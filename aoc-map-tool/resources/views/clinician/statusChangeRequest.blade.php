<?php
    use App\Helpers\H;
    use App\Helpers\LD;

    $empStatus = H::getHelper('StatusChangeRequests')->getNewClinicianStatus();
    $src_reasonList = config('constant')['statusChangeRequestReason'];
?>
<x-modal>
    <x-slot name="id">statusChangeRequestFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
        <x-slot name="title">Status Change Request</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
        <div id="successBody"></div>
        <form method="POST" id="add-static-change-request-form-validate" action="{{ route('clinician.statusChangeRequestSave') }}">
            @csrf
            <input type="hidden" name="src_provider_id" id="src_provider_id" value=""/>
            <input type="hidden" name="src_branch_id" id="src_branch_id" value=""/>
            <div class="form-group row">
                <div class="col-md-6">
                    <label for="src_candidate_name" class="text-left">Clinician Name</label>
                    <input type="text" class="form-control" id="src_candidate_name" name="src_candidate_name" value="">
                </div>
                <div class="col-md-6">
                    <label for="src_candidate_id" class="text-left">Clinician ID</label>
                    <input type="text" class="form-control" id="src_candidate_id" name="src_candidate_id" value="" readonly>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label for="src_branch" class="text-left">Branch</label>
                    <input type="text" class="form-control" id="src_branch" name="src_branch" value="" readonly>
                </div>
                <div class="col-md-6">
                    <label for="src_current_status" class="text-left">Current Status</label>
                    <input type="text" class="form-control" id="src_current_status" name="src_current_status" value="" readonly>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label for="src_requested_status" class="text-left">Requested Status</label>
                    <select class="form-control" id="src_requested_status" name="src_requested_status">
                        <option value="">-- Select One --</option>
                        @foreach ($empStatus as $item)
                            <option value="{{ $item['value'] }}">{{ $item['text'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label for="src_reason" class="text-left">Reason</label>
                    <select class="form-control" id="src_reason" name="src_reason">
                        <option value="">-- Select One --</option>
                        @foreach ($src_reasonList as $item)
                            <option value="{{ $item }}">{{ $item }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label for="src_date_requested" class="text-left">Date Requested</label>
                    <input type="text" class="form-control" id="src_date_requested" name="src_date_requested" value="" autocomplete="off">
                </div>
                @if(LD::checkFeatureFlag('add-part-time-and-full-time-fild-in-status-change-request'))
                    <div class="col-md-6">
                        <label class="text-left">Part Time or Full Time?</label>
                        <div name="employmenttype">
                            <label class="radio-inline"><input type="radio" name="employment_type"
                            value="1"> Part Time</label>
                            <label class="radio-inline"><input type="radio" name="employment_type"
                            value="2"> Full Time</label>
                        </div>
                    </div>
                @endif
            </div>
            <div class="form-group row">
                <div class="col-md-12 text-center mt-2">
                    <button type="button" class="btn btn-primary mt-3" onclick="saveStatusChangeRequest(this)">Save</button>
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<style>
.ui-autocomplete {
    z-index: 999999;
}
#statusChangeRequestFormModal .form-group label {
    line-height: 20px;
}
#ui-datepicker-div {
    position: absolute !important;
    left: auto !important;
    top: -185px !important;
    box-shadow: 0px 0px 3px 0px;
}
</style>
<script type="text/javascript">
var flag = false;
function closeForm() {
    $('#statusChangeRequestFormModal').modal('hide');
}

function saveStatusChangeRequest(button) {
    $("#successBody").empty();
    if ($("#add-static-change-request-form-validate").valid() == false) {
        return false;
    }
    button.disabled = true;
    $.ajax({
        url: $("#add-static-change-request-form-validate").attr('action'),
        type: "POST",
        data: $("#add-static-change-request-form-validate").serialize(),
        success: function(response) {
            button.disabled = false;
            if (response.status) {
                $("#successBody").html(response.msg);
                $('<div class="row pt-2"><div class="col-md-7 text-right"><button type="button" data-dismiss="modal" aria-label="Close" class="btn btn-primary">Close</button></div></div>').insertAfter('#successBody');
                $('#add-static-change-request-form-validate')[0].reset();
                $('#add-static-change-request-form-validate').hide();
            } else {
                $("#successBody").html('<div class="alert alert-danger" role="alert">' + response.msg + '</div>');
            }
        },
    });
}
$(document).ready(function() {
    $("#src_candidate_name").autocomplete({
        source: function(request, response) {
            $.getJSON("{{ route('clinician.getCandidateName') }}", {
                term: $('#src_candidate_name').val()
            }, response);
        },
        minLength: 2,
        select: function(event, ui) {
            flag = true;
            $('#src_candidate_id').val(ui.item.ClinicianID);
            $('#src_branch').val(ui.item.FacilityName);
            $('#src_current_status').val(ui.item.EmploymentStatus);
            $('#src_provider_id').val(ui.item.id);
            $('#src_branch_id').val(ui.item.branchid);
        }
    });

    $('#src_candidate_name').keyup(function() {
        flag = false;
        if ($(this).val() == '') {
            $('#src_candidate_id').val('');
            $('#src_branch').val('');
            $('#src_current_status').val('');
            $('#src_provider_id').val('');
            $('#src_branch_id').val('');
        }
    });

    $('#src_candidate_name').blur(function() {
        if (flag == false) {
            $(this).val('');
            $('#src_candidate_id').val('');
            $('#src_branch').val('');
            $('#src_current_status').val('');
            $('#src_provider_id').val('');
            $('#src_branch_id').val('');
        }
    });

    $("#src_date_requested").datepicker({
        dateFormat: 'mm/dd/yy',
    }).bind('click', function() {
        $('#ui-datepicker-div').appendTo($('#src_date_requested').parent());
    });

    $.validator.addMethod("sameRequestStatus", function(value, element) {
        return $('#src_current_status').val() != $('#src_requested_status option[value='+value+']').text();
    }, "Requested Status is same as Current Status");

    $("#add-static-change-request-form-validate").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "src_candidate_name": {
                required: true
            },
            "src_requested_status": {
                required: true,
                sameRequestStatus: true
            },
            "src_reason": {
                required: true
            },
            "src_date_requested": {
                required: true
            },
            <?php if(LD::checkFeatureFlag('add-part-time-and-full-time-fild-in-status-change-request')) { ?>
                "employment_type": {
                    required: true
                },
            <?php } ?>
        },
        errorPlacement: function(error, element) {
        if (element.attr("name") == "employment_type") {
            error.insertAfter($("[name='employmenttype']"));
        } else {
            error.insertAfter(element);
        }}
    });

    $("#src_date_requested").datepicker('setDate', new Date());
});
</script>
<!-- /.container-fluid -->
