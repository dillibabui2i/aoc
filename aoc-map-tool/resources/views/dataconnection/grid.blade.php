<?php
use App\Helpers\H;
use App\Helpers\LD;
$sourceData =  [
    ['value' => 'MSSQL', 'text' => 'MSSQL'],
    ['value' => 'Snowflake', 'text' => 'Snowflake']
];
$dataConnectionFilter = [
        [
            'label'=>'Source',
            'name'=>'source',
            'type'=>'multiselect',
            'source'=>$sourceData
        ],
        [
            'label'=>'Last Update Date',
            'name'=>'last_updated_date',
            'type'=>'singledate',
            'fromName' => 'last_updated_date'
        ]
    ];
?>
@extends('layouts.myaccount')
@section('title', 'Data Connections')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Data Connections</h1>
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
            @component('components.datatable-filters',['filters'=>$dataConnectionFilter])@endcomponent
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Sync Name</th>
                            <th>Last Updated Date/Time</th>
                            <th>Last Sync Id</th>
                            <th>Purpose</th>
                            <th>Frequency</th>
                            <th>Comment</th>
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
            [0, "asc"]
        ],
        "ajax": {
            "url": "{{ route('getdataconnectionlistajax') }}",
            "data": function(d) {
                d.filters = $('#datatableFilterSearchForm').serializeArray();
            }
        },

        "processing": true,
        "serverSide": true,
        "columns": [
            {
                "data": "source"
            },
           {
                "data": "process"
            },
            {
                "data": "display_last_updated_date",
                "data-sort": "last_updated_date"
            },
            {
                "data": "last_updated_id",
                orderable: false,
                searchable: false
            },
            {
                "data": "purpose",
                orderable: false,
                searchable: false
            },
            {
                "data": "frequency",
                orderable: false,
                searchable: false
            },
            {
                "data": "info",
                orderable: false,
                searchable: false
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                width: 70
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
</script>
<!-- /.container-fluid -->
@endsection
