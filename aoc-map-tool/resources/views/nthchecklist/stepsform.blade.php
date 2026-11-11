<?php
use App\Helpers\LD;
use App\Helpers\H;
?>
<x-modal>
    <x-slot name="id">nthsteps_form</x-slot>
    <x-slot name="title">{{ $title }}</x-slot>
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
            <form method="post" autocomplete="off" id="nth_steps_form" class="scaffold-form"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden"  name="type" value="{{$type}}"/>
                <input type="hidden" name="ids_{{str_replace(' ', '_', $type)}}" id="ids_{{str_replace(' ', '_', $type)}}" value="{{$ids}}"/>
                <div class="row pt-2">
                        <label for="no_of_nurses_contacted"  class="col-md-4 text-right">{{$fieldLabel}}</label>
                        <input type="text" name="no_of_nurses_contacted" id="no_of_nurses_contacted" class="col-md-6 pl-1"/>
                </div>
                @if($type == 'social_media')
                <div class="row pt-2">
                        <label for="no_of_post_made"  class="col-md-4 text-right">Number of posts made</label>
                        <input type="text" name="no_of_post_made" id="no_of_post_made" class="col-md-6 pl-1"/>
                </div>
                @endif
                @if(LD::checkFeatureFlag('escalated-content-fields-for-text-blast'))
                @if($type == 'text_blast')
                <div class="row pt-2">
                    @if($escalatedRateText != '')
                    <label for="TextBlastEscalatedRate"  class="col-md-4 text-right">&nbsp;</label><label class="col-md-8  pl-0 font-italic text-left">{{ $escalatedRateText}}</label>
                    @endif
                </div>
                <div class="row">
                    <label for="TextBlastEscalatedRate"  class="col-md-4 text-right">Escalated Rate</label>
                    <input type="text" name="TextBlastEscalatedRate" id="TextBlastEscalatedRate" class="col-md-6 pl-1"/>
                </div>
                @if(LD::checkFeatureFlag('add-escalated-margin-field-to-nth-text-blast-widget'))
                    <div class="row pt-2">
                        <label for="TextBlastEscalatedMargin"  class="col-md-4 text-right">Escalated Margin</label>
                        <input type="text" name="TextBlastEscalatedMargin" id="TextBlastEscalatedMargin" class="col-md-6 pl-1"/>
                    </div>
                @endif
                <div class="row pt-2">
                    <label for="TextBlastContent"  class="col-md-4 text-right">Content</label>
                    <textarea name="TextBlastContent" id="TextBlastContent" class="col-md-6 pl-1"></textarea>
                </div>
                @endif
                @endif
                <div id="candidate_save" class="row pt-2">
                    <div class="col-md-7 text-right">
                        <button type="button" onclick="setSchedule(this)" id="setScheduleBtn" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<script type="text/javascript">
// Wait for the DOM to be ready
$(function() {
    setTimeout( function() { $("#no_of_nurses_contacted").focus(); }, 500 );

    $.validator.addMethod('onlyOne', function(value, element, param) {
        return ($(element).is(':filled') && $('[name="' + param[0] + '"]').is(':blank')) || (($('[name="' + param[0] + '"]').is(':filled')) && $(element).is(':blank')) || (($('[name="' + param[0] + '"]').is(':filled')) && $(element).is(':filled'));
    }, "Please fill out only one of these fields");

    $("#nth_steps_form").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            <?php if($type == 'social_media') { ?>
            "no_of_nurses_contacted": {
                onlyOne: ["no_of_post_made"],
                digits: true
            },
            "no_of_post_made": {
                onlyOne: ["no_of_nurses_contacted"],
                digits: true
            },
            <?php } elseif($type == 'text_blast') { ?>
                "no_of_nurses_contacted": {
                    required: true,
                    digits: true
                },
            <?php if(LD::checkFeatureFlag('escalated-content-fields-for-text-blast')) { ?>
                "TextBlastEscalatedRate": {
                    required: true,
                    number: true
                },
                <?php if(LD::checkFeatureFlag('add-escalated-margin-field-to-nth-text-blast-widget')) { ?>
                "TextBlastEscalatedMargin": {
                    required: true,
                    number: true
                },
                <?php } ?>
                "TextBlastContent": {
                    required: true
                },                
            <?php }} else { ?>
            "no_of_nurses_contacted": {
                required: true,
                digits: true
            },
            <?php } ?>
        },
        errorPlacement: function(error, element) {}
    });
});
</script>

<script type="text/javascript">
function closeForm() {
    $('#nthsteps_form').modal('hide');
}
$("#nth_steps_form").on("keydown",function(e){
    var keyCode = e.which || e.keyCode;
    if(keyCode == 13) // enter key code
    {
        $(this).find('button').click();
    }

});
function setSchedule(button) {
    event.preventDefault();
    if ($("#nth_steps_form").valid() == false) {
        return false;
    }
    button.disabled = true;    
    $.ajax({
        url: "{{ route('dashboard.saveNthSteps') }}",
        type: "POST",
        data: $("#nth_steps_form").serialize(),
        success: function(response) {
            button.disabled = false;
            if (response) {
                $("#successBody").html(response.message);
                if (response.success == 1) {
                        $("#nthStepsFormDiv").hide();
                        $("#nthStepSuccessDiv").show();
                        $("#nth_steps_form")[0].reset();
                } else {
                    $("#nthStepsFormDiv").show();
                    $("#nthStepSuccessDiv").hide();
                }
            }
        },
    });
}
</script>

<script type="text/javascript">
$(document).ready(function() {
    $('#nthsteps_form').on('shown.bs.modal', function() {
        $("#nthStepsFormDiv").show();
        $("#nthStepSuccessDiv").hide();
    });

    $('#nthsteps_form').on('hidden.bs.modal', function() {
        type = $(this).find('form input[name="type"]').val()
        $('.select-all-' + type + '.td').prop('checked', false);
        $('#select-all-checkbox-' + type).prop('checked', false);
        $('#dataTable-nthchecklist-' + type + '_wrapper').find('.updateall').remove();
    });
});
</script>
