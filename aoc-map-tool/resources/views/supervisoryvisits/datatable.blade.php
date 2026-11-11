<?php
use App\Helpers\H;
use App\Helpers\LD;
$isSelectField = false;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$ccm = H::getHelper('ConstantHelper')->getClinicalCareManager();
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-<?php echo $type?>" width="100%" aria-describedby="">
		<thead>
			<tr>
				<?php foreach($dataTable as $key => $data) {
					if ($key == 'select') {
						$fields[] = ["data" => $key, "name" => $key, "orderable" => false];
					} else {
						$fields[] = ["data"=>$key, "name"=>$key];
					}
					if($key=='select') {
						$isSelectField = true;
						echo '<td><input class="form-input" id="select-all-checkbox-'.$type.'"
						type="checkbox" name="select-'.$type.'" value="0" /></td>';
					}else{
						?>
							<th><?php echo $data?></th>
						<?php
					}
				}
				?>
				<th>Action</td>
			</tr>
		</thead>
	</table>
</div>
@if(LD::checkFeatureFlag('add-code-change-to-make-supervisoryvisits-fast'))
<script>
	  function <?php  echo $type?>(){
        var fields_<?php echo $type?> = '<?php echo json_encode($fields)?>';
        fields_<?php echo $type?> = JSON.parse(fields_<?php echo $type?>);
        var columns_<?php echo $type?> = [];
        $.each(fields_<?php echo $type?>, function(key, val) {
            if(val.data == 'select'){
                columns_<?php echo $type?>.push({
                    data: null,
                    width: '2%',
                    orderable: false,
                    render: function(data, type, row) {
                        var select = '<input class="form-input td select-all-{{$type}}" ' +
						'type="checkbox" name="checkbox-{{$type}}[]" ' +
						'value="' + row['id'] + '" />';
                        return select;
                    }
                });
            }else{
                columns_<?php echo $type?>.push({
                    data: val.data,
                    name: val.name,
                    visible: typeof val.visible !== 'undefined' ? val.visible : true,
                    width: typeof val.width !== 'undefined' ? val.width : ''
                });
            }
        });
        columns_<?php echo $type?>.push({
            data: null,
            width: '10%',
            className: "action",
            render: function(data, type, row) {
				let action = '';

				if (row['completed_at'] && row['completed_by']) {
					action += '<a href="javascript:void(0);" onclick="openSupervisoryVisitForm('+row['id']+','+row['aoc_provider_id']+','+row['aoc_patient_id']+',\'view\');">\
								<i class="fa fa-eye" aria-hidden="true"></i></a>';
				}

				action += '<a href="javascript:void(0);" onclick="openSupervisoryVisitForm('+row['id']+','+row['aoc_provider_id']+','+row['aoc_patient_id']+',\'edit\');">\
							<i class="fa fa-check-square-o" aria-hidden="true" title="Complete Task"></i></a>';

				action += '<a href="javascript:void(0);" onclick="getClinicianProfile('+row['aoc_provider_id']+');">\
							<i class="fa fa-user-md" aria-hidden="true" title="View Clinician Profile"></i></a>';

				action += '<a href="javascript:void(0);" onclick="getClientDetails('+row['aoc_patient_id']+');">\
							<i class="fa fa-user" title="View Client Profile"></i></a>';

				@if (LD::checkFeatureFlag('add-reason-field-in-supervisory-visit-delete-pop-up'))
					@if (H::canPermissions('supervisoryVisits.delete'))
						action += '<a href="javascript:void(0);" onclick="supervisoryvisitDeletePopUp('+row['id']+', \'bucket\');">\
									<i class="fa fa-trash-o" title="Delete"></i></a>';
					@endif
				@else
					@if (H::canPermissions('supervisoryVisits.delete'))
						action += '<a href="javascript:void(0);" onclick="supervisoryvisitDeletePopUp('+row['id']+', \'bucket\');">\
									<i class="fa fa-trash-o" title="Delete"></i></a>';
					@endif
				@endif
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
                "url": "{{route('supervisoryVisit.bucket')}}?type=<?php echo $type?>",
				data: function(d) {
                	d.filters = $("#super_visory").serializeArray();
                    if (window.GlobalLocationFilter) {
                        window.GlobalLocationFilter.applyToParams(d, "#dataTable-<?php echo $type?>", {
                            state: "svstate",
                            region: "svregion",
                            branch: "svbranch"
                        });
                    }
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
                jQuery("#dataTable-<?php echo $type?>_filter").prepend(
                    '<a data-type="{{$type}}" title="Export" href="javascript:void(0)" \
                    class="supervisoryVisitExport"><i class="fa fa-download"></i></a>'
                    );
            },
			"rowCallback": function( row, data ) {
			if (new Date(data.visit_date_due).getTime() < new Date().getTime()) {
					$(row).addClass('dashboard-older-record-cl-ra');
				}
			}
        });
    }
</script>
@else
<?php
	$fields[] = ["data"=>"Action","orderable"=>false];
?>
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
		var <?php echo $type?> = $("#dataTable-<?php echo $type?>").DataTable({
			"order": [
				[2, "desc"]
			],
			"columnDefs": [

				{ className: "dt-nowrap", "targets": [1] },
				{ className: "dt-nowrap", "targets": [-1] },
			],
			"ajax": {
				"url": "{{route('supervisoryVisit.bucket')}}?type=<?php echo $type?>",
				data: function(d) {
                	d.filters = $("#super_visory").serializeArray();

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
@endif
<script>
	$(document).ready(function(){
		$('#select-all-checkbox-{{$type}}').click(function() {
			$('.select-all-{{$type}}.td').prop('checked', $(this).prop("checked"));
			showHideUdateButtonFor{{$typeFunction}}($(this).is(':checked'));
		});
	});
	function showHideUdateButtonFor{{$typeFunction}}(isChecked) {
		$('#dataTable-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			@if($typeFunction == 'Supervisoryvisitsbucket')
			var ccmuser = @php echo json_encode($ccm); @endphp;
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '<select name="ccruser" id="ccruser">';
			html += '<option value="">Please Select</option>';
			$.each(ccmuser, function (key, val) {
				html += '<option value="'+val['value']+'">'+val['text']+'</option>';
			});
			html += '</select>';
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="assigSupernCCR{{$typeFunction}}(this)" class="btn btn-primary">Update</button>';
			html += '</div>';
			@endif
			$('#dataTable-{{$type}}_wrapper').prepend(html);
		}
	}

	$(document).on('click', '.supervisoryVisitExport', function(e) {
		e.preventDefault();
		var data = {};
		data['filters'] = $("#super_visory").serializeArray();
		data['type'] = $(this).data('type');
		var url = "{{route('exportSupervisoryVisitsData')}}" + '?' + $.param(data);
		window.location.href = url;
	});

	function assigSupernCCR{{$typeFunction}}(button) {
		button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
		$('input[name="checkbox-{{$type}}[]"]:checked').each(function() {
			selectedIds.push(this.value);
		});
		$.ajax({
            url: "{{route('supervisoryVisit.assignedSuperCcm')}}",
            type: "POST",
            data: {
				_token: _token,
				ids: selectedIds.join(","),
				ccr_id: $('#<?php echo $type?> #ccruser').val(),
			},
            success: function(response) {
                button.disabled = false;
				if (response.success == 1) {
					$("#dataTable-<?php echo $type?>").DataTable().ajax.reload();
				}
				$('input[name="checkbox-{{$type}}[]"]').prop('checked', false);
				$('#select-all-checkbox-{{$type}}').prop('checked', false);
				$('.dataTables_length').hide();
            },
        });
	}
</script>
