<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\Calendar;
    use Illuminate\Support\Facades\Auth;
    use Illuminate\Support\Facades\Session;
    $lastCalendarSearch = Session::get('lastCalendarSearch');
    $currentDate = isset($lastCalendarSearch['date']) && $lastCalendarSearch['date'] != '' ? $lastCalendarSearch['date'] : date('Y-m-d');
    $years = [2020,2021,2022,2023];
?>

@extends('layouts.myaccount')
@section('title', 'CCR Calendar')
@section('content')

<style>
.ui-autocomplete {
    max-height: 400px;
    overflow-y: auto;
}

.calendar .table {
    border-top: 1px solid #ddd;
}

.office-therapy {
    cursor: pointer;
}
.weekly-table .event.type-996 {
    background: #215eac;
    color: #fff;
}
.weekly-table .event .appointment-branch-badge {
    display: block;
    width: fit-content;
    margin-bottom: 3px;
    white-space: normal;
}
.weekly-table .event .prc-block-branch-badge {
    display: block;
    width: fit-content;
    margin-bottom: 3px;
    white-space: normal;
    background: #ffed4a;
    color: #b52b27;
}
.shifttype-cnt .multiselect-native-select,
.shifttype-cnt .btn-group,
.branch-cnt .multiselect-native-select,
.branch-cnt .btn-group {
    width: 100%;
    max-width: 100%;
}
.multiselect-container.dropdown-menu {
    width: 100%;
    overflow-x: hidden;
}
.multiselect-container.dropdown-menu.show {
    height: auto !important;
}
#prcstate {
    text-align-last: center;
}
#prcstate option {
    text-align: left;
}
</style>

<div class="calendar">
    <div class='col-md-2 pull-left'>
        <div class="form-group row">
            <label for="prcstate"> State </label>
            <select class="form-control" name="prcstate" id="prcstate">
                <option value="">Select State</option>
                @foreach($prcStates as $option)
                <option value="{{$option['value']}}">{{$option['text']}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group row branch-cnt">
            <label for="prcbranch"> Branch </label>
            <select multiple class="selectpicker" data-placeholder="Select Branch"
                name="prcbranch[]" id="prcbranch" disabled>
            </select>
        </div>
        <div class="form-group row shifttype-cnt">
            <label for="prcshifttype"> Shift Type </label>
            <select multiple class="selectpicker" data-placeholder="Select Shift Type"
                name="prcshifttype[]" id="prcshifttype">
                @foreach($prcShiftTypes as $shiftType)
                <option value="{{$shiftType->value}}">{{$shiftType->label()}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group row">
            @canPermission('ccrAvailability.manageOthers')
            @include('ccr.manageSchedule')
            @endcanPermission
        </div>
        @canPermission('ccrAvailability.manageOthers')
        @include('ccr.blockAvailabilityEditor')
        @endcanPermission
        <div class="form-group row search-cnt" style="display:none;">
            <div class="d-flex w-100">
                <label for=""> Search </label>
                <a title="Block Time" href="javascript:void(0);" onclick="addCCREvent();" style="display:none;"><i class="fa fa-plus-circle"></i></a>
            </div>
            <input class="form-control"
                value="{{isset($lastCalendarSearch['search']) ? $lastCalendarSearch['search'] : ''}}"
                placeholder="Search for"
                data-value="{{isset($lastCalendarSearch['searchData']) ? $lastCalendarSearch['searchData'] : ''}}"
                type="text" name="ipeaCalendarSearch" id="ipeaCalendarSearch" />
            <a class="clear-search" id="searchClear" href="javascript:void(0);">Clear</a>
        </div>
        <input type="hidden" name="date" id="date"
            value="{{ isset($lastCalendarSearch['date']) && $lastCalendarSearch['date'] != '' ? $lastCalendarSearch['date'] : date('Y-m-d')}}" />
</div>
<div id="weekEvents" class='col-md-10 pull-right weekly-calendar'>
    <h3 class="map-intro-text">Select a State from the drop down box to the left</h3>
</div>
</div>

<script>
$(function() {
    $('#prcshifttype').multiselect({
        includeSelectAllOption: true,
        buttonWidth: '100%'
    });
    $('#prcbranch').multiselect({
        includeSelectAllOption: true,
        buttonWidth: '100%'
    });
    if (!$('#prcstate').val()) {
        $('#prcbranch').multiselect('disable');
    }
    getWeekEvents($("#date").val(),$("#ipeaCalendarSearch").attr('data-value'), function(data) {});
});

$(document).on('change', '#prcbranch', function() {
    getWeekEvents($("#date").val(), $("#ipeaCalendarSearch").attr('data-value'), function(data) {});
});

$(document).on('change', '#prcshifttype', function() {
    getWeekEvents($("#date").val(), $("#ipeaCalendarSearch").attr('data-value'), function(data) {});
});

$(document).on('change', '#prcstate', function() {
    var state = $(this).val();
    let _token = $('meta[name="csrf-token"]').attr('content');
    $('#prcbranch').empty();
    if (state) {
        $.ajax({
            url: "{{ route('getstatebranch') }}",
            type: 'GET',
            data: {
                state: state,
                _token: _token
            },
            success: function(response) {
                $.each(response, function(index, value) {
                    $('#prcbranch').append('<option value="' + value.id + '">' + value.FacilityName + '</option>');
                });
                $('#prcbranch').multiselect('rebuild');
                $('#prcbranch').multiselect('enable');
                getWeekEvents($("#date").val(), $("#ipeaCalendarSearch").attr('data-value'), function(data) {});
            }
        });
    } else {
        $('#prcbranch').multiselect('rebuild');
        $('#prcbranch').multiselect('disable');
        getWeekEvents($("#date").val(), $("#ipeaCalendarSearch").attr('data-value'), function(data) {});
    }
});

$(document).on('click', '.event[data-id]', function(e) {
    if ($(this).attr('data-id') != 'undefined' && $(this).attr('data-id') != '') {
        getEventDetails($(this).attr('data-id'));
    }
});
function getEventDetails(scheduleId) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccrCalendar.getEventDetails')}}",
        type: 'get',
        data: {
            id: scheduleId,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#ccrEventDetailsModal').modal('show');
        }
    });
}
function addCCREvent() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccrCalendar.addEvent')}}",
        type: 'get',
        data: {
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addCCREventsModal').modal('show');
        }
    });
}

