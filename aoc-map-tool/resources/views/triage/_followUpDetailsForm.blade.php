<?php
    use App\Helpers\H;
    use App\Helpers\LD;

    $currentSymptomStatus = [];
    if(isset($followUpDetails->symptomStatus) && $followUpDetails->symptomStatus != ''){
        $currentSymptomStatus = json_decode($followUpDetails->symptomStatus,true);
    }
?>
<style>
    .form-group label{
        width: 100%;
    }
</style>
<h5>Symptom(s) Status</h5>
<div class="form-group row">
    <div class="col-md-6">
        <p class="w-100">Select the condition for the each reported symptom</p>
    </div>
    <div class="col-md-3">
        <p class="w-100">Condition</p>
    </div>
    <div class="col-md-3">
        <p class="w-100">Note(s)</p>
    </div>
</div>
<hr />

@if(LD::checkFeatureFlag('edit-triage-call'))

<?php
if(!empty($originalCallDetails->reason)) {
    $reasons = explode(",",$originalCallDetails->reason);
    $cntr = 0;
    foreach($reasons as $k => $r) {


?>
    <div class="form-group row" data-parent-id="condition_<?php echo $cntr?>">
        <div class="col-md-6">
            <input type="hidden" name="symptom[title][<?php echo $cntr?>]" id="" value="<?php echo $r?>" />
            <label for=""><?php echo $r?></label>
        </div>
        <div class="col-md-6 d-flex flex-wrap condition-section">
            <div class="col-md-6">
                <select data-label="<?php echo $r?>" name="symptom[condition][<?php echo $cntr?>]" required="true" id="condition_<?php echo $cntr?>" class="condition form-control">
                    <option value="">Select Condition</option>
                    @foreach(config('constant')['symptomCondition'] as $v)
                        <option {{isset($currentSymptomStatus['condition'][$cntr]) && $currentSymptomStatus['condition'][$cntr] == $v ? 'selected="selected"' : ''}} value="{{$v}}">{{$v}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <textarea  value="{{isset($currentSymptomStatus['note'][$cntr]) ? $currentSymptomStatus['note'][$cntr] : ''}}" name="symptom[note][<?php echo $cntr?>]" class="form-control" placeholder="Add Note..."></textarea>
            </div>
            <?php if(isset($currentSymptomStatus['additionalReason'][$cntr])){?>
                <div class="additional-reason col-md-12 mt-2">
                    <label for="">Additional Reason</label>
                    <textarea name="symptom[additionalReason][<?php echo $cntr?>]" class="form-control">{{$currentSymptomStatus['additionalReason'][$cntr]}}</textarea>
                </div>
            <?php }?>
        </div>
    </div>
<?php $cntr++; }
} ?>

<h5 class="mt-3">Outcome</h5>
<div class="form-group row">
    <div class="col-md-6">
        <label class="w-100 w-100">Suggested Disposition</label>
        <strong for="" class="w-100 pull-left"><?php echo $originalCallDetails->disposition?> <?php echo !empty($originalCallDetails->suggestedDatetime)?$originalCallDetails->suggestedDatetime:''?></strong>
    </div>
    <div class="col-md-3">
        <label class="w-100">Final Disposition</label>
        <select name="finalDisposition" id="finalDisposition" class="form-control">
            <option value="">Select Disposition</option>
            @if(LD::checkFeatureFlag('update-disposition-option-for-trige-from'))
            @foreach(config('constant')['dispositionupdated'] as $v)
                <option {{isset($followUpDetails->finalDisposition) && $followUpDetails->finalDisposition == $v ? 'selected="selected"' : ''}} value="{{$v}}">{{$v}}</option>
            @endforeach
            @else
            @foreach(config('constant')['disposition'] as $v)
                <option {{isset($followUpDetails->finalDisposition) && $followUpDetails->finalDisposition == $v ? 'selected="selected"' : ''}} value="{{$v}}">{{$v}}</option>
            @endforeach
            @endif
        </select>
    </div>
    <div class="col-md-3">
        <label class="w-100">Date of Action</label>
        <input type="text" value="{{isset($followUpDetails->despositionActionDate) ? $followUpDetails->despositionActionDate : ''}}" name="despositionActionDate" id="despositionActionDate" class="form-control"  />
    </div>
</div>

@if(LD::checkFeatureFlag('triage-followup-add-que-go-to-ed'))
    <div class="form-group row">
        <div class="col-md-6">
            <label  class="w-auto" for="">Did the client go to the ED?</label>
            <div class="custom-control custom-switch custom-switch-lg text-left">
                <input {{isset($followUpDetails->clientGoToED) && $followUpDetails->clientGoToED == 1 ? 'checked="checked"' : ''}} class="custom-control-input" type="checkbox" value="1" id="clientGoToED" name="clientGoToED"/>
                <label class="custom-control-label" for="clientGoToED"></label>
            </div>
        </div>
    </div>
@endif

<div class="form-group row">
    <div class="col-md-6">
        <label  class="w-auto" for="">Was the client hospitalized?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input {{isset($followUpDetails->wasClientHospitalized) && $followUpDetails->wasClientHospitalized == 1 ? 'checked="checked"' : ''}} class="custom-control-input" type="checkbox" value="1" id="wasClientHospitalized" name="wasClientHospitalized"/>
            <label class="custom-control-label" for="wasClientHospitalized"></label>
        </div>
    </div>
    <div class="col-md-3 wasClientHospitalized {{isset($followUpDetails->wasClientHospitalized) && $followUpDetails->wasClientHospitalized == 1 ? '' : 'hide'}}">
        <label class="w-100">Start of Hospitalization</label>
        <input value="{{isset($followUpDetails->hospitalizationStartDate) ? $followUpDetails->hospitalizationStartDate : ''}}" type="text" name="hospitalizationStartDate" id="hospitalizationStartDate" class="form-control"  />
    </div>
    <div class="col-md-3 wasClientHospitalized {{isset($followUpDetails->wasClientHospitalized) && $followUpDetails->wasClientHospitalized == 1 ? '' : 'hide'}}">
        <label class="w-100">End of Hospitalization</label>
        <input value="{{isset($followUpDetails->hospitalizationEndDate) ? $followUpDetails->hospitalizationEndDate : ''}}" type="text" name="hospitalizationEndDate" id="hospitalizationEndDate" class="form-control" />
    </div>
</div>

<div class="form-group row">
    <div class="col-md-6">
        <label  class="w-auto" for="">Was medication prescribed?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input {{isset($followUpDetails->wasMedPrescribed) && $followUpDetails->wasMedPrescribed == 1 ? 'checked="checked"' : ''}} class="custom-control-input" type="checkbox" value="1" id="wasMedPrescribed" name="wasMedPrescribed"/>
            <label class="custom-control-label" for="wasMedPrescribed"></label>
        </div>
    </div>
    <div class="col-md-3 wasMedPrescribed {{isset($followUpDetails->wasMedPrescribed) && $followUpDetails->wasMedPrescribed == 1 ? '' : 'hide'}}">
        <label class="w-100">Drug Classification</label>
        <select name="drugClassification" id="drugClassification" class="form-control">
            <option value="">Select One</option>
            @foreach(config('constant')['drugClassification'] as $v)
                <option {{isset($followUpDetails->drugClassification) && $followUpDetails->drugClassification == $v ? 'selected="selected"' : ''}} value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 wasMedPrescribed {{isset($followUpDetails->wasMedPrescribed) && $followUpDetails->wasMedPrescribed == 1 ? '' : 'hide'}}">
        <label class="w-100 w-100 pull-left mb-2">Has Medication been picked up?</label>
        <div class="form-check form-check-inline">
            <input {{isset($followUpDetails->hasMedicationPickedUp) && $followUpDetails->hasMedicationPickedUp == 1 ? 'checked="checked"' : ''}}  class="form-check-input" type="radio" name="hasMedicationPickedUp" id="hasMedicationPickedUp1" value="1" />
            <label class="form-check-label" for="hasMedicationPickedUp1">Yes</label>
        </div>
        <div class="form-check form-check-inline">
            <input {{isset($followUpDetails->hasMedicationPickedUp) && $followUpDetails->hasMedicationPickedUp == 0 ? 'checked="checked"' : ''}} class="form-check-input" type="radio" name="hasMedicationPickedUp" id="hasMedicationPickedUp0" value="0" />
            <label class="form-check-label" for="hasMedicationPickedUp0">No</label>
        </div>
    </div>
</div>
<div class="form-group row">
    <div class="col-md-4">
        <label  class="w-auto" for="">Were New Intervention Orders Received?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input {{isset($followUpDetails->wereNewOrderRecieived) && $followUpDetails->wereNewOrderRecieived == 1 ? 'checked="checked"' : ''}} class="custom-control-input" type="checkbox" value="1" id="wereNewOrderRecieived" name="wereNewOrderRecieived"/>
            <label class="custom-control-label" for="wereNewOrderRecieived"></label>
        </div>
    </div>
    <div class="col-md-3 wereNewOrderRecieived {{isset($followUpDetails->wereNewOrderRecieived) && $followUpDetails->wereNewOrderRecieived == 1 ? '' : 'hide'}}">
        <label class="w-100">Intervention Type</label>
        <select name="interventionType" id="interventionType" class="form-control">
            <option value="">Select One</option>
            @php
            $interventionType = config('constant')['interventionType'];
            sort($interventionType);
            @endphp
            @foreach($interventionType as $v)
                <option {{isset($followUpDetails->interventionType) && $followUpDetails->interventionType == $v ? 'selected="selected"' : ''}} value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 wereNewOrderRecieived {{isset($followUpDetails->wereNewOrderRecieived) && $followUpDetails->wereNewOrderRecieived == 1 ? '' : 'hide'}}">
        <label class="w-100">Follow-Up Action</label>
        <select name="followUpAction" id="followUpAction" class="form-control" multiple>
            @foreach(config('constant')['followupAction'] as $v)
                <option {{isset($followUpDetails->followUpAction) && stripos($followUpDetails->followUpAction,$v) !== false ? 'selected="selected"' : ''}} value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="wereNewOrderRecieived wasMedPrescribed row {{(isset($followUpDetails->wereNewOrderRecieived) && $followUpDetails->wereNewOrderRecieived == 1) || (isset($followUpDetails->wasMedPrescribed) && $followUpDetails->wasMedPrescribed == 1) ? '' : 'hide'}}" id="order-detail-container">
    <div class="col-md-12">
        <table  style="width:100%"  class="table table-striped" width="100%" id="order_detail_table" >
            <thead>
                <tr>
                    <th class="tbl-heading dt-nowrap">Order Number</th>
                    <th class="tbl-heading dt-nowrap">Order Detail</th>
                    <th class="tbl-heading dt-nowrap">Order Date</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<div class="form-group row wereNewOrderRecieived wasMedPrescribed pl-3 {{(isset($followUpDetails->wereNewOrderRecieived) && $followUpDetails->wereNewOrderRecieived == 1) || (isset($followUpDetails->wasMedPrescribed) && $followUpDetails->wasMedPrescribed == 1) ? '' : 'hide'}}">
    <button class="btn btn-primary" type="button" onclick="openOrderForm(event, this);">Add Order</button>

</div>

<div class="form-group row">
    <div class="col-md-3">
        <label  class="w-auto" for="">Was a diagnosis made?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input {{isset($followUpDetails->wasDiagnosisMade) && $followUpDetails->wasDiagnosisMade == 1 ? 'checked="checked"' : ''}} class="custom-control-input" type="checkbox" id="wasDiagnosisMade" value="1"  name="wasDiagnosisMade"/>
            <label class="custom-control-label" for="wasDiagnosisMade"></label>
        </div>
    </div>
    <div class="col-md-3 wasDiagnosisMade {{isset($followUpDetails->wasDiagnosisMade) && $followUpDetails->wasDiagnosisMade == 1 ? '' : 'hide'}}">
        <label class="w-100">ICD10 Code</label>
        <input type="text" value="{{isset($followUpDetails->ICDCode)  ? $followUpDetails->ICDCode : ''}}" class="form-control" name="ICDCode" id="ICDCode" placeholder="---.--- -"/>
        <a href="https://www.icd10data.com/ICD10CM/Codes" target="_blank"><small>Click here to see ICD 10 links</small></a>
    </div>
    <div class="col-md-6 wasDiagnosisMade {{isset($followUpDetails->wasDiagnosisMade) && $followUpDetails->wasDiagnosisMade == 1 ? '' : 'hide'}}">
        <label class="w-100">Diagnosis</label>
        <textarea class="form-control" name="diagnosis" id="diagnosis">{{isset($followUpDetails->diagnosis)  ? $followUpDetails->diagnosis : ''}}</textarea>
    </div>
</div>
<div class="form-group row">
    <div class="col-md-12">
        <label  class="w-auto" for="">Has a Care Coordination Note (CCN) been entered in KanTime and was CCM notified?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input {{isset($followUpDetails->caseConferenceWithCaseManager) && $followUpDetails->caseConferenceWithCaseManager == 1 ? 'checked="checked"' : ''}} class="custom-control-input" type="checkbox" id="caseConferenceWithCaseManager" value="1" name="caseConferenceWithCaseManager"/>
            <label class="custom-control-label" for="caseConferenceWithCaseManager"></label>
        </div>
    </div>
</div>
<div class="form-group row">
    <div class="col-md-6">
        <label class="w-100">Additional care coordination services needed</label>
        <select name="additionCoordination" id="additionCoordination" class="form-control" multiple>

            @foreach(config('constant')['triageCareCoordination'] as $v)
                <option {{isset($followUpDetails->additionCoordination) && stripos($followUpDetails->additionCoordination,$v) !== false ? 'selected="selected"' : ''}} value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>

        <input type="text" placeholder="Other: Please Specifiy" class="form-control hide mt-3" name="additionalCoordinationOther" id="additionalCoordinationOther" value="{{isset($followUpDetails->additionCoordination)  ? $followUpDetails->additionCoordination : ''}}" />
    </div>

</div>
<div class="form-group row">
    <div class="col-md-12">
        <label class="w-100">Any questions regarding care plan, home care services, ER warning signs/symptoms?</label>
        <input value="{{isset($followUpDetails->questionsRegardingPlan)  ? $followUpDetails->questionsRegardingPlan : ''}}" name="questionsRegardingPlan" id="questionsRegardingPlan" type="text" class="form-control" />
    </div>
</div>
@if(LD::checkFeatureFlag('add-new-survey-sent-toggle-triage-followup'))
    @if(request()->get('followupType') == '1D' || request()->get('followupType') == '7D')
        <div class="form-group row">
            <div class="col-md-12">
                <label  class="w-auto" for="survay_sent">Was the survey sent to client? (<a href="https://nam11.safelinks.protection.outlook.com/?url=https%3A%2F%2Fforms.office.com%2Fpages%2Fresponsepage.aspx%3Fid%3DIOBA6i6pkUuHQhZAy8IRbu2OXB6HW55Oq3J-YEVDbzlUMVVCM1IxTlU0REVRU044QTM2NE00UUVBMi4u&data=05%7C01%7Cappsupport%40angelsofcare.com%7Cc2b2ab267203460c2d8d08db0eb9b7f2%7Cea40e020a92e4b9187421640cbc2116e%7C0%7C0%7C638119963044906434%7CUnknown%7CTWFpbGZsb3d8eyJWIjoiMC4wLjAwMDAiLCJQIjoiV2luMzIiLCJBTiI6Ik1haWwiLCJXVCI6Mn0%3D%7C3000%7C%7C%7C&sdata=Sl%2B274Lb2jeKuxAiLCZO13GyK%2FawIPAi2bNFL4TFzz0%3D&reserved=0" target="_blank">SURVEY</a>)</label>
                <div class="custom-control custom-switch custom-switch-lg text-left">
                    <input {{isset($followUpDetails->survay_sent) && $followUpDetails->survay_sent == 1 ? 'checked="checked"' : ''}} class="custom-control-input" type="checkbox" id="survay_sent" value="1" name="survay_sent"/>
                    <label class="custom-control-label" for="survay_sent"></label>
                </div>
            </div>
        </div>
    @endif
@endif
<div class="form-group row">
    <div class="col-md-12">
        <label class="w-100">Additional Notes</label>
        <textarea name="additionNote" id="additionNote" class="form-control">{{isset($followUpDetails->additionNote)  ? $followUpDetails->additionNote : ''}}</textarea>
    </div>
</div>

@else
<?php
if(!empty($originalCallDetails->reason)) {
    $reasons = explode(",",$originalCallDetails->reason);
    $cntr = 0;
    foreach($reasons as $r) {

?>
    <div class="form-group row">
        <div class="col-md-6">
            <input type="hidden" name="symptom[title][<?php echo $cntr?>]" id="" value="<?php echo $r?>" />
            <label for=""><?php echo $r?></label>
        </div>
        <div class="col-md-3">
            <select name="symptom[condition][<?php echo $cntr?>]" required="true" id="condition_<?php echo $cntr?>" class="condition form-control">
                <option value="">Select Condition</option>
                @foreach(config('constant')['symptomCondition'] as $v)
                    <option value="{{$v}}">{{$v}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <input type="text" name="symptom[note][<?php echo $cntr?>]" class="form-control" placeholder="Add Note..." />
        </div>
    </div>
<?php $cntr++; }
} ?>

<h5 class="mt-3">Outcome</h5>
<div class="form-group row">
    <div class="col-md-6">
        <label class="w-100 w-100">Suggested Disposition</label>
        <strong for="" class="w-100 pull-left"><?php echo $originalCallDetails->disposition?> <?php echo !empty($originalCallDetails->suggestedDatetime)?$originalCallDetails->suggestedDatetime:''?></strong>
    </div>
    <div class="col-md-3">
        <label class="w-100">Final Disposition</label>
        <select name="finalDisposition" id="finalDisposition" class="form-control">
            <option value="">Select Disposition</option>
            @foreach(config('constant')['disposition'] as $v)
                <option value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="w-100">Date of Action</label>
        <input type="text" name="despositionActionDate" id="despositionActionDate" class="form-control"  />
    </div>
</div>

<div class="form-group row">
    <div class="col-md-6">
        <label  class="w-auto" for="">Was the client hospitalized?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input class="custom-control-input" type="checkbox" value="1" id="wasClientHospitalized" name="wasClientHospitalized"/>
            <label class="custom-control-label" for="wasClientHospitalized"></label>
        </div>
    </div>
    <div class="col-md-3 wasClientHospitalized hide">
        <label class="w-100">Start of Hospitalization</label>
        <input type="text" name="hospitalizationStartDate" id="hospitalizationStartDate" class="form-control"  />
    </div>
    <div class="col-md-3 wasClientHospitalized hide">
        <label class="w-100">End of Hospitalization</label>
        <input type="text" name="hospitalizationEndDate" id="hospitalizationEndDate" class="form-control" />
    </div>
</div>

<div class="form-group row">
    <div class="col-md-6">
        <label  class="w-auto" for="">Was medication prescribed?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input class="custom-control-input" type="checkbox" value="1" id="wasMedPrescribed" name="wasMedPrescribed"/>
            <label class="custom-control-label" for="wasMedPrescribed"></label>
        </div>
    </div>
    <div class="col-md-3 wasMedPrescribed hide">
        <label class="w-100">Drug Classification</label>
        <select name="drugClassification" id="drugClassification" class="form-control">
            <option value="">Select One</option>
            @foreach(config('constant')['drugClassification'] as $v)
                <option value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 wasMedPrescribed hide">
        <label class="w-100 w-100 pull-left mb-2">Has Medication been picked up?</label>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="hasMedicationPickedUp" id="hasMedicationPickedUp1" value="1" />
            <label class="form-check-label" for="hasMedicationPickedUp1">Yes</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="hasMedicationPickedUp" id="hasMedicationPickedUp0" value="0" />
            <label class="form-check-label" for="hasMedicationPickedUp0">No</label>
        </div>
    </div>
</div>
<div class="form-group row">
    <div class="col-md-4">
        <label  class="w-auto" for="">Were New Intervention Orders Received?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input class="custom-control-input" type="checkbox" value="1" id="wereNewOrderRecieived" name="wereNewOrderRecieived"/>
            <label class="custom-control-label" for="wereNewOrderRecieived"></label>
        </div>
    </div>
    <div class="col-md-3 wereNewOrderRecieived hide">
        <label class="w-100">Intervention Type</label>
        <select name="interventionType" id="interventionType" class="form-control">
            <option value="">Select One</option>
            @php
            $interventionType = config('constant')['interventionType'];
            sort($interventionType);
            @endphp
            @foreach($interventionType as $v)
                <option value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3 wereNewOrderRecieived hide">
        <label class="w-100">Follow-Up Action</label>
        <select name="followUpAction" id="followUpAction" class="form-control" multiple>
            @foreach(config('constant')['followupAction'] as $v)
                <option value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="wereNewOrderRecieived hide row" id="order-detail-container">
    <div class="col-md-12">
        <table  style="width:100%"  class="table table-striped" width="100%" id="order_detail_table" >
            <thead>
                <tr>
                    <th class="tbl-heading dt-nowrap">Order Number</th>
                    <th class="tbl-heading dt-nowrap">Order Detail</th>
                    <th class="tbl-heading dt-nowrap">Order Date</th>
                </tr>
            </thead>
            <tbody>

            </tbody>
        </table>
    </div>
</div>

<div class="form-group row wereNewOrderRecieived hide pl-3">
    <button class="btn btn-primary" type="button" onclick="openOrderForm(event, this);">Add Order</button>

</div>

<div class="form-group row">
    <div class="col-md-3">
        <label  class="w-auto" for="">Was a diagnosis made?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input class="custom-control-input" type="checkbox" id="wasDiagnosisMade" value="1"  name="wasDiagnosisMade"/>
            <label class="custom-control-label" for="wasDiagnosisMade"></label>
        </div>
    </div>
    <div class="col-md-3 wasDiagnosisMade hide">
        <label class="w-100">ICD10 Code</label>
        <input type="text" class="form-control" name="ICDCode" id="ICDCode" placeholder="---.--- -"/>
        <a href="https://www.icd10data.com/ICD10CM/Codes" target="_blank"><small>Click here to see ICD 10 links</small></a>
    </div>
    <div class="col-md-6 wasDiagnosisMade hide">
        <label class="w-100">Diagnosis</label>
        <textarea class="form-control" name="diagnosis" id="diagnosis"></textarea>
    </div>
</div>
<div class="form-group row">
    <div class="col-md-12">
        <label  class="w-auto" for="">Has a Care Coordination Note (CCN) been entered in KanTime and was CCM notified?</label>
        <div class="custom-control custom-switch custom-switch-lg text-left">
            <input class="custom-control-input" type="checkbox" id="caseConferenceWithCaseManager" value="1" name="caseConferenceWithCaseManager"/>
            <label class="custom-control-label" for="caseConferenceWithCaseManager"></label>
        </div>
    </div>
</div>
<div class="form-group row">
    <div class="col-md-6">
        <label class="w-100">Additional care coordination services needed</label>
        <select name="additionCoordination" id="additionCoordination" class="form-control" multiple>

            @foreach(config('constant')['additionalcoordinationofservices'] as $v)
                <option value="{{$v}}">{{$v}}</option>
            @endforeach
        </select>

        <input type="text" placeholder="Other: Please Specifiy" class="form-control hide mt-3" name="additionalCoordinationOther" id="additionalCoordinationOther" value="" />
    </div>

</div>
<div class="form-group row">
    <div class="col-md-12">
        <label class="w-100">Any questions regarding care plan, home care services, ER warning signs/symptoms?</label>
        <input name="questionsRegardingPlan" id="questionsRegardingPlan" type="text" class="form-control" />
    </div>
</div>
<div class="form-group row">
    <div class="col-md-12">
        <label class="w-100">Additional Notes</label>
        <textarea name="additionNote" id="additionNote" class="form-control"></textarea>
    </div>
</div>
@endif

<div class="form-group row">
    <div class="col-md-12 text-center mt-2">
        <button class="btn btn-primary" onclick="saveTriageFollowUp(event, this);">Save</button>
        <a class="copy-form-btn" onclick="copyFormToClipboard();">Copy Text from Form</a>
    </div>
</div>



