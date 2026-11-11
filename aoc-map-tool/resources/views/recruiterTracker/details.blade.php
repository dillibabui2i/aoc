<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\ConstantHelper;
    $outcomeAsso = ['PEPI'=>'PEPI-Outcome','WEPI'=>'WEPI-Outcome','IPEA'=>'IPEA_Outcome'];

    $new = 0;
    $current = 0;
    $users = H::getLoggedInUser();
    if($recruiterDetails->aoc_provider_id){
        $current = 1;
    } else {
        $new = 1;
    }

        $approvedByList = [];
        $approvedUserList = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates');
        foreach($approvedUserList as $user) {
            $approvedByList[] = $user->name;
        }
    //Client Status
	$clientStatus  = '';
    if(isset($patientsDetails->id)){
        $clientStatus = H::getHelper('ConstantHelper')->getClientStatusById($patientsDetails->id);
    }
?>

<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<style>
.tag.editable {
    text-decoration-line: underline;
    text-decoration-style: dashed;
    border-bottom: none;
}
</style>
<x-modal>
    <x-slot name="id">recruiterTrackerDetails</x-slot>
    <x-slot name="size">w80</x-slot>
    <x-slot name="title">Tracker Details \ {{$recruiterDetails->Candidate}}</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <!-- <a href=""><i class="fa fa-pencil"></i></a> -->
            <a href="javascript:void(0);" data-id="{{$recruiterDetails->id}}" id="addScheduleForm"><i
                    class="fa fa-calendar-times-o"></i></a>
            @if(isset($recruiterDetails->linked_IPEA) && $recruiterDetails->linked_IPEA !='' &&
            $recruiterDetails->linked_IPEA != null)
            <a href="javascript:void(0);" onclick='updateNurseIpeaInfo({{$recruiterDetails->linked_IPEA}})'><i
                    class="fa fa-vcard"></i></a>
            @endif
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$recruiterDetails->id}}','tracker','Recruiter Tracker Notes Added')"><i
                class="fa  fa-sticky-note-o"></i></a>

            <?php if(LD::checkFeatureFlag('conversation-icon-on-tracker')){ ?>
                @if($recruiterDetails->aoc_provider_id != 0)
                <a href="javascript:void(0);" data-candidate-name="{{$providerDetails->fullName}}" data-candidate-id="{{$providerDetails->id}}" data-candidate-phone="{{$providerDetails->Mobile}}"  id="startConversationWithClinicianBtn" >
                    <i class="fa fa-comment-o"></i>
                </a>
                @else
                <a href="javascript:void(0);" data-candidate-name="{{$candidateDetails->fullName}}" data-candidate-id="{{$candidateDetails->id}}" data-candidate-phone="{{$candidateDetails->mobile}}"  id="startConversationWithCandidateBtn" >
                    <i class="fa fa-comment-o"></i>
                </a>
                @endif

            <?php } ?>
            <?php if(LD::checkFeatureFlag('client-profile-icon-on-tracker-detail-and-schedule-next-step') &&
            !empty($patientsDetails)){ ?>
                &nbsp;<a href="javascript:void(0)"
                onclick="getClientDetails('{{$patientsDetails->id}}')">
                    <i class="fa fa-user" aria-hidden="true">
                        </i></a>
            <?php } ?>
        </div>
        <div class="col-md-6 text-right pull-right">
            @if(LD::checkFeatureFlag('tracker-current-candidate-status'))
            <span class='editable-select btn btn-primary' data-emptytext='Click to Change Candidate Current Status'
                data-type='select'
                data-source='<?php echo htmlspecialchars(json_encode(config('constant')['candidateCurrentStatus']),ENT_QUOTES, 'UTF-8');?>'
                data-value='{{ nl2br($recruiterDetails->Candidate_Current_Status) }}' data-url='{{route("editable")}}'
                data-pk="{{ $recruiterDetails->id }}" data-name='Candidate_Current_Status'
                data-table='recruiterDetails'>
                {{ nl2br($recruiterDetails->Candidate_Current_Status) }}
            </span>
            @else
            <span class='btn btn-primary'>
                {{ nl2br($recruiterDetails->Candidate_Current_Status) }}
            </span>
            @endif
        </div>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">


        <div class="row d-flex">
            <div class="col-md-9 tracker-details">
                <div class="col-md-12 d-flex p-0">
                    <div class="col-md-6 p-0">
                        <dl class="col-md-12 p-0 d-flex">
                            @if(!H::canPermissions(['edit-uncontrollable']))
                            <dt class="col-sm-4 p-0 text-right">Recruiter: </dt>
                            <dd class="col-sm-8 text-left">
                                <span class='editable-select' data-emptytext='Click to Recruiter' data-type='select'
                                    data-source="{{json_encode(H::getHelper('ConstantHelper')->getRecruitersWithIds())}}"
                                    data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                    data-value='{{$recruiterDetails->Recruiter}}' data-name='Recruiter'
                                    data-table='recruiterDetails'>
                                    {{ nl2br($recruiterDetails->Recruiter) }}
                                </span>
                            </dd>
                            @endif
                            @if(H::canPermissions(['edit-uncontrollable']))
                            <dl class="col-md-7 p-0 d-flex">
                                <dt class="col-sm-6 p-0 text-right">Recruiter: </dt>
                                <dd class="col-sm-6 text-left">
                                    <span class='editable-select' data-emptytext='Click to Recruiter' data-type='select'
                                        data-source="{{json_encode(H::getHelper('ConstantHelper')->getRecruitersWithIds())}}"
                                        data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                        data-value='{{$recruiterDetails->Recruiter}}' data-name='Recruiter'
                                        data-table='recruiterDetails'>
                                        {{ nl2br($recruiterDetails->Recruiter) }}
                                    </span>
                                </dd>
                            </dl>
                            <dl class="col-md-5 p-0 d-flex">
                                <dt class="col-sm-8 p-0 text-right">Uncontrollable: </dt>
                                <dd class="col-sm-4 text-left">
                                    <span class='editable-select' data-emptytext='Click to Uncontrollable' data-type='select'
                                        data-source='<?php echo json_encode(H::getHelper('ConstantHelper')->getYesNoOptions());?>'
                                        data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                        data-value='{{$recruiterDetails->uncontrollable}}' data-name='uncontrollable'
                                        data-table='recruiterDetails'>
                                        {{ nl2br($recruiterDetails->uncontrollable) }}
                                    </span>
                                </dd>
                            </dl>
                            @endif
                        </dl>
                        @if(!H::canPermissions(['edit-uncontrollable']))
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Recruiting Admin: </dt>
                            <dd class="col-sm-8 text-left">{{H::getRecruiterAdmin($recruiterDetails->user_id)}}</dd>
                        </dl>
                        @else
                        <dl class="col-md-6 p-0 d-flex">
                            <dt class="col-sm-7 p-0 text-right">Recruiting Admin: </dt>
                            <dd class="col-sm-9 pr-0 text-left">{{H::getRecruiterAdmin($recruiterDetails->user_id)}}</dd>
                        </dl>
                        @endif
                        @if(LD::checkFeatureFlag('edit-whiteboard-row'))
                            @canPermission(['edit-whiteboard-row'])
                                @if(!H::canPermissions(['edit-uncontrollable']))
                                    <dl class="col-md-12 p-0 d-flex">
                                        <dt class="col-sm-4 p-0 text-right">Whiteboard Row: </dt>
                                        <dd class="col-sm-8 text-left">
                                            <span class='editable-text' data-emptytext='Click to Change Whiteboard Row' data-type='text'
                                                data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                                data-value='{{$recruiterDetails->whiteboard_row}}' data-name='whiteboard_row'
                                                data-table='recruiterDetails'>
                                                {{ nl2br($recruiterDetails->whiteboard_row) }}
                                            </span>
                                        </dd>
                                    </dl>
                                @else
                                    <dl class="col-md-6 p-0 d-flex">
                                        <dt class="col-sm-7 p-0 text-right">Whiteboard Row: </dt>
                                        <dd class="col-sm-5 pr-0 text-left">
                                            <span class='editable-text' data-emptytext='Click to Change Whiteboard Row' data-type='text'
                                                data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                                data-value='{{$recruiterDetails->whiteboard_row}}' data-name='whiteboard_row'
                                                data-table='recruiterDetails'>
                                                {{ nl2br($recruiterDetails->whiteboard_row) }}
                                            </span>
                                        </dd>
                                    </dl>
                                @endif
                            @endcanPermission
                        @endif
                    </div>
                    <div class="col-md-6 p-0">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Bonus Status: </dt>
                            <dd class='col-sm-8 text-left'>
                                @if (LD::checkFeatureFlag("make-bonus-status-paid-date-read-only"))
                                    {{ nl2br($recruiterDetails->Bonus_Status) }}
                                @else
                                    <span class='editable-select' data-emptytext='Click to Change Bonus Status'
                                        data-type='select'
                                        data-source='<?php echo json_encode(config('constant')['bonusStatus']);?>'
                                        data-value='{{ nl2br($recruiterDetails->Bonus_Status) }}'
                                        data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                        data-name='Bonus_Status' data-table='recruiterDetails'>
                                        {{ nl2br($recruiterDetails->Bonus_Status) }}
                                    </span>
                                @endif
                            </dd>
                        </dl>
                        @if(LD::checkFeatureFlag('show-hours-worked'))
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Hours Worked: </dt>
                            <dd class='col-sm-8 text-left'>
                                <span>
                                    {{ nl2br($recruiterDetails->Hours_Worked) }}
                                </span>
                            </dd>
                        </dl>
                        @endif
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Paid Date: </dt>
                            <dd class="col-sm-8 text-left">
                                @if (LD::checkFeatureFlag("make-bonus-status-paid-date-read-only"))
                                    {{H::getFormatedDate($recruiterDetails->Bonus_Paid_Date)}}
                                @else
                                    <span class='editable' data-emptytext='Click to Change Paid Date'
                                        data-format="mm/dd/yyyy" data-type='date'
                                        data-value='{{ nl2br($recruiterDetails->Bonus_Paid_Date) }}'
                                        data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                        data-name='Bonus_Paid_Date' data-table='recruiterDetails'>
                                        {{H::getFormatedDate($recruiterDetails->Bonus_Paid_Date)}}
                                    </span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Candidate</h5>
                            <div class="detail-section">
                                <h6>{{$recruiterDetails->Candidate}}
                                @if(LD::checkFeatureFlag('add-gender-tag-in-tracker-detail-page'))
                                    @if(!empty($providerDetails->Gender))
                                        <span class='tag' style="float: right;">{{$providerDetails->Gender}}</span></h6>
                                    @endif
                                @endif
                                <p class="card-text">


                                    <input type="radio" value="" disabled="disabled" name="identify_candidate"
                                        <?php if($new) { ?> checked <?php } ?>> <span>New</span>
                                    <input type="radio" value="" disabled="disabled" name="identify_candidate"
                                        <?php if($current) { ?> checked <?php } ?>> <span>Current</span>
                                    @if(LD::checkFeatureFlag('add-view-resume-button-in-recruiting-tracker-page'))
                                        @if(!empty($recruiterDetails->aoc_candidate_id))
                                            <button type="button" class="tag" style="float: right;"
                                                onclick="getCandidateResume('<?php echo $recruiterDetails->aoc_candidate_id?>')">
                                                View Resume
                                            </button>
                                        @endif
                                    @endif
                                </p>

                                <p class="card-text">
                                    {{isset($providerDetails->ClinicianID) ? $providerDetails->ClinicianID : ""}}

                                </p>
                                <p class="card-text">
                                    {{$recruiterDetails->Branch_Location}}
                                </p>
                                <p class="card-text">
                                    @if($recruiterDetails->aoc_provider_id)
                                    {{isset($providerDetails->Phone) ? $providerDetails->Phone : ""}}
                                    @else
                                    @if(isset($candidateDetails->id))
                                    <span class="att-value editable-text" data-emptytext='Click to add phone'
                                    data-type='text' data-url='{{route("editable")}}'
                                    data-pk="{{ $candidateDetails->id }}" data-name='homePhone'
                                    data-value="{{ isset($candidateDetails->homePhone) ? $candidateDetails->homePhone : "" }}"
                                    data-table='candidatesDetails'>{{isset($candidateDetails->homePhone) ? $candidateDetails->homePhone : ""}}</span>
                                    @endif
                                    @endif

                                </p>
                                <p class="card-text">
                                    @if($recruiterDetails->aoc_provider_id)
                                    {{isset($providerDetails->Mobile) ? $providerDetails->Mobile : ""}}
                                    @else
                                    @if(isset($candidateDetails->id))
                                    <span class="att-value editable-text" data-emptytext='Click to add mobile'
                                    data-type='text' data-url='{{route("editable")}}'
                                    data-pk="{{ $candidateDetails->id }}" data-name='mobile'
                                    data-value="{{ isset($candidateDetails->mobile) ? $candidateDetails->mobile : "" }}"
                                    data-table='candidatesDetails'>{{isset($candidateDetails->mobile) ? $candidateDetails->mobile : ""}}</span>
                                    @endif
                                    @endif
                                </p>
                                <p class="card-text">
                                    @if($recruiterDetails->aoc_provider_id)
                                        {{isset($providerDetails->External_Email) ? $providerDetails->External_Email : ""}}
                                    @else
                                    @if(isset($candidateDetails->id))
                                    <span class="att-value editable-text" data-emptytext='Click to add email'
                                    data-type='text' data-url='{{route("editable")}}'
                                    data-pk="{{ $candidateDetails->id }}" data-name='email'
                                    data-value="{{ isset($candidateDetails->email) ? $candidateDetails->email : "" }}"
                                    data-table='candidatesDetails'>{{isset($candidateDetails->email) ? $candidateDetails->email : ""}}</span>
                                    @endif
                                    @endif
                                </p>

                                <div class="tags-cnt">
                                    @if($recruiterDetails->Skillset != '')
                                    <!-- <span class='tag'>{{$recruiterDetails->Skillset}}</span> -->
                                        @if(LD::checkFeatureFlag('change-att-to-aide-in-add-clinician-form'))
                                            <span class='editable-select tag' data-emptytext='Click to skillset'
                                                data-type='select'
                                                data-source='<?php echo json_encode(config('constant')['newSkills']);?>'
                                                data-value='{{ nl2br($recruiterDetails->Skillset) }}'
                                                data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                                data-name='Skillset' data-table='recruiterDetails'>
                                                {{ nl2br($recruiterDetails->Skillset) }}
                                            </span>
                                        @else
                                            <span class='editable-select tag' data-emptytext='Click to skillset'
                                                data-type='select'
                                                data-source='<?php echo json_encode(config('constant')['skills']);?>'
                                                data-value='{{ nl2br($recruiterDetails->Skillset) }}'
                                                data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                                data-name='Skillset' data-table='recruiterDetails'>
                                                {{ nl2br($recruiterDetails->Skillset) }}
                                            </span>
                                        @endif
                                    @endif
                                    @if($recruiterDetails->Bilingual == 'Yes')
                                    <span class='tag'>Bilingual</span>
                                    @endif
                                </div>
                                <ul class="d-flex att-cnt">
                                    <li>
                                        <span class="att-name">Total Hours</span>
                                        @if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules'))
                                        <span id="new_totalhours-counter"></span>
                                        @else
                                        <span class="att-value editable-text" data-emptytext='Click to add Total Hours'
                                            data-type='number' data-url='{{route("editable")}}'
                                            data-pk="{{ $recruiterDetails->id }}" data-name='Total_Hours'
                                            data-value="{{ $recruiterDetails->Total_Hours }}"
                                            data-table='recruiterDetails'>{{nl2br($recruiterDetails->Total_Hours)}}</span>
                                        @endif
                                    </li>
                                    @if (LD::checkFeatureFlag('bonus_report'))
                                        @if (H::canPermissions('edit.recruiting'))
                                            <li>
                                                <span class="att-name">Bonus Amount</span>
                                                $<span class="att-value editable" data-inputclass="float"
                                                    data-emptytext='Click to add Bonus Amount' data-type='number'
                                                    data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                                    data-name='bonus_amount' data-value="{{ $recruiterDetails->bonus_amount}}"
                                                    data-table='recruiterDetails'>{{$recruiterDetails->bonus_amount}}</span>
                                            </li>
                                        @elseif(H::canPermissions('view.recruiting'))
                                            <li>
                                                <span class="att-name">Bonus Amount</span>
                                                $<span class="att-value" data-table='recruiterDetails'>{{$recruiterDetails->bonus_amount}}</span>
                                            </li>
                                        @elseif($recruiterDetails->user_id == $users['id'])
                                            <li>
                                                <span class="att-name">Bonus Amount</span>
                                                $<span class="att-value" data-table='recruiterDetails'>{{$recruiterDetails->bonus_amount}}</span>
                                            </li>
                                        @endif
                                    @endif
                                    <li>
                                        <span class="att-name">Pay Rate</span>
                                        $<span class="att-value editable-text" data-inputclass="float"
                                            data-emptytext='Click to add Pay Rate' data-type='text'
                                            data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                            data-name='Agreed_Pay_Rate'
                                            data-table='recruiterDetails'>{{number_format((float)$recruiterDetails->Agreed_Pay_Rate, 2, '.', '')}}</span>
                                    </li>
                                    <li>
                                        <span class="att-name">GP</span>
                                        <span class="att-value editable" data-inputclass="float"
                                            data-emptytext='Click to add GP' data-type='text'
                                            data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                            data-name='GP' data-value="{{ $recruiterDetails->GP * 100}}"
                                            data-table='recruiterDetails'>{{$recruiterDetails->GP * 100}}</span>%
                                    </li>
                                    <li>
                                        <span class="att-name">Approved By</span>
                                        <span class="att-value editable-select"
                                            data-emptytext='Click to add Approved By' data-type='select'
                                            data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                            data-name='Approved_By' data-value="{{ $recruiterDetails->Approved_By }}"
                                            data-source='<?php echo htmlspecialchars(json_encode($approvedByList),ENT_QUOTES, 'UTF-8');?>'
                                            data-table='recruiterDetails'>{{nl2br($recruiterDetails->Approved_By)}}</span>
                                    </li>
                                </ul>
                                @if(!LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules'))
                                <p><span>Schedule: </span>
                                    <span class="att-value editable-text" data-emptytext='Click to add schedule'
                                        data-type='text' data-url='{{route("editable")}}'
                                        data-pk="{{ $recruiterDetails->id }}" data-name='Schedule'
                                        data-value="{{ $recruiterDetails->Schedule }}"
                                        data-table='recruiterDetails'>{{$recruiterDetails->Schedule}}
                                    </span>
                                </p>
                                @endif
                                @if($recruiterDetails->aoc_provider_id==0)
                                <span class="att-value editable-text" data-emptytext='Click to assign Clinician ID'
                                    data-type='text' data-url='{{route("editable")}}'
                                    data-pk="{{ $recruiterDetails->id }}" data-name='Clinician_ID'
                                    data-value="{{ $recruiterDetails->Clinician_ID }}"
                                    data-table='recruiterDetails'>{{$recruiterDetails->Clinician_ID}}</span>
                                @endif
                                    @if($recruiterDetails->referred_by != NULL)
                                    <p><span>Referred by: </span><span class="att-value" data-table='recruiterDetails'>
                                        {{H::getHelper('ConstantHelper')->getProviderNameByID($recruiterDetails->referred_by)}}
                                    </span></p>
                                    @endif
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client</h5>
                            <div class="detail-section">
                                <div class="row">
                                    <div class="col-lg-6">
										<p class="card-text">
                                            <?php $name='';
                                                    if(!empty($patientsDetails->Patient_FirstName)) {
                                                        $name.=$patientsDetails->Patient_FirstName;
                                                    }
                                                    if(!empty($patientsDetails->Patient_MiddleName)) {
                                                        $name.=" ".$patientsDetails->Patient_MiddleName;
                                                    }
                                                    if(!empty($patientsDetails->Patient_LastName)) {
                                                        $name.=" ".$patientsDetails->Patient_LastName;
                                                    }
                                                    //echo trim($name);
                                                    ?>

                                            <?php if(LD::checkFeatureFlag('add-edit-client-on-tracker')) { ?>
                                                    <span class="att-value editable-autocomplete" data-emptytext='Change Client'
                                                    data-type='text' data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='aoc_patient_id'
                                                    data-value=""
                                                    data-inputclass="autocomplete-client"
                                                    data-table='recruiterDetails'>{{trim($name)}}</span>
                                            <?php } else { echo trim($name); } ?>

										</p>
                                        <?php if(isset($recruiterDetails->aoc_patient_id) && $recruiterDetails->aoc_patient_id==0){ ?>
                                        <p class="card-text">
                                            <span id="client-card-account"><i>Client on Tracker:
                                                </i>{{isset($recruiterDetails->Client_Name) ? $recruiterDetails->Client_Name : ""}}</span>
                                        </p>
                                        <?php } ?>
                                        <p class="card-text">
                                            <span
                                                id="client-card-account">{{isset($patientsDetails->PatientAccount) ? $patientsDetails->PatientAccount : ""}}</span>
                                        </p>
                                        <p class="card-text">
                                            <span
                                                id="client-card-branch">{{isset($branchDetails->BranchName) ? $branchDetails->BranchName : ""}}</span>
                                        </p>
                                        <p class="card-text">
                                            <span
                                                id="client-card-phone">{{isset($patientsDetails->Patient_Phone) ? $patientsDetails->Patient_Phone : ""}}</span>
                                        </p>
                                        <p class="card-text">
                                            <span
                                                id="client-card-mobile">{{isset($patientsDetails->Patient_MobilPhone) ? $patientsDetails->Patient_MobilPhone : ""}}</span>
                                        </p>
                                        <?php if(LD::checkFeatureFlag(
                                            'show-client-emergency-contacts-on-tracker-detail-and-sns-form')
                                        ) {
                                            foreach($clientEmergencyContact as $key => $value) { ?>
                                            <p class="card-text">
                                                <span id="client-card-mobile">
                                                    {{$key}}: {{$value}}
                                                </span></p>
                                        <?php }
                                            } ?>
                                    </div>
                                    <div class="col-lg-6">
                                        {!!isset($patientsDetails->skillDisplayValue) ? $patientsDetails->skillDisplayValue : ""!!}
                                    </div>
                                </div>
                            </div>
                            @if (isset($patientsDetails->CF_NurseLanguage) && $patientsDetails->CF_NurseLanguage != '')
                            <p class="card-text p-2">
                                <span>Nurse Language: {{$patientsDetails->CF_NurseLanguage}}</span>
                            </p>
                            @endif
                            <p class="card-text pl-2">
                                <?php if(LD::checkFeatureFlag('change-ccc-label-on-recruiter-tracker')) { ?>
                                    <?php if(isset($patientsDetails->ccc_id) && $patientsDetails->ccc_id != ''){ ?>
                                        <span>CCC: {{H::getHelper('ConstantHelper')
                                        ->getUserById($patientsDetails->ccc_id)}}</span>
                                    <?php } ?>
                                <?php } else {?>
                                    <span>CCC: {{isset($patientsDetails->ClientCareCoordinator) ?
                                    H::getHelper('ConstantHelper')
                                    ->getCCCNameByProviderID($patientsDetails->ClientCareCoordinator) : ""}}</span>
                                <?php } ?>
                            </p>

                            <?php if(LD::checkFeatureFlag('new-field-add-to-recruiting-page')){ ?>
                            <p class="card-text pl-2">
                                <?php if(isset($patientsDetails->ccm_id) && $patientsDetails->ccm_id != ''){ ?>
                                    <span>CCM: {{H::getHelper('ConstantHelper')->getUserById($patientsDetails->ccm_id)}}</span>
                                <?php } ?>
                                <?php if($clientStatus != ''){ ?>
                                    <br />
                                    <span>Client Status: {{$clientStatus}}</span>
                                <?php } ?>
                                <?php if(isset($patientsDetails->id) && $clientStatus == 'Intake'){
                                ?>
                                    <br />
                                    <span>Goal Soc Date : {{isset($patientsDetails->CF_GoalSOCDate) ? H::getFormatedDate($patientsDetails->CF_GoalSOCDate) :''}}</span>
                                <?php } ?>
                            </p>
                            <?php } ?>
                            <?php if(LD::checkFeatureFlag('client-preferences-tags-on-tracker-detail')){ ?>
                                <div class="col-lg-12 pt-1 pb-4">
                                    {!!isset($patientsDetails->clientPreference) ? $patientsDetails->clientPreference : ""!!}
                                </div>
                            <?php } ?>
                            <p class="card-text payer">
                                <span id="client-card-payer">Payer: {{$recruiterDetails->Payor_Source}}</span>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 p-0 mt-3 mb-3">
                    <label for="note">Clinician Specifications:</label>

                    <div class="card">
                        <div class="card-body p-3">
                            <span class="att-value editable-textarea" data-inputclass="textarea-size"
                                data-emptytext='Click to add Comments' data-type='textarea'
                                data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                data-name='Candidate_Notes' data-value="{{ $recruiterDetails->Candidate_Notes }}"
                                data-table='recruiterDetails'>{{$recruiterDetails->Candidate_Notes}}</span>
                        </div>
                    </div>
                </div>
                @if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules'))
                    <div class="col-md-6 p-0 mt-3 mr-4 d-inline-block align-top">
                        <h5>Appointments</h5>
                @else
                    <div class="col-md-8 p-0 mt-3">
                @endif
                    <table class="table" aria-describedby="">
                        <tr>
                            <th>Type</th>
                            <th>Scheduled</th>
                            <th>Date</th>
                            <th>Outcome</th>
                            <th>*</th>
                        </tr>

                        @foreach($scheduleType as $k)
                        @php
                        $flag = 0
                        @endphp
                        @foreach($schedules as $key)
                        @if($k['id'] == $key->type_id)
                        @php
                        $flag = 1;
                        $tmpTime = str_replace('12:00am','',H::getFormatedTime($key->appointment_date.'
                        '.$key->appointment_time));
                        @endphp
                        <tr>
                            <td>{{$k['type']}}</td>
                            <td>{{H::getFormatedDate($key->scheduled_date)}}</td>
                            <td>@if($key->type_id == 2) {{H::getFormatedLongDateTime($key->created_at)}} @else {{H::getFormatedDate($key->appointment_date)}}
                                {{$tmpTime}} @endif </td>
                            <td style="white-space: normal; word-break: break-word;">
                                {{$key->outcome}}
                            </td>
                            <td>{{$key->attempt_count}}</td>
                        </tr>
                        @endif
                        @endforeach

                        @if($flag == 0)
                        <tr>
                            <td>{{$k['type']}}</td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        @endif

                        @endforeach
                    </table>
                </div>
                @if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules'))
                <div class="col-md-5 p-0 mt-3 d-inline-block align-top">
                    <h5>Schedule <a href="javascript:void(0)" onclick="addNthSchedule();"><i class="fa fa-pencil ml-2 text-dark" aria-hidden="true"></i></a></h5>
                    <table class="table" id="trackernthschedule">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Hours</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
                @endif

            </div>
            <div class="col-md-3 d-flex p-0">
                <x-activity-log>
                    <x-slot name="trackerId">{{$recruiterDetails->id}}</x-slot>
                </x-activity-log>
            </div>
        </div>


    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

@if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules'))
<x-modal>
    <x-slot name="id">addNthSchedulePopup</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="title">Add Schedule</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <form method="post" autocomplete="off" id="nth_schedule_add" class="scaffold-form" enctype="multipart/form-data">
            @csrf
            @if (LD::checkFeatureFlag('add-schedule-validation'))
            <input type="hidden"  name="recruiter_nth_schedule_ids" id="recruiter_nth_schedule_ids" value="{{$recruiter_nth_schedule_ids}}"/>
            @endif
            <input type="hidden"  name="aoc_patient_id" id="aoc_patient_id" value="{{$recruiterDetails->aoc_patient_id}}"/>
            <div class="row pt-2">
                <label for="nth_schedule_ids" class="col-md-4">Schedule:</label>
                <select id="nth_schedule_ids" name="nth_schedule_ids[]" multiple="multiple" class="form-control" autocomplete="off">
                    @foreach($nthSchedules as $nthScheduleKey => $nthScheduleValue)
                        <option {{(in_array($nthScheduleValue['id'], explode(',', $recruiterDetails->nth_schedule_ids))) ? 'selected' :''}} value="{{$nthScheduleValue['id']}}">{{$nthScheduleValue['StartDayOfWeek'] . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($nthScheduleValue['StartHour'] . ':' . $nthScheduleValue['StartMinute']) . ' - ' . H::getHelper('ConstantHelper')->getSmallTimeFormat($nthScheduleValue['EndHour'] . ':' . $nthScheduleValue['EndMinute'])}}</option>
                    @endforeach
                </select>
                <script type="text/javascript">
                    $('#nth_schedule_ids').multiselect({
                        includeSelectAllOption: true,
                        buttonWidth: '100%',
                    });
                </script>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer">
        @if (LD::checkFeatureFlag('add-schedule-validation'))
            <button type="button" onclick="validateNthSchedules(this)" class="btn btn-primary">Save</button>
        @else
            <button type="button" onclick="saveNthSchedule(this)" class="btn btn-primary">Save</button>
        @endif
    </x-slot>
</x-modal>
@endif
<script>
$(document).ready(function() {
    $(document).on('focus', '.form-control.autocomplete-client', function(e) {
        $(".form-control.autocomplete-client").val("");
        $(".form-control.autocomplete-client").autocomplete({
            source: "{{ route('getScheduleClient') }}",
            minLength: 2,
            select: function(event, ui) {

                $(".form-control.autocomplete-client").val(ui.item.id);
                $(".editable-submit").trigger("click");
                $('#client-card-payer').text(ui.item.payer);
                $('#client-card-phone').text(ui.item.phone);
                $('#client-card-account').text(ui.item.account);
                $('#client-card-branch').text(ui.item.branch_name);
                setTimeout(function() {
                    $("span[data-inputclass='autocomplete-client']").html(ui.item
                        .value);
                }, 1500);
                return false;
            }
        });
    });



});


function reloadTrackerHistory(id, type, destination) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('trackerhistory')}}",
        type: 'get',
        data: {
            id: id,
            type: type,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $(destination).html(data);
        }
    });
}

