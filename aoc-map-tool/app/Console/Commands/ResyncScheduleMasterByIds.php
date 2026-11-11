<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\ScheduleMasterResyncStatusEnum;
use App\Kantime\Services\ScheduleMasterSyncService;
use App\Repositories\Notes\NotesReadRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Re-sync specific ScheduleMaster rows from Kantime (BigQuery) by CGTaskID ("Visit ID" in the UI).
 *
 * Usage:
 *   php artisan kantime:resync-schedulemaster 25358388 25358389            — re-sync the given CGTaskIDs
 *   php artisan kantime:resync-schedulemaster --app-error-notes            — re-sync schedules flagged by billers
 *   php artisan kantime:resync-schedulemaster --app-error-notes --dry-run  — report what would change, no DB writes
 *
 * Logic covered by this command:
 *   CASE 0: CGTaskID is not in mssql_schedulemaster / the local row is deleted (is_deleted = 1)
 *           → NOT_FOUND_LOCAL / SKIPPED_DELETED, not sent to BigQuery (the command never creates rows)
 *
 *   CASE 1: CGTaskID is not returned by BigQuery
 *           → NOT_FOUND, nothing written
 *
 *   CASE 2: BigQuery row is marked invalid (ISVALIDRECORD)
 *           → DELETED, same as the regular sync
 *
 *   CASE 3: BigQuery row differs from the local row in a tracked column
 *           (IsBilled, IsBillable, ScheduleStatusID, EChartStatusID, HasChild, IsChild)
 *           → UPDATED (WOULD_UPDATE in dry run)
 *
 *   CASE 4: Tracked columns already match
 *           → UNCHANGED (the row is still rewritten with the BigQuery data in live mode)
 *
 *   CASE 5: Writing the row failed
 *           → FAILED, error logged, the rest of the run continues
 *
 * Notes:
 *   - Ids given as arguments must be positive integers; any invalid value stops the command before syncing.
 *   - --app-error-notes selects unbilled schedules in Bill Ready / Not Bill Ready (billing_bucket_type 1, 2)
 *     that have a billing note with "APP ERROR" (also "APP EROR", "APP-ERROR", "APPERROR").
 *   - One BigQuery query per 1000 ids.
 *   - A live run holds the regular sync's batch lock (process_settings 'daas_schedules') and refuses to start
 *     while that sync is running, so the two never overwrite each other. Dry run takes no lock.
 *   - Buckets are recomputed only by `php artisan set:billingbucketgroup`.
 */
class ResyncScheduleMasterByIds extends Command
{
    protected $signature = 'kantime:resync-schedulemaster
                            {ids?* : CGTaskIDs to re-sync}
                            {--app-error-notes : Add unbilled bucket schedules with an "APP ERROR" billing note}
                            {--dry-run : Report what would change, do not write}';

    protected $description = 'PD-5516: re-sync specific ScheduleMaster rows from Kantime by CGTaskID';

    private const APP_ERROR_NOTE_PATTERNS = ['%APP ERROR%', '%APP EROR%', '%APP-ERROR%', '%APPERROR%'];

    private const LOG_CHANNEL = 'resync_schedulemaster';

    private bool $isDryRun = false;

    public function handle(ScheduleMasterSyncService $service, NotesReadRepository $notesReadRepository): int
    {
        $this->isDryRun = (bool) $this->option('dry-run');
        $invalidIds = array_filter(
            $this->argument('ids'),
            fn ($id) => !ctype_digit((string) $id) || (int) $id === 0
        );

        if ($invalidIds !== []) {
            $this->logError('Invalid CGTaskIDs (positive integers only): ' . implode(', ', $invalidIds));

            return self::FAILURE;
        }

        $ids = array_map('intval', $this->argument('ids'));

        $this->info('=== Resync ScheduleMaster by CGTaskID ===');
        $this->info('Mode  : ' . ($this->isDryRun ? 'DRY RUN' : 'LIVE'));

        $this->log('=== START === mode=' . ($this->isDryRun ? 'DRY_RUN' : 'LIVE'));

        if ($this->option('app-error-notes')) {
            $noteIds = $notesReadRepository->getUnbilledBucketScheduleIdsWithBillingNote(self::APP_ERROR_NOTE_PATTERNS);
            $this->info('APP ERROR notes : ' . count($noteIds));
            $ids = array_merge($ids, $noteIds);
        }

        $ids = array_values(array_unique($ids));

        if (empty($ids)) {
            $this->logError('No CGTaskIDs given.');

            return self::FAILURE;
        }

        $this->info('Ids   : ' . count($ids));

        try {
            $results = $service->resyncByIds($ids, $this->isDryRun);
        } catch (\Throwable $e) {
            $this->logError("Resync failed: {$e->getMessage()}");

            return self::FAILURE;
        }

        foreach ($results as $result) {
            $this->logResult($result);
        }

        $this->printFinalReport($results);

        $failed = array_filter($results, fn ($result) => $result['status'] === ScheduleMasterResyncStatusEnum::FAILED);

        return $failed === [] ? self::SUCCESS : self::FAILURE;
    }

    private function logResult(array $result): void
    {
        $changes = [];
        foreach ($result['changes'] as $column => [$old, $new]) {
            $changes[] = "{$column}=" . ($old ?? 'NULL') . '->' . ($new ?? 'NULL');
        }

        $line = "{$result['status']->value} id={$result['id']}"
            . ($changes !== [] ? ' ' . implode(' ', $changes) : '');

        if ($result['status'] === ScheduleMasterResyncStatusEnum::FAILED) {
            $this->logError("{$line} error: {$result['error']}");

            return;
        }

        $this->log($line);
    }

    private function printFinalReport(array $results): void
    {
        $byStatus = array_fill_keys(array_column(ScheduleMasterResyncStatusEnum::cases(), 'value'), []);
        foreach ($results as $result) {
            $byStatus[$result['status']->value][] = $result['id'];
        }

        $billedNow = array_filter(
            $results,
            fn ($result) => isset($result['changes']['IsBilled']) && (int) $result['changes']['IsBilled'][1] === 1
        );

        $this->info('');
        $this->info('=== Resync ScheduleMaster Complete ===');
        $this->info('Requested              : ' . count($results));
        foreach ($byStatus as $status => $ids) {
            if ($ids !== []) {
                $this->info(str_pad($status, 23) . ': ' . count($ids));
            }
        }
        $this->info('IsBilled 0 -> 1        : ' . count($billedNow));
        $this->info('Log                    : ' . config('logging.channels.' . self::LOG_CHANNEL . '.path'));

        $this->log('=== SUMMARY START ===');
        foreach ($byStatus as $status => $ids) {
            $this->log(strtolower($status) . '_count=' . count($ids));
            $this->log(strtolower($status) . '_ids=' . implode(',', $ids));
        }
        $this->log('billed_now_count=' . count($billedNow));
        $this->log('=== SUMMARY END ===');
    }

    private function log(string $message): void
    {
        Log::channel(self::LOG_CHANNEL)->info($message);
    }

    private function logError(string $message): void
    {
        $this->error($message);
        Log::channel(self::LOG_CHANNEL)->error($message);
        Log::error($message);
    }
}
