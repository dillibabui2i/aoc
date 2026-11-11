<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class ExternalUserPatients extends Model
{
    use HasFactory;
    protected $table = 'external_user_patients';

    protected static function getPatientList($external_user_id)
    {
        $patientList = [];
        $patientList = self::select("external_user_patients.aoc_patient_id as patient_id", 
        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
        IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
        mssql_patients.Patient_LastName) as patient_full_name"),
        'mssql_patients.rdod_id',
        'mssql_patients.dbo_id',
        'mssql_patients.ccc_id',
        'mssql_patients.ic_id',
        'mssql_patients.r_id',
        'mssql_patients.ccm_id')
        ->join('mssql_patients','mssql_patients.id','=','external_user_patients.aoc_patient_id')
        ->where('external_user_id', $external_user_id)
        ->where('status', 1)->get()->toArray();
        return $patientList;
    }
    protected static function getClientEmergencyContactDetails($aoc_patient_id){
        return DB::table('client_emergency_contacts')
        ->select('client_emergency_contacts.*',
        DB::raw("CONCAT(client_emergency_contacts.first_name,' ',client_emergency_contacts.last_name)
        as fullName"),)
        ->leftJoin('mssql_patients', 'client_emergency_contacts.client_id', '=',
        'mssql_patients.BillingInternalPatientID')
        ->where('mssql_patients.id', $aoc_patient_id)->get();
    }
    protected static function checkPatientIdWithExternalUser($external_user_id,$aoc_patient_id)
    {
        return self::select("external_user_patients.aoc_patient_id")
        ->where('external_user_id', $external_user_id)
        ->where('aoc_patient_id', $aoc_patient_id)
        ->where('status', 1)->first();
    }
}
