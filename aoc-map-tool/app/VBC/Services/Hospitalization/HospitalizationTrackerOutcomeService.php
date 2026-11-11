<?php

namespace App\VBC\Services\Hospitalization;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerOutcomeDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Repositories\Hospitalization\HospitalizationWriteRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Applies an Outcome transition. Two lifecycles share this same service/table (trackers are
 * distinguished by `encounter_type`):
 *  - Hospitalization (IP): Open -> In Progress -> Closed, and the discharge lifecycle that continues
 *    past it: In Progress -> Discharged -> Ready for Review -> Ready for Next Steps -> Completed.
 *  - ED Visit (PD-4069): In Progress -> Ready for Review -> Ready for Next Steps -> Completed, with
 *    Closed reachable from any of the first three.
 * Closing a Hospitalization with the Transfer reason is not a plain Closed: it also opens the patient's next
 * hospitalization as a new, nested tracker (see transfer()).
 * Validates the transition is legal for the tracker's current status (per its own encounter type),
 * requires the Current Hospitalization Info section to be complete before allowing Hospitalization's In
 * Progress or Completed (a larger set of fields for the latter — not required for ED, which has no such
 * guard), persists the new status (and whichever of closing reason / discharge fields / new assignee the
 * outcome carries), and writes the Activity Log entry the ticket specifies.
 */
class HospitalizationTrackerOutcomeService
{
    /** Which statuses an outcome may be applied from, per encounter type. */
    private const ALLOWED_SOURCE_STATUSES = [
        'IP' => [
            'IN_PROGRESS' => [HospitalizationTrackerStatusEnum::OPEN],
            'CLOSED' => [HospitalizationTrackerStatusEnum::OPEN, HospitalizationTrackerStatusEnum::IN_PROGRESS],
            'DISCHARGED' => [HospitalizationTrackerStatusEnum::IN_PROGRESS],
            'READY_FOR_REVIEW' => [HospitalizationTrackerStatusEnum::DISCHARGED],
            'READY_FOR_NEXT_STEPS' => [HospitalizationTrackerStatusEnum::READY_FOR_REVIEW],
            'COMPLETED' => [HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS],
        ],
        'ED' => [
            'READY_FOR_REVIEW' => [HospitalizationTrackerStatusEnum::IN_PROGRESS],
            'READY_FOR_NEXT_STEPS' => [HospitalizationTrackerStatusEnum::READY_FOR_REVIEW],
            'COMPLETED' => [HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS],
            'CLOSED' => [
                HospitalizationTrackerStatusEnum::IN_PROGRESS,
                HospitalizationTrackerStatusEnum::READY_FOR_REVIEW,
                HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS,
            ],
        ],
    ];

    public function __construct(
        private readonly HospitalizationReadRepository $readRepository,
        private readonly HospitalizationWriteRepository $writeRepository,
        private readonly HospitalizationTrackerActivityLog $activityLog,
        private readonly HospitalizationTrackerFieldValues $fieldValues,
    ) {
    }

