@extends('layouts.myaccount')
@section('title', 'Manage Roles')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-10 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Roles</h1>
            @canPermission('manageroles.add')
            <a title="Add Role" href="{{ route('manageroles.add') }}"><i class="fa fa-plus-circle"></i></a>
            @endcanPermission
        </div>
    </div>


    <!-- DataTales Example -->
    <div class="card shadow mb-4">

        @if ($message = Session::get('success111'))
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
                            <th>Name</th>
                            @canAnyPermission(['manageroles.editrole'])
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
var dataTableDelay =  1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [0, "desc"]
        ],
        "ajax": {
            "url": "{{ route('getroleslistajax') }}",
        },

        "processing": true,
        "serverSide": true,
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
        "columns": [{
                "data": "name"
            },
            @canAnyPermission(['manageroles.editrole']) {
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
<!-- /.container-fluid -->
@endsection
