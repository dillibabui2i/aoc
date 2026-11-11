<?php
use App\Helpers\LD;
use App\Helpers\H;
?>
@php
$interviewTypeList = DB::table('schedule_types')->get();
$interviewTypeData = [];
if (count($interviewTypeList)) {
$interviewTypeData = json_decode(json_encode($interviewTypeList), true);
}
if($bucket==''){
$outcomeList = [];
}else{
$outcomeList = config('constant')['orientationCoordinatorOutcomes'][$bucket];
}
    $approvedByList = [];
    $approvedUserList = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates');
    foreach($approvedUserList as $user) {
        $approvedByList[] = $user->name;
    }
@endphp
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>

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
</style>
<x-modal>
    <x-slot name="id"> oc-next-step</x-slot>
    <x-slot name="title">Orientation Coordinator Next Step \ {{$recruiterDetails->Candidate}}</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$recruiterDetails->id}}','tracker','Recruiter Tracker Notes Added')"><i
                    class="fa  fa-sticky-note-o"></i></a>
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
                <input type="hidden" id="ipea_id" name="ipea_id" value="{{ $ipea_id??''}}">
                <input type="hidden" id="patient_id" name="patient_id" value="{{ $recruiterDetails->aoc_patient_id}}">
                <input type="hidden" id="mode" name="mode" value="Edit">
                <input type="hidden" id="candidate_id" name="candidate_id"
                    value="{{ $recruiterDetails->aoc_candidate_id}}">
                <input type="hidden" id="provider_id" name="provider_id"
                    value="{{ $recruiterDetails->aoc_provider_id}}">
                <input type="hidden" id="facility_id" name="facility_id"
                    value="{{ $recruiterDetails->aoc_facility_id}}">
                <input type="hidden" id="scheduling" name="scheduling" value="0">
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
                                        <p class="card-text">{{$recruiterDetails->Branch_Location}}</p>
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
                                            <li>
                                                <span class="att-name">Approved By</span>
                                                <span class="att-value editable-select"
                                                    data-emptytext='Click to add Approved By' data-type='select'
                                                    data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='Approved_By'
                                                    data-value="{{ $recruiterDetails->Approved_By }}"
                                                    data-source='<?php echo htmlspecialchars(json_encode(config('constant')['approvedBy']),ENT_QUOTES, 'UTF-8');?>'
                                                    data-table='recruiterDetails'>{{nl2br($recruiterDetails->Approved_By)}}</span>
                                            </li>
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
                            <option value="prn">PRN</option>
                            <option value="ineligible">Ineligible for Hire/Rehire</option>
                            <option value="donotuse">Do Not Use in KanTime</option>
                        </select>
                    </div>
                    <div class="col-md-6 cancelled_reason_container" style="display:none;">
                        <label for="outcome">Next Step</label>
                        <select id="cancelled_reason" name="cancelled_reason" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            <option value="new_client">Find another client</option>
                            <option value="not_work">Nurse decided not to work for AOC</option>
                        </select>
                    </div>
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
                    <div class="col-md-6 ipea_lunch_container" style="display:none;">
                        <label for="ipea_lunch">Lunch Choice</label>
                        <input type="input" class="form-control" id="lunch_choice" name="lunch_choice" value="">
                    </div>
                </div>
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
                            <div class="col-md-6">
                                <label for="schedule">Schedule</label>
                                <input type="input" class="form-control" id="schedule" name="schedule" value="">
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
                <div id="commonfields" style="{{ (count($outcomeList)>0 ) ? 'display: none;' : '' }}">
                    <div id="candidate_note">
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
                                <div class="col-md-6">
                                    <label for="ipea_location_id">IPEA Location</label>
                                    <select name="ipea_location_id" class="form-control" id="ipea_location_id">
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
                            </div>
                            <div class="row pt-2">
                                <div class="col-md-6">
                                    <label for="datepicker">Date</label>
                                    <input type="input" class="form-control" id="datepicker" name="appointment_date" />
                                </div>
                            </div>
                            <div class="row pt-2">
                                <div class="col-sm-6">
                                    <label for="appointment_time">Time</label>
                                    <input type="input" class="form-control" id="appointment_time"
                                        name="appointment_time">
                                </div>
                            </div>
                            <div class="row pt-2">
                                <div class="col-sm-6">
                                    <div class="custom-control custom-switch custom-control-right showforipea"
                                        style="margin: 12px 0px;">
                                        <input type="hidden" name="is_rehire" value="0" />
                                        <input class="custom-control-input" type="checkbox" id="is_rehire"
                                            name="is_rehire" value="1" />
                                        <label class="custom-control-label" for="is_rehire">Is this rehire?</label>
                                    </div>
                                </div>
                            </div>
                            <div class="row pt-2 showforipea">
                                <div class="col-md-12">
                                    <label for="why_nurse_chosen">Why was nurse choosen for this client?</label>
                                    <textarea name="why_nurse_chosen" id="why_nurse_chosen" cols="80" rows="7"
                                        class="form-control"></textarea>
                                </div>
                            </div>
                        </fieldset>
                    </div>
                    <div id="candidate_save" class="row pt-2">
                        <div class="col-md-7 text-right">
                            <button type="button" onclick="setSchedule()" class="btn btn-primary">Save</button>
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

