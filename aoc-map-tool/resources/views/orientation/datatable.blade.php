<?php
use App\Helpers\LD;

$heading = [];
$fields = [];
if($type=='tomorrow_ipeas') {
	$heading[] = "Nurse";
	$heading[] = "IPEA Date";
	$heading[] = "Branch";
	$heading[] = "OC";
	$heading[] = "Status";
	$heading[] = "Recruiter Notes";
	$heading[] = "Branch Notes";
	$heading[] = "Action";

	$fields[] = ["data"=>"Candidate","name"=>"mssql_recruitertracker.Candidate", "width" => "12%"];
	$fields[] = ["data"=>"appointment_date","name"=>"recruiting_schedule.appointment_date", "width" => "8%"];
	$fields[] = ["data"=>"branch","name"=>"branch_locations.branch", "width" => "8%"];
	$fields[] = ["data"=>"oc","name"=>"oc", "width" => "8%","searchable"=> false];
	$fields[] = ["data"=>"Candidate_Current_Status","name"=>"mssql_recruitertracker.Candidate_Current_Status", "width" => "12%"];
	$fields[] = ["data"=>"Candidate_Notes","name"=>"mssql_recruitertracker.Candidate_Notes", "width" => "25%"];
	$fields[] = ["data"=>"branch_notes","name"=>"recruiting_schedule.branch_notes", "width" => "25%"];
	$fields[] = ["data"=>"Action", "width" => "5%"];
}else if($type=='ipeas_to_confirm') {
	if(LD::checkFeatureFlag("show-in-home-on-ipea-calendar")) {
		$heading[] = "Nurse";
		$heading[] = "IPEA Date";
		$heading[] = "Branch";
		$heading[] = "OC";
		$heading[] = "Status";
		$heading[] = "Recruiter Notes";
		$heading[] = "Branch Notes";
		$heading[] = "In Home?";
		$heading[] = "Action";

		$fields[] = [
			"data" => "Candidate",
			"name" => "mssql_recruitertracker.Candidate",
			"width" => "12%"
		];
		$fields[] = [
			"data" => "appointment_date",
			"name" => "recruiting_schedule.appointment_date",
			"width" => "8%"
		];
		$fields[] = [
			"data" => "branch",
			"name" => "branch_locations.branch",
			"width" => "8%"
		];
		$fields[] = [
			"data" => "oc",
			"name" => "oc",
			"width" => "8%",
			"searchable" => false
		];
		$fields[] = [
			"data" => "Candidate_Current_Status",
			"name" => "mssql_recruitertracker.Candidate_Current_Status",
			"width" => "10%"
		];
		$fields[] = [
			"data" => "Candidate_Notes",
			"name" => "mssql_recruitertracker.Candidate_Notes",
			"width" => "25%"
		];
		$fields[] = [
			"data" => "branch_notes",
			"name" => "recruiting_schedule.branch_notes",
			"width" => "20%"
		];
		$fields[] = [
			"data" => "intake_in_home",
			"name" => "mssql_recruitertracker.intake_in_home",
			"width" => "5%"
		];
		$fields[] = [
			"data" => "Action",
			"width" => "5%"
		];
	} else {
		$heading[] = "Nurse";
		$heading[] = "IPEA Date";
		$heading[] = "Branch";
		$heading[] = "OC";
		$heading[] = "Status";
		$heading[] = "Recruiter Notes";
		$heading[] = "Branch Notes";
		$heading[] = "Action";

		$fields[] = [
			"data" => "Candidate",
			"name" => "mssql_recruitertracker.Candidate",
			"width" => "12%"
		];
		$fields[] = [
			"data" => "appointment_date",
			"name" => "recruiting_schedule.appointment_date",
			"width" => "8%"
		];
		$fields[] = [
			"data" => "branch",
			"name" => "branch_locations.branch",
			"width" => "8%"
		];
		$fields[] = [
			"data" => "oc",
			"name" => "oc",
			"width" => "8%",
			"searchable" => false
		];
		$fields[] = [
			"data" => "Candidate_Current_Status",
			"name" => "mssql_recruitertracker.Candidate_Current_Status",
			"width" => "10%"
		];
		$fields[] = [
			"data" => "Candidate_Notes",
			"name" => "mssql_recruitertracker.Candidate_Notes",
			"width" => "25%"
		];
		$fields[] = [
			"data" => "branch_notes",
			"name" => "recruiting_schedule.branch_notes",
			"width" => "20%"
		];
		$fields[] = [
			"data" => "Action",
			"width" => "5%"
		];
	}
} else if($type=='todays_ipeas') {
	$heading[] = "Nurse";
	$heading[] = "IPEA Date";
	$heading[] = "Time";
	$heading[] = "Branch";
	$heading[] = "OC";
	$heading[] = "Recruiter Tracker Status";
	$heading[] = "Last Step Completed";
	$heading[] = "Next Step";
	$heading[] = "Recruiter Notes";
	$heading[] = "Branch Notes";
	$heading[] = "Action";

	$fields[] = ["data"=>"Candidate","name"=>"mssql_recruitertracker.Candidate", "width" => "10%"];
	$fields[] = ["data"=>"appointment_date","name"=>"recruiting_schedule.appointment_date", "width" => "8%"];
	$fields[] = ["data"=>"appointment_time","name"=>"recruiting_schedule.appointment_time", "width" => "8%"];
	$fields[] = ["data"=>"branch","name"=>"branch_locations.branch", "width" => "9%"];
	$fields[] = ["data"=>"oc","name"=>"oc", "width" => "8%","searchable"=> false];
	$fields[] = ["data"=>"Candidate_Current_Status","name"=>"mssql_recruitertracker.Candidate_Current_Status", "width" => "10%"];
	$fields[] = ["data"=>"ipea_status","name"=>"recruiting_schedule.ipea_status", "width" => "10%"];
	$fields[] = ["data"=>"NS","name"=>"NS", "width" => "10%"];
	$fields[] = ["data"=>"Candidate_Notes","name"=>"mssql_recruitertracker.Candidate_Notes", "width" => "15%"];
	$fields[] = ["data"=>"branch_notes","name"=>"recruiting_schedule.branch_notes", "width" => "15%"];
	$fields[] = ["data"=>"Action", "width" => "5%"];
}else if($type=='pending_orientation') {
	$heading[] = "Nurse";
	$heading[] = "IPEA Date";
	if(LD::checkFeatureFlag('add-branch-in-pending-orientation')){
		$heading[] = "Branch";
	}
	$heading[] = "OC";
	$heading[] = "Pending";
	$heading[] = "Branch Notes";
	$heading[] = "Action";
	$fields[] = ["data"=>"Candidate","name"=>"mssql_recruitertracker.Candidate", "width" => "10%"];
	$fields[] = ["data"=>"appointment_date","name"=>"recruiting_schedule.appointment_date", "width" => "10%"];
	if(LD::checkFeatureFlag('add-branch-in-pending-orientation')){
		$fields[] = ["data"=>"BranchName","name"=>"mssql_facilities.BranchName", "width" => "10%"];
	}
	$fields[] = ["data"=>"oc","name"=>"oc", "width" => "8%","searchable"=> false];
	$fields[] = ["data"=>"IPEA_pending","name"=>"recruiting_schedule.IPEA_pending", "width" => "15%"];
	$fields[] = ["data"=>"branch_notes","name"=>"recruiting_schedule.branch_notes", "width" => "40%"];
	$fields[] = ["data"=>"Action", "width" => "10%"];
} else if($type=='badge_request') {
    $heading[] = "ID";
	$heading[] = "Name";
	$heading[] = "ClinicianID";
	$heading[] = "Branch";
	$heading[] = "Hire Date";
	$heading[] = "Action";
    $fields[] = ["data"=>"providerId","name"=>"providerId", "width" => "30%",'visible'=>false];
	$fields[] = ["data"=>"fullName","name"=>"mssql_providers.fullName", "width" => "30%"];
	$fields[] = ["data"=>"ClinicianID","name"=>"mssql_providers.ClinicianID", "width" => "20%"];
    $fields[] = ["data"=>"FacilityName","name"=>"mssql_facilities.FacilityName", "width" => "20%"];
    $fields[] = ["data"=>"HiredDate","name"=>"mssql_providers.HiredDate", "width" => "20%"];
	$fields[] = ["data"=>"Action", "width" => "10%"];
}else if($type=='expiring') {
    $heading[] = "ID";
	$heading[] = "Nurse";
	$heading[] = "Discipline";
	$heading[] = "Renewable Item";
	$heading[] = "Expiration Date";
	$heading[] = "Action";
    $fields[] = ["data"=>"id","name"=>"id", "width" => "5%",'visible'=>false];
	$fields[] = ["data"=>"fullName","name"=>"mssql_providers.fullName", "width" => "30%"];
	$fields[] = ["data"=>"DisciplineName","name"=>"mssql_providers.DisciplineName", "width" => "20%"];
    $fields[] = ["data"=>"name","name"=>"renewables_types.name", "width" => "20%"];
    $fields[] = ["data"=>"expiration_date","name"=>"renewables.expiration_date", "width" => "20%"];
	$fields[] = ["data"=>"Action", "width" => "10%"];
}else if($type=='missing') {
    $heading[] = "ID";
	$heading[] = "Nurse";
	$heading[] = "Discipline";
	$heading[] = "Renewable Item";
	$heading[] = "Action";
    $fields[] = ["data"=>"id","name"=>"id", "width" => "5%",'visible'=>false];
	$fields[] = ["data"=>"fullName","name"=>"mssql_providers.fullName", "width" => "30%"];
	$fields[] = ["data"=>"DisciplineName","name"=>"mssql_providers.DisciplineName", "width" => "20%"];
    $fields[] = ["data"=>"name","name"=>"renewables_types.name", "width" => "20%"];
	$fields[] = ["data"=>"Action", "width" => "10%"];
}
?>


<div class="table-responsive">
	<table class="table table-bordered" id="dataTable-orientation-<?php echo str_replace(' ', '_', $type)?>" width="100%" cellspacing="0">
		<thead>
			<tr>
				<?php foreach($heading as $head) { ?>
				<td><?php echo $head?></td>
				<?php } ?>
			</tr>
		</thead>
	</table>
</div>

@if(LD::checkFeatureFlag('oc-dashboard-remove-candidate'))
<x-modal>
    <x-slot name="id">ocDashboardRemoveCandidate<?php echo str_replace(' ', '_', $type); ?></x-slot>
    <x-slot name="size">w-50</x-slot>
    <x-slot name="title">Remove Candidate</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
		<p>Remove Candidate?</p>
		<input type="hidden" name="<?php echo str_replace(' ', '_', $type); ?>recruiter_tracker_id" id="<?php echo str_replace(' ', '_', $type); ?>recruiter_tracker_id" value=""/>
		<div class="input-group mb-3">
			<div class="input-group-prepend">
				<label class="input-group-text" for="select_reason<?php echo str_replace(' ', '_', $type); ?>">Select a Reason</label>
			</div>
			<select id="select_reason<?php echo str_replace(' ', '_', $type); ?>" name="select_reason<?php echo str_replace(' ', '_', $type); ?>" class="custom-select">
				<option value="No longer interested in Home Health">No longer interested in Home Health</option>
				<option value="Accepted another job opportunity">Accepted another job opportunity</option>
				<option value="Needs to postpone until further notice">Needs to postpone until further notice</option>
				<option value="Unable to contact to complete onboarding">Unable to contact to complete onboarding</option>
			</select>
		</div>
		<div class="input-group mb-3" id="reason_notes_container<?php echo str_replace(' ', '_', $type); ?>" style="display:none;">
			<textarea placeholder="Notes" class="form-control" id="reason_notes<?php echo str_replace(' ', '_', $type); ?>" name="reason_notes<?php echo str_replace(' ', '_', $type); ?>"></textarea>
		</div>
    </x-slot>
    <x-slot name="footer">
		<button type="button" class="btn btn-primary" onclick="removeCandidate('<?php echo str_replace(' ', '_', $type); ?>');" data-dismiss="modal">Remove</button>
	</x-slot>
