<?php
	use Illuminate\Support\Facades\Request;
    use App\Helpers\H;
    use App\Helpers\Reports;
	use Carbon\Carbon;
	use App\Helpers\LD;
    
	$months = ['Jan 2023','Dec 2022','Nov 2022'];
?>
@extends('layouts.myaccount')
@section('title', 'Bonus Report')
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
					'bonus' => 'Bonus'
				];
			@endphp
			<div class="top-sticky-container">
                <!-- Page Heading -->
                <div class="row">
                    <div class="form-group col-md-6 head-title">
                        <h1 class="h3 mb-2 text-gray-800">{{ $report['title'] }}</h1>
                        @if(LD::checkFeatureFlag('add-state-filter-and-export-bonus-report'))
                            <a title="Export" href="javascript:void(0)" id="downloadBonusReport"><i class="fa fa-download"></i></a>
                        @endif
                    </div>
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
								H::ExcelLog(print_r($row,true),'dbBonusReport.log');
								$metric = $rowId;
								H::ExcelLog('metric: ' . $metric,'dbBonusReport.log');
								
								if(isset($report['rowStyle']) && $report['rowStyle']!='') {
									$style = ' style="' . $report['rowStyle'] . '"';
								}
								echo '<tr' . $style . '>';
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
											if(array_key_exists($cfilter, $row)) {
												$valArray[$cfilter]=($row[$cfilter]);
											}
										}
									}
									if($defaultval) {
										foreach($defaultval as $defaultkey=> $defaultv){
											$valArray[$defaultkey]=$defaultv;
										}
									}
									if(!empty($valArray) && Reports::formatCell($column["cell"]["format"],$cell)!= '') {
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
<x-modal>
    <x-slot name="id">setBonusPaidmodal</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <h5 id="confirmationDialogMessage">Are you sure you want to set this records to Bonus Paid</h5>
        </div>
        <label for="datepicker">Bonus Paid On:</label>
        <input type="input" class="form-control" id="datepicker" name="Bonus_Paid_Date" value=""/>
        <span id="bonus" style="color: red;display: none;">Field is required</span>
    </x-slot>
    <x-slot name="footer">
		@if(LD::checkFeatureFlag('recruiter-bonus-report-change-download-timing'))
			<button class="btn btn-primary" id="setBonusPaidYesButton" onclick="saveAndCloseSetBonusPaid()">Save</button>
		@else
			<button class="btn btn-primary" id="setBonusPaidYesButton" onclick="saveAndCloseSetBonusPaid()">Yes</button>
			<button class="btn btn-primary" id="setBonusPaidNoButton" onclick="closeSetBonusPaid()">No</button>
		@endif
    </x-slot>
</x-modal>
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
			url: "{{route('getRecruiterBonusPopup')}}",
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
	@if(LD::checkFeatureFlag('recruiter-bonus-report-change-download-timing'))
		$("#downloadBonusReport").click(function(e) {
			$('#setBonusPaidmodal').modal('show');
		});
		function downloadcsv(){
			var data = {};
			var search = {};
			let _token = $('meta[name="csrf-token"]').attr('content');
			data['filters'] = $("#ReportsFilterSearchForm").serializeArray();
			console.log(data);
			var url = "{{route('downloadBonusReport')}}" + '?' + $.param(data);
			window.location.href = url;
			setTimeout(function() {
				window.location.reload(true);
			}, 10000);
		}
		function saveAndCloseSetBonusPaid() {
			let bonusPaidDate = $("#datepicker").val();
			if(bonusPaidDate == ''){
				$("#bonus").attr("style", "color: red;display:block");
				return false;
			}
			let _token = $('meta[name="csrf-token"]').attr('content');
			let filter = $("#ReportsFilterSearchForm").serializeArray();
			$.ajax({
				url: "{{route('setBonusReportPaidmodal')}}",
				type: 'post',
				data: {
					bonusPaidDate: bonusPaidDate,
					filters: filter,
					_token: _token
				},
				success: function(data) {
					$('#setBonusPaidmodal').modal('hide');
					downloadcsv();
				}
			});
		}
	@else
		$("#downloadBonusReport").click(function(e) {
			var data = {};
			var search = {};
			let _token = $('meta[name="csrf-token"]').attr('content');
			data['filters'] = $("#ReportsFilterSearchForm").serializeArray();
			console.log(data);
			var url = "{{route('downloadBonusReport')}}" + '?' + $.param(data);
			$('#setBonusPaidmodal').modal('show');
			window.location.href = url;
		});
		function saveAndCloseSetBonusPaid() {
			let bonusPaidDate = $("#datepicker").val();
			if(bonusPaidDate == ''){
				$("#bonus").attr("style", "color: red;display:block");
				return false;
			}
			let _token = $('meta[name="csrf-token"]').attr('content');
			let filter = $("#ReportsFilterSearchForm").serializeArray();
			$.ajax({
				url: "{{route('setBonusReportPaidmodal')}}",
				type: 'post',
				data: {
					bonusPaidDate: bonusPaidDate,
					filters: filter,
					_token: _token
				},
				success: function(data) {
					$('#setBonusPaidmodal').modal('hide');
					window.location.reload(true);
				}
			});
		}
	@endif
	function closeSetBonusPaid() {
        $('#setBonusPaidmodal').modal('hide');
    }
	$('#datepicker').datepicker();
    $('#datepicker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($('#datepicker').parent());
    });
	@if (!Request::ajax())
		$('#month').val('Pending');
	@endif
	$('.clearall').on('click', function () {
		$('#cover-spin').show(0);
	});
	$('#ReportsFilterSearch').on('click', function () {
		$('#cover-spin').show(0);
	});
	$(document).ready(function () {
		$('#cover-spin').hide(0);
	});
</script>
@endsection
