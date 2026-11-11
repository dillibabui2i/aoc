<?php

namespace App\VBC\Http\Requests\Hospitalization;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerCreationDTO;
use App\VBC\DTO\Hospitalization\HospitalizationTrackerFieldsDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Repositories\Hospitalization\HospitalizationReadRepository;
use App\VBC\Rules\ActiveClientExists;
use Illuminate\Validation\Rule;

class CreateHospitalizationTrackerRequest extends CoreRequest
{
    public function __construct(private readonly HospitalizationReadRepository $repository)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        // The form lets the user set the assignee, level and the client-profile toggles before submitting,
        // so creation accepts every editable field up front instead of forcing a follow-up PATCH. Taking
        // the set from the enum keeps "editable" defined in exactly one place.
        $rules = [
            'patient_id' => ['required', 'integer', new ActiveClientExists($this->repository)],
            // Defaults to IP when omitted (every tracker today is IP); wired through explicitly so a
            // future ED creation flow only needs to send a different value here.
            'encounter_type' => ['sometimes', 'string', Rule::enum(EncounterTypeEnum::class)],
        ];

        foreach (HospitalizationTrackerFieldEnum::HEADER_FIELDS as $field) {
            // The toggles may be left out entirely (they keep the client's current value); the required
            // fields have to be answered before a tracker can exist.
            $presence = $field->isRequired() ? ['required'] : ['sometimes'];
            $rules[$field->value] = array_merge($presence, $field->valueRules());
        }

        return $rules;
    }

    /** So a 422 names the field the way the form labels it, not as "assigned to id". */
    public function attributes(): array
    {
        $attributes = [];

        foreach (HospitalizationTrackerFieldEnum::HEADER_FIELDS as $field) {
            $attributes[$field->value] = $field->label();
        }

        return $attributes;
    }

    public function toDto(): HospitalizationTrackerCreationDTO
    {
        // Only fields actually submitted are carried over, so an omitted one keeps its default rather
        // than being written as null/false.
        $submitted = array_intersect_key(
            $this->validated(),
            array_flip(HospitalizationTrackerFieldEnum::headerFieldValues())
        );

        return new HospitalizationTrackerCreationDTO(
            patientId: (int) $this->input('patient_id'),
            fields: HospitalizationTrackerFieldsDTO::cast($submitted),
            encounterType: $this->filled('encounter_type') ? EncounterTypeEnum::from($this->input('encounter_type')) : EncounterTypeEnum::IP,
        );
    }
}
