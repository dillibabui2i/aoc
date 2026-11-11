<?php
use App\Helpers\H;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
$ccc = H::getHelper('ConstantHelper')->getClientCareCoordinator()
?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
    <table class="table table-bordered" id="dataTable-<?php echo $type?>" width="100%" aria-describedby="">
        <thead>
            <tr>
                <?php foreach($dataTable as $key => $data) {
                    $fields[] = ["data"=>$key,"name"=>$key];
                    if($key=='select') {
						$isSelectField = true;
						echo '<td><input class="form-input" id="select-all-checkbox-'.$type.'"
                            type="checkbox" name="select-'.$type.'" value="0" /></td>';
					} else {
                ?>
                    <th><?php echo $data?></th>
                <?php } } ?>
                <th>Action</th>
            </tr>
        </thead>
    </table>
</div>

<script>
    function <?php  echo $type?>(){
        var forms = '<?php  echo $form?>';
        var fields_<?php echo $type?> = '<?php echo json_encode($fields)?>';
        fields_<?php echo $type?> = JSON.parse(fields_<?php echo $type?>);
        var columns_<?php echo $type?> = [];
        $.each(fields_<?php echo $type?>, function(key, val) {
            if (val.data == 'select') {
                columns_<?php echo $type?>.push({
                    data: null,
                    orderable: false,
                    render: function(data, type, row) {
                        var text = '<input type="checkbox" name="checkbox-{{$type}}[]" \
                        class="form-input td select-all-{{$type}}" value="' + data.school_setup_id + '" />';
                        return text;

                    }
                });
            } else {
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
            className: "dt-nowrap",
            searchable: false,
            orderable: false,
            render: function(data, type, row) {
                var action = '';
                action += '<a title="Set Outcome" href="javascript:void(0);"\
                    onclick="getOpenSchoolSetUpNextStepForm(\''+row['school_setup_id']+'\')">\
                    <i class="fa fa-calendar"></i></a>';
                action += ' | <a title="View clinician profile" href="javascript:void(0)" \
                        onclick="getClinicianProfile(\''+row['aoc_provider_id']+'\')" >\
                        <i class="fa fa-user-md" aria-hidden="true"></i></a>\
                        | <a title="View client profile" href="javascript:void(0);"\
                        onclick="getClientDetails(\''+row['aoc_patient_id']+'\')">\
                        <i class="fa fa-user"></i></a>';
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
                "url": "{{route('dashboard.getSchoolSetUpsRequest')}}?type=<?php echo $type?>",
                data: function(d) {
                    d.filters = $(""+forms+"").serializeArray();
                },
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
            },

        });
    }
    $(document).ready(function(){
		$('#select-all-checkbox-{{$type}}').click(function() {
			$('.select-all-{{$type}}.td').prop('checked', $(this).prop("checked"));
			showHideUdateButtonFor{{$typeFunction}}($(this).is(':checked'));
		});
	});
    function showHideUdateButtonFor{{$typeFunction}}(isChecked) {
		$('#dataTable-<?php echo $type?>_wrapper div.updateall').remove();
		if (isChecked) {
			var cccuser = @php echo json_encode($ccc); @endphp;
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '<select name="cccuser" id="cccuser">';
			html += '<option value="">Please Select</option>';
			$.each(cccuser, function (key, val) {
				html += '<option value="'+val['value']+'">'+val['text']+'</option>';
			});
			html += '</select>';
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="assignCCC{{$typeFunction}}(this)" \
            class="btn btn-primary">Update</button>';
			html += '</div>';
			$('#dataTable-<?php echo $type?>_wrapper').prepend(html);
		}
	}

	function assignCCC{{$typeFunction}}(button) {
		button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
		$('input[name="checkbox-{{$type}}[]"]:checked').each(function() {
			selectedIds.push(this.value);
		});
		$.ajax({
            url: "{{route('schoolSetup.assignedCcc')}}",
            type: "POST",
            data: {
				_token: _token,
				ids: selectedIds.join(","),
				ccc_id: $('#cccuser').val(),
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
