<?php
    use App\Helpers\H;
    use App\Helpers\LD;

    $loggedInUser = H::getLoggedInUser();
    $userId = $loggedInUser['id'];
    $userId = 80; //temp until Rachel can test

	$mytime = Carbon\Carbon::now();
    H::ExcelLog('start view','coveragedbWhiteboard.log');

    $rowHtml = '';
    $totalHtml = '';

    $rowHtmlArray = [];
    $totalHtmlArray = [];

    define('TD', '<td></td>');
    define('ENDTD', '</td>');
    define('STARTTD', '<td>');
?>
@extends('layouts.myaccount')
@section('title', 'Coverage WhiteBoard')
@section('content')
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<style>
    td.showhand {
       cursor: pointer;
    }
    .table-responsive{
        overflow-x: inherit;
    }
    .sticky tbody{
        width: 100%;
        position: relative;
    }
    .sticky .table thead tr:first-child,.sticky ul.ui-tabs-nav {
        position: sticky;
        z-index: 30;
    }
    .sticky .table thead tr:first-child{
        top: 35px;
        background: #fff;
        width: 100%;
    }
    .sticky ul.ui-tabs-nav {
        top:0;
        width: 102%;
    }
    .ui-widget-header{
        background: #fff;
        border: none;
    }
    .modal-dialog {
        max-width:80%;
    }
    .candidateReport {
        cursor: pointer;
    }
