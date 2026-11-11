<?php

namespace App\Repositories\CCC;

use Illuminate\Support\Facades\DB;

class CccFollowupCallsReadRepository
{
    public function getLastClientCallCycleStart(int $patientId): ?string
    {
        $last = DB::table('ccc_followup_calls')
            ->select('week_month_start')
            ->where('type', 'client')
            ->where('aoc_patient_id', $patientId)
            ->orderBy('created_at', 'DESC')
            ->first();

        return $last?->week_month_start;
    }

    public function clientCallExistsForCycle(int $patientId, string $cycleStart): bool
    {
        return DB::table('ccc_followup_calls')
            ->where('type',           'client')
            ->where('aoc_patient_id', $patientId)
            ->where('week_month_start', $cycleStart)
            ->exists();
    }
}
