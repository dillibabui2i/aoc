<?php

namespace App\Http\Controllers;

use App\Enums\MssqlRecruiterTrackerBonusStatus;
use App\Helpers\H;
use App\Helpers\LD;
use App\Http\Requests\WhiteBoard\ManageWhiteBoardRequest;
use App\RecruitingSchedule;
use App\Services\WhiteBoard\TherapyWhiteBoardService;
use App\WhiteBoard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;


class WhiteBoardController extends Controller
{
    /**
     * @param TherapyWhiteBoardService $whiteBoardService
     */
    public function __construct(protected TherapyWhiteBoardService $whiteBoardService)
    {
    }

    public function getWhiteboardReport(Request $request){
        $req = $request->all();
        $clickedDate = '';
        $lastSunday = '';
        if(isset($req['type']) && ($req['type']=='cmh' || $req['type']=='candidate_start_hour' ||  $req['type']=='weekly_candidate_start_hour' ||  $req['type']=='weekly_cmh') ){
          $query = RecruitingSchedule::select("mssql_recruitertracker.whiteboard_row  as Service","users.name as Recruiter Name"
          ,"mssql_recruitertracker.Candidate", DB::raw('(CASE WHEN recruiting_schedule.aoc_provider_id > 0 THEN "Current" ELSE "New" END)AS New_Current')
                ,DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS Client_Name"),"mssql_recruitertracker.Branch_Location as Location","recruiting_schedule.scheduled_date  as Scheduled Date"
                ,"recruiting_schedule.appointment_date  as Appointment Date","recruiting_schedule.appointment_time  as Time","mssql_recruitertracker.Total_Hours as Total Hours","mssql_patients.Payer");

        } else {
           $query = RecruitingSchedule::select("mssql_recruitertracker.whiteboard_row  as Service","users.name as Recruiter Name","mssql_recruitertracker.Candidate"
           , DB::raw('(CASE WHEN recruiting_schedule.aoc_provider_id > 0 THEN "Current" ELSE "New" END)AS New_Current')
           ,DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS Client_Name"),"mssql_recruitertracker.Branch_Location as Location","recruiting_schedule.scheduled_date  as Scheduled Date"
           ,"recruiting_schedule.appointment_date  as Appointment Date","recruiting_schedule.appointment_time  as Time","mssql_patients.Payer");
        }
        $query->leftJoin("mssql_recruitertracker", function($q){
            $q->on("mssql_recruitertracker.id","=","recruiting_schedule.tracker_id")
			->on("recruiting_schedule.aoc_patient_id", "=", "mssql_recruitertracker.aoc_patient_id");
        });

        $query->leftJoin("mssql_patients", function($q){
            $q->on("mssql_patients.id","=","mssql_recruitertracker.aoc_patient_id");
        });

        $query->leftJoin("users", function($q){
            $q->on("users.id","=","mssql_recruitertracker.user_id");
        });
        if(!empty($request['userid'])) {
            $query->where("mssql_recruitertracker.user_id",'=',$req['userid']);
        }

        if(!empty($request['whiteboard_row'])) {
            $query->where("mssql_recruitertracker.whiteboard_row",'=',$req['whiteboard_row']);
        }

        if (LD::checkFeatureFlag('exclude-bonus-ineligible-trackers-on-whiteboard')) {
            $query->leftJoin('mssql_recruitertracker as rt_bi', function($q){
                $q->on('rt_bi.id', '=', 'mssql_recruitertracker.id')
                    ->whereRaw("IFNULL(rt_bi.Bonus_Status, '') = ?", [MssqlRecruiterTrackerBonusStatus::BONUS_INELIGIBLE->value]);
            });

            $query->whereNull('rt_bi.Bonus_Status');
        }

        if(!empty($req['typeid'])){
            $query->where("recruiting_schedule.type_id",'=',$req['typeid']);
            $days =["Monday"=>"1","Tuesday"=>"2","Wednesday"=>"3","Thursday"=>"4","Friday"=>"5","Saturday"=>"6","Sunday"=>"7"];
            $lastSunday = date('Y-m-d', strtotime('last Sunday'));
            $clickedDate =date("Y-m-d",strtotime($lastSunday." +".$days[$req["day"]]." days"));
            if($req['typeid']=='1' || $req['typeid']=='3'|| $req['typeid']=='4'|| $req['typeid']=='6') {
                if($req['type']=='weekly_candidate_start_hour') {
                    $query->where("mssql_recruitertracker.Candidate_Current_Status",'=','First Shift Confirmed');
                    $query->where("recruiting_schedule.appointment_date",'>',$lastSunday)->where("recruiting_schedule.appointment_date",'<=',$clickedDate);
                } else if($req['type']=='candidate_start_hour') {
                    $query->where("mssql_recruitertracker.Candidate_Current_Status",'=','First Shift Confirmed');
                    $query->where("recruiting_schedule.appointment_date",'=',$clickedDate);
                } else if($req['type']=='weekly_ipea' || $req['type']=='weekly_pepi' || $req['type']=='weekly_wepi' || $req['type']=='weekly_cm' || $req['type']=='weekly_cmh'){
                    $query->where("recruiting_schedule.scheduled_date",'>',$lastSunday)->where("recruiting_schedule.scheduled_date",'<=',$clickedDate);
                } else {
                    $query->where("recruiting_schedule.scheduled_date",'=',$clickedDate);
                }
            }
        }
        H::ExcelLog(print_r($req,true),'doubleclick-whiteboard.log');
        H::ExcelLog("Current Date:".$clickedDate,'doubleclick-whiteboard.log');
        H::ExcelLog("Last Sunday:".$lastSunday,'doubleclick-whiteboard.log');
        H::ExcelLog($query->toSql(),'doubleclick-whiteboard.log');

        $res = $query->get()->toArray();
        return view('whiteboard.popup',['data'=>$request->all(),"results"=>count($res)>0?$res:[]]);
    }

