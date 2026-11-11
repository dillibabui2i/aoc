<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\FieldEvaluation;
$branchObject = H::getHelper("ConstantHelper")->getFacilities();
$years = FieldEvaluation::select(DB::raw('YEAR(field_evaluations.created_at) as evaluation_year'))
    ->distinct()
    ->pluck('evaluation_year')
    ->toArray();
$yearOptions = [];
if (count($years) > 0) {
    foreach ($years as $year) {
        $yearOptions[] = ["value" => $year, 'text' => $year];
    }
}

$branches  = [];
foreach($branchObject as $br) {
    $branches[] = ["value"=>$br->id,'text'=>$br->FacilityName];
}
if(LD::checkFeatureFlag('add-year-filter-evaluations-pages')){
    $filters = [
        [
            'label' => 'Include Signed',
            'name' => 'signed',
            'type' => 'checkbox',
            'value' => false
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
            'source' => $branches
        ],
        [
            'label' => 'Discipline',
            'name' => 'discipline',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getDiscipline()
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
            'source' => H::getHelper('ConstantHelper')->getFieldEvaluationStatus()
        ]
    ];
}else{
    $filters = [
        [
            'label' => 'Include Signed',
            'name' => 'signed',
            'type' => 'checkbox',
            'value' => false
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
            'source' => $branches
        ],
        [
            'label' => 'Discipline',
            'name' => 'discipline',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getDiscipline()
        ],
        [
            'label' => 'Status',
            'name' => 'status',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getFieldEvaluationStatus()
        ]
    ];
}
?>
@extends('layouts.myaccount')
@section('title', 'Field Evaluations')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Field Evaluations</h1>
            <a title="Export" href="javascript:void(0)" id="exportFieldEvaluationData"><i class="fa fa-download"></i></a>
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

@if(LD::checkFeatureFlag('add-delete-reason-field-evaluation'))
<x-modal>
    <x-slot name="id">fieldEvaluationDelPopUp</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <h5 id="confirmationDialogMessage">Are you sure want to delete this evaluation?</h5>
            <form class="col-md-12 p-0" id="deletefieldEvaluation" name="deletefieldEvaluation">
                {!! csrf_field() !!}
                <input type="hidden" name="id" id="id" value="" />
                <div class="col-md-12 p-0">
                    <label for="note">Reason:</label>
                    <textarea name="reason" id="reason" class="form-control"></textarea>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" type="submit" form="deletefieldEvaluation">Yes</button>
        <button class="btn btn-primary" onclick="closeFieldEvaluation()">No</button>
    </x-slot>
</x-modal>
@endif

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
        if (row['status'] == 'Generated' || row['status'] == 'Edited') {
            action += '<a href="javascript:void(0)" onclick=\'editFieldEvaluation("' + row['id'] + '")\' title="Edit Evaluation"><i class="fa fa-pencil" aria-hidden="true"></i></a>';
        }
        if (row['document_filename'] != "" && row['document_filename'] != null) {
            action += '<a href="javascript:void(0)" onclick="window.location.href=\'<?php echo route('rightSignature.documentDownload').'?documentFileName='; ?>'+row['document_filename']+'\'" title="Evaluation PDF"><i class="fa fa-eye" aria-hidden="true"></i></a>';
        }
        action += '<a href="javascript:void(0)" onclick=\'getClinicianProfile("' + row['aoc_provider_id'] + '")\' title="Clinician"><i class="fa fa-user" aria-hidden="true"></i></a>';
        @canPermission(['delete_evaluations'])
            if (row['document_filename'] == "" || row['document_filename'] == null) {
                @if(LD::checkFeatureFlag('add-delete-reason-field-evaluation'))
                    action += '<a href="javascript:void(0)" title="Delete Evaluation" class="delete-it"';
                    action += 'data-id="'+row['id']+'">';
                    action += '<i class="fa fa-trash-o" aria-hidden="true"></i></a>';
                @else
                    action += '<a href="javascript:void(0)" title="Delete Evaluation" class="delete-it"';
                    action += 'data-href="{{route('fieldevaluation.delete')}}?id='+row['id']+'">';
                    action += '<i class="fa fa-trash-o" aria-hidden="true"></i></a>';
                @endif
            }
        @endcanPermission
        return action;
    }
});
@if(LD::checkFeatureFlag('add-delete-reason-field-evaluation'))
$(document).ready(function($) {
    $('#deletefieldEvaluation').validate({
        rules: {
            reason: {
                required: true
            }
        },
        errorPlacement: function(error, element) {
        },
        submitHandler: function(form) {
            $.ajax({
                url: '{{route('fieldevaluation.delete')}}',
                type: "GET",
                data: $('#deletefieldEvaluation').serialize(),
                success: function(response) {
                    $('#fieldEvaluationDelPopUp').modal('hide');
                    if (response.status) {
                        $('#dataTable').DataTable().ajax.reload();
                    }
                },
            });
        }
    });
});
function closeFieldEvaluation() {
    $('#fieldEvaluationDelPopUp').modal('hide');
}
@endif
$(document).ready(function($) {
    var dataTableDelay = 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [4, "desc"]
        ],
        "ajax": {
            "url": "{{ route('fieldevaluation.list') }}",
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
                @if(LD::checkFeatureFlag('add-delete-reason-field-evaluation'))
                    $("#tempModalCnt").remove();
                    var $div = $('<div />').appendTo('body');
                    $div.attr('id', 'tempModalCnt');
                    $('#fieldEvaluationDelPopUp').modal('show');
                    $('#fieldEvaluationDelPopUp #id').val($(this).attr('data-id'));
                    $('#fieldEvaluationDelPopUp #reason').removeClass('error').val('');
                @else
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
                @endif
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

    $("#exportFieldEvaluationData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        var url = "{{route('fieldevaluation.exportData')}}" + '?' + $.param(data);
        window.location.href = url;

    });
});

function editFieldEvaluation(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('fieldevaluation.edit')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            id: id
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#fieldEvaluationFormModal').modal('show');
        }
    });
}
</script>

@endsection
