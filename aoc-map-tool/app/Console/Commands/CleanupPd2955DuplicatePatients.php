<?php

namespace App\Console\Commands;

use App\Kantime\Repository\MariaDB\AocPatientsRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * PD-2955: merges the 176 manager-reviewed duplicate-patient pairs from the reviewed CSV.
 *
 * Safety model:
 * - Dry-run by default; --execute is required to write anything.
 * - Before any write for a pair, every row about to be touched (the losing mssql_patients
 *   row + every dependent-table row about to be repointed) is snapshotted to a JSON manifest
 *   on disk. pd2955:restore-backup can replay that manifest to undo a pair exactly - re-INSERTing
 *   the deleted mssql_patients row with its original id and reverting every repointed row - so a
 *   bad merge is always literally recoverable from the backup file, not just from memory of what
 *   the command "should" have done.
 * - Hard-deletes the losing mssql_patients row (by explicit request - the point is for the
 *   record to actually be gone, not just hidden behind is_deleted). This is why the Tier 3
 *   check below matters more here than it would for a soft-delete: InnoDB FK constraints on
 *   those tables would reject the DELETE outright if a referencing row were left behind.
 * - A pair with rows in a Tier 3 table (FK tables this command doesn't know how to repoint
 *   safely) is skipped entirely and flagged for manual handling, rather than partially merged.
 */
class CleanupPd2955DuplicatePatients extends Command
{
    protected $signature = 'pd2955:cleanup-duplicates
        {csv : Path to the manager-reviewed PD-2955 CSV}
        {--execute : Actually write changes. Without this flag, only reports what would happen.}
        {--all : Process every remaining actionable row in this run instead of just the next one.}
        {--row= : Only process this 1-based data row number (for staged rollout / spot checks).}
        {--limit= : Only process the first N actionable pairs.}
        {--include-extended-tables : Also repoint the Tier 2 "gap" tables (see TIER2_TABLES).}
        {--backup-dir=storage/app/pd2955-backups : Directory to write the backup manifest into.}';

    protected $description = 'Merge PD-2955 manager-reviewed duplicate mssql_patients pairs, with a JSON backup of every row touched.';

    /**
     * Always repointed. Mirrors MergeDuplicateClients::handle()'s table list, plus
     * recruiting_schedule.patient_id - a second FK-by-convention column on a table already in
     * that list (column comment: "mssql_patients.id Ref"). Confirmed 2026-10-12 that no current
     * write path (including MergeDuplicateClients itself) ever populates it, only
     * aoc_patient_id - so it's effectively always NULL in practice, but repointed anyway in
     * case any live row is an exception.
     * Each entry: [table, fk_column, extraWhere].
     */
    private const TIER1_TABLES = [
        ['NeedToHire_Accounts', 'aoc_patient_id', []],
        ['NeedToHire_Accounts_Audit', 'aoc_patient_id', []],
        ['notes', 'patient_id', []],
        ['notes', 'entity_id', ['entity' => 'client']],
        ['notifications', 'related_item', ['type' => 'client']],
        ['mssql_recruitertracker', 'aoc_patient_id', []],
        ['recruiting_schedule', 'aoc_patient_id', []],
        ['recruiting_schedule', 'patient_id', []],
    ];

    /**
     * Opt-in via --include-extended-tables. Never repointed by any prior tool, so unverified at
     * scale. Compiled 2026-10-12 by grepping every `aoc_patient_id`/`patient_id` column
     * definition in database/schema/mysql-schema.sql and keeping only tables both (a) lacking a
     * real FK constraint (those are TIER3_BLOCKING_TABLES instead) and (b) actually referenced
     * in app/ today - excludes dead tables (ccc_followup_calls_old/_latest_prod,
     * patient_vacation, provider_patient, temp_rs_notes - zero app/ references) and the dozen
     * dated/"_BU_"/"_temp" snapshot copies of intake/mssql_recruitertracker/recruiting_schedule/
     * supervisory_visits, which are frozen historical copies, not live FK targets.
     * IMPORTANT: by default (without --include-extended-tables) a loser with rows in ANY of
     * these tables causes the whole pair to be SKIPPED, not silently orphaned - see
     * findBlockingReferences().
     */
    private const TIER2_TABLES = [
        ['gp_calculator', 'aoc_patient_id', []],
        ['triageCalls', 'aoc_patient_id', []],
        ['triageFollowup', 'aoc_patient_id', []],
        ['supervisory_visits', 'aoc_patient_id', []],
        ['patient_well_child_visits', 'aoc_patient_id', []],
        ['school_setup_requests', 'aoc_patient_id', []],
        ['intake', 'aoc_patient_id', []],
        ['authorizations', 'aoc_patient_id', []],
        ['ccc_followup_calls', 'aoc_patient_id', []],
        ['coverage_tracker', 'aoc_patient_id', []],
        ['coverage_tracker_log', 'aoc_patient_id', []],
        ['external_user_patients', 'aoc_patient_id', []],
        ['external_users', 'aoc_patient_id', []],
        ['maps_covclin', 'aoc_patient_id', []],
        ['maps_coverageneeds', 'aoc_patient_id', []],
        ['ncc_report_monthly_calls', 'aoc_patient_id', []],
        ['nth_inquiries', 'aoc_patient_id', []],
        ['supply_requests', 'aoc_patient_id', []],
    ];

