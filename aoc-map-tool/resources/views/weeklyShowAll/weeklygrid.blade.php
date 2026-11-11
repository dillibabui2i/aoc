<?php
use App\Helpers\H;
use App\Helpers\LD;

$lobName = H::getHelper('ConstantHelper')->getLob();
$loboption = [];
foreach($lobName as $lob){
    $loboption[] = ['value' => $lob->LOBID, 'text' =>$lob->LOBName];
}
if(LD::checkFeatureFlag('add-lob-filter-in-ccc-follow-up-calls'))  {
    $weeklyFilter = [
        [
            'label'=>'Me Mode',
            'name'=>'weeklymemode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'weeklyccstate',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label'=>'Region',
            'name'=>'weeklyregion',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label'=>'Branch',
            'name'=>'weeklyccbranch',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label'=>'Client Care Coordinator',
            'name'=>'weeklyclientcare',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],
        [
            'label'=>'LOB',
            'name'=>'weeklyLOB',
            'type'=>'multiselect',
            'source' => $loboption
        ],
        [
            'label'=>'Payer',
            'name'=>'weeklypayer',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPayers()
        ],
        [
            'label' => 'Special Payer Group',
            'name' => 'weeklypayergroup',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ],
    ];
} else {
    $weeklyFilter = [
        [
            'label'=>'Me Mode',
            'name'=>'weeklymemode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'weeklyccstate',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label'=>'Region',
            'name'=>'weeklyregion',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label'=>'Branch',
            'name'=>'weeklyccbranch',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label'=>'Client Care Coordinator',
            'name'=>'weeklyclientcare',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],
        [
            'label'=>'Payer',
            'name'=>'weeklypayer',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPayers()
        ],
        [
            'label' => 'Special Payer Group',
            'name' => 'weeklypayergroup',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ],
    ];
}

?>

@extends('layouts.myaccount')
@section('title', 'Weekly Call')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">
    <!-- DataTales Example -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Weekly Call</h1>
            <a title="Export" href="javascript:void(0)" id="WeeklyShowAll"><i class="fa fa-download"></i></a>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            @component('components.datatable-filters',['filters'=>$weeklyFilter])
            @endcomponent
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" aria-describedby="true">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>ClientID</th>
                            <th>Client Care Coordinator</th>
                            <th>Priority</th>
                            <th>Branch</th>
                            <th>Payer</th>
                            <th>Service Needed</th>
                            <th>Date Added</th>
                            <th>Hours on NTH</th>
                            <th>Outcome</th>
                            <th>Last Updated</th>
                            <th>Last Call Recap</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<script type="text/javascript">
$(document).ready(function($) {
    var dataTableDelay= 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [1, "desc"]
        ],
        "ajax": {
            "url": "{{ route('weeklyShowAllAjax') }}",
            data: function(d) {
                d.filters = $('#datatableFilterSearchForm').serializeArray();
            }
        },
        "processing": true,
        "serverSide": true,
        "columns": [{
                "data": "patient_name"
            },
            {
                "data": "PatientAccount"
            },
            {
                "data": "Client_Coordinator"
            },
            {
                "data": "IsPriority"
            },
            {
                "data": "FacilityName"
            },
            {
                "data": "Payer"
            },
            {
                "data": "CF_ServicesNeeded"
            },
            {
                "data": "created_at"
            },
            {
                "data": "totalhours"
            },
            {
                "data": "outcome"
            },
            {
                "data": "last_weekly_followup"
            },
            {
                data: 'last_recap',
                name: 'last_recap',
                orderable: false,
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
        },
        "initComplete": function(settings, json) {
            jQuery('#dataTable_filter input').off('keyup.DT input.DT');
            var searchDelay = null;
            jQuery('#dataTable_filter input').on('keyup', function(e) {
                var search = jQuery('#dataTable_filter input').val();
                clearTimeout(searchDelay);
                searchDelay = setTimeout(function() {
                    if (search != null && search.length >=3)
                        table.search(search).draw();
                    if (search.length==0 && e.keyCode==8)
                        table.search(search).draw();
                }, dataTableDelay);
            });
        },
    });
});
$("#WeeklyShowAll").click(function(e) {
    var data = {};
    var search = {};
    let _token = $('meta[name="csrf-token"]').attr('content');
    search['value'] = $('#dataTable_filter').find('input').val();
    data['search'] = search;
    data['filters'] = $("#datatableFilterSearchForm").serializeArray();
    var url = "{{route('exportWeeklyShowAll')}}" + '?' + $.param(data);
    window.location.href = url;

});
$(document).on("change",'#weeklyccstate',function(){
        var state = $(this).val().join(",");
        let _token = $('meta[name="csrf-token"]').attr('content');
        if (state.indexOf('TX') !== -1) {
            $('#weeklyregion').multiselect('enable');
        } else {
            $('#weeklyregion').multiselect('disable');
        }
        $.ajax({
        url: "{{ route('getstatebranch') }}",
        type: "GET",
        data: {
            state: state,
            _token: _token
        },
        success: function(response) {
            $('#weeklyccbranch').empty();
            $.each(response, function( index, value ) {
                $("#weeklyccbranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
            });
            $("#weeklyccbranch").multiselect("rebuild");
        },
    });
});
</script>
@endsection