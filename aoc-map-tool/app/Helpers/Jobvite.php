<?php

namespace App\Helpers;

use App\Candidates as Candidates;
use App\Models\JobCategories;
use Carbon\Carbon;
use App\Jobs;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\DB;

class Jobvite
{
    public function getJobviteByStatus($status = '',$request='', $showCount=0){
        $users = H::getLoggedInUser();
        $currentRole = H::getHelper('ConstantHelper')->getCurrentRolesByUserId($users['id']);
        $mytime = Carbon::now();
        $currentDateTime = $mytime->toDateString();
        $mytime = Carbon::tomorrow();
        $tomorrowDateTime = $mytime->toDateString();

        if(LD::checkFeatureFlag('jobvite-add-candidate-note')) {
            $jobvite = DB::table(DB::raw("(select candidate_id, max(id) as maxId from candidate_applications group by candidate_id) as x"))->select('candidates.appWorkFlowState', 'candidates.id','users.name as primaryRecruiter','candidates.fullName', 'jobs.title','jobs.locationCity', 'candidates.appSentDate as application_date', 'candidates.mobile', 'candidates.homePhone','candidates.email',DB::raw("DATE_FORMAT(candidates.FollowUpDateTime, '".config('constant')['mysqlsearchdatetimeformat']."') as FollowUpDateTime"),DB::raw("DATE_FORMAT(candidates.GraduationDate, '%m/%d/%Y') as GraduationDate"),'candidates.workFlowStateChangeDate',DB::raw("(select concat(DATE_FORMAT(notes.created_at,'%m/%d'),' ',users.name,' commented: ',notes.notes) from notes left join users on users.id = notes.created_by where entity = 'candidate' and entity_id = candidates.id order by notes.id desc limit 1) as Candidate_Notes"));
        }else{
            $jobvite = DB::table(DB::raw("(select candidate_id, max(id) as maxId from candidate_applications group by candidate_id) as x"))->select('candidates.appWorkFlowState', 'candidates.id','users.name as primaryRecruiter','candidates.fullName', 'jobs.title','jobs.locationCity', 'candidates.appSentDate as application_date', 'candidates.mobile', 'candidates.homePhone','candidates.email',DB::raw("DATE_FORMAT(candidates.FollowUpDateTime, '".config('constant')['mysqlsearchdatetimeformat']."') as FollowUpDateTime"),DB::raw("DATE_FORMAT(candidates.GraduationDate, '%m/%d/%Y') as GraduationDate"),'candidates.workFlowStateChangeDate');
        }


        $jobvite->leftJoin("candidate_applications",function($query){
            $query->on("x.maxId","=","candidate_applications.id");
        });
        $jobvite->leftJoin("candidates",function($query){
            $query->on("candidate_applications.candidate_id","=","candidates.id");
        });
        $jobvite->leftJoin("users",function($query){
            $query->on("users.id","=","candidates.user_id");
        });
        $jobvite->leftJoin("jobs",function($query){
            $query->on("candidate_applications.job_id","=","jobs.id");
        });

        $appSentDate = '2022-11-28';
        $jobvite->whereDate("candidates.appSentDate",">",$appSentDate);

        if (!H::getHelper('UserPermissionResolver')->isRoleHasPermission($currentRole, 'can_view_all_candidates')) {
            $userEmail = $users['email'];
        }
        if (isset($request['filters'][0]['value']) && !empty($request['filters'][0]['value']) && $request['filters'][0]['name'] == 'default') {
            $userEmail = $users['email'];
            $jobvite->where(function($query) use($userEmail, $users) {
                $query->where("jobs.recruiters","like",'%'.$userEmail.'%');
                $query->orWhere("candidates.user_id", $users['id']);
            });
        }
        if($status=='NEW') {
            $jobvite->where("appWorkFlowState","New");
        } else if($status=='recruiter_phone_screen') {
            $jobvite->where("appWorkFlowState","Recruiter Phone Screen");
            $jobvite->where(function($query) use($tomorrowDateTime) {
                $query->where('FollowUpDateTime', '<', $tomorrowDateTime);
                $query->orWhereNull("FollowUpDateTime");
                $query->orWhere('workFlowStateChangeDate',  '0000-00-00 00:00:00');
            });
        } else if($status=='jpet') {
            $jobvite->whereIn("appWorkFlowState",["P.E.T. #1", "P.E.T. #2", "P.E.T. #3"]);
            $jobvite->where(function($query) use($currentDateTime) {
                $query->where('workFlowStateChangeDate', '<', $currentDateTime);
                $query->orWhereNull("workFlowStateChangeDate");
                $query->orWhere('workFlowStateChangeDate',  '0000-00-00 00:00:00');
            });
            $jobvite->where(function($query) use($currentDateTime) {
                $query->where('PET1Date', '<', $currentDateTime);
                $query->orWhereNull("PET1Date");
                $query->orWhere('PET1Date',  '0000-00-00 00:00:00');
            });
            $jobvite->where(function($query) use($currentDateTime) {
                $query->where('PET2Date', '<', $currentDateTime);
                $query->orWhereNull("PET2Date");
                $query->orWhere('PET2Date',  '0000-00-00 00:00:00');
            });
            $jobvite->where(function($query) use($currentDateTime) {
                $query->where('PET3Date', '<', $currentDateTime);
                $query->orWhereNull("PET3Date");
                $query->orWhere('PET3Date',  '0000-00-00 00:00:00');
            });
            if(LD::checkFeatureFlag('add-pet-filter-to-jobvite-pet-bucket')){
                if(isset($request['filters'])){
                    $pet = '';
                    foreach ($request['filters'] as $key => $value) {
                        if ($value['name'] == 'pet' && !empty($value['value'])) {
                            $pet = $value['value'];
                        }
                    }
                    if($pet != ''){
                        $jobvite->where('appWorkFlowState','=', $pet);
                    }
                }
            }
        } else if($status=='pending_graduation_followup'){
            $jobvite->where("appWorkFlowState","Nursing Student: Pending Graduation");
            $jobvite->where(function($query) use($tomorrowDateTime) {
                $query->where('FollowUpDateTime', '<', $tomorrowDateTime);
                $query->orWhereNull("FollowUpDateTime");
                $query->orWhere('workFlowStateChangeDate',  '0000-00-00 00:00:00');
            });
            $jobvite->where(function($query) use($currentDateTime) {
                $query->where('workFlowStateChangeDate', '<', $currentDateTime);
                $query->orWhereNull("workFlowStateChangeDate");
                $query->orWhere('workFlowStateChangeDate',  '0000-00-00 00:00:00');
            });
        }
        //Jobvite filters added
        $categoryList = JobCategories::query()->where('is_enabled', true)
            ->pluck('category')
            ->toArray();
        if (isset($request['filters'])) {
            $where = $state = $category = $branch = $recruiter = $region = [];
            foreach ($request['filters'] as $key => $value) {
                if ($value['name'] == 'jobvitestate' && !empty($value['value'])) {
                    $state[] = $value['value'];
                } else if ($value['name'] == 'Category' && !empty($value['value'])) {
                    $category[] = $value['value'];
                } else if ($value['name'] == 'branch' && !empty($value['value'])) {
                    $branch[] = $value['value'];
                } else if ($value['name'] == 'recruiter' && !empty($value['value'])) {
                    $recruiter[] = $value['value'];
                } else if ($value['name'] == 'region' && !empty($value['value'])) {
                    $region[] = $value['value'];
                }
            }
            if (!empty($state)) {
                $jobvite->whereIn("jobs.state", $state);
            }
            if (!empty($branch)) {
                // PD-5392: selected branch is canonical; match all its spellings stored in jobs.branch
                $helper = H::getHelper('ConstantHelper');
                $jobvite->whereIn('jobs.branch', array_merge(...array_map(
                    fn($name) => $helper->getJobviteBranchAliases($name),
                    $branch
                )));
            }
            if (!empty($region)) {
                $regionArray = [];
                foreach ($region as $fieldValue) {
                    foreach (explode(",", $fieldValue) as $val) {
                        array_push($regionArray, $val);
                    }
                }
                $jobvite->whereIn("jobs.branch", $regionArray);
            }
            if (!empty($recruiter)) {
                $jobvite->whereIn("candidates.user_id", $recruiter);
            }
            if (!empty($category)) {
                $jobvite->whereIn("jobs.category", $category);
            }
            if (count($where) > 0) {
                $jobvite->whereRaw(implode(" AND ", $where));
            }
        }
        H::ExcelLog('getJobviteByStatus: ' . $jobvite->toSql(), 'getJobviteByStatus.log');
        if($showCount) {
            if(LD::checkFeatureFlag('dashboard-past-due-count')) {
                $data2 = $jobvite->get();
                $filtered_data = $data2->filter(function ($data2) use ($status) {
                    if ($status == 'NEW') {
                        return round((strtotime(date('Y-m-d H:i:s')) - strtotime($data2->application_date))/3600, 1) > 1;
                    } else if ($status == 'recruiter_phone_screen') {
                        return round((strtotime(date('Y-m-d H:i:s')) - strtotime($data2->FollowUpDateTime))/(60 * 60 * 24), 1) > 1;
                    } else {
                        return round((strtotime(date('Y-m-d H:i:s')) - strtotime($data2->workFlowStateChangeDate))/(60 * 60 * 24), 1) > 1;
                    }
                });
                return '<span>('.$jobvite->count().' | '.$filtered_data->count().' Past Due)</span>';
            } else {
                return '('.$jobvite->count().')';
            }
        }
        return $jobvite->get();
    }

