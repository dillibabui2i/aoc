<?php

namespace App\VBC\DTO\SickPlan;

/** A Sick Plan item create/update submission from the "Sick Plan Details" modal. */
final readonly class SickPlanItemDTO
{
    public function __construct(
        public string $sickPlanType,
        public bool $completed,
        public ?string $notes,
    ) {
    }
}
