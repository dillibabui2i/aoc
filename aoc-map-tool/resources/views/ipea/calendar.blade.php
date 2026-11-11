<?php
    use App\Helpers\LD;
    use App\Helpers\Calendar;
    use Illuminate\Support\Facades\Session;
    $lastCalendarSearch = Session::get('lastCalendarSearch');
    $currentDate = isset($lastCalendarSearch['date']) && $lastCalendarSearch['date'] != '' ? $lastCalendarSearch['date'] : date('Y-m-d');

    $currentYear = date('Y');
    $years = [];
    for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
        $years[] = $i;
    }
    // $years = [2020,2021,2022,2023];
?>

@extends('layouts.myaccount')
@section('title', 'IPEA Calendar')
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

.virtual-ipea-badge {
    display: inline-block;
    margin-top: 5px;
    padding: 2.5px 12.5px;
    border-radius: 0;
    background: #3A99FF;
    color: #000;
    font-size: 13.75px;
}
</style>

<div class="calendar">
    <div class='col-md-2 pull-left'>
        <div class="form-group row">
            <label for=""> Select State </label>
            <select class="form-control" name="states" id="states">
                <option value=""> Select State </option>
                @foreach($states as $key => $state)
                <option
                    {{isset($lastCalendarSearch['state']) && $state['state'] == $lastCalendarSearch['state'] ? 'selected="selected"' : ''}}
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
                        {{isset($lastCalendarSearch['branch']) && $b['id'] == $lastCalendarSearch['branch'] ? 'selected="selected"' : ''}}
                        value="{{$b['id']}}">{{$b['branch']}}</option>
                    @endforeach
                </optgroup>
                @endforeach
            </select>
        </div>
        <div class="form-group row search-cnt hide">
            <label for=""> Search </label>
            <input class="form-control"
                value="{{isset($lastCalendarSearch['search']) ? $lastCalendarSearch['search'] : ''}}"
                placeholder="Search for"
                data-value="{{isset($lastCalendarSearch['searchData']) ? $lastCalendarSearch['searchData'] : ''}}"
                type="text" name="ipeaCalendarSearch" id="ipeaCalendarSearch" />
            <a class="clear-search" id="searchClear" href="javascript:void(0);">Clear</a>
            <input type="hidden" name="date" id="date"
                value="{{ isset($lastCalendarSearch['date']) && $lastCalendarSearch['date'] != '' ? $lastCalendarSearch['date'] : date('Y-m-d')}}" />
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
        <div class="col-md-12 text-left mt-3 p-0">
            <table class="role_categories_info table table-bordered">
                <tr>
                    <td style="background-color: #c4dbef;">&nbsp;</td><td>Nursing</td>
                </tr>
                <tr>
                    <td style="background-color: #f6d711;">&nbsp;</td><td>Therapy</td>
                </tr>
                <tr>
                    <td style="background-color: #e4a14d;">&nbsp;</td><td>Office</td>
                </tr>
                <tr>
                    <td style="background-color: #215eac;">&nbsp;</td><td>Branch Event</td>
                </tr>
            </table>
        </div>
        <div class="col-md-12 text-left mt-3 p-0">
            <h5 class="heading branch-notes">Branch Notes</h5>
            <span class="b-notes">

            </span>
        </div>
    </div>
</div>
<div id="weekEvents" class='col-md-10 pull-right weekly-calendar'>
    <h3 class="map-intro-text">Select a state from the drop down box to the left</h3>
</div>
</div>

<script>
$(function() {
    $('#states').trigger('change');
    $('#branchSelect').trigger('change');
});

$(document).on('click', '.event[data-id]', function(e) {
    if ($(this).attr('data-id') != 'undefined' && $(this).attr('data-id') != '') {
        updateNurseIpeaInfo($(this).attr('data-id'));
    }

});

@if(LD::checkFeatureFlag('neo-ipea-template-change'))
    $(document).on('click', '.office-therapy.type-997,.office-therapy.type-998', function(e) {
        if ($(this).attr('data-scheduleId') != 'undefined' && $(this).attr('data-scheduleId') != '') {
            neoIpeaTemplateDetails($(this).attr('data-scheduleId'));
        }
    });
