<?php
use App\Helpers\LD;
use App\Helpers\H;
$fields = isset($dataTable['fields'])?$dataTable['fields']:[];
$fieldsWidth = isset($dataTable['fieldswidth'])?$dataTable['fieldswidth']:[];

$jsonFields = json_encode($fields);
$jsonFieldsWidth = json_encode($fieldsWidth);
?>

@extends('layouts.myaccount')


@section('content')
@yield("datatabletop")

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <div id="new_dataTable_wrapper"></div>
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                    <thead>
                        <tr><?php if (LD::checkFeatureFlag('show-mass-text-option')  && H::canPermissions('save.massmessage')) { ?>
                            <?php foreach($fields as $key => $value){
                                if($key == 'select'){
                                    $checkbox = '<td><input class="form-input" id="select-all-checkbox-candidate" type="checkbox" name="select-candidate" value="0" /></td>';
                                    echo $checkbox;
                                }else{
                                    ?>
                            <th><?php echo $value;?></th>
                            <?php }}?>
                            <?php } else {?>
                                <?php foreach($fields as $key => $value){ ?>
                                <th><?php echo $value;?></th>
                                <?php }}?>
                        </tr>
                    </thead>
                    <tbody>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>



<script src="{{ mix('js/dataTables.responsive.min.js') }}"></script>
<script>
var jsonFields = jQuery.parseJSON('<?php echo $jsonFields?>');
var jsonFieldsWidth = jQuery.parseJSON('<?php echo $jsonFieldsWidth?>');
let _token = $('meta[name="csrf-token"]').attr('content');
var table;
$(document).ready(function($) {
    function getFieldJSON() {
        jsonObj = [];

        $(jsonFields).each(function(ind, jsonObject) {
            $.each(jsonObject, function(key, val) {
                item = {}
                if (key == 'select') {
                    <?php if (LD::checkFeatureFlag('show-mass-text-option') && H::canPermissions('save.massmessage')) { ?>
                    jsonObj.push({
                        data: null,
                        orderable: false,
                        render: function(data, type, row) {
                            var text = '<input type="checkbox" name="checkbox-candidate[]" class="form-input td select-all-candidate" value="' + data.id + '" />';
                            return text;

                        }
                    });
                    <?php } ?>
                } else if (key == 'action') {
                    jsonObj.push({
                        data: null,
                        width: (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null,
                        sClass: 'dt-nowrap',
                        render: function(data, type, row) {
                            var text =
                                '<a href="javascript:void(0);" onclick="openRecruiterDetails(' +
                                data.id + ')"><i class="fa fa-eye"></i></a>';

                            text +=
                                ' | <a href="javascript:void(0);" onclick="openNotes(' +
                                data.id +
                                ',\'tracker\',\'Recruiter Tracker Notes Added\')"><i class="fa  fa-sticky-note-o"></i></a>';
                            if (typeof data.linked_IPEA != 'undefined' && data
                                .linked_IPEA != '' && data
                                .linked_IPEA != null) {
                                text +=
                                    ' | <a href="javascript:void(0);" onclick="updateNurseIpeaInfo(' +
                                    data.linked_IPEA +
                                    ')"><i class="fa  fa-vcard"></i></a>';
                            }

							@canPermission('dashboard.removeactivity')
                            text +=
                                ' | <a href="javascript:void(0);" onclick="removeActivity(' + data.id + ')"><i class="fa fa-trash-o"></i></a>'
							@endcanPermission

                            return text;

                        }
                    });




                } else if (key == 'caction') {
                    const requestType = @json(request()->get('type'));

                    jsonObj.push({
                        data: null,
                        width: (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null,
                        sClass: 'dt-nowrap',
                        render: function(data, type, row) {
                            var text = '<a href="javascript:void(0);" onclick="showScheduleForm(\'\', \'candidates##' + data.id + '\', \'\')"><i class="fa fa-plus"></i></a> | ';
                            text += '<a href="javascript:void(0)" onclick="window.location = \'{{route('goToNeedToHireMap').'?candidateId='}}' + data.id + '\'"><i class="fa fa-map-marker"></i></a> | ';
                            text += '<a href="javascript:void(0)" onclick="openCandidateDetails(' + data.id + ',false, \'' + requestType + '\')"  ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                            var appWorkFlowStates = <?php echo json_encode(array_merge(config('constant')['jobViteOutcomes'], config('constant')['jobviteTherapyOutcomes'])); ?>;
                            <?php if(LD::checkFeatureFlag('jobvite-add-action-item-on-show-all-page')){ ?>
                                    text +=
                                        ' | <a href="javascript:void(0);" onclick="showCandidateNextStepForm(' + data.id + ', \'' + requestType + '\')"><i class="fa fa-calendar"></i></a>';
                            <?php } else { ?>
                                if (appWorkFlowStates[data.appWorkflowState] != undefined) {
                                    text +=
                                        ' | <a href="javascript:void(0);" onclick="showCandidateNextStepForm(' + data.id + ', \'' + requestType + '\')"><i class="fa fa-calendar"></i></a>';
                                    }
                            <?php } ?>
                            return text;

                        }
                    });




                }  else if (key == 'waction') {
                    jsonObj.push({
                        data: null,
                        width: (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null,
                        sClass: 'dt-nowrap',
                        render: function(data, type, row) {
                            var text = "";
                            @canPermission('managewhiteboard.edit')
                                text += "<a href='javascript:void(0)' onclick=\"window.location = '{{url('managewhiteboard/edit')}}/" + data.id + "'\" class='edit'><i class='fa fa-edit' title='Edit'></i></a>";
                            @endcanPermission
                            @canPermission('managewhiteboard.edit')
                                text += "<a href='javascript:void(0)' class='disable-it' data-href='{{url('managewhiteboard/delete')}}/" + data.id + "'><i class='fa fa-trash-o' title='Remove'></i></a>";
                            @endcanPermission
                            return text;
                        }
                    });
                } else if (key == 'mapaction') {
                    jsonObj.push({
                        data: null,
                        sClass: 'dt-nowrap',
                        render: function(data, type, row) {
                            var text = "";
                            text += "<a href='javascript:void(0)' onclick=\"window.location = '{{route('map.ignoreform')}}?id=" + data.id + "'\" class='edit'><i class='fa fa-edit' title='Edit'></i></a>";
                            text += "<a href='javascript:void(0)' class='disable-it' onclick=\"window.location='{{route('map.deleteIgnore')}}?id=" + data.id + "'\"><i class='fa fa-trash-o' title='Disable'></i></a>";
                            return text;
                        }
                    });
                } else if (key == 'id') {
                    item["data"] = key;
                    item["name"] = val;
                    item["visible"] = false;
                    item['width'] = (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null;
                    jsonObj.push(item);
                } else if (key == 'linked_IPEA') {
                    item["data"] = key;
                    item["name"] = val;
                    item["visible"] = false;
                    item["width"] = (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null;
                    jsonObj.push(item);
                } else {



                    if (key == 'login_action') {
                        jsonObj.push({
                            width: (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null,
                            data: null,
                            render: function(data, type, row) {
                                return '<a href="{{route("blacklist.edit")}}?id=' +
                                    data.id +
                                    '" ><i class="fa fa-pencil"></i></a> &nbsp; <a href="{{route("blacklist.delete")}}?id=' +
                                    data.id +
                                    '"><i class="fa fa-trash-o"></i></a>  '
                            }
                        });
                    } else if (key == 'file_path') {
                        jsonObj.push({
                            width: (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null,
                            data: null,
                            render: function(data, type, row) {

                                return '<a href="{{route("manageipeaattachment.download")}}?id=' +
                                    data.id +
                                    '">' + data.file_path + '</a>'
                            }
                        });
                    } else if (key == 'ipea_attachment_action') {
                        jsonObj.push({
                            width: (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null,
                            data: null,
                            render: function(data, type, row) {
                                return '<a href="{{route("manageipeaattachment.edit")}}?id=' +
                                    data.id +
                                    '" ><i class="fa fa-pencil"></i></a> &nbsp; <a href="{{route("manageipeaattachment.delete")}}?id=' +
                                    data.id +
                                    '"><i class="fa fa-trash-o"></i></a>  '
                            }
                        });
                    } else if (key == 'ipea_email_action') {
                        jsonObj.push({
                            width: (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null,
                            data: null,
                            render: function(data, type, row) {
                                var returnText = '';
                                @canPermission(['edit_branch', 'edit_ipea_data'])
                                returnText +=
                                    '<a href="{{route("emailtemplate.edit")}}?id=' +
                                    data.id +
                                    '" ><i class="fa fa-pencil"></i></a>';
                                @endcanPermission
                                @canPermission(['edit_branch'])
                                returnText +=
                                    ' &nbsp; <a href="{{route("emailtemplate.delete")}}?id=' +
                                    data.id +
                                    '"><i class="fa fa-trash-o"></i></a>';
                                @endcanPermission
                                return returnText;

                            }
                        });
                    } else {
                        item["data"] = key;
                        item["name"] = val;
                        item["width"] = (jsonFieldsWidth[key]) ? jsonFieldsWidth[key] : null;
                        jsonObj.push(item);
                    }
                }

            });
        });

        return jsonObj;
    }
    var dataTableDelay = 1000;
    table = $('#dataTable').DataTable({
        "order": [
            [<?php echo $dataTable['defaultsort']?>, "asc"]
        ],
        "ajax": {
            "url": "<?php echo $dataTable['action']?>",
            "data": function(d) {
                d.filters = $("#datatableFilterSearchForm").serializeArray();
                d.jobviteType = "{{ request()->get('type') }}"
            }
        },
        "drawCallback": function (oSettings, json) {
            <?php if (LD::checkFeatureFlag('show-mass-text-option')  && H::canPermissions('save.massmessage')) { ?>
            $('.select-all-candidate.td').off().bind('click', function() {
                showHideButtonForMassText($('.select-all-candidate.td:checked').length ? true : false);
            });
            <?php } ?>
        },
        "processing": true,
        "serverSide": true,
        "columns": getFieldJSON(),
        "initComplete": function(settings, json) {
            jQuery('#dataTable_filter input').off('keyup.DT input.DT');
            var searchDelay = null;
            jQuery('#dataTable_filter input').on('keyup', function(e) {
                var search = jQuery('#dataTable_filter input').val();
                clearTimeout(searchDelay);
                searchDelay = setTimeout(function() {
                    if (search != null && search.length >= 3)
                        table.search(search).draw();
                    if (search.length == 0 && e.keyCode == 8)
                        table.search(search).draw();
                }, dataTableDelay);
            });
        },

    });
    console.log(table, 'table');

});
    function showHideButtonForMassText(isChecked) {
		$('#new_dataTable_wrapper div.updateall').remove();
        $('#new_dataTable_wrapper').empty();
		if (isChecked) {
			var html = '<div class="row pt-1"><div class="col-md-6 pl-1 pb-2 text-left"><div style="display: inline-block;" class="dataTables_length updateall">';
			html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="getMassTextForms(\'candidates\')" class="btn btn-primary">Send Message</button>';
			html += '</div></div><div  class="col-md-6 pl-1"></div></div>';
			$('#new_dataTable_wrapper').prepend(html);
		}
	}
	$(document).ready(function(){
		$('#select-all-checkbox-candidate').click(function() {
			$('.select-all-candidate.td').prop('checked', $(this).prop("checked"));
			showHideButtonForMassText($(this).is(':checked'));
		});
	});
</script>




@yield("datatablebottom")
@stop