$(document).ready(function() {
    reloadTrackerHistory('<?php echo $recruiterDetails->id?>', 'trackerhistory', '.activity-log');
    @if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules'))
    $("#trackernthschedule").DataTable({
        "order": [
            ["0", "desc"]
        ],
        "ajax": {
            "url": "{{route('getTrackerNthSchedule')}}",
            data: {
                'tracker_id': '{{$recruiterDetails->id}}'
            },
        },
        "drawCallback": function (oSettings, json) {$("#new_totalhours-counter").html(oSettings.json.counthours);},
        "deferRender": true,
        "processing": true,
        "serverSide": true,
        "searching": false,
        "lengthChange": false,
        "info": false,
        "ordering": false,
        "bAutoWidth": false,
        "columns": [
            {"data":"id","visible":false},
            {"data":"StartDayOfWeek","visible":true},
            {"data":"StartTime","visible":true},
            {"data":"EndTime","visible":true},
            {"data":"ShiftHours","visible":true},
            {"data":"Action"}
        ]
    });
    @else
    $("#trackernthschedule").DataTable({
        "order": [
            ["0", "desc"]
        ],
        "ajax": {
            "url": "{{route('getTrackerNthSchedule')}}",
            data: {
                'tracker_id': '{{$recruiterDetails->id}}'
            },
        },
        "deferRender": true,
        "processing": true,
        "serverSide": true,
        "searching": false,
        "lengthChange": false,
        "info": false,
        "ordering": false,
        "bAutoWidth": false,
        "columns": [
            {"data":"id","visible":false},
            {"data":"StartDayOfWeek","visible":true},
            {"data":"StartTime","visible":true},
            {"data":"EndTime","visible":true},
            {"data":"ShiftHours","visible":true},
            {"data":"Action"}
        ]
    });
    @endif
});

