<?php

namespace App\VBC\Http\Requests\CareCoordination;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\CareCoordination\CareCoordinationUpdateDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Repositories\CareCoordination\CareCoordinationReadRepository;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCareCoordinationRequest extends CoreRequest
{
    public function __construct(private readonly CareCoordinationReadRepository $repository)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'care_coordination_type_id' => ['required', 'integer', 'exists:care_coordination_types,id'],
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date_format:' . config('vbc.date_time_format')],
            'completed_at' => ['nullable', 'date_format:' . config('vbc.date_time_format')],
            'notes' => ['nullable', 'string'],
            'encounter_type' => ['required', Rule::enum(EncounterTypeEnum::class)],
        ];
    }

    /**
     * PD-3969: server-side read-only. An activity is editable only while its encounter's tracker is
     * In Progress, and only through the flow matching its encounter type.
     * An unknown id is left to the controller's 404.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $activity = $this->repository->findById((int) $this->route('id'));
            if (empty($activity)) {
                return;
            }

            $tracker = $activity->encounter->hospitalizationTrackers->first();
            if ($tracker?->status !== HospitalizationTrackerStatusEnum::IN_PROGRESS) {
                $validator->errors()->add('hospitalization_tracker_id', 'This Care Coordination activity is read-only because its tracker is not In Progress.');

                return;
            }

            $type = EncounterTypeEnum::tryFrom((string) $this->input('encounter_type'));
            // A missing/invalid type is reported by the `encounter_type` rule itself.
            if ($type !== null && $activity->encounter->type !== $type) {
                $validator->errors()->add('encounter_type', 'This Care Coordination activity does not belong to the given encounter type.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'care_coordination_type_id' => 'Coordination Type',
            'assigned_to_id' => 'Assigned To',
            'due_at' => 'Due Date',
            'completed_at' => 'Completion Date',
        ];
    }

    public function toDto(): CareCoordinationUpdateDTO
    {
        return new CareCoordinationUpdateDTO(
            careCoordinationTypeId: (int) $this->input('care_coordination_type_id'),
            assignedToId: $this->filled('assigned_to_id') ? (int) $this->input('assigned_to_id') : null,
            dueAt: $this->filled('due_at')
                ? Carbon::createFromFormat(config('vbc.date_time_format'), $this->input('due_at'))
                : null,
            completedAt: $this->filled('completed_at')
                ? Carbon::createFromFormat(config('vbc.date_time_format'), $this->input('completed_at'))
                : null,
            notes: $this->filled('notes') ? $this->input('notes') : null,
        );
    }
}
