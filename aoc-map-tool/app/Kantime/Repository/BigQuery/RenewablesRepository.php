<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\DataMappers\RenewablesDataMapper;
use App\Kantime\Domain\DataItem;
use App\Kantime\Domain\RenewableItem;
use App\Kantime\Repository\KantimeRepositoryInterface;

final class RenewablesRepository implements KantimeRepositoryInterface
{
    private const TABLE_NAME = 'daas_dw_renewableitems_vw';

    private const DEFAULT_LIMIT = 1000;

    private string $tableRef;

    public function __construct(
        private readonly BigQueryConnection $connection,
        private readonly RenewablesDataMapper $mapper,
    ) {
        $this->tableRef = $this->connection->tableRef(self::TABLE_NAME);
    }

    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): \Generator
    {
        $etlDate = $lastUpdated ?? date('Y-m-d', strtotime('yesterday'));

        $sql = <<<SQL
                SELECT
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS LastModified,
                CONCAT(CAST(RENEW_CLINICIAN_ID AS STRING), CAST(RENEW_ITEM AS STRING)) AS UNIQUE_KEY,
                AGENCY_ID AS HHA,
                CAST(ISVALIDRECORD AS STRING) AS ISVALIDRECORD,
                CAST(RENEW_CLINICIAN_ID AS STRING) AS RENEW_CLINICIAN_ID,
                RENEW_EMPLOYEE_ID AS ProviderID,
                CAST(RENEW_EXPIRY_DATE AS STRING) AS expiration_date,
                RENEW_INTERNAL_ID AS renewable_item_id,
                CAST(RENEW_ISSUE_DATE AS STRING) AS issued_date,
                RENEW_IS_MANDATORY AS mandatory,
                RENEW_ITEM_ID AS renewables_type,
                CAST(RENEW_ITEM AS STRING) AS renew_item,
                CAST(RENEW_LICENSE_NO AS STRING) AS license_no,
                CAST(RENEW_STATE AS STRING) AS license_state
            FROM `$this->tableRef`
            where ETL_UPDATED_TIMESTAMP > @offset
            ORDER BY RENEW_INTERNAL_ID
            LIMIT @limit
        SQL;

        $params = [
            'offset' => $etlDate,
            'limit' => $limit,
        ];

        foreach ($this->connection->cursor($sql, $params) as $row) {
            yield $this->mapper->fromDaasRow($row);
        }
    }

    public function getRecord($identifier) : RenewableItem | DataItem | array
    {
        $query = <<<SQL
                SELECT
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS LastModified,
                CONCAT(CAST(RENEW_CLINICIAN_ID AS STRING), CAST(RENEW_ITEM AS STRING)) AS UNIQUE_KEY,
                AGENCY_ID AS HHA,
                CAST(ISVALIDRECORD AS STRING) AS ISVALIDRECORD,
                CAST(RENEW_CLINICIAN_ID AS STRING) AS RENEW_CLINICIAN_ID,
                RENEW_EMPLOYEE_ID AS ProviderID,
                CAST(RENEW_EXPIRY_DATE AS STRING) AS expiration_date,
                RENEW_INTERNAL_ID AS renewable_item_id,
                CAST(RENEW_ISSUE_DATE AS STRING) AS issued_date,
                RENEW_IS_MANDATORY AS mandatory,
                RENEW_ITEM_ID AS renewables_type,
                CAST(RENEW_ITEM AS STRING) AS renew_item,
                CAST(RENEW_LICENSE_NO AS STRING) AS license_no,
                CAST(RENEW_STATE AS STRING) AS license_state
            FROM `$this->tableRef`
            WHERE RENEW_EMPLOYEE_ID = @identifier
            ORDER BY `LastModified` DESC
        SQL;

        $params = [
            'identifier' => $identifier,
        ];

        foreach ($this->connection->cursor($query, $params) as $row) {
            return $this->mapper->fromDaasRow($row);
        }

        return [];
    }

    /** @return RenewableItem[] */
    public function getRecordsByProviders(array $identifiers) : array
    {
        $query = <<<SQL
                SELECT
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS LastModified,
                CONCAT(CAST(RENEW_CLINICIAN_ID AS STRING), CAST(RENEW_ITEM AS STRING)) AS UNIQUE_KEY,
                AGENCY_ID AS HHA,
                CAST(ISVALIDRECORD AS STRING) AS ISVALIDRECORD,
                CAST(RENEW_CLINICIAN_ID AS STRING) AS RENEW_CLINICIAN_ID,
                RENEW_EMPLOYEE_ID AS ProviderID,
                CAST(RENEW_EXPIRY_DATE AS STRING) AS expiration_date,
                RENEW_INTERNAL_ID AS renewable_item_id,
                CAST(RENEW_ISSUE_DATE AS STRING) AS issued_date,
                RENEW_IS_MANDATORY AS mandatory,
                RENEW_ITEM_ID AS renewables_type,
                CAST(RENEW_ITEM AS STRING) AS renew_item,
                CAST(RENEW_LICENSE_NO AS STRING) AS license_no,
                CAST(RENEW_STATE AS STRING) AS license_state
            FROM `$this->tableRef`
            WHERE RENEW_EMPLOYEE_ID IN UNNEST(@identifiers)
            ORDER BY `LastModified` DESC
        SQL;

        $params = [
            'identifiers' => array_values($identifiers),
        ];

        $data = [];
        foreach ($this->connection->cursor($query, $params) as $row) {
            $data[] = $this->mapper->fromDaasRow($row);
        }

        return $data;
    }

    /** @return RenewableItem[] */
    public function getRecordsById($identifier) : array
    {
        $query = <<<SQL
                SELECT
                FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS LastModified,
                CONCAT(CAST(RENEW_CLINICIAN_ID AS STRING), CAST(RENEW_ITEM AS STRING)) AS UNIQUE_KEY,
                AGENCY_ID AS HHA,
                CAST(ISVALIDRECORD AS STRING) AS ISVALIDRECORD,
                CAST(RENEW_CLINICIAN_ID AS STRING) AS RENEW_CLINICIAN_ID,
                RENEW_EMPLOYEE_ID AS ProviderID,
                CAST(RENEW_EXPIRY_DATE AS STRING) AS expiration_date,
                RENEW_INTERNAL_ID AS renewable_item_id,
                CAST(RENEW_ISSUE_DATE AS STRING) AS issued_date,
                RENEW_IS_MANDATORY AS mandatory,
                RENEW_ITEM_ID AS renewables_type,
                CAST(RENEW_ITEM AS STRING) AS renew_item,
                CAST(RENEW_LICENSE_NO AS STRING) AS license_no,
                CAST(RENEW_STATE AS STRING) AS license_state
            FROM `$this->tableRef`
            WHERE RENEW_EMPLOYEE_ID = @identifier
            ORDER BY `LastModified` DESC
        SQL;

        $params = [
            'identifier' => $identifier,
        ];

        $data = [];
        foreach ($this->connection->cursor($query, $params) as $row) {
            $data[] = $this->mapper->fromDaasRow($row);
        }

        return $data;
    }
}
