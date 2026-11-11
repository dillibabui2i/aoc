<?php
use App\Helpers\H;
use App\Helpers\LD;

$invoicesArray = [
    "all" => 'All',
    "unpaid_invoices_no_response" => 'No Response',
    "unpaid_invoices_denied" => 'Denied',
    "invoices_under_paid" => 'Under Paid',
    "invoices_over_paid" => 'Over Paid',
];
$invoiceType  = [];
foreach($invoicesArray as $key=>$br) {
    $invoiceType[] = ["value"=>$key,'text'=>$br];
}
$billingInvoiceFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'billInvoices_memode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'billInvoices_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label'=>'Region',
        'name'=>'billInvoices_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label'=>'Branch',
        'name'=>'billInvoices_branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label'=>'Payer',
        'name'=>'billInvoices_payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ],
    [
        'label' => 'Special Payer Group',
        'name' => 'billInvoices_payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],
    [
        'label'=>'Invoice Type',
        'name'=>'invoice_type',
        'type'=>'select',
        'source'=>$invoiceType,
    ],
    [
        'label'=>'Invoice Date',
        'name'=>'InvoiceDate',
        'type'=>'dateRangePicker',
        'fromName'=>'fromInvoiceDate',
        'toName'=>'toInvoiceDate',
        'fromdate'=>'',
        'todate'=>'',
    ]
];
?>
@extends('layouts.myaccount')
@section('title', 'Invoices')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Invoices</h1>
            <a title="Export" href="javascript:void(0)" id="exportInvoiceData"><i class="fa fa-download"></i></a>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            @component('components.datatable-filters',['filters'=>$billingInvoiceFilter])
            @endcomponent
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" aria-describedby="true">
                    <thead>
                        <tr>
                            <th>Invoice Number</th>
                            <th>Invoice Status</th>
                            <th>Invoice Date</th>
                            <th>Aging</th>
                            <th>Client</th>
                            <th>Branch Location</th>
                            <th>Payer</th>
                            <th>Statement Period</th>
                            <th>Expected Amount</th>
                            <th>Paid Amount</th>
                            <th>Balance</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Page level custom scripts -->
<script type="text/javascript">
$(document).ready(function($) {
    var dataTableDelay= 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [1, "desc"]
        ],
        "ajax": {
            "url": "{{ route('billing.invoiceShowAllAjax') }}",
            type: 'POST',
            data: function(d) {
                d._token = $('meta[name="csrf-token"]').attr('content');
                d.filters = $('#datatableFilterSearchForm').serializeArray();
            }
        },
        "processing": true,
        "serverSide": true,
        "columns": [{
                "data": "invoice_number"
            },
            {
                "data": "invoice_status"
            },
            {
                "data": "invoice_date"
            },
            {
                "data": "invoice_age"
            },
            {
                "data": "clientName"
            },
            {
                "data": "FacilityName"
            },
            {
                "data": "Payer"
            },
            {
                "data": "StatementPeriod"
            },
            {
                "data": "contractual_amount"
            },
            {
                "data": "paidamount"
            },
            {
                "data": "contractual_paid_diff"
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ],
        "drawCallback": function(settings) {
        },
        "initComplete": function(settings, json) {
            jQuery('#dataTable_filter input').off('keyup.DT input.DT');
            var searchDelay = null;
            jQuery('#dataTable_filter input').on('keyup', function(e) {
                var search = jQuery('#dataTable_filter input').val();
                clearTimeout(searchDelay);
                searchDelay = setTimeout(function() {
                    if (search != null && search.length >=3)
                        table.search(search).draw();
                    if (search.length==0 && e.keyCode==8)
                        table.search(search).draw();
                }, dataTableDelay);
            });
        },
    });
});

@if(LD::checkFeatureFlag('change-export-method-billing-dashboard'))
    $("#exportInvoiceData").click(function(e) {
        e.preventDefault();
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        var tempdata = {};
        tempdata['postexportdata'] = JSON.stringify(data);
        tempdata['_token'] = _token;
        postInNewTab("{{route('billing.invoiceExport')}}", tempdata);
    });
@else
    $("#exportInvoiceData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        var url = "{{route('billing.invoiceExport')}}" + '?' + $.param(data);
        window.location.href = url;

    });
@endif
$(document).on("change",'#billInvoices_state',function(){
        var state = $(this).val().join(",");
        let _token = $('meta[name="csrf-token"]').attr('content');
        if (state.indexOf('TX') !== -1) {
            $('#billInvoices_region').multiselect('enable');
        } else {
            $('#billInvoices_region').multiselect('disable');
        }
        $.ajax({
        url: "{{ route('getstatebranch') }}",
        type: "GET",
        data: {
            state: state,
            _token: _token
        },
        success: function(response) {
            $('#billInvoices_branch').empty();
            $.each(response, function( index, value ) {
                $("#billInvoices_branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
            });
            $("#billInvoices_branch").multiselect("rebuild");
        },
    });
});
</script>
@endsection
