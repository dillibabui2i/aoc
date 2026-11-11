<?php

namespace App\Services\NationalRecruitingReport\Tabs;

use App\Contracts\NationalRecruitingReport\ReportTabInterface;
use App\DTO\NationalRecruitingReport\TableParams;
use App\Enums\NRR\NRRTypes;
use App\Enums\ScheduleTypes;
use App\Helpers\H;
use App\Helpers\LD;
use App\Repositories\NationalRecruitingReport\Tabs\GrossMarginRepository;
use App\Services\NationalRecruitingReport\NRRTableService;
use App\Services\Reports\ReportLobFilterService;
use App\Traits\NationalRecruitingReport\NRRTrait;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class GrossMarginService implements ReportTabInterface
{
    const DEFAULT_ID_PATTERN = '%s|%s|%s|%s|';
    const APPROVER_ID_PATTERN = '%s|%s';

    use NRRTrait;

    /**
     * @param NRRTableService $nrrTableService
     * @param TableParams $params
     * @param GrossMarginRepository $grossMarginRepository
     */
    public function __construct(
        private NrrTableService $nrrTableService,
        protected TableParams $params,
        protected GrossMarginRepository $grossMarginRepository,
        protected ReportLobFilterService $lobFilterService,
    )
    {
    }

    /**
     * @param string $source
     * @param array $record
     * @return string
     */
    public function buildId(string $source, array $record): string
    {
        match ($source) {
            'approver' => $resultId = $this->buildByApproverId($record),
            'branch' => $resultId = $this->buildByBranchId($record),
            default => $resultId = $this->buildDefaultId($record),
        };

        return $resultId;
    }

    /**
     * @param array $record
     * @return string
     */
    private function buildDefaultId(array $record): string
    {
        return sprintf(
            self::DEFAULT_ID_PATTERN,
            $record['whiteboard_row'],
            $record['region'],
            $record['Recruiter'],
            is_null($record['territory']) ? "Term'd" : $record['territory'],
            $record['hire_date']
        );
    }

    /**
     * @param array $record
     * @return string
     */
    private function buildByBranchId(array $record): string
    {
        return $record['whiteboard_row'] . $record['region'] . $record['Branch_Location'];
    }

    /**
     * @param array $record
     * @return string
     */
    private function buildByApproverId(array $record): string
    {
        return sprintf(
            self::APPROVER_ID_PATTERN,
            $record['whiteboard_row'],
            $record['approver'],
        );
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getTabData(): array
    {
        ///todo should be refactoered in the next iterations
        $period = 'week';
        if (isset($this->params->period)) {
            $period = $this->params->period;
        }

        $source = NRRTypes::RECRUITER->value;

        if (isset($this->params->source)) {
            $source = $this->params->source;
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

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Gross Margin';
        $report['id'] = 'gross_margin';
        $report['period'] = $period;
        $schedTypeIds = [
            ScheduleTypes::IPEA->value,
            ScheduleTypes::PEPI->value,
            ScheduleTypes::CANDIDATE_START->value
        ];

        $sourceTypes = [
            NRRTypes::RECRUITER->value,
            NRRTypes::APPROVER->value,
        ];

        $showAvgColumn = 0;

        //columns defines the fieldname, heading, optional format, width and style for each column
        $report['columns'] = $this->nrrTableService->buildTableColumns($source);

        $clickedFilters = [];
        if (in_array($source, $sourceTypes)) {
            $clickedFilters = [
                'state',
                'whiteboard_row',
                'Recruiter',
                'territory',
                'hire_date',
                'region_name'
            ];
            ///todo need to improve
            if ($source === NRRTypes::APPROVER->value) {
                array_push($clickedFilters, NRRTypes::APPROVER->value);
                unset($clickedFilters[2]);
            }

        } else {
            $clickedFilters = [
                'whiteboard_row',
                'Branch_Location',
                'state',
                'region_name'
            ];
        }

        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset($this->params->date) && !empty($this->params->date['from']) && !empty($this->params->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, $this->params->date['from']);
            $tdate = H::getReportEndingDate($report['period'], $this->params->date['to']);

            if (LD::checkFeatureFlag('nrr-report-changes')) {
                $tdate = $this->params->date['to'];
            }

            $periods = H::getReportPeriodColumns($report['period'], ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate), $this->params->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }

        if (LD::checkFeatureFlag('nrr-report-changes')) {
            $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
            if ($selectedDatesDiffInWeek >= 13) {
                $showAvgColumn = 1;
            }
        }

        foreach ($periods as $period) {
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'avg', 'attributes' => '', 'style' => ''],
                'clickable' => [
                    'jsfunction' => 'openNationalRecruitingReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' =>
                        [
                            'start_of_week' => $period['name'],
                            'type_id' => $schedTypeIds
                        ]
                ],
            ];
        }

        if (LD::checkFeatureFlag('nrr-report-changes')) {
            if($report['period'] == 'week' && $showAvgColumn == 1 && (!isset($this->params->view) || (isset($this->params->view) && $this->params->view == 'count'))) {
                $report['columns'][] = [
                    'field' => 'SixWeekAvg',
                    'heading' => ['title' => 'Six Week Average', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '6', 'style' => ''],
                ];
                $report['columns'][] = [
                    'field' => 'ThirteenWeekAvg',
                    'heading' => ['title' => 'Thirteen Week Average', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '13', 'style' => ''],
                ];
                $report['columns'][] = [
                    'field' => 'AvgTrend',
                    'heading' => ['title' => '', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '', 'style' => ''],
                ];
            }
        }

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => ['title' => 'Total', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => 'avg', 'attributes' => '', 'style' => ''],
        ];

        //rowStyle is any special style to apply to the <tr> tags for the data rows
        $report['rowStyle'] = '';

        $report['groupby'] = config('NRR.grossMargin.defaultGroupBy');

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches  = [];

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
        $branches  = [];

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

                $filters[] = [
                    'label' => 'Step/Level',
                    'name' => 'type_id',
                    'type' => 'multiselect',
                    'source' => ScheduleTypes::getListGMFilter(),
                    'class' => 'select-step-level',
                ];
            }
        }

        $sourceFilters = config('NRR.grossMargin.sourceFilters');

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
                        } elseif ($field == 'Payer') {
                            $tmp = (
                            LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "rt.Payor_Source" : "mp.Payer"
                            );
                        } elseif ($field == 'payer_group') {
                            $tmp = (
                            LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "(select payergroup_name from payment_source where name =
                                    rt.Payor_Source order by id DESC limit 1)" : 'mp.payer_group'
                            );
                        } elseif ($field == 'Recruiter') {
                            $tmp = 'rt.Recruiter';
                        }
                        elseif ($field == 'lob') {
                            $tmp = "TRIM(SUBSTRING_INDEX(rt.whiteboard_row, ' - ', -1))";
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

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'rs.appointment_date');
        //now get report data

        $params = [
            'schedTypeIds' => $schedTypeIds,
            'periodQuery' => $periodQuery,
            'where' => $where,
            'filterParams' => array_merge([$startDate, $endingDate], $paramsArray)
        ];

        match ($source) {
            NRRTypes::RECRUITER->value => $records = $this->grossMarginRepository->getReportByRecruiter($params),
            NRRTypes::APPROVER->value => $records = $this->grossMarginRepository->getReportByApprover($params),
            NRRTypes::BRANCH->value => $records = $this->grossMarginRepository->getReportByBranch($params),
            default => []
        };

        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);
        $data = [];

        //process each record in case we need to alter data programmatically
        foreach ($records as $record) {
            if (in_array($source, $sourceTypes)) {
                $id = $this->buildId($source, $record);
                if (!isset($data[$id])) {
                    $fieldMap = config('NRR.grossMargin.fields');

                    $data[$id] = [];

                    foreach ($fieldMap[$source] as $field) {
                        $data[$id][$field] =
                            $field === 'territory' && is_null($record[$field])
                                ? "Term'd"
                                : $record[$field];
                    }

                    //dynamically add fields for each period
                    foreach ($periods as $period) {
                        $data[$id][$period['name']] = 0;
                    }

                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['SixWeekCount'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekCount'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                    $data[$id]['Count'] = 0;
                }
            } else {
                $id = $this->buildId($source, $record);

                if (!isset($data[$id])) {
                    $data[$id] = [
                        'state' => $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'region' => $record['region'],
                        'Branch_Location' => $record['Branch_Location']
                    ];

                    //dynamically add fields for each period
                    foreach ($periods as $period) {
                        $data[$id][$period['name']] = 0;
                    }

                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['SixWeekCount'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekCount'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                    $data[$id]['Count'] = 0;
                }
            }

            $grossProfit = round($record['myCount'] * 100);

            if (LD::checkFeatureFlag('nrr-report-changes')) {
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $grossProfit;
                    if ($grossProfit > 0) {
                        $data[$id]['SixWeekCount'] = $data[$id]['SixWeekCount'] + 1;
                    }
                }

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $grossProfit;
                    if ($grossProfit > 0) {
                        $data[$id]['ThirteenWeekCount'] = $data[$id]['ThirteenWeekCount'] + 1;
                    }
                }
            }

            $data[$id][$record['Monday']] = $grossProfit;
            $data[$id]['Total'] = $data[$id]['Total'] + $grossProfit;

            if (floatval($data[$id]['Total']) != 0) {
                $data[$id]['Count']++;
            }
        }

        if (!LD::checkFeatureFlag('fix-gross-margin-tab-calculation-issue')) {
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

        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing
         * we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource
         * probably better to put in a function but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => $source
        ];
    }
}
