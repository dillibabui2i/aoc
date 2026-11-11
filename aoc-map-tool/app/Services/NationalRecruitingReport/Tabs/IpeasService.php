<?php

namespace App\Services\NationalRecruitingReport\Tabs;

use App\Contracts\NationalRecruitingReport\ReportTabInterface;
use App\DTO\NationalRecruitingReport\TableParams;
use App\Enums\NRR\NRRTypes;
use App\Helpers\H;
use App\Helpers\LD;
use App\Repositories\NationalRecruitingReport\Tabs\RecruitingScheduleRepository;
use App\Services\Reports\ReportLobFilterService;
use App\Traits\NationalRecruitingReport\NRRTrait;
use App\User;
use Carbon\Carbon;
use Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

class IpeasService implements ReportTabInterface
{
    private const ZERO_WEEKS = 0;
    private const SIX_WEEKS = 6;
    private const THIRTEEN_WEEKS = 13;
    private const SEVEN_DAYS = 7;

    use NRRTrait;

    /**
     * @param TableParams $params
     * @param RecruitingScheduleRepository $recruitingScheduleRepository
     */
    public function __construct(
        protected TableParams $params,
        protected RecruitingScheduleRepository $recruitingScheduleRepository,
        protected ReportLobFilterService $lobFilterService
    )
    {
    }
    ////todo This method should be refactored. It is too long and complex. It was improved a bit
    /**
     * @return array
     * @throws \Exception
     */
    public function getTabData(): array
    {
        $period = 'week';
        $title = 'National IPEA Report';
        $type = 'ipea';
        $intakeData = [];

        if (isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'recruiter';
        if (isset(request()->source)) {
            $source = request()->source;
        }

        $report = [];
        $data = [];
        $report['title'] = $title;
        $report['id'] = $type;
        $report['period'] = $period;
        $report['goalField'] = 'ipea_goal';

        if ($type != 'ipea') {
            $report['goalField'] = 'pepi_wepi_goal';
        }
        if ($type == 'ipea') {
            $report['goalField'] = 'ipea_goal';
            $schedTypeId = 1;
        } elseif ($type == 'pepi') {
            $report['goalField'] = 'pepi_wepi_goal';
            $schedTypeId = 3;
        } elseif ($type == 'wepi') {
            $report['goalField'] = 'pepi_wepi_goal';
            $schedTypeId = 4;
        } elseif ($type == 'cm') {
            $report['goalField'] = 'client_match_goal';
            $schedTypeId = 6;
        } else {
            $report['goalField'] = 'ipea_goal';
            $schedTypeId = 1;
        }

        if (LD::checkFeatureFlag('save-report-filters-to-user-table')) {
            if ($this->params->isajax) {
                $search = $this->params->search;
                $date = $this->params->date;
                User::where('id', Auth::user()->id)->update([
                    'report_filters' => json_encode(array_merge($search, ['daterange' => $date]))
                ]);
            } else {
                $savedFilterData = User::find(Auth::user()->id)->report_filters;
                $savedFilterData = json_decode($savedFilterData, true) ?? [];
                if (Route::currentRouteName() != 'candidates') {
                    $fields = [
                        'lob',
                        'state',
                        'region',
                        'aoc_facility_id',
                        'Recruiter',
                        'excludeprn',
                        'Payer',
                        'payer_group',
                        'daterange'
                    ];

                    $savedFilterData = array_intersect_key($savedFilterData, array_flip($fields));
                    if (isset($savedFilterData['state']) && strlen($savedFilterData['state'][0]) > 2) {
                        unset($savedFilterData['state']);
                    }
                } else {
                    $fields = ['lob', 'state','branch', 'sourceType', 'appSource'];
                    $savedFilterData = array_intersect_key($savedFilterData, array_flip($fields));
                    if (isset($savedFilterData['state']) && strlen($savedFilterData['state'][0]) <= 2) {
                        unset($savedFilterData['state']);
                    }
                }
                if (isset($savedFilterData['daterange'])) {
                    unset($savedFilterData['daterange']);
                }
                $this->params->search = $savedFilterData;
                $this->params->isajax = true;
            }
            if (isset($this->params->date) && !empty($this->params->date['from']) && !empty($this->params->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime($this->params->date['from']));
                $date['to'] = date('Y-m-d', strtotime($this->params->date['to']));
                $this->params->date = $date;
            } elseif (!empty($daterange = H::getReportFiltersFieldVal('daterange', 'daterange'))) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime($daterange['from']));
                $date['to'] = date('Y-m-d', strtotime($daterange['to']));
                $this->params->date = $date;
            }
        } else {
            if (isset($this->params->date) && !empty($this->params->date['from']) && !empty($this->params->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime($this->params->date['from']));
                $date['to'] = date('Y-m-d', strtotime($this->params->date['to']));
                $this->params->date = $date;
            }
        }
        //columns defines the fieldname, heading, optional format, width and style for each column
        ///todo move it to config file
        if ($source == 'recruiter') {
            $report['columns'] = [
                [
                    'field' => 'state',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'whiteboard_row',
                    'heading' => ['title' => 'Service', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'region',
                    'heading' => ['title' => 'Region', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Recruiter',
                    'heading' => ['title' => 'Recruiter', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'territory',
                    'heading' => ['title' => 'Territory', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'hire_date',
                    'heading' => ['title' => 'Hire Date', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'date', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => $report['goalField'],
                    'heading' => ['title' => 'Goal', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => (LD::checkFeatureFlag('nrr-report-changes') ? 'sum' : ''), 'attributes' => '', 'style' => ''],
                ],
            ];

            if (
                in_array($type, NRRTypes::getIPWTypes())
                && ($this->params->view === NRRTypes::VIEW_HOURS->value || empty($this->params->view))
                && LD::checkFeatureFlag('distinguish-views-per-tab-nrr')
            ) {
                array_pop($report['columns']);
            }
        } else {
            $report['columns'] = [
                [
                    'field' => 'state',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'whiteboard_row',
                    'heading' => ['title' => 'Service', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'region',
                    'heading' => ['title' => 'Region', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Branch_Location',
                    'heading' => ['title' => 'Branch', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'Recruiter',
                    'heading' => ['title' => 'Recruiter', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ]
            ];

            if (LD::checkFeatureFlag('fixed-nrr-cm-branch-view')) {
                array_pop($report['columns']);
            }
        }

        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset($this->params->date) && !empty($this->params->date['from']) && !empty($this->params->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, $this->params->date['from']);
            $tdate = H::getReportEndingDate($report['period'], $this->params->date['to']);
            $tdate = $this->params->date['to'];
            $diffInWeeks = H::datediffInWeeks($sdate, $tdate);

            if ($tdate == Carbon::now()->format('Y-m-d')) {
                $diffInWeeks++;
            }

            $periods = H::getReportPeriodColumns($report['period'], ($report['period'] == 'week') ? $diffInWeeks : H::monthDiff($sdate, $tdate), $this->params->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;
        if (LD::checkFeatureFlag('nrr-report-changes')) {
            $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
            if ($selectedDatesDiffInWeek >= 13) {
                $showAvgColumn = 1;
            }
        }

        foreach ($periods as $period) {
            $clickedFilters = [];
            if ($source == 'recruiter') {
                $clickedFilters = [
                    'whiteboard_row',
                    'Recruiter',
                    'territory',
                    'hire_date',
                    //$report['goalField'],
                    'state',
                    $source,
                    'region_name'
                ];
            } else {
                if (LD::checkFeatureFlag('fixed-nrr-cm-branch-view')) {
                    $clickedFilters = [
                        'whiteboard_row',
                        'Branch_Location',
                        'state',
                        $source,
                        'region_name'
                    ];
                } else {
                    $clickedFilters = [
                        'whiteboard_row',
                        'Branch_Location',
                        'state',
                        'Recruiter',
                        $source,
                        'region_name'
                    ];
                }
            }

            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => [
                    'format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' : ''),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => [
                    'jsfunction' => 'openNationalRecruitingReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name'],
                        'type_id' => $schedTypeId
                    ]
                ],
            ];
        }

        if (LD::checkFeatureFlag('nrr-report-changes')) {
            if($report['period'] == 'week' && $showAvgColumn == 1 && (!isset($this->params->view) || (isset($this->params->view) && $this->params->view == 'count'))) {
                $report['columns'][] = [
                    'field' => 'SixWeekAvg',
                    'heading' => ['title' => 'Six Week Average', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '6', 'style' => ''],
                ];
                $report['columns'][] = [
                    'field' => 'ThirteenWeekAvg',
                    'heading' => ['title' => 'Thirteen Week Average', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '13', 'style' => ''],
                ];
                $report['columns'][] = [
                    'field' => 'AvgTrend',
                    'heading' => ['title' => '', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '', 'style' => ''],
                ];
            }
        }
        $report['columns'][] = [
            'field' => 'Total',
            'heading' => ['title' => 'Total', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => (($this->params->view && $this->params->view == 'percentage') ? 'avg' : 'sum'), 'attributes' => '', 'style' => ''],
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
            ['id' => 1, 'label' => '', 'field' => 'region', 'showCols' => 'region', 'style' => 'background-color: #D9D9D9;color:#444444;', 'level' => 1],
            ['id' => 2, 'label' => '', 'field' => 'whiteboard_row', 'showCols' => 'whiteboard_row', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 2],
            ['id' => 3, 'label' => '@group3', 'field' => 'state', 'showCols' => 'state', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 3],
            ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
            ['id' => 9, 'label' => 'National Totals', 'field' => '', 'showCols' => '', 'style' => 'background-color: #D5D5D5;color:#000', 'level' => 5],
        ];

        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset($this->params->date) && !empty($this->params->date['from']) && !empty($this->params->date['to'])) {
            $startDate = H::getReportStartingDate($report['period'], 0, $this->params->date['from']);
            $endingDate = $this->params->date['to'];
        } else {
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branches = [];
        $regions = H::getHelper("ConstantHelper")->getRegion();
        $recruiters = H::getHelper("ConstantHelper")->getRecruitersList(false);

        $branches = [];

        $lobs = $this->lobFilterService->getLobOptions();

        if (LD::checkFeatureFlag('save-report-filters-to-user-table')) {
            $filters = [
                [
                    'label' => 'LOB',
                    'name' => 'lob',
                    'type' => 'multiselect',
                    'source' => $lobs,
                    'defaultValue' => H::getReportFiltersFieldVal('lob', 'multiselect')
                ],
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state,
                    'defaultValue' => H::getReportFiltersFieldVal('state', 'multiselect')
                ],
                [
                    'label' => 'Region',
                    'name' => 'region',
                    'type' => 'select',
                    'source' => $regions,
                    'defaultValue' => H::getReportFiltersFieldVal('region', 'select')
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches,
                    'defaultValue' => H::getReportFiltersFieldVal('aoc_facility_id', 'multiselect')
                ],
                [
                    'label' => 'Recruiter',
                    'name' => 'Recruiter',
                    'type' => 'multiselect',
                    'source' => $recruiters,
                    'defaultValue' => H::getReportFiltersFieldVal('Recruiter', 'multiselect')
                ],
                [
                    'label' => 'Date',
                    'name' => 'daterange',
                    'type' => 'daterange',
                    'fromdate' => H::getFormatedDate($startDate),
                    'todate' => H::getFormatedDate($endingDate),
                    'defaultValue' => H::getReportFiltersFieldVal('daterange', 'daterange')
                ]
            ];

            $filters[] = [
                'label' => 'Exclude PRN',
                'name' => 'excludeprn',
                'type' => 'checkbox',
                'value' => false,
                'defaultValue' => H::getReportFiltersFieldVal('excludeprn', 'checkbox')
            ];

            if (LD::checkFeatureFlag('add-payer-filter-to-nrr')) {
                $filters[] = [
                    'label' => 'Payer',
                    'name' => 'Payer',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getPayers(),
                    'defaultValue' => H::getReportFiltersFieldVal('Payer', 'multiselect')
                ];
                $filters[] = [
                    'label' => 'Special Payer Group',
                    'name' => 'payer_group',
                    'type' => 'multiselect',
                    'source' => (LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                        H::getHelper('ConstantHelper')->getPayerGroupForNRR() :
                        H::getHelper('ConstantHelper')->getPayerGroup()),
                    'defaultValue' => H::getReportFiltersFieldVal('payer_group', 'multiselect')
                ];
            }
        } else {
            $filters = [
                [
                    'label' => 'LOB',
                    'name' => 'lob',
                    'type' => 'multiselect',
                    'source' => $lobs,
                    'defaultValue' => H::getReportFiltersFieldVal('lob', 'multiselect')
                ],
                [
                    'label' => 'State',
                    'name' => 'state',
                    'type' => 'multiselect',
                    'source' => $state
                ],
                [
                    'label' => 'Region',
                    'name' => 'region',
                    'type' => 'select',
                    'source' => $regions
                ],
                [
                    'label' => 'Branch',
                    'name' => 'aoc_facility_id',
                    'type' => 'multiselect',
                    'source' => $branches
                ],
                [
                    'label' => 'Recruiter',
                    'name' => 'Recruiter',
                    'type' => 'multiselect',
                    'source' => $recruiters
                ],
                [
                    'label' => 'Date',
                    'name' => 'daterange',
                    'type' => 'daterange',
                    'fromdate' => H::getFormatedDate($startDate),
                    'todate' => H::getFormatedDate($endingDate),
                ]
            ];

            $filters[] = [
                'label' => 'Exclude PRN',
                'name' => 'excludeprn',
                'type' => 'checkbox',
                'value' => false
            ];

            if (LD::checkFeatureFlag('add-payer-filter-to-nrr')) {
                $filters[] = [
                    'label' => 'Payer',
                    'name' => 'Payer',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getPayers()
                ];
                $filters[] = [
                    'label' => 'Special Payer Group',
                    'name' => 'payer_group',
                    'type' => 'multiselect',
                    'source' => (LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                        H::getHelper('ConstantHelper')->getPayerGroupForNRR() :
                        H::getHelper('ConstantHelper')->getPayerGroup())
                ];
            }
        }

        $sourceFilters = [
            [
                'label' => 'By Recruiter',
                'value' => 'recruiter',
            ],
            [
                'label' => 'By Branch',
                'value' => 'branch',
            ]
        ];

        $where = [];
        $paramsArray = [];
        if ($this->params->isajax) {
            $search = $this->params->search;
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'rt.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "left(rt.whiteboard_row,2)";
                        } elseif ($field == 'Recruiter') {
                            $tmp = 'rt.Recruiter';
                        } elseif ($field == 'Payer') {
                            $tmp = (
                            LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                'rt.Payor_Source' : 'mp.Payer'
                            );
                        } elseif ($field == 'payer_group') {
                            $tmp = (
                            LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "(select payergroup_name from payment_source where name =
                                    rt.Payor_Source order by id DESC limit 1)" : 'mp.payer_group'
                            );
                        } elseif ($field == 'region') {
                            $tmp = 'reg.region_name';
                        }
                        elseif ($field == 'lob') {
                            $tmp = "SUBSTRING_INDEX(rt.whiteboard_row, ' - ', -1)";
                        }

                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        if ($value != '') {
                            if ($field == 'aoc_facility_id') {
                                $where[] = 'rt.aoc_facility_id = ?';
                                array_push($paramsArray, $value);
                            } elseif ($field == 'state') {
                                $where[] = "left(rt.whiteboard_row,2) = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'region') {
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'excludeprn') {
                                $where[] = "(mp.isPRN = '0' OR mp.isPRN is null)";
                            } else {
                                $where[] = $field . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'rs.scheduled_date');
        $params = [
            'schedTypeId' => $schedTypeId,
            'report' => $report,
            'periodQuery' => $periodQuery,
            'where' => $where,
            'startDate' => $startDate,
            'endDate' => $endingDate,
            'filterParams' => array_merge([$startDate, $endingDate], $paramsArray)
        ];

        //now get report data
        if ($source === NRRTypes::RECRUITER->value) {
            $recruiterReport = $this->getNRRReport($params, false);
        } else {
            //todo need to move this to the another method. Related to the branch filter
            if (LD::checkFeatureFlag('fixed-nrr-cm-branch-view')) {
                $qry = "SELECT left(rt.whiteboard_row,2) as state, rt.whiteboard_row, mf.FacilityName as Branch_Location,
                 reg.region_name as region,
                " . $periodQuery . " as Monday,
                count(rs.id) as myCount,
                SUM(rt.Total_Hours) AS totalHours

                FROM `recruiting_schedule` rs
                left join mssql_recruitertracker rt on tracker_id = rt.id
                left join mssql_patients mp on rs.aoc_patient_id = mp.id
                left join users u on rt.user_id = u.id
                left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
                left join mssql_facilities mf on mf.id = mp.aoc_facility_id and left(mf.FacilityName,2) = left(rt.whiteboard_row,2)
                left join regions reg on mf.region = reg.id
                where rs.type_id = " . $schedTypeId . " and rs.scheduled_date >= ? and rs.scheduled_date <= ?  ".
                    (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). ' AND rs.is_deleted <> 1 ' . "
                group by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id," . $periodQuery . "
                order by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id," . $periodQuery;
            } else {
                $qry = "SELECT left(rt.whiteboard_row,2) as state, rt.whiteboard_row, mf.FacilityName as Branch_Location,
                case when isnull(w.territory) = true then " . '"' . "Term'd" . '"' .  " else rt.Recruiter end as Recruiter, reg.region_name,CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
                " . $periodQuery . " as Monday,
                count(rs.id) as myCount,
                SUM(rt.Total_Hours) AS totalHours

                FROM `recruiting_schedule` rs
                left join mssql_recruitertracker rt on tracker_id = rt.id
                left join mssql_patients mp on rs.aoc_patient_id = mp.id
                left join users u on rt.user_id = u.id
                left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
                left join mssql_facilities mf on mf.id = mp.aoc_facility_id and left(mf.FacilityName,2) = left(rt.whiteboard_row,2)
                left join regions reg on mf.region = reg.id
                where rs.type_id = " . $schedTypeId . " and rs.scheduled_date >= ? and rs.scheduled_date <= ?  ".
                    (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). ' AND rs.is_deleted <> 1 ' . "
                group by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id, Recruiter," . $periodQuery . "
                order by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id, Recruiter," . $periodQuery;
            }

            //todo fix me
            $recruiterReport = DB::stream($qry, array_merge([
                $startDate,
                $endingDate
            ], $paramsArray));
        }

        if (in_array($type, ['ipea']) && $source === NRRTypes::RECRUITER->value) {
            $intakeReport = $this->getNRRReport($params, true);
        }

        $data = $this->groupReportData($recruiterReport, $source, $report, $periods, false, $this->params);

        if ($this->params->view && $this->params->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach ($test as $key => $value) {
                if ($value['Count'] != 0) {
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'], 0);
                } else {
                    $data[$key]['Total'] = 0;
                }
            }
        }

        if (in_array($type, ['ipea']) && $source == NRRTypes::RECRUITER->value) {
            $intakeData = $this->groupReportData(
                $intakeReport,
                $source,
                $report,
                $periods,
                true,
                $this->params
            );

            $data = array_merge($data, $intakeData);
        }

        $data = $this->moveKeyToEnd($data, 'grand');

        return [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => $source,
        ];
    }

    /**
     * @param array $params
     * @param bool $isIntake
     * @return Generator
     */
    public function getNRRReport(array $params, bool $isIntake): Generator
    {
        $params['type'] = $isIntake ? NRRTypes::INTAKE_COORDINATOR->value : NRRTypes::RECRUITER->value;

        return $this->recruitingScheduleRepository->getReport($params);
    }

    /**
     * @param Generator $records
     * @param string $source
     * @param array $report
     * @param array $periods
     * @param bool $isIntake
     * @param TableParams $requestParams
     * @return array
     * @throws \Exception
     */
    public function groupReportData(
        Generator $records,
        string $source,
        array $report,
        array $periods,
        bool $isIntake,
        TableParams $requestParams
    ): array {
        $data = [];

        foreach ($records as $record) {
            if ($source == NRRTypes::RECRUITER->value) {
                $id = $this->buildId($record, $report, $isIntake);

                if (is_null($id)) {
                    continue;
                }

                if (!isset($data[$id])) {
                    $data[$id] = [
                        'state' => $isIntake ? self::INTAKE_ROW_NAME : $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'Recruiter' => $isIntake ? $record['intakeCoordinator'] : $record['Recruiter'],
                        'region' => $record['region'],
                        'region_name' => $record['region_name'],
                        'territory' => $record['territory'],
                        'hire_date' => $record['hire_date'],
                        $report['goalField'] => ($report['period'] == 'week') ?
                            $record[$report['goalField']] :
                            (
                                ($record[$report['goalField']] / self::SEVEN_DAYS) *
                                $this->getDaysInMonth(date('m'), date('Y'))
                            ),
                    ];

                    if ($isIntake) {
                        $data[$id]['state4intake'] = $record['state'];
                    }

                    //dynamically add fields for each period
                    foreach ($periods as $period) {
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                    $data[$id]['Count'] = 0;
                }
            } else {
                $branchLocation = isset($record['Branch_Location']) ? $record['Branch_Location'] : '-';
                $id = $this->buildByBranchId($record, $branchLocation);

                if (!isset($data[$id])) {
                    if (LD::checkFeatureFlag('fixed-nrr-cm-branch-view')) {
                        $data[$id] = [
                            'state' => $record['state'],
                            'whiteboard_row' => $record['whiteboard_row'],
                            'region' => $record['region'],
                            'region_name' => $record['region'],
                            'Branch_Location' => $branchLocation,
                        ];
                    } else {
                        $data[$id] = [
                            'state' => $record['state'],
                            'whiteboard_row' => $record['whiteboard_row'],
                            'region' => $record['region'],
                            'region_name' => $record['region_name'],
                            'Branch_Location' => $branchLocation,
                            'Recruiter' => $record['Recruiter'],
                        ];
                    }

                    //dynamically add fields for each period
                    foreach ($periods as $period) {
                        $data[$id][$period['name']] = 0;
                    }

                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                }
            }

            if ($requestParams->view && $requestParams->view === 'percentage') {
                $this->calculatePrecentageTotal($data, $record, $id, $report);
            } else {
                $this->calculateNoPrecentageTotal($data, $record, $id, $report);
            }
        }

        if ($requestParams->view && $requestParams->view == 'percentage') {
            //average the Total columns
            $this->calculateTotal($data);
        }

        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing
         * we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably
         * better to put in a function. but this works so????
         */
        if (isset($id)) { ///todo fix me
            $data['grand'] = $data[$id];
        }

        return $data;
    }

    /**
     * @param $record
     * @param $report
     * @param bool $isIntake
     * @return string|null
     */
    private function buildId($record, $report, bool $isIntake): ?string
    {
        $prefix = $isIntake ? self::INTAKE_ROW_NAME : $record['state'];
        $param = $isIntake ? $record['intakeCoordinator'] : $record['Recruiter'];

        $requiredFields = [
            $record['whiteboard_row'] ?? '',
            $record['region'] ?? '',
            $record['territory'] ?? '',
            $record['hire_date'] ?? '',
            $record[$report['goalField']] ?? '',
        ];

        $filledFields = array_filter($requiredFields, fn($value) => trim($value) !== '');

        if (empty($filledFields)) {
            return null;
        }

        $result = sprintf(
            '|%s|%s|%s|%s|%s|%s',
            $record['whiteboard_row'],
            $record['region'],
            $param,
            $record['territory'],
            $record['hire_date'],
            $record[$report['goalField']]
        );

        return $prefix . $result;
    }

    /**
     * @param array $record
     * @param string $branchLocation
     * @return string
     * @throws \Exception
     */
    private function buildByBranchId(array $record, ?string $branchLocation): string
    {
        if (LD::checkFeatureFlag('fixed-nrr-cm-branch-view')) {
            $id = sprintf('%s|%s|%s', $record['state'], $record['whiteboard_row'], $branchLocation);
        } else {
            $id = sprintf('%s|%s|%s', $record['state'], $record['whiteboard_row'], $record['Recruiter']);
        }

        return $id;
    }

    /**
     * @param int $month
     * @param int $year
     * @return int
     */
    private function getDaysInMonth(int $month, int $year): int
    {
        return cal_days_in_month(CAL_GREGORIAN, $month, $year);
    }

    /**
     * @param array $data
     * @return void
     */
    protected function calculateTotal(array &$data): void ///todo need to refactor this
    {
        foreach ($data as $key => $value) {
            if ($value['Count'] != 0) {
                $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'], 0);
            } else {
                $data[$key]['Total'] = 0;
            }
        }
    }

    /**
     * @param array $data
     * @param $record
     * @param $id
     * @param $report
     * @return void
     */
    protected function calculatePrecentageTotal(array &$data, $record, $id, $report): void ///todo need to refactor this
    {
        $total = (($record[$report['goalField']] > 0) ?
            (($record['myCount'] / $record[$report['goalField']]) * 100) : 0);

        $total = is_int($total) ? $total : round($total, 2);
        $data[$id][$record['Monday']] = $total;
        $data[$id]['Total'] = $data[$id]['Total'] + $total;

        if (floatval($data[$id]['Total']) != 0) {
            $data[$id]['Count']++;
        }
    }

    /**
     * @param array $data
     * @param $record
     * @param $id
     * @return void
     * @throws \Exception
     */
    protected function calculateNoPrecentageTotal(array &$data, $record, $id) ///todo need to refactor this
    {
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), self::ZERO_WEEKS);
        $currentSixMonday = H::getMondayFromDate($currentMonday, self::SIX_WEEKS);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, self::THIRTEEN_WEEKS);

        if ($this->params->view === NRRTypes::VIEW_COUNT->value) {
            if (LD::checkFeatureFlag('nrr-report-changes')) {
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['myCount'];
                }

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $record['myCount'];
                }
            }

            $data[$id][$record['Monday']] = $record['myCount'];
            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        } else {
            if (LD::checkFeatureFlag('distinguish-views-per-tab-nrr')) {
                $data[$id][$record['Monday']] = $record['totalHours'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['totalHours'];
            }
        }
    }
}
