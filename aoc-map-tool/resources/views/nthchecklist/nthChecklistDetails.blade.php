<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\NTHChecklist;
    $bucketArr = H::getHelper('NTHChecklist')->getBuckets();
    $steps = [];
    foreach (config('constant')['nthCheckListSteps'] as $key => $value) {
        $steps[] = ['value'=>$key,'text'=>$value];
    }
    $nthCheckListMainSteps = config('constant')['nthCheckListMainSteps'];
    $nthCheckListMainStepsLabel = config('constant')['nthCheckListMainStepsLabel'];
    $theSaleLength = '';
    if(strlen($patientsDetails->CF_TheSale)<255) {
        $theSaleLength = ' (' . strlen($patientsDetails->CF_TheSale) . ' characters)';
    }

    switch ($needToHireChecklistDetails->webVisibility) {
    case 'published':
        $needToHireChecklistDetails->webVisibility = '✅ Published – ' . H::getFormatedLongDateTime($needToHireChecklistDetails->updated_at);
        break;
    case 'not_published':
        $needToHireChecklistDetails->webVisibility = '❌ Not Published – Missing Required Fields';
        break;
    case 'error':
        $needToHireChecklistDetails->webVisibility = '⚠️ Error – Contact Support';
        break;
    case 'removed':
        $needToHireChecklistDetails->webVisibility = '⛔ Removed';
        break;
    default:
        $needToHireChecklistDetails->webVisibility = '⚠️ Unknown Status';
        break;
}
?>

<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>

<style>
.fa-check-square-o {
    font-size: 26px !important;
}

.fst-normal {
    font-style: normal !important;
}
</style>

