<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Domain\DataItem;
use App\Kantime\Exception\MethodNotImplementedException;
use App\Kantime\Repository\KantimeRepositoryInterface;

final class RenewablesTypesRepository implements KantimeRepositoryInterface
{

    const TABLE_NAME = 'daas_dw_renewableitems_vw';

    public function __construct(private readonly BigQueryConnection $connection)
    {
    }


    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): \Generator
    {
        $query = '
                SELECT DISTINCT
                    RENEW_ITEM_ID,
                    RENEW_ITEM
                FROM `' . $this->connection->tableRef(self::TABLE_NAME) . '`
                ORDER BY RENEW_ITEM_ID
            ';
        foreach ($this->connection->cursor($query, []) as $row) {
            yield $row;
        }

    }

    public function getRecord($identifier) : array | DataItem
    {
        throw new MethodNotImplementedException('Method not implemented. Feel free to implement it if needed.');
    }
}
