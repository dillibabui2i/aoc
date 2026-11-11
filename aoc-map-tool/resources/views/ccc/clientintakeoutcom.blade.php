<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $specialized = 'No';
    if(H::getHelper('NTHChecklist')->isSpecialized($clientDetails->CF_Skills)) {
        $specialized = 'Yes';
    }
    $priority = 'No';
    if(isset($needToHireChecklistDetails->IsPriority) && $needToHireChecklistDetails->IsPriority==1) {
        $priority = 'Yes';
    }
    $outcomeTempList = ["Document Call", "Requested Call back"];
    $outcomeAttempt = [];

     if($attemptCount==0 || $attemptCount==NULL){
        $outcomeAttempt[] = "Attempt 1";
    }
    if($attemptCount==1){
        $outcomeAttempt[] = "Attempt 2";
    }
    if($attemptCount==2){
        $outcomeAttempt[] = "Attempt 3";
    }

    $outcomeList = array_merge($outcomeAttempt,$outcomeTempList);

?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<link href="{{ mix('css/jquery.datetimepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/jquery.datetimepicker.full.min.js') }}"></script>
<x-modal>
    <x-slot name="id">client_intake_outcom_modal</x-slot>
    <x-slot name="size">w70</x-slot>
    <x-slot name="title">Introductory call \ {{$clientDetails->Patient_FirstName}} {{$clientDetails->Patient_LastName}}</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$clientDetails->id}}', 'client', 'Client Notes Added', 'note')"><i
                class="fa  fa-sticky-note-o"></i>
            </a>
        </div>
    </x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details client-cnt">
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client Info</h5>
                            <div class="detail-section">
                                <div class="row">
                                    <div class="col-md-6">{{$clientDetails->Patient_FirstName}} {{$clientDetails->Patient_LastName}}</div>
                                    <div class="col-md-6">DOB: {{H::getFormatedDate($clientDetails->Patient_DOB)}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><p class="card-text">{{$clientDetails->PatientAccount}}</p></div>
                                    <div class="col-md-6">Age: {{$clientDetails->Patient_age}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><p class="card-text">{{$clientDetails->FacilityName}}</p></div>
                                    <div class="col-md-6">Weight: {{$clientDetails->CF_Weight}}</div>
                                </div>
                                <div class="row pt-3">
                                    <div class="col-md-6"><p class="card-text">{{$clientDetails->Patient_Phone}}</p></div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="card-text">
                                        @if($clientDetails->Patient_MobilPhone)
                                            {{$clientDetails->Patient_MobilPhone}} (mobile)
                                        @endif
                                        </p>
                                    </div>
                                </div>

                                <div class="row pt-3">
                                    <div class="col-md-6">
                                        <p class="card-text">{{$clientDetails->Patient_Address1}} {{$clientDetails->Patient_Address2}}</p>
                                        <p class="card-text">{{$clientDetails->Patient_City}} {{$clientDetails->Patient_State}} {{$clientDetails->Patient_Zip}}</p>
                                    </div>
                                    <div class="col-md-6">
                                        @if($clientDetails->Patient_Gender != '')
                                        <div class="tags-cnt">
                                            <span class='tag tag-grey'>{{$clientDetails->Patient_Gender}}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">&nbsp;</h5>
                            <div class="detail-section">
                                <div class="row">
                                    <div class="col-md-6">PDN Auth Hours:  {{$clientDetails->AuthorizedWeeklyHours}}</div>
                                    <div class="col-md-6">Specialized Client:  {{$specialized}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">CNA Auth Hours:  {{$clientDetails->CNAAuthorizedWeeklyHours}}</div>
                                    <div class="col-md-6">Priority: {{$priority}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">Respite Auth Hours:  {{$clientDetails->CF_RespWeeklyHrs}}</div>
                                    <div class="col-md-6">Specialized Rate: </div>
                                </div>
                                <div class="row pt-3">
                                    <div class="col-md-6">
                                        @if($clientDetails->is_workForce)
                                        <div class="tags-cnt">
                                            <span class='tag'>Work Force Dev Proj</span>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="col-md-6">
                                    {!! $clientDetails->skillDisplayValue !!}
                                    </div>
                                </div>
                                <div class="row pt-3">
                                    <div class="col-md-12">Terminal Payor:  {{$clientDetails->Payer??''}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">Respite Terminal Payor:  {{$clientDetails->RespitePayer??''}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 p-0" id="coverageTrackersFormDiv">
                    <form method="post" autocomplete="off" id="client_intake_outcom_form" class="scaffold-form"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden"  name="aoc_patient_id" value="{{$clientDetails->id}}"/>
                    <div class="row pt-2">
                        <div class="col-md-7">
                            <label for="outcome"  class="col-md-5 text-left p-0">Outcomes:</label>
                            <select class="form-control  outcome" name="outcome" id="outcome">
                                <option value="">Select Outcome</option>
                                <?php foreach($outcomeList as $outcome) { ?>
                                    <option value="<?php echo $outcome?>"><?php echo $outcome?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>

                    <section class="hide pt-1 pl-0" id="contact_attempt">

                        <div class="col-md-12">
                            <label class="w-100">Comment:</label>
                            <textarea name="contact_comment" id="contact_comment"  class="form-control"></textarea>
                        </div>
                    </section>

                    <div class="row pt-2 hide" id="document_container">
                        <div class="col-md-7">
                            <label for="comment"  class="col-md-4 text-left p-0">Comment:</label>
                            <textarea name="comment" id="comment" class="form-control"></textarea>
                        </div>
                    </div>

                    <section id="request_callback_container" class="hide">
                        <div class="form-group">
                            <div class="mt-2 col-md-7 pl-0">
                                <input type="text" class="form-control general" placeholder="Choose Callback DateTime" id="requested_callback" required="true" name="requested_callback" value=""/>
                            </div>
                        </div>
                    </section>

                    <div id="bottom_container" class="row pt-2 hide">
                        <div class="col-md-7 text-right">
                            <button type="button" onclick="saveIntakeOutcome(this)" id="saveIntakeOutcomeBtn" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                    </form>
                </div>
                <div class="col-md-12 p-2" id="coverageTrackerSuccessDiv" style="display: none">
                    <div id="successBody" class="alert-success"></div>
                    <div class="row pt-2">
                        <div class="col-md-7 text-right">
                            <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>


<script type="text/javascript">
$('#client_intake_outcom_form #requested_callback').datetimepicker({
            format: 'Y-m-d H:i:s',
            lang: 'en',
        });

$(document).on("change","#client_intake_outcom_form #outcome",function(){
            var selectedValue =$(this).find(":selected").val();
            $("#client_intake_outcom_form #request_callback_container").addClass("hide");
            $("#client_intake_outcom_form #document_container").addClass("hide");
            $("#client_intake_outcom_form #bottom_container").addClass("hide");
            $("#client_intake_outcom_form #contact_attempt").addClass("hide");

            if(selectedValue!='') {
                $("#client_intake_outcom_form #bottom_container").removeClass("hide");
                if(selectedValue=='Document Call') {
                    $("#client_intake_outcom_form #document_container").removeClass("hide");
                } else if(selectedValue=='Requested Call back') {
                    $("#client_intake_outcom_form #request_callback_container").removeClass("hide");
                } else if(selectedValue=='Attempt 1' || selectedValue=='Attempt 2' || selectedValue=='Attempt 3') {
                    $("#client_intake_outcom_form #contact_attempt").removeClass("hide");
                }
            }
        });

// Wait for the DOM to be ready
$(function() {
    setTimeout( function() { $("#no_of_nurses_contacted").focus(); }, 500 );

    $.validator.addMethod('onlyOne', function(value, element, param) {
        return ($(element).is(':filled') && $('[name="' + param[0] + '"]').is(':blank')) || (($('[name="' + param[0] + '"]').is(':filled')) && $(element).is(':blank')) || (($('[name="' + param[0] + '"]').is(':filled')) && $(element).is(':filled'));
    }, "Please fill out only one of these fields");

    $("#client_intake_outcom_form").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "outcome": {
                required: true
            },
            requested_callback:{ required:function(element){
                return $("#client_intake_outcom_form #outcome option:selected").val() == "Requested Call back";
            }},
            comment:{ required:function(element){
                return $("#client_intake_outcom_form #outcome option:selected").val() == "Document Call";
            }},


            contact_comment:{ required:function(element){
                return $("#client_intake_outcom_form #outcome option:selected").val() == "Attempt 1" || $("#client_intake_outcom_form #outcome option:selected").val() == "Attempt 2" || $("#client_intake_outcom_form #outcome option:selected").val() == "Attempt 3";
            }},
        },
        errorPlacement: function(error, element) {}
    });
});
</script>

<script type="text/javascript">

function closeForm() {
    $('#client_intake_outcom_modal').modal('hide');
    $("#dataTable-clientcare-exception-" + bucket_type).DataTable().ajax.reload();
}
$("#client_intake_outcom_form").on("keydown",function(e){
    var keyCode = e.which || e.keyCode;
    if(keyCode == 13) // enter key code
    {
        $(this).find('button').click();
    }
});
function saveIntakeOutcome(button) {
    event.preventDefault();
    if ($("#client_intake_outcom_form").valid() == false) {
        return false;
    }
    button.disabled = true;
    $.ajax({
        url: "{{ route('saveclientintakeoutcom') }}",
        type: "POST",
        data: $("#client_intake_outcom_form").serialize(),
        success: function(response) {
            button.disabled = false;
            if (response) {
                $("#successBody").html(response.message);
                if (response.success == 1) {
                        $("#coverageTrackersFormDiv").hide();
                        $("#coverageTrackerSuccessDiv").show();
                        $("#client_intake_outcom_form")[0].reset();
                } else {
                    $("#coverageTrackersFormDiv").show();
                    $("#coverageTrackerSuccessDiv").hide();
                }
            }
        },
    });
}
</script>

<script type="text/javascript">
$(document).ready(function() {
    $('#client_intake_outcom_modal').on('shown.bs.modal', function() {
        $("#coverageTrackersFormDiv").show();
        $("#coverageTrackerSuccessDiv").hide();
    });

    $('#client_intake_outcom_modal').on('hidden.bs.modal', function() {
        type = $(this).find('form input[name="type"]').val()
        $('.select-all-' + type + '.td').prop('checked', false);
        $('#select-all-checkbox-' + type).prop('checked', false);
        $('#dataTable-clientcare-exception-ccc_new_clients_wrapper').find('.updateall').remove();
        $("#dataTable-clientcare-exception-ccc_new_clients").DataTable().ajax.reload();
    });
});
</script>
