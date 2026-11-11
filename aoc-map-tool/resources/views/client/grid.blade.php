<?php
use App\Helpers\H;
use App\Helpers\LD;

$state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
$branchObject = H::getHelper("ConstantHelper")->getFacilities();
$patientStatus = H::getHelper("ConstantHelper")->getMssqlPatientStatus();
$lobStatus = H::getHelper("ConstantHelper")->getLobStatus();
$clientCustomFields = H::getHelper('ConstantHelper')->getCustomFields("client");

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
        'label' => 'LOB',
        'name' => 'LOBID',
        'type' => 'multiselect',
        'source' => $lob
    ],
    [
        'label' => 'Status',
        'name' => 'Patient_CurrentStatusID',
        'type' => 'multiselect',
        'source' => $status
    ],
    [
        'label'=>'Intake',
        'name'=>'is_intake',
        'type'=>'select',
        'source'=>[['text'=>'True','value'=>'true'],['text'=>'False','value'=>'false']]
    ],
];
if (LD::checkFeatureFlag('payer-filters-on-client-listing')) {
    $filters[] = [
            'label'=>'Payer',
            'name'=>'client-payer',
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
@section('title', 'Client List')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Client List</h1>
            @if(LD::checkFeatureFlag('export-custom-field-clients'))
                @if(LD::checkFeatureFlag('export-client-list'))
                    <a title="Export" href="javascript:void(0)" id="exportClientData_popup"><i class="fa fa-download"></i></a>
                @endif
            @else
                @if(LD::checkFeatureFlag('export-client-list'))
                    <a title="Export" href="javascript:void(0)" id="exportClientData"><i class="fa fa-download"></i></a>
                @endif
            @endif

            @if(LD::checkFeatureFlag('clinical-tab-in-client-profile'))
                <a title="Export Well Child Visit Data" href="javascript:void(0)" id="exportWellChildVisitData" style="margin-top: -4px;margin-left: -3px;"><i class="fa fa-arrow-down"></i></a>
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
            @component('components.datatable-filters',['filters'=>$filters])
            @endcomponent
            <div class="row form-group">
                <div class="col-md-8 d-flex">
                    <div class="col-md-2 d-flex p-0">
                        <input checked="checked" class="form-control w-15" type="radio" name="changePageNTHClient" value="client" />
                        <strong class="align-self-center pl-3"> All Clients </strong>
                    </div>
                    <div class="col-md-2 d-flex p-0">
                        <input onclick="window.location.href = '<?php echo route('nth.nthList') ?>'" class="form-control w-15" type="radio" name="changePageNTHClient" value="nth" />
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
            "url": "{{ route('clientlisting') }}",
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
        <?php if(LD::checkFeatureFlag('export-custom-field-clients')){ ?>
          data['cf_ids'] = $('#customfieldform')
          .find('input[name="cf_ids[]"]:checked')
          .map(function () {
            return $(this).val();
          })
          .get();
        $("#customfieldExport").modal("hide");
        <?php } ?>
        var url = "{{route('exportclientdata')}}" + '?' + $.param(data);
        window.location.href = url;

    });

    $("#exportClientData_popup").click(function(e){
        $("#customfieldExport").modal("show");
    });

    $("#exportWellChildVisitData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        var url = "{{route('exportWellChildVisitData')}}" + '?' + $.param(data);
        window.location.href = url;

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
            <button type="button" class="btn btn-primary" id="exportClientData">Export</button>
        </div>
        </div>
    </div>
</div>
@endsection
