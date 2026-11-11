<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Enums\NTHLeadsAndInqInquiriesStatusEnum;
    $attemptCount = $leadsInquiriesDetail->attempt_count;
    $outcomeList = [];
    if($leadsInquiriesDetail->type == 'inq'){
        $outcomeList[] = 'Convert to Lead';
        $outcomeList[] = 'Resolved';
    }else{
        $outcomeList[] = 'Schedule Next Steps';
        $outcomeList[] = 'Suggest Other Clients';

        if(LD::checkFeatureFlag('add-resolve-outcome-for-nth-leads'))
        {
            $outcomeList[] = 'Resolved';
        };
    }
    if($attemptCount == 0){
        $outcomeList[] = "Attempt 1";
    }
    if($attemptCount == 1){
        $outcomeList[] = "Attempt 2";
    }
    if($attemptCount == 2){
        $outcomeList[] = "Attempt 3";
    }
    sort($outcomeList);

    $allInquiriesFilter = [
        [
        'label'=>'Recruiter',
        'name'=>'nthlead_recruiter',
        'type'=>'multiselect',
        'source'=>H::getHelper("ConstantHelper")->getRecruiters('id')
        ],
        [
                'label' => 'Status',
                'name' => 'nthlead_status',
                'type' => 'multiselect',
                'source' => [
                    ["value"=>"New","text"=>"New"],
                    ["value"=>"In Progress","text"=>"In Progress"],
                    ["value"=>"Pending","text"=>"Pending"],
                    ["value"=>"Resolved","text"=>"Resolved"],
                ]
        ]
        ,
        [
                'label' => 'Outcome',
                'name' => 'nthlead_outcome',
                'type' => 'multiselect',
                'source' => [
                    ["value"=>"Convert to Lead","text"=>"Convert to Lead"],
                    ["value"=>"Attempt 1","text"=>"Attempt 1"],
                    ["value"=>"Attempt 2","text"=>"Attempt 2"],
                    ["value"=>"Attempt 3","text"=>"Attempt 3"],
                    ["value"=>"Schedule Next Steps","text"=>"Schedule Next Steps"],
                    ["value"=>"Suggest Other Clients","text"=>"Suggest Other Clients"],
                ]
        ],
    ];

if (LD::checkFeatureFlag('add-resolve-outcome-for-nth-leads')) {
    $allInquiriesFilter[] = [
        'label' => 'Resolution',
        'name' => 'nthlead_resolution',
        'type' => 'multiselect',
        'source' => H::getHelper("ConstantHelper")->getLeadResolutions()
    ];
}

    $colMdStyle = 'col-md-6';
    if($leadsInquiriesDetail->Status == 'Resolved') {
        $colMdStyle = 'col-md-12';
    }

    if (isset($relatedLeads['newestLead']) && LD::checkFeatureFlag('nth-superseded-lead-notifications')) {
        $alertType = 'warning';
        $alertMessage = '⚠️This lead was superseded by Lead-' .
            $relatedLeads['newestLead']->id .
            ' (+ ' . $relatedLeads['count'] - 1 .
            ' others).';

        if($relatedLeads['count'] === 1){
            $alertMessage = '⚠️A newer lead was submitted for this clinician (Lead-' .
                $relatedLeads['newestLead']->id . ', Recruiter ' .
                $relatedLeads['newestLead']->name . ') on ' .
                getFormatedDateTime($relatedLeads['newestLead']->created_at);
        }
        if ($relatedLeads['newestLead']->created_at < $leadsInquiriesDetail->created_at) {
            $alertType = 'primary';
            $alertMessage = 'ℹ️ This clinician has other leads in New or Pending status - Lead-' .
                $relatedLeads['newestLead']->id .
                (($relatedLeads['count'] - 1) === 0 ?  '' : ' (+ ' . $relatedLeads['count'] - 1 . ' others)') . '.';
        }
    };

?>
@if(LD::checkFeatureFlag('lead-bulk-resolve-on-schedule') && $bucket === 'NTH LEADS AND INQUIRIES')
    @include('nthalerts.schedule-next-steps')
@endif

