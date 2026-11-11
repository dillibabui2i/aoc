<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\TextTemplate;
use App\TemplateCategory;
use App\Helpers\H;

class TextTemplateController extends Controller
{
    public function index(){
        return view('textTemplate.grid');
    }

    public function textTemplatelist(Request $request){
        $columns = [
            0 => 'department',
            1 => 'category',
            2 => 'template_name',
            3 => 'created_by',
        ];
        $textTemplatelist = TextTemplate::select('templates.*', 'users.name as user_name')
        ->join('users', 'templates.created_by','=', 'users.id');
        if (isset($request->search['value']) && $request->search['value'] != '') {
            $textTemplatelist = $textTemplatelist->where(function($query) use ($request) {
                $searchValue = $request['search']['value'];
                $query->where('templates.department', 'like', "%{$searchValue}%")
                        ->orWhere('templates.category', 'like', "%{$searchValue}%")
                        ->orWhere('templates.template_name', 'like', "%{$searchValue}%")
                        ->orWhere('users.name', 'like', "%{$searchValue}%");
            });
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $textTemplatelist = $textTemplatelist->orderby($columns[intval($request['order'][0]['column'])]
            ,$request['order'][0]['dir']);
        }
        $total_records = $textTemplatelist->get();
        if (isset($request['start']) && $request['length'] != '-1') {
            $textTemplatelist = $textTemplatelist->offset($request['start'])
            ->limit($request['length']);
        }
        $textTemplatelist = $textTemplatelist->get();
        $cnt = 0;
        foreach ($textTemplatelist as $textTemplateData) {
            $btn = '';
            if (H::canPermissions('addTextTemplate')) {
                $btn .= '<a href="javascript:void(0)"
                class="edit" onclick="showTextTemplateForm(' . $textTemplateData['id'] . ')">' .
                '<i class="fa fa-edit" title="Edit"></i></a>';
                $btn .= '| <a href="javascript:void(0)"
                    onclick="templateDelete('.$textTemplateData['id'].')" >
                    <i class="fa fa-trash-o" aria-hidden="true"></i>
                    </a>';
                $textTemplatelist[$cnt]['action'] = $btn;
            }
            $cnt++;
        }
        return [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $textTemplatelist,
        ];
    }

    public function addTextTemplate(){
        return view('textTemplate.form');
    }

    public function getTextTemplateCategory($departmentName)
    {
        $categories = TemplateCategory::where('department', $departmentName)->get();
        return response()->json($categories);
    }

    public function saveTextTemplate(Request $request){
        if(empty($request->template_id)){
            $data = [
                'department' => $request->department,
                'category' => $request->category,
                'template_name' => $request->template_name,
                'Template' => $request->Template,
                'created_at' => now(),
                'created_by' => auth()->user()->id,
                'updated_at' => now(),
                'updated_by' => auth()->user()->id,
            ];
            TextTemplate::insert($data);
            return response()->json(['success' => 1]);
        }else{
            $data = [
                'department' => $request->department,
                'category' => $request->category,
                'template_name' => $request->template_name,
                'Template' => $request->Template,
                'updated_at' => now(),
                'updated_by' => auth()->user()->id,
            ];
            TextTemplate::where('id', $request->template_id)->update($data);
            return response()->json(['success' => 1]);
        }
    }

    public function editTextTemplate(Request $request){
        $id = $request->id;
        $textTemplate = TextTemplate::find($id);
        return view('textTemplate.form', compact('textTemplate'));
    }

    public function deleteTextTemplate(Request $request){
        try{
            $id = $request->id;
            TextTemplate::where('id', $id)->delete();
            return response()->json(['success' => 1]);
        }catch(\Exception $e){
            return response()->json(['success' => 0, 'message' => 'Something went wrong']);
        }
    }
}
