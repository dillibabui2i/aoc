<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));

?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-supplyRequests-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
                    if($key == 'created_at'){
                        $fields[] = ["data"=>['_'=>'created_at.display','sort'=>'created_at.timestamp'],"name"=>'created_at.timestamp',"searchable"=>false];
                    }else{
					    $fields[] = ["data"=>$key,"name"=>$key,"searchable"=>false];
                    }
				?>
				<td><?php echo $data?></td>
				<?php } ?>
				<td>Action</td>
			</tr>
		</thead>
	</table>
</div>

<?php
	$fields[] = ["data"=>"Action"];
?>
@if (LD::checkFeatureFlag('supply-requests-add-filters'))
@if($type == 'oc_supply_requests')
<script>
	$(document).ready(function(){
		if($("#reload-<?php echo $type?>").val()==1) {
			let _token = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				url: "{{ route('supplies.reloadCountSupplyRequests') }}?type=<?php echo $type?>",
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
		var <?php echo $type?> = $("#dataTable-supplyRequests-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('supplies.getSupplyRequestsDashboard')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters = $("#{{$type}}_filter_form").serializeArray();
				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": <?php echo json_encode($fields)?>,
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
</script>
@else
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
					d.filters =  $("#cccsupplyrequestsform").serializeArray();
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
@endif
@else
<script>
	$(document).ready(function(){
		if($("#reload-<?php echo $type?>").val()==1) {
			let _token = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				url: "{{ route('supplies.reloadCountSupplyRequests') }}?type=<?php echo $type?>",
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
		var <?php echo $type?> = $("#dataTable-supplyRequests-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('supplies.getSupplyRequestsDashboard')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters = $("#{{$type}}_filter_form").serializeArray();
				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": <?php echo json_encode($fields)?>,
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
</script>
@endif
