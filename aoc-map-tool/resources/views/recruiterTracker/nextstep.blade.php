<?php
use App\Enums\ScheduleNextStepOutcomesEnum;
use App\Helpers\LD;
use App\Helpers\H;
use App\Enums\RecruitingBucket;
use App\Enums\IntakeCoordinatorBucket;
?>
@php
//$interviewTypeList = DB::table('schedule_types')->get();
$interviewTypeList = DB::table('schedule_types')->whereNotIn('type',['CCR','Needs to be Scheduled by RA'])->get();
$interviewTypeData = [];
//if (LD::checkFeatureFlag('no-automatic-recruiting-schedule')) {
	$isOutcomeCompleted = 1;
    if($isOutcomeCompleted == 0){
        $interviewTypeData[] = ['id' =>1,'type' => 'IPEA','sort_order' => 1];
        $interviewTypeData[] = ['id' =>2,'type' => 'CCR','sort_order' => 2];
    } else {
        if (count($interviewTypeList)) {
            $interviewTypeData = json_decode(json_encode($interviewTypeList), true);
        }
    }
//}else {
//    if (count($interviewTypeList)) {
//        $interviewTypeData = json_decode(json_encode($interviewTypeList), true);
//    }
//}

if(LD::checkFeatureFlag('intake-scheduling-ipea')) {
    $intakeRecruiters = H::getHelper('ConstantHelper')->getRecruitersList();
    if($bucket=='INTAKE-SCHEDULE-IPEA') {
        $interviewTypeData = [];
        $interviewTypeData[] = ['id' =>1,'type' => 'IPEA','sort_order' => 1];
    } else if ($bucket=='INTAKE-TODAY-IPEA') {
        $interviewTypeData = [];
        $interviewTypeList = DB::table('schedule_types')->whereIn('type',['Candidate Start','IPEA'])->get();
        if (count($interviewTypeList)) {
            $interviewTypeData = json_decode(json_encode($interviewTypeList), true);
        }
    } else if($bucket=='INTAKE-CANDIDATE-STARTS' || $bucket=='INTAKE-PENDING-CANDIDATE-STARTS') {
        $interviewTypeData = [];
        $interviewTypeData[] = ['id' =>6,'type' => 'Candidate Start','sort_order' => 1];
    }

}



if($bucket==''){
    $outcomeList = [];
}else{
$outcomeList = config('constant')['recruitingOutcomes'][$bucket];
}

if (
    LD::checkFeatureFlag('schedule-next-step-pending-outcomes')
    && RecruitingBucket::supportsPendingOutcomes($bucket)
) {
    $outcomeList[] = ScheduleNextStepOutcomesEnum::PENDING_CLINICIAN_STATUS->value;
    $outcomeList[] = ScheduleNextStepOutcomesEnum::PENDING_CLIENT->value;
}

if(LD::checkFeatureFlag('candidate-starts-sns-new-outcome-candidate-ncns') && $bucket=='CANDIDATE STARTS'){
    $outcomeList[]="Candidate NCNS to first shift";
}

if (LD::checkFeatureFlag("add-ipea-outcome-pending-next-step") && $bucket=='IPEA') {
    $outcomeList[] = 'Pending Next Steps';
}

if($bucket=='IPEA' && $recruiterDetails->live_ipea_status=='Need to review new clients'){
    $outcomeList = config('constant')['recruitingOutcomes']['NEED TO REVIEW NEW CLIENTS'];
}
if($bucket=='IPEA' && LD::checkFeatureFlag("change-outcomes-for-ipea-widget")) {
    $outcomeList = array_values(array_diff($outcomeList, array('Move to PRN')));
    $newOutcomeList = [
        'PRN Agreement Signed & First Month Scheduled',
        'No Longer Interest in PRN - Needs to Review New Clients',
        'No Longer Interested in PRN - Moved to Active Coverage Status'
    ];
    $outcomeList = array_merge($outcomeList,$newOutcomeList);
}

if($bucket=='IPEA' && LD::checkFeatureFlag("change-outcomes-for-ipea-without-prn-status")) {
    $outcomeList = array_values(array_diff($outcomeList, [
            'Move to PRN',
            'PRN Agreement Signed & First Month Scheduled',
            'No Longer Interest in PRN - Needs to Review New Clients',
            'No Longer Interested in PRN - Moved to Active Coverage Status'
            ]
        ));
    $newOutcomeList = [
        'Move to Active Coverage'
    ];
    $outcomeList = array_merge($outcomeList,$newOutcomeList);
}

if($bucket=='IPEA' && LD::checkFeatureFlag("recruiting-ipea-convert-tracker-to-activecoverage-client")) {
    $oldValue = 'No Longer Interested in PRN - Moved to Active Coverage Status';
    $newValue = 'No Longer Interested in PRN - Move to Active Coverage Status';


    $outcomeList = array_map(function($item) use ($oldValue, $newValue) {
        return ($item === $oldValue) ? $newValue : $item;
    }, $outcomeList);
}

if($bucket=='IPEA' && LD::checkFeatureFlag("change-client-outcome-seperate-ff")) {
    $newOutcomeList = ['Change Client'];
    $outcomeList = array_merge($outcomeList,$newOutcomeList);
}
if($bucket=='CCRS' && $defaultOutcome=='Suggest Other Clients'){
    $outcomeList = config('constant')['recruitingOutcomes']['CCRS2'];
}

if($bucket=='CCRS' && LD::checkFeatureFlag("ccrs-change-client-outcome")) {
    $outcomeList[] = 'Change Client';
    $schedule_type='';
    $proposed_ns_date = '';
    $proposed_ns_time = '';

}
if(LD::checkFeatureFlag("add-ccr-outcome-enhancements") && $bucket=='CCRS') {
    for($i=($recruiterDetails->pending_parent_attempt_count +1); $i <= ($recruiterDetails->pending_parent_attempt_count +1) && $i<=3; $i++){
        $outcomeList[] = 'Pending Parent Attempt '.$i;
    }
    for($i=($recruiterDetails->pending_candidate_attempt_count +1); $i <= ($recruiterDetails->pending_candidate_attempt_count +1) && $i<=3; $i++){
        $outcomeList[] = 'Pending Candidate Attempt '.$i;
    }
    $outcomeList[] = 'Client in hospital';
    $outcomeList[] = 'Move to PRN';
}
if (LD::checkFeatureFlag("add-new-outcomes-to-new-to-review-new-clients-bucket") && $bucket=='NEED TO REVIEW NEW CLIENTS') {
    if($attemptCount == 0){
        $outcomeList[] = "Attempt 1";
    }
    if($attemptCount == 1){
        $outcomeList[] = "Attempt 2";
    }
    if($attemptCount == 2){
        $outcomeList[] = "Attempt 3";
    }
}

if (LD::checkFeatureFlag("add-attempts-outcome-schedule-by-ra-widget") && $bucket == 'NEED TO BE SCHEDULED BY RA') {
    if($scheduleByRaAttemptCount == 0){
        $outcomeList[] = "Attempt 1";
    }
    if($scheduleByRaAttemptCount == 1){
        $outcomeList[] = "Attempt 2";
    }
    if($scheduleByRaAttemptCount == 2){
        $outcomeList[] = "Attempt 3";
    }
}

if (LD::checkFeatureFlag("add-new-outcome-to-ipea-widget-sns-form") && $bucket=='IPEA') {
    if($ipeaAttemptCount == 0){
        $outcomeList[] = "Attempt 1";
    }
    if($ipeaAttemptCount == 1){
        $outcomeList[] = "Attempt 2";
    }
    if($ipeaAttemptCount == 2){
        $outcomeList[] = "Attempt 3";
    }
}
if (LD::checkFeatureFlag("add-outcomes-to-schedule-next-step-form-for-prn") && $bucket=='PRN') {
    $outcomeList = ['First Shift Confirm', 'Inactivate on tracker', 'Suggest Other Clients'];
}

if (LD::checkFeatureFlag("prn-process-active-coverage-bucket-fixes") && $bucket=='PRN') {
    if (LD::checkFeatureFlag("add-outcome-to-recruiting-active-coverage-bucket")) {
        $outcomeList = ['Inactivate Tracker'];
        $bonusPaidDate = strtotime(date('Y-m-d',strtotime($recruiterDetails->Bonus_Paid_Date)));
        $today = strtotime(date("Y-m-d"));
        if(($recruiterDetails->Hours_Worked>=40) && ($bonusPaidDate <= $today)) {
            array_push($outcomeList, 'Confirm Completed');
        }
        array_push($outcomeList, 'No longer interested in Active Coverage - move to needs to review');
    } else {
        $outcomeList = ['Inactivate on tracker'];
    }
}

if(LD::checkFeatureFlag('client-match-sns-patientstatus-candidatestatus')) {
    $candidateStatus = H::getHelper("ConstantHelper")->getEmploymentStatus();
} else {
    $patientStatus = H::getHelper("ConstantHelper")->getMssqlPatientStatus();
}

    $approvedByList = [];
    $approvedUserList = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates');
    foreach($approvedUserList as $user) {
        $approvedByList[] = $user->name;
    }

$facilities = H::getHelper("ConstantHelper")->getFacilities()->toArray();
if(LD::checkFeatureFlag('intake-scheduling-ipea') && ($bucket=='INTAKE-SCHEDULE-IPEA' || $bucket=='INTAKE-TODAY-IPEA')){

    $approvedByListIntake = [];
    $approvedUserList = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates');
    foreach($approvedUserList as $user) {

        $approvedByListIntake[] = ["text"=>$user->name,'value'=>$user->id];
    }

    //var_dump($facilities);
    $tempFacility = [];
    foreach($facilities as $facility) {
        $tempFacility[] = ["text"=>$facility['FacilityName'],'value'=>$facility['id']];

    }
    $facilities = $tempFacility;
} else {
    $facilities = array_column($facilities,'FacilityName');
}
if(LD::checkFeatureFlag('prn-changes-add-clinician')) {
    $key = array_search("Move to PRN", $outcomeList);
    if ($key !== false) {
        unset($outcomeList[$key]);
    }
}
if(LD::checkFeatureFlag("remove-suggest-other-client-option-from-recruiter-tracker-ipea-bucket")){
    if($bucket == 'IPEA'){
        $key = array_search("Suggest Other Clients", $outcomeList);
        if ($key !== false) {
            unset($outcomeList[$key]);
        }
    }
}
@endphp
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<script src="{{ mix('js/nth-schedule-select.js') }}"></script>
<?php
$clinicianInterviewCompleted = 'false';
if(isset($schedules[0])){
    $clinicianInterviewCompleted = $schedules[0]->IPEA_interview;
}
?>
<style>
.ipeaUpcomingEvents .d-flex p{
    margin: 0;
}
div.custom-control-right {
    padding-right: 24px;
    padding-left: 0;
    margin-left: 0;
    margin-right: 0;
}

