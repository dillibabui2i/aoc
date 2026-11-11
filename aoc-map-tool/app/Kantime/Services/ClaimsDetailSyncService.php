<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ClaimsDetailRepository;
use App\Kantime\Repository\MariaDB\AocClaimsDetailRepository;

final class ClaimsDetailSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_claimsdetail';

    public function __construct(
        private readonly ClaimsDetailRepository $repository,
        private readonly AocClaimsDetailRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'claim_detail_id';
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
                $this->syncConfig->debugSync && logger()->info('Processing Claims Detail record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = $dbRow[$fieldName];

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByClaimDetailId((int) $lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Claims Detail record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByClaimDetailId((int) $lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Claims Detail record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Claims Detail Items:', [
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
            'claim_detail_id' => $this->toIntOrNull($row['claim_detail_id'] ?? null),
            'agency_id' => $this->toIntOrNull($row['agency_id'] ?? null),
            'claim_id' => $this->toIntOrNull($row['claim_id'] ?? null),
            'schedule_date' => $this->toNullableDateTime($row['schedule_date'] ?? null),
            'units' => $row['units'] ?? null,
            'billed_amount' => $row['billed_amount'] ?? null,
            'schedule_id' => $this->toIntOrNull($row['schedule_id'] ?? null),
            'item_type' => $this->toNullableString($row['item_type'] ?? null),
            'service_code_id' => $this->toIntOrNull($row['service_code_id'] ?? null),
            'billrate' => $row['billrate'] ?? null,
            'is_billrate_hourly' => $this->toBoolInt($row['is_billrate_hourly'] ?? null),
            'is_unitbased_billing' => $this->toBoolInt($row['is_unitbased_billing'] ?? null),
            'contractual_amount' => $row['contractual_amount'] ?? null,
            'contractual_rate' => $row['contractual_rate'] ?? null,
            'child_schedule_id' => $this->toIntOrNull($row['child_schedule_id'] ?? null),
            'billrate_hourly_contractual' => $this->toBoolInt($row['billrate_hourly_contractual'] ?? null),
            'is_contractual_unitbased_billing' => $this->toBoolInt($row['is_contractual_unitbased_billing'] ?? null),
            'is_denied' => $this->toBoolInt($row['is_denied'] ?? null),
            'total_hours' => $row['total_hours'] ?? null,
            'misc_visit_id' => $this->toIntOrNull($row['misc_visit_id'] ?? null),
            'paidamount' => $row['paidamount'] ?? null,
            'requifee_amount' => $row['requifee_amount'] ?? null,
            'is_merged' => $this->toBoolInt($row['is_merged'] ?? null),
            'mergedclaim_detail_id' => $this->toIntOrNull($row['mergedclaim_detail_id'] ?? null),
            'hcpcs' => $this->toNullableString($row['hcpcs'] ?? null),
            'isvalidrecord' => isset($row['isvalidrecord']) ? (int) $row['isvalidrecord'] : 1,
            'is_additional_lineitem' => $this->toBoolInt($row['is_additional_lineitem'] ?? null),
            'is_locked' => $this->toBoolInt($row['is_locked'] ?? null),
            'gl_locked_month' => $this->toNullableDate($row['gl_locked_month'] ?? null),
            'system_date' => $this->toNullableDateTime($row['system_date'] ?? null),
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
