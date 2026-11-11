<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ClientMedicationMasterRepository;
use App\Kantime\Repository\MariaDB\AocClientMedicationMasterRepository;

final class ClientMedicationMasterSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_client_medication_master';

    public function __construct(
        private readonly ClientMedicationMasterRepository $repository,
        private readonly AocClientMedicationMasterRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'client_medication_unique_id';
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
                $this->syncConfig->debugSync && logger()->info('Processing Client Medication Master record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = (string) ($dbRow[$fieldName] ?? '');

                if ($lastSuccessfulId === '') {
                    continue;
                }

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByClientMedicationUniqueId($lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Client Medication Master record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByClientMedicationUniqueId($lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Client Medication Master record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Client Medication Master Items:', [
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
            'client_medication_unique_id' => $this->toNullableString($row['client_medication_unique_id'] ?? null),
            'agency_id' => $this->toIntOrNull($row['agency_id'] ?? null),
            'client_id' => $this->toIntOrNull($row['client_id'] ?? null),
            'medication_id' => $this->toIntOrNull($row['medication_id'] ?? null),
            'episodeid' => $this->toIntOrNull($row['episodeid'] ?? null),
            'level' => $this->toNullableString($row['level'] ?? null),
            'name' => $this->toNullableString($row['name'] ?? null),
            'dosage' => $this->toNullableString($row['dosage'] ?? null),
            'route' => $this->toNullableString($row['route'] ?? null),
            'frequency' => $this->toNullableString($row['frequency'] ?? null),
            'medcode' => $this->toNullableString($row['medcode'] ?? null),
            'purpose' => $this->toNullableString($row['purpose'] ?? null),
            'start_date' => $this->toNullableDateTime($row['start_date'] ?? null),
            'planned_days' => $this->toNullableString($row['planned_days'] ?? null),
            'is_prn' => $this->toBoolInt($row['is_prn'] ?? null),
            'dc_date' => $this->toNullableDateTime($row['dc_date'] ?? null),
            'dc_time' => $this->toNullableString($row['dc_time'] ?? null),
            'frequency_days' => $this->toIntOrNull($row['frequency_days'] ?? null),
            'frequency_type' => $this->toNullableString($row['frequency_type'] ?? null),
            'frequency_dayson' => $this->toIntOrNull($row['frequency_dayson'] ?? null),
            'frequency_daysoff' => $this->toIntOrNull($row['frequency_daysoff'] ?? null),
            'classification' => $this->toNullableString($row['classification'] ?? null),
            'med_profile' => $this->toNullableString($row['med_profile'] ?? null),
            'is_patientcaregiver_manages' => $this->toBoolInt($row['is_patientcaregiver_manages'] ?? null),
            'is_antibiotic' => $this->toBoolInt($row['is_antibiotic'] ?? null),
            'is_high_risk' => $this->toBoolInt($row['is_high_risk'] ?? null),
            'is_dc_ontime' => $this->toBoolInt($row['is_dc_ontime'] ?? null),
            'is_hold' => $this->toBoolInt($row['is_hold'] ?? null),
            'is_current_hold' => $this->toBoolInt($row['is_current_hold'] ?? null),
            'classification_type' => $this->toNullableString($row['classification_type'] ?? null),
            'daas_last_modified_timestamp' => $this->toNullableDateTime($row['daas_last_modified_timestamp'] ?? null),
            'isvalidrecord' => isset($row['isvalidrecord']) ? (int) $row['isvalidrecord'] : 1,
            'etl_updated_timestamp' => $this->toNullableDateTime($row['etl_updated_timestamp'] ?? null),
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

    private function toBoolInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int)(bool) $value;
    }
}
