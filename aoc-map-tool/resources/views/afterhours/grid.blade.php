<?php
use App\Helpers\H;
use App\Helpers\LD;

$afterHoursFilter = [];
if(LD::checkFeatureFlag('state-branch-filter-manage-after-hours-page'))  {
    $branchObject = H::getHelper('ConstantHelper')->getBranches();
    $branches  = [];
    foreach($branchObject as $br) {
        $branches[] = ["value"=>$br->id,'text'=>$br->branch];
    }
    $stateObject = H::getHelper('ConstantHelper')->getBranchStates();
    $state  = [];
    foreach($stateObject as $sr) {
        $state[] = ["value"=>$sr->state,'text'=>$sr->state];
    }
    $afterHoursFilter = [
        [
            'label'=>'State',
            'name'=>'afterhoursstate',
            'type'=>'multiselect',
            'source'=>$state
        ],
        [
            'label'=>'Branch',
            'name'=>'afterhoursbranch',
            'type'=>'multiselect',
            'source'=>$branches
        ]
    ];
}
?>
@extends('layouts.myaccount')
@section('title', 'Manage After Hours')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage After Hours</h1>
            <a title="Add Shift" href="{{ route('manageafterhours.add') }}"><i class="fa fa-plus-circle"></i></a>
        </div>
        <!-- <div class="form-group col-md-2 text-right">
<a href="{{ route('manageusers.add') }}" class="btn btn-primary">Add Users</a>
</div> -->
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
            @if(LD::checkFeatureFlag('state-branch-filter-manage-after-hours-page'))
                @component('components.datatable-filters',['filters'=>$afterHoursFilter])@endcomponent
            @endif
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                    <thead>
                        <tr>
                            <th>Title</th>
                            @if (LD::checkFeatureFlag('add-columns-to-manage-after-hours-page'))
                            <th>State</th>
                            <th>Branch</th>
                            @endif
                            <th>Role</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Repeat</th>
                            @if (LD::checkFeatureFlag('add-columns-to-manage-after-hours-page'))
                            <th>Created By</th>
                            @endif
                            <th>Action</th>
                        </tr>
                    </thead>


                </table>
            </div>
        </div>
    </div>

</div>
<!-- Page level custom scripts -->

<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script type="text/javascript">
$(document).ready(function($) {
    var dataTableDelay= 1000;
    var table = $('#dataTable').DataTable({
        "order": [
            [0, "desc"]
        ],
        "ajax": {
            "url": "{{ route('getafterhourslistajax') }}",
            "data": function(d) {
                @if(LD::checkFeatureFlag('state-branch-filter-manage-after-hours-page'))
                d.filters = $('#datatableFilterSearchForm').serializeArray();
                @endif

            }
        },

        "processing": true,
        "serverSide": true,
        "columns": [
            {
                "data": "title"
            },
            @if (LD::checkFeatureFlag('add-columns-to-manage-after-hours-page'))
            {
                "data": "state",
                orderable: false,
                searchable: false,
                width: 120
            },
            {
                "data": "branch",
                orderable: false,
                searchable: false,
                width: 150
            },
            @endif
            {
                "data": "role_name",
                orderable: false,
                searchable: false
            },
            {
                "data": "display_start_time",
                "data-sort": "start_time"
            },
            {
                "data": "display_end_time",
                "data-sort": "end_time"
            },
            {
                "data": "repeat"
            },
            @if (LD::checkFeatureFlag('add-columns-to-manage-after-hours-page'))
            {
                data: 'created_by_name',
                name: 'created_by_name',
                orderable: false,
                searchable: false
            },
            @endif
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false,
                width: 70
            }
        ],
        "drawCallback": function(settings) {
            $(".delete").on('click', function() {
                if (confirm("Are you sure want to delete this Shift?")) {
                    window.location.href = $(this).attr('href');
                }
                return false;
            });
        },
        "initComplete": function(settings, json) {
				jQuery('#dataTable_filter input').off('keyup.DT input.DT');
				var searchDelay = null;
				jQuery('#dataTable_filter input').on('keyup', function(e) {
					var search = jQuery('#dataTable_filter input').val();
					clearTimeout(searchDelay);
					searchDelay = setTimeout(function() {
						if (search != null && search.length >=3)
							table.search(search).draw();
                        if (search.length==0 && e.keyCode==8)
                            table.search(search).draw();
					}, dataTableDelay);
				});
			},
    });
    @if(LD::checkFeatureFlag('state-branch-filter-manage-after-hours-page'))
    $(document).on("change",'#afterhoursstate',function() {
        $("#afterhoursbranch").multiselect("clearSelection");
        var state = $(this).val().join(",");
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{ route('getocstatebranch') }}",
            type: "GET",
            data: {
                state: state,
                _token: _token
            },
            success: function(response) {
                $('#afterhoursbranch').empty();
                $.each(response, function( index, value ) {
                    $("#afterhoursbranch").append('<option value="'+value.id+'">'+value.branch+'</option>')
                });
                $("#afterhoursbranch").multiselect("rebuild");
            },
        });
    });
    @endif
});
</script>
<!-- /.container-fluid -->
@endsection
