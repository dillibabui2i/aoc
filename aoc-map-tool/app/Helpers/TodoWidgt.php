<?php
namespace App\Helpers;

use App\Helpers\H;
use App\Notes;
use DB;
use Illuminate\Contracts\Support\Renderable;

class TodoWidgt
{
    public function getDataTableHtml($type): Renderable
    {
        switch($type) {
            case 'task_list':
                $dataTable = [
                    'entity' => 'Entity',
                    'source' => 'Source',
                    'notes' => 'Notes',
                    'assigned_to' => 'Assigned To',
                    'created_by' => 'Created By',
                    'created_at' => 'Created Date',
                    'due_date' => 'Due Date',
                ];
            break;
            default:
                $dataTable = [];
            break;
        }

        return view('todowidgt.dashboard_tasklist_datatable', ["type" => $type, 'dataTable' => $dataTable]);
    }

    public function getTodoTaskList($type, $request = '',$showCount=0){
        $userId = auth()->user()->id;
        $ignoreEntity = config('constant.toDoIgonreEntity');
        $bucket = Notes::select('notes.*',
        DB::raw(
            "CASE WHEN
                entity = 'client'
            THEN
                'Client'
            ELSE
               entity
            END as entity"
        ),
		DB::raw('(case when notes.misc1 = \'mobileapp\' then eu.name else u1.name end) as created_by'),
		DB::raw('u.name as assigned_to')
		)
		->leftJoin('users as u', 'notes.assigned_to', '=', 'u.id')
		->leftJoin('users as u1', 'notes.created_by', '=', 'u1.id')
		->leftJoin('external_users as eu', 'notes.created_by', '=', 'eu.id')
		->whereNotNull('assigned_to')
        ->where('assigned_to', '!=', 0)
        ->where('completed', '0')
        ->whereNotIn('entity', $ignoreEntity)
        ->orderBy('created_at', 'desc');
        $filters = [];

        if(isset($request['filters'])) {
            foreach($request['filters'] as $fl) {
                if(!empty($fl['value'])) {
                    $filters[$fl['name']][] = $fl['value'];
                }
            }

            foreach ($filters as $fkey => $fval) {
                if ($fkey == 'entity') {
                    $bucket->whereIn('entity', $fval);
                } elseif ($fkey == 'created_by') {
                    $bucket->whereIn('created_by', $fval);
                } elseif ($fkey == 'me_mode') {
                    $bucket->where('assigned_to', $userId);
                } elseif ($fkey == 'source') {
                    $bucket->whereIn('source', $fval);
                }
            }
        }

        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket->get();
    }

    public function getNoteCreatedUser(){
        $notesuser = DB::table('notes')->select('users.id', 'users.name')
        ->Join('users', 'notes.created_by', '=', 'users.id')
        ->whereNotNull('assigned_to')
        ->where('assigned_to', '!=', 0)
        ->distinct()
        ->get();
        $userdata = [];
        if(count($notesuser) > 0){
            foreach($notesuser as $key => $data){
                $userdata[$key] = ['value' =>$data->id,'text' => $data->name];
            }
        }
        return $userdata;
    }
}
