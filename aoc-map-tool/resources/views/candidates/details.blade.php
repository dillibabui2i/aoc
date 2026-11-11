<?php
use App\Helpers\H;
use App\Helpers\Candidates;
use App\Helpers\LD;
use App\Helpers\ConstantHelper;

$loggedInUser = H::getLoggedInUser();
$userCurrentRole = H::getHelper('ConstantHelper')->getCurrentRolesNameByUserId($loggedInUser['id']);
$workflows = config('constant')['appWorkflowState'];
$skills = config("constant")["skills"];
$newSkills = config('constant')['newSkills'];
$isTherapy = str_contains($type, 'therap');
$appWorkFlowStates = config('constant')['jobViteOutcomes'];

if ($isTherapy) {
    $appWorkFlowStates = config('constant')['jobviteTherapyOutcomes'];
    $skills = config("constant")["therapySkills"];
    $workflows = config('constant')['appWorkflowStateTherapist'];
}

?>

<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<style>
ul.att-cnt li {
    min-width: 20%;
}

.w90 {
    max-width: 90% !important;
}

.tags-cnt {
    margin-top: 10px;
    margin-bottom: 10px;
}
.extra-header-cnt a {
    margin-right: 10px;
}
.tabs-container .tab-content{
    max-height: inherit;
}

#candidatesDetails .multiselect-native-select .btn-group {
  max-width: 100% !important;
  width: 100% !important;
}

#candidatesDetails span.multiselect-native-select {
  position: relative;
  width: 100%;
}
</style>

