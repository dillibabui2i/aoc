<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class BillingInquiryNotesRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_billinquirynotes_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    BN_NOTE_ID AS note_id,
                    AGENCY_ID AS agency_id,
                    CLIENT_ID AS PatientID,
                    BN_PAYER_ID AS payer_id,
                    CAST(BN_START_DATE AS STRING) AS start_date,
                    CAST(BN_END_DATE AS STRING) AS end_date,
                    FORMAT_TIMESTAMP('%F %T', BN_CREATED_DATE) AS created_date,
                    CAST(BN_NOTES AS STRING) AS notes,
                    BN_ADDEDBY_USER_ID AS addedby_user_id,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(BN_NOTE_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(BN_NOTE_ID AS STRING)) > ?
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
                    BN_NOTE_ID AS note_id,
                    AGENCY_ID AS agency_id,
                    CLIENT_ID AS PatientID,
                    BN_PAYER_ID AS payer_id,
                    CAST(BN_START_DATE AS STRING) AS start_date,
                    CAST(BN_END_DATE AS STRING) AS end_date,
                    FORMAT_TIMESTAMP('%F %T', BN_CREATED_DATE) AS created_date,
                    CAST(BN_NOTES AS STRING) AS notes,
                    BN_ADDEDBY_USER_ID AS addedby_user_id,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(BN_NOTE_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE BN_NOTE_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
