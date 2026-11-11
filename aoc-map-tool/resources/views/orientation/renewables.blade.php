<?php
use App\Helpers\H;
use App\Helpers\LD;
$typedata = [
    ['value' => 'office', 'text' => 'Office'],
    ['value' => 'field', 'text' => 'Field'],
    ['value' => 'both', 'text' => 'Both']
];
$renewablefilter = [

        [
            'label'=>'Clinician status',
            'name'=>'renewable_clinicianstatus',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRenewableClinicianStatus()
        ],[
            'label'=>'Renewable Item',
            'name'=>'renewable_items',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRenewableItems()
        ],
        [
            'label'=>'Expiration Date',
            'name'=>'renewable_expiration_date',
            'type'=>'dateRangePicker',
            'fromName'=>'fromrenewable_expiration_date',
            'toName'=>'torenewable_expiration_date',
            'fromdate'=>'',
            'todate'=>'',
        ],[
            'label'=>'',
            'name'=>'renewable_textsearch',
            'type'=>'text'
        ]


    ];

if(LD::checkFeatureFlag('add-type-field-and-filter-in-renewables-all-bucket')){
    $renewablefilter[] = [
        'label'=>'Type',
        'name'=>'type',
        'type'=>'select',
        'source'=> $typedata
    ];
}
if(LD::checkFeatureFlag('oc-dashboard-renewables-discipline-filter')) {
    $renewableDisfilter[] =
    [
        'label' => 'Discipline',
        'name' => 'renewable_discipline',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getDiscipline()
    ];
    $renewablefilter = array_merge( $renewableDisfilter, $renewablefilter );
}
?>
 <div class="card-body">
    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse"
                    data-target="#renewables" aria-expanded="false" aria-controls="collapseOne">
                    RENEWABLES <i class="fa fa-chevron-right"></i>
                </button>
                <div class="col-md-2 text-right pull-left"><a href="{{route('renewables.showAll')}}" class="dashboard-showall" title="Show All Renewables">Show All Renewables</a></div>
            </h4>
        </div>

        <div id="renewables" class="collapse" aria-labelledby="dailyactivityone" data-parent="#orientation">
            <div class="filters-common">
                @component('components.datatable-filters', ['filters' => $renewablefilter, 'formname' => 'ocrenewableform'])
                @endcomponent
            </div>
            <div class="card-body">
                <?php
                    //$buckets =  ['expiring'=>["heading"=>"Expiring"],'missing'=>["heading"=>"Missing"]];
                    $buckets =  ['expiring'=>["heading"=>"Expiring"],'missing'=>["heading"=>"Missing"]];
                    foreach($buckets as $bucketKey => $bucket) {

                        if($bucketKey=='expiring') { ?>

                            <div class="card">
                                <div class="card-header" id="renewables-<?php echo $bucketKey?>">
                                    <h4 class="mb-0">
                                        <button class="btn btn-link btn-block text-left collapsed" type="button"
                                            data-toggle="collapse" data-target="#<?php echo $bucketKey?>" aria-expanded="false"
                                            aria-controls="collapseOne">
                                            <?php echo $bucket['heading']?> <span id="<?php echo $bucketKey?>-counter"></span> <i class="fa fa-chevron-right"></i>
                                        </button>
                                    </h4>
                                </div>
                                <div id="<?php echo $bucketKey?>" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#renewables">

                                    <div class="card-body">

                                        <?php $expiringbuckets =  ['thirtyzeroexpiring'=>["heading"=>"0-30 Days & Expired"],'ninetythirtyexpiring'=>["heading"=>"31-120 Days"]]; ?>
                                        <?php foreach($expiringbuckets as $expiringbucketKey => $expiringbucket) { ?>
                                                <div class="card">
                                                    <div class="card-header" id="expiring-<?php echo $expiringbucketKey?>">
                                                        <h4 class="mb-0">
                                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                                data-toggle="collapse" data-target="#<?php echo $expiringbucketKey?>" aria-expanded="false"
                                                                aria-controls="collapseOne">
                                                                <?php echo $expiringbucket['heading']?> <span id="<?php echo $expiringbucketKey?>-counter"></span> <i class="fa fa-chevron-right"></i>
                                                            </button>
                                                        </h4>
                                                    </div>
                                                    <div id="<?php echo $expiringbucketKey?>" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#renewables-<?php echo $bucketKey?>">
                                                        <?php if($expiringbucketKey == 'thirtyzeroexpiring') { ?>
                                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Orientation Coordinators</u><br>
                                                        <b>Description:</b> This bucket includes clinicians who have renewables entered in their KanTime clinician profile with a true expiration date for that item that expires in 0-30 days. This bucket should be monitored daily to ensure compliance with agency renewable policies. Clinicians will also get notifications via email when their renewables are expiring.<br>
                                                        <?php } ?>
                                                        <div class="card-body">
                                                            <?php  echo H::getHelper("Renewables")->getDataTableHtml($expiringbucketKey)?>
                                                        </div>
                                                    </div>
                                                </div>

                                            <?php } ?>

                                        <?php // echo H::getHelper("Orientation")->getDataTableHtml($bucketKey)?>
                                    </div>
                                </div>
                            </div>





                       <?php } else {


                    ?>
                    <input type="hidden" name="reload-<?php echo $bucketKey?>" id="reload-<?php echo $bucketKey?>" value="1" />
                        <div class="card">
                            <div class="card-header" id="renewables-<?php echo $bucketKey?>">
                                <h4 class="mb-0">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse" data-target="#<?php echo $bucketKey?>" aria-expanded="false"
                                        aria-controls="collapseOne">
                                        <?php echo $bucket['heading']?> <span id="<?php echo $bucketKey?>-counter"></span> <i class="fa fa-chevron-right"></i>
                                    </button>
                                </h4>
                            </div>
                            <div id="<?php echo $bucketKey?>" class="collapse " aria-labelledby="dailyactivityone"  data-parent="#renewables">
                                <p style="text-align: left;"><u><b>Responsible Party:</b> Orientation Coordinators</u><br>
                                <b>Description:</b> This bucket includes clinicians that have missing renewables in their KanTime clinician profile. These must be addressed ASAP to ensure compliance with agency renewable policies.<br>
                                <div class="card-body">
                                <?php  echo H::getHelper("Renewables")->getDataTableHtml($bucketKey)?>
                                </div>
                            </div>
                        </div>
                        <?php } ?>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
const renewablesBucket = ["thirtyzeroexpiring",'ninetythirtyexpiring','missing'];

        $(document).on('submit', '#orientationForm,#ocrenewableform', function(e) {
            e.preventDefault();
            var sData = $("#orientationForm,#ocrenewableform").serializeArray();


            for(var i=0; i < renewablesBucket.length;i++) {
                var tempObject = renewablesBucket[i].replace(/ /g,"_");
                if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    console.log(sData);
                    $.ajax({
                        url: "{{route('renewables.reloadexpiringdata')}}?type="+renewablesBucket[i],
                        type: "POST",
                        data: {
                            filters: sData,
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
                   $("#dataTable-expiring-"+tempObject).DataTable().ajax.reload();
                }
            }

        });
</script>
