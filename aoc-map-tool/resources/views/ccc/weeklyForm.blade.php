<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $loggedInUser = H::getLoggedInUser();
    $currentDate = date('m/d/Y');
    $currentTime = date('H:i');
	$outcomeTempList = ["Document Call", "Requested Call back"];
    $outcomeAttempt = [];
    if($attemptCount==0){
        $outcomeAttempt[] = "Attempt 1";
    }
    if($attemptCount==1){
        $outcomeAttempt[] = "Attempt 2";
    }
    if($attemptCount==2 && $type!='weekly'){
        $outcomeAttempt[] = "Attempt 3";
    }

    $outcomeList = array_merge($outcomeAttempt,$outcomeTempList);

    $specialized = 'No';
    if(H::getHelper('NTHChecklist')->isSpecialized($clientDetails->CF_Skills)) {
        $specialized = 'Yes';
    }
    $priority = 'No';
    if(isset($needToHireChecklistDetails->IsPriority) && $needToHireChecklistDetails->IsPriority==1) {
        $priority = 'Yes';
    }


?>
<style>
    .followUpTriageCallForm .nav-tabs .nav-item.show .nav-link, .followUpTriageCallForm .nav-tabs .nav-link.active {
        background-color: #2f5baa;
        color: #fff;
    }
    .followUpTriageCallForm .nav-tabs .nav-item.show .nav-link, .followUpTriageCallForm .nav-tabs .nav-link {
        background-color: #ddd;
        color: #000;
    }
    .followUpTriageCallForm li.nav-item {
        margin-right: 10px;
    }
    label#contactWithCaller-error {
        position: absolute;
        width: 200px !important;
        bottom: -35px;
        left: 0px;
    }
</style>