<x-modal>
    <x-slot name="id">nthChecklistDetails</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">NTH Checklist Details /
        {{(isset($patientsDetails->Patient_FirstName) ? $patientsDetails->Patient_FirstName : '').' '.(isset($patientsDetails->Patient_LastName) ? $patientsDetails->Patient_LastName : '')}}
    </x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a href="javascript:void(0);" onclick="showStepForm()"><i class="fa fa-check-square-o"></i></a>
            @if (LD::checkFeatureFlag('add-profile-icon-and-age-and-schedule-sortnode-on-NTH-Checklist-detail-page'))
            <a href="javascript:void(0);" onclick="getClientDetails('{{$patientsDetails->id}}')"><i class="fa fa-user"></i></a>
            @endif
            <!-- <a href=""><i class="fa fa-pencil"></i></a> -->
        </div>
        <div class="col-md-6 text-right pull-right">
            <span class='editable-select btn btn-primary' data-emptytext='Click to Change Current Step'
                data-type='select'
                data-source='<?php echo htmlspecialchars(json_encode($steps),ENT_QUOTES, 'UTF-8');?>'
                data-value="{{ config('constant')['nthCheckListSteps'][$needToHireChecklistDetails->CurrentStep] }}" data-url='{{route("editable")}}'
                data-pk="{{ $needToHireChecklistDetails->id }}" data-name='CurrentStep' data-table='needToHireChecklist'>
                {{ config('constant')['nthCheckListSteps'][$needToHireChecklistDetails->CurrentStep] }}
            </span>
        </div>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="<?php echo ($hideactivity == 'true') ? 'col-md-12' : 'col-md-9'; ?> tracker-details">
                <div class="col-md-12 d-flex p-0">
                    <div class="col-md-6 p-0">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Client Care Coordinator: </dt>
                            <dd class="col-sm-8 text-left">
                            {{H::getHelper('ConstantHelper')->
                                    getUserNameById($needToHireAccounts->ClientCareCoordinator)}}
                            </dd>
                        </dl>
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Recruiter: </dt>
                            <dd class="col-sm-8 text-left">
                                <span class='editable-select' data-emptytext='Click to Recruiter' data-type='select'
                                    data-source="{{json_encode(H::getHelper('ConstantHelper')->getRecruitersWithIds())}}"
                                    data-url='{{route("editable")}}' data-pk="{{ $needToHireAccounts->id }}"
                                    data-value='{{$needToHireAccounts->Recruiter}}' data-name='Recruiter'
                                    data-table='needToHireAccounts'>
                                    {{$needToHireAccounts->Recruiter}}
                                </span>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6 p-0">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Intake Coordinator: </dt>
                            <dd class='col-sm-8 text-left'>
                            {{H::getHelper('ConstantHelper')->
                                    getUserNameById($needToHireAccounts->IntakeCoordinator)}}
                            </dd>
                        </dl>
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">BD Rep: </dt>
                            <dd class="col-sm-8 text-left"> {{$needToHireAccounts->BD_Rep}} </dd>
                        </dl>
                    </div>
                </div>
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client</h5>
                            <div class="detail-section">
                                <h5>
                                    <?php $name='';
                                             if(isset($patientsDetails->Patient_FirstName)) {
                                                $name.=$patientsDetails->Patient_FirstName;
                                             }
                                             if(isset($patientsDetails->Patient_MiddleName)) {
                                                $name.=" ".$patientsDetails->Patient_MiddleName;
                                             }
                                             if(isset($patientsDetails->Patient_LastName)) {
                                                $name.=" ".$patientsDetails->Patient_LastName;
                                             }
                                             echo trim($name);
                                            ?>
                                </h5>
                                <p class="card-text">
                                    {{isset($needToHireAccounts->ClientID) ? $needToHireAccounts->ClientID : ""}}</p>
                                <p class="card-text">
                                    {{isset($patientsDetails->Patient_City) ? $patientsDetails->Patient_City : ""}} {{isset($patientsDetails->Patient_State) ? $patientsDetails->Patient_State : ""}}</p>

                                <p class="card-text">
                                    {{isset($patientsDetails->Patient_Phone) ? $patientsDetails->Patient_Phone : ""}}
                                </p>
                                <p class="card-text">
                                    <span id="client-card-mobile">
                                        {{isset($patientsDetails->Patient_MobilPhone) && $patientsDetails->Patient_MobilPhone != '' ? $patientsDetails->Patient_MobilPhone.' (Mobile)' : ""}}
                                    </span>
                                </p>
                                <p class="card-text">
                                    {{isset($patientsDetails->Patient_Address1) ? $patientsDetails->Patient_Address1 : ""}},{{isset($patientsDetails->Patient_Address2) ? $patientsDetails->Patient_Address2 : ""}}
                                </p>
                                <p class="card-text">
                                    {{isset($patientsDetails->Patient_City) ? $patientsDetails->Patient_City : ""}}
                                    {{isset($patientsDetails->Patient_State) ? $patientsDetails->Patient_State : ""}}
                                    {{isset($patientsDetails->Patient_Zip) ? $patientsDetails->Patient_Zip : ""}}<br>
                                    @if (LD::checkFeatureFlag('add-profile-icon-and-age-and-schedule-sortnode-on-NTH-Checklist-detail-page'))
                                    @php
                                        $dob = isset($patientsDetails->Patient_DOB) ? $patientsDetails->Patient_DOB : '';
                                        $age = '';
                                        if($dob){
                                            $patientDOB = new DateTime($dob);
                                            $currentDate = new DateTime();
                                            $ageInterval = $patientDOB->diff($currentDate);
                                            $age = $ageInterval->y;
                                        }
                                    @endphp
                                    Age: {{!empty($age) ? $age.' Years' : ""}}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="card border-0">
                        <div class="card-body p-0">
                            <h5 class="card-title bg-blue m-0 p-3 text-center text-white">NTH Ticket Details</h5>
                            <div class="p-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <div><span class="text-secondary">NTH Status:</span> {{$needToHireAccounts->Status}}</div>
                                    <div><span class="text-secondary">Web visibility:</span> {!! $needToHireChecklistDetails->webVisibility !!}</div>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <div><span class="text-secondary">Date added to NTH:</span> {{H::getFormatedDate($needToHireAccounts->created_at)}}</div>
                                    <div><span class="text-secondary">Days on NTH:</span> {{$needToHireAccounts->daysInNTH}}</div>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <div><span class="text-secondary">Services Needed:</span> {{$needToHireAccounts->ServicesNeeded}}</div>
                                    <div><span class="text-secondary">NTH Hours:</span> {{$totalShiftHours}}</div>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <div><span class="text-secondary">Payor:</span> {{$patientsDetails->Payer}}</div>
                                </div>
                                @if(LD::checkFeatureFlag('add-profile-icon-and-age-and-schedule-sortnode-on-NTH-Checklist-detail-page'))
                                    <div class="mb-2"><span class="text-secondary">Client Schedule:</span> {{H::getHelper('NTHChecklist')->getNthShiftDescription($needToHireAccounts->id)}}</div>
                                @endif
                                <div class="mb-2"><span class="text-secondary">NTH Notes:</span> {{$patientsDetails->NTHNotes}}</div>
                            </div>
                        </div>
                    </div>
                </div>
                @if (LD::checkFeatureFlag('client-profile-icon-nth-checklist'))
                <div class="col-md-12 p-0 mt-3 mb-3">
                <?php if(!empty($needToHireChecklistDetails)) {
                    $mediaConsent = 'Not on file';
                    if(strpos($patientsDetails->PreferenceList,'Media Consent Form on File')!==false) {
                        $mediaConsent = 'On File';
                    }
                    ?>
                    <section class="nthrecords-section">
                        <div class="col-md-12 p-0 nthrecords-log">
                            <div class="nthrecords-container col-md-12">
                                <div class="tracker-section  col-md-12 clear p-0   pt-3 m-0">
                                    <div class="schedule-container clear m-0">
                                        <div class="comment-heading">
                                            <div class="nthrecords-comment-title col-md-3 p-0 pull-left">NTH Record&nbsp;<a href="javascript:void(0)" onclick="addNTHForm('{{$needToHireChecklistDetails->aoc_nthaccounts_id}}')"><i class="fa fa-edit ml-2" aria-hidden="true"></i></a></div>
                                            <div class="comment-time nthrecords-datetime-history col-md-9 p-0 pull-left">
                                                Date Added: {{H::getFormatedDate($needToHireChecklistDetails->created_at)}}&nbsp;|&nbsp;Days on NTH: {{$needToHireAccounts->daysInNTH}}&nbsp;|&nbsp;Created Reason:{{$needToHireAccounts->CreatedReason}}
                                            </div>
                                        </div>
                                        <div class="schedule-box clear m-0">
                                            <div class="nthschedule-content-container">
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        @if($needToHireAccounts->Status != '')
                                                        <div class="row">
                                                            <div class="col-md-12"><span class="tag">{{$needToHireAccounts->Status}}</span></div>
                                                        </div>
                                                        @endif
                                                        <div class="row">
                                                            <div class="col-md-12">Service Needed: {{$needToHireAccounts->ServicesNeeded}}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">Schedule Description: {{$needToHireAccounts->ScheduleText}}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">Skills Needed: {{$needToHireAccounts->skills}}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">NTH Hours: {{$totalShiftHours}}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">Media Consent: {{$mediaConsent}}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">Last Social Media Post:</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="row">
                                                            <div class="col-md-12">Client Preferences<br> {!! $patientsDetails->clientPreference !!}</div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-12">Clinician Preferences<br> {!! $patientsDetails->clinicianPreference !!}</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </section>
                <?php } ?>
                </div>
                @endif
                <div class="col-md-12 p-0 mt-3 mb-3">
                    <div class="card">
                    <label for="note">The Sale:{{ $theSaleLength }}</label>
                        <div class="card-body p-3">
                        <span class="att-value editable-textarea" data-inputclass="textarea-size"
                                data-emptytext='Click to add sales notes' data-type='textarea'
                                data-url='{{route("editable")}}' data-pk="{{ $patientsDetails->id }}"
                                data-name='CF_TheSale' data-value="{{ $patientsDetails->CF_TheSale }}"
                                data-table='patientsDetails'>{{$patientsDetails->CF_TheSale}}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-12 p-0 mt-3">
                    <table class="table" aria-describedby="">
                        <tr>
                            <th>NTH Checklist Step</th>
                            <th>Date Started</th>
                            <th>Last Activity Date</th>
                            <th>Last 7 Days Number</th>
                            <th>Number</th>
                        </tr>
                        @php
                        $i=1;
                        @endphp
                        @foreach($bucketArr as $k => $v)

                        <tr>
                            <td>{{$i}}. {{$v['subheading']}}</td>
                            <td>{{ isset($v['start']) ? H::getFormatedDate($needToHireChecklistDetails->{$v['start']}) : "" }}
                            </td>
                            <td>{{ isset($v['work']) ? H::getFormatedDate($needToHireChecklistDetails->{$v['work']}) : "" }}
                            </td>
                            <td>
                                <?php
                                $sevenDaysCount = $sevenDaysCountNew ='';
                                if(isset($v['number'])) {
                                    $sevenDaysCount = H::getHelper('NTHChecklist')->getLast7DaysNumber($v['number'],$needToHireChecklistDetails->id);
                                    if($k == 'social_media') {
                                        $sevenDaysCountNew = H::getHelper('NTHChecklist')->getLast7DaysNumber($v['number'],$needToHireChecklistDetails->id,'misc3');
                                    }
                                    if( $sevenDaysCountNew != '') {
                                        $sevenDaysCount = $sevenDaysCount." / ".$sevenDaysCountNew;
                                    }
                                    echo  $sevenDaysCount;
                                }
                                ?>
                            </td>
                            <td>
                                <?php
                                $totalDaysCount = $totalDaysCountNew ='';
                                if(isset($v['number']) && $needToHireChecklistDetails->{$v['number']} != 0) {
                                    $totalDaysCount = $needToHireChecklistDetails->{$v['number']};
                                    if($k == 'social_media') {
                                        $totalDaysCountNew = $needToHireChecklistDetails->SocialMediaPostsINT;
                                    }
                                    if( $totalDaysCountNew != '') {
                                        $totalDaysCount = $totalDaysCount. " / ".$totalDaysCountNew;
                                    }
                                    echo  $totalDaysCount;
                                }
                                ?>
                            </td>
                        </tr>
                        @php
                        $i++;
                        @endphp
                        @endforeach
                    </table>
                </div>
            </div>
            @if($hideactivity == 'false')
            <div class="col-md-3 d-flex p-0">
                <x-activity-log>

                </x-activity-log>
            </div>
            @endif
        </div>
    </x-slot>
    <x-slot name="footer">
    </x-slot>
