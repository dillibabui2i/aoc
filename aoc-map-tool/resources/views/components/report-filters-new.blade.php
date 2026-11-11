<?php
	use App\Helpers\LD;
	use App\Helpers\H;
?>
<style>
	.ui-datepicker {z-index:9999 !important}
</style>
<div class="reports-filter-cnt col-md-12">
    <form id="ReportsFilterSearchForm" action="{{Request::url()}}" method="post">
		<input type="hidden" name="_token" value="{{ csrf_token() }}" />
		<input type="hidden" name="isajax" value="true" />
        <div class="form-row align-items-center">
			@if(
				Route::currentRouteName() != 'hoursOnNTH' &&
				Route::currentRouteName() != 'skillsMatch' &&
				Route::currentRouteName() != 'whiteboard' &&
				Route::currentRouteName() != 'bonusReport' &&
				Route::currentRouteName() != 'monthlycalls' &&
				Route::currentRouteName() != 'nthWeeklyCalls'
			)
				<div class="form-group col-md-4">
					<label class="filter-label">Periods:</label>
					@if(
						Route::currentRouteName() == 'nthHoursAdded' ||
						Route::currentRouteName() == 'nthHoursRemoved' ||
						Route::currentRouteName() == 'nthHoursClassification'
					)
					<div class="col-auto">
						<input type="radio" name="period" value="day">
						<span>Day</span>
					</div>
					@endif
					<div class="col-auto">
						<input type="radio" name="period" value="week" checked>
						<span>Week</span>
					</div>
					<div class="col-auto">
						<input type="radio" name="period" value="month">
						<span>Month</span>
					</div>
					@if(Route::currentRouteName() == 'nurseChurn')
					<div class="col-auto">
						<input type="radio" name="period" value="quarter">
						<span>Quarter</span>
					</div>
					@endif
				</div>
			@endif
			@if($sourceFilters)
			<div class="form-group col-md-4">
				@if(
					Route::currentRouteName() == 'ipeaConversion' ||
					Route::currentRouteName() == 'coveragebooked'
				)
				<label class="filter-label">Source:</label>
				@else
				<label class="filter-label">Sort By:</label>
				@endif
				@foreach($sourceFilters as $key => $filter)
					<div class="col-auto">
						<input type="radio" name="source" value="{{$filter['value']}}" {{($key == 0) ? 'checked' : ''}}>
						<span>{{$filter['label']}}</span>
					</div>
				@endforeach
			</div>
			@endif
			@if (LD::checkFeatureFlag('nrr-show-percentage-to-goal'))
				@if(in_array(Route::currentRouteName() ,
					[
						'IPEAs',
						'PEPIs',
						'WEPIs',
						'candidateMatch',
						'hoursRemoved',
						'candidateStarts',
						'coverageBooked',
						'nthWeeklyCalls',
						'monthlycalls',
					]))
					@if(
						Route::currentRouteName() == 'monthlycalls'
					)
						<div class="form-group col-md-3 viewfilter">
							<label class="filter-label">View:</label>
							<div class="col-auto">
								<input type="radio" name="view" value="percentage" checked>
								<span>% to Goal</span>
							</div>
							<div class="col-auto">
								<input type="radio" name="view" value="count">
								<span>Count</span>
							</div>
						</div>
					@else
						<div class="form-group col-md-3 viewfilter">
							<label class="filter-label">View:</label>
							<div class="col-auto">
								<input type="radio" name="view" value="count" checked>
								<span>Count</span>
							</div>
							<div class="col-auto">
								<input type="radio" name="view" value="percentage">
								<span>% to Goal</span>
							</div>
						</div>
					@endif
				@endif
			@endif
		</div>
		<div class="form-row align-items-center filters-container">
			<div class="form-group col-md-12">
				<label id="filter-label" class="filter-label">Filters:</label>
				@foreach($filters as $filter)
					@if($filter['type'] == 'select')
						<div class="col-auto">
							<label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
							<select class="form-control" name="search[{{$filter['name']}}]" id="{{$filter['name']}}">
								<option value="">Select {{$filter['label']}}</option>
								@foreach($filter['source'] as $option)
								<option value="{{$option['value']}}"
								{{ (($option['value'] == $filter['defaultValue']) ? 'selected' : '') }}>
									{{$option['text']}}
								</option>
								@endforeach
							</select>
						</div>
					@elseif($filter['type'] == 'dependablemultiselect')
						<div class="col-auto">
							<label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
							<select multiple class="selectpicker" data-placeholder="Select {{$filter['label']}}" data-live-search="true" name="search[{{$filter['name']}}]" id="{{$filter['name']}}">
							</select>
						</div>
						<script type="text/javascript">
							$(document).ready(function() {
								let defaultval = '{{ (isset($filter['defaultValue']) ? $filter['defaultValue'] : '') }}';
								$("#{{$filter['dependon']}}").change(function() {
									$.post( "{{$filter['dependonurl']}}", {'_token': "{{ csrf_token() }}", 'source_type': $(this).val()}, function( data ) {
										$("#{{$filter['name']}}").empty();
										$.each(data, function(index, value) {
											$("#{{$filter['name']}}").append(
											'<option '+((defaultval == value.value) ? 'selected' : '')+' value="'+value.value+'">'+value.text+'</option>'
											);
											$("#{{$filter['name']}}").multiselect("rebuild");
										});
									});
								});
							});
						</script>
					@elseif($filter['type'] == 'checkbox')
						&nbsp &nbsp &nbsp
						<div class="form-check col-auto">
							<input type="{{$filter['type']}}" name="search[{{$filter['name']}}]" class="form-check-input"
								id="{{$filter['name']}}" {{
									!empty($filter['defaultValue']) ?
									(($filter['defaultValue'] == true) ? 'checked="checked"' : '') :
									(($filter['value'] == true) ? 'checked="checked"' : '')
								}}
								placeholder="{{$filter['label']}}" />
							<label class="form-check-label" for="{{$filter['name']}}">{{$filter['label']}}</label>
						</div>
					@elseif($filter['type']=='multiselect')
						<div class="col-auto">
							<select multiple {{((isset($filter['disabled']) && $filter['disabled'] == true) ? 'disabled' : '')}}
							 class="selectpicker" data-placeholder="Select {{$filter['label']}}"
							 data-live-search="true" name="search[{{$filter['name']}}][]"
							 id="{{$filter['name']}}">
								@foreach($filter['source'] as $option)
								<option value="{{$option['value']}}"
								{{ (in_array($option['value'], $filter['defaultValue']) ? 'selected' : '') }}>
									{{$option['text']}}
								</option>
								@endforeach
							</select>
						</div>
					@elseif($filter['type'] == 'daterange')
					<div class="col-auto input-daterange">
						<label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">From</span>
							</div>
							<input type="text" readonly class="form-control from_date" name="date[from]" id="from_date" value="{{
								isset($filter['defaultValue']['from']) ? H::getFormatedDate($filter['defaultValue']['from']) : $filter['fromdate']
							}}"/>
						</div>
						<div class="align-items-center d-flex ml-1 mr-1 float-left todiv">-</div>
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">To</span>
							</div>
							<input type="text" readonly class="form-control to_date" name="date[to]" id="to_date" value="{{
								isset($filter['defaultValue']['to']) ? H::getFormatedDate($filter['defaultValue']['to']) : $filter['todate']
							}}"/>
						</div>
					</div>
					<script type="text/javascript">
						function getDate( element ) {
							var date;
							try {
								date = $.datepicker.parseDate( 'mm/dd/yy', element.value );
							} catch( error ) {
								date = null;
							}
							return date;
						}
						$(document).ready(function() {
							$("#from_date").datepicker({
								dateFormat: 'mm/dd/yy',
								maxDate: 0,
							}).on('change', function () {
								$('#to_date').datepicker("option", 'minDate', getDate( this ) );
							});

							$("#to_date").datepicker({
								dateFormat: 'mm/dd/yy',
								maxDate: 0
							}).on('change', function () {
								$('#from_date').datepicker("option", 'maxDate', getDate( this ) );
							});
						});
					</script>
					@else
						<div class="col-auto">
							<label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
							<input type="{{$filter['type']}}" name="search[{{$filter['name']}}]" class="form-control"
								id="{{$filter['name']}}" placeholder="{{$filter['label']}}" />
						</div>
					@endif
				@endforeach
				<div class="col-auto">
					<button type="button" id="ReportsFilterSearch" class="btn btn-primary">Search</button>
				</div>
				<div class="col-auto">
					<button type="button"  data-filter='<?php echo htmlspecialchars(json_encode($filters),ENT_QUOTES, 'UTF-8');?>' data-formname="ReportsFilterSearchForm" class="btn btn-primary clearall">Clear All</button>
				</div>
			</div>
        </div>
    </form>
