@php
use App\Helpers\LD;
use App\Helpers\H;
$locationList = DB::table('mssql_facilities')->where('hide','<>',1)->get();
    $locationData = [];
    if (count($locationList)) {
    $locationData = json_decode(json_encode($locationList), true);
    }

    $interviewTypeList = DB::table('schedule_types')->whereNotIn('type',['CCR','Needs to be Scheduled by RA'])->get();
    $interviewTypeData = [];
    if (count($interviewTypeList)) {
    $interviewTypeData = json_decode(json_encode($interviewTypeList), true);
    }
    if($tracker_id!=''){
    $myTitle = $client_data['candidate_name'];
    }else{
    $myTitle = 'Add Clinician';
    }
    if($bucket=='' || (preg_match('/^nthLeadInquiry##\d+$/', $bucket))){
    $outcomeList = [];
    }else{
    $outcomeList = config('constant')['recruitingOutcomes'][$bucket];
    }
        $approvedByList = [];
        $approvedUserList = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates');
        foreach($approvedUserList as $user) {
            $approvedByList[] = $user->name;
        }
    if(LD::checkFeatureFlag("new_payrate_approval_process")){
        $approvedByListLevel2 = [];
        $approvedUserListLevel2 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_2');
        foreach($approvedUserListLevel2 as $user) {
            $approvedByListLevel2[] = $user->name;
        }

        $approvedByListLevel3 = [];
        $approvedUserListLevel3 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_3');
        foreach($approvedUserListLevel3 as $user) {
            $approvedByListLevel3[] = $user->name;
        }
        $level4 = [];
        $approvedUserListLevel4 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_4');
        foreach($approvedUserListLevel4 as $user) {
            $level4[] = $user->name;
        }
        $level5 = [];
        $approvedUserListLevel5 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_5');
        foreach($approvedUserListLevel5 as $user) {
            $level5[] = $user->name;
        }
        $level6 = [];
        $approvedUserListLevel6 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_6');
        foreach($approvedUserListLevel6 as $user) {
            $level6[] = $user->name;
        }
        $level7 = [];
        $approvedUserListLevel7 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_7');
        foreach($approvedUserListLevel7 as $user) {
            $level7[] = $user->name;
        }
        $level8 = [];
        $approvedUserListLevel8 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_8');
        foreach($approvedUserListLevel8 as $user) {
            $level8[] = $user->name;
        }
        $level9 = [];
        $approvedUserListLevel9 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_9');
        foreach($approvedUserListLevel9 as $user) {
            $level9[] = $user->name;
        }
        $level10 = [];
        $approvedUserListLevel10 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_10');
        foreach($approvedUserListLevel10 as $user) {
            $level10[] = $user->name;
        }
        $level11 = [];
        $approvedUserListLevel11 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_11');
        foreach($approvedUserListLevel11 as $user) {
            $level11[] = $user->name;
        }
        $level12 = [];
        $approvedUserListLevel12 = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates_Level_12');
        foreach($approvedUserListLevel12 as $user) {
            $level12[] = $user->name;
        }
        $levelarray = [
            'Level1' => $approvedByList,
            'Level2' => $approvedByListLevel2,
            'Level3' => $approvedByListLevel3,
            'Level4' => $level4,
            'Level5' => $level5,
            'Level6' => $level6,
            'Level7' => $level7,
            'Level8' => $level8,
            'Level9' => $level9,
            'Level10' => $level10,
            'Level11' => $level11,
            'Level12' => $level12,
        ];
    }
    $facilities = H::getHelper("ConstantHelper")->getFacilities();

    @endphp
    <script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
    <script src="{{ mix('js/nth-schedule-select.js') }}"></script>
    <link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">

    <style>
    div.custom-control-right {
        padding-right: 24px;
        padding-left: 0;
        margin-left: 0;
        margin-right: 0;
    }

    div.custom-control-right .custom-control-label::after {
        right: -1.5rem;
        left: auto;
    }

    div.custom-control-right .custom-control-label::before {
        right: -2.35rem;
        left: auto;
    }

    .custom-control-right .custom-control-label {
        line-height: 22px;
    }

    .schedule-appointment {
        margin-top: 40px;
    }

    .schedule-appointment fieldset {
        padding: 15px;
        border: 1px solid #ddd;
    }

    #ui-datepicker-div {
        position: absolute !important;
        left: auto !important;
        top: auto !important;
        box-shadow: 0px 0px 3px 0px;
    }
    .ipeaUpcomingEvents p{
        margin-bottom: 5px;
    }
    .disabled {
        pointer-events:none;
        opacity:0.6;
    }
    button.multiselect.error {
        border: 1px dashed red;
        color: red;
        font-weight: 300;
    }
    </style>
    <x-modal>
        <x-slot name="id"> schedule_next</x-slot>
        <x-slot name="title">{{ $myTitle }}</x-slot>
        <x-slot name="class"></x-slot>
        <x-slot name="body">
            <div class="modal-body" id="scheduleNextSuccessDiv" style="display: none">
                <div id="successBody"></div>
                <div class="row pt-2">
                    <div class="col-md-7 text-right">
                        <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                    </div>
                </div>
            </div>
            <div class="modal-body" id="scheduleNextFormDiv">
                <form method="post" autocomplete="off" id="schedule_next_form" class="scaffold-form"
                    enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" id="linked_IPEA" name="linked_IPEA" value="0">
                    <input type="hidden" id="tracker_id" name="tracker_id" value="{{ $tracker_id??''}}">
                    <input type="hidden" id="referred_by" name="referred_by" value="">
                    <input type="hidden" id="patient_id" name="patient_id"
                        value="{{ $client_data['aoc_patient_id']??''}}">
                    <input type="hidden" id="mode" name="mode" value="{{ !empty($tracker_id) ? 'Edit' : 'Add'}}">
                    <input type="hidden" id="candidate_id" name="candidate_id"
                        value="{{ isset($candidatesDetails->id) && $candidatesDetails->id ? $candidatesDetails->id : $client_data['aoc_candidate_id']??'' }}">

                    <input type="hidden" id="provider_id" name="provider_id"
                        value="{{ $client_data['aoc_provider_id']??''}}">
                    <input type="hidden" id="facility_id" name="facility_id"
                        value="{{ $client_data['aoc_facility_id']??''}}">
                    <input type="hidden" id="nth_lead_bucket_id" name="nth_lead_bucket_id"
                        value="{{ $bucket??''}}">
                    <div class="row pt-2" style="margin: 12px 0px;;{{ empty($tracker_id) ? 'display:none' : ''}}">
                        <div class="col-md-6">
                            <h3>{{ $client_data['current_status']??''}}</h3>
                        </div>
                    </div>
                    @if(count($outcomeList)>0)
                    <div class="row pt-2">
                        <div class="col-md-6">
                            <label for="outcome">Outcome</label>
                            <select id="outcome" name="outcome" class="form-control" autocomplete="off">
                                <option value="">-- Select One --</option>
                                @foreach ($outcomeList as $item)
                                <option value="{{  $item }}">{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 attampted_to_contact_container" style="display:none;">
                            <label></label>
                            <div class="custom-control custom-switch" style="margin: 12px 0px;">
                                <input type="hidden" name="attampted_to_contact" value="0" />
                                <input class="custom-control-input" type="checkbox" id="attampted_to_contact"
                                    name="attampted_to_contact" value="1" />
                                <label class="custom-control-label" for="attampted_to_contact">Attempted to
                                    Contact?</label>
                            </div>
                        </div>
                    </div>
                    @endif
                    <div id="commonfields" style="{{ (count($outcomeList)>0 ) ? 'display: none;' : '' }}">
                    @if(LD::checkFeatureFlag('prn-changes-add-clinician'))
                            <div id="new_candidate" class="pb-2">
                                <div class="row pt-2">
                                    <div class="col-md-6">
                                        <label for="candidate_type">Candidate Type</label>
                                        @if(isset($candidatesDetails->id) && $candidatesDetails->id != '')
                                            <select id="candidate_type" name="candidate_type" class="form-control" readonly>
                                                <option value="new" {{isset($isNew) && $isNew ? 'selected':''}}>New</option>
                                                <option value="current" {{!isset($isNew) || !$isNew ? 'selected':''}}>Current</option>
                                                <option value="prn">Active Coverage</option>
                                            </select>
                                            <input
                                                class="hide"
                                                type="radio"
                                                id="status1"
                                                name="status"
                                                value="new"
                                                {{isset($isNew) && $isNew ? 'checked':''}}
                                                readonly/>
                                            <input
                                                class="hide"
                                                type="radio"
                                                id="status2"
                                                name="status"
                                                {{!isset($isNew) || !$isNew ? 'checked':''}}
                                                readonly
                                                value="current"/>
                                            <input
                                                class="hide"
                                                type="radio"
                                                id="status3"
                                                name="status"
                                                readonly
                                                value="prn"/>
                                        @else
                                            <select id="candidate_type" name="candidate_type" class="form-control">
                                                <option value="new" {{$tracker_id == '' ? 'selected' :''}}>New</option>
                                                <option value="current">Current</option>
                                                <option value="prn">Active Coverage</option>
                                            </select>
                                            <input
                                                class="hide"
                                                type="radio"
                                                id="status1"
                                                name="status"
                                                value="new"
                                                {{$tracker_id == '' ? 'checked' :''}}/>
                                            <input
                                                class="hide"
                                                type="radio"
                                                id="status2"
                                                name="status"
                                                value="current"/>
                                            <input
                                                class="hide"
                                                type="radio"
                                                id="status3"
                                                name="status"
                                                value="prn"/>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            <div id="new_candidate">
                                <div class="row pt-2">
                                    <div class="col-md-6"
                                        style="margin: 12px 0px;;{{ !empty($tracker_id) ? 'display:none' : ''}}">
                                        <label for=""></label>
                                        @if(isset($candidatesDetails->id) && $candidatesDetails->id != '')
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input class="custom-control-input" type="radio" id="status1" name="status" value="new" {{isset($isNew) && $isNew ? 'checked':''}} readonly/>
                                            <label class="custom-control-label" for="status1">New</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input class="custom-control-input" type="radio" id="status2" name="status" {{!isset($isNew) || !$isNew ? 'checked':''}} readonly value="current" />
                                            <label class="custom-control-label" for="status2">Current</label>
                                        </div>
                                        @else
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input class="custom-control-input" {{$tracker_id == '' ? 'checked' :''}}
                                                type="radio" id="status1" name="status" value="new" />
                                            <label class="custom-control-label" for="status1">New</label>
                                        </div>
                                        <div class="custom-control custom-radio custom-control-inline">
                                            <input class="custom-control-input" type="radio" id="status2" name="status"
                                                value="current" />
                                            <label class="custom-control-label" for="status2">Current</label>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endif
                        <div id="candidate_details">
                            <div class="row pt-2">
                                <div class="col-md-6">
                                    <label for="patient_id">Name</label>
                                    <input type="input" class="form-control" id="candidate_name"
                                        aria-describedby="emailHelp" name="candidate_name" autocomplete="off"
                                        value="{{ (isset($candidatesDetails->fullName) && $candidatesDetails->fullName) != '' ?
                                            $candidatesDetails->fullName : $client_data['candidate_name']??''}}"
                                        {{ !empty($tracker_id) ? 'readonly' : ''}}>

                                </div>
                                <div class="col-md-6">
                                    <label for="title">Title</label>
                                        @if(LD::checkFeatureFlag('change-att-to-aide-in-add-clinician-form'))
                                        <select id="title" name="title" class="form-control">
                                            <option value="">-- Select One --</option>
                                            @foreach(config('constant')['newSkills'] as $k => $v)
                                            <option value="{{$v}}"
                                                {{ (isset($candidatesDetails->skillset) && $candidatesDetails->skillset == $v) ? 'selected' :
                                                    ((isset($client_data['skillset']) && $client_data['skillset']== $v) ? 'selected' : '')}}>
                                                {{$v}}</option>
                                            @endforeach
                                        </select>
                                        @else
                                            <select id="title" name="title" class="form-control">
                                                <option value="">-- Select One --</option>
                                                @foreach(config('constant')['skills'] as $k => $v)
                                                <option value="{{$v}}"
                                                    {{ (isset($candidatesDetails->skillset) && $candidatesDetails->skillset == $v) ? 'selected' :
                                                        ((isset($client_data['skillset']) && $client_data['skillset']== $v) ? 'selected' : '')}}>
                                                    {{$v}}</option>
                                                @endforeach
                                            </select>
                                        @endif
                                </div>
                            </div>
                            <div class="row pt-2">
                                <div class="col-md-6">
                                    <div class="custom-control custom-switch custom-control-right"
                                        style="margin: 12px 0px;">
                                        <input type="hidden" name="bilingual" value="0" />
                                        <input class="custom-control-input" type="checkbox" id="bilingual"
                                            name="bilingual" value="1"
                                            {{ (isset($client_data['bilingual']) && $client_data['bilingual']!= 'No') ? 'checked' : '' }} />
                                        <label class="custom-control-label" for="bilingual">Bilingual?</label>
                                    </div>
                                </div>
                                @if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class'))
                                    <div class="col-md-6" id="specializedClientDiv" style="display: none;">
                                        <div class="custom-control custom-switch custom-control-right"
                                            style="margin: 12px 0px;">
                                            <input type="hidden" name="specializedClient" value="0" />
                                            <input class="custom-control-input" type="checkbox" id="specializedClient" readonly="true" name="specializedClient" value="1" checked />
                                            <label class="custom-control-label" for="specializedClient">Specialized Client?</label>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @if (LD::checkFeatureFlag('dashboard-nthalerts-inquiry-bucket'))
                                <div class="row  pt-2">
                                    <div class="col-md-6">
                                        <label for="city">Phone No</label>
                                        <input type="text" class="form-control" id="phone" name="phone"
                                            value="{{ (isset($candidatesDetails->mobile) && $candidatesDetails->mobile != '') ? $candidatesDetails->mobile : (isset($candidatesDetails->homePhone) ? $candidatesDetails->homePhone : $client_data['phone']??'') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email">Email</label>
                                        <input type="input" class="form-control" id="email" name="email"
                                            value="{{isset($candidatesDetails->email) ? $candidatesDetails->email : $client_data['email']??'' }}">
                                    </div>
                                </div>
                            @else
                                <div class="row  pt-2">
                                        <div class="col-md-6">
                                            <label for="city">Phone No</label>
                                            <input type="text" class="form-control" id="phone" name="phone"
                                                value="{{ (isset($candidatesDetails->mobile) && $candidatesDetails->mobile != '') ? $candidatesDetails->mobile : (isset($candidatesDetails->homePhone) ? $candidatesDetails->homePhone : '') }}">
                                        </div>
                                    <div class="col-md-6">
                                        <label for="email">Email</label>
                                        <input type="input" class="form-control" id="email" name="email"
                                            value="{{isset($candidatesDetails->email) ? $candidatesDetails->email : '' }}">
                                    </div>
                                </div>
                            @endif
                            <div class="row  pt-2">
                                <div class="col-md-6">
                                    <label for="caddress">Address1</label>
                                    <input type="text" class="form-control" id="caddress" name="caddress" value="{{isset($candidatesDetails->address) ? $candidatesDetails->address : '' }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="caddress2">Address2</label>
                                    <input type="text" class="form-control" id="caddress2" name="caddress2" value="{{isset($candidatesDetails->address2) ? $candidatesDetails->address2 : '' }}">
                                </div>
                            </div>
                            <div class="row  pt-2">
                                <div class="col-md-6">
                                    <label for="ccity">City</label>
                                    <input type="text" class="form-control" id="ccity" name="ccity" value="{{isset($candidatesDetails->city) ? $candidatesDetails->city : '' }}">
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="cstate">State</label>
                                            <input type="text" class="form-control" id="cstate" name="cstate" value="{{isset($candidatesDetails->state) ? $candidatesDetails->state : '' }}">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="cpostalCode">Zip</label>
                                            <input type="text" class="form-control" id="cpostalCode" name="cpostalCode" value="{{isset($candidatesDetails->postalCode) ? $candidatesDetails->postalCode : '' }}">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row  pt-2 prndisabled">
                                <div class="col-md-6 ui-widget">
                                    <label for="city">Client</label>
                                    @if(LD::checkFeatureFlag("add-schedule-matching-the-nth-schedules") && $isEffectiveDateGreaterThanNow)
                                        <span class="tooltip-icon" tabindex="0" aria-label="More info" title="Search by the client ID or the client name "></span>
                                        <img id="loadingGif" src="/images/LoaderIcon.gif"
                                             style="display: none; position: absolute; right: 27px; top: 70%; transform: translateY(-50%); width: 16px; height: 16px;">
                                    @endif
                                    <input type="input" class="form-control" id="ClientName"
                                        aria-describedby="emailHelp" name="ClientName"
                                        value="{{ $client_data['ClientName']??''}}">
                                    <span id="error-message" style="color: red; display: none;">There is something wrong. Please select the client again</span>
                                </div>
                                <div class="col-md-6">
                                    @if(LD::checkFeatureFlag("add-schedule-matching-the-nth-schedules") && $isEffectiveDateGreaterThanNow)
                                        <label for="nth_schedule_ids">Select Schedule</label>
                                        <select id="nth_schedule_ids" name="nth_schedule_ids[]" multiple="multiple" class="form-control" autocomplete="off">
                                        </select>
                                        <span style="font-weight: 700; color: lightcoral;" id="nth_schedule_error_message"></span>
                                            <img id="nth_schedule_loadingGif" src="/images/LoaderIcon.gif"
                                                 style="display: none; position: absolute; right: 47px; top: 70%; transform: translateY(-50%); width: 16px; height: 16px;">
                                        <script type="text/javascript">
                                            var nthScheduleSelect = window.NthScheduleSelect.init({
                                                selectSelector: '#nth_schedule_ids',
                                                totalHoursSelector: '#total_hours',
                                                loadingGifSelector: '#nth_schedule_loadingGif',
                                                errorMessageSelector: '#nth_schedule_error_message',
                                                autoPopulateHours: @json(LD::checkFeatureFlag('clinician-form-auto-populate-hours-from-schedule')),
                                                url: "{{route('getnthschedulebyclientid')}}",
                                            });
                                        </script>
                                    @else
                                        <label for="schedule">Schedule</label>
                                        <input type="input" class="form-control" id="schedule" name="schedule"
                                            value="{{ $client_data['schedule']??''}}">
                                    @endif
                                </div>
                            </div>

                            <div class="row pt-2 prndisabled">
                                <div class="col-md-6">
                                    <label for="total_hours">Total Hours</label>
                                    <input type="input" class="form-control" id="total_hours" name="total_hours"
                                        value="{{ $client_data['total_hours']??'0'}}">
                                </div>
                                <div class="col-md-6">
                                    <label for="pay_rate">Pay Rate</label>
                                    <input type="input" class="form-control float" id="pay_rate" name="pay_rate"
                                        value="{{ $client_data['pay_rate']??''}}">
                                </div>
                            </div>

                            <div class="row pt-2 prndisabled">
                                <div class="col-md-6">
                                    <label for="city">GP %</label>
                                    <input type="input" class="form-control" id="gp" aria-describedby="emailHelp"
                                        name="gp" value="{{ $client_data['gp']??''}}">
                                </div>
                                <div class="col-md-6">
                                    <label for="state">Approved By</label>
                                    <select id="gp_approved_by" name="gp_approved_by" class="form-control"
                                        autocomplete="off">
                                        <option value="">-- Select One --</option>
                                        @foreach ($approvedByList as $item)
                                        <option value="{{  $item }}">{{ $item }}</option>
                                        @endforeach
                                    </select>
                                </div>

                            </div>
                            @if(LD::checkFeatureFlag('prn-changes-add-clinician'))
                            @php
                                $prnBranchData = H::getHelper('ConstantHelper')->getPRNClients();
                            @endphp
                            <div class="row pt-2 prnenabled hide">
                                <div class="col-md-6">
                                    <label for="prn_branch">Active Coverage Branch</label>
                                    <select
                                        disabled
                                        class="form-control"
                                        name="prn_branch"
                                        id="prn_branch">
                                        <option value="">-- Select One --</option>
                                        @foreach($prnBranchData as $prnBranch)
                                            <option
                                                data-branch="{{$prnBranch->branch}}"
                                                value="{{$prnBranch->aoc_patient_id}}">
                                                    {{$prnBranch->client_name}}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="shift_type">Shift Type</label>
                                    <select
                                        disabled
                                        class="form-control"
                                        name="shift_type[]"
                                        id="shift_type"
                                        multiple>
                                        <option value="Weekday - Days">Weekday - Days</option>
                                        <option value="Weekday - Nights">Weekday - Nights</option>
                                        <option value="Weekend - Days">Weekend - Days</option>
                                        <option value="Weekend - Nights">Weekend - Nights</option>
                                    </select>
                                </div>
                            </div>
                            @endif
                            <div class="row pt-2">
                                <div class="col-md-6">
                                    <label for="country">Branch:</label>
                                    <select class="form-control" {{isset($client_data["branch_location"]) ? "disabled='true'" : ""}} name="branch_location" id="branch_location">
                                        <option value="">-- Select One --</option>
                                        @foreach($facilities as $facility)
                                            <option data-state="{{$facility->State}}" {{isset($client_data["branch_location"]) && $client_data["branch_location"] == $facility->FacilityName ? "selected='selected'" : ""}} value="{{$facility->FacilityName}}">{{$facility->FacilityName}}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6 prndisabled">
                                    <label for="country">Payer:</label>
                                    <input type="input" class="form-control" id="payer" name="payer"
                                        value="{{ $client_data['payer']??''}}">
                                </div>
                            </div>
                        </div>
                        <div id="candidate_note">
                            <div class="row pt-2">
                                <div class="col-md-12">
                                    <label for="note">Note:</label>
                                    <textarea name="note" id="note"
                                        class="form-control">{{ $client_data['note']??''}}</textarea>
                                </div>
                            </div>
                        </div>
                        <div id="schedule_appointment" class="schedule-appointment">
                            <h6>Schedule</h6>
                            <fieldset>
                                <div class="row pt-2">
                                    <div class="col-md-6">
                                        <div class="row pt-2">
                                            <div class="col-md-12">
                                                <label for="schedule_type">Interview Type</label>
                                                <select id="schedule_type" name="schedule_type" class="form-control"
                                                    autocomplete="off">
                                                    <option value="">-- Select One --</option>
                                                    @foreach ($interviewTypeData as $item)
                                                    <option value="{{  $item['type'] }}"
                                                        {{ (isset($client_data['schedule_type']) && $client_data['schedule_type']== $item['type']) ? 'selected' : '' }}>
                                                        {{ $item['type'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row pt-2 showforipea">
                                            <div class="col-md-12">
                                                <label for="ipea_location_id">IPEA Location</label>
                                                <select name="ipea_location_id" class="form-control" id="ipea_location_id">
                                                    <option value="">-- Select One --</option>
                                                    @foreach($branches as $key => $branch)
                                                    <optgroup label="{{$key}}">
                                                        @foreach($branch as $b)
                                                        <option value="{{$b['id']}}">{{$b['branch']}}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row pt-2 hideforneedstobeschedulebyra">
                                            <div class="col-md-12" style="position:relative">
                                                <label for="datepicker">Date</label>
                                                <input type="input" class="form-control" id="datepicker"
                                                    name="appointment_date" />
                                            </div>
                                        </div>
                                        <div class="row pt-2 hideforccrschool hideforneedstobeschedulebyra">
                                            <div class="col-sm-12">
                                                <label for="appointment_time">Time</label>
                                                <input type="input" class="form-control" id="appointment_time"
                                                    name="appointment_time">
                                            </div>
                                        </div>

                                        <div class="row pt-2 appointment-time-ccr" style="display: none;" >
                                            <div class="col-sm-12">
                                                <label for="appointment_time_ccr">Time</label>
                                                <select name="appointment_time" disabled="true" class="form-control" id="appointment_time_ccr">
                                                    <option value="">-- Select One --</option>
                                                    @php
                                                        $ccrTimeSlotArr = config('constant')['ccrTimeSlot'];
                                                        array_pop($ccrTimeSlotArr);
                                                    @endphp
                                                    @foreach($ccrTimeSlotArr as $k => $v)
                                                        <option value="{{$v}}">{{$v}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row pt-2 ccrScheduleFields">
                                            <div class="col-md-12" style="position:relative">
                                                <label for="proposed_next_step">Proposed Next Step</label>
                                                <select name="proposed_next_step" class="form-control" id="proposed_next_step">
                                                    <option value="">-- Select One --</option>
                                                    @foreach(config('constant')['ccrScheduleProposedNextStep'] as $k => $v)
                                                        <option value="{{$v}}">{{$v}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row pt-2 ccrScheduleFields">
                                            <div class="col-md-12" style="position:relative">
                                                <label for="proposed_ns_date">Proposed Date</label>
                                                <input type="input" class="form-control" id="proposed_ns_date"
                                                    name="proposed_ns_date" />
                                            </div>
                                        </div>
                                        <div class="row pt-2 ccrScheduleFields">
                                            <div class="col-sm-12">
                                                <label for="proposed_ns_time">Proposed Time</label>
                                                <input type="input" class="form-control" id="proposed_ns_time"
                                                    name="proposed_ns_time">
                                            </div>
                                        </div>
                                        <div class="row pt-2 crr-notes" style="display: none;">
                                            <div class="col-sm-12">
                                                <label for="ccrNotes">CCR Notes</label>
                                                <textarea name="ccrNotes" id="ccrNotes" class="form-control"></textarea>
                                            </div>
                                        </div>
                                        <div class="row pt-2">
                                            <div class="col-sm-4">
                                                <div class="custom-control custom-switch custom-control-right showforipea"
                                                    style="margin: 12px 0px;">
                                                    <input type="hidden" name="is_rehire" value="0" />
                                                    <input class="custom-control-input" type="checkbox" id="is_rehire"
                                                        name="is_rehire" value="1" />
                                                    <label class="custom-control-label" for="is_rehire">Is this rehire?</label>
                                                </div>
                                            </div>
                                            <div class="col-sm-4">
                                                <div class="custom-control custom-switch custom-control-right showforipea" style="margin: 12px 0px;">
                                                    <input type="hidden" name="new_grad" value="0" />
                                                    <input class="custom-control-input" type="checkbox" id="new_grad" name="new_grad" value="1" />
                                                    <label class="custom-control-label" for="new_grad">New Grad</label>
                                                </div>
                                            </div>
                                            @if(LD::checkFeatureFlag('show-virtual-ipea'))
                                            <div class="col-sm-4">
                                                <div class="custom-control custom-switch custom-control-right showforipea" style="margin: 12px 0px;">
                                                    <input type="hidden" name="is_virtual" value="0" />
                                                    <input class="custom-control-input" type="checkbox" id="is_virtual" name="is_virtual" value="1" />
                                                    <label class="custom-control-label" for="is_virtual">Virtual IPEA</label>
                                                </div>
                                            </div>
                                            @endif
                                        </div>
                                        <div class="row pt-2 showforipea">
                                            <div class="col-md-12">
                                                <label for="why_nurse_chosen">Why was nurse choosen for this client?</label>
                                                <textarea name="why_nurse_chosen" id="why_nurse_chosen" cols="80" rows="7"
                                                    class="form-control"></textarea>
                                            </div>
                                            <div class="col-md-12">
                                                <label for="referred_by">Referred By</label>
                                                <input type="input" class="form-control" id="ProviderName"
                                                aria-describedby="emailHelp" name="ProviderName"
                                                value="">
                                            </div>
                                        </div>
                                    </div>
									<div class="col-md-6 ccrbefornext" style="display: none;">
                                       <p class="alert alert-danger">Clinician requires a CCR before next steps can be scheduled. Please note this is a proposed date/time until the CCM has completed the CCR.</p>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="pt-2 showforipeabranchnote" style="display: none;">
                                            <fieldset><div class="pt-2 ipeabranchnote">This is note section</div></fieldset>
                                        </div>
                                        <div class="pt-2 ipeaUpcomingEvents" style="display: none;">
                                            <h5>Upcoming Branch Events:</h5>
                                            <ul></ul>
                                        </div>
                                    </div>
                                </div>
                            </fieldset>
                        </div>
                        @if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class'))
                        @canPermission('showscheduleclassform')
                            @if (LD::checkFeatureFlag('P1-3035-change-date-sort-trach-vent'))
                                <div id="trach_vent_class_request" class="schedule-appointment">
                                    <input type="hidden" name="show_trach_vent" id="show_trach_vent" value="0"/>
                                    <h6>Schedule Trach/Vent Class</h6>
                                    <fieldset>
                                        <div class="row pt-2">
                                            <div class="col-md-6">
                                                <label for="schedule_type">Class</label>
                                                <select name="trach_vent_class_type" id="trach_vent_class_type" class="form-control">
                                                    @foreach ($classCategories as $key => $item)
                                                    <option value="{{ $key }}">{{ $item }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                @php $trachVentScheduleClassClassTypeList = config('constant')['trachVentScheduleClassClassType']; @endphp
                                                <label for="class_type">Class Type</label>
                                                <select name="class_type" id="class_type" class="form-control">
                                                    @foreach ($trachVentScheduleClassClassTypeList as $key => $item)
                                                        <option value="{{$key}}">{{$item}}</option>
                                                    @endforeach
                                                    <option value="Request Class">Request Class</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row pt-2">
                                            <div class="col-md-6">
                                                <label for="location_id">Branch</label>
                                                <select name="location_id" class="form-control" id="location_id">
                                                    <option value="">-- Select One --</option>
                                                    @foreach($branches as $key => $branch)
                                                    <optgroup label="{{$key}}">
                                                        @foreach($branch as $b)
                                                        <option value="{{$b['id']}}">{{$b['branch']}}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 hide pl-0 pr-0">
                                                <div class="col-md-12">
                                                    <label for="trach_vent_requested_date_time">Candidates available dates</label>
                                                    <input type="text" class="form-control" id="trach_vent_requested_date_time" name="trach_vent_requested_date_time" />
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row pt-2">
                                            <div class="col-md-6">
                                                <label for="occurrence_id">Date</label>
                                                <select name="occurrence_id" class="form-control" id="occurrence_id">
                                                    <option value="">-- Select One --</option>
                                                </select>
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            @else
                                <div id="trach_vent_class_request" class="schedule-appointment">
                                    <h6>Schedule Trach/Vent Class</h6>
                                    <fieldset>
                                        <div class="row pt-2">
                                            <div class="col-md-6">
                                                <label for="schedule_type">Type</label>
                                                <select name="trach_vent_class_type" id="trach_vent_class_type" class="form-control">
                                                    @foreach ($classCategories as $key => $item)
                                                    <option value="{{ $key }}">{{ $item }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row pt-2">
                                            <div class="col-md-6">
                                                <label for="location_id">Branch</label>
                                                <select name="location_id" class="form-control" id="location_id">
                                                    <option value="">-- Select One --</option>
                                                    @foreach($branches as $key => $branch)
                                                    <optgroup label="{{$key}}">
                                                        @foreach($branch as $b)
                                                        <option value="{{$b['id']}}">{{$b['branch']}}</option>
                                                        @endforeach
                                                    </optgroup>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row pt-2">
                                            <div class="col-md-6">
                                                <label for="occurrence_id">Class</label>
                                                <select name="occurrence_id" class="form-control" id="occurrence_id">
                                                    <option value="">-- Select One --</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row pt-2 hide">
                                            <div class="col-md-6">
                                                <label for="trach_vent_requested_date_time">Requested Date/Time</label>
                                                <input type="text" class="form-control" id="trach_vent_requested_date_time" name="trach_vent_requested_date_time" />
                                            </div>
                                        </div>
                                    </fieldset>
                                </div>
                            @endif
                        @endcanPermission
                        @endif
                        <div id="candidate_save" class="row pt-2">
                            <div class="col-md-7 text-right">
                                    <button type="button" onclick="checkCandidateMatch(this)" class="btn btn-primary">Save</button>
                                    @if (LD::checkFeatureFlag('save-and-new-button'))
                                        @if($show_save_next_btn == 1)
                                        <button type="button" onclick="checkCandidateMatch(this, 1)" class="btn btn-primary">Save & New</button>
                                        @endif
                                    @endif
                            </div>


                        </div>
                        <div class="col-md-12 text-center  alert alert-error hide" id="error-msg-provider">
                            You must select a clinician from the drop down list if 'Current' is selected
                        </div>
                        <div class="col-md-12 text-center alert alert-error hide" id="error-msg-kantime-hours">
                            <span id="error-msg-kantime-hours-text"></span>
                            <a href="javascript:void(0);" id="error-msg-kantime-hours-link" class="hide">View existing tracker</a>
                        </div>
                        <div class="col-md-12 text-center alert alert-error hide" id="error-msg-duplicate-submit"></div>
                </form>
            </div>
        </x-slot>
        <x-slot name="footer">
            <!-- Footer of the modal -->
        </x-slot>
    </x-modal>

    <x-modal>
        <x-slot name="id">candidateMatchCheck</x-slot>
        <x-slot name="title">Clinician Match</x-slot>
        <x-slot name="body">
            <div class="col-md-12">
                <h5>Are you sure this is not the current clinician shown below?</h5>
                <div class="matched-candidate-details">
                    <p id="candidateFullName"></p>
                    <p id="candidatePhoneMobile"></p>
                    <p id="candidateExternalEmail"></p>
                    <p id="candidateCityState"></p>
                </div>
                <button type="button" onclick="setSchedule(this)" class="btn btn-light">Yes, I'm sure.  Continue with <strong>NEW</strong> clinician</button>
                <button data-res="" onclick="setClinicianAsCurrentAfterMatch()" id="currentClinicianInsteadBtn" type="button" class="btn btn-primary">Change to CURRENT clinician instead</button>
            </div>
        </x-slot>
        <x-slot name="footer"></x-slot>
    </x-modal>

 @if (LD::checkFeatureFlag('add-clinician-form-active-coverage-autopopulate'))
     <x-modal>
        <x-slot name="id">candidateMatchCheckActiveCoverage</x-slot>
        <x-slot name="title">Clinician Match</x-slot>
        <x-slot name="body">
            <div class="col-md-12">
                <h5>The clinician shown below currently exists. An Active Coverage Clinician can not be scheduled for an IPEA and instead requires a Status Change Request.</h5>
                <div class="matched-candidate-details">
                    <p id="candidateFullNameActiveCoverage"></p>
                    <p id="candidatePhoneMobileActiveCoverage"></p>
                    <p id="candidateExternalEmailActiveCoverage"></p>
                    <p id="candidateCityStateActiveCoverage"></p>
                </div>
                <button data-res="" id="activeCoveragePopupClose" type="button" class="btn btn-primary">Close</button>
            </div>
        </x-slot>
        <x-slot name="footer"></x-slot>
    </x-modal>

    <script>
        $(document).on("hidden.bs.modal", "#candidateMatchCheckActiveCoverage", function() {
            $("#schedule_next_form #candidate_type").val('new').trigger('change');
            $("#schedule_next_form #schedule_type").val('').trigger('change');
            $("#schedule_next_form #patient_id").val('');
            $("#schedule_next_form #appointment_date").val('');
            $("#schedule_next_form #appointment_time").val('');
            $('body').addClass("modal-open");
        });

        $(document).on("click","#activeCoveragePopupClose",function(){
            $('#candidateMatchCheckActiveCoverage').modal('hide');
        });
    </script>
  @endif

    <style>
    .ui-autocomplete {
        z-index: 999999;
    }
    .btn-light {
        background-color: #f8f9fa;
        border-color: #f8f9fa;
        color: #212529;
    }
    .matched-candidate-details{
        border-left: 3px solid #2f5baa;
        padding-left: 15px;
        margin: 20px;
    }
    .matched-candidate-details p{
        margin-bottom: 5px;
    }

    /* Tooltip Icon */
    .tooltip-icon{
        --size: 18px;
        display: inline-block;
        width: var(--size);
        height: var(--size);
        line-height: var(--size);
        text-align: center;
        border-radius: 50%;
        background: #2563eb;
        color: #fff;
        font-weight: 700;
        font-family: Arial, Helvetica, sans-serif;
        font-size: 12px;
        position: relative;
        vertical-align: middle;
        user-select: none;
        box-shadow: 0 1px 2px rgba(0,0,0,0.12);
    }

    .tooltip-icon::after{
        content: "i";
        display: inline-block;
        transform: translateY(-1px);
    }
    </style>

    <script type="text/javascript">
    function checkCandidateMatch(button,saveandnew = 0){
        let Candidate_type = $("#schedule_next_form #candidate_type").val();
        let patientIdField = $("#schedule_next_form #patient_id");
        let patientId = patientIdField.val().trim();
        if(Candidate_type != "prn"){
        if (!patientId) {
            $("#error-message").show();
            $('#ClientName').val('');
            $('#patient_id').val('');
            patientIdField.focus();
            return false;
        }
        }
        $("#error-message").hide();
        @if (LD::checkFeatureFlag('add-clinician-form-active-coverage-autopopulate'))
            if(($('input[name=status]:checked', '#schedule_next_form').val() == 'new' || $('input[name=status]:checked', '#schedule_next_form').val() == 'prn' ) && $('#schedule_next_form').valid()){
                if (!window.scheduleNextSubmitLock.acquire()) {
                    return false;
                }
                $.ajax({
                    url: "{{ route('candidate.checkCandidateMatch') }}",
                    type: "POST",
                    data: $("#schedule_next_form").serialize(), //only input
                    // Without an error handler a failed pre-check would hold the lock and leave the
                    // form unusable until the page is reloaded.
                    error: function() {
                        window.scheduleNextSubmitLock.release();
                    },
                    success: function(response) {
                        window.scheduleNextSubmitLock.release();
                        if(typeof response.id != 'undefined' && response.id != ''){
                            if($('input[name=status]:checked', '#schedule_next_form').val() == 'prn') {
                                $('#candidateMatchCheckActiveCoverage #candidateFullNameActiveCoverage').html(response.fullName);
                                $('#candidateMatchCheckActiveCoverage #candidatePhoneMobileActiveCoverage').html(response.Phone+' | '+response.Mobile);
                                $('#candidateMatchCheckActiveCoverage #candidateExternalEmailActiveCoverage').html(response.External_Email);
                                $('#candidateMatchCheckActiveCoverage #candidateCityStateActiveCoverage').html(response.City+', '+response.State);

                                $('#candidateMatchCheckActiveCoverage').modal('show');

                            } else {
                                $('#candidateMatchCheck #candidateFullName').html(response.fullName);
                                $('#candidateMatchCheck #candidatePhoneMobile').html(response.Phone+' | '+response.Mobile);
                                $('#candidateMatchCheck #candidateExternalEmail').html(response.External_Email);
                                $('#candidateMatchCheck #candidateCityState').html(response.City+', '+response.State);
                                $("#currentClinicianInsteadBtn").attr('data-res',JSON.stringify(response));
                                $('#candidateMatchCheck').modal('show');
                            }

                        }else{
                            setSchedule(button, saveandnew);
                        }
                    }
                });
            }else{
                setSchedule(button, saveandnew);
            }
        @else
            if($('input[name=status]:checked', '#schedule_next_form').val() == 'new' && $('#schedule_next_form').valid()){
                if (!window.scheduleNextSubmitLock.acquire()) {
                    return false;
                }
                $.ajax({
                    url: "{{ route('candidate.checkCandidateMatch') }}",
                    type: "POST",
                    data: $("#schedule_next_form").serialize(), //only input
                    // Without an error handler a failed pre-check would hold the lock and leave the
                    // form unusable until the page is reloaded.
                    error: function() {
                        window.scheduleNextSubmitLock.release();
                    },
                    success: function(response) {
                        window.scheduleNextSubmitLock.release();
                        if(typeof response.id != 'undefined' && response.id != ''){
                            $('#candidateMatchCheck #candidateFullName').html(response.fullName);
                            $('#candidateMatchCheck #candidatePhoneMobile').html(response.Phone+' | '+response.Mobile);
                            $('#candidateMatchCheck #candidateExternalEmail').html(response.External_Email);
                            $('#candidateMatchCheck #candidateCityState').html(response.City+', '+response.State);
                            $("#currentClinicianInsteadBtn").attr('data-res',JSON.stringify(response));
                            $('#candidateMatchCheck').modal('show');
                        }else{
                            setSchedule(button, saveandnew);
                        }
                    }
                });
            }else{
                setSchedule(button, saveandnew);
            }
        @endif
    }

    function validateNthSchedules(button, saveandnew) {
        if (!window.scheduleNextSubmitLock.acquire()) {
            return false;
        }
        $('#scheduleconflictmsg').remove();
        $.ajax({
            url: "{{ route('validateNthSchedules') }}",
            type: "POST",
            data: $("#schedule_next_form").serialize(), //only input
            error: function() {
                window.scheduleNextSubmitLock.release();
            },
            success: function(response) {
                window.scheduleNextSubmitLock.release();
                if (response.status == 'true') {
                    if (response.conflictMsg) {
                        $('<div class="row pt-2" id="scheduleconflictmsg"><div class="col-md-12"><div class="alert alert-danger" role="alert">' + response.conflictMsg + '</div></div></div>').insertAfter($("#candidate_save"));
                    }

                    let _token = $('meta[name="csrf-token"]').attr('content');
                    var formData = {
                        _token:_token,
                        patient_id: $("#patient_id").val()
                    };

                    $.ajax({
                        url: "{{ route('getpatientstatebyid') }}",
                        type: "GET",
                        data: formData, //only input
                        success: function(response) {
                            if(response.state=='PA' || response.state=='FL'  || response.state=='NC') {
                                $("#scheduleconflictmsg").append('<div class="col-md-12"><button type="button" onclick="setScheduleNew(this, '+saveandnew+')" class="btn btn-primary">Save Anyway</button></div>');
                                //setScheduleNew(button, saveandnew);
                            }
                        }
                    });
                } else {
                    setScheduleNew(button, saveandnew);
                }
            }
        });
    }

    function setClinicianAsCurrentAfterMatch(){
        var data = $("#currentClinicianInsteadBtn").attr('data-res');
        data = JSON.parse(data);
        $('#schedule_next_form #candidate_id').val('');
        $('#schedule_next_form #candidate_name').val(data.fullName);
        $('#schedule_next_form #phone').val(data.Phone != '' ? data.Phone : data.Mobile);
        $('#schedule_next_form #email').val(data.External_Email);
        $('#schedule_next_form #status2').attr('checked','checked');
        $('#candidateMatchCheck').modal('hide');
    }

    $(document).on("hidden.bs.modal", "#candidateMatchCheck", function() {
        $('body').addClass("modal-open");
    });

    // Wait for the DOM to be ready
    $(function() {
        $("#schedule_next_form").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "ClientName": {
                    required: true
                },
                "ipea_location_id": {
                    required: function(element) {
                        return $("#schedule_type").val() != '';
                    }
                },
                "candidate_name": {
                    required: true
                },
                "branch_location": {
                    required: true
                },
                "title": {
                    required: true
                },
                "phone": {
                    required: true
                },
                "email": {
                    required: true,
                    email: true
                },
                "datepicker": {
                    required: function(element) {
                        return $("#schedule_type").val() != '';
                    }
                },
                "appointment_time": {
                    required: function(element) {
                        return $("#schedule_type").val() != '';
                    }
                }
            },
            errorPlacement: function(error, element) {},
            highlight: function( element, errorClass, validClass ) {
                if (element.id == "nth_schedule_ids") {
                    let e = jQuery('#nth_schedule_ids').parent().find('button.multiselect');
                    e.addClass( errorClass ).removeClass( validClass );
                } else if (element.id == "shift_type") {
                    let e = jQuery('#schedule_next_form #shift_type').parent().find('button.multiselect');
                    e.addClass( errorClass ).removeClass( validClass );
                } else {
                    if ( element.type === "radio" ) {
                        this.findByName( element.name ).addClass( errorClass ).removeClass( validClass );
                    } else {
                        $( element ).addClass( errorClass ).removeClass( validClass );
                    }
                }
            },
            unhighlight: function( element, errorClass, validClass ) {
                if (element.id == "nth_schedule_ids") {
                    let e = jQuery('#nth_schedule_ids').parent().find('button.multiselect');
                    e.removeClass( errorClass ).addClass( validClass );
                } else if (element.id == "shift_type") {
                    let e = jQuery('#schedule_next_form #shift_type').parent().find('button.multiselect');
                    e.removeClass( errorClass ).addClass( validClass );
                } else {
                    if ( element.type === "radio" ) {
                        this.findByName( element.name ).removeClass( errorClass ).addClass( validClass );
                    } else {
                        $( element ).removeClass( errorClass ).addClass( validClass );
                    }
                }
            }
        });
    });
    </script>

    <script type="text/javascript">

    //CCR Scheduling

    var blockedTimeSlot = [];
    var slotArray = '<?php echo json_encode($ccrTimeSlotArr);?>';
    slotArray = JSON.parse(slotArray);
    console.log(slotArray,'sdf');
    function checkingCCRTimeSlotAndSetting(selectedDate){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $('#appointment_time_ccr option').removeClass('hide');

        // Added the logic for the Today date selection we have to restric the previous and the 30 minutes gap before the time slot
        var currentDateTime = new Date();
        var currentHour = currentDateTime.getHours();
        var currentMinutes = currentDateTime.getMinutes();
        currentDateTime.setHours(0,0,0,0);
        const isSelectedToday = (new Date(selectedDate).getTime() === currentDateTime.getTime()) ? true : false;
        if(isSelectedToday){
            if (currentMinutes >= 30) {
                currentHour++;
            }
            currentHour = String(currentHour).padStart(2, '0');
            const ampm = currentHour >= 12 ? 'pm' : 'am';
            const roundTime = `${String(currentHour % 12).padStart(2, '0') || 12}:${'00'}${ampm}`;
            for (let index = 0; index < slotArray.length; index++) {
                $('#appointment_time_ccr option[value="'+slotArray[index].toLowerCase()+'"]').addClass('hide');
                if(roundTime == slotArray[index]){
                    break;
                }
            }
        }
        if(selectedDate != ''){
            $.ajax({
                url: "{{ route('ccrCalendar.fetchScheduleDetailsOfCCR') }}",
                type: "GET",
                data: {
                    _token:_token,
                    appointment_date: selectedDate
                },
                success: function(response) {
                    if(response.status === true){
                        blockedTimeSlot = [];
                        response.data.forEach(function(schedule) {
                            //Finding and creating the blocked time slot for hiding in the time dropdown

                            if(schedule.appointment_end_time != '' && schedule.appointment_end_time != null){
                                var sEx = schedule.appointment_time_24.split(':');
                                var start = new Date();
                                start.setHours(sEx[0], sEx[1], sEx[2]);
                                var eEx = schedule.appointment_end_time.split(':');
                                var end = new Date();
                                end.setHours(eEx[0], eEx[1], eEx[2]);

                                var current = new Date(start);
                                while (current < end) {
                                    var formattedTime = current.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit',  hour12: true }).replace(' ','');
                                    blockedTimeSlot.push(formattedTime);
                                    current.setHours(current.getHours() + 1);
                                }
                            }else{
                                blockedTimeSlot.push(schedule.appointment_time);
                            }
                            console.log(blockedTimeSlot,'blockedTimeSlot');
                            blockedTimeSlot.forEach(function(itm) {
                                $('#appointment_time_ccr option[value="'+itm.toLowerCase()+'"]').addClass('hide');
                            });
                        });
                    }
                }
            });
        }
    }
    $('#branch_location').change(function() {
        if($(this).find('option[value="'+$(this).val()+'"]').attr('data-state')=='TX'){
            if($("#schedule_type option[value='CCR']").length <= 0){
                $("#schedule_type option").eq(4).before($("<option></option>").val("CCR").text("CCR"));
            }
        }else{
            $("#schedule_type option[value='CCR']").remove();
        }
    });


    $(document).ready(function() {
        var jsonObject;
        $("#ClientName").keyup(function(){
            if($(this).val() == ''){
                $('#branch_location').removeAttr('disabled');
                $('#schedule_next_form input[name="branch_location"]').remove();
            }
        });
        if($("#ClientName").val() != ''){
            let blvalue = $('#branch_location').val();
            $('<input type="hidden" name="branch_location" value="'+blvalue+'"/>').insertAfter($('#schedule_next_form #branch_location'));
        }
        $("#ProviderName").autocomplete({
            source: "{{ route('getScheduleProvider') }}",
            minLength: 2,
            select: function(event, ui) {
                $('#referred_by').val(ui.item.id);
            }
        });
        $("#ClientName").autocomplete({
            source: function(request, response) {
                $('#loadingGif').show();
                $.ajax({
                    url: "{{ route('getScheduleClient') }}",
                    data: { term: request.term },
                    success: function(data) {
                        $('#loadingGif').hide();
                        response(data);
                    },
                    error: function() {
                        $('#loadingGif').hide();
                    }
                });
            },
            minLength: 2,
            focus: function(event, ui) {
                return false;
            },
            select: function(event, ui) {
                $('#ClientName').val(ui.item.value);
                $('#patient_id').val(ui.item.id);
                $('#gp').val('');
                $('#gp_approved_by').empty().append(
                    $('<option>', {value: '', text: '-- Select One --'}),
                    <?php foreach ($approvedByList as $data) {?>
                            $('<option>', {value: '<?php echo addslashes($data); ?>', text: '<?php echo addslashes($data); ?>'}),
                    <?php }?>
                );
                if(ui.item.branch_name != ''){
                    $('#branch_location').val(ui.item.branch_name);
                        $('#branch_location').change();
                    $('#branch_location').attr('disabled','true');
                    $('<input type="hidden" name="branch_location" value="'+ui.item.branch_name+'"/>').insertAfter($('#schedule_next_form #branch_location'));
                }
                $("#payer").val(ui.item.payer);
                @if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class'))
                @canPermission('showscheduleclassform')
                    showTrachVentClassOption(ui.item.is_specialized);
                @endcanPermission
                @endif
                @if(LD::checkFeatureFlag("new_payrate_approval_process"))
                    $.ajax({
                        url: "{{route('getApprovedPayRatebyclientid')}}",
                        type: 'get',
                        data: {
                            client_id: ui.item.id,
                        },
                        success: function(response) {
                            jsonObject = $.parseJSON(response);
                        }
                    });
                @endif
                @if(LD::checkFeatureFlag("add-schedule-matching-the-nth-schedules") && $isEffectiveDateGreaterThanNow)
                    nthScheduleSelect.loadForClient(ui.item.id);
                @endif
            },
            change: function(event, ui) {
                if (!ui.item) {
                    $('#patient_id').val('');
                }
            }
        });
        var flag = false;
        $("#candidate_name").autocomplete({
            source: function(request, response) {
                $.getJSON("{{ route('getCandidateName') }}", {
                    status: $('input[name="status"]:checked').val(),
                    term: $('#candidate_name').val()
                }, response);
            },
            minLength: 2,
            select: function(event, ui) {
                flag = true;
                    $('#phone').val(ui.item.phone);
                $('#email').val(ui.item.email);
                $('#title option').removeAttr('selected').filter('[value="' + ui.item.title + '"]')
                    .attr('selected', true);
                if ($('input[name="status"]:checked').val() == 'current') {
                    $('#provider_id').val(ui.item.id);
                    $('#candidate_id').val('');
                } else {
                    $('#candidate_id').val(ui.item.id);
                    $('#provider_id').val('');
                }
                if(typeof ui.item.isIneligibleRehire !== "undefined" && ui.item.isIneligibleRehire == '1'){
                    $('<div class="row pt-2 ineligible-rehire-error"><div class="col-md-12"><div class="alert alert-danger" role="alert">Ineligible for rehire. Please contact the Advocacy Department for further instruction.</div></div></div>').insertAfter($("#candidate_name").parent().parent());
                   //$('#candidate_save').find('button').attr('disabled',true);
                }else{
                    $('.ineligible-rehire-error').remove();
                   //$('#candidate_save').find('button').removeAttr('disabled');
                }

                $('#schedulewarnning').remove();
                $('#recruiter_nth_schedule_ids').remove();
                @if (LD::checkFeatureFlag("add-schedule-validation"))
                if (ui.item.nthScheduleMsg != '') {
                    $('<div class="row pt-2" id="schedulewarnning"><div class="col-md-12"><div class="alert alert-warning" role="alert">' + ui.item.nthScheduleMsg + '</div></div></div>').insertAfter($("#candidate_name").parent().parent());
                    $('<input type="hidden" name="recruiter_nth_schedule_ids" id="recruiter_nth_schedule_ids" value="' + ui.item.nthSchedulesIds + '"/>').insertAfter($("#candidate_name"));
                }
                @endif

                $('#caddress').val(ui.item.address);
                $('#caddress2').val(ui.item.address2);
                $('#ccity').val(ui.item.city);
                $('#cstate').val(ui.item.state);
                $('#cpostalCode').val(ui.item.postalCode);
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
                $('#candidate_id').val('');
                $('#provider_id').val('');
            }
        });

        $('#datepicker').datepicker({
            onSelect: function(dateText) {
                checkingCCRTimeSlotAndSetting(this.value);
            }
        }).bind('click', function() {
            $('#ui-datepicker-div').appendTo($('#datepicker').parent());
        });

        $('#proposed_ns_date').datepicker().bind('click', function() {
            $('#ui-datepicker-div').appendTo($('#proposed_ns_date').parent());
        });
        $('#proposed_ns_time').timepicker({
            "step": 15,
            'forceRoundTime': true,
            'minTime': '06:00:00',
            'maxTime': '21:00:00'
        });

        $('#appointment_time').timepicker({
            "step": 15,
            'forceRoundTime': true,
            'minTime': '06:00:00',
            'maxTime': '21:00:00'
        });


        var clientData = "{{json_encode($client_data)}}";
        $('#outcome').change(function() {
            if ($(this).val() != '') {
                $('#commonfields').show();
            } else {
                $('#commonfields').hide();
            }

            for (var k in clientData) {
                $('#' + k).val(clientData[k]);
            }

            if ($(this).val() == 'Cancelled') {
                $('#ClientName').val('');
                $('#pay_rate').val('');
                $('#gp').val('');
                $('#gp_approved_by').val('');
            }

            if ($(this).val() == 'NC/NS' || $(this).val() == 'Pending Nurse' || $(this).val() ==
                'Pending Parent') {
                $('.attampted_to_contact_container').show();
                $('#candidate_note').show();
                $('#schedule_appointment').hide();
                $('#candidate_details').hide();
            } else {
                $('.attampted_to_contact_container').hide();
                $('#candidate_note').show();
                $('#schedule_appointment').show();
                $('#candidate_details').show();
            }
        });

        $('#schedule_type').change(function() {
            if ($(this).val() != '') {
                $('#datepicker').attr('required', 'required');
            } else {
                $('#datepicker').removeAttr('required');
            }

            if ($(this).val() == 'IPEA') {
                $('.showforipea').show();
            } else {
                $('.showforipea').hide();
            }

            if ($(this).val() == 'CCR' || $(this).val() == 'School Set Up') {
                $('.hideforccrschool').hide();
            } else {
                $('.hideforccrschool').show();
            }
			if($(this).val() == 'PEPI' || $(this).val() == 'WEPI' || $(this).val() == 'Training Shift' || $(this).val() == 'Candidate Start'){
                $('.ccrbefornext').show();
            }else{
                $('.ccrbefornext').hide();
            }

            if ($(this).val() == 'Needs to be Scheduled by RA') {
                $('.hideforneedstobeschedulebyra').hide();
            } else {
                $('.hideforneedstobeschedulebyra').show();
            }

            if ($(this).val() == 'IPEA') {
                setBranchAutoSelect();
            }
            if ($(this).val() == 'CCR') {
                $('.crr-notes').show();
                $('.appointment-time-ccr').show();
                $('#appointment_time_ccr').removeAttr('disabled');
                $('#appointment_time').attr('disabled','true');
                $('#appointment_time').parent().parent().hide();
                $('.ccrScheduleFields').show();
                $("#datepicker").datepicker('setDate', null);
                $("#datepicker").datepicker('option', 'beforeShowDay', $.datepicker.noWeekends);
            }else{
                $('.crr-notes').hide();
                $('.appointment-time-ccr').hide();
                $('#appointment_time_ccr').attr('disabled','true');
                $('#appointment_time').removeAttr('disabled');
                $('#appointment_time').parent().parent().show();
                $('.ccrScheduleFields').hide();
                $("#datepicker").datepicker('option', 'beforeShowDay', false);
            }
        });
        $('#schedule_type').change();

        $('#location_id').change(function() {
            getTrachVentScheduleClass();
        });
        $('#trach_vent_class_type').change(function() {
            if($('#location_id :selected').val() != '') {
                getTrachVentScheduleClass();
            }
        });

        @if(LD::checkFeatureFlag('P1-3035-change-date-sort-trach-vent'))
        $('#class_type').change(function() {
            if($('#location_id :selected').val() != '') {
                getTrachVentScheduleClass();
            }
        });
        @endif

        $('input[name="status"]').change(function(){
            if (
                $('input[name="status"]:checked').val() == 'new' ||
                $('input[name="status"]:checked').val() == 'prn'
            ) {
                $('#caddress').removeAttr('disabled');
                $('#caddress2').removeAttr('disabled');
                $('#ccity').removeAttr('disabled');
                $('#cstate').removeAttr('disabled');
                $('#cpostalCode').removeAttr('disabled');
            } else {
                $('#caddress').val('').attr('disabled', 'disabled');
                $('#caddress2').val('').attr('disabled', 'disabled');
                $('#ccity').val('').attr('disabled', 'disabled');
                $('#cstate').val('').attr('disabled', 'disabled');
                $('#cpostalCode').val('').attr('disabled', 'disabled');
            }
        });
        $('input[name="status"]').change();
        $("#gp").blur(function() {
            <?php if(LD::checkFeatureFlag('new_payrate_approval_process'))  {?>
                let gp = $(this).val();
                gp = gp / 100;
                var filteredValues = [];
                $.each(jsonObject, function(key, value) {
                    if (value !== null && parseFloat(value) <= parseFloat(gp)) {
                        filteredValues[key] = value;
                    }
                });
                var jalevelarray = JSON.parse('<?php echo json_encode($levelarray); ?>');
                var mergedArray = [];

                for (var key in filteredValues) {
                if (jalevelarray.hasOwnProperty(key)) {
                    if (jalevelarray[key].length > 0) {
                    mergedArray = mergedArray.concat(jalevelarray[key]);
                    }
                }
                }
                if(mergedArray.length > 0){
                    $('#gp_approved_by').empty().append(
                        $('<option>', {value: '', text: '-- Select One --'})
                    );
                    $.each(mergedArray, function(index, data) {
                        $('#gp_approved_by').append(
                            $('<option>', {value: data, text: data})
                        );
                    });
                }else{
                    $('#gp_approved_by').empty().append(
                        $('<option>', {value: '', text: '-- Select One --'}),
                        <?php foreach ($approvedByList as $data) {?>
                            $('<option>', {value: '<?php echo $data; ?>', text: '<?php echo $data; ?>'}),
                        <?php }?>
                    );
                }
            <?php }?>
        });
    });

    $(document).ready(function() {
        $('#schedule_type').on('change', function () {
            if($('#schedule_next_form #schedule_type :selected').val() != 'IPEA') {
                $('.showforipeabranchnote').hide();
                $(".ipeabranchnote").html('');
            }
        });
        $('#schedule_next_form #ipea_location_id').change(function() {
            getIpeaBranchNotes();
        });
        $('#schedule_type').on('change', function () {
            if($('#schedule_next_form #schedule_type :selected').val() != 'IPEA') {
                $('.ipeaUpcomingEvents').hide();
                $(".ipeaUpcomingEvents ul").html('');
            }
        });

        $('#schedule_next_form #ipea_location_id').change(function() {
            if($('#schedule_next_form #schedule_type :selected').val() == 'IPEA') {
                getIpeaBranchEvents();
            }else{
                $('.ipeaUpcomingEvents').hide();
            }
        });
    });
    function setBranchAutoSelect() {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('getipeanearestbranch')}}",
            type: 'post',
            data: {
                address1: $('#schedule_next_form #caddress').val(),
                address2: $('#schedule_next_form #caddress2').val(),
                city: $('#schedule_next_form #ccity').val(),
                state: $('#schedule_next_form #cstate').val(),
                country: 'USA',
                zipcode: $('#schedule_next_form #cpostalCode').val(),
                _token: _token
            },
            success: function(response) {
                if (response.status) {
                    $('#schedule_appointment #ipea_location_id').val(response.id).change();
                }
            }
        });
    }
    function getIpeaBranchNotes() {
        $('.showforipeabranchnote').hide();
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('getipeabranchnotes')}}",
            type: 'post',
            data: {
                ipea_location_id: $('#schedule_next_form #ipea_location_id :selected').val(),
                _token: _token
            },
            success: function(response) {
                if(response.notes != '') {
                    $('.showforipeabranchnote').show();
                    $(".ipeabranchnote").html(response.notes);
                }

            }
        });
    }
    function getIpeaBranchEvents() {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('ipea.getBranchUpcomingEvents')}}",
            type: 'get',
            data: {
                ipea_location_id: $('#schedule_next_form #ipea_location_id :selected').val(),
                _token: _token
            },
            success: function(response) {
                console.log(response,'response')
                if(response.status) {
                    ulStr = '';
                    if(response.events.length > 0){
                        $('.ipeaUpcomingEvents').show();
                    }else{
                        $('.ipeaUpcomingEvents').hide();
                    }
                    response.events.forEach(function(event) {
                        ulStr += '<li><strong>'+event.appointment_date+'</strong><p>'+event.title+'</p>';
                        if(event.description != null){
                            ulStr += '<p>'+event.description+'</p></li>';
                        }
                        ulStr += '</li>';
                    });
                    $(".ipeaUpcomingEvents ul").html(ulStr);
                }else{
                    console.log('Error: ',msg);
                }
            }
        });
    }
    function getTrachVentScheduleClass() {
        if($('#location_id :selected').val() === '') {
            $("#occurrence_id").html('<option value="">-- Select One --</option>');
            return false
        }
        $.ajax({
            url: "{{route('gettrachventscheduleclass')}}",
            type: 'get',
            @if(LD::checkFeatureFlag('P1-3035-change-date-sort-trach-vent'))
            data: {
                trach_vent_class_type: $('#trach_vent_class_type :selected').val(),
                location_id:$('#location_id :selected').val(),
                class_type:$('#class_type :selected').val()
            },
            @else
            data: {
                trach_vent_class_type: $('#trach_vent_class_type :selected').val(),
                location_id:$('#location_id :selected').val()
            },
            @endif
            success: function(response) {
                $("#occurrence_id").html('<option value="">-- Select One --</option>');
                $.each(response, function( index, value ) {
                    $("#occurrence_id").append('<option value="' + value.id + '">' + value.class_date + ' ' +
                        value.class_time + (value.end_time ? ' - ' + value.end_time : '') + '</option>')
                });
            }
        });
    }
    @if (LD::checkFeatureFlag('P1-3035-change-date-sort-trach-vent'))
        $(document).on('change','#class_type',function(){
            if($(this).val() == 'Request Class'){
                $('#trach_vent_requested_date_time').parent().parent().removeClass('hide');
                $('#occurrence_id').parent().parent().addClass('hide');
            }else{
                $('#trach_vent_requested_date_time').parent().parent().addClass('hide');
                $('#occurrence_id').parent().parent().removeClass('hide');
            }
        });
    @else
        $(document).on('change','#occurrence_id',function(){
            if($(this).val() == 'Request Class'){
                $('#trach_vent_requested_date_time').parent().parent().removeClass('hide');
            }else{
                $('#trach_vent_requested_date_time').parent().parent().addClass('hide');
            }
        });
    @endif
    @if (LD::checkFeatureFlag('P1-3035-change-date-sort-trach-vent'))
    function showTrachVentClassOption(is_specialized) {
        $('#trach_vent_class_request').show();
        $('#show_trach_vent').val(1);
        $('#specializedClientDiv').toggle(is_specialized == 1);
    }
    @else
    function showTrachVentClassOption(is_specialized) {
        $('#trach_vent_class_request').show();
        $('#specializedClientDiv').toggle(is_specialized == 1);
    }
    @endif
    function closeForm() {
        $('#schedule_next').modal('hide');
    }

    function setSchedule(button, saveandnew = 0) {

        @if (LD::checkFeatureFlag('add-schedule-validation'))
            if ($('#recruiter_nth_schedule_ids').length == 1 && $('#nth_schedule_ids option:selected').length > 0) {
                validateNthSchedules(button, saveandnew);
            } else {
                setScheduleNew(button, saveandnew);
            }
        @else
            setScheduleNew(button, saveandnew);
        @endif
    }

    function setScheduleNew(button, saveandnew = 0) {
        if ($("#schedule_next_form").valid() == false) {
            return false;
        }
        if (!window.scheduleNextSubmitLock.acquire()) {
            return false;
        }
        $("#error-msg-provider").addClass("hide");
        $("#error-msg-kantime-hours").addClass("hide");
        $("#error-msg-kantime-hours-link").addClass("hide").off("click");
        $("#error-msg-duplicate-submit").addClass("hide");
        console.log($("#schedule_next_form").serialize());
        $.ajax({
            url: "{{ route('saveScheduleNext') }}",
            type: "POST",
            data: $("#schedule_next_form").serialize(), //only input
            error: function(response) {
                window.scheduleNextSubmitLock.release();
                var data = response.responseJSON;

                if (response.status === 429 && data && data.error === 'duplicate_submit') {
                    $("#error-msg-duplicate-submit").text(data.message).removeClass("hide");

                    return;
                }

                if (response.status === 409 && data && data.error === 'kantime_hours_exceeded') {
                    $("#error-msg-kantime-hours-text").text(data.message);
                    if (data.existing_tracker_id) {
                        $("#error-msg-kantime-hours-link")
                            .removeClass("hide")
                            .on("click", function() {
                                $('.modal').modal('hide');
                                openRecruiterDetails(data.existing_tracker_id);
                            });
                    }
                    $("#error-msg-kantime-hours").removeClass("hide");
                }
            },
            success: function(response) {
                window.scheduleNextSubmitLock.release();

                if (response) {
                    $("#successBody").html(response.message);
                    if (response.success == 1) {
                        if (saveandnew == 0) {
                            $("#scheduleNextFormDiv").hide();
                            $("#scheduleNextSuccessDiv").show();
                            //$("#scheduleNextForm").modal('show');
                            $("#schedule_next_form")[0].reset();
                        } else {
                            var status = $('input[name="status"]:checked').val();
                                var phone = $('#phone').val();
                            var email = $('#email').val();
                            var title = $('#title').val();
                            var provider_id = $('#provider_id').val();
                            var candidate_name = $('#candidate_name').val();
                            var bilingual = $('#bilingual').is(':checked');
                            $("#schedule_next_form")[0].reset();
                            $("input[name=status][value='" + status + "']").prop("checked", true);
                                $('#phone').val(phone);
                            $('#email').val(email);
                            $('#title').val(title);
                            $('#provider_id').val(provider_id);
                            $('#candidate_id').val(response.candidate_id);
                            $('#candidate_name').val(candidate_name);
                            $("#bilingual").prop("checked", bilingual);
                            $('#patient_id').val('');
                        }
                        $('#candidateMatchCheck').modal('hide');
                    } else {
                        @if(LD::checkFeatureFlag('ccr-calendar-double-booking'))
                            if(response.success == '-1'){
                                $('#schedule_appointment fieldset').prepend('<div class="alert alert-danger">'+response.msg+'</div>');
                                $('#candidateMatchCheck').modal('hide');
                                return 0;
                            }
                        @endif
                        @if(LD::checkFeatureFlag('add-clinician-error-handling-status-current'))
                            var errorCode = response.error;
                            if(errorCode==10) {
                                $("#error-msg-provider").removeClass("hide");
                            }
                        @endif;
                        $("#scheduleNextFormDiv").show();
                        $("#scheduleNextSuccessDiv").hide();
                    }

                }
            },
        });
    }
    </script>

    <script type="text/javascript">
    function clerecandidatedata() {
        $('#candidate_name').val('');
            $('#candidate_id').val('');
            $('#provider_id').val('');
            $('#title').val('');
            $('#phone').val('');
            $('#email').val('');
            $('#caddress').val('');
            $('#caddress2').val('');
            $('#ccity').val('');
            $('#cstate').val('');
            $('#cpostalCode').val('');
            $('#schedulewarnning').remove();
            $('.ineligible-rehire-error').remove();
    }
    $(document).ready(function() {
        $('#schedule_next').on('shown.bs.modal', function() {
            $("#scheduleNextFormDiv").show();
            $("#scheduleNextSuccessDiv").hide();


        });
        $('#status1').on('click', function() {
            clerecandidatedata();
        });
        $('#status2').on('click', function() {
            clerecandidatedata();
        });
    });
    function checkRedio(){
        var radioButtons = document.getElementsByName('status');
        for (let i = 0; i < radioButtons.length; i++) {
            if (radioButtons[i].checked) {
                var redioValue = radioButtons[i].value;
                if(redioValue === 'new'){
                    $('.newphone').show();
                    $('.oldphone').hide();
                    $('.oldphone input').prop('disabled', true);
                    $('.newphone input').prop('disabled', false);
                    $( "#homephone" ).rules( "add", {
                        required: true,
                    });
                }else if(redioValue === 'current'){
                    $('.oldphone').show();
                    $('.newphone').hide();
                    $('.newphone input').prop('disabled', true);
                    $('.oldphone input').prop('disabled', false);
                    $( "#homephone" ).rules( "remove" );
                }
            }
        }
    }
    </script>
    @if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class'))
    @canPermission('showscheduleclassform')
        <script type="text/javascript">
            showTrachVentClassOption({{ $client_data['isSpecialized'] ? 1 : 0 }});
        </script>
    @endcanPermission
    @endif
    @if(LD::checkFeatureFlag('prn-changes-add-clinician'))
    <script type="text/javascript">
        $(document).ready(function(){
            $('#candidate_type').change(function() {
                $('.prndisabled').find('input').removeAttr('disabled');
                $('.prndisabled').find('select').removeAttr('disabled');
                $('.prndisabled').show();

                $('.prnenabled').addClass('hide');
                $('.prnenabled').find('input').attr('disabled', true);

                $("#schedule_next_form #caddress").rules("remove", "required");
                $("#schedule_next_form #ccity").rules("remove", "required");
                $("#schedule_next_form #cstate").rules("remove", "required");
                $("#schedule_next_form #prn_branch").rules("remove", "required");
                $("#schedule_next_form #shift_type").rules("remove", "required");
                $("#schedule_next_form #shift_type").multiselect("disable");

                $('#schedule_next_form .error').removeClass('error');
                $('#schedulewarnning').remove();
                $("#candidate_name").autocomplete("enable");
                var val = $(this).val();
                if (val == 'new') {
                    $('#status1').prop('checked', true);
                    $('#status1').click();
                } else if (val == 'current') {
                    $('#status2').prop('checked', true);
                    $('#status2').click();
                } else if (val == 'prn') {
                    @if (!LD::checkFeatureFlag('add-clinician-form-active-coverage-autopopulate'))
                    $("#candidate_name").autocomplete("disable");
                    @endif
                    $('#status3').prop('checked', true);
                    $('#status3').click();
                    $('.prndisabled').find('input').attr('disabled', true);
                    $('.prndisabled').find('select').attr('disabled', true);
                    $('.prndisabled').hide();

                    $('.prnenabled').removeClass('hide');
                    $('.prnenabled').find('select').removeAttr('disabled');

                    $("#schedule_next_form #caddress").rules("add", "required");
                    $("#schedule_next_form #ccity").rules("add", "required");
                    $("#schedule_next_form #cstate").rules("add", "required");
                    $("#schedule_next_form #prn_branch").rules("add", "required");
                    $("#schedule_next_form #shift_type").rules("add", "required");

                    $("#schedule_next_form #shift_type").multiselect("enable");
                    $("#schedule_next_form #shift_type").multiselect("rebuild");
                }
                $('input[name="status"]').change();
            });

            $('#schedule_next_form #shift_type').multiselect({
                includeSelectAllOption: true
            });

            $('#status3').on('click', function() {
                clerecandidatedata();
            });

            $('#prn_branch').change(function() {
                $('#patient_id').val($(this).find('option:selected').val());
                $('#branch_location').val($(this).find('option:selected').data('branch'));
            });
        })
    </script>
    <style>
        .prnenabled .multiselect-native-select {
            display: block;
            width: 100%;
        }
        .prnenabled .multiselect-native-select .btn-group {
            width: 100%;
        }
    </style>
    @endif

@if (LD::checkFeatureFlag('dashboard-nthalerts-inquiry-bucket'))
@if(isset($isNthLeadInquiry) && $isNthLeadInquiry == 1)
<script>
    $(document).on('shown.bs.modal', '#schedule_next', function () {
        $("#schedule_next_form #candidate_type").val('current').trigger('change');
        $('#status2').prop('checked', true);
        $('#candidate_name').val('{{ $client_data['candidate_name'] }}');
        $('#provider_id').val('{{ $client_data['aoc_provider_id'] }}');
        $('#phone').val('{{ $client_data['phone'] }}');
        $('#email').val('{{ $client_data['email'] }}');
    });
</script>
@endif
@endif
