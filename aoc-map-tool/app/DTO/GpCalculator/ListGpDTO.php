<?php

namespace App\DTO\GpCalculator;

class ListGpDTO
{
    public function __construct(public readonly ?string $search,
        public readonly ?int $limit = null,
        public readonly ?int $start = null,
        public readonly ?string $order = null,
        public readonly ?string $dir = 'ASC',

    ) {
    }

}
