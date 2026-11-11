<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Domain\DataItem;

final class ClientMasterAdditionalRepository extends AbstractCommonRepository
{
    const TABLE_NAME = 'daas_dw_clientmasteradditional_vw';

    public function getQuery(): string
    {
        return <<<SQL
                SELECT
                    CONCAT(
                        CAST(CLIENT_INTAKE_ID AS STRING),
                        CAST(IFNULL(CLIENT_CLIENT_ID, 0) AS STRING)
                    ) AS client_additional_unique_id,
                    AGENCY_ID AS agency_id,
                    CLIENT_CLIENT_ID AS client_client_id,
                    CLIENT_INTAKE_ID AS intake_id,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    CLIENT_FIRST_EPISODE_ID AS first_episode_id,
                    CLIENT_LAST_EPISODE_ID AS last_episode_id,
                    CAST(CLIENT_RN_TELNUMBER AS STRING) AS rn_telnumber,
                    CAST(CLIENT_PT_TELNUMBER AS STRING) AS pt_telnumber,
                    CAST(CLIENT_OT_TELNUMBER AS STRING) AS ot_telnumber,
                    CAST(CLIENT_ST_TELNUMBER AS STRING) AS st_telnumber,
                    CAST(CLIENT_MSW_TELNUMBER AS STRING) AS msw_telnumber,
                    CAST(CLIENT_OTHER_DIAGNOSIS AS STRING) AS other_diagnosis,
                    CAST(CLIENT_EMAIL AS STRING) AS email,
                    CAST(CLIENT_HOSPITAL_PHONE AS STRING) AS hospital_phone,
                    CAST(CLIENT_INSURANCE_GROUP_NO AS STRING) AS insurance_group_no,
                    CAST(CLIENT_SSN AS STRING) AS ssn,
                    CAST(CLIENT_BILLING_ACUITY_EFFPERIOD AS STRING) AS billing_acuity_effperiod,
                    FORMAT_TIMESTAMP('%F %T', CLIENT_LAST_SCHEDULE_DATE) AS last_schedule_date,
                    FORMAT_TIMESTAMP('%F %T', CLIENT_LAST_RECERT_DATE) AS last_recert_date,
                    CAST(CLIENT_DAYS_LAST_RECERT AS STRING) AS days_last_recert,
                    FORMAT_TIMESTAMP('%F %T', CLIENT_LAST_SUPERVISORY_VISIT) AS last_supervisory_visit,
                    CAST(CLIENT_POC_MEDICATION AS STRING) AS poc_medication,
                    CAST(CLIENT_POC_DME AS STRING) AS poc_dme,
                    CAST(CLIENT_POC_SUPPLIES AS STRING) AS poc_supplies,
                    CAST(CLIENT_POC_SAFETYMEASURES AS STRING) AS poc_safetymeasures,
                    CAST(CLIENT_POC_NUTRITION_REQUIRMENT AS STRING) AS poc_nutrition_requirment,
                    CAST(CLIENT_POC_FOOD_ALLERGIES AS STRING) AS poc_food_allergies,
                    CAST(CLIENT_POC_MED_ALLERGIES AS STRING) AS poc_med_allergies,
                    CAST(CLIENT_POC_ENVIRONMENT_ALLERGIES AS STRING) AS poc_environment_allergies,
                    CAST(CLIENT_POC_FUNCTIONAL_LIMITATIONS AS STRING) AS poc_functional_limitations,
                    CAST(CLIENT_POC_ACTIVITIES_PERMITTED AS STRING) AS poc_activities_permitted,
                    CAST(CLIENT_POC_MENTAL_STATUS AS STRING) AS poc_mental_status,
                    CAST(CLIENT_POC_PROGNOSIS AS STRING) AS poc_prognosis,
                    FORMAT_TIMESTAMP('%F %T', CLIENT_POC_VERBAL_SOC_DATE) AS poc_verbal_soc_date,
                    FORMAT_TIMESTAMP('%F %T', CLIENT_POC_HHA_RECEIVED_DATE) AS poc_hha_received_date,
                    CAST(CLIENT_POC_CERTIFICATION AS STRING) AS poc_certification,
                    CAST(CLIENT_CMA_AGENCY_NAME AS STRING) AS cma_agency_name,
                    CAST(CLIENT_REPRESENTATIVE_AVAIL AS STRING) AS representative_avail,
                    CAST(CLIENT_PORTAL_AVAIL AS STRING) AS portal_avail,
                    CAST(CLIENT_ATTPHY_OFFICE_FAX AS STRING) AS attphy_office_fax,
                    CAST(CLIENT_ATTPHY_OFFICE_PHONE AS STRING) AS attphy_office_phone,
                    CAST(CLIENT_DOC_RECEIVED AS STRING) AS doc_received,
                    CAST(CLIENT_INTAKE_COORDINATOR_LASTNAME AS STRING) AS intake_coordinator_lastname,
                    CAST(CLIENT_INTAKE_COORDINATOR_FIRSTNAME AS STRING) AS intake_coordinator_firstname,
                    CAST(CLIENT_REFPHY_OFFICE_FAX AS STRING) AS refphy_office_fax,
                    CAST(CLIENT_REFPHY_OFFICE_PHONE AS STRING) AS refphy_office_phone,
                    CLIENT_DAYS_ON_SERVICE AS days_on_service,
                    CAST(CLIENT_ALERT AS STRING) AS alert,
                    CAST(CLIENT_PAYROLL_EXPORT_ID AS STRING) AS payroll_export_id,
                    CAST(CLIENT_BILLING_ADDRESS_LINE1 AS STRING) AS billing_address_line1,
                    CAST(CLIENT_BILLING_ADDRESS_LINE2 AS STRING) AS billing_address_line2,
                    CAST(CLIENT_BILLING_ADDRESS_CITY AS STRING) AS billing_address_city,
                    CAST(CLIENT_BILLING_ADDRESS_STATE AS STRING) AS billing_address_state,
                    CAST(CLIENT_BILLING_ADDRESS_ZIP AS STRING) AS billing_address_zip,
                    CAST(CLIENT_GURANTOR_ID AS STRING) AS gurantor_id,
                    CAST(CLIENT_GURANTOR_NAME AS STRING) AS gurantor_name,
                    CAST(CLIENT_GURANTOR_RELATION AS STRING) AS gurantor_relation,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS lastmodified,
                    CONCAT(
                        FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                        CAST(CLIENT_INTAKE_ID AS STRING),
                        CAST(IFNULL(CLIENT_CLIENT_ID, 0) AS STRING)
                    ) AS combinedstring
                FROM `$this->tableRef`
                WHERE CONCAT(
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP),
                    CAST(CLIENT_INTAKE_ID AS STRING),
                    CAST(IFNULL(CLIENT_CLIENT_ID, 0) AS STRING)
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
                        CAST(CLIENT_INTAKE_ID AS STRING),
                        CAST(IFNULL(CLIENT_CLIENT_ID, 0) AS STRING)
                    ) AS client_additional_unique_id,
                    AGENCY_ID AS agency_id,
                    CLIENT_CLIENT_ID AS client_client_id,
                    CLIENT_INTAKE_ID AS intake_id,
                    FORMAT_TIMESTAMP('%F %T', DAAS_LAST_MODIFIED_TIMESTAMP) AS daas_last_modified_timestamp,
                    CAST(ISVALIDRECORD AS INT64) AS isvalidrecord,
                    FORMAT_TIMESTAMP('%F %T', ETL_UPDATED_TIMESTAMP) AS etl_updated_timestamp,
                    CLIENT_FIRST_EPISODE_ID AS first_episode_id,
                    CLIENT_LAST_EPISODE_ID AS last_episode_id
                FROM `$this->tableRef`
                WHERE CONCAT(
                    CAST(CLIENT_INTAKE_ID AS STRING),
                    CAST(IFNULL(CLIENT_CLIENT_ID, 0) AS STRING)
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
