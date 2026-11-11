<?php
    use App\Helpers\LD;
	use App\Helpers\H;
?>
<ul class="nav nav-tabs" role="tablist">
	<li class="nav-item">
		<a class="nav-link active" data-toggle="tab" href="#clientnthrecords" role="tab">NTH Records</a>
	</li>
	<li class="nav-item">
		<a class="nav-link " data-toggle="tab" href="#clientrecruitingActivity" role="tab">Recruiting Activity</a>
	</li>
	<li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#clientupcomingAppointments" role="tab">Upcoming Appointments</a>
	</li>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#clientcareTeam" role="tab">Care Team </a>
	</li>
	<?php if(LD::checkFeatureFlag('client-profile-add-past-appointments')){ ?>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#clientPastAppointments" role="tab">Previous Appointments</a>
	</li>
	<?php } ?>
    <?php if(LD::checkFeatureFlag('halos-tab-in-client-profile')){ ?>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#halos" role="tab">HALOS</a>
	</li>
	<?php } ?>
	<?php if(LD::checkFeatureFlag('add-mobile-app-user')){
		if(H::canPermissions('mobile.addnewuser')){?>
	<li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#mobileAppUser" role="tab">Mobile App User</a>
	</li>
	<?php }} ?>
	<?php if(LD::checkFeatureFlag('clinical-tab-in-client-profile')){ ?>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#clinicalWellChild" role="tab">Clinical</a>
	</li>
	<?php } ?>
	<?php if(LD::checkFeatureFlag('show_feature_photo_client')){ ?>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#featurePhotos" role="tab">Feature Photos</a>
	</li>
	<?php } ?>
	<?php if(LD::checkFeatureFlag('client-payer-source-eligibility-tab')){ ?>
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#payerSourceEligibility" role="tab">Payer Source & Eligibility</a>
	</li>
	<?php } ?>
	@if(LD::checkFeatureFlag('update-custom-fields-on-client-profile'))
    <li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#customFields" role="tab">Custom Fields</a>
	</li>
	@endif
	@if(LD::checkFeatureFlag('add-intake-tab-in-client-profile'))
	<li class="nav-item">
		<a class="nav-link" data-toggle="tab" href="#clientintake" role="tab">Intake</a>
	</li>
	@endif
</ul>
<div class="tab-content p-3">
	<div class="tab-pane active" id="clientnthrecords" role="tabpanel">
		@include('client.tabs._nthRecords')
	</div>
	<div class="tab-pane" id="clientrecruitingActivity" role="tabpanel">
		@include('client.tabs._recruitingActivity')
	</div>
	<div class="tab-pane" id="clientupcomingAppointments" role="tabpanel">
		@include('client.tabs._upcomingAppointments')
	</div>
    <div class="tab-pane" id="clientcareTeam" role="tabpanel">
		@include('client.tabs._careTeam')
	</div>
	<?php if(LD::checkFeatureFlag('client-profile-add-past-appointments')){ ?>
	<div class="tab-pane" id="clientPastAppointments" role="tabpanel">
		@include('client.tabs._pastAppointments')
	</div>
	<?php } ?>
    <?php if(LD::checkFeatureFlag('halos-tab-in-client-profile')){ ?>
	<div class="tab-pane" id="halos" role="tabpanel">
		@include('client.tabs._halos')
	</div>
	<?php } ?>
	<?php if(LD::checkFeatureFlag('add-mobile-app-user')){
		if(H::canPermissions('mobile.addnewuser')){?>
		<div class="tab-pane" id="mobileAppUser" role="tabpanel">
			@include('client.tabs._mobileAppUser')
		</div>
	<?php }} ?>
	<?php if(LD::checkFeatureFlag('clinical-tab-in-client-profile')){ ?>
		<div class="tab-pane" id="clinicalWellChild" role="tabpanel">
			@include('client.tabs._clinicalWellChild')
		</div>
	<?php } ?>
	<?php if(LD::checkFeatureFlag('show_feature_photo_client')){ ?>
	<div class="tab-pane" id="featurePhotos" role="tabpanel">
		@include('client.tabs._featurePhotos')
	</div>
	<?php } ?>
	<?php if(LD::checkFeatureFlag('client-payer-source-eligibility-tab')){ ?>
	<div class="tab-pane" id="payerSourceEligibility" role="tabpanel">
		@include('client.tabs._payerSourceEligibility')
	</div>
	<?php } ?>
	@if(LD::checkFeatureFlag('update-custom-fields-on-client-profile'))
	<div class="tab-pane" id="customFields" role="tabpanel">
		@include('client.tabs._customFields')
	</div>
	@endif
	@if(LD::checkFeatureFlag('add-intake-tab-in-client-profile'))
	<div class="tab-pane" id="clientintake" role="tabpanel">
		@include('client.tabs._intake')
	</div>
	@endif
</div>
@if (!empty($defaultTab))
<script type="text/javascript">
	$(document).ready(function(){
		$('a[href="#{{$defaultTab}}"]').click();
	});
</script>
@endif
