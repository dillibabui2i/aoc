<?php
use App\Helpers\H;

$filters = [
    [
        'label' => 'Me Mode',
        'name' => 'intakestafftransfersdefault',
        'type' => 'checkbox',
        'value' => true
    ],
    [
        'label' => 'State',
        'name' => 'intakestafftransfersstate',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label' => 'Region',
        'name' => 'intakestafftransfersregion',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label' => 'Branch',
        'name' => 'intakestafftransfersbranch',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label' => 'Intake Coordinator',
        'name' => 'intakestafftransferscare',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getIntakeCoordinator()
    ],
    [
        'label' => 'BD Rep',
        'name' => 'intakestafftransfersbdrep',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getBdRep(),
    ],
    [
        'label' => 'Payer',
        'name' => 'intakestafftransfersPayer',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayers()
    ],
    [
        'label' => 'Special Payer Group',
        'name' => 'intakestafftransfersspecialpayergroup',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],
    [
        'label' => 'Referral Date',
        'name' => 'intakestafftransfersreferraldate',
        'type' => 'dateRangePicker',
        'fromName' => 'intakestafftransfersreferraldatefrom',
        'toName' => 'intakestafftransfersreferraldateto',
        'fromdate' => '',
        'todate' => '',
    ]
];
?>
@extends('layouts.myaccount')
@section('title', 'Intake')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Intake</h1>
            <a title="Export" href="javascript:void(0)" id="exportIntakeData"><i class="fa fa-download"></i></a>
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
            @component('components.datatable-filters', ['filters' => $filters])
            @endcomponent
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                <thead>
                    <tr>
                        <?php foreach ($columns as $key => $data) { ?>
                            <th><?php echo $data['label']; unset($columns[$key]['label']); ?></th>
                        <?php }  ?>
                    </tr>
                </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
var columns = [];
var fields = '<?php echo json_encode($columns)?>';
fields = JSON.parse(fields);
$.each(fields, function(key, val) {
    columns.push(val);
});
$(document).ready(function($) {
    var dataTableDelay = 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [4, "desc"]
        ],
        "ajax": {
            "url": "{{ route('intake.list') }}",
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

    $(document).on("change",'#intakestafftransfersstate',function() {
        if ($(this).val().indexOf('TX') !== -1) {
            $('select[name="intakestafftransfersregion"]').multiselect('enable');
        } else {
            $('select[name="intakestafftransfersregion"]').multiselect('deselectAll').multiselect('disable');
        }
        $("#intakestafftransfersbranch").multiselect("clearSelection");
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
                $('#intakestafftransfersbranch').empty();
                $.each(response, function( index, value ) {
                    $("#intakestafftransfersbranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                });
                $("#intakestafftransfersbranch").multiselect("rebuild");
            },
        });
    });

    $('.clearall').click(function() {
        $('select[name="intakestafftransfersregion"]').multiselect('disable');
    });

    $("#exportIntakeData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        search['regex'] = false;
        data['search'] = search;
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        var url = "{{route('intake.export')}}" + '?' + $.param(data);
        window.location.href = url;

    });
});
</script>

@endsection
