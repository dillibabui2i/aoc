<?php

namespace App\VBC\Repositories\Hospitalization;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerFieldsDTO;
use App\VBC\Enum\Encounter\EncounterStatusEnum;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerClosingReasonEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\Encounter\EncounterModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerCustomPropertyDefinitionModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerPropertyModel;
use App\VBC\Models\PatientCustomProperty\PatientCustomPropertyDefinitionModel;
use App\VBC\Models\PatientCustomProperty\PatientCustomPropertyModel;
use Carbon\CarbonInterface;
use RuntimeException;

class HospitalizationWriteRepository
{
    public function __construct(
        private HospitalizationTrackerModel $hospitalizationTrackerModel,
        private EncounterModel $encounterModel,
        private PatientCustomPropertyModel $patientCustomPropertyModel,
        private PatientCustomPropertyDefinitionModel $patientCustomPropertyDefinitionModel,
        private HospitalizationTrackerPropertyModel $trackerPropertyModel,
        private HospitalizationTrackerCustomPropertyDefinitionModel $trackerPropertyDefinitionModel,
    ) {
    }

    /**
     * Takes a write lock on this client's encounter rows. Under REPEATABLE READ the next-key lock on
     * encounters.patient_id also covers the gap, so a concurrent request cannot insert an encounter for
     * the same client until we commit — which is what stops two simultaneous submits from both passing
     * the "no active tracker" check and creating duplicates.
     */
    public function lockEncountersForPatient(int $patientId): void
    {
        $this->encounterModel->newQuery()
            ->where('patient_id', $patientId)
            ->lockForUpdate()
            ->get(['id']);
    }

    /**
     * A tracker always hangs off an encounter (there is no patient_id on hospitalization_trackers), so
     * creating a tracker for a client means opening the encounter first. Only the columns needed to
     * satisfy the FK are set here — the admission/facility details belong to the Current
     * Hospitalization Info tab, which is a separate story.
     */
    public function createEncounterForPatient(
        int $patientId,
        EncounterTypeEnum $encounterType = EncounterTypeEnum::IP,
        ?int $parentEncounterId = null,
        ?string $transferReason = null,
    ): EncounterModel
    {
        /** @var EncounterModel $encounter */
        $encounter = $this->encounterModel->newQuery()->create([
            'patient_id' => $patientId,
            'encounter_parent_id' => $parentEncounterId,
            'transfer_reason' => $transferReason,
            'type' => $encounterType,
            'status' => EncounterStatusEnum::ADMITTED,
        ]);

        return $encounter;
    }

    /** The patient left this encounter's facility for another one (the transfer's new encounter is its child). */
    public function markEncounterTransferred(EncounterModel $encounter): void
    {
        $encounter->update(['status' => EncounterStatusEnum::TRANSFERRED]);
    }

    public function createTracker(
        int $encounterId,
        int $authorId,
        HospitalizationTrackerStatusEnum $status = HospitalizationTrackerStatusEnum::OPEN,
    ): HospitalizationTrackerModel
    {
        /** @var HospitalizationTrackerModel $tracker */
        $tracker = $this->hospitalizationTrackerModel->newQuery()->create([
            'encounter_id' => $encounterId,
            'author_id' => $authorId,
            'status' => $status,
        ]);

        return $tracker;
    }

    /**
     * Takes a write lock on this tracker's row so a concurrent Outcome submission blocks until this one
     * commits, then re-reads its status fresh rather than trusting a value that could be stale by then —
     * same TOCTOU concern as lockEncountersForPatient() above, one row instead of a gap.
     */
    public function lockForUpdate(HospitalizationTrackerModel $tracker): HospitalizationTrackerModel
    {
        /** @var HospitalizationTrackerModel $locked */
        $locked = $this->hospitalizationTrackerModel->newQuery()
            ->lockForUpdate()
            ->findOrFail($tracker->id);

        return $locked;
    }

    /**
     * An Outcome transition: the new status, the closing reason when it is being closed, and — for the
     * discharge-lifecycle outcomes that reassign the tracker (Ready for Review, Ready for Next Steps) —
     * the new assignee and/or who it was sent to review by. Omitted (null) means "leave as is", not
     * "clear" — Ready for Next Steps changes the assignee without touching who sent it to review.
     */
    public function updateStatus(
        HospitalizationTrackerModel $tracker,
        HospitalizationTrackerStatusEnum $status,
        ?HospitalizationTrackerClosingReasonEnum $reason = null,
        ?int $assignedToId = null,
        ?int $sentToReviewById = null,
    ): void
    {
        $tracker->update(array_filter([
            'status' => $status,
            'closing_reason' => $reason,
            'assigned_to_id' => $assignedToId,
            'sent_to_review_by_id' => $sentToReviewById,
        ], fn ($value) => $value !== null));
    }

    /** Every submitted field of the header form, whether it arrived with the tracker or as a later edit. */
    public function updateFields(HospitalizationTrackerModel $tracker, HospitalizationTrackerFieldsDTO $fields): void
    {
        foreach ($fields->values as $field => $value) {
            // The keys come from validated input restricted to the enum, so an unknown one is a bug in
            // our own code and from() failing loudly is the right outcome.
            $this->updateField($tracker, HospitalizationTrackerFieldEnum::from($field), $value);
        }
    }