    /**
     * @throws Throwable
     */
    public function transition(HospitalizationTrackerModel $tracker, HospitalizationTrackerOutcomeDTO $dto): ?HospitalizationTrackerModel
    {
        // The Current Hospitalization Info completeness guard below is Hospitalization (IP)-specific. ED has
        // its own, narrower one (six Hospitalization Info fields, PD-5800) that gates every ED transition
        // and runs inside the transaction, against the locked row.
        $isHospitalization = $tracker->encounter->type === EncounterTypeEnum::IP;

        if ($isHospitalization && $dto->outcome === HospitalizationTrackerStatusEnum::IN_PROGRESS) {
            $this->guardCurrentInfoIsComplete(
                $tracker,
                HospitalizationTrackerFieldEnum::requiredForInProgressFields(),
                $dto->outcome,
            );
        }

        if ($isHospitalization && $dto->outcome === HospitalizationTrackerStatusEnum::COMPLETED) {
            $this->guardCurrentInfoIsComplete(
                $tracker,
                HospitalizationTrackerFieldEnum::requiredForCompletedFields(),
                $dto->outcome,
            );
        }

        $author = auth()->user()?->name;
        $isTransfer = HospitalizationTrackerOutcomeDTO::isTransfer($dto->outcome, $dto->reason);
        // A transfer's own comment (where to, and why) is a separate entry, so the Closed one carries no notes.
        $note = $this->buildNote($isTransfer ? new HospitalizationTrackerOutcomeDTO($dto->outcome, $dto->reason) : $dto, $author);

        $resultTrackerId = DB::transaction(function () use ($tracker, $dto, $note, $isTransfer, $author) {
            // Locked read, not the $tracker the controller fetched earlier: a concurrent Outcome
            // submission on the same tracker (double-click, two tabs, two ICM users) blocks here until
            // this transaction commits, then re-checks the transition against the status that
            // submission actually landed on. Guarding against the pre-transaction $tracker instead would
            // let two racing submits both pass against the same stale status and both write a note.
            $locked = $this->writeRepository->lockForUpdate($tracker);
            $this->guardTransitionIsAllowed($locked, $dto->outcome);

            // Read off the locked row, so a simultaneous edit cannot slip between check and status write.
            if ($locked->encounter->type === EncounterTypeEnum::ED) {
                $this->guardCurrentInfoIsComplete(
                    $locked,
                    HospitalizationTrackerFieldEnum::requiredForEdVisitTransitionFields(),
                    $dto->outcome,
                );
            }

            if ($isTransfer) {
                return $this->transfer($locked, $dto, $note, $author);
            }

            if ($dto->outcome === HospitalizationTrackerStatusEnum::DISCHARGED) {
                $this->writeRepository->updateFields($locked, $dto->fields);
            }

            // Ready for Review is the only transition that needs to remember who it was sent to review
            // by — read off the still-locked row before it is overwritten with the reviewer below.
            // For an ED Visit it is the user submitting the review (PD-4069 AC6): a new ED tracker may have
            // no assignee, so the previous assigned_to_id can't be relied on there.
            $sentToReviewById = null;
            if ($dto->outcome === HospitalizationTrackerStatusEnum::READY_FOR_REVIEW) {
                $sentToReviewById = $locked->encounter->type === EncounterTypeEnum::ED
                    ? (int) auth()->id()
                    : $locked->assigned_to_id;
            }

            $this->writeRepository->updateStatus(
                $locked,
                $dto->outcome,
                $dto->reason,
                $dto->assigneeId,
                $sentToReviewById,
            );
            $this->activityLog->write($locked, $note);

            return $locked->id;
        });

        return $this->readRepository->findForForm($resultTrackerId);
    }

    /**
     * Closes the tracker as Transferred and opens the patient's next hospitalization, nested under it:
     * the new encounter's parent is the old one, which is what chains the history together. The old
     * tracker is already Closed by the time the new one is created, so it no longer counts as the
     * patient's active tracker and the new one becomes the Current Hospitalization Info.
     *
     * @return int the new tracker's id — what the caller reads back
     */
    private function transfer(HospitalizationTrackerModel $locked, HospitalizationTrackerOutcomeDTO $dto, string $closedNote, ?string $author): int
    {
        $encounter = $locked->encounter;
        $facility = $dto->fields?->values[HospitalizationTrackerFieldEnum::FACILITY_NAME->value] ?? null;

        $this->writeRepository->lockEncountersForPatient($encounter->patient_id);
        $this->writeRepository->updateStatus($locked, $dto->outcome, $dto->reason);
        $this->writeRepository->markEncounterTransferred($encounter);
        $this->activityLog->write($locked, $closedNote);
        $this->activityLog->write($locked, $this->buildTransferNote($facility, $dto->notes));

        $newEncounter = $this->writeRepository->createEncounterForPatient($encounter->patient_id, $encounter->type, $encounter->id, $dto->notes);
        $newTracker = $this->writeRepository->createTracker($newEncounter->id, (int) auth()->id(), HospitalizationTrackerStatusEnum::IN_PROGRESS);
        $this->writeRepository->updateStatus($newTracker, HospitalizationTrackerStatusEnum::IN_PROGRESS, null, $locked->assigned_to_id);
        $this->writeRepository->updateFields($newTracker, $dto->fields);
        // The frontend tells an In Progress tracker apart from a never-progressed one by this very entry.
        $this->activityLog->write($newTracker, $this->buildNote(
            new HospitalizationTrackerOutcomeDTO(HospitalizationTrackerStatusEnum::IN_PROGRESS, null),
            $author,
        ));

        return $newTracker->id;
    }

