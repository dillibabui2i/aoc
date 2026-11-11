<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
class TextTemplate extends Model
{
    use HasFactory;
    protected $table = 'templates';

    public static function getTemplateList($department = null,$category= null) {
        $templateList = self::select('id','template_name', 'template');
        if(!empty($department)) {
            $templateList->where('department',$department);
        }
        if(!empty($category)) {
            $templateList->where('category',$category);
        }
        return $templateList->get();
    }

    public static function replaceTemplateKeywords($receiverId,$requestData) {
        $message=$requestData['send_message'];
        $department=$requestData['department'];
        $replaceArr = [];
        $senderInformation = User::find(Auth::user()->id);
        $replaceArr['sender_name'] = $senderInformation->name;
        if(!empty($requestData['ooo_from_date'])) {
            $replaceArr['ooo_from_date'] = H::getFormatedDate($requestData['ooo_from_date']);
        }
        if(!empty($requestData['ooo_to_date'])) {
            $replaceArr['ooo_to_date'] = H::getFormatedDate($requestData['ooo_to_date']);
        }
        $replaceArr['sender_phone_number'] = $senderInformation->phone_number;
        if($department == 'Client Care Coordinator') {
            $patientDetail = DB::table("mssql_patients")
            ->select('mssql_patients.id',
            'mssql_patients.Patient_FirstName as client_first_name',
            'mssql_patients.Patient_LastName as client_last_name',
            'mssql_facilities.Phone as branch_number'
            )->leftJoin("mssql_facilities",function($query){
                $query->on("mssql_patients.aoc_facility_id","=","mssql_facilities.id");
            })
            ->where("mssql_patients.id",$receiverId)->first();
            $replaceArr['client_first_name'] = $patientDetail->client_first_name;
            $replaceArr['client_last_name'] = $patientDetail->client_last_name;
            $replaceArr['branch_number'] = $patientDetail->branch_number;
        }
        $departmentKeyworkList = config('constant')['massTextKeywords'][$department];
        foreach ($departmentKeyworkList as $keyword) {
            if (strpos($message, $keyword) !== false && !empty($replaceArr[$keyword])) {
                $message = str_replace('['.$keyword.']',$replaceArr[$keyword],$message);
            }
        }
        return $message;
    }
}
