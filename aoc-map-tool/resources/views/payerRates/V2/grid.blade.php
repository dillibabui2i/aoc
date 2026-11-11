@extends('layouts.myaccount')
@section('title', 'Manage Payer Rates')
@section('content')
    @php
    $filters = [
        [
            'label'=>'Payer',
            'name'=>'payrate-payer',
            'type'=>'multiselect',
            'source'=>$payer
        ],
    ];
    @endphp

    <style>
        table.dataTable tbody td.dt-control { text-align: center; cursor: pointer; vertical-align: middle; }

        .expand-icon { transition: transform .2s; color: #9ca3af; font-size: 14px; display: inline-block; }
        .expand-icon.open { transform: rotate(90deg); color: #2563ab; }

        tr.expanded-row td { padding: 0 !important; border-bottom: 1px solid #dbeafe !important; }
        .expanded-inner { background: #f0f7ff; border-left: 3px solid #2563ab; padding: 12px 16px 14px 48px; }
        .expanded-inner .inner-label { font-size: 10px; font-weight: 700; color: #2563ab; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 10px; }

        .svc-chips { display: flex; flex-wrap: wrap; gap: 8px; }
        .svc-chip { display: flex; align-items: center; gap: 8px; background: #fff; border: 1px solid #dbeafe; border-radius: 7px; padding: 6px 12px; }
        .svc-chip .chip-name { font-size: 12px; font-weight: 500; color: #1a3a6b; }
        .svc-chip .chip-divider { width: 1px; height: 14px; background: #dbeafe; }
        .svc-chip .chip-rate { font-size: 13px; font-weight: 700; color: #059669; }
        .empty-expanded { font-size: 12px; color: #9ca3af; font-style: italic; }

        table.dataTable.dtr-inline.collapsed > tbody > tr > td.dtr-control::before,
        table.dataTable.dtr-inline.collapsed > tbody > tr > th.dtr-control::before,
        table.dataTable td.dt-control::before,
        table.dataTable td.dt-control::after {
            display: none !important;
            content: none !important;
        }

        #dataTable tbody tr:not(.expanded-row) {
            cursor: pointer;
        }

        #dataTable tbody tr:not(.expanded-row):hover td {
            background-color: #f0f7ff !important;
        }
    </style>

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
                            <th style="width: 30px;"></th>

                            @foreach($columns as $k => $v)
                                <th>{{$v['label']}}</th>
                            @endforeach
                            <th>Services</th>
                            <th>Action</th>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <script>
        function formatExpandedData(rowData) {

            var mockServices = (rowData.services || []).map(service => ({
                name: service?.service_name ?? '—',
                rate: service?.pending_rate ?? service?.rate,
            }));

            if (mockServices.length === 0) {
                return `
                <div class="expanded-inner">
                    <div class="inner-label">Configured Services & Rates</div>
                    <span class="empty-expanded">No services configured yet. Click Edit to add service rates.</span>
                </div>
            `;
            }

            var chipsHtml = '';
            mockServices.forEach(function(svc) {
                chipsHtml += `
                <div class="svc-chip">
                    <span class="chip-name">${svc.name}</span>
                    <span class="chip-divider"></span>
                    <span class="chip-rate">$${svc.rate}</span>
                </div>
            `;
            });

            return `
            <div class="expanded-inner">
                <div class="inner-label">Configured Services & Rates</div>
                <div class="svc-chips">
                    ${chipsHtml}
                </div>
            </div>
        `;
        }

        $(document).ready(function() {
            var fields = '<?php echo json_encode($columns);?>';
            fields = JSON.parse(fields);
            var columns = [];

            columns.push({
                className: 'dt-control',
                orderable: false,
                data: null,
                defaultContent: '<i class="fa fa-chevron-right expand-icon" aria-hidden="true"></i>',
                width: '5%'
            });

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
                    data:null,
                    ordarable:false,
                    width: '10%',
                    render: function (data, type, row) {
                        return row.services.length;
                    }
                });
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
                            actionButton += `<a href="{{ url('editpayerrates') }}/${data.id}"  title="Edit" class="edit-it ml-1"><i class="fa fa-edit" aria-hidden="true"></i></a>`;
                        @endcanPermission
                                @canPermission(['payerrates.approve'])
                        if (data.status == 0 || data.pending_data != null) {
                            actionButton += '<a href="javascript:void(0)" data-id="'+data.id+'" title="Approve Payer Rate" class="approve-it ml-1"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
                            actionButton += '<a href="javascript:void(0)" data-id="'+data.id+'" title="Deny Payer Rate" class="deny-it ml-1"><i class="fa fa-ban" aria-hidden="true"></i></a>';
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
                        [2, "asc"]
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

                $('#dataTable tbody').on('click', 'tr', function (e) {
                    if ($(this).hasClass('expanded-row')) return;

                    if ($(e.target).closest('a, button, input').length) return;

                    var tr = $(this);
                    var row = table.row( tr );
                    var icon = tr.find('.expand-icon');

                    if (!row.length) return;

                    if ( row.child.isShown() ) {
                        row.child.hide();
                        tr.removeClass('shown');
                        icon.removeClass('open');
                    }
                    else {
                        table.rows('.shown').every(function() {
                            this.child.hide();
                            $(this.node()).removeClass('shown');
                            $(this.node()).find('.expand-icon').removeClass('open');
                        });

                        row.child( formatExpandedData(row.data()) ).show();
                        tr.addClass('shown');
                        icon.addClass('open');

                        $(row.node()).next().addClass('expanded-row');
                    }
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
