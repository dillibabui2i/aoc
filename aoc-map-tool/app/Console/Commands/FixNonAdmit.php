<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Usage:
 *   php artisan daas:fix-non-admit                           — full fix
 *   php artisan daas:fix-non-admit --dry-run                 — diagnostics only, no changes
 *   php artisan daas:fix-non-admit --chunk=5000              — custom chunk size
 *   php artisan daas:fix-non-admit --patient-id=45546-PDN    — single patient fix
 *
 * Covers all known cases:
 *   CASE 1: IsNonAdmit in MySQL doesn't match CLIENT_IS_NON_ADMIT in Snowflake → sync it
 *   CASE 2: Both duplicate records active (is_deleted=0) → keep best, delete rest
 *   CASE 3: Wrong record active, right record deleted → restore right, delete wrong
 *   CASE 5: Active IsLatestAdmit=1 has intake with Approve Non Admit but Snowflake
 *           says CLIENT_IS_NON_ADMIT=0:
 *           5a. Sibling found (is_deleted=0 or is_deleted=1):
 *               → restore sibling if deleted, relink intake to sibling, delete current
 *           5b. No sibling found:
 *               → clear intake_outcome, intake_bucket, intake_nonadmit_reason
 *   CASE 6: No current IntakeID candidates in MySQL → deduplicate any IntakeID with dupes
 *   CASE 7: Recalculate IsLatestAdmit globally after all fixes
 */
class FixNonAdmit extends Command
{
    protected $signature = 'daas:fix-non-admit
                            {--dry-run : Run diagnostics only, no DB changes}
                            {--chunk=5000 : Records per Snowflake chunk}
                            {--patient-id= : Process single PatientAccount for testing}';

    protected $description = 'Fix IsNonAdmit, is_deleted and IsLatestAdmit by comparing with Snowflake';

    private string $logFile = 'fix_non_admit.log';
    private string $tableName = 'mssql_patients';
    private bool $isDryRun = false;

    private int $snowflakeTotal = 0;
    private int $fixedIsNonAdmit = 0;
    private int $fixedDupes = 0;
    private int $fixedRestored = 0;
    private int $fixedRelinked = 0;
    private int $fixedCleared = 0;
    private int $skipped = 0;
    private int $errors = 0;