<script src="{{ mix('js/confetti.js') }}"></script>
<script type="text/javascript">
// Wait for the DOM to be ready
$(function() {
    $("#schedule_next_form").validate({
        rules: {
            "datepicker": {
                required: function(element) {
                    return $("#schedule_type").val() != '';
                }
            },
            "appointment_time": {
                required: function(element) {
                    return $("#schedule_type").val() != '';
                }
            }
        },
        errorPlacement: function(error, element) {}
    });
});
</script>

<script type="text/javascript">
function showConfetti() {
    startConfetti();
    setTimeout(function() {
        stopConfetti();
    }, 1000);
}
$(document).ready(function() {
    $("#change_client_name").autocomplete({
        source: "{{ route('getScheduleClient') }}",
        minLength: 2,
        select: function(event, ui) {
            console.log(ui);
            $('#patient_id').val(ui.item.id);
            $("#payer").val(ui.item.payer);
            $("#client_branch").val(ui.item.branch_name);
            $("#note").val('');
        }
    });
    var flag = false;

    $('#datepicker').datepicker();
    $('#appointment_time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '06:00:00',
        'maxTime': '21:00:00'
    });
    <?php if(LD::checkFeatureFlag('reason-nurse-declined')){ ?>
    $('#cancelled_reason').change(function() {
        if ($(this).val() == 'not_work' && $('#outcome').val() == 'Nurse Declined') {
            $('.reason_nurse_declined').show();
        } else {
            $('.reason_nurse_declined').hide();
        }
    });
    <?php } ?>
    $('#outcome').change(function() {
        if ($(this).val() != '') {
            $('#commonfields').show();
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
        if ($(this).val() == 'NC/NS' || $(this).val() == 'Pending Nurse' || $(this).val() ==
            'Pending Parent') {
            $('.attampted_to_contact_container').show();
            $('.cancelled_reason_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
            $('.ipea_lunch_container').hide();
        } else if ($(this).val() == 'Confirmed') {
            $('.ipea_lunch_container').show();
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').hide();
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
            $('.cancelled_reason_container').show();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
            $('#note').val('');
            $('.ipea_lunch_container').hide();
        } else if ($(this).val() == 'Inactivate on tracker') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            $('.inactivate_reason_container').show();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
            $('.ipea_lunch_container').hide();
        } else if ($(this).val() == 'Schedule Next Step') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').show();
            $("#scheduling").val('1');
            $('#change_client_container').show();
            $('.ipea_lunch_container').hide();
        } else if ($(this).val() == 'Nurse decided not to work for Angels of Care') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').hide();
            $("#scheduling").val('0');
            $('#change_client_container').hide();
            $('.ipea_lunch_container').hide();
        } else if ($(this).val() == 'Rescheduled') {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').show();
            $("#scheduling").val('1');
            $('#change_client_container').hide();
            $('.ipea_lunch_container').hide();
        } else {
            $('.attampted_to_contact_container').hide();
            $('.cancelled_reason_container').hide();
            $('.inactivate_reason_container').hide();
            $('#candidate_note').show();
            $("#note").val('');
            $('#schedule_appointment').show();
            $("#scheduling").val('1');
            $('#change_client_container').hide();
            $('.ipea_lunch_container').hide();
        }
    });

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

        if ($(this).val() == 'CCR' || $(this).val() == 'School Set Up') {
            $('.hideforccrschool').hide();
        } else {
            $('.hideforccrschool').show();
        }
    });
    $('#schedule_type').change();
});

function closeForm() {
    $('#oc-next-step').modal('hide');
}

function setSchedule() {
    //alert("In Progress");
    //return false;
    if ($("#schedule_next_form").valid() == false) {
        return false;
    }
    if ($('#patient_id').val() == 0 || $("#patient_id").val() == '') {
        $("#change_client_name").focus();
        $("#change_client_name").val("");
        return false;
    }
    if ($("#ipea_location_id").val() == '' && $("#schedule_type").val() == 'IPEA') {
        $("#ipea_location_id").focus();
        return false;
    }
    console.log($("#schedule_next_form").serialize());
    $.ajax({
        url: "{{ route('saveOrientationNext') }}",
        type: "POST",
        data: $("#schedule_next_form").serialize(), //only input
        success: function(response) {
            console.log(response);
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
    });
}
</script>

<script type="text/javascript">
$(document).ready(function() {
    $('#oc-next-step').on('shown.bs.modal', function() {
        $("#scheduleNextFormDiv").show();
        $("#scheduleNextSuccessDiv").hide();


    });
});
</script>
