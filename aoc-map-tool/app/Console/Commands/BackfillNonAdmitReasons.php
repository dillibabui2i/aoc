<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


/**
 * Usage:
 *   php artisan daas:backfill-non-admit-reasons                           — run full backfill
 *   php artisan daas:backfill-non-admit-reasons --dry-run                 — diagnostics only, no DB changes
 *   php artisan daas:backfill-non-admit-reasons --chunk=5000              — custom Snowflake chunk size
 *   php artisan daas:backfill-non-admit-reasons --patient-id=45546-PDN    — run for a single patient account
 *
 * Logic covered by this command:
 *   CASE 1: Snowflake row is marked as non-admit, but CLIENT_NONADMIT_REASON is blank/null
 *           → skip as missing Snowflake reason
 *
 *   CASE 2: Snowflake non-admit row does not have an exact local match
 *           (PatientAccount + IntakeID + CLIENT_INTAKE_SEQUENCE_NO)
 *           → skip as not found locally
 *
 *   CASE 3: Exact local patient row is found, but related intake row is not eligible
 *           based on current backfill rule
 *           → skip as not eligible
 *
 *   CASE 4: Exact local patient row is found, intake row is eligible,
 *           but Non Admit Reason is already filled in the app
 *           → skip as existing reason already set
 *
 *   CASE 5: Exact local patient row is found, intake row is eligible,
 *           app Non Admit Reason is blank, and Snowflake reason is present
 *           → backfill Non Admit Reason from Snowflake
 *
 * Notes:
 *   - Matching is done at the intake level using:
 *       PatientAccount + IntakeID + CLIENT_INTAKE_SEQUENCE_NO
 *   - The current target field is intake.intake_nonadmit_reason
 *   - The current eligibility rule follows report-oriented logic:
 *       intake.intake_outcome = 'Non Admit'
 *       OR Patient_CurrentStatusID = 3
 *   - Dry run never writes to DB; it only logs WOULD_UPDATE entries and summary counters.
 */
class BackfillNonAdmitReasons extends Command
{

    protected $signature = 'daas:backfill-non-admit-reasons
                            {--dry-run : Evaluate and log only, do not update records}
                            {--chunk=5000 : Records per Snowflake chunk}
                            {--patient-id= : Process a single PatientAccount}';

    protected $description = 'Backfill Non Admit Reason from Snowflake/Kantime for eligible intake clients';

    /**
     * confirm which field(s) must be updated
     */
    private const UPDATE_INTAKE_REASON = true;

    private const UPDATE_MSSQL_PATIENT_REASON = false;

    /**
     * confirm which condition is the source of truth for "Non Admit"
     *
     * true  = use report-oriented logic
     * false = use alternative logic later
     */
    private const USE_REPORT_LOGIC = true;

    private bool $isDryRun = false;

    private string $logFile = 'backfill_non_admit_reasons.log';

    private int $snowflakeRowsProcessed = 0;

    private int $patientsEvaluatedCount = 0;

    private int $patientsUpdatedCount = 0;

    private int $patientsSkippedExistingCount = 0;

    private int $patientsSkippedMissingSnowflakeCount = 0;

    private int $patientsSkippedNotEligibleCount = 0;

    private int $patientsNotFoundLocallyCount = 0;

    private int $exactMatchesCount = 0;

    private int $relatedIntakesFoundCount = 0;

    private int $patientsWithMultipleRelatedIntakesCount = 0;

    private int $eligibleIntakesCount = 0;

    private int $wouldUpdateCount = 0;

    private int $updatedIntakesCount = 0;

    private int $notEligibleIntakesCount = 0;

    private int $errorsCount = 0;

    private array $patientsEvaluated = [];

    private array $patientsUpdated = [];

    private array $patientsSkippedExisting = [];

    private array $patientsSkippedMissingSnowflake = [];

    private array $patientsSkippedNotEligible = [];

    private array $patientsNotFoundLocally = [];
    private int $eligibleByIntakeOutcomeCount = 0;

    private int $eligibleByPatientStatusOnlyCount = 0;

