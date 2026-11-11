<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\ConstantHelper;

    $specialized = 'No';
    if(H::getHelper('NTHChecklist')->isSpecialized($patientsDetails->CF_Skills)) {
        $specialized = 'Yes';
    }
    $priority = 'No';
    if(isset($needToHireAccounts->IsPriority) && $needToHireAccounts->IsPriority==1) {
        $priority = 'Yes';
    }


    $cname = '';

    if(isset($providerDetails->FirstName) &&  isset($providerDetails->LastName)) {
        $cname = $providerDetails->FirstName.' '.$providerDetails->LastName;
    }

    if(empty($cname)) {
        if(isset($candidateDetails->firstName) && isset($candidateDetails->lastName) ) {
            $cname = $candidateDetails->firstName.' '.$candidateDetails->lastName;
        }
    }

?>

<x-modal>
    <x-slot name="id">cccClientMatchTemplate</x-slot>
    <x-slot name="size">w80</x-slot>
    <x-slot name="title">Client Match \ {{$cname}}</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
        <a data-bs-dismiss="modal" href="javascript:void(0);"
                    onclick="event.preventDefault();openNotes('{{$patientsDetails->id}}', 'client', 'Client Notes Added', 'note')"><i
                    class="fa  fa-sticky-note-o"></i>
                </a>
        </div>

    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-9 tracker-details client-match-template">
                <div class="col-md-12 d-flex p-0">
                    <div class="col-md-6 p-0">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Recruiter: </dt>
                            <dd class="col-sm-8 text-left">
                                    {{ nl2br($recruiterDetails->Recruiter) }}
                            </dd>
                        </dl>
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Clinician Start Date: </dt>
                            <dd class="col-sm-8 text-left">{{H::getFormatedDate($recruiterDetails->Candidate_Start_Date)}}</dd>
                        </dl>
                    </div>


                    <div class="col-md-6 p-0">
                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Was Client on NTH: </dt>
                            <dd class='col-sm-8 text-left'>
                                    {{ nl2br(isset($needToHireAccounts->isOnNTH)?$needToHireAccounts->isOnNTH:'No') }}
                            </dd>
                        </dl>

                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Days on NTH: </dt>
                            <dd class='col-sm-8 text-left'>
                                <span>
                                    {{ nl2br(isset($needToHireAccounts->daysInNTH)?$needToHireAccounts->daysInNTH:0) }}
                                </span>
                            </dd>
                        </dl>

                        <dl class="col-md-12 p-0 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Type of Removal: </dt>
                            <dd class='col-sm-8 text-left'>
                                <span>
                                    {{ nl2br($recruiterDetails->removal_type) }}
                                </span>
                            </dd>
                        </dl>
                    </div>
                </div>


                <div class="col-md-12 card-cnt p-0 d-flex">
                <div class="card mr-5">
                    <div class="card-body">
                        <h5 class="card-title bg-blue">Client Info</h5>
                        <div class="detail-section">
                            <div class="row">
                                <div class="col-md-6">{{$patientsDetails->Patient_FirstName}} {{$patientsDetails->Patient_LastName}}</div>
                                <div class="col-md-6">DOB: {{H::getFormatedDate($patientsDetails->Patient_DOB)}}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><p class="card-text">{{$patientsDetails->PatientAccount}}</p></div>
                                <div class="col-md-6">Age: {{$patientsDetails->Patient_age}}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><p class="card-text">{{$patientsDetails->FacilityName}}</p></div>
                                <div class="col-md-6">Weight: {{$patientsDetails->CF_Weight}}</div>
                            </div>
                            <div class="row pt-3">
                                <div class="col-md-6"><p class="card-text">{{$patientsDetails->Patient_Phone}}</p></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <p class="card-text">
                                    @if($patientsDetails->Patient_MobilPhone)
                                        {{$patientsDetails->Patient_MobilPhone}} (mobile)
                                    @endif
                                    </p>
                                </div>
                            </div>

                            <div class="row pt-3">
                                <div class="col-md-6">
                                    <p class="card-text">{{$patientsDetails->Patient_Address1}} {{$patientsDetails->Patient_Address2}}</p>
                                    <p class="card-text">{{$patientsDetails->Patient_City}} {{$patientsDetails->Patient_State}} {{$patientsDetails->Patient_Zip}}</p>
                                </div>
                                <div class="col-md-6">
                                    @if($patientsDetails->Patient_Gender != '')
                                    <div class="tags-cnt">
                                        <span class='tag tag-grey'>{{$patientsDetails->Patient_Gender}}</span>
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
                                <div class="col-md-6">PDN Auth Hours:  {{$patientsDetails->AuthorizedWeeklyHours}}</div>
                                <div class="col-md-6">Specialized Client:  <?php echo $specialized?></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">CNA Auth Hours:  {{$patientsDetails->CNAAuthorizedWeeklyHours}}</div>
                                <div class="col-md-6">Priority: {{$priority}}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">Respite Auth Hours:  {{$patientsDetails->CF_RespWeeklyHrs}}</div>
                                <div class="col-md-6">Specialized Rate: </div>
                            </div>
                            <div class="row pt-3">
                                <div class="col-md-6">
                                    @if($patientsDetails->is_workForce)
                                    <div class="tags-cnt">
                                        <span class='tag'>Work Force Dev Proj</span>
                                    </div>
                                    @endif
                                </div>
                                <div class="col-md-6">
                                {!! $patientsDetails->skillDisplayValue !!}
                                </div>
                            </div>
                            <div class="row pt-3">
                                <div class="col-md-12">Terminal Payor:  {{$patientsDetails->Payer??''}}</div>
                            </div>
                            <div class="row">
                                <div class="col-md-12">Respite Terminal Payor:  {{$patientsDetails->RespitePayer??''}}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @if(LD::checkFeatureFlag('add-outcomes-to-track-client-match'))
                @if($outcome == 'true')
                    <form id="clientMatch" class="clientMatch">
                    <input type="hidden" name="trackerId" value="{{$recruiterDetails->id}}">
                    <input type="hidden" name="patientId" value="{{$patientsDetails->id}}">
                    <input type="hidden" name="clinician_name" value="{{$cname}}">
                        <div class="col-md-12 pt-3">
                            <div id="successBody"></div>
                            <div class="col-md-6 p-0">
                                <label for="outcome">Outcome</label>
                                <select id="clientmatch_outcome" name="clientmatch_outcome" class="form-control" autocomplete="off">
                                    <option value="">-- Select One --</option>
                                    <option data-id="clientMatchCompleted" value="Client match completed">Client match completed</option>
                                    <option data-id="clientMatchFailed" value="Client match failed">Client match failed</option>
                                </select>
                            </div>
                            <p class="completedText" style="display: none;">This has been addressed in KanTime.</p>
                            <p class="failedText" style="display: none;">This has been addressed in KanTime and hours added back to the NTH.</p>
                            <div class="form-group row">
                                <div class="col-md-12 pt-3">
                                    <label for="clientmatch_comment">Comments</label>
                                    <textarea
                                        name="clientmatch_comment"
                                        id="clientmatch_comment"
                                        class="form-control"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-12 p-0 pt-4 btncontainer text-center">
                            <button type="button" id="clientMatchOutcomeSaveBtn"
                            onclick="saveClientMatchOutcome(this)" class="btn btn-primary">Save</button>
                        </div>
                    </form>
                @else
                    <div class="col-md-6 p-0 mt-3 mr-4 d-inline-block align-top">
                        <h5>Checklist</h5>
                            <table class="table" aria-describedby="">
                            <tbody>
                                <tr>
                                    <th>Name</th>
                                    <th>Done</th>
                                </tr>

                                <?php foreach(H::getHelper("ConstantHelper")->getChecklist() as $key => $value) { ?>
                                <tr>
                                    <td><?php echo $value?></td>
                                    <td>
                                        <?php if($recruiterDetails->{$key}=='1') { ?>
                                            <i class="fa fa-check-circle" aria-hidden="true"></i>
                                        <?php } else { ?>
                                            <i class="fa fa-times-circle" aria-hidden="true"></i>
                                        <?php } ?>
                                    </td>
                                </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="col-md-5 p-0 mt-3 d-inline-block align-top">
                        <h5>Schedule</h5>
                        <table class="table" id="trackernthschedule">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Day</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Hours</th>
                                    <th></th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endif
            @else
                <div class="col-md-6 p-0 mt-3 mr-4 d-inline-block align-top">
                    <h5>Checklist</h5>
                        <table class="table" aria-describedby="">
                        <tbody>
                            <tr>
                                <th>Name</th>
                                <th>Done</th>
                            </tr>

                            <?php foreach(H::getHelper("ConstantHelper")->getChecklist() as $key => $value) { ?>
                            <tr>
                                <td><?php echo $value?></td>
                                <td>
                                    <?php if($recruiterDetails->{$key}=='1') { ?>
                                        <i class="fa fa-check-circle" aria-hidden="true"></i>
                                    <?php } else { ?>
                                        <i class="fa fa-times-circle" aria-hidden="true"></i>
                                    <?php } ?>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>

                <div class="col-md-5 p-0 mt-3 d-inline-block align-top">
                    <h5>Schedule</h5>
                    <table class="table" id="trackernthschedule">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Day</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Hours</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            @endif
            </div>
            <div class="col-md-3 d-flex p-0">
                <x-activity-log>
                    <x-slot name="trackerId">{{$recruiterDetails->id}}</x-slot>
                </x-activity-log>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
function reloadClientHistory(id, type, destination) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('clienthistory')}}",
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
    reloadClientHistory('<?php echo $patientsDetails->id?>', 'clienthistory', '.activity-log');

    $("#trackernthschedule").DataTable({
        "order": [
            ["0", "desc"]
        ],
        "ajax": {
            "url": "{{route('getTrackerNthSchedule')}}",
            data: {
                'tracker_id': '{{$recruiterDetails->id}}',
                'all':'1'
            },
        },
        "deferRender": true,
        "processing": true,
        "serverSide": true,
        "searching": false,
        "lengthChange": false,
        "info": false,
        "ordering": false,
        "bAutoWidth": false,
        "columns": [
            {"data":"id","visible":false},
            {"data":"StartDayOfWeek","visible":true},
            {"data":"StartTime","visible":true},
            {"data":"EndTime","visible":true},
            {"data":"ShiftHours","visible":true},
            {"data":"Action"}
        ]
    });
});

