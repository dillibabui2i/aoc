<?php
use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Manage Payer')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Payer</h1>
            <a title="Export" href="javascript:void(0)" id="exportPayerData"><i class="fa fa-download"></i></a>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" aria-describedby="true">
                    <thead>
                        <tr>
                            <th>Payer Name</th>
                            <th>State</th>
                            <th>Margin Configuration</th>
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
            "url": "{{ route('getPayerlistajax') }}",
        },
        "processing": true,
        "serverSide": true,
        "columns": [
            {
                "data": "name"
            },
            {
                "data": "state"
            },
            {
                "data": "margin_configuration"
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
$("#exportPayerData").click(function(e) {
    var data = {};
    var search = {};
    let _token = $('meta[name="csrf-token"]').attr('content');
    search['value'] = $('#dataTable_filter').find('input').val();
    data['search'] = search;
    var url = "{{route('exportPayerData')}}" + '?' + $.param(data);
    window.location.href = url;

});
</script>
<!-- /.container-fluid -->
@endsection
