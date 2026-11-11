<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Helpers\H;

class AfterhoursShift extends Model {
    use HasFactory;
    protected $table = 'afterhours_shifts';
    protected $fillable = [
        'title',
        'roles_id',
        'branchid',
        'color_filled',
        'color_open',
        'start_time',
        'end_time',
        'repeat',
        'repeat_on',
        'created_by',
        'updated_by',
        'send_notification',
        'send_notification_duration',
    ];

    public static function setSchedule($id, $data, $action = 'add') {
        $startDate = date('Y-m-d');
        $lastDateOfNextMonth = strtotime('last day of next month');
        $lastDay = date('Y-m-d', $lastDateOfNextMonth);
        $afterHoursShiftList = AfterhoursShift::where('id', $id)->get()->toArray();
        if($afterHoursShiftList[0]['repeat'] == 'Specific') {
            $startDate = $data['specific_date'];
        }
        if($action == 'edit') {
            $existingAfterHoursShift = AfterhoursSchedule::where('afterhours_shifts_id',
            $id)->orderBy('id', 'asc')->first();
            //AfterhoursSchedule::where('afterhours_schedule.afterhours_shifts_id', $id)->delete();
            $startDate = $existingAfterHoursShift->start_date;
            $lastDay = $existingAfterHoursShift->end_date;
        }
        self::createSchedule($afterHoursShiftList, $startDate, $lastDay, 'add');
    }

