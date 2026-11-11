<?php

namespace App\Services\NationalRecruitingReport\Tabs;

use App\Contracts\NationalRecruitingReport\ReportTabInterface;
use App\DTO\NationalRecruitingReport\TableParams;
use App\Enums\NRR\NRRTypes;
use App\Enums\ScheduleTypes;
use App\Helpers\H;
use App\Helpers\LD;
use App\Repositories\NationalRecruitingReport\Tabs\CandidateStartsRepository;
use App\Services\Reports\ReportLobFilterService;
use App\Traits\NationalRecruitingReport\NRRTrait;
use App\User;
use Generator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class CandidateStartsService implements ReportTabInterface
{
    use NRRTrait;

    /**
     * @param TableParams $params
     * @param CandidateStartsRepository $CSRepository
     */
    public function __construct(
        private TableParams $params,
        private CandidateStartsRepository $CSRepository,
        private ReportLobFilterService $lobFilterService
    ) {
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTabData(): array
    {
        $period = 'week';
        if (isset($this->params->period)) {
            $period = $this->params->period;
        }

        $source = NRRTypes::RECRUITER->value;
        if (isset($this->params->source)) {
            $source = $this->params->source;
        }
        ///todo need to refactor this
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
                        'daterange',
                    ];
                    $savedFilterData = array_intersect_key($savedFilterData, array_flip($fields));
                    if (isset($savedFilterData['state']) && strlen($savedFilterData['state'][0]) > 2) {
                        unset($savedFilterData['state']);
                    }
                } else {
                    $fields = ['lob', 'state', 'branch', 'sourceType', 'appSource'];
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

            if (
                isset($this->params->date) &&
                !empty($this->params->date['from']) &&
                !empty($this->params->date['to'])
            ) {
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
            if (
                isset($this->params->date) &&
                !empty($this->params->date['from']) &&
                !empty($this->params->date['to'])
            ) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime($this->params->date['from']));
                $date['to'] = date('Y-m-d', strtotime($this->params->date['to']));
                $this->params->date = $date;
            }
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = config('NRR.candidateStarts.title');
        $report['id'] = config('NRR.candidateStarts.reportId');
        $report['period'] = $period;
        $report['goalField'] = config('NRR.candidateStarts.goalField');

        $where = [];
        $paramsArray = [];

        //columns defines the fieldname, heading, optional format, width and style for each column
        $this->defineDefaultColumns($source, $report);
        ///todo need to refactor this
        //dynamically add columns for the time periods
        //first get the column names based on then set the column definition for each
        if (isset($this->params->date) && !empty($this->params->date['from']) && !empty($this->params->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, $this->params->date['from']);
            $tdate = H::getReportEndingDate($report['period'], $this->params->date['to']);

            if (LD::checkFeatureFlag('nrr-report-changes')) {
                $tdate = $this->params->date['to'];
            }

            $periods = H::getReportPeriodColumns(
                $report['period'],
                ($report['period'] == 'week') ?
                    H::datediffInWeeks($sdate, $tdate) :
                    H::monthDiff($sdate, $tdate),
                $this->params->date['to']
            );
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        ///todo need to refactor this
        $showAvgColumn = 0;
        if (LD::checkFeatureFlag('nrr-report-changes')) {
            $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
            if ($selectedDatesDiffInWeek >= 13) {
                $showAvgColumn = 1;
            }
        }
        ///todo need to refactor this
        foreach ($periods as $period) {
            $clickedFilters = [];
            if ($source == 'recruiter') {
                $clickedFilters = [
                    'state',
                    'whiteboard_row',
                    'Recruiter',
                    'territory',
                    'hire_date',
                    //$report['goalField'],
                    $source,
                    'region_name'
                ];
            } else {
                $clickedFilters = [
                    'whiteboard_row',
                    'Branch_Location',
                    'state',
                    $source,
                    'region_name'
                ];
            }
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => [
                    'format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' : ''),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' : 'sum'),
                    'attributes' => '',
                    'style' => ''
                ],
                'clickable' => [
                    'jsfunction' => 'openNationalRecruitingReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name'],
                        'type_id' => ScheduleTypes::CANDIDATE_START->value
                    ]
                ],
            ];
        }
        ///todo will be refactored in the next iteration
        if (LD::checkFeatureFlag('nrr-report-changes')) {
            if (
                $report['period'] == 'week' &&
                $showAvgColumn == 1 &&
                (!isset($this->params->view) || (isset($this->params->view) && $this->params->view == 'count'))
            ) {
                $report['columns'][] = [
                    'field' => 'SixWeekAvg',
                    'heading' => ['title' => 'Six Week Average', 'attributes' => '', 'style' => ''],
                    'cell' => [
                        'format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' : ''),
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '6', 'style' => ''],
                ];
                $report['columns'][] = [
                    'field' => 'ThirteenWeekAvg',
                    'heading' => ['title' => 'Thirteen Week Average', 'attributes' => '', 'style' => ''],
                    'cell' => [
                        'format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' : ''),
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '13', 'style' => ''],
                ];
                $report['columns'][] = [
                    'field' => 'AvgTrend',
                    'heading' => ['title' => '', 'attributes' => '', 'style' => ''],
                    'cell' => [
                        'format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' : ''),
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '', 'style' => ''],
                ];
            }
        }

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => ['title' => 'Total', 'attributes' => '', 'style' => ''],
            'cell' => [
                'format' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' : ''),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => (($this->params->view && $this->params->view == 'percentage') ? 'pct' : 'sum'),
                'attributes' => '',
                'style' => ''
            ],
        ];

        //rowStyle is any special style to apply to the <tr> tags for the data rows
        $report['rowStyle'] = '';

        $report['groupby'] = config('NRR.candidateStarts.groupBy');

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();

        $branches = [];
        foreach ($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset($this->params->date) && !empty($this->params->date['from']) && !empty($this->params->date['to'])) {
            $startDate = H::getReportStartingDate($report['period'], 0, $this->params->date['from']);
            $endingDate = $this->params->date['to'];
        } else {
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }

        $regions = H::getHelper("ConstantHelper")->getRegion();
        $recruiters = H::getHelper("ConstantHelper")->getRecruitersList(false);

        $branches = [];
        //todo need to refactor this

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

        $sourceFilters = config('NRR.candidateStarts.sourceFilters');

        if ($this->params->isajax) {
            $search = $this->params->search;
            $this->buildSearchRequest($search, $paramsArray, $where);
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'rs.appointment_date');
        //now get report data

        $params = [
            'schedTypeId' => ScheduleTypes::CANDIDATE_START->value,
            'report' => $report,
            'periodQuery' => $periodQuery,
            'where' => $where,
            'startDate' => $startDate,
            'endDate' => $endingDate,
            'filterParams' => array_merge([$startDate, $endingDate], $paramsArray)
        ];

        if ($source == NRRTypes::RECRUITER->value) {
            $reportData = $this->CSRepository->getReportByRecruiter($params);
        } else {
            $reportData = $this->CSRepository->getReportByBranch($params);
        }

        //process each record in case we need to alter data programmatically
        $data = $this->groupReportData($reportData, $source, $report, $periods);

        if (LD::checkFeatureFlag('nrr-report-get-goals-for-all-whiteboard-rows')) {
            $whiteBoardData = $this->getWhiteBoardGoals([
                'filterParams' => $paramsArray,
                'report' => $report,
                'where' => $where,
                'type' => $source,
            ]);

            if ($whiteBoardData) {
                $this->groupWhiteBoardData($whiteBoardData, $report, $periods, $data, $source);
            }
        }

        ////get intake data for additional row
        if ($source === NRRTypes::RECRUITER->value) {
            $intakeReportData = $this->CSRepository->getReportByRecruiter($params, NRRTypes::INTAKE_COORDINATOR->value);
            $groupedIntakeData = $this->groupReportData($intakeReportData, $source, $report, $periods, true);

            $data = array_merge($data, $groupedIntakeData);

            if (
                ($this->params->view === NRRTypes::VIEW_COUNT->value)
                && LD::checkFeatureFlag('distinguish-views-per-tab-nrr')
            ) {
                $this->removeGoal($data, $report);
            }
        }

        $data = $this->moveKeyToEnd($data, 'grand');

        return [
            'data' => $data,
            'filters' => $filters,
            'report' => $report,
            'sourceFilters' => $sourceFilters,
            'source' => $source
        ];
    }

    /**
     * @param array $data
     * @param array $report
     * @return void
     */
    private function removeGoal(array &$data, array &$report): void
    {
        unset($report['goalField']);
        foreach ($data as $k => $v) {
            foreach ($v as $kk => $vv) {
                if (str_contains($kk, '_goal')) {
                    unset($data[$k][$kk]);
                }
            }
        }

        foreach ($report['columns'] as $columnKey => $column) {
            if (str_contains($column['field'], '_goal')) {
                unset($report['columns'][$columnKey]);
            }

        }
    }

    /**
     * @param Generator $records
     * @param string $source
     * @param array $report
     * @param array $periods
     * @param bool $isIntake
     * @return array
     * @throws \Exception
     */
    private function groupReportData(
        Generator $records,
        string $source,
        array $report,
        array $periods,
        bool $isIntake = false
    ) {
        $data = [];
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);

        foreach ($records as $record) {
            if ($source === NRRTypes::RECRUITER->value) {
                $id = $this->buildId($record, $report, $isIntake);

                if (!isset($data[$id])) {
                    $data[$id] = [
                        'state' => $isIntake ? 'Intake' : $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'region' => $record['region'],
                        'Recruiter' => $isIntake ? $record['intakeCoordinator'] : $record['Recruiter'],
                        'territory' => $record['territory'],
                        'hire_date' => $record['hire_date'],
                        $report['goalField'] => ($report['period'] == 'week') ?
                            $record[$report['goalField']] :
                            (($record[$report['goalField']] / 7) * $this->get_days_in_month(date('m'), date('Y'))),
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
                $branchLocation = $record['Branch_Location'] ?? '-';
                $id = sprintf('%s|%s|%s', $record['whiteboard_row'], $branchLocation, $record['region']);

                if (!isset($data[$id])) {
                    $data[$id] = [
                        'state' => $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'Branch_Location' => $branchLocation,
                        'region' => $record['region']
                    ];
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

            $count = $record['totalHours'];

            if (($source == 'recruiter' && request()->get('view') === 'count') && LD::checkFeatureFlag(
                    'distinguish-views-per-tab-nrr'
                )) {
                $count = $record['schedulesCount'];
            }

            if (LD::checkFeatureFlag('nrr-report-changes')) {
                if (strtotime($currentMonday) > strtotime($record['Monday']) && strtotime(
                        $record['Monday']
                    ) >= strtotime($currentSixMonday)) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $count;
                }
                if (strtotime($currentMonday) > strtotime($record['Monday']) && strtotime(
                        $record['Monday']
                    ) >= strtotime($currentThirteenMonday)) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $count;
                }
            }


            $data[$id][$record['Monday']] = $count;
            $data[$id]['Total'] = $data[$id]['Total'] + $count;
        }

        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return $data;
    }

    /**
     * @param $search
     * @param $paramsArray
     * @param $where
     * @return void
     * @throws \Exception
     */
    private function buildSearchRequest($search, &$paramsArray, &$where)
    {
        if ($search) {
            foreach ($search as $field => $value) {
                if (is_array($value)) {
                    $tmp = $field;
                    if ($field == 'aoc_facility_id') {
                        $tmp = 'rt.aoc_facility_id';
                    } elseif ($field == 'state') {
                        $tmp = "left(rt.whiteboard_row,2)";
                    } elseif ($field == 'Payer') {
                        $tmp = (
                        LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                            "rt.Payor_Source" : "mp.Payer"
                        );
                    } elseif ($field == 'payer_group') {
                        $tmp = (
                        LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                            "(select payergroup_name from payment_source where name =
                                    rt.Payor_Source order by id DESC limit 1)" : 'p1.payer_group'
                        );
                    } elseif ($field == 'Recruiter') {
                        $tmp = 'rt.Recruiter';
                    }

                    elseif ($field == 'lob') {
                        $tmp = "TRIM(SUBSTRING_INDEX(rt.whiteboard_row, ' - ', -1))";
                    }

                    $fieldStr = '';
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
                            $where[] = "(p1.isPRN = '0' OR p1.isPRN is null)";
                        } else {
                            $where[] = $field . '= ?';
                            array_push($paramsArray, $value);
                        }
                    }
                }
            }
        }
    }

    /**
     * @param $record
     * @param $report
     * @param bool $isIntake
     * @return string|null
     */
    private function buildId($record, $report, bool $isIntake): ?string
    {
        $prefix = $isIntake ? 'Intake - intake' : $record['whiteboard_row'];
        $param = $isIntake ? $record['intakeCoordinator'] : $record['Recruiter'];

        $requiredFields = [
            $record['whiteboard_row'] ?? '',
            $record['region'] ?? '',
            $record['user_id'] ?? '',
            $record['territory'] ?? '',
            $record['hire_date'] ?? '',
            $record[$report['goalField']] ?? '',
        ];

        $filledFields = array_filter($requiredFields, fn($value) => trim($value) !== '');

        if (empty($filledFields)) {
            return null;
        }

        return sprintf(
            '%s|%s|%s|%s|%s|%s|%s',
            $prefix,
            $record['region'],
            $record['user_id'],
            $param,
            $record['territory'],
            $record['hire_date'],
            $record[$report['goalField']]
        );
    }

    /**
     * @param string $source
     * @param array $report
     * @return void
     * @throws \Exception
     */
    private function defineDefaultColumns(string $source, array &$report): void
    {
        if ($source == NRRTypes::RECRUITER->value) {
            $defaultColumns = config('NRR.candidateStarts.recruiterColumns');
            $defaultColumns[] = [
                'field' => $report['goalField'],
                'heading' => ['title' => 'Goal', 'attributes' => '', 'style' => ''],
                'cell' => [
                    'format' => '',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => (LD::checkFeatureFlag('nrr-report-changes') ? 'sum' : ''),
                    'attributes' => '',
                    'style' => ''
                ],
            ];

            $report['columns'] = $defaultColumns;
        } else {
            $report['columns'] = config('NRR.candidateStarts.branchColumns');
        }
    }

    /**
     * @param array $params
     * @return Generator|null
     */
    protected function getWhiteBoardGoals(array $params): ?Generator
    {
        /*for Recruiter report - look for all whiteboard rows to
            make sure we have the recruiter's goal included.*/
        if ($params['type'] === NRRTypes::RECRUITER->value) {
            return $this->CSRepository->getWhiteBoardData($params);
        }

        return null;
    }

    /**
     * @param Generator $records
     * @param array $report
     * @param array $periods
     * @param array $data
     * @param string $type
     * @return void
     */
    protected function groupWhiteBoardData(
        Generator $records,
        array $report,
        array $periods,
        array &$data,
        string $type
    ) {
        foreach ($records as $record) {
            $id = sprintf(
                '%s|%s|%s|%s|%s|%s|%s',
                $record['row_id'],
                $record['region'],
                $record['user_id'],
                $record['user_name'],
                $record['territory'],
                $record['hire_date'],
                $record[$report['goalField']]
            );

            if (!isset($data[$id])) {
                //adding the row
                $data[$id] = [
                    'state' => $record['state'],
                    'whiteboard_row' => $record['row_id'],
                    'region' => $record['region'],
                    'Recruiter' => $record['user_name'],
                    'territory' => $record['territory'],
                    'hire_date' => $record['hire_date'],
                    $report['goalField'] => ($report['period'] == 'week') ? $record[$report['goalField']] :
                        (($record[$report['goalField']] / 7) * $this->get_days_in_month(date('m'), date('Y'))),
                ];

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
        }

        ksort($data);

        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource
         * before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably
         * better to put in a function. but this works so????
         */
        if (isset($id) && $type === NRRTypes::RECRUITER->value) { ///todo fix me
            $data['grand'] = $data[$id];
        }
    }
}
