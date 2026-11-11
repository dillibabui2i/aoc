<?php

namespace App\VBC\Repositories\HospitalizationTrackerContact;

use App\VBC\DTO\Hospitalization\HospitalizationTrackerContactDTO;
use App\VBC\Models\HospitalizationTrackerContact\HospitalizationTrackerContactModel;

class HospitalizationTrackerContactWriteRepository
{
    public function __construct(private HospitalizationTrackerContactModel $contactModel)
    {
    }

    public function create(
        int $trackerId,
        HospitalizationTrackerContactDTO $dto,
        int $receivedById,
    ): HospitalizationTrackerContactModel {
        /** @var HospitalizationTrackerContactModel $contact */
        $contact = $this->contactModel->newQuery()->create([
            'hospitalization_tracker_id' => $trackerId,
            'contact_type_id' => $dto->contactTypeId,
            'contact_name' => $dto->contactName,
            'phone_number' => $dto->phoneNumber,
            'email' => $dto->email,
            // Stamped now, same as `received_by_id` — never client input, never touched on edit.
            'contact_date' => now(),
            'received_by_id' => $receivedById,
            'contact_notes' => $dto->contactNotes,
        ]);

        return $contact;
    }

    // `received_by_id`/`contact_date` are deliberately absent here — immutable once set at creation.
    public function update(
        HospitalizationTrackerContactModel $contact,
        HospitalizationTrackerContactDTO $dto,
    ): HospitalizationTrackerContactModel {
        $contact->update([
            'contact_type_id' => $dto->contactTypeId,
            'contact_name' => $dto->contactName,
            'phone_number' => $dto->phoneNumber,
            'email' => $dto->email,
            'contact_notes' => $dto->contactNotes,
        ]);

        return $contact;
    }
}
