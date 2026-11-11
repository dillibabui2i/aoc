<?php

namespace App\Helpers;

use App\Criteria\ActivityPrnCriteria;
use App\Enums\MssqlRecreturTrackerStatus;
use App\Enums\MssqlRecruiterTrackerLiveIpeaStatus;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class Activity
{
    public function getActivityByStatus($status = '', $request = '',$showCount=0)
    {
        $request = $request->all();
        $varNoDateTimeEntry = '0000-00-00 00:00:00';
        $varNoTimeEntry = '00:00:00';
        $acceptableDateTimeFormat = 'Y-m-d H:i:s';


        $users = H::getLoggedInUser();
        $currentRole = 0;
            $currentRole = H::getHelper('ConstantHelper')->getCurrentRolesByUserId($users['id']);
        $selectAllRecruiter = 'mssql_recruitertracker.*';
        $currentDateTime = \Carbon\Carbon::today();
        $activityDashboard = DB::table('mssql_recruitertracker')
        ->select(DB::raw($selectAllRecruiter),'msqlfac.FacilityName',
        DB::raw(
            "(select concat(DATE_FORMAT(notes.created_at,'%m/%d'),' ',users.name,' commented: ',notes.notes)
            from notes left join users on users.id = notes.created_by
            where entity = 'tracker' and entity_id = mssql_recruitertracker.id
            order by notes.id desc limit 1) as Candidate_Notes"
        ),
        DB::raw(
            "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName)
            AS patient_name"
        ));
        $activityDashboard->leftJoin("mssql_patients", function ($query) {
            $query->on("mssql_recruitertracker.aoc_patient_id", '=', 'mssql_patients.id');
        });
        $activityDashboard->leftJoin("mssql_facilities as msqlfac", function ($query) {
            $query->on("mssql_patients.aoc_facility_id", '=', 'msqlfac.id');
        });

        $filters = [];
        if ($status == 'NO CANDIDATE MATCH' || $status == 'NO PROVIDER MATCH' ) {
            if ($status == 'NO CANDIDATE MATCH') {
                $activityDashboard->where('no_candidate_match', '1');
            }
            if ($status == 'NO PROVIDER MATCH') {
                $activityDashboard->where('no_provider_match', '1');
            }

        } else {
                if (H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew( 'is_recruiting_admin_form')) {
                    if (isset($request['filters'][0]['value']) &&
                    !empty($request['filters'][0]['value']) &&
                    $request['filters'][0]['name'] == 'default') {
                        $allSubOrdinates = H::getHelper('UserPermissionResolver')-> getRAsRecruiters($users['id']);
                        $activityDashboard->whereIn('mssql_recruitertracker.Recruiter', $allSubOrdinates);
                        H::ExcelLog(print_r($allSubOrdinates,true),'testRA.log');
                    }
                } elseif (H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew('is_recruiting_team_leader') ||
                        H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew('is_recruiting_manager')) {
                    if (isset($request['filters'][0]['value']) &&
                    !empty($request['filters'][0]['value']) &&
                    $request['filters'][0]['name'] == 'default') {
                        $allSubOrdinates = H::getHelper('UserPermissionResolver')
                                            ->getAllSubordinatesFromCurrentUser($users['id']);
                        $activityDashboard->whereIn('mssql_recruitertracker.Recruiter', $allSubOrdinates);
                    }
                } else {
                    if (isset($request['filters'][0]['value']) &&
                    !empty($request['filters'][0]['value']) &&
                    $request['filters'][0]['name'] == 'default') {
                        $activityDashboard->where('mssql_recruitertracker.Recruiter', $users['name']);
                    }
                }

        }
        if ($status == 'NEED TO REVIEW NEW CLIENTS') {
            if (LD::checkFeatureFlag('auto-inactivate-recruiter-trackers')){
                $activityDashboard
                    ->where('Candidate_Current_Status', MssqlRecreturTrackerStatus::NEE_TO_REVIEW_NEW_CLIENTS->value)
                    ->where('is_active', true);
            }else{
                $activityDashboard->where('Candidate_Current_Status', 'Need to Review New Clients');
            }
        }

        if ($status == 'NEED TO BE SCHEDULED BY RA') {
            $activityDashboard->where('Candidate_Current_Status', 'Need to be Scheduled by RA');
        }

        if ($status == 'IPEA') {
            if (LD::checkFeatureFlag('clean-active-coverage-process')) {
                $activityDashboard
                    ->active()
                    ->whereNot(function (Builder $query) {
                        $query->where('Client_Name', 'like', '%PRN%')
                            ->where(
                                'live_ipea_status',
                                '=',
                                MssqlRecruiterTrackerLiveIpeaStatus::READY_TO_WORK->value
                            );
                    });
            }

            $activityDashboard
                ->whereRaw("if(IPEA_Outcome='NC/NS',DATE(Status_Changed_Date) < '" . date('Y-m-d') . "','1')");
        }

        if ($status == 'CCRS') {
                $ccm_query = DB::raw("users.name AS ccm_id");
                if(LD::checkFeatureFlag('change-assign-ccr-process')){
                    $ccm_query = DB::raw("IFNULL(users.name, 'Unassigned') AS ccm_id");
                }
                $activityDashboard
                ->select(
                    DB::raw(
                        'concat(mssql_recruitertracker.proposed_ns_date," ",mssql_recruitertracker.proposed_ns_time)
                        as proposed_ns_datetime'
                    ),
                    $ccm_query,'mssql_recruitertracker.proposed_next_step as proposed_ns',
                    DB::raw(
                        '(select created_at from recruiting_schedule
                        where mssql_recruitertracker.id = recruiting_schedule.tracker_id
                        and recruiting_schedule.type_id = 2
                        order by recruiting_schedule.id
                        desc limit 1) AS ccr_requested_date'),
                    DB::raw($selectAllRecruiter),
                    'msqlfac.FacilityName',
                    DB::raw(
                        "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName)
                        AS patient_name"
                    ),
                    DB::raw('(select CASE WHEN
                            notes.notes = "CCR marked complete because candidate is already established with client."
                            THEN "Ready to Schedule Next Steps"
                            ELSE recruiting_schedule.outcome END
                            from recruiting_schedule
                            left join notes on recruiting_schedule.tracker_id = notes.entity_id
                            where mssql_recruitertracker.id = recruiting_schedule.tracker_id
                            and recruiting_schedule.type_id = 2
                            order by recruiting_schedule.id desc, notes.id
                            desc limit 1)
                        AS ccrOutcome'),
                    DB::raw('(select misc3
                        from notes
                        where notes.entity_id=mssql_recruitertracker.id
                        and notes.misc1 = "CCR"
                        and notes.misc2 = "Completed"
                        order by notes.id desc limit 1)
                    AS notesCCROutcome'),
                    DB::raw('(select notes
                        from notes
                        where notes.entity_id=mssql_recruitertracker.id
                        and notes.misc1 = "CCR" and notes.misc2 = "Completed"
                        order by notes.id desc limit 1)
                    AS notesDescription'),
                    DB::raw('(select misc4
                        from notes
                        where notes.entity_id=mssql_recruitertracker.id
                        and notes.misc1 = "CCR" order by notes.id desc limit 1)
                    AS ccr_notes'),
                    DB::raw("(select
                    concat(DATE_FORMAT(notes.created_at,'%m/%d'),' ',users.name,' commented: ',notes.notes)
                    from notes left join users on users.id = notes.created_by
                    where entity = 'tracker' and entity_id = mssql_recruitertracker.id
                    order by notes.id desc limit 1) as Candidate_Notes"));
            $activityDashboard->where('Candidate_Current_Status', 'Clinical Competency Review');

                $activityDashboard->leftJoin("users",function($query){
                    $query->on(
                        "users.id",
                        "=",
                        DB::raw(
                            "IF(
                                mssql_recruitertracker.assigned_ccr <> '' ,
                                mssql_recruitertracker.assigned_ccr,
                                mssql_patients.ccm_id
                            )"
                        )
                    );
                });
        }

        if ($status == 'PEPI') {
                $activityDashboard
                ->whereRaw("if(`PEPI-Outcome`='NC/NS',DATE(Status_Changed_Date) < '".date('Y-m-d')."','1')");
        }

        if ($status == 'WEPI') {
                $activityDashboard
                ->whereRaw("if(`WEPI-Outcome`='NC/NS',DATE(Status_Changed_Date) < '".date('Y-m-d')."','1')");
        }

        if ($status == 'PENDING CANDIDATE STARTS') {
            $activityDashboard->leftJoin("mssql_patientstatus", function ($query) {
                $query->on("mssql_patients.Patient_CurrentStatusID", '=', 'mssql_patientstatus.id');
            });
            $activityDashboard->
            whereIn(
                'Candidate_Current_Status',
                ['Client Match', 'Current Nurse Placed', 'New Nurse Placed', 'Pending Start']
            );
            if (!LD::checkFeatureFlag('date-filter-pending-candidate-starts-bucket')) {
                $activityDashboard
                ->where('Candidate_Start_Date', '>=', $currentDateTime)
                ->where('Candidate_Start_Date', '!=', $varNoDateTimeEntry);
            }

            if (LD::checkFeatureFlag('add-shift-time-to-pending-candidate-start-bucket')) {
                $activityDashboard
                ->addSelect(
                    DB::raw(
                        'DATE_FORMAT(
                            concat(
                                DATE(Candidate_Start_Date),
                                " ",
                                IFNULL(Candidate_Start_Time,"'.$varNoTimeEntry.'")
                            ),
                            "' . config('constant')['mysqlsearchdatetimeformat'] . '"
                        )AS Candidate_Start_Date_Time'
                    )
                );
            }

            if (LD::checkFeatureFlag('document-send-signed-unsigned-information-dashboard-bucket')) {
                $activityDashboard
                ->addSelect(
                    DB::raw(
                        "(
                            select
                            DATE_FORMAT(aoc_documents.send_date,'%m/%d/%Y')
                            from aoc_documents
                            where aoc_documents.tracker_id = mssql_recruitertracker.id
                            order by id desc
                            limit 1
                        ) as last_send_date")
                    );
                $activityDashboard->
                addSelect(
                    DB::raw(
                            "(
                                select
                                IF(aoc_documents.status='executed','Yes','No')
                                from aoc_documents
                                where aoc_documents.tracker_id = mssql_recruitertracker.id
                                order by id desc
                                limit 1
                            ) as is_signed"
                        )
                    );
           }
            $activityDashboard->addSelect('mssql_patientstatus.PatientStatus as PatientStatus');
            $activityDashboard
            ->addSelect(
                DB::raw(
                    'CASE
                        WHEN mssql_patients.Patient_CurrentStatusID = 4
                        THEN
                            (
                                CASE
                                WHEN mssql_patients.CF_GoalSOCDate != "'.$varNoDateTimeEntry.'"
                                THEN DATE_FORMAT(
                                            mssql_patients.CF_GoalSOCDate,
                                            "'.config('constant')['mysqlsearchdateformat'].'"
                                            )
                                ELSE "" END
                            )
                        ELSE ""
                    END as goal_soc_date'
                )
            );
        }

        if ($status == 'CANDIDATE STARTS') {
            $activityDashboard->leftJoin("mssql_patientstatus", function ($query) {
                $query->on("mssql_patients.Patient_CurrentStatusID", '=', 'mssql_patientstatus.id');
            });
            if (LD::checkFeatureFlag('add-time-to-candidate-start-widget')) {
                $activityDashboard
                ->select(
                    DB::raw($selectAllRecruiter),
                    'msqlfac.FacilityName',
                    DB::raw(
                        "(
                            select
                            concat(DATE_FORMAT(notes.created_at,'%m/%d'),' ',users.name,' commented: ',notes.notes)
                            from notes
                            left join users on users.id = notes.created_by
                            where entity = 'tracker'
                            and entity_id = mssql_recruitertracker.id
                            order by notes.id desc
                            limit 1
                        ) as Candidate_Notes"
                    ),
                    DB::raw(
                        "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"
                    ),
                    DB::raw(
                        'DATE_FORMAT(
                            concat(DATE(Candidate_Start_Date), " ", IFNULL(Candidate_Start_Time,"'.$varNoTimeEntry.'")),
                            "' . config('constant')['mysqlsearchdatetimeformat'] . '"
                        ) AS Candidate_Start_Date_Time'
                    )
                );
            }

            $activityDashboard
            ->whereIn(
                'Candidate_Current_Status',
                ['Client Match', 'New Nurse Placed', 'Current Nurse Placed']
            );
            $tempDateTime = date('Y-m-d',strtotime($currentDateTime));
            $tempDateTime.=" 23:59:59";
            $activityDashboard
            ->where('Candidate_Start_Date', '<=', $tempDateTime)
            ->where('Candidate_Start_Date', '!=', $varNoDateTimeEntry);

            if (LD::checkFeatureFlag('document-send-signed-unsigned-information-dashboard-bucket')) {
                $activityDashboard
                ->addSelect(
                    DB::raw(
                        "(
                            select
                            DATE_FORMAT(aoc_documents.send_date,'%m/%d/%Y')
                            from aoc_documents
                            where aoc_documents.tracker_id = mssql_recruitertracker.id
                            order by id desc
                            limit 1
                        ) as last_send_date"
                    )
                );
                $activityDashboard
                ->addSelect(
                    DB::raw(
                        "(
                            select
                            IF(aoc_documents.status='executed','Yes','No')
                            from aoc_documents
                            where aoc_documents.tracker_id = mssql_recruitertracker.id
                            order by id desc
                            limit 1
                        ) as is_signed"
                    )
                );
            }
            $activityDashboard->addSelect('mssql_patientstatus.PatientStatus as PatientStatus');
            $activityDashboard->addSelect(
                DB::raw(
                    'CASE
                        WHEN mssql_patients.Patient_CurrentStatusID = 4
                        THEN (
                            CASE
                                WHEN mssql_patients.CF_GoalSOCDate != "'.$varNoDateTimeEntry.'"
                                THEN
                                    DATE_FORMAT(
                                        mssql_patients.CF_GoalSOCDate,
                                        "'.config('constant')['mysqlsearchdateformat'].'"
                                    )
                                ELSE ""
                            END)
                        ELSE ""
                    END as goal_soc_date'));
        }

        if ($status == 'PRN') {
            $activityDashboard = (new ActivityPrnCriteria())->apply($activityDashboard);
        }

        if ($status == 'SCHOOL SETS UP') {
            if (LD::checkFeatureFlag
            ('show-only-trackers-linked-to-ssu-tracker-id-on-ssu-bucket-daily-activity-widget')) {
                if(LD::checkFeatureFlag('school-set-up-status-column-default-value')){
                    $activityDashboard->addselect(DB::raw("IFNULL(live_status, 'Not Started') as live_status"));
                } else {
                    $activityDashboard->addSelect('school_setup_requests.live_status');
                }
                $activityDashboard->join("school_setup_requests", function ($query) {
                    $query->on("school_setup_requests.tracker_id", '=', 'mssql_recruitertracker.id');
                });
                $activityDashboard->whereNotIn('school_setup_requests.status', ['Completed','Cancelled']);
            } else{
                $activityDashboard->whereDate('School_Set_Up_Due_Date', '<=', date('Y-m-d'))
                ->whereNull('School_Set_Up_Completed')
                ->where('School_Set_Up_Due_Date', '!=', $varNoDateTimeEntry);
            }
            if(LD::checkFeatureFlag('make-ssu-form-view-only')){
                $activityDashboard->addselect('school_setup_requests.id as school_setup_id');
            }
        }

        if ($status == 'EXCEPTION') {
            $activityDashboard->where(function($query){
                $query->orWhere(function($query){
                    $query->where('aoc_patient_id', '0')->orWhereNull("aoc_patient_id");
                });
                $query->orWhere(function($query){
                    $query->where(function($query) {
                        $query->where('aoc_candidate_id', '0')->orWhereNull("aoc_candidate_id");
                    });
                    $query->where(function($query) {
                        $query->where('aoc_provider_id', '0')->orWhereNull("aoc_provider_id");
                    });

                });
            });
            $activityDashboard->where('is_active','1')->where('Candidate_Current_Status',"!=",'Inactive');
        }

            if(isset($request['filters'])) {
                foreach($request['filters'] as $fl) {
                    if(!empty($fl['value'])) {
                        $filters[$fl['name']][] = $fl['value'];
                    }

                }
            }
            if (LD::checkFeatureFlag('date-filter-pending-candidate-starts-bucket')) {
                if ($status == 'PENDING CANDIDATE STARTS') {
                    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                        $activityDashboard
                        ->whereBetween(
                            'Candidate_Start_Date',
                            [
                                date($acceptableDateTimeFormat,
                                strtotime($filters['from_date'][0] . $varNoTimeEntry)),
                                date($acceptableDateTimeFormat,
                                strtotime($filters['to_date'][0] . '23:59:59'))
                            ]
                        );
                        unset($filters['from_date']);
                        unset($filters['to_date']);
                    } else {
                        $tempDateTime = date('Y-m-d',strtotime($currentDateTime));
                        $tempDateTime.=" 23:59:59";
                        $activityDashboard
                        ->where('Candidate_Start_Date', '>=', $tempDateTime)
                        ->where('Candidate_Start_Date', '!=', $varNoDateTimeEntry);
                    }
                }
            }
            if($status == 'IPEA'){
                    if(isset($filters['ipea_all']) && $filters['ipea_all'][0] == 'on'){
                        $activityDashboard->where('Candidate_Current_Status', 'IPEA - Scheduled');
                    }else{
                        $activityDashboard
                        ->whereIn(
                            'Candidate_Current_Status',
                            ['IPEA - Completed', 'IPEA - Scheduled', 'IPEA - Pending Orientation']
                        );
                            $activityDashboard
                            ->whereDate('IPEA_Date', '<=', date('Y-m-d'))
                            ->where('IPEA_Date', '!=', $varNoDateTimeEntry);
                    }
                    if (isset($filters['ipea_all']) &&
                    (!empty($filters['from_ipea_date']) &&
                    !empty($filters['to_ipea_date']))) {
                        $activityDashboard
                        ->whereBetween('IPEA_Date',
                            [
                                date($acceptableDateTimeFormat,
                                strtotime($filters['from_ipea_date'][0] . $varNoTimeEntry)),
                                date($acceptableDateTimeFormat,
                                strtotime($filters['to_ipea_date'][0] . '23:59:59'))
                            ]
                        );
                    }
                    if(LD::checkFeatureFlag('prn-process-active-coverage-bucket-fixes')) {

                        $activityDashboard->leftJoin("mssql_providers", function ($query) {
                            $query->on("mssql_recruitertracker.aoc_provider_id", '=', 'mssql_providers.id');
                        });

                        $activityDashboard->leftJoin("mssql_employmentstatus", function ($query) {
                            $query->on("mssql_employmentstatus.EmploymentStatusID", '=', 'mssql_providers.EmploymentStatusID');
                        });

                        $activityDashboard->where(function($query){
                            $query->whereRaw('CASE
                                                WHEN (mssql_employmentstatus.EmploymentStatus = \'Active Coverage\')
                                                THEN ifnull(live_ipea_status,"") != \'Ready to work\'
                                                ELSE TRUE
                                            END');
                        });
                    }

                unset($filters['ipea_all']);
                unset($filters['from_ipea_date']);
                unset($filters['to_ipea_date']);
            }
            if($status == 'PEPI'){
                    if(isset($filters['pepi_all']) && $filters['pepi_all'][0] == 'on'){
                        $activityDashboard->where('Candidate_Current_Status', 'PEPI - Scheduled');

                    }else{
                        $activityDashboard
                        ->whereIn(
                            'Candidate_Current_Status',
                            ['PEPI - Completed', 'PEPI - Scheduled', 'PEPI - Rescheduled']
                        );
                        $activityDashboard
                        ->whereDate('PEPI_Date', '<=', date('Y-m-d'))
                        ->where('PEPI_Date', '!=', $varNoDateTimeEntry);
                    }
                    if (isset($filters['pepi_all']) &&
                     (!empty($filters['from_pepi_date']) &&
                     !empty($filters['to_pepi_date']))) {
                        $activityDashboard
                        ->whereBetween(
                            'PEPI_Date',
                            [
                                date($acceptableDateTimeFormat,
                                strtotime($filters['from_pepi_date'][0] . $varNoTimeEntry)),
                                date($acceptableDateTimeFormat,
                                strtotime($filters['to_pepi_date'][0] . '23:59:59'))
                            ]
                        );
                    }
                unset($filters['pepi_all']);
                unset($filters['from_pepi_date']);
                unset($filters['to_pepi_date']);
            }
            if($status == 'WEPI'){
                    if(isset($filters['wepi_all']) && $filters['wepi_all'][0] == 'on'){
                        $activityDashboard->where('Candidate_Current_Status', 'WEPI - Scheduled');
                    }else{
                        $activityDashboard
                        ->whereIn(
                            'Candidate_Current_Status',
                            ['WEPI - Completed', 'WEPI - Scheduled', 'WEPI - Rescheduled']
                        );
                        $activityDashboard
                        ->whereDate('WEPI_Date', '<=', date('Y-m-d'))
                        ->where('WEPI_Date', '!=', $varNoDateTimeEntry);
                    }
                    if (isset($filters['wepi_all']) &&
                     (!empty($filters['from_wepi_date']) &&
                      !empty($filters['to_wepi_date']))) {
                        $activityDashboard
                        ->whereBetween(
                            'WEPI_Date',
                            [
                                date($acceptableDateTimeFormat,
                                strtotime($filters['from_wepi_date'][0] . $varNoTimeEntry)),
                                date($acceptableDateTimeFormat,
                                strtotime($filters['to_wepi_date'][0] . '23:59:59'))
                            ]
                        );
                    }
                unset($filters['wepi_all']);
                unset($filters['from_wepi_date']);
                unset($filters['to_wepi_date']);
            }
            if($status == 'TRAINING SHIFT'){
                    if(isset($filters['trainig_all']) && $filters['trainig_all'][0] == 'on'){
                        $activityDashboard->where('Candidate_Current_Status', 'Training Shift Scheduled');
                    }else{
                        $activityDashboard->where('Candidate_Current_Status', 'Training Shift Scheduled');
                        $activityDashboard
                        ->whereDate('Training_Shift', '<=', date('Y-m-d'))
                        ->where('Training_Shift', '!=', $varNoDateTimeEntry);
                    }
                    if (isset($filters['trainig_all']) &&
                     (!empty($filters['from_trainig_date']) &&
                      !empty($filters['to_trainig_date']))) {
                        $activityDashboard
                        ->whereBetween(
                            'Training_Shift',
                            [
                                date($acceptableDateTimeFormat,
                                strtotime($filters['from_trainig_date'][0] . $varNoTimeEntry)),
                                date($acceptableDateTimeFormat,
                                strtotime($filters['to_trainig_date'][0] . '23:59:59'))
                            ]
                        );
                    }
                unset($filters['trainig_all']);
                unset($filters['from_trainig_date']);
                unset($filters['to_trainig_date']);


                if(LD::checkFeatureFlag('conversation-icon-on-tracker')){
                    $activityDashboard->leftJoin("mssql_providers", function ($query) {
                        $query->on("mssql_recruitertracker.aoc_provider_id", '=', 'mssql_providers.id');
                    });
                    $activityDashboard->leftJoin("candidates", function ($query) {
                        $query->on("mssql_recruitertracker.aoc_candidate_id", '=', 'candidates.id');
                    });
                    $activityDashboard
                    ->addSelect(
                        DB::raw(
                            'CASE
                                WHEN mssql_recruitertracker.aoc_provider_id != 0
                                THEN mssql_providers.fullName
                                ELSE candidates.fullName
                            END as fullName'
                        ),
                        DB::raw(
                            'CASE
                                WHEN mssql_recruitertracker.aoc_provider_id != 0
                                THEN mssql_providers.Mobile
                                ELSE candidates.mobile
                            END as mobile'
                        )
                    );
                }

            }
            foreach($filters as $fkey => $fval) {

                if($fkey == 'excludeprn') {
                    $activityDashboard->where(function($query) {
                        $query->where('mssql_patients.isPRN','<>',1);
                        $query->orWhereNull('mssql_patients.isPRN');
                    });
                    continue;
                }

                if($fkey!='default') {

                    if($fkey=='state') {

                        if(H::getHelper('CommonDatatableFilters')->isGeneralFilter($fkey)){
                            $activityDashboard = H::getHelper('CommonDatatableFilters')
                                                ->generateQuery($activityDashboard,$fkey,$fval);
                        }
                    } else {
                        if(H::getHelper('CommonDatatableFilters')->isGeneralFilter($fkey)){
                            $activityDashboard = H::getHelper('CommonDatatableFilters')
                            ->generateQuery($activityDashboard,$fkey,$fval);
                        } else {
                                    if($fkey == 'Payer' || $fkey == 'payer_group') {
                                        $activityDashboard->where(function($query) use ($fval, $fkey) {
                                            foreach($fval as $val) {
                                                $query->orWhere("mssql_patients.".$fkey,'=',$val);
                                            }
                                        });
                                    } elseif($fkey == 'user_id') {
                                        $activityDashboard->leftJoin("users",function($query) {
                                            $query->on("mssql_recruitertracker.user_id",'=','users.id');
                                        });
                                        $activityDashboard->where(function($query) use ($fval) {
                                            foreach($fval as $val) {
                                                $query->orWhere("users.recruiting_admin",'=',$val);
                                            }
                                        });
                                    }elseif ($fkey == 'region'){
                                        $activityDashboard->where(function ($activityDashboard) use ($fval) {
                                                $activityDashboard->whereIn("msqlfac.region", $fval);
                                        });
                                    }elseif ($fkey == 'live_ipea_status'){
                                        $activityDashboard->where(function($query) use ($fval) {
                                            foreach($fval as $val) {
                                                if($val != 'pending'){
                                                    $query->orWhere("mssql_recruitertracker.live_ipea_status",'=',$val);
                                                }else{
                                                    $query
                                                    ->orWhere(
                                                        "mssql_recruitertracker.live_ipea_status",
                                                        'like',
                                                        'pending%'
                                                    );
                                                }
                                            }
                                        });
                                    } else {
                                        $activityDashboard->where(function($query) use ($fval, $fkey) {
                                            foreach($fval as $val) {
                                                $query->orWhere("mssql_recruitertracker.".$fkey,'=',$val);
                                            }
                                        });
                                    }
                        }
                    }

                }
            }
        if (in_array($status, ['NEED TO BE SCHEDULED BY RA', 'IPEA', 'CCRS', 'PEPI', 'WEPI', 'TRAINING SHIFT']) &&
         LD::checkFeatureFlag('recruiting-dashboard-add-column-client-status')) {
            $activityDashboard->addSelect('PatientStatus');
            $activityDashboard->leftJoin("mssql_patientstatus", function ($query) {
                $query->on("mssql_patients.Patient_CurrentStatusID", '=', 'mssql_patientstatus.PatientStatusID');
            });
        }
        if (LD::checkFeatureFlag('show-only-non-intake-records-recruting-buckets')) {
            $activityDashboard->whereRaw("IFNULL(aoc_intake_id,0) <= 0");
        }
        if($showCount) {
                if(LD::checkFeatureFlag('dashboard-past-due-count')) {
                    $activityDashboard2 = clone $activityDashboard;
                    $data = $activityDashboard2->get();
                    $typeArray = [
                        'IPEA'=>'IPEA_Date',
                        'CCRS'=>'Clinical_Competency_Review',
                        'PEPI'=>'PEPI_Date',
                        'WEPI'=>'WEPI_Date',
                        'PENDING CANDIDATE STARTS'=>'Candidate_Start_Date',
                        'CANDIDATE STARTS'=>'Candidate_Start_Date',
                        'TRAINING SHIFT'=>'Training_Shift',
                        'PRN'=>'Candidate_Start_Date',
                        'SCHOOL SETS UP'=>'School_Set_Up_Due_Date'
                    ];
                    $totalCountData = $data->pluck('Total_Hours')->all();
                    $totalHr =  number_format(array_sum($totalCountData), 0);
                    if (isset($typeArray[$status])) {
                        $filtered_data = $data->filter(function ($data) use ($typeArray, $status) {
                            return date('Y-m-d',strtotime($data->{$typeArray[$status]})) < date('Y-m-d');
                        });
                        $pastDuetotalHr =  number_format(array_sum($filtered_data->pluck('Total_Hours')->all()), 0);
                        return '<span>
                                    ('
                                        .$data->count().' | ' . $totalHr . '
                                        <span style="text-transform: initial;">hrs</span>
                                    )
                                </span>'
                                . '<span style="margin-left: 12px">
                                    ('
                                        .$filtered_data->count().' | ' . $pastDuetotalHr . '
                                        <span style="text-transform: initial;">hrs Past Due</span>
                                    )
                                </span>';
                    } elseif($status == 'NEED TO REVIEW NEW CLIENTS' || $status == 'NEED TO BE SCHEDULED BY RA') {
                        $filtered_data = $data->filter(function ($data) use($acceptableDateTimeFormat) {
                            return round(
                                        (strtotime(date($acceptableDateTimeFormat))
                                        -strtotime($data->Status_Changed_Date))/3600,
                                        1
                                        ) > 1;
                        });
                        $pastDuetotalHr =  number_format(array_sum($filtered_data->pluck('Total_Hours')->all()), 0);
                        return '<span>
                            ('
                                .$data->count().' | ' . $totalHr . '
                                <span style="text-transform: initial;">hrs</span>
                            )
                        </span>' . '
                        <span style="margin-left: 12px">
                        ('
                            .$filtered_data->count().' | ' . $pastDuetotalHr . '
                            <span style="text-transform: initial;">hrs Past Due</span>
                        )</span>';
                    } else {
                        return '(
                                    '.$data->count().' | ' . $totalHr . '
                                    <span style="text-transform: initial;">hrs</span>
                                )';
                    }
                } else {
                    $totalCountData = $activityDashboard->pluck('Total_Hours')->all();
                    $totalHr =  number_format(array_sum($totalCountData), 0);
                    return '(
                                '.$activityDashboard->count().' | ' . $totalHr . '
                                <span style="text-transform: initial;">hrs</span>
                            )';
                }
        }
        H::ExcelLog($status.' activityDashboard: ' . $activityDashboard->toSql(), 'dailyActivityDashboard.log');
        return $activityDashboard->get();
    }

    public function getDataTableHtml($type)
    {
        $fieldArray = [];
        $users = H::getLoggedInUser();
        $currentRole = $users['role'];
        switch ($type) {

            case 'NEED TO REVIEW NEW CLIENTS':
                if(LD::checkFeatureFlag('add-discipline-filed-into-needs-to-review-new-clients-widget')){
                    $fieldArray = ['id'=>'ID','Recruiter'=>'Recruiter',
                    'FacilityName'=>'Branch Location',
                    'Candidate'=>'Candidate',
                    'Skillset' => 'Discipline',
                    'Status_Changed_Date'=>'Date Added',
                    'Candidate_Notes'=>'Candidate Notes'];
                }else{
                    $fieldArray = ['id'=>'ID','Recruiter'=>'Recruiter',
                    'FacilityName'=>'Branch Location',
                    'Candidate'=>'Candidate',
                    'Status_Changed_Date'=>'Date Added',
                    'Candidate_Notes'=>'Candidate Notes'];
                }
                if (LD::checkFeatureFlag('add-new-outcomes-to-new-to-review-new-clients-bucket')) {
                    $fieldArray['attempt_count'] = 'Outcome';
                }
                break;

            case 'NEED TO BE SCHEDULED BY RA':
                $fieldArray = ['id'=>'ID','Recruiter'=>'Recruiter',
                'FacilityName'=>'Branch Location',
                'Candidate'=>'Candidate',
                'patient_name'=>'Client',
                'Status_Changed_Date'=>'Date Added',
                'Candidate_Notes'=>'Candidate Notes'];
                break;

            case 'IPEA':
                $fieldArray = [
                    'id'=>'ID',
                    'Recruiter'=>'Recruiter',
                    'FacilityName'=>'Branch Location',
                    'Candidate'=>'Candidate',
                    'patient_name'=>'Client',
                    'IPEA_Date'=>'IPEA Date',
                    'IPEA_Time'=>'IPEA Time',
                    'live_ipea_status'=>'IPEA Status',
                    'Candidate_Notes'=>'Candidate Notes'
                ];
                break;

            case 'CCRS':
                if (LD::checkFeatureFlag('ccr-changes')) {
                    $fieldArray = [
                        'id'=>'ID',
                        'Recruiter'=>'Recruiter',
                        'FacilityName'=>'Branch Location',
                        'Candidate'=>'Candidate',
                        'patient_name'=>'Client',
                        'ccr_requested_date' =>'CCR Requested Date/Time',
                        'proposed_next_step'=>'Proposed Next Step',
                        'proposed_ns_datetime'=>'Proposed Next Step Date/Time',
                        'Candidate_Notes'=>'Candidate Notes',
                        'ccrOutcome'=>"CCR Status",
                        'ccr_notes'=>'CCR Notes'
                    ];
                } else {
                    $fieldArray = [
                        'id'=>'ID',
                        'Recruiter'=>'Recruiter',
                        'FacilityName'=>'Branch Location',
                        'Candidate'=>'Candidate',
                        'ccr_requested_date' =>'CCR Requested Date/Time',
                        'proposed_next_step'=>'Proposed Next Step',
                        'proposed_ns_datetime'=>'Proposed Next Step Date/Time',
                        'ccrOutcome'=>"CCR Status",
                        'Candidate_Notes'=>'Candidate Notes'
                    ];
                }

                    $new = [
                        'ccm_id'=>'CCM',
                    ];
                    $keys = array_keys( $fieldArray );
                    $index = array_search( 'Candidate_Notes', $keys );
                    $pos = false === $index ? count( $fieldArray ) : $index + 1;
                    $fieldArray =  array_merge(
                                array_slice( $fieldArray, 0, $pos ), $new, array_slice( $fieldArray, $pos )
                            );

                break;

            case 'PEPI':
                $fieldArray = [
                'id'=>'ID',
                'Recruiter'=>'Recruiter',
                'FacilityName'=>'Branch Location',
                'Candidate'=>'Candidate',
                'patient_name'=>'Client',
                'PEPI_Date'=>'PEPI Date',
                'PEPI_Time'=>'PEPI Time',
                'PEPI-Outcome'=>'PEPI Outcome',
                'Candidate_Notes'=>'Candidate Notes',
                 ];
                 break;

            case 'WEPI':
                $fieldArray = [
                'id'=>'ID',
                'Recruiter'=>'Recruiter',
                'FacilityName'=>'Branch Location',
                'Candidate'=>'Candidate',
                'patient_name'=>'Client',
                'WEPI_Date'=>'WEPI Date',
                'WEPI_Time'=>'WEPI Time',
                'WEPI-Outcome'=>'WEPI Outcome',
                'Candidate_Notes'=>'Candidate Notes',
                ];
                break;

            case 'PENDING CANDIDATE STARTS':
                if(LD::checkFeatureFlag('document-send-signed-unsigned-information-dashboard-bucket')) {
                    $fieldArray = [
                        'id'=>'ID',
                        'Recruiter'=>'Recruiter',
                        'FacilityName'=>'Branch Location',
                        'Candidate'=>'Candidate',
                        'Total_Hours'=>'Total Hours',
                        'patient_name'=>'Client',
                        'PatientStatus'=>"Client Status",
                        'goal_soc_date'=>'Goal SOC Date',
                        'Candidate_Start_Date' => 'Candidate Start Date',
                        'Candidate_Notes'=>'Candidate Notes',
                        'is_signed'=>'Pay Rate Agreement Signed',
                        'last_send_date' => 'Last Date Sent'
                    ];

                } else {
                        $fieldArray = [
                            'id'=>'ID',
                            'Recruiter'=>'Recruiter',
                            'FacilityName'=>'Branch Location',
                            'Candidate'=>'Candidate',
                            'Total_Hours'=>'Total Hours',
                            'patient_name'=>'Client',
                            'PatientStatus'=>"Client Status",
                            'goal_soc_date'=>'Goal SOC Date',
                            'Candidate_Start_Date' => 'Candidate Start Date',
                            'Candidate_Notes'=>'Candidate Notes',
                        ];
                }
                if (LD::checkFeatureFlag('add-shift-time-to-pending-candidate-start-bucket')) {
                    if (array_key_exists('Candidate_Start_Date', $fieldArray)) {
                        $val = $fieldArray['Candidate_Start_Date'];
                        $fieldArray = array_slice(
                            $fieldArray,
                            0,
                            array_search('Candidate_Start_Date', array_keys($fieldArray)),
                            true
                        )
                        +
                        array("Candidate_Start_Date_Time" => $val)
                        +
                        array_slice(
                            $fieldArray,
                            array_search('Candidate_Start_Date', array_keys($fieldArray)) + 1,
                            count($fieldArray) - 1,
                            true
                        );
                    }
                }
                break;

            case 'CANDIDATE STARTS':

                if(LD::checkFeatureFlag('document-send-signed-unsigned-information-dashboard-bucket')) {
                    $fieldArray = [
                        'id'=>'ID',
                        'Recruiter'=>'Recruiter',
                        'FacilityName'=>'Branch Location',
                        'Candidate'=>'Candidate',
                        'patient_name'=>'Client',
                        'PatientStatus'=>"Client Status",
                        'goal_soc_date'=>'Goal SOC Date',
                        'Candidate_Start_Date_Time' => 'Candidate Start Date',
                        'Candidate_Notes'=>'Candidate Notes',
                        'is_signed'=>'Pay Rate Agreement Signed',
                        'last_send_date' => 'Last Date Sent'
                    ];

                } else {
                    if (LD::checkFeatureFlag('add-time-to-candidate-start-widget')) {
                        $fieldArray = [
                            'id'=>'ID',
                            'Recruiter'=>'Recruiter',
                            'FacilityName'=>'Branch Location',
                            'Candidate'=>'Candidate',
                            'patient_name'=>'Client',
                            'PatientStatus'=>"Client Status",
                            'goal_soc_date'=>'Goal SOC Date',
                            'Candidate_Start_Date_Time' => 'Candidate Start Date',
                            'Candidate_Notes'=>'Candidate Notes',
                        ];
                    } else {
                        $fieldArray = [
                            'id'=>'ID',
                            'Recruiter'=>'Recruiter',
                            'FacilityName'=>'Branch Location',
                            'Candidate'=>'Candidate',
                            'patient_name'=>'Client',
                            'PatientStatus'=>"Client Status",
                            'goal_soc_date'=>'Goal SOC Date',
                            'Candidate_Start_Date' => 'Candidate Start Date',
                            'Candidate_Notes'=>'Candidate Notes',
                        ];
                    }
                }
                break;

            case 'TRAINING SHIFT':
                $fieldArray = ['id'=>'ID',
                'Recruiter'=>'Recruiter',
                'FacilityName'=>'Branch Location',
                'Candidate'=>'Candidate',
                'patient_name'=>'Client',
                'Training_Shift'=>'Training Shift',
                'Training_Shift_Time'=>'Shift Time',
                'Candidate_Notes'=>'Candidate Notes',
                    ];
                break;

            case 'PRN':
                if(LD::checkFeatureFlag('prn-bucket-modification-6736')){
                    $fieldArray = ['id'=>'ID',
                    'Recruiter'=>'Recruiter',
                    'FacilityName'=>'Branch Location',
                    'Candidate'=>'Candidate',
                    'IPEA_Date'=>'IPEA Date',
                    'Candidate_Current_Status'=>'Candidate Current Status',
                    'Candidate_Start_Date' => 'Candidate Start Date',
                    'Hours_Worked'=>'Hours Worked',
                    'Bonus_Paid_Date'=>'Bonus Paid Date',
                    'Candidate_Notes'=>'Candidate Notes',
                        ];
                } else {
                    $fieldArray = ['id'=>'ID',
                    'Recruiter'=>'Recruiter',
                    'FacilityName'=>'Branch Location',
                    'Candidate'=>'Candidate',
                    'Candidate_Current_Status'=>'Candidate Current Status',
                    'Candidate_Start_Date' => 'Candidate Start Date',
                    'Candidate_Notes'=>'Candidate Notes',
                        ];
                }
                break;

            case 'SCHOOL SETS UP':
                $fieldArray = ['id'=>'ID',
                'Recruiter'=>'Recruiter',
                'FacilityName'=>'Branch Location',
                'Candidate'=>'Candidate',
                'patient_name'=>'Client',
                'Candidate_Current_Status'=>'Candidate Current Status',
                'School_Set_Up_Due_Date'=>'School Set Up Due Date',
                'Candidate_Notes'=>'Candidate Notes',
                    ];
                    if (LD::checkFeatureFlag
                    ('show-only-trackers-linked-to-ssu-tracker-id-on-ssu-bucket-daily-activity-widget')) {
                        $fieldArray['live_status'] = 'Status';
                    }
                break;
            case 'NO CANDIDATE MATCH':
                $fieldArray = ['id'=>'ID',
                'FacilityName'=>'Branch Location',
                'Recruiter'=>'Recruiter',
                'Candidate'=>'Candidate',
                'Clinician_ID'=>'Clinician ID',
                'ProviderID' => 'Provider Id',
                'application_date'=>'Application Date',
                ];
                break;

            case 'EXCEPTION':
            case 'NO PROVIDER MATCH':
                $fieldArray = ['id'=>'ID',
                    'FacilityName'=>'Branch Location',
                    'Recruiter'=>'Recruiter',
                    'Candidate'=>'Candidate',
                    'Clinician_ID'=>'Clinician ID',
                    'ProviderID' => 'Provider Id',
                    'application_date'=>'Application Date',
                    ];
                break;
            default:
                break;
        }

        if (in_array($type, ['NEED TO BE SCHEDULED BY RA', 'IPEA', 'CCRS', 'PEPI', 'WEPI', 'TRAINING SHIFT']) &&
         LD::checkFeatureFlag('recruiting-dashboard-add-column-client-status')) {
            if (array_key_exists('patient_name', $fieldArray)) {
                $fieldArray = array_slice(
                    $fieldArray,
                    0,
                    array_search('patient_name', array_keys($fieldArray)) + 1,
                    true
                )
                +
                array("PatientStatus" => 'Client Status')
                +
                array_slice(
                    $fieldArray,
                    array_search('patient_name', array_keys($fieldArray)),
                    count($fieldArray) - 1,
                    true
                ) ;
            }
        }

        $norwap = 1;

        $tableHeading = '';
        $keyArray = [];
        $jsonField = '';
        foreach ($fieldArray as $key => $value) {
            $tableHeading .= '<th>' . $value . '</th>';
            $visible=true;
            if($key=='id') {
                $visible = false;
            }
            if(LD::checkFeatureFlag('prn-bucket-modification-6736')){
                if($key=='IPEA_Date' ||
             $key == 'PEPI_Date' ||
              $key == 'Status_Changed_Date' ||
               $key == 'Candidate_Start_Date' ||
                $key == 'application_date' ||
                 $key == 'proposed_ns_datetime' ||
                  $key == 'ccr_requested_date' ||
                  $key == 'Bonus_Paid_Date'){
                $keyArray[] = [
                    "data"=>[
                        '_'=>$key.'.display',
                        'sort'=>$key.'.timestamp'
                    ],
                    "name"=>$key.'.timestamp',
                    "searchable"=>false
                ];
             } else {
            $keyArray[] = ['data'=>$key,'name'=>$key,'visible'=>$visible,"searchable"=>true];
            }

            } else {
                if($key=='IPEA_Date' ||
                $key == 'PEPI_Date' ||
                 $key == 'Status_Changed_Date' ||
                  $key == 'Candidate_Start_Date' ||
                   $key == 'application_date' ||
                    $key == 'proposed_ns_datetime' ||
                     $key == 'ccr_requested_date'){
                   $keyArray[] = [
                       "data"=>[
                           '_'=>$key.'.display',
                           'sort'=>$key.'.timestamp'
                       ],
                       "name"=>$key.'.timestamp',
                       "searchable"=>false
                   ];
                } else {
               $keyArray[] = ['data'=>$key,'name'=>$key,'visible'=>$visible,"searchable"=>true];
               }
            }

        }
        $keyArray[count($keyArray)] = ['data'=>'Action'];
        $tableHeading .= '<th>Action</th>';
        $jsonField = json_encode($keyArray);
        $defaultSsort = 3;
        $defaultOrder = "desc";
        if($type=='PENDING CANDIDATE STARTS'){
            $defaultSsort = 5;
            $defaultOrder='asc';
        }

        $url = route('dashboard.activitydata');
        $html = '<div class="table-responsive">
                    <input type="hidden"
                        name="reload-'.str_replace(' ', '_', $type).'"
                        id="reload-'.str_replace(' ', '_', $type).'"
                        value="1"
                    />
					<table
                        class="table table-bordered"
                        id="dataTable-' . str_replace(' ', '_', $type) . '"
                        width="100%"
                        cellspacing="0">
                        <thead>
                            <tr>
                                ' . $tableHeading . '
                            </tr>
                        </thead>
                    </table>
                </div>';
        $formVar = '';
        if (LD::checkFeatureFlag('date-filter-pending-candidate-starts-bucket') &&
         $type == 'PENDING CANDIDATE STARTS') {
            $formVar = ', #pendingcandidatestarts';
        } elseif (LD::checkFeatureFlag('add-ipea-status-filter-IPEA-bucket') && $type == 'IPEA') {
            $formVar = ', #daily_activity_ipea';
        }elseif(LD::checkFeatureFlag('add-ipea-status-filter-IPEA-bucket') && $type == 'PEPI'){
            $formVar = ', #daily_activity_pepi';
        }elseif(LD::checkFeatureFlag('add-ipea-status-filter-IPEA-bucket') && $type == 'WEPI'){
            $formVar = ', #daily_activity_wepi';
        }elseif(LD::checkFeatureFlag('add-ipea-status-filter-IPEA-bucket') && $type == 'TRAINING SHIFT'){
            $formVar = ', #daily_activity_trainig';
        }
        $html .= '<script type="text/javascript">
            var dataTableDelay = 1000;
            function '.str_replace(' ', '_', $type).'(){
                var ' . str_replace(' ', '_', $type) . ' = $("#dataTable-' . str_replace(' ', '_', $type) . '").DataTable({
                    "order": [
                        ["'.$defaultSsort.'", "'.$defaultOrder.'"]
                    ],
                    "columnDefs": [
                        { className: "dt-nowrap", "targets": [' . $norwap . '] },
                        { className: "dt-nowrap", "targets": [-1] },
                    ],
                    "ajax": {
                        "url": "' . $url . '?type=' . $type . '",
                        data: function(d) {
                            d.filters = $("#dashboardactivity '.$formVar.'").serializeArray();
                            if (window.GlobalLocationFilter) {
                                window.GlobalLocationFilter.applyToParams(d, "#dataTable-' . str_replace(' ', '_', $type) . '");
                            }
                        },
                    },
                    "deferRender": true,
                    "processing": true,
                    "serverSide": true,
                    "columns": ' . $jsonField . ',
                    "drawCallback": function (oSettings, json) {
                    ';
                            $html .= '$("#' . str_replace(' ', '_', $type) .
                            '-counter  ").html(oSettings.json.counthours);';
                    $html .= '},
                    "initComplete": function(settings, json) {

                        jQuery("#dataTable-' . str_replace(' ', '_', $type) . '_filter input").off("keyup.DT input.DT");
                        var searchDelay = null;
                        jQuery("#dataTable-' . str_replace(' ', '_', $type) . '_filter input").on("keyup", function(e) {

                            var search = jQuery("#dataTable-' . str_replace(' ', '_', $type) . '_filter input").val();

                            clearTimeout(searchDelay);
                            searchDelay = setTimeout(function() {
                                if (search != null && search.length >=3)
                                ' . str_replace(' ', '_', $type) . '.search(search).draw();
                                if (search.length==0 && e.keyCode==8)
                                ' . str_replace(' ', '_', $type) . '.search(search).draw();
                            }, dataTableDelay);
                        });
                    },

                });
            }
        </script>';
        if ($type == 'IPEA') {
            $html.='<script> function reloadIPEA() {

                     if($("#reload-IPEA").val()==0) {
                	    $("#dataTable-' . str_replace(' ', '_', $type) . '").DataTable().ajax.reload(null, false);
                        setTimeout(reloadIPEA,60000);
                     } else {
                        console.log("ipea table not loaded")
                     }
                    }
			$(document).ready(function(){	setTimeout(reloadIPEA,60000);});
			</script>';
        }
        return $html;
    }
}
