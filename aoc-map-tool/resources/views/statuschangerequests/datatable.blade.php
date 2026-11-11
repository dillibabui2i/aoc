<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$isSelectField = false;
$advSpecialist = H::getStatusChangeRequestUsersByRole();
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-changeRequest-<?php echo $type?>" width="100%" aria-describedby="">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					if($key == 'request_date'){
						$fields[] = [
							"data"=>['_'=>$key.'.display','sort'=>$key.'.timestamp'],
							"name"=>$key.'.timestamp',
							"searchable"=>false
						];
					}else{
						if(LD::checkFeatureFlag('status-change-request-request-oc-checkbox')) {
							if($key=='select') {
								$fields[] = ["data"=>$key,"name"=>$key,"orderable"=>false];
							} else {
								$fields[] = ["data"=>$key,"name"=>$key];
							}
						} else {
							$fields[] = ["data"=>$key,"name"=>$key];
						}
						
					}
					if(LD::checkFeatureFlag('status-change-request-request-oc-checkbox')) {
					if($key=='select') {
						$isSelectField = true;
						echo '<td><input class="form-input" id="select-all-checkbox-'.$type.'" type="checkbox" name="select-'.$type.'" value="0" /></td>';
					} else {
					?>
					<th><?php echo $data?></th>
				<?php } } else { ?>
					<th><?php echo $data?></th>
				<?php }}  ?>
				<th>Action</td>
			</tr>
		</thead>
	</table>
</div>
<?php
	$fields[] = ["data"=>"Action","orderable"=>false];
?>
<script>
	function assignRequestOC{{$typeFunction}}(button) {
		button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
		$('input[name="checkbox-{{$type}}[]"]:checked').each(function() {
			selectedIds.push(this.value);
		});
		$.ajax({
            url: "{{route('statuschangerequest.assignrequestoc')}}",
            type: "POST",
            data: {
				_token: _token,
				ids: selectedIds.join(","),
				requestoc: $('#changerequestrequestoc').val(),
			},
            success: function(response) {
                button.disabled = false;
				if (response.success == 1) {
					$("#dataTable-changeRequest-<?php echo $type?>").DataTable().ajax.reload();
				}
				$('input[name="checkbox-{{$type}}[]"]').prop('checked', false);
				$('#select-all-checkbox-{{$type}}').prop('checked', false);
				$('.dataTables_length').hide();
            },
        });
	}
	function showHideUdateButtonFor{{$typeFunction}}(isChecked) {
		$('#dataTable-changeRequest-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			
			var adspecialist = @php echo json_encode($advSpecialist); @endphp;
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '<select name="changerequestrequestoc" id="changerequestrequestoc">';
			html += '<option value="">Please Select</option>';
			$.each(adspecialist, function (key, val) {
				html += '<option value="'+val['value']+'">'+val['text']+'</option>';
			});
			html += '</select>';
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="assignRequestOC{{$typeFunction}}(this)" class="btn btn-primary">Update</button>';
			html += '</div>';
			
			$('#dataTable-changeRequest-{{$type}}_wrapper').prepend(html);
		}
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
                width: typeof val.width !== 'undefined' ? val.width : '',
				orderable: typeof val.orderable !== 'undefined' ? val.orderable : true,
            });
        });
		var <?php echo $type?> = $("#dataTable-changeRequest-<?php echo $type?>").DataTable({
			<?php if(LD::checkFeatureFlag('status-change-request-request-oc-checkbox')) { ?>
				"order": [
					[10, "desc"]
				],
			<?php }else{ ?>
				"order": [
					[9, "desc"]
				],
			<?php } ?>
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },
				
			],
			"ajax": {
				"url": "{{route('statuschangerequests.getSupplyRequestsDashboard')}}?type=<?php echo $type?>",
				data: function(d) {
                	d.filters = $("#orientationForm,#oc_status_change_requests_form").serializeArray();

            	}
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			@if($isSelectField)
			"columnDefs": [
				{
					orderable: false,
					targets:   0,
					className:"select-checkbox"
				}
			],
			@endif
			"drawCallback": function (oSettings, json) {
				$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
			},
			createdRow: function( row, data, dataIndex ) {
    		},
			"initComplete": function(settings, json) {
				jQuery("#dataTable-changeRequest-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-changeRequest-<?php echo $type?>_filter input").on("keyup", function(e) {
					var search = jQuery("#dataTable-changeRequest-<?php echo $type?>_filter input").val();
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
