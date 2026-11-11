<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Reports;
use Carbon\Carbon;
use Illuminate\Http\Request;

class IpeaSpeedReportController extends Controller
{
    const BACKGROUND1 = 'background-color: #215eac;color:#fff;';
    const BACKGROUND2 = 'background-color: #D5D5D5;color:#000';
    const BACKGROUND3 = 'background-color: #D9D9D9;color:#444;';
    const BACKGROUND4 = 'background-color: #dbf0fd;color:#444444;';
    const NATIONALTOTAL = 'National Totals';
    const ANDCONDITION = ' AND ';
    public function getipeaspeedreportpopup(Request $request)
    {
        $req = $request->all();
        H::ExcelLog(print_r($req, true), 'ipeaspeedreportpopup.log');
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
        if (
			$rowFilter['conversiongroup'] == '% of Candidates Scheduled for IPEA within 24 Business Hours' ||
			$rowFilter['conversiongroup'] == '% of candidates with IPEA within 10 business days of application date'
		) {
            $query = DB::table('recruiting_schedule')
                    ->select(
                        'mssql_recruitertracker.Recruiter',
                        'mssql_recruitertracker.Candidate as Candidate Name',
                        DB::raw("IF(
                            (select date(ca.sentDate) from candidate_applications ca
								where ca.candidate_id = recruiting_schedule.aoc_candidate_id
								and date(ca.sentDate) < recruiting_schedule.created_at
								order by date(ca.sentDate) desc limit 1
							) != '' AND (select date(ca.sentDate) from candidate_applications ca
								where ca.candidate_id = recruiting_schedule.aoc_candidate_id
								and date(ca.sentDate) < recruiting_schedule.created_at
								order by date(ca.sentDate) desc limit 1
							) IS NOT NULL,
                            DATE_FORMAT((select ca.sentDate from candidate_applications ca
								where ca.candidate_id = recruiting_schedule.aoc_candidate_id
								and date(ca.sentDate) < recruiting_schedule.created_at
								order by date(ca.sentDate) desc limit 1
							),'%m/%d/%Y %h:%i %p'),
                            ''
                        ) as `Application Date`"),
                        DB::raw('DATE_FORMAT(recruiting_schedule.created_at, \'%m/%d/%Y %h:%i %p\') as `IPEA Scheduled Date`'),
						DB::raw('CONCAT(FLOOR(recruiting_schedule.ipeaschedulehours),
						\'H \', ROUND((recruiting_schedule.ipeaschedulehours - FLOOR(recruiting_schedule.ipeaschedulehours)) * 60),
						\'M\') as `Business Hours to Schedule IPEA`'),
						DB::raw('CONCAT(DATE_FORMAT(recruiting_schedule.appointment_date, \'%m/%d/%Y\'), \' \', DATE_FORMAT(recruiting_schedule.appointment_time, \'%h:%i %p\')) as `IPEA Date`'),
						DB::raw('floor(recruiting_schedule.ipeadays) as `Business Days to IPEA`'),
						DB::raw('concat(mssql_recruitertracker.id,\'#tracker\') as Action')
                    );
					$query->leftJoin("mssql_recruitertracker", function ($query) {
                        $query->on("mssql_recruitertracker.id", "=", "recruiting_schedule.tracker_id");
                    });
					$query->leftJoin("mssql_facilities", function ($query) {
                        $query->on("mssql_facilities.id", "=", "mssql_recruitertracker.aoc_facility_id");
                    });
					$query->leftJoin("regions", function ($query) {
                        $query->on("regions.id", "=", "mssql_facilities.region");
                    });
                    $query->where('recruiting_schedule.type_id', 1);
					$query->where('recruiting_schedule.outcome', '<>' , 'Cancelled');
					$query->whereRaw('ifnull(recruiting_schedule.aoc_candidate_id,0) <> 0');
					$query->whereRaw('(select date(ca.sentDate) from candidate_applications ca
                            where ca.candidate_id = recruiting_schedule.aoc_candidate_id
                            and date(ca.sentDate) < recruiting_schedule.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) is not null');
					$query->whereRaw('floor(recruiting_schedule.ipeaschedulehours/24) < 30');
                    if (count($rowFilter)) {
                        unset($rowFilter['reporttype']);
						unset($rowFilter['conversiongroup']);
                        $rawFieldMap = [
                            //'conversiongroup' => 'NULLIF(candidates.appWorkFlowState, \'\')',
							'state' => 'mssql_facilities.State',
							'aoc_facility_id' => 'mssql_facilities.id',
							'sourceType' => DB::raw('(select ca.sourceType from candidate_applications ca
													where ca.candidate_id = recruiting_schedule.aoc_candidate_id
													and date(ca.sentDate) < recruiting_schedule.created_at
													order by date(ca.sentDate) desc limit 1
												)'),
							'appSource' => DB::raw('(select ca.source from candidate_applications ca
													where ca.candidate_id = recruiting_schedule.aoc_candidate_id
													and date(ca.sentDate) < recruiting_schedule.created_at
													order by date(ca.sentDate) desc limit 1
												)'),
							'region' => 'regions.region_name'
                        ];
                        $query->where(function ($q) use ($rowFilter, $form, $rawFieldMap) {
                            foreach ($rowFilter as $rfkey => $rf) {
                                $rfkey = isset($rawFieldMap[$rfkey]) ? $rawFieldMap[$rfkey] : $rfkey;
                                if ($rfkey == 'start_of_week') {
                                    if ($form[2]['value'] == 'month') {
                                        $q->whereRaw('MONTH((select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										)) = Month(CONCAT(?,"-01")) AND
                                        YEAR((select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										)) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                    } else if ($form[2]['value'] == 'quarter') {
                                        $q->whereRaw('QUARTER((select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										)) = QUARTER(CONCAT(?,"-01"))', [$rf]);
                                    } else {
                                        $q->whereRaw('(select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										) >= ? AND
                                        (select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										) < (? + INTERVAL 7 - weekday(?) DAY)', [
                                            $rf,
                                            $rf,
                                            $rf
                                        ]);
                                    }
                                } else {
                                    if (!empty($rfkey)) {
                                        if (empty($rf)) {
                                            $q->whereRaw($rfkey . " IS NULL ");
                                        } else {
											if (is_array($rf)) {
												$q->whereIn($rfkey, $rf);
											} else {
												if ($rfkey == 'lob') {
													if ($rf == 'CNA') {
														$q->whereRaw('(
															select jobs.category
															from candidate_applications ca
															left join jobs on ca.job_id = jobs.id
															where ca.candidate_id = recruiting_schedule.aoc_candidate_id
															and date(ca.sentDate) < recruiting_schedule.created_at
															order by date(ca.sentDate) desc limit 1
														) like "%(CNA)%"');
													} else {
														$q->whereRaw('(
															select jobs.category
															from candidate_applications ca
															left join jobs on ca.job_id = jobs.id
															where ca.candidate_id = recruiting_schedule.aoc_candidate_id
															and date(ca.sentDate) < recruiting_schedule.created_at
															order by date(ca.sentDate) desc limit 1
														) not like "%(CNA)%"');
													}
												} else {
													$q->where($rfkey, $rf);
												}
											}
                                        }
                                    }
                                }
                            }
                        });
                    }
        } else {
			$query = DB::table('recruiting_schedule')
                    ->select(
                        'mssql_recruitertracker.Recruiter',
                        'mssql_recruitertracker.Candidate as Candidate Name',
                        DB::raw("IF(
                            (select date(ca.sentDate) from candidate_applications ca
								where ca.candidate_id = recruiting_schedule.aoc_candidate_id
								and date(ca.sentDate) < recruiting_schedule.created_at
								order by date(ca.sentDate) desc limit 1
							) != '' AND (select date(ca.sentDate) from candidate_applications ca
								where ca.candidate_id = recruiting_schedule.aoc_candidate_id
								and date(ca.sentDate) < recruiting_schedule.created_at
								order by date(ca.sentDate) desc limit 1
							) IS NOT NULL,
                            DATE_FORMAT((select ca.sentDate from candidate_applications ca
								where ca.candidate_id = recruiting_schedule.aoc_candidate_id
								and date(ca.sentDate) < recruiting_schedule.created_at
								order by date(ca.sentDate) desc limit 1
							),'%m/%d/%Y %h:%i %p'),
                            ''
                        ) as `Application Date`"),
                        DB::raw('DATE_FORMAT(recruiting_schedule.created_at, \'%m/%d/%Y %h:%i %p\') as `IPEA Scheduled Date`'),
						DB::raw('CONCAT(FLOOR(recruiting_schedule.ipeaschedulehours),
						\'H \', ROUND((recruiting_schedule.ipeaschedulehours - FLOOR(recruiting_schedule.ipeaschedulehours)) * 60),
						\'M\') as `Business Hours to Schedule IPEA`'),
						DB::raw('CONCAT(DATE_FORMAT(recruiting_schedule.appointment_date, \'%m/%d/%Y\'), \' \', DATE_FORMAT(recruiting_schedule.appointment_time, \'%h:%i %p\')) as `IPEA Date`'),
						DB::raw('floor(recruiting_schedule.ipeadays) as `Business Days to IPEA`'),
						DB::raw('concat(mssql_recruitertracker.id,\'#tracker\') as Action')
                    );
					$query->leftJoin("mssql_recruitertracker", function ($query) {
                        $query->on("mssql_recruitertracker.id", "=", "recruiting_schedule.tracker_id");
                    });
					$query->leftJoin("mssql_facilities", function ($query) {
                        $query->on("mssql_facilities.id", "=", "mssql_recruitertracker.aoc_facility_id");
                    });
					$query->leftJoin("regions", function ($query) {
                        $query->on("regions.id", "=", "mssql_facilities.region");
                    });
                    $query->where('recruiting_schedule.type_id', 1);
					$query->where('recruiting_schedule.outcome', '<>' , 'Cancelled');
					$query->whereRaw('ifnull(recruiting_schedule.aoc_candidate_id,0) <> 0');
					$query->whereRaw('(select date(ca.sentDate) from candidate_applications ca
                            where ca.candidate_id = recruiting_schedule.aoc_candidate_id
                            and date(ca.sentDate) < recruiting_schedule.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) is not null');
					$query->whereRaw('floor(recruiting_schedule.ipeaschedulehours/24) < 30');
					/*if ($rowFilter['conversiongroup'] == '% of Candidates Scheduled for IPEA within 24 Business Hours') {
						$query->whereRaw('recruiting_schedule.ipeaschedulehours < 24');
					} else {
						$query->whereRaw('recruiting_schedule.ipeadays <= 10');
					}*/
                    if (count($rowFilter)) {
                        unset($rowFilter['reporttype']);
						unset($rowFilter['conversiongroup']);
                        $rawFieldMap = [
                            //'conversiongroup' => 'NULLIF(candidates.appWorkFlowState, \'\')',
							'state' => 'mssql_facilities.State',
							'aoc_facility_id' => 'mssql_facilities.id',
							'sourceType' => DB::raw('(select ca.sourceType from candidate_applications ca
													where ca.candidate_id = recruiting_schedule.aoc_candidate_id
													and date(ca.sentDate) < recruiting_schedule.created_at
													order by date(ca.sentDate) desc limit 1
												)'),
							'appSource' => DB::raw('(select ca.source from candidate_applications ca
													where ca.candidate_id = recruiting_schedule.aoc_candidate_id
													and date(ca.sentDate) < recruiting_schedule.created_at
													order by date(ca.sentDate) desc limit 1
												)'),
							'region' => 'regions.region_name'
                        ];
                        $query->where(function ($q) use ($rowFilter, $form, $rawFieldMap) {
                            foreach ($rowFilter as $rfkey => $rf) {
                                $rfkey = isset($rawFieldMap[$rfkey]) ? $rawFieldMap[$rfkey] : $rfkey;
                                if ($rfkey == 'start_of_week') {
                                    if ($form[2]['value'] == 'month') {
                                        $q->whereRaw('MONTH((select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										)) = Month(CONCAT(?,"-01")) AND
                                        YEAR((select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										)) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                    } else if ($form[2]['value'] == 'quarter') {
                                        $q->whereRaw('QUARTER((select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										)) = QUARTER(CONCAT(?,"-01"))', [$rf]);
                                    } else {
                                        $q->whereRaw('(select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										) >= ? AND
                                        (select date(ca.sentDate) from candidate_applications ca
											where ca.candidate_id = recruiting_schedule.aoc_candidate_id
											and date(ca.sentDate) < recruiting_schedule.created_at
											order by date(ca.sentDate) desc limit 1
										) < (? + INTERVAL 7 - weekday(?) DAY)', [
                                            $rf,
                                            $rf,
                                            $rf
                                        ]);
                                    }
                                } else {
                                    if (!empty($rfkey)) {
                                        if (empty($rf)) {
                                            $q->whereRaw($rfkey . " IS NULL ");
                                        } else {
											if (is_array($rf)) {
												$q->whereIn($rfkey, $rf);
											} else {
												if ($rfkey == 'lob') {
													if ($rf == 'CNA') {
														$q->whereRaw('(
															select jobs.category
															from candidate_applications ca
															left join jobs on ca.job_id = jobs.id
															where ca.candidate_id = recruiting_schedule.aoc_candidate_id
															and date(ca.sentDate) < recruiting_schedule.created_at
															order by date(ca.sentDate) desc limit 1
														) like "%(CNA)%"');
													} else {
														$q->whereRaw('(
															select jobs.category
															from candidate_applications ca
															left join jobs on ca.job_id = jobs.id
															where ca.candidate_id = recruiting_schedule.aoc_candidate_id
															and date(ca.sentDate) < recruiting_schedule.created_at
															order by date(ca.sentDate) desc limit 1
														) not like "%(CNA)%"');
													}
												} else {
													$q->where($rfkey, $rf);
												}
											}
                                        }
                                    }
                                }
                            }
                        });
                    }
		}
        H::ExcelLog(print_r($query->toSql(), true), 'ipeaspeedreportpopup.log');
        if ($query) {
            $result = $query->get()->toArray();
        }

        return view('reports.popup', ['data' => $request->all(), "results" => count($result) > 0 ? $result : [], 'sortColumn' => true]);
    }

