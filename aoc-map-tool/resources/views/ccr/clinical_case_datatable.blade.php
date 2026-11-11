<?php
use App\Helpers\H;
use App\Helpers\LD;
$isSelectField = false;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$buckets =H::getHelper("ClientCareCoordinatorCoverage")->getBucketArray();
$nextStepName = '';
$nextStepKey = 0;
$currentStep = array_search($type, array_keys($buckets));
$nextStep = $currentStep+1;
$values = array_values($buckets);
$keys = array_keys($buckets);
if ($nextStep < count($values)) {
	$nextStepName = $values[$nextStep];
	$nextStepKey = $keys[$currentStep + 1];
}
$ccm = H::getHelper('ConstantHelper')->getClinicalCareManager();
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-clinical-case-manager-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					if($key == 'scheduled_date' || $key == 'created_at'|| $key == 'appointment_date'){
						$fields[] = ["data"=>['_'=>$key.'.display','sort'=>$key.'.timestamp'],"name"=>$key.'.timestamp',"searchable"=>false];
					} elseif($key == 'proposed_next_step_date') {
							$fields[] = ["data"=>$key,"name"=>'proposed_next_step_date_raw',"searchable"=>false];
					} else {
						$fields[] = ["data"=>$key,"name"=>$key,"searchable"=>true];
					}
					if($key=='select') {
						$isSelectField = true;
						echo '<td><input class="form-input" id="select-all-checkbox-'.$type.'" type="checkbox" name="select-'.$type.'" value="0" /></td>';
					} else {
				?>
					<td><?php echo $data?></td>
				<?php } }  ?>
				<td>Action</td>
			</tr>
		</thead>
	</table>
</div>
<script>
	function showHideUdateButtonFor{{$typeFunction}}(isChecked) {
		$('#dataTable-clinical-case-manager-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			@if($typeFunction == 'Clinicalcompetencyreviews')
			var ccmuser = @php echo json_encode($ccm); @endphp;
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '<select name="ccruser" id="ccruser">';
			html += '<option value="">Please Select</option>';
			$.each(ccmuser, function (key, val) {
				html += '<option value="'+val['value']+'">'+val['text']+'</option>';
			});
			html += '</select>';
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="assignCCR{{$typeFunction}}(this)" class="btn btn-primary">Update</button>';
			html += '</div>';
			@endif
			$('#dataTable-clinical-case-manager-{{$type}}_wrapper').prepend(html);
		}
	}

	function assignCCR{{$typeFunction}}(button) {
		button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
		$('input[name="checkbox-{{$type}}[]"]:checked').each(function() {
			selectedIds.push(this.value);
		});
		$.ajax({
            url: "{{route('assignedCcr')}}",
            type: "POST",
            data: {
				_token: _token,
				ids: selectedIds.join(","),
				ccr_id: $('#ccruser').val(),
			},
            success: function(response) {
                button.disabled = false;
				if (response.success == 1) {
					$("#dataTable-clinical-case-manager-<?php echo $type?>").DataTable().ajax.reload();
				}
				$('input[name="checkbox-{{$type}}[]"]').prop('checked', false);
				$('#select-all-checkbox-{{$type}}').prop('checked', false);
				$('.dataTables_length').hide();
            },
        });
	}

	$(document).ready(function(){
		$('#select-all-checkbox-{{$type}}').click(function() {
			$('.select-all-{{$type}}.td').prop('checked', $(this).prop("checked"));
			showHideUdateButtonFor{{$typeFunction}}($(this).is(':checked'));
		});
	});
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
		var forms = '<?php  echo $form?>';
		columns_<?php echo $type?>.push({
			data: 'Action',
			name: 'Action',
			orderable: false,
            sClass:'dt-nowrap'
		});
		var <?php echo $type?> = $("#dataTable-clinical-case-manager-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"ajax": {
				"url": "{{route('dashboard.getclinicalcase')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $(""+forms+"").serializeArray();
                    if (window.GlobalLocationFilter) {
                        window.GlobalLocationFilter.applyToParams(
                            d,
                            "#dataTable-clinical-case-manager-<?php echo $type?>",
                            <?php echo json_encode($globalFilterFields) ?>
                        );
                    }
				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			@if($isSelectField)
			"columnDefs": [
				{
					orderable: false,
					className: 'select-checkbox',
					targets:   0
				}
			],
			@endif
			"drawCallback": function (oSettings, json) {
				var api = this.api();
            	var json = api.ajax.json();
				@if($type == 'clinical_competency_reviews')
					@if(LD::checkFeatureFlag('dashboard-past-due-count'))
						$("#<?php echo $type?>-counter").html("<span>("+<?php echo $type?>.page.info().recordsTotal+" | "+json.pastduecount+" Past Due)</span>");
					@else
					$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
					@endif
				@else
				$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
				@endif

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
			},
			"initComplete": function(settings, json) {
				jQuery("#dataTable-clinical-case-manager-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-clinical-case-manager-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-clinical-case-manager-<?php echo $type?>_filter input").val();

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
</script>
