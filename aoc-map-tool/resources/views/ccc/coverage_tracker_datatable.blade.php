<?php
use App\Helpers\H;
use App\Helpers\LD;
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
?>
<style>
	.accordion.dashboard #dataTable-clientcare-coverage-tracker-{{ $type }}_wrapper .dataTables_length {
		display: block !important;
	}
</style>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-clientcare-coverage-tracker-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					$fields[] = ["data"=>$key,"name"=>$key];
					if($key=='select') {
						$checkbox = '<td><input class="form-input" id="select-all-checkbox-'.$type.'" type="checkbox" name="select-'.$type.'" value="0" /></td>';
						echo $checkbox;
					} else {
				?>
					<td><?php echo $data?></td>
				<?php } } ?>
				<td>Action</td>
			</tr>
		</thead>
	</table>
</div>
<?php
	$fields[] = ["data"=>"Action"];
?>
<script>
	function updateSelectedCountFor{{$typeFunction}}() {
		$('#selected-count-{{$type}}').text($('.select-all-{{$type}}.td:checked').length + ' Selected');
	}
	function showHideUdateButtonFor{{$typeFunction}}(isChecked) {
		let bucketType = '<?php echo $type ?>';
		$('#dataTable-clientcare-coverage-tracker-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			if(bucketType != 'escalated'){
				html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="showCoverageTrackerStepForm(\'{{$type}}\', \'-1\', this,\'{{$nextStepKey}}\')" class="btn btn-primary">Update Selected</button>';
			}
			@if (LD::checkFeatureFlag('add-to-tracker-from-dashboard-ccc'))
				html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="addCoverageShiftTracker(\'dashboard_buckets\', \'{{$type}}\')" class="btn btn-primary">Add to Tracker</button>';
			@endif
			html += '</div>';
			$('#dataTable-clientcare-coverage-tracker-{{$type}}_wrapper').prepend(html);
		}
	}
	$(document).ready(function(){
		$('#select-all-checkbox-{{$type}}').click(function() {
			$('.select-all-{{$type}}.td').prop('checked', $(this).prop("checked"));
			showHideUdateButtonFor{{$typeFunction}}($(this).is(':checked'));
			updateSelectedCountFor{{$typeFunction}}();
		});
	});
	function <?php  echo $type?>(){
		
		var fields_<?php echo $type?> = '<?php echo json_encode($fields)?>';
		fields_<?php echo $type?> = JSON.parse(fields_<?php echo $type?>);
		var columns_<?php echo $type?> = [];
		$.each(fields_<?php echo $type?>, function(key, val) {
			if(val.data != 'Action' && val.data != 'select') {
				columns_<?php echo $type?>.push({
				data: val.data,
				name: val.name,
				visible: typeof val.visible !== 'undefined' ? val.visible : true,
				width: typeof val.width !== 'undefined' ? val.width : ''
				});
			}else {
				columns_<?php echo $type?>.push({
				data: val.data,
				name: val.name,
				visible: typeof val.visible !== 'undefined' ? val.visible : true,
				width: typeof val.width !== 'undefined' ? val.width : '',
				orderable: false,
				});
			}
		});
		var <?php echo $type?> = $("#dataTable-clientcare-coverage-tracker-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"ajax": {
				"url": "{{route('clientcare.getclientcarecoveragetracker')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $("#ccccoveragetrackerform").serializeArray();

				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"lengthMenu": [10, 25, 50, 100],
			"pageLength": 10,
			"columns": columns_<?php echo $type?>,
			"drawCallback": function (oSettings, json) {
				$("#<?php echo $type?>-counter").html(oSettings.json.counthours);

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
					updateSelectedCountFor{{$typeFunction}}();
				});
				updateSelectedCountFor{{$typeFunction}}();
			},
			"initComplete": function(settings, json) {
				@if (array_key_exists('select', $dataTable))
				$('#dataTable-clientcare-coverage-tracker-{{ $type }}_wrapper .dataTables_length').append('<span id="selected-count-{{ $type }}" class="selected-count-label" style="margin-left: 15px;">0 Selected</span>');
				@endif
				jQuery("#dataTable-clientcare-coverage-tracker-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;	 
				jQuery("#dataTable-clientcare-coverage-tracker-<?php echo $type?>_filter input").on("keyup", function(e) {
					
					var search = jQuery("#dataTable-clientcare-coverage-tracker-<?php echo $type?>_filter input").val();
												
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
	function showCoverageTrackerStepForm(bucket_type, id = 0, button) {
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
            url: "{{ route('dashboard.getcoveragetrackerstepform') }}",
            type: "GET",
            data: {
				type: bucket_type,
				_token: _token,
				ids: ids
			},
            success: function(response) {
                button.disabled = false;
				if (response) {
					$("#tempModalCnt").remove();
					var $div = $('<div />').appendTo('body');
					$div.attr('id', 'tempModalCnt').html(response);
					$('#clientcarecoveragetracker_form').modal('show');
					$(".modal:visible").length && $(document.body).addClass("modal-open");
				}
            },
        });
	}

</script>
