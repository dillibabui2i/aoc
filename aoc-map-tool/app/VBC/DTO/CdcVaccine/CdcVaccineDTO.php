<?php

namespace App\VBC\DTO\CdcVaccine;

final readonly class CdcVaccineDTO
{
    public function __construct(
        public int $cvxCode,
        public ?string $fullName,
        public ?string $shortDescription,
        public ?string $status,
        public ?string $lastUpdated,
        public ?int $cvxGroupCode = null,
    ) {
    }

    public function withGroupCode(?int $cvxGroupCode): self
    {
        return new self(
            $this->cvxCode,
            $this->fullName,
            $this->shortDescription,
            $this->status,
            $this->lastUpdated,
            $cvxGroupCode,
        );
    }
}