    public function handle(): int
    {
        $this->isDryRun = (bool)$this->option('dry-run');
        $chunkSize = (int)$this->option('chunk');
        $patientId = $this->option('patient-id');

        $this->info('=== Backfill Non Admit Reasons ===');
        $this->info('Mode  : ' . ($this->isDryRun ? 'DRY RUN' : 'LIVE'));
        $this->info('Chunk : ' . $chunkSize);
        $this->info(
            'Target: '
            . (self::UPDATE_INTAKE_REASON ? '[intake] ' : '')
            . (self::UPDATE_MSSQL_PATIENT_REASON ? '[mssql_patients]' : '')
        );

        $this->log('=== START ===');

        if ($patientId) {
            $rows = $this->fetchSingleFromSnowflake($patientId);
            $this->snowflakeRowsProcessed += count($rows);
            $this->processChunk($rows);
        } else {
            $offset = 0;

            do {
                $this->info("Fetching chunk offset={$offset} size={$chunkSize}...");
                $rows = $this->fetchChunkFromSnowflake($chunkSize, $offset);
                $count = count($rows);

                $this->snowflakeRowsProcessed += $count;

                if ($count > 0) {
                    $this->processChunk($rows);
                }

                $offset += $chunkSize;

                $this->info(
                    "Chunk done. evaluated={$this->patientsEvaluatedCount} updated={$this->patientsUpdatedCount} " .
                    "skip_existing={$this->patientsSkippedExistingCount} " .
                    "skip_missing_sf={$this->patientsSkippedMissingSnowflakeCount} " .
                    "skip_not_eligible={$this->patientsSkippedNotEligibleCount} " .
                    "not_found={$this->patientsNotFoundLocallyCount} errors={$this->errorsCount}"
                );
            } while ($count === $chunkSize);
        }

        $this->printFinalReport();

        return self::SUCCESS;
    }

    private function fetchChunkFromSnowflake(int $limit, int $offset): array
    {
        $query = "
        SELECT
            CLIENT_PATIENT_ID,
            CLIENT_INTAKE_ID,
            CLIENT_INTAKE_SEQUENCE_NO,
            CLIENT_CURRENT_STATUS,
            CLIENT_STATUS,
            CLIENT_INTAKE_STATUS,
            CLIENT_IS_NON_ADMIT,
            CLIENT_NONADMIT_REASON,
            ETL_UPDATED_TIMESTAMP
        FROM CLIENTMASTER_SVW
        WHERE CLIENT_IS_NON_ADMIT = 1
        ORDER BY CLIENT_PATIENT_ID ASC, CLIENT_INTAKE_ID ASC, CLIENT_INTAKE_SEQUENCE_NO ASC
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
            CLIENT_STATUS,
            CLIENT_INTAKE_STATUS,
            CLIENT_IS_NON_ADMIT,
            CLIENT_NONADMIT_REASON,
            ETL_UPDATED_TIMESTAMP
        FROM CLIENTMASTER_SVW
        WHERE CLIENT_PATIENT_ID = '{$safe}'
          AND CLIENT_IS_NON_ADMIT = 1
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

        foreach ($grouped as $patientAccount => $snowflakeRows) {
            try {
                $this->processPatient($patientAccount, $snowflakeRows);
            } catch (\Throwable $e) {
                $this->errorsCount++;
                $this->logError("PatientAccount={$patientAccount} error: {$e->getMessage()}");
            }
        }
    }