@if(LD::checkFeatureFlag('add-schedule-matching-the-nth-schedules'))
function addNthSchedule() {
    $('#scheduleconflictmsg').remove();
    $('#addNthSchedulePopup').modal('show');
}

function saveNthSchedule(e) {
    e.disabled = true;
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('saveTrackerNthSchedule')}}",
        type: 'post',
        data: {
            tracker_id: '{{$recruiterDetails->id}}',
            nth_schedule_ids: $('#nth_schedule_ids').val(),
            _token: _token
        },
        success: function(data) {
            e.disabled = false;
            if (data.status) {
                $('#addNthSchedulePopup').modal('hide');
                $('#trackernthschedule').DataTable().ajax.reload();
            }
        }
    });
}

function removeNthSchedule(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('removeTrackerNthSchedule')}}",
        type: 'post',
        data: {
            tracker_id: '{{$recruiterDetails->id}}',
            nth_schedule_ids: id,
            _token: _token
        },
        success: function(data) {
            if (data.status) {
                $('#trackernthschedule').DataTable().ajax.reload();
            }
        }
    });
}
@endif

function validateNthSchedules(e) {

    e.disabled = true;
    $('#scheduleconflictmsg').remove();
    $.ajax({
        url: "{{ route('validateNthSchedules') }}",
        type: "POST",
        data: $("#nth_schedule_add").serialize(), //only input
        success: function(response) {
            e.disabled = false;
            if (response.status == 'true') {
                if (response.conflictMsg) {
                    $('<div class="row pt-2" id="scheduleconflictmsg"><div class="col-md-12" ><div class="alert alert-danger" role="alert">' + response.conflictMsg + '</div></div></div>').insertAfter($("#nth_schedule_add #aoc_patient_id"));

                    let _token = $('meta[name="csrf-token"]').attr('content');
                    var formData = {
                        _token:_token,
                        patient_id: $("#aoc_patient_id").val()
                    };
                    console.log($("#nth_schedule_add").serialize());

                    $.ajax({
                        url: "{{ route('getpatientstatebyid') }}",
                        type: "GET",
                        data: formData, //only input
                        success: function(response) {

                            if(response.state=='PA' || response.state=='FL'  || response.state=='NC') {
                                $("#scheduleconflictmsg").append('<div class="col-md-12"><button type="button" onclick="saveNthSchedule(this)" class="btn btn-primary">Save Anyway</button></div>');
                                //saveNthSchedule(e);
                            }
                        }
                    });

                }
            } else {
                saveNthSchedule(e);
            }
        }
    });
}
</script>

<style>
.ui-autocomplete.ui-front {
    z-index: 1050;
}
</style>