    public static function createSchedule($afterHoursShiftList, $startDate, $lastDay, $action = 'cron') {
        if(count($afterHoursShiftList) > 0) { // +1
            foreach($afterHoursShiftList as $afterHoursShiftData) { // +2
                $insertFinalDateArray = $insertData = [];
                $insertData['afterhours_shifts_id'] = $afterHoursShiftData['id'];
                $insertData['start_time'] = $afterHoursShiftData['start_time'];
                $insertData['end_time'] = $afterHoursShiftData['end_time'];
                $insertData['created_by'] = $insertData['updated_by'] = 1;
                $send_notification = $afterHoursShiftData['send_notification'];
                if($send_notification == 1) { // +3
                    $send_notification_duration = explode(",", $afterHoursShiftData['send_notification_duration']);
                    if(in_array(24, $send_notification_duration)) { // +4
                        $insertData['notification1'] = 1;
                    } else { // +1
                        $insertData['notification1'] = 0;
                    }
                    if(in_array(72, $send_notification_duration)) {  // +4
                        $insertData['notification2'] = 1;
                    } else { // +1
                        $insertData['notification2'] = 0;
                    }
                    if(in_array(48, $send_notification_duration)) {  // +4
                        $insertData['notification3'] = 1;
                    } else { // +1
                        $insertData['notification3'] = 0;
                    }
                    if(in_array(6, $send_notification_duration)) {  // +4
                        $insertData['notification4'] = 1;
                    } else { // +1
                        $insertData['notification4'] = 0;
                    }
                    if(in_array(1, $send_notification_duration)) {  // +4
                        $insertData['notification5'] = 1;
                    } else { // +1
                        $insertData['notification5'] = 0;
                    }
                } else { // +1
                    $insertData['notification1'] = 0;
                    $insertData['notification2'] = 0;
                    $insertData['notification3'] = 0;
                    $insertData['notification4'] = 0;
                    $insertData['notification5'] = 0;
                }
                if($action == 'add') {  // +3
                    $insertData['created_by'] = $insertData['updated_by'] = auth()->user()->id;
                }
                if($action == 'edit') { // +3
                    $insertData['updated_by'] = auth()->user()->id;
                }
                if(
                    $afterHoursShiftData['repeat'] == 'Specific' && // +3
                    $action != 'cron' // +1
                ) {
                    $insertData['start_date'] = $startDate;
                    $insertData['end_date'] = $startDate;
                    //see if it has been created already
                    $schedules = AfterhoursSchedule::select('afterhours_schedule.id')
                        ->where('afterhours_shifts_id', $insertData['afterhours_shifts_id'])
                        ->where('afterhours_schedule.start_date', '=', $insertData['start_date']);
                    $schedules = $schedules->get()->toArray();
                    if(empty($schedules)) { // +4
                        AfterhoursSchedule::create($insertData);
                    } else { // +1
                        unset($insertData['start_date']);
                        unset($insertData['end_date']);
                        unset($insertData['created_by']);
                        AfterhoursSchedule::where('afterhours_shifts_id',
                        $insertData['afterhours_shifts_id'])
                        ->update($insertData);
                    }
                } elseif($afterHoursShiftData['repeat'] == 'Day') {  // +1
                    $repeatOnArray = explode(",", $afterHoursShiftData['repeat_on']);
                    foreach($repeatOnArray as $repeatOnData) {  // +4
                        $insertDateArray = [];
                        $insertDateArray = H::getHelper('AfterHours')
                        ->getDateForSpecificDayBetweenDates($startDate,
                        $lastDay, $repeatOnData);
                        foreach($insertDateArray as $insertDateData) {  // +5
                            $insertFinalDateArray[] = $insertDateData;
                        }
                    }
                    foreach($insertFinalDateArray as $insertFinalDateData) { // +4
                        $insertData['start_date'] = $insertFinalDateData;
                        $insertData['end_date'] = $insertFinalDateData;
                        //see if it has been created already
                        $schedules = AfterhoursSchedule::select('afterhours_schedule.id')
                            ->where('afterhours_shifts_id', $insertData['afterhours_shifts_id'])
                            ->where('afterhours_schedule.start_date', '=', $insertData['start_date']);
                        $schedules = $schedules->get()->toArray();
                        if(empty($schedules)) { // +5
                            AfterhoursSchedule::create($insertData);
                        } else { // +1
                            unset($insertData['start_date']);
                            unset($insertData['end_date']);
                            unset($insertData['created_by']);
                            AfterhoursSchedule::where('afterhours_shifts_id',
                            $insertData['afterhours_shifts_id'])
                            ->update($insertData);
                        }
                    }
                } elseif($afterHoursShiftData['repeat'] == 'Week') { // +1

                    $insertFinalDateArray = H::getHelper('AfterHours')->getWeekDateRange($startDate, $lastDay);
                    foreach($insertFinalDateArray as $insertFinalDateData) { // +1
                        $startEndDateArray = explode("##", $insertFinalDateData);
                        $insertData['start_date'] = $startEndDateArray[0];
                        $insertData['end_date'] = $startEndDateArray[1];
                        //see if it has been created already
                        $schedules = AfterhoursSchedule::select('afterhours_schedule.id')
                            ->where('afterhours_shifts_id', $insertData['afterhours_shifts_id'])
                            ->where('afterhours_schedule.start_date', '=', $insertData['start_date']);
                        $schedules = $schedules->get()->toArray();
                        if(empty($schedules)) { // +5
                            AfterhoursSchedule::create($insertData);
                        } else { // +1
                            unset($insertData['start_date']);
                            unset($insertData['end_date']);
                            unset($insertData['created_by']);
                            AfterhoursSchedule::where('afterhours_shifts_id',
                            $insertData['afterhours_shifts_id'])
                            ->update($insertData);
                        }
                    }
                } elseif($afterHoursShiftData['repeat'] == 'Month') { // +1
                    $firstDateOfNextMonth = strtotime('first day of next month');
                    $insertData['start_date'] = date('Y-m-d', $firstDateOfNextMonth);
                    $insertData['start_date'] = $startDate;
                    $insertData['end_date'] = $lastDay;
                    $insertFinalDateArray = H::getHelper('AfterHours')
                    ->getDatesFromRange($insertData['start_date'],
                    $insertData['end_date']);
                    foreach($insertFinalDateArray as $insertFinalDateData) { // +4
                        $insertData['start_date'] = $insertData['end_date'] = $insertFinalDateData;
                        //see if it has been created already
                        $schedules = AfterhoursSchedule::select('afterhours_schedule.id')
                            ->where('afterhours_shifts_id', $insertData['afterhours_shifts_id'])
                            ->where('afterhours_schedule.start_date', '=', $insertData['start_date']);
                        $schedules = $schedules->get()->toArray();
                        if(empty($schedules)) { // +5
                            AfterhoursSchedule::create($insertData);
                        } else { // +1
                            unset($insertData['start_date']);
                            unset($insertData['end_date']);
                            unset($insertData['created_by']);
                            AfterhoursSchedule::where('afterhours_shifts_id', $insertData['afterhours_shifts_id'])
                            ->update($insertData);
                        }
                    }
                }
            }
        }
    }
}
