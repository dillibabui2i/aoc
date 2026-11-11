<?php

use App\Helpers\H;
use App\Helpers\LD;
$patientStatus = H::getHelper("ConstantHelper")->getMssqlPatientStatus();
$status = [];
if(LD::checkFeatureFlag('ccmdashboard_show_exception')){
    foreach($patientStatus as $ps){
        if(in_array($ps->PatientStatus,['Active','Intake','On Hold','Pending SOC','Transferred'])){
            $status[] = ["value"=>$ps->id,'text'=>$ps->PatientStatus];
        }
    }
}else{
    foreach($patientStatus as $ps){
        $status[] = ["value"=>$ps->id,'text'=>$ps->PatientStatus];
    }
}
$clinicalException = [
    [
        'label'=>'Me Mode',
        'name'=>'ccmmemode',
        'type'=>'checkbox',
        'value'=>true
    ],[
        'label' => 'State',
        'name' => 'ccmstate',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Region',
        'name'=>'ccmregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],[
        'label' => 'Branch',
        'name' => 'ccmbranch',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
],
    [
        'label' => 'Status',
        'name' => 'ccmcurrentstatus',
        'type' => 'multiselect',
        'source' => $status
    ],
];
?>
<div class="card-header" id="dailyactivityone">
    <h4 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#clinical_exception_bucket" aria-expanded="false" aria-controls="collapseOne">
            Exception Bucket<span id="clinical_exception_bucket-counter"></span><i class="fa fa-chevron-right"></i>
        </button>
    </h4>
</div>
<div id="clinical_exception_bucket" class="collapse" aria-labelledby="dailyactivityone" data-parent="#clinical_case_childs">
    <div class="filters-common">
        @component('components.datatable-filters',['filters'=>$clinicalException,'formname'=>'clinicalexceptionform'])
        @endcomponent
    </div>

    <div class="card-body">
    <?php echo H::getHelper("ClinicalCaseManager")->getDataTableHtml(
    'clinical_exception_bucket',
    '#clinicalexceptionform',
        [
            'state' => 'ccmstate',
            'branch' => 'ccmbranch',
            'region' => 'ccmregion',
        ]
    ) ?>
    </div>
</div>
<script>
    const ccmBucketArray = ['clinical_exception_bucket'];
    $(document).on('submit', '#clinicalexceptionform', function(e) {
        e.preventDefault();
        var serializeData = $("#clinicalexceptionform").serializeArray();
        for (var i = 0; i < ccmBucketArray.length; i++) {
            var tempObject = ccmBucketArray[i].replace(/ /g, "_");
            if ($("#reload-" + tempObject).val() == 1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('dashboard.reloadcountclinicalcase') }}?type=" + ccmBucketArray[i],
                    type: "POST",
                    data: {
                        filters: serializeData,
                        _token: _token
                    },
                    success: function(response) {
                        var jsonObject = JSON.parse(response);
                        var type = jsonObject.type;
                        var idObject = type.replace(/ /g, "_");
                        $("#" + idObject + '-counter').html(jsonObject.counter);
                    },
                });
            } else {
                console.log("clinicalexceptionform=tempObject=" + tempObject);
                $("#dataTable-clinical-case-manager-" + tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#clinicalexceptionform").trigger("submit");
    $(document).on("change", '#ccmstate', function() {
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
                $('#ccmbranch').empty();
                $.each(response, function(index, value) {
                    $("#ccmbranch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                });
                $("#ccmbranch").multiselect("rebuild");
            },
        });
        if ($(this).val().indexOf('TX') !== -1) {
            $('#clinicalexceptionform select[name="ccmregion"]').multiselect('enable');
        } else {
            $('#clinicalexceptionform select[name="ccmregion"]').multiselect('disable');
        }
        $('#clinicalexceptionform .clearall').click(function() {
            $('#clinicalexceptionform select[name="ccmregion"]').multiselect('disable');
        });
    });
</script>
