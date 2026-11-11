<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Helpers\Notifications;
use App\Notes;
use App\NeedToHireAccounts;
use App\MssqlPatients;

class NotesController extends Controller
{
    //

    /**
     * Show the User list.
     */
    public function openNotes(Request $request)
    {
        return view('notes.popup',['data'=>$request->all()]);
    }

    public function openEmailTemplate(Request $request){
        $data = H::getHelper('ActivityNotes')->getEmailContent($request->input('id'));
        return view('recruiterTracker.emailpopup',['data'=>$data]);
    }

    public function getClientHistory(Request $request) {
        $requestedIds  = [$request->input("id")];
        if(LD::checkFeatureFlag('client-new-changes')) {
            H::ExcelLog("Inside FF", 'getClientHistory.log');
            H::ExcelLog("mssql_patients.id-".$request->input("id"), 'getClientHistory.log');
            $dischargedRecords = MssqlPatients::select("mssql_patients.id","mssql_patients.PatientAccount")
            ->where("mssql_patients.id",$request->input("id"))->first();
            H::ExcelLog("mssql_patients.PatientAccount-".$dischargedRecords->PatientAccount, 'getClientHistory.log');
            $otherRecords = MssqlPatients::select("mssql_patients.id","mssql_patients.PatientAccount")
            ->where("mssql_patients.PatientAccount",$dischargedRecords->PatientAccount)
            ->OrderBy('mssql_patients.created_at','DESC')->get()->toArray();
            $requestedIds = array_column($otherRecords,'id');
        }
        H::ExcelLog(print_r($requestedIds, true), 'getClientHistory.log');
        $comments = H::getHelper("ActivityNotes")->getActivityNotesByClient($requestedIds);
        $schedule = H::getHelper("ActivityNotes")->getRecruitingScheduleByClient($requestedIds);
        $trackerActivity = H::getHelper("ActivityNotes")->getActivityNotesByTypeAndClient($requestedIds);
        $email = H::getHelper("ActivityNotes")->getEmailNotesByClient($requestedIds);
        $nth = $social = [];
        if(LD::checkFeatureFlag('show_notes_on_client_and_NTH')){
            $nth = H::getHelper("ActivityNotes")->getNthNotesByClientId($requestedIds);
            $social = H::getHelper("ActivityNotes")->getSocialNotesByClientId($requestedIds);
        }

        $data = [];
        $merge = [];
        foreach($comments as $note) {
            $data["comment"][] = ['type'=>'comment',
            'id'=>$note->id,
            'name' => $note->name,
            'notes'=>$note->notes,
            'created_at'=>$note->created_at];
        }

        foreach($schedule as $note) {
            $name = "";
            $appWith = '';
            if(!empty($note->candidateName)) {
                $name = $note->candidateName;
                $appWith = 'C';
            } elseif(!empty($note->providerName)) {
                $name = $note->providerName;
                $appWith = 'P';
            }

            $data["schedule"][] = ['id'=>$note->id,'type'=>'schedule','name' => $note->name,
            'appointment_date'=>$note->appointment_date,
            'appointment_time'=>$note->appointment_time,
            'outcome'=>$note->outcome,
            'branch'=> $note->branch,
            'schedule_type'=>$note->type,
            'patient_name'=>$name,
            'app_with'=> $appWith,
            'created_at'=>$note->created_at];
            if(LD::checkFeatureFlag('shift-type-ccr-client-acitivty-notes')) {
                $shiftType = is_null($note->coverage_id) ? 0 : $note->coverage_id;
                $data['schedule'][count($data['schedule']) - 1]['shift_type'] = ($shiftType == 0) ? 'Perm' : 'Coverage';
            }
        }

        foreach($trackerActivity as $note) {

            $data["activity"][] = ['type'=>'activity',
            'id'=>$note->id,
            'name' => $note->name,
            'notes'=>$note->notes,
            'created_at'=>$note->created_at];
        }

        foreach($email as $note) {
            $data["email"][] = ['type'=>'email','id'=>$note->id,'notes'=>$note->notes, 'created_at'=>$note->created_at];
        }
        if(LD::checkFeatureFlag('show_notes_on_client_and_NTH')){
            if(!empty($nth)){
                foreach($nth as $nthdata){
                    if(!empty($nthdata)){
                        $data["nth"][] = ['type'=>'activity',
                        'id'=>$nthdata->id,
                        'notes'=>$nthdata->notes,
                        'created_at'=>$nthdata->created_at];
                    }
                }
            }
            if(!empty($social)){
                foreach($social as $socialData){
                    if(!empty($socialData)){
                        $data["social"][] = ['type'=>'activity',
                        'id'=>$socialData->id,
                        'notes'=>$socialData->notes,
                        'created_at'=>$socialData->created_at];
                    }
                }
            }
        }
        if(isset($data['comment'])) {
            $merge = array_merge($merge,$data['comment']);
        }
        if(isset($data['schedule'])) {
            $merge = array_merge($merge,$data['schedule']);
        }
        if(isset($data['activity'])) {
            $merge = array_merge($merge,$data['activity']);
        }
        if(isset($data['email'])) {
            $merge = array_merge($merge,$data['email']);
        }
        if(isset($data['nth'])){
            $merge = array_merge($merge,$data['nth']);
        }
        if(isset($data['social'])){
            $merge = array_merge($merge,$data['social']);
        }
        usort($merge, function ($item1, $item2) {
            return $item2['created_at'] <=> $item1['created_at'];
        });
        return view('components.trackerhistory',['data'=>$merge]);
    }

