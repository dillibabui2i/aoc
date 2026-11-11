<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class ClaimsPaymentRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_claimpayments_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    CP_CLAIMPAYMENT_ID AS claimpayment_id,
                    CP_CLAIM_ID AS claim_id,
                    AGENCY_ID AS agency_id,
                    CP_PAYMENT_ID AS payment_id,
                    CP_PAID_AMT AS paid_amt,
                    CAST(CP_NOTES AS STRING) AS notes,
                    CAST(CP_TRANSACTION_DATE AS STRING) AS transaction_date,
                    CP_CREATOR_ID AS creator_id,
                    FORMAT_TIMESTAMP('%F %T', CP_POSTING_DATE) AS posting_date,
                    CAST(CP_STATUS AS STRING) AS status,
                    CAST(CP_IS_LOCKED AS INT64) AS is_locked,
                    CAST(CP_GL_LOCKED_MONTH AS STRING) AS gl_locked_month,
                    FORMAT_TIMESTAMP('%F %T', CP_GL_LOCKED_DATE) AS gl_locked_date,
                    CAST(CP_CLAIM_START_DATE AS STRING) AS claim_start_date,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CP_CLAIMPAYMENT_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CP_CLAIMPAYMENT_ID AS STRING)) > ?
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
                    CP_CLAIMPAYMENT_ID AS claimpayment_id,
                    CP_CLAIM_ID AS claim_id,
                    AGENCY_ID AS agency_id,
                    CP_PAYMENT_ID AS payment_id,
                    CP_PAID_AMT AS paid_amt,
                    CAST(CP_NOTES AS STRING) AS notes,
                    CAST(CP_TRANSACTION_DATE AS STRING) AS transaction_date,
                    CP_CREATOR_ID AS creator_id,
                    FORMAT_TIMESTAMP('%F %T', CP_POSTING_DATE) AS posting_date,
                    CAST(CP_STATUS AS STRING) AS status,
                    CAST(CP_IS_LOCKED AS INT64) AS is_locked,
                    CAST(CP_GL_LOCKED_MONTH AS STRING) AS gl_locked_month,
                    FORMAT_TIMESTAMP('%F %T', CP_GL_LOCKED_DATE) AS gl_locked_date,
                    CAST(CP_CLAIM_START_DATE AS STRING) AS claim_start_date,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CP_CLAIMPAYMENT_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CP_CLAIMPAYMENT_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
