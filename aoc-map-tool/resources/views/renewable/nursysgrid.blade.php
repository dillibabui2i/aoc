<?php
use App\Helpers\LD;
use App\Helpers\H;

$columnIndex = array_flip(array_column($columns, 'name'));


$renewablefilter = [
    [
        'label'=>'Me Mode',
        'name'=>'ocdefault',
        'type'=>'checkbox',
        'value'=>true
],
    [
        'label'=>'State',
        'name'=>'ocstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ocstate')
    ],[
        'label'=>'Region',
        'name'=>'ocregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'ipea_location_id',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ipea_location_id')
    ],[
        'label'=>'Orientation Coordinator',
        'name'=>'oc',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('oc')
    ],[
        'label'=>'Clinician status',
        'name'=>'nursys_clinicianstatus',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRenewableClinicianStatus()
    ]

];

?>
@extends('layouts.myaccount')
@section('title', 'Nursys License Mismatches')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Nursys License Mismatches</h1>
            <a title="Export" href="javascript:void(0)" id="exportnursysmismatch"><i class="fa fa-download"></i></a>
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
            @component('components.datatable-filters', ['filters' => $renewablefilter])
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
$(document).on("change",'#ocstate',function(){
    var state = $(this).val().join(",");
    if (state.indexOf('Texas') !== -1) {
        $('select[name="ocregion"]').multiselect('enable');
    } else {
        $('select[name="ocregion"]').multiselect('disable');
    }
});
$(document).ready(function($) {
    var dataTableDelay = 1000;
    
    columns.push({
        data: null,
        width:'170px',
        className: 'action',
        orderable: false,
        render: function(data, type, row) {
            var action='<a href="javascript:void(0)" title="View clinician" onclick="getClinicianProfile('+row['aoc_provider_id']+')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
				action+='<a href="javascript:void(0)" title="Mark as Updated" onclick="showNursysExceptionForm('+row['id']+')"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
				return action;
            return action;
        }
    });
    console.log("outside");
   
    var table = $('#dataTable').DataTable({
        "order": [
            [{{ $columnIndex['license_no'] }}, "desc"]
        ],
        createdRow: function(row, data, dataIndex) {
            if (data.is_overdue) {
                $(row).addClass("dashboard-older-record");
            }
        },
        "ajax": {
            "url": "{{ route('nursys.showAll') }}",
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

$("#exportnursysmismatch").click(function(e) {
    var data = {};
    var search = {};
    let _token = $('meta[name="csrf-token"]').attr('content');
    data['filters'] = $("#datatableFilterSearchForm").serializeArray();
    search['value'] = $('#dataTable_filter').find('input').val();
    data['search'] = search;
    var url = "{{route('nursys.exports')}}" + '?' + $.param(data);
    window.location.href = url;
});
</script>

@endsection