div.custom-control-right .custom-control-label::after {
    right: -1.5rem;
    left: auto;
}

div.custom-control-right .custom-control-label::before {
    right: -2.35rem;
    left: auto;
}

.custom-control-right .custom-control-label {
    line-height: 22px;
}

.schedule-appointment {
    margin-top: 40px;
}

.schedule-appointment fieldset {
    padding: 15px;
    border: 1px solid #ddd;
}
.alert-danger-specific {
    background-color: #f9d6d5;
    border-color: #f7c6c5;
    color: #761b18;
}
.datepicker>div {
    display: inline;
}
.datepicker-dropdown.datepicker-orient-left:before,
.datepicker-dropdown.datepicker-orient-left:after {
    display: none;
}
</style>
<x-modal>
    <x-slot name="id"> schedule_next</x-slot>
    <x-slot name="title">Schedule Next Step \ {{$recruiterDetails->Candidate}}</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();
                openNotes('{{$recruiterDetails->id}}','tracker','Recruiter Tracker Notes Added')"><i
                class="fa  fa-sticky-note-o"></i></a>
                <?php if(LD::checkFeatureFlag('client-profile-icon-on-tracker-detail-and-schedule-next-step') &&
                 !empty($patientsDetails)){ ?>
                    &nbsp;<a href="javascript:void(0)"
                    onclick="getClientDetails('{{$patientsDetails->id}}')">
                    <i class="fa fa-user" aria-hidden="true"></i></a>
                <?php } ?>
        </div>
        <div class="col-md-6 text-right pull-right">
            <span class='btn btn-primary'>
                {{ nl2br($recruiterDetails->Candidate_Current_Status) }}

            </span>
        </div>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="modal-body" id="scheduleNextSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="modal-body" id="scheduleNextFormDiv">

            <form method="post" autocomplete="off" id="schedule_next_form" class="scaffold-form"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="tracker_id" name="tracker_id" value="{{ $tracker_id??''}}">
                <input type="hidden" id="patient_id" name="patient_id" value="{{ $recruiterDetails->aoc_patient_id}}">
                <input type="hidden" id="mode" name="mode" value="Edit">
                <input type="hidden" id="candidate_id" name="candidate_id"
                    value="{{ $recruiterDetails->aoc_candidate_id}}">
                <input type="hidden" id="provider_id" name="provider_id"
                    value="{{ $recruiterDetails->aoc_provider_id}}">
                <input type="hidden" id="facility_id" name="facility_id"
                    value="{{ $recruiterDetails->aoc_facility_id}}">
                <input type="hidden" id="scheduling" name="scheduling" value="0">
                <?php if(LD::checkFeatureFlag('intake-scheduling-ipea')) {
                        if($bucket=='INTAKE-SCHEDULE-IPEA' || $bucket=='INTAKE-TODAY-IPEA'  || $bucket=='INTAKE-CANDIDATE-STARTS'  || $bucket=='INTAKE-PENDING-CANDIDATE-STARTS'  ) { ?>
                            <input type="hidden" id="bucket_type" name="bucket_type" value="{{$bucket}}">
                    <?php } ?>
                <?php } ?>
                <div class="row d-flex">
                    <div class="col-md-12 tracker-details">
                        <div class="col-md-12 card-cnt p-0 d-flex">
                            <div class="card mr-5">
                                <div class="card-body">
                                    <h5 class="card-title bg-blue">Candidate</h5>
                                    <div class="detail-section">
                                        <h6>{{$recruiterDetails->Candidate}}</h6>
                                        <p class="card-text">
                                            {{$recruiterDetails->Clinician_ID}}
                                        </p>

                                        <?php if(LD::checkFeatureFlag('intake-scheduling-ipea') && ($bucket=='INTAKE-SCHEDULE-IPEA' || $bucket=='INTAKE-TODAY-IPEA')){ ?>
                                            <p class="card-text">
                                                <span class="att-value editable-select"
                                                            data-emptytext='Click to add Branch Location' data-type='select'
                                                            data-url='{{route("editable")}}'
                                                            data-source='<?php echo htmlspecialchars(json_encode($facilities),ENT_QUOTES, 'UTF-8');?>'
                                                            data-pk="{{ $recruiterDetails->id }}" data-name='aoc_facility_id'
                                                            data-value="{{ $recruiterDetails->aoc_facility_id }}"
                                                            data-table='recruiterDetails'>{{$recruiterDetails->aoc_facility_id}}</span>
                                                </p>
                                        <?php } else { ?>
                                            <p class="card-text">
                                                <span class="att-value editable-select"
                                                            data-emptytext='Click to add Branch Location' data-type='select'
                                                            data-url='{{route("editable")}}'
                                                            data-source='<?php echo htmlspecialchars(json_encode($facilities),ENT_QUOTES, 'UTF-8');?>'
                                                            data-pk="{{ $recruiterDetails->id }}" data-name='Branch_Location'
                                                            data-value="{{ $recruiterDetails->Branch_Location }}"
                                                            data-table='recruiterDetails'>{{$recruiterDetails->Branch_Location}}</span>
                                                </p>
                                       <?php } ?>

                                        <p class="card-text">
                                            @if($recruiterDetails->aoc_provider_id)
                                            {{isset($providerDetails->Phone) ? $providerDetails->Phone : ""}}
                                            @else
                                            {{isset($candidateDetails->homePhone) ? $candidateDetails->homePhone : ""}}
                                            @endif

                                        </p>
                                        <p class="card-text">
                                            @if($recruiterDetails->aoc_provider_id)
                                            {{isset($providerDetails->Mobile) ? $providerDetails->Mobile : ""}}
                                            @else
                                            {{isset($candidateDetails->mobile) ? $candidateDetails->mobile : ""}}
                                            @endif
                                        </p>
                                        <p class="card-text">
                                            @if($recruiterDetails->aoc_provider_id)
                                            {{isset($providerDetails->AOC_Email) ? $providerDetails->AOC_Email : ""}}
                                            @else
                                            {{isset($candidateDetails->email) ? $candidateDetails->email : ""}}
                                            @endif
                                        </p>
                                        <div class="tags-cnt">
                                            @if($recruiterDetails->Skillset != '')
                                            <span class='tag'>{{$recruiterDetails->Skillset}}</span>
                                            @endif
                                            @if($recruiterDetails->Bilingual == 'Yes')
                                            <span class='tag'>Bilingual</span>
                                            @endif
                                            @if(LD::checkFeatureFlag('cross-state-dynamic-email'))
                                                @if($crossState)
                                                    <span class='tag cross-state'>Cross-State</span>
                                                @endif
                                            @endif
                                        </div>
                                        <ul class="d-flex att-cnt">
                                            <li>
                                                <span class="att-name">Total Hours</span>
                                                <span class="att-value editable-text"
                                                    data-emptytext='Click to add Total Hours' data-type='number'
                                                    data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='Total_Hours'
                                                    data-value="{{ $recruiterDetails->Total_Hours }}"
                                                    data-table='recruiterDetails'>{{nl2br($recruiterDetails->Total_Hours)}}</span>
                                            </li>
                                            <li>
                                                <span class="att-name">Pay Rate</span>
                                                $<span class="att-value editable-text" data-inputclass="float"
                                                    data-emptytext='Click to add Pay Rate' data-type='text'
                                                    data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='Agreed_Pay_Rate'
                                                    data-table='recruiterDetails'>{{number_format((float)$recruiterDetails->Agreed_Pay_Rate, 2, '.', '')}}</span>
                                            </li>
                                            <li>
                                                <span class="att-name">GP</span>
                                                <span class="att-value editable" data-emptytext='Click to add GP'
                                                    data-type='number' data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='GP'
                                                    data-value="{{ $recruiterDetails->GP * 100}}"
                                                    data-table='recruiterDetails'>{{$recruiterDetails->GP * 100}}</span>%
                                            </li>
                                            <?php if(LD::checkFeatureFlag('intake-scheduling-ipea') && ($bucket=='INTAKE-SCHEDULE-IPEA' || $bucket=='INTAKE-TODAY-IPEA')){ ?>
                                                <li>
                                                    <span class="att-name">Approved By</span>
                                                    <span class="att-value editable-select"
                                                        data-emptytext='Click to add Approved By' data-type='select'
                                                        data-url='{{route("editable")}}'
                                                        data-pk="{{ $candidateDetails->id }}" data-name='approved_by'
                                                        data-value="{{ $candidateDetails->approved_by }}"
                                                        data-source='<?php echo htmlspecialchars(json_encode($approvedByListIntake),ENT_QUOTES, 'UTF-8');?>'
                                                        data-table='candidatesDetails'>{{nl2br($candidateDetails->approved_by)}}</span>
                                                </li>

                                            <?php } else { ?>
                                                    <li>
                                                        <span class="att-name">Approved By</span>
                                                        <span class="att-value editable-select"
                                                            data-emptytext='Click to add Approved By' data-type='select'
                                                            data-url='{{route("editable")}}'
                                                            data-pk="{{ $recruiterDetails->id }}" data-name='Approved_By'
                                                            data-value="{{ $recruiterDetails->Approved_By }}"
                                                            data-source='<?php echo htmlspecialchars(json_encode($approvedByList),ENT_QUOTES, 'UTF-8');?>'
                                                            data-table='recruiterDetails'>{{nl2br($recruiterDetails->Approved_By)}}</span>
                                                    </li>
                                            <?php } ?>
                                        </ul>
                                        @if($recruiterDetails->aoc_provider_id==0)
                                        <span class="att-value editable-text"
                                            data-emptytext='Click to assign Clinician ID' data-type='text'
                                            data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                            data-name='Clinician_ID' data-value="{{ $recruiterDetails->Clinician_ID }}"
                                            data-table='recruiterDetails'>{{$recruiterDetails->Clinician_ID}}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title bg-blue">Client</h5>
                                    <div class="detail-section">
                                        @if(isset($patientsDetails->Patient_FirstName) ||
                                        isset($patientsDetails->Patient_MiddleName) ||
                                        isset($patientsDetails->Patient_LastName))
                                        <h6>{{$patientsDetails->Patient_FirstName.' '.$patientsDetails->Patient_MiddleName.' '.$patientsDetails->Patient_LastName}}
                                        </h6>
                                        @endif
                                        <p class="card-text">
                                            {{isset($patientsDetails->PatientAccount) ? $patientsDetails->PatientAccount : ""}}
                                        </p>
                                        <p class="card-text">
                                            {{isset($branchDetails->BranchName) ? $branchDetails->BranchName : ""}}
                                        </p>
                                        <p class="card-text">
                                            {{isset($patientsDetails->Patient_Phone) ? $patientsDetails->Patient_Phone : ""}}
                                        </p>
                                        <p class="card-text">
                                            {{isset($patientsDetails->Patient_MobilPhone) ? $patientsDetails->Patient_MobilPhone : ""}}
                                        </p>
                                        <?php if(LD::checkFeatureFlag(
                                            'show-client-emergency-contacts-on-tracker-detail-and-sns-form')
                                        ) {
                                            foreach($clientEmergencyContact as $key => $value) { ?>
                                            <p class="card-text">
                                                    {{$key}}: {{$value}}
                                            </p>
                                        <?php }
                                            } ?>
                                    </div>
                                    <p class="card-text payer">
                                        @if($recruiterDetails->Payor_Source!='') Payer:
                                        {{$recruiterDetails->Payor_Source}} @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @if(count($outcomeList)>0)
                <div class="row pt-2">
                    <div class="col-md-6">
                        <label for="outcome">Outcome</label>
                        <select id="outcome" name="outcome" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            @foreach ($outcomeList as $item)
                                <option {{isset($defaultOutcome) && $defaultOutcome == $item ? "selected='selected'" : ""}}
                                value="{{  $item }}">{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(LD::checkFeatureFlag('schedule-next-step-pending-outcomes'))
                        @if(isset($pending_reasons))
                            <div class="col-md-6 pending_reason_container" style="display:none;">
                                <label for="pending_reason">Reason</label>
                                <select id="pending_reason" name="pending_reason" class="form-control" autocomplete="off">
                                    <option value="">-- Select One --</option>

                                    {{-- Pending Clinician Status --}}
                                    @if(isset($pending_reasons[ScheduleNextStepOutcomesEnum::PENDING_CLINICIAN_STATUS->value]))
                                        @foreach($pending_reasons[ScheduleNextStepOutcomesEnum::PENDING_CLINICIAN_STATUS->value] as $reason)
                                            <option data-outcome="{{ScheduleNextStepOutcomesEnum::PENDING_CLINICIAN_STATUS->value}}" value="{{ $reason }}">{{ $reason }}</option>
                                        @endforeach
                                    @endif

                                    {{-- Pending Client --}}
                                    @if(isset($pending_reasons[ScheduleNextStepOutcomesEnum::PENDING_CLIENT->value]))
                                        @foreach($pending_reasons[ScheduleNextStepOutcomesEnum::PENDING_CLIENT->value] as $reason)
                                            <option data-outcome="{{ScheduleNextStepOutcomesEnum::PENDING_CLIENT->value}}" value="{{ $reason }}">{{ $reason }}</option>
                                        @endforeach
                                    @endif

                                </select>
                            </div>
                        @endif
                    @endif
                    @if(LD::checkFeatureFlag('add-new-field-reason-when-nurse-declines'))
                        <div class="col-md-6 nurseDeclineReason" style="display:none;">
                            <label for="nurseDeclineReason">Reason</label>
                            <select id="nurseDeclineReason" name="nurseDeclineReason" class="form-control" autocomplete="off">
                                <option value="">-- Select One --</option>
                                @foreach (config('constant')['scheduleNextStepNurseDeclined'] as $item)
                                    <option {{isset($defaultOutcome) && $defaultOutcome == $item ? "selected='selected'" : ""}}
                                    value="{{  $item }}">{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="col-md-6 attampted_to_contact_container" style="display:none;">
                        <label></label>
                        <div class="custom-control custom-switch" style="margin: 12px 0px;">
                            <input type="hidden" name="attampted_to_contact" value="0" />
                            <input class="custom-control-input" type="checkbox" id="attampted_to_contact"
                                name="attampted_to_contact" value="1" />
                            <label class="custom-control-label" for="attampted_to_contact">Attempted to Contact?</label>
                        </div>
                    </div>
                    <div class="col-md-6 inactivate_reason_container" style="display:none;">
                        <label for="outcome">Reason</label>
                        <select id="inactivate_reason" name="inactivate_reason" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            <option value="no_cases">No cases fit current needs</option>
                            @if(!LD::checkFeatureFlag('prn-changes-add-clinician'))
                                <option value="prn">PRN</option>
                            @endif
                            <option value="ineligible">Ineligible for Hire/Rehire</option>
                            <option value="donotuse">Do Not Use in KanTime</option>
                        </select>
                    </div>
                    <?php if(LD::checkFeatureFlag("recruiting-ipea-convert-tracker-to-activecoverage-client")) { ?>
                        <div class="col-md-6 move-activecoverage-container" style="display:none">
                            <input type="hidden" id="active_PRN_Client_ID" name="active_PRN_Client_ID" value="">
                            <input type="hidden" id="active_PRN_PATIENT_ID" name="active_PRN_PATIENT_ID" value="">
                            <label for="active_prn_client">Select PRN Client</label>
                            <input type="input" class="form-control" id="active_prn_client" name="active_prn_client" value="">
                        </div>

                        <div class="col-md-12 move-activecoverage-container" style="display:none">
                            <div class="form-group row">
                                <div class="col-md-6  ">
                                    <label  class="w-100" for="active_shift_type">Is your preferred shift type still</label>
                                    <select
                                        class="form-control"
                                        name="active_shift_type[]"
                                        id="active_shift_type"
                                        multiple>
                                        <option value="Weekday - Days">Weekday - Days</option>
                                        <option value="Weekday - Nights">Weekday - Nights</option>
                                        <option value="Weekend - Days">Weekend - Days</option>
                                        <option value="Weekend - Nights">Weekend - Nights</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group row">
                                <div class="col-md-6">
                                    <input type="hidden" name="active_days_available_hidden" id="active_days_available_hidden" value="">
                                    <label  class="w-100" for="days_available1">What are the dates you are available next month</label>
                                    <div id="activecoverage_days_available"></div>
                                </div>
                                <div class="col-md-6">
                                    <div name="employmenttype" id="available_days">
                                        <label  class="w-100" for="available_days_selected">Available Days</label>
                                    </div>
                                    <div id="available_days_selected">

                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php } ?>

                    <div class="col-md-6 cancelled_reason_container" style="display:none;">
                        <label for="outcome">Next Step</label>
                        <select id="cancelled_reason" name="cancelled_reason" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            @if(LD::checkFeatureFlag('ipea-received-better-job') && $bucket == 'IPEA')
                                <option value="better_offer">Received better job offer</option>
                                <option value="not_work">Nurse decided not to work for AOC</option>
                                @if(LD::checkFeatureFlag('prn-changes-add-clinician'))
                                    <option value="change_prn">Change to Active Coverage</option>
                                @else
                                    <option value="change_prn">Change to PRN</option>
                                @endif
                                @if(LD::checkFeatureFlag('add-option-placed-on-case-by-different-recruiter-next-step-form'))
                                    <option value="different_recruiter">Placed on case by different recruiter</option>
                                @endif
                            @else
                                <option value="new_client">Find another client</option>
                                <option value="not_work">Nurse decided not to work for AOC</option>
                                @if(LD::checkFeatureFlag('prn-changes-add-clinician'))
                                    <option value="change_prn">Change to Active Coverage</option>
                                @else
                                    <option value="change_prn">Change to PRN</option>
                                @endif
                                @if(LD::checkFeatureFlag('add-option-placed-on-case-by-different-recruiter-next-step-form'))
                                    <option value="different_recruiter">Placed on case by different recruiter</option>
                                @endif
                            @endif
                            <option value="ineligible_for_hire">Ineligible for hire/rehire</option>
                        </select>
                    </div>
                    @if(!LD::checkFeatureFlag('active-coverage-prn-branch'))
                    <div class="col-md-6 excludeprn_container pt-2" style="display:none;">
                        <input type="hidden" id="PRN_Client_ID" name="PRN_Client_ID" value="">
                        <input type="hidden" id="PRN_PATIENT_ID" name="PRN_PATIENT_ID" value="">
                        <label for="prn_client">Select PRN Client</label>
                        <input type="input" class="form-control" id="prn_client" name="prn_client" value="">
                    </div>
                    @endif

                    @if(LD::checkFeatureFlag('intake-scheduling-ipea'))
                        @if(empty($intake_recruiter) && ($bucket=='INTAKE-TODAY-IPEA' || $bucket=='INTAKE-SCHEDULE-IPEA'))
                            <div class="col-md-6 intake_recruiter_container" style="display:none;">
                                <label for="intake_recruiter">Recruiter</label>
                                <select id="intake_recruiter" name="intake_recruiter"  class="form-control" autocomplete="off">
                                        <option value="">--- Select ---</option>
                                        @foreach($intakeRecruiters as $v)
                                        <option value="{{$v['value']}}">
                                        {{$v['text']}}</option>
                                        @endforeach
                                </select>
                            </div>
                        @endif
                    @endif
                    @if(LD::checkFeatureFlag('enable-nth-schedule-nextstep-form'))
                    <div class="col-md-6 client_match_container" style="display:none;">
                        <label for="nth_schedule">Select Schedules to Remove</label>
                        <select id="nth_schedule" name="nth_schedule[]" multiple="multiple" class="form-control" autocomplete="off">
                        <?php foreach($nthSchedule as $key => $schedule) { ?>
                                <option value="<?php echo $schedule->id?>"><?php echo $schedule->StartDayOfWeek?> - <?php echo H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->StartHour.':'.$schedule->StartMinute)?> - <?php echo H::getHelper('ConstantHelper')->getSmallTimeFormat($schedule->EndHour.':'.$schedule->EndMinute)?> </option>
                        <?php } ?>
                        </select>
                    </div>
                    @endif



                    <?php if(LD::checkFeatureFlag('reason-nurse-declined')){ ?>
                    <div class="col-md-12 reason_nurse_declined mt-2" style="display:none">
                        <label for="outcome">Select Reason</label>
                        <select id="reason_nurse_declined" name="reason_nurse_declined" class="form-control"
                            autocomplete="off">
                            <option value="">-- Select One --</option>
                            @foreach(config('constant')['ReasonNurseDeclined'] as $k => $v)
                            <option value="{{$v}}">{{$v}}</option>
                            @endforeach
                        </select>
                    </div>
                    <?php } ?>
                </div>

                @if(LD::checkFeatureFlag('client-match-sns-additional-form'))
                    <div class="col-md-12 row pt-2 client_match_container2">
                        <div class="row col-md-12">
                            <div class="col-md-12 m-0 p-0">
                                <input type="checkbox" id="hard_stop" name="hard_stop" value="1" />
                                <label for="hard_stop">Hard Stop Adjusted (if needed) </label>
                            </div>
                        </div>

                        <div class="col-md-12 d-flex  m-0 p-0">

                            <div class="custom-control custom-switch  text-center">
                                <input class="custom-control-input" type="checkbox" id="pay_rate_entered_in_kantime" value="1" name="pay_rate_entered_in_kantime"/>
                                <label class="custom-control-label" for="pay_rate_entered_in_kantime"></label>
                            </div>
                            <label  class="w-auto" for="pay_rate_entered_in_kantime">Pay Rate Entered in Kantime?</label>
                        </div>

                        <div class="col-md-12 d-flex  m-0 p-0">

                            <div class="custom-control custom-switch  text-center">
                                <input class="custom-control-input" type="checkbox" id="right_signature_sent" value="1" name="right_signature_sent"/>
                                <label class="custom-control-label" for="right_signature_sent"></label>
                            </div>
                            <label  class="w-auto" for="right_signature_sent">Override Schedule on Pay Rate Agreement?</label>
                            <div class="p-0 col-md-6 ml-3 d-flex schedule_override hide">
                                <label class="col-md-4 p-0" for="schedule_override">Schedule to Show:</label>
                                <input type="text" class="form-control col-md-8" id="schedule_override" name="schedule_override" value="">
                            </div>
                        </div>
                        <?php if(LD::checkFeatureFlag('client-match-sns-schedule')) {?>
                            <div class="col-md-12 d-flex  m-0 p-0">
                                <?php  echo $schedulegrid?>
                            </div>
                        <?php } ?>

                        <div class="col-md-12 d-flex  m-0 p-0">

                            <div class="custom-control custom-switch  text-center">
                                <input class="custom-control-input" type="checkbox" id="first_week_created" value="1" name="first_week_created"/>
                                <label class="custom-control-label" for="first_week_created"></label>
                            </div>
                            <label  class="w-auto" for="first_week_created">First Week Created</label>
                        </div>
                        <?php
                            if(LD::checkFeatureFlag('client-match-sns-patientstatus-candidatestatus')) {

                        ?>
                            <div class="row col-md-12  m-0 p-0">
                                <div class="col-md-6  m-0 p-0">
                                    <label for="hard_stop_adjusted">Status in Kantime</label>
                                    <select name="kantime_candidate_status" id="kantime_candidate_status" class="form-control">
                                        <option value="">Select Candidate Status</option>
                                        <?php foreach($candidateStatus as $candidateOption) { ?>
                                            <option value="<?php echo $candidateOption->id?>" <?php if(isset($providerDetails->EmploymentStatusID) && $providerDetails->EmploymentStatusID==$candidateOption->id) { ?> selected="selected" <?php } ?>><?php echo $candidateOption->EmploymentStatus?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                            </div>
                            <?php } else { ?>
                                <div class="row col-md-12  m-0 p-0">
                                    <div class="col-md-6  m-0 p-0">
                                        <label for="hard_stop_adjusted">Status in Kantime</label>
                                        <select name="kantime_patient_status" id="kantime_patient_status" class="form-control">
                                            <option value="">Select Patient Status</option>
                                            <?php foreach($patientStatus as $patients) { ?>
                                                <option value="<?php echo $patients->id?>" <?php if(isset($patientsDetails->Patient_CurrentStatusID) && $patientsDetails->Patient_CurrentStatusID==$patients->id) { ?> selected="selected" <?php } ?>><?php echo $patients->PatientStatus?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>

                            <?php } ?>
                    </div>
                @endif

                @if(LD::checkFeatureFlag('ipea-received-better-job'))
                    <div class="row pt-2 better_offer" style="display:none">
                        <div class="col-md-6">
                            <label for="better_offer_type">Type</label>
                            <select id="better_offer_type" name="better_offer_type" class="form-control"
                                autocomplete="off">
                                <option value="">-- Select Type --</option>
                                <option value="Hospital">Hospital</option>
                                <option value="Other Home Health Agency">Other Home Health Agency</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="better_offer_rate">Rate</label>
                            <input type="number" class="form-control" id="better_offer_rate" name="better_offer_rate" value="">
                        </div>
                    </div>
                @endif
                @endif
                <div id="change_client_container" style="display:none;">
                    <div id="change_client">
                        <div class="row pt-2">
                            <div class="col-md-12">
                                <label for="change-client">Change Client:</label>
                                <input type="input" class="form-control" id="change_client_name"
                                    aria-describedby="emailHelp" name="change_client_name" value="">
                            </div>
                        </div>
                        <div class="row pt-2">
                            <div class="col-md-6">
                                <label for="payer">Payer:</label>
                                <input type="input" class="form-control" id="payer" area-describedby="emailHelp"
                                    name="payer" value="">
                            </div>
                            <div class="col-md-6">
                                <label for="branch">Branch:</label>
                                <input type="input" class="form-control" id="client_branch" name="client_branch"
                                    value="">
                            </div>
                        </div>
                        <div class="row pt-2">
                            <div class="col-md-6">
                                <label for="total_hours">Total Hours</label>
                                <input type="input" class="form-control" id="total_hours" name="total_hours" value="">
                            </div>
                            <div class="col-md-6 ui-widget">
                                @if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules') && $isEffectiveDateGreaterThanNow)
                                    <label for="nth_schedule_ids">Select Schedule</label>
                                    <select id="nth_schedule_ids" name="nth_schedule_ids[]" multiple="multiple" class="form-control" autocomplete="off">
                                    </select>
                                    <span style="font-weight: 700; color: lightcoral;" id="nth_schedule_error_message"></span>
                                    <img id="nth_schedule_loadingGif" src="/images/LoaderIcon.gif"
                                         style="display: none; position: absolute; right: 47px; top: 70%; transform: translateY(-50%); width: 16px; height: 16px;">
                                    <script type="text/javascript">
                                        var nthScheduleSelect = window.NthScheduleSelect.init({
                                            selectSelector: '#nth_schedule_ids',
                                            totalHoursSelector: '#total_hours',
                                            loadingGifSelector: '#nth_schedule_loadingGif',
                                            errorMessageSelector: '#nth_schedule_error_message',
                                            autoPopulateHours: @json(LD::checkFeatureFlag('clinician-form-auto-populate-hours-from-schedule')),
                                            requiredMessage: "No NTH hours selected. Please select a schedule to create the tracker.",
                                            url: "{{route('getnthschedulebyclientid')}}",
                                            onTotalHoursChange: function(totalHours) {
                                                $('.att-value[data-name="Total_Hours"]').text(totalHours).attr('data-value', totalHours);
                                            },
                                        });
                                    </script>
                                @else
                                    <label for="schedule">Schedule</label>
                                    <input type="input" class="form-control" id="schedule" name="schedule" value="">
                                @endif
                            </div>
                        </div>
                        <div class="row pt-2">
                            <div class="col-md-4">
                                <label for="pay_rate">Pay Rate:</label>
                                <input type="input" class="form-control" id="pay_rate" name="pay_rate" value="">
                            </div>
                            <div class="col-md-4">
                                <label for="city">GP %</label>
                                <input type="input" class="form-control" id="gp" aria-describedby="emailHelp" name="gp"
                                    value="">
                            </div>
                            <div class="col-md-4">
                                <label for="state">Approved By</label>
                                <select id="gp_approved_by" name="gp_approved_by" class="form-control"
                                    autocomplete="off">
                                    <option value="">-- Select One --</option>
                                    @foreach ($approvedByList as $item)
                                    <option value="{{  $item }}">{{ $item }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="first_month_scheduled_container" style="display:none;">
                    <div class="row pt-2">
                        <div class="col-md-6">
                            <label for="datepicker">Date</label>
                            <div class="input_wrap">
                                <input
                                    type="text"
                                    name="first_month_scheduled"
                                    id="first_month_scheduled"
                                    class="form-control first_month_scheduled_input"

                                    readonly>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="commonfields" style="{{ (count($outcomeList)>0 ) ? 'display: none;' : '' }}">
                    <div id="candidate_note">
                        <div class="row pt-2 clinician_declined_reason">
                            <div class="col-md-12">
                                <label for="note">Reason</label>
                                <select name="declined_reason" id="declined_reason" class="form-control">
                                    <option value="">Select Reason</option>
                                    @foreach(config('constant')['clinician_declined_the_agency_reason'] as $v)
                                        <option value="{{$v}}">{{$v}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if(LD::checkFeatureFlag('active-coverage-prn-branch'))
                            <div class="change_to_active_coverage_container" style="display:none;">
                                <div class="row">
                                    <div class="col-md-6 pt-2">
                                        <label for="prn_branch">PRN Branch</label>
                                            <?php
                                            $prnBranchData = H::getHelper('ConstantHelper')->getPRNClients();
                                            ?>
                                        <select name="prn_branch" class="form-control" id="prn_branch">
                                            <option value="">-- Select One --</option>
                                            @foreach($prnBranchData as $prnBranch)
                                                <option
                                                    value="{{$prnBranch->branch}}"
                                                    data-patient="{{$prnBranch->aoc_patient_id}}"
                                                    data-client="{{$prnBranch->aoc_client_id}}">
                                                    {{$prnBranch->client_name}}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12 pt-2">
                                        <div class="alert alert-danger" style="display: none">
                                        <span class="icon">
                                            <i class="fa fa-warning" style="font-size:16px; vertical-align: unset;"></i></span>A
                                            status change request will be created for this candidate. They will appear
                                            in the Active Coverage bucket once the status change is completed.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div class="row pt-2">
                            <div class="col-md-12">
                                <label for="note">Comments:</label>
                                <textarea name="note" id="note"
                                    class="form-control">{{$recruiterDetails->Candidate_Notes}}</textarea>
                            </div>
                        </div>
                    </div>
                    <div id="schedule_appointment" class="schedule-appointment">
                        <h6>Schedule</h6>
                        <fieldset>
                            <div class="row pt-2">
                                <div class="col-md-6">
                                        <div class="row pt-2">
                                        <div class="col-md-12">
                                            <label for="schedule_type">Interview Type</label>
                                            <select id="schedule_type" name="schedule_type" class="form-control"
                                                autocomplete="off">
                                                <option value="">-- Select One --</option>
                                                @foreach ($interviewTypeData as $item)
                                                <option value="{{  $item['type'] }}"
                                                    {{ (isset($schedule_type) && $schedule_type== $item['type']) ? 'selected' : '' }}>
                                                    {{ $item['type'] }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row pt-2 showforipea">
                                        <div class="col-md-12">
                                            <label for="ipea_location_id">IPEA Location</label>
                                            <select name="ipea_location_id" class="form-control" id="ipea_location_id">
                                                <option value="">-- Select One --</option>
                                                @foreach($branches as $key => $branch)
                                                <optgroup label="{{$key}}">
                                                    @foreach($branch as $b)
                                                    <option {{isset($schedules[0]) && isset($schedules[0]->ipea_location_id) && $schedules[0]->ipea_location_id == $b['id'] ? "selected='selected'" : ""}} value="{{$b['id']}}">{{$b['branch']}}</option>
                                                    @endforeach
                                                </optgroup>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row pt-2">
                                        <div class="col-md-12">
                                            <label for="datepicker">Date</label>
                                            <input type="input" class="form-control" id="datepicker" value = "{{ ($proposed_ns_date != '') ? H::getFormatedDate($proposed_ns_date):''}}" name="appointment_date" />
                                        </div>
                                    </div>
                                    <div class="row pt-2">
                                        <div class="col-sm-12">
                                            <label for="appointment_time">Time</label>
                                            <input type="input" class="form-control" id="appointment_time" value = "{{ ($proposed_ns_time != '')?H::getFormatedTime($proposed_ns_time):''}}" name="appointment_time">
                                        </div>
                                    </div>
                                    <div class="row pt-2">
                                        <div class="col-sm-12">
                                            <div class="custom-control custom-switch custom-control-right showforipea pull-left col-md-6"
                                                style="margin: 12px 0px;">
                                                <input type="hidden" name="is_rehire" value="0" />
                                                <input class="custom-control-input" type="checkbox" id="is_rehire"
                                                    name="is_rehire" value="1" {{ (isset($recruiterDetails->is_rehire) && $recruiterDetails->is_rehire== 1) ? 'checked' : '' }}/>
                                                <label class="custom-control-label" for="is_rehire">Is this rehire?</label>
                                            </div>
                                            @if(LD::checkFeatureFlag('show-virtual-ipea') && $bucket !== IntakeCoordinatorBucket::INTAKE_SCHEDULE_IPEA->value)
                                            <div class="custom-control custom-switch custom-control-right showforipea pull-left col-md-6"
                                                style="margin: 12px 0px;">
                                                <input type="hidden" name="is_virtual" value="0" />
                                                <input class="custom-control-input" type="checkbox" id="is_virtual"
                                                    name="is_virtual" value="1" {{ (isset($schedules[0]) && isset($schedules[0]->is_virtual) && $schedules[0]->is_virtual === 1) ? 'checked' : '' }}/>
                                                <label class="custom-control-label" for="is_virtual">Virtual IPEA</label>
                                            </div>
                                            @endif
                                            <?php if(LD::checkFeatureFlag('intake-scheduling-ipea')) {
                                                    if($bucket=='INTAKE-SCHEDULE-IPEA') { ?>
                                                        <div class="custom-control custom-switch custom-control-right pull-left col-md-6 showforipea"
                                                        style="margin: 12px 0px;">
                                                            <input type="hidden" name="intake_in_home" value="0" />
                                                            <input class="custom-control-input" type="checkbox" id="intake_in_home"
                                                                name="intake_in_home" value="1" {{ (isset($recruiterDetails->intake_in_home) && $recruiterDetails->intake_in_home== 1) ? 'checked' : '' }}/>
                                                            <label class="custom-control-label" for="intake_in_home">In Home Hire</label>
                                                        </div>
                                            <?php } } ?>

                                        </div>
                                    </div>
                                </div>
                                @if(LD::checkFeatureFlag('change-client-notes-schedule-next-steps'))
                                <div class="col-md-6 ccrbefornext" style="display: none;">
                                    <p class="alert alert-danger-specific">Clinician requires a CCR before next steps can be scheduled. Please note this is a proposed date/time until the CCM has completed the CCR.</p>
                                 </div>
                                 @endif
                                <div class="col-md-6">
                                    <div class="pt-2 showforipeabranchnote" style="display: none;">
                                        <fieldset><div class="pt-2 ipeabranchnote">This is note section</div></fieldset>
                                    </div>
                                    <?php if(LD::checkFeatureFlag('ipea-upcoming-events-rescheduled-ipea')) { ?>
                                        <div class="pt-2 ipeaUpcomingEvents" style="display: none;">
                                            <h5>Upcoming Branch Events:</h5>
                                            <ul></ul>
                                        </div>
                                    <?php } ?>
                                </div>

                            </div>
                            <div class="row pt-2 showforipea">
                                <div class="col-md-12">
                                    <label for="why_nurse_chosen">Why was nurse choosen for this client?</label>
                                    <textarea name="why_nurse_chosen" id="why_nurse_chosen" cols="80" rows="7"
                                        class="form-control">{{$recruiterDetails->why_nurse_chosen}}</textarea>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    @if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class'))
                    @canPermission('showscheduleclassform')
                        <div id="trach_vent_toggle_section" class="schedule-appointment">
                            <p>Enable the toggle below to schedule a training class alongside this next step. Available dates and times are pulled from classes currently configured and update automatically based on the class you select.</p>
                            <div class="custom-control custom-switch custom-control-right" style="margin: 12px 0px;">
                                <input type="hidden" name="show_trach_vent" id="show_trach_vent" value="0" />
                                <input class="custom-control-input" type="checkbox" id="trach_vent_toggle" />
                                <label class="custom-control-label" for="trach_vent_toggle">Schedule a Training Class?</label>
                            </div>
                        </div>
                        <div id="trach_vent_class_request" class="schedule-appointment" style="display:none;">
                            <h6>Schedule Trach/Vent Class</h6>
                            <fieldset>
                                <div class="row pt-2">
                                    <div class="col-md-6">
                                        <label for="trach_vent_class_type">Class</label>
                                        <select name="trach_vent_class_type" id="trach_vent_class_type" class="form-control">
                                            @foreach ($classCategories as $key => $item)
                                            <option value="{{ $key }}">{{ $item }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="trach_vent_class_date">Date</label>
                                        <select class="form-control" id="trach_vent_class_date" disabled="disabled">
                                            <option value="">-- Select class type first --</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row pt-2">
                                    <div class="col-md-6">
                                        <label for="location_id">Branch</label>
                                        <select name="location_id" class="form-control" id="location_id">
                                            <option value="">-- Select One --</option>
                                            @foreach($branches as $key => $branch)
                                            <optgroup label="{{$key}}">
                                                @foreach($branch as $b)
                                                <option value="{{$b['id']}}">{{$b['branch']}}</option>
                                                @endforeach
                                            </optgroup>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="occurrence_id">Time</label>
                                        <select name="occurrence_id" class="form-control" id="occurrence_id" disabled="disabled">
                                            <option value="">-- Select date first --</option>
                                        </select>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                    @endcanPermission
                    @endif
                    <div id="candidate_save" class="row pt-2">
                        <div class="col-md-7 text-right">
                            <button type="button" id="candidateSaveBtn" onclick="setSchedule(this)" class="btn btn-primary">Save</button>
                            <button type="button" id="candidateCloseBtn" data-dismiss="modal" class="btn btn-primary hide">Close</button>
                        </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<x-modal>
    <x-slot name="id">activityStatusCheck</x-slot>
    <x-slot name="title">Warning</x-slot>
    <x-slot name="body">
        <div class="col-md-12">
            <p></p>
            <p id="activityStatusCheckLink"></p>
            <button type="button" data-bs-dismiss="modal" id="close-activityStatusCheck" class="btn btn-light ">Close</button>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>


<style>
.ui-autocomplete {
    z-index: 999999;
}
</style>
<link href="{{ mix('css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-datepicker.min.js') }}"></script>
<script src="{{ mix('js/confetti.js') }}"></script>
<script type="text/javascript">
    $('#close-activityStatusCheck').on('click', function (e) {
        $('#activityStatusCheck').modal('hide');
    })

  $('#simpleLeadsModal').on('show.bs.modal', function (e) {
    let bucket = "{{ $bucket }}";

    if (bucket !== 'NTH LEADS AND INQUIRIES') {
      e.preventDefault();
      e.stopImmediatePropagation();
      return false;
    }

    if (!window.simpleLeadsTriggerSource) {
      e.preventDefault();
      e.stopImmediatePropagation();
      return false;
    }
  });

    $("#first_month_scheduled").datepicker({
        todayBtn: false,
        format:"mm/dd/yyyy",
        clearBtn: true,
        multidate: true,
        todayHighlight: true,

    });
var clinicianInterviewCompleted = '<?php echo $clinicianInterviewCompleted; ?>';
var payrateApproved= '<?php echo $recruiterDetails->payrate_approved; ?>'
// Wait for the DOM to be ready
$(function() {
    $("#schedule_next_form").validate({
        rules: {
            "outcome": {
                required:true
            },
            "datepicker": {
                required: function(element) {
                    return $("#schedule_type").val() != '';
                }
            },
            "appointment_time": {
                required: function(element) {
                    return $("#schedule_type").val() != '';
                }
            },
            "prn_client": {
                required: function(element) {
                    return $("#cancelled_reason").val() == 'change_prn';
                }
            },
            @if (LD::checkFeatureFlag('active-coverage-prn-branch'))
                "prn_branch": {
                    required: function(element) {
                        return $("#cancelled_reason").val() == 'change_prn';
                    }
                },
            @endif
            "change_client_name": {
                required: function(element) {
                    return $("#outcome").val() == 'Schedule Next Step' || $("#outcome").val() == 'Change Client';
                }
            },
            "intake_recruiter" :{
                required: function(element) {
                    <?php if(LD::checkFeatureFlag('intake-scheduling-ipea')) {
                        if(empty($intake_recruiter) && ($bucket=='INTAKE-TODAY-IPEA' || $bucket=='INTAKE-SCHEDULE-IPEA')) { ?>
                                if ($("#schedule_next_form #outcome").val() == 'Need to review new clients'){
                                    return true;
                                }
                        <?php } ?>
                    <?php } ?>
                    return false;
                }
            },
             <?php if(LD::checkFeatureFlag('recruiting-ipea-convert-tracker-to-activecoverage-client')) { ?>
                    "active_prn_client" :{
                        required: function(element) {
                            if ($("#schedule_next_form #outcome").val() == 'No Longer Interested in PRN - Move to Active Coverage Status'){
                                    return true;
                                }

                        }
                    },
            <?php } ?>
            <?php if(LD::checkFeatureFlag('validation-sns-form-conflict-ccs-sns')) { ?>
                    "first_month_scheduled" :{
                        required: function(element) {
                            if ($("#schedule_next_form #outcome").val() == 'PRN Agreement Signed & First Month Scheduled'){
                                    return true;
                                }

                        }
                    },
            <?php } ?>
            "note": {
                required: function(element) {
                    if ($(
                            "#schedule_next_form  #outcome").val() == 'Pending Next Steps' ||
                            $("#schedule_next_form  #outcome").val() == 'Attempt 1' ||
                            $("#schedule_next_form  #outcome").val() == 'Attempt 2' ||
                            $("#schedule_next_form  #outcome").val() == 'Attempt 3' ||
                            $("#schedule_next_form  #outcome").val() == 'First Shift Worked' ||
                            $("#schedule_next_form  #outcome").val() == 'Rescheduled' ||
                            $("#schedule_next_form  #outcome").val() == 'Need to review new clients' ||
                            $("#schedule_next_form  #outcome").val() == 'Parent Declined Agency - Cancel Candidate Start' ||
                            $("#schedule_next_form  #outcome").val() == 'Clinician Declined the Agency' ||
                            $("#schedule_next_form  #outcome").val() == 'Schedule Next Step' ||
                            $("#schedule_next_form  #outcome").val() == 'Parent Declined Agency - IPEA Cancelled' ||
                            $("#schedule_next_form  #outcome").val() == 'NC/NS' ||
                            @if(LD::checkFeatureFlag('active-coverage-prn-branch'))
                                ($("#schedule_next_form  #outcome").val() == 'Cancelled' && $("#cancelled_reason").val() == 'change_prn') ||
                            @endif
                            ($("#schedule_next_form  #outcome").val() == 'Cancelled' && $("#cancelled_reason").val() == 'ineligible_for_hire')
                            @if(LD::checkFeatureFlag('change-outcomes-for-ipea-widget'))
                            || $("#schedule_next_form  #outcome").val() == 'No Longer Interest in PRN - Needs to Review New Clients' ||
                            $("#schedule_next_form  #outcome").val() == 'No Longer Interested in PRN - Moved to Active Coverage Status'
                            @endif
                            @if(LD::checkFeatureFlag('change-outcomes-for-ipea-without-prn-status'))
                            ||
                            $("#schedule_next_form  #outcome").val() == 'Move to Active Coverage'
                            @endif
                            @if(LD::checkFeatureFlag('add-outcomes-to-schedule-next-step-form-for-prn'))
                            || $("#schedule_next_form  #outcome").val() == 'Inactivate on tracker'
                            @endif
                            @if (LD::checkFeatureFlag("add-outcome-to-recruiting-active-coverage-bucket"))
                            || $("#schedule_next_form  #outcome").val() == 'Inactivate Tracker'
                            || $("#schedule_next_form  #outcome").val() == "No longer interested in Active Coverage - move to needs to review"
                            || $("#schedule_next_form  #outcome").val() == "Confirm Completed"
                            @endif
                            @if(LD::checkFeatureFlag('candidate-starts-sns-new-outcome-candidate-ncns'))
                            || $("#schedule_next_form  #outcome").val() == 'Candidate NCNS to first shift'
                            @endif

                        )
                        return true;
                }
            },
        },
        errorPlacement: function(error, element) {
            if (element.attr('id') === 'nth_schedule_ids') {
                error.insertAfter($('#nth_schedule_error_message'));
            }
        },
        highlight: function(element, errorClass, validClass) {
            if (element.id === 'nth_schedule_ids') {
                $('#nth_schedule_ids').parent().find('button.multiselect').addClass(errorClass).removeClass(validClass);
            } else if (element.type === 'radio') {
                this.findByName(element.name).addClass(errorClass).removeClass(validClass);
            } else {
                $(element).addClass(errorClass).removeClass(validClass);
            }
        },
        unhighlight: function(element, errorClass, validClass) {
            if (element.id === 'nth_schedule_ids') {
                $('#nth_schedule_ids').parent().find('button.multiselect').removeClass(errorClass).addClass(validClass);
            } else if (element.type === 'radio') {
                this.findByName(element.name).removeClass(errorClass).addClass(validClass);
            } else {
                $(element).removeClass(errorClass).addClass(validClass);
            }
        }
    });
});
$('#nth_schedule').multiselect({
    includeSelectAllOption: true,
    buttonWidth: '100%'
});
</script>

<script type="text/javascript">
var bucketTypeForm = '<?php echo $bucket?>';
function showConfetti() {
    startConfetti();
    setTimeout(function() {
        stopConfetti();
    }, 1000);
}
$(document).ready(function() {
    $('#right_signature_sent').change(function(e){
        if($(this).is(":checked")){
            $('.schedule_override').removeClass('hide');
        }else{
            $('.schedule_override').addClass('hide');
        }
    });
    $("#change_client_name").autocomplete({
        source: "{{ route('getScheduleClient') }}",
        minLength: 2,
        select: function(event, ui) {
            $('#patient_id').val(ui.item.id);
            $("#payer").val(ui.item.payer);
            $("#client_branch").val(ui.item.branch_name);
            $("#note").val('');
            @if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules') && $isEffectiveDateGreaterThanNow)
                nthScheduleSelect.loadForClient(ui.item.id);
            @endif
        },
        change: function(event, ui) {
            if (!ui.item) {
                $('#change_client_name').val('');
                $('#patient_id').val('');
                $("#client_branch").val('');
                $("#payer").val('');
                @if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules') && $isEffectiveDateGreaterThanNow)
                    nthScheduleSelect.reset();
                @endif
            }
        }
    });
    var flag = false;

    $('#datepicker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($('#datepicker').parent());
    });
    $('#appointment_time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '06:00:00',
        'maxTime': '21:00:00'
    });
    @if (LD::checkFeatureFlag('active-coverage-prn-branch'))
    $('#cancelled_reason').change(function() {
        if ($(this).val() == 'change_prn'){
            $('.change_to_active_coverage_container').show();
        } else {
            $('.change_to_active_coverage_container').hide();
        }
    });
    $('#prn_branch').change(function() {
        $('.change_to_active_coverage_container .alert-danger').show();
    });
    @elseif(LD::checkFeatureFlag('reason-nurse-declined'))
        $('#cancelled_reason').change(function() {
        $('.error').removeClass('error');
        if ($(this).val() == 'not_work' && $('#outcome').val() == 'Nurse Declined') {
            $('.reason_nurse_declined').show();
        } else {
            $('.reason_nurse_declined').hide();
        }
        @if(LD::checkFeatureFlag('ipea-received-better-job'))
            if ($(this).val() == 'better_offer') {
                jQuery('.better_offer').show();
            } else {
                jQuery('.better_offer').hide();
            }
        @endif
        if ($(this).val() == 'change_prn') {
            jQuery('.excludeprn_container').show();
        } else {
            jQuery('.excludeprn_container').hide();
        }
    });
    @endif
    $('#outcome').change(function() {
        console.log($(this).val());
        $('.error').removeClass('error');
        <?php if(LD::checkFeatureFlag('clinician-interview-completed-condition')){ ?>
            $('.alert.alert-danger').remove();
            $('#candidate_save #candidateSaveBtn').removeClass('hide');
            $('#candidate_save #candidateCloseBtn').addClass('hide');
        <?php } ?>
        @if (LD::checkFeatureFlag('active-coverage-prn-branch'))
            $('.change_to_active_coverage_container').hide();
        @endif

        if ($(this).val() != '') {
            $('#commonfields').show();
            $('.clinician_declined_reason').hide();
        } else {
            $('#commonfields').hide();
        }
        <?php if(LD::checkFeatureFlag('reason-nurse-declined')){ ?>
        if ($(this).val() == 'Nurse decided not to work for Angels of Care') {
            $('.reason_nurse_declined').show();
        } else {
            $('.reason_nurse_declined').hide();
        }
        <?php } ?>
        <?php if(LD::checkFeatureFlag('poop-sky-animation')){ ?>
        console.log($(this).val())
        <?php if($bucket == 'CANDIDATE STARTS') {?>
        if ($(this).val() != '' && $(this).val() != 'First Shift Worked' && $(this).val() != 'Rescheduled') {
            showPoopSkyAnimation();
        }
        <?php } ?>
        <?php } ?>
        $("#client_match_container").hide();
        $('#first_month_scheduled_container').hide();
        $('.move-activecoverage-container').hide();
        $('.cancelled_reason_container label').text('Next Step');
        <?php if(LD::checkFeatureFlag('intake-scheduling-ipea')) {
                if ($bucket=='INTAKE-TODAY-IPEA' || $bucket=='INTAKE-SCHEDULE-IPEA') { ?>
                    $(".intake_recruiter_container").hide();

                <?php }
            } ?>
        if ($(this).val() == 'NC/NS' || $(this).val() == 'Pending Nurse' || $(this).val() ==
            'Pending Parent') {
            $('.attampted_to_contact_container').show();
            $('.cancelled_reason_container').hide();
            $('.inactivate_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'Cancelled' || $(this).val() == 'Nurse Declined' || $(this).val() ==
            'Parent Declined' ||
            $(this).val() == 'Client authorization denied' || $(this).val() ==
            'Client/caregiver declined services' || $(this).val() ==
            'Client hospitalized - nurse discussing other cases' ||
            $(this).val() == 'Nurse declined case - nurse discussing other cases') {
            $('.attampted_to_contact_container').hide();
            if ($(this).val() == 'Cancelled') {
                $('.cancelled_reason_container label').text('Reason');
            }
            $('.cancelled_reason_container').show();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
            $('#note').val('');
        } else if ($(this).val() == 'Inactivate on tracker' || $(this).val() == 'Inactivate Tracker') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            @if (LD::checkFeatureFlag('add-outcome-to-recruiting-active-coverage-bucket')) {}
                $('.inactivate_reason_container').hide();
            @else
                $('.inactivate_reason_container').show();
            @endif
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        }else if ($(this).val() == 'No longer interested in Active Coverage - move to needs to review') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        }else if ($(this).val() == 'Confirm Completed') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        }else if ($(this).val() == 'Completed School Set Up') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'Schedule Next Step') {
            <?php if($isOutcomeCompleted==0) { ?>
                    console.log("here");
                    $('#commonfields').prepend('<div class="alert alert-danger mt-3" role="alert">You are unable to Schedule Next Steps until the status of the Clinical Competency Review is completed by the Clinical Care Manager. </div>');
                    $('#candidate_note').hide();
                    $('#schedule_appointment').hide();
                    $('#candidate_save #candidateSaveBtn').addClass('hide');
                    $('.attampted_to_contact_container').hide();
                    $('.cancelled_reason_container').hide();
                    jQuery('.excludeprn_container').hide();
                    $('.inactivate_reason_container').hide();
                    $('#change_client_container').hide();
            <?php } else { ?>
                    $('.cancelled_reason_container').hide();
                    jQuery('.excludeprn_container').hide();
                    <?php if($bucket == 'CCRS') {?>
                            $('.attampted_to_contact_container').hide();
                            $('.cancelled_reason_container').hide();
                            jQuery('.excludeprn_container').hide();
                            $('.inactivate_reason_container').hide();
                            $('#candidate_note').show();
                            $("#note").val('');
                            $('#schedule_appointment').show();
                            $("#scheduling").val('1');
                            $('#change_client_container').hide();
                    <?php } elseif($bucket == 'NEED TO BE SCHEDULED BY RA') {?>
                        $('.attampted_to_contact_container').hide();
                            $('.cancelled_reason_container').hide();
                            jQuery('.excludeprn_container').hide();
                            $('.inactivate_reason_container').hide();
                            $('#candidate_note').show();
                            $("#note").val('');
                            $('#schedule_appointment').show();
                            $("#scheduling").val('1');
                            $('#change_client_container').hide();
                    <?php } elseif($bucket == 'IPEA') {?>
                        <?php if(LD::checkFeatureFlag('clinician-interview-completed-condition')){ ?>
                            <?php if($recruiterDetails->live_ipea_status!='Need to review new clients'){ ?>
                                if(clinicianInterviewCompleted != 'false' && clinicianInterviewCompleted == 'Good Fit'){
                                    $('.attampted_to_contact_container').hide();
                                    $('.cancelled_reason_container').hide();
                                    jQuery('.excludeprn_container').hide();
                                    $('.inactivate_reason_container').hide();
                                    $('#candidate_note').show();
                                    $("#note").val('');
                                    $('#schedule_appointment').show();
                                    $("#scheduling").val('3');
                                    $('#change_client_container').hide();
                                    $('#candidate_save #candidateSaveBtn').removeClass('hide');
                                    $('#candidate_save #candidateCloseBtn').addClass('hide');
                                }else{
                                    $('#candidate_note').hide();
                                    $('#schedule_appointment').hide();
                                    $('#commonfields').prepend('<div class="alert alert-danger mt-3" role="alert">You are unable to "Schedule Next Step" until the status of the "Clinician Interview Completed" field is updated by the Branch. </div>');
                                    $('#candidate_save #candidateSaveBtn').addClass('hide');
                                    $('#candidate_save #candidateCloseBtn').removeClass('hide');
                                }
                            <?php }else{ ?>
                                $('.attampted_to_contact_container').hide();
                                $('.cancelled_reason_container').hide();
                                jQuery('.excludeprn_container').hide();
                                $('.inactivate_reason_container').hide();
                                $('#candidate_note').show();
                                $("#note").val('');
                                $('#schedule_appointment').show();
                                $("#scheduling").val('1');
                                $('#change_client_container').show();
                            <?php } ?>
                        <?php }else{ ?>
                            $('.attampted_to_contact_container').hide();
                            $('.cancelled_reason_container').hide();
                            jQuery('.excludeprn_container').hide();
                            $('.inactivate_reason_container').hide();
                            $('#candidate_note').show();
                            $("#note").val('');
                            $('#schedule_appointment').show();
                            $("#scheduling").val('3');
                            $('#change_client_container').hide();
                        <?php } ?>
                    <?php }else{ ?>
                        $('.attampted_to_contact_container').hide();
                        $('.cancelled_reason_container').hide();
                        jQuery('.excludeprn_container').hide();
                        $('.inactivate_reason_container').hide();
                        $('#candidate_note').show();
                        $("#note").val('');
                        $('#schedule_appointment').show();
                        $("#scheduling").val('1');
                        <?php if(LD::checkFeatureFlag('intake-scheduling-ipea')) {

                                if ($bucket=='INTAKE-TODAY-IPEA') { ?>
                                    $('#schedule_type')
                                    .find('option')
                                    .remove()
                                    .end()
                                    .append('<option value="Candidate Start">Candidate Start</option>')
                                    .val('Candidate Start');
                                    $('#schedule_type').change();
                                    $('.showforipea').hide();

                                <?php }


                                if($bucket=='INTAKE-SCHEDULE-IPEA' || $bucket=='INTAKE-TODAY-IPEA' ) { ?>
                                    $('#change_client_container').hide();
                                <?php } else { ?>
                                    $('#change_client_container').show();
                                <?php } ?>
                        <?php } else { ?>
                            $('#change_client_container').show();
                        <?php } ?>

                    <?php } ?>
                <?php } ?>
        } else if ($(this).val() == 'Change Client') {
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');

            $('#schedule_appointment').show();

            $("#scheduling").val('1');
            $('#change_client_container').show();
        } else if ($(this).val() == 'Nurse decided not to work for Angels of Care') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'First Shift Worked' || $(this).val() == 'First Shift Confirm') {
            showConfetti();
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').hide();
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'Need to review new clients' || $(this).val() == 'Move to PRN') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').show();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').hide();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();

            <?php if(LD::checkFeatureFlag('intake-scheduling-ipea')) {
                if ($bucket=='INTAKE-TODAY-IPEA' || $bucket=='INTAKE-SCHEDULE-IPEA') { ?>
                    $(".intake_recruiter_container").show();
                    $('#candidate_note').show();
                <?php }
            } ?>

        } else if ($(this).val() == 'Rescheduled') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').show();
            <?php if($bucket == 'IPEA') {?>
                $("#schedule_type").val('IPEA');
                $('.showforipea').show();
            <?php } ?>

            <?php if(LD::checkFeatureFlag('intake-scheduling-ipea')) {
                if ($bucket=='INTAKE-TODAY-IPEA') { ?>

                    $('#schedule_type')
                                    .find('option')
                                    .remove()
                                    .end()
                                    .append('<option value="IPEA">IPEA</option>')
                                    .val('IPEA');
                                    $('#schedule_type').change();
                    $('.showforipea').show();
                <?php }
            } ?>

            $("#scheduling").val('1');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'Pending Next Steps') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'No Longer Interest in PRN - Needs to Review New Clients') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'No Longer Interested in PRN - Moved to Active Coverage Status') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'Move to Active Coverage') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'No Longer Interested in PRN - Move to Active Coverage Status') {
            $('.attampted_to_contact_container').hide();
            $('.move-activecoverage-container').show();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else if ($(this).val() == 'PRN Agreement Signed & First Month Scheduled') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').hide();
            $('#first_month_scheduled_container').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
        } else {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').show();
            $("#scheduling").val('1');
            $('#change_client_container').hide();
        }

        @if(LD::checkFeatureFlag('intake-scheduling-ipea'))
            if($(this).val()=='Parent Declined Agency - IPEA Cancelled' || $(this).val()=='Parent Declined Agency - Cancel Candidate Start'
            ||  $(this).val()=='Clinician Declined the Agency') {
                $("#scheduling").val('0');
                $('#schedule_appointment').hide();
                if($(this).val()=='Clinician Declined the Agency'){
                    $(".clinician_declined_reason").show();
                }
            }
        @endif

        @if(LD::checkFeatureFlag('enable-nth-schedule-nextstep-form'))
        $(".client_match_container").hide();
        if($(this).val()=='Client Match') {
            $(".client_match_container").show();
            <?php if($bucket == 'PEPI' || $bucket == 'WEPI') {?>
                $("#schedule_type").val('Candidate Start');
            <?php } ?>
        }
        @endif
        @if(LD::checkFeatureFlag('client-match-sns-additional-form'))
        $(".client_match_container2").hide();
        if($(this).val()=='Client Match') {
            $(".client_match_container2").show();
            <?php if($bucket == 'PEPI' || $bucket == 'WEPI') {?>
                $("#schedule_type").val('Candidate Start');
            <?php } ?>
        }
        @endif

        @if(LD::checkFeatureFlag('pay-rate-agreement-sns-form-changes'))
        if($(this).val()=='Client Match' && payrateApproved == '0') {

            $('#commonfields').prepend('<div class="alert alert-danger mt-3" role="alert">You are unable to Client Match until the pay rate is approved by the manager. Please reach out to your manager for approval before proceeding.</div>');
            $(".client_match_container").hide();
            $(".client_match_container2").hide();
            $('#candidate_note').hide();
            $('#schedule_appointment').hide();
            $('#candidate_save #candidateSaveBtn').addClass('hide');
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            jQuery('.excludeprn_container').hide();
            $('.inactivate_reason_container').hide();
            $('#change_client_container').hide();
        }
        @endif

        @if(LD::checkFeatureFlag('ipea-received-better-job'))
            if (!$('.cancelled_reason_container').is(":visible")) {
                jQuery('#cancelled_reason').val('');
                jQuery('.better_offer').hide();
            }
        @endif

        @if(LD::checkFeatureFlag('add-new-outcomes-to-new-to-review-new-clients-bucket') || LD::checkFeatureFlag('add-attempts-outcome-schedule-by-ra-widget') || LD::checkFeatureFlag('add-new-outcome-to-ipea-widget-sns-form'))
            if($(this).val()=='Attempt 1' || $(this).val()=='Attempt 2' || $(this).val()=='Attempt 3') {
                $('#schedule_appointment').hide();
            }
        @endif
        @if(LD::checkFeatureFlag('add-ccr-outcome-enhancements'))
            if($(this).val()=='Pending Parent Attempt 1' || $(this).val()=='Pending Parent Attempt 2' || $(this).val()=='Pending Parent Attempt 3' || $(this).val()=='Pending Candidate Attempt 1' || $(this).val()=='Pending Candidate Attempt 2' || $(this).val()=='Pending Candidate Attempt 3' || $(this).val()=='Client in hospital') {
                $('#schedule_appointment').hide();
            }
        @endif

        @if(LD::checkFeatureFlag('add-new-field-reason-when-nurse-declines'))
            if($(this).val()=='Nurse Declined') {
                $('.nurseDeclineReason').show();
            }else{
                $('.nurseDeclineReason').hide();
            }
        @endif

        @if(LD::checkFeatureFlag('candidate-starts-sns-new-outcome-candidate-ncns'))

        if($(this).val()=='Candidate NCNS to first shift') {
            <?php if($bucket == 'CANDIDATE STARTS') {?>
                $('.attampted_to_contact_container').show();
                $('.cancelled_reason_container').hide();
                $('.inactivate_reason_container').hide();
                jQuery('.excludeprn_container').hide();
                $('#candidate_note').show();
                $("#note").val('');
                $('#schedule_appointment').hide();
                $("#scheduling").val('0');
                $('#change_client_container').hide();
            <?php } ?>
        }
        @endif

      var PENDING_REASONS = @json($pending_reasons ?? []);

      if (typeof PENDING_REASONS !== 'undefined') {

        const selectedOutcome = $('#outcome').val();

        $('.pending_reason_container').hide();
        $('#pending_reason').val('');
        $('#pending_reason option[data-outcome]').hide();

        if (!PENDING_REASONS[selectedOutcome]) {
          return;
        }

        $('#schedule_appointment').hide();
        $('.pending_reason_container').show();
        $(`#pending_reason option[data-outcome="${selectedOutcome}"]`).show();
      }

    });
    $('#outcome').change();

    $('#schedule_type').change(function() {
        if ($(this).val() != '') {
            $('#datepicker').attr('required', 'required');
        } else {
            $('#datepicker').removeAttr('required');
        }

        if ($(this).val() == 'IPEA') {
            $('.showforipea').show();
        } else {
            $('.showforipea').hide();
        }
        @if(LD::checkFeatureFlag("change-client-notes-schedule-next-steps"))
            if($(this).val() == 'PEPI' || $(this).val() == 'WEPI' || $(this).val() == 'Training Shift' || $(this).val() == 'Candidate Start'){
                $('.ccrbefornext').show();
            }else{
                $('.ccrbefornext').hide();
            }
        @endif

        if ($(this).val() == 'CCR' || $(this).val() == 'School Set Up') {
            $('.hideforccrschool').hide();
        } else {
            $('.hideforccrschool').show();
        }
    });
    $('#schedule_type').change();

    var prnClientSelected = false;
    $("#prn_client").autocomplete({
        source: "{{ route('getPRNClient') }}",
        minLength: 2,
        select: function(event, ui) {
            $('#PRN_PATIENT_ID').val(ui.item.id);
            $('#PRN_Client_ID').val(ui.item.PatientAccount);
            prnClientSelected = true;
        }
    });
    $("#prn_client").on('keyup', function(){
        prnClientSelected = false;
    });
    $("#prn_client").on('blur', function(){
        if (!prnClientSelected) {
            $("#prn_client").val('');
        }
    });
    <?php if(LD::checkFeatureFlag('recruiting-ipea-convert-tracker-to-activecoverage-client')) { ?>
            var activeprnClientSelected = false;
            $("#active_prn_client").autocomplete({
                source: "{{ route('getPRNClient') }}",
                minLength: 2,
                select: function(event, ui) {
                    $('#active_PRN_PATIENT_ID').val(ui.item.id);
                    $('#active_PRN_Client_ID').val(ui.item.PatientAccount);
                    activeprnClientSelected = true;
                }
            });
            $("#active_prn_client").on('keyup', function(){
                activeprnClientSelected = false;
            });
            $("#active_prn_client").on('blur', function(){
                if (!activeprnClientSelected) {
                    $("#active_prn_client").val('');
                }
            });


            $('#activecoverage_days_available').datepicker({
                multidate: true,
                todayHighlight: true
            });
            $('#activecoverage_days_available').on('changeDate', function() {
                let selectedDates = $('#activecoverage_days_available').datepicker('getFormattedDate');
                $('#active_days_available_hidden').val(selectedDates);
                $('#available_days_selected').html(selectedDates.split(',').join('</br>'));

            });


            $('#active_shift_type').multiselect({
                includeSelectAllOption: true,
                buttonWidth: '100%'
            });
    <?php } ?>

});

