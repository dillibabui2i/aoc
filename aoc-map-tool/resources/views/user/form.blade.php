<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\BranchLocations;
use App\Roles;

?>
@extends('layouts.myaccount')
@section('title', 'Manage Users')
@section('content')
<!-- Begin Page Content -->

<?php
$superUser="";
if(isset($userDetail)){
    $superUser = $userDetail->user_superior;
}

$recruitingTeamLeaders = H::getHelper("UserPermissionResolver")->getAllTeamLeader();
$recruitingManagers = H::getHelper("UserPermissionResolver")->getAllManager();
$recruitingadmin = H::getHelper("UserPermissionResolver")->getRecruitingAdmin();
$billingTeamLeadId = Roles::select('id')->where('name', 'Billing Team Lead')->first();
$billingManagerId = Roles::select('id')->where('name', 'Billing Manager')->first();
$billingTeamLeaders = H::getUsersByRoles($billingTeamLeadId);
$billingManagers = H::getUsersByRoles($billingManagerId);
//var_dump($recruitingManager);
//var_dump($recruitingTeamLeader);

$branchLocation = H::getHelper('ConstantHelper')->getBranches()->toArray();
$branchLocation = H::arrGroupBy($branchLocation, 'state');

if(LD::checkFeatureFlag('add-facilityid-to-users')){
    $facilityids =  H::getHelper('ConstantHelper')->getAssociatedKanTimeBranches()->toArray();
    $facilityids = H::arrGroupBy($facilityids, 'State');
}
$userCurrentRoles = isset($userDetail->id) ? H::getHelper('ConstantHelper')->getCurrentRolesByUserId($userDetail->id) : [];
$timezoneList = [];
if(LD::checkFeatureFlag('add-kantime-id-in-user-grid')){
    $timezoneList = config('constant.usaTimeZone');
}else{
    $timezoneList = H::getHelper('ConstantHelper')->getTimeZoneList();
}
if(LD::checkFeatureFlag('add-intake-goal-field-in-user-form')){
    $allRoles = H::getHelper('ConstantHelper')->getAllRoles();
    $ic_role_id = null;
    foreach($allRoles as $role){
        if($role->name == 'Intake Coordinator'){
            $ic_role_id = $role->id;
        }
    }
    $is_Ic = in_array($ic_role_id, $userCurrentRoles);
}
?>
<style>

.recruiting-section {
    display: none;
}
.billing-section {
    display: none;
}
</style>
<style>
    span.multiselect-native-select {
        width: 100%;
    }
    span.multiselect-native-select .btn-group {
        width: 100%;
        max-width: 100%;
    }
    .multiselect-container.dropdown-menu.show {
        width:100%;
        height: 400px;
        overflow-y: auto;
    }
