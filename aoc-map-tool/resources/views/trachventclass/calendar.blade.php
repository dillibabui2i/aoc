<?php
    use App\Helpers\LD;
    use App\Helpers\Calendar;
    use Illuminate\Support\Facades\Session;
    $lastTrachVentClassCalendarSearch = Session::get('lastTrachVentClassCalendarSearch');
    $currentDate = isset($lastTrachVentClassCalendarSearch['date']) && $lastTrachVentClassCalendarSearch['date'] != '' ? $lastTrachVentClassCalendarSearch['date'] : date('Y-m-d');
    $years = [2020,2021,2022,2023];
?>

@extends('layouts.myaccount')
@section('title', 'Class Calendar')
@section('content')

<div class="calendar">
    <div class='col-md-2 pull-left'>
        <div class="form-group row">
            <label for=""> Select State </label>
            <select class="form-control" name="states" id="states">
                <option value=""> Select State </option>
                @foreach($states as $key => $state)
                <option
                    {{isset($lastTrachVentClassCalendarSearch['state']) && $state['state'] == $lastTrachVentClassCalendarSearch['state'] ? 'selected="selected"' : ''}}
                    value="{{$state['state']}}">{{$state['state']}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group row branch-cnt hide">
            <label for=""> Select Branch </label>
            <select class="form-control" name="branchSelect" id="branchSelect">
                <option value=""> Select Branch </option>
                @foreach($branches as $key => $branch)
                <optgroup label="{{$key}}" class="value" data-state="{{$key}}">
                    @foreach($branch as $b)
                    <option class="value" data-state="{{$key}}"
                        {{isset($lastTrachVentClassCalendarSearch['branch']) && $b['id'] == $lastTrachVentClassCalendarSearch['branch'] ? 'selected="selected"' : ''}}
                        value="{{$b['id']}}">{{$b['branch']}}</option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>
        <div class="form-group row search-cnt hide">
            <label for=""> Search </label>
            <input class="form-control"
                value="{{isset($lastTrachVentClassCalendarSearch['search']) ? $lastTrachVentClassCalendarSearch['search'] : ''}}"
                placeholder="Search for"
                data-value="{{isset($lastTrachVentClassCalendarSearch['searchData']) ? $lastTrachVentClassCalendarSearch['searchData'] : ''}}"
                type="text" name="trachVentCalendarSearch" id="trachVentCalendarSearch" />
            <a class="clear-search" id="searchClear" href="javascript:void(0);">Clear</a>
            <input type="hidden" name="date" id="date"
                value="{{ isset($lastTrachVentClassCalendarSearch['date']) && $lastTrachVentClassCalendarSearch['date'] != '' ? $lastTrachVentClassCalendarSearch['date'] : date('Y-m-d')}}" />
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
        <div class="col-md-12 mt-3 p-0 class-category-legend">
            @foreach($classCategories->where('is_system', true) as $classCategory)
            <div class="class-category-legend-item">
                <div class="class-category-legend-dot" style="--category-color: {{$classCategory->color}};"></div>
                {{$classCategory->name}}
            </div>
            @endforeach
            <div class="class-category-legend-item">
                <div class="class-category-legend-dot" style="--category-color: #e3342f;"></div>
                At Capacity
            </div>
            @foreach($classCategories->where('is_system', false) as $classCategory)
            <div class="class-category-legend-item">
                <div class="class-category-legend-dot" style="--category-color: {{$classCategory->color}};"></div>
                {{$classCategory->name}}
            </div>
            @endforeach
        </div>
        <div class="col-md-12 text-left mt-3 p-0">
            <h5 class="heading branch-notes">Branch Notes</h5>
            <span class="b-notes">

            </span>
        </div>
    </div>
</div>
<div id="trachVentWeekEvents" class='col-md-10 pull-right weekly-calendar'>
    <h3 class="map-intro-text">Select a state from the drop down box to the left</h3>
</div>
</div>

<script>
$(function() {
    $('#states').trigger('change');
    $('#branchSelect').trigger('change');

    var urlParams = new URLSearchParams(window.location.search);
    var occurrenceId = urlParams.get('occurrence_id');
    if (occurrenceId) {
        openTrachVentClassFromNotification(occurrenceId);
    }
});

function openTrachVentClassFromNotification(occurrenceId) {
    getTrachVentClassInfo(occurrenceId, function($modalCnt) {
        var $info = $modalCnt.find('#trachVentClassInfoData');
        var state = $info.data('state');
        var branchId = $info.data('branch-id');
        var classDate = $info.data('class-date');

        $('#states').val(state || '').trigger('change');
        $('#date').val(classDate || $('#date').val());
        if (branchId) {
            $('#branchSelect').val(branchId).trigger('change');
        }
    });
}
//Open class details
$(document).on('click', '.event[data-id]', function(e) {
    if ($(this).attr('data-id') != 'undefined' && $(this).attr('data-id') != '') {
        getTrachVentClassInfo($(this).attr('data-id'));
    }

});
$(document).on('click', '.month-calendar-table td.day,#prev-week,#next-week', function(e) {
    var date = $(this).attr('rel');
    $('#date').val(date);
    $('.month-calendar-table td.day').removeClass('current-date');
    $('td.day[rel="' + date + '"]').addClass('current-date');
    getTrachVentWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#trachVentCalendarSearch").attr(
            'data-value'),
        function(data) {

        });
});
$(document).on('change', '#branchSelect', function(e) {
    if ($(this).val() != '') {
        getTrachVentWeekEvents($('#states').val(), $(this).val(), $("#date").val(), $("#trachVentCalendarSearch").attr(
            'data-value'), function(
            data) {
        });
    }
});