</x-modal>
<x-modal>
    <x-slot name="id">updateNTHStep</x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="title">Update NTH step</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="modal-body" id="nthStepSuccessDiv" style="display: none">
            <div id="successBody"></div>
            <div class="row pt-2">
                <div class="col-md-7 text-right">
                    <button type="button" onclick="closeForm()" class="btn btn-primary">Close</button>
                </div>
            </div>
        </div>
        <div class="modal-body" id="nthStepsFormDiv">
            <form method="post" autocomplete="off" id="nth_steps_form" class="scaffold-form" enctype="multipart/form-data">
                @csrf
                <input type="hidden"  name="type" id="type"/>
                <div class="row pt-2">
                    <label for="step" class="col-md-4">NTH Step:</label>
                    <select name="step" id="step" class="col-md-6">
                    <option value="">Select Step</option>
                    <?php foreach ($nthCheckListMainSteps as $key => $value) {
                        echo '<option value="'.$key.'">'.$value.'</option>';
                        } ?>
                    </select>
                </div>
                <div class="row pt-2" id="div_step_count" style="display: none;">
                    <label for="no_of_nurses_contacted" id="label_step_count" class="col-md-4"></label>
                    <input type="text" name="no_of_nurses_contacted" id="no_of_nurses_contacted" class="col-md-6 pl-1"/>
                </div>
                <div class="row pt-2" id="social_media_extra_field" style="display: none;">
                    <label for="no_of_post_made" class="col-md-4">Number of posts made</label>
                    <input type="text" name="no_of_post_made" id="no_of_post_made" class="col-md-6 pl-1"/>
                </div>
                @if(LD::checkFeatureFlag('escalated-content-fields-for-text-blast'))
                <div class="text_blast_extra_field" style="display: none;">
                <div class="row pt-2">
                    @if($needToHireChecklistDetails->TextBlastEscalatedRate != NULL)
                    <label for="TextBlastEscalatedRate"  class="col-md-4 text-right">&nbsp;</label><label class="col-md-8  pl-0 font-italic text-left">Previous Rate: {{ $needToHireChecklistDetails->TextBlastEscalatedRate}}</label>
                    @endif
                </div>
                <div class="row">
                    <label for="TextBlastEscalatedRate"  class="col-md-4 text-right">Escalated Rate</label>
                    <input type="text" name="TextBlastEscalatedRate" id="TextBlastEscalatedRate" class="col-md-6 pl-1"/>
                </div>
                @if(LD::checkFeatureFlag('add-escalated-margin-field-to-nth-text-blast-widget'))
                    <div class="row pt-2">
                        <label for="TextBlastEscalatedMargin"  class="col-md-4 text-right">Escalated Margin</label>
                        <input type="text" name="TextBlastEscalatedMargin" id="TextBlastEscalatedMargin" class="col-md-6 pl-1"/>
                    </div>
                @endif
                <div class="row pt-2">
                    <label for="TextBlastContent"  class="col-md-4 text-right">Content</label>
                    <textarea name="TextBlastContent" id="TextBlastContent" class="col-md-6 pl-1"></textarea>
                </div>
                </div>
                @endif
                <div id="candidate_save" class="row pt-2">
                    <div class="col-md-7 text-right">
                        <button type="button" onclick="setSchedule(this)" id="setScheduleBtn" class="btn btn-primary">Submit</button>
                    </div>
                </div>
                <div id="socialmediasponsored" class="row pt-2" style="display: none;">
                    <div class="col-md-7 text-right">
                        <button type="button" onclick="showNthSocialMediaForm('{{ $needToHireAccounts->id }}', this)" class="btn btn-primary">Open Request Detail</button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script>
