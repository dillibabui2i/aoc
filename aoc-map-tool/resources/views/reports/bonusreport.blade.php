<?php
use App\Helpers\H;
use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Bonus Report')
@section('content')
<style>
    #datatableFilterSearchForm #Recruiter, #Bonus_Status {
        margin-bottom: 0px !important;
    }
</style>
<div class="form-group col-md-6 head-title">
    <h1 class="h3 mb-2 text-gray-800">Bonus Report</h1>
    <b><span id="bonus-report-amount" style="line-height: 28px; padding: 0px 5px;">($0.00)</span></b>
    <a title="Export" href="javascript:void(0)" id="exportBonusReport"><i class="fa fa-download"></i></a>
    <a title="Set to Bonus Paid" href="javascript:void(0)" id="setBonusPaid"><i class="fa fa-usd"></i></a>
</div>
<?php
$recruiters = H::getHelper("ConstantHelper")->getRecruiters();
$state = H::getHelper('CommonDatatableFilters')->getFilterDataSource('state');
$branches  = [];
$bonusStatusList = config('constant')['bonusStatus'];
$bonusStatus  = [];
foreach ($bonusStatusList as $bStatus) {
    $bonusStatus[] = ["value" => $bStatus, 'text' => $bStatus];
}
$filters = [
    [
        'label' => 'State',
        'name' => 'state',
        'type' => 'multiselect',
        'source' => $state
    ], [
        'label' => 'Branch',
        'name' => 'aoc_facility_id',
        'type' => 'multiselect',
        'source' => $branches
    ], [
        'label' => 'Recruiter',
        'name' => 'Recruiter',
        'type' => 'select',
        'source' => $recruiters
    ], [
        'label' => 'Bonus Staus',
        'name' => 'Bonus_Status',
        'type' => 'select',
        'source' => $bonusStatus
    ], [
        'label' => 'Date',
        'name' => 'daterange',
        'type' => 'daterange',
        'fromdate' => '',
        'todate' => '',
    ]
];
$filters[] = [
    'label'=>'Exclude PRN',
    'name'=>'excludeprn',
    'type'=>'checkbox',
    'value'=>false
];
?>

<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-body">
            @component('components.datatable-filters',['filters'=>$filters])
            @endcomponent
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0" aria-describedby="">
                    <thead>
                        <tr>
                            <th>Recruiter</th>
                            <th>Branch</th>
                            <th>Candidate</th>
                            <th>Client</th>
                            <th>Candidate Start Date</th>
                            <th>Hours Worked</th>
                            <th>Calculated Bonus</th>
                            <th>Override Bonus</th>
                            <th>Bonus Status</th>
                            <th>Tier</th>
                            <th>Margin</th>
                            <th>Actual GP</th>
                            <th>Bonus Amount</th>
                            <th>Paid Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<x-modal>
    <x-slot name="id">setBonusPaidmodal</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirmation</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row">
            <h5 id="confirmationDialogMessage">Are you sure you want to set <span id="totalrecord"></span> records to Bonus Paid</h5>
        </div>
        <label for="datepicker">Bonus Paid On:</label>
        <input type="input" class="form-control" id="datepicker" name="Bonus_Paid_Date" value=""/>
        <span id="bonus" style="color: red;display: none;">Field is required</span>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="setBonusPaidYesButton" onclick="saveAndclosesetBonusPaid()">Yes</button>
        <button class="btn btn-primary" id="setBonusPaidNoButton" onclick="closesetBonusPaid()">No</button>
    </x-slot>
</x-modal>
<x-modal>
    <x-slot name="id">updatebonusrecord</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Update Bonus override</x-slot>
    <x-slot name="extraHeader"></x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <input type="hidden" name="tracker_id" id="tracker_id" value="">
        <label for="datepicker">Bonus override</label>
        <input type="number" class="form-control" id="bonus_override" name="bonus_override" value=""/>
        <span id="bonuserror" style="color: red;display: none;">Field is required</span>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="updateBonusOverRide" onclick="updateBonusOverRide()">Yes</button>
        <button class="btn btn-primary" id="closeBonusRecord" onclick="closeBonusRecord()">No</button>
    </x-slot>
</x-modal>
<style>
    i.fa {
        margin-right: 0px;
    }
