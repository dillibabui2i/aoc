<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Reports;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IpeaConversionReportController extends Controller
{
    
    public function IpeaConversion()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'state';
        if(isset(request()->source)) {
            $source = request()->source;
        }
        H::ExcelLog(print_r(request()->source,true),'dbnrr.log');

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }
      
        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'IPEA Conversion';
        $report['id'] = 'ipea_conversion';
        $report['period'] = $period;
        //columns defines the fieldname, heading, optional format, width and style for each column
        if($source=='branch' || $source=='state'){
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
                    'field' => 'result',
                    'heading' => ['title' => 'Result', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
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
                    'field' => 'Recruiter',
                    'heading' => ['title' => 'Recruiter', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'result',
                    'heading' => ['title' => 'Result', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
            ];           
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            $periods = H::getReportPeriodColumns($report['period'], ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate), request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $clickedFilters = [];
        if ($source == 'branch') {
            $clickedFilters = [
                'FacilityName',
                'result',
                'State',
                $source
            ];
        }elseif($source=='state'){
            $clickedFilters = [
                'result',
                'State',
                $source
            ];
        } else {
            $clickedFilters = [
                'FacilityName',
                'result',
                'State',
                'Recruiter',
                $source
            ];
        }
        foreach($periods as $period){
            $report['columns'][] = [   
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'pct', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openIpeaConversionReport', 'clickedfilters' => $clickedFilters,'defaultval' => ['start_of_week' => $period['name']]],
            ];
        }
        $report['columns'][] = [   
            'field' => 'Total',
            'heading' => ['title' => 'Total', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => 'pct', 'attributes' => '', 'style' => ''],
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
        if($source == 'branch' || $source=='state'){
            $report['groupby'] = [
                        ['id' => 1, 'label' => '', 'field' => 'FacilityName', 'showCols' => 'FacilityName', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 1],
                        ['id' => 2, 'label' => '@group2', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
                        ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                        ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                        ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
                    ];
        }else{
            $report['groupby'] = [
                        ['id' => 1, 'label' => '', 'field' => 'Recruiter', 'showCols' => 'Recruiter', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 1],
                        ['id' => 2, 'label' => '@group2', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
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

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branches  = [];
        if (LD::checkFeatureFlag('ipea-conversion-report-changes')) {
            $regions = H::getHelper("ConstantHelper")->getRegion('id');
            $sources = H::getHelper("ConstantHelper")->getAllSource();
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
                    'label' => 'Region',
                    'name' => 'region',
                    'type' => 'multiselect',
                    'source' => $regions
                ],
                [
                    'label' => 'Source',
                    'name' => 'source',
                    'type' => 'multiselect',
                    'source' => $sources
                ],
                [
                    'label'=>'Payer',
                    'name'=>'Payer',
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
                    'label'=>'Exclude PRN',
                    'name'=>'excludeprn',
                    'type'=>'checkbox',
                    'value'=>false
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
                    'label' => 'Date',
                    'name' => 'daterange',
                    'type' => 'daterange',
                    'fromdate' => H::getFormatedDate($startDate),
                    'todate' => H::getFormatedDate($endingDate),
                ]
            ];
        }
		$sourceFilters = [
            [
				'label' => 'State',
				'value' => 'state'
			],
            [
				'label' => 'Branch',
				'value' => 'branch'
            ],
            [
				'label' => 'Recruiter',
				'value' => 'recruiter'
			],
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
                        if($field=='aoc_facility_id'){
                            $tmp = 'rt.aoc_facility_id';
                        }elseif($field=='state') {
                            $tmp = "left(rt.whiteboard_row,2)";
                        }elseif($field=='Payer'){
                            $tmp = 'p1.Payer';
                        }elseif($field=='payer_group'){
                            $tmp = 'p1.payer_group';
                        }elseif($field=='region'){
                            $tmp = 'f.region';
                        }elseif($field=='source'){
                            $tmp = '(select ca.source  from candidate_applications ca where ca.candidate_id = recruiting_schedule.aoc_candidate_id order by ca.id desc limit 1)';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value,'dbNRR.log');
                        if($value!='') {
                            if($field=='aoc_facility_id'){
                                $where[] = 'rt.aoc_facility_id = ?';
                                array_push($paramsArray, $value);
                            }elseif($field=='state') {
                                $where[] = "left(rt.whiteboard_row,2) = ?";
                                array_push($paramsArray, $value);
                            }elseif($field=='excludeprn') {
                                $where[] = "(p1.isPRN = '0' OR p1.isPRN is null)";
                            }else{
                                $where[] = $field . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'],'appointment_date');
        //now get report data
        if($source=='branch'){
            $qry = "SELECT rt.aoc_facility_id, State, FacilityName, 
                    case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                        when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                        else ifnull(IPEA_onboarding_status,'Not Set') end as result,
            " . $periodQuery . " as Monday, 
            count(recruiting_schedule.id) as myCount  
            FROM `recruiting_schedule`
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients p1 on rt.aoc_patient_id = p1.id
            left join mssql_facilities f on rt.aoc_facility_id = f.id 
            where recruiting_schedule.type_id = 1 and appointment_date >= ? and appointment_date <= ?  ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). " AND recruiting_schedule.is_deleted <> 1
            group by State, FacilityName, aoc_facility_id, 
            case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery . "
            order by State, FacilityName, aoc_facility_id, 
                case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                    when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                    else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery;
        }elseif($source=='state'){
            $qry = "SELECT State, State as FacilityName, 
                    case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                        when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                        else ifnull(IPEA_onboarding_status,'Not Set') end as result,
            " . $periodQuery . " as Monday, 
            count(recruiting_schedule.id) as myCount  
            FROM `recruiting_schedule`
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients p1 on rt.aoc_patient_id = p1.id
            left join mssql_facilities f on rt.aoc_facility_id = f.id 
            where recruiting_schedule.type_id = 1 and appointment_date >= ? and appointment_date <= ?  ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). " AND recruiting_schedule.is_deleted <> 1
            group by State, FacilityName, 
            case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery . "
            order by State, FacilityName, 
                case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                    when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                    else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery;
        }else{
            $qry = "SELECT rt.aoc_facility_id, State, FacilityName, rt.Recruiter, 
                    case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                        when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                        else ifnull(IPEA_onboarding_status,'Not Set') end as result,
            " . $periodQuery . " as Monday, 
            count(recruiting_schedule.id) as myCount  
            FROM `recruiting_schedule`
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients p1 on rt.aoc_patient_id = p1.id
            left join mssql_facilities f on rt.aoc_facility_id = f.id 
            where recruiting_schedule.type_id = 1 and appointment_date >= ? and appointment_date <= ?  ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). " AND recruiting_schedule.is_deleted <> 1
            group by State, Recruiter,
            case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery . "
            order by State, Recruiter,
                case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                    when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                    else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery;
        }
        H::ExcelLog($qry,'dbNRR.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $data = [];
        $agg = [];
        //dynamically add fields for each period
        foreach($periods as $period){
            $agg['grand'][$period['name']] = 0;
        }
        $agg['grand']['Total'] = 0;
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            //this code is creating one array element in $data for each detail row
            $id = $record['State'] . "|" . $record['FacilityName'] . "|" . $record['result'];
            if(!isset($data[$id])){
                if($source == 'branch' || $source == 'state'){
                    $data[$id] = ['State' => $record['State'],
                        'FacilityName' => $record['FacilityName'],
                        'result' => $record['result'],
                    ];
                }elseif($source == 'recruiter'){
                    $id = $record['State'] . "|" . $record['Recruiter'] . "|" . $record['result'];
                    $data[$id] = ['State' => $record['State'],
                        'Recruiter' => $record['Recruiter'],
                        'result' => $record['result'],
                    ];
                }else{
                    $data[$id] = ['State' => $record['State'],
                        'FacilityName' => $record['FacilityName'],
                        'Recruiter' => $record['Recruiter'],
                        'result' => $record['result'],
                    ];
                }
                //dynamically add fields for each period
                foreach($periods as $period){
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
            }
            //this code is creating one element in $agg for each group level
            if(!isset($agg[$record['State']])){
                //dynamically add fields for each period
                foreach($periods as $period){
                    $agg[$record['State']][$period['name']] = 0;
                }
                $agg[$record['State']]['Total'] = 0;
                $agg[$record['State']]['Facilities'] = [];
            }
            if(!isset($agg[$record['State']]['Facilities'][$record['FacilityName']])){
                //dynamically add fields for each period
                foreach($periods as $period){
                    $agg[$record['State']]['Facilities'][$record['FacilityName']][$period['name']] = 0;
                }
                $agg[$record['State']]['Facilities'][$record['FacilityName']]['Total'] = 0;
                $agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'] = [];
            }
            if($source == 'branch' || $source=='state'){
                if(!isset($agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'][$record['result']])){
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'][$record['result']][$period['name']] = 0;
                    }
                    $agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'][$record['result']]['Total'] = 0;
                }
            }else{
                if(!isset($agg[$record['State']]['Recruiters'][$record['Recruiter']])){
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $agg[$record['State']]['Recruiters'][$record['Recruiter']][$period['name']] = 0;
                    }
                    $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Total'] = 0;
                    $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Results'] = [];
                }
                if(!isset($agg[$record['State']]['Recruiters'][$record['Recruiter']]['Results'][$record['result']])){
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Results'][$record['result']][$period['name']] = 0;
                    }
                    $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Results'][$record['result']]['Total'] = 0;
                }
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount']; 
            $agg[$record['State']][$record['Monday']] = (isset($agg[$record['State']][$record['Monday']]) ? $agg[$record['State']][$record['Monday']] : 0) + $record['myCount'];
            $agg[$record['State']]['Total'] = $agg[$record['State']]['Total'] + $record['myCount'];
            $agg[$record['State']]['Facilities'][$record['FacilityName']][$record['Monday']] = (isset($agg[$record['State']]['Facilities'][$record['FacilityName']][$record['Monday']]) ? $agg[$record['State']]['Facilities'][$record['FacilityName']][$record['Monday']] : 0) + $record['myCount'];
            $agg[$record['State']]['Facilities'][$record['FacilityName']]['Total'] = $agg[$record['State']]['Facilities'][$record['FacilityName']]['Total'] + $record['myCount'];
            if($source == 'branch' || $source=='state'){
                $agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'][$record['result']][$record['Monday']] = (isset($agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'][$record['result']][$record['Monday']]) ? $agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'][$record['result']][$record['Monday']] : 0) + $record['myCount'];
                $agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'][$record['result']]['Total'] = $agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'][$record['result']]['Total'] + $record['myCount'];
            }else{
                $agg[$record['State']]['Recruiters'][$record['Recruiter']][$record['Monday']] = (isset($agg[$record['State']]['Recruiters'][$record['Recruiter']][$record['Monday']]) ? $agg[$record['State']]['Recruiters'][$record['Recruiter']][$record['Monday']] : 0) + $record['myCount'];
                $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Total'] = $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Total'] + $record['myCount'];
                $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Results'][$record['result']][$record['Monday']] = (isset($agg[$record['State']]['Recruiters'][$record['Recruiter']]['Results'][$record['result']][$record['Monday']]) ? $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Results'][$record['result']][$record['Monday']] : 0) + $record['myCount'];
                $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Results'][$record['result']]['Total'] = $agg[$record['State']]['Recruiters'][$record['Recruiter']]['Results'][$record['result']]['Total'] + $record['myCount'];
            }
            $agg['grand'][$record['Monday']] = (isset($agg['grand'][$record['Monday']]) ? $agg['grand'][$record['Monday']] : 0) + $record['myCount'];
            $agg['grand']['Total'] = $agg['grand']['Total'] + $record['myCount'];
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.ipeaConversionReport', ['report' => $report, 'data' => $data, 'agg' => $agg, 'source' => $source, 'filters' => $filters, 'sourceFilters' => $sourceFilters]);
    }

    public function IpeaConversionCounts()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'state';
        if(isset(request()->source)) {
            $source = request()->source;
        }
        H::ExcelLog(print_r(request()->source,true),'dbnrr.log');

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }
      
        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'IPEA Conversion Counts';
        $report['id'] = 'ipea_conversion_counts';
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
                    'field' => 'result',
                    'heading' => ['title' => 'Result', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
            ];
        }elseif($source=='state'){
            $report['columns'] = [
                [
                    'field' => 'State',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'result',
                    'heading' => ['title' => 'Result', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
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
                    'field' => 'Recruiter',
                    'heading' => ['title' => 'Recruiter', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'result',
                    'heading' => ['title' => 'Result', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
            ];
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            $periods = H::getReportPeriodColumns($report['period'], ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate), request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $clickedFilters = [];
        if ($source == 'branch') {
            $clickedFilters = [
                'FacilityName',
                'result',
                'State',
                $source
            ];
        }elseif($source=='state'){
            $clickedFilters = [
                'result',
                'State',
                $source
            ];
        } else {
            $clickedFilters = [
                'FacilityName',
                'Branch_Location',
                'State',
                'result',
                $source
            ];
        }
        foreach($periods as $period){
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openIpeaConversionReport', 'clickedfilters' => $clickedFilters,'defaultval' => ['start_of_week' => $period['name']]],
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
        if($source=='state'){
            $report['groupby'] = [
                ['id' => 1, 'label' => '@group1', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 1],
                ['id' => 2, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 2],
                ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
            ];
        }elseif($source=='branch'){
            $report['groupby'] = [
                        ['id' => 1, 'label' => '', 'field' => 'FacilityName', 'showCols' => 'FacilityName', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 1],
                        ['id' => 2, 'label' => '@group2', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
                        ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                        ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                        ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
                    ];
        }else{
            $report['groupby'] = [
                ['id' => 1, 'label' => '', 'field' => 'Recruiter', 'showCols' => 'Recruiter', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 1],
                ['id' => 2, 'label' => '@group2', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
                ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                ['id' => 9, 'label' => 'Grand total', 'field' => '', 'showCols' => '', 'style' => 'background-color: #444444;color:#fff;', 'level' => 5],
            ];
        }
        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = H::getReportEndingDate($report['period'], request()->date['to']);
        } else {
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branches  = [];
        if (LD::checkFeatureFlag('ipea-conversion-report-changes')) {
            $regions = H::getHelper("ConstantHelper")->getRegion('id');
            $sources = H::getHelper("ConstantHelper")->getAllSource();
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
                    'label' => 'Region',
                    'name' => 'region',
                    'type' => 'multiselect',
                    'source' => $regions
                ],
                [
                    'label'=>'Payer',
                    'name'=>'Payer',
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
                    'label' => 'Source',
                    'name' => 'source',
                    'type' => 'multiselect',
                    'source' => $sources
                ],
                [
                    'label'=>'Exclude PRN',
                    'name'=>'excludeprn',
                    'type'=>'checkbox',
                    'value'=>false
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
                    'label' => 'Date',
                    'name' => 'daterange',
                    'type' => 'daterange',
                    'fromdate' => H::getFormatedDate($startDate),
                    'todate' => H::getFormatedDate($endingDate),
                ]
            ];
        }
		$sourceFilters = [
			[
				'label' => 'State',
				'value' => 'state'
            ],
            [
				'label' => 'Branch',
				'value' => 'branch'
            ],
            [
				'label' => 'Recruiter',
				'value' => 'recruiter'
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
                        if($field=='aoc_facility_id'){
                            $tmp = 'rt.aoc_facility_id';
                        }elseif($field=='state') {
                            $tmp = "left(rt.whiteboard_row,2)";
                        }elseif($field=='Payer'){
                            $tmp = 'p1.Payer';
                        }elseif($field=='payer_group'){
                            $tmp = 'p1.payer_group';
                        }elseif($field=='region'){
                            $tmp = 'f.region';
                        }elseif($field=='source'){
                            $tmp = '(select ca.source  from candidate_applications ca where ca.candidate_id = recruiting_schedule.aoc_candidate_id order by ca.id desc limit 1)';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value,'dbNRR.log');
                        if($value!='') {
                            if($field=='aoc_facility_id'){
                                $where[] = 'rt.aoc_facility_id = ?';
                                array_push($paramsArray, $value);
                            }elseif($field=='state') {
                                $where[] = "left(rt.whiteboard_row,2) = ?";
                                array_push($paramsArray, $value);
                            }elseif($field=='excludeprn') {
                                $where[] = "(p1.isPRN = '0' OR p1.isPRN is null)";
                            }else{
                                $where[] = $field . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'],'appointment_date');
        //now get report data
        if($source=='branch'){
            $qry = "SELECT rt.aoc_facility_id, State, FacilityName, 
                    case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                        when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                        else ifnull(IPEA_onboarding_status,'Not Set') end as result,
            " . $periodQuery . " as Monday, 
            count(recruiting_schedule.id) as myCount  
            FROM `recruiting_schedule`
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients p1 on rt.aoc_patient_id = p1.id
            left join mssql_facilities f on rt.aoc_facility_id = f.id 
            where recruiting_schedule.type_id = 1 and appointment_date >= ? and appointment_date <= ?  ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). " AND recruiting_schedule.is_deleted <> 1
            group by State, FacilityName, aoc_facility_id, 
            case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery . "
            order by State, FacilityName, aoc_facility_id, 
                case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                    when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                    else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery;
        }elseif($source=='state'){
            $qry = "SELECT State, 
                    case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                        when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                        else ifnull(IPEA_onboarding_status,'Not Set') end as result,
            " . $periodQuery . " as Monday, 
            count(recruiting_schedule.id) as myCount  
            FROM `recruiting_schedule`
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients p1 on rt.aoc_patient_id = p1.id
            left join mssql_facilities f on rt.aoc_facility_id = f.id 
            where recruiting_schedule.type_id = 1 and appointment_date >= ? and appointment_date <= ?  ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). " AND recruiting_schedule.is_deleted <> 1
            group by State, 
            case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery . "
            order by State,  
                case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                    when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                    else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery;
        }else{
            $qry = "SELECT rt.aoc_facility_id, rt.whiteboard_row, rt.Branch_Location, State, FacilityName, rt.Recruiter,
                    case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                        when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                        else ifnull(IPEA_onboarding_status,'Not Set') end as result,
            " . $periodQuery . " as Monday, 
            count(recruiting_schedule.id) as myCount  
            FROM `recruiting_schedule`
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients p1 on rt.aoc_patient_id = p1.id
            left join mssql_facilities f on rt.aoc_facility_id = f.id 
            where recruiting_schedule.type_id = 1 and appointment_date >= ? and appointment_date <= ?  ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). " AND recruiting_schedule.is_deleted <> 1
            group by State, Recruiter, 
            case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery . "
            order by State, Recruiter, 
                case when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' 
                    when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS'
                    else ifnull(IPEA_onboarding_status,'Not Set') end, " . $periodQuery;
        }
        H::ExcelLog($qry,'dbNRR.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $data = [];
        $agg = [];
        //dynamically add fields for each period
        foreach($periods as $period){
            $agg['grand'][$period['name']] = 0;
        }
        $agg['grand']['Total'] = 0;
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            //this code is creating one array element in $data for each detail row
            if($source=='state'){
                $id = $record['State'] . "|" . $record['result'];
            }elseif($source=='branch'){
                $id = $record['State'] . "|" . $record['FacilityName'] . "|" . $record['result'];
            }else{
                $id = $record['State'] . "|" . $record['Recruiter'] . "|" . $record['result'];
            }
            if(!isset($data[$id])){
                if($source=='state'){
                    $data[$id] = ['State' => $record['State'],
                                    'result' => $record['result'],
                                ];
                }elseif($source=='branch'){
                    $data[$id] = ['State' => $record['State'],
                                    'FacilityName' => $record['FacilityName'],
                                    'result' => $record['result'],
                                ];
                }else{
                    $data[$id] = ['State' => $record['State'],
                                'FacilityName' => $record['FacilityName'],
                                'Recruiter' => $record['Recruiter'],
                                'result' => $record['result'],
                                ];
                }
                //dynamically add fields for each period
                foreach($periods as $period){
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
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

        return view('reports.ipeaConversionReport', ['report' => $report, 'data' => $data, 'agg' => $agg, 'source' => $source, 'filters' => $filters, 'sourceFilters' => $sourceFilters]);
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

    public function getICRPopup(Request $request){
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
/*
        SELECT f.FacilityName as Branch, rt.Candidate, rt.Recruiter, appointment_date as `Ipea Date`, ifnull(IPEA_clinician_arrived,'None') as `Arrived`, ifnull(IPEA_onboarding_status,'None') as `Onboarding Status`, ifnull(IPEA_pending,'') as 'Pending'
FROM `recruiting_schedule` 
left join mssql_recruitertracker rt on tracker_id = rt.id
left join mssql_facilities f on rt.aoc_facility_id = f.id 
WHERE type_id = 1 and appointment_date > '2022-09-25' and rt.aoc_facility_id = 7
*/



        $query = DB::table("recruiting_schedule")
            ->select(
                "recruiting_schedule.id",
                "mssql_recruitertracker.Candidate",
                "mssql_facilities.FacilityName as Branch",
                "appointment_date as Ipea Date",
                DB::raw("(SELECT users.name from users left join users_has_roles on users.id = users_has_roles.user_id left join roles on users_has_roles.role_id = roles.id where roles.name='Orientation Coordinator' and users.status='1' and find_in_set(recruiting_schedule.ipea_location_id, users.branchid ) limit 1) as OC"),
                "mssql_recruitertracker.Recruiter",
                "mssql_recruitertracker.Client_Name as Client",
                DB::raw("ifnull(recruiting_schedule.IPEA_onboarding_status,'None') as `Onboarding Status`"),
                "recruiting_schedule.ipea_status as Last Step Completed",
                DB::raw("ifnull(recruiting_schedule.IPEA_pending,'') as `Pending`")
            )
            ->leftJoin("mssql_recruitertracker","recruiting_schedule.tracker_id",'=',"mssql_recruitertracker.id")
            ->leftJoin("mssql_facilities","mssql_recruitertracker.aoc_facility_id","=","mssql_facilities.id")
            ->where("recruiting_schedule.type_id",1)
            ->where("recruiting_schedule.is_deleted",0);
        if(count($rowFilter)) {
            $fieldmap = [
                'FacilityName' => 'mssql_facilities.FacilityName',
                'Branch_Location' => 'mssql_recruitertracker.Branch_Location',
                'whiteboard_row' => 'mssql_recruitertracker.whiteboard_row',
                'State' => 'mssql_facilities.State',
                'result' => DB::raw("(CASE when IPEA_clinician_arrived = 'Cancelled' then 'Cancelled' when  IPEA_clinician_arrived = 'NC/NS' then 'NC/NS' else ifnull(IPEA_onboarding_status,'Not Set') end)"),
				'aoc_facility_id' => 'mssql_recruitertracker.aoc_facility_id',
            ];
            $query->where(function($q) use($rowFilter, $fieldmap, $form){
                foreach($rowFilter as $rfkey => $rf) {
                    $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                    if ($rfkey == 'start_of_week') {
                        if ($form[2]['value'] == 'month') {
                            $q->whereRaw('MONTH(recruiting_schedule.appointment_date) = Month(CONCAT(?,"-01")) AND
                            YEAR(recruiting_schedule.appointment_date) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                        } else {
                            $q->whereRaw('recruiting_schedule.appointment_date >= ? AND
                            recruiting_schedule.appointment_date < (? + INTERVAL 7 - weekday(?) DAY)',[
                                $rf,
                                $rf,
                                $rf
                            ]);
                        }
                    } else {
                        if (is_array($rf)) {
                            $q->whereIn($rfkey, $rf);
                        } elseif(is_null($rf)) {
                            $q->whereNull($rfkey);
                        } else {
                            $q->where($rfkey, "=", $rf);
                        }
                    }
                }
            });
        }                
        H::ExcelLog(print_r($query->toSql(),true),'dbNrrPop.log');
        //if($query)
        $result = $query->get()->toArray();
        if(!empty($result)){
            foreach($result as $data){
                $id = $data->id;
                unset($data->id);
                $data->Action = '<a href="javascript:void(0);" onclick="updateNurseIpeaInfo('.$id.')"><i
                class="fa fa-vcard"></i></a>';
            }
        }
        return view('reports.popup',['data'=>$request->all(),"results"=>count($result)>0?$result:[]]);  
    }

}
