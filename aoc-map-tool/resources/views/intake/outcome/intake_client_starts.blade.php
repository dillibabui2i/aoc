<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $nonAdmitReasons = H::getHelper("ConstantHelper")->getNonAdmitReason();
?>
<div class="col-md-6 p-0">
    <label for="outcome">Outcome</label>
    <select id="outcome" name="outcome" class="form-control" autocomplete="off">
        <option value="">-- Select One --</option>
        <option data-id="goalsocdatefield" value="Change Goal SOC Date">Change Goal SOC Date</option>
        <option data-id="nonadmitfield" value="Non Admit">Non Admit</option>
    </select>
</div>
<div class="outcome_container">
    <div class="col-md-12 p-0 goalsocdatefield hide">
        <div class="form-group row">
            <div class="col-md-6">
                <label  class="w-100" for="intakeStaffForm_goalsocdate">Goal SOC Date</label>
                <input
                    type="text"
                    class="form-control"
                    id="intakeStaffForm_goalsocdate"
                    name="intakeStaffForm_goalsocdate"
                    value=""
                    disabled/>
            </div>
        </div>
        @if(LD::checkFeatureFlag('add-comment-box-in-intake-outcome-form'))
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-100" for="newComment">Comment</label>
                    <textarea name="newComment" id="newComment" class="form-control"></textarea>
                </div>
            </div>
        @endif
    </div>
</div>
<div class="col-md-12 p-0 nonadmitfield hide">
    <div class="form-group row">
        <div class="col-md-6">
            <label class="w-100" for="intakeStaffForm_nonadmitreasons">Non Admit Reasons</label>
            <select
                class="form-control"
                id="intakeStaffForm_nonadmitreasons"
                name="intakeStaffForm_nonadmitreasons"
                disabled>
                <option value=""> --- Select --- </option>
                @foreach($nonAdmitReasons as $v)
                    <option value="{{$v['value']}}">
                {{$v['text']}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
        <label class="w-100" for="intakeStaffForm_contrbluncontrbl">Controllable Vs Uncontrollable</label>
            <select
                class="form-control"
                id="intakeStaffForm_contrbluncontrbl"
                name="intakeStaffForm_contrbluncontrbl"
                disabled>
                <option value=""> --- Select --- </option>
                <option value="Controllable">Controllable</option>
                <option value="Uncontrollable">Uncontrollable</option>
            </select>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-md-6 form-check ml-3">
            <input
                type="checkbox"
                class="form-check-input"
                id="intakeStaffForm_excludefromreporting"
                name="intakeStaffForm_excludefromreporting"
                value="1"
                disabled />
            <label class="form-check-label w-100" for="intakeStaffForm_excludefromreporting">Lead: Exclude from Reporting</label>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-md-12">
            <label for="intakeStaffForm_comments">Comments</label>
            <textarea
                name="intakeStaffForm_comments"
                id="intakeStaffForm_comments"
                class="form-control"
                disabled></textarea>
        </div>    
    </div>
</div>
<div class="col-md-12 p-0 pt-4 hide btncontainer text-center">
    <button type="button" id="intakeOutcomeSaveBtn" onclick="saveIntakeOutcome(this)" class="btn btn-primary">Save</button>
</div>
<script>
    $("#intakeStaffForm").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "intakeStaffForm_goalsocdate":{
                required: true
            },            
            "intakeStaffForm_nonadmitreasons":{
                required: true
            },
            "intakeStaffForm_contrbluncontrbl":{
                required: true
            },
            "intakeStaffForm_comments":{
                required: true
            },
        },
        errorPlacement: function(error, element) {
            error.appendTo(element.parent());
        }
    });
    $('#intakeStaffForm #outcome').change(function() {
        $('.outcome_container select, .outcome_container input, .outcome_container textarea').attr('disabled', 'disabled');
        let selectedOption = $(this).find('option:selected').data('id');
        $('.readytostartstatusfield').addClass('hide');
        $('.provideupdatefield').addClass('hide');
        $('.nonadmitfield').addClass('hide');
        $('.btncontainer').addClass('hide');
        $('.' + selectedOption).removeClass('hide');
        $('.' + selectedOption +
            ' input , .' + selectedOption + ' select, .' +
            selectedOption + ' textarea'
        ).removeAttr('disabled');
        if ($(this).find('option:selected').val() != '') {
            $('.btncontainer').removeClass('hide');
        }
        @if(LD::checkFeatureFlag('add-comment-box-in-intake-outcome-form'))
            if(selectedOption == 'goalsocdatefield'){
                addNewFieldVelidation();
            }else{
                removeNewFieldVelidation();
            }
        @endif
    });

    $('#ui-datepicker-div').remove();
    $('#intakeStaffForm_goalsocdate').datepicker({
        beforeShow: function(input, inst) {
            var calendar = inst.dpDiv;
            setTimeout(function() {
                calendar.position({
                    my: 'left bottom',
                    at: 'left top',
                    collision: 'none',
                    of: input
                });
            }, 1);
        }
    });

    function saveIntakeOutcome(button) {
        $('#successBody').empty();
        if ($("#intakeStaffForm").valid()) {
            button.disabled = true;
            $.ajax({
                url: "{{ route('intake.saveOutcome') }}",
                type: "POST",
                data: $("#intakeStaffForm").serialize(),
                dataType: "json",
                success: function(response) {
                    button.disabled = false;
                    if (response.status == 1) {
                        $('#intakeStaffOutcomeForm').modal('hide');
                        $("#dataTable-staffTransfers-"+response.bucket).DataTable().ajax.reload();
                        if ($.fn.DataTable.isDataTable('#dataTable')) {
                            $("#dataTable").DataTable().ajax.reload();
                        }
                    } else {
                        $('#successBody').html('<div class="alert alert-danger" role="alert">' + response.errMsg + '</div>');
                    }
                },
            });
        }
    }

    function addNewFieldVelidation(){
       $( "#newComment" ).rules( "add", {
            required: true,
        });
    }

    function removeNewFieldVelidation(){
        $( "#newComment" ).rules( "remove" );
    }
</script>