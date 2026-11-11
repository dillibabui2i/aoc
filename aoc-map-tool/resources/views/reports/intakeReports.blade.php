<?php
    use App\Helpers\H;
    use App\Helpers\Reports;
    use App\Helpers\LD;

	define('BLANKTD', '<td></td>');
	$maxLevel = 0;
	$groupLevelName = [];
    $lastValue = [];
    $currentValue = [];
    foreach($report['groupby'] as $group){
        $lastValue[$group['id']] = '';
        $currentValue[$group['id']] = '';
		if (!empty($group['showCols']) && $group['level'] > $maxLevel) {
			$maxLevel = $group['level'];
			$key = array_search($group['field'], array_column($report['columns'], 'field'));
			$groupLevelName[$group['level']] = $report['columns'][$key]['heading']['title'];
		}
    }
	$states = [];
	$tmp = H::getHelper("ConstantHelper")->getDashboardState();
	foreach($tmp as $key => $value){
		$states[] = $value['value'];
	}
    $subtotals = [];
	$rowCount = [];
	$sixWeekCount = [];
	$sixWeekTotals = [];
	$thirteenWeekCount = [];
	$thirteenWeekTotals = [];
    foreach($report['groupby'] as $group){
        foreach($report['columns'] as $column){
            if($column['totals']['formula']!=''){
                $subtotals[$group['id']][$column['field']] = 0;
				$rowCount[$group['id']][$column['field']] = 0;
            }
        }
		$sixWeekCount[$group['id']] = 0;
		$sixWeekTotals[$group['id']] = 0;
		$thirteenWeekCount[$group['id']] = 0;
		$thirteenWeekTotals[$group['id']] = 0;
    }
	H::ExcelLog(print_r($data,true),'znrrgm.log');
	$groupCount = 0; //counting the groups within each state
    $firstRowDone = 0; //set this to 1 after we load the first row and set $lastValue for grouping
    $output = [];
    $output[1] = '';
    $output[2] = '';
    $output[3] = '';
    $output[4] = '';
    $output[9] = '';
    $finalOutput = '';

	$count1 = $count2 = $count3 = $count4 = $count9 = 0;
?>
@extends('layouts.myaccount')
@section('title', 'National Intake')
@section('content')