    public function getTrackerHistory(Request $request) {
        $comments = H::getHelper("ActivityNotes")->getActivityNotesByRecruiterTracker($request->input("id"));
        $schedule = H::getHelper("ActivityNotes")->getRecruitingScheduleByRecruiterTracker($request->input("id"));
        $trackerActivity = H::getHelper("ActivityNotes")->getActivityNotesByTypeAndRecruiterTracker($request->input("id"));
        $email = H::getHelper("ActivityNotes")->getEmailNotes($request->input("id"));


        $data = [];
        $merge = [];
        foreach($comments as $note) {
            if($note->entity!='email'){
                $data["comment"][] = ['type'=>'comment','id'=>$note->id,'name' => $note->name,'notes'=>$note->notes,'created_at'=>$note->created_at];
            }
        }

        foreach($schedule as $note) {

            $data["schedule"][] = ['id'=>$note->id,'type'=>'schedule','name' => $note->name,
            'appointment_date'=>$note->appointment_date,
            'appointment_time'=>$note->appointment_time,
            'outcome'=>$note->outcome,
            'branch'=> $note->branch,
            'schedule_type'=>$note->type,
            'patient_name'=>$note->PatientName,
            'created_at'=>$note->created_at];
			if(LD::checkFeatureFlag('shift-type-ccr-client-acitivty-notes')) {
                $shiftType = is_null($note->coverage_id) ? 0 : $note->coverage_id;
                $data['schedule'][count($data['schedule']) - 1]['shift_type'] = ($shiftType == 0) ? 'Perm' : 'Coverage';
            }
        }

        foreach($trackerActivity as $note) {

            $data["activity"][] = ['type'=>'activity','id'=>$note->id,'name' => $note->name,'notes'=>$note->notes, 'created_at'=>$note->created_at];
        }

        foreach($email as $note) {
            $data["email"][] = ['type'=>'email','id'=>$note->id,'notes'=>$note->notes, 'created_at'=>$note->created_at];
        }

        //var_dump($data['comment']);
        if(isset($data['comment'])) {
            $merge = array_merge($merge,$data['comment']);
        }
        if(isset($data['schedule'])) {
            $merge = array_merge($merge,$data['schedule']);
        }
        if(isset($data['activity'])) {
            $merge = array_merge($merge,$data['activity']);
        }
        if(isset($data['email'])) {
            $merge = array_merge($merge,$data['email']);
        }

        usort($merge, function ($item1, $item2) {
            return $item2['created_at'] <=> $item1['created_at'];
        });

        return view('components.trackerhistory',['data'=>$merge]);
    }

