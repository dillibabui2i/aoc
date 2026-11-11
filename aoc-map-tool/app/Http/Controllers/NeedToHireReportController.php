<?php

namespace App\Http\Controllers;

use App\Enums\NthNewHoursReasons;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Reports;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NeedToHireReportController extends Controller
{
    
    public function nthHoursAdded()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'branch';
        if(isset(request()->source)) {
            $source = request()->source;
        }
        H::ExcelLog(print_r(request()->source,true),'dbnrr.log');
        return $this->nthActivity('Hours Added', 'added', $period, $source);
    }

    public function nthHoursRemoved()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'branch';
        if(isset(request()->source)) {
            $source = request()->source;
        }
        H::ExcelLog(print_r(request()->source,true),'dbnrr.log');
        return $this->nthActivity('Hours Removed', 'removed', $period, $source);
    }

    public function nthHoursClassification()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }

        $source = 'classification';
        if(isset(request()->source)) {
            $source = request()->source;
        }
        H::ExcelLog(print_r(request()->source,true),'dbnrr.log');
        $title = 'NTH Hours Classification';
        $type = 'classification';
        if(isset(request()->type)) {
            $source = request()->type;
        }

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }
        

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = $title;
        $report['id'] = $type;
        $report['period'] = $period;

        //columns defines the fieldname, heading, optional format, width and style for each column
        if($source=='branch'){
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'FacilityName',
                    'heading' => ['title' => 'Branch', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Classification',
                    'heading' => ['title' => 'Classification', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ]
            ];
        }else{
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Classification',
                    'heading' => ['title' => 'Classification', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ]
            ];
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            $count = 0;
            if($report['period'] == 'week') {
                $count = H::datediffInWeeks($sdate, $tdate);
            }
            if($report['period'] == 'month') {
                $count = H::monthDiff($sdate, $tdate);
            }
            if($report['period'] == 'day') {
                //add a day because we don't really want the difference, we want the number of days in the range
                $count = abs(round((strtotime($tdate) - strtotime($sdate)) / 86400)) + 1;
            }
            $periods = H::getReportPeriodColumns($report['period'], $count, request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $clickedFilters = [];
        if ($source == 'branch') {
            $clickedFilters = [
                'FacilityName',
                'Classification',
            ];
        } else {
            $clickedFilters = [
                'State',
                'Classification',
            ];
        }

        foreach($periods as $period){
            $report['columns'][] = [   
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '1decimal', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNTHReport', 'clickedfilters' => $clickedFilters,'defaultval' => ['start_of_week' => $period['name']]],
            ];
        }
        $report['columns'][] = [   
            'field' => 'Total',
            'heading' => ['title' => 'Total', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
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
        if ($source == 'branch') {
            $report['groupby'] = [
                    ['id' => 1, 'label' => '', 'field' => 'FacilityName', 'showCols' => 'FacilityName', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 1],
                    ['id' => 2, 'label' => '@group2', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
                    ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                    ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                    ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
                ];
        }else{
            $report['groupby'] = [
                ['id' => 1, 'label' => '@group1', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 1],
                ['id' => 2, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 2],
                ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
            ];
        }

        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            //$endingDate = H::getReportEndingDate($report['period'], request()->date['to']);
            $endingDate = request()->date['to'];
        } else {
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        H::ExcelLog('before filters date range: ' . $startDate . ' - ' . $endingDate,'dbNTH.log');
        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branches  = [];
        if (LD::checkFeatureFlag('add-payer-filter-to-nth')) {
            $filters = [
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches
                ],
                [
                    'label' => 'Service Needed',
                    'name' => 'ServicesNeeded',
                    'type' => 'multiselect',
                    'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                ],
                [
                    'label'=>'Payer',
                    'name'=>'nth-payer',
                    'type'=>'multiselect',
                    'source'=>H::getHelper('ConstantHelper')->getPayers()
                ],
                [
                    'label' => 'Payer Group',
                    'name' => 'payer_group',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getPayerGroup()
                ],
                [
                    'label' => 'Date',
                    'name' => 'daterange',
                    'type' => 'daterange',
                    'fromdate' => H::getFormatedDate($startDate),
                    'todate' => H::getFormatedDate($endingDate),
                ]
            ];
        } else {
            $filters = [
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches
                ],
                [
                    'label' => 'Service Needed',
                    'name' => 'ServicesNeeded',
                    'type' => 'multiselect',
                    'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                ],
                [
                    'label' => 'Date',
                    'name' => 'daterange',
                    'type' => 'daterange',
                    'fromdate' => $startDate,
                    'todate' => $endingDate,
                ]
            ];
        }
		$sourceFilters = [
			[
				'label' => 'By Classification',
				'value' => 'classification'
            ],
			[
				'label' => 'By Branch',
				'value' => 'branch'
            ]
		];
        
        H::ExcelLog(print_r(request()->all(),true),'dbNRR.log');
        
        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value,true), 'dbNRR.log');
                        $tmp = $field;
                        if ($field == 'state') {
                            $tmp = 'mssql_facilities.State';
                        }
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mssql_facilities.id';
                        }
                        if (LD::checkFeatureFlag('add-payer-filter-to-nth')) {
                            if ($field == 'nth-payer') {
                                $tmp = 'mssql_patients.Payer';
                            }
                            if ($field == 'payer_group') {
                                $tmp = 'mssql_patients.payer_group';
                            }
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNRR.log');
                        if ($value != '') {
                            $tmp = $field;
                            if ($field == 'state') {
                                $tmp = 'mssql_facilities.State';
                            }
                            if ($field == 'aoc_facility_id') {
                                $tmp = 'mssql_facilities.id';
                            }
                            $where[] = $tmp . '= ?';
                            array_push($paramsArray, $value);
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'],'DATE(audit.created_at)');
        $data = [];
        //now get report data
        $nonAdmit = $nonAdmit2 = '';
        if (LD::checkFeatureFlag('fix-need-to-hire-calculation')) {
            $nonAdmit = "(audit.Reason LIKE '%Non-admit' OR audit.Reason in ('Non Admit'))";
            $nonAdmit2 = "(audit.Reason LIKE '%Non-admit' OR audit.Reason in ('Non-admit','Discharged','Staffed','AOC - Staffed'))";
        }
        else {
            $nonAdmit = "audit.Reason = 'Non-admit'";
            $nonAdmit2 = "audit.Reason in ('Non-admit','Discharged','Staffed','AOC - Staffed')";
        }
        $qry = "with audit as 
                (Select NeedToHire_Audit.* from NeedToHire_Audit where isNet <> 1 and created_at > ? and created_at < CONCAT(?,' 23:59:59')),
                cte as 
                    (select mssql_facilities.State, mssql_facilities.FacilityName, audit.Type, 
                        CASE WHEN " . $nonAdmit . " THEN 'Non Admit'
                            WHEN audit.Reason = 'Discharged' THEN 'Discharged'
                            WHEN audit.Reason LIKE '%Not Staffed%' THEN NULL
                            WHEN audit.Reason like '%Staffed' THEN 'Staffed'
                            WHEN audit.Reason in (" . NthNewHoursReasons::getSqlString() . ") THEN 'New Hours'
                            ELSE 'Previously Staffed Hours' END AS Classification, " . $periodQuery . " as Monday, 
                        CASE WHEN " . $nonAdmit2 . " THEN NetChangeinHours
                            WHEN audit.Reason LIKE '%Not Staffed%' THEN 0
                            WHEN audit.Reason in (" . NthNewHoursReasons::getSqlString() . ") THEN HoursAdded
                            ELSE NetChangeinHours END AS Hours,
                        CASE WHEN " . $nonAdmit . " THEN 5
                            WHEN audit.Reason = 'Discharged' THEN 4
                            WHEN audit.Reason like '%Staffed' THEN 3
                            WHEN audit.Reason in (" . NthNewHoursReasons::getSqlString() . ") THEN 1
                            ELSE 2 END AS SortOrder
                    FROM audit 
                    left join NeedToHire_Schedules on audit.schedule_id = NeedToHire_Schedules.id 
                    left join NeedToHire_Accounts on NeedToHire_Schedules.aoc_nthaccounts_id = NeedToHire_Accounts.id 
                    left join mssql_patients on NeedToHire_Accounts.aoc_patient_id = mssql_patients.id 
                    left join mssql_facilities on mssql_patients.aoc_facility_id = mssql_facilities.id ".(count($where) > 0 ? " WHERE " . implode(" AND " , $where) : '')."), ";
        if($source=='branch'){
            //includes Facilityname in grouping
            $qry .= " cte2 as 
                        (select cte.State, cte.FacilityName, cte.SortOrder, cte.Classification, cte.Monday, sum(cte.Hours) as myCount
                            from cte 
                            group by State, FacilityName, cte.SortOrder, cte.Classification, Monday
                            order by State, FacilityName, cte.SortOrder, cte.Classification, Monday)
                    select cte2.* from cte2
                    where cte2.Classification IS NOT NULL";
        }else{
            //does not include FAcilityName in grouping
            $qry .= " cte2 as 
                        (select cte.State, cte.SortOrder, cte.Classification, cte.Monday, sum(cte.Hours) as myCount
                            from cte 
                            group by State, SortOrder, Classification, Monday
                            order by State, SortOrder, Classification, Monday)
                    select cte2.* from cte2
                    where cte2.Classification IS NOT NULL";
        }
        H::ExcelLog($qry,'dbNRR.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if($source=='branch'){
                $id = $record['State'] . "|" . $record['FacilityName'] . "|" . $record['Classification'];
                if(!isset($data[$id])){
                    $data[$id] = ['State' => $record['State'],
                        'FacilityName' => $record['FacilityName'],
                        'Classification' => $record['Classification'],
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['Total'] = 0;
                }
            }else{
                $id = $record['State'] . "|" . $record['Classification'];
                if(!isset($data[$id])){
                    $data[$id] = ['State' => $record['State'],
                        'Classification' => $record['Classification'],
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['Total'] = 0;
                }
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount']; 
        }

        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.needToHireReports', ['report' => $report, 'data' => $data, 'filters' => $filters, 'sourceFilters' => $sourceFilters]);
    }

    public function nthActivity($title, $type, $period, $source)
    {

        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'branch';
        if(isset(request()->source)) {
            $source = request()->source;
        }
        H::ExcelLog(print_r(request()->source,true),'dbNTH.log');

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }
       
        //define report settings
        $report = [];
        $data = [];
        $report['title'] = $title;
        $report['id'] = $type;
        $report['period'] = $period;

        //columns defines the fieldname, heading, optional format, width and style for each column
        if($source=='branch'){
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'FacilityName',
                    'heading' => ['title' => 'Branch', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Reason',
                    'heading' => ['title' => 'Reason', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ]
            ];
        } elseif ($source == 'user') {
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'User',
                    'heading' => ['title' => 'User', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Reason',
                    'heading' => ['title' => 'Reason', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ]
            ];
        }else{
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Reason',
                    'heading' => ['title' => 'Reason', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ]
            ];
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('from: ' . request()->date['from'],'dbNTH.log');
            H::ExcelLog('to: ' . request()->date['to'],'dbNTH.log');
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = request()->date['to'];
            H::ExcelLog('sdate: ' . $sdate,'dbNTH.log');
            H::ExcelLog('tdate: ' . $tdate,'dbNTH.log');
            $count = 0;
            if($report['period'] == 'week') {
                $count = H::datediffInWeeks($sdate, $tdate);
            }
            if($report['period'] == 'month') {
                $count = H::monthDiff($sdate, $tdate);
            }
            if($report['period'] == 'day') {
                //add a day because we don't really want the difference, we want the number of days in the range
                $count = abs(round((strtotime($tdate) - strtotime($sdate)) / 86400)) + 1;
            }
            H::ExcelLog('count: ' . $count,'dbNTH.log');
            $periods = H::getReportPeriodColumns($report['period'], $count, request()->date['to']);
            H::ExcelLog(print_r($periods,true),'dbNTH.log');
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $clickedFilters = [];
        if ($source == 'branch') {
            $clickedFilters = [
                'FacilityName',
                'Reason',
            ];
        } elseif ($source == 'user') {
            $clickedFilters = [
                'State',
                'User',
                'Reason',
            ];
        } else {
            $clickedFilters = [
                'State',
                'Reason',
            ];
        }

        foreach($periods as $period){
            $report['columns'][] = [   
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '1decimal', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNTHReport', 'clickedfilters' => $clickedFilters,'defaultval' => ['start_of_week' => $period['name']]],
            ];
        }
        $report['columns'][] = [   
            'field' => 'Total',
            'heading' => ['title' => 'Total', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
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
        if ($source == 'branch') {
            $report['groupby'] = [
                    ['id' => 1, 'label' => '', 'field' => 'FacilityName', 'showCols' => 'FacilityName', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 1],
                    ['id' => 2, 'label' => '@group2', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
                    ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                    ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                    ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
                ];
        } elseif ($source == 'user') {
            $report['groupby'] = [
                    ['id' => 1, 'label' => '', 'field' => 'User', 'showCols' => 'User', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 1],
                    ['id' => 2, 'label' => '@group2', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
                    ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                    ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                    ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
                ];
        } else {
            $report['groupby'] = [
                ['id' => 1, 'label' => '@group1', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 1],
                ['id' => 2, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 2],
                ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
            ];
        }

        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        H::ExcelLog('before filters date range: ' . $startDate . ' - ' . $endingDate,'dbNTH.log');
        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branches  = [];
        if (LD::checkFeatureFlag('add-payer-filter-to-nth')) {
            if (LD::checkFeatureFlag('nth-report-view-by-user')) {
                $filters = [
                    [
                        'label' => 'State',
                        'name' => 'state',
                        'type' => 'multiselect',
                        'source' => $state
                    ],
                    [
                        'label' => 'Branch',
                        'name' => 'aoc_facility_id',
                        'type' => 'multiselect',
                        'source' => $branches
                    ],
                    [
                        'label' => 'Service Needed',
                        'name' => 'ServicesNeeded',
                        'type' => 'multiselect',
                        'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                    ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                    ],
                    [
                        'label'=>'Client Care Coordinator',
                        'name'=>'ClientCareCoordinator',
                        'type'=>'multiselect',
                        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
                    ],
                    [
                        'label' => 'Recruiter',
                        'name' => 'recruiter',
                        'type' => 'multiselect',
                        'source' => H::getHelper("ConstantHelper")->getRecruitersList(false)
                    ],
                    [
                        'label'=>'Payer',
                        'name'=>'nth-payer',
                        'type'=>'multiselect',
                        'source'=>H::getHelper('ConstantHelper')->getPayers()
                    ],
                    [
                        'label' => 'Payer Group',
                        'name' => 'payer_group',
                        'type' => 'multiselect',
                        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
                    ],
                    [
                        'label' => 'Date',
                        'name' => 'daterange',
                        'type' => 'daterange',
                        'fromdate' => H::getFormatedDate($startDate),
                        'todate' => H::getFormatedDate($endingDate),
                    ]
                ];
            } else {
                $filters = [
                    [
                        'label' => 'State',
                        'name' => 'state',
                        'type' => 'multiselect',
                        'source' => $state
                    ],
                    [
                        'label' => 'Branch',
                        'name' => 'aoc_facility_id',
                        'type' => 'multiselect',
                        'source' => $branches
                    ],
                    [
                        'label' => 'Service Needed',
                        'name' => 'ServicesNeeded',
                        'type' => 'multiselect',
                        'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                    ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                    ],
                    [
                        'label'=>'Payer',
                        'name'=>'nth-payer',
                        'type'=>'multiselect',
                        'source'=>H::getHelper('ConstantHelper')->getPayers()
                    ],
                    [
                        'label' => 'Payer Group',
                        'name' => 'payer_group',
                        'type' => 'multiselect',
                        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
                    ],
                    [
                        'label' => 'Date',
                        'name' => 'daterange',
                        'type' => 'daterange',
                        'fromdate' => H::getFormatedDate($startDate),
                        'todate' => H::getFormatedDate($endingDate),
                    ]
                ];
            }
        } else {
            if (LD::checkFeatureFlag('nth-report-view-by-user')) {
                $filters = [
                    [
                        'label' => 'State',
                        'name' => 'state',
                        'type' => 'multiselect',
                        'source' => $state
                    ],
                    [
                        'label' => 'Branch',
                        'name' => 'aoc_facility_id',
                        'type' => 'multiselect',
                        'source' => $branches
                    ],
                    [
                        'label' => 'Service Needed',
                        'name' => 'ServicesNeeded',
                        'type' => 'multiselect',
                        'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                    ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                    ],
                    [
                        'label'=>'Client Care Coordinator',
                        'name'=>'ClientCareCoordinator',
                        'type'=>'multiselect',
                        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
                    ],
                    [
                        'label' => 'Recruiter',
                        'name' => 'recruiter',
                        'type' => 'multiselect',
                        'source' => H::getHelper("ConstantHelper")->getRecruitersList(false)
                    ],
                    [
                        'label' => 'Date',
                        'name' => 'daterange',
                        'type' => 'daterange',
                        'fromdate' => H::getFormatedDate($startDate),
                        'todate' => H::getFormatedDate($endingDate),
                    ]
                ];
            } else {
                $filters = [
                    [
                        'label' => 'State',
                        'name' => 'state',
                        'type' => 'multiselect',
                        'source' => $state
                    ],
                    [
                        'label' => 'Branch',
                        'name' => 'aoc_facility_id',
                        'type' => 'multiselect',
                        'source' => $branches
                    ],
                    [
                        'label' => 'Service Needed',
                        'name' => 'ServicesNeeded',
                        'type' => 'multiselect',
                        'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                    ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                    ],
                    [
                        'label' => 'Date',
                        'name' => 'daterange',
                        'type' => 'daterange',
                        'fromdate' => H::getFormatedDate($startDate),
                        'todate' => H::getFormatedDate($endingDate),
                    ]
                ];
            }
        }
		$sourceFilters = [
			[
				'label' => 'By Branch',
				'value' => 'branch'
            ],
			[
				'label' => 'By Reason',
				'value' => 'reason'
			]
		];

        if (LD::checkFeatureFlag('nth-report-view-by-user')) {
            $sourceFilters[] = [
				'label' => 'By User',
				'value' => 'user'
			];
        }
        
        H::ExcelLog(print_r(request()->all(),true),'dbNRR.log');
        
        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value,true), 'dbNRR.log');
                        $tmp = $field;
                        if ($field == 'state') {
                            $tmp = 'mssql_facilities.State';
                        }
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mssql_facilities.id';
                        }
                        if (LD::checkFeatureFlag('add-payer-filter-to-nth')) {
                            if ($field == 'nth-payer') {
                                $tmp = 'mssql_patients.Payer';
                            }
                            if ($field == 'payer_group') {
                                $tmp = 'mssql_patients.payer_group';
                            }
                        }
                        if (LD::checkFeatureFlag('nth-report-view-by-user')) {
                            if ($field == 'recruiter') {
                                $tmp = 'NeedToHire_Accounts.Recruiter';
                            }
                            if ($field == 'ClientCareCoordinator') {
                                $tmp = 'mssql_patients.ccc_id';
                            }
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNRR.log');
                        if ($value != '') {
                            $tmp = $field;
                            if ($field == 'state') {
                                $tmp = 'mssql_facilities.State';
                            }
                            if ($field == 'aoc_facility_id') {
                                $tmp = 'mssql_facilities.id';
                            }
                            if (LD::checkFeatureFlag('add-payer-filter-to-nth')) {
                                if ($field == 'nth-payer') {
                                    $tmp = 'mssql_patients.Payer';
                                }
                            }
                            if (LD::checkFeatureFlag('nth-report-view-by-user')) {
                                if ($field == 'recruiter') {
                                    $tmp = 'NeedToHire_Accounts.Recruiter';
                                }
                                if ($field == 'ClientCareCoordinator') {
                                    $tmp = 'mssql_patients.ccc_id';
                                }
                            }
                            $where[] = $tmp . '= ?';
                            array_push($paramsArray, $value);
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'],'DATE(audit.created_at)');

        //now get report data
        if($type=='added'){ 
            $auditType = 'A';
            $whereCode = 'where myCount > 0';
        }else{
            $auditType = 'R';
            $whereCode = 'where myCount < 0';
        }
        H::ExcelLog('Start report for ' . $type . ' - ' . $source . ' with date range ' . $startDate . ' to ' . $endingDate . ' and period of ' . $report['period'],'dbNthReport.log');
        $nonAdmit = '';
        if (LD::checkFeatureFlag('fix-need-to-hire-calculation')) {
            $nonAdmit = "(cte.Reason LIKE '%Non-admit' OR cte.Reason in ('Discharged','Non-admit','Staffed',
            'Client match - partial','New Intake','Caregiver - Authorized for Additional Hours','Caregiver - New Eligibility',
            'Caregiver - Utilizing Additional Hours','New Intake','Caregiver - Authorized for Additional Hours',
            'Caregiver - New Eligibility', 'Caregiver - Utilizing Additional Hours','Client match - partial'))";
        }
        else {
            $nonAdmit = "cte.Reason in ('Discharged','Non-admit','Staffed','Client match - partial','New Intake',
            'Caregiver - Authorized for Additional Hours','Caregiver - New Eligibility',
            'Caregiver - Utilizing Additional Hours','New Intake','Caregiver - Authorized for Additional Hours',
            'Caregiver - New Eligibility','Caregiver - Utilizing Additional Hours','Client match - partial')";
        }
        if (LD::checkFeatureFlag('nth-report-view-by-user')) {
            $qry = "with audit as 
                        (Select NeedToHire_Audit.* from NeedToHire_Audit where isNet <> 1 and created_at > ? and created_at < CONCAT(?,' 23:59:59')),
                    cte as 
                        (select mssql_facilities.State, mssql_facilities.FacilityName, audit.Type, audit.Reason, users.name as User, " . $periodQuery . " as Monday, 
                                sum(NetChangeinHours) as Hours
                            FROM audit 
                                        left join NeedToHire_Schedules on audit.schedule_id = NeedToHire_Schedules.id 
                                        left join NeedToHire_Accounts on NeedToHire_Schedules.aoc_nthaccounts_id = NeedToHire_Accounts.id 
                                        left join mssql_patients on NeedToHire_Accounts.aoc_patient_id = mssql_patients.id 
                                        left join mssql_facilities on mssql_patients.aoc_facility_id = mssql_facilities.id
                                        left join users on NeedToHire_Accounts.user_id = users.id
                                        ".(count($where) > 0 ? " WHERE " . implode(" AND " , $where) : '')."
                        group by mssql_facilities.State, mssql_facilities.FacilityName, audit.Type, audit.Reason, " . $periodQuery . "),
                    cte2 as 
                        (select cte.State, cte.FacilityName, cte.Type, cte.Reason, cte.User, cte.Monday, cte.Hours,
                        case when cte.Reason like '%error%' then cte.Hours 
                            when " . $nonAdmit . "
                            and cte.Type = '" . $auditType . "' then cte.Hours
                        else 
                            cte.Hours 
                        end as netHours";
        } else {
            $qry = "with audit as 
                    (Select NeedToHire_Audit.* from NeedToHire_Audit where isNet <> 1 and created_at > ? and created_at < CONCAT(?,' 23:59:59')),
                cte as 
                    (select mssql_facilities.State, mssql_facilities.FacilityName, audit.Type, audit.Reason, " . $periodQuery . " as Monday, 
                            sum(NetChangeinHours) as Hours
                        FROM audit 
                                    left join NeedToHire_Schedules on audit.schedule_id = NeedToHire_Schedules.id 
                                    left join NeedToHire_Accounts on NeedToHire_Schedules.aoc_nthaccounts_id = NeedToHire_Accounts.id 
                                    left join mssql_patients on NeedToHire_Accounts.aoc_patient_id = mssql_patients.id 
                                    left join mssql_facilities on mssql_patients.aoc_facility_id = mssql_facilities.id
                                    ".(count($where) > 0 ? " WHERE " . implode(" AND " , $where) : '')."
                    group by mssql_facilities.State, mssql_facilities.FacilityName, audit.Type, audit.Reason, " . $periodQuery . "),
                cte2 as 
                    (select cte.State, cte.FacilityName, cte.Type, cte.Reason, cte.Monday, cte.Hours,
                    case when cte.Reason like '%error%' then cte.Hours 
                        when " . $nonAdmit . "
                        and cte.Type = '" . $auditType . "' then cte.Hours
                    else 
                        cte.Hours 
                    end as netHours";
        }
        if($source=='branch'){
            //includes Facilityname in grouping
            $qry .= " from cte),
                    cte3 as (
                        select cte2.State, cte2.FacilityName, cte2.Reason, cte2.Monday, sum(cte2.netHours) as myCount
                        from cte2 
                        where cte2.netHours <> 0
                        group by State, FacilityName, Reason, Monday
                        order by State, FacilityName, Reason, Monday)
                    select cte3.* from cte3 " . $whereCode;                
        } elseif ($source == 'user') {
            //grouping by user
            $qry .= " from cte),
                    cte3 as (
                        select cte2.State, cte2.User, cte2.Reason, cte2.Monday, sum(cte2.netHours) as myCount
                        from cte2 
                        where cte2.netHours <> 0
                        group by State, User, Reason, Monday
                        order by State, User, Reason, Monday)
                    select cte3.* from cte3 " . $whereCode;                
        } else {
            //does not include FAcilityName in grouping
            $qry .= " from cte),
                    cte3 as (         
                    select cte2.State, cte2.Reason, cte2.Monday, sum(cte2.netHours) as myCount
                    from cte2 
                    where cte2.netHours <> 0
                    group by State, Reason, Monday
                    order by State, Reason, Monday)
                    select cte3.* from cte3 " . $whereCode;
        }
        H::ExcelLog($qry,'dbNthReport.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if($source=='branch'){
                $id = $record['State'] . "|" . $record['FacilityName'] . "|" . $record['Reason'];
                if(!isset($data[$id])){
                    $data[$id] = ['State' => $record['State'],
                        'FacilityName' => $record['FacilityName'],
                        'Reason' => $record['Reason'],
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['Total'] = 0;
                }
            } elseif($source == 'user') {
                $id = $record['State'] . "|" . $record['User']. "|" . $record['Reason'];
                if(!isset($data[$id])){
                    $data[$id] = ['State' => $record['State'],
                        'User' => $record['User'],
                        'Reason' => $record['Reason'],
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['Total'] = 0;
                }
            } else {
                $id = $record['State'] . "|" . $record['Reason'];
                if(!isset($data[$id])){
                    $data[$id] = ['State' => $record['State'],
                        'Reason' => $record['Reason'],
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['Total'] = 0;
                }
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount']; 
        }

        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.needToHireReports', ['report' => $report, 'data' => $data, 'filters' => $filters, 'sourceFilters' => $sourceFilters]);
    }

    public function hoursOnNTH()
    {
        $report = [];
        $data = [];
        $report['title'] = 'Hours on NTH';
        $report['id'] = 'hoursOnNTH';
        //columns defines the fieldname, heading, optional format, width and style for each column
        $report['columns'] = [
            [
                'field' => 'State',
                'heading' => ['title' => 'State', 'attributes' => ' width="40%"', 'style' => ''],
                'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [
                'field' => 'FacilityName',
                'heading' => ['title' => 'Branch', 'attributes' => ' width="40%"', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ],
            [   
                'field' => 'ShiftHours',
                'heading' => ['title' => 'Hours on NTH', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '0decimal', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNTHReport', 'clickedfilters' => ['FacilityName','State']],
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
        $report['groupby'] = [
            ['id' => 1, 'label' => '@group1', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 1],
            ['id' => 2, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 2],
            ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
            ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
            ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
        ];

        //now define filters
        $state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
        $branches  = [];
        if (LD::checkFeatureFlag('add-payer-filter-to-nth')) {
            $filters = [
                [
                    'label' => 'Is Intake?',
                    'name' => 'is_intake',
                    'type' => 'select',
                    'source' => [["value" => '0', 'text' => "No"], ["value" => '1', 'text' => "Yes"]]
                ],
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches
                ],
                [
                    'label' => 'Service Needed',
                    'name' => 'ServicesNeeded',
                    'type' => 'multiselect',
                    'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                ],
                [
                    'label'=>'Payer',
                    'name'=>'nth-payer',
                    'type'=>'multiselect',
                    'source'=>H::getHelper('ConstantHelper')->getPayers()
                ],
                [
                    'label' => 'Payer Group',
                    'name' => 'payer_group',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getPayerGroup()
                ]
            ];
        } else {
            $filters = [
                [
                    'label' => 'Is Intake?',
                    'name' => 'is_intake',
                    'type' => 'select',
                    'source' => [["value" => '0', 'text' => "No"], ["value" => '1', 'text' => "Yes"]]
                ],
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches
                ],
                [
                    'label' => 'Service Needed',
                    'name' => 'ServicesNeeded',
                    'type' => 'multiselect',
                    'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                ]
            ];
        }
        if (LD::checkFeatureFlag('add-filter-on-hoursOnNTH')) {
            $filters[] = [
                'label' => 'Client Care Coordinator',
                'name' => 'ccc',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
            ];

            $filters[] = [
                'label' => 'Intake Coordinator',
                'name' => 'intake_coordinator',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getIntakeCoordinator()
            ];

            $filters[] = [
                'label' => 'Recruiter',
                'name' => 'recruiter',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getRecruiters('id')
            ];
        }
        if (LD::checkFeatureFlag('add-meeting-nurses-filter-on-hoursOnNTH')) {
            $meetingNurseFilter = [[
                'label' => 'Meeting Nurses',
                'name' => 'CF_MeetingNurses',
                'type' => 'select',
                'source' => [["value" => 'No', 'text' => "No"], ["value" => 'Yes', 'text' => "Yes"]]
            ]];
            $filters = array_merge($meetingNurseFilter,$filters);
        }
        //define datasource
        $where = [];
        $paramsArray = [];
        H::ExcelLog(print_r(request()->all(),true),'dbNRR.log');
		if (request()->input('isajax')) {
			$search = request()->input('search');
			if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value,true), 'dbNRR.log');
                        $tmp = $field;
                        if ($field == 'state') {
                            $tmp = 'mssql_facilities.State';
                        }
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mssql_facilities.id';
                        }
                        if (LD::checkFeatureFlag('add-payer-filter-to-nth')) {
                            if ($field == 'nth-payer') {
                                $tmp = 'mssql_patients.Payer';
                            }
                            if ($field == 'payer_group') {
                                $tmp = 'mssql_patients.payer_group';
                            }
                        }
                        if (LD::checkFeatureFlag('add-filter-on-hoursOnNTH')) {
                            if ($field == 'ccc') {
                                $tmp = 'mssql_patients.ccc_id';
                            }
                            if ($field == 'intake_coordinator') {
                                $tmp = 'mssql_patients.ic_id';
                            }
                            if ($field == 'recruiter') {
                                $tmp = 'NeedToHire_Accounts.user_id';
                            }
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNRR.log');
                        if ($value != '') {
                            $tmp = $field;
                            if ($field == 'state') {
                                $tmp = 'mssql_facilities.State';
                            }
                            if ($field == 'aoc_facility_id') {
                                $tmp = 'mssql_facilities.id';
                            }
                            if (LD::checkFeatureFlag('add-filter-on-hoursOnNTH')) {
                                if ($field == 'ccc') {
                                    $tmp = 'mssql_patients.ccc_id';
                                }
                                if ($field == 'intake_coordinator') {
                                    $tmp = 'mssql_patients.ic_id';
                                }
                                if ($field == 'recruiter') {
                                    $tmp = 'NeedToHire_Accounts.user_id';
                                }
                            }
                            if ($field == 'CF_MeetingNurses') {
                                $where[] = $tmp . ' LIKE ?';
                                array_push($paramsArray, $value.'%');
                            } else {
                                $where[] = $tmp . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
			}
		}
        $qry = "SELECT  ifnull(mssql_facilities.State,'No EMR Match') as State,
                ifnull(mssql_facilities.id,21) as id, ifnull(mssql_facilities.FacilityName,'No EMR Match') as FacilityName,
                     sum(NeedToHire_Schedules.ShiftHours) as ShiftHours 
                FROM `NeedToHire_Schedules` 
                    left join NeedToHire_Accounts on NeedToHire_Accounts.id = NeedToHire_Schedules.aoc_nthaccounts_id 
                    left join mssql_patients on mssql_patients.id = NeedToHire_Accounts.aoc_patient_id
                    left join mssql_facilities on mssql_facilities.id = mssql_patients.aoc_facility_id
                    where NeedToHire_Accounts.IsActive = 1 and NeedToHire_Schedules.IsActive = 1 ".
                    (count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."
                    group by State, FacilityName";
        H::ExcelLog($qry,'dbNRR.log');
        $tmp = DB::select($qry, $paramsArray);
        $tmp = json_decode(json_encode($tmp), true);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            $id = $record['id'];
            foreach($record as $key => $val){
                if($key!='id') {
                    $data[$id][$key] = $val;
                }
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }
        
        return view('reports.needToHireReports', ['report' => $report, 'data' => $data, 'filters' => $filters]);
    }

    public function nthByMeetingNurses()
    {
        $report = [];
        $data = [];
        $source = 'meetingnurses';
        if(isset(request()->source)) {
            $source = request()->source;
        }
        $report['title'] = 'NTH by Meeting Nurses';
        $report['id'] = 'nthMeetingNurses';
        //columns defines the fieldname, heading, optional format, width and style for each column
        if($source=='branch'){
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => ' width="40%"', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'FacilityName',
                    'heading' => ['title' => 'Branch', 'attributes' => ' width="40%"', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'MeetingNurses',
                    'heading' => ['title' => 'Meeting Nurses', 'attributes' => ' width="40%"', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'OverUnder',
                    'heading' => ['title' => 'Over/Under 21', 'attributes' => ' width="40%"', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [   
                    'field' => 'ShiftHours',
                    'heading' => ['title' => 'Hours on NTH', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '0decimal', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                    'clickable' => [
                        'jsfunction' => 'openNTHReport',
                        'clickedfilters' => [
                            'State',
                            'FacilityName',
                            'MeetingNurses',
                            'OverUnder'
                        ]
                    ],
                ],
            ];
        }else{
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => ' width="40%"', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'MeetingNurses',
                    'heading' => ['title' => 'Meeting Nurses', 'attributes' => ' width="40%"', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'OverUnder',
                    'heading' => ['title' => 'Over/Under 21', 'attributes' => ' width="40%"', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [   
                    'field' => 'ShiftHours',
                    'heading' => ['title' => 'Hours on NTH', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '0decimal', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                    'clickable' => [
                        'jsfunction' => 'openNTHReport',
                        'clickedfilters' => [
                            'State',
                            'MeetingNurses',
                            'OverUnder'
                        ]
                    ],
                ],
            ];
    
        }
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
        if($source=='branch'){
            $report['groupby'] = [
                ['id' => 1, 'label' => '', 'field' => 'OverUnder', 'showCols' => 'OverUnder', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 1],
                ['id' => 2, 'label' => '', 'field' => 'MeetingNurses', 'showCols' => 'MeetingNurses', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 2],
                ['id' => 3, 'label' => '@group3', 'field' => 'FacilityName', 'showCols' => 'FacilityName', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 3],
                ['id' => 4, 'label' => '@group4', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 4],
                ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
            ];
        }else{
            $report['groupby'] = [
                ['id' => 1, 'label' => '', 'field' => 'OverUnder', 'showCols' => 'OverUnder', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 1],
                ['id' => 2, 'label' => '', 'field' => 'MeetingNurses', 'showCols' => 'MeetingNurses', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 2],
                ['id' => 3, 'label' => '@group3', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 3],
                ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
            ];
        }

        //now define filters
        $state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
        $branches  = [];
        if (LD::checkFeatureFlag('add-payer-filter-to-nth')) {
            $filters = [
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches
                ],
                [
                    'label' => 'Service Needed',
                    'name' => 'ServicesNeeded',
                    'type' => 'multiselect',
                    'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                ],
                [
                    'label'=>'Payer',
                    'name'=>'nth-payer',
                    'type'=>'multiselect',
                    'source'=>H::getHelper('ConstantHelper')->getPayers()
                ],
                [
                    'label' => 'Payer Group',
                    'name' => 'payer_group',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getPayerGroup()
                ],
            ];
        } else {
            $filters = [
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches
                ],
                [
                    'label' => 'Service Needed',
                    'name' => 'ServicesNeeded',
                    'type' => 'multiselect',
                    'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"], 
                                ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
                ],
            ];
        }
        //define datasource
        $where = [];
        $paramsArray = [];
        H::ExcelLog(print_r(request()->all(),true),'dbNRR.log');
		if (request()->input('isajax')) {
			$search = request()->input('search');
			if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value,true), 'dbNRR.log');
                        $tmp = $field;
                        if ($field == 'State') {
                            $tmp = 'mssql_facilities.State';
                        }
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mssql_facilities.id';
                        }
                        if (LD::checkFeatureFlag('add-payer-filter-to-nth')) {
                            if ($field == 'nth-payer') {
                                $tmp = 'mssql_patients.Payer';
                            }
                            if ($field == 'payer_group') {
                                $tmp = 'mssql_patients.payer_group';
                            }
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNRR.log');
                        if ($value != '') {
                            $tmp = $field;
                            if ($field == 'State') {
                                $tmp = 'mssql_facilities.State';
                            }
                            if ($field == 'aoc_facility_id') {
                                $tmp = 'mssql_facilities.id';
                            }
                            $where[] = $tmp . '= ?';
                            array_push($paramsArray, $value);
                        }
                    }
                }
			}
		}
        if($source=='branch'){
            $qry = "with cte AS
                        (SELECT  ifnull(mssql_facilities.State,'No EMR Match') as State, ifnull(mssql_facilities.FacilityName,'No EMR Match') as FacilityName,
                                            case when mssql_patients.CF_MeetingNurses like 'Yes%' then 'Yes'
                                                when mssql_patients.CF_MeetingNurses like 'No%' then 'No'
                                                else 'Undefined' end as MeetingNurses,
                                            case when TIMESTAMPDIFF(YEAR, Patient_DOB, CURDATE()) >=21 then 'Over 21'
                                                else 'Under 21' end as OverUnder,
                                            NeedToHire_Schedules.ShiftHours as ShiftHours 
                                        FROM `NeedToHire_Schedules` 
                                            left join NeedToHire_Accounts on NeedToHire_Accounts.id = NeedToHire_Schedules.aoc_nthaccounts_id 
                                            left join mssql_patients on mssql_patients.id = NeedToHire_Accounts.aoc_patient_id
                                            left join mssql_facilities on mssql_facilities.id = mssql_patients.aoc_facility_id
                                            where NeedToHire_Accounts.IsActive = 1 and NeedToHire_Schedules.IsActive = 1 ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."  
                        )
                        select concat(State,'|',FacilityName,'|',MeetingNurses,'|',OverUnder) as id,State,FacilityName, MeetingNurses,OverUnder,sum(ShiftHours) as ShiftHours
                        from cte 
                                            group by State, FacilityName, MeetingNurses, OverUnder
                                            order by State, Facilityname, MeetingNurses,OverUnder";
        }else{
            $qry = "with cte AS
            (SELECT  ifnull(mssql_facilities.State,'No EMR Match') as State, 
                                case when mssql_patients.CF_MeetingNurses like 'Yes%' then 'Yes'
                                    when mssql_patients.CF_MeetingNurses like 'No%' then 'No'
                                    else 'Undefined' end as MeetingNurses,
                                case when TIMESTAMPDIFF(YEAR, Patient_DOB, CURDATE()) >=21 then 'Over 21'
                                    else 'Under 21' end as OverUnder,
                                NeedToHire_Schedules.ShiftHours as ShiftHours 
                            FROM `NeedToHire_Schedules` 
                                left join NeedToHire_Accounts on NeedToHire_Accounts.id = NeedToHire_Schedules.aoc_nthaccounts_id 
                                left join mssql_patients on mssql_patients.id = NeedToHire_Accounts.aoc_patient_id
                                left join mssql_facilities on mssql_facilities.id = mssql_patients.aoc_facility_id
                                where NeedToHire_Accounts.IsActive = 1 and NeedToHire_Schedules.IsActive = 1 ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."  
            )
            select concat(State,'|',MeetingNurses,'|',OverUnder) as id, State, MeetingNurses,OverUnder,sum(ShiftHours) as ShiftHours
                from cte 
                                group by State, MeetingNurses, OverUnder
                                order by State, MeetingNurses,OverUnder";
        }
        H::ExcelLog($qry,'dbNRR.log');
        $tmp = DB::select($qry, $paramsArray);
        $tmp = json_decode(json_encode($tmp), true);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            $id = $record['id'];
            foreach($record as $key => $val){
                if($key!='id') {
                    $data[$id][$key] = $val;
                }
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }
        
        return view('reports.needToHireReports', ['report' => $report, 'data' => $data, 'filters' => $filters]);
    }

    public function getNthPopup(Request $request){
        $req = $request->all();
        H::ExcelLog(print_r($req,true),'dbNthPop.log');
        $rowFilter = json_decode($req['rowfilter'],true);
        $form = $req['form'];
        $filterReq = [];
        foreach($form as $key => $value) {
            //keep '0' values (e.g. Is Intake? = No) so the popup matches the grid
            if ($value['value'] === null || $value['value'] === '' || substr_count($value['name'], "date")) {
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
        if(isset($req['type'])){
            if($req['type']=='hoursOnNTH' || $req['type'] == 'nthMeetingNurses') {
                $query = DB::table("NeedToHire_Schedules")->select("mssql_patients.PatientName as Client","mssql_patients.PatientAccount as ClientID","NeedToHire_Accounts.ServicesNeeded as Services Needed",
                            "mssql_facilities.State","mssql_facilities.FacilityName as Branch","NeedToHire_Schedules.ShiftHours as NTH Hours")
                    ->leftJoin("NeedToHire_Accounts","NeedToHire_Schedules.aoc_nthaccounts_id",'=',"NeedToHire_Accounts.id");
                $query->leftJoin("mssql_patients",function($query){
                    $query->on("mssql_patients.id", "=", "NeedToHire_Accounts.aoc_patient_id");
                });
                $query->leftJoin("mssql_facilities",function($query){
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $query->where('NeedToHire_Accounts.IsActive', 1);
                $query->where('NeedToHire_Schedules.IsActive', 1);
                if(count($rowFilter)) {
                    $fieldmap = [
                        'FacilityName' => 'mssql_facilities.FacilityName',
                        'State' => 'mssql_facilities.State',
                        'Intake' => 'mssql_patients.is_intake',
                        'ServicesNeeded' => 'NeedToHire_Accounts.ServicesNeeded',
                        'aoc_facility_id' => 'mssql_facilities.id',
                        'state' => 'mssql_facilities.state',
                        'is_intake' => 'mssql_patients.is_intake',
                        'nth-payer' => 'mssql_patients.Payer',
                        'payer_group' => 'mssql_patients.payer_group',
                        'ccc' => 'mssql_patients.ccc_id',
                        'intake_coordinator' => 'mssql_patients.ic_id',
                        'recruiter' => 'NeedToHire_Accounts.user_id',
                        'MeetingNurses' => DB::raw("case when mssql_patients.CF_MeetingNurses like 'Yes%' then 'Yes'
                                when mssql_patients.CF_MeetingNurses like 'No%' then 'No'
                                else 'Undefined' end"),
                        'OverUnder' => DB::raw("case when TIMESTAMPDIFF(YEAR, Patient_DOB, CURDATE()) >=21 then 'Over 21'
                                else 'Under 21' end")
                    ];
                    $query->where(function($q) use($rowFilter, $fieldmap){
                        foreach($rowFilter as $rfkey => $rf) {
                            //the Meeting Nurses filter holds values like 'Yes - reason', so match it the same way the grid does
                            if ($rfkey === 'CF_MeetingNurses') {
                                $q->where('mssql_patients.CF_MeetingNurses', 'like', $rf . '%');
                                continue;
                            }
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
							if ($rf === null || $rf === '' || $rf === 'No EMR Match') {
								$q->whereRaw($rfkey . " IS NULL ");
							} else if (is_array($rf)) {
                                $q->whereIn($rfkey, $rf);
                            } else {
                                $q->where($rfkey, "=", $rf);
                            }
                        }
                    });
                }                
            }else{

                $enableServiceNeeded = LD::checkFeatureFlag('add-services-needed-to-nth-popup');

                $query =  DB::table("NeedToHire_Audit")
                    ->select(DB::raw("
                        NeedToHire_Accounts.id as `NTH Account`,
                        NeedToHire_Audit.created_at as `ChangedOn`,
                        mssql_patients.PatientName as `Client`,
                        mssql_patients.PatientAccount as `ClientID`
                        " . ($enableServiceNeeded ? ",
                            NeedToHire_Accounts.ServicesNeeded as `Services Needed`
                        " : "") . ",
                        users.name as `Changed By`,
                        NeedToHire_Audit.StartDayOfWeek as `Day`,
                        NeedToHire_Audit.StartHour,
                        NeedToHire_Audit.StartMinute,
                        NeedToHire_Audit.Type,
                        NeedToHire_Audit.Reason,
                        NeedToHire_Audit.HoursAdded as `Added`,
                        NeedToHire_Audit.HoursRemoved as `Removed`,
                        NeedToHire_Audit.NetChangeInHours
                    "))
                    ->leftJoin("NeedToHire_Schedules", "NeedToHire_Audit.schedule_id", '=', "NeedToHire_Schedules.id")
                    ->leftJoin("NeedToHire_Accounts", "NeedToHire_Schedules.aoc_nthaccounts_id", '=', "NeedToHire_Accounts.id")
                    ->leftJoin("mssql_patients", "NeedToHire_Accounts.aoc_patient_id", "=", "mssql_patients.id")
                    ->leftJoin("mssql_facilities", "mssql_patients.aoc_facility_id", "=", "mssql_facilities.id")
                    ->leftJoin("users", "NeedToHire_Audit.created_by", "=", "users.id")
                    ->where("NeedToHire_Audit.isNet", 0);
                if(count($rowFilter)) {
                    $fieldmap = [
                        'FacilityName' => 'mssql_facilities.FacilityName',
                        'State' => 'mssql_facilities.State',
                        'state' => 'mssql_facilities.State',
                        'aoc_facility_id' => 'mssql_patients.aoc_facility_id',
                        'ServicesNeeded' => 'NeedToHire_Accounts.ServicesNeeded',
                        'Reason' => 'NeedToHire_Audit.Reason',
                        'nth-payer' => 'mssql_patients.Payer',
                        'payer_group' => 'mssql_patients.payer_group',
                        'ClientCareCoordinator' => 'mssql_patients.ccc_id',
                        'recruiter' => 'NeedToHire_Accounts.Recruiter',
                        'User' => 'users.name'
                    ];
                    //the period radio is not always the 3rd form field, so look it up by name
                    $period = '';
                    foreach ($form as $formField) {
                        if ($formField['name'] == 'period') {
                            $period = $formField['value'];
                            break;
                        }
                    }
                    $query->where(function($q) use($rowFilter, $fieldmap, $period){
                        foreach($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if ($rfkey == 'start_of_week') {
                                if ($period == 'month') {
                                    $q->whereRaw('MONTH(NeedToHire_Audit.created_at) = Month(CONCAT(?,"-01")) AND YEAR(NeedToHire_Audit.created_at) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                }elseif($period == 'day') {
                                    $q->whereRaw('NeedToHire_Audit.created_at > ? and NeedToHire_Audit.created_at < CONCAT(?," 23:59:59")', [$rf, $rf]);
                                } else {
                                    $q->whereRaw('NeedToHire_Audit.created_at >= ? AND NeedToHire_Audit.created_at < (? + INTERVAL 7 - weekday(?) DAY)', [
                                        $rf,
                                        $rf,
                                        $rf
                                    ]);
                                }
                            } elseif($rfkey == 'Classification') {
                                $nonAdmit = '';
                                if (LD::checkFeatureFlag('fix-need-to-hire-calculation')) {
                                    $nonAdmit = "(NeedToHire_Audit.Reason LIKE '%Non-admit' OR NeedToHire_Audit.Reason in ('Non Admit'))";
                                } else {
                                    $nonAdmit = "NeedToHire_Audit.Reason = 'Non-admit'";
                                }
                                $q->whereRaw("CASE WHEN " . $nonAdmit . " THEN 'Non Admit'
                                            WHEN NeedToHire_Audit.Reason LIKE '%Not Staffed%' THEN 'Schedule Change (Not Staffed)'
                                            WHEN NeedToHire_Audit.Reason like '%Staffed' THEN 'Staffed'
                                            WHEN NeedToHire_Audit.Reason in (" . NthNewHoursReasons::getSqlString() . ") THEN 'New Hours'
                                            ELSE 'Previously Staffed Hours' END  = ?", $rf);
                            } else {
                                if (is_array($rf)) {
                                    $q->whereIn($rfkey, $rf);
                                } elseif(is_null($rf)) {
                                    $q->whereNull($rfkey);
                                } else {
                                    //$q->where($rfkey, "=", \DB::raw("'".$rf."'"));
                                    $q->where($rfkey, "=", $rf);
                                }
                            }
                        }
                    });
                }   
            }             
        }
        H::ExcelLog(print_r($query->toSql(),true),'dbNthPop.log');
        $result = $query->get()->toArray();

        return view('reports.popup',['data'=>$request->all(),"results"=>count($result)>0?$result:[]]);  
    } 

    public function generateMultiSelectQuery($field, $value, &$array)
    {
        $fieldStr = [];
        foreach ($value as $fieldValue) {
            $fieldStr[] = '?';
            array_push($array, $fieldValue);
        }
        return ' ' . $field . ' IN (' . implode(", ", $fieldStr) . ') ';
    }
}
