<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class ReferralSourceRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_referralsource_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    AGENCY_ID AS agency_id,
                    RS_SOURCE_ID AS source_id,
                    AGENCY_BRANCH_ID AS agency_branch_id,
                    CAST(RS_NAME AS STRING) AS name,
                    RS_ADMISSION_SOURCE AS admission_source,
                    CAST(RS_ADDRESS_LINE1 AS STRING) AS address_line1,
                    CAST(RS_ADDRESS_LINE2 AS STRING) AS address_line2,
                    CAST(RS_STATE AS STRING) AS state,
                    CAST(RS_ZIP AS STRING) AS zip,
                    CAST(RS_PHONE_NO AS STRING) AS phone_no,
                    CAST(RS_MOBILE_NO AS STRING) AS mobile_no,
                    CAST(RS_CONTACT_NAME AS STRING) AS contact_name,
                    CAST(RS_CONTAXT_PHONE AS STRING) AS contact_phone,
                    RS_SALESPERSON_ID AS salesperson_id,
                    CAST(RS_CATEGORY AS STRING) AS category,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(RS_SOURCE_ID AS STRING)) AS combinedstring,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(RS_SOURCE_ID AS STRING)) > ?
                ORDER BY combinedstring
                LIMIT ?
                SQL;
    }

    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function getRecord($identifier) : array | DataItem
    {
        $query = <<<SQL
                SELECT
                    AGENCY_ID AS agency_id,
                    RS_SOURCE_ID AS source_id,
                    AGENCY_BRANCH_ID AS agency_branch_id,
                    CAST(RS_NAME AS STRING) AS name,
                    RS_ADMISSION_SOURCE AS admission_source,
                    CAST(RS_ADDRESS_LINE1 AS STRING) AS address_line1,
                    CAST(RS_ADDRESS_LINE2 AS STRING) AS address_line2,
                    CAST(RS_STATE AS STRING) AS state,
                    CAST(RS_ZIP AS STRING) AS zip,
                    CAST(RS_PHONE_NO AS STRING) AS phone_no,
                    CAST(RS_MOBILE_NO AS STRING) AS mobile_no,
                    CAST(RS_CONTACT_NAME AS STRING) AS contact_name,
                    CAST(RS_CONTAXT_PHONE AS STRING) AS contact_phone,
                    RS_SALESPERSON_ID AS salesperson_id,
                    CAST(RS_CATEGORY AS STRING) AS category,
                    CAST(DAAS_LAST_MODIFIED_TIMESTAMP AS STRING) AS daas_last_modified_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(RS_SOURCE_ID AS STRING)) AS combinedstring,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord
                FROM `$this->tableRef`
                WHERE RS_SOURCE_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
