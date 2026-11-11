<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\Permissions;
use App\Roles;
use App\RolesPermissions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use DB;

class RolesController extends Controller
{
    public function listroles()
    {
        return view('roles.grid');
    }

    public function add()
    {
        $permissions = new Permissions();
        $permissionData = $permissions->get()->toArray();

        $finalPermissionData = [];
        foreach ($permissionData as $data) {
            $finalPermissionData[$data['group']][] = $data;
        }

        return view('roles.form', ['permissionsDetail' => $finalPermissionData]);
    }

    public function getRolesList(Request $request)
    {
        $columns = [
            0 => 'name',
        ];

        $roleList = new Roles();

        if (isset($request->search['value']) && $request->search['value'] != '') {
            $searchValue = '%' . $request['search']['value'] . '%';
            $roleList = $roleList->where('name', 'like', $searchValue);
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $roleList = $roleList->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        $total_records = $roleList->get();

        if (isset($request['start']) && $request['length'] != '-1') {
            $roleList = $roleList->offset($request['start'])->limit($request['length']);
        }

        $roleList = $roleList->get();

        $cnt = 0;
        foreach ($roleList as $roleData) {
            if (H::canPermissions('manageroles.editrole')) {
                $btn = '';
                if ($roleData['name'] != 'Super Admin') {
                    $btn .= '<a href="' . url('/manageroles/editrole/id', $roleData['id']) . '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
                }

                $roleList[$cnt]['action'] = $btn;
                $cnt++;
            }
        }
        $data = [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $roleList,
        ];

        return $data;
    }

    public function editrole(Request $request, $id)
    {
        $roleList = new Roles();
        $roleList = $roleList->where('id', $id)->first();
        if ($roleList->name == 'Super Admin') {
            return redirect(route('manageroles'));
        }
        $permissions = new Permissions();
        $permissionData = $permissions->get()->toArray();

        $finalPermissionData = [];
        foreach ($permissionData as $data) {
            $finalPermissionData[$data['group']][] = $data;
        }

        $selectedPermissions = RolesPermissions::where('roles_id', $id)->get()->toArray();

        return view('roles.form', ['roleDetail' => $roleList, 'permissionsDetail' => $finalPermissionData, 'selectedPermissions' => array_values(array_column($selectedPermissions, 'permissions_id'))]);
    }

    public function saverole(Request $request)
    {
        try {
            $id = 0;
            $params = $request->all();
            if (isset($params['id'])) {
                Roles::where('id', $params['id'])->update(['name' => $params['name'], 'department' => $params['department']]);
                RolesPermissions::where('roles_id', $params['id'])->delete();
                $id = $params['id'];
            } else {
                $id = Roles::insertGetId(['name' => $params['name'], 'department' => $params['department']]);
            }

            foreach ($params['permissions'] as $key => $permissionId) {
                $rolesPermissionsModel = new RolesPermissions;
                $data = ['permissions_id' => $permissionId, 'roles_id' => $id];
                $rolesPermissionsModel->insert($data);
            }
            Session::flash('success', 'Role saved successfully');

            return redirect(route('manageroles'));
        } catch (\Exception $e) {
            H::ExcelLog('ERROR: ' . $e->getMessage(),'saverole.log');
            exit;
            //Session::flash('error', 'There is an error while updating role. Error Description : '.$e->getMessage());
            //return redirect(route("manageroles"));
        }
    }

    public function restricted(Request $request)
    {
        return view('roles.restricted');
    }

    public function checkDuplicateRole(Request $request)
    {
        $params = $request->all();
        $roles = Roles::where(DB::raw('TRIM(LOWER(name))'), 'like', '%' . trim(strtolower($params['name'])) . '%')
        ->get()->toArray();
        if (count($roles) > 0) {
            echo 'false';
        } else {
            echo 'true';
        }
    }
}