$("#clientmatch_outcome").change(function () {
    let outcome = $("#clientmatch_outcome").val();
    if (outcome === 'Client match completed') {
        $(".completedText").show();
        $(".failedText").hide(); 
    } else if (outcome === 'Client match failed') {
        $(".completedText").hide();
        $(".failedText").show();
    } else {
        $(".completedText").hide();
        $(".failedText").hide();
    }
});
$("#clientMatch").validate({
    onfocusout: false,
    invalidHandler: function(form, validator) {
        var errors = validator.numberOfInvalids();
        if (errors) {
            validator.errorList[0].element.focus();
        }
    },
    rules: {
        "clientmatch_outcome":{
            required: true
        },
        "clientmatch_comment":{
            required: true
        },
    },
    errorPlacement: function(error, element) {
        error.appendTo(element.parent());
    }
});

function saveClientMatchOutcome(button) {
    if ($("#clientMatch").valid()) {
        button.disabled = true;
        $.ajax({
            url: "{{ route('saveClientMatchOutcome') }}",
            type: 'get',
            data: $("#clientMatch").serialize(),
            dataType: "json",
            success: function(response) {
                button.disabled = false;
                if (response.status == 1) {
                    $('#cccClientMatchTemplate').modal('hide');
                    $('.dataTable').DataTable().ajax.reload(null, false);
                }
            },
        });
    }
}
</script>
