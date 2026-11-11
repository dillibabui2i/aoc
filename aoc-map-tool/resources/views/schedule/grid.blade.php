<?php
use App\Helpers\LD;
use App\Helpers\H;

$filters = [
    [
        'label'=>'Show All',
        'name'=>'show_all',
        'type'=>'checkbox',
        'value'=>false
    ],[
        'label'=>'Appointment Date From',
        'name'=>'appointment_date_from',
        'type'=>'date',
        'value'=>''
    ],
    [
        'label'=>'Appointment Date To',
        'name'=>'appointment_date_to',
        'type'=>'date',
        'value'=>''
    ],[
        'label'=>'Schedule Type',
        'name'=>'schedule_type',
        'type'=>'select',
        'source'=>$scheduleType
    ],[
        'label'=>'Branch',
        'name'=>'branch',
        'type'=>'select',
        'source'=>$branches
    ]

];
if(LD::checkFeatureFlag('change-branch-filter-as-per-event-filter-in-schedule-page')){
    $filters[] = [
        'label'=>'Branch',
        'name'=>'ipea_location',
        'type'=>'select',
        'source'=>$ipeaLocation
    ];
}
?>
@extends('layouts.myaccount')
@section('title', 'Schedules')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Schedules</h1>
            @canPermission('schedules.addEvents')
                <a title="Add Schedule" data-toggle="modal" id='addSchedule' href="javascript:void(0);" onclick="addEvents();"><i class="fa fa-plus-circle"></i></a>
            @endcanPermission
        </div>
    </div>


    <!-- DataTales Example -->
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
                            <th>Id</th>
                            <th>Candidate / Title</th>
                            <th>Client / Description</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Schedule Type</th>
                            <th>Recruiter</th>
                            <th>Branch</th>
                            <th>Created Date/Time</th>
                            <?php if(H::canPermissions('schedules.removeOfficeEvents') || H::canPermissions('schedules.removeSchedule') || H::canPermissions('schedules.addofficeipeaevent') || H::canPermissions('schedules.addtherapyipeaevent')){ ?>
                            <th>Action</th>
                            <?php } ?>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

</div>



<script type="text/javascript">
function removeSchedule(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    if (confirm("Are you sure you want to delete this appointment?")) {
        $.ajax({
            url: "{{route('schedules.removeSchedule')}}",
            type: 'get',
            data: {
                id: id,
                _token: _token
            },
            success: function(data) {
                if (data.status) {
                    $('.dataTable').DataTable().ajax.reload(null, false);
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr, status, error, 'error');
                if (status == 'error') {
                    window.location.href = '{{route("login")}}';
                }
            }
        });
    } else {
        return false;
    }
}

function removeNeoSchedule(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    if (confirm("Are you sure you want to delete this appointment?")) {
        $.ajax({
            url: "{{route('schedules.removeNeoSchedule')}}",
            type: 'get',
            data: {
                id: id,
                _token: _token
            },
            success: function(data) {
                if (data.status) {
                    $('.dataTable').DataTable().ajax.reload(null, false);
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr, status, error, 'error');
                if (status == 'error') {
                    window.location.href = '{{route("login")}}';
                }
            }
        });
    } else {
        return false;
    }
}

function removeOfficeEvents(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    if (confirm("Are you sure you want to delete this event?")) {
        $.ajax({
            url: "{{route('schedules.removeOfficeEvents')}}",
            type: 'get',
            data: {
                id: id,
                _token: _token
            },
            success: function(data) {
                if (data.status) {
                    $('.dataTable').DataTable().ajax.reload(null, false);
                }
            },
            error: function(xhr, status, error) {
                console.log(xhr, status, error, 'error');
                if (status == 'error') {
                    window.location.href = '{{route("login")}}';
                }
            }
        });
    } else {
        return false;
    }
}

