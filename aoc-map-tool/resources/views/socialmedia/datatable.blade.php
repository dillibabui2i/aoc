<?php
use App\Helpers\H;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-socialmedia-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					$fields[] = ["data"=>$key,"name"=>$key];
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

<script>
	$(document).ready(function(){

		if($("#reload-<?php echo $type?>").val()==1) {
			let _token = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				url: "{{ route('dashboard.reloadCountSocialMedia') }}?type=<?php echo $type?>",
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
		var <?php echo $type?> = $("#dataTable-socialmedia-<?php echo $type?>").DataTable({
			"order": @json($defaultOrder),
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('dashboard.getSocialMediaDashboard')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters = $("#datatableFilterSearchForm").serializeArray();
				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": <?php echo json_encode($fields)?>,
@if ($isRequestsBucket)
			createdRow: function( row, data, dataIndex ) {
				if (data.is_overdue) {
					$(row).addClass("dashboard-older-record");
				}
			},
@endif
			"drawCallback": function (oSettings, json) {
				$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
			},
			"initComplete": function(settings, json) {

				jQuery("#dataTable-socialmedia-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-socialmedia-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-socialmedia-<?php echo $type?>_filter input").val();

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
