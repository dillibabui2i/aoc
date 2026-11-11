<?php

namespace App\Kantime\Repository\BigQuery;

use App\Kantime\Common\BigQueryConnection;
use App\Kantime\Domain\DataItem;

final class ClientMasterRepository extends AbstractCommonRepository
{
    private const TABLE_NAME = 'daas_dw_clientmaster_vw';

    private const CUSTOMFIELDS_TABLE = 'daas_dw_customfields_vw';
    private const USERMASTER_TABLE = 'daas_dw_usermaster_vw';
    private const SALESPERSON_TABLE = 'daas_dw_salesperson_vw';

    private string $customfieldsRef;
    private string $usermasterRef;
    private string $salespersonRef;

    public function __construct(BigQueryConnection $connection)
    {
        parent::__construct($connection);
        $this->customfieldsRef = $this->connection->tableRef(self::CUSTOMFIELDS_TABLE);
        $this->usermasterRef = $this->connection->tableRef(self::USERMASTER_TABLE);
        $this->salespersonRef = $this->connection->tableRef(self::SALESPERSON_TABLE);
    }

    public function getQuery(): string
    {
        $columns = $this->selectColumns();

        return <<<SQL
                SELECT
                    {$columns}
                FROM `$this->tableRef` cm
                LEFT JOIN `$this->customfieldsRef` cf
                    ON cf.CF_INTAKE_ID = cm.CLIENT_INTAKE_ID
                LEFT JOIN `$this->usermasterRef` um
                    ON um.CG_USER_ID = cm.CLIENT_INTAKE_ADDEDBY
                LEFT JOIN `$this->salespersonRef` sp
                    ON sp.SP_SALESPERSON_ID = cm.CLIENT_SALESPERSON_ID
                    AND CAST(sp.ISVALIDRECORD AS BOOL) = TRUE
                WHERE CONCAT(
                        FORMAT_TIMESTAMP('%F %T', cm.ETL_UPDATED_TIMESTAMP),
                        '|',
                        CAST(cm.CLIENT_PATIENT_ID AS STRING),
                        '|',
                        CAST(cm.CLIENT_INTAKE_SEQUENCE_NO AS STRING),
                        '|',
                        CAST(cm.CLIENT_INTAKE_ID AS STRING)
                    ) > ?
                ORDER BY CONCAT(
                        FORMAT_TIMESTAMP('%F %T', cm.ETL_UPDATED_TIMESTAMP),
                        '|',
                        CAST(cm.CLIENT_PATIENT_ID AS STRING),
                        '|',
                        CAST(cm.CLIENT_INTAKE_SEQUENCE_NO AS STRING),
                        '|',
                        CAST(cm.CLIENT_INTAKE_ID AS STRING)
                    )
                LIMIT ?
                SQL;
    }

    public function getTableName(): string
    {
        return self::TABLE_NAME;
    }

    public function getRecord($identifier): array|DataItem
    {
        $columns = $this->selectColumns();
        $query = <<<SQL
                SELECT
                    {$columns}
                FROM `$this->tableRef` cm
                LEFT JOIN `$this->customfieldsRef` cf
                    ON cf.CF_INTAKE_ID = cm.CLIENT_INTAKE_ID
                LEFT JOIN `$this->usermasterRef` um
                    ON um.CG_USER_ID = cm.CLIENT_INTAKE_ADDEDBY
                LEFT JOIN `$this->salespersonRef` sp
                    ON sp.SP_SALESPERSON_ID = cm.CLIENT_SALESPERSON_ID
                    AND CAST(sp.ISVALIDRECORD AS BOOL) = TRUE
                WHERE cm.CLIENT_PATIENT_ID = ?
                LIMIT 1
                SQL;

        $rows = iterator_to_array($this->connection->cursor($query, [$identifier]), false);

        if (empty($rows)) {
            return [];
        }

        return (array) $rows[0];
    }

    public function getReferralFacilityRows(int $limit = 0): \Generator
    {
        $limitClause = $limit > 0 ? 'LIMIT ' . $limit : '';

        $query = <<<SQL
                SELECT
                    CAST(cm.CLIENT_PATIENT_ID AS STRING) AS PatientAccount,
                    cm.CLIENT_INTAKE_ID AS IntakeID,
                    cm.CLIENT_INTAKE_SEQUENCE_NO AS CLIENT_INTAKE_SEQUENCE_NO,
                    cm.CLIENT_LOB_ID AS LOBID,
                    cm.CLIENT_REFERRAL_FACILITY_ID AS ReferralSourceID
                FROM `$this->tableRef` cm
                WHERE CAST(cm.ISVALIDRECORD AS BOOL) = TRUE
                ORDER BY cm.CLIENT_INTAKE_ID, cm.CLIENT_INTAKE_SEQUENCE_NO
                {$limitClause}
                SQL;

        yield from $this->connection->cursor($query);
    }

