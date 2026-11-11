<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ClientPhysicianMasterRepository;
use App\Kantime\Repository\MariaDB\AocClientPhysicianMasterRepository;

final class ClientPhysicianMasterSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_client_physician_master';

    public function __construct(
        private readonly ClientPhysicianMasterRepository $repository,
        private readonly AocClientPhysicianMasterRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'internal_id';
        $batchStatus = $this->sync->startBatch($syncType);

        if ($batchStatus === -1) {
            return;
        }

        $batchInfo = $this->sync->get_batch_information($syncType);
        $lastUpdatedDate = $batchInfo['last_updated_date'] ? date_create($batchInfo['last_updated_date'])->format('Y-m-d H:i:s') : '';
        $lastUpdatedId = $batchInfo['last_updated_id'] ? (int) $batchInfo['last_updated_id'] : 0;
        $limit = $batchInfo['batch_size'];
        $combinedString = str_replace("'", "''", $lastUpdatedDate . $lastUpdatedId);
        $lastSuccessfulDate = $lastUpdatedDate;
        $lastSuccessfulId = $lastUpdatedId;

        try {
            foreach ($this->repository->getRecords($limit, $combinedString) as $row) {
                $this->syncConfig->debugSync && logger()->info('Processing Client Physician Master record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = (int) ($dbRow[$fieldName] ?? 0);

                if ($lastSuccessfulId === 0) {
                    continue;
                }

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByInternalId($lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Client Physician Master record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByInternalId($lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Client Physician Master record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Client Physician Master Items:', [
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
            'agency_id' => $this->toIntOrNull($row['agency_id'] ?? null),
            'client_id' => $this->toIntOrNull($row['client_id'] ?? null),
            'internal_id' => $this->toIntOrNull($row['internal_id'] ?? null),
            'physician_id' => $this->toIntOrNull($row['physician_id'] ?? null),
            'physician_identifier' => $this->toNullableString($row['physician_identifier'] ?? null),
            'lastseen_date' => $this->toNullableDateTime($row['lastseen_date'] ?? null),
            'last_visit_type' => $this->toNullableString($row['last_visit_type'] ?? null),
            'specialization' => $this->toNullableString($row['specialization'] ?? null),
            'phone' => $this->toNullableString($row['phone'] ?? null),
            'fax' => $this->toNullableString($row['fax'] ?? null),
            'office_id' => $this->toIntOrNull($row['office_id'] ?? null),
            'office_phonenumber' => $this->toNullableString($row['office_phonenumber'] ?? null),
            'office_faxnumber' => $this->toNullableString($row['office_faxnumber'] ?? null),
            'office_name' => $this->toNullableString($row['office_name'] ?? null),
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
}
