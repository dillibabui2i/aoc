<?php
    use App\Helpers\H;
    use App\Helpers\Calendar;
    use Illuminate\Support\Facades\Session;
    $lastAfterHoursCalendarSearch = Session::get('lastAfterHoursCalendarSearch');
    $currentDate = isset($lastAfterHoursCalendarSearch['date']) && $lastAfterHoursCalendarSearch['date'] != '' ? $lastAfterHoursCalendarSearch['date'] : date('Y-m-d');
    $years = [2021,2022,2023,2024];
?>

@extends('layouts.myaccount')
@section('title', 'Shift Calendar')
@section('content')

<div class="calendar">
    <div class='col-md-2 pull-left'>
        <div class="form-group row">
            <label for=""> Select State </label>
            <select class="form-control" name="states" id="states">
                <option value=""> Select State </option>
                @foreach($states as $key => $state)
                <option
                    {{isset($lastAfterHoursCalendarSearch['state']) && $state['state'] == $lastAfterHoursCalendarSearch['state'] ? 'selected="selected"' : ''}}
                    value="{{$state['state']}}">{{$state['state']}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group row branch-cnt hide">
            <label for=""> Select Role </label>
            <select class="form-control" name="role_id" id="role_id">
                <option value=""> Select Role </option>
                @foreach($roles as $b)
                    <option class="value" {{isset($lastAfterHoursCalendarSearch['role_id']) && $b['id'] == $lastAfterHoursCalendarSearch['role_id'] ? 'selected="selected"' : ''}} value="{{$b['id']}}">{{$b['name']}}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group row branch-cnt hide">
            <label for=""> Select Department </label>
            <select class="form-control" name="department_name" id="department_name">
                <option value=""> Select Department </option>
                @foreach($departmentName as $b)
                    @if($b != '' && $b != '0')
                    <option class="value" {{isset($lastAfterHoursCalendarSearch['department_name']) && $b == $lastAfterHoursCalendarSearch['department_name'] ? 'selected="selected"' : ''}} value="{{$b}}">{{$b}}</option>
                    @endif
                @endforeach
            </select>
        </div>
        <div class="form-group row branch-cnt hide">
        <label for=""> Type </label>
            <div class="col-md-12 text-left mt-3 p-0 branch-cnt hide">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type" id="flexRadioDefault1" value="filled">
                    <label class="form-check-label" for="flexRadioDefault1">Filled</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type" id="flexRadioDefault2" value="notfilled">
                    <label class="form-check-label" for="flexRadioDefault2">Not Filled</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="type" id="flexRadioDefault3" value="all" checked>
                    <label class="form-check-label" for="flexRadioDefault3">All</label>
                </div>
            </div>
        </div>
        <div class="form-group row search-cnt hide">
            <input type="hidden" name="date" id="date" value="{{ isset($lastAfterHoursCalendarSearch['date']) && $lastAfterHoursCalendarSearch['date'] != '' ? $lastAfterHoursCalendarSearch['date'] : date('Y-m-d')}}" />
        </div>
        <div class="row month-cnt hide">
            <div id="carouselContent" class="carousel slide" data-interval="false" data-ride="carousel">
                <a class="carousel-control-prev" href="#carouselContent" role="button" data-slide="prev">
                    <span class="fa fa-chevron-left" aria-hidden="true"></span>
                    <span class="sr-only">Previous</span>
                </a>
                <a class="carousel-control-next" href="#carouselContent" role="button" data-slide="next">
                    <span class="fa fa-chevron-right" aria-hidden="true"></span>
                    <span class="sr-only">Next</span>
                </a>
                <div class="carousel-inner" role="listbox">
                    @foreach($years as $year)
                    @for($i=1; $i<=12; $i++) <div
                        class="carousel-item {{$i==date('m',strtotime($currentDate)) && $year==date('Y',strtotime($currentDate)) ? 'active' : '' }}">
                        <?php echo Calendar::getMonthOfCalendar($i,$currentDate,$year); ?>
                </div>
                @endfor
                @endforeach
            </div>
        </div>
    </div>
</div>
<div id="trachVentWeekEvents" class='col-md-10 pull-right weekly-calendar'>
    <h3 class="map-intro-text">Select a state from the drop down box to the left</h3>
</div>
</div>

<x-modal>
    <x-slot name="id">addAfterHorusNewFormModel</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">Schedule After Hours</x-slot>
    <x-slot name="class">add-triage-call-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<script>
function addAfterHorusNewForm() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('manageafterhours.addafterhours') }}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token
        },
        success: function(data) {
            $('#addAfterHorusNewFormModel .modal-body').html($(data).find('#afterhoursform').html());
            $('#addAfterHorusNewFormModel .modal-body button').attr('type', 'button');
            $('#addAfterHorusNewFormModel .modal-body button').attr('onclick', 'saveAfterHoursSchedule(this)');
            eval($(data).find('#addhoursjs').html());
            $('#addAfterHorusNewFormModel').modal('show');
        }
    });
}

