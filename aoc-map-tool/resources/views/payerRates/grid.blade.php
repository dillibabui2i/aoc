@extends('layouts.myaccount')
@section('title', 'Manage Payer Rates')
@section('content')
<?php
$filters = [
    [
        'label'=>'Payer',
        'name'=>'payrate-payer',
        'type'=>'multiselect',
        'source'=>$payer
    ],
];
?>
<div class="container-fluid">

    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">
                Manage Payer Rates
                @canPermission('payerrates.add')
                    <a title="Add Payer Rates" href="{{route('payerrates.add')}}"><i class="fa fa-plus-circle"></i></a>
                @endcanPermission
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
            @component('components.datatable-filters',['filters'=>$filters])
            @endcomponent
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
                width: '10%',
                render: function(data, type, row) {
                    var actionButton = '';
                    @canPermission(['payerrates.delete'])
                        actionButton += '<a href="javascript:void(0)" data-id="'+data.id+'" title="Remove" class="disable-it"><i class="fa fa-trash-o" aria-hidden="true"></i></a>';
                    @endcanPermission
                    @canPermission(['payerrates.update'])
                        actionButton += `<a href="{{ url('editpayerrates') }}/${data.id}"  title="Edit" class="edit-it"><i class="fa fa-edit" aria-hidden="true"></i></a>`;
                    @endcanPermission
                    @canPermission(['payerrates.approve'])
                        if (data.status == 0 || data.pending_data != null) {
                            actionButton += '<a href="javascript:void(0)" data-id="'+data.id+'" title="Approve Payer Rate" class="approve-it"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
                            actionButton += '<a href="javascript:void(0)" data-id="'+data.id+'" title="Deny Payer Rate" class="deny-it"><i class="fa fa-ban" aria-hidden="true"></i></a>';
                        }
                    @endcanPermission
                    if( data.denied_at != null) {
                        actionButton += '<i class="fa fa-times text-danger ml-2" title="Rate Denied"></i>';
                    }
                    return actionButton;
                }
            });
            var table = $('#dataTable').DataTable({
                "order": [
                    [1, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('payerrates.list') }}",
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
                jQuery('.disable-it').on('click', function() {
                    var id = $(this).attr('data-id');
                    modalConfirm("Are you sure want to remove this record?",'Yes','No',function(res, modelDialog){
                        if (res) {
                            let _token = $('meta[name="csrf-token"]').attr('content');
                            $.ajax({
                                url: "{{route('payerrates.delete')}}",
                                type: "GET",
                                data: {
                                    id: id,
                                    _token: _token
                                },
                                success: function(response) {
                                    $('#dataTable').DataTable().ajax.reload();
                                },
                            });
                        }
                        modelDialog.modal('hide');
                    });
                    return false;
                });

                jQuery('.approve-it').on('click', function() {
                    var id = $(this).attr('data-id');
                    modalConfirm("Approve Payer Rates?",'Yes','No',function(res, modelDialog){
                        if (res) {
                            let _token = $('meta[name="csrf-token"]').attr('content');
                            $.ajax({
                                url: "{{route('payerrates.approve')}}",
                                type: "GET",
                                data: {
                                    id: id,
                                    _token: _token
                                },
                                success: function(response) {
                                    $('#dataTable').DataTable().ajax.reload();
                                },
                            });
                        }
                        modelDialog.modal('hide');
                    });
                    return false;
                });
                jQuery('.deny-it').on('click', function() {
                    var id = $(this).attr('data-id');
                    modalConfirm("Deny Payer Rates?",'Yes','No',function(res, modelDialog){
                        if (res) {
                            let _token = $('meta[name="csrf-token"]').attr('content');
                            $.ajax({
                                url: "{{route('payerrates.deny')}}",
                                type: "GET",
                                data: {
                                    id: id,
                                    _token: _token
                                },
                                success: function(response) {
                                    $('#dataTable').DataTable().ajax.reload();
                                },
                            });
                        }
                        modelDialog.modal('hide');
                    });
                    return false;
                });
            });
        });
    });
    $('#payrate-payer').multiselect({includeSelectAllOption: true,enableCaseInsensitiveFiltering: true});
</script>

@endsection
