<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class ClaimDenialsRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_claimdenials_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    AGENCY_ID AS agency_id,
                    DENIAL_ID AS denial_id,
                    CLIENT_ID AS client_id,
                    DENIAL_CLAIM_ID AS claim_id,
                    CAST(DENIAL_DATE AS STRING) AS date,
                    CAST(DENIAL_NOTES AS STRING) AS notes,
                    CAST(DENIAL_REASON AS STRING) AS reason,
                    CAST(DENIAL_STATUS AS STRING) AS status,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_PAID_DATE) AS paid_date,
                    DENIAL_PAIDBY_USER_ID AS paidby_user_id,
                    DENIAL_CREATEDBY_USER_ID AS createdby_user_id,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_REJECTION_DATE) AS rejection_date,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_EOB_DATE) AS eob_date,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_LOCKEDMONTH_DATE) AS lockedmonth_date,
                    CAST(DENIAL_BADDEBT_NOTES AS STRING) AS baddebt_notes,
                    CAST(DENIAL_TRANSACTION_DATE AS STRING) AS transaction_date,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_SYSTEM_DATE) AS system_date,
                    CAST(DENIAL_IS_LOCKED AS INT64) AS is_locked,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_GL_LOCKED_DATE) AS gl_locked_date,
                    CAST(DENIAL_CLAIM_START_DATE AS STRING) AS claim_start_date,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(DENIAL_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(DENIAL_ID AS STRING)) > ?
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
                    AGENCY_ID AS agency_id,
                    DENIAL_ID AS denial_id,
                    CLIENT_ID AS client_id,
                    DENIAL_CLAIM_ID AS claim_id,
                    CAST(DENIAL_DATE AS STRING) AS date,
                    CAST(DENIAL_NOTES AS STRING) AS notes,
                    CAST(DENIAL_REASON AS STRING) AS reason,
                    CAST(DENIAL_STATUS AS STRING) AS status,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_PAID_DATE) AS paid_date,
                    DENIAL_PAIDBY_USER_ID AS paidby_user_id,
                    DENIAL_CREATEDBY_USER_ID AS createdby_user_id,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_REJECTION_DATE) AS rejection_date,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_EOB_DATE) AS eob_date,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_LOCKEDMONTH_DATE) AS lockedmonth_date,
                    CAST(DENIAL_BADDEBT_NOTES AS STRING) AS baddebt_notes,
                    CAST(DENIAL_TRANSACTION_DATE AS STRING) AS transaction_date,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_SYSTEM_DATE) AS system_date,
                    CAST(DENIAL_IS_LOCKED AS INT64) AS is_locked,
                    FORMAT_TIMESTAMP('%F %T', DENIAL_GL_LOCKED_DATE) AS gl_locked_date,
                    CAST(DENIAL_CLAIM_START_DATE AS STRING) AS claim_start_date,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(DENIAL_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE DENIAL_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
