<?php
use App\Helpers\LD;
use App\Helpers\H;
$recruiters = H::getHelper("ConstantHelper")->getRecruitersList(false);
$currentSteps = H::getHelper("ConstantHelper")->getCurrentSteps();
$clientCustomFields = H::getHelper('ConstantHelper')->getCustomFields("client");
foreach($states as &$v){
    $v = ['value'=>$v, 'text'=>$v];
}
$filters = [];
if (LD::checkFeatureFlag('nth-meeting-nurses-filter')) {
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
            'label'=>'Intake Coordinator',
            'name'=>'IntakeCoordinator',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getIntakeCoordinator()
        ],
        [
            'label'=>'Client Care Coordinator',
            'name'=>'ClientCareCoordinator',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],
        [
            'label' => 'Recruiter',
            'name' => 'recruiter',
            'type' => 'multiselect',
            'source' => $recruiters
        ],
        [
            'label' => 'Meeting Nurses',
            'name' => 'meetingNurses',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getMeetingNurses()
        ],
        [
            'label'=>'Intake',
            'name'=>'is_intake',
            'type'=>'select',
            'source'=>[['text'=>'True','value'=>'true'],['text'=>'False','value'=>'false']]
        ],
    ];
} else {
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
            'label'=>'Intake Coordinator',
            'name'=>'IntakeCoordinator',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getIntakeCoordinator()
        ],
        [
            'label'=>'Client Care Coordinator',
            'name'=>'ClientCareCoordinator',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],
        [
            'label' => 'Recruiter',
            'name' => 'recruiter',
            'type' => 'multiselect',
            'source' => $recruiters
        ],
        [
            'label'=>'Intake',
            'name'=>'is_intake',
            'type'=>'select',
            'source'=>[['text'=>'True','value'=>'true'],['text'=>'False','value'=>'false']]
        ],
    ];
}
if (LD::checkFeatureFlag('payer-filters-on-nth-listing')) {
    $filters[] = [
            'label'=>'Payer',
            'name'=>'nth-payer',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPayers()
    ];
    $filters[] = [
            'label' => 'Special Payer Group',
            'name' => 'payer_group',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ];
}
if (LD::checkFeatureFlag('NTH-add-acquisition-column-filter')) {   
    $filters[] = [
        'label'=>'Acquisiton',
        'name'=>'acquisiton',
        'type'=>'select',
        'source'=>[['text'=>'Yes','value'=>'1'],['text'=>'No','value'=>'0']]
    ];
}
$filters[] = [
    'label'=>'Is Priority',
    'name'=>'IsPriority',
    'type'=>'checkbox',
    'value'=>false
];
$filters[] = [
    'label'=>'Show All',
    'name'=>'showAll',
    'type'=>'checkbox',
    'value'=>false
];
if (LD::checkFeatureFlag('add-me-mode-client-nth-list-page')) {
    array_unshift($filters, [
        'label' => 'Me Mode',
        'name' => 'default',
        'type' => 'checkbox',
        'value' => true
    ]);
}
?>
@extends('layouts.myaccount')
@section('title', 'Need To Hire')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Need To Hire</h1>

            @if(LD::checkFeatureFlag('export-custom-field-clients'))
                <a title="Export" href="javascript:void(0)" id="exportNthData_popup"><i class="fa fa-download"></i></a>
            @else
                <a title="Export" href="javascript:void(0)" id="exportNthData"><i class="fa fa-download"></i></a>
            @endif
                
            @canAnyPermission(['nth.addNTHForm'])
            <a href="javascript:void(0);" onclick="addNTHForm('');" ><i class="fa fa-plus-circle"></i></a>
            @endcanPermission
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
            <div class="row form-group">
                <div class="col-md-8 d-flex">
                    <div class="col-md-2 d-flex p-0">
                        <input onclick="window.location.href = '<?php echo route('clientlisting') ?>'" class="form-control w-15" type="radio" name="changePageNTHClient" value="client" />
                        <strong class="align-self-center pl-3"> All Clients </strong>
                    </div>
                    <div class="col-md-2 d-flex p-0">
                        <input class="form-control w-15" type="radio" name="changePageNTHClient" value="nth" checked="checked" />
                        <strong class="align-self-center pl-3">NTH</strong>
                    </div>
                </div>
            </div>
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
 $('#datatableFilterSearchForm #nth-payer').multiselect({includeSelectAllOption: true,enableCaseInsensitiveFiltering: true});
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
        width:'170px',
        className: 'action',
        orderable: false,
        render: function(data, type, row) {
            var actionBtn = '';
            @canAnyPermission(['nth.addNTHForm'])
            actionBtn += ' <a href="javascript:void(0)" onclick=\'getClientDetails("' + row['clientId'] + '")\' ><i class="fa fa-user" aria-hidden="true"></i></a>';
            if (row['IsActive'] == 0) {
                actionBtn += ' <a href="javascript:void(0)" class="disabled"><i class="fa fa-calendar ml-2" aria-hidden="true"></i></a>';
                actionBtn += ' <a href="javascript:void(0)" class="disabled"><i class="fa fa-edit ml-2" aria-hidden="true"></i></a>';
            } else {
                actionBtn += ' <a href="javascript:void(0)" onclick=\'openBuildScheduleForm("' + row['nthaccounts_id'] + '")\' ><i class="fa fa-calendar ml-2" aria-hidden="true"></i></a>';
                actionBtn += ' <a href="javascript:void(0)" onclick=\'addNTHForm("' + row['nthaccounts_id'] + '")\' ><i class="fa fa-edit ml-2" aria-hidden="true"></i></a>';
            }
            @endcanPermission
            actionBtn += ' <a href="javascript:void(0)" onclick=\'openNthChecklistDetails("' + row['nthaccounts_id'] + '")\' ><i class="fa fa-eye ml-2" aria-hidden="true"></i></a>';
            return actionBtn;
        }
    });
    var table = $('#dataTable').DataTable({
        "order": [
            [0, "asc"]
        ],
        "ajax": {
            "url": "{{ route('nth.nthList') }}",
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
    $("#exportNthData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        <?php if(LD::checkFeatureFlag('export-custom-field-clients')){ ?>
          data['cf_ids'] = $('#customfieldform')
          .find('input[name="cf_ids[]"]:checked')
          .map(function () {
            return $(this).val();
          })
          .get();
        $("#customfieldExport").modal("hide"); 
        <?php } ?>
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        var url = "{{route('exportNthData')}}" + '?' + $.param(data);
        window.location.href = url;
    });

    $("#exportNthData_popup").click(function(e){
        $("#customfieldExport").modal("show");
    });

    // Select All custom fields
    $(document).on('change', '#select_all_custom_fields', function () {
      const isChecked = $(this).is(':checked');
      $('#customfieldform input[name="cf_ids[]"]').prop('checked', isChecked);
    });

    // Sync Select All when individual checkbox changes
    $(document).on('change', '#customfieldform input[name="cf_ids[]"]', function () {
      const total = $('#customfieldform input[name="cf_ids[]"]').length;
      const checked = $('#customfieldform input[name="cf_ids[]"]:checked').length;

      $('#select_all_custom_fields').prop('checked', total === checked);
    });

    $("#customfieldExport").on('show.bs.modal', function () {
      $('#select_all_custom_fields').prop('checked', false);
      $('#customfieldform input[name="cf_ids[]"]').prop('checked', false);
    });
});
</script>


<div class="modal fade" id="customfieldExport" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog w25" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel">Choose Custom Field Columns</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <form name="customfieldform" id="customfieldform">
            <div class="container_custom">
                <div class="row mb-2">
                    <div class="col-sm">
                        <strong>Select All</strong>
                    </div>
                    <div class="col-sm text-right">
                        <input
                                type="checkbox"
                                class="form-check-input"
                                id="select_all_custom_fields"
                        />
                    </div>
                </div>
                <hr>
                @foreach($clientCustomFields as $customfield)
                    <div class="row">
                        <div class="col-sm">
                            <label for="cf_{{$customfield->id}}">
                                {{$customfield->field_name}}
                            </label>
                        </div>
                        <div class="col-sm text-right">
                            <input
                                    type="checkbox"
                                    name="cf_ids[]"
                                    class="form-check-input"
                                    id="cf_{{$customfield->id}}"
                                    value="{{$customfield->id}}"
                            />
                        </div>
                    </div>
                @endforeach
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
            <button type="button" class="btn btn-primary" id="exportNthData">Export</button>
        </div>
        </div>
    </div>
</div>

@endsection
