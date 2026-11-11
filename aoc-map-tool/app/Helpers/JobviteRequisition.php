<?php

namespace App\Helpers;

use App\Candidates as Candidates;
use Carbon\Carbon;
use App\Jobs;
use Illuminate\Support\Facades\DB;

class JobviteRequisition
{
    public function getRequisitionData($viewType="dashboard",$bucketType = '',$searchfilter=[],$showCount=0,$request='') {
        $loggedInUser = H::getLoggedInUser();
        $bucket = null;

        $state = $jobviteCategory = [];
        if(!empty($searchfilter)) {
            foreach($searchfilter as $filter) {
                $filterName = $filter['name'];
                $filterValue = $filter['value'];
                if($filterName=='jobvitestate' ) {
                    $state[] =$filterValue;
                }
                if($filterName=='Category' ) {
                    $jobviteCategory[] =$filterValue;
                }
            }
        }


        if($bucketType=='jobvite_requisition_jobviteopen') {
            $bucket = DB::table("NeedToHire_Checklist")->distinct()->select("NeedToHire_Hub.Hub","NeedToHire_Hub.State",DB::raw("jobvite_category.category as JobCategory"))
            ->leftJoin('NeedToHire_Accounts','NeedToHire_Accounts.id', '=', 'NeedToHire_Checklist.aoc_nthaccounts_id')
            ->leftJoin('mssql_patients','mssql_patients.id', '=', 'NeedToHire_Accounts.aoc_patient_id')
            ->join('NeedToHire_Hub', function ($join) {
                $join->on('NeedToHire_Hub.City', '=', 'mssql_patients.Patient_City')
                     ->on('NeedToHire_Hub.ShortState', '=', 'mssql_patients.Patient_State');
            })
            ->join('jobvite_category', function ($join) {
                $join->on('jobvite_category.state', '=', 'NeedToHire_Hub.State')
                     ->on('jobvite_category.service', '=', DB::raw("(case when NeedToHire_Accounts.ServicesNeeded in ('PDN/Respite', 'Respite', 'Skilled Nursing', 'PDN') then 'PDN'
                     when NeedToHire_Accounts.ServicesNeeded in ('CNA', 'IHSS') then 'CNA'
                     else '' end)"));
            })
            ->where("NeedToHire_Accounts.IsActive",1)
            ->where("NeedToHire_Checklist.HubCity",0);
            
            if(count($state)>0){
                $bucket->where(function($q) use ($state){
                    foreach($state as $s) {
                        $q->orWhere("NeedToHire_Hub.State","=",$s);
                    }
                });
            }

            if(count($jobviteCategory)>0){
                $bucket->where(function($q) use ($jobviteCategory){
                    foreach($jobviteCategory as $j) {
                        $q->orWhere("jobvite_category.category","=",$j);
                    }
                });
            }
    
        } else if ($bucketType=='jobvite_requisition_jobviteclosed') {
            $bucket = DB::table("jobs")->select(
            "jobs.id",
            "jobs.title",
            "jobs.locationCity",
            "jobs.locationState",
            "jobs.category",
            DB::raw('DATE_FORMAT(jobs.sentDate,"'.config('constant')['mysqlsearchdatetimeformat'].'") as dateOpened'))
            ->where("jobState","Open")
            ->where("no_active_nth",1)
            ->where(function($q){
                $q->whereDate("no_active_nth_resolved_on","<",date("Y-m-d"))->orWhereNull("no_active_nth_resolved_on");
            });

            if(count($state)>0){
                $bucket->where(function($q) use ($state){
                    foreach($state as $s) {
                        $q->orWhere("jobs.locationState","=",$s);
                    }
                });
            }

            if(count($jobviteCategory)>0){
                $bucket->where(function($q) use ($jobviteCategory){
                    foreach($jobviteCategory as $j) {
                        $q->orWhere("jobs.category","=",$j);
                    }
                });
            }
           
        }
       
        if($showCount) {
            $values =  $bucket->get();
            return '('.count($values).')';
        }
        return $bucket->get();
    }

    public function getDataTableHtml($type)
    {

        $dataTable = [];
        switch($type) {

            case 'jobvite_requisition_jobviteopen':
            
                $dataTable = [
                    'Hub'=>'Hub',
                    'State'=>"State",
                    'JobCategory'=>'Job Category'
                ];
                break;
            case 'jobvite_requisition_jobviteclosed':
                $dataTable = [
                    'title'=>'Title',
                    'locationCity'=>"City",
                    'locationState'=>'State',
                    'category'=>'Job Category',
                    'dateOpened'=>'Date Opened',
                ];
                break;
            default:
                break;
        }

       return view('jobvite.requisitiondatatable',["type"=>$type,'dataTable'=>$dataTable]);
    }


    
}
