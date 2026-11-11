<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class PhysicianMasterRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_physicianmaster_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    AGENCY_ID AS agency_id,
                    PHYSICIAN_ID AS physician_id,
                    PHY_SALES_PERSON_ID AS sales_person_id,
                    CAST(PHY_FIRST_NAME AS STRING) AS first_name,
                    CAST(PHY_LAST_NAME AS STRING) AS last_name,
                    CAST(PHY_MIDDLE_NAME AS STRING) AS middle_name,
                    CAST(PHY_STATUS AS STRING) AS status,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(PHY_NPI AS STRING) AS npi,
                    CAST(PHY_TITLE AS STRING) AS title,
                    CAST(PHY_EXTERNAL_ID AS STRING) AS external_id,
                    CAST(PHY_LICENSENO AS STRING) AS licenseno,
                    FORMAT_TIMESTAMP('%F %T', PHY_LICENSEEXPIRYDATE) AS licenseexpirydate,
                    CAST(PHY_UPIN AS STRING) AS upin,
                    CAST(PHY_TPI AS STRING) AS tpi,
                    CAST(PHY_TAXONOMYCODE AS STRING) AS taxonomycode,
                    CAST(PHY_PECOSENROLLED AS STRING) AS pecosenrolled,
                    CAST(PHY_ADDRESS_1 AS STRING) AS address_1,
                    CAST(PHY_ADDRESS_2 AS STRING) AS address_2,
                    CAST(PHY_ZIP AS STRING) AS zip,
                    CAST(PHY_CITY AS STRING) AS city,
                    CAST(PHY_STATE AS STRING) AS state,
                    CAST(PHY_MOBILE AS STRING) AS mobile,
                    CAST(PHY_PHONE AS STRING) AS phone,
                    CAST(PHY_EMAIL AS STRING) AS email,
                    CAST(PHY_FAX AS STRING) AS fax,
                    CAST(PHY_SPECIALIZATION AS STRING) AS specialization,
                    CAST(PHY_CATEGORY AS STRING) AS category,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(PHYSICIAN_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(PHYSICIAN_ID AS STRING)) > ?
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
                    PHYSICIAN_ID AS physician_id,
                    PHY_SALES_PERSON_ID AS sales_person_id,
                    CAST(PHY_FIRST_NAME AS STRING) AS first_name,
                    CAST(PHY_LAST_NAME AS STRING) AS last_name,
                    CAST(PHY_MIDDLE_NAME AS STRING) AS middle_name,
                    CAST(PHY_STATUS AS STRING) AS status,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(PHY_NPI AS STRING) AS npi,
                    CAST(PHY_TITLE AS STRING) AS title,
                    CAST(PHY_EXTERNAL_ID AS STRING) AS external_id,
                    CAST(PHY_LICENSENO AS STRING) AS licenseno,
                    FORMAT_TIMESTAMP('%F %T', PHY_LICENSEEXPIRYDATE) AS licenseexpirydate,
                    CAST(PHY_UPIN AS STRING) AS upin,
                    CAST(PHY_TPI AS STRING) AS tpi,
                    CAST(PHY_TAXONOMYCODE AS STRING) AS taxonomycode,
                    CAST(PHY_PECOSENROLLED AS STRING) AS pecosenrolled,
                    CAST(PHY_ADDRESS_1 AS STRING) AS address_1,
                    CAST(PHY_ADDRESS_2 AS STRING) AS address_2,
                    CAST(PHY_ZIP AS STRING) AS zip,
                    CAST(PHY_CITY AS STRING) AS city,
                    CAST(PHY_STATE AS STRING) AS state,
                    CAST(PHY_MOBILE AS STRING) AS mobile,
                    CAST(PHY_PHONE AS STRING) AS phone,
                    CAST(PHY_EMAIL AS STRING) AS email,
                    CAST(PHY_FAX AS STRING) AS fax,
                    CAST(PHY_SPECIALIZATION AS STRING) AS specialization,
                    CAST(PHY_CATEGORY AS STRING) AS category,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(PHYSICIAN_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE PHYSICIAN_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
