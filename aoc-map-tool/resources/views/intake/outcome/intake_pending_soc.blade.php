<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $clinicalCareManager = H::getHelper("ConstantHelper")->getClinicalCareManager();
    $pendingReason = H::getHelper('ConstantHelper')->getReadyToStartReason();
    $nonAdmitReasons = H::getHelper("ConstantHelper")->getNonAdmitReason();

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
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/tribute.js') }}"></script>
<link href="{{ mix('css/tribute.css') }}" rel="stylesheet">
<style>
    div.editable {
        width: 300px;
        height: 200px;
        border: 1px solid #ccc;
        padding: 5px;
    }
    #intakeStaffForm_comments2 {
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
    #intakeStaffForm_comments2:focus {
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
        <option data-id="readytostartstatusfield" value="Ready to Start">Ready to Start</option>
        <option data-id="provideupdatefield" value="Provide an update">Provide an update</option>
        <option data-id="sendcalendarinvitefield" value="Send Calendar Invite">Send Calendar Invite</option>
        <option data-id="nonadmitfield" value="Non Admit">Non Admit</option>
    </select>
</div>
<div class="outcome_container">
    <div class="col-md-12 p-0  readytostartstatusfield hide">
        <div class="form-group row">
            <div class="col-md-6">
                <label class="w-100" for="intakeStaffForm_readytostartstatus">Ready to Start</label>
                <select
                    class="form-control"
                    id="intakeStaffForm_readytostartstatus"
                    name="intakeStaffForm_readytostartstatus"
                    disabled>
                    <option value=""> --- Select --- </option>
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
            <div class="col-md-6 intakeStaffForm_reason hide">
                <label  class="w-100" for="intakeStaffForm_reason">Reasons</label>
                <select
                    class="form-control"
                    id="intakeStaffForm_reason"
                    name="intakeStaffForm_reason"
                    disabled>
                    <option value=""> --- Select --- </option>
                    @foreach($pendingReason as $v)
                        <option value="{{$v['value']}}">
                    {{$v['text']}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        @if(LD::checkFeatureFlag('add-comment-box-in-intake-outcome-form'))
            <div class="form-group row newComment hide">
                <div class="col-md-6">
                    <label  class="w-100" for="newComment">Comment</label>
                    <textarea name="newComment" id="newComment" class="form-control"></textarea>
                </div>
            </div>
        @endif
    </div>
    <div class="col-md-12 p-0  provideupdatefield hide">
        <div class="form-group row">
            <div class="col-md-12">
                <label for="intakeStaffForm_comments2">Comments</label>
                <div
                    id="intakeStaffForm_comments2"
                    name="intakeStaffForm_comments2"
                    contenteditable="true"
                ></div>

            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label  class="w-100" for="intakeStaffForm_followupdate">Follow Up Date</label>
                <input
                    type="text"
                    class="form-control"
                    id="intakeStaffForm_followupdate"
                    name="intakeStaffForm_followupdate"
                    value=""
                    disabled/>
            </div>
            <div class="col-md-6">
                <label  class="w-100" for="intakeStaffForm_contact">Contact</label>
                <input
                    type="text"
                    class="form-control"
                    id="intakeStaffForm_contact"
                    name="intakeStaffForm_contact"
                    value=""
                    disabled/>
            </div>
        </div>
    </div>
    <div class="col-md-12 p-0  sendcalendarinvitefield hide">
        <div class="form-group row">
            <div class="col-md-6">
                <label class="w-100" for="intakeStaffForm_intakeNurse">Intake Nurse</label>
                <select
                    class="form-control"
                    id="intakeStaffForm_intakeNurse"
                    name="intakeStaffForm_intakeNurse"
                    disabled>
                    <option value=""> --- Select --- </option>
                    @foreach($clinicalCareManager as $v)
                        <option value="{{$v['value']}}">
                    {{$v['text']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label  class="w-100" for="intakeStaffForm_admissionVisitDate">Admission Visit Date</label>
                <input
                    type="text"
                    class="form-control"
                    id="intakeStaffForm_admissionVisitDate"
                    name="intakeStaffForm_admissionVisitDate"
                    value=""
                    disabled />
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
            </div>
            <div class="col-md-3">
                <label  class="w-100" for="intakeStaffForm_start_time">Start Time</label>
                <input
                    type="input"
                    placeholder="Start Time"
                    class="form-control"
                    id="intakeStaffForm_start_time"
                    name="intakeStaffForm_start_time"
                    value=""
                    disabled />
            </div>
            <div class="col-md-3">
                <label  class="w-100" for="intakeStaffForm_end_time">End Time</label>
                <input
                    type="input"
                    placeholder="End Time"
                    class="form-control"
                    id="intakeStaffForm_end_time"
                    name="intakeStaffForm_end_time"
                    value=""
                    disabled >
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label  class="w-100" for="intakeStaffForm_subject">Subject</label>
                <input
                    type="text"
                    class="form-control"
                    id="intakeStaffForm_subject"
                    name="intakeStaffForm_subject"
                    value=""
                    disabled />
            </div>
            <div class="col-md-6">
                <label  class="w-100" for="intakeStaffForm_location">Location</label>
                <input
                    type="text"
                    class="form-control"
                    id="intakeStaffForm_location"
                    name="intakeStaffForm_location"
                    value=""
                    disabled />
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-12">
                <label for="intakeStaffForm_description">Description</label>
                <textarea
                    name="intakeStaffForm_description"
                    id="intakeStaffForm_description"
                    class="form-control"
                    disabled></textarea>
            </div>
        </div>
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
@include('intake.sendIntakeSms',['from_page' => 'intake_pending_soc'])
<div class="col-md-12 p-0 pt-4 hide btncontainer text-center">
    <button type="button" id="intakeOutcomeSaveBtn" onclick="saveIntakeOutcome(this)" class="btn btn-primary">Save</button>
    @if(LD::checkFeatureFlag('add-copy-text-function-on-provide-an-update-outcome'))
        <a class="copy-form-btn provideupdatefield hide"  onclick="copyFormToClipboard();">Copy Text from Form</a>
    @endif
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
            "intakeStaffForm_intakeNurse":{
                required: true
            },
            "intakeStaffForm_admissionVisitDate":{
                required: true
            },
            "intakeStaffForm_description":{
                required: true,
            },
            "intakeStaffForm_location":{
                required: true
            },
            "intakeStaffForm_subject":{
                required: true
            },
            "intakeStaffForm_end_time":{
                required: true
            },
            "intakeStaffForm_start_time":{
                required: true
            },
            "intakeStaffForm_readytostartstatus":{
                required: true
            },
            "intakeStaffForm_reason":{
                required: {
                    depends: function(element) {
                        return $("#intakeStaffForm_readytostartstatus option:selected").val() == 0;
                    }
                }
            },
            "intakeStaffForm_comments":{
                required: true
            },
            "intakeStaffForm_comments2":{
                required: true
            },
            "intakeStaffForm_contact":{
                required: {
                    depends: function(element) {
                        return $("#intakeStaffForm_followupdate").val() != '';
                    }
                }
            },
            "intakeStaffForm_nonadmitreasons":{
                required: true
            },
            "intakeStaffForm_contrbluncontrbl":{
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
        $('.sendcalendarinvitefield').addClass('hide');
        $('.btncontainer').addClass('hide');
        $('.' + selectedOption).removeClass('hide');
        $('.' + selectedOption +
            ' input , .' + selectedOption + ' select, .' +
            selectedOption + ' textarea'
        ).removeAttr('disabled');
        if ($(this).find('option:selected').val() != '') {
            $('.btncontainer').removeClass('hide');
        }

        $('.smscareteam').addClass('hide');
        $('.smscareteam_checkbox').prop('checked',false);
        $('.careTeamDropdown_div').hide();
        $('.careTeamDropdown').multiselect('deselectAll', false);
        $('.careTeamDropdown').multiselect('refresh');
        if (selectedOption === 'provideupdatefield') {
            $('.smscareteam').removeClass('hide');
        }
    });

    $('#ui-datepicker-div').remove();
    $('#intakeStaffForm_admissionVisitDate').datepicker({
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
    $('#intakeStaffForm_start_time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '00:00:00',
        'maxTime': '23:59:00'
    });
    $('#intakeStaffForm_end_time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '00:00:00',
        'maxTime': '23:59:00'
    });

    $('#intakeStaffForm_followupdate').datepicker({
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

    $('#intakeStaffForm_readytostartstatus').change(function() {
        let selectedOption = $(this).find('option:selected').val();
        if (selectedOption == '0') {
            $('.intakeStaffForm_reason').removeClass('hide');
        } else {
            $('.intakeStaffForm_reason').addClass('hide');
        }
    });

    @if(LD::checkFeatureFlag('add-comment-box-in-intake-outcome-form'))
        $('#intakeStaffForm_readytostartstatus').change(function() {
            let selectedOption = $(this).find('option:selected').val();
            if (selectedOption == '0') {
                $('.newComment').removeClass('hide');
                addNewFieldVelidation();
            } else {
                $('.newComment').addClass('hide');
                removeNewFieldVelidation();
            }
        });
    @endif

    function saveIntakeOutcome(button) {
        $('#successBody').empty();
        if ($("#intakeStaffForm").valid()) {
            button.disabled = true;
            let selectedOption = $("#intakeStaffForm #outcome").find('option:selected').data('id');
            if(selectedOption=='provideupdatefield') {
                var html = $('#intakeStaffForm_comments2').html();
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
            } else {
                $("#intakeStaffForm #mention").val("");
                $("#intakeStaffForm #notes_text").val("");
            }

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

    tribute.attach($('#intakeStaffForm_comments2'));


    @if(LD::checkFeatureFlag('add-copy-text-function-on-provide-an-update-outcome'))
        function copyFormToClipboard(){
            var patientName = '<?php echo addslashes($clientDetails->Patient_FirstName.' '. $clientDetails->Patient_LastName); ?>';
            var patientAccount = '<?php echo $clientDetails->PatientAccount ?>';
            var str = '';
            str += 'Client: ' + patientName + '\n';
            str += 'Client ID: ' + patientAccount + '\n';
            str += 'Comments: ' + $('#intakeStaffForm_comments2').text() + '\n';
            str += 'Follow Up Date: ' + $('#intakeStaffForm_followupdate').val() + '\n';
            str += 'Contact: ' + $('#intakeStaffForm_contact').val() + '\n';

            navigator.clipboard.writeText(str).then(function() {
                console.log('Copied!');
            }).catch(function(err) {
                console.error('Could not copy text: ', err);
            });
        }
    @endif()

    function addNewFieldVelidation(){
       $( "#newComment" ).rules( "add", {
            required: true,
        });
    }

    function removeNewFieldVelidation(){
        $( "#newComment" ).rules( "remove" );
    }
</script>