</style>
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Coverage WhiteBoard</h1>
        </div>
    </div>

    <!-- Table -->
    <div>
        @if ($message = Session::get('success111'))
        <div class="alert alert-success alert-block">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <strong>{{ $message }}</strong>
        </div>
        @endif
        <div class="card-body">
			@php
				$days = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];
			@endphp
            <?php foreach($days as $day): ?>
            <?php
                $lastGroup = '';
                $lastState = '';
                $lastRegion = '';
                $stateNames = config('constant.dashboard_state');
                $totalList = array('state','grand');
                $totals = [];
                foreach($totalList as $total){
                    $totals[$total] = array('GCSH' => 0, 'CSH' => 0,'WCSH' => 0);
                }
                $groupCount = 0; //counting the groups within each state
                $isFirstRow = 1;
            ?>
            <?php foreach($wb as $row): ?>
                <?php
                    $currentGroup = $row['row_id'];
                    $currentState = substr($row['row_id'],0,2);
                    $currentRegion = isset($row['regionname']) ? $row['regionname'] : '';
                    if($isFirstRow==1){
                        $lastGroup = $currentGroup;
                        $lastState = $currentState;
                        $lastRegion = $currentRegion;
                        $isFirstRow = 0;
                    }
                    foreach($totalList as $total) {
                        $showTotal = 0;
                        if($total=='group'){
                            $title = $lastGroup;
                            $trStyle = 'background-color: #dbf0fd;color:#000;';
                            if($currentGroup!=$lastGroup || $currentState!=$lastState ) {
                                $showTotal = 1;
                            }
                        }elseif($total=='state'){
                            $title = '';
                            $myState = 'Undefined';
                            if(isset($stateNames[$lastState])) $myState = $stateNames[$lastState];
                            if (!LD::checkFeatureFlag('change-label-florida-whiteboard')) {
                                if ($myState == 'Florida') $myState = 'Florida PDN / CNA';
                            }
                            if($lastState!='') $title = $myState . ' Team Totals';
                            $trStyle = 'background-color: #215eac;color:#fff;';
                            if($currentState!=$lastState) $showTotal = 1;
                        }elseif($total=='regionname'){
                            $title = $lastRegion;
                            $trStyle = 'background-color: #D9D9D9;color:#000;';
                            if($currentRegion!=$lastRegion || $currentState!=$lastState ) {
                                $showTotal = 1;
                            }
                            if(trim($title)=='' && $totals[$total]['CSH']==0) $showTotal = 0;
                        }else{
                            $title = 'National Totals';
                            $trStyle = 'background-color: #D5D5D5;color:#000;';
                            if($row['row_id']=='9999')  $showTotal = 1;
                        }
                        if($showTotal==1){
                            //skip subtotal row on group if there is only one group in the state
                            if($total=='group') $groupCount++; //counting the groups within each state
                            if($total=='state') $groupCount = 0;
                            //if($total=='regionname') $groupCount = 0;
                            if(
                                $total!='regionname' ||
                                $total!='group' ||
                                $currentState==$lastState ||
                                $groupCount>1
                            ){
                                $totalHtml .= '<tr class="' . $total . '-totals '.($title == 'National Totals' ? '' : (($total == 'state') ? 'hidestate' : 'hide')).'" style="' . $trStyle . '">';
                                if ($total == 'regionname') {
                                    $regionnameTitle = explode(" | ", $title);
                                    //$totalHtml .= '<td colspan="4">' . $regionnameTitle[0]??'' . ENDTD;
                                } elseif ($total == 'state') {
                                    $totalHtml .= STARTTD . $title . ENDTD;
                                    $totalHtml .= '<td colspan="1"></td>';
                                    $totalHtml .= '<td colspan="1"></td>';
                                } else {
                                    $totalHtml .= STARTTD . $title . ENDTD;
                                    $totalHtml .= '<td colspan="1"></td>';
                                    $totalHtml .= '<td colspan="1"></td>';
                                }
                                $totalHtml .= STARTTD . number_format($totals[$total]['GCSH']) . ENDTD;
                                $pct = '';
                                if($totals[$total]['GCSH']!=0){
                                    $pct = round($totals[$total]['WCSH'] / $totals[$total]['GCSH'],2)*100;
                                    $totalHtml .= STARTTD . number_format($pct) . '%</td>';
                                }else{
                                    $totalHtml .= TD;
                                }
                                $totalHtml .= STARTTD . number_format($totals[$total]['CSH']) . ENDTD;
                                $totalHtml .= STARTTD . number_format($totals[$total]['WCSH']) . ENDTD;
                                $totalHtml .= '</tr>';
                                if ($total == 'group' || $total == 'regionname') {
                                    $totalHtmlArray[$day][$lastState][$total][] = $totalHtml;
                                } elseif ($total == 'grand') {
                                    $totalHtmlArray[$day]['9999'] = $totalHtml;
                                } else {
                                    $totalHtmlArray[$day][$lastState][$total][] = $totalHtml;
                                }
                                $totalHtml = "";
                            }
                            //zero out these totals
                            $totals[$total] = array('GCSH' => 0, 'CSH' => 0,'WCSH' => 0);
                        }
                    }
                    if($row['row_id']=='9999') break;
                    $lastGroup = $currentGroup;
                    $lastState = $currentState;
                    $lastRegion = $currentRegion;
                    if($row['show_row']==1  || $row['territory']!='Out of Territory'){
                        //accumulate totals
                        foreach($totalList as $total){
                            $totals[$total]['GCSH'] = $totals[$total]['GCSH'] + $row['candidate_start_hour_goals'];
                            $totals[$total]['CSH'] = $totals[$total]['CSH'] + $row[$day]['CSH'];
                            $totals[$total]['WCSH'] = $totals[$total]['WCSH'] + $row['wtd'][$day]['CSH'];
                        }
                    }
                ?>
                <?php
                    if($row['show_row']==1 || $row['territory']!='Out of Territory') {
                        if (LD::checkFeatureFlag('s16-whiteboard-changes')) {
                            $textStyle = '';
                            if($row['user_id']==$userId) $textStyle = ' style="color: #215eac;font-weight: bold;" ';
                        }else{
                            $textStyle = '';
                        }


                        $rowHtml .= '<tr'.$textStyle.' class="hide">';
                            $rowHtml .= TD;
                            $rowHtml .= STARTTD.$row['name'].ENDTD;
                            $rowHtml .= STARTTD.$row['roles'].ENDTD;
                            $rowHtml .= STARTTD.$row['candidate_start_hour_goals'].ENDTD;
                            $pct = '';
                            if($row['candidate_start_hour_goals']!=0){
                                $pct = round($row['wtd'][$day]['CSH'] / $row['candidate_start_hour_goals'],2)*100;
                                $bkstyle = '';
                                if($pct >= 60) $bkstyle = ' style="background-color: #FFCF68;" ';
                                if($pct >= 100) $bkstyle = ' style="background-color: #83E490;" ';

                                $rowHtml .= '<td' . $bkstyle . '>' . $pct . '%</td>';
                            }else{
                                $rowHtml .= TD;
                            }
                            if($row[$day]['CSH'] > 0){
                                $rowHtml .= str_replace('<td', '<td data-json=\''.json_encode(['user_id' => $row['user_id'], 'State' => $row['State'], 'date' => $row['wtd'][$day]['date'], 'type' => 'day']).'\' class="candidateReport"', STARTTD).$row[$day]['CSH'].ENDTD;
                            }else{
                                $rowHtml .= STARTTD.$row[$day]['CSH'].ENDTD;
                            }
                            if($row['wtd'][$day]['CSH'] > 0){
                                $rowHtml .= str_replace('<td', '<td data-json=\''.json_encode(['user_id' => $row['user_id'], 'State' => $row['State'], 'date' => $row['wtd'][$day]['date'], 'type' => 'wtd']).'\' class="candidateReport"', STARTTD).$row['wtd'][$day]['CSH'].ENDTD;
                            }else{
                                $rowHtml .= STARTTD.$row['wtd'][$day]['CSH'].ENDTD;
                            }
                        $rowHtml .= '</tr>';
                        $rowHtmlArray[$day][$currentState][] = $rowHtml;
                        $rowHtml = '';
                    }
                ?>
                <?php endforeach; ?>
            <?php endforeach; ?>
			<div id="tabs" style="display: none;">
				<ul>
					@foreach ($days as $day)
						<li><a href="#tabs-{{ $day }}">@php echo ucfirst($day); @endphp</a></li>
					@endforeach
				</ul>
				@foreach ($days as $day)
                    <?php H::ExcelLog($day,'coveragedbWhiteboard.log'); ?>
					<div class="table-responsive" id="tabs-{{ $day }}" style="display:none;">
                    <div class="float-left pt-2">
                        <a href="javascript:void(0)" onclick="collapseExpandAllTab(this)"><span class="badge badge-primary"><span>Expand</span> All State</span></a>
                    </div>
						<table class="table table-bordered" style="font-size: 12px !important;" id="whiteboard_{{ $day }}" width="100%" cellspacing="0">
							<thead>
								<tr>
    								<th>State</th>
                                    <th>Client Care Coordinator / Coverage Coordinator</th>
                                    <th>Role(s)</th>
                                    <th>Coverage<br/>Hours<br/>Goal</th>
                                    <th>% To<br/>Goal</th>
									<th>Coverage<br/>Hours Booked</th>
                                    <th>Weekly<br/>Total<br/>Coverage<br/>Hours</th>
								</tr>
							</thead>
							<tbody>
                                <?php
                                    //print_r($totalHtmlArray[$day]);
                                   //print_r($rowHtmlArray[$day]); exit;
                                    foreach ($totalHtmlArray[$day] as $state => $groupstatevalue) {
                                        if ($state == '9999') {
                                            echo $groupstatevalue;
                                            continue;
                                        }
                                        echo $groupstatevalue['state'][0];
                                        unset($groupstatevalue['state']);
                                        if (isset($rowHtmlArray[$day][$state])) {
                                            foreach (
                                                $rowHtmlArray[$day][$state] as $rowitems
                                            ) {
                                                echo $rowitems;
                                            }
                                        }
                                    }
                                ?>
							</tbody>
						</table>
					</div>
				@endforeach
			</div>
        </div>
    </div>
