<?php

namespace App\Http\Controllers\V2;

use App\Enums\ClinicianDisciplineEnum;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\QueryBuilder\NamedWhereIn;
use App\Helpers\QueryBuilder\NamedWhereLikeOr;
use App\Http\Controllers\NurseChurnReportController as ChurnController;
use App\Repositories\Reports\NurseChurn\NurseChurnSelectRepository;
use App\Services\JobCategory\JobCategoryService;
use App\Services\NurseChurnReport\ClinicianDisciplineMapper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class NurseChurnReportController extends ChurnController
{
    private const reportStateMap = [
        'AZ' => 'Arizona',
        'FL' => 'Florida',
        'TX' => 'Texas',
        'NC' => 'North Carolina',
        'PA' => 'Pennsylvania',
        'CO' => 'Colorado',
        'SC' => 'South Carolina',
        'TN' => 'Tennessee',
        'GA' => 'Georgia',
    ];

    public function __construct(
        private readonly JobCategoryService $jobCategoryService,
        private readonly NurseChurnSelectRepository $nurseChurnSelectRepository,
        private readonly ClinicianDisciplineMapper $disciplineMapper
    )
    {
        parent::__construct($this->jobCategoryService, $this->nurseChurnSelectRepository, $this->disciplineMapper);
    }

    public function nurseChurn()
    {
        $period = 'week';
        if (isset(request()->period)) {
            $period = request()->period;
        }

        $title = "Nurse Churn";
        $type = 'nurseChurn';
        if (isset(request()->type)) {
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
            ],
        ];
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('period:' . $report['period'], 'dbNurseChurn.log');
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            $count = 0;
            if ($report['period'] == 'quarter') {
                $count = H::datediffInQuartersNew($sdate, $tdate);
            }
            if ($report['period'] == 'week') {
                $count = H::datediffInWeeks($sdate, $tdate);
            }
            if ($report['period'] == 'month') {
                $count = H::monthDiff($sdate, $tdate);
            }
            if ($report['period'] == 'day') {
                //add a day because we don't really want the difference, we want the number of days in the range
                $count = abs(round((strtotime($tdate) - strtotime($sdate)) / 86400)) + 1;
            }
            $periods = H::getReportPeriodColumnsNew($report['period'], $count, request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumnsNew($report['period']);
        }

        $clickedFilters = [
            'metric',
        ];

        foreach ($periods as $period) {
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '1decimal', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNurseChurnPopup', 'clickedfilters' => $clickedFilters, 'defaultval' => ['start_of_week' => $period['name']]],
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
        /*
         * define the fields to group by for subtotals.  I set it up to allow up to 4 groupings.  The last one with id = 9 is for the grand totals.
         * leave 'field' empty for ones you do not need.  It will ignore it.
         * do not remove any or change the id values!!!!
         * make sure your query sorts the data in the same order as the grouping are defined!!!
         * it prints the subtotal row when the data in column 'field' changes
         * style = the style to apply to the <tr> tag
         * label = what to display before the grouping value use @group3 @group2 to insert the value of the grouping fields (number = groupID).
         *
         * Define groups with the lowest level first
         */

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = H::getReportEndingDate($report['period'], request()->date['to']);
        } else {
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }

        H::ExcelLog('before filters date range: ' . $startDate . ' - ' . $endingDate, 'dbNTH.log');
        $state = H::getHelper('ConstantHelper')->getDashboardState();
        $branches = H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id');
        $lobs = config('constant.lobFilterNurseChurnReport.options');

        if (LD::checkFeatureFlag('nurse-churn-report-add-region')) {
            $regions = H::getHelper('ConstantHelper')->getRegion('id');
            $filters = [
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state,
                ],
                [
                    'label' => 'Region',
                    'name' => 'region',
                    'type' => 'multiselect',
                    'source' => $regions,
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches,
                ],
                [
                    'label' => 'Date',
                    'name' => 'daterange',
                    'type' => 'daterange',
                    'fromdate' => H::getFormatedDate($startDate),
                    'todate' => H::getFormatedDate($endingDate),
                ],
            ];
        } else {
            $filters = [
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state,
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches,
                ],
                [
                    'label' => 'Date',
                    'name' => 'daterange',
                    'type' => 'daterange',
                    'fromdate' => H::getFormatedDate($startDate),
                    'todate' => H::getFormatedDate($endingDate),
                ],
            ];
        }

        $whereStatements = [];
        $where_candidates = [];
        $paramsArray = [];
        $disciplines = [];
        $jobTitles = [];
        $search = request()->input('search');

        if (LD::checkFeatureFlag('add-lob-filter-to-nurse-churn-report')) {

            array_unshift($filters, [
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'multiselect',
                'source' => $lobs,
            ]);

            $mapping = config('constant.lobFilterNurseChurnReport.mapping');
            $selected_lobs = array_column($lobs, 'value'); // Default to all LOBs
            if (isset($search['lob'])) {

                $selected_lobs = $search['lob'];
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
            $disIn = new NamedWhereIn('mssql_providers.DisciplineName', 'discipline', $disciplines);
            $whereStatements[] = (string) $disIn;
            $paramsArray = array_merge($paramsArray, $disIn->getNamedDictionary());
        }

        if (!empty($jobTitles)) {
            $likeOr = new NamedWhereLikeOr('jobs.title', 'jobTitle', $jobTitles);
            $where_candidates[] = $likeOr;
        }


        if (request()->input('isajax')) {
            unset($search['lob']);

            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        $tmp = $field;
                        $tmpNamedParam = ':' . $tmp;

                        if ($field == 'state') {
                            $tmp = 'mssql_facilities.State';
                            $tmpNamedParam = 'state';
                        }

                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mssql_facilities.id';
                            $tmpNamedParam = 'facility';
                        }

                        if ($field == 'region') {
                            $tmp = 'mssql_facilities.region';
                            $tmpNamedParam = 'region';
                        }
                        $whereIn = new NamedWhereIn($tmp, $tmpNamedParam, $value);
                        $whereStatements[] = (string) $whereIn;
                        $paramsArray = [...$paramsArray, ...$whereIn->getNamedDictionary()];

                        if ($field == 'state') {
                            $value2 = [];

                            foreach ($value as $thisValue) {
                                if (array_key_exists($thisValue, self::reportStateMap)) {
                                    $value2[] = self::reportStateMap[$thisValue];
                                }
                            }

                            $where_candidates[] = new NamedWhereIn('jobs.state', 'state', $value2);
                        }

                        if (LD::checkFeatureFlag('fix-nurse-churn-quarter-calculation')) {
                            if ($field == 'aoc_facility_id') {
                                $value2 = [];

                                foreach ($value as $thisValue) {
                                    $value2[] = substr(H::getHelper('ConstantHelper')->getFacilityById($thisValue), 5);
                                }

                                $where_candidates[] = new NamedWhereIn('jobs.branch', 'branch', $value2);
                            }

                            if ($field == 'region') {
                                $regions = DB::table('mssql_facilities')->whereIn('region', $value)->pluck('BranchName')->toArray();
                                $where_candidates[] = new NamedWhereIn('jobs.branch', 'region', $regions);
                            }
                        }
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNRR.log');

                        if ($value != '') {
                            $tmp = $field;
                            $tmpNamedParam = '';
                            if ($field == 'state') {
                                $tmp = 'mssql_facilities.State';
                                $tmpNamedParam = 'state';
                            }

                            if ($field == 'aoc_facility_id') {
                                $tmp = 'mssql_facilities.id';
                                $tmpNamedParam = 'facility';
                            }

                            $whereStatements[] = $tmp . ' = :' . $tmpNamedParam;
                            $paramsArray[$tmpNamedParam] = $value;
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'DATE(candidate_applications.application_date)');
        $categories = null;
        if (!LD::checkFeatureFlag('add-lob-filter-to-nurse-churn-report')) {
            $categories = new NamedWhereIn('jobs.category', 'category', $this->getActiveCategories());
        }

        $version  = Cache::store('nurse_churn')->get('nurse_churn_report_version', 'initial');
        $cacheKey = 'nurse_churn_' . $version . '_' . md5(serialize([
            $startDate,
            $endingDate,
            $report['period'],
            $whereStatements,
            $paramsArray,
            array_map('strval', $where_candidates),
            $disciplines,
            $jobTitles,
            $categories ? (string) $categories : null,
        ]));

        $now = Carbon::now('America/Chicago');
        $next4am = $now->copy()->startOfDay()->addHours(4);
        if ($now->gte($next4am)) {
            $next4am->addDay();
        }
        $cacheTtl = (int) $now->diffInSeconds($next4am);

        $data = Cache::store('nurse_churn')->remember(
            $cacheKey,
            $cacheTtl,
            function () use ($startDate, $endingDate, $report, $whereStatements, $paramsArray, $where_candidates, $categories, $periods, $periodQuery) {
            $data = [];

            $qry = "with cte as
            (SELECT candidate_id,
                DATE_FORMAT(date_add(candidate_applications.application_date, interval  -WEEKDAY(candidate_applications.application_date) day),'%Y-%m-%d') as Monday, min(id) as appId
                FROM `candidate_applications`
                WHERE candidate_applications.application_date >= :cteStartDate
                    and candidate_applications.application_date <= :cteEndDate
                GROUP BY candidate_id,
                    DATE_FORMAT(date_add(candidate_applications.application_date, interval  -WEEKDAY(candidate_applications.application_date) day),'%Y-%m-%d')
            )

            SELECT " . $periodQuery . ' as Monday, count(candidate_applications.id) as myCount
            FROM cte
                left join `candidate_applications` on cte.appId = candidate_applications.id
                left join candidates on candidate_applications.candidate_id = candidates.id
                left join jobs on candidate_applications.job_id = jobs.id
            WHERE candidate_applications.application_date >= :startDate
                and candidate_applications.application_date <= :endDate';

        $parameters = [
            'startDate' => $startDate,
            'endDate' => $endingDate,
            'cteStartDate' => $startDate,
            'cteEndDate' => $endingDate,
        ];

        if ($categories) {
            $qry .= ' AND ' . $categories;
            $parameters = [...$parameters, ...$categories->getNamedDictionary()];
        }

        foreach ($where_candidates as $inStatement) {
            $qry .= sprintf(' AND %s', $inStatement);
            $parameters = [...$parameters, ...$inStatement->getNamedDictionary()];
        }

        $qry .= 'GROUP BY ' . $periodQuery . '
            ORDER BY ' . $periodQuery;

        $tmp = DB::select($qry, $parameters);
        $tmp = json_decode(json_encode($tmp), true);

        foreach ($tmp as $record) {
            $id = 'candidates';
            if (!isset($data[$id])) {
                $data[$id] = ['metric' => 'Candidates'];
                //dynamically add fields for each period
                foreach ($periods as $period) {
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }

        //Hired Nurses
        $periodQuery = H::getReportPeriodDefinition($report['period'], 'DATE(mssql_providers.HiredDate)');
        $qry = 'SELECT ' . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                    LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE IsClinician = 1
                    and mssql_providers.HiredDate >= :startDate
                    and mssql_providers.HiredDate <= :endDate
                    " . (count($whereStatements) > 0 ? ' AND ' . implode(' AND ', $whereStatements) : '') . '
                GROUP BY ' . $periodQuery . '
                ORDER BY ' . $periodQuery;

        $tmp = DB::select($qry, [
            'startDate' => $startDate,
            'endDate' => $endingDate,
            ...$paramsArray,
        ]);
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'hired_nurses';

            if (!isset($data[$id])) {
                $data[$id] = ['metric' => 'Hired Nurses'];
                //dynamically add fields for each period
                foreach ($periods as $period) {
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
        foreach ($periods as $period) {
            if (isset($data['candidates']) && floatval($data['candidates'][$period['name']]) != 0 && isset($data['hired_nurses'])) {
                $data[$id][$period['name']] = $data['hired_nurses'][$period['name']] / $data['candidates'][$period['name']];
            } else {
                $data[$id][$period['name']] = 0;
            }
        }
        if (isset($data['candidates'])
            && floatval($data['candidates']['Total']) != 0
            && isset($data['hired_nurses'])) {
            $data[$id]['Total'] = $data['hired_nurses']['Total'] / $data['candidates']['Total'];
        } else {
            $data[$id]['Total'] = 0;
        }

        //Nurses with Billable Shifts
        $periodQuery = H::getReportPeriodDefinition($report['period'], 'DATE(mssql_providers.HiredDate)');
        $qry = 'SELECT ' . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                    LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE IsClinician = 1 and mssql_providers.has_billable_visit = 1
                    and mssql_providers.HiredDate >= :startDate
                    and mssql_providers.HiredDate <= :endDate
                    " . (count($whereStatements) > 0 ? ' AND ' . implode(' AND ', $whereStatements) : '') . '
                GROUP BY ' . $periodQuery . '
                ORDER BY ' . $periodQuery;

        H::ExcelLog($qry, 'dbNurseChurn.log');
        $tmp = DB::select($qry, [
            'startDate' => $startDate,
            'endDate' => $endingDate,
            ...$paramsArray,
        ]);
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'nurses_with_shift';
            if (!isset($data[$id])) {
                $data[$id] = ['metric' => 'Nurses w/Billable Shifts'];
                //dynamically add fields for each period
                foreach ($periods as $period) {
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['Total'] = 0;
            }
            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }

        //Nurses without Billable Shifts
        $periodQuery = H::getReportPeriodDefinition($report['period'], 'DATE(mssql_providers.HiredDate)');
        $qry = 'SELECT ' . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                    LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE IsClinician = 1
                    and ifnull(mssql_providers.has_billable_visit,0) = 0
                    and mssql_providers.HiredDate >= :startDate
                    and mssql_providers.HiredDate <= :endDate
                    " . (count($whereStatements) > 0 ? ' AND ' . implode(' AND ', $whereStatements) : '') . '
                GROUP BY ' . $periodQuery . '
                ORDER BY ' . $periodQuery;

        H::ExcelLog($qry, 'dbNurseChurn.log');
        $tmp = DB::select($qry, [
            'startDate' => $startDate,
            'endDate' => $endingDate,
            ...$paramsArray,
        ]);
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'nurses_with_no_shift';
            if (!isset($data[$id])) {
                $data[$id] = ['metric' => 'Nurses w/No Billable Shifts'];
                //dynamically add fields for each period
                foreach ($periods as $period) {
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
        foreach ($periods as $period) {
            $data[$id][$period['name']] = isset($data['hired_nurses']) ? $data['hired_nurses'][$period['name']] : '';
        }
        $data[$id]['Total'] = isset($data['hired_nurses']) ? $data['hired_nurses']['Total'] : 0;

        //Calculate % of nurses with billable shifts
        $id = 'pct_shifts';
        $data[$id] = ['metric' => '% Nurses w/Billable Shifts'];
        //dynamically add fields for each period
        foreach ($periods as $period) {
            if (isset($data['nurses_with_shift']) && floatval($data['total_hires'][$period['name']]) != 0) {
                $data[$id][$period['name']] = $data['nurses_with_shift'][$period['name']] / $data['total_hires'][$period['name']];
            } else {
                $data[$id][$period['name']] = 0;
            }
        }
        if (isset($data['nurses_with_shift']) && floatval($data['total_hires']['Total']) != 0) {
            $data[$id]['Total'] = $data['nurses_with_shift']['Total'] / $data['total_hires']['Total'];
        } else {
            $data[$id]['Total'] = 0;
        }

        //Nurses working after 30
        $periodQuery = H::getReportPeriodDefinition($report['period'], 'DATE(mssql_providers.HiredDate)');
        $qry = 'SELECT ' . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                    LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE IsClinician = 1
                    and  ifnull(mssql_providers.has_billable_visit,0) = 1
                    and DATEDIFF(mssql_providers.latest_schedule,mssql_providers.HiredDate) >= 30
                    and mssql_providers.HiredDate >= :startDate
                    and mssql_providers.HiredDate <= CONCAT(:endDate,' 23:59')
                    " . (count($whereStatements) > 0 ? ' AND ' . implode(' AND ', $whereStatements) : '') . '
                GROUP BY ' . $periodQuery . '
                ORDER BY ' . $periodQuery;

        $tmp = DB::select($qry, [
            'startDate' => $startDate,
            'endDate' => $endingDate,
            ...$paramsArray,
        ]);
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'working_after_30';
            if (!isset($data[$id])) {
                $data[$id] = ['metric' => 'Nurses working after 30 days'];
                //dynamically add fields for each period
                foreach ($periods as $period) {
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
        foreach ($periods as $period) {
            if (isset($data['working_after_30']) && isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift'][$period['name']]) != 0) {
                $data[$id][$period['name']] = $data['working_after_30'][$period['name']] / $data['nurses_with_shift'][$period['name']];
            } else {
                $data[$id][$period['name']] = 0;
            }
        }
        if (floatval(isset($data['working_after_30']) && isset($data['nurses_with_shift']) && $data['nurses_with_shift']['Total']) != 0) {
            $data[$id]['Total'] = $data['working_after_30']['Total'] / $data['nurses_with_shift']['Total'];
        } else {
            $data[$id]['Total'] = 0;
        }

        //Nurses working after 90
        $periodQuery = H::getReportPeriodDefinition($report['period'], 'DATE(mssql_providers.HiredDate)');
        $qry = 'SELECT ' . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                    LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE IsClinician = 1 and ifnull(mssql_providers.has_billable_visit,0) = 1 and DATEDIFF(mssql_providers.latest_schedule,mssql_providers.HiredDate) >= 90
                    and mssql_providers.HiredDate >= :startDate and mssql_providers.HiredDate <= :endDate
                    " . (count($whereStatements) > 0 ? ' AND ' . implode(' AND ', $whereStatements) : '') . '
                GROUP BY ' . $periodQuery . '
                ORDER BY ' . $periodQuery;

        H::ExcelLog($qry, 'dbNurseChurn.log');
        $tmp = DB::select($qry, [
            'startDate' => $startDate,
            'endDate' => $endingDate,
            ...$paramsArray,
        ]);
        $tmp = json_decode(json_encode($tmp), true);
        foreach ($tmp as $record) {
            $id = 'working_after_90';
            if (!isset($data[$id])) {
                $data[$id] = ['metric' => 'Nurses working after 90 days'];
                //dynamically add fields for each period
                foreach ($periods as $period) {
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
        foreach ($periods as $period) {
            if (isset($data['working_after_90']) && isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift'][$period['name']]) != 0) {
                $data[$id][$period['name']] = $data['working_after_90'][$period['name']] / $data['nurses_with_shift'][$period['name']];
            } else {
                $data[$id][$period['name']] = 0;
            }
        }
        if (isset($data['working_after_90']) && isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift']['Total']) != 0) {
            $data[$id]['Total'] = $data['working_after_90']['Total'] / $data['nurses_with_shift']['Total'];
        } else {
            $data[$id]['Total'] = 0;
        }

        //Nurses working after 150
        $periodQuery = H::getReportPeriodDefinition($report['period'], 'DATE(mssql_providers.HiredDate)');
        $qry = 'SELECT ' . $periodQuery . " as Monday, count(mssql_providers.id) as myCount
                FROM `mssql_providers`
                    LEFT JOIN mssql_facilities on mssql_providers.FacilityID = mssql_facilities.FacilityID
                WHERE IsClinician = 1
                    and ifnull(mssql_providers.has_billable_visit,0) = 1
                    and DATEDIFF(mssql_providers.latest_schedule, mssql_providers.HiredDate) >= 150
                    and mssql_providers.HiredDate >= :startDate
                    and mssql_providers.HiredDate <= :endDate
                    " . (count($whereStatements) > 0 ? ' AND ' . implode(' AND ', $whereStatements) : '') . '
                GROUP BY ' . $periodQuery . '
                ORDER BY ' . $periodQuery;

        H::ExcelLog($qry, 'dbNurseChurn.log');
        $tmp = DB::select($qry, [
            'startDate' => $startDate,
            'endDate' => $endingDate,
            ...$paramsArray,
        ]);
        $tmp = json_decode(json_encode($tmp), true);

        $id = 'working_after_150';
        $data[$id] = ['metric' => 'Nurses working after 150 days'];
        //dynamically add fields for each period
        foreach ($periods as $period) {
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
        foreach ($periods as $period) {
            if (isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift'][$period['name']]) != 0) {
                $data[$id][$period['name']] = $data['working_after_150'][$period['name']] / $data['nurses_with_shift'][$period['name']];
            } else {
                $data[$id][$period['name']] = 0;
            }
        }
        if (isset($data['nurses_with_shift']) && floatval($data['nurses_with_shift']['Total']) != 0) {
            $data[$id]['Total'] = $data['working_after_150']['Total'] / $data['nurses_with_shift']['Total'];
        } else {
            $data[$id]['Total'] = 0;
        }

            return $data;
        });

        return view('reports.nurseChurnReport', ['report' => $report, 'data' => $data, 'filters' => $filters]);
    }

    protected function getActiveCategories() : array
    {
        $activeCategories = $this->jobCategoryService->getFilteredCategories();
        $uniqueCategories = [];
        if ($activeCategories->count() > 0) {
            $uniqueCategories = array_unique($activeCategories->pluck('category')->toArray());
        }

        return $uniqueCategories;
    }
}
