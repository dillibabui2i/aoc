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
        'label'=>'Reporter',
        'name'=>'reporter',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getReporterList(true)
    ],
    [
        'label'=>'State',
        'name'=>'triagestate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Branch',
        'name'=>'triagebranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Body Systems(s)',
        'name'=>'bodysystems',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getBodySystems()
    ],[
        'label'=>'Follow up Status',
        'name'=>'followup_status',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getFollowupStatus()
    ]
];
if (LD::checkFeatureFlag('date-filter-triage-showall')) {
    $filters[] =
        [
            'label' => 'Date',
            'name' => 'daterange',
            'type' => 'daterange',
            'fromdate' => '',
            'todate' => '',
        ];
}
?>
@extends('layouts.myaccount')
@section('title', 'All Triage Calls')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">All Triage Calls</h1>
            <a title="Export" href="javascript:void(0)" id="exportTriageCallData"><i class="fa fa-download"></i></a>
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
                sClass:'dt-nowrap',
                render: function(data, type, row) {
                    console.log(row.followUpId,'followUpId');
                    var editAction = '';
                    @if(LD::checkFeatureFlag('edit-triage-call'))
                    if(row.followUpId != '' && row.followUpId != null){
                        editAction = '<a href="javascript:void(0)" title="Edit Call" onclick=\'editFollowUp("' + row[
                            'followUpId'] + '")\' ><i class="fa fa-pencil" aria-hidden="true"></i></a>| ';
                    }else{
                        editAction = '<a href="javascript:void(0)" title="Edit Call" onclick=\'editTriageCallForm("' + row[
                            'id'] + '")\' ><i class="fa fa-pencil" aria-hidden="true"></i></a>| ';
                    }
                    @endif
                    @if(LD::checkFeatureFlag('add-outcome-option-on-triage-show-all-page'))
                        editAction += '<a href="javascript:void(0)" title="Document Outcome" onclick=\'getOutcomeDetails("' + row['id'] + '", "' + determineNextStepValue(row) + '")\'><i class="fa fa-calendar" aria-hidden="true"></i></a>| ';
                    @endif
                    return editAction+'<a href="javascript:void(0)" title="Call Details" onclick=\'getTriageCallDetails("' + row[
                            'id'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                }
            });

            var table = $('#dataTable').DataTable({
                "order": [
                    [2, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('triage.getallcalls') }}"+"{{isset($clientId) ? '?clientId='.$clientId : ''}}",
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

        $(document).on("change",'#triagestate',function(){
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
                   $('#triagebranch').empty();
                   $.each(response, function( index, value ) {
                       $("#triagebranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#triagebranch").multiselect("rebuild");
               },
           });
        });
        $("#exportTriageCallData").click(function(e) {
            var data = {};
            var search = {};
            let _token = $('meta[name="csrf-token"]').attr('content');
            data['filters'] = $("#datatableFilterSearchForm").serializeArray();
            search['value'] = $('#dataTable_filter').find('input').val();
            data['search'] = search;
            var url = "{{route('triage.exportTriageCallData')}}" + '?' + $.param(data);
            window.location.href = url;
        });

    });
    function determineNextStepValue(row) {
        const steps = ['step1D', 'step2D', 'step7D', 'step30d'];
        const stepLabels = ['1D', '2D', '7D', '30D'];
        let lastCompletedStepIndex = -1;
        for (let i = 0; i < steps.length; i++) {
            if (row[steps[i]] != null && row[steps[i]] !== '') {
                lastCompletedStepIndex = i;
            }
        }
        if (lastCompletedStepIndex === -1) {
            return stepLabels[0];
        }
        if (lastCompletedStepIndex < steps.length - 1) {
            return stepLabels[lastCompletedStepIndex + 1];
        }
        return stepLabels[3];
    }

</script>

@endsection
