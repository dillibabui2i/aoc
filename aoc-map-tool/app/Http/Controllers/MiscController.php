<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;

class MiscController extends Controller
{
    public function log(Request $request){
        try {
            $reqData = $request->all();
            $user = H::getLoggedInUser();
            if(isset($reqData['text']) && isset($reqData['fileName'])){
                $text = "********************** Frontend Logs **************************** \n";
                $text .= "User:".$user['name'].'('.$user['id'].')'."\n";
                $text .= $reqData['text'];
                $fileName = str_replace("\0", '', $reqData['fileName']);
                $fileName = preg_replace('/[^a-zA-Z0-9_-]/', '', $fileName);
                if (empty($fileName)) {
                    throw new \Exception("Invalid file name.");
                }
                H::ExcelLog($text,$fileName);
            }else{
                H::ExcelLog('text Or fileName are required:','fronendLogErr.log');
            }
        } catch (\Exception $e) {
            H::ExcelLog('error while logging from frontend:'.$e->getMessage(),'fronendLogErr.log');
        }

        return response()->json(['status'=>true]);
    }
}
