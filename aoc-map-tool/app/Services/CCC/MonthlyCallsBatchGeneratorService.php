<?php

namespace App\Services\CCC;

use App\Enums\MonthlyCallsEnum;
use App\Repositories\CCC\CccFollowupCallsReadRepository;
use App\Repositories\CCC\CccFollowupCallsWriteRepository;
use App\Repositories\MssqlPatients\MssqlPatientsReadRepository;
use Carbon\Carbon;
use Carbon\CarbonImmutable;

class MonthlyCallsBatchGeneratorService
{
    public function __construct(
        private MonthlyCallsScheduleService $schedule,
        private MssqlPatientsReadRepository $patientsRead,
        private CccFollowupCallsReadRepository $callsRead,
        private CccFollowupCallsWriteRepository $callsWrite
    ) {
    }

    public function generateForCurrentCycles(int $actorUserId = 1): int
    {
        $today = $this->schedule->today();
        $weekStart = $this->schedule->currentWeekStart();
        $weekEnd = $this->schedule->currentWeekEnd();
        $monthStart = $this->schedule->currentMonthStart();
        $monthEnd = $this->schedule->currentMonthEnd();

        $patients = $this->patientsRead->getEligibleForCccCalls($monthStart->toDateString());

        $attempted = 0;

        foreach ($patients as $p) {
            $freq = MonthlyCallsEnum::fromDb($p->ccc_client_call_frequency);

            if ($freq->isInactive()) {
                continue;
            }

            $lastCycleStartStr = $this->callsRead->getLastClientCallCycleStart((int)$p->id);
            $anchor = $lastCycleStartStr ? Carbon::parse($lastCycleStartStr) : null;

            $should = $this->schedule->shouldGenerateForCurrentCycleWithHistory(
                $freq,
                $weekStart,
                $monthStart,
                $anchor
            );

            if (!$should) {
                continue;
            }

            $cycleStart = $this->schedule->currentCycleStart($freq, $weekStart, $monthStart);
            $dueDate = $this->schedule->currentDueDate($freq, $weekEnd, $monthEnd);

            $alreadyExists = $this->callsRead->clientCallExistsForCycle(
                (int)$p->id,
                $cycleStart->toDateString()
            );

            if ($alreadyExists) {
                continue;
            }

            $this->callsWrite->insertClientCallRow([
                'aoc_patient_id' => (int)$p->id,
                'aoc_nthaccount_id' => 0,
                'start_date' => $cycleStart->toDateString(),
                'base_date' => $cycleStart->toDateString(),
                'ccc_id' => (int)$p->ccc_id,
                'created_at' => CarbonImmutable::now()->toDateTimeString(),
                'created_by' => $actorUserId,
                'updated_at' => CarbonImmutable::now()->toDateTimeString(),
                'updated_by' => $actorUserId,
                'due_date' => $dueDate->toDateString(),
                'type' => 'client',
                'week_month_start' => $cycleStart->toDateString(),
            ]);

            $attempted++;
        }

        $this->callsWrite->markPastDueOpenClientCallsNotComplete($today->toDateString());

        return $attempted;
    }
}
