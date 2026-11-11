<?php
use App\Helpers\H;
$canEditPrcBlocks = H::canPermissions('ccrAvailability.manageOthers');
?>
<div class="col-md-12 weekly-calendar-header d-flex">
    <div class="col-md-4 text-right">
        <a href="javascript:void(0);" id="prev-week"
            rel="{{date('Y-m-d', strtotime('-2 day', strtotime($week['start'])))}}"><i
                class="fa fa-chevron-left"></i></a>
    </div>
    <div class="col-md-4">
        @if(!empty($stateName))
        <h3>{{$stateName}}</h3>
        @endif
        <h3>{{date('M d, Y',strtotime($week['start']))}} - {{date('M d, Y',strtotime($week['end']))}}</h3>
    </div>
    <div class="col-md-4 text-left">
        <a href="javascript:void(0);" id="next-week" rel="{{date('Y-m-d', strtotime($week['end'] . ' +2 day'))}}"><i
                class="fa fa-chevron-right"></i></a>
    </div>
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
                @php
                $branchName = '';
                if(isset($schedule->ipea_location_id) && $schedule->ipea_location_id != ''){
                $scheduleBranch = array_search($schedule->ipea_location_id, array_column($branches, 'id'));
                $branchName = $branches[$scheduleBranch]['branch'];
                }
                @endphp
                <div class="event" data-id="{{$schedule->id}}">
                    @if(!empty($schedule->branch_name))
                    <span class="badge badge-info appointment-branch-badge">{{$schedule->branch_name}}</span>
                    @endif
                    @if($schedule->candidate != '')
                    <p class="event-title">{{$schedule->candidate}}</p>
                    @endif
                    @if($schedule->client != '')
                    <p class="small text-muted mt-1">{{$schedule->client}}</p>
                    @endif
                    @if($branchName != '')
                    <p class="small text-muted">{{$branchName}}</p>
                    @endif
                    <p class="small text-muted">{{H::getFormatedTime($schedule->appointment_date.'
                        '.$schedule->appointment_time)}}</p>
                </div>
                @endforeach
                @endif
                @if(isset($blockData[$time][$day]) && !empty($blockData[$time][$day]))
                @foreach($blockData[$time][$day] as $block)
                <div class="event prc-blocked-event" data-block-id="{{$block->id}}"
                    data-block-date="{{$block->block_date}}"
                    data-start-time="{{substr($block->start_time, 0, 5)}}"
                    data-end-time="{{substr($block->end_time, 0, 5)}}"
                    data-note="{{ e($block->note) }}"
                    @if($canEditPrcBlocks) onclick="openBlockEditor(this);" style="background:#d9534f;color:#fff;border:1px solid #b52b27;cursor:pointer;"
                    @else style="background:#d9534f;color:#fff;border:1px solid #b52b27;"
                    @endif>
                    @if(!empty($block->facility_name))
                    <span class="badge badge-light prc-block-branch-badge">{{ $block->facility_name }}</span>
                    @endif
                    <p class="event-title mb-0">
                        <strong>BLOCKED{{!empty($block->note) ? ' - '.$block->note : ''}}</strong>
                    </p>
                    <p class="small mt-1 mb-0" style="color:#f6d7d6;">
                        {{getFormatedTime($block->block_date.' '.$block->start_time)}}
                        &ndash;
                        {{getFormatedTime($block->block_date.' '.$block->end_time)}}
                    </p>
                </div>
                @endforeach
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach

    </table>
</div>
