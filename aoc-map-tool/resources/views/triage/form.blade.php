<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $loggedInUser = H::getLoggedInUser();
    $reason = H::getHelper('ConstantHelper')->getTriageReason();
    if(isset($data)){
        $bodyoption = explode(",", $data->bodySystem);
        $reasonoption = explode(",", $data->reason);
        $careoption = explode(",", $data->careCoordination);
        $followUpAction = explode(",", $data->followUpAction);
    }
    $currentDate = date('m/d/Y');
    $currentTime = date('H:i');
?>
<style>
    #disposition-error{
        order: 2;
    }
</style>
<x-modal>
    <x-slot name="id">addTriageCallFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">{{isset($data) ? 'Edit Incoming Call Ticket Form' : 'Incoming Call Ticket Form'}}</x-slot>
    <x-slot name="class">add-triage-call-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="addTriageCallForm" class="addTriageCallForm">
        <input type="hidden" name="trige_id" id="trige_id" value="{{isset($data) ? $data->id : ''}}">
        <section class="triage-details">
            <div class="form-group row">
                <div class="col-md-4">
                    <label  class="w-100" for="user_id">Reporter</label>
                    <input type="hidden" class="form-control" id="user_id" name="user_id" value="{{isset($data) ? $data->user_id : $loggedInUser['id']}}"/>
                    <input type="text" class="form-control disabled" id="reporter" name="reporter" value="{{isset($data) ? H::getUserNameById($data->user_id) : $loggedInUser['name']}}" disabled/>
                </div>
                <div class="col-md-4">
                    <label  class="w-100" for="callDate">Date</label>
                    <input type="text" class="form-control datepicker disabled" id="callDate" name="callDate" value="{{isset($data) ? date('m/d/Y', strtotime($data->callDate)) : $currentDate}}" disabled/>

                </div>
                <div class="col-md-4">
                    <label  class="w-100" for="callStartTime">Call Start Time</label>
                    <input type="time" class="form-control disabled" id="callStartTime" name="callStartTime" value="{{isset($data) ? $data->callStartTime : $currentTime}}" disabled/>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-3">
                    <label  class="w-100" for="clientRelationship">Relationship to Client</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="clientRelationship" id="clientRelationshipParent" value="Parent"  {{(isset($data) && $data->clientRelationship == 'Parent') ? 'checked' : ''}}/>
                        <label class="form-check-label" for="clientRelationshipParent">Parent</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="clientRelationship" id="clientRelationshipNurse" value="Nurse"  {{(isset($data) && $data->clientRelationship == 'Nurse') ? 'checked' : ''}}/>
                        <label class="form-check-label" for="clientRelationshipNurse">Nurse</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="clientRelationship" id="clientRelationshipAuthorizedCaller" value="Authorized Caller"  {{(isset($data) && $data->clientRelationship == 'Authorized Caller') ? 'checked' : ''}}/>
                        <label class="form-check-label" for="clientRelationshipAuthorizedCaller">Authorized Caller</label>
                    </div>
                    @if(LD::checkFeatureFlag('pull-information-from-kantime-triage-incoming-call-form'))
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="clientRelationship" id="clientRelationshipUnauthorizedCaller" value="Unauthorized Caller"   {{(isset($data) && $data->clientRelationship == 'Unauthorized Caller') ? 'checked' : ''}}/>
                        <label class="form-check-label" for="clientRelationshipUnauthorizedCaller">Unauthorized Caller</label>
                    </div>
                    @endif
                </div>
                <div class="col-md-3">
                    <label  class="w-100" for="callerFirstName">Caller First Name</label>
                    <input type="text" class="form-control" id="callerFirstName" name="callerFirstName" value="{{ isset($data) ? $data->callerFirstName : ''}}"/>
                    @if(LD::checkFeatureFlag('pull-information-from-kantime-triage-incoming-call-form'))
                    <input type="hidden" class="form-control" id="aoc_provider_id" name="aoc_provider_id" value="{{ isset($data) ? $data->aoc_provider_id : ''}}"/>
                    @endif
                </div>
                <div class="col-md-3">
                    <label  class="w-100" for="callerLastName">Caller Last Name</label>
                    <input type="text" class="form-control" id="callerLastName" name="callerLastName" value="{{ isset($data) ? $data->callerLastName : ''}}"/>
                </div>
                <div class="col-md-3">
                    <label  class="w-100" for="callerPhone">Caller Phone Number</label>
                    <input type="text" class="form-control" id="callerPhone" name="callerPhone" value="{{ isset($data) ? $data->callerPhone : ''}}"/>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-3">
                    <label  class="w-100" for="callerEmail">Caller Email Address</label>
                    <input type="text" class="form-control" id="callerEmail" name="callerEmail" value="{{ isset($data) ? $data->callerEmail : ''}}"/>
                </div>
                <div class="col-md-3">
                    <label  class="w-100" for="aoc_patient_id">Client Name</label>
                    <input type="text" class="form-control" id="clientName" name="clientName" value="{{ isset($data) ? $data->clientName : ''}}"/>
                    <input type="hidden" class="form-control" id="aoc_patient_id" name="aoc_patient_id" value="{{ isset($data) ? $data->aoc_patient_id : ''}}"/>
                    <div id="clientWarningMsg" class="alert alert-warning mt-3 pl-1 pr-1 pt-2 pb-2 hide" role="alert"> <i class="fa fa-exclamation-triangle"></i> We found <span id="clientCallCount"></span> other Triage Calls for this Client. <a href="javascript:void();" id="clientAllCallsExtLink"><i class="fa fa-external-link pull-right"></i></a></div>
                </div>
                <div class="col-md-3 client-birth-date">
                    <label  class="w-100" for="clientDOB">Client Date Of Birth</label>
                    <small class="muted">*Confirm DOB to verify caller</small>
                    <input type="text" class="form-control datepicker" id="clientDOB" name="clientDOB" value="{{isset($patientDetails) && isset($patientDetails->Patient_DOB) ? H::getFormatedDate($patientDetails->Patient_DOB) : ''}}"/>
                </div>
                <div class="col-md-3">
                    <label  class="w-100" for="terminalPlayer">Terminal Payer</label>
                    <input type="text" class="form-control" id="terminalPlayer" name="terminalPlayer" value="{{ isset($data) ? $data->terminalPlayer : ''}}"/>
                </div>
            </div>
            @if(LD::checkFeatureFlag('add-client-id-and-client-location-triage-form'))
                <div class="form-group row">
                    <div class="col-md-3">
                        <label  class="w-100" for="client_id">Client ID</label>
                        <input type="text" class="form-control" id="client_id" name="client_id" value=""/>
                    </div>
                    <div class="col-md-3">
                        <label  class="w-100" for="client_location">Client Location</label>
                        <input type="text" class="form-control" id="client_location" name="client_location" value=""/>
                    </div>
                </div>
            @endif
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-100" for="bodySystem">Body System(s) Involved</label>
                    <select class="form-control" name="bodySystem" id="bodySystem" placeholder="Select Body System" multiple>
                        @foreach(config('constant')['bodySystem'] as $v)
                        <option value="{{$v}}" {{ (isset($data) && in_array($v, $bodyoption)) ? 'selected' : '' }}>{{$v}}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <?php
                if(!empty(env("CLEARTRIAGE_SSO_TOKEN")) && !empty(env("CLEARTRIAGE_BASE_URL")) ) { ?>

                <div class="form-group row">
                    <div class="col-md-12">
                        <?php  env("CLEARTRIAGE_SSO_TOKEN")?>
                        <button type="button" class="btn opencleartriage" id="open-ct-btn" onclick="openClearTriage()">Open ClearTriage</button>&nbsp;&nbsp;<button type="button" class="btn hidecleartriage" id="closetriage" disabled="disabled" onclick="closeClearTriage()">Close ClearTriage</button>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-12" id="iframecontainer" style="display: none;">

                    </div>
                </div>
                <?php } ?>



            <div class="form-group row">
                <div class="col-md-12">
                    <label  class="w-100" for="description">Description</label>
                    <textarea class="form-control" name="description" id="description">{{ isset($data) ? $data->description : ''}}</textarea>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-4">
                    <label  class="w-100" for="reason">Reason</label>
                    <select class="form-control" name="reason" id="reason" multiple>
                        @foreach($reason as $k => $v)
                        <option value="{{$v['reason']}}" {{ (isset($data) && in_array($v['reason'], $reasonoption)) ? 'selected' : '' }}>{{$v['reason']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label  class="w-100" for="disposition">Disposition</label>
                    <select class="form-control" name="disposition" id="disposition">
                        <option value="">--- Select ----</option>
                        @if(LD::checkFeatureFlag('update-disposition-option-for-trige-from'))
                            @foreach(config('constant')['dispositionupdated'] as $v)
                            <option value="{{$v}}" {{ (isset($data) && $data->disposition == $v) ? 'selected' : ''}}>{{$v}}</option>
                            @endforeach
                        @else
                            @foreach(config('constant')['disposition'] as $v)
                            <option value="{{$v}}">{{$v}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-4">
                    <label  class="w-100" for="careCoordination">Care Coordination</label>
                    <select class="form-control" name="careCoordination" id="careCoordination" multiple placeholder="Select Care Coordination">
                        @if(LD::checkFeatureFlag('add-mew-triage-Care-Coordination-option-in-form'))
                            @foreach(config('constant')['newTriageCareCoordination'] as $v)
                                <option value="{{$v}}" {{ (isset($data) && in_array($v, $careoption)) ? 'selected' : '' }}>{{$v}}</option>
                            @endforeach
                        @else
                            @foreach(config('constant')['triageCareCoordination'] as $v)
                                <option value="{{$v}}" {{ (isset($data) && in_array($v, $careoption)) ? 'selected' : '' }}>{{$v}}</option>
                            @endforeach
                        @endif
                    </select>
                </div>
                <div class="col-md-4 isDisposition hide">
                    <label  class="w-100" for="isAmbulanceTransfer">Ambulance Transfer</label>
                    <div class="custom-control custom-switch custom-switch-lg text-left">
                        <input class="custom-control-input" type="checkbox" id="isAmbulanceTransfer" name="isAmbulanceTransfer" {{ (isset($data) && $data->isAmbulanceTransfer == '1') ? 'checked' : ''}}/>
                        <label class="custom-control-label" for="isAmbulanceTransfer"></label>
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-3 isDisposition hide">
                    <label  class="w-100" for="suggestedTimeframe">Suggested Timeframe</label>
                    <input class="form-control" type="text" name="suggestedDatetime" id="suggestedDatetime" {{isset($data) ? $data->suggestedDatetime : ''}}/>
                </div>
                <!-- <div class="col-md-3 isDisposition hide">
                    <label  class="w-100" for="">Suggested Date/Time</label>
                    <strong id="suggestedDatetime"> </strong>
                </div> -->
            </div>
            @if (LD::checkFeatureFlag('triage-incoming-call-form-changes'))
            <div class="form-group row">
                <div class="col-md-4">
                    <label  class="w-auto" for="">Was medication prescribed?</label>
                    <div class="custom-control custom-switch custom-switch-lg text-left">
                        <input class="custom-control-input" type="checkbox" value="1" id="wasMedPrescribed" name="wasMedPrescribed" {{(isset($data) && $data->wasMedPrescribed == '1') ? 'checked' : ''}}/>
                        <label class="custom-control-label" for="wasMedPrescribed"></label>
                    </div>
                </div>
                <div class="col-md-4 wasMedPrescribed hide">
                    <label class="w-100">Drug Classification</label>
                    <select name="drugClassification" id="drugClassification" class="form-control">
                        <option value="">Select One</option>
                        @foreach(config('constant')['drugClassification'] as $v)
                            <option value="{{$v}}" {{(isset($data) && $data->drugClassification == $v) ? 'selected' : ''}}>{{$v}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4 wasMedPrescribed hide">
                    <label class="w-100 w-100 pull-left mb-2">Has Medication been picked up?</label>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="hasMedicationPickedUp" id="hasMedicationPickedUp1" value="1" {{(isset($data) && $data->hasMedicationPickedUp == 1) ? 'checked' : ''}}/>
                        <label class="form-check-label" for="hasMedicationPickedUp1">Yes</label>
                    </div>
                    <div class="form-check form-check-inline" id="hasMedicationPickedUpNo">
                        <input class="form-check-input" type="radio" name="hasMedicationPickedUp" id="hasMedicationPickedUp0" value="0" {{(isset($data) && $data->hasMedicationPickedUp == 0) ? 'checked' : ''}}/>
                        <label class="form-check-label" for="hasMedicationPickedUp0">No</label>
                    </div>
                </div>
            </div>
            @else
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-100" for="emergencyRoom">If you didn't talk to us, would you have gone to the Emergency Room?</label>
                    <div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="emergencyRoom" name="emergencyRoom" {{(isset($data) && $data->emergencyRoom == 1) ? 'checked' : ''}}/>
                        <label class="custom-control-label" for="emergencyRoom"></label>
                    </div>
                </div>
                <div class="col-md-6 emergencyRoom hide">
                    <label  class="w-100" for="whereYouGone">Where would you have gone?</label>
                    <input type="text" class="form-control" id="whereYouGone" name="whereYouGone" value="{{isset($data) ? $data->whereYouGone : ''}}"/>
                </div>
            </div>
            @endif
            @if (LD::checkFeatureFlag('add-order-detail-field-to-triage-incoming-call-form'))
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-auto" for="">Were New Intervention Orders Received?</label>
                    <div class="custom-control custom-switch custom-switch-lg text-left">
                        <input class="custom-control-input" type="checkbox" value="1" id="wereNewOrderRecieived" name="wereNewOrderRecieived"{{ (isset($data) && $data->wereNewOrderRecieived == 1) ? 'checked' : '' }} />
                        <label class="custom-control-label" for="wereNewOrderRecieived"></label>
                    </div>
                </div>
                <div class="col-md-6 wereNewOrderRecieived hide">
                    <label class="w-100">Intervention Type</label>
                    <select name="interventionType" id="interventionType" class="form-control">
                        <option value="">Select One</option>
                        @php
                        $interventionType = config('constant')['interventionType'];
                        sort($interventionType);
                        @endphp
                        @foreach($interventionType as $v)
                            <option value="{{$v}}" {{(isset($data) && $data->interventionType == $v) ? 'selected' : ''}}>{{$v}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-3 wereNewOrderRecieived hide">
                    <label class="w-100">Follow-Up Action</label>
                    <select name="followUpAction" id="followUpAction" class="form-control" multiple>
                        @foreach(config('constant')['followupAction'] as $v)
                            <option value="{{$v}}" {{ (isset($data) && in_array($v, $followUpAction)) ? 'selected' : '' }}>{{$v}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if (LD::checkFeatureFlag('incoming-call-form-changes-triage'))
                <div class="wereNewOrderRecieived wasMedPrescribed hide row" id="order-detail-container">
            @else
                <div class="wereNewOrderRecieived hide row" id="order-detail-container">
            @endif
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
            @if (LD::checkFeatureFlag('incoming-call-form-changes-triage'))
                <div class="form-group row wereNewOrderRecieived wasMedPrescribed hide pl-3">
            @else
                <div class="form-group row wereNewOrderRecieived hide pl-3">
            @endif
                <button class="btn btn-primary" type="button" onclick="openOrderForm(event, this);">Add Order</button>
            </div>
            @endif
            @if (LD::checkFeatureFlag('triage-incoming-call-form-changes'))
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-100" for="emergencyRoom">If you didn't talk to us, would you have gone to the Emergency Room?</label>
                    <div class="custom-control custom-switch custom-switch-lg text-left">
                        <input class="custom-control-input" type="checkbox" id="emergencyRoom" name="emergencyRoom" {{(isset($data) && $data->emergencyRoom) ? 'checked' : ''}}/>
                        <label class="custom-control-label" for="emergencyRoom"></label>
                    </div>
                </div>
            </div>
            <div class="form-group row emergencyRoom hide">
                <div class="col-md-6">
                    <label class="w-100">Where would you have gone?</label>
                    @if(isset($data) && !empty($data->emergencyDepartment))
                        <select name="emergencyDepartment" id="emergencyDepartment" class="form-control">
                            <option value="{{$data->emergencyDepartment}}" selected>{{$data->emergencyDepartment}}</option>
                        </select>
                    @else
                        <select name="emergencyDepartment" id="emergencyDepartment" class="form-control">
                        </select>
                    @endif
                </div>
                <div class="col-md-6 emergencyDepartment hide">
                    <label  class="w-100" for="emergencyDepartmentOther">Details</label>
                    <input type="text" class="form-control" id="emergencyDepartmentOther" name="emergencyDepartmentOther" value=""/>
                </div>
            </div>
            @endif
            @if (LD::checkFeatureFlag('triage-add-notes'))
            <div class="form-group row">
                <div class="col-md-12">
                    <label  class="w-100" for="notes">Notes</label>
                    <textarea class="form-control" name="notes" id="notes">{{isset($data) ? $data->notes : ''}}</textarea>
                </div>
            </div>
            @endif
        </section>
        @if(isset($data))
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button class="btn btn-primary triage-update-btn" onclick="editTriage(event, this);">Update</button>
                <a class="copy-form-btn" onclick="copyFormToClipboard();">Copy Text from Form</a>
            </div>
        </div>
        @else
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button class="btn btn-primary" onclick="saveTriage(event, this);">Submit</button>
                <a class="copy-form-btn" onclick="copyFormToClipboard();">Copy Text from Form</a>
            </div>
        </div>
        @endif

    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

@if(H::canPermissions(['edit-triage-call-date']))
<x-modal>
    <x-slot name="id">changeTriageDateTime</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="title">Change Triage Call Date and Time</x-slot>
    <x-slot name="isCloseBtn">false</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <form id="changeTriageDateTimeForm">
            <input type="hidden" name="triage_id" id="triage_id" value="{{isset($data) ? $data->id : ''}}">
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-100" for="reporter_id">Reporter</label>
                    <input type="text" class="form-control" id="reporter_id" name="reporter_id" value="{{isset($data) ? H::getUserNameById($data->user_id) : $loggedInUser['name']}}"/>
                    <input type="hidden" class="form-control" id="reporter_user_id" name="user_id" value="{{isset($data) ? $data->user_id : $loggedInUser['id']}}"/>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="changeCallDate">Date</label>
                    <input type="text" class="form-control datepicker" id="changeCallDate" name="changeCallDate" value="{{isset($data) ? date('m/d/Y', strtotime($data->callDate)) : $currentDate}}"/>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="changeCallStartTime">Call Start Time</label>
                    <input type="time" class="form-control" id="changeCallStartTime" name="changeCallStartTime" value="{{isset($data) ? $data->callStartTime : $currentTime}}" />
                </div>
            </div>
        </form>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-primary" id="saveChangeTriageDateTime" data-id="">Save</button>
        <button type="button" class="btn btn-primary" id="cancelChangeTriageDateTime" data-id="">Cancel</button>
    </x-slot>
</x-modal>
@endif

@if (LD::checkFeatureFlag('add-order-detail-field-to-triage-incoming-call-form'))
<x-modal>
    <x-slot name="id">order_details_form</x-slot>
    <x-slot name="size">w30</x-slot>
    <x-slot name="title">Add Order</x-slot>
    <x-slot name="class">order-details-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
        <form name="order-details-form" id="order-details-form">
            <div class="col-md-12 order-details-container">
                <div class="col-md-12">
                    <label class="w-100">Order Number</label>
                    <input type="text" class="form-control" required="true" name="order_number_input" id="order_number_input" value=""/>
                </div>
                <div class="col-md-12 pt-3">
                    <label class="w-100">Details</label>
                    <textarea name="order_details_input" id="order_details_input" required="true" class="form-control"></textarea>
                </div>

                <div class="col-md-12 pt-3">
                    <button class="btn btn-primary" type="button" onclick="saveOrderDetails(event, this);">Save</button>
                </div>
            <div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
@endif

<script>
    
    var iframe = null;
    var iframeContainer = null;
    var frameCounter = 0;

    @if (!empty(env("CLEARTRIAGE_SSO_TOKEN")) && !empty(env("CLEARTRIAGE_BASE_URL")))

    var ctBaseUrl = '<?php echo env("CLEARTRIAGE_BASE_URL")?>';
    var ctLoginToken = '<?php echo env("CLEARTRIAGE_SSO_TOKEN");?>';
    var firstName = "<?php echo $loggedInUser['name']?>";
   
   
    
    function openClearTriage() {
        $("#open-ct-btn").prop('disabled', true);
        $("#closetriage").prop('disabled', false);

        if(frameCounter==0) {
            iframe = document.createElement('iframe');
            iframeContainer = document.querySelector('#iframecontainer');
            //iframeContainer.removeChild(iframe);
            iframe.src = ctBaseUrl + '/app/login/sso/' + ctLoginToken + '?firstName=' + firstName;
            iframe.id = 'ct-iframe';
            iframe.width = "100%";
            iframe.height = 700;
            iframeContainer.appendChild(iframe);
            frameCounter++;

            function triageResponseGetter(event) {
                
                ESSLog.log('Clear Triage for('+firstName+'):'+JSON.stringify(event.data),"cleartriage.log",function(){});
                if (event.data && event.data.subject) {
                    // console.log(event.data);
                    switch (event.data.subject) {
                        case 'ct-ready':
                        initSession();
                        break;
                        case 'ct-init-err':
                        handleInitError(event.data);
                        break;
                        case 'ct-call-summary':
                        handleCtCallSummary(event);
                    }
                }
            }
           
          //  window.removeEventListener('message', triageResponseGetter,{useCapture:false, passive:false, once:false});
            if(!isTriageEventRegistered) {
                window.addEventListener('message',triageResponseGetter,{useCapture:false, passive:false, once:false});
                isTriageEventRegistered = true;
            }
            
            // Initialize a session with ClearTriage
            function initSession() {
                iframe.contentWindow.postMessage({
                    subject: 'ct-init',
                }, ctBaseUrl);

                ESSLog.log('Clear Triage Ready',"cleartriage.log",function(){});
            }
            // Handle an initialization error from ClearTriage. This can happen if your origin URL is not whitelisted on your account.
            function handleInitError(eventData) {
                console.log('ct-init error: ' + eventData.messageBody);
                ESSLog.log('Clear Triage Error: '+eventData.messageBody,"cleartriage.log",function(){});
            }
            // Handle successful return of a call summary from ClearTriage
            function handleCtCallSummary(event) {
                if (event.origin === ctBaseUrl) {
                    var jsonData = event.data;
                    //console.log(jsonData);
                var callSummary = jsonData.messageBody.callSummary;
                var disposition  = callSummary.callDisposition.title;
                var reasons = callSummary.protocols;
                var reasonTitle = [];
                $("#reason").multiselect("clearSelection");
                $("#disposition").multiselect("clearSelection");
                for(var i = 0; i< reasons.length; i++) {
                    reasonTitle[i] = reasons[i].title;
                    //$('#reason option:contains("'+reasons[i].title+'")').prop('selected',true);

                }

                var rsTitle = [];
                var cntr = 0;
                $("select[name=reason] option").each(function(i,v) {
                    for(var rt=0; rt < reasonTitle.length;rt++) {
                        if($(this).val().toLowerCase()==reasonTitle[rt].toLowerCase()) {
                            rsTitle[cntr] = $(this).val();
                            cntr++;
                        }
                    }

                    //$("#container").append(this.value + ' ');
                });
                var selectDisp = '';
                $("select[name=disposition] option").each(function(i,v) {
                    if($(this).val().toLowerCase()==disposition.toLowerCase()) {
                        selectDisp = $(this).val();
                    }
                });

                $("select[name=reason]").val(rsTitle);
                $("select[name=disposition]").val(selectDisp);

                $("#reason").multiselect("rebuild");
                $("#disposition").multiselect("rebuild");

                $("#description").val(jsonData.messageBody.callSummaryText);

                closeClearTriage();
                $("#iframecontainer").slideUp();
                //   console.log(reasonTitle);
                //    console.log(disposition);
                //  console.log(callSummary);
                // callSummary.innerHTML = JSON.stringify(event.data);
                }
                else {
                console.log('ct-call-summary broadcast from unexpected origin: ' + event.origin);
                }
            }
        }
        $("#iframecontainer").slideDown();
       
    }

    function closeClearTriage() {
     /* var iframe = document.querySelector('#ct-iframe')

      var iframeContainer = document.querySelector('#iframecontainer')
      iframeContainer.style.visibility = 'hidden'
      iframeContainer.style.display = 'none'
      iframe.style.visibility = 'hidden'
      iframe.style.display = 'none'

      iframeContainer.removeChild(iframe);*/
      $("#iframecontainer").slideUp();
      $("#open-ct-btn").prop('disabled', false);
      $("#closetriage").prop('disabled', true);
     // openCtButton.style.display = 'block';
      //$("#open-ct-btn").show();
    }

   

    @endif


    @if (LD::checkFeatureFlag('triage-incoming-call-form-changes'))
    function copyFormToClipboard() {
        var str = '';
        let elements = [
            'reporter|text',
            'callDate|text',
            'callStartTime|text',
            'clientRelationship|radio',
            'callerFirstName|text',
            'callerLastName|text',
            'callerPhone|text',
            'callerEmail|text',
            'clientName|text',
            'clientDOB|text',
            'terminalPlayer|text',
            'bodySystem|text',
            'description|text',
            'reason|multiselect',
            'disposition|multiselect',
            'careCoordination|multiselect',
            'wasMedPrescribed|checkbox',
            'drugClassification|select',
            'hasMedicationPickedUp|radio',
            'wereNewOrderRecieived|checkbox',
            'interventionType|select',
            'followUpAction|multiselect',
            'orderDetail|table',
            'emergencyRoom|checkbox',
            'emergencyDepartment|multiselect',
            'notes|text'
        ]
        $.each ( elements, function (index, elementName) {
            let elementInput = elementName.split('|');
            let elm = '#addTriageCallForm [name="' + elementInput[0] + '"]';

            if ($(elm).length > 0 && !$(elm).is(':visible') && (elementInput[0] != 'bodySystem' || elementInput[0] != 'orderDetail')) {
                return;
            }

            if ($(elm).attr('id') == 'bodySystem') {
                str += 'Body System(s) Involved'+': '+$(elm).val() + '\n';
                return;
            }

            if (elementInput[1] == 'multiselect') {
                str += $(elm).parent().parent().find('label').html()+': '+$(elm).val() + '\n';
            } else if (elementInput[1] == 'radio' || elementInput[1] == 'checkbox') {
                str += $(elm).parent().parent().find('label').html()+': ';
                str += (($(elm).filter(":checked").val() != undefined) ? $(elm).filter(":checked").val() : '') + '\n';
            } else if (elementInput[1] == 'table' && $('#wasMedPrescribed').is(':checked')) {
                str += 'Order Items'+': ' + '\n';
                str += 'Order Number'+'\t\t Order Detail\t\t Order Date' + '\n';
                jQuery.each(jsonHiddenAddedData, function(index, item) {
                    str += item['order_number']+'\t\t ' + item['order_detail'] + '\t\t '+ item['order_date'] + '\n';
                });
            } else {
                if (elementInput[0] == 'description') {
                    str += $(elm).val() + '\n';
                } else {
                    str += $(elm).siblings('label').html()+': '+$(elm).val() + '\n';
                }
            }
        });
        navigator.clipboard.writeText(str).then(function() {
            console.log('Copied!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    @else
    function copyFormToClipboard(){
        var str = '';
        $("#addTriageCallForm input[type=text]:visible,#addTriageCallForm input[type=time]:visible,#addTriageCallForm textarea:visible,#addTriageCallForm select:not([multiple]):visible, #addTriageCallForm select[multiple]:visible").each(function(ind,elm){
            if($(elm).attr('id') == 'bodySystem'){
                str += 'Body System(s) Involved'+': '+$(elm).val() + '\n';
            }else if($(elm).attr('id') == 'reason'){
                str += 'Reason'+': '+$(elm).val() + '\n';
            }else{
                if ($(elm).prop('nodeName') == 'SELECT' && $(elm).attr('multiple') == 'multiple') {
                    str += $(elm).parent().parent().find('label.w-100').html()+': '+$(elm).val() + '\n';
                } else {
                    str += $(elm).siblings('label.w-100').html()+': '+$(elm).val() + '\n';
                }
            }
        });
        str += 'Relationship to Client'+': '+$('input[name="clientRelationship"]:checked').val() + '\n';
        str += 'Were New Intervention Orders Received?'+': '+($('input[name="wereNewOrderRecieived"]').is(':checked') ? 'Yes' : 'No') + '\n';
        if ($('input[name="wereNewOrderRecieived"]').is(':checked')) {
            str += 'Intervention Type'+': '+($('input[name="interventionType"]').val()) + '\n';
            str += 'Follow-Up Action'+': '+($('input[name="followUpAction"]').val()) + '\n';
            str += 'Order Items'+': ' + '\n';
            str += 'Order Number'+'\t\t Order Detail\t\t Order Date' + '\n';
            jQuery.each(jsonHiddenAddedData, function(index, item) {
                str += item['order_number']+'\t\t ' + item['order_detail'] + '\t\t '+ item['order_date'] + '\n';
            });
        }
        navigator.clipboard.writeText(str).then(function() {
            console.log('Copied!');
        }, function(err) {
            console.error('Could not copy text: ', err);
        });
    }
    @endif
    var isDisposition = ['Go to ED Now','Go to ED Now or to PCP Office with PCP Approval','Office Visit/Telehealth Visit Now or Urgent Care Now'];
    $(document).on('change','#disposition',function(){
        if($(this).val() != '' && isDisposition.includes($(this).val())){
            $('.isDisposition').removeClass('hide');
        }else{
            $('.isDisposition').addClass('hide');
        }
    });
    $(document).on('click','#clientAllCallsExtLink',function(){
        var link = '{{route("triage.getallcalls")}}?clientId='+$('#aoc_patient_id').val();
        window.open(link,'_blank');
    });

    @if (LD::checkFeatureFlag('add-order-detail-field-to-triage-incoming-call-form'))
    var jsonTableData = [];
    var jsonHiddenAddedData = [];
        @if(isset($data) && !empty($data->orderDetails))
            jsonTableData = '<?php echo (isset($data->orderDetails) && $data->orderDetails != '{}') ? addslashes($data->orderDetails) : ""?>';
            jsonTableData = jsonTableData != '' ? JSON.parse(jsonTableData) : [];
            jsonHiddenAddedData = jsonTableData;
        @endif
    function openOrderForm(e,button) {
        $('#order_details_form').modal('show');
    }

    $(document).on("hidden.bs.modal", "#order_details_form", function() {
        $(".modal:visible").length && $(document.body).addClass("modal-open");
    });

    function saveOrderDetails(e,button) {
        if($('#order-details-form').valid()){
            var orderNumber = $("#order_number_input").val();
            var orderDetails = $("#order_details_input").val();
            $("#order_number_input").val("");
            $("#order_details_input").val("");

            var currentdate = new Date();
            var datetime =("0" + (currentdate.getMonth()+1)).slice(-2)  + "/"+ ("0" + currentdate.getDate()).slice(-2) + "/" + currentdate.getFullYear() + " " + ("0" + currentdate.getHours()).slice(-2) + ":" + ("0" + currentdate.getMinutes()).slice(-2) + ":" + ("0" +currentdate.getSeconds()).slice(-2);
            var item = {}
            item["order_number"] = orderNumber;
            item["order_detail"] = orderDetails;
            item["order_date"] = datetime;
            // jsonTableData.push(item);
            jsonHiddenAddedData.push(item);
            $('#order_detail_table').DataTable().clear().draw();
            $('#order_detail_table').DataTable().rows.add(jsonHiddenAddedData); // Add new data
            $('#order_detail_table').DataTable().columns.adjust().draw(); // Redraw the DataTable
            $('#order_details_form').modal('hide');
        }
    }
    @endif

    $(document).ready(function(){
        var trige_id = $("#trige_id").val();

        var multipleCancelButton = new Choices('#bodySystem', {
            removeItemButton: true
        });
        $('#callerPhone').usPhoneFormat({
            format: '(xxx) xxx-xxxx',
        });

        @if (LD::checkFeatureFlag('add-order-detail-field-to-triage-incoming-call-form'))
        $(document).on('change','#wereNewOrderRecieived',function() {
            if($(this).is(":checked")){
                $("."+$(this).attr("id")).removeClass("hide");
            } else {
                $("."+$(this).attr("id")).addClass("hide");
            }
        });

        $('#addTriageCallForm #followUpAction').multiselect({includeSelectAllOption: true});
        if(!$.fn.dataTable.isDataTable('#order_detail_table')){
        var orderDetailTable =  $('#order_detail_table').dataTable({
                data: jsonTableData,
                columns: [
                    { data: 'order_number', width:'10%' },
                    { data: 'order_detail',width:'70%' },
                    { data: 'order_date', width:'20%' }
                ]
            });
        }
        @endif
        @if (LD::checkFeatureFlag('triage-incoming-call-form-changes'))
        $(document).on('change','#wasMedPrescribed',function(){
            if($(this).is(":checked")){
            $("."+$(this).attr("id")).removeClass("hide");
            } else {
            $("."+$(this).attr("id")).addClass("hide");
            }
        });
        $(document).on('change','#emergencyDepartment',function() {
            if($(this).find(":selected").val() == 'other'){
                $("."+$(this).attr("id")).removeClass("hide");
            } else {
                $("."+$(this).attr("id")).addClass("hide");
            }
        });
        @endif
        @if (LD::checkFeatureFlag('change-er-question-to-yes'))
            $(document).on('change','#emergencyRoom',function() {
                if($(this).is(":checked")){
                    $("."+$(this).attr("id")).removeClass("hide");
                } else {
                    $("."+$(this).attr("id")).addClass("hide");
                }
            });
        @endif
        console.log(trige_id,'trige_id')
        if(trige_id !== null){
            let aoc_patient_id = $("#aoc_patient_id").val();
            $("#wasMedPrescribed").trigger("change");
            $("#wereNewOrderRecieived").trigger("change");
            $("#emergencyDepartment").trigger("change");
            $("#emergencyRoom").trigger("change");
            $("#disposition").trigger("change");
            if(aoc_patient_id != null){
                $.ajax({
                    url: "{{route('triage.getclientname')}}",
                    method: "GET",
                    data: { id: aoc_patient_id},
                    dataType: "json",
                    success: function(response) {
                        $("#clientName").val(response.name);
                        if(response.payer != 'undefined'){
                            $("#terminalPlayer").val(response.payer);
                            $("#terminalPlayer").attr('disabled',true);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log("Error: " + error);
                    }
                });
            }
        }
    });
    var isConfirmed = 0;
    var isSave = 0;
    $('#addTriageCallFormModal').on('hide.bs.modal', function(event) {
        (!isConfirmed) ? event.preventDefault():'';
        (!isSave) ? modalConfirm('Do you want to exit without saving? Any unsaved changes will not be saved.','Close without Saving','Go Back',function(res,modelDialog){
            if(res){
                isConfirmed = 1;
                frameCounter = 0;
                if(iframeContainer) {
                    iframeContainer.removeChild(iframe);
                }
                    
                $('.modal').modal('hide');
            }else{
                modelDialog.modal('hide');
                $('body').addClass("modal-open");
            }
        }) : '';
    });

    $('.datepicker').datepicker();
    $('.datepicker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });
    function checkingClientCallsAvailabilityAndGetDOB(clientId,cb){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('triage.checkingClientCallsAvailability')}}",
            type: 'get',
            data: {
                clientId:clientId,
                _token: _token
            },
            success: function(data) {
                cb(data.count,data.patientDetails);
            }
        });
    }
    function saveTriage(e, button){
        e.preventDefault();
        if($('#addTriageCallForm').valid()){
            $('.disabled').removeAttr('disabled');
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#addTriageCallForm').serializeObject();
            @if (LD::checkFeatureFlag('add-order-detail-field-to-triage-incoming-call-form'))
                data['orderdetails'] = jsonHiddenAddedData;
            @endif
            data['_token'] = _token;
            $.ajax({
                url: "{{route('triage.save')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        isConfirmed = 1;
                        isSave = 1;
                        $('#addTriageCallFormModal').modal('hide');

                    }else{
                        console.log('Error:' + data.msg);
                    }
                    $('.disabled').attr('disabled','disabled');
                }
            });
        }
    }
    function editTriage(e, button){
        e.preventDefault();
        if($('#addTriageCallForm').valid()){
            $('.disabled').removeAttr('disabled');
            $('.follow-type-tabs .tab-content').find('select,radio,checkbox,input,textarea,button.multiselect,.wereNewOrderRecieived button,button.btn').not('select[name="bodySystem"]').removeAttr('disabled');
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#addTriageCallForm').serializeObject();
            @if (LD::checkFeatureFlag('add-order-detail-field-to-triage-incoming-call-form'))
                data['orderdetails'] = jsonHiddenAddedData;
            @endif
            data['_token'] = _token;
            $.ajax({
                url: "{{route('triage.edit')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        isConfirmed = 1;
                        isSave = 1;
                        <?php if(LD::checkFeatureFlag('triage-details-edit-call')){ ?>
                            $('.modal').modal('hide');
                        <?php } else {?>
                            $('#addTriageCallFormModal').modal('hide');
                        <?php } ?>


                    }else{
                        console.log('Error:' + data.msg);
                    }
                    $('.disabled').attr('disabled','disabled');
                    $('.follow-type-tabs .tab-content').find('select,radio,checkbox,input,textarea,button.multiselect,.wereNewOrderRecieived button,button.btn').not('select[name="bodySystem"]').attr('disabled','disabled');
                }
            });
        }
    }
    function getEmergencyDepartment(aoc_patient_id){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{ route('triage.getEmergencyDepartment') }}",
            type: "GET",
            data: {
                aoc_patient_id: aoc_patient_id,
                _token: _token
            },
            success: function(response) {
                $('#emergencyDepartment').empty();
                $("#emergencyDepartment").append('<option value="">Select One</option>');
                $.each(response, function( index, value ) {
                    $("#emergencyDepartment").append('<option value="'+value.text+'">'+value.label+'</option>')
                });
                $("#emergencyDepartment").append('<option value="other">Other</option>');
                @if (LD::checkFeatureFlag('incoming-call-form-changes-triage'))
                    $("#emergencyDepartment").multiselect("rebuild");
                @endif
            },
        });
    }
    @if (LD::checkFeatureFlag('incoming-call-form-changes-triage'))
        $('#addTriageCallForm #emergencyDepartment').multiselect({includeSelectAllOption: true,enableCaseInsensitiveFiltering: true});
    @endif
    $('#addTriageCallForm #reason').multiselect({includeSelectAllOption: true,enableCaseInsensitiveFiltering: true});
    $("#addTriageCallForm #clientName").autocomplete({
        source: "{{ route('getScheduleClient') }}",
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            $('#addTriageCallForm #aoc_patient_id').val(ui.item.id);
            checkingClientCallsAvailabilityAndGetDOB(ui.item.id,function(count,patientDetails){
                if(count > 0){
                    $('#clientWarningMsg').removeClass('hide').find('#clientCallCount').html(count);
                }else{
                    $('#clientWarningMsg').addClass('hide');
                }
                if(patientDetails.Patient_DOB != 'undefined'){
                    $('#clientDOB').val(patientDetails.Patient_DOB);
                }
                if(patientDetails.payer != 'undefined'){
                    $("#terminalPlayer").val(patientDetails.Payer);
                    @if(LD::checkFeatureFlag('add-original-call-notes-to-activity-log-triage'))
                        $("#terminalPlayer").prop('readonly', true);
                    @else
                        $("#terminalPlayer").attr('disabled',true);
                    @endif
                }
                <?php if(LD::checkFeatureFlag('add-client-id-and-client-location-triage-form')) {?>
                    if(patientDetails.PatientAccount != 'undefined'){
                        $("#client_id").val(patientDetails.PatientAccount);
                        @if(LD::checkFeatureFlag('add-original-call-notes-to-activity-log-triage'))
                            $("#client_id").prop('readonly', true);
                        @else
                            $("#client_id").attr('disabled',true);
                        @endif
                    }
                    if(patientDetails.facilityName != 'undefined'){
                        $("#client_location").val(patientDetails.facilityName);
                        @if(LD::checkFeatureFlag('add-original-call-notes-to-activity-log-triage'))
                            $("#client_location").prop('readonly', true);
                        @else
                            $("#client_location").attr('disabled',true);
                        @endif
                    }
                <?php } ?>
            });
            @if (LD::checkFeatureFlag('triage-incoming-call-form-changes'))
            getEmergencyDepartment(ui.item.id)
            @endif
        }
    });

    $("#addTriageCallForm").validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "user_id":{
                required: true
            },
            "clientName":{
                required: function(element) {
                    return !($("#aoc_patient_id").val() != '');
                }
            },
            "callDate":{
                required: true
            },
            "callStartTime":{
                required: true
            },
            "callerFirstName":{
                required: true
            },
            "callerLastName":{
                required: true
            },
            "callerPhone":{
                required: true,
                phoneNumber: true,
                minlength:14,
            },
            "callerEmail":{
                customEmail:true
            },
            "clientRelationship":{
                required: true
            },
            "clientDOB":{
                required: true
            },
            "bodySystem":{
                required: true
            },
            "reason":{
                required: true
            },
            "disposition":{
                required: true
            },
            "description":{
                required: true
            },
            // "terminalPlayer":{
            //     required: true
            // },
            // "suggestedTimeframe":{
            //     required: function() {
            //         return !($('#suggestedTimeframe').parent().hasClass('hide'));
            //     }
            // },
            // "isAmbulanceTransfer":{
            //     required: function() {
            //         return !($('#isAmbulanceTransfer').parent().parent().hasClass('hide'));
            //     }
            // }
            @if (LD::checkFeatureFlag('add-order-detail-field-to-triage-incoming-call-form'))
            "interventionType":{
                required: function(element) {
                    return ($("#wereNewOrderRecieived").is(":checked"));
                }
            },
            @endif
            @if (LD::checkFeatureFlag('triage-incoming-call-form-changes'))
            "drugClassification":{
                required: function(element) {
                    return ($("#wasMedPrescribed").is(":checked"));
                }
            },
            "hasMedicationPickedUp":{
                required: function(element) {
                    return ($("#wasMedPrescribed").is(":checked"));
                }
            },
            "emergencyDepartmentOther":{
                required: function(element) {
                    return ($("#emergencyDepartment").find(":selected").val() == 'other');
                }
            },
            @endif
        },
        messages: {
            "callerPhone":{
                minlength: 'Please enter at least 10 numbers.',
            }
        },
        // errorPlacement: function(error, element) {
        //     if (element.attr("name") == "hasMedicationPickedUp") {
        //         error.insertAfter("#hasMedicationPickedUpNo");
        //     }
        // }
    });

    //Custom rules for validation
    $.validator.addMethod("phoneNumber", function(value, element) {
        var regex = /^[0-9- )(]+$/;
        return regex.test(value);
    }, "This field will allow only numbers");

    //Custom rules for validation
    $.validator.addMethod("customEmail", function(value, element) {
        if(value != ''){
            var regex = /^\b[A-Z0-9._%-]+@[A-Z0-9.-]+\.[A-Z]{2,4}\b$/i;
            return regex.test(value);
        }else{
            return true;
        }
    }, "Please enter a valid email address.");

    $("#addTriageCallForm #terminalPlayer").autocomplete({
        source: "{{ route('triage.getTerminalPayer') }}",
        classes: {
            "ui-autocomplete": "terminalPlayer"
        },
        minLength: 2,
        select: function(event, ui) {}
    });

    $('#addTriageCallForm #careCoordination').multiselect({includeSelectAllOption: true, enableCaseInsensitiveFiltering: true});
    $('#addTriageCallForm #disposition').multiselect({includeSelectAllOption: false, enableCaseInsensitiveFiltering: true});

    @if(LD::checkFeatureFlag('pull-information-from-kantime-triage-incoming-call-form'))
    $('input[name="clientRelationship"]').change(function() {
        $('#callerFirstName').val('');
        $('#callerLastName').val('');
        $('#callerPhone').val('');
        $('#callerEmail').val('');
        $('#aoc_provider_id').val('');
        if ($('input[name="clientRelationship"]:checked').val() == 'Nurse') {
            if (!$("#callerFirstName").hasClass("ui-autocomplete-input")) {
                $("#callerFirstName").autocomplete({
                    source: function(request, response) {
                        $.getJSON("{{ route('getCandidateName') }}", {
                            status: 'current',
                            filter: {'mssql_providers.EmploymentStatusID': [1,2,8,22,24]},
                            term: $('#callerFirstName').val()
                        }, response);
                    },
                    minLength: 2,
                    select: function(event, ui) {
                        event.preventDefault();
                        var name = ui.item.value.split(" ");
                        $('#callerPhone').val(ui.item.phone);
                        $('#callerEmail').val(ui.item.email);
                        $('#callerFirstName').empty().val(name[0]);
                        $('#callerLastName').val(name[1]);
                        $('#aoc_provider_id').val(ui.item.id);
                    }
                });
            } else {
                $("#callerFirstName").autocomplete({
                    disabled: false
                });
            }
        } else {
            $("#callerFirstName").autocomplete({
                disabled: true
            });
        }
    });

    $('#candidate_name').keyup(function() {
        flag = false;
        if ($(this).val() == '') {
            $('#candidate_id').val('');
            $('#provider_id').val('');
        }
    });
    $('#candidate_name').blur(function() {
        if ($('#mode').val() == 'Add' && $('input[name="status"]:checked').val() == 'current' &&
            flag ==
            false) {
            $(this).val('');
            $('#candidate_id').val();
            $('#provider_id').val();
        }
    });
    @endif
    @if(H::canPermissions(['edit-triage-call-date']))
        $("#changeTriageDateTime #reporter_id").autocomplete({
            source: "{{ route('triage.getReporterList') }}",
            classes: {
                "ui-autocomplete": "clientName"
            },
            minLength: 2,
            select: function(event, ui) {
                $('#reporter_user_id').val(ui.item.id);
            }
        });
    @endif

</script>
<style>
.ui-autocomplete {
    z-index: 999999;
}
</style>
