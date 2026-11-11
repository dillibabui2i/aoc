<?php
	use App\Helpers\LD;
?>

<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item">
		<a class="nav-link active" data-toggle="tab" href="#recruitingActivity" role="tab">Recruiting Activity</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#upcomingAppointments" role="tab">Upcoming Appointments</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#renewables_tab" role="tab">Renewables</a>
	</li>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#careTeam" role="tab">Care Team </a>
	</li>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#notes" role="tab">Notes</a>
	</li>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#messages" role="tab">Messages</a>
	</li>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#payRateAgreements" role="tab">Documents</a>
	</li>
	@if(LD::checkFeatureFlag('update-custom-fields-on-clinician-profile'))
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#customFields" role="tab">Custom Fields</a>
	</li>
	@endif
</ul>
<div class="tab-content p-3">
	<div class="tab-pane active" id="recruitingActivity" role="tabpanel">
        @include('clinician._recruitingActivity')
	</div>
	<div class="tab-pane" id="upcomingAppointments" role="tabpanel">
        @include('clinician._upcomingAppointments')
	</div>
	<div class="tab-pane" id="renewables_tab" role="tabpanel">
        @include('clinician._renewables')
	</div>
    <div class="tab-pane" id="careTeam" role="tabpanel">
        @include('clinician._careTeam')
	</div>
    <div class="tab-pane" id="notes" role="tabpanel">
		<p>Notes</p>
		@if(LD::checkFeatureFlag('create-notification-for-statuschange-request-update'))
			@include('clinician._notes')
		@endif
	</div>
    <div class="tab-pane" id="messages" role="tabpanel">
        @include('clinician._messages')
	</div>
    <div class="tab-pane" id="payRateAgreements" role="tabpanel">
        @include('clinician._payRateAgreements')
	</div>
	@if(LD::checkFeatureFlag('update-custom-fields-on-clinician-profile'))
	<div class="tab-pane" id="customFields" role="tabpanel">
		@include('clinician._customFields')
	</div>
	@endif
</div>
