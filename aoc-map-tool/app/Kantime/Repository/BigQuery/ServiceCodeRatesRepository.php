<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Domain\DataItem;
use App\Kantime\Repository\KantimeRepositoryInterface;

final class ServiceCodeRatesRepository implements KantimeRepositoryInterface
{

    const TABLE_NAME = 'daas_dw_serviceratehistory_vw';
    private $tableRef;

    public function __construct(private readonly BigQueryConnection $connection)
    {
        $this->tableRef = $this->connection->tableRef(self::TABLE_NAME);
    }

    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): \Generator
    {
        $query = <<<SQL
            SELECT
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(SERVICERATE_ID AS STRING)) AS combinedstring,
                SERVICERATE_ID AS servicerateid,
                SERVICERATE_SERVICECODE_ID AS servicecodeid,
                CAST(SERVICERATE_START_DATE AS STRING) AS startdate,
                CAST(SERVICERATE_END_DATE AS STRING) AS enddate,
                AGENCY_ID AS agency_id,
                CAST(SERVICERATE_CONTRACTUAL_BILLRATE AS FLOAT64) AS contractualrate,
                CAST(SERVICERATE_CONTRACTUAL_BILLMODE AS STRING) AS contractualbillmode,
                CAST(SERVICERATE_UNITTYPE AS STRING) AS unittype,
                CAST(SERVICERATE_UNIT AS FLOAT64) AS unit,
                CAST(SERVICERATE_MINUTEINUNIT AS INT64) AS minuteinunit,
                ISVALIDRECORD AS isvalidrecord
            FROM `$this->tableRef`
            WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(SERVICERATE_ID AS STRING)) > ?
            ORDER BY CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(SERVICERATE_ID AS STRING)) ASC
            LIMIT ?
            SQL;

        $params = [$offset, $limit];

        foreach ($this->connection->cursor($query, $params) as $row) {
            yield $row;
        }
    }

    public function getRecord($identifier) : DataItem | array | null
    {
        $query = "SELECT
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(SERVICERATE_ID AS STRING)) AS combinedstring,
                    SERVICERATE_ID AS servicerateid,
                    SERVICERATE_SERVICECODE_ID AS servicecodeid,
                    CAST(SERVICERATE_START_DATE AS STRING) AS startdate,
                    CAST(SERVICERATE_END_DATE AS STRING) AS enddate,
                    AGENCY_ID AS agency_id,
                    CAST(SERVICERATE_CONTRACTUAL_BILLRATE AS FLOAT64) AS contractualrate,
                    CAST(SERVICERATE_CONTRACTUAL_BILLMODE AS STRING) AS contractualbillmode,
                    CAST(SERVICERATE_UNITTYPE AS STRING) AS unittype,
                    CAST(SERVICERATE_UNIT AS FLOAT64) AS unit,
                    CAST(SERVICERATE_MINUTEINUNIT AS INT64) AS minuteinunit,
                    ISVALIDRECORD AS isvalidrecord
                FROM `$this->tableRef`
                WHERE SERVICERATE_ID = ?
                LIMIT 1";

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return null;
        }

        return (array) $rows[0];
    }
}
