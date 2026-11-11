<?php
use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Manage Custom Fields')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Custom Fields</h1>
            @canPermission('customfieldaction')
                <a title="Add Custom Field" href="javascript:void(0)" class="addCustomField">
                    <i class="fa fa-plus-circle"></i>
                </a>
            @endcanPermission
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" aria-describedby="true">
                    <thead>
                        <tr>
                            <th>Sort Order</th>
                            <th>Entity</th>
                            <th>Category</th>
                            <th>Field Name</th>
                            <th>Field Code</th>
                            <th>Frontend Input</th>
                            <th>Frontend Label</th>
                            @canPermission('customfieldaction')
                            <th>Action</th>
                            @endcanPermission
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
            [0, "asc"]
        ],
        "ajax": {
            "url": "{{ route('getCustomFieldslistajax') }}",
        },
        "processing": true,
        "serverSide": true,
        "columns": [
            {
                "data": "sort"
            },
            {
                "data": "entity"
            },
            {
                "data": "category"
            },
            {
                "data": "field_name"
            },
            {
                "data": "db_field_name"
            },
            {
                data: 'input_type'
            },
            {
                data: 'frontend_label'
            },
            @canPermission('customfieldaction')
            {
                data: 'action',
                orderable: false,
                searchable: false
            },
            @endcanPermission
        ],
        @canPermission('customfieldaction')
        "aoColumnDefs": [
           {
                "aTargets": [7],
                "mData": "action",
                "mRender": function (data, type, full) {
                    btn = '<a href="javascript:void(0)" onclick="openCustomFieldForm(\'edit\', ' + full.id +
                    ')" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
                    btn += '<a href="javascript:void(0)" class="delete" data-id="' + full.id +
                    '"><i class="fa fa-trash" title="Delete"></i></a>';
                    return btn;
                }
            }
         ],
        @endcanPermission
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

    @canPermission('customfieldaction')

    $(document).on('click', '.addCustomField', function(){
        openCustomFieldForm('add');
    });

    $(document).on('click', '.delete', function(){
        var id = $(this).attr('data-id');
        modalConfirm("Do you really want to delete this Custom Field?",'Yes','No',
        function(res, modelDialog){
            if (res) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{route('manageCustomFields.delete')}}",
                    type: "GET",
                    data: {
                        id: id,
                        _token: _token
                    },
                    success: function(response) {
                        $('#dataTable').DataTable().ajax.reload();
                    },
                });
            }
            modelDialog.modal('hide');
        });
        return false;
    });
    @endcanPermission
});
</script>
<!-- /.container-fluid -->
@endsection
