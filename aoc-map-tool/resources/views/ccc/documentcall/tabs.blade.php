<?php
$monthlyBuckets = [
    'preferences'=>'Preferences',
    'monthlyscheduling'=>'Scheduling',
    'insurance'=>'Insurance',
    'journalnotes'=>'Journal Notes',
    'nurserelation'=>'Relationship with nurses',
    'clientsatisfaction'=>'Client satisfaction',
    'callrecap'=>'Call recap',
];

$weeklyBuckets = [
    'preferences'=>'Preferences',
    'nthfields'=>'Nth Fields',
    'scheduling'=>'Scheduling',
    'insurance'=>'Insurance',
    'nurserelation'=>'Relationship with nurses',
    'clientsatisfaction'=>'Client satisfaction',
    'staffingUpdates'=>'Staffing updates',
    'callrecap'=>'Call recap',
];

$combineBucket = [
    'preferences'=>'Preferences',
    'nthfields'=>'Nth Fields',
    'combinescheduling'=>'Scheduling',
    'insurance'=>'Insurance',
    'journalnotes'=>'Journal Notes',
    'nurserelation'=>'Relationship with nurses',
    'clientsatisfaction'=>'Client satisfaction',
    'staffingUpdates'=>'Staffing updates',
    'callrecap'=>'Call recap',
];

if($isCombine)
    $accordion = $combineBucket;
else {
    if($type=='weekly')
        $accordion = $weeklyBuckets;
    if($type=='monthly')
        $accordion = $monthlyBuckets;
}


?>
<input type="hidden" name="followupId" id="followupId" value="{{$followupId}}" />
<input type="hidden" name="monthlyCallId" id="monthlyCallId" value="{{$monthlyCallId}}" />
<input type="hidden" name="weeklyCallId" id="weeklyCallId" value="{{$weeklyCallId}}" />
<input type="hidden" name="followupType" id="followupType" value="{{$type}}" />

<section class="followup-documentcall pt-3  pl-0 hide" id="document_container">
    <div class="accordion" id="followuptabs">
       <?php $cntr=2; foreach($accordion as $key=>$acc) { 
            if($key=='journalnotes' && count($journalNotes)==0){
                continue;
            }
        ?> 
            <div class="card">
                <div class="card-header" id="tab_<?php echo $cntr?>">
                    <h2 class="mb-0">
                    <button class="btn btn-link btn-block text-left collapsed" type="button" data-toggle="collapse" data-target="#followtab-<?php echo $key?>" aria-expanded="false" aria-controls="collapseTwo">
                        <?php echo $acc?>
                    </button>
                    </h2>
                </div>
                <div id="followtab-<?php echo $key?>" class="collapse" aria-labelledby="tab_<?php echo $cntr?>" data-parent="#followuptabs">
                    <div class="card-body">
                        @include('ccc.documentcall.tabs.'.$key)
                    </div>
                </div>
            </div>
        <?php }?>
    </div>
</section>
