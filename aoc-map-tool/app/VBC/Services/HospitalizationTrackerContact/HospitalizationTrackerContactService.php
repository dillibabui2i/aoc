<?php

namespace App\VBC\Services\HospitalizationTrackerContact;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerContactDTO;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerStatusEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;
use App\VBC\Models\HospitalizationTrackerContact\HospitalizationTrackerContactModel;
use App\VBC\Repositories\HospitalizationTrackerContact\HospitalizationTrackerContactWriteRepository;

/**
 * Contacts are only creatable/editable while the tracker they belong to is "In Progress" (AC6/AC9/AC15);
 * every other status shows them read-only. Unlike CareCoordination (create-only guard), edits must be
 * blocked too, so both entry points go through the same guardEditable() check — mirroring
 * SickPlanService's guard for the same reason.
 */
class HospitalizationTrackerContactService
{
    public function __construct(private readonly HospitalizationTrackerContactWriteRepository $writeRepository)
    {
    }

    public function create(
        HospitalizationTrackerModel $tracker,
        HospitalizationTrackerContactDTO $dto,
    ): HospitalizationTrackerContactModel {
        $this->guardEditable($tracker);

        // "Contact received by" is always the current user, never client input — see the request classes.
        return $this->writeRepository->create($tracker->id, $dto, (int) auth()->id());
    }

    public function update(
        HospitalizationTrackerModel $tracker,
        HospitalizationTrackerContactModel $contact,
        HospitalizationTrackerContactDTO $dto,
    ): HospitalizationTrackerContactModel {
        $this->guardEditable($tracker);

        return $this->writeRepository->update($contact, $dto);
    }

    private function guardEditable(HospitalizationTrackerModel $tracker): void
    {
        if ($tracker->status !== HospitalizationTrackerStatusEnum::IN_PROGRESS) {
            throw new HospitalizationTrackerContactNotEditableException(
                "Contacts are only editable while the tracker is In Progress (currently {$tracker->status->label()})."
            );
        }
    }
}
