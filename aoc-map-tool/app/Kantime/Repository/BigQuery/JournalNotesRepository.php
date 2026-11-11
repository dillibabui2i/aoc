<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class JournalNotesRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_journalnotes_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    JN_NOTE_ID AS note_id,
                    CLIENT_ID AS PatientID,
                    CAST(JN_CONTEXT_TYPE AS STRING) AS context_type,
                    JN_CONTEXT_ID AS context_id,
                    FORMAT_TIMESTAMP('%F %T', JN_ADDED_DATE) AS added_date,
                    JN_ADDEDBY_USER_ID AS addedby_user_id,
                    FORMAT_TIMESTAMP('%F %T', JN_MODIFIED_DATE) AS modified_date,
                    JN_MODIFIEDBY_USER_ID AS modifiedby_user_id,
                    CAST(JN_CATEGORY AS STRING) AS category,
                    CAST(JN_NOTES AS STRING) AS notes,
                    JN_LOB_ID AS lob_id,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(JN_NOTE_ID AS STRING)) AS combinedstring,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(JN_NOTE_ID AS STRING)) > ?
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
                    JN_NOTE_ID AS note_id,
                    CLIENT_ID AS PatientID,
                    CAST(JN_CONTEXT_TYPE AS STRING) AS context_type,
                    JN_CONTEXT_ID AS context_id,
                    FORMAT_TIMESTAMP('%F %T', JN_ADDED_DATE) AS added_date,
                    JN_ADDEDBY_USER_ID AS addedby_user_id,
                    FORMAT_TIMESTAMP('%F %T', JN_MODIFIED_DATE) AS modified_date,
                    JN_MODIFIEDBY_USER_ID AS modifiedby_user_id,
                    CAST(JN_CATEGORY AS STRING) AS category,
                    CAST(JN_NOTES AS STRING) AS notes,
                    JN_LOB_ID AS lob_id,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(JN_NOTE_ID AS STRING)) AS combinedstring,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord
                FROM `$this->tableRef`
                WHERE JN_NOTE_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