</div>
<script type="text/javascript">
	function setReportFilterRequest() {
		@if(Route::currentRouteName() == 'whiteboard')
			$.post( "{{Request::url()}}", $('#ReportsFilterSearchForm').serialize(), function( data ) {
				$(data).find('.table-responsive').each(function(){
					$('#' + $(this).attr('id')).html($(this).html());
				});
				//$('.table-responsive').html($(data).find('.table-responsive').html());
				if (jQuery('.filters-common').next().attr('class') == 'float-left') {
					jQuery('.filters-common').next().html($(data).find('.filters-common').next().html());
				}
				eval($(data).find('#whiteboardreportsjs').html());
				let _token = $('meta[name="csrf-token"]').attr("content");
				$(".editable-text,.editable").editable({
					params: function(params) {
						params.table = $(this).editable().data("table");
						var type = $(this).editable().data("type");
						if (params.name == "GP") {
							params.value = params.value / 100;
						}
						if (type == "date") {
							params.value = formatDate(params.value);
						}
						params._token = _token;
						return params;
					},
					success: function(response, newValue) {
						if($(this).editable().data("name") == 'GP'){
							openRecruiterDetails($(this).editable().data("pk"), 'append');
						}
						if (!response.success) return response.msg;
					},
				});
			});
		@else
			$.post( "{{Request::url()}}", $('#ReportsFilterSearchForm').serialize(), function( data ) {
				$('.table-responsive').html($(data).find('.table-responsive').html());
				if (jQuery('.filters-common').next().attr('class') == 'float-left') {
					jQuery('.filters-common').next().html($(data).find('.filters-common').next().html());
				}
				eval($(data).find('#nrrreportsjs').html());
			});
		@endif
	}
	$(document).ready(function() {
		$('#ReportsFilterSearch').click(function() {
			setReportFilterRequest();
		});

		$("form#ReportsFilterSearchForm input:radio").change(function () {
			setReportFilterRequest();
		});
		$('.selectpicker').multiselect({
			includeSelectAllOption: true
		});
		$(document).on("click","#ReportsFilterSearchForm .clearall",function(e) {
			var frmObject = $("#"+$(this).attr("data-formname"));
			var filters =  jQuery.parseJSON($(this).attr("data-filter"));

			$.each(filters, function(i, item) {
				var elem = frmObject.find("#"+item.name);
				if(item.type=='checkbox') {
					if(item.value==true){
						elem.prop('checked', true);
					} else {
						elem.prop('checked', false);
					}
				} else if(item.type=='select') {
					elem.val(elem.prop("selectedIndex", 0).val())
				} else if(item.type=='multiselect') {
					elem.multiselect("clearSelection");
				} else if(item.type == 'daterange') {
					$('#from_date').val(item.fromdate);
					$('#to_date').val(item.todate);
				} else {
					elem.val((elem.data('defaultval') ? elem.data('defaultval') : ''));
				}
			});
			setReportFilterRequest();
		});
	});

	@if (LD::checkFeatureFlag('saved-applied-filter-nrr'))
		@if(in_array(Route::currentRouteName(),array('hoursOnNTH', 'IPEAs', 'PEPIs', 'WEPIs', 'candidateMatch', 'hoursRemoved', 'candidateStarts', 'grossMargin')))
		function saveFilters(key, value) {
			var filters = JSON.parse(sessionStorage.getItem("filters"));
			if (filters == null) {
				filters = {};
			}
			filters[key] = value;
			sessionStorage.setItem("filters", JSON.stringify(filters));
		}

		function getFilters(key) {
			var filters = JSON.parse(sessionStorage.getItem("filters"));
			if (filters == null) {
				return null;
			} else {
				return filters[key];
			}
		}

		var filterApplied = false;
		$('.filters-container input:not(.multiselect-native-select input), .filters-container select').each(function() {
			if (getFilters($(this).attr('id')) != null) {
				if (document.getElementById($(this).attr('id')).type == 'checkbox') {
					$(this).prop('checked', getFilters($(this).attr('id')));
				} else if (document.getElementById($(this).attr('id')).type == 'select-one' || document.getElementById($(this).attr('id')).type == 'text') {
					$(this).val(getFilters($(this).attr('id')));
				} else if (document.getElementById($(this).attr('id')).type == 'select-multiple') {
					$(this).val(getFilters($(this).attr('id'))).multiselect("rebuild");
				}
				filterApplied = true;
			}
		});
		$('.filters-container #ReportsFilterSearch').click(function() {
			$('.filters-container input:not(.multiselect-native-select input), .filters-container select').each(function() {
				if (document.getElementById($(this).attr('id')).type == 'checkbox') {
					saveFilters($(this).attr('id'), $(this).is(':checked'));
				} else {
					saveFilters($(this).attr('id'), $(this).val());
				}
			});
		});
		$('.filters-container .clearall').click(function() {
			sessionStorage.clear();
		});
		$(document).ready(function() {
			if (filterApplied) {
				setTimeout(function() {
					$('.filters-container #ReportsFilterSearch').click();
				}, 50);
			}
		});
		@endif
	@endif
</script>
