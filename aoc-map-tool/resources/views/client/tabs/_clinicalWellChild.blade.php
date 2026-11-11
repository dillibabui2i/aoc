<?php
    use Illuminate\Support\Facades\Storage;
    use App\Helpers\H;
?>
<style>
    .ui-autocomplete {
        z-index: 999999;
    }
</style>
@if($clientDetails->PrimarycarePhysicianID > 0)
<section class="clinicalwellchild-section" id="clinical-well-child-list">
    @include('client.tabs._clinicalWellChildList')
</section>
<section class="clinicalwellchild-section" id="clinicalwellchild-section">
    <form method="post" autocomplete="off" id="add_clinical_wellchild_form" class="scaffold-form" enctype="multipart/form-data">
        <input type="hidden" id="external_user_id" name="external_user_id" value="0">
        <input type="hidden" id="aoc_patient_id" name="aoc_patient_id" value="{{$clientDetails->id}}">
        <input type="hidden" id="primary_care_physician_id" name="primary_care_physician_id" value="{{$clientDetails->PrimarycarePhysicianID}}">
        @csrf
        <div class="col-md-12 flex-wrap alert alert-danger error-msg" style="display:none" id="saveClinicalWellChildError"></div>
        <div class="col-md-12 pt-1">
            <div class="row">
                <div class="col-md-7 pt-1">
                    <label for="primary_care_physician_id">Primary Care Physician</label>
                    <input type="input" class="form-control" id="primary_care_physician_name" name="primary_care_physician_name" value="{{(!empty($ReferringProviders))?$ReferringProviders->fullName:''}}" readonly>
                </div>
                <div class="col-md-7 pt-1">
                    <label for="last_visit">Well Child Visit Date</label>
                    <input type="input" class="form-control" id="last_visit" aria-describedby="emailHelp" name="last_visit" autocomplete="off">
                </div>
            </div>
            <div class="col-md-6 pt-1">
                <button type="button" onclick="saveClinicalWellChild(this);" class="btn btn-primary">Save</button> &nbsp; <a href="javascript:void(0);" onclick="cancelClinicalWellChild()">Cancel</a>
            </div>
        </div>
        
    </form>
</section>
@else
<section>
    No Referring Provider Data Available
</section>
@endif
<script type="text/javascript">

$(document).ready(function() {
    $('#last_visit').datepicker().bind('click', function() {
            $('#ui-datepicker-div').appendTo($(this).parent());
    });
    $('#next_visit').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });
    @if(!empty($mobileExistingUser))
    $('#clinical-well-child-list').show();
    $('#clinicalwellchild-section').hide();
    @endif
    
});
// Wait for the DOM to be ready
$(function() {
    $("#add_clinical_wellchild_form").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "next_visit": {
                required: true
            },
            "last_visit": {
                required: true
            }
        },
        errorPlacement: function(error, element) {}
    });
});
function saveClinicalWellChild(button) {
    $('.error-msg').hide();
    if ($("#add_clinical_wellchild_form").valid() == false) {
        return false;
    }
    button.disabled = true;
    $.ajax({
        url: "{{ route('saveClinicalWellChild') }}",
        type: "POST",
        data: $("#add_clinical_wellchild_form").serialize(), //only input
        success: function(response) {
            button.disabled = false;
            if (response) {
                if (response.success == 1) {
                    $('#add_clinical_wellchild_form')[0].reset();
                    getClinicalWellChild("{{$clientDetails->id}}",1);
                } else {
                    $('#saveClinicalWellChildError').html(response.message);
                    $('#saveClinicalWellChildError').show();
                    setTimeout(hideAlertBox, 5000);
                }
            }
        },
    });
}

function getClinicalWellChild(aoc_patient_id,action=0) {

    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('getClinicalWellChild') }}",
        type: "POST",
        data: {
            aoc_patient_id: aoc_patient_id,
            _token: _token
        },
        success: function(response) {
            $('#clinical-well-child-list').html(response);
            $('#clinical-well-child-list').show();
            $('#clinicalwellchild-section').hide();
            if(action ==1) {
                $('#saveWellChildSuccessMsg').show();
                $('#saveWellChildSuccessMsg').html('Well child visit has been added successfully');
                setTimeout(hideAlertBox, 5000);
            }
            if(action ==2) {
                $('#saveWellChildSuccessMsg').show();
                $('#saveWellChildSuccessMsg').html('Selected Well child visit(s) have been deleted successfully');
                setTimeout(hideAlertBox, 5000);
            }
        },
    });
}
function addClinicalWellChild() {
    $('#clinical-well-child-list').hide();
    $('#clinicalwellchild-section').show();
}
function cancelClinicalWellChild() {
    $('#clinical-well-child-list').show();
    $('#clinicalwellchild-section').hide();
}
function hideAlertBox() {
    $('.alert').hide();
}
getClinicalWellChild('{{$clientDetails->id}}');

function showHideButtonForMassText(isChecked) {
    $('#new_dataTable_wrapper div.updateall').remove();
    $('#new_dataTable_wrapper').empty();
    if (isChecked) {
        var html = '<div class="row"><div class="col-md-6 pl-1 text-left"><div style="display: inline-block;" class="dataTables_length updateall">';
        html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="getDeleteConfirmBox()" class="btn btn-primary">Delete</button>';
        html += '</div></div><div  class="col-md-6 pl-1"></div></div>';
        $('#new_dataTable_wrapper').prepend(html);
    }
}
function getDeleteConfirmBox() {
    modalConfirm("Are you sure you want to delete?",'Yes','No',function(res, modelDialog){
        if (res) {
            let _token = $('meta[name="csrf-token"]').attr('content');
            let ids = 0;
            var selectedIds = [];
            $('.select-all-candidate.td:checked').each(function() {
                selectedIds.push(this.value);
            });
            ids = selectedIds.join(",");
            $.ajax({
                url: "{{route('deleteClinicalWellChild')}}",
                type: "GET",
                data: {
                    ids: ids,
                    _token: _token
                },
                success: function(response) {
                    if(response.success ==1 ) {
                        getClinicalWellChild('{{$clientDetails->id}}',2);
                    } else {
                        $('#saveWellChildSuccessMsg').show();
                        $('#saveWellChildSuccessMsg').html(response.message);
                        setTimeout(hideAlertBox, 5000);
                    }
                },
            });
        }
        modelDialog.modal('hide');
    });
    return false;
}
</script>