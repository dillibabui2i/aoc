<?php
use App\Helpers\H;
use App\Helpers\LD;

$branches = H::getHelper('ConstantHelper')->getBranches()->toArray();
$branches = H::arrGroupBy($branches, 'state');
$branchesList = [];
foreach ($branches as $state => $branch) {
    foreach($branch as $key => $value) {
        $branchesList[$state][] = ['value' => $value['id'], 'label' => $value['branch']];
    }
}
$states = H::getHelper('ConstantHelper')->getBranchStates()->toArray();
$stateList = [];
foreach ($states as $key => $state) {
    $stateList[] = ['value' => $state['state'], 'text' => $state['state']];
}
$ocList = H::getOrientationCoordinatorList();
$oc = [];
foreach($ocList as $key => $value) {
    $oc[] = ['value' => $value->branchid, 'text' => $value->name];
}
if (LD::checkFeatureFlag('ipea-multiselect-filter')) {
    $filters = [
        [
            'label' => 'State',
            'name' => 'State',
            'type' => 'multiselect',
            'source' => $stateList
        ],
        [
            'label' => 'Branch',
            'name' => 'Branch',
            'type' => 'multiselect',
            'source' => []
        ],
        [
            'label' => 'OC',
            'name' => 'OC',
            'type' => 'multiselect',
            'source' => $oc
        ],
        [
            'label' => 'Include Completed',
            'name' => 'include_completed',
            'type' => 'checkbox',
            'value' => 0
        ],
    ];
} else {
    $filters = [
        [
            'label' => 'State',
            'name' => 'State',
            'type' => 'select',
            'source' => $stateList
        ],
        [
            'label' => 'Branch',
            'name' => 'Branch',
            'type' => 'select',
            'source' => []
        ],
        [
            'label' => 'OC',
            'name' => 'OC',
            'type' => 'select',
            'source' => $oc
        ],
        [
            'label' => 'Include Completed',
            'name' => 'include_completed',
            'type' => 'checkbox',
            'value' => 0
        ],
    ];
}
?>
@extends('layouts.myaccount')
@section('title', 'IPEAS')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">IPEAS</h1>
            @if(LD::checkFeatureFlag('export-ipea-list'))
                <a title="Export" href="javascript:void(0)" id="exportIPEAData"><i class="fa fa-download"></i></a>
            @endif
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
                        return '<a href="javascript:void(0)" onclick=\'updateNurseIpeaInfo("' + row[
                        'id'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                }
            });
            var table = $('#dataTable').DataTable({
                "order": [
                    [2, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('ipeaListing') }}",
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

        var branches = {!! json_encode($branchesList) !!};
        jQuery(document).on("change", '#State', function() {
            @if (LD::checkFeatureFlag('ipea-multiselect-filter'))
                jQuery("#Branch").empty();
                jQuery.each(jQuery("#State").val(), function(index, value) {
                    jQuery.each(branches[value], function(index, value) {
                        jQuery("#Branch").append('<option value="' + value.value + '">' + value.label + '</option>');
                    });
                });
                jQuery("#Branch").multiselect("rebuild");
            @else
                jQuery("#Branch").empty().append('<option value="">Select Branch</option>');
                jQuery.each(branches[jQuery(this).val()], function(index, value) {
                    jQuery("#Branch").append('<option value="' + value.value + '">' + value.label + '</option>');
                });
            @endif
        });
        @if(LD::checkFeatureFlag('export-ipea-list'))
            $("#exportIPEAData").click(function(e) {
                var data = {};
                var search = {};
                let _token = $('meta[name="csrf-token"]').attr('content');
                data['filters'] = $("#datatableFilterSearchForm").serializeArray();
                search['value'] = $('#dataTable_filter').find('input').val();
                data['search'] = search;
                var url = "{{route('ipea.export')}}" + '?' + $.param(data);
                window.location.href = url;

            });
        @endif
    });
</script>

@endsection
