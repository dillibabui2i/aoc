<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
?>

<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered w-100"
    id="dataTable-supplyRequests-<?php echo $type?>"
    aria-describedby="">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
                    if($key == 'created_at'){
                        $fields[] = [
                                "data"=>[
                                    '_'=>'created_at.display',
                                    'sort'=>'created_at.timestamp'
                                ],
                                "name"=>'created_at.timestamp',
                                "searchable"=>false
                            ];
                    }else{
					    $fields[] = ["data"=>$key,"name"=>$key,"searchable"=>false];
                    }
				?>
				<th><?php echo $data?></td>
				<?php } ?>
				<th class="dt-nowrap">Action</th>
			</tr>
		</thead>
	</table>
</div>

<?php
	$fields[] = ["data"=>"Action"];
?>

<script>
	function <?php  echo $type?>(){
		var fields_<?php echo $type?> = '<?php echo json_encode($fields)?>';
		fields_<?php echo $type?> = JSON.parse(fields_<?php echo $type?>);
		var columns_<?php echo $type?> = [];
		$.each(fields_<?php echo $type?>, function(key, val) {
			if(val.data != 'Action') {
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
                    sClass: 'dt-nowrap',
					visible: typeof val.visible !== 'undefined' ? val.visible : true,
					width: typeof val.width !== 'undefined' ? val.width : '',
					orderable: false,
				});
			}
        });
		var <?php echo $type?> = $("#dataTable-supplyRequests-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"ajax": {
				"url": "{{route('supplies.getSupplyRequestsDashboard')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $("#supplyrequestsform").serializeArray();
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

				jQuery("#dataTable-supplyRequests-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-supplyRequests-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-supplyRequests-<?php echo $type?>_filter input").val();

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
