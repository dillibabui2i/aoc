<?php

namespace App\Repositories\GpCalculator;

use App\DTO\GpCalculator\DateBoundariesDTO;
use App\Enums\MssqlScheduleMasterStatus;
use App\Helpers\LD;
use App\MssqlPatients;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ScheduleMasterRepository implements ScheduleMasterRepositoryInterface
{
    public function getPatientScheduledData(int $patientId, $permOnly = false, ?string $startWeek = '', ?string $endWeek = '', ?bool $serviceWise = false) : array
    {
        $patientData = MssqlPatients::find($patientId);
        $mssqlPatientId = $patientData?->PatientID;
        $dateBoundaries = $this->getDateBoundaries($permOnly, $startWeek, $endWeek);

        if ($serviceWise) {
            $requiredStatuses = [
                MssqlScheduleMasterStatus::PLANNED->value,
                MssqlScheduleMasterStatus::IN_PROGRESS->value,
                MssqlScheduleMasterStatus::COMPLETED->value,
                MssqlScheduleMasterStatus::APPROVED->value,
            ];
            $nurseData = DB::table('mssql_schedulemaster')
                ->select([
                    DB::raw('CONCAT(mssql_providers.FirstName," ",mssql_providers.LastName) as nurseName'),
                    DB::raw('mssql_providers.id as aoc_provider_id'),
                    DB::raw('0 as aoc_candidate_id'),
                    DB::raw('service_codes_daas.name as discipline'),
                    DB::raw('1 as isOldNurse'),
                    DB::raw('CASE
                        WHEN service_codes_daas.name NOT LIKE "%RESPITE%" THEN 1
                        WHEN service_codes_daas.name LIKE "%RESPITE%" THEN 3
                    END as priority'),
                ])
                ->selectSub(function ($q) use ($mssqlPatientId, $dateBoundaries, $permOnly, $requiredStatuses) {
                    // Average planned hours (scaled) for the provider across other patients in the date window
                    $q->from('mssql_schedulemaster as msm')
                        ->leftJoin('service_codes_daas as scd', 'scd.service_id', '=', 'msm.ServiceCodeID')
                        ->whereColumn('msm.ProviderID', 'mssql_schedulemaster.ProviderID')
                        ->whereIn('msm.ScheduleStatusID', $requiredStatuses)
                        ->where('msm.PatientID', '!=', $mssqlPatientId)
                        //TODO isChild applied directly until we go with split schedules improvement
                        ->where('msm.isChild', 0)
                        ->whereRaw('scd.is_respite = (CASE WHEN service_codes_daas.name LIKE "%RESPITE%" THEN 1 ELSE 0 END)')
                        ->when($permOnly, function ($qq) {
                            $qq->where('scd.name', 'not like', '%coverage%');
                            $qq->where('msm.isBillable', 1);
                        })
                        ->when(
                            $dateBoundaries->applyRange,
                            function ($qq) use ($dateBoundaries) {
                                $qq->where('msm.ScheduleDate', '>', $dateBoundaries->rangeStartDate)
                                   ->where('msm.ScheduleDate', '<', $dateBoundaries->rangeEndDate);
                            },
                            function ($qq) use ($dateBoundaries) {
                                $qq->where('msm.ScheduleDate', '>', $dateBoundaries->subStartDate)
                                   ->where('msm.ScheduleDate', '<', $dateBoundaries->endDate);
                            }
                        );
                    //if range applied that means we are dealing with perm only = OFF, so we're only selecting in one week range and do not need to divide by 4
                    if ($dateBoundaries->applyRange) {
                        $q->selectRaw('IFNULL(SUM(msm.PlannedHours), 0)');
                    } else {
                        $q->selectRaw('IFNULL(SUM(msm.PlannedHours), 0) / 4');
                    }
                }, 'preHours')
                ->selectSub(function ($q) use ($mssqlPatientId, $dateBoundaries, $permOnly, $requiredStatuses) {
                    // Average planned hours (scaled) for the provider across other patients in the date window
                    $q->from('mssql_schedulemaster as msm')
                        ->leftJoin('service_codes_daas as scd', 'scd.service_id', '=', 'msm.ServiceCodeID')
                        ->whereColumn('msm.ProviderID', 'mssql_schedulemaster.ProviderID')
                        ->whereIn('msm.ScheduleStatusID', $requiredStatuses)
                        ->where('msm.PatientID', '=', $mssqlPatientId)
                        //TODO isChild applied directly until we go with split schedules improvement
                        ->where('msm.isChild', 0)
                        ->whereRaw('scd.is_respite = (CASE WHEN service_codes_daas.name LIKE "%RESPITE%" THEN 1 ELSE 0 END)')
                        ->when($permOnly, function ($qq) {
                            $qq->where('scd.name', 'not like', '%coverage%');
                            $qq->where('msm.isBillable', 1);
                        })
                        ->when(
                            $dateBoundaries->applyRange,
                            function ($qq) use ($dateBoundaries) {
                                $qq->where('msm.ScheduleDate', '>', $dateBoundaries->rangeStartDate)
                                    ->where('msm.ScheduleDate', '<', $dateBoundaries->rangeEndDate);
                            },
                            function ($qq) use ($dateBoundaries) {
                                $qq->where('msm.ScheduleDate', '>', $dateBoundaries->subStartDate)
                                    ->where('msm.ScheduleDate', '<', $dateBoundaries->endDate);
                            }
                        );
                    //if range applied that means we are dealing with perm only = OFF, so we're only selecting in one week range and do not need to divide by 4
                    if ($dateBoundaries->applyRange) {
                        $q->selectRaw('IFNULL(SUM(msm.PlannedHours), 0)');
                    } else {
                        $q->selectRaw('IFNULL(SUM(msm.PlannedHours), 0) / 4');
                    }
                }, 'hours')
                ->selectSub(function ($q) use ($mssqlPatientId, $dateBoundaries, $permOnly, $requiredStatuses) {
                    // Desired rate = total cost / total planned hours for the provider in the date window
                    $q->from('mssql_schedulemaster as msm')
                        ->leftJoin('service_codes_daas as scd', 'scd.service_id', '=', 'msm.ServiceCodeID')
                        ->whereColumn('msm.ProviderID', 'mssql_schedulemaster.ProviderID')
                        ->where('msm.PatientID', '=', $mssqlPatientId)
                        //TODO isChild applied directly until we go with split schedules improvement
                        ->where('msm.isChild', 0)
                        ->whereRaw('scd.is_respite = (CASE WHEN service_codes_daas.name LIKE "%RESPITE%" THEN 1 ELSE 0 END)')
                        ->whereIn('msm.ScheduleStatusID', $requiredStatuses)
                        ->when($permOnly, function ($qq) {
                            $qq->where('scd.name', 'NOT LIKE', '%coverage%');
                            $qq->where('msm.isBillable', 1);
                        })
                        ->when(
                            $dateBoundaries->applyRange,
                            function ($qq) use ($dateBoundaries) {
                                $qq->where('msm.ScheduleDate', '>', $dateBoundaries->rangeStartDate)
                                   ->where('msm.ScheduleDate', '<', $dateBoundaries->rangeEndDate);
                            },
                            function ($qq) use ($dateBoundaries) {
                                $qq->where('msm.ScheduleDate', '>', $dateBoundaries->subStartDate)
                                   ->where('msm.ScheduleDate', '<', $dateBoundaries->endDate);
                            }
                        )
                        // In case when we have no Cost we shouldn't display the row for non-coverage. Works in pair with having
                        ->selectRaw(
                            'CASE
                              WHEN IFNULL(SUM(msm.PlannedHours),0) = 0 THEN NULL
                              WHEN IFNULL(SUM(msm.Cost),0) = 0 THEN NULL
                              ELSE ROUND(SUM(msm.Cost) / SUM(msm.PlannedHours), 2)
                            END'
                        );
                }, 'desiredRate')
                ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', '=', 'mssql_schedulemaster.ProviderID')
                ->leftJoin('service_codes_daas', 'service_codes_daas.service_id', '=', 'mssql_schedulemaster.ServiceCodeID')
                ->where('mssql_providers.LastName', 'NOT LIKE', '\\_U%')
                ->where('PatientID', '=', $mssqlPatientId)
                ->where('ScheduleStatusID', MssqlScheduleMasterStatus::PLANNED->value)
                ->when(
                    $dateBoundaries->applyRange,
                    function ($q) use ($dateBoundaries) {
                        $q->where('ScheduleDate', '>', $dateBoundaries->rangeStartDate)
                            ->where('ScheduleDate', '<', $dateBoundaries->rangeEndDate);
                    },
                    function ($q) use ($dateBoundaries) {
                        $q->where('ScheduleDate', '>', $dateBoundaries->subStartDate)
                            ->where('ScheduleDate', '<', $dateBoundaries->endDate);
                    }
                )
                ->whereNotNull('mssql_schedulemaster.ProviderID')
                ->when($permOnly, function ($q) {
                    $q->where('service_codes_daas.name', 'NOT LIKE', '%coverage%');
                })
                ->orderBy('mssql_schedulemaster.ProviderID')
                ->orderBy('priority')
                ->groupBy('mssql_schedulemaster.ProviderID', 'priority')
                ->havingNotNull('desiredRate')
                ->get()
                ->toArray();
        } else {
            $nurseData = DB::table('mssql_schedulemaster')
                ->select([
                    DB::raw('0 as aoc_candidate_id'),
                    DB::raw('0 as preHours'),
                    DB::raw('1 as isOldNurse'),
                ])
                ->selectSub(function ($q) {
                    $q->from('mssql_providers as p')
                        ->whereColumn('p.ProviderID', 'mssql_schedulemaster.ProviderID')
                        ->where('p.is_deleted', 0)
                        ->select('p.id')
                        ->limit(1);
                }, 'aoc_provider_id')
                ->selectSub(function ($q) {
                    $q->from('mssql_providers as p')
                        ->whereColumn('p.ProviderID', 'mssql_schedulemaster.ProviderID')
                        ->where('p.is_deleted', 0)
                        ->select('p.DisciplineName')
                        ->limit(1);
                }, 'discipline')
                ->selectSub(function ($q) use ($dateBoundaries) {
                    // Average planned hours for this provider+patient within the current month window
                    $q->from('mssql_schedulemaster as msm')
                        ->whereColumn('msm.ProviderID', 'mssql_schedulemaster.ProviderID')
                        ->whereColumn('msm.PatientID', 'mssql_schedulemaster.PatientID')
                        ->where('msm.ScheduleStatusID', MssqlScheduleMasterStatus::PLANNED->value)
                        ->where('msm.ScheduleDate', '>', $dateBoundaries->hoursStartDate)
                        ->where('msm.ScheduleDate', '<', $dateBoundaries->hoursEndDate)
                        ->selectRaw('AVG(msm.PlannedHours)')
                        ->limit(1);
                }, 'hours')
                ->selectSub(function ($q) use ($dateBoundaries) {
                    // Desired rate derived from a historical (completed/in-progress) window
                    $q->from('mssql_schedulemaster as msm')
                        ->whereColumn('msm.ProviderID', 'mssql_schedulemaster.ProviderID')
                        ->whereColumn('msm.PatientID', 'mssql_schedulemaster.PatientID')
                        ->whereIn('msm.ScheduleStatusID', [
                            MssqlScheduleMasterStatus::APPROVED->value,
                            MssqlScheduleMasterStatus::COMPLETED->value,
                            MssqlScheduleMasterStatus::IN_PROGRESS->value,
                        ])
                        ->where('msm.ScheduleDate', '>', $dateBoundaries->desiredRateStartDate)
                        ->where('msm.ScheduleDate', '<', $dateBoundaries->desiredRateEndDate)
                        ->selectRaw('CASE WHEN IFNULL(msm.ActualHours,0) = 0 THEN 0
                            ELSE ROUND(IFNULL(msm.Cost,0) / msm.ActualHours, 2)
                        END')
                        ->limit(1);
                }, 'desiredRate')
                ->leftJoin('mssql_providers', 'mssql_providers.ProviderID', '=', 'mssql_schedulemaster.ProviderID')
                ->where('mssql_providers.LastName', 'NOT LIKE', '\\_U%')
                ->where('PatientID', '=', $mssqlPatientId)
                ->where('ScheduleStatusID', MssqlScheduleMasterStatus::PLANNED->value)
                ->where('ScheduleDate', '>', $dateBoundaries->mainStartDate)
                ->where('ScheduleDate', '<', $dateBoundaries->endDate)
                ->whereNotNull('mssql_schedulemaster.ProviderID')
                ->groupBy('mssql_schedulemaster.ProviderID')
                ->get()
                ->toArray();
        }

        return $nurseData;
    }


    private function getDateBoundaries($permOnly, ?string $startWeek, ?string $endWeek): DateBoundariesDTO
    {
        if (LD::checkFeatureFlag('update-hours-logic-for-new-week')) {
            return $this->getSaturdaySundayDateBoundaries($permOnly, $startWeek, $endWeek);
        }

        // TODO: Remove legacy Sunday/Monday boundaries after release of update-hours-logic-for-new-week.
        return $this->getSundayMondayDateBoundaries($permOnly, $startWeek, $endWeek);
    }

    private function getSundayMondayDateBoundaries($permOnly, ?string $startWeek, ?string $endWeek): DateBoundariesDTO
    {
        // TODO: Remove this legacy Sunday/Monday boundary builder after release of update-hours-logic-for-new-week.
        // Shared date boundaries for the queries below
        $mainStartDate = Carbon::now()->parse('this sunday')->format('Y-m-d');
        $subStartDate = Carbon::now()->previous('Sunday')->format('Y-m-d');
        $endDate = Carbon::now()->next('Monday')->addWeeks(3)->format('Y-m-d');

        // Non-serviceWise (feature flagged)
        $hoursStartDate = Carbon::now()->parse('first Sunday of ' . date('M') . ' ' . date('Y') . '')->format('Y-m-d');
        $hoursEndDate = Carbon::now()->parse('last Monday of ' . date('M') . ' ' . date('Y') . '')->format('Y-m-d');
        $desiredRateStartDate = Carbon::now()->previous('Sunday')->subWeeks(3)->format('Y-m-d');
        $desiredRateEndDate = Carbon::now()->parse('-1 Monday')->format('Y-m-d');

        // Selected range is only applied when Show Perm Only is OFF.
        // When a range is selected, Hours Already Scheduled must include BOTH permanent and coverage schedules.
        $applyRange = (!$permOnly && !empty($startWeek) && !empty($endWeek));
        $rangeStartDate = $mainStartDate;
        $rangeEndDate = $endDate;

        if ($applyRange) {
            $start = Carbon::createFromFormat('m/d/Y', $startWeek);
            $end = Carbon::createFromFormat('m/d/Y', $endWeek);
            //Jump one day back if Monday is given, because we would be out of range by one day
            if ($start->isMonday()) {
                $start = $start->subDay();
            }
            //Jump one day forward if Sunday is given, because we would be out of range by one day
            if ($end->isSunday()) {
                $end = $end->addDay();
            }

            $rangeStartDate = $start->format('Y-m-d');
            $rangeEndDate = $end->format('Y-m-d');
        }

        return new DateBoundariesDTO(
            $mainStartDate,
            $subStartDate,
            $endDate,
            $hoursStartDate,
            $hoursEndDate,
            $desiredRateStartDate,
            $desiredRateEndDate,
            $applyRange,
            $rangeStartDate,
            $rangeEndDate
        );
    }

    private function getSaturdaySundayDateBoundaries($permOnly, ?string $startWeek, ?string $endWeek): DateBoundariesDTO
    {
        // Query filters below use exclusive boundaries (`>` / `<`),
        // so Friday/Monday sentinel dates are used to include the intended Saturday/Sunday range.
        $mainStartDate = Carbon::now()->parse('this friday')->format('Y-m-d');
        $subStartDate = Carbon::now()->previous('Friday')->format('Y-m-d');
        $endDate = Carbon::now()->next('Monday')->addWeeks(3)->format('Y-m-d');

        // Non-serviceWise (feature flagged)
        $hoursStartDate = Carbon::now()->parse('first Friday of ' . date('M') . ' ' . date('Y') . '')->format('Y-m-d');
        $hoursEndDate = Carbon::now()->parse('last Monday of ' . date('M') . ' ' . date('Y') . '')->format('Y-m-d');
        $desiredRateStartDate = Carbon::now()->previous('Friday')->subWeeks(3)->format('Y-m-d');
        $desiredRateEndDate = Carbon::now()->parse('-1 Monday')->format('Y-m-d');

        // Selected range is only applied when Show Perm Only is OFF.
        // When a range is selected, Hours Already Scheduled must include BOTH permanent and coverage schedules.
        $applyRange = (!$permOnly && !empty($startWeek) && !empty($endWeek));
        $rangeStartDate = $mainStartDate;
        $rangeEndDate = $endDate;

        if ($applyRange) {

            $start = Carbon::createFromFormat('m/d/Y', $startWeek)->subDay();
            $end = Carbon::createFromFormat('m/d/Y', $endWeek)->addDay();

            $rangeStartDate = $start->format('Y-m-d');
            $rangeEndDate = $end->format('Y-m-d');
        }

        return new DateBoundariesDTO(
            $mainStartDate,
            $subStartDate,
            $endDate,
            $hoursStartDate,
            $hoursEndDate,
            $desiredRateStartDate,
            $desiredRateEndDate,
            $applyRange,
            $rangeStartDate,
            $rangeEndDate
        );
    }
}
