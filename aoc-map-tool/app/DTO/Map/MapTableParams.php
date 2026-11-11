<?php

namespace App\DTO\Map;

use Illuminate\Http\Request;

readonly class MapTableParams
{
    public function __construct(
        public readonly ?string $draw,
        public readonly ?string $period,
        public readonly ?string $source,
        public readonly ?string $view,
        public readonly array $search = [],
        public readonly array $date = [],
        public readonly array $order = [],
        public readonly int $start = 0,
        public readonly int $length = 10,
        public readonly ?string $_token = null,
        public readonly bool $isCheckBox = false,
        public readonly array $columns = [],
    ) {}
}
