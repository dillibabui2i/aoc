<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\BillingInquiryNotesRepository;
use App\Kantime\Repository\MariaDB\AocBillingInquiryNotesRepository;

final class BillingInquiryNotesSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_billinginquerynotes';

    public function __construct(
        private readonly BillingInquiryNotesRepository $repository,
        private readonly AocBillingInquiryNotesRepository $aocRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(): void
    {
        $syncType = self::SYNC_TYPE;
        $fieldName = 'note_id';
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
                $this->syncConfig->debugSync && logger()->info('Processing Billing Inquiry Notes record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = $dbRow[$fieldName];

                if (!($row['isvalidrecord'] ?? true)) {
                    $this->aocRepository->deleteByNoteId((int) $lastSuccessfulId);
                    $this->syncConfig->debugSync && logger()->info('Deleted Billing Inquiry Notes record: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByNoteId((int) $lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Billing Inquiry Notes record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Billing Inquiry Notes Items:', [
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
            'note_id' => $this->toIntOrNull($row['note_id'] ?? null),
            'agency_id' => $this->toIntOrNull($row['agency_id'] ?? null),
            'PatientID' => $this->toIntOrNull($row['PatientID'] ?? null),
            'payer_id' => $this->toIntOrNull($row['payer_id'] ?? null),
            'start_date' => $this->toNullableDate($row['start_date'] ?? null),
            'end_date' => $this->toNullableDate($row['end_date'] ?? null),
            'created_date' => $this->toNullableDateTime($row['created_date'] ?? null),
            'notes' => $this->toNullableString($row['notes'] ?? null),
            'addedby_user_id' => $this->toIntOrNull($row['addedby_user_id'] ?? null),
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
}