<!-- Begin Page Content -->
<div class="container-fluid nrrreport-main report-sticky-compact">

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
			$types = [
				'intakeReport.referrals' => 'Referrals',
				'intakeReport.admits' => 'Admits',
				'intakeReport.conversion' => 'Conversion Report',
				'intakeReport.nonadmit' => 'Non Admits',
			];
			@endphp
			<div class="top-sticky-container">
                <h2 class="h3 form-group text-gray-800">National Intake Report</h2>
				<div id="tabs" class="ui-tabs ui-corner-all ui-widget ui-widget-content">
					<ul class="ui-tabs-nav ui-corner-all ui-helper-reset ui-helper-clearfix ui-widget-header">
						@foreach ($types as $id => $type)
						<li
							class="ui-tabs-tab ui-corner-top ui-state-default ui-tab
							{{((Route::currentRouteName() == $id) ? 'ui-tabs-active ui-state-active' : '')}}">
							<a href="{{route($id)}}" class="ui-tabs-anchor">{{ucfirst($type)}}</a>
						</li>
						@endforeach
					</ul>
				</div>
				<div class="filters-common">
					@component('components.report-filters',['filters' => $filters, 'sourceFilters' =>
					$sourceFilters??''])
					@endcomponent
				</div>
				<div id="cover-spin"></div>
				@if ($report['id']=='nthWeeklyCalls')
				<div>
					<p style="color: red;">Weeks may be limited because the data for this report is only available from 02/05/2024.</p>
				</div>
				@endif
				@if ($report['id']=='nthMonthlyCalls')
				<div>
					<p style="color: red;">Months may be limited because the data for this report is only available from 02/01/2024.</p>
				</div>
				@endif
				@if ($report['id']=='missedVisit' && $report['period']=='week')
				<div>
					<p style="color: red;">Weeks or months may be limited because the data for this
						 report is only available from 02/26/2024.</p>
				</div>
				@endif

				<div class="float-left">
					@for($i=$maxLevel;$i>0;$i--)
					<a href="javascript:void(0)" onclick="collapseExpandAllTab(this, {{$i}})"><span
							class="badge badge-primary"><span>Collapse</span> All {{$groupLevelName[$i]}}</span></a>
					@endfor
				</div>
			</div>
			<div class="table-responsive">
				<table class="table table-bordered nrrreports" style="font-size: 12px !important;"
					id="report_table-{{ $report['id'] }}" width="100%" cellspacing="0"
					aria-describedby="Nation Client Care Report">
					<thead>
						<tr>
							@foreach ($report['columns'] as $column)
							<th style="{{ $column['heading']['style'] }}" <?php echo $column['heading']['attributes'];
								?>>{{ $column['heading']['title'] }}</th>
							@endforeach
						</tr>
					</thead>
					<tbody>
						<?php $lastStateName = ""; $firstTxRow = 0; $groupFlage = 0;?>
						@foreach ($data as $rowId => $row)
						<?php
								if ($report['groupby'][2]['field'] == 'state' && $lastStateName == 'TX' && $firstTxRow == 0) {
									H::arraySwap($report['groupby'],0,1);
									$report['groupby'][0]['id'] = 1;
									$report['groupby'][0]['level'] = 1;
									$report['groupby'][1]['id'] = 2;
									$report['groupby'][1]['level'] = 2;
									H::arraySwap($lastValue,1,2);
									H::arraySwap($currentValue,1,2);
									$firstTxRow = 1;
								}
								if($firstRowDone==0){
									foreach($report['groupby'] as $group){
										if($group['field']!='') {
											$lastValue[$group['id']] = $row[$group['field']];
											$groupFlage++;
										}
									}
									$firstRowDone = 1;
								}
								foreach($report['groupby'] as $group){
									if($group['field']!='') $currentValue[$group['id']] = $row[$group['field']];
								}
								$parentGroup = '';
								foreach($report['groupby'] as $group){
									$groupId = $group['id'];
									$parentGroup = $group['field'];
									$showTotal = 0;
									if($groupId==1){
										if($group['field']!=''){
											$title = str_replace(
												'@group4',
												$lastValue[4],
												str_replace(
													'@group3',
													$lastValue[3],
													str_replace(
														'@group2',
														$lastValue[2],
														str_replace(
															'@group1',
															$lastValue[1],
															$group['label']
														)
													)
												)
											);
											$trStyle = $group['style'];
											if(
												$currentValue[1]!=$lastValue[1] ||
												$currentValue[2]!=$lastValue[2] ||
												$currentValue[3]!=$lastValue[3] ||
												$currentValue[4]!=$lastValue[4] ||
												$rowId=='grand' ||
												($groupFlage == 1 && $firstRowDone == 0)
											)
												$showTotal = 1;
										}
									}elseif($groupId==2){
										if($group['field']!=''){
											$title = Reports::formatGroupTitle($groupId, $group, $lastValue);
											$trStyle = $group['style'];
											if(
												$currentValue[2]!=$lastValue[2] ||
												$currentValue[3]!=$lastValue[3] ||
												$currentValue[4]!=$lastValue[4] ||
												$rowId=='grand'
											)
												$showTotal = 1;
										}
									}elseif($groupId==3){
										if($group['field']!=''){
											$title = Reports::formatGroupTitle($groupId, $group, $lastValue);
											$trStyle = $group['style'];
											if(
												$currentValue[3]!=$lastValue[3] ||
												$currentValue[4]!=$lastValue[4] ||
												$rowId=='grand'
											)
												$showTotal = 1;
										}
									}elseif($groupId==4){
										if($group['field']!=''){
											$title = str_replace(
												'@group4',
												$lastValue[4],
												str_replace(
													'@group3',
													$lastValue[3],
													str_replace(
														'@group2',
														$lastValue[2],
														str_replace(
															'@group1',
															$lastValue[1],
															$group['label']
														)
													)
												)
											);
											$trStyle = $group['style'];
											if(
												$currentValue[4]!=$lastValue[4] ||
												$rowId=='grand'
											)
												$showTotal = 1;
										}
									}elseif($groupId==9){
										$title = $group['label'] . ' ' . $lastValue[9];
										$trStyle = $group['style'];
										if($rowId=='grand' && $group['label']!='')
											$showTotal = 1;
									}
									if($showTotal==1){
										${'count'.$groupId}++;
										$dataid = 0;
										if($groupId == 1) {
											$dataid = $count3 . '' .$count2;
										} else if($groupId == 2) {
											$dataid = $count3;
										} else if($groupId == 3) {
											$dataid = $count3;
										}else if($groupId == 4) {
											$dataid = 0;
										}else if($groupId == 9) {
											$dataid = 0;
										}
										$totalOutput = '';
										$totalOutput .= '<tr class="' . $group['field'] . '-totals ' . $group['field'] . '-totals-'.$dataid;
										$totalOutput .= ' parent'.$group['level'].' '.($group['level'] == $maxLevel ? 'hidechild' : 'hidechild hidden');
										$totalOutput .= '" data-level="'.$dataid.'" style="' . $trStyle . '">';
										$firstColumn = 1;
										foreach($report['columns'] as $column){
											if($firstColumn==1){
												$totalOutput .= '<td>' . $title . '</td>';
												$firstColumn = 0;
											}else{
												if($column['totals']['formula']!=''){
													if($column['totals']['formula'] == 'avg'){
														if($rowCount[$groupId][$column['field']] > 0){
															$tmpAvg = round($subtotals[$groupId][$column['field']] / $rowCount[$groupId][$column['field']],0);
															if (isset($group['hidetotal']) && $group['hidetotal']) {
																$totalOutput .= BLANKTD;
															} else {
																$totalOutput .= '<td>' . Reports::formatCell2($column['cell']['format'],$tmpAvg) . '</td>';
															}
														}else{
															if (isset($group['hidetotal']) && $group['hidetotal']) {
																$totalOutput .= BLANKTD;
															} else {
																$totalOutput .= '<td>0%</td>';
															}
														}
													}elseif($column['totals']['formula'] == 'sum'){
														if (isset($group['hidetotal']) && $group['hidetotal']) {
															$totalOutput .= BLANKTD;
														} else {
															$totalOutput .= '<td>' . Reports::formatCell2(
																$column['cell']['format'],
																$subtotals[$groupId][$column['field']]
															) . '</td>';
														}
													}else{
														if($column['field'] =='SixWeekAvg' || $column['field'] =='ThirteenWeekAvg') {
																if ($column['field'] =='SixWeekAvg') {
																	$tmpAvg = H::getReportAverage($subtotals[$groupId], $rowCount[$groupId] , 6);
																} else {
																	$tmpAvg = H::getReportAverage($subtotals[$groupId], $rowCount[$groupId] , 13);
																}
																if (isset($group['hidetotal']) && $group['hidetotal']) {
																	$totalOutput .= BLANKTD;
																} else {
																	$totalOutput .= '<td>' . Reports::formatCell2($column['cell']['format'],$tmpAvg) . '</td>';
																}
														} elseif($column['field']=='AvgTrend'){
															$arrayIconAttribute = '';
															$tmpSixWeekAvg = H::getReportAverage($subtotals[$groupId], $rowCount[$groupId] , 6);
															$tmpThirteenWeekAvg = H::getReportAverage($subtotals[$groupId], $rowCount[$groupId] , 13);
															if($tmpSixWeekAvg > $tmpThirteenWeekAvg) {
																$arrayIconAttribute = ' <i class="fa fa-long-arrow-up" style="color:#32de84"></i>';
															} elseif($tmpSixWeekAvg < $tmpThirteenWeekAvg) {
																$arrayIconAttribute = ' <i class="fa fa-long-arrow-down" style="color:red"></i>';
															} else {
																$arrayIconAttribute = '<i class="fa fa-solid fa-minus" style="color:yellow;"></i>';
															}
															if (isset($group['hidetotal']) && $group['hidetotal']) {
																$totalOutput .= BLANKTD;
															} else {
																$totalOutput .= '<td>'. $arrayIconAttribute .'</td>';
															}
														} else {
															if (isset($group['hidetotal']) && $group['hidetotal']) {
																$totalOutput .= BLANKTD;
															} else {
																$totalOutput .= '<td>' . $column['totals']['formula'] . '</td>';
															}
														}
													}
												}else{
													if($group['field']=='region'){
														if($column['field']=='region'){
															if(isset($tmp[1])){
																$totalOutput .= '<td>' . $lastRecord['region'] . '</td>';
															}else{
																$totalOutput .= BLANKTD;
															}
														}else{
															$totalOutput .= BLANKTD;
														}
													}else{
														if(strpos(','.$group['showCols'].',',','.$column['field'].',')!==false){
															$totalOutput .= '<td>' . $lastRecord[$column['field']] . '</td>';
														}else{
															$totalOutput .= BLANKTD;
														}
													}
												}
											}
										}
										$totalOutput .=  '</tr>';

										if($groupId==9){
											$finalOutput = $output[9] . $output[4] . $output[3] . $output[2] . $output[1];
											if (LD::checkFeatureFlag('national-total-on-nrr-report')) {
												$removeArray = ["hidechild", "hidden", "parent5"];
												echo str_replace($removeArray, "", $totalOutput);
												echo $finalOutput;
												echo $totalOutput;
											}else {
												echo $finalOutput;
												echo $totalOutput;
											}
										}elseif($groupId==4){
											$output[9] .= $totalOutput . $output[4];
											$output[4] = '';
										}elseif($groupId==3){
											$output[4] .= $totalOutput . $output[3];
											$output[3] = '';
										}elseif($groupId==2){
											$output[3] .= $totalOutput . $output[2];
											$output[2] = '';
										}elseif($groupId==1){
											$source = request()->source;
											if ($source == 'status' || $source == 'reason') {
												$output[2] .= $output[1];
											} else {
												if (($lastStateName == 'TX' || $groupFlage == 1)) {
													$output[2] .= $totalOutput . $output[1];
												} else {
													$output[2] .= $output[1];
												}
											}
											$output[1] = '';
										}else{
											//error?
										}
										//zero out these totals
										foreach($report['columns'] as $column){
											if($column['totals']['formula']!=''){
												$subtotals[$groupId][$column['field']] = 0;
												$rowCount[$groupId][$column['field']] = 0;
											}
										}
										$sixWeekTotals[$groupId] = 0;
										$thirteenWeekCount[$groupId] = 0;
										$thirteenWeekTotals[$groupId] = 0;
									}
								}
								if($rowId!='grand'){
									foreach($report['groupby'] as $group){
										if($group['id']!=9) $lastValue[$group['id']] = $currentValue[$group['id']];
									}

									foreach($report['groupby'] as $group){
										if ($group['field'] == 'region') {
											$lastStateName = $row['state'];
										}
									}

									foreach($report['groupby'] as $group){
										$groupId = $group['id'];
										foreach($report['columns'] as $column){
											if($column['totals']['formula']!=''){
												$subtotals[$groupId][$column['field']] = $subtotals[$groupId][$column['field']] + $row[$column['field']];

												if($row[$column['field']] > 0){
													$rowCount[$groupId][$column['field']] ++;
												}

											}
										}
									}

									$style = '';
									if(isset($report['rowStyle']) && $report['rowStyle']!='') $style = ' style="' . $report['rowStyle'] . '"';
									$source = request()->source;
									if ($source == 'status' || $source == 'reason') {
										$output[1] .= '<tr' . $style . '>';
									} else {
										$output[1] .= '<tr' . $style . ' class="hidden">';
									}
									foreach($report['columns'] as $column){
										$cell = $row[$column['field']];
										$clickable = isset($column['clickable'])?$column['clickable']:false;
										$defaultval = isset($clickable['defaultval'])?$clickable['defaultval']:false;

										$valArray = [];
										$jsonValues = '';
										$function = '';
										if($clickable) {
											$function = $clickable['jsfunction'];
											foreach($clickable['clickedfilters'] as $cfilter){
													if(array_key_exists($cfilter,$row) !== false)
														$valArray[$cfilter]=($row[$cfilter]);
											}
										}
										if($defaultval) {

											foreach($defaultval as $defaultkey=> $defaultv){
												$valArray[$defaultkey]=$defaultv;
											}
										}
										if(!empty($valArray) && Reports::formatCell2($column["cell"]["format"],$cell)!=0) {
											$jsonValues = json_encode($valArray,JSON_ERROR_UTF8);
											$output[1].= '<td class=\'showhand intakereport\'  data-report="'.$report['id'];
											$output[1].= '" data-json=\''.$jsonValues.'\'>'.Reports::formatCell2($column["cell"]["format"],$cell) . '</td>';
										} else {
											if($column['field'] =='SixWeekAvg' || $column['field'] =='ThirteenWeekAvg') {
												$tmpAvg = $row[$column['field']];
												$output[1] .= '<td>'. Reports::formatCell2($column['cell']['format'],$tmpAvg) . '</td>';
											} elseif($column['field'] =='AvgTrend') {
												$arrayIconAttribute = '';
												$tmpSixWeekAvg = $row['SixWeekAvg'];
												$tmpThirteenWeekAvg = $row['ThirteenWeekAvg'];
												if($tmpSixWeekAvg > $tmpThirteenWeekAvg) {
													$arrayIconAttribute = ' <i class="fa fa-long-arrow-up" style="color:#32de84"></i>';
												} elseif($tmpSixWeekAvg < $tmpThirteenWeekAvg) {
													$arrayIconAttribute = ' <i class="fa fa-long-arrow-down" style="color:red"></i>';
												} else {
													$arrayIconAttribute = '<i class="fa fa-solid fa-minus" style="color:yellow;"></i>';
												}
												$output[1].= '<td>'.$arrayIconAttribute.'</td>';
											}else {
												$output[1].= '<td>'.Reports::formatCell2($column['cell']['format'],$cell) . '</td>';
											}
										}
									}
									$output[1] .= '</tr>';

									$lastRecord = $row;
								}
							?>
						@endforeach
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

