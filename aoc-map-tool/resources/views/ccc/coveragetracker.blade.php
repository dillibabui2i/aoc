<?php
use App\Helpers\H;
use App\Helpers\LD;

$currentWeekDates = H::getWeekRange(date('Y-m-d'));

$coverageTracker = [];

if(LD::checkFeatureFlag('region-filter-for-all-dashboard'))  {
    $coverageTracker = [
    [
        'label'=>'Me Mode',
        'name'=>'default',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'coveragetrackerstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label'=>'Region',
        'name'=>'coveragetrackerregion',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label'=>'Branch',
        'name'=>'coveragetrackerbranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Coordinator',
        'name'=>'coveragetrackerclientcare',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ],[
        'label'=>'Payer',
        'name'=>'coveragetrackerpayer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ],[
        'label' => 'Special Payer Group',
        'name' => 'coveragetrackerpayer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],[
        'label'=>'Date',
        'name'=>'scheduledate',
        'type'=>'coveragetrackerdaterange',
        'fromdate'=> H::getFormatedDate($currentWeekDates[0]),
        'todate'=> H::getFormatedDate($currentWeekDates[1]),
    ]
];
} else {

$coverageTracker = [
    [
        'label'=>'Me Mode',
        'name'=>'default',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'coveragetrackerstate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Branch',
        'name'=>'coveragetrackerbranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'Coordinator',
        'name'=>'coveragetrackerclientcare',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClientCareCoordinator()
    ],[
        'label'=>'Payer',
        'name'=>'coveragetrackerpayer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ],[
        'label' => 'Special Payer Group',
        'name' => 'coveragetrackerpayer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],[
        'label'=>'Date',
        'name'=>'scheduledate',
        'type'=>'coveragetrackerdaterange',
        'fromdate'=> H::getFormatedDate($currentWeekDates[0]),
        'todate'=> H::getFormatedDate($currentWeekDates[1]),
    ]
];
}

