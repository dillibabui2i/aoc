<?php
use App\Helpers\H;

$filters = [
    [
        'label' => 'Include Completed',
        'name' => 'include_completed',
        'type' => 'checkbox',
        'value' => 0
    ],
    [
        'label' => 'Date',
        'name' => 'daterange',
        'type' => 'daterange',
        'fromdate' => '',
        'todate' => '',
    ],
    [
        'label'=>'State',
        'name'=>'state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label'=>'Discipline',
        'name'=>'discipline',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getSocialMediaDiscipline()
    ],
    [
        'label'=>'Ad Type',
        'name'=>'ad_type',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getAdsType()
    ],
    [
        'label'=>'Category',
        'name'=>'category',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getSocialMediaCategory()
    ]
];
?>
@extends('layouts.myaccount')
@section('title', 'Social Media Spend Tracker')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Social Media Spend Tracker</h1>
            <a title="Export" href="javascript:void(0)" id="exportSocialMediaData"><i class="fa fa-download"></i></a>
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
                width: '10%',
                render: function(data, type, row) {
                    return '<a href="javascript:void(0)" onclick=\'getSocialMediaDetails("' + row[
                            'id'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>' +
                            '<a href="javascript:void(0)" onclick=\'getSocialMediaClosePopup("' + row[
                            'id'] + '")\' ><i class="fa fa-check-square-o" aria-hidden="true"></i></a>';
                }
            });

            var table = $('#dataTable').DataTable({
                "order": [
                    [5, "desc"]
                ],
                "ajax": {
                    "url": "{{ route('socialmedia.getAllSocialMedia') }}",
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

        $("#exportSocialMediaData").click(function(e) {
            var data = {};
            var search = {};
            let _token = $('meta[name="csrf-token"]').attr('content');
            data['filters'] = $("#datatableFilterSearchForm").serializeArray();
            search['value'] = $('#dataTable_filter').find('input').val();
            data['search'] = search;
            var url = "{{route('socialmedia.exportSocialMediaAd')}}" + '?' + $.param(data);
            window.location.href = url;
        });

    });
</script>

@endsection
