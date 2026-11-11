<?php

namespace App\Http\Controllers;

use App\BranchLocations;
use App\Helpers\Calendar;
use App\Helpers\H;
use App\Helpers\LD;
use App\MssqlFacilities;
use App\MssqlPatients;
use App\MssqlProviders;
use App\MssqlRecruitertracker;
use App\ScheduleType;
use App\Candidates;
use App\RecruitingSchedule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\Notifications;
use Illuminate\Support\Facades\Auth;

class IntakeCalendarController extends Controller
{
    public function getWeekEvents(Request $request)
    {
        $reqData = $request->all();
        $branchData = [];
        $state = '';
        $branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
        $week = Calendar::getWeekStartEndByDates($reqData['date']);


        if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                $scheduleData = DB::table("intake")
                    ->select(
                        'mssql_patients.id',
                        'mssql_patients.PatientID',
                        DB::raw('mssql_schedulemaster.ServiceName as name'),
                        DB::raw("users.name AS intakeCordinator"),
                        'mssql_facilities.FacilityName',
                        DB::raw(
                            "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"
                        ),
                        'lob.LOBName',
                        
                        DB::raw("(mssql_schedulemaster.ScheduleDate) as IntakeDate"
                        ),
                        DB::raw('CASE WHEN
                                    (mssql_schedulemaster.ActualStart !="0000-00-00 00:00:00")
                                THEN
                                    mssql_schedulemaster.ActualStart
                                ELSE
                                mssql_schedulemaster.PlannedStart
                                END as IntakeStartTime'),
                    
                        DB::raw("casemanager.name as case_manager")
                    );
                $scheduleData->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $scheduleData->leftJoin("mssql_schedulemaster", function ($query) {
                    $query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_admission_visit");
                });
            } else {
                $scheduleData = DB::table("mssql_patients")
                    ->select(
                        'mssql_patients.id',
                        'mssql_patients.PatientID',
                        DB::raw('mssql_schedulemaster.ServiceName as name'),
                        DB::raw("users.name AS intakeCordinator"),
                        'mssql_facilities.FacilityName',
                        DB::raw(
                            "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"
                        ),
                        'lob.LOBName',
                        
                        DB::raw("(mssql_schedulemaster.ScheduleDate) as IntakeDate"
                        ),
                        DB::raw('CASE WHEN
                                    (mssql_schedulemaster.ActualStart !="0000-00-00 00:00:00")
                                THEN
                                    mssql_schedulemaster.ActualStart
                                ELSE
                                mssql_schedulemaster.PlannedStart
                                END as IntakeStartTime'),
                    
                        DB::raw("casemanager.name as case_manager")
                    );
                $scheduleData->leftJoin("mssql_schedulemaster", function ($query) {
                    $query->on("mssql_schedulemaster.CGTaskID", "=", "mssql_patients.intake_admission_visit");
                });
            }
            
            $scheduleData->leftJoin("mssql_facilities", function ($query) {
                $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
            });
            
            $scheduleData->leftJoin("users as casemanager", function ($query) {
                $query->on("mssql_patients.ccm_id", "=", "casemanager.id");
            });
            
            /*$scheduleData->leftJoin("mssql_providers", function ($query) {
                $query->on("mssql_patients.CaseManagerID", "=", "mssql_providers.mssqlProviderID");
            });*/
            $scheduleData->leftJoin("lob", function ($query) {
                $query->on("mssql_patients.LOBID", "=", "lob.LOBID");
            });
            $scheduleData->leftJoin("users", function ($query) {
                $query->on("mssql_patients.ic_id", "=", "users.id");
            });
            //$scheduleData->whereIn('service_codes_daas.name', ['RA Post Hospital','RA Miscellaneous']);
            $scheduleData->whereDate("mssql_schedulemaster.ScheduleDate",">=",$week['start']);
            $scheduleData->whereDate("mssql_schedulemaster.ScheduleDate","<=",$week['end']);

            if(!empty($reqData)) {
                Session::put(['lastCalendarSearch'=>$reqData]);
                $state = '';
                $branch = [];
                $lob = '';
              
                if (isset($reqData['state']) && !empty($reqData['state'])) {

                    $states = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
                    $state = $reqData['state'];
                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                    $scheduleData->whereIn("mssql_patients.aoc_facility_id", array_column($facilities->toArray(), 'id'));

                    $key = array_search($state, array_column($states, 'value'));
                    $state = $states[$key]['text'];
                    

                }
        
                if (isset($reqData['branch']) && !empty($reqData['branch'])) {
                    $branch[] = $reqData['branch'];
                    $scheduleData->whereIn("mssql_patients.aoc_facility_id", $branch);
                }

                if (isset($reqData['lob']) && !empty($reqData['lob'])) {
                    $lob = $reqData['lob'];
                    $scheduleData->where("mssql_patients.LOBID", $lob);
                }

                if (isset($reqData['ic_id']) && !empty($reqData['ic_id'])) {
                    $ic_id = $reqData['ic_id'];
                    $scheduleData->where("mssql_patients.ic_id", $ic_id);
                }

                if (isset($reqData['case_manager']) && !empty($reqData['case_manager'])) {
                    $case_manager = $reqData['case_manager'];
                    $scheduleData->where("casemanager.id", $case_manager);
                }
            }
            
            

            $bucket = DB::table("mssql_patients")
                ->select(
                    'mssql_patients.id',
                    DB::raw("users.name AS intakeCordinator"),
                    'mssql_facilities.FacilityName',
                    DB::raw(
                        "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"
                    ),
                    'lob.LOBName',
                    
                    DB::raw("(select admission_visit_date
                        from intake
                        where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                        ) as IntakeDate"
                    ),
                    DB::raw("(select start_time
                        from intake
                        where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                        ) as IntakeStartTime"
                    ),
                    DB::raw("(select referral_type
                        from intake
                        where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                        ) as referral_type"
                    ),
                    DB::raw("casemanager.name as case_manager")
                );
                $bucket->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
                });
                /*$bucket->leftJoin("mssql_providers", function ($query) {
                    $query->on("mssql_patients.CaseManagerID", "=", "mssql_providers.mssqlProviderID");
                });*/
                $bucket->leftJoin("users as casemanager", function ($query) {
                    $query->on("mssql_patients.ccm_id", "=", "casemanager.id");
                });
                
                $bucket->leftJoin("lob", function ($query) {
                    $query->on("mssql_patients.LOBID", "=", "lob.LOBID");
                });
                $bucket->leftJoin("users", function ($query) {
                    $query->on("mssql_patients.ic_id", "=", "users.id");
                });

                $bucket->where('mssql_patients.IntakeStatus', 'Scheduled');
                $bucket->where('mssql_patients.is_intake', '1');


                if(!empty($reqData)) {
                    Session::put(['lastCalendarSearch'=>$reqData]);
                    $state = '';
                    $branch = [];
                    $lob = '';
                  
                    if (isset($reqData['state']) && !empty($reqData['state'])) {

                        $states = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
                        $state = $reqData['state'];
                        $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                        $bucket->whereIn("mssql_patients.aoc_facility_id", array_column($facilities->toArray(), 'id'));

                        $key = array_search($state, array_column($states, 'value'));
                        $state = $states[$key]['text'];
                        

                    }
            
                    if (isset($reqData['branch']) && !empty($reqData['branch'])) {
                        $branch[] = $reqData['branch'];
                        $bucket->whereIn("mssql_patients.aoc_facility_id", $branch);
                    }

                    if (isset($reqData['lob']) && !empty($reqData['lob'])) {
                        $lob = $reqData['lob'];
                        $bucket->where("mssql_patients.LOBID", $lob);
                    }

                    if (isset($reqData['ic_id']) && !empty($reqData['ic_id'])) {
                        $ic_id = $reqData['ic_id'];
                        $bucket->where("mssql_patients.ic_id", $ic_id);
                    }

                    if (isset($reqData['case_manager']) && !empty($reqData['case_manager'])) {
                        $case_manager = $reqData['case_manager'];
                        $bucket->where("casemanager.id", $case_manager);
                        //$bucket->where("mssql_patients.CaseManagerID", $case_manager);
                    }

                    if(!empty($week)) {

                        $bucket->whereRaw("(select admission_visit_date
                        from intake
                        where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                        ) Between ? and ?", [$week['start'], $week['end']]);
                    }
                }
                

                $intakeData =[];
                $intakes = $bucket->get();
                foreach ($intakes as $key => $value) {
                    if ($value->IntakeDate != '00:00:00') {
                        $t = date('ga', strtotime($value->IntakeStartTime));
                        $day = date('l', strtotime($value->IntakeDate));
                        if(date('G', strtotime($value->IntakeStartTime)) <= 8){
                            $intakeData['8am'][$day][] = $value;
                        }elseif(date('G', strtotime($value->IntakeStartTime)) >= 20){
                            $intakeData['8pm'][$day][] = $value;
                        }else{
                            $intakeData[$t][$day][] = $value;
                        }
                    }
                }

                $scheduleD = $scheduleData->get();
               
                foreach ($scheduleD as $key => $value) {
                    
                    if ($value->IntakeDate != '00:00:00') {
                        $t = date('ga', strtotime($value->IntakeStartTime));
                        $day = date('l', strtotime($value->IntakeDate));
                        if(date('G', strtotime($value->IntakeStartTime)) <= 8){
                            $intakeData['8am'][$day][] = $value;
                        }elseif(date('G', strtotime($value->IntakeStartTime)) >= 20){
                            $intakeData['8pm'][$day][] = $value;
                        }else{
                            $intakeData[$t][$day][] = $value;
                        }
                    }
                }

              
        return view('intake.weekEvents',
         ['scheduleData'=>$intakeData,
         'branches'=>$branches,
         'branchData'=>$branchData,
         'week'=>$week,
         'state'=>$state]);
    }

    public function index(Request $request)
    {
        $branches = H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id');
        $states = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
       
        return view('intake.calendar', ['branches'=>$branches,'states'=>$states]);
    }

    

    public function getCalendarSearchString(Request $request)
    {
        $term = $request->input('term');
        $data = [];
        $q = DB::table("mssql_patients")
        ->select(
            'mssql_patients.id',
            DB::raw("users.name AS intakeCordinator"),
            'mssql_facilities.FacilityName',
            'mssql_patients.aoc_facility_id',
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
            'lob.LOBName',
            DB::raw("(select admission_visit_date
                from intake
                where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                ) as IntakeDate"
            ),
            DB::raw("(select start_time
                from intake
                where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                ) as IntakeStartTime"
            ),
            DB::raw("(select referral_type
                from intake
                where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                ) as referral_type"
            ),
            DB::raw("casemanager.name as case_manager")
        );
        $q->leftJoin("mssql_facilities", function ($query) {
            $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
        });

        $q->leftJoin("users as casemanager", function ($query) {
            $query->on("mssql_patients.ccm_id", "=", "casemanager.id");
        });

        /*$q->leftJoin("mssql_providers", function ($query) {
            $query->on("mssql_patients.CaseManagerID", "=", "mssql_providers.mssqlProviderID");
        });*/
        $q->leftJoin("lob", function ($query) {
            $query->on("mssql_patients.LOBID", "=", "lob.LOBID");
        });
        $q->leftJoin("users", function ($query) {
            $query->on("mssql_patients.ic_id", "=", "users.id");
        });

        $q->where('mssql_patients.IntakeStatus', 'Scheduled');
        $q->where('mssql_patients.is_intake', '1');

        if (isset($term) && $term != '') {
            
            $data = $q->where(function ($query) use ($term) {
                $query->where('casemanager.name', 'LIKE', "%{$term}%")
                ->orWhere('mssql_patients.Search_Name', 'LIKE', "%{$term}%")
                ->orWhere('users.name', 'LIKE', "%{$term}%");
            })->get();
            $res = [];
            foreach ($data as  $value) {
                $label = '';
                $label .= isset($value->patient_name) && $value->patient_name != '' ? $value->patient_name . ',' : '';
                $label .= isset($value->FacilityName) && $value->FacilityName != '' ? $value->FacilityName . ',' : '';
                $label .= isset($value->IntakeDate) && $value->IntakeDate != '' ?
                H::getFormatedDate($value->IntakeDate) . ',' : '';
                $label = str_replace(',,', ',', $label);
                $label = str_replace(',,', ',', $label);
                $res[] = [
                    'id'=>$value->id,
                    'label'=>trim($label, ','),
                    'date'=>$value->IntakeDate,
                    'client'=>$value->patient_name,
                    'state'=>substr($value->FacilityName,0,2),
                    'branchId'=>$value->aoc_facility_id,
                    'month'=>date('n', strtotime($value->IntakeDate))
                ];
            }



            if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                $scheduleData = DB::table("intake")
                ->select(
                    'mssql_patients.id',
                    'mssql_patients.PatientID',
                    DB::raw("mssql_schedulemaster.ServiceName as name"),
                    DB::raw("users.name AS intakeCordinator"),
                    'mssql_facilities.FacilityName',
                    'mssql_patients.aoc_facility_id',
                    DB::raw(
                        "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"
                    ),
                    'lob.LOBName',
                    
                    DB::raw("(mssql_schedulemaster.ScheduleDate) as IntakeDate"
                    ),
                    DB::raw('CASE WHEN
                                (mssql_schedulemaster.ActualStart !="0000-00-00 00:00:00")
                            THEN
                                mssql_schedulemaster.ActualStart
                            ELSE
                            mssql_schedulemaster.PlannedStart
                            END as IntakeStartTime'),
                
                    DB::raw("casemanager.name as case_manager")
                );
                $scheduleData->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $scheduleData->leftJoin("mssql_schedulemaster", function ($query) {
                    $query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_admission_visit");
                });
            } else {
                $scheduleData = DB::table("mssql_patients")
                ->select(
                    'mssql_patients.id',
                    'mssql_patients.PatientID',
                    DB::raw("mssql_schedulemaster.ServiceName as name"),
                    DB::raw("users.name AS intakeCordinator"),
                    'mssql_facilities.FacilityName',
                    'mssql_patients.aoc_facility_id',
                    DB::raw(
                        "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"
                    ),
                    'lob.LOBName',
                    
                    DB::raw("(mssql_schedulemaster.ScheduleDate) as IntakeDate"
                    ),
                    DB::raw('CASE WHEN
                                (mssql_schedulemaster.ActualStart !="0000-00-00 00:00:00")
                            THEN
                                mssql_schedulemaster.ActualStart
                            ELSE
                            mssql_schedulemaster.PlannedStart
                            END as IntakeStartTime'),
                
                    DB::raw("casemanager.name as case_manager")
                );
                $scheduleData->leftJoin("mssql_schedulemaster", function ($query) {
                    $query->on("mssql_schedulemaster.CGTaskID", "=", "mssql_patients.intake_admission_visit");
                });
            }
            /*$scheduleData->leftJoin("service_codes_daas", function ($query) {
                $query->on("service_codes_daas.service_id", "=", "mssql_schedulemaster.ServiceCodeID");
            });*/
            $scheduleData->leftJoin("mssql_facilities", function ($query) {
                $query->on("mssql_patients.aoc_facility_id", "=", "mssql_facilities.id");
            });
            /*$scheduleData->leftJoin("mssql_providers", function ($query) {
                $query->on("mssql_patients.CaseManagerID", "=", "mssql_providers.mssqlProviderID");
            });*/
            $scheduleData->leftJoin("users as casemanager", function ($query) {
                $query->on("mssql_patients.ccm_id", "=", "casemanager.id");
            });
            $scheduleData->leftJoin("lob", function ($query) {
                $query->on("mssql_patients.LOBID", "=", "lob.LOBID");
            });
            $scheduleData->leftJoin("users", function ($query) {
                $query->on("mssql_patients.ic_id", "=", "users.id");
            });
            //$scheduleData->whereIn('service_codes_daas.name', ['RA Post Hospital','RA Miscellaneous']);
                     
            $time = strtotime("-1 month", time());
            $monthBack = date("Y-m-d", $time);

            $time = strtotime("+1 month", time());
            $monthForward = date("Y-m-d", $time);

            $scheduleData->whereDate("mssql_schedulemaster.ScheduleDate",">=",$monthBack);
            $scheduleData->whereDate("mssql_schedulemaster.ScheduleDate","<=",$monthForward);
            $data2 = $scheduleData->where(function ($query) use ($term) {
                $query->where('casemanager.name', 'LIKE', "%{$term}%")
                ->orWhere('mssql_patients.Search_Name', 'LIKE', "%{$term}%")
                ->orWhere('users.name', 'LIKE', "%{$term}%");
            })->get();
           
            foreach ($data2 as  $value) {
                $label = '';
                $label .= isset($value->patient_name) && $value->patient_name != '' ? $value->patient_name . ',' : '';
                $label .= isset($value->FacilityName) && $value->FacilityName != '' ? $value->FacilityName . ',' : '';
                $label .= isset($value->IntakeDate) && $value->IntakeDate != '' ?
                H::getFormatedDate($value->IntakeDate) . ',' : '';
                $label = str_replace(',,', ',', $label);
                $label = str_replace(',,', ',', $label);
                $res[] = [
                    'id'=>$value->id,
                    'label'=>trim($label, ','),
                    'date'=>$value->IntakeDate,
                    'client'=>$value->patient_name,
                    'state'=>substr($value->FacilityName,0,2),
                    'branchId'=>$value->aoc_facility_id,
                    'month'=>date('n', strtotime($value->IntakeDate))
                ];
            }
            
            
            

            return response()->json($res);
        } else {
            return response()->json($data);
        }
    }
    
}
