<?php
    use Illuminate\Support\Facades\Storage;
    use App\Helpers\H;
?>
<style>
    .ui-autocomplete {
        z-index: 999999;
    }
</style>

<section class="mobileappuser-section" id="mobile-app-existing-user-section">
    @include('client.tabs._mobileAppUserList')
</section>
<section class="mobileappuser-section" id="mobileappuser-section">
    <form method="post" autocomplete="off" id="add_mobile_user_form" class="scaffold-form" enctype="multipart/form-data">
        <input type="hidden" id="external_user_id" name="external_user_id" value="0">
        <input type="hidden" id="aoc_patient_id" name="aoc_patient_id" value="{{$clientDetails->id}}">
        @csrf
        <div class="col-md-12 flex-wrap alert alert-danger error-msg" style="display:none" id="saveUserError"></div>
        <div class="col-md-12 flex-wrap alert alert-success error-msg" style="display:none" id="deleteUserSuccessMsg"></div>
        <div class="col-md-12 d-flex flex-wrap">
            <div class="custom-control custom-radio custom-control-inline">
                <input class="custom-control-input" type="radio" id="status1" name="status" value="new" checked />
                <label class="custom-control-label" for="status1">New</label>
            </div>
            <div class="custom-control custom-radio custom-control-inline">
                <input class="custom-control-input" type="radio" id="status2" name="status" value="current" />
                <label class="custom-control-label" for="status2">Current</label>
            </div>
        </div>
        <div class="col-md-12 pt-1">
            <div class="row" id="newUser">
                <div class="col-md-7 pt-1">
                    <label for="new_mobile_user_name">Name</label>
                    <input type="input" class="form-control" id="new_mobile_user_name" name="new_mobile_user_name" aria-describedby="emailHelp" name="new_mobile_user" autocomplete="off" maxlength="50">
                </div>
                <div class="col-md-7 pt-1">
                    <label for="new_mobile_user">Email</label>
                    <input type="input" class="form-control" id="new_mobile_user" name="new_mobile_user_email" aria-describedby="emailHelp" autocomplete="off" maxlength="50">
                </div>
                <div class="col-md-7 pt-1">
                    <label for="new_mobile_user_phone">Mobile</label>
                    <input type="input" class="form-control" id="new_mobile_user_phone" name="new_mobile_user_phone" aria-describedby="emailHelp" autocomplete="off" maxlength="15">
                </div>
            </div>
            <div class="row" id="currentUser" style="display: none;">
                <div class="col-md-6">
                    <label for="current_mobile_user">Email</label>
                    <input type="input" class="form-control" id="current_mobile_user" name="current_mobile_user_email" aria-describedby="emailHelp" autocomplete="off" maxlength="50">
                </div>
            </div>
            
            <div class="col-md-6 pt-1">
                <button type="button" onclick="saveMobileUser(this);" class="btn btn-primary">Save</button>
                @if(!empty($mobileExistingUser))
                    <button type="button" onclick="cancelMobileUser();" class="btn btn-primary">Cancel</button>
                @endif

            </div>
        </div>
        
    </form>
</section>
<script type="text/javascript">
$('input[name="status"]').change(function(){
    $('#newUser').hide();
    $('#currentUser').hide();
    if ($('input[name="status"]:checked').val() == 'new') {
        $('#newUser').show();
    } else {
        $('#currentUser').show();
    }
});

$(document).ready(function() {
    @if(!empty($mobileExistingUser))
    $('#mobile-app-existing-user-section').show();
    $('#mobileappuser-section').hide();
    @endif
    var flag = false;
    $("#current_mobile_user").autocomplete({
        source: function(request, response) {
            $.getJSON("{{ route('mobile.getExternalUserAutoCompleteList') }}", {
                term: $('#current_mobile_user').val()
            }, response);
        },
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            flag = true;
            $('#external_user_id').val(ui.item.id);
            console.log(ui.item.id);
        }
    });
    $('#current_mobile_user').keyup(function() {
        flag = false;
        if ($(this).val() == '') {
            $('#external_user_id').val(0);
        }
    });
    $('#current_mobile_user').blur(function() {
        if ($('input[name="status"]:checked').val() == 'current' && flag ==false) {
            $(this).val('');
            $('#external_user_id').val(0);
        }
    });
});
// Wait for the DOM to be ready
$(function() {
    $("#add_mobile_user_form").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "current_mobile_user_email": {
                required: function(element) {
                    return ($('input[name="status"]:checked').val() == 'current');
                }
            },
            "new_mobile_user_name": {
                required: function(element) {
                    return ($('input[name="status"]:checked').val() == 'new');
                }
            },
            "new_mobile_user_email": {
                required: true,
                email: true
            },
            "new_mobile_user_phone": {
                required: function(element) {
                    return ($('input[name="status"]:checked').val() == 'new');
                }
            },
        },
        errorPlacement: function(error, element) {}
    });
});
function saveMobileUser(button) {
    $('.error-msg').hide();
    if ($("#add_mobile_user_form").valid() == false) {
        return false;
    }
    button.disabled = true;
    $.ajax({
        url: "{{ route('saveMobileUser') }}",
        type: "POST",
        data: $("#add_mobile_user_form").serialize(), //only input
        success: function(response) {
            button.disabled = false;

            if (response) {
                if (response.success == 1) {
                    $('#add_mobile_user_form')[0].reset();
                    getMobileUser("{{$clientDetails->id}}");
                } else {
                    $('#saveUserError').html(response.message);
                    $('#saveUserError').show();
                }
            }
        },
    });
}

function removeMobileUser(aoc_patient_id,external_user_id) {
    $('.error-msg').hide();
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('removeMobileUser') }}",
        type: "POST",
        data: {
            aoc_patient_id: aoc_patient_id,
            external_user_id: external_user_id,
            _token: _token
        },
        success: function(response) {
            if (response) {
                if (response.success == 1) {
                    $('#deleteUserSuccessMsg').show();
                    $('#deleteUserSuccessMsg').html(response.message);
                    getMobileUser(aoc_patient_id);
                } else {
                    $('#deleteUserError').html(response.message);
                    $('#deleteUserError').show();
                }
            }
        },
    });
}
function getMobileUser(aoc_patient_id) {

    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('getMobileUser') }}",
        type: "POST",
        data: {
            aoc_patient_id: aoc_patient_id,
            _token: _token
        },
        success: function(response) {
            $('#mobile-app-existing-user-section').html(response);
            $('#mobile-app-existing-user-section').show();
            $('#mobileappuser-section').hide();
            $('#saveUserSuccessMsg').show();
            $('#saveUserSuccessMsg').html('Mobile app user created successfully');
        },
    });
}
function showAddMobileUserForm() {
    $('#mobile-app-existing-user-section').hide();
    $('#mobileappuser-section').show();
}
function cancelMobileUser() {
    $('#mobile-app-existing-user-section').show();
    $('#mobileappuser-section').hide();
}
</script>