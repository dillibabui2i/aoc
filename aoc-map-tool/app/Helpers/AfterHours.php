<?php
namespace App\Helpers;

use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AfterHours
{
    public function getDateForSpecificDayBetweenDates($startDate, $endDate, $day_number)
    {
        $date_array =[];
        $endDate = strtotime($endDate);
        $days = array('1' => 'Monday', '2' => 'Tuesday', '3' => 'Wednesday', '4' => 'Thursday', '5' => 'Friday', '6' => 'Saturday', '7' => 'Sunday');
        for ($i = strtotime($days[$day_number], strtotime($startDate)); $i <= $endDate; $i = strtotime('+1 week', $i)) {
            $date_array[] = date('Y-m-d', $i);
        }
        return $date_array;
    }
    public function getWeekDateRange($startDate, $endDate)
    {
        $insertFinalDateArray = [];
        $endDate   = date('Y-m-d', strtotime('Sunday next week', strtotime($startDate)));
        $endDateMonth   = strtotime($endDate);
        $i = 0;
        while (true) {
            if ($i == 0) {
                $sStartDate = date('Y-m-d', strtotime($startDate));
                $sEndDate   = date('Y-m-d', strtotime('+1 days', strtotime($sStartDate)));
            }
            $insertFinalDateArray[] =  $sStartDate . '##' . $sEndDate;
            $sEndDateMonth   = strtotime($sEndDate);
            H::ExcelLog('===sEndDateMonth-' . $sEndDateMonth, 'getWeekDateRange.log');
            if ($sEndDateMonth > $endDateMonth) {
                break;
            }
            $sStartDate   = date('Y-m-d', strtotime('+0 days', strtotime($sEndDate)));
            $sEndDate   = date('Y-m-d', strtotime('+1 days', strtotime($sStartDate)));

            $i++;
        }
        return $insertFinalDateArray;
    }

    // Function to get all the dates in given range
    public function getDatesFromRange($start, $end, $format = 'Y-m-d') {
        // Declare an empty array
        $array = array();
        // Variable that store the date interval of period 1 day
        $interval = new \DateInterval('P1D');
        $realEnd = new \DateTime($end);
        $realEnd->add($interval);
        $period = new \DatePeriod(new \DateTime($start), $interval, $realEnd);
        // Use loop to store date into array
        foreach($period as $date) {
            $array[] = $date->format($format);
        }
        // Return the array elements
        return $array;
    }

    public function getAhJournalNotesDataTableHtml($type)
    {
        $dataTable = [];
        if($type == 'ah_journal_notes') {
            $dataTable = [
                'patient_name' => 'Client',
                'added_date' => 'Date Added',
            ];
            if (LD::checkFeatureFlag('add-category-field-and-filter-in-ahJournalNotes-bucket')) {
				$dataTable['category'] = 'Category';
			}
            $dataTable['notes'] ='Note';
			if (LD::checkFeatureFlag('add-added-by-column-ah-journal-notes-bucket')) {
				$dataTable['addedby'] = 'Added By';
			}
            return view(
                'afterhours.ahJournal_datatable',
                [
                    "type" => $type,
                    'dataTable' => $dataTable
                ]
            );
        }
    }

    public function getAhJournalData($request, $showCount=0) {
        $newfiled = 'mssql_patients.FacilityID';
        if(LD::checkFeatureFlag('fix-after-hour-filter-issue')){
            $newfiled = 'mssql_patients.aoc_facility_id';
        }
        $type = $request['type'];
        if($type == 'ah_journal_notes') {
            $bucket = DB::table('journal_notes')->select(
                'journal_notes.id',
                'mssql_patients.id as aoc_patient_id',
                DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',
                IF(mssql_patients.Patient_MiddleName != '',CONCAT(mssql_patients.Patient_MiddleName, ' '),''),
                mssql_patients.Patient_LastName) AS patient_name"),
                'journal_notes.added_date',
                'journal_notes.notes',
                DB::raw("ifnull(mssql_providers.fullName,'') as addedby"),
                'journal_notes.category',
            )
            ->leftJoin('mssql_patients', 'journal_notes.PatientID', '=', 'mssql_patients.PatientID')
            ->leftJoin('mssql_providers', 'journal_notes.ProviderID', '=', 'mssql_providers.ProviderID')
            ->leftJoin('mssql_facilities', "$newfiled", '=', 'mssql_facilities.FacilityID');
            $bucket->whereRaw("journal_notes.category like 'After%'");
            $bucket->where('journal_notes.context_type','=','ClientProfile');
            $bucket->whereNull("journal_notes.note_cleared_date");
            $bucket->where(function($q) {
                $q->where('journal_notes.is_deleted', 0)
                  ->orWhereNull('journal_notes.is_deleted');
            });
            $bucket->whereDate("journal_notes.added_date", '>=', '2024-06-10'); // AOC requested to get data from this date

            $branch = [];
            $state = [];
            $users = [];
            $payer = [];
            $payerGroup=[];
            $region = [];
            $category = [];
            $isMeMode = 0;
            if(isset($request['filters'])) {
                foreach($request['filters'] as $value){
                    if($value['name']=='ah_journal_notes_state') {
                        $state[] = $value['value'];
                    }
                    if($value['name']=='ah_journal_notes_branch') {
                        $branch[] = $value['value'];
                    }
                    if($value['name']=='ah_journal_notes_clientcare'){
                        $users[] = $value['value'];
                    }
                    if( $value['name']=='ah_journal_notes_payer'){
                        $payer[] = $value['value'];
                    }
                    if($value['name']=='ah_journal_notes_region'){
                        $region[] = $value['value'];
                    }
                    if( $value['name']=='ah_journal_notes_payer_group'){
                        $payerGroup[] = $value['value'];
                    }
                    if($value['name']=='ah_journal_notes_memode') {
                        $isMeMode = 1;
                    }
                    if(LD::checkFeatureFlag('add-category-field-and-filter-in-ahJournalNotes-bucket')){
                        if($value['name']=='category') {
                            $category[] = $value['value'];
                        }
                    }
                }
            }

            if(!empty($state)) {
                $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
                $bucket->where(function($q) use ($facilities){
                    foreach($facilities as $facility) {
                        $q->orWhere("mssql_patients.aoc_facility_id","=",$facility->id);
                    }
                });
            }
            if(!empty($branch)) {
                $bucket->where(function($q) use ($branch){
                    foreach($branch as $b) {
                        $q->orWhere("mssql_patients.aoc_facility_id","=",$b);
                    }
                });
            }
            if(!empty($region)){
                $bucket->where(function($query) use ($region) {
                    foreach($region as $val) {
                        $query->orWhere("mssql_facilities.region",'=',$val);
                    }
                });
            }
            if(!empty($users)){
                $bucket->whereIn("mssql_patients.ccc_id",$users);
            } elseif($isMeMode) {
                $bucket->where("mssql_patients.ccc_id","=",Auth::user()->id);
            }
            if(!empty($payer)){
                $bucket->where(function($q) use ($payer){
                    foreach($payer as $t) {
                        $q->orWhere("mssql_patients.Payer","=",$t);
                    }
                });
            }
            if(!empty($payerGroup)){
                $bucket->where(function($q) use ($payerGroup){
                    foreach($payerGroup as $t) {
                        $q->orWhere("mssql_patients.payer_group","=",$t);
                    }
                });
            }
            if(LD::checkFeatureFlag('add-category-field-and-filter-in-ahJournalNotes-bucket')){
                if(!empty($category)) {
                    $bucket->where(function($q) use ($category){
                        foreach($category as $t) {
                            $q->orWhere("journal_notes.category","=",$t);
                        }
                    });
                }
            }
        }

        if($showCount) {
            return '('.$bucket->count().')';
        }
        return $bucket->get();
    }
}
