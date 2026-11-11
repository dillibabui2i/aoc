@extends('layouts.myaccount')
@section('title', 'Manage Hub City')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">
                Manage Hub City
                <a title="Add Hub City" href="{{route('hubcities.add')}}"><i class="fa fa-plus-circle"></i></a>
            </h1>
        </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {!! implode('', $errors->all('<div>:message</div>')) !!}
    </div>
    @endif
    @if ($message = Session::get('error'))
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif

    <div class="card shadow mb-4">
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
<script>
    $(document).ready(function() {
        var fields = '<?php echo json_encode($columns);?>';
        fields = JSON.parse(fields);
        var columns = [];
        $.each(fields, function(key, val) {
            columns.push({
                data: val.name,
                name: val.name,
                visible: typeof val.visible !== 'undefined' ? val.visible : true,
                width: typeof val.width !== 'undefined' ? val.width : ''
            });
        });
        $(document).ready(function($) {
            var dataTableDelay = 1000;
            columns.push({
                data: null,
                ordering: false,
                width: '10%',
                render: function(data, type, row) {
                    return '<a href="javascript:void(0)" onclick="window.location.href=\'{{route('hubcities.edit')}}?id='+data.id+'\'" title="Edit"><i class="fa fa-edit" aria-hidden="true"></i></a> <a href="javascript:void(0)" data-href="{{route('hubcities.delete')}}?id='+data.id+'" title="Remove" class="disable-it"><i class="fa fa-trash-o" aria-hidden="true"></i></a>';
                }
            });
            var table = $('#dataTable').DataTable({
                "order": [
                    [1, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('hubcities') }}",
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

            table.on('draw', function() {
                jQuery('.disable-it').on('click', function(){
                    if (confirm("Are you sure want to remove this record?")) {
                        window.location.href = $(this).attr('data-href');
                    }
                    return false;
                });
            });
        });

        $(document).on("change", '#State', function() {
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('getstatebranch') }}",
                type: "GET",
                data: {
                    state: $(this).val(),
                    _token: _token
                },
                success: function(response) {
                    $("#Branch").empty().append('<option value="">Select Branch</option>');
                    $.each(response, function(index, value) {
                        $("#Branch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>');
                    });
                },
            });

        });
    });
</script>

@endsection
