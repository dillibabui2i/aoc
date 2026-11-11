<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\ConstantHelper;

?>

<x-modal>
    <x-slot name="id">cccClientMatchTemplate</x-slot>
    <x-slot name="size">w80</x-slot>
    <x-slot name="title">Client Match \ {{isset($providerDetails->FirstName)?$providerDetails->FirstName:''}} {{isset($providerDetails->LastName)?$providerDetails->LastName:''}}</x-slot>
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
                            <h5 class="card-title bg-blue">Clinician</h5>
                            <div class="detail-section">
                                @if($recruiterDetails->aoc_provider_id)
                                <h6>{{isset($providerDetails->FirstName)?$providerDetails->FirstName:''}} {{isset($providerDetails->LastName)?$providerDetails->LastName:''}}</h6>
                                @else
                                <h6>{{isset($candidateDetails->firstName)?$candidateDetails->firstName:''}} {{isset($candidateDetails->lastName)?$candidateDetails->lastName:''}}</h6>
                                @endif

                                <p class="card-text">
                                    {{isset($providerDetails->ClinicianID) ? $providerDetails->ClinicianID : ""}}
                                </p>
                                <p class="card-text">
                                    {{isset($providerDetails->Branch_Location)?$providerDetails->Branch_Location:''}}

                                </p>
                                <p class="card-text">
                                    @if($recruiterDetails->aoc_provider_id)
                                    {{isset($providerDetails->Phone) ? $providerDetails->Phone : ""}}
                                    @else
                                    {{isset($candidateDetails->homePhone) ? $candidateDetails->homePhone : ""}}
                                    @endif

                                </p>
                                <p class="card-text">
                                    @if($recruiterDetails->aoc_provider_id)
                                    {{isset($providerDetails->Mobile) ? $providerDetails->Mobile : ""}}
                                    @else
                                    {{isset($candidateDetails->mobile) ? $candidateDetails->mobile : ""}}
                                    @endif
                                </p>
                                <p class="card-text">
                                    @if($recruiterDetails->aoc_provider_id)
                                    {{isset($providerDetails->AOC_Email) ? $providerDetails->AOC_Email : ""}}
                                    @else
                                    {{isset($candidateDetails->email) ? $candidateDetails->email : ""}}
                                    @endif
                                </p>

                                <div class="tags-cnt">
                                    <span class='tag'>{{ isset($providerDetails->DisciplineName)?$providerDetails->DisciplineName:'' }}</span>
                                </div>
                                <ul class="d-flex att-cnt">
                                    <li>
                                        <span class="att-name">Total Hours</span>
                                        {{nl2br($recruiterDetails->Total_Hours)}}
                                    </li>
                                    <li>
                                        <span class="att-name">Pay Rate</span>
                                        {{number_format((float)$recruiterDetails->Agreed_Pay_Rate, 2, '.', '')}}
                                    </li>
                                    <li>
                                        <span class="att-name">GP</span>
                                        {{$recruiterDetails->GP * 100}}%
                                    </li>

                                    <li>
                                        <span class="att-name">Approved By</span>
                                        {{nl2br($recruiterDetails->Approved_By)}}
                                    </li>
                                </ul>

                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client</h5>
                            <div class="detail-section">
                                <div class="row">
                                    <div class="col-lg-6">

                                            <?php $name='';
                                                    if(!empty($patientsDetails->Patient_FirstName)) {
                                                        $name.=$patientsDetails->Patient_FirstName;
                                                    }
                                                    if(!empty($patientsDetails->Patient_MiddleName)) {
                                                        $name.=" ".$patientsDetails->Patient_MiddleName;
                                                    }
                                                    if(!empty($patientsDetails->Patient_LastName)) {
                                                        $name.=" ".$patientsDetails->Patient_LastName;
                                                    }
                                                    echo trim($name);
                                                    ?>

                                        <?php if(isset($recruiterDetails->aoc_patient_id) && $recruiterDetails->aoc_patient_id==0){ ?>
                                        <p class="card-text">
                                            <span id="client-card-account"><i>Client on Tracker:
                                                </i>{{isset($recruiterDetails->Client_Name) ? $recruiterDetails->Client_Name : ""}}</span>
                                        </p>
                                        <?php } ?>
                                        <p class="card-text">
                                            <span
                                                id="client-card-account">{{isset($patientsDetails->PatientAccount) ? $patientsDetails->PatientAccount : ""}}</span>
                                        </p>
                                        <p class="card-text">
                                            <span
                                                id="client-card-branch">{{isset($branchDetails->BranchName) ? $branchDetails->BranchName : ""}}</span>
                                        </p>
                                        <p class="card-text">
                                            <span
                                                id="client-card-phone">{{isset($patientsDetails->Patient_Phone) ? $patientsDetails->Patient_Phone : ""}}</span>
                                        </p>
                                        <p class="card-text">
                                            <span
                                                id="client-card-mobile">{{isset($patientsDetails->Patient_MobilPhone) ? $patientsDetails->Patient_MobilPhone : ""}}</span>
                                        </p>
                                    </div>
                                    <div class="col-lg-6">
                                        {!!isset($patientsDetails->skillDisplayValue) ? $patientsDetails->skillDisplayValue : ""!!}
                                    </div>
                                </div>
                            </div>
                            <p class="card-text p-2">
                                <span>CCC: {{isset($patientsDetails->ClientCareCoordinator) ? H::getHelper('ConstantHelper')->getCCCNameByProviderID($patientsDetails->ClientCareCoordinator) : ""}}</span>
                            </p>
                            <p class="card-text payer">
                                <?php if(!empty($patientsDetails->CF_TerminalPayer)) { ?>
                                    <span id="client-card-payer">Terminal Payor: {{$patientsDetails->CF_TerminalPayer}}</span>
                                <?php } ?>
                            </p>
                        </div>
                    </div>
                </div>




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
</script>
