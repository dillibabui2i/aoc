<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use Illuminate\Support\Facades\DB;

    $loggedInUser = H::getLoggedInUser();
    $userId = $loggedInUser['id'];
    $userId = 80; //temp until Rachel can test

	$mytime = Carbon\Carbon::now();
    H::ExcelLog('start view','dbWhiteboard.log');
    $hasPermission = H::canPermissions('editableWhiteboard');

    $rowHtml = '';
    $totalHtml = '';

    $rowHtmlArray = [];
    $totalHtmlArray = [];
?>
@extends('layouts.myaccount')
@section('title', 'WhiteBoard')
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
</style>
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">WhiteBoard</h1>
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
                $totalList = array('group','state','regionname','grand');
                $totals = [];
                foreach($totalList as $total){
                    $totals[$total] = array('GCSH' => 0, 'CSH' => 0,'IPEA' => 0,'PEPI' => 0, 'WEPI' => 0, 'CM' => 0, 'CMH' => 0,
                                'WCSH' => 0, 'WIPEA' => 0, 'WPEPI' => 0, 'WWEPI' => 0, 'WCM' => 0, 'WCMH' => 0,
                                'GIPEA' => 0, 'GPEPIWEPI' => 0, 'GCM' => 0, 'GCMH' => 0, 'GCS' => 0);
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
                            if ($myState == 'Florida') $myState = 'Florida PDN / CNA';
                            if($lastState!='') $title = $myState . ' Team Totals';
                            $trStyle = 'background-color: #215eac;color:#fff;';
                            if($currentState!=$lastState) $showTotal = 1;
                        }elseif($total=='regionname'){
                            $title = $lastRegion;
                            $trStyle = 'background-color: #D9D9D9;color:#000;';
                            if($currentRegion!=$lastRegion || $currentState!=$lastState ) {
                                $showTotal = 1;
                            }
                            if(trim($title)=='' && $totals[$total]['CSH']==0 && $totals[$total]['IPEA']==0 && $totals[$total]['PEPI']==0 && $totals[$total]['WEPI']==0 && $totals[$total]['CM']==0 && $totals[$total]['CMH']==0) $showTotal = 0;
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
                            if($total!='group' || $currentState==$lastState || $groupCount>1){
                                $totalHtml .= '<tr class="' . $total . '-totals '.($title == 'National Totals' ? '' : (($total == 'state') ? 'hidestate' : 'hide')).'" style="' . $trStyle . '">';
                                if ($total == 'regionname') {
                                    //$totalHtml .= '<td></td>';
                                    $totalHtml .= '<td colspan="4">' . str_replace(" | ","<br />",$title) . '</td>';
                                    //$totalHtml .= '<td colspan="2"></td>';
                                } elseif ($total == 'state') {
                                    $totalHtml .= '<td colspan="4">'.$title.'</td>';
                                } else {
                                    $totalHtml .= '<td>' . $title . '</td>';
                                    $totalHtml .= '<td colspan="3"></td>';
                                }
                                $totalHtml .= '<td>' . number_format($totals[$total]['GCSH']) . '</td>';
                                $pct = '';
                                if($totals[$total]['GCSH']!=0){
                                    $pct = round($totals[$total]['WCSH'] / $totals[$total]['GCSH'],2)*100;
                                    $totalHtml .= '<td>' . number_format($pct) . '%</td>';
                                }else{
                                    $totalHtml .= '<td></td>';
                                }
                                $totalHtml .= '<td>' . number_format($totals[$total]['CSH']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['IPEA']).' </td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['PEPI']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['WEPI']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['CM']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['CMH']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['WCSH']) . '</td>';
                                $totalHtml .= '<td>' .number_format( $totals[$total]['GCS']) . '</td>';
                                if (LD::checkFeatureFlag('s16-whiteboard-changes')) {
                                    if(floatval($totals[$total]['GCSH'])!=0){
                                       // $variance = floatval($totals[$total]['GCSH']) - floatval($totals[$total]['WCSH']);
                                        $variance = floatval($totals[$total]['WCSH'])-floatval($totals[$total]['GCSH']);
                                        $totalHtml .= '<td>' . number_format($variance) . '</td>';
                                    }else{
                                        $totalHtml .= '<td></td>';
                                    }
                                }

                                $totalHtml .= '<td>' . number_format($totals[$total]['WIPEA']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['GIPEA']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['WPEPI']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['WWEPI']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['GPEPIWEPI']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['WCM']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['GCM']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['WCMH']) . '</td>';
                                $totalHtml .= '<td>' . number_format($totals[$total]['GCMH']) . '</td>';
                                $totalHtml .= '</tr>';
                                if ($total == 'group' || $total == 'regionname') {
                                    $totalHtmlArray[$day][$lastState][$lastGroup][$lastRegion][$total][] = $totalHtml;
                                } elseif ($total == 'grand') {
                                    $totalHtmlArray[$day]['9999'] = $totalHtml;
                                } else {
                                    $totalHtmlArray[$day][$lastState][$total][] = $totalHtml;
                                }
                                $totalHtml = "";
                            }
                            //zero out these totals
                            $totals[$total] = array('GCSH' => 0, 'CSH' => 0,'IPEA' => 0,'PEPI' => 0, 'WEPI' => 0, 'CM' => 0, 'CMH' => 0,
                                            'WCSH' => 0, 'WIPEA' => 0, 'WPEPI' => 0, 'WWEPI' => 0, 'WCM' => 0, 'WCMH' => 0,
                                            'GIPEA' => 0, 'GPEPIWEPI' => 0, 'GCM' => 0, 'GCMH' => 0, 'GCS' => 0);
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
                            $totals[$total]['IPEA'] = $totals[$total]['IPEA'] + $row[$day]['IPEA'];
                            $totals[$total]['PEPI'] = $totals[$total]['PEPI'] + $row[$day]['PEPI'];
                            $totals[$total]['WEPI'] = $totals[$total]['WEPI'] + $row[$day]['WEPI'];
                            $totals[$total]['CM'] = $totals[$total]['CM'] + $row[$day]['CM'];
                            $totals[$total]['CMH'] = $totals[$total]['CMH'] + $row[$day]['CMH'];
                            $totals[$total]['WCSH'] = $totals[$total]['WCSH'] + $row['wtd'][$day]['CSH'];
                            $totals[$total]['WIPEA'] = $totals[$total]['WIPEA'] + $row['wtd'][$day]['IPEA'];
                            $totals[$total]['WPEPI'] = $totals[$total]['WPEPI'] + $row['wtd'][$day]['PEPI'];
                            $totals[$total]['WWEPI'] = $totals[$total]['WWEPI'] + $row['wtd'][$day]['WEPI'];
                            $totals[$total]['WCM'] = $totals[$total]['WCM'] + $row['wtd'][$day]['CM'];
                            $totals[$total]['WCMH'] = $totals[$total]['WCMH'] + $row['wtd'][$day]['CMH'];
                            $totals[$total]['GIPEA'] = $totals[$total]['GIPEA'] + $row['ipea_goal'];
                            $totals[$total]['GPEPIWEPI'] = $totals[$total]['GPEPIWEPI'] + $row['pepi_wepi_goal'];
                            $totals[$total]['GCM'] = $totals[$total]['GCM'] + $row['client_match_goal'];
                            $totals[$total]['GCMH'] = $totals[$total]['GCMH'] + $row['client_match_hours_goal'];
                            $totals[$total]['GCS'] = $totals[$total]['GCS'] + $row['candidate_start_goal'];
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

                            $userDetail = DB::table('users')->where('id', $row['user_id'])->first();
                            $userActivationClass = 'activeUserAttendance';
                            if (($userDetail && $userDetail->ooo_from_date) && ($userDetail && $userDetail->ooo_to_date)) {
                                $todayDate = (new \DateTime())->format('Y-m-d');
                                $oooFromDate = (new \DateTime($userDetail->ooo_from_date))->format('Y-m-d');
                                $oooToDate = (new \DateTime($userDetail->ooo_to_date))->format('Y-m-d');

                                if (($todayDate >= $oooFromDate) && ($todayDate <= $oooToDate)) {
                                    $userActivationClass = 'inactiveUserAttendance';
                                }
                            }
                            $rowHtml .= '<tr'. $textStyle .' class="hide">';
                                $rowHtml .= '<td>'.$row['row_id'].'</td>';
                                $rowHtml .= '<td></td>';
								if (LD::checkFeatureFlag('whiteboard-attendance')) {
									if($hasPermission) {
										$rowHtml .= '<td class="userattendance'.$row['user_id'].'"><i class="fa fa-circle ' . $userActivationClass . '" aria-hidden="true"></i><a href="javascript:void(0)" onclick="setUserAttendance(\'' . H::getFormatedDate(($userDetail && $userDetail->ooo_from_date && $userDetail->ooo_from_date != '0000-00-00') ? $userDetail->ooo_from_date : date('Y-m-d')) . '\', \'' . H::getFormatedDate(($userDetail && $userDetail->ooo_to_date && $userDetail->ooo_to_date != '0000-00-00') ? $userDetail->ooo_to_date : date('Y-m-d')) . '\', \'' . (($userDetail && $userDetail->id) ? $userDetail->id : -1) . '\')" class="editable-click">'.$row['name'].'</a></td>';
									} else {
										$rowHtml .= '<td class="userattendance'.$row['user_id'].'"><i class="fa fa-circle ' . $userActivationClass . '" aria-hidden="true"></i>'.$row['name'].'</td>';
									}
								} else {
									$rowHtml .= '<td>'.$row['name'].'</td>';
								}
                                if(isset($row['id']) && $hasPermission) {
                                    $rowHtml .= '<td>
                                        <span class="att-value editable-text" data-emptytext="Click to add territory" data-type="text" data-url="'.route("editable").'" data-pk="'.$row['id'].'" data-name="territory" data-value="'.$row['territory'].'" data-table="whiteboradDetails">'.$row['territory'].'</span>
                                    </td>';
                                } else {
                                    $rowHtml .= '<td>'.$row['territory'].'</td>';
                                }
                                if(isset($row['id']) && $hasPermission) {
                                    $rowHtml .= '<td>
                                        <span class="att-value editable-text" data-emptytext="Click to add candidate start hours goal" data-type="number" data-url="'.route("editable").'" data-pk="'.$row['id'].'" data-name="candidate_start_hour_goals" data-value="'.$row['candidate_start_hour_goals'].'" data-table="whiteboradDetails">'.$row['candidate_start_hour_goals'].'</span>
                                    </td>';
                                } else {
                                    $rowHtml .= '<td>'.$row['candidate_start_hour_goals'].'</td>';
                                }
                                $pct = '';
                                if($row['candidate_start_hour_goals']!=0){
                                    $pct = round($row['wtd'][$day]['CSH'] / $row['candidate_start_hour_goals'],2)*100;
                                    $bkstyle = '';
                                    if($pct >= 60) $bkstyle = ' style="background-color: #FFCF68;" ';
                                    if($pct >= 100) $bkstyle = ' style="background-color: #83E490;" ';

                                    $rowHtml .= '<td' . $bkstyle . '>' . $pct . '%</td>';
                                }else{
                                    $rowHtml .= '<td></td>';
                                }
                                $rowHtml .= ($row[$day]['CSH']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',6,\'candidate_start_hour\')">' . $row[$day]['CSH'].' </td>':'<td>' . $row[$day]['CSH'].'</td>';
                                $rowHtml .= ($row[$day]['IPEA']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',1,\'\')">' . $row[$day]['IPEA'].' </td>':'<td>' . $row[$day]['IPEA'].'</td>';
                                $rowHtml .= ($row[$day]['PEPI']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',3,\'\' )">' . $row[$day]['PEPI'].' </td>':'<td>' . $row[$day]['PEPI'].'</td>';
                                $rowHtml .= ($row[$day]['WEPI']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',4,\'\')">' . $row[$day]['WEPI'].' </td>':'<td>' . $row[$day]['WEPI'].'</td>';
                                $rowHtml .= ($row[$day]['CM']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',6,\'\')">' . $row[$day]['CM'].' </td>':'<td>' . $row[$day]['CM'].'</td>';
                                $rowHtml .= ($row[$day]['CMH']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',6,\'cmh\')">' . $row[$day]['CMH'].' </td>':'<td>' . $row[$day]['CMH'].'</td>';
                                $rowHtml .= ($row['wtd'][$day]['CSH']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',6,\'weekly_candidate_start_hour\')">' . $row['wtd'][$day]['CSH'].' </td>':'<td>' . $row['wtd'][$day]['CSH'].'</td>';
                                if (isset($row['id']) && $hasPermission) {
                                    $rowHtml .= '<td>
                                        <span class="att-value editable-text" data-emptytext="Click to add candidate start goal" data-type="number" data-url="'.route("editable").'" data-pk="'.$row['id'].'" data-name="candidate_start_goal" data-value="'.$row['candidate_start_goal'].'" data-table="whiteboradDetails">'.$row['candidate_start_goal'].'</span>
                                    </td>';

                                } else {
                                    $rowHtml .= '<td>'.$row['candidate_start_goal'].'</td>';
                                }

                                if (LD::checkFeatureFlag('s16-whiteboard-changes')) {
                                    if(floatval($row['candidate_start_hour_goals'])!=0){
                                        //$variance = floatval($row['candidate_start_hour_goals']) - floatval($row['wtd'][$day]['CSH']);
                                        $variance = floatval($row['wtd'][$day]['CSH']) - floatval($row['candidate_start_hour_goals']);
                                        $rowHtml .= '<td>' . $variance . '</td>';
                                    }else{
                                        $rowHtml .= '<td></td>';
                                    }
                                }

                                $rowHtml .= ($row['wtd'][$day]['IPEA']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',1,\'weekly_ipea\')">' . $row['wtd'][$day]['IPEA'].' </td>':'<td>' . $row['wtd'][$day]['IPEA'].'</td>';
                                if (isset($row['id']) && $hasPermission) {
                                    $rowHtml .= '<td>
                                        <span class="att-value editable-text" data-emptytext="Click to add IPEA goal" data-type="number" data-url="'.route("editable").'" data-pk="'.$row['id'].'" data-name="ipea_goal" data-value="'.$row['ipea_goal'].'" data-table="whiteboradDetails">'.$row['ipea_goal'].'</span>
                                    </td>';

                                } else {
                                    $rowHtml .= '<td>'.$row['ipea_goal'].'</td>';
                                }
                                $rowHtml .= ($row['wtd'][$day]['PEPI']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',3,\'weekly_pepi\')">' . $row['wtd'][$day]['PEPI'].' </td>':'<td>' . $row['wtd'][$day]['PEPI'].'</td>';
                                $rowHtml .= ($row['wtd'][$day]['WEPI']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',4,\'weekly_wepi\')">' . $row['wtd'][$day]['WEPI'].' </td>':'<td>' . $row['wtd'][$day]['WEPI'].'</td>';
                                if (isset($row['id']) && $hasPermission) {
                                    $rowHtml .= '<td>
                                        <span class="att-value editable-text" data-emptytext="Click to add PEPI/WEPI goal" data-type="number" data-url="'.route("editable").'" data-pk=".'.$row['id'].'" data-name="pepi_wepi_goal" data-value="'.$row['pepi_wepi_goal'].'" data-table="whiteboradDetails">'.$row['pepi_wepi_goal'].'</span>
                                    </td>';

                                } else {
                                    $rowHtml .= '<td>'.$row['pepi_wepi_goal'].'</td>';
                                }
                                $rowHtml .= ($row['wtd'][$day]['CM']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',6,\'weekly_cm\')">' . $row['wtd'][$day]['CM'].' </td>':'<td>' . $row['wtd'][$day]['CM'].'</td>';
                                if (isset($row['id']) && $hasPermission) {
                                    $rowHtml .= '<td>
                                        <span class="att-value editable-text" data-emptytext="Click to add match goal" data-type="number" data-url="'.route("editable").'" data-pk="'.$row['id'].'" data-name="client_match_goal" data-value="'.$row['client_match_goal'].'" data-table="whiteboradDetails">'.$row['client_match_goal'].'</span>
                                    </td>';

                                } else {
                                    $rowHtml .= '<td>'.$row['client_match_goal'].'</td>';
                                }
                                $rowHtml .= ($row['wtd'][$day]['CMH']>0)?'<td class="showhand" ondblclick="getWhiteboardReport(\''.$day.'\',\''.$row['row_id'].'\',\''.$row['user_id'].'\',6,\'weekly_cmh\')">' . $row['wtd'][$day]['CMH'].' </td>':'<td>' . $row['wtd'][$day]['CMH'].'</td>';

                                if (isset($row['id']) && $hasPermission) {
                                    $rowHtml .= '<td>
                                        <span class="att-value editable-text" data-emptytext="Click to add match hours goal" data-type="number" data-url="'.route("editable").'" data-pk="'.$row['id'].'" data-name="client_match_hours_goal" data-value="'.$row['client_match_hours_goal'].'" data-table="whiteboradDetails">'.$row['client_match_hours_goal'].'</span>
                                    </td>';

                                } else {
                                    $rowHtml .= '<td>'.$row['client_match_hours_goal'].'</td>';
                                }
                            $rowHtml .= '</tr>';
                        $rowHtmlArray[$day][$currentState][$currentGroup][$currentRegion][] = $rowHtml;
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
                <?php
                    $filters[] = [
                        'label'=>'Exclude PRN',
                        'name'=>'excludeprn',
                        'type'=>'checkbox',
                        'value'=>false
                    ];
                    $sourceFilters = [];
                ?>
                    <div class="filters-common mt-3">
                        @component('components.report-filters',['filters' => $filters, 'sourceFilters' => $sourceFilters??''])
                        @endcomponent
                    </div>
				@foreach ($days as $day)
                    <?php H::ExcelLog($day,'dbWhiteboard.log'); ?>
					<div class="table-responsive" id="tabs-{{ $day }}" style="display:none;">
                    <div class="float-left pt-2">
                        <a href="javascript:void(0)" onclick="collapseExpandAllTab(this)"><span class="badge badge-primary"><span>Expand</span> All State</span></a>
                    </div>
						<table class="table table-bordered" style="font-size: 12px !important;" id="whiteboard_{{ $day }}" width="100%" cellspacing="0">
							<thead>
								<tr>
    								<th>State</th>
                                    <th>Region</th>
									<th>Recruiter</th>
									<th>Territory</th>
                                    <th>Candidate<br/>Start Hours<br/>Goal</th>
                                    <th>% To<br/>Goal</th>
									<th>Candidate<br/>Start<br/>Hours</th>
									<th>IPEAS</th>
									<th>PEPIS</th>
									<th>WEPIS</th>
									<th>CM</th>
									<th>CM Hours</th>
									<th>Weekly<br/>Total<br/>Candidate<br/>Start<br/>Hours</th>
                                    <th>Candidate<br/>Start<br/>Goal</th>
                                    <?php if (LD::checkFeatureFlag('s16-whiteboard-changes')): ?>
                                        <th>Candidate<br/>Start<br/>Variance</th>
                                    <?php endif; ?>
									<th>Weekly<br/>Total<br/>IPEAS</th>
                                    <th>IPEA<br/>Goal</th>
									<th>Weekly<br/>Total<br/>PEPIS</th>
									<th>Weekly<br/>Total<br/>WEPIS</th>
                                    <th>PEPI/WEPI<br/>Goal</th>
									<th>Weekly<br/>Total<br/>CM</th>
                                    <th>Client<br/>Match<br/>Goal</th>
									<th>Weekly<br/>Total<br/>CM Hours</th>
									<th>Client<br/>Match<br/>Hour Goal</th>
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
                                        foreach ($groupstatevalue as $group => $groupvalue) {
                                            $region = array_key_last($groupvalue);
                                            if (isset($groupvalue[$region]['group'])) {
                                                echo $groupvalue[$region]['group'][0];
                                                unset($groupvalue[$region]['group']);
                                            }
                                            foreach ($groupvalue as $regionname => $regionvalues) {
                                                //print_r($groupvalue);
                                                if ($state == 'TX') {
                                                    echo isset($groupvalue[$regionname]['regionname']) ? $groupvalue[$regionname]['regionname'][0] : '';
                                                }
                                                if (isset($rowHtmlArray[$day][$state][$group][$regionname])) {
                                                    foreach ($rowHtmlArray[$day][$state][$group][$regionname] as $key => $rowitems) {
                                                        echo $rowitems;
                                                    }
                                                }
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

<x-modal>
    <x-slot name="id">userAttendancePopup</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Out Of Office</x-slot>
    <x-slot name="extraHeader">
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <form id="userattendanceform">
            <input type="hidden" name="ooo_user_id" id="ooo_user_id" />
            <div class="row d-flex">
                <div class="col-md-12">
                    <div class="col-md-12 form-group">
                        <div class="col-md-3">From: </div>
                        <div class="col-md-9"><input type="text" readonly name="ooo_from_date" id="ooo_from_date" class="form-control"/></div>
                    </div>
                    <div class="col-md-12 form-group">
                        <div class="col-md-3">To: </div>
                        <div class="col-md-9"><input type="text" readonly name="ooo_to_date" id="ooo_to_date" class="form-control"/></div>
                    </div>
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="oooSaveButton" onclick="oooSaveButton()">Save</button>
        <button class="btn btn-primary" id="oooCancelButton" onclick="oooCancelButton()">Close</button>
        <button class="btn btn-primary" id="oooClearButton" onclick="oooClearButton()">Clear</button>
    </x-slot>
</x-modal>

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
        $(this).nextUntil('tr.group-totals').not('tr.grand-totals').toggleClass('hide');
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

function oooSaveButton() {
    $("#userattendanceform").validate({
        rules: {
            ooo_to_date: { greaterThan: "#ooo_from_date" }
        }
    });
    if ($('#ooo_to_date').valid()) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('whiteboard.saveOutOfOffice')}}",
            type: 'post',
            data: {
                ooo_user_id: $("#ooo_user_id").val(),
                ooo_from_date: $("#ooo_from_date").val(),
                ooo_to_date: $("#ooo_to_date").val(),
                _token: _token
            },
            success: function(data) {
                $('.userattendance' + data.ooo_user_id + ' i').removeClass('activeUserAttendance').removeClass('inactiveUserAttendance').addClass(data.userActivationClass);
                $('.userattendance' + data.ooo_user_id + ' a').attr('onclick', data.onclick);
                $('#userAttendancePopup').modal('hide');
            }
        });
    }
}

function oooCancelButton() {
    $('#userAttendancePopup').modal('hide');
}

function oooClearButton() {
    $("#ooo_from_date").val('');
    $("#ooo_to_date").val('');
}

$.validator.addMethod("greaterThan",
function(value, element, params) {

    if(value == '' && $(params).val() == '') {
        return true;
    }
    if (!/Invalid|NaN/.test(new Date(value))) {
        if (new Date(value).getTime() == new Date($(params).val()).getTime()) {
            return true;
        } else {
            return (new Date(value).getTime() > new Date($(params).val()).getTime());
        }
    }

    return isNaN(value) && isNaN($(params).val())
        || (Number(value) > Number($(params).val()));
},'To date must be greater than or equal to from date.');

function setUserAttendance(from_date, to_date, user_id)
{
    $("#tempModalCnt").remove();
    var $div = $('<div />').appendTo('body');
    $div.attr('id', 'tempModalCnt');
    $('#userAttendancePopup').modal('show');

    $('#ooo_user_id').val(user_id);

    $("#ooo_from_date").val(from_date).datepicker({
        dateFormat: 'mm/dd/yy',
        beforeShow: function (input, inst) {
            setTimeout(function () {
                $('#ui-datepicker-div').appendTo($(input).parent());
                inst.dpDiv.css({
                    top: 'initial',
                });
            }, 0);
        }
    });

    $("#ooo_to_date").val(to_date).datepicker({
        dateFormat: 'mm/dd/yy',
        beforeShow: function (input, inst) {
            setTimeout(function () {
                $('#ui-datepicker-div').appendTo($(input).parent());
                inst.dpDiv.css({
                    top: 'initial',
                });
            }, 0);
        }
    }).removeClass('error');

    $('#ooo_to_date-error').remove();
}
</script>
@endsection
