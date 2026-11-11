<?php

namespace App\DTO\TrachVentClass;

final readonly class GridFilterDTO
{
    public function __construct(
        public int $draw,
        public ?int $start,
        public ?int $length,
        public ?string $searchValue,
        public ?int $orderColumn,
        public ?string $orderDirection,
    ) {
    }
}
