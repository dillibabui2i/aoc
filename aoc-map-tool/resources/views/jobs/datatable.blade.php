<?php
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
if ($type == 'nth_expiring_jobs') {
	$url = route('dashboard.getNTHExpiringJobslist');
	$reloadUrl  = route('reloadCountNTHExpiringJobs');
}
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-nthchecklist-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php 
					foreach($field as $key => $data) { 
						$fields[] = ["data" => $key, "name" => $key];
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

<style>
.select-checkbox.sorting_disabled {
	background-color: #215eac;
	color: #fff;
}
.select-checkbox {
	max-width:0px !important;
}
</style>	
<script>
	$(document).ready(function() {
		if($("#reload-<?php echo $type?>").val()==1) {
			let _token = $('meta[name="csrf-token"]').attr('content');
			$.ajax({
				url: "{{$reloadUrl}}",
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
		var <?php echo $type?> = $("#dataTable-nthchecklist-<?php echo $type?>").DataTable({
			"order": [				
				[0, "desc"]
			],
			"ajax": {
				"url": "{{$url}}",
				data: function(d) {
					//d.filters = $("#datatableFilterSearchForm").serializeArray();
				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": <?php echo json_encode($fields)?>,
			"drawCallback": function (oSettings, json) {
				$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
			},
			"initComplete": function(settings, json) {
													
				jQuery("#dataTable-nthchecklist-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;	 
				jQuery("#dataTable-nthchecklist-<?php echo $type?>_filter input").on("keyup", function(e) {
					
					var search = jQuery("#dataTable-nthchecklist-<?php echo $type?>_filter input").val();
												
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
