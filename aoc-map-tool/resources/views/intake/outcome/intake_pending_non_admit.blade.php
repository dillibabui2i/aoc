<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $nonAdmitReasons = H::getHelper("ConstantHelper")->getNonAdmitReason();
    $getclientdata = DB::table('mssql_patients')->where('id', $clientDetails->id)->first();

    if(LD::checkFeatureFlag('careteam-tagging-to-notes')) {
        $users = H::getHelper("ConstantHelper")->getUsersWithCareTeam();
    } else {
        $users = H::getHelper("ConstantHelper")->getUsers();
    }
    $user = [];
    $cnt =0 ;
    foreach($users as $u) {
        $user[$cnt]["key"] =$u['name'];
        $user[$cnt]["value"] = $u['name'];
        $user[$cnt]["email"] = $u['email'];
        $user[$cnt]["id"] = $u['id'];
        $cnt++;
    }

?>
<script src="{{ mix('js/tribute.js') }}"></script>
<link href="{{ mix('css/tribute.css') }}" rel="stylesheet">
<style>
    div.editable {
        width: 300px;
        height: 200px;
        border: 1px solid #ccc;
        padding: 5px;
    }
    #intakeStaffForm_intake_status_comments {
        background-clip: padding-box;
        background-color: #fff;
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        color: #495057;
        display: block;
        font-size: .9rem;
        font-weight: 400;
        line-height: 1.6;
        padding: 0.375rem 0.75rem;
        transition: border-color .15s ease-in-out,box-shadow .15s ease-in-out;
        width: 100%;
        height: 150px;
    }
    #intakeStaffForm_intake_status_comments:focus {
        background-color: #fff;
        border-color: #a1cbef;
        box-shadow: 0 0 0 0.2rem rgb(52 144 220 / 25%);
        color: #495057;
        outline: 0;
    }
</style>
<input type="hidden" name="mention" id="mention" value="" />
<input type="hidden" name="notes_text" id="notes_text" value="" />
<div class="col-md-6 p-0">
    <label for="outcome">Outcome</label>
    <select id="outcome" name="outcome" class="form-control" autocomplete="off">
        <option value="">-- Select One --</option>
        @if(H::canPermissions('can_approve_non_admit'))
            <option data-id="approveNonadmitfield" value="Approve Non Admit">Approve Non Admit</option>
        @endif
        <option data-id="intakeStatus" value="Intake Status">Change Intake Status</option>
    </select>
</div>
<div class="outcome_container">
    <div class="col-md-12 p-0 intakeStatus hide">
        <div class="form-group row">
            <div class="col-md-6 pr-0">
                <label  class="w-100" for="new_intake_status">Change Intake Status</label>
                <select name="new_intake_status" id="new_intake_status" class="form-control">
                    <option value="">Select Status</option>
                    <option value="Admitted">Admitted (Pending SOC or Client Starts based on Goal SOC Date)</option>
                    <option value="Scheduled">Intake Scheduled</option>
                    <option value="Pending">Prospective Clients</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6 pr-0">
                <label for="intakeStaffForm_intake_status_comments">Comments</label>
                <div
                    id="intakeStaffForm_intake_status_comments"
                    name="intakeStaffForm_intake_status_comments"
                    contenteditable="true"
                ></div>
            </div>
        </div>
    </div>
</div>
<div class="col-md-12 p-0 approveNonadmitfield hide">
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
            <label class="form-check-label w-100"
            for="intakeStaffForm_excludefromreporting">Lead: Exclude from reporting</label>
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
<div class="col-md-12 p-0 pt-4 btncontainer text-center">
    <button type="button" id="intakeOutcomeSaveBtn"
    onclick="saveIntakeOutcome(this)" class="btn btn-primary">Save</button>
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
            "intakeStaffForm_nonadmitreasons":{
                required: true
            },
            "intakeStaffForm_contrbluncontrbl":{
                required: true
            },
            "intakeStaffForm_comments":{
                required: true
            },
            "new_intake_status":{
                required: true
            },
            "intakeStaffForm_intake_status_comments": {
                required: true
            }
        },
        errorPlacement: function(error, element) {
            error.appendTo(element.parent());
        }
    });
    $('#intakeStaffForm #outcome').change(function() {
        let selectedOption = $(this).find('option:selected').data('id');
        if (selectedOption === 'approveNonadmitfield') {
            $(".approveNonadmitfield").removeClass('hide');
            $(".intakeStatus").addClass('hide');
        } else if (selectedOption === 'intakeStatus') {
            $(".approveNonadmitfield").addClass('hide');
            $(".intakeStatus").removeClass('hide');
        }
        $('.btncontainer').addClass('hide');
        if ($(this).find('option:selected').val() != '') {
            $('.btncontainer').removeClass('hide');
        }
    });

    function saveIntakeOutcome(button) {
        $('#successBody').empty();
        if ($("#intakeStaffForm").valid()) {
            button.disabled = true;

            var html = $('#intakeStaffForm_intake_status_comments').html();
            var $obj = $("<div>"+html+"</div>").find("a");
            var idArray = [];
            var cntr = 0;
            $obj.each(function(i,item){
                idArray[cntr] = $(item).attr("data-id");
                cntr++;
            });
            var notes = $("<div>"+html+"</div>").text();

            var ids = idArray.join(",");
            $("#intakeStaffForm #mention").val(ids);
            $("#intakeStaffForm #notes_text").val(notes);

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
                        $('#successBody').html('<div class="alert alert-danger"role="alert">'
                        + response.errMsg + '</div>');
                    }
                },
            });
        }
    }

    var client_data = <?php echo json_encode($getclientdata); ?>;
    $('#intakeStaffForm #outcome').change(function() {
        $('#intakeStaffForm_nonadmitreasons').val(client_data.intake_nonadmit_reason).prop('disabled', false);
        $('#intakeStaffForm_contrbluncontrbl').val(client_data.intake_controllable).prop('disabled', false);
        $('#intakeStaffForm_excludefromreporting').prop('checked',
        client_data.intake_lead_exclude == 1).prop('disabled', false);
        $('#intakeStaffForm_comments').val(client_data.intake_comment).prop('disabled', false);
        $('#intakeStaffForm_intake_status_comments').prop('disabled', false);
    });

    var jsonFields = JSON.parse('<?php echo addslashes(json_encode($user))?>');
    var tribute = new Tribute({
        values: jsonFields,
        selectTemplate: function(item) {
          if (typeof item === "undefined") return null;
            return (
              '<a class="tag-container" data-id="'+item.original.id+'" href="#" target="_blank" title="' +
              item.original.email +
              '">@' +
              item.original.value +
              "</a>"
            );
        }
    });

    tribute.attach($('#intakeStaffForm_intake_status_comments'));
</script>