</style>
<script>
    $(document).ready(function() {

        $(document).on("change", '#state', function() {
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('getstatebranch') }}",
                type: "GET",
                data: {
                    state: $(this).val(),
                    _token: _token
                },
                success: function(response) {
                    $("#aoc_facility_id").empty();
                    $.each(response, function(index, value) {
                        $("#aoc_facility_id").append('<option value="' + value.id + '">' + value.FacilityName + '</option>');
                        $("#aoc_facility_id").multiselect("rebuild");
                    });
                },
            });

        });

        var table = $('#dataTable').DataTable({
            "order": [
                [0, "asc"]
            ],
            "ajax": {
                "url": "{{ route('recruiting') }}",
                data: function(d) {
                    d.filters = $('#datatableFilterSearchForm').serializeArray();
                }
            },
            "drawCallback": function (oSettings, json) {$("#totalrecord").html(oSettings.json.recordsFiltered),$("#bonus-report-amount").html('($'+oSettings.json.totalbonus.toLocaleString()+')');},
            "processing": true,
            "serverSide": true,
            columns: [{
                    data: 'Recruiter',
                    name: 'Recruiter'
                },
                {
                    data: 'Branch_Location',
                    name: 'Branch_Location'
                },
                {
                    data: 'Candidate',
                    name: 'Candidate'
                },
                {
                    data: 'Client',
                    name: 'Client'
                },
                {
                    data: 'Formatted_Candidate_Start_Date',
                    name: 'Candidate_Start_Date'
                },
                {
                    data: 'Hours_Worked',
                    name: 'Hours_Worked'
                },
                {
                    data: 'bonus_amount',
                    name: 'bonus_amount'
                },
                {
                    data: 'bonus_override',
                    name: 'bonus_override'
                },                {
                    data: 'Bonus_Status',
                    name: 'Bonus_Status'
                },
                {
                    data: 'bonus_tier',
                    name: 'Tier'
                },
                {
                    data: 'margin',
                    name: 'margin'
                },
                {
                    data: 'actual_gp',
                    name: 'actual_gp'
                },
                {
                    data: 'final_bonus_amount',
                    name: 'final_bonus_amount'
                },
                {
                    data: 'Formatted_Bonus_Paid_Date',
                    name: 'Bonus_Paid_Date'
                },
                {
                    data: null,
                    name: 'action',
                    render: function(data, type, row) {
                        let actionstr = '';
                        <?php  if(H::canPermissions('manage-recruiter-bonus')) { ?>
                            if(row['Bonus_Status'] == 'Pending Hours'){
                                actionstr += '<a href="javascript:void(0)" onclick=\'updateBonusRecord("' + row['id'] + '")\' ><i class="fa fa-edit" aria-hidden="true"></i></a>';
                                return actionstr;
                            }
                        <?php } ?>
                        return actionstr;
                    }
                },
            ]
        });
    });
    $("#exportBonusReport").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        var url = "{{route('exportBonusReport')}}" + '?' + $.param(data);
        window.location.href = url;
    });
    $("#setBonusPaid").click(function(e) {
        $('#setBonusPaidmodal').modal('show');
    });
    function saveAndclosesetBonusPaid() {
        let bonusPaidDate = $("#datepicker").val();
        if(bonusPaidDate == ''){
            $("#bonus").attr("style", "color: red;display:block");
            return false;
        }
        let _token = $('meta[name="csrf-token"]').attr('content');
        let filter = $("#datatableFilterSearchForm").serializeArray();
        let search = $('#dataTable_filter').find('input').val();
        $.ajax({
            url: "{{route('setBonusPaidmodal')}}",
            type: 'post',
            data: {
                bonusPaidDate: bonusPaidDate,
                search: search,
                filters: filter,
                _token: _token
            },
            success: function(data) {
                $('#setBonusPaidmodal').modal('hide');
                $('#dataTable').DataTable().ajax.reload(null, false);
            }
        });
    }
    function closesetBonusPaid() {
        $('#setBonusPaidmodal').modal('hide');
    }
    $('#datepicker').datepicker();
    $('#datepicker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($('#datepicker').parent());
    });
    $("#setBonusPaid").hide();
    function updateBonusRecord(id){
        $("#tracker_id").val(id);
        $('#updatebonusrecord').modal('show');
    }
    function closeBonusRecord(){
        $('#updatebonusrecord').modal('hide');
    }
    function updateBonusOverRide(){
        let bonusoverride = $("#bonus_override").val();
        let tracke_id = $("#tracker_id").val();
        if(bonusoverride == ''){
            $("#bonuserror").attr("style", "color: red;display:block");
            return false;
        }
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('updatebonusoverride')}}",
            type: 'post',
            data: {
                bonusoverride: bonusoverride,
                id: tracke_id,
                _token: _token
            },
            success: function(data) {
                $('#updatebonusrecord').modal('hide');
                $('#dataTable').DataTable().ajax.reload(null, false);
            }
        });
    }
</script>

@endsection
