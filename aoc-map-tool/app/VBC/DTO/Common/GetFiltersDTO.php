<?php

namespace App\VBC\DTO\Common;

use App\VBC\Enum\Common\FilterBucketEnum;

final readonly class GetFiltersDTO
{
    public function __construct(
        public ?FilterBucketEnum $bucket = null,
    ) {
    }
}
