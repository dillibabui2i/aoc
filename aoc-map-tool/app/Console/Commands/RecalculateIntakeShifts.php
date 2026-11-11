<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Intake;
use App\Services\Intake\IntakeShiftInfoService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Recalculates intake_admission_visit / intake_first_billable_visit / intake_completed of the selected intake rows
 * from mssql_schedulemaster, with the same logic as `intake:setshifts2` (IntakeShiftInfoService).
 * Unlike `intake:setshifts2` it does not refresh service_codes_daas flags and is not behind the feature flag.
 *
 * --rollback restores intake_admission_visit / intake_first_billable_visit / intake_completed from the *_original snapshot columns
 * (see migration 2026_10_08_000000_add_original_intake_shift_columns_to_intake_table).
 */
class RecalculateIntakeShifts extends Command
{
    private const COLUMNS = [
        'intake_admission_visit',
        'intake_first_billable_visit',
        'intake_completed',
    ];

    protected $signature = 'intake:recalculate-shifts
                            {--patient-id=* : mssql_patients.PatientID (can be repeated)}
                            {--patient-account=* : mssql_patients.PatientAccount (can be repeated)}
                            {--all : Process every intake row linked to mssql_patients}
                            {--rollback : Restore values from the *_original columns instead of recalculating}
                            {--dry-run : Run inside a transaction that is rolled back, only report affected rows}';

    protected $description = 'Recalculate intake admission / first billable visits from mssql_schedulemaster (or roll back from *_original)';

    public function handle(IntakeShiftInfoService $intakeShiftInfo): int
    {
        $patientIds = $this->listOption('patient-id');
        $patientAccounts = $this->listOption('patient-account');
        $all = (bool) $this->option('all');
        $rollback = (bool) $this->option('rollback');
        $dryRun = (bool) $this->option('dry-run');

        if (!$all && $patientIds === [] && $patientAccounts === []) {
            $this->error('Pass --patient-id, --patient-account or --all.');
            return self::INVALID;
        }

        if ($all && ($patientIds !== [] || $patientAccounts !== [])) {
            $this->error('--all cannot be combined with --patient-id / --patient-account.');
            return self::INVALID;
        }

        $this->info(($dryRun ? '[DRY RUN] ' : '') . ($rollback ? 'Rollback from *_original' : 'Recalculate from mssql_schedulemaster') . '...');

        try {
            $intakeIds = $all ? null : $this->scopedQuery($patientIds, $patientAccounts)->pluck('intake.id')->all();

            DB::beginTransaction();

            $affected = $rollback
                ? ['rollback from *_original' => $this->rollback($intakeIds)]
                : $intakeShiftInfo->recalculate($intakeIds);

            $dryRun ? DB::rollBack() : DB::commit();
        } catch (Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            logger()->error('intake:recalculate-shifts failed', [
                'rollback' => $rollback,
                'patient_ids' => $patientIds,
                'patient_accounts' => $patientAccounts,
                'message' => $e->getMessage(),
            ]);
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($intakeIds !== null) {
            $this->line('Intake rows in scope: ' . count($intakeIds));
        }

        $this->table(
            ['step', $dryRun ? 'rows that would change' : 'rows updated'],
            array_map(fn (string $step, int $rows) => [$step, $rows], array_keys($affected), $affected)
        );

        return self::SUCCESS;
    }

    /**
     * @param int[]|null $intakeIds null = all intake rows linked to mssql_patients
     */
    private function rollback(?array $intakeIds): int
    {
        $query = Intake::join('mssql_patients as mp', 'mp.id', '=', 'intake.aoc_patient_id')
            ->when($intakeIds !== null, fn (Builder $query) => $query->whereIn('intake.id', $intakeIds))
            ->where(function (Builder $query) {
                foreach (self::COLUMNS as $column) {
                    $query->orWhereRaw("NOT (intake.{$column} <=> intake.{$column}_original)");
                }
            });

        $values = [];
        foreach (self::COLUMNS as $column) {
            $values["intake.{$column}"] = DB::raw("intake.{$column}_original");
        }
        $values['intake.updated_at'] = now()->format('Y-m-d H:i:s');

        return $query->update($values);
    }

    private function scopedQuery(array $patientIds, array $patientAccounts): Builder
    {
        return Intake::join('mssql_patients as mp', 'mp.id', '=', 'intake.aoc_patient_id')
            ->where(function (Builder $query) use ($patientIds, $patientAccounts) {
                if ($patientIds !== []) {
                    $query->orWhereIn('mp.PatientID', $patientIds);
                }
                if ($patientAccounts !== []) {
                    $query->orWhereIn('mp.PatientAccount', $patientAccounts);
                }
            });
    }

    private function listOption(string $name): array
    {
        return array_values(array_filter(array_map('trim', (array) $this->option($name)), 'strlen'));
    }
}
