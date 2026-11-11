<?php
    use App\Helpers\H;
    use App\Helpers\Ld;
?>
<form method="post" autocomplete="off" id="eligibility_form" class="scaffold-form" enctype="multipart/form-data">
    <input type="hidden" id="aoc_patient_id" name="aoc_patient_id" value="{{$clientDetails->id}}">
    @csrf
    <div class="col-md-12 pt-1">
        @if(LD::checkFeatureFlag('add-medicaid-in-payer-source-eligibility-tab'))
            @if(!empty($clientDetails->MedicaidNumber))
                <span>Medicaid#: {{$clientDetails->MedicaidNumber}}</span>
            @endif
        @endif
        <div class="row">
            <div class="col-md-6 pt-2 pb-2">
                <label for="medicaid_type">Medicaid Type</label>
                <select multiple  class="selectpicker" data-placeholder="Select Type" data-live-search="true" id="medicaid_type" name="medicaid_type[]">
                    @foreach (config('constant')['medicaidType'] as $item)
                        <option value="{{$item}}" {{(in_array($item, explode(",", $clientDetails->medicaid_type))) ? 'selected' : ''}}>{{$item}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="row showmedicaidType pt-2 pb-2" style="display: none;">
            <div class="col-md-6">
                <label for="eligibility_end_date">Eligibility End Date</label>
                <input type="input" class="form-control" id="eligibility_end_date" name="eligibility_end_date" autocomplete="off" value="{{H::getFormatedDate($clientDetails->eligibility_end_date)}}">
            </div>
        </div>
        <div class="row showmedicaidType pt-2 pb-2" style="display: none;">
            <div class="col-md-6">
                <label for="time_renew_date">Time to Renew Date</label>
                <input type="input" class="form-control" id="time_renew_date" name="time_renew_date" autocomplete="off" value="{{H::getFormatedDate($clientDetails->time_renew_date)}}">
            </div>
        </div>
        <div class="row">        
            <div class="col-md-6 pt-2 pb-2">
                <button type="button" onclick="saveEligibility(this);" class="btn btn-primary">Save</button>
            </div>
        </div>
    </div>
    
</form>
<script type="text/javascript">
$(document).ready(function() {
    $('#medicaid_type').multiselect({
        includeSelectAllOption: true
    });
    $('#medicaid_type').change(function(){
        let medicaidType = $(this).val().join(",");
        if (medicaidType.includes('Traditional Medicaid (fee for service)') || medicaidType.includes('STAR') || medicaidType.includes('STAR Kids') || medicaidType.includes('STAR+PLUS') || medicaidType.includes('STAR Health')) {
            $('.showmedicaidType').show();
        } else {
            $('.showmedicaidType').hide();
        }
    });
    $('#medicaid_type').change();
    $("#eligibility_end_date").datepicker({
        dateFormat: 'mm/dd/yy',
        beforeShow: function (input, inst) {
            setTimeout(function () {
                $('#ui-datepicker-div').appendTo($(input).parent());
                inst.dpDiv.css({
                    top: 'initial',
                    left: 'initial',
                    position: 'absolute'
                });
            }, 0);
        }
    });
    $("#time_renew_date").datepicker({
        dateFormat: 'mm/dd/yy',
        beforeShow: function (input, inst) {
            setTimeout(function () {
                $('#ui-datepicker-div').appendTo($(input).parent());
                inst.dpDiv.css({
                    top: 'initial',
                    left: 'initial',
                    position: 'absolute'
                });
            }, 0);
        }
    });
});
// Wait for the DOM to be ready
$(function() {
    $("#eligibility_form").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "medicaid_type[]": {
                required: true
            }
        },
        errorPlacement: function(error, element) {}
    });
});
function saveEligibility(button) {
    $('.error-msg').hide();
    if ($("#eligibility_form").valid() == false) {
        return false;
    }
    button.disabled = true;
    $.ajax({
        url: "{{ route('client.saveEligibility') }}",
        type: "POST",
        data: $("#eligibility_form").serialize(),
        success: function(response) {
            button.disabled = false;

            if (response) {
                if (response.success == 0) {
                    $('#eligibility_form')[0].reset();
                } else {
                    reloadClientHistory('{{$clientDetails->id}}', 'clienthistory', '.activity-log');
                }
            }
        },
    });
}
</script>