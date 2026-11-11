<?php

namespace App\SyncProcesses;

use App\Helpers\Geocoding;
use App\Helpers\H;
use App\Helpers\Notifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MssqlNthMap
{
    public function syncNthData()
    {
        //this function syncs changes to NTH during the day
        $this->processNthData('NTH');
    }

    public function syncMorningNthData()
    {
        //this function syncs all NTH every morning
        $this->processNthData('MorningNTH');
    }

    public function getShiftDescription($acctId)
    {
        $days = ['', 'S', 'M', 'T', 'W', 'Th', 'F', 'Sa'];
        $qry = "SELECT NeedToHireAccountsID, StartDayOfWeek,StartDayOfWeekInt, StartHour, 
						StartMinute, EndHour, EndMinute, StartAndEndOnSameDay 
				FROM `nthSchedules` 
				where NeedToHireAccountsID = $acctId and IsActive = 1 
				order by NeedToHireAccountsID, StartHour, StartDayOfWeekInt";
        $dbShifts = DB::select($qry);
        $shifts = [];
        $scheduleDetails = '';
        foreach ($dbShifts as $shift) {
            var_dump($shift);
            echo "\n";
            $startAmPm = 'a';
            $startHour = $shift->StartHour;
            if ($startHour >= 12) {
                $startAmPm = 'p';
            }
            if ($startHour > 12) {
                $startHour = $startHour - 12;
            }
            $endHour = $shift->EndHour;
            $endAmPm = 'a';
            if ($endHour >= 12) {
                $endAmPm = 'p';
            }
            if ($endHour > 12) {
                $endHour = $endHour - 12;
            }
            $mytime = $startHour;
            if ($shift->StartMinute != 0) {
                $tmp = '0' . $shift->StartMinute;
                echo '-------------------- ' . $tmp . "\n";
                $tmp = substr($tmp, -2);
                echo '-------------------- ' . $tmp . "\n";

                $mytime .= $tmp;
            }
            $mytime .= $startAmPm . '-';
            $mytime .= $endHour;
            if ($shift->EndMinute != 0) {
                $mytime .= substr('0' . $shift->EndMinute, -2);
            }
            $mytime .= $endAmPm;
            $tmp = '';
            if (isset($shifts[$mytime])) {
                $tmp = $shifts[$mytime] . '&';
            }
            $tmp .= $days[$shift->StartDayOfWeekInt];
            $shifts[$mytime] = $tmp;
        }
        var_dump($shifts);
        $shiftDesc = '';
        foreach ($shifts as $key => $value) {
            if ($shiftDesc != '') {
                $shiftDesc .= ', ';
            }
            $tmp = $value;
            if (substr($tmp, 0, 2) == 'S&') {
                $tmp = substr($tmp, 2) . '&S';
            }
            echo $tmp . "\n";
            if ($tmp == 'M&T&W&Th&F&Sa&S') {
                $tmp = 'M-S';
            }
            if ($tmp == 'M&T&W&Th&F&Sa') {
                $tmp = 'M-Sa';
            }
            if ($tmp == 'M&T&W&Th&F') {
                $tmp = 'M-F';
            }
            if ($tmp == 'M&T&W&Th') {
                $tmp = 'M-Th';
            }
            if ($tmp == 'M&T&W') {
                $tmp = 'M-W';
            }
            if ($tmp == 'M&T') {
                $tmp = 'M-T';
            }
            if ($tmp == 'T&W&Th&F&Sa&S') {
                $tmp = 'T-S';
            }
            if ($tmp == 'T&W&Th&F&Sa') {
                $tmp = 'T-Sa';
            }
            if ($tmp == 'T&W&Th&F') {
                $tmp = 'T-F';
            }
            if ($tmp == 'T&W&Th') {
                $tmp = 'T-Th';
            }
            if ($tmp == 'T&W') {
                $tmp = 'T-W';
            }
            if ($tmp == 'W&Th&F&Sa&S') {
                $tmp = 'W-S';
            }
            if ($tmp == 'W&Th&F&Sa') {
                $tmp = 'W-Sa';
            }
            if ($tmp == 'W&Th&F') {
                $tmp = 'W-F';
            }
            if ($tmp == 'W&Th') {
                $tmp = 'W-Th';
            }
            if ($tmp == 'Th&F&Sa&S') {
                $tmp = 'Th-S';
            }
            if ($tmp == 'Th&F&Sa') {
                $tmp = 'Th-Sa';
            }
            if ($tmp == 'Th&F') {
                $tmp = 'Th-F';
            }
            if ($tmp == 'F&Sa&S') {
                $tmp = 'F-S';
            }
            if ($tmp == 'F&Sa') {
                $tmp = 'F-Sa';
            }
            if ($tmp == 'Sa&S') {
                $tmp = 'Sa-S';
            }
            $shiftDesc .= $tmp . ' ' . $key;
        }
        echo $shiftDesc;
        echo "\n\n\n\n\n\n\n";

        return $shiftDesc;
    }

    public function processNthData($syncType, $account = '')
    {
        echo 'Sync NTH Data';
        $tableName = 'maps_nth';
        //start batch
        echo '******  ' . $syncType . ' - ' . $account . "\n";
        $last_updated_date = '';
        $last_updated_id = '';
        $limit = 10;
        $tsql = '';
        if ($account == '') {
            $batch_status = H::getHelper('Sync')->startBatch($syncType);
            echo $batch_status . "\n";
            if ($batch_status == -1) {
                echo 'stop process';
                exit;
            }
            $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
            $batch_result = 0;
            var_dump($batch_info);
            echo "\n";

            $last_updated_date = $batch_info['last_updated_date'];
            $last_updated_id = $batch_info['last_updated_id'];
            $limit = $batch_info['batch_size'];
            if ($syncType == 'MorningNTH' && $last_updated_id == 'donefortheday') {
                echo 'Skipping because we are done for the day';
                $batch_status = H::getHelper('Sync')->stopBatch($syncType);
                exit;
            }
        }
        $conn = H::getHelper('Sync')->OpenConnection();
        if ($account == '') {
            if ($syncType == 'NTH') {
                $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
                $tsql = 'SELECT top ' . $limit . " * FROM Excelss.MAP_NeedToHireExtract 
						WHERE (cast(CONVERT(char(23), ModifiedOn,121) as varchar(23)) + convert(varchar(20),NeedToHireAccountsID))  > '$combinedString'
						ORDER BY ModifiedOn ASC, NeedToHireAccountsID ASC";
            } else {
                $tsql = 'SELECT top ' . $limit . " * FROM Excelss.MAP_NeedToHireExtract 
						WHERE NeedToHireAccountsID  > $last_updated_id
						ORDER BY NeedToHireAccountsID ASC";
            }
        }
        if ($account != '') {
            $tsql = 'select * from Excelss.MAP_NeedToHireExtract where NeedToHireAccountsID = ' . $account;
        }
        echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);

        if ($getRecords == false) {
            echo 'error';
            die();
        }
        $recordCount = 0;
        while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
            $recordCount++;
            var_dump($row);
            if($row['MeetingNursesBit']==1){
                H::ExcelLog($row['Client'] . ' - ' . $row['NeedToHireAccountsID'], 'nth-' . date('Y-m-d') . '.log');
                echo "\n";
                if ($syncType == 'NTH') {
                    $last_updated_date = $row['ModifiedOn'];
                } else {
                    $last_updated_date = '';
                }
                $last_updated_id = $row['NeedToHireAccountsID'];
                echo $last_updated_date . ' - ' . $last_updated_id . "\n";
                $uniqueID = $row['NeedToHireAccountsID'];
                $shiftDesc = $this->getShiftDescription($uniqueID);
                echo $shiftDesc . "\n";

                $newRow = [];

                foreach ($row as $key => &$value) {
                    if (is_null($value)) {
                        $value = '';
                    }
                    if ($key == 'Specializedclient' || $key == 'AttendingSchool' || $key == 'Covid19VaccineRequired' || $key == 'MediaConsent'
                        || $key == 'NonSmokingNurses' || $key == 'SmokersLiveinHome') {
                        if (trim($value) != '1') {
                            $value = 'No';
                        } else {
                            $value = 'Yes';
                        }
                    }
                    if ($key == 'NTHStatus') {
                        $key = 'NTH_Status';
                    }
                    if ($key == 'ScheduleText') {
                        $key = 'Type_of_Shift';
                    }
                    if ($key == 'ScheduleFreeText') {
                        $key = 'Schedule_Details';
                    }
                    if ($key == 'ScheduleDescription') {
                        $key = 'Schedule';
                    }
                    if ($key == 'hoursonNTH') {
                        $key = 'NTH_Hours';
                    }
                    if ($key == 'TotalauthorizedPDNhours') {
                        $key = 'PDN_Auth_Hrs';
                    }
                    if ($key == 'TotalauthorizedRespitehours') {
                        $key = 'Respite_Auth_Hrs';
                    }
                    if ($key == 'TotalauthorizedCNAhours') {
                        $key = 'CNA_Auth_Hrs';
                    }
                    if ($key == 'TheSale') {
                        $key = 'The_Sale';
                    }
                    if ($key == 'AttendingSchool') {
                        $key = 'Attending_School';
                    }
                    if ($key == 'NurseLanguage') {
                        $key = 'Nurse_Language';
                    }
                    if ($key == 'PetsinHome') {
                        $key = 'Pets_in_Home';
                    }
                    if ($key == 'PetsNurseCannotOwn') {
                        $key = 'Pets_Nurse_Cannot_Own';
                    }
                    if ($key == 'Specializedclient') {
                        $key = 'Specialized';
                    }
                    if ($key == 'Serviceneeded') {
                        $key = 'Service_Needed';
                    }
                    if ($key == 'ClinicianGender') {
                        $key = 'Clinician_Gender';
                    }
                    if ($key == 'Cliniciandiscipline') {
                        $key = 'Clinician_Discipline';
                    }
                    if ($key == 'RespitePayer') {
                        $key = 'Respite_Payer';
                    }
                    if ($key == 'IsIntake') {
                        $key = 'Intake';
                    }
                    if ($key == 'IntakeStatus') {
                        $key = 'Intake_Status';
                    }
                    if ($key == 'IntakeDate') {
                        $key = 'Intake_Date';
                    }
                    if ($key == 'SOCDate') {
                        $key = 'SOC_Date';
                    }
                    if ($key == 'MeetingNurses') {
                        $key = 'Meeting_Nurses';
                    }
                    if ($key == 'WorkForceDevProj') {
                        $key = 'WFD_Client';
                    }
                    if ($key == 'NTHNotes') {
                        $key = 'Notes';
                    }
                    if ($key == 'Covid19VaccineRequired') {
                        $key = 'Covid_Vaccine_Required';
                    }
                    if ($key == 'NonSmokingNurses') {
                        $key = 'NonSmoking_Nurses';
                    }
                    if ($key == 'SmokersLiveinHome') {
                        $key = 'Smokers_Live_in_Home';
                    }
                    if ($key == 'ClientCareCoordinator') {
                        $key = 'Client_Care_Coordinator';
                    }
                    if ($key == 'DaysSinceCreated') {
                        $key = 'Days_on_NTH';
                    }
                    if ($key == 'Schedule') {
                        $key = 'NTH_Schedule';
                    }
                    if ($key == 'ReferralDate') {
                        $key = 'Referral_Date';
                    }
                    if ($key == 'AssessmentCaseManager') {
                        $key = 'Assessment_Case_Manager';
                    }
                    if ($key == 'AuthorizationCaseManager') {
                        $key = 'Auth_Case_Manager';
                    }
                    if ($key == 'IntakeCoordinator') {
                        $key = 'Intake_Coordinator';
                    }
                    if ($key == 'BusinessDeveloper') {
                        $key = 'BD_Rep';
                    }
                    if ($key == 'Patient_DOB') {
                        $key = 'Client_DOB';
                    }
                    if ($key == 'IsPriority') {
                        $key = 'Priority';
                    }
                    $newKey = str_replace(' ', '_', $key);
                    $newKey = str_replace('%', '', $key);
                    if (Schema::hasColumn($tableName, $newKey)) {
                        $newRow[$newKey] = $value;
                    }
                }
                $newRow['Schedule'] = $shiftDesc;
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                //first find address or geocode it
                if (trim($row['Zipcode'])!='' || (trim($row['City']) != '' && trim($row['State']) != '')) {
                    $address = [];
                    $address['address1'] = $row['Address'];
                    $address['city'] = $row['City'];
                    $address['state'] = $row['State'];
                    $address['country'] = 'USA';
                    $address['zipcode'] = $row['Zipcode'];
                    $mode = 'api';
                    $geocoding = Geocoding::processAddress($address, $mode);
                    var_dump($geocoding);
                    echo "\n";
                    if (isset($geocoding['lat']) && isset($geocoding['lng']) && $geocoding['lat'] && $geocoding['lng']) {
                        $newRow['lat'] = $geocoding['lat'];
                        $newRow['lng'] = $geocoding['lng'];
                    }
                }
                $dbClient = DB::select("select * from maps_nth where `NeedToHireAccountsID` = '" . $uniqueID . "'");
                if (!empty($dbClient)) {
                    echo 'Update Client' . "\n";
                    DB::table('maps_nth')->whereRaw('`NeedToHireAccountsID`= ?', [$uniqueID])->update($newRow);
                } else {
                    echo 'Insert Client' . "\n";
                    DB::table('maps_nth')->insert($newRow);
                    $data = [];
                    $data['user_id'] = 1;
                    $data['title'] = $row['PatientName'] . ' was added to NTH';
                    $data['type'] = 'NTH';
                    $data['related_item'] = 0;
                    $data['notes'] = '';
                    Notifications::addNotification($data);
                }
            }
            if ($account == '') {
                H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
            }
        }
        sqlsrv_free_stmt($getRecords);
        sqlsrv_close($conn);
        if ($account == '') {
            //if no records were found,   delete all rows with 'is_updated = 0' and stop from processing for rest of day
            if ($syncType == 'MorningNTH' && $recordCount == 0) {
                H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, 'donefortheday');
                echo 'deleting records not updated' . "\n";
                $deleted = DB::table($tableName)->where('is_updated', 0)->delete();
            }
        }
        //******************************************************************************************* */
        echo "sync finished \n";
        if ($account == '') {
            $batch_status = H::getHelper('Sync')->stopBatch($syncType);
        }
    }
}
