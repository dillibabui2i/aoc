<?php
    use App\Helpers\H;
    use App\Helpers\Reports;
	use Carbon\Carbon;

	$states = [];
	$tmp = H::getHelper("ConstantHelper")->getDashboardState();
	foreach($tmp as $key => $value){
		$states[] = $value['value'];
	}
	$Over30DaysAgo =  $day = Carbon::now()->subDays(30)->format('Y-m-d');
	$Over90DaysAgo =  $day = Carbon::now()->subDays(90)->format('Y-m-d');
	$Over150DaysAgo =  $day = Carbon::now()->subDays(150)->format('Y-m-d');
?>
@extends('layouts.myaccount')
@section('title', 'Nurse Churn Report')
@section('content')


<!-- Begin Page Content -->
<div class="container-fluid skillsreport-main">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ $report['title'] }}</h1>
        </div>
        <span class="ml-auto">@if (Route::currentRouteName() == 'nurseChurn')<a href="{{route('nurseChurnLegacy')}}"> Switch to legacy report</a>@endif</span>
    </div>
    <div class="row">
        <div class="col-md-12">
            <p class="text-gray-600 text-left">The Nurse Churn Report displays all nurses hired within a selected time period (week, month, or quarter), including how many have billable shifts and how many continue working after 30, 90, and 150 days. Report data is refreshed once daily at 4:00 AM CST. As a result, data displayed on this report reflects information as of the previous day.</p>
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
				$types = [
					'nurseChurn' => 'Nurse Churn'
				];
			@endphp
			<div class="top-sticky-container">
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
				<div id="cover-spin"></div>
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
								//create the output into a variable
								$style = '';
								$metric = $rowId;
								if(isset($report['rowStyle']) && $report['rowStyle']!='') $style = ' style="' . $report['rowStyle'] . '"';
								if($metric == 'hired_pct' || $metric == 'working_after_150' || $metric == 'working_after_90' || $metric == 'working_after_30' || $metric == 'pct_shifts'){
									echo '<tr style="background-color: #D9D9D9;">';
								}else{
									echo '<tr' . $style . '>';
								}
								foreach($report['columns'] as $column){
									$period = $column['field'];
									$cell = $row[$column['field']];
									$clickable = isset($column['clickable'])?$column['clickable']:false;
									$defaultval = isset($clickable['defaultval'])?$clickable['defaultval']:false;
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
									if($period!='metric' && $period!='Total'){
										if($metric == 'hired_pct' || $metric == 'pct_shifts' || $metric == 'pct_after_30' || $metric == 'pct_after_90' || $metric == 'pct_after_150'){
											$myPct = round(floatval($cell) * 100,1);
											$cell = $myPct . '%';
										}
									}
									H::ExcelLog('period: ' . $period,'dbNurseChurn.log');
									if($metric=='pct_after_30' && $period!='metric' && $period!='Total'){
										//if last day of column is not before $30DaysAgo, show 'N/A'
										if($report['period']=='week'){
											$mydate = Carbon::createFromFormat('Y-m-d', $period)->addDays(7)->format('Y-m-d');
										}elseif($report['period']=='month'){
											$mydate = Carbon::createFromFormat('Y-m-d', $period.'-01')->addMonths(1)->format('Y-m-d');
										}else{
											//quarter
											$mydate = Carbon::createFromFormat('Y-m-d', $period.'-01')->addMonths(3)->format('Y-m-d');
										}
										if($mydate >= $Over30DaysAgo) $cell = 'N/A';
									}
									if($metric=='pct_after_90' && $period!='metric' && $period!='Total'){
										//if last day of column is not before $30DaysAgo, show 'N/A'
										if($report['period']=='week'){
											$mydate = Carbon::createFromFormat('Y-m-d', $period)->addDays(7)->format('Y-m-d');
										}elseif($report['period']=='month'){
											$mydate = Carbon::createFromFormat('Y-m-d', $period.'-01')->addMonths(1)->format('Y-m-d');
										}else{
											//quarter
											$mydate = Carbon::createFromFormat('Y-m-d', $period.'-01')->addMonths(3)->format('Y-m-d');
										}
										if($mydate >= $Over90DaysAgo) $cell = 'N/A';
									}
									if($metric=='pct_after_150' && $period!='metric' && $period!='Total'){
										//if last day of column is not before $30DaysAgo, show 'N/A'
										if($report['period']=='week'){
											$mydate = Carbon::createFromFormat('Y-m-d', $period)->addDays(7)->format('Y-m-d');
										}elseif($report['period']=='month'){
											$mydate = Carbon::createFromFormat('Y-m-d', $period.'-01')->addMonths(1)->format('Y-m-d');
										}else{
											//quarter
											$mydate = Carbon::createFromFormat('Y-m-d', $period.'-01')->addMonths(3)->format('Y-m-d');
										}
										if($mydate >= $Over150DaysAgo) $cell = 'N/A';
									}
									if(!empty($valArray) && Reports::formatCell($column["cell"]["format"],$cell)!=0 && (!in_array($metric,['hired_pct','total_hires']))) {
										$jsonValues = json_encode($valArray,JSON_ERROR_UTF8);
										echo '<td class=\'showhand\' ondblclick=\''.$function.'(this)\' data-report="'.$report['id'].'" data-json=\''.$jsonValues.'\'>'.Reports::formatCell($column["cell"]["format"],$cell) . '</td>';
									} else {
										echo '<td>'.Reports::formatCell($column['cell']['format'],$cell) . '</td>';
									}
								}
								echo '</tr>';
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
				});
				$("#aoc_facility_id").multiselect("rebuild");
			},
		});

	});

	function openNurseChurnPopup(ele) {
		var json = decodeURIComponent(escape($(ele).attr("data-json")));
		var reportType = $(ele).attr("data-report");
		var formname = $("#ReportsFilterSearchForm").serializeArray();
		let _token = $('meta[name="csrf-token"]').attr('content');
		$.ajax({
			url: "{{route('getNurseChurnPopup')}}",
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

	$('.clearall').on('click', function() {
		$('#cover-spin').show(0);
	});
	$('#ReportsFilterSearch').on('click', function() {
		$('#cover-spin').show(0);
	});
	$(document).ready(function() {
		$('#cover-spin').hide(0);
	});
	$("form#ReportsFilterSearchForm input:radio").change(function () {
		$('#cover-spin').show(0);
	});
	$('#ReportsFilterSearchForm #state').change(function(){
        if ($(this).val().indexOf('TX') !== -1) {
            $('#ReportsFilterSearchForm #region').multiselect('enable');
        } else {
            $('#ReportsFilterSearchForm #region').multiselect('disable');
        }
    });
	//$('#ReportsFilterSearchForm #state').change();
</script>
@endsection
