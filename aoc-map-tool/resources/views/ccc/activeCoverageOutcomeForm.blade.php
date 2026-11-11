<?php
    use App\Helpers\H;
    use App\Helpers\LD;

?>
<x-modal>
    <x-slot name="id">activecoverageoutcomeform</x-slot>
    <x-slot name="size"></x-slot>
    <x-slot name="title">Document Active Coverage Days</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="getClinicianProfile('<?php echo $clinicianDetails->id ?>');">
                <i class="fa fa-user invert-color"></i>
            </a>
        </div>
    </x-slot>
    <x-slot name="body">
    <link href="{{ mix('css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
    <script src="{{ mix('js/bootstrap-datepicker.min.js') }}"></script>
        <form id="activeCoverageOutcomeForm" class="activeCoverageOutcomeForm">
            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
            <input type="hidden" name="trackerId" id="trackerId" value="{{ $trackerId }}">
            <input type="hidden" name="bucket" id="bucket" value="{{ Request::get("bucket") }}">
            <div class="tracker-details col-md-12">
            <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Clinician</h5>
                            <div class="detail-section">
                                <h6>
                                    {{$clinicianDetails->FirstName}} {{$clinicianDetails->LastName}}
                                </h6>
                                <div class="tags-cnt">
                                    <span class='tag'>
                                        {{ $clinicianDetails->DisciplineName }}
                                    </span>
                                </div>
                                <p class="card-text">
                                    {{$clinicianDetails->Phone}}
                                </p>
                                <p class="card-text">
                                    {{$clinicianDetails->Mobile}}
                                    <?php if(LD::checkFeatureFlag('display-twilio-error-on-profile-page')){ ?>
                                        <?php if(!empty($twilioErrorData)) { ?>
                                        &nbsp;<span data-toggle="tooltip" style="vertical-align:text-bottom"
                                        title="(Error:{{ $twilioErrorData->error_code }}) {{ $twilioErrorData->error_message }}">
                                            <i class="fa fa-times" style="font-size:16px;color:red"></i></span>
                                        <?php } ?>
                                    <?php } ?>
                                </p>
                                <p class="card-text">
                                    {{$clinicianDetails->External_Email}}
                                </p>
                                <ul class="d-flex att-cnt">
                                    <li>
                                        {{$clinicianDetails->Address1}}
                                    </li>
                                    <li>
                                        {{$clinicianDetails->Address2}}
                                    </li>
                                    <li>
                                        {{$clinicianDetails->City}}
                                    </li>
                                    <li>
                                       {{$clinicianDetails->State}}
                                    </li>
                                    <li>
                                        {{$clinicianDetails->Zip}}
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue"></h5>
                            <div class="detail-section"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12 pt-3">
                <div id="successBody"></div>
                <div class="col-md-6 p-0">
                    <label for="outcome">Outcome</label>
                    <select id="outcome" name="outcome" class="form-control" autocomplete="off">
                        <option value="">-- Select One --</option>
                        <option data-id="attempt_contact" value="Attempted to contact">Attempted to contact</option>
                        <option data-id="document_call" value="Document call">Document call</option>
                        @if(Request::get("bucket") == 'active_coverage')
                            <option
                                data-id="escalate_ccm"
                                value="Escalate to Client Care Manager"
                            >Escalate to Client Care Manager</option>
                        @endif
                        <option data-id="status_change_request" value="Status Change Request">Status Change Request</option>
                    </select>
                </div>
            </div>

            <div class="col-md-12 pt-3">
                <div class="col-md-12 p-0 attempt_contact hide">
                    @include('ccc.activecoverage.attempt_contact')
                </div>
                <div class="col-md-12 p-0 document_call hide">
                    @include('ccc.activecoverage.document_call')
                </div>
                <div class="col-md-12 p-0 escalate_ccm hide">
                    @include('ccc.activecoverage.escalate_ccm')
                </div>
                <div class="col-md-12 p-0 status_change_request hide">
                    @include('ccc.activecoverage.status_change_request')
                </div>

            </div>


            <div class="col-md-12 p-0 pt-4 hide btncontainer text-center">

                <button type="button" id="activecoverageOutcomeSaveBtn" onclick="saveActiveCoverageForm(this)" class="btn btn-primary">Save</button>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>


<script>
    $('#activeCoverageOutcomeForm #outcome').change(function() {

        let selectedOption = $(this).find('option:selected').data('id');
        $(".attempt_contact").addClass("hide");
        $(".document_call").addClass("hide");
        $(".escalate_ccm").addClass("hide");
        $(".status_change_request").addClass("hide");
        $('.btncontainer').addClass('hide');

        $('.' + selectedOption).removeClass('hide');
        if ($(this).find('option:selected').val() != '') {
            $('.btncontainer').removeClass('hide');
        }
    });

    function saveActiveCoverageForm(button) {
        $('#successBody').empty();
        if ($("#activeCoverageOutcomeForm").valid()) {
            button.disabled = true;
            let selectedOption = $("#activeCoverageOutcomeForm #outcome").find('option:selected').data('id');

            $.ajax({
                url: "{{ route('activecoverage.saveoutcomeform') }}",
                type: "POST",
                data: $("#activeCoverageOutcomeForm").serializeObject(),
                dataType: "json",
                success: function(response) {
                    button.disabled = false;
                    if (response.status == 1) {
                        $('#activecoverageoutcomeform').modal('hide');
                        $("#dataTable-ccc_active_coverage").DataTable().ajax.reload();
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

    $("#activeCoverageOutcomeForm").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "result":{
                    required:function(element){
                        return $("#activeCoverageOutcomeForm #outcome option:selected").attr('data-id') == "attempt_contact";
                    }
            },
            "activecoverage_comment":{required:function(element){
                        return $("#activeCoverageOutcomeForm #outcome option:selected").attr('data-id') == "attempt_contact";
                    }
            },
            "shift_type":{
                    required:function(element){
                        return $("#activeCoverageOutcomeForm #outcome option:selected").attr('data-id') == "document_call";
                    }
            },
            "days_available_hidden":{
                    required:function(element){
                        return $("#activeCoverageOutcomeForm #outcome option:selected").attr('data-id') == "document_call";
                    }
            },
            "escalate_comment":{
                    required:function(element){
                        return $("#activeCoverageOutcomeForm #outcome option:selected").attr('data-id') == "escalate_ccm";
                    }
            },
            "src_requested_status":{
                    required:function(element){
                        return $("#activeCoverageOutcomeForm #outcome option:selected").attr('data-id') == "status_change_request";
                    }
            },
            "src_reason":{
                    required:function(element){
                        return $("#activeCoverageOutcomeForm #outcome option:selected").attr('data-id') == "status_change_request";
                    }
            },
            "src_date_requested":{
                    required:function(element){
                        return $("#activeCoverageOutcomeForm #outcome option:selected").attr('data-id') == "status_change_request";
                    }
            },
            "employment_type":{
                    required:function(element){
                        return $("#activeCoverageOutcomeForm #outcome option:selected").attr('data-id') == "status_change_request";
                    }
            },
        },
        errorPlacement: function(error, element) {
            error.appendTo(element.parent());
        }
    });
</script>

<style>
    .invert-color {
        background: #2f5baa;
        color: #fff;
        padding:3px;
    }
</style>
