<?php

namespace App\Kantime\Services;

use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\JournalNotesRepository;
use App\Kantime\Repository\MariaDB\AocJournalNotesRepository;

final class JournalNotesSyncService implements KantimeSyncServiceInterface
{
    public const SYNC_TYPE = 'daas_journalnotes';

    public function __construct(
        private readonly JournalNotesRepository $repository,
        private readonly AocJournalNotesRepository $aocRepository,
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
                $this->syncConfig->debugSync && logger()->info('Processing Journal Notes record: ', $row);
                $dbRow = $this->toDbRow($row);
                $lastSuccessfulId = $dbRow[$fieldName];

                if (!($row['isvalidrecord'] ?? true)) {
                    // $this->aocRepository->deleteByNoteId((int) $lastSuccessfulId);
                    $this->aocRepository->update((int) $lastSuccessfulId, ['is_deleted' => 1]);
                    $this->syncConfig->debugSync && logger()->info('Marked Journal Notes record as deleted: ' . $lastSuccessfulId);
                    $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;
                    continue;
                }

                $this->aocRepository->upsertByNoteId((int) $lastSuccessfulId, $dbRow);
                $lastSuccessfulDate = $row['lastmodified'] ?? $lastSuccessfulDate;

                $this->syncConfig->debugSync && logger()->info('Upserted Journal Notes record: ' . $lastSuccessfulId);
            }
        } catch (\Throwable $e) {
            throw new KantimeSyncException($e->getMessage());
        } finally {
            logger()->info('Successfully Journal Notes Items:', [
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
            'PatientID' => $this->toIntOrNull($row['PatientID'] ?? null),
            'context_type' => $this->toNullableString($row['context_type'] ?? null),
            'context_id' => $this->toIntOrNull($row['context_id'] ?? null),
            'added_date' => $this->toNullableDateTime($row['added_date'] ?? null),
            'addedby_user_id' => $this->toIntOrNull($row['addedby_user_id'] ?? null),
            'modified_date' => $this->toNullableDateTime($row['modified_date'] ?? null),
            'modifiedby_user_id' => $this->toIntOrNull($row['modifiedby_user_id'] ?? null),
            'category' => $this->toNullableString($row['category'] ?? null),
            'notes' => $this->toNullableString($row['notes'] ?? null),
            'lob_id' => $this->toIntOrNull($row['lob_id'] ?? null),
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
