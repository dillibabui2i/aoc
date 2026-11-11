<?php

use App\Helpers\H;
use App\Helpers\LD;
$branchLocation = H::getHelper('ConstantHelper')->getBranches()->toArray();
$branchLocation = H::arrGroupBy($branchLocation, 'state');
$trachVentScheduleClassClassTypeList = config('constant')['trachVentScheduleClassClassType'];
$isoWeekdays = ['1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '7' => 'Sunday'];
?>
@extends('layouts.myaccount')
@section('title', 'Manage Users')
@section('content')
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Schedule Trach/Vent Class</h1>
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
    <div id="enhancementSuggestionFormDiv">
        @if( isset($classDetail))
        <form method="POST" id="schedule_class_form" class="scaffold-form" enctype="multipart/form-data" action="{{ route('managetrachventclass.updatescheduleclass') }}">
        <input type="hidden" name="class_id" id="class_id" value="{{$classDetail->id}}">
        @else
        <form method="post" action="{{ route('managetrachventclass.savescheduleclass') }}" id="schedule_class_form" class="scaffold-form" enctype="multipart/form-data">
        @endif
            @csrf
                <div class="form-row">
                    <div class="form-group col-md-12" id="type_group">
                        <label for="city">Class</label>
                        <select name="type" id="type" class="form-control">
                            @foreach ($classCategories as $key => $item)
                            <option value="{{ $key }}" @if(isset($classDetail)) {{ ($classDetail->type == $key)?'selected':'' }} @endif>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12" id="location_group">
                        <label for="city">Branch</label>
                        <select id="location_id" name="location_id" class="form-control">
                            <option value="">-- Select One --</option>
                            @foreach($branchLocation as $key => $branch)
                            <optgroup label="{{$key}}">
                                @foreach($branch as $b)
                                <option value="{{$b['id']}}" @if(isset($classDetail)) {{ ($classDetail->location_id ==$b['id'])?'selected':'' }} @endif>{{$b['branch']}}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="instructor">Instructor</label>
                        <select id="instructor" name="instructor_id" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            @foreach ($instructorList as $item)
                                <option value="{{ $item['id'] }}" @if(isset($classDetail)) {{ ($classDetail->instructor_id == $item['id'])?'selected':'' }} @endif>{{ $item['name'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    @if(LD::checkFeatureFlag('add-new-field-class-type-trach-vent'))
                    <div class="form-group col-md-12">
                        <label for="class_type">Class Type</label>
                        <select id="class_type" name="class_type" class="form-control" autocomplete="off">
                            <option value="">-- Select One --</option>
                            @foreach ($trachVentScheduleClassClassTypeList as $key => $item)
                                <option value="{{  $key }}" @if(isset($classDetail)) {{ ($classDetail->class_type ==$key)?'selected':'' }} @endif>{{ $item }}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="form-group col-md-12">
                            <label for="city">Date</label>
                            <input type="input" class="form-control" id="class_date" name="class_date" @if(isset($classDetail)) value="{{ H::getFormatedDate($classDetail->class_date) }}"  @endif>
                    </div>
                    <div class="form-group col-md-12"">
                        <label for="city">Start Time</label>
                        <input type="input" class="form-control" id="class_time" name="class_time" @if(isset($classDetail))  value="{{ $classDetail->class_time}}" @endif>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="end_time">End Time</label>
                        <input type="input" class="form-control" id="end_time" name="end_time" @if(isset($classDetail) && $classDetail->end_time) value="{{ $classDetail->end_time }}" @endif>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="city">Class Capacity</label>
                        <input type="number" class="form-control" id="capacity" name="capacity" @if(isset($classDetail))  value="{{ $classDetail->capacity}}" @endif>
                    </div>
                    <div class="form-group col-md-12">
                            <label for="city">Notes</label>
                            <textarea class="form-control" id="notes" name="notes"> @if(isset($classDetail)) {{ $classDetail->notes}} @endif</textarea>
                    </div>
                    @if(!isset($classDetail))
                    <div class="form-group col-md-12">
                        <label for="repeat_every">Repeat Every</label>
                        <select id="repeat_every" name="repeat_every" class="form-control">
                            <option value="none" selected>None</option>
                            <option value="day">Day</option>
                            <option value="week">Week</option>
                            <option value="month">Month</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12" id="repeat_on_group" style="display:none;">
                        <label>Repeat On</label>
                        <div>
                            @foreach ($isoWeekdays as $isoDay => $label)
                            <label class="checkbox-inline" style="margin-right:10px;">
                                <input type="checkbox" name="repeat_on[]" value="{{ $isoDay }}"> {{ $label }}
                            </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="form-group col-md-12" id="repeat_until_group" style="display:none;">
                        <label for="repeat_until">Repeat Until</label>
                        <input type="input" class="form-control" id="repeat_until" name="repeat_until">
                    </div>
                    @elseif($classDetail->isRecurring())
                    <div class="form-group col-md-12">
                        <label>Recurrence</label>
                        <p class="form-control-static">
                            @if($classDetail->repeat_every === \App\Enums\TrachVentClassRepeatEvery::DAY)
                                Daily: {{ implode(', ', array_map(fn ($isoDay) => $isoWeekdays[$isoDay], $classDetail->repeatOnDays())) }}
                            @elseif($classDetail->repeat_every === \App\Enums\TrachVentClassRepeatEvery::WEEK)
                                Weekly
                            @elseif($classDetail->repeat_every === \App\Enums\TrachVentClassRepeatEvery::MONTH)
                                Monthly
                            @endif
                        </p>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="repeat_until">End Date</label>
                        <input type="input" class="form-control" id="repeat_until" name="repeat_until" value="{{ $classDetail->repeat_until?->format('m/d/Y') }}">
                    </div>
                    @endif
                    <div class="form-group col-md-12">
                        <button type="submit" id="savebtn" class="btn btn-primary">Submit</button>
                    </div>
                </div>
        </form>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#class_date').datepicker();
        $('#class_time, #end_time').timepicker({
            "step": 15,
            'forceRoundTime': true,
            'minTime': '06:00:00',
            'maxTime': '21:00:00'
        });
        if ($('#repeat_until').length && !$('#repeat_until').prop('readonly')) {
            $('#repeat_until').datepicker();
        }

        function toggleRecurrenceFields() {
            var repeatEvery = $('#repeat_every').val();
            $('#repeat_on_group').toggle(repeatEvery === 'day');
            $('#repeat_until_group').toggle(repeatEvery !== 'none');
        }

        if ($('#repeat_every').is('select')) {
            $('#repeat_every').on('change', toggleRecurrenceFields);
            toggleRecurrenceFields();
        }

    });
    // Wait for the DOM to be ready
    $(function() {
        $.validator.addMethod('endAfterStart', function(value) {
            var start = $('#class_time').timepicker('getTime');
            var end = $('#end_time').timepicker('getTime');
            return !start || !end || end > start;
        });
        $("#schedule_class_form").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "type": {
                    required: true
                },
                "location_id": {
                    required: true
                },
                "class_date": {
                    required: true
                },
                "class_time": {
                    required: true
                },
                "end_time": {
                    required: true,
                    endAfterStart: true
                },
                "capacity": {
                    required: true
                },
                "class_type": {
                    required: true
                },
                "instructor_id": {
                    required: true
                },
                "repeat_on[]": {
                    required: function() {
                        return $('#repeat_every').val() === 'day';
                    }
                },
                "repeat_until": {
                    required: function() {
                        return $('#repeat_every').val() !== 'none';
                    }
                }
            },
            messages: {
                "end_time": {
                    required: 'Select an end time.',
                    endAfterStart: 'End time must be later than the start time.'
                }
            },
            errorPlacement: function(error, element) {
                if (element.attr('name') === 'end_time') {
                    var group = element.closest('.form-group').css('position', 'relative');
                    error.css({
                        position: 'absolute',
                        top: element.position().top + element.outerHeight() + 2,
                        left: element.position().left,
                        'line-height': 'normal'
                    }).appendTo(group);
                }
            }
        });
    });
</script>
@endsection
