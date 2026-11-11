<?php

use App\Helpers\H;
use App\User;
use App\Helpers\LD;

$cccList = User::where('status',1)->orderBy('name')->get()->toArray();

$mode = isset($coverageTrackerDetail)?'edit':'add';
?>

<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<x-modal>
    <x-slot name="id">addCoverageShiftTrackerFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    @if(LD::checkFeatureFlag('coverage-tracker-view-action'))
        @if ($isViewMode)
            <x-slot name="title">Coverage Tracker</x-slot>
        @else
            <x-slot name="title">{{($mode == 'add')?'Add to':'Edit'}} Coverage Tracker</x-slot>
        @endif
    @else
        <x-slot name="title">{{($mode == 'add')?'Add to':'Edit'}} Coverage Tracker</x-slot>
    @endif
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
            @if(LD::checkFeatureFlag('coverage-tracker-view-action'))
                <form id="addCoverageShiftTrackerForm" class="addCoverageShiftTrackerForm">
                    @if($mode == 'edit')
                    <input type="hidden" id="coverage_tracker_id" name="coverage_tracker_id" value="{{isset($coverageTrackerDetail['id']) ? $coverageTrackerDetail['id'] : '0'}}"/>
                    @endif
                    <input type="hidden" id="aoc_patient_id" name="aoc_patient_id" value="{{isset($coverageTrackerDetail['aoc_patient_id']) ? $coverageTrackerDetail['aoc_patient_id'] : '0'}}"/>
                    <input type="hidden" id="aoc_provider_id" name="aoc_provider_id" value="{{isset($coverageTrackerDetail['aoc_provider_id']) ? $coverageTrackerDetail['aoc_provider_id'] : '0'}}"/>
                    <input type="hidden" id="filled_by" name="filled_by" value="{{Auth::user()->id}}">
                    <section class="supplies-details">
                        <div id="form-error"></div>
                        <div class="form-group row">
                            <div class="col-md-12">
                                <label class="w-100" for="filled_by_name">Coordinator</label>
                                <select {{$isViewMode ? 'disabled="disabled"' : ''}} class="form-control" name="filled_by_name" id="filled_by_name">
                                    <option value="">-- Select One --</option>
                                    @foreach($cccList as $ccc)
                                        <option {{(
                                                    ($isViewMode) ? $coverageTrackerDetail['filled_by'] : Auth::user()->id) == $ccc['id']
                                                    ? "selected='selected'" : ""
                                                }} value="{{$ccc['id']}}">
                                                    {{$ccc['name']}}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="w-100" for="clientName">Client Name</label>
                                <input {{$isViewMode ? 'disabled="disabled"' : ''}} type="text" class="form-control" id="clientName" name="clientName" value="{{isset($coverageTrackerDetail['clientName']) ? $coverageTrackerDetail['clientName'] : ''}}" {{($mode == 'edit')?"readonly":''}} />
                            </div>
                            <div class="col-md-6">
                                <label class="w-100" for="shift_date">Visit Date</label>
                                <input {{$isViewMode ? 'disabled="disabled"' : ''}} type="input" class="form-control" id="shift_date" name="shift_date" autocomplete="off" value="{{isset($coverageTrackerDetail['shift_date']) ? H::getFormatedDate($coverageTrackerDetail['shift_date']) : ''}}"/>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-3">
                                <label class="w-100" for="start_time">Start Time</label>
                                <input {{$isViewMode ? 'disabled="disabled"' : ''}} type="input" class="form-control" id="start_time" name="start_time" value="{{isset($coverageTrackerDetail['start_time']) ? $coverageTrackerDetail['start_time'] : ''}}"/>
                            </div>
                            <div class="col-md-3">
                                <label class="w-100" for="end_time">End Time</label>
                                <input {{$isViewMode ? 'disabled="disabled"' : ''}} type="input" class="form-control" id="end_time" name="end_time" value="{{isset($coverageTrackerDetail['end_time']) ? $coverageTrackerDetail['end_time'] : ''}}"/>
                            </div>
                            <div class="col-md-6">
                                <label class="w-100" for="Hours">Planned Hours</label>
                                <input {{$isViewMode ? 'disabled="disabled"' : ''}} type="input" class="form-control" id="Hours" name="Hours" />
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="w-100" for="candidate_name">Clinician</label>
                                <input {{$isViewMode ? 'disabled="disabled"' : ''}} type="text" class="form-control" id="candidate_name" name="candidate_name"  value="{{isset($coverageTrackerDetail['candidate_name']) ? $coverageTrackerDetail['candidate_name'] : ''}}" />
                            </div>
                            @if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker'))
                            <div class="col-md-6">
                                <label class="w-100" for="ClinicianID">Clinician ID</label>
                                <input {{$isViewMode ? 'disabled="disabled"' : ''}} disabled='true' type="text" class="form-control" value="{{isset($coverageTrackerDetail['ClinicianID']) ? $coverageTrackerDetail['ClinicianID'] : ''}}" id="ClinicianID" name="ClinicianID" />
                            </div>
                            @endif
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="w-100" for="escalated_pay_rate">Escalated Pay Rate</label>
                                <input {{$isViewMode ? 'disabled="disabled"' : ''}} type="input" class="form-control" id="escalated_pay_rate" name="escalated_pay_rate" value="{{isset($coverageTrackerDetail['escalated_pay_rate']) ? $coverageTrackerDetail['escalated_pay_rate'] : ''}}" />
                            </div>
                            <div class="col-md-6">
                                <label class="w-100" for="escalated_pay_rate">GP%</label>
                                <input {{$isViewMode ? 'disabled="disabled"' : ''}} type="input" class="form-control" id="gp" name="gp" value="{{isset($coverageTrackerDetail['gp']) ? $coverageTrackerDetail['gp'] : ''}}" />
                            </div>
                            <div class="col-md-6">
                                <label for="state">Approved By</label>
                                <select {{$isViewMode ? 'disabled="disabled"' : ''}} id="gp_approved_by" name="gp_approved_by" class="form-control" autocomplete="off">
                                    <option value="">-- Select One --</option>
                                    @foreach ($approvedByList as $item)
                                    <option value="{{$item['text']}}" {{(isset($coverageTrackerDetail['approved_by']) && $item['text'] == $coverageTrackerDetail['approved_by']) ? 'selected' : ''}}>{{$item['text']}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @if($mode == 'add' && !$isViewMode)
                        <div class="form-group row">
                            <div class="col-md-6 text-right mt-2">
                                <button class="btn btn-primary" onclick="saveCoverageShiftTracker(event, 0);" id="savebtn">Save</button>
                            </div>
                            <div class="col-md-6 text-left mt-2">
                                <button class="btn btn-primary" onclick="saveCoverageShiftTracker(event, 1);" id="savenewbtn">Save & New</button>
                            </div>
                        </div>
                        @elseif(!$isViewMode)
                        <div class="form-group row">
                            <div class="col-md-6 text-right mt-2">
                                <button class="btn btn-primary" onclick="updateCoverageShiftTracker(event);" id="savebtn">Update</button>
                            </div>
                            <div class="col-md-6 text-left mt-2">
                            </div>
                        </div>
                        @endif
                    </section>
                </form>
            @else
            <form id="addCoverageShiftTrackerForm" class="addCoverageShiftTrackerForm">
                @if($mode == 'edit')
                <input type="hidden" id="coverage_tracker_id" name="coverage_tracker_id" value="{{isset($coverageTrackerDetail['id']) ? $coverageTrackerDetail['id'] : '0'}}"/>
                @endif
                <input type="hidden" id="aoc_patient_id" name="aoc_patient_id" value="{{isset($coverageTrackerDetail['aoc_patient_id']) ? $coverageTrackerDetail['aoc_patient_id'] : '0'}}"/>
                <input type="hidden" id="aoc_provider_id" name="aoc_provider_id" value="{{isset($coverageTrackerDetail['aoc_provider_id']) ? $coverageTrackerDetail['aoc_provider_id'] : '0'}}"/>
                <input type="hidden" id="filled_by" name="filled_by" value="{{Auth::user()->id}}">
                <section class="supplies-details">
                    <div id="form-error"></div>
                    <div class="form-group row">
                        <div class="col-md-12">
                            <label class="w-100" for="filled_by_name">Coordinator</label>
                            <select class="form-control" name="filled_by_name" id="filled_by_name">
                                <option value="">-- Select One --</option>
                                @foreach($cccList as $ccc)
                                    <option {{Auth::user()->id == $ccc['id'] ? "selected='selected'" : ""}} value="{{$ccc['id']}}">{{$ccc['name']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label class="w-100" for="clientName">Client Name</label>
                            <input type="text" class="form-control" id="clientName" name="clientName" value="{{isset($coverageTrackerDetail['clientName']) ? $coverageTrackerDetail['clientName'] : ''}}" {{($mode == 'edit')?"readonly":''}} />
                        </div>
                        <div class="col-md-6">
                            <label class="w-100" for="shift_date">Visit Date</label>
                            <input type="input" class="form-control" id="shift_date" name="shift_date" autocomplete="off" value="{{isset($coverageTrackerDetail['shift_date']) ? H::getFormatedDate($coverageTrackerDetail['shift_date']) : ''}}"/>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-3">
                            <label class="w-100" for="start_time">Start Time</label>
                            <input type="input" class="form-control" id="start_time" name="start_time" value="{{isset($coverageTrackerDetail['start_time']) ? $coverageTrackerDetail['start_time'] : ''}}"/>
                        </div>
                        <div class="col-md-3">
                            <label class="w-100" for="end_time">End Time</label>
                            <input type="input" class="form-control" id="end_time" name="end_time" value="{{isset($coverageTrackerDetail['end_time']) ? $coverageTrackerDetail['end_time'] : ''}}"/>
                        </div>
                        <div class="col-md-6">
                            <label class="w-100" for="Hours">Planned Hours</label>
                            <input type="input" class="form-control" id="Hours" name="Hours" />
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label class="w-100" for="candidate_name">Clinician</label>
                            <input type="text" class="form-control" id="candidate_name" name="candidate_name"  value="{{isset($coverageTrackerDetail['candidate_name']) ? $coverageTrackerDetail['candidate_name'] : ''}}" />
                        </div>
                        @if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker'))
                        <div class="col-md-6">
                            <label class="w-100" for="ClinicianID">Clinician ID</label>
                            <input disabled='true' type="text" class="form-control" value="{{isset($coverageTrackerDetail['ClinicianID']) ? $coverageTrackerDetail['ClinicianID'] : ''}}" id="ClinicianID" name="ClinicianID" />
                        </div>
                        @endif
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label class="w-100" for="escalated_pay_rate">Escalated Pay Rate</label>
                            <input type="input" class="form-control" id="escalated_pay_rate" name="escalated_pay_rate" value="{{isset($coverageTrackerDetail['escalated_pay_rate']) ? $coverageTrackerDetail['escalated_pay_rate'] : ''}}" />
                        </div>
                        <div class="col-md-6">
                            <label class="w-100" for="escalated_pay_rate">GP%</label>
                            <input type="input" class="form-control" id="gp" name="gp" value="{{isset($coverageTrackerDetail['gp']) ? $coverageTrackerDetail['gp'] : ''}}" />
                        </div>
                        <div class="col-md-6">
                            <label for="state">Approved By</label>
                            <select id="gp_approved_by" name="gp_approved_by" class="form-control" autocomplete="off">
                                <option value="">-- Select One --</option>
                                @foreach ($approvedByList as $item)
                                <option value="{{$item['text']}}" {{(isset($coverageTrackerDetail['approved_by']) && $item['text'] == $coverageTrackerDetail['approved_by']) ? 'selected' : ''}}>{{$item['text']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if($mode == 'add')
                    <div class="form-group row">
                        <div class="col-md-6 text-right mt-2">
                            <button class="btn btn-primary" onclick="saveCoverageShiftTracker(event, 0);" id="savebtn">Save</button>
                        </div>
                        <div class="col-md-6 text-left mt-2">
                            <button class="btn btn-primary" onclick="saveCoverageShiftTracker(event, 1);" id="savenewbtn">Save & New</button>
                        </div>
                    </div>
                    @else
                    <div class="form-group row">
                        <div class="col-md-6 text-right mt-2">
                            <button class="btn btn-primary" onclick="updateCoverageShiftTracker(event);" id="savebtn">Update</button>
                        </div>
                        <div class="col-md-6 text-left mt-2">
                        </div>
                    </div>
                    @endif
                </section>
            </form>
            @endif
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
    function closeForm() {
        $('#addCoverageShiftTrackerFormModal').modal('hide');
        $(".dataTable").DataTable().ajax.reload();
    }
    $(document).ready(function() {
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
                term: $('#candidate_name').val()
            }, response);
        },
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            candidateNameFlag = true;
            $('#aoc_provider_id').val(ui.item.id);
            @if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker'))
                $('#ClinicianID').val(ui.item.ClinicianID);
            @endif
        }
    });
    @if(LD::checkFeatureFlag('allow-clinician-name-coverage-tracker'))
    $("#candidate_name").keyup(function() {
        $('#ClinicianID').val('');
        $('#aoc_provider_id').val(0);
    });
    @endif

    @if(!LD::checkFeatureFlag('allow-clinician-name-coverage-tracker'))
    $('#addCoverageShiftTrackerForm #candidate_name').keyup(function() {
        candidateNameFlag = false;
        if ($(this).val() == '') {
            $('#aoc_provider_id').val(0);
        }
    });
    $('#addCoverageShiftTrackerForm #candidate_name').blur(function() {
        if (candidateNameFlag == false) {
            $(this).val('');
            $('#aoc_provider_id').val(0);
        }
    });
    @endif

    function saveCoverageShiftTracker(e, saveandnew) {
        if($('#aoc_provider_id').val()==0) {
            $("#candidate_name").val('');
        }
        e.preventDefault();
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
                        $(".dataTable").DataTable().ajax.reload();
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

    function updateCoverageShiftTracker(e) {
        if($('#aoc_provider_id').val()==0) {
            $("#candidate_name").val('');
        }
        e.preventDefault();
        if ($('#addCoverageShiftTrackerForm').valid()) {

            $('#savebtn').prop('disabled', true);
            $('#savenewbtn').prop('disabled', true);
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#addCoverageShiftTrackerForm').serializeObject();
            data['_token'] = _token;
            $.ajax({
                url: "{{route('coveragetracker.updateCoverageShiftTrackerForm')}}",
                type: 'post',
                data: data,
                success: function(response) {
                    $("#successBody").html(response.message);
                    $('#savebtn').prop('disabled', false);
                    $('#savenewbtn').prop('disabled', false);
                    if (response.success == 1) {
                        $("#scheduleNextFormDiv").hide();
                        $("#scheduleNextSuccessDiv").show();
                        //$("#scheduleNextForm").modal('show');
                        $("#addCoverageShiftTrackerForm")[0].reset();
                    } else {
                        $('#form-error').html('<label class="error">'+response.message+'</label>');
                    }
                }
            });
        }
    }
    @if($mode == 'edit')
    $(document).ready(function() {
        $('#addCoverageShiftTrackerForm #end_time').trigger("change");
        $('#addCoverageShiftTrackerForm #start_time').trigger("change");
        $('#addCoverageShiftTrackerForm #filled_by_name').trigger("change");
    });
    @endif
</script>
