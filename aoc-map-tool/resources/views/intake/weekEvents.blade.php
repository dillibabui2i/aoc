<?php
use App\Helpers\H;
use App\Helpers\LD;


?>
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
        @foreach(config('constant')['weeklyIntakeTime'] as $time)
        <tr>
            <td>{{substr($time, 0, -2)}}:00</td>
            @foreach(config('constant')['weekDays'] as $day)
            <td>
                @if(isset($scheduleData[$time][$day]) && !empty($scheduleData[$time][$day]))
                @foreach($scheduleData[$time][$day] as $key => $schedule)
                <?php
                $intakeLOB = 0;
                if(in_array($schedule->LOBName,['IHSS','PCS','Attendant Care'])) {
                    $intakeLOB = 1;
                }elseif(in_array($schedule->LOBName,['CNA','HHA','LHA'])) {
                    $intakeLOB = 2;
                }elseif(in_array($schedule->LOBName,['Arizona- Adult Oasis','PDN'])) {
                    $intakeLOB = 3;
                }elseif(in_array($schedule->LOBName,['PPEC'])) {
                    $intakeLOB = 4;
                }elseif(in_array($schedule->LOBName,['Skilled Visits'])) {
                    $intakeLOB = 5;
                }elseif(in_array($schedule->LOBName,['ABA','OT','PT','ST'])) {
                    $intakeLOB = 6;
                }
                ?>
                <div class="event intake-type-{{$intakeLOB}}" data-id="{{$schedule->id}}">
                   
                    @if($schedule->patient_name != '')
                    <p class="event-title">{{$schedule->patient_name}}</p>
                    @endif
                    @if(isset($schedule->name))
                    <p class="small mt-1">Appointment Type: {{$schedule->name}}</p>
                    @else
                    <p class="small mt-1">Appointment Type: Intake</p>
                    @endif

                    <p class="small">Time: {{H::getFormatedTime($schedule->IntakeStartTime)}}</p>
                    @if(isset($schedule->referral_type))
                    <p class="small">Referral Type: {{$schedule->referral_type}}</p>
                    @endif

                    <p class="small">LOB: {{$schedule->LOBName}}</p>
                    
                    @if($schedule->FacilityName != '')
                    <p class="small">Branch: {{$schedule->FacilityName}}</p>
                    @endif

                    @if($schedule->case_manager!='')
                    <p class="small">CM: {{$schedule->case_manager}}</p>
                    @endif
                    
                   
                </div>
                @endforeach
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach

    </table>
</div>
