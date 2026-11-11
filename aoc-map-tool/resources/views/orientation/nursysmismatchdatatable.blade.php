<?php
use App\Helpers\H;
$fields =  [];
$columnIndex = array_flip(array_keys($dataTable));
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-nursys-<?php echo $type?>" width="100%" aria-describedby="">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					$fields[] = ["data"=>$key,"name"=>$key];
				?>
					<th><?php echo $data?></th>
				<?php }  ?>
				<th>Action</td>
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
				var action='<a href="javascript:void(0)" title="View clinician" onclick="getClinicianProfile('+row['aoc_provider_id']+')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
				action+='<a href="javascript:void(0)" title="Mark as Updated" onclick="showNursysExceptionForm('+row['id']+')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
				return action;
			}

		});

		var <?php echo $type?> = $("#dataTable-nursys-<?php echo $type?>").DataTable({
			"order": [
				[{{ $columnIndex['FacilityName'] }}, "desc"]
			],
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [{{ $columnIndex['ClinicianID'] }}] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('nursys.nursysmismatchdata')}}?type=<?php echo $type?>",
				data: function(d) {
                	d.filters = $("#orientationForm,#nursyslicensemismatchform").serializeArray();

            	}
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			"drawCallback": function (oSettings, json) {
				$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
			},
			createdRow: function( row, data, dataIndex ) {

    		},
			"initComplete": function(settings, json) {

				jQuery("#dataTable-nursys-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-nursys-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-nursys-<?php echo $type?>_filter input").val();

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
