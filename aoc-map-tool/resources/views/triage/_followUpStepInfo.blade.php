<?php 
use App\Helpers\H;
$loadData = isset($followupData[$type])?$followupData[$type]:[];
if(!empty($loadData)) {
?>

<div class="original-info-container">
    <div class="form-group row">
        <div class="col-md-12">
            <strong>Caller Informantion</strong>
            <p class="m-0 p-0 pl-2 pt-2">{{$loadData->callerFirstname}} {{$loadData->callerLastname}}, {{$loadData->callerPhone}}, {{$loadData->callerEmail}} </p>
        </div>

        <div class="col-md-12 mt-3">
            <strong>Body System(s) Affected</strong>
            <p class="m-0 p-0 pl-2 pt-2"><?php echo str_replace(",",", ",$originalCallDetails->bodySystem)?></p>
        </div>

        <div class="col-md-12 mt-3">
           <strong>Symptoms:</strong>
           <div class="col-md-12 m-0 p-0 pl-2 pt-2">
                <?php
                    $symptomStatus = json_decode($loadData->symptomStatus,true);

                    for($i=0; $i<count($symptomStatus['title']);$i++) { ?>

                        <p class="m-0 p-0"><?php echo $symptomStatus['title'][$i]?></p>
                        <?php if(!empty($symptomStatus['condition'][$i])) { ?>
                            <p class="m-0 p-0 pl-4">Status:<?php echo $symptomStatus['condition'][$i]?></p>
                        <?php }?> 

                        <?php if(!empty($symptomStatus['note'][$i])) { ?>
                            <p class="m-0 p-0  pl-4">Note(s):<?php echo $symptomStatus['note'][$i]?></p>
                        <?php }?> 
                    <?php } ?>
            </div>
        </div>

        <div class="col-md-12 mt-3"><strong>Outcome:</strong></div>

        <div class="col-md-6 m-0 p-0 pl-4 pt-2 pull-left">
            
            <p class="m-0 p-0"><strong>Original Suggested Disapoistion:</strong></p>
            <p class="m-0 p-0 pl-4">
                <?php echo $originalCallDetails->disposition?> <?php echo !empty($originalCallDetails->suggestedDatetime)?H::getFormatedDateTime($originalCallDetails->suggestedDatetime):''?>
            </p>

            <p class="m-0 p-0 pt-2"><strong>Actual Disapoistion:</strong></p>
            <p class="m-0 p-0 pl-4">
                <?php echo $loadData->finalDisposition?>
            </p>


            <p class="m-0 p-0 pt-2"><strong>Hospitalization:</strong></p>
            <p class="m-0 p-0 pl-4">
                <?php
                    if($loadData->wasClientHospitalized=='1')  {
                        echo "Yes";
                    } else {
                        echo "No";
                    } ?>
            </p>
            <?php if(!empty($loadData->hospitalizationStartDate)){?> 
            <p class="m-0 p-0 pl-4">Start:<?php echo !empty($loadData->hospitalizationStartDate)?H::getFormatedDateTime($loadData->hospitalizationStartDate):''?></p>
            <?php } ?>

            <?php if(!empty($loadData->hospitalizationEndDate)){?> 
            <p class="m-0 p-0 pl-4">End:<?php echo !empty($loadData->hospitalizationEndDate)?H::getFormatedDateTime($loadData->hospitalizationEndDate):''?></p>
            <?php } ?>


            <p class="m-0 p-0 pt-2"><strong>Medication:</strong></p>
            <?php if($loadData->wasMedPrescribed=='1')  { ?>
                <p class="m-0 p-0 pl-4">
                    <?php echo "Yes :". $loadData->drugClassification; ?>
                </p>
            <?php } ?>
            <p class="m-0 p-0 pl-4">
                Has Medication been Picked up?
            <?php if($loadData->hasMedicationPickedUp=='1')  {
                    echo "Yes";
                 } else {
                    echo "No";
                 } ?>
            </p>

            <?php if(!empty($loadData->questionsRegardingPlan)) { ?> 
            <p class="m-0 p-0 pt-2"><strong>Question from Caller:</strong></p>
            <p class="m-0 p-0">
                <?php echo  $loadData->questionsRegardingPlan; ?>
            </p>
            <?php } ?>

            <?php
            if(!empty($loadData->additionNote)) { ?> 
                <p class="m-0 p-0 pt-2">
                    <strong>Additional Notes from Reporter:</strong>
                </p>
                <p class="m-0 p-0">
                <?php echo  $loadData->additionNote; ?>
                </p>
            <?php } ?>


        </div>

        <div class="col-md-6 mt-3 pull-left">
        
            <p class="m-0 p-0"><strong>New Order Recieved:</strong></p>
            <p class="m-0 p-0 pl-4">
            <?php if($loadData->wereNewOrderRecieived=='1')  {
                    echo "Yes";
                 } else {
                    echo "No";
                 } ?>
                
            </p>
            <p class="m-0 p-0"><?php echo $loadData->followUpAction?></p>

            <p class="m-0 p-0 pt-2"><strong>Diagnosis Made:</strong></p>
            <p class="m-0 p-0 pl-4">
            <?php if($loadData->wasDiagnosisMade=='1')  {
                    echo "Yes";
                 } else {
                    echo "No";
                 } ?>
                
            </p>
            <p class="m-0 p-0 pl-4"><?php echo $loadData->ICDCode?></p>

            <p class="m-0 p-0 pt-2"><strong>Additional Service Needed:</strong></p>
            <p class="m-0 p-0 pl-4"><?php echo $loadData->additionCoordination?></p>

            <p class="m-0 p-0 pt-2"><strong>Case Conference Completed with Case Manager:</strong></p>
            <p class="m-0 p-0 pl-4">
                <?php if($loadData->caseConferenceWithCaseManager=='1')  {
                    echo "Yes";
                 } else {
                    echo "No";
                 } ?>
            </p>
        
        </div> 
    </div>
</div>
<?php } ?>