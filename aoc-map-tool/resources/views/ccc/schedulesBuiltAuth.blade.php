<?php
use App\Helpers\H;
use App\Helpers\LD;
$intakeValue= [['value' =>'Yes', 'text'=> 'Yes'],['value' =>'No', 'text'=> 'No']];
$patientStatus = H::getHelper("ConstantHelper")->getMssqlPatientStatus();
$status = [];
foreach($patientStatus as $ps){
    $status[] = ["value"=>$ps->id,'text'=>$ps->PatientStatus];
}
$lobName = H::getHelper('ConstantHelper')->getLob();
$loboption = [];
foreach($lobName as $lob){
    $loboption[] = ['value' => $lob->LOBID, 'text' =>$lob->LOBName];
}
if(LD::checkFeatureFlag('add-lob-filter-to-built-vs-auth-bucket')){
    $schedulesAuthFilter = [
        [
            'label'=>'Me Mode',
            'name'=>'scheduleBuiltAuthMeMode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'scheduleBuiltAuthState',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label'=>'Region',
            'name'=>'scheduleBuiltAuthRegion',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label'=>'Branch',
            'name'=>'scheduleBuiltAuthBranch',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],[
            'label'=>'Coordinator',
            'name'=>'scheduleBuiltAuthCccId',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],
        [
            'label' => 'Status',
            'name' => 'scheduleBuiltAuthPatient_CurrentStatusID',
            'type' => 'multiselect',
            'source' => $status,
            'default' => 1
        ],
        [
            'label'=>'Intake',
            'name'=>'scheduleBuiltAuthIntake',
            'type'=>'select',
            'source'=>$intakeValue
        ],
        [
            'label'=>'LOB',
            'name'=>'builtVsAuthLob',
            'type'=>'multiselect',
            'source' => $loboption
        ],
        [
            'label'=>'Payer',
            'name'=>'scheduleBuiltAuthPayer',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPayers()
        ],[
            'label' => 'Special Payer Group',
            'name' => 'scheduleBuiltAuthPayerGroup',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ]
    ];
}else{
    $schedulesAuthFilter = [
        [
            'label'=>'Me Mode',
            'name'=>'scheduleBuiltAuthMeMode',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'scheduleBuiltAuthState',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label'=>'Region',
            'name'=>'scheduleBuiltAuthRegion',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label'=>'Branch',
            'name'=>'scheduleBuiltAuthBranch',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],[
            'label'=>'Coordinator',
            'name'=>'scheduleBuiltAuthCccId',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ],
        [
            'label' => 'Status',
            'name' => 'scheduleBuiltAuthPatient_CurrentStatusID',
            'type' => 'multiselect',
            'source' => $status,
            'default' => 1
        ],
        [
            'label'=>'Intake',
            'name'=>'scheduleBuiltAuthIntake',
            'type'=>'select',
            'source'=>$intakeValue
    ],
        [
            'label'=>'Payer',
            'name'=>'scheduleBuiltAuthPayer',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getPayers()
        ],[
            'label' => 'Special Payer Group',
            'name' => 'scheduleBuiltAuthPayerGroup',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ]
    ];
}

$buckets =H::getHelper("ClientCareCoordinatorSchedulesBuiltAuth")->getBucketArray();
$keys = array_keys($buckets);
 ?>
    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#ccc_schedules_built_auth-container" aria-expanded="false"
                        aria-controls="collapseOne">
                        Built vs auth<span id="ccc_schedules_built_auth1-counter">
                            </span><i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>
            <div id="ccc_schedules_built_auth-container" class="collapse" aria-labelledby="dailyactivityone"
                data-parent="#client_care_childs">
                <div class="filters-common">
                    @component('components.datatable-filters',
                    ['filters'=>$schedulesAuthFilter,'formname'=>'cccSchedulesBuiltAuthFilterForm'])
                    @endcomponent
                </div>

                <div class="card-body">
                    <?php
                        $buckets =  H::getHelper("ClientCareCoordinatorSchedulesBuiltAuth")->getBuckets();
                        foreach($buckets as $bucketKey => $bucket) {?>
                            <div class="card">
                                <div class="card-header" id="ccc_schedules_built_auth-<?php echo $bucketKey?>">
                                    <h4 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button"
                                            data-toggle="collapse" data-target="#<?php echo $bucketKey?>"
                                            aria-expanded="false" aria-controls="collapseOne">
                                            <?php echo $bucket['heading']?> <span id="<?php echo $bucketKey?>-counter">
                                            </span><i class="fa fa-chevron-right"></i>
                                        </button>
                                    </h4>
                                </div>
                                <div id="<?php echo $bucketKey?>" class="collapse " aria-labelledby="dailyactivityone"
                                    data-parent="#ccc_schedules_built_auth-container">
                                    <div class="card-body">
                                    <?php  echo H::getHelper("ClientCareCoordinatorSchedulesBuiltAuth")
                                    ->getDataTableHtml($bucketKey)?>
                                    </div>
                                </div>
                            </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    const cccScheduleAuthBucketArray = <?php echo json_encode($keys); ?>;
    $(document).on('submit', '#cccSchedulesBuiltAuthFilterForm', function(e) {
        e.preventDefault();
        var serializeData = $("#cccSchedulesBuiltAuthFilterForm").serializeArray();

        for(var i=0; i < cccScheduleAuthBucketArray.length;i++) {
            var tempObject = cccScheduleAuthBucketArray[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('ccc.reloadClientCareCoordinatorSchedulesBuiltAuth') }}?type="+cccScheduleAuthBucketArray[i],
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
                $("#dataTable-clientcare-schedule-built-auth-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#cccSchedulesBuiltAuthFilterForm").trigger("submit");
        $(document).on("change",'#scheduleBuiltAuthState',function(){
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');
            if (state.indexOf('TX') !== -1) {
                $('select[name="scheduleBuiltAuthRegion"]').multiselect('enable');
            } else {
                $('select[name="scheduleBuiltAuthRegion"]').multiselect('disable');
            }
            $.ajax({
               url: "{{ route('getstatebranch') }}",
               type: "GET",
               data: {
                   state: state,
                   _token: _token
               },
               success: function(response) {
                   $('#scheduleBuiltAuthBranch').empty();
                   $.each(response, function( index, value ) {
                       $("#scheduleBuiltAuthBranch").append('<option\
                       value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#scheduleBuiltAuthBranch").multiselect("rebuild");
               },
           });
        });

        $(document).on("click",'#scheduleMeMode',function(){
            $("#scheduleCccId").multiselect("clearSelection");
        });
        $(document).on("change",'#scheduleCccId',function(){
            $('#scheduleMeMode').prop('checked', false);
        });
    </script>
