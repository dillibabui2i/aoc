<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;
use App\Kantime\Repository\KantimeRepositoryInterface;

final class HospitalizationReportRepository implements KantimeRepositoryInterface
{

    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): \Generator
    {
        // TODO: Implement getRecords() method.
    }

    public function getRecord($identifier) : array | DataItem
    {
        // TODO: Implement getRecord() method.
    }
}
