<?php

namespace App\SyncProcesses;

use App\AfterhoursSchedule;
use App\Helpers\H;
use App\AfterhoursShift;
class AfterHoursUpcomingSchedule
{
    /**
     * The function to to send Reminders for Upcoming Shifts 
     *
     * @author Nayan - 30 Nov 2022
     * 
     */
    public function setAfterHoursUpcomingSchedules()
    {
        $firstDateOfNextMonth = strtotime('first day of next month');
        $startDate = date('Y-m-d', $firstDateOfNextMonth);
        $lastDateOfNextMonth = strtotime('last day of next month');
        $lastDay = date('Y-m-d', $lastDateOfNextMonth);
        $afterHoursShiftList = AfterhoursShift::get()->toArray();
        
        AfterhoursShift::createSchedule($afterHoursShiftList,$startDate,$lastDay);
    }

}
