<?php

namespace App\VBC\Http\Requests\CareCoordination;

use App\Http\Requests\CoreRequest;
use App\VBC\DTO\CareCoordination\CareCoordinationCreationDTO;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Repositories\CareCoordination\CareCoordinationReadRepository;
use App\VBC\Rules\CareCoordinationTrackerIsInProgress;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class StoreCareCoordinationRequest extends CoreRequest
{
    public function __construct(private readonly CareCoordinationReadRepository $repository)
    {
        parent::__construct();
    }

    public function rules(): array
    {
        return [
            'hospitalization_tracker_id' => ['required', 'integer', new CareCoordinationTrackerIsInProgress($this->repository, $this->encounterType())],
            'encounter_type' => ['required', Rule::enum(EncounterTypeEnum::class)],
            'care_coordination_type_id' => ['required', 'integer', 'exists:care_coordination_types,id'],
            'assigned_to_id' => ['nullable', 'integer', 'exists:users,id'],
            'due_at' => ['nullable', 'date_format:' . config('vbc.date_time_format')],
            'completed_at' => ['nullable', 'date_format:' . config('vbc.date_time_format')],
            'notes' => ['nullable', 'string'],
        ];
    }

    /** PD-3969: the tracker must belong to an encounter of this type (ED or IP). */
    private function encounterType(): EncounterTypeEnum
    {
        // An absent/invalid value fails the `encounter_type` rule; ED here only keeps the tracker rule typed.
        return EncounterTypeEnum::tryFrom((string) $this->input('encounter_type')) ?? EncounterTypeEnum::ED;
    }

    public function attributes(): array
    {
        return [
            'hospitalization_tracker_id' => 'Patient',
            'care_coordination_type_id' => 'Coordination Type',
            'assigned_to_id' => 'Assigned To',
            'due_at' => 'Due Date',
            'completed_at' => 'Completion Date',
        ];
    }

    public function toDto(): CareCoordinationCreationDTO
    {
        // The submitted value is the tracker id, not the encounter id care_coordinations stores — the rule
        // above already confirmed this tracker exists and is In Progress, so it's fetched again here to
        // resolve the encounter it belongs to (see the encounter-vs-tracker link decision in the plan).
        $tracker = $this->repository->findTrackerById((int) $this->input('hospitalization_tracker_id'));

        return new CareCoordinationCreationDTO(
            encounterId: $tracker->encounter_id,
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