function closeForm() {
    $('#schedule_next').modal('hide');
}

<?php if(LD::checkFeatureFlag('ipea-upcoming-events-rescheduled-ipea')) { ?>

    $('#schedule_next_form #schedule_type').on('change', function () {
        if($('#schedule_next_form #schedule_type :selected').val() != 'IPEA') {
            $('.ipeaUpcomingEvents').hide();
            $(".ipeaUpcomingEvents ul").html('');
        }
    });
    $('#schedule_next_form #ipea_location_id, #schedule_next_form #schedule_type').change(function() {
        if($('#schedule_next_form #schedule_type :selected').val() == 'IPEA' && $('#schedule_next_form #outcome :selected').val() == 'Rescheduled' ) {
            getIpeaBranchEvents();
        }else{
            $('.ipeaUpcomingEvents').hide();
        }
    });
    $('#schedule_next_form #ipea_location_id').trigger("change");
    function getIpeaBranchEvents() {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('ipea.getBranchUpcomingEvents')}}",
            type: 'get',
            data: {
                ipea_location_id: $('#schedule_next_form #ipea_location_id :selected').val(),
                _token: _token
            },
            success: function(response) {

                if(response.status) {
                    ulStr = '';
                    if(response.events.length > 0){
                        $('.ipeaUpcomingEvents').show();
                    }else{
                        $('.ipeaUpcomingEvents').hide();
                    }
                    response.events.forEach(function(event) {
                        ulStr += '<li><strong>'+event.appointment_date+'</strong><p>'+event.title+'</p>';
                        if(event.description != null){
                            ulStr += '<p>'+event.description+'</p></li>';
                        }
                        ulStr += '</li>';
                    });
                    $(".ipeaUpcomingEvents ul").html(ulStr);
                }
            }
        });
    }
