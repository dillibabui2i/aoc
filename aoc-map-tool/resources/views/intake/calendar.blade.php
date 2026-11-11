<?php
    use App\Helpers\LD;
    use App\Helpers\H;
    use App\Helpers\Calendar;
    use Illuminate\Support\Facades\Session;
    $lastCalendarSearch = Session::get('lastCalendarSearch');
    $currentDate = isset($lastCalendarSearch['date']) && $lastCalendarSearch['date'] != '' ?
     $lastCalendarSearch['date'] : date('Y-m-d');
    $icList = H::getHelper("ConstantHelper")->getIntakeCoordinator();
    $lobName = H::getHelper('ConstantHelper')->getLob();
    $intakeCaseManager = H::getHelper('ConstantHelper')->getIntakeCaseManager();
    $currentYear = date('Y');
    $years = [];
    for ($i = $currentYear - 5; $i <= $currentYear + 5; $i++) {
        $years[] = $i;
    }
?>

@extends('layouts.myaccount')
@section('title', 'Intake Calendar')
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

.weekly-table .event.intake-type-1{
    background-color:    #f6d711;
    color: #000;
}
.weekly-table .event.intake-type-2{
    background-color:    #ef335e;
    color:#fff;
}
.weekly-table .event.intake-type-3{
    background-color:    #c4dbef;
}
.weekly-table .event.intake-type-4{
    background-color:    #4d4d4f;
    color:#fff;
}
.weekly-table .event.intake-type-5{
    background-color:    #e4a14d;
    color: #000;
}
.weekly-table .event.intake-type-6{
    background-color:    #215eac;
    color:#fff;
}
.event {
    text-align: left;
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
                    {{isset($lastCalendarSearch['state']) && $state['value'] == $lastCalendarSearch['state']
                    ? 'selected="selected"' : ''}}
                    value="{{$state['value']}}">{{$state['text']}}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group row branch-cnt">
            <label for=""> Select Branch </label>
            <select class="form-control" name="branchSelect" id="branchSelect">
                <option value=""> Select Branch </option>
                @foreach($branches as $key => $branch)
                    <option class="value" data-state="{{substr($branch['text'],0,2)}}"
                        {{isset($lastCalendarSearch['branch']) && $branch['value'] == $lastCalendarSearch['branch'] ?
                         'selected="selected"' : ''}}
                        value="{{$branch['value']}}">{{$branch['text']}}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group row lob-cnt">
            <label for=""> LOB </label>
            <select name="lob" id="lob" class="form-control">
                <option value="">--- Select ---</option>
                @foreach($lobName as $v)
                <option {{isset($lastCalendarSearch['lob']) && $v->LOBID == $lastCalendarSearch['lob']
                ? 'selected="selected"' : ''}} value="{{$v->LOBID}}">
                {{$v->LOBName}}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group row ic_id-cnt">
            <label for=""> Intake Coordinator </label>
            <select class="form-control" name="ic_id" id="ic_id">
                <option value="">-- Select One --</option>
                @foreach($icList as $ic)
                    <option {{isset($lastCalendarSearch['ic_id']) && $ic['value'] == $lastCalendarSearch['ic_id']
                    ? 'selected="selected"' : ''}}
                     value="{{$ic['value']}}">{{$ic['text']}}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group row case_manager-cnt">
            <label for=""> Case Manager</label>
            <select class="form-control" name="case_manager" id="case_manager">
                <option value="">-- Select One --</option>
                @foreach($intakeCaseManager as $ic)
                    
                    <option {{isset($lastCalendarSearch['case_manager']) && $ic['value']
                     == $lastCalendarSearch['case_manager']
                    ? 'selected="selected"' : ''}}
                    value="{{$ic['value']}}">{{$ic['text']}}</option>
                @endforeach
            </select>
        </div>

        <div class="form-group row search-cnt">
            <label for=""> Search </label>
            <input class="form-control"
                value="{{isset($lastCalendarSearch['search']) ? $lastCalendarSearch['search'] : ''}}"
                placeholder="Search for"
                data-value="{{isset($lastCalendarSearch['searchData']) ? $lastCalendarSearch['searchData'] : ''}}"
                type="text" name="ipeaCalendarSearch" id="ipeaCalendarSearch" />
            <a class="clear-search" id="searchClear" href="javascript:void(0);">Clear</a>
            <input type="hidden" name="date" id="date"
                value="{{ isset($lastCalendarSearch['date']) && $lastCalendarSearch['date'] != ''
                 ? $lastCalendarSearch['date'] : date('Y-m-d')}}" />
        </div>
        <div class="row month-cnt">
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
                        class="carousel-item {{$i==date('m',strtotime($currentDate)) &&
                         $year==date('Y',strtotime($currentDate)) ? 'active' : '' }}">
                        <?php echo Calendar::getMonthOfCalendar($i,$currentDate,$year); ?>
                </div>
                @endfor
                @endforeach
            </div>
        </div>
        <div class="col-md-12 text-left mt-3 p-0">
            <table class="role_categories_info table table-bordered">
                <tr>
                    <th style="background-color:#f6d711;">&nbsp;</td><td>IHSS, PCS, Attendant Care</th>
                </tr>
                <tr>
                    <th style="background-color: #ef335e;">&nbsp;</td><td>CNA, HHA, LHA</th>
                </tr>
                <tr>
                    <th style="background-color: #c4dbef;">&nbsp;</td><td>Arizona- Adult Oasis, PDN</th>
                </tr>
                <tr>
                    <th style="background-color: #4d4d4f;">&nbsp;</td><td>PPEC</th>
                </tr>
                <tr>
                    <th style="background-color: #e4a14d;">&nbsp;</td><td>Skilled Visits</th>
                </tr>
                <tr>
                    <th style="background-color: #215eac;">&nbsp;</td><td>OT, PT, ST</th>
                </tr>
            </table>
        </div>
       
    </div>
