@extends('layouts.myaccount')
@section('title', 'Manage Classes')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Classes</h1>
            <a title="Add Class" href="{{ route('managetrachventclass.add') }}"><i class="fa fa-plus-circle"></i></a>
             @if ($canManageClassTypes)
        <div>
            <a title="Manage Class Types" data-target="#manageClassCategories" data-toggle="modal" href="#"><i class="fa fa-edit"></i></a>
        </div>
        @endif
        </div>
        <!-- <div class="form-group col-md-2 text-right">
<a href="{{ route('manageusers.add') }}" class="btn btn-primary">Add Users</a>
</div> -->
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
                            <th>Type</th>
                            <th>Location</th>
                            <th>Instructor</th>
                            <th>Class Date</th>
                            <th>Class Time</th>
                            <th>Repeat Every</th>
                            <th>Repeat Until</th>
                            <th>Capacity</th>
                            <th>Notes</th>
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
    var dataTableDelay= 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [3, "desc"]
        ],
        "ajax": {
            "url": "{{ route('gettrachventclasslistajax') }}",
            "data": function(d) {
                return $.extend({}, d, {
                    'customer_group_code': $('input[name="customer_group_code"]:checked')
                        .val(),
                })
            }
        },

        "processing": true,
        "serverSide": true,
        "columns": [
            {
                "data": "type_name"
            },
            {
                "data": "location",
                orderable: false,
                searchable: false
            },
            {
                data: 'instructor_name',
                orderable: false,
                searchable: false
            },
            {
                "data": "display_class_date",
                "data-sort": "class_date"
            },
            {
                "data": "display_class_time",
                "data-sort": "class_time"
            },
            {
                "data": "repeat_every",
                orderable: false,
                searchable: false,
                render: function(data) {
                    return data ? data.charAt(0).toUpperCase() + data.slice(1) : 'One-time';
                }
            },
            {
                "data": "display_repeat_until",
                orderable: false,
                searchable: false
            },
            {
                "data": "capacity"
            },
            {
                "data": "notes",
                orderable: false,
                searchable: false
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
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
});
</script>
@if ($canManageClassTypes)
    @include('trachventclass.manage-class-categories')
@endif
<!-- /.container-fluid -->
@endsection
