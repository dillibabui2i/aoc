<?php

namespace App\Http\Controllers;

use App\Enums\EntityTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use App\CustomFields;
use App\Helpers\H;
use Auth;

class ManagingCustomFields extends Controller
{
    public function index(){
        return view('ManagingCustomFields.grid');
    }

    public function customFieldslist(Request $request){
        $request = $request->all();
        $columns = [
            0 => 'sort',
            1 => 'entity',
            2 => 'category',
            3 => 'field_name',
            4 => 'db_field_name',
            5 => 'input_type',
            6 => 'frontend_label',
        ];
        $customFieldslist = CustomFields::query();
        if (isset($request['search']['value']) && $request['search']['value'] != '') {
            $customFieldslist->where(function($query) use ($request) {
                $searchValue = $request['search']['value'];
                $query->where('custom_fields.entity', 'like', "%{$searchValue}%")
                        ->orWhere('custom_fields.field_name', 'like', "%{$searchValue}%")
                        ->orWhere('custom_fields.frontend_label', 'like', "%{$searchValue}%")
                        ->orWhere('custom_fields.category', 'like', "%{$searchValue}%")
                        ->orWhere('custom_fields.db_field_name', 'like', "%{$searchValue}%")
                        ->orWhere('custom_fields.input_type', 'like', "%{$searchValue}%");
            });
        }
        $customFieldslist->where('is_deleted', 0);
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $customFieldslist = $customFieldslist->orderby($columns[intval($request['order'][0]['column'])]
            ,$request['order'][0]['dir']);
        }
        $total_records = $customFieldslist->get();
        if (isset($request['start']) && $request['length'] != '-1') {
            $customFieldslist = $customFieldslist->offset($request['start'])
            ->limit($request['length']);
        }
        $customFieldslist = $customFieldslist->get();
        return [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $customFieldslist,
        ];
    }

    public function openCustomFieldForm(Request $request) {
        $data = $request->all();
        $dependableFields = CustomFields::select('id', 'field_name', 'options', 'input_type', 'category')
        ->whereIn('input_type', ['dropdown', 'boolean', 'mdropdown'])
        ->where('is_deleted', 0);
        if ($data['mode'] == 'edit') {
            $customField = CustomFields::where('id', $data['id'])->first()->toArray();
            $data = array_merge($data, $customField);
            $dependableFields->whereNot('id', $data['id']);
        }
        $dependableFieldsList = $dependableFields->get()->toArray();
        $data = array_merge($data, ['dependentFields' => $dependableFieldsList]);
        return view('ManagingCustomFields.form', ['data' => $data]);
    }

    public function saveForm(Request $request)
    {
        $user = H::getLoggedInUser();
        try {
            $data = $request->all();
            $mode = $data['mode'];
            unset($data['_token']);
            unset($data['mode']);

            if ($mode == 'add') {
                $data['created_at'] = date('Y-m-d');
                $data['created_by'] = $user['id'];
            } else {
                $data['updated_at'] = date('Y-m-d');
                $data['updated_by'] = $user['id'];
            }
            $dependent_on_field = $data['dependent_on_field'];
            $dependent_on_value = $data['dependent_on_value'];
            unset($data['dependent_on_field']);
            unset($data['dependent_on_value']);            

            if ($mode == 'add') {
                $customFieldEntity = config('constant')['customFieldEntity'];
                $customFieldType = config('constant')['customFieldType'];
                $data['db_field_name'] = "aoc_cf_" . strtolower(str_replace(" ", "_", $data['field_name']));

                $aocTable = $customFieldEntity[$data['entity']][1];
                $fielDetail = explode(":", $customFieldType[$data['input_type']][1]);
                Schema::table($aocTable, function($table) use($fielDetail, $data, $aocTable) {
                    if (Schema::hasColumn($aocTable, $data['db_field_name'])) {
                        $data['db_field_name'] = $data['db_field_name']."_".time();
                    }
                    if (count($fielDetail) > 1) {
                        $table->{$fielDetail[0]}($data['db_field_name'], $fielDetail[1])->nullable();
                    } else {
                        $table->{$fielDetail[0]}($data['db_field_name'])->nullable();
                    }
                });

                $id = CustomFields::insertGetId($data);
                $data['dependent_on'] = json_encode([
                    'dependent_field' => $id,
                    'dependent_on_field' => $dependent_on_field,
                    'dependent_on_value' => $dependent_on_value
                ]);
                CustomFields::where('id', $id)->update($data);
            } else {
                $data['dependent_on'] = json_encode([
                    'dependent_field' => $data['id'],
                    'dependent_on_field' => $dependent_on_field,
                    'dependent_on_value' => $dependent_on_value
                ]);

                $labelArr = [
                    'field_name' => 'Name',
                    'entity' => 'Entity',
                    'frontend_label' => 'Label',
                    'call_script' => 'Call Script Label',
                    'category' => 'Category',
                    'input_type' => 'Input Type',
                    'default_value' => 'Default Value',
                    'options' => 'Options',
                    'is_required' => 'Is Required?',
                    'frontend_instruction' => 'Frontend Instruction',
                    'is_dependent' => 'Is Dependent?',
                    'sort' => 'Sort',
                    'dependent_on_field' => 'Dependent On Field',
                    'dependent_on_value' => 'Dependent On Value',
                    'max_length' => 'Max Characters'
                ];
    
                $existingCustomField = CustomFields::where('id', $request->id)
                                        ->first()
                                        ->toArray();
                
                foreach ($existingCustomField as $key => $value) {
                    if (array_key_exists($key, $data) === false) {
                        unset($existingCustomField[$key]);
                    }
                }
    
                $oldValues = array_diff_assoc($existingCustomField, $data);
                $newValues = array_diff_assoc($data, $existingCustomField);
    
                if (!empty($newValues['dependent_on'])) {
                    $oldDependOnValue = json_decode($oldValues['dependent_on'], true);
                    $oldFieldName = '';
                    if (!empty($oldDependOnValue['dependent_on_field'])) {
                        $oldFieldName = CustomFields::find($oldDependOnValue['dependent_on_field'])->field_name;
                    }
                    $oldValues['dependent_on_field'] = $oldFieldName;
                    $oldValues['dependent_on_value'] = $oldDependOnValue['dependent_on_value'];
                    
                    $newDependOnValue = json_decode($newValues['dependent_on'], true);
                    $newFieldName = '';
                    if (!empty($newDependOnValue['dependent_on_field'])) {
                        $newFieldName = CustomFields::find($newDependOnValue['dependent_on_field'])->field_name;
                    }
                    $newValues['dependent_on_field'] = $newFieldName;
                    $newValues['dependent_on_value'] = $newDependOnValue['dependent_on_value'];
                }
                foreach ($oldValues as $oldKey => $oldValue) {
                    if (in_array($oldKey, [
                                'created_at',
                                'created_by',
                                'updated_at',
                                'updated_by',
                                'dependent_on'
                            ])) {
                        continue;
                    }
                    if (in_array($oldKey, ['is_required', 'is_dependent'])) {
                        $oldValues[$oldKey] = ($oldValues[$oldKey] == 0) ? 'No' : 'Yes';
                        $newValues[$oldKey] = ($newValues[$oldKey] == 0) ? 'No' : 'Yes';
                    }
                    $logData = [];
                    if (!empty($oldValues[$oldKey]) && !empty($newValues[$oldKey])) {
                        $logData['notes'] = $labelArr[$oldKey] . ' updated from ' . $oldValues[$oldKey] .
                                            ' to ' . (is_array($newValues[$oldKey]) ? implode(",", $newValues[$oldKey]) : $newValues[$oldKey])
                                            . ' by ' . Auth::user()->name;
                    } else if (!empty($oldValues[$oldKey]) && empty($newValues[$oldKey])) {
                        $logData['notes'] = $labelArr[$oldKey] . ' ('.$oldValues[$oldKey].') removed by ' . Auth::user()->name;
                    } else if (empty($oldValues[$oldKey]) && !empty($newValues[$oldKey])) {
                        $logData['notes'] = $labelArr[$oldKey] . ' ('.(is_array($newValues[$oldKey]) ? implode(",", $newValues[$oldKey]) :
                                            $newValues[$oldKey]).') added by ' . Auth::user()->name;
                    }
                    if (count($logData) > 0) {
                        $logData['entity'] = 'custom-field-change';
                        $logData['entity_id'] = $request->id;
                        H::getHelper("ActivityNotes")->setActivityNotes($logData);
                    }
                }

                CustomFields::where('id', $data['id'])->update($data);
            }
            return response()->json([
                "status" => true,
                "msg" => '<div class="alert alert-success col-md-12" role="alert">
                    Custom field saved successfully.
                    </div>'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                "msg" => '<div class="alert alert-danger col-md-12" role="alert">
                    Error while saving custom field.
                    </div>'
            ]);
        }
    }

    public function delete(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                CustomFields::where('id', $request->input('id'))->update(['is_deleted' => 1]);
                return response()->json(["success" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }
}
