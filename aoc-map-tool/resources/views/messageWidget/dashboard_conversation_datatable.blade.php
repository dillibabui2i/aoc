<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-messsage_conversation-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					$fields[] = ["data"=>$key,"name"=>$key,"searchable"=>true];
				?>
					<td><?php echo $data?></td>
				<?php }  ?>
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
			data: 'Action',
			name: 'Action',
			orderable: false,
            sClass:'dt-nowrap'
		});
		var <?php echo $type?> = $("#dataTable-messsage_conversation-<?php echo $type?>").DataTable({
			"order": [
				[0, "desc"]
			],
			"ajax": {
				"url": "{{route('dashboard.getMessageCenterList')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $('#messagecenterconversationsform').serializeArray();
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
				jQuery("#dataTable-messsage_conversation-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-messsage_conversation-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-messsage_conversation-<?php echo $type?>_filter input").val();

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
