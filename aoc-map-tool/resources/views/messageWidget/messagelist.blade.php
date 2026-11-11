<?php
use App\Helpers\H;
use App\Helpers\LD;

$state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
$branchObject = H::getHelper("ConstantHelper")->getFacilities();
$patientStatus = H::getHelper("ConstantHelper")->getMssqlPatientStatus();
$lobStatus = H::getHelper("ConstantHelper")->getLobStatus();

$status = [];
foreach($patientStatus as $ps){
    $status[] = ["value"=>$ps->id,'text'=>$ps->PatientStatus];
}

$branch  = [];
foreach($branchObject as $br) {
    $branches[] = ["value"=>$br->id,'text'=>$br->FacilityName];
}

$lob  = [];
foreach($lobStatus as $br) {
    $lob[] = ["value"=>$br->LOBID,'text'=>$br->LOBName];
}

$filters = [
    [
        'label'=>'State',
        'name'=>'state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label'=>'Branch',
        'name'=>'aoc_facility_id',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label' => 'Date',
        'name' => 'daterange',
        'type' => 'daterange',
        'fromdate' => '',
        'todate' => '',
    ]
];

?>
@extends('layouts.myaccount')
@section('title', 'Client List')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Client List</h1>
            @if(LD::checkFeatureFlag('export-client-list'))
                <a title="Export" href="javascript:void(0)" id="exportClientData"><i class="fa fa-download"></i></a>
            @endif
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
            @component('components.datatable-filters',['filters'=>$filters]) @endcomponent
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
    });
    var dataTableDelay = 1000;
    columns.push({
        data: null,
        render: function(data, type, row) {

            let detailLink = '';
            @canPermission('client.getDetails')
                detailLink = '<a href="javascript:void(0)" onclick=\'getClientDetails("' + row[
                'id'] + '")\'><i class="fa fa-eye" aria-hidden="true"></i></a>';
            @endcanPermission
            return detailLink;

        }
    });
    var table = $('#dataTable').DataTable({
        "order": [
            [0, "asc"]
        ],
        "ajax": {
            "url": "{{ route('messagecenter.list') }}",
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
    $("#exportClientData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        var url = "{{route('exportclientdata')}}" + '?' + $.param(data);
        window.location.href = url;

    });
});
</script>

@endsection
