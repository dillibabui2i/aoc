<?php
    use App\Helpers\H;
?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<link href="{{ mix('css/tribute.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<script src="{{ mix('js/tribute.js') }}"></script>
<x-modal>
    <x-slot name="id">schoolSetupsOutcomeFormModal</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">
        School Set Up
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <input class="form-control" type="hidden" name="school_setup_id" id="school_setup_id"
                    value="{{$schoolSetupDetail->id}}" />
        <div class="col-md-12 p-0 d-flex nurse-info-cnt">
            <div class="col-md-9 pl-3 tracker-details tabs-container">
                <div class="col-md-12 card-cnt p-0 d-flex mb-5">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Clinician</h5>
                            <div class="detail-section">
                                <h6>
                                    @if(!empty($clinicianDetails))
                                    {{$clinicianDetails->FirstName}} {{$clinicianDetails->LastName}}
                                    @elseif(!empty($candidateDetails))
                                    {{$candidateDetails->fullName}}
                                    @endif
                                </h6>
                                <p class="card-text">
                                    {{isset($clinicianDetails->ClinicianID) ? $clinicianDetails->ClinicianID : ""}}
                                </p>
                                <p class="card-text">
                                    @if(!empty($clinicianDetails))
                                    {{$clinicianDetails->State}}-{{$clinicianDetails->City}}
                                    @elseif(!empty($candidateDetails))
                                    {{$candidateDetails->state}}-{{$candidateDetails->city}}
                                    @endif
                                </p>

                                <p class="card-text">
                                    @if(!empty($clinicianDetails))
                                    {{$clinicianDetails->Phone}}
                                    @elseif(!empty($candidateDetails))
                                    {{$candidateDetails->homePhone}}
                                    @endif
                                </p>
                                <p class="card-text">
                                    @if(!empty($clinicianDetails))
                                    {{$clinicianDetails->Mobile}}
                                    @elseif(!empty($candidateDetails))
                                    {{$candidateDetails->mobile}}
                                    @endif
                                </p>
                                <p class="card-text">
                                    @if(!empty($clinicianDetails))
                                    {{$clinicianDetails->External_Email}}
                                    @elseif(!empty($candidateDetails))
                                    {{$candidateDetails->email}}
                                    @endif
                                </p>
                                <div class="tags-cnt">
                                    <span class='tag'>
                                        @if(!empty($clinicianDetails))
                                        {{ $clinicianDetails->DisciplineName }}
                                        @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if($patientsDetails)
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client</h5>
                            <div class="detail-section">
                                <div class="row">
                                    <div class="col-md-6">{{$patientsDetails->Patient_FirstName}}
                                        {{$patientsDetails->Patient_LastName}}</div>
                                    <div class="col-md-6">DOB: {{H::getFormatedDate($patientsDetails->Patient_DOB)}}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="card-text">{{$patientsDetails->PatientAccount}}</p>
                                    </div>
                                    <div class="col-md-6">Age: {{$patientsDetails->Patient_age}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="card-text">{{$patientsDetails->FacilityName}}</p>
                                    </div>
                                    <div class="col-md-6">Weight: {{$patientsDetails->CF_Weight}}</div>
                                </div>
                                <div class="row pt-3">
                                    <div class="col-md-6">
                                        <p class="card-text">{{$patientsDetails->Patient_Phone}}</p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            @if($patientsDetails->Patient_MobilPhone)
                                            {{$patientsDetails->Patient_MobilPhone}} (mobile)
                                            @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="row pt-3">
                                    <div class="col-md-6">
                                        <p class="card-text">{{$patientsDetails->Patient_Address1}}
                                            {{$patientsDetails->Patient_Address2}}</p>
                                        <p class="card-text">{{$patientsDetails->Patient_City}}
                                            {{$patientsDetails->Patient_State}} {{$patientsDetails->Patient_Zip}}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        @if($patientsDetails->Patient_Gender != '')
                                        <div class="tags-cnt">
                                            <span class='tag tag-grey'>{{$patientsDetails->Patient_Gender}}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#summary" role="tab">School Set Up</a>
                    </li>
                </ul>
                <div class="tab-content p-1 ">
                    <div class="tab-pane active" id="summary" role="tabpanel">
                        <div class="col-md-12 p-0 d-flex">
                            <div class="col-md-12 p-3 ipea-cnt">
                                <div class="form-group row pb-2">
                                    <div class="col-md-2">Clearance Packet</div>
                                    <div class="form-check form-check-inline col-md-9">
                                        <div class="my-radio-container">
                                            <label class="form-check-label" for="school_clearance_packet_none" >
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                name="school_clearance_packet" id="school_clearance_packet_none"
                                                {{isset($schoolSetupDetail->clearance_packet) &&
                                                $schoolSetupDetail->clearance_packet == "None" ?
                                                "checked='checked'" : ""}}
                                                value="None"/>None</label>
                                            <label for="school_clearance_packet_psifb" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                name="school_clearance_packet" id="school_clearance_packet_psifb"
                                                value="Pending school information from branch"
                                                {{isset($schoolSetupDetail->clearance_packet) &&
                                                $schoolSetupDetail->clearance_packet ==
                                                "Pending school information from branch" ?
                                                    "checked='checked'" : ""}}/>
                                                Pending school information from branch</label>
                                            <label for="school_clearance_packet_pssp" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                            name="school_clearance_packet" id="school_clearance_packet_pssp"
                                            value="Pending school sending packet"
                                            {{isset($schoolSetupDetail->clearance_packet) &&
                                                $schoolSetupDetail->clearance_packet ==
                                                "Pending school sending packet" ?
                                                    "checked='checked'" : ""}}/>
                                            Pending school sending packet</label>
                                            <label for="school_clearance_packet_pancas" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                name="school_clearance_packet" id="school_clearance_packet_pancas"
                                                value="Pending answer if nurse can attend school"
                                                {{isset($schoolSetupDetail->clearance_packet) &&
                                                    $schoolSetupDetail->clearance_packet ==
                                                    "Pending answer if nurse can attend school" ?
                                                        "checked='checked'" : ""}}/>
                                                Pending answer if nurse can attend school</label>
                                            <label for="school_clearance_packet_ns" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                            name="school_clearance_packet" id="school_clearance_packet_pn"
                                            value="Pending Nurse"
                                            {{isset($schoolSetupDetail->clearance_packet) &&
                                                $schoolSetupDetail->clearance_packet ==
                                                "Pending Nurse" ?
                                                    "checked='checked'" : ""}}/>
                                            Pending Nurse</label>
                                            <label for="school_clearance_packet_pc" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                    name="school_clearance_packet" id="school_clearance_packet_pc"
                                                value="Pending Caregiver"
                                                {{isset($schoolSetupDetail->clearance_packet) &&
                                                    $schoolSetupDetail->clearance_packet ==
                                                    "Pending Caregiver" ?
                                                        "checked='checked'" : ""}}/>
                                            Pending Caregiver</label>
                                            <label for="school_clearance_packet_pb" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                    name="school_clearance_packet" id="school_clearance_packet_pb"
                                                value="Pending Background"
                                                {{isset($schoolSetupDetail->clearance_packet) &&
                                                    $schoolSetupDetail->clearance_packet ==
                                                    "Pending Background" ?
                                                        "checked='checked'" : ""}}/>
                                            Pending Background</label>
                                            <label for="school_clearance_packet_psa" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                        name="school_clearance_packet" id="school_clearance_packet_psa"
                                                    value="Pending school approval"
                                                    {{isset($schoolSetupDetail->clearance_packet) &&
                                                        $schoolSetupDetail->clearance_packet ==
                                                        "Pending school approval" ?
                                                            "checked='checked'" : ""}}/>
                                                    Pending school approval</label>
                                            <label for="school_clearance_packet_pnrb" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                            name="school_clearance_packet" id="school_clearance_packet_pnrb"
                                            value="Pending nurse receiving badge"
                                            {{isset($schoolSetupDetail->clearance_packet) &&
                                                $schoolSetupDetail->clearance_packet ==
                                                "Pending nurse receiving badge" ?
                                                    "checked='checked'" : ""}}/>
                                            Pending nurse receiving badge</label>
                                            <label for="school_clearance_packet_ns" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                            name="school_clearance_packet" id="school_clearance_packet_completed"
                                                        value="Completed"
                                                        {{isset($schoolSetupDetail->clearance_packet) &&
                                                            $schoolSetupDetail->clearance_packet ==
                                                            "Completed" ?
                                                                "checked='checked'" : ""}}/>
                                                    Completed</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row pb-2" id="div_pending_nurse_reason"
                                style="{{$schoolSetupDetail->clearance_packet !=
                                    "Pending Nurse" ?
                                        "display: none;" : ""}}">
                                    <div class="col-md-2"></div>
                                    <div class="form-check-inline col-md-9">
                                        <select name="pending_nurse_reason[]" multiple id="pending_nurse_reason"
                                            class="form-control w-50 school-dd-on-change">
                                            <option value="Paperwork"
                                            {{(isset($schoolSetupDetail->clearance_packet_pending_nurse_reason) &&
                                            in_array('Paperwork',
                                            explode(", ",$schoolSetupDetail->clearance_packet_pending_nurse_reason))) ?
                                            'selected="selected"' : '' }}>
                                            Paperwork</option>
                                            <option value="Personal Identification"
                                            {{(isset($schoolSetupDetail->clearance_packet_pending_nurse_reason) &&
                                            in_array('Personal Identification',
                                            explode(", ",$schoolSetupDetail->clearance_packet_pending_nurse_reason))) ?
                                                'selected="selected"' : '' }}>Personal Identification</option>
                                            <option value="CPR"
                                            {{(isset($schoolSetupDetail->clearance_packet_pending_nurse_reason) &&
                                            in_array('CPR',
                                            explode(", ",$schoolSetupDetail->clearance_packet_pending_nurse_reason))) ?
                                                    'selected="selected"' : '' }}
                                                >CPR</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row pb-2">
                                    <div class="col-md-2">Finger Printing</div>
                                    <div class="form-check-inline col-md-9">
                                        <div class="my-radio-container">
                                        <label for="finger_printing_none" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                name="finger_printing" id="finger_printing_none"
                                                value="None"
                                                {{isset($schoolSetupDetail->finger_printing) &&
                                                    $schoolSetupDetail->finger_printing ==
                                                    "None" ?
                                                        "checked='checked'" : ""}}/>
                                            None</label>
                                        <label for="finger_printing_pa" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                name="finger_printing" id="finger_printing_pa"
                                                value="Pending appointment"
                                                {{isset($schoolSetupDetail->finger_printing) &&
                                                    $schoolSetupDetail->finger_printing ==
                                                    "Pending appointment" ?
                                                        "checked='checked'" : ""}}/>
                                                Pending appointment</label>
                                        <label for="finger_printing_fpnn" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                            name="finger_printing" id="finger_printing_fpnn"
                                            value="Fingerprinting not needed"
                                            {{isset($schoolSetupDetail->finger_printing) &&
                                                $schoolSetupDetail->finger_printing ==
                                                "Fingerprinting not needed" ?
                                                    "checked='checked'" : ""}}/>Fingerprinting not needed</label>
                                        <label for="finger_printing_completed" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                            name="finger_printing" id="finger_printing_completed"
                                                        value="Completed"
                                            {{isset($schoolSetupDetail->finger_printing) &&
                                                $schoolSetupDetail->finger_printing ==
                                                "Completed" ?
                                                    "checked='checked'" : ""}}/>
                                        Completed</label>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-md-2">School Set Up Status</div>
                                    <div class="form-check form-check-inline col-md-9">
                                        <div class="my-radio-container">
                                            <label for="school_setup_status_none" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                    name="school_setup_status" id="school_setup_status_none"
                                                    value="Open"
                                                    {{ ($schoolSetupDetail->school_setup_status ==
                                                        "Open" || $schoolSetupDetail->school_setup_status == null) ?
                                                            "checked='checked'" : ""}}/>
                                                None</label>
                                            <label for="school_setup_status" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                    name="school_setup_status" id="school_setup_status_psifb"
                                                    value="Started"  {{isset($schoolSetupDetail->school_setup_status) &&
                                                        $schoolSetupDetail->school_setup_status ==
                                                        "Started" ?
                                                            "checked='checked'" : ""}}/>
                                                    Started</label>
                                            <label for="school_setup_status_ns" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                name="school_setup_status" id="school_setup_status_completed"
                                                value="Completed"
                                                {{isset($schoolSetupDetail->school_setup_status) &&
                                                    $schoolSetupDetail->school_setup_status ==
                                                    "Completed" ?
                                                        "checked='checked'" : ""}}/>
                                                Completed</label>
                                            <label for="school_setup_status_ns" class="form-check-label">
                                            <input class="form-check-input radio-inline school-on-change" type="radio"
                                                name="school_setup_status" id="school_setup_status_cancelled"
                                                value="Cancelled"
                                                {{isset($schoolSetupDetail->school_setup_status) &&
                                                    $schoolSetupDetail->school_setup_status ==
                                                    "Cancelled" ?
                                                        "checked='checked'" : ""}}/>
                                                Cancelled</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="form-group row" id="div_school_setup_status_cancelled_reason"
                                style="{{$schoolSetupDetail->school_setup_status != "Cancelled" ?
                                        "display: none;" : ""}}">
                                    <div class="col-md-2"></div>
                                    <div class="form-check-inline col-md-9">
                                        <select name="school_setup_status_cancelled_reason[]" multiple
                                        id="school_setup_status_cancelled_reason"
                                            class="form-control w-50 school-dd-on-change">
                                            <optgroup label="Nurse Reasons">
                                            <option value="Declined fingerprinting"
                                            {{(isset($schoolSetupDetail->status_cancelled_reason) &&
                                                in_array('Declined fingerprinting',
                                                explode(", ",$schoolSetupDetail->status_cancelled_reason))) ?
                                                'selected="selected"' : '' }}>Declined fingerprinting</option>
                                            <option value="Declined paperwork"
                                            {{(isset($schoolSetupDetail->status_cancelled_reason) &&
                                                in_array('Declined paperwork',
                                                explode(", ",$schoolSetupDetail->status_cancelled_reason))) ?
                                                'selected="selected"' : '' }}>Declined paperwork</option>
                                            <option value="Wants to stay with current client"
                                            {{(isset($schoolSetupDetail->status_cancelled_reason) &&
                                                in_array('Wants to stay with current client',
                                                explode(", ",$schoolSetupDetail->status_cancelled_reason))) ?
                                                'selected="selected"' : '' }}>
                                                Wants to stay with current client
                                            </option>
                                            <option value="Clearance took too long"
                                            {{(isset($schoolSetupDetail->status_cancelled_reason) &&
                                                in_array('Clearance took too long',
                                                explode(", ",$schoolSetupDetail->status_cancelled_reason))) ?
                                                'selected="selected"' : '' }}>
                                                Clearance took too long
                                            </option>
                                            <option value="Will not drive client to school"
                                            {{(isset($schoolSetupDetail->status_cancelled_reason) &&
                                                in_array('Will not drive client to school',
                                                explode(", ",$schoolSetupDetail->status_cancelled_reason))) ?
                                                'selected="selected"' : '' }}>
                                                Will not drive client to school
                                            </option>
                                            <option value="Unable to contact"
                                            {{(isset($schoolSetupDetail->status_cancelled_reason) &&
                                                in_array('Unable to contact',
                                                explode(", ",$schoolSetupDetail->status_cancelled_reason))) ?
                                                'selected="selected"' : '' }}>
                                                Unable to contact
                                            </option>
                                            </optgroup>
                                            <optgroup label="Other Reasons">
                                            <option value="Nurse failed background check"
                                            {{(isset($schoolSetupDetail->status_cancelled_reason) &&
                                                in_array('Nurse failed background check',
                                                explode(", ",$schoolSetupDetail->status_cancelled_reason))) ?
                                                'selected="selected"' : '' }}>
                                                Nurse failed background check
                                            </option>
                                            <option value="Nurse failed fingerprinting"
                                            {{(isset($schoolSetupDetail->status_cancelled_reason) &&
                                                in_array('Nurse failed fingerprinting',
                                                explode(", ",$schoolSetupDetail->status_cancelled_reason))) ?
                                                'selected="selected"' : '' }}>
                                                Nurse failed fingerprinting
                                            </option>
                                            <option value="School does not allow PDN Service"
                                            {{(isset($schoolSetupDetail->status_cancelled_reason) &&
                                                in_array('School does not allow PDN Service',
                                                explode(", ",$schoolSetupDetail->status_cancelled_reason))) ?
                                                'selected="selected"' : '' }}>
                                                School does not allow PDN Service
                                            </option>
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row notes-cnt mt-3">
                                    <div class="col-md-12">
                                        <label for="" class="w-100"> School Set Up Notes</label>
                                        <div class="textarea-border">
                                            <span class="att-value editable-textarea"
                                            data-inputclass="textarea-size" data-placement="bottom"
                                                data-emptytext='Click to add notes' data-type='textarea'
                                                data-url='{{route("editable")}}' data-pk="{{ $schoolSetupDetail->id }}"
                                                data-name='SchoolSetUpNotes'
                                                data-value="{{ $schoolSetupDetail->notes }}"
                                                data-table='school_setup_requests'>{{$schoolSetupDetail->notes}}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex p-0">
                <x-activity-log>

                </x-activity-log>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
    $(document).ready(function(){
        reloadSchoolSetupHistory('{{ $schoolSetupDetail->id }}')
        let changeCancelledVal = false;
        $('#school_setup_status_cancelled_reason').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 4,
            onDropdownHide: function(event) {
                if (changeCancelledVal) {
                    saveSchoolOutcomeFormInfo($(event.target).prev("select"));
                }
                changeCancelledVal = false;
            },
            onChange: function(option, checked, select) {
                changeCancelledVal = true;
            }
        });
        let changePendingVal = false;
        $('#pending_nurse_reason').multiselect({
            includeSelectAllOption: true,
            numberDisplayed: 4,
            onDropdownHide: function(event) {
                if (changePendingVal) {
                    saveSchoolOutcomeFormInfo($(event.target).prev("select"));
                }
                changePendingVal = false;
            },
            onChange: function(option, checked, select) {
                changePendingVal = true;
            }
        });
    });
    $('#schoolSetupsOutcomeFormModal').on('hidden.bs.modal', function (e) {
        if($.fn.dataTable.isDataTable('#dataTable-open_school_setup_requests')){
            $('#dataTable-open_school_setup_requests').DataTable().ajax.reload();
        }
        if($.fn.dataTable.isDataTable('#dataTable-ccc_pending_school_setups_request')){
            $('#dataTable-ccc_pending_school_setups_request').DataTable().ajax.reload();
        }
    });
</script>