    private function processPatient(string $patientAccount, array $snowflakeRows): void
    {
        $this->patientsEvaluatedCount++;
        $this->patientsEvaluated[] = $patientAccount;

        $localPatientsExist = DB::table('mssql_patients')
            ->where('PatientAccount', $patientAccount)
            ->exists();

        if (!$localPatientsExist) {
            $this->patientsNotFoundLocallyCount++;
            $this->patientsNotFoundLocally[] = $patientAccount;
            $this->log("NOT_FOUND_LOCAL patient={$patientAccount}");
            return;
        }

        $patientUpdated = false;
        $patientSkippedExisting = false;
        $patientSkippedMissingSnowflake = false;
        $patientHadEligibleRecords = false;
        $patientHadExactMatch = false;

        foreach ($snowflakeRows as $snowflakeRow) {
            $result = $this->processSnowflakeRow($patientAccount, $snowflakeRow);

            $patientUpdated = $patientUpdated || $result['updated'];
            $patientSkippedExisting = $patientSkippedExisting || $result['skipped_existing'];
            $patientSkippedMissingSnowflake = $patientSkippedMissingSnowflake || $result['skipped_missing_snowflake'];
            $patientHadEligibleRecords = $patientHadEligibleRecords || $result['had_eligible_records'];
            $patientHadExactMatch = $patientHadExactMatch || $result['had_exact_match'];
        }

        if ($patientUpdated) {
            $this->patientsUpdatedCount++;
            $this->patientsUpdated[] = $patientAccount;
            return;
        }

        if ($patientSkippedExisting) {
            $this->patientsSkippedExistingCount++;
            $this->patientsSkippedExisting[] = $patientAccount;
            return;
        }

        if ($patientSkippedMissingSnowflake) {
            $this->patientsSkippedMissingSnowflakeCount++;
            $this->patientsSkippedMissingSnowflake[] = $patientAccount;
            return;
        }

        if (!$patientHadExactMatch) {
            $this->patientsNotFoundLocallyCount++;
            $this->patientsNotFoundLocally[] = $patientAccount;
            $this->log("NOT_FOUND_EXACT_MATCH patient={$patientAccount}");
            return;
        }

        if (!$patientHadEligibleRecords) {
            $this->patientsSkippedNotEligibleCount++;
            $this->patientsSkippedNotEligible[] = $patientAccount;
            $this->log("SKIP_NOT_ELIGIBLE patient={$patientAccount}");
        }
    }

    private function isEligibleForBackfill(object $patient, object $intake): bool
    {
        if (self::USE_REPORT_LOGIC) {
            return $this->isEligibleByIntakeOutcome($intake)
                || $this->isEligibleByPatientStatusOnly($patient, $intake);
        }

        return false;
    }

    private function isEligibleByIntakeOutcome(object $intake): bool
    {
        return $intake->intake_outcome === 'Non Admit';
    }

    private function isEligibleByPatientStatusOnly(object $patient, object $intake): bool
    {
        return !$this->isEligibleByIntakeOutcome($intake)
            && (int)($patient->Patient_CurrentStatusID ?? 0) === 3;
    }

    private function needsBackfill(object $patient, object $intake): bool
    {
        if (self::UPDATE_INTAKE_REASON) {
            return $this->isBlank($intake->intake_nonadmit_reason ?? null);
        }

        if (self::UPDATE_MSSQL_PATIENT_REASON) {
            return $this->isBlank($patient->intake_nonadmit_reason ?? null);
        }

        return false;
    }

    private function hasExistingReason(object $patient, object $intake): bool
    {
        if (self::UPDATE_INTAKE_REASON && !$this->isBlank($intake->intake_nonadmit_reason ?? null)) {
            return true;
        }

        if (self::UPDATE_MSSQL_PATIENT_REASON && !$this->isBlank($patient->intake_nonadmit_reason ?? null)) {
            return true;
        }

        return false;
    }

    private function applyBackfill(object $patient, object $intake, string $reason): void
    {
        if ($this->isDryRun) {
            return;
        }

        DB::transaction(function () use ($patient, $intake, $reason) {
            if (self::UPDATE_INTAKE_REASON) {
                DB::table('intake')
                    ->where('id', $intake->id)
                    ->update([
                        'intake_nonadmit_reason' => $reason,
                        'updated_at' => now(),
                    ]);
            }

            if (self::UPDATE_MSSQL_PATIENT_REASON) {
                DB::table('mssql_patients')
                    ->where('id', $patient->id)
                    ->update([
                        'intake_nonadmit_reason' => $reason,
                        'updated_at' => now(),
                    ]);
            }
        });
    }

