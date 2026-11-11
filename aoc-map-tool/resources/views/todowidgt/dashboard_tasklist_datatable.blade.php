<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-task_list-<?php echo $type?>" width="100%" aria-describedby="">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					if($key == 'due_date' || $key == 'created_at'){
						$fields[] = [
                            "data"=>['_'=>$key.'.display','sort'=>$key.'.timestamp'],
                            "name"=>$key.'.timestamp',
                            "searchable"=>false
                        ];
					}else{
						$fields[] = ["data"=>$key,"name"=>$key,"searchable"=>true];
					}
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
			if (val.data === 'notes') {
				columns_<?php echo $type?>.push({
					data: val.data,
					name: val.name,
					visible: typeof val.visible !== 'undefined' ? val.visible : true,
					width: '50%'
				});
			} else {
				columns_<?php echo $type?>.push({
					data: val.data,
					name: val.name,
					visible: typeof val.visible !== 'undefined' ? val.visible : true
				});
			}
        });
		columns_<?php echo $type?>.push({
			data: 'Action',
			name: 'Action',
			orderable: false,
            sClass:'dt-nowrap'
		});
		var <?php echo $type?> = $("#dataTable-task_list-<?php echo $type?>").DataTable({
			"order": [
				[4, "desc"]
			],
			"ajax": {
				"url": "{{route('dashboard.getToDoTaskList')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $('#tasklistform').serializeArray();
				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			"drawCallback": function (oSettings, json) {
				$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
			},
			"initComplete": function(settings, json) {
				jQuery("#dataTable-task_list-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-task_list-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-task_list-<?php echo $type?>_filter input").val();

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
