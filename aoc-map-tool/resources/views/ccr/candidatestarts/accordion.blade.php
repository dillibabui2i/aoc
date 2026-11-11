<?php
use App\Helpers\H;
use App\Helpers\LD;

if(LD::checkFeatureFlag('region-filter-for-all-dashboard'))  {
$ccmcandidatestarts = [

        [
            'label'=>'Me Mode',
            'name'=>'ccmcandidatedefault',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'ccmcandidatestate',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label'=>'Region',
            'name'=>'ccmcandidateregion',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label'=>'Branch',
            'name'=>'ccmcandidatebranch',
            'type'=>'multiselect',
            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label'=>'CCM',
            'name'=>'candidateccm',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getClinicalCareManager()
        ],
    ];
} else {
    $ccmcandidatestarts = [

['label'=>'Me Mode',
            'name'=>'ccmcandidatedefault',
            'type'=>'checkbox',
            'value'=>true
        ],
    [
        'label'=>'State',
        'name'=>'ccmcandidatestate',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],[
        'label'=>'Branch',
        'name'=>'ccmcandidatebranch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],[
        'label'=>'CCM',
        'name'=>'candidateccm',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getClinicalCareManager()
    ],
];
}
if (LD::checkFeatureFlag('payer-filters-on-ccm-candidate-start')) {
    $ccmcandidatestarts[] = [
        'label'=>'Payer',
        'name'=>'Payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ];
    $ccmcandidatestarts[] = [
        'label' => 'Special Payer Group',
        'name' => 'payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ];
}

$ccmcandidatestarts[] = [
    'label'=>'Candidate Start Date',
    'name'=>'candidate_start_date',
    'type'=>'dateRangePicker',
    'fromName'=>'fromCandidateStartDate',
    'toName'=>'toCandidateStartDate',
    'fromdate'=>'',
    'todate'=>'',
];
$buckets = ['first_shift_follow_up'=>'First Shift Follow Up','seven_day_follow_up'=>'7 Day Follow Up','thirty_day_follow_up'=>'30 Day Follow Up'];
?>


<div class="card-header" id="dailyactivityone">
    <h4 class="mb-0">
        <button class=" col-md-9 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
            data-target="#candidate_starts_follow_up" aria-expanded="false" aria-controls="collapseOne">Candidate Start Follow Ups<span id="candidate_starts_follow_up-counter"></span><i class="fa fa-chevron-right"></i>
        </button>
        <div class="col-md-3 text-right pull-left pr-1"><a href="{{route('ccm.getcandidatestartsall')}}" class="dashboard-showall" title="Show All Candidate Start Follow-ups">Show All Candidate Start Follow-ups</a></div>
    </h4>
</div>

<div id="candidate_starts_follow_up" class="collapse" aria-labelledby="dailyactivityone" data-parent="#clinical_case_childs">
    <div
        class="filters-common"
        data-bucket-filter="true"
        data-global-filter-fields='{"state":"ccmcandidatestate","region":"ccmcandidateregion","branch":"ccmcandidatebranch"}'
    >
        @component('components.datatable-filters',['filters'=>$ccmcandidatestarts,'formname'=>'ccmcandidatestarts'])
        @endcomponent
    </div>
    <div class="card-body">
        <?php

            foreach($buckets as $bucketKey => $bucket) {?>
                <div class="card" data-follows-global="true">
                    <div class="card-header" id="triage-<?php echo $bucketKey?>">
                        <h4 class="mb-0">
                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                data-toggle="collapse" data-target="#<?php echo $bucketKey?>" aria-expanded="false"
                                aria-controls="collapseOne">
                                <?php echo $bucket?> <span id="<?php echo $bucketKey?>-counter"></span> <i class="fa fa-chevron-right"></i>
                            </button>
                        </h4>
                    </div>
                    <div id="<?php echo $bucketKey?>" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#candidate_starts_follow_up">
                        <?php if($bucketKey == 'first_shift_follow_up') {?>
                            <div class="col-md-12 text-left bucket-subheading"> First shift follow ups that need to be completed. Anything red is  considered past due (1 day past the candidate start date).</div>
                        <?php } elseif ($bucketKey == 'seven_day_follow_up') {?>
                            <div class="col-md-12 text-left bucket-subheading">New candidate starts that have been on case for 7 days  and need a follow up completed. Anything red is considered past due (1  day past the 7 day follow up date).</div>
                        <?php }else { ?>
                            <div class="col-md-12 text-left bucket-subheading">New candidate starts that have been on case for 30 days  and need a follow up completed. Anything red is considered past due (1  day past the 30 day follow up date).</div>
                         <?php } ?>
                         @if(LD::checkFeatureFlag('add-progress-bar-in-ccm-follow-up'))
                            <div id="{{$bucketKey}}_progressbar" style="margin-top: 5px;margin-bottom: 5px;">
                            </div>
                         @endif
                        <div class="card-body">
                            <?php echo H::getHelper("CCRCandidateStarts")->getDataTableHtml($bucketKey)?>
                        </div>
                    </div>
                </div>
        <?php } ?>
    </div>
</div>

    <script>
       const ccmcandidatestarts = ['first_shift_follow_up','seven_day_follow_up','thirty_day_follow_up'];

    $(document).on('submit', '#ccmcandidatestarts', function(e) {
        e.preventDefault();
        var serializeData = $("#ccmcandidatestarts").serializeArray();
        //console.log(serializeData);
        for(var i=0; i < ccmcandidatestarts.length;i++) {
            var tempObject = ccmcandidatestarts[i].replace(/ /g,"_");
            if($("#reload-"+tempObject).val()==1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('ccm.reloadcandidatestarts') }}?type="+ccmcandidatestarts[i],
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
                $("#dataTable-ccm-candidate-starts-"+tempObject).DataTable().ajax.reload();
            }
        }
    });
    $("#ccmcandidatestarts").trigger("submit");


        $(document).on("change",'#ccmcandidatestate',function(){
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');
            <?php if(LD::checkFeatureFlag('region-filter-for-all-dashboard'))  { ?>
                if (state.indexOf('TX') !== -1) {
                    $('#ccmcandidateregion').multiselect('enable');
                } else {
                    $('#ccmcandidateregion').multiselect('disable');
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
                   $('#ccmcandidatebranch').empty();
                   $.each(response, function( index, value ) {
                       $("#ccmcandidatebranch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
                   });
                   $("#ccmcandidatebranch").multiselect("rebuild");
               },
           });
        });

    </script>
