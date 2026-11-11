<?php
use App\Helpers\H;
use App\Helpers\LD;

$state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
$employmentStatusList = H::getHelper('ConstantHelper')->getEmploymentStatus();
$employmentStatus = [];
foreach($employmentStatusList as $key => $value) {
    $employmentStatus[] = ['value' => $value->EmploymentStatusID, 'text' => $value->EmploymentStatus];
}
$disciplineList = config('constant')['skillsExperience'];
$discipline = [];
foreach($disciplineList as $key => $value) {
    $discipline[] = ['value' => $value, 'text' => $value];
}
$filters = [
    [
        'label'=>'State',
        'name'=>'State',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Branch',
        'name'=>'Branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Clinical Care Manager',
        'name'=>'clinical_care_manager',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClinicalCareManager()
    ],[
        'label'=>'Clinician Start Date',
        'name'=>'appointment_date',
        'type'=>'dateRangePicker',
        'fromName'=>'fromAppointmentDate',
        'toName'=>'toAppointmentDate',
        'fromdate'=>'',
        'todate'=>'',
    ]

];
?>
@extends('layouts.myaccount')
@section('title', 'CCR')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">CCR</h1>
            <a title="Export" href="javascript:void(0)" id="exportCCRData"><i class="fa fa-download"></i></a>
        </div>
    </div>

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
                width: '5%',
                render: function(data, type, row) {
                    if(row['shift_type'] == 'Coverage'){
                        return '<a href="javascript:void(0)" title = "Document Task" onclick=\'openCCRSNSForm("' + row['id'] + '","coverage")\' ><i class="fa fa-calendar" aria-hidden="true"></i></a> | <a href="javascript:void(0)" title = "View Tracker Details" onclick=\'getDetailsOfCoverageTracker("' + row['coverage_id'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    }
                    else{
                        return '<a href="javascript:void(0)" title = "Document Task" onclick=\'openCCRSNSForm("' + row['id'] + '")\' ><i class="fa fa-calendar" aria-hidden="true"></i></a> | <a href="javascript:void(0)" title = "View Tracker Details" onclick=\'openRecruiterDetails("' + row['tracker_id'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                    }
                    
                }
            });
            var table = $('#dataTable').DataTable({
                "order": [
                    [2, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('ccrListing') }}",
                    data: function(d) {
                        d.filters = $('#datatableFilterSearchForm').serializeArray();
                    }
                },"columnDefs": [
				{ className: "action", "targets": [7] },

			],

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

        $(document).on("change",'#State',function(){
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
                   $('#Branch').empty();
                   $.each(response, function( index, value ) {
                       $("#Branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#Branch").multiselect("rebuild");
               },
           });
        });

        $("#exportCCRData").click(function(e) {
            var data = {};
            var search = {};
            let _token = $('meta[name="csrf-token"]').attr('content');
            data['filters'] = $("#datatableFilterSearchForm").serializeArray();
            search['value'] = $('#dataTable_filter').find('input').val();
            data['search'] = search;
            var url = "{{route('ccr.exportData')}}" + '?' + $.param(data);
            window.location.href = url;
        });
    });
</script>

@endsection
