<?php
use App\Helpers\H;
use App\Helpers\LD;
$lobStatus = H::getHelper("ConstantHelper")->getLobStatus();
$lob  = [];
$excludeLOB = ['PT', 'OT', 'ST'];
foreach($lobStatus as $br) {
    if(!in_array($br->LOBName, $excludeLOB)){
        $lob[] = ["value"=>$br->LOBID,'text'=>$br->LOBName];
    }
}
$excludeDiscipline = ["PT", "PTA", "OT", "OTA", "ST", "STA"];
$discipline = H::filterArrayByValues(H::getHelper('ConstantHelper')->getDiscipline(), $excludeDiscipline);
$filters = [
    [
        'label'=>'Me Mode',
        'name'=>'svdefault',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'svstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'svregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label'=>'Branch',
        'name'=>'svbranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label'=>'Clinical Care Manager',
        'name'=>'svclinicalcaremanager',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClinicalCareManager()
    ],
    [
        'label' => 'LOB',
        'name' => 'LOBID',
        'type' => 'multiselect',
        'source' => $lob
    ],
    [
        'label' => 'Discipline',
        'name' => 'discipline',
        'type' => 'multiselect',
        'source' => $discipline
    ],
    [
        'label'=>'Sup Visit Due Date (Next sup visit)',
        'name'=>'visit_date_due',
        'type'=>'dateRangePicker',
        'fromName'=>'fromvisit_date_due',
        'toName'=>'tovisit_date_due',
        'fromdate'=>'',
        'todate'=>'',
    ],
];
?>
@extends('layouts.myaccount')
@section('title', 'Supervisory Visits')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Supervisory Visits</h1>
            <a title="Export" href="javascript:void(0)" id="exportSupVisitData"><i class="fa fa-download"></i></a>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            @component('components.datatable-filters',['filters'=>$filters])
            @endcomponent
            <div class="table-responsive">
                <table class="table table-bordered" id="supervisoryVisitDataTable" aria-describedby="">
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
@include('supervisoryvisits.deletepopup')
<script>
    $(document).ready(function() {
        $(document).on("change",'#svstate',function(){
            var state = $(this).val().join(",");
            if (state.indexOf('TX') !== -1) {
                $('select[name="svregion"]').multiselect('enable');
            } else {
                $('select[name="svregion"]').multiselect('disable');
            }
        });

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
                width: '10%',
                className: "action",
                render: function(data, type, row) {
                    var action = '';
                    if (Boolean(row['completed_at']) && Boolean(row['completed_by'])) {
                        action += '<a title="View Sup Visit" href="javascript:void(0);"\
                            onclick="openSupervisoryVisitForm(\''+row['id']+'\',\
                            \''+row['aoc_provider_id']+'\', \''+row['aoc_patient_id']+'\', \'view\')">\
                            <i class="fa fa-eye"></i></a>';
                    } else {
                        action += '<a title="Document Sup Visit" href="javascript:void(0);"\
                            onclick="openSupervisoryVisitForm(\''+row['id']+'\',\
                            \''+row['aoc_provider_id']+'\', \''+row['aoc_patient_id']+'\', \'edit\')">\
                            <i class="fa fa-check-square-o"></i></a>';
                    }
                    action += '<a title="View Clinician Profile" href="javascript:void(0)" \
                            onclick="getClinicianProfile(\''+row['aoc_provider_id']+'\')" >\
                            <i class="fa fa-user-md" aria-hidden="true"></i></a>\
                            <a title="View Client Profile" href="javascript:void(0);"\
                            onclick="getClientDetails(\''+row['aoc_patient_id']+'\')">\
                            <i class="fa fa-user"></i></a>';
                    <?php if(LD::checkFeatureFlag('add-reason-field-in-supervisory-visit-delete-pop-up')) { ?>
                        <?php if(H::canPermissions('supervisoryVisits.delete')) { ?>
                            action += '<a title="Delete" href="javascript:void(0);"\
                            onclick="supervisoryvisitDeletePopUp(\''+row['id']+'\', \'showall\')">\
                            <i class="fa fa-trash-o"></i></a>';
                        <?php } ?>
                    <?php }else{ ?>
                            <?php if(H::canPermissions('supervisoryVisits.delete')) { ?>
                            action += '<a title="Delete" href="javascript:void(0);"\
                            onclick="supervisoryvisitDeletePopUp(\''+row['id']+'\', \'showall\')">\
                            <i class="fa fa-trash-o"></i></a>';
                        <?php } ?>
                    <?php } ?>
                    return action;
                }
            });
            var table = $('#supervisoryVisitDataTable').DataTable({
                "order": [
                    [2, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('supervisoryVisits.showAll') }}",
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
                "rowCallback": function( row, data ) {
                    console.log( data.days_visit_due);
                    if ( data.days_visit_due > 0) {
                        $(row).addClass('dashboard-older-record-cl-ra');
                    }
                },
                "columns": columns
            });
        });
    });
    $("#exportSupVisitData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        search['value'] = $('#supervisoryVisitDataTable_filter').find('input').val();
        data['search'] = search;
        var url = "{{route('exportSupervisoryVisitsData')}}" + '?' + $.param(data);
        window.location.href = url;
    });
</script>

@endsection
