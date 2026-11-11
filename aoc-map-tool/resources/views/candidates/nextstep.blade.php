<?php
use App\Helpers\H;
use App\Helpers\Candidates;
use App\Helpers\LD;
use Illuminate\Support\Str;

$skills = config("constant")["skills"];
$newSkills = config('constant')['newSkills'];
$isTherapy = str_contains($type, 'therap');

if ($isTherapy) {
    $skills = config("constant")["therapySkills"];
    $workflows = config('constant')['appWorkflowStateTherapist'];
}

/**
 * @see CandidatesController::showNextStepForm
 */
?>

<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<link href="{{ mix('css/jquery.datetimepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<script src="{{ mix('js/jquery.datetimepicker.full.min.js') }}"></script>
<style>
ul.att-cnt li {
    min-width: 20%;
}

.tags-cnt {
    margin-top: 10px;
    margin-bottom: 10px;
}
#error-note {
    list-style: none;
    padding: 20px 20px 0px;
    color: #f00;
}

#ui-datepicker-div {
    position: absolute !important;
    left: auto !important;
    top: auto !important;
    box-shadow: 0px 0px 3px 0px;
}
</style>

<x-modal>
    <x-slot name="id">candidateShowNextStepForm</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">Update Workflow \ {{$candidatesDetails->fullName}}</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <!-- <a href=""><i class="fa fa-pencil"></i></a> -->
            @if(str_contains($type, 'nurs'))
                <a href="javascript:void(0);" data-id="{{$candidatesDetails->id}}" id="addScheduleFormFromCandidate"><i
                        class="fa fa-plus"></i></a>
                <a href="{{route('goToNeedToHireMap').'?candidateId='.$candidatesDetails->id}}"
                    data-id="{{$candidatesDetails->id}}"><i class="fa fa-map-marker"></i></a>
            @endif
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="event.preventDefault();openNotes('{{$candidatesDetails->id}}','candidate','Candidate Notes Added')"><i
                class="fa  fa-sticky-note-o"></i></a>
        </div>
        <div class="col-md-6 text-right pull-right">
            <span class='btn btn-primary'>
                {{$candidatesDetails->appWorkflowState}}
            </span>
        </div>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details">
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Candidate</h5>
                            <div class="detail-section">
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
                                <div class="tags-cnt">
                                    @if(LD::checkFeatureFlag('change-att-to-aide-in-add-clinician-form') && !$isTherapy)
                                        <span
                                            class='editable-select {{$candidatesDetails->skillset != "" && in_array($candidatesDetails->skillset, $skills) ? "tag":""}}'
                                            data-emptytext="{{$candidatesDetails->skillset != '' && !in_array($candidatesDetails->skillset, $newSkills) ? 'Current skillset is :'.$candidatesDetails->skillset.' It is invalid click to add valid skillset' : 'Click to add skillset' }}"
                                            data-type='select'
                                            data-source='<?php echo json_encode($newSkills);?>'
                                            data-value="{{ in_array($candidatesDetails->skillset, $newSkills) ? $candidatesDetails->skillset : '' }}"
                                            data-url='{{route("editable")}}' data-pk="{{ $candidatesDetails->id }}"
                                            data-name='skillset' data-table='candidatesDetails'>
                                            {{ in_array($candidatesDetails->skillset, $newSkills) ? $candidatesDetails->skillset : '' }}
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
                                        <span class="att-value editable-text" data-emptytext='Click to add State'
                                            data-type='text' data-url='{{route("editable")}}'
                                            data-pk="{{ $candidatesDetails->id }}" data-name='state'
                                            data-table='candidatesDetails'>{{$candidatesDetails->state}}</span>
                                    </li>
                                    <li>
                                        <span class="att-value editable-text" data-emptytext='Click to add Zip'
                                            data-type='number' data-url='{{route("editable")}}'
                                            data-pk="{{ $candidatesDetails->id }}" data-name='postalCode'
                                            data-table='candidatesDetails'>{{$candidatesDetails->postalCode}}</span>
                                    </li>

                                </ul>
                                @if($candidatesDetails?->screening?->isUnqualified())
                                    <div style="background: red; padding: 15px; margin-top: 10px; color: #fff">
                                        <p>
                                            NOTE: This candidate was unqualified by {{ $candidatesDetails->screening->recruiter->name }} after the recruiter's phone screen
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Requisition</h5>
                            <div class="detail-section">
                                <dl class="col-md-12 p-0 d-flex">
                                    <dt class="col-sm-5 p-0 text-left">Requisition Title: </dt>
                                    <dd class="col-sm-7 text-left">
                                        {{isset($jobDetails->title) ? $jobDetails->title : ''}}
                                    </dd>
                                </dl>
                                <dl class="col-md-12 p-0 d-flex">
                                    <dt class="col-sm-5 p-0 text-left">Requisition Location: </dt>
                                    <dd class="col-sm-7 text-left">
                                        {{isset($jobDetails->locationCity) ? $jobDetails->locationCity : ''}}
                                    </dd>
                                </dl>
                                <dl class="col-md-12 p-0 d-flex">
                                    <dt class="col-sm-5 p-0 text-left">Requisition Category: </dt>
                                    <dd class="col-sm-7 text-left">
                                        {{isset($jobDetails->category) ? $jobDetails->category : ''}}
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <form class="scaffold-form" id="jobViteOutcomeForm">
                    <ul id="error-note"></ul>
                    <!-- hidden fields -->
                    <input type="hidden" name="id" id="candidateId" value="{{$candidatesDetails->id}}" />
                    <div class="row pt-2">
                        <div class="col-md-12">
                            <label for="jobViteOutcome">Outcome</label>
                            @include('candidates.partials.outcome', ['type' => $type, 'bucket' => $bucket, 'isTherapyClinicalManager' => $isTherapyClinicalManager])
                        </div>
                    </div>
                    <div id="outcomeForm"></div>
                    <div class="row pt-5 saveJobViteOutcome" style="display:none;">
                        <div class="col-md-7 text-right">
                            <button type="button" id="saveJobViteOutcomeBtn" class="btn btn-primary">Save</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script>
