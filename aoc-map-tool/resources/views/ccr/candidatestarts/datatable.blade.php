<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
//$allBuckets =H::getHelper("ClientMatch")->getBuckets();
$ccm = H::getHelper('ConstantHelper')->getClinicalCareManager();
$isSelectField = false;
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-ccm-candidate-starts-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					$fields[] = ["data"=>$key,"name"=>$key];
						if(LD::checkFeatureFlag("candidate_start_reassign_calls")) {
								if($key=='select') {
									$isSelectField = true;
									echo '<td><input class="form-input" id="select-all-checkbox-'.$type.'" type="checkbox" name="select-'.$type.'" value="0" /></td>';
								} else { ?>
									<td><?php echo $data?></td>
							<?php }
						} else { ?>
							<td><?php echo $data?></td>
						<?php } ?>
				<?php }  ?>
				<td>Action</td>
			</tr>
		</thead>
	</table>
</div>

<script>
	function showHideUdateButtonFor{{$typeFunction}}(isChecked) {
		$('#dataTable-ccm-candidate-starts-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {

			var ccmuser = @php echo json_encode($ccm); @endphp;
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '<select name="ccmuser" id="ccruser{{$typeFunction}}">';
			html += '<option value="">Please Select</option>';
			$.each(ccmuser, function (key, val) {
				html += '<option value="'+val['value']+'">'+val['text']+'</option>';
			});
			html += '</select>';
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="assignCCM{{$typeFunction}}(this)" class="btn btn-primary">Update</button>';
			html += '</div>';
			$('#dataTable-ccm-candidate-starts-{{$type}}_wrapper').prepend(html);
		}
	}

	function assignCCM{{$typeFunction}}(button) {
		button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
		$('input[name="checkbox-{{$type}}[]"]:checked').each(function() {
			selectedIds.push(this.value);
		});

		$.ajax({
            url: "{{route('assignedCcm')}}",
            type: "POST",
            data: {
				_token: _token,
				ids: selectedIds.join(","),
				ccr_id: $('#ccruser{{$typeFunction}}').val(),
			},
            success: function(response) {
                button.disabled = false;
				if (response.success == 1) {
					$("#dataTable-ccm-candidate-starts-<?php echo $type?>").DataTable().ajax.reload();
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
		columns_<?php echo $type?>.push({
			data: null,
			width: '5%',
			orderable: false,
			render: function(data, type, row) {
				return '<a href="javascript:void(0)" title = "Document Task" onclick="openCandidateStartOutcome('+row['id']+',\'<?php echo $type?>\')"><i class="fa fa-calendar" aria-hidden="true"></i></a>|&nbsp;<a href="javascript:void(0)" title = "View Tracker Details" onclick="openRecruiterDetails('+row['tracker_id']+')"><i class="fa fa-eye" aria-hidden="true"></i></a>|&nbsp;<a href="javascript:void(0)" title = "View Client Profile" onclick="getClientDetails('+row['aoc_patient_id']+')"><i class="fa fa-user" aria-hidden="true"></i></a>';
			}
		});

		var <?php echo $type?> = $("#dataTable-ccm-candidate-starts-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('ccm.getcandidatestarts')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $("#ccmcandidatestarts").serializeArray();

                    if (window.GlobalLocationFilter) {
                        window.GlobalLocationFilter.applyToParams(d, "#dataTable-ccm-candidate-starts-<?php echo $type?>", {
                            state: "ccmcandidatestate",
                            region: "ccmcandidateregion",
                            branch: "ccmcandidatebranch"
                        });
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
				$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
				@if(LD::checkFeatureFlag('add-progress-bar-in-ccm-follow-up'))
				<?php if($type=='first_shift_follow_up' || $type=='seven_day_follow_up' || $type == 'thirty_day_follow_up') { ?>
						let _token = $('meta[name="csrf-token"]').attr('content');
						$.ajax({
							url: "{{route('ccc.progressbar')}}",
							type: 'POST',
							dateType:"html",
							data: {
								bucketType:"<?php echo $type?>",
								_token: _token,
								<?php if($type=='first_shift_follow_up') { ?>
										filters:  $("#ccmcandidatestarts").serializeArray()
								<?php } elseif($type=='seven_day_follow_up') { ?>
										filters:  $("#ccmcandidatestarts").serializeArray()
								<?php } elseif($type=='thirty_day_follow_up') { ?>
										filters:  $("#ccmcandidatestarts").serializeArray()
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
				@endif
				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
			},
			createdRow: function( row, data, dataIndex ) {
				if(data.candidate_date_diff==1){
					$(row).addClass("dashboard-older-record");
				}

    		},
			"initComplete": function(settings, json) {

				jQuery("#dataTable-ccm-candidate-starts-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-ccm-candidate-starts-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-ccm-candidate-starts-<?php echo $type?>_filter input").val();

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