    private function buildTransferNote(?string $facility, ?string $reason): string
    {
        $line = "Patient transferred to {$facility}";

        return empty($reason) ? $line : "{$line}\nReason:\n{$reason}";
    }

    private function guardTransitionIsAllowed(HospitalizationTrackerModel $tracker, HospitalizationTrackerStatusEnum $outcome): void
    {
        $allowedSources = self::ALLOWED_SOURCE_STATUSES[$tracker->encounter->type->value][$outcome->value] ?? [];

        if (!in_array($tracker->status, $allowedSources, true)) {
            throw new InvalidHospitalizationOutcomeTransitionException(
                "Cannot move a {$tracker->status->label()} tracker to {$outcome->label()}."
            );
        }
    }

    /**
     * In Progress and Completed each require a different subset of the Current Hospitalization Info
     * section to already be filled in — Completed's superset adds Family Contacted / Care Requirements /
     * 30 Days Outcome on top of what In Progress already required (see
     * `HospitalizationTrackerFieldEnum::requiredForCompletedFields()`).
     *
     * @param  array<int, HospitalizationTrackerFieldEnum>  $requiredFields
     */
    private function guardCurrentInfoIsComplete(
        HospitalizationTrackerModel $tracker,
        array $requiredFields,
        HospitalizationTrackerStatusEnum $outcome,
    ): void {
        $values = $this->fieldValues->readAll($tracker, $requiredFields);

        $missingLabels = [];
        $missingKeys = [];
        foreach ($requiredFields as $field) {
            if ($this->isBlank($values[$field->value])) {
                $missingLabels[] = $field->shortLabel();
                $missingKeys[] = $field->value;
            }
        }

        if (!empty($missingLabels)) {
            throw new InvalidHospitalizationOutcomeTransitionException(
                "Complete the following Current Hospitalization Info fields before moving to {$outcome->label()}: "
                . implode(', ', $missingLabels) . '.',
                $missingKeys,
            );
        }
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || $value === '' || $value === [];
    }

    private function buildNote(HospitalizationTrackerOutcomeDTO $dto, ?string $author): string
    {
        $when = now()->format(config('vbc.date_time_format'));

        return match ($dto->outcome) {
            HospitalizationTrackerStatusEnum::IN_PROGRESS => "In Progress by {$author} at {$when}\n"
                . "Status changed to 'In Progress' by {$author}",
            HospitalizationTrackerStatusEnum::DISCHARGED => $this->appendNotes(
                "Discharged by {$author} at {$when}\nStatus changed to 'Discharged' by {$author}",
                $dto->notes,
            ),
            HospitalizationTrackerStatusEnum::READY_FOR_REVIEW => $this->appendNotes(
                "Ready for Review by {$author} at {$when}",
                $dto->notes,
            ),
            HospitalizationTrackerStatusEnum::READY_FOR_NEXT_STEPS => $this->appendNotes(
                "Ready for Next Steps by {$author} at {$when}",
                $dto->notes,
            ),
            HospitalizationTrackerStatusEnum::COMPLETED => $this->appendNotes(
                "Completed by {$author} at {$when}",
                $dto->notes,
            ),
            // Closure notes (PD-4069 AC11/AC13) are optional, same as every other discharge-lifecycle
            // outcome above — Hospitalization's Close flow has never sent `notes`, so this stays a no-op
            // for it (appendNotes() only adds a line when one is actually given).
            default => $this->appendNotes(
                "Closed by {$author} at {$when}\n"
                . "Status changed to 'Closed' by {$author} - Reason: {$dto->reason?->label()}",
                $dto->notes,
            ),
        };
    }

    /** Notes are optional on every discharge-lifecycle outcome — appended as an extra line only when given. */
    private function appendNotes(string $line1, ?string $notes): string
    {
        return empty($notes) ? $line1 : "{$line1}\n{$notes}";
    }
}
