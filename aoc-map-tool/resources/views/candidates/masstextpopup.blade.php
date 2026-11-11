<?php
use App\Helpers\H;
use App\Helpers\LD;
?>
<x-modal>
<x-slot name="id">masstextmodal</x-slot>
<x-slot name="size">w50</x-slot>
<x-slot name="title">Send SMS Message</x-slot>

<x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="modal-body" id="nthStepSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="modal-body" id="nthStepsFormDiv">
            <form method="post" autocomplete="off" id="mass_text_form" class="scaffold-form" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="candidate_ids" id="candidate_ids" value="{{$ids}}"/>
            <input type="hidden" name="frompage" id="frompage" value="{{$frompage}}"/>
            <input type="hidden" name="dtsearch" id="dtsearch" value="{{$dtsearch}}"/>
            <input type="hidden" name="department" id="department" value="{{$department}}"/>
            <div id="errorBody" class="alert alert-danger" style="display: none"></div>
            <?php if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit') && $ids=='selectallmap' && $frompage=='maps_textblast') {
                $loggedInUser = H::getLoggedInUser();
                $smsLimit = is_numeric($loggedInUser['sms_radius_limit'])?$loggedInUser['sms_radius_limit']:50; ?>
            <div id="errorBody2" class="alert alert-warning" style="display: block">You do not have permission to send campaigns beyond a [<?php echo $smsLimit?>]-mile radius.
                Your message will only be sent to the selected records within your radius limit.</div>
                <?php } ?>
            <div class="row pt-2">
                    <label for="no_of_nurses_contacted"  class="col-md-4 text-right">From:</label>
                   {{$senderInformation->name}} ({{$senderInformation->sending_sms_number}})
                </div>
                <div class="row pt-2">
                    <label for="no_of_nurses_contacted"  class="col-md-4 text-right">To:</label>
                    <?php if($ids == 'selectallmap') {
                        echo $totalSelectedCount." Candidates";
                    } else {
                        $ids = explode(",",$ids);
                        if($department == 'Client Care Coordinator') {
                            echo (count($ids) > 1)? count($ids).' Clients':count($ids).' Client';
                        } else {
                            echo (count($ids) > 1)? count($ids).' Candidates':count($ids).' Candidate';
                        }
                    }
                    ?>
                </div>
                @if(LD::checkFeatureFlag('mass-text-template-option'))
                @if(!empty($templateList ))
                <div class="row pt-2">
                    <label for="no_of_post_made"  class="col-md-4 text-right">Template:</label>
                    <div class="col-md-6 pl-1">
                        <select id="template_select" name="template_select" class="form-control" autocomplete="off">
                            <option value=""">Select a Template</option>
                            @foreach($templateList as $templateData)
                            <option data-template="{{ $templateData->template }}"
                                value="{{ $templateData->template_name }}">
                                {{ $templateData->template_name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                @endif
                @endif
                <div class="row pt-2">
                    <label for="no_of_post_made"  class="col-md-4 text-right">Message:</label>
                    <div class="col-md-6 pl-1">
                        <textarea name="send_message" id="send_message" rows="4"
                        cols="50" onkeyup="countChar()"></textarea>
                        <div class="col-md-6 pl-1 d-flex textSuccess" id="charCountDiv">
                            <div id="charNum">0</div>&nbsp;/&nbsp;138 characters</div>
                    </div>
                </div>
                <div class="row pt-2" id="outOfOfficeInput" style="display: none;">
                    <label for="ooo_from_date"  class="col-md-4 text-right">Out of Office From:</label>
                    <div class="col-md-6 pl-1">
                        <input type="text" id="ooo_from_date" name="ooo_from_date" class="form-control">
                    </div>
                    <label for="ooo_to_date" class="col-md-4 text-right pt-2">Out of Office To:</label>
                    <div class="col-md-6 pl-1 pt-2">
                        <input type="text" id="ooo_to_date" name="ooo_to_date"  class="form-control">
                    </div>
                </div>
                <div id="candidate_save" class="row pt-2">
                    <div class="col-md-7 text-right">
                        <button type="button" onclick="saveMassText(this)" id="setScheduleBtn" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
<x-slot name="footer"></x-slot>
</x-modal>

<script>
    function countChar() {
        var maxlimit = 138;
        var len = $('#send_message').val().length;
        var charcntr = maxlimit - len;
        $('#charCountDiv').removeClass('textcolorred');
        $('#charCountDiv').addClass('textSuccess');
        if(charcntr < 0) {
            $('#charCountDiv').addClass('textcolorred');
            $('#charCountDiv').removeClass('textSuccess');
        } else {
            charcntr = len;
        }
        $('#charNum').html(charcntr);
    };
    function closeForm() {
        $('#masstextmodal').modal('hide');
    }
    function saveMassText(button) {
        event.preventDefault();
        if ($("#mass_text_form").valid() == false) {
            return false;
        }
        button.disabled = true;
        button.innerHTML = 'Please wait...';
        $.ajax({
            url: "{{ route('save.massmessage') }}",
            type: "POST",
            data: $("#mass_text_form").serialize(),
            timeout: 300000,
            success: function(response) {
                if (response) {
                    $("#successBody").html(response.message);
                    if (response.success == 1) {
                            $("#nthStepsFormDiv").hide();
                            $("#nthStepSuccessDiv").show();
                            $("#mass_text_form")[0].reset();
                    } else if (response.success == -1) {
                        $('#continuemassmessage').remove();
                        $("<input type='hidden' name='continuemassmessage' \
                        id='continuemassmessage' value='0'/>").insertAfter("#mass_text_form #send_message");
                        modalConfirm(response.message,'Yes','No',function(res, modelDialog){
                            if(res){
                                $('#continuemassmessage').val('1');
                                saveMassText(button);
                                $('.modal').modal('hide');
                            }else{
                                modelDialog.modal('hide');
                                $('body').addClass("modal-open");
                                $('#continuemassmessage').remove();
                            }
                        });
                    } else {
                        $("#errorBody").html(response.message);
                        $("#nthStepsFormDiv").show();
                        $("#nthStepSuccessDiv").hide();
                        $("#errorBody").show();
                    }
                }
            },
            error: function(xhr, status, error) {
                errorText = 'Something went wrong, Please try again.';
                if (status === 'timeout') {
                    errorText = (
                        'Timeout: The text blast is unable to send at this time. ' +
                        'Please try again in an hour. If the issue persists, ' +
                        'please submit a ticket via FreshService with the error message included.');
                }

                $("#errorBody").html(errorText);
                $("#nthStepsFormDiv").show();
                $("#nthStepSuccessDiv").hide();
                $("#errorBody").show();
            },
            complete: function() {
                button.disabled = false;
                button.innerHTML = 'Submit';
            }
        });
    }
</script>

<script type="text/javascript">
$(document).ready(function() {
    $('#masstextmodal').on('shown.bs.modal', function() {
        $("#nthStepsFormDiv").show();
        $("#nthStepSuccessDiv").hide();
    });
    @if($frompage == 'caseload')
    $('#masstextmodal').on('hidden.bs.modal', function() {
        $('.select-all-case_load').prop('checked', false);
        $('#select-all-checkbox-case_load').prop('checked', false);
        $('div.updateall').remove();
    });
    @else
    $('#masstextmodal').on('hidden.bs.modal', function() {
        $('.select-all-candidate').prop('checked', false);
        $('#select-all-checkbox-candidate').prop('checked', false);
        $('#new_dataTable_wrapper div.updateall').remove();
    });
    @endif
});
</script>
@if(LD::checkFeatureFlag('mass-text-template-option'))
<script type="text/javascript">
$(document).ready(function() {
    $('#template_select').change(function() {
        $("#outOfOfficeInput").hide();
        var selectedTemplate = '';
        if($(this).find(':selected').val() != '') {
            $('#send_message').prop('readonly', true);
            selectedTemplate = $(this).find(':selected').data('template');
            if (selectedTemplate.indexOf("ooo_to_date") !== -1) {
                $("#outOfOfficeInput").show();
            }
        } else {
            $('#send_message').removeAttr('readonly');
        }
        $('#send_message').val(selectedTemplate);
        countChar();
    });
});
function getDate( element ) {
    var date;
    try {
        date = $.datepicker.parseDate( 'mm/dd/yy', element.value );
    } catch( error ) {
        date = null;
    }
    return date;
}
$(document).ready(function() {
    $("#ooo_from_date").datepicker({
        dateFormat: 'mm/dd/yy',
        minDate: new Date(),
        beforeShow: function(input, inst) {
            var calendar = inst.dpDiv;
            setTimeout(function() {
                calendar.position({
                    my: 'left top',
                    at: 'left bottom',
                    collision: 'none',
                    of: input
                });
            }, 1);
        }
    }).on('change', function () {
        $('#ooo_to_date').datepicker("option", 'minDate', getDate( this ) );
    });

    $("#ooo_to_date").datepicker({
        dateFormat: 'mm/dd/yy',
        minDate: new Date(),
        beforeShow: function(input, inst) {
            var calendar = inst.dpDiv;
            setTimeout(function() {
                calendar.position({
                    my: 'left top',
                    at: 'left bottom',
                    collision: 'none',
                    of: input
                });
            }, 1);
        }
    }).on('change', function () {
        $('#ooo_from_date').datepicker("option", 'maxDate', getDate( this ) );
    });
});
$(function() {
    $("#mass_text_form").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "ooo_from_date": {
                required: true
            },
            "ooo_to_date": {
                required: true
            }
        },
        errorPlacement: function(error, element) {}
    });
});
</script>
@endif
