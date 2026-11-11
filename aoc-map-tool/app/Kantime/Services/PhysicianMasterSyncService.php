<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\PhysicianMasterRepository;
use App\Kantime\Repository\MariaDB\AocPhysicianMasterRepository;

final class PhysicianMasterSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_physician_master';

    public function __construct(
        private readonly PhysicianMasterRepository $repository,
        private readonly AocPhysicianMasterRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'physician_id';
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
                $this->syncConfig->debugSync && logger()->info('Processing Physician Master record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = (int) ($dbRow[$fieldName] ?? 0);

                if ($lastSuccessfulId === 0) {
                    continue;
                }

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByPhysicianId($lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Physician Master record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByPhysicianId($lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Physician Master record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Physician Master Items:', [
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
            'physician_id' => $this->toIntOrNull($row['physician_id'] ?? null),
            'sales_person_id' => $this->toIntOrNull($row['sales_person_id'] ?? null),
            'first_name' => $this->toNullableString($row['first_name'] ?? null),
            'last_name' => $this->toNullableString($row['last_name'] ?? null),
            'middle_name' => $this->toNullableString($row['middle_name'] ?? null),
            'status' => $this->toNullableString($row['status'] ?? null),
            'daas_last_modified_timestamp' => $this->toNullableDateTime($row['daas_last_modified_timestamp'] ?? null),
            'npi' => $this->toNullableString($row['npi'] ?? null),
            'title' => $this->toNullableString($row['title'] ?? null),
            'external_id' => $this->toNullableString($row['external_id'] ?? null),
            'licenseno' => $this->toNullableString($row['licenseno'] ?? null),
            'licenseexpirydate' => $this->toNullableDateTime($row['licenseexpirydate'] ?? null),
            'upin' => $this->toNullableString($row['upin'] ?? null),
            'tpi' => $this->toNullableString($row['tpi'] ?? null),
            'taxonomycode' => $this->toNullableString($row['taxonomycode'] ?? null),
            'pecosenrolled' => $this->toNullableString($row['pecosenrolled'] ?? null),
            'address_1' => $this->toNullableString($row['address_1'] ?? null),
            'address_2' => $this->toNullableString($row['address_2'] ?? null),
            'zip' => $this->toNullableString($row['zip'] ?? null),
            'city' => $this->toNullableString($row['city'] ?? null),
            'state' => $this->toNullableString($row['state'] ?? null),
            'mobile' => $this->toNullableString($row['mobile'] ?? null),
            'phone' => $this->toNullableString($row['phone'] ?? null),
            'email' => $this->toNullableString($row['email'] ?? null),
            'fax' => $this->toNullableString($row['fax'] ?? null),
            'specialization' => $this->toNullableString($row['specialization'] ?? null),
            'category' => $this->toNullableString($row['category'] ?? null),
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
