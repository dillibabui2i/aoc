<?php

namespace App\VBC\Http\Resources\Hospitalization;

use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFollowupStatusEnum;
use App\VBC\Http\Resources\Followup\FollowupResource;
use App\VBC\Http\Resources\HospitalizationTrackerContact\HospitalizationTrackerContactResource;
use App\VBC\Http\Resources\HospitalizationTrackerDiagnosis\HospitalizationTrackerDiagnosisResource;
use App\VBC\Http\Resources\HospitalizationTrackerTraining\HospitalizationTrackerTrainingResource;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerFollowupModel;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Services\Hospitalization\HospitalizationTrackerFieldValues;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The saved "Hospitalization Record": the tracker's own fields, the Current Hospitalization Info
 * section, and the client details it was opened for. The client half is delegated to
 * HospitalizationClientProfileResource, the same mapping used to pre-fill the form before the tracker
 * exists; the section's fields come from the field enum, so they are named and shaped exactly as the
 * form submits them back.
 */
class HospitalizationTrackerResource extends JsonResource
{
    public function toArray($request): array
    {
        /** @var HospitalizationTrackerModel $tracker */
        $tracker = $this->resource;

        $trackerFields = [
            'tracker_id' => $tracker->id,
            'creation_date' => $tracker->created_at?->format(config('vbc.date_time_format')),
            'author_name' => $tracker->author?->name,
            'assigned_to_id' => $tracker->assigned_to_id,
            'assigned_to_name' => $tracker->assignedTo?->name,
            'sent_to_review_by_id' => $tracker->sent_to_review_by_id,
            'sent_to_review_by_name' => $tracker->sentToReviewBy?->name,
            'status' => $tracker->status->label(),
            // Which kind of tracker this is (IP/ED) — lets the frontend route "open the active tracker"
            // (PD-3983 AC16) to the viewer built for that kind instead of assuming Hospitalization's.
            'encounter_type' => $tracker->encounter->type->value,
            'level' => $tracker->icm_level?->value,
            'level_label' => $tracker->icm_level?->label(),
            'closing_reason' => $tracker->closing_reason?->value,
            'closing_reason_label' => $tracker->closing_reason?->label(),
            // Transfer chain (the blue note on a nested tracker): null when the patient was never transferred in.
            'first_facility_name' => $tracker->encounter->previousEncounters()->last()?->facility_name,
            'is_transfer' => !empty($tracker->encounter->encounter_parent_id),
        ];

        // Everything the Current Hospitalization Info / ED Visit Info section edits, read back out of
        // the four places those fields live in — one mapping shared with the write path (see the field
        // enum). Which field set depends on the tracker's own encounter type (PD-3983): an ED tracker's
        // "Hospitalization Info" tab shows a different field set than an IP tracker's.
        $infoFields = $tracker->encounter->type === EncounterTypeEnum::ED
            ? HospitalizationTrackerFieldEnum::edVisitInfoFields()
            : HospitalizationTrackerFieldEnum::currentInfoFields();
        $currentInfo = app(HospitalizationTrackerFieldValues::class)->readAll($tracker, $infoFields);

        // The tracker is already loaded here, so it's handed to each follow-up directly rather than
        // having FollowupResource trigger one lazy-loaded query per row (AC37-AC41).
        $followUps = $tracker->followups
            ->sortByDesc('created_at')
            ->values()
            ->map(function (HospitalizationTrackerFollowupModel $followup) use ($tracker, $request) {
                $followup->setRelation('hospitalizationTracker', $tracker);

                return (new FollowupResource($followup))->toArray($request);
            })
            ->all();

        // ED Visit follow-up workflow (PD-4073), ED trackers only: the current follow-up status, Not
        // Started until the first outcome is submitted (no follow-up row exists before then).
        $edFollowUp = [];
        if ($tracker->encounter->type === EncounterTypeEnum::ED) {
            $edFollowupStatus = $tracker->followups
                ->filter(fn (HospitalizationTrackerFollowupModel $f) => in_array($f->status, HospitalizationTrackerFollowupStatusEnum::edVisitStatuses(), true))
                ->sortByDesc('id')
                ->first()?->status ?? HospitalizationTrackerFollowupStatusEnum::NOT_STARTED;

            $edFollowUp = [
                'ed_follow_up_status' => $edFollowupStatus->value,
                'ed_follow_up_status_label' => $edFollowupStatus->label(),
            ];
        }

        // Contacts (PD-3693): tracker-scoped (AC11), so a plain resource map is enough — no lazy
        // per-row query the way FollowupResource would trigger, since `type` is already eager-loaded.
        $contacts = $tracker->contacts
            ->sortByDesc('created_at')
            ->values()
            ->map(fn ($contact) => (new HospitalizationTrackerContactResource($contact))->toArray($request))
            ->all();

        // Diagnoses (PD-3498): hang off the tracker's encounter (see HospitalizationTrackerModel::diagnoses()),
        // so eager-loaded the same lazy-query-free way as Contacts.
        $diagnoses = $tracker->diagnoses
            ->sortByDesc('created_at')
            ->values()
            ->map(fn ($diagnosis) => (new HospitalizationTrackerDiagnosisResource($diagnosis))->toArray($request))
            ->all();

        // "Was the diagnosis made?" toggle: a tracker custom property (EAV), not a
        // HospitalizationTrackerFieldEnum case — see HospitalizationTrackerDiagnosisService. Read
        // directly off the already eager-loaded `properties.definition` relation, same data `high_utilizer`
        // etc. would use if they were tracker- rather than patient-scoped.
        $wasDiagnosisMadeProperty = $tracker->properties
            ->first(fn ($property) => $property->definition->key === 'was_diagnosis_made');

        // Education (Training/Education records): tracker-scoped, same lazy-query-free eager-load
        // shape as Contacts/Diagnoses (`type`/`source` already eager-loaded).
        $educationRecords = $tracker->trainings
            ->sortByDesc('created_at')
            ->values()
            ->map(fn ($training) => (new HospitalizationTrackerTrainingResource($training))->toArray($request))
            ->all();

        // "Staff / Family Training/Education required?" toggle: a tracker custom property (EAV),
        // same mechanism as "Was the diagnosis made?" above.
        $staffFamilyTrainingRequiredProperty = $tracker->properties
            ->first(fn ($property) => $property->definition->key === 'staff_family_training_required');

        return $trackerFields
            + ['hospitalization_status' => $tracker->encounter->status?->label()]
            + ['follow_ups' => $followUps]
            + $edFollowUp
            + ['contacts' => $contacts]
            + ['diagnoses' => $diagnoses]
            + ['was_diagnosis_made' => $wasDiagnosisMadeProperty?->value ?? false]
            + ['education_records' => $educationRecords]
            + ['staff_family_training_required' => $staffFamilyTrainingRequiredProperty?->value ?? false]
            + $currentInfo
            + HospitalizationClientProfileResource::make($tracker->encounter->patient)->toArray($request);
    }
}
