<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Domain\DataItem;
use App\Kantime\Repository\KantimeRepositoryInterface;

abstract class AbstractCommonRepository implements KantimeRepositoryInterface
{
    protected string $tableRef;

    public function __construct(protected readonly BigQueryConnection $connection)
    {
        $this->tableRef = $this->connection->tableRef($this->getTableName());
    }

    abstract public function getQuery() : string;

    abstract public function getTableName() : string;

    abstract public function getRecord($identifier) : array | DataItem;

    /**
     * Common method to query Kantime dictionaries.
     * To make it work getQuery method should be implemented and return valid query.
     * @param int $limit
     * @param int|string $offset
     * @param string|null $lastUpdated
     * @param string|null $order
     * @param string|null $groupBy
     * @return \Generator
     */
    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): \Generator
    {
        $params = [$offset, $limit];

        foreach ($this->connection->cursor($this->getQuery(), $params) as $row) {
            yield $row;
        }
    }
}
