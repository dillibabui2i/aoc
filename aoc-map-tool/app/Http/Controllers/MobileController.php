<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Auth;
use App\Helpers\H;
class MobileController extends Controller
{
    //
    public function getExternalUserAutoCompleteList(Request $request) {
        $externalUserListData = [];
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $externalUserList = DB::table('external_users')->select(
            'external_users.id',
            'external_users.email',
            'external_users.name'
        )->where('external_users.aoc_provider_id',0)->where(function($query) use ($term) {
            $query->orWhere('external_users.email', 'like', '%' . $term . '%')->orWhere('external_users.name', 'like', '%' . $term . '%');
        })
        ->limit(15)->get();
        $externalUserList = json_decode(json_encode($externalUserList), true);
        foreach ($externalUserList as $clientSubData) {
            $externalUserListData[] = [
                'label' => $clientSubData['name'] . ' (' . $clientSubData['email'].')',
                'value' => $clientSubData['email'],
                'id' => $clientSubData['id']
            ];
        }
        return response()->json($externalUserListData);
    }

    public function saveMobileUser(Request $request) {
        try {
            if($request->status =='current') {
                $externalUserList = DB::table('external_users')->leftJoin('external_user_patients', 'external_user_patients.external_user_id', '=', 'external_users.id')->select('external_users.id')->where('external_users.email',$request->current_mobile_user_email)
                ->where('external_user_patients.aoc_patient_id',$request->aoc_patient_id)->first();
                if(empty($externalUserList)) {
                    DB::table('external_user_patients')->insert(['external_user_id' => $request->external_user_id,'aoc_patient_id' => $request->aoc_patient_id]);
                } else {
                    return response()->json(['success'=>0, 'message'=>'This email is already exists with this patient.']);
                }
            } else{
                $externalUserList = DB::table('external_users')->select('external_users.id')->where('external_users.email',$request->new_mobile_user_email)->first();
                if(!empty($externalUserList)) {
                    return response()->json(['success'=>0, 'message'=>'The email or phone number entered is assigned to an existing user. Please add a new email or phone number, or select the existing user.']);
                }
                $new_mobile_user_phone = H::getHelper('MassMessageWidget')->getFormatPhoneForMessage($request->new_mobile_user_phone);
                H::ExcelLog('getFormatPhoneForMessage: '.$new_mobile_user_phone,'saveMobileUser.log');
                $externalUserMobileData = DB::table('external_users')->select('external_users.id')->where('external_users.mobile_number',$new_mobile_user_phone)->first();
                if(!empty($externalUserMobileData)) {
                    return response()->json(['success'=>0, 'message'=>'The email or phone number entered is assigned to an existing user. Please add a new email or phone number, or select the existing user.']);
                }
                H::ExcelLog('saveMobileUser ','saveMobileUser.log');
                $saveExternalUserData = ['name' => $request->new_mobile_user_name,'email' => $request->new_mobile_user_email,'mobile_number' => $new_mobile_user_phone,'user_id' => Auth::user()->id];
                H::ExcelLog(print_r($saveExternalUserData, true),'saveMobileUser.log');
                $externalUserId = DB::table('external_users')->insertGetId($saveExternalUserData);
                DB::table('external_user_patients')->insert(['external_user_id' => $externalUserId,'aoc_patient_id' => $request->aoc_patient_id]);
            }
            return response()->json(['success'=>1, 'message'=>'Mobile app user created successfully']);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'message'=>'Something went wrong.'.$th->getMessage()]);
        }
        
    }
    public function removeMobileUser(Request $request) {
        try {
            H::ExcelLog('aoc_patient_id '.$request->aoc_patient_id,'removeMobileUser.log');
            $externalUserData = DB::table('external_user_patients')->select('external_user_patients.external_user_id')->where('external_user_patients.aoc_patient_id',$request->aoc_patient_id)->first();
            if(!empty($externalUserData)) {
                H::ExcelLog('Not empty externalUserData ','removeMobileUser.log');
                $externalUserList = DB::table('external_users')->select('external_user_patients.aoc_patient_id')->leftjoin('external_user_patients','external_user_patients.external_user_id','=','external_users.id')->where('external_user_patients.external_user_id',$request->external_user_id)->get()->toArray();
                DB::table('external_user_patients')->where('external_user_patients.aoc_patient_id',$request->aoc_patient_id)
                ->where('external_user_patients.external_user_id',$request->external_user_id)->delete();
                H::ExcelLog('Total users-'.count($externalUserList),'removeMobileUser.log');
                if(count($externalUserList) == 1){
                    H::ExcelLog('external_user_id '.$request->external_user_id,'removeMobileUser.log');
                    DB::table('external_users')->where('external_users.id',$request->external_user_id)->delete();
                }
            }
            return response()->json(['success'=>1, 'message'=>'Mobile app user deleted successfully']);
        } catch (\Throwable $th) {
            return response()->json(['success'=>0, 'message'=>'Something went wrong.'.$th->getMessage()]);
        }
        
    }
    public function getMobileUser(Request $request) {
        $mobileExistingUser = DB::table('external_users')->select("external_users.email","external_users.mobile_number","external_users.name","external_user_patients.aoc_patient_id","external_users.id")->join('external_user_patients','external_users.id','=','external_user_patients.external_user_id')->where('external_user_patients.aoc_patient_id',$request->aoc_patient_id)->where('external_users.status', 1)->where('external_user_patients.status', 1)->get();
        return view('client.tabs._mobileAppUserList', ['mobileExistingUser'=>$mobileExistingUser]);
    }
}
