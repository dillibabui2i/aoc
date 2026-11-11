<?php

namespace App\Kantime\Services;

use App\Enums\ScheduleMasterResyncStatusEnum;
use App\Helpers\Sync;
use App\Kantime\Common\KantimeSyncConfig;
use App\Kantime\Domain\ScheduleMasterItem;
use App\Kantime\Exception\KantimeSyncException;
use App\Kantime\Repository\BigQuery\ScheduleMasterRepository;
use App\Kantime\Repository\KantimeRepositoryInterface;
use App\Kantime\Repository\MariaDB\ScheduleMasterRepository as AocScheduleMasterRepository;
use Illuminate\Database\DetectsConcurrencyErrors;

class ScheduleMasterSyncService implements KantimeSyncServiceInterface
{
    use DetectsConcurrencyErrors;

    private const LIMIT = 5000;

    // process_settings row of the regular (every 4 min) BigQuery sync, see sync()
    private const RESYNC_LOCK = 'daas_schedules';

    private const RESYNC_CHUNK_SIZE = 1000;

    // Columns reported by resyncByIds() as changed (they drive the billing buckets)
    private const RESYNC_TRACKED_COLUMNS = [
        'IsBilled',
        'IsBillable',
        'ScheduleStatusID',
        'EChartStatusID',
        'HasChild',
        'IsChild',
    ];

    /**
     * @param ScheduleMasterRepository $scheduleMasterRepository
     * @param AocScheduleMasterRepository $aocScheduleMasterRepository
     * @param Sync $sync
     */
    public function __construct(
        private readonly KantimeRepositoryInterface $scheduleMasterRepository,
        private readonly AocScheduleMasterRepository $aocScheduleMasterRepository,
        private readonly Sync $sync,
        private readonly KantimeSyncConfig $syncConfig
    ) {
    }