    public function getDataTableHtml($type, string $idPrefix = '', string $filterFormId = '')
    {
        switch($type) {
            case 'nth_expiring_jobs':
                $field = [
                    'requisitionId' => 'Req ID',
                    'title' => 'Title',
                    'department' => 'Department',
                    'category' => 'Category',
                    'locationCity' => 'City',
                    'locationState' => 'State',
                    'created_at' => 'Created On',
                ];
                return view('jobs.datatable',["type" => $type,"field" => $field]);
            default:
                $field = [];
                return view('jobvite.datatable', [
                    'type' => $type,
                    'field' => $field,
                    'idPrefix' => $idPrefix,
                    'filterFormId' => $filterFormId,
                ]);
        }
    }

    //TODO implement in future tasks
    public function getDataTableHtmlTherapy($type): Renderable
    {
        $field = [];

        return view('jobvite.datatable-therapy', ['type' => $type, 'field' => $field]);
    }

    public function getNTHExpiringJobs($showCount = 0) {
        $bucket = Jobs::where('jobState', 'Open')->where(
            'created_at', '<', Carbon::now()->subDays(30)->toDateTimeString()
        );
        $bucket->select('*');

        if($showCount) {
            return '('.$bucket->count().')';
        }

        return $bucket;
    }
    public function getCandidateFromJobvite($id)
    {
        $apicall = env('JOBVITE_API_URL') . '/candidate?candidateId=' . $id;
        echo $apicall . "\n";
        $data = $this->getCurl($apicall,"candidate");
        if (count($data['candidates']) > 0) {
            foreach ($data['candidates'] as $c) {
                return $c;
            }
        }
    }

