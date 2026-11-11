<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Helpers\H;
use App\MssqlRecruitertracker;
use DB;
use Illuminate\Support\Facades\Auth;

class SnowFlexController extends Controller
{
    public function testconnnection(Request $request)
    {
		//$mssqlTableName = '[Excelss].[vAOCAPP_DATA]';
		$mssqlTableName = '[Excelss].[GENERAL_SpecialPayerGroupMapping]';
		//$mssqlTableName = '[Excelss].[GENERAL_IntakePatients]';
		//$mssqlTableName = '[Excelss].[MAP_CoverageClinicianList]';
		//$mssqlTableName = '[Excelss].[NeedToHireInsertFromApp]';
		$conn = H::getHelper('Sync')->OpenConnection();
		$tsql = 'SELECT * FROM '.$mssqlTableName;
        //$tsql = "SELECT top 100 * FROM ".$mssqlTableName." where cast(CalendarDate as date) >= '2024-09-30' and cast(CalendarDate as date) <= '2024-10-06' AND Classification = 'NTH_PREV_STAFFED_HOURS' order by CalendarDate desc";
		//$tsql = 'DELETE FROM '.$mssqlTableName.' WHERE CalendarDate >= \'2024-08-02 00:00:00.000\'';
		//$tsql .= " WHERE PatientAccount = '41454-PDN'";
		echo $tsql . "\n";
        $getRecords = sqlsrv_query($conn, $tsql);
		
		/*$stmt = sqlsrv_prepare($conn, $tsql);
		if (!$stmt) {
			print_r(sqlsrv_errors());die();
		} else {
			if (sqlsrv_execute($stmt)) {
				echo "Success";
			} else {
				print_r(sqlsrv_errors());die();
			}
		}
			
		exit;*/
		//print"<pre>";print_r($getRecords);
		$Cnt = 0;
		while ($row = sqlsrv_fetch_array($getRecords, SQLSRV_FETCH_ASSOC)) {
			print"<pre>";print_r($row);
			$Cnt++;
		}
		echo "Total Count ".$Cnt;
		exit;
        $query = 'select top 1 cast(ETL_UPDATED_TIMESTAMP as varchar(23)) as LastModified 
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
            ,cast(S_SCHEDULE_STATUS as varchar(10)) AS ScheduleStatus
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
            ,cast(MODIFIER_4 as varchar(100)) AS Modifier4
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
            ,cast(PRIMARY_DIAGNOSIS_CODE as varchar(100)) AS PrimaryIcdCode
            ,cast(SURGCICAL_DIAGNOSIS_CODE as varchar(100)) AS SurgicalIcdCode
            ,cast(OTHER_DIAGNOSIS_CODE as TEXT) AS OtherDiagnosisCode
            ,S_HOLIDAY_HOURS AS HolidayHours
            ,S_HOLIDAY_PAYROLL_AMOUNT AS HolidayPayrollAmount
            ,S_HOLIDAY_PAYROLL_RATE AS HolidayPayrollRate
            ,S_OT_PAYROLL_RATE AS OTPayrollRate
            ,cast(SCHEDULE_ADDED_ON as varchar(23)) AS AddedOn
            ,cast(SECONDARY_PAYERSOURCE as varchar(250)) AS SecondaryPayerSourceNames
            ,cast(TERTIARY_PAYERSOURCE as varchar(250)) AS TertiaryPayerSourceNames
            ,cast(S_MISSEDVISIT_REASONTYPE as varchar(100)) AS MissedVisitReasonType
            ,cast(S_MISSEDVISIT_NOTE as varchar(100)) AS MissedVisitNote
            ,cast(S_MISC_VISITREASON as varchar(100)) AS MiscVisitReason
            ,S_IS_EVV_SCHEDULE AS IsEvvSchedule
            ,cast(S_ECC_AGGREGATORSTATUS as varchar(100)) AS EVVAggregatorStatus
            ,cast(SCHEDULE_UNIQUE_IDENTIFIER as varchar(100)) as ScheduleUniqueIdentifier

            
            from SCHEDULEMASTER_SVW where SCHEDULE_Id = \'550105\' AND S_SCEDULE_CREATOR_ID = \'13538\' order by cast(ETL_UPDATED_TIMESTAMP as varchar(23)) desc';

DB::purge('daas');
        $test = DB::connection('daas')->select(DB::raw($query)); 
        echo '<pre>';
        var_dump($test);
    }
}