$(document).on('change', '#states', function(e) {
    if ($(this).val() != '') {
        var self = $(this);
        $('.month-cnt').removeClass('hide');
        $('.search-cnt').removeClass('hide');
        $('.branch-cnt').removeClass('hide');
        getTrachVentWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#trachVentCalendarSearch")
            .attr('data-value'),
            function(
                data) {
                $('#branchSelect').find('optgroup.value').addClass('hide');
                $('#branchSelect').find('option.value').addClass('hide');
                $('#branchSelect').find('option[data-state="' + self.val() + '"]').removeClass('hide');
                $('#branchSelect').find('optgroup[data-state="' + self.val() + '"]').removeClass('hide');
                $('#branchSelect').val('');
            });
    } else {
        $('.month-cnt').addClass('hide');
        $('.search-cnt').addClass('hide');
        $('.branch-cnt').addClass('hide');
    }
});

$(document).on('click', '#searchClear', function(e) {
    $('#trachVentCalendarSearch').val('');
    $('#trachVentCalendarSearch').attr('data-value', '');
    getTrachVentWeekEvents($('#states').val(), '', $("#date").val(), '', function(
        data) {
        $('#branchSelect').val('');
    });
});

function getTrachVentWeekEvents(state, branch, date, search, cb) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('trachventclass.getWeekEvents')}}",
        type: 'get',
        data: {
            state: state,
            branch: branch,
            date: date,
            search: search,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $('#trachVentWeekEvents').html(data);
            $('.b-notes').html($("<div>" + data + "</div>").find("#branch-notes").text());
            cb(data);
        }
    });
}


$("#trachVentCalendarSearch").autocomplete({
    source: "{{ route('trachventclass.getTrachVentCalendarSearchString') }}",
    minLength: 2,
    select: function(event, ui) {
        if (ui.item.date != 'undefined' && ui.item.date != '') {
            if (ui.item.branchId != 'undefined' && ui.item.branchId != '' && ui.item.branchId != null) {
                getTrachVentWeekEvents($('#states').val(), ui.item.branchId, ui.item.date, JSON.stringify(ui.item),
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
                getTrachVentWeekEvents($('#states').val(), $('#branchSelect').val(), ui.item.date, JSON.stringify(ui
                    .item), function(
                    data) {
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
$(document).on("hidden.bs.modal", "#getTrachVentClassInfoModal", function() {
    getTrachVentWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#trachVentCalendarSearch").attr(
            'data-value'),
        function(data) {});
});
</script>
<style>
.class-category-legend {
    display: flex;
    flex-direction: column;
}
.class-category-legend-item {
    display: flex;
    align-items: center;
    gap: 7px;
    margin-bottom: 6px;
    font-size: 12px;
}
.class-category-legend-dot {
    width: 14px;
    height: 14px;
    border-radius: 2px;
    flex-shrink: 0;
    background: color-mix(in srgb, var(--category-color) 20%, transparent);
    border-left: 3px solid var(--category-color);
}
</style>
@endsection
