<?php
use App\Helpers\H;
use App\Helpers\LD;
$filters = [
    [
        'label' => 'State',
        'name' => 'officestate',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label' => 'Region',
        'name' => 'officeregion',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label' => 'Branch',
        'name' => 'officebranch',
        'type' => 'multiselect',
        'source' => []
    ],
    [
        'label' => 'Supervisor',
        'name' => 'officesupervisor',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getOfficeEvaluationSupervisor()
    ],
    [
        'label' => 'Manager',
        'name' => 'officemanager',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getOfficeEvaluationManagers()
    ],
    [
        'label' => 'Status',
        'name' => 'officestatus',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getOfficeEvaluationStatus()
    ]
];
 ?>
 <div class="card" data-follows-global="true">
        <div class="card-header" id="leadership_widget">
            <h2 class="mb-0">
                <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                    data-target="#leadership_childs" aria-expanded="false" aria-controls="collapseThree">
                    Evaluations
                    @include('account.partials.globalFilterBadge')
                    <i class="fa fa-chevron-right"></i>
                </button>

            </h2>

        </div>
        <div id="leadership_childs" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
            <div class="card-body">

                <?php if (LD::checkFeatureFlag('office-evaluation-dashboard-bucket')) { ?>
                    @canPermission(['officeevaluation.unsentdocuments'])
                        <div class="card">
                            <div class="card-header" id="dailyactivityone">
                                <h4 class="mb-0">
                                    <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                                        data-target="#office_evaluation" aria-expanded="false" aria-controls="collapseOne">
                                        Office Evaluation <span id="office_evaluation-counter"></span><i class="fa fa-chevron-right"></i>
                                    </button>

                                    <div class="col-md-2 text-right pull-left"><a href="{{route('officeevaluation.list')}}" class="dashboard-showall" title="Show All Office Evaluation">Show All Office Evaluation</a></div>

                                </h4>
                            </div>
                            <div
                                id="office_evaluation"
                                class="collapse filters-common"
                                aria-labelledby="dailyactivityone"
                                data-parent="#leadership_childs"
                                data-bucket-filter="true"
                                data-global-filter-fields='{"state":"officestate","region":"officeregion","branch":"officebranch","stateTriggersAjax":true}'
                                data-triggers-counter-refresh="true"
                            >
                                @component('components.datatable-filters',['filters'=>$filters,"formname"=>'officeEv'])
                                @endcomponent
                                <p style="text-align: left;"><u><b>Responsible Party:</b> Supervisors and Managers</u><br>
                                <b>Description:</b> Supervisor completes and submits; manager approves and submits.<br>
                                <b>Timeline:</b> Check daily.  Refer to Sharepoint for this year's deadlines.</p>
                                <div class="card-body">
                                    <?php echo H::getHelper("OfficeEvaluation")->getDataTableHtml('office_evaluation') ?>
                                </div>
                            </div>
                        </div>
                        @endcanPermission
                        <?php } ?>

                <?php if (LD::checkFeatureFlag('clinician-care-manager-dashboard')) { ?>
                    @canPermission(['fieldevaluation.unsentdocuments'])
                        @include('fieldEvaluation.dashboard')
                    @endcanPermission
                <?php } ?>
            </div>


        </div>
    </div>
    <script>
        const officeEvaluation = ['office_evaluation'];
        $(document).on('submit', '#officeEv', function(e) {
            e.preventDefault();
            var officeEvFieldNames = { state: 'officestate', region: 'officeregion', branch: 'officebranch' };
            var officeEvReloadData = window.GlobalLocationFilter
                ? window.GlobalLocationFilter.buildReloadFilters('#officeEv', officeEvFieldNames)
                : { filters: $("#officeEv").serializeArray() };
            for(var i=0; i < officeEvaluation.length;i++) {
                var tempObject = officeEvaluation[i].replace(/ /g,"_");
                if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('officeevaluation.reloadunsentdocuments') }}?type="+officeEvaluation[i],
                        type: "POST",
                        data: {
                            filters: officeEvReloadData.filters,
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
                    $("#dataTable-leadership-"+tempObject).DataTable().ajax.reload();
                }
            }
        });
        $("#officeEv").trigger("submit");


        $(document).on("change",'#officestate',function(){
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
                   $('#officebranch').empty();
                   $.each(response, function( index, value ) {
                       $("#officebranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#officebranch").multiselect("rebuild");
               },
           });

           if (state.indexOf('TX') !== -1) {
                $('#officeregion').multiselect('enable');
            } else {
                $('#officeregion').multiselect('disable');
            }
        });



     </script>