<!-- /.container-fluid -->
<script type="text/javascript" id="nrrreportsjs">
	function collapseExpandAllTab(element, level) {
		if ($(element).find('span span').text() == 'Collapse') {
			$(element).find('span span').text('Expand');
		} else {
			$(element).find('span span').text('Collapse');
		}
		$('tr.parent' + level).click();
	}
<?php
    foreach($report['groupby'] as $group){
		if ($group['field'] != '') {
			$class = $group['field']. '-totals';
            echo "
			$('." . $class . "').bind('click', function () {
				$(this).toggleClass('hidechild');
				if ($(this).nextAll('tr.parent".$group['level']."').length > 0) {
					if ($(this).hasClass('hidechild')) {
						$(this).nextUntil('tr.parent".$group['level']."').not('tr.parent".($group['level']+1)."')
						.not('tr.parent".($group['level']+2)."').addClass('hidden');
					} else {
						$(this).nextUntil('tr.parent".$group['level']."').not('tr.parent".($group['level']+1)."')
						.not('tr.parent".($group['level']+2)."').removeClass('hidden').removeClass('hidechild');
					}
				} else if ($(this).nextAll('tr.parent5').length > 0) {
					if ($(this).hasClass('hidechild')) {
						$(this).nextUntil('tr.parent5').addClass('hidden');
					} else {
						$(this).nextUntil('tr.parent5').removeClass('hidden').removeClass('hidechild');
					}
				}
			});
			";
		}
	}
?>
		@if (in_array(Route:: currentRouteName(), [
			'intakeReport.referrals',
			'intakeReport.nonadmit',
			'intakeReport.admits',
			'intakeReport.conversion'
		]))
		$(document).on("change", '#state', function () {
			var state = $(this).val().join(",");
			if (state.indexOf('TX') !== -1) {
				$('select[name="search\[region\]"]').multiselect('enable');
			} else {
				$('select[name="search\[region\]"]').multiselect('disable');
			}

			let _token = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				url: "{{ route('getstatebranch') }}",
				type: "GET",
				data: {
					state: $(this).val(),
					_token: _token
				},
				success: function (response) {
					$("#aoc_facility_id").empty();
					$.each(response, function (index, value) {
						$("#aoc_facility_id").append('<option value="' + value.id + '">' + value.FacilityName + '</option>');
						$("#aoc_facility_id").multiselect("rebuild");
					});
				},
			});

		});
	@endif

	$(window).scroll(function () {
		var scroll = $(window).scrollTop();
		if (scroll >= 80) {
			$("body").addClass("sticky");
		} else {
			$("body").removeClass("sticky");
		}
	});
</script>
@endsection
