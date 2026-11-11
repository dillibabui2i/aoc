<?php

namespace App\Kantime\Services;

use App\Enums\AcquisitionEnum;
use App\Helpers\ConstantHelper;
use App\Helpers\LD;
use App\Helpers\NTHChecklist;
use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ClientMasterRepository;
use App\Kantime\Repository\MariaDB\AocIntakeRepository;
use App\Kantime\Repository\MariaDB\AocNthRepository;
use App\Kantime\Repository\MariaDB\AocPatientsRepository;
use App\Kantime\Repository\MariaDB\AocPatientStatusRepository;
use App\Kantime\Repository\MariaDB\FacilityRepository;
use App\Services\NeedToHire\SocialMediaAdAutoCloseService;

final class ClientMasterSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_patients_test_daas';

    private const LOB_IDS_NOT_ALLOWED_TO_OVERRIDE_HOURS = [
        '1023', // Colorado
        '1029', // IHSS
        '1004', // OT
        '1034', // PCS
        '1031', // PPEC
        '1003', // PT
        '1006', // ST
    ];

    public function __construct(
        private readonly ClientMasterRepository $repository,
        private readonly AocPatientsRepository $patientsRepository,
        private readonly AocIntakeRepository $intakeRepository,
        private readonly AocPatientStatusRepository $patientStatusRepository,
        private readonly AocNthRepository $nthRepository,
        private readonly FacilityRepository $facilityRepository,
        private readonly KantimeSyncConfig $syncConfig,
        //TODO these dependencies should be moved together with Kantime subdir when moving to other project
        private readonly Sync $sync,
        private readonly NTHChecklist $nthChecklist,
        private readonly ConstantHelper $constantHelper,
        private readonly LD $ld,
        private readonly SocialMediaAdAutoCloseService $socialMediaAdAutoCloseService,
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $batchStatus = $this->sync->startBatch($syncType);

        if ($batchStatus === -1) {
            return;
        }

        $batchInfo = $this->sync->get_batch_information($syncType);
        $lastUpdatedDate = $batchInfo['last_updated_date'] ? date_create($batchInfo['last_updated_date'])->format('Y-m-d H:i:s') : '';
        $lastUpdatedId = $batchInfo['last_updated_id'] ? $batchInfo['last_updated_id'] : 0;
        $limit = (int) ($batchInfo['batch_size'] ?? 500);
        $lastSuccessfulDate = $lastUpdatedDate;
        $lastSuccessfulId = $lastUpdatedId;
        $aocFacilities = $this->getFacilities();

        try {
            foreach ($this->repository->getRecords($limit, $lastUpdatedId) as $row) {
                $this->syncConfig->debugSync && logger()->info('Processing Client Master record: ', $row);

                $this->processRow($row, $aocFacilities);

                $lastSuccessfulDate = $row['LastModified'] ?? $lastSuccessfulDate;
                $lastSuccessfulId = $row['CURSOR_KEY'] ?? $lastSuccessfulId;
            }
        } catch (\Throwable $e) {
            logger()->error('ClientMasterSyncService::sync failed', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Client Master Items:', [
                'lastUpdatedDate' => $lastSuccessfulDate,
                'lastUpdatedId' => $lastSuccessfulId,
            ]);

            $this->patientsRepository->recalculateLatestAdmits();
            $this->runPostSyncUpdates();

            $this->sync->update_last_updated($syncType, date_create($lastSuccessfulDate)->format('Y-m-d H:i:s'), $lastSuccessfulId);
            $this->sync->stopBatch($syncType);
        }
    }

    private function processRow(array $row, array $aocFacilities): void
    {
        $patientAccount = trim((string) ($row['PatientAccount'] ?? ''));
        $clientId = (int) ($row['ClientID'] ?? 0);
        $intakeId = (int) ($row['IntakeID'] ?? 0);
        $isValidRecord = (int) ($row['ISVALIDRECORD'] ?? 1);
        $isIntake = (int) ($row['IS_INTAKE'] ?? 0);

        $this->syncConfig->debugSync && logger()->info(
            'Processing patient: ' . ($row['PatientName'] ?? 'unknown') . ' - ' . $patientAccount .
            ' (' . $clientId . ' - ' . $intakeId . ')'
        );

        $customFields = json_decode((string) ($row['cf_kp_value'] ?? '{}'), true);
        if (!is_array($customFields)) {
            $customFields = [];
        }

        $acquisition = $this->getCustomFieldValue('Acquisition', $customFields);
        if ($acquisition === AcquisitionEnum::INCOMPLETE->value) {
            $this->syncConfig->debugSync && logger()->info(
                'Skipping patient ' . $patientAccount . ' because Acquisition = ' . AcquisitionEnum::INCOMPLETE->value
            );

            return;
        }

        $newRow = $this->buildRow($row, $customFields, $aocFacilities);
        $newRow['is_deleted'] = $isValidRecord === 0 ? 1 : 0;
        $newRow['is_updated'] = 1;
        $newRow['updated_at'] = now()->format('Y-m-d H:i:s');
        $newRow['Search_Name'] = str_replace(
            '  ',
            ' ',
            str_replace(
                ',',
                '',
                ($newRow['Patient_FirstName'] ?? '') . ' ' .
                ($newRow['Patient_LastName'] ?? '') . ' ' .
                ($newRow['Patient_FirstName'] ?? '')
            )
        );

        $existingRecord = $this->findExistingRecord(
            $patientAccount,
            (int) ($row['CLIENT_INTAKE_SEQUENCE_NO'] ?? 1),
            $intakeId,
            $acquisition,
            $newRow['MedicaidNumber'] ?? ''
        );

        if ($isValidRecord === 0) {
            if (!empty($existingRecord)) {
                $this->patientsRepository->softDelete((int) $existingRecord->id);
                $this->updateNTH($newRow, (int) $existingRecord->id);

                $this->syncConfig->debugSync && logger()->info('Deleted Client Master record: ' . $existingRecord->id);
            }

            return;
        }

        if (!empty($existingRecord)) {
            $aocPatientId = (int) $existingRecord->id;

            if ((int) ($existingRecord->ic_id ?? 0) !== 0 && !is_null($existingRecord->ic_id)) {
                $newRow['ic_id'] = $existingRecord->ic_id;
            }

            if (!empty($existingRecord->PatientAccount)) {
                $newRow['not_in_kantime'] = 0;
            }

            $this->patientsRepository->update($aocPatientId, $newRow);
            $this->updateNTH($newRow, $aocPatientId);
            $this->syncIntakeMirrorOnUpdate($aocPatientId, $newRow);

            $this->syncConfig->debugSync && logger()->info('Updated Client Master record: ' . $aocPatientId);

            return;
        }

        $newRow['created_at'] = now()->format('Y-m-d H:i:s');
        $aocPatientId = $this->patientsRepository->insert($newRow);

        if (($row['Patient_LastName'] ?? '') !== 'PRN') {
            $this->syncIntakeMirrorOnInsert($aocPatientId, $newRow);
        }

        $this->syncConfig->debugSync && logger()->info('Inserted Client Master record: ' . $aocPatientId);
    }

    private function findExistingRecord(
        string $patientAccount,
        int $sequenceNo,
        int $intakeId,
        string $acquisition = '',
        string $medicaidNumber = ''
    ): ?object {
        if (
            $this->ld->checkFeatureFlagLoggedOut('acquisition-merge-by-medicaid-id') &&
            $acquisition === AcquisitionEnum::YES->value &&
            $medicaidNumber !== ''
        ) {
            $record = $this->patientsRepository->findByMedicaidNumber($medicaidNumber);
            if ($record) {
                return $record;
            }
        }

        if ($intakeId > 0 && $sequenceNo > 0) {
            $record = $this->patientsRepository->findByIntakeIdAndSequence($intakeId, $sequenceNo);
            if ($record) {
                return $record;
            }
        }

        if ($intakeId > 0) {
            $record = $this->patientsRepository->findByIntakeIdWithoutSequence($intakeId);
            if ($record) {
                return $record;
            }
        }

        if ($patientAccount !== '') {
            $record = $this->patientsRepository->findByPatientAccountNoIntake($patientAccount);
            if ($record) {
                return $record;
            }
        }

        return null;
    }

    private function syncIntakeMirrorOnInsert(int $aocPatientId, array $newRow): void
    {
        try {
            $this->intakeRepository->insert([
                'aoc_patient_id' => $aocPatientId,
                'ic_id'              => $newRow['ic_id'] ?? null,
                'ReferralDate'       => $newRow['ReferralDate'] ?? null,
                'IntakeDate'         => $newRow['ActualIntakeDate'] ?? null,
                'AttendingPhysicianID' => $newRow['ReferringProviderID'] ?? null,
                'ReferralSourceID'   => $newRow['ReferralSourceID'] ?? null,
                'LOBID'              => $newRow['LOBID'] ?? null,
                'created_at'         => now()->format('Y-m-d H:i:s'),
                'updated_at'         => now()->format('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            logger()->error('Failed to sync intake mirror on insert: ' . $e->getMessage());
        }
    }

    private function syncIntakeMirrorOnUpdate(int $aocPatientId, array $newRow): void
    {
        try {
            $intakeRecord = $this->intakeRepository->findByPatientAndLob($aocPatientId, $newRow['LOBID'] ?? null);

            if ($intakeRecord) {
                $this->intakeRepository->update($intakeRecord->id, [
                    'ReferralDate'        => $newRow['ReferralDate'] ?? null,
                    'IntakeDate'          => $newRow['ActualIntakeDate'] ?? null,
                    'AttendingPhysicianID' => $newRow['ReferringProviderID'] ?? null,
                    'ReferralSourceID'    => $newRow['ReferralSourceID'] ?? null,
                    'updated_at'          => now()->format('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Throwable $e) {
            logger()->error('Failed to sync intake mirror on update: ' . $e->getMessage());
        }
    }

    private function runPostSyncUpdates(): void
    {
        $this->patientsRepository->updatePayerFromClientPaymentSource();
        $this->patientsRepository->updateMolinaPayerGroup();
        $this->patientsRepository->updateRespitePayerFromClientPaymentSource();
        $this->intakeRepository->syncIcIdFromPatients();
        $this->intakeRepository->markNonAdmitIntakes();
    }

    private function updateNTH(array $row, int $aocPatientId): void
    {
        $this->syncConfig->debugSync && logger()->info('Looking for Active NTH record for: ' . $aocPatientId);

        $nthAccount = $this->nthRepository->findActiveByPatientId($aocPatientId);

        if (!isset($nthAccount->id)) {
            return;
        }

        $nthAccountId = (int) $nthAccount->id;

        if (in_array((int) ($row['Patient_CurrentStatusID'] ?? 0), [2, 3, 5], true)) {
            $this->nthRepository->deactivateSchedules($nthAccountId);
            $this->nthRepository->deactivateAccount($nthAccountId);
            $this->nthRepository->deactivateMap($nthAccountId);
            $this->socialMediaAdAutoCloseService->closeOpenAdsForNth($nthAccountId);
        }

        $this->nthChecklist->AddEditNTHMapRecord($nthAccountId);
        $this->nthChecklist->setNTHScheduleToMap($nthAccountId);
    }

    private function getFacilities(): array
    {
        $result = [];
        foreach ($this->facilityRepository->getFacilityList() as $row) {
            $result[$row->BillingFacilityID] = $row->id;
        }

        return $result;
    }

    private function getFacilityId(array $aocFacilities, mixed $billingFacilityId): int
    {
        return isset($aocFacilities[$billingFacilityId]) ? (int) $aocFacilities[$billingFacilityId] : 21;
    }

    private function buildRow(array $row, array $customFields, array $aocFacilities): array
    {
        $nr = [];
        $nr['LastModified'] = $row['LastModified'] ?? null;
        $nr['HHA'] = $row['HHA'] ?? null;
        $nr['BillingInternalPatientID'] = $row['ClientID'] ?? null;
        $nr['PatientID'] = $row['ClientID'] ?? null;
        $nr['IntakeID'] = $row['IntakeID'] ?? null;
        $nr['CLIENT_INTAKE_SEQUENCE_NO'] = $row['CLIENT_INTAKE_SEQUENCE_NO'] ?? 1;
        $nr['is_intake'] = $row['IS_INTAKE'] ?? 0;
        $nr['aoc_facility_id'] = $this->getFacilityId($aocFacilities, $row['HHABranchID'] ?? null);
        $nr['EMRID'] = 1;
        $nr['PrimaryPayerID'] = $row['PrimaryPayerID'] ?? null;
        $nr['PrimaryPayerProgramTypeID'] = $row['PrimaryPayerProgramTypeID'] ?? null;
        $nr['PrimaryPayerGroupID'] = $row['PrimaryPayerGroupID'] ?? null;
        $nr['PatientAccount'] = $row['PatientAccount'] ?? null;
        $nr['PatientName'] = $row['PatientName'] ?? null;
        $nr['Patient_Salutation'] = $row['Patient_Salutation'] ?? null;
        $nr['Patient_FirstName'] = $row['Patient_FirstName'] ?? null;
        $nr['Patient_MiddleName'] = $row['Patient_MiddleName'] ?? null;
        $nr['Patient_LastName'] = $row['Patient_LastName'] ?? null;
        $nr['Patient_Gender'] = $row['Patient_Gender'] ?? null;
        $nr['Patient_DOB'] = $row['Patient_DOB'] ?? null;
        $nr['Ethnicity'] = $row['Ethnicity'] ?? null;
        $nr['MedicaidNumber'] = $row['MedicaidNumber'] ?? null;
        $nr['Patient_Address1'] = $row['Patient_Address1'] ?? null;
        $nr['Patient_Address2'] = $row['Patient_Address2'] ?? null;
        $nr['Patient_City'] = $row['Patient_City'] ?? null;
        $nr['Patient_State'] = $row['Patient_State'] ?? null;
        $nr['Patient_Zip'] = $row['Patient_Zip'] ?? null;
        $nr['Patient_Phone'] = $row['Patient_Phone'] ?? null;
        $nr['Patient_MobilPhone'] = $row['Patient_MobilPhone'] ?? null;
        $nr['CaseManagerID'] = $row['CaseManagerID'] ?? null;
        $nr['AttendingPhysicianID'] = $row['AttendingPhysicianID'] ?? null;
        $nr['PrimaryClinicianID'] = $row['PrimaryClinicianID'] ?? null;
        $nr['PrimaryOTID'] = $row['PrimaryOT'] ?? null;
        $nr['PrimarySTID'] = $row['PrimaryST'] ?? null;
        $nr['PrimaryPT'] = $row['PrimaryPT'] ?? null;
        $nr['PrimaryAIDEClinicianID'] = $row['PrimaryAIDEClinicianID'] ?? null;
        $nr['PrimaryMSWClinicianID'] = $row['PrimaryMSWClinicianID'] ?? null;
        $nr['ReferringProviderID'] = $row['ReferringProviderID'] ?? null;
        $nr['PrimarycarePhysicianID'] = $row['PrimaryCarePhysicianID'] ?? null;
        $nr['IsLatestAdmit'] = 0; // recalculated post-batch
        $nr['AdmitNo'] = $row['AdmitNo'] ?? null;
        $nr['ADMIT_GROUP_NO'] = $row['CLIENT_ADMIT_GROUP_NO'] ?? null;
        $nr['SOCDate'] = $row['SOCDate'] ?? null;
        $nr['AssessmentSequence'] = $row['AssessmentSequence'] ?? null;
        $nr['IntakeDate'] = $row['IntakeDate'] ?? null;
        $nr['INTAKE_DATE'] = $row['IntakeDate'] ?? null;
        $nr['ActualIntakeDate'] = $row['ActualIntakeDate'] ?? null;
        $nr['AcceptedDate'] = $row['AcceptedDate'] ?? null;
        $nr['INTAKE_ACCEPTEDDATE'] = $row['IntakeAcceptedDate'] ?? null;
        $nr['InitialSOCdateCurrentAdmit'] = $row['InitialSOCdateCurrentAdmit'] ?? null;
        $nr['DischargeDate'] = $row['DischargeDate'] ?? null;
        $nr['TransferDate'] = $row['TransferDate'] ?? null;
        $nr['ReferralDate'] = $row['ReferralDate'] ?? null;
        $nr['ACTIVEDATE'] = $row['ACTIVEDATE'] ?? null;
        $nr['requested_soc_date'] = $row['RequestedSocDate'] ?? null;
        $nr['NextMDVisit'] = $row['NextMDVisit'] ?? null;
        $nr['LastMDVisit'] = $row['LastMDVisit'] ?? null;
        $nr['LOBID'] = $row['LOBID'] ?? null;
        $nr['IsNonAdmit'] = $row['IsNonAdmit'] ?? null;
        $nr['ReferralSourceID'] = $row['ReferralSourceID'] ?? null;
        // PreferenceList is not synced from Kantime: it is derived in AOC from the aoc_cf_* Preferences custom fields
        //$nr['PreferenceList'] = $row['PreferenceList'] ?? null;
        $nr['BD_Rep'] = $row['BD_Rep'] ?? null;
        $nr['NonAdmitDate'] = $row['NonAdmitDate'] ?? null;
        $nr['LengthOfStay'] = $row['LengthOfStay'] ?? null;
        $nr['SalesPersonID'] = $row['SalesPersonID'] ?? null;
        $nr['ClientCareCoordinator'] = $row['ClientCareCoordinator'] ?? null;
        $nr['DrivingDirection'] = $row['DrivingDirection'] ?? null;
        $nr['CC2'] = $row['CC2'] ?? null;
        $nr['Languages'] = $row['Languages'] ?? null;
        $nr['SOCClinicianID'] = $row['SOCClinicianID'] ?? null;
        $nr['IntakeStatus'] = $row['IntakeStatus'] ?? null;
        $nr['OrdersReceived'] = $row['OrdersReceived'] ?? null;
        $nr['client_classification'] = $row['Classification'] ?? null;
        $nr['marital_status'] = $row['MaritalStatus'] ?? null;
        $nr['transport_level_assistance'] = $row['TransportLevelAssistance'] ?? null;
        $nr['PlaceOfService'] = $row['PlaceOfService'] ?? null;
        $nr['living_situation'] = $row['living_situation'] ?? null;
        $nr['TreatmentAddressName'] = $row['TreatmentAddressName'] ?? null;
        $nr['TreatmentAddress1'] = $row['TreatmentAddress1'] ?? null;
        $nr['TreatmentAddress2'] = $row['TreatmentAddress2'] ?? null;
        $nr['TreatmentAddressCity'] = $row['TreatmentCity'] ?? null;
        $nr['TreatmentAddressState'] = $row['TreatmentState'] ?? null;
        $nr['TreatmentAddressEmail'] = $row['TreatmentEmail'] ?? null;
        $nr['TreatmentAddressZIPCode'] = $row['TreatmentZIPCode'] ?? null;
        $nr['TreatmentAddressTelePhone'] = $row['TreatmentTelePhone'] ?? null;
        $nr['TreatmentAddressCellPhone'] = $row['TreatmentCellPhone'] ?? null;

        $nr['Patient_CurrentStatusID'] = $this->patientStatusRepository->getStatusIdByName($row['Status'] ?? '');

        if ((int) ($row['IS_INTAKE'] ?? 0) === 1) {
            $nr['Patient_CurrentStatusID'] = !empty($nr['IsNonAdmit']) ? 3 : 4;
        }

        $allowOverrideFromKantime = !in_array((string) ($nr['LOBID'] ?? ''), self::LOB_IDS_NOT_ALLOWED_TO_OVERRIDE_HOURS, true);

        if ($allowOverrideFromKantime) {
            $this->overrideIfNotEmpty($nr, 'AuthorizedWeeklyHours', $this->getCustomFieldValue('Authorized_PDN_Weekly_Hours', $customFields));
            $this->overrideIfNotEmpty($nr, 'AuthorizedWeeklyVisits', $this->getCustomFieldValue('Authorized_Weekly_Visits', $customFields));
            $this->overrideIfNotEmpty($nr, 'CNAAuthorizedWeeklyHours', $this->getCustomFieldValue('CNA_Authorized_Weekly_Hours', $customFields));
            $this->overrideIfNotEmpty($nr, 'HHAAuthorizedWeeklyHours', $this->getCustomFieldValue('Authorized_HHA_Weekly_Hours', $customFields));
            $this->overrideIfNotEmpty($nr, 'CF_RespWeeklyHrs', $this->getCustomFieldValue('Respite_Weekly_Hours', $customFields));
        }

        $nr['CF_NeedToHire'] = $this->getCustomFieldValue('Need-to-Hire', $customFields);
        $nr['CF_ReferralType'] = $this->getCustomFieldValue('Referral_Type', $customFields);
        $nr['CF_TerminalPayer'] = $this->getCustomFieldValue('Terminal_Payer', $customFields);
        $nr['CF_WorkForceDevProj'] = $this->getCustomFieldValue('Workforce_Development_Project', $customFields);
        $nr['SchoolDistrict'] = $this->getCustomFieldValue('School_District', $customFields);
        $nr['SchoolName'] = $this->getCustomFieldValue('School_Name', $customFields);

        // Intake coordinator — resolved from caregiver_id (usermaster JOIN)
        $nr['ic_id'] = null;
        $icUserId = $this->constantHelper->getUserIdByBillingProviderId(
            trim((string) ($row['caregiver_id'] ?? ''))
        );
        if ($icUserId !== '') {
            $nr['ic_id'] = $icUserId;
        }

        $ccmUserId = $this->constantHelper->getUserIdByBillingProviderId(
            trim((string) ($row['CaseManagerID'] ?? ''))
        );
        if ($ccmUserId !== '') {
            $nr['ccm_id'] = $ccmUserId;
        }

        $bdUserId = $this->constantHelper->getUserIdByBillingProviderId(
            trim((string) ($row['SP_EMPLOYEE_ID'] ?? ''))
        );
        if ($bdUserId !== '') {
            $nr['bd_id'] = $bdUserId;
        }

        $cccUserId = $this->constantHelper->getUserIdByBillingProviderId(
            trim((string) ($row['SchedulerID'] ?? ''))
        );
        $nr['ccc_id'] = $cccUserId !== '' ? $cccUserId : '';

        return $nr;
    }

    private function getCustomFieldValue(string $key, array $cf): string
    {
        return isset($cf[$key]) ? (string) $cf[$key] : '';
    }

    private function overrideIfNotEmpty(array &$nr, string $field, string $value): void
    {
        if (trim($value) !== '') {
            $nr[$field] = $value;
        }
    }
}
