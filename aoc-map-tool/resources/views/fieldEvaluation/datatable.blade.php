<?php
use App\Helpers\H;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));

?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-clinician-eval-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) { 
					$fields[] = ["data"=>$key,"name"=>$key];
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
			data: null,
			width: '5%',
			orderable: false,
			render: function(data, type, row) {
				var action = '';
				return action='<a href="javascript:void(0)" title="Edit Evaluation" onclick=\'editFieldEvaluation("' + row['id'] + '")\' ><i class="fa fa-pencil" aria-hidden="true"></i></a>';
			}
       
		});
		
		var <?php echo $type?> = $("#dataTable-clinician-eval-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [
				
				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },          

			],
			"ajax": {
				"url": "{{route('fieldevaluation.unsentdocuments')}}?type=<?php echo $type?>",
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
				if(data.candidate_date_diff==1){
					$(row).addClass("dashboard-older-record");
				}

    		},
			"initComplete": function(settings, json) {
													
				jQuery("#dataTable-clinician-eval-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;	 
				jQuery("#dataTable-clinician-eval-<?php echo $type?>_filter input").on("keyup", function(e) {
					
					var search = jQuery("#dataTable-clinician-eval-<?php echo $type?>_filter input").val();
												
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