<style>
.weekly-table .event.class-category-event {
    border-radius: 4px;
    padding: 5px 7px;
    margin-bottom: 2px;
    cursor: pointer;
    font-size: 11px;
    line-height: 1.4;
    background: color-mix(in srgb, var(--category-color) 20%, transparent);
    border-left: 3px solid var(--category-color);
}
.class-category-event .event-title {
    font-weight: 700;
    font-size: 11px;
}
.class-category-event-divider {
    height: 3px;
    border-radius: 2px;
    margin: 3px 0;
    background: var(--category-color);
}
.class-category-badge {
    background-color: color-mix(in srgb, var(--category-color) 25%, transparent);
}
</style>
<div class="col-md-12 weekly-calendar-header d-flex">
    <div class="col-md-4 text-right">
        <a href="javascript:void(0);" id="prev-week"
            rel="{{date('Y-m-d', strtotime('-2 day', strtotime($week['start'])))}}"><i
                class="fa fa-chevron-left"></i></a>
    </div>
    <div class="col-md-4">
        @if(isset($branchData['branch']) && $branchData['branch'] != '')
        <h3>{{$branchData['branch']}}</h3>
        @endif
        @if(isset($state) && $state != '' && !isset($branchData['branch']))
        <h3>{{$state}}</h3>
        @endif
        <h3>{{date('M d, Y',strtotime($week['start']))}} - {{date('M d, Y',strtotime($week['end']))}}</h3>
    </div>
    <div class="col-md-4 text-left">
        <a href="javascript:void(0);" id="next-week" rel="{{date('Y-m-d', strtotime($week['end'] . ' +2 day'))}}"><i
                class="fa fa-chevron-right"></i></a>
    </div>
</div>
<div class="col-md-12 branch-notes hide" id="branch-notes">
    @if(isset($branchData['IPEA_instructions']) && $branchData['IPEA_instructions'] != '')
    <p>
        {{  nl2br($branchData['IPEA_instructions'])}}
    </p>
    @endif
</div>
<div class="col-md-12 weekly-table p-0">
    <table class="table" aria-describedby="">
        <tr>
            <th></th>
            @php
            $i=0;
            @endphp
            @foreach(config('constant')['weekDays'] as $day)
            <th>{{$day}}
                <span class="notes text-muted small d-block">
                    {{date('M d',strtotime($week['start'].' +'.$i.' day'))}}
                </span>
            </th>
            @php
            $i++;
            @endphp
            @endforeach
        </tr>
        @foreach(config('constant')['weeklyTime'] as $time)
        <tr>
            <td>{{substr($time, 0, -2)}}:00</td>
            @foreach(config('constant')['weekDays'] as $day)
            <td>
                @if(isset($scheduleData[$time][$day]) && !empty($scheduleData[$time][$day]))
                @foreach($scheduleData[$time][$day] as $key => $schedule)
                <div class="event class-category-event" style="--category-color: {{ $schedule['is_full'] ? '#e3342f' : $schedule['class_category_color'] }};" data-id="{{$schedule['id']}}">
                    @if(!empty($schedule['is_recurring']))
                    <span class="recurring-indicator" title="Recurring class">&#128260;</span>
                    @endif
                    @if($schedule['class_category_name'] != '')
                    <p class="event-title">{{$schedule['class_category_name']}} Class</p>
                    <div class="class-category-event-divider"></div>
                    @endif
                    @if($schedule['class_type_label'] != '')
                    <span class="badge class-category-badge">{{$schedule['class_type_label']}}</span>
                    @endif
                    @if($schedule['instructor_name'] != '')
                    <p class="small text-muted mt-1">{{$schedule['instructor_name']}}</p>
                    @endif
                    @if($schedule['branch_name'] != '' && !isset($branchData['branch']))
                    <p class="small text-muted">{{$schedule['branch_name']}}</p>
                    @endif
                    <p class="small text-muted">{{$schedule['formatted_time']}}</p>
                    <p class="small text-muted">{{ $schedule['is_full'] ? 'Full' : $schedule['enrolled_count'].'/'.$schedule['capacity'] }}</p>
                </div>
                @endforeach
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach

    </table>
</div>
