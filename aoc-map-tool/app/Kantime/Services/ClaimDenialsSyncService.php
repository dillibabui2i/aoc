<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ClaimDenialsRepository;
use App\Kantime\Repository\MariaDB\AocClaimDenialsRepository;

final class ClaimDenialsSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_claimdenials';

    public function __construct(
        private readonly ClaimDenialsRepository $repository,
        private readonly AocClaimDenialsRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'denial_id';
        $batchStatus = $this->sync->startBatch($syncType);

        if ($batchStatus === -1) {
            return;
        }

        $batchInfo = $this->sync->get_batch_information($syncType);
        $lastUpdatedDate = $batchInfo['last_updated_date'] ? date_create($batchInfo['last_updated_date'])->format('Y-m-d H:i:s') : '';
        $lastUpdatedId = $batchInfo['last_updated_id'] ?: 0;
        $limit = $batchInfo['batch_size'];
        $combinedString = str_replace("'", "''", $lastUpdatedDate . $lastUpdatedId);
        $lastSuccessfulDate = $lastUpdatedDate;
        $lastSuccessfulId = $lastUpdatedId;

        try {
            foreach ($this->repository->getRecords($limit, $combinedString) as $row) {
                $this->syncConfig->debugSync && logger()->info('Processing Claim Denials record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = $dbRow[$fieldName];

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByDenialId((int) $lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Claim Denials record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByDenialId((int) $lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Claim Denials record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Claim Denials Items:', [
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
            'denial_id' => $this->toIntOrNull($row['denial_id'] ?? null),
            'client_id' => $this->toIntOrNull($row['client_id'] ?? null),
            'claim_id' => $this->toIntOrNull($row['claim_id'] ?? null),
            'date' => $this->toNullableDate($row['date'] ?? null),
            'notes' => $this->toNullableString($row['notes'] ?? null),
            'reason' => $this->toNullableString($row['reason'] ?? null),
            'status' => $this->toNullableString($row['status'] ?? null),
            'paid_date' => $this->toNullableDateTime($row['paid_date'] ?? null),
            'paidby_user_id' => $this->toIntOrNull($row['paidby_user_id'] ?? null),
            'createdby_user_id' => $this->toIntOrNull($row['createdby_user_id'] ?? null),
            'rejection_date' => $this->toNullableDateTime($row['rejection_date'] ?? null),
            'eob_date' => $this->toNullableDateTime($row['eob_date'] ?? null),
            'lockedmonth_date' => $this->toNullableDateTime($row['lockedmonth_date'] ?? null),
            'baddebt_notes' => $this->toNullableString($row['baddebt_notes'] ?? null),
            'transaction_date' => $this->toNullableDate($row['transaction_date'] ?? null),
            'daas_last_modified_timestamp' => $this->toNullableDateTime($row['daas_last_modified_timestamp'] ?? null),
            'isvalidrecord' => isset($row['isvalidrecord']) ? (int) $row['isvalidrecord'] : 1,
            'system_date' => $this->toNullableDateTime($row['system_date'] ?? null),
            'is_locked' => $this->toBoolInt($row['is_locked'] ?? null),
            'gl_locked_date' => $this->toNullableDateTime($row['gl_locked_date'] ?? null),
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

    private function toNullableDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        return date_create((string) $value)?->format('Y-m-d');
    }

    private function toIntOrNull(mixed $value): ?int
    {
        return ($value === null || $value === '') ? null : (int) $value;
    }

    private function toBoolInt(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        return (int) (bool) $value;
    }
}
