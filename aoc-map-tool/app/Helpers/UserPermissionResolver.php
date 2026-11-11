<?php

namespace App\Helpers;

use App\Enums\RolesEnum;
use App\Helpers\H;
use App\Permissions as Permissions;
use App\RolesPermissions;
use App\UserPermissions;
use App\User;
use Auth;
use DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class UserPermissionResolver
{
    public function getPermissionIdByPermissionName($permission)
    {
        $pm = Permissions::select('id')->where('route_name', $permission)->get();
        if (!empty($pm[0])) {
            return $pm[0]->id;
        } else {
            return null;
        }
    }

    public function isUserHasPermission($userId,$permission) {
        return UserPermissions::select("*")->where('permissions_id', $permission)->where('user_id', $userId)->first();
    }
    public function isRoleHasPermissionNew($permission, $userId = 0)
    {
        H::ExcelLog('isRoleHasPermissionNew: ' . $permission, 'tsPermissions.log');

        if ($userId === 0) {
            $user = H::getLoggedInUser();
            $userId = $user['id'];
        }

        $permissionId = $this->getPermissionIdByPermissionName($permission);
        if($permissionId) {
            $roles = H::getHelper('ConstantHelper')->getCurrentRolesByUserId($userId);
            $rolePermission = RolesPermissions::select('*')->where('permissions_id', $permissionId)->whereIn('roles_id', $roles)->get();
            if (isset($rolePermission[0])) {
                return true;
            } else {
                if($this->isUserHasPermission($userId,$permissionId)){
                    return true;
                } else{
                    return false;
                }
            }
        } else {
            return false;
        }
    }
    public function isRouteAllowedForUser(string $routeName, int $userId = 0): bool
    {
        if ($userId === 0) {
            $userId = Auth::id();
        }

        $roles = H::getHelper('ConstantHelper')->getCurrentRolesByUserId($userId);

        if (in_array(RolesEnum::SUPER_ADMIN->value, $roles)) {
            return true;
        }

        $permissionIds = RolesPermissions::whereIn('roles_id', $roles)->pluck('permissions_id')
            ->merge(UserPermissions::where('user_id', $userId)->pluck('permissions_id'))
            ->unique();

        $allowedRoutes = Permissions::whereIn('id', $permissionIds)->get(['route_name', 'related_routes'])
            ->flatMap(function ($permission) {
                $routes = [$permission->route_name];
                if (!empty($permission->related_routes)) {
                    $routes = array_merge($routes, explode(',', $permission->related_routes));
                }

                return $routes;
            });

        return $allowedRoutes->contains($routeName);
    }

    /**
     * Ids of the users holding the permission that covers the given route, as granted to them
     * individually (user_has_permission).
     *
     * Deliberately narrower than isRouteAllowedForUser(): role-inherited permissions and the Super
     * Admin bypass are NOT considered. Those make someone able to *reach* a page, which is not the same
     * as being someone the business would pick for the work behind it — a Super Admin can open every
     * screen in the app, and listing all of them as candidates is noise.
     *
     * @return array<int, int>
     */
    public function getUserIdsWithPermission(string $routeName): array
    {
        // Matched in SQL rather than by hydrating every permission row and filtering in PHP:
        // related_routes is a plain comma-separated list, which is exactly what FIND_IN_SET reads.
        // Spaces are stripped first — process:permissions never writes any, but a stray one in
        // config/permissions.php would otherwise make the match silently fail.
        $permissionIds = Permissions::where('route_name', $routeName)
            ->orWhereRaw("FIND_IN_SET(?, REPLACE(related_routes, ' ', ''))", [$routeName])
            ->pluck('id');

        if ($permissionIds->isEmpty()) {
            return [];
        }

        return UserPermissions::whereIn('permissions_id', $permissionIds)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->map(fn ($userId) => (int) $userId)
            ->toArray();
    }

    public function isRoleHasPermission($roleId, $permission)
    {
        H::ExcelLog('isRoleHasPermission: ' . $permission, 'tsPermissions.log');

        if(LD::checkFeatureFlag('multiple-roles-to-user')) {
            H::ExcelLog('isRoleHasPermissionMulti: ' . $permission, 'tsPermissions.log');

            $permissionId = $this->getPermissionIdByPermissionName($permission);
            if($permissionId) {
                $rolePermission = RolesPermissions::select('*')->where('permissions_id', $permissionId)->whereIn('roles_id', $roleId)->get();
                if (isset($rolePermission[0])) {
                    return true;
                } else {
                    $user = H::getLoggedInUser();
                    $userId = $user['id'];

                    if($this->isUserHasPermission($userId,$permissionId)){
                        return true;
                    } else{
                        return false;
                    }
                }
            } else {
                return false;
            }

        } else {
            H::ExcelLog('isRoleHasPermissionOld: ' . $permission, 'tsPermissions.log');

            $permissionId = $this->getPermissionIdByPermissionName($permission);
            H::ExcelLog('permission: ' . $permissionId, 'tsPermissions.log');
            if ($permissionId) {

                $rolePermission = RolesPermissions::select('*')->where('permissions_id', $permissionId)->where('roles_id', $roleId)->get();
                if (isset($rolePermission[0])) {
                    return true;
                } else {
                    $user = H::getLoggedInUser();
                    $userId = $user['id'];

                    if($this->isUserHasPermission($userId,$permissionId)){
                        return true;
                    } else{
                        return false;
                    }
                }
            } else {
                return false;
            }
        }
    }

    public function getAllRolesByPermissionId($permissionId)
    {
        if ($permissionId) {
            $rolePermission = RolesPermissions::select('*')->where('permissions_id', $permissionId)->get();
            if (isset($rolePermission[0])) {
                return $rolePermission;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    public function getUserByPermission($permission)
    {
        $permissionId = $this->getPermissionIdByPermissionName($permission);
        $superiorUserRoles = [];
        $roles = $this->getAllRolesByPermissionId($permissionId);
        if ($roles == false) {
            $roles = [];
        }
        foreach ($roles as $role) {
            $superiorUserRoles[] = $role->roles_id;
        }

        $checkInUserHasPermission =  DB::table('user_has_permission')->where('permissions_id',$permissionId)->exists();

        if (!empty($superiorUserRoles) || $checkInUserHasPermission) {
            $tmpUsers = [];
            $escalatedUsers = DB::table('user_has_permission')
                ->join('users', 'user_has_permission.user_id', '=', 'users.id')
                ->select('users.name','users.id')
                ->where('user_has_permission.permissions_id',$permissionId)
                ->get();
            if(!empty($escalatedUsers[0])){
                foreach($escalatedUsers as $tmp){
                    if(!in_array(array('name' => $tmp->name, 'id' => $tmp->id), $tmpUsers)){
                        $tmpUsers[] = array('name' => $tmp->name, 'id' => $tmp->id);
                    }
                }
            }
			$users = User::select("users.id","users.name")->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')->where('status',1)->orderBy('users.name')->whereIn('role_id', $superiorUserRoles)->get();
            if (!empty($users[0])) {
                foreach($users as $tmp){
                    if(!in_array(array('name' => $tmp->name, 'id' => $tmp->id), $tmpUsers)){
                        $tmpUsers[] = array('name' => $tmp->name, 'id' => $tmp->id);
                    }
                }
            }
            usort($tmpUsers, function($a, $b){ return strcmp($a["name"], $b["name"]); });
            $users = json_decode(json_encode($tmpUsers), FALSE);
            H::ExcelLog(print_r($users,true),'tsApprovedBy.log');

            if (!empty($users[0])) {
                return $users;
            } else {
                return [];
            }
        }

        return [];
    }

    public function getUsersByRoleName(string $role): Collection
    {
        return User::whereHas('roles', function ($q) use ($role) {
            return $q->where('name', $role);
        })->where('status', 1)->get();
    }

    public function getUserListByPermission($permission)
    {
        $permissionId = $this->getPermissionIdByPermissionName($permission);
        $superiorUserRoles =  $roles = $tmpUsers = [];
        $roles = $this->getAllRolesByPermissionId($permissionId);
        if (!empty($roles)) {
            foreach ($roles as $role) {
                $superiorUserRoles[] = $role->roles_id;
            }
        }
        if (!empty($superiorUserRoles)) {
            $users = User::select("users.id","users.name")->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')->where('status',1)->orderBy('users.name')->whereIn('role_id', $superiorUserRoles)->get();
            if (!empty($users[0])) {
                foreach($users as $tmp){
                    if(!in_array(array('name' => $tmp->name, 'id' => $tmp->id), $tmpUsers)){
                        $tmpUsers[] = array('name' => $tmp->name, 'id' => $tmp->id);
                    }
                }
            }

        }
        $escalatedUsers = DB::table('user_has_permission')
                ->join('users', 'user_has_permission.user_id', '=', 'users.id')
                ->select('users.name','users.id')
                ->where('user_has_permission.permissions_id',$permissionId)
                ->get();
        if(!empty($escalatedUsers)){
            foreach($escalatedUsers as $tmp){
                if(!in_array(array('name' => $tmp->name, 'id' => $tmp->id), $tmpUsers)){
                    $tmpUsers[] = array('name' => $tmp->name, 'id' => $tmp->id);
                }
            }
        }
        usort($tmpUsers, function($a, $b){ return strcmp($a["name"], $b["name"]); });
        $users = json_decode(json_encode($tmpUsers), true);
        H::ExcelLog(print_r($users,true),'getUserListByPermission.log');
        if (!empty($users)) {
            return $users;
        } else {
            return [];
        }
    }

    public function getUserByPermissionAndBranch($permission,$branchId)
    {
        $permissionId = $this->getPermissionIdByPermissionName($permission);
        $superiorUserRoles = [];
        $roles = $this->getAllRolesByPermissionId($permissionId);
        if ($roles == false) {
            $roles = [];
        }
        foreach ($roles as $role) {
            $superiorUserRoles[] = $role->roles_id;
        }
        $users = User::select('id', 'name','email')->where(function($query) use ($permissionId,$superiorUserRoles) {
            $query->whereIn('role', $superiorUserRoles)->orWhereIn('id',function($query)use ($permissionId){
                $query->select('user_id')->from('user_has_permission')->where('permissions_id',$permissionId);
            })->get();
        })->whereRaw('FIND_IN_SET(?, branchid)', $branchId)->get()->toArray();
        if (!empty($users[0])) {
            return $users;
        } else {
            return [];
        }
    }

    public function getRecruitingAdmin()
    {
        return $this->getUserByPermission('is_recruiting_admin_form');
    }

    public function getAllTeamLeader()
    {
        return $this->getUserByPermission('is_recruiting_team_leader');
    }

    public function getAllManager()
    {
        return $this->getUserByPermission('is_supervisor');
    }

    public function getSuperior($currentRole)
    {
        $superiorUserRoles = [];
        if (LD::checkFeatureFlag('multiple-roles-to-user')) {
            if ($this->isRoleHasPermissionNew('is_recruiting_team_leader')) {
                // show users with permission is_recruiting_manager

                $permissionId = $this->getPermissionIdByPermissionName('is_recruiting_manager');
                $roles = $this->getAllRolesByPermissionId($permissionId);
                foreach ($roles as $role) {
                    $superiorUserRoles[] = $role->roles_id;
                }
            } elseif ($this->isRoleHasPermissionNew('is_recruiting_manager')) {
                // show users with permission is_recruiting_admin

                $permissionId = $this->getPermissionIdByPermissionName('is_recruiting_admin');
                $roles = $this->getAllRolesByPermissionId($permissionId);
                foreach ($roles as $role) {
                    $superiorUserRoles[] = $role->roles_id;
                }
            } else {
                $permissionId = $this->getPermissionIdByPermissionName('is_recruiting_team_leader');
                $roles = $this->getAllRolesByPermissionId($permissionId);
                foreach ($roles as $role) {
                    $superiorUserRoles[] = $role->roles_id;
                }
            }
        } else {
            if ($this->isRoleHasPermission($currentRole, 'is_recruiting_team_leader')) {
                // show users with permission is_recruiting_manager

                $permissionId = $this->getPermissionIdByPermissionName('is_recruiting_manager');
                $roles = $this->getAllRolesByPermissionId($permissionId);
                foreach ($roles as $role) {
                    $superiorUserRoles[] = $role->roles_id;
                }
            } elseif ($this->isRoleHasPermission($currentRole, 'is_recruiting_manager')) {
                // show users with permission is_recruiting_admin

                $permissionId = $this->getPermissionIdByPermissionName('is_recruiting_admin');
                $roles = $this->getAllRolesByPermissionId($permissionId);
                foreach ($roles as $role) {
                    $superiorUserRoles[] = $role->roles_id;
                }
            } else {
                $permissionId = $this->getPermissionIdByPermissionName('is_recruiting_team_leader');
                $roles = $this->getAllRolesByPermissionId($permissionId);
                foreach ($roles as $role) {
                    $superiorUserRoles[] = $role->roles_id;
                }
            }
        }
        if (!empty($superiorUserRoles)) {
            $users = User::select('id', 'name')->whereIn('role', $superiorUserRoles)->get();
            if (!empty($users[0])) {
                return $users;
            } else {
                return null;
            }
        }

        return null;
    }

    public function getUserTreeByTeamLeader($superId)
    {
        $user = User::select('id', 'name')->where('recruiting_teamleader', $superId)->get();

        return $user;
    }
    public function getUserTreeByRA($superId)
    {
        $user = User::select('id', 'name')->where('recruiting_admin', $superId)->get();

        return $user;
    }
    public function getUserTreeByManager($superId)
    {
        $user = User::select('id', 'name')->where('recruiting_manager', $superId)->get();

        return $user;
    }

    public function getRAsRecruiters($currentUserId = ''){
        static $username = [];

        if (empty($username)) {
            $users = H::getLoggedInUser();
            $username[] = $users['name'];
        }

        if (!empty($currentUserId)) {
            $suser = $this->getUserTreeByRA($currentUserId);
            if (isset($suser) && !empty($suser)) {
                foreach ($suser as $user) {
                    $username[] = $user->name;
                }
            }
        }

        H::ExcelLog(print_r($username,true),'testra.log');
        return $username;
    }

    public function getAllSubordinatesFromCurrentUser($currentUserId = '')
    {
        static $username = [];

        if (empty($username)) {
            $users = H::getLoggedInUser();
            $username[] = $users['name'];
        }

        if (!empty($currentUserId)) {
            $suser = $this->getUserTreeByTeamLeader($currentUserId);
            if (isset($suser) && !empty($suser)) {
                foreach ($suser as $user) {
                    $username[] = $user->name;
                    $this->getAllSubordinatesFromCurrentUser($user->id);
                }
            }

            $manager = $this->getUserTreeByManager($currentUserId);
            if (isset($manager) && !empty($manager)) {
                foreach ($manager as $m) {
                    $username[] = $m->name;
                    $this->getAllSubordinatesFromCurrentUser($m->id);
                }
            }
        }

        return $username;
    }

    public function getUserIdByFacilityAndRole($facility_id , $role)
    {
        $userIds = [];

        if (empty($facility_id)) {
            return $userIds;
        }

        $cacheKey = sprintf('user_ids_facility_%s_role_%s', $facility_id, $role);
        $cacheKey = md5($cacheKey);

        return Cache::remember($cacheKey, now()->addMinutes(2), function () use ($facility_id, $role, $userIds) {
            return User::select('users.id', 'users.name')
                ->leftJoin("users_has_roles","users.id","=","users_has_roles.user_id")
                ->leftJoin("roles","users_has_roles.role_id","=","roles.id")
                ->where("roles.name",$role)->where("users.status",1)
                ->whereRaw("find_in_set(".$facility_id.", users.facilityid)")
                ->pluck('users.id')
                ->toArray();
        });
    }
    public function getUserIdByBranchAndRole($branchid , $role)
    {
        $userIds = [];
        if (!empty($branchid)) {
            $users = User::select('users.id', 'users.name')
                ->leftJoin("users_has_roles","users.id","=","users_has_roles.user_id")
                ->leftJoin("roles","users_has_roles.role_id","=","roles.id")
                ->where("roles.name",$role)->where("users.status",1)
                ->whereRaw("find_in_set(".$branchid.", users.branchid)")->get()->toArray();
            if (!empty($users)) {
                foreach($users as $user) {
                    $userIds[] = $user['id'];
                }
                return $userIds;
            } else {
                return $userIds;
            }
        } else {
            return $userIds;
        }
    }

    public function isUserHasRole(string $role, int $userId = 0)
    {
        if ($userId === 0) {
            $userId = Auth::id();
        }

        return User::select('users.id', 'users.name')
            ->leftJoin('users_has_roles','users.id','=','users_has_roles.user_id')
            ->leftJoin('roles','users_has_roles.role_id','=','roles.id')
            ->where('roles.name', '=', $role)
            ->where('users.id', '=', $userId)
            ->exists();
    }
}
