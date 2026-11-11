<?php
use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Regions')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Regions</h1>
            @canPermission('region.edit')
            <a title="Add User" href="{{ route('region.add') }}"><i class="fa fa-plus-circle"></i></a>
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
                            <th>Region</th>
                            <th>Team Lead</th>
                            @canPermission('region.edit')
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
    var dataTableDelay= 1000;
    var table = $('#dataTable').DataTable({
        "ajax": {
            "url": "{{ route('regionajaxlist') }}"
        },

        "processing": true,
        "serverSide": true,
        "columns": [{
                "data": "region_name"
            },
            {
                "data": "team_leader"
            },
            @canAnyPermission('region.edit')
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
            @endcanAnyPermission
        ],
        "drawCallback": function(settings) {
            $(".disable-it").on('click', function() {
                if (confirm("Are you sure want to Delete this Region?")) {
                    window.location.href = $(this).attr('data-href');
                }
                return false;
            });
        },
    });
});
</script>
<!-- /.container-fluid -->
@endsection
