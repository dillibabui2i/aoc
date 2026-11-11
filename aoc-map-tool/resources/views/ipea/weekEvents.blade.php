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
                @if($schedule->type_id == 999)
                <div class="event office-therapy created type-{{$schedule->type_id}}" data-scheduleId="{{$schedule->id}}">
                    @if($schedule->title != '')
                    <p class="event-title">{{$schedule->title}}</p>
                    @endif
                    @if($schedule->description != '')
                    <p class="small">{{H::getFormatedTime($schedule->appointment_time)}} - {{H::getFormatedTime($schedule->appointment_end_time)}}</p>
                    <p class="small event-description">
                        {!!$schedule->description!!}
                    </p>
                    @endif
                </div>
                @elseif($schedule->type_id == 997 || $schedule->type_id == 998)
                @php
                $descriptionArr = H::isJSON($schedule->description) ? json_decode($schedule->description,true) : '';
                @endphp
                <div class="event office-therapy type-{{$schedule->type_id}}" data-scheduleId="{{$schedule->id}}">
                    @if(isset($descriptionArr['First Name']) || isset($descriptionArr['Last Name']))
                    <p class="event-title">{{isset($descriptionArr['First Name']) ? $descriptionArr['First Name'] :''}}
                        {{isset($descriptionArr['Last Name']) ? $descriptionArr['Last Name'] :''}}</p>
                    @endif
                    @if(isset($descriptionArr['Position']))
                    <p class="small text-muted mt-1">{{$descriptionArr['Position']}}</p>
                    @endif
                    @if(isset($descriptionArr['Discipline']))
                    <p class="small text-muted mt-1">{{$descriptionArr['Discipline']}}</p>
                    @endif
                    @if($branchName != '')
                    <p class="small text-muted">{{$branchName}}</p>
                    @endif
                    <p class="small text-muted">{{H::getFormatedTime($schedule->appointment_date.'
                        '.$schedule->appointment_time)}}</p>
                </div>
                @else
                <div class="event" data-id="{{$schedule->id}}">
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
                    @if(LD::checkFeatureFlag('add-rehire-on-ipea-cal-card'))
                    <p class="small text-primary">{{ isset($schedule->is_rehire) && $schedule->is_rehire != 0 ? 'Rehire':''}} </p>
                    @endif
                    @if(LD::checkFeatureFlag('show-in-home-on-ipea-calendar'))
                        {!! isset($schedule->intake_in_home) && $schedule->intake_in_home != 0 ?
                            '<p class="small text-primary">In Home</p>':'' !!}
                    @endif
                    @if(LD::checkFeatureFlag('show-virtual-ipea'))
                        @if(isset($schedule->is_virtual) && $schedule->is_virtual !== 0)
                        <span class="virtual-ipea-badge">Virtual</span>
                        @endif
                    @endif
                    @if(LD::checkFeatureFlag('cross-state-dynamic-email'))
                        @if($schedule->cross_state)
                            <span class='tag cross-state'>Cross-State</span>
                        @endif
                    @endif
                </div>
                @endif
                @endforeach
                @endif
            </td>
            @endforeach
        </tr>
        @endforeach

    </table>
</div>
