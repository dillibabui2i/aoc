<?php

use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Manage Adjustments')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Adjustments</h1>
            @canPermission('adjustments.edit')
            <a title="Add Adjustment" href="{{ route('adjustment.add') }}"><i class="fa fa-plus-circle"></i></a>
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
                            <th>Category</th>
                            <th>Approval Level 1</th>
                            <th>Approval Level 2</th>
                            <th>Approval Level 3</th>
                            @canPermission('adjustments.edit')
                            <th>Actions</th>
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
                "url": "{{ route('adjustment.data') }}"
            },

            "processing": true,
            "serverSide": true,
            "columns": [
                {
                    "data": "category"
                },
                {
                    "data": "level_1_name"
                },
                {
                    "data": "level_2_name"
                },
                {
                    "data": "level_3_name"
                },
                @canAnyPermission('adjustments.edit') {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }
                @endcanAnyPermission
            ],
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
