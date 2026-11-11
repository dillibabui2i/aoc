<?php

use App\Helpers\H;
use App\Helpers\LD;
$branchLocation = H::getHelper('ConstantHelper')->getBranches()->toArray();
$branchLocation = H::arrGroupBy($branchLocation, 'state');
$selectedRoles = [];
if(isset($afterHoursDetail)) {
    $selectedRoles = explode(",", $afterHoursDetail->roles_id??'');
    $selectedBranch = explode(",", $afterHoursDetail->branchid??'');
    $sendNotificationDuration = explode(",", $afterHoursDetail->send_notification_duration??'');
}

?>
@extends('layouts.myaccount')
@section('title', 'Manage After Hours')
@section('content')
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Schedule After Hours</h1>
        </div>
    </div>
    @if ($errors->any())
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {!! implode('', $errors->all('<div>:message</div>')) !!}
    </div>
    @endif
    @if ($message = Session::get('error'))
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    <div id="afterhoursform" class="afterhoursform">
        @if( isset($afterHoursDetail))
        <form method="POST" id="schedule_class_form" class="scaffold-form" enctype="multipart/form-data" action="{{ route('manageafterhours.updateafterhoursshift') }}">
            <input type="hidden" name="id" id="id" value="{{$afterHoursDetail->id}}">
            @else
            <form method="post" action="{{ route('manageafterhours.saveafterhoursshift') }}" id="schedule_class_form" class="scaffold-form" enctype="multipart/form-data">
                @endif
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="title">Title</label>
                        <input type="input" class="form-control" id="title" name="title" @if(isset($afterHoursDetail)) value="{{$afterHoursDetail->title}}" @endif>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="roles_id">Role</label>
                        <select id="roles_id" name="roles_id[]" class="form-control selectpicker" multiple data-placeholder="Select Role" data-live-search="true">
                        @foreach($roleData as $role)
                        <option value="{{$role['id']}}" @if(isset($afterHoursDetail)) {{(in_array($role['id'], $selectedRoles))?'selected':''}} @endif>{{$role['name']}}</option>
                        @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="branchid">Branch</label>
                        <select id="branchid" name="branchid[]" class="form-control selectpicker" multiple data-placeholder="Select Branch" data-live-search="true">
                            @foreach($branchLocation as $key => $branch)
                            <optgroup label="{{$key}}">
                                @foreach($branch as $b)
                                <option value="{{$b['id']}}" @if(isset($afterHoursDetail)) {{(in_array($b['id'], $selectedBranch))?'selected':''}} @endif>{{$b['branch']}}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="color_filled">Color for Filled Schedule</label>
                        <select name="color_filled" id="color_filled" class="form-control">
                            <option value="">--Select Color for Filled Schedule--</option>
                            <option value="EB0602" style="background-color:#EB0602;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled =='EB0602')?'selected':'' }} @endif>Red - Filled</option>
                            <option value="F7CDCC" style="background-color:#F7CDCC;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled =='F7CDCC')?'selected':'' }} @endif>Red - Unfilled</option>
                            <option value="4D53FF" style="background-color:#4D53FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == '4D53FF')?'selected':'' }} @endif>Blue - Filled</option>
                            <option value="DADBF9" style="background-color:#DADBF9;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'DADBF9')?'selected':'' }} @endif>Blue - Unfilled</option>
                            <option value="05AD3F" style="background-color:#05AD3F;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == '05AD3F')?'selected':'' }} @endif>Green - Filled</option>
                            <option value="D1F7DE" style="background-color:#D1F7DE;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'D1F7DE')?'selected':'' }} @endif>Green - Unfilled</option>
                            <option value="FFBD19" style="background-color:#FFBD19;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'FFBD19')?'selected':'' }} @endif>Orange - Filled</option>
                            <option value="FFEDC0" style="background-color:#FFEDC0;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'FFEDC0')?'selected':'' }} @endif>Orange - Unfilled</option>
                            <option value="FAFF48" style="background-color:#FAFF48;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'FAFF48')?'selected':'' }} @endif>Yellow - Filled</option>
                            <option value="FEFFD9" style="background-color:#FEFFD9;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'FEFFD9')?'selected':'' }} @endif>Yellow - Unfilled</option>
                            <option value="AE29FF" style="background-color:#AE29FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'AE29FF')?'selected':'' }} @endif>Purple - Filled</option>
                            <option value="F0D8FF" style="background-color:#F0D8FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'F0D8FF')?'selected':'' }} @endif>Purple - Unfilled</option>
                            <option value="926733" style="background-color:#926733;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == '926733')?'selected':'' }} @endif>Brown - Filled</option>
                            <option value="BEAC97" style="background-color:#BEAC97;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'BEAC97')?'selected':'' }} @endif>Brown - Unfilled</option>
                            <option value="000000" style="background-color:#000000;color:#fff;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == '000000')?'selected':'' }} @endif>Black - Filled</option>
                            <option value="C6C6C6" style="background-color:#C6C6C6;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'C6C6C6')?'selected':'' }} @endif>Black - Unfilled</option>
                            <option value="FA11A5" style="background-color:#FA11A5;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'FA11A5')?'selected':'' }} @endif>Pink - Filled</option>
                            <option value="FFCFEE" style="background-color:#FFCFEE;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'FFCFEE')?'selected':'' }} @endif>Pink - Unfilled</option>
                            <option value="00FF27" style="background-color:#00FF27;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == '00FF27')?'selected':'' }} @endif>Lime Green - Filled</option>
                            <option value="C3FFCC" style="background-color:#C3FFCC;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'C3FFCC')?'selected':'' }} @endif>Lime Green - Unfilled</option>
                            <option value="139B91" style="background-color:#139B91;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == '139B91')?'selected':'' }} @endif>Teal - Filled</option>
                            <option value="B3E2DF" style="background-color:#B3E2DF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'B3E2DF')?'selected':'' }} @endif>Teal - Unfilled</option>
                            <option value="04D9FF" style="background-color:#04D9FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == '04D9FF')?'selected':'' }} @endif>Aqua - Filled</option>
                            <option value="C1F6FF" style="background-color:#C1F6FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_filled == 'C1F6FF')?'selected':'' }} @endif>Aqua - Unfilled</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="color_open">Color for Unfilled Schedule</label>
                        <select name="color_open" id="color_open" class="form-control">
                            <option value="">--Select Color for Unfilled Schedule--</option>
                            <option value="EB0602" style="background-color:#EB0602;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open =='EB0602')?'selected':'' }} @endif>Red - Filled</option>
                            <option value="F7CDCC" style="background-color:#F7CDCC;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open =='F7CDCC')?'selected':'' }} @endif>Red - Unfilled</option>
                            <option value="4D53FF" style="background-color:#4D53FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == '4D53FF')?'selected':'' }} @endif>Blue - Filled</option>
                            <option value="DADBF9" style="background-color:#DADBF9;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'DADBF9')?'selected':'' }} @endif>Blue - Unfilled</option>
                            <option value="05AD3F" style="background-color:#05AD3F;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == '05AD3F')?'selected':'' }} @endif>Green - Filled</option>
                            <option value="D1F7DE" style="background-color:#D1F7DE;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'D1F7DE')?'selected':'' }} @endif>Green - Unfilled</option>
                            <option value="FFBD19" style="background-color:#FFBD19;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'FFBD19')?'selected':'' }} @endif>Orange - Filled</option>
                            <option value="FFEDC0" style="background-color:#FFEDC0;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'FFEDC0')?'selected':'' }} @endif>Orange - Unfilled</option>
                            <option value="FAFF48" style="background-color:#FAFF48;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'FAFF48')?'selected':'' }} @endif>Yellow - Filled</option>
                            <option value="FEFFD9" style="background-color:#FEFFD9;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'FEFFD9')?'selected':'' }} @endif>Yellow - Unfilled</option>
                            <option value="AE29FF" style="background-color:#AE29FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'AE29FF')?'selected':'' }} @endif>Purple - Filled</option>
                            <option value="F0D8FF" style="background-color:#F0D8FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'F0D8FF')?'selected':'' }} @endif>Purple - Unfilled</option>
                            <option value="926733" style="background-color:#926733;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == '926733')?'selected':'' }} @endif>Brown - Filled</option>
                            <option value="BEAC97" style="background-color:#BEAC97;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'BEAC97')?'selected':'' }} @endif>Brown - Unfilled</option>
                            <option value="000000" style="background-color:#000000;color:#fff;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == '000000')?'selected':'' }} @endif>Black - Filled</option>
                            <option value="C6C6C6" style="background-color:#C6C6C6;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'C6C6C6')?'selected':'' }} @endif>Black - Unfilled</option>
                            <option value="FA11A5" style="background-color:#FA11A5;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'FA11A5')?'selected':'' }} @endif>Pink - Filled</option>
                            <option value="FFCFEE" style="background-color:#FFCFEE;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'FFCFEE')?'selected':'' }} @endif>Pink - Unfilled</option>
                            <option value="00FF27" style="background-color:#00FF27;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == '00FF27')?'selected':'' }} @endif>Lime Green - Filled</option>
                            <option value="C3FFCC" style="background-color:#C3FFCC;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'C3FFCC')?'selected':'' }} @endif>Lime Green - Unfilled</option>
                            <option value="139B91" style="background-color:#139B91;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == '139B91')?'selected':'' }} @endif>Teal - Filled</option>
                            <option value="B3E2DF" style="background-color:#B3E2DF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'B3E2DF')?'selected':'' }} @endif>Teal - Unfilled</option>
                            <option value="04D9FF" style="background-color:#04D9FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == '04D9FF')?'selected':'' }} @endif>Aqua - Filled</option>
                            <option value="C1F6FF" style="background-color:#C1F6FF;color:#000;" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->color_open == 'C1F6FF')?'selected':'' }} @endif>Aqua - Unfilled</option>
                        </select>
                    </div>
                    @if (!empty($addafterhours))
                    <div class="form-group col-md-12" id="displayOnSpecific">
                        <label for="title">Date</label>
                        <input type="input" class="form-control" id="specific_date" name="specific_date" autocomplete="off" value="{{ (isset($specific_date))?$specific_date :''}}">
                    </div>
                    @endif
                    <div class="form-group col-md-12">
                        <label for="start_time">Start Time</label>
                        <input type="input" class="form-control" id="start_time" name="start_time" @if(isset($afterHoursDetail)) value="{{ $afterHoursDetail->start_time}}" @endif>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="end_time">End Time</label>
                        <input type="input" class="form-control" id="end_time" name="end_time" @if(isset($afterHoursDetail)) value="{{ $afterHoursDetail->end_time}}" @endif>
                    </div>
                    @if(LD::checkFeatureFlag('after-hours-create-shift-on-specific-day'))
                        @if (empty($addafterhours))
                            <div class="form-group col-md-12">
                                <label for="repeat">Repeat Every</label>
                                <select name="repeat" id="repeat" class="form-control"  @if(isset($afterHoursDetail)) disabled @endif>
                                    <option value="">--Select Repeat Option--</option>
                                    <option value="Day" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->repeat =='Day')?'selected':'' }} @endif>Day</option>
                                    <option value="Week" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->repeat == 'Week')?'selected':'' }} @endif>Week</option>
                                    <option value="Month" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->repeat == 'Month')?'selected':'' }} @endif>Month</option>
                                    <option value="Specific" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->repeat == 'Specific')?'selected':'' }} @endif>Specific Day</option>
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="repeat" id="repeat" value="Specific"/>
                        @endif
                    @else
                    <div class="form-group col-md-12">
                        <label for="repeat">Repeat Every</label>
                        <select name="repeat" id="repeat" class="form-control"  @if(isset($afterHoursDetail)) disabled @endif>
                            <option value="">--Select Repeat Option--</option>
                            <option value="Day" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->repeat =='Day')?'selected':'' }} @endif>Day</option>
                            <option value="Week" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->repeat == 'Week')?'selected':'' }} @endif>Week</option>
                            <option value="Month" @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->repeat == 'Month')?'selected':'' }} @endif>Month</option>
                        </select>
                    </div>
                    @endif
                    <div class="form-group col-md-12" id="displayOnDiv" style="display: none;">
                        <label style="width: 28.5%;">Repeat On</label>
                        <div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox1" name="repeat_on[]" value="1" @if(!empty($repeat_on)  && in_array("1",$repeat_on)) checked @endif   @if(isset($afterHoursDetail)) disabled @endif>
                                <label class="form-check-label" for="inlineCheckbox1">Monday</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox2" name="repeat_on[]" value="2" @if(!empty($repeat_on)  && in_array("2",$repeat_on)) checked @endif   @if(isset($afterHoursDetail)) disabled @endif>
                                <label class="form-check-label" for="inlineCheckbox2">Tuesday</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox3" name="repeat_on[]" value="3" @if(!empty($repeat_on)  && in_array("3",$repeat_on)) checked @endif   @if(isset($afterHoursDetail)) disabled @endif>
                                <label class="form-check-label" for="inlineCheckbox3">Wednesday</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox4" name="repeat_on[]" value="4" @if(!empty($repeat_on)  && in_array("4",$repeat_on)) checked @endif   @if(isset($afterHoursDetail)) disabled @endif>
                                <label class="form-check-label" for="inlineCheckbox4">Thursday</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox5" name="repeat_on[]" value="5" @if(!empty($repeat_on)  && in_array("5",$repeat_on)) checked @endif   @if(isset($afterHoursDetail)) disabled @endif>
                                <label class="form-check-label" for="inlineCheckbox5">Friday</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox6" name="repeat_on[]" value="6" @if(!empty($repeat_on)  && in_array("6",$repeat_on)) checked @endif   @if(isset($afterHoursDetail)) disabled @endif>
                                <label class="form-check-label" for="inlineCheckbox6">Saturday</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="checkbox" id="inlineCheckbox7" name="repeat_on[]" value="7" @if(!empty($repeat_on)  && in_array("7",$repeat_on)) checked @endif   @if(isset($afterHoursDetail)) disabled @endif>
                                <label class="form-check-label" for="inlineCheckbox7">Sunday</label>
                            </div>
                            <label id="repeat_on[]-error" class="error" for="repeat_on[]"></label>
                        </div>
                    </div>
                    @if (empty($addafterhours))
                    <div class="form-group col-md-12" id="displayOnSpecific" style="display: none;">
                        <label for="title">Date</label>
                        <input type="input" class="form-control" id="specific_date" name="specific_date" autocomplete="off" value="{{ (isset($specific_date))?$specific_date :''}}">
                    </div>
                    @endif
                    <div class="form-group col-md-12">
                        <label for="send_notification" style="width: 28.5%;">Send Notifications</label>
                        <span class="text-left">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="send_notification" id="send_notification_yes" value="1"  @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->send_notification == '1')?'checked':'' }} @endif>
                                <label class="form-check-label" for="send_notification_yes">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="send_notification" id="send_notification_no" value="0"  @if(isset($afterHoursDetail)) {{ ($afterHoursDetail->send_notification == '0')?'checked':'' }}  @endif>
                                <label class="form-check-label" for="send_notification_no">No</label>
                            </div>
                        </span>
                    </div>
                    <div class="form-group col-md-12" id="sendNoticationDurationDiv" style="display: none;">
                        <label></label>
                        <select name="send_notification_duration[]" id="send_notification_duration" class="form-control selectpicker" multiple data-placeholder="Duration of notification before shift" data-live-search="true">
                            <option value="72" @if(isset($afterHoursDetail)) {{ (in_array(72, $sendNotificationDuration))?'selected':'' }} @endif>72 hours</option>
                            <option value="48" @if(isset($afterHoursDetail)) {{ (in_array(48, $sendNotificationDuration))?'selected':'' }} @endif>48 hours</option>
                            <option value="24" @if(isset($afterHoursDetail)) {{ (in_array(24, $sendNotificationDuration))?'selected':'' }} @endif>24 hours</option>
                            <option value="6" @if(isset($afterHoursDetail)) {{ (in_array(6, $sendNotificationDuration))?'selected':'' }} @endif>6 hours</option>
                            <option value="1" @if(isset($afterHoursDetail)) {{ (in_array(1, $sendNotificationDuration))?'selected':'' }} @endif>Before 1 hour</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <button type="submit" id="savebtn" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
    </div>