    public function getCurl($url = 'https://api.jobvite.com/api/v2/job',$type='job')
    {
        echo $url . "\n";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, H::getHelper("Jobvite")->getJobviteAuthenticationHeader($type));
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }


    public function getJobviteAuthenticationHeader($type='job'){
         // API credentials
         if($type=='job') {
         $apiKey = env('JOBVITE_JOBS_API_KEY');
         $apiSecret = env('JOBVITE_JOBS_SECRET_KEY');
         } else {
            $apiKey = env('JOBVITE_API_KEY');
            $apiSecret = env('JOBVITE_SECRET_KEY');
         }
         $timestamp = time();
         // String to hash
         $stringToHash = $apiKey . '|' . $timestamp; // Replace '1476218468' with the epoch value you need

         // Generate the hashed value using HMAC-SHA256
         $hashedValue = hash_hmac('sha256',$stringToHash, $apiSecret,true);

         // Construct the headers
         $headers = array(
             'Content-Type: application/json',
             'X-JVI-SIGN: ' . base64_encode($hashedValue),
             'X-JVI-API: ' . $apiKey,
             'X-JVI-EPOCH: '.$timestamp // Replace with the epoch value you need
         );
         return $headers;
    }

    function csvToArray($filename = '', $delimiter = ','){

        if (!file_exists($filename) || !is_readable($filename))
            return false;

        $header = null;
        $data = array();
        if (($handle = fopen($filename, 'r')) !== false)
        {
            while (($row = fgetcsv($handle, 1000, $delimiter)) !== false)
            {
                if (!$header)
                    $header = $row;
                else
                    $data[] = array_combine($header, $row);
            }
            fclose($handle);
        }
        return $data;

    }
}
