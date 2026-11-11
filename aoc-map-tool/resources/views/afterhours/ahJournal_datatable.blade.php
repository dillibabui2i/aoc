<?php
use App\Helpers\H;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));

?>
<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
    <table class="table table-bordered" id="dataTable-<?php echo $type?>"
        style="width:100%;" aria-describedby="">
        <thead>
            <tr>
                <?php
                    foreach($dataTable as $key => $data) {
                        if($key == 'added_date'){
                            $fields[] = [
                                "data" => [
                                    '_' => 'added_date.display',
                                    'sort' => 'added_date.timestamp'
                                ],
                                "name" => 'added_date.timestamp',
                                "searchable" => false
                            ];
                        } else {
                            $fields[] = [
                                "data" => $key,
                                "name" => $key
                            ];
                        }
                ?>
                    <th><?php echo $data?></th>
                <?php }  ?>
                <th>Action</th>
            </tr>
        </thead>
    </table>
</div>
<?php
	$fields[] = [
        "data" => "Action",
        "searchable" => false,
        "orderable" => false
    ];
?>
<script>
    function clearNotes(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('afterhours.clearNote')}}",
            type: "GET",
            data: {
                id: id,
                _token: _token
            },
            success: function(response) {
                $("#dataTable-<?php echo $type?>").DataTable().ajax.reload();
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
                width: typeof val.width !== 'undefined' ? val.width : '',
                searchable: (typeof val.searchable !== 'undefined' && !val.searchable) ? false : true,
                orderable: (typeof val.orderable !== 'undefined' && !val.orderable) ? false : true
            });
        });

        var <?php echo $type?> = $("#dataTable-<?php echo $type?>").DataTable({
            "order": [
                [1, "desc"]
            ],
            "columnDefs": [

                { className: "dt-nowrap", "targets": [1] },
                { className: "dt-nowrap", "targets": [-1] },

            ],
            "ajax": {
                "url": "{{route('afterhours.getAhJournalData')}}?type=<?php echo $type?>",
                data: function(d) {
                    d.filters = $("#ahJournalNotesForm").serializeArray();
                },
            },
            "deferRender": true,
            "processing": true,
            "serverSide": true,
            "columns": columns_<?php echo $type?>,
            "drawCallback": function (oSettings, json) {
                $("#<?php echo $type?>-counter").html("("+<?php echo $type?>.page.info().recordsTotal+")");

                jQuery('.clearit').off().on('click', function() {
                    var id = $(this).attr('data-id');
                    modalConfirm(
                        "Do you confirm you have completed any actions needed for this note?",
                        'Yes',
                        'No',
                        function(res, modelDialog) {
                        if (res) {
                            clearNotes(id);
                        }
                        modelDialog.modal('hide');
                    });
                    return false;
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