<link href="{{ mix('css/jquery.datetimepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/jquery.datetimepicker.full.min.js') }}"></script>
<x-modal>
    <x-slot name="id">weeklyFormModal</x-slot>
    <x-slot name="size">w80</x-slot>
    @if(LD::checkFeatureFlag('ccc-weekly-monthly-form'))
        @if($type=='weekly')
            <x-slot name="title">Weekly NTH Call</x-slot>
        @else
            <x-slot name="title">Monthly Call</x-slot>
        @endif
    @else
    <x-slot name="title">Weekly NTH Call</x-slot>
    @endif
    <x-slot name="class">weekly-nth-call-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            @if(LD::checkFeatureFlag('ccc-weekly-monthly-form'))
                @if($type=='weekly')
                    <a data-bs-dismiss="modal" href="javascript:void(0);"
                    onclick="openBuildScheduleForm('<?php echo $aocPatientId?>');"><i
                    class="fa fa-calendar"></i>
                </a>
                @endif
            @else
                <a data-bs-dismiss="modal" href="javascript:void(0);"
                    onclick="openBuildScheduleForm('<?php echo $aocPatientId?>');"><i
                    class="fa fa-calendar"></i>
                </a>
            @endif
            <?php if (LD::checkFeatureFlag('weekly-nth-call-client-profile')) { ?>
                <a data-bs-dismiss="modal" href="javascript:void(0);"
                    onclick="getClientDetails('<?php echo $clientId?>');"><i
                    class="fa fa-user"></i>
                </a>
            <?php }?>

        </div>
    </x-slot>
    <x-slot name="body">
    <form id="weeklyform" class="weeklyform">
    <div class="tracker-details col-md-12">
            <div class="col-md-12 card-cnt p-0 d-flex">
                <div class="card mr-5">
                    <div class="card-body">
                        <h5 class="card-title bg-blue">Client Info</h5>
                        <div class="detail-section">
                            <div class="row">
                                <div class="col-md-6">{{$clientDetails->Patient_FirstName}} {{$clientDetails->Patient_LastName}}</div>
                                <div class="col-md-6">DOB: {{H::getFormatedDate($clientDetails->Patient_DOB)}}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><p class="card-text">{{$clientDetails->PatientAccount}}</p></div>
                                <div class="col-md-6">Age: {{$clientDetails->Patient_age}}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><p class="card-text">{{$clientDetails->FacilityName}}</p></div>
                                <div class="col-md-6">Weight: {{$clientDetails->CF_Weight}}</div>
                            </div>
                            <div class="row pt-3">
                                <div class="col-md-6"><p class="card-text">{{$clientDetails->Patient_Phone}}</p></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="card-text">
                                    @if($clientDetails->Patient_MobilPhone)
                                        {{$clientDetails->Patient_MobilPhone}} (mobile)
                                    @endif
                                    </p>
                                </div>
                            </div>

                            <div class="row pt-3">
                                <div class="col-md-6">
                                    <p class="card-text">{{$clientDetails->Patient_Address1}} {{$clientDetails->Patient_Address2}}</p>
                                    <p class="card-text">{{$clientDetails->Patient_City}} {{$clientDetails->Patient_State}} {{$clientDetails->Patient_Zip}}</p>
                                </div>
                                <div class="col-md-6">
                                    @if($clientDetails->Patient_Gender != '')
                                    <div class="tags-cnt">
                                        <span class='tag tag-grey'>{{$clientDetails->Patient_Gender}}</span>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title bg-blue">&nbsp;</h5>
                        <div class="detail-section">
                            <div class="row">
                                <div class="col-md-6">PDN Auth Hours:  {{$clientDetails->AuthorizedWeeklyHours}}</div>
                                <div class="col-md-6">Specialized Client:  <?php echo $specialized?></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">CNA Auth Hours:  {{$clientDetails->CNAAuthorizedWeeklyHours}}</div>
                                <div class="col-md-6">Priority: {{$priority}}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">Respite Auth Hours:  {{$clientDetails->CF_RespWeeklyHrs}}</div>
                                <div class="col-md-6">Specialized Rate: </div>
                            </div>
                            <div class="row pt-3">
                                <div class="col-md-6">
                                    @if($clientDetails->is_workForce)
                                    <div class="tags-cnt">
                                        <span class='tag'>Work Force Dev Proj</span>
                                    </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                {!! $clientDetails->skillDisplayValue !!}
                                </div>
                            </div>
                            @if(LD::checkFeatureFlag('sponsored-ad-for-nth-client'))
                                <div class="row">
                                    <div class="col-md-6 p-1">
                                        <div class="col-md-12 p-1">Terminal Payor:  {{$clientDetails->Payer??''}}</div>
                                        <div class="col-md-12 p-1">Respite Terminal Payor:  {{$clientDetails->RespitePayer??''}}</div>
                                    </div>
                                    <div class="col-md-6 p-1">
                                        @if(!empty($socialMediaAdDetail))
                                            <div class="col-md-12 p-1 sponsored-ad">Client has a sponsored ad currently running:
                                                {!! ($socialMediaAdDetail->completed ==0)?'<i class="fa fa-circle" aria-hidden="true"></i>':'<i class="fa fa-circle-o" aria-hidden="true"></i>' !!}Yes&nbsp;&nbsp;{!! ($socialMediaAdDetail->completed ==1)?'<i class="fa fa-circle" aria-hidden="true"></i>':'<i class="fa fa-circle-o" aria-hidden="true"></i>' !!} No
                                            </div>
                                            <div class="col-md-12 p-1">Date of last ad: {{H::getFormatedDate($socialMediaAdDetail->start_date)}}</div>
                                            <div class="col-md-12 p-1">Link to ad: <a href="javascript:void(0)" onclick="getSocialMediaDetails('{{$socialMediaAdDetail->id}}')">{{$socialMediaAdDetail->ad_name}}</a></div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="row pt-3">
                                    <div class="col-md-12">Terminal Payor:  {{$clientDetails->Payer??''}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-12">Respite Terminal Payor:  {{$clientDetails->RespitePayer??''}}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @if(LD::checkFeatureFlag('ccc-weekly-monthly-form'))
                <section class="main-weekly-intro-section pt-3 pb-2">
                    <div class="card">
                        <div class="card-header" id="weeklytab1">
                            <h2 class="mb-0">
                            <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#weekly-intro" aria-expanded="true" aria-controls="collapseOne">
                                Introduction
                            </button>
                            </h2>
                        </div>

                        <div id="weekly-intro" class="collapse show" aria-labelledby="weeklytab1" data-parent="#weeklyfollowuptabs">
                            <div class="card-body">
                                @include('ccc.documentcall.tabs.intro')
                            </div>
                        </div>
                    </div>
                </section>
            @endif

                <section class="main-container">
                    <div class="row">
                        <div class="col-md-6 pt-3 pl-3">
                            <label  class="w-auto" for="outcome">Outcome:</label>
                            <select class="form-control  outcome" name="outcome" id="outcome">
                                <option value="">Select Outcome</option>
                                <?php foreach($outcomeList as $outcome) { ?>
                                    <option value="<?php echo $outcome?>"><?php echo $outcome?></option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="col-md-6 pt-3 pl-4 hide" id="result_container">
                            <label  class="w-100" for="result">Result:</label>
                            <select class="form-control  result" name="result" id="result" multiple data-placeholder="Select Result" data-live-search="true">
                                <option value="Left Voicemail">Left Voicemail</option>
                                <option value="Sent Text">Sent Text</option>
                                <option value="Sent Email">Sent Email</option>
                            </select>
                        </div>
                    </div>
                </section>


                <section class="hide pt-1 pl-0" id="contact_attempt">
                    <div class="col-md-12 p-0">
                        <label class="w-100">Comment:</label>
                        <textarea name="contact_comment" id="contact_comment"  class="form-control"></textarea>
                    </div>
                </section>
                @if(LD::checkFeatureFlag('ccc-weekly-monthly-form'))
                    <input type="hidden" name="nthaccount_id" id="nthaccount_id" value="<?php echo $aocPatientId?>" />
                    @include('ccc.documentcall.tabs')
                @else
                <section class="triage-details pt-3  pl-0 hide" id="document_container">
                    <div class="form-group row">
                        <div class="col-md-12">
                            <input type="hidden" name="nthaccount_id" id="nthaccount_id" value="<?php echo $aocPatientId?>" />
                            <label  class="w-100" for="callerLastName">Spoke With:</label>
                            <input type="text" class="form-control general" id="spokewith" name="spokewith" value=""/>
                        </div>

                        <div class="col-md-12">
                            <label  class="w-100" for="clientRelationship">Text, Phone, or Email:</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" name="textorphone" id="textorphonetext" value="Text" />
                                <label class="form-check-label" for="textorphonetext">Text</label>
                            </div>
                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" name="textorphone" id="textorphonephone" value="Phone" />
                                <label class="form-check-label" for="textorphonephone">Phone</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" name="textorphone" id="textorphoneemail" value="Email" />
                                <label class="form-check-label" for="textorphoneemail">Email</label>
                            </div>

                        </div>

                        <div class="col-md-12">
                            <label  class="w-auto" for="mediaconsentonfile">Media consent on file:</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="mediaconsentonfile_yes" name="mediaconsentonfile" <?php if($mediaConsent) { ?> checked="checked"  <?php }?> value="1">
                                <label class="form-check-label" for="mediaconsentonfile_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="mediaconsentonfile_no" name="mediaconsentonfile" <?php if(!$mediaConsent) { ?> checked="checked"  <?php }?> value="0">
                                <label class="form-check-label" for="mediaconsentonfile_no">No</label>
                                @if(LD::checkFeatureFlag('ccc-record-date-for-media-consent-on-weekly-call') &&
                                !empty($needToHireAccount->date_media_refused))
                                &nbsp;&nbsp;Refused on: {{H::getFormatedDate($needToHireAccount->date_media_refused)}}
                                @endif
                            </div>
                        </div>

                        <div class="col-md-4">
                            <select class="form-control hide mediaconsentonfilecontainer" name="mediaconsentreason" id="mediaconsentreason">
                                <option value="">Select Reason</option>
                                <option value="Parent refused">Parent refused</option>
                                <option value="Consent sent to parent">Consent sent to parent</option>
                                <option value="Foster Child">Foster Child</option>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label  class="w-auto" for="">Staffing update details provided:</label>
                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" checked="checked" type="radio" id="staffingupdatedetailsprovided_none" name="staffingupdatedetailsprovided"  value="">
                                <label class="form-check-label" for="staffingupdatedetailsprovided_none">None</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="staffingupdatedetailsprovided_yes" name="staffingupdatedetailsprovided"  value="1">
                                <label class="form-check-label" for="staffingupdatedetailsprovided_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="staffingupdatedetailsprovided_no" name="staffingupdatedetailsprovided"  value="0">
                                <label class="form-check-label" for="staffingupdatedetailsprovided_no">No</label>
                            </div>

                        </div>

                        <div class="col-md-4">
                            <label  class="w-auto" for="contactWithCaller">Coverage:</label>
                            <select class="form-control" name="coverage" id="coverage">
                                <option value="">Select Coverage</option>
                                <option value="Coverage declined">Coverage declined</option>
                                <option value="Coverage accepted">Coverage accepted</option>
                                <option value="Coverage with stipulations">Coverage with stipulations</option>
                            </select>
                        </div>

                        <div class="col-md-4  hide coveragestipulations">
                            <label class="w-100" for="contactWithCaller">Coverage stipulations:</label>
                            <select class="form-control selectpicker" data-placeholder="Select Coverage stipulations" name="coveragestipulations" id="coveragestipulations" multiple>
                                @foreach(config('constant')['coveragestipulations'] as $k => $v)
                                <option value="{{ $v }}">{{ $v }}</option>
                                @endforeach
                            </select>
                            <label id="coveragestipulations-error" class="error" for="coveragestipulations"></label>
                        </div>


                        <div class="col-md-12">
                            <label class="w-100">Client requests:</label>
                            <textarea name="clientrequest" id="clientrequest" class="form-control"></textarea>
                        </div>

                        <div class="col-md-12">
                            <label  class="w-auto" for="contactWithCaller">Verified insurance :</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" checked="checked" type="radio" id="verifiedinsurance_none" name="verifiedinsurance"  value="">
                                <label class="form-check-label" for="verifiedinsurance_none">None</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedinsurance_yes" name="verifiedinsurance"  value="1">
                                <label class="form-check-label" for="verifiedinsurance_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedinsurance_no" name="verifiedinsurance"  value="0">
                                <label class="form-check-label" for="verifiedinsurance_no">No</label>
                            </div>

                            <!--<div class="form-check custom-switch custom-switch-lg text-center">
                                <input class="form-check-input" type="checkbox" id="verifiedinsurance" value="1" name="verifiedinsurance"/>
                                <label class="form-check-label" for="verifiedinsurance"></label>
                            </div>-->
                        </div>

                        <div class="col-md-12">
                            <label  class="w-auto" >Verified schedule:</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" checked="checked" type="radio" id="verifiedschedule_none" name="verifiedschedule"  value="">
                                <label class="form-check-label" for="verifiedschedule_none">None</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedschedule_yes" name="verifiedschedule" onclick="openBuildScheduleForm('<?php echo $aocPatientId?>');" value="1">
                                <label class="form-check-label" for="verifiedschedule_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedschedule_no" name="verifiedschedule"  value="0">
                                <label class="form-check-label" for="verifiedschedule_no">No</label>
                            </div>


                            <!--
                            <div class="form-check custom-switch custom-switch-lg text-center">
                                <input class="form-check-input" type="checkbox" id="verifiedschedule" value="1" name="verifiedschedule"/>
                                <label class="form-check-label" for="verifiedschedule"></label>
                            </div>-->
                        </div>

                        <div class="col-md-12">
                            <label  class="w-auto" >Verified pets :</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" checked="checked" type="radio" id="verifiedpets_none" name="verifiedpets"  value="">
                                <label class="form-check-label" for="verifiedpets_none">None</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedpets_yes" name="verifiedpets"  value="1">
                                <label class="form-check-label" for="verifiedpets_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedpets_no" name="verifiedpets"  value="0">
                                <label class="form-check-label" for="verifiedpets_no">No</label>
                            </div>

                            <!--<div class="form-check custom-switch custom-switch-lg text-center">
                                <input class="form-check-input" type="checkbox" id="verifiedpets" value="1" name="verifiedpets"/>
                                <label class="form-check-label" for="verifiedpets"></label>
                            </div>-->
                        </div>

                        <div class="col-md-12">
                            <label  class="w-auto" for="">Verified lifting requirements and lift availability :</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" checked="checked" type="radio" id="verifiedliftingrequirements_none" name="verifiedliftingrequirements"  value="">
                                <label class="form-check-label" for="verifiedliftingrequirements_none">None</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedliftingrequirements_yes" name="verifiedliftingrequirements"  value="1">
                                <label class="form-check-label" for="verifiedliftingrequirements_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedliftingrequirements_no" name="verifiedliftingrequirements"  value="0">
                                <label class="form-check-label" for="verifiedliftingrequirements_no">No</label>
                            </div>


                        <!-- <div class="form-check custom-switch custom-switch-lg text-center">
                                <input class="form-check-input" type="checkbox" id="verifiedliftingrequirements" value="1" name="verifiedliftingrequirements"/>
                                <label class="form-check-label" for="verifiedliftingrequirements"></label>
                            </div>-->
                        </div>

                        <div class="col-md-12">
                            <label  class="w-auto" for="">Verified weight :</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" checked="checked" type="radio" id="verifiedweight_none" name="verifiedweight"  value="">
                                <label class="form-check-label" for="verifiedweight_none">None</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedweight_yes" name="verifiedweight"  value="1">
                                <label class="form-check-label" for="verifiedweight_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedweight_no" name="verifiedweight"  value="0">
                                <label class="form-check-label" for="verifiedweight_no">No</label>
                            </div>
                            <!--<div class="form-check custom-switch custom-switch-lg text-center">
                                <input class="form-check-input" type="checkbox" id="verifiedweight" value="1" name="verifiedweight"/>
                                <label class="form-check-label" for="verifiedweight"></label>
                            </div>-->
                        </div>

                        <div class="col-md-12">
                            <label  class="w-auto" for="contactWithCaller">Verified NTH ticket:</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" checked="checked" type="radio" id="verifiednthticket_none" name="verifiednthticket"  value="">
                                <label class="form-check-label" for="verifiednthticket_none">None</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiednthticket_yes" name="verifiednthticket"  value="1">
                                <label class="form-check-label" for="verifiednthticket_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiednthticket_no" name="verifiednthticket"  value="0">
                                <label class="form-check-label" for="verifiednthticket_no">No</label>
                            </div>

                            <!--<div class="form-check custom-switch custom-switch-lg text-center">
                                <input class="form-check-input" type="checkbox" id="verifiednthticket" value="1" name="verifiednthticket"/>
                                <label class="form-check-label" for="verifiednthticket"></label>
                            </div>-->
                        </div>

                        <div class="col-md-12">
                            <label  class="w-auto" for="contactWithCaller">Verified clinician discipline :</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" checked="checked" type="radio" id="verifiedcliniciandiscipline_none" name="verifiedcliniciandiscipline"  value="">
                                <label class="form-check-label" for="verifiedcliniciandiscipline_none">None</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedcliniciandiscipline_yes" name="verifiedcliniciandiscipline"  value="1">
                                <label class="form-check-label" for="verifiedcliniciandiscipline_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="verifiedcliniciandiscipline_no" name="verifiedcliniciandiscipline"  value="0">
                                <label class="form-check-label" for="verifiedcliniciandiscipline_no">No</label>
                            </div>

                            <!--<div class="form-check custom-switch custom-switch-lg text-center">
                                <input class="form-check-input" type="checkbox" id="verifiedcliniciandiscipline" value="1" name="verifiedcliniciandiscipline"/>
                                <label class="form-check-label" for="verifiedcliniciandiscipline"></label>
                            </div>-->
                        </div>


                        <div class="col-md-12">

                            <label  class="w-auto" for="askedaboutrelationshipwithnurse">Asked about relationship with in-home nurses :</label>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" checked="checked" type="radio" id="askedaboutrelationshipwithnurse_none" name="askedaboutrelationshipwithnurse"  value="">
                                <label class="form-check-label" for="askedaboutrelationshipwithnurse_none">None</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="askedaboutrelationshipwithnursee_yes" name="askedaboutrelationshipwithnurse"  value="1">
                                <label class="form-check-label" for="askedaboutrelationshipwithnurse_yes">Yes</label>
                            </div>

                            <div class="form-check form-check-inline general">
                                <input class="form-check-input" type="radio" id="askedaboutrelationshipwithnurse_no" name="askedaboutrelationshipwithnurse"  value="0">
                                <label class="form-check-label" for="askedaboutrelationshipwithnurse_no">No</label>
                            </div>

                            <!--<div class="form-check custom-switch custom-switch-lg text-center">
                                <input class="form-check-input" type="checkbox" id="askedaboutrelationshipwithnurse" value="1" name="askedaboutrelationshipwithnurse"/>
                                <label class="form-check-label" for="askedaboutrelationshipwithnurse"></label>
                            </div>-->

                        </div>

                        <div class="col-md-12">
                            <label class="w-100">Notes about relationship with in-home nurses (if applicable):</label>
                            <textarea name="notesaboutrelationship" id="notesaboutrelationship"  class="form-control"></textarea>
                        </div>

                        <div class="col-md-12">
                            <label class="w-100">On a scale of 1 to 10, how would you rate your experience at Angels of Care?</label>
                            <div class="satisfaction-rating">
                                <input type="radio" name="rate" id="rate-10" value="10"><label for="rate-10">10</label>
                                <input type="radio" name="rate" id="rate-9" value="9"><label for="rate-9">9</label>
                                <input type="radio" name="rate" id="rate-8" value="8"><label for="rate-8">8</label>
                                <input type="radio" name="rate" id="rate-7" value="7"><label for="rate-7">7</label>
                                <input type="radio" name="rate" id="rate-6" value="6"><label for="rate-6">6</label>
                                <input type="radio" name="rate" id="rate-5" value="5"><label for="rate-5">5</label>
                                <input type="radio" name="rate" id="rate-4" value="4"><label for="rate-4">4</label>
                                <input type="radio" name="rate" id="rate-3" value="3"><label for="rate-3">3</label>
                                <input type="radio" name="rate" id="rate-2" value="2"><label for="rate-2">2</label>
                                <input type="radio" name="rate" id="rate-1" value="1"><label for="rate-1">1</label>
                                <input type="radio" name="rate" id="rate-0" value="0"><label for="rate-0">0</label>
                            </div>
                        </div>

                        <div class="col-md-12">
                            <label class="w-100">Recap of call:</label>
                            <textarea name="recapofcall" id="recapofcall"  class="form-control"></textarea>
                        </div>



                    </div>
                </section>
                @endif
                <section id="request_callback_container" class="hide">
                    <div class="form-group col-md-12 pl-0">
                        <div class="mt-3 col-md-6 pl-0">
                            <input type="text" class="form-control general" placeholder="Choose Callback DateTime" id="requested_callback"  name="requested_callback" value=""/>
                        </div>
                    </div>
                </section>

                <section id="bottom_container" class="hide">
                    <div class="form-group col-md-12 p-0">
                        <div class="col-md-6 text-left pl-0 pt-3">
                            <button class="btn btn-primary" onclick="saveWeeklyForm(event, this);">Save</button>
                            <a class="copy-form-btn" id="clipbtn" onclick="copyFormToClipboard();">Copy Text from Form</a>
                        </div>
                    </div>
                </section>

            </div>
    </form>

    @if(LD::checkFeatureFlag('ccc-weekly-monthly-form'))
        <script>

        function copyFormToClipboard(){
            $("#weeklyform #contact_comment").val(null);
            $("#weeklyform #requested_callback").val(null);
            $("#weeklyform #result").multiselect('clearSelection');
            var str = '';
            $("#weeklyform input[type=text],#weeklyform input[type=checkbox],#weeklyform input[type=time],#weeklyform textarea,#weeklyform select, #weeklyform input[type=radio]").each(function(ind,elm){
                if($(elm).attr('id') == 'mediaconsentreason'){
                    if($(elm).val() != '') {
                        str += 'Reason'+': '+$(elm).val() + '\n';
                    }
                }else if($(elm).attr('type') == 'checkbox'){
                    if($(elm).is(':checked')){
                        if($(elm).hasClass("form-check-input")){

                        }else{
                            str += $(elm).siblings('label').html()+ '\n';
                        }
                    }
                } else if($(elm).attr('type') == 'radio'){
                    if($(elm).is(':checked')){
                        str += $(elm).parent().parent().find('label').html()+ ' ' +$(elm).next('label').html()+ '\n';
                    }
                }else{
                    if($(elm).val() != '') {
                        if($(elm).siblings('label').html() === undefined){
                            if($(elm).hasClass("currentissuedetails_dropdown")){
                                if($(elm).css("display")=="block" && $(elm).val()!='') {
                                    str += 'Details of issue(s) -: '+$(elm).val() + '\n';
                                }
                                //var style = $(elm).css("display");
                                //console.log(style);

                            }

                        }else{
                            str += $(elm).siblings('label').html()+': '+$(elm).val() + '\n';
                        }
                    }
                }
            });
            navigator.clipboard.writeText(str).then(function() {
                console.log('Copied!');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }

            $(function()
                {

                    $.validator.addMethod("atLeastOneSelected", function(value, element) {
                        // Get all dropdowns with a selected option
                        if($("#weeklyform #outcome option:selected").val() != "Document Call") {
                            return 1;
                        }
                        <?php if(LD::checkFeatureFlag('ccc-followup-calls-add-option-to-nurse-relationship')) { ?>
                                if($("#nurserelation_relation  option:selected").val()=='Yes'  ||  $("#nurserelation_relation  option:selected").val() == 'No clinicians in the home') {
                                    return 1;
                                }
                        <?php } else { ?>
                                if($("#nurserelation_relation  option:selected").val()=='Yes') {
                                    return 1;
                                }
                        <?php } ?>
                        var dropdowns = $("select.currentissue_dropdown");
                        var selectedCount = 0;

                        // Count the number of dropdowns with selected options
                        dropdowns.each(function() {
                            if ($(this).val() != "None") {
                                selectedCount++;
                            }
                        });

                        // Return true if at least one dropdown is selected, false otherwise
                        return selectedCount > 0;
                    }, "Please select at least one option.");

                    $.validator.addClassRules("currentissue_dropdown", { atLeastOneSelected: true });

                    $('#weeklyform').validate(
                    {
                        ignore: '',
                        rules:
                        {

                            'scheduling\[accept_coverage\]':{ required:function(element){
                                return $("#scheduling_accept_coverage").length>0 && $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },

                            'scheduling\[reason_not_accept_coverage\]':{ required:function(element){
                                return $("#scheduling_reason_not_accept_coverage").length>0 && $("#weeklyform #outcome option:selected").val() == "Document Call" && $("#weeklyform #scheduling_accept_coverage option:selected").val()=='Accepts coverage with stipulations';
                            } },

                            'scheduling\[coverage_shift_schedule\]':{ required:function(element){
                                return $("#scheduling_coverage_shift_schedule").length>0 && $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },

                            'scheduling\[available_days_hours_still\]':{ required:function(element){
                                return $("#scheduling_available_days_hours_still").length>0 && $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },

                            //monthly scheduling
                            'scheduling[upcomingvacation]':{ required:function(element){
                                return $("#monthlyscheduling_upcomingvacation").length>0 && $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },

                            'scheduling[nurseattendvacation]':{ required:function(element){
                                return $("#weeklyform #monthlyscheduling_upcomingvacation option:selected").val() == "Yes" && $("#monthlyscheduling_nurseattendvacation").length>0 && $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },

                            'scheduling[workschedulechanged]':{ required:function(element){
                                return $("#monthlyscheduling_workschedulechanged").length>0 && $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },

                            'scheduling[currentschedulechanged]':{ required:function(element){
                                return $("#monthlyscheduling_currentschedulechanged").length>0 && $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },



                            'insurance[provider_correct]':{ required:function(element){
                                return $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },

                            'nurserelation[relation]':{ required:function(element){
                                return $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },



                            'callrecap[thiscall]':{ required:function(element){
                                return $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },

                            'journalnotes[confirmation]':{ required:function(element){
                                return $("#journalnotes_confirmation").length>0 && $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },

                            rate:{ required:function(element){
                                return $("#weeklyform #outcome option:selected").val() == "Document Call";
                            } },


                            requested_callback:{ required:function(element){
                                return $("#weeklyform #outcome option:selected").val() == "Requested Call back";
                            }  },

                            contact_comment:{ required:function(element){
                                return $("#weeklyform #outcome option:selected").val() == "Attempt 1" || $("#weeklyform #outcome option:selected").val() == "Attempt 2" || $("#weeklyform #outcome option:selected").val() == "Attempt 3";
                            }  },

                            result:{ required:function(element){
                                return $("#weeklyform #outcome option:selected").val() == "Attempt 1" || $("#weeklyform #outcome option:selected").val() == "Attempt 2" || $("#weeklyform #outcome option:selected").val() == "Attempt 3";
                            }  },


                        },
                        invalidHandler: function(form, validator) {
                            var errors = validator.errorList;
                            $("#weeklyform .collapse").not(":first").removeClass("show");
                            if (errors.length > 0) {
                                var firstErrorElement = errors[0].element;
                                var parent = $(firstErrorElement).closest('#weeklyform .collapse');
                                parent.addClass('show');

                            }
                        },
                        errorPlacement: function(error, element) {
                            $('.error').css('position', 'initial');
                           // var parent = element.closest('.collapse'); // Replace 'your-parent-class' with the actual parent class name
                           // parent.addClass('show');
                            if ( element.is(":radio") )
                            {
                                error.appendTo( element.parent().parent() );
                            }
                            if($(element).attr("id") == 'result') {
                                error.insertAfter(element.parent());
                            }
                        },

                    });

                }
            );
            function saveWeeklyForm(e, button){
                e.preventDefault();
                if($('#weeklyform').valid()){
                    $('.disabled').removeAttr('disabled');
                    button.disabled = true;
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    var data = $('#weeklyform').serializeObject();

                    data['_token'] = _token;
                    $.ajax({
                        url: "{{route('ccc.saveweeklyinputcall')}}",
                        type: 'post',
                        data: data,
                        success: function(data) {
                            console.log(data);
                            button.disabled = false;
                            if(data.status){
                                isSave = 1;
                                isConfirmed = 1;
                                $('#weeklyFormModal').modal('hide');
                                if($.fn.dataTable.isDataTable('#dataTable-clientcare-exception-monthly_calls')){
                                    $('#dataTable-clientcare-exception-monthly_calls').DataTable().ajax.reload(null, false);
                                }
                                if($.fn.dataTable.isDataTable('#dataTable-clientcare-exception-weekly_nth_calls')){
                                    $('#dataTable-clientcare-exception-weekly_nth_calls').DataTable().ajax.reload(null, false);
                                }


                            }else{
                                console.log('Error:' + data.msg);
                            }

                        }
                    });
                } else {
                      console.log("not valid");
                }
            }

            $(document).on("change","#weeklyform #outcome",function(){
                var selectedValue =$(this).find(":selected").val();
                $("#weeklyform #request_callback_container").addClass("hide");
                $("#weeklyform #document_container").addClass("hide");
                $("#weeklyform #bottom_container").addClass("hide");
                $("#weeklyform #contact_attempt").addClass("hide");
                $("#weeklyform #result_container").addClass("hide");
                $("#weeklyform #result_container").removeClass("show");
                $("#weeklyform #clipbtn").addClass("hide");
                if(selectedValue!='') {
                    $("#weeklyform #bottom_container").removeClass("hide");
                    if(selectedValue=='Document Call') {
                        $("#weeklyform #document_container").removeClass("hide");
                        $("#weeklyform #clipbtn").removeClass("hide");
                    } else if(selectedValue=='Requested Call back') {
                        $("#weeklyform #request_callback_container").removeClass("hide");
                    } else if(selectedValue=='Attempt 1' || selectedValue=='Attempt 2' || selectedValue=='Attempt 3') {
                        $("#weeklyform #contact_attempt").removeClass("hide");
                        $("#weeklyform #result_container").removeClass("hide");
                        $("#weeklyform #result_container").addClass("show");
                    }
                }
            });

            $('#weeklyform #requested_callback').datetimepicker({
                format: 'Y-m-d H:i:s',
                lang: 'en',
            });

            $('#weeklyform #result').multiselect({
                includeSelectAllOption: true,
                maxHeight: 135
            });

            $(document).ready(function(){
                $(document).on("change","#coverage",function(){
                    if($(this).find(":selected").val()=='Coverage with stipulations'){
                        $('.coveragestipulations').removeClass("hide");
                    } else {
                        $('.coveragestipulations').addClass("hide");
                    }
                });
                $("input[name=mediaconsentonfile]").click(function(){

                    if($(this).val()=="0") {
                        $(".mediaconsentonfilecontainer").removeClass("hide");
                    } else {
                        $(".mediaconsentonfilecontainer").addClass("hide");
                    }
                });
                if($('input[name="mediaconsentonfile"]:checked').val()==1) {
                    $(".mediaconsentonfilecontainer").addClass("hide");
                } else {
                    $(".mediaconsentonfilecontainer").removeClass("hide");
                }

                $('.selectpicker').multiselect({
                    includeSelectAllOption: true
                });

            });
        </script>

    @else

    <script>
        $('#weeklyform #requested_callback').datetimepicker({
            format: 'Y-m-d H:i:s',
            lang: 'en',
        });
        function copyFormToClipboard(){
            $("#weeklyform #contact_comment").val(null);
            $("#weeklyform #requested_callback").val(null);
            $("#weeklyform #result").multiselect('clearSelection');
            var str = '';
            $("#weeklyform input[type=text],#weeklyform input[type=checkbox],#weeklyform input[type=time],#weeklyform textarea,#weeklyform select, #weeklyform input[type=radio]").each(function(ind,elm){
                if($(elm).attr('id') == 'mediaconsentreason'){
                    if($(elm).val() != '') {
                        str += 'Reason'+': '+$(elm).val() + '\n';
                    }
                }else if($(elm).attr('type') == 'checkbox'){
                    if($(elm).is(':checked')){
                        if($(elm).hasClass("form-check-input")){

                        }else{
                            str += $(elm).siblings('label').html()+ '\n';
                        }
                    }
                } else if($(elm).attr('type') == 'radio'){
                    if($(elm).is(':checked')){
                        str += $(elm).parent().parent().find('label').html()+ ' ' +$(elm).next('label').html()+ '\n';
                    }
                }else{
                    if($(elm).val() != '') {
                        if($(elm).siblings('label').html() === undefined){
                            str += 'Coverage stipulations: '+$(elm).val() + '\n';
                        }else{
                            str += $(elm).siblings('label').html()+': '+$(elm).val() + '\n';
                        }
                    }
                }
            });
            navigator.clipboard.writeText(str).then(function() {
                console.log('Copied!');
            }, function(err) {
                console.error('Could not copy text: ', err);
            });
        }

        $(document).on("change","#weeklyform #outcome",function(){
            var selectedValue =$(this).find(":selected").val();
            $("#weeklyform #request_callback_container").addClass("hide");
            $("#weeklyform #document_container").addClass("hide");
            $("#weeklyform #bottom_container").addClass("hide");
            $("#weeklyform #contact_attempt").addClass("hide");
            $("#weeklyform #result_container").addClass("hide");
            $("#weeklyform #result_container").removeClass("show");
            $("#weeklyform #clipbtn").addClass("hide");
            if(selectedValue!='') {
                $("#weeklyform #bottom_container").removeClass("hide");
                if(selectedValue=='Document Call') {
                    $("#weeklyform #document_container").removeClass("hide");
                    $("#weeklyform #clipbtn").removeClass("hide");
                } else if(selectedValue=='Requested Call back') {
                    $("#weeklyform #request_callback_container").removeClass("hide");
                } else if(selectedValue=='Attempt 1' || selectedValue=='Attempt 2' || selectedValue=='Attempt 3') {
                    $("#weeklyform #contact_attempt").removeClass("hide");
                    $("#weeklyform #result_container").removeClass("hide");
                    $("#weeklyform #result_container").addClass("show");
                }
            }
        });
        function saveWeeklyForm(e, button){
            e.preventDefault();
            if($('#weeklyform').valid()){
                //$('.disabled').removeAttr('disabled');
                button.disabled = true;
                let _token = $('meta[name="csrf-token"]').attr('content');
                var data = $('#weeklyform').serializeObject();
                data['_token'] = _token;
                $.ajax({
                    url: "{{route('ccc.saveweeklyinputcall')}}",
                    type: 'post',
                    data: data,
                    success: function(data) {
                        console.log(data);
                        button.disabled = false;
                        if(data.status){
                            isConfirmed = 1;
                            isSave = 1;
                            $('#weeklyFormModal').modal('hide');
                            $('#dataTable-clientcare-exception-weekly_nth_calls').DataTable().ajax.reload(null, false);

                            let _token = $('meta[name="csrf-token"]').attr('content');
                            $.ajax({
                                url: "{{route('ccc.progressbar')}}",
                                type: 'POST',
                                dateType:"html",
                                data: {
                                    bucketType:"weekly_nth_calls",
                                    _token: _token,
                                    filters:  $("#weeklyclientcareform").serializeArray()
                                },
                                success: function(res) {
                                    if(res.percentage==100) {
                                        startConfetti();
                                        setTimeout(function() {
                                            stopConfetti();
                                        }, 1000);
                                    }
                                }
                            });



                        }else{
                            console.log('Error:' + data.msg);
                        }

                    }
                });
            }
        }
        $(function()
            {
                $.validator.setDefaults({
                    ignore: [],
                    // any other default options and/or rules
                });
                $('#weeklyform').validate(
                {   rules:
                    {
                        textorphone:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        } },
                        spokewith:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        } },
                        recapofcall :{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        } },
                        verifiedinsurance:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        }  },
                        verifiedschedule:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        }  },
                        verifiedpets:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        }  },
                        verifiedliftingrequirements:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        }  },
                        verifiedweight:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        }  },
                        verifiednthticket:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        }  },
                        verifiedcliniciandiscipline:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        }  },
                        notesaboutrelationship: {
                            required:function(element){
                                return '#askedaboutrelationshipwithnurse:checked' && $("#weeklyform #outcome option:selected").val() == "Document Call";
                            }
                        },
                        requested_callback:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Requested Call back";
                        }  },

                        contact_comment:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Attempt 1" || $("#weeklyform #outcome option:selected").val() == "Attempt 2" || $("#weeklyform #outcome option:selected").val() == "Attempt 3";
                        }  },
                        result:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Attempt 1" || $("#weeklyform #outcome option:selected").val() == "Attempt 2" || $("#weeklyform #outcome option:selected").val() == "Attempt 3";
                        }  },
                        coverage:{required:function(element){
                            return $("#outcome option:selected").val() == "Document Call";
                        }},
                        rate:{ required:function(element){
                            return $("#weeklyform #outcome option:selected").val() == "Document Call";
                        } },
                        coveragestipulations:{required:function(element){
                            let c = $("#coverage option:selected").val();
                            if(c == 'Coverage with stipulations'){
                                return true;
                            }
                        }}
                    },
                    errorPlacement: function(error, element)
                    {
                        $('.error').css('position', 'initial');
                        if ( element.is(":radio") )
                        {
                            error.appendTo( element.parent().parent() );
                        }
                        if($(element).attr("id") == 'result') {
                            error.insertAfter(element.parent());
                        }
                    }
                });
                $('#weeklyform #result').multiselect({
                    includeSelectAllOption: true,
                    maxHeight: 135
                });
            }
        );
        $(document).ready(function(){
            $(document).on("change","#coverage",function(){
                if($(this).find(":selected").val()=='Coverage with stipulations'){
                    $('.coveragestipulations').removeClass("hide");
                } else {
                    $('.coveragestipulations').addClass("hide");
                }
            });
            $("input[name=mediaconsentonfile]").click(function(){

                if($(this).val()=="0") {
                    $(".mediaconsentonfilecontainer").removeClass("hide");
                } else {
                    $(".mediaconsentonfilecontainer").addClass("hide");
                }
            });
            if($('input[name="mediaconsentonfile"]:checked').val()==1) {
                $(".mediaconsentonfilecontainer").addClass("hide");
            } else {
                $(".mediaconsentonfilecontainer").removeClass("hide");
            }

            $('.selectpicker').multiselect({
                includeSelectAllOption: true
            });
        });
    </script>
    @endif
    <style>
            label#textorphone-error {
                position: absolute;
                left: 300%;
                width: 200px !important;
                top: -10px;
            }

            label#verifiedinsurance-error, label#verifiedschedule-error, label#verifiedpets-error, label#verifiedliftingrequirements-error, label#verifiedweight-error, label#verifiednthticket-error, label#verifiedcliniciandiscipline-error {
                position: absolute;
                width: 200px !important;
                left: 300%;
            }
    </style>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
