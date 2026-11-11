<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Reports;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\MssqlRecruitertracker;

class BonusReportController extends Controller
{
    
    public function bonusReport()
    {
        $title = 'Recruiter Bonus';
        $type = 'recruiterBonus';
        $source = 'Pending';
        if(isset(request()->type)) {
            $source = request()->type;
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = $title;
        $report['id'] = $type;
        $state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
        
		$months = [["value" => 'Pending', 'text' => 'Pending']];
		$bonusPeriods = MssqlRecruitertracker::select('bonus_period')->distinct()->where('bonus_period', '<>', '0000-00-00')->orderBy('bonus_period')->get();
		foreach($bonusPeriods as $bonusPeriod) {
			$months[] = ["value" => date('M Y', strtotime($bonusPeriod->bonus_period)), 'text' => date('M Y', strtotime($bonusPeriod->bonus_period))];
		}
        /*$months = [
            ["value" => 'Pending', 'text' => 'Pending'],
            ["value" => 'Jan 2023', 'text' => 'Jan 2023'],
        ];*/
        $filters = [
            [
                'label' => 'Period',
                'name' => 'month',
                'type' => 'select',
                'source' => $months
            ],
        ];
        if(LD::checkFeatureFlag('add-state-filter-and-export-bonus-report')){
            $filters[] = [
                'label' => 'State',
                'name' => 'state',
                'type' => 'multiselect',
                'source' => $state
            ];
        }

        //columns defines the fieldname, heading, optional format, width and style for each column
        $report['columns'] = [
            [
                'field' => 'recruiter',
                'heading' => ['title' => 'Recruiter', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNurseChurnPopup', 'clickedfilters' => ['user_id'],'defaultval'=>['bonus_period' => $months[0]['value']]],
            ],
            [
                'field' => 'TrackerCount',
                'heading' => ['title' => 'Tracker Count', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'TotalCSHours',
                'heading' => ['title' => 'Candidate Start Hours', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'TotalCSGoal',
                'heading' => ['title' => 'Candidate Start Hours Goal', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'PercentageOfGoal',
                'heading' => ['title' => '% of Goal', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'bonusTier',
                'heading' => ['title' => 'Bonus Tier', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'bonusPerTracker',
                'heading' => ['title' => 'Bonus Amount', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'totalBonus',
                'heading' => ['title' => 'Total Bonus', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'currency', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],

        ];

        //rowStyle is any special style to apply to the <tr> tags for the data rows  
        $report['rowStyle'] = '';
        /**
            * define the fields to group by for subtotals.  I set it up to allow up to 4 groupings.  The last one with id = 9 is for the grand totals.  
            * leave 'field' empty for ones you do not need.  It will ignore it. 
            * do not remove any or change the id values!!!!
            * make sure your query sorts the data in the same order as the grouping are defined!!!  
            * it prints the subtotal row when the data in column 'field' changes
            * style = the style to apply to the <tr> tag
            * label = what to display before the grouping value use @group3 @group2 to insert the value of the grouping fields (number = groupID)
            * 
            * Define groups with the lowest level first  
            * 
            */
            //no grouping for this report
        //set date ranges
        //later we need to make this dynamic to cover previous months

        H::ExcelLog(print_r(request()->all(),true),'dbNRR.log');
        //Nik here
        //$startDate = '2023-05-01';
        //$endDate = '2023-06-04';
        //$weeks = 5;
		
		$startDate = date("Y-m-d", strtotime("first Monday of Previous Month"));
		$endDate = date("Y-m-d", strtotime('next sunday', strtotime(date("Y-m-d", strtotime("last Monday of Previous Month")))));

		$startDateObj = new \DateTime($startDate);
		$endDateObj = new \DateTime($endDate);

		$diff = $endDateObj->diff($startDateObj);
		$weeks = ceil($diff->days / 7);
		
        $where = [];
        $states = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if ($value != '') {
                        $tmp = $field;
                        if ($field == 'month'){
                            H::ExcelLog($field . ' - ' . $value, 'dbNRR.log');
                            //dynamically create date range
                            //Nik here
                            if($value=='Pending'){
                                $source = 'Pending';
                            } else {
                                $startDate = date("Y-m-d", strtotime("first Monday of " . $value));
								$endDate = date("Y-m-d", strtotime('next sunday', strtotime(date("Y-m-d", strtotime("last Monday of " . $value )))));

								$startDateObj = new \DateTime($startDate);
								$endDateObj = new \DateTime($endDate);

								$diff = $endDateObj->diff($startDateObj);
								$weeks = ceil($diff->days / 7);
                                $source = 'Archived';                      
                            }
                        }
                        if(LD::checkFeatureFlag('add-state-filter-and-export-bonus-report')){
                            if($field == 'state' && !empty($value)){
                                $states = $value;
                            }
                        }
                    }
                }
            }
        }
        if($source=='Pending'){
            H::getHelper('RecruiterTrackerHelper')->updateHoursWorkedToTrackers();
        }
        $data = [];
        //now get report data
        $statefilter = "";
        $stateFilterArray = [];
        if(LD::checkFeatureFlag('add-state-filter-and-export-bonus-report')){
            if(!empty($states)){
                $serstate = "'".implode("','",$states)."'";
                //$statefilter .= "and (select mssql_facilities.State from mssql_recruitertracker rt LEFT JOIN mssql_facilities ON mssql_facilities.id = rt.aoc_facility_id where rt.user_id = whiteboard.user_id 
                //and rt.Candidate_Current_Status = 'First Shift Confirmed' and rt.Bonus_Status = 'Pending Hours' and rt.Candidate_Start_Date > '2022-12-31' GROUP BY whiteboard.user_id ) IN (".$serstate.")";
                foreach($states as $myState){
                    if($statefilter==''){
                        $statefilter .= " and (whiteboard.row_id like CONCAT(?,'%')";
                        array_push($stateFilterArray, $myState);
                    }else{
                        $statefilter .= " or whiteboard.row_id like CONCAT(?,'%')";
                        array_push($stateFilterArray, $myState);
                    }
                }
                $statefilter .= ")";
            }
        }
        if($source=='Pending'){
            $qry = "SELECT user_id, user_name, 
                (select count(*) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id 
                    and rt.Candidate_Current_Status = 'First Shift Confirmed' and rt.Bonus_Status = 'Pending Hours' and rt.Candidate_Start_Date > '2022-12-31') as TrackerCount,
                    tmpTotalCandidateStartHours as TotalCSHours,
                sum(candidate_start_hour_goals) * " . $weeks . " as  TotalCSGoal,
                tmpTotalCandidateStartHours / (sum(candidate_start_hour_goals) * " . $weeks . ") as PercentageOfGoal,
                (select sum(rt.bonus_amount) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id 
                    and rt.Candidate_Current_Status = 'First Shift Confirmed' and (rt.Hours_Worked >=40 or rt.bonus_override > 0) and rt.Bonus_Status = 'Pending Hours' and rt.Candidate_Start_Date > '2022-12-31') as TotalBonus
                FROM `whiteboard` 
                WHERE candidate_start_hour_goals <> 0  ".$statefilter."
                group by user_id, user_name
                order by user_name, whiteboard.primary desc";

                H::ExcelLog($qry,'dbBonusReport.log');
                $tmp = DB::select($qry, $stateFilterArray);
        }else{
            //build query to get results based on mssql_recruitertracker.bonus_period = $month instead of date range
			if(LD::checkFeatureFlag('change-Percentage-to-goal-logic-bonus-report')){
				$qry = "SELECT user_id, user_name, 
                (select count(*) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) as TrackerCount,
                (select avg(bonus_calc_total_hours) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) as TotalCSHours,
                (select avg(bonus_calc_goals) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id and
                rt.bonus_period = ?) as TotalCSGoal,
                (((select avg(bonus_calc_total_hours) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) - ((select avg(bonus_calc_goals) from mssql_recruitertracker rt where
                rt.user_id = whiteboard.user_id and rt.bonus_period = ?) * ".$weeks.")) / ((select avg(bonus_calc_goals) from mssql_recruitertracker rt where
                rt.user_id = whiteboard.user_id and rt.bonus_period = ?) * " . $weeks . ")) as PercentageOfGoal,
                (select sum(rt.bonus_amount) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) as TotalBonus
                FROM `whiteboard` 
                WHERE candidate_start_hour_goals <> 0  ".$statefilter."
                group by user_id, user_name
                order by user_id, whiteboard.primary desc";

                H::ExcelLog($qry,'dbBonusReport.log');
                $tmp = DB::select($qry, array_merge([
                    $startDate,
                    $startDate,
                    $startDate,
					$startDate,
                    $startDate,
                    $startDate,
                    $startDate
                ], $stateFilterArray));
			} else {
				$qry = "SELECT user_id, user_name, 
                (select count(*) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) as TrackerCount,
                (select avg(bonus_calc_total_hours) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) as TotalCSHours,
                (select avg(bonus_calc_goals) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id and
                rt.bonus_period = ?) as TotalCSGoal,
                ((select avg(bonus_calc_total_hours) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) / (select avg(bonus_calc_goals) from mssql_recruitertracker rt where
                rt.user_id = whiteboard.user_id and rt.bonus_period = ?) * " . $weeks . ") as PercentageOfGoal,
                (select sum(rt.bonus_amount) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) as TotalBonus
                FROM `whiteboard` 
                WHERE candidate_start_hour_goals <> 0  ".$statefilter."
                group by user_id, user_name
                order by user_id, whiteboard.primary desc";

                H::ExcelLog($qry,'dbBonusReport.log');
                $tmp = DB::select($qry, array_merge([
                    $startDate,
                    $startDate,
                    $startDate,
                    $startDate,
                    $startDate,
                    $startDate
                ], $stateFilterArray));
			}
        }
        $tmp = json_decode(json_encode($tmp), true);

        foreach ($tmp as $record) {
            $id = $record['user_id'];
            $bonusTier = '';
            if($record['PercentageOfGoal'] < 1.25 ){
                $bonusTier = 1;
                $bonusPerTracker = 40;
            }else{
                $bonusTier = 2;
                $bonusPerTracker = 45;
            }
            $totalBonus = $bonusPerTracker * $record['TrackerCount'];
            if(!isset($data[$id])){
                $data[$id] = ['recruiter' => $record['user_name'],
                    'TrackerCount' => $record['TrackerCount'],
                    'TotalCSHours' => $record['TotalCSHours'],
                    'TotalCSGoal' => $record['TotalCSGoal'],
                    'PercentageOfGoal' => round($record['PercentageOfGoal']*100),
                    'bonusTier' => $bonusTier,
                    'bonusPerTracker' => $bonusPerTracker,
                    'totalBonus' => $record['TotalBonus'],
                    'user_id' => $record['user_id'],
                ];
            }
        }
        H::ExcelLog(print_r($data,true),'dbBonusReport.log');
        return view('reports.bonusRecruiterReport', ['report' => $report, 'data' => $data, 'filters' => $filters]);
    }

    public function getRecruiterBonusPopup(Request $request){
        $req = $request->all();
        H::ExcelLog(print_r($req,true),'dbNrrPop.log');
        $rowFilter = json_decode($req['rowfilter'],true);
        $form = $req['form'];
        $filterReq = [];
        foreach($form as $key => $value) {
            if (empty($value['value']) || substr_count($value['name'], "date")) {
                continue;
            }
            if (substr_count($value['name'], "search") > 0) {
                if (substr_count($value['name'], "[") == 1) {
                    $key = str_replace(["]","search["], ["",""], $value['name']);
                    $rowFilter[$key] = $value['value'];
                } else if (substr_count($value['name'], "[") == 2) {
                    $key = str_replace(["]","search","["], ["","",""], $value['name']);
                    if (isset($rowFilter[$key]) && !is_array($rowFilter[$key])) {
                        $temp = $rowFilter[$key];
                        unset($rowFilter[$key]);
                        $rowFilter[$key][] = $temp;
                        $rowFilter[$key][] = $value['value'];
                    } else {
                        $rowFilter[$key][] = $value['value'];
                    }
                }
            } else {
                if (in_array($value['name'], array('_token', 'isajax', 'period', 'source', 'lob'))) {
                    continue;
                }
                $rowFilter[$key] = $value['value'];
            }
        }
        $result = [];
        $query = '';
        /*$startDate = '';
        $endingDate = '';
        $period = $form[2]['value'];
        if ($period == 'week') {
            $startDate = $rowFilter['start_of_week'];
            $endingDate = date('Y-m-d', strtotime("+6 day", strtotime($startDate)));
        } elseif($period == 'month') {
            $startDate = $rowFilter['start_of_week']."-01";
            $endingDate = date('Y-m-t', strtotime($startDate));
        } elseif($period == 'month') {
            $startDate = $rowFilter['start_of_week']."-01";
            $endingDate = date('Y-m-t', strtotime($startDate));
        }*/
        
        $query = DB::select("select Recruiter, pr.ProviderName as Provider, f.FacilityName as 'Branch Name', p.PatientName as Client, Candidate_Start_Date as 'Candidate Start', rt.skillset as 'Discipline', rt.Total_Hours as 'Hours Scheduled', Hours_Worked as 'Hours Worked', actual_gp as 'Gross Profit', bonus_amount as 'Bonus'
        from mssql_recruitertracker rt 
        left join mssql_providers pr on rt.aoc_provider_id = pr.id 
        left join mssql_patients p on rt.aoc_patient_id = p.id
        left join mssql_facilities f on pr.FacilityID = f.FacilityID
        where rt.Candidate_Start_Date > '2022-12-31' and rt.Bonus_Status = 'Pending Hours' and Candidate_Current_Status = 'First Shift Confirmed' 
        and rt.user_id = ? and pr.is_deleted = 0 order by pr.ProviderName", [$rowFilter['user_id']]);
        //H::ExcelLog(print_r($query->toSql(),true),'dbNrrPop.log');
        $result = $query;

        return view('reports.popup',['data'=>$request->all(),"results"=>count($result)>0?$result:[]]);  
    } 

    public function generateMultiSelectQuery($field, $value, $implodestr = 0)
    {
        $tmp = $field;
        $fieldStr = '';
        $cnt = 0;
        if ($implodestr == 1) {
            foreach ($value as $fieldValue) {
                $fieldStr .= "'" . $fieldValue . "',";
            }
            $fieldStr = substr($fieldStr, 0, -1);
        } else {
            foreach ($value as $fieldValue) {
                $fieldStr .= ($cnt == 0) ? ' ( ' : '';
                $fieldStr .= ($cnt > 0) ? ' OR ' : '';
                $fieldStr .= $tmp . "= '" . $fieldValue . "'";
                $cnt++;
                $fieldStr .= (count($value) == $cnt) ? ' ) ' : '';
            }
        }
        return $fieldStr;
    }

    public function downloadBonusReport(Request $request){
        $request = $request->all();
        $columns = [
            0 => 'user_name',
            1 => 'TrackerCount',
            2 => 'TotalCSHours',
            3 => 'TotalCSGoal',
            4 => 'PercentageOfGoal',
            5 => 'bonusTier',
            6 => 'bonusPerTracker',
            7 => 'TotalBonus',
        ];

        $dataTableFields = [
            'recruiter' => 'Recruiter',
            'totalBonus' => 'Total Bonus',
        ];

        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=Bonusreport.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        $source = 'Pending';
        H::ExcelLog(print_r(request()->all(),true),'dbNRR.log');
        //Nik here
        //$startDate = '2023-05-01';
        //$endDate = '2023-06-04';
        //$weeks = 5;

		$startDate = date("Y-m-d", strtotime("first Monday of Previous Month"));
		$endDate = date("Y-m-d", strtotime('next sunday', strtotime(date("Y-m-d", strtotime("last Monday of Previous Month")))));

		$startDateObj = new \DateTime($startDate);
		$endDateObj = new \DateTime($endDate);

		$diff = $endDateObj->diff($startDateObj);
		$weeks = ceil($diff->days / 7);

        $where = [];
        $states = [];
        if (isset($request['filters'])) {
            $filters = $request['filters'];
            $stats = [];
            $branch = [];
            foreach ($filters as $filter) {
                if ($filter['name'] == 'search[month]' && !empty($filter['value'])) {
                    H::ExcelLog($filter['name'] . ' - ' . $filter['value'], 'dbNRR.log');
                    if ($filter['value'] != '') {
                        $tmp = $filter['name'];
                        if ($filter['name'] == 'search[month]'){
                            //dynamically create date range
                            //Nik here
                            if($filter['value']=='Pending'){
                                $source = 'Pending';
                            } else {
                                $startDate = date("Y-m-d", strtotime("first Monday of " . $filter['value']));
								$endDate = date("Y-m-d", strtotime('next sunday', strtotime(date("Y-m-d", strtotime("last Monday of " . $filter['value'] )))));

								$startDateObj = new \DateTime($startDate);
								$endDateObj = new \DateTime($endDate);

								$diff = $endDateObj->diff($startDateObj);
								$weeks = ceil($diff->days / 7);
                                $source = 'Archived';                      
                            }
                        } 
                    }
                }
                if($filter['name'] == 'search[state][]' && !empty($filter['value'])){
                    $states[] = $filter['value'];
                }
                
            }

        }
        $data = [];
        //now get report data
        $statefilter = "";
        $stateFilterArray = [];
        if(!empty($states)){
            $serstate = "'".implode("','",$states)."'";
            //$statefilter .= "and (select mssql_facilities.State from mssql_recruitertracker rt LEFT JOIN mssql_facilities ON mssql_facilities.id = rt.aoc_facility_id where rt.user_id = whiteboard.user_id 
            //and rt.Candidate_Current_Status = 'First Shift Confirmed' and rt.Bonus_Status = 'Pending Hours' and rt.Candidate_Start_Date > '2022-12-31' GROUP BY whiteboard.user_id ) IN (".$serstate.")";
            foreach($states as $myState){
                if($statefilter==''){
                    $statefilter .= " and (whiteboard.row_id like CONCAT(?,'%')";
                    array_push($stateFilterArray, $myState);
                }else{
                    $statefilter .= " or whiteboard.row_id like CONCAT(?,'%')";
                    array_push($stateFilterArray, $myState);
                }
            }
            $statefilter .= ")";
        }
        if($source=='Pending'){
            $qry = "SELECT user_id, user_name, 
                (select count(*) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id 
                    and rt.Candidate_Current_Status = 'First Shift Confirmed' and
                    rt.Bonus_Status = 'Pending Hours' and rt.Candidate_Start_Date > '2022-12-31') as TrackerCount,
                    tmpTotalCandidateStartHours as TotalCSHours,
                sum(candidate_start_hour_goals) * " . $weeks . " as  TotalCSGoal,
                tmpTotalCandidateStartHours / (sum(candidate_start_hour_goals) * " . $weeks . ") as PercentageOfGoal,
                (select sum(rt.bonus_amount) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id 
                    and rt.Candidate_Current_Status = 'First Shift Confirmed' and
                    rt.Bonus_Status = 'Pending Hours' and rt.Candidate_Start_Date > '2022-12-31') as TotalBonus
                FROM `whiteboard` 
                WHERE candidate_start_hour_goals <> 0  ".$statefilter."
                group by user_id, user_name
                order by user_name, whiteboard.primary desc";
            H::ExcelLog($qry,'dbBonusReport.log');
            $tmp = DB::select($qry, $stateFilterArray);
        }else{
            //build query to get results based on mssql_recruitertracker.bonus_period = $month instead of date range
            $qry = "SELECT user_id, user_name, 
                (select count(*) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id and rt.bonus_period = ?)
                as TrackerCount,
                (select avg(bonus_calc_total_hours) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) as TotalCSHours,
                (select avg(bonus_calc_goals) from from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) as TotalCSGoal,
                (select avg(bonus_calc_total_hours) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) / (select avg(bonus_calc_goals) from from mssql_recruitertracker rt
                where rt.user_id = whiteboard.user_id and rt.bonus_period = ?) * " . $weeks . ") as PercentageOfGoal,
                (select sum(rt.bonus_amount) from mssql_recruitertracker rt where rt.user_id = whiteboard.user_id
                and rt.bonus_period = ?) as TotalBonus
                FROM `whiteboard` 
                WHERE candidate_start_hour_goals <> 0  ".$statefilter."
                group by user_id, user_name
                order by user_id, whiteboard.primary desc";
            H::ExcelLog($qry,'dbBonusReport.log');
            $tmp = DB::select($qry, array_merge([
                $startDate,
                $startDate,
                $startDate,
                $startDate,
                $startDate,
                $startDate
            ], $stateFilterArray));
        }
        $tmp = json_decode(json_encode($tmp), true);

        foreach ($tmp as $record) {
            $id = $record['user_id'];
            $bonusTier = '';
            if($record['PercentageOfGoal'] < 1.25 ){
                $bonusTier = 1;
                $bonusPerTracker = 40;
            }else{
                $bonusTier = 2;
                $bonusPerTracker = 45;
            }
            $totalBonus = $bonusPerTracker * $record['TrackerCount'];
            if(!isset($data[$id])){
                $data[$id] = ['recruiter' => $record['user_name'],
                    'totalBonus' => $record['TotalBonus'],
                ];
            }
        }

        $columns = [];

        foreach ($dataTableFields as $k => $v) {
            $columns[] = $v;
        }
        $callback = function () use ($data, $columns, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($data as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($dataTableFields as $fields => $val) {
                        $csvData[] = $tableData[$fields];
                    $cntr++;
                }

                fputcsv($file, $csvData);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function setBonusPaidmodal(Request $request){
        //Guard against submitting a cross-recruiter duplicate to payroll before the
        //next daily process:hoursworked run would have caught it (see markCrossRecruiterDuplicatesIneligible).
        H::getHelper('RecruiterTrackerHelper')->markCrossRecruiterDuplicatesIneligible();

        $request = $request->all();
        $datetime = $request['bonusPaidDate'];
        $date = NULL;
        if(!empty($datetime)){
            $date = date('Y-m-d h:i:s', strtotime($datetime));
        }
        H::ExcelLog(print_r(request()->all(),true),'dbNRR.log');
        //Nik here
        //$startDate = '2023-05-01';
        //$endDate = '2023-06-04';
        //$weeks = 5;

		$startDate = date("Y-m-d", strtotime("first Monday of Previous Month"));
		$endDate = date("Y-m-d", strtotime('next sunday', strtotime(date("Y-m-d", strtotime("last Monday of Previous Month")))));

		$startDateObj = new \DateTime($startDate);
		$endDateObj = new \DateTime($endDate);

		$diff = $endDateObj->diff($startDateObj);
		$weeks = ceil($diff->days / 7);

        $where = [];
        $states = [];
        if (isset($request['filters'])) {
            $filters = $request['filters'];
            $stats = [];
            $branch = [];
            foreach ($filters as $filter) {
                if ($filter['name'] == 'search[month]' && !empty($filter['value'])) {
                    H::ExcelLog($filter['name'] . ' - ' . $filter['value'], 'dbNRR.log');
                    if ($filter['value'] != '') {
                        $tmp = $filter['name'];
                        if ($filter['name'] == 'month'){
                            //dynamically create date range
                            if ($filter['name'] == 'search[month]'){
                                //dynamically create date range
                                //Nik here
                                if($filter['value'] != 'Pending') {
									$startDate = date("Y-m-d", strtotime("first Monday of " . $filter['value']));
									$endDate = date("Y-m-d", strtotime('next sunday', strtotime(date("Y-m-d", strtotime("last Monday of " . $filter['value'] )))));

									$startDateObj = new \DateTime($startDate);
									$endDateObj = new \DateTime($endDate);

									$diff = $endDateObj->diff($startDateObj);
									$weeks = ceil($diff->days / 7);
                                }
                            }
                        } 
                    }
                }
                if($filter['name'] == 'search[state][]' && !empty($filter['value'])){
                    $states[] = $filter['value'];
                }
                
            }

        }

        $data = [];
        //now get report data
        $statefilter = "";
        $stateFilterArray = [];
        if(!empty($states)){
            $serstate = "'".implode("','",$states)."'";
            //$statefilter .= "and (select mssql_facilities.State from mssql_recruitertracker rt LEFT JOIN mssql_facilities ON mssql_facilities.id = rt.aoc_facility_id where rt.user_id = whiteboard.user_id 
            //and rt.Candidate_Current_Status = 'First Shift Confirmed' and rt.Bonus_Status = 'Pending Hours' and rt.Candidate_Start_Date > '2022-12-31' GROUP BY whiteboard.user_id ) IN (".$serstate.")";
            foreach($states as $myState){
                if($statefilter==''){
                    $statefilter .= " and (f.State =  ?";
                    array_push($stateFilterArray, $myState);
                }else{
                    $statefilter .= " or f.State = ?";
                    array_push($stateFilterArray, $myState);
                }
            }
            $statefilter .= ")";
        }
        $qry = "SELECT rt.id
        FROM mssql_recruitertracker rt
        LEFT JOIN mssql_facilities f ON rt.aoc_facility_id = f.id
        where rt.Candidate_Start_Date > '2022-12-31' and rt.Bonus_Status = 'Pending Hours' and rt.Hours_Worked >=40 and Candidate_Current_Status = 'First Shift Confirmed' ".$statefilter;

        H::ExcelLog($qry,'dbBonusReport.log');
        $tmp = DB::select($qry, $stateFilterArray);
        foreach($tmp as $data){
            if($data->id != '' && $data->id != NULL){
                $record = MssqlRecruitertracker::where('id', $data->id)->update(['mssql_recruitertracker.Bonus_Status' => 'Submitted To Payroll', 'mssql_recruitertracker.bonus_period' => $startDate, 'mssql_recruitertracker.Bonus_Paid_Date' => $date]);

                $noteData = [];
                $noteData['entity'] = 'tracker-activity';
                $noteData['entity_id'] = $data->id;
                $noteData['notes'] = "Bonus submitted to payroll for payment on ".$request['bonusPaidDate']."";
    
                H::getHelper("ActivityNotes")->setActivityNotes($noteData);
            }
        }
        return response()->json(['success' => 1,'message' => 'Record is updated.']);
    }
}
