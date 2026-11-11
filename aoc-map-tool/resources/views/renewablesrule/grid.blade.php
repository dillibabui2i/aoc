<?php

use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Renewables Rule')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Renewables Rule</h1>
            <a title="Add Rule" href="{{ route('addrenewablesrule') }}"><i class="fa fa-plus-circle"></i></a>
        </div>
    </div>

    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>States</th>
                            <th>Is Clinician</th>
                            <th>Is Office staff</th>
                            <th>Discipline</th>
                            <th>Expiring</th>
                            <th>Missing</th>
                            <th>Email to Nurse</th>
                            <th>Client over 21</th>
                            <th>Action</th>
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
                "url": "{{ route('getrenewablesruleajax') }}",
            },

            "processing": true,
            "serverSide": true,
            "columns": [
                {
                    "data": "type"
                },
                {
                    "data": "states",
                    "searchable": false,
                },
                {
                    "data": "is_clinician",
                    "searchable": false,
                    "orderable": false
                },
                {
                    "data": "is_Office_staff",
                    "searchable": false,
                    "orderable": false
                },
                {
                    "data": "discipline",
                    "searchable": false,
                },
                {
                    "data": "expiring",
                    "searchable": false,
                    "orderable": false
                },
                {
                    "data": "missing",
                    "searchable": false,
                    "orderable": false
                },
                {
                    "data": "email_to_nurse",
                    "searchable": false,
                    "orderable": false
                },
                {
                    "data": "has_client_over21",
                    "searchable": false,
                    "orderable": false
                },
                {
                    data: 'action',
                    name: 'Action',
                    orderable: false,
                    searchable: false,
                    width:"10%"
                },
            ],
            "drawCallback": function(settings) {
                $(".disable-it").on('click', function() {
                    if (confirm("Are you sure want to delete this Rule?")) {
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