    public function ipeaSpeed()
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
		
		if (!isset(request()->view)) {
            request()->merge(['view' => 'percentage']);
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'IPEA SPEED';
        $report['description'] = 'This report tracks the time it takes to schedule candidates for an IPEA and the time it takes
                                for them to attend their IPEA appointment. Our goal is to schedule nurses for an IPEA within 24
                                business hours and ensure they attend the appointment within 10 business days.';
        $report['id'] = 'ipeaSpeed';
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
        ];
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('period:' . $report['period'],'ipeaspeedreport.log');
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
        H::ExcelLog("selectedDatesDiffInWeek==" . $selectedDatesDiffInWeek, 'ipeaspeedreport.log');
        H::ExcelLog("showAvgColumn==" . $showAvgColumn, 'ipeaspeedreport.log');

        foreach ($periods as $period) {
            $clickedFilters = [];
				$clickedFilters = [
					'reporttype',
					'conversiongroup'
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
                'field' => 'reporttype',
                'showCols' => 'reporttype',
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
                'style' => self::BACKGROUND2 . '; display:none;',
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
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'], 'ipeaspeedreport.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2', 'ipeaspeedreport.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        $regions = H::getHelper("ConstantHelper")->getRegion();

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
        
        $sourceFilters = [];


        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'ipeaspeedreport.log');
                        $tmp = $field;
                        if ($field == 'state') {
                            $tmp = "State";
                        } elseif ($field == 'aoc_facility_id') {
                            $tmp = 'id';
                        } elseif ($field == 'sourceType') {
                            $tmp = 'sourceType';
                        } elseif ($field == 'appSource') {
                            $tmp = 'source';
                        } elseif ($field == 'recruiter') {
                            $tmp = 'Recruiter';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        if ($value!='') {
                            if ($field == 'lob') {
                                if ($value == 'CNA') {
                                    $where[] = 'category like "%(CNA)%"';
                                } else {
                                    $where[] = 'category not like "%(CNA)%"';
                                }
                            } elseif($field == 'region'){
                                $where[] = "region_name = ?";
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

        $condition = '';
        $condition2 = '';
		$periodQuery = H::getReportPeriodDefinition($report['period'], '(select date(ca.sentDate) from candidate_applications ca
						where ca.candidate_id = rs.aoc_candidate_id
						and date(ca.sentDate) < rs.created_at
						order by date(ca.sentDate) desc limit 1
					)', true);
        if (request()->view && request()->view == 'avg') {
			$condition = "floor(rs.ipeaschedulehours/24) count2,
                            'Days to Schedule IPEA' AS conversiongroup";
            $condition2 = "rs.ipeadays count2,
                        'Days to IPEA Date' AS conversiongroup";
        } else {
            $condition = "CASE 
                                WHEN rs.ipeaschedulehours < 24 
                                THEN 1 
                                ELSE 0 
                            END AS count2,
                            '% of Candidates Scheduled for IPEA within 24 Business Hours' AS conversiongroup";
            $condition2 = "CASE 
                                WHEN rs.ipeadays <= 10 
                                THEN 1 
                                ELSE 0 
                            END AS count2,
                            '% of candidates with IPEA within 10 business days of application date' AS conversiongroup";
        }
        $qry = "SELECT 
                        'IPEA' as reporttype,
                        Monday,
                        conversiongroup,
                        COUNT(aoc_candidate_id) AS myCount,
                        SUM(count2) AS candidate_start_hour_goals,
                        sourceType,
                        source,
                        State,
                        id,
                        FacilityName,
                        category,
						Recruiter,
						region_name
                    FROM (
                        SELECT 
                            ".$periodQuery." AS Monday,
                            ".$condition.",
							1 as sortorder,
                            rs.aoc_candidate_id,
                        (select ca.sourceType from candidate_applications ca
                            where ca.candidate_id = rs.aoc_candidate_id
                            and date(ca.sentDate) < rs.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) as sourceType,
                        (select ca.source from candidate_applications ca
                            where ca.candidate_id = rs.aoc_candidate_id
                            and date(ca.sentDate) < rs.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) as source,
                        mf.State,
                        mf.id,
                        mf.FacilityName,
						reg.region_name,
                        (
                            select jobs.category
                            from candidate_applications ca
                            left join jobs on ca.job_id = jobs.id
                            where ca.candidate_id = rs.aoc_candidate_id
                            and date(ca.sentDate) < rs.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) as category,
						rt.Recruiter
                        FROM recruiting_schedule rs
                        left join mssql_recruitertracker rt on rt.id = rs.tracker_id
                        left join mssql_facilities mf on mf.id = rt.aoc_facility_id
						left join regions reg on mf.region = reg.id
                        WHERE rs.type_id = 1
                        AND (select date(ca.sentDate) from candidate_applications ca
                                where ca.candidate_id = rs.aoc_candidate_id
                                and date(ca.sentDate) < rs.created_at
                                order by date(ca.sentDate) desc limit 1
                            ) >= ? and (select date(ca.sentDate) from candidate_applications ca
                                where ca.candidate_id = rs.aoc_candidate_id
                                and date(ca.sentDate) < rs.created_at
                                order by date(ca.sentDate) desc limit 1
                            ) <= ?
                        ANd rs.outcome <> 'Cancelled'
                        AND ifnull(rs.aoc_candidate_id,0) <> 0
                        AND (select date(ca.sentDate) from candidate_applications ca
                            where ca.candidate_id = rs.aoc_candidate_id
                            and date(ca.sentDate) < rs.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) is not null
                        AND floor(rs.ipeaschedulehours/24) < 30
                        GROUP BY 
                            Monday, rs.aoc_candidate_id

                        UNION ALL

                        SELECT 
                            ".$periodQuery." AS Monday,
                            ".$condition2.",
							2 as sortorder,
                            rs.aoc_candidate_id,
                        (select ca.sourceType from candidate_applications ca
                            where ca.candidate_id = rs.aoc_candidate_id
                            and date(ca.sentDate) < rs.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) as sourceType,
                        (select ca.source from candidate_applications ca
                            where ca.candidate_id = rs.aoc_candidate_id
                            and date(ca.sentDate) < rs.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) as source,
                        mf.State,
                        mf.id,
                        mf.FacilityName,
						reg.region_name,
                        (
                            select jobs.category
                            from candidate_applications ca
                            left join jobs on ca.job_id = jobs.id
                            where ca.candidate_id = rs.aoc_candidate_id
                            and date(ca.sentDate) < rs.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) as category,
						rt.Recruiter
                        FROM recruiting_schedule rs
                        left join mssql_recruitertracker rt on rt.id = rs.tracker_id
                        left join mssql_facilities mf on mf.id = rt.aoc_facility_id
						left join regions reg on mf.region = reg.id
                        WHERE rs.type_id = 1
                        AND (select date(ca.sentDate) from candidate_applications ca
                                where ca.candidate_id = rs.aoc_candidate_id
                                and date(ca.sentDate) < rs.created_at
                                order by date(ca.sentDate) desc limit 1
                            ) >= ? and (select date(ca.sentDate) from candidate_applications ca
                                where ca.candidate_id = rs.aoc_candidate_id
                                and date(ca.sentDate) < rs.created_at
                                order by date(ca.sentDate) desc limit 1
                            ) <= ?
                        ANd rs.outcome <> 'Cancelled'
                        AND ifnull(rs.aoc_candidate_id,0) <> 0
                        AND (select date(ca.sentDate) from candidate_applications ca
                            where ca.candidate_id = rs.aoc_candidate_id
                            and date(ca.sentDate) < rs.created_at
                            order by date(ca.sentDate) desc limit 1
                        ) is not null
                            AND floor(rs.ipeaschedulehours/24) < 30
                        GROUP BY 
                            Monday, rs.aoc_candidate_id
                    ) AS subquery
                    ". (count($where) > 0 ? " WHERE " . implode(" AND " , $where) : '') . "
                    GROUP BY 
                        sortorder, Monday, conversiongroup
                    ORDER BY 
                        sortorder, Monday, conversiongroup";

        H::ExcelLog($qry, 'ipeaspeedreport.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate,
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
            $id = $record['conversiongroup'].$record['reporttype'];
            if (!isset($data[$id])) {
                $data[$id] = [
                    'conversiongroup' => $record['conversiongroup'],
                    'reporttype' => $record['reporttype']
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
                $total1 = (($record[$report['goalField']] > 0) ? (($record[$report['goalField']] / $record['myCount']) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if (floatval($data[$id]['Total']) != 0) {
                    $data[$id]['Count']++;
                }
            } else {
                $data[$id][$record['Monday']] = ($record[$report['goalField']] / $record['myCount']);
                $data[$id]['Total'] = $data[$id]['Total'] + ($record[$report['goalField']] / $record['myCount']);
            }
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

        return view('reports.ipeaSpeedReports', [
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
