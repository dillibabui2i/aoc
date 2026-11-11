<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ClaimsAdjustmentRepository;
use App\Kantime\Repository\MariaDB\AocClaimsAdjustmentRepository;

final class ClaimsAdjustmentSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_claimsadjustment';

    public function __construct(
        private readonly ClaimsAdjustmentRepository $repository,
        private readonly AocClaimsAdjustmentRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'adjustment_id';
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
                $this->syncConfig->debugSync && logger()->info('Processing Claims Adjustment record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = $dbRow[$fieldName];

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByAdjustmentId((int) $lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Claims Adjustment record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByAdjustmentId((int) $lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Claims Adjustment record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Claims Adjustment Items:', [
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
            'adjustment_id' => $this->toIntOrNull($row['adjustment_id'] ?? null),
            'agency_id' => $this->toIntOrNull($row['agency_id'] ?? null),
            'claim_id' => $this->toIntOrNull($row['claim_id'] ?? null),
            'claim_detail_id' => $this->toIntOrNull($row['claim_detail_id'] ?? null),
            'amount' => $row['amount'] ?? null,
            'notes' => $this->toNullableString($row['notes'] ?? null),
            'type' => $this->toNullableString($row['type'] ?? null),
            'lockedmonth_date' => $this->toNullableDateTime($row['lockedmonth_date'] ?? null),
            'transaction_date' => $this->toNullableDateTime($row['transaction_date'] ?? null),
            'adjustment_date' => $this->toNullableDateTime($row['adjustment_date'] ?? null),
            'account_type' => $this->toNullableString($row['account_type'] ?? null),
            'createdon' => $this->toNullableDateTime($row['createdon'] ?? null),
            'is_locked' => $this->toBoolInt($row['is_locked'] ?? null),
            'gl_locked_date' => $this->toNullableDateTime($row['gl_locked_date'] ?? null),
            'isvalidrecord' => isset($row['isvalidrecord']) ? (int) $row['isvalidrecord'] : 1,
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
        if ($value === null) {
            return null;
        }

        if (is_bool($value)) {
            return (int) $value;
        }

        return (int) (bool) $value;
    }
}
