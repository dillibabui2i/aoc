<?php

namespace App\Kantime\Domain;

readonly class RenewableItem implements DataItem
{
    public function __construct(
        public ?string $lastModified,
        public ?string $uniqueKey,
        public ?int $hha,
        public bool $isValidRecord,
        public ?string $renewClinicianId,
        public ?string $providerId,
        public ?string $expirationDate,
        public int $renewableItemId,
        public ?string $issuedDate,
        public ?bool $mandatory,
        public ?int $renewablesType,
        public ?string $renewItem,
        public ?string $licenseNo,
        public ?string $licenseState,
    ) {
    }
}