</x-modal>
@endif

<script>
function <?php echo str_replace(' ', '_', $type)?>(){
	var <?php echo str_replace(' ', '_', $type)?> = $("#dataTable-orientation-<?php echo str_replace(' ', '_', $type)?>").DataTable({
		"order": [
			[2, "desc"]
		],
		"columnDefs": [

			{ className: "dt-nowrap", "targets": [1] },
			{ className: "dt-nowrap", "targets": [-1] },

		],
		"ajax": {
			"url": "{{route('dashboard.getorientationdata')}}?type=<?php echo $type?>",
			data: function(d) {
				d.filters = $("#orientationForm").serializeArray();
			},
		},
		"deferRender": true,
		"processing": true,
		"serverSide": true,
		"columns": <?php echo json_encode($fields)?>,
		"drawCallback": function (oSettings, json) {
			$("#<?php echo str_replace(' ', '_', $type)?>-counter").html("("+<?php echo str_replace(' ', '_', $type)?>.page.info().recordsTotal+")");
		},
		"initComplete": function(settings, json) {

			jQuery("#dataTable-orientation-<?php echo str_replace(' ', '_', $type)?>_filter input").off("keyup.DT input.DT");
			var searchDelay = null;
			jQuery("#dataTable-orientation-<?php echo str_replace(' ', '_', $type)?>_filter input").on("keyup", function(e) {

				var search = jQuery("#dataTable-orientation-<?php echo str_replace(' ', '_', $type)?>_filter input").val();

				clearTimeout(searchDelay);
				searchDelay = setTimeout(function() {
					if (search != null && search.length >=3)
					<?php echo str_replace(' ', '_', $type)?>.search(search).draw();
					if (search.length==0 && e.keyCode==8)
					<?php echo str_replace(' ', '_', $type)?>.search(search).draw();
				}, dataTableDelay);
			});
		},
	});
}