    public function getCandidatesHistory(Request $request) {
        $comments = H::getHelper("ActivityNotes")->getActivityNotesByCandidates($request->input("id"));
        $schedule = H::getHelper("ActivityNotes")->getRecruitingScheduleByCandidates($request->input("id"));
        $trackerActivity = H::getHelper("ActivityNotes")->getActivityNotesByTypeAndCandidates($request->input("id"));

        $data = [];
        $merge = [];
        foreach($comments as $note) {
            $data["comment"][] = ['type'=>'comment','id'=>$note->id,'name' => $note->name,'notes'=>$note->notes,'created_at'=>$note->created_at];
        }

        foreach($schedule as $note) {

            $data["schedule"][] = ['id'=>$note->id,'type'=>'schedule','name' => $note->name,
            'appointment_date'=>$note->appointment_date,
            'appointment_time'=>$note->appointment_time,
            'outcome'=>$note->outcome,
            'branch'=> $note->branch,
            'schedule_type'=>$note->type,
            'patient_name'=>$note->PatientName,
            'created_at'=>$note->created_at];
			if(LD::checkFeatureFlag('shift-type-ccr-client-acitivty-notes')) {
                $shiftType = is_null($note->coverage_id) ? 0 : $note->coverage_id;
                $data['schedule'][count($data['schedule']) - 1]['shift_type'] = ($shiftType == 0) ? 'Perm' : 'Coverage';
            }
        }

        foreach($trackerActivity as $note) {

            $data["activity"][] = ['type'=>'activity','id'=>$note->id,'name' => $note->name,'notes'=>$note->notes, 'created_at'=>$note->created_at];
        }

        //var_dump($data['comment']);
        if(isset($data['comment'])) {
            $merge = array_merge($merge,$data['comment']);
        }
        if(isset($data['schedule'])) {
            $merge = array_merge($merge,$data['schedule']);
        }
        if(isset($data['activity'])) {
            $merge = array_merge($merge,$data['activity']);
        }
        //$merge = array_merge($data['comment'],$data['schedule'],$data['activity']);


        usort($merge, function ($item1, $item2) {
            return $item2['created_at'] <=> $item1['created_at'];
        });

        return view('components.trackerhistory',['data'=>$merge]);
    }


    public function getClinicianHistory(Request $request) {
        $comments = H::getHelper("ActivityNotes")->getActivityNotesByClinician($request->input("id"));
        $schedule = H::getHelper("ActivityNotes")->getRecruitingScheduleByClinician($request->input("id"));
        $trackerActivity = H::getHelper("ActivityNotes")->getActivityNotesByTypeAndClinician($request->input("id"));

        $data = [];
        $merge = [];
        foreach($comments as $note) {
            if($note->type == 'status_change_request') {
                $data["activity"][] = ['type'=>'activity','id'=>$note->id,
                'name' => $note->name,'notes'=>$note->notes,
                'created_at'=>$note->created_at];
            } else {
                $data["comment"][] = ['type'=>'comment',
                'id'=>$note->id,'name' => $note->name,
                'notes'=>$note->notes,'created_at'=>$note->created_at];
            }
        }

        foreach($schedule as $note) {

            $data["schedule"][] = ['id'=>$note->id,'type'=>'schedule','name' => $note->name,
            'appointment_date'=>$note->appointment_date,
            'appointment_time'=>$note->appointment_time,
            'outcome'=>$note->outcome,
            'branch'=> $note->branch,
            'schedule_type'=>$note->type,
            'patient_name'=>$note->PatientName,
            'created_at'=>$note->created_at];
			if(LD::checkFeatureFlag('shift-type-ccr-client-acitivty-notes')) {
                $shiftType = is_null($note->coverage_id) ? 0 : $note->coverage_id;
                $data['schedule'][count($data['schedule']) - 1]['shift_type'] = ($shiftType == 0) ? 'Perm' : 'Coverage';
            }
        }

        foreach($trackerActivity as $note) {

            $data["activity"][] = ['type'=>'activity','id'=>$note->id,'name' => $note->name,'notes'=>$note->notes, 'created_at'=>$note->created_at];
        }

        //var_dump($data['comment']);
        if(isset($data['comment'])) {
            $merge = array_merge($merge,$data['comment']);
        }
        if(isset($data['schedule'])) {
            $merge = array_merge($merge,$data['schedule']);
        }
        if(isset($data['activity'])) {
            $merge = array_merge($merge,$data['activity']);
        }
        //$merge = array_merge($data['comment'],$data['schedule'],$data['activity']);


        usort($merge, function ($item1, $item2) {
            return $item2['created_at'] <=> $item1['created_at'];
        });

        return view('components.trackerhistory',['data'=>$merge]);
    }