</div>

<script type="text/javascript" id="addhoursjs">
    $(document).ready(function() {
        $('#specific_date').datepicker().bind('click', function() {
            $('#ui-datepicker-div').appendTo($(this).parent());
        });
        $('#start_time').timepicker({
            "step": 30,
            'forceRoundTime': true,
            'minTime': '00:00:00',
            'maxTime': '23:59:00'
        });
        $('#end_time').timepicker({
            "step": 30,
            'forceRoundTime': true,
            'minTime': '00:00:00',
            'maxTime': '23:59:00'
        });
        $("#repeat").on('change',function(){
            $('#displayOnDiv').hide();
            if($(this).val() == 'Day') {
                $('#displayOnDiv').show();
            }
            $('#displayOnSpecific').hide();
            if($(this).val() == 'Specific') {
                $('#displayOnSpecific').show();
            }
        });
        $('input[name="send_notification"]').on('change', function() {
            if ($('input[name="send_notification"]:checked').val() == 1) {
                $('#sendNoticationDurationDiv').show();
            } else {
                $('#sendNoticationDurationDiv').hide();
                $('#send_notification_duration').multiselect("clearSelection");
                $('#send_notification_duration').multiselect("refresh");
            }
        });
        @if( isset($afterHoursDetail))
            $("#repeat").trigger("change");
            $('input[name="send_notification"]').trigger("change");
        @endif
    });

    // Wait for the DOM to be ready
    $(function() {
        $("#schedule_class_form").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "title": {
                    required: true
                },
                "roles_id[]": {
                    required: true
                },
                "start_time": {
                    required: true
                },
                "end_time": {
                    required: true
                },
                "color_filled": {
                    required: true
                },
                "color_open": {
                    required: true
                },
                "repeat": {
                    required: true
                },
                "branchid[]": {
                    required: true
                },
                "repeat_on[]": {
                    required: function (element) {
                        if($('#repeat option:selected').val() =='Day') {
                            return true;
                        }
                    }
                },
                "send_notification": {
                    required: true
                },
                "send_notification_duration[]": {
                    required: function (element) {
                        if($('input[name="send_notification"]:checked').val() == 1) {
                            return true;
                        }
                    }
                },
                "specific_date": {
                    required: function (element) {
                        if($('#repeat option:selected').val() =='Specific') {
                            return true;
                        }
                    }
                },
            },
            errorPlacement: function(error, element) {
                if($(element).attr("id") == 'branchid' || $(element).attr("id") == 'roles_id' || $(element).attr("id") == 'send_notification_duration') {
                    error.insertAfter(element);
                } else if($(element).attr("name") == 'send_notification') {
                    element.parent().parent().append(error);
                }
            }
        });
    });
    $(document).ready(function() {
        $('#branchid').multiselect({
            includeSelectAllOption: true,
            enableClickableOptGroups: true
        });
        $('#roles_id').multiselect({
            includeSelectAllOption: true
        });
        $('#send_notification_duration').multiselect({
            includeSelectAllOption: true
        });
    });
</script>
@endsection