<x-modal>
    <x-slot name="id">candidatesDetails</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">Candidate Details \ {{$candidatesDetails->fullName}}</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <!-- <a href=""><i class="fa fa-pencil"></i></a> -->
            @if($type !== 'therapist' || is_null($type))
                <a href="javascript:void(0);" data-id="{{$candidatesDetails->id}}" id="addScheduleFormFromCandidate"><i
                    class="fa fa-plus"></i></a>
                <a href="{{route('goToNeedToHireMap').'?candidateId='.$candidatesDetails->id}}"
                data-id="{{$candidatesDetails->id}}"><i class="fa fa-map-marker"></i></a>
            @endif
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$candidatesDetails->id}}','candidate','Candidate Notes Added')"><i
                    class="fa  fa-sticky-note-o"></i></a>
            <?php
                if (array_key_exists($candidatesDetails->appWorkflowState, $appWorkFlowStates)) {
                    ?>
                    <a href="javascript:void(0);" onclick="showCandidateNextStepForm('{{$candidatesDetails->id}}')"><i class="fa fa-calendar"></i></a>
                    <?php
                }
                ?>
            <?php if(LD::checkFeatureFlag('message-widget')){ ?>
                <a href="javascript:void(0);" data-candidate-name="{{$candidatesDetails->fullName}}" data-candidate-id="{{$candidatesDetails->id}}" data-candidate-phone="{{$candidatesDetails->mobile}}"  id="startConversationWithCandidateBtn" >
                    <i class="fa fa-comment-o"></i>
                </a>
            <?php } ?>
        </div>
        <div class="col-md-6 text-right pull-right">
            <?php if(in_array('Primary Admin',$userCurrentRole) || in_array('Super Admin',$userCurrentRole)){ ?>
                <span class='editable-select btn btn-primary' data-emptytext='Click to Change Candidate Workflow Status'
                    data-type='select'
                    data-source='<?php echo htmlspecialchars(json_encode(array_values($workflows)),ENT_QUOTES, 'UTF-8');?>'
                    data-value='{{ nl2br($candidatesDetails->appWorkflowState) }}' data-url='{{route("editable")}}'
                    data-pk="{{ $candidatesDetails->id }}" data-name='appWorkflowState' data-table='candidatesDetails'>
                    {{ nl2br($candidatesDetails->appWorkflowState) }}
                </span>
            <?php } else { ?>
                <span class='btn btn-primary'>{{ nl2br($candidatesDetails->appWorkflowState) }}</span>
            <?php } ?>
        </div>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-9 tracker-details">
                <div class="col-md-12 d-flex p-0">
                    <div class="col-md-6 p-0">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Recruiter: </dt>
                            <dd class="col-sm-8 text-left">
                                {{H::getHelper('ConstantHelper')->getUserNameById($candidatesDetails->user_id)}}
                            </dd>
                        </dl>
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Application Date: </dt>
                            <dd class="col-sm-8 text-left">{{H::getFormatedDate($candidatesDetails->appSentDate)}}</dd>
                        </dl>
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Application Time: </dt>
                            <dd class="col-sm-8 text-left">{{H::getFormatedTime($candidatesDetails->appSentDate)}}</dd>
                        </dl>
                    </div>
                    <div class="col-md-6 p-0">

                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Last Activity: </dt>
                            <dd class="col-sm-8 text-left">{{H::getFormatedDate($candidatesDetails->updated_at)}} {{H::getFormatedTime($candidatesDetails->updated_at)}}</dd>
                        </dl>
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Follow Up: </dt>
                            <dd class='col-sm-8 text-left'>
                                {{H::getFormatedDate($candidatesDetails->FollowUpDateTime)}}
                            </dd>
                        </dl>
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Nurse Graduation: </dt>
                            <dd class='col-sm-8 text-left'>
                                {{H::getFormatedDate($candidatesDetails->GraduationDate)}}
                            </dd>
                        </dl>
                    </div>
                </div>

                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Candidate</h5>
                            <div class="detail-section">
                                <div class="card-text">
                                    <div class="col-md-6 pull-left p-0">
                                        <h6>
                                            <span class="att-value editable-text" data-emptytext='Click to add full name'
                                                data-type='text' data-url='{{route("editable")}}'
                                                data-pk="{{ $candidatesDetails->id }}" data-name='firstName'
                                                data-table='candidatesDetails'>{{$candidatesDetails->firstName}}</span>
                                            <span class="att-value editable-text" data-emptytext='Click to add full name'
                                                data-type='text' data-url='{{route("editable")}}'
                                                data-pk="{{ $candidatesDetails->id }}" data-name='lastName'
                                                data-table='candidatesDetails'>{{$candidatesDetails->lastName}}</span>
                                        </h6>
                                    </div>
                                    <div class="col-md-6 pull-left text-right">
                                        <button type="button" class="tag" onclick="getCandidateResume('<?php echo $candidatesDetails->id?>')">View Resume</button>
                                    </div>
                                </div>
                                <div class="tags-cnt" style="clear:both">
                                    @if(LD::checkFeatureFlag('change-att-to-aide-in-add-clinician-form') && !$isTherapy)
                                        <span
                                            class='editable-select {{$candidatesDetails->title != "" && in_array($candidatesDetails->title, $skills) ? "tag":""}}'
                                            data-emptytext="{{$candidatesDetails->title != '' && !in_array($candidatesDetails->title, $newSkills) ? 'Current skillset is :'.$candidatesDetails->skillset.' It is invalid click to add valid skillset' : 'Click to add skillset' }}"
                                            data-type='select'
                                            data-source='<?php echo json_encode($newSkills);?>'
                                            data-value="{{ in_array($candidatesDetails->title, $newSkills) ? $candidatesDetails->title : '' }}"
                                            data-url='{{route("editable")}}' data-pk="{{ $candidatesDetails->id }}"
                                            data-name='title' data-table='candidatesDetails'>
                                            {{ in_array($candidatesDetails->title, $newSkills) ? $candidatesDetails->title : '' }}
                                        </span>
                                    @else
                                        <span
                                            class='editable-select {{$candidatesDetails->title != "" && in_array($candidatesDetails->title, $skills) ? "tag":""}}'
                                            data-emptytext="{{$candidatesDetails->title != '' && !in_array($candidatesDetails->title, $skills) ? 'Current skillset is :'.$candidatesDetails->skillset.' It is invalid click to add valid skillset' : 'Click to add skillset' }}"
                                            data-type='select'
                                            data-source='<?php echo json_encode($skills);?>'
                                            data-value="{{ in_array($candidatesDetails->title, $skills) ? $candidatesDetails->title : '' }}"
                                            data-url='{{route("editable")}}' data-pk="{{ $candidatesDetails->id }}"
                                            data-name='title' data-table='candidatesDetails'>
                                            {{ in_array($candidatesDetails->title, $skills) ? $candidatesDetails->title : '' }}
                                        </span>
                                    @endif
                                </div>
                                <p class="card-text">
                                    <span class="att-value editable-text" data-emptytext='Click to add Home Phone'
                                        data-type='text' data-url='{{route("editable")}}'
                                        data-pk="{{ $candidatesDetails->id }}" data-name='homePhone'
                                        data-table='candidatesDetails'>{{$candidatesDetails->homePhone}}</span>
                                </p>
                                <p class="card-text">
                                    <span class="att-value editable-text" data-emptytext='Click to add mobile'
                                        data-type='text' data-url='{{route("editable")}}'
                                        data-pk="{{ $candidatesDetails->id }}" data-name='mobile'
                                        data-table='candidatesDetails'>{{$candidatesDetails->mobile}}</span>
                                        <?php if(LD::checkFeatureFlag('display-twilio-error-on-profile-page')){ ?>
                                            <?php if(!empty($twilioErrorData)) { ?>
                                            &nbsp;<span data-toggle="tooltip" style="vertical-align:text-bottom"
                                            title="(Error:{{ $twilioErrorData->error_code }}) {{ $twilioErrorData->error_message }}">
                                                <i class="fa fa-times" style="font-size:16px;color:red"></i></span>
                                            <?php } ?>
                                        <?php } ?>
                                </p>
                                <p class="card-text">
                                    <span class="att-value editable-text" data-emptytext='Click to add email'
                                        data-type='text' data-url='{{route("editable")}}'
                                        data-pk="{{ $candidatesDetails->id }}" data-name='email'
                                        data-table='candidatesDetails'>{{$candidatesDetails->email}}</span>
                                </p>
                                <ul class="d-flex att-cnt">
                                    <li>
                                        <span class="att-value editable-text" data-emptytext='Click to add Address'
                                            data-type='text' data-url='{{route("editable")}}'
                                            data-pk="{{ $candidatesDetails->id }}" data-name='address'
                                            data-table='candidatesDetails'>{{$candidatesDetails->address}}</span>
                                    </li>
                                    <li>
                                        <span class="att-value editable-text" data-emptytext='Click to add Address2'
                                            data-type='text' data-url='{{route("editable")}}'
                                            data-pk="{{ $candidatesDetails->id }}" data-name='address2'
                                            data-table='candidatesDetails'>{{$candidatesDetails->address2}}</span>
                                    </li>
                                    <li>
                                        <span class="att-value editable-text" data-emptytext='Click to add City'
                                            data-type='text' data-url='{{route("editable")}}'
                                            data-pk="{{ $candidatesDetails->id }}" data-name='city'
                                            data-table='candidatesDetails'>{{$candidatesDetails->city}}</span>
                                    </li>
                                    <li>
                                        <span class="editable-select" data-emptytext='Click to add State'
                                            data-url='{{route("editable")}}'
                                            data-mode="popup"
                                            data-type='select'
                                            data-source='<?php echo json_encode(config('constant')['states']);?>'
                                            data-pk="{{ $candidatesDetails->id }}" data-name='stateCode'
                                            data-value="{{ in_array($candidatesDetails->stateCode,config('constant')['states']) ? $candidatesDetails->stateCode : '' }}"
                                            data-table='candidatesDetails'>{{$candidatesDetails->stateCode}}</span>
                                    </li>
                                    <li>
                                        <span class="att-value editable-text" data-emptytext='Click to add Zip'
                                            data-type='number' data-url='{{route("editable")}}'
                                            data-pk="{{ $candidatesDetails->id }}" data-name='postalCode'
                                            data-table='candidatesDetails'>{{$candidatesDetails->postalCode}}</span>
                                    </li>

                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Requisition</h5>
                            <div class="detail-section">
                                @php
                                    $i=0;
                                @endphp
                                @foreach($jobDetails as $job)
                                    @if($i==0)
                                    <dl class="col-md-12 p-0 d-flex">
                                        <dt class="col-sm-5 p-0 text-left">Requisition Title: </dt>
                                        <dd class="col-sm-7 text-left">
                                            {{isset($job->title) ? $job->title : ''}}
                                        </dd>
                                    </dl>
                                    <dl class="col-md-12 p-0 d-flex">
                                        <dt class="col-sm-5 p-0 text-left">Requisition Location: </dt>
                                        <dd class="col-sm-7 text-left">
                                            {{isset($job->locationCity) ? $job->locationCity : ''}}
                                        </dd>
                                    </dl>
                                    <dl class="col-md-12 p-0 d-flex">
                                        <dt class="col-sm-5 p-0 text-left">Requisition Category: </dt>
                                        <dd class="col-sm-7 text-left">
                                            {{isset($job->category) ? $job->category : ''}}
                                        </dd>
                                    </dl>
                                    <dt class="col-sm-5 p-0 text-left mt-3">Additional applications for: </dt>
                                    <ul>
                                    @else
                                    <li class="">
                                        {{$job->title}} ({{$job->category}}) in {{$job->branch}}, {{$job->state}}
                                    </li>
                                    @endif
                                    @php
                                        $i++;
                                    @endphp
                                @endforeach
                                    </ul>
                            </div>
                        </div>
                    </div>
                </div>

                @if($isTherapy)
                <div class="col-md-12 p-0 d-flex mt-3">
                    <div class="card w-100">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Therapy Documents</h5>
                            <div class="detail-section d-flex flex-wrap" style="gap:10px;">
                                @if(!empty($candidatesDetails->recruiterPhoneScreenDate))
                                    <a href="{{ route('candidate.therapy.pdf', [$candidatesDetails->id, 'recruiter_phone_screen']) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa fa-file-pdf-o"></i> Recruiter Phone Screen
                                    </a>
                                @endif
                                @if(!empty($candidatesDetails->clinicalInterviewDate))
                                    <a href="{{ route('candidate.therapy.pdf', [$candidatesDetails->id, 'clinical_interview']) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa fa-file-pdf-o"></i> Clinical Interview
                                    </a>
                                @endif
                                @if(!empty($candidatesDetails->offerExtendedDate))
                                    <a href="{{ route('candidate.therapy.pdf', [$candidatesDetails->id, 'offer_extended']) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                        <i class="fa fa-file-pdf-o"></i> Offer Extended
                                    </a>
                                @endif
                                @foreach($documents as $document)
                                        <a href="{{ route('rightSignature.documentDownload', ['documentFileName' => $document['filename']]) }}" target="_blank" class="btn btn-sm btn-outline-secondary">
                                            <i class="fa fa-file-o"></i> {{ $document['original_filename'] }}
                                        </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <div class="col-md-12 p-0 d-flex mt-5 tabs-container flex-column">
                    <ul class="nav nav-tabs col-md-12" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" data-toggle="tab" href="#application" role="tab">Application</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#chatWidget" role="tab">Messaging</a>
                        </li>

                        @if(LD::checkFeatureFlag('add-custom-field-to-candidate'))
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#customFields" role="tab">Custom Fields</a>
                            </li>
                        @endif

                    </ul>
                    <div class="tab-content p-3">
                        <div class="tab-pane active" id="application" role="tabpanel">
                            <div class="col-md-12 card-cnt p-0 d-flex mt-5">
                                <div class="card mr-5 no-border p-0">
                                    <div class="form-group row notes-cnt">
                                        <div class="col-md-12 mb-3">
                                            <div class="textarea-border">
                                                <span class="att-value editable-textarea" data-inputclass="textarea-size"
                                                    data-emptytext='Click to add comments' data-type='textarea'
                                                    data-url='{{route("editable")}}' data-pk="{{ $candidatesDetails->id }}"
                                                    data-name='comments' data-value="{{ $candidatesDetails->comments }}"
                                                    data-table='candidatesDetails'>{{$candidatesDetails->comments}}</span>
                                            </div>
                                        </div>
                                    </div>
                                    @if(isset($candidatesDetails->outcomeNotes) && $candidatesDetails->outcomeNotes != '')
                                    <!--<div class="form-group row notes-cnt">
                                        <div class="col-md-12">
                                            Reason: {{$candidatesDetails->outcomeNotes}}
                                        </div>
                                    </div>-->
                                    @endif
                                </div>
                                <div class="card no-border p-0">
                                    <table class="table" aria-describedby="">
                                        <tr>
                                            <th>Workflow</th>
                                            <th>Date</th>
                                            <th>Time</th>
                                        </tr>
                                        @foreach($workflows as $key => $workFlow)
                                        <tr>
                                            <td>{{ $workFlow }}</td>
                                            <td>{{ H::getFormatedDate($candidatesDetails->{$key}) }}</td>
                                            <td>
                                                @if ($key === 'clinicalInterviewDate' && $isTherapy)
                                                    {{H::getFormatedTimeWithCurrentUserTimezone($candidatesDetails->{$key})}}
                                                @else
                                                    {{H::getFormatedTime($candidatesDetails->{$key})}}
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane" id="chatWidget" role="tabpanel">
                            @include('candidates._messages')
                        </div>

                        @if(LD::checkFeatureFlag('add-custom-field-to-candidate'))
                            <div class="tab-pane" id="customFields" role="tabpanel">
                                @include('candidates._custom_fields_tab')
                            </div>
                        @endif

                    </div>
                </div>
            </div>
            <div class="col-md-3 d-flex p-0">
                <x-activity-log>
                    <x-slot name="trackerId">{{$candidatesDetails->id}}</x-slot>
                </x-activity-log>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>
function reloadTrackerHistory(id, type, destination) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('candidateHistory')}}",
        type: 'get',
        data: {
            id: id,
            type: type,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $(destination).html(data);
        }
    });
}

$(document).ready(function() {
    reloadTrackerHistory('<?php echo $candidatesDetails->id?>', 'candidate', '.activity-log');
});

$(document).on(
  'shown.bs.tab',
  'a[data-toggle="tab"][href="#customFields"]',
  function () {
    $('#customFields').find('.datepicker').each(function () {
      const $input = $(this);

      if ($input.hasClass('hasDatepicker')) return;

      $input.datepicker({
        dateFormat: 'mm/dd/yy',
        beforeShow: function (input, inst) {
          const $input = $(input);

          setTimeout(() => {
            const offset = $input.offset();
            const height = $input.outerHeight();

            inst.dpDiv
              .appendTo('body')
              .css({
                position: 'absolute',
                top: offset.top + height + 6,
                left: offset.left,
                zIndex: 1055
              });
          }, 0);
        }
      });
    });
  }
);
</script>