function closeForm() {
    $('#updateNTHStep').modal('hide');
}
function reloadNthHistory(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('nth.nthHistory')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $('.activity-log').html(data);
        }
    });
}
function showStepForm() {
    $('#updateNTHStep').modal('show');
}
$(document).ready(function() {
    reloadNthHistory(['<?php echo $needToHireChecklistDetails->id; ?>','<?php echo $needToHireAccounts->id; ?>']);
    var nthCheckListMainStepsLabel = <?php echo json_encode($nthCheckListMainStepsLabel);?>;

    var needToHireChecklistId = <?php echo $needToHireChecklistDetails->id ;?>;
    $('#step').change(function() {
        $('#div_step_count').hide();
        $('#candidate_save').show();
        $('#socialmediasponsored').hide();
        $('#social_media_extra_field').hide();
        $('.text_blast_extra_field').hide();
        $('#no_of_nurses_contacted').val('');
        if(this.value != '') {
            $( ".nthids" ).remove();
            let thisArry = nthCheckListMainStepsLabel[this.value];
            $('#div_step_count').show();
            $('#label_step_count').html(thisArry);
            $('#nth_steps_form #type').val(this.value);
            $('#nth_steps_form').append('<input type="hidden" class="nthids" name="ids_'+this.value+'" id="ids_'+this.value+'" value="'+needToHireChecklistId+'"/>');
            if(this.value == 'social_media') {
                $('#social_media_extra_field').show();
                $('#nth_steps_form').validate().settings.rules.no_of_nurses_contacted = {onlyOne: ["no_of_post_made"]};
                $('#nth_steps_form').validate().settings.rules.no_of_post_made = {onlyOne: ["no_of_nurses_contacted"], digits: true};
            } else if (this.value == 'social_media_sponsored'){
                $('#div_step_count').hide();
                $('#social_media_extra_field').hide();
                $('#candidate_save').hide();
                $('#socialmediasponsored').show();
            } else if(this.value == 'text_blast') {
                $('#nth_steps_form').validate().settings.rules.no_of_nurses_contacted = {required: true, digits: true};
                <?php if(LD::checkFeatureFlag('escalated-content-fields-for-text-blast')) { ?>
                    $('.text_blast_extra_field').show();
                    $('#nth_steps_form').validate().settings.rules.TextBlastEscalatedRate = {required: true, number: true};
                    $('#nth_steps_form').validate().settings.rules.TextBlastEscalatedMargin = {required: true, number: true};
                    $('#nth_steps_form').validate().settings.rules.TextBlastContent = {required: true};
                <?php } ?>
            }
            else {
                $('#nth_steps_form').validate().settings.rules.no_of_nurses_contacted = {required: true, digits: true};
            }
        }
    });

    setTimeout( function() { $("#no_of_nurses_contacted").focus(); }, 500 );

    $.validator.addMethod('onlyOne', function(value, element, param) {
        return ($(element).is(':filled') && $('[name="' + param[0] + '"]').is(':blank')) || (($('[name="' + param[0] + '"]').is(':filled')) && $(element).is(':blank')) || (($('[name="' + param[0] + '"]').is(':filled')) && $(element).is(':filled'));
    }, "Please fill out only one of these fields");

    $("#nth_steps_form").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "no_of_nurses_contacted": {
                required: true,
                digits: true
            },
            "step": {
                required: true
            },
        },
        errorPlacement: function(error, element) {}
    });
});
function setSchedule(button) {
    event.preventDefault();
    if ($("#nth_steps_form").valid() == false) {
        return false;
    }
    button.disabled = true;
    $.ajax({
        url: "{{ route('dashboard.saveNthSteps') }}",
        type: "POST",
        data: $("#nth_steps_form").serialize(),
        success: function(response) {
            button.disabled = false;
            if (response) {
                var displayMessage = response.message;
                if (response.success == 1) {
                    displayMessage = displayMessage + ' Refresh the page to view updated results';
                        $("#nthStepsFormDiv").hide();
                        $("#nthStepSuccessDiv").show();
                        $("#nth_steps_form")[0].reset();
                } else {
                    $("#nthStepsFormDiv").show();
                    $("#nthStepSuccessDiv").hide();
                }
                $("#successBody").html(displayMessage);
            }
        },
    });
}
</script>
