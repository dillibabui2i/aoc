<?php 
    use App\Helpers\H;
    use App\Helpers\Reports;
    
	$maxLevel = 0;
	$groupLevelName = [];
    $lastValue = [];
    $currentValue = [];
    //print"<pre>";print_r($report);exit;
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
    foreach($report['groupby'] as $group){
        foreach($report['columns'] as $column){
            if($column['totals']['formula']!=''){
                $subtotals[$group['id']][$column['field']] = 0;
				$rowCount[$group['id']][$column['field']] = 0;
            }
        }
    }
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
@section('title', 'Skills Match Report')
@section('content')


<!-- Begin Page Content -->
<div class="container-fluid skillsreport-main nrrreport-main report-sticky-compact">

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
					'skillsMatch' => 'Skills Match'
				];
			@endphp
			<div class="top-sticky-container">
                <!-- Page Heading -->
                <div class="row">
                    <div class="form-group col-md-6 head-title">
                        <h1 class="h3 mb-2 text-gray-800">{{ $report['title'] }}</h1>
                    </div>
                </div>
				<div id="tabs" class="ui-tabs ui-corner-all ui-widget ui-widget-content">
					<ul class="ui-tabs-nav ui-corner-all ui-helper-reset ui-helper-clearfix ui-widget-header">
						@foreach ($types as $id => $type)
							<li class="ui-tabs-tab ui-corner-top ui-state-default ui-tab @php if(Route::currentRouteName() == $id): @endphp ui-tabs-active ui-state-active@php endif; @endphp"><a href="{{route($id)}}" class="ui-tabs-anchor">@php echo ucfirst($type); @endphp</a></li>
						@endforeach
					</ul>
				</div>
				<div class="filters-common">
					@component('components.report-filters',['filters' => $filters, 'sourceFilters' => $sourceFilters??''])
					@endcomponent
				</div>
				<div class="float-left">
					@for($i=$maxLevel;$i>0;$i--)
						<a href="javascript:void(0)" onclick="collapseExpandAllTab(this, {{$i}})"><span class="badge badge-primary"><span>Collapse</span> All {{$groupLevelName[$i]}}</span></a>
					@endfor
				</div>
			</div>
			<div class="table-responsive">
				<table class="table table-bordered nrrreports" style="font-size: 12px !important;" id="report_table-{{ $report['id'] }}" width="100%" cellspacing="0">
					<thead>
						<tr>
							@foreach ($report['columns'] as $column)
								<th style="{{ $column['heading']['style'] }}"<?php echo $column['heading']['attributes']; ?>>{{ $column['heading']['title'] }}</th>
							@endforeach    
						</tr>
					</thead>
					<tbody>
						@foreach ($data as $rowId => $row)
							<?php 
								
								//on the first row only, set $lastValue for each group to the field in the first row
								if($firstRowDone==0){
									foreach($report['groupby'] as $group){
										if($group['field']!='') $lastValue[$group['id']] = $row[$group['field']];
									}         
									$lastRow = $row;                       
									$firstRowDone = 1;    
								}
								//set $currentValue for each group to the field in this row
								foreach($report['groupby'] as $group){
									if($group['field']!='') $currentValue[$group['id']] = $row[$group['field']];
								}
								//check to see if we need to output one or more subtotal rows
								//if the $currentValue <> $lastValue for the group or any 'downstream' groups, show the subtotal row
								$parentGroup = '';
								foreach($report['groupby'] as $group){
									$groupId = $group['id'];
									$parentGroup = $group['field'];
									//H::ExcelLog('groupid: ' . $groupId . ' - rowId: ' . $rowId, 'dbNRR.log');
									$showTotal = 0;
									if($groupId==1){
										if($group['field']!=''){
											$title = str_replace('@group4',$lastValue[4],str_replace('@group3',$lastValue[3],str_replace('@group2',$lastValue[2],str_replace('@group1',$lastValue[1],$group['label']))));
											$trStyle = $group['style'];
											if($currentValue[1]!=$lastValue[1] || $currentValue[2]!=$lastValue[2] || $currentValue[3]!=$lastValue[3] || $currentValue[4]!=$lastValue[4] || $rowId=='grand') $showTotal = 1;
										}
									}elseif($groupId==2){
										if($group['field']!=''){
											$title = str_replace('@group4',$lastValue[4],str_replace('@group3',$lastValue[3],str_replace('@group2',$lastValue[2],str_replace('@group1',$lastValue[1],$group['label']))));
											$trStyle = $group['style'];
											if($currentValue[2]!=$lastValue[2] || $currentValue[3]!=$lastValue[3] || $currentValue[4]!=$lastValue[4] || $rowId=='grand') $showTotal = 1;
										}
									}elseif($groupId==3){
										if($group['field']!=''){
											$title = Reports::formatGroupTitle($groupId, $group, $lastValue);
											$trStyle = $group['style'];
											if($currentValue[3]!=$lastValue[3] || $currentValue[4]!=$lastValue[4] || $rowId=='grand') $showTotal = 1;
										}
									}elseif($groupId==4){
										if($group['field']!=''){
											$title = str_replace('@group4',$lastValue[4],str_replace('@group3',$lastValue[3],str_replace('@group2',$lastValue[2],str_replace('@group1',$lastValue[1],$group['label']))));
											$trStyle = $group['style'];
											if($currentValue[4]!=$lastValue[4] || $rowId=='grand') $showTotal = 1;
										}
									}elseif($groupId==9){
										$title = $group['label'] . ' ' . $lastValue[9];
										$trStyle = $group['style'];
										if($rowId=='grand' && $group['label']!='')  $showTotal = 1;
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
										if($report['id']=='wepi' && $group['field'] == 'state'){
											$nextState = array_shift($states);
											while($nextState!=$lastValue[$group['id']]){
												H::ExcelLog('nextState: ' . print_r($nextState,true),'dbNRR.log');
												H::ExcelLog('thisState: ' . print_r($lastValue[$group['id']],true),'dbNRR.log');
												if($nextState!=$lastValue[$group['id']]){
													//state is missing so we need to print a row for it.  
													$totalOutput .= '<tr class="' . $group['field'] . '-totals ' . $group['field'] . '-totals-'.$dataid.' parent'.$group['level'].'" data-level="'.$dataid.'" style="' . $trStyle . '">';
										
													$firstColumn = 1;
													foreach($report['columns'] as $column){
														if($firstColumn==1){
															$totalOutput .= '<td>' . $nextState . '</td>';
															$firstColumn = 0;
														}else{
															if($column['totals']['formula']!=''){
																$totalOutput .= '<td>0</td>';
															}else{
																$totalOutput .= '<td></td>';
															}
														}
													}
													$totalOutput .=  '</tr>';
												}
												$nextState = array_shift($states);
												H::ExcelLog(print_r($states,true),'dbNRR.log');
											}
										}
										$totalOutput .= '<tr class="' . $group['field'] . '-totals ' . $group['field'] . '-totals-'.$dataid.' parent'.$group['level'].'" data-level="'.$dataid.'" style="' . $trStyle . '">';
										$firstColumn = 1;
										foreach($report['columns'] as $column){
											if($firstColumn==1){
												$totalOutput .= '<td>' . $title . '</td>';
												$firstColumn = 0;
											}else{
												//$agg[$record['State']]['Facilities'][$record['FacilityName']]['Results'][$record['result']][$record['Monday']]
												if($column['totals']['formula']!=''){
													if($column['totals']['formula'] == 'pct'){
														$totalCount = 0;
														$thisCount = 0;
														if($source=='branch'){
															if($groupId==1){
																$thisCount = $agg[$lastRow['State']]['Facilities'][$lastRow['FacilityName']][$column['field']];
																$totalCount = $agg[$lastRow['State']][$column['field']];
															}elseif ($groupId==2){
																$thisCount = $agg[$lastRow['State']][$column['field']];
																$totalCount = $agg['grand'][$column['field']];
															}
														}else{
															if($groupId==1){
																$thisCount = $agg[$lastRow['State']]['Facilities'][$lastRow['FacilityName']]['Recruiters'][$lastRow['Recruiter']][$column['field']];
																$totalCount = $agg[$lastRow['State']]['Facilities'][$lastRow['FacilityName']][$column['field']];
															}elseif ($groupId==2){
																$thisCount = $agg[$lastRow['State']]['Facilities'][$lastRow['FacilityName']][$column['field']];
																$totalCount = $agg[$lastRow['State']][$column['field']];
															}elseif ($groupId==3){
																$thisCount = $agg[$lastRow['State']][$column['field']];
																$totalCount = $agg['grand'][$column['field']];
															}else{
																$thisCount = $agg['grand'][$column['field']];
																$totalCount = $agg['grand'][$column['field']];
															}
														}
														if($totalCount!=0){
															$myPct = round($thisCount / $totalCount * 100,2);
															$totalOutput .= '<td>' . Reports::formatCell($column['cell']['format'],$myPct) . '</td>';
														}else{
															$totalOutput .= '<td>0%</td>';
														}
													}elseif($column['totals']['formula'] == 'sum' || $column['totals']['formula'] == 'count'){
														$totalOutput .= '<td>' . Reports::formatCell($column['cell']['format'],$subtotals[$groupId][$column['field']]) . '</td>';  //add formatting before output 
													}else{
														$totalOutput .= '<td>' . $column['totals']['formula'] . '</td>';
													}
												}else{
													if(strpos($group['showCols'],$column['field'])!==false){
														$totalOutput .= '<td>' . $lastRecord[$column['field']] . '</td>';
													}else{
														$totalOutput .= '<td></td>';
													}
												}
											}
										}
										$totalOutput .=  '</tr>';
										
										if($groupId==9){
											$finalOutput = $output[9] . $output[4] . $output[3] . $output[2] . $output[1];
											echo $finalOutput;
											echo $totalOutput; //show grand totals at the bottom
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
											$output[2] .= $totalOutput . $output[1];
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
									}                                   
								}
								if($rowId!='grand'){ 
									//set grouping $lastValue = $currentValue (except for grand totals)
									foreach($report['groupby'] as $group){
										if($group['id']!=9) $lastValue[$group['id']] = $currentValue[$group['id']];
									}
									$lastRow = $row;
									//accumulate totals
									foreach($report['groupby'] as $group){
										$groupId = $group['id'];
										foreach($report['columns'] as $column){
											if($column['totals']['formula']!=''){
												if($column['totals']['formula']!='count'){
													$subtotals[$groupId][$column['field']] = $subtotals[$groupId][$column['field']] + $row[$column['field']];
												}else{
													$subtotals[$groupId][$column['field']] = $subtotals[$groupId][$column['field']] + 1;
												}
												if($row[$column['field']] > 0) $rowCount[$groupId][$column['field']] ++;
											}
										}
									}

									//create the output into a variable
									$style = '';
									if(isset($report['rowStyle']) && $report['rowStyle']!='') $style = ' style="' . $report['rowStyle'] . '"';
									$output[1] .= '<tr' . $style . '>';
									//foreach($row as $field => $cell){
									foreach($report['columns'] as $column){
										$cell = $row[$column['field']];
										$clickable = isset($column['clickable'])?$column['clickable']:false;
										$defaultval = isset($clickable['defaultval'])?$clickable['defaultval']:false;
										/*echo "<br><br><br>";
										var_dump($row);
										echo "<br>";

										var_dump($column);
										var_dump($defaultval); */
										
										$valArray = [];
										$jsonValues = '';
										$function = '';
										if($clickable) {
											$function = $clickable['jsfunction'];
											foreach($clickable['clickedfilters'] as $cfilter){
												if(array_key_exists($cfilter, $row))
													$valArray[$cfilter]=($row[$cfilter]);
											}
										}
										if($defaultval) {
											
											foreach($defaultval as $defaultkey=> $defaultv){
												$valArray[$defaultkey]=$defaultv;
											}
										}
										if($column['cell']['format']=='pct'){
											if($source == 'branch'){
												$thisCount = $agg[$row['State']]['Facilities'][$row['FacilityName']]['Results'][$row['result']][$column['field']];
												$totalCount = $agg[$row['State']]['Facilities'][$row['FacilityName']][$column['field']];
											}else{
												$thisCount = $agg[$row['State']]['Facilities'][$row['FacilityName']]['Recruiters'][$row['Recruiter']]['Results'][$row['result']][$column['field']];
												$totalCount = $agg[$row['State']]['Facilities'][$row['FacilityName']]['Recruiters'][$row['Recruiter']][$column['field']];
											}
											if($totalCount!=0){
												$myPct = round($thisCount / $totalCount * 100,2);
												$cell = $myPct;
											}
										}
										if(!empty($valArray) && Reports::formatCell($column["cell"]["format"],$cell)!=0) {
											$jsonValues = json_encode($valArray,JSON_ERROR_UTF8);
											$output[1].= '<td class=\'showhand\' ondblclick=\''.$function.'(this)\' data-report="'.$report['id'].'" data-json=\''.$jsonValues.'\'>'.Reports::formatCell($column["cell"]["format"],$cell) . '</td>';
										
										} else {
											$output[1].= '<td>'.Reports::formatCell($column['cell']['format'],$cell) . '</td>';
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
        if($group['field']!=''){
            $class = $group['field'] . '-totals';
            echo "
                $('." . $class . "').bind('click', function() {
					$(this).toggleClass('hidechild');
					if($(this).nextAll('tr.parent".$group['level']."').length > 0){
						if($(this).hasClass('hidechild')) {
							$(this).nextUntil('tr.parent".$group['level']."').not('tr.parent".($group['level']+1)."').not('tr.parent".($group['level']+2)."').addClass('hidden');
						} else {
							$(this).nextUntil('tr.parent".$group['level']."').removeClass('hidden').removeClass('hidechild');
						}
					} else if($(this).nextAll('tr.parent5').length > 0){
						if($(this).hasClass('hidechild')) {
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
$(document).on("change", '#state', function() {
	let _token = $('meta[name="csrf-token"]').attr('content');
	$.ajax({
		url: "{{ route('getstatebranch') }}",
		type: "GET",
		data: {
			state: $(this).val(),
			_token: _token
		},
		success: function(response) {
			$("#aoc_facility_id").empty();
			$.each(response, function(index, value) {
				$("#aoc_facility_id").append('<option value="' + value.id + '">' + value.FacilityName + '</option>');
				$("#aoc_facility_id").multiselect("rebuild");
			});
		},
	});

});

function openIpeaConversionReport(ele) {
	var json = decodeURIComponent(escape($(ele).attr("data-json")));
	var reportType = $(ele).attr("data-report");
	var formname = $("#ReportsFilterSearchForm").serializeArray();
	let _token = $('meta[name="csrf-token"]').attr('content');
	$.ajax({
		url: "{{route('icr.geticrpopup')}}",
		type: 'get',
		dateType:"html",
		data: {
			rowfilter: json,
			type: reportType,
			form: formname,
			_token: _token
		},
		success: function(data) {
			$("#tempModalnrrreport").remove();
			var $div = $('<div />').appendTo('body');
			$div.attr('id', 'tempModalnrrreport').html(data);
			$('#nrr-popup').modal('show');
		}
	});
}

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
