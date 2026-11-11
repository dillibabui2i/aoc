<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-expiring-<?php echo $type?>" width="100%" aria-describedby="">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
                    if($key == 'expiration_date_format' || $key == 'recent_date'){
						$fields[] = [
                            "data"=>['_'=>$key.'.display','sort'=>$key.'.timestamp'],
                            "name"=>$key.'.timestamp',
                            "searchable"=>false
                        ];
					}else{
                        $fields[] = ["data"=>$key,"name"=>$key];
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
            var colDef = {
                data: val.data,
                name: val.name,
                visible: typeof val.visible !== 'undefined' ? val.visible : true,
                width: typeof val.width !== 'undefined' ? val.width : ''
            };
            @if (LD::checkFeatureFlag('add-notes-to-oc-renewables'))
            if (val.name === 'latest_note') {
                colDef.render = function(data, type, row) {
                    if (!data) return '';
                    var escaped = $('<div>').text(data).html();
                    return '<span title="' + escaped + '" style="display:inline-block;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escaped + '</span>';
                };
            }
            @endif
            columns_<?php echo $type?>.push(colDef);
        });
		columns_<?php echo $type?>.push({
			data: null,
			width: '5%',
			orderable: false,
			render: function(data, type, row) {
				var action='<a href="javascript:void(0)" title="View clinician profile" onclick="getClinicianProfile('+row['aoc_provider_id']+')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
				action+= '<a href="javascript:void(0)" title="Document task" onclick="showRenewableForm('+row['id']+',\'<?php echo $type?>\');"><i class="fa fa-calendar" aria-hidden="true"></i></a>';
				action+='<a href="javascript:void(0)" title="Complete task" onclick="showCompleteRenewableForm('+row['id']+',\'<?php echo $type?>\');"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
				action+='<a  href="javascript:void(0);" title="Add note" onclick="showRenewableNotesForm('+row['id']+',\'<?php echo $type?>\');"><i class="fa  fa-sticky-note-o"></i></a>';

				return action;
			}

		});

		var <?php echo $type?> = $("#dataTable-expiring-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },

			],
			"ajax": {
				"url": "{{route('renewables.expiringdata')}}?type=<?php echo $type?>",
				data: function(d) {
					<?php if($type=='cccfifteenzeroexpiring') { ?>
						d.filters = $("#cccrenewableform").serializeArray();
					<?php } else {?>
                	d.filters = $("#orientationForm,#ocrenewableform").serializeArray();
					<?php } ?>
            	}
			},
			"deferRender": true,
			"processing": true,
			"serverSide": true,
			"columns": columns_<?php echo $type?>,
			"drawCallback": function (oSettings, json) {
				<?php if($type=='thirtyzeroexpiring') { ?>
					$("#<?php echo $type?>-counter").html(""+oSettings.json.expcount+"");
				<?php } elseif($type=='cccfifteenzeroexpiring'){ ?>
					<?php if(LD::checkFeatureFlag('change-ccc-renewable-bucket-name')) { ?>
						$("#<?php echo $type?>-counter").html(""+oSettings.json.expcount+"");
					<?php }else{?>
						$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
					<?php }?>
				<?php } else {?>
					$("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
				<?php } ?>
				$('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonFor{{$typeFunction}}($('.select-all-{{$type}}.td:checked').length ? true : false);
				});
			},
			createdRow: function( row, data, dataIndex ) {
				<?php if($type=='thirtyzeroexpiring') { ?>
				if(data.days_till_expiration < 0){
					$(row).addClass("dashboard-older-record-cl-ra");
				}
				<?php } ?>
                @if (LD::checkFeatureFlag('Add-background-color-does-not-meet-criteria'))
					if(data.last_follow_up != null){
						let today = new Date();
    					let lastFollowUpDate = new Date(data.last_follow_up);
						if(data.outcome === 'Uploaded to KanTime'  && lastFollowUpDate < today ){
							$(row).addClass("dashboard-renewable-doesnt-match-outcome");
						}
					}
                @endif

    		},
			"initComplete": function(settings, json) {

				jQuery("#dataTable-expiring-<?php echo $type?>_filter input").off("keyup.DT input.DT");
				var searchDelay = null;
				jQuery("#dataTable-expiring-<?php echo $type?>_filter input").on("keyup", function(e) {

					var search = jQuery("#dataTable-expiring-<?php echo $type?>_filter input").val();

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