    private function selectColumns(): string
    {
        return <<<'COLS'
                    CONCAT(
                        FORMAT_TIMESTAMP('%F %T', cm.ETL_UPDATED_TIMESTAMP),
                        '|',
                        CAST(cm.CLIENT_PATIENT_ID AS STRING),
                        '|',
                        CAST(cm.CLIENT_INTAKE_SEQUENCE_NO AS STRING),
                        '|',
                        CAST(cm.CLIENT_INTAKE_ID AS STRING)
                    ) AS CURSOR_KEY,
                    cm.AGENCY_ID AS HHA,
                    CAST(CLIENT_PRIMARY_PAYER_TYPE AS STRING) AS PrimaryPayerType,
                    cm.AGENCY_BRANCH_ID AS HHABranchID,
                    cm.CLIENT_ID AS ClientID,
                    cm.CLIENT_INTAKE_ID AS IntakeID,
                    cm.CLIENT_INTAKE_SEQUENCE_NO AS CLIENT_INTAKE_SEQUENCE_NO,
                    CAST(cm.CLIENT_PATIENT_ID AS STRING) AS PatientAccount,
                    cm.CLIENT_PRIMARY_PAYER_ID AS PrimaryPayerID,
                    cm.CLIENT_PRIMARY_PAYER_GROUP AS PrimaryPayerGroupID,
                    cm.CLIENT_PRIMARY_PROGRAM AS PrimaryPayerProgramTypeID,
                    CAST(cm.CLIENT_IS_INTAKE AS INT64) AS IS_INTAKE,
                    CAST(cm.CLIENT_IS_LATESTADMIT AS INT64) AS IsLatestAdmit,
                    CAST(cm.CLIENT_IS_NON_ADMIT AS INT64) AS IsNonAdmit,
                    CAST(cm.ISVALIDRECORD AS INT64) AS ISVALIDRECORD,
                    CAST(cm.CLIENT_SALUTATION AS STRING) AS Patient_Salutation,
                    CAST(cm.CLIENT_FIRST_NAME AS STRING) AS Patient_FirstName,
                    CAST(cm.CLIENT_MIDDLE_NAME AS STRING) AS Patient_MiddleName,
                    CAST(cm.CLIENT_LAST_NAME AS STRING) AS Patient_LastName,
                    TRIM(CONCAT(cm.CLIENT_LAST_NAME, ', ', cm.CLIENT_FIRST_NAME, ' ', IFNULL(cm.CLIENT_MIDDLE_NAME, ''))) AS PatientName,
                    CAST(cm.CLIENT_GENDER AS STRING) AS Patient_Gender,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_DOB) AS Patient_DOB,
                    CAST(cm.CLIENT_ETHNICITY AS STRING) AS Ethnicity,
                    CAST(cm.CLIENT_MEDICAID_NO AS STRING) AS MedicaidNumber,
                    CAST(cm.CLIENT_MEDICARENO AS STRING) AS MedicareNumber,
                    CAST(cm.CLIENT_ADDRESS_LINE1 AS STRING) AS Patient_Address1,
                    CAST(cm.CLIENT_ADDRESS_LINE2 AS STRING) AS Patient_Address2,
                    CAST(cm.CLIENT_CITY AS STRING) AS Patient_City,
                    CAST(cm.CLIENT_STATE AS STRING) AS Patient_State,
                    CAST(cm.CLIENT_ZIP AS STRING) AS Patient_Zip,
                    CAST(cm.CLIENT_PHONE AS STRING) AS Patient_Phone,
                    CAST(cm.CLIENT_MOBILE AS STRING) AS Patient_MobilPhone,
                    cm.CLIENT_CASEMANAGER_ID AS CaseManagerID,
                    cm.CLIENT_ATTENDING_PHYSICIAN_ID AS AttendingPhysicianID,
                    cm.CLIENT_PRIMARY_CLINICIAN_ID AS PrimaryClinicianID,
                    cm.CLIENT_PRIMARYCARE_PHYSICIAN_ID AS PrimaryCarePhysicianID,
                    cm.CLIENT_PRIMARY_OT AS PrimaryOT,
                    cm.CLIENT_PRIMARY_ST AS PrimaryST,
                    cm.CLIENT_PRIMARY_PT AS PrimaryPT,
                    cm.CLIENT_PRIMARY_MSW_CLINICIAN_ID AS PrimaryMSWClinicianID,
                    cm.CLIENT_PRIMARY_AIDE_CLINICIAN_ID AS PrimaryAIDEClinicianID,
                    cm.CLIENT_REFERRAL_PHYSICIAN_ID AS ReferringProviderID,
                    cm.CLIENT_SCHEDULER_ID AS SchedulerID,
                    CAST(cm.CLIENT_CURRENT_STATUS AS STRING) AS Status,
                    cm.CLIENT_LOB_ID AS LOBID,
                    cm.CLIENT_ADMIT_NO AS AdmitNo,
                    cm.CLIENT_ADMIT_GROUP_NO AS CLIENT_ADMIT_GROUP_NO,
                    CAST(cm.CLIENT_ASSESSMENT_SEQUENCE AS STRING) AS AssessmentSequence,
                    cm.CLIENT_REFERRAL_FACILITY_ID AS ReferralSourceID,
                    CAST(cm.CLIENT_PREFERENCE_LIST AS STRING) AS PreferenceList,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_SOC_DATE) AS SOCDate,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_INTAKE_DATE) AS IntakeDate,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_ACCEPTED_DATE) AS AcceptedDate,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_INTAKE_ACCEPTEDDATE) AS IntakeAcceptedDate,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_INITIAL_SOC_DATE) AS InitialSOCdateCurrentAdmit,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_DISCHARGE_DATE) AS DischargeDate,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_TRANSFER_DATE) AS TransferDate,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_REFERRAL_DATE) AS ReferralDate,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_REQUESTED_SOC_DATE) AS RequestedSocDate,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_ADMIT_DATE) AS NonAdmitDate,
                    FORMAT_TIMESTAMP('%F %T', cm.CLIENT_ACTIVEDATE) AS ACTIVEDATE,
                    CAST(cm.CLIENT_ACTUAL_INTAKE_DATE AS STRING) AS ActualIntakeDate,
                    CAST(cm.CLIENT_MD_NEXT_VISITDATE AS STRING) AS NextMDVisit,
                    CAST(cm.CLIENT_MD_LAST_VISITDATE AS STRING) AS LastMDVisit,
                    CAST(cm.CLIENT_CARECOORDINATOR1 AS STRING) AS ClientCareCoordinator,
                    CAST(cm.CLIENT_DRIVING_DIRECTION AS STRING) AS DrivingDirection,
                    CAST(cm.CLIENT_CARECOORDINATOR2 AS STRING) AS CC2,
                    CAST(cm.CLIENT_LANGUAGES AS STRING) AS Languages,
                    cm.CLIENT_SOC_CLINICIAN_ID AS SOCClinicianID,
                    CAST(cm.CLIENT_INTAKE_STATUS AS STRING) AS IntakeStatus,
                    CAST(cm.CLIENT_ORDERRECEIVED AS STRING) AS OrdersReceived,
                    CAST(cm.CLIENT_LENGTH_OF_STAY AS STRING) AS LengthOfStay,
                    CAST(cm.CLIENT_SALESPERSON_ID AS STRING) AS SalesPersonID,
                    CAST(cm.CLIENT_CLASSIFICATION AS STRING) AS Classification,
                    CAST(cm.CLIENT_MARITALSTATUS AS STRING) AS MaritalStatus,
                    CAST(cm.CLIENT_TRANSPORTLEVELASSISTANCE AS STRING) AS TransportLevelAssistance,
                    CAST(cm.CLIENT_PLACEOFSERVICE AS STRING) AS PlaceOfService,
                    CAST(cm.CLIENT_LIVINGSITUATION AS STRING) AS living_situation,
                    CAST(cm.CLIENT_TREATMENTADDRESS_NAME AS STRING) AS TreatmentAddressName,
                    CAST(cm.CLIENT_TREATMENTADDRESS_LINE1 AS STRING) AS TreatmentAddress1,
                    CAST(cm.CLIENT_TREATMENTADDRESS_LINE2 AS STRING) AS TreatmentAddress2,
                    CAST(cm.CLIENT_TREATMENTADDRESS_CITY AS STRING) AS TreatmentCity,
                    CAST(cm.CLIENT_TREATMENTADDRESS_STATE AS STRING) AS TreatmentState,
                    CAST(cm.CLIENT_TREATMENTADDRESS_EMAIL AS STRING) AS TreatmentEmail,
                    CAST(cm.CLIENT_TREATMENTADDRESS_ZIP AS STRING) AS TreatmentZIPCode,
                    CAST(cm.CLIENT_TREATMENTADDRESS_PHONE AS STRING) AS TreatmentTelePhone,
                    CAST(cm.CLIENT_TREATMENTADDRESS_CELLPHONE AS STRING) AS TreatmentCellPhone,
                    cf.CF_KP_VALUE AS cf_kp_value,
                    um.CAREGIVER_ID AS caregiver_id,
                    sp.SP_EMPLOYEE_ID AS SP_EMPLOYEE_ID,
                    TRIM(CONCAT(
                        IFNULL(CAST(sp.SP_FIRST_NAME AS STRING), ''), ' ',
                        IFNULL(CAST(sp.SP_LAST_NAME AS STRING), '')
                    )) AS BD_Rep,
                    FORMAT_TIMESTAMP('%F %T', cm.ETL_UPDATED_TIMESTAMP) AS LastModified
                COLS;
    }
}
