<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $outcomes = config('constant')['advocacyStatusChangeRequestOutcome'];
?>
<x-modal>
    <x-slot name="id">advocacystatusChangeRequestNextStepForm</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">Status Change Request \ {{$clinicianDetails->fullName}}</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            &nbsp;
        </div>
        <?php if(!empty($clinicianDetails->request_outcome)) { ?>
            <div class="col-md-6 text-right pull-right">
                <span class='btn btn-primary'>
                    {{ nl2br($clinicianDetails->request_outcome) }}
                </span>
            </div>
        <?php } ?>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details">
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
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
                            </div>
                        </div>
                    </div>
                </div>

                <form class="scaffold-form" id="advocacystatusChangeOutcomeForm">
                    @csrf
                    <ul id="error-note"></ul>
                    <!-- hidden fields -->
                    <input type="hidden" name="id" id="id" value="{{$clinicianDetails->id}}" />
                    <input type="hidden" name="outcome_key" id="outcome_key" value="" />
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

                        <div class="col-md-6 p-0 m-0 hide suboutcome" id="requestedFields">

                            <div class="col-md-12 pt-0 m-0 p-0">
                                <label for="requested_reason">Reason</label>
                                <select id="requested_reason" name="requested_reason" class="form-control" autocomplete="off">
                                    <option value="">-- Select Reason --</option>
                                    <option value="Not Eligible for Rehire">Not Eligible for Rehire</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-md-6 p-0 m-0 hide suboutcome" id="rejectedFields">

                            <div class="col-md-12 pt-0 m-0 p-0">
                                <label for="rejected_reason">Reason</label>
                                <select id="rejected_reason" name="rejected_reason" class="form-control" autocomplete="off">
                                    <option value="">-- Select Reason --</option>
                                    <option value="Cancelled">Cancelled</option>
                                    <option value="Not Eligible for Rehire">Not Eligible for Rehire</option>
                                </select>
                            </div>
                        </div>
                       
                        <div class="col-md-6 p-0 m-0 hide suboutcome" id="pending_documentsFields">
                            <div class="col-md-12 pt-0 m-0 p-0">
                                <label for="pending_documents_reason" class="col-md-12">Document</label>

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
    $('#advocacystatusChangeOutcomeForm #pending_documents_reason').multiselect({
        includeSelectAllOption: true,
    });
    $(document).on('change','#advocacystatusChangeOutcomeForm #outcome',function(e){
        var outcome = $(this).find(':selected').attr('data-bucket-key');
        $("#commonfields").addClass("hide"); 
        $('.suboutcome').addClass("hide");
        $("#outcome_key").val(outcome);
        if(outcome=='rejected' || outcome=='requested' || outcome=='pending_documents') {
            $("#"+outcome+"Fields").removeClass("hide");
        }
        $('#saveStatusChangeOutcomeBtnDiv').removeClass("hide");
        if(outcome=='rejected') {
            $("#advocacystatusChangeOutcomeForm #rejected_reason").trigger("change");
        }
        
    });


    $(document).on('change','#advocacystatusChangeOutcomeForm #rejected_reason',function(e){
        var suboutcome = $(this).find(":selected").val();
        $("#commonfields").addClass("hide");
        if(suboutcome == 'Not Eligible for Rehire' || suboutcome=='Cancelled') {
            $("#commonfields").removeClass("hide");
        }
    });
});
function setOutcomeForm(button){
    if($('#advocacystatusChangeOutcomeForm').valid()){
        console.log("validated");
       
        //console.log($('#advocacystatusChangeOutcomeForm').serializeObject());
        button.disabled = true;
        $.ajax({
            url: "{{ route('advocacy.saveoutcomeform') }}",
            type: "POST",
            data: $("#advocacystatusChangeOutcomeForm").serializeObject(), //only input
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
    $('#advocacystatusChangeRequestNextStepForm').modal('hide');
    $('#dataTable-advocacy-advocacy_statuschangerequest').DataTable().ajax.reload();
}
$(function()
    {
        $('#advocacystatusChangeOutcomeForm').validate(
        {   rules:
            {   comment:{required:true},
                pending_documents_reason:{ required:function(element){
                    return $("#advocacystatusChangeOutcomeForm #outcome option:selected").attr('data-bucket-key') == "pending_documents";
                }},
                rejected_reason:{ required:function(element){
                    return $("#advocacystatusChangeOutcomeForm #outcome option:selected").attr('data-bucket-key') == "rejected";
                }},
                requested_reason:{ required:function(element){
                    return $("#advocacystatusChangeOutcomeForm #outcome option:selected").attr('data-bucket-key') == "requested";
                }},

            },
        });
    }
);

</script>