</style>
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ isset($userDetail) ? 'Edit Users' : 'Add Users' }}</h1>
            <a href="{{ route('manageusers') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
        </div>
    </div>
    <!-- DataTales Example -->
    @if ($errors->any())
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {!! implode('', $errors->all('<div>:message</div>')) !!}
    </div>
    @endif
    @if ($message = Session::get('error'))
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    <div>
        @if( isset($userDetail))
        <form method="POST" id="add-form-validate" action="{{ route('manageusers.updateuser') }}">
            <input type="hidden" name="id" id="id" value="{{$userDetail->id}}">
            @else
            <form method="POST" id="add-form-validate" action="{{ route('manageusers.saveuser') }}">
                @endif
                @csrf
                <div class="form-row">
                    @if(LD::checkFeatureFlag('add-kantime-id-in-user-grid') && !empty($kantimeId))
                        <div class="form-group col-md-12">
                            <label for="name" class="text-left">KantimeID</label>
                            <input type="text" class="form-control" id="name" name="name"
                                value="{{ isset($kantimeId)?$kantimeId:''}}" readonly>
                        </div>
                    @endif
                    <div class="form-group col-md-12">
                        <label for="name" class="text-left">Name</label>
                        <input type="text" class="form-control" id="name" name="name"
                            value="{{ isset($userDetail)?$userDetail->name:''}}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="title" class="text-left">Title</label>
                        <input type="text" class="form-control" id="title" name="title"
                            value="{{ isset($userDetail)?$userDetail->title:''}}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="email" class="text-left">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="{{ isset($userDetail)?$userDetail->email:''}}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="recruiting_manager" class="text-left">Supervisor</label>
                        <select name="recruiting_manager" id="recruiting_manager" class="form-control">
                            <option value="">No Supervisor</option>
                            <?php
                                if(!empty($recruitingManagers)) {
                                    foreach($recruitingManagers as  $manager) {?>
                            <option value="<?php echo $manager->id?>" @if(isset($userDetail))
                                {{ ($userDetail->recruiting_manager == $manager->id)?'selected':'' }} @endif>
                                <?php echo $manager->name?></option>
                            <?php }
                                }
                            ?>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="user_timezone" class="text-left">TimeZone</label>
                        <select name="user_timezone" id="user_timezone" class="form-control">
                            <option value=""> ---Select--- </option>
                            @foreach ($timezoneList as $key => $value)
                                <option  value="{{$key}}" @if(isset($userDetail)) {{ ($userDetail->user_timezone == $key)?'selected':'' }} @endif> {{$value}}</option>
                            @endforeach
                        </select>
                    </div>

                    @if(LD::checkFeatureFlag('multiple-roles-to-user'))
                    <div class="form-group col-md-12">
                        <label for="email" class="text-left">Role(s)</label>
                        <select name="role[]" id="role" class="form-control" multiple>
                            @php $roles =H::getHelper('ConstantHelper')->getAllRoles(); @endphp
                            @foreach ($roles as $key => $value)
                            <option data-department="{{$value->department}}" value="{{$value->id}}"
                                @if(isset($userDetail)) {{ ( in_array($value->id,$userCurrentRoles)) ? "selected" : "" }} @endif>
                                {{$value->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    @else
                    <div class="form-group col-md-12">
                        <label for="email" class="text-left">Role(s)</label>
                        <select name="role" id="role" class="form-control">
                            @php $roles = DB::table('roles')->get(); @endphp
                            @foreach ($roles as $key => $value)
                            <option data-department="{{$value->department}}" value="{{$value->id}}"
                                @if(isset($userDetail)) {{ ($userDetail->role == $value->id)?'selected':'' }} @endif>
                                {{$value->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    @if(!LD::checkFeatureFlag('add-facilityid-to-users'))
                    <div class="form-group col-md-12">
                        <label for="alternateRole" class="text-left">Alternate Role</label>
                        <select name="alternateRole" id="alternateRole" class="form-control">
                            <option value=""> ---Select--- </option>
                            @php $roles = DB::table('roles')->get(); @endphp
                            @foreach ($roles as $key => $value)
                            <option data-department="{{$value->department}}" value="{{$value->id}}"
                                @if(isset($userDetail)) {{ ($userDetail->alternateRole == $value->id)?'selected':'' }} @endif>
                                {{$value->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="form-group col-md-12">
                        <label for="branchid" class="text-left">Associated IPEA Locations</label>
                        <?php $selectedBranches = explode(",", $userDetail->branchid??''); ?>
                        <select id="branchid" name="branchid[]" multiple data-live-search="true" multiple>
                            @foreach($branchLocation as $key => $branch)
                                <optgroup label="{{$key}}">
                                    @foreach($branch as $b)
                                        <option value="{{$b['id']}}" {{(in_array($b['id'], $selectedBranches))?'selected':''}}>{{$b['branch']}}</option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>
                    @if(LD::checkFeatureFlag('add-facilityid-to-users'))
                    <div class="form-group col-md-12">
                        <label for="facilityid" class="text-left">Associated KanTime Branches</label>
							<?php $selectedfacilityid = explode(",", $userDetail->facilityid??''); ?>
							<select id="facilityid" name="facilityid[]" multiple data-live-search="true" multiple>
								@foreach($facilityids as $key => $branch)
									<optgroup label="{{$key}}">
										@foreach($branch as $b)
											<option value="{{$b['id']}}" {{(in_array($b['id'], $selectedfacilityid))?'selected':''}}>{{$b['FacilityName']}}</option>
										@endforeach
									</optgroup>
								@endforeach
							</select>
                    </div>
                    @endif
                    @if(!LD::checkFeatureFlag('add-facilityid-to-users'))
                    <div class="form-group col-md-12">
                        <label for="kantime_username" class="text-left">KanTime User</label>
                        <input type="text" class="form-control" id="kantime_username" name="kantime_username"
                            value="{{ isset($userDetail)?$userDetail->kantime_username:''}}">
                    </div>
                    @endif
                    @if(LD::checkFeatureFlag('add-bilingual-on-user-page'))
                    <div class="form-group col-md-12">
                        <label for="bilingual" class="text-left">Bilingual?</label>
                        <select class="form-control" name="bilingual" id="bilingual">
                                <option value="0" {{ (isset($userDetail['bilingual']) && $userDetail['bilingual'] == 0) ? 'selected' : '' }}>No</option>
                                <option value="1" {{ (isset($userDetail['bilingual']) && $userDetail['bilingual'] == 1) ? 'selected' : '' }}>Yes</option>
                        </select>
                    </div>
                    @endif
                    @if(isset($userDetail))
                        @if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit'))
                        <div class="form-group col-md-12">
                            <label for="bilingual" class="text-left">SMS Radius Limit</label>
                            <select class="form-control"  name="sms_radius_limit_dropdown" id="sms_radius_limit_dropdown">
                                @for($i=50;$i<=500;$i+=50)
                                <option value="{{$i}}" {{($userDetail['sms_radius_limit'] == $i) ? 'selected' : ''}}>{{$i}}</option>
                                @endfor
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="sms_radius_limit" class="text-left"></label>
                            <input type="text" class="form-control hide" placeholder="Please Specify SMS Radius" id="sms_radius_limit" name="sms_radius_limit"
                                value="{{$userDetail['sms_radius_limit']}}">
                        </div>
                        @endif
                    @endif
                    @if(LD::checkFeatureFlag('add-instructor-field-to-user-profile'))
                        <div class="form-group col-md-12">
                            <label for="bilingual" class="text-left">Class instructor</label>
                            <select class="form-control" name="is_instructor" id="instructor">
                                <option value="0" {{ (isset($userDetail['is_instructor']) && $userDetail['is_instructor'] == 0) ? 'selected' : '' }}>No</option>
                                <option value="1" {{ (isset($userDetail['is_instructor']) && $userDetail['is_instructor'] == 1) ? 'selected' : '' }}>Yes</option>
                            </select>
                        </div>
                    @endif
					@canPermission('additional.permission')
					<?php if (isset($userDetail)): ?>
					<div class="form-group col-md-12 rolesform">
						<input type="hidden" name="user_selected_permissions" value="{{implode(",",$userSelectedPermissions)}}"/>
                        <label for="email" class="text-left">Additional Permissions</label>
                        <div id="accordion" style="width:100%;">
							@foreach($permissionsDetail as $group => $permissions)
							<h3>{{$group}}</h3>
							<div>
								<ul>
									@foreach($permissions as $permission)
                                        @if($permission['name'] === 'Retry Background Check' && !LD::checkFeatureFlag('new-background-check'))
                                            @continue(1)
                                        @endif

									<li><input name="permissions[]" type="checkbox" value="{{$permission['id']}}"
											{{in_array($permission['id'], $userSelectedPermissions) ? "checked" : ""}}
											{{(in_array($permission['id'], $selectedPermissions) || ($roleName == 'Super Admin')) ? "checked disabled" : ""}} />
										{{$permission['name']}}</li>
									@endforeach
								</ul>
							</div>
							@endforeach
						</div>
                    </div>
					<?php endif; ?>
					@endcanPermission
                    @if(LD::checkFeatureFlag('add-coverage-booked-goal-field')
                    && (isset($userDetail) && $haveDepartment == 1))
                        <div class="client-care-section col-md-12">
                            <h3 class="text-left"><b>Client Care Coordinator</b></h3>
                            <div class="form-group col-md-12" id="ccc">
                                <label for="coveragebookedgoal" class="text-left">Coverage Booked Goal</label>
                                <input type="text" class="form-control" id="coveragebookedgoal"
                                name="coveragebookedgoal"
                                    value="{{ isset($userDetail)?$userDetail->coverage_booked_goal:''}}">
                            </div>
                        </div>
                    @endif
                    @if(LD::checkFeatureFlag('add-intake-goal-field-in-user-form'))
                        <div class="intake-cordinator-section col-md-12 icclass">
                            <h3 class="text-left"><b>Intake Coordinator</b></h3>
                            <div class="form-group col-md-12" id="intake_cordinator">
                                <label for="IntakeCoordinator" class="text-left">Intake Goal</label>
                                <input type="text" class="form-control" id="ic_goal"
                                name="ic_goal"
                                    value="{{ isset($userDetail)?$userDetail->ic_goal:''}}">
                            </div>
                        </div>
                    @endif
                    <div class="recruiting-section col-md-12">
                        <h3 class="text-left">Recruiting</h3>
                        <div class="form-group col-md-12">
                            <label for="territory" class="text-left">Territory</label>
                            <input type="text" class="form-control" id="territory" name="territory"
                                value="{{ isset($userDetail)?$userDetail->territory:''}}">
                        </div>
                        <div class="form-group col-md-12">
                            <label for="recruiting_teamleader" class="text-left">Recruiting Team Leader</label>
                            <select name="recruiting_teamleader" id="recruiting_teamleader" class="form-control">
                                <option value="">No Teamleader</option>
                                <?php
                                    if(!empty($recruitingTeamLeaders)) {
                                        foreach($recruitingTeamLeaders as  $teamleader) {?>
                                <option value="<?php echo $teamleader->id?>" @if(isset($userDetail))
                                    {{ ($userDetail->recruiting_teamleader == $teamleader->id)?'selected':'' }} @endif>
                                    <?php echo $teamleader->name?></option>
                                <?php }
                                    }
                                ?>
                            </select>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="recruiting_admin" class="text-left">Recruiting Admin</label>
                            <select name="recruiting_admin" id="recruiting_admin" class="form-control">
                                <option value="">No Recruiting Admin</option>

                                <?php
                                    if(!empty($recruitingadmin)) {
                                        foreach($recruitingadmin as  $admin) {?>
                                <option value="<?php echo $admin->id?>" @if(isset($userDetail))
                                    {{ ($userDetail->recruiting_admin == $admin->id)?'selected':'' }} @endif>
                                    <?php echo $admin->name?></option>
                                <?php }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                    @if(LD::checkFeatureFlag('add-billing-teamlead-and-manager-in-user-form'))
                        <div class="billing-section col-md-12">
                            <h3 class="text-left">Billing</h3>
                            <div class="form-group col-md-12">
                                <label for="billing_team_lead" class="text-left">Billing Team Leader</label>
                                <select name="billing_team_lead" id="billing_team_lead" class="form-control">
                                    <option value="">No Billing Teamleader</option>
                                    <?php
                                        if(!empty($billingTeamLeaders)) {
                                            foreach($billingTeamLeaders as  $key=>$teamleader) {?>
                                    <option value="{{ $key }}" @if(isset($userDetail))
                                        {{ ($userDetail->billing_team_lead == $key)?'selected':'' }} @endif>
                                        {{ $teamleader }}</option>
                                    <?php }
                                        }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group col-md-12">
                                <label for="billing_team_manager" class="text-left">Billing Manager</label>
                                <select name="billing_team_manager" id="billing_team_manager" class="form-control">
                                    <option value="">No Billing Manager</option>

                                    <?php
                                        if(!empty($billingManagers)) {
                                            foreach($billingManagers as  $key=>$manager) {?>
                                    <option value="{{ $key }}" @if(isset($userDetail))
                                        {{ ($userDetail->billing_team_manager == $key)?'selected':'' }} @endif>
                                        {{ $manager }}</option>
                                    <?php }
                                        }
                                    ?>
                                </select>
                            </div>
                        </div>
                    @endif
                    @if( isset($userDetail))
                    <div class="form-group col-md-12">
                        <label for="status" class="text-left">Status</label>
                        <select name="status" id="status" class="form-control">
                            <option value="1" @if(isset($userDetail)) {{ ($userDetail->status == 1)?'selected':'' }}
                                @endif>Enabled</option>
                            <option value="2" @if(isset($userDetail)) {{ ($userDetail->status == 2 || $userDetail->status == 0)?'selected':'' }}
                                @endif>Disabled</option>
                        </select>
                    </div>
                    @endif
                    @if(LD::checkFeatureFlag('mass-text-template-option'))
                    <div class="form-group col-md-12">
                        <label for="name" class="text-left">Twilio Phone Number</label>
                        <input type="text" class="form-control" id="sending_sms_number"
                        name="sending_sms_number"
                        value="{{ isset($userDetail)?str_replace('+1','',$userDetail->sending_sms_number):''}}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="name" class="text-left">Phone Number</label>
                        <input type="text" class="form-control" id="phone_number"
                        name="phone_number"
                        value="{{ isset($userDetail)?$userDetail->phone_number:''}}">
                    </div>
                    @else
                    <div class="form-group col-md-12">
                        <label for="name" class="text-left">Phone Number</label>
                        <input type="text" class="form-control" id="sending_sms_number" name="sending_sms_number" value="{{ isset($userDetail)?str_replace('+1','',$userDetail->sending_sms_number):''}}">
                    </div>
                    @endif
                    <div class="form-group col-md-12">
                        <label for="signature" class="text-left">Email Signature</label>
                        <textarea name="signature" id="signature" class="tinymce">{{ isset($userDetail)?$userDetail->signature:''}}</textarea>
                    </div>
                    @if(H::canPermissions('manageusers.edit'))
                    @if(isset($userDetail))
                    <div class="form-group col-md-12">
                        <label for="activity_log" class="text-left">Activity Log</label>
                        @php
                            $logData = H::getHelper("ActivityNotes")->getActivityNotes(['entity' => 'profile-change', 'entity_id' => $userDetail->id]);
                        @endphp
                        <div style="width: 100%; overflow-y: scroll; height: 225px;">
                        @if(count($logData) > 0)
                        <table style="width: 100%;border: 1px solid #dee2e6;" class="table table-striped" summary="User Form" aria-hidden="true">
                            @foreach ($logData->toArray() as $data)
                                <tr>
                                    <td class="text-left">{!!$data['notes']!!}</td>
                                    <td class="text-left">{{date("M d Y", strtotime($data['created_at'])) . ' at ' . date("g:i A", strtotime($data['created_at']))}}</td>
                                </tr>
                            @endforeach
                        </table>
                        @endif
                        </div>
                    </div>
                    @endif
                    @endif
                </div>


    </div>
    <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
    @canPermission('manageusers.sendpasswordemail')
    @if(isset($userDetail))<button type="button" class="btn btn-primary" id="sendpassemailbtn">Send Email For New
        Password</button>@endif

    </form>
    @if( isset($userDetail))
    <form method="POST" id="sendpassemail" action="{{ route('manageusers.sendpasswordemail') }}">
        @csrf
        <input type="hidden" name="id" id="id" value="{{$userDetail->id}}">
    </form>
    @endif
    @endcanPermission
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $('#branchid').multiselect({
            includeSelectAllOption: true
        });
    });
</script>
@if(LD::checkFeatureFlag('mass-text-template-option'))
<script>
$(document).ready(function() {
    $('#phone_number').on('keypress', function (e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57) && e.which != 45) {
            return false;
        }
        $(this).attr('maxlength', '14');
    });

    $('#phone_number').bind('paste', function (e) {
        e.preventDefault();
        var inputValue = e.originalEvent.clipboardData.getData('Text');
        inputValue = String(inputValue.replace(/[^0-9-]/g, ""));
        $(this).val(inputValue);
        $(this).val('');
        inputValue = inputValue.substring(0, 14);
        $(this).val(inputValue);
    });

});
</script>
@endif
<script type="text/javascript">
var selectedSuperior = "<?php echo $superUser?>";

function showPasswordSection() {
    $("#passwordSection").toggle();
}
$(document).ready(function() {
    $('#sending_sms_number').on('keypress', function (e) {
        if (e.which != 8 && e.which != 0 && (e.which < 48 || e.which > 57)) {
            return false;
        }
        $(this).attr('maxlength', '14');
    });
    $('#sending_sms_number').bind('paste', function (e) {
        e.preventDefault();
        var inputValue = e.originalEvent.clipboardData.getData('Text');
        inputValue = String(inputValue.replace(/[^0-9.]/g, ""));
        $(this).val(inputValue);
        $(this).val('');
        inputValue = inputValue.substring(0, 14);
        $(this).val(inputValue);
    });
    @if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit'))
    $('#sms_radius_limit_dropdown').on('change', function (e) {

        if($(this).val()=='other'){
            $("#sms_radius_limit").removeClass("hide");
            $("#sms_radius_limit").val("");
        } else {

            $("#sms_radius_limit").addClass("hide");

            $("#sms_radius_limit").val($(this).val());
        }
    });
    @endif
});
// Wait for the DOM to be ready
$(function() {

    <?php if(LD::checkFeatureFlag('multiple-roles-to-user')){ ?>
        $('#role').multiselect({includeSelectAllOption: true,enableFiltering: true});
    <?php } ?>
    <?php if(LD::checkFeatureFlag('add-facilityid-to-users')){ ?>
        $('#facilityid').multiselect({includeSelectAllOption: true,enableFiltering: true});
    <?php } ?>
    //Showing the recruiting section based on the role department
    var dept = $('#role').find(':selected').map(function() {
        return $(this).attr('data-department');
    }).get();

    if (dept.includes('Recruiting')) {
        $('.recruiting-section').show();
    } else {
        $('.recruiting-section').hide();
    }
    @if(LD::checkFeatureFlag('add-billing-teamlead-and-manager-in-user-form'))
        let selectedRoles = $('#role').val();
        if (selectedRoles.includes('51')) {
            $('.billing-section').show();
        } else {
            $('.billing-section').hide();
        }

        $(document).on('change', '#role', function(e) {
        var selectedRoles = $('#role').val();
            if (selectedRoles.includes('51')) {
                $('.billing-section').show();
            } else {
                $('.billing-section').hide();
            }
        });
    @endif
    $(document).on('change', '#role', function(e) {
        var dept = $(this).find(':selected').map(function() {
            return $(this).attr('data-department');
        }).get();
        if (dept.includes('Recruiting')) {
            $('.recruiting-section').show();
        } else {
            $('.recruiting-section').hide();
        }
    });

    // Initialize form validation on the registration form.
    // It has the name attribute "registration"
    $("#add-form-validate").validate({
        rules: {
            name: "required",
            password: "required",
            password_confirmation: "required",
            email: {
                required: true,
                email: true
            },
            @if(LD::checkFeatureFlag('add-coverage-booked-goal-field') && (isset($userDetail) && $haveDepartment == 1))
            coveragebookedgoal: {
                required: false,
                digits: true
            },
            @endif
            user_timezone: "required"
        },
        messages: {
            name: "Please enter Name",
            email: "Please enter a valid email address",
            password: "Please enter password",
            password_confirmation: "Please enter confirmation password",
            user_timezone: "Please select timezone",
            coveragebookedgoal: "Please enter numerical values"
        },
        errorPlacement: function() {
            return true;
        }
    });


    $('#sendpassemailbtn').click(function() {
        $("#sendpassemail").submit();
    });

	$("#accordion").accordion();
});

	const imageUploadAjax = (blobInfo,progress) => new Promise((resolve,reject) => {
        const formData = new FormData();
        formData.append('file', blobInfo.blob(), blobInfo.filename());
		formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
        $.ajax({
            url: "{{route('profile.uploadfiles')}}",
            method: "POST",
            data: formData,
			cache: false,
			contentType: false,
			processData: false,
            success: function(data) {
                if (data.status == 'true') {
					resolve(data.location);
				}
            },
            error: function(data) {
                reject("Error Uploading Image");
            },
        });

    });

	tinymce.init({
		selector: 'textarea.tinymce',
		plugins: 'image code',
		toolbar: 'undo redo bold italic underline alignleft aligncenter alignright alignjustify image code',
		height: 400,
		relative_urls : false,
        remove_script_host : false,
        convert_urls : true,
        images_upload_handler: imageUploadAjax,
	});

    @if(LD::checkFeatureFlag('add-intake-goal-field-in-user-form'))
        var is_Ic = <?php echo ($is_Ic !== null && $is_Ic == 1) ? '1' : 'null'; ?>;
        var ic_role_id = '<?php echo $ic_role_id ?>';
        if (is_Ic == 1) {
            $('.icclass').show();
            $('.icclass input').prop('disabled', false);
        } else {
            $('.icclass').hide();
            $('.icclass input').prop('disabled', true);
        }
        $('#role').change(function() {
            var selectedValues = $('#role').val();
            if (selectedValues.includes(ic_role_id)) {
                $('.icclass').show();
                $('.icclass input').prop('disabled', false);
            } else {
                $('.icclass').hide();
                $('.icclass input').prop('disabled', true);
            }
        });
    @endif
</script>
<!-- /.container-fluid -->
@endsection
