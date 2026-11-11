<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\OfficeEvaluation;
$isReadOnlyMode = true;

if(H::canPermissions(['officeevaluation.list'])) {
    $isReadOnlyMode = false;
}
$years = OfficeEvaluation::select(DB::raw('YEAR(office_evaluations.created_at) as evaluation_year'))
    ->distinct()
    ->pluck('evaluation_year')
    ->toArray();
$yearOptions = [];
if (count($years) > 0) {
    foreach ($years as $year) {
        $yearOptions[] = ["value" => $year, 'text' => $year];
    }
}

if(LD::checkFeatureFlag('add-year-filter-evaluations-pages')){
    $filters = [
        [
            'label'=>'Me Mode',
            'name'=>'default',
            'type'=>'checkbox',
            'value'=>true,
            'readonly'=>$isReadOnlyMode
        ],
        [
            'label' => 'State',
            'name' => 'state',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label' => 'Region',
            'name' => 'region',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label' => 'Branch',
            'name' => 'branch',
            'type' => 'multiselect',
            'source' => []
        ],
        [
            'label' => 'Supervisor',
            'name' => 'supervisor',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getOfficeEvaluationSupervisor()
        ],
        [
            'label' => 'Manager',
            'name' => 'manager',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getOfficeEvaluationManagers()
        ],
        [
            'label' => 'Year',
            'name' => 'evaluation_year',
            'type' => 'multiselect',
            'source' => $yearOptions
        ],
        [
            'label' => 'Status',
            'name' => 'status',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getOfficeEvaluationStatus()
        ]
    ];
}else{
    $filters = [
        [
            'label'=>'Me Mode',
            'name'=>'default',
            'type'=>'checkbox',
            'value'=>true,
            'readonly'=>$isReadOnlyMode
        ],
        [
            'label' => 'State',
            'name' => 'state',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label' => 'Region',
            'name' => 'region',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label' => 'Branch',
            'name' => 'branch',
            'type' => 'multiselect',
            'source' => []
        ],
        [
            'label' => 'Supervisor',
            'name' => 'supervisor',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getOfficeEvaluationSupervisor()
        ],
        [
            'label' => 'Manager',
            'name' => 'manager',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getOfficeEvaluationManagers()
        ],
        [
            'label' => 'Status',
            'name' => 'status',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getOfficeEvaluationStatus()
        ]
    ];
}
?>
@extends('layouts.myaccount')
@section('title', 'Office Evaluations')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Office Evaluations</h1>
            <a title="Export" href="javascript:void(0)" id="exportOfficeEvaluationData"><i class="fa fa-download"></i></a>
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
columns.push({
    data: null,
    width: '5%',
    sClass:'dt-nowrap',
    searchable: false,
    orderable: false,
    render: function(data, type, row) {
        var action = '';

        action+='<a href="javascript:void(0)" title="View Evaluation" onclick=\'editOfficeEvaluation("' + row['id'] + '","view")\'><i class="fa fa-eye" aria-hidden="true"></i></a>';
        if (row['is_supervisor'] && (row['status']!='Employee Signed' && row['status']!='Pending Employee Signature')) {
            action += '<a href="javascript:void(0)" title="Edit Evaluation" onclick=\'editOfficeEvaluation("' + row['id'] + '")\' ><i class="fa fa-pencil" aria-hidden="true"></i></a>';
        }
        action += '<a href="javascript:void(0)" title="View Profile" onclick=\'getClinicianProfile("' + row['aoc_provider_id'] + '")\' ><i class="fa fa-user" aria-hidden="true"></i></a>';
        if (row['selfid'] != "") {
            action += '<a href="javascript:void(0)" title="View Self-evaluation" onclick=\'selfEvaluation("' + row['selfid'] + '")\' ><i class="fa fa-address-book-o" aria-hidden="true"></i></a>';
        }
        @canPermission(['delete_evaluations'])
            if (row['document_filename'] == "" || row['document_filename'] == null) {
                action += '<a href="javascript:void(0)" title="Delete" class="delete-it" data-href="{{route('officeevaluation.delete')}}?id='+row['id']+'"><i class="fa fa-trash-o" aria-hidden="true"></i></a>';
            }
        @endcanPermission
        return action;
    }
});
$(document).ready(function($) {
    var dataTableDelay = 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [3, "desc"]
        ],
        "ajax": {
            "url": "{{ route('officeevaluation.list') }}",
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
        "drawCallback": function(settings) {
            $(".delete-it").on('click', function() {
                if (confirm("Are you sure want to delete this evaluation?")) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: $(this).attr('data-href'),
                        type: "GET",
                        data: {
                            _token: _token
                        },
                        success: function(response) {
                            if (response.status) {
                                $('#dataTable').DataTable().ajax.reload();
                            }
                        },
                    });
                }
                return false;
            });
        },
        "columns": columns
    });

    $(document).on("change",'#state',function() {
        if ($(this).val().indexOf('TX') !== -1) {
            $('select[name="region"]').multiselect('enable');
        } else {
            $('select[name="region"]').multiselect('deselectAll').multiselect('disable');
        }
        $("#branch").multiselect("clearSelection");
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
                $('#branch').empty();
                $.each(response, function( index, value ) {
                    $("#branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                });
                $("#branch").multiselect("rebuild");
            },
        });
    });

    $('.clearall').click(function() {
        $('select[name="region"]').multiselect('disable');
    });

    $("#exportOfficeEvaluationData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        var url = "{{route('officeevaluation.exportData')}}" + '?' + $.param(data);
        window.location.href = url;

    });
});


$(document).on("click",'#default',function(){
    if($(this).is(":checked")) {
        $("#state").multiselect("clearSelection");
        $("#region").multiselect("clearSelection");
        $("#branch").multiselect("clearSelection");
        $("#supervisor").multiselect("clearSelection");
        $("#manager").multiselect("clearSelection");
        $("#status").multiselect("clearSelection");
        $('#region').multiselect('disable');
    }
});
$(document).on("change",'#state, #region, #branch, #supervisor, #manager, #status',function(){
        $('#default').prop('checked', false);
});
</script>

@endsection