</div>
<!-- /.container-fluid -->

<script type="text/javascript" id="whiteboardreportsjs">
function collapseExpandAllTab(element) {
	if ($(element).find('span span').text() == 'Collapse') {
		$(element).find('span span').text('Expand');
        $('#' + $("#tabs .ui-tabs-panel:visible").attr("id") + ' tr.state-totals').removeClass('hidestate').click();
	} else {
		$(element).find('span span').text('Collapse');
        $('#' + $("#tabs .ui-tabs-panel:visible").attr("id") + ' tr.state-totals.hidestate').click();
	}
}
$(document).ready(function(){
	var index = $('#tabs a[href="#tabs-{{$mytime->format('l')}}"]').parent().index();
	$( "#tabs" ).tabs({ active: index, create: function(event, ui) { $('#tabs').show(); $('.tabs_content').show(); } });
});
$('.state-totals').click(function() {
    $(this).toggleClass('hidestate');
    var stateclass = $(this).hasClass('hidestate');
    if ($(this).nextAll('tr.state-totals').length > 0) {
        if (stateclass)
            $(this).nextUntil('tr.state-totals').not('.hide').addClass('hide');
        else
            $(this).nextUntil('tr.state-totals').removeClass('hide');
    } else {
        if (stateclass)
            $(this).nextUntil('tr.grand-totals').not('.hide').addClass('hide');
        else
            $(this).nextUntil('tr.grand-totals').removeClass('hide');
    }
});
$('.group-totals').click(function() {
    var foundStateTotal = false
    $(this).nextUntil('tr.group-totals').each(function() {
        if ($(this).attr('class') == 'state-totals' || $(this).attr('class') == 'state-totals hidestate') {
            foundStateTotal = true;
        }
    });
    if (foundStateTotal == false) {
        $(this)
        .nextUntil('tr.group-totals')
        .not('tr.regionname-totals')
        .not('tr.grand-totals')
        .toggleClass('hide');
    } else {
        $(this).nextUntil('tr.state-totals').not('tr.grand-totals').toggleClass('hide');
    }
});

$(window).scroll(function() {
    var scroll = $(window).scrollTop();
    if (scroll >= 80) {
        $("body").addClass("sticky");
    }else{
        $("body").removeClass("sticky");
    }
});
</script>
@endsection