<div id="sns-loading-overlay" class="loading-overlay" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading Schedule Next Steps form...</span>
    </div>
    <p class="mt-2">Opening form, please wait...</p>
</div>
<style>
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 1050;
    }
    .spinner-border {
        width: 3rem;
        height: 3rem;
    }

    .superseded-alert {
        margin-top: -2rem;
    }
</style>
<x-modal>
    <x-slot name="id">nthLeadsInquiriesDetailModal</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">
        NTH {{ ($leadsInquiriesDetail->type == 'inq')?'Inquiry':'Lead' }} \ {{$leadsInquiriesDetail->ticket_id}}
    </x-slot>
    <x-slot name="extraHeader">
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="col-md-12 p-0 d-flex nurse-info-cnt">
            <div class="col-md-9 pl-3 tracker-details tabs-container">
                @if(isset($relatedLeads['newestLead']) && $leadsInquiriesDetail->Status != NTHLeadsAndInqInquiriesStatusEnum::RESOLVED->value)
                    <div id="errorBody" class="alert alert-{{$alertType}} superseded-alert" style="color: black;">
                        <b>{{$alertMessage}}</b>
                        <br>
                        <div style="margin-left: 22px;">
                            @if($alertType == 'warning')
                                <a
                                    href="#"
                                    class="view-new-lead"
                                    onclick="getNthLeadsInquiriesDetail('{{ $relatedLeads['newestLead']->id }}')"
                                >View New Lead</a> |
                            @endif
                            <a href="#nthdataTable" class="{{ $alertType == 'warning' ? 'view-all-leads' : 'view-other-leads' }}">
                                {{ $alertType == 'warning' ? 'View All Leads' : 'View other leads' }}
                            </a>
                        </div>
                    </div>
                @endif
                <div class="col-md-12 card-cnt p-0 d-flex mb-5">
                    <div class="card  mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue text-center">Clinician Details</h5>
                            <div class="row">
                                <div class="col-md-8">
                                    <h5 class="text-primary">{{$clinicianData->fullName}}</h5>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <p><strong>Clinician ID:</strong> {{$clinicianData->ClinicianID}}</p>
                                    <p><strong>Discipline:</strong> {{$clinicianData->DisciplineName}}</p>
                                    <p><strong>Status:</strong> {{$clinicianData->EmploymentStatus}}</p>
                                    <p><strong>Location:</strong>{{$clinicianData->FacilityName}}</p>
                                </div>
                                <div class="col-md-4 border-left border-right">
                                    <p><strong>Address:</strong><br>
                                        {{$clinicianData->Address1}} {{$clinicianData->Address2}}<br>
                                        {{$clinicianData->City}}<br>
                                        {{$clinicianData->State}} {{$clinicianData->Zip}}</p>
                                </div>
                                <div class="col-md-4">
                                    <p><strong>Mobile:</strong> {{$clinicianData->Mobile}}</p>
                                    <p><strong>Phone:</strong> {{$clinicianData->Phone}}</p>
                                    <p><strong>Email:</strong> {{$clinicianData->External_Email}}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue text-center">{{ ($leadsInquiriesDetail->type == 'inq')?'Inquiry Details':'Lead Details' }}</h5>
                            <div class="row mt-3">
                                <div class="col-md-6">
                                    @if($leadsInquiriesDetail->type == 'lead')
                                    <p><strong>Client ID:</strong> {{$leadsInquiriesDetail->PatientAccount}}</p>
                                    @endif
                                    <p><strong>{{ ($leadsInquiriesDetail->type == 'inq')?'Inquiry #':'Lead #' }}:</strong> {{$leadsInquiriesDetail->ticket_id}}</p>
                                    <p><strong>Created:</strong> {{H::getFormatedDate($leadsInquiriesDetail->created_at)}}</p>
                                    <p><strong>Contact Method:</strong> {{ $leadsInquiriesDetail->preferred_contact_method }}</p>
                                    <p><strong>Recruiter:</strong> {{ $leadsInquiriesDetail->Recruiter }}</p>
                                    @if($leadsInquiriesDetail->type == 'inq')
                                        <p><strong>Client:</strong> {{ $leadsInquiriesDetail->patient_name }}</p>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> {{ $leadsInquiriesDetail->Status }}</p>
                                    <p><strong>Modified:</strong> {{ H::getFormatedDate($leadsInquiriesDetail->updated_at) }}</p>
                                    <p><strong>Contact Time:</strong> {{ $leadsInquiriesDetail->best_time_to_contact }}</p>
                                    <p><strong>Outcome:</strong> {{ $leadsInquiriesDetail->outcome }}</p>
                                    @if(
                                            (($leadsInquiriesDetail->type === 'lead') && LD::checkFeatureFlag('add-resolve-outcome-for-nth-leads'))
                                            || ($leadsInquiriesDetail->type === 'inq')
                                        )
                                        <p><strong>Resolution:</strong> {{ $leadsInquiriesDetail->resolution}}</p>
                                        <p><strong>Resolution Detail:</strong> {{ $leadsInquiriesDetail->resolution_detail }}</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 card-cnt p-0 d-flex mb-5">
                    <div class="card lead-full">
                        <form id="leadInquiryOutcomeForm">
                            <input type="hidden" id="selectedId" name="selectedId" value="{{ $leadsInquiriesDetail->id }}"/>
                            <input type="hidden" id="type" name="type" value="{{$leadsInquiriesDetail->type}}"/>
                            <input type="hidden" id="patient_id" name="patient_id" value="0">
                            <input type="hidden" id="aoc_nthaccounts_id" name="aoc_nthaccounts_id" value="0">
                        <div class="card-body main-detail-card">
                            <h5 class="card-title bg-blue">{{ ($leadsInquiriesDetail->type == 'inq')?'Inquiry and Outcome Details':'Lead and Outcome Details' }}</h5>
                            <div class="row">
                                <div class="{{ $colMdStyle }}">
                                    @if($leadsInquiriesDetail->type == 'lead')
                                        <div class="card ml-2 bg-ntp">
                                            <div class="card-header inquiry-outcome">Lead Content</div>
                                            <div class="card-body inquiry-outcome-body bg-light">
                                                <div class="card mb-2 ml-3 mt-2">
                                                    <div class="card-header inquiry-outcome-inner">Client Details</div>
                                                    <div class="card-body inquiry-outcome-inner-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Client:</strong> <a href="#">{{$clientDetails->Patient_FirstName ?? ''}} {{$clientDetails->Patient_LastName ?? ''}}</a></p>
                                                                <p><strong>ID:</strong> {{$clientDetails->PatientAccount ?? ''}}</p>
                                                                <p><strong>Location:</strong> {{$clientDetails->FacilityName ?? ''}}</p>
                                                                <p><strong>Address:</strong> {{$clientDetails->Patient_Address1 ?? ''}} {{$clientDetails->Patient_Address2 ?? ''}}, {{$clientDetails->Patient_City ?? ''}}, {{$clientDetails->Patient_State ?? ''}} {{$clientDetails->Patient_Zip ?? ''}}</p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>DOB:</strong> {{H::getFormatedDate($clientDetails->Patient_DOB ?? '')}}</p>
                                                                <p><strong>Age:</strong> {{$clientDetails->Patient_age ?? ''}}</p>
                                                                <p><strong>Weight:</strong> {{$clientDetails->CF_Weight ?? ''}}</p>
                                                                <p><strong>Gender:</strong> {{$clientDetails->Patient_Gender ?? ''}}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card mb-2 ml-3 mt-2">
                                                    <div class="card-header inquiry-outcome-inner d-flex justify-content-between">
                                                        <span>NTH Ticket Details</span>
                                                        <span>Status: {{$needToHireChecklistDetails->Status ?? ''}}</span>
                                                    </div>
                                                    <div class="card-body inquiry-outcome-inner-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Schedule:</strong>
                                                                    @php $schedules = explode(',',$needToHireChecklistDetails->displaySchedule ?? ''); @endphp
                                                                    @foreach($schedules as $schedule)
                                                                    {!! $schedule !!}<br>
                                                                    @endforeach
                                                                </p>
                                                                <p><strong>Services Needed:</strong> {{$needToHireChecklistDetails->ServicesNeeded ?? ''}}</p>
                                                                <p><strong>Discipline:</strong> {{$needToHireChecklistDetails->clinicianDiscipline ?? ''}}</p>
                                                                <p><strong>Skills:</strong> {{$needToHireChecklistDetails->skills ?? ''}}</p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Preferred Gender:</strong> {{$clientDetails->genderPref ?? ''}}</p>
                                                                <p><strong>Language:</strong> {{ $clientDetails->CF_NurseLanguage ?? '' }}</p>
                                                                <p><strong>Date Added to NTH:</strong> {{H::getFormatedDate($needToHireChecklistDetails->created_at ?? '')}}</p>
                                                                <p><strong>Days on NTH:</strong> {{$needToHireChecklistDetails->daysInNTH ?? ''}}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card mb-2 ml-3 mt-2">
                                                    <div class="card-header inquiry-outcome-inner">Payer Details</div>
                                                    <div class="card-body inquiry-outcome-inner-body">
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <p><strong>Payer:</strong> {{$clientDetails->Payer ?? ''}}</p>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <p><strong>Pay Range:</strong></p>
                                                                <p><strong>RN:</strong> {{$needToHireChecklistDetails->pay_range_rn ?? ''}}</p>
                                                                <p><strong>LVN/LPN:</strong> {{$needToHireChecklistDetails->pay_range_lvn ?? ''}}</p>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="card">
                                            <div class="card-header inquiry-outcome">Message Content</div>
                                            <div class="card-body bg-light" style="height: 200px;">{{ $leadsInquiriesDetail->message }}</div>
                                        </div>
                                    @endif
                                </div>
                                @if($leadsInquiriesDetail->Status != 'Resolved')
                                <div class="col-md-6">
                                    <div class="card bg-ntp">
                                        <div class="card-header inquiry-outcome">Outcome Form</div>
                                        <div class="card-body inquiry-outcome-body">
                                            <label><strong>Select Outcome</strong></label>
                                            <select name="outcome" id="outcome" class="form-control mb-3">
                                                <option value=''>-- Select One --</option>
                                                @foreach ($outcomeList as $item)
                                                    <option data-id="{{ strtolower(str_replace(' ','',$item)) }}" value="{{  $item }}">{{ $item }}</option>
                                                @endforeach
                                            </select>
                                            <div id="nth_client_div" style="display: none;">
                                            <label><strong>Client</strong></label>
                                            <input type="text" id="nth_client" name="nth_client" class="form-control mb-3">
                                            </div>
                                            <label><strong>Notes</strong></label>
                                            <textarea name="outcome_comment" id="outcome_comment" class="form-control mb-3" rows="4"></textarea>
                                                @if($leadsInquiriesDetail->type == 'lead')
                                                <div id="resolution_div" style="display: none;" class="mb-3">
                                                    <label><strong>Resolution <span class="text-danger">*</span></strong></label>
                                                    <select name="resolution" id="resolution" class="form-control mb-2">
                                                        <option value="">-- Select Resolution --</option>
                                                        @foreach($leadResolutions as $item)
                                                            <option value="{{ $item['value'] }}">{{ $item['text'] }}</option>
                                                        @endforeach
                                                    </select>

                                                    <label><strong>Resolution Detail</strong> <small>(optional, up to 500 chars)</small></label>
                                                    <textarea name="resolution_detail" id="resolution_detail" class="form-control mb-2" rows="3" maxlength="500" placeholder="Provide details (required when 'Other' selected)"></textarea>
                                                </div>
                                                @endif
                                            <div class="form-group row">
                                                <div class="col-md-12 text-center mt-2">
                                                    <input type="reset" class="btn btn-primary submit" value="Cancel">
                                                    <button class="btn btn-primary submit" onclick="saveLeadInquiryOutcome(event, this);">Save</button>
                                                </div>
                                            </div>
                                            <div class="form-group row"  id="error-div" style="display: none;">
                                                <div class="col-md-12 text-center mt-2 textcolorred" id="error-inner-div">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
                <div class="col-md-12 card-cnt p-0 d-flex mb-5">
                    <div class="card main-detail-card lead-full">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">{{ ($leadsInquiriesDetail->type == 'inq')?'All Inquiries':'All Leads' }}</h5>
                            <div class="listdiv">
                            <div class="card-header bg-ntp">
                                @component('components.datatable-filters',['filters'=>$allInquiriesFilter,'formname'=>'allInquiriesFilterform'])@endcomponent
                            </div>
                            <div class="table-responsive bg-ntp">
                                <table class="table table-bordered" id="nthdataTable" aria-describedby="true">
                                    <thead>
                                        <tr>
                                            <th>{{ ($leadsInquiriesDetail->type == 'inq')?'INQ #':'Lead #' }}</th>
                                            @if($leadsInquiriesDetail->type === 'lead')
                                                @if(LD::checkFeatureFlag('nth-superseded-lead-notifications'))
                                                    <th>Superseded By</th>
                                                @endif
                                            @endif
                                            <th>Created Date</th>
                                            <th>Modified Date</th>
                                            <th>Status</th>
                                            @if($leadsInquiriesDetail->type == 'lead')
                                                @if(LD::checkFeatureFlag('add-resolve-outcome-for-nth-leads'))
                                                    <th>Resolution</th>
                                                @endif
                                            <th>Client</th>
                                            @endif
                                            @if($leadsInquiriesDetail->type == 'inq')
                                                <th>Resolution</th>
                                            @endif
                                            <th>Recruiter</th>
                                            <th>Outcome</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                </table>
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
<script type="text/javascript">
    $(document).ready(function($) {
        loadGetClinicianAllNthLeadsInquiries();
        reloadNthLeadsInquiriesHistory('{{ $leadsInquiriesDetail->id }}');
        $("#leadInquiryOutcomeForm").validate({
            ignore: [],
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "outcome":{
                    required: true
                },
                "nth_client": {
                    required: function(element) {
                        return ($('#nthLeadsInquiriesDetailModal #outcome').find('option:selected').data('id') === 'converttolead');
                    }
                },
                "resolution": {
                    required: function() {
                        // required only when Outcome == Resolved AND record is a lead
                        return ($('#nthLeadsInquiriesDetailModal #outcome').find('option:selected').data('id') === 'resolved'
                                && $('#leadInquiryOutcomeForm #type').val() === 'lead');
                    }
                },
                "resolution_detail": {
                    required: function() {
                        // required only when resolution == Other AND record is a lead
                        return ($('#leadInquiryOutcomeForm #resolution').val() === 'Other'
                                && $('#leadInquiryOutcomeForm #type').val() === 'lead');
                    },
                    maxlength: 500
                }
            },
            messages: {
                "outcome": {
                    required: "Select an Outcome to continue."
                },
                "resolution": {
                    required: "Select a Resolution to continue."
                },
                "resolution_detail": {
                    required: "Provide Resolution Detail when 'Other' is selected.",
                    maxlength: "Resolution Detail cannot exceed 500 characters."
                }
            }
        });
        var clientFlag = true;
        $("#leadInquiryOutcomeForm #nth_client").autocomplete({
            source: function(request, response) {
                $.getJSON("{{ route('nthalerts.getNTHClientList') }}", {
                    term: $('#leadInquiryOutcomeForm #nth_client').val()
                }, function(data) {
                    if (data.length === 0) {
                        clientFlag = false;
                    } else {
                        clientFlag = true;
                    }
                    response(data);
                });
            },
            minLength: 2,
            select: function(event, ui) {
                clientFlag = true;
                $('#leadInquiryOutcomeForm #patient_id').val(ui.item.id);
                $('#leadInquiryOutcomeForm #aoc_nthaccounts_id').val(ui.item.aoc_nthaccounts_id);
            },
            change: function(event, ui) {
                if (!ui.item) {
                    clientFlag = false;
                    $('#leadInquiryOutcomeForm #patient_id').val(0);
                    $('#leadInquiryOutcomeForm #aoc_nthaccounts_id').val(0);
                }
            }
        });
        $('#leadInquiryOutcomeForm #nth_client').keyup(function() {
            clientFlag = false;
            if ($(this).val() != '' && $('#leadInquiryOutcomeForm #patient_id').val() != 0) {
                clientFlag = true;
            }
        });
        $('#leadInquiryOutcomeForm #nth_client').blur(function() {
            if (clientFlag == false) {
                $(this).val('');
                $('#leadInquiryOutcomeForm #patient_id').val(0);
                $('#leadInquiryOutcomeForm #aoc_nthaccounts_id').val(0);
            }
        });

        @if(LD::checkFeatureFlag('lead-bulk-resolve-on-schedule') && $bucket === 'NTH LEADS AND INQUIRIES')
            $(document).off('shown.bs.modal', '#simpleLeadsModal')
              .on('shown.bs.modal', '#simpleLeadsModal', function () {

                function loadSimpleLeadsTable() {

                  if (window.simpleLeadsTriggerSource !== 'schedule') {
                    return;
                  }

                  let bucket = '{{ $bucket }}';
                  if (bucket !== 'NTH LEADS AND INQUIRIES') {
                    return;
                  }

                  window.simpleLeadsTriggerSource = null;

                  let currentClinicianID = "{{ $clinicianData->ClinicianID }}";

                  $('#simpleLeadsTable').DataTable({
                    processing: true,
                    serverSide: true,
                    paging: false,
                    info: false,
                    lengthChange: false,
                    destroy: true,
                    ajax: {
                      url: '{{ route("nthalerts.get-leads-for-bulk") }}',
                      type: 'POST',
                      data: function(d) {
                        d.ClinicianID = currentClinicianID;
                        d.exclude_lead_id = {{ $leadsInquiriesDetail->id }};
                        d._token = '{{ csrf_token() }}';
                      }
                    },
                    searching: false,
                    ordering: true,
                    columns: [
                      {
                        data: 'id',
                        render: data => `<input type="checkbox" class="lead-checkbox" value="${data}">`,
                        orderable: false
                      },
                      { data: 'ticket_id' },
                      { data: 'aoc_patient_id' },
                      { data: 'patient_name' },
                      { data: 'city_state' },
                      { data: 'Status' },
                      { data: 'Recruiter' }
                    ]
                  });
                }
                loadSimpleLeadsTable();
              });
        @endif
    });

    $(document).on('hidden.bs.modal', '#simpleLeadsModal', function () {
      if ($.fn.DataTable.isDataTable('#simpleLeadsTable')) {
        $('#simpleLeadsTable').DataTable().clear().destroy(true);
      }
    });

    $('#nthLeadsInquiriesDetailModal #outcome').change(function() {
        let selectedOption = $(this).find('option:selected').data('id');
        $('#nthLeadsInquiriesDetailModal #nth_client_div').hide();
        $('#resolution_div').hide();
        let leadStatus = "{{ $leadsInquiriesDetail->Status }}";

        // convert to lead logic
        if (selectedOption == 'converttolead') {
            $('#nthLeadsInquiriesDetailModal #nth_client_div').show();
        }

        // show resolution inputs when Outcome == Resolved AND current record is a lead
        if (selectedOption == 'resolved' && $('#leadInquiryOutcomeForm #type').val() === 'lead') {
            $('#resolution_div').show();
        } else {
            $('#leadInquiryOutcomeForm #resolution').val('');
            $('#leadInquiryOutcomeForm #resolution_detail').val('');
            $('#leadInquiryOutcomeForm').find('#resolution, #resolution_detail').removeClass('error');
            var validator = $('#leadInquiryOutcomeForm').data('validator') || $('#leadInquiryOutcomeForm').validate();
            if (validator) {
                validator.hideErrors();
            }
        }

        @if(LD::checkFeatureFlag('lead-bulk-resolve-on-schedule') && $bucket === 'NTH LEADS AND INQUIRIES')

          if ($('#nthLeadsInquiriesDetailModal').is(':visible') &&
                selectedOption === 'schedulenextsteps' &&
                (leadStatus === 'New' || leadStatus === 'Pending')) {
            $.ajax({
              url: '{{ route("nthalerts.check-other-leads") }}',
              type: 'POST',
              data: {
                _token: '{{ csrf_token() }}',
                ClinicianID: '{{ $clinicianData->ClinicianID }}',
                exclude_lead_id: '{{ $leadsInquiriesDetail->id }}'
              },
              success: function (response) {
                if (response.count > 0) {
                  window.simpleLeadsTriggerSource = 'schedule';
                  $('#simpleLeadsModal').modal('show');
                } else {
                }
              }
            });
          }
        @endif


    });
    $('#leadInquiryOutcomeForm #resolution').change(function() {
        if ($(this).val() === 'Other') {
            $('#leadInquiryOutcomeForm').find('#resolution_detail').rules('add', {
                required: true,
                messages: { required: "Provide Resolution Detail when 'Other' is selected." }
            });
        } else {
            $('#leadInquiryOutcomeForm').find('#resolution_detail').rules('remove', 'required');
        }
    });
    $(document).off('submit', '#allInquiriesFilterform').on('submit', '#allInquiriesFilterform', function(e) {
        e.preventDefault();
        $('#nthLeadsInquiriesDetailModal #nthdataTable').DataTable().ajax.reload();
    });
    function loadGetClinicianAllNthLeadsInquiries() {
        var dataTableDelay= 1000;
        var table = $('#nthLeadsInquiriesDetailModal #nthdataTable').DataTable({
            "order": [
                [1, "desc"]
            ],
            "ajax": {
                "url": "{{ route('nthalerts.getClinicianAllNthLeadsInquiries') }}",
                type: 'POST',
                data: function(d) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
                    d.id = '{{ $leadsInquiriesDetail->id }}';
                    d.ClinicianID = '{{ $leadsInquiriesDetail->ClinicianID }}';
                    d.type = '{{ $leadsInquiriesDetail->type }}';
                    d.resolution = '{{ $leadsInquiriesDetail->resolution }}';
                    d.filters = $('#allInquiriesFilterform').serializeArray();
                }
            },
            "processing": true,
            "serverSide": true,
            @php
                $columns = [
                    ['data' => 'ticket_id'],
                ];

                if ($leadsInquiriesDetail->type == 'lead') {
                    if (LD::checkFeatureFlag('nth-superseded-lead-notifications')) {
                        $columns[] = ['data' => 'superseded_by'];
                    }
                }
                $columns[] = ['data' => 'created_at'];
                $columns[] = ['data' => 'updated_at'];
                $columns[] = ['data' => 'Status'];

                if ($leadsInquiriesDetail->type == 'lead') {
                    if (LD::checkFeatureFlag('add-resolve-outcome-for-nth-leads')) {
                        $columns[] = ['data' => 'resolution'];
                    }
                    $columns[] = ['data' => 'patient_name'];
                }

                if ($leadsInquiriesDetail->type == 'inq') {
                    $columns[] = ['data' => 'resolution'];
                }

                $columns[] = ['data' => 'Recruiter'];
                $columns[] = ['data' => 'outcome'];
                $columns[] = ['data' => 'latestNotes'];
            @endphp
            "columns": {!! json_encode($columns) !!},
            "drawCallback": function(settings) {
            },
            "initComplete": function(settings, json) {
                jQuery('#dataTable_filter input').off('keyup.DT input.DT');
                var searchDelay = null;
                jQuery('#dataTable_filter input').on('keyup', function(e) {
                    var search = jQuery('#dataTable_filter input').val();
                    clearTimeout(searchDelay);
                    searchDelay = setTimeout(function() {
                        if (search != null && search.length >=3)
                            table.search(search).draw();
                        if (search.length==0 && e.keyCode==8)
                            table.search(search).draw();
                    }, dataTableDelay);
                });
            },
        });
    }
    function saveLeadInquiryOutcome(e, button){
        e.preventDefault();
        $('#nthLeadsInquiriesDetailModal #error-div').hide();
        if($('#leadInquiryOutcomeForm').valid()){
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#leadInquiryOutcomeForm').serializeObject();
            data['_token'] = _token;
            let selectedOutcomeId = $('#nthLeadsInquiriesDetailModal #outcome').find('option:selected').data('id');
            data['outcome_id'] = selectedOutcomeId;
            $.ajax({
                url: "{{route('nthalerts.saveLeadInquiryOutcome')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        $('#nthLeadsInquiriesDetailModal').modal('hide');
                        $('#nthLeadsInquiriesDetailModal').one('hidden.bs.modal', function() {
                            $('#dataTable-nth_leads').DataTable().ajax.reload(null, false);

                            if(data.outcome_id == 'schedulenextsteps' && $('#leadInquiryOutcomeForm #type').val() == 'lead') {
                                $('#sns-loading-overlay').show();

                                $(document).one('shown.bs.modal', '#schedule_next', function() {
                                    $('#sns-loading-overlay').hide();
                                });

                                showScheduleForm('', 'nthLeadInquiry##{{ $leadsInquiriesDetail->id }}', '{{$clientDetails->PatientAccount ?? ''}}',0);
                            }
                        });
                    }else{
                        $('#nthLeadsInquiriesDetailModal #error-inner-div').html(data.message);
                        $('#nthLeadsInquiriesDetailModal #error-div').show();
                        $('#leadInquiryOutcomeForm #nth_client').val('');
                        $('#leadInquiryOutcomeForm #patient_id').val(0);
                    }
                }
            });
        }
    }

    $(document).on('click', '#selectAllLeads', function() {
        const checked = this.checked;
        $('#simpleLeadsTable .lead-checkbox').prop('checked', checked);
    });

    $(document).off('click', '#resolveSelectedLeads').on('click', '#resolveSelectedLeads', function() {
        const $helper = $('#resolveHelper');
        const $btn = $(this);
        const currentLeadId = {{ $leadsInquiriesDetail->id }};
        const selectedIds = $('#simpleLeadsTable .lead-checkbox:checked')
            .map(function() { return $(this).val(); })
            .get();

        if (selectedIds.length === 0) {
            $helper
                .text('Select at least one Lead to resolve.')
                .removeClass('text-success')
                .addClass('text-danger fw-bold')
                .fadeIn()
                .delay(3000)
                .fadeOut();
            return;
        }

        const ids = [...selectedIds, currentLeadId];

        $helper.hide();
        $btn.prop('disabled', true).text('Processing...');

        $.ajax({
            url: '{{ route("nthalerts.bulk-resolve-leads") }}',
            type: 'POST',
            data: {
                ids: ids,
                advancing_lead_id: currentLeadId,
                _token: '{{ csrf_token() }}'
            },
            success: function(resp) {
                $btn.prop('disabled', false).text('Resolve Selected Leads');

                if (resp.success) {
                    $helper
                        .text(resp.message || 'Leads resolved successfully.')
                        .removeClass('text-danger')
                        .addClass('text-success fw-bold')
                        .fadeIn()
                        .delay(1000)
                        .fadeOut(function() {
                            $('#simpleLeadsModal').modal('hide');
                            $('#nthLeadsInquiriesDetailModal').modal('hide');

                            if ($.fn.DataTable.isDataTable('#dataTable-nth_leads')) {
                                $('#dataTable-nth_leads').DataTable().ajax.reload(null, false);
                            }

                          showScheduleForm('', 'nthLeadInquiry##{{ $leadsInquiriesDetail->id }}', '{{$clientDetails->PatientAccount ?? ''}}',0);
                        });
                } else {
                    $helper
                        .text(resp.message || 'Failed to resolve leads.')
                        .removeClass('text-success')
                        .addClass('text-danger fw-bold')
                        .fadeIn();
                }
            },
            error: function(xhr) {
                $btn.prop('disabled', false).text('Resolve Selected Leads');
                $helper
                    .text('Server error: ' + (xhr.responseJSON?.message || xhr.statusText))
                    .removeClass('text-success')
                    .addClass('text-danger fw-bold')
                    .fadeIn();
            }
        });
    });

    $(document).on('click', '.view-other-leads', function() {
        $('#allInquiriesFilterform select').multiselect('deselectAll', false).multiselect('refresh');
        const $statusSelect = $('#allInquiriesFilterform [name="nthlead_status"]');
        $statusSelect.multiselect('select', ['New', 'Pending']);
        $statusSelect.multiselect('refresh');
        $('#nthLeadsInquiriesDetailModal #nthdataTable').DataTable().ajax.reload();
    });

</script>
