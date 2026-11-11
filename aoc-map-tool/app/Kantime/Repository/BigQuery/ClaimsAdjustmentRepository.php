<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class ClaimsAdjustmentRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_claimadjustments_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    CA_ADJUSTMENT_ID AS adjustment_id,
                    AGENCY_ID AS agency_id,
                    CA_CLAIM_ID AS claim_id,
                    CA_CLAIM_DETAIL_ID AS claim_detail_id,
                    CA_AMOUNT AS amount,
                    CAST(CA_NOTES AS STRING) AS notes,
                    CAST(CA_TYPE AS STRING) AS type,
                    FORMAT_TIMESTAMP('%F %T', CA_LOCKEDMONTH_DATE) AS lockedmonth_date,
                    FORMAT_TIMESTAMP('%F %T', CA_TRANSACTION_DATE) AS transaction_date,
                    FORMAT_TIMESTAMP('%F %T', CA_ADJUSTMENT_DATE) AS adjustment_date,
                    CAST(CA_ACCOUNT_TYPE AS STRING) AS account_type,
                    FORMAT_TIMESTAMP('%F %T', CA_CREATEDON) AS createdon,
                    CAST(CA_IS_LOCKED AS INT64) AS is_locked,
                    FORMAT_TIMESTAMP('%F %T', CA_GL_LOCKED_DATE) AS gl_locked_date,
                    CAST(CA_CLAIM_START_DATE AS STRING) AS claim_start_date,
                    CAST(CA_CREATED_USER_ID AS STRING) AS created_user_id,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CA_ADJUSTMENT_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CA_ADJUSTMENT_ID AS STRING)) > ?
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
                    CA_ADJUSTMENT_ID AS adjustment_id,
                    AGENCY_ID AS agency_id,
                    CA_CLAIM_ID AS claim_id,
                    CA_CLAIM_DETAIL_ID AS claim_detail_id,
                    CA_AMOUNT AS amount,
                    CAST(CA_NOTES AS STRING) AS notes,
                    CAST(CA_TYPE AS STRING) AS type,
                    FORMAT_TIMESTAMP('%F %T', CA_LOCKEDMONTH_DATE) AS lockedmonth_date,
                    FORMAT_TIMESTAMP('%F %T', CA_TRANSACTION_DATE) AS transaction_date,
                    FORMAT_TIMESTAMP('%F %T', CA_ADJUSTMENT_DATE) AS adjustment_date,
                    CAST(CA_ACCOUNT_TYPE AS STRING) AS account_type,
                    FORMAT_TIMESTAMP('%F %T', CA_CREATEDON) AS createdon,
                    CAST(CA_IS_LOCKED AS INT64) AS is_locked,
                    FORMAT_TIMESTAMP('%F %T', CA_GL_LOCKED_DATE) AS gl_locked_date,
                    CAST(CA_CLAIM_START_DATE AS STRING) AS claim_start_date,
                    CAST(CA_CREATED_USER_ID AS STRING) AS created_user_id,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CA_ADJUSTMENT_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CA_ADJUSTMENT_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
