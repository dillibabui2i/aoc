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
    if($attemptCount==2){
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
    <x-slot name="id">monthlyFormModal</x-slot>
    <x-slot name="size">w80</x-slot>
    <x-slot name="title">Monthly Call</x-slot>
    <x-slot name="class">monthly-call-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a href="javascript:void(0)" onclick="getClientDetails('{{$clientDetails->id}}')"><i class="fa fa-user" aria-hidden="true"></i></a>
        </div>
    </x-slot>
    <x-slot name="body">
    <form id="monthlyform" class="monthlyform">

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
        <section class="main-container">
            <div class="row">
                <div class="col-md-6 pt-3 pl-3">
                    <label  class="w-auto" for="outcome">Outcome:</label>
                    <select class="form-control  outcome" name="outcome" id="outcome">
                        <option value="">Select Outcome</option>
                        <?php  foreach($outcomeList as $outcome) { ?>
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
            <div class="col-md-12 pl-0">
                <label class="w-100">Comment:</label>
                <textarea name="contact_comment" id="contact_comment"  class="form-control"></textarea>
            </div>
        </section>

        <section class="triage-details p-2 hide pl-3" id="document_container">
            <div class="form-group row">
                <div class="col-md-12 pl-0">
                    <input type="hidden" name="aoc_patient_id" id="aoc_patient_id" value="<?php echo $aocPatientId?>" />
                    <label  class="w-100" for="callerLastName">Spoke With:</label>
                    <input type="text" class="form-control general" id="spokewith" name="spokewith" value=""/>
                </div>

                <div class="col-md-12  pl-0">
                    <label  class="w-100" for="clientRelationship">Text, Phone, or Email:</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" name="textorphone"  id="textorphonetext" value="Text" />
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

                <div class="col-md-12  pl-0 d-flex">
                    <label  class="w-auto" for="doctorverified">Doctor Verified :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" checked="checked" type="radio" id="doctorverified_none" name="doctorverified"  value="">
                        <label class="form-check-label" for="doctorverified_none">None</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="doctorverified_yes" name="doctorverified"  value="1">
                        <label class="form-check-label" for="doctorverified_yes">Yes</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="doctorverified_no" name="doctorverified"  value="0">
                        <label class="form-check-label" for="doctorverified_no">No</label>
                    </div>

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="doctorverified" value="1" name="doctorverified"/>
                        <label class="custom-control-label" for="doctorverified"></label>
                    </div>-->
                </div>

                <div class="col-md-12  pl-0 d-flex">
                    <label  class="w-auto" for="verifiedinsurance">Verified insurance :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input"  checked="checked" type="radio" id="verifiedinsurance_none" name="verifiedinsurance"  value="">
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

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="verifiedinsurance" value="1" name="verifiedinsurance"/>
                        <label class="custom-control-label" for="verifiedinsurance"></label>
                    </div>-->
                </div>

                <div class="col-md-12  pl-0 d-flex">
                    <label  class="w-auto" for="verifiedpatient">Verified patient data/classification :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" checked="checked" type="radio" id="verifiedpatient_none" name="verifiedpatient"  value="">
                        <label class="form-check-label" for="verifiedpatient_none">None</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="verifiedpatient_yes" name="verifiedpatient"  value="1">
                        <label class="form-check-label" for="verifiedpatient_yes">Yes</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="verifiedpatient_no" name="verifiedpatient"  value="0">
                        <label class="form-check-label" for="verifiedpatient_no">No</label>
                    </div>

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="verifiedpatient" value="1" name="verifiedpatient"/>
                        <label class="custom-control-label" for="verifiedpatient"></label>
                    </div>-->
                </div>


                <div class="col-md-12  pl-0 d-flex">
                    <label  class="w-auto" for="upcomingvacations">Upcoming vacations  :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" checked="checked" type="radio" id="upcomingvacations_none" name="upcomingvacations"  value="">
                        <label class="form-check-label" for="upcomingvacations_none">None</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="upcomingvacations_yes" name="upcomingvacations"  value="1">
                        <label class="form-check-label" for="upcomingvacations_yes">Yes</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="upcomingvacations_no" name="upcomingvacations"  value="0">
                        <label class="form-check-label" for="upcomingvacations_no">No</label>
                    </div>

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="upcomingvacations" value="1" name="upcomingvacations"/>
                        <label class="custom-control-label" for="upcomingvacations"></label>
                    </div>-->
                </div>


                <div class="col-md-12  pl-0  upcomingvacationscontainer hide">
                    <div class="col-md-12">
                        <label  class="w-auto" for="whereareyougoing">Where are you going:</label>
                        <input type="text" class="form-control" name="whereareyougoing" id="whereareyougoing" value=""/>
                    </div>

                    <div class="col-md-12 p-0 m-0">
                        <div class="col-md-12  m-0">
                            <label  class="w-auto" for="selecteddate">Select  Date:</label>
                        </div>
                        <div class="col-md-12 p-0 m-0">
                            <div class="col-md-3 pull-left">
                                <input type="text"  class="form-control" name="fromdate" id="fromdate" placeholder="From Date" value=""/>
                            </div>

                            <div class="col-md-3 pull-left">
                                <input type="text" class="form-control" name="todate" id="todate" placeholder="To Date" value=""/>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12  d-flex p-3">
                        <label  class="w-auto" for="needtotakenurse">Need to take nurse? :</label>


                        <div class="form-check form-check-inline general">
                            <input class="form-check-input" checked="checked" type="radio" id="needtotakenurse_none" name="needtotakenurse"  value="">
                            <label class="form-check-label" for="needtotakenurse_none">None</label>
                        </div>

                        <div class="form-check form-check-inline general">
                            <input class="form-check-input" type="radio" id="needtotakenurse_yes" name="needtotakenurse"  value="1">
                            <label class="form-check-label" for="needtotakenurse_yes">Yes</label>
                        </div>

                        <div class="form-check form-check-inline general">
                            <input class="form-check-input" type="radio" id="needtotakenurse_no" name="needtotakenurse"  value="0">
                            <label class="form-check-label" for="needtotakenurse_no">No</label>
                        </div>


                       <!-- <div class="custom-control custom-switch custom-switch-lg text-center">
                            <input class="custom-control-input" type="checkbox" id="needtotakenurse" value="1" name="needtotakenurse"/>
                            <label class="custom-control-label" for="needtotakenurse"></label>
                        </div>-->
                    </div>
                </div>

                <div class="col-md-12  pl-0 d-flex">
                    <label  class="w-auto" for="workschedulechanged">Work schedule changed (respite) :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" checked="checked" type="radio" id="workschedulechanged_none" name="workschedulechanged"  value="">
                        <label class="form-check-label" for="workschedulechanged_none">None</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="workschedulechanged_yes" name="workschedulechanged"  value="1">
                        <label class="form-check-label" for="workschedulechanged_yes">Yes</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="workschedulechanged_no" name="workschedulechanged"  value="0">
                        <label class="form-check-label" for="workschedulechanged_no">No</label>
                    </div>

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="workschedulechanged" value="1" name="workschedulechanged"/>
                        <label class="custom-control-label" for="workschedulechanged"></label>
                    </div>-->
                </div>

                <div class="col-md-12  pl-0 workschedulechangedcontainer hide">
                    <div class="col-md-12">
                        <input type="text" class="form-control" name="workschedulefreetext" id="workschedulefreetext" placeholder="Type here..." value=""/>
                    </div>
                </div>

                <div class="col-md-12  pl-0 d-flex">
                    <label  class="w-auto" for="verifiedprefrences">Verified preferences :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" checked="checked" type="radio" id="verifiedprefrences_none" name="verifiedprefrences"  value="">
                        <label class="form-check-label" for="verifiedprefrences_none">None</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="verifiedprefrences_yes" name="verifiedprefrences"  value="1">
                        <label class="form-check-label" for="verifiedprefrences_yes">Yes</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="verifiedprefrences_no" name="verifiedprefrences"  value="0">
                        <label class="form-check-label" for="verifiedprefrences_no">No</label>
                    </div>

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="verifiedprefrences" value="1" name="verifiedprefrences"/>
                        <label class="custom-control-label" for="verifiedprefrences"></label>
                    </div>-->
                </div>

                <div class="col-md-12  pl-0 d-flex">
                    <label  class="w-auto" for="verifiedscheduleandauth">Verified schedule and auth :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" checked="checked" type="radio" id="verifiedscheduleandauth_none" name="verifiedscheduleandauth"  value="">
                        <label class="form-check-label" for="verifiedscheduleandauth_none">None</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="verifiedscheduleandauth_yes" name="verifiedscheduleandauth"  value="1">
                        <label class="form-check-label" for="verifiedscheduleandauth_yes">Yes</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="verifiedscheduleandauth_no" name="verifiedscheduleandauth"  value="0">
                        <label class="form-check-label" for="verifiedscheduleandauth_no">No</label>
                    </div>

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="verifiedscheduleandauth" value="1" name="verifiedscheduleandauth"/>
                        <label class="custom-control-label" for="verifiedscheduleandauth"></label>
                    </div>-->
                </div>

                <div class="col-md-12  pl-0 d-flex">
                    <label  class="w-auto" for="discussednecessaryjournal">Discussed necessary journal notes :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" checked="checked" type="radio" id="discussednecessaryjournal_none" name="discussednecessaryjournal"  value="">
                        <label class="form-check-label" for="discussednecessaryjournal_none">None</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="discussednecessaryjournal_yes" name="discussednecessaryjournal"  value="1">
                        <label class="form-check-label" for="discussednecessaryjournal_yes">Yes</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="discussednecessaryjournal_no" name="discussednecessaryjournal"  value="0">
                        <label class="form-check-label" for="discussednecessaryjournal_no">No</label>
                    </div>

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="discussednecessaryjournal" value="1" name="discussednecessaryjournal"/>
                        <label class="custom-control-label" for="discussednecessaryjournal"></label>
                    </div>-->
                </div>

                <div class="col-md-12  pl-0 d-flex">
                    <label  class="w-auto" for="schoolsetup">School setup :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" checked="checked" type="radio" id="schoolsetup_none" name="schoolsetup"  value="">
                        <label class="form-check-label" for="schoolsetup_none">None</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="schoolsetup_yes" name="schoolsetup"  value="1">
                        <label class="form-check-label" for="schoolsetup_yes">Yes</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="schoolsetup_no" name="schoolsetup"  value="0">
                        <label class="form-check-label" for="schoolsetup_no">No</label>
                    </div>

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="schoolsetup" value="1" name="schoolsetup"/>
                        <label class="custom-control-label" for="schoolsetup"></label>
                    </div>-->
                </div>

                <div class="col-md-12  pl-0 d-flex">

                    <label  class="w-auto" for="askedaboutrelationshipwithnurse">Asked about relationship with in-home nurses :</label>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" checked="checked" type="radio" id="askedaboutrelationshipwithnurse_none" name="askedaboutrelationshipwithnurse"  value="">
                        <label class="form-check-label" for="askedaboutrelationshipwithnurse_none">None</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="askedaboutrelationshipwithnurse_yes" name="askedaboutrelationshipwithnurse"  value="1">
                        <label class="form-check-label" for="askedaboutrelationshipwithnurse_yes">Yes</label>
                    </div>

                    <div class="form-check form-check-inline general">
                        <input class="form-check-input" type="radio" id="askedaboutrelationshipwithnurse_no" name="askedaboutrelationshipwithnurse"  value="0">
                        <label class="form-check-label" for="askedaboutrelationshipwithnurse_no">No</label>
                    </div>

                    <!--<div class="custom-control custom-switch custom-switch-lg text-center">
                        <input class="custom-control-input" type="checkbox" id="askedaboutrelationshipwithnurse" value="1" name="askedaboutrelationshipwithnurse"/>
                        <label class="custom-control-label" for="askedaboutrelationshipwithnurse"></label>
                    </div>-->

                </div>

                <div class="col-md-12  pl-0">
                    <label class="w-100">Notes about relationship with in-home nurses (if applicable):</label>
                    <textarea name="notesaboutrelationship" id="notesaboutrelationship" class="form-control"></textarea>
                </div>

                <div class="col-md-12">
                    <label class="w-100">On a scale of 1 to 10, how would you rate your experience at Angels of Care?</label>
                    <div class="satisfaction-rating">
                        <input type="radio" name="rate" id="rate2-10" value="10"><label for="rate2-10">10</label>
                        <input type="radio" name="rate" id="rate2-9" value="9"><label for="rate2-9">9</label>
                        <input type="radio" name="rate" id="rate2-8" value="8"><label for="rate2-8">8</label>
                        <input type="radio" name="rate" id="rate2-7" value="7"><label for="rate2-7">7</label>
                        <input type="radio" name="rate" id="rate2-6" value="6"><label for="rate2-6">6</label>
                        <input type="radio" name="rate" id="rate2-5" value="5"><label for="rate2-5">5</label>
                        <input type="radio" name="rate" id="rate2-4" value="4"><label for="rate2-4">4</label>
                        <input type="radio" name="rate" id="rate2-3" value="3"><label for="rate2-3">3</label>
                        <input type="radio" name="rate" id="rate2-2" value="2"><label for="rate2-2">2</label>
                        <input type="radio" name="rate" id="rate2-1" value="1"><label for="rate2-1">1</label>
                        <input type="radio" name="rate" id="rate2-0" value="0"><label for="rate2-0">0</label>
                    </div>
                </div>
                <div class="col-md-12 pl-0">
                    <label class="w-100">Recap of call:</label>
                    <textarea name="recapofcall" id="recapofcall" class="form-control"></textarea>
                </div>



            </div>
        </section>

        <section id="request_callback_container" class="hide">
            <div class="form-group col-md-12  pl-0">
                <div class="mt-2 col-md-6 pl-0">
                    <input type="text" class="form-control general" placeholder="Choose Callback DateTime" id="requested_callback" name="requested_callback" value=""/>
                </div>
            </div>
        </section>

        <section id="bottom_container" class="hide">
            <div class="form-group col-md-12 pl-0">
                <div class="col-md-6 text-left pl-0 pt-3">
                <button class="btn btn-primary" onclick="saveMonthlyForm(event, this);">Save</button>
                <a class="copy-form-btn" id="clipbtn" onclick="copyFormToClipboard();">Copy Text from Form</a>
                </div>
            </div>

        </section>
        </div>


    </form>

    <script>
         $('#monthlyform #requested_callback').datetimepicker({
            format: 'Y-m-d H:i:s',
            lang: 'en',
        });

        function copyFormToClipboard(){
            $("#monthlyform #contact_comment").val(null);
            $("#monthlyform #requested_callback").val(null);
            $("#monthlyform #result").multiselect('clearSelection');
            var str = '';
            $("#monthlyform input[type=text],#monthlyform input[type=checkbox],#monthlyform input[type=time],#monthlyform textarea,#monthlyform select, #monthlyform input[type=radio]").each(function(ind,elm){
                if($(elm).attr('id') == 'reason'){
                    if($(elm).val() != '') {
                        str += 'Reason'+': '+$(elm).val() + '\n';
                    }
                }else if($(elm).attr('type') == 'checkbox'){
                    if($(elm).is(':checked')){
                        str += $(elm).siblings('label').html()+ '\n';
                    }
                } else if($(elm).attr('type') == 'radio'){
                    if($(elm).is(':checked')){
                        if($(".upcomingvacationscontainer").is(":hidden") && $(elm).parent().parent().find('label').html()=='Need to take nurse? :'){

                        }else{
                            str += $(elm).parent().parent().find('label').html()+ ' ' +$(elm).next('label').html()+ '\n';
                        }
                    }
                }else{
                    if($(elm).val() != '') {
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
        $(document).on("change","#monthlyform #outcome",function(){
            var selectedValue =$(this).find(":selected").val();
            $("#monthlyform #request_callback_container").addClass("hide");
            $("#monthlyform #document_container").addClass("hide");
            $("#monthlyform #bottom_container").addClass("hide");
            $("#monthlyform #contact_attempt").addClass("hide");
            $("#monthlyform #clipbtn").addClass("hide");
            $("#monthlyform #result_container").addClass("hide");
            if(selectedValue!='') {
                $("#monthlyform #bottom_container").removeClass("hide");
                if(selectedValue=='Document Call') {
                    $("#monthlyform #document_container").removeClass("hide");
                    $("#monthlyform #clipbtn").removeClass("hide");
                } else if(selectedValue=='Requested Call back') {
                    $("#monthlyform #request_callback_container").removeClass("hide");
                }  else if(selectedValue=='Attempt 1' || selectedValue=='Attempt 2' || selectedValue=='Attempt 3') {
                    $("#monthlyform #contact_attempt").removeClass("hide");
                    $("#monthlyform #result_container").removeClass("hide");
                }
            }
        });
            function saveMonthlyForm(e, button){
                e.preventDefault();
                if($('#monthlyform').valid()){
                    button.disabled = true;
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    var data = $('#monthlyform').serializeObject();
                    data['_token'] = _token;
                    $.ajax({
                        url: "{{route('ccc.savemonthlyinputcall')}}",
                        type: 'post',
                        data: data,
                        success: function(data) {
                            console.log(data);
                            button.disabled = false;
                            if(data.status){
                                isConfirmed = 1;
                                isSave = 1;
                                $('#monthlyFormModal').modal('hide');
                                $('#dataTable-clientcare-exception-monthly_calls').DataTable().ajax.reload(null, false);

                                let _token = $('meta[name="csrf-token"]').attr('content');
                                $.ajax({
                                    url: "{{route('ccc.progressbar')}}",
                                    type: 'POST',
                                    dateType:"html",
                                    data: {
                                        bucketType:"monthly_calls",
                                        _token: _token,
                                        filters:  $("#monthlyclientcareform").serializeArray()
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


            $("input[name=upcomingvacations]").click(function(){
                if($(this).val()=="1") {
                    $(".upcomingvacationscontainer").removeClass("hide");
                } else {
                    $(".upcomingvacationscontainer").addClass("hide");
                }
            });

            $("input[name=workschedulechanged]").click(function(){
                if($(this).val()=="1") {
                    $(".workschedulechangedcontainer").removeClass("hide");
                } else {
                    $(".workschedulechangedcontainer").addClass("hide");
                }
            });



            $( function() {
                var dateFormat = "mm/dd/yy",
                from = $( "#fromdate" )
                    .datepicker({
                    changeMonth: true,
                    numberOfMonths: 1
                    })
                    .on( "change", function() {
                    to.datepicker( "option", "minDate", getDate( this ) );
                    }),
                to = $( "#todate" ).datepicker({
                    changeMonth: true,
                    numberOfMonths: 1
                })
                .on( "change", function() {
                    from.datepicker( "option", "maxDate", getDate( this ) );
                });

                function getDate( element ) {
                var date;
                try {
                    date = $.datepicker.parseDate( dateFormat, element.value );
                } catch( error ) {
                    date = null;
                }

                return date;
                }
            });

            $(function()
                {
                    $.validator.setDefaults({
                        ignore: [],
                        // any other default options and/or rules
                    });
                    $('#monthlyform').validate(
                    {   rules:
                        {   textorphone:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        } },
                            doctorverified:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        } },
                            verifiedinsurance:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        } },
                            verifiedpatient:{required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        }},
                            verifiedprefrences:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        } },
                            verifiedscheduleandauth:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        } },
                            discussednecessaryjournal:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        } },
                        notesaboutrelationship: {
                            required:'#askedaboutrelationshipwithnurse:checked'
                        },

                        requested_callback:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Requested Call back";
                        }  },

                        contact_comment:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Attempt 1" || $("#monthlyform #outcome option:selected").val() == "Attempt 2" || $("#monthlyform #outcome option:selected").val() == "Attempt 3";
                        }  },
                        result:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Attempt 1" || $("#monthlyform #outcome option:selected").val() == "Attempt 2" || $("#monthlyform #outcome option:selected").val() == "Attempt 3";
                        }  },
                        rate:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        } },
                        recapofcall:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        } },
                        spokewith:{ required:function(element){
                            return $("#monthlyform #outcome option:selected").val() == "Document Call";
                        } },
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

                    $('#monthlyform #result').multiselect({
                        includeSelectAllOption: true,
                        maxHeight: 135
                    });

                }
            );
    </script>

    <style>
            label#textorphone-error {
                position: absolute;
                left: 400%;
                width: 200px !important;
                top: -10px;
            }

            #monthlyform .form-check.form-check-inline.general {
                top: -3px;
            }
            label#doctorverified-error,
            label#verifiedinsurance-error,
            label#verifiedpatient-error,
            label#upcomingvacations-error,
            label#workschedulechanged-error,
            label#verifiedprefrences-error,
            label#verifiedscheduleandauth-error,
            label#discussednecessaryjournal-error,
            label#schoolsetup-error
            {
                position: absolute;
                width: 200px !important;
                left: 300%;
            }
    </style>


    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
