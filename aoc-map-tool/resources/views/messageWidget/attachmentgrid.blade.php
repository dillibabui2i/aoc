@extends('layouts.myaccount')
@section('title', 'Manage SMS Attachments')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage SMS Attachments</h1>
            <a title="Add New Message Attachment" href="{{route('managemessageattachment.add')}}"><i class="fa fa-plus-circle"></i></a>
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
                orderable: false,
                width: '8%',
                render: function(data, type, row) {
                    var action = '';
                    action += '<a href="{{route("managemessageattachment.edit")}}?id='+data.id+'" class="edit"><i class="fa fa-pencil" title="Edit"></i></a>';
                    action += '<a href="javascript:void(0)" class="disable-it" data-href="{{route("managemessageattachment.delete")}}?id='+data.id+'"><i class="fa fa-trash-o" title="Disable"></i></a>';
                    action += '<a href="{{route("managemessageattachment.download")}}?id='+data.id+'" class="download"><i class="fa fa-download" title="Download"></i></a>';
                    return action;
                }
            });
            var table = $('#dataTable').DataTable({
                "order": [
                    [2, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('managemessageattachment.list') }}",
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

                    $('.disable-it').click(function(){
                        if (confirm("Are you sure? You want to delete this record.")) {
                            window.location.href = $(this).data('href');
                        }
                    });
                },
                "columns": columns
            });
        });
    });
</script>

@endsection