    public function whiteboard(Request $request)
    {
        //set the dates for each tab
        //get the data (I know I need to move this somewhere else)
        //$staticstart = date('Y-m-d', strtotime('last Sunday'));
        //$staticfinish = date('Y-m-d', strtotime('next Monday'));
        //var_dump($staticstart);
        //var_dump($staticfinish);
        //die();
        $qry = '';
        $where = ' row_id <> "FL - PPEC" AND whiteboard.is_therapy = 0 ';
        $search = request()->input('search');
        if (isset($search['excludeprn'])) {
            $where .= ' AND (mssql_patients.isPRN = 0 OR mssql_patients.isPRN is null) ';
        }

        $bonusStatusFilter = '';
        if (LD::checkFeatureFlag('exclude-bonus-ineligible-trackers-on-whiteboard')) {
            $bonusStatusFilter = " and (IFNULL(mssql_recruitertracker.Bonus_Status, '') = '"
                . MssqlRecruiterTrackerBonusStatus::BONUS_INELIGIBLE->value . "')";

            $where .= ' and (mssql_recruitertracker.Bonus_Status IS NULL)';
        }

        $qry = 'select whiteboard.*, CONCAT(regions.region_name, " | ", regions.team_leader) as regionname
            from `whiteboard`
            left join regions on whiteboard.region = regions.id
            left join mssql_recruitertracker on whiteboard.row_id = mssql_recruitertracker.whiteboard_row 
                and whiteboard.user_id = mssql_recruitertracker.user_id' . $bonusStatusFilter . '
            left join mssql_patients on mssql_recruitertracker.aoc_patient_id = mssql_patients.id
            WHERE ' . $where .'
            group by row_id, regions.region_name, user_name
            order by row_id, case when regions.region_name = "Out of Territory" then "zz" else regions.region_name end, user_name';
        //H::ExcelLog($qry,'dbwhiteboard.log');
        $tmp = DB::select($qry);
        $tmp = json_decode(json_encode($tmp), true);
        $wb = [];
        foreach ($tmp as $user) {
            $wb[$user['id']] = [
                'id' => $user['id'],
                'user_id' => $user['user_id'],
                'name' => $user['user_name'],
                'row_number' => $user['id'],
                'row_id' => $user['row_id'],
                'show_row' => 0,
                'territory' => $user['territory'],
                'candidate_start_hour_goals' => $user['candidate_start_hour_goals'],
                'ipea_goal' => $user['ipea_goal'],
                'pepi_wepi_goal' => $user['pepi_wepi_goal'],
                'client_match_goal' => $user['client_match_goal'],
                'client_match_hours_goal' => $user['client_match_hours_goal'],
                'candidate_start_goal' => $user['candidate_start_goal'],
                'Monday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                'Tuesday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                'Wednesday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                'Thursday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                'Friday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                'Saturday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                'Sunday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                'wtd' => [
                    'Monday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                    'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                    'Tuesday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                    'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                    'Wednesday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                    'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                    'Thursday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                    'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                    'Friday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                    'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                    'Saturday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                    'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                    'Sunday' => ['IPEA' => 0, 'PEPI' => 0, 'WEPI' => 0, 'CCR' => 0, 'CS' => 0,
                    'CSH' => 0, 'CM' => 0, 'CMH' => 0, ],
                ],

            ];

            $wb[$user['id']]['regionname'] = $user['regionname'];
        }