<?php } ?>

var trackerWasInactive = false;

function showErrorModal(message, shouldRedirectOnBack = false, existingTrackerId = null) {
    const modal = $('#activityStatusCheck');
    modal.find('p').first().text(message);

    const linkContainer = $('#activityStatusCheckLink').empty();
    if (existingTrackerId) {
        $('<a>', { href: 'javascript:void(0);', text: 'View existing tracker' })
            .on('click', function() {
                $('.modal').modal('hide');
                openRecruiterDetails(existingTrackerId);
            })
            .appendTo(linkContainer);
    }

    modal.modal('show');

    if (shouldRedirectOnBack) {
        sessionStorage.setItem('trackerRedirectOnBack', '1');

        history.pushState({ redirectOnBack: true }, '', window.location.href);
    }
}

@if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class'))
@canPermission('showscheduleclassform')
window.trachVentClassesByDate = {};

$(document).on('change', '#trach_vent_toggle', function () {
    if ($(this).is(':checked')) {
        $('#show_trach_vent').val(1);
        $('#trach_vent_class_request').show();
    } else {
        $('#show_trach_vent').val(0);
        $('#trach_vent_class_request').hide();
        $('#trach_vent_class_type, #location_id').val('');
        resetTrachVentDateAndTime();
    }
});