var validation = {};

(function($,undefined){
  '$:nomunge'; // Used by YUI compressor.

  $.fn.serializeObject = function(){
    var obj = {};

    $.each( this.serializeArray(), function(i,o){
      var n = o.name,
        v = o.value;

        obj[n] = obj[n] === undefined ? v
          : $.isArray( obj[n] ) ? obj[n].concat( v )
          : [ obj[n], v ];
    });

    return obj;
  };

})(jQuery);

$(document).ready(function(){
    $(document).on('change','#jobViteOutcome',function(e){
        var selected = $( "#jobViteOutcome option:selected" );
        var key = selected.attr('data-bucket-key');
        var isTherapy = selected.attr('data-therapy') === 'true';

        if(key != ''){
            loadOutcomeForm(key, isTherapy);
            $('.saveJobViteOutcome').show();
        }else{
            $('.saveJobViteOutcome').hide();
        }
    });
    $(document).on('click','#saveJobViteOutcomeBtn',function(e){
        if ($("#jobViteOutcomeForm").valid() == false) {
            return false;
        }
        saveOutcome();
    });
});
function saveOutcome() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    var data = {};

    data = $('#jobViteOutcomeForm').serializeObject();
    data['_token'] = _token;

    if($('#jobViteOutcome').is(":disabled") && $('#jobViteOutcome').val()) {
        data['jobViteOutcome'] = $('#jobViteOutcome').val();
    }

    $('#error-note').empty();

    var request = {
        url: "{{route('candidate.saveOutcome')}}",
        type: 'post',
        data: data,
        // Server-side validation (SaveOutcomeRequest) answers with 422 instead of the
        // usual payload, so its messages have to be rendered separately.
        error: function(xhr) {
            if (xhr.status !== 422) {
                console.log('Error:', xhr.status, xhr.statusText);
                return;
            }

            var response = xhr.responseJSON || {};
            var messages = [];

            $.each(response.errors || {}, function(field, fieldMessages) {
                messages = messages.concat(fieldMessages);
            });

            if (!messages.length && response.message) {
                messages.push(response.message);
            }

            var $errorNote = $('#error-note');
            $.each(messages, function(index, message) {
                $errorNote.append($('<li/>').text(message));
            });
        },
        success: function(data) {
            if(data.status){
                $('#candidateShowNextStepForm').modal('hide');
                if($( "#jobViteOutcome option:selected" ).attr('data-bucket-key') == 'schedule_ipea'){
                    var candidateId = $('#candidateId').val();
                    showScheduleForm('', 'candidates##'+candidateId, '');
                }
            }else{
                if (data.msg !== undefined) {
                    var txt = 'The outcome was updated successfully, but the calendar invite could not be created. Error message: ' + data.msg;
                    $('.msg-error').text(txt);
                }
                console.log('Error:',data);
            }
        }
    };

    var hasOfferExtendedFiles = $('#offerExtendedFiles').length > 0
        && $('#offerExtendedFiles')[0].files.length > 0;

    if ($('#jobViteOutcome').val() === 'Offer Accepted' || hasOfferExtendedFiles) {
        data = new FormData($(document).find('#jobViteOutcomeForm')[0]);
        data.append('_token', _token);

        // FormData skips disabled controls, so the outcome has to be added explicitly.
        if($('#jobViteOutcome').is(":disabled") && $('#jobViteOutcome').val()) {
            data.append('jobViteOutcome', $('#jobViteOutcome').val());
        }

        request.data = data;
        request.processData = false;
        request.contentType = false;
    }

    $.ajax(request);
}
function loadOutcomeForm(key, isTherapy = false) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('candidate.loadOutcomeForm')}}",
        type: 'get',
        data: {
            bucketKey: key,
            isTherapy: isTherapy,
            id:'{{$candidatesDetails->id}}',
            _token: _token
        },
        success: function(data) {
            $("#outcomeForm").html(data);
            if(typeof validation !== 'undefined'){
                $("#jobViteOutcomeForm").removeData('validator');
                $("#jobViteOutcomeForm").validate(validation);

                if ($('#jobViteOutcome').val() === 'Offer Accepted') {
                    initOfferAcceptedValidation();
                }
            }
        }
    });
}

