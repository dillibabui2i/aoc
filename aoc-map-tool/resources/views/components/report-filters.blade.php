<?php
	use App\Helpers\LD;
?>
<style>
.ui-datepicker {z-index:9999 !important}
.multiselect-native-select select.select-step-level ~ .btn-group .dropdown-menu {
    max-height: 120px !important;
    overflow-y: auto !important;
}
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
					@if(
						Route::currentRouteName() == 'nurseChurn' ||
						Route::currentRouteName() == 'nurseChurnLegacy' ||
						Route::currentRouteName() == 'intakeReport.referrals' ||
						Route::currentRouteName() == 'intakeReport.nonadmit' ||
						Route::currentRouteName() == 'intakeReport.admits' ||
						Route::currentRouteName() == 'intakeReport.conversion' ||
						Route::currentRouteName() == 'candidateConversion' ||
						Route::currentRouteName() == 'ipeaSpeed'
					)
					<div class="col-auto">
						<input type="radio" name="period" value="quarter">
						<span>Quarter</span>
					</div>
					@endif
				</div>
			@endif
			@if($sourceFilters)
			@if (in_array(Route:: currentRouteName(), ['intakeReport.nonadmit', 'intakeReport.conversion']))
				<div class="form-group col-md-5">
			@else
				<div class="form-group col-md-4">
			@endif
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
						'intakeReport.referrals',
						'intakeReport.nonadmit',
						'intakeReport.admits',
						'intakeReport.conversion',
						'candidateConversion',
						'ipeaSpeed'
					]))
					@if(
						Route::currentRouteName() == 'monthlycalls'
					)
						<div class="form-group col-md-3 viewfilter">
							<label class="filter-label">View:</label>
							<div class="col-auto">
								<input type="radio" name="view" value="percentage">
								<span>% to Goal</span>
							</div>
							<div class="col-auto">
								<input type="radio" name="view" value="count" checked>
								<span>Count</span>
							</div>
						</div>
					@elseif(
						Route::currentRouteName() == 'intakeReport.referrals' ||
						Route::currentRouteName() == 'intakeReport.conversion' ||
						Route::currentRouteName() == 'intakeReport.admits'
					)
						<div class="form-group col-md-3 viewfilter">
							<label class="filter-label">View:</label>
							<div class="col-auto">
								<input type="radio" name="view" value="count" checked>
								<span>Count</span>
							</div>
							<div class="col-auto">
								<input type="radio" name="view" value="hours">
								<span>Hours</span>
							</div>
							@if (Route::currentRouteName() == 'intakeReport.conversion')
								<div class="col-auto">
									<input type="radio" name="view" value="percentage">
									<span>Conversion %</span>
								</div>
							@else
								<div class="col-auto">
									<input type="radio" name="view" value="percentage">
									<span>% to Goal</span>
								</div>
							@endif
						</div>
					@elseif(
						Route::currentRouteName() == 'intakeReport.nonadmit'
					)
						<div class="form-group col-md-3 viewfilter">
							<label class="filter-label">View:</label>
							<div class="col-auto">
								<input type="radio" name="view" value="count" checked>
								<span>Count</span>
							</div>
							<div class="col-auto">
								<input type="radio" name="view" value="hours">
								<span>Hours</span>
							</div>
						</div>
					@elseif(
						Route::currentRouteName() == 'intakeReport.nonadmit'
					)
						<div class="form-group col-md-3 viewfilter">
							<label class="filter-label">View:</label>
							<div class="col-auto">
								<input type="radio" name="view" value="count" checked>
								<span>Count</span>
							</div>
							<div class="col-auto">
								<input type="radio" name="view" value="hours">
								<span>Hours</span>
							</div>
						</div>
					@elseif(
						Route::currentRouteName() == 'candidateConversion'
					)
						<div class="form-group col-md-3 viewfilter">
							<label class="filter-label">View:</label>
							<div class="col-auto">
								<input type="radio" name="view" value="count" checked>
								<span>Count</span>
							</div>
							<div class="col-auto">
								<input type="radio" name="view" value="percentage">
								<span>% of Candidates</span>
							</div>
						</div>
					@elseif(
						Route::currentRouteName() == 'ipeaSpeed'
					)
						<div class="form-group col-md-3 viewfilter">
							<label class="filter-label">View:</label>
							<div class="col-auto">
								<input type="radio" name="view" value="percentage" checked>
								<span>% of Candidates</span>
							</div>
							<div class="col-auto">
								<input type="radio" name="view" value="avg">
								<span>Average Days</span>
							</div>
						</div>
					@else
                            <div class="form-group col-md-3 viewfilter">
                                <label class="filter-label">View:</label>

                                @if(
                                in_array(Route::currentRouteName(), ['IPEAs', 'PEPIs', 'WEPIs', 'candidateMatch', 'hoursRemoved', 'candidateStarts'])
                                && LD::checkFeatureFlag('distinguish-views-per-tab-nrr')
                                && $source === 'recruiter'
                                )
                                    <div class="col-auto">
                                        <input type="radio" name="view" value="hours" checked>
                                        <span>Hours</span>
                                    </div>
                                    <div class="col-auto">
                                        <input type="radio" name="view" value="count">
                                        <span>Count</span>
                                    </div>
                                    <div class="col-auto">
                                        <input type="radio" name="view" value="percentage">
                                        <span>% to Goal</span>
                                    </div>
                                @else
                                    <div class="col-auto">
                                        <input type="radio" name="view" value="count" checked>
                                        <span>Count</span>
                                    </div>
                                    <div class="col-auto">
                                        <input type="radio" name="view" value="percentage">
                                        <span>% to Goal</span>
                                    </div>
                                @endif

                                @if (Route::currentRouteName() == 'coverageBooked')
                                    <div class="col-auto">
                                        <input type="radio" name="view" value="worked">
                                        <span>Hours worked</span>
                                    </div>
                                @endif
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
							<select {{((isset($filter['disabled']) && $filter['disabled'] == true) ? 'disabled' : '')}}
								class="form-control" name="search[{{$filter['name']}}]" id="{{$filter['name']}}">
								<option value="">Select {{$filter['label']}}</option>
								@foreach($filter['source'] as $option)
								<option value="{{$option['value']}}">{{$option['text']}}</option>
								@endforeach
							</select>
						</div>
					@elseif($filter['type'] == 'dependablemultiselect')
						<div class="col-auto">
							<label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
							<select multiple class="selectpicker" data-placeholder="Select {{$filter['label']}}" data-live-search="true" name="search[{{$filter['name']}}][]" id="{{$filter['name']}}">
							</select>
						</div>
						<script type="text/javascript">
							$(document).ready(function() {
								$("#{{$filter['dependon']}}").change(function() {
									$.post( "{{$filter['dependonurl']}}", {'_token': "{{ csrf_token() }}", 'source_type': $(this).val()}, function( data ) {
										$("#{{$filter['name']}}").empty();
										$.each(data, function(index, value) {
											$("#{{$filter['name']}}").append('<option value="'+value.value+'">'+value.text+'</option>');
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
								id="{{$filter['name']}}" {{$filter['value'] == true ? 'checked="checked"' : ''}}
								placeholder="{{$filter['label']}}" />
							<label class="form-check-label" for="{{$filter['name']}}">{{$filter['label']}}</label>
						</div>
					@elseif($filter['type']=='multiselect')
						<div class="col-auto">
							<select multiple {{((isset($filter['disabled']) && $filter['disabled'] == true) ? 'disabled' : '')}}
							 class="selectpicker {{$filter['class'] ?? ''}}" data-placeholder="Select {{$filter['label']}}"
							 data-live-search="true" name="search[{{$filter['name']}}][]"
							 id="{{$filter['name']}}">
								@foreach($filter['source'] as $option)
								<option value="{{$option['value']}}">{{$option['text']}}</option>
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
							<input type="text" readonly class="form-control from_date" name="date[from]" id="from_date" value="{{$filter['fromdate']}}"/>
						</div>
						<div class="align-items-center d-flex ml-1 mr-1 float-left todiv">-</div>
						<div class="input-group">
							<div class="input-group-prepend">
								<span class="input-group-text" id="basic-addon1">To</span>
							</div>
							<input type="text" readonly class="form-control to_date" name="date[to]" id="to_date" value="{{$filter['todate']}}"/>
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
        $('#cover-spin').show(0);
		@if(Route::currentRouteName() == 'whiteboard')
			$.post( "{{Request::url()}}", $('#ReportsFilterSearchForm').serialize(), function( data ) {
                $('#cover-spin').hide(0);
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
                $('#cover-spin').hide(0);
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
            @if (
                LD::checkFeatureFlag('nrr-session-filter-retention') &&
                in_array(Route::currentRouteName(),array('hoursOnNTH', 'IPEAs', 'PEPIs', 'WEPIs', 'candidateMatch', 'hoursRemoved', 'candidateStarts', 'grossMargin'))
                )
                if (this.name === 'view') return;
                if (this.name === 'source') return;
            @endif

           setReportFilterRequest();
        });

        $('.selectpicker').not('#lob').multiselect({
            includeSelectAllOption: true
        });

        $('#lob').multiselect({
            includeSelectAllOption: false,
            nonSelectedText: 'Select LOB'
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
					elem.val(elem.prop("selectedIndex", 0).val());
                    elem.multiselect('clearSelection');
				} else if(item.type=='multiselect') {
					elem.multiselect("clearSelection");
				} else if(item.type == 'daterange') {
					$('#from_date').val(item.fromdate);
					$('#to_date').val(item.todate);
				} else {
					elem.val((elem.data('defaultval') ? elem.data('defaultval') : ''));
				}
			});

          const $appSource = $('#appSource');
          if ($appSource.length) {
            $appSource.val([]);
            $appSource.multiselect('refresh');
          }
			setReportFilterRequest();
		});
	});

	@if (LD::checkFeatureFlag('saved-applied-filter-nrr') && !LD::checkFeatureFlag('nrr-session-filter-retention'))
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

        @if (LD::checkFeatureFlag('nrr-session-filter-retention'))
        (function () {
            const RouteName = @json(Route::currentRouteName());
            const routes = [
                'candidates',
                'IPEAs',
                'PEPIs',
                'WEPIs',
                'candidateMatch',
                'grossMargin',
                'candidateStarts',
                'hoursRemoved'
            ];
            if (!routes.includes(RouteName)) {
                return;
            }
            const EXCLUDED_SESSION_FIELDS = ['view', 'search[lob][]'];
            const STORAGE_KEY = 'nrr.session.filters.shared';
            const $form = $('#ReportsFilterSearchForm');
            if (!$form.length) {
                return;
            }
            let isRestoring = false;
            let isInitialLoad = true;

            function saveState() {
                if (isRestoring) return;
                if (isInitialLoad) return;

                const state = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || '{}');

                $form.find('input, select').each(function () {
                    const name = this.name;
                    if (!name) return;
                    if (EXCLUDED_SESSION_FIELDS.includes(name)) return;
                    if (this.type === 'checkbox') {
                        state[name] = this.checked;
                    } else if (this.type === 'radio') {
                        if (this.checked) state[name] = this.value;
                    } else if (this.multiple) {
                        state[name] = $(this).val() || [];
                    } else {
                        state[name] = this.value;
                    }
                });
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
            }

            function restoreState() {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (!raw) return;
                let state;
                try {
                    state = JSON.parse(raw);
                } catch {
                    return;
                }
                isRestoring = true;
                Object.entries(state).forEach(([name, value]) => {
                    const $el = $form.find(`[name="${name}"], [name="${name}[]"]`);
                    if (!$el.length) return;
                    if ($el.is(':radio')) {
                        $el.filter(`[value="${value}"]`).prop('checked', true);
                    } else if ($el.is(':checkbox')) {
                        $el.prop('checked', !!value);
                    } else if ($el.next('.btn-group').length) {
                        const values = Array.isArray(value) ? value : [value];
                        // trigger dependencies FIRST (start ajax)
                        if (name.includes('sourceType') || name.includes('state')) {
                            $el.multiselect('select', values);
                            $el.multiselect('refresh');
                            setTimeout(() => {
                                $el.trigger('change');
                            }, 0);
                            return;
                        }
                        // dependent multiselects (branch, appSource)
                        if (name.includes('branch')
                            || name.includes('appSource')
                            || name.includes('aoc_facility_id')) {
                            const interval = setInterval(() => {
                                if ($el.find('option').length > 0) {
                                    $el.val(values);
                                    $el.multiselect('select', values);
                                    $el.multiselect('refresh');
                                    clearInterval(interval);
                                }
                            }, 50);

                            return;
                        } else {
                            // normal multiselect (LOB, recruiter, etc)
                            $el.multiselect('select', values);
                            $el.multiselect('refresh');
                        }
                    } else {
                        $el.val(value);
                    }
                });
                setTimeout(() => {
                    isRestoring = false;
                }, 500);
            }
            function restoreViewIfExists() {
                const raw = sessionStorage.getItem(STORAGE_KEY);
                if (!raw) return;
                let state;
                try {
                    state = JSON.parse(raw);
                } catch {
                    return;
                }
                if (!state.view) return;
                const $viewRadios = $form.find('input[name="view"]');
                if (!$viewRadios.length) return;
                isRestoring = true;
                $viewRadios
                    .filter(`[value="${state.view}"]`)
                    .prop('checked', true);
                setTimeout(() => {
                    isRestoring = false;
                }, 0);
            }
            $form.on('change input', 'input, select', function () {
              if (isRestoring) return;
              saveState();
            });
            $form.on('change', 'input[name="view"]', function () {
                if (isRestoring) return;
                const raw = sessionStorage.getItem(STORAGE_KEY) || '{}';
                let state;
                try {
                    state = JSON.parse(raw);
                } catch {
                    state = {};
                }
                state.view = this.value;
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(state));
                setReportFilterRequest();
            });
            $form.on('change', 'input[name="source"]', function () {
                if (isRestoring) return;
                const source = this.value;
                if (source !== 'recruiter') {
                    setReportFilterRequest();
                    return;
                }
                //RECRUITER → restore view, then submit
                isRestoring = true;
                restoreViewIfExists();
                requestAnimationFrame(() => {
                    isRestoring = false;
                    setReportFilterRequest();
                });
            });
            // VIEW should ALWAYS be visible & persistent for these routes
            const routesWithPersistentView = [
                'IPEAs',
                'PEPIs',
                'WEPIs',
                'candidateMatch',
                'hoursRemoved',
                'candidateStarts'
            ];
            if (routesWithPersistentView.includes(RouteName)) {
                $('.viewfilter').show();
            }
            $form.on('click', '.clearall', function () {
                const defaultState = {
                    source: 'recruiter',
                    view: 'count'
                };
                sessionStorage.setItem(STORAGE_KEY, JSON.stringify(defaultState));
                //SYNC UI WITH DEFAULT STATE
                // 1. source → recruiter
                const $sourceRecruiter = $form.find('input[name="source"][value="recruiter"]');
                if ($sourceRecruiter.length) {
                    $sourceRecruiter.prop('checked', true).trigger('change');
                }
                // 2. viewfilter must be visible
                $('.viewfilter').show();
                // 3. view → count
                setTimeout(() => {
                    const $viewCount = $form.find('input[name="view"][value="count"]');
                    if ($viewCount.length) {
                        $viewCount.prop('checked', true).trigger('change');
                    }
                }, 0);
            });
            $(document).ready(function () {
                restoreState();
                setTimeout(() => {
                    if (routesWithPersistentView.includes(RouteName)) {
                        $('.viewfilter').show();
                        restoreViewIfExists();
                    }
                }, 0);
                setTimeout(() => {
                    isRestoring = false;
                    const source = $form.find('input[name="source"]:checked').val();
                    if (source) {
                        $form.find(`input[name="source"][value="${source}"]`).trigger('change');
                    }

                  setTimeout(() => {
                    isInitialLoad = false;
                  }, 300);
                }, 0);
            });
        })();
    @endif
</script>
