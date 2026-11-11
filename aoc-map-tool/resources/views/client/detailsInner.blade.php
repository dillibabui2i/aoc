<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $specialized = 'No';
    if(H::getHelper('NTHChecklist')->isSpecialized($clientDetails->CF_Skills)) {
        $specialized = 'Yes';
    }
    $priority = 'No';
    if(isset($needToHireChecklistDetails->IsPriority) && $needToHireChecklistDetails->IsPriority==1) {
        $priority = 'Yes';
    }
    $theSaleLength = '';
    if(strlen($clientDetails->CF_TheSale)<255) {
        $theSaleLength = ' (' . strlen($clientDetails->CF_TheSale) . ' characters)';
    }

    $isSuperior = 0;
    if(strpos(strtolower($clientDetails->Payer),'superior')!==false || strpos(strtolower($clientDetails->RespitePayer),'superior')!==false ) {
        $isSuperior = 1;
    }
?>
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
                    @if(LD::checkFeatureFlag('add-intake-tab-in-client-profile'))
                        @if(!empty($intakeData))
                            <div class="col-md-6">Email: {{$intakeData->client_email}}</div>
                        @endif
                    @endif
                </div>
                <div class="row">
                    <div class="col-md-6">
                        <p class="card-text">
                        @if($clientDetails->Patient_MobilPhone)
                            {{$clientDetails->Patient_MobilPhone}} (mobile)
                        @endif
                        <?php if(LD::checkFeatureFlag('display-twilio-error-on-profile-page')){ ?>
                            <?php if(!empty($twilioErrorData)) { ?>
                            &nbsp;<span data-toggle="tooltip" style="vertical-align:text-bottom"
                            title="(Error:{{ $twilioErrorData->error_code }}) {{ $twilioErrorData->error_message }}">
                                <i class="fa fa-times" style="font-size:16px;color:red"></i></span>
                            <?php } ?>
                        <?php } ?>
                        </p>
                    </div>
                </div>

                @if(!empty($clientDetails['EmergencyContact']))
                    @foreach($clientDetails['EmergencyContact'] as $contact)
                        @foreach(['telephone' => 'emergency', 'alternate_phone' => 'alternative', 'work_phone' => 'work'] as $ecField => $ecPhoneType)
                            @if(!empty($contact->$ecField))
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="card-text" data-toggle="tooltip"
                                            title="Belongs to: {{ $contact->full_name }} ({{ $contact->relation }})">
                                            <span style="border-bottom: 1px dashed; display: inline-block;">{{ $contact->$ecField }}</span> (undefined{{--hardcode. Type to be added in following tickets--}})
                                        </p>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    @endforeach
                @endif

                <div class="row pt-3">
                    <div class="col-md-6">
                        <p class="card-text">{{$clientDetails->Patient_Address1}} {{$clientDetails->Patient_Address2}}</p>
                        <p class="card-text">{{$clientDetails->Patient_City}} {{$clientDetails->Patient_State}} {{$clientDetails->Patient_Zip}}</p>
                    </div>
                    <div class="col-md-6">
                        <div class="tags-cnt">
                            @if($clientDetails->Patient_Gender != '')
                                <span class='tag tag-grey'>{{$clientDetails->Patient_Gender}}</span>
                            @endif
                            @if(LD::checkFeatureFlag('add-hh-tag-client-profile'))
                                @if($clientDetails->hh_status == 'Active')
                                <span class='tag tag-grey'>Health Home</span>
                                @endif
                            @endif
                        </div>
                        @if(!empty($clientDetails->CF_GoalSOCDate) && $clientDetails->CF_GoalSOCDate != '0000-00-00 00:00:00')
                            <span>Goal SOC Date: {{H::getFormatedDate($clientDetails->CF_GoalSOCDate)}}</span>
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
                    <div class="col-md-6">Specialized Client:  {{$specialized}}</div>
                </div>
                <div class="row">
                    <div class="col-md-6">CNA Auth Hours:  {{$clientDetails->CNAAuthorizedWeeklyHours}}</div>
                    <div class="col-md-6">Priority: {{$priority}}</div>
                </div>
                <div class="row">
                    <div class="col-md-6">HHA Auth Hours:  {{ $clientDetails->HHAAuthorizedWeeklyHours ?? 0 }}</div>
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
                @canPermission(['update_non_vbc'])
                    @if($isSuperior == 1)
                        <div class="row">
                            <div class="col-md-12">Non VBC?:
                                <span class='editable-select' data-emptytext='Click to set Non VBC'
                                    data-type='select'
                                    data-source='<?php echo htmlspecialchars(json_encode(array_values(['0'=>'No','1'=>'Yes'])),ENT_QUOTES, 'UTF-8');?>'
                                    data-value='{{ $clientDetails->non_vbc }}' data-url='{{route("editable")}}'
                                    data-pk="{{ $clientDetails->id }}" data-name='non_vbc' data-table='patientsDetails'>
                                    {{ $clientDetails->non_vbc }}
                                </span>
                            </div>
                        </div>
                    @endif
                @endif
                @if(LD::checkFeatureFlag('display-frequency-on-client-profile'))
                    <div class="row">
                        @if(!empty($clientDetails->ccc_client_call_frequency))
                        <div class="col-md-6 pl-2">Monthly Call Frequency:
                            {{$clientDetails->ccc_client_call_frequency?
                            config('constant.clientCallFrequency')[$clientDetails->ccc_client_call_frequency]
                            : ''}}</div>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<div class="col-md-12 p-0 tabs-container">
    @include('client._tabs')
</div>


@if(LD::checkFeatureFlag('nth-audit'))
<x-modal>
    <x-slot name="id">showAuditDetailPopup</x-slot>
    <x-slot name="size">w-75</x-slot>
    <x-slot name="title">NTH Schedule Audit</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
    <div class="col-md-12 p-0 mt-3 d-inline-block align-top">
        <table class="table" id="nthauditlist">
            <thead>
                <tr>
                    <th>Changed By</th>
                    <th>Changed At</th>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Hours</th>
                    <th>Action</th>
                    <th>Reason</th>
                    <th>Net</th>
                </tr>
            </thead>
            @foreach($needToHireAuditList as $needToHireAuditData)
                <tr>
                    <td>{{$needToHireAuditData['ChangedBy']}}</td>
                    <td>{{$needToHireAuditData['created_at']}}</td>
                    <td>{{$needToHireAuditData['Day']}}</td>
                    <td>{{$needToHireAuditData['StartTime']}}</td>
                    <td>{{$needToHireAuditData['EndTime']}}</td>
                    <td>{{$needToHireAuditData['ShiftHours']}}</td>
                    <td>{{$needToHireAuditData['DisplayType']}}</td>
                    <td>{{$needToHireAuditData['Reason']}}</td>
                    <td>{{$needToHireAuditData['NetChangeInHours']}}</td>
                </tr>
            @endforeach
        </table>
    </div>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
@endif
