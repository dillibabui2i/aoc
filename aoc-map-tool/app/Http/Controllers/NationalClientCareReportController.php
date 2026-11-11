<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Reports;
use Carbon\Carbon;
use Illuminate\Http\Request;

class NationalClientCareReportController extends Controller
{
    const BACKGROUND1 = 'background-color: #215eac;color:#fff;';
    const BACKGROUND2 = 'background-color: #D5D5D5;color:#000';
    const BACKGROUND3 = 'background-color: #D9D9D9;color:#444;';
    const BACKGROUND4 = 'background-color: #dbf0fd;color:#444444;';
    const NATIONALTOTAL = 'National Totals';
    const ANDCONDITION = ' AND ';
    const GROUP3 = '@group3';
    const CCC = 'Client Care Coordinator';
    const SPECIALPAYER = 'mssql_patients.payer_group as Special Payer';
    const COORDINATOR = 'users.name as Coordinator';
    const BRANCH = 'mssql_facilities.FacilityName as Branch';
    public function getnccpopup(Request $request)
    {
        $req = $request->all();
        H::ExcelLog(print_r($req, true), 'dbNccPop.log');
        $rowFilter = json_decode($req['rowfilter'], true);
        $form = $req['form'];
        $view = '';
        foreach ($form as $key => $value) {
            if ($value['name'] == 'view')
                $view = $value['value'];
            if (empty($value['value']) || substr_count($value['name'], "date") || substr_count($value['name'], "excludeprn"))
                continue;
            if (substr_count($value['name'], "search") > 0) {
                if (substr_count($value['name'], "[") == 1) {
                    $key = str_replace(["]", "search["], ["", ""], $value['name']);
                    $rowFilter[$key] = $value['value'];
                } else if (substr_count($value['name'], "[") == 2) {
                    $key = str_replace(["]", "search", "["], ["", "", ""], $value['name']);
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
                if (in_array($value['name'], array('_token', 'isajax', 'period', 'source', 'lob', 'view'))) {
                    continue;
                }
                $rowFilter[$key] = $value['value'];
            }
        }
        $result = [];
        $query = null;
        if (isset($req['type'])) {
            if ($req['type'] == 'missedVisit') {
                $query = DB::table("mssql_schedulemaster")->select(
                    self::BRANCH,
                    (LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank') ?
                    DB::raw("IFNULL(users.name, 'Unassigned') as Coordinator") : self::COORDINATOR),
                    DB::raw(
                        "CONCAT(
                            mssql_patients.Patient_FirstName,
                            ' ',
                            mssql_patients.Patient_LastName
                        ) as `Client`"
                    ),
                    DB::raw("IF(
                        mssql_schedulemaster.ScheduleDate != '' AND mssql_schedulemaster.ScheduleDate IS NOT NULL,
                        DATE_FORMAT(mssql_schedulemaster.ScheduleDate,'%m/%d/%Y'),
                        ''
                    ) as `Shift Date`"),
                    "mssql_patients.Payer",
                    self::SPECIALPAYER,
                );
                $query->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.PatientID", "=", "mssql_schedulemaster.PatientID");
                });
                $query->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $query->leftJoin("regions", function ($query) {
                    $query->on("regions.id", "=", "mssql_facilities.region");
                });
                $query->leftJoin("users", function ($query) {
                    $query->on("users.id", "=", "mssql_patients.ccc_id");
                });
                $query->where('is_missed_visit', '1');
                $query->where('IsBillable', '1');
                if (count($rowFilter)) {
                    $fieldmap = [
                        'state_id' => 'mssql_facilities.id',
                        'region_id' => 'regions.id',
                        'ccc_id' => 'users.id',
                        'lob' => 'mssql_patients.LOBID'
                    ];

                    $allowedRowFilterKeys = [
                        'state_id',
                        'ccc_id',
                        'start_of_week',
                        'lob'
                    ];

                    $rowFilter = array_intersect_key($rowFilter, array_flip($allowedRowFilterKeys));

                    $query->where(function ($q) use ($rowFilter, $fieldmap, $form) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if ($rfkey == 'start_of_week') {
                                if ($form[2]['value'] == 'month') {
                                    $q->whereRaw('MONTH(mssql_schedulemaster.ScheduleDate) = Month(CONCAT(?,"-01")) AND
                                    YEAR(mssql_schedulemaster.ScheduleDate) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                } else {
                                    $q->whereRaw('mssql_schedulemaster.ScheduleDate >= ? AND
                                    mssql_schedulemaster.ScheduleDate < (? + INTERVAL 7 - weekday(?) DAY)', [
                                        $rf,
                                        $rf,
                                        $rf
                                    ]);
                                }
                            } else {
                                if (is_array($rf)) {
                                    $q->whereIn($rfkey, $rf);
                                } else {
                                    if (LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank')) {
                                        if ($rfkey == 'users.id' && $rf == 0) {
                                            $q->whereNull('users.id');
                                        } else {
                                            $q->where($rfkey, "=", $rf);
                                        }
                                    } else {
                                        $q->where($rfkey, "=", $rf);
                                    }
                                }
                            }
                        }
                    });
                }
            } elseif ($req['type'] == 'coverageBooked') {
                $query = DB::table("coverage_tracker")->select(
                    self::BRANCH,
                    self::COORDINATOR,
                    DB::raw(
                        "CONCAT(
                            mssql_patients.Patient_FirstName,
                            ' ',
                            mssql_patients.Patient_LastName
                        ) as `Client`"
                    ),
                    DB::raw("IF(
                        coverage_tracker.shift_date != '' AND coverage_tracker.shift_date IS NOT NULL,
                        DATE_FORMAT(coverage_tracker.shift_date,'%m/%d/%Y'),
                        ''
                    ) as `Visit Date`"),
                    "mssql_patients.Payer",
                    self::SPECIALPAYER,
                    "coverage_tracker.Hours as Planned Hours",
                    "mssql_providers.fullName as Clinician",
                    "coverage_tracker.escalated_pay_rate as Pay Rate"
                );
                $query->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "coverage_tracker.aoc_patient_id");
                });
                $query->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $query->leftJoin("regions", function ($query) {
                    $query->on("regions.id", "=", "mssql_facilities.region");
                });
                $query->leftJoin("users", function ($query) {
                    $query->on("users.id", "=", "coverage_tracker.filled_by");
                });
                $query->leftJoin("mssql_providers", function ($query) {
                    $query->on("mssql_providers.id", "=", "coverage_tracker.aoc_provider_id");
                });
                if ($form[4]['value'] == 'worked') {
                    if (LD::checkFeatureFlag('include-client-not-in-kantime-in-NCC-report')) {
                        $query->whereRaw(' (
                                (coverage_tracker.has_billable_visit = 1 AND mssql_patients.not_in_kantime = 0) OR
                                mssql_patients.not_in_kantime = 1
                            ) ');
                    } else {
                        $query->whereRaw(' isnull((select sm.ScheduleDate from mssql_schedulemaster sm where
                            sm.ProviderID = mssql_providers.ProviderID and sm.PatientID = mssql_patients.PatientID and
                            sm.ScheduleStatusID in (1,2,3) and sm.ScheduleDate = coverage_tracker.shift_date limit 1)) = false '
                        );
                    }
                }
                if (count($rowFilter)) {
                    $fieldmap = [
                        'state' => 'mssql_facilities.State',
                        'region_id' => 'regions.id',
                        'ccc_id' => 'users.id',
                        'Branch_Location' => 'mssql_facilities.FacilityName',
                        'lob' => 'mssql_patients.LOBID'
                    ];

                    $allowedRowFilterKeys = [
                        'state',
                        'region_id',
                        'ccc_id',
                        'Branch_Location',
                        'start_of_week',
                        'lob'
                    ];

                    $rowFilter = array_intersect_key($rowFilter, array_flip($allowedRowFilterKeys));

                    $query->where(function ($q) use ($rowFilter, $fieldmap, $form) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if ($rfkey == 'start_of_week') {
                                if ($form[2]['value'] == 'month') {
                                    $q->whereRaw('MONTH(coverage_tracker.created_at) = Month(CONCAT(?,"-01")) AND
                                    YEAR(coverage_tracker.created_at) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                } else {
                                    $q->whereRaw('coverage_tracker.created_at >= ? AND
                                    coverage_tracker.created_at < (? + INTERVAL 7 - weekday(?) DAY)', [
                                        $rf,
                                        $rf,
                                        $rf
                                    ]);
                                }
                            } else {
                                if (is_array($rf)) {
                                    $q->whereIn($rfkey, $rf);
                                } else {
                                    $q->where($rfkey, "=", $rf);
                                }
                            }
                        }
                    });
                }
            } elseif ($req['type'] == 'nthWeeklyCalls') {
                $table = 'ccc_followup_calls';
                $query = DB::table($table)->select(
                    self::BRANCH,
                    (LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank') ?
                    DB::raw("IFNULL(users.name, 'Unassigned') as Coordinator") : self::COORDINATOR),
                    DB::raw("
                        CONCAT(
                            mssql_patients.Patient_FirstName,
                            ' ',
                            mssql_patients.Patient_LastName,
                            '|',
                            mssql_patients.id
                        ) as `Client`"),
                    "NeedToHire_Accounts.ServicesNeeded as Service Needed",
                    "mssql_patients.Payer",
                    self::SPECIALPAYER,
                    DB::raw("IF(
                        NeedToHire_Accounts.CreatedOn != '' AND NeedToHire_Accounts.CreatedOn IS NOT NULL,
                        DATE_FORMAT(NeedToHire_Accounts.CreatedOn,'%m/%d/%Y'),
                        ''
                    ) as `Visit Date`"),
					DB::raw('(SELECT sum(ShiftHours) from NeedToHire_Schedules where NeedToHire_Schedules.aoc_nthaccounts_id = '.$table.'.aoc_nthaccount_id and NeedToHire_Accounts.IsActive = 1 and NeedToHire_Schedules.IsActive = 1) as `Hours on NTH`')
                );
                $query->leftJoin("NeedToHire_Accounts", function ($query) use ($table) {
                    $query->on($table.".aoc_nthaccount_id", "=", "NeedToHire_Accounts.id");
                });
                $query->leftJoin("mssql_patients", function ($query) {
                    $query->on("NeedToHire_Accounts.aoc_patient_id", "=", "mssql_patients.id");
                });
                $query->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
                });
                $query->leftJoin("regions", function ($query) {
                    $query->on("mssql_facilities.region", "=", "regions.id");
                });
                $query->leftJoin("users", function ($query) use ($table) {
                    $query->on($table. ".ccc_id", "=", "users.id");
                });
				$query->where($table.'.type', 'nth');
                if (count($rowFilter)) {
                    $fieldmap = [
                        'state_id' => 'mssql_facilities.id',
                        'region_id' => 'regions.id',
                        'ccc_id' => 'users.id',
						'Branch_Location' => 'mssql_facilities.FacilityName',
                        'lob' => 'mssql_patients.LOBID'
                    ];

                    $allowedRowFilterKeys = [
                        'state_id',
                        'region_id',
                        'ccc_id',
                        'Branch_Location',
                        'start_of_week',
                        'lob'
                    ];

                    $rowFilter = array_intersect_key($rowFilter, array_flip($allowedRowFilterKeys));

                    $query->where(function ($q) use ($rowFilter, $fieldmap, $form, $table) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if ($rfkey == 'start_of_week') {
								$q->whereRaw($table . '.week_month_start >= ? AND
								'.$table.'.week_month_start < (? + INTERVAL 7 - weekday(?) DAY)', [
									$rf,
									$rf,
									$rf
								]);
                            } else {
                                if (is_array($rf)) {
                                    $q->whereIn($rfkey, $rf);
                                } else {
                                    if (LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank')) {
                                        if ($rfkey == 'users.id' && $rf == 0) {
                                            $q->whereNull('users.id');
                                        } else {
                                            $q->where($rfkey, "=", $rf);
                                        }
                                    } else {
                                        $q->where($rfkey, "=", $rf);
                                    }
                                }
                            }
                        }
                    });
                }
            } elseif ($req['type'] == 'nthMonthlyCalls') {
                $table = 'ccc_followup_calls';
                $query = DB::table($table)->select(
                    self::BRANCH,
                    (LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank') ?
                    DB::raw("IFNULL(users.name, 'Unassigned') as Coordinator") : self::COORDINATOR),
                    DB::raw("
                        CONCAT(
                            mssql_patients.Patient_FirstName,
                            ' ',
                            mssql_patients.Patient_LastName,
                            '|',
                            mssql_patients.id
                        ) as `Client`"),
                    DB::raw("IF(
                        mssql_patients.SOCDate != '' AND mssql_patients.SOCDate IS NOT NULL,
                        DATE_FORMAT(mssql_patients.SOCDate,'%m/%d/%Y'),
                        ''
                    ) as `SOC Date`"),
                    "mssql_patients.Payer",
                    self::SPECIALPAYER
                );
                $query->leftJoin("mssql_patients", function ($query) use ($table) {
                    $query->on($table . ".aoc_patient_id", "=", "mssql_patients.id");
                });
                $query->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
                });
                $query->leftJoin("regions", function ($query) {
                    $query->on("mssql_facilities.region", "=", "regions.id");
                });
                $query->leftJoin("users", function ($query) use ($table) {
                    $query->on($table . ".ccc_id", "=", "users.id");
                });
                $query->where($table.'.type', 'client');
                if (count($rowFilter)) {
                    $fieldmap = [
                        'state_id' => 'mssql_facilities.id',
                        'region_id' => 'regions.id',
                        'ccc_id' => 'users.id',
                        'lob' => 'mssql_patients.LOBID'
                    ];

                    $allowedRowFilterKeys = [
                        'state_id',
                        'region_id',
                        'ccc_id',
                        'lob',
                        'start_of_week'
                    ];

                    $rowFilter = array_intersect_key($rowFilter, array_flip($allowedRowFilterKeys));

                    $query->where(function ($q) use ($rowFilter, $fieldmap, $form, $table) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if ($rfkey == 'start_of_week') {
								$q->whereRaw('MONTH('.$table.'.week_month_start) = Month(CONCAT(?,"-01")) AND
								YEAR('.$table.'.week_month_start) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                            } else {
                                if (is_array($rf)) {
                                    $q->whereIn($rfkey, $rf);
                                } else {
                                    if (LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank')) {
                                        if ($rfkey == 'users.id' && $rf == 0) {
                                            $q->whereNull('users.id');
                                        } else {
                                            $q->where($rfkey, "=", $rf);
                                        }
                                    } else {
                                        $q->where($rfkey, "=", $rf);
                                    }
                                }
                            }
                        }
                    });
                }
            }
            H::ExcelLog(print_r($query->toSql(), true), 'dbNccPop.log');
            if ($query) {
                $result = $query->get()->toArray();
            }

        }

        return view('reports.popup', ['data' => $request->all(), "results" => count($result) > 0 ? $result : []]);
    }

    public function missedVisit()
    {
        $period = 'week';
        if (isset(request()->period)) {
            $period = request()->period;
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
        $report['title'] = 'Missed Visit';
        $report['description'] = 'The count is showing visits in planned status for
        the previous week and data is ran every Thursday.';
        $report['id'] = 'missedVisit';
        $report['period'] = $period;
        $report['goalField'] = 'candidate_start_hour_goals';
        //columns defines the fieldname, heading, optional format, width and style for each column
        $report['columns'] = [
            [
                'field' => 'state',
                'heading' => [
                    'title' => 'State',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => 'hide',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ],
            [
                'field' => 'region',
                'heading' => [
                    'title' => 'Region',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => 'hide',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ],
            [
                'field' => 'Branch_Location',
                'heading' => [
                    'title' => 'Branch',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => 'hide',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ],
            [
                'field' => 'ccc',
                'heading' => [
                    'title' => 'CCC',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => '',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ]
        ];
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = request()->date['to'];
            if($period=='week'){
                if($sdate < '2024-02-26'){
                    $sdate = '2024-02-26';
                }
            }else{
                if($sdate < '2024-02-01'){
                    $sdate = '2024-02-01';
                }
            }

            $periods = H::getReportPeriodColumns(
                $report['period'],
                ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate),
                request()->date['to']
            );
        } else {
            $date = new \Carbon\Carbon();
            if($date->dayOfWeek<4){
                //before Thursday we want Sunday of previous week
                $tdate = $date->subDays(7)->startOfWeek()->subDays(1)->format('Y-m-d');
            }else{
                //after Wednesday we want last sunday
                $tdate = $date->startOfWeek()->subDays(1)->format('Y-m-d');
            }
            //tempm change until after 4/29/2024
            if($period=='week'){
                $dt = new \DateTime();
                $today = $dt->format('Y-m-d');
                if($today < '2024-05-27'){
                    $sdate = '2024-02-19';
                    $periods = H::getReportPeriodColumns(
                        $report['period'],
                        H::datediffInWeeks($sdate, $tdate),
                        $tdate
                    );
                }else{
                    $sdate = H::getReportStartingDate('week', 13, H::getReportStartingDate('week'));
                    $periods = H::getReportPeriodColumns(
                        $report['period'],
                        H::datediffInWeeks($sdate, $tdate),
                        $tdate
                    );
                }
            }else{
                $dt = new \DateTime();
                $today = $dt->format('Y-m-d');
                if($today < '2025-03-01'){
                    $sdate = '2024-02-01';
                    $tdate = H::getReportEndingDate('month');
                    $periods = H::getReportPeriodColumns(
                        $report['period'],
                        H::monthDiff($sdate, $tdate),
                        $tdate
                    );
                }else{
                    $sdate = H::getReportStartingDate('month', 9, H::getReportStartingDate('week'));
                    $tdate = H::getReportEndingDate('week');
                    $periods = H::getReportPeriodColumns(
                        $report['period'],
                        H::monthDiff($sdate, $tdate),
                        $tdate
                    );
                }
            }
        }
        $showAvgColumn = 0;

        $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
        if ($selectedDatesDiffInWeek >= 13) {
            $showAvgColumn = 1;
        }
        H::ExcelLog("selectedDatesDiffInWeek==" . $selectedDatesDiffInWeek, 'dbNCC.log');
        H::ExcelLog("showAvgColumn==" . $showAvgColumn, 'dbNCC.log');

        foreach ($periods as $period) {
            $clickedFilters = [];
            $clickedFilters = [
                'state_id',
                'region_id',
                'ccc_id'
            ];
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => [
                    'title' => $period['label'],
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => 'sum',
                    'attributes' => '',
                    'style' => ''
                ],
                'clickable' => [
                    'jsfunction' => 'openNationalClientCareReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name']
                    ]
                ],
            ];
        }

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => [
                'title' => 'Total',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                'attributes' => '',
                'style' => ''
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
            [
                'id' => 1,
                'label' => '',
                'field' => 'region',
                'showCols' => 'region',
                'style' => self::BACKGROUND4,
                'level' => 1,
            ],
            [
                'id' => 2,
                'label' => '',
                'field' => 'Branch_Location',
                'showCols' => 'Branch_Location',
                'style' => self::BACKGROUND3,
                'level' => 2,
            ],
            [
                'id' => 3,
                'label' => self::GROUP3,
                'field' => 'state',
                'showCols' => 'state',
                'style' => self::BACKGROUND1,
                'level' => 3
            ],
            [
                'id' => 4,
                'label' => '',
                'field' => '',
                'showCols' => '',
                'style' => '',
                'level' => 4
            ],
            [
                'id' => 9,
                'label' => self::NATIONALTOTAL,
                'field' => '',
                'showCols' => '',
                'style' => self::BACKGROUND2,
                'level' => 5
            ],
        ];

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches = [];
        foreach ($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'], 'dbNCC.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2', 'dbNCC.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        $regions = H::getHelper("ConstantHelper")->getRegion();
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
                'label' => self::CCC,
                'name' => 'ccc',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
            ],
            [
                'label' => 'Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ],
            [
                'label'=>'Payer',
                'name'=>'payer',
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
        if (LD::checkFeatureFlag('add-lob-filter-to-client-care-report')) {
            $lobCollection = H::getHelper('ConstantHelper')->getLob();
            $loboption = [];
            foreach($lobCollection as $lob) {
                $loboption[] = ['value' => $lob->LOBID, 'text' => $lob->LOBName];
            }
            array_splice($filters, 5, 0, [[
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'multiselect',
                'source' => $loboption
            ]]);
        }
        $sourceFilters = [];


        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'dbNCC.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mp.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "mf.State";
                        } elseif ($field == 'region') {
                            $tmp = 'reg.region_name';
                        } elseif ($field == 'ccc') {
                            $tmp = 'u.id';
                        } elseif ($field == 'payer') {
                            $tmp = 'mp.Payer';
                        } elseif ($field == 'payer_group') {
                            $tmp = 'mp.payer_group';
                        } elseif ($field == 'lob') {
                            $tmp = 'mp.LOBID';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNCC.log');
                        if ($value != '') {
                            if ($field == 'aoc_facility_id') {
                                $where[] = 'mp.aoc_facility_id = ?';
                                array_push($paramsArray, $value);
                            } else if ($field == 'state') {
                                $where[] = 'mf.State = ?';
                                array_push($paramsArray, $value);
                            } elseif ($field == 'region') {
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'ccc') {
                                $where[] = "u.id = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'payer') {
                                $where[] = "mp.Payer = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'payer_group') {
                                $where[] = "mp.payer_group = ?";
                                array_push($paramsArray, $value);
                            } else {
                                $where[] = $field . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'msm.ScheduleDate', true);
        //now get report data

        $qry = "SELECT mf.State as state, mf.FacilityName as Branch_Location,
        reg.region_name,reg.region_name as region, ".(LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank') ?
        'IFNULL(u.name, \'Unassigned\') as ccc' : 'u.name as ccc').",
        " . $periodQuery . " as Monday, count(msm.id) as myCount, mf.id as state_id, reg.id as region_id, ".(LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank') ?
        'IFNULL(u.id, \'0\') as ccc_id' : 'u.id as ccc_id')."
        FROM `mssql_schedulemaster` msm
        left join mssql_patients mp on msm.PatientID = mp.PatientID
        left join users u on mp.ccc_id = u.id
        left join mssql_facilities mf on mp.aoc_facility_id = mf.id
        left join regions reg on mf.region = reg.id
        where msm.ScheduleDate >= ? and msm.ScheduleDate <= ? AND msm.is_missed_visit = 1 AND msm.IsBillable = 1 " .
            (!empty($where) ? self::ANDCONDITION . implode(self::ANDCONDITION, $where) : '') . "
        group by mf.State, region, mp.aoc_facility_id, u.id, " . $periodQuery . "
        order by mf.State, region, mp.aoc_facility_id, u.id, " . $periodQuery;

        H::ExcelLog($qry, 'dbNCC.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            $id = $record['Branch_Location'] . $record['state'] . $record['region'] . $record['ccc'];
            if (!isset($data[$id])) {
                $data[$id] = [
                    'state' => $record['state'],
                    'region' => $record['region'],
                    'Branch_Location' => $record['Branch_Location'],
                    'ccc' => $record['ccc'],
                    'state_id' => $record['state_id'],
                    'region_id' => $record['region_id'],
                    'ccc_id' => $record['ccc_id']
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

            if (request()->view && request()->view == 'percentage') {
                $total1 = (($record[$report['goalField']] > 0) ? (($record['myCount'] / $record[$report['goalField']]) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if (floatval($data[$id]['Total']) != 0) {
                    $data[$id]['Count']++;
                }
            } else {

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

                $data[$id][$record['Monday']] = $record['myCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
            }
        }

        if (LD::checkFeatureFlag('ncc-report-show-all-states')) {
            foreach ($state as $st) {
                if (($key = array_search($st['value'], array_column($data, 'state'))) === false) {
                    $firstElement = [
                        'state' => $st['value'],
                        'region' => '',
                        'Branch_Location' => '',
                        'ccc' => '',
                        'state_id' => 0,
                        'region_id' => 0,
                        'ccc_id' => 0
                    ];
                    foreach ($periods as $period) {
                        $firstElement[$period['name']] = 0;
                    }
                    $firstElement['SixWeekAvg'] = 0;
                    $firstElement['ThirteenWeekAvg'] = 0;
                    $firstElement['AvgTrend'] = 0;
                    $firstElement['Total'] = 0;
                    $data[$st['value']] = $firstElement;
                }
            }

            uasort($data, function ($a, $b) {
                return strcmp($a['state'], $b['state']);
            });
        }

        if (request()->view && request()->view == 'percentage') {
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
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.nationalClientCareReports', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => 'branch'
        ]);
    }

    public function coverageBooked()
    {
        $period = 'week';
        if (isset(request()->period)) {
            $period = request()->period;
        }

        $source = 'ccc';
        if(isset(request()->source)) {
            $source = request()->source;
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
        $report['title'] = 'Coverage Hours';
        $report['description'] = 'Coverage hours booked count is showing the hours booked for the week.
                                Hours worked view is showing the hours on schedule that week with a status of in
                                progress, completed, or approved.';
        $report['id'] = 'coverageBooked';
        $report['period'] = $period;
        $report['goalField'] = ($source == 'ccc') ? 'coverage_booked_goal' : 'branch_coverage_booked_goal';
        //columns defines the fieldname, heading, optional format, width and style for each column
        if (LD::checkFeatureFlag('ncc-remove-branch-region-ccc-view')) {
            $report['columns'] = [
                [
                    'field' => 'state',
                    'heading' => [
                        'title' => 'State',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
            if ($source != 'ccc') {
            $report['columns'][] = [
                    'field' => 'region',
                    'heading' => [
                        'title' => 'Region',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ];
            $report['columns'][] = [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => ($source == 'ccc') ? 'hide' : '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ];
            }
        } else {
            $report['columns'] = [
                [
                    'field' => 'state',
                    'heading' => [
                        'title' => 'State',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'region',
                    'heading' => [
                        'title' => 'Region',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => ($source == 'ccc') ? 'hide' : '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
            ];
        }
        if ($source == 'ccc') {
            $report['columns'][] =[
                'field' => 'ccc',
                'heading' => [
                    'title' => 'CCC',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => '',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ];

            $report['columns'][] =[
                'field' => 'roles',
                'heading' => [
                    'title' => 'Role(s)',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => '',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ];
        }
        $report['columns'][] = [
            'field' => $report['goalField'],
            'heading' => [
                'title' => 'Goal',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => '',
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => 'sum',
                'attributes' => '',
                'style' => ''
            ],
        ];
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = request()->date['to'];
            H::ExcelLog(H::datediffInWeeks($sdate, $tdate), 'dbNCC.log');
            H::ExcelLog($tdate, 'dbNCC.log');

            $periods = H::getReportPeriodColumns(
                $report['period'],
                ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate),
                request()->date['to']
            );
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;

        $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
        if ($selectedDatesDiffInWeek >= 13) {
            $showAvgColumn = 1;
        }
        H::ExcelLog("selectedDatesDiffInWeek==" . $selectedDatesDiffInWeek, 'dbNCC.log');
        H::ExcelLog("showAvgColumn==" . $showAvgColumn, 'dbNCC.log');

        foreach ($periods as $period) {
            $clickedFilters = [];
            if ($source == 'ccc') {
                $clickedFilters = [
                    'state',
                    'ccc_id',
                    //$report['goalField']
                ];
            } else {
                $clickedFilters = [
                    'state',
                    'region_id',
                    'Branch_Location',
                    //$report['goalField']
                ];
            }
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => [
                    'title' => $period['label'],
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                    'attributes' => '',
                    'style' => ''
                ],
                'clickable' => [
                    'jsfunction' => 'openNationalClientCareReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name']
                    ]
                ],
            ];
        }

        $report['columns'][] = [
            'field' => 'SixWeekAvg',
            'heading' => [
                'title' => 'Six Week Average',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : ''),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => 'weekavg',
                'attributes' => '',
                'style' => ''
            ],
        ];
        $report['columns'][] = [
            'field' => 'ThirteenWeekAvg',
            'heading' => [
                'title' => 'Thirteen Week Average',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : ''),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => 'weekavg',
                'attributes' => '',
                'style' => ''
            ],
        ];
        $report['columns'][] = [
            'field' => 'AvgTrend',
            'heading' => [
                'title' => '',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : ''),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => 'weekavg',
                'attributes' => '',
                'style' => ''
            ],
        ];

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => [
                'title' => 'Total',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                'attributes' => '',
                'style' => ''
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
        if ($source == 'ccc') {
            if (LD::checkFeatureFlag('ncc-remove-branch-region-ccc-view')) {
                $report['groupby'] = [
                    [
                        'id' => 1,
                        'label' => '@group1',
                        'field' => 'state',
                        'showCols' => 'state',
                        'style' => self::BACKGROUND1,
                        'level' => 1,
                    ],
                    [
                        'id' => 2,
                        'label' => '',
                        'field' => '',
                        'showCols' => '',
                        'style' => '',
                        'level' => 2
                    ],
                    [
                        'id' => 3,
                        'label' => '',
                        'field' => '',
                        'showCols' => '',
                        'style' => '',
                        'level' => 3
                    ],
                    [
                        'id' => 4,
                        'label' => '',
                        'field' => '',
                        'showCols' => '',
                        'style' => '',
                        'level' => 4
                    ],
                    [
                        'id' => 9,
                        'label' => self::NATIONALTOTAL,
                        'field' => '',
                        'showCols' => '',
                        'style' => self::BACKGROUND2,
                        'level' => 5,
                        'hidetotal' => (
                            request()->view && request()->view == 'percentage'
                        ) ? 'true' : false
                    ],
                ];
            } else {
                $report['groupby'] = [
                    [
                        'id' => 1,
                        'label' => '',
                        'field' => 'region',
                        'showCols' => 'region',
                        'style' => self::BACKGROUND4,
                        'level' => 1,
                        'hidetotal' => (
                            request()->view && request()->view == 'percentage'
                        ) ? 'true' : false
                    ],
                    [
                        'id' => 2,
                        'label' => '',
                        'field' => 'Branch_Location',
                        'showCols' => 'Branch_Location',
                        'style' => self::BACKGROUND3,
                        'level' => 2,
                        'hidetotal' => (
                            request()->view && request()->view == 'percentage'
                        ) ? 'true' : false
                    ],
                    [
                        'id' => 3,
                        'label' => self::GROUP3,
                        'field' => 'state',
                        'showCols' => 'state',
                        'style' => self::BACKGROUND1,
                        'level' => 3,
                        'hidetotal' => (
                            request()->view && request()->view == 'percentage'
                        ) ? 'true' : false
                    ],
                    [
                        'id' => 4,
                        'label' => '',
                        'field' => '',
                        'showCols' => '',
                        'style' => '',
                        'level' => 4
                    ],
                    [
                        'id' => 9,
                        'label' => self::NATIONALTOTAL,
                        'field' => '',
                        'showCols' => '',
                        'style' => self::BACKGROUND2,
                        'level' => 5,
                        'hidetotal' => (
                            request()->view && request()->view == 'percentage'
                        ) ? 'true' : false
                    ],
                ];
            }
        } else {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'region',
                    'showCols' => 'region',
                    'style' => self::BACKGROUND4,
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'state',
                    'showCols' => 'state',
                    'style' => self::BACKGROUND1,
                    'level' => 2
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        }

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches = [];
        foreach ($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'], 'dbNCC.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2', 'dbNCC.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        $regions = H::getHelper("ConstantHelper")->getRegion();
        $branches = [];
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
                'label' => self::CCC,
                'name' => 'ccc',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
            ],
            [
                'label' => 'Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ],
            [
                'label'=>'Payer',
                'name'=>'payer',
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

        if (LD::checkFeatureFlag('add-lob-filter-to-client-care-report')) {
            $lobCollection = H::getHelper('ConstantHelper')->getLob();
            $loboption = [];
            foreach($lobCollection as $lob) {
                $loboption[] = ['value' => $lob->LOBID, 'text' => $lob->LOBName];
            }
            array_splice($filters, 5, 0, [[
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'multiselect',
                'source' => $loboption
            ]]);
        }

        $sourceFilters = [
            [
                'label' => 'By CCC',
                'value' => 'ccc'
            ],
            [
                'label' => 'By Branch',
                'value' => 'branch'
            ]
        ];


        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'dbNCC.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mp.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "mf.State";
                        } elseif ($field == 'region') {
                            $tmp = 'reg.region_name';
                        } elseif ($field == 'ccc') {
                            $tmp = 'u.id';
                        } elseif ($field == 'payer') {
                            $tmp = 'mp.Payer';
                        } elseif ($field == 'payer_group') {
                            $tmp = 'mp.payer_group';
                        } elseif ($field == 'lob') {
                            $tmp = 'mp.LOBID';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNCC.log');
                        if ($value != '') {
                            if ($field == 'aoc_facility_id') {
                                $where[] = 'mp.aoc_facility_id = ?';
                                array_push($paramsArray, $value);
                            } else if ($field == 'state') {
                                $where[] = 'mf.State = ?';
                                array_push($paramsArray, $value);
                            } elseif ($field == 'region') {
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'ccc') {
                                $where[] = "u.id = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'payer') {
                                $where[] = "mp.Payer = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'payer_group') {
                                $where[] = "mp.payer_group = ?";
                                array_push($paramsArray, $value);
                            } else {
                                $where[] = $field . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'ct.created_at', true);
        //now get report data

        if (request()->view && request()->view == 'worked') {
            if (LD::checkFeatureFlag('include-client-not-in-kantime-in-NCC-report')) {
                $where[] = ' ((ct.has_billable_visit = 1 and mp.not_in_kantime = 0) OR  mp.not_in_kantime = 1) ';
            } else {
                $where[] = ' isnull((select sm.ScheduleDate from mssql_schedulemaster sm where
                sm.ProviderID = p2.ProviderID and sm.PatientID = mp.PatientID and sm.ScheduleStatusID in (1,2,3)
                and sm.isBillable = 1 and sm.ScheduleDate = ct.shift_date limit 1)) = false ';
            }
        }

        if ($source == 'branch') {
            $qry = "SELECT mf.State as state, mf.FacilityName as Branch_Location,
            reg.region_name,reg.region_name as region, u.name as ccc,
            " . $periodQuery . " as Monday, sum(ct.Hours) as myCount, mf.id as state_id,
            reg.id as region_id, u.id as ccc_id, ".(LD::checkFeatureFlag('ncc-coverage-hours-exclude-per-goal-for-outside-of-payroall') ? 'IF((select
				State from mssql_providers where mssql_providers.AOC_Email = u.email limit 1)
				= mp.Patient_State, mf.'.$report['goalField'].', 0) as ' . $report['goalField']
				: 'mf.' . $report['goalField'])."
            FROM `coverage_tracker` ct
            left join mssql_patients mp on ct.aoc_patient_id = mp.id
            left join users u on ct.filled_by = u.id
            left join mssql_facilities mf on mp.aoc_facility_id = mf.id
            left join regions reg on mf.region = reg.id
            left join mssql_providers p2 on ct.aoc_provider_id = p2.id
            where ct.created_at >= ? and ct.created_at <= ? " .
                (!empty($where) ? self::ANDCONDITION . implode(self::ANDCONDITION, $where) : '') . "
            group by mf.State, region, mp.aoc_facility_id, " . $periodQuery . "
            order by mf.State, region, mp.aoc_facility_id, " . $periodQuery;
        } else {
            $groupOrderBy = " group by mf.State, region, mp.aoc_facility_id, u.id, " . $periodQuery . "
                order by mf.State, region, mp.aoc_facility_id, u.name, " . $periodQuery;
            if (LD::checkFeatureFlag('ncc-remove-branch-region-ccc-view')) {
                $groupOrderBy = " group by mf.State, u.id, " . $periodQuery . "
                order by mf.State, u.name, " . $periodQuery;
            }
            $qry = "SELECT mf.State as state, mf.FacilityName as Branch_Location,
            reg.region_name,reg.region_name as region, u.name as ccc,
            " . $periodQuery . " as Monday, sum(ct.Hours) as myCount, mf.id as state_id,
            reg.id as region_id, u.id as ccc_id, ".(LD::checkFeatureFlag('ncc-coverage-hours-exclude-per-goal-for-outside-of-payroall') ? 'IF((select
				State from mssql_providers where mssql_providers.AOC_Email = u.email limit 1)
				= mp.Patient_State, u.'.$report['goalField'].', 0) as ' . $report['goalField']
				: 'u.' . $report['goalField'])."
            FROM `coverage_tracker` ct
            left join mssql_patients mp on ct.aoc_patient_id = mp.id
            left join users u on ct.filled_by = u.id
            left join mssql_facilities mf on mp.aoc_facility_id = mf.id
            left join regions reg on mf.region = reg.id
            left join mssql_providers p2 on ct.aoc_provider_id = p2.id
            where ct.created_at >= ? and ct.created_at <= ? " .
                (!empty($where) ? self::ANDCONDITION . implode(self::ANDCONDITION, $where) : '') . ' ' . $groupOrderBy;
        }

        H::ExcelLog($qry, 'dbNCC.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if ($source == 'branch') {
                $id =  $record['state']."|".$record['region']."|".$record['Branch_Location'];
            } else {
                $id = $record['state']."|".$record['region']."|".$record['Branch_Location']."|".$record['ccc'];
                if (LD::checkFeatureFlag('ncc-remove-branch-region-ccc-view')) {
                    $id = $record['state']."|".$record['ccc'];
                }
            }
            if (!isset($data[$id])) {
                $data[$id] = [
                    'state' => $record['state'],
                    'region' => $record['region'],
                    'Branch_Location' => $record['Branch_Location'],
                    'ccc' => $record['ccc'],
                    'state_id' => $record['state_id'],
                    'region_id' => $record['region_id'],
                    'ccc_id' => $record['ccc_id'],
                    $report['goalField'] => ($report['period'] == 'week') ? $record[$report['goalField']] : (($record[$report['goalField']] / 7) *  $this->get_days_in_month(date('m'), date('Y'))),
                    'roles' => H::getRolesAbbreviationForReport($record['ccc_id'])
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
                $data[$id]['SixWeekAvgCount'] = 0;
                $data[$id]['ThirteenWeekAvgCount'] = 0;
            }

            if (request()->view && request()->view == 'percentage') {
                $total1 = (($record[$report['goalField']] > 0) ? (($record['myCount'] / $record[$report['goalField']]) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if ($total1 != 0) {
                    $data[$id]['Count']++;
                }

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $total1;
                    if ($total1 != 0) {
                        $data[$id]['SixWeekAvgCount']++;
                    }
                }
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $total1;
                    if ($total1 != 0) {
                        $data[$id]['ThirteenWeekAvgCount']++;
                    }
                }

            } else {

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['myCount'];
                    if ($record['myCount'] != 0) {
                        $data[$id]['SixWeekAvgCount']++;
                    }
                }
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $record['myCount'];
                    if ($record['myCount'] != 0) {
                        $data[$id]['ThirteenWeekAvgCount']++;
                    }
                }

                $data[$id][$record['Monday']] = $record['myCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
            }
        }

        if (LD::checkFeatureFlag('ncc-report-show-all-states')) {
            foreach ($state as $st) {
                if (($key = array_search($st['value'], array_column($data, 'state'))) === false) {
                    $firstElement = [
                        'state' => $st['value'],
                        'region' => '',
                        'Branch_Location' => '',
                        'ccc' => '',
                        'state_id' => 0,
                        'region_id' => 0,
                        'ccc_id' => 0,
                        $report['goalField'] => 0,
                        'roles' => ''
                    ];
                    foreach ($periods as $period) {
                        $firstElement[$period['name']] = 0;
                    }
                    $firstElement['SixWeekAvg'] = 0;
                    $firstElement['ThirteenWeekAvg'] = 0;
                    $firstElement['AvgTrend'] = 0;
                    $firstElement['Total'] = 0;
                    $firstElement['Count'] = 0;
                    $firstElement['SixWeekAvgCount'] = 0;
                    $firstElement['ThirteenWeekAvgCount'] = 0;
                    $data[$st['value']] = $firstElement;
                }
            }

            uasort($data, function ($a, $b) {
                return strcmp($a['state'], $b['state']);
            });
        }

        if (request()->view && request()->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach ($test as $key => $value) {
                if ($value['Count'] != 0) {
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'], 0);
                } else {
                    $data[$key]['Total'] = 0;
                }
                if ($value['ThirteenWeekAvgCount'] != 0) {
                    $data[$key]['ThirteenWeekAvg'] = round(
                        $data[$key]['ThirteenWeekAvg'] / $data[$key]['ThirteenWeekAvgCount']
                    );
                } else {
                    $data[$key]['ThirteenWeekAvg'] = 0;
                }
                if ($value['SixWeekAvgCount'] != 0) {
                    $data[$key]['SixWeekAvg'] = round(
                        $data[$key]['SixWeekAvg'] / $data[$key]['SixWeekAvgCount']
                    );
                } else {
                    $data[$key]['SixWeekAvg'] = 0;
                }
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.nationalClientCareReports', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => $source
        ]);
    }

    public function nthWeeklyCalls()
    {
        $period = 'week';

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'NTH Weekly Calls';
        $report['description'] = 'The count is showing the NTH weekly calls that were completed.';
        $report['id'] = 'nthWeeklyCalls';
        $report['period'] = $period;
        $report['goalField'] = 'weekly_goals';
        //columns defines the fieldname, heading, optional format, width and style for each column
        $report['columns'] = [
            [
                'field' => 'state',
                'heading' => [
                    'title' => 'State',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => 'hide',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ],
            [
                'field' => 'region',
                'heading' => [
                    'title' => 'Region',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => 'hide',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ],
            [
                'field' => 'Branch_Location',
                'heading' => [
                    'title' => 'Branch',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => 'hide',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ],
            [
                'field' => 'ccc',
                'heading' => [
                    'title' => 'CCC',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => '',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ]
        ];
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = request()->date['to'];

            $date = new \Carbon\Carbon();
            $maxdate = $date->startOfWeek()->subDays(1)->format('Y-m-d');
            if($tdate > $maxdate){
                $tdate = $maxdate;
            }
            $periods = H::getReportPeriodColumns(
                $report['period'],
                H::datediffInWeeks($sdate, $tdate),
                $tdate
            );
        } else {
            $date = new \Carbon\Carbon();
            $tdate = $date->startOfWeek()->subDays(1)->format('Y-m-d');
            //tempm change until after 4/29/2024
            $dt = new \DateTime();
            $today = $dt->format('Y-m-d');
            if($today < '2024-04-29'){
                $sdate = '2024-02-05';
            }else{
                $sdate = $date->startOfWeek()->subWeeks(13)->format('Y-m-d');
            }
            $periods = H::getReportPeriodColumns(
                $report['period'],
                H::datediffInWeeks($sdate, $tdate),
                $tdate
            );
        }
        $showAvgColumn = 0;

        $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
        if ($selectedDatesDiffInWeek >= 13) {
            $showAvgColumn = 1;
        }

        foreach ($periods as $period) {
            $clickedFilters = [];
            $clickedFilters = [
                'state_id',
                'region_id',
				'Branch_Location',
                'ccc_id'
            ];
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => [
                    'title' => $period['label'],
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                    'attributes' => '',
                    'style' => ''
                ],
                'clickable' => [
                    'jsfunction' => 'openNationalClientCareReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name']
                    ]
                ],
            ];
        }

        if (
            $report['period'] == 'week' &&
            $showAvgColumn == 1
        ) {
            $report['columns'][] = [
                'field' => 'SixWeekAvg',
                'heading' => [
                    'title' => 'Six Week Average',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : ''),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => 'weekavg',
                    'attributes' => '',
                    'style' => ''
                ],
            ];
            $report['columns'][] = [
                'field' => 'ThirteenWeekAvg',
                'heading' => [
                    'title' => 'Thirteen Week Average',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : ''),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => 'weekavg',
                    'attributes' => '',
                    'style' => ''
                ],
            ];
            $report['columns'][] = [
                'field' => 'AvgTrend',
                'heading' => [
                    'title' => '',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : ''),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => 'weekavg',
                    'attributes' => '',
                    'style' => ''
                ],
            ];
        }

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => [
                'title' => 'Total',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                'attributes' => '',
                'style' => ''
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
            [
                'id' => 1,
                'label' => '',
                'field' => 'region',
                'showCols' => 'region',
                'style' => self::BACKGROUND4,
                'level' => 1,
            ],
            [
                'id' => 2,
                'label' => '',
                'field' => 'Branch_Location',
                'showCols' => 'Branch_Location',
                'style' => self::BACKGROUND3,
                'level' => 2,
            ],
            [
                'id' => 3,
                'label' => self::GROUP3,
                'field' => 'state',
                'showCols' => 'state',
                'style' => self::BACKGROUND1,
                'level' => 3
            ],
            [
                'id' => 4,
                'label' => '',
                'field' => '',
                'showCols' => '',
                'style' => '',
                'level' => 4
            ],
            [
                'id' => 9,
                'label' => self::NATIONALTOTAL,
                'field' => '',
                'showCols' => '',
                'style' => self::BACKGROUND2,
                'level' => 5
            ],
        ];

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches = [];
        foreach ($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        $startDate = $sdate;
        $endingDate = $tdate;

        $regions = H::getHelper("ConstantHelper")->getRegion();
        $branches = [];
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
                'label' => self::CCC,
                'name' => 'ccc',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
            ],
            [
                'label' => 'Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ],
            [
                'label'=>'Payer',
                'name'=>'payer',
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
        if (LD::checkFeatureFlag('add-lob-filter-to-client-care-report')) {
            $lobCollection = H::getHelper('ConstantHelper')->getLob();
            $loboption = [];
            foreach($lobCollection as $lob) {
                $loboption[] = ['value' => $lob->LOBID, 'text' => $lob->LOBName];
            }
            array_splice($filters, 5, 0, [[
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'multiselect',
                'source' => $loboption
            ]]);
        }
        $sourceFilters = [];


        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'dbNCC.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mp.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "mf.State";
                        } elseif ($field == 'region') {
                            $tmp = 'reg.region_name';
                        } elseif ($field == 'ccc') {
                            $tmp = 'ncc.ccc_id';
                        } elseif ($field == 'payer') {
                            $tmp = 'mp.Payer';
                        } elseif ($field == 'payer_group') {
                            $tmp = 'mp.payer_group';
                        } elseif ($field == 'lob') {
                            $tmp = 'mp.LOBID';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNCC.log');
                        if ($value != '') {
                            if ($field == 'aoc_facility_id') {
                                $where[] = 'mp.aoc_facility_id = ?';
                                array_push($paramsArray, $value);
                            } else if ($field == 'state') {
                                $where[] = 'mf.State = ?';
                                array_push($paramsArray, $value);
                            } elseif ($field == 'region') {
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'ccc') {
                                $where[] = "ncc.ccc_id = ?";
                                array_push($paramsArray, $value);
                            }  elseif ($field == 'payer') {
                                $where[] = "mp.Payer = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'payer_group') {
                                $where[] = "mp.payer_group = ?";
                                array_push($paramsArray, $value);
                            } else {
                                $where[] = $field . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

		$table = 'ccc_followup_calls';
		$where[] = 'ncc.type = "nth"';
		$weekStartField = 'week_month_start';
        $periodQuery = H::getReportPeriodDefinition($report['period'], 'ncc.' . $weekStartField, true);
        //now get report data
        $qry = "SELECT mf.State as state, mf.FacilityName as Branch_Location,
        reg.region_name,reg.region_name as region, ".(LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank') ?
        'IFNULL(u.name, \'Unassigned\') as ccc' : 'u.name as ccc').",
        mf.id as state_id, reg.id as region_id, ".(LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank') ?
        'IFNULL(ncc.ccc_id, \'0\') as ccc_id' : 'ncc.ccc_id as ccc_id').", GROUP_CONCAT(ifnull(ncc.outcome,'')) as outcome,
        " . $periodQuery . " as Monday, count(ncc.id) as myCount
        FROM `" . $table . "` ncc
        left join NeedToHire_Accounts nc on ncc.aoc_nthaccount_id = nc.id
        left join mssql_patients mp on nc.aoc_patient_id = mp.id
        left join users u on ncc.ccc_id = u.id
        left join mssql_facilities mf on mp.aoc_facility_id = mf.id
        left join regions reg on mf.region = reg.id
        where ncc.".$weekStartField." >= ? and ncc.".$weekStartField." <= ? " .
            (!empty($where) ? self::ANDCONDITION . implode(self::ANDCONDITION, $where) : '') . "
        group by mf.State, region, mp.aoc_facility_id, u.id, " . $periodQuery . "
        order by mf.State, region, mp.aoc_facility_id, u.id, " . $periodQuery;

        H::ExcelLog($qry, 'dbNCC.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            $id = $record['Branch_Location'] . $record['state'] . $record['region'] . $record['ccc'];
            $completedCalls = 0;
            foreach (explode(",", $record['outcome']) as $eachOutcome) {
                if ($eachOutcome == 'Not Complete' || $eachOutcome == 'Attempt 2' || $eachOutcome == 'Attempt 3' || $eachOutcome == 'Completed') {
                    $completedCalls++;
                }
            }
            $record[$report['goalField']] = $completedCalls;
            if (!isset($data[$id])) {
                $data[$id] = [
                    'state' => $record['state'],
                    'region' => $record['region'],
                    'Branch_Location' => $record['Branch_Location'],
                    'state_id' => $record['state_id'],
                    'region_id' => $record['region_id'],
                    'ccc' => $record['ccc'],
                    'ccc_id' => $record['ccc_id']
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
                $data[$id]['SixWeekAvgCount'] = 0;
                $data[$id]['ThirteenWeekAvgCount'] = 0;
            }

            if (request()->view && request()->view == 'percentage') {
                $total1 = (($record['myCount'] > 0) ? (($record[$report['goalField']] / $record['myCount']) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if ($total1 != 0) {
                    $data[$id]['Count']++;
                }

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $total1;
                    if ($total1 != 0) {
                        $data[$id]['SixWeekAvgCount']++;
                    }
                }
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $total1;
                    if ($total1 != 0) {
                        $data[$id]['ThirteenWeekAvgCount']++;
                    }
                }

            } else {

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['myCount'];
                    if ($record['myCount'] != 0) {
                        $data[$id]['SixWeekAvgCount']++;
                    }
                }
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $record['myCount'];
                    if ($record['myCount'] != 0) {
                        $data[$id]['ThirteenWeekAvgCount']++;
                    }
                }

                $data[$id][$record['Monday']] = $record['myCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
            }
        }

        if (LD::checkFeatureFlag('ncc-report-show-all-states')) {
            foreach ($state as $st) {
                if (($key = array_search($st['value'], array_column($data, 'state'))) === false) {
                    $firstElement = [
                        'state' => $st['value'],
                        'region' => '',
                        'Branch_Location' => '',
                        'ccc' => '',
                        'state_id' => 0,
                        'region_id' => 0,
                        'ccc_id' => 0
                    ];
                    foreach ($periods as $period) {
                        $firstElement[$period['name']] = 0;
                    }
                    $firstElement['SixWeekAvg'] = 0;
                    $firstElement['ThirteenWeekAvg'] = 0;
                    $firstElement['AvgTrend'] = 0;
                    $firstElement['Total'] = 0;
                    $firstElement['Count'] = 0;
                    $firstElement['SixWeekAvgCount'] = 0;
                    $firstElement['ThirteenWeekAvgCount'] = 0;
                    $data[$st['value']] = $firstElement;
                }
            }

            uasort($data, function ($a, $b) {
                return strcmp($a['state'], $b['state']);
            });
        }

        if (request()->view && request()->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach ($test as $key => $value) {
                if ($value['Count'] != 0) {
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'], 0);
                } else {
                    $data[$key]['Total'] = 0;
                }
                if ($value['ThirteenWeekAvgCount'] != 0) {
                    $data[$key]['ThirteenWeekAvg'] = round(
                        $data[$key]['ThirteenWeekAvg'] / $data[$key]['ThirteenWeekAvgCount']
                    );
                } else {
                    $data[$key]['ThirteenWeekAvg'] = 0;
                }
                if ($value['SixWeekAvgCount'] != 0) {
                    $data[$key]['SixWeekAvg'] = round(
                        $data[$key]['SixWeekAvg'] / $data[$key]['SixWeekAvgCount']
                    );
                } else {
                    $data[$key]['SixWeekAvg'] = 0;
                }
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.nationalClientCareReports', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => 'branch'
        ]);
    }

    public function monthlycalls()
    {
        $period = 'month';

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Monthly Calls';
        $report['description'] = 'The count is showing the monthly calls that were completed.';
        $report['id'] = 'nthMonthlyCalls';
        $report['period'] = $period;
        $report['goalField'] = 'monthly_goals';
        //columns defines the fieldname, heading, optional format, width and style for each column
        $report['columns'] = [
            [
                'field' => 'state',
                'heading' => [
                    'title' => 'State',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => 'hide',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ],
            [
                'field' => 'region',
                'heading' => [
                    'title' => 'Region',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => 'hide',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ],
            [
                'field' => 'Branch_Location',
                'heading' => [
                    'title' => 'Branch',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => 'hide',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ],
            [
                'field' => 'ccc',
                'heading' => [
                    'title' => 'CCC',
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => '',
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => '',
                    'attributes' => '',
                    'style' => ''
                ],
            ]
        ];
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $date = new \Carbon\Carbon();
            $tdate = request()->date['to'];
            /*$date = new \Carbon\Carbon();
            $maxdate = $date->subMonths(1)->endOfMonth()->format('Y-m-d');
            if($tdate > $maxdate){
                $tdate = $maxdate;
            }*/
            $periods = H::getReportPeriodColumns(
                $report['period'],
                H::monthDiff($sdate, $tdate),
                $tdate
            );
        } else {
            $date = new \Carbon\Carbon();
            $tdate = $date->startOfMonth()->subMonths(1)->format('Y-m-d');
            //tempm change until after 4/29/2024
            $dt = new \DateTime();
            $today = $dt->format('Y-m-d');
            if($today < '2025-03-01'){
                $sdate = '2024-02-01';
                $tdate = $today;
                $periods = H::getReportPeriodColumns(
                    $report['period'],
                    H::monthDiff($sdate, $tdate),
                    $tdate
                );
            }else{
                $sdate = $date->startOfMonth()->subMonths(12)->format('Y-m-d');
                $periods = H::getReportPeriodColumns(
                    $report['period'],
                    H::monthDiff($sdate, $tdate),
                    $tdate
                );
            }
        }

        foreach ($periods as $period) {
            $clickedFilters = [];
            $clickedFilters = [
                'state_id',
                'region_id',
                'ccc_id'
            ];
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => [
                    'title' => $period['label'],
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                    'attributes' => '',
                    'style' => ''
                ],
                'clickable' => [
                    'jsfunction' => 'openNationalClientCareReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name']
                    ]
                ],
            ];
        }

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => [
                'title' => 'Total',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                'attributes' => '',
                'style' => ''
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
            [
                'id' => 1,
                'label' => '',
                'field' => 'region',
                'showCols' => 'region',
                'style' => self::BACKGROUND4,
                'level' => 1,
            ],
            [
                'id' => 2,
                'label' => '',
                'field' => 'Branch_Location',
                'showCols' => 'Branch_Location',
                'style' => self::BACKGROUND3,
                'level' => 2,
            ],
            [
                'id' => 3,
                'label' => self::GROUP3,
                'field' => 'state',
                'showCols' => 'state',
                'style' => self::BACKGROUND1,
                'level' => 3
            ],
            [
                'id' => 4,
                'label' => '',
                'field' => '',
                'showCols' => '',
                'style' => '',
                'level' => 4
            ],
            [
                'id' => 9,
                'label' => self::NATIONALTOTAL,
                'field' => '',
                'showCols' => '',
                'style' => self::BACKGROUND2,
                'level' => 5
            ],
        ];

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches = [];
        foreach ($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'], 'dbNCC.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2', 'dbNCC.log');
            $startDate = H::getReportStartingDate('month', 9, H::getReportStartingDate('week'));
            $endingDate = H::getReportEndingDate('week');
        }
        $regions = H::getHelper("ConstantHelper")->getRegion();
        $branches = [];
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
                'label' => self::CCC,
                'name' => 'ccc',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
            ],
            [
                'label' => 'Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ],
            [
                'label'=>'Payer',
                'name'=>'payer',
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
        if (LD::checkFeatureFlag('add-lob-filter-to-client-care-report')) {
            $lobCollection = H::getHelper('ConstantHelper')->getLob();
            $loboption = [];
            foreach($lobCollection as $lob) {
                $loboption[] = ['value' => $lob->LOBID, 'text' => $lob->LOBName];
            }
            array_splice($filters, 5, 0, [[
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'multiselect',
                'source' => $loboption
            ]]);
        }
        $sourceFilters = [];


        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'dbNCC.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mp.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "mf.State";
                        } elseif ($field == 'region') {
                            $tmp = 'reg.region_name';
                        } elseif ($field == 'ccc') {
                            $tmp = 'ncc.ccc_id';
                        } elseif ($field == 'payer') {
                            $tmp = 'mp.Payer';
                        } elseif ($field == 'payer_group') {
                            $tmp = 'mp.payer_group';
                        } elseif ($field == 'lob') {
                            $tmp = 'mp.LOBID';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNCC.log');
                        if ($value != '') {
                            if ($field == 'aoc_facility_id') {
                                $where[] = 'mp.aoc_facility_id = ?';
                                array_push($paramsArray, $value);
                            } else if ($field == 'state') {
                                $where[] = 'mf.State = ?';
                                array_push($paramsArray, $value);
                            } elseif ($field == 'region') {
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'ccc') {
                                $where[] = "ncc.ccc_id = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'payer') {
                                $where[] = "mp.Payer = ?";
                                array_push($paramsArray, $value);
                            } elseif ($field == 'payer_group') {
                                $where[] = "mp.payer_group = ?";
                                array_push($paramsArray, $value);
                            } else {
                                $where[] = $field . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

		$table = 'ccc_followup_calls';
		$where[] = 'ncc.type = "client"';
		$monthStartField = 'week_month_start';

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'ncc.' . $monthStartField, true);
        //now get report data

        $qry = "SELECT mf.State as state, mf.FacilityName as Branch_Location,
        reg.region_name,reg.region_name as region, ".(LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank') ?
                'IFNULL(u.name, \'Unassigned\') as ccc' : 'u.name as ccc').",
        mf.id as state_id, reg.id as region_id, ".(LD::checkFeatureFlag('ncc-show-unassigned-in-ccc-column-if-blank') ?
                'IFNULL(ncc.ccc_id, \'0\') as ccc_id' : 'ncc.ccc_id as ccc_id').", GROUP_CONCAT(ncc.outcome) as outcome,
        " . $periodQuery . " as Monday, count(ncc.id) as myCount
        FROM `".$table."` ncc
        left join mssql_patients mp on ncc.aoc_patient_id = mp.id
        left join users u on ncc.ccc_id = u.id
        left join mssql_facilities mf on mp.aoc_facility_id = mf.id
        left join regions reg on mf.region = reg.id
        where ncc.".$monthStartField." >= ? and ncc.".$monthStartField." <= ? " .
            (!empty($where) ? self::ANDCONDITION . implode(self::ANDCONDITION, $where) : '') . "
        group by mf.State, region, mp.aoc_facility_id, u.id, " . $periodQuery . "
        order by mf.State, region, mp.aoc_facility_id, u.id, " . $periodQuery;

        H::ExcelLog($qry, 'dbNCC.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            $id = $record['Branch_Location'] . $record['state'] . $record['region'] . $record['ccc'];

            $completedCalls = 0;
            foreach (explode(",", $record['outcome']) as $eachOutcome) {
                if ($eachOutcome == 'Not Complete' || $eachOutcome == 'Attempt 3' || $eachOutcome == 'Completed') {
                    $completedCalls++;
                }
            }
            $record[$report['goalField']] = $completedCalls;

            if (!isset($data[$id])) {
                $data[$id] = [
                    'state' => $record['state'],
                    'region' => $record['region'],
                    'Branch_Location' => $record['Branch_Location'],
                    'ccc' => $record['ccc'],
                    'state_id' => $record['state_id'],
                    'region_id' => $record['region_id'],
                    'ccc_id' => $record['ccc_id'],
                    $report['goalField'] => $record[$report['goalField']],
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

            if (request()->view && request()->view == 'percentage') {
                $total1 = (($record['myCount'] > 0) ? (($record[$report['goalField']] / $record['myCount']) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if (floatval($data[$id]['Total']) != 0) {
                    $data[$id]['Count']++;
                }
            } else {
                $data[$id][$record['Monday']] = $record['myCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
            }
        }

        if (LD::checkFeatureFlag('ncc-report-show-all-states')) {
            foreach ($state as $st) {
                if (($key = array_search($st['value'], array_column($data, 'state'))) === false) {
                    $firstElement = [
                        'state' => $st['value'],
                        'region' => '',
                        'Branch_Location' => '',
                        'ccc' => '',
                        'state_id' => 0,
                        'region_id' => 0,
                        'ccc_id' => 0,
                        $report['goalField'] => 0
                    ];
                    foreach ($periods as $period) {
                        $firstElement[$period['name']] = 0;
                    }
                    $firstElement['SixWeekAvg'] = 0;
                    $firstElement['ThirteenWeekAvg'] = 0;
                    $firstElement['AvgTrend'] = 0;
                    $firstElement['Total'] = 0;
                    $firstElement['Count'] = 0;
                    $data[$st['value']] = $firstElement;
                }
            }

            uasort($data, function ($a, $b) {
                return strcmp($a['state'], $b['state']);
            });
        }

        if (request()->view && request()->view == 'percentage') {
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
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.nationalClientCareReports', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => 'branch'
        ]);
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

    public function get_days_in_month($month, $year)
    {
        if ($month == "02")
        {
            if ($year % 4 == 0) {
                return 29;
            } else {
                return 28;
            }
        }
        else if ($month == "01" || $month == "03" || $month == "05" || $month == "07" || $month == "08" || $month == "10" || $month == "12") {
            return 31;
        } else {
            return 30;
        }
    }
}
