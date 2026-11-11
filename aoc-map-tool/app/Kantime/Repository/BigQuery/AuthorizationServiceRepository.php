<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class AuthorizationServiceRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_authorizationservice_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    CONCAT(CAST(AUTH_PAYMENT_SERVICE_ID AS STRING), CAST(AUTH_AUTHORIZATION_ID AS STRING)) AS auth_service_unique_id,
                    AUTH_PAYMENT_SERVICE_ID AS payment_service_id,
                    AUTH_AUTHORIZATION_ID AS authorization_id,
                    AUTH_SERVICE_ID AS service_id,
                    AGENCY_ID AS agency_id,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(
                        FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                        CAST(AUTH_PAYMENT_SERVICE_ID AS STRING),
                        CAST(AUTH_AUTHORIZATION_ID AS STRING)
                    ) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                    CAST(AUTH_PAYMENT_SERVICE_ID AS STRING),
                    CAST(AUTH_AUTHORIZATION_ID AS STRING)
                ) > ?
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
                    CONCAT(CAST(AUTH_PAYMENT_SERVICE_ID AS STRING), CAST(AUTH_AUTHORIZATION_ID AS STRING)) AS auth_service_unique_id,
                    AUTH_PAYMENT_SERVICE_ID AS payment_service_id,
                    AUTH_AUTHORIZATION_ID AS authorization_id,
                    AUTH_SERVICE_ID AS service_id,
                    AGENCY_ID AS agency_id,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(
                        FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                        CAST(AUTH_PAYMENT_SERVICE_ID AS STRING),
                        CAST(AUTH_AUTHORIZATION_ID AS STRING)
                    ) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(CAST(AUTH_PAYMENT_SERVICE_ID AS STRING), CAST(AUTH_AUTHORIZATION_ID AS STRING)) = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