$(document).on('click', '.month-calendar-table td.day,#prev-week,#next-week', function(e) {
    var date = $(this).attr('rel');
    $('#date').val(date);
    $('.month-calendar-table td.day').removeClass('current-date');
    $('td.day[rel="' + date + '"]').addClass('current-date');
    getWeekEvents($("#date").val(), $("#ipeaCalendarSearch").attr('data-value'),function(data) {});
});

$(document).on('click', '#searchClear', function(e) {
    $('#ipeaCalendarSearch').val('');
    $('#ipeaCalendarSearch').attr('data-value', '');
    getWeekEvents($("#date").val(), '', function(data) {});
});

function getWeekEvents(date, search, cb) {
    if (!$('#prcstate').val()) {
        $('#weekEvents').html('<h3 class="map-intro-text">Select a State from the drop down box to the left</h3>');
        cb();
        return;
    }
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccrCalendar.getWeekEvents')}}",
        type: 'get',
        data: {
            date: date,
            search: search,
            state: $('#prcstate').val() || '',
            branch: $('#prcbranch').val() || [],
            shiftTypes: $('#prcshifttype').val() || [],
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $('#weekEvents').html(data);
            cb(data);
        }
    });
}


$("#ipeaCalendarSearch").autocomplete({
    source: "{{ route('ipea.getCalendarSearchString') }}",
    minLength: 2,
    select: function(event, ui) {
        if (ui.item.date != 'undefined' && ui.item.date != '') {
            if (ui.item.branchId != 'undefined' && ui.item.branchId != '' && ui.item.branchId != null) {
                getWeekEvents(ui.item.date, JSON.stringify(ui.item),
                    function(data) {
                        $("#branchSelect").find('option[selected]').removeAttr('selected');
                        $("#branchSelect").find('option[value="' + ui.item.branchId + '"]').attr(
                            'selected', 'selected');
                        $('.month-calendar-table td.day').removeClass('current-date');
                        $('td.day[rel="' + ui.item.date + '"]').addClass('current-date');
                        var slide = $('td.day[rel="' + ui.item.date + '"]').closest('.carousel-item')
                            .index();
                        $("#carouselContent").carousel(parseInt(slide));
                    });
            } else {
                getWeekEvents(ui.item.date, JSON.stringify(ui.item), function(data) {
                    $('.month-calendar-table td.day').removeClass('current-date');
                    $('td.day[rel="' + ui.item.date + '"]').addClass('current-date');
                    var slide = $('td.day[rel="' + ui.item.date + '"]').closest('.carousel-item')
                        .index();
                    $("#carouselContent").carousel(parseInt(slide));
                });
            }
        } else {
            return false;
        }
    }
});
</script>
@endsection
