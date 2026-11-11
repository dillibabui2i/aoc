<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\ConstantHelper;
    $new = 0;
    $current = 0;
    $recruiterDetails->aoc_provider_id ? $current = 1 : $new = 1;

    $users = H::getHelper("ConstantHelper")->getUsers();
    $user = [];
    $cnt =0 ;
    foreach($users as $u) {
        $user[$cnt]["key"] =$u['name'];
        $user[$cnt]["value"] = $u['name'];
        $user[$cnt]["email"] = $u['email'];
        $user[$cnt]["id"] = $u['id'];
        $cnt++;
    }

?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<link href="{{ mix('css/tribute.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<script src="{{ mix('js/tribute.js') }}"></script>

<script>
    var jsonFields = JSON.parse('<?php echo addslashes(json_encode($user))?>');
    var tribute = new Tribute({
        values: jsonFields,
        selectTemplate: function(item) {
            if (typeof item === "undefined") return null;
            return (
                '<a class="tag-container" data-id="'+item.original.id+'" href="#" target="_blank" title="' +
                item.original.email +
                '">@' +
                item.original.value +
                "</a>"
            );
        }
    });
    $('.contenteditable').on('shown', function(e, editable) {
        // editable.input.$input.html(editable.value);
        tribute.attach(document.getElementsByClassName("contenteditable content-editable"));
    });

</script>

<style>
.textarea-border {
    border: 1px solid #ddd;
    height: 135px;
    padding: 5px;
    overflow-y: auto;
}

.textarea-border.thin {
    height: 50px;
    min-width: 70%;
    margin: 10px 0px;
    float: right;
}

.textarea-border.thin textarea {
    min-height: 50px;
    max-height: 50px;
}

.pe-none {
    pointer-events: none;
}

.custom-switch .custom-control-label:before {
    pointer-events: none;
}

.w90 {
    max-width: 90% !important;
}

.notes-cnt label {
    line-height: 25px;
    margin-bottom: 0px;
}

.form-check-label {
    line-height: 20px !important;
}

.branch-note-mt {
    margin-top: 30%;
}

.client-cnt {
    margin-left: auto;
}

.w40 {
    width: 40%;
}

