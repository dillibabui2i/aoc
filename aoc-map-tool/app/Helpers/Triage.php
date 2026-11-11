<?php

namespace App\Helpers;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Triage as TriageModel;
use Illuminate\Support\Facades\Auth;

class Triage
{
    public $buckets = [
    "triage_24h_follow_up"=>["heading"=>"24 Hour Follow-Up"],
	"triage_48h_follow_up"=>["heading"=>"48 Hour Follow-Up"],
	"triage_7d_follow_up"=>["heading"=>"7 Day Follow-Up"],
	"triage_30d_follow_up"=>["heading"=>"30 Day Follow-Up"]
	];


    public function getBuckets(){
        return $this->buckets;
    }

    /**
     * The function return bucket title
     *
     * @param string    $type
     * @author Pradip
     * @return string
     */
    public function getBucketTitle($type) {
        return $this->buckets[$type]['heading'];
    }

    public function getDataTableHtml($type)
    {
        $dataTable = [];
        $dataTable = [
            ['data'=>'id','name'=>'id','label'=>'ID','visible'=>false],
            ['data'=>'reason','name'=>'reason','label'=>'Reason Reported','width'=>'30%'],
            ['data'=>'disposition','name'=>'disposition','label'=>'Disposition'],
            ['data'=>'patient_name','name'=>'patient_name','label'=>'Client Name'],
            ['data'=>'caller_name','name'=>'caller_name','label'=>'Caller Name'],
            ['data'=>'DOB','name'=>'DOB','label'=>'DOB'],
            ['data'=>'Payer','name'=>'Payer','label'=>'Terminal Payer'],
            ['data'=>'callStartTime','name'=>'callStartTime','label'=>'Last Call Day/Time'],
            ['data'=>'outcome_attempt','name'=>'outcome_attempt','label'=>'Attempt'],
        ];
        return view('triage.datatable',["type"=>$type,'dataTable'=>$dataTable]);

    }

