<?php
use App\Helpers\LD;
use App\Helpers\H;


    $filters = [
        [
            'label'=>'Me Mode',
            'name'=>'ctmemode',
            'type'=>'checkbox',
            'value'=>true
        ],
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
            'label'=>'Client Care Coordinator',
            'name'=>'ClientCareCoordinator',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getCoverageTrackerFilledBy()
        ],
        [
            'label'=>'Payer',
            'name'=>'Payer',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPayers()
        ],
        [
            'label' => 'Special Payer Group',
            'name' => 'payer_group',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ],
        [
            'label' => 'Date',
            'name' => 'daterange',
            'type' => 'daterange',
            'fromdate' => '',
            'todate' => '',
        ]
    ];
    if(LD::checkFeatureFlag('add-intake-coverage-tracker-show-all-page')) {
        $intakeValue= [['value' =>'Yes', 'text'=> 'Yes'],['value' =>'No', 'text'=> 'No']];
        $filters[] = [
            'label'=>'Intake',
            'name'=>'intake',
            'type'=>'select',
            'source'=>$intakeValue
        ];
    }

    if(LD::checkFeatureFlag('coverage_tracker_replace_filledby_with_createdby')) {
        
        $filters[3] = [
            'label'=>'Client Care Coordinator',
            'name'=>'ClientCareCoordinator',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getCoverageTrackerCreatedBy()
        ];
    }
?>
@extends('layouts.myaccount')
@section('title', 'Coverage Tracker')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Coverage Tracker</h1>
            <span id="coverage_tracker-counter" style="line-height: 28px; padding: 0px 5px;">(0 Hours)</span>
            <a title="Export" href="javascript:void(0)" id="exportCoverageData"><i class="fa fa-download"></i></a>
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
    @if(LD::checkFeatureFlag('add-delete-icon-coverage-tracker-show-all-page') || LD::checkFeatureFlag('edit-icon-coverage-tracker-show-all-page'))
    if(val.name=='action'){
        columns.push({
            data: null,
            class: 'dt-nowrap',
            render: function(data, type, row) {
                let actionstr = '<a href="javascript:void(0)" onclick=\'getClientDetails("' + row['patient_id'] + '")\' ><i class="fa fa-user" aria-hidden="true"></i></a>';
                @if(LD::checkFeatureFlag('edit-icon-coverage-tracker-show-all-page'))
                actionstr = actionstr+'<a href="javascript:void(0)" onclick=\'editCoverageTrackerRecord("' + row['id'] + '")\' ><i class="fa fa-edit" aria-hidden="true"></i></a>';
                @endif
                actionstr = actionstr+'<a href="javascript:void(0)" onclick=\'showDeleteCoverageTrackerPopup("' + row['id'] + '")\' ><i class="fa fa-trash-o" aria-hidden="true"></i></a>';
                return actionstr;
            }
        });
    }else{
        columns.push({
            data: val.name,
            name: val.name,
            visible: typeof val.visible !== 'undefined' ? val.visible : true
        });
    }
    @else
        columns.push({
            data: val.name,
            name: val.name,
            visible: typeof val.visible !== 'undefined' ? val.visible : true
        });
    @endif
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

    var table = $('#dataTable').DataTable({
        "order": [
            [0, "asc"]
        ],
        "ajax": {
            "url": "{{ route('coverage.list') }}",
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
        "drawCallback": function (oSettings, json) {
            $("#coverage_tracker-counter").html('(' + oSettings.json.counthours + ' Hours)');
        },
        "columns": columns
    });
    $("#exportCoverageData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        var url = "{{route('coverage.exportdata')}}" + '?' + $.param(data);
        window.location.href = url;
    });
});
/*
$(function()
            {
                $.validator.setDefaults({
                    ignore: [],
                    // any other default options and/or rules
                });
                $('#delete_coverage_form').validate(
                {   rules:
                    {
                        reason_category:{ required:true},
                        reason:{ required:true},

                    },

                });

            }
        );
        */
</script>

@endsection
