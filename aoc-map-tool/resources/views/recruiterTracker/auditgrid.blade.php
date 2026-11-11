<?php
use App\Helpers\H;

$recruiters = H::getHelper("ConstantHelper")->getRecruiters();
$branchObject = H::getHelper("ConstantHelper")->getFacilities();
$branch  = [];
foreach($branchObject as $br) {
    $branches[] = ["value"=>$br->id,'text'=>$br->FacilityName];
}
$filters = [
    [
        'label' => 'Branch',
        'name' => 'branch',
        'type' => 'select',
        'source' => $branches
    ],
    [
        'label' => 'Recruiter',
        'name' => 'recruiter',
        'type' => 'select',
        'source' => $recruiters
    ],
    [
        'label' => 'Created Date',
        'name' => 'created_date',
        'type' => 'text',
        'value' => H::getFormatedDate(date('Y-m-d',strtotime("-1 days"))),
        'defaultvalue' => H::getFormatedDate(date('Y-m-d',strtotime("-1 days")))
    ],
];
?>
@extends('layouts.myaccount')
@section('title', 'Trackers Audit')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">
                Trackers Audit
                <a title="Export" href="javascript:void(0)" id="exportAuditData"><i class="fa fa-download"></i></a>
            </h1>
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
                orderable: false,
                render: function(data, type, row) {
                    return '<a href="javascript:void(0)" onclick=\'openRecruiterDetails("' + row[
                    'rid'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                }
            });
            var table = $('#dataTable').DataTable({
                "order": [
                    [2, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('trackersAudit') }}",
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

        $("#exportAuditData").click(function(e) {
            var data = {};
            var search = {};
            var order = {};
            let _token = $('meta[name="csrf-token"]').attr('content');
            data['filters'] = $("#datatableFilterSearchForm").serializeArray();
            search['value'] = $('#dataTable_filter').find('input').val();
            data['search'] = search;
            order['column'] = $('#dataTable').dataTable().fnSettings().aaSorting[0][0];
            order['dir'] = $('#dataTable').dataTable().fnSettings().aaSorting[0][1];
            data['order'] = Array(order);
            var url = "{{route('exportTrackersAudit')}}" + '?' + $.param(data);
            window.location.href = url;
        });

        $('#created_date').datepicker({
            format: 'dd/mm/yyyy'
        });
    });
</script>

@endsection
