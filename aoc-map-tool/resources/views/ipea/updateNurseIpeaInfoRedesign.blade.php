<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\ConstantHelper;

/**
 * @see IpeaCalendarController::updateNurseIpeaInfo
 */

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
$totalIpeaChecklist = 0;
array_walk_recursive($ipeaChecklist, function($item) use (&$totalIpeaChecklist) {
    $totalIpeaChecklist++;
});
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
    .created-at{
        font-size: 11px;
        text-align: right;
        margin-top: 5px;
        float: right;
    }
    .tabs-container .tab-content{
        max-height: 550px;
        min-height: 300px;
        overflow-x: hidden;
    }
    .tag.skillset{
        background: #9747FF;
    }
    .tag.rehire{
        background: #3A99FF;
    }
    .tag.new-grand{
        background: #3AAE5C;
    }
    .tag.bilingual{
        background: #F24822;
    }
    .tag.in-home{
        background: #10761F;
    }
    .tag.virtual{
        background: #E4536B;
        margin-left: -13px;
    }
    .tag.cross-state{
        background: #c2660b;
    }
    #ipeaChecklistAccordion .btn{
        color: #fff;
    }
    #ipeaChecklistAccordion .checklist .card-body {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    #ipeaChecklistAccordion .checklist .card-body .form-control {
        width: 20px;
    }
    #ipeaChecklistAccordion .checklist {
        padding: 10px 20px;
        border-bottom: 1px solid #ddd;
    }
    #ipeaChecklistAccordion .checklist label {
        margin: 0;
    }
    #ipeaChecklistAccordion .card-header{
        padding: 0px;
    }
    ul.att-cnt{
        margin-top:0px;
    }
    ul.att-cnt li {
        width: 30%;
        float: left;
        margin-bottom: 10px;
    }
    .tracker-details .card{
        width: 47.5%;
    }

    .tracker-details h5.card-title.bg-blue{
        width: 100%;
        /* max-height: 40px; */
    }
    .w-23{
        width: 23% !important;
    }
    .w-45{
        width: 45% !important;
    }
    .h-80{
        height: 80%;
    }
    .datepicker-days {
        display:block;
    }
</style>
@if(LD::checkFeatureFlag("sticky-note-ipea-template-page"))
    <style>
        .created-at span{
            font-size: 14px;
            vertical-align: middle;
            font-weight: bold;
        }
    </style>
@else
    <style>
        #updateNurseIpeaInfoModal .modal-title{
            width: 100%;
        }
        .created-at span{
            font-size: 14px;
            vertical-align: middle;
        }
    </style>
