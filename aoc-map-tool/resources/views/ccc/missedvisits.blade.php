<?php
use App\Helpers\H;
use App\Helpers\LD;

$lobName = H::getHelper('ConstantHelper')->getLob();
$loboption = [];
foreach($lobName as $lob){
    $loboption[] = ['value' => $lob->LOBID, 'text' =>$lob->LOBName];
}
$missedVisits = [
    [
        'label'=>'Me Mode',
        'name'=>'missedmemode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'missedstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Branch',
        'name'=>'missedbranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Client Care Coordinator',
        'name'=>'missedclientcare',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ]
];
if (LD::checkFeatureFlag('payer-filters-on-missed-visits')) {
    $onnth = ['No', 'Yes'];
    $opetionData = [];
    foreach ($onnth as $key => $value) {
        $opetionData[] = ['value' => $value, 'text' => $value];
    }
    $missedVisits[] = [
        'label'=>'Payer',
        'name'=>'Payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ];
    $missedVisits[] = [
        'label' => 'Special Payer Group',
        'name' => 'payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ];
    $missedVisits[] = [
        'label' => 'On NTH',
        'name' => 'on_nth',
        'type' => 'select',
        'source' => $opetionData
    ];
}
if(LD::checkFeatureFlag('add-lob-filter-in-missed-visits-bucket')){
    $missedVisits[] = [
        'label'=>'LOB',
        'name'=>'LOB',
        'type'=>'multiselect',
        'source' => $loboption
    ];
}
$missedVisits[] = [
    'label'=>'Schedule Date',
    'name'=>'scheduledate',
    'type'=>'daterangemissedbucket',
    'fromdate'=>'',
    'todate'=>'',
];

if(LD::checkFeatureFlag('add-patient-status-filter-in-missed-visits-bucket')){
    $patientStatus = H::getHelper('ConstantHelper')->getMssqlPatientStatus();
    $patientS = [];
    foreach($patientStatus as $status){
        $patientS[] = ['value' => $status->id, 'text' =>$status->PatientStatus];
    }
    usort($patientS, function ($a, $b) {
        return strcmp($a['text'], $b['text']);
    });
    $missedVisits[] = [
        'label'=>'Client Status',
        'name'=>'patient_status',
        'type'=>'multiselect',
        'source' => $patientS
    ];
}

if (LD::checkFeatureFlag('ccc-missed-visits-bucket')) { ?>
    @canPermission(['missed_visit_bucket'])
    
    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                        data-target="#ccc_missed_visits-container" aria-expanded="false" aria-controls="collapseOne">
                        Missed Visits <span id="ccc_missed_visits1-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                </h4>
            </div>


        
            <div id="ccc_missed_visits-container" class="collapse" aria-labelledby="dailyactivityone"
                data-parent="#client_care_childs">
                <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care Coordinator</u><br>
                            <b>Description:</b>  Visits are populating in planned status for the past full year and this year to date that need to be marked as missed with the correct PDN reason in KanTime.<br>
                            <b>Timeline:</b> Must clear buckets within two business days of the past planned shift.</p>
                <div class="filters-common">
                    @component('components.datatable-filters',['filters'=>$missedVisits,'formname'=>'cccmissedvisitsform'])
                    @endcomponent
                </div>
                
                <div class="card-body">
                        <div class="card">
                            <div class="card-header" id="ccc-planned-visit-container">
                                <h4 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#ccc_missed_visits" aria-expanded="false"
                                        aria-controls="collapseOne">
                                        Planned Visits With Clinician <span id="ccc_missed_visits-counter"></span> <i class="fa fa-chevron-right"></i>
                                    </button>
                                </h4>
                            </div>
                            <div id="ccc_missed_visits" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#ccc_missed_visits-container">
                                <div class="card-body">
                                    <?php  echo H::getHelper("ClientCareCoordinatorException")->getDataTableHtml('ccc_missed_visits')?>
                                </div>
                            </div>
                        </div>
                </div>


                <div class="card-body">
                        <div class="card">
                            <div class="card-header" id="ccc-planned-visit-container">
                                <h4 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#planned_visits_with_no_clinician" aria-expanded="false"
                                        aria-controls="collapseOne">
                                        Planned Visits With No Clinician <span id="planned_visits_with_no_clinician-counter"></span> <i class="fa fa-chevron-right"></i>
                                    </button>
                                </h4>
                            </div>
                            <div id="planned_visits_with_no_clinician" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#ccc_missed_visits-container">
                                <div class="card-body">
                                    <?php  echo H::getHelper("ClientCareCoordinatorException")->getDataTableHtml('planned_visits_with_no_clinician')?>
                                </div>
                            </div>
                        </div>
                </div>
            </div>
        </div>
    </div>

    <script>
       const missedBucket = ['ccc_missed_visits','planned_visits_with_no_clinician'];

    $(document).on('submit', '#cccmissedvisitsform', function(e) {
        e.preventDefault();
        var serializeData = $("#cccmissedvisitsform").serializeArray();
        
        for(var i=0; i < missedBucket.length;i++) {
            var tempObject = missedBucket[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('clientcare.reloadcountclientcareexceptions') }}?type="+missedBucket[i],
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
                $("#dataTable-clientcare-exception-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#cccmissedvisitsform").trigger("submit"); 


        $(document).on("change",'#missedstate',function(){
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
                   $('#missedbranch').empty();
                   $.each(response, function( index, value ) {
                       $("#missedbranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#missedbranch").multiselect("rebuild");
               },
           });
        });

        $(document).on("click",'#missedmemode',function(){ 
            $("#missedclientcare").multiselect("clearSelection");
        });
        $(document).on("change",'#missedclientcare',function(){ 
            $('#missedmemode').prop('checked', false);
        });
    </script>
    @endcanPermission
<?php } ?>