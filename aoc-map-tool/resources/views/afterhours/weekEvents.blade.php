<?php
use App\Helpers\H;
?>
<div class="col-md-12 weekly-calendar-header d-flex">
    <div class="col-md-2 text-left">
        @canPermission(['manageafterhoursclass','manageafterhoursallclass'])
        <a title="Add Shift" href="javascript:addAfterHorusNewForm()"><i class="fa fa-plus-circle"></i></a>
        @endcanPermission
    </div>
    <div class="col-md-2 text-right">
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
        @foreach($afterhourShiftArray as $time)
        @if(isset($scheduleData[$time]) && !empty($scheduleData[$time]))
        <tr>
            <td></td>
            @foreach(config('constant')['weekDays'] as $day)
            <td>
                @if(isset($scheduleData[$time][$day]) && !empty($scheduleData[$time][$day]))
                @foreach($scheduleData[$time][$day] as $key => $schedule)
                @php

                $branchName = '';
                if(isset($schedule['location_id']) && $schedule['location_id'] != ''){
                $scheduleBranch = array_search($schedule['location_id'], array_column($branches, 'id'));
                $branchName = $branches[$scheduleBranch]['branch'];
                }
                $boxClass = $schedule['color_open'];
                if($schedule['user_id'] > 0) {
                    $boxClass = $schedule['color_filled'];
                }
                $fontColor = '#fff';
                $darkColorArray = ['F9D900','FFFF00','FEFE9A','FEFDCB','FFFA0A', 'FFEDC0', 'E2E4FF', 'FEFFD9', 'F7CDCC', '00FF27', 'DADBF9', 'FAFF48', 'B3E2DF', 'D1F7DE', 'C3FFCC', 'FFCFEE', 'C1F6FF', 'EFCDFF'];
                if (in_array($boxClass,$darkColorArray)) {
                    $fontColor = '#000';
                }
                $boxClass = '#'.$boxClass;
                @endphp
                <div class="event" style="color:{{$fontColor}};background:{{$boxClass}};" data-id="{{$schedule['id']}}">
                    @if($schedule['title'] != '')
                    <p class="event-title">{{$schedule['title']}}</p>
                    @endif
                    @if($schedule['user_id'] > 0)
                    <p class="small">{{$schedule['userName']}} {{ (isset($schedule['bilingual']) && $schedule['bilingual'] == 1) ? '*' : '' }}</p>
                    @endif
                    <p class="small">{{H::getFormatedTime($schedule['start_date'].' '.$schedule['start_time']) . ' - ' . H::getFormatedTime($schedule['end_date'].' '.$schedule['end_time'])}}</p>
                </div>
                @endforeach
                @endif
            </td>
            @endforeach
        </tr>
        @endif
        @endforeach

    </table>
</div>
