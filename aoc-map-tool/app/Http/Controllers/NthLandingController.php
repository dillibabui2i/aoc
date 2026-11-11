<?php
namespace App\Http\Controllers;

use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Helpers\Geocoding;
use App\Services\NeedToHire\NthLandingService;

class NthLandingController extends Controller
{
    public function __construct(
        private NthLandingService $service
    ) {}

    public function nthRedirectPage(Request $request){
        $clinicianID =  H::getHelper("NTHLanding")->decrypt($request->encryptedClinicianId);
        if ($clinicianID === false) {
            return redirect('/nthlanding/error-page')->with('error', 'Something went wrong. Please try again later.');
        }
        $clinicianData = DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->first();
        if(empty($clinicianData)) {
            return redirect('/nthlanding/error-page')->with('error', 'Something went wrong. Please try again later.');
        }
        Session::put('clinicianID', $clinicianID);
        return redirect('/nthlanding/available-cases');
    }

    public function nthAvailableCases(Request $request){
        $clinicianID = Session::get('clinicianID');
        $clinicianData = DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->first();
        if(empty($clinicianData)) {
            return redirect('/nthlanding/error-page')->with('error', 'Something went wrong. Please try again later.');
        }
        return view('nthlanding.available_cases_form',["clinicianData"=>$clinicianData]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getGeoCities(Request $request): JsonResponse
    {
        $term = urlencode(trim(preg_replace('/\s+/', ' ', $request->term)));

        try {
            $apiKey = env('GOOGLE_API_KEY');
            $url = "https://maps.googleapis.com/maps/api/place/autocomplete/json?input=". $term ."&types=geocode&components=country:us&key=". $apiKey;
            $response = H::getCurl($url);

            $cnt=0;
            $cityLocationData = [];
            if(!empty($response['predictions'])) {
                foreach ($response['predictions'] as $prediction) {
                    $addressData = '';
                    if (isset($prediction['structured_formatting'])) {
                        $city = $prediction['structured_formatting']['main_text'];
                        $secondaryParts = explode(', ', $prediction['structured_formatting']['secondary_text']);
                        if (count($secondaryParts) >= 2) {
                            $state = $secondaryParts[0]; // State
                            $addressData = $city . ', ' . $state;
                        }
                    }elseif (isset($prediction['description'])) {
                        $parts = explode(', ', $prediction['description']);
                        if (count($parts) >= 2) {
                            $addressData = $parts[0] . ', ' . $parts[1];
                        }
                    }
                    $cityLocationData[$cnt] = ['label' => $addressData,'value' => $addressData];
                    $cnt++;
                }
            }

            return response()->json($cityLocationData);
        } catch (\Throwable $th) {
            H::ExcelLog('getGeoCities Throwable='.$th->getMessage(),'getGeoCities.log');
        }
    }

    public function nthErrorPage(Request $request){
        return view('nthlanding.error_page');
    }

    public function savePreferenceFromResult(Request $request) {


        try {
            $data = $request->all();
            $clinicianID = Session::get('clinicianID');

            $clinicianData = DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->first();

            $requestArray = [];

            $city = $mileRadius = '';
            $schedule = $skills = $environment = $language = [];
            $filters = isset($data['filters'])?$data['filters']:[];

            foreach($filters as $filter){
                if($filter['name']=='orderby') {
                    $requestArray['orderby'] = $filter['value'];
                }
                if($filter['name']=='city') {
                    $requestArray['city'] = $filter['value'];
                }
                if($filter['name']=='mile_radius') {
                    $requestArray['mile_radius'] = $filter['value'];
                }
                if($filter['name']=='schedule') {
                    $requestArray['schedule'][] = $filter['value'];
                }
                if($filter['name']=='skills') {
                    $requestArray['skills'][] = $filter['value'];
                }
                if($filter['name']=='environment') {
                    $requestArray['environment'][] = $filter['value'];
                }
                if($filter['name']=='language') {
                    $requestArray['language'][] = $filter['value'];
                }

            }


            $requestObject = new \Illuminate\Http\Request($requestArray);

            $requestObject->validate([
                'city' => 'required|string|max:255',
                'mile_radius' => 'required|numeric|min:0'
            ], [
                'city.required' => 'The city field is required.',
                'city.string' => 'The city must be a valid string.',
                'city.max' => 'The city must not exceed 255 characters.',
                'mile_radius.required' => 'The mile radius field is required.',
                'mile_radius.numeric' => 'The mile radius must be a number.',
                'mile_radius.min' => 'The mile radius must be at least 0.'
            ]);

            $this->savePreferenceData($requestObject,$clinicianData,$clinicianID);
            return response()->json(['error'=>false,'message'=>"Your Preference have been saved!"]);
        } catch(\Exception $e) {
            return response()->json(['error'=>true,'message'=>"Failed to save Preference Please try again."]);
        }


    }

    private function savePreferenceData(Request $request,$clinicianData,$clinicianID) {

        $noteStr = 'Clinician updated case preferences via web form '.  date('d/m/Y h:i A', strtotime(date('Y-m-d H:i:s')));

        $parts = explode(',', $request->city);
        $cityValue = trim($parts[0]);
        $stateValue = trim($parts[1]);

        $updateData = ['aoc_cf_city' =>$cityValue,'aoc_cf_state' =>$stateValue,'aoc_cf_mile_radius' =>$request->mile_radius,'Preference_Status' =>1];
        $noteStr = $noteStr .'<br>City:'.$cityValue.' <br>State:'.$stateValue.' <br>Mile Radius:'.$request->mile_radius;
        $scheduleReqData = $request->schedule ?? [];
        $updateData['aoc_cf_schedule'] = implode(',', $scheduleReqData);
        $noteStr = $noteStr .'<br>Shift Type:'.implode(',', $scheduleReqData);

        $skillsReqData = $request->skills ?? [];
        $updateData['aoc_cf_skills'] = implode(',', $skillsReqData);
        $noteStr = $noteStr .'<br>Skills:'.implode(',', $skillsReqData);

        $environmentReqData = $request->environment ?? [];
        $updateData['aoc_cf_environment'] = implode(',', $environmentReqData);
        $noteStr = $noteStr .'<br>Environment:'.implode(',', $environmentReqData);

        $liftingRequirementsReqData = $request->lifting_requirements ?? [];
        $updateData['aoc_cf_lifting_requirements'] = implode(',', $liftingRequirementsReqData);
        $noteStr = $noteStr .'<br>Lifting Requirements:'.implode(',', $liftingRequirementsReqData);

        if(!empty($request->orderby)) {
            $orderby = $request->orderby ?? [];
            $updateData['aoc_cf_default_order'] = $orderby;
            $noteStr = $noteStr .'<br>Default Sort Order:'.$orderby;

        }

        $languageReqData = $request->language ?? [];
        $languageReqDataStr = implode('##', $languageReqData);
        $updateData['aoc_cf_language'] = $languageReqDataStr;
        $noteStr = $noteStr .'<br>Language:'.$languageReqDataStr;
        H::ExcelLog('Updated ClinicianPreferences for clinicianID='.$clinicianID,'updateClinicianPreferences.log');


        DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->update($updateData);
        DB::table('maps_nthalerts')->where('ClinicianID', $clinicianID)->update(['Preference_Status' =>1]);
        $noteData = [];
        $noteData['entity'] = 'Clinician';
        $noteData['entity_id'] = $clinicianData->id;
        $noteData['notes'] = $noteStr;
        H::getHelper("ActivityNotes")->setActivityNotesFromConsole($noteData);
    }

    public function updateClinicianPreferences(Request $request){
        $clinicianID = Session::get('clinicianID');

        $clinicianData = DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->first();

        if(empty($clinicianData)) {
            return redirect('/nthlanding/error-page')->with('error', 'Something went wrong. Please try again later.');
        }
        $request->validate([
            'city' => 'required|string|max:255',
            'mile_radius' => 'required|numeric|min:0'
        ], [
            'city.required' => 'The city field is required.',
            'city.string' => 'The city must be a valid string.',
            'city.max' => 'The city must not exceed 255 characters.',
            'mile_radius.required' => 'The mile radius field is required.',
            'mile_radius.numeric' => 'The mile radius must be a number.',
            'mile_radius.min' => 'The mile radius must be at least 0.'
        ]);
        try{
            $this->savePreferenceData($request,$clinicianData,$clinicianID);
            return redirect()->route('nthlanding.results');
        } catch (\Throwable $th) {
            H::ExcelLog('updateClinicianPreferences Throwable='.$th->getMessage(),'updateClinicianPreferences.log');
            return redirect()->route('nthlanding.nthAvailableCases')
            ->withErrors(['form_error' => 'An unexpected error occurred. Please try again later.'])
            ->withInput();
        }
    }

    public function generateTinyURL(Request $request){
        $clinicianId = $request->clinicianId;
        echo H::getHelper("NTHLanding")->generateTinyURL($clinicianId);
        exit;
    }


    public function getResults(Request $request) {
        $data = $request->all();
        $clinicianID = Session::get('clinicianID');
        $candidateData = DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->first();
        if(empty($candidateData)) {
            return redirect()->route('nthlanding.nthErrorPage')->with('error', 'Something went wrong. Please try again later.');
        }
        if(!$request->ajax()){
            return view('nthlanding.results',["clinicianData"=>$candidateData]);
        } else {

            $limit = 6;
            $page_no = ($data['page'] * $limit) - $limit;
            $filters = isset($data['filters'])?$data['filters']:[];
            $orderBy = "asc";

            $recordsData = DB::table("NeedToHire_Accounts")
            ->select("NeedToHire_Accounts.id","NeedToHire_Accounts.pay_range_rn","NeedToHire_Accounts.pay_range_lvn","maps_nth.lat","maps_nth.lng","mssql_patients.Patient_City","mssql_patients.Patient_Zip",
            "mssql_facilities.State","mssql_patients.PetsinHome","mssql_patients.PreferenceList","mssql_patients.CF_LiftingRequired",
            "mssql_patients.CF_ClinicianDiscipline","mssql_patients.CF_Skills","mssql_patients.CF_NurseLanguage")
            ->leftJoin("mssql_patients","mssql_patients.id","=","NeedToHire_Accounts.aoc_patient_id")
            ->leftJoin("mssql_facilities","mssql_patients.aoc_facility_id","=","mssql_facilities.id")
            ->leftJoin("maps_nth","maps_nth.aoc_nthaccounts_id","=","NeedToHire_Accounts.id")
            ->where("NeedToHire_Accounts.IsActive","1")
            ->where(DB::raw('substr(mssql_patients.CF_MeetingNurses, 1, 2)'), '!=' , 'No')
            ->where(function($q){
                $q->where("NeedToHire_Accounts.clinicianDiscipline", "like", "%RN%")
                ->orWhere("NeedToHire_Accounts.clinicianDiscipline", "like", "%LVN%")
                ->orWhere("NeedToHire_Accounts.clinicianDiscipline", "like", "%LPN%");
            });


            if($candidateData){
                if(!empty($candidateData->aoc_cf_lifting_requirements)) {
                    $liftings = explode(",",$candidateData->aoc_cf_lifting_requirements);
                    $recordsData->where(function($q) use($liftings){
                        foreach($liftings as $lifting) {
                            $lift = '%'.$lifting.'%';
                            $q->orWhere("mssql_patients.CF_LiftingRequired","like",$lift);
                        }
                    });
                }
            }

            if($filters) {

                $city = $mileRadius = $clearAll ='';

                $schedule = $skills = $environment = $language = [];

                foreach($filters as $filter){
                    if($filter['name']=='orderby') {
                        if($filter['value']=='Farthest') {
                            $orderBy = "desc";
                        }
                    }

                    if($filter['name']=='clearall') {
                        $clearAll = $filter['value'];
                    }

                    if($filter['name']=='city') {
                        $city = $filter['value'];
                    }
                    if($filter['name']=='mile_radius') {
                        $mileRadius = $filter['value'];
                    }
                    if($filter['name']=='schedule') {
                        $schedule[] = $filter['value'];
                    }
                    if($filter['name']=='skills') {
                        $skills[] = $filter['value'];
                    }
                    if($filter['name']=='environment') {
                        $environment[] = $filter['value'];
                    }
                    if($filter['name']=='language') {
                        $language[] = $filter['value'];
                    }
                }

               if(empty($city) && empty($mileRadius) && empty($schedule)
                && empty($skills) && empty($environment)  && empty($language)) {
                    return response()->json(['clearall'=>true,'message'=>'Please apply filters to view the available cases.']);
                }

                if(!empty($city)) {
                    $location = $city.', USA';
                    $locationData = Geocoding::getGeoGoogle($location);

                    if (!empty($locationData)) {

                        $recordsData->selectRaw(sprintf("(3959 * acos(cos(radians('%f')) * cos(radians(lat)) * cos( radians(lng) - radians('%f')) + sin(radians('%f')) * sin(radians(lat)))) AS distance",$locationData['lat'],$locationData['lng'],$locationData['lat']));

                        if(!empty($mileRadius)) {
                            $recordsData->havingRaw(sprintf(' distance <= ' . ('%f+0.5'),$mileRadius));
                        }

                        $recordsData->orderBy("distance",$orderBy);

                    }
                }

                if(!empty($schedule)){
                    $recordsData->where(function ($q) use ($schedule) {
                        foreach ($schedule as $item) {
                            $q->orWhere('maps_nth.Type_of_Shift', 'like', '%'. $item . '%');
                        }
                    });
                }

                if(!empty($skills)){
                    $recordsData->whereIn("mssql_patients.CF_Skills",$skills);
                }

                if(!empty($environment)){

                    $environments = $environment;
                    $recordsData->where(function($q) use($environments){
                        foreach($environments as $environmentData) {
                            $env = '%'.$environmentData.'%';
                            $q->orWhere("mssql_patients.PetsinHome","like",$env);
                        }
                    });
                }

                if(!empty($language)){
                    $languages = $language;

                    $recordsData->where(function($q) use($languages){
                        $sepLanguage = [];
                        foreach($languages as $languageData) {
                            $languagesCommas = explode(",",$languageData);
                            foreach($languagesCommas as $languagesComma){
                                $languageSlashes = explode("/",$languagesComma);
                                foreach($languageSlashes as $languageSlash){
                                    $sepLanguage[] = $languageSlash;
                                }
                            }
                        }
                        if(!empty($sepLanguage)){
                            $sepLanguage = array_unique($sepLanguage);
                            $q->whereIn("mssql_patients.CF_NurseLanguage",$sepLanguage);
                        }
                    });
                }
            }

            $recordsData->offset($page_no)
            ->limit($limit+1);
            $records = $recordsData->get();

            $hasMore = $records->count() > $limit;
            $records = $records->take($limit);

            foreach($records as &$record) {
                $env = [];
                if(!empty($record->PetsinHome)) {
                    $env[] = $record->PetsinHome;
                }

                $env[] = strpos(strtolower($record->PreferenceList),"smokers live in home") !== false  ? 'Smoking' : 'Non-smoking';

                if ($record->CF_LiftingRequired != '' && substr_count(strtolower($record->CF_LiftingRequired), 'yes')) {
                    $env[]='Lifting Required';
                }else {
                    $env[]='No Lifting Required';
                }

                if(!empty($env)) {
                    $environment = implode(", ",$env);
                    $record->environment = $environment;
                }
                $record->displaySchedule = H::getHelper("NTHLanding")->getNTHShiftDescription($record->id);
                $inquiryData = DB::table('nth_inquiries')->where('ClinicianID', $clinicianID)
                ->where('aoc_nthaccounts_id',$record->id)
                ->where('not_interested',0)->first();
                $record->isInterested = !empty($inquiryData) ? 1 : 0;
                $record->isFlexibleSchedule = H::getHelper("NTHLanding")->isFlexibleSchedule($record->id);
                $finalLangStr = '';
                if (!empty($record->CF_NurseLanguage)) {
                    $languageList = array_map('trim', explode(',', $record->CF_NurseLanguage));
                    $formattedLanguages = array_map(fn($lang) => ucfirst(strtolower($lang)), $languageList);
                    $finalLangStr = implode(', ', $formattedLanguages);
                }
                $record->CF_NurseLanguage = $finalLangStr;
            }
            if($clearAll) {
                return response()->json(['clearall'=>true,'message'=>'Please apply filters to view the available cases.']);

            } else {

                return response()->json(['clearall'=>false,"filters"=>$this->appliedFilters($filters),"count"=>$hasMore,'records'=>$this->getBoxes($records)->render()]);

            }

        }

    }
    private function appliedFilters($filters){
        return '';
        //return view("nthlanding.appliedFilters",["filters"=>$filters]);
    }
    private function getBoxes($data) {
        return view("nthlanding.boxes",["records"=>$data]);
    }

    public function getNthGeneralInquiry(Request $request){
        $clinicianID = Session::get('clinicianID');
        $clinicianData = DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->first();
        return view('nthlanding.general_inquiry_form',["clinicianData"=>$clinicianData]);
    }

    public function saveNthGeneralInquiry(Request $request){
        try{
            $clinicianID = Session::get('clinicianID');
            $clinicianData = DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->first();
            if(empty($clinicianData)) {
                return response()->json(['status'=>false,'message'=>'Something went wrong. Please try again later.']);
            }
            $insertData['preferred_contact_method'] = implode(',', $request->contact_method ?? []);
            $insertData['best_time_to_contact'] = implode(',', $request->contact_time ?? []);
            $insertData['message'] = $request->message;
            $insertData['ClinicianID'] = $clinicianID;
            $insertData['aoc_provider_id'] = $clinicianData->id;
            $insertData['type'] = 'inq';
            DB::table('nth_inquiries')->insert($insertData);
            return response()->json(['status'=>true,'message'=>'Inquiry details sent successfully']);
        } catch (\Exception $e) {
            H::ExcelLog('saveNthGeneralInquiry: '.$e->getMessage(),'saveNthGeneralInquiry.log');
            return response()->json(['status'=>false,'message'=>'Something went wrong. Please try again later.']);
        }

    }

    public function saveNthCaseInterest(Request $request){

        $result = $this->service->saveNthCaseInterest($request->aoc_nthaccounts_id);

        if ($result['status']) {
            return response()->json(['status' => true, 'action' => $result['action']]);
        } else {
            return response()->json(['status' => false, 'message' => 'Something went wrong. Please try again later.']);
        }

    }
}