</div>
<div id="weekEvents" class='col-md-10 pull-right weekly-calendar'>
    <h3 class="map-intro-text">Select a state from the drop down box to the left</h3>
</div>
</div>

<script>
$(function() {
    getWeekEvents( $('#states').val(),
             $('#branchSelect').val(),
             $("#date").val(),
             $("#ipeaCalendarSearch").attr('data-value'),
             $('#lob').val(),
             $('#ic_id').val(),
             $('#case_manager').val(), function(data) {});
});
$(function() {
    $('#states').trigger('change');
    $('#branchSelect').trigger('change');
});

$(document).on('click', '.event[data-id]', function(e) {
    if ($(this).attr('data-id') != 'undefined' && $(this).attr('data-id') != '') {
       
        getClientDetails($(this).attr('data-id'),'clientintake');
    }

});

$(document).on('click', '.month-calendar-table td.day,#prev-week,#next-week', function(e) {
    var date = $(this).attr('rel');
    $('#date').val(date);
    $('.month-calendar-table td.day').removeClass('current-date');
    $('td.day[rel="' + date + '"]').addClass('current-date');
    getWeekEvents( $('#states').val(),
             $('#branchSelect').val(),
             $("#date").val(),
             $("#ipeaCalendarSearch").attr('data-value'),
             $('#lob').val(),
             $('#ic_id').val(),
             $('#case_manager').val(),
        function(data) {

        });
});
$(document).on('change', '#branchSelect,#lob,#ic_id,#lob,#case_manager', function(e) {
    
        getWeekEvents( $('#states').val(),
             $('#branchSelect').val(),
             $("#date").val(),
             $("#ipeaCalendarSearch").attr('data-value'),
             $('#lob').val(),
             $('#ic_id').val(),
             $('#case_manager').val(), function(
            data) {

        });
    
});

$(document).on('change', '#states', function(e) {
    
        var self = $(this);
        $('.month-cnt').removeClass('hide');
        $('.search-cnt').removeClass('hide');
        $('.branch-cnt').removeClass('hide');
        $('.lob-cnt').removeClass('hide');
        $('.ic_id-cnt').removeClass('hide');
        $('.case_manager-cnt').removeClass('hide');
        $('#branchSelect').find('option.value').removeClass('hide');
        getWeekEvents(
            $('#states').val(),
             $('#branchSelect').val(),
             $("#date").val(),
             $("#ipeaCalendarSearch").attr('data-value'),
             $('#lob').val(),
             $('#ic_id').val(),
             $('#case_manager').val(),
            function(
                data) {
                    //$('#branchSelect').find('option[data-state="'+self.val()+'"]').addClass("hide");
                    //$('#branchSelect').find('optgroup.value').addClass('hide');
                    if (self.val() != '') {
                        $('#branchSelect').find('option.value').addClass('hide');
                        $('#branchSelect').find('option[data-state="'+self.val()+'"]').removeClass("hide");
                        $('#branchSelect').val('');
                    }
                   

            });
    
});

$(document).on('click', '#searchClear', function(e) {
    $('#ipeaCalendarSearch').val('');
    $('#ipeaCalendarSearch').attr('data-value', '');
    getWeekEvents($('#states').val(),
             '',
             $("#date").val(),
             '',
             '',
             '',
             '', function(
        data) {
            $('#states').val(),
            $('#branchSelect').val(),
            $('#lob').val(''),
            $('#ic_id').val(''),
            $('#case_manager').val(''),
            $('#branchSelect').val('');
    });
});

function getWeekEvents(state, branch, date, search, lob, ic_id,case_manager, cb) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('intake.getWeekEvents')}}",
        type: 'get',
        data: {
            state: state,
            branch: branch,
            date: date,
            search: search,
            lob: lob,
            ic_id: ic_id,
            case_manager: case_manager,
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
    source: "{{ route('intake.getCalendarSearchString') }}",
    minLength: 2,
    select: function(event, ui) {
        console.log(ui.item);
        if (ui.item.date != 'undefined' && ui.item.date != '') {
            if (ui.item.branchId != 'undefined' && ui.item.branchId != '' && ui.item.branchId != null) {
                $("#states").val(ui.item.state);
                $("#branchSelect").val(ui.item.branchId);
                getWeekEvents($('#states').val(), ui.item.branchId, ui.item.date, JSON.stringify(ui.item), $('#lob').val(),
             $('#ic_id').val(),
             $('#case_manager').val(),
                    function(data) {
                        
                        $('.month-calendar-table td.day').removeClass('current-date');
                        $('td.day[rel="' + ui.item.date + '"]').addClass('current-date');
                        var slide = $('td.day[rel="' + ui.item.date + '"]').closest('.carousel-item')
                            .index();
                        $("#carouselContent").carousel(parseInt(slide));
                    });
            } else {
                getWeekEvents($('#states').val(), $('#branchSelect').val(), ui.item.date, JSON.stringify(ui
                    .item), $('#lob').val(),
             $('#ic_id').val(),
             $('#case_manager').val(),function(
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
</script>
@endsection
