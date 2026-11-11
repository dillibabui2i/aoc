<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Exception\MethodNotImplementedException;
use App\Kantime\Repository\KantimeRepositoryInterface;

final class ServiceCodesRepository implements KantimeRepositoryInterface
{
    const TABLE_NAME = 'daas_dw_servicecodesmaster_vw';
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
                CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(SERVICE_ID AS STRING)) AS combinedstring,
                SERVICE_ID AS service_id,
                SERVICE_PARENT_SERVICEID AS parent_serviceid,
                SERVICE_PAYERID AS payerid,
                AGENCY_ID AS agency_id,
                CAST(SERVICE_TYPE AS STRING) AS type,
                CAST(SERVICE_NAME AS STRING) AS name,
                CAST(SEVICE_CODE AS STRING) AS code,
                CAST(SERVICE_PAYCODE AS STRING) AS paycode,
                SERVICE_GROUP_ID AS group_id,
                SERVICE_FORM_ID AS form_id,
                SERVICE_CREATED_BY AS sf_created_by,
                SERVICE_ACTIVE AS active,
                CAST(SERVICE_CONT_BILLRATE AS FLOAT64) AS contractualrate,
                CAST(SERVICE_CONT_BILLMODE AS STRING) AS contractualbillmode,
                CAST(SERVICE_UNITTYPE AS STRING) AS unittype,
                SERVICE_UNIT AS unit,
                SERVICE_MINUTEINUNIT AS minuteinunit,
                SERVICE_CATEGORY AS category,
                SERVICE_IS_BILLABLE AS is_billable_daas,
                SERVICE_IS_TERMINATED AS is_terminated,
                CAST(ISVALIDRECORD AS INT64) AS isvalidrecord
            FROM `$this->tableRef`
            WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(SERVICE_ID AS STRING)) > ?
            ORDER BY CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(SERVICE_ID AS STRING))
            LIMIT ?
            SQL;
        $params = [$offset, $limit];

        foreach ($this->connection->cursor($query, $params) as $row) {
            yield $row;
        }
    }

    public function getRecord($identifier) : array
    {
        throw new MethodNotImplementedException('Method not implemented');
    }
}
