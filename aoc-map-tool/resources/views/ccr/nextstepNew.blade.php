@php
    use App\Helpers\LD;
    use App\Helpers\H;
    use App\Enums\CCR\OutcomeStatusEnum;
@endphp

@php
$interviewTypeList = DB::table('schedule_types')->get();
$interviewTypeData = [];
if (count($interviewTypeList)) {
$interviewTypeData = json_decode(json_encode($interviewTypeList), true);
}
$outcomeList = [];
$patientStatus = H::getHelper("ConstantHelper")->getMssqlPatientStatus();
    $approvedByList = [];
    $approvedUserList = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates');
    foreach($approvedUserList as $user) {
        $approvedByList[] = $user->name;
    }
$facilities = H::getHelper("ConstantHelper")->getFacilities()->toArray();
$facilities = array_column($facilities,'FacilityName');

$permOutcome=[
    OutcomeStatusEnum::CANDIDATE_ESTABLISHED_WITH_CLIENT->value,
    OutcomeStatusEnum::COMPLETED->value,
    OutcomeStatusEnum::CANCELLED->value,
];
$attemptArray = [];

    if($attemptCount==0) {
        $attemptArray[0] = OutcomeStatusEnum::ATTEMPT1->value;
    }
    if($attemptCount==1) {
        $attemptArray[0] = OutcomeStatusEnum::ATTEMPT2->value;
    }
    if($attemptCount==2) {
        $attemptArray[0] = OutcomeStatusEnum::ATTEMPT3->value;
    }
$outcomeList = array_merge($attemptArray,$permOutcome);

$ccrCheckboxes = H::getHelper('ConstantHelper')->getSNSCCRCheckbox();

$outcomeStatuses = [
    OutcomeStatusEnum::ATTEMPT1->value,
    OutcomeStatusEnum::ATTEMPT2->value,
    OutcomeStatusEnum::ATTEMPT3->value,
    OutcomeStatusEnum::CANDIDATE_ESTABLISHED_WITH_CLIENT->value,
    OutcomeStatusEnum::CANCELLED->value,
];

$completedStatus = OutcomeStatusEnum::COMPLETED->value;
$rd = $recruiterDetails ?? null;

