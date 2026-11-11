<?php

namespace App\Console\Commands;

use App\Helpers\H;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupTexasRnSupervisoryVisits extends Command
{
    private const STATE = 'TX';

    private const DISCIPLINE = 'RN';

    protected $signature = 'supervisoryvisits:cleanup-tx-rn
        {cutoff=2026-08-23 : Soft-delete records with Next Sup Visit strictly before this date (Y-m-d)}
        {--dry-run : List matching records without soft-deleting them}';

    protected $description = 'Soft-delete Supervisory Visit Tracking records for State=TX, Discipline=RN with Next Sup Visit before the given cutoff date';

    protected string $logFile = 'supervisory_visits_backend.log';

    public function handle(): int
    {
        $cutoff = $this->argument('cutoff');
        $isDryRun = (bool) $this->option('dry-run');

        $matches = DB::table('supervisory_visits')
            ->select(
                'supervisory_visits.id',
                'mssql_providers.ProviderID',
                'mssql_patients.PatientID',
                'supervisory_visits.visit_date_due'
            )
            ->join('mssql_providers', 'mssql_providers.id', '=', 'supervisory_visits.aoc_provider_id')
            ->join('mssql_patients', 'mssql_patients.id', '=', 'supervisory_visits.aoc_patient_id')
            ->join('mssql_facilities', 'mssql_facilities.id', '=', 'mssql_patients.aoc_facility_id')
            ->where('mssql_facilities.State', self::STATE)
            ->where('mssql_providers.DisciplineName', self::DISCIPLINE)
            ->whereDate('supervisory_visits.visit_date_due', '<', $cutoff)
            ->active('supervisory_visits')
            ->get();

        $this->info(sprintf(
            'Found %d matching record(s) [State=%s, Discipline=%s, Next Sup Visit < %s].',
            $matches->count(),
            self::STATE,
            self::DISCIPLINE,
            $cutoff
        ));

        if ($matches->isEmpty()) {
            return 0;
        }

        foreach ($matches as $match) {
            $this->line(sprintf(
                'id=%d ProviderID=%s PatientID=%s visit_date_due=%s',
                $match->id,
                $match->ProviderID,
                $match->PatientID,
                $match->visit_date_due
            ));
        }

        if ($isDryRun) {
            $this->info('Dry run: no records were modified.');
            return 0;
        }

        $ids = $matches->pluck('id')->all();
        $failedIds = [];

        foreach ($ids as $id) {
            try {
                DB::table('supervisory_visits')
                    ->where('id', $id)
                    ->active()
                    ->softDelete();
            } catch (\Throwable $e) {
                $failedIds[] = $id;
                $this->error(sprintf('Failed to soft-delete id=%d: %s', $id, $e->getMessage()));
            }
        }

        $deletedIds = array_diff($ids, $failedIds);

        H::ExcelLog(
            sprintf('Cleanup TX/RN sup visits before %s: %s', $cutoff, implode(',', $deletedIds)),
            $this->logFile
        );

        if (!empty($failedIds)) {
            H::ExcelLog(
                sprintf('Cleanup TX/RN sup visits before %s FAILED (likely duplicate business key): %s', $cutoff, implode(',', $failedIds)),
                $this->logFile
            );
            $this->warn(sprintf(
                '%d record(s) could not be soft-deleted (likely conflict with a duplicate active record sharing the same provider/patient/visit_date_due/last_visit) and need manual review: %s',
                count($failedIds),
                implode(',', $failedIds)
            ));
        }

        $this->info(sprintf('Soft-deleted %d record(s).', count($deletedIds)));

        return 0;
    }
}
