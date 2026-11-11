<?php

namespace App\Repositories\CCC;

use Illuminate\Support\Facades\DB;

class CccFollowupCallsWriteRepository
{
    public function deleteOpenClientCallsFrom(int $patientId, string $weekStart, string $monthStart): int
    {
        $from = min($weekStart, $monthStart);

        return DB::table('ccc_followup_calls')
            ->where('type', 'client')
            ->where('aoc_patient_id', $patientId)
            ->whereRaw("IFNULL(outcome,'') = ''")
            ->where('week_month_start', '>=', $from)
            ->delete();
    }

    public function insertClientCallRow(array $row): void
    {
        DB::table('ccc_followup_calls')->insert([$row]);
    }

    public function markPastDueOpenClientCallsNotComplete(string $todayYmd, int $updatedBy = 1): int
    {
        return DB::table('ccc_followup_calls')
            ->where('type', 'client')
            ->whereRaw("ifnull(outcome,'') = ''")
            ->where('due_date', '<', $todayYmd)
            ->update([
                'outcome' => 'Not Complete',
                'updated_at' => now(),
                'updated_by' => $updatedBy
            ]);
    }
}
