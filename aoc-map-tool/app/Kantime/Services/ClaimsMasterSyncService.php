<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ClaimsMasterRepository;
use App\Kantime\Repository\MariaDB\AocClaimsMasterRepository;

final class ClaimsMasterSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_claims_master';

    public function __construct(
        private readonly ClaimsMasterRepository $repository,
        private readonly AocClaimsMasterRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'claim_id';
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
                $this->syncConfig->debugSync && logger()->info('Processing Claims Master record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = (int) ($dbRow[$fieldName] ?? 0);

                if ($lastSuccessfulId === 0) {
                    continue;
                }

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByClaimId($lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Claims Master record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByClaimId($lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Claims Master record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Claims Master Items:', [
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
            'agency_branch_id' => $this->toIntOrNull($row['agency_branch_id'] ?? null),
            'claim_id' => $this->toIntOrNull($row['claim_id'] ?? null),
            'client_id' => $this->toIntOrNull($row['client_id'] ?? null),
            'payersource_id' => $this->toIntOrNull($row['payersource_id'] ?? null),
            'episode_id' => $this->toIntOrNull($row['episode_id'] ?? null),
            'type' => $this->toNullableString($row['type'] ?? null),
            'invoice_date' => $this->toNullableDateTime($row['invoice_date'] ?? null),
            'invoice_number' => $this->toNullableString($row['invoice_number'] ?? null),
            'start_date' => $this->toNullableDateTime($row['start_date'] ?? null),
            'end_date' => $this->toNullableDateTime($row['end_date'] ?? null),
            'totalamount' => $row['totalamount'] ?? null,
            'contractual_amount' => $row['contractual_amount'] ?? 0,
            'balance' => $row['balance'] ?? 0,
            'balance_contractual' => $row['balance_contractual'] ?? 0,
            'is_cancelled' => $this->toBoolInt($row['is_cancelled'] ?? 0),
            'is_fully_paid' => $this->toBoolInt($row['is_fully_paid'] ?? 0),
            'is_partially_paid' => $this->toBoolInt($row['is_partially_paid'] ?? 0),
            'is_appealed' => $this->toBoolInt($row['is_appealed'] ?? 0),
            'is_denied' => $this->toBoolInt($row['is_denied'] ?? 0),
            'contractual_adjustments' => $row['contractual_adjustments'] ?? 0.0,
            'other_adjustments' => $row['other_adjustments'] ?? 0.0,
            'paidamount' => $row['paidamount'] ?? 0,
            'is_secondary' => $this->toBoolInt($row['is_secondary'] ?? 0),
            'is_tertiary' => $this->toBoolInt($row['is_tertiary'] ?? 0),
            'is_lupa' => $this->toBoolInt($row['is_lupa'] ?? 0),
            'isoutlier' => $this->toBoolInt($row['isoutlier'] ?? 0),
            'is_pep' => $this->toBoolInt($row['is_pep'] ?? 0),
            'is_transferred_copay' => $this->toBoolInt($row['is_transferred_copay'] ?? 0),
            'is_service_copay' => $this->toBoolInt($row['is_service_copay'] ?? 0),
            'is_manual' => $this->toBoolInt($row['is_manual'] ?? 0),
            'initial_hhrg' => $row['initial_hhrg'] ?? 0.0,
            'final_hhrg' => $row['final_hhrg'] ?? 0.0,
            'hhrg_goal' => $row['hhrg_goal'] ?? 0.0,
            'status' => $this->toNullableString($row['status'] ?? null),
            'paid_date' => $this->toNullableDateTime($row['paid_date'] ?? null),
            'is_edicreated' => $this->toBoolInt($row['is_edicreated'] ?? null),
            'edi_createddate' => $this->toNullableDateTime($row['edi_createddate'] ?? null),
            'edi_createdby_user_id' => $this->toNullableString($row['edi_createdby_user_id'] ?? null),
            'edi_file_id' => $this->toIntOrNull($row['edi_file_id'] ?? null),
            'createdby_user_id' => $this->toNullableString($row['createdby_user_id'] ?? null),
            'cancellation_reason' => $this->toNullableString($row['cancellation_reason'] ?? null),
            'is_billed' => $this->toBoolInt($row['is_billed'] ?? 0),
            'invoice_status' => $this->toNullableString($row['invoice_status'] ?? null),
            'parent_claimid' => $this->toIntOrNull($row['parent_claimid'] ?? null),
            'primary_claimid' => $this->toIntOrNull($row['primary_claimid'] ?? null),
            'isvalidrecord' => isset($row['isvalidrecord']) ? (int) $row['isvalidrecord'] : 1,
            'addedon' => $this->toNullableDateTime($row['addedon'] ?? null),
            'transaction_date' => $this->toNullableDate($row['transaction_date'] ?? null),
            'billed_date' => $this->toNullableDateTime($row['billed_date'] ?? null),
            'is_client_pay' => $this->toBoolInt($row['is_client_pay'] ?? 0),
            'is_locked' => $this->toBoolInt($row['is_locked'] ?? 0),
            'gl_locked_month' => $this->toNullableDate($row['gl_locked_month'] ?? null),
            'gl_locked_date' => $this->toNullableDateTime($row['gl_locked_date'] ?? null),
            'remit_remark' => $this->toNullableString($row['remit_remark'] ?? null),
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