    /**
     * Writes one field of the form to wherever the field declares it lives: a column on the tracker, a
     * column on its encounter, or a custom property (EAV) of either the client profile or the tracker.
     */
    private function updateField(HospitalizationTrackerModel $tracker, HospitalizationTrackerFieldEnum $field, mixed $value): void
    {
        $trackerColumn = $field->trackerColumn();

        if (!empty($trackerColumn)) {
            $tracker->update([$trackerColumn => $value]);

            return;
        }

        $encounterColumn = $field->encounterColumn();

        if (!empty($encounterColumn)) {
            $tracker->encounter->update([$encounterColumn => $value]);

            return;
        }

        $patientPropertyKey = $field->patientPropertyKey();

        if (!empty($patientPropertyKey)) {
            $this->updatePatientProperty($tracker, $patientPropertyKey, $value);

            return;
        }

        $trackerPropertyKey = $field->trackerPropertyKey();

        if (empty($trackerPropertyKey)) {
            throw new RuntimeException("Field [{$field->value}] has no storage target.");
        }

        $this->updateTrackerProperty($tracker, $trackerPropertyKey, $value);
    }

    /**
     * Upsert of a client-profile custom property. The property row's own updated_at is what the form
     * shows as "last updated", so touching the value is enough — no separate timestamp to maintain.
     */
    private function updatePatientProperty(HospitalizationTrackerModel $tracker, string $propertyKey, mixed $value): void
    {
        /** @var PatientCustomPropertyDefinitionModel|null $definition */
        $definition = $this->patientCustomPropertyDefinitionModel->newQuery()
            ->where('key', $propertyKey)
            ->first();

        if (empty($definition)) {
            throw new RuntimeException("Patient custom property definition [{$propertyKey}] is missing.");
        }

        $patientId = $tracker->encounter->patient_id;
        /** @var PatientCustomPropertyModel|null $existing */
        $existing = $this->patientCustomPropertyModel->newQuery()
            ->where('patient_id', $patientId)
            ->where('definition_id', $definition->id)
            ->first();

        // The form shows the property row's updated_at as "last updated", so a write
        // that doesn't change anything must not happen at all — otherwise submitting
        // the form would stamp a toggle the user never touched. A missing row counts
        // as the definition's default, not as "no value".
        $value = $this->normaliseForStorage($value);
        $current = empty($existing) ? $definition->default_value : $existing->value;

        if ($current === $value) {
            return;
        }

        if (!empty($existing)) {
            $existing->update(['value' => $value]);

            return;
        }

        $this->patientCustomPropertyModel->newQuery()->create([
            'patient_id' => $patientId,
            'definition_id' => $definition->id,
            'value' => $value,
        ]);
    }

    /**
     * Upsert of a tracker custom property — same shape as the client-profile one above, against the
     * tracker's own EAV tables. Definitions are seeded, never created here: an unknown key means the
     * field was declared in the enum without being seeded, which has to fail rather than silently
     * store nothing.
     *
     * Public: also called directly by HospitalizationTrackerDiagnosisService for the "Was the diagnosis
     * made?" toggle (PD-3498), which isn't a HospitalizationTrackerFieldEnum case — it belongs to the
     * Diagnosis tab, not the header form or Current Hospitalization Info section, so it doesn't go
     * through updateField()/updateFields() above, just reuses this same EAV upsert.
     */
    public function updateTrackerProperty(HospitalizationTrackerModel $tracker, string $propertyKey, mixed $value): void
    {
        /** @var HospitalizationTrackerCustomPropertyDefinitionModel|null $definition */
        $definition = $this->trackerPropertyDefinitionModel->newQuery()
            ->where('key', $propertyKey)
            ->first();

        if (empty($definition)) {
            throw new RuntimeException("Hospitalization tracker custom property definition [{$propertyKey}] is missing.");
        }

        /** @var HospitalizationTrackerPropertyModel|null $existing */
        $existing = $this->trackerPropertyModel->newQuery()
            ->where('hospitalization_tracker_id', $tracker->id)
            ->where('definition_id', $definition->id)
            ->first();

        $value = $this->normaliseForStorage($value);
        $current = empty($existing) ? $definition->default_value : $existing->value;

        if ($current === $value) {
            return;
        }

        if (!empty($existing)) {
            $existing->update(['value' => $value]);

            return;
        }

        $this->trackerPropertyModel->newQuery()->create([
            'hospitalization_tracker_id' => $tracker->id,
            'definition_id' => $definition->id,
            'value' => $value,
        ]);
    }

    /**
     * EAV values are stored as JSON, which would turn a Carbon into its serialised form and make the
     * "did this actually change?" comparison above never match. Dates go in as plain strings instead.
     */
    private function normaliseForStorage(mixed $value): mixed
    {
        return $value instanceof CarbonInterface ? $value->toDateTimeString() : $value;
    }
}