@if(LD::checkFeatureFlag('oc-dashboard-remove-candidate'))
function showRemoveCandidateModel(recruterTrackerId, type) {
	$('#' + type + 'recruiter_tracker_id').val(recruterTrackerId);
	$('#ocDashboardRemoveCandidate'+type).modal('show');
	$('#select_reason' + type).change(function(){
		if ($(this).val() == 'Needs to postpone until further notice') {
			$('#reason_notes_container'+type).val('').show();
		} else {
			$('#reason_notes_container'+type).hide();
		}
	});
}
function removeCandidate(type) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('removeCandidate')}}",
        type: 'post',
        data: {
            recruterTrackerId: $('#' + type + 'recruiter_tracker_id').val(),
			reason: $('#select_reason' + type).val(),
			reasonNotes: $('#reason_notes' + type).val(),
            _token: _token
        },
        success: function(data) {
			$('#' + type + 'recruiter_tracker_id').val('');
			$('#select_reason' + type).val($("#select_reason" + type + " option:first").val());
			$('#reason_notes' + type).val('');
            $("#dataTable-orientation-" + type).DataTable().ajax.reload();
        },
        error: function(xhr, status, error) {
            console.log(xhr, status, error, 'error');
            if (status == 'error') {
                window.location.href = '{{route("login")}}';
            }
        }
    });
}
@endif
</script>
