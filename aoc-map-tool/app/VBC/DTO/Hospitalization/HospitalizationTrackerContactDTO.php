<?php

namespace App\VBC\DTO\Hospitalization;

/** One submit of the "Contact Details" modal — shared shape for create and edit. */
final readonly class HospitalizationTrackerContactDTO
{
    public function __construct(
        public int $contactTypeId,
        public string $contactName,
        public ?string $phoneNumber,
        public ?string $email,
        public ?string $contactNotes,
    ) {
    }
}