function initOfferAcceptedValidation() {
    var $form = $('#jobViteOutcomeForm');
    $('#datepicker').rules('add', {
        required: true
    });

    $('#visits').rules('add', {
        required: true
    });

    function toggleNeoRules() {
        var isVisible = $('#show_neo_form').is(':checked');

        $('#add-therapy-neo-container').toggleClass('hide', !isVisible);

        var neoFields = [
            '#first_name',
            '#last_name',
            '#phone',
            '#email',
            '#ipea_location_id',
            '#appointment_date',
            '#appointment_time'
        ];

        $(neoFields.join(',')).each(function () {
            $(this).rules('remove');
        });

        if (isVisible) {
            $('#first_name').rules('add', { required: true });
            $('#last_name').rules('add', { required: true });
            $('#phone').rules('add', { required: true });
            $('#email').rules('add', { required: true, email: true });
            $('#ipea_location_id').rules('add', { required: true });
            $('#appointment_date').rules('add', { required: true });
            $('#appointment_time').rules('add', { required: true });
        }

        var validator = $form.data('validator');
        if (validator) {
            validator.resetForm();
        }
    }

    $('#show_neo_form')
        .off('change.offerAcceptedValidation')
        .on('change.offerAcceptedValidation', function () {
            toggleNeoRules();
        });

    toggleNeoRules();

    $('#datepicker').datepicker({
        onSelect: function () {
            $(this).valid();
        }
    }).on('change', function () {
        $(this).valid();
    });

    $('#datepicker').on('click', function () {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });

    $('#appointment_time').timepicker({
        step: 15,
        forceRoundTime: true,
        minTime: '08:00:00',
        maxTime: '19:00:00'
    });
}
</script>
