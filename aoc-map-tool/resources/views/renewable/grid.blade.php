<?php
use App\Helpers\LD;
use App\Helpers\H;

$typedata = [
    ['value' => 'office', 'text' => 'Office'],
    ['value' => 'field', 'text' => 'Field'],
    ['value' => 'both', 'text' => 'Both']
];
if(LD::checkFeatureFlag('renewable-multiselect-renewabletype-dropdown')) {
$renewablefilter = [
    [
        'label'=>'Me Mode',
        'name'=>'ocdefault',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'Type',
        'name'=>'renewable_type',
        'type'=>'multiselect',
        'source'=>[
            ['text'=>"All",'value'=>"all"],
            ['text'=>"Completed",'value'=>"completed"],
            ['text'=>"Expired",'value'=>"expired"],
            ['text'=>"Expiring",'value'=>"expiring"],
            ['text'=>"Missing",'value'=>"missing"]
        ]

    ],
    [
        'label'=>'State',
        'name'=>'ocstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ocstate')
    ],[
        'label'=>'Region',
        'name'=>'ocregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'ipea_location_id',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ipea_location_id')
    ],[
        'label'=>'Orientation Coordinator',
        'name'=>'oc',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('oc')
    ],[
        'label'=>'Clinician status',
        'name'=>'renewable_clinicianstatus',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRenewableClinicianStatus()
    ],[
        'label'=>'Renewable Item',
        'name'=>'renewable_items',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRenewableItems()
    ],
    [
        'label'=>'Expiration Date',
        'name'=>'renewable_expiration_date',
        'type'=>'dateRangePicker',
        'fromName'=>'fromrenewable_expiration_date',
        'toName'=>'torenewable_expiration_date',
        'fromdate'=>'',
        'todate'=>'',
    ]

];
} else {
    $renewablefilter = [
    [
        'label'=>'Me Mode',
        'name'=>'ocdefault',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'Type',
        'name'=>'renewable_type',
        'type'=>'select',
        'source'=>[
            ['text'=>"All",'value'=>"all"],
            ['text'=>"Completed",'value'=>"completed"],
            ['text'=>"Expired",'value'=>"expired"],
            ['text'=>"Expiring",'value'=>"expiring"],
            ['text'=>"Missing",'value'=>"missing"]
        ]

    ],
    [
        'label'=>'State',
        'name'=>'ocstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ocstate')
    ],[
        'label'=>'Region',
        'name'=>'ocregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label'=>'Branch',
        'name'=>'ipea_location_id',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('ipea_location_id')
    ],[
        'label'=>'Orientation Coordinator',
        'name'=>'oc',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('oc')
    ],[
        'label'=>'Clinician status',
        'name'=>'renewable_clinicianstatus',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRenewableClinicianStatus()
    ],[
        'label'=>'Renewable Item',
        'name'=>'renewable_items',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRenewableItems()
    ],
    [
        'label'=>'Expiration Date',
        'name'=>'renewable_expiration_date',
        'type'=>'dateRangePicker',
        'fromName'=>'fromrenewable_expiration_date',
        'toName'=>'torenewable_expiration_date',
        'fromdate'=>'',
        'todate'=>'',
    ]

];
}
if(LD::checkFeatureFlag('add-type-field-and-filter-in-renewables-all-bucket')){
    $renewablefilter[] = [
        'label'=>'Type',
        'name'=>'type',
        'type'=>'select',
        'source'=> $typedata
    ];
}
?>
@extends('layouts.myaccount')
@section('title', 'Renewable')
@section('content')

<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Renewable</h1>
            <a title="Export" href="javascript:void(0)" id="exportRenewableData"><i class="fa fa-download"></i></a>
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
            @component('components.datatable-filters', ['filters' => $renewablefilter])
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
$(document).on("change",'#ocstate',function(){
    var state = $(this).val().join(",");
    if (state.indexOf('Texas') !== -1) {
        $('select[name="ocregion"]').multiselect('enable');
    } else {
        $('select[name="ocregion"]').multiselect('disable');
    }
});
$(document).ready(function($) {
    var dataTableDelay = 1000;
    <?php if(!LD::checkFeatureFlag('renewable-multiselect-renewabletype-dropdown')) { ?>
        $('#renewable_type').val('expiring');
    <?php } else { ?>
        $('#renewable_type').val('expiring');
        $("#renewable_type").multiselect("refresh");
    <?php } ?>
    
    columns.push({
        data: null,
        width:'170px',
        className: 'action',
        orderable: false,
        render: function(data, type, row) {
            var t = "missing";
            if(row['days_till_expiration'] > 0 && row['days_till_expiration'] <= 30){
                t = 'thirtyzeroexpiring';
            }else if(row['days_till_expiration'] > 30 && row['days_till_expiration'] <= 90){
                t = 'ninetythirtyexpiring';
            }else{
                t = "missing";
            }
            var action='<a href="javascript:void(0)" title="View clinician profile" onclick="getClinicianProfile('+row['aoc_provider_id']+')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
            action+= '<a href="javascript:void(0)" title="Document task" onclick="showRenewableForm('+row['id']+',\''+t+'\');"><i class="fa fa-calendar" aria-hidden="true"></i></a>';
            action+='<a href="javascript:void(0)"  title="Complete task" onclick="showCompleteRenewableForm('+row['id']+',\''+t+'\');"><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
            action+='<a  href="javascript:void(0);" title="Add note" onclick="openNotes('+row['id']+',\'renewables\',\'Renewable Notes Added\');"><i class="fa  fa-sticky-note-o"></i></a>';

            return action;
        }
    });
    var table = $('#dataTable').DataTable({
        "order": [
            [11, "desc"]
        ],
        "ajax": {
            "url": "{{ route('renewables.showAll') }}",
            data: function(d) {
                d.filters = $('#datatableFilterSearchForm').serializeArray();
            }
        },

        "processing": true,
        "serverSide": true,
        "drawCallback": function(row, data, dataIndex){
            @if (LD::checkFeatureFlag('Add-background-color-does-not-meet-criteria')) 
                let table = this.api();
                table.rows().every(function() {
                    let data = this.data();
                    if (data.last_follow_up !== null) {
                        let today = new Date();
                        let lastFollowUpDate = new Date(data.last_follow_up);
                        if (data.outcome === 'Uploaded to KanTime' && lastFollowUpDate < today) {
                            $(this.node()).addClass("dashboard-renewable-doesnt-match-outcome");
                        }
                    }
                });
            @endif
        },
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
        "columns": columns,
    });
});
$("#exportRenewableData").click(function(e) {
    var data = {};
    var search = {};
    let _token = $('meta[name="csrf-token"]').attr('content');
    data['filters'] = $("#datatableFilterSearchForm").serializeArray();
    search['value'] = $('#dataTable_filter').find('input').val();
    data['search'] = search;
    var url = "{{route('renewables.exportRenewables')}}" + '?' + $.param(data);
    window.location.href = url;
});
</script>

@endsection