    private function processSnowflakeRow(string $patientAccount, array $snowflakeRow): array
    {
        $result = [
            'updated' => false,
            'skipped_existing' => false,
            'skipped_missing_snowflake' => false,
            'had_eligible_records' => false,
            'had_exact_match' => false,
        ];

        $sfIntakeId = (int)($snowflakeRow['CLIENT_INTAKE_ID'] ?? 0);
        $sfSeq = (int)($snowflakeRow['CLIENT_INTAKE_SEQUENCE_NO'] ?? 0);

        $snowflakeReason = $this->normalizeNullableString($snowflakeRow['CLIENT_NONADMIT_REASON'] ?? null);

        if ($snowflakeReason === null) {
            $result['skipped_missing_snowflake'] = true;

            $this->log(
                "SKIP_MISSING_SNOWFLAKE patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq}"
            );

            return $result;
        }

        $localPatient = DB::table('mssql_patients')
            ->where('PatientAccount', $patientAccount)
            ->where('IntakeID', $sfIntakeId)
            ->where('CLIENT_INTAKE_SEQUENCE_NO', $sfSeq)
            ->orderByDesc('id')
            ->first();

        if (!$localPatient) {
            $this->log(
                "NOT_FOUND_EXACT_ROW patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq}"
            );

            return $result;
        }

        $result['had_exact_match'] = true;
        $this->exactMatchesCount++;

        $relatedIntakes = DB::table('intake')
            ->where('aoc_patient_id', $localPatient->id)
            ->orderByDesc('id')
            ->get();

        $relatedCount = $relatedIntakes->count();
        $this->relatedIntakesFoundCount += $relatedCount;

        if ($relatedCount > 1) {
            $this->patientsWithMultipleRelatedIntakesCount++;

            $this->log(
                "MULTIPLE_RELATED_INTAKES patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq} " .
                "local_patient_id={$localPatient->id} related_count={$relatedCount}"
            );
        } else {
            $this->log(
                "EXACT_MATCH patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq} " .
                "local_patient_id={$localPatient->id} related_count={$relatedCount}"
            );
        }

        foreach ($relatedIntakes as $intake) {
            if (!$this->isEligibleForBackfill($localPatient, $intake)) {
                $this->notEligibleIntakesCount++;

                $this->log(
                    "SKIP_NOT_ELIGIBLE_INTAKE patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq} " .
                    "intake_id={$intake->id} local_patient_id={$localPatient->id} " .
                    "intake_outcome=" . ($intake->intake_outcome ?? 'NULL') . " " .
                    "Patient_CurrentStatusID=" . (int)($localPatient->Patient_CurrentStatusID ?? 0)
                );

                continue;
            }

            $result['had_eligible_records'] = true;
            $this->eligibleIntakesCount++;

            if ($this->isEligibleByIntakeOutcome($intake)) {
                $this->eligibleByIntakeOutcomeCount++;

                $this->log(
                    "ELIGIBLE_BY_INTAKE_OUTCOME patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq} " .
                    "intake_id={$intake->id} local_patient_id={$localPatient->id} " .
                    "intake_outcome=" . ($intake->intake_outcome ?? 'NULL')
                );
            } elseif ($this->isEligibleByPatientStatusOnly($localPatient, $intake)) {
                $this->eligibleByPatientStatusOnlyCount++;

                $this->log(
                    "ELIGIBLE_BY_PATIENT_STATUS_ONLY patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq} " .
                    "intake_id={$intake->id} local_patient_id={$localPatient->id} " .
                    "intake_outcome=" . ($intake->intake_outcome ?? 'NULL') . " " .
                    "Patient_CurrentStatusID=" . (int)($localPatient->Patient_CurrentStatusID ?? 0)
                );
            }


            if ($this->hasExistingReason($localPatient, $intake)) {
                $result['skipped_existing'] = true;

                $this->log(
                    "SKIP_EXISTING patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq} " .
                    "intake_id={$intake->id} local_patient_id={$localPatient->id} " .
                    "existing_reason=\"" . ($intake->intake_nonadmit_reason ?? '') . "\""
                );

                continue;
            }

            if (!$this->needsBackfill($localPatient, $intake)) {
                $this->log(
                    "SKIP_NO_BACKFILL_NEEDED patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq} " .
                    "intake_id={$intake->id} local_patient_id={$localPatient->id}"
                );

                continue;
            }

            if ($this->isDryRun) {
                $this->wouldUpdateCount++;

                $this->log(
                    "WOULD_UPDATE patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq} " .
                    "intake_id={$intake->id} local_patient_id={$localPatient->id} reason=\"{$snowflakeReason}\""
                );
            } else {
                $this->applyBackfill($localPatient, $intake, $snowflakeReason);
                $this->updatedIntakesCount++;

                $this->log(
                    "UPDATED patient={$patientAccount} sf_intake_id={$sfIntakeId} sf_seq={$sfSeq} " .
                    "intake_id={$intake->id} local_patient_id={$localPatient->id} reason=\"{$snowflakeReason}\""
                );
            }

            $result['updated'] = true;
        }

        return $result;
    }

