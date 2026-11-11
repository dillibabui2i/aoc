<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ClientMasterAdditionalRepository;
use App\Kantime\Repository\MariaDB\AocClientMasterAdditionalRepository;

final class ClientMasterAdditionalSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_client_master_additional';

    public function __construct(
        private readonly ClientMasterAdditionalRepository $repository,
        private readonly AocClientMasterAdditionalRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'client_additional_unique_id';
        $batchStatus = $this->sync->startBatch($syncType);

        if ($batchStatus === -1) {
            return;
        }

        $batchInfo = $this->sync->get_batch_information($syncType);
        $lastUpdatedDate = $batchInfo['last_updated_date'] ? date_create($batchInfo['last_updated_date'])->format('Y-m-d H:i:s') : '';
        $lastUpdatedId = $batchInfo['last_updated_id'] ?: '';
        $limit = $batchInfo['batch_size'];
        $combinedString = str_replace("'", "''", $lastUpdatedDate . $lastUpdatedId);
        $lastSuccessfulDate = $lastUpdatedDate;
        $lastSuccessfulId = $lastUpdatedId;

        try {
            foreach ($this->repository->getRecords($limit, $combinedString) as $row) {
                $this->syncConfig->debugSync && logger()->info('Processing Client Master Additional record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = (string) ($dbRow[$fieldName] ?? '');

                if ($lastSuccessfulId === '') {
                    continue;
                }

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByClientAdditionalUniqueId($lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Client Master Additional record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByClientAdditionalUniqueId($lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Client Master Additional record: ' . $lastSuccessfulId);
            }

            $this->aocRepository->deleteOrphanedNullClientIds();
            $this->syncConfig->debugSync && logger()->info('Client Master Additional post-run cleanup completed.');
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Client Master Additional Items:', [
                'lastUpdatedDate' => $lastSuccessfulDate,
                'lastUpdatedId' => $lastSuccessfulId,
            ]);
            $this->sync->update_last_updated($syncType, date_create($lastSuccessfulDate)->format('Y-m-d H:i:s'), $lastSuccessfulId);
            $this->sync->stopBatch($syncType);
        }
    }

    private function toDbRow(array $row): array
    {
        return [
            'client_additional_unique_id' => $this->toNullableString($row['client_additional_unique_id'] ?? null),
            'agency_id' => $this->toIntOrNull($row['agency_id'] ?? null),
            'client_client_id' => $this->toIntOrNull($row['client_client_id'] ?? null),
            'intake_id' => $this->toIntOrNull($row['intake_id'] ?? null),
            'daas_last_modified_timestamp' => $this->toNullableDateTime($row['daas_last_modified_timestamp'] ?? null),
            'isvalidrecord' => isset($row['isvalidrecord']) ? (int) $row['isvalidrecord'] : 1,
            'etl_updated_timestamp' => $this->toNullableDateTime($row['etl_updated_timestamp'] ?? null),
            'first_episode_id' => $this->toIntOrNull($row['first_episode_id'] ?? null),
            'last_episode_id' => $this->toIntOrNull($row['last_episode_id'] ?? null),
            'rn_telnumber' => $this->toNullableString($row['rn_telnumber'] ?? null),
            'pt_telnumber' => $this->toNullableString($row['pt_telnumber'] ?? null),
            'ot_telnumber' => $this->toNullableString($row['ot_telnumber'] ?? null),
            'st_telnumber' => $this->toNullableString($row['st_telnumber'] ?? null),
            'msw_telnumber' => $this->toNullableString($row['msw_telnumber'] ?? null),
            'other_diagnosis' => $this->toNullableString($row['other_diagnosis'] ?? null),
            'email' => $this->toNullableString($row['email'] ?? null),
            'hospital_phone' => $this->toNullableString($row['hospital_phone'] ?? null),
            'insurance_group_no' => $this->toNullableString($row['insurance_group_no'] ?? null),
            'ssn' => $this->toNullableString($row['ssn'] ?? null),
            'billing_acuity_effperiod' => $this->toNullableString($row['billing_acuity_effperiod'] ?? null),
            'last_schedule_date' => $this->toNullableDateTime($row['last_schedule_date'] ?? null),
            'last_recert_date' => $this->toNullableDateTime($row['last_recert_date'] ?? null),
            'days_last_recert' => $this->toNullableString($row['days_last_recert'] ?? null),
            'last_supervisory_visit' => $this->toNullableDateTime($row['last_supervisory_visit'] ?? null),
            'poc_medication' => $this->toNullableString($row['poc_medication'] ?? null),
            'poc_dme' => $this->toNullableString($row['poc_dme'] ?? null),
            'poc_supplies' => $this->toNullableString($row['poc_supplies'] ?? null),
            'poc_safetymeasures' => $this->toNullableString($row['poc_safetymeasures'] ?? null),
            'poc_nutrition_requirment' => $this->toNullableString($row['poc_nutrition_requirment'] ?? null),
            'poc_food_allergies' => $this->toNullableString($row['poc_food_allergies'] ?? null),
            'poc_med_allergies' => $this->toNullableString($row['poc_med_allergies'] ?? null),
            'poc_environment_allergies' => $this->toNullableString($row['poc_environment_allergies'] ?? null),
            'poc_functional_limitations' => $this->toNullableString($row['poc_functional_limitations'] ?? null),
            'poc_activities_permitted' => $this->toNullableString($row['poc_activities_permitted'] ?? null),
            'poc_mental_status' => $this->toNullableString($row['poc_mental_status'] ?? null),
            'poc_prognosis' => $this->toNullableString($row['poc_prognosis'] ?? null),
            'poc_verbal_soc_date' => $this->toNullableDateTime($row['poc_verbal_soc_date'] ?? null),
            'poc_hha_received_date' => $this->toNullableDateTime($row['poc_hha_received_date'] ?? null),
            'poc_certification' => $this->toNullableString($row['poc_certification'] ?? null),
            'cma_agency_name' => $this->toNullableString($row['cma_agency_name'] ?? null),
            'representative_avail' => $this->toNullableString($row['representative_avail'] ?? null),
            'portal_avail' => $this->toNullableString($row['portal_avail'] ?? null),
            'attphy_office_fax' => $this->toNullableString($row['attphy_office_fax'] ?? null),
            'attphy_office_phone' => $this->toNullableString($row['attphy_office_phone'] ?? null),
            'doc_received' => $this->toNullableString($row['doc_received'] ?? null),
            'intake_coordinator_lastname' => $this->toNullableString($row['intake_coordinator_lastname'] ?? null),
            'intake_coordinator_firstname' => $this->toNullableString($row['intake_coordinator_firstname'] ?? null),
            'refphy_office_fax' => $this->toNullableString($row['refphy_office_fax'] ?? null),
            'refphy_office_phone' => $this->toNullableString($row['refphy_office_phone'] ?? null),
            'days_on_service' => $this->toIntOrNull($row['days_on_service'] ?? null),
            'alert' => $this->toNullableString($row['alert'] ?? null),
            'payroll_export_id' => $this->toNullableString($row['payroll_export_id'] ?? null),
            'billing_address_line1' => $this->toNullableString($row['billing_address_line1'] ?? null),
            'billing_address_line2' => $this->toNullableString($row['billing_address_line2'] ?? null),
            'billing_address_city' => $this->toNullableString($row['billing_address_city'] ?? null),
            'billing_address_state' => $this->toNullableString($row['billing_address_state'] ?? null),
            'billing_address_zip' => $this->toNullableString($row['billing_address_zip'] ?? null),
            'gurantor_id' => $this->toNullableString($row['gurantor_id'] ?? null),
            'gurantor_name' => $this->toNullableString($row['gurantor_name'] ?? null),
            'gurantor_relation' => $this->toNullableString($row['gurantor_relation'] ?? null),
            'created_by' => 1,
        ];
    }

    private function toNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function toNullableDateTime(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return date_create((string) $value)?->format('Y-m-d H:i:s');
    }

    private function toIntOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }
}
