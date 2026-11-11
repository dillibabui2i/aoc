<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\AuthorizationsMasterRepository;
use App\Kantime\Repository\MariaDB\AocAuthorizationsMasterRepository;

final class AuthorizationsMasterSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_authorizations';

    public function __construct(
        private readonly AuthorizationsMasterRepository $repository,
        private readonly AocAuthorizationsMasterRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'auth_id';
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
                $this->syncConfig->debugSync && logger()->info('Processing Authorizations Master record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = (int) ($dbRow[$fieldName] ?? 0);

                if ($lastSuccessfulId === 0) {
                    continue;
                }

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByAuthId($lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Authorizations Master record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByAuthId($lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Authorizations Master record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Authorizations Master Items:', [
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
            'auth_id' => $this->toIntOrNull($row['auth_id'] ?? null),
            'payer_id' => $this->toIntOrNull($row['payer_id'] ?? null),
            'PatientID' => $this->toIntOrNull($row['patient_id'] ?? null),
            'aoc_facility_id' => $this->aocRepository->resolveAocFacilityId(
                isset($row['agency_branch_id']) && $row['agency_branch_id'] !== '' ? (int) $row['agency_branch_id'] : null
            ),
            'number' => $this->toNullableString($row['number'] ?? null),
            'start_date' => $this->toNullableDateTime($row['start_date'] ?? null),
            'end_date' => $this->toNullableDateTime($row['end_date'] ?? null),
            'is_visitbased' => $this->toBoolInt($row['is_visitbased'] ?? null),
            'totalunits' => $row['totalunits'] ?:  0,
            'visit_count' => $row['visit_count'] ?: 0,
            'service_code_id' => $this->toIntOrNull($row['service_code_id'] ?? null),
            'discipline_name' => $this->toNullableString($row['discipline_name'] ?? null),
            'unusedunits' => $this->toNullableFloat($row['unusedunits'] ?? null),
            'weekly_limit' => $row['weekly_limit'] ?: 0,
            'monthly_limit' => $row['monthly_limit'] ?: 0,
            'daily_limit' => $row['daily_limit'] ?: 0,
            'is_auto' => $row['is_auto'] ?: 0,
            'agency_id' => $this->toIntOrNull($row['agency_id'] ?? null),
            'sf_created_at' => $this->toNullableDateTime($row['sf_created_at'] ?? null),
            'payer_allocationorder' => $row['payer_allocationorder'] ?: 0,
            'is_hourlybased' => $this->toBoolInt($row['is_hourlybased'] ?? null),
            'auth_ack_by' => $row['auth_ack_by'] ?: 0,
            'auth_ack_date' => $row['auth_ack_date'] ?: '0000-00-00',
            'auth_created_date' => $this->toNullableString($row['auth_created_date'] ?? null),
            'auth_entered_by' => $this->toIntOrNull($row['auth_entered_by'] ?? null),
            'priority_type' => $this->toNullableString($row['priority_type'] ?? null),
            'auth_received_date' => $row['auth_received_date'] ?: '0000-00-00',
            'auth_requested_by' => $row['auth_requested_by'] ?: 0,
            'auth_requested_date' => $row['auth_requested_date'] ?: '0000-00-00',
            'sf_updated_at' => $row['sf_updated_at'] ?? null,
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

    private function toNullableFloat(mixed $value): ?float
    {
        return ($value === null || $value === '') ? null : (float) $value;
    }

    private function toBoolInt(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int)(bool) $value;
    }
}
