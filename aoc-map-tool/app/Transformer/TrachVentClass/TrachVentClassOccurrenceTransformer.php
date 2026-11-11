<?php

namespace App\Transformer\TrachVentClass;

use App\Helpers\LD;
use App\TrachVentClassOccurrence;
use Illuminate\Support\Collection;

class TrachVentClassOccurrenceTransformer
{
    /**
     * @param Collection<int, TrachVentClassOccurrence> $schedulesModels
     */
    public function transformWeekEvents(Collection $schedulesModels, array $branches, array $instructors): array
    {
        $trachVentScheduleClassClassTypeList = config('constant')['trachVentScheduleClassClassType'];
        $showClassType = LD::checkFeatureFlag('add-new-field-class-type-trach-vent');
        $scheduleData = [];

        foreach ($schedulesModels as $scheduleModel) {
            $schedule = $this->buildWeekEvent($scheduleModel, $branches, $instructors, $trachVentScheduleClassClassTypeList, $showClassType);

            $classTime = $scheduleModel->class_time;

            if ($classTime === '00:00:00') {
                continue;
            }

            $t = date('ga', strtotime($classTime));
            $day = date('l', strtotime($schedule['class_date']));
            $hour = (int) date('G', strtotime($classTime));

            if ($hour <= 8) {
                $scheduleData['8am'][$day][] = $schedule;
            } elseif ($hour >= 17) {
                $scheduleData['5pm'][$day][] = $schedule;
            } else {
                $scheduleData[$t][$day][] = $schedule;
            }
        }

        return $scheduleData;
    }

    public function transformCalendarSearchRows(Collection $rows): array
    {
        return $rows->map(function ($value) {
            $label = implode(',', array_filter([
                $value->Candidate ?? '',
                $value->branch ?? '',
                ($value->class_date ?? '') !== '00/00/0000' ? ($value->class_date ?? '') : '',
            ], fn ($part) => $part !== ''));

            return [
                'id' => $value->id,
                'label' => $label,
                'date' => $value->class_date,
                'branch' => $value->branch,
                'branchId' => $value->branchId,
            ];
        })->all();
    }

    private function buildWeekEvent(
        TrachVentClassOccurrence $scheduleModel,
        array $branches,
        array $instructors,
        array $trachVentScheduleClassClassTypeList,
        bool $showClassType,
    ): array {
        $trachVentClass = $scheduleModel->trachVentClass;
        $schedule = $scheduleModel->toArray();
        $schedule['id'] = $scheduleModel->id;
        $schedule['location_id'] = $trachVentClass->location_id;
        $schedule['is_recurring'] = $trachVentClass->isRecurring();

        $enrolledCount = $scheduleModel->enrolledCount();
        $schedule['instructor_name'] = $instructors[$scheduleModel->instructor_id] ?? '';
        $schedule['enrolled_count'] = $enrolledCount;
        $schedule['capacity'] = $scheduleModel->capacity;
        $schedule['is_full'] = $scheduleModel->capacity <= $enrolledCount;

        $branchName = '';
        if (($schedule['location_id'] ?? '') !== '') {
            $branchIds = array_map('intval', array_column($branches, 'id'));
            $scheduleBranch = array_search((int) $schedule['location_id'], $branchIds, true);
            $branchName = $scheduleBranch !== false ? ($branches[$scheduleBranch]['branch'] ?? '') : '';
        }

        $schedule['branch_name'] = $branchName;
        $schedule['class_category_name'] = $trachVentClass->classCategory->name ?? '';
        $schedule['class_category_color'] = $trachVentClass->classCategory->color;
        $schedule['formatted_time'] = getFormatedTimeRange($scheduleModel->class_time, $scheduleModel->end_time);
        $schedule['class_type_label'] = ($showClassType && $scheduleModel->class_type !== null && $scheduleModel->class_type !== '')
            ? ($trachVentScheduleClassClassTypeList[$scheduleModel->class_type] ?? '')
            : '';

        return $schedule;
    }
}
