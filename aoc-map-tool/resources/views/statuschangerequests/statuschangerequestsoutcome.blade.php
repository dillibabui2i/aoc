<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $outcomes = config('constant')['statusChangeRequestOutcome'];
    $requested = $clinicianDetails->EmploymentStatus;
    $status = $clinicianDetails->CurrentStatus;

   
?>
<x-modal>
    <x-slot name="id">statusChangeRequestNextStepForm</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">Update Workflow \ {{$clinicianDetails->fullName}}</x-slot>
    <x-slot name="extraHeader">
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details">
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-4">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Clinician</h5>
                            <div class="detail-section">
                                <h6>
                                    {{$clinicianDetails->FirstName}} {{$clinicianDetails->LastName}}
                                </h6>
                                <div class="tags-cnt">
                                    <span class='tag'>
                                        {{ $clinicianDetails->DisciplineName }}
                                    </span>
                                </div>
                                <p class="card-text">
                                    {{$clinicianDetails->Phone}}
                                </p>
                                <p class="card-text">
                                    {{$clinicianDetails->Mobile}}
                                </p>
                                <p class="card-text">
                                    {{$clinicianDetails->External_Email}}
                                </p>
                                <ul class="d-flex att-cnt">
                                    <li>
                                        {{$clinicianDetails->Address1}}
                                    </li>
                                    <li>
                                        {{$clinicianDetails->Address2}}
                                    </li>
                                    <li>
                                        {{$clinicianDetails->City}}
                                    </li>
                                    <li>
                                       {{$clinicianDetails->State}}
                                    </li>
                                    <li>
                                        {{$clinicianDetails->Zip}}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Status Change Request</h5>
                            <div class="detail-section">
                                <p class="card-text">
                                    Requested Status: {{$clinicianDetails->EmploymentStatus}}
                                </p>
                                <p class="card-text">
                                    Task Status: {{$clinicianDetails->request_outcome}}
                                </p>
                                @if(LD::checkFeatureFlag('add-part-time-and-full-time-fild-in-status-change-request'))
                                    <p class="card-text">
                                    Full/Part Time: {{$clinicianDetails->change_request_employment == 1 ? 'Part Time'
                                    : ($clinicianDetails->change_request_employment == 2 ? 'Full Time' : NULL) }}
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <form class="scaffold-form" id="statusChangeOutcomeForm">
                    @csrf
                    <ul id="error-note"></ul>
                    <!-- hidden fields -->
                    <input type="hidden" name="id" id="id" value="{{$clinicianDetails->id}}" />
                    <input type="hidden" name="outcome_key" id="outcome_key" value="" />
                    <input type="hidden" name="canshowcheckbox" id="canshowcheckbox" value="<?php echo H::getHelper("StatusChangeRequests")->canShowCheckMark($status,$requested)?>"/>
                    <div class="row pt-2">
                        <div class="col-md-6">
                            <label for="outcome">Outcome</label>
                            <select id="outcome" name="outcome" class="form-control" autocomplete="off">
                                <option value="">-- Select One --</option>
                                @foreach ($outcomes as $item)
                                <option data-bucket-key={{str_replace('-',"_",Str::slug($item))}} {{isset($defaultOutcome) && $defaultOutcome == $item ? "selected='selected'" : ""}}
                                    value="{{  $item }}">{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 p-0 m-0 hide suboutcome" id="rejectedFields">
                            <label for="rejected_reason">Reason</label>
                            <select id="rejected_reason" name="rejected_reason" class="form-control" autocomplete="off">
                                <option value="">-- Select Reason --</option>
                                <option value="Cancelled">Cancelled</option>
                                <option value="Current Status is Correct">Current Status is Correct</option>
                                <option value="Inaccurate Use of Status">Inaccurate Use of Status</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 p-0 m-0 hide suboutcome" id="pending_advocacyFields">
                            <label for="pending_advocacy_reason">Reason</label>
                            <select id="pending_advocacy_reason" name="pending_advocacy_reason" class="form-control" autocomplete="off">
                                <option value="">-- Select Reason --</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 p-0 m-0 hide suboutcome" id="pending_documentsFields">
                                <label class="col-md-12" for="pending_documents_reason">Document</label>
                                <select id="pending_documents_reason" name="pending_documents_reason" class="form-control selectpicker" multiple data-live-search="true" autocomplete="off">
                                    <option value="Annual In-Service">Annual In-Service</option>
                                    <option value="Application">Application</option>
                                    <option value="Article 9">Article 9</option>
                                    <option value="Auto Insurance">Auto Insurance</option>
                                    <option value="Background">Background</option>
                                    <option value="Check-offs">Check-offs</option>
                                    <option value="CPR">CPR</option>
                                    <option value="I-9">I-9</option>
                                    <option value="Needs New IPEA">Needs New IPEA</option>
                                    <option value="Professional License">Professional License</option>
                                    <option value="State License">State License</option>
                                    <option value="TB Test Results">TB Test Results</option>
                                </select>
                        </div>
                    </div>
                    <div id="outcomeForm">
                        <div class="col-md-12 p-0 m-0 hide suboutcome" id="commonfields">
                            <div id="completed_established" class="col-md-12 pt-2 m-0 p-0">
                                <div class="col-md-12 pl-0">
                                    <label class="w-100">Comment</label>
                                    <textarea name="comment" id="comment"  class="form-control"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="verify_completed_container" class="row col-md-12 pl-3 m-0 pt-3 hide">
                        <label for="verify_completed" class="form-check-label"> By checking this box, I confirm that I have completed the Move to Active checklist to confirm all requirements are in place in accordance with company policy and procedure.</label>
                        <input class="form-check-input radio-inline on-change" type="checkbox" name="verify_completed" id="verify_completed" value="1">
                    </div>
                    <div class="row pt-5 hide" id="saveStatusChangeOutcomeBtnDiv">
                        <div class="col-md-7 text-right">
                            <button type="button" id="saveStatusChangeOutcomeBtn" onclick="setOutcomeForm(this)"  class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>
$(document).ready(function() {
    $('#statusChangeOutcomeForm #pending_documents_reason').multiselect({
        includeSelectAllOption: true,
    });
    
    $(document).on('change','#statusChangeOutcomeForm #outcome',function(e){
        var outcome = $(this).find(':selected').attr('data-bucket-key');
        var canshowcheckbox = $("#canshowcheckbox").val();
        
        $("#verify_completed_container").addClass("hide");
        $('.suboutcome').addClass("hide");
        $("#outcome_key").val(outcome);
        if(outcome=='rejected' || outcome=='pending_advocacy' || outcome=='pending_documents') {
            $("#"+outcome+"Fields").removeClass("hide");
        }
        if(outcome=='completed' && canshowcheckbox==1) {
            $("#verify_completed_container").removeClass("hide");
        }
        $('#saveStatusChangeOutcomeBtnDiv').removeClass("hide");
    });
    $(document).on('change','#statusChangeOutcomeForm #rejected_reason, #statusChangeOutcomeForm #pending_advocacy_reason',function(e){
        var suboutcome = $(this).find(":selected").val();
        $("#commonfields").addClass("hide");
        $("#commonfields").removeClass("hide");
    });
});
function setOutcomeForm(button){
    if($('#statusChangeOutcomeForm').valid()){
        //console.log($('#statusChangeOutcomeForm').serializeObject());
        button.disabled = true;
        $.ajax({
            url: "{{ route('statuschangerequests.saveoutcomeform') }}",
            type: "POST",
            data: $("#statusChangeOutcomeForm").serializeObject(), //only input
            success: function(response) {

                if (response.status) {
                    button.disabled = false;
                    closeForm();
                }
            },
        });
    }
}
function closeForm() {
    $('#statusChangeRequestNextStepForm').modal('hide');
    if($.fn.dataTable.isDataTable('#dataTable-changeRequest-oc_status_change_requests')){
        $('#dataTable-changeRequest-oc_status_change_requests').DataTable().ajax.reload();
    }
    if($.fn.dataTable.isDataTable('#dataTable')){
        $('#dataTable').DataTable().ajax.reload();
    }
}
$(function()
    {
        $('#statusChangeOutcomeForm').validate(
        {   rules:
            {   comment:{ required:true},
                pending_documents_reason:{ required:function(element){
                    return $("#statusChangeOutcomeForm #outcome option:selected").attr('data-bucket-key') == "pending_documents";
                }},
                rejected_reason:{ required:function(element){
                    return $("#statusChangeOutcomeForm #outcome option:selected").attr('data-bucket-key') == "rejected";
                }},
                pending_advocacy_reason:{ required:function(element){
                    return $("#statusChangeOutcomeForm #outcome option:selected").attr('data-bucket-key') == "pending_advocacy";
                }},
                verify_completed:{ required:function(element){
                    return $("#statusChangeOutcomeForm #outcome option:selected").attr('data-bucket-key') == "completed" && $("#canshowcheckbox").val()=='1';
                }},
                

            },
        });
    }
);

</script>