    /**
     * Never auto-repointed. A pair with rows here is skipped entirely (not partially merged)
     * because silently orphaning or mis-repointing these FKs is worse than leaving the
     * duplicate alone for manual review.
     */
    private const TIER3_BLOCKING_TABLES = [
        ['encounters', 'patient_id'],
        ['patient_contacts', 'patient_id'],
        ['patient_custom_properties', 'patient_id'],
        ['sick_plans', 'patient_id'],
        ['hospitalization_tracker_followups', 'patient_id'],
    ];

    /** CSV rows with a manual override note - always excluded from auto-processing. */
    private const MANUAL_OVERRIDE_PATIENT_ACCOUNTS = ['42302-PDN'];

    /**
     * mssql_patientstatus lookup table, inlined (confirmed against live data 2026-10-12).
     * Patient_StatusID looked like the obvious column for "Status" but is stale/unmaintained
     * (empirically: blank or wrong on live rows, and not referenced anywhere else in the app).
     * The real current status is Patient_CurrentStatusID, decoded via this map - matches
     * App\Enums\MssqlPatientStatus, which has no literal "Intake/Pending" value; the CSV uses
     * that label for a subset of status-4 (Intake) rows, handled as a special case below.
     */
    private const PATIENT_STATUS_LABELS = [
        1 => 'Active', 2 => 'InActive', 3 => 'Non-Admit', 4 => 'Intake', 5 => 'Discharged',
        6 => 'On Hold', 7 => 'Pending SOC', 8 => 'Transferred', 9 => '', 10 => 'EOE',
    ];

    private array $backupManifest = [];
    private string $backupPath;

