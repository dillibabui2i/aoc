<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$allBuckets =H::getHelper("IntakeCoordinatorException")->getBuckets();
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
	<table class="table table-bordered" id="dataTable-intake-exception-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					if($key == 'last_monthly_followup'){
						$fields[] = ["data"=>['_'=>'last_monthly_followup.display','sort'=>'last_monthly_followup.timestamp'],"name"=>'last_monthly_followup.timestamp',"searchable"=>false];
					}elseif($key == 'CF_GoalSOCDate'){
						$fields[] = ["data"=>['_'=>'CF_GoalSOCDate.display','sort'=>'CF_GoalSOCDate.timestamp'],"name"=>'CF_GoalSOCDate.timestamp',"searchable"=>false];
					}elseif($key == 'shift_date'){
						$fields[] = ["data"=>['_'=>'shift_date.display','sort'=>'shift_date.timestamp'],"name"=>'shift_date.timestamp',"searchable"=>false];
					}elseif($key == 'intake_approved_date'){
						$fields[] = ["data"=>['_'=>'intake_approved_date.display','sort'=>'intake_approved_date.timestamp'],
						"name"=>'intake_approved_date.timestamp',"searchable"=>false];
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
				<?php if($type =='intake_coverage_shift_covered_by_perm_nurse'
				|| $type == 'intake_coverage_shift_before_goal_soc_date') { ?>
				return '<a href="javascript:void(0);" title = "View Tracker Details" \
				onclick="getDetailsOfCoverageTracker('+row['id']+')">\
				<i class="fa fa-eye" aria-hidden="true"></i></a> | \
				<a href="javascript:void(0)" \
				onclick=\'getClientDetails("' + row['aoc_patient_id'] + '", "", null, null, "' + row['id'] + '")\'>\
				<i class="fa fa-user" aria-hidden="true"></i></a>';
				<?php } elseif($type =='intake_unassigned_care_team'){ ?>
return '<a href="javascript:void(0)" \
				onclick=\'getClientDetails("' + row['aoc_patient_id'] + '", "", null, null, "' + row['id'] + '")\'>\
				<i class="fa fa-eye" aria-hidden="true"></i></a> | <a href="javascript:void(0)" \onclick="setUnassignedCareTeam('+row['aoc_patient_id']+',\'<?php echo $type ?>\')">\
<i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
				<?php } else { ?>
				return '<a href="javascript:void(0)" \
				onclick="getExceptionAddressed('+row['aoc_patient_id']+',\'<?php echo $type ?>\')">\
				<i class="fa fa-check-square-o" aria-hidden="true"></i></a> \
				| <a href="javascript:void(0)" \
				onclick=\'getClientDetails("' + row['aoc_patient_id'] + '", "", null, null, "' + row['id'] + '")\'>\
				<i class="fa fa-eye" aria-hidden="true"></i></a>';
				<?php } ?>
			}
		});

		var <?php echo $type?> = $("#dataTable-intake-exception-<?php echo $type?>").DataTable({
			"order": [
				[3, "desc"]
			],
			"columnDefs": [
				{'orderable': false, 'targets': [{{implode(",", $selectColumnIndex)}}]},
				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('intake.getintakeexceptions')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $("#intakeexceptionform").serializeArray();

                    if (window.GlobalLocationFilter) {
                        window.GlobalLocationFilter.applyToParams(d, "#dataTable-intake-exception-<?php echo $type?>");
                    }
				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			"drawCallback": function (oSettings, json) {
					$("#intake-<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
			},
			createdRow: function( row, data, dataIndex ) {
				if(data.planned_time_diff > 24){
					$(row).addClass("dashboard-older-record");
				}

    		},
			"initComplete": function(settings, json) {

				jQuery("#dataTable-intake-exception-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-intake-exception-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-intake-exception-<?php echo $type?>_filter input").val();

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
		$('#dataTable-intake-exception-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="getCheckedOffMissedVisitForms(\'{{$type}}\', \'-1\', this,\'{{$nextStepKey}}\')" class="btn btn-primary">Update Selected</button>';
			html += '</div>';
			$('#dataTable-intake-exception-{{$type}}_wrapper').prepend(html);
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

</script>