    public function sync(string $mode = 'schedules'): void
    {
        $syncType = match ($mode) {
            'schedulescu' => 'daas_schedules_cu',
            'schedulemidnightcu' => 'daas_schedules_midnight_cu',
            default => 'daas_schedules',
        };

        if ($mode === 'schedulemidnightcu') {
            $now = \Carbon\Carbon::now();
            $midnight30 = $now->copy()->setTime(0, 30, 0);
            $midnight35 = $now->copy()->setTime(0, 35, 0);

            if ($now->between($midnight30, $midnight35, true)) {
                $previousDay = $now->copy()->subDay()->format('Y-m-d');
                $this->sync->update_last_updated($syncType, $previousDay . ' 00:00:00.001', 'a000');
            }
        }

        $batchStatus = $this->sync->startBatch($syncType);
        if ($batchStatus == -1) {
            return;
        }

        $batchInfo = $this->sync->get_batch_information($syncType);

        $limit = (int) ($batchInfo['batch_size'] ?? self::LIMIT);
        $lastUpdatedDate = $batchInfo['last_updated_date'] ? date_create($batchInfo['last_updated_date'])->format('Y-m-d H:i:s.u') : '';
        $lastUpdatedId = (string) ($batchInfo['last_updated_id'] ?? '0');
        if ($lastUpdatedId === '') {
            $lastUpdatedId = '0';
        }

        $processed = 0;
        $failedScheduleIds = [];
        $concurrencyError = null;
        // Once a record fails, the persisted cursor stops advancing for the rest of this
        // run, so that record (and anything after it) is retried on the next run instead
        // of being skipped forever once a later item in the same batch succeeds (PD-5516).
        $cursorStalled = false;

        try {
            foreach ($this->scheduleMasterRepository->getRecords($limit, $lastUpdatedId, $lastUpdatedDate) as $item) {
                try {
                    $this->processOne($item);
                    $processed++;

                    if (!$cursorStalled) {
                        $lastUpdatedDate = $item->etl_updated_timestamp;
                        $lastUpdatedId = (string) $item->CGTaskID;
                    }
                } catch (\Throwable $e) {
                    $id = $item instanceof ScheduleMasterItem ? $item->CGTaskID ?? null : null;

                    // On a DB lock (deadlock / lock wait timeout) the table may stay locked
                    // for a while, so an immediate in-process retry is pointless. Stop the
                    // batch WITHOUT advancing the cursor past this record and rethrow after
                    // cleanup: the job then fails and is retried by the queue after its
                    // backoff delay (and the scheduled run is a further backstop), by which
                    // time the lock should be gone. This record is re-read on the next run.
                    if ($this->causedByConcurrencyError($e)) {
                        $concurrencyError = $e;
                        logger()->warning('ScheduleMaster sync: concurrency error, retrying job after backoff', [
                            'syncType' => $syncType,
                            'ScheduleId' => $id,
                            'exception' => $e->getMessage(),
                        ]);
                        break;
                    }

                    // Non-lock errors: freeze the cursor and keep going so one bad row
                    // can't stall the whole sync, but also can't be skipped forever. It is
                    // logged for a targeted re-sync/backfill.
                    $cursorStalled = true;
                    $failedScheduleIds[] = $id;
                    logger()->error('ScheduleMaster sync: failed processing item', [
                        'syncType' => $syncType,
                        'ScheduleId' => $id,
                        'exception' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            logger()->error(
                'ScheduleMaster sync: Job Service failed',
                [
                    'lastUpdatedDate' => $lastUpdatedDate,
                    'lastUpdatedId' => $lastUpdatedId,
                    'exception' => $e->getMessage(),
                ]
            );
        } finally {
            logger()->info('ScheduleMaster sync batch finished', [
                'syncType' => $syncType,
                'processedItems' => $processed,
                'failedScheduleIds' => $failedScheduleIds,
                'cursorStalled' => $cursorStalled,
                'lastUpdatedDate' => $lastUpdatedDate,
                'lastUpdatedId' => $lastUpdatedId,
            ]);
            $this->sync->update_last_updated($syncType, date_create($lastUpdatedDate)->format('Y-m-d H:i:s.u'), $lastUpdatedId);
            $this->sync->stopBatch($syncType);
        }

        // Surface the lock error after the batch is cleanly closed and the cursor is
        // persisted at the last successful record, so the queue retries the job.
        if ($concurrencyError !== null) {
            throw $concurrencyError;
        }
    }

    /**
     * Manual catch-up run for records the regular incremental sync (see sync()) may have permanently
     * skipped (PD-5144): a failed item's position gets passed once a later item in the same batch
     * succeeds, so it's never retried by the normal cursor-based sync. This scans BigQuery starting at
     * $startDate independently of the persisted cursor and does not update it, so it can't disturb the
     * regular incremental sync.
     */
    public function backfill(string $startDate, ?string $endDate = null): void
    {
        $limit = self::LIMIT;
        $lastUpdatedDate = date_create($startDate)->format('Y-m-d H:i:s');
        $lastUpdatedId = '0';
        $endTimestamp = !empty($endDate) ? strtotime($endDate) : null;

        $processed = 0;
        $failedScheduleIds = [];
        $reachedEnd = false;

        do {
            $batchCount = 0;

            foreach ($this->scheduleMasterRepository->getRecords($limit, $lastUpdatedId, $lastUpdatedDate) as $item) {
                $batchCount++;

                if ($endTimestamp !== null && !empty($item->etl_updated_timestamp) && strtotime($item->etl_updated_timestamp) > $endTimestamp) {
                    $reachedEnd = true;
                    break;
                }

                try {
                    $this->processOne($item);
                    $processed++;
                } catch (\Throwable $e) {
                    $id = $item instanceof ScheduleMasterItem ? $item->CGTaskID ?? null : null;
                    $failedScheduleIds[] = $id;
                    logger()->error('ScheduleMaster backfill: failed processing item', [
                        'ScheduleId' => $id,
                        'exception' => $e->getMessage(),
                    ]);
                }

                $lastUpdatedDate = $item->etl_updated_timestamp;
                $lastUpdatedId = (string) $item->CGTaskID;
            }
        } while (!$reachedEnd && $batchCount === $limit);

        logger()->info('ScheduleMaster backfill finished', [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'processedItems' => $processed,
            'failedScheduleIds' => $failedScheduleIds,
        ]);
    }

    /**
     * Re-sync specific schedules by CGTaskID, one BigQuery query per RESYNC_CHUNK_SIZE ids.
     *
     * Returns one entry per requested id (see resyncResult()). Only ids that exist locally and are not
     * deleted are sent to BigQuery. UNCHANGED rows are still written in live mode (same data), the status
     * only reports the tracked columns.
     */
    public function resyncByIds(array $cgTaskIds, bool $dryRun = false): array
    {
        if (!$this->scheduleMasterRepository instanceof ScheduleMasterRepository) {
            throw new \LogicException('Resync by ids requires the BigQuery ScheduleMaster repository.');
        }

        if ($dryRun) {
            return $this->resyncChunks($cgTaskIds, true);
        }

        // Hold the regular sync's batch lock: otherwise the sync could write a newer state of a row
        // between our BigQuery read and our write, and we would overwrite it with the older one.
        if ($this->sync->startBatch(self::RESYNC_LOCK) === -1) {
            throw new KantimeSyncException('ScheduleMaster sync is running, try again later.');
        }

        try {
            return $this->resyncChunks($cgTaskIds, false);
        } finally {
            $this->sync->stopBatch(self::RESYNC_LOCK);
        }
    }

    private function resyncChunks(array $cgTaskIds, bool $dryRun): array
    {
        $results = [];

        // Only re-sync schedules we already have: never create rows, and skip locally deleted ones
        // (the repository's update() ignores them, so nothing would be written).
        $deletedFlags = $this->aocScheduleMasterRepository->getDeletedFlagsByCgTaskIds($cgTaskIds);
        $toSync = [];
        foreach ($cgTaskIds as $id) {
            $id = (int) $id;

            if (!array_key_exists($id, $deletedFlags)) {
                $results[$id] = $this->resyncResult($id, ScheduleMasterResyncStatusEnum::NOT_FOUND_LOCAL);
            } elseif ($deletedFlags[$id] === 1) {
                $results[$id] = $this->resyncResult($id, ScheduleMasterResyncStatusEnum::SKIPPED_DELETED);
            } else {
                $toSync[] = $id;
            }
        }

        foreach (array_chunk($toSync, self::RESYNC_CHUNK_SIZE) as $chunk) {
            foreach ($this->scheduleMasterRepository->getRecordsByIds($chunk) as $item) {
                $id = (int) $item->CGTaskID;
                $results[$id] = $this->resyncOne($item, $dryRun);
            }

            foreach ($chunk as $id) {
                $results[$id] ??= $this->resyncResult($id, ScheduleMasterResyncStatusEnum::NOT_FOUND);
            }
        }

        return $results;
    }

    private function resyncOne(ScheduleMasterItem $item, bool $dryRun): array
    {
        $id = (int) $item->CGTaskID;

        try {
            $current = $this->aocScheduleMasterRepository->findByCgTaskId($id, self::RESYNC_TRACKED_COLUMNS);
            $changes = [];
            foreach (self::RESYNC_TRACKED_COLUMNS as $column) {
                $old = $current?->{$column};
                $new = $item->{$column};

                if ((string) $old !== (string) $new) {
                    $changes[$column] = [$old, $new];
                }
            }

            if (!$item->isValid) {
                $status = $dryRun ? ScheduleMasterResyncStatusEnum::WOULD_DELETE : ScheduleMasterResyncStatusEnum::DELETED;
            } elseif ($changes === []) {
                $status = ScheduleMasterResyncStatusEnum::UNCHANGED;
            } else {
                $status = $dryRun ? ScheduleMasterResyncStatusEnum::WOULD_UPDATE : ScheduleMasterResyncStatusEnum::UPDATED;
            }

            if (!$dryRun) {
                $this->processOne($item);
            }

            return $this->resyncResult($id, $status, $changes);
        } catch (\Throwable $e) {
            return $this->resyncResult($id, ScheduleMasterResyncStatusEnum::FAILED, [], $e->getMessage());
        }
    }

    // $changes: column => [old, new]
    private function resyncResult(
        int $id,
        ScheduleMasterResyncStatusEnum $status,
        array $changes = [],
        ?string $error = null
    ): array {
        return [
            'id' => $id,
            'status' => $status,
            'changes' => $changes,
            'error' => $error,
        ];
    }

    /**
     * Process a single ScheduleMasterItem (DTO) using the same logic as the old DaasScheduleMaster job.
     */
    private function processOne(ScheduleMasterItem $item): void
    {
        // Required unique key
        $uniqueID = $item->CGTaskID ?? 0;
        if ($uniqueID <= 0) {
            return;
        }

        if (!$item->isValid) {
            $this->aocScheduleMasterRepository->delete($uniqueID);
            $this->syncConfig->debugSync && logger()->info('ScheduleMaster Item deleted', ['id' => $uniqueID]);
            return;
        }
        $this->aocScheduleMasterRepository->updateOrInsert($item);
        $this->syncConfig->debugSync && logger()->info('ScheduleMaster Item upserted', ['id' => $uniqueID]);
    }
}
