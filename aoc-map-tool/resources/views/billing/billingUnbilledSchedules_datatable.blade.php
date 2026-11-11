<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));

?>

<style>
    i.fa.fa-download{
        font-size: 22px;
        color: #215eac;
    }
</style>

<input type="hidden" name="reload-<?php echo $type?>" id="reload-<?php echo $type?>" value="1" />
<div class="table-responsive">
    <table class="table table-bordered w-100"
    id="dataTable-<?php echo $type?>"
    aria-describedby="">
        <thead>
            <tr>
                <?php foreach($dataTable as $key => $data) {
                    if($key == 'id') {
                        $fields[] = ["data"=>$key,"name"=>$key,"visible" =>false];
                    } else {
                        $fields[] = ["data"=>$key,"name"=>$key];
                    }
                ?>
                    @if($key=='select')
                        <th>
                            <input class="form-input"
                            id="select-all-checkbox-{{$type}}"
                            type="checkbox" name="select-{{$type}}"
                            value="0" />
                        </th>
                    @else
                        <th><?php echo $data?></th>
                    @endif
                <?php } ?>
                <th>Action</th>
            </tr>
        </thead>
    </table>
</div>

<script>
    var exportDatatableParams = [];
    function <?php  echo $type?>(){
        var forms = '<?php  echo $form?>';
        @if($type == 'bill_not_ready')
            forms = forms+ ', #billingUnbilledSchedulesForm2';
        @endif
        @if($type == 'bill_ready')
            forms = forms+ ', #billingUnbilledSchedulesForm3';
        @endif
        @if(LD::checkFeatureFlag('move-common-filters-billing-dashboard'))
            forms = forms+', #billingMainForm';
        @endif
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
                        @if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard'))
                            var select = '<input class="form-input select-all-<?php echo $type?>" type="checkbox" name="select-<?php echo $type?>[]" value="'+row['smids']+'" />';
                        @else
                            var select = '<input class="form-input select-all-<?php echo $type?>" type="checkbox" name="select-<?php echo $type?>[]" value="'+row['id']+'" />';
                        @endif
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
                var action = '';
                @if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard'))
                action += '<a  href="javascript:void(0);" title="Add Note" \
                        onclick="openNotes(\''+row['smids']+'\',\'billing\',\'Billing Notes Added\', \'{{$type}}\');">\
                        <i class="fa  fa-sticky-note-o"></i></a>';
                @else
                action += '<a  href="javascript:void(0);" title="Add Note" \
                        onclick="openNotes('+row['id']+',\'billing\',\'Billing Notes Added\', \'{{$type}}\');">\
                        <i class="fa  fa-sticky-note-o"></i></a>';
                @endif
                <?php if($type=='bill_ready' || ($type=='bill_not_ready' && LD::checkFeatureFlag('action-column-not-bill-ready-bucket-billing-dashboard'))){ ?>
                    @if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard'))
                        action += '<a href="javascript:void(0);" title="Resolve" ' +
                        'onclick="billingResolve{{$type}}(this, \''+row['smids']+'\');">' +
                        '<i class="fa fa-check-square-o"></i></a>';
                        action += '<a href="javascript:void(0);" title="Set Outcome" ' +
                        'onclick="openBillingOutcome{{$type}}(this, \''+row['smids']+'\');">' +
                        '<i class="fa fa-calendar"></i></a>';
                    @else
                        action += '<a href="javascript:void(0);" title="Resolve" ' +
                        'onclick="billingResolve{{$type}}(this, '+row['id']+');">' +
                        '<i class="fa fa-check-square-o"></i></a>';
                    @endif
                <?php } ?>
                @if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard'))
                action += '<a href="javascript:void(0);" title="View Detail" ' +
                'onclick="getBillingDetails(\''+row['smids']+'\')">' +
                '<i class="fa fa-eye"></i></a>';
                @if(H::canPermissions(['billing.billingExclude'])) {
                    action += '<a href="javascript:void(0);" title="Exclude" ' +
                        'onclick="billingExclude{{$type}}(this, \'' + row['smids'] + '\')">' +
                        '<i class="fa fa-close"></i></a>';
                }
                @endif
                @else
                action += '<a href="javascript:void(0);" title="View Detail" ' +
                'onclick="getBillingDetails('+row['id']+')">' +
                '<i class="fa fa-eye"></i></a>';
                @if(H::canPermissions(['billing.billingExclude'])) {
                action += '<a href="javascript:void(0);" title="Exclude" ' +
                    'onclick="billingExclude{{$type}}(this, \'' + row['id'] + '\')">' +
                    '<i class="fa fa-close"></i></a>';
                }
                @endif
                @endif
                return action;
            }
        });
        var <?php echo $type?> = $("#dataTable-<?php echo $type?>").DataTable({
            "order": [
                [6, "desc"]
            ],
            "columnDefs": [

                { className: "dt-nowrap", "targets": [1] },
                { className: "dt-nowrap", "targets": [-1] },

            ],
            "ajax": {
                "url": "{{route('dashboard.getUnbilledSchedulesData')}}?type=<?php echo $type?>",
                type: 'POST',
                data: function(d) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
                    d.filters = $(forms).serializeArray();
                    exportDatatableParams['{{$type}}'] = d;
                },
            },
            "deferRender": true,
            "processing": true,
            "serverSide": true,
            "columns": columns_<?php echo $type?>,
            "drawCallback": function (oSettings, json) {
                showHideUpdateButtonFor{{$type}}(false);
                $('.select-all-{{$type}}').off().bind('click', function() {
					showHideUpdateButtonFor{{$type}}($('.select-all-{{$type}}:checked').length ? true : false);
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
                    class="billingExport"><i class="fa fa-download"></i></a>'
                    );
            },
            <?php if($type=='bill_ready'){?>
                "rowCallback": function( row, data ) {
                    if ( data.DaysPastDOS >= 14 && data.less_than_today ==1) {
                        $(row).addClass('dashboard-older-record-cl-ra');
                    }
                },
            <?php } ?>
        });
    }
    $(document).ready(function(){
		$('#select-all-checkbox-{{$type}}').click(function() {
			$('.select-all-{{$type}}').prop('checked', $(this).prop("checked"));
            showHideUpdateButtonFor{{$type}}($(this).is(':checked'));
		});
        $(document).on("hidden.bs.modal", "#notes-popup", function() {
            showHideUpdateButtonFor{{$type}}(false);
            $('.dataTable').DataTable().ajax.reload(null, false);
        });
	});
    function showHideUpdateButtonFor{{$type}}(isChecked) {
		$('#dataTable-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
            <?php if($type=='bill_not_ready' && !LD::checkFeatureFlag('action-column-not-bill-ready-bucket-billing-dashboard')){?>
                html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="openNotesPopupForBilling{{$type}}(this)" \
            class="btn btn-primary">Add Notes</button>';
            <?php } else {?>
                html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="openBillingOutcome{{$type}}(this)" \
                class="btn btn-primary">Set Outcome</button>';
                //Resolved
                html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="billingResolve{{$type}}(this)" \
                class="btn btn-primary">Resolve</button>';
                //Add notes
                html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="openNotesPopupForBilling{{$type}}(this)" \
                class="btn btn-primary">Add Notes</button>';
                //Exclude schedules
                @if(H::canPermissions(['billing.billingExclude'])) {
                    html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="billingExclude{{$type}}(this)" \
                    class="btn btn-primary">Exclude</button>';
                }
               @endif
            <?php } ?>
			html += '</div>';
			$('#dataTable-{{$type}}_wrapper').prepend(html);
		}
	}
    function openNotesPopupForBilling{{$type}}(button){
        button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
		$('input[name="select-{{$type}}[]"]:checked').each(function() {
			selectedIds.push(this.value);
		});

        @if(LD::checkFeatureFlag('send-one-notifications-per-week-row-unbilled-schedules'))
            openNotes(selectedIds.join("##"),'billing','Billing Notes Added', '{{$type}}');
        @else
            @if(LD::checkFeatureFlag('show-operation-notes-from-open-notes'))
                openNotes(selectedIds.join(","),'billing','Billing Notes Added', '{{$type}}');
            @else
                openNotes(selectedIds.join(","),'billing','Billing Notes Added');
            @endif
        @endif
    }

    function openBillingOutcome{{$type}}(button, id = null){
        button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
        if(id !== null){
            selectedIds.push(id);
        }else{
            $('input[name="select-{{$type}}[]"]:checked').each(function() {
                selectedIds.push(this.value);
            });
        }
        $.ajax({
            url: "{{route('billing.showOutcome')}}",
            type: "POST",
            dateType:"html",
            data: {
				_token: _token,
				ids: selectedIds.join(","),
				type: '{{$type}}',
			},
            success: function(data) {
                button.disabled = false;
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#billingOutcomeModal').modal('show');
            },
        });
    }
    function billingResolve{{$type}}(button, id = null){
        button.disabled = true;
		let _token = $('meta[name="csrf-token"]').attr('content');
		var selectedIds = [];
        if(id !== null){
            selectedIds.push(id);
        }else{
            $('input[name="select-{{$type}}[]"]:checked').each(function() {
                selectedIds.push(this.value);
            });
        }
        $.ajax({
            url: "{{route('billing.billingResolve')}}",
            type: "POST",
            dateType:"html",
            data: {
				_token: _token,
				selectedIds: selectedIds.join(","),
				type: '{{$type}}',
			},
            success: function(data) {
                button.disabled = false;
                $('.dataTable').DataTable().ajax.reload(null, false);
                showHideUpdateButtonFor{{$type}}(false);
            },
        });
    }

    function billingExclude{{$type}}(button, id = null){
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');

        var selectedIds = [];
        if (id !== null) {
            selectedIds.push(id);
        } else {
            $('input[name="select-{{$type}}[]"]:checked').each(function () {
                selectedIds.push(this.value);
            });
        }

        pluralPart = selectedIds.length > 1 ? ' these records ' : ' this record ';

        popUpMessage = 'Are you sure you want to exclude' + pluralPart + 'from the bucket?' +
            ' Please note, once excluded,' + pluralPart + 'will not reappear in the bucket under any circumstance.'

        modalConfirm(popUpMessage,'Yes','No',function(res,modelDialog){
            if(res){
                $.ajax({
                    url: "{{route('billing.billingExclude')}}",
                    type: "POST",
                    dateType:"html",
                    data: {
                        _token: _token,
                        selectedIds: selectedIds.join(","),
                        type: '{{$type}}',
                    },
                    success: function(data) {
                        $('.dataTable').DataTable().ajax.reload(null, false);
                        showHideUpdateButtonFor{{$type}}(false);
                        modelDialog.modal('hide');
                    },
                });
            }else{
                modelDialog.modal('hide');
            }
        });
        button.disabled = false;
    }

    @if(LD::checkFeatureFlag('change-export-method-billing-dashboard'))
    $(document).off('click','.billingExport').on('click','.billingExport',function(e){
        e.preventDefault();
        var type = $(this).attr('data-type');
        let _token = $('meta[name="csrf-token"]').attr('content');
        data = exportDatatableParams[type];
        data['type'] = type;
        var tempdata = {};
        tempdata['postexportdata'] = JSON.stringify(data);
        tempdata['_token'] = _token;
        postInNewTab("{{route('billing.exportBilling')}}", tempdata);
    });
    @else
    $(document).off('click','.billingExport').on('click','.billingExport',function(){
        var type = $(this).attr('data-type');
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        data = exportDatatableParams[type];
        data['type'] = type;
        var url = "{{route('billing.exportBilling')}}" + '?' + $.param(data);
        var newWindow = window.open(url, '_blank');

    });
    @endif
</script>
