<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$allBuckets =H::getHelper("ClientCareCoordinatorException")->getBuckets();
$nextStepName = '';
$nextStepKey = 0;
$currentStep = array_search($type, array_keys($allBuckets));
$nextStep = $currentStep+1;
$values = array_values($allBuckets);
$keys = array_keys($allBuckets);
if ($nextStep < count($values)) {
	$nextStepName = $values[$nextStep];
	$nextStepKey = $keys[$currentStep + 1];
}
$selectColumnIndex = [];
$count = 0;
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-clientcare-exception-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) { 
					if($key == 'last_monthly_followup'){
						$fields[] = ["data"=>['_'=>'last_monthly_followup.display','sort'=>'last_monthly_followup.timestamp'],"name"=>'last_monthly_followup.timestamp',"searchable"=>false];
					}elseif($key == 'CF_GoalSOCDate'){
						$fields[] = ["data"=>['_'=>'CF_GoalSOCDate.display','sort'=>'CF_GoalSOCDate.timestamp'],"name"=>'CF_GoalSOCDate.timestamp',"searchable"=>false];
					}elseif($key == 'shift_date'){
						$fields[] = ["data"=>['_'=>'shift_date.display','sort'=>'shift_date.timestamp'],"name"=>'shift_date.timestamp',"searchable"=>false];
					}elseif($key == 'select') {
						$selectColumnIndex[] = $count;
						$fields[] = ["data" => $key, "name" => $key, "searchable" => false];
					}else{
						$fields[] = ["data"=>$key,"name"=>$key,"searchable"=>true];
					}
					if($key == 'select'){
						$checkbox = '<td><input class="form-input" id="select-all-checkbox-'.$type.'" type="checkbox" name="select-'.$type.'" value="0" /></td>';
						echo $checkbox;
					}else{
				?>
					<td><?php echo $data?></td>
				<?php } $count++;}  ?>
				<td>Action</td>
			</tr>
		</thead>
	</table>
