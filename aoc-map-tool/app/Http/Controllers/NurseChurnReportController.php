<?php

namespace App\Http\Controllers;

use App\Repositories\Reports\NurseChurn\NurseChurnSelectRepository;
use App\Services\JobCategory\JobCategoryService;
use App\Services\NurseChurnReport\ClinicianDisciplineMapper;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Enums\ClinicianDisciplineEnum;
use Illuminate\Http\Request;

class NurseChurnReportController extends Controller
{

    public function __construct(
        private JobCategoryService $jobCategoryService,
        private NurseChurnSelectRepository $nurseChurnSelectRepository,
        private ClinicianDisciplineMapper $disciplineMapper
    )
    {}

    public function nurseChurn()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $title = 'Nurse Churn';
        $type = 'nurseChurn';
        if(isset(request()->type)) {
            $source = request()->type;
        }


        //define report settings
        $report = [];
        $data = [];
        $report['title'] = $title;
        $report['id'] = $type;
        $report['period'] = $period;

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }

        //columns defines the fieldname, heading, optional format, width and style for each column
        $report['columns'] = [
            [
                'field' => 'metric',
                'heading' => ['title' => '', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
            ]
        ];
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('period:' . $report['period'],'dbNurseChurn.log');
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            $count = 0;
            if($report['period'] == 'quarter') {
                $count = H::datediffInQuartersNew($sdate, $tdate);
            }
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
            $periods = H::getReportPeriodColumnsNew($report['period'], $count, request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumnsNew($report['period']);
        }
        $clickedFilters = [];
        $clickedFilters = [
            'metric'
        ];

        foreach($periods as $period){
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '1decimal', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNurseChurnPopup', 'clickedfilters' => $clickedFilters,'defaultval' => ['start_of_week' => $period['name']]],
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
            //no grouping for this report
        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = H::getReportEndingDate($report['period'], request()->date['to']);
        } else {
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        H::ExcelLog('before filters date range: ' . $startDate . ' - ' . $endingDate,'dbNTH.log');
        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branches  = H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id');
        if (LD::checkFeatureFlag('nurse-churn-report-add-region')) {
            $regions = H::getHelper("ConstantHelper")->getRegion('id');
            $filters = [
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state
                ],
                [
                    'label' => 'Region',
                    'name' => 'region',
                    'type' => 'multiselect',
                    'source' => $regions
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

        H::ExcelLog(print_r(request()->all(),true),'dbNRR.log');

        $where = [];
        $where_candidates = [];
        $candidateParamsArray = [];
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
                        if ($field == 'region') {
                            $tmp = 'mssql_facilities.region';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                        if ($field === 'state') {
                            $value2 = [];

                            foreach ($value as $thisValue) {
                                if ($fullStateName = config('constant.states.' . $thisValue)) {
                                    $value2[] = $fullStateName;
                                }
                            }
                            $fieldStr = $this->generateMultiSelectQuery('jobs.state', $value2, $candidateParamsArray);
                            $where_candidates[] = $fieldStr;
                        }
                        if (LD::checkFeatureFlag('fix-nurse-churn-quarter-calculation')) {
                            if ($field === 'aoc_facility_id') {
                                $value2 = [];
                                foreach ($value as $thisValue) {
                                    $value2[] = substr(H::getHelper("ConstantHelper")->getFacilityById($thisValue), 5);
                                }
                                $fieldStr = $this->generateMultiSelectQuery('jobs.branch', $value2, $candidateParamsArray);
                                $where_candidates[] = $fieldStr;
                            }
                            if ($field === 'region') {
                                $value2 = DB::table('mssql_facilities')->whereIn('region', $value)->pluck('BranchName')->toArray();
                                $fieldStr = $this->generateMultiSelectQuery('jobs.branch', $value2, $candidateParamsArray);
                                $where_candidates[] = $fieldStr;
                            }
                        }
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

        $periodQuery = H::getReportPeriodDefinition($report['period'],'DATE(candidate_applications.application_date)');
        $data = [];
        //now get report data
        //candidates
        $activeCategories = $this->jobCategoryService->getFilteredCategories();

        $categoriesSql = "'" . implode(
                "','",
                array_map('addslashes', $activeCategories->pluck('category')->toArray())
            ) . "'";

        $qry = "with cte as
                (SELECT candidate_id, DATE_FORMAT(date_add(candidate_applications.application_date, interval  -WEEKDAY(candidate_applications.application_date) day),'%Y-%m-%d') as Monday, min(id) as appId
                    FROM `candidate_applications`
                    WHERE candidate_applications.application_date >= ? and candidate_applications.application_date <= ?
                    group by candidate_id, DATE_FORMAT(date_add(candidate_applications.application_date, interval  -WEEKDAY(candidate_applications.application_date) day),'%Y-%m-%d')
                )

                SELECT ".$periodQuery." as Monday, count(candidate_applications.id) as myCount
                FROM cte
                left join `candidate_applications` on cte.appId = candidate_applications.id
                left join candidates on candidate_applications.candidate_id = candidates.id
                left join jobs on candidate_applications.job_id = jobs.id
                WHERE candidate_applications.application_date >= ? and candidate_applications.application_date <= ?
                    and jobs.category in ($categoriesSql) ".(count($where_candidates) > 0 ? " AND " . implode(" AND " , $where_candidates) : '') . "
                            GROUP BY ".$periodQuery."
                            ORDER BY ".$periodQuery;
//print_r($candidateParamsArray);
//echo $qry;
//exit;
        H::ExcelLog($qry,'dbNurseChurn.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate,
            $startDate,
            $endingDate
        ], $candidateParamsArray));
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'candidates';
            if(!isset($data[$id])){
                $data[$id] = ['metric' => 'Candidates'];
                //dynamically add fields for each period
                foreach($periods as $period){
                    H::ExcelLog(print_r($period,true),'dbNurseChurn.log');
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }
        H::ExcelLog(print_r($data,true),'dbNurseChurn.log');
        //Hired Nurses
        $periodQuery = H::getReportPeriodDefinition($report['period'],'DATE(mssql_providers.HiredDate)');
        $qry = "SELECT " . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE mssql_providers.DisciplineName in ('RN','LVN','LPN') and IsClinician = 1 and mssql_providers.HiredDate >= ? and mssql_providers.HiredDate <= ?
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . "
                GROUP BY " . $periodQuery . "
                ORDER BY " . $periodQuery;

        H::ExcelLog($qry,'dbNurseChurn.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'hired_nurses';
            if(!isset($data[$id])){
                $data[$id] = ['metric' => 'Hired Nurses'];
                //dynamically add fields for each period
                foreach($periods as $period){
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }

        //Calculate Hired % of Candidates
        $id = 'hired_pct';
        $data[$id] = ['metric' => 'Hired % of Candidates'];
        //dynamically add fields for each period
        foreach($periods as $period){
            if(isset($data['candidates']) && floatval($data['candidates'][$period['name']])!=0 && isset($data['hired_nurses'])){
                $data[$id][$period['name']] = $data['hired_nurses'][$period['name']] / $data['candidates'][$period['name']];
            }else{
                $data[$id][$period['name']] = 0;
            }
        }
        if(isset($data['candidates']) && floatval($data['candidates']['Total'])!=0 && isset($data['hired_nurses'])){
            $data[$id]['Total'] = $data['hired_nurses']['Total'] / $data['candidates']['Total'];
        }else{
            $data[$id]['Total'] = 0;
        }

        //Nurses with Billable Shifts
        $periodQuery = H::getReportPeriodDefinition($report['period'],'DATE(mssql_providers.HiredDate)');
        $qry = "SELECT " . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE mssql_providers.DisciplineName in ('RN','LVN','LPN') and IsClinician = 1 and mssql_providers.has_billable_visit = 1 and mssql_providers.HiredDate >= ? and mssql_providers.HiredDate <= ?
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . "
                GROUP BY " . $periodQuery . "
                ORDER BY " . $periodQuery;

        H::ExcelLog($qry,'dbNurseChurn.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'nurses_with_shift';
            if(!isset($data[$id])){
                $data[$id] = ['metric' => 'Nurses w/Billable Shifts'];
                //dynamically add fields for each period
                foreach($periods as $period){
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }

        //Nurses without Billable Shifts
        $periodQuery = H::getReportPeriodDefinition($report['period'],'DATE(mssql_providers.HiredDate)');
        $qry = "SELECT " . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE mssql_providers.DisciplineName in ('RN','LVN','LPN') and IsClinician = 1  and ifnull(mssql_providers.has_billable_visit,0) = 0 and mssql_providers.HiredDate >= ? and mssql_providers.HiredDate <= ?
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . "
                GROUP BY " . $periodQuery . "
                ORDER BY " . $periodQuery;

        H::ExcelLog($qry,'dbNurseChurn.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'nurses_with_no_shift';
            if(!isset($data[$id])){
                $data[$id] = ['metric' => 'Nurses w/No Billable Shifts'];
                //dynamically add fields for each period
                foreach($periods as $period){
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }

        //Calculate Total Hires
        $id = 'total_hires';
        $data[$id] = ['metric' => 'Total Hires'];
        //dynamically add fields for each period
        foreach($periods as $period){
            $data[$id][$period['name']] = isset($data['hired_nurses']) ? $data['hired_nurses'][$period['name']] : '';
        }
        $data[$id]['Total'] = isset($data['hired_nurses']) ? $data['hired_nurses']['Total'] : 0;

        //Calculate % of nurses with billable shifts
        $id = 'pct_shifts';
        $data[$id] = ['metric' => '% Nurses w/Billable Shifts'];
        //dynamically add fields for each period
        foreach($periods as $period){
            if(isset($data['nurses_with_shift']) && floatval($data['total_hires'][$period['name']])!=0){
                $data[$id][$period['name']] = $data['nurses_with_shift'][$period['name']] / $data['total_hires'][$period['name']];
            }else{
                $data[$id][$period['name']] = 0;
            }
        }
        if(isset($data['nurses_with_shift']) && floatval($data['total_hires']['Total'])!=0){
            $data[$id]['Total'] = $data['nurses_with_shift']['Total'] / $data['total_hires']['Total'];
        }else{
            $data[$id]['Total'] = 0;
        }

        //Nurses working after 30
        $periodQuery = H::getReportPeriodDefinition($report['period'],'DATE(mssql_providers.HiredDate)');
        $qry = "SELECT " . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE mssql_providers.DisciplineName in ('RN','LVN','LPN') and IsClinician = 1 and  ifnull(mssql_providers.has_billable_visit,0) = 1 and DATEDIFF(mssql_providers.latest_schedule,mssql_providers.HiredDate) >= 30
                    and mssql_providers.HiredDate >= ? and mssql_providers.HiredDate <= CONCAT(?,' 23:59')
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . "
                GROUP BY " . $periodQuery . "
                ORDER BY " . $periodQuery;

        H::ExcelLog($qry,'dbNurseChurn.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'working_after_30';
            if(!isset($data[$id])){
                $data[$id] = ['metric' => 'Nurses working after 30 days'];
                //dynamically add fields for each period
                foreach($periods as $period){
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }

        //Calculate % of nurses working after 30
        $id = 'pct_after_30';
        $data[$id] = ['metric' => '30 Days'];
        //dynamically add fields for each period
        foreach($periods as $period){
            if(isset($data['working_after_30']) && isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift'][$period['name']])!=0){
                $data[$id][$period['name']] = $data['working_after_30'][$period['name']] / $data['nurses_with_shift'][$period['name']];
            }else{
                $data[$id][$period['name']] = 0;
            }
        }
        if(floatval(isset($data['working_after_30']) && isset($data['nurses_with_shift']) && $data['nurses_with_shift']['Total'])!=0){
            $data[$id]['Total'] = $data['working_after_30']['Total'] / $data['nurses_with_shift']['Total'];
        }else{
            $data[$id]['Total'] = 0;
        }

        //Nurses working after 90
        $periodQuery = H::getReportPeriodDefinition($report['period'],'DATE(mssql_providers.HiredDate)');
        $qry = "SELECT " . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE mssql_providers.DisciplineName in ('RN','LVN','LPN') and IsClinician = 1 and ifnull(mssql_providers.has_billable_visit,0) = 1 and DATEDIFF(mssql_providers.latest_schedule,mssql_providers.HiredDate) >= 90
                    and mssql_providers.HiredDate >= ? and mssql_providers.HiredDate <= ?
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . "
                GROUP BY " . $periodQuery . "
                ORDER BY " . $periodQuery;

        H::ExcelLog($qry,'dbNurseChurn.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'working_after_90';
            if(!isset($data[$id])){
                $data[$id] = ['metric' => 'Nurses working after 90 days'];
                //dynamically add fields for each period
                foreach($periods as $period){
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }

        //Calculate % of nurses working after 90
        $id = 'pct_after_90';
        $data[$id] = ['metric' => '90 Days'];
        //dynamically add fields for each period
        foreach($periods as $period){
            if(isset($data['working_after_90']) && isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift'][$period['name']])!=0){
                $data[$id][$period['name']] = $data['working_after_90'][$period['name']] / $data['nurses_with_shift'][$period['name']];
            }else{
                $data[$id][$period['name']] = 0;
            }
        }
        if(isset($data['working_after_90']) && isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift']['Total'])!=0){
            $data[$id]['Total'] = $data['working_after_90']['Total'] / $data['nurses_with_shift']['Total'];
        }else{
            $data[$id]['Total'] = 0;
        }

        //Nurses working after 150
        $periodQuery = H::getReportPeriodDefinition($report['period'],'DATE(mssql_providers.HiredDate)');
        $qry = "SELECT " . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE mssql_providers.DisciplineName in ('RN','LVN','LPN') and IsClinician = 1 and ifnull(mssql_providers.has_billable_visit,0) = 1 and DATEDIFF(mssql_providers.latest_schedule,mssql_providers.HiredDate) >= 150
                    and mssql_providers.HiredDate >= ? and mssql_providers.HiredDate <= ?
                    ".(count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . "
                GROUP BY " . $periodQuery . "
                ORDER BY " . $periodQuery;

        H::ExcelLog($qry,'dbNurseChurn.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);

        $id = 'working_after_150';
        $data[$id] = ['metric' => 'Nurses working after 150 days'];
        //dynamically add fields for each period
        foreach($periods as $period){
            $data[$id][$period['name']] = 0;
        }
        $data[$id]['Total'] = 0;
        foreach ($tmp as $record) {
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }

        //Calculate % of nurses working after 150
        $id = 'pct_after_150';
        $data[$id] = ['metric' => '150 Days'];
        //dynamically add fields for each period
        foreach($periods as $period){
            if(isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift'][$period['name']])!=0){
                $data[$id][$period['name']] = $data['working_after_150'][$period['name']] / $data['nurses_with_shift'][$period['name']];
            }else{
                $data[$id][$period['name']] = 0;
            }
        }
        if(isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift']['Total'])!=0){
            $data[$id]['Total'] = $data['working_after_150']['Total'] / $data['nurses_with_shift']['Total'];
        }else{
            $data[$id]['Total'] = 0;
        }

        return view('reports.nurseChurnReport', ['report' => $report, 'data' => $data, 'filters' => $filters]);
    }

    public function getNurseChurnPopup(Request $request){
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
        $startDate = '';
        $endingDate = '';
        $where = [];
        $where_candidates = [];
        $paramsArray = [];
        $candidateParamsArray = [];
        $period = $form[2]['value'];
        if ($period == 'week') {
            $startDate = $rowFilter['start_of_week'];
            $endingDate = date('Y-m-d', strtotime("+6 day", strtotime($startDate)));
        } elseif($period == 'month') {
            $startDate = $rowFilter['start_of_week']."-01";
            $endingDate = date('Y-m-t', strtotime($startDate));
        } elseif($period == 'quarter') {
            $startDate = $rowFilter['start_of_week']."-01";
            $endingDate = date('Y-m-t', strtotime("+2 months", strtotime($startDate)));
        }

        if (isset($rowFilter['state'])) {
            $where[] = $this->generateMultiSelectQuery('mssql_facilities.State', $rowFilter['state'], $paramsArray);

            $value2 = [];
            foreach ($rowFilter['state'] as $thisValue) {
                if ($fullStateName = config('constant.states.' . $thisValue)) {
                    $value2[] = $fullStateName;
                }
            }
            $where_candidates[] = $this->generateMultiSelectQuery('jobs.state', $value2, $candidateParamsArray);
        }

        if (isset($rowFilter['region'])) {
            $where[] = $this->generateMultiSelectQuery('mssql_facilities.region', $rowFilter['region'], $paramsArray);

			$regionIds = $regionData = [];
			$regionQry = 'select BranchName from mssql_facilities where ' . $this->generateMultiSelectQuery('mssql_facilities.region', $rowFilter['region'], $regionIds);
			$regionData = DB::select($regionQry, $regionIds);
			$regionData = json_decode(json_encode($regionData), true);

			$where_candidates[] = $this->generateMultiSelectQuery('jobs.branch', array_column($regionData, 'BranchName'), $candidateParamsArray);
        }

        if (isset($rowFilter['aoc_facility_id'])) {
            $where[] = $this->generateMultiSelectQuery('mssql_facilities.id', $rowFilter['aoc_facility_id'], $paramsArray);

			$regionIds = $regionData = [];
			$regionQry = 'select BranchName from mssql_facilities where ' . $this->generateMultiSelectQuery('mssql_facilities.id', $rowFilter['aoc_facility_id'], $regionIds);
			$regionData = DB::select($regionQry, $regionIds);
			$regionData = json_decode(json_encode($regionData), true);

            $where_candidates[] = $this->generateMultiSelectQuery('jobs.branch',  array_column($regionData, 'BranchName'), $candidateParamsArray);
        }

        $disciplines = [];
        $jobTitles = [];
        $lobs = config('constant.lobFilterNurseChurnReport.options');

        if (LD::checkFeatureFlag('add-lob-filter-to-nurse-churn-report')) {

            $mapping = config('constant.lobFilterNurseChurnReport.mapping');
            $selected_lobs = array_column($lobs, 'value'); // Default to all LOBs
            if (isset($rowFilter['lob'])) {

                $selected_lobs = $rowFilter['lob'];
                if (empty($selected_lobs)) {
                    $selected_lobs = array_column($lobs, 'value'); // If empty selection, treat as all
                }
            }
            // Collect disciplines and jobTitles based on selected LOBs
            foreach ($selected_lobs as $lobValue) {
                if (isset($mapping[$lobValue])) {
                    $disciplines = array_merge($disciplines, (array) $mapping[$lobValue]['discipline']);
                    $jobTitles = array_merge($jobTitles, (array) $mapping[$lobValue]['jobTitle']);
                }
            }

            $disciplines = array_unique($disciplines);
            $jobTitles = array_unique($jobTitles);

        } else {
            $disciplines = [
                ClinicianDisciplineEnum::DISCIPLINE_RN->value,
                ClinicianDisciplineEnum::DISCIPLINE_LVN->value,
                ClinicianDisciplineEnum::DISCIPLINE_LPN->value,
            ];
        }

        if (!empty($disciplines)) {
            $placeholders = implode(', ', array_fill(0, count($disciplines), '?'));
            $where[] = "mssql_providers.DisciplineName IN ($placeholders)";
            foreach ($disciplines as $discipline) {
                $paramsArray[] = $discipline;
            }
        }

        if (!empty($jobTitles)) {
            $likeClauses = [];
            foreach ($jobTitles as $jobTitle) {
                $likeClauses[] = "jobs.title LIKE ?";
                $candidateParamsArray[] = '%' . $jobTitle . '%';  // ADDED: Wildcards for partial matches
            }
            $where_candidates[] = '(' . implode(' OR ', $likeClauses) . ')';
        }

        $isFeatureFlag = LD::checkFeatureFlag('add-lob-filter-to-nurse-churn-report');

        if ($isFeatureFlag) {
            $this->disciplineMapper->applySearchForMappedDiscipline($rowFilter, $where);
        }

        $whereSql = count($where) ? ' AND ' . implode(' AND ', $where) : '';

        $version  = Cache::store('nurse_churn')->get('nurse_churn_report_version', 'initial');
        $cacheKey = 'nurse_churn_popup_' . $version . '_' . md5(serialize([
            $startDate,
            $endingDate,
            $rowFilter['metric'] ?? '',
            $whereSql,
            $paramsArray,
            $where_candidates,
            $candidateParamsArray,
            $isFeatureFlag,
        ]));

        $now = Carbon::now('America/Chicago');
        $next4am = $now->copy()->startOfDay()->addHours(4);
        if ($now->gte($next4am)) {
            $next4am->addDay();
        }
        $cacheTtl = (int) $now->diffInSeconds($next4am);

        $result = Cache::store('nurse_churn')->remember(
            $cacheKey,
            $cacheTtl,
            function () use ($rowFilter, $startDate, $endingDate, $isFeatureFlag, $whereSql, $paramsArray, $where_candidates, $candidateParamsArray) {
                $query = [];

                if (isset($rowFilter['metric'])) {
                    if ($rowFilter['metric'] == 'Candidates') {

                        $categoriesSql = '';
                        if (!$isFeatureFlag) {
                            $activeCategories = $this->jobCategoryService->getFilteredCategories();
                            $categoriesSql = "'" . implode(
                                    "','",
                                    array_map('addslashes', $activeCategories->pluck('category')->toArray())
                                ) . "'";
                        }

                        $whereCandidatesSql = count($where_candidates)
                            ? ' AND ' . implode(' AND ', $where_candidates)
                            : '';

                        $sql = $this->nurseChurnSelectRepository->candidatesSql($isFeatureFlag, $categoriesSql, $whereCandidatesSql);

                        $query = DB::select(
                            $sql,
                            array_merge([$startDate, $endingDate, $startDate, $endingDate], $candidateParamsArray));

                    } elseif ($rowFilter['metric'] == 'Hired Nurses') {

                        $sql = $this->nurseChurnSelectRepository->hiredNursesSql($isFeatureFlag, $whereSql);
                        $query = DB::select($sql, array_merge([$startDate, $endingDate], $paramsArray));

                    } elseif ($rowFilter['metric'] == 'Nurses w/No Billable Shifts') {

                        $sql = $this->nurseChurnSelectRepository->noBillableShiftsSql($isFeatureFlag, $whereSql);
                        $query = DB::select($sql, array_merge([$startDate, $endingDate], $paramsArray));

                    } elseif ($rowFilter['metric'] == 'Nurses w/Billable Shifts') {

                        $sql = $this->nurseChurnSelectRepository->billableShiftsSql($isFeatureFlag, $whereSql);
                        $query = DB::select($sql, array_merge([$startDate, $endingDate], $paramsArray));

                    } elseif ($rowFilter['metric'] == 'Nurses working after 30 days' || $rowFilter['metric'] == '30 Days') {

                        $sql = $this->nurseChurnSelectRepository->workingAfterDaysSql(30, $whereSql, $isFeatureFlag);
                        $query = DB::select($sql, array_merge([$startDate, $endingDate], $paramsArray));

                    } elseif ($rowFilter['metric'] == 'Nurses working after 90 days' || $rowFilter['metric'] == '90 Days') {

                        $sql = $this->nurseChurnSelectRepository->workingAfterDaysSql(90, $whereSql, $isFeatureFlag);
                        $query = DB::select($sql, array_merge([$startDate, $endingDate], $paramsArray));

                    } elseif ($rowFilter['metric'] == 'Nurses working after 150 days' || $rowFilter['metric'] == '150 Days') {

                        $sql = $this->nurseChurnSelectRepository->workingAfterDaysSql(150, $whereSql, $isFeatureFlag);
                        $query = DB::select($sql, array_merge([$startDate, $endingDate], $paramsArray));
                    }
                }

                $result = $query ?: [];
                if ($isFeatureFlag) {
                    $this->disciplineMapper->mapDiscipline($result);
                    $this->disciplineMapper->formatDateFields($result);
                }
                return $result;
            }
        );

        return view('reports.popup', ['data' => $request->all(), 'results' => count($result) > 0 ? $result : []]);
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
