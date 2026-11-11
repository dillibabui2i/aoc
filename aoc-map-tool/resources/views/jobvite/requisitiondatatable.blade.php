<?php
use App\Helpers\H;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
?>

<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-<?php echo $type?>" width="100%" aria-describedby="">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					$fields[] = ["data"=>$key,"name"=>$key];
				?>
					<th><?php echo $data?></th>
				<?php }  ?>
				<th>Action</th>
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
                width: typeof val.width !== 'undefined' ? val.width : '',
				orderable: typeof val.orderable !== 'undefined' ? val.orderable : true,
            });
        });

		columns_<?php echo $type?>.push({
			data: null,
			width: '5%',
			orderable: false,
			render: function(data, type, row) {
				var action = '';

				<?php if($type=='jobvite_requisition_jobviteclosed') { ?>
						action = '<a href="javascript:void(0)" onclick="completeCloseRequistion('+row["id"]+');" title="Closed Requisition"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
				<?php } else { ?>
						action = '<a href="javascript:void(0)" onclick="completeOpenRequistion(\''+row["Hub"]+'\',\''+row["JobCategory"]+'\');" title="Completed Requisition"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
				<?php } ?>
				return action;
			}

		});

		var <?php echo $type?> = $("#dataTable-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('jobvite.requisitiondata')}}?type=<?php echo $type?>",
				data: function(d) {
                	d.filters = $("#dashboardjobvite").serializeArray();

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
				jQuery("#dataTable-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-<?php echo $type?>_filter input").on("keyup", function(e) {
					var search = jQuery("#dataTable-<?php echo $type?>_filter input").val();
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