    public function handle(): int
    {
        $this->isDryRun = $this->option('dry-run');
        $chunkSize = (int)$this->option('chunk');
        $patientId = $this->option('patient-id');

        $this->info('=== Fix NonAdmit / IsLatestAdmit ===');
        $this->info('Mode  : ' . ($this->isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->info('Chunk : ' . $chunkSize);
        $this->log('=== START ===');

        $this->runDiagnostics('Before');

        if ($this->isDryRun) {
            $this->info('Dry run complete. No changes made.');
            return 0;
        }

        $this->info('');
        $this->info('Processing Snowflake data...');

        if ($patientId) {
            $rows = $this->fetchSingleFromSnowflake($patientId);
            $this->snowflakeTotal += count($rows);
            $this->processChunk($rows);
        } else {
            $offset = 0;
            do {
                $this->info("Fetching chunk offset={$offset} size={$chunkSize}...");
                $rows = $this->fetchChunkFromSnowflake($chunkSize, $offset);
                $count = count($rows);
                $this->snowflakeTotal += $count;

                if ($count > 0) {
                    $this->processChunk($rows);
                }

                $offset += $chunkSize;
                $this->info(
                    "  Chunk done. isNonAdmit={$this->fixedIsNonAdmit} dupes={$this->fixedDupes} restored={$this->fixedRestored} relinked={$this->fixedRelinked} cleared={$this->fixedCleared} errors={$this->errors}"
                );
            } while ($count === $chunkSize);
        }

        $this->info('');
        $this->info('Recalculating IsLatestAdmit...');
        $this->recalculateLatestAdmits();

        $this->printFinalReport();

        return 0;
    }

    private function fetchChunkFromSnowflake(int $limit, int $offset): array
    {
        $query = "
            SELECT
                CLIENT_PATIENT_ID,
                CLIENT_INTAKE_ID,
                CLIENT_INTAKE_SEQUENCE_NO,
                CLIENT_CURRENT_STATUS,
                CLIENT_IS_NON_ADMIT,
                CLIENT_STATUS,
                CLIENT_INTAKE_STATUS,
                CLIENT_NONADMIT_REASON,
                CLIENT_IS_INTAKE,
                ISVALIDRECORD,
                ETL_UPDATED_TIMESTAMP
            FROM CLIENTMASTER_SVW
            ORDER BY CLIENT_PATIENT_ID ASC, CLIENT_INTAKE_ID ASC
            LIMIT {$limit} OFFSET {$offset}
        ";

        $raw = DB::connection('daas')->select(DB::raw($query));
        return json_decode(json_encode($raw), true);
    }

    private function fetchSingleFromSnowflake(string $patientId): array
    {
        $safe = str_replace("'", "''", $patientId);
        $query = "
            SELECT
                CLIENT_PATIENT_ID,
                CLIENT_INTAKE_ID,
                CLIENT_INTAKE_SEQUENCE_NO,
                CLIENT_CURRENT_STATUS,
                CLIENT_IS_NON_ADMIT,
                CLIENT_STATUS,
                CLIENT_INTAKE_STATUS,
                CLIENT_NONADMIT_REASON,
                CLIENT_IS_INTAKE,
                ISVALIDRECORD,
                ETL_UPDATED_TIMESTAMP
            FROM CLIENTMASTER_SVW
            WHERE CLIENT_PATIENT_ID = '{$safe}'
            ORDER BY CLIENT_INTAKE_ID ASC, CLIENT_INTAKE_SEQUENCE_NO ASC
        ";

        $raw = DB::connection('daas')->select(DB::raw($query));
        return json_decode(json_encode($raw), true);
    }

    private function processChunk(array $rows): void
    {
        $grouped = [];
        foreach ($rows as $row) {
            $grouped[$row['CLIENT_PATIENT_ID']][] = $row;
        }

        foreach ($grouped as $patientAccount => $patientRows) {
            try {
                $this->processPatient($patientAccount, $patientRows);
            } catch (\Exception $e) {
                $this->errors++;
                $this->logError("PatientAccount={$patientAccount} error: " . $e->getMessage());
            }
        }
    }

    private function processPatient(string $patientAccount, array $snowflakeRows): void
    {
        $allMysqlRecords = DB::table($this->tableName)
            ->where('PatientAccount', $patientAccount)
            ->orderByDesc('id')
            ->get()
            ->toArray();

        if (empty($allMysqlRecords)) {
            $this->skipped++;
            return;
        }

        foreach ($snowflakeRows as $sfRow) {
            $sfIntakeId = (int)$sfRow['CLIENT_INTAKE_ID'];
            $sfIsNonAdmit = (int)$sfRow['CLIENT_IS_NON_ADMIT'];

            foreach ($allMysqlRecords as $record) {
                if ((int)$record->IntakeID !== $sfIntakeId) {
                    continue;
                }
                if ((int)$record->IsNonAdmit !== $sfIsNonAdmit) {
                    $this->log(
                        "CASE1 IsNonAdmit: PA={$patientAccount} IntakeID={$sfIntakeId} id={$record->id} {$record->IsNonAdmit}→{$sfIsNonAdmit}"
                    );
                    DB::table($this->tableName)
                        ->where('id', $record->id)
                        ->update(['IsNonAdmit' => $sfIsNonAdmit, 'updated_at' => now()]);
                    $this->fixedIsNonAdmit++;
                }
            }
        }

        $currentSfRow = null;
        foreach ($snowflakeRows as $sfRow) {
            if ($sfRow['CLIENT_CURRENT_STATUS'] === 'Active' && (int)$sfRow['CLIENT_IS_NON_ADMIT'] === 0) {
                if ($currentSfRow === null || (int)$sfRow['CLIENT_INTAKE_ID'] > (int)$currentSfRow['CLIENT_INTAKE_ID']) {
                    $currentSfRow = $sfRow;
                }
            }
        }
        if ($currentSfRow === null) {
            foreach ($snowflakeRows as $sfRow) {
                if ($currentSfRow === null || (int)$sfRow['CLIENT_INTAKE_ID'] > (int)$currentSfRow['CLIENT_INTAKE_ID']) {
                    $currentSfRow = $sfRow;
                }
            }
        }

        if ($currentSfRow === null) {
            $this->skipped++;
            return;
        }

        $currentIntakeId = (int)$currentSfRow['CLIENT_INTAKE_ID'];
        $currentSeq = (int)$currentSfRow['CLIENT_INTAKE_SEQUENCE_NO'];
        $currentIsNonAdmit = (int)$currentSfRow['CLIENT_IS_NON_ADMIT'];

        if ($currentIsNonAdmit === 0) {
            // Reload records fresh before handling wrong intake
            $freshRecords = DB::table($this->tableName)
                ->where('PatientAccount', $patientAccount)
                ->orderByDesc('id')
                ->get()
                ->toArray();
            $this->handleWrongIntakeLink($freshRecords, $patientAccount, $currentIntakeId, $currentSeq);
        }

        $candidates = array_values(
            array_filter(
                DB::table($this->tableName)
                    ->where('PatientAccount', $patientAccount)
                    ->where('IntakeID', $currentIntakeId)
                    ->where('CLIENT_INTAKE_SEQUENCE_NO', $currentSeq)
                    ->orderByDesc('id')
                    ->get()
                    ->toArray(),
                fn($r) => true
            )
        );

        if (empty($candidates)) {
            $this->handleOrphanDuplicates(
                DB::table($this->tableName)
                    ->where('PatientAccount', $patientAccount)
                    ->orderByDesc('id')
                    ->get()
                    ->toArray(),
                $patientAccount
            );
            return;
        }

        $bestRecord = $this->selectBestRecord($candidates, $patientAccount, $currentIntakeId);

        if ($bestRecord === null) {
            $this->skipped++;
            return;
        }

        foreach ($candidates as $candidate) {
            if ($candidate->id === $bestRecord->id) {
                if ($candidate->is_deleted == 1) {
                    $this->log("CASE3 RESTORE: PA={$patientAccount} id={$candidate->id} IntakeID={$currentIntakeId}");
                    DB::table($this->tableName)
                        ->where('id', $candidate->id)
                        ->update(['is_deleted' => 0, 'updated_at' => now()]);
                    $this->fixedRestored++;
                }
            } else {
                if ($candidate->is_deleted == 0) {
                    $this->log(
                        "CASE2 DEDUP: PA={$patientAccount} deleted id={$candidate->id} kept id={$bestRecord->id}"
                    );
                    DB::table($this->tableName)
                        ->where('id', $candidate->id)
                        ->update(['is_deleted' => 1, 'IsLatestAdmit' => 0, 'updated_at' => now()]);
                    $this->fixedDupes++;
                }
            }
        }
    }

    private function handleWrongIntakeLink(
        array $allMysqlRecords,
        string $patientAccount,
        int $currentIntakeId,
        int $currentSeq
    ): void {
        $activeForCurrentIntake = array_values(
            array_filter(
                $allMysqlRecords,
                fn($r) => (int)$r->IntakeID === $currentIntakeId
                    && (int)$r->CLIENT_INTAKE_SEQUENCE_NO === $currentSeq
                    && $r->is_deleted == 0
            )
        );

        foreach ($activeForCurrentIntake as $currentRecord) {
            $wrongIntakes = DB::table('intake')
                ->where('aoc_patient_id', $currentRecord->id)
                ->whereIn('intake_outcome', ['Non Admit', 'Approve Non Admit'])
                ->whereNotNull('intake_nonadmit_reason')
                ->where('intake_nonadmit_reason', '<>', '')
                ->get()
                ->toArray();

            if (empty($wrongIntakes)) {
                continue;
            }

            foreach ($wrongIntakes as $wrongIntake) {
                $sibling = DB::table($this->tableName)
                    ->where('PatientAccount', $patientAccount)
                    ->where('IntakeID', $currentIntakeId)
                    ->where('CLIENT_INTAKE_SEQUENCE_NO', $currentSeq)
                    ->where('id', '<>', $currentRecord->id)
                    ->orderBy('is_deleted', 'asc') // prefer active sibling first
                    ->orderByDesc('id')
                    ->first();

                if ($sibling) {
                    $this->log(
                        "CASE5a: PA={$patientAccount} intake_id={$wrongIntake->id} relink from id={$currentRecord->id} to sibling id={$sibling->id}"
                    );

                    if ($sibling->is_deleted == 1) {
                        DB::table($this->tableName)
                            ->where('id', $sibling->id)
                            ->update(['is_deleted' => 0, 'updated_at' => now()]);
                        $this->fixedRestored++;
                        $this->log("CASE5a RESTORE sibling: id={$sibling->id}");
                    }

                    DB::table('intake')
                        ->where('id', $wrongIntake->id)
                        ->update([
                            'aoc_patient_id' => $sibling->id,
                            'intake_outcome' => null,
                            'intake_bucket' => null,
                            'intake_nonadmit_reason' => null,
                            'updated_at' => now(),
                        ]);

                    DB::table($this->tableName)
                        ->where('id', $currentRecord->id)
                        ->update(['is_deleted' => 1, 'IsLatestAdmit' => 0, 'updated_at' => now()]);
                    $this->fixedDupes++;
                    $this->fixedRelinked++;
                } else {
                    $this->log(
                        "CASE5b CLEAR: PA={$patientAccount} intake_id={$wrongIntake->id} no sibling found, clearing non-admit data"
                    );

                    DB::table('intake')
                        ->where('id', $wrongIntake->id)
                        ->update([
                            'intake_outcome' => null,
                            'intake_bucket' => null,
                            'intake_nonadmit_reason' => null,
                            'updated_at' => now(),
                        ]);
                    $this->fixedCleared++;
                }
            }
        }
    }

    private function handleOrphanDuplicates(array $allMysqlRecords, string $patientAccount): void
    {
        $counts = [];
        foreach ($allMysqlRecords as $record) {
            if (!$record->IntakeID || $record->is_deleted == 1) {
                continue;
            }
            $key = $record->IntakeID . '_' . $record->CLIENT_INTAKE_SEQUENCE_NO;
            $counts[$key][] = $record;
        }

        $hasDupes = false;
        foreach ($counts as $key => $records) {
            if (count($records) <= 1) {
                continue;
            }

            $hasDupes = true;
            [$intakeId] = explode('_', $key);

            usort($records, fn($a, $b) => $b->id <=> $a->id);

            $best = $this->selectBestRecord($records, $patientAccount, (int)$intakeId);
            if (!$best) {
                continue;
            }

            foreach ($records as $record) {
                if ($record->id !== $best->id) {
                    $this->log(
                        "CASE6 DEDUP: PA={$patientAccount} IntakeID={$intakeId} deleted id={$record->id} kept id={$best->id}"
                    );
                    DB::table($this->tableName)
                        ->where('id', $record->id)
                        ->update(['is_deleted' => 1, 'IsLatestAdmit' => 0, 'updated_at' => now()]);
                    $this->fixedDupes++;
                }
            }
        }

        if (!$hasDupes) {
            $this->skipped++;
        }
    }

    // -----------------------------------------------------------------------
    // Select best record from candidates
    //
    // Priority:
    //   1. intake_bucket = 'intake_admitted' (most recently updated)
    //   2. Most recently updated linked intake (any bucket)
    //   3. MAX(id)
    // -----------------------------------------------------------------------
    private function selectBestRecord(array $candidates, string $patientAccount, int $intakeId): ?object
    {
        $candidateIds = array_column($candidates, 'id');

        $intakeRecords = DB::table('intake')
            ->whereIn('aoc_patient_id', $candidateIds)
            ->orderByDesc('updated_at')
            ->get()
            ->groupBy('aoc_patient_id')
            ->toArray();

        // Priority 1: intake_admitted
        $admittedCandidates = [];
        foreach ($candidates as $candidate) {
            $intakes = $intakeRecords[$candidate->id] ?? [];
            foreach ($intakes as $intake) {
                if ($intake->intake_bucket === 'intake_admitted') {
                    $admittedCandidates[] = [
                        'record' => $candidate,
                        'intake_updated' => $intake->updated_at,
                    ];
                    break;
                }
            }
        }

        if (!empty($admittedCandidates)) {
            usort($admittedCandidates, fn($a, $b) => strcmp($b['intake_updated'], $a['intake_updated']));
            $best = $admittedCandidates[0]['record'];
            $this->log("BEST by intake_admitted: PA={$patientAccount} IntakeID={$intakeId} id={$best->id}");
            return $best;
        }

        // Priority 2: most recently updated intake
        $withIntake = [];
        foreach ($candidates as $candidate) {
            $intakes = $intakeRecords[$candidate->id] ?? [];
            if (!empty($intakes)) {
                $withIntake[] = [
                    'record' => $candidate,
                    'intake_updated' => $intakes[0]->updated_at,
                ];
            }
        }

        if (!empty($withIntake)) {
            usort($withIntake, fn($a, $b) => strcmp($b['intake_updated'], $a['intake_updated']));
            $best = $withIntake[0]['record'];
            $this->log("BEST by latest intake: PA={$patientAccount} IntakeID={$intakeId} id={$best->id}");
            return $best;
        }

        // Priority 3: MAX(id)
        $best = $candidates[0];
        $this->log("BEST by MAX(id): PA={$patientAccount} IntakeID={$intakeId} id={$best->id}");
        return $best;
    }

    private function recalculateLatestAdmits(): void
    {
        DB::statement(
            "UPDATE mssql_patients mp
            JOIN (
                SELECT p.PatientAccount,
                       (SELECT p2.id FROM mssql_patients p2
                        WHERE p2.PatientAccount = p.PatientAccount
                          AND p2.is_deleted = 0
                          AND p2.not_in_kantime <> 1
                        ORDER BY
                            CASE
                                WHEN p2.Patient_CurrentStatusID IN (1,6,7,8,10) THEN 1
                                WHEN p2.Patient_CurrentStatusID = 4 AND p2.IsNonAdmit = 0 THEN 2
                                WHEN p2.Patient_CurrentStatusID = 7 THEN 3
                                ELSE 4
                            END ASC,
                            p2.is_intake DESC,
                            p2.IsNonAdmit ASC,
                            CASE WHEN p2.IntakeID IS NULL THEN 1 ELSE 0 END ASC,
                            p2.IntakeID DESC,
                            p2.CLIENT_INTAKE_SEQUENCE_NO DESC
                        LIMIT 1) as winner_id
                FROM mssql_patients p
                WHERE p.is_deleted = 0
                  AND p.PatientAccount <> ''
                  AND p.not_in_kantime <> 1
                GROUP BY p.PatientAccount
            ) winners ON mp.PatientAccount = winners.PatientAccount
            SET mp.IsLatestAdmit = CASE WHEN mp.id = winners.winner_id THEN 1 ELSE 0 END
            WHERE mp.is_deleted = 0
              AND mp.PatientAccount <> ''
              AND mp.not_in_kantime <> 1"
        );

        $this->info('IsLatestAdmit recalculated.');
    }

    private function runDiagnostics(string $label = ''): void
    {
        $this->info("--- Diagnostics {$label} ---");

        $activeDupes = DB::select(
            "
            SELECT COUNT(*) as cnt FROM (
                SELECT IntakeID, CLIENT_INTAKE_SEQUENCE_NO
                FROM {$this->tableName}
                WHERE is_deleted = 0
                  AND IntakeID IS NOT NULL AND IntakeID != 0
                GROUP BY IntakeID, CLIENT_INTAKE_SEQUENCE_NO
                HAVING COUNT(*) > 1
            ) t
        "
        );
        $this->info("  Active dupes (IntakeID+SEQ)       : " . ($activeDupes[0]->cnt ?? 0));

        $multiLatest = DB::select(
            "
            SELECT COUNT(*) as cnt FROM (
                SELECT PatientAccount
                FROM {$this->tableName}
                WHERE is_deleted = 0
                  AND IsLatestAdmit = 1
                  AND PatientAccount <> ''
                GROUP BY PatientAccount
                HAVING COUNT(*) > 1
            ) t
        "
        );
        $this->info("  Multiple IsLatestAdmit=1          : " . ($multiLatest[0]->cnt ?? 0));

        $wrongActive = DB::select(
            "
            SELECT COUNT(DISTINCT mp_min.PatientAccount) as cnt
            FROM {$this->tableName} mp_min
            JOIN {$this->tableName} mp_max
                ON mp_max.IntakeID = mp_min.IntakeID
                AND mp_max.CLIENT_INTAKE_SEQUENCE_NO = mp_min.CLIENT_INTAKE_SEQUENCE_NO
                AND mp_max.id > mp_min.id
            JOIN intake i_min ON i_min.aoc_patient_id = mp_min.id
            JOIN intake i_max ON i_max.aoc_patient_id = mp_max.id
            WHERE mp_min.is_deleted = 0
              AND mp_max.is_deleted = 1
              AND i_min.intake_bucket = 'intake_pending_non_admit'
              AND i_max.intake_bucket = 'intake_admitted'
        "
        );
        $this->info("  Wrong active (non-admit/admitted)  : " . ($wrongActive[0]->cnt ?? 0));

        $wrongIntakeLink = DB::select(
            "
            SELECT COUNT(*) as cnt
            FROM {$this->tableName} mp
            JOIN intake i ON i.aoc_patient_id = mp.id
            WHERE mp.is_deleted = 0
              AND mp.IsLatestAdmit = 1
              AND mp.IsNonAdmit = 0
              AND i.intake_outcome IN ('Non Admit', 'Approve Non Admit')
              AND IFNULL(i.intake_nonadmit_reason, '') <> ''
        "
        );
        $this->info("  Wrong intake link (active+nonAdmit): " . ($wrongIntakeLink[0]->cnt ?? 0));

        $this->info('-------------------');
    }

    private function printFinalReport(): void
    {
        $this->info('');
        $this->info('=== Fix NonAdmit Complete ===');
        $this->info("Snowflake rows processed : {$this->snowflakeTotal}");
        $this->info("IsNonAdmit fixed         : {$this->fixedIsNonAdmit}");
        $this->info("Duplicates removed       : {$this->fixedDupes}");
        $this->info("Records restored         : {$this->fixedRestored}");
        $this->info("Intake relinked          : {$this->fixedRelinked}");
        $this->info("Intake cleared           : {$this->fixedCleared}");
        $this->info("Skipped                  : {$this->skipped}");
        $this->info("Errors                   : {$this->errors}");

        $this->info('');
        $this->runDiagnostics('After');

        $this->log(
            "=== DONE isNonAdmit={$this->fixedIsNonAdmit} dupes={$this->fixedDupes} restored={$this->fixedRestored} relinked={$this->fixedRelinked} cleared={$this->fixedCleared} skipped={$this->skipped} errors={$this->errors} ==="
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