@endphp
<style>
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
label.pleft {
    padding-left: 17px;
    margin-top: -20px;
    display: inherit;
}
</style>
<x-modal>
    <x-slot name="id">ccr_sns_form</x-slot>
    @if ($type == 'coverage')
        <x-slot name="title">Clinical Competency Review \ {{ isset($providerDetails->fullName) ? $providerDetails->fullName : optional($coverageDetails)->new_clinician_name }}</x-slot>
    @else
        <x-slot name="title">Clinical Competency Review \ {{ optional($rd)->Candidate ?? 'N/A' }}</x-slot>
    @endif
    <x-slot name="size">w40</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            @if ($type == 'coverage')
                <a data-bs-dismiss="modal" href="javascript:void(0);"
                   onclick="event.preventDefault();openNotes('{{optional($recruitingSchedule)->coverage_id}}','coverage','Coverage Tracker Notes Added')"><i
                        class="fa  fa-sticky-note-o"></i></a>
            @else
                <a data-bs-dismiss="modal" href="javascript:void(0);"
                   onclick="event.preventDefault();openNotes('{{ optional($rd)->id }}','tracker','Recruiter Tracker Notes Added')"><i
                        class="fa  fa-sticky-note-o"></i></a>
            @endif
        </div>
        @if (!empty(optional($recruitingSchedule)->outcome))
            <div class="col-md-6 text-right pull-right">
                <span class='btn btn-primary'>
                    {{ nl2br(optional($recruitingSchedule)->outcome) }}
                </span>
            </div>
        @endif
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

            <form method="post" autocomplete="off" id="schedule_next_form_ccr" class="scaffold-form"  enctype="multipart/form-data">
                @csrf
                @if ($type == 'coverage')
                    <input type="hidden" id="mode" name="mode" value="Edit">
                    <input type="hidden" id="patient_id" name="patient_id" value="{{ optional($recruitingSchedule)->aoc_patient_id }}">
                    <input type="hidden" id="provider_id" name="provider_id" value="{{ optional($recruitingSchedule)->aoc_provider_id }}">
                    <input type="hidden" id="facility_id" name="facility_id" value="{{ optional($recruitingSchedule)->aoc_facility_id }}">
                    <input type="hidden" id="recruitingScheduleId" name="recruitingScheduleId" value="{{ $recruitingScheduleId }}">
                    <input type="hidden" id="coverage_id" name="coverage_id" value="{{ optional($recruitingSchedule)->coverage_id ?? '' }}">
                @else
                    <input type="hidden" id="mode" name="mode" value="Edit">
                    <input type="hidden" id="tracker_id" name="tracker_id" value="{{ $tracker_id ?? '' }}">
                    <input type="hidden" id="patient_id" name="patient_id" value="{{ optional($rd)->aoc_patient_id ?? '' }}">
                    <input type="hidden" id="candidate_id" name="candidate_id" value="{{ optional($rd)->aoc_candidate_id ?? '' }}">
                    <input type="hidden" id="provider_id" name="provider_id" value="{{ optional($rd)->aoc_provider_id ?? '' }}">
                    <input type="hidden" id="facility_id" name="facility_id" value="{{ optional($rd)->aoc_facility_id ?? '' }}">
                    <input type="hidden" id="recruitingScheduleId" name="recruitingScheduleId" value="{{ $recruitingScheduleId }}">
                @endif
                <div class="row d-flex">
                    <div class="col-md-12 tracker-details">
                        <div class="col-md-12 card-cnt p-0 d-flex">
                            <div class="card mr-5">
                                <div class="card-body">
                                    @if ($type == 'coverage')
                                    <h5 class="card-title bg-blue">Clinician</h5>
                                    @else
                                    <h5 class="card-title bg-blue">Candidate</h5>
                                    @endif
                                    <div class="detail-section">
                                        @if ($type == 'coverage')
                                            <h6 id="candidatename">{{isset($providerDetails->fullName) ? $providerDetails->fullName : optional($coverageDetails)->new_clinician_name}}</h6>
                                            <p class="card-text">
                                                {{isset($providerDetails->ClinicianID) ? $providerDetails->ClinicianID : ''}}
                                            </p>
                                        @else
                                            <h6 id="candidatename">{{ optional($rd)->Candidate ?? 'N/A' }}</h6>
                                            <p class="card-text">
                                                {{ optional($rd)->Clinician_ID }}
                                            </p>
                                        @endif
                                        @if ($type == 'coverage')
                                            <p class="card-text">
                                                @if (isset($providerDetails->FacilityID))
                                                    {{H::getHelper('ConstantHelper')->getFacilityNameByFacilityId($providerDetails->FacilityID)}}
                                                @endif
                                            </p>
                                            <p class="card-text">
                                                {{isset($providerDetails->Phone) ? $providerDetails->Phone : ""}}
                                            </p>
                                            <p class="card-text">
                                                {{isset($providerDetails->Mobile) ? $providerDetails->Mobile : ""}}
                                            </p>
                                            <p class="card-text">
                                                {{isset($providerDetails->AOC_Email) ? $providerDetails->AOC_Email : ""}}
                                            </p>
                                            <div class="tags-cnt">
                                                @if(isset($providerDetails->DisciplineName) && $providerDetails->DisciplineName != '')
                                                    <span class='tag'>{{$providerDetails->DisciplineName}}</span>
                                                @endif
                                                @if(isset($providerDetails->Bilingual) && $providerDetails->Bilingual == 1)
                                                    <span class='tag'>Bilingual</span>
                                                @endif
                                            </div>
                                            <ul class="d-flex att-cnt">
                                                <li>
                                                    <span class="att-name">Shift Date</span>
                                                    <span class="att-value">{{H::getFormatedDate(optional($coverageDetails)->shift_date)}}</span>
                                                </li>
                                                <li>
                                                    <span class="att-name">Schedule</span>
                                                    <span class="att-value">{{ optional($coverageDetails)->Schedule_Time }}</span>
                                                </li>
                                                <li>
                                                    <span class="att-name">Hours</span>
                                                    <span class="att-value">{{ optional($coverageDetails)->Hours }}</span>
                                                </li>
                                            </ul>
                                        @else
                                            @if($rd)
                                                <p class="card-text">
                                            <span class="att-value editable-select" data-emptytext='Click to add Branch Location' data-type='select' data-url='{{route("editable")}}' data-source='<?php echo htmlspecialchars(json_encode($facilities),ENT_QUOTES, 'UTF-8');?>' data-pk="{{ $rd->id }}" data-name='Branch_Location' data-value="{{ $rd->Branch_Location }}" data-table='recruiterDetails'>{{ $rd->Branch_Location }}</span>
                                                </p>
                                                <p class="card-text">
                                                    @if($rd->aoc_provider_id)
                                                        {{ isset($providerDetails->Phone) ? $providerDetails->Phone : "" }}
                                                    @else
                                                        {{ isset($candidateDetails->homePhone) ? $candidateDetails->homePhone : "" }}
                                                    @endif
                                                </p>
                                                <p class="card-text">
                                                    @if($rd->aoc_provider_id)
                                                        {{ isset($providerDetails->Mobile) ? $providerDetails->Mobile : "" }}
                                                    @else
                                                        {{ isset($candidateDetails->mobile) ? $candidateDetails->mobile : "" }}
                                                    @endif
                                                </p>
                                                <p class="card-text">
                                                    @if($rd->aoc_provider_id)
                                                        {{ isset($providerDetails->AOC_Email) ? $providerDetails->AOC_Email : "" }}
                                                    @else
                                                        {{ isset($candidateDetails->email) ? $candidateDetails->email : "" }}
                                                    @endif
                                                </p>
                                                <div class="tags-cnt">
                                                    @if($rd->Skillset != '')
                                                        <span class='tag'>{{ $rd->Skillset }}</span>
                                                    @endif
                                                    @if($rd->Bilingual == 'Yes')
                                                        <span class='tag'>Bilingual</span>
                                                    @endif
                                                </div>
                                                <ul class="d-flex att-cnt">
                                                    <li>
                                                        <span class="att-name">Total Hours</span>
                                                        <span class="att-value editable-text" data-emptytext='Click to add Total Hours' data-type='number' data-url='{{route("editable")}}' data-pk="{{ $rd->id }}" data-name='Total_Hours' data-value="{{ $rd->Total_Hours }}" data-table='recruiterDetails'>{{nl2br($rd->Total_Hours)}}</span>
                                                    </li>
                                                    <li>
                                                        <span class="att-name">Pay Rate</span> $<span class="att-value editable-text" data-inputclass="float" data-emptytext='Click to add Pay Rate' data-type='text' data-url='{{route("editable")}}' data-pk="{{ $rd->id }}" data-name='Agreed_Pay_Rate' data-table='recruiterDetails'>{{number_format((float)$rd->Agreed_Pay_Rate, 2, '.', '')}}</span>
                                                    </li>
                                                    <li>
                                                        <span class="att-name">GP</span>
                                                        <span class="att-value editable" data-emptytext='Click to add GP' data-type='number' data-url='{{route("editable")}}' data-pk="{{ $rd->id }}" data-name='GP' data-value="{{ $rd->GP * 100}}" data-table='recruiterDetails'>{{ $rd->GP * 100 }}</span>%
                                                    </li>
                                                    <li>
                                                        <span class="att-name">Approved By</span>
                                                        <span class="att-value editable-select" data-emptytext='Click to add Approved By' data-type='select' data-url='{{route("editable")}}' data-pk="{{ $rd->id }}" data-name='Approved_By' data-value="{{ $rd->Approved_By }}" data-source='<?php echo htmlspecialchars(json_encode($approvedByList),ENT_QUOTES, 'UTF-8');?>' data-table='recruiterDetails'>{{nl2br($rd->Approved_By)}}</span>
                                                    </li>
                                                </ul>
                                                @if($rd->aoc_provider_id == 0)
                                                    <span class="att-value editable-text" data-emptytext='Click to assign Clinician ID' data-type='text' data-url='{{route("editable")}}' data-pk="{{ $rd->id }}" data-name='Clinician_ID' data-value="{{ $rd->Clinician_ID }}" data-table='recruiterDetails'>{{ $rd->Clinician_ID }}</span>
                                                @endif
                                            @else
                                                <p class="text-muted"><em>Candidate data not available</em></p>
                                            @endif
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
                                        <h6 id="clientname">{{$patientsDetails->Patient_FirstName.' '.$patientsDetails->Patient_MiddleName.' '.$patientsDetails->Patient_LastName}}
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
                                    </div>
                                    @if ($type == 'coverage')
                                        <p class="card-text payer">
                                            @if(optional($patientsDetails)->Payer != '')
                                                Payer: {{ $patientsDetails->Payer }}
                                            @endif
                                        </p>
                                    @else
                                        <p class="card-text payer">
                                            @if(optional($rd)->Payor_Source != '')
                                                Payer: {{ $rd->Payor_Source }}
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row pt-2">
                    <div class="col-md-6">
                        <label for="outcome">Outcome</label>
                        <select id="outcome" name="outcome" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            @foreach ($outcomeList as $item)
                                <option value="{{$item}}">{{$item}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="col-md-12 p-0 m-0 hide" id="commonfields">
                    <div id="attempt_container" class="hide">
                        <div class="row pt-2">
                            <div class="col-md-12">
                                <label for="note">Comments:</label>
                                <textarea name="note" id="note" class="form-control"></textarea>
                            </div>
                        </div>
                    </div>

                    <div id="extra-fields-container" class="col-md-12">
                        <div class="row pt-2 checkboxoption">
                            <?php $class=''; foreach($ccrCheckboxes as $key => $value) {
                                    if($key=='timely_documentation') {
                                        $class='pleft';
                                    } else {
                                        $class='';
                                    }
                                ?>
                                    <div class="row col-md-12">
                                        <div class="col-md-12 m-0 p-0">
                                            <input type="checkbox" id="<?php echo $key?>" name="ccrcheckbox[<?php echo $key?>]" value="1">
                                            <label class="<?php echo $class?>" for="<?php echo $key?>"><?php echo $value?></label>
                                        </div>
                                    </div>
                            <?php } ?>
                        </div>

                        <div class="row pt-2">
                            <label for="clinician_meeting">
                                    {{ LD::checkFeatureFlag('changes-to-ccr-form-for-pepr-process') ?
                                        'Is clinician ready to start without additional training?' :
                                         'Agree with clinician meeting client?' }}
                            </label>
                            <select id="clinician_meeting" name="clinician_meeting" class="form-control" autocomplete="off">
                                <option value="">-- Select Option --</option>
                                <option value="Yes">Yes</option>
                                <option value="No">No</option>
                            </select>
                        </div>

                        <div class="row pt-2 dependend-field-container hide" >
                            <label for="reason">Reason</label>
                            <select id="reason" name="reason" class="form-control" autocomplete="off">
                                <option value="">-- Select Reason --</option>
                                <option value="Requires further training">Requires further training</option>
                                <option value="Nurse uncomfortable with required skills">Nurse uncomfortable with required skills</option>
                                <option value="Nurse uncomfortable with location/home environment">Nurse uncomfortable with location/home environment</option>
                                <option value="Soft skills not a match">Soft skills not a match</option>
                                <option value="Do Not Use">Do Not Use</option>
                            </select>
                        </div>
                        <div class="row pt-2 hide" id="div_ccr_donotuse_reason" >
                            <label for="ccr_donotuse_reason">Reason for do not use</label>
                            <select id="ccr_donotuse_reason" name="ccr_donotuse_reason" class="form-control" autocomplete="off">
                                <option value="">-- Select Reason for do not use--</option>
                                <option value="History of NCNS/Callouts">History of NCNS/Callouts</option>
                                <option value="Not a good agency fit">Not a good agency fit</option>
                            </select>
                        </div>
                        <div class="row pt-2 dependend-field-container-type hide" id="div_ccr_training_type" >
                            <label for="ccr_training_type">Training Type</label>
                            <select id="ccr_training_type" name="ccr_training_type" class="form-control" autocomplete="off">
                                <option value="">-- Select Training Type--</option>
                                <option value="In Home Training">In Home Training</option>
                                <option value="In Office Training">In Office Training</option>
                                <option value="Trach/Vent Class">Trach/Vent Class</option>
                                <option value="TPN Class">TPN Class</option>
                            </select>
                        </div>
                        <div class="row pt-2 dependend-field-container hide" id="div_next_step">
                            <label for="next_step">Next Step</label>
                            <select id="next_step" name="next_step" class="form-control" autocomplete="off">
                                <option value="">-- Select Next Step --</option>
                                <option value="Find another client">Find another client</option>
                                <option value="Nurse decided not to work for AOC">Nurse decided not to work for AOC</option>
                            </select>
                        </div>

                        <div class="row pt-2 dependend-field-find_another_client hide" id="div_find_another_client">
                            <label for="find_another_client_text">Notes</label>
                            <input type="text" class="form-control" id="find_another_client_text" name="find_another_client_text" value="">
                        </div>

                        <div class="row pt-2 dependend-field-nurse_decided_not_to_work_for_aoc hide" id="div_nurse_decided_not_to_work_for_aoc">
                            <label for="reason_nurse_declined">Reason Nurse Declined</label>
                            <select id="reason_nurse_declined" name="reason_nurse_declined" class="form-control" autocomplete="off">
                                <option value="">-- Select One --</option>
                                @foreach(config('constant')['ReasonNurseDeclined'] as $k => $v)
                                <option value="{{$v}}">{{$v}}</option>
                                @endforeach
                            </select>
                        </div>


                        <div id="copy_clipboard_comment" class="hide">
                            <div class="row pt-2">
                                <div class="col-md-12 p-0">
                                    <label for="note">Comments:</label>
                                    <textarea name="copy_clipboard" id="copy_clipboard" class="form-control"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="row col-md-12 p-0 pt-2 hide" id="find_another_client_ccr_completed">
                            <div class="col-md-12 m-0 p-0">
                                <input type="checkbox" id="ccr_completed_for_proposed_client" name="ccr_completed_for_proposed_client" value="1">
                                <label class="" for="ccr_completed_for_proposed_client">CCR Completed For Proposed Client</label>
                            </div>
                        </div>
                    </div>

                    <div id="candidate_save" class="row pt-2">
                        <div class="col-md-7 text-right">
                            <button type="button" id="candidateSaveBtn" onclick="setCCRSchedule(this)" class="btn btn-primary">Save</button>
                            <a class="copy-form-btn" onclick="copyFormToClipboard();">Copy Text from Form</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<style>
    .ui-autocomplete {
        z-index: 999999;
    }
</style>

<script>
    function copyFormToClipboard(){
        var str = '';
        str += 'Client Name:'+' '+$.trim($("#clientname").html())+'\n';
        str += 'Clinician Name:'+' '+$.trim($("#candidatename").html())+'\n';
        $("#schedule_next_form_ccr input[type=text],#schedule_next_form_ccr input[type=checkbox],#schedule_next_form_ccr input[type=time],#schedule_next_form_ccr textarea,#schedule_next_form_ccr select ").each(function(ind,elm){
            //console.log($(elm).attr('type'));
            if($(elm).attr('id') == 'reason'){
                if($(elm).val() != '') {
                    str += 'Reason'+': '+$(elm).val() + '\n';
                }
            } else if($(elm).attr('type') == 'checkbox'){

                if($(elm).is(':checked')){
                    var checkid  = $(elm).attr("id");
                    str += $("label[for='"+checkid+"']").html()+ '\n';
                }
            } else{
                if($(elm).val() != '') {
                    str += $(elm).siblings('label').html()+': '+$(elm).val() + '\n';
                }
            }
        });
        navigator.clipboard.writeText(str).then(function() {
            console.log('Copied!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
        //console.log(str);
        //$("#copy_clipboard").val(str);
    }
    $(document).on("change","#outcome",function(){

        var outcome = $(this).find(":selected").val();
        const completedStatus = @json($completedStatus);

        let outcomeStatuses = @json($outcomeStatuses);

        if (outcome == '') {
            $("#commonfields").addClass("hide");
        } else {
            $("#commonfields").removeClass("hide");

            if (outcomeStatuses.includes(outcome)) {
                $("#attempt_container").removeClass("hide");
                $("#extra-fields-container").addClass("hide");
            } else {
                $("#attempt_container").addClass("hide");
                $("#extra-fields-container").removeClass("hide");
            }
        }

        $("#copy_clipboard_comment").addClass("hide");

        if (outcome === completedStatus) {
            $("#copy_clipboard_comment").removeClass("hide");
        }
    });

    $(document).on("change","#next_step",function(){
        $("#div_find_another_client").addClass("hide");
        $("#div_nurse_decided_not_to_work_for_aoc").addClass("hide");
        $("#find_another_client_ccr_completed").addClass("hide");

        var nextStep = $(this).find(":selected").val();

        if(nextStep=='Find another client') {
            $("#find_another_client_ccr_completed").removeClass("hide");
            $("#div_find_another_client").removeClass("hide");
        } else if(nextStep=='Nurse decided not to work for AOC') {
            $("#div_nurse_decided_not_to_work_for_aoc").removeClass("hide");
        }


    });
    $(document).on("change","#reason",function(){
        $("#div_ccr_donotuse_reason").addClass("hide");
        $("#div_ccr_training_type").addClass("hide");
        $("#div_next_step").removeClass("hide");
        $("#div_find_another_client").addClass("hide");
        $("#div_nurse_decided_not_to_work_for_aoc").addClass("hide");
        $('#ccr_donotuse_reason').prop('selectedIndex',0);
        var outcome = $(this).find(":selected").val();
        var clinicianMeetingno = $("#clinician_meeting").find(":selected").val();
        if(outcome=='Do Not Use') {
            $("#div_ccr_donotuse_reason").removeClass("hide");
            $("#div_next_step").addClass("hide");
        }
        if(outcome=='Requires further training' && clinicianMeetingno=='No'){
            $("#div_ccr_training_type").removeClass("hide");
        }
        if(outcome === 'Requires further training' && @json(LD::checkFeatureFlag('changes-to-ccr-form-for-pepr-process'))){
            $("#div_next_step").addClass("hide");
            $("#next_step").val('');
        }
    });
    $(document).on("change","#clinician_meeting",function(){

        var clinicianMeeting = $(this).find(":selected").val();
        var outcome = $("#reason").find(":selected").val();
        if(clinicianMeeting=='No') {
            $(".dependend-field-container").removeClass("hide");
            if(outcome=='Requires further training'){
                $(".dependend-field-container-type").removeClass("hide");
            }
        } else {
            $(".dependend-field-container").addClass("hide");
            $("#find_another_client_ccr_completed").addClass("hide");
            $(".dependend-field-container-type").addClass("hide");
        }

       // $("#next_step").trigger("change");
    });
    function setCCRSchedule(button){

        if($('#schedule_next_form_ccr').valid()){
            button.disabled = true;
            $.ajax({
                url: "{{ route('ccr.saveschedulenext') }}",
                type: "POST",
                data: $("#schedule_next_form_ccr").serialize(), //only input
                success: function(response) {

                    if (response) {
                        button.disabled = false;
                        if (response.success == 1) {
                            $("#successBody").html(response.message);
                            $("#scheduleNextFormDiv").hide();
                            $("#scheduleNextSuccessDiv").show();
                        } else {
                            $("#successBody").html(response.message);
                            $("#scheduleNextSuccessDiv").show();
                            $("#scheduleNextFormDiv").show();
                        // $("#ccr_sns_form").hide();
                        }

                    }
                },
                error: function() {
                    button.disabled = false;
                },
            });
        }
    }


    $(function()
            {
                $.validator.setDefaults({
                    ignore: [],
                });
                $('#schedule_next_form_ccr').validate(
                {   rules:
                    {
                        note:{ required:function(element){
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Candidate already established with client" || $("#schedule_next_form_ccr #outcome option:selected").val() == "Attempt 1" || $("#schedule_next_form_ccr #outcome option:selected").val() == "Attempt 2" || $("#schedule_next_form_ccr #outcome option:selected").val() == "Attempt 3";
                        }},
                        clinician_meeting:{ required:function(element){
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},
                        copy_clipboard:{ required:function(element){
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},
                        reason:{ required:function(element){
                            return $("#schedule_next_form_ccr #clinician_meeting  option:selected").val() == "No";
                        }},
                        next_step:{ required:function(element){
                                let changesToCcrForm = @json(LD::checkFeatureFlag('changes-to-ccr-form-for-pepr-process'));
                                let reason = $("#schedule_next_form_ccr #reason option:selected").val();

                                if (changesToCcrForm && reason === 'Requires further training') {
                                    return false;
                                }

                            return $("#schedule_next_form_ccr #clinician_meeting  option:selected").val() == "No" &&  $("#schedule_next_form_ccr #reason option:selected").val() != "Do Not Use";
                        }},
                        ccr_training_type:{ required:function(element){
                            return $("#schedule_next_form_ccr #clinician_meeting  option:selected").val() == "No" &&  $("#schedule_next_form_ccr #reason option:selected").val() == "Requires further training";
                        }},
                        ccr_donotuse_reason:{ required:function(element){
                            return $("#schedule_next_form_ccr #clinician_meeting  option:selected").val() == "No" &&  $("#schedule_next_form_ccr #reason option:selected").val() == "Do Not Use";
                        }},
                        find_another_client_text:{ required:function(element){
                            return $("#schedule_next_form_ccr #clinician_meeting  option:selected").val() == "No" &&  $("#schedule_next_form_ccr #next_step option:selected").val() == "Find another client";
                        }},
                        reason_nurse_declined:{ required:function(element){
                            return $("#schedule_next_form_ccr #clinician_meeting  option:selected").val() == "No" &&  $("#schedule_next_form_ccr #next_step option:selected").val() == "Nurse decided not to work for AOC";
                        }},




                        "ccrcheckbox[plan_care]":{required:function(element) {
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},

                        "ccrcheckbox[timely_documentation]":{required:function(element) {
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},

                        "ccrcheckbox[reviewed_understanding_care_coordination]":{required:function(element) {
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},

                        "ccrcheckbox[reviewed_understanding_professional]":{required:function(element) {
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},

                        "ccrcheckbox[clinician_knows_ccm_ccc]":{required:function(element) {
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},

                        "ccrcheckbox[clinician_knows_reach_hour]":{required:function(element) {
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},

                        "ccrcheckbox[kantime_login]":{required:function(element) {
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},

                        "ccrcheckbox[clinician_is_competent]":{required:function(element) {
                            return $("#schedule_next_form_ccr #outcome option:selected").val() == "Completed";
                        }},





                    },

                });

            }
        );


function closeForm() {
    $('#ccr_sns_form').modal('hide');
    $('#dataTable').DataTable().ajax.reload();
}

</script>

<style>
    .checkboxoption label.error {
        width: 100% !important;
        color: #f00;
        float: right;
        width: 10% !important;
    }
</style>
