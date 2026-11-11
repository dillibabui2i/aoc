<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TemplateCategory;
use App\Helpers\H;

class ManageCategoryController extends Controller
{
    public function index(){
        return view('textTemplateCategory.grid');
    }

    public function templateCategoryList(Request $request){
        $columns = [
            0 => 'category',
            1 => 'department',
            2 => 'created_by',
        ];
        $templateCategorylist = TemplateCategory::select('template_categories.*', 'users.name as user_name')
        ->join('users', 'template_categories.created_by','=', 'users.id');
        if (isset($request->search['value']) && $request->search['value'] != '') {
            $templateCategorylist = $templateCategorylist->where(function($query) use ($request) {
                $searchValue = $request['search']['value'];
                $query->where('template_categories.department', 'like', "%{$searchValue}%")
                        ->orWhere('template_categories.category', 'like', "%{$searchValue}%")
                        ->orWhere('users.name', 'like', "%{$searchValue}%");
            });
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $templateCategorylist = $templateCategorylist->orderby($columns[intval($request['order'][0]['column'])]
            ,$request['order'][0]['dir']);
        }
        $total_records = $templateCategorylist->get();
        if (isset($request['start']) && $request['length'] != '-1') {
            $templateCategorylist = $templateCategorylist->offset($request['start'])
            ->limit($request['length']);
        }
        $templateCategorylist = $templateCategorylist->get();
        $cnt = 0;
        foreach ($templateCategorylist as $textTemplateData) {
            $btn = '';
            if (H::canPermissions('addTextTemplate')) {
                $btn .= '<a href="javascript:void(0)"
                class="edit" onclick="showTemplateCategoryForm(' . $textTemplateData['id'] . ')">' .
                '<i class="fa fa-edit" title="Edit"></i></a>';
                $btn .= '| <a href="javascript:void(0)"
                    onclick="categoryDelete('.$textTemplateData['id'].')" >
                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                    </a>';
                $templateCategorylist[$cnt]['action'] = $btn;
            }
            $cnt++;
        }
        return [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $templateCategorylist,
        ];
    }

    public function addTemplateCategory(){
        return view('textTemplateCategory.form');
    }

    public function saveTemplateCategory(Request $request){
        if(empty($request->category_id)){
            $data = [
                'department' => $request->department,
                'category' => $request->category,
                'created_at' => now(),
                'created_by' => auth()->user()->id,
                'updated_at' => now(),
                'updated_by' => auth()->user()->id,
            ];
            TemplateCategory::insert($data);
            return response()->json(['success' => 1]);
        }else{
            $data = [
                'department' => $request->department,
                'category' => $request->category,
                'updated_at' => now(),
                'updated_by' => auth()->user()->id,
            ];
            TemplateCategory::where('id', $request->category_id)->update($data);
            return response()->json(['success' => 1]);
        }
    }

    public function editTemplateCategory(Request $request){
        $id = $request->id;
        $categoryData = TemplateCategory::find($id);
        return view('textTemplateCategory.form', compact('categoryData'));
    }

    public function deleteTemplateCategory(Request $request){
        try{
            $id = $request->id;
            TemplateCategory::where('id', $id)->delete();
            return response()->json(['success' => 1]);
        }catch(\Exception $e){
            return response()->json(['success' => 0, 'message' => 'Something went wrong']);
        }
    }
}
