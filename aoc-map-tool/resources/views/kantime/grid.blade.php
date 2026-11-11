<?php
use App\Helpers\H;
?>
@extends('layouts.myaccount')
@section('title', 'Kantime Facility')
@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Kantime Facility</h1>
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
columns.push({
    data: null,
    width: '5%',
    sClass:'dt-nowrap',
    searchable: false,
    orderable: false,
    render: function(data, type, row) {
        var action = '';
        action += '<a href="javascript:void(0)" onclick=\'editKantimeFacility("' + row['id'] + '")\' title="Edit Kantime Facility"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
        return action;
    }
});
$(document).ready(function($) {
    var dataTableDelay = 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [1, "desc"]
        ],
        "ajax": {
            "url": "{{ route('view.kantimefacility') }}",
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
        "drawCallback": function(settings) {
            $(".delete-it").on('click', function() {
                if (confirm("Are you sure want to delete this evaluation?")) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: $(this).attr('data-href'),
                        type: "GET",
                        data: {
                            _token: _token
                        },
                        success: function(response) {
                            if (response.status) {
                                $('#dataTable').DataTable().ajax.reload();
                            }
                        },
                    });
                }
                return false;
            });
        },
        "columns": columns
    });
});

function editKantimeFacility(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    window.location.href="{{route('add.kantimefacility')}}?facility_id="+id;
}

</script>

@endsection
