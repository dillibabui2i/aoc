<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\Helpers\LD;
use App\SocialMedia;
use App\NeedToHireAccounts;
use App\MssqlPatients;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use DB;
use Schema;

class SocialMediaController extends Controller
{
    public function getAdsInfoForm(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $id = $request->input('id');
                $socialMedia =  $needToHireAccounts = $patientsDetails = [];
                if($request->input('id') > 0) {
                    $socialMedia = SocialMedia::find($request->input('id'));
                    $needToHireAccounts = NeedToHireAccounts::find($socialMedia->aoc_nthaccounts_id);
                    $patientsDetails = MssqlPatients::find($needToHireAccounts->aoc_patient_id);
                }
                return view('socialmedia.adsInfoForm', ['socialMediaId'=>$id,'socialMedia'=>$socialMedia, 'needToHireAccounts' => $needToHireAccounts, 'patientsDetails' => $patientsDetails]);
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(),'socialMedia.log');
        }
    }

    public function getSocialMediaClosePopup(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $id = $request->input('id');
                if($id > 0) {
                    $socialMedia = SocialMedia::select('social_media_ads.id as sid', 'social_media_ads.ad_name as sname')->where('social_media_ads.id', '!=', $id)->leftJoin("NeedToHire_Accounts", function($query){
                        $query->on("NeedToHire_Accounts.id","=","social_media_ads.aoc_nthaccounts_id");
                    })->where("NeedToHire_Accounts.IsActive", 1)->where("ad_placed", 1)->where('completed', 0)->get();
                    return view('socialmedia.closePopup', ['socialMediaId' => $id, 'socialMedia' => $socialMedia]);
                }
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(),'socialMedia.log');
        }
    }

    public function saveAdsForm(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                if ($request->input('id') > 0) {
                    $updateArr = $request->except(['id', '_token']);
                    $updateArr['start_date'] = date('Y-m-d H:i:s', strtotime($updateArr['start_date']));
                    $updateArr['ad_placed'] = 1;
                    if(LD::checkFeatureFlag('add-date-field-into-social-media-sponsored-bucket')){
                        $updateArr['date_posted'] = date('Y-m-d');
                    }
                    SocialMedia::where('id', $request->input('id'))->update($updateArr);
                } else {
                    $insertArr = $request->except(['id', '_token']);
                    $insertArr['start_date'] = date('Y-m-d H:i:s', strtotime($insertArr['start_date']));
                    $insertArr['ad_placed'] = 1;
                    $insertArr["created_at"]= date('Y-m-d H:i:s');
                    $insertArr['created_by']=Auth::user()->id;
                    $insertArr["updated_at"]= date('Y-m-d H:i:s');
                    if(LD::checkFeatureFlag('add-date-field-into-social-media-sponsored-bucket')){
                        $insertArr['date_posted'] = date('Y-m-d');
                    }
                    SocialMedia::insert($insertArr);
                }
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(),'socialMedia.log');
        }
    }
    public function markAsCompletedAds(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                if(LD::checkFeatureFlag('add-date-field-into-social-media-sponsored-bucket')){
                    $data['date_posted'] = date('Y-m-d H:i:s');
                }
                    $data['completed'] = 1;
                    $budgetFrom = $budgetTo = 0;
                    $budgetFromAdsName = '';
                    $budgetToAdsName = [];
                    if ($request->input('ads_close_reason') != '') {
                        $data['ads_close_reason'] = $request->input('ads_close_reason');
                        $socialMedia = SocialMedia::find($request->input('id'));
                        $budgetFromAdsName = $socialMedia->ad_name;
                        $data['budget'] = (float)$socialMedia->budget - (float)$socialMedia->actual_spend;
                        $budgetFrom = $data['budget'];
                    }
                    if ($request->input('move_remaining_budget') != '') {
                        $otherAds = $request->input('move_remaining_budget');
                        $amountSplit = round($data['budget'] / count($otherAds), 2);
                        $budgetTo = $amountSplit;
                        foreach ($otherAds as $otherAdsId) {
                            $tmp = SocialMedia::find($otherAdsId)->ad_name.' ('.$otherAdsId.')';
                            $budgetToAdsName[] = $tmp;
                            SocialMedia::find($otherAdsId)->increment('budget', $amountSplit);
                            $myNote = '$'.number_format($amountSplit,2).' budget was moved from '.$budgetFromAdsName.' ('.$request->input('id').') to ' . $tmp . ' by ' . Auth::user()->name;
                            H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'social', 'entity_id' => $otherAdsId, 'notes' => $myNote]);
                        }
                    }
                    SocialMedia::where('id',$request->input('id'))->update($data);

                    $myNote = '$'.number_format($budgetFrom,2).' budget was moved from '.$budgetFromAdsName.' ('.$request->input('id').') to '.implode(', ', $budgetToAdsName).' by ' . Auth::user()->name;
                    $closeNote = $budgetFromAdsName.' is closed by '.Auth::user()->name;
                    H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'social', 'entity_id' => $request->input('id'), 'notes' => $myNote]);
                    H::getHelper('ActivityNotes')->setActivityNotes(['entity' => 'social', 'entity_id' => $request->input('id'), 'notes' => $closeNote]);
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(),'socialMedia.log');
        }
    }
    public function getSocialMediaDetails(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $socialMedia = SocialMedia::find($request->input('id'));
                return view('socialmedia.details',['socialMedia'=>$socialMedia]);
            }
        } catch (\Exception $e) {
            H::ExcelLog($e->getMessage(),'socialMedia.log');
        }
    }

    public function getAllSocialMedia(Request $request){
        $columns = [
            ['name' => 'id','label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'ad_name', 'label' => 'Ad Name', 'width' => '15%'],
            ['name' => 'city', 'label' => 'City', 'width' => '10%'],
            ['name' => 'state', 'label' => 'State', 'width' => '10%'],
            ['name' => 'discipline', 'label' => 'Discipline', 'width' => '10%'],
            ['name' => 'start_date', 'label' => 'Start Date', 'width' => '10%'],
            ['name' => 'budget', 'label' => 'Budget', 'width' => '10%'],
            ['name' => 'actual_spend','label' => 'Actual Spend', 'width' => '10%'],
            ['name' => 'post_engagement','label' => 'Post Engagement', 'width' => '10%'],
            ['name' => 'link_clicks','label' => 'Link Clicks', 'width' => '5%'],
        ];

        if ($request->ajax()) {
            $res = $this->getFilteredData($request, $columns);
            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($res['totalData']),
                'recordsFiltered' => intval($res['totalFiltered']),
                'data'            => $this->DataProcess($res['data']),
            ]);
        }
        return view('socialmedia.grid',['columns' => $columns]);
    }

    protected function getFilteredData($request, $columns, $mode="showAll"){
        $reqData = $request->all();
        $search = $request->input('search.value');
        $limit = $request->input('length');
        $start = $request->input('start');
        $order = isset($columns[$request->input('order.0.column')]) ? $columns[$request->input('order.0.column')]['name'] : '';
        $dir = $request->input('order.0.dir');

        $q = SocialMedia::Select("social_media_ads.*");
        $q->where('social_media_ads.is_deleted', 0);

        $totalData = $q->count();
        $totalFiltered = $totalData;

        if (!empty($search)) {
            $q->whereNested(function($q) use ($search) {
                $q->where('social_media_ads.ad_name', 'LIKE', "%{$search}%")
                ->orWhere('social_media_ads.city', 'LIKE', "%{$search}%")
                ->orWhere('social_media_ads.state', 'LIKE', "%{$search}%")
                ->orWhere('social_media_ads.discipline', 'LIKE', "%{$search}%")
                ->orWhere(DB::raw('DATE_FORMAT(social_media_ads.start_date, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%")
                ->orWhere('social_media_ads.budget', 'LIKE', "%{$search}%")
                ->orWhere('social_media_ads.actual_spend', 'LIKE', "%{$search}%")
                ->orWhere('social_media_ads.post_engagement', 'LIKE', "%{$search}%")
                ->orWhere('social_media_ads.link_clicks', 'LIKE', "%{$search}%");
            });
            $totalFiltered = $q->count();
        }

        if (!empty($reqData['filters'])) {
            $state = [];
            $discipline = [];
            $ad_type = [];
            $category = [];
            $daterange = [];
            $include_completed = "";
            foreach($request['filters'] as $key => $value) {
                if($value['name']=='state') {
                    $state[] = $value['value'];
                }
                if($value['name']=='discipline') {
                    $discipline[] = $value['value'];
                }
                if($value['name']=='ad_type') {
                    $ad_type[] = $value['value'];
                }
                if($value['name']=='category') {
                    $category[] = $value['value'];
                }
                if($value['name']=='from_date') {
                    $daterange['from_date'] = $value['value'];
                }
                if($value['name']=='to_date') {
                    $daterange['to_date'] = $value['value'];
                }
                if($value['name']=='include_completed') {
                    $include_completed = $value['value'];
                }
            }
            if(count($state)>0) {
                $q->where(function($qr) use ($state){
                    foreach($state as $s) {
                        $qr->orWhere("social_media_ads.state","=",$s);
                    }
                });
            }
            if(count($discipline)>0) {
                $q->where(function($qr) use ($discipline){
                    foreach($discipline as $b) {
                        $qr->orWhere("social_media_ads.discipline","=",$b);
                    }
                });
            }
            if(count($ad_type)>0) {
                $q->where(function($qr) use ($ad_type){
                    foreach($ad_type as $d) {
                        $qr->orWhere("social_media_ads.ad_type","=",$d);
                    }
                });
            }
            if(count($category)>0) {
                $q->where(function($qr) use ($category){
                    foreach($category as $c) {
                        $qr->orWhere("social_media_ads.category","=",$c);
                    }
                });
            }
            if (!empty($daterange['from_date']) && !empty($daterange['to_date'])) {
                $q->whereBetween('social_media_ads.start_date', [date('Y-m-d', strtotime($daterange['from_date'])), date('Y-m-d', strtotime($daterange['to_date']))]);
            }
            if (empty($include_completed)) {
                $q->where('social_media_ads.completed', '<>' , 1);
            }
            $totalFiltered = $q->count();
        }

        if($mode != 'export'){
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();
        }else{
            $data = $q->get();
        }
        return ['data'=>$data,'totalData'=>$totalData,'totalFiltered'=>$totalFiltered];
    }

    public function exportSocialMediaAd(Request $request){
        $res = $this->getFilteredData($request,[],'export');
        $dataTableFields = Schema::Connection('mysql')->getColumnListing('social_media_ads');
        $data = json_decode(json_encode($res['data']->toArray()), true);
        $headers = [
            'Content-type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename=social-media-ad.csv",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];
        $callback = function () use ($data, $dataTableFields) {
            $file = fopen('php://output', 'w');
            fputcsv($file, array_map(function ($value) {return $value;},array_values($dataTableFields)));
            foreach ($data as $tableData) {
                $cntr = 0;
                $csvData = [];
                foreach ($dataTableFields as $val => $fields) {
                    $csvData[] = $tableData[$fields];
                    $cntr++;
                }
                fputcsv($file, $csvData);
            }
            fclose($file);
        };
        return response()->stream($callback, 200, $headers);
    }

    private function DataProcess($data) {
        foreach ($data as $key => $value) {
            $value->start_date = H::getFormatedDate($value->start_date);
        }
        return $data;
    }
}