    public function nthHistory(Request $request){
        if($request->input('id') != ''){
            $nthAccountId = $request->input('id')[1];
            $nthChecklistId = $request->input('id')[0];
            // if(LD::checkFeatureFlag('show_notes_on_client_and_NTH')){
            //     $clientId = NeedToHireAccounts::where('id', $nthAccountId)->value('aoc_patient_id');
            // }
            // if(LD::checkFeatureFlag('show_notes_on_client_and_NTH')){
            //     $nthHistory = Notes::select('notes.*', 'users.name')
            //     ->where(function($query) use ($nthChecklistId)  {
            //         $query->where('notes.entity', 'NeedToHire')
            //               ->where('notes.entity_id',$nthChecklistId);
            //     })
            //     ->orWhere(function($query)  use ($nthAccountId) {
            //         $query->where('notes.entity', 'nthAccount')
            //               ->where('notes.entity_id',$nthAccountId);
            //     })
            //     ->orWhere(function($query)  use ($clientId) {
            //         $query->where('notes.entity', 'client')
            //               ->where('notes.entity_id',$clientId);
            //     })
            //     ->leftJoin('users', function ($join) {
            //         $join->on('notes.created_by', '=', 'users.id');
            //     })->orderBy('created_at', 'DESC')->get();
            // }else{
                $nthHistory = Notes::select('notes.*', 'users.name')
                ->where(function($query) use ($nthChecklistId)  {
                    $query->where('notes.entity', 'NeedToHire')
                          ->where('notes.entity_id',$nthChecklistId)
						  ->where('notes.type','NTH');
                })
                ->orWhere(function($query)  use ($nthAccountId) {
                    $query->where('notes.entity', 'nthAccount')
                          ->where('notes.entity_id',$nthAccountId);
                })->orWhere(function($query)  use ($nthAccountId) {
                    $query->where('notes.entity', 'NeedToHire')
                          ->where('notes.entity_id',$nthAccountId)
						  ->whereNull('notes.type');
				})
                ->leftJoin('users', function ($join) {
                    $join->on('notes.created_by', '=', 'users.id');
                })->orderBy('created_at', 'DESC')->get();
            // }
            $data = [];
            foreach($nthHistory as $note) {
                if($note->type != ''){
                    $data[] = ['type'=>'activity','id'=>$note->id,'notes'=>$note->notes, 'created_at'=>$note->created_at,'misc1' => $note->misc1,'misc2'=>$note->misc2];
                }else{
                    $data[] = ['type'=>'comment','id'=>$note->id,'name' => isset($note->name) ? $note->name : '','notes'=>$note->notes, 'created_at'=>$note->created_at,'misc1' => $note->misc1,'misc2'=>$note->misc2];
                }
            }
            return view('components.trackerhistory',['data'=>$data]);
        }
    }

