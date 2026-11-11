<?php

use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Job Category')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Job Category</h1>
            @canPermission('jobcategory.edit')
            <a title="Add User" href="{{ route('jobcategory.add') }}"><i class="fa fa-plus-circle"></i></a>
            @endcanPermission
        </div>
    </div>


    <!-- DataTales Example -->
    <div class="card shadow mb-4">

        @if ($message = Session::get('success'))
        <div class="alert alert-success alert-block">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <strong>{{ $message }}</strong>
        </div>
        @endif
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                    <thead>
                        <tr>
                            <th>State</th>
                            <th>Type</th>
                            <th>Category</th>
                            <th>Created BY</th>
                            <th>Updated By</th>
                            @canPermission('jobcategory.edit')
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

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
    $(document).ready(function($) {
        var dataTableDelay = 1000;
        var table = $('#dataTable').DataTable({
            "ajax": {
                "url": "{{ route('jobcategoryjaxlist') }}"
            },

            "processing": true,
            "serverSide": true,
            "columns": [{
                    "data": "State"
                },
                {
                    "data": "Type"
                },
                {
                    "data": "category"
                },
                {
                    "data": "created_name"
                },
                {
                    "data": "updated_name"
                },
                @canAnyPermission('jobcategory.edit') {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
                @endcanAnyPermission
            ],
            "drawCallback": function(settings) {
                $(".toggle-status").off('click').on('click', function(e) {
                    e.preventDefault();
                    const url = $(this).data('href');
                    const action = $(this).find('i').hasClass('fa-toggle-on') ? 'disable' : 'enable';

                    if (confirm(`Are you sure you want to ${action} this Job Category?`)) {
                        window.location.href = url;
                    }
                    return false;
                });
            },
        });
    });
</script>

<script>
    setTimeout(function() {
        $('.alert-success, .alert-danger, .alert-warning').fadeOut('slow', function() {
            $(this).remove();
        });
    }, 2000);
</script>
<!-- /.container-fluid -->
@endsection
