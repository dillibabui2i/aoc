<?php
use App\Helpers\LD;
use App\Helpers\H;
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$buckets =H::getHelper("ClientCareCoordinatorCoverage")->getBucketArray();
$nextStepName = '';
$nextStepKey = 0;
$currentStep = array_search($type, array_keys($buckets));
$nextStep = $currentStep+1;
$values = array_values($buckets);
$keys = array_keys($buckets);
if ($nextStep < count($values)) {
	$nextStepName = $values[$nextStep];
	$nextStepKey = $keys[$currentStep + 1];
}
$lastStepKey = $keys[count($keys)-1];
?>
<x-modal>
    <x-slot name="id">clientcarecoveragetracker_form</x-slot>
    <x-slot name="title">{{ $title }}</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="modal-body" id="coverageTrackerSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="modal-body" id="coverageTrackersFormDiv">
            <form method="post" autocomplete="off" id="coverage_tracker_form" class="scaffold-form"
                enctype="multipart/form-data">
                @csrf
                <input type="hidden"  name="type" value="{{$type}}"/>
                <input type="hidden" name="ids_{{str_replace(' ', '_', $type)}}" id="ids_{{str_replace(' ', '_', $type)}}" value="{{$ids}}"/>
                <div class="row pt-2">
                        <label for="no_of_nurses_contacted"  class="col-md-5 text-right">{{$fieldLabel}}?</label>
                        <input type="text" name="no_of_nurses_contacted" id="no_of_nurses_contacted" class="col-md-6 pl-1"/>
                </div>
                @if($type != 'call_nurses_from_text_blast_map' && $type != 'check_cover_nurses_availability')
                <div class="row pt-2">
                    <label for="escalated_rate"  class="col-md-5 text-right">Escalated Rate</label>
                    <input type="text" name="escalated_rate" id="escalated_rate" class="col-md-6 pl-1"/>
                </div>
                <div class="row pt-2">
                    <label for="content"  class="col-md-5 text-right">Content</label>
                    <input type="text" name="content" id="content" class="col-md-6 pl-1"/>
                </div>
                @endif
                @if($type == 'text_blast_sent_180_mile_radius')
                <div class="row pt-2">
                        <label for="radius"  class="col-md-4 text-right">Radius</label>
                        <input type="text" name="radius" id="radius" class="col-md-6 pl-1"/>
                </div>
                @endif
                <div id="candidate_save" class="row pt-2">
                    <div class="col-md-7 text-right">
                        @if($lastStepKey == $type)
                        <button type="button" onclick="setSchedule(this)" id="setScheduleBtn" class="btn btn-primary">Update</button>
                        @else
                        <button type="button" onclick="setSchedule(this)" id="setScheduleBtn" class="btn btn-primary">Update and Push to Next Step</button>
                        @endif
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

    $("#coverage_tracker_form").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "no_of_nurses_contacted": {
                required: true
            },
            @if($type != 'call_nurses_from_text_blast_map' && $type != 'check_cover_nurses_availability')
            "escalated_rate": {
                required: true
            },
            @endif
        },
        errorPlacement: function(error, element) {}
    });
});
</script>

<script type="text/javascript">
    var bucket_type = "{{str_replace(' ', '_', $type)}}";
    var next_step_key = "{{$nextStepKey}}";
function closeForm() {
    $('#clientcarecoveragetracker_form').modal('hide');
    $("#dataTable-clientcare-coverage-tracker-" + bucket_type).DataTable().ajax.reload();
    if ( $.fn.dataTable.isDataTable( "#dataTable-clientcare-coverage-tracker-" + next_step_key ) ) {
        $("#dataTable-clientcare-coverage-tracker-" + next_step_key).DataTable().ajax.reload();
    }
}
$("#coverage_tracker_form").on("keydown",function(e){
    var keyCode = e.which || e.keyCode;
    if(keyCode == 13) // enter key code
    {
        $(this).find('button').click();
    }

});
function setSchedule(button) {
    event.preventDefault();
    if ($("#coverage_tracker_form").valid() == false) {
        return false;
    }
    button.disabled = true;    
    $.ajax({
        url: "{{ route('dashboard.saveCoverageTrackerStep') }}",
        type: "POST",
        data: $("#coverage_tracker_form").serialize(),
        success: function(response) {
            button.disabled = false;
            if (response) {
                $("#successBody").html(response.message);
                if (response.success == 1) {
                        $("#coverageTrackersFormDiv").hide();
                        $("#coverageTrackerSuccessDiv").show();
                        $("#coverage_tracker_form")[0].reset();
                        $('#' + next_step_key + '-counter').html(response.next_step_counter);
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
    $('#clientcarecoveragetracker_form').on('shown.bs.modal', function() {
        $("#coverageTrackersFormDiv").show();
        $("#coverageTrackerSuccessDiv").hide();
    });

    $('#clientcarecoveragetracker_form').on('hidden.bs.modal', function() {
        type = $(this).find('form input[name="type"]').val()
        $('.select-all-' + type + '.td').prop('checked', false);
        $('#select-all-checkbox-' + type).prop('checked', false);
        $('#dataTable-clientcare-coverage-tracker-' + type + '_wrapper').find('.updateall').remove();
        $("#dataTable-clientcare-coverage-tracker-" + bucket_type).DataTable().ajax.reload();
        if ( $.fn.dataTable.isDataTable( "#dataTable-clientcare-coverage-tracker-" + next_step_key ) ) {
            $("#dataTable-clientcare-coverage-tracker-" + next_step_key).DataTable().ajax.reload();
        }
    });
});
</script>
