<?php
use App\Helpers\H;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$isSelectField = false;
$advSpecialist = H::getAdvocacySpecialist();
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-advocacy-<?php echo $type?>" aria-describedby="" width="100%">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					if($key == 'request_last_followup_date'){
						$fields[] = [
							"data"=>['_'=>$key.'.display','sort'=>$key.'.timestamp'],
							"name"=>$key.'.timestamp',
							"searchable"=>false
						];
					}else{
						$fields[] = ["data"=>$key,"name"=>$key];
					}
					if($key=='select') {
						$isSelectField = true;
						echo '<td><input class="form-input" id="select-all-checkbox-'.$type.'" type="checkbox" name="select-'.$type.'" value="0" /></td>';
					} else {
					?>
					<th><?php echo $data?></th>
				<?php } } ?>
				<td nowrap="nowrap">Action</td>
			</tr>
		</thead>
	</table>
</div>

<script>
	function assignAdvocacySpecialist{{$typeFunction}}(button) {
		button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
		$('input[name="checkbox-{{$type}}[]"]:checked').each(function() {
			selectedIds.push(this.value);
		});
		$.ajax({
            url: "{{route('advocacy.setadvocacyspecialist')}}",
            type: "POST",
            data: {
				_token: _token,
				ids: selectedIds.join(","),
				advocacyspecialist: $('#advocacyspecialist').val(),
			},
            success: function(response) {
                button.disabled = false;
				if (response.success == 1) {
					$("#dataTable-advocacy-<?php echo $type?>").DataTable().ajax.reload();
				}
				$('input[name="checkbox-{{$type}}[]"]').prop('checked', false);
				$('#select-all-checkbox-{{$type}}').prop('checked', false);
				$('.dataTables_length').hide();
            },
        });
	}
	function showHideUdateButtonFor{{$typeFunction}}(isChecked) {
		$('#dataTable-advocacy-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			@if($typeFunction == 'Advocacystatuschangerequest')
			var adspecialist = @php echo json_encode($advSpecialist); @endphp;
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '<select name="advocacyspecialist" id="advocacyspecialist">';
			html += '<option value="">Please Select</option>';
			$.each(adspecialist, function (key, val) {
				html += '<option value="'+val['value']+'">'+val['text']+'</option>';
			});
			html += '</select>';
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="assignAdvocacySpecialist{{$typeFunction}}(this)" class="btn btn-primary">Update</button>';
			html += '</div>';
			@endif
			$('#dataTable-advocacy-{{$type}}_wrapper').prepend(html);
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
                width: typeof val.width !== 'undefined' ? val.width : ''
            });
        });
		columns_<?php echo $type?>.push({
			data: null,
			width: '5%',
			orderable: false,
			className:"datatable-no-wrap",
			render: function(data, type, row) {

				var action = '<a href="javascript:void(0)" title="View clinician profile" onclick="getClinicianProfile('+row['id']+')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
				 action+= '<a href="javascript:void(0)" title="Update request status" onclick="advocacyStatusChangeRequestNextStepForm('+row['id']+');"><i class="fa fa-calendar" aria-hidden="true"></i></a>';
				 action+='<a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes('+row['id']+',\'Clinician\',\'Advocacy Notes Added\')"><i class="fa  fa-sticky-note-o"></i></a>';
				return action;
			}

		});

		var <?php echo $type?> = $("#dataTable-advocacy-<?php echo $type?>").DataTable({
			"order": [
				[10, "desc"]
			],
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('advocacy.statuschangerequest')}}?type=<?php echo $type?>",
				data: function(d) {
                	d.filters = $('#advocacystatuschange').serializeArray();
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
			},
			createdRow: function( row, data, dataIndex ) {
				if(data.candidate_date_diff==1){
					$(row).addClass("dashboard-older-record");
				}

    		},
			"initComplete": function(settings, json) {

				jQuery("#dataTable-advocacy-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-advocacy-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-advocacy-<?php echo $type?>_filter input").val();

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
