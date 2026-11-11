<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\AfterhoursShift;
use App\AfterhoursSchedule;
use App\Roles;
use DB;
use App\Helpers\H;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Calendar;
use App\BranchLocations;
use App\Helpers\Notifications;
use App\User;
use App\Helpers\LD;
use DataTables;
class AfterHoursController extends Controller
{
    //
    public function getAfterHoursCalender(Request $request)
    {
        $states = H::getHelper('ConstantHelper')->getBranchStates()->toArray();
        $afterhoursShiftRoles =  AfterhoursShift::select(DB::raw('distinct(roles_id)'))->where('roles_id', '!=', '')->get()->toArray();
        $afterhoursShiftRoleIdArr =  array_column($afterhoursShiftRoles, 'roles_id');
        $afterhoursShiftRoleId= [];
        foreach($afterhoursShiftRoleIdArr as $afterhoursShiftRoleIdData) {
            $afterhoursShiftRoleIdTempData = explode(",",$afterhoursShiftRoleIdData);
            $afterhoursShiftRoleId = array_merge($afterhoursShiftRoleId,$afterhoursShiftRoleIdTempData);
        }
        $afterhoursShiftRoleId = array_unique($afterhoursShiftRoleId);
        $roleData = Roles::whereIn('id', $afterhoursShiftRoleId)->orderby("name")->get()->toArray();
        $departmentName = array_unique(array_column($roleData, 'department'));
        $redirect_afterhours_schedule_id =0;
        if (Session::has('redirect_afterhours_schedule_id')){
            $redirect_afterhours_schedule_id =Session::get('redirect_afterhours_schedule_id');
           Session::forget('redirect_afterhours_schedule_id');
        }
        return view('afterhours.calendar', ['states' => $states, 'roles' => $roleData, 'departmentName' => $departmentName,'redirect_afterhours_schedule_id' => $redirect_afterhours_schedule_id]);
    }

