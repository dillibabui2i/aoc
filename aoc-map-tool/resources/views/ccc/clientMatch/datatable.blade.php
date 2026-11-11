<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$allBuckets =H::getHelper("ClientMatch")->getBuckets();

?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-clientcare-exception-<?php echo $type?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) { 
					$fields[] = ["data"=>$key,"name"=>$key];
				
				if($key=='select') {
							$checkbox = '<td>';
							$checkbox .= '<input class="form-input" id="select-all-checkbox-'.$type.'" type="checkbox" name="select-'.$type.'" value="0" />';
							$checkbox .= '</td>';
							echo $checkbox;
						} else {
					?>
					<td><?php echo $data?></td>
				<?php } } ?>
				<td>Action</td>
			</tr>
		</thead>
	</table>
</div>

<script>

		$(document).ready(function(){
			$('#select-all-checkbox-ccc_client_match').click(function() {
				$('.select-all-ccc_client_match.td').prop('checked', $(this).prop("checked"));
				showHideUdateButtonForClientMatch($(this).is(':checked'));
			});
		});

	function showHideUdateButtonForClientMatch(isChecked) {
		$('#dataTable-clientcare-exception-ccc_client_match_wrapper div.updateall').remove();
		if (isChecked) {
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '<button type="button" onclick="openClientMatchCompleteTemplate(this,0)" class="btn btn-primary">Complete Selected</button>';
			html += '</div>';
			$('#dataTable-clientcare-exception-ccc_client_match_wrapper').prepend(html);
		}
	}

	
	function openClientMatchCompleteTemplate(obj,id) {
		let _token = $('meta[name="csrf-token"]').attr('content');
		let ids = 0;

		if(id!=0) {
			ids = id;
		} else {

		var selectedIds = [];
			$('.select-all-ccc_client_match.td:checked').each(function() {
				selectedIds.push(this.value);
			});
			ids = selectedIds.join(",");
		}
		
		
		$.ajax({
			url: "{{route('ccc.getclientmatchcompletetemplate')}}",
			type: "GET",
			data: {
				_token: _token,
				ids: ids
			},
			success: function(response) {
				
				if (response) {
					$("#tempModalCnt").remove();
					var $div = $('<div />').appendTo('body');
					$div.attr('id', 'tempModalCnt').html(response);
					$('#cccClientMatchCompleteTemplate').modal('show');
					$(".modal:visible").length && $(document.body).addClass("modal-open");

				}
			},
		});
	}

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
				@if(LD::checkFeatureFlag('add-outcomes-to-track-client-match'))
					@if(LD::checkFeatureFlag('remove-check-box-from-client-match-bucket-action-item'))
						return '<a href="javascript:void(0)" onclick="openClientMatchTemplate('+row['id']+', true)"><i class="fa fa-calendar" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick="openClientMatchTemplate('+row['id']+')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
					@else
						return '<a href="javascript:void(0)" onclick="openClientMatchTemplate('+row['id']+', true)"><i class="fa fa-calendar" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick="openClientMatchCompleteTemplate(this,'+row['id']+')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick="openClientMatchTemplate('+row['id']+')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
					@endif
				@else
					return '<a href="javascript:void(0)" onclick="openClientMatchCompleteTemplate(this,'+row['id']+')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a> | <a href="javascript:void(0)" onclick="openClientMatchTemplate('+row['id']+')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
				@endif
			}
		});
		
		var <?php echo $type?> = $("#dataTable-clientcare-exception-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [
				{
				orderable: false,
				className: 'select-checkbox',
				targets:   0
			},
				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },          

			],
			"ajax": {
				"url": "{{route('ccc.getclientmatch')}}?type=<?php echo $type?>",
				data: function(d) {
					d.filters =  $("#cccclientmatchform").serializeArray();

				},
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			"drawCallback": function (oSettings, json) {
				$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");

				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonForClientMatch($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
			},
			createdRow: function( row, data, dataIndex ) {
				if(data.planned_time_diff > 24){
					$(row).addClass("dashboard-older-record");
				}

    		},
			"initComplete": function(settings, json) {
													
				jQuery("#dataTable-clientcare-exception-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;	 
				jQuery("#dataTable-clientcare-exception-<?php echo $type?>_filter input").on("keyup", function(e) {
					
					var search = jQuery("#dataTable-clientcare-exception-<?php echo $type?>_filter input").val();
												
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