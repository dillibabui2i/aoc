<?php

namespace App\DTO;

class NthInquiryFilterDTO
{

    public function __construct(
      public readonly string $clinicianId,
      public readonly ?int $excludeLeadId = null,
      public readonly string $orderColumn = 'created_at',
      public readonly string $orderDirection = 'desc'
    ) {}

    public function hasExcludeLeadId(): bool
    {
        return !empty($this->excludeLeadId);
    }

}
