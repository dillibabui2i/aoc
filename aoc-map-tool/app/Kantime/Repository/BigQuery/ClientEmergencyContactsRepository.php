<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Domain\DataItem;
use App\Kantime\Exception\MethodNotImplementedException;
use App\Kantime\Repository\KantimeRepositoryInterface;

class ClientEmergencyContactsRepository implements KantimeRepositoryInterface
{
    const TABLE_NAME = 'daas_dw_clientemergencycontacts_vw';

    private string $tableRef;

    public function __construct(private readonly BigQueryConnection $connection)
    {
        $this->tableRef = $this->connection->tableRef(self::TABLE_NAME);
    }

    public function getRecords(int $limit, int|string $offset, ?string $lastUpdated = null, ?string $order = null, ?string $groupBy = null): \Generator
    {
        $query = $this->buildQuery($limit);

        $params = [
            'limit' => $limit,
            'combinedString' => $offset,
        ];

        foreach ($this->connection->cursor($query, $params) as $row) {
            yield $row;
        }
    }

    public function getRecord($identifier) : array | DataItem
    {
        throw new MethodNotImplementedException('Method getRecord() not implemented yet.');
    }

    private function buildQuery(int $limit): string
    {
        return <<<SQL
            WITH rawChangedRows AS (
                SELECT
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS changeLastModified,
                    CONCAT(
                        FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                        '|',
                        CAST(COALESCE(EC_CLIENT_ID, 0) AS STRING),
                        '|',
                        CAST(COALESCE(EC_INTAKE_ID, 0) AS STRING)
                    ) AS changeCombinedString,
                    CAST(COALESCE(EC_CLIENT_ID, 0) AS STRING) AS syncClientIdString,
                    CAST(COALESCE(EC_INTAKE_ID, 0) AS STRING) AS syncIntakeIdString,
                    EC_CLIENT_ID AS syncClientId,
                    EC_INTAKE_ID AS syncIntakeId
                FROM `{$this->connection->tableRef(self::TABLE_NAME)}`
                WHERE CONCAT(
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                    '|',
                    CAST(COALESCE(EC_CLIENT_ID, 0) AS STRING),
                    '|',
                    CAST(COALESCE(EC_INTAKE_ID, 0) AS STRING)
                ) > @combinedString
                ORDER BY changeCombinedString
                LIMIT @limit
            ),
            changedRows AS (
                SELECT
                    MAX(changeLastModified) AS changeLastModified,
                    MAX(changeCombinedString) AS changeCombinedString,
                    CONCAT(syncClientIdString, '|', syncIntakeIdString) AS changeClientKey,
                    syncClientId,
                    syncIntakeId
                FROM rawChangedRows
                GROUP BY syncClientIdString, syncIntakeIdString, syncClientId, syncIntakeId
            ),
            expandedRows AS (
                SELECT DISTINCT
                    changedRows.changeLastModified,
                    changedRows.changeCombinedString,
                    changedRows.changeClientKey,
                    changedRows.syncClientId,
                    changedRows.syncIntakeId,
                    FORMAT_TIMESTAMP('%F %T', src.ETL_UPDATED_TIMESTAMP) AS lastModified,
                    CONCAT(
                        FORMAT_TIMESTAMP('%F %T', src.ETL_UPDATED_TIMESTAMP),
                        '|',
                        CAST(COALESCE(src.EC_CLIENT_ID, 0) AS STRING),
                        '|',
                        CAST(COALESCE(src.EC_INTAKE_ID, 0) AS STRING)
                    ) AS combinedString,
                    src.AGENCY_ID AS agencyId,
                    CONCAT(
                        CAST(COALESCE(src.EC_CLIENT_ID, 0) AS STRING),
                        '|',
                        CAST(COALESCE(src.EC_INTAKE_ID, 0) AS STRING)
                    ) AS clientKey,
                    src.EC_CLIENT_ID AS clientId,
                    src.EC_INTAKE_ID AS intakeId,
                    CAST(src.EC_FIRST_NAME AS STRING) AS firstName,
                    CAST(src.EC_LAST_NAME AS STRING) AS lastName,
                    CAST(src.EC_RELATION AS STRING) AS relation,
                    CAST(src.EC_PRIORITY AS STRING) AS priority,
                    CAST(src.EC_TELEPHONE AS STRING) AS telephone,
                    CAST(src.EC_EMAIL AS STRING) AS email,
                    src.EC_IS_CLIENT_REPRSENTATIVE AS isClientRepresentative,
                    CAST(src.EC_CONTACT_ID AS STRING) AS contactId,
                    CAST(src.EC_ALTERNATE_PHONE AS STRING) AS alternatePhone,
                    CAST(src.EC_WORK_PHONE AS STRING) AS workPhone,
                    src.ISVALIDRECORD AS isValidRecord
                FROM `$this->tableRef` AS src
                INNER JOIN changedRows
                    ON COALESCE(src.EC_CLIENT_ID, 0) = COALESCE(changedRows.syncClientId, 0)
                   AND COALESCE(src.EC_INTAKE_ID, 0) = COALESCE(changedRows.syncIntakeId, 0)
                WHERE src.ISVALIDRECORD = true
            )
            SELECT *
            FROM expandedRows
            ORDER BY changeCombinedString, combinedString, clientKey, firstName, lastName
            SQL;
    }
}
