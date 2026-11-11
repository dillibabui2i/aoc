<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Helpers\TodoWidgt;
use App\Notes;
use PSpell\Config;
use Yajra\DataTables\DataTables;

class TodoController extends Controller
{
    public function getToDoTaskList(Request $request)
    {
        $type = $request->input("type");
        $data = H::getHelper("TodoWidgt")->getTodoTaskList($type, $request);

        $datatableObj = Datatables::of($data)
        ->editColumn('source', function ($row) {
            return $row->source ?? '-';
        })
		->editColumn('completed_at', function ($row) {
            return H::getFormatedDateTime($row->completed_at);
        })->editColumn('due_date', function ($row) {
            if(!empty($row->due_date)){
                return [
                    'display' => e(H::getFormatedDateTime($row->due_date)),
                    'timestamp' => strtotime($row->due_date)
                ];
            } else {
                return [
                    'display' => '',
                    'timestamp' => ''
                ];
            }
        })
		->editColumn('created_at', function ($row) {
            if(!empty($row->created_at)){
                return [
                    'display' => e(H::getFormatedDateTime($row->created_at)),
                    'timestamp' => strtotime($row->created_at)
                ];
            } else {
                return [
                    'display' => '',
                    'timestamp' => ''
                ];
            }
        })
        ->addColumn('Action', function ($row) {
            $btn = '';
            if ($row->completed == 1) {
                $btn .= '<input type="checkbox" checked disabled>';
            } else {
                $btn .= '<input type="checkbox" onclick="markAsCompleted(\'' . $row->id . '\')">';
            }
            return $btn;
        })->editColumn('entity', function ($row) {
            if(array_key_exists($row->entity, Config('constant.todoEntityCrossWalk'))){
                return Config('constant.todoEntityCrossWalk')[$row->entity];
            }else{
                return $row->entity;
            }
        });

        return $datatableObj->escapeColumns('Action')->escapeColumns('select')->make(true);
    }

    public function reloadCountToDoTaskList(Request $request)
    {
        $type = $request->input("type");
        return json_encode(["counter" => H::getHelper('TodoWidgt')->getTodoTaskList($request->input("type"), $request, 1), "type" => $request->input("type")]);
    }

    public function makrAsCompleted(Request $request){
        $noteId = $request->id;
        $data = [
            "completed" => '1',
            "completed_at" => date("Y-m-d H:i:s"),
            "completed_by" => auth()->user()->id,
        ];
        $notes = Notes::where('id', $noteId)->update($data);
        if ($notes) {
            return response()->json(['success' => true, 'message' => 'Note marked as completed.']);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to mark note as completed.']);
        }
    }
}
