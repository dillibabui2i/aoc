<?php
use App\Helpers\H;

$loggedInUser = H::getLoggedInUser();
$ocTaskStatusList  = config('constant')['statusChangeRequestTaskStatus'];
$ocTaskStatus  = [];
foreach($ocTaskStatusList as $br =>$vl) {
    $ocTaskStatus[] = ["value"=>$vl,'text'=>$vl];
}

$userCurrentRole = H::getHelper('ConstantHelper')->getCurrentRolesNameByUserId($loggedInUser['id']);

$filters = [
    [
        'label'=>'Me Mode',
        'name'=>'ocdefault',
        'type'=>'checkbox',
        'value'=>true
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
    ],
    [
        'label'=>'New Status',
        'name'=>'ocNewStatus',
        'type'=>'multiselect',
        'source'=>$ocNewStatus
    ],
    [
        'label'=>'Task Status',
        'name'=>'ocTaskStatus',
        'type'=>'multiselect',
        'source'=>$ocTaskStatus
    ]
];
?>
@extends('layouts.myaccount')
@section('title', 'Status change Request')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Status change Request</h1>
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
    var allowedOutcomeForAdvocacy = '<?php echo json_encode(H::getHelper('Advocacy')->allowedTaskStatus())?>';
    allowedOutcomeForAdvocacy = JSON.parse(allowedOutcomeForAdvocacy);
    $(document).ready(function() {
        $(document).on("change",'#ocstate',function(){
            var state = $(this).val().join(",");
            if (state.indexOf('Texas') !== -1) {
                $('select[name="ocregion"]').multiselect('enable');
            } else {
                $('select[name="ocregion"]').multiselect('disable');
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
                    <?php if(in_array('Advocacy Specialist',$userCurrentRole)){?>
                        if(allowedOutcomeForAdvocacy.includes(row['request_outcome'])){
                            action = '<a href="javascript:void(0)" onclick=\'advocacyStatusChangeRequestNextStepForm("'+row[
                            'id']+'");\' ><i class="fa fa-calendar" aria-hidden="true"></i></a> <a href="javascript:void(0)" onclick=\'getClinicianProfile("' + row[
                            'id'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a> <a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes("'+row[
                            'id']+'",\'candidate\',\'Candidate Notes Added\')"><i class="fa  fa-sticky-note-o"></i></a>';
                        }else{
                            action = '<a href="javascript:void(0)" onclick=\'getClinicianProfile("' + row[
                            'id'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a> <a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes("'+row[
                            'id']+'",\'candidate\',\'Candidate Notes Added\')"><i class="fa  fa-sticky-note-o"></i></a>';
                        }
                    <?php }else{ ?>
                        action = '';
                        if (!['Rejected', 'Completed'].includes(row['request_outcome'])) {
                            action += '<a href="javascript:void(0)" onclick=\'showStatusChangeRequestNextStepForm("'+row[
                            'id']+'");\' ><i class="fa fa-calendar" aria-hidden="true"></i></a>';
                        }
                        action += '<a href="javascript:void(0)" onclick=\'getClinicianProfile("' + row[
                        'id'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a> <a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes("'+row[
                        'id']+'",\'candidate\',\'Candidate Notes Added\')"><i class="fa  fa-sticky-note-o"></i></a>';
                    <?php } ?>
                    return action;
                }
            });
            var table = $('#dataTable').DataTable({
                "order": [
                    [10, "desc"]
                ],
                "ajax": {
                    "url": "{{ route('statusChangeRequest.showAll') }}",
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
    });
</script>

@endsection
