<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\Enums\FeatureFlagsEnum;

if(LD::checkFeatureFlag('region-filter-for-all-dashboard'))  {
    $clinicalCompetencyReview = [
        [
            'label'=>'Me Mode',
            'name'=>'ccrmemode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'ccrstate',
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
            'label'=>'Requested By',
            'name'=>'created_by',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getCCRRequestby()
        ],[
            'label'=>'Payer',
            'name'=>'Payer',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPayers()
        ],[
            'label' => 'Payer Group',
            'name' => 'payer_group',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ]
    ];

} else {
    $clinicalCompetencyReview = [
        [
            'label'=>'Me Mode',
            'name'=>'ccrmemode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'ccrstate',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
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
        ]
    ];
}
if(LD::checkFeatureFlag('ccm-shifttype-new-filters')) {
    $clinicalCompetencyReview[]=
    [
        'label' => 'Shift Type',
        'name' => 'shift_type',
        'type' => 'multiselect',
        'source' => [
            ["value"=>"Coverage","text"=>"Coverage"],
            ["value"=>"Training","text"=>"Training"]]
    ];
}
$buckets =H::getHelper("ClientCareCoordinatorCoverage")->getBucketArray();
$keys = array_keys($buckets);
 ?>
 <div class="card" data-follows-global="true">
        <div class="card-header" id="clinical_case_manager">
            <h2 class="mb-0">
                <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                    data-target="#clinical_case_childs" aria-expanded="false" aria-controls="collapseThree">
                    Clinical Care Manager
                    @include('account.partials.globalFilterBadge')
                    <i class="fa fa-chevron-right"></i>
                </button>

            </h2>

        </div>
        <div id="clinical_case_childs" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
            <div class="card-body">
                @if(LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_THERAPIST_BUCKET->value))
                    <div class="card">
                        @include('ccr.therapist-applications.accordion', ['filters' => $filters])
                    </div>
                @endif
                <div class="card">
                    <div class="card-header" id="dailyactivityone">
                        <h4 class="mb-0">
                            <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                                data-target="#clinical_competency_reviews" aria-expanded="false" aria-controls="collapseOne">
                                Clinical Competency Reviews <span id="clinical_competency_reviews-counter"></span><i class="fa fa-chevron-right"></i>
                            </button>
                            <?php if(LD::checkFeatureFlag('ccr_show_all_page')){ ?>
                                @canPermission(['dashboard.getclinicalcase'])
                                    <div class="col-md-2 text-right pull-left p-1"><a href="{{route('ccrListing')}}" class="dashboard-showall" title="Show All CCRs">Show All CCRs</a></div>
                                @endcanPermission
                            <?php } ?>
                        </h4>
                    </div>
                    <div id="clinical_competency_reviews" class="collapse" aria-labelledby="dailyactivityone" data-parent="#clinical_case_childs">
                    <div class="col-md-12 text-left bucket-subheading">CCRs that need to be completed prior to a nurse working in a clients home. Anything red is considered past due (1 hour past the request time)</div>
                        <div
                            class="filters-common"
                            data-bucket-filter="true"
                            data-global-filter-fields='{"state":"ccrstate","region":"region","branch":"ccrbranch"}'
                        >
                            @component('components.datatable-filters',['filters'=>$clinicalCompetencyReview,'formname'=>'ccmclinicalcasemanagerform'])
                            @endcomponent
                        </div>

                        <div class="card-body">
                        <?php echo H::getHelper("ClinicalCaseManager")->getDataTableHtml(
                                    'clinical_competency_reviews',
                                    '#ccmclinicalcasemanagerform',
                                    [
                                        'state' => "ccrstate",
                                        'region' => 'region',
                                        'branch' => 'ccrbranch'
                                    ]
                        ) ?>
                        </div>
                    </div>
                </div>
                <div class="card">
                    @include('ccr.candidatestarts.accordion')
                </div>
                <div class="card">
                    @include('ccr.todayipea')
                </div>
                <div class="card">
                    @include('ccr.ccmscheduledvisits')
                </div>
                <?php if(LD::checkFeatureFlag('ccmdashboard_show_exception')){ ?>
                    <div class="card">
                        @include('ccr.ccmexceptionbucket')
                    </div>
                <?php } ?>
                @if(LD::checkFeatureFlag('add-supervisoryvisite-bucket-on-dashboard'))
                    @include('supervisoryvisits.supervisoryvisitbucket')
                @endif
            </div>
        </div>
    </div>

    <x-modal>
        <x-slot name="id">ccrmodel</x-slot>
        <x-slot name="size">w25</x-slot>
        <x-slot name="title">Confirmation</x-slot>
        <x-slot name="extraHeader"></x-slot>
        <x-slot name="class"></x-slot>
        <x-slot name="body">
            <div class="row">
                <form class="row" action="" id="updateccrview">
                    <input type="hidden" name="trackerid" id="trackerid" value="">
                </form>
                <h5 id="confirmationDialogMessage">Acknowledge you have seen this CCR</h5>
            </div>
        </x-slot>
        <x-slot name="footer">
            <button class="btn btn-primary" id="seeccrYesButton" onclick="seeAndCloseSave()">Yes</button>
            <button class="btn btn-primary" id="seeccrNoButton" onclick="seeAndClose()">No</button>
        </x-slot>
    </x-modal>
    <script>

    const ccrBucketArray = ['clinical_competency_reviews'];
    $(document).on('submit', '#ccmclinicalcasemanagerform', function(e) {
        e.preventDefault();
        var serializeData = $("#ccmclinicalcasemanagerform").serializeArray();
        for(var i=0; i < ccrBucketArray.length;i++) {
            var tempObject = ccrBucketArray[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('dashboard.reloadcountclinicalcase') }}?type="+ccrBucketArray[i],
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
                console.log("ccmclinicalcasemanagerform=tempObject="+tempObject);
                $("#dataTable-clinical-case-manager-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#ccmclinicalcasemanagerform").trigger("submit");
        $(document).on("change",'#ccrstate',function(){
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
                   $('#ccrbranch').empty();
                   $.each(response, function( index, value ) {
                       $("#ccrbranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#ccrbranch").multiselect("rebuild");
               },
           });


            if ($(this).val().indexOf('TX') !== -1) {
                $('#ccmclinicalcasemanagerform select[name="region"]').multiselect('enable');
            } else {
                $('#ccmclinicalcasemanagerform select[name="region"]').multiselect('disable');
            }
            $('#ccmclinicalcasemanagerform .clearall').click(function() {
                $('#ccmclinicalcasemanagerform select[name="region"]').multiselect('disable');
            });
        });
    </script>
    <script>
    function seeAndCloseSave() {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('acknowledgeCcr')}}",
            type: 'post',
            data: {
                id: $("#trackerid").val(),
                _token: _token
            },
            success: function(data) {
                $("#dataTable-clinical-case-manager-clinical_competency_reviews").DataTable().ajax.reload();
                $('#ccrmodel').modal('hide');
            }
        });
    }
    function seeAndClose() {
        $('#ccrmodel').modal('hide');
    }
</script>