function resetTrachVentDateAndTime() {
    window.trachVentClassesByDate = {};
    $('#trach_vent_class_date').html('<option value="">-- Select class type first --</option>').prop('disabled', true);
    $('#occurrence_id').html('<option value="">-- Select date first --</option>').prop('disabled', true);
}

$(document).on('change', '#trach_vent_class_type, #location_id', function () {
    resetTrachVentDateAndTime();
    getTrachVentScheduleClass();
});

function getTrachVentScheduleClass() {
    let classType = $('#trach_vent_class_type').val();
    let locationId = $('#location_id').val();

    if (!classType || !locationId) {
        return;
    }

    $.ajax({
        url: "{{route('gettrachventscheduleclass')}}",
        type: 'get',
        data: {
            trach_vent_class_type: classType,
            location_id: locationId
        },
        success: function (response) {
            window.trachVentClassesByDate = {};
            $.each(response, function (index, value) {
                if (!window.trachVentClassesByDate[value.class_date]) {
                    window.trachVentClassesByDate[value.class_date] = [];
                }
                window.trachVentClassesByDate[value.class_date].push(value);
            });

            let dateSelect = $('#trach_vent_class_date');
            dateSelect.html('<option value="">-- Select One --</option>');
            $.each(window.trachVentClassesByDate, function (classDate) {
                dateSelect.append('<option value="' + classDate + '">' + classDate + '</option>');
            });
            dateSelect.prop('disabled', false);
        }
    });
}