    private function isBlank(mixed $value): bool
    {
        return $this->normalizeNullableString($value) === null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string)$value);

        return $value === '' ? null : $value;
    }

    private function printFinalReport(): void
    {

        $this->info('');
        $this->info('=== Backfill Non Admit Reasons Complete ===');
        $this->info("Snowflake rows processed         : {$this->snowflakeRowsProcessed}");
        $this->info("Patients evaluated               : {$this->patientsEvaluatedCount}");
        $this->info("Patients updated                 : {$this->patientsUpdatedCount}");
        $this->info("Skipped existing reason          : {$this->patientsSkippedExistingCount}");
        $this->info("Skipped missing Snowflake reason : {$this->patientsSkippedMissingSnowflakeCount}");
        $this->info("Skipped not eligible             : {$this->patientsSkippedNotEligibleCount}");
        $this->info("Patients not found locally       : {$this->patientsNotFoundLocallyCount}");
        $this->info("Errors                           : {$this->errorsCount}");
        $this->info("Exact mssql matches             : {$this->exactMatchesCount}");
        $this->info("Related intakes found total     : {$this->relatedIntakesFoundCount}");
        $this->info("Cases with multiple intakes     : {$this->patientsWithMultipleRelatedIntakesCount}");
        $this->info("Eligible intake rows            : {$this->eligibleIntakesCount}");
        $this->info("Not eligible intake rows        : {$this->notEligibleIntakesCount}");
        $this->info("Eligible by intake outcome      : {$this->eligibleByIntakeOutcomeCount}");
        $this->info("Eligible by patient status only : {$this->eligibleByPatientStatusOnlyCount}");

        if ($this->isDryRun) {
            $this->info("Would update intake rows        : {$this->wouldUpdateCount}");
        } else {
            $this->info("Updated intake rows             : {$this->updatedIntakesCount}");
        }

        $this->log('=== SUMMARY START ===');
        $this->log('evaluated_count=' . $this->patientsEvaluatedCount);
        $this->log('evaluated_ids=' . implode(',', $this->patientsEvaluated));
        $this->log('updated_count=' . $this->patientsUpdatedCount);
        $this->log('updated_ids=' . implode(',', $this->patientsUpdated));
        $this->log('skipped_existing_count=' . $this->patientsSkippedExistingCount);
        $this->log('skipped_existing_ids=' . implode(',', $this->patientsSkippedExisting));
        $this->log('skipped_missing_snowflake_count=' . $this->patientsSkippedMissingSnowflakeCount);
        $this->log('skipped_missing_snowflake_ids=' . implode(',', $this->patientsSkippedMissingSnowflake));
        $this->log('skipped_not_eligible_count=' . $this->patientsSkippedNotEligibleCount);
        $this->log('skipped_not_eligible_ids=' . implode(',', $this->patientsSkippedNotEligible));
        $this->log('not_found_local_count=' . $this->patientsNotFoundLocallyCount);
        $this->log('not_found_local_ids=' . implode(',', $this->patientsNotFoundLocally));
        $this->log('errors_count=' . $this->errorsCount);
        $this->log('exact_matches_count=' . $this->exactMatchesCount);
        $this->log('related_intakes_found_count=' . $this->relatedIntakesFoundCount);
        $this->log('patients_with_multiple_related_intakes_count=' . $this->patientsWithMultipleRelatedIntakesCount);
        $this->log('eligible_intakes_count=' . $this->eligibleIntakesCount);
        $this->log('not_eligible_intakes_count=' . $this->notEligibleIntakesCount);
        $this->log('would_update_count=' . $this->wouldUpdateCount);
        $this->log('updated_intakes_count=' . $this->updatedIntakesCount);
        $this->log('eligible_by_intake_outcome_count=' . $this->eligibleByIntakeOutcomeCount);
        $this->log('eligible_by_patient_status_only_count=' . $this->eligibleByPatientStatusOnlyCount);
        $this->log('=== SUMMARY END ===');
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