    public function getTriageDataByType($type, $request = [],$showCount=0) {


        $bucket = TriageModel::Select("triageCalls.*", DB::raw("DATE_FORMAT(mssql_patients.Patient_DOB,'%m/%d/%Y') AS DOB"), 'mssql_patients.Payer',DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
        DB::raw("CONCAT(triageCalls.callerFirstName,' ',triageCalls.callerLastName) AS caller_name"));
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","triageCalls.aoc_patient_id");
        });
        $bucket->where(function($q){
            $q->where("triageCalls.outcome","!=",'Parent Declined Further Follow Ups')->orWhereNull("triageCalls.outcome");
        });
        if(LD::checkFeatureFlag('add-outcome-triage-followup-care-coordination')){
            $bucket->where(function($q){
                $q->where("triageCalls.outcome","!=",'Care Coordination Only; No Further Follow Ups')->orWhereNull("triageCalls.outcome");
            });
        }

        switch($type) {
            case 'triage_24h_follow_up':
                $twentyFourHrToDate =  date("Y-m-d");
                $twentyFourHrFromDate = date("Y-m-d",strtotime("-2 day"));
                $bucket->whereRaw(
                    'triageCalls.callDate < ? and triageCalls.callDate > ?',
                    [$twentyFourHrToDate,$twentyFourHrFromDate]
                );
                $bucket->where(function($q){
                    $q->where("step1D","!=",1)->orWhereNull("step1D");
                });
                $bucket->addSelect(DB::raw('(select triageFollowup.contactWithCaller from triageFollowup where triageCalls.id=triageFollowup.triagecall_id and triageFollowup.followupType="1D" limit 1) as contactwithcaller'));
                if(LD::checkFeatureFlag('triage-attempt-count-changes')){
                    $bucket->addSelect('attempts_24 as outcome_attempt');
                }
                break;
            case 'triage_48h_follow_up':
                $fortyEightHrToDate =  date("Y-m-d",strtotime("-1 day"));
                $fortyEightFromDate = date("Y-m-d",strtotime("-7 day"));
                $bucket->whereRaw(
                    'triageCalls.callDate < ? and triageCalls.callDate > ? ',
                    [$fortyEightHrToDate,$fortyEightFromDate]
                );
                $bucket->where(function($q){
                    $q->where("step2D","!=",1)->orWhereNull("step2D");
                });
                $bucket->addSelect(DB::raw('(select triageFollowup.contactWithCaller from triageFollowup where triageCalls.id=triageFollowup.triagecall_id and triageFollowup.followupType="2D" limit 1) as contactwithcaller'));
                if(LD::checkFeatureFlag('triage-attempt-count-changes')){
                    $bucket->addSelect('attempts_48 as outcome_attempt');
                }
                break;
            case 'triage_7d_follow_up':
                $sevenDaysToDate =  date("Y-m-d",strtotime("-6 day"));
                $sevenDaysFromDate = date("Y-m-d",strtotime("-30 day"));
                $bucket->whereRaw(
                    'triageCalls.callDate < ? and triageCalls.callDate > ? ',
                    [$sevenDaysToDate,$sevenDaysFromDate]
                );
                $bucket->where(function($q){
                    $q->where("step7D","!=",1)->orWhereNull("step7D");
                });
                $bucket->addSelect(DB::raw('(select triageFollowup.contactWithCaller from triageFollowup where triageCalls.id=triageFollowup.triagecall_id and triageFollowup.followupType="7D" limit 1) as contactwithcaller'));
                if(LD::checkFeatureFlag('triage-attempt-count-changes')){
                    $bucket->addSelect('attempts_7day as outcome_attempt');
                }
                break;
            case 'triage_30d_follow_up':
                $thirtyDaysToDate =  date("Y-m-d",strtotime("-29 day"));
                $thirtyDaysFromDate = date("Y-m-d",strtotime("-31 day"));
                $bucket->whereRaw('triageCalls.callDate < ?',[$thirtyDaysToDate]);
                $bucket->where(function($q){
                    $q->where("step30D","!=",1)->orWhereNull("step30D");
                });
                $bucket->addSelect(DB::raw('(select triageFollowup.contactWithCaller from triageFollowup where triageCalls.id=triageFollowup.triagecall_id and triageFollowup.followupType="30D" limit 1) as contactwithcaller'));
                if(LD::checkFeatureFlag('triage-attempt-count-changes')){
                    $bucket->addSelect('attempts_30day as outcome_attempt');
                }
                break;
            default:
                break;
        }

        if(isset($request['filters'])) {

                $state = [];
                $branch = [];
                $terminalPayor = [];
                $reporter = [];
                $dispostion =[];
                $fullname='';
                $dob = '';
                foreach($request['filters'] as $key => $value) {
                    if($value['name']=='triagestate') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='triagebranch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='triagedisposition') {
                        $dispostion[] = $value['value'];
                    }
                    if($value['name']=='terminal_payor') {
                        $terminalPayor[] = $value['value'];
                    }
                    if($value['name']=='reporter') {
                        $reporter[] = $value['value'];
                    }
                    if($value['name']=='triagefullname') {
                        $fullname = $value['value'];
                    }
                    if($value['name']=='triagedob') {
                        $dob = $value['value'];
                    }
                }
                if(count($state)>0) {
                    $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                    $bucket->where(function($q) use ($facilities){
                        foreach($facilities as $facility) {
                            $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                        }
                    });
                }
                if(count($branch)>0) {
                    $bucket->where(function($q) use ($branch){
                        foreach($branch as $b) {
                            $q->orWhere("mssql_patients.aoc_facility_id","=",$b);
                        }
                    });
                }

                if(count($dispostion)>0) {
                    $bucket->where(function($q) use ($dispostion){
                        foreach($dispostion as $d) {
                            $q->orWhere("triageCalls.disposition","=",$d);
                        }
                    });
                }

                if(count($reporter)>0) {
                    $bucket->where(function($q) use ($reporter){
                        foreach($reporter as $r) {
                            $q->orWhere("triageCalls.user_id","=",$r);
                        }
                    });
                }
                if(count($terminalPayor)>0) {
                    $bucket->where(function($q) use ($terminalPayor){
                        foreach($terminalPayor as $t) {
                            $q->orWhere("mssql_patients.Payer","=",$t);
                        }
                    });
                }
                if(!empty($fullname)){
                    $bucket->where(function($q) use ($fullname) {
                        $q->orWhereRaw(
                            "CONCAT(triageCalls.callerFirstName,' ',triageCalls.callerLastName) like '%?%'",
                            [$fullname]
                        );
                        $q->orWhereRaw(
                            "CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) like '%?%'",
                            [$fullname]
                        );
                    });
                }

                if(!empty($dob)){
                    $bucket->whereRaw("DATE_FORMAT(mssql_patients.Patient_DOB,'%m/%d/%Y') like '%?%'",[$dob]);
                }
        }
        H::ExcelLog($bucket->toSql(),'getTriageDataByType.log');
        if($showCount) {
            if (LD::checkFeatureFlag('dashboard-past-due-count')) {
                $data2 = clone $bucket;
                $filtered_data = $data2->get()->filter(function ($data) {
                    return $data->contactwithcaller == '0';
                });
                return '<span>('.$bucket->count().' | '.$filtered_data->count().' Past Due)</span>';
            } else {
                return '('.$bucket->count().')';
            }
        }

        return $bucket->get();

    }

    public static function getCallViewFields(){
        return [
            ['data'=>'id','name'=>'id','label'=>'ID','visible'=>false],
            ['data'=>'callDate','name'=>'callDate','label'=>'Original Call Day/Time','width'=>'25%'],
            ['data'=>'bodySystem','name'=>'bodySystem','label'=>'Body System(s)','width'=>'15%'],
            ['data'=>'reason','name'=>'reason','label'=>'Reason(s)','width'=>'20%'],
            ['data'=>'careCoordination','name'=>'careCoordination','label'=>'Care Coordination','width'=>'15%'],
            ['data'=>'isAmbulanceTransfer','name'=>'isAmbulanceTransfer','label'=>'Ambulance Transfer','width'=>'15%'],
            ['data'=>'callerName','name'=>'callerName','label'=>'Caller Name','width'=>'10%']
        ];
    }

    public function formateNotes($data, $id){
        $labelArr = [
            'reporter' => 'Reporter',
           'callDate' => 'Date',
           'callStartTime' => 'Call Start Time',
           'clientRelationship' => 'Relationship to Client',
           'callerFirstName' => 'Caller First Name',
           'callerLastName' => 'Caller Last Name',
           'callerPhone' => 'Caller Phone Number',
           'callerEmail' => 'Caller Email Address',
           'clientName' => 'Client Name',
           'clientDOB' => 'Client Date Of Birth',
           'terminalPlayer' => 'Terminal Payer',
           'client_id' => 'Client ID',
           'client_location' => 'Client Location',
           'bodySystem' => 'Body System(s) Involved',
           'description' => 'Description',
           'reason' => 'Reason',
           'disposition' => 'Disposition',
           'careCoordination' => 'Care Coordination',
           'wasMedPrescribed' => 'Was medication prescribed?',
           'drugClassification' => 'Drug Classification',
           'hasMedicationPickedUp' => 'Has Medication been picked up?',
           'wereNewOrderRecieived' => 'Were New Intervention Orders Received?',
           'interventionType' => 'Intervention Type',
           'followUpAction' => 'Follow-Up Action',
           'emergencyRoom' => "If you didn't talk to us, would you have gone to the Emergency Room?",
           'emergencyDepartment' => 'Where would you have gone?',
           'notes' => 'Notes',
        ];

        $value = [];
        $value['reporter'] = isset($data['reporter']) ? $data['reporter'] : '';
        $value['callDate'] = isset($data['callDate']) ? $data['callDate'] : '';
        $value['callStartTime'] = isset($data['callStartTime']) ? $data['callStartTime'] : '';
        $value['clientRelationship'] = isset($data['clientRelationship']) ? $data['clientRelationship'] : '';
        $value['callerFirstName'] = isset($data['callerFirstName']) ? $data['callerFirstName'] : '';
        $value['callerLastName'] = isset($data['callerLastName']) ? $data['callerLastName'] : '';
        $value['callerPhone'] = isset($data['callerPhone']) ? $data['callerPhone'] : '';
        $value['callerEmail'] = isset($data['callerEmail']) ? $data['callerEmail'] : '';
        $value['clientName'] = isset($data['clientName']) ? $data['clientName'] : '';
        $value['clientDOB'] = isset($data['clientDOB']) ? $data['clientDOB'] : '';
        $value['terminalPlayer'] = isset($data['terminalPlayer']) ? $data['terminalPlayer'] : '';
        $value['client_id'] = isset($data['client_id']) ? $data['client_id'] : '';
        $value['client_location'] = isset($data['client_location']) ? $data['client_location'] : '';
        $value['bodySystem'] = isset($data['bodySystem']) ? $data['bodySystem'] : '';
        $value['description'] = isset($data['description']) ? $data['description'] : '';
        $value['reason'] = isset($data['reason']) ? $data['reason'] : '';
        $value['disposition'] = isset($data['disposition']) ? $data['disposition'] : '';
        $value['careCoordination'] = isset($data['careCoordination']) ? $data['careCoordination'] : '';
        $value['wasMedPrescribed'] = isset($data['wasMedPrescribed']) ?
        ($data['wasMedPrescribed'] == 1 ? 'Yes' : 'No') : '';
        $value['drugClassification'] = isset($data['drugClassification']) ? $data['drugClassification'] : '';
        $value['hasMedicationPickedUp'] = isset($data['hasMedicationPickedUp']) ?
        ($data['hasMedicationPickedUp'] == 1 ? 'Yes' : 'No') : '';
        $value['wereNewOrderRecieived'] = isset($data['wereNewOrderRecieived']) ?
        ($data['wereNewOrderRecieived'] == 1 ? 'Yes' : 'No') : '';
        $value['interventionType'] = isset($data['interventionType']) ? $data['interventionType'] : '';
        $value['followUpAction'] = isset($data['followUpAction']) ? $data['followUpAction'] : '';
        $value['emergencyRoom'] = isset($data['emergencyRoom']) ?
        ($data['emergencyRoom'] == 'on' ? 'Yes' : 'No') : '';
        $value['emergencyDepartment'] = isset($data['emergencyDepartment']) ? $data['emergencyDepartment'] : '';
        $value['notes'] = isset($data['notes']) ? $data['notes'] : '';

        $logData = [];
        $logData['entity'] = 'triage';
        $logData['entity_id'] = $id;
        $logData['notes'] = '';
        $logData['notes'] .= Auth::user()->name.' submitted new triage call on '.date('m/d/Y H:i A').'.';
        $logData['notes'] .= '<ul>';
        foreach ($value as $oldkey => $data) {
            $logData['notes'] .= "<li>".$labelArr[$oldkey] .
            ' : '.(is_array($value[$oldkey]) ? implode(',', $value[$oldkey]) : $value[$oldkey])."</li>";
        }
        $logData['notes'] .= '</ul>';
        H::getHelper("ActivityNotes")->setActivityNotes($logData);
    }
}
