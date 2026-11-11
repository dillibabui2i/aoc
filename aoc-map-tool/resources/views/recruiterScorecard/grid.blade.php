<?php
use App\Helpers\H;
?>
@extends('layouts.myaccount')
@section('title', 'Recruiter Scorecards')
@section('content')
<link href="https://cdn.datatables.net/1.11.4/css/jquery.dataTables.min.css" rel="stylesheet">
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Recruiter Scorecards</h1>
            <a title="Add Recruiter" href="javascript:recruiterScorecard('add')"><i class="fa fa-plus-circle"></i></a>
        </div>
    </div>

<?php
$recruiters = H::getHelper("ConstantHelper")->getRecruiters('email');
$RecruitingManager = H::getHelper("ConstantHelper")->getRecruiterManager('email');
$filters = [
    [
        'label' => 'Recruiter',
        'name' => 'Recruiter',
        'type' => 'multiselect',
        'source' => $recruiters
    ],
    [
        'label' => 'Recruiting Manager',
        'name' => 'Recruiting_Manager',
        'type' => 'multiselect',
        'source' => $RecruitingManager
    ],
    [
        'label' => 'Date',
        'name' => 'daterange',
        'type' => 'daterange',
        'fromdate' => '',
        'todate' => '',
    ],
];
?>
@component('components.datatable-filters', ['filters' => $filters])
@endcomponent
    <!-- DataTales Example -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th>Manager</th>
                            <th>Recruiter</th>
                            <th>Submitted On</th>
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
            "url": "{{ route('getrecruiterscorecardlist') }}",
            "data": function(d) {
                d.filters = $("#datatableFilterSearchForm").serializeArray();
            }
        },

        "processing": true,
        "serverSide": true,
        "columns": [{
                "data": "month"
            },
            {
                "data": "manager"
            },
            {
                "data": 'recruiter'
            },
            {
                "data": "submitted_on",
            },
            {
                data: 'action',
                name: 'action',
                orderable: false,
                searchable: false
            }
        ],
        "drawCallback": function(settings) {
            $(".delete-it").on('click', function() {
                if (confirm("Are you sure want to delete this scorecard?")) {
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
    });
});
</script>
<!-- /.container-fluid -->
@endsection
