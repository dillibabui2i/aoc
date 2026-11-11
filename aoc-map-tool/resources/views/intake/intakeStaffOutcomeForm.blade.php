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

   
?>
<x-modal>
    <x-slot name="id">intakeStaffOutcomeForm</x-slot>
    <x-slot name="size"></x-slot>
    <x-slot name="title">Intake Outcome</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a data-bs-dismiss="modal" href="javascript:void(0);"
                onclick="getClientDetails('<?php echo $clientDetails->id ?>');">
                <i class="fa fa-user"></i>
            </a>
        </div>
    </x-slot>
    <x-slot name="body">
        <form id="intakeStaffForm" class="intakeStaffForm">
            <input type="hidden" name="bucket" id="bucket" value="{{$bucket}}"/>
            <input type="hidden" name="aoc_patient_id" id="aoc_patient_id" value="{{$clientDetails->id}}"/>
            <input type="hidden" name="intake_id" id="intake_id" value="{{$intakeId}}"/>
            <input type="hidden" name="type" id="type" value="{{$type}}"/>
            <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
            <div class="tracker-details col-md-12">
                <div class="col-md-12 card-cnt p-0 d-flex">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client Info</h5>
                            <div class="detail-section">
                                <div class="row">
                                    <div class="col-md-6">
                                        {{$clientDetails->Patient_FirstName}} {{$clientDetails->Patient_LastName}}
                                    </div>
                                    <div class="col-md-6">
                                        DOB: {{H::getFormatedDate($clientDetails->Patient_DOB)}}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            {{$clientDetails->PatientAccount}}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        Age: {{$clientDetails->Patient_age}}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            {{$clientDetails->FacilityName}}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        Weight: {{$clientDetails->CF_Weight}}
                                    </div>
                                </div>
                                <div class="row pt-3">
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            {{$clientDetails->Patient_Phone}}
                                        </p>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            @if($clientDetails->Patient_MobilPhone)
                                                {{$clientDetails->Patient_MobilPhone}} (mobile)
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="row pt-3">
                                    <div class="col-md-6">
                                        <p class="card-text">
                                            {{$clientDetails->Patient_Address1}} {{$clientDetails->Patient_Address2}}
                                        </p>
                                        <p class="card-text">
                                            {{$clientDetails->Patient_City}} {{$clientDetails->Patient_State}} {{$clientDetails->Patient_Zip}}
                                        </p>
                                    </div>
                                    <div class="col-md-6">
                                        @if($clientDetails->Patient_Gender != '')
                                            <div class="tags-cnt">
                                                <span class='tag tag-grey'>
                                                    {{$clientDetails->Patient_Gender}}
                                                </span>
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
                                    <div class="col-md-6">
                                        PDN Auth Hours:  {{$clientDetails->AuthorizedWeeklyHours}}
                                    </div>
                                    <div class="col-md-6">
                                        Specialized Client:  <?php echo $specialized?>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        CNA Auth Hours:  {{$clientDetails->CNAAuthorizedWeeklyHours}}
                                    </div>
                                    <div class="col-md-6">
                                        Priority: {{$priority}}
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        Respite Auth Hours:  {{$clientDetails->CF_RespWeeklyHrs}}
                                    </div>
                                    <div class="col-md-6">
                                        Specialized Rate: 
                                    </div>
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
                                            <div class="col-md-12 p-1">
                                                Terminal Payor:  {{$clientDetails->Payer??''}}
                                            </div>
                                            <div class="col-md-12 p-1">
                                                Respite Terminal Payor:  {{$clientDetails->RespitePayer??''}}
                                            </div>
                                        </div>
                                        <div class="col-md-6 p-1">
                                            @if(!empty($socialMediaAdDetail))
                                                <div class="col-md-12 p-1 sponsored-ad">
                                                    Client has a sponsored ad currently running:
                                                    {!! ($socialMediaAdDetail->completed ==0) ?
                                                        '<i class="fa fa-circle" aria-hidden="true"></i>':
                                                        '<i class="fa fa-circle-o" aria-hidden="true"></i>'
                                                    !!}Yes&nbsp;&nbsp;
                                                    {!! ($socialMediaAdDetail->completed ==1) ?
                                                    '<i class="fa fa-circle" aria-hidden="true"></i>':
                                                    '<i class="fa fa-circle-o" aria-hidden="true"></i>'
                                                    !!} No
                                                </div>
                                                <div class="col-md-12 p-1">
                                                    Date of last ad: {{H::getFormatedDate($socialMediaAdDetail->start_date)}}
                                                </div>
                                                <div class="col-md-12 p-1">
                                                    Link to ad:
                                                    <a href="javascript:void(0)" onclick="getSocialMediaDetails('{{$socialMediaAdDetail->id}}')">
                                                        {{$socialMediaAdDetail->ad_name}}
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="row pt-3">
                                        <div class="col-md-12">
                                            Terminal Payor:  {{$clientDetails->Payer??''}}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            Respite Terminal Payor:  {{$clientDetails->RespitePayer??''}}
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>                        
            </div>
            <div class="col-md-12 pt-3">
                <div id="successBody"></div>
                @include('intake.outcome.' . $bucket)
            </div>
        </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>