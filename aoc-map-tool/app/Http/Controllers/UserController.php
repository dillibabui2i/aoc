<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Helpers\CacheHelper;
use App\Helpers\H;
use App\Helpers\LD;
use App\Repositories\TrachVentClass\TrachVentClassReadRepository;
use App\Roles;
use App\Services\User\DisableUserService;
use App\UserPermissions;
use App\Permissions;
use App\RolesPermissions;
use App\User;
use App\BranchLocations;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\MssqlFacilities as MssqlFacilities;
use App\MssqlProviders;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * @param CacheHelper $cacheHelper
     */
    public function __construct(
        protected CacheHelper $cacheHelper,
        protected TrachVentClassReadRepository $trachVentClassReadRepository,
        protected DisableUserService $disableUserService,
    ) {
    }

    /**
     * Show the User list.
     */
    public function index()
    {
        return view('user.grid');
    }

    public function getParentPermissionUsers(Request $request)
    {
        $currentRole = $request->input('currentrole');
        $superiors = H::getHelper('UserPermissionResolver')->getSuperior($currentRole);
        $super = [];
        if (!empty($superiors)) {
            foreach ($superiors as $key => $superior) {
                $super[$key]['id'] = $superior->id;
                $super[$key]['name'] = $superior->name;
            }
        }

        if (!empty($super)) {
            return json_encode($super);
        } else {
            return json_encode([]);
        }
    }

    /**
     * Show the User ajax list.
     */
    public function getUserList(Request $request)
    {
        if(LD::checkFeatureFlag('add-kantime-id-in-user-grid')) {
            $columns = [
                0 => 'name',
                1 => 'email',
                2 => 'roles.name',
                4 => 'created_at',
            ];
        } else {
            $columns = [
                0 => 'name',
                1 => 'email',
                2 => 'roles.name',
                3 => 'created_at',
            ];
        }
        $newRoleName = '(select GROUP_CONCAT(name SEPARATOR ", ") from roles where id in (select role_id from users_has_roles where user_id = users.id))';
        if(LD::checkFeatureFlag('multiple-roles-to-user')){
            $userList = User::select('users.id','users.title','users.name','users.email','users.email_verified_at','users.last_password_change_date','users.recruiting_admin','users.recruiting_manager','users.recruiting_teamleader','users.branchid','users.recruiter','users.territory','users.status','users.profile_picture','users.is_deleted','users.kantime_username','users.signature','users.created_at',DB::raw($newRoleName.' as role'));
        }else{
            $userList = User::select('users.*','roles.name as role')->join('roles','roles.id','=','users.role');
        }

        if (isset($request->search['value']) && $request->search['value'] != '') {
            if(LD::checkFeatureFlag('multiple-roles-to-user')) {
                $userList = $userList->where(function($query) use ($request, $newRoleName) {
                    $searchValue = $request['search']['value'];
                    $query->where('users.name', 'like', "%{$searchValue}%")
                          ->orWhere('users.email', 'like', "%{$searchValue}%")
                          ->orWhere(DB::raw("{$newRoleName}"), 'like', "%{$searchValue}%");
                });
            } else {
                $userList = $userList->where(function($query) use ($request) {
                    $searchValue = $request['search']['value'];
                    $query->where('users.name', 'like', "%{$searchValue}%")
                          ->orWhere('users.email', 'like', "%{$searchValue}%")
                          ->orWhere('roles.name', 'like', "%{$searchValue}%");
                });

            }
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            if($columns[intval($request['order'][0]['column'])] == 'roles.name') {
                $userList = $userList->orderby('role', $request['order'][0]['dir']);
            } else {
                $userList = $userList->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
            }
        }
        $total_records = $userList->get();

        if (isset($request['start']) && $request['length'] != '-1') {
            $userList = $userList->offset($request['start'])->limit($request['length']);
        }
        $userList = $userList->get();

        $cnt = 0;
        foreach ($userList as $userData) {
            $display_created = '-';
            if($userData['created_at'] != '') {
                $display_created = date('m/d/Y H:i:s', strtotime($userData['created_at']));
            }
            $userList[$cnt]['display_created'] = $display_created;

            $btn = '';
            if (H::canPermissions('manageusers.edit')) {
                $btn .= '<a href="' . url('/manageusers/edit', $userData['id']) . '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
            }
            if (H::canPermissions('manageusers.disable')) {
                if ($userData['status'] == 1) {
                    $btn = $btn . '&nbsp|&nbsp <a href="javascript:void(0)" class="disable-it"  data-href="' . url('/manageusers/disable', $userData['id']) . '"  class="disable btn btn-info btn-sm"><i class="fa fa-ban" title="Disable"></i></a>';
                }
            }
            if (H::canPermissions(['manageusers.edit', 'manageusers.disable'])) {
                $userList[$cnt]['action'] = $btn;
            }
            if(LD::checkFeatureFlag('add-kantime-id-in-user-grid')){
                $kantimeId = MssqlProviders::where('AOC_Email', $userData['email'])->value('ClinicianID');
                $userList[$cnt]['kantimeid'] = !empty($kantimeId) ? $kantimeId : '-';
            }
            $userList[$cnt]['disaply_status'] = ($userData['status'] == 1) ? 'Enabled' : 'Disabled';
            $userList[$cnt]['disaply_role'] = $userData['role'];
            $cnt++;
        }
        $data = [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $userList,
        ];

        return $data;
    }

    /**
     * User Add.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function add()
    {
        return view('user.form');
    }

    public function saveuser(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' =>'min:6|required_with:password_confirmation|same:password_confirmation',
        ]);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        //Resolve Array to string error - Nayan - 30 Aug 2022
        $branchidStr = implode(",", $request->branchid??[]);
        $facilityids = implode(",", $request->facilityid??[]);
        $roleids = implode(",", $request->role??[]);
        $request->request->add(['branchid' => $branchidStr]);
        $request->request->add(['facilityid' => $facilityids]);

        $newPassword = User::generateRandomKey(9);
        $sending_sms_number = isset($request->sending_sms_number) ? "+1".$request->sending_sms_number : '';
        $request->request->add(['role'=>$roleids, 'password' => Hash::make($newPassword),'sending_sms_number' => $sending_sms_number]);
        if(LD::checkFeatureFlag('mass-text-template-option')) {
            $phone_number = isset($request->phone_number) ? $request->phone_number : '';
            $request->request->add(['phone_number' => $phone_number]);
        }

        $userDetail = User::create($request->except(['_token', 'password_confirmation']));
        //User::sendPasswordEmail($request->email,$newPassword,'add');
        User::sendPasswordLink($request, 'add');
        //Inserting the multiple roles
        if(LD::checkFeatureFlag('multiple-roles-to-user')){
            $this->updateRoles(explode(",",$roleids),$userDetail->id);
        }
        //Update Manager Id
        $this->updateManagerId($userDetail->id);
        return redirect('/manageusers')->with('success', 'User added!');
    }

    public function edit($id)
    {
        $userDetail = User::find($id);
		$permissions = new Permissions();
        $permissionData = $permissions->get()->toArray();

        $permissionsDetail = [];
        foreach ($permissionData as $data) {
            $permissionsDetail[$data['group']][] = $data;
        }
        $userSelectedPermissions = array_values(array_column(UserPermissions::where('user_id', $userDetail->id)->get()->toArray(), 'permissions_id'));
        $roles = H::getHelper('ConstantHelper')->getCurrentRolesByUserId($id);
        $haveDepartment = User::checkRoleDepartment('Client Care Coordinator', $roles);
        $rolePermission = RolesPermissions::select('*')->whereIn('roles_id', $roles)->get()->toArray();
        $selectedPermissions = array_values(array_column($rolePermission, 'permissions_id'));

        $roleDetail = Roles::where('id', $userDetail->role)->first();
        $roleName = isset($roleDetail->name) ? $roleDetail->name : '';
        $kantimeId = '';
        if(LD::checkFeatureFlag('add-kantime-id-in-user-grid')){
            $kantimeId = MssqlProviders::where('AOC_Email', $userDetail->email)->value('ClinicianID');
        }

        return view('user.form', compact('userDetail', 'permissionsDetail', 'selectedPermissions','userSelectedPermissions','roleName', 'kantimeId', 'haveDepartment'));
    }

    public function updateRoles($roles, $userId): void
    {
        DB::table('users_has_roles')->where('user_id',$userId)->delete();
        $insertRoles = [];

        foreach ($roles as $key => $value) {
            $insertRoles[] = ['user_id'=>$userId,'role_id'=>$value];
        }

        if(!empty($insertRoles)){
            DB::table('users_has_roles')->insert($insertRoles);
        }

        $this->cacheHelper->forgetUserPermissionsCache($userId);
    }
    public function updateuser(Request $request): RedirectResponse {
        $validationArr = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $request->id,
        ];
        if (isset($request->change_password_required) && $request->change_password_required == 1) {
            $validationArr = array_merge($validationArr, ['password' =>'min:6|required_with:password_confirmation|same:password_confirmation']);
        }
        $validator = Validator::make($request->all(), $validationArr);
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
        try {
            $labelArr = [
                'title' => 'Title',
                'name' => 'Name',
                'email' => 'Email Address',
                'role' => 'Role(s)',
                'alternateRole' => 'Alternate Role',
                'recruiting_admin' => 'Recruiting Admin',
                'recruiting_manager' => 'Supervisor',
                'recruiting_teamleader' => 'Recruiting Team Leader',
                'branchid' => 'Associated IPEA Locations',
                'territory' => 'Territory',
                'status' => 'Status',
                'signature' => 'Email Signature',
                'kantime_username' => 'KanTime User',
                'bilingual' => 'Bilingual',
                'facilityid' => 'Associated KanTime Branches',
                'sending_sms_number' => 'Phone Number',
                'user_timezone' => 'TimeZone',
                'permissions' => 'Additional Permissions',
                'is_instructor' => 'Class Instructor',
            ];
            if(LD::checkFeatureFlag('mass-text-template-option')) {
                $labelArr['sending_sms_number'] = 'Twilio Phone Number';
                $labelArr['phone_number'] = 'Phone Number';
            }
            if(LD::checkFeatureFlag('add-intake-goal-field-in-user-form')) {
                $labelArr['ic_goal'] = 'intake Goal';
            }

            if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
                $labelArr['sms_radius_limit'] = 'SMS Radius Limit';
            }
            if(LD::checkFeatureFlag('add-billing-teamlead-and-manager-in-user-form')) {
                $labelArr['billing_team_lead'] = 'Billing Team Lead';
                $labelArr['billing_team_manager'] = 'Billing Manager';
            }
            $existingUser = User::where('id', $request->id)
                                    ->first()
                                    ->makeHidden([
                                        'email_verified_at',
                                        'last_password_change_date',
                                        'google2fa_secret',
                                        'created_at',
                                        'created_by',
                                        'updated_at',
                                        'updated_by',
                                        'is_deleted',
                                        'profile_picture',
                                        'manager_id',
                                        'exec_manager_id',
                                        'recruiter',
                                    ])
                                    ->toArray();

            $postData = $request->all();

            $postData['role'] = implode(",", $postData['role']??[]);
            if (H::canPermissions('additional.permission')) {
                $postData['permissions'] = $postData['permissions']??[];
                sort($postData['permissions']);
                $postData['permissions'] = implode(",", $postData['permissions']);
            }
            $postData['branchid'] = implode(",", $postData['branchid']??[]);
            if (LD::checkFeatureFlag('add-facilityid-to-users')) {
                $postData['facilityid'] = implode(",", $postData['facilityid']??[]);
            }
            $postData['sending_sms_number'] = (!empty($postData['sending_sms_number']) ? "+1".$postData['sending_sms_number'] : '');
            if(LD::checkFeatureFlag('mass-text-template-option')) {
                $postData['phone_number'] = (!empty($postData['phone_number']) ? $postData['phone_number'] : '');
            }
            unset($postData['_token']);
            unset($postData['user_selected_permissions']);

            if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
                unset($postData['sms_radius_limit_dropdown']);
            }


            foreach ($existingUser as $key => $value) {
                if (array_key_exists($key, $postData) === false) {
                    unset($existingUser[$key]);
                }
                if ($key == 'role') {
                    $roles = DB::table('roles')->whereIn('id', H::getHelper('ConstantHelper')->getCurrentRolesByUserId($request->id))->orderBy('name', 'ASC')->pluck('id')->toArray();
                    $existingUser[$key] = implode(",", $roles);
                }
            }
            if (H::canPermissions('additional.permission')) {
                $user_permissions = DB::table('user_has_permission')->where('user_id', $request->id)->orderBy('permissions_id', 'ASC')->pluck('permissions_id')->toArray();
                $existingUser['permissions'] = implode(",", $user_permissions);
            }

            $oldValues = array_diff_assoc($existingUser, $postData);
            $newValues = array_diff_assoc($postData, $existingUser);

            $facilityids =  H::getHelper('ConstantHelper')->getAssociatedKanTimeBranches()->toArray();
            $branchLocation = H::getHelper('ConstantHelper')->getBranches()->toArray();
            $recruitingTeamLeaders = H::getHelper("UserPermissionResolver")->getAllTeamLeader();
            $recruitingTeamLeaders = json_decode(json_encode($recruitingTeamLeaders),true);
            $recruitingManagers = H::getHelper("UserPermissionResolver")->getAllManager();
            $recruitingManagers = json_decode(json_encode($recruitingManagers),true);
            $recruitingadmin = H::getHelper("UserPermissionResolver")->getRecruitingAdmin();
            $recruitingadmin = json_decode(json_encode($recruitingadmin),true);

            $needToReactOnUserDisabled = false;
            foreach ($oldValues as $oldKey => $oldValue) {
                if ($oldKey == 'facilityid') {
                    $oldFacilityName = [];
                    $newFacilityName = [];
                    foreach(array_filter(explode(",", $oldValues[$oldKey])) as $facilityid) {
                        $oldFacilityName[] = $facilityids[array_search($facilityid, array_column($facilityids, 'id'))]['FacilityName'];
                    }
                    $oldValues[$oldKey] = implode(", ", $oldFacilityName);

                    foreach(array_filter(explode(",", $newValues[$oldKey])) as $facilityid) {
                        $newFacilityName[] = $facilityids[array_search($facilityid, array_column($facilityids, 'id'))]['FacilityName'];
                    }
                    $newValues[$oldKey] = implode(", ", $newFacilityName);
                }
                if ($oldKey == 'branchid') {
                    $oldBranchName = [];
                    $newBranchName = [];
                    foreach(array_filter(explode(",", $oldValues[$oldKey])) as $branchid) {
                        $oldBranchName[] = $branchLocation[array_search($branchid, array_column($branchLocation, 'id'))]['branch'];
                    }
                    $oldValues[$oldKey] = implode(", ", $oldBranchName);

                    foreach(array_filter(explode(",", $newValues[$oldKey])) as $branchid) {
                        $newBranchName[] = $branchLocation[array_search($branchid, array_column($branchLocation, 'id'))]['branch'];
                    }
                    $newValues[$oldKey] = implode(", ", $newBranchName);
                }
                if ($oldKey == 'bilingual') {
                    $oldValues[$oldKey] = ($oldValues[$oldKey] == 0) ? 'No' : 'Yes';
                    $newValues[$oldKey] = ($newValues[$oldKey] == 0) ? 'No' : 'Yes';
                }
                if ($oldKey == 'is_instructor') {
                    $oldValues[$oldKey] = ($oldValues[$oldKey] == 0) ? 'No' : 'Yes';
                    $newValues[$oldKey] = ($newValues[$oldKey] == 0) ? 'No' : 'Yes';
                }
                if ($oldKey == 'status') {
                    $needToReactOnUserDisabled = $newValues[$oldKey] == 2 && $oldValues[$oldKey] !== $newValues[$oldKey]
                        && LD::checkFeatureFlag('add-instructor-field-to-user-profile');
                    $oldValues[$oldKey] = ($oldValues[$oldKey] == 1) ? 'Enabled' : 'Disabled';
                    $newValues[$oldKey] = ($newValues[$oldKey] == 1) ? 'Enabled' : 'Disabled';
                }
                if ($oldKey == 'user_timezone') {
                    $oldValues[$oldKey] = strtok($oldValues[$oldKey], '#');
                    $newValues[$oldKey] = strtok($newValues[$oldKey], '#');
                }
                if ($oldKey == 'role' || $oldKey == 'alternateRole') {
                    $oldValues[$oldKey] = implode(", ", DB::table('roles')->whereIn('id', explode(",", $oldValues[$oldKey]))->orderBy('name', 'ASC')->pluck('name')->toArray());
                    $newValues[$oldKey] = implode(", ", DB::table('roles')->whereIn('id', explode(",", $newValues[$oldKey]))->orderBy('name', 'ASC')->pluck('name')->toArray());
                }
                if ($oldKey == 'permissions') {
                    $oldValues[$oldKey] = implode(", ", DB::table('permissions')->whereIn('id', explode(",", $oldValues[$oldKey]))->orderBy('name', 'ASC')->pluck('name')->toArray());
                    $newValues[$oldKey] = implode(", ", DB::table('permissions')->whereIn('id', explode(",", $newValues[$oldKey]))->orderBy('name', 'ASC')->pluck('name')->toArray());
                }
                if ($oldKey == 'recruiting_admin') {
                    $oldValues[$oldKey] = $recruitingadmin[array_search($oldValues[$oldKey], array_column($recruitingadmin, 'id'))]['name'];
                    $newValues[$oldKey] = $recruitingadmin[array_search($newValues[$oldKey], array_column($recruitingadmin, 'id'))]['name'];
                }
                if ($oldKey == 'recruiting_manager') {
                    $oldValues[$oldKey] = $recruitingManagers[array_search($oldValues[$oldKey], array_column($recruitingManagers, 'id'))]['name'];
                    $newValues[$oldKey] = $recruitingManagers[array_search($newValues[$oldKey], array_column($recruitingManagers, 'id'))]['name'];
                }
                if ($oldKey == 'recruiting_teamleader') {
                    $oldValues[$oldKey] = $recruitingTeamLeaders[array_search($oldValues[$oldKey], array_column($recruitingTeamLeaders, 'id'))]['name'];
                    $newValues[$oldKey] = $recruitingTeamLeaders[array_search($newValues[$oldKey], array_column($recruitingTeamLeaders, 'id'))]['name'];
                }
                $logData = [];
                $logData['entity'] = 'profile-change';
                $logData['entity_id'] = $request->id;

                if (!empty($oldValues[$oldKey]) && !empty($newValues[$oldKey])) {
                    $logData['notes'] = $labelArr[$oldKey] . ' updated from ' . $oldValues[$oldKey] . ' to ' . (is_array($newValues[$oldKey]) ? implode(",", $newValues[$oldKey]) : $newValues[$oldKey]) . ' by ' . Auth::user()->name;
                } else if (!empty($oldValues[$oldKey]) && empty($newValues[$oldKey])) {
                    $logData['notes'] = $labelArr[$oldKey] . ' ('.$oldValues[$oldKey].') removed by ' . Auth::user()->name;
                } else if (empty($oldValues[$oldKey]) && !empty($newValues[$oldKey])) {
                    $logData['notes'] = $labelArr[$oldKey] . ' ('.(is_array($newValues[$oldKey]) ? implode(",", $newValues[$oldKey]) : $newValues[$oldKey]).') added by ' . Auth::user()->name;
                }
                H::getHelper("ActivityNotes")->setActivityNotes($logData);
            }

            $updateArr = ['recruiting_admin'=>$request->recruiting_admin, 'recruiting_manager'=>$request->recruiting_manager, 'recruiting_teamleader'=>$request->recruiting_teamleader, 'branchid'=>$request->branchid, 'recruiter'=>$request->recruiter, 'name'=>$request->name, 'email'=>$request->email, 'role'=>$request->role ?? 1, 'status'=>$request->status, 'territory'=>$request->territory, 'facilityid' => $request->facilityid];

			$updateArr['branchid'] = implode(",", $updateArr['branchid']??[]);
			if (LD::checkFeatureFlag('add-facilityid-to-users')) {
				$updateArr['facilityid'] = implode(",", $updateArr['facilityid']??[]);
			}

            if(empty($request->permissions)) {
                $request->permissions = [];
            }
            $userSelectedPermissions = explode(",",$request->user_selected_permissions);
            $removeEntry = array_diff($userSelectedPermissions, $request->permissions);
            if (count($removeEntry)) {
                UserPermissions::where('user_id', $request->id)->whereIn('permissions_id', $removeEntry)->delete();
            }
            $addNewEntry = array_diff($request->permissions, $userSelectedPermissions);

            $this->cacheHelper->forgetUserPermissionsCache($request->id);

            if (count($addNewEntry)) {
                $data = [];
                foreach($addNewEntry as $key => $value) {
                    $data[] = ['permissions_id' => $value, 'user_id' => $request->id];
                }
                UserPermissions::insert($data);
            }

            if(LD::checkFeatureFlag('add-bilingual-on-user-page')){
                $updateArr['bilingual'] = $request->bilingual;
            }
            $updateArr['kantime_username'] = $request->kantime_username??'';
            $updateArr['title'] = $request->title;
            $updateArr['signature'] = $request->signature;
            $updateArr['alternateRole'] = isset($request->alternateRole) ? $request->alternateRole : '';
            $updateArr['is_instructor'] = $request->is_instructor ?? 0;
            if(LD::checkFeatureFlag('multiple-roles-to-user')){
                unset($updateArr['role']);
            }
            $updateArr['user_timezone'] = isset($request->user_timezone) ? $request->user_timezone : '';
            $updateArr['sending_sms_number'] = isset($request->sending_sms_number) ? "+1".$request->sending_sms_number : '';
            if(LD::checkFeatureFlag('mass-text-template-option')) {
                $updateArr['phone_number'] = isset($request->phone_number) ? $request->phone_number : '';
            }
            if(LD::checkFeatureFlag('add-coverage-booked-goal-field')){
                $updateArr['coverage_booked_goal'] = isset($request->coveragebookedgoal)
                ? $request->coveragebookedgoal : null;
            }
            if(LD::checkFeatureFlag('add-intake-goal-field-in-user-form')){
                $updateArr['ic_goal'] = isset($request->ic_goal)
                ? $request->ic_goal : null;
            }
            if(LD::checkFeatureFlag('add-billing-teamlead-and-manager-in-user-form')) {
                $updateArr['billing_team_lead'] = isset($request->billing_team_lead)
                ? $request->billing_team_lead : null;
                $updateArr['billing_team_manager'] = isset($request->billing_team_manager)
                ? $request->billing_team_manager : null;
            }

            if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')){
                $updateArr['sms_radius_limit'] = is_numeric($request->sms_radius_limit)
                ? $request->sms_radius_limit : 50;
            }

            User::where('id', $request->id)->update($updateArr);
            if ($needToReactOnUserDisabled) {
                $user = User::findOrFail($request->id);
                $this->disableUserService->execute($user);
            }

            //Inserting the multiple roles
            if(LD::checkFeatureFlag('multiple-roles-to-user')){
                $this->updateRoles($request->input('role'), $request->id);

                $this->cacheHelper->forgetUserPermissionsCache($request->id);
            }
            //Update Manager Id
            $this->updateManagerId($request->id);
            return redirect('/manageusers')->with('success', 'User updated!');
        } catch (\Exception $e) {

            return redirect('/manageusers/edit/' . $request->id)->with('error', 'Something went wrong.');
        }
    }
    public function updateManagerId($userId){

        H::ExcelLog($userId,'updateManager.log');
        $updateQuery = "update `users`
        left join users supervisors on users.recruiting_manager = supervisors.id
        left join users managers on supervisors.recruiting_manager = managers.id
        set users.manager_id = supervisors.recruiting_manager
        where users.status = 1 and ifnull(users.manager_id,'') <> ifnull(supervisors.recruiting_manager,'')";
        H::ExcelLog($updateQuery,'updateManager.log');
        DB::statement($updateQuery);

    }
    public function destroy($id)
    {
        $user = User::find($id);
        $user->delete();

        return redirect('/manageusers')->with('success', 'User deleted!');
    }

    public function disable($id): RedirectResponse
    {
        $user = User::findOrFail($id);
        if ($user->status == UserStatus::DISABLED) {
            return redirect('/manageusers');
        }

        if (LD::checkFeatureFlag('add-instructor-field-to-user-profile')) {

            $futureClasses = $this->trachVentClassReadRepository->getInstructorFutureClasses($user->id)->toArray();

            $this->disableUserService->execute($user);

            return redirect('/manageusers')->with('warningDisabledUserHasTrachVentClasses', [
                'classes' => $futureClasses,
                'userName' => $user->name,
            ]);
        }

        User::where('id', $id)->update(['status' => UserStatus::DISABLED->value]);

        return redirect('/manageusers')->with('success', 'User disabled!');
    }

    public function sendPasswordEmail(Request $request)
    {
        $userDetail = User::where('id', $request->id)->first();
        $request->request->add(['email'=>$userDetail->email, 'name'=>$userDetail->name]);
        User::sendPasswordLink($request);
        //return redirect('/manageusers/edit/'.$request->id)->with('success', 'Email with new password has been sent to user!');
        $successMsg = 'Email for new password instructions has been sent to user!';
        session()->flash('success', $successMsg);

        return redirect('/manageusers/edit/' . $request->id);
    }

    public function getListOfUsersForAutoComplete(Request $request)
    {
        $term = $request->term;
        $users = User::where('name','like','%'.$term.'%')->where('status', 1)->get()->toArray();
        $data = [];
        foreach ($users as $user) {
            $data[] = ['id' => $user['id'],'label' => $user['name'] , 'value' => $user['name']];
        }
        return response()->json($data);
    }

    public function exportUserData(Request $request){
        $columns = [
            0 => 'name',
            1 => 'email',
            2 => 'roles.name',
            3 => 'created_at',
        ];


        if(LD::checkFeatureFlag("user-export-add-manager-column")) {

            $dataTableFields = [
                'name' => 'Name',
                'email' => 'Email',
                'title' => 'Title',
                'role' => 'Roles',
                'branchid' => 'Branches',
                'status' => 'Status',
                'facilityid' => 'facilityid',
                'recruiting_manager' => 'Supervisor',
                'user_manager' => 'Manager',
                'kantimeid' => 'KantimeId',
                'timezone' => 'Timezone',
                'bilingual' => 'Bilingual',
                'territory' => 'Territory',
                'recruiting_teamleader' => 'Recruiting Team Leader',
                'recruiting_admin' => 'Recruiting Admin',
                'permission' => 'Permission'
            ];

        } else {
            if(LD::checkFeatureFlag('add-kantime-id-in-user-grid')){
                $dataTableFields = [
                    'name' => 'Name',
                    'email' => 'Email',
                    'title' => 'Title',
                    'role' => 'Roles',
                    'branchid' => 'Branches',
                    'status' => 'Status',
                    'facilityid' => 'facilityid',
                    'recruiting_manager' => 'Supervisor',
                    'kantimeid' => 'KantimeId',
                    'timezone' => 'Timezone',
                    'bilingual' => 'Bilingual',
                    'territory' => 'Territory',
                    'recruiting_teamleader' => 'Recruiting Team Leader',
                    'recruiting_admin' => 'Recruiting Admin',
                    'permission' => 'Permission'
                ];
            }else{
                $dataTableFields = [
                    'name' => 'Name',
                    'email' => 'Email',
                    'title' => 'Title',
                    'role' => 'Roles',
                    'branchid' => 'Branches',
                    'status' => 'Status',
                    'facilityid' => 'facilityid',
                ];
            }
        }



        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=manageuserData.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        if(LD::checkFeatureFlag('fix-export-user-query')){
            $newRoleName = '(select GROUP_CONCAT(name SEPARATOR ", ") from roles
            where id in (select role_id from users_has_roles where user_id = users.id))';
            if(LD::checkFeatureFlag('user-export-add-manager-column')) {
                $userList = User::select('users.*', DB::raw($newRoleName.' as role'),
                'umanager.name as user_manager','usupervisor.name as recruiting_manager')
                ->Leftjoin('roles','roles.id','=','users.role')
                ->Leftjoin('users as umanager','umanager.id','=','users.manager_id')
                ->Leftjoin('users as usupervisor','usupervisor.id','=','users.recruiting_manager');
            } else {
                $userList = User::select('users.*','roles.name as role')
                ->join('roles','roles.id','=','users.role');
            }
            if (isset($request->search['value']) && $request->search['value'] != '') {
                if(LD::checkFeatureFlag('multiple-roles-to-user')) {
                    $userList = $userList->where(function($query) use ($request, $newRoleName) {
                        $searchValue = $request['search']['value'];
                        $query->where('users.name', 'like', "%{$searchValue}%")
                              ->orWhere('users.email', 'like', "%{$searchValue}%")
                              ->orWhere(DB::raw("{$newRoleName}"), 'like', "%{$searchValue}%");
                    });
                } else {
                    $userList = $userList->where(function($query) use ($request) {
                        $searchValue = $request['search']['value'];
                        $query->where('users.name', 'like', "%{$searchValue}%")
                              ->orWhere('users.email', 'like', "%{$searchValue}%")
                              ->orWhere('roles.name', 'like', "%{$searchValue}%");
                    });
                }
            }
            if (isset($request['order'][0]) && $request['order'][0] != '') {
                if($columns[intval($request['order'][0]['column'])] == 'roles.name') {
                    $userList = $userList->orderby('role', $request['order'][0]['dir']);
                } else {
                    $userList = $userList->orderby($columns[intval($request['order'][0]['column'])],
                    $request['order'][0]['dir']);
                }
            }
            if (isset($request['start']) && $request['length'] != '-1') {
                $userList = $userList->offset($request['start'])->limit($request['length']);
            }
            $userList = $userList->get();
        }else{
            if(LD::checkFeatureFlag('user-export-add-manager-column')) {
                $userList = User::select('users.*','roles.name as role','umanager.name as user_manager','usupervisor.name as recruiting_manager')
                ->join('roles','roles.id','=','users.role')
                ->join('users as umanager','umanager.id','=','users.manager_id')
                ->join('users as usupervisor','usupervisor.id','=','users.recruiting_manager');
            } else {
                $userList = User::select('users.*','roles.name as role')
                ->join('roles','roles.id','=','users.role');
            }
            if (isset($request->search['value']) && $request->search['value'] != '') {
                $userList = $userList->where(function($query) use ($request) {
                    $searchValue = $request['search']['value'];
                    $query->where('users.name', 'like', "%{$searchValue}%")
                          ->orWhere('users.email', 'like', "%{$searchValue}%")
                          ->orWhere('roles.name', 'like', "%{$searchValue}%");
                });
            }
            if (isset($request['order'][0]) && $request['order'][0] != '') {
                $userList = $userList->orderby($columns[intval($request['order'][0]['column'])],
                $request['order'][0]['dir']);
            }
            $userList = $userList->get();
        }
        $columns = [];

        foreach ($dataTableFields as $k => $v) {
            $columns[] = $v;
        }
        $recruitingTeamLeaders = H::getHelper("UserPermissionResolver")->getAllTeamLeader();
        $recruitingManagers = H::getHelper("UserPermissionResolver")->getAllManager();
        $recruitingadmin = H::getHelper("UserPermissionResolver")->getRecruitingAdmin();
        $timezoneList = H::getHelper('ConstantHelper')->getTimeZoneList();
        $permissions = new Permissions();
        $permissionData = $permissions->get()->toArray();

        $permissionsDetail = [];
        foreach ($permissionData as $data) {
            $permissionsDetail[$data['group']][] = $data;
        }
        $callback = function () use ($userList, $columns, $dataTableFields, $recruitingManagers, $timezoneList, $recruitingTeamLeaders, $recruitingadmin, $permissionsDetail) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($userList as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($dataTableFields as $fields => $val) {
                    if($fields == 'name'){
                        $csvData[] = $tableData->{$fields};
                    }if($fields == 'email'){
                        $csvData[] = $tableData->{$fields};
                    }if($fields == 'title'){
                        $csvData[] = $tableData->{$fields};
                    }if($fields == 'role'){
                        $csvData[] = $tableData->{$fields};
                    }if($fields == 'branchid'){
                        $branchid = explode(",", $tableData->{$fields});
                        $branchs = BranchLocations::select('branch_locations.branch')->wherein('branch_locations.id', $branchid)->get()->toArray();
                        $branchname = [];
                        foreach($branchs as $branch){
                            $branchname[] = $branch['branch'];
                        }
                        $branch = implode(",", $branchname);
                        $csvData[] = $branch;
                    }if($fields == 'status'){
                        $csvData[] = $tableData->{$fields} == 1 ? 'Active' : 'Inactive';
                    }if($fields == 'facilityid'){
                        $facilityid = $tableData->{$fields};
                        $csvData[] = $facilityid;
                    }
                    if(LD::checkFeatureFlag('user-export-add-manager-column')) {
                        if($fields == 'user_manager'){
                            $manager = $tableData->{$fields};
                            $csvData[] = $manager;
                        }
                    }
                    if(LD::checkFeatureFlag('add-kantime-id-in-user-grid')){
                        if(LD::checkFeatureFlag('user-export-add-manager-column')) {
                            if($fields == 'recruiting_manager'){
                                $supervisor = $tableData->{$fields};
                                $csvData[] = $supervisor;
                            }
                        } else {
                            if($fields == 'recruiting_manager'){
                                $supervisor = '';
                                if(!empty($recruitingManagers)){
                                    foreach($recruitingManagers as $manager){
                                        if($tableData->{$fields} == $manager->id){
                                            $supervisor = $manager->name;
                                        }
                                    }
                                }
                                $csvData[] = $supervisor;
                            }
                        }

                        if($fields == 'kantimeid'){
                            $kantimeId = MssqlProviders::where('AOC_Email', $tableData->email)->value('ClinicianID');
                            $csvData[] = $kantimeId;
                        }
                        if($fields == 'timezone') {
                            $timezone = '';
                            foreach($timezoneList as $key => $value){
                                if($tableData->user_timezone == $key){
                                    $timezone = $value;
                                }
                            }
                            $csvData[] = $timezone;
                        }
                        if($fields == 'bilingual'){
                            $bilingual = ($tableData->{$fields} == 1) ? 'Yes' : 'No';
                            $csvData[] = $bilingual;
                        }
                        if($fields == 'territory'){
                            $territory = $tableData->{$fields};
                            $csvData[] = $territory;
                        }
                        if($fields == 'recruiting_teamleader'){
                            $teamleadername = '';
                            if(!empty($recruitingTeamLeaders)) {
                                foreach($recruitingTeamLeaders as $teamleader){
                                    if($tableData->recruiting_teamleader == $teamleader->id){
                                        $teamleadername = $teamleader->name;
                                    }
                                }
                            }
                            $csvData[] = $teamleadername;
                        }
                        if($fields == 'recruiting_admin'){
                            $recruitingadminname = '';
                            if(!empty($recruitingadmin)){
                                foreach($recruitingadmin as $admin){
                                    if($tableData->recruiting_admin == $admin->id) {
                                        $recruitingadminname = $admin->name;
                                    }
                                }
                            }
                            $csvData[] = $recruitingadminname;
                        }
                        if($fields == 'permission'){
                            $userSelectedPermissions = array_values(array_column(UserPermissions::where('user_id', $tableData->id)->get()->toArray(), 'permissions_id'));
                            $roles = H::getHelper('ConstantHelper')->getCurrentRolesByUserId($tableData->id);
                            $rolePermission = RolesPermissions::select('*')->whereIn('roles_id', $roles)->get()->toArray();
                            $selectedPermissions = array_values(array_column($rolePermission, 'permissions_id'));
                            $permissionNames = Permissions::whereIn('id', $selectedPermissions)->pluck('name')->toArray();
                            $checkedPermissionNames = [];
                            foreach ($permissionsDetail as $group => $permissions) {
                                foreach ($permissions as $permission) {
                                    if (in_array($permission['id'], $userSelectedPermissions)) {
                                        $checkedPermissionNames[] = $permission['name'];
                                    }
                                }
                            }
                            $checkedPermissionNames = array_merge($checkedPermissionNames, $permissionNames);
                            $csvData[] = implode(",", $checkedPermissionNames);
                        }
                    }
                    $cntr++;
                }

                fputcsv($file, $csvData);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
