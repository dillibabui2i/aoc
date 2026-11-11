<?php

use App\MssqlRecruitertracker;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use App\User;
use App\Enums\MssqlRecreturTrackerStatus;
use App\Enums\MssqlRecruiterTrackerLiveIpeaStatus;
use App\Enums\MssqlRecruiterTrackerActiveCoverageOutcome;

return new class extends Migration
{
    public function up()
    {
        $currentDate = Carbon::now()->format("Y-m-d H:i:s");

        MssqlRecruitertracker::query()
            ->where('Candidate_Start_Date', '>', '2022-12-31')
            ->where('Candidate_Start_Date', '<', '2026-01-01')
            ->where('Hours_Worked', '>=', 40)
            ->where('is_active', 1)
            ->where('Candidate_Current_Status','!=', MssqlRecreturTrackerStatus::INACTIVE->value)
            ->where('live_ipea_status', MssqlRecruiterTrackerLiveIpeaStatus::READY_TO_WORK->value)
            ->where('is_prn_confirmed', 0)
            ->update([
                'is_prn_confirmed' => 1,
                'prn_confirmed_date' => $currentDate,
                'prn_confirmed_by' => User::SYSTEM_ID,
                'activecoverage_outcome' => MssqlRecruiterTrackerActiveCoverageOutcome::CONFIRM_COMPLETED->value,
            ]);
    }

    public function down()
    {
        MssqlRecruitertracker::query()
            ->where('Candidate_Start_Date', '>', '2022-12-31')
            ->where('Candidate_Start_Date', '<', '2026-01-01')
            ->where('Hours_Worked', '>=', 40)
            ->where('is_active', 1)
            ->where('Candidate_Current_Status', '!=', MssqlRecreturTrackerStatus::INACTIVE->value)
            ->where('live_ipea_status',MssqlRecruiterTrackerLiveIpeaStatus::READY_TO_WORK->value)
            ->where('is_prn_confirmed', User::SYSTEM_ID)
            ->where('prn_confirmed_date', '>', '2026-09-01')
            ->where('prn_confirmed_by', User::SYSTEM_ID)
            ->where('activecoverage_outcome', MssqlRecruiterTrackerActiveCoverageOutcome::CONFIRM_COMPLETED->value)
            ->update([
                'is_prn_confirmed' => 0,
                'prn_confirmed_date' => null,
                'prn_confirmed_by' => null,
                'activecoverage_outcome' => ''
            ]);
    }
};
