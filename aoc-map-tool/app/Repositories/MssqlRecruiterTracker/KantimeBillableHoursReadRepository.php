<?php

namespace App\Repositories\MssqlRecruiterTracker;

use App\Enums\MssqlScheduleMasterStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class KantimeBillableHoursReadRepository
{
    private const BILLABLE_SCHEDULE_STATUS_IDS = [
        MssqlScheduleMasterStatus::APPROVED->value,
        MssqlScheduleMasterStatus::COMPLETED->value,
        MssqlScheduleMasterStatus::IN_PROGRESS->value,
    ];

    private const ROLLING_WINDOW_MONTHS = 12;

    public function getHighestWeeklyBillableHours(int $aocProviderId, int $aocPatientId): float
    {
        $provider = DB::table('mssql_providers')->where('id', $aocProviderId)->first(['ProviderID']);
        $patient = DB::table('mssql_patients')->where('id', $aocPatientId)->first(['PatientID']);

        if (!$provider || !$patient) {
            return 0.0;
        }

        $weeklyTotal = $this->getWeeklyBillableHoursTotals($provider->ProviderID, $patient->PatientID)
            ->max('weekly_hours');

        return (float) ($weeklyTotal ?? 0);
    }

    private function getWeeklyBillableHoursTotals(int $providerId, int $patientId): Collection
    {
        return DB::table('mssql_schedulemaster')
            ->where('ProviderID', $providerId)
            ->where('PatientID', $patientId)
            ->where('IsBillable', 1)
            ->whereIn('ScheduleStatusID', self::BILLABLE_SCHEDULE_STATUS_IDS)
            ->where('ScheduleDate', '>=', now()->subMonths(self::ROLLING_WINDOW_MONTHS)->startOfWeek())
            ->groupBy('PayrollWeekStartDate')
            ->select(DB::raw('SUM(Hours) as weekly_hours'))
            ->get();
    }
}