@endif
<x-modal>
    <x-slot name="id">updateNurseIpeaInfoModal</x-slot>
    <x-slot name="size">w90</x-slot>
    @if(LD::checkFeatureFlag("sticky-note-ipea-template-page"))
        <x-slot name="title">
            IPEA Record \ {{$recruiterDetails->Candidate}}
        </x-slot>
        <x-slot name="extraHeader">
            <div class="col-md-6 text-left pull-left">
                <a data-bs-dismiss="modal" href="javascript:void(0);"
                   onclick="event.preventDefault();openNotes('{{$recruiterDetails->id}}',
                'tracker-activity','IPEA Notes Added','ipea-note')">
                    <i class="fa  fa-sticky-note-o"></i>
                </a>
            </div>
            <div class="col-md-6 text-right pr-2 pull-right">
                <div class="created-at">
                    Created at <span>{{H::getFormatedDateTime($scheduleDetails->created_at)}}</span>
                </div>
            </div>
        </x-slot>
    @else
        <x-slot name="title">
            IPEA Record \ {{$recruiterDetails->Candidate}}
            <div class="created-at">
                Created at <span>{{H::getFormatedDateTime($scheduleDetails->created_at)}}</span>
            </div>
        </x-slot>
    @endif
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="col-md-12 p-0 d-flex nurse-info-cnt">
            <div class="col-md-9 pl-3 tracker-details tabs-container">
                <div class="col-md-12 d-flex p-0">
                    <div class="card border-0 p-2 d-block">
                        <div class="col-md-12 p-0 d-flex">
                            <dl class="col-md-5 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-left">Date:</dt>
                                <dd class='col-sm-8 text-left'>
                                    {{H::getFormatedDate($scheduleDetails->appointment_date)}}
                                    {{H::getFormatedTime(date('Y-m-d').' '.$scheduleDetails->appointment_time)}}
                                </dd>
                            </dl>
                            <dl class="col-md-6 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-left">Coordinator: </dt>
                                <dd class="col-sm-8 text-left">@if(isset($ipeaLocationDetails->id)) {{ H::getHelper('ConstantHelper')->getUserNameById(H::getOrientationCoordinator($ipeaLocationDetails->id))}} @endif</dd>
                            </dl>
                        </div>
                        <div class="col-md-12 p-0 d-flex">
                            <dl class="col-md-5 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-left">Location: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{isset($ipeaLocationDetails->branch) ? $ipeaLocationDetails->branch : ""}}
                                </dd>
                            </dl>
                        </div>
                        @if(LD::checkFeatureFlag('show-virtual-ipea'))
                            @if($scheduleDetails->is_virtual !== 0)
                                <div class="col-md-12 p-0 d-flex">
                                    <div class="col-sm-2 p-0"></div>
                                    <div class="col-sm-10 p-0 text-left">
                                        <span class="tag virtual mt-1 mb-1">Virtual</span>
                                    </div>
                                </div>
                            @endif
                        @endif
                    </div>
                    <div class="card border-0 p-2 d-block ml-auto">
                        <div class="col-md-12 p-0 d-flex">
                            <dl class="col-md-5 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-left">Recruiter: </dt>
                                <dd class="col-sm-8 text-left">{{ nl2br($recruiterDetails->Recruiter) }}</dd>
                            </dl>
                            <dl class="col-md-7 p-0 d-flex">
                                <dt class="col-sm-3 p-0 text-right">Assisted By: </dt>
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
                        <div class="col-md-12 p-0">
                            <dl class="col-md-5 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-left">Admin: </dt>
                                <dd class="col-sm-8 text-left">{{H::getRecruiterAdmin($recruiterDetails->user_id)}}</dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 card-cnt p-0 d-flex mb-5">
                    <div class="col-md-12 p-0 d-flex">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title bg-blue">Candidate</h5>
                                <div class="d-flex flex-wrap h-80">
                                    <div class="detail-section d-flex w-100">
                                        <div class="col-md-5 pl-0">
                                            @if ($recruiterDetails->aoc_provider_id != 0)

                                                <a href="javascript:void(0);" onclick="getClinicianProfile('{{$recruiterDetails->aoc_provider_id}}')">
                                                    <h6>{{$recruiterDetails->Candidate}}</h6>
                                                </a>

                                            @else

                                                <a href="javascript:void(0);" onclick="openCandidateDetails('{{$recruiterDetails->aoc_candidate_id}}')">
                                                    <h6>{{$recruiterDetails->Candidate}}</h6>
                                                </a>

                                            @endif

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
                                        </div>

                                        <div class="col-md-7 pr-0">
                                            @if(isset($candidateDetails->id))
                                                <div>
                                                    <button type="button" class="tag"
                                                            onclick="getCandidateResume('<?php echo $candidateDetails->id?>')">
                                                        View Resume
                                                    </button>
                                                </div>
                                            @endif
                                            <ul class="d-flex att-cnt flex-wrap">
                                                @if($recruiterDetails->Schedule != '')
                                                    <li class="w-23">
                                                        <span class="att-name">Schedule</span>
                                                        <p class="card-text">{{$recruiterDetails->Schedule}}</p>
                                                    </li>
                                                @endif
                                                <li class="w-23">
                                                    <span class="att-name">Total Hours</span>
                                                    <p class="card-text text-right">{{nl2br($recruiterDetails->Total_Hours)}}</p>
                                                </li>
                                                <li class="w-23">
                                                    <span class="att-name">Pay Rate</span>
                                                    <p class="card-text">$
                                                        {{number_format((float)$recruiterDetails->Agreed_Pay_Rate, 2, '.', '')}}
                                                    </p>
                                                </li>
                                                <li class="w-23">
                                                    <span class="att-name">GP</span>
                                                    <p class="card-text">{{$recruiterDetails->GP * 100}}%</p>
                                                </li>
                                                <li class="w-45">
                                                    <span class="att-name">Approved By</span>
                                                    <p class="card-text">{{nl2br($recruiterDetails->Approved_By)}}</p>
                                                </li>
                                                <li class="w-45">
                                                    <span class="att-name">Referred By</span>
                                                    <p class="card-text">{{isset($recruiterDetails->referred_by) ? H::getHelper('ConstantHelper')->getProviderNameByID($recruiterDetails->referred_by) : ''}}</p>
                                                </li>
                                            </ul>
                                        </div>
                                    </div>
                                    <div class="detail-section d-flex w-100 flex-wrap">
                                        <div class="tags-cnt">
                                            @if($recruiterDetails->Skillset != '')
                                                <span class='tag skillset'>{{$recruiterDetails->Skillset}}</span>
                                            @endif
                                            @if($recruiterDetails->is_rehire != 0)
                                                <span class='tag rehire'>Rehire</span>
                                            @endif
                                            @if($recruiterDetails->new_grad != 0)
                                                <span class='tag new-grand'>New Grad</span>
                                            @endif
                                            @if($recruiterDetails->Bilingual == 'Yes')
                                                <span class='tag bilingual'>Bilingual</span>
                                            @endif
                                            @if($scheduleDetails->type_id == 1)
                                                <span class='tag bilingual'>Field</span>
                                            @endif
                                            @if(LD::checkFeatureFlag("show-in-home-on-ipea-calendar"))
                                                @if($recruiterDetails->intake_in_home != 0)
                                                    <span class='tag in-home'>In Home</span>
                                                @endif
                                            @endif
                                            @if(LD::checkFeatureFlag('cross-state-dynamic-email'))
                                                @if($crossState)
                                                    <span class='tag cross-state'>Cross-State</span>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card client-cnt">
                            <div class="card-body">
                                <h5 class="card-title bg-blue">Client</h5>
                                <div class="d-flex flex-wrap h-80">
                                    <div class="detail-section d-flex w-100">
                                        <div class="col-md-5 pl-0">
                                            <a href="javascript:void(0);" onclick="getClientDetails('{{$recruiterDetails->aoc_patient_id}}')"><p class="card-text">
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
                                                </p></a>
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
                                                    id="client-card-phone">{{isset($patientsDetails->Patient_Phone) ? $patientsDetails->Patient_Phone : ""}}</span>
                                            </p>
                                            <p class="card-text">
                                                <span
                                                    id="client-card-mobile">{{isset($patientsDetails->Patient_MobilPhone) ? $patientsDetails->Patient_MobilPhone : ""}}</span>
                                            </p>
                                        </div>
                                        <div class="col-md-7 pr-0">
                                            <dl class="col-md-12 p-0">
                                                <dt class="col-sm-12 p-0 text-left">Payer</dt>
                                                <dd class='col-sm-12 p-0 text-left'>{{$recruiterDetails->Payor_Source}}</dd>
                                            </dl>
                                            <div class="col-md-12 p-0 d-flex">
                                                <dl class="col-md-6 p-0">
                                                    <dt class="col-sm-12 p-0 text-left">Location</dt>
                                                    <dd class='col-sm-12 p-0 text-left'>{{isset($branchDetails->BranchName) ? $branchDetails->BranchName : "-"}}</dd>
                                                </dl>
                                                <dl class="col-md-6 p-0">
                                                    <dt class="col-sm-12 p-0 text-left">Skills</dt>
                                                    <dd class='col-sm-12 p-0 text-left'>{{isset($patientsDetails->CF_Skills) ? $patientsDetails->CF_Skills:''}}</dd>
                                                </dl>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="detail-section d-flex mt-auto w-100 flex-wrap">
                                        <div class="tags-cnt">
                                            @if(isset($patientsDetails->Patient_Gender) && $patientsDetails->Patient_Gender != '')
                                                <span class='tag bilingual'>{{$patientsDetails->Patient_Gender}}</span>
                                            @endif
                                            @if(isset($patientsDetails->CF_ServicesNeeded) && $patientsDetails->CF_ServicesNeeded != '')
                                                <span class='tag rehire'>{{$patientsDetails->CF_ServicesNeeded}}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input class="form-control" type="hidden" name="scheduleId" id="scheduleId"
                       value="{{$scheduleDetails->id}}" data-id="{{$scheduleDetails->id}}" />
                <input class="form-control" type="hidden" name="trackerId" id="trackerId"
                       value="{{$recruiterDetails->id}}" />

                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#summary" role="tab">Summary</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#checklist" role="tab">Checklist (<span class="selectedChecklistTotal"></span> / <span class="totalIpeaChecklist">{{$totalIpeaChecklist}}</span>)</a>
                    </li>
                </ul>
                <div class="tab-content p-1 ">
                    <div class="tab-pane active" id="summary" role="tabpanel">
                        <div class="col-md-12 p-0 d-flex">
                            <div class="col-md-7 p-3 ipea-cnt">
                                @if(auth()->user()->email === 'andrii.boichenko@angelsofcare.com')
                                    <span data-toggle="tooltip" style="vertical-align:text-bottom" title="Fix issue and retry submission">
                                        <a class="retry-background-check" data-id="{{ $scheduleDetails->id }}" href="#"><img src="{{ asset('resources/images/retry-icon.png') }}"></a>
                                    </span>
                                @endif


                                @if(LD::checkFeatureFlag("new-background-check") && $shouldShowNewBackground)
                                    <div class="form-group row">
                                        <style>
                                            .hr-l2 {
                                                border: none;
                                                height: 4px;
                                                background-color: black;
                                            }
                                            #render-background-check-error div {
                                                background: pink;
                                                width: 100%;
                                                max-width: 80%;
                                                border: 3px solid red;
                                            }
                                            .disable-retry img {
                                                filter: grayscale(100%);
                                            }

                                            .disable-retry:hover {
                                                cursor: not-allowed;
                                            }
                                        </style>
                                        <div class="form-check-inline col-md-12">
                                            <label for=""><b>Background Check</b></label>
                                            @canPermission(['first-advantage.retry'])
                                            @if ($lastAvailableError)
                                                <label for="background-retry" class="retry-background-check-label">
                                                    <span data-toggle="tooltip" style="vertical-align:text-bottom" title="Fix issue and retry submission"><a class="retry-background-check" data-id="{{ $scheduleDetails->id }}" href="#"><img src="{{ asset('resources/images/retry-icon.png') }}"></a></span>
                                                </label>
                                            @endif
                                            @endcanPermission
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="form-check-inline col-md-12">
                                            <label for="">Application Status</label>
                                            <label data-id="{{ $recruitingScheduleID }}" data-type="{{ $type }}" for="application-status" id="fa-application-status" class="form-check-label">{{ $firstAdvantage['applicationStatus'] }}</label>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="form-check-inline col-md-12">
                                            <label for="">Order Status:</label>
                                            <label for="order-status" id="fa-order-status" class="form-check-label">{{ $firstAdvantage['orderStatus'] }}</label>
                                        </div>
                                    </div>
                                    <div class="form-group row">
                                        <div class="form-check-inline col-md-12">
                                            <label for="">Result Status:</label>
                                            <label for="order-status" id="fa-result-status" class="form-check-label">{{ $firstAdvantage['resultStatus'] }}</label>
                                        </div>
                                    </div>

                                    @if ($firstAdvantage['updated_at'])
                                        <div class="form-group row">
                                            <div class="form-check-inline col-md-12">
                                                <label for="">Last Attempt:</label>
                                                <label for="last-attempt" id="last-attempt-id" class="form-check-label">{{ $firstAdvantage['updated_at'] }}</label>
                                            </div>
                                        </div>
                                    @endif
                                    <div id="render-background-check-error">
                                        @if ($lastAvailableError)
                                            <div>
                                                <ul>
                                                    <li>{{$lastAvailableError->message}}</li>
                                                </ul>
                                            </div>
                                        @endif
                                    </div>
                                    <hr class="hr-l2">
                                @endif
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
                                        <label for="tracker-history-container" class="form-check-label">
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
                                @if (!$shouldShowNewBackground)
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
                                @endif
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
                            <div class="col-md-5 pr-0">
                                <h5 class="mt-0">Next Steps</h5>
                                @if(LD::checkFeatureFlag('prn-changes-add-clinician'))
                                    @if($recruiterDetails->nurse_status == 2)
                                        @php
                                            $shiftType = explode(",", $recruiterDetails->shift_type);
                                        @endphp
                                        <div class="form-group row overlay">
                                            <div class="col-md-12">
                                                <label for="shift_type">Preferred Shift Type</label>
                                                <select
                                                    class="form-control"
                                                    name="shift_type[]"
                                                    id="shift_type"
                                                    multiple>
                                                    <option
                                                        value="Weekday - Days"
                                                        {{ in_array('Weekday - Days', $shiftType) ? 'selected' :'' }}
                                                    >Weekday - Days</option>
                                                    <option
                                                        value="Weekday - Nights"
                                                        {{ in_array('Weekday - Nights', $shiftType) ? 'selected' :'' }}
                                                    >Weekday - Nights</option>
                                                    <option
                                                        value="Weekend - Days"
                                                        {{ in_array('Weekend - Days', $shiftType) ? 'selected' :'' }}
                                                    >Weekend - Days</option>
                                                    <option
                                                        value="Weekend - Nights"
                                                        {{ in_array('Weekend - Nights', $shiftType) ? 'selected' :'' }}
                                                    >Weekend - Nights</option>
                                                </select>
                                            </div>
                                            <div class="col-md-12">
                                                <label for="available_days" class=""w-100">Days Available</label>
                                                <div
                                                    id="datepicker"
                                                    data-date="{!! implode(",",array_map([H::class,'getFormatedDate'],explode(",", $recruiterDetails->days_available))) !!}"
                                                    class="d-inline-block align-top"></div>
                                                <div
                                                    id="selected_date"
                                                    class="d-inline-block align-top"
                                                    style="overflow-y: auto;max-height: 180px;min-width: 135px;">
                                                    {!! implode("<br>",array_map([H::class,'getFormatedDate'],explode(",", $recruiterDetails->days_available))) !!}
                                                </div>
                                                <input type="hidden" id="available_days" name="available_days">
                                            </div>
                                            @if(LD::checkFeatureFlag('add-branch-notes-to-active-coverage-tracker'))
                                                <div class="col-md-12 notes-cnt">
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
                                            @endif
                                        </div>
                                    @else
                                        <div class="col-md-12 next-schedule-form">
                                            <div class="col-md-12 d-flex">
                                                <dl class="col-md-5 p-0 d-flex">
                                                    <dt class="col-sm-5 p-0 text-left">Next Step:</dt>
                                                    <dd class='col-sm-7 text-left'>
                                                        @php
                                                            if(isset($nextScheduleDetails->type_id) && $nextScheduleDetails->type_id != ''){
                                                                $key = array_search($nextScheduleDetails->type_id, array_column($scheduleTypes, 'id'));
                                                                echo $scheduleTypes[$key]['type'];
                                                            }else{
                                                                echo '-';
                                                            }
                                                        @endphp
                                                    </dd>
                                                </dl>
                                                <dl class="col-md-7 p-0 d-flex">
                                                    <dt class="col-sm-5 p-0 text-left">Date and Time:</dt>
                                                    <dd class='col-sm-7 text-left'>
                                                        {{isset($nextScheduleDetails->appointment_date) ? H::getFormatedDatetime($nextScheduleDetails->appointment_date.' '.$nextScheduleDetails->appointment_time)  : '-'}}
                                                    </dd>
                                                </dl>
                                            </div>
                                            <div class="col-md-12 d-flex">
                                                <dl class="col-md-5 p-0 d-flex">
                                                    <dt class="col-sm-5 p-0 text-left">Availability:</dt>
                                                    <dd class='col-sm-7 text-left'>
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
                                                    </dd>
                                                </dl>
                                            </div>
                                        </div>
                                        <div class="form-group row notes-cnt mt-3">
                                            <div class="col-md-12">
                                                <label for="" class="w-100"> What led to the selection of this nurse for this particular client? </label>
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
                                        <div class="form-group row notes-cnt mt-3">
                                            <div class="col-md-12">
                                                <label for="" class="w-100"> Clinician Specifications </label>
                                                <div class="textarea-border">
                                                    {{$recruiterDetails->Candidate_Notes}}
                                                    <!-- <span class="att-value editable-textarea" data-inputclass="textarea-size"
                                                        data-emptytext='Click to add candidate notes' data-type='textarea' data-placement="bottom"
                                                        data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                                        data-name='Candidate_Notes'
                                                        data-value="{{ $recruiterDetails->Candidate_Notes }}"
                                                        data-table='recruiterDetails'>{{$recruiterDetails->Candidate_Notes}}</span> -->
                                                </div>
                                            </div>
                                        </div>
                                        <div class="form-group row notes-cnt mt-3">
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
                                    @endif
                                @else
                                    <div class="col-md-12 next-schedule-form">
                                        <div class="col-md-12 d-flex">
                                            <dl class="col-md-5 p-0 d-flex">
                                                <dt class="col-sm-5 p-0 text-left">Next Step:</dt>
                                                <dd class='col-sm-7 text-left'>
                                                    @php
                                                        if(isset($nextScheduleDetails->type_id) && $nextScheduleDetails->type_id != ''){
                                                            $key = array_search($nextScheduleDetails->type_id, array_column($scheduleTypes, 'id'));
                                                            echo $scheduleTypes[$key]['type'];
                                                        }else{
                                                            echo '-';
                                                        }
                                                    @endphp
                                                </dd>
                                            </dl>
                                            <dl class="col-md-7 p-0 d-flex">
                                                <dt class="col-sm-5 p-0 text-left">Date and Time:</dt>
                                                <dd class='col-sm-7 text-left'>
                                                    {{isset($nextScheduleDetails->appointment_date) ? H::getFormatedDatetime($nextScheduleDetails->appointment_date.' '.$nextScheduleDetails->appointment_time)  : '-'}}
                                                </dd>
                                            </dl>
                                        </div>
                                        <div class="col-md-12 d-flex">
                                            <dl class="col-md-5 p-0 d-flex">
                                                <dt class="col-sm-5 p-0 text-left">Availability:</dt>
                                                <dd class='col-sm-7 text-left'>
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
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                    <div class="form-group row notes-cnt mt-3">
                                        <div class="col-md-12">
                                            <label for="" class="w-100"> What led to the selection of this nurse for this particular client? </label>
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
                                    <div class="form-group row notes-cnt mt-3">
                                        <div class="col-md-12">
                                            <label for="" class="w-100"> Clinician Specifications </label>
                                            <div class="textarea-border">
                                                {{$recruiterDetails->Candidate_Notes}}
                                                <!-- <span class="att-value editable-textarea" data-inputclass="textarea-size"
                                                    data-emptytext='Click to add candidate notes' data-type='textarea' data-placement="bottom"
                                                    data-url='{{route("editable")}}' data-pk="{{ $recruiterDetails->id }}"
                                                    data-name='Candidate_Notes'
                                                    data-value="{{ $recruiterDetails->Candidate_Notes }}"
                                                    data-table='recruiterDetails'>{{$recruiterDetails->Candidate_Notes}}</span> -->
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group row notes-cnt mt-3">
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
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="checklist" role="tabpanel">
                        @include('ipea.checklist',['ipeaChecklist'=>$ipeaChecklist,'currentChecklists'=>$currentChecklists])
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

        $('body').tooltip({selector: '[data-toggle="tooltip"]',}).off('focus');
        $(document).on("show.bs.modal", ".modal", function() {
            $('.ui-tooltip').remove();
        });
        $(document).on("mouseover", ".ui-tooltip", function() {
            $('.ui-tooltip').remove();
        });

        @if(LD::checkFeatureFlag('remove-jquery-old-function-and-use-new-function'))
        const observer = new MutationObserver(function(mutations) {
            let content = $('#why_not_qualified').html();
            if (content != '' && content !== 'Click to add reason') {
                $('#IPEA_interview_other').prop('disabled', false);
                $('#IPEA_interview_not_qual').prop('disabled', false);
            }
        });
        observer.observe(document.getElementById('why_not_qualified'), { childList: true, subtree: true });
        @else
        $('#why_not_qualified').bind('DOMSubtreeModified', function(){
            if($('#why_not_qualified').html() != '' && $('#why_not_qualified').html() != 'Click to add reason'){
                $('#IPEA_interview_other').removeAttr('disabled');
                $('#IPEA_interview_not_qual').removeAttr('disabled');
            }
        });
        @endif
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
                    $("span[data-inputclass='autocomplete-user']").data('autocompletevalidate',ui.item.id)
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
        reloadIpeaHistory($('#trackerId').val());
    });



    function reloadIpeaHistory(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('ipea.ipeaHistory')}}",
            type: 'get',
            data: {
                id: id,
                _token: _token
            },
            dataType: 'html',
            success: function(data) {
                $('.activity-log').html(data);
            }
        });
    }