@else
    $(document).on('click', '.office-therapy', function(e) {
        if ($(this).attr('data-scheduleId') != 'undefined' && $(this).attr('data-scheduleId') != '') {
            officeTherapyEventDetails($(this).attr('data-scheduleId'));
        }
    });
@endif
$(document).on('click', '.month-calendar-table td.day,#prev-week,#next-week', function(e) {
    var date = $(this).attr('rel');
    $('#date').val(date);
    $('.month-calendar-table td.day').removeClass('current-date');
    $('td.day[rel="' + date + '"]').addClass('current-date');
    getWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#ipeaCalendarSearch").attr(
            'data-value'),
        function(data) {

        });
});
$(document).on('change', '#branchSelect', function(e) {
    if ($(this).val() != '') {
        getWeekEvents($('#states').val(), $(this).val(), $("#date").val(), $("#ipeaCalendarSearch").attr(
            'data-value'), function(
            data) {

        });
    } else {


    }
});

$(document).on('change', '#states', function(e) {
    if ($(this).val() != '') {
        var self = $(this);
        $('.month-cnt').removeClass('hide');
        $('.search-cnt').removeClass('hide');
        $('.branch-cnt').removeClass('hide');
        getWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#ipeaCalendarSearch")
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
    $('#ipeaCalendarSearch').val('');
    $('#ipeaCalendarSearch').attr('data-value', '');
    getWeekEvents($('#states').val(), '', $("#date").val(), '', function(
        data) {
        $('#branchSelect').val('');
    });
});

function getWeekEvents(state, branch, date, search, cb) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ipea.getWeekEvents')}}",
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
            $('#weekEvents').html(data);
            $('.b-notes').html($("<div>" + data + "</div>").find("#branch-notes").text());
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
                getWeekEvents($('#states').val(), ui.item.branchId, ui.item.date, JSON.stringify(ui.item),
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
                getWeekEvents($('#states').val(), $('#branchSelect').val(), ui.item.date, JSON.stringify(ui
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
$(document).on("hidden.bs.modal", "#updateNurseIpeaInfoModal", function() {
    getWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#ipeaCalendarSearch").attr(
            'data-value'),
        function(data) {});
});
@if(LD::checkFeatureFlag('neo-ipea-template-change'))
$(document).on("hidden.bs.modal", "#neoIpeaTemplateModal", function() {
    getWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#ipeaCalendarSearch").attr(
            'data-value'),
        function(data) {});
});
@endif
function removeNeoSchedule(button,scheduleId,type_id) {
    var deleteUrl ='';
    if(type_id == 997) {
        deleteUrl = "{{route('schedules.removeNeoSchedule')}}";
    }else if(type_id == 998) {
        deleteUrl = "{{route('schedules.removeNeoSchedule')}}";
    }else if(type_id == 999) {
        deleteUrl = "{{route('schedules.removeOfficeEvents')}}";
    }
    let _token = $('meta[name="csrf-token"]').attr('content');
    button.disabled = true;
    button.innerHTML = 'Please wait...';
    $.ajax({
        url: deleteUrl,
        type: 'get',
        data: {
            'id':scheduleId,
            _token: _token
        },
        success: function(data) {
            button.disabled = false;
            button.innerHTML = 'Delete';
            if (data.status) {
                getWeekEvents($('#states').val(), $('#branchSelect').val(), $("#date").val(), $("#ipeaCalendarSearch").attr('data-value'),function(data) {});
                $('.modal').modal('hide');
            } else {
                $("#errorBody").html(data.message);
                $("#errorBody").show();
            }
        },
    });
}
// $(document).on('change', 'input[name="is_rehire"]', function(e) {
//     let _token = $('meta[name="csrf-token"]').attr('content');
//     var url = $('#is_rehire').attr('data-url');
//     console.log($(this).is(":checked"), '$(this).val()');
//     $.ajax({
//         url: url,
//         type: 'post',
//         data: {
//             name: $('#is_rehire').attr('data-name'),
//             value: $(this).is(":checked") ? 1 : 0,
//             pk: $('#is_rehire').attr('data-pk'),
//             table: $('#is_rehire').attr('data-table'),
//             _token: _token
//         },
//         dataType: 'json',
//         success: function(data) {
//             if (data.status == false) {
//                 console.log('Something went wrong!');
//             }
//         }
//     });
// });
</script>
@endsection
