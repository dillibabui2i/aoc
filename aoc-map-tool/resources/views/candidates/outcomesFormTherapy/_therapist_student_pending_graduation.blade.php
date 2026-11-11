<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">

<style>
    #candidateShowNextStepForm .modal-content {
        min-height: 900px !important;
    }
</style>
<div class="row pt-5 schedule-clinical-interview-section">
    <div class="col-md-9">
        <label for="clinical-manager-for-interview">Follow-up notes:</label>
        <textarea class="form-control required-field" rows="5" name="outcomeNotes" ></textarea>
    </div>
    <div class="col-md-3">
        <label for="graduation-date">Graduation date</label>
        <input type="text" class="form-control required-field" autocomplete="off" id="graduation-date" name="GraduationDate" value="">
        <br>
        <label for="follow-up-date">Next follow-up date</label>
        <input type="text" class="form-control required-field" autocomplete="off" id="follow-up-date" name="FollowUpDateTime" value="">
    </div>
</div>

<div class="row pt-5">
    <div class="col-md-12">
        <p class="msg-error"></p>
    </div>
</div>
<script>
    $.validator.addClassRules('required-field', {
        required: true,
        minlength: 1
    });

    $('#jobViteOutcomeForm').validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            if (validator.numberOfInvalids()) {
                validator.errorList[0].element.focus();
            }
        },
        errorPlacement: function(error, element) {}
    });
    $(function(){

        $('#graduation-date').datepicker().bind('click', function () {
            $('#ui-datepicker-div').appendTo($('#graduation-date').parent());
        });

        $('#follow-up-date').datepicker().bind('click', function () {
            $('#ui-datepicker-div').appendTo($('#follow-up-date').parent());
        });
    });
</script>