</div>
<script>
	function <?php  echo $type?>(){
		
		var fields_<?php echo $type?> = '<?php echo json_encode($fields)?>';
		fields_<?php echo $type?> = JSON.parse(fields_<?php echo $type?>);
		var columns_<?php echo $type?> = [];
		$.each(fields_<?php echo $type?>, function(key, val) {
            columns_<?php echo $type?>.push({
                data: val.data,
                name: val.name,
                visible: typeof val.visible !== 'undefined' ? val.visible : true,
                width: typeof val.width !== 'undefined' ? val.width : ''
            });
        });
		columns_<?php echo $type?>.push({
			data: null,
			width: '5%',
			orderable: false,
			render: function(data, type, row) {
				<?php
					if($type=='weekly_nth_calls')  { ?>

						<?php if(LD::checkFeatureFlag('weekly-monthly-bucket-data-from-new-table'))  { ?>
							return '<a href="javascript:void(0)" onclick=\'getWeeklyMonthlyInputCall("' + row['id'] + '","weekly")\'>\
								<i class="fa fa-calendar" aria-hidden="true"></i>\
								</a>&nbsp;<a href="javascript:void(0)" onclick=\'getClientDetails("' + row['aoc_patient_id'] + '")\'>\
									<i class="fa fa-user" aria-hidden="true"></i></a>';
						<?php } else { ?>
							return '<a href="javascript:void(0)" onclick=\'getWeeklyInputCall("' + row['id'] + '")\'>\
								<i class="fa fa-calendar" aria-hidden="true"></i></a>&nbsp;\
								<a href="javascript:void(0)" onclick=\'getClientDetails("' + row['aoc_patient_id'] + '")\'>\
									<i class="fa fa-user" aria-hidden="true"></i></a>';
						<?php } ?>
					<?php } else if($type=='monthly_calls') { ?>
						<?php if(LD::checkFeatureFlag('weekly-monthly-bucket-data-from-new-table'))  { ?>
							return '<a href="javascript:void(0)" onclick=\'getWeeklyMonthlyInputCall("' + row['id'] + '","monthly")\'>\
								<i class="fa fa-calendar" aria-hidden="true"></i></a>&nbsp;\
								<a href="javascript:void(0)" onclick=\'getClientDetails("' + row['aoc_patient_id'] + '")\'>\
									<i class="fa fa-user" aria-hidden="true"></i></a>';
						<?php } else { ?>
							return '<a href="javascript:void(0)" onclick=\'getMonthlyInputCall("' + row['id'] + '")\'>\
								<i class="fa fa-calendar" aria-hidden="true"></i></a>&nbsp;\
								<a href="javascript:void(0)" onclick=\'getClientDetails("' + row['id'] + '")\'>\
									<i class="fa fa-user" aria-hidden="true"></i></a>';
						<?php } ?>
					<?php } else if($type=='ccc_missed_visits'  || $type=='planned_visits_with_no_clinician') { ?> 
							return '<a href="javascript:void(0)" onclick="getCheckedOffMissedVisitForm('+row['id']+',\'<?php echo $type ?>\')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
					<?php } else if($type=='ccc_new_clients') { ?>
							return '<a href="javascript:void(0)" onclick=\'getClientDetails("' + row['id'] + '")\'><i class="fa fa-user" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick="getClientIntakeOutcomForm('+row['id']+')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
					<?php } else if($type=='ccc_pending_intake_clients') { ?>
							return '<a href="javascript:void(0)" onclick=\'getClientDetails("' + row['id'] + '")\'><i class="fa fa-user" aria-hidden="true"></i></a>';
					<?php } else if($type=='staffed_coverage_shifts_not_updated_in_kantime') { ?>
							return '<a href="javascript:void(0)" onclick="getExceptionAddressed('+row['MID']+',\'<?php echo $type ?>\')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick=\'getClientDetails("' + row['PID'] + '")\'><i class="fa fa-user" aria-hidden="true"></i></a>';
					<?php } else if($type=='ccc_intake_coverage_shifts_booked') { ?>
						let actionstr = '<a href="javascript:void(0)" onclick=\'getClientDetails("' + row['aoc_patient_id'] + '")\' ><i class="fa fa-user" aria-hidden="true"></i></a>';
						@if(LD::checkFeatureFlag('edit-icon-coverage-tracker-show-all-page'))
						actionstr = actionstr+'<a href="javascript:void(0)" onclick=\'editCoverageTrackerRecord("' + row['id'] + '")\' ><i class="fa fa-edit" aria-hidden="true"></i></a>';
						@endif
						actionstr = actionstr+'<a href="javascript:void(0)" title = "Complete" onclick=\'completeCoverge("' + row['id'] + '")\' ><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
						actionstr = actionstr+'<a href="javascript:void(0)" onclick=\'showDeleteCoverageTrackerPopup("' + row['id'] + '")\' ><i class="fa fa-trash-o" aria-hidden="true"></i></a>';
						return actionstr;
					<?php } else if($type=='coverage_shift_without_kantime_clinician') { ?>
							return '<a href="javascript:void(0)" onclick=\'editCoverageTrackerRecord("' + row['id'] + '")\' ><i class="fa fa-edit" aria-hidden="true"></i></a>';
					<?php } elseif($type=='ccc_intake_coverage_shift_covered_by_perm_nurse'
					|| $type == 'ccc_intake_coverage_shift_before_goal_soc_date') { ?>
						return '<a href="javascript:void(0)" \
						onclick="getExceptionAddressed('+row['id']+',\'<?php echo $type ?>\')">\
							<i class="fa fa-check-square-o" aria-hidden="true"></i></a> | <a href="javascript:void(0)" \
							onclick=\'getClientDetails("' + row['aoc_patient_id'] + '")\'>\
							<i class="fa fa-user" aria-hidden="true"></i></a> \
							| <a href="javascript:void(0)" onclick=\'editCoverageTrackerRecord("' + row['id'] + '")\' >\
							<i class="fa fa-edit" aria-hidden="true"></i></a>';
					<?php } elseif($type == 'clients_without_kantime_match') { ?>
							return '<a title="View Client Profile" href="javascript:void(0)" \
									onclick=\'getClientDetails("' + row['id'] + '")\' >\
									<i class="fa fa-user" aria-hidden="true"></i></a>';
					<?php } elseif($type == 'active_coverage_exception') { ?>
							let actionstr = '<a href="javascript:void(0)" onclick=\'showActiveCoverageOutcome(' + row['id'] +
							',"active_coverage_exception")\'><i class="fa fa-calendar" aria-hidden="true" title = "Documentation"></i></a>'
							actionstr += '<a href="javascript:void(0)" onclick=\'getClinicianProfile("' + row['aoc_provider_id'] +
							'")\' ><i class="fa fa-user-md" aria-hidden="true" title = "Clinician Profile"></i></a>';
							return actionstr;
					<?php } else { ?>
						@if(LD::checkFeatureFlag('add-color-highlights-ccc-exception-bucket'))
							@if(LD::checkFeatureFlag('add-new-widget-to-client-care-exception'))
								return '<a href="javascript:void(0)" onclick=\'openBuildScheduleForm(' + row['id'] + ')\'><i class="fa fa-calendar" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick="getExceptionAddressed('+row['aoc_patient_id']+',\'<?php echo $type ?>\')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick=\'getClientDetails("' + row['aoc_patient_id'] + '")\'><i class="fa fa-eye" aria-hidden="true"></i></a>';
							@else
								return '<a href="javascript:void(0)" onclick="getExceptionAddressed('+row['aoc_patient_id']+',\'<?php echo $type ?>\')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick=\'getClientDetails("' + row['aoc_patient_id'] + '")\'><i class="fa fa-eye" aria-hidden="true"></i></a>';	
							@endif
						@else
							@if(LD::checkFeatureFlag('add-new-widget-to-client-care-exception'))
								return '<a href="javascript:void(0)" onclick=\'openBuildScheduleForm(' + row['id'] + ')\'><i class="fa fa-calendar" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick=\'getClientDetails("' + row['aoc_patient_id'] + '")\'><i class="fa fa-eye" aria-hidden="true"></i></a>';
							@else
								return '<a href="javascript:void(0)" onclick=\'getClientDetails("' + row['aoc_patient_id'] + '")\'><i class="fa fa-eye" aria-hidden="true"></i></a>';
							@endif
						@endif
					<?php } ?>
			}
		});
		
		var <?php echo $type?> = $("#dataTable-clientcare-exception-<?php echo $type?>").DataTable({
			"order": [
				[3, "desc"]
			],
			"columnDefs": [
				{'orderable': false, 'targets': [{{implode(",", $selectColumnIndex)}}]},
				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },          

			],
			"ajax": {
				"url": "{{route('clientcare.getclientcareexceptions')}}?type=<?php echo $type?>",
				data: function(d) {
					<?php
					if($type=='weekly_nth_calls') { ?>
						d.filters =  $("#weeklyclientcareform").serializeArray();
					<?php } else if($type=='monthly_calls') { ?>
						d.filters =  $("#monthlyclientcareform").serializeArray();
					<?php } else if($type=='ccc_missed_visits' || $type=='planned_visits_with_no_clinician') { ?>
						d.filters =  $("#cccmissedvisitsform").serializeArray();
					<?php } else if($type=='ccc_new_clients' || $type=='ccc_pending_intake_clients' || $type=='ccc_intake_coverage_shifts_booked') { ?>
						d.filters =  $("#cccintakeclientsform").serializeArray();
					<?php }  else {?>
						d.filters =  $("#clientcareform").serializeArray();
					<?php } ?>

				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			"drawCallback": function (oSettings, json) {
				@if (LD::checkFeatureFlag('add-planned-hours-to-ccc-missed-visits'))
					@if ($type == 'ccc_missed_visits' || $type == 'planned_visits_with_no_clinician')
						$("#<?php echo $type?>-counter").html(oSettings.json.plannedhours);
					@else
						$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
					@endif
				@else
					$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
				@endif

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});

				<?php if($type=='weekly_nth_calls' || $type=='monthly_calls') { ?> 
						let _token = $('meta[name="csrf-token"]').attr('content');
						$.ajax({
							url: "{{route('ccc.progressbar')}}",
							type: 'POST',
							dateType:"html",
							data: {
								bucketType:"<?php echo $type?>",
								_token: _token,
								<?php if($type=='weekly_nth_calls') { ?>
										filters:  $("#weeklyclientcareform").serializeArray()
								<?php } else if($type=='monthly_calls') { ?>
										filters: $("#monthlyclientcareform").serializeArray()
								<?php } ?>

							},
							success: function(res) {
								
								$("#<?php echo $type?>_progressbar").html(res.html);

								/*if(res.percentage==100) {
									startConfetti();
									setTimeout(function() {
										stopConfetti();
									}, 1000);
								}*/
							}
						});
				<?php } ?>
				
			},
			createdRow: function( row, data, dataIndex ) {
				<?php if(LD::checkFeatureFlag('ccc-missed-visit-overdue-logic')) { ?>
						<?php if($type=='ccc_missed_visits' || $type=='planned_visits_with_no_clinician') { ?> 
									if(data.checked_off_pasttime >= 48) {
										$(row).addClass("dashboard-older-record");
									} else if(data.checked_off_pasttime > 24) {
										$(row).addClass("dashboard-renewable-doesnt-match-outcome");
									}
								
						<?php } else { ?>
								if(data.planned_time_diff > 24){
									$(row).addClass("dashboard-older-record");
								}
						<?php } ?>
				<?php } else { ?>
					if(data.planned_time_diff > 24){
						$(row).addClass("dashboard-older-record");
					}
				<?php } ?> 

    		},
			"initComplete": function(settings, json) {
				
				jQuery("#dataTable-clientcare-exception-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;	 
				jQuery("#dataTable-clientcare-exception-<?php echo $type?>_filter input").on("keyup", function(e) {
					
					var search = jQuery("#dataTable-clientcare-exception-<?php echo $type?>_filter input").val();
												
					clearTimeout(searchDelay);										 
					searchDelay = setTimeout(function() {
						if (search != null && search.length >=3)
						<?php echo $type?>.search(search).draw();
						if (search.length==0 && e.keyCode==8)
						<?php echo $type?>.search(search).draw();
					}, dataTableDelay);
				});                
			},
			
		});
	}
	function showHideUdateButtonFor{{$typeFunction}}(isChecked) {
		$('#dataTable-clientcare-exception-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="getCheckedOffMissedVisitForms(\'{{$type}}\', \'-1\', this,\'{{$nextStepKey}}\')" class="btn btn-primary">Update Selected</button>';
			html += '</div>';
			$('#dataTable-clientcare-exception-{{$type}}_wrapper').prepend(html);
		}
	}
	$(document).ready(function(){
		$('#select-all-checkbox-{{$type}}').click(function() {
			$('.select-all-{{$type}}.td').prop('checked', $(this).prop("checked"));
			showHideUdateButtonFor{{$typeFunction}}($(this).is(':checked'));
		});
	});
	function getCheckedOffMissedVisitForms(bucket_type, id = 0, button) {
    let _token = $('meta[name="csrf-token"]').attr('content');
	let ids = 0;
		if (id > 0) {
			ids = id;
		} else if(id < 0) {
			var selectedIds = [];
			$('.select-all-' + bucket_type + '.td:checked').each(function() {
				selectedIds.push(this.value);
			});
			ids = selectedIds.join(",");
		}
    $.ajax({
        url: "{{route('dashboard.missedvisitforms')}}",
        type: 'get',
        data: {
			type: bucket_type,
			_token: _token,
			ids: ids
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#missedvisitsmodal').modal('show');
        }
    });
}
function completeCoverge(id){
    modalConfirm("Has this coverage shift been built in KanTime?",'Yes','No',function(res, modelDialog){
        if (res) {
            let _token = $('meta[name="csrf-token"]').attr('content');
            let ids = id;
            $.ajax({
                url: "{{route('coverage.makecoveragecompleted')}}",
                type: "GET",
                data: {
                    id: ids,
                    _token: _token
                },
                success: function(response) {
                    if(response.success ==1 ) {
						$('#dataTable-clientcare-exception-ccc_intake_coverage_shifts_booked').DataTable().ajax.reload();
                    }
                },
            });
        }
        modelDialog.modal('hide');
    });
    return false;
}
</script>