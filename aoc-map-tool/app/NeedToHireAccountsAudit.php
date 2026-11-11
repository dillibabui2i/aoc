<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NeedToHireAccountsAudit extends Model
{
    use HasFactory;
    protected $table = 'NeedToHire_Accounts_Audit';
    protected $fillable = ['aoc_nthaccounts_id','aoc_patient_id','user_id','NeedToHireAccountsID','ClientID','MatchInEMR','IsActive','CreatedReason','RemovalReason','ServicesNeeded','Recruiter','Status','IsPriority','ScheduleText','RespiteTerminalPayer','TerminalPayer','IntakeCoordinator','CreatedOn','CreatedBy','ModifiedOn','HoursAddedOn','ModifiedBy','is_deleted','is_updated','created_by','updated_by','liftingRequirements','clinicianDiscipline','skills','ClientCareCoordinator','BD_Rep','created_at','updated_at'];
}
