<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DaasScheduleMaster
{
    /**
     * Sync schedule master table
     */
    public function syncScheduleMaster($mode = 'schedules')
    {
        echo "Sync Schedule Master List \n";
        $syncType = 'daas_schedules';
		$logFileName = 'daasScheduleSync.log';
        if($mode=='schedulescu'){
            echo "in catch up mode" . "\n";
            $syncType = 'daas_schedules_cu';
			$logFileName = 'daasScheduleCUSync.log';
        }
		if($mode=='schedulemidnightcu'){
            echo "in midnight catch up mode" . "\n";
            $syncType = 'daas_schedules_midnight_cu';
			$logFileName = 'daasScheduleMidnightCUSync.log';
			$time = Carbon::now();
			$midnight30 = Carbon::create($time->year, $time->month, $time->day, 0, 30, 0);
			$midnight35 = Carbon::create($time->year, $time->month, $time->day, 0, 35, 0);
			if($time->between($midnight30, $midnight35, true)) {
				$previousDay = Carbon::now()->subDays(1)->format('Y-m-d');
				H::getHelper('Sync')->update_last_updated($syncType, $previousDay . " 00:00:00.001", 'a000');
			}
        }
        $tableName = 'mssql_schedulemaster';
        $fieldName = 'CGTASKID';

        //start batch
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
        if($last_updated_id=='') {
            $last_updated_id = 0;
        }

        //get facility list
        $facilities = $this->getFacilityList();

        echo 'Schedule Master last_updated_id: ' . $last_updated_id . "\n";
        $limit = $batch_info['batch_size'];
        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        if($syncType == 'daas_schedules_cu') {
            $combinedString2 = '';
        }else{
            $combinedString2 = $combinedString;
        }
		$query = "select count(*) as RecordCount from SCHEDULEMASTER_SVW
                    WHERE concat(cast(ETL_UPDATED_TIMESTAMP as varchar(23)),
                        cast(SCHEDULE_ID as varchar(20))) > '" . $combinedString2 . "'";
		if($syncType == 'daas_schedules_cu') {
			$query .= " and SCHEDULE_DATE > '2021-12-31' and SCHEDULE_DATE < current_date() ";
		}
        $rawData2 = DB::connection('daas')->select(DB::raw($query));
        $data2 = json_decode(json_encode($rawData2), true);
        H::ExcelLog("Count for CombinedString > '" . $combinedString2 . "' : " . $data2[0]['RECORDCOUNT'],$logFileName);
		$query = "select top  " . $limit . " cast(ETL_UPDATED_TIMESTAMP as varchar(23)) as LASTMODIFIED
                    ,concat(cast(ETL_UPDATED_TIMESTAMP as varchar(23)),
                        cast(SCHEDULE_ID as varchar(20))) as CombinedString
                    ,ETL_UPDATED_TIMESTAMP
                    ,AGENCY_ID AS HHA
                    ,S_SCEDULE_CREATOR_ID AS CreatedBy
                    ,S_HHA_BRANCH_ID AS HHABranchID
                    ,SCHEDULE_ID AS CGTaskID
                    ,S_CLIENT_ID AS ClientID
                    ,S_CAREGIVER_ID AS Caregiver
                    ,S_PAYER_SOURCE_ID AS PayerID
                    ,S_SERVICE_CODE_ID AS ServiceCodeID
                    ,cast(S_PLANNED_START as varchar(23)) AS PlannedStart
                    ,cast(S_PLANNED_END as varchar(23)) AS PlannedEnd
                    ,cast(S_ACTUAL_START as varchar(23)) AS ActualStart
                    ,cast(S_ACTUAL_END as varchar(23)) AS ActualEnd
                    ,round(S_PLANNED_HOURS,2) AS PlannedHours
                    ,round(S_ACTUAL_HOURS,2) AS ActualHours
                    ,round(S_HOURS,2) AS Hours
                    ,cast(S_SCHEDULE_STATUS as varchar(25)) as ScheduleStatus
                    ,cast(S_ECHART_STATUS as varchar(100)) AS EChartStatus
                    ,S_MILES AS Miles
                    ,S_IS_AUTH_REQUIRED AS IsAuthRequired
                    ,cast(S_AUTHORIZATIONS as varchar(100)) AS Authorizations
                    ,cast(S_PAYBLE_HOURS as varchar(100)) AS PaybleHours
                    ,cast(REASON_FOR_MISSED_VISIT as varchar(100)) AS MissedVisitReason
                    ,cast(S_WEEKSTART as varchar(100)) AS WeekStart
                    ,EPISODE_ID AS EpisodeID
                    ,S_IS_PRN_VISIT AS IsPRNVisit
                    ,cast(S_REVENUE_CODE as varchar(100)) AS RevenueCode
                    ,cast(S_GCODE as varchar(100)) AS GCode
                    ,cast(MODIFIER_1 as varchar(100)) AS Modifier1
                    ,cast(MODIFIER_2 as varchar(100)) AS Modifier2
                    ,cast(MODIFIER_3 as varchar(100)) AS Modifier3
                    //,cast(MODIFIER_4 as varchar(100)) AS Modifier4
                    ,cast(S_UNITS as varchar(100)) AS Units
                    ,S_HAS_CHILD AS HasChild
                    ,IS_CHILD_VISIT AS IsChild
                    ,PARENT_SCHEDULE_ID AS ParentCgTaskID
                    ,IS_SCHEDULE_BILLABLE AS IsBillable
                    ,IS_SCHEDULE_PAYABLE AS IsPayable
                    ,IS_SCHEDULE_BILLED AS IsBilled
                    ,IS_SCHEDULE_PAID AS IsPaid
                    ,S_BILL_RATE AS BillRate
                    ,S_PAY_RATE AS PayRate
                    ,cast(S_BILL_MODE as varchar(100)) AS BillMode
                    ,cast(S_PAY_MODE as varchar(100)) AS PayMode
                    ,cast(S_PAY_MODE as varchar(100)) AS PayMode
                    ,S_BILLED_AMOUNT AS BilledAmount
                    ,S_PAYROLLED_AMOUNT AS PayrolledAmount
                    ,S_REGULARAMOUNT AS RegularAmount
                    ,S_OT_AMOUNT AS OTAmount
                    ,S_BONUS_AMOUNT AS BonusAmount
                    ,S_BASE_RATE AS BaseRate
                    ,cast(REASON_FOR_CANELLATION as varchar(100)) AS CancellationReason
                    ,S_CONTRACTUAL_RATE AS ContractualRate
                    ,cast(S_CONTRACTUAL_MODE as varchar(100)) AS ContractualMode
                    ,S_CONTRACTUAL_AMOUNT AS ContractualAmount
                    ,cast(NON_BILLABLE_REASON as varchar(100)) AS NonBillableReason
                    ,round(S_REGULAR_HOURS,2) AS RegularHours
                    ,round(S_OT_HOURS,2) AS OTHours
                    ,IS_MISCVISIT AS isMiscVisit
                    ,S_COST AS Cost
                    ,S_REVENUE AS Revenue
                    ,S_CONTRACTUAL_REVENUE AS ContractualRevenue
                    ,cast(SCHEDULE_DATE as varchar(23)) AS ScheduleDate
                    ,S_PROFIT AS Profit
                    ,S_PROFIT_CONTRACTUAL AS ProfitContractual
                    ,S_MARGIN AS Margin
                    ,S_MARGIN_CONTRACTUAL AS MarginContractual
                    ,S_BILLED_HOURS AS BilledHours
                    ,S_SCHEDULE_PRODUCTIVITY_POINTS AS ScheduleProductivityPoints
                    ,S_PAYROLL_WEEK_STARTDATE AS PayrollWeekStartDate
                    ,S_WEEK_NO AS WeekNo
                    ,S_CLINICIAN_PRODUCTIVITY_PER_WEEK AS ClinicianProductivityPerWeek
                    ,S_AUTHORIZATION_ID AS AuthorizationID
                    ,MISC_VISIT_ID AS ScheduleMiscVisitID
                    ,cast(SUBMITTEDON_DATE as varchar(23)) AS SubmittedOn
                    ,cast(APPROVED_DATE as varchar(23)) AS ApprovedOn
                    ,cast(SCHEDULE_INVOICEEDDATE as varchar(23)) AS InvoicedOn
                    ,cast(S_PAYMENT_RECIEVED_ON as varchar(23)) AS PaymentRecievedOn
                    ,S_HOLIDAY_HOURS AS HolidayHours
                    ,S_HOLIDAY_PAYROLL_AMOUNT AS HolidayPayrollAmount
                    ,S_HOLIDAY_PAYROLL_RATE AS HolidayPayrollRate
                    ,S_OT_PAYROLL_RATE AS OTPayrollRate
                    ,cast(SCHEDULE_ADDED_ON as varchar(23)) AS AddedOn
                    ,cast(SECONDARY_PAYERSOURCE as varchar(250)) AS SecondaryPayerSourceNames
                    ,cast(TERTIARY_PAYERSOURCE as varchar(250)) AS TertiaryPayerSourceNames
                    ,cast(left(S_MISC_VISITREASON,250) as varchar(250)) AS MiscVisitReason
                    ,S_IS_EVV_SCHEDULE AS IsEvvSchedule
                    ,cast(S_ECC_AGGREGATORSTATUS as varchar(100)) AS EVVAggregatorStatus
                    ,SCHEDULE_UNIQUE_IDENTIFIER
					,cast(ISVALIDRECORD as varchar(10)) as ISVALIDRECORD
                    from SCHEDULEMASTER_SVW
                    WHERE SCHEDULE_DATE > '2021-12-31' and CombinedString > '" . $combinedString . "'";

        if($syncType == 'daas_schedules_cu') {
            $query .= " and SCHEDULE_DATE > '2021-12-31' and SCHEDULE_DATE < current_date() ";
        }
        $query .= " order by CombinedString";
        echo $query . "\n";
        H::ExcelLog("SELECT .... where SCHEDULE_DATE > '2021-12-31' and CombinedString > '" . $combinedString . "'",$logFileName);
        $rawData = DB::connection('daas')->select(DB::raw($query));
		//$data = json_decode(json_encode($rawData), true);
		$data =(array) $rawData;
		$data = array_map([self::class, 'ensureUtf8Encoding'], $data);

        //now start regular sync process
        $recordCount = 0;
        $updatedCount = 0;
        $insertedCount = 0;
        $deletedCount = 0;

        foreach($data as $row) {
            $recordCount++;
            echo $row['COMBINEDSTRING'] . ' - ' . $row['LASTMODIFIED'];
            echo "\n";
            echo $row['LASTMODIFIED'] . "\n";
            $newRow = [];
            $uniqueID = $row[$fieldName];
            echo 'unique: ' . $uniqueID . "\n";
            $last_updated_date = $row['LASTMODIFIED'];
            $last_updated_id = $uniqueID;
            echo $row['SCHEDULEDATE'] . ' - last updated: ' . $last_updated_date . ' - ' . $last_updated_id . "\n";
            $dbRecord = DB::select("select * from $tableName where `$fieldName` = " . $uniqueID);
            var_dump($dbRecord);
            echo "\n";
            $newRow = $this->setNewRow($tableName, $row, $newRow, $facilities);

            H::ExcelLog('Processing: ' . $row['CGTASKID'] . ' (' . $row['LASTMODIFIED'] . ')',$logFileName);
			if (isset($row['ISVALIDRECORD']) && $row['ISVALIDRECORD'] == 'false') {
                H::ExcelLog('Delete Record: ' . $uniqueID . ' (' . $row['LASTMODIFIED'] . ')',$logFileName);
                $deletedCount++;
				echo 'Deleted Record ' . $uniqueID . "\n";
				DB::table($tableName)->where($fieldName, $uniqueID)->delete();
                //$newRow['is_deleted'] = '1';
                //$newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                //DB::table($tableName)->whereRaw("`$fieldName`= ?", [$uniqueID])->update($newRow);
                //H::ExcelLog(print_r($newRow,true),'daasScheduleSyncDeleted.log');
			} elseif (!empty($dbRecord)) {
                echo 'Update Record in sm' . "\n";

				if($dbRecord[0]->is_deleted == 1) {
					echo 'skip updating record as record is already set as deleted' . "\n";
					H::ExcelLog('skip updating record as record is already set as deleted',$logFileName);
					continue;
				}
                $updatedCount++;
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                $tmpETL = $dbRecord[0]->Modifier4;
                if($tmpETL!='') $tmpETL .= ' , ';
                $newRow['Modifier4'] = $tmpETL . $row['ETL_UPDATED_TIMESTAMP'];
				$newRow['etl_updated_timestamp'] = $row['LASTMODIFIED'];

				//update is_no_provider value
				if(!empty($newRow['ProviderID']) && $dbRecord[0]->is_no_provider == 2 && $newRow['ScheduleStatusID'] == 5){
					$newRow['is_no_provider'] = 1;
					H::ExcelLog('Set is_no_provider value to 1',$logFileName);
				}
				if($newRow['ScheduleStatusID'] != 5){
					$newRow['is_no_provider'] = 0;
					H::ExcelLog('Set is_no_provider value to 0',$logFileName);
				}
				//update is_no_provider value code ends
                DB::table($tableName)->whereRaw("`{$fieldName}`= ?", [$uniqueID])->update($newRow);
                H::ExcelLog('Updated: ' . $uniqueID . ' (' . $row['LASTMODIFIED'] . ')',$logFileName);
            } else {
                echo 'Insert Record in sm' . "\n";
                $insertedCount++;
                $newRow['is_updated'] = '1';
                $newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                $newRow['checked_off_at'] = date_format(date_create(), 'Y-m-d H:i:s');
				$newRow['etl_updated_timestamp'] = $row['LASTMODIFIED'];
                DB::table($tableName)->insert($newRow);
                H::ExcelLog('Inserted: ' . $uniqueID . ' (' . $row['LASTMODIFIED'] . ')',$logFileName);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        //Update FacilityID
        H::ExcelLog("FacilityID Updated",$logFileName);
        /*
        DB::table('mssql_schedulemaster')
        ->join('mssql_facilities','mssql_facilities.BillingFacilityID','=','mssql_schedulemaster.BillingFacilityID')
        ->whereRaw("ifnull(mssql_schedulemaster.FacilityID,'')=''")
        ->update(['mssql_schedulemaster.FacilityID' => \DB::raw('mssql_facilities.FacilityID')]);
        */
        echo "Schedule Master sync finished \n";
        echo "Records Processed: " . $recordCount . "\n";
        echo "Records Updated: " . $updatedCount . "\n";
        echo "Records Inserted: " . $insertedCount . "\n";
        echo "Records Deleted: " . $deletedCount . "\n";
        H::ExcelLog("Records Processed: " . $recordCount ,$logFileName);
        H::ExcelLog("Records Updated: " . $updatedCount,$logFileName);
        H::ExcelLog("Records Inserted: " . $insertedCount,$logFileName);
        H::ExcelLog("Records Deleted: " . $deletedCount,$logFileName);
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    private function setNewRow($tableName, $row, $newRow, $facilities)
    {

        //$newRow['ETL_UPDATED_TIMESTAMP'] = $row['LASTMODIFIED'];
        $newRow['CGTaskID'] = $row['CGTASKID'];
        $newRow['CreatedBy'] = $row[strtoupper('CreatedBy')];
        $newRow['BillingFacilityID'] = $row[strtoupper('HHABranchID')];
        //don't set FacilityID becuase all existing rows have it and it's the right value.
        //at some point we have to either set all FacilityID to BillingFacilityID or
        //set mssql_facilities.FacilityID here to match
        if(isset($facilities[$row[strtoupper('HHABranchID')]])){
            $newRow['FacilityID'] = $facilities[$row[strtoupper('HHABranchID')]];
        }else{
            $newRow['FacilityID'] = '';
        }
        echo 'Facility: ' . $newRow['BillingFacilityID'] . ' > ' . $newRow['FacilityID'] . "\n";
        $newRow['PatientID'] = $row[strtoupper('ClientID')];
        $newRow['ProviderID'] = $row[strtoupper('Caregiver')];
        $newRow['BillingPatientID'] = $row[strtoupper('ClientID')];
        $newRow['BillingProviderID'] = $row[strtoupper('Caregiver')];
        $newRow['InsuranceID'] = $row[strtoupper('PayerID')];
        $newRow['ServiceCodeID'] = $row[strtoupper('ServiceCodeID')];
        $newRow['ServiceName'] = '';
        $newRow['ScheduleDate'] = $row['SCHEDULEDATE'];
        $newRow['PlannedStart'] = $row[strtoupper('PlannedStart')];
        $newRow['PlannedEnd'] = $row[strtoupper('PlannedEnd')];
        $newRow['ActualStart'] = $row[strtoupper('ActualStart')];
        $newRow['ActualEnd'] = $row[strtoupper('ActualEnd')];
        $newRow['PlannedHours'] = $row[strtoupper('PlannedHours')];
        $newRow['ActualHours'] = $row[strtoupper('ActualHours')];
        $newRow['Hours'] = $row[strtoupper('Hours')];
        $newRow['ScheduleStatusID'] = $this->getScheduleStatusID($row[strtoupper('ScheduleStatus')]);
        $newRow['EChartStatusID'] = $this->getEchartStatusID($row[strtoupper('EChartStatus')]);
        $newRow['Miles'] = $row[strtoupper('Miles')];
        $newRow['IsAuthRequired'] = $row[strtoupper('IsAuthRequired')];
        $newRow['AuthorizationStatusID'] = $this->getAuthStatusID($row[strtoupper('Authorizations')]);
        $newRow['PaybleHours'] = $row[strtoupper('PaybleHours')];
        $newRow['MissedVisitReasonID'] = 0;
        $newRow['MissedVisitReason'] = $row[strtoupper('MissedVisitReason')];
        $newRow['WeekStart'] = $row[strtoupper('WeekStart')];
        $newRow['EpisodeID'] = $row[strtoupper('EpisodeID')];
        $newRow['IsPRNVisit'] = $row[strtoupper('IsPRNVisit')];
        $newRow['RevenueCode'] = $row[strtoupper('RevenueCode')];
        $newRow['GCode'] = $row[strtoupper('GCode')];
        $newRow['Modifier1'] = $row[strtoupper('Modifier1')];
        $newRow['Modifier2'] = $row[strtoupper('Modifier2')];
        $newRow['Modifier3'] = $row[strtoupper('Modifier3')];
        //$newRow['Modifier4'] = $row[strtoupper('Modifier4')];
        $newRow['Units'] = $row[strtoupper('Units')];
        $newRow['HasChild'] = $row[strtoupper('HasChild')];
        $newRow['IsChild'] = $row[strtoupper('IsChild')];
        $newRow['ParentCgTaskID'] = $row[strtoupper('ParentCgTaskID')];
        $newRow['IsBillable'] = $row[strtoupper('IsBillable')];
        $newRow['IsPayable'] = $row[strtoupper('IsPayable')];
        $newRow['IsBilled'] = $row[strtoupper('IsBilled')];
        $newRow['IsPaid'] = $row[strtoupper('IsPaid')];
        $newRow['BillRate'] = $row[strtoupper('BillRate')];
        $newRow['PayRate'] = $row[strtoupper('PayRate')];
        $newRow['BillModeID'] = $this->getBillModeID($row[strtoupper('BillMode')]);
        $newRow['PayMode'] = $row[strtoupper('PayMode')];
        $newRow['BilledAmount'] = $row[strtoupper('BilledAmount')];
        $newRow['PayrolledAmount'] = $row[strtoupper('PayrolledAmount')];
        $newRow['RegularAmount'] = $row[strtoupper('RegularAmount')];
        $newRow['OTAmount'] = $row[strtoupper('OTAmount')];
        $newRow['BonusAmount'] = $row[strtoupper('BonusAmount')];
        $newRow['BaseRate'] = $row[strtoupper('BaseRate')];
        $newRow['CancellationReason'] = $row[strtoupper('CancellationReason')];
        $newRow['ContractualRate'] = $row[strtoupper('ContractualRate')];
        $newRow['ContractualModeID'] = $this->getContractualModeID($row[strtoupper('ContractualMode')]);
        $newRow['ContractualAmount'] = $row[strtoupper('ContractualAmount')];
        $newRow['NonBillableReason'] = $row[strtoupper('NonBillableReason')];
        $newRow['RegularHours'] = $row[strtoupper('RegularHours')];
        $newRow['OTHours'] = $row[strtoupper('OTHours')];
        $newRow['isMiscVisit'] = $row[strtoupper('isMiscVisit')];
        $newRow['Cost'] = $row[strtoupper('Cost')];
        $newRow['Revenue'] = $row[strtoupper('Revenue')];
        $newRow['ContractualRevenue'] = $row[strtoupper('ContractualRevenue')];
        //$newRow['UserBranchID'] = $row[strtoupper('')];
        $newRow['Profit'] = $row[strtoupper('Profit')];
        $newRow['ProfitContractual'] = $row[strtoupper('ProfitContractual')];
        $newRow['Margin'] = $row[strtoupper('Margin')];
        $newRow['MarginContractual'] = $row[strtoupper('MarginContractual')];
        $newRow['BilledHours'] = $row[strtoupper('BilledHours')];
        $newRow['ScheduleProductivityPoints'] = $row[strtoupper('ScheduleProductivityPoints')];
        $newRow['PayrollWeekStartDate'] = $row[strtoupper('PayrollWeekStartDate')];
        $newRow['WeekNo'] = $row[strtoupper('WeekNo')];
        $newRow['ClinicianProductivityPerWeek'] = $row[strtoupper('ClinicianProductivityPerWeek')];
        $newRow['AuthorizationID'] = $row[strtoupper('AuthorizationID')];
        $newRow['ScheduleMiscVisitID'] = $row[strtoupper('ScheduleMiscVisitID')];
        $newRow['SubmittedOn'] = $row[strtoupper('SubmittedOn')];
        $newRow['ApprovedOn'] = $row[strtoupper('ApprovedOn')];
        $newRow['ScheduleInvoiceDate'] = $row[strtoupper('InvoicedOn')];
        $newRow['PaymentReceivedOn'] = $row[strtoupper('PaymentRecievedOn')];
        //$newRow['PrimaryIcdCode'] = $row[strtoupper('PrimaryIcdCode')];
        //$newRow['SurgicalIcdCode'] = $row[strtoupper('SurgicalIcdCode')];
        //$newRow['OtherDiagnosisCode'] = $row[strtoupper('')];
        $newRow['HolidayHours'] = $row[strtoupper('HolidayHours')];
        $newRow['HolidayPayrollAmount'] = $row[strtoupper('HolidayPayrollAmount')];
        $newRow['HolidayPayrollRates'] = $row[strtoupper('HolidayPayrollRate')];
        $newRow['OTPayrollRate'] = $row[strtoupper('OTPayrollRate')];
        $newRow['AddedOn'] = $row[strtoupper('AddedOn')];
        $newRow['SecondaryPayerSourceNames'] = $row[strtoupper('SecondaryPayerSourceNames')];
        $newRow['TertiaryPayerSourceNames'] = $row[strtoupper('TertiaryPayerSourceNames')];
        //$newRow['IsEvvSchedule'] = $row[strtoupper('IsEvvSchedule')];
        //$newRow['EVVAggregatorStatus'] = $row[strtoupper('EVVAggregatorStatus')];

        $newRow['EMRID'] = 1;
        $newRow['ClaimDetailID'] = '';
        $newRow['CalculatedRegularHours'] = '';
        $newRow['CalculatedRegularRate'] = '';
        $newRow['ReportingCompletedHours'] = '';
        $newRow['ReportingMissedHours'] = '';
        $newRow['ReportingPlannedHours'] = '';
        $newRow['MissedVisitReasonCategoryID'] = '';
        $newRow['CalculatedRevenue'] = '';
        $newRow['CalculatedCost'] = '';
        $newRow['GrossMargin'] = '';
        $newRow['MiscVisitReason'] = $row[strtoupper('MiscVisitReason')];
        $newRow['DistinctServiceCodeID'] = '';
        $newRow['PlacedHours'] = '';
        $newRow['ReportingHours'] = '';
        $newRow['SecondaryPayer'] = '';
        $newRow['TertiaryPayer'] = '';
        $newRow['updated_at'] = date_format(date_create(), config('constant')['longdatetimeformat']);
        $newRow['is_updated'] = 1;
        $newRow['is_deleted'] = 0;
        return $newRow;
    }

	public static function ensureUtf8Encoding($element) {
		if (is_array($element)) {
			// Recursively process arrays
			return array_map([self::class, 'ensureUtf8Encoding'], $element);
		}

		if (is_object($element)) {
			return array_map([self::class, 'ensureUtf8Encoding'], (array)$element);
		}

		// Handle individual elements (strings or other types)
		if (!mb_check_encoding($element, 'UTF-8')) {
			return mb_convert_encoding($element, 'UTF-8', 'auto');
		}

		return $element;
	}

    private function getFacilityList(){
        $facilities = [];
        $tmp = DB::select("select BillingFacilityID, FacilityID from mssql_facilities");
        $facilityRows = json_decode(json_encode($tmp), true);
        foreach ($facilityRows as $mybranch) {
            $facilities[$mybranch['BillingFacilityID']] = $mybranch['FacilityID'];
        }
        return $facilities;
    }


    private function getScheduleStatusID($statusID)
    {
        switch ($statusID) {
            case 'Approved':
                $scheduleStatus = 1;
                break;
            case 'Completed':
                $scheduleStatus = 2;
                break;
            case 'In_Progress':
                $scheduleStatus = 3;
                break;
            case 'MissedVisit':
                $scheduleStatus = 4;
                break;
            case 'Planned':
                $scheduleStatus = 5;
                break;
            case 'NA':
                $scheduleStatus = 6;
                break;
            case 'NO':
                $scheduleStatus = 7;
                break;
            case 'Office Auth':
                $scheduleStatus = 8;
                break;
            case 'Real Auth':
                $scheduleStatus = 9;
                break;
            case 'ReSchedule':
                $scheduleStatus = 10;
                break;
            case 'Deleted':
                $scheduleStatus = 11;
                break;
            default:
                $scheduleStatus = 0;
                break;
        }
        return $scheduleStatus;
    }


    private function getBillModeID($statusID)
    {
        switch ($statusID) {
            case '0':
                $scheduleStatus = 1;
                break;
            case 'Fixed':
                $scheduleStatus = 2;
                break;
            case 'Hourly':
                $scheduleStatus = 3;
                break;
            case 'Unit Based':
                $scheduleStatus = 4;
                break;
            default:
                $scheduleStatus = 0;
                break;
        }
        return $scheduleStatus;
    }

    private function getContractualModeID($statusID)
    {
        switch ($statusID) {
            case '':
                $scheduleStatus = 1;
                break;
            case '0':
                $scheduleStatus = 2;
                break;
            case 'Fixed':
                $scheduleStatus = 3;
                break;
            case 'Hourly':
                $scheduleStatus = 4;
                break;
            case 'Unit Based':
                $scheduleStatus = 5;
                break;
            default:
                $scheduleStatus = 0;
                break;
        }
        return $scheduleStatus;
    }

    private function getEchartStatusID($statusID)
    {
        switch ($statusID) {
            case 'Approved':
                $scheduleStatus = 2;
                break;
            case 'Planned':
                $scheduleStatus = 4;
                break;
            case 'Submitted':
                $scheduleStatus = 5;
                break;
            case 'OnCorrection':
                $scheduleStatus = 3;
                break;
            default:
                $scheduleStatus = 0;
                break;
        }
        return $scheduleStatus;
    }

    private function getAuthStatusID($statusID)
    {
        switch ($statusID) {
            case 'NA':
                $scheduleStatus = 1;
                break;
            case 'NO':
                $scheduleStatus = 2;
                break;
            case 'Office Auth':
                $scheduleStatus = 3;
                break;
            case 'Real Auth':
                $scheduleStatus = 4;
                break;
            default:
                $scheduleStatus = 0;
                break;
        }
        return $scheduleStatus;
    }


     /**
     * Sync service codes table
     */
    public function syncServiceCodes()
    {
        echo "Sync service codes \n";
        $syncType = 'daas_servicecodes';
        $tableName = 'service_codes_daas';
        $fieldName = 'SERVICE_ID';
        //start batch
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
        if($last_updated_id=='') {
            $last_updated_id = 0;
        }
        echo 'last_updated_id: ' . $last_updated_id . "\n";
        $limit = $batch_info['batch_size'];

        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $query = "select top " . $limit . " cast(ETL_UPDATED_TIMESTAMP as varchar(50)) as LastModified
                    ,concat(cast(ETL_UPDATED_TIMESTAMP as varchar(50)), cast(SERVICE_ID as varchar(50))) as CombinedString
                    ,SERVICE_ID AS service_id
                    ,SERVICE_PARENT_SERVICEID AS parent_serviceid
                    ,SERVICE_PAYERID AS payerid
                    ,AGENCY_ID AS agency_id
                    ,cast(SERVICE_TYPE as varchar(200)) AS type
					,cast(SERVICE_NAME as varchar(200)) AS name
					,cast(SEVICE_CODE as varchar(200)) AS code
					,cast(SERVICE_PAYCODE as varchar(200)) AS paycode
                    ,SERVICE_GROUP_ID AS group_id
                    ,SERVICE_FORM_ID AS form_id
                    ,SERVICE_CREATED_BY AS sf_created_by
					,SERVICE_ACTIVE AS active
                    ,SERVICE_CONT_BILLRATE as ContractualRate
                    ,cast(SERVICE_CONT_BILLMODE as varchar(50)) as ContractualBillMode
                    ,cast(SERVICE_UNITTYPE as varchar(50)) as UnitType
                    ,SERVICE_UNIT as Unit
                    ,SERVICE_MINUTEINUNIT as MinuteInUnit
                    ,SERVICE_CATEGORY as Category
                    from SERVICECODESMASTER_SVW
                    WHERE CombinedString > '" . $combinedString ."'
                    order by CombinedString ASC";
        echo $query . "\n";
        $rawData = DB::connection('daas')->select(DB::raw($query));
        $data = json_decode(json_encode($rawData), true);
        $recordCount = 0;
        foreach($data as $row) {
            $recordCount++;
            var_dump($row);
            echo "\n";
            echo $row['LASTMODIFIED'] . "\n";
            $newRow = [];
            $last_updated_date = $row['LASTMODIFIED'];
            $last_updated_id = $row[$fieldName];

            echo 'last updated: ' . $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row[$fieldName];
            $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                $newKey = str_replace(' ', '_', $key);

                if (Schema::hasColumn($tableName, $newKey)) {
                    $newRow[$newKey] = $value;
                }
				$newRow['created_by'] = 1;
				$newRow['updated_by'] = 1;
				$newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
            }
            if (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
                DB::table($tableName)->whereRaw("`{$fieldName}`= ?", [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Record' . "\n";
                $newRow['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table($tableName)->insert($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);

        DB::statement("update service_codes_daas
                        left join lob on lob.LOBName =
                            case
                                when service_codes_daas.type = 'SN' then
                                    case
                                        when service_codes_daas.name like '%SV%' then 'Skilled Visits'
                                        when service_codes_daas.name like '%CNA%' then 'CNA'
                                        when service_codes_daas.name like '%IHSS%' then 'IHSS'
                                        else 'PDN'
                                    end
                                when service_codes_daas.type = 'AIDE' then 'Attendant Care'
                                else service_codes_daas.type
                            end
                        set service_codes_daas.lobid = lob.LOBID;");
    }
     /**
     * Sync service codes table
     */
    public function syncServiceCodeRateHistory()
    {
        echo "Sync service codes rates\n";
        $syncType = 'daas_servicecoderates';
        $tableName = 'service_code_rates';
        $fieldName = 'serviceRateId';
        //start batch
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
        if($last_updated_id=='') {
            $last_updated_id = 0;
        }
        echo 'last_updated_id: ' . $last_updated_id . "\n";
        $limit = $batch_info['batch_size'];

        $combinedString = str_replace("'", "''", $last_updated_date . $last_updated_id);
        $query = "select top " . $limit . " cast(ETL_UPDATED_TIMESTAMP as varchar(50)) as LastModified
                    ,concat(cast(ETL_UPDATED_TIMESTAMP as varchar(50)), cast(SERVICERATE_ID as varchar(50))) as CombinedString
                    ,SERVICERATE_ID AS serviceRateID
                    ,SERVICERATE_SERVICECODE_ID AS serviceCodeID
                    ,cast(SERVICERATE_START_DATE as varchar(50)) as startDate
                    ,cast(SERVICERATE_END_DATE as varchar(50)) as endDate
                    ,AGENCY_ID AS agency_id
                    ,SERVICERATE_CONTRACTUAL_BILLRATE as ContractualRate
                    ,cast(SERVICERATE_CONTRACTUAL_BILLMODE as varchar(50)) as ContractualBillMode
                    ,cast(SERVICERATE_UNITTYPE as varchar(50)) as UnitType
                    ,SERVICERATE_UNIT as Unit
                    ,SERVICERATE_MINUTEINUNIT as MinuteInUnit
                    ,ISVALIDRECORD
                    from SERVICERATEHISTORY_SVW
                    WHERE CombinedString > '" . $combinedString ."'
                    order by CombinedString ASC";
        echo $query . "\n";
        $rawData = DB::connection('daas')->select(DB::raw($query));
        $data = json_decode(json_encode($rawData), true);
        $recordCount = 0;
        foreach($data as $row) {
            $recordCount++;
            var_dump($row);
            echo "\n";
            echo $row['LASTMODIFIED'] . "\n";
            $newRow = [];
            $last_updated_date = $row['LASTMODIFIED'];
            $last_updated_id = $row['SERVICERATEID'];

            echo 'last updated: ' . $last_updated_date . ' - ' . $last_updated_id . "\n";
            $uniqueID = $row['SERVICERATEID'];
            $dbRecord = DB::select("select * from $tableName where `$fieldName` = '" . $uniqueID . "'");
            foreach ($row as $key => &$value) {
                if (is_null($value)) {
                    $value = '';
                }
                $newKey = str_replace(' ', '_', $key);

                if (Schema::hasColumn($tableName, $newKey)) {
                    $newRow[$newKey] = $value;
                }
				$newRow['created_by'] = 1;
				$newRow['updated_by'] = 1;
				$newRow['updated_at'] = date_format(date_create(), 'Y-m-d H:i:s');
            }
            if (!empty($dbRecord)) {
                echo 'Update Record' . "\n";
                DB::table($tableName)->whereRaw("`{$fieldName}`= ?", [$uniqueID])->update($newRow);
            } else {
                echo 'Insert Record' . "\n";
                $newRow['created_at'] = date_format(date_create(), 'Y-m-d H:i:s');
                DB::table($tableName)->insert($newRow);
            }
            H::getHelper('Sync')->update_last_updated($syncType, $last_updated_date, $last_updated_id);
        }
        echo "sync finished \n";
        $batch_status = H::getHelper('Sync')->stopBatch($syncType);
    }

    public function updatePayers()
    {
        $querySuccessMessage = 'Query was successful';
        $errorMessage = "There was some error";
        $errorConstant = 'Error: ';
        $todayDate = date('Y-m-d');
        $logFile = 'updateScheduleMasterPayers';
        //secondary payer
        $query = "update `mssql_schedulemaster`
                set SecondaryPayer =
                (CASE WHEN (SecondaryPayerSourceNames = ''  or SecondaryPayerSourceNames like '%,%') THEN NULL ELSE CAST(SecondaryPayerSourceNames AS int) END)
                WHERE updated_at > '" . $todayDate . "'";

        H::ExcelLog('Updating SecondaryPayer: ' . $query, $logFile);
        try{
            $result = DB::statement($query);
            if($result){
                H::ExcelLog('Updating SecondaryPayer was successful', $logFile);
                echo $querySuccessMessage . "\n";
            }else{
                H::ExcelLog($errorMessage, $logFile);
                echo $errorMessage . "\n";
                //send a notification
            }
        }catch (\Exception $e){
            H::ExcelLog($errorConstant . $e->getMessage(), $logFile);
        }

        //tertiary payer
        $query = "update `mssql_schedulemaster`
                set TertiaryPayer =
                (CASE WHEN (TertiaryPayerSourceNames = ''  or TertiaryPayerSourceNames like '%,%') THEN NULL ELSE CAST(TertiaryPayerSourceNames AS int) END)
                WHERE updated_at > '" . $todayDate . "'";

        H::ExcelLog('Updating TertiaryPayer: ' . $query, $logFile);
        try{
            $result = DB::statement($query);
            if($result){
                H::ExcelLog('Updating TertiaryPayer was successful', $logFile);
                echo $querySuccessMessage . "\n";
            }else{
                H::ExcelLog($errorMessage, $logFile);
                echo $errorMessage . "\n";
                //send a notification
            }
        }catch (\Exception $e){
            H::ExcelLog($errorConstant . $e->getMessage(), $logFile);
        }

        //terminal payer
        $query = "update  `mssql_schedulemaster`
                set TerminalPayer =
                COALESCE(TertiaryPayer, SecondaryPayer, InsuranceID)
                WHERE updated_at > '" . $todayDate . "'";

        H::ExcelLog('Updating TerminalPayer: ' . $query, $logFile);
        try{
            $result = DB::statement($query);
            if($result){
                H::ExcelLog('Updating TermimalPayer was successful', $logFile);
                echo $querySuccessMessage . "\n";
            }else{
                H::ExcelLog($errorMessage, $logFile);
                echo $errorMessage . "\n";
                //send a notification
            }
        }catch (\Exception $e){
            H::ExcelLog($errorConstant . $e->getMessage(), $logFile);
        }

        //terminal payer
        $query = "update  `mssql_schedulemaster`
                set TerminalPayer =
                COALESCE(TertiaryPayer, SecondaryPayer, InsuranceID)
                WHERE updated_at > '" . $todayDate . "'";

        H::ExcelLog('Updating TerminalPayer: ' . $query, $logFile);
        try{
            $result = DB::statement($query);
            if($result){
                H::ExcelLog('Updating TermimalPayer was successful', $logFile);
                echo $querySuccessMessage . "\n";
            }else{
                H::ExcelLog($errorMessage, $logFile);
                echo $errorMessage . "\n";
                //send a notification
            }
        }catch (\Exception $e){
            H::ExcelLog($errorConstant . $e->getMessage(), $logFile);
        }

    }



}
