<?php
use App\Helpers\H;

$state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
$employmentStatusList = H::getHelper('ConstantHelper')->getEmploymentStatus();
$employmentStatus = [];
foreach($employmentStatusList as $key => $value) {
    $employmentStatus[] = ['value' => $value->EmploymentStatusID, 'text' => $value->EmploymentStatus];
}
$filters = [
    [
        'label' => 'State',
        'name' => 'State',
        'type' => 'select',
        'source' => $state
    ],
    [
        'label' => 'Branch',
        'name' => 'Branch',
        'type' => 'select',
        'source' => []
    ],
    [
        'label' => 'Status',
        'name' => 'Status',
        'type' => 'select',
        'source' => $employmentStatus
    ],
    [
        'label' => 'Discipline',
        'name' => 'Discipline',
        'type' => 'select',
        'source' => H::getHelper('ConstantHelper')->getDiscipline()
    ],

];
?>
@extends('layouts.myaccount')
@section('title', 'Clinician')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Clinician</h1>
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
                render: function(data, type, row) {
                    return '<a href="javascript:void(0)" onclick=\'getClinicianProfile("' + row[
                        'id'] + '")\' ><i class="fa fa-eye" aria-hidden="true"></i></a>';
                }
            });
            var table = $('#dataTable').DataTable({
                "order": [
                    [2, "asc"]
                ],
                "ajax": {
                    "url": "{{ route('clinicianListing') }}",
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

        $(document).on("change", '#State', function() {
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('getstatebranch') }}",
                type: "GET",
                data: {
                    state: $(this).val(),
                    _token: _token
                },
                success: function(response) {
                    $("#Branch").empty().append('<option value="">Select Branch</option>');
                    $.each(response, function(index, value) {
                        $("#Branch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>');
                    });
                },
            });

        });
    });
</script>

@endsection
