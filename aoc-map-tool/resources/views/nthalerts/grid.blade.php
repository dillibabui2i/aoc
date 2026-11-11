<?php
use App\Helpers\H;
use App\Helpers\LD;


$nthalertsFilter = [
    [
        'label'=>'State',
        'name'=>'nthlead_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'nthlead_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label'=>'Branch',
        'name'=>'nthlead_branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Recruiter',
        'name'=>'nthlead_recruiter',
        'type'=>'multiselect',
        'source'=>H::getHelper("ConstantHelper")->getRecruiters('id')
    ],
    [
        'label'=>'Category',
        'name'=>'nthlead_category',
        'type'=>'multiselect',
        'source'=>[
            ["value"=>"lead","text"=>"Lead"],
            ["value"=>"inq","text"=>"Inquiry"]
        ]
    ],
    [
        'label' => 'Status',
        'name' => 'nthlead_status',
        'type' => 'multiselect',
        'source' => [
            ["value" => "New", "text" => "New"],
            ["value" => "Pending", "text" => "Pending"],
            ["value" => "Resolved", "text" => "Resolved"],
        ]
    ],
];

if (LD::checkFeatureFlag('add-resolve-outcome-for-nth-leads')) {
    $nthalertsFilter[] = [
        'label' => 'Resolution',
        'name' => 'nthlead_resolution',
        'type' => 'multiselect',
        'source' => H::getHelper("ConstantHelper")->getLeadResolutions()
    ];
}

?>
@extends('layouts.myaccount')
@section('title', 'NTH Alerts')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">NTH Alerts</h1>
            <a title="Export" href="javascript:void(0)" id="exportNTHLeads"><i class="fa fa-download"></i></a>
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
            @component('components.datatable-filters',['filters'=>$nthalertsFilter])
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
    let col = {
        data: val.name,
        name: val.name,
        visible: typeof val.visible !== 'undefined' ? val.visible : true
    };
    if (val.name === 'workflow_step') {
        col.render = function(data, type, row) {
            if (data === "Not Captured (Before Feature)") {
                return '<span title="This field was added as part of a later update. Older records will not contain this data.">' + data + '</span>';
            } else {
                return data;
            }
        };
    }
    columns.push(col);
});
columns.push({
    data: null,
    width: '5%',
    sClass:'dt-nowrap',
    searchable: false,
    orderable: false,
    render: function(data, type, row) {
        var action = '';

        action+='<a href="javascript:void(0);" title="View" onclick="getNthLeadsInquiriesDetail(\''+row['id']+'\')"><i class="fa fa-eye" aria-hidden="true"></i></a> |';

        action+='<a href="javascript:void(0);" title="View Clinician Profile" onclick="getClinicianProfile(\''+row['aoc_provider_id']+'\')"><i class="fa fa-stethoscope" aria-hidden="true"></i></a> | ';

        action+='<a href="javascript:void(0);" title="View client Profile" onclick="getClientDetails(\''+row['aoc_patient_id']+'\')"><i class="fa fa-user" aria-hidden="true"></i></a> | ';

        action+='<a data-bs-dismiss="modal" href="javascript:void(0);" onclick="event.preventDefault();openNotes(\''+row['id']+'\',\'nthalerts\',\'NTH Leads and Inquiries notes added\')"><i class="fa  fa-sticky-note-o"></i></a>';
        return action;
    }
});
$(document).ready(function($) {
    var dataTableDelay = 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [3, "desc"]
        ],
        "ajax": {
            "url": "{{ route('nthalerts.list') }}",
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
                return false;
            });
        },
        "columns": columns
    });

    $(document).on("change",'#nthlead_state',function() {
        if ($(this).val().indexOf('TX') !== -1) {
            $('select[name="nthlead_region"]').multiselect('enable');
        } else {
            $('select[name="nthlead_region"]').multiselect('deselectAll').multiselect('disable');
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
                $('#nthlead_branch').empty();
                $.each(response, function( index, value ) {
                    $("#nthlead_branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                });
                $("#nthlead_branch").multiselect("rebuild");
            },
        });
    });

    $('.clearall').click(function() {
        $('select[name="nthlead_region"]').multiselect('disable');
    });

    $("#exportNTHLeads").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        var url = "{{route('nthalerts.exportData')}}" + '?' + $.param(data);
        window.location.href = url;

    });
});


$(document).on("click",'#default',function(){
    if($(this).is(":checked")) {
        $("#state").multiselect("clearSelection");
        $("#region").multiselect("clearSelection");
        $("#branch").multiselect("clearSelection");
        $("#supervisor").multiselect("clearSelection");
        $("#manager").multiselect("clearSelection");
        $("#status").multiselect("clearSelection");
        $('#region').multiselect('disable');
    }
});
$(document).on("change",'#state, #region, #branch, #supervisor, #manager, #status',function(){
        $('#default').prop('checked', false);
});
</script>

@endsection