    public function handle(AocPatientsRepository $patientsRepository): int
    {
        $csvPath = $this->argument('csv');
        if (!is_file($csvPath)) {
            $this->error("CSV file not found: {$csvPath}");
            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $all = (bool) $this->option('all');
        $includeExtended = (bool) $this->option('include-extended-tables');
        $onlyRow = $this->option('row') !== null ? (int) $this->option('row') : null;
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $this->backupPath = rtrim($this->option('backup-dir'), '/') . '/' .
            now()->format('Y-m-d_His') . '_pd2955-cleanup.json';

        $rows = $this->parseCsv($csvPath);
        $this->info(sprintf('%d data rows parsed from %s.', count($rows), $csvPath));

        $processed = 0;
        foreach ($rows as $rowNumber => $row) {
            if ($onlyRow !== null && $rowNumber !== $onlyRow) {
                continue;
            }
            if ($limit !== null && $processed >= $limit) {
                break;
            }

            [$decision, $skipReason] = $this->resolveDecision($row);
            if ($decision === null) {
                $this->line("Row {$rowNumber} ({$row['patient_account']}): SKIP - {$skipReason}");
                continue;
            }

            [$winnerId, $loserId] = $decision;

            $outcome = $this->processPair($rowNumber, $row, $winnerId, $loserId, $includeExtended, $execute);
            $this->line("Row {$rowNumber} ({$row['patient_account']}): {$outcome}");

            if (Str::startsWith($outcome, 'MERGED') || Str::startsWith($outcome, 'WOULD MERGE')) {
                $processed++;

                // Default mode: stop after the first row actually processed this run, so a
                // second invocation advances to the next not-yet-processed row instead of
                // redoing this one or requiring --limit bookkeeping. Already-merged rows above
                // are skipped for free (processPair() returns SKIPPED - no longer exist), so the
                // scan naturally lands on the next new row each time. --all or an explicit --row
                // opts out of this one-at-a-time throttle.
                if (!$all && $onlyRow === null) {
                    break;
                }
            }
        }

        if ($execute && $processed > 0) {
            // Manifest was already flushed to disk incrementally, after each pair, before its
            // delete ran - see processPair(). Nothing left to write here.
            $this->info("Backup manifest at {$this->backupPath} ({$processed} pair(s)).");
            $this->info('Recalculating IsLatestAdmit across all patients...');
            $patientsRepository->recalculateLatestAdmits();
        } elseif (!$execute && $processed > 0) {
            $this->info("Dry run only - nothing written, no backup file created. Re-run with --execute to apply.");
        }

        $this->info("Done. {$processed} pair(s) " . ($execute ? 'merged' : 'would be merged') . '.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: array{0:int,1:int}|null, 1: string} [[winnerId, loserId] or null, skip reason (empty if not skipped)]
     */
    private function resolveDecision(array $row): array
    {
        if (in_array($row['patient_account'], self::MANUAL_OVERRIDE_PATIENT_ACCOUNTS, true)) {
            return [null, 'manual override note on this patient account, never auto-processed'];
        }

        $decisionText = trim($row['decision']);

        if ($decisionText === '') {
            // No explicit manager decision - fall back to the auto-generated Suggested Action,
            // but only if it was confident (not UNCLEAR).
            if (Str::startsWith($row['suggested_action'], 'UNCLEAR')) {
                return [null, 'blank decision, and Suggested Action is UNCLEAR - needs manual review'];
            }
            $decisionText = $row['suggested_action'];
        }

        if (Str::contains(strtolower($decisionText), ['skip', 'do not merge', "don't merge"])) {
            return [null, "explicit SKIP: {$decisionText}"];
        }

        if (stripos($decisionText, 'keep all records') !== false) {
            return [null, 'not a duplicate per manager decision (Keep all records)'];
        }

        if (preg_match('/keep record\s*1/i', $decisionText)) {
            return [[$row['r1_id'], $row['r2_id']], ''];
        }

        if (preg_match('/keep record\s*2/i', $decisionText)) {
            return [[$row['r2_id'], $row['r1_id']], ''];
        }

        return [null, "unrecognized decision text: {$decisionText}"];
    }

    private function processPair(
        int $rowNumber,
        array $row,
        int $winnerId,
        int $loserId,
        bool $includeExtended,
        bool $execute
    ): string {
        if ($winnerId === $loserId) {
            return 'SKIPPED - winner and loser are the same id (bad CSV row); needs manual review.';
        }

        $winner = DB::table('mssql_patients')->where('id', $winnerId)->first();
        $loser = DB::table('mssql_patients')->where('id', $loserId)->first();

        if (!$winner || !$loser) {
            return 'SKIPPED - one or both records no longer exist in mssql_patients.';
        }
        if ((int) $loser->is_deleted === 1) {
            return 'SKIPPED - losing record already soft-deleted by another process; needs manual re-review before a hard delete.';
        }
        if ((int) $winner->is_deleted === 1) {
            return 'SKIPPED - winning record is soft-deleted; decision needs manual re-review.';
        }

        $drift = $this->detectDrift($row, $winner, $loser);
        if ($drift !== null) {
            return "SKIPPED - live data drifted since the CSV snapshot ({$drift}); needs manual re-review.";
        }

        $tablesToCheck = $includeExtended ? self::TIER2_TABLES : [];
        $blockingHits = $this->findBlockingReferences($loserId, $includeExtended);
        if (!empty($blockingHits)) {
            return 'SKIPPED - loser has rows in blocking table(s) [' . implode(', ', $blockingHits) .
                '] this command does not repoint; needs manual review.';
        }

        $pairBackup = [
            'row_number' => $rowNumber,
            'patient_account' => $row['patient_account'],
            'winner_id' => $winnerId,
            'loser_id' => $loserId,
            'loser_row_before' => (array) $loser,
            'repointed_rows' => [],
        ];

        $tables = array_merge(self::TIER1_TABLES, $tablesToCheck);
        foreach ($tables as [$table, $column, $extraWhere]) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            $query = DB::table($table)->where($column, $loserId);
            foreach ($extraWhere as $k => $v) {
                $query->where($k, $v);
            }
            $matches = $query->get();
            foreach ($matches as $record) {
                $pairBackup['repointed_rows'][] = [
                    'table' => $table,
                    'column' => $column,
                    'id' => $record->id,
                    'row_before' => (array) $record,
                    // What this column gets written to below - recorded explicitly (rather than
                    // assumed from $winnerId at restore time) so restore can verify nothing else
                    // has touched the row since, before reverting it.
                    'written_value' => $winnerId,
                ];
            }
        }

        // maps_nth is keyed by business-key ClientID, not a plain FK id - find the rows that
        // will need repointing the same way MergeDuplicateClients does, via the
        // NeedToHire_Accounts ids already captured above. Done here (a pure read) rather than
        // inside the transaction below, so this is captured in the backup before anything
        // is written - see the flushBackupManifest() call right after this block.
        $movedNthIds = collect($pairBackup['repointed_rows'])
            ->where('table', 'NeedToHire_Accounts')
            ->pluck('id');
        if ($movedNthIds->isNotEmpty() && Schema::hasTable('maps_nth')) {
            $mapsNthBefore = DB::table('maps_nth')->whereIn('aoc_nthaccounts_id', $movedNthIds)->get();
            foreach ($mapsNthBefore as $record) {
                $pairBackup['repointed_rows'][] = [
                    'table' => 'maps_nth',
                    'column' => 'ClientID',
                    'id' => $record->id,
                    'row_before' => (array) $record,
                    // maps_nth.ClientID is set to the winner's PatientAccount (business key),
                    // not winner_id - unlike every other entry above.
                    'written_value' => $winner->PatientAccount,
                ];
            }
        }

        if (!$execute) {
            $count = count($pairBackup['repointed_rows']);
            return "WOULD MERGE - keep {$winnerId}, hard-delete {$loserId}, repoint {$count} dependent row(s).";
        }

        // Backup must be durably on disk before any write happens below - never the other way
        // around. flushBackupManifest() throws if encoding or writing fails, which aborts this
        // pair (and the whole run) before the transaction ever starts, so a bad/missing backup
        // can never be discovered only after the hard delete already committed.
        $this->backupManifest[] = $pairBackup;
        $this->flushBackupManifest();

        try {
            DB::transaction(function () use ($pairBackup, $winnerId, $loserId, $winner, $movedNthIds) {
                foreach ($pairBackup['repointed_rows'] as $entry) {
                    if ($entry['table'] === 'maps_nth') {
                        continue; // repointed below in one batched update, using the winner's current PatientAccount
                    }
                    DB::table($entry['table'])->where('id', $entry['id'])->update([$entry['column'] => $winnerId]);
                }

                if ($movedNthIds->isNotEmpty() && Schema::hasTable('maps_nth')) {
                    DB::table('maps_nth')
                        ->whereIn('aoc_nthaccounts_id', $movedNthIds)
                        ->update(['ClientID' => $winner->PatientAccount]);
                }

                // Hard delete, by request - every referencing row was either repointed above or
                // caused this pair to be skipped earlier (see findBlockingReferences), so no FK
                // constraint on mssql_patients.id should still be pointing at $loserId here.
                DB::table('mssql_patients')->where('id', $loserId)->delete();
            });
        } catch (\Throwable $e) {
            // The transaction already rolled back on its own - nothing was actually written.
            // But the backup entry appended above now describes a merge that never happened,
            // so it must come back out of the manifest (and the file re-flushed) rather than
            // sitting there as a false record. Most likely cause: a unique constraint on one
            // of the Tier 2 tables (e.g. supervisory_visits' provider+patient+visit-date key) -
            // duplicate patients very often have duplicate downstream rows too (see PD-4193),
            // so repointing the loser's row onto the winner can collide with one the winner
            // already has. Skip this pair for manual review instead of crashing the whole run.
            array_pop($this->backupManifest);
            $this->flushBackupManifest();
            return "SKIPPED - DB error while merging ({$e->getMessage()}); nothing written, needs manual review.";
        }

        $count = count($pairBackup['repointed_rows']);
        return "MERGED - kept {$winnerId}, hard-deleted {$loserId}, repointed {$count} dependent row(s).";
    }

    private function detectDrift(array $row, object $winner, object $loser): ?string
    {
        // Compare by the original r1/r2 identity from the CSV, regardless of which one won.
        $r1 = $row['r1_id'] === (int) $winner->id ? $winner : $loser;
        $r2 = $row['r1_id'] === (int) $winner->id ? $loser : $winner;

        $mismatches = [];
        if ((int) $r1->AdmitNo !== $row['r1_admitno']) {
            $mismatches[] = 'r1.AdmitNo';
        }
        if ($this->statusDiffers($r1->Patient_CurrentStatusID, $row['r1_status'])) {
            $mismatches[] = 'r1.Status';
        }
        if ((int) $r1->IsLatestAdmit !== $row['r1_latest']) {
            $mismatches[] = 'r1.IsLatestAdmit';
        }
        if ((int) $r2->AdmitNo !== $row['r2_admitno']) {
            $mismatches[] = 'r2.AdmitNo';
        }
        if ($this->statusDiffers($r2->Patient_CurrentStatusID, $row['r2_status'])) {
            $mismatches[] = 'r2.Status';
        }
        if ((int) $r2->IsLatestAdmit !== $row['r2_latest']) {
            $mismatches[] = 'r2.IsLatestAdmit';
        }

        return empty($mismatches) ? null : implode(', ', $mismatches);
    }

    private function statusDiffers(?int $liveStatusId, string $snapshotStatus): bool
    {
        $liveLabel = self::PATIENT_STATUS_LABELS[$liveStatusId] ?? null;
        if ($liveLabel === 'Intake' && str_starts_with($snapshotStatus, 'Intake')) {
            return false;
        }
        return strtolower($liveLabel ?? '~unmapped~') !== strtolower($snapshotStatus);
    }

    /**
     * @return string[] table names that have at least one row referencing $loserId and would
     *     otherwise be silently orphaned by the hard delete - either a Tier 3 table (never
     *     repointed, ever) or, when --include-extended-tables wasn't passed, any Tier 2 table
     *     (repointable, but opt-in - so a referencing row there blocks the pair instead of
     *     being quietly left dangling).
     */
    private function findBlockingReferences(int $loserId, bool $includeExtended): array
    {
        $hits = [];
        foreach (self::TIER3_BLOCKING_TABLES as [$table, $column]) {
            if (!Schema::hasTable($table)) {
                continue;
            }
            if (DB::table($table)->where($column, $loserId)->exists()) {
                $hits[] = $table;
            }
        }
        if (!$includeExtended) {
            foreach (self::TIER2_TABLES as [$table, $column, $extraWhere]) {
                if (!Schema::hasTable($table)) {
                    continue;
                }
                $query = DB::table($table)->where($column, $loserId);
                foreach ($extraWhere as $k => $v) {
                    $query->where($k, $v);
                }
                if ($query->exists()) {
                    $hits[] = "{$table} (pass --include-extended-tables to repoint it)";
                }
            }
        }
        return $hits;
    }

    /**
     * Rewrites the whole manifest file from $this->backupManifest. Called after every pair is
     * appended, before that pair's transaction runs - throws rather than returning on any
     * failure, since a hard delete must never proceed without a confirmed-on-disk backup.
     */
    private function flushBackupManifest(): void
    {
        $dir = dirname($this->backupPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException("Could not create backup directory: {$dir}");
        }

        $json = json_encode($this->backupManifest, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            throw new \RuntimeException(
                'Failed to JSON-encode the backup manifest (' . json_last_error_msg() . '); aborting before any write.'
            );
        }

        $written = file_put_contents($this->backupPath, $json);
        if ($written === false || $written !== strlen($json)) {
            throw new \RuntimeException("Failed to fully write backup manifest to {$this->backupPath}; aborting before any write.");
        }
    }

    /**
     * Parses the manager-reviewed CSV. Data rows start right after the header row whose first
     * two columns are "Patient Account" / "KanTime Intake ID", and run until a blank first cell.
     */
    private function parseCsv(string $path): array
    {
        $handle = fopen($path, 'r');
        $allRows = [];
        while (($line = fgetcsv($handle)) !== false) {
            $allRows[] = $line;
        }
        fclose($handle);

        $headerIndex = null;
        foreach ($allRows as $i => $line) {
            if (($line[0] ?? null) === 'Patient Account' && ($line[1] ?? null) === 'KanTime Intake ID') {
                $headerIndex = $i;
                break;
            }
        }
        if ($headerIndex === null) {
            throw new \RuntimeException('Could not find the "Patient Account" header row in the CSV.');
        }

        $parsed = [];
        $rowNumber = 0;
        for ($i = $headerIndex + 1; $i < count($allRows); $i++) {
            $line = $allRows[$i];
            if (($line[0] ?? '') === '') {
                break;
            }
            $rowNumber++;
            $parsed[$rowNumber] = [
                'patient_account' => trim($line[0]),
                'kantime_intake_id' => (int) $line[1],
                'r1_id' => (int) $line[3],
                'r1_admitno' => (int) $line[4],
                'r1_status' => trim($line[5]),
                'r1_latest' => strtolower(trim($line[6])) === 'yes' ? 1 : 0,
                'r2_id' => (int) $line[9],
                'r2_admitno' => (int) $line[10],
                'r2_status' => trim($line[11]),
                'r2_latest' => strtolower(trim($line[12])) === 'yes' ? 1 : 0,
                'suggested_action' => trim($line[19] ?? ''),
                'decision' => trim($line[20] ?? ''),
            ];
        }

        return $parsed;
    }
}