$(document).on('change', '#trach_vent_class_date', function () {
    let timeSelect = $('#occurrence_id');
    let selectedDate = $(this).val();

    if (!selectedDate) {
        timeSelect.html('<option value="">-- Select date first --</option>').prop('disabled', true);
        return;
    }

    timeSelect.html('<option value="">-- Select One --</option>');
    $.each(window.trachVentClassesByDate[selectedDate] || [], function (index, value) {
        timeSelect.append('<option value="' + value.id + '">' + value.class_time + (value.end_time ? ' - ' + value.end_time : '') + '</option>');
    });
    timeSelect.prop('disabled', false);
});
@endcanPermission
@endif

function setSchedule(button) {
    //alert("In Progress");
    //return false;
    if ($("#schedule_next_form").valid() == false) {
        return false;
    }

  let outcome = $('#outcome').val();
  let reason = $('#pending_reason').val();

  const PENDING_OUTCOMES = @json(ScheduleNextStepOutcomesEnum::getPendingOutcomes());

  if (PENDING_OUTCOMES.includes(outcome) && !reason) {
    $('#pending_reason')
      .css('border', '1px solid red')
      .focus();
    return false;
  }

  $('#pending_reason').css('border', '');


    if ($("#schedule_next_form #ipea_location_id").val() == '' && $("#schedule_next_form #schedule_type").val() == 'IPEA') {
        $("#schedule_next_form #ipea_location_id").focus();
        return false;
    }

    if ($('#trach_vent_toggle').is(':checked')) {
        let missingField = null;
        if (!$('#trach_vent_class_type').val()) missingField = $('#trach_vent_class_type');
        else if (!$('#location_id').val()) missingField = $('#location_id');
        else if (!$('#trach_vent_class_date').val()) missingField = $('#trach_vent_class_date');
        else if (!$('#occurrence_id').val()) missingField = $('#occurrence_id');

        if (missingField) {
            alert('Please complete all training class fields (Class, Branch, Date, Time) before saving.');
            missingField.focus();
            return false;
        }
    }

    if (!window.scheduleNextSubmitLock.acquire()) {
        return false;
    }
    console.log($("#schedule_next_form").serialize());
    let serializedForm = $("#schedule_next_form").serializeArray();
    const isExpanded = $('button[data-target="#NEED_TO_REVIEW_NEW_CLIENTS"]').attr('aria-expanded') === 'true';
    serializedForm.push({
        name: 'needs_to_review_expanded',
        value: isExpanded
    })
    $.ajax({
        url: "{{ route('saveScheduleNext') }}",
        type: "POST",
        data: serializedForm, //only input
        success: function(response) {
            console.log(response);
            window.scheduleNextSubmitLock.release();
            if (response) {
                $("#successBody").html(response.message);
                if (response.success == 1) {

                    $("#scheduleNextFormDiv").hide();
                    $("#scheduleNextSuccessDiv").show();
                    //$("#scheduleNextForm").modal('show');
                    $("#schedule_next_form")[0].reset();
                } else {
                    $("#scheduleNextFormDiv").show();
                    $("#scheduleNextSuccessDiv").hide();
                }

            }
        },
        error: function (response) {
            window.scheduleNextSubmitLock.release();
            const data = response.responseJSON;

            if (response.status === 429 && data?.error === 'duplicate_submit') {
                showErrorModal(data.message, false);
                return;
            }

            if (response.status === 409 && data?.error === 'inactive') {
                showErrorModal(data.message, false);
                return;
            }

            if (response.status === 409 && data?.error === 'kantime_hours_exceeded') {
                showErrorModal(data.message, false, data.existing_tracker_id);
                return;
            }

            if (response.status === 422 && data?.error === 'missing_schedule') {
                showErrorModal(data.message, false);
                return;
            }

            showErrorModal('Unable to verify tracker status at this time. Please try again later.', true);
        }
    });
}

window.addEventListener('popstate', function () {
    if (sessionStorage.getItem('trackerRedirectOnBack')) {
        sessionStorage.removeItem('trackerRedirectOnBack');
        window.location.reload();
    }
});
</script>

<script type="text/javascript">
$(document).ready(function() {
    $('#schedule_next').on('shown.bs.modal', function() {
        $("#scheduleNextFormDiv").show();
        $("#scheduleNextSuccessDiv").hide();


    });
});
$(document).ready(function() {
        $('#schedule_next_form #ipea_location_id').change(function() {
            getIpeaBranchNotes();
        });
        @if(isset($schedules[0]) && isset($schedules[0]->ipea_location_id))
            $('#schedule_next_form #ipea_location_id').change();
        @endif
});
function getIpeaBranchNotes() {
    $('.showforipeabranchnote').hide();
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('getipeabranchnotes')}}",
        type: 'post',
        data: {
            ipea_location_id: $('#schedule_next_form #ipea_location_id :selected').val(),
            _token: _token
        },
        success: function(response) {
            if(response.notes != '') {
                $('.showforipeabranchnote').show();
                $(".ipeabranchnote").html(response.notes);
            }

        }
    });
}
</script>
