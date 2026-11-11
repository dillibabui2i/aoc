<?php

namespace App\VBC\Services\SickPlan;

use App\VBC\DTO\SickPlan\SickPlanItemDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Models\SickPlan\SickPlanItemModel;
use App\VBC\Models\SickPlan\SickPlanModel;
use App\VBC\Repositories\SickPlan\SickPlanReadRepository;
use App\VBC\Repositories\SickPlan\SickPlanWriteRepository;
use App\VBC\Services\Hospitalization\HospitalizationTrackerActivityLog;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

/**
 * A Sick Plan belongs to the client, not the tracker: it is created once and reused across every
 * hospitalization tracker that client ever has (AC5/AC12). Its items are only editable while the
 * tracker through which they're being edited is "In Progress" (AC7-AC9); every other status shows
 * them read-only.
 */
class SickPlanService
{
    public function __construct(
        private readonly SickPlanReadRepository $readRepository,
        private readonly SickPlanWriteRepository $writeRepository,
        private readonly HospitalizationTrackerActivityLog $activityLog,
    ) {
    }

    /**
     * Creates a blank Sick Plan (one item per catalog entry) for the client if none exists yet;
     * reuses the existing one otherwise (AC3/AC5/AC12). Called from the tracker creation transaction.
     * @throws Throwable
     */
    public function ensureForPatient(int $patientId, int $authorId): SickPlanModel
    {
        $existing = $this->readRepository->findForPatient($patientId);
        if (!empty($existing)) {
            return $existing;
        }

        return DB::transaction(fn () => $this->writeRepository->createBlankForPatient($patientId, $authorId));
    }

    public function updateItem(HospitalizationTrackerModel $tracker, int $itemId, SickPlanItemDTO $dto): SickPlanModel
    {
        $this->guardEditable($tracker);
        $patientId = $tracker->encounter->patient_id;

        $plan = $this->readRepository->findForPatient($patientId);
        if (empty($plan)) {
            throw new RuntimeException("Client [{$patientId}] has no Sick Plan yet.");
        }

        /** @var SickPlanItemModel|null $item */
        $item = $plan->items->firstWhere('id', $itemId);
        if (empty($item)) {
            throw new RuntimeException("Sick Plan item [{$itemId}] does not belong to this client's Sick Plan.");
        }

        $catalogId = $this->writeRepository->catalogIdForType($dto->sickPlanType);

        $item->loadMissing(['catalog']);
        $before = [
            'completed' => (bool) $item->completed,
            'notes' => $this->normaliseNotes($item->notes),
        ];
        $itemType = $item->catalog->domain;
        $currentUserId = (int) auth()->id();

        DB::transaction(function () use ($tracker, $item, $catalogId, $dto, $currentUserId, $before, $itemType) {
            $this->writeRepository->updateItem($item, $catalogId, $dto->completed, $dto->notes, $currentUserId);

            $after = [
                'completed' => $dto->completed,
                'notes' => $this->normaliseNotes($dto->notes),
            ];
            $description = $this->describeItemChange($itemType, $before, $after);
            // ActivityNotes swallows insert errors and returns false, so turn that into a rollback (AC9).
            if ($description !== null && !$this->activityLog->write($tracker, $description)) {
                throw new RuntimeException("Could not write the Activity Log note for tracker [{$tracker->id}].");
            }
        });

        return $this->readRepository->find($plan->id);
    }

    /**
     * One Activity Log line for the whole save, or null when neither Completed? nor Notes changed.
     *
     * @param  array{completed: bool, notes: ?string}  $before
     * @param  array{completed: bool, notes: ?string}  $after
     */
    private function describeItemChange(string $itemType, array $before, array $after): ?string
    {
        if ($before['completed'] === $after['completed'] && $before['notes'] === $after['notes']) {
            return null;
        }

        $parts = [];
        if ($before['completed'] !== $after['completed']) {
            $parts[] = $this->describeField(
                'Completed?',
                $before['completed'] ? 'Yes' : 'No',
                $after['completed'] ? 'Yes' : 'No',
            );
        }
        if ($before['notes'] !== $after['notes']) {
            $parts[] = $this->describeField(
                'Notes',
                $before['notes'] === null ? null : "'{$before['notes']}'",
                $after['notes'] === null ? null : "'{$after['notes']}'",
            );
        }

        $author = auth()->user()?->name;
        $by = empty($author) ? '' : ' by ' . $author;

        return "Sick Plan ({$itemType}): " . implode('; ', $parts) . $by;
    }

    private function describeField(string $label, ?string $old, ?string $new): string
    {
        $new = $new ?? '(empty)';

        return $old === null
            ? "{$label} set to {$new}"
            : "{$label} updated from {$old} to {$new}";
    }

    private function normaliseNotes(?string $notes): ?string
    {
        return trim((string) $notes) === '' ? null : $notes;
    }

    private function guardEditable(HospitalizationTrackerModel $tracker): void
    {
        if ($tracker->status !== HospitalizationTrackerStatusEnum::IN_PROGRESS) {
            throw new SickPlanNotEditableException(
                "Sick Plan fields are only editable while the tracker is In Progress (currently {$tracker->status->label()})."
            );
        }
    }
}
