<?php
namespace App\Helpers;

use App\SocialMedia as SM;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class SocialMedia
{
    public function getDataTableHtml($type,$label)
    {
        $dataTable = [];
        switch($type) {
            case 'social_media_request':
                $dataTable = [
                    'ClientNameCustom' => 'Patient',
                    'request_created_at' => 'Created Date/Time',
                    'cityState' => 'City/State',
                    'Patient_Zip' => 'Zip Code',
                    'discipline' => 'Discipline',
                    'skills_diagnosis' => 'Skills',
                    'shifts_needed' => 'Shifts Needed',
                ];
                break;
            case 'social_media_active_ads':
                $dataTable = ['ad_name'=>'Ad Name','start_date'=>'Start Date','state'=>'State','city'=>'City','budget'=>'Budget','ad_type'=>'Ad Type'];
                break;
            default:
                break;
        }

        $isRequestsBucket = $type === 'social_media_request';
        $defaultSortColumn = $isRequestsBucket ? 'request_created_at' : 'state';
        $defaultSortColumnIndex = array_search($defaultSortColumn, array_keys($dataTable), true);
        $defaultOrder = [[$defaultSortColumnIndex, 'desc']];

        return view('socialmedia.datatable', [
            'type' => $type,
            'label' => $label,
            'dataTable' => $dataTable,
            'isRequestsBucket' => $isRequestsBucket,
            'defaultOrder' => $defaultOrder,
        ]);
    }

    public function getSocialMediaByType($type, $request = '',$showCount=0) {
        $bucket = DB::table("social_media_ads");
        $bucket->leftJoin("NeedToHire_Accounts",function($query){
            $query->on("NeedToHire_Accounts.id","=","social_media_ads.aoc_nthaccounts_id");
        });
        $bucket->leftJoin("mssql_patients",function($query){
            $query->on("mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id");
        });
        $bucket->where("NeedToHire_Accounts.IsActive","=",1);
        $bucket->select(
            '*',
            'mssql_patients.id as mpid',
            'social_media_ads.id as socialMediaId',
            DB::raw("CONCAT(social_media_ads.city,'/',social_media_ads.state) AS cityState"),
            DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS ClientNameCustom"),
            'social_media_ads.created_at as request_created_at'
        );

        switch($type) {
            case 'social_media_request':
                $bucket->where(function (Builder $query): void {
                    $query->where('ad_placed', 0)->orWhereNull('ad_placed');
                });
                $bucket->where('social_media_ads.completed', 0);
                $bucket->active('social_media_ads');
                break;
            case 'social_media_active_ads':
                if (!isset($request['filters'][0]['name'])) {
                    $bucket->where("ad_placed",1)->where('completed',0);
                }
                break;
            default:
                break;
        }

        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket;
    }
    public function getReportData(){
        $data = DB::table('social_media_budgets')->select('social_media_budgets.budget as budget','social_media_budgets.state as state','social_media_budgets.category as category',DB::raw('(select sum(social_media_ads.budget) from social_media_ads where MONTH(start_date) = MONTH(NOW()) and state = social_media_budgets.state and category = social_media_budgets.category) as allocated_budget'),DB::raw('(select sum(social_media_ads.actual_spend) from social_media_ads where MONTH(start_date) = MONTH(NOW()) and state = social_media_budgets.state and category = social_media_budgets.category) as actual_spend'))->groupBy('social_media_budgets.state','social_media_budgets.category')->get();
        $reportData = [];
        foreach ($data as $key => $value) {
            $reportData[$value->state][] = $value;
        }
        return $reportData;
    }

    public function getSocialMediaForPatient($aoc_nthaccounts_id) {
        $data = [];
        $data = DB::table("social_media_ads")->select('social_media_ads.id','social_media_ads.start_date','social_media_ads.aoc_nthaccounts_id','social_media_ads.completed','social_media_ads.ad_placed','social_media_ads.ad_name')->where("ad_placed",1)->where("aoc_nthaccounts_id",$aoc_nthaccounts_id)->orderBy('start_date','DESC')->first();
        return $data;
    }
}
