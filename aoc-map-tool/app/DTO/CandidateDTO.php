<?php

namespace App\DTO;

class CandidateDTO
{

    public function __construct(
      public readonly ?string $email,
      public readonly ?string $externalEmail,
      public readonly ?string $phone,
      public readonly ?string $mobile,
    ) {}

}
