
<?php
use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Manage Text Templates')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Templates</h1>
            @canPermission('addTextTemplate')
            <a title="Add Text Template" href="javascript:showTextTemplateForm()"><i class="fa fa-plus-circle"></i></a>
			@endcanPermission
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" aria-describedby="true">
                    <thead>
                        <tr>
                            <th>Department</th>
                            <th>Category</th>
                            <th>Template Name</th>
                            <th>Created By</th>
                            @canAnyPermission(['addTextTemplate'])
                            <th>Action</th>
						    @endcanAnyPermission
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<x-modal>
    <x-slot name="id">textTemplateDelPopUp</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <form class="row" action="" id="deleteTextTemplateRequest">
                <input type="hidden" name="textTemplate" id="textTemplateId" value="">
            </form>
            <h5 id="confirmationDialogMessage">Do you really want to remove this Template?</h5>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="seeccrYesButton" onclick="deleteTextTemplateRequest()">Yes</button>
        <button class="btn btn-primary" id="seeccrNoButton" onclick="closeTextTemplateRequest()">No</button>
    </x-slot>
</x-modal>
<!-- Page level custom scripts -->
<script type="text/javascript">
$(document).ready(function($) {
    var dataTableDelay= 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [1, "desc"]
        ],
        "ajax": {
            "url": "{{ route('getTextTemplatelistajax') }}",
        },
        "processing": true,
        "serverSide": true,
        "columns": [{
                "data": "department"
            },
            {
                "data": "category"
            },
            {
                "data": "template_name"
            },
            {
                "data": "user_name"
            },
			@canAnyPermission(['addTextTemplate'])
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
			@endcanAnyPermission
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
function deleteTextTemplateRequest() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('deleteTextTemplate')}}",
        type: 'post',
        data: {
            id: $("#textTemplateId").val(),
            _token: _token
        },
        success: function(data) {
            $("#dataTable").DataTable().ajax.reload();
            $('#textTemplateDelPopUp').modal('hide');
        }
    });
}
function closeTextTemplateRequest(){
    $('#textTemplateDelPopUp').modal('hide');
}
</script>
<!-- /.container-fluid -->
@endsection