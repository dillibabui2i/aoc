<?php
use app\Helpers\H;
use App\Helpers\Ld;
use Illuminate\Support\Facades\DB;
$categoryCount = count($intakeData['customefileds']);
$catCount = 1;
?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>

<div class="col-md-6">
    <input type="hidden" id="patient_id" name="patient_id" value="{{$intakeData->aoc_patient_id}}">
    <div class="box" style="background-color: #dfe6ed;">
        <p style="display: inline-block;" class="nthrecords-comment-title">Intake Info</p>
        <a id="editButton" href="javascript:void(0)" class="edit"
        onclick="editIntakeForm('<?php echo $intakeData->id ?>')"
        style="display: inline-block; padding-left: 7px;">
            <i class="fa fa-edit" title="Edit"></i>
        </a>
        @if(!empty($newrefarray))
            <div style="display: inline-block; padding-left: 7px; float:right;">
                <select id="dropdown" style="padding: 5px;" onchange="updateEditButton(this)">
                    @foreach($newrefarray as $key =>$data)
                        <option value="{{$key}}"{{($key == $intakeData->id) ? 'selected' : ''}}>{{$data}}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <div class="box intake-info" style="background-color: white;">
            <div class="row">
                <div class="col-md-6">
                    <p><b>Intake Status</b>: {{$intakeData->IntakeStatus}}</p>
                    <p><b>Referral Type</b>: {{$intakeData->CF_ReferralType}}</p>
                    @if(!empty($intakeData->ReferralDate) && $intakeData->ReferralDate != '0000-00-00')
                    <p><b>Referral Date</b>: {{H::getHelper('H')
                    ->getFormatedDate($intakeData->ReferralDate)}}</p>
                    @else
                    <p><b>Referral Date</b>:</p>
                    @endif
                    <p><b>Intake Date</b>: {{H::getHelper('H')
                    ->getFormatedDate($intakeData->intake_created_date)}}</p>
                    <p><b>Goal SOC Date</b>: {{(!empty($intakeData->CF_GoalSOCDate) ? H::getHelper('H')
                    ->getFormatedDate($intakeData->CF_GoalSOCDate) : '' )}}</p>
                    <p><b>Referral Source</b>: {{isset($intakeData->ReferralSourceID) ? H::getPhyAndRefNamebyId('ref', $intakeData->ReferralSourceID) : ''}}</p>
                    <p><b>Phone</b>: {{$intakeData->Patient_Phone}}</p>
                </div>
                <div class="col-md-6">
                    <p><b>Pending Reason</b>: {{$intakeData->intake_pending_reason}}</p>
                    <p><b>Intake Coordinator</b>: {{H::getHelper('ConstantHelper')
                    ->getUserNameById($intakeData->ic_id)}}</p>
                    <p><b>Intake Location</b>: {{$intakeData->location}}</p>
                    @if(!empty($intakeData->intake_nurse_id))
                        <p><b>Intake Nurse</b>: {{H::getHelper('ConstantHelper')
                        ->getUserNameById($intakeData->intake_nurse_id)}}</p>
                    @else
                        <p><b>Intake Nurse</b>: {{H::getHelper('ConstantHelper')
                        ->getIntakeNurseFromSchedule($intakeData->PatientID)}}</p>
                    @endif
                    <p><b>Referring Physician</b>: {{isset($intakeData->ReferringPhysician) ? H::getPhyAndRefNamebyId('phy', $intakeData->ReferringPhysician) : ''}}</p>
                    <p><b>Business Development Rep</b>: {{$intakeData->BD_Rep}}</p>
                </div>
                <div class="col-md-12">
                    <p><b>Notes</b>:
                        <span class="editable-text" data-placement="bottom"
                            data-emptytext="Click to add notes" data-type="textarea"
                            data-url="{{route('editable')}}" data-pk="{{ $intakeData->id }}"
                            data-name="notes" data-value="{{ $intakeData->notes }}"
                            data-table="intake">{!! $intakeData->notes !!}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="box" style="background-color: #dfe6ed;">
        <p class="nthrecords-comment-title">Client Info</p>
        <div class="box intake-info" style="background-color: white;">
            <div class="row">
                <div class="col-md-12">
                    <p><b>KanTime Link</b>: {{$intakeData->kantime_url}}</p>
                    <p><b>Medicaid #</b>: {{$intakeData->MedicaidNumber}}</p>
                    <p><b>Line of Business</b>: {{$intakeData->LOBName}}</p>
                    <p><b>Transportation Assistance Level</b>: {{$intakeData->transport_level_assistance}}</p>
                    <p><b>Classification</b>: {{$intakeData->client_classification}}</p>
                    <p><b>Marital Status</b>: {{$intakeData->marital_status}}</p>
                    <p><b>Ethnicity</b>: {{$intakeData->Ethnicity}}</p>
                    <p><b>Language</b>: {{$intakeData->Languages}}</p>
                    <p><b>Place of Service</b>: {{$intakeData->PlaceOfService}}</p>
                    <p><b>Living Situation</b>: {{$intakeData->living_situation}}</p>
                    <p><b>Specialized</b>: {{$intakeData->CF_Specialized}}</p>
                </div>
            </div>
        </div>
    </div>
    <br>
    <div class="box" style="background-color: #dfe6ed;">
        <p class="nthrecords-comment-title">Physician Info</p>
        <div class="box intake-info" style="background-color: white;">
            <div class="row">
                <div class="col-md-12">
                    <p><b>Attending Physician</b>: {{isset($intakeData->acuAttendingPhysicianID) ? H::getPhyAndRefNamebyId('phy', $intakeData->acuAttendingPhysicianID) : ''}}</p>
                    <p><b>Primary Care Physician</b>: {{isset($intakeData->PrimarycarePhysicianID) ? H::getPhyAndRefNamebyId('phy', $intakeData->PrimarycarePhysicianID) : ''}}</p>
                    <p><b>Last MD Visit</b>: {{!empty($intakeData->LastMDVisit) ? H::getHelper('H')
                    ->getFormatedDate($intakeData->LastMDVisit) : '' }}</p>
                    <p><b>Next MD Visit</b>: {{!empty($intakeData->NextMDVisit) ? H::getHelper('H')
                    ->getFormatedDate($intakeData->NextMDVisit) : '' }}</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="col-md-6">
    <div class="box" style="background-color: #dfe6ed;">
        <p class="nthrecords-comment-title">Insurance</p>
        <div class="box intake-info" style="background-color: white;">
            <table class="table bg-white">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Priority</th>
                        <th>Insurance ID</th>
                        <th>Effective Period</th>
                    </tr>
                </thead>
                <tbody>
                    @if(!empty($intakeData->insuranc) && count($intakeData->insuranc) > 0)
                        @foreach($intakeData->insuranc as $data)
                            <tr>
                                <td>{{$data['name']}}</td>
                                <td>{{$data['priority']}}</td>
                                <td>{{$data['id']}}</td>
                                <td>{{$data['effective_date']}}</td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>
    <br>
    <div class="box" style="background-color: #dfe6ed;">
        <p class="nthrecords-comment-title">Emergency Contacts</p>
        <div class="box intake-info" style="background-color: white;">
            <table class="table bg-white">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Relation</th>
                        <th>Priority</th>
                        <th>Phone</th>
                    </tr>
                </thead>
                <tbody>
                @if(!empty($intakeData->EmergencyContact) && count($intakeData->EmergencyContact) > 0)
                    @foreach($intakeData->EmergencyContact as $data)
                        <tr>
                            <td>{{$data->full_name}}</td>
                            <td>{{$data->relation}}</td>
                            <td>{{$data->priority}}</td>
                            <td>{{$data->telephone}}</td>
                        </tr>
                    @endforeach
                @endif
                </tbody>
            </table>
        </div>
    </div>
    <br>
    <div class="box" style="background-color: #dfe6ed;">
        <p class="nthrecords-comment-title">Custom Fields</p>
        <div class="box intake-info" style="background-color: white;">
            @foreach($intakeData['customefileds'] as $category => $fields)
                <div class="row {{($catCount != $categoryCount) ? 'border-bottom mb-3' : ''}}">
                    @foreach($fields as $key => $field)
                        @if (!empty($field))
                            <div class="col-md-6 pt-1 pb-1">
                                {!! $field !!}
                            </div>
                        @endif
                    @endforeach
                    @php
                        $catCount++;
                    @endphp
                </div>
            @endforeach
        </div>
    </div>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        @if(!H::canPermissions('editCustomFieldValue'))
            $('#clientintake .editable').editable('disable');
        @endif
    });
    function updateEditButton(select) {
        var selectedValue = select.value;
        var patientId = document.getElementById("patient_id").value;
        getIntakeData(patientId, selectedValue);
        @if(LD::checkFeatureFlag('client-profile-upcoming-recruitment-clinician-clickable'))
            reloadClientHistory(patientId, 'clienthistory', '.client-activity-log');
        @else
            reloadClientHistory(patientId, 'clienthistory', '.activity-log');
        @endif
        var editButton = document.getElementById('editButton');
        if (selectedValue) {
            editButton.setAttribute('onclick', 'editIntakeForm("' + selectedValue + '")');
        } else {
            editButton.setAttribute('onclick', 'editIntakeForm("<?php echo $intakeData->id ?>")');
        }
    }
</script>
