<?php

namespace App\Http\Controllers;

use App\Enums\NRR\NRRTypes;
use App\Helpers\H;
use App\Helpers\LD;
use App\Http\Requests\NRR\NationalRecruitingReportRequest;
use App\MssqlFacilities;
use App\Services\JobCategory\JobCategoryService;
use App\Services\Reports\ReportLobFilterService;
use App\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class NationalRecruitingReportController extends Controller
{
    private const LOB = 'lob';
    private const STATE = 'state';
    private const BRANCH = 'aoc_facility_id';
    private const RECRUITER = 'Recruiter';
    private const SOURCE_TYPE = 'sourceType';
    private const APP_SOURCE = 'appSource';
    private const SOURCE = 'source';
    private const CNA = 'CNA';
    private const FULL_CNA = 'Certified Nursing Aide';

    public function __construct(
        private JobCategoryService $jobCategoryService,
        private ReportLobFilterService $lobFilterService
    )
    {}

    public function getNRRPopup(Request $request)
    {
        $req = $request->all();
        $rowFilter = json_decode($req['rowfilter'], true);
        $form = $req['form'];
        $filterReq = [];
        $typesArr = ['ipea', 'pepi', 'wepi', 'cm', 'hours_removed', 'candidate_starts', 'gross_margin'];

        foreach($form as $key => $value) {
            if (empty($value['value']) || substr_count($value['name'], "date") || substr_count($value['name'], "excludeprn"))
                continue;
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
                if (in_array($value['name'], ['_token', 'isajax', 'period', 'source', 'lob', 'view'])) {
                    continue;
                }
                $rowFilter[$key] = $value['value'];
            }
        }
        $result = [];
        $query = null;

        if(isset($req['type'])){
            if($req['type']=='hoursOnNTH') {
                $query = DB::table("NeedToHire_Schedules")->select("mssql_patients.PatientName as Client","mssql_patients.PatientAccount as ClientID","NeedToHire_Accounts.ServicesNeeded as Services Needed",
                            "mssql_facilities.State","mssql_facilities.FacilityName as Branch","NeedToHire_Schedules.ShiftHours as NTH Hours")
                    ->leftJoin("NeedToHire_Accounts","NeedToHire_Schedules.aoc_nthaccounts_id",'=',"NeedToHire_Accounts.id");
                $query->leftJoin("mssql_patients",function($query){
                    $query->on("mssql_patients.id", "=", "NeedToHire_Accounts.aoc_patient_id");
                });

                $query->leftJoin("mssql_facilities",function($query){
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });

                if (count($rowFilter)) {

                    $fieldmap = [
                        'FacilityName' => 'mssql_facilities.FacilityName',
                        'State' => 'mssql_facilities.State',
                        'Intake' => 'mssql_patients.is_intake',
                        'ServicesNeeded' => 'NeedToHire_Accounts.ServicesNeeded',
                        'aoc_facility_id' => 'mssql_facilities.id',
                        'state' => 'mssql_facilities.state',
                    ];

                    $query->where(function ($q) use ($rowFilter, $fieldmap) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if (is_array($rf)) {
                                $q->whereIn($rfkey, $rf);
                            } else {
                                $q->where($rfkey, "=", $rf);
                            }
                        }
                    });
                }
            }

            if($req['type']=='candidates') {
                $periodVal = $form[2]['value'] ?? null;
                $startOfWeek = null;
                if (isset($rowFilter['start_of_week'])) {
                    $startOfWeek = $rowFilter['start_of_week'];
                    unset($rowFilter['start_of_week']);
                }

                if (isset($rowFilter['branch'])) {
                    $rowFilter['branch'] = array_map(function ($item) {
                        $parts = explode(' - ', $item);
                        return trim(end($parts));
                    }, (array)$rowFilter['branch']);
                }

                $dedup = DB::table('candidate_applications as ca2')
                    ->selectRaw("MIN(ca2.id) as appId")
                    ->selectRaw("DATE_FORMAT(DATE_ADD(ca2.application_date, INTERVAL -WEEKDAY(ca2.application_date) DAY),'%Y-%m-%d') as Monday")
                    ->addSelect('ca2.candidate_id')
                    ->groupBy(
                        'ca2.candidate_id',
                        DB::raw("DATE_FORMAT(DATE_ADD(ca2.application_date, INTERVAL -WEEKDAY(ca2.application_date) DAY),'%Y-%m-%d')")
                    );

                if ($startOfWeek) {
                    if ($periodVal == 'month') {
                        $dedup->whereRaw("DATE_FORMAT(ca2.application_date, '%Y-%m') = ?", [$startOfWeek]);
                    } else {
                        $dedup->whereRaw(
                            'ca2.application_date >= ? AND ca2.application_date < (? + INTERVAL 7 - WEEKDAY(?) DAY)',
                            [$startOfWeek, $startOfWeek, $startOfWeek]
                        );
                    }
                }

                $query = DB::table('candidate_applications as ca')
                    ->joinSub($dedup, 'cte', function ($join) {
                        $join->on('ca.id', '=', 'cte.appId');
                    })
                    ->leftJoin('candidates as c', 'ca.candidate_id', '=', 'c.id')
                    ->leftJoin('jobs as j', 'ca.job_id', '=', 'j.id')
                    ->select(
                        "j.category as Job Category",
                        "j.title as Title",
                        "c.email as Email",
                        "c.fullName as Candidate",
                        "ca.sourceType as Source Type",
                        "ca.source as Source"
                    );

                if (count($rowFilter)) {
                    $fieldmap = [
                        'state'      => 'j.state',
                        'category'   => 'j.category',
                        'sourceType' => 'ca.sourceType',
                        'appSource'  => 'ca.source',
                        'source'     => 'ca.source',
                        'recruiter'  => 'c.user_id',
                        'lob'        => 'j.category',
                        'branch'     => 'j.branch',
                    ];

                    $allowedRowFilterKeys = [
                        'state',
                        'category',
                        'sourceType',
                        'source',
                        'recruiter',
                        'start_of_week',
                    ];

                    $rowFilter = array_intersect_key($rowFilter, array_flip($allowedRowFilterKeys));

                    $query->where(function ($q) use ($rowFilter, $form, $fieldmap, $periodVal) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = str_replace("org", "", $rfkey);
                            $rfkey = $fieldmap[$rfkey] ?? $rfkey;

                            if ($rfkey == 'j.category') {
                                if ($rf == self::CNA || $rf == self::FULL_CNA) {
                                    $q->whereRaw('j.category like "%(CNA)%"');
                                } else {
                                    $q->whereRaw('j.category not like "%(CNA)%"');
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
            }

            if (in_array($req['type'], $typesArr)) {
                $fieldmap = [
                    'whiteboard_row' => 'mssql_recruitertracker.whiteboard_row',
                    'user_id' => 'mssql_recruitertracker.user_id',
                    'Recruiter' => 'mssql_recruitertracker.Recruiter',
                    'territory' => 'whiteboard.territory',
                    'hire_date' => 'whiteboard.hire_date',
                    'pepi_wepi_goal' => 'whiteboard.pepi_wepi_goal',
                    'client_match_goal' => 'whiteboard.client_match_goal',
                    'type_id' => 'recruiting_schedule.type_id',
                    'state' => DB::raw('left(mssql_recruitertracker.whiteboard_row,2)'),
                    'Branch_Location' => 'mssql_facilities.FacilityName',
                    'aoc_facility_id' => 'mssql_recruitertracker.aoc_facility_id',
                    'candidate_start_hour_goals' => 'whiteboard.candidate_start_hour_goals',
                    'region' => 'region_name'
                ];

                if ($form[3]['value'] === NRRTypes::APPROVER->value) {
                    $fieldmap['approver'] = 'mssql_recruitertracker.Approved_By';
                    unset($fieldmap['Recruiter']);
                }

                if (LD::checkFeatureFlag('fixed-payer-nrr-tabs')) {
                    $fieldmap['payer_group'] = DB::raw('(select payergroup_name from payment_source where name =
                                    mssql_recruitertracker.Payor_Source order by id DESC limit 1)');
                }

                $dateFieldArr = [
                    'ipea' => ['IPEA Date', 'IPEA_Date'],
                    'pepi' => ['PEPI Date', 'PEPI_Date'],
                    'wepi' => ['WEPI Date', 'WEPI_Date'],
                    'cm' => ['Client Match Date', 'Client_Match_Date'],
                    'candidate_starts' => ['Candidate Start Date', 'Candidate_Start_Date'],
                    'gross_margin' => ['Candidate Start Date', 'Candidate_Start_Date']
                ];

                $dateField = 'Candidate_Start_Date';
                $dateLabel = 'Candidate Start Date';

                if (array_key_exists($req['type'], $dateFieldArr)) {
                    $dateField = $dateFieldArr[$req['type']][1];
                    $dateLabel = $dateFieldArr[$req['type']][0];
                }

                if($req['type'] == 'gross_margin') {
                    if (LD::checkFeatureFlag('fixed-payer-nrr-tabs')) {
                        $query = DB::table("recruiting_schedule")->select(
                            "mssql_facilities.FacilityName as Branch",
                            "mssql_recruitertracker.Candidate",
                            "mssql_providers.DisciplineName as Discipline",
                            "mssql_recruitertracker.Client_Name as Client",
                            "mssql_recruitertracker.Payor_Source as Payer",
                            DB::raw("(select payergroup_name from payment_source where name =
                            mssql_recruitertracker.Payor_Source order by id DESC limit 1) as `Special Payer Group`"),
                            DB::raw("mssql_recruitertracker.Agreed_Pay_Rate as `Pay Rate`"),
                            "mssql_recruitertracker.Total_Hours as Total Hours",
                            DB::raw("CONCAT(CEIL((mssql_recruitertracker.GP * 100)), '%') as `Gross Margin`"),
                            'mssql_recruitertracker.Approved_By as Approved By',
                            DB::raw("IF(mssql_recruitertracker.".$dateField." != '' AND mssql_recruitertracker.".$dateField.
                            " IS NOT NULL, DATE_FORMAT(mssql_recruitertracker.".$dateField.",'%m/%d/%Y'), '') as `".$dateLabel."`"),
                            "mssql_recruitertracker.Recruiter",
                            "whiteboard.territory as Territory"
                        );
                    } else {
                        $query = DB::table("recruiting_schedule")->select(
                            "mssql_facilities.FacilityName as Branch",
                            "mssql_recruitertracker.Candidate",
                            "mssql_providers.DisciplineName as Discipline",
                            "mssql_recruitertracker.Client_Name as Client",
                            "mssql_patients.Payer",
                            "mssql_patients.payer_group as Special Payer Group",
                            DB::raw("mssql_recruitertracker.Agreed_Pay_Rate as `Pay Rate`"),
                            DB::raw("CONCAT(CEIL((mssql_recruitertracker.GP * 100)), '%') as `Gross Margin`"),
                            'mssql_recruitertracker.Approved_By as Approved By',
                            DB::raw("IF(mssql_recruitertracker.".$dateField." != '' AND mssql_recruitertracker.".$dateField.
                            " IS NOT NULL, DATE_FORMAT(mssql_recruitertracker.".$dateField.",'%m/%d/%Y'), '') as `".$dateLabel."`"),
                            "mssql_recruitertracker.Recruiter",
                            "whiteboard.territory as Territory"
                        );
                    }
                } else {
                    if (
                        ($req['type'] == 'hours_removed' || $req['type'] == 'candidate_starts')
                    ) {
                        $query = DB::table("recruiting_schedule")->select(
                            "mssql_facilities.FacilityName as Branch",
                            "mssql_recruitertracker.Candidate",
                            "mssql_recruitertracker.Client_Name as Client",
                            (
                                LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "mssql_recruitertracker.Payor_Source as Payer" : "mssql_patients.Payer"
                            ),
                            (
                                LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                DB::raw("(select payergroup_name from payment_source where name =
                                mssql_recruitertracker.Payor_Source order by id DESC limit 1) as `Special Payer Group`") :
                                "mssql_patients.payer_group as Special Payer Group"
                            ),
                            "mssql_recruitertracker.Total_Hours as Total Hours",
                            DB::raw("mssql_recruitertracker.Agreed_Pay_Rate as `Pay Rate`"),
                            DB::raw("CONCAT(CEIL((mssql_recruitertracker.GP * 100)), '%') as `Gross Margin`"),
                            DB::raw("IF(mssql_recruitertracker.".$dateField." != '' AND mssql_recruitertracker.".$dateField.
                            " IS NOT NULL, DATE_FORMAT(mssql_recruitertracker.".$dateField.",'%m/%d/%Y'), '') as `".$dateLabel."`"),
                            "mssql_recruitertracker.Recruiter",
                            "whiteboard.territory as Territory"
                        );
                    } else {
                        $query = DB::table("recruiting_schedule")->select(
                            "mssql_facilities.FacilityName as Branch",
                            "mssql_recruitertracker.Candidate",
                            "mssql_recruitertracker.Client_Name as Client",
                            (
                                LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "mssql_recruitertracker.Payor_Source as Payer" : "mssql_patients.Payer"
                            ),
                            (
                                LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                DB::raw("(select payergroup_name from payment_source where name =
                                mssql_recruitertracker.Payor_Source order by id DESC limit 1) as `Special Payer Group`") :
                                "mssql_patients.payer_group as Special Payer Group"
                            ),
                            DB::raw("mssql_recruitertracker.Agreed_Pay_Rate as `Pay Rate`"),
                            "mssql_recruitertracker.Total_Hours as Total Hours",
                            DB::raw("CONCAT(CEIL((mssql_recruitertracker.GP * 100)), '%') as `Gross Margin`"),
                            DB::raw("IF(mssql_recruitertracker.".$dateField." != '' AND mssql_recruitertracker.".$dateField.
                            " IS NOT NULL, DATE_FORMAT(mssql_recruitertracker.".$dateField.",'%m/%d/%Y'), '') as `".$dateLabel."`"),
                            "mssql_recruitertracker.Recruiter",
                            "whiteboard.territory as Territory"
                        );
                    }
                }

                $query->leftJoin("mssql_recruitertracker", function ($query) {
                    $query->on("recruiting_schedule.tracker_id", "=", "mssql_recruitertracker.id");
                });

                $query->leftJoin("users", function ($query) {
                    $query->on("mssql_recruitertracker.user_id", "=", "users.id");
                });

                $query->leftJoin("mssql_patients", "recruiting_schedule.aoc_patient_id", "=", "mssql_patients.id");
                $query->leftJoin("mssql_providers", "mssql_recruitertracker.aoc_provider_id", "=", "mssql_providers.id");
                $query->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
                });

                $query->leftJoin("whiteboard", function ($query) {
                    $query->on("mssql_recruitertracker.whiteboard_row", "=", "whiteboard.row_id");
                    $query->on("mssql_recruitertracker.user_id", "=", "whiteboard.user_id");
                });

                $query->leftJoin("regions", "whiteboard.region", "=", "regions.id");
                $query->whereRaw('ifnull(mssql_providers.is_deleted,0)=0');
                $query->where("recruiting_schedule.is_deleted", "<>", "1");

                if ($req['type'] == 'candidate_starts') {
					if (LD::checkFeatureFlag('fixed-nrr-candidate-start-tab-prn-client-issue')) {
                        if (LD::checkFeatureFlag('include-client-not-in-kantime-in-NRR-candidate-start-report')) {
                            $query->whereRaw('(
                                    (mssql_patients.isPRN <> 1 AND recruiting_schedule.has_billable_visit = 1 and mssql_patients.not_in_kantime = 0) OR
                                    mssql_patients.isPRN = 1 OR mssql_patients.not_in_kantime = 1
                                )');
                        } else {
                            $query->whereRaw("(
                            (
                                mssql_patients.isPRN <> 1 AND isnull(
                                (
                                    select sm.ScheduleDate from mssql_schedulemaster sm where sm.ProviderID = mssql_providers.ProviderID and
                                    sm.PatientID = mssql_patients.PatientID and sm.ScheduleStatusID in (1,2,3) and sm.ScheduleDate = recruiting_schedule.appointment_date limit 1)
                                ) = false
                            ) OR mssql_patients.isPRN = 1)");
                        }
					} else {
                        if (LD::checkFeatureFlag('include-client-not-in-kantime-in-NRR-candidate-start-report')) {
                            $query->whereRaw('recruiting_schedule.has_billable_visit = 1');
                        } else {
						    $query->whereRaw("
                                isnull(
                                (
                                    select sm.ScheduleDate from mssql_schedulemaster sm where sm.ProviderID = mssql_providers.ProviderID and
							        sm.PatientID = mssql_patients.PatientID and sm.ScheduleStatusID in (1,2,3) and sm.ScheduleDate = recruiting_schedule.appointment_date limit 1
                                )) = false"
                            );
                        }
					}

                    $query->where('mssql_recruitertracker.Candidate_Current_Status', '<>', 'Inactive');
                }

                if (LD::checkFeatureFlag('fixed-payer-nrr-tabs')) {
                    if ($req['type'] == 'gross_margin') {
                        $query->where('mssql_recruitertracker.GP', '<>', 0);
                    }
                }

                if ($req['type'] === 'gross_margin') {
                    $query->groupBy('mssql_recruitertracker.id');
                }

                $allowedRowFilterKeys = [
                    'whiteboard_row',
                    'user_id',
                    'Recruiter',
                    'territory',
                    'start_of_week',
                    'state',
                    'region_name',
                    'type_id'
                ];

                $rowFilter = array_intersect_key($rowFilter, array_flip($allowedRowFilterKeys));

                if (count($rowFilter)) {
                    $query->where(function ($q) use ($rowFilter, $form, $fieldmap, $req) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;

                            if ($rfkey == 'start_of_week') {
                                if ($form[2]['value'] == 'month') {
                                    if ($req['type'] == 'candidate_starts' || $req['type'] == 'gross_margin') {
                                        $q->whereRaw('MONTH(recruiting_schedule.appointment_date) = Month(CONCAT(?,"-01")) AND
                                        YEAR(recruiting_schedule.appointment_date) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                    } else {
                                        $q->whereRaw('MONTH(recruiting_schedule.scheduled_date) = Month(CONCAT(?,"-01")) AND
                                        YEAR(recruiting_schedule.scheduled_date) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                    }
                                } else {
                                    if ($req['type'] == 'candidate_starts' || $req['type'] == 'gross_margin') {
                                        $q->whereRaw('recruiting_schedule.appointment_date >= ? AND
                                        recruiting_schedule.appointment_date < (? + INTERVAL 7 - weekday(?) DAY)', [
                                            $rf,
                                            $rf,
                                            $rf
                                        ]);
                                    } else {
                                        $q->whereRaw('recruiting_schedule.scheduled_date >= ? AND
                                        recruiting_schedule.scheduled_date < (? + INTERVAL 7 - weekday(?) DAY)', [
                                            $rf,
                                            $rf,
                                            $rf
                                        ]);
                                    }
                                }
                            } elseif ($rfkey == 'mssql_recruitertracker.Recruiter' && $rf == "Term'd") {
                                //don't add recruiter to where clause
                                $q->whereNull('whiteboard.user_id');
                            } elseif ($rfkey == 'region_name' || $rfkey == 'region') {
                                $q->where('regions.' . $rfkey, $rf);
                            } else {
                                if (is_array($rf)) {
                                    $q->whereIn($rfkey, $rf);
                                } else {
                                    if (urldecode($rf) == 'Term\'d') {
                                        //$q->whereNull($rfkey);
                                    } else {
                                        if ($rfkey === 'recruiting_schedule.type_id') {
                                            $q->whereIn($rfkey, [$rf]);
                                        } else {
                                            $q->where($rfkey, '=', $rf);
                                        }
                                    }
                                }
                            }
                        }
                    });
                }
            }

            if($query) {
                $result = $query->get()->toArray();
            }
        }

        return view('reports.popup', [
            'data' => $request->all(),
            'results' => count($result) > 0 ? $result : []
        ]);
    }

    public function nthHours()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'branch';
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
        $report['title'] = 'NTH Hours';
        $report['id'] = 'nthhours';
        $report['period'] = $period;
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
        ];
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            $periods = H::getReportPeriodColumns($report['period'], ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate), request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        foreach($periods as $period){
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
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
        $report['groupby'] = [
            ['id' => 1, 'label' => '@group1', 'field' => 'State', 'showCols' => 'State', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 1],
            ['id' => 2, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 2],
            ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
            ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
            ['id' => 9, 'label' => 'National Totals', 'field' => '', 'showCols' => '', 'style' => 'background-color: #D5D5D5;color:#000', 'level' => 5],
        ];
        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = H::getReportEndingDate($report['period'], request()->date['to']);
        } else {
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        //now define filters
        $state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
        $branches  = [];
        $filters = [
            [
                'label' => 'Is Intake?',
                'name' => 'is_intake',
                'type' => 'select',
                'source' => [["value" => '0', 'text' => "No"], ["value" => '1', 'text' => "Yes"]]
            ],
            [
                'label' => 'Service Needed',
                'name' => 'ServicesNeeded',
                'type' => 'multiselect',
                'source' => [["value" => 'PDN', 'text' => "PDN"], ["value" => 'CNA', 'text' => "CNA"], ["value" => 'Respite', 'text' => "Respite"],
                            ["value" => 'PDN/Respite', 'text' => "PDN/Respite"], ["value" => 'Skilled Nursing', 'text' => "Skilled Nursing"], ["value" => 'IHSS', 'text' => "IHSS"]]
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
                'label' => 'Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ]
        ];

        $sourceFilters = [
            [
                'label' => 'By Branch',
                'value' => 'branch'
            ]
        ];

        //define dataset
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

        $periodQuery = H::getReportPeriodDefinition($report['period'],'NeedToHire_Historical.nth_date');
        //now get report data
        if($source=='branch'){
            $qry = "SELECT  ifnull(mssql_facilities.State,'No EMR Match') as State, ifnull(mssql_facilities.id,21) as id, ifnull(mssql_facilities.FacilityName,'No EMR Match') as FacilityName,
                            " . $periodQuery . " as Monday,
            sum(NeedToHire_Historical.ShiftHours) as myCount
           FROM `NeedToHire_Historical`
           left join NeedToHire_Accounts on NeedToHire_Accounts.id = NeedToHire_Historical.aoc_nthaccounts_id
           left join mssql_patients on mssql_patients.id = NeedToHire_Accounts.aoc_patient_id
           left join mssql_facilities on mssql_facilities.id = mssql_patients.aoc_facility_id
           where NeedToHire_Historical.nth_date >= ? and NeedToHire_Historical.nth_date <= ?
           and NeedToHire_Accounts.IsActive = 1 and NeedToHire_Historical.IsActive = 1 ".
           (count($where) > 0 ? " AND " . implode(" AND " , $where) : '')."
           group by State, FacilityName, " . $periodQuery . "
           order by State, FacilityName, " . $periodQuery;
        }else{
            $qry = '';
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
                $id = $record['State'] . '|' . $record['FacilityName'];
                if(!isset($data[$id])){
                    $data[$id] = ['State' => $record['State'],
                        'FacilityName' => $record['FacilityName']
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['Total'] = 0;
                }
                $data[$id][$record['Monday']] = $record['myCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.recruitingReports', ['report' => $report, 'data' => $data, 'filters' => $filters, 'sourceFilters' => $sourceFilters, 'source' => $source]);
    }

    public function candidates()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $sourceField = 'source';
        if(isset(request()->source)) {
            $sourceField = request()->source;
        }
        H::ExcelLog(print_r($sourceField,true),'dbnrr.log');

        if (LD::checkFeatureFlag('save-report-filters-to-user-table')
            && !LD::checkFeatureFlag('nrr-session-filter-retention')) {
            if (request()->input('isajax')) {
                $search = request()->input('search');
                $date = request()->input('date');
                User::where('id', Auth::user()->id)->update([
                    'report_filters' => json_encode(array_merge($search, ['daterange' => $date]))
                ]);
            } else {
                $savedFilterData = User::find(Auth::user()->id)->report_filters;
                $savedFilterData = json_decode($savedFilterData, true)??[];
                if (Route::currentRouteName() != 'candidates') {
                    $fields = ['state', 'region', 'aoc_facility_id', 'Recruiter', 'excludeprn', 'Payer', 'payer_group', 'daterange'];
                    $savedFilterData = array_intersect_key($savedFilterData, array_flip($fields));
                    if (isset($savedFilterData['state']) && strlen($savedFilterData['state'][0]) > 2) {
                        unset($savedFilterData['state']);
                    }
                } else {
                    $fields = ['lob', 'state','aoc_facility_id', 'sourceType', 'appSource'];
                    $savedFilterData = array_intersect_key($savedFilterData, array_flip($fields));
                    if (isset($savedFilterData['state']) && strlen($savedFilterData['state'][0]) <= 2) {
                        unset($savedFilterData['state']);
                    }
                }
                if (isset($savedFilterData['daterange'])) {
                    unset($savedFilterData['daterange']);
                }
                request()->merge(['search' => $savedFilterData, 'isajax' => true]);
            }
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            } else if (!empty($daterange = H::getReportFiltersFieldVal('daterange', 'daterange'))) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime($daterange['from']));
                $date['to'] = date('Y-m-d', strtotime($daterange['to']));
                request()->merge(['date' => $date]);
            }
        } else {
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            }
        }

        //now define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Candidates';
        $report['id'] = 'candidates';
        $report['period'] = $period;
        //columns defines the fieldname, heading, optional format, width and style for each column
        if($sourceField=='source'){
            $report['columns'] = [
                [
                    'field' => 'state',
                    'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'category',
                    'heading' => ['title' => 'Category', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'sourceType',
                    'heading' => ['title' => 'Source Type', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
                [
                    'field' => 'source',
                    'heading' => ['title' => 'Source', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                ],
            ];
        }else{
                $report['columns'] = [
                    [
                        'field' => 'state',
                        'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
                        'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                        'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                    ],
                    [
                        'field' => 'category',
                        'heading' => ['title' => 'Category', 'attributes' => '', 'style' => ''],
                        'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
                        'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                    ],
                    [
                        'field' => 'branch',
                        'heading' => ['title' => 'Branch', 'attributes' => '', 'style' => ''],
                        'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                        'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
                    ],
                ];
        }
        H::ExcelLog(print_r($report,true),'dbnrr.log');
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            if (LD::checkFeatureFlag('nrr-report-changes')) {
                $tdate = request()->date['to'];
                H::ExcelLog(H::datediffInWeeks($sdate, $tdate),'dbNRR.log');
                H::ExcelLog($tdate,'dbNRR.log');
            }
            $periods = H::getReportPeriodColumns($report['period'], ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate), request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;
        if (LD::checkFeatureFlag('nrr-report-changes')) {
            $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) -1]['name'], $periods[0]['name']);
            if( $selectedDatesDiffInWeek >= 13) {
                $showAvgColumn = 1;
            }
            H::ExcelLog("selectedDatesDiffInWeek==".$selectedDatesDiffInWeek,'dbNRR.log');
            H::ExcelLog("showAvgColumn==".$showAvgColumn,'dbNRR.log');
        }
        foreach($periods as $period){
            $clickedFilters = [];
            if ($sourceField == 'source') {
                $clickedFilters = ['state','category', 'sourceType', $sourceField];
            } else {
                    $clickedFilters = ['state','category', 'branch'];
            }
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNationalRecruitingReport', 'clickedfilters' => $clickedFilters,'defaultval'=>['start_of_week'=>$period['name']]],
            ];
        }
        if (LD::checkFeatureFlag('nrr-report-changes')) {
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
                $report['columns'][] = [
                    'field' => 'AvgTrend',
                    'heading' => ['title' => '', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '', 'style' => ''],
                ];
            }
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
        if($sourceField=='source'){
            $report['groupby'] = [
                    ['id' => 1, 'label' => '', 'field' => 'sourceType', 'showCols' => 'sourceType', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 1],
                    ['id' => 2, 'label' => '', 'field' => 'category', 'showCols' => 'category', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 2],
                    ['id' => 3, 'label' => '@group3', 'field' => 'state', 'showCols' => 'state', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 3],
                    ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                    ['id' => 9, 'label' => 'National Totals', 'field' => '', 'showCols' => '', 'style' => 'background-color: #D5D5D5;color:#000', 'level' => 5],
                ];
        }else{
            if(LD::checkFeatureFlag('nrr-candidate-tab-remove-duplicate-branch')) {
                $report['groupby'] = [
                    ['id' => 1, 'label' => '', 'field' => 'category', 'showCols' => 'category', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 1],
                    ['id' => 2, 'label' => '@group2', 'field' => 'state', 'showCols' => 'state', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 2],
                    ['id' => 3, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 3],
                    ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                    ['id' => 9, 'label' => 'National Totals', 'field' => '', 'showCols' => '', 'style' => 'background-color: #D5D5D5;color:#000', 'level' => 5],
                ];
            } else {
                $report['groupby'] = [
                    ['id' => 1, 'label' => '', 'field' => 'branch', 'showCols' => 'branch', 'style' => 'background-color: #D5D5D5;color:#444444;', 'level' => 1],
                    ['id' => 2, 'label' => '', 'field' => 'category', 'showCols' => 'category', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 2],
                    ['id' => 3, 'label' => '@group3', 'field' => 'state', 'showCols' => 'state', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 3],
                    ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
                    ['id' => 9, 'label' => 'National Totals', 'field' => '', 'showCols' => '', 'style' => 'background-color: #D5D5D5;color:#000', 'level' => 5],
                ];
            }
        }
        //jobs table has state name instead of initials,  so we will do this instead of calling constantHelper
        $dashboardState= config('constant.dashboard_state');
        $state = H::getHelper('ConstantHelper')->getDashboardState();

        $branches  = [];
        $sourceTypeObj = DB::table('candidate_applications')->select("sourceType")->distinct()->where('sourceType','<>','')->get();
        $sourceType = [];
        foreach($sourceTypeObj as $source) {
            $sourceType[] = ["value" => $source->sourceType, 'text' => $source->sourceType];
        }

        $recruiter = H::getHelper("ConstantHelper")->getRecruiters();

        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'],'dbNRR.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            //$endingDate = H::getReportEndingDate($report['period'], request()->date['to']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2','dbNRR.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }

        $activeCategories = $this->jobCategoryService->getCategories();

        $lobOptions = $activeCategories
            ->pluck('Type')
            ->unique()
            ->values()
            ->map(fn($type) => [
                'value' => $type,
                'text' => $type,
            ]);

        if (LD::checkFeatureFlag('save-report-filters-to-user-table')) {
            if (LD::checkFeatureFlag('nrr-add-recruiter-filter-candidate-tab')) {
                $filters = [
                    [
                        'label' => 'LOB',
                        'name' => 'lob',
                        'type' => 'multiselect',
                        'source' => $lobOptions,
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
                        'source' => $recruiter,
                        'defaultValue' => H::getReportFiltersFieldVal('recruiter', 'multiselect')
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
                        'label' => 'Date',
                        'name' => 'daterange',
                        'type' => 'daterange',
                        'fromdate' => H::getFormatedDate($startDate),
                        'todate' => H::getFormatedDate($endingDate),
                        'defaultValue' => H::getReportFiltersFieldVal('daterange', 'daterange')
                    ]
                ];
            } else {
                $filters = [
                    [
                        'label' => 'LOB',
                        'name' => 'lob',
                        'type' => 'multiselect',
                        'source' => $lobOptions,
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
                        'label' => 'Branch',
                        'name' => 'aoc_facility_id',
                        'type' => 'multiselect',
                        'source' => $branches,
                        'defaultValue' => H::getReportFiltersFieldVal('aoc_facility_id', 'multiselect')
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
                        'label' => 'Date',
                        'name' => 'daterange',
                        'type' => 'daterange',
                        'fromdate' => H::getFormatedDate($startDate),
                        'todate' => H::getFormatedDate($endingDate),
                        'defaultValue' => H::getReportFiltersFieldVal('daterange', 'daterange')
                    ]
                ];
            }
        } else {
            if (LD::checkFeatureFlag('nrr-add-recruiter-filter-candidate-tab')) {
                $filters = [
                    [
                        'label' => 'LOB',
                        'name' => 'lob',
                        'type' => 'multiselect',
                        'source' => $lobOptions
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
                        'label' => 'Recruiter',
                        'name' => 'Recruiter',
                        'type' => 'multiselect',
                        'source' => $recruiter
                    ],
                    [
                        'label' => 'Source Type',
                        'name' => 'sourceType',
                        'type' => 'multiselect',
                        'source' => $sourceType
                    ],
                    [
                        'label' => 'Source',
                        'name' => 'appSource',
                        'type' => 'dependablemultiselect',
                        'dependon' => 'sourceType',
                        'dependonurl' => route('getSource')
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
                        'label' => 'LOB',
                        'name' => 'lob',
                        'type' => 'multiselect',
                        'source' => $lobOptions
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
                        'label' => 'Source Type',
                        'name' => 'sourceType',
                        'type' => 'multiselect',
                        'source' => $sourceType
                    ],
                    [
                        'label' => 'Source',
                        'name' => 'appSource',
                        'type' => 'dependablemultiselect',
                        'dependon' => 'sourceType',
                        'dependonurl' => route('getSource')
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
				'label' => 'By Source',
				'value' => 'source'
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

                    if ($field == self::LOB) {
                        $isEmptyArray = is_array($value) && count(array_filter($value)) == 0;
                        $isEmptyScalar = !is_array($value) && ($value == '' || $value == null);
                        $isAll = !is_array($value) && strtolower((string)$value) == 'all';
                        if ($isEmptyArray || $isEmptyScalar || $isAll) {
                            continue;
                        }
                    }

                    if (is_array($value)) {
                        if ($field == self::STATE) {
                            $converted = array_map(function ($item) use ($dashboardState) {
                                return $dashboardState[$item] ?? $item;
                            }, $value);
                            $where[] = $this->generateMultiSelectQuery('jobs.state', $converted, $paramsArray);
                        } elseif ($field == self::BRANCH) {

                            $facilities = MssqlFacilities::query()
                                ->whereIn('id', $value)
                                ->where(function ($query) {
                                    $query->where('hide', '!=', '1')
                                        ->orWhereNull('hide');
                                })
                                ->pluck('FacilityName')
                                ->toArray();

                            $normalized = array_map(function ($item) {
                                $parts = explode(' - ', $item);
                                return trim(end($parts));
                            }, $facilities);

                            $where[] = $this->generateMultiSelectQuery('jobs.branch', $normalized, $paramsArray);
                        } elseif ($field == self::RECRUITER) {
                            if (!empty($value) && !is_numeric($value[0])) {

                                $ids = User::whereIn('name', $value)
                                    ->pluck('id')
                                    ->toArray();

                                $value = $ids;
                            }
                            $where[] = $this->generateMultiSelectQuery('candidates.user_id', $value, $paramsArray);
                        } elseif ($field == self::SOURCE_TYPE) {
                            $where[] = $this->generateMultiSelectQuery('candidate_applications.sourceType', $value, $paramsArray);
                        } elseif ($field == self::APP_SOURCE || $field == self::SOURCE) {
                            $where[] = $this->generateMultiSelectQuery('candidate_applications.source', $value, $paramsArray);
                        } elseif ($field == self::LOB) {
                            // LOB determined via job_categories.Type later (xref mapping)
                            continue;
                        }
                    } else {
                        if ($value !== '') {
                            if ($field == self::STATE) {
                                $converted = $dashboardState[$value] ?? $value;
                                $where[] = 'jobs.state = ?';
                                $paramsArray[] = $converted;
                            } elseif ($field == self::BRANCH) {
                                if (is_numeric($value)) {

                                    $facility = MssqlFacilities::query()
                                        ->where('id', $value)
                                        ->where(function ($query) {
                                            $query->where('hide', '!=', '1')
                                                ->orWhereNull('hide');
                                        })
                                        ->value('FacilityName');

                                    if ($facility) {
                                        $parts = explode(' - ', $facility);
                                        $value = trim(end($parts));
                                    }
                                }

                                $where[] = 'jobs.branch = ?';
                                $paramsArray[] = $value;
                            } elseif ($field == self::RECRUITER) {
                                if (!is_numeric($value)) {
                                    $userId = User::where('name', $value)->value('id');
                                    $value = $userId;
                                }
                                $where[] = 'candidates.user_id = ?';
                                $paramsArray[] = $value;
                            } elseif ($field == self::SOURCE_TYPE) {
                                $where[] = 'candidate_applications.sourceType = ?';
                                $paramsArray[] = $value;
                            } elseif ($field == self::APP_SOURCE || $field == self::SOURCE) {
                                $where[] = 'candidate_applications.source = ?';
                                $paramsArray[] = $value;
                            } elseif ($field == self::LOB) {
                                // LOB determined via job_categories.Type later (xref mapping)
                                continue;
                            } else {
                                $where[] = $field . ' = ?';
                                $paramsArray[] = $value;
                            }
                        }
                    }
                }
			}
		}

        $periodQuery = H::getReportPeriodDefinition($report['period'],'candidate_applications.application_date');
        //now get report data

        if($sourceField == self::SOURCE) {
            $qry = "
        WITH cte AS (
            SELECT
              candidate_applications.candidate_id,
              DATE_FORMAT(DATE_ADD(candidate_applications.application_date, INTERVAL -WEEKDAY(candidate_applications.application_date) DAY),'%Y-%m-%d') AS Monday,
              MIN(candidate_applications.id) AS appId
            FROM candidate_applications
            WHERE candidate_applications.application_date >= ? AND candidate_applications.application_date <= ?
            GROUP BY candidate_applications.candidate_id,
                     DATE_FORMAT(DATE_ADD(candidate_applications.application_date, INTERVAL -WEEKDAY(candidate_applications.application_date) DAY),'%Y-%m-%d')
        )
        SELECT
            CONCAT(jobs.category,'|',candidate_applications.sourceType,'|',candidate_applications.source) AS myBucket,
            jobs.state,
            jobs.category,
            candidate_applications.sourceType,
            candidate_applications.source,
            " . $periodQuery . " AS Monday,
            COUNT(candidate_applications.id) AS myCount,
            NULL AS intakeCoordinator
        FROM cte
        LEFT JOIN candidate_applications ON cte.appId = candidate_applications.id
        LEFT JOIN candidates ON candidate_applications.candidate_id = candidates.id
        LEFT JOIN jobs ON candidate_applications.job_id = jobs.id
        WHERE candidate_applications.application_date >= ? AND candidate_applications.application_date <= ?
        " . (count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . "
        GROUP BY jobs.state, jobs.category, candidate_applications.sourceType, candidate_applications.source, " . $periodQuery . "
        ORDER BY jobs.state, jobs.category, candidate_applications.sourceType, candidate_applications.source, " . $periodQuery . "
    ";

            $tmp = DB::select($qry, array_merge([
                $startDate,
                $endingDate,
                $startDate,
                $endingDate
            ], $paramsArray));
        } else {
            $qry = "
        WITH cte AS (
            SELECT
              candidate_applications.candidate_id,
              DATE_FORMAT(DATE_ADD(candidate_applications.application_date, INTERVAL -WEEKDAY(candidate_applications.application_date) DAY),'%Y-%m-%d') AS Monday,
              MIN(candidate_applications.id) AS appId
            FROM candidate_applications
            WHERE candidate_applications.application_date >= ? AND candidate_applications.application_date <= ?
            GROUP BY candidate_applications.candidate_id,
                     DATE_FORMAT(DATE_ADD(candidate_applications.application_date, INTERVAL -WEEKDAY(candidate_applications.application_date) DAY),'%Y-%m-%d')
        )
        SELECT
            CONCAT(jobs.category,'|',jobs.branch) AS myBucket,
            jobs.state,
            jobs.category,
            jobs.branch,
            candidate_applications.sourceType,
            candidate_applications.source,
            " . $periodQuery . " AS Monday,
            COUNT(candidate_applications.id) AS myCount,
            NULL AS intakeCoordinator
        FROM cte
        LEFT JOIN candidate_applications ON cte.appId = candidate_applications.id
        LEFT JOIN candidates ON candidate_applications.candidate_id = candidates.id
        LEFT JOIN jobs ON candidate_applications.job_id = jobs.id
        WHERE candidate_applications.application_date >= ? AND candidate_applications.application_date <= ?
        " . (count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . "
        GROUP BY jobs.state, jobs.category, jobs.branch, candidate_applications.sourceType, candidate_applications.source, " . $periodQuery . "
        ORDER BY jobs.state, jobs.category, jobs.branch, candidate_applications.sourceType, candidate_applications.source, " . $periodQuery . "
    ";

            $tmp = DB::select($qry, array_merge([
                $startDate,
                $endingDate,
                $startDate,
                $endingDate
            ], $paramsArray));
        }

        $xref = $activeCategories->mapWithKeys(function ($cat) {
            return [
                $cat->category => [
                    'label' => $cat->Type,
                    'state' => $cat->State,
                ],
            ];
        })->toArray();

        H::ExcelLog($qry,'dbNRR.log');
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'),0);
        $currentSixMonday = H::getMondayFromDate($currentMonday,6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday,13);
        $data = [];
        //process each record in case we need to alter data programmatically

        $selectedLobs = request()->input('search.lob');
        $selectedLobs = $selectedLobs ? (array)$selectedLobs : [];
        foreach ($tmp as $record) {

            $cat = $record['category'];
            $state = '';

            if (isset($xref[$record['category']])) {
                $cat = $xref[$record['category']]['label'];
                $state = $xref[$record['category']]['state'];
            }

            // LOB filter
            if ($selectedLobs && !in_array($cat, $selectedLobs)) {
                continue;
            }

            $id = $record['myBucket'];

            if (!isset($data[$id])) {
                if ($sourceField == 'source') {
                    $data[$id] = [
                        'state' => $record['state'],
                        'category' => $cat,
                        'sourceType' => $record['sourceType'],
                        'source' => $record['source'],
                    ];
                } else {
                    $data[$id] = [
                        'state' => $record['state'],
                        'category' => $cat,
                        'branch' => $record['branch'],
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
                $data[$id]['intakeCoordinator'] = $record['intakeCoordinator'];
            }

            if (LD::checkFeatureFlag('nrr-report-changes')) {
                if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentSixMonday)) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['myCount'];
                }
                if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentThirteenMonday)) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $record['myCount'];
                }
            }

            if(isset($data[$id][$record['Monday']])) {
                $data[$id][$record['Monday']] += $record['myCount'];
            }

            $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
        }
        /**
        * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
        * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
        */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.recruitingReports', [
                'report' => $report,
                'data' => $data,
                'filters' => $filters,
                'sourceFilters' => $sourceFilters,
                'source' => $sourceField,
            ]
        );
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

    public function IPW($title, $type, $period, $source)
    {
        //define report settings
        $report = [];
        $data = [];
        $report['title'] = $title;
        $report['id'] = $type;
        $report['period'] = $period;
        $report['goalField'] = 'ipea_goal';

        if ($type !== 'ipea') {
            $report['goalField'] = 'pepi_wepi_goal';
        }
        if ($type === 'ipea') {
            $report['goalField'] = 'ipea_goal';
            $schedTypeId = 1;
        } elseif ($type === 'pepi') {
            $report['goalField'] = 'pepi_wepi_goal';
            $schedTypeId = 3;
        } elseif ($type === 'wepi') {
            $report['goalField'] = 'pepi_wepi_goal';
            $schedTypeId = 4;
        } elseif ($type === 'cm') {
            $report['goalField'] = 'client_match_goal';
            $schedTypeId = 6;

        } else {
            $report['goalField'] = 'ipea_goal';
            $schedTypeId = 1;
        }

        if (LD::checkFeatureFlag('save-report-filters-to-user-table')
            && !LD::checkFeatureFlag('nrr-session-filter-retention')) {
            if (request()->input('isajax')) {
                $search = request()->input('search');
                $date = request()->input('date');
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
                request()->merge(['search' => $savedFilterData, 'isajax' => true]);
            }
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            } elseif (!empty($daterange = H::getReportFiltersFieldVal('daterange', 'daterange'))) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime($daterange['from']));
                $date['to'] = date('Y-m-d', strtotime($daterange['to']));
                request()->merge(['date' => $date]);
            }
        } else {
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            }
        }
        //columns defines the fieldname, heading, optional format, width and style for each column
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
                && (request()->view === 'hours' || empty(request()->view))
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
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            $tdate = request()->date['to'];

            $diffInWeeks = H::datediffInWeeks($sdate, $tdate);
            if ($tdate == Carbon::now()->format('Y-m-d')) {
                $diffInWeeks++;
            }

            $periods = H::getReportPeriodColumns(
                $report['period'],
                ($report['period'] == 'week') ?
                    $diffInWeeks :
                    H::monthDiff($sdate, $tdate),
                request()->date['to']
            );
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;
        if (LD::checkFeatureFlag('nrr-report-changes')) {
            $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) -1]['name'], $periods[0]['name']);
            if( $selectedDatesDiffInWeek >= 13) {
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
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : ''),
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
                $report['columns'][] = [
                    'field' => 'AvgTrend',
                    'heading' => ['title' => '', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '', 'style' => ''],
                ];
            }
        }
        $report['columns'][] = [
            'field' => 'Total',
            'heading' => ['title' => 'Total', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'), 'attributes' => '', 'style' => ''],
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
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2','dbNRR.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }

        $state = H::getHelper("ConstantHelper")->getDashboardState();
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
                    'label'=>'Recruiter',
                    'name'=>'Recruiter',
                    'type'=>'multiselect',
                    'source'=>$recruiters,
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
                'label'=>'Exclude PRN',
                'name'=>'excludeprn',
                'type'=>'checkbox',
                'value'=>false,
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
                    'label'=>'Recruiter',
                    'name'=>'Recruiter',
                    'type'=>'multiselect',
                    'source'=>$recruiters
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
                'label'=>'Exclude PRN',
                'name'=>'excludeprn',
                'type'=>'checkbox',
                'value'=>false,
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
        if (request()->input('isajax')) {
            $search = request()->input('search');
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
                            $tmp = "TRIM(SUBSTRING_INDEX(rt.whiteboard_row, ' - ', -1))";
                        }

                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        if($value!='') {
                            if($field=='aoc_facility_id'){
                                $where[] = 'rt.aoc_facility_id = ?';
                                array_push($paramsArray, $value);
                            }elseif($field=='state') {
                                $where[] = "left(rt.whiteboard_row,2) = ?";
                                array_push($paramsArray, $value);
                            }elseif($field=='region'){
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            }elseif($field=='excludeprn'){
                                $where[] = "(mp.isPRN = '0' OR mp.isPRN is null)";
                            }else{
                                $where[] = $field . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'],'rs.scheduled_date');
        //now get report data
        if($source=='recruiter'){
            $qry = "SELECT
            rt.whiteboard_row,
                case when isnull(w.territory) = true then 0 else rt.user_id end as user_id,
                case when isnull(w.territory) = true then " . '"' . "Term'd" . '"' .  " else rt.Recruiter end as Recruiter
            , w.territory, w.hire_date, w." . $report['goalField'] . ", left(rt.whiteboard_row,2) as state, reg.region_name, CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $periodQuery . " as Monday,
            count(rs.id) as myCount,
            SUM(rt.Total_Hours) AS totalHours

            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join regions reg on w.region = reg.id
            where rs.type_id = " . $schedTypeId . " and rs.scheduled_date >= ? and rs.scheduled_date <= ?  ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). ' AND rs.is_deleted <> 1 ' ."
            group by left(rt.whiteboard_row,2), rt.whiteboard_row, region, Recruiter, w.territory, w.hire_date, w.".$report['goalField'].", " . $periodQuery . "
            order by left(rt.whiteboard_row,2), rt.whiteboard_row, region, Recruiter, w.territory, w.hire_date, w.".$report['goalField'].", " . $periodQuery;
        }else{
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
        }

        $tmp = DB::stream($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));

        $currentMonday = H::getMondayFromDate(date('Y-m-d'),0);
        $currentSixMonday = H::getMondayFromDate($currentMonday,6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday,13);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if($source=='recruiter'){
                $id = $record['state'] . "|" . $record['whiteboard_row'] . "|" . $record['region'] . "|" . $record['Recruiter'] . "|" . (is_null($record['territory']) ? "Term'd" : $record['territory']) . "|" . $record['hire_date'] . "|" . $record[$report['goalField']];

                if(!isset($data[$id])){
                    $data[$id] = ['state' => $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'Recruiter' => $record['Recruiter'],
                        'region' => $record['region'],
                        'region_name' => $record['region_name'],
                        'territory' => is_null($record['territory']) ? "Term'd" : $record['territory'],
                        'hire_date' => $record['hire_date'],
                        $report['goalField'] => ($report['period'] == 'week') ? $record[$report['goalField']] : (($record[$report['goalField']] / 7) *  $this->get_days_in_month(date('m'), date('Y'))),
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }

                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                    $data[$id]['Count'] = 0;
                }
            }else{
				if (LD::checkFeatureFlag('fixed-nrr-cm-branch-view')) {
					$id = $record['state'] . "|" . $record['whiteboard_row'] . "|" . $record['Branch_Location'];
				} else {
					$id = $record['state'] . "|" . $record['whiteboard_row'] . "|" . $record['Recruiter'];
				}
                if(!isset($data[$id])){
                    if (LD::checkFeatureFlag('fixed-nrr-cm-branch-view')) {
                        $data[$id] = [
                            'state' => $record['state'],
							'whiteboard_row' => $record['whiteboard_row'],
							'region' => $record['region'],
							'region_name' => $record['region'],
							'Branch_Location' => $record['Branch_Location'],
						];
                    } else {
						$data[$id] = [
                            'state' => $record['state'],
							'whiteboard_row' => $record['whiteboard_row'],
							'region' => $record['region'],
							'region_name' => $record['region_name'],
							'Branch_Location' => $record['Branch_Location'],
							'Recruiter' => $record['Recruiter'],
						];
					}
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                }
            }

            if (request()->view && request()->view == 'percentage') {
                $total1 = (($record[$report['goalField']] > 0) ? (($record['myCount'] / $record[$report['goalField']]) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1, 2);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;

                if(floatval($data[$id]['Total'])!=0) {
                    $data[$id]['Count']++;
                }

            } elseif (request()->view && request()->view == 'count') {
                if (LD::checkFeatureFlag('nrr-report-changes')) {
                    if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentSixMonday)) {
                        $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['myCount'];
                    }

                    if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentThirteenMonday)) {
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

        if (request()->view && request()->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach($test as $key => $value){
                if($value['Count']!=0){
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'],0);
                }else{
                    $data[$key]['Total'] = 0;
                }
            }
        }

        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) { ///todo fix me
            $data['grand'] = $data[$id];
        }

        return view('reports.recruitingReports', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => $source,
        ]);
    }

    public function IPEAs(NationalRecruitingReportRequest $request)
    {
        $period = 'week';
        if (isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'recruiter';
        if (isset(request()->source)) {
            $source = request()->source;
        }

        return $this->IPW('IPEAs', 'ipea', $period, $source);
    }

    public function PEPIs()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'recruiter';
        if(isset(request()->source)) {
            $source = request()->source;
        }
        return $this->IPW('PEPIs', 'pepi', $period, $source);
    }

    public function WEPIs(NationalRecruitingReportRequest $request): View
    {
        try {
            $params = $request->toDTO();
            $period = 'week';

            if (isset(request()->period)) {
                $period = request()->period;
            }
            $source = 'recruiter';
            if (isset(request()->source)) {
                $source = request()->source;
            }

            return $this->IPW('WEPIs', 'wepi', $period, $source, $params);
        } catch (Exception $e) {
            Log::channel('nrr')->error('Exception caught', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    public function candidateMatch()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'recruiter';
        if(isset(request()->source)) {
            $source = request()->source;
        }
        return $this->IPW('Candidate Match', 'cm', $period, $source);
    }

    public function hoursRemoved()
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'recruiter';
        if(isset(request()->source)) {
            $source = request()->source;
        }

        if (LD::checkFeatureFlag('save-report-filters-to-user-table')
            && !LD::checkFeatureFlag('nrr-session-filter-retention')) {
            if (request()->input('isajax')) {
                $search = request()->input('search');
                $date = request()->input('date');
                User::where('id', Auth::user()->id)->update([
                    'report_filters' => json_encode(array_merge($search, ['daterange' => $date]))
                ]);
            } else {
                $savedFilterData = User::find(Auth::user()->id)->report_filters;
                $savedFilterData = json_decode($savedFilterData, true)??[];
                if (Route::currentRouteName() != 'candidates') {
                    $fields = ['lob','state', 'region', 'aoc_facility_id', 'Recruiter', 'excludeprn', 'Payer', 'payer_group', 'daterange'];
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
                request()->merge(['search' => $savedFilterData, 'isajax' => true]);
            }
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            } else if (!empty($daterange = H::getReportFiltersFieldVal('daterange', 'daterange'))) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime($daterange['from']));
                $date['to'] = date('Y-m-d', strtotime($daterange['to']));
                request()->merge(['date' => $date]);
            }
        } else {
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            }
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Hours Removed';
        $report['id'] = 'hours_removed';
        $report['period'] = $period;
        $report['goalField'] = 'candidate_start_hour_goals';
        $schedTypeId = 6;
        //columns defines the fieldname, heading, optional format, width and style for each column
        if($source=='recruiter'){
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

            if ((request()->view === 'count') && LD::checkFeatureFlag('distinguish-views-per-tab-nrr')) {
                array_pop($report['columns']);
            }
        }else{
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
                ]
            ];
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            if (LD::checkFeatureFlag('nrr-report-changes')) {
                $tdate = request()->date['to'];
                H::ExcelLog(H::datediffInWeeks($sdate, $tdate),'dbNRR.log');
                H::ExcelLog($tdate,'dbNRR.log');
            }
            $periods = H::getReportPeriodColumns($report['period'], ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate), request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;
        if (LD::checkFeatureFlag('nrr-report-changes')) {
            $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) -1]['name'], $periods[0]['name']);
            if( $selectedDatesDiffInWeek >= 13) {
                $showAvgColumn = 1;
            }
            H::ExcelLog("selectedDatesDiffInWeek==".$selectedDatesDiffInWeek,'dbNRR.log');
            H::ExcelLog("showAvgColumn==".$showAvgColumn,'dbNRR.log');
        }
        foreach($periods as $period){
            $clickedFilters = [];
            if ($source == 'recruiter') {
                $clickedFilters = [
                    'state',
                    'whiteboard_row',
                    'Recruiter',
                    'territory',
                    'hire_date',
                    $report['goalField'],
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
                'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :'1decimal'), 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'sum', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNationalRecruitingReport', 'clickedfilters' => $clickedFilters,'defaultval' => ['start_of_week' => $period['name'] , 'type_id' => $schedTypeId]],
            ];
        }
        if (LD::checkFeatureFlag('nrr-report-changes')) {
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
                $report['columns'][] = [
                    'field' => 'AvgTrend',
                    'heading' => ['title' => '', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '', 'style' => ''],
                ];
            }
        }
        $report['columns'][] = [
            'field' => 'Total',
            'heading' => ['title' => 'Total', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :'1decimal'), 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'), 'attributes' => '', 'style' => ''],
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
            ['id' => 1, 'label' => '', 'field' => 'region', 'showCols' => 'region', 'style' => 'background-color: #D9D9D9;color:#444;', 'level' => 1],
            ['id' => 2, 'label' => '', 'field' => 'whiteboard_row', 'showCols' => 'whiteboard_row', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 2],
            ['id' => 3, 'label' => '@group3', 'field' => 'state', 'showCols' => 'state', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 3],
            ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
            ['id' => 9, 'label' => 'National Totals', 'field' => '', 'showCols' => '', 'style' => 'background-color: #D5D5D5;color:#000', 'level' => 5],
        ];

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches  = [];
        foreach($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'],'dbNRR.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            //$endingDate = H::getReportEndingDate($report['period'], request()->date['to']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2','dbNRR.log');
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
                    'label'=>'Recruiter',
                    'name'=>'Recruiter',
                    'type'=>'multiselect',
                    'source'=>$recruiters,
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
                'label'=>'Exclude PRN',
                'name'=>'excludeprn',
                'type'=>'checkbox',
                'value'=>false,
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
                    'label'=>'Recruiter',
                    'name'=>'Recruiter',
                    'type'=>'multiselect',
                    'source'=>$recruiters
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
                'label'=>'Exclude PRN',
                'name'=>'excludeprn',
                'type'=>'checkbox',
                'value'=>false
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
				'value' => 'recruiter'
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
                        H::ExcelLog($field . ' - ' . print_r($value,true), 'dbNRR.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'rt.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "left(rt.whiteboard_row,2)";
                        }elseif($field=='Payer'){
                            $tmp = (
                                LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "rt.Payor_Source" : "mp.Payer"
                            );
                        }elseif($field=='payer_group'){
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

                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value,'dbNRR.log');
                        if($value!='') {
                            if ($field == 'aoc_facility_id') {
                                $where[] = 'rt.aoc_facility_id = ?';
                                array_push($paramsArray, $value);
                            } else if ($field == 'state') {
                                $where[] = 'left(rt.whiteboard_row,2) = ?';
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

        $periodQuery = H::getReportPeriodDefinition($report['period'],'rs.scheduled_date');
        //now get report data
        if($source=='recruiter'){
            $qry = "SELECT rt.whiteboard_row,                 case when isnull(w.territory) = true then 0 else rt.user_id end as user_id,
            case when isnull(w.territory) = true then " . '"' . "Term'd" . '"' .  " else rt.Recruiter end as Recruiter
            , w.territory, w.hire_date, w." . $report['goalField'] . ", left(rt.whiteboard_row,2) as state, reg.region_name,CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $periodQuery . " as Monday,
            sum(rt.Total_Hours) as totalHours,
            COUNT(DISTINCT rs.id) AS schedulesCount

            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join regions reg on w.region = reg.id
            where rs.type_id = " . $schedTypeId . " and rs.scheduled_date >= ? and rs.scheduled_date <= ? ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . ' AND rs.is_deleted <> 1 ' ."
            group by rt.whiteboard_row, region, rt.user_id, rt.Recruiter, w.territory, w.hire_date, w.ipea_goal, left(rt.whiteboard_row,2), " . $periodQuery . "
            order by rt.whiteboard_row, region, rt.user_id, rt.Recruiter, w.territory, w.hire_date, w.ipea_goal, left(rt.whiteboard_row,2), " . $periodQuery;
        }else{
            $qry = "SELECT left(rt.whiteboard_row,2) as state, rt.whiteboard_row, mf.FacilityName as Branch_Location, reg.region_name,CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $periodQuery . " as Monday,
            sum(rt.Total_Hours) as totalHours,
            COUNT(DISTINCT rs.id) AS schedulesCount

            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join mssql_facilities mf on mf.id = mp.aoc_facility_id
            left join regions reg on mf.region = reg.id
            where rs.type_id = " . $schedTypeId . " and rs.scheduled_date >= ? and rs.scheduled_date <= ? ".
            (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). ' AND rs.is_deleted <> 1 ' . "
            group by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id, " . $periodQuery . "
            order by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id, " . $periodQuery;
        }

        H::ExcelLog($qry,'dbNRR.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'),0);
        $currentSixMonday = H::getMondayFromDate($currentMonday,6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday,13);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if($source=='recruiter'){
                $id = $record['whiteboard_row'] . "|" . $record['region'] . "|" . $record['Recruiter'] . "|" . (is_null($record['territory']) ? "Term'd" : $record['territory']) . "|" . $record['hire_date'] . "|" . $record[$report['goalField']];
                //$id = $record['user_id'] . $record['whiteboard_row'];
                if(!isset($data[$id])){
                    $data[$id] = ['state' => $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'region' => $record['region'],
                        'Recruiter' => $record['Recruiter'],
                        'territory' => is_null($record['territory']) ? "Term'd" : $record['territory'],
                        'hire_date' => $record['hire_date'],
                        $report['goalField'] => ($report['period'] == 'week') ? $record[$report['goalField']] : (($record[$report['goalField']] / 7) *  $this->get_days_in_month(date('m'), date('Y'))),
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                    $data[$id]['Count'] = 0;
                }
            }else{
                $id = $record['whiteboard_row'] . $record['Branch_Location'] . $record['region'];
                if(!isset($data[$id])){
                    $data[$id] = ['state' => $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'region' => $record['region'],
                        'Branch_Location' => $record['Branch_Location']
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                }
            }
            if (request()->view && request()->view == 'percentage') {
                $total1 = (($record[$report['goalField']] > 0) ? (($record['totalHours'] / $record[$report['goalField']]) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1, 2);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if(floatval($data[$id]['Total'])!=0) {
                    $data[$id]['Count']++;
                }
            } else {
                if (LD::checkFeatureFlag('nrr-report-changes')) {
                    if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentSixMonday)) {
                        $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['totalHours'];
                    }
                    if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentThirteenMonday)) {
                        $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $record['totalHours'];
                    }
                }
                $data[$id][$record['Monday']] = $record['totalHours'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['totalHours'];
            }

            if (request()->view && request()->view == 'count' && LD::checkFeatureFlag('distinguish-views-per-tab-nrr')) {
                $data[$id][$record['Monday']] = $record['schedulesCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['schedulesCount'];
            }
        }

        if (request()->view && request()->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach($test as $key => $value){
                if($value['Count']!=0){
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'],0);
                }else{
                    $data[$key]['Total'] = 0;
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

        return view('reports.recruitingReports', ['report' => $report, 'data' => $data, 'filters' => $filters, 'sourceFilters' => $sourceFilters, 'source' => $source]);
    }

    /**
     * @param string $tab
     * @param NationalRecruitingReportRequest $request
     * @param NRRFactoryService $factory
     * @return Application|Factory|View|void
     * @throws Exception
     */
    public function candidateStarts(NationalRecruitingReportRequest $request)
    {


//        dd(LD::checkFeatureFlag('distinguish-views-per-tab-nrr'));


        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'recruiter';
        if(isset(request()->source)) {
            $source = request()->source;
        }

        if (LD::checkFeatureFlag('save-report-filters-to-user-table')
            && !LD::checkFeatureFlag('nrr-session-filter-retention')) {
            if (request()->input('isajax')) {
                $search = request()->input('search');
                $date = request()->input('date');
                User::where('id', Auth::user()->id)->update([
                    'report_filters' => json_encode(array_merge($search, ['daterange' => $date]))
                ]);
            } else {
                $savedFilterData = User::find(Auth::user()->id)->report_filters;
                $savedFilterData = json_decode($savedFilterData, true)??[];
                if (Route::currentRouteName() != 'candidates') {
                    $fields = ['state', 'region', 'aoc_facility_id', 'Recruiter', 'excludeprn', 'Payer', 'payer_group', 'daterange'];
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
                request()->merge(['search' => $savedFilterData, 'isajax' => true]);
            }
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            } else if (!empty($daterange = H::getReportFiltersFieldVal('daterange', 'daterange'))) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime($daterange['from']));
                $date['to'] = date('Y-m-d', strtotime($daterange['to']));
                request()->merge(['date' => $date]);
            }
        } else {
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            }
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Candidate Starts';
        $report['id'] = 'candidate_starts';
        $report['period'] = $period;
        $report['goalField'] = 'candidate_start_hour_goals';
        $schedTypeId = 6;

        //columns defines the fieldname, heading, optional format, width and style for each column
        if($source=='recruiter'){
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

            if ((request()->view === 'count') && LD::checkFeatureFlag('distinguish-views-per-tab-nrr')) {
                array_pop($report['columns']);
            }
        }else{
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
                ]
            ];
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            if (LD::checkFeatureFlag('nrr-report-changes')) {
                $tdate = request()->date['to'];
            }
            $periods = H::getReportPeriodColumns($report['period'], ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate), request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;
        if (LD::checkFeatureFlag('nrr-report-changes')) {
            $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) -1]['name'], $periods[0]['name']);
            if( $selectedDatesDiffInWeek >= 13) {
                $showAvgColumn = 1;
            }
        }
        foreach($periods as $period){
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
                'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => ((request()->view && request()->view == 'percentage') ? 'pct' : 'sum'), 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNationalRecruitingReport', 'clickedfilters' => $clickedFilters, 'defaultval' => ['start_of_week' => $period['name'] , 'type_id' => $schedTypeId]],
            ];
        }
        if (LD::checkFeatureFlag('nrr-report-changes')) {
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
                $report['columns'][] = [
                    'field' => 'AvgTrend',
                    'heading' => ['title' => '', 'attributes' => '', 'style' => ''],
                    'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
                    'totals' => ['formula' => 'weekavg', 'attributes' => '', 'style' => ''],
                ];
            }
        }
        $report['columns'][] = [
            'field' => 'Total',
            'heading' => ['title' => 'Total', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => ((request()->view && request()->view == 'percentage') ? 'pct' :''), 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => ((request()->view && request()->view == 'percentage') ? 'pct' : 'sum'), 'attributes' => '', 'style' => ''],
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
            ['id' => 1, 'label' => '', 'field' => 'region', 'showCols' => 'region', 'style' => 'background-color: #D9D9D9;color:#444;', 'level' => 1],
            ['id' => 2, 'label' => '', 'field' => 'whiteboard_row', 'showCols' => 'whiteboard_row', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 2],
            ['id' => 3, 'label' => '@group3', 'field' => 'state', 'showCols' => 'state', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 3],
            ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
            ['id' => 9, 'label' => 'National Totals', 'field' => '', 'showCols' => '', 'style' => 'background-color: #D5D5D5;color:#000', 'level' => 5],
        ];

        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches  = [];
        foreach($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        $currentDate = date('Y-m-d');
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'],'dbNRR.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            //$endingDate = H::getReportEndingDate($report['period'], request()->date['to']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2','dbNRR.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        $regions = H::getHelper("ConstantHelper")->getRegion();
        $recruiters = H::getHelper("ConstantHelper")->getRecruitersList(false);
        $branches  = [];
        if (LD::checkFeatureFlag('save-report-filters-to-user-table')) {
            $filters = [
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
                    'label'=>'Recruiter',
                    'name'=>'Recruiter',
                    'type'=>'multiselect',
                    'source'=>$recruiters,
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
                'label'=>'Exclude PRN',
                'name'=>'excludeprn',
                'type'=>'checkbox',
                'value'=>false,
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
                    'label'=>'Recruiter',
                    'name'=>'Recruiter',
                    'type'=>'multiselect',
                    'source'=>$recruiters
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
                'label'=>'Exclude PRN',
                'name'=>'excludeprn',
                'type'=>'checkbox',
                'value'=>false
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
                'value' => 'recruiter'
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
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'dbNRR.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'rt.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "left(rt.whiteboard_row,2)";
                        }elseif($field=='Payer'){
                            $tmp = (
                            LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "rt.Payor_Source" : "mp.Payer"
                            );
                        }elseif($field=='payer_group'){
                            $tmp = (
                            LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "(select payergroup_name from payment_source where name =
                                    rt.Payor_Source order by id DESC limit 1)" : 'p1.payer_group'
                            );
                        }elseif ($field == 'Recruiter') {
                            $tmp = 'rt.Recruiter';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'dbNRR.log');
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
                            }else {
                                $where[] = $field . '= ?';
                                array_push($paramsArray, $value);
                            }
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'],'rs.appointment_date');
        //now get report data
        $scheduleQuery = '';
        if (LD::checkFeatureFlag('fixed-nrr-candidate-start-tab-prn-client-issue')) {
            if (LD::checkFeatureFlag('include-client-not-in-kantime-in-NRR-candidate-start-report')) {
                $scheduleQuery = '((p1.isPRN <> 1 AND rs.has_billable_visit = 1 and p1.not_in_kantime = 0) OR p1.isPRN = 1 OR p1.not_in_kantime = 1)';
            } else {
                $scheduleQuery = '((p1.isPRN <> 1 AND isnull((select sm.ScheduleDate from mssql_schedulemaster sm where
                sm.ProviderID = p2.ProviderID and sm.PatientID = p1.PatientID and sm.ScheduleStatusID in (1,2,3) and
                sm.ScheduleDate = rs.appointment_date limit 1)) = false) OR p1.isPRN = 1)';
            }
        } else {
            if (LD::checkFeatureFlag('include-client-not-in-kantime-in-NRR-candidate-start-report')) {
                $scheduleQuery = 'rs.has_billable_visit = 1';
            } else {
                $scheduleQuery = 'isnull((select sm.ScheduleDate from mssql_schedulemaster sm where
                sm.ProviderID = p2.ProviderID and sm.PatientID = p1.PatientID and sm.ScheduleStatusID in (1,2,3) and
                sm.ScheduleDate = rs.appointment_date limit 1)) = false';
            }
        }
        $scheduleQuery .= ' AND rt.Candidate_Current_Status <> \'Inactive\' ';
        if ($source=='recruiter') {
            $qry = "SELECT rt.whiteboard_row, rt.user_id, rt.Recruiter, w.territory, w.hire_date,
                w." . $report['goalField'] . ", left(rt.whiteboard_row,2) as state,
                reg.region_name, CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
                " . $periodQuery . " as Monday,
                sum(rt.Total_Hours) as totalHours,
                COUNT(rs.id) AS schedulesCount
                FROM `recruiting_schedule` rs
                left join mssql_recruitertracker rt on tracker_id = rt.id
                left join users u on rt.user_id = u.id
                left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
                left join regions reg on w.region = reg.id
                left join mssql_patients p1 on rs.aoc_patient_id = p1.id
                left join mssql_providers p2 on rt.aoc_provider_id = p2.id
                where rs.type_id = " . $schedTypeId . "
                AND IFNULL(p2.is_deleted, 0) = 0
                AND rs.appointment_date >= ? and rs.appointment_date <= ?
                AND " . $scheduleQuery . " " . (count($where) > 0 ? "
                AND " . implode("
                AND ", $where) : ''). '
                AND rs.is_deleted <> 1 ' . "
                group by rt.whiteboard_row, region, rt.user_id, rt.Recruiter, w.territory, w.hire_date, w.ipea_goal, left(rt.whiteboard_row,2), " . $periodQuery . "
                order by rt.whiteboard_row, region, rt.user_id, rt.Recruiter, w.territory, w.hire_date, w.ipea_goal, left(rt.whiteboard_row,2), " . $periodQuery;
        }else{
            $qry = "SELECT left(rt.whiteboard_row,2) as state, rt.whiteboard_row, mf.FacilityName as Branch_Location, reg.region_name, CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $periodQuery . " as Monday,
            sum(rt.Total_Hours) as totalHours,
                COUNT(rs.id) AS schedulesCount
            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join mssql_patients p1 on rs.aoc_patient_id = p1.id
            left join mssql_providers p2 on rt.aoc_provider_id = p2.id
            left join mssql_facilities mf on mf.id = p1.aoc_facility_id
            left join regions reg on mf.region = reg.id
            where rs.type_id = " . $schedTypeId . " and p2.is_deleted = 0 and rs.appointment_date >= ? and
            rs.appointment_date <= ? and
            " . $scheduleQuery . " " .(count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). ' AND rs.is_deleted <> 1 ' . "
            group by left(rt.whiteboard_row,2), rt.whiteboard_row, region, p1.aoc_facility_id, " . $periodQuery . "
            order by left(rt.whiteboard_row,2), rt.whiteboard_row, region, p1.aoc_facility_id, " . $periodQuery;
        }
        H::ExcelLog($qry,'dbNRR.log');

        $tmp = DB::stream($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));

        $currentMonday = H::getMondayFromDate(date('Y-m-d'),0);
        $currentSixMonday = H::getMondayFromDate($currentMonday,6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday,13);
        $data = [];

        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if($source=='recruiter'){
                $id = $record['whiteboard_row'] . "|" . $record['region'] . "|" .
                    $record['user_id'] . "|" . $record['Recruiter'] . "|" .
                    $record['territory'] . "|" . $record['hire_date'] . "|" . $record[$report['goalField']];
                if(!isset($data[$id])){
                    $data[$id] = ['state' => $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'region' => $record['region'],
                        'Recruiter' => $record['Recruiter'],
                        'territory' => is_null($record['territory']) ? "Term'd" : $record['territory'],
                        'hire_date' => $record['hire_date'],
                        $report['goalField'] => ($report['period'] == 'week') ? $record[$report['goalField']] : (($record[$report['goalField']] / 7) *  $this->get_days_in_month(date('m'), date('Y'))),
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                    $data[$id]['Count'] = 0;
                }
            }else{
                $id = $record['whiteboard_row'] . $record['Branch_Location'] . $record['region'];
                if(!isset($data[$id])){
                    $data[$id] = ['state' => $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'Branch_Location' => $record['Branch_Location'],
                        'region' => $record['region']
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
                        $data[$id][$period['name']] = 0;
                    }
                    $data[$id]['SixWeekAvg'] = 0;
                    $data[$id]['ThirteenWeekAvg'] = 0;
                    $data[$id]['AvgTrend'] = 0;
                    $data[$id]['Total'] = 0;
                }
            }

            $count = $record['totalHours'];

            if (($source=='recruiter' && $request->get('view') === 'count') && LD::checkFeatureFlag('distinguish-views-per-tab-nrr')) {
                $count = $record['schedulesCount'];
            }

            if (LD::checkFeatureFlag('nrr-report-changes')) {
                if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentSixMonday)) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $count;
                }
                if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentThirteenMonday)) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $count;
                }
            }

            $data[$id][$record['Monday']] = $count;
            $data[$id]['Total'] = $data[$id]['Total'] + $count;
        }

        if (LD::checkFeatureFlag('nrr-report-get-goals-for-all-whiteboard-rows')) {
            /*for Recruiter report - look for all whiteboard rows to
            make sure we have the recruiter's goal included.*/
            if($source=='recruiter'){
                $qry = "SELECT left(row_id,2) as state
                        ,row_id
                        ,CONCAT(reg.region_name, ' | ', reg.team_leader) as region
                        ,reg.region_name
                        ,user_name
                        ,w.user_id
                        ,territory
                        ,hire_date
                        ," . $report['goalField'] . "
                    FROM `whiteboard` w
                    left join regions reg on w.region = reg.id
                    left join mssql_recruitertracker rt on w.row_id = rt.whiteboard_row and rt.user_id = w.user_id
                    left join mssql_patients p1 on p1.id = rt.aoc_patient_id
                    WHERE w.is_deleted = 0 " . (count($where) > 0 ? " AND " . implode(" AND " , $where) : '') . "
                    order by w.row_id, region, w.user_id, user_name, territory, hire_date";

                $tmp2 = DB::stream($qry, $paramsArray);

                foreach($tmp2 as $record){
                    $id = $record['row_id'] . "|" . $record['region'] . "|" .
                        $record['user_id'] . "|" . $record['user_name'] . "|" .
                        $record['territory'] . "|" . $record['hire_date'] . "|" . $record[$report['goalField']];
                    if(!isset($data[$id])){
                        //adding the row
                        H::ExcelLog('Adding a row for ' . $id,'dbNRR1.log');
                        $data[$id] = ['state' => $record['state'],
                            'whiteboard_row' => $record['row_id'],
                            'region' => $record['region'],
                            'Recruiter' => $record['user_name'],
                            'territory' => $record['territory'],
                            'hire_date' => $record['hire_date'],
                            $report['goalField'] => ($report['period'] == 'week') ? $record[$report['goalField']] :
                                (($record[$report['goalField']] / 7) *  $this->get_days_in_month(date('m'), date('Y'))),
                        ];

                        //dynamically add fields for each period
                        foreach($periods as $period){
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
                H::ExcelLog(print_r($data,true),'dbNRR1.log');
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if(isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.recruitingReports', ['report' => $report, 'data' => $data, 'filters' => $filters, 'sourceFilters' => $sourceFilters, 'source' => $source]);
    }

    /**
     * @return Application|Factory|View
     * @throws Exception
     */
    public function grossMargin(NationalRecruitingReportRequest $request)
    {
        $period = 'week';
        if(isset(request()->period)) {
            $period = request()->period;
        }
        $source = 'recruiter';
        if(isset(request()->source)) {
            $source = request()->source;
        }

        if (LD::checkFeatureFlag('save-report-filters-to-user-table')
            && !LD::checkFeatureFlag('nrr-session-filter-retention')) {
            if (request()->input('isajax')) {
                $search = request()->input('search');
                $date = request()->input('date');
                User::where('id', Auth::user()->id)->update([
                    'report_filters' => json_encode(array_merge($search, ['daterange' => $date]))
                ]);
            } else {
                $savedFilterData = User::find(Auth::user()->id)->report_filters;
                $savedFilterData = json_decode($savedFilterData, true)??[];
                if (Route::currentRouteName() != 'candidates') {
                    $fields = ['state', 'region', 'aoc_facility_id', 'Recruiter', 'excludeprn', 'Payer', 'payer_group', 'daterange'];
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
                request()->merge(['search' => $savedFilterData, 'isajax' => true]);
            }
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            } else if (!empty($daterange = H::getReportFiltersFieldVal('daterange', 'daterange'))) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime($daterange['from']));
                $date['to'] = date('Y-m-d', strtotime($daterange['to']));
                request()->merge(['date' => $date]);
            }
        } else {
            if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
                $date = [];
                $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
                $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
                request()->merge(['date' => $date]);
            }
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Gross Margin';
        $report['id'] = 'gross_margin';
        $report['period'] = $period;
        $schedTypeId = 6;

        //columns defines the fieldname, heading, optional format, width and style for each column
        if($source=='recruiter'){
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
            ];
        }else{
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
                ]
            ];
        }
        $clickedFilters = [];
        if ($source == 'recruiter') {
            $clickedFilters = [
                'state',
                'whiteboard_row',
                'Recruiter',
                'territory',
                'hire_date',
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
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = H::getReportEndingDate($report['period'], request()->date['to']);
            if (LD::checkFeatureFlag('nrr-report-changes')) {
                $tdate = request()->date['to'];
            }
            $periods = H::getReportPeriodColumns($report['period'], ($report['period'] == 'week') ? H::datediffInWeeks($sdate, $tdate) : H::monthDiff($sdate, $tdate), request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;
        if (LD::checkFeatureFlag('nrr-report-changes')) {
            $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) -1]['name'], $periods[0]['name']);
            if( $selectedDatesDiffInWeek >= 13) {
                $showAvgColumn = 1;
            }
        }
        foreach($periods as $period){
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => ['title' => $period['label'], 'attributes' => '', 'style' => ''],
                'cell' => ['format' => 'pct', 'attributes' => '', 'style' => ''],
                'totals' => ['formula' => 'avg', 'attributes' => '', 'style' => ''],
                'clickable' => ['jsfunction' => 'openNationalRecruitingReport', 'clickedfilters' => $clickedFilters, 'defaultval' => ['start_of_week' => $period['name'] , 'type_id' => $schedTypeId]],
            ];
        }
        if (LD::checkFeatureFlag('nrr-report-changes')) {
            if($report['period'] == 'week' && $showAvgColumn == 1 && (!isset(request()->view) || (isset(request()->view) && request()->view == 'count'))) {
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
            ['id' => 1, 'label' => '', 'field' => 'region', 'showCols' => 'region', 'style' => 'background-color: #D9D9D9;color:444;', 'level' => 1],
            ['id' => 2, 'label' => '', 'field' => 'whiteboard_row', 'showCols' => 'whiteboard_row', 'style' => 'background-color: #dbf0fd;color:#444444;', 'level' => 2],
            ['id' => 3, 'label' => '@group3', 'field' => 'state', 'showCols' => 'state', 'style' => 'background-color: #215eac;color:#fff;', 'level' => 3],
            ['id' => 4, 'label' => '', 'field' => '', 'showCols' => '', 'style' => '', 'level' => 4],
            ['id' => 9, 'label' => 'National Totals', 'field' => '', 'showCols' => '', 'style' => 'background-color: #D5D5D5;color:#000', 'level' => 5],
        ];


        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches  = [];
        foreach($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
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
        $regions = H::getHelper("ConstantHelper")->getRegion();
        $recruiters = H::getHelper("ConstantHelper")->getRecruitersList(false);
        $branches  = [];
        if (LD::checkFeatureFlag('save-report-filters-to-user-table')) {
            $filters = [
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
                    'label'=>'Recruiter',
                    'name'=>'Recruiter',
                    'type'=>'multiselect',
                    'source'=>$recruiters,
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
                'label'=>'Exclude PRN',
                'name'=>'excludeprn',
                'type'=>'checkbox',
                'value'=>false,
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
                'value' => 'recruiter'
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
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'dbNRR.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'rt.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "left(rt.whiteboard_row,2)";
                        }elseif($field=='Payer'){
                            $tmp = (
                            LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "rt.Payor_Source" : "mp.Payer"
                            );
                        }elseif($field=='payer_group'){
                            $tmp = (
                            LD::checkFeatureFlag('fixed-payer-nrr-tabs') ?
                                "(select payergroup_name from payment_source where name =
                                    rt.Payor_Source order by id DESC limit 1)" : 'mp.payer_group'
                            );
                        }elseif ($field == 'Recruiter') {
                            $tmp = 'rt.Recruiter';
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

        $periodQuery = H::getReportPeriodDefinition($report['period'],'rs.appointment_date');
        //now get report data
        if($source=='recruiter'){
            $qry = "SELECT rt.whiteboard_row,
                    case when isnull(w.territory) = true then 999999 else rt.user_id end as user_id,
                    case when isnull(w.territory) = true then " . '"' . "Term'd" . '"' .  " else rt.Recruiter end as Recruiter
                    , w.territory, w.hire_date, left(rt.whiteboard_row,2) as state,reg.region_name, CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $periodQuery . " as Monday,
            avg(rt.GP) as myCount

            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join regions reg on w.region = reg.id
            where rt.GP <> 0 and rs.type_id = " . $schedTypeId . " and rs.appointment_date >= ? and rs.appointment_date <= ? ".
                (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). ' AND rs.is_deleted <> 1 ' . "
            group by rt.whiteboard_row, region, case when isnull(w.territory) = true then 999999 else rt.user_id end, rt.Recruiter, w.territory, w.hire_date, w.ipea_goal, left(rt.whiteboard_row,2), " . $periodQuery . "
            order by rt.whiteboard_row, region, case when isnull(w.territory) = true then 999999 else rt.user_id end, rt.Recruiter, w.territory, w.hire_date, w.ipea_goal, left(rt.whiteboard_row,2), " . $periodQuery;
        }else{
            $qry = "SELECT left(rt.whiteboard_row,2) as state, rt.whiteboard_row, mf.FacilityName as Branch_Location,reg.region_name,CONCAT(reg.region_name, ' | ', reg.team_leader) as region,
            " . $periodQuery . " as Monday,
            avg(rt.GP) as myCount
            FROM `recruiting_schedule` rs
            left join mssql_recruitertracker rt on tracker_id = rt.id
            left join mssql_patients mp on rs.aoc_patient_id = mp.id
            left join users u on rt.user_id = u.id
            left join whiteboard w on rt.whiteboard_row = w.row_id and rt.user_id = w.user_id
            left join mssql_facilities mf on mf.id = mp.aoc_facility_id
            left join regions reg on mf.region = reg.id
            where rs.type_id = " . $schedTypeId . " and rs.appointment_date >= ? and rs.appointment_date <= ? ".
                (count($where) > 0 ? " AND " . implode(" AND " , $where) : ''). ' AND rs.is_deleted <> 1 ' . "
            group by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id, " . $periodQuery . "
            order by left(rt.whiteboard_row,2), rt.whiteboard_row, region, mp.aoc_facility_id, " . $periodQuery;
        }

        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'),0);
        $currentSixMonday = H::getMondayFromDate($currentMonday,6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday,13);
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if($source=='recruiter'){
                //$id = $record['user_id'] . $record['whiteboard_row'];
                $id = $record['whiteboard_row'] . "|" .  $record['region'] . "|" . $record['Recruiter'] . "|" . (is_null($record['territory']) ? "Term'd" : $record['territory']) . "|" . $record['hire_date'];
                if(!isset($data[$id])){
                    $data[$id] = ['state' => $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'region' => $record['region'],
                        'Recruiter' => $record['Recruiter'],
                        'territory' => is_null($record['territory']) ? "Term'd" : $record['territory'],
                        'hire_date' => $record['hire_date'],
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
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
            }else{
                $id = $record['whiteboard_row'] . $record['region'] . $record['Branch_Location'];
                if(!isset($data[$id])){
                    $data[$id] = ['state' => $record['state'],
                        'whiteboard_row' => $record['whiteboard_row'],
                        'region' => $record['region'],
                        'Branch_Location' => $record['Branch_Location']
                    ];
                    //dynamically add fields for each period
                    foreach($periods as $period){
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
                if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentSixMonday)) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $grossProfit;
                    if($grossProfit > 0){
                        $data[$id]['SixWeekCount'] = $data[$id]['SixWeekCount'] + 1;
                    }
                }
                if(strtotime($currentMonday) > strtotime($record['Monday']) && strtotime($record['Monday']) >= strtotime($currentThirteenMonday)) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $grossProfit;
                    if($grossProfit > 0) {
                        $data[$id]['ThirteenWeekCount'] = $data[$id]['ThirteenWeekCount'] + 1;
                    }
                }
            }

            $data[$id][$record['Monday']] = $grossProfit;
            $data[$id]['Total'] = $data[$id]['Total'] + $grossProfit;
            if(floatval($data[$id]['Total'])!=0) {
                $data[$id]['Count']++;
            }
        }
        if (!LD::checkFeatureFlag('fix-gross-margin-tab-calculation-issue')) {
            //average the Total columns
            $test = $data;
            foreach($test as $key => $value){
                if($value['Count']!=0){
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'],0);
                }else{
                    $data[$key]['Total'] = 0;
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

        return view('reports.recruitingReports', ['report' => $report, 'data' => $data, 'filters' => $filters, 'sourceFilters' => $sourceFilters, 'source' => $source]);
    }

    public function whiteboard()
    {
        // Do nothing.
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
