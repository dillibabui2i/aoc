<?php

namespace App\Kantime\Repository;

use App\Kantime\Domain\DataItem;

interface KantimeRepositoryInterface
{
    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): \Generator;

    public function getRecord($identifier) : DataItem | array | null;
}