</script>
@if(LD::checkFeatureFlag('prn-changes-add-clinician'))
    @if($recruiterDetails->nurse_status == 2)
        <style>
            .overlay-div {
                background-color: gray;
                opacity: .8;
                pointer-events: none;
            }
        </style>

        <script src="{{ mix('js/bootstrap-datepicker.min.js') }}"></script>
        <link href="{{ mix('css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
        <script>
            function saveNextStepData(id, field, value) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{route('ipea.saveNextStep')}}",
                    type: 'post',
                    data: {
                        id: id,
                        field: field,
                        value: value,
                        _token: _token
                    },
                    success: function(data) {
                        if (!data.status) {
                            alert(data.msg);
                        }
                        reloadIpeaHistory($('#trackerId').val());
                    }
                });
            }
            function enableDisabledNextSteps(flag) {
                if (flag) {
                    $('#updateNurseIpeaInfoModal #shift_type').multiselect('enable');
                    $('#updateNurseIpeaInfoModal .overlay').removeClass('overlay-div');
                } else {
                    $('#updateNurseIpeaInfoModal #shift_type').multiselect('disable');
                    $('#updateNurseIpeaInfoModal .overlay').addClass('overlay-div');
                    $("#updateNurseIpeaInfoModal #datepicker").datepicker('disabled', true);
                }
            }
            var date = new Date();
            $(document).ready(function() {

                $('#updateNurseIpeaInfoModal #shift_type').multiselect({
                    includeSelectAllOption: true,
                    onChange: function(option, checked, select) {
                        saveNextStepData(
                            '{{$recruiterDetails->id}}',
                            'shift_type',
                            $('#updateNurseIpeaInfoModal #shift_type').val()
                        );
                    },
                    onSelectAll: function(options) {
                        saveNextStepData(
                            '{{$recruiterDetails->id}}',
                            'shift_type',
                            $('#updateNurseIpeaInfoModal #shift_type').val()
                        );
                    },
                    onDeselectAll: function(options) {
                        saveNextStepData(
                            '{{$recruiterDetails->id}}',
                            'shift_type',
                            $('#updateNurseIpeaInfoModal #shift_type').val()
                        );
                    }
                });
                $('#updateNurseIpeaInfoModal #datepicker').datepicker({
                    multidate: true,
                    startDate: date,
                    todayHighlight: true
                });
                $('#updateNurseIpeaInfoModal #datepicker').on('changeDate', function() {
                    let selectedDates = $('#datepicker').datepicker('getFormattedDate');
                    $('#updateNurseIpeaInfoModal #available_days').val(selectedDates);
                    $('#updateNurseIpeaInfoModal #selected_date').html(selectedDates.split(',').join('</br>'));
                    saveNextStepData(
                        '{{$recruiterDetails->id}}',
                        'days_available',
                        selectedDates
                    );
                });
                $(document).on('change', '.on-change', function(e) {

                    if ($(this).attr("name")=='IPEA_onboarding_status' && $(this).val() == 'Ready To Work') {
                        enableDisabledNextSteps(false);
                    } else if ($(this).attr("name")=='IPEA_onboarding_status' && $(this).val() != 'Ready To Work'){
                        enableDisabledNextSteps(true);
                    }
                });

                    <?php if($scheduleDetails->IPEA_onboarding_status=='Ready To Work') { ?>
                enableDisabledNextSteps(false);
                <?php } else { ?>
                enableDisabledNextSteps(true);
                <?php } ?>
            });
        </script>
    @endif
@endif