    public function saveNotes(Request $request) {
        $all = $request->all();
        $returnVal = 0;
        if(LD::checkFeatureFlag('billing-dashboard')){
            if(LD::checkFeatureFlag('send-one-notifications-per-week-row-unbilled-schedules')) {
                if(in_array($all['type'], ['bill_ready','bill_not_ready'])){
                    $entityIds = explode("##",$all['entity_id']);
                } else {
                    $entityIds = explode(",",$all['entity_id']);
                }
            } else {
                $entityIds = explode(",",$all['entity_id']);
            }
            if(isset($all['mention'])) {
                if(LD::checkFeatureFlag('careteam-tagging-to-notes')  && ($all['entity_type']=='client')) {
                    if(strstr($all['mention'],'-1')) {
                        $careTeams = H::getHelper("ConstantHelper")->getCareTeam($all['entity_id']);
                        
                        $careTeamIds = [];
                        foreach($careTeams as $careteam) {
                            $careTeamIds[] = $careteam['id'];
                        }
                        if(!empty($careTeamIds)) {
                            $all['mention'] = str_replace("-1",implode(",",$careTeamIds),$all['mention']);
                        }
                    }
                }
                $mentions = array_unique(explode(",",$all['mention']));
                
                foreach($mentions as $mention) {
                    foreach($entityIds as $entityId) {
                        $data = [];
                        $data['user_id'] = $mention;
                        $data['title'] = $all['title'];
                        $data['type'] = $all['entity_type'];
                        $data['related_item'] = $entityId;
                        $data['notes'] = $all['notes_text'];
                        if(LD::checkFeatureFlag('send-one-notifications-per-week-row-unbilled-schedules')) {
                            if(in_array($all['type'], ['bill_ready','bill_not_ready'])){
                                $data['related_item'] = 0;
                                $data['misc1'] = $entityId;
                            }
                        }
                        Notifications::addNotification($data);
                    }
                }
            }
            $flag = 0;
            foreach($entityIds as $entityId) {
                $insert = [];
                $dueDate = isset($all['due_date'])?$all['due_date']:'';
                $notes = isset($all['notes_text'])?$all['notes_text']:'';
                $assignedTo = isset($all['assigned_to'])?$all['assigned_to']:'';
                $insert['entity'] = $all['entity_type'];
                $insert['entity_id'] = $entityId;
                $insert['type'] = $all['type']??'';
                if(!empty($dueDate)) {
                    $dueDate = date('Y-m-d', strtotime($dueDate));
                    $insert['due_date'] = $dueDate;
                }
                if(!empty($notes)) {
                    $insert['notes'] = $notes;
                }
                if(!empty($assignedTo)) {
                    $insert['assigned_to'] = $assignedTo;
                }
                if($insert['entity'] == 'billing') {
                    $insert['misc4'] = time();
                }
                if(!empty($insert['notes'])) {
                    H::getHelper("ActivityNotes")->setActivityNotes($insert);
                    $flag++;
                }
            }
            if($flag == count($entityIds)){
                $returnVal = 1;
            }
        }else{
            if(isset($all['mention'])) {
                $mentions = explode(",",$all['mention']);
               
                foreach($mentions as $mention) {

                    $data = [];

                    $data['user_id'] = $mention;
                    $data['title'] = $all['title'];
                    $data['type'] = $all['entity_type'];
                    $data['related_item'] = $all['entity_id'];
                    $data['notes'] = $all['notes_text'];

                    Notifications::addNotification($data);

                }
            }
            $insert = [];
            $dueDate = isset($all['due_date'])?$all['due_date']:'';
            $notes = isset($all['notes_text'])?$all['notes_text']:'';
            $assignedTo = isset($all['assigned_to'])?$all['assigned_to']:'';

            $insert['entity'] = $all['entity_type'];
            $insert['entity_id'] = $all['entity_id'];
            //$insert['tracker_id'] = $all['entity_id'];
            $insert['type'] = $all['type']??'';

            if(!empty($dueDate)) {
                $dueDate = date('Y-m-d', strtotime($dueDate));
                $insert['due_date'] = $dueDate;
            }
            if(!empty($notes)) {
                $insert['notes'] = $notes;
            }
            if(!empty($assignedTo)) {
                $insert['assigned_to'] = $assignedTo;
            }

            if(!empty($insert['notes'])) {
                H::getHelper("ActivityNotes")->setActivityNotes($insert);
                $returnVal = 1;
            }
        }
        //send SMS to selected care team
        if(!empty($all['send_sms_care_team']) && !empty($all['careTeamRole'])) {
            $requestData = [];
            $requestData['aoc_patient_id'] = $all['entity_id'];
            $requestData['careTeamRole'] = $all['careTeamRole'];
            $requestData['from_page'] = 'client_notes';
            H::ExcelLog('Send SMS to care team Start For ' . $requestData['aoc_patient_id'], 'intake_outcome.log');
            H::getHelper('MessageWidget')->sendSmsToIntakeCareTeam($requestData);
        }
        return $returnVal;
    }

