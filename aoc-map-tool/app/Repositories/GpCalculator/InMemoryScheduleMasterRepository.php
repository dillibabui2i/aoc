<?php

namespace App\Repositories\GpCalculator;

use Carbon\Carbon;

/**
 * In-memory repository for local/dev usage.
 *
 * You can seed it in tests or from a dev bootstrap.
 */
class InMemoryScheduleMasterRepository implements ScheduleMasterRepositoryInterface
{
    /**
     * @var array<int, array<int, array<string, mixed>>> keyed by patientId
     */
    private array $dataByPatientId;

    public function __construct(array $dataByPatientId = [])
    {
        $this->dataByPatientId = $dataByPatientId;
    }

    public function seedPatient(int $patientId, array $rows): void
    {
        $this->dataByPatientId[$patientId] = $rows;
    }

    /**
     * Provides the data for development environment due to a lack of data. Simulated behaviour based on filters. Service registered as a singleton with env option as decider.
     * @param int $patientId
     * @param bool|null $permOnly
     * @param string|null $startWeek
     * @param string|null $endWeek
     * @param bool|null $serviceWise
     * @return array[]|mixed[][]
     */
    public function getPatientScheduledData(int $patientId, ?bool $permOnly = false, ?string $startWeek = '', ?string $endWeek = '', ?bool $serviceWise = false): array
    {
        $monday = Carbon::now()->startOfWeek(Carbon::MONDAY)->format('m/d/Y');
        $sunday = Carbon::now()->startOfWeek(Carbon::MONDAY)->addDays(6)->format('m/d/Y');

        // Default dev sample
        $default = [
            ['nurseName' => 'Priscilla Guia', 'aoc_provider_id' => 382943, 'aoc_candidate_id' => 0, 'discipline' => 'LVN/LPN MDCP', 'preHours' => 10, 'hours' => 10, 'desiredRate' => 22.5, 'isOldNurse' => 1, 'priority' => 1],
            ['nurseName' => 'Priscilla Guia', 'aoc_provider_id' => 382943, 'aoc_candidate_id' => 0, 'discipline' => 'RESPITE LVN/LPN MDCP', 'preHours' => 10, 'hours' => 10, 'desiredRate' => 22.5, 'isOldNurse' => 1, 'priority' => 3],
        ];
        //adds up data to display coverage case
        if (!$permOnly) {
            $default[] = ['nurseName' => 'Priscilla Guia', 'aoc_provider_id' => 382943, 'aoc_candidate_id' => 0, 'discipline' => 'Respite Coverage LVN/LPN MDCP', 'preHours' => 10, 'hours' => 10, 'desiredRate' => 22.5, 'isOldNurse' => 1, 'priority' => 3];
        }
        //adds up data to display coverage case within current week
        if (!$permOnly && $monday == $startWeek && $sunday == $endWeek) {
            $default[] = ['nurseName' => 'Priscilla Guia', 'aoc_provider_id' => 382943, 'aoc_candidate_id' => 0, 'discipline' => 'Coverage LVN/LPN MDCP', 'preHours' => 10, 'hours' => 10, 'desiredRate' => 22.5, 'isOldNurse' => 1, 'priority' => 1];
        }

        return $this->dataByPatientId[$patientId] ?? $default;
    }
}