$(document).ready(function($) {
    var dataTableDelay = 1000;
    var columns = [{
        data: 'id',
        name: 'id',
        visible: false
    }, {
        data: 'candidate',
        name: 'candidate'
    }, {
        data: 'client',
        name: 'client',
        "defaultContent": ""
    }, {
        data: 'appointment_date',
        name: 'appointment_date'
    }, {
        data: 'appointment_time',
        name: 'appointment_time'
    }, {
        data: 'type',
        name: 'type'
    }, {
        data: 'recruiter',
        name: 'recruiter'
    }, {
        data: 'branch',
        name: 'branch'
    },{
        data: 'created_at',
        name: 'created_at',
    }];
        <?php if(H::canPermissions('schedules.removeOfficeEvents') || H::canPermissions('schedules.removeSchedule') || H::canPermissions('schedules.addofficeipeaevent') || H::canPermissions('schedules.addtherapyipeaevent')){ ?>
            columns.push({
                data: null,
                orderable: false,
                render: function(data, type, row) {
                    var actions = '';
                    if (data.type_id == 999) {
                        <?php if(H::canPermissions('schedules.removeOfficeEvents')){ ?>
                            actions += '<a href="javascript:void(0);" onclick="removeOfficeEvents(' + data.id + ')"><i class="fa fa-trash-o"></i></a>';
                            actions += '<a href="javascript:void(0);" onclick="addEvents(' + data.id + ',\'edit\')"><i class="fa fa-edit"></i></a>';
                        <?php } ?>
                    } else if (data.type_id == 998) {
                        <?php if(H::canPermissions('schedules.addtherapyipeaevent')){ ?>
                            actions += '<a href="javascript:void(0);" onclick="removeNeoSchedule(' + data.id + ')"><i class="fa fa-trash-o"></i></a>';
                            actions += '<a href="javascript:void(0);" onclick="javascript:addTherapyIpeaEvent(' + data.id + ',\'edit\')"><i class="fa fa-edit"></i></a>';
                        <?php } ?>
                    } else if (data.type_id == 997) {
                        <?php if(H::canPermissions('schedules.addofficeipeaevent')){ ?>
                            actions += '<a href="javascript:void(0);" onclick="removeNeoSchedule(' + data.id + ')"><i class="fa fa-trash-o"></i></a>';
                            actions += '<a href="javascript:void(0);" onclick="addOfficeIpeaEvent(' + data.id + ',\'edit\')"><i class="fa fa-edit"></i></a>';
                        <?php } ?>
                    } else if (data.type_id == 996) {
                        <?php if(H::canPermissions('ccrCalendar.removeCcrEvents')){ ?>
                            actions += '<a href="javascript:void(0);" onclick="removeOfficeEvents(' + data.id + ')"><i class="fa fa-trash-o"></i></a>';
                        <?php } ?>
                    } else {
                        <?php if(H::canPermissions('schedules.removeSchedule')){ ?>
                            actions += '<a href="javascript:void(0);" onclick="removeSchedule(' + data.id + ')"><i class="fa fa-trash-o"></i></a>';
                        <?php } ?>
                    }
                    return actions;
                }
            });
        <?php } ?>
    var table = $('#dataTable').DataTable({
        "order": [
            [3, "desc"]
        ],
        "ajax": {
            "url": "{{ route('schedules') }}",
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
@if(LD::checkFeatureFlag('change-branch-filter-as-per-event-filter-in-schedule-page'))
    $(document).ready(function($){
        $("#branch").hide();
        $("#ipea_location").hide();
        $("#schedule_type").change(function() {
            var selectedValue = $(this).val();
            if(selectedValue == 999){
                $("#ipea_location").show();
                $("#branch").hide();
            }else if(selectedValue != 999 && selectedValue != ''){
                $("#ipea_location").hide();
                $("#branch").show();
            }else if(selectedValue == ''){
                $("#branch").hide();
                $("#ipea_location").hide();
            }
        });
        $(".clearall").click(function(){
            $("#branch").hide();
            $("#ipea_location").hide();
        });

    });
@endif
</script>
<!-- /.container-fluid -->
@endsection