    public function getTriageActivity(Request $request) {
        $comments = H::getHelper("ActivityNotes")->getActivityOfTriageCall($request->input("id"));
        $data = [];
        $merge = [];
        foreach($comments as $note) {
            $data["activity"][] = ['type'=>'activity','id'=>$note->id,'name' => $note->name,'notes'=>$note->notes,'created_at'=>$note->created_at];
        }
        if(isset($data['activity'])) {
            $merge = array_merge($merge,$data['activity']);
        }
        usort($merge, function ($item1, $item2) {
            return $item2['created_at'] <=> $item1['created_at'];
        });
        return view('components.trackerhistory',['data'=>$merge]);
    }

    public function ipeaHistory(Request $request){
        if($request->input('id') != ''){
            if(LD::checkFeatureFlag('sticky-note-ipea-template-page')) {
            $notes = Notes::select('notes.*', 'users.name')
            ->where('notes.entity_id',$request->input('id'))
            ->where('notes.entity','tracker-activity')
            ->whereIn('notes.type',['ipea','ipea-note'])
            ->leftJoin('users', function ($join) {
                $join->on('notes.created_by', '=', 'users.id');
            })->orderBy('notes.created_at', 'DESC')->get();
            } else {
                $notes = Notes::select('notes.*', 'users.name')
            ->where('notes.entity_id',$request->input('id'))
            ->where('notes.entity','tracker-activity')
            ->where('notes.type','ipea')
            ->leftJoin('users', function ($join) {
                $join->on('notes.created_by', '=', 'users.id');
            })->orderBy('notes.created_at', 'DESC')->get();
            }
            $data = [];
            foreach($notes as $note) {
                if($note->type != ''){
                    if($note->type == 'ipea-note'){
                        $data[] = ['type'=>'comment',
                        'id'=>$note->id,
                        'name' => isset($note->name) ? $note->name : '',
                        'notes'=>$note->notes,
                        'created_at'=>$note->created_at,
                        'misc1' => $note->misc1,
                        'misc2'=>$note->misc2];
                    } else{
                        $data[] = ['type'=>'activity',
                        'id'=>$note->id,
                        'notes'=>$note->notes,
                        'created_at'=>$note->created_at,
                        'misc1' => $note->misc1,
                        'misc2'=>$note->misc2];
                    }
                }else{
                    $data[] = ['type'=>'comment',
                    'id'=>$note->id,
                    'name' => isset($note->name) ? $note->name : '',
                    'notes'=>$note->notes,
                    'created_at'=>$note->created_at,
                    'misc1' => $note->misc1,
                    'misc2'=>$note->misc2];
                }
            }
            return view('components.trackerhistory',['data'=>$data]);
        }
    }
    public function ipeaNeoHistory(Request $request){
        if($request->input('id') != ''){
            $notes = Notes::select('notes.*', 'users.name')
            ->where('notes.entity_id',$request->input('id'))
            ->where('notes.entity','recruiting-schedule')
            ->leftJoin('users', function ($join) {
                $join->on('notes.created_by', '=', 'users.id');
            })->orderBy('notes.created_at', 'DESC')->get();
            $data = [];
            foreach($notes as $note) {
                $data[] = ['type'=>'activity','id'=>$note->id,'notes'=>$note->notes, 'created_at'=>$note->created_at,'misc1' => $note->misc1,'misc2'=>$note->misc2];
            }
            return view('components.trackerhistory',['data'=>$data]);
        }
    }
    public function schoolSetupHistory(Request $request){
        if($request->input('id') != ''){
            $notes = Notes::select('notes.*', 'users.name')
            ->where('notes.entity_id',$request->input('id'))
            ->where('notes.entity','school-setup')
            ->leftJoin('users', function ($join) {
                $join->on('notes.created_by', '=', 'users.id');
            })->orderBy('created_at', 'DESC')->get();
            $data = [];
            foreach($notes as $note) {
                if($note->type != ''){
                    $data[] = ['type'=>'activity','id'=>$note->id,
                    'notes'=>$note->notes, 'created_at'=>$note->created_at,
                    'misc1' => $note->misc1,'misc2'=>$note->misc2];
                }else{
                    $data[] = ['type'=>'comment','id'=>$note->id,
                    'name' => isset($note->name) ? $note->name : '','notes'=>$note->notes,
                    'created_at'=>$note->created_at,'misc1' => $note->misc1,'misc2'=>$note->misc2];
                }
            }
            return view('components.trackerhistory',['data'=>$data]);
        }
    }