if(LD::checkFeatureFlag('add-intake-coverage-tracker-widget-cc-dashboard'))  {
    $intakeValue= [['value' =>'Yes', 'text'=> 'Yes'],['value' =>'No', 'text'=> 'No']];
    $coverageTracker[] = [
        'label'=>'Intake',
        'name'=>'coveragetrackerintake',
        'type'=>'select',
        'source'=>$intakeValue
    ];
}
$buckets =H::getHelper("ClientCareCoordinatorCoverage")->getBucketArray();
$keys = array_keys($buckets);
 ?>
    <div class="card-body">
        <div class="card">
            <div class="card-header" id="dailyactivityone">
                <h4 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed col-md-10 pull-left" type="button" data-toggle="collapse"
                        data-target="#ccc_coverage_tracker-container" aria-expanded="false" aria-controls="collapseOne">
                        Coverage Tracker<span id="ccc_coverage_tracker1-counter"></span><i class="fa fa-chevron-right"></i>
                    </button>
                    @canPermission('coverage.list')
                    <div class="col-md-2 text-right pull-left p-0"><a href="{{route('coverage.list')}}" class="dashboard-showall" title="Show Coverage Tracker">Show Coverage Tracker</a></div>
                    @endcanPermission
                </h4>
            </div>


        
            <div id="ccc_coverage_tracker-container" class="collapse" aria-labelledby="dailyactivityone"
                data-parent="#client_care_childs">
                <p style="text-align: left;"><u>Confirm each progressive step completed in the Coverage Escalation Process.</u></p>
                <div class="filters-common"style="display: flex;">
                    <div class="pt-3">
                        @if (LD::checkFeatureFlag('add-to-tracker-from-dashboard-ccc'))
                            <a href="javascript:void(0);" onclick="addCoverageShiftTracker('dashboard_buckets_add_form', '');"><i class="fa fa-plus-circle" aria-hidden="true" style="font-size:28px;color:#2f5baa;"></i></a>
                        @else
                            <a href="javascript:void(0);" onclick="addCoverageShiftTracker();"><i class="fa fa-plus-circle" aria-hidden="true" style="font-size:28px;color:#2f5baa;"></i></a>
                        @endif
                    </div>
                    @component('components.datatable-filters',['filters'=>$coverageTracker,'formname'=>'ccccoveragetrackerform'])
                    @endcomponent
                </div>
                
                <div class="card-body">
                    <?php
                        $clientCareException =  H::getHelper("ClientCareCoordinatorCoverage")->getBuckets();
                        foreach($clientCareException as $bucketKey => $bucket) {?>
                            <div class="card">
                                <div class="card-header" id="ccc_coverage_tracker-<?php echo $bucketKey?>">
                                    <h4 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button"
                                            data-toggle="collapse" data-target="#<?php echo $bucketKey?>" aria-expanded="false"
                                            aria-controls="collapseOne">
                                            <?php echo $bucket['heading']?> <span id="<?php echo $bucketKey?>-counter"></span> <i class="fa fa-chevron-right"></i>
                                        </button>
                                    </h4>
                                </div>
                                <div id="<?php echo $bucketKey?>" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#ccc_coverage_tracker-container">
                                @if($bucketKey == 'text_call_client_schedule_regulars')
                                    <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care or Coverage Coordinator</u><br>
                                    <b>Description:</b> Text and call clinicians who regularly work in the home about coverage shifts.<br>
                                    <b>Timeline:</b> All regular clinicians should be contacted as soon as coverage needs are identified.</p>
                                @elseif($bucketKey == 'text_call_record_clinicians')
                                    <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care or Coverage Coordinator</u><br>
                                    <b>Description:</b> Text and call clinicians who have previously worked in the home about coverage shifts<br>
                                    <b>Timeline:</b> All record clinicians should be contacted as soon as calls/texts to regular clinicians are completed.</p>
                                @elseif($bucketKey == 'text_call_prn_clinicians_from_local_prn_schedule')      
                                    <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care or Coverage Coordinator</u><br>
                                    <b>Description:</b> Text and call clinicians from the local PRN Schedule about coverage shifts.<br>
                                    <b>Timeline:</b> All PRN Calendar clinicians should be contacted as soon as calls/texts to regular clinicians are completed.</p>
                                @elseif($bucketKey == 'text_blast_180_mile_radius_from_text_blast_map')
                                    <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care or Coverage Coordinator</u><br>
                                    <b>Description:</b> Send a text blast to clinicians from the coverage clinicians Map about upcoming coverage shifts. Blasts may start with a smaller radius and escalate out, depending on urgency related to timing of the shift. This step is not considered completed until a full 180-mile radius is used.<br>
                                    <b>Timeline:</b> Text blasts should be sent day one after the prior steps are completed.</p>
                                @elseif($bucketKey == 'call_nurses_from_text_blast_map')
                                    <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care or Coverage Coordinator</u><br>
                                    <b>Description:</b> Call clinicians from the text blast in the prior step about coverage shifts. During the call, if they cannot pick up the shift you are promoting most, find out what days and times they CAN pick up a shift and get it booked. The better you are at building relationships during these calls, the easier it will be to find coverage. Get to know your clinicians for the best partnership.<br>
                                    <b>Timeline:</b> Make multiple attempts daily to secure coverage, gradually expanding the radius.</p>
                                @elseif($bucketKey == 'check_cover_nurses_availability')
                                    <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care or Coverage Coordinator</u><br>
                                    <b>Description:</b> Contact Cover Nurse Coordinator to check availability only after completing prior steps.<br>
                                    <b>Timeline:</b> As soon as all texted clinicians have been called.</p>
                                @endif
                                    <div class="card-body">
                                    <?php  echo H::getHelper("ClientCareCoordinatorCoverage")->getDataTableHtml($bucketKey)?>
                                    </div>
                                </div>
                            </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    const ccBucketArrayCoverageTracker = <?php echo json_encode($keys); ?>;
    $(document).on('submit', '#ccccoveragetrackerform', function(e) {
        e.preventDefault();
        var serializeData = $("#ccccoveragetrackerform").serializeArray();
        
        for(var i=0; i < ccBucketArrayCoverageTracker.length;i++) {
            var tempObject = ccBucketArrayCoverageTracker[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('clientcare.reloadcountclientcarecoveragetracker') }}?type="+ccBucketArrayCoverageTracker[i],
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
                $("#dataTable-clientcare-coverage-tracker-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#ccccoveragetrackerform").trigger("submit"); 
        $(document).on("change",'#coveragetrackerstate',function(){
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');

            <?php if(LD::checkFeatureFlag("region-filter-for-all-dashboard")) { ?> 
                    if (state.indexOf('TX') !== -1) {
                        $('select[name="coveragetrackerregion"]').multiselect('enable');
                    } else {
                        $('select[name="coveragetrackerregion"]').multiselect('disable');
                    }
            <?php } ?>
            $.ajax({
               url: "{{ route('getstatebranch') }}",
               type: "GET",
               data: {
                   state: state,
                   _token: _token
               },
               success: function(response) {
                   $('#coveragetrackerbranch').empty();
                   $.each(response, function( index, value ) {
                       $("#coveragetrackerbranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#coveragetrackerbranch").multiselect("rebuild");
               },
           });
        });
    </script>