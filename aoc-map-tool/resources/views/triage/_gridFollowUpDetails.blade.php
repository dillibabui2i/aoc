<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $followTypeArray = array_map(function ($ar) {return $ar['followupType'];}, $triageFollowUpDetails);
    $titles = ['30D'=>'30 Day Follow-Up','7D'=>'7 Day Follow-Up','2D'=>'48 Hour Follow-Up','1D'=>'24 Hour Follow-Up']
?>
<section class="follow-type-tabs">
    <div class="row">
        <div class="col-md-3 mb-3">
            <ul class="nav nav-pills flex-column" id="myTab" role="tablist">
                <?php foreach($followTypeArray as $k => $v) { ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo ($k==0) ? 'active' :'' ?>" data-toggle="tab" href="#<?php echo 'i-'.$v.$data['id'];?>" role="tab" aria-controls="home"><?php echo $titles[$v];?></a>
                    </li>
                <?php } ?>
                <li class="nav-item">
                    <a class="nav-link <?php echo (empty($followTypeArray)) ? 'active' :'' ?>" data-toggle="tab" href="#original{{$data['id']}}" role="tab" aria-controls="settings">Original</a>
                </li>
            </ul>
        </div>
        <div class="col-md-9 mb-3">
            <div class="tab-content">
                <?php foreach($followTypeArray as $k => $v) {
                    $currentData = array_search($v, array_column($triageFollowUpDetails, 'followupType'));
                    $currentFollowUpDetails = $triageFollowUpDetails[$currentData];
                ?>
                    <div class="tab-pane <?php echo ($k==0) ? 'active' :'' ?>" id="<?php echo 'i-'.$v.$data['id'];?>" role="tabpanel">
                        <div class="row m-0">
                            <div class="col-md-12">
                                <strong class="w-100 pull-left">Call Information</strong>
                                <div class="w-100 pull-left pt-2 pl-3">
                                    <span class="w-100 pull-left">Reporter: {{H::getHelper('ConstantHelper')->getUserById($currentFollowUpDetails['user_id'])}}</span>
                                    <span class="w-100 pull-left">Call Date/Time: {{H::getFormatedDateTime($currentFollowUpDetails['callDate'].' '.$currentFollowUpDetails['callStartTime'])}}</span>
                                </div>
                            </div>
                            <div class="col-md-12 mt-3">
                                <strong class="w-100 pull-left">Caller Information</strong>
                                <div class="w-100 pull-left pt-2 pl-3">
                                    <span class="w-100 pull-left">{{$currentFollowUpDetails['callerFirstname'].' '.$currentFollowUpDetails['callerLastname']}}, {{$currentFollowUpDetails['callerRelationship']}}, {{$currentFollowUpDetails['callerPhone']}}, {{$currentFollowUpDetails['callerEmail']}}</span>

                                </div>
                            </div>
                            <div class="col-md-12 mt-3">
                                <strong class="w-100 pull-left">Body Systems Affected</strong>
                                <div class="w-100 pull-left pt-2 pl-3">
                                    <span class="w-100 pull-left">{{$triageDetails['bodySystem']}}</span>
                                </div>
                            </div>
                            <div class="col-md-12 mt-3">
                                <strong class="w-100 pull-left">Symptoms</strong>
                                <?php $symptomStatus = json_decode($currentFollowUpDetails['symptomStatus'],true); ?>
                                <?php for($i=0;$i<count($symptomStatus['title']); $i++){ ?>
                                    <div class="w-100 pull-left pt-2 pl-3">
                                        <span class="w-100 pull-left"><?php echo $symptomStatus['title'][$i];?></span>
                                        <?php if(isset($symptomStatus['condition'][$i]) && $symptomStatus['condition'][$i] != null) { ?>
                                            <span class="w-100 pull-left pl-3">Status: <?php echo $symptomStatus['condition'][$i];?></span>
                                        <?php }?>
                                        <?php if(isset($symptomStatus['note'][$i]) && $symptomStatus['note'][$i] != null) {?>
                                            <span class="w-100 pull-left pl-3">Note(s): <?php echo $symptomStatus['note'][$i];?></span>
                                        <?php }?>
                                    </div>
                                <?php } ?>
                            </div>
                            <div class="col-md-12 mt-3">
                                <strong class="w-100 pull-left">Outcome</strong>
                                <div class="w-100 pull-left pt-2 pl-3">
                                <span class="w-100 pull-left">Last Suggested Disposition</span>
                                <span class="w-100 pull-left pl-3">
                                    <?php echo $currentFollowUpDetails['finalDisposition']; ?> <?php echo isset($triageDetails->suggestedDatetime)?H::getFormatedDateTime($triageDetails->suggestedDatetime):''?>
                                </span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php } ?>
                <div class="tab-pane <?php echo (empty($followTypeArray)) ? 'active' :'' ?>" id="original{{$data['id']}}" role="tabpanel">
                    <div class="row m-0">
                        <div class="col-md-12">
                            <strong class="w-100 pull-left">Call Information</strong>
                            <div class="w-100 pull-left pt-2 pl-3">
                                <span class="w-100 pull-left">Reporter: {{H::getHelper('ConstantHelper')->getUserById($triageDetails->user_id)}}</span>
                                <span class="w-100 pull-left">Call Date/Time: {{H::getFormatedDateTime($triageDetails->callDate.' '.$triageDetails->callStartTime)}}</span>
                            </div>
                        </div>
                        <div class="col-md-12 mt-3">
                            <strong class="w-100 pull-left">Caller Information</strong>
                            <div class="w-100 pull-left pt-2 pl-3">
                                <span class="w-100 pull-left">{{$triageDetails->callerFirstName.' '.$triageDetails->callerLastName}}, {{$triageDetails->clientRelationship}}, {{$triageDetails->callerPhone}}, {{$triageDetails->callerEmail}}</span>

                            </div>
                        </div>
                        <div class="col-md-12 mt-3">
                            <strong class="w-100 pull-left">Body Systems Affected</strong>
                            <div class="w-100 pull-left pt-2 pl-3">
                                <span class="w-100 pull-left">{{$triageDetails->bodySystem}}</span>
                            </div>
                        </div>
                        <div class="col-md-12 mt-3">
                            <strong class="w-100 pull-left">Reason</strong>
                            <div class="w-100 pull-left pt-2 pl-3">
                                <span class="w-100 pull-left">{{$triageDetails->reason}}</span>
                            </div>
                        </div>
                        <div class="col-md-12 mt-3">
                            <strong class="w-100 pull-left">Outcome</strong>
                            <div class="w-100 pull-left pt-2 pl-3">
                            <span class="w-100 pull-left">Suggested Disposition</span>
                            <span class="w-100 pull-left pl-3">
                                <?php echo $triageDetails->disposition; ?> <?php echo isset($triageDetails->suggestedDatetime)?H::getFormatedDateTime($triageDetails->suggestedDatetime):''?>
                            </span>
                            </div>
                        </div>
                        <div class="col-md-12 mt-3">
                            <strong class="w-100 pull-left">Notes</strong>
                            <div class="w-100 pull-left pt-2 pl-3">
                                <span class="w-100 pull-left">{{$triageDetails->notes}}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</section>
