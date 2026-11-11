<?php
use App\Helpers\H;
use App\Helpers\LD;
?>
@section('datatabletop')
<div class="form-group col-md-6 head-title">
    <h1 class="h3 mb-2 text-gray-800">Recruiter Tracker <a title="Export" href="javascript:void(0)"
            id="exportTrackerData"><i class="fa fa-download"></i></a>
    @if(LD::checkFeatureFlag('add-import-option-to-recruiter-tracker'))
        <a href="{{route('tracker.import')}}"><i class="fa fa-upload" title="Import Tracker Data"></i></a>
    @endif
    </h1>
</div>
<?php
$branchObject = H::getHelper("ConstantHelper")->getFacilities();
$recruiters = H::getHelper("ConstantHelper")->getRecruitersList(false);
$state = H::getHelper("ConstantHelper")->getDashboardState();
$candidateCurrentStatus = H::getHelper("ConstantHelper")->getCandidateCurrentStatus();
$branch  = [];
foreach($branchObject as $br) {
    $branches[] = ["value"=>$br->id,'text'=>$br->FacilityName];
}
$currentStatus  = [];
foreach($candidateCurrentStatus as $cStatus) {
    $currentStatus[] = ["value"=>$cStatus,'text'=>$cStatus];
}
$bonusStatusList = config('constant.bonusStatus');
$bonusStatus  = [];
foreach($bonusStatusList as $bStatus) {
    $bonusStatus[] = ['value' => $bStatus, 'text' => $bStatus];
}

$recruitersAdminList = H::getAllRecruitersAdmin();
$recruitersAdmin = [];
foreach($recruitersAdminList as $id => $name) {
    $recruitersAdmin[] = ["value"=>$id,'text'=>$name];
}
if(LD::checkFeatureFlag('recruiter-tracker-show-all-page-multiselect-filter')) {

     $filters = [
        [
            'label'=>'Include Completed',
            'name'=>'incl_complete',
            'type'=>'checkbox',
            'value'=>0

        ],
        [
            'label'=>'State',
            'name'=>'rec_state',
            'type'=>'multiselect',
            'source'=>$state
        ],[
            'label'=>'Branch',
            'name'=>'rec_aoc_facility_id',
            'type'=>'multiselect',
            'source'=>$branches
        ],[
            'label'=>'Recruiter',
            'name'=>'rec_recruiter',
            'type'=>'multiselect',
            'source'=>$recruiters
        ],[
            'label'=>'Candidate Current Status',
            'name'=>'rec_Candidate_Current_Status',
            'type'=>'multiselect',
            'source'=>$currentStatus
        ],[
            'label'=>'Bonus Staus',
            'name'=>'rec_Bonus_Status',
            'type'=>'multiselect',
            'source'=>$bonusStatus
        ],
        [
            'label'=>'Recruiting Admin',
            'name'=>'rec_user_id',
            'type'=>'multiselect',
            'source'=>$recruitersAdmin
        ]
    ];

} else {
    
    $filters = [
        [
            'label'=>'Include Completed',
            'name'=>'incl_complete',
            'type'=>'checkbox',
            'value'=>0

        ],
        [
            'label'=>'State',
            'name'=>'state',
            'type'=>'select',
            'source'=>$state
        ],[
            'label'=>'Branch',
            'name'=>'aoc_facility_id',
            'type'=>'select',
            'source'=>$branches
        ],[
            'label'=>'Recruiter',
            'name'=>'Recruiter',
            'type'=>'select',
            'source'=>$recruiters
        ],[
            'label'=>'Candidate Current Status',
            'name'=>'Candidate_Current_Status',
            'type'=>'select',
            'source'=>$currentStatus
        ],[
            'label'=>'Bonus Staus',
            'name'=>'Bonus_Status',
            'type'=>'select',
            'source'=>$bonusStatus
        ]
    ];
    if (LD::checkFeatureFlag('add-RA-filter-recruting-dashboard')) {
        $filters[] = [
            'label'=>'Recruiting Admin',
            'name'=>'user_id',
            'type'=>'select',
            'source'=>$recruitersAdmin
        ];
    }

}
?>
@component('components.datatable-filters',['filters'=>$filters])
@endcomponent

<style>
i.fa {
    margin-right: 0px;
}
</style>
<script>
$(document).ready(function() {

    $("#exportTrackerData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        var url = "{{route('exportTrackerData')}}" + '?' + $.param(data);
        window.location.href = url;
    });

    $(document).on("change", '#aoc_facility_id', function() {
        $("#Recruiter").val($("#Recruiter option:first").val());
        $("#state").val($("#state option:first").val());
    });

    $(document).on("change", '#Recruiter', function() {

        $("#aoc_facility_id").val($("#aoc_facility_id option:first").val());
        $("#state").val($("#state option:first").val());
    });

    $(document).on("change", '#state', function() {

        $("#aoc_facility_id").val($("#aoc_facility_id option:first").val());
        $("#Recruiter").val($("#Recruiter option:first").val());
    });
});
</script>

@endsection

@extends('layouts.datatable')

@section('datatablebottom')
<script>
	function removeActivity(id) {
		let _token = $('meta[name="csrf-token"]').attr('content');
		if (confirm("Are you sure you want to delete this activity?")) {
			$.ajax({
				url: "{{route('dashboard.removeactivity')}}",
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
				}
			});
		} else {
			return false;
		}
	}
</script>
@endsection