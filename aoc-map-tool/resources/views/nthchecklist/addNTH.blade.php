<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\CustomFields;

    $recruiter = H::getHelper('ConstantHelper')->getRecruitersList(false);
    $selectedSkills = [];
    if(isset($patientsDetails->CF_Skills)){
        $selectedSkills = explode(',',$patientsDetails->CF_Skills);
    }

    $selectedclinicianDiscipline = [];
    if(isset($patientsDetails->CF_ClinicianDiscipline)){
        $selectedclinicianDiscipline = explode(',',$patientsDetails->CF_ClinicianDiscipline);
    }
    $facilities = H::getHelper("ConstantHelper")->getFacilities();
    $cccList = H::getHelper("ConstantHelper")->getClientCareCoordinator();
    $icList = H::getHelper("ConstantHelper")->getIntakeCoordinator();
    if(LD::checkFeatureFlag('add-pay-range-fields-nth-form')){
        if(H::canPermissions('nthFormFieldsForNthCard')){
            if(!empty($needToHireAccounts)){
                $rnParts = explode(' - ', str_replace('$', '', $needToHireAccounts->pay_range_rn));
                $lvnParts = explode(' - ', str_replace('$', '', $needToHireAccounts->pay_range_lvn));
            }
        }
    }
    $customFieldsList = CustomFields::where('is_deleted', 0)
                                        ->where('entity', 'client')
                                        ->whereIn('field_name', [
                                            'Services Needed',
                                            'Meeting Nurses',
                                            'Lifting Required',
                                            'Skills',
                                            'Clinician Discipline'
                                        ])
                                        ->orderBy('sort')
                                        ->get()
                                        ->toArray();
?>
<x-modal>
    <x-slot name="id">addNTHForm</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">{{$mode=='edit' ? "Edit NTH" : "Add NTH"}}</x-slot>
    <x-slot name="class">add-nth-form</x-slot>
    <x-slot name="body">
    <form id="addNTHBuildScheduleForm">
    <input type="hidden" class="form-control" id="scheduleSpecialized" name="scheduleSpecialized" value="{{ !empty($names['scheduleSpecialized']) ?$names['scheduleSpecialized']:0 }}" />
            @if($mode == 'edit')
                <input type="hidden" class="form-control" id="needToHireAccountsId" name="needToHireAccountsId" value="{{isset($needToHireAccounts->id) ? $needToHireAccounts->id : ''}}" />
                <div class="form-group row">
                    <div class="col-md-6 d-flex">
                        <div class="col-md-4 pl-1 pr-1">
                            <label class="w-100" for="IsActive">Active</label>
                            <div class="custom-control custom-switch custom-switch-lg">
                            <input  readonly="true" class="custom-control-input  pe-none" type="checkbox" id="IsActive" name="IsActive" {{isset($needToHireAccounts->IsActive) && $needToHireAccounts->IsActive==1 ? "checked='checked'" : ""}}/>
                                <label class="custom-control-label  pe-none" for="IsActive"></label>
                            </div>
                        </div>
                        @if (LD::checkFeatureFlag('specialized-vent-included-in-skills-nth-form'))
                        <div class="col-md-4 pl-1 pr-1">
                            <label class="w-100" for="specializedClient">Specialized Client?</label>
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input readonly="true" class="custom-control-input pe-none" type="checkbox" id="specializedClient" name="specializedClient" {{ ((isset($patientsDetails->CF_Skills) && H::getHelper('NTHChecklist')->isSpecialized($patientsDetails->CF_Skills,$names['scheduleSpecialized']))) ? "checked='checked'" : ""}} />
                                <label class="custom-control-label pe-none" for="specializedClient"></label>
                            </div>
                        </div>
                        @else
                        <div class="col-md-4 pl-1 pr-1">
                            <label class="w-100" for="specializedClient">Specialized Client?</label>
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input readonly="true" class="custom-control-input pe-none" type="checkbox" id="specializedClient" name="specializedClient" {{isset($needToHireAccounts->skills) && H::getHelper('NTHChecklist')->isSpecialized($needToHireAccounts->skills) ? "checked='checked'" : ""}} />
                                <label class="custom-control-label pe-none" for="specializedClient"></label>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-4 pl-1 pr-1">
                            <label class="w-100" for="IsPriority">Priority</label>
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input class="custom-control-input" type="checkbox" id="IsPriority" name="IsPriority" {{isset($needToHireAccounts->IsPriority) && $needToHireAccounts->IsPriority==1 ? "checked='checked'" : ""}}/>
                                <label class="custom-control-label" for="IsPriority"></label>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="form-group row">
                    <div class="col-md-6 d-flex">
                        @if (LD::checkFeatureFlag('specialized-vent-included-in-skills-nth-form'))
                        <div class="col-md-4 pl-1 pr-1">
                            <label class="w-100" for="specializedClient">Specialized Client?</label>
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input readonly="true" class="custom-control-input pe-none" type="checkbox" id="specializedClient" name="specializedClient" />
                                <label class="custom-control-label pe-none" for="specializedClient"></label>
                            </div>
                        </div>
                        @else
                        <div class="col-md-6">
                            <label class="w-100" for="specializedClient">Specialized Client?</label>
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input readonly="true" class="custom-control-input pe-none" type="checkbox" id="specializedClient" name="specializedClient" {{isset($needToHireAccounts->skills) && H::getHelper('NTHChecklist')->isSpecialized($needToHireAccounts->skills) ? "checked='checked'" : ""}} />
                                <label class="custom-control-label pe-none" for="specializedClient"></label>
                            </div>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <label class="w-100" for="IsPriority">Priority</label>
                            <div class="custom-control custom-switch custom-switch-lg">
                                <input class="custom-control-input" type="checkbox" id="IsPriority" name="IsPriority" {{isset($needToHireAccounts->IsPriority) && $needToHireAccounts->IsPriority==1 ? "checked='checked'" : ""}}/>
                                <label class="custom-control-label" for="IsPriority"></label>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        <div class="form-group row">
            <div class="col-md-6">
                <label  class="w-100">Client Name</label>
                <input type="hidden" class="form-control client-data" id="aoc_patient_id" name="aoc_patient_id" value="{{isset($patientsDetails->id) ? $patientsDetails->id : ''}}" />
                <?php if(LD::checkFeatureFlag('add-client-form-allow-discharged-as-new-client')) { ?>
                    <input type="hidden" class="form-control client-data" id="is_discharged_client" name="is_discharged_client" value="" />
                <?php } ?>
                <input type="text" class="form-control" id="clientName" name="clientName" value="{{isset($names['clientName']) ? $names['clientName'] : ''}}" {{$mode == 'edit' ? "readonly='true'" : ""}} />
            </div>
            <?php if(LD::checkFeatureFlag('add-medicaid-number-on-add-nth-form')): ?>
                <div class="row col-md-6 pr-0">
                    <div class="col-md-6">
                        <label for="">Client ID</label>
                        <input type="text" class="form-control client-data" id="clientId" name="clientId" value="{{isset($patientsDetails->PatientAccount) ? $patientsDetails->PatientAccount : ''}}" />
                    </div>
                    <div class="col-md-6  pr-0" {{ (isset($patientsDetails->not_in_kantime) && $patientsDetails->not_in_kantime == 0) ?
                                            "style=display:none;" : ""}}>
                        <label  class="w-100">Medicaid Number</label>                
                        <input
                            type="text"
                            class="form-control"
                            id="MedicaidNumber"
                            name="MedicaidNumber"
                            value="{{isset($patientsDetails->MedicaidNumber) ? $patientsDetails->MedicaidNumber : ""}}"
                            {{ (isset($patientsDetails->not_in_kantime) && $patientsDetails->not_in_kantime == 0) ?
                                            "disabled=disabled" : ""}}/>
                    </div>
                </div>
            <?php else: ?>
                <div class="col-md-6">
                    <label for="">Client ID</label>
                    <input type="text" readonly tabindex="-1" class="form-control client-data" id="clientId" name="clientId" value="{{isset($patientsDetails->PatientAccount) ? $patientsDetails->PatientAccount : ''}}" />
                </div>
            <?php endif; ?>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label for="branch">Branch</label>
                <select class="form-control client-data" name="aoc_facility_id" id="aoc_facility_id">
                    <option value="">-- Select One --</option>
                    @foreach($facilities as $facility)
                        <option {{isset($patientsDetails->aoc_facility_id) && $patientsDetails->aoc_facility_id == $facility->id ? "selected='selected'" : ""}} value="{{$facility->id}}">{{$facility->FacilityName}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="nthStatus">NTH Status</label>
                <select name="nthStatus" id="nthStatus" class="form-control">
                    <option value="">--- Select ---</option>
                    <option {{ isset($needToHireAccounts->Status) && $needToHireAccounts->Status == 'New' ? "selected='selected'" : '' }} value="New">New</option>
                    <option {{ isset($needToHireAccounts->Status) && $needToHireAccounts->Status == 'Current' ? "selected='selected'" : '' }} value="Current">Current</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label  class="w-100" for="ccc">Client Care Coordinator</label>
                <select class="form-control" name="ccc_id" id="ccc_id">
                    <option value="">-- Select One --</option>
                    @foreach($cccList as $ccc)
                        <option {{isset($patientsDetails->ccc_id) && $patientsDetails->ccc_id == $ccc['value'] ? "selected='selected'" : ""}} value="{{$ccc['value']}}">{{$ccc['text']}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="recruiter">Recruiter</label>
                <select name="recruiter" id="recruiter" class="form-control">
                    <option value="">--- Select ---</option>
                    @foreach($recruiter as $v)
                    <option {{isset($needToHireAccounts->Recruiter) && $needToHireAccounts->Recruiter == $v['value'] ? "selected='selected'" :""}} value="{{$v['value']}}">{{$v['text']}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label  class="w-100" for="serviceNeeded">Service Needed</label>
                <select name="serviceNeeded" id="serviceNeeded" class="form-control {{ ($customFieldsList[
                                                    array_search('Services Needed', array_column($customFieldsList, 'field_name'))
                                                ]['is_required'] == 1 ? 'required' : '') }}">
                    <option value="">--- Select ---</option>
                    @php
                        $serviceNeededForNTH = preg_split('/\r\n|[\r\n]/', $customFieldsList[
                                                    array_search('Services Needed', array_column($customFieldsList, 'field_name'))
                                                ]['options']);
                    @endphp
                    @foreach($serviceNeededForNTH as $v)
                    <option {{isset($patientsDetails->CF_ServicesNeeded) && $patientsDetails->CF_ServicesNeeded==$v ? "selected='selected'" : "" }} value="{{$v}}">{{$v}}</option>
                    @endforeach
                </select>
            </div>
                <div class="col-md-6">
                    <label  class="w-100" for="meeting_nurses">Meeting Nurses</label>
                    <select name="meeting_nurses" id="meeting_nurses" class="form-control {{ ($customFieldsList[
                                                    array_search('Meeting Nurses', array_column($customFieldsList, 'field_name'))
                                                ]['is_required'] == 1 ? 'required' : '') }}">
                        <option value="">--- Select ---</option>
                        @php
                            $meetingNurses = preg_split('/\r\n|[\r\n]/', $customFieldsList[
                                                    array_search('Meeting Nurses', array_column($customFieldsList, 'field_name'))
                                                ]['options']);
                        @endphp
                        @foreach($meetingNurses as $v)
                        <option {{isset($patientsDetails->CF_MeetingNurses) && $patientsDetails->CF_MeetingNurses == $v ? "selected='selected'" : "" }} value="{{$v}}">{{$v}}</option>
                        @endforeach
                    </select>
                </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label  class="w-100" for="liftingRequirements">Lifting Requirements</label>
                <select name="liftingRequirements" id="liftingRequirements" class="form-control {{ ($customFieldsList[
                                                    array_search('Lifting Required', array_column($customFieldsList, 'field_name'))
                                                ]['is_required'] == 1 ? 'required' : '') }}">
                    <option value="">--- Select ---</option>
                        @php
                            $liftingRequirements = preg_split('/\r\n|[\r\n]/', $customFieldsList[
                                                    array_search('Lifting Required', array_column($customFieldsList, 'field_name'))
                                                ]['options']);
                        @endphp
                    @foreach($liftingRequirements as $v)
                    <option {{isset($patientsDetails->CF_LiftingRequired) && $patientsDetails->CF_LiftingRequired == $v ? "selected='selected'" : "" }} value="{{$v}}">{{$v}}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-6">
                <label for="skills">Skills</label>
                <select name="skills[]" id="skills" multiple class="form-control {{ ($customFieldsList[
                                                    array_search('Skills', array_column($customFieldsList, 'field_name'))
                                                ]['is_required'] == 1 ? 'required' : '') }}">
                        @php
                            $skillsForNTH = preg_split('/\r\n|[\r\n]/', $customFieldsList[
                                                    array_search('Skills', array_column($customFieldsList, 'field_name'))
                                                ]['options']);
                        @endphp
                    @foreach($skillsForNTH as $v)
                    <option {{in_array($v,$selectedSkills) ? "selected='selected'" : "" }} value="{{$v}}">{{$v}}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                <label  class="w-100" for="clinicianDiscipline">Clinician Discipline</label>
                <select name="clinicianDiscipline[]" multiple id="clinicianDiscipline" class="form-control {{ ($customFieldsList[
                                                    array_search('Clinician Discipline', array_column($customFieldsList, 'field_name'))
                                                ]['is_required'] == 1 ? 'required' : '') }}">
                    @php
                        $clinicianDisciplineForNTH = preg_split('/\r\n|[\r\n]/', $customFieldsList[
                                                array_search('Clinician Discipline', array_column($customFieldsList, 'field_name'))
                                            ]['options']);
                    @endphp
                    @foreach($clinicianDisciplineForNTH as $v)
                    <option {{in_array($v,$selectedclinicianDiscipline) ? "selected='selected'" : "" }}  value="{{$v}}">{{$v}}</option>
                    @endforeach
                </select>

            </div>
            @if(LD::checkFeatureFlag('add-pay-range-fields-nth-form'))
                @canPermission('nthFormFieldsForNthCard')
                <div class="col-md-6">
                    <label for="" class="d-block mb-2">Pay Range:</label>
                    <div class="d-flex align-items-center">
                        <div class="d-flex align-items-center me-4">
                            RN:
                            <input type="text" class="form-control mx-2" id="rnStart" name="rnStart" placeholder="From" value="{{ $rnParts[0] ?? '' }}" />
                            To
                            <input type="text" class="form-control mx-2" id="rnEnd" name="rnEnd" placeholder="To" value="{{ $rnParts[1] ?? '' }}" />
                        </div>
                        <div class="d-flex align-items-center">
                            LVN/LPN:
                            <input type="text" class="form-control mx-2" id="lvnStart" name="lvnStart" placeholder="From" value="{{ $lvnParts[0] ?? '' }}" />
                            To
                            <input type="text" class="form-control mx-2" id="lvnEnd" name="lvnEnd" placeholder="To" value="{{ $lvnParts[1] ?? '' }}"/>
                        </div>
                    </div>
                </div>
                @endcanPermission
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
        </div>
        <div class="form-group row">
            <div class="col-md-6">

                    <div class="d-flex">
                        <label  class="w-auto" for="address1">Address Line 1</label>
                        <div class="pl-1 pr-1 d-flex">
                            <label class="w-100" for="clientIsRelocating">Client is relocating</label>
                            <div class="custom-control custom-switch custom-switch-lg">
                            <input {{isset($needToHireAccounts->clientIsRelocating) && $needToHireAccounts->clientIsRelocating==1 ? "checked='checked'" : ""}}  class="custom-control-input" type="checkbox" id="clientIsRelocating" name="clientIsRelocating"/>
                                <label class="custom-control-label" for="clientIsRelocating"></label>
                            </div>
                        </div>
                    </div>
                <input type="text" class="form-control client-data" id="address1" name="address1" value="{{isset($patientsDetails->Patient_Address1) ? $patientsDetails->Patient_Address1 : ''}}" />
            </div>
            <div class="col-md-6">
                <label  class="w-100" for="address2">Address Line 2</label>
                <input type="text" class="form-control client-data" id="address2" name="address2" value="{{isset($patientsDetails->Patient_Address2) ? $patientsDetails->Patient_Address2 : ''}}" />
            </div>

        </div>
        <div class="form-group row">
            <div class="col-md-4">
                <label for="city">City</label>
                <input type="text" class="form-control client-data" id="city" name="city" value="{{isset($patientsDetails->Patient_City) ? $patientsDetails->Patient_City : ''}}" />
            </div>
            <div class="col-md-4">
                <label for="state">State</label>
                <input type="text" class="form-control client-data" id="state" name="state" value="{{isset($patientsDetails->Patient_State) ? $patientsDetails->Patient_State : ''}}"/>
            </div>
            <div class="col-md-4">
                <label for="zipCode">Zip Code</label>
                <input type="text" class="form-control client-data" id="zipCode" name="zipCode" value="{{isset($patientsDetails->Patient_Zip) ? $patientsDetails->Patient_Zip : ''}}" />
            </div>
        </div>
        <section class="client-relocating-address m-3 {{!isset($needToHireAccounts->clientIsRelocating) || $needToHireAccounts->clientIsRelocating==0 ? 'hide' : ''}} ">
            <h5 class="border-bottom p-2">Client Relocating address</h5>
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-auto" for="relocatingAddress1">Address Line 1</label>
                    <input type="text" class="form-control" id="relocatingAddress1" name="relocatingAddress1" value="{{isset($needToHireAccounts->relocatingAddress1) ? $needToHireAccounts->relocatingAddress1 : ''}}" />
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="relocatingAddress2">Address Line 2</label>
                    <input type="text" class="form-control" id="relocatingAddress2" name="relocatingAddress2" value="{{isset($needToHireAccounts->relocatingAddress2) ? $needToHireAccounts->relocatingAddress2 : ''}}" />
                </div>

            </div>
            <div class="form-group row">
                <div class="col-md-4">
                    <label for="relocatingCity">City</label>
                    <input type="text" class="form-control" id="relocatingCity" name="relocatingCity" value="{{isset($needToHireAccounts->relocatingCity) ? $needToHireAccounts->relocatingCity : ''}}" />
                </div>
                <div class="col-md-4">
                    <label for="relocatingState">State</label>
                    <input type="text" class="form-control" id="relocatingState" name="relocatingState" value="{{isset($needToHireAccounts->relocatingState) ? $needToHireAccounts->relocatingState : ''}}"/>
                </div>
                <div class="col-md-4">
                    <label for="relocatingZipCode">Zip Code</label>
                    <input type="text" class="form-control" id="relocatingZipCode" name="relocatingZipCode" value="{{isset($needToHireAccounts->relocatingZipCode) ? $needToHireAccounts->relocatingZipCode : ''}}" />
                </div>
            </div>
        </section>
        
        <div class="form-group row">
            <div class="col-md-12">
                <label for="scheduleDescription">Schedule Description</label>
                <textarea name="scheduleDescription" id="scheduleDescription" class="form-control">{{isset($needToHireAccounts->ScheduleText) ? $needToHireAccounts->ScheduleText : ''}}</textarea>
            </div>
        </div>
        <div class="form-group row">
            <div class="col-md-6">
                @if(LD::checkFeatureFlag('add-client-changing-payer-client-nth'))
                <div class="d-flex">
                    <label  class="w-auto" for="terminalPlayer">Terminal Payer</label>
                    <div class="pl-1 pr-1 d-flex">
                        <label class="w-100" for="isClientChangingPayer">Client will be changing payer</label>
                        <div class="custom-control custom-switch custom-switch-lg">
                        <input {{isset($needToHireAccounts->isClientChangingPayer) && $needToHireAccounts->isClientChangingPayer==1 ? "checked='checked'" : ""}} class="custom-control-input" type="checkbox" id="isClientChangingPayer" name="isClientChangingPayer"/>
                            <label class="custom-control-label" for="isClientChangingPayer"></label>
                        </div>
                    </div>
                </div>
                @else
                <label class="w-100" for="terminalPlayer">Terminal Payer</label>
                @endif
                <input type="text" class="form-control client-data" id="terminalPlayer" name="terminalPlayer" value="{{isset($patientsDetails->Payer) ? $patientsDetails->Payer : ''}}" />
            </div>
            <div class="col-md-6">
                <label class="w-100" for="respiteTerminalPlayer">Respite Terminal Payer</label>
                <input type="text" class="form-control client-data" id="respiteTerminalPlayer" name="respiteTerminalPlayer" value="{{isset($patientsDetails->RespitePayer) ? $patientsDetails->RespitePayer : ''}}" />
            </div>
        </div>
        @if(LD::checkFeatureFlag('add-client-changing-payer-client-nth'))
        <section class="client-change-payer m-3 {{!isset($needToHireAccounts->isClientChangingPayer) || $needToHireAccounts->isClientChangingPayer==0 ? 'hide' : ''}}">
            <h5 class="border-bottom p-2">Client will be changing payer</h5>
            <div class="form-group row">
                <div class="col-md-6">
                    <label  class="w-auto" for="newTerminalPayer">New Terminal Payer</label>
                    <input type="text" class="form-control" id="newTerminalPayer" name="newTerminalPayer" value="{{isset($needToHireAccounts->newTerminalPayer) ? $needToHireAccounts->newTerminalPayer : ''}}" />
                </div>
                <div class="col-md-6">
                    <label  class="w-100" for="payerEffectiveDates">Payer Effective Dates</label>
                    <input type="text" class="form-control" id="payerEffectiveDates" name="payerEffectiveDates" value="{{isset($needToHireAccounts->payerEffectiveDates) ? H::getFormatedDate($needToHireAccounts->payerEffectiveDates) : ''}}" autocomplete="off" />
                </div>

            </div>
            <div class="form-group row">
                <div class="col-md-5">
                    <label class="w-auto" for="newRespiteTerminalPayer">New Respite Terminal Payer</label>
                    <input type="text" class="form-control" id="newRespiteTerminalPayer" name="newRespiteTerminalPayer" value="{{isset($needToHireAccounts->newRespiteTerminalPayer) ? $needToHireAccounts->newRespiteTerminalPayer : ''}}" />
                </div>
            </div>
        </section>
        @endif
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button onclick="saveBuildSchedule(event, this);" class="btn btn-primary">{{$mode=='edit' ? "Save" : "Build Schedule"}}</button>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<x-modal>
    <x-slot name="id">isNTHAvailable</x-slot>
    <x-slot name="size">w-25</x-slot>
    <x-slot name="title">Client Account Available</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <span id="availableClientName"></span> has an existing NTH record open.
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button id="editNTHRecord" class="btn btn-primary">Edit NTH Record</button>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<x-modal>
    <x-slot name="id">validateClientIdOfClientModal</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="isCloseBtn">false</x-slot>
    <x-slot name="title">Add NTH Account</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <h5>This <span id="clientIdForClientIdValidation"></span> matches a <span id="clientStatusForClientIdValidation"></span> record for <span id="clientNameForClientIdValidation"></span>.  Is this client being re-admitted?</h5>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-primary" id="validateClientIdOfClientBtn" data-id="">Yes</button>
        <button type="button" class="btn btn-primary" id="validateClientIdOfClientNoBtn" >No</button>
    </x-slot>
</x-modal>
<x-modal>
    <x-slot name="id">validateClientNameOfClientModal</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="title">Add NTH Account</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <h5>There is already a <span id="matchedClientName"></span> with ClientID <span id="matchedClientId"></span>.  Is this who you are looking for?</h5>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-primary" id="yesClientNameMatchedBtn" data-id="">Yes</button>
        <button type="button" class="btn btn-primary" id="noClientNameMatchedBtn" >No</button>
    </x-slot>
</x-modal>
<script>
    $(document).on("hidden.bs.modal", "#isNTHAvailable", function() {
        $('#addNTHBuildScheduleForm')[0].reset();
        $(".modal:visible").length && $(document.body).addClass("modal-open");
    });

    <?php if (LD::checkFeatureFlag('validate-client-name-nth-form')) {?>
        function validateClientName(clientName){
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('nth.validateClientName')}}",
                type: 'get',
                data: {
                    clientName: clientName,
                    _token: _token
                },
                success: function(data) {
                    console.log(data,'data');
                    if(typeof data.patientDetails.id != 'undefined' && data.patientDetails.id != ''){
                        $('#matchedClientId').html(data.patientDetails.PatientAccount);
                        $('#yesClientNameMatchedBtn').attr('data-id',data.patientDetails.id);
                        $('#matchedClientName').html(data.patientDetails.clientName);
                        $('#validateClientNameOfClientModal').modal('show');
                    }
                }
            });
        }
        $('#clientName').off('blur',function(e){});
        $('#clientName').on('blur',function(e){
            <?php if(LD::checkFeatureFlag('add-client-form-allow-discharged-as-new-client')) { ?>
                console.log("disacharged Client :"+$("#addNTHBuildScheduleForm #is_discharged_client").val());
                if($("#addNTHBuildScheduleForm #is_discharged_client").val()!=1 && $(this).val() != '' && $("#addNTHBuildScheduleForm #aoc_patient_id").val() == ''){
                    validateClientName($(this).val());
                }
             <?php } else { ?>
                if($(this).val() != '' && $("#addNTHBuildScheduleForm #aoc_patient_id").val() == ''){
                    validateClientName($(this).val());
                }
                <?php } ?>
            
        });
        $(document).on('click','#yesClientNameMatchedBtn',function(e){
            if($(this).attr('data-id') != ''){
                getDetailsByClientChange($(this).attr('data-id'));
                $('#validateClientNameOfClientModal').modal('hide');
            }
        });
        $(document).on('click','#noClientNameMatchedBtn',function(e){
            $('#validateClientNameOfClientModal').modal('hide');

        });
        $(document).on("hidden.bs.modal", "#validateClientNameOfClientModal", function() {
            $(".modal:visible").length && $(document.body).addClass("modal-open");
        });
    <?php } ?>
    function validateClientId(clientId){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('nth.validateClientId')}}",
            type: 'get',
            data: {
                clientId: clientId,
                _token: _token
            },
            success: function(data) {
                console.log(data,'data');
                if(typeof data.patientDetails.id != 'undefined' && data.patientDetails.id != ''){
                    $('#validateClientIdOfClientBtn').attr('data-id',data.patientDetails.id);
                    $('#clientIdForClientIdValidation').html(data.patientDetails.PatientAccount)
                    $('#clientStatusForClientIdValidation').html(data.patientDetails.status);
                    $('#clientNameForClientIdValidation').html(data.patientDetails.clientName);
                    $('#validateClientIdOfClientModal').modal('show');
                }
            }
        });
    }
    $(document).on('click','#validateClientIdOfClientBtn',function(e){
        if($(this).attr('data-id') != ''){
            getDetailsByClientChange($(this).attr('data-id'));
            $('#validateClientIdOfClientModal').modal('hide');
        }
    });
    $(document).on('click','#validateClientIdOfClientNoBtn',function(e){
        $('#validateClientIdOfClientModal').modal('hide');
        $('#addNTHBuildScheduleForm #clientId').val('');

    });
    $('#clientId').off('blur',function(e){});
    $('#clientId').on('blur',function(e){
        if($(this).val() != ''){
            validateClientId($(this).val());
        }
    });
    $(document).on("hidden.bs.modal", "#validateClientIdOfClientModal", function() {
        $(".modal:visible").length && $(document.body).addClass("modal-open");
    });
    function saveBuildSchedule(e, button){
        e.preventDefault();
        if($('#addNTHBuildScheduleForm').valid()){
            button.disabled = true;
            $('.client-data').removeAttr('disabled');
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#addNTHBuildScheduleForm').serializeObject();
            console.log(data,data);
            data['_token'] = _token;
            $.ajax({
                url: "{{route('nth.saveBuildSchedule')}}",
                type: 'post',
                data: data,
                success: function(data) {
                    button.disabled = false;
                    if(data.status){
                        $('#addNTHForm').modal('hide');
                        <?php if($mode == 'insert'){ ?>
                            openBuildScheduleForm(data.aoc_nthaccounts_id, 'nthform');
                        <?php } ?>
                    }else{
                        console.log('Error:' + data.msg);
                        $('#addNTHBuildScheduleForm').prepend('<p class="alert alert-danger"> Error:'+data.msg+'</p>');
                    }
                }
            });
        }
    }
    function setFieldsValueByClientChange(branchDetails,patientsDetails,names,nthAccountId){
        var status = patientsDetails.is_intake == 1 ? 'New' : "Current";
        if (patientsDetails.CF_Skills == null) {
            patientsDetails.CF_Skills = '';
        }
        var skills = patientsDetails.CF_Skills.split(",");
        //Filling the new value from the client change
            $('#addNTHBuildScheduleForm #clientName').val(patientsDetails.Patient_FirstName+' '+patientsDetails.Patient_LastName);
        $('#addNTHBuildScheduleForm #clientId').val(patientsDetails.PatientAccount);

        <?php if (LD::checkFeatureFlag('add-client-form-allow-discharged-as-new-client')) {?>
            if(patientsDetails.Patient_CurrentStatusID!=5) {
                $('#addNTHBuildScheduleForm #aoc_patient_id').val(patientsDetails.id);
                $('#addNTHBuildScheduleForm #is_discharged_client').val(0);
            } else {
                $('#addNTHBuildScheduleForm #is_discharged_client').val(1);
            }
        <?php } else { ?> 
            $('#addNTHBuildScheduleForm #aoc_patient_id').val(patientsDetails.id);
        <?php } ?>
        
        $('#addNTHBuildScheduleForm #aoc_facility_id').val(patientsDetails.aoc_facility_id);
        $('#addNTHBuildScheduleForm #nthStatus').val(status);
        $('#addNTHBuildScheduleForm #ccc_id').val(patientsDetails.ccc_id);
        $('#addNTHBuildScheduleForm #ic_id').val(patientsDetails.ic_id);
        $('#addNTHBuildScheduleForm #liftingRequirements').val(patientsDetails.CF_LiftingRequired);
        $('#addNTHBuildScheduleForm #clinicianDiscipline').val(patientsDetails.CF_ClinicianDiscipline.split(","));
        $('#addNTHBuildScheduleForm #skills').val(patientsDetails.CF_Skills.split(","));
        $('#addNTHBuildScheduleForm #state').val(patientsDetails.Patient_State);
        $('#addNTHBuildScheduleForm #city').val(patientsDetails.Patient_City);
        $('#addNTHBuildScheduleForm #zipCode').val(patientsDetails.Patient_Zip);
        $('#addNTHBuildScheduleForm #terminalPlayer').val(patientsDetails.Payer);
        $('#addNTHBuildScheduleForm #respiteTerminalPlayer').val(patientsDetails.RespitePayer);
        $('#addNTHBuildScheduleForm #address1').val(patientsDetails.Patient_Address1);
        $('#addNTHBuildScheduleForm #address2').val(patientsDetails.Patient_Address2);
        $("#addNTHBuildScheduleForm #serviceNeeded").val(patientsDetails.CF_ServicesNeeded);
        $("#addNTHBuildScheduleForm #meeting_nurses").val(patientsDetails.CF_MeetingNurses);
        $("#addNTHBuildScheduleForm #scheduleSpecialized").val(names.scheduleSpecialized);
        <?php if (LD::checkFeatureFlag('specialized-vent-included-in-skills-nth-form')) {?>
            var keywords = ['trach', 'trach & vent', 'vent'];
            var skills = patientsDetails.CF_Skills.toLowerCase();
            var isSpecialized = false;

            if (skills.indexOf('trach') !== -1 || skills.indexOf('trach & vent') !== -1) {
                isSpecialized = true;
            } else if (skills.indexOf('vent') !== -1 && names.scheduleSpecialized === 1) {
                isSpecialized = true;
            }
            if(isSpecialized) {
                $("#addNTHBuildScheduleForm #specializedClient").attr('checked',true);
            }else{
                $("#addNTHBuildScheduleForm #specializedClient").attr('checked',false);
            }
        <?php } else {?>
            if(patientsDetails.CF_Skills.toLowerCase().indexOf('trach') != -1){
                $("#addNTHBuildScheduleForm #specializedClient").attr('checked',true);
            }else{
                $("#addNTHBuildScheduleForm #specializedClient").attr('checked',false);
            }
        <?php } ?>
        $('.client-data').attr('disabled',true);
        if(nthAccountId != ''){
            <?php if (LD::checkFeatureFlag('add-client-form-allow-discharged-as-new-client')) {?>
                if(patientsDetails.Patient_CurrentStatusID!=5) {
                    $('#availableClientName').html(patientsDetails.Patient_FirstName+' '+patientsDetails.Patient_LastName);
                    $('#isNTHAvailable').modal('show');
                    $('#editNTHRecord').attr('onclick','addNTHForm("'+nthAccountId+'")');
                }

         <?php } else {?>
                $('#availableClientName').html(patientsDetails.Patient_FirstName+' '+patientsDetails.Patient_LastName);
                $('#isNTHAvailable').modal('show');
                $('#editNTHRecord').attr('onclick','addNTHForm("'+nthAccountId+'")');
            <?php } ?>
        }

    }
    function getDetailsByClientChange(id){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('nth.getClientDetailsInJSON')}}",
            type: 'get',
            data: {
                id: id,
                _token: _token
            },
            success: function(data) {
                
                if(data.status){
                    setFieldsValueByClientChange(data.branchDetails,data.patientsDetails,data.names,data.nthAccountId);
                }
            }
        });
    }
    $("#addNTHBuildScheduleForm #clientName").keydown(function(event){
        if (String.fromCharCode(event.keyCode).match(/(\w|\s)/g) && event.which != 9) {
            $('.client-data').removeAttr('disabled');
            $('#addNTHBuildScheduleForm #aoc_patient_id').val('');
        }
    });
    $('#addNTHBuildScheduleForm #clientName').on('input', function() {
        if ($(this).val().trim() === '') {
            $('#addNTHBuildScheduleForm')[0].reset();
        }
    });
    $("#addNTHBuildScheduleForm #clientName").autocomplete({
        source: "{{ route('getScheduleClient') }}?from=addnth",
        classes: {
            "ui-autocomplete": "clientName"
        },
        minLength: 2,
        select: function(event, ui) {
            getDetailsByClientChange(ui.item.id);
        },
        change: function(event,ui) {
            @if(!H::canPermissions('nthallownewclient'))
                if (ui.item == null) {
                    $(this).val('');
                    $('#addNTHBuildScheduleForm')[0].reset();
                }
            @endif
            @if(LD::checkFeatureFlag('add-medicaid-number-on-add-nth-form'))
            if (ui.item != null) {
                $('#MedicaidNumber').attr('disabled', 'disabled');
                $('#MedicaidNumber').parent().hide();
                $('#MedicaidNumber').rules('remove', 'required');
            } else {
                $('#MedicaidNumber').removeAttr('disabled');
                $('#MedicaidNumber').parent().show();
                $('#MedicaidNumber').rules("add", "required");
            }
            @endif
        }
    });
    $("#addNTHBuildScheduleForm #respiteTerminalPlayer, #addNTHBuildScheduleForm #newRespiteTerminalPayer").autocomplete({
        source: "{{ route('nth.getRespiteTerminalPayer') }}",
        classes: {
            "ui-autocomplete": "respiteTerminalPlayer"
        },
        minLength: 2,
        select: function(event, ui) {}
    });
    $("#addNTHBuildScheduleForm #terminalPlayer, #addNTHBuildScheduleForm #newTerminalPayer").autocomplete({
        source: "{{ route('nth.getTerminalPayer') }}",
        classes: {
            "ui-autocomplete": "terminalPlayer"
        },
        minLength: 2,
        select: function(event, ui) {}
    });
    $(document).ready(function() {

        <?php if($mode=='edit') {?>
            $('.client-data').attr('disabled',true);
            @if(LD::checkFeatureFlag('allow-edit-nth-fields-in-nth-edit-form'))
                @if(H::canPermissions('nthallownewclient'))
                    @if((isset($patientsDetails->not_in_kantime) && $patientsDetails->not_in_kantime == 1))
                        $('.client-data#clientId').removeAttr('disabled');
                        $('.client-data#aoc_facility_id').removeAttr('disabled');
                        $('.client-data#terminalPlayer').removeAttr('disabled');
                        $('.client-data#respiteTerminalPlayer').removeAttr('disabled');
                    @endif
                @endif
            @endif
        <?php } ?>
        $('#addNTHBuildScheduleForm #clinicianDiscipline').multiselect({
            includeSelectAllOption: true,
        });
        $('#addNTHBuildScheduleForm #skills').multiselect({
            includeSelectAllOption: true,
            onSelectAll:function(){
                $("#addNTHBuildScheduleForm #specializedClient").attr('checked',true);
            },
            onDeselectAll:function(){
                $("#addNTHBuildScheduleForm #specializedClient").attr('checked',false);
            },
            onChange: function(option, checked, select) {
                <?php if (LD::checkFeatureFlag('specialized-vent-included-in-skills-nth-form')) {?>
                    var isSpecialized = false;
                    var keywords = ['trach', 'trach & vent', 'vent'];
                    $('#addNTHBuildScheduleForm #skills option:selected').each(function() {
                        var skills = $(this).val().toLowerCase();

                        if (skills.indexOf('trach') !== -1 || skills.indexOf('trach & vent') !== -1) {
                            isSpecialized = true;
                            return false;
                        } else if (skills.indexOf('vent') !== -1 && $("#addNTHBuildScheduleForm #scheduleSpecialized").val() === '1') {
                            isSpecialized = true;
                            return false;
                        }
                    });
                    if(isSpecialized) {
                        $("#addNTHBuildScheduleForm #specializedClient").attr('checked',true);
                    }else{
                        $("#addNTHBuildScheduleForm #specializedClient").attr('checked',false);
                    }
                <?php } else { ?>
					var isSpecialize = false;
                    $('#addNTHBuildScheduleForm #skills option:selected').each(function() {
                        if($(this).val().toLowerCase().indexOf('trach') != -1){
                            isSpecialize = true;
                        }
                    });
                    $("#addNTHBuildScheduleForm #specializedClient").attr('checked',isSpecialize);
                <?php } ?>
            }
        });
        <?php if($mode=='edit') {?>
            $('#addNTHBuildScheduleForm #clinicianDiscipline').multiselect("rebuild");
            $('#addNTHBuildScheduleForm #skills').multiselect("rebuild");
        <?php } ?>
        $("#addNTHBuildScheduleForm").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "nthStatus":{
                    required: true
                },
                "clientId":{
                    required: true
                },
                "aoc_facility_id":{
                    required: true
                },
                "clientName": {
                    required: function(element) {
                        return !($("#clientName").val() != '');
                    }
                },
                "clinicianDiscipline":{
                    required: true
                },
                "rnStart":{
                    required: true,
                    digits: true
                },
                "rnEnd":{
                    required: true,
                    digits: true
                },
                "lvnStart":{
                    required: true,
                    digits: true
                },
                "lvnEnd":{
                    required: true,
                    digits: true
                },
            },
            errorPlacement: function(error, element) {}
        });

        @if((isset($patientsDetails->not_in_kantime) && $patientsDetails->not_in_kantime == 1))
        $('#MedicaidNumber').rules("add", "required");
        @endif

            $('#clientIsRelocating').change(function(){
                if($('#clientIsRelocating').is(':checked')){
                    $('.client-relocating-address').removeClass('hide');
                }else{
                    $('.client-relocating-address').addClass('hide');
                }
            });
        @if(LD::checkFeatureFlag('add-client-changing-payer-client-nth'))
        $('#isClientChangingPayer').change(function(){
            if($('#isClientChangingPayer').is(':checked')){
                $('.client-change-payer').removeClass('hide');
            }else{
                $('.client-change-payer').addClass('hide');
            }
        });
        $('#payerEffectiveDates').datepicker();
        $('#payerEffectiveDates').datepicker().bind('click', function() {
            $('#ui-datepicker-div').appendTo($('#payerEffectiveDates').parent());
        });
        @endif
    });
</script>
