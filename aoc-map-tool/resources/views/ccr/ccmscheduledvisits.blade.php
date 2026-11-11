<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $clinicalCompetencyReview = [
        [
            'label'=>'Me Mode',
            'name'=>'ccrmemode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'ccrsvstate',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],[
            'label'=>'Region',
            'name'=>'region',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],[
            'label'=>'Branch',
            'name'=>'ccrbranch',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],[
            'label'=>'Clinical Care Manager',
            'name'=>'ccrclinicalcaremanager',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getClinicalCareManager()
        ],[
            'label'=>'Scheduled Date',
            'name'=>'schedulevisit_date',
            'type'=>'dateRangePicker',
            'fromName'=>'fromschedulevisit_date',
            'toName'=>'toschedulevisit_date',
            'fromdate'=>'',
            'todate'=>'',
        ]
    ];
?>
<div class="card-header">
    <h4 class="mb-0">
        <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
            data-target="#ccm_scheduled_visits" aria-expanded="false" aria-controls="collapseOne">
            SCHEDULED VISITS <span id="ccm_scheduled_visits-counter"></span><i class="fa fa-chevron-right"></i>
        </button>
    </h4>
</div>
<div id="ccm_scheduled_visits" class="collapse" data-parent="#clinical_case_childs">
    <?php if(LD::checkFeatureFlag('scheduled-visits-filter')){ ?>
    <div class="filters-common">
        @component('components.datatable-filters',['filters'=>$clinicalCompetencyReview,'formname'=>'ccmscheduledvisitsform'])
        @endcomponent
    </div>
    <?php } ?>
    <div class="card-body">
    <?php

        $globalFilterFields = [
            'state' => 'ccrsvstate',
            'branch' => 'ccrbranch',
            'region' => 'region',
        ];

        if(LD::checkFeatureFlag('scheduled-visits-filter')){
            echo H::getHelper("ClinicalCaseManager")->getDataTableHtml(
                'ccm_scheduled_visits',
                '#ccmscheduledvisitsform',
                $globalFilterFields,
            );
         } else{
            echo H::getHelper('ClinicalCaseManager')->getDataTableHtml('ccm_scheduled_visits', '', $globalFilterFields);
         } ?>
    </div>
</div>
<?php if(LD::checkFeatureFlag('scheduled-visits-filter')){ ?>
<script type="text/javascript">
$(document).ready(function(){
    $(document).on('submit', '#ccmscheduledvisitsform', function(e) {
        e.preventDefault();
        var serializeData = $("#ccmscheduledvisitsform").serializeArray();
        let _token = $('meta[name="csrf-token"]').attr('content');
        var tempObject = 'ccm_scheduled_visits';
        if($("#reload-"+tempObject).val()==1) {
                $.ajax({
                    url: "{{ route('dashboard.reloadcountclinicalcase') }}?type=ccm_scheduled_visits",
                    type: "POST",
                    data: {
                    filters: serializeData,
                    _token: _token
                },
                success: function(response) {
                    var jsonObject = JSON.parse(response);
                    var type = jsonObject.type;
                    var idObject = type.replace(/ /g,"_");
                    $("#"+idObject+'-counter').html(jsonObject.counter);
                },
            });
        } else {
            $("#dataTable-clinical-case-manager-"+tempObject).DataTable().ajax.reload();
        }
    });
    $("#ccmscheduledvisitsform").trigger("submit");
    $('#ccrsvstate').change(function(){
        if ($(this).val().indexOf('TX') !== -1) {
            $('#ccmscheduledvisitsform select[name="region"]').multiselect('enable');
        } else {
            $('#ccmscheduledvisitsform select[name="region"]').multiselect('disable');
        }
    })
    $('#ccmscheduledvisitsform .clearall').click(function() {
        $('#ccmscheduledvisitsform select[name="region"]').multiselect('disable');
    });
});
</script>
<?php }else {?>
<script type="text/javascript">
$(document).ready(function(){
let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('dashboard.reloadcountclinicalcase') }}?type=ccm_scheduled_visits",
        type: "POST",
        data: {
            _token: _token
        },
        success: function(response) {
            var jsonObject = JSON.parse(response);
            var type = jsonObject.type;
            var idObject = type.replace(/ /g,"_");
            $("#"+idObject+'-counter').html(jsonObject.counter);
        },
    });
});
</script>
<?php }?>
