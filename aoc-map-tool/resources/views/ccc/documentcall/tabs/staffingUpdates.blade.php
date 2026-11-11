<?php
use App\Helpers\H;


$nurseBucket = ["PETINT","ColdCallINT"];
$sentLettersBucket = ["TextBlastINT","PrivateMessageINT","LinkedININT","SnailMailINT","BONNursePMINT"];
$nurseCount = 0;
foreach($nurseBucket as $nurse) {
    $tempCount = (int)H::getHelper('NTHChecklist')
    ->getLast7DaysNumber($nurse,$needToHireChecklistDetails->needToHireChecklistId);
    $nurseCount+=$tempCount;
}
$sentLetterCount = 0;
foreach($sentLettersBucket as $sentletter) {
    $tempCount =(int)H::getHelper('NTHChecklist')
    ->getLast7DaysNumber($sentletter,$needToHireChecklistDetails->needToHireChecklistId);
    $sentLetterCount+=$tempCount;
}
?>

<section class="tab-content-staffingupdates">
    <div class="main-content">
        
        <div class="form-row">
            <div class="form-group col-md-12 pb-0 mb-0">
                <label
                    for="staffingupdates_maintext"
                    class="col-md-12 p-0 m-0 text-left"
                >Our recruiting team has been working hard to find the right nurse for your family. 
                This week they called <span style="{{ $nurseCount == 0 ? 'color: red;' : '' }}">{{ $nurseCount }}</span> nurses and they have messaged, emailed, and sent letters to <span style="{{ $sentLetterCount == 0 ? 'color: red;' : '' }}">{{ $sentLetterCount }}</span> nurses.
                You also have <span style="{{ count($upcomingAppointments) == 0 ? 'color: red;' : '' }}">{{ count($upcomingAppointments) }}</span> appointments coming as well.</label>
                
            </div> 
        </div>

        <div class="tab-content-staffingupdates-details">
            <div class="col-md-12 p-0 tabs-container">

                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" data-target="#clientrecruitingActivity" href="#clientrecruitingActivity" role="tab">Recruiting Activity</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab"  data-target="#clientupcomingAppointments" href="#clientupcomingAppointments" role="tab">Upcoming Appointments</a>
                    </li>
                </ul>
                <div class="tab-content p-3">
                    <div class="tab-pane active" id="clientrecruitingActivity" role="tabpanel">
                        @include('client.tabs._recruitingActivity')
                    </div>
                    <div class="tab-pane" id="clientupcomingAppointments" role="tabpanel">
                        @include('client.tabs._upcomingAppointments')
                    </div>
                </div>


            </div>
        </div>

    </div>
</section>