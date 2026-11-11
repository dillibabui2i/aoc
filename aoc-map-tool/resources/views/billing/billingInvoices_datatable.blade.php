<?php
use App\Helpers\H;
use App\Helpers\LD;
$fields =  [];
$type = str_replace(' ', '_', $type);
$typeFunction = ucwords(str_replace('_', '', $type));
?>
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
    $(document).ready(function(){
		$('#select-all-checkbox-{{$type}}').click(function() {
			$('.select-all-{{$type}}').prop('checked', $(this).prop("checked"));
            showHideInvoiceUpdateButtonFor{{$type}}($(this).is(':checked'));
		});
	});
    function showHideInvoiceUpdateButtonFor{{$type}}(isChecked) {
		$('#dataTable-{{$type}}_wrapper div.updateall').remove();
		if (isChecked) {
			var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
                html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="invoicesOutcome{{$type}}(this)" \
                class="btn btn-primary">Set Outcome</button>';

            @if(LD::checkFeatureFlag('manage-project-tags'))
                html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="invoicesTag{{$type}}()" \
                class="btn btn-primary">Add Tag</button>';
            @endif

			html += '</div>';
			$('#dataTable-{{$type}}_wrapper').prepend(html);
		}
	}
    var exportInvoiceDatatableParams = [];
    function <?php  echo $type?>(){
        var sortOrder = 3;
        @if($type == 'invoices_pending')
        sortOrder = 2;
        @endif
        var forms = '<?php  echo $form?>';
        @if($type == 'invoices_pending')
            forms = forms+ ', #billingInvoiceForm2';
        @endif
        @if(LD::checkFeatureFlag('adjustments-bucket-invoices-widget'))
            @if($type == 'invoices_adjustments')
                forms = forms+', #billingInvoiceForm3';
            @endif
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
                        var select = '<input class="form-input select-all-<?php echo $type?>" \
                        type="checkbox" name="select-<?php echo $type?>[]" value="'+row['id']+'" />';
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
                <?php if($type == 'invoices_pending') { ?>
                        let actionItem = '<a href="javascript:void(0);" title="Set Outcome" ' +
                    'onclick="invoicesOutcomeFromDetail(\''+row['invoice_bucket_type']+'\', '+row['id']+')">' +
                    '<i class="fa fa-calendar"></i></a>&nbsp;|&nbsp;';
                    actionItem += '<a href="javascript:void(0);" title="View Detail" ' +
                    'onclick="getInvoiceDetails('+row['id']+','+row['invoice_bucket_type']+')">' +
                    '<i class="fa fa-eye"></i></a>';
                    actionItem += '<a  href="javascript:void(0);" title="Add Note" \
                    onclick="openNotes('+row['id']+',\'invoice\',\'Invoice Notes Added\');">\
                    <i class="fa fa-sticky-note-o"></i></a>';
                    actionItem += '<a  href="javascript:void(0);" title="Client Profile" \
                    onclick="getClientDetails('+row['patient_id']+');">\
                    <i class="fa fa-user"></i></a>';
                <?php }elseif($type == 'invoices_adjustments') { ?>
                    let actionItem = '<a href="javascript:void(0);" title="Set Outcome" ' +
                    'onclick="invoicesAdjustmentOutcome('+row['id']+')">' +
                    '<i class="fa fa-calendar"></i></a>&nbsp;|&nbsp;';
                    actionItem += '<a href="javascript:void(0);" title="View Detail" ' +
                    'onclick="getInvoiceDetails('+row['id']+',\'invoices_adjustments\')">' +
                    '<i class="fa fa-eye"></i></a>';
                    actionItem += '<a  href="javascript:void(0);" title="Add Note" \
                    onclick="openNotes('+row['id']+',\'invoice\',\'Invoice Notes Added\');">\
                    <i class="fa fa-sticky-note-o"></i></a>';
                    actionItem += '<a  href="javascript:void(0);" title="Reassign" \
                    onclick="invoicesAdjustmentReassign('+row['id']+');">\
                    <i class="fa fa-refresh"></i></a>';
                <?php } else{  ?>
                    let actionItem = '<a href="javascript:void(0);" title="Set Outcome" ' +
                    'onclick="invoicesOutcome{{$type}}(this, '+row['id']+')">' +
                    '<i class="fa fa-calendar"></i></a>&nbsp;|&nbsp;';
                    actionItem += '<a href="javascript:void(0);" title="View Detail" ' +
                    'onclick="getInvoiceDetails('+row['id']+',\'{{$type}}\')">' +
                    '<i class="fa fa-eye"></i></a>';
                    actionItem += '<a  href="javascript:void(0);" title="Add Note" \
                    onclick="openNotes('+row['id']+',\'invoice\',\'Invoice Notes Added\');">\
                    <i class="fa fa-sticky-note-o"></i></a>';
                <?php }  ?>
                return actionItem;
            }
        });
        var <?php echo $type?> = $("#dataTable-<?php echo $type?>").DataTable({
            "order": [
                [sortOrder, "desc"]
            ],
            "columnDefs": [
                { className: "dt-nowrap", "targets": [1] },
                { className: "dt-nowrap", "targets": [-1] },
            ],
            "ajax": {
                "url": "{{route('dashboard.getInvoicesData')}}?type=<?php echo $type?>",
                type: 'POST',
                data: function(d) {
                    d._token = $('meta[name="csrf-token"]').attr('content');
                    d.filters = $(forms).serializeArray();
                    exportInvoiceDatatableParams['{{$type}}'] = d;

                    if (window.GlobalLocationFilter) {
                        window.GlobalLocationFilter.applyToParams(d, "#dataTable-@php echo $type @endphp", {
                            state: "unbilledschedule_state",
                            region: "unbilledschedule_region",
                            branch: "unbilledschedule_branch"
                        });
                    }
                },
            },
            "deferRender": true,
            "processing": true,
            "serverSide": true,
            "columns": columns_<?php echo $type?>,
            "drawCallback": function (oSettings, json) {
                showHideInvoiceUpdateButtonFor{{$type}}(false);
                $('.select-all-{{$type}}').off().bind('click', function() {
					showHideInvoiceUpdateButtonFor{{$type}}($('.select-all-{{$type}}:checked').length ? true : false);
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
                <?php if(LD::checkFeatureFlag('add-export-to-all-invoice-buckets')){ ?>
                jQuery("#dataTable-<?php echo $type?>_filter").prepend(
                    '<a data-type="{{$type}}" title="Export" href="javascript:void(0)" \
                    class="exportInvoiceBucket"><i class="fa fa-download"></i></a>');
                <?php }  ?>
            },
            <?php if($type == 'invoices_adjustments'){?>
                "rowCallback": function( row, data ) {
                    if ( data.invoice_completed_age >= 1) {
                        $(row).addClass('dashboard-older-record-cl-ra');
                    }
                },
            <?php } ?>
        });
    }

    function invoicesTag{{$type}}() {
        let selectedIds = [];
        $('input[name="select-{{$type}}[]"]:checked').each(function () {
            selectedIds.push(this.value);
        });

        if (selectedIds.length === 0) return;

        $.ajax({
            url: "{{ route('billing.showTagInvoiceModal') }}",
            type: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                ids: selectedIds.join(",")
            },
            success: function (html) {
                $("#tempModalCnt").remove();
                $('<div id="tempModalCnt">').appendTo('body').html(html);
                $('#billingInvoiceTagModal').modal('show');
            }
        });
    }

    function invoicesOutcome{{$type}}(button, id = null){
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
            url: "{{route('billing.showInvoiceOutcome')}}",
            type: "POST",
            dateType:"html",
            data: {
                _token: _token,
                ids: selectedIds.join(","),
                type: '{{$type}}',
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#billingInvoiceOutcomeModal').modal('show');
            },
        });
    }
    @if(LD::checkFeatureFlag('change-export-method-billing-dashboard'))
    $(document).off('click','.exportInvoiceBucket').on('click','.exportInvoiceBucket',function(e){
        e.preventDefault();
        var type = $(this).attr('data-type');
        let _token = $('meta[name="csrf-token"]').attr('content');
        data = exportInvoiceDatatableParams[type];
        data['type'] = type;
        var tempdata = {};
        tempdata['postexportdata'] = JSON.stringify(data);
        tempdata['_token'] = _token;
        postInNewTab("{{route('billing.exportInvoiceBucket')}}", tempdata);
    });
    @else
    $(document).off('click','.exportInvoiceBucket').on('click','.exportInvoiceBucket',function(){
        var type = $(this).attr('data-type');
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        data = exportInvoiceDatatableParams[type];
        data['type'] = type;
        var url = "{{route('billing.exportInvoiceBucket')}}" + '?' + $.param(data);
        var newWindow = window.open(url, '_blank');

    });
    @endif
</script>
