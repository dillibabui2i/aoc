<?php
use App\Helpers\H;
use App\User;
use App\Helpers\LD;

$cccList = User::where('status',1)->orderBy('name')->get()->toArray();
$scheduleTime = !empty($request['scheduleTimeEditable']) ? explode(' - ', $request['scheduleTimeEditable']) : '';
$daysAvailableArray = [];
$daysAvailableString = '';
 if(LD::checkFeatureFlag('coverage-clinician-map-prn-process') ){
    if(isset($request['bucket']) && $request['bucket']=='map_coverageclinician') {
        if(isset($request['daysAvailable']) && !empty($request['daysAvailable'])){
                $daysAvailableString = implode(",",$request['daysAvailable']);
        }
 }  }

?>

<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<x-modal>
    <x-slot name="id">addCoverageShiftTrackerFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">Add to Coverage Tracker</x-slot>
    <x-slot name="class">addSuppliesRequest</x-slot>
    <x-slot name="body">
        <div class="modal-body" id="scheduleNextSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="modal-body" id="scheduleNextFormDiv">
            <form id="addCoverageShiftTrackerForm" class="addCoverageShiftTrackerForm">
                <div role="alert" class="alert alert-danger mt-3 clinicianValidationMsg hide">You've assigned an RN to a non RN shift.</div>
                <div
                    role="alert"
                    class="alert alert-danger mt-3 clinicianCCRValidationMsg hide">
                        Clinician has not worked with client in last 30 days.&nbsp;&nbsp;
                        Clinician will need CCR for coverage shift with client.
                </div>
                <input type="hidden" id="aoc_patient_id" name="aoc_patient_id" value="{{(isset($request['source']) && $request['source'] != 'dashboard_buckets_add_form') ? $request['patientId'] : ''}}">
                <?php if(LD::checkFeatureFlag('coverage-clinician-map-prn-process') ){
                        if(isset($request['bucket']) && $request['bucket']=='map_coverageclinician') {  ?>
                            <input type="hidden" id="aoc_provider_id" name="aoc_provider_id" value="{{$request['aocproviderid']}}">

                <?php } else { ?>
                        <input type="hidden" id="aoc_provider_id" name="aoc_provider_id" value="0">

               <?php } } else { ?>
                <input type="hidden" id="aoc_provider_id" name="aoc_provider_id" value="0">

                <?php } ?>

                <input type="hidden" id="source" name="source" value="{{$request['source']}}">
                <input type="hidden" id="filled_by" name="filled_by" value="{{Auth::user()->id}}">
                <input type="hidden" class="form-control" id="selectedCoverageRows" name="selectedCoverageRows" value="{{(isset($request['source']) && $request['source'] != 'dashboard_buckets_add_form') ? implode(",",$request['mapIds']) : ''}}"/>

                <?php if(LD::checkFeatureFlag('coverage-clinician-map-prn-process') ){
                        if(isset($request['bucket']) && $request['bucket']=='map_coverageclinician') {  ?>
                            <input type="hidden" id="days_available" name="days_available" value="{{implode(',',$request['daysAvailable'])}}">
                            <input type="hidden" id="shift_type" name="shift_type" value="{{implode(',',$request['shift_type'])}}">
                            <input type="hidden" id="bucket" name="bucket" value="{{$request['bucket']}}">

                    <?php }
                    }?>

                <section class="supplies-details">
                    <div id="form-error"></div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <label class="w-100" for="filled_by_name">Coordinator</label>
                            <select class="form-control" name="filled_by_name" id="filled_by_name">
                                <option value="">-- Select One --</option>
                                @foreach($cccList as $ccc)
                                    <option {{((isset($request['source']) && $request['source'] != 'dashboard_buckets_add_form' && $request['cccId'] != -1) ? $request['cccId'] : Auth::user()->id) == $ccc['id'] ? "selected='selected'" : ""}} value="{{$ccc['id']}}">{{$ccc['name']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label class="w-100" for="clientName">Client Name</label>
                            <input type="text" class="form-control" id="clientName" name="clientName" value="{{(isset($request['source']) && $request['source'] != 'dashboard_buckets_add_form') ? $request['patientName'] : ''}}"/>
                        </div>
                        <div class="col-md-6">
                            <label class="w-100" for="shift_date">Visit Date</label>
                            <input type="input" class="form-control" id="shift_date" name="shift_date" autocomplete="off" {{(isset($request['source']) && $request['source'] != 'dashboard_buckets_add_form') ? 'disabled' : ''}}/>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="w-100" for="start_time">Start Time</label>
                            <input type="input" class="form-control" id="start_time" name="start_time" {{(isset($request['source']) && $request['source'] != 'dashboard_buckets_add_form' && empty($scheduleTime)) ? 'disabled' : ''}} value="{{ !empty($scheduleTime) ? current($scheduleTime) : "" }}">
                        </div>
                        <div class="col-md-3">
                            <label class="w-100" for="end_time">End Time</label>
                            <input type="input" class="form-control" id="end_time" name="end_time" {{(isset($request['source']) && $request['source'] != 'dashboard_buckets_add_form'  && empty($scheduleTime)) ? 'disabled' : ''}} value="{{ !empty($scheduleTime) ? end($scheduleTime) : "" }}">
                        </div>
                        <div class="col-md-6">
                            <label class="w-100" for="Hours">Planned Hours</label>
                            <input type="input" class="form-control" id="Hours" name="Hours" {{(isset($request['source']) && $request['source'] != 'dashboard_buckets_add_form'  && empty($scheduleTime)) ? 'disabled' : ''}}/>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                        <?php if(LD::checkFeatureFlag('coverage-clinician-map-prn-process') ){
                                if(isset($request['bucket']) && $request['bucket']=='map_coverageclinician') {  ?>
                                <label class="w-100" for="candidate_name">Clinician</label>
                                <input type="text" class="form-control" id="candidate_name" name="candidate_name" value="{{isset($request['clinicianName'])?$request['clinicianName']:''}}"/>
                            <?php } else { ?>
                                    <label class="w-100" for="candidate_name">Clinician</label>
                                    <input type="text" class="form-control" id="candidate_name" name="candidate_name" />
                                <?php } } else { ?>
                                    <label class="w-100" for="candidate_name">Clinician</label>
                                    <input type="text" class="form-control" id="candidate_name" name="candidate_name" />
                                    <?php } ?>
                        </div>
                        @if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker'))
                            <div class="col-md-6">
                            <?php if(LD::checkFeatureFlag('coverage-clinician-map-prn-process') ){
                                if(isset($request['bucket']) && $request['bucket']=='map_coverageclinician') {  ?>
                                 <label class="w-100" for="ClinicianID">Clinician ID</label>
                                 <input disabled='true' type="text" class="form-control" id="ClinicianID" name="ClinicianID" value="{{isset($request['clinicianId'])?$request['clinicianId']:''}}" />
                            <?php } else { ?>
                                <label class="w-100" for="ClinicianID">Clinician ID</label>
                                <input disabled='true' type="text" class="form-control" id="ClinicianID" name="ClinicianID" />
                                <?php } } else { ?>
                                    <label class="w-100" for="ClinicianID">Clinician ID</label>
                                <input disabled='true' type="text" class="form-control" id="ClinicianID" name="ClinicianID" />
                                    <?php } ?>




                            </div>
                        @endif
                    </div>
                    <div class="form-group row">
                        <div class="col-md-4">
                            <label class="w-100" for="escalated_pay_rate">Escalated Pay Rate</label>
                            <input type="input" class="form-control" id="escalated_pay_rate" name="escalated_pay_rate" />
                        </div>
                        <div class="col-md-4">
                            <label class="w-100" for="escalated_pay_rate">GP%</label>
                            <input type="input" class="form-control" id="gp" name="gp" />
                        </div>
                        <div class="col-md-4">
                            <label for="state">Approved By</label>
                            <select id="gp_approved_by" name="gp_approved_by" class="form-control" autocomplete="off">
                                <option value="">-- Select One --</option>
                                @foreach ($approvedByList as $item)
                                <option value="{{$item['text']}}">{{$item['text']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if(LD::checkFeatureFlag('change-coverage-form-to-allow-adding-ccr'))
                    <div class="form-group row">
                        <div class="col-md-4">
                            <input type="hidden" name="need_to_add_ccr" id="need_to_add_ccr" value="1"/>
                            <label
                                class="w-100"
                                for="request_ccr_hopsital_post_coverage_shift">
                                    Request CCR for post hospital coverage shift
                            </label>
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input
                                    class="custom-control-input"
                                    type="checkbox"
                                    id="request_ccr_hopsital_post_coverage_shift"
                                    name="request_ccr_hopsital_post_coverage_shift"/>
                                <label
                                    class="custom-control-label"
                                    for="request_ccr_hopsital_post_coverage_shift">
                                </label>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div class="form-group row">
                        <div class="col-md-6 text-right mt-2">
                            <button class="btn btn-primary" onclick="saveCoverageShiftTracker(event, 0);" id="savebtn">Save</button>
                        </div>
                        @if(isset($request['source']) && $request['source'] == 'dashboard_buckets_add_form')
                        <div class="col-md-6 text-left mt-2">
                            <button class="btn btn-primary" onclick="saveCoverageShiftTracker(event, 1);" id="savenewbtn">Save & New</button>
                        </div>
                        @endif
                    </div>
                </section>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
    function closeForm() {
        $('#addCoverageShiftTrackerFormModal').modal('hide');
        @if($request['source'] == 'dashboard_buckets')
        $("#dataTable-clientcare-coverage-tracker-{{$request['bucket']}}_wrapper div.updateall").remove();
        $("#dataTable-clientcare-coverage-tracker-{{$request['bucket']}}").DataTable().ajax.reload();
        @endif
        @if($request['source'] == 'map_buckets')
        $("#addCoverageTracker2").addClass('hide');
        $('.data-table').DataTable().ajax.reload();
        @endif
        @if($request['bucket']=='map_coverageclinician')
        $("#new_dataTable_wrapper .updateall .btn-primary").addClass("hide");
        $('.data-table').DataTable().ajax.reload();
        @endif
    }
    $(document).ready(function() {
        <?php if(LD::checkFeatureFlag('coverage-clinician-map-prn-process') ){
                if(isset($request['bucket']) && $request['bucket']=='map_coverageclinician') {
                    if(!empty($daysAvailableString)) { ?>
                        $('#shift_date').val('<?php echo $daysAvailableString?>');
                    <?php }
                }
           } ?>

        $('#shift_date').datepicker().bind('click', function() {
            $('#ui-datepicker-div').appendTo($('#shift_date').parent());
        });
        $('#start_time').timepicker({
            "step": 15,
            'forceRoundTime': true,
            'minTime': '06:00:00',
            'maxTime': '21:00:00'
        });
        $('#end_time').timepicker({
            "step": 15,
            'forceRoundTime': true,
            'minTime': '06:00:00',
            'maxTime': '21:00:00'
        });
        $('#addCoverageShiftTrackerForm #start_time').change(function() {
            if ( $('#addCoverageShiftTrackerForm #start_time').val() != '' && $('#addCoverageShiftTrackerForm #end_time').val() != '') {
            getHourDiff($('#addCoverageShiftTrackerForm #start_time').val(), $('#addCoverageShiftTrackerForm #end_time').val())
            }
        });
        $('#addCoverageShiftTrackerForm #end_time').change(function() {
            if ( $('#addCoverageShiftTrackerForm #start_time').val() != '' && $('#addCoverageShiftTrackerForm #end_time').val() != '') {
            getHourDiff($('#addCoverageShiftTrackerForm #start_time').val(), $('#addCoverageShiftTrackerForm #end_time').val())
            }
        });

        @if(!empty($scheduleTime))
            $('#addCoverageShiftTrackerForm #start_time').change();
            $('#addCoverageShiftTrackerForm #end_time').change();
        @endif

        $('#addCoverageShiftTrackerForm #filled_by_name').change(function() {
            $('#addCoverageShiftTrackerForm #filled_by').val($('#addCoverageShiftTrackerForm #filled_by_name option:selected').val());
        });
    });
    function getHourDiff(startTime, endTime) {
        var start_time = getTwentyFourHourTime(startTime);
        var end_time = getTwentyFourHourTime(endTime);

        var time1 = start_time.split(':'), time2 =end_time.split(':');
        var hours1 = parseInt(time1[0], 10),
        hours2 = parseInt(time2[0], 10),
        mins1 = parseInt(time1[1], 10),
        mins2 = parseInt(time2[1], 10);
        var hours = hours2 - hours1, mins = 0;

        // get hours
        if(hours < 0) hours = 24 + hours;

        // get minutes
        if(mins2 >= mins1) {
            mins = mins2 - mins1;
        }
        else {
            mins = (mins2 + 60) - mins1;
            hours--;
        }

        // convert to fraction of 60
        mins = mins / 60;

        hours += mins;
        hours = hours.toFixed(2);
        $('#Hours').val(hours);
    }
    function getTwentyFourHourTime(timestart) {
        var time = timestart.toLowerCase().split(':');
        var hours = parseInt(time[0]);
        var _ampm = time[1];
        if (_ampm.indexOf('am') != -1 && hours == 12) {
            time[0] = '00';
        }
        if (_ampm.indexOf('pm')  != -1 && hours < 12) {
            time[0] = hours + 12;
        }
        return time.join(':').replace(/(am|pm)/, '');
    }
    // Wait for the DOM to be ready
    $(function() {
        $("#addCoverageShiftTrackerForm").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "filled_by_name": {
                    required: true
                },
                "clientName": {
                    required: true
                },
                "Hours": {
                    required: true
                },
                "shift_date": {
                    required: true
                },
                "start_time": {
                    required: true
                },
                "end_time": {
                    required: true
                },
                "candidate_name": {
                    required: true
                },
                "escalated_pay_rate": {
                    required: true
                },
                "gp_approved_by": {
                    required: true
                },
                "gp": {
                    required: true
                }
            },
            errorPlacement: function(error, element) {}
        });
    });
    var clientNameFlag = false;
    var candidateNameFlag = false;
    $("#addCoverageShiftTrackerForm #clientName").autocomplete({
        source: "{{ route('getScheduleClient') }}",
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            clientNameFlag = true;
            $('#addCoverageShiftTrackerForm #aoc_patient_id').val(ui.item.id);
        }
    });
    $('#addCoverageShiftTrackerForm #clientName').keyup(function() {
        clientNameFlag = false;
        if ($(this).val() == '') {
            $('#aoc_patient_id').val(0);
        }
    });
    $('#addCoverageShiftTrackerForm #clientName').blur(function() {
        if (clientNameFlag == false) {
            $(this).val('');
            $('#aoc_patient_id').val(0);
        }
    });
    $("#candidate_name").autocomplete({
        source: function(request, response) {
            $.getJSON("{{ route('getCandidateName') }}", {
                status: 'current',
                filter: {'mssql_providers.EmploymentStatusID': [1,2,8,22,24]},
                term: $('#candidate_name').val(),
                @if (LD::checkFeatureFlag('change-coverage-form-to-allow-adding-ccr'))
                aoc_patient_id: $('#addCoverageShiftTrackerForm #aoc_patient_id').val()
                @endif
            }, response);
        },
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            candidateNameFlag = true;
            console.log(ui.item);
            $('#addCoverageShiftTrackerForm #aoc_provider_id').val(ui.item.id);
            validateDisciplineName(ui.item.id);
            @if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker'))
                $('#ClinicianID').val(ui.item.ClinicianID);
            @endif
            @if (LD::checkFeatureFlag('change-coverage-form-to-allow-adding-ccr'))
            if (
                !$('#addCoverageShiftTrackerForm #request_ccr_hopsital_post_coverage_shift').is(':checked') &&
                ui.item.scheduled == 0
            ) {
                $('.clinicianCCRValidationMsg').removeClass('hide');
            } else{
                $('#need_to_add_ccr').val(0);
                $('.clinicianCCRValidationMsg').addClass('hide');
            }
            @endif
        }
    });
    @if (LD::checkFeatureFlag('change-coverage-form-to-allow-adding-ccr'))
        $('#addCoverageShiftTrackerForm #request_ccr_hopsital_post_coverage_shift').change(function() {
            if (
                $('#addCoverageShiftTrackerForm #aoc_patient_id').val() > 0 &&
                $('#addCoverageShiftTrackerForm #ClinicianID').val() != '' &&
                !$(this).is(':checked') &&
                $('#addCoverageShiftTrackerForm #need_to_add_ccr').val() == 1
            ) {
                $('.clinicianCCRValidationMsg').removeClass('hide');
            } else {
                $('.clinicianCCRValidationMsg').addClass('hide');
            }
        });
    @endif
    @if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker'))
    $("#candidate_name").keyup(function() {
        $('#ClinicianID').val('');
        $('#aoc_provider_id').val(0);
        $('.clinicianCCRValidationMsg').addClass('hide');
    });
    @endif

    @if(!LD::checkFeatureFlag('allow-clinician-name-coverage-tracker'))
        $('#addCoverageShiftTrackerForm #candidate_name').keyup(function() {
            candidateNameFlag = false;
            if ($(this).val() == '') {
                $('#addCoverageShiftTrackerForm #aoc_provider_id').val(0);
            }
        });

        $('#addCoverageShiftTrackerForm #candidate_name').blur(function() {
            if (candidateNameFlag == false) {
                $(this).val('');
                $('#addCoverageShiftTrackerForm #aoc_provider_id').val(0);
            }
        });
    @endif
    function validateDisciplineName(providerId){
        let _token = $('meta[name="csrf-token"]').attr('content');
        coverageMapRowIds = $("#selectedCoverageRows").val();
		if (coverageMapRowIds != '' && providerId != '') {
			$.ajax({
				url: "{{route('coverage.validateDisciplineName')}}",
				type: 'get',
				dateType:"html",
				data: {
					providerId:providerId,
					coverageMapRowIds:coverageMapRowIds,
					_token: _token
				},
				success: function(data) {
					if(!data.status){
						$(".clinicianValidationMsg").removeClass('hide');
					}else{
						$(".clinicianValidationMsg").addClass('hide');
					}
				}
			});
		}
    }
    function saveCoverageShiftTracker(e, saveandnew) {

        e.preventDefault();
        if($('#aoc_provider_id').val()==0) {
            $("#candidate_name").val('');
        }
        if ($('#addCoverageShiftTrackerForm').valid()) {

            $('#savebtn').prop('disabled', true);
            $('#savenewbtn').prop('disabled', true);
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#addCoverageShiftTrackerForm').serializeObject();
            data['_token'] = _token;
            $.ajax({
                url: "{{route('coveragetracker.saveCoverageShiftTrackerForm')}}",
                type: 'post',
                data: data,
                success: function(response) {
                    $("#successBody").html(response.message);
                    $('#savebtn').prop('disabled', false);
                    $('#savenewbtn').prop('disabled', false);
                    if (response.success == 1) {
                        if (saveandnew == 0) {
                            $("#scheduleNextFormDiv").hide();
                            $("#scheduleNextSuccessDiv").show();
                            //$("#scheduleNextForm").modal('show');
                            $("#addCoverageShiftTrackerForm")[0].reset();
                        } else {
                            var clientName = $('#clientName').val();
                            var gp = $('#gp').val();
                            var gp_approved_by = $('#gp_approved_by').val();
                            var escalated_pay_rate = $('#escalated_pay_rate').val();
                            var ProviderName = $('#ProviderName').val();
                            var candidate_name = $('#candidate_name').val();
                            var aoc_patient_id = $('#aoc_patient_id').val();
                            var aoc_provider_id = $('#aoc_provider_id').val();
                            var filled_by_name = $('#filled_by_name').val();
                            var filled_by = $('#filled_by').val();
                            $("#addCoverageShiftTrackerForm")[0].reset();
                            $('#clientName').val(clientName);
                            $('#ProviderName').val(ProviderName);
                            $('#escalated_pay_rate').val(escalated_pay_rate);
                            $('#gp_approved_by').val(gp_approved_by);
                            $('#gp').val(gp);
                            $('#candidate_name').val(candidate_name);
                            $('#aoc_patient_id').val(aoc_patient_id);
                            $('#aoc_provider_id').val(aoc_provider_id);
                            $('#filled_by_name').val(filled_by_name);
                            $('#filled_by').val(filled_by);
                        }
                    } else {
                        $('#form-error').html('<label class="error">'+response.message+'</label>');
                    }
                }
            });
        }
    }
</script>
