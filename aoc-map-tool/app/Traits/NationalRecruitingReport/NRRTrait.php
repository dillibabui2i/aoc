<?php

namespace App\Traits\NationalRecruitingReport;

trait NRRTrait
{
    /**
     * @param $field
     * @param $value
     * @param $array
     * @return string
     */
    public function generateMultiSelectQuery($field, $value, &$array)
    {
        $fieldStr = [];
        foreach ($value as $fieldValue) {
            $fieldStr[] = '?';
            array_push($array, $fieldValue);
        }
        return ' ' . $field . ' IN (' . implode(", ", $fieldStr) . ') ';
    }

    /**
     * @param $month
     * @param $year
     * @return int
     */
    public function get_days_in_month($month, $year): int //todo need to refactor
    {
        if ($month == '02') {
            if ($year % 4 == 0) {
                return 29;
            } else {
                return 28;
            }
        } elseif (
            $month == '01' ||
            $month == '03' ||
            $month == '05' ||
            $month == '07' ||
            $month == '08' ||
            $month == '10' ||
            $month == '12')
        {
            return 31;
        } else {
            return 30;
        }
    }

    /**
     * @param array $arr
     * @param string $key
     * @return array
     */
    protected function moveKeyToEnd(array $arr, string $key): array
    {
        if (isset($arr[$key])) {
            $value = $arr[$key];
            unset($arr[$key]);
            $arr[$key] = $value;
        }

        return $arr;
    }
}
