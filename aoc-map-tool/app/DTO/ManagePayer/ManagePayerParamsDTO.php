<?php

namespace App\DTO\ManagePayer;

readonly class ManagePayerParamsDTO
{
    public function __construct(
        public readonly int     $id,
        public readonly string  $state,
        public readonly array   $rows,
    )
    {
    }
}