    public function billingInvoiceHistory(Request $request){
        if($request->input('id') != ''){
            if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard')) {
                $smIds = explode(",",$request->input('id'));
                $notes = Notes::select('notes.*', 'users.name')
                ->whereIn('notes.entity_id',$smIds)
                ->leftJoin('users', function ($join) {
                    $join->on('notes.created_by', '=', 'users.id');
                });
                if($request->input('type') == 'invoice'){
                $notes = $notes->Where('notes.entity','invoice');
                }elseif($request->input('type') == 'billing'){
                    $notes = $notes->Where('notes.entity','billing');
                    $notes = $notes->groupBy('misc4');
                }
                $notes = $notes->orderBy('created_at', 'DESC')->get();
            } else {
                $notes = Notes::select('notes.*', 'users.name')
                ->where('notes.entity_id',$request->input('id'))
                ->leftJoin('users', function ($join) {
                    $join->on('notes.created_by', '=', 'users.id');
                });
                if($request->input('type') == 'invoice'){
                   $notes = $notes->Where('notes.entity','invoice');
                }elseif($request->input('type') == 'billing'){
                    $notes = $notes->Where('notes.entity','billing');
                }
                $notes = $notes->orderBy('created_at', 'DESC')->get();
            }
            $data = [];
            foreach($notes as $note) {
                $data[] = ['type'=>'comment','id'=>$note->id,
                'name' => isset($note->name) ? $note->name : '','notes'=>$note->notes,
                'created_at'=>$note->created_at,'misc1' => $note->misc1,'misc2'=>$note->misc2];
            }
            return view('components.trackerhistory',['data'=>$data]);
        }
    }


    public function nthLeadsInquiriesHistory(Request $request){
        if($request->input('id') != ''){
            $notes = Notes::select('notes.*', 'users.name')
            ->where('notes.entity_id',$request->input('id'))
            ->leftJoin('users', function ($join) {
                $join->on('notes.created_by', '=', 'users.id');
            });
            $notes = $notes->Where('notes.entity','nthalerts');
            $notes = $notes->orderBy('created_at', 'DESC')->get();
            $data = [];
            foreach($notes as $note) {
                $data[] = ['type'=>'comment','id'=>$note->id,
                'name' => isset($note->name) ? $note->name : '','notes'=>$note->notes,
                'created_at'=>$note->created_at,'misc1' => $note->misc1,'misc2'=>$note->misc2];
            }
            return view('components.trackerhistory',['data'=>$data]);
        }
    }
}