#ui-datepicker-div {
    position: absolute !important;
    left: auto !important;
    top: auto !important;
    box-shadow: 0px 0px 3px 0px;
}
 .ui-widget.ui-widget-content{
    z-index: 1051;
}
.top-sticky-container .ui-widget.ui-widget-content{
    z-index: 0;
}
.contenteditable{
    min-height: 100px;
    width: 250px;
}
</style>
<x-modal>
    <x-slot name="id">updateNurseIpeaInfoModal</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">Nurse Information \ {{$recruiterDetails->Candidate}}</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="col-md-12 p-0 d-flex nurse-info-cnt">
            <div class="col-md-12 p-0 tracker-details">
                <div class="col-md-8 d-flex p-0">
                    <div class="col-md-6 p-0 d-flex">
                        <div class="col-md-6 p-0">
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Recruiter: </dt>
                                <dd class="col-sm-8 text-left">{{ nl2br($recruiterDetails->Recruiter) }}</dd>
                            </dl>
                            <dl class="col-md-12 p-0 d-flex mt-2">
                                <dt class="col-sm-4 p-0 text-right">Recruiting Admin: </dt>
                                <dd class="col-sm-8 text-left">{{H::getRecruiterAdmin($recruiterDetails->user_id)}}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6 p-0">
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Orientation Coordinator: </dt>
                                <dd class="col-sm-8 text-lef">@if(isset($ipeaLocationDetails->id)) {{ H::getHelper('ConstantHelper')->getUserNameById(H::getOrientationCoordinator($ipeaLocationDetails->id))}} @endif</dd>
                            </dl>
                            <dl class="col-md-12 p-0 d-flex mt-2">
                                <dt class="col-sm-4 p-0 text-right">Assisted By: </dt>
                                <dd class="col-sm-8 text-left">
                                    <span class="att-value editable-autocomplete" data-emptytext='Click to assign Assisted By'
                                        data-type='text' data-url='{{route("editable")}}'
                                        data-pk="{{ $scheduleDetails->id }}" data-name='assisted_by' data-value=""
                                        data-inputclass="autocomplete-user" data-table='schedule' data-autocompletevalidate="validateassisted">
                                        {{H::getHelper('ConstantHelper')->getUserNameById($scheduleDetails->assisted_by)}}
                                    </span>
                                </dd>
                            </dl>
                        </div>
                    </div>
                    <div class="col-md-6 p-0">
                        <div class="col-md-6 p-0 d-flex">
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">IPEA:</dt>
                                <dd class='col-sm-8 text-left'>
                                    {{H::getFormatedDate($scheduleDetails->appointment_date)}}
                                    {{H::getFormatedTime(date('Y-m-d').' '.$scheduleDetails->appointment_time)}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Created At:</dt>
                                <dd class='col-sm-8 text-left'>
                                    {{H::getFormatedDateTime($scheduleDetails->created_at)}}
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6 p-0 d-flex mt-2">
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Location: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{isset($ipeaLocationDetails->branch) ? $ipeaLocationDetails->branch : ""}}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 card-cnt p-0 d-flex mb-5">
                    <div class="col-md-8 p-0 d-flex">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title bg-blue">Candidate</h5>
                                <div class="detail-section">
                                    <h6>
                                        {{$recruiterDetails->Candidate}}
                                        <div class="col-md-6 pull-right">
                                            <label>Is rehire?</label>
                                            <div class="custom-control custom-switch d-inline">
                                                <input readonly="true" data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='is_rehire'
                                                    data-table='recruiterDetails' class="custom-control-input pe-none"
                                                    type="checkbox" id="is_rehire" name="is_rehire"
                                                    {{ (isset($recruiterDetails->is_rehire) && $recruiterDetails->is_rehire != 0) ? 'checked' : '' }} />
                                                <label class="custom-control-label pe-none" for="is_rehire"></label>
                                            </div>
                                                <label>Referred by: </label>
                                                <div class="att-value" data-table='recruiterDetails'>
                                                    {{(!empty($recruiterDetails->referred_by) ?
                                                        H::getHelper('ConstantHelper')->getProviderNameByID($recruiterDetails->referred_by) : '')}}
                                                </div>
                                            <label>New Grad</label>
                                            <div class="custom-control custom-switch d-inline">
                                                <input readonly="true" data-url='{{route("editable")}}'
                                                    data-pk="{{ $recruiterDetails->id }}" data-name='new_grad'
                                                    data-table='recruiterDetails' class="custom-control-input pe-none"
                                                    type="checkbox" id="new_grad" name="new_grad"
                                                    {{ (isset($recruiterDetails->new_grad) && $recruiterDetails->new_grad != 0) ? 'checked' : '' }} />
                                                <label class="custom-control-label pe-none" for="new_grad"></label>
                                            </div>
                                                @if(isset($candidateDetails->id))
                                                    <div>
                                                        <button type="button" class="tag" onclick="getCandidateResume('<?php echo $candidateDetails->id?>')">View Resume</button>
                                                    </div>
                                                @endif
                                        </div>
                                    </h6>
                                    <p class="card-text">
                                        {{isset($providerDetails->ClinicianID) ? $providerDetails->ClinicianID : ""}}
                                    </p>
                                    <p class="card-text">
                                        {{isset($branchDetails->BranchName) ? $branchDetails->BranchName : ""}}
                                    </p>
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
                                            <p class="card-text">{{nl2br($recruiterDetails->Total_Hours)}}</p>
                                        </li>
                                        <li>
                                            <span class="att-name">Pay Rate</span>
                                            <p class="card-text">$
                                                {{number_format((float)$recruiterDetails->Agreed_Pay_Rate, 2, '.', '')}}
                                            </p>
                                        </li>
                                        <li>
                                            <span class="att-name">GP</span>
                                            <p class="card-text">{{$recruiterDetails->GP * 100}}%</p>
                                        </li>
                                        <li>
                                            <span class="att-name">Approved By</span>
                                            <p class="card-text">{{nl2br($recruiterDetails->Approved_By)}}</p>
                                        </li>
                                    </ul>
                                    @if($recruiterDetails->Schedule != '')
                                    <p class="card-text"><span class="font-italic">Schedule:</span>
                                        {{$recruiterDetails->Schedule}}</p>
                                    @endif
                                    @if($recruiterDetails->aoc_provider_id==0)
                                    <p class="card-text">{{$recruiterDetails->Clinician_ID}}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="card client-cnt">
                            <div class="card-body">
                                <h5 class="card-title bg-blue">Client</h5>
                                <div class="detail-section">
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
                                                echo trim($name);
                                                ?>
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
                                </div>
                                <p class="card-text payer">
                                    <span id="client-card-payer">Payer: {{$recruiterDetails->Payor_Source}}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 pr-0">
                        <div class="form-group row notes-cnt">
                            <div class="col-md-12 mb-4">
                                <label for="" class="w-100"> Recruiting Tracker Comments </label>
                                <div class="textarea-border">
                                    <span class="att-value editable-textarea" data-inputclass="textarea-size"
                                        data-emptytext='Click to add candidate notes' data-type='textarea' data-placement="bottom"
                                        data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                        data-name='Candidate_Notes'
                                        data-value="{{ $recruiterDetails->Candidate_Notes }}"
                                        data-table='recruiterDetails'>{{$recruiterDetails->Candidate_Notes}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row notes-cnt">
                            <div class="col-md-12">
                                <label for="" class="w-100"> Why was this nurse chosen for this client? </label>
                                <div class="textarea-border">
                                    <span class="att-value editable-textarea" data-inputclass="textarea-size" data-placement="bottom"
                                        data-emptytext='Click to add notes' data-type='textarea'
                                        data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                        data-name='why_nurse_chosen'
                                        data-value="{{ $recruiterDetails->why_nurse_chosen }}"
                                        data-table='recruiterDetails'>{{$recruiterDetails->why_nurse_chosen}}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input class="form-control" type="hidden" name="scheduleId" id="scheduleId"
                    value="{{$scheduleDetails->id}}" />
                <input class="form-control" type="hidden" name="trackerId" id="trackerId"
                    value="{{$recruiterDetails->id}}" />
                <h5>Branch Portion</h5>
                <div class="col-md-12 p-0 d-flex">
                    <div class="col-md-8 ipea-cnt">
                       <div class="form-group row">
                            <div class="col-md-12 row">
                                <label for="">Background Check</label>
                            </div>
                            <div class="form-check-inline col-md-12">
                                <label for="application-status" class="form-check-label">
                                    Application Status: {{ $firstAdvantage['applicationStatus'] }}
                                </label>
                                <label for="application-status" class="form-check-label">
                                    Order Status: {{ $firstAdvantage['orderStatus'] }}
                                </label>
                                <label for="application-status" class="form-check-label">
                                    Result Status: {{ $firstAdvantage['resultStatus'] }}
                                </label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <hr>
                        </div>                        
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for="">IPEA Confirmed by Location Day Before</label>
                                <label for="IPEA_Confirmed-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_Confirmed" id="IPEA_Confirmed-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_Confirmed) ||$scheduleDetails->IPEA_Confirmed == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_Confirmed" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_Confirmed" id="IPEA_Confirmed" value="Yes"
                                        {{isset($scheduleDetails->IPEA_Confirmed) && $scheduleDetails->IPEA_Confirmed == "Yes" ? "checked='checked'" : ""}} />
                                    Yes</label>
                                <label for="IPEA_Confirmed" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_Confirmed" id="IPEA_Confirmed_No" value="No"
                                        {{isset($scheduleDetails->IPEA_Confirmed) && $scheduleDetails->IPEA_Confirmed == "No" ? "checked='checked'" : ""}} />
                                    No</label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for="IPEA_lunch_preference"> Lunch Preference </label>
                                <input class="w-50 form-control on-change" type="text" name="IPEA_lunch_preference"
                                    id="IPEA_lunch_preference"
                                    value="{{isset($scheduleDetails->IPEA_lunch_preference) ? $scheduleDetails->IPEA_lunch_preference : ''}}" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for=""> Clinician Arrived </label>
                                <label for="IPEA_clinician_arrived-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_clinician_arrived" id="IPEA_clinician_arrived-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_clinician_arrived) || $scheduleDetails->IPEA_clinician_arrived == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_clinician_arrived" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_clinician_arrived" id="IPEA_clinician_arrived" value="Yes"
                                        {{isset($scheduleDetails->IPEA_clinician_arrived) && $scheduleDetails->IPEA_clinician_arrived == "Yes" ? "checked='checked'" : ""}} />
                                    Yes</label>
                                <label for="IPEA_clinician_arrived_nc_ns" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_clinician_arrived" id="IPEA_clinician_arrived_nc_ns" value="NC/NS"
                                        {{isset($scheduleDetails->IPEA_clinician_arrived) && $scheduleDetails->IPEA_clinician_arrived == "NC/NS" ? "checked='checked'" : ""}} />
                                    NC/NS</label>
                                <label for="IPEA_clinician_arrived_no" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_clinician_arrived" id="IPEA_clinician_arrived_no" value="Rescheduled"
                                        {{isset($scheduleDetails->IPEA_clinician_arrived) && $scheduleDetails->IPEA_clinician_arrived == "Rescheduled" ? "checked='checked'" : ""}} />
                                    Rescheduled</label>
                                <label for="IPEA_clinician_arrived_Cancelled" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_clinician_arrived" id="IPEA_clinician_arrived_Cancelled"
                                        value="Cancelled"
                                        {{isset($scheduleDetails->IPEA_clinician_arrived) && $scheduleDetails->IPEA_clinician_arrived == "Cancelled" ? "checked='checked'" : ""}} />
                                    Cancelled</label>

                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for=""> Drug Test </label>
                                <label for="IPEA_drug_test-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_drug_test" id="IPEA_drug_test-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_drug_test) ||$scheduleDetails->IPEA_drug_test == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_drug_test" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_drug_test" id="IPEA_drug_test" value="Passed"
                                        {{isset($scheduleDetails->IPEA_drug_test) && $scheduleDetails->IPEA_drug_test == "Passed" ? "checked='checked'" : ""}} />
                                    Passed</label>
                                <label for="IPEA_drug_test_no" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_drug_test" id="IPEA_drug_test_no" value="Failed"
                                        {{isset($scheduleDetails->IPEA_drug_test) && $scheduleDetails->IPEA_drug_test == "Failed" ? "checked='checked'" : ""}} />
                                    Failed</label>
                                <label for="IPEA_drug_test_refused" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_drug_test" id="IPEA_drug_test_refused" value="Refused"
                                        {{isset($scheduleDetails->IPEA_drug_test) && $scheduleDetails->IPEA_drug_test == "Refused" ? "checked='checked'" : ""}} />
                                    Refused</label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for=""> Background Check </label>
                                <label for="IPEA_background-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_background" id="IPEA_background-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_background) ||$scheduleDetails->IPEA_background == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_background" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_background" id="IPEA_background" value="Passed"
                                        {{isset($scheduleDetails->IPEA_background) && $scheduleDetails->IPEA_background == "Passed" ? "checked='checked'" : ""}} />
                                    Passed</label>
                                <label for="IPEA_background_no" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_background" id="IPEA_background_no" value="Failed"
                                        {{isset($scheduleDetails->IPEA_background) && $scheduleDetails->IPEA_background == "Failed" ? "checked='checked'" : ""}} />
                                    Failed</label>
                                <label for="IPEA_background_no" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_background" id="IPEA_background_refused" value="Refused"
                                        {{isset($scheduleDetails->IPEA_background) && $scheduleDetails->IPEA_background == "Refused" ? "checked='checked'" : ""}} />
                                    Refused</label>
                                <label for="IPEA_background_initiated" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_background" id="IPEA_background_initiated" value="Initiated"
                                        {{isset($scheduleDetails->IPEA_background) && $scheduleDetails->IPEA_background == "Initiated" ? "checked='checked'" : ""}} />
                                        Initiated
                                </label>
                            </div>
                        </div>
                        @if(LD::checkFeatureFlag('add-new-filed-in-home-cargiver-ipea-ticket-branch-portion-checklist'))
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for="">In-home Caregiver</label>
                                <label for="IPEA_in_home_cargiver-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_in_home_cargiver" id="IPEA_in_home_cargiver-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_in_home_cargiver) ||$scheduleDetails->IPEA_in_home_cargiver == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_in_home_cargiver" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_in_home_cargiver" id="IPEA_in_home_cargiver" value="Yes"
                                        {{isset($scheduleDetails->IPEA_in_home_cargiver) && $scheduleDetails->IPEA_in_home_cargiver == "yes" ? "checked='checked'" : ""}} />
                                    Yes</label>
                                <label for="IPEA_in_home_cargiver" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_in_home_cargiver" id="IPEA_in_home_cargiver_No" value="No"
                                        {{isset($scheduleDetails->IPEA_in_home_cargiver) && $scheduleDetails->IPEA_in_home_cargiver == "no" ? "checked='checked'" : ""}} />
                                    No</label>
                            </div>
                        </div>
                        @endif
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for=""> Clinician Interview Completed </label>
                                <label for="IPEA_interview-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_interview" id="IPEA_interview-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_interview) ||$scheduleDetails->IPEA_interview == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_interview" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_interview" id="IPEA_interview" value="Good Fit"
                                        {{isset($scheduleDetails->IPEA_interview) && $scheduleDetails->IPEA_interview == "Good Fit" ? "checked='checked'" : ""}} />
                                    Good Fit</label>
                                <label for="IPEA_interview_other" class="form-check-label">
                                    <input disabled='disabled' class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_interview" id="IPEA_interview_other" value="Suggest Other Clients"
                                        {{isset($scheduleDetails->IPEA_interview) && $scheduleDetails->IPEA_interview == "Suggest Other Clients" ? "checked='checked'" : ""}} />
                                    Suggest Other Clients</label>
                                <label for="IPEA_interview_not_qual" class="form-check-label">
                                    <input disabled='disabled' class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_interview" id="IPEA_interview_not_qual" value="Not Qualified"
                                        {{isset($scheduleDetails->IPEA_interview) && $scheduleDetails->IPEA_interview == "Not Qualified" ? "checked='checked'" : ""}} />
                                    Not Qualified</label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-check-inline d-block col-md-12 pull-right">
                                <div class="textarea-border thin">
                                    <span id="why_not_qualified" class="att-value editable-textarea" data-inputclass=""
                                        data-emptytext='Click to add reason' data-type='textarea'
                                        data-url='{{route("editable")}}' data-pk="{{ $scheduleDetails->id }}"
                                        data-name='why_not_qualified'
                                        data-value="{{ $scheduleDetails->why_not_qualified }}"
                                        data-table='schedule'>{{$scheduleDetails->why_not_qualified}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for=""> Clinical Skills Competency Check Completed </label>
                                <label for="IPEA_competency_check-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_competency_check" id="IPEA_competency_check-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_competency_check) ||$scheduleDetails->IPEA_competency_check == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_competency_check" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_competency_check" id="IPEA_competency_check" value="Passed"
                                        {{isset($scheduleDetails->IPEA_competency_check) && $scheduleDetails->IPEA_competency_check == "Passed" ? "checked='checked'" : ""}} />
                                    Passed</label>
                                <label for="IPEA_competency_check_failed" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_competency_check" id="IPEA_competency_check_failed" value="Failed"
                                        {{isset($scheduleDetails->IPEA_competency_check) && $scheduleDetails->IPEA_competency_check == "Failed" ? "checked='checked'" : ""}} />
                                    Failed</label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for=""> TPN Class </label>
                                <label for="IPEA_tpn_class-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_tpn_class" id="IPEA_tpn_class-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_tpn_class) ||$scheduleDetails->IPEA_tpn_class == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_tpn_class" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_tpn_class" id="IPEA_tpn_class" value="Scheduled"
                                        {{isset($scheduleDetails->IPEA_tpn_class) && $scheduleDetails->IPEA_tpn_class == "Scheduled" ? "checked='checked'" : ""}} />
                                    Scheduled</label>
                                <label for="IPEA_tpn_class_completed" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_tpn_class" id="IPEA_tpn_class_completed" value="Completed"
                                        {{isset($scheduleDetails->IPEA_tpn_class) && $scheduleDetails->IPEA_tpn_class == "Completed" ? "checked='checked'" : ""}} />
                                    Completed</label>
                                <label for="IPEA_tpn_class_na" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_tpn_class" id="IPEA_tpn_class_na" value="N/A"
                                        {{isset($scheduleDetails->IPEA_tpn_class) && $scheduleDetails->IPEA_tpn_class == "N/A" ? "checked='checked'" : ""}} />
                                    N/A</label>
                                <label for="IPEA_tpn_class_ns" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_tpn_class" id="IPEA_tpn_class_ns" value="Needs to be scheduled"
                                        {{isset($scheduleDetails->IPEA_tpn_class) && $scheduleDetails->IPEA_tpn_class == "Needs to be scheduled" ? "checked='checked'" : ""}} />
                                    Needs to be scheduled</label>
                            </div>
                        </div>

                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for=""> Trach / Vent Class </label>
                                <label for="IPEA_trachvent_class-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_trachvent_class" id="IPEA_trachvent_class-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_trachvent_class) ||$scheduleDetails->IPEA_trachvent_class == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_trachvent_class" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_trachvent_class" id="IPEA_trachvent_class" value="Scheduled"
                                        {{isset($scheduleDetails->IPEA_trachvent_class) && $scheduleDetails->IPEA_trachvent_class == "Scheduled" ? "checked='checked'" : ""}} />
                                    Scheduled</label>
                                <label for="IPEA_trachvent_class_completed" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_trachvent_class" id="IPEA_trachvent_class_completed"
                                        value="Completed"
                                        {{isset($scheduleDetails->IPEA_trachvent_class) && $scheduleDetails->IPEA_trachvent_class == "Completed" ? "checked='checked'" : ""}} />
                                    Completed</label>
                                <label for="IPEA_trachvent_class_na" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_trachvent_class" id="IPEA_trachvent_class_na" value="N/A"
                                        {{isset($scheduleDetails->IPEA_trachvent_class) && $scheduleDetails->IPEA_trachvent_class == "N/A" ? "checked='checked'" : ""}} />
                                    N/A</label>
                                <label for="IPEA_trachvent_class_ns" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_trachvent_class" id="IPEA_trachvent_class_ns" value="Needs to be scheduled"
                                        {{isset($scheduleDetails->IPEA_trachvent_class) && $scheduleDetails->IPEA_trachvent_class == "Needs to be scheduled" ? "checked='checked'" : ""}} />
                                    Needs to be scheduled</label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for=""> Onboarding Status </label>
                                <label for="IPEA_onboarding_status-none" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_onboarding_status" id="IPEA_onboarding_status-none" value="None"
                                        {{!isset($scheduleDetails->IPEA_onboarding_status) ||$scheduleDetails->IPEA_onboarding_status == NULL ? "checked='checked'" : ""}} />
                                    None</label>
                                <label for="IPEA_onboarding_status" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_onboarding_status" id="IPEA_onboarding_status" value="Ready To Work"
                                        {{isset($scheduleDetails->IPEA_onboarding_status) && $scheduleDetails->IPEA_onboarding_status == "Ready To Work" ? "checked='checked'" : ""}} />
                                    Ready To Work</label>
                                <label for="IPEA_onboarding_status_na" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_onboarding_status" id="IPEA_onboarding_status_na" value="Incomplete"
                                        {{isset($scheduleDetails->IPEA_onboarding_status) && $scheduleDetails->IPEA_onboarding_status == "Incomplete" ? "checked='checked'" : ""}} />
                                    Incomplete</label>
                                <label for="IPEA_onboarding_status_Ineligible" class="form-check-label">
                                    <input class="form-check-input radio-inline on-change" type="radio"
                                        name="IPEA_onboarding_status" id="IPEA_onboarding_status_Ineligible"
                                        value="Ineligible for Hire/Rehire"
                                        {{isset($scheduleDetails->IPEA_onboarding_status) && $scheduleDetails->IPEA_onboarding_status == "Ineligible for Hire/Rehire" ? "checked='checked'" : ""}} />
                                    Ineligible for Hire/Rehire</label>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="form-check-inline col-md-12">
                                <label for="IPEA_pending"> Pending </label>
                                <select name="IPEA_pending[]" multiple id="IPEA_pending"
                                    class="form-control w-50 on-change">
                                    @foreach($ipeaPendings as $pending)
                                    <option
                                        {{(isset($scheduleDetails->IPEA_pending) && in_array($pending,explode(",",$scheduleDetails->IPEA_pending))) ? 'selected="selected"' : '' }}
                                        {{isset($scheduleDetails->IPEA_pending) && $scheduleDetails->IPEA_pending == $pending ? "selected='selected'" : ""}}
                                        value="{{$pending}}">{{$pending}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 pr-0">
                        <div class="form-group row notes-cnt">
                            <div class="col-md-12">
                                <label for="" class="w-100"> Branch Notes </label>
                                <div class="textarea-border">
                                @if(LD::checkFeatureFlag('tagging-branch-notes'))
                                <span class="att-value contenteditable" data-placement="bottom"
                                    data-emptytext='Click to add branch notes' data-type='contenteditable'
                                    data-url='{{route("editable")}}' data-pk="{{ $scheduleDetails->id }}"
                                    data-name='branch_notes' data-value="{{ $scheduleDetails->branch_notes }}"
                                    data-table='schedule'>{!! $scheduleDetails->branch_notes !!}
                                </span>
                                @else
                                <span class="att-value editable-textarea" data-inputclass="textarea-size" data-placement="bottom"
                                    data-emptytext='Click to add branch notes' data-type='textarea'
                                    data-url='{{route("editable")}}' data-pk="{{ $scheduleDetails->id }}"
                                    data-name='branch_notes' data-value="{{ $scheduleDetails->branch_notes }}"
                                    data-table='schedule'>{{$scheduleDetails->branch_notes}}
                                </span>
                                @endif
                                </div>
                            </div>
                        </div>
                        <div class="form-group row notes-cnt mt-4">
                            <div class="col-md-12">
                                <label for="" class="w-100"> When is clinician available for next steps? </label>
                                <div class="textarea-border">
                                    @if(LD::checkFeatureFlag('tagging-clinician-next-steps-notes'))
                                    <span class="att-value contenteditable" data-placement="bottom"
                                        data-emptytext='Click to add notes' data-type='contenteditable'
                                        data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}" data-schedule="{{ $scheduleDetails->id }}"
                                        data-name='clinician_availability'
                                        data-value="{{ $recruiterDetails->clinician_availability }}"
                                        data-table='recruiterDetails'>{!! $recruiterDetails->clinician_availability !!}</span>
                                    @else
                                    <span class="att-value editable-textarea" data-inputclass="textarea-size" data-placement="bottom"
                                        data-emptytext='Click to add notes' data-type='textarea'
                                        data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                        data-name='clinician_availability'
                                        data-value="{{ $recruiterDetails->clinician_availability }}"
                                        data-table='recruiterDetails'>{{$recruiterDetails->clinician_availability}}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <h5 class="mt-4">Next Steps</h5>
                        <div class="col-md-12 next-schedule-form">
                            <div class="form-group row mb-2">
                                <div class="col-md-12 d-flex">
                                    <label for="interview_type"> Interview Type </label>
                                    <select disabled="true" class="form-control" name="interview_type"
                                        id="interview_type">
                                        <option value="">Interview Type</option>
                                        @foreach($scheduleTypes as $scheduleType)
                                        <option
                                            {{isset($nextScheduleDetails->type_id) && $nextScheduleDetails->type_id == $scheduleType['id'] ? "selected='selected'" : ""}}
                                            value="{{$scheduleType['id']}}">{{$scheduleType['type']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="form-group row mb-3">
                                <div class="col-md-12 d-flex">
                                    <label for=""> Date </label>
                                    <input disabled="true" type="date" class="form-control"
                                        value="{{isset($nextScheduleDetails->appointment_date) ? $nextScheduleDetails->appointment_date : ''}}" />
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-md-12 d-flex">
                                    <label for=""> Time </label>
                                    <input disabled="true" type="time" class="form-control"
                                        value="{{isset($nextScheduleDetails->appointment_time) ? $nextScheduleDetails->appointment_time : ''}}" />
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<x-modal>
    <x-slot name="id">ipeaReschedule</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">Reschedule</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="col-md-12">
            <form method="get" id="rescheduleForm">
                <div class="pt-2">
                    <div class="col-md-12">
                        <label for="ipea_location_id">IPEA Location</label>
                        <select name="ipea_location_id" class="form-control" id="ipea_location_id">
                            <option value="">-- Select One --</option>
                            @foreach($branches as $key => $branch)
                            <optgroup label="{{$key}}">
                                @foreach($branch as $b)
                                <option {{isset($ipeaLocationDetails->id) && $ipeaLocationDetails->id == $b['id'] ? "selected='selected'" : ""}} value="{{$b['id']}}">{{$b['branch']}}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pt-2">
                    <div class="col-md-12">
                        <label for="datepicker">Date</label>
                        <input type="input" class="form-control" id="IPEA_Date" name="IPEA_Date" />
                    </div>
                </div>
                <div class="pt-2">
                    <div class="col-sm-12">
                        <label for="appointment_time">Time</label>
                        <input type="input" class="form-control" id="IPEA_Time" name="IPEA_Time">
                    </div>
                </div>
                <div class="pt-2">
                    <div class="col-sm-12">
                        <button type="button" id="saveReschedule" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
$('#IPEA_pending').multiselect({
    includeSelectAllOption: true,
    numberDisplayed: 4
});
</script>

<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<script>
validateassisted = "";
$(document).ready(function() {
    $('#why_not_qualified').bind('DOMSubtreeModified', function(){
        if($('#why_not_qualified').html() != '' && $('#why_not_qualified').html() != 'Click to add reason'){
            $('#IPEA_interview_other').removeAttr('disabled');
            $('#IPEA_interview_not_qual').removeAttr('disabled');
        }
    });
    $('#IPEA_Date').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($('#IPEA_Date').parent());
    });
    $('#IPEA_Time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '06:00:00',
        'maxTime': '21:00:00'
    });
    $("#rescheduleForm").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "ipea_location_id": {
                required: true
            },
            "IPEA_Date": {
                required: true
            },
            "IPEA_Time": {
                required: true
            },
        }
    });

    $(document).on('focus', '.form-control.autocomplete-user', function(e) {
        $(".form-control.autocomplete-user").val("");
        $(".form-control.autocomplete-user").autocomplete({
            source: "{{ route('user.getListOfUsersForAutoComplete') }}",
            minLength: 2,
            search: function( event, ui ) { validateassisted = ""; },
            select: function(event, ui) {
                validateassisted = ui.item.id;
                $(".form-control.autocomplete-user").val(ui.item.id);
                $(".editable-submit").trigger("click");
                setTimeout(function() {
                    $("span[data-inputclass='autocomplete-user']").html(ui.item
                        .value);
                }, 1500);
                return false;
            }
        });
    });

});
</script>
