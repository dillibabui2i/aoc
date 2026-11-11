<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class ClientMedicationMasterRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_clientmedicationmaster_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    CONCAT(
                        CAST(CLIENT_ID AS STRING),
                        CAST(CM_MEDICATION_ID AS STRING),
                        CAST(CM_EPISODEID AS STRING)
                    ) AS client_medication_unique_id,
                    AGENCY_ID AS agency_id,
                    CLIENT_ID AS client_id,
                    CM_MEDICATION_ID AS medication_id,
                    CM_EPISODEID AS episodeid,
                    CAST(CM_LEVEL AS STRING) AS level,
                    CAST(CM_NAME AS STRING) AS name,
                    CAST(CM_DOSAGE AS STRING) AS dosage,
                    CAST(CM_ROUTE AS STRING) AS route,
                    CAST(CM_FREQUENCY AS STRING) AS frequency,
                    CAST(CM_MEDCODE AS STRING) AS medcode,
                    CAST(CM_PURPOSE AS STRING) AS purpose,
                    FORMAT_TIMESTAMP('%F %T', CM_START_DATE) AS start_date,
                    CAST(CM_PLANNED_DAYS AS STRING) AS planned_days,
                    CAST(CM_IS_PRN AS INT64) AS is_prn,
                    FORMAT_TIMESTAMP('%F %T', CM_DC_DATE) AS dc_date,
                    CAST(CM_DC_TIME AS STRING) AS dc_time,
                    CM_FREQUENCY_DAYS AS frequency_days,
                    CAST(CM_FREQUENCY_TYPE AS STRING) AS frequency_type,
                    CM_FREQUENCY_DAYSON AS frequency_dayson,
                    CM_FREQUENCY_DAYSOFF AS frequency_daysoff,
                    CAST(CM_CLASSIFICATION AS STRING) AS classification,
                    CAST(CM_MED_PROFILE AS STRING) AS med_profile,
                    CAST(CM_IS_PATIENTCAREGIVER_MANAGES AS INT64) AS is_patientcaregiver_manages,
                    CAST(CM_IS_ANTIBIOTIC AS INT64) AS is_antibiotic,
                    CAST(CM_IS_HIGH_RISK AS INT64) AS is_high_risk,
                    CAST(CM_IS_DC_ONTIME AS INT64) AS is_dc_ontime,
                    CAST(CM_IS_HOLD AS INT64) AS is_hold,
                    CAST(CM_IS_CURRENT_HOLD AS INT64) AS is_current_hold,
                    CAST(CM_CLASSIFICATION_TYPE AS STRING) AS classification_type,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(
                        FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                        CAST(CLIENT_ID AS STRING),
                        CAST(CM_MEDICATION_ID AS STRING),
                        CAST(CM_EPISODEID AS STRING)
                    ) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                    CAST(CLIENT_ID AS STRING),
                    CAST(CM_MEDICATION_ID AS STRING),
                    CAST(CM_EPISODEID AS STRING)
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
                    CONCAT(
                        CAST(CLIENT_ID AS STRING),
                        CAST(CM_MEDICATION_ID AS STRING),
                        CAST(CM_EPISODEID AS STRING)
                    ) AS client_medication_unique_id,
                    AGENCY_ID AS agency_id,
                    CLIENT_ID AS client_id,
                    CM_MEDICATION_ID AS medication_id,
                    CM_EPISODEID AS episodeid,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp
                FROM `$this->tableRef`
                WHERE CONCAT(
                    CAST(CLIENT_ID AS STRING),
                    CAST(CM_MEDICATION_ID AS STRING),
                    CAST(CM_EPISODEID AS STRING)
                ) = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
