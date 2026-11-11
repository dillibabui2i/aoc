<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\ConstantHelper;
    use App\Enums\IntakeReferralTypeEnum;
    $recruiter = H::getHelper('ConstantHelper')->getRecruitersList();
    $selectedServicesNeeded = [];
    if(isset($needToHireAccounts->ServicesNeeded)){
        $selectedServicesNeeded = explode(',',$needToHireAccounts->ServicesNeeded);
    }
    $selectedSkills = [];
    if(isset($needToHireAccounts->skills)){
        $selectedSkills = explode(',',$needToHireAccounts->skills);
    }
    $facilities = H::getHelper("ConstantHelper")->getFacilities();
    $icList = H::getHelper("ConstantHelper")->getIntakeCoordinator();
    $clinicalCareManager = H::getHelper("ConstantHelper")->getClinicalCareManager();
    $department = ['Authorization Case Manager', 'Authorization Clinical Manager'];
    $nurseList = H::getHelper('ConstantHelper')->getUserListByRoles($department, true);
    $lobName = H::getHelper('ConstantHelper')->getLob();
    $intake = $intakeData ?? '';
    if(!empty($intake)){
        if(!empty($intake['start_time'])){
            $start_time = $intake['start_time'];
            $time_parts = explode(":", $start_time);
            $hours = intval($time_parts[0]);
            $minutes = intval($time_parts[1]);
            $ampm = $hours >= 12 ? 'pm' : 'am';
            $hours = $hours % 12;
            $hours = $hours ? $hours : 12;
            $formatted_start_time = $hours . ':' . str_pad($minutes, 2, "0", STR_PAD_LEFT) . $ampm;
        }
        if(!empty($intake['end_time'])){
            $end_time = $intake['end_time'];
            $time_parts = explode(":", $end_time);
            $hours = intval($time_parts[0]);
            $minutes = intval($time_parts[1]);
            $ampm = $hours >= 12 ? 'pm' : 'am';
            $hours = $hours % 12;
            $hours = $hours ? $hours : 12;
            $formatted_end_time = $hours . ':' . str_pad($minutes, 2, "0", STR_PAD_LEFT) . $ampm;
        }
        $skilsArray = [];
        if(!empty($intake['skills'])){
            $skilsArray = explode(',', $intake['skills']);
        }
    }
    $intakeDiscipline = config('constant.intakeDiscipline');
    $approvedByList = [];
    $approvedUserList = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates');
    foreach($approvedUserList as $user) {
        $approvedByList[$user->id] = $user->name;
    }
    $bd_reps = H::getUsersByRoles([28, 29]);

    $selectedReferral = '';
    if (!empty($intake['referralType'])) {
        $selectedReferral = strtolower(IntakeReferralTypeEnum::normalize($intake['referralType']));
    }
?>
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<style>
    .modal-body{
        overflow: visible;
    }

    #cover-spin {
        position:fixed;
        width:100%;
        left:0;right:0;top:0;bottom:0;
        background-color: rgba(255,255,255,0.5);
        z-index:9999;
        display:none;
    }

    @-webkit-keyframes spin {
        from {-webkit-transform:rotate(0deg);}
        to {-webkit-transform:rotate(360deg);}
    }

    @keyframes spin {
        from {transform:rotate(0deg);}
        to {transform:rotate(360deg);}
    }

    #cover-spin::after {
        content:'';
        display:block;
        position:absolute;
        left:48%;top:40%;
        width:40px;height:40px;
        border-style:solid;
        border-color:#215eac;
        border-top-color:transparent;
        border-width: 4px;
        border-radius:50%;
        -webkit-animation: spin .8s linear infinite;
        animation: spin .8s linear infinite;
    }
