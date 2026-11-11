<?php

namespace App\DTO;

class NthInquiryBulkResolveDTO
{

    public function __construct(
      public readonly array $ids,
      public readonly int $advancingLeadId,
      public readonly int $recruiterId,
      public readonly string $recruiterName
    ) {}

}
