<?php
use App\Helpers\H;
use App\Helpers\LD;

$status = DB::table('school_setup_requests')
    ->select('status')
    ->distinct()
    ->orderBy('status', 'asc')
    ->get();
$schoolSetupStatus = [];
foreach($status as $v){
    $schoolSetupStatus[] = ['value' => $v->status, 'text' => $v->status];
}

if(LD::checkFeatureFlag('add-status-filter-on-school-set-up-show-all-page')){
    $filters = [
        [
            'label' => 'Include Completed',
            'name' => 'open_school_setup_request_include_completed',
            'type' => 'checkbox',
            'value' => false
        ],
        [
            'label' => 'State',
            'name' => 'open_school_setup_requests_state',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label' => 'Region',
            'name' => 'open_school_setup_requests_region',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label' => 'Branch',
            'name' => 'open_school_setup_requests_aoc_facility_id',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label' => 'Client Care Cordinator',
            'name' => 'open_school_setup_requests_client_care_coordinator',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],
        [
            'label' => 'School Set Up Status',
            'name' => 'school_Set_Up_Status',
            'type' => 'multiselect',
            'source' => $schoolSetupStatus
        ],
        [
            'label' => 'Payer',
            'name' => 'open_school_setup_requests_payer',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayers()
        ],
        [
            'label' => 'Special Payer Group',
            'name' => 'open_school_setup_requests_payer_group',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ],
    ];
}else{
    $filters = [
        [
            'label' => 'Include Completed',
            'name' => 'open_school_setup_request_include_completed',
            'type' => 'checkbox',
            'value' => false
        ],
        [
            'label' => 'State',
            'name' => 'open_school_setup_requests_state',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label' => 'Region',
            'name' => 'open_school_setup_requests_region',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label' => 'Branch',
            'name' => 'open_school_setup_requests_aoc_facility_id',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label' => 'Client Care Cordinator',
            'name' => 'open_school_setup_requests_client_care_coordinator',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],
        [
            'label' => 'Payer',
            'name' => 'open_school_setup_requests_payer',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayers()
        ],
        [
            'label' => 'Special Payer Group',
            'name' => 'open_school_setup_requests_payer_group',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ],
    ];
}

?>
@extends('layouts.myaccount')
@section('title', 'School Set Up Requests')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">School Set Up Requests</h1>
            <a title="Export" href="javascript:void(0)" id="exportSchoolSetUpsData"><i class="fa fa-download"></i></a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @component('components.datatable-filters', ['filters' => $filters])
            @endcomponent
            <div class="table-responsive">
                <table class="table table-bordered" id="schoolSetUpsDataTable" aria-describedby="">
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
    $(document).ready(function () {
        $(document).on("change", '#open_school_setup_requests_state', function () {
            var state = $(this).val().join(",");
            if (state.indexOf('TX') !== -1) {
                $('select[name="open_school_setup_requests_region"]').multiselect('enable');
            } else {
                $('select[name="open_school_setup_requests_region"]').multiselect('disable');
            }
        });

        var fields = '<?php echo json_encode($columns); ?>';
        fields = JSON.parse(fields);
        var columns = [];
        $.each(fields, function (key, val) {
            columns.push({
                data: val.name,
                name: val.name,
                visible: typeof val.visible !== 'undefined' ? val.visible : true,
                width: typeof val.width !== 'undefined' ? val.width : ''
            });
        });
        $(document).ready(function ($) {
            var dataTableDelay = 1000;
            columns.push({
                data: null,
                width: '10%',
                className: "action",
                searchable: false,
                orderable: false,
                render: function(data, type, row) {
                    var action = '';
                    if (row['status'] == 'Open' || row['status'] == 'Started') {
                        action += '<a title="Edit Request" href="javascript:void(0);"\
                            onclick="loadSchoolSetupForm(\''+row['aoc_patient_id']+'\', \''+row['school_setup_id']+'\', \'edit\')">\
                            <i class="fa fa-edit"></i></a>';
                    }
                    if (row['status'] == 'Completed') {
                        action += '<a title="View Request" href="javascript:void(0);"\
                            onclick="loadSchoolSetupForm(\''+row['aoc_patient_id']+'\', \''+row['school_setup_id']+'\', \'view\')">\
                            <i class="fa fa-eye"></i></a>';
                    }
                    action += '<a title="View client profile" href="javascript:void(0);"\
                            onclick="getClientDetails(\''+row['aoc_patient_id']+'\')">\
                            <i class="fa fa-user"></i></a>';
                    if (row['aoc_provider_id'] != '' || row['aoc_provider_id'] != null) {
                        action += '<a title="View clinician profile" href="javascript:void(0)" \
                                onclick="getClinicianProfile(\''+row['aoc_provider_id']+'\')" >\
                                <i class="fa fa-stethoscope" aria-hidden="true"></i></a>';
                    }
                    return action;
                }
            });
            var table = $('#schoolSetUpsDataTable').DataTable({
                "order": [
                    [2, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('schoolsetups.showall') }}",
                    data: function (d) {
                        d.filters = $('#datatableFilterSearchForm').serializeArray();
                    }
                },

                "processing": true,
                "serverSide": true,
                "initComplete": function (settings, json) {
                    jQuery('#dataTable_filter input').off('keyup.DT input.DT');
                    var searchDelay = null;
                    jQuery('#dataTable_filter input').on('keyup', function (e) {
                        var search = jQuery('#dataTable_filter input').val();
                        clearTimeout(searchDelay);
                        searchDelay = setTimeout(function () {
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
    });
    $("#exportSchoolSetUpsData").click(function (e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        search['value'] = $('#schoolSetUpsDataTable_filter').find('input').val();
        data['search'] = search;
        var url = "{{route('schoolsetups.export')}}" + '?' + $.param(data);
        window.location.href = url;
    });
</script>

@endsection
