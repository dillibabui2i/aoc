<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class AuthorizationsMasterRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_authorizationmaster_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    AUTH_ID AS auth_id,
                    AUTH_PAYER_ID AS payer_id,
                    CLIENT_ID AS patient_id,
                    AGENCY_BRANCH_ID AS agency_branch_id,
                    CAST(AUTH_NUMBER AS STRING) AS number,
                    FORMAT_TIMESTAMP('%F %T', AUTH_START_DATE) AS start_date,
                    FORMAT_TIMESTAMP('%F %T', AUTH_END_DATE) AS end_date,
                    CAST(AUTH_IS_VISITBASED AS INT64) AS is_visitbased,
                    ROUND(AUTH_TOTALUNITS, 2) AS totalunits,
                    AUTH_VISIT_COUNT AS visit_count,
                    AUTH_SERVICE_CODE_ID AS service_code_id,
                    CAST(AUTH_DISCIPLINE_NAME AS STRING) AS discipline_name,
                    ROUND(AUTH_UNUSEDUNITS, 2) AS unusedunits,
                    ROUND(AUTH_WEEKLY_LIMIT, 2) AS weekly_limit,
                    ROUND(AUTH_MONTHLY_LIMIT, 2) AS monthly_limit,
                    ROUND(AUTH_DAILY_LIMIT, 2) AS daily_limit,
                    CAST(AUTH_IS_AUTO AS INT64) AS is_auto,
                    AGENCY_ID AS agency_id,
                    FORMAT_TIMESTAMP('%F %T', AUTH_CREATED_DATE) AS sf_created_at,
                    FORMAT_TIMESTAMP('%F', AUTH_CREATED_DATE) AS auth_created_date,
                    CAST(AUTH_PAYER_ALLOCATIONORDER AS STRING) AS payer_allocationorder,
                    CAST(AUTH_IS_HOURLYBASED AS INT64) AS is_hourlybased,
                    AUTH_ACKBY_CGID AS auth_ack_by,
                    FORMAT_TIMESTAMP('%F', AUTH_ACK_ON) AS auth_ack_date,
                    AUTH_ENTEREDBY_CGID AS auth_entered_by,
                    CAST(AUTH_PRIORITY_TYPE AS STRING) AS priority_type,
                    FORMAT_TIMESTAMP('%F', AUTH_RECEIVED_DATE) AS auth_received_date,
                    AUTH_REQUESTEDBY_CGID AS auth_requested_by,
                    FORMAT_TIMESTAMP('%F', AUTH_REQUESTED_DATE) AS auth_requested_date,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS sf_updated_at,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(AUTH_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(AUTH_ID AS STRING)) > ?
                ORDER BY combinedstring
                LIMIT ?
                SQL;
    }

    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function getRecord($identifier): array|DataItem
    {
        $query = <<<SQL
                SELECT
                    AUTH_ID AS auth_id,
                    AUTH_PAYER_ID AS payer_id,
                    CLIENT_ID AS patient_id,
                    AGENCY_ID AS agency_id,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS sf_updated_at,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified
                FROM `$this->tableRef`
                WHERE AUTH_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