function saveAfterHoursSchedule(button) {
    if($('#addAfterHorusNewFormModel #schedule_class_form').valid()) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        var data = $('#addAfterHorusNewFormModel #schedule_class_form').serializeObject();
        data['_token'] = _token;
        data['isAjax'] = true;
        button.disabled = true;
        $.ajax({
            url: $('#addAfterHorusNewFormModel #schedule_class_form').attr('action'),
            type: 'post',
            data: data,
            success: function(data) {
                button.disabled = false;
                if (data.success) {
                    $('#addAfterHorusNewFormModel .modal-body').empty().html('<div class="alert alert-success" role="alert">'+data.message+'</div>');
                    //$('#addAfterHorusNewFormModel').modal('hide');

                } else {
                    $('#addAfterHorusNewFormModel .modal-body').empty().html('<div class="alert alert-danger" role="alert">'+data.message+'</div>');
                    //console.log('Error:' + data.msg);
                }
            }
        });
    }
}
</script>

<script>
$(function() {
    $('#states').trigger('change');
    $('#role_id').trigger('change');
});
//Open class details
$(document).on('click', '.event[data-id]', function(e) {
    if ($(this).attr('data-id') != 'undefined' && $(this).attr('data-id') != '') {
        getAfterHoursInfo($(this).attr('data-id'));
    }
});
$(document).on('click', '.month-calendar-table td.day,#prev-week,#next-week', function(e) {
    var date = $(this).attr('rel');
    $('#date').val(date);
    $('.month-calendar-table td.day').removeClass('current-date');
    $('td.day[rel="' + date + '"]').addClass('current-date');
    getTrachVentWeekEvents($('#states').val(), $('#role_id').val(), $("#date").val(), $("#department_name").val(),$('input[name=type]:checked').val());
});
$(document).on('change', '#role_id', function(e) {
    if ($(this).val() != '') {
        getTrachVentWeekEvents($('#states').val(), $(this).val(), $("#date").val(), $("#department_name").val(),$('input[name=type]:checked').val());
    } else {
        getTrachVentWeekEvents($('#states').val(), '', $("#date").val(), $("#department_name").val(),$('input[name=type]:checked').val());
    }
});
$(document).on('change', '#department_name', function(e) {
    if ($(this).val() != '') {
        getTrachVentWeekEvents($('#states').val(), $('#role_id').val(), $("#date").val(), $(this).val(),$('input[name=type]:checked').val());
    } else {
        getTrachVentWeekEvents($('#states').val(),$('#role_id').val(), $("#date").val(),'',$('input[name=type]:checked').val());
    }
});
$(document).on('change', "input[name='type']", function(e) {
    console.log($(this).val());
    getTrachVentWeekEvents($('#states').val(), $('#role_id').val(), $("#date").val(), $("#department_name").val(),$('input[name=type]:checked').val());
});
$(document).on('change', '#states', function(e) {
    if ($(this).val() != '') {
        var self = $(this);
        $('.month-cnt').removeClass('hide');
        $('.search-cnt').removeClass('hide');
        $('.branch-cnt').removeClass('hide');
        getTrachVentWeekEvents($('#states').val(), $('#role_id').val(), $("#date").val(), $("#department_name").val(),$('input[name=type]:checked').val());
    } else {
        $('.month-cnt').addClass('hide');
        $('.search-cnt').addClass('hide');
        $('.branch-cnt').addClass('hide');
    }
});


function getTrachVentWeekEvents(state, role_id, date,department_name,shift_type) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('afterhours.getWeekEvents')}}",
        type: 'get',
        data: {
            state: state,
            role_id: role_id,
            department_name: department_name,
            date: date,
            type: shift_type,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $('#trachVentWeekEvents').html(data);
            $('.b-notes').html($("<div>" + data + "</div>").find("#branch-notes").text());
        }
    });
}
</script>
@if($redirect_afterhours_schedule_id > 0)
<script>$(function() { getAfterHoursInfo('{{ $redirect_afterhours_schedule_id }}');});</script>
@endif
@endsection
