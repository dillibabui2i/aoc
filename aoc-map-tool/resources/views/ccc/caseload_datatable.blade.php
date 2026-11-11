<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));

?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
    <table class="table table-bordered" id="dataTable-<?php echo $type?>"
        width="100%" aria-describedby="">
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
                <th>Action</th>
            </tr>
        </thead>
    </table>
</div>
<?php
	$fields[] = ["data"=>"Action"];
?>
<script>

$(document).ready(function(){
			$('#select-all-checkbox-case_load').click(function() {
				$('.select-all-case_load.td').prop('checked', $(this).prop("checked"));
				showHideUdateButtonForCaseLoad($(this).is(':checked'));
			});
		});

	function showHideUdateButtonForCaseLoad(isChecked) {
		$('#dataTable-case_load_wrapper div.updateall').remove();
		if (isChecked) {
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
			html += '<button type="button" onclick="getMassTextForms(\'caseload\',\'Client Care Coordinator\')" \
            class="btn btn-primary">Send Message</button>';
			html += '</div>';
			$('#dataTable-case_load_wrapper').prepend(html);
		}
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

        var <?php echo $type?> = $("#dataTable-<?php echo $type?>").DataTable({
            @if(LD::checkFeatureFlag('add-check-box-in-case-load'))
                "order": [
                    <?php if(LD::checkFeatureFlag('caseload-bucket-additional-filters')) { ?> 
                    [0, "desc"]
                    <?php } else { ?>
                        [2, "desc"]
                    <?php } ?>
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
                    "url": "{{route('ccc.getcaseloaddata')}}?type=<?php echo $type?>",
                    data: function(d) {
                        d.filters = $("#ccccaseloadform").serializeArray();
                    },
                },
            @else
                "order": [
                    [1, "desc"]
                ],
                "columnDefs": [
                    { className: "dt-nowrap", "targets": [1] },
                    { className: "dt-nowrap", "targets": [-1] },

                ],
                "ajax": {
                    "url": "{{route('ccc.getcaseloaddata')}}?type=<?php echo $type?>",
                    data: function(d) {
                        d.filters = $("#ccccaseloadform").serializeArray();
                    },
                },
            @endif
            "deferRender": true,
            "processing": true,
            "serverSide": true,
            "columns": columns_<?php echo $type?>,
            "drawCallback": function (oSettings, json) {
                $("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");
                $('.select-all-{{$type}}.td').off().bind('click', function() {
					showHideUdateButtonForCaseLoad($('.select-all-{{$type}}.td:checked').length ? true : false);
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
</script>
