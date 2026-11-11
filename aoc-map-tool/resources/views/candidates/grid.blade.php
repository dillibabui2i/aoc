<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\Models\JobCategories;
use App\Enums\FeatureFlagsEnum;
?>
@section('title', 'Candidates')
@section('datatabletop')
<div class="form-group col-md-6 head-title">
    <h1 class="h3 mb-2 text-gray-800">Candidates
        <a title="Export" href="javascript:void(0)" id="exportCandidatesData"><i class="fa fa-download"></i></a>
        @if(LD::checkFeatureFlag('add-new-candidate'))
        @canPermission('getAddCandidateForm')
        <a href="javascript:void(0);" onclick="showAddCandidateForm('{{ request()->get('type') }}')"><i class="fa fa-plus"></i></a>
        <a href="{{route('candidates.import')}}"><i class="fa fa-upload" title="Import Candidate Data"></i></a>
        @endcanPermission
        @endif
    </h1>
</div>

@if (session('success'))
        <div class="col-sm-12">
            <div class="alert  alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
            </div>
        </div>
    @endif
<?php
$recruiters = H::getHelper("ConstantHelper")->getRecruiters('id');
$workflow = [];
$workflowList = config('constant')['appWorkflowState'];
foreach ($workflowList as $key => $value) {
    if (in_array($value, ['Added to Potential Nurse List','Offer Rejected','Rejected by Agency'])) {
        continue;
    }
    $workflow[] = ['value' => $value, 'text' => $value];
}

if (request()->get('type') === 'therapy') {
    $workflow = [];
    $workflowList = config('constant')['appWorkflowStateTherapist'];
    foreach ($workflowList as $key => $value) {
        $workflow[] = ['value' => $value, 'text' => $value];
    }


}

$cityList = H::getHelper("ConstantHelper")->getJobsLocationCity();
$city = [];
foreach ($cityList as $key => $value) {
    $city[] = ['value' => $value['locationCity'], 'text' => $value['locationCity']];
}

$category = [];
$categoryList = JobCategories::query()->where('is_enabled', true);

if (LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_NURSING_FILTER->value) && request()->get('type') === 'nursing') {
    $categoryList = $categoryList->where('category', 'like', '%Nurs%');
}

if (LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_THERAPIST_BUCKET->value) && request()->get('type') === 'therapy') {
    $categoryList = $categoryList->where('category', 'like', '%therap%');
}

$categoryList = $categoryList->pluck('category')->toArray();
foreach ($categoryList as $key => $value) {
    $category[] = ['value' => $value, 'text' => $value];
}
$filters = [
    [
        'label'=>'Me Mode',
        'name'=>'default',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label' => 'Recruiter',
        'name' => 'Recruiter',
        'type' => 'select',
        'source' => $recruiters
    ],
    [
        'label' => 'Workflow',
        'name' => 'Workflow',
        'type' => 'select',
        'source' => $workflow
    ],
    [
        'label' => 'City',
        'name' => 'City',
        'type' => 'select',
        'source' => $city
    ],
    [
        'label' => 'Category',
        'name' => 'Category',
        'type' => 'select',
        'source' => $category
    ],
    [
        'label' => 'Completed',
        'name' => 'Completed',
        'type' => 'checkbox',
        'value' => 0
    ],
    [
        'label' => 'Show All',
        'name' => 'showall',
        'type' => 'checkbox',
        'value' => 0
    ]
];
?>
@component('components.datatable-filters', ['filters' => $filters])
@endcomponent
<style>
i.fa {
    margin-right: 0px;
}
</style>
<script>
$(document).ready(function() {

    $("#exportCandidatesData").click(function(e) {
        var data = {};
        var search = {};
        let _token = $('meta[name="csrf-token"]').attr('content');
        data['filters'] = $("#datatableFilterSearchForm").serializeArray();
        data['jobviteType'] = "{{ request()->get('type') }}";
        search['value'] = $('#dataTable_filter').find('input').val();
        data['search'] = search;
        var url = "{{route('exportCandidatesData')}}" + '?' + $.param(data);
        window.location.href = url;
    });
    $('#showall').click(function() {
        let isShowAllChecked = $(this).is(':checked');
        $('#default').prop("disabled", isShowAllChecked);
        $('#Recruiter').prop("disabled", isShowAllChecked);
        $('#Workflow').prop("disabled", isShowAllChecked);
        $('#City').prop("disabled", isShowAllChecked);
        $('#Category').prop("disabled", isShowAllChecked);
        $('#Completed').prop("disabled", isShowAllChecked);
    });
    $('.clearall').click(function() {
        $('#default').prop("disabled", );
        $('#Recruiter').prop("disabled", false);
        $('#Workflow').prop("disabled", false);
        $('#City').prop("disabled", false);
        $('#Category').prop("disabled", false);
        $('#Completed').prop("disabled", false);
    });
    $('#Completed').click(function() {
        if ($(this).is(':checked')) {
            $('#Workflow').append('<option value="Added to Potential Nurse List">Added to Potential Nurse List</option>');
            $('#Workflow').append('<option value="Offer Rejected">Offer Rejected</option>');
            $('#Workflow').append('<option value="Rejected by Agency">Rejected by Agency</option>');
        } else {
            $('#Workflow option[value="Added to Potential Nurse List"]').remove();
            $('#Workflow option[value="Offer Rejected"]').remove();
            $('#Workflow option[value="Rejected by Agency"]').remove();
        }
    });

    $(document).on("click","#datatableFilterSearchForm .clearall",function(e) {
        $('#Workflow option[value="Added to Potential Nurse List"]').remove();
        $('#Workflow option[value="Offer Rejected"]').remove();
        $('#Workflow option[value="Rejected by Agency"]').remove();
    });
});
</script>

@endsection

@extends('layouts.datatable')

@section('datatablebottom')
@endsection

