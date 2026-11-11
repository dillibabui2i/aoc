<?php

namespace App\Http\Controllers;

use App\Models\JobCategories;
use App\Services\JobCategory\JobCategoryService;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Reports;
use Carbon\Carbon;
use Illuminate\Http\Request;

class CandidateConversionReportController extends Controller
{

    public function __construct(private JobCategoryService $jobCategoryService)
    {
    }

    const BACKGROUND1 = 'background-color: #215eac;color:#fff;';
    const BACKGROUND2 = 'background-color: #D5D5D5;color:#000';
    const BACKGROUND3 = 'background-color: #D9D9D9;color:#444;';
    const BACKGROUND4 = 'background-color: #dbf0fd;color:#444444;';
    const NATIONALTOTAL = 'Totals';
    const ANDCONDITION = ' AND ';
    const GROUP3 = '@group3';
    const CCC = 'Client Care Coordinator';
    const SPECIALPAYER = 'mssql_patients.payer_group as Special Payer';
    const COORDINATOR = 'users.name as Coordinator';
    const BRANCH = 'mssql_facilities.FacilityName as Branch';
    public function getcandidateconversionreportpopup(Request $request)
    {
        $req = $request->all();
        $rowFilter = json_decode($req['rowfilter'], true);
        $form = $req['form'];
        $view = '';
        foreach ($form as $key => $value) {
            if ($value['name'] == 'view')
                $view = $value['value'];
            if (empty($value['value']) || substr_count($value['name'], "date"))
                continue;
            if (substr_count($value['name'], "search") > 0) {
                if (substr_count($value['name'], "[") == 1) {
                    $key = str_replace(["]", "search["], ["", ""], $value['name']);
                    if (isset($rowFilter[$key]) && !is_array($rowFilter[$key])) {
                        $temp = $rowFilter[$key];
                        unset($rowFilter[$key]);
                        $rowFilter[$key][] = $temp;
                        $rowFilter[$key][] = $value['value'];
                    } else {
                        $rowFilter[$key][] = $value['value'];
                    }
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
		$query = DB::table('candidate_applications')
		->select(
			'users.name as Recruiter',
			'candidates.fullName as Candidate Name',
			DB::raw("IF(
				candidate_applications.sentDate != '' AND candidate_applications.sentDate IS NOT NULL,
				DATE_FORMAT(candidate_applications.sentDate,'%m/%d/%Y'),
				''
			) as `Application Date`")
		);
		if (isset($rowFilter['reporttype']) && $rowFilter['reporttype'] == 'Candidate') {
			$query->addSelect(DB::raw("(case
							when candidate_applications.conversion_state = 1 then 'New'
							when candidate_applications.conversion_state = 2 then 'Recruiter Phone Screen'
							when candidate_applications.conversion_state = 3 then 'P.E.T. #1'
							when candidate_applications.conversion_state = 4 then 'P.E.T. #2'

							when candidate_applications.conversion_state = 6 then 'Offer Rejected'
							when candidate_applications.conversion_state = 7 then 'Rejected by Agency'
							when candidate_applications.conversion_state = 8 then 'Do Not Contact'
							when candidate_applications.conversion_state = 9 then 'Nursing Student: Pending Graduation'
							when candidate_applications.conversion_state = 10 then 'Added to Potential Nurse List'
							when candidate_applications.conversion_state = 11 then 'IPEA Scheduled'
							end
						) as `Jobvite Status`")
						);
		}
		if (isset($rowFilter['reporttype']) && $rowFilter['reporttype'] == 'IPEAs') {
			$query->addSelect(DB::raw("IF(
                            mssql_recruitertracker.IPEA_Date != '' AND mssql_recruitertracker.IPEA_Date IS NOT NULL,
                            DATE_FORMAT(mssql_recruitertracker.IPEA_Date,'%m/%d/%Y'),
                            ''
                        ) as `IPEA Date`"));
			$query->addSelect('rs.IPEA_onboarding_status as IPEA Status');
			$query->addSelect('mssql_recruitertracker.Candidate_Current_Status as Tracker Status');
		}
		if (isset($rowFilter['reporttype']) && $rowFilter['reporttype'] == 'Active Coverage (PRN)') {
			$query->addSelect('mssql_recruitertracker.Candidate_Current_Status as Tracker Status');
		}
		if (isset($rowFilter['reporttype']) && $rowFilter['reporttype'] == 'Need to Review New Clients') {
			$query->addSelect(DB::raw("IF(
                            mssql_recruitertracker.IPEA_Date != '' AND mssql_recruitertracker.IPEA_Date IS NOT NULL,
                            DATE_FORMAT(mssql_recruitertracker.IPEA_Date,'%m/%d/%Y'),
                            ''
                        ) as `IPEA Date`"));
			$query->addSelect('mssql_recruitertracker.Candidate_Current_Status as Tracker Status');
		}
		if (
			isset($rowFilter['reporttype']) &&
			(
				$rowFilter['reporttype'] == 'Client Match' ||
				$rowFilter['reporttype'] == 'Candidate Start'
			)
		) {
			$query->addSelect(DB::raw("IF(
                            mssql_recruitertracker.IPEA_Date != '' AND mssql_recruitertracker.IPEA_Date IS NOT NULL,
                            DATE_FORMAT(mssql_recruitertracker.IPEA_Date,'%m/%d/%Y'),
                            ''
                        ) as `IPEA Date`"));
			$query->addSelect(DB::raw("IF(
                            mssql_recruitertracker.PEPI_Date != '' AND mssql_recruitertracker.PEPI_Date IS NOT NULL,
                            DATE_FORMAT(mssql_recruitertracker.PEPI_Date,'%m/%d/%Y'),
                            IF(
                                mssql_recruitertracker.WEPI_Date != '' AND mssql_recruitertracker.WEPI_Date IS NOT NULL,
                                DATE_FORMAT(mssql_recruitertracker.WEPI_Date,'%m/%d/%Y'),
                                IF(
                                    mssql_recruitertracker.Training_Shift != '' AND mssql_recruitertracker.Training_Shift IS NOT NULL,
                                    DATE_FORMAT(mssql_recruitertracker.Training_Shift,'%m/%d/%Y'),
                                    ''
                                )
                            )
                        ) as `PEPI/WEPI/Training Shift Date`"));
			$query->addSelect(DB::raw("IF(
                            rs.scheduled_date != '' AND rs.scheduled_date IS NOT NULL,
                            DATE_FORMAT(rs.scheduled_date,'%m/%d/%Y'),
                            ''
                        ) as `Client Match Date`"));
			$query->addSelect(DB::raw("IF(
                            rs.appointment_date != '' AND rs.appointment_date IS NOT NULL,
                            DATE_FORMAT(rs.appointment_date,'%m/%d/%Y'),
                            ''
                        ) as `Candidate Start Date`"));
			$query->addSelect('mssql_recruitertracker.Candidate_Current_Status as Tracker Status');
			if ($rowFilter['conversiongroup'] == 'Cancelled') {
				$query->addSelect('rs.outcome as Outcome');
				$query->addSelect('rs.outcome_reason as Sub Reason');
			}
		}
		if (isset($rowFilter['reporttype']) && $rowFilter['reporttype'] == 'PEPI/WEPI/Training Shift') {
			$query->addSelect(DB::raw("IF(
                            mssql_recruitertracker.IPEA_Date != '' AND mssql_recruitertracker.IPEA_Date IS NOT NULL,
                            DATE_FORMAT(mssql_recruitertracker.IPEA_Date,'%m/%d/%Y'),
                            ''
                        ) as `IPEA Date`"));
			$query->addSelect(DB::raw("IF(
                            mssql_recruitertracker.PEPI_Date != '' AND mssql_recruitertracker.PEPI_Date IS NOT NULL,
                            DATE_FORMAT(mssql_recruitertracker.PEPI_Date,'%m/%d/%Y'),
                            IF(
                                mssql_recruitertracker.WEPI_Date != '' AND mssql_recruitertracker.WEPI_Date IS NOT NULL,
                                DATE_FORMAT(mssql_recruitertracker.WEPI_Date,'%m/%d/%Y'),
                                IF(
                                    mssql_recruitertracker.Training_Shift != '' AND mssql_recruitertracker.Training_Shift IS NOT NULL,
                                    DATE_FORMAT(mssql_recruitertracker.Training_Shift,'%m/%d/%Y'),
                                    ''
                                )
                            )
                        ) as `PEPI/WEPI/Training Shift Date`"));
			$query->addSelect('mssql_recruitertracker.Candidate_Current_Status as Tracker Status');
			if ($rowFilter['conversiongroup'] == 'Inactive') {
				$query->addSelect('rs.outcome as Outcome');
				$query->addSelect('rs.outcome_reason as Sub Reason');
			}
		}
		if (isset($rowFilter['conversiongroup']) && $rowFilter['conversiongroup'] == 'Offer Rejected') {
			$query->addSelect('candidates.OfferRejectedReason as Offer Rejected Reason');
		}
		if (isset($rowFilter['conversiongroup']) && $rowFilter['conversiongroup'] == 'Rejected By Agency') {
			$query->addSelect('candidates.RejectedByAgencyReason as Rejected by Agency Reason');
		}
		if (isset($rowFilter['conversiongroup']) && $rowFilter['conversiongroup'] == 'IPEA Scheduled') {
			$query->addSelect(DB::raw("IF(
				mssql_recruitertracker.IPEA_Date != '' AND mssql_recruitertracker.IPEA_Date IS NOT NULL,
				DATE_FORMAT(mssql_recruitertracker.IPEA_Date,'%m/%d/%Y'),
				''
			) as `IPEA Date`"));
		}
		if (isset($rowFilter['reporttype']) && $rowFilter['reporttype'] == 'Candidate') {
			$query->addSelect(DB::raw(
				'concat(
					candidates.id,\'#candidate\'
				) as Action'
			));
		} else {
			if (isset($rowFilter['reporttype']) && $rowFilter['reporttype'] == 'IPEAs') {
				$query->addSelect(DB::raw(
					'concat(
						mssql_recruitertracker.id,\'#tracker\',\',\',mssql_recruitertracker.linked_IPEA,\'#ipea\'
					) as Action'
				));
			} else {
				$query->addSelect(DB::raw(
					'concat(
						mssql_recruitertracker.id,\'#tracker\'
					) as Action'
				));
			}
		}
		$query->leftJoin("candidates", function ($query) {
			$query->on("candidates.id", "=", "candidate_applications.candidate_id");
		});
		/*$query->leftJoin("recruiting_schedule", function ($query) {
			$query->on("recruiting_schedule.candidate_application_id", "=", "candidate_applications.id");
		});*/
		$whereClause = '1 = 1';
        if($rowFilter['reporttype']=='IPEAs') $whereClause = "type_id = 1";
        //if($rowFilter['reporttype']=='NTR') $whereClause = "type_id = 1";
        if($rowFilter['reporttype']=='Client Match' || $rowFilter['reporttype']=='Candidate Start') $whereClause = "type_id = 6";
        if($rowFilter['reporttype']=='PEPI/WEPI/Training Shift') $whereClause = "type_id in (3,4,5)";

		$recruitingScheduleSubquery = DB::table('recruiting_schedule as rs')
			->selectRaw("candidate_application_id, MIN(tracker_id) as trackerid, outcome, outcome_reason, appointment_date, scheduled_date,
                ipea_status as IPEA_onboarding_status")
            ->whereRaw($whereClause)
			->groupBy('candidate_application_id');
		$query->leftJoinSub($recruitingScheduleSubquery, 'rs', 'candidate_applications.id', '=', 'rs.candidate_application_id');
		$query->leftJoin("mssql_recruitertracker", function ($query) {
			$query->on("mssql_recruitertracker.id", "=", "rs.trackerid");
		});
		$query->leftJoin("users", function ($query) {
			$query->on("users.id", "=", "candidates.user_id");
		});
		$query->leftJoin("jobs", function ($query) {
			$query->on("jobs.id", "=", "candidate_applications.job_id");
		});
		$query->leftJoin("candidate_application_states", function ($query) {
			$query->on("candidate_application_states.conversion_id", "=", "candidate_applications.conversion_state");
		});

        $activeCategories = $this->jobCategoryService->getCategories();

        $categoriesSql = "'" . implode(
                "','",
                array_map('addslashes', $activeCategories->pluck('category')->toArray())
            ) . "'";

		$query->whereRaw("jobs.category in ($categoriesSql) AND is_trackable AND candidate_application_states.conversion_id > 0");
		if (count($rowFilter)) {
			unset($rowFilter['reporttype']);
			unset($rowFilter['conversiongroup']);
			$rawFieldMap = [
				'sourceType' => 'candidate_applications.sourceType',
				'appSource' => 'candidate_applications.source',
				'aoc_facility_id' => 'jobs.branch',
				'state' => 'jobs.state',
				'recruiter' => 'users.name'
			];
			$query->where(function ($q) use ($rowFilter, $form, $rawFieldMap) {
				foreach ($rowFilter as $rfkey => $rf) {
					$rfkey = isset($rawFieldMap[$rfkey]) ? $rawFieldMap[$rfkey] : $rfkey;
					if ($rfkey == 'start_of_week') {
						if ($form[2]['value'] == 'month') {
							$q->whereRaw('MONTH(date(candidate_applications.sentDate)) = Month(CONCAT(?,"-01")) AND
							YEAR(date(candidate_applications.sentDate)) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
						} else if ($form[2]['value'] == 'quarter') {
							$q->whereRaw('QUARTER(date(candidate_applications.sentDate)) = QUARTER(CONCAT(?,"-01"))', [$rf]);
						} else {
							$q->whereRaw('date(candidate_applications.sentDate) >= ? AND
							date(candidate_applications.sentDate) < (? + INTERVAL 7 - weekday(?) DAY)', [
								$rf,
								$rf,
								$rf
							]);
						}
					} else {
						if (!empty($rfkey)) {
							if (is_array($rf)) {
								if ($rfkey == 'lob') {
									if($rf[0]=='CNA'){
										$q->whereRaw('jobs.category like "%(CNA)%"');
									}else{
										$q->whereRaw('jobs.category not like "%(CNA)%"');
									}
								} else if ($rfkey == 'region') {
									$q->whereRaw("FIND_IN_SET(jobs.branch, ?) > 0", [$rf]);
								} else if($rfkey == 'excludeprn') {
									$q->whereRaw('(
										candidate_applications.isPRN = \'0\' OR
										candidate_applications.isPRN is null
									)');
								} else {
									$q->whereIn($rfkey, $rf);
								}
                            }
							else if (empty($rf)) {
								$q->whereRaw($rfkey . " IS NULL ");
							} else {
								$q->whereRaw($rfkey . " = ? ", [$rf]);
							}
						}
					}
				}
			});
		}

        if ($query) {
            $result = $query->get()->toArray();
        }

        return view('reports.popup', [
            'data' => $request->all(),
            'results' => count($result) > 0 ? $result : []
        ]);
    }

    public function candidateConversion()
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
        $report['title'] = 'Candidate Conversion';
        $report['description'] = 'This report tracks the number and percentage of unique candidates from their
                                application date to when they have their first shift confirmed. The total number
                                of candidates who apply each week, month, or quarter remains fixed; candidates progress
                                through various workflow steps based on their current stage in the process. The ultimate
                                objective is for each candidate to reach the point of having their first shift confirmed.';
        $report['id'] = 'candidateConversion';
        $report['period'] = $period;
        $report['goalField'] = 'candidate_start_hour_goals';
        //columns defines the fieldname, heading, optional format, width and style for each column
        $report['columns'] = [
            [
                'field' => 'reporttype',
                'heading' => [
                    'title' => '',
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
                'field' => 'conversiongroup',
                'heading' => [
                    'title' => '',
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
            ],
			[
				'field' => 'subSection',
				'heading' => [
					'title' => '',
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
            ],
        ];
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('period:' . $report['period'],'candidateconversionreport.log');
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            //$tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            $tdate = request()->date['to'];
            $count = 0;
            if($report['period'] == 'quarter') {
                $count = H::datediffInQuarters($sdate, $tdate);
            }
            if($report['period'] == 'week') {
                $count = H::datediffInWeeks($sdate, $tdate);
            }
            if($report['period'] == 'month') {
                $count = H::monthDiff($sdate, $tdate);
            }
            $periods = H::getReportPeriodColumns($report['period'], $count, request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;

        $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
        if ($selectedDatesDiffInWeek >= 13) {
            $showAvgColumn = 1;
        }
        H::ExcelLog("selectedDatesDiffInWeek==" . $selectedDatesDiffInWeek, 'candidateconversionreport.log');
        H::ExcelLog("showAvgColumn==" . $showAvgColumn, 'candidateconversionreport.log');

        foreach ($periods as $period) {
            $clickedFilters = [];
            $clickedFilters = [
                'reporttype',
                'conversiongroup',
				'conversion_id'
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

        if($report['period'] == 'week' && $showAvgColumn == 1 && (!isset(request()->view) || (isset(request()->view) && request()->view == 'count'))) {
            $report['columns'][] = [
                'field' => 'SixWeekAvg',
                'heading' => ['title' => 'Six Week Average', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'weekavg', 'attributes' => '6', 'style' => ''],
            ];
            $report['columns'][] = [
                'field' => 'ThirteenWeekAvg',
                'heading' => ['title' => 'Thirteen Week Average', 'attributes' => '', 'style' => ''],
                'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'weekavg', 'attributes' => '13', 'style' => ''],
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
                'formula' => ((request()->view && request()->view == 'percentage') ? 'sum' : 'sum'),
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
                'label' => '@group1',
                'field' => 'subSection',
                'showCols' => 'subSection',
                'style' => self::BACKGROUND2,
                'level' => 1,
            ],
            [
                'id' => 2,
                'label' => '',
                'field' => 'conversiongroup',
                'showCols' => 'conversiongroup',
                'style' => self::BACKGROUND2,
                'level' => 2
            ],
            [
                'id' => 3,
                'label' => '@group3',
                'field' => 'reporttype',
                'showCols' => 'reporttype',
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
                'style' => self::BACKGROUND2 . ';',
                'level' => 5
            ],
        ];
        $dashboardState= config('constant.dashboard_state');
        $branches = [];
		$state = [];
        foreach($dashboardState as $key=>$value) {
            $state[] = ["value"=>$value,"text"=>$value];
        }
        //set date ranges
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'], 'candidateconversionreport.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2', 'candidateconversionreport.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        $regions = H::getHelper('ConstantHelper')->getRegion('jobvite_branches');

        $sourceTypeObj = DB::table('candidate_applications')->select("sourceType")->distinct()->where('sourceType','<>','')->get();
        $sourceType = [];
        foreach($sourceTypeObj as $source) {
            $sourceType[] = ["value" => $source->sourceType, 'text' => $source->sourceType];
        }

        $filters = [
            [
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'select',
                'source' => [
                    [
                        "value" => 'PDN',
                        'text' => "PDN"
                    ],
                    [
                        "value" => 'CNA',
                        'text' => "CNA"
                    ]
                ]
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
                'source' => $regions,
                'disabled' => true
            ],
            [
                'label' => 'Branch',
                'name' => 'aoc_facility_id',
                'type' => 'multiselect',
                'source' => $branches
            ],
            [
                'label' => 'Recruiter',
                'name' => 'recruiter',
                'type' => 'multiselect',
                'source' => H::getHelper("ConstantHelper")->getRecruitersList(false)
            ],
            [
                'label' => 'Source Type',
                'name' => 'sourceType',
                'type' => 'multiselect',
                'source' => $sourceType,
                'defaultValue' => H::getReportFiltersFieldVal('sourceType', 'multiselect')
            ],
            [
                'label' => 'Source',
                'name' => 'appSource',
                'type' => 'dependablemultiselect',
                'dependon' => 'sourceType',
                'dependonurl' => route('getSource'),
                'defaultValue' => H::getReportFiltersFieldVal('appSource', 'dependablemultiselect')
            ],
            [
                'label' => 'Application Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ],
        ];

		$filters[] = [
			'label' => 'Exclude PRN',
			'name' => 'excludeprn',
			'type' => 'checkbox',
			'value' => false
		];

        $sourceFilters = [];


        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'candidateconversionreport.log');
                        $tmp = $field;
                        if ($field == 'sourceType') {
                            $tmp = 'candidate_applications.sourceType';
                        } elseif ($field == 'appSource') {
                            $tmp = "candidate_applications.source";
                        } elseif ($field == 'state') {
                            $tmp = 'jobs.state';
                        } elseif ($field == 'aoc_facility_id') {
                            $tmp = 'jobs.branch';
                        } elseif ($field == 'recruiter') {
                            $tmp = 'users.name';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    }  else {
                        H::ExcelLog($field . ' - ' . $value, 'candidateconversionreport.log');
                        if ($value != '') {
                            if ($field == 'region') {
                                $where[] = 'FIND_IN_SET(jobs.branch, ?) > 0';
                                array_push($paramsArray, $value);
                            } else if ($field == 'lob') {
                                if($value=='CNA'){
                                    $where[] = 'jobs.category like "%(CNA)%"';
                                }else{
                                    $where[] = 'jobs.category not like "%(CNA)%"';
                                }
                            } else if ($field == 'excludeprn') {
                                $where[] = '(
									candidate_applications.isPRN = \'0\' OR
									candidate_applications.isPRN is null
								)';
                            }
                        }
                    }
                }
            }
        }

		$groupBy = " group by reporttype, conversiongroup, candidate_application_states.subSection, Monday ";
        $orderBy = " ORDER by candidate_application_states.sortIndex ";

		$periodQuery = H::getReportPeriodDefinition($report['period'], 'date(candidate_applications.sentDate)', true);
        $select = '
            candidate_application_states.section as reporttype,
            candidate_application_states.application_state as conversiongroup,
			candidate_application_states.subSection,
			candidate_application_states.conversion_id
        ';

        $activeCategories = $this->jobCategoryService->getCategories();

        $categoriesSql = "'" . implode(
                "','",
                array_map('addslashes', $activeCategories->pluck('category')->toArray())
            ) . "'";

		$qry = "
		SELECT ".$select.", ".$periodQuery." as Monday, count(candidate_applications.id) as myCount, 0 as candidate_start_hour_goals
		from  `candidate_applications`
		left join `candidates` on `candidates`.`id` = `candidate_applications`.`candidate_id`
		left join users on users.id = candidates.user_id
		left join jobs on jobs.id  = candidate_applications.job_id
		left join candidate_application_states on candidate_application_states.conversion_id = candidate_applications.conversion_state
        where date(candidate_applications.sentDate) >= ? and date(candidate_applications.sentDate) <= ? and
		jobs.category in ($categoriesSql)
        AND is_trackable
        AND candidate_application_states.conversion_id > 0 " .(!empty($where) ? " AND " . implode(self::ANDCONDITION, $where) : '') . $groupBy . $orderBy;

        H::ExcelLog($qry, 'candidateconversionreport.log');
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
            $id = $record['reporttype'].'|'.$record['conversiongroup'].'|'.$record['subSection'];
            if (!isset($data[$id])) {
                $data[$id] = [
                    'conversiongroup' => $record['conversiongroup'],
                    'reporttype' => $record['reporttype'],
					'subSection' => $record['subSection'],
					'conversion_id' => $record['conversion_id']
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
                //$total1 = (($record[$report['goalField']] > 0) ? (($record['myCount'] / $record[$report['goalField']]) * 100) : 0);
				$total1 = $record['myCount'];
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

		if (request()->view && request()->view == 'percentage') {
            //average the Total columns
			$test = $data;
			$finalCanidateTotals = [];
			foreach ($test as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    if (!in_array($key1, [
                        'conversiongroup',
                        'reporttype',
                        'subSection',
                        'conversion_id',
                        'SixWeekAvg',
                        'ThirteenWeekAvg',
                        'AvgTrend',
                        'Count'
                    ])) {
                        if (!isset($finalCanidateTotals[$key1])) {
                            $finalCanidateTotals[$key1] = $data[$key][$key1];
                        } else {
                            $finalCanidateTotals[$key1] += $data[$key][$key1];
                        }
                    }
                }
            }
			foreach ($test as $key => $value) {
				foreach ($value as $key1 => $value1) {
                    if (isset($finalCanidateTotals[$key1])) {
                        $data[$key][$key1] = ($finalCanidateTotals[$key1] > 0) ?
                        round(($data[$key][$key1] / $finalCanidateTotals[$key1] ) * 100, 0) : 0;
                    }
				}
			}
        }

        /*if (request()->view && request()->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach ($test as $key => $value) {
                if ($value['Count'] != 0) {
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'], 0);
                } else {
                    $data[$key]['Total'] = 0;
                }
            }
        }*/
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.candidateConversionReports', [
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
