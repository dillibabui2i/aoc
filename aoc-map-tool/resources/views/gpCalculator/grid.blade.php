<?php
use App\Helpers\LD;
use App\Helpers\H;
?>
@extends('layouts.myaccount')
@section('title', 'GP List')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">GP List</h1>
            <a title="Add GP" href="{{route('gpCalculatorV2.index')}}"><i class="fa fa-plus-circle"></i></a>
        </div>
    </div>

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
                            @foreach($columns as $k => $v)
                            <th>{{$v['label']}}</th>
                            @endforeach
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
var fields = '<?php echo json_encode($columns);?>';
fields = JSON.parse(fields);
var columns = [];
$.each(fields, function(key, val) {
    columns.push({
        data: val.name,
        name: val.name,
        visible: typeof val.visible !== 'undefined' ? val.visible : true
    });
});
$(document).ready(function($) {
    var dataTableDelay = 1000;
    columns.push({
        data: null,
        width:'170px',
        className: 'action',
        orderable: false,
        render: function(data, type, row) {
            var actionBtn = ' <a href="{{route("gpCalculatorV2.index")}}?gpId='+row.id+'" ><i class="fa fa-pencil ml-2" aria-hidden="true"></i></a>';
            return actionBtn;
        }
    });
    var table = $('#dataTable').DataTable({
        "order": [
            [0, "desc"]
        ],
        "ajax": {
            "url": "{{ route('gpCalculatorV2.list') }}",
            data: function(d) {
                d.filters = $('#datatableFilterSearchForm').serializeArray();
            }
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
                    if (search != null && search.length >= 3)
                        table.search(search).draw();
                    if (search.length == 0 && e.keyCode == 8)
                        table.search(search).draw();
                }, dataTableDelay);
            });
        },
        "columns": columns
    });
});
</script>

@endsection
