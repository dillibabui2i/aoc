@extends('layouts.myaccount')
@section('title', 'Manage Feature Flags')
@section('content')
<?php
$status = [
    [
        "value" => "0",
        "text" => "Targeting Off"
    ],
    [
        "value" => "1",
        "text" => "Targeting On"
    ],
    [
        "value" => "-1",
        "text" => "Flag Disabled"
    ],
];
$filters = [];
$filters = [
        [
            'label'=>'status',
            'name'=>'status',
            'type'=>'select',
            'source'=>$status
        ]
    ];
?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<style>
.tag.editable {
    text-decoration-line: underline;
    text-decoration-style: dashed;
    border-bottom: none;
}
</style>
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Feature Flags</h1>
        </div>
    </div>
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        @component('components.datatable-filters',['filters'=>$filters])
        @endcomponent
        @if ($message = Session::get('success'))
        <div class="alert alert-success alert-block">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <strong>{{ $message }}</strong>
        </div>
        @endif
        @if ($message = Session::get('error'))
        <div class="alert alert-danger alert-block">
            <button type="button" class="close" data-dismiss="alert">×</button>
            <strong>{{ $message }}</strong>
        </div>
        @endif
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                    <thead>
                        <tr>
                            <th>Flag Name</th>
                            <th>Users</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Created By</th>
                            <th>Status </th>
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
            "order": [
                [0, "asc"]
            ],
            "ajax": {
                "url": "{{ route('getfeatureflaglistajax') }}",
                data: function(d){
                    d.filters  = $("#datatableFilterSearchForm").serializeArray();
                    d.customer_group_code = 0;
                }
            },

            "processing": true,
            "serverSide": true,
            "columns": [{
                    "data": "flag"
                },
                {
                    "data": "users"
                },
                {
                    "data": "flag_description"
                },
                {
                    data: 'ips',
                    orderable: false,
                    searchable: false
                },
                {
                    "data": "created_by",
                    "data-sort": "created_by"
                },
                {
                    data: 'disaply_status',
                    "data-sort": "status",
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
                    if (confirm("Are you sure want to change the status of this feature flag?")) {
                        window.location.href = $(this).attr('data-href');
                    }
                    return false;
                });
                let _token = $('meta[name="csrf-token"]').attr("content");
                $.fn.editable.defaults.mode = "popup";
                $(".editable-select").editable({
                    mode: "inline",
                    params: function(params) {
                        params.table = $(this).editable().data("table");
                        params._token = _token;
                        return params;
                    },
                    display: function(value, sourceData, response) {
                        if (typeof sourceData !== "undefined") {
                            var k = findByKey(sourceData, "value", value);
                            if (typeof k !== "undefined") {
                                $(this).html(sourceData[k].text);
                            }
                        } else {
                            $(this).html(value);
                        }
                    },
                });
                function findByKey(arr, key, value) {
                    if (typeof arr !== "undefined") {
                        for (var i = 0; i < arr.length; i++) {
                            if (arr[i][key] == value) {
                                return i;
                            }
                        }
                    } else {
                        return 0;
                    }
                }
            },
            "initComplete": function(settings, json) {
                jQuery('#dataTable_filter input').off('keyup.DT input.DT');
                var searchDelay = null;
                jQuery('#dataTable_filter input').on('keyup', function(e) {
                    var search = jQuery('#dataTable_filter input').val();
                    clearTimeout(searchDelay);
                    searchDelay = setTimeout(function() {
                        if (search != null && search.length >= 3)
                            table.search(search).draw();
                        if (search.length == 0 && e.keyCode == 8)
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
<!-- /.container-fluid -->
@endsection
