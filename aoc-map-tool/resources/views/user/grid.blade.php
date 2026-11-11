<?php
use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Manage Users')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Users</h1>
            @if(LD::checkFeatureFlag('export-users-list'))
                <a title="Export" href="javascript:void(0)" id="exportUserData"><i class="fa fa-download"></i></a>
            @endif
			@canPermission('manageusers.add')
            <a title="Add User" href="{{ route('manageusers.add') }}"><i class="fa fa-plus-circle"></i></a>
			@endcanPermission
        </div>
        <!-- <div class="form-group col-md-2 text-right">
<a href="{{ route('manageusers.add') }}" class="btn btn-primary">Add Users</a>
</div> -->
    </div>


    <!-- DataTales Example -->
    <div class="card shadow mb-4">

        @if ($message = Session::get('success111'))
        <div class="alert alert-success alert-block">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <strong>{{ $message }}</strong>
        </div>
        @endif
            @if ($data = Session::get('warningDisabledUserHasTrachVentClasses'))
                <div class="alert alert-info alert-block">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <strong>The user {{ $data['userName'] ?? '' }} was disabled and all their assigned classes should be reassigned to another Instructor.</strong>
                    @include('user.userTrachVentClasses', ['classes' => $data['classes'] ?? []])
                </div>
            @endif
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            @if(LD::checkFeatureFlag('add-kantime-id-in-user-grid'))
                            <th>KantimeId</th>
                            @endif
                            <th>Created </th>
                            <th>Status </th>
							@canAnyPermission(['manageusers.edit','manageusers.disable'])
                            <th>Action</th>
							@endcanAnyPermission
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
        "order": [
            @if(LD::checkFeatureFlag('add-kantime-id-in-user-grid'))
            [4, "desc"]
            @else
            [3, "desc"]
            @endif
        ],
        "ajax": {
            "url": "{{ route('getuserlistajax') }}",
            "data": function(d) {
                return $.extend({}, d, {
                    'customer_group_code': $('input[name="customer_group_code"]:checked')
                        .val(),
                })
            }
        },

        "processing": true,
        "serverSide": true,
        "columns": [{
                "data": "name"
            },
            {
                "data": "email"
            },
            {
                data: 'disaply_role'
            },
            @if(LD::checkFeatureFlag('add-kantime-id-in-user-grid'))
            {
                data: 'kantimeid',
                orderable: false,
                searchable: false
            },
            @endif
            {
                "data": "display_created",
                "data-sort": "created_at"
            },
            {
                data: 'disaply_status',
                orderable: false,
                searchable: false
            },
			@canAnyPermission(['manageusers.edit','manageusers.disable'])
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
                if (confirm("Are you sure want to disable this user?")) {
                    window.location.href = $(this).attr('data-href');
                }
                return false;
            });
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
    $('input[name="customer_group_code"]').on('click', function() {
        table.ajax.reload();
    });

    $("#exportUserData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        var url = "{{route('exportUserData')}}" + '?' + $.param(data);
        window.location.href = url;

    });
});
</script>
<!-- /.container-fluid -->
@endsection
