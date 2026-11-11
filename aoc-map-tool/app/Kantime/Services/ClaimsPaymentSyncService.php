<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ClaimsPaymentRepository;
use App\Kantime\Repository\MariaDB\AocClaimsPaymentRepository;

final class ClaimsPaymentSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_claimspayment';

    public function __construct(
        private readonly ClaimsPaymentRepository $repository,
        private readonly AocClaimsPaymentRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'claimpayment_id';
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
                $this->syncConfig->debugSync && logger()->info('Processing Claims Payment record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = $dbRow[$fieldName];

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByClaimPaymentId((int) $lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Claims Payment record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByClaimPaymentId((int) $lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Claims Payment record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Claims Payment Items:', [
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
            'claimpayment_id' => $this->toIntOrNull($row['claimpayment_id'] ?? null),
            'claim_id' => $this->toIntOrNull($row['claim_id'] ?? null),
            'agency_id' => $this->toIntOrNull($row['agency_id'] ?? null),
            'payment_id' => $this->toIntOrNull($row['payment_id'] ?? null),
            'paid_amt' => $row['paid_amt'] ?? null,
            'notes' => $this->toNullableString($row['notes'] ?? null),
            'transaction_date' => $this->toNullableDate($row['transaction_date'] ?? null),
            'creator_id' => $this->toIntOrNull($row['creator_id'] ?? null),
            'posting_date' => $this->toNullableDateTime($row['posting_date'] ?? null),
            'status' => $this->toNullableString($row['status'] ?? null),
            'is_locked' => $this->toBoolInt($row['is_locked'] ?? null),
            'gl_locked_month' => $this->toNullableDate($row['gl_locked_month'] ?? null),
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