</style>
<x-modal>
    <x-slot name="id">addIntakeFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    @if(!empty($intake))
        <x-slot name="title">Edit Intake</x-slot>
    @else
        <x-slot name="title">Add New Intake</x-slot>
    @endif
    <x-slot name="class">add-intake-form</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="addIntakeForm" class="addIntakeForm">
        <section class="intake-details">
            @if(LD::checkFeatureFlag('intake-form-new-change'))
            <div class="form-group row">
                @if(!empty($intake))
                    <div class="col-md-6">
                        <label  class="w-100" for="ic_id">Intake Coordinator</label>
                        <select class="form-control" name="ic_id" id="ic_id">
                            <option value="">-- Select One --</option>
                            @foreach($icList as $ic)
                                <option {{(!empty($intake) && $intake['ic_id'] == $ic['value'])
                                ? "selected" : ""}} value="{{$ic['value']}}">{{$ic['text']}}</option>
                            @endforeach
                        </select>
                    </div>
                @else
                    <div class="col-md-6">
                        <label  class="w-100" for="ic_id">Intake Coordinator</label>
                        <select class="form-control" name="ic_id" id="ic_id">
                            <option value="">-- Select One --</option>
                            @foreach($icList as $ic)
                                <option {{isset($patientsDetails->ic_id) && $patientsDetails->ic_id == $ic['value'] ? "selected='selected'" : ""}} value="{{$ic['value']}}">{{$ic['text']}}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-6">
                    <label for="clientId">Client ID</label>
                    <input type="text" class="form-control client_disable" id="clientId" name="clientId"
                    value="{{$intake ? $intake['clientId'] : ''}}" />
                </div>
            </div>
            @endif
            <div class="form-group row">
                @if(LD::checkFeatureFlag('intake-form-new-change'))
                <input type="hidden" name="clientPId" id="clientPId" value="{{ $intake ? $intake['clientPId']: '' }}">
                <input type="hidden" name="intakeid" id="intakeid" value="{{ $intake ? $intake['intakeid'] : '' }}">
                <input type="hidden" name="nthID" id="nthID" value="{{ $intake ? $intake['nthID'] : '' }}">
                <input type="hidden" name="mode" id="mode" value="{{ $intake ? 'Edit' : '' }}">
                @endif
                @if(!empty($intake))
                    <div class="col-md-6">
                        <label  class="w-100" for="clientName">Client Name</label>
                        <input type="text" class="form-control client_disable" id="clientName" name="clientName"
                        value="{{$intake ? $intake['clientName'] : ''}}"
                        readonly/>
                    </div>
                @else
                    <div class="col-md-6">
                        <label  class="w-100" for="clientName">Client Name</label>
                        <input type="text" class="form-control client_disable" id="clientName" name="clientName" value=""/>
                    </div>
                @endif
                <div class="col-md-6 d-flex">
                    @if(LD::checkFeatureFlag('intake-form-new-change'))
                        <div class="col-md-6" style="padding-left: initial;">
                            <label for="lob">Line of Business</label>
                            <select name="lob" id="lob" class="form-control">
                                <option value="">--- Select ---</option>
                                @foreach($lobName as $v)
                                <option value="{{$v->LOBID}}"
                                {{(!empty($intake) && $intake['LOBID'] == $v->LOBID)
                                ? 'selected' : ''}}>
                                {{$v->LOBName}}</option>
                                @endforeach
                            </select>
                            @if(LD::checkFeatureFlag('prevent-client-from-adding-duplicate-lob-in-intake'))
                                <span id="lob-error" class="lob-error-message" style="color: red; display: none;">
                                    Client has an existing LOB. Please navigate to their record to make any necessary changes.
                                </span>
                            @endif
                            @if(LD::checkFeatureFlag('not-allowed-to-select-col-client-in-intake-form'))
                                <span id="col-error" class="col-error-message" style="color: red; display: none;">
                                    This client is already listed on the Intake Dashboard. Please locate the existing intake and assign the appropriate Line of Business (LOB).
                                </span>
                            @endif
                        </div>
                    @endif
                    <div class="col-md-6">
                        <label class="w-100" for="specializedClient">Specialized Client?</label>
                        <div class="custom-control custom-switch custom-switch-lg">
                            <input readonly="true" class="custom-control-input" type="checkbox"
                            {{(!empty($intake) && $intake['specializedClient'] == 'Yes') ? 'checked' : ''}}
                            id="specializedClient" name="specializedClient"/>
                            <label class="custom-control-label" for="specializedClient"></label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="newLob hide">
                <div class="form-group row">
                    <div class="col-md-6">
                        <label  class="w-100" for="referral_date">Referral Date</label>
                        <input type="text" class="form-control datepicker" id="referral_date"
                        name="referral_date"
                        value="{{ (!empty($intake) && !empty($intake['referral_date']))
                    ? date('m/d/y', strtotime($intake['referral_date'])) : date('m/d/Y') }}"/>
                    </div>
                    <div class="col-md-6">
                        <label  class="w-100" for="referral_source">Referral Source</label>
                        <input type="text" class="form-control" id="referral_source" name="referral_source"
                        value="{{$intake ? $intake['referral_source'] : ''}}"/>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label  class="w-100" for="referring_physician">Referring Physician</label>
                        <input type="text" class="form-control" id="referring_physician"
                        name="referring_physician"
                        value="{{$intake ? $intake['referring_physician'] : ''}}"/>
                    </div>
                    <div class="col-md-6">
                        <label  class="w-100" for="business_development_rep">Business Development Rep</label>
                        <select name="business_development_rep" id="business_development_rep"
                        class="form-control">
                            <option value="">select</option>
                            @foreach($bd_reps as $key => $bd_rep)
                                <option value="{{$key}}"
                                {{(!empty($intake) && $intake['business_development_rep'] == $bd_rep)
                                ? 'selected': ""}}>{{$bd_rep}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <div class="col-md-6">
                        <label  class="w-100" for="living_situation">Living Situation</label>
                        <select name="living_situation" id="living_situation"
                        class="form-control">
                            <option value="">Select</option>
                            <option value="Lives Alone"
                            {{(!empty($intake) && $intake['living_situation'] == 'Lives Alone')
                            ? 'selected': ""}}>Lives Alone</option>
                            <option value="Lives with Family"
                            {{(!empty($intake) && $intake['living_situation'] == 'Lives with Family')
                            ? 'selected': ""}}>Lives with Family</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-100" for="address1">Address Line 1</label>
                    <input type="text" class="form-control client-data client_disable" id="address1" name="address1"
                    value="{{$intake ? $intake['address1'] : ''}}"/>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="address2">Address Line 2</label>
                    <input type="text" class="form-control client-data client_disable" id="address2" name="address2"
                    value="{{$intake ? $intake['address2'] : ''}}"/>
                </div>

            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label for="city">City</label>
                    <input type="text" class="form-control client-data client_disable" id="city" name="city"
                    value="{{$intake ? $intake['city'] : ''}}"/>
                </div>
                <div class="col-md-6">
                    <label for="state">State</label>
                    <input type="text" class="form-control client-data client_disable" id="state" name="state"
                    value="{{$intake ? $intake['state'] : ''}}"/>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label for="zipCode">Zip Code</label>
                    <input type="text" class="form-control client-data client_disable" id="zipCode" name="zipCode"
                    value="{{$intake ? $intake['zipCode'] : ''}}"/>
                </div>
                <div class="col-md-6">
                    <label for="aoc_facility_id">Branch</label>
                    <select class="form-control client_disable" name="aoc_facility_id" id="aoc_facility_id">
                        <option value="">-- Select One --</option>
                        @foreach($facilities as $facility)
                            <option value="{{$facility->id}}"
                            {{(!empty($intake) && $intake['aoc_facility_id'] == $facility->id)
                            ? 'selected': ""}}>
                            {{$facility->FacilityName}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group row mt-5">
                <div class="col-md-6">
                    <label  class="w-100" for="linkToKanTime">Link To KanTime</label>
                    <input type="text" class="form-control" id="linkToKanTime" name="linkToKanTime"
                    value="{{$intake ? $intake['linkToKanTime'] : ''}}"/>
                </div>
                <div class="col-md-6">
                    <label for="referralType">Referral Type</label>
                    <select name="referralType" id="referralType" class="form-control">
                        <option value="">--- Select ---</option>
                        @foreach(IntakeReferralTypeEnum::cases() as $v)
                        <option value="{{$v}}"
                        {{(!empty($intake) && $selectedReferral === strtolower($v->value))
                        ? 'selected' : ''}}>
                        {{$v}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-group row referralTypeFields hide">
                <div class="col-md-6">
                    <label  class="w-100" for="hospital">Hospital</label>
                    <input type="text" class="form-control" id="hospital" name="hospital"
                    value="{{$intake ? $intake['hospital'] : ''}}"/>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="hospitalDischargeDate">Hospital Discharge Date</label>
                    <input type="text" class="form-control datepicker" id="hospitalDischargeDate"
                    name="hospitalDischargeDate"
                    value="{{ (!empty($intake) && !empty($intake['hospitalDischargeDate']))
                    ? date('m/d/y', strtotime($intake['hospitalDischargeDate'])) : '' }}"/>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-100" for="anticipatedHours">Anticipated Hours/Visits</label>
                    <input type="text" class="form-control" id="anticipatedHours"
                    name="anticipatedHours"
                    value="{{$intake ? $intake['anticipatedHours'] : ''}}"/>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="goalSocDate">Goal SOC Date <span style="color: red;">(First Billable Shift)</span></label>
                    <input type="text" class="form-control datepicker" id="goalSocDate"
                    name="goalSocDate"
                    value="{{ (!empty($intake) && !empty($intake['goalSocDate']))
                    ? date('m/d/y', strtotime($intake['goalSocDate'])) : '' }}"/>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-md-6">
                    <label class="w-100" for="terminalPlayer">Terminal Payer</label>
                    <input type="text" class="form-control" id="terminalPlayer"
                    name="terminalPlayer"
                    value="{{$intake ? $intake['terminalPlayer'] : ''}}"/>
                </div>
                <div class="col-md-6">
                    <label class="w-100" for="respiteTerminalPlayer">Respite Terminal Payer</label>
                    <input type="text" class="form-control" id="respiteTerminalPlayer"
                    name="respiteTerminalPlayer"
                    value="{{$intake ? $intake['respiteTerminalPlayer'] : ''}}"/>
                </div>
            </div>
            @if(LD::checkFeatureFlag('rearrange-auth-nurse-field-in-intake-form'))
                <div class="form-group row">
                    <div class="col-md-6">
                        <label  class="w-100" for="auth_nurse_id">Authorization Clinician</label>
                        <select class="form-control" name="auth_nurse_id" id="auth_nurse_id">
                            <option value="">-- Select One --</option>
                            @foreach($nurseList as $ic)
                                <option {{(!empty($intake) && $intake['auth_nurse_id'] == $ic['value'])
                                ? 'selected' : ''}}
                                value="{{$ic['value']}}">{{$ic['text']}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="notes">Notes</label>
                        <textarea name="notes"
                        id="notes"
                        class="form-control">{{$intake ? $intake['notes'] : ''}}</textarea>
                    </div>
                </div>
            @else
                <div class="form-group row">
                    <div class="col-md-12">
                        <label for="notes">Notes</label>
                        <textarea name="notes"
                        id="notes"
                        class="form-control">{{$intake ? $intake['notes'] : ''}}</textarea>
                    </div>
                </div>
            @endif
            <div class="form-group row">
                <div class="col-md-6 form-check form-check-inline p-0 m-0">
                    <input type="checkbox" class="form-check-input ml-3"
                    id="admissionVisitScheduled"
                    name="admissionVisitScheduled"
                    {{(!empty($intake) && $intake['admissionVisitScheduled'] == 1)
                    ? 'checked' : ''}}/>
                    <label class="form-check-label ml-2" for="admissionVisitScheduled">Admission Visit Scheduled</label>
                </div>
            </div>
            <div class="form-group row admissionVisitScheduledFields hide">
                <div class="col-md-6">
                    <label class="w-100" for="intakeNurse">Intake Clinician</label>
                    <select class="form-control" id="intakeNurse" name="intakeNurse">
                        <option value=""> --- Select --- </option>
                        @foreach($clinicalCareManager as $v)
                        <option value="{{$v['value']}}"
                        {{(!empty($intake) && $intake['intakeNurse'] == $v['value'])
                        ? 'selected' : ''}}>
                        {{$v['text']}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="admissionVisitDate">Admission Visit Date</label>
                    <input type="text" class="form-control datepicker" id="admissionVisitDate"
                    name="admissionVisitDate"
                    value="{{ (!empty($intake) && !empty($intake['admissionVisitDate']))
                    ? date('m/d/y', strtotime($intake['admissionVisitDate'])) : '' }}"/>
                </div>
            </div>
            @if(LD::checkFeatureFlag('intake-form-new-change'))
                <div class="form-group row admissionVisitScheduledFields hide">
                    <div class="col-md-6">
                    </div>
                    <div class="col-md-3">
                        <label  class="w-100" for="subject">Start Time</label>
                        <input type="input" required="true" placeholder="Start Time"
                        class="form-control" id="start_time" name="start_time"
                        value="{{$formatted_start_time?? ''}}">
                    </div>
                    <div class="col-md-3">
                        <label  class="w-100" for="subject">End Time</label>
                        <input type="input" required="true"  placeholder="End Time"
                        class="form-control" id="end_time" name="end_time"
                        value="{{$formatted_end_time?? ''}}">
                    </div>
                </div>
                <div class="form-group row admissionVisitScheduledFields hide">
                    <div class="col-md-6">
                        <label  class="w-100" for="subject">Subject</label>
                        <input type="text" class="form-control"
                        id="subject" name="subject"
                        value="{{$intake ? $intake['subject'] : ''}}"/>
                    </div>
                    <div class="col-md-6">
                        <label  class="w-100" for="location">Location</label>
                        <input type="text" class="form-control"
                        id="location" name="location"
                        value="{{$intake ? $intake['location'] : ''}}"/>
                    </div>
                </div>
                <div class="form-group row admissionVisitScheduledFields hide">
                    <div class="col-md-12">
                        <label for="Description">Description</label>
                        <textarea name="description" id="description"
                        class="form-control">{{$intake ? $intake['description'] : ''}}</textarea>
                    </div>
                </div>
            @endif
        </section>
        @if(LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form') && empty($intake))
            <div id="nth-div">
                <div class="form-group row">
                    <div class="col-md-6 form-check form-check-inline p-0 m-0">
                        <input type="checkbox" class="form-check-input ml-3" id="addToNTH"
                        name="addToNTH"
                        {{(!empty($intake) && $intake['addToNTH'] == 1)
                            ? 'checked' : ''}}/>
                        <label class="form-check-label ml-2" for="addToNTH">Add to NTH?</label>
                    </div>
                </div>
                <section class="nth-details hide">
                    <div class="form-group row">
                        @if(!LD::checkFeatureFlag('intake-form-new-change'))
                            @if(!empty($intake))
                                <div class="col-md-6">
                                    <label  class="w-100" for="ic_id">Intake Coordinator</label>
                                    <select class="form-control" name="ic_id" id="ic_id">
                                        <option value="">-- Select One --</option>
                                        @foreach($icList as $ic)
                                            <option {{(!empty($intake) && $intake['ic_id'] == $ic['value'])
                                            ? "selected" : ""}} value="{{$ic['value']}}">{{$ic['text']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @else
                                <div class="col-md-6">
                                    <label  class="w-100" for="ic_id">Intake Coordinator</label>
                                    <select class="form-control" name="ic_id" id="ic_id">
                                        <option value="">-- Select One --</option>
                                        @foreach($icList as $ic)
                                            <option {{isset($patientsDetails->ic_id) && $patientsDetails->ic_id == $ic['value'] ? "selected='selected'" : ""}} value="{{$ic['value']}}">{{$ic['text']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        @endif
                        <div class="col-md-6">
                            <label for="recruiter">Recruiter</label>
                            <select name="recruiter" id="recruiter" class="form-control">
                                <option value="">--- Select ---</option>
                                @foreach($recruiter as $v)
                                <option value="{{$v['value']}}"
                                {{(!empty($intake) && $intake['recruiter'] == $v['value'])
                                ? 'selected' : ''}}>
                                {{$v['text']}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @if(LD::checkFeatureFlag('intake-form-new-change'))
                        @if(!LD::checkFeatureFlag('rearrange-auth-nurse-field-in-intake-form'))
                            <div class="form-group row">
                                <div class="col-md-6">
                                    <label  class="w-100" for="auth_nurse_id">Auth Nurse</label>
                                    <select class="form-control" name="auth_nurse_id" id="auth_nurse_id">
                                        <option value="">-- Select One --</option>
                                        @foreach($nurseList as $ic)
                                            <option {{(!empty($intake) && $intake['auth_nurse_id'] == $ic['value'])
                                            ? 'selected' : ''}}
                                            value="{{$ic['value']}}">{{$ic['text']}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @endif
                    @endif
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label  class="w-100" for="serviceNeeded">Service Needed</label>
                            <select name="serviceNeeded" id="serviceNeeded" class="form-control">
                                <option value="">--- Select ---</option>
                                @foreach(config('constant')['serviceNeededForNTH'] as $v)
                                <option value="{{$v}}"
                                {{(!empty($intake) && $intake['serviceNeeded'] == $v)
                                ? 'selected' : ''}}>
                                {{$v}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="skills">Skills</label>
                            <select name="skills[]" id="skills" multiple class="form-control">
                                @foreach(config('constant')['skillsForNTH'] as $v)
                                <option value="{{$v}}"
                                {{(!empty($intake) &&
                                !empty($intake['skills'])
                                && in_array($v, $skilsArray))
                                ? 'selected' : ''}}>
                                {{$v}}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label  class="w-100" for="clinicianDiscipline">Clinician Discipline</label>
                            <select name="clinicianDiscipline" id="clinicianDiscipline" class="form-control">
                                <option value="">--- Select ---</option>
                                @foreach(config('constant')['clinicianDisciplineForNTH'] as $v)
                                <option value="{{$v}}"
                                {{(!empty($intake) && $intake['clinicianDiscipline'] == $v)
                                ? 'selected' : ''}}>
                                {{$v}}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="scheduleDescription">Schedule Description</label>
                            <textarea name="scheduleDescription" id="scheduleDescription"
                            class="form-control">{{$intake ? $intake['scheduleDescription'] : ''}}</textarea>
                        </div>
                    </div>

                </section>
            </div>
        @endif
        <h4 class="border-bottom text-center p-2">Transfer Clinicians</h4>
        <section class="nurses-details mt-3">
            @if(!empty($intake) && isset($intake['nursedata']) && count($intake['nursedata']) > 0)
            <div class="nurse-form-cnt">
                @foreach($intake['nursedata'] as $key=>$nursedata)
                    <div class="nurse-form-inner mt-5" data-nurse-index="{{ $key }}">
                        <a href="javascript:void(0);" class='nurse-remove-icon'><i class="fa fa-times-circle-o"></i></a>
                        <input type="hidden" name="candidateId{{$key}}" value="{{$nursedata['id']}}">
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="w-100" for="firstLastName">First & Last Name</label>
                                <input type="text" class="form-control clinician-search" id="firstLastName{{$key}}" name="firstLastName{{$key}}"
                                value="{{$nursedata['firstLastName']}}"/>
                            </div>
                            <div class="col-md-6">
                                <label  class="w-100" for="phoneNumber">Phone Number</label>
                                <input type="text" class="form-control phoneNumber" id="phoneNumber{{$key}}" name="phoneNumber{{$key}}"
                                value="{{$nursedata['phoneNumber']}}"/>
                            </div>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="w-100" for="intakeNurse">Email Address</label>
                                <input type="text" class="form-control emailAddress" id="email{{$key}}" name="email{{$key}}"
                                value="{{$nursedata['email']}}"/>
                            </div>
                            <div class="col-md-6 d-flex">
                                <div class="col-md-6 p-0 pr-2">
                                    <label  class="w-100" for="currentRate">Current Rate</label>
                                    <input type="text" class="form-control" id="currentRate{{$key}}" name="currentRate{{$key}}" value="{{$nursedata['Agreed_Pay_Rate']}}"/>
                                </div>
                                <div class="col-md-6 form-check form-check-inline p-0 mt-4">
                                    <input type="checkbox" class="form-check-input" id="paystubsRequest{{$key}}" name="paystubsRequest{{$key}}" />
                                    <label class="form-check-label ml-1" for="paystubsRequest">Paystubs Request</label>
                                </div>
                            </div>
                        </div>
                        @if(LD::checkFeatureFlag('intake-form-new-change'))
                        <div class="form-group row">
                            <div class="col-md-6">
                                <label class="w-100" for="intakeNurse">Discipline</label>
                                <select name="discipline{{$key}}"
                                class="form-control"
                                id="discipline{{$key}}">
                                    <option value="">Select option</option>
                                    @foreach($intakeDiscipline as $discipline)
                                    <option value="{{$discipline}}"
                                    {{($nursedata['discipline'] == $discipline)
                                    ? 'selected' : ''}}>
                                    {{$discipline}}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="w-100" for="intakeNurse">GP%</label>
                                <input type="number" class="form-control" id="gp{{$key}}" name="gp{{$key}}"
                                value="{{$nursedata['gp']}}"/>
                            </div>
                            <div class="col-md-3">
                                <label class="w-100" for="intakeNurse">Approved By</label>
                                <select name="approved_by{{$key}}"
                                class="form-control"
                                id="approved_by{{$key}}">
                                    <option value="">Select option</option>
                                    @foreach($approvedByList as $key=>$user)
                                    <option value="{{$key}}"
                                    {{($nursedata['approved_by'] == $key)
                                    ? 'selected' : ''}}>
                                    {{$user}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        @endif
                        <div class="nurse-error text-danger" style="display: none;"></div>
                    </div>
                @endforeach
            </div>
            @else
            <div class="nurse-form-cnt"></div>
            @endif
            <a href="javascript:void(0);" onclick="loadMoreNurseForm()">Add More</a>
        </section>
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <div id="cover-spin"></div>

                <button class="btn btn-primary lobError" onclick="saveIntake(event, this);">Save</button>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
{{-- Model for old  client --}}

<x-modal>
    <x-slot name="id">newlobref</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="title">New Referral</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <h5 id="newlob"></h5>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-primary" id="newLobRefConfirmationBtn"
        onclick="addNewField()" data-id="">Yes</button>
        <button type="button" class="btn btn-primary" data-dismiss="modal">No</button>
    </x-slot>
</x-modal>

<script>
    @if(LD::checkFeatureFlag('intake-form-new-change'))
    $('#start_time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '00:00:00',
        'maxTime': '23:59:00'
    });
    $('#end_time').timepicker({
        "step": 15,
        'forceRoundTime': true,
        'minTime': '00:00:00',
        'maxTime': '23:59:00'
    });
    @endif
    var isConfirmed = 0;
    var isSave = 0;
    var newLob = 0;
    $('#addIntakeFormModal').on('hide.bs.modal', function(event) {
        (!isConfirmed) ? event.preventDefault():'';
        manageModal = 0;
        if(newLob === 1){
            isConfirmed = 1;
            newLob = 2;
            $('#addIntakeFormModal').modal('hide');
        }
        if(newLob <= 0){
            (!isSave) ? modalConfirm('Do you want to exit without saving? Any unsaved changes will not be saved.','Close without Saving','Go Back',function(res,modelDialog){
                if(res){
                    isConfirmed = 1;
                    nursesCount = 0;
                    modelDialog.modal('hide');
                    $('#addIntakeFormModal').modal('hide');
                }else{
                    modelDialog.modal('hide');
                    $('body').addClass("modal-open");
                }
            }) : '';
        }
    });

    $('.datepicker').datepicker();
    $('.datepicker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });
    function saveIntake(e, button){
        e.preventDefault();
        if($('#addIntakeForm').valid()){
            $('#cover-spin').show(0);
            button.disabled = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#addIntakeForm').serializeObject();
            data['_token'] = _token;
            data['nursesCount'] = nursesCount;
            $.ajax({
                url: "{{route('intake.save')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    $('#cover-spin').hide(1);
                    if(data.status){
                        isConfirmed = 1;
                        isSave = 1;
                        $('#addIntakeFormModal').modal('hide');
                        nursesCount = 0;
                        if(typeof data.aoc_nthaccounts_id != 'undefined' && data.aoc_nthaccounts_id != ''){
                            @if(LD::checkFeatureFlag('hide-add-nth-part-from-edit-intake-form'))
                                @if(empty($intake))
                                    openBuildScheduleForm(data.aoc_nthaccounts_id);
                                @endif
                            @else
                                openBuildScheduleForm(data.aoc_nthaccounts_id);
                            @endif
                        }
                        if(typeof data.aoc_patient_id != 'undefined' && data.aoc_patient_id != ''){
                            getIntakeData(data.aoc_patient_id, data.intake_id);
                        }
                        @if(LD::checkFeatureFlag('client-profile-upcoming-recruitment-clinician-clickable'))
                            reloadClientHistory(data.aoc_patient_id, 'clienthistory', '.client-activity-log');
                        @else
                            reloadClientHistory(data.aoc_patient_id, 'clienthistory', '.activity-log');
                        @endif
                    }else{
                        console.log('Error:' + data.msg);
                    }
                }
            });
        }
    }
    $(document).on('click','.nurse-remove-icon',function(){
        $(this).parent().remove();
        nursesCount--;
    });
    var nursesCount = 0;
    @if(!empty($intake))
        nursesCount = <?php echo isset($intake['nursedata']) ? count($intake['nursedata']) + 1 : 0 ?>;
    @else
        nursesCount = 0;
    @endif
    $('#addIntakeForm #skills').multiselect({
        includeSelectAllOption: true,
        onChange: function(option, checked, select) {
            isSpecialize = false;
           $('#addIntakeForm #skills option:selected').each(function() {
                if($(this).val().toLowerCase().indexOf('trach') != -1){
                    isSpecialize = true;
              }
           });
       }
   });
   @if(LD::checkFeatureFlag('prohibite-exiting-nurse-to-added-from-intake-from'))
       $(".clinician-search").each(function() {
            initializeAutocomplete(this);
        });
        function loadMoreNurseForm() {
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('intake.loadMoreNurseForm')}}",
                type: 'get',
                dataType: "html",
                data: { nursesCount: nursesCount, _token: _token },
                success: function(data) {
                    $('.nurse-form-cnt').append(data);
                    initializeAutocomplete("#firstLastName" + nursesCount);
                    $('#gp' + nursesCount).rules("add",
                    {
                        number: true
                    });
                    nursesCount++;
                }
            });
        }
        function initializeAutocomplete(inputField, errorContainer) {
            $(inputField).autocomplete({
                source: function (request, response) {
                    $.ajax({
                        url: "{{ route('clinician.search') }}",
                        data: {
                            query: request.term
                        },
                        success: function (data) {
                            response($.map(data, function (item) {
                                return {
                                    label: item.fullName + " (ID: " + item.ClinicianID + ") - " + item.branch + " | " + item.phone + " | " + item.email,
                                    value: "", // Prevent autofill
                                    phone: item.phone,
                                    email: item.email
                                };
                            }));
                        }
                    });
                },
                select: function (event, ui) {
                    event.preventDefault(); // Prevent selection
                }
            });
        }
        function checkUniqueFields(nurseSection) {
            let phoneInput = nurseSection.find("input[name^='phoneNumber']");
            let emailInput = nurseSection.find("input[name^='email']");
            let errorContainer = nurseSection.find(".nurse-error");
            let phone = phoneInput.val();
            let email = emailInput.val();

            $.ajax({
                url: "{{ route('clinician.validate') }}",
                type: 'POST',
                data: {
                    phone: phone,
                    email: email,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function (response) {
                    console.log(response.valid);
                    if (response.valid === true) {
                        errorContainer.text("Clinician has an active profile in KanTime. Please remove from form as they do not need to be scheduled for an IPEA.").show();
                        $(".lobError").prop("disabled", true);
                    } else {
                        errorContainer.text("").hide();
                        checkAllErrors();
                    }
                }
            });
        }
        function checkAllErrors() {
            if ($(".nurse-error:visible").length === 0) {
                $(".lobError").prop("disabled", false);
            }
        }
        $(document).on("blur", "input[name^='phoneNumber'], input[name^='email']", function () {
            let nurseSection = $(this).closest(".nurse-form-inner");
            checkUniqueFields(nurseSection);
        });
        $(document).ready(function () {
            $(".nurse-form-inner").each(function () {
                initializeAutocomplete($(this).find("input[name^='firstLastName']"), $(this).find(".nurse-error"));
            });
        });
   @else
        function loadMoreNurseForm(){
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('intake.loadMoreNurseForm')}}",
                type: 'get',
                dateType:"html",
                data: {
                    nursesCount:nursesCount,
                    _token: _token
                },
                success: function(data) {
                    $('.nurse-form-cnt').append(data);
                    $('#gp' + nursesCount).rules("add",
                    {
                        number: true
                    });
                    nursesCount++;
                }
            });
        }
    @endif
    @if(!empty($intake) && !isset($intake['nursedata']))
        $(document).ready(function(){
            loadMoreNurseForm();
        });
   @elseif(empty($intake))
        $(document).ready(function(){
            loadMoreNurseForm();
        });
    @endif

    $("#addToNTH").change(function() {
        if(this.checked) {
            $('.nth-details').removeClass('hide');
        }else{
            $('.nth-details').addClass('hide');
        }
    });
    $("#admissionVisitScheduled").change(function() {
        if(this.checked) {
            $('.admissionVisitScheduledFields').removeClass('hide');
        }else{
            $('.admissionVisitScheduledFields').addClass('hide');
        }
    });
    $("#referralType").change(function() {
        if($(this).val() == 'New - Hospital Discharge' || $(this).val() == 'Transfer - Hospital Discharge') {
            $('.referralTypeFields').removeClass('hide');
        }else{
            $('.referralTypeFields').addClass('hide');
        }
    });

    $("#addIntakeForm #respiteTerminalPlayer").autocomplete({
        source: "{{ route('intake.getRespiteTerminalPayer') }}",
        classes: {
            "ui-autocomplete": "respiteTerminalPlayer"
        },
        minLength: 2,
        select: function(event, ui) {}
    });
    $("#addIntakeForm #terminalPlayer").autocomplete({
        source: "{{ route('intake.getTerminalPayer') }}",
        classes: {
            "ui-autocomplete": "terminalPlayer"
        },
        minLength: 2,
        select: function(event, ui) {}
    });

    $("#addIntakeForm").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "clientName":{
                required: true,
                alphabetsDashSingleQuote: false
            },
            "clientId":{
                required: true,
                alphabetsNumbersDash: true
            },
            "address1":{
                required: true,
            },
            "city":{
                required: true,
                alphabetsDash:true
            },
            "state":{
                required: true,
                textRule:true
            },
            "zipCode":{
                required: true,
                numberRule:true
            },
            "aoc_facility_id":{
                required: true
            },
            "anticipatedHours":{
                floatRule:true
            },
            @if(LD::checkFeatureFlag('intake-form-new-change'))
            "clientFirstName":{
                required:true
            },
            "clientLastName":{
                required:true
            },
            "ic_id" : {
                required:true
            },
            "lob" : {
                required:true
            },
            @endif
        },
        //errorPlacement: function(error, element) {}
    });

    //Custom rules for validation
    $.validator.addMethod("alphabetsDashSingleQuote", function(value, element) {
        var regex = /^[A-Za-z- '.]+$/;
        return regex.test(value);
    }, "This field will allow the following [alphabets, -, ', .] ");
    $.validator.addMethod("alphabetsNumbersDash", function(value, element) {
        var regex = /^[0-9A-Za-z- ]+$/;
        return regex.test(value);
    }, "This field will allow the following [alphabets, Numbers, -] ");
    $.validator.addMethod("alphabetsDash", function(value, element) {
        var regex = /^[A-Za-z- ]+$/;
        return regex.test(value);
    }, "This field will allow the following [alphabets, - ] ");
    $.validator.addMethod("textRule", function(value, element) {
        var regex = /^[A-Za-z ]+$/;
        return regex.test(value);
    }, "This field will only allow the alphabets ");
    $.validator.addMethod("numberRule", function(value, element) {
        var regex = /^[0-9]+$/;
        return regex.test(value);
    }, "This field will only allow numbers ");
    $.validator.addMethod("floatRule", function(value, element) {
        var regex = /^[0-9.]+$/;
        return (regex.test(value) && value <= 168) || value == '';
    }, "This field will only allow numbers(with decimals) and maximum of 168.");
    $.validator.addMethod("numberTextRule", function(value, element) {
        var regex = /^[0-9A-Za-z]+$/;
        return regex.test(value) || value == '';
    }, "This field will only allow numbers and alphabets ");

    @if(LD::checkFeatureFlag('intake-form-new-change'))
    var isSelect = 0;
    $("#addIntakeForm #clientName").autocomplete({
        source: "{{ route('getScheduleClient') }}?from=addIntake",
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            if(ui.item.id !== undefined){
                isSelect = 1;
            }
            $('#addIntakeForm #clientId').val(ui.item.PatientAccount);
            $('#addIntakeForm #clientFirstName').val(ui.item.Patient_FirstName);
            $('#addIntakeForm #clientLastName').val(ui.item.Patient_LastName);
            $('#addIntakeForm #address1').val(ui.item.Patient_Address1);
            $('#addIntakeForm #address2').val(ui.item.Patient_Address2);
            $('#addIntakeForm #city').val(ui.item.Patient_City);
            $('#addIntakeForm #state').val(ui.item.Patient_State);
            $('#addIntakeForm #zipCode').val(ui.item.Patient_Zip);
            $('#addIntakeForm #clientPId').val(ui.item.id);
            $('#addIntakeForm #terminalPlayer').val(ui.item.payer);
            $('#addIntakeForm #respiteTerminalPlayer').val(ui.item.respiteTerminalPlayer);
            $('#addIntakeForm #aoc_facility_id').val(ui.item.aoc_facility_id);
            $('#lob').val(ui.item.LOBID).change();
			if (ui.item.ic_id != '' || ui.item.ic_id != 0) {
				$('#addIntakeForm #ic_id').val(ui.item.ic_id);
			}
            let colFlage = false;
            $('#col-error').css('display', 'none');
            $('.lobError').prop('disabled', false);
            @if(LD::checkFeatureFlag('not-allowed-to-select-col-client-in-intake-form'))
                let clientPId = $('#addIntakeForm #clientPId').val();
                $.ajax({
                    async: false,
                    url: "{{ route('check.patient.lob') }}",
                    method: "POST",
                    data: {
                        clientPId: clientPId,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (!response.success) {
                            $('#col-error').css('display', 'block');
                            $('.lobError').prop('disabled', true);
                            colFlage = true;
                        }
                    },
            });
            @endif
            if (colFlage)
                return false;
            if (ui.item.CF_Specialized == 'Yes') {
            $('#specializedClient').prop('checked', true);
            } else {
                $('#specializedClient').prop('checked', false);
            }
            if (ui.item.PatientStatus != 'Non-Admit' && ui.item.PatientStatus != 'Discharged'
                && ui.item.PatientStatus != 'InActive'
            ) {
                openrefmodal(ui.item.Patient_FirstName, ui.item.Patient_LastName);
                @if(LD::checkFeatureFlag('prevent-client-from-adding-duplicate-lob-in-intake'))
                    setOldLobValue(ui.item.LOBID);
                @endif
            }
            $('.client_disable').prop('readonly', true);
            @if(LD::checkFeatureFlag('allow-edit-address-field-in-intake-form'))
                if (
                    !ui.item.Patient_Address1?.trim() ||
                    !ui.item.Patient_Address2?.trim() ||
                    !ui.item.Patient_City?.trim() ||
                    !ui.item.Patient_State?.trim() ||
                    !ui.item.Patient_Zip?.trim()
                ) {
                    $("#addIntakeForm #address1").prop('readonly', false);
                    $("#addIntakeForm #address2").prop('readonly', false);
                    $("#addIntakeForm #city").prop('readonly', false);
                    $("#addIntakeForm #state").prop('readonly', false);
                    $("#addIntakeForm #zipCode").prop('readonly', false);
                }
            @endif

        }
    });
    $('#clientName').on('keydown', function(event) {
        if (event.keyCode === 8 && isSelect === 1) {
            $('#addIntakeForm #clientId').val('');
            $('#addIntakeForm #clientFirstName').val('');
            $('#addIntakeForm #clientLastName').val('');
            $('#addIntakeForm #address1').val('');
            $('#addIntakeForm #address2').val('');
            $('#addIntakeForm #city').val('');
            $('#addIntakeForm #state').val('');
            $('#addIntakeForm #zipCode').val('');
            $('#addIntakeForm #clientPId').val('');
            $('#addIntakeForm #terminalPlayer').val('');
            $('#addIntakeForm #respiteTerminalPlayer').val('');
            $('#addIntakeForm #aoc_facility_id').val('');
            $('#lob').val('').change();
            $('#addIntakeForm #ic_id').val('');
            $('#specializedClient').prop('checked', false);
            $('.newLob').addClass('hide');
            removeNewFieldVelidation();
            $('.client_disable').prop('readonly', false);
        }
    });
        @if(LD::checkFeatureFlag('make-clientID-filed-search-on-addintake-form'))
            $("#addIntakeForm #clientId").on("blur", function() {
                const clientId = $(this).val().trim();
                const clientIdField = $(this);
                if (clientIdField.prop("readonly")) {
                    return;
                }
                if (clientId !== "") {
                    $.ajax({
                        url: "{{ route('intake.checkPatientAccount') }}",
                        method: "GET",
                        data: {
                            clientId: clientId,
                        },
                        success: function(response) {
                            if (response.PatientAccount !== undefined) {
                                $('#addIntakeForm #clientFirstName').val(response.Patient_FirstName);
                                $('#addIntakeForm #clientLastName').val(response.Patient_LastName);
                                $('#addIntakeForm #address1').val(response.Patient_Address1);
                                $('#addIntakeForm #address2').val(response.Patient_Address2);
                                $('#addIntakeForm #city').val(response.Patient_City);
                                $('#addIntakeForm #state').val(response.Patient_State);
                                $('#addIntakeForm #zipCode').val(response.Patient_Zip);
                                $('#addIntakeForm #clientPId').val(response.id);
                                $('#addIntakeForm #terminalPlayer').val(response.payer);
                                $('#addIntakeForm #respiteTerminalPlayer').val(response.respiteTerminalPlayer);
                                $('#addIntakeForm #aoc_facility_id').val(response.aoc_facility_id);
                                $('#addIntakeForm #clientName').val(response.value);
                                $('#lob').val(response.LOBID).change();
                                let colFlage = false;
                                $('#col-error').css('display', 'none');
                                $('.lobError').prop('disabled', false);
                                @if(LD::checkFeatureFlag('not-allowed-to-select-col-client-in-intake-form'))
                                    let clientPId = $('#addIntakeForm #clientPId').val();
                                    $.ajax({
                                        async: false,
                                        url: "{{ route('check.patient.lob') }}",
                                        method: "POST",
                                        data: {
                                            clientPId: clientPId,
                                            _token: "{{ csrf_token() }}"
                                        },
                                        success: function(response) {
                                            if (!response.success) {
                                                $('#col-error').css('display', 'block');
                                                $('.lobError').prop('disabled', true);
                                                colFlage = true;
                                            }
                                        },
                                });
                                @endif
                                if (colFlage)
                                    return false;
                                if (response.ic_id && response.ic_id != 0) {
                                    $('#addIntakeForm #ic_id').val(response.ic_id);
                                }

                                if (response.CF_Specialized === 'Yes') {
                                    $('#specializedClient').prop('checked', true);
                                } else {
                                    $('#specializedClient').prop('checked', false);
                                }

                                if (response.PatientStatus !== 'Non-Admit' && response.PatientStatus !== 'Discharged'
                                && response.PatientStatus !== 'InActive') {
                                    openrefmodal(response.Patient_FirstName, response.Patient_LastName);
                                        @if(LD::checkFeatureFlag('prevent-client-from-adding-duplicate-lob-in-intake'))
                                            setOldLobValue(response.LOBID);
                                        @endif
                                }

                                $('.client_disable').prop('readonly', true);
                                @if(LD::checkFeatureFlag('allow-edit-address-field-in-intake-form'))
                                    if (
                                        !response.Patient_Address1?.trim() ||
                                        !response.Patient_Address2?.trim() ||
                                        !response.Patient_City?.trim() ||
                                        !response.Patient_State?.trim() ||
                                        !response.Patient_Zip?.trim()
                                    ) {
                                        $("#addIntakeForm #address1").prop('readonly', false);
                                        $("#addIntakeForm #address2").prop('readonly', false);
                                        $("#addIntakeForm #city").prop('readonly', false);
                                        $("#addIntakeForm #state").prop('readonly', false);
                                        $("#addIntakeForm #zipCode").prop('readonly', false);
                                    }
                                @endif
                            }
                        },
                    });
                }
            });
        @endif
    @endif

    @if(LD::checkFeatureFlag('intake-form-new-change'))
    $("#admissionVisitScheduled").on("change", function() {
        if ($(this).is(":checked")) {
            $( "#intakeNurse" ).rules( "add", {
                required: true,
            });
            $( "#admissionVisitDate" ).rules( "add", {
                required: true,
            });
            $( "#description" ).rules( "add", {
                required: true,
            });
            $( "#location" ).rules( "add", {
                required: true,
            });
            $( "#subject" ).rules( "add", {
                required: true,
            });
            $( "#end_time" ).rules( "add", {
                required: true,
            });
            $( "#start_time" ).rules( "add", {
                required: true,
            });
        } else {
            $( "#intakeNurse" ).rules( "remove" );
            $( "#admissionVisitDate" ).rules( "remove" );
            $( "#description" ).rules( "remove" );
            $( "#location" ).rules( "remove" );
            $( "#subject" ).rules( "remove" );
            $( "#end_time" ).rules( "remove" );
            $( "#start_time" ).rules( "remove" );
        }
    });
    @endif

    @if(!empty($intake))
        var reftype = '<?php echo $intake['referralType']?>';
        var admissionVisitScheduled = '<?php echo $intake['admissionVisitScheduled'] ?>';
        var add_Nth = '<?php echo $intake['addToNTH'] ?>'
        if(reftype == 'New - Hospital Discharge' || reftype == 'Transfer - Hospital Discharge') {
            $('.referralTypeFields').removeClass('hide');
        }else{
            $('.referralTypeFields').addClass('hide');
        }

        if(admissionVisitScheduled == 1) {
            $('.admissionVisitScheduledFields').removeClass('hide');
        }else{
            $('.admissionVisitScheduledFields').addClass('hide');
        }

        if(add_Nth == 1) {
            $('.nth-details').removeClass('hide');
        }else{
            $('.nth-details').addClass('hide');
        }
    @endif

    function openrefmodal(fName, lName){
        let name = fName+' '+lName;
        let text = name+' has an existing profile in KanTime. Is this referral for a new LOB? If this is not a new referral, please locate the existing client in the Intake Dashboard to make changes.';
        document.getElementById('newlob').innerHTML = text;
        $('#newlobref').modal('show');

        @if(LD::checkFeatureFlag('prevent-client-from-adding-duplicate-lob-in-intake'))
            $('#lob-error').css('display', 'block');
            $('.lobError').prop('disabled', true);
            handleLobChange();
        @endif
    }
    $(document).ready(function() {
        $('#newlobref').on('hide.bs.modal', function(event) {
            if(manageModal < 1){
                newLob = 1;
                $('#addIntakeFormModal').modal('hide');
                addIntakeForm();
            }
        });
    });
    var manageModal = 0;
    function addNewField(){
        manageModal = 1;
        $('#newlobref').modal('hide');
        $('.newLob').removeClass('hide');
        addNewFieldVelidation();
    }
    function addNewFieldVelidation(){
        $( "#referral_date" ).rules( "add", {
            required: true,
        });
        @if(!LD::checkFeatureFlag('make-referral-field-not-required-in-add-intake-form'))
            $( "#referral_source" ).rules( "add", {
                required: true,
            });
            $( "#referring_physician" ).rules( "add", {
                required: true,
            });
        @endif
        $( "#business_development_rep" ).rules( "add", {
            required: true,
        });
    }
    function removeNewFieldVelidation(){
        $( "#referral_date" ).rules( "remove" );
        @if(!LD::checkFeatureFlag('make-referral-field-not-required-in-add-intake-form'))
            $( "#referral_source" ).rules( "remove" );
            $( "#referring_physician" ).rules( "remove" );
        @endif
        $( "#business_development_rep" ).rules( "remove" );
    }

    $("#addIntakeForm #referral_source").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "{{ route('getreferralsource') }}",
                data: { term: request.term },
                success: function(data) {
                    response(data);
                }
            });
        },
        classes: {
            "ui-autocomplete": "referral_source"
        },
        minLength: 3,
        select: function(event, ui) {
            $("#addIntakeForm #referral_source").val(ui.item.value);
            return false;
        }
    }).on("blur", function() {
        const inputValue = $(this).val();
        const $input = $(this);
        $.ajax({
            url: "{{ route('getreferralsource') }}",
            data: { term: inputValue },
            success: function(data) {
                const isValid = data.some(item => item.value === inputValue);
                if (!isValid) {
                    $input.val("");
                }
            }
        });
    });

    @if(isset($newRef) && $newRef == true)
        addNewField();
    @endif

    $("#addIntakeForm #referring_physician").autocomplete({
        source: function(request, response) {
            $.ajax({
                url: "{{ route('getreferralphysician') }}",
                data: { term: request.term },
                success: function(data) {
                    response(data);
                }
            });
        },
        classes: {
            "ui-autocomplete": "referring_physician"
        },
        minLength: 3,
        select: function(event, ui) {
            $("#addIntakeForm #referring_physician").val(ui.item.value);
            return false;
        }
    }).on("blur", function() {
        const inputValue = $(this).val();
        const $input = $(this);

        $.ajax({
            url: "{{ route('getreferralphysician') }}",
            data: { term: inputValue },
            success: function(data) {
                const isValid = data.some(item => item.value === inputValue);
                if (!isValid) {
                    $input.val("");
                }
            }
        });
    });

    $('#referring_physician').on('keydown', function(event) {
        if (event.keyCode === 8) {
            $('#addIntakeForm #referring_physician').val('');

        }
    });
    $('#referral_source').on('keydown', function(event) {
        if (event.keyCode === 8) {
            $('#addIntakeForm #referral_source').val('');

        }
    });
    $(document).ready(function() {
        @if(!empty($intake))
            $('.client_disable').prop('readonly', true);
                @if(LD::checkFeatureFlag('allow-edit-address-field-in-intake-form'))
                    @if(
                        empty(trim($intake['address1'] ?? '')) ||
                        empty(trim($intake['address2'] ?? '')) ||
                        empty(trim($intake['city'] ?? '')) ||
                        empty(trim($intake['state'] ?? '')) ||
                        empty(trim($intake['zipCode'] ?? ''))
                    )
                        $("#addIntakeForm #address1").prop('readonly', false);
                        $("#addIntakeForm #address2").prop('readonly', false);
                        $("#addIntakeForm #city").prop('readonly', false);
                        $("#addIntakeForm #state").prop('readonly', false);
                        $("#addIntakeForm #zipCode").prop('readonly', false);
                    @endif
                @endif
        @else
            $('.client_disable').prop('readonly', false);
        @endif
    });
    @if(LD::checkFeatureFlag('prevent-client-from-adding-duplicate-lob-in-intake'))
        var oldLobValue = '';
        function setOldLobValue(lob) {
            oldLobValue = lob;
        }

        function handleLobChange() {
            const $lobField = $('#lob');
            const $lobErrorMessage = $('#lob-error');
            const $saveButton = $('.lobError');

            $lobField.on('change', function () {
                const newLob = $lobField.val();
                if (newLob != oldLobValue) {
                    $lobErrorMessage.hide();
                    $saveButton.prop('disabled', false);
                } else {
                    $lobErrorMessage.show();
                    $saveButton.prop('disabled', true);
                }
            });
        }

    @endif
</script>
