<?php

namespace App\VBC\Repositories\CareCoordination;

use App\VBC\DTO\CareCoordination\CareCoordinationCreationDTO;
use App\VBC\DTO\CareCoordination\CareCoordinationUpdateDTO;
use App\VBC\Models\CareCoordination\CareCoordinationModel;

class CareCoordinationWriteRepository
{
    public function __construct(private CareCoordinationModel $careCoordinationModel)
    {
    }

    public function create(CareCoordinationCreationDTO $dto): CareCoordinationModel
    {
        /** @var CareCoordinationModel $careCoordination */
        $careCoordination = $this->careCoordinationModel->newQuery()->create([
            'encounter_id' => $dto->encounterId,
            'care_coordination_type_id' => $dto->careCoordinationTypeId,
            'assigned_to_id' => $dto->assignedToId,
            'due_at' => $dto->dueAt,
            'completed_at' => $dto->completedAt,
            'notes' => $dto->notes,
        ]);

        return $careCoordination;
    }

    /**
     * The modal always submits the whole form (not a single field), so unlike
     * HospitalizationWriteRepository::updateStatus() an explicit null here is a real "clear this field"
     * (e.g. unassigning, or blanking the Completion Date) — every value is written as-is, none filtered out.
     */
    public function update(CareCoordinationModel $careCoordination, CareCoordinationUpdateDTO $dto): CareCoordinationModel
    {
        $careCoordination->update([
            'care_coordination_type_id' => $dto->careCoordinationTypeId,
            'assigned_to_id' => $dto->assignedToId,
            'due_at' => $dto->dueAt,
            'completed_at' => $dto->completedAt,
            'notes' => $dto->notes,
        ]);

        return $careCoordination;
    }
}
