<?php
use App\Helpers\LD;
use App\Helpers\H;
$ocList = [];
foreach (H::getOrientationCoordinatorList() as $key => $value) {
    $ocList[] = ['text'=>$value->name,'value'=>$value->id];
}

    $filters = [
        [
            'label'=>'Me Mode',
            'name'=>'meMode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'state',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label'=>'Region',
            'name'=>'region',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label'=>'Branch',
            'name'=>'aoc_facility_id',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label'=>'Client Care Coordinator',
            'name'=>'ClientCareCoordinator',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getCoverageTrackerFilledBy()
        ],
        [
            'label'=>'Payer',
            'name'=>'Payer',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPayers()
        ],
        [
            'label' => 'Special Payer Group',
            'name' => 'payer_group',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ],
        [
            'label' => 'Include Completed',
            'name' => 'include_completed',
            'type' => 'checkbox',
            'value' => 0
        ],
    ];
?>
@extends('layouts.myaccount')
@section('title', 'Supply Requests')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Supply Requests</h1>
            <a href="javascript:void(0);" onclick="addSuppliesRequest();" ><i class="fa fa-plus-circle"></i></a>
            <a title="Export" href="javascript:void(0)" id="exportSupplyRequestData"><i class="fa fa-download"></i></a>
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
@include('supplies.deletepopup')
<script type="text/javascript">
var fields = '<?php echo json_encode($columns);?>';
fields = JSON.parse(fields);
var columns = [];
$.each(fields, function(key, val) {
    if(val.name == 'items'){
        columns.push({
            data: val.name,
            name: val.name,
            visible: typeof val.visible !== 'undefined' ? val.visible : true,
            render: function(data, type, row) {
                var itemsArr = JSON.parse(data);
                var itemsStr = '<div>';
                $.each(itemsArr, function(k, v) {
                    itemsStr += '<div><strong>'+v['item']+'</strong>: '+v['qty']+' <p class="pl-2">'+(v['description'] != null ? v['description'] : '')+'</p></div>';
                });
                return itemsStr;
            }
        });
    }else{
        columns.push({
            data: val.name,
            name: val.name,
            visible: typeof val.visible !== 'undefined' ? val.visible : true
        });
    }
});
$(document).ready(function($) {
    var dataTableDelay = 1000;
    columns.push({
        data: null,
        width:'170px',
        className: 'dt-nowrap',
        orderable: false,
        render: function(data, type, row) {
            var actionBtn = '';
            if(row['statusNum']!='2'){
                actionBtn += '<a href="javascript:void(0)" onclick=\'changeOutcomeToSupplyRequests("'+row['id']+'")\'><i class="fa fa-calendar" aria-hidden="true"></i></a>| ';
            }
            if(row['statusNum']=='0'){
                actionBtn += '<a href="javascript:void(0)" onclick=\'editSupplyRequests("'+row['id']+'")\'><i class="fa fa-pencil" aria-hidden="true"></i></a>| ';
            }
            actionBtn += '<a href="javascript:void(0)" onclick=\'getSupplyRequestsDetails("' + row['id'] + '")\' ><i class="fa fa-eye ml-2" aria-hidden="true"></i></a>';

            actionBtn += '| <a href="javascript:void(0)" onclick=\'getClientDetails("' + row['client_id'] + '")\' >';
            actionBtn += '<i class="fa fa-user ml-2" aria-hidden="true"></i></a>';
            if(row['statusNum']=='0'){
                actionBtn += '| <a href="javascript:void(0)" onclick=\'supplyReqDelete("'+row['id']+'","showall")\'>';
                actionBtn +=  '<i class="fa fa-trash-o" aria-hidden="true"></i></a>';
            }
            return actionBtn;
        }
    });
    var table = $('#dataTable').DataTable({
        "order": [
            [0, "asc"]
        ],
        "ajax": {
            "url": "{{ route('supplies.list') }}",
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
$(document).on("change",'#state',function(){
        var state = $(this).val().join(",");
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{ route('getstatebranch') }}",
            type: "GET",
            data: {
                state: state,
                _token: _token
            },
            success: function(response) {
                $('#aoc_facility_id').empty();
                $.each(response, function( index, value ) {
                    $("#aoc_facility_id").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                });
                        $("#aoc_facility_id").multiselect("rebuild");
            },
        });
        if ($(this).val().indexOf('TX') !== -1) {
            $('select[name="region"]').multiselect('enable');
        } else {
            $('select[name="region"]').multiselect('disable');
        }
    });
    $("#exportSupplyRequestData").click(function(e) {
            var data = {};
            var search = {};
            let _token = $('meta[name="csrf-token"]').attr('content');
            data['filters'] = $("#datatableFilterSearchForm").serializeArray();
            search['value'] = $('#dataTable_filter').find('input').val();
            data['search'] = search;
            var url = "{{route('supplies.exportSupplies')}}" + '?' + $.param(data);
            window.location.href = url;
        });
</script>

@endsection
