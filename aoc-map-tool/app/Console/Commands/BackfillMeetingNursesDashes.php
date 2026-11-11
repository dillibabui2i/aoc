<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Usage:
 *   php artisan nth:backfill-meeting-nurses-dashes                           — run full backfill
 *   php artisan nth:backfill-meeting-nurses-dashes --dry-run                 — diagnostics only, no DB changes
 *   php artisan nth:backfill-meeting-nurses-dashes --chunk=5000              — custom chunk size
 *   php artisan nth:backfill-meeting-nurses-dashes --patient-id=45546-PDN    — run for a single patient account
 *
 * Background (PD-4862):
 *   mssql_patients.CF_MeetingNurses has two variants of the same phrases, differing only
 *   by the dash character used ("-" U+002D vs "–" U+2013 en dash), e.g.
 *   "No - unable to contact" vs "No – unable to contact". This makes the Need to Hire
 *   grid's "Meeting Nurses" filter (built from a SELECT DISTINCT on this column) show
 *   near-duplicate options.
 *
 *   This command normalizes CF_MeetingNurses by replacing the en dash with a regular
 *   hyphen, so the filter's DISTINCT list collapses back to one option per phrase.
 *
 * Notes:
 *   - Dry run never writes to DB; it only logs WOULD_UPDATE entries and summary counters.
 */
class BackfillMeetingNursesDashes extends Command
{
    protected $signature = 'nth:backfill-meeting-nurses-dashes
                            {--dry-run : Evaluate and log only, do not update records}
                            {--chunk=5000 : Records per chunk}
                            {--patient-id= : Process a single PatientAccount}';

    protected $description = 'Normalize en dash to hyphen in mssql_patients.CF_MeetingNurses';

    private const TABLE = 'mssql_patients';

    private const COLUMN = 'CF_MeetingNurses';

    private const EN_DASH = "\u{2013}";

    private string $logFile = 'backfill_meeting_nurses_dashes.log';

    private bool $isDryRun = false;

    private int $rowsScanned = 0;

    private int $rowsUpdated = 0;

    private int $wouldUpdateCount = 0;

    private int $errorsCount = 0;

    public function handle(): int
    {
        $this->isDryRun = (bool)$this->option('dry-run');
        $chunkSize = (int)$this->option('chunk');
        $patientId = $this->option('patient-id');

        $this->info('=== Backfill Meeting Nurses Dashes ===');
        $this->info('Mode  : ' . ($this->isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->info('Chunk : ' . $chunkSize);
        $this->log('=== START ===');

        $query = DB::table(self::TABLE)
            ->where(self::COLUMN, 'like', '%' . self::EN_DASH . '%');

        if ($patientId) {
            $query->where('PatientAccount', $patientId);
        }

        $query->orderBy('id')
            ->chunkById($chunkSize, function ($rows) {
                foreach ($rows as $row) {
                    $this->processRow($row);
                }

                $this->info(
                    "Chunk done. scanned={$this->rowsScanned} updated={$this->rowsUpdated} " .
                    "would_update={$this->wouldUpdateCount} errors={$this->errorsCount}"
                );
            }, 'id');

        $this->printFinalReport();

        return self::SUCCESS;
    }

    private function processRow(object $row): void
    {
        $this->rowsScanned++;

        try {
            $original = $row->{self::COLUMN};
            $normalized = str_replace(self::EN_DASH, '-', $original);

            if ($normalized === $original) {
                return;
            }

            if ($this->isDryRun) {
                $this->wouldUpdateCount++;
                $this->log(
                    "WOULD_UPDATE id={$row->id} PatientAccount={$row->PatientAccount} " .
                    "before=\"{$original}\" after=\"{$normalized}\""
                );
                return;
            }

            $wasUpdated = DB::transaction(function () use ($row) {
                $locked = DB::table(self::TABLE)
                    ->where('id', $row->id)
                    ->lockForUpdate()
                    ->first();

                if ($locked === null) {
                    return false;
                }

                $lockedNormalized = str_replace(self::EN_DASH, '-', $locked->{self::COLUMN});

                if ($lockedNormalized === $locked->{self::COLUMN}) {
                    return false;
                }

                DB::table(self::TABLE)
                    ->where('id', $row->id)
                    ->update([
                        self::COLUMN => $lockedNormalized,
                        'updated_at' => now(),
                    ]);

                return true;
            });

            if (!$wasUpdated) {
                return;
            }

            $this->rowsUpdated++;
            $this->log(
                "UPDATED id={$row->id} PatientAccount={$row->PatientAccount} " .
                "before=\"{$original}\" after=\"{$normalized}\""
            );
        } catch (\Throwable $e) {
            $this->errorsCount++;
            $this->logError("id={$row->id} error: {$e->getMessage()}");
        }
    }

    private function printFinalReport(): void
    {
        $this->info('');
        $this->info('=== Backfill Meeting Nurses Dashes Complete ===');
        $this->info("Rows scanned (with en dash) : {$this->rowsScanned}");

        if ($this->isDryRun) {
            $this->info("Would update                : {$this->wouldUpdateCount}");
        } else {
            $this->info("Rows updated                : {$this->rowsUpdated}");
        }

        $this->info("Errors                      : {$this->errorsCount}");

        $this->log(
            "=== DONE scanned={$this->rowsScanned} updated={$this->rowsUpdated} " .
            "would_update={$this->wouldUpdateCount} errors={$this->errorsCount} ==="
        );
    }

    private function log(string $message): void
    {
        $line = '[' . now()->format('Y-m-d H:i:s') . '] ' . $message;
        $logPath = storage_path('logs/' . $this->logFile);
        file_put_contents($logPath, $line . PHP_EOL, FILE_APPEND);
    }

    private function logError(string $message): void
    {
        $this->error($message);
        $this->log('ERROR: ' . $message);
        Log::error($message);
    }
}
