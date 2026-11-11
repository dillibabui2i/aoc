<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\AuthorizationServiceRepository;
use App\Kantime\Repository\MariaDB\AocAuthorizationServiceRepository;

final class AuthorizationServiceSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_authorizations_service';

    public function __construct(
        private readonly AuthorizationServiceRepository $repository,
        private readonly AocAuthorizationServiceRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'auth_service_unique_id';
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
                $this->syncConfig->debugSync && logger()->info('Processing Authorization Service record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = (string) ($dbRow[$fieldName] ?? '');

                if ($lastSuccessfulId === '') {
                    continue;
                }

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByAuthServiceUniqueId($lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Authorization Service record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByAuthServiceUniqueId($lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Authorization Service record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Authorization Service Items:', [
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
            'auth_service_unique_id' => $this->toNullableString($row['auth_service_unique_id'] ?? null),
            'payment_service_id' => $this->toIntOrNull($row['payment_service_id'] ?? null),
            'authorization_id' => $this->toIntOrNull($row['authorization_id'] ?? null),
            'service_id' => $this->toIntOrNull($row['service_id'] ?? null),
            'agency_id' => $this->toIntOrNull($row['agency_id'] ?? null),
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