        //H::ExcelLog('start queries','dbWhiteboard.log');
        foreach ($wb as $key => $wbRow) {
            $rowNumber = $wbRow['id'];
            $userId = $wbRow['user_id']?:-999;
            $rowId = $wbRow['row_id'];
            $recruiter = $wbRow['name'];
            $lastSunday = date('Y-m-d', strtotime('last Sunday'));
            $nextMonday = date('Y-m-d', strtotime('next Monday'));
            $rounds = ['counts','CSH','CMH','CM'];
            foreach($rounds as $round){
                $qry = '';
                if($round=='counts'){
                    $leftJoin = 'left join `mssql_recruitertracker` rt on rs.tracker_id = rt.id
                                    left join `schedule_types` st on rs.type_id = st.id';
                    if (LD::checkFeatureFlag('hot-fix-whiteboard-calculation')) {
                        $leftJoin = 'left join `mssql_recruitertracker` rt on rt.id = rs.tracker_id and rt.aoc_patient_id = rs.aoc_patient_id
							left join `schedule_types` st on st.id =  rs.type_id';
                    }
                    $qry = 'SELECT r.type, r.scheduled_date, sum(r.mycount) as total
                        from
                        (
                            SELECT st.type, rs.scheduled_date, 1 as mycount
                            FROM `recruiting_schedule` rs
							' . $leftJoin . '
                            WHERE rt.user_id = ' . $userId . " and rt.whiteboard_row = '" . $rowId . "' and rs.scheduled_date > '$lastSunday' and rs.scheduled_date < '$nextMonday' and rs.type_id < 5 AND rs.is_deleted <> 1
                        ) as r
                        group by r.type, r.scheduled_date order by r.type, r.scheduled_date";
                }elseif($round=='CSH'){
                    $leftJoin = 'left join `mssql_recruitertracker` rt2 on rs2.tracker_id = rt2.id';
                    if (LD::checkFeatureFlag('hot-fix-whiteboard-calculation')) {
                        $leftJoin = 'left join `mssql_recruitertracker` rt2 on rt2.id = rs2.tracker_id and rt2.aoc_patient_id = rs2.aoc_patient_id';
                    }
                    $qry = "SELECT r2.type, r2.scheduled_date, sum(r2.mycount) as total
                        from
                        (
                            select 'CSH' as type, rs2.appointment_date as scheduled_date, rt2.Total_Hours as mycount
                            from `recruiting_schedule` rs2
							" . $leftJoin . "
                                where rt2.user_id = " . $userId . " and rt2.whiteboard_row = '" . $rowId . "' and rs2.type_id = 6 and
                                rt2.Candidate_Current_Status = 'First Shift Confirmed' and rs2.appointment_date > '$lastSunday' and rs2.appointment_date < '$nextMonday' AND rs2.is_deleted <> 1
                        ) as r2
                        group by r2.type, r2.scheduled_date order by r2.type, r2.scheduled_date";
                }elseif($round=='CMH'){
                    $leftJoin = 'left join `mssql_recruitertracker` rt3 on rs3.tracker_id = rt3.id';
                    if (LD::checkFeatureFlag('hot-fix-whiteboard-calculation')) {
                        $leftJoin = 'left join `mssql_recruitertracker` rt3 on rt3.id = rs3.tracker_id and rt3.aoc_patient_id = rs3.aoc_patient_id';
                    }
                    $qry = "SELECT r2.type, r2.scheduled_date, sum(r2.mycount) as total
                        from
                        (
                            select 'CMH' as type, rs3.scheduled_date as scheduled_date, rt3.Total_Hours as mycount
                            from `recruiting_schedule` rs3
							" . $leftJoin . "
                                where rt3.user_id = " . $userId . " and rt3.whiteboard_row = '" . $rowId . "' and rs3.type_id = 6 and rs3.scheduled_date > '$lastSunday' and rs3.scheduled_date < '$nextMonday' AND rs3.is_deleted <> 1
                        ) as r2
                        group by r2.type, r2.scheduled_date order by r2.type, r2.scheduled_date";
                }else{
                    $leftJoin = 'left join `mssql_recruitertracker` rt3 on rs3.tracker_id = rt3.id';
                    if (LD::checkFeatureFlag('hot-fix-whiteboard-calculation')) {
                        $leftJoin = 'left join `mssql_recruitertracker` rt3 on rt3.id = rs3.tracker_id and rt3.aoc_patient_id = rs3.aoc_patient_id';
                    }
                    $qry = "SELECT r2.type, r2.scheduled_date, sum(r2.mycount) as total
                        from
                        (
                            select 'CM' as type, rs3.scheduled_date as scheduled_date, 1 as mycount
                            from `recruiting_schedule` rs3
							" . $leftJoin . "
                            where rt3.user_id = " . $userId . " and rt3.whiteboard_row = '" . $rowId . "' and rs3.type_id = 6 and rs3.scheduled_date > '$lastSunday' and rs3.scheduled_date < '$nextMonday' AND rs3.is_deleted <> 1
                        ) as r2
                        group by r2.type, r2.scheduled_date order by r2.type, r2.scheduled_date";
                }
                //H::ExcelLog($qry,'dbWhiteboard.log');
                //echo $wbRow['name'] . '<br /><br />';
                //echo $qry . '<br /><br /><br />';
                $mainData = DB::select($qry);
                //echo '<pre>';
                //var_dump($mainData);
                //echo '</pre>';
                //echo '<br /><br /><br />';
                $mainData = json_decode(json_encode($mainData), true);
                //now fill the buckets into the chart rows
                $count = 0;

                foreach ($mainData as $row) {
                    if ($row['total'] > 0) {
                        $wb[$rowNumber]['show_row'] = 1;
                        $type = $row['type'];
                        $date = $row['scheduled_date'];
                        //set day of week
                        $tmp = strtotime($date);
                        $day = date('l', $tmp);

                        //echo $rowNumber . ' - ' . $day . ' - ' . $type . '<br />';
                        $wb[$rowNumber][$day][$type] = $row['total'];
                        if($type!=''){
                            if ($day == 'Monday') {
                                $wb[$rowNumber]['wtd']['Monday'][$type] = $wb[$rowNumber]['wtd']['Monday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Tuesday'][$type] = $wb[$rowNumber]['wtd']['Tuesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Wednesday'][$type] = $wb[$rowNumber]['wtd']['Wednesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Thursday'][$type] = $wb[$rowNumber]['wtd']['Thursday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];
                            } elseif ($day == 'Tuesday') {
                                $wb[$rowNumber]['wtd']['Tuesday'][$type] = $wb[$rowNumber]['wtd']['Tuesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Wednesday'][$type] = $wb[$rowNumber]['wtd']['Wednesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Thursday'][$type] = $wb[$rowNumber]['wtd']['Thursday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];
                            } elseif ($day == 'Wednesday') {
                                $wb[$rowNumber]['wtd']['Wednesday'][$type] = $wb[$rowNumber]['wtd']['Wednesday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Thursday'][$type] = $wb[$rowNumber]['wtd']['Thursday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];
                            } elseif ($day == 'Thursday') {
                                $wb[$rowNumber]['wtd']['Thursday'][$type] = $wb[$rowNumber]['wtd']['Thursday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];
                            } elseif ($day == 'Friday') {
                                $wb[$rowNumber]['wtd']['Friday'][$type] = $wb[$rowNumber]['wtd']['Friday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];
                            } elseif ($day == 'Saturday') {
                                $wb[$rowNumber]['wtd']['Saturday'][$type] = $wb[$rowNumber]['wtd']['Saturday'][$type] + $row['total'];
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];
                            } elseif ($day == 'Sunday') {
                                $wb[$rowNumber]['wtd']['Sunday'][$type] = $wb[$rowNumber]['wtd']['Sunday'][$type] + $row['total'];
                            }
                            $count++;
                        }
                    }
                }
            }
            //H::ExcelLog(print_r($wb,true),'dbWhiteboard.log');
            //echo '<pre>';
            //var_dump($wb[$rowNumber]);
            //echo '</pre><br /><br /><br /><br />';
        }
        $wb['9999']['row_id'] = '9999';
        return view('whiteboard.grid', ['wb'=>$wb]);
    }

    public function managewhiteboard() {
        $dataTable = H::getHelper('Datatable')->getConfiguration('whiteboard');
        return view('whiteboard.managewhiteboardgrid', ['dataTable' => $dataTable]);
    }

    public function getwhiteboardlistajax(Request $request) {
        $data = H::getHelper('Datatable')->ajaxDataResponse($request, 'whiteboard');
        return $data;
    }

    public function add() {
        $regions = H::getHelper('ConstantHelper')->getRegion('id', true);
        $therapyRegions = H::getHelper('ConstantHelper')->getTherapyRegions('id');
        return view('whiteboard.form', ['mode' => 'add', 'regions' => $regions, 'therapyRegions' => $therapyRegions]);
    }

    public function edit($id) {
        $data = WhiteBoard::find($id)->toArray();
        $data['hire_date'] = H::getFormatedDate($data['hire_date']);
        $regions = H::getHelper('ConstantHelper')->getRegion('id', true);
        $therapyRegions = H::getHelper('ConstantHelper')->getTherapyRegions('id');
        return view('whiteboard.form', ['mode' => 'edit', 'data' => $data, 'regions' => $regions, 'therapyRegions' => $therapyRegions]);
    }

    /**
     * @param ManageWhiteBoardRequest $request
     * @return Application|RedirectResponse|Redirector
     */
    public function save(ManageWhiteBoardRequest $request): Application|RedirectResponse|Redirector
    {
        $this->whiteBoardService->saveWhiteBoard($request);

        return redirect('/managewhiteboard')->with('success', 'Whiteboard information saved successfully!');
    }

    public function delete($id)
    {
        WhiteBoard::where('id', $id)->delete();
        return redirect('/managewhiteboard')->with('success', 'Whiteboard record removed successfully!');
    }

    public function saveOutOfOffice(Request $request)
    {
        try {
            if ($request->input('ooo_user_id') != '' || $request->input('ooo_user_id') != -1) {
                $userActivationClass = 'activeUserAttendance';
                if ($request->input('ooo_from_date') && $request->input('ooo_to_date')) {
                    $todayDate = (new \DateTime())->format('Y-m-d');
                    $oooFromDate = (new \DateTime($request->input('ooo_from_date')))->format('Y-m-d');
                    $oooToDate = (new \DateTime($request->input('ooo_to_date')))->format('Y-m-d');

                    if (($todayDate >= $oooFromDate) && ($todayDate <= $oooToDate)) {
                        $userActivationClass = 'inactiveUserAttendance';
                    }
                    DB::table("users")->where("id", $request->input('ooo_user_id'))->update(['ooo_from_date' => $oooFromDate, 'ooo_to_date' => $oooToDate]);
                } else {
                    DB::table("users")->where("id", $request->input('ooo_user_id'))->update(['ooo_from_date' => '', 'ooo_to_date' => '']);
                }
                return response()->json(["success" => true, 'userActivationClass' => $userActivationClass, 'ooo_user_id' => $request->input('ooo_user_id'), 'onclick' => 'setUserAttendance(\''.H::getFormatedDate(($request->input('ooo_from_date')) ? $request->input('ooo_from_date') : date('Y-m-d')).'\',\''.H::getFormatedDate(($request->input('ooo_to_date')) ? $request->input('ooo_to_date') : date('Y-m-d')).'\',\''.$request->input('ooo_user_id').'\')']);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }
}

