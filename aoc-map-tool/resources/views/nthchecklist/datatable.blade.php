<?php
use App\Helpers\H;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$allBuckets =H::getHelper("NTHChecklist")->getBuckets();
$nonStepBucket = ['social_media_sponsored','hub_city_requisition','escaleted'];
$moveStepBucket = ['hub_city_requisition','escaleted'];
$buckets = [
                'text_blast' => "Text Blast", 'pet' => 'P.E.T', 'social_media' => 'Social Media',
                'private_message' => 'Private Message', 'linkedin' => 'Linkedin', 'indeed' => "Indeed", 'cold_call' => "Cold Call", 'mail_matters' => 'Mail Matters',
                'bon_nurse_search' => 'Bon Nurse Search','social_media_sponsored' => 'Social Media Sponsored','escaleted' => 'Escalated'
            ];
$nextStepName = '';
$nextStepKey = 0;
if (!in_array($type, $moveStepBucket)) {
	$currentStep = array_search($type, array_keys($buckets));
	$nextStep = $currentStep+1;
	$values = array_values($buckets);
	$keys = array_keys($buckets);
	if ($nextStep < count($values)) {
		$nextStepName = $values[$nextStep];
		$nextStepKey = $keys[$currentStep + 1];
	}
}
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-nthchecklist-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>

				<?php foreach($dataTable as $key => $data) {
						if($key=='SocialMediaStarted'){
							$fields[] = ["data"=>['_'=>'SocialMediaStarted.display','sort'=>'SocialMediaStarted.timestamp'],"name"=>'SocialMediaStarted.timestamp',"searchable"=>false];
						}elseif($key=='start_date'){
							$fields[] = ["data"=>['_'=>'start_date.display','sort'=>'start_date.timestamp'],"name"=>'start_date.timestamp',"searchable"=>false];
						}else{
							$fields[] = ["data"=>$key,"name"=>$key];
						}
						if($key=='select') {
							$checkbox = '<td>';
							if(!in_array($type,$moveStepBucket)) {
								$checkbox .= '<input class="form-input" id="select-all-checkbox-'.$type.'" type="checkbox" name="select-'.$type.'" value="0" />';
							}
							$checkbox .= '</td>';
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
<x-modal>
    <x-slot name="id"><?php echo 'move_nth_bucket_'.$type?></x-slot>
    <x-slot name="title">Info</x-slot>
    <x-slot name="body">
        <table class="table" id="<?php echo 'move_nth_bucket_table_'.$type?>">
        </table>
        <div class="text-center"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
	var <?php echo $type.'_bucket_datatable';?>;
	function showHideUdateButtonFor{{$typeFunction}}(isChecked) {
		$('#dataTable-nthchecklist-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			@php if(!in_array($type,$nonStepBucket)) { @endphp
			html += '<button type="button" onclick="showNthForm(\'{{$type}}\', \'-1\', this)" class="btn btn-primary">Update Selected</button>';
			@php } if(!in_array($type,$moveStepBucket)) { @endphp
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="moveNthStep(\'{{$type}}\', \'-1\', this,\'{{$nextStepKey}}\')" class="btn btn-primary">Move To {{ $nextStepName  }}</button>';
			@php }  @endphp
			html += '</div>';
			$('#dataTable-nthchecklist-{{$type}}_wrapper').prepend(html);
		}
	}
	$(document).ready(function(){
		$('#select-all-checkbox-{{$type}}').click(function() {
			$('.select-all-{{$type}}.td').prop('checked', $(this).prop("checked"));
			showHideUdateButtonFor{{$typeFunction}}($(this).is(':checked'));
		});

		if($("#reload-<?php echo $type?>").val()==1) {
			let _token = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				url: "{{ route('reloadCountNTH') }}?type=<?php echo $type?>",
				type: "GET",
				data: {
					_token: _token
				},
				success: function(response) {
					//console.log(response);
					var jsonObject = JSON.parse(response);
					var type = jsonObject.type;
					var idObject = type.replace(/ /g,"_");
					$("#<?php echo $type?>-counter").html(jsonObject.counter);
				},
			});
		}
	});
	function <?php echo $type?>(){
		<?php echo $type.'_bucket_datatable'; ?> = $("#dataTable-nthchecklist-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [
				{
				orderable: false,
				className: 'select-checkbox',
				targets:   0
			},
				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('dashboard.getNTHChecklist')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters = $("#nthChecklistFiltersDashboard").serializeArray();
                    if (window.GlobalLocationFilter) {
                        window.GlobalLocationFilter.applyToParams(d, "#dataTable-nthchecklist-<?php echo $type?>", {
                            state: "nth-state",
                            region: "nth-region",
                            branch: "nth-aoc_facility_id"
                        });
                    }
				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": <?php echo json_encode($fields)?>,
			"drawCallback": function (oSettings, json) {
				$("#<?php echo $type?>-counter").html("("+<?php echo $type.'_bucket_datatable';?>.page.info().recordsTotal+")");

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
			},
			"initComplete": function(settings, json) {

				jQuery("#dataTable-nthchecklist-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-nthchecklist-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-nthchecklist-<?php echo $type?>_filter input").val();

					clearTimeout(searchDelay);
					searchDelay = setTimeout(function() {
						if (search != null && search.length >=3)
						<?php echo $type.'_bucket_datatable';?>.search(search).draw();
						if (search.length==0 && e.keyCode==8)
						<?php echo $type.'_bucket_datatable';?>.search(search).draw();
					}, dataTableDelay);
				});
			},

		});
	}

	function showNthForm(type, id = 0, button) {
		let _token = $('meta[name="csrf-token"]').attr('content');
		let ids = 0;
		if (id > 0) {
			ids = id;
		} else if(id < 0) {
			var selectedIds = [];
			$('.select-all-' + type + '.td:checked').each(function() {
				selectedIds.push(this.value);
			});
			ids = selectedIds.join(",");
		}
		button.disabled = true;
		$.ajax({
			url: "{{route('dashboard.getnthstepform')}}",
			type: "GET",
			data: {
				type: type,
				_token: _token,
				ids: ids
			},
			success: function(response) {
				button.disabled = false;
				if (response) {
					$("#tempModalCnt").remove();
					var $div = $('<div />').appendTo('body');
					$div.attr('id', 'tempModalCnt').html(response);
					$('#nthsteps_form').modal('show');
					$(".modal:visible").length && $(document.body).addClass("modal-open");

				}
			},
		});
	}
	function moveNthStep(bucket_type, id = 0, button,next_step_key) {
		console.log('next_step_key ===' + next_step_key);
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
            url: "{{ route('dashboard.moventhsteps') }}",
            type: "POST",
            data: {
				type: bucket_type,
				_token: _token,
				ids: ids
			},
            success: function(response) {
                button.disabled = false;
				$('#move_nth_bucket_table_' + bucket_type).html(response.message);
				$('#move_nth_bucket_' + bucket_type).modal('show');
				if (response.success == 1) {
					//response.next_step_counter
					$('#' + next_step_key + '-counter').html(response.next_step_counter);
					$("#dataTable-nthchecklist-" + bucket_type).DataTable().ajax.reload();
				}
				$('.dataTables_length').hide();
            },
        });
	}
</script>
