<?php
namespace App\Http\Controllers;

use App\Helpers\MessageWidget;
use Composer\DependencyResolver\Request;
use App\MessageAttachment;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Conversation;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Http\Request as Requestarg;
use Illuminate\Support\Facades\DB;
use DataTables;

class MessageController extends Controller
{
    public function index()
    {
        $token = MessageWidget::getToken();
        return view('messageWidget.widget',['token'=>$token]);
    }
    public function getToken(Request $request){
        $token = MessageWidget::getToken();
        return response()->json(['token'=>$token]);
    }
    public function fullPageView(Request $request){
        return view('messageWidget.fullPageWidget', ['mode' => 'add']);
    }

    public function add()
    {
        return view('messageWidget.attachmentform', ['mode' => 'add']);
    }

    public function getConversationsByCandidateAndClinician(Requestarg $request){
        try {
            $id = $request->input('id');
            $type = $request->input('type');
            $res = ['status'=>false,'msg'=>'No Conversation Found.'];
            if(isset($id) && $id != '' && isset($type) && $type != ''){
                $con = Conversation::getConversation($id,$type);
                $res = ['status'=>true,'data'=>$con];
            }
            return response()->json($res);
        } catch (Exception $e) {
            H::ExcelLog('getConversationsByCandidateAndClinician:'.$e->getMessage(),'conversation.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }
    public function addConversationToDB(Requestarg $request){
        try {
            $reqData = $request->all();
            $res = ['status'=>false,'msg'=>'No Conversation Found.'];
            if(isset($reqData['sid']) && $reqData['sid'] != '' && isset($reqData['unique_name']) && $reqData['unique_name'] != '' && isset($reqData['friendly_name']) && $reqData['friendly_name'] != ''){
                $reqData['messagingServiceSid'] = env('TWILIO_SERVICE_SID');
                $reqData['chatServiceSid'] = env('TWILIO_SERVICE_SID');
                $reqData['user_id'] = Auth::user()->id;
                $id = Conversation::addConversation($reqData);
                $res = ['status'=>true,'data'=>$id];
            }
            return response()->json($res);
        } catch (Exception $e) {
            H::ExcelLog('addConversationToDB:'.$e->getMessage(),'conversation.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function getFileList(Requestarg $request){
        $userType = $request->input('userType');
        $data = MessageAttachment::where('is_deleted', '<>', '1')->where('status',1)->where('message_types',$userType)->get();
        return view('messageWidget.fileSelectList', ['data' => $data]);
    }
    public function list(Requestarg $request)
    {
        $columns = [
            ['name' => 'id','label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'message_types', 'label' => 'Message Types', 'width' => '10%'],
            ['name' => 'name', 'label' => 'Name', 'width' => '15%'],
            ['name' => 'description', 'label' => 'Description', 'width' => '30%'],
            ['name' => 'status', 'label' => 'Status', 'width' => '10%'],
        ];
        if ($request->ajax()) {
            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');

            $q = MessageAttachment::where('is_deleted', '<>', '1');

            $totalData = $q->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q->whereNested(function($q) use ($search) {
                    $q->where('message_types', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }

            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $this->DataProcess($data),
            ]);
        }
        return view('messageWidget.attachmentgrid', ['columns' => $columns]);
    }

    private function DataProcess($data) {
        foreach ($data as $key => $value) {
            $value->status = ($value->status == 1) ? 'Enabled' : 'Disabled';
        }
        return $data;
    }

    public function edit(Requestarg $request)
    {
        try {
            $data = MessageAttachment::where('id', $request->input('id'))->first();
            if (empty($data)) {
                return redirect(route('managemessageattachment.list'))->with('error', 'Requested information not found.');
            }
            return view('messageWidget.attachmentform', ['mode' => 'edit', 'data' => $data]);
        } catch (\Exception $e) {
            return redirect(route('managemessageattachment.list'))->with('error', 'Error while loading information.');
        }
    }

    public function save(Requestarg $request)
    {
        try {
            $requestData = $request->all();

            if (isset($requestData['id']) && !empty($requestData['id'])) {
                $messageAttachment = MessageAttachment::find($requestData['id']);
            } else {
                $messageAttachment = new MessageAttachment;
            }
            $messageAttachment->message_types = $requestData['message_types'];
            $messageAttachment->name = $requestData['name'];
            $messageAttachment->description = $requestData['description'];
            $file = $request->file('file_path');
            if (!empty($file)) {
                $destinationPath = storage_path() . '/messageAttachments';
                $filePath = realpath($destinationPath . '/' . $messageAttachment->url);
                if ($filePath !== false && strpos($filePath, $destinationPath) === 0 && file_exists($filePath)) {
                    @unlink($filePath);
                }
                $filename =  time() . "_" . $file->getClientOriginalName();
                $file->move($destinationPath, $filename);
                $messageAttachment->url = $filename;
            }
            $messageAttachment->status = $requestData['status'];
            $messageAttachment->save();

            return redirect(route('managemessageattachment.list'))->with('success', 'Message attachment saved successfully.');
        } catch(\Exception $e) {
            return redirect(route('managemessageattachment.list'))->with('error', 'Error while saving information.');
        }
    }

    public function destroy(Requestarg $request)
    {
        try {
            $requestData = $request->all();
            $messageAttachment = MessageAttachment::find($requestData['id']);
            if (empty($messageAttachment)) {
                return redirect(route('managemessageattachment.list'))->with('error', 'Requested information not found.');
            }
            MessageAttachment::where('id', $request->input('id'))->update(['is_deleted' => 1]);
            return redirect(route('managemessageattachment.list'))->with('success', 'Message attachment deleted successfully.');
        } catch (\Exception $e) {
            return redirect(route('managemessageattachment.list'))->with('error', 'Error while deleting information.');
        }
    }

    public function download(Requestarg $request)
    {
        try {
            $requestData = $request->all();
            $messageAttachment = MessageAttachment::find($requestData['id']);
            if (empty($messageAttachment)) {
                return redirect(route('managemessageattachment.list'))->with('error', 'Requested information not found.');
            }
            $destinationPath = storage_path() . '/messageAttachments/';

            if (!empty($messageAttachment->url) && file_exists($destinationPath . $messageAttachment->url)) {
                $type = explode('.', $messageAttachment->url);
                $ext = $type[count($type) - 1];
                $headers = ['Content-Type: application/' . $ext];

                return response()->download($destinationPath . $messageAttachment->url, $messageAttachment->url, $headers);
            } else {
                return redirect(route('managemessageattachment.list'))->with('error', 'Error while downloading information.');
            }
        } catch (\Exception $e) {
            return redirect(route('managemessageattachment.list'))->with('error', 'Error while downloading information.');
        }
    }
    public function messageCenterList(Requestarg $request)
    {
        $columns = [
            ['name' => 'name', 'label' => 'Name'],
            ['name' => 'receiver_name', 'label' => 'Client'],
            ['name' => 'receiver_mobile', 'label' => 'Phone'],
            ['name' => 'created_at', 'label' => 'Date']
        ];
        if ($request->ajax()) {
            $filters = H::getFilterInputArr($request->all());

            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $order = $columns[$request->input('order.0.column')]['name'];
            $dir = $request->input('order.0.dir');
            $candidateName = "(CASE WHEN conversation.aoc_candidate_id > 0 THEN (select CONCAT(candidates.firstName,' ',candidates.lastName) from candidates where candidates.id=conversation.aoc_candidate_id) WHEN conversation.aoc_provider_id > 0 THEN (select CONCAT(mssql_providers.FirstName,' ',mssql_providers.LastName) from mssql_providers where mssql_providers.id=conversation.aoc_provider_id) ELSE '' END) as receiver_name";

            $query = DB::table('conversation')
            ->leftJoin('users', 'users.id', '=', 'conversation.user_id')
            ->select('conversation.id','users.name',DB::raw($candidateName),'conversation.receiver_mobile',DB::raw("DATE_FORMAT(conversation.created_at, '" . config('constant')['mysqlsearchdatetimeformat'] . "') as created_at"));

            $totalData = $query->count();
            $totalFiltered = $totalData;

            if (!empty($search)) {
                $q = $query->where('users.name', 'LIKE', "%{$search}%");
                $totalFiltered = $query->count();
            }
            $filters = [];
            if(isset($request['filters'])) {
                foreach($request['filters'] as $fl) {
                    if(!empty($fl['value'])) {
                        $filters[$fl['name']][] = $fl['value'];
                    }
                }
                foreach($filters as $fkey => $fval) {
                    if($fkey=='Recruiter') {
                        $query->where(function($query) use ($fval) {
                            $query->whereIn("conversation.user_id",$fval);
                        });
                    }elseif($fkey=='receiver') {
                        $query->where('conversation.'.$fval[0],'>', 0);
                    }
                    if (!empty($filters['from_date']) && !empty($filters['to_date'])) {
                        $query->whereBetween('conversation.created_at', [date('Y-m-d 00:00:00', strtotime($filters['from_date'][0])), date('Y-m-d 23:59:59', strtotime($filters['to_date'][0]))]);
                    }
                }
                $totalFiltered = $query->count();
            }
            H::ExcelLog('messageCenterList: ' . $query->toSql(), 'messageCenterList.log');
            $data = $query->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $data,
            ]);
        }
        return view('messageWidget.grid', ['columns' => $columns]);
    }
    public function setConversationToRead(Requestarg $request){
        try {
            $conversationId = $request->input('conversationId');
            if($conversationId != ''){
                DB::table('conversation')->where('sid',$conversationId)->update(['is_read'=>1]);
                return response()->json(['status'=>true]);
            }else{
                return response()->json(['status'=>false]);
            }
        } catch (\Exception $e) {
            H::ExcelLog('setConversationToRead:'.$e->getMessage(),'conversation.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }
    public function getMessagesByConversationId(Requestarg $request){
        try {
            $conversationId = $request->input('conversationId');
            $messages = [];
            if($conversationId != ''){
                $conversation = DB::table('conversation')->where('sid',$conversationId)->first();
                if(isset($conversation->id)){
                    $messages = DB::table('conversation_reply')->where('conversation_id',$conversation->id)->get()->toArray();
                }
                return response()->json(['status'=>true,'messages'=>$messages]);
            }else{
                return response()->json(['status'=>false]);
            }
        } catch (\Exception $e) {
            H::ExcelLog('getMessagesByConversationId:'.$e->getMessage(),'conversation.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }
    }

    public function getMessageCenterList(Requestarg $request)
    {
        $type = $request->input("type");
        $data = H::getHelper("MessageWidget")->getMessageCenterList($type, $request);
        $datatableObj = Datatables::of($data)->editColumn('last_received', function ($row) {
            return H::getFormatedDateTime($row->last_received);
        })->editColumn('last_send', function ($row) {
            return H::getFormatedDateTime($row->last_send);
        })->addColumn('Action', function ($row) {
            $btn = '';
            $btn .= ' <a href="javascript:void(0);" onclick="getConversationDetail(\'' . $row->id . '\')"><i class="fa fa-eye" aria-hidden="true"></i></a>';
            if(LD::checkFeatureFlag('add-way-to-join-conversation')){
                if(H::canPermissions('join_conversation')){
                    if($row->user_id != Auth::user()->id){
                        $btn .= ' <a href="javascript:void(0);" class="joinConversationBtn" title="Join Conversation" onclick="widget.joinConversation(this,\'' . $row->id . '\',\'' . $row->sid . '\')"><i class="fa fa-comment-o" aria-hidden="true"></i></a>';
                    }
                }
            }
            if(LD::checkFeatureFlag('take-over-conversation')){
                if(H::canPermissions('take_over_conversation')){
                    if($row->user_id != Auth::user()->id){
                        $btn .= ' <a href="javascript:void(0);" class="takeOverConversationBtn" title="Take Over Conversation" onclick="widget.takeOverConversation(this,\'' . $row->id . '\',\'' . $row->sid . '\')"><i class="fa fa-comments-o" aria-hidden="true"></i></a>';
                    }
                }
            }
            return $btn;
        });
        return $datatableObj->escapeColumns('Action')->escapeColumns('select')->make(true);
    }
    public function reloadCountMessageCenterList(Requestarg $request)
    {
        $type = $request->input("type");
        return json_encode(["counter" => H::getHelper('MessageWidget')->getMessageCenterList($request->input("type"), $request, 1), "type" => $request->input("type")]);
    }
    public function setConversationToArchive(Requestarg $request){
        try{
            $conversationId = $request->input('conversationId');
            if($conversationId != ''){
                H::getHelper("MessageWidget")->setConversationToArchive($conversationId);
                return response()->json(['status'=>true]);
            }else{
                return response()->json(['status'=>false]);
            }
        } catch (\Exception $e) {
            H::ExcelLog('setConversationToArchive:'.$e->getMessage(),'conversation.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }

    }
    public function getConversationDetail(Requestarg $request){
        $messages = $conversation = [];
        try {
            $conversationId = $request->input('conversationId');
            if($conversationId != ''){
                $conversation = DB::table('conversation')->where('id',$conversationId)->first();
                if(isset($conversation->id)){
                    $messages = DB::table('conversation_reply')->where('conversation_id',$conversation->id)->orderBy('created_at')->get()->toArray();
                }
            }
            $messages = H::objToArr($messages);
        } catch (\Exception $e) {
            H::ExcelLog('getConversationDetail:'.$e->getMessage(),'conversation.log');
        }
        return view('messageWidget.conversationDetail', ['messages' => $messages,'conversation' => $conversation]);
    }
    public function addParticipantToConversation(Requestarg $request){
        try {
            $conversationId = $request->input('id');
            if($conversationId != ''){
                $userId = Auth::user()->id;
                $conversation = DB::table('conversation')->where('id',$conversationId)->first();
                $participantIds = $userId;
                if($conversation->participant_ids != ''){
                    $participantIds = $conversation->participant_ids.','.$participantIds;
                }
                if(strpos($conversation->participant_ids,(String)$userId) === false){
                    DB::table('conversation')->where('id',$conversationId)->update(['participant_ids'=>$participantIds]);
                }
                return response()->json(['status'=>true,'msg'=>'Participant Added']);
            }else{
                return response()->json(['status'=>false,'msg'=>'Conversation Id required.']);
            }

        } catch (\Exception $e) {
            H::ExcelLog('addParticipantToConversation:'.$e->getMessage(),'conversation.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }

    }
    public function updateUserIdForTakeOverConversation(Requestarg $request){
        try {
            $conversationId = $request->input('id');
            if($conversationId != ''){
                $userId = Auth::user()->id;
                DB::table('conversation')->where('id',$conversationId)->update(['user_id'=>$userId]);
                return response()->json(['status'=>true,'msg'=>'Participant Changed']);
            }else{
                return response()->json(['status'=>false,'msg'=>'Conversation Id required.']);
            }
        } catch (\Exception $e) {
            H::ExcelLog('updateUserIdForTakeOverConversation:'.$e->getMessage(),'conversation.log');
            return response()->json(['status'=>false,'msg'=>$e->getMessage()]);
        }

    }
}
