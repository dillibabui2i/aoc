<?php

namespace App\Helpers;
use Carbon\Carbon;
class Calendar
{
    public static function getMonthOfCalendar($month, $currentDate, $year)
    {
        $daysOfWeek = ['M', 'T', 'W', 'T', 'F', 'S', 'S'];
        $firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
        $numberDays = date('t', $firstDayOfMonth);
        $dateComponents = getdate($firstDayOfMonth);
        $monthName = $dateComponents['month'];
        $dayOfWeek = $dateComponents['wday'] - 1;
        if (date('w', $firstDayOfMonth) == 0) {
            $dayOfWeek = 6;
        }

        $calendar = "<table class='month-calendar-table table caption-top'>";
        $calendar .= "<caption>$monthName $year</caption>";
        $calendar .= '<tr>';

        foreach ($daysOfWeek as $day) {
            $calendar .= "<th class='month-calendar-header'>$day</th>";
        }
        $currentDay = 1;
        $calendar .= '</tr><tr>';
        if ($dayOfWeek > 0) {
            $calendar .= "<td colspan='$dayOfWeek'>&nbsp;</td>";
        }
        $month = str_pad($month, 2, '0', STR_PAD_LEFT);
        while ($currentDay <= $numberDays) {
            if ($dayOfWeek == 7) {
                $dayOfWeek = 0;
                $calendar .= '</tr><tr>';
            }
            $currentDayRel = str_pad($currentDay, 2, '0', STR_PAD_LEFT);
            $date = "$year-$month-$currentDayRel";
            $isCurrentDate = ($date == $currentDate) ? 'current-date' : '';
            $calendar .= "<td class='day $isCurrentDate' rel='$date'>$currentDay</td>";
            $currentDay++;
            $dayOfWeek++;
        }
        if ($dayOfWeek != 7) {
            $remainingDays = 7 - $dayOfWeek;
            $calendar .= "<td colspan='$remainingDays'>&nbsp;</td>";
        }
        $calendar .= '</tr>';
        $calendar .= '</table>';

        return $calendar;
    }

    public static function getWeekStartEndByDates($date)
    {
        $week = date('W', strtotime($date));
        $year = date('Y', strtotime($date));

        if(LD::checkFeatureFlag('ipea-calender-weekly-date-fix-and-otherfix')) {
            return self::getWeekStartEndByWeekAndYearNew($date);
        } else {
            return self::getWeekStartEndByWeekAndYear($week, $year);
        }
        
    }

    public static function getWeekStartEndByWeekAndYearNew($date){
        // Parse the date
        $carbonDate = Carbon::parse($date);

        // Get the week start date (Monday)
        $result['start'] = $carbonDate->startOfWeek(Carbon::MONDAY)->toDateString();

        // Get the week end date (Sunday)
        $result['end'] = $carbonDate->endOfWeek(Carbon::SUNDAY)->toDateString();
        return $result;
    }


    public static function getWeekStartEndByWeekAndYear($week, $year)
    {
        $d = new \DateTime();
        $result['start'] = $d->setISODate($year, $week, 1)->format('Y-m-d');
        $result['end'] = $d->setISODate($year, $week, 7)->format('Y-m-d');

        return $result;
    }

    /**
     * Buckets a time into the PRC Calendar's 8am-5pm hour rows, clamping anything
     * earlier than 8am into the '8am' row and anything at/after 5pm into the '5pm' row.
     */
    public function clampToHourBucket(string $time): string
    {
        $hour = (int) date('G', strtotime($time));

        if ($hour <= 8) {
            return '8am';
        }
        if ($hour >= 17) {
            return '5pm';
        }

        return date('ga', strtotime($time));
    }

    public static function getNotificationFormatDateTime($date)
    {
        $today = date('Y-m-d');
        $match = date('Y-m-d', strtotime($date));
        $returnDate = date('F j, Y', strtotime($date)) . ' at ' . date(config('constant')['shorttimeformat'], strtotime($date));
        if ($today == $match) {
            $returnDate = date(config('constant')['shorttimeformat'], strtotime($date));
        } elseif (date('Y-m-d', (strtotime('-1 day', strtotime($today)))) == $match) {
            $returnDate = 'Yesterday at ' . date(config('constant')['shorttimeformat'], strtotime($date));
        }

        return $returnDate;
    }
}