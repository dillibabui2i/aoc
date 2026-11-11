<?php

namespace App\Services\CCC;

use App\Enums\MonthlyCallsEnum;
use App\Repositories\MssqlPatients\MssqlPatientsReadRepository;
use App\Repositories\CCC\CccFollowupCallsReadRepository;
use App\Repositories\CCC\CccFollowupCallsWriteRepository;
use Carbon\Carbon;

class MonthlyCallsRecalculatorService
{
    public function __construct(
        private MonthlyCallsScheduleService $schedule,
        private MssqlPatientsReadRepository $patientsRead,
        private CccFollowupCallsReadRepository $callsRead,
        private CccFollowupCallsWriteRepository $callsWrite
    ) {
    }

    public function recalculateForPatient(int $patientId, int $newFrequencyRaw, int $userId): void
    {
        $freq = MonthlyCallsEnum::fromDb($newFrequencyRaw);

        $weekStart = $this->schedule->currentWeekStart();
        $weekEnd = $this->schedule->currentWeekEnd();
        $monthStart = $this->schedule->currentMonthStart();
        $monthEnd = $this->schedule->currentMonthEnd();

        $this->callsWrite->deleteOpenClientCallsFrom(
            $patientId,
            $weekStart->toDateString(),
            $monthStart->toDateString()
        );

        if ($freq->isInactive()) {
            return;
        };

        $patient = $this->patientsRead->getById($patientId);
        if (!$patient) {
            return;
        }

        $lastCycleStartStr = $this->callsRead->getLastClientCallCycleStart($patientId);
        $anchor = $lastCycleStartStr ? Carbon::parse($lastCycleStartStr) : null;

        $should = $this->schedule->shouldGenerateForCurrentCycleWithHistory(
            $freq,
            $weekStart,
            $monthStart,
            $anchor
        );

        if (!$should) {
            return;
        }

        $cycleStart = $this->schedule->currentCycleStart($freq, $weekStart, $monthStart);
        $dueDate = $this->schedule->currentDueDate($freq, $weekEnd, $monthEnd);

        $this->callsWrite->insertClientCallRow([
            'aoc_patient_id' => $patientId,
            'aoc_nthaccount_id' => 0,
            'start_date' => $cycleStart->toDateString(),
            'base_date' => $cycleStart->toDateString(),
            'ccc_id' => (int)$patient->ccc_id,
            'created_at' => Carbon::now()->toDateTimeString(),
            'created_by' => $userId,
            'updated_at' => Carbon::now()->toDateTimeString(),
            'updated_by' => $userId,
            'due_date' => $dueDate->toDateString(),
            'type' => 'client',
            'week_month_start' => $cycleStart->toDateString(),
        ]);
    }
}
