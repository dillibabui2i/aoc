<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class ClientPhysicianMasterRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_clientphysicians_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    AGENCY_ID AS agency_id,
                    CLIENT_ID AS client_id,
                    CPHY_INTERNAL_ID AS internal_id,
                    CPHY_PHYSICIAN_ID AS physician_id,
                    CAST(CPHY_PHYSICIAN_IDENTIFIER AS STRING) AS physician_identifier,
                    FORMAT_TIMESTAMP('%F %T', CPHY_LASTSEEN_DATE) AS lastseen_date,
                    CAST(CPHY_LAST_VISIT_TYPE AS STRING) AS last_visit_type,
                    CAST(CPHY_SPECIALIZATION AS STRING) AS specialization,
                    CAST(CPHY_PHONE AS STRING) AS phone,
                    CAST(CPHY_FAX AS STRING) AS fax,
                    CPHY_OFFICE_ID AS office_id,
                    CAST(CPHY_OFFICE_PHONENUMBER AS STRING) AS office_phonenumber,
                    CAST(CPHY_OFFICE_FAXNUMBER AS STRING) AS office_faxnumber,
                    CAST(CPHY_OFFICE_NAME AS STRING) AS office_name,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CPHY_INTERNAL_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CPHY_INTERNAL_ID AS STRING)) > ?
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
                    CLIENT_ID AS client_id,
                    CPHY_INTERNAL_ID AS internal_id,
                    CPHY_PHYSICIAN_ID AS physician_id,
                    CAST(CPHY_PHYSICIAN_IDENTIFIER AS STRING) AS physician_identifier,
                    FORMAT_TIMESTAMP('%F %T', CPHY_LASTSEEN_DATE) AS lastseen_date,
                    CAST(CPHY_LAST_VISIT_TYPE AS STRING) AS last_visit_type,
                    CAST(CPHY_SPECIALIZATION AS STRING) AS specialization,
                    CAST(CPHY_PHONE AS STRING) AS phone,
                    CAST(CPHY_FAX AS STRING) AS fax,
                    CPHY_OFFICE_ID AS office_id,
                    CAST(CPHY_OFFICE_PHONENUMBER AS STRING) AS office_phonenumber,
                    CAST(CPHY_OFFICE_FAXNUMBER AS STRING) AS office_faxnumber,
                    CAST(CPHY_OFFICE_NAME AS STRING) AS office_name,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP), CAST(CPHY_INTERNAL_ID AS STRING)) AS combinedstring
                FROM `$this->tableRef`
                WHERE CPHY_INTERNAL_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }
}