    public function getAfterHoursWeekEvents(Request $request)
    {
        $reqData = $request->all();
        $scheduleData = [];
        $branchData = $afterhourShiftArray = [];
        $week = [];
        if (isset($reqData['state']) && $reqData['state'] != '' && isset($reqData['date']) && $reqData['date'] != '') {
            $searchArr = [];
            if (isset($reqData['search']) && $reqData['search'] != '') {
                $searchArr = json_decode($reqData['search'], true);
                $reqData['searchData'] = $reqData['search'];
                $reqData['search'] = $searchArr['label'];
            }
            Session::put(['lastAfterHoursCalendarSearch' => $reqData]);
            $week = Calendar::getWeekStartEndByDates($reqData['date']);
            $branchData = [];
            $states = [];
            $states = BranchLocations::select('id')->where('state', $reqData['state'])->get()->toArray();
            $states = array_column($states, 'id');
            $statesIds = implode(",",$states);
            $schedules = AfterhoursSchedule::select('afterhours_schedule.*', 'afterhours_shifts.color_filled', 'afterhours_shifts.color_open', 'afterhours_shifts.title')
                ->where('afterhours_schedule.start_date','<=',$week['end'])->where('afterhours_schedule.end_date','>=',$week['start']);
            if (count($states) > 0) {
                $stateBindings[] = $statesIds;
                $schedules->whereRaw("afterhours_shifts.branchid REGEXP
                CONCAT('(^|,)(', REPLACE(?, ',', '|'), ')(,|$)')",$stateBindings);
            }
            if (isset($reqData['role_id']) && $reqData['role_id'] != '') {
                $roleBindings[] = $reqData['role_id'];
                $schedules->whereRaw("afterhours_shifts.roles_id REGEXP
                CONCAT('(^|,)(', REPLACE(?, ',', '|'), ')(,|$)')",$roleBindings);
            }
            if (isset($reqData['department_name']) && $reqData['department_name'] != '') {
                $departmentData = Roles::where('department', $reqData['department_name'])->get()->toArray();
                $roleIds = array_column($departmentData, 'id');
                $roleCondition = '';
                if(count($roleIds) > 0) {
                    $cnt = 0;
                    $roleCondition = $roleCondition.' (';
                    foreach($roleIds as $roleData) {
                        if($cnt == 0) {
                            $roleCondition = $roleCondition."afterhours_shifts.roles_id REGEXP CONCAT('(^|,)(', REPLACE('" . $roleData . "', ',', '|'), ')(,|$)')";
                        } else {
                            $roleCondition = $roleCondition." OR afterhours_shifts.roles_id REGEXP CONCAT('(^|,)(', REPLACE('" . $roleData . "', ',', '|'), ')(,|$)')";
                        }
                        $cnt++;
                    }
                    $roleCondition = $roleCondition.' )';
                }
                if($roleCondition != '') {
                    $schedules->whereRaw($roleCondition);
                }
            }
            if (isset($reqData['type']) && $reqData['type'] == 'filled') {
                $schedules->where('afterhours_schedule.user_id', '>', 0);
            } elseif (isset($reqData['type']) && $reqData['type'] == 'notfilled') {
                $schedules->where('afterhours_schedule.user_id', 0);
            }
            $schedules->join('afterhours_shifts', 'afterhours_shifts.id', '=', 'afterhours_schedule.afterhours_shifts_id');
            $schedules->leftjoin('users', 'users.id', '=', 'afterhours_schedule.user_id');
            H::ExcelLog($schedules->toSql(), 'getAfterHoursWeekEvents.log');
            $schedules = $schedules->get()->toArray();
            $cnt = 0;
            foreach ($schedules as $key => $value) {
                $userName = '';
                $bilingual = '';
                if ($value['user_id'] > 0) {
                    $userDetails = User::where('id', $value['user_id'])->first();
                    $userName = $userDetails->name;
                    $bilingual = $userDetails->bilingual;
                }
                $schedules[$cnt]['userName'] = $userName;
                $schedules[$cnt]['bilingual'] = $bilingual;
                $cnt++;
            }
            $stimekeys = array_column($schedules, 'start_time');
            array_multisort($stimekeys, SORT_ASC, $schedules);
            $afterhourShiftArray = array_column($schedules, 'afterhours_shifts_id');
            $afterhourShiftArray = array_unique($afterhourShiftArray);
            foreach ($schedules as $key => $value) {
                if ($value['start_time'] != '00:00:00') {
                    $t = date('ga', strtotime($value['start_time']));
                    $t =$value['afterhours_shifts_id'];
                    $startDate = $value['start_date'];
                    $endDate = $value['end_date'];
                    $i = 0;
                    $NStartDate   = date('Y-m-d', strtotime('+1 days', strtotime($startDate)));
                    if (strtotime($NStartDate) < strtotime($endDate) && strtotime($NStartDate) >= strtotime($week['start'])) {
                        while (strtotime($NStartDate) < strtotime($endDate)) {
                            if ($i == 0) {
                                $startDate = $value['start_date'];
                            }
                            if($startDate>=$week['start'] && $startDate <=$week['end']){
                                $day = date('l', strtotime($startDate));
                                $scheduleData[$t][$day][] = $value;
                            }
                            $startDate   = date('Y-m-d', strtotime('+1 days', strtotime($startDate)));
                            //echo $value['title']."==".$startDate.'=='.$endDate;echo "<br>";
                            if (strtotime($startDate) > strtotime($endDate)) {
                                break;
                            }
                            $i++;
                        }
                    } else {
                        if (strtotime($value['start_date']) >= strtotime($week['start'])) {
                            $day = date('l', strtotime($value['start_date']));
                            $scheduleData[$t][$day][] = $value;
                        }
                    }
                }
            }

            return view('afterhours.weekEvents', ['scheduleData' => $scheduleData,'afterhourShiftArray' => $afterhourShiftArray, 'branchData' => $branchData, 'week' => $week, 'state' => $reqData['state']]);
        }
    }

    public function getAfterHoursInfo(Request $request)
    {
        $reqData = $request->all();
        $afterhourDetails = AfterhoursSchedule::select('afterhours_schedule.*', 'afterhours_shifts.color_filled', 'afterhours_shifts.color_open', 'afterhours_shifts.title', 'afterhours_shifts.roles_id', 'afterhours_shifts.branchid')->join('afterhours_shifts', 'afterhours_shifts.id', '=', 'afterhours_schedule.afterhours_shifts_id')->leftjoin('users', 'users.id', '=', 'afterhours_schedule.user_id')->where('afterhours_schedule.id', $reqData['afterhours_schedule_id'])->first();
        $afterhourDetails = json_decode(json_encode($afterhourDetails), true);
        $assignmentUser = [];
        if (H::canPermissions('assign.allshifts')) {
            $assignmentUser = User::select('users.id','users.name','users.bilingual')->join('users_has_roles', 'users_has_roles.user_id', '=', 'users.id')->whereIn('users_has_roles.role_id', explode(",",$afterhourDetails['roles_id']))->where('status', 1)->get()->toArray();
        } elseif (H::canPermissions('assign.shifts')) {
            $assignmentUser = User::select('users.id','users.name','users.bilingual')->join('users_has_roles', 'users_has_roles.user_id', '=', 'users.id')->whereIn('users_has_roles.role_id', explode(",",$afterhourDetails['roles_id']))->whereRaw("branchid REGEXP CONCAT('(^|,)(', REPLACE('" . $afterhourDetails['branchid'] . "', ',', '|'), ')(,|$)')")->where('status', 1)->get()->toArray();
        }
        $assigneeUserDetail = [];
        if ($afterhourDetails['user_id'] > 0) {
            $assigneeUserDetail = User::find($afterhourDetails['user_id']);
        }
        return view('afterhours.getAfterHoursInfo', ['afterhourDetails' => $afterhourDetails, 'assignmentUser' => $assignmentUser, 'assigneeUserDetail' => $assigneeUserDetail]);
    }
    public function saveAfterHoursInfo(Request $request)
    {
        try {
            $reqData = $request->all();
            $afterhourDetails =  AfterhoursSchedule::where('afterhours_schedule.id', $reqData['afterhours_schedule_id'])->first();
            if ($reqData['shift_update'] == 'all') {
                AfterhoursSchedule::where('afterhours_schedule.afterhours_shifts_id', $afterhourDetails['afterhours_shifts_id'])->update(['user_id' => $reqData['user_id']]);
            } elseif ($reqData['shift_update'] == 'this_and_following') {
                AfterhoursSchedule::where('afterhours_schedule.afterhours_shifts_id', $afterhourDetails['afterhours_shifts_id'])->where('afterhours_schedule.start_date','>=',$afterhourDetails['start_date'])->update(['user_id' => $reqData['user_id']]);
            }else {
                AfterhoursSchedule::where('afterhours_schedule.id', $reqData['afterhours_schedule_id'])->update(['user_id' => $reqData['user_id']]);
            }
            $displayMessage  = 'Shift assigned successfully';
            $emailTextURL = env('APP_URL') . '/afterhours/calendar';
            if ($reqData['assigned_user_id'] != $reqData['user_id']) {
                if ($reqData['assigned_user_id'] > 0) {
                    $removedUserDetail = User::find($reqData['assigned_user_id']);
                    $displayMessage  = 'Shift assignee changed successfully';
                    $removedNotificationText = "Your After Hours Shift for " . H::getFormatedDate($afterhourDetails->start_date) . " at " . H::getFormatedTime($afterhourDetails->start_date . ' ' . $afterhourDetails->start_time) . ', Has Been Canceled';
                    Notifications::addNotification($data = ['user_id' => $reqData['assigned_user_id'], 'title' => $removedNotificationText, 'type' => 'AfterHoursCanceled', 'related_item' => $reqData['afterhours_schedule_id']]);
                    H::ExcelLog($removedNotificationText, 'AfterHoursSchedule.log');
                    //Email
                    $emailNotificationText = "Your After Hours shift on " . H::getFormatedDate($afterhourDetails->start_date) . " at " . H::getFormatedTime($afterhourDetails->start_date . ' ' . $afterhourDetails->start_time) . " - " . H::getFormatedTime($afterhourDetails->end_date . ' ' . $afterhourDetails->end_time) . ', has been canceled. Click <a href="' . $emailTextURL . '">here</a> to open the calendar.';
                    $data = [];
                    $data['subject']  = 'Your Scheduled After Hours Shift Has Been Canceled';
                    $data['to_email']  = $removedUserDetail->email;
                    H::getHelper('EmailNotification')->sendEmailNotification($data['to_email'], $data['subject'], $emailNotificationText, 'html');
                }
                //Notification
                $assigneeUserDetail = User::find($reqData['user_id']);
                $notificationText = "You've Been Scheduled for an After Hours Shift on " . H::getFormatedDate($afterhourDetails->start_date) . " at " . H::getFormatedTime($afterhourDetails->start_date . ' ' . $afterhourDetails->start_time);
                Notifications::addNotification($data = ['user_id' => $reqData['user_id'], 'title' => $notificationText, 'type' => 'AfterHours', 'related_item' => $reqData['afterhours_schedule_id']]);
                H::ExcelLog($notificationText, 'AfterHoursSchedule.log');

                //Email
                $emailTextURL = env('APP_URL').'/afterhours/showcalendarpopup/'.$reqData['afterhours_schedule_id'];
                $emailNotificationText = "You've been scheduled for an After Hours shift " . H::getFormatedDate($afterhourDetails->start_date) . " at " . H::getFormatedTime($afterhourDetails->start_date . ' ' . $afterhourDetails->start_time) . " - " . H::getFormatedTime($afterhourDetails->end_date . ' ' . $afterhourDetails->end_time) . '. Click <a href="' . $emailTextURL .'">here</a> to learn more.';
                $data = [];
                $data['body']  = $emailNotificationText;
                $data['subject']  = 'You\'ve Been Scheduled for an After Hours Shift';
                $data['to_email']  = $assigneeUserDetail->email;
                H::getHelper('EmailNotification')->sendEmailNotification($data['to_email'], $data['subject'], $emailNotificationText, 'html');

                //Notes
                $notes = H::getLoggedInUser()['name'] . ' added Scheduled for an After Hours Shift on ' . H::getFormatedDate($afterhourDetails->start_date) . " at " . H::getFormatedTime($afterhourDetails->start_date . ' ' . $afterhourDetails->start_time);
                H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'AfterHours', 'entity_id' => $reqData['afterhours_schedule_id'], 'type' => 'insert', 'notes' => $notes]);
            }
            return response()->json(['success' => 1, 'message' => $displayMessage]);
        } catch (\Exception $ex) {
            H::ExcelLog('==saveAfterHoursInfo-Exception-' . $ex->getMessage(), 'saveAfterHoursInfo.log');
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    /**
     * Show the Class list.
     */
    public function index()
    {
        return view('afterhours.grid');
    }

    /**
     * Show the Class ajax list.
     */
    public function getAfterhoursShiftList(Request $request)
    {
        $branchLocation = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $columns = [
            0 => 'title',
            4 => 'start_time',
            5 => 'end_time',
            6 => 'repeat',
        ];
        $AfterhoursShiftList = new AfterhoursShift();
        $AfterhoursShiftList = $AfterhoursShiftList->select('afterhours_shifts.*', DB::raw("TIME_FORMAT(afterhours_shifts.start_time,'%h:%i %p') AS display_start_time"), DB::raw("TIME_FORMAT(afterhours_shifts.end_time,'%h:%i %p') AS display_end_time"));
        if (isset($request->search['value']) && $request->search['value'] != '') {
            $AfterhoursShiftList = $AfterhoursShiftList->where(function ($query) use ($request) {
                $searchValue = '%' . $request['search']['value'] . '%';
                $query->where('afterhours_shifts.title', 'like', $searchValue)
                      ->orWhere('afterhours_shifts.repeat', 'like', $searchValue);
            });
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $AfterhoursShiftList = $AfterhoursShiftList->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        $total_records = $AfterhoursShiftList->get();

        //filters - P1-3669
        if (LD::checkFeatureFlag('state-branch-filter-manage-after-hours-page')) {
            $branch = $state = [];
            if(isset($request['filters'])) {
                foreach($request['filters'] as $key => $value){
                    if($value['name']=='afterhoursstate') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='afterhoursbranch') {
                        $branch[] = $value['value'];
                    }
                }
            }
            $stateStr = '';
            if(count($state)>0) {
                $stateStr = implode(",",$state);
                $facilities = H::getHelper("ConstantHelper")->getOCBranchByState($stateStr);
                $AfterhoursShiftList->where(function($q) use ($facilities){
                    foreach($facilities as $facility) {
                        $q->orWhereRaw("afterhours_shifts.branchid REGEXP CONCAT('(^|,)(', REPLACE('" . $facility->id . "', ',', '|'), ')(,|$)')");
                    }
                });
            }
            if(count($branch)>0) {
                $AfterhoursShiftList->where(function($q) use ($branch){
                    foreach($branch as $b) {
                        $q->orWhereRaw("afterhours_shifts.branchid REGEXP CONCAT('(^|,)(', REPLACE('" . $b . "', ',', '|'), ')(,|$)')");
                    }
                });
            }
        }
        $totalFiltered = $AfterhoursShiftList->count();
        if (isset($request['start']) && $request['length'] != '-1') {
            $AfterhoursShiftList = $AfterhoursShiftList->offset($request['start'])->limit($request['length']);
        }
        $AfterhoursShiftList = $AfterhoursShiftList->get()->toArray();
        $cnt = 0;
        foreach ($AfterhoursShiftList as $AfterhoursShiftData) {
            foreach ($branchLocation as $b) {
                if ($b['id'] == $AfterhoursShiftData['branchid']) {
                    $AfterhoursShiftList[$cnt]['location'] =  $b['branch'];
                    break;
                }
            }
            $roleData = Roles::where('id', $AfterhoursShiftData['roles_id'])->first();
            $AfterhoursShiftList[$cnt]['role_name'] =  $roleData->name;
            if(LD::checkFeatureFlag('add-columns-to-manage-after-hours-page'))  {
                $branchLocationAry = explode(",",$AfterhoursShiftData['branchid']);
                $states = BranchLocations::select('state',DB::raw('GROUP_CONCAT(branch) as branch_name'))->whereIn('id', $branchLocationAry)->groupBy('state')->get()->toArray();
                $AfterhoursShiftList[$cnt]['state'] = implode(",",array_values(array_column($states,'state')));
                $AfterhoursShiftList[$cnt]['branch'] = implode(",",array_values(array_column($states,'branch_name')));
                $createdByUser = '';
                if($AfterhoursShiftData['created_by'] != null) {
                    $createdByUserDetails  = User::find($AfterhoursShiftData['created_by']);
                    $createdByUser = $createdByUserDetails->name;
                }
                $AfterhoursShiftList[$cnt]['created_by_name'] = $createdByUser;
            }
            $btn = '';
            $btn .= '<a href="' . url('/manageafterhours/edit', $AfterhoursShiftData['id']) . '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
            $btn .= '<a href="' . url('/deleteafterhoursshiftrecord', $AfterhoursShiftData['id']) . '" class="delete"><i class="fa fa-trash" title="delete"></i></a>';
            $AfterhoursShiftList[$cnt]['action'] = $btn;
            $cnt++;
        }
        $data = [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval($totalFiltered),
            'data'            => $AfterhoursShiftList,
        ];
        return $data;
    }

    public function add()
    {
        $roleData = Roles::get()->toArray();
        return view('afterhours.afterhoursform', ['roleData' => $roleData]);
    }

    public function addafterhours()
    {
        $roleData = Roles::get()->toArray();
        return view('afterhours.afterhoursform', ['roleData' => $roleData, 'addafterhours' => true]);
    }

    public function saveAfterhoursShift(Request $request)
    {
        try {
            $data = $request->all();
            $data['start_time'] = date('H:i', strtotime($data['start_time']));
            $data['end_time'] = date('H:i', strtotime($data['end_time']));
            if(isset($data['specific_date']) && $data['specific_date'] !='') {
                $data['specific_date'] = date('Y-m-d', strtotime($data['specific_date']));
            }
            $repeat_on = '';
            if ($data['repeat'] == 'Day') {
                $repeat_on = implode(",", $data['repeat_on']);
            }
            $data['repeat_on'] = $repeat_on;

            $data['roles_id'] = implode(",", $data['roles_id']);
            $data['branchid'] = implode(",", $data['branchid']);
            if (isset($data['send_notification_duration'])) {
                $data['send_notification_duration'] = implode(",", $data['send_notification_duration']);
            }
            $data['created_by'] = $data['updated_by'] = auth()->user()->id;
            $afterhoursShiftData = AfterhoursShift::create($data);
            AfterhoursShift::setSchedule($afterhoursShiftData->id,$data);
            if (!empty($data['isAjax'])) {
                return response()->json(['success' => 1, 'message' => 'Afterhours Shift added successfully!']);
            } else {
                return redirect('/manageafterhours')->with('success', 'Afterhours Shift added successfully!');
            }
        } catch (\Exception $ex) {
            H::ExcelLog('==saveAfterhoursShift-Exception-' . $ex->getMessage(), 'saveAfterhoursShift.log');
            if (!empty($data['isAjax'])) {
                return response()->json(['success' => 0, 'message' => $ex->getMessage()]);
            } else {
                return redirect('/manageafterhours/add/')->with('error', 'Something went wrong.');
            }
        }
    }

    public function edit($id)
    {
        $roleData = Roles::get()->toArray();
        $afterHoursDetail = AfterhoursShift::find($id);
        $repeat_on = [];
        if ($afterHoursDetail->repeat == 'Day') {
            $repeat_on  = explode(",", $afterHoursDetail->repeat_on);
        }
        $specific_date ='';
        $afterhoursScheduleDetail = AfterhoursSchedule::where('afterhours_shifts_id', $id)->first();
        if(!empty($afterhoursScheduleDetail)) {
            $specific_date = H::getFormatedDate($afterhoursScheduleDetail->start_date);
        }
        return view('afterhours.afterhoursform', compact('afterHoursDetail', 'roleData', 'repeat_on','specific_date'));
    }

    public function updateAfterhoursShift(Request $request)
    {
        $updateArr = [];
        try {
            $data = $request->all();
            $updateArr['title'] =  $data['title'];
            $updateArr['roles_id'] =  $data['roles_id'];
            $updateArr['branchid'] =  $data['branchid'];
            $updateArr['color_filled'] =  $data['color_filled'];
            $updateArr['color_open'] =  $data['color_open'];
            if (isset($data['start_time']) && $data['start_time'] != '') {
                $updateArr['start_time'] = date('H:i', strtotime($data['start_time']));
            }
            if (isset($data['end_time']) && $data['end_time'] != '') {
                $updateArr['end_time'] = date('H:i', strtotime($data['end_time']));
            }
            if (isset($data['repeat']) && $data['repeat'] != '') {
                $updateArr['repeat'] =  $data['repeat'];
            }
            if (isset($data['repeat']) && $data['repeat'] == 'Day') {
                $updateArr['repeat_on'] = implode(",", $data['repeat_on']);
            }
            if (!empty($data['roles_id'])) {
                $updateArr['roles_id'] = implode(",", $data['roles_id']);
            }
            if (!empty($data['branchid'])) {
                $updateArr['branchid'] = implode(",", $data['branchid']);
            }
            if (!empty($data['specific_date'])) {
                $data['specific_date'] = date('Y-m-d', strtotime($data['specific_date']));
            }
            $updateArr['send_notification'] =  $data['send_notification'];
            if (!empty($data['send_notification_duration'])) {
                $updateArr['send_notification_duration'] = implode(",", $data['send_notification_duration']);
            }
            $updateArr['updated_by'] = auth()->user()->id;
            AfterhoursShift::where('id', $request->id)->update($updateArr);
            AfterhoursShift::setSchedule($request->id,$data,'edit');
            return redirect('/manageafterhours')->with('success', 'Afterhours Shift updated!');
        } catch (\Exception $e) {
            H::ExcelLog('==updateAfterhoursShift-Exception-' . $e->getMessage(), 'updateAfterhoursShift.log');
            return redirect('/manageafterhours/edit/' . $request->id)->with('error', 'Something went wrong.');
        }
    }

    public function showAfterHoursCalenderPopup($id)
    {
        Session::put(['redirect_afterhours_schedule_id' => $id]);
        return redirect('/afterhours/calendar');
    }

    public function deleteAfterHoursShift(Request $request)
    {
        try {
            AfterhoursSchedule::where('id', $request['afterhours_schedule_id'])->delete();
            $displayMessage = 'Shift has been deleted successfully.';
            return response()->json(['success' => 1, 'message' => $displayMessage]);
        } catch (\Exception $ex) {
            H::ExcelLog('==deleteAfterHoursShift-Exception-' . $ex->getMessage(), 'deleteAfterHoursShift.log');
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    public function deleteAfterHoursShiftRecord($id){
        try {
            AfterhoursShift::where('id', $id)->delete();
            AfterhoursSchedule::where('afterhours_shifts_id', $id)->delete();
            $displayMessage = 'Shift has been deleted successfully.';
            return redirect('/manageafterhours')->with('success', $displayMessage);
        } catch (\Exception $ex) {
            H::ExcelLog('==deleteAfterHoursShift-Exception-' . $ex->getMessage(), 'deleteAfterHoursShift.log');
            return response()->json(['success' => 0, 'message' => 'Something went wrong, Please try again.']);
        }
    }

    private function limitText($text, $limit) {
        if (str_word_count($text, 0) > $limit) {
            $words = str_word_count($text, 2);
            $pos   = array_keys($words);
            $text  = substr($text, 0, $pos[$limit]) . '...';
        }
        return $text;
    }

    public function getAhJournalData(Request $request) {
        $data = H::getHelper("AfterHours")
        ->getAhJournalData($request->all());
        return Datatables::of($data)
        ->editColumn('added_date', function ($row) {
            if(!empty($row->added_date)){
                return [
                    'display' => e(H::getFormatedDateTime($row->added_date)),
                    'timestamp' => strtotime($row->added_date)
                ];
            } else {
                return [
                    'display' => '',
                    'timestamp' => ''
                ];
            }
        })
        ->editColumn('notes', function ($row) {
            return $this->limitText($row->notes, 15);
        })
        ->addColumn('Action', function ($row) {
            return '<a href="javascript:void(0)" title = "Clear Note"
                class="clearit" data-id="' . $row->id . '">
                <i class="fa fa-check-square-o" aria-hidden="true"></i></a>
                 | <a href="javascript:void(0)" title="View Note"
                onclick=\'getJournalNoteDetails("'. $row->id . '")\'>
                <i class="fa fa-eye" aria-hidden="true"></i></a>
                 | <a href="javascript:void(0);" title="View Client Profile"
                 onclick=\'getClientDetails("' . $row->aoc_patient_id . '")\'>
                 <i class="fa fa-user"></i></a>';
        })->escapeColumns('Action')->make(true);
    }

    public function reloadAhJournalData(Request $request) {
        return json_encode(
            [
                "counter" => H::getHelper('AfterHours')->getAhJournalData($request->all(), 1),
                "type" => $request->input("type")
            ]
        );
    }

    public function clearNote(Request $request) {
        try {
            if ($request->input('id') != '') {
                    DB::table("journal_notes")
                    ->where("id", $request->input('id'))
                    ->update(
                        [
                            'note_cleared_by' => Auth::user()->id,
                            'note_cleared_date' => date('Y-m-d H:i:s')
                        ]
                    );
                return response()->json(["success" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function getJournalNoteDetails(Request $request){

        try {
            if ($request->input('id') != '') {
                $details = DB::table('journal_notes')->select(
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
                    IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
                    mssql_patients.Patient_LastName) AS patient_name"),
                    'journal_notes.added_date',
                    'journal_notes.notes',
                    'journal_notes.id'
                )
                ->leftJoin('mssql_patients', 'journal_notes.PatientID', '=', 'mssql_patients.PatientID')
                ->where('journal_notes.id', $request->input('id'))
                ->first();
                return view('afterhours.journalNoteDetails',
                [
                    'details' => $details
                ]);
            }
        } catch (\Exception $e) {
            return false;
        }
    }
}
