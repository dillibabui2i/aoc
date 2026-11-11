<?php

namespace App\Http\Controllers;

use App\Enums\MssqlPatientStatus;
use App\Enums\MssqlScheduleMasterStatus;
use App\Helpers\H;
use App\Helpers\LD;
use App\Services\Intake\IntakeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IntakeReportController extends Controller
{
    const BACKGROUND1 = 'background-color: #215eac;color:#fff;';
    const BACKGROUND2 = 'background-color: #D5D5D5;color:#000';
    const BACKGROUND3 = 'background-color: #D9D9D9;color:#444;';
    const BACKGROUND4 = 'background-color: #dbf0fd;color:#444444;';
    const NATIONALTOTAL = 'National Totals';
    const ANDCONDITION = ' AND ';
    // normalizes "Last, First" -> "First Last" and lowercases, so BD Rep name variants group/filter as one representative
    const BD_REP_NORMALIZE_SQL = "LOWER(TRIM(IF(LOCATE(',', %1\$s) > 0, CONCAT(TRIM(SUBSTRING_INDEX(%1\$s, ',', -1)), ' ', TRIM(SUBSTRING_INDEX(%1\$s, ',', 1))), %1\$s)))";
    const GROUP3 = '@group3';
    const CCC = 'Client Care Coordinator';
    const SPECIALPAYER = 'mssql_patients.payer_group as Special Payer';
    const COORDINATOR = 'users.name as Coordinator';
    const BRANCH = 'mssql_facilities.FacilityName as Branch';
    private const PERMISSIBLE_SERVICE_NAMES = [
        'CNA Admission',
        'CNA Admission (Add LOB)',
        'CNA Admission Field (Add LOB)',
        'CNA Admission Oasis',
        'IHSS Admission',
        'PDN Admission (Add LOB)',
        'PDN Admission (Billable)',
        'PDN Admission Field (Add LOB)',
        'RN ADMISSION',
        'RN Admission Field',
        'RN Admission OASIS',
        'RN Admission OASIS Field',
        'SV Admission',
        'SV Admission (Add LOB)',
        'SV Admission (NONBILL)',
        'SV Admission (OFFICE NONBILL)',
        'SV Admission (OFFICE)',
        'Physical Therapy Initial Eval – High Complexity',
        'Physical Therapy Initial Eval – Low Complexity',
        'Physical Therapy Initial Eval – Mod Complexity',
        'Physical Therapy Initial Evaluation',
        'Tele-Physical Therapy Initial Evaluation',
        'PT Initial Evaluation – Acute',
        'PT Initial Evaluation – Acute EI',
        'PT Initial Evaluation – Long Term',
        'PT Initial Evaluation – Long Term EI',
        'PT SOC Initial Evaluation – Acute',
        'PT SOC Initial Evaluation – Acute - OASIS',
        'PT SOC Initial Evaluation – Acute EI',
        'PT SOC Initial Evaluation – Long Term',
        'PT SOC Initial Evaluation – Long Term – OASIS',
        'PT SOC Initial Evaluation – Long Term EI',
        'Tele-PT Initial Evaluation – Acute',
        'Tele-PT Initial Evaluation – Acute EI',
        'Tele-PT Initial Evaluation – Long Term',
        'Tele-PT Initial Evaluation – Long Term EI',
        'Occupational Therapy Initial Eval – Low Complexity',
        'Occupational Therapy Initial Eval – Mod Complexity',
        'Occupational Therapy Initial Eval High Complexity',
        'Occupational Therapy Initial Evaluation',
        'Tele-Occupational Therapy Initial Evaluation',
        'OT Initial Evaluation – Acute',
        'OT Initial Evaluation – Acute EI',
        'OT Initial Evaluation – Long Term',
        'OT Initial Evaluation – Long Term EI',
        'OT SOC Initial Evaluation – Acute',
        'OT SOC Initial Evaluation – Acute - OASIS',
        'OT SOC Initial Evaluation – Acute EI',
        'OT SOC Initial Evaluation – Long Term',
        'OT SOC Initial Evaluation – Long Term – OASIS',
        'OT SOC Initial Evaluation – Long Term EI',
        'Tele-OT Initial Evaluation – Acute',
        'Tele-OT Initial Evaluation – Acute EI',
        'Tele-OT Initial Evaluation – Long Term',
        'Tele-OT Initial Evaluation – Long Term EI',
        'Behavioral & Qual Analys of Voice & Resonance',
        'Eval of Oral & Pharyngeal Swallow Function',
        'Eval of Speech Sound Production w/Language Comp & Expr',
        'Evaluation of Speech Fluency',
        'Evaluation of Speech Sound Production',
        'Tele-Behavioral & Qual Analys of Voice & Resonance',
        'Tele-Eval of Oral & Pharyngeal Swallow Function',
        'Tele-Eval of Speech Sound Production w/Language Comp & Expr',
        'Tele-Evaluation of Speech Fluency',
        'Tele-Evaluation of Speech Sound Production',
        'ST Initial Evaluation – Acute',
        'ST Initial Evaluation – Acute EI',
        'ST Initial Evaluation – Long Term',
        'ST Initial Evaluation – Long Term EI',
        'ST SOC Initial Evaluation – Acute',
        'ST SOC Initial Evaluation – Acute - OASIS',
        'ST SOC Initial Evaluation – Acute EI',
        'ST SOC Initial Evaluation – Long Term',
        'ST SOC Initial Evaluation – Long Term – OASIS',
        'ST SOC Initial Evaluation – Long Term EI',
        'Tele-ST Initial Evaluation – Acute',
        'Tele-ST Initial Evaluation – Acute EI',
        'Tele-ST Initial Evaluation – Long Term',
        'Tele-ST Initial Evaluation – Long Term EI'
    ];

    public function __construct(private IntakeService $intakeService)
    {}

    private function normalizeBdRepSql(string $column): string
    {
        return sprintf(self::BD_REP_NORMALIZE_SQL, $column);
    }

    public function getintakepopup(Request $request)
    {
        $req = $request->all();
        H::ExcelLog(print_r($req, true), 'IntakeReportPop');
        $rowFilter = json_decode($req['rowfilter'], true);
        $form = $req['form'];
        $view = '';
        foreach ($form as $key => $value) {
            if ($value['name'] == 'view')
                $view = $value['value'];
            if (empty($value['value']) || substr_count($value['name'], "date") || substr_count($value['name'], "excludeprn"))
                continue;
            if (substr_count($value['name'], "search") > 0) {
                if (substr_count($value['name'], "[") == 1) {
                    $key = str_replace(["]", "search["], ["", ""], $value['name']);
                    $rowFilter[$key] = $value['value'];
                } else if (substr_count($value['name'], "[") == 2) {
                    $key = str_replace(["]", "search", "["], ["", "", ""], $value['name']);
                    if (isset($rowFilter[$key]) && !is_array($rowFilter[$key])) {
                        $temp = $rowFilter[$key];
                        unset($rowFilter[$key]);
                        $rowFilter[$key][] = $temp;
                        $rowFilter[$key][] = $value['value'];
                    } else {
                        $rowFilter[$key][] = $value['value'];
                    }
                }
            } else {
                if (in_array($value['name'], array('_token', 'isajax', 'period', 'source', 'lob', 'view'))) {
                    continue;
                }
                $rowFilter[$key] = $value['value'];
            }
        }

        $isLeadOnly = !empty($rowFilter['leadonly']);
        unset($rowFilter['leadonly']);

        $result = [];
        $query = null;
        if (isset($req['type'])) {
            if ($req['type'] == 'referrals') {
                $query = DB::table("intake")->select(
                    DB::raw(
                        "CONCAT(
                            mssql_patients.Patient_FirstName,
                            ' ',
                            mssql_patients.Patient_LastName,
                            '|',
                            mssql_patients.id,
                            '|',
                            'clientintake',
                            '|',
                            COALESCE(intake.LOBID, '')
                        ) as `Client`"
                    ),
                    'referralsource_master.name as Facility',
                    'mssql_facilities.BranchName as Branch',
                    'lob.LOBName as LOB',
                    "mssql_patients.Payer",
                    DB::raw($this->intakeService->getPatientHoursFieldCaseSql('intake.LOBID') . " AS `Hours/Visits`"),
                    DB::raw("IF(
                        IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) != '' AND
						IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) IS NOT NULL,
                        DATE_FORMAT(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate),'%m/%d/%Y'),
                        ''
                    ) as `Referral Date`"),
                    DB::raw("IF(
                        intake.CF_GoalSOCDate != '' AND intake.CF_GoalSOCDate IS NOT NULL,
                        DATE_FORMAT(intake.CF_GoalSOCDate,'%m/%d/%Y'),
                        ''
                    ) as `Goal SOC Date`"),
					DB::raw("
								CASE
									WHEN (
										intake.intake_bucket = 'intake_prospective_clients'
									) THEN 'Prospective Clients'
									WHEN (
										intake.intake_bucket = 'intake_schedule'
									) THEN 'Intake Scheduled'
									WHEN (
										intake.intake_bucket = 'intake_pending_soc'
									) THEN 'Pending Start of Care'
									WHEN (
										intake.intake_bucket = 'intake_client_starts'
									) THEN 'Client Starts'
                                    WHEN (
										intake.intake_bucket = 'intake_pending_non_admit'
									) THEN 'Pending Non Admit'
									WHEN (
										{$this->admittedConditionSql('mssql_schedulemaster', 'mssql_patients')}
									) THEN 'Admitted'
									WHEN (
										(
											{$this->nonAdmitConditionSql('mssql_patients')}
										)
									) THEN 'Non Admit'
									END as Status"),
                );
				$query->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $query->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $query->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $query->leftJoin("regions", function ($query) {
                    $query->on("regions.id", "=", "mssql_facilities.region");
                });
                $query->leftJoin("referralsource_master", function ($query) {
                    $query->on("referralsource_master.source_id", "=", "intake.ReferralSourceID");
                });
				$query->leftJoin("mssql_schedulemaster", function ($query) {
                    $query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_first_billable_visit");
                });
                if (count($rowFilter)) {
                    $fieldmap = [
                        'state_id' => 'mssql_facilities.id',
                        'region_id' => 'regions.id',
						'payer' => 'mssql_patients.Payer',
						'intakecoordinator' => 'intake.ic_id',
						'state' => 'mssql_facilities.State',
						'region' => 'regions.region_name',
                        'lob' => 'intake.LOBID',
                    ];
                    if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                        $intakeStatus = "
                            CASE
                                WHEN (
                                    intake.intake_bucket = 'intake_prospective_clients'
                                ) THEN 'Prospective Clients'
                                WHEN (
                                    intake.intake_bucket = 'intake_schedule'
                                ) THEN 'Intake Scheduled'
                                WHEN (
                                    intake.intake_bucket = 'intake_pending_soc'
                                ) THEN 'Pending Start of Care'
                                WHEN (
                                    intake.intake_bucket = 'intake_client_starts'
                                ) THEN 'Client Starts'
                                WHEN (
                                    intake.intake_bucket = 'intake_pending_non_admit'
                                ) THEN 'Pending Non Admit'
                                WHEN (
                                    {$this->admittedConditionSql('mssql_schedulemaster', 'mssql_patients')}
                                ) THEN 'Admitted'
                                WHEN (
                                    (
                                        {$this->nonAdmitConditionSql('mssql_patients')}
                                    )
                                ) THEN 'Non Admit'
                                END
                        ";
                    } else {
                        $intakeStatus = "
                            CASE
                                WHEN (
                                    intake.intake_bucket = 'intake_prospective_clients'
                                ) THEN 'Prospective Clients'
                                WHEN (
                                    intake.intake_bucket = 'intake_schedule'
                                ) THEN 'Intake Scheduled'
                                WHEN (
                                    intake.intake_bucket = 'intake_pending_soc'
                                ) THEN 'Pending Start of Care'
                                WHEN (
                                    intake.intake_bucket = 'intake_client_starts'
                                ) THEN 'Client Starts'
                                WHEN (
                                    intake.intake_bucket = 'intake_pending_non_admit'
                                ) THEN 'Pending Non Admit'
                                WHEN (
                                    {$this->admittedConditionSql('mssql_schedulemaster', 'mssql_patients')}
                                ) THEN 'Admitted'
                                WHEN (
                                    (
                                        {$this->nonAdmitConditionSql('mssql_patients')}
                                    )
                                ) THEN 'Non Admit'
                                END
                        ";
                    }
                    $rawFieldMap = [
                        'Branch_Location' => 'NULLIF(referralsource_master.name, \'\')',
                        'BranchName' => 'NULLIF(mssql_facilities.BranchName, \'\')',
                        'BD_Rep' => 'NULLIF(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.BD_Rep, intake.BD_Rep), \'\')',
                        'referral_status' => $intakeStatus
                    ];
                    $query->where(function ($q) use ($rowFilter, $fieldmap, $form, $rawFieldMap) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if ($rfkey == 'start_of_week') {
                                if ($form[2]['value'] == 'month') {
                                    $q->whereRaw('MONTH(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate)) = Month(CONCAT(?,"-01")) AND
                                    YEAR(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate)) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                } else if ($form[2]['value'] == 'quarter') {
                                    $q->whereRaw('QUARTER(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate)) = QUARTER(CONCAT(?,"-01"))', [$rf]);
                                } else {
                                    $q->whereRaw('IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) >= ? AND
                                    IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) < (? + INTERVAL 7 - weekday(?) DAY)', [
                                        $rf,
                                        $rf,
                                        $rf
                                    ]);
                                }
                            } else {
                                $rowKey = isset($rawFieldMap[$rfkey]) ? $rawFieldMap[$rfkey] : '';
                                if (!empty($rowKey)) {
                                    if (empty($rf)) {
                                        $q->whereRaw($rowKey . " IS NULL ");
                                    } else {
                                        $q->whereRaw($rowKey . " = ? ", [$rf]);
                                    }
                                } else {
                                    if (is_array($rf)) {
                                        $q->whereIn($rfkey, $rf);
                                    } else {
                                        $q->where($rfkey, "=", $rf);
                                    }
                                }
                            }
                        }
                    });
                }
            } else if ($req['type'] == 'admits') {
                //fall back to mssql_patients.SOCDate when the admission visit was never synced into mssql_schedulemaster (PD-5144)
                $admitDateField = "COALESCE(mssql_schedulemaster.ScheduleDate, mssql_patients.SOCDate)";
                $query = DB::table("intake")->select(
                    DB::raw(
                        "CONCAT(
                            mssql_patients.Patient_FirstName,
                            ' ',
                            mssql_patients.Patient_LastName,
                            '|',
                            mssql_patients.id,
                            '|',
                            'clientintake',
                            '|',
                           COALESCE(intake.LOBID, '')
                        ) as `Client`"
                    ),
                    'referralsource_master.name as Facility',
                    'mssql_facilities.BranchName as Branch',
                    'lob.LOBName as LOB',
                    "mssql_patients.Payer",
                    DB::raw($this->intakeService->getPatientHoursFieldCaseSql('intake.LOBID') . " AS `Hours/Visits`"),
                    DB::raw("IF(
                        IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) != '' AND
						IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) IS NOT NULL,
                        DATE_FORMAT(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate),'%m/%d/%Y'),
                        ''
                    ) as `Referral Date`"),
                    DB::raw("
                        DATE_FORMAT($admitDateField,'%m/%d/%Y') as `Admit Date`"),
                    DB::raw("IF(
                        intake.CF_GoalSOCDate != '' AND intake.CF_GoalSOCDate IS NOT NULL,
                        DATE_FORMAT(intake.CF_GoalSOCDate,'%m/%d/%Y'),
                        ''
                    ) as `Goal SOC Date`"),
                );
				$query->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $query->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $query->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $query->leftJoin("regions", function ($query) {
                    $query->on("regions.id", "=", "mssql_facilities.region");
                });
                $query->leftJoin("referralsource_master", function ($query) {
                    $query->on("referralsource_master.source_id", "=", "intake.ReferralSourceID");
                });
                $query->leftJoin("mssql_schedulemaster", function ($query) {
                        $query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_admission_visit");
                });
                $query->leftJoin("service_codes_daas", function ($q) {
                    $q->on("mssql_schedulemaster.ServiceCodeID", "=", "service_codes_daas.service_id");
                });
                $query->whereRaw('ifnull(mssql_patients.IsNonAdmit,0) = 0');
                $query->whereRaw('(intake.intake_admission_visit > 0 OR mssql_patients.SOCDate is not null)');

                if (count($rowFilter)) {
                    $fieldmap = [
                        'state_id' => 'mssql_facilities.id',
                        'region_id' => 'regions.id',
						'payer' => 'mssql_patients.Payer',
						'intakecoordinator' => 'intake.ic_id',
						'state' => 'mssql_facilities.State',
						'region' => 'regions.region_name',
                        'lob' => 'intake.LOBID'
                    ];
                    $rawFieldMap = [
                        'Branch_Location' => 'NULLIF(referralsource_master.name, \'\')',
                        'BranchName' => 'NULLIF(mssql_facilities.BranchName, \'\')',
                        'BD_Rep' => 'NULLIF(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.BD_Rep, intake.BD_Rep), \'\')'
                    ];
                    $query->where(function ($q) use ($rowFilter, $fieldmap, $form, $rawFieldMap, $admitDateField) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if ($rfkey == 'start_of_week') {
                                if ($form[2]['value'] == 'month') {
                                    $q->whereRaw("MONTH($admitDateField) = Month(CONCAT(?,\"-01\")) AND
                                    YEAR($admitDateField) = YEAR(CONCAT(?,\"-01\"))", [$rf, $rf]);
                                } else if ($form[2]['value'] == 'quarter') {
                                    $q->whereRaw("QUARTER($admitDateField) = QUARTER(CONCAT(?,\"-01\"))", [$rf]);
                                } else {
                                    $q->whereRaw("$admitDateField >= ? AND
                                    $admitDateField < (? + INTERVAL 7 - weekday(?) DAY)", [
                                        $rf,
                                        $rf,
                                        $rf
                                    ]);
                                }
                            } else {
                                $rowKey = isset($rawFieldMap[$rfkey]) ? $rawFieldMap[$rfkey] : '';
                                if (!empty($rowKey)) {
                                    if (empty($rf)) {
                                        $q->whereRaw($rowKey . " IS NULL ");
                                    } else {
                                        $q->whereRaw($rowKey . " = ? ", [$rf]);
                                    }
                                } else {
                                    if (is_array($rf)) {
                                        $q->whereIn($rfkey, $rf);
                                    } else {
                                        $q->where($rfkey, "=", $rf);
                                    }
                                }
                            }
                        }
                    });
                }
            } else if ($req['type'] == 'nonadmit') {
                $query = DB::table("intake")->select(
                    DB::raw(
                        "CONCAT(
                            mssql_patients.Patient_FirstName,
                            ' ',
                            mssql_patients.Patient_LastName,
                            '|',
                            mssql_patients.id,
                            '|',
                            'clientintake',
                            '|',
                            COALESCE(intake.LOBID, '')
                        ) as `Client`"
                    ),
                    'referralsource_master.name as Facility',
                    'mssql_facilities.BranchName as Branch',
                    'lob.LOBName as LOB',
                    "mssql_patients.Payer",
                    DB::raw($this->intakeService->getPatientHoursFieldCaseSql('intake.LOBID') . " AS `Hours/Visits`"),
                    DB::raw("IF(
                        IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) != '' AND
						IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) IS NOT NULL,
                        DATE_FORMAT(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate),'%m/%d/%Y'),
                        ''
                    ) as `Referral Date`"),
                    DB::raw("IF(
                        mssql_patients.NonAdmitDate != '' AND mssql_patients.NonAdmitDate IS NOT NULL,
                        DATE_FORMAT(mssql_patients.NonAdmitDate,'%m/%d/%Y'),
                        ''
                    ) as `Non Admit Date`"),
                    "intake.intake_nonadmit_reason as Reason",
                );
				$query->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $query->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $query->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $query->leftJoin("regions", function ($query) {
                    $query->on("regions.id", "=", "mssql_facilities.region");
                });
                $query->leftJoin("referralsource_master", function ($query) {
                    $query->on("referralsource_master.source_id", "=", "intake.ReferralSourceID");
                });
				$query->whereRaw('(
						intake.intake_outcome = "Non Admit" OR mssql_patients.Patient_CurrentStatusID = 3
				)');
                if (count($rowFilter)) {
                    $fieldmap = [
                        'state_id' => 'mssql_facilities.id',
                        'region_id' => 'regions.id',
						'payer' => 'mssql_patients.Payer',
						'intakecoordinator' => 'intake.ic_id',
						'state' => 'mssql_facilities.State',
						'region' => 'regions.region_name',
                        'lob' => 'intake.LOBID'
                    ];
                    $rawFieldMap = [
                        'Branch_Location' => 'NULLIF(referralsource_master.name, \'\')',
                        'BranchName' => 'NULLIF(mssql_facilities.BranchName, \'\')',
                        'BD_Rep' => 'NULLIF(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.BD_Rep, intake.BD_Rep), \'\')',
                        'reason' => 'NULLIF(intake.intake_nonadmit_reason, \'\')'
                    ];
                    $query->where(function ($q) use ($rowFilter, $fieldmap, $form, $rawFieldMap) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if ($rfkey == 'start_of_week') {
                                if ($form[2]['value'] == 'month') {
                                    $q->whereRaw('MONTH(mssql_patients.NonAdmitDate) = Month(CONCAT(?,"-01")) AND
                                    YEAR(mssql_patients.NonAdmitDate) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                } else {
                                    $q->whereRaw('mssql_patients.NonAdmitDate >= ? AND
                                    mssql_patients.NonAdmitDate < (? + INTERVAL 7 - weekday(?) DAY)', [
                                        $rf,
                                        $rf,
                                        $rf
                                    ]);
                                }
                            } else {
                                $rowKey = isset($rawFieldMap[$rfkey]) ? $rawFieldMap[$rfkey] : '';
                                if (!empty($rowKey)) {
                                    if (empty($rf)) {
                                        $q->whereRaw($rowKey . " IS NULL ");
                                    } else {
                                        $q->whereRaw($rowKey . " = ? ", [$rf]);
                                    }
                                } else {
                                    if (is_array($rf)) {
                                        $q->whereIn($rfkey, $rf);
                                    } else {
                                        $q->where($rfkey, "=", $rf);
                                    }
                                }
                            }
                        }
                    });
                }
            } else if ($req['type'] == 'conversion') {
                $query = DB::table("intake")->select(
                    DB::raw(
                        "CONCAT(
                            mssql_patients.Patient_FirstName,
                            ' ',
                            mssql_patients.Patient_LastName,
                            '|',
                            mssql_patients.id,
                            '|',
                            'clientintake',
                            '|',
                           COALESCE(intake.LOBID, '')
                        ) as `Client`"
                    ),
                    'referralsource_master.name as Facility',
                    'lob.LOBName as LOB',
                    "mssql_patients.Payer",
                    DB::raw($this->intakeService->getPatientHoursFieldCaseSql('intake.LOBID') . " AS `Hours/Visits`"),
                    DB::raw("IF(
                        IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) != '' AND
						IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) IS NOT NULL,
                        DATE_FORMAT(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate),'%m/%d/%Y'),
                        ''
                    ) as `Referral Date`"),
                    DB::raw("
                        DATE_FORMAT(mssql_schedulemaster.ScheduleDate,'%m/%d/%Y') as `Admit Date`"),
                    DB::raw("IF(
                        intake.CF_GoalSOCDate != '' AND intake.CF_GoalSOCDate IS NOT NULL,
                        DATE_FORMAT(intake.CF_GoalSOCDate, '%m/%d/%Y'),
                        ''
                    ) as `Goal SOC Date`"),
                    (
                         DB::raw("CASE
								WHEN (
									intake.intake_bucket = 'intake_prospective_clients'
								) THEN 'Prospective Clients'
								WHEN (
									intake.intake_bucket = 'intake_schedule'
								) THEN 'Intake Scheduled'
								WHEN (
									intake.intake_bucket = 'intake_pending_soc'
								) THEN 'Pending Start of Care'
								WHEN (
									intake.intake_bucket = 'intake_client_starts'
								) THEN 'Client Starts'
                                WHEN (
                                    intake.intake_bucket = 'intake_pending_non_admit'
                                ) THEN 'Pending Non Admit'
                                WHEN (
                                    {$this->admittedConditionSql('smBillable', 'mssql_patients')}
                                ) THEN 'Admitted'
								WHEN (
									(
										{$this->nonAdmitConditionSql('mssql_patients')}
									)
								) THEN 'Non Admit'
								END as Status")
                    )
                    ,
                );
                if (!empty($rowFilter['IntakeStatus']) && $rowFilter['IntakeStatus'] == 'Non Admit') {
                    $query->addSelect('intake.intake_nonadmit_reason as Non Admit Reason');
                }
				$query->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $query->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $query->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $query->leftJoin("regions", function ($query) {
                    $query->on("regions.id", "=", "mssql_facilities.region");
                });
                $query->leftJoin("referralsource_master", function ($query) {
                    $query->on("referralsource_master.source_id", "=", "intake.ReferralSourceID");
                });
                $query->leftJoin("mssql_providers", function ($query) {
                    $query->on("mssql_providers.ClinicianID", "=", DB::raw("CAST(mssql_patients.SOCClinicianID AS CHAR)"));
                });
				$query->leftJoin("mssql_schedulemaster", function ($query) {
                    $query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_admission_visit");
                });
				$query->leftJoin("mssql_schedulemaster as smBillable", function ($query) {
                    $query->on("smBillable.CGTaskID", "=", "intake.intake_first_billable_visit");
                });
                if (count($rowFilter)) {
                    $fieldmap = [
                        'state_id' => 'mssql_facilities.id',
                        'region_id' => 'regions.id',
						'payer' => 'mssql_patients.Payer',
						'intakecoordinator' => 'intake.ic_id',
						'state' => 'mssql_facilities.State',
						'region' => 'regions.region_name',
                        'lob' => 'intake.LOBID'
                    ];
                    $intakeStatus = "
                        CASE
                            WHEN (
                                intake.intake_bucket = 'intake_prospective_clients'
                            ) THEN 'Prospective Clients'
                            WHEN (
                                intake.intake_bucket = 'intake_schedule'
                            ) THEN 'Intake Scheduled'
                            WHEN (
                                intake.intake_bucket = 'intake_pending_soc'
                            ) THEN 'Pending Start of Care'
                            WHEN (
                                intake.intake_bucket = 'intake_client_starts'
                            ) THEN 'Client Starts'
                            WHEN (
                                intake.intake_bucket = 'intake_pending_non_admit'
                            ) THEN 'Pending Non Admit'
                            WHEN (
                                {$this->admittedConditionSql('smBillable', 'mssql_patients')}
                            ) THEN 'Admitted'
                            WHEN (
                                (
									{$this->nonAdmitConditionSql('mssql_patients')}
                                )
                            ) THEN 'Non Admit'
                            END
                    ";
                    $rawFieldMap = [
                        'Branch_Location' => 'NULLIF(referralsource_master.name, \'\')',
                        'BranchName' => 'NULLIF(mssql_facilities.BranchName, \'\')',
                        'BD_Rep' => 'NULLIF(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.BD_Rep, intake.BD_Rep), \'\')',
                        'IntakeStatus' => $intakeStatus,
						'referral_status' => $intakeStatus,
                    ];
                    $query->where(function ($q) use ($rowFilter, $fieldmap, $form, $rawFieldMap) {
                        foreach ($rowFilter as $rfkey => $rf) {
                            $rfkey = isset($fieldmap[$rfkey]) ? $fieldmap[$rfkey] : $rfkey;
                            if ($rfkey == 'start_of_week') {
                                if ($form[2]['value'] == 'month') {
                                    $q->whereRaw('MONTH(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate)) = Month(CONCAT(?,"-01")) AND
                                    YEAR(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate)) = YEAR(CONCAT(?,"-01"))', [$rf, $rf]);
                                } else if ($form[2]['value'] == 'quarter') {
                                    $q->whereRaw('QUARTER(IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate)) = QUARTER(CONCAT(?,"-01"))', [$rf]);
                                } else {
                                    $q->whereRaw('IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) >= ? AND
                                    IF(intake.LOBID = mssql_patients.LOBID, mssql_patients.ReferralDate, intake.ReferralDate) < (? + INTERVAL 7 - weekday(?) DAY)', [
                                        $rf,
                                        $rf,
                                        $rf
                                    ]);
                                }
                            } else {
                                $rowKey = isset($rawFieldMap[$rfkey]) ? $rawFieldMap[$rfkey] : '';
                                if (!empty($rowKey)) {
                                    if (empty($rf)) {
                                        $q->whereRaw($rowKey . " IS NULL ");
                                    } else {
                                        $q->whereRaw($rowKey . " = ? ", [$rf]);
                                    }
                                } else {
                                    if (is_array($rf)) {
                                        $q->whereIn($rfkey, $rf);
                                    } else {
                                        $q->where($rfkey, "=", $rf);
                                    }
                                }
                            }
                        }
                    });
                    //$query->groupBy('mssql_patients.id');
                    $query->orderBy('intake.created_at', 'DESC');
                }
            }

            if (LD::checkFeatureFlag('national-intake-add-lead-filter') && $query) {
                if ($isLeadOnly) {
                    $query->whereRaw('IFNULL(intake.intake_lead_exclude, 0) = 1');
                } else {
                    $query->whereRaw('IFNULL(intake.intake_lead_exclude, 0) = 0');
                }
            }

            H::ExcelLog(print_r($query->toSql(), true), 'IntakeReportPop');
            H::ExcelLog(print_r($query->getBindings(), true), 'IntakeReportPop');
            if ($query) {
                $result = $query->get()->toArray();
            }
        }

        return view('reports.popup', ['data' => $request->all(), "results" => count($result) > 0 ? $result : []]);
    }

    public function referrals()
    {
        $period = 'week';
        if (isset(request()->period)) {
            $period = request()->period;
        }

        $source = 'facility';
        if(isset(request()->source)) {
            $source = request()->source;
        }

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Referrals';
        $report['description'] = '';
        $report['id'] = 'referrals';
        $report['period'] = $period;
        $report['goalField'] = 'candidate_start_hour_goals';
        //columns defines the fieldname, heading, optional format, width and style for each column
        if ($source == 'bdr') {
            $report['columns'] = [
                [
                    'field' => 'BD_Rep',
                    'heading' => [
                        'title' => 'Business Development Rep',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Facility',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else if ($source == 'state') {
            $report['columns'] = [
                [
                    'field' => 'state',
                    'heading' => [
                        'title' => 'State',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'region',
                    'heading' => [
                        'title' => 'Region',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'BranchName',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else {
            $report['columns'] = [
                [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Facility',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'BranchName',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('period:' . $report['period'],'intakereport.log');
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = request()->date['to'];
            $count = 0;
            if($report['period'] == 'quarter') {
                $count = H::datediffInQuarters($sdate, $tdate);
            }
            if($report['period'] == 'week') {
                $count = H::datediffInWeeks($sdate, $tdate);
            }
            if($report['period'] == 'month') {
                $count = H::monthDiff($sdate, $tdate);
            }
            if($report['period'] == 'day') {
                //add a day because we don't really want the difference, we want the number of days in the range
                $count = abs(round((strtotime($tdate) - strtotime($sdate)) / 86400)) + 1;
            }
            $periods = H::getReportPeriodColumns($report['period'], $count, request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;

        $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
        if ($selectedDatesDiffInWeek >= 13) {
            $showAvgColumn = 1;
        }
        H::ExcelLog("selectedDatesDiffInWeek==" . $selectedDatesDiffInWeek, 'intakereport.log');
        H::ExcelLog("showAvgColumn==" . $showAvgColumn, 'intakereport.log');

        foreach ($periods as $period) {
            $clickedFilters = [];
            if ($source == 'bdr') {
                $clickedFilters = [
                    'BD_Rep',
                    'Branch_Location',
                ];
            } else if ($source == 'state') {
                $clickedFilters = [
                    'state_id',
                    'region_id',
                    'BranchName'
                ];
            } else {
                $clickedFilters = [
                    'Branch_Location',
                    'BranchName'
                ];
            }
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => [
                    'title' => $period['label'],
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => 'sum',
                    'attributes' => '',
                    'style' => ''
                ],
                'clickable' => [
                    'jsfunction' => 'openNationalClientCareReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name']
                    ]
                ],
            ];
        }

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => [
                'title' => 'Total',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                'attributes' => '',
                'style' => ''
            ],
        ];
        //rowStyle is any special style to apply to the <tr> tags for the data rows
        $report['rowStyle'] = '';
        /**
         * define the fields to group by for subtotals.  I set it up to allow up to 4 groupings.  The last one with id = 9 is for the grand totals.
         * leave 'field' empty for ones you do not need.  It will ignore it.
         * do not remove any or change the id values!!!!
         * make sure your query sorts the data in the same order as the grouping are defined!!!
         * it prints the subtotal row when the data in column 'field' changes
         * style = the style to apply to the <tr> tag
         * label = what to display before the grouping value use @group3 @group2 to insert the value of the grouping fields (number = groupID)
         *
         * Define groups with the lowest level first
         *
         */
        if ($source == 'bdr') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'Branch_Location',
                    'showCols' => 'Branch_Location',
                    'style' => self::BACKGROUND3,
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'BD_Rep',
                    'showCols' => 'BD_Rep',
                    'style' => self::BACKGROUND1,
                    'level' => 2,
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else if ($source == 'state') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'region',
                    'showCols' => 'region',
                    'style' => self::BACKGROUND4,
                    'level' => 1,
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'state',
                    'showCols' => 'state',
                    'style' => self::BACKGROUND1,
                    'level' => 2
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'BranchName',
                    'showCols' => 'BranchName',
                    'style' => self::BACKGROUND3,
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'Branch_Location',
                    'showCols' => 'Branch_Location',
                    'style' => self::BACKGROUND1,
                    'level' => 2,
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        }
        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches = [];
        foreach ($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'], 'intakereport.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2', 'intakereport.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        $regions = H::getHelper("ConstantHelper")->getRegion();
        $lobCollection = H::getHelper('ConstantHelper')->getLob();
        $loboption = [];
        foreach($lobCollection as $lob) {
            $loboption[] = ['value' => $lob->LOBID, 'text' => $lob->LOBName];
        }
        $filters = [
            [
                'label' => 'State',
                'name' => 'state',
                'type' => 'multiselect',
                'source' => $state
            ],
            [
                'label' => 'Region',
                'name' => 'region',
                'type' => 'select',
                'source' => $regions,
                'disabled' => true
            ],
            [
                'label' => 'Branch',
                'name' => 'aoc_facility_id',
                'type' => 'multiselect',
                'source' => $branches
            ],
            [
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'multiselect',
                'source' => $loboption
            ],
            [
                'label' => 'Intake Coordinator',
                'name' => 'intakecoordinator',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getIntakeCoordinator()
            ],
            [
                'label' => 'BD Rep',
                'name' => 'bdrep',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getBdRep(),
            ],
            [
                'label'=>'Payer',
                'name'=>'payer',
                'type'=>'multiselect',
                'source'=>H::getHelper('ConstantHelper')->getPayers()
            ],
            [
                'label' => 'Payer Group',
                'name' => 'payer_group',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayerGroup()
            ],
            [
                'label' => 'Referral Status',
                'name' => 'referral_status',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getReferralStatus()
            ],
            [
                'label' => 'Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ],
        ];

        if (LD::checkFeatureFlag('national-intake-add-lead-filter')) {
            array_push($filters, [
                'label' => 'Show Leads Only',
                'name' => 'leadonly',
                'type' => 'checkbox',
                'value' => false
            ]);
        }
        $sourceFilters = [
            [
                'label' => 'Facility',
                'value' => 'facility'
            ],
            [
                'label' => 'Business Dev. Rep',
                'value' => 'bdr'
            ],
            [
                'label' => 'State',
                'value' => 'state'
            ]
        ];


        $isLeadOnly = 0;
        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'intakereport.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mp.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "mf.State";
                        } elseif ($field == 'intakecoordinator') {
                            $tmp = 'u.id';
                        } elseif ($field == 'payer') {
                            $tmp = 'mp.Payer';
                        } elseif ($field == 'payer_group') {
                            $tmp = 'mp.payer_group';
                        } elseif ($field == 'bdrep') {
                            $tmp = $this->normalizeBdRepSql('IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)');
                        } elseif ($field == 'lob') {
                            $tmp = 'ink.LOBID';
                            if ($value == '1023') {
                                $value = '';
                            }
                        } elseif ($field == 'referral_status') {
							$tmp = "
								CASE
									WHEN (
										ink.intake_bucket = 'intake_prospective_clients'
									) THEN 'Prospective Clients'
									WHEN (
										ink.intake_bucket = 'intake_schedule'
									) THEN 'Intake Scheduled'
									WHEN (
										ink.intake_bucket = 'intake_pending_soc'
									) THEN 'Pending Start of Care'
									WHEN (
										ink.intake_bucket = 'intake_client_starts'
									) THEN 'Client Starts'
                                    WHEN (
										ink.intake_bucket = 'intake_pending_non_admit'
									) THEN 'Pending Non Admit'
									WHEN (
										{$this->admittedConditionSql('smBillable', 'mp')}
									) THEN 'Admitted'
									WHEN (
										(
											{$this->nonAdmitConditionSql('mp')}
										)
									) THEN 'Non Admit'
									END
							";
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'intakereport.log');
                        if ($value != '') {
                            if ($field == 'region') {
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            } else if ($field == 'leadonly') {
                                $where[] = "IFNULL(ink.intake_lead_exclude, 0) = 1";
                                $isLeadOnly = 1;
                            }
                        }
                    }
                }
            }
        }

        if (LD::checkFeatureFlag('national-intake-add-lead-filter')) {
            if ($isLeadOnly == 0) {
                $where[] = "IFNULL(ink.intake_lead_exclude, 0) = 0";
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'IF(ink.LOBID = mp.LOBID, mp.ReferralDate, ink.ReferralDate)', true);
        //now get report data

        if ($source == 'bdr') {
            $bdRepNormalized = $this->normalizeBdRepSql('IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)');
            $select = "distinct(ink.id), refmstr.name as Branch_Location, MIN(IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)) as BD_Rep, $bdRepNormalized as BDRepKey";
            $sortGroupBy = " group by NULLIF($bdRepNormalized,''), NULLIF(refmstr.name,''), " . $periodQuery . "
            order by NULLIF($bdRepNormalized,''), NULLIF(refmstr.name,''), " . $periodQuery;
        } else if ($source == 'state') {
            $select = 'distinct(ink.id), mf.State as state, mf.BranchName as BranchName,
            reg.region_name,reg.region_name as region,mf.id as state_id, reg.id as region_id';
            $sortGroupBy = " group by NULLIF(mf.State, ''), NULLIF(region, ''), mp.aoc_facility_id, " . $periodQuery . "
            order by NULLIF(mf.State, ''), NULLIF(region, ''), mp.aoc_facility_id, " . $periodQuery;
        } else {
            $select = 'distinct(ink.id), refmstr.name as Branch_Location, mf.BranchName as BranchName';
            $sortGroupBy = " group by NULLIF(refmstr.name, ''), NULLIF(mf.BranchName, ''), " . $periodQuery . "
            order by NULLIF(refmstr.name, ''), NULLIF(mf.BranchName, ''), " . $periodQuery;
        }

        if (request()->view && request()->view  == 'hours') {
            $caseSql = $this->intakeService->getPatientHoursFieldCaseSql('ink.LOBID', 'mp');
            $count = "SUM($caseSql) as myCount";
        } else if (request()->view && request()->view == 'percentage') {
            $count = 'count(ink.id) as myCount';
        } else {
            $count = 'count(ink.id) as myCount';
        }

        $qry = "SELECT " . $select . ",
        " . $periodQuery . " as Monday, ".$count.", 0 as candidate_start_hour_goals
        FROM intake ink
		left join mssql_patients mp on mp.id = ink.aoc_patient_id
        left join users u on u.id = ink.ic_id
        left join mssql_facilities mf on mf.id = mp.aoc_facility_id
        left join regions reg on reg.id = mf.region
        left join referralsource_master refmstr on refmstr.source_id = ink.ReferralSourceID
		left join mssql_schedulemaster smBillable on smBillable.CGTaskID = ink.intake_first_billable_visit
        where IF(ink.LOBID = mp.LOBID, mp.ReferralDate, ink.ReferralDate) >= ? and
			IF(ink.LOBID = mp.LOBID, mp.ReferralDate, ink.ReferralDate) <= ?".
            (!empty($where) ? self::ANDCONDITION . implode(self::ANDCONDITION, $where) : '')  . $sortGroupBy;
        H::ExcelLog($qry, 'intakereport.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);
        $bdRepDisplayNames = $source === 'bdr' ? $this->intakeService->resolveBdRepDisplayNames($tmp) : [];
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if ($source == 'bdr') {
                $id = $record['BDRepKey'] . $record['Branch_Location'];
            } else if ($source == 'state') {
                $id = $record['state'] . $record['region'] . $record['BranchName'];
            } else {
                $id = $record['Branch_Location'] . $record['BranchName'];
            }
            if (!isset($data[$id])) {
                if ($source == 'bdr') {
                    $data[$id] = [
                        'Branch_Location' => $record['Branch_Location'],
                        'BD_Rep' => $bdRepDisplayNames[$record['BDRepKey']]
                    ];
                } else if ($source == 'state') {
                    $data[$id] = [
                        'state' => $record['state'],
                        'region' => $record['region'],
                        'BranchName' => $record['BranchName'],
                        'state_id' => $record['state_id'],
                        'region_id' => $record['region_id']
                    ];
                } else {
                    $data[$id] = [
                        'Branch_Location' => $record['Branch_Location'],
                        'BranchName' => $record['BranchName']
                    ];
                }
                //dynamically add fields for each period
                foreach ($periods as $period) {
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['SixWeekAvg'] = 0;
                $data[$id]['ThirteenWeekAvg'] = 0;
                $data[$id]['AvgTrend'] = 0;
                $data[$id]['Total'] = 0;
                $data[$id]['Count'] = 0;
            }

            if (request()->view && request()->view == 'percentage') {
                $total1 = (($record[$report['goalField']] > 0) ? (($record['myCount'] / $record[$report['goalField']]) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if (floatval($data[$id]['Total']) != 0) {
                    $data[$id]['Count']++;
                }
            } else {

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['myCount'];
                }
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $record['myCount'];
                }

                $data[$id][$record['Monday']] = $record['myCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
            }
        }

        if (request()->view && request()->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach ($test as $key => $value) {
                if ($value['Count'] != 0) {
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'], 0);
                } else {
                    $data[$key]['Total'] = 0;
                }
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.intakeReports', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => 'branch'
        ]);
    }

    public function nonadmit()
    {
        $period = 'week';
        if (isset(request()->period)) {
            $period = request()->period;
        }

        $source = 'facility';
        if(isset(request()->source)) {
            $source = request()->source;
        }

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Non Admits';
        $report['description'] = '';
        $report['id'] = 'nonadmit';
        $report['period'] = $period;
        $report['goalField'] = 'candidate_start_hour_goals';
        //columns defines the fieldname, heading, optional format, width and style for each column
        if ($source == 'bdr') {
            $report['columns'] = [
                [
                    'field' => 'BD_Rep',
                    'heading' => [
                        'title' => 'Business Development Rep',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Facility',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else if ($source == 'state') {
            $report['columns'] = [
                [
                    'field' => 'state',
                    'heading' => [
                        'title' => 'State',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'region',
                    'heading' => [
                        'title' => 'Region',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'BranchName',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else if ($source == 'reason') {
            $report['columns'] = [
                [
                    'field' => 'reason',
                    'heading' => [
                        'title' => 'Reason',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else {
            $report['columns'] = [
                [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Facility',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'BranchName',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('period:' . $report['period'],'intakereport.log');
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = request()->date['to'];
            $count = 0;
            if($report['period'] == 'quarter') {
                $count = H::datediffInQuarters($sdate, $tdate);
            }
            if($report['period'] == 'week') {
                $count = H::datediffInWeeks($sdate, $tdate);
            }
            if($report['period'] == 'month') {
                $count = H::monthDiff($sdate, $tdate);
            }
            if($report['period'] == 'day') {
                //add a day because we don't really want the difference, we want the number of days in the range
                $count = abs(round((strtotime($tdate) - strtotime($sdate)) / 86400)) + 1;
            }
            $periods = H::getReportPeriodColumns($report['period'], $count, request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;

        $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
        if ($selectedDatesDiffInWeek >= 13) {
            $showAvgColumn = 1;
        }
        H::ExcelLog("selectedDatesDiffInWeek==" . $selectedDatesDiffInWeek, 'intakereport.log');
        H::ExcelLog("showAvgColumn==" . $showAvgColumn, 'intakereport.log');

        foreach ($periods as $period) {
            $clickedFilters = [];
            if ($source == 'bdr') {
                $clickedFilters = [
                    'BD_Rep',
                    'Branch_Location',
                ];
            } else if ($source == 'state') {
                $clickedFilters = [
                    'state_id',
                    'region_id',
                    'BranchName'
                ];
            } else if ($source == 'reason') {
                $clickedFilters = [
                    'reason'
                ];
            } else {
                $clickedFilters = [
                    'Branch_Location',
                    'BranchName'
                ];
            }
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => [
                    'title' => $period['label'],
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => 'sum',
                    'attributes' => '',
                    'style' => ''
                ],
                'clickable' => [
                    'jsfunction' => 'openNationalClientCareReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name']
                    ]
                ],
            ];
        }

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => [
                'title' => 'Total',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                'attributes' => '',
                'style' => ''
            ],
        ];
        //rowStyle is any special style to apply to the <tr> tags for the data rows
        $report['rowStyle'] = '';
        /**
         * define the fields to group by for subtotals.  I set it up to allow up to 4 groupings.  The last one with id = 9 is for the grand totals.
         * leave 'field' empty for ones you do not need.  It will ignore it.
         * do not remove any or change the id values!!!!
         * make sure your query sorts the data in the same order as the grouping are defined!!!
         * it prints the subtotal row when the data in column 'field' changes
         * style = the style to apply to the <tr> tag
         * label = what to display before the grouping value use @group3 @group2 to insert the value of the grouping fields (number = groupID)
         *
         * Define groups with the lowest level first
         *
         */
        if ($source == 'bdr') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'Branch_Location',
                    'showCols' => 'Branch_Location',
                    'style' => self::BACKGROUND3,
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'BD_Rep',
                    'showCols' => 'BD_Rep',
                    'style' => self::BACKGROUND1,
                    'level' => 2,
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else if ($source == 'state') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'region',
                    'showCols' => 'region',
                    'style' => self::BACKGROUND4,
                    'level' => 1,
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'state',
                    'showCols' => 'state',
                    'style' => self::BACKGROUND1,
                    'level' => 2
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else if ($source == 'reason') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'reason',
                    'showCols' => 'reason',
                    'style' => self::BACKGROUND1,
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 2,
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'BranchName',
                    'showCols' => 'BranchName',
                    'style' => self::BACKGROUND3,
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'Branch_Location',
                    'showCols' => 'Branch_Location',
                    'style' => self::BACKGROUND1,
                    'level' => 2,
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        }
        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches = [];
        foreach ($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'], 'intakereport.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2', 'intakereport.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        $regions = H::getHelper("ConstantHelper")->getRegion();
        $lobCollection = H::getHelper('ConstantHelper')->getLob();
        $loboption = [];
        foreach($lobCollection as $lob) {
            $loboption[] = ['value' => $lob->LOBID, 'text' => $lob->LOBName];
        }
        $filters = [
            [
                'label' => 'State',
                'name' => 'state',
                'type' => 'multiselect',
                'source' => $state
            ],
            [
                'label' => 'Region',
                'name' => 'region',
                'type' => 'select',
                'source' => $regions,
                'disabled' => true
            ],
            [
                'label' => 'Branch',
                'name' => 'aoc_facility_id',
                'type' => 'multiselect',
                'source' => $branches
            ],
            [
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'multiselect',
                'source' => $loboption
            ],
            [
                'label' => 'Intake Coordinator',
                'name' => 'intakecoordinator',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getIntakeCoordinator()
            ],
            [
                'label' => 'BD Rep',
                'name' => 'bdrep',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getBdRep(),
            ],
            [
                'label'=>'Payer',
                'name'=>'payer',
                'type'=>'multiselect',
                'source'=>H::getHelper('ConstantHelper')->getPayers()
            ],
            [
                'label' => 'Payer Group',
                'name' => 'payer_group',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayerGroup()
            ],
            [
                'label' => 'Reason',
                'name' => 'reason',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getNonAdmitReason()
            ],
            [
                'label' => 'Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ],
        ];

        if (LD::checkFeatureFlag('national-intake-add-lead-filter')) {
            array_push($filters, [
                'label' => 'Show Leads Only',
                'name' => 'leadonly',
                'type' => 'checkbox',
                'value' => false
            ]);
        }

        $sourceFilters = [
            [
                'label' => 'Facility',
                'value' => 'facility'
            ],
            [
                'label' => 'Business Dev. Rep',
                'value' => 'bdr'
            ],
            [
                'label' => 'State',
                'value' => 'state'
            ],
            [
                'label' => 'Reason',
                'value' => 'reason'
            ]
        ];

        $isLeadOnly = 0;
        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'intakereport.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mp.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "mf.State";
                        } elseif ($field == 'intakecoordinator') {
                            $tmp = 'u.id';
                        } elseif ($field == 'payer') {
                            $tmp = 'mp.Payer';
                        } elseif ($field == 'payer_group') {
                            $tmp = 'mp.payer_group';
                        } elseif ($field == 'bdrep') {
                            $tmp = $this->normalizeBdRepSql('IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)');
                        } elseif ($field == 'lob') {
                            $tmp = 'ink.LOBID';
                            if ($value == '1023') {
                                $value = '';
                            }
                        } elseif ($field == 'reason') {
                            $tmp = 'ink.intake_nonadmit_reason';
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'intakereport.log');
                        if ($value != '') {
                            if ($field == 'region') {
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            } else if ($field == 'leadonly') {
                                $where[] = "IFNULL(ink.intake_lead_exclude, 0) = 1";
                                $isLeadOnly = 1;
                            }
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'mp.NonAdmitDate', true);
        //now get report data

        if (LD::checkFeatureFlag('national-intake-add-lead-filter')) {
            if ($isLeadOnly == 0) {
                $where[] = "IFNULL(ink.intake_lead_exclude, 0) = 0";
            }
        }

        if ($source == 'bdr') {
            $bdRepNormalized = $this->normalizeBdRepSql('IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)');
            $select = "refmstr.name as Branch_Location, MIN(IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)) as BD_Rep, $bdRepNormalized as BDRepKey";
            $sortGroupBy = " group by NULLIF($bdRepNormalized, ''), NULLIF(refmstr.name, '')," . $periodQuery . "
            order by NULLIF($bdRepNormalized, ''), NULLIF(refmstr.name, '')," . $periodQuery;
        } else if ($source == 'state') {
            $select = 'mf.State as state, mf.BranchName as BranchName,
            reg.region_name,reg.region_name as region,mf.id as state_id, reg.id as region_id';
            $sortGroupBy = " group by NULLIF(mf.State, ''), NULLIF(region, ''), mp.aoc_facility_id, " . $periodQuery . "
            order by NULLIF(mf.State, ''), NULLIF(region, ''), mp.aoc_facility_id, " . $periodQuery;
        } else if ($source == 'reason') {
            $select = 'ink.intake_nonadmit_reason as reason';
            $sortGroupBy = " group by NULLIF(ink.intake_nonadmit_reason, '')," . $periodQuery . "
            order by NULLIF(ink.intake_nonadmit_reason, '')," . $periodQuery;
        } else {
            $select = 'refmstr.name as Branch_Location, mf.BranchName as BranchName';
            $sortGroupBy = " group by NULLIF(refmstr.name, ''), NULLIF(mf.BranchName, ''), " . $periodQuery . "
            order by NULLIF(refmstr.name, ''), NULLIF(mf.BranchName, ''), " . $periodQuery;
        }

        if (request()->view && request()->view  == 'hours') {
            $caseSql = $this->intakeService->getPatientHoursFieldCaseSql('ink.LOBID', 'mp');
            $count = "SUM($caseSql) as myCount";
        } else if (request()->view && request()->view == 'percentage') {
            $count = 'count(ink.id) as myCount';
        } else {
            $count = 'count(ink.id) as myCount';
        }

        $qry = "SELECT " . $select . ",
        " . $periodQuery . " as Monday, ".$count.", 0 as candidate_start_hour_goals
        FROM intake ink
		left join mssql_patients mp on mp.id = ink.aoc_patient_id
        left join users u on u.id = ink.ic_id
        left join mssql_facilities mf on mf.id = mp.aoc_facility_id
        left join regions reg on reg.id = mf.region
        left join referralsource_master refmstr on refmstr.source_id = mp.ReferralSourceID
        where mp.NonAdmitDate >= ? and mp.NonAdmitDate <= ? AND (ink.intake_outcome = 'Non Admit'
        OR mp.Patient_CurrentStatusID = 3)" .
            (!empty($where) ? self::ANDCONDITION . implode(self::ANDCONDITION, $where) : '')  . $sortGroupBy;

        H::ExcelLog($qry, 'intakereport.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);
        $bdRepDisplayNames = $source === 'bdr' ? $this->intakeService->resolveBdRepDisplayNames($tmp) : [];
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if ($source == 'bdr') {
                $id = $record['BDRepKey'] . $record['Branch_Location'];
            } else if ($source == 'state') {
                $id = $record['state'] . $record['region'] . $record['BranchName'];
            } else if ($source == 'reason') {
                $id = $record['reason'];
            } else {
                $id = $record['Branch_Location'] . $record['BranchName'];
            }
            if (!isset($data[$id])) {
                if ($source == 'bdr') {
                    $data[$id] = [
                        'Branch_Location' => $record['Branch_Location'],
                        'BD_Rep' => $bdRepDisplayNames[$record['BDRepKey']]
                    ];
                } else if ($source == 'state') {
                    $data[$id] = [
                        'state' => $record['state'],
                        'region' => $record['region'],
                        'BranchName' => $record['BranchName'],
                        'state_id' => $record['state_id'],
                        'region_id' => $record['region_id']
                    ];
                } else if ($source == 'reason') {
                    $data[$id] = [
                        'reason' => $record['reason']
                    ];
                } else {
                    $data[$id] = [
                        'Branch_Location' => $record['Branch_Location'],
                        'BranchName' => $record['BranchName']
                    ];
                }
                //dynamically add fields for each period
                foreach ($periods as $period) {
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['SixWeekAvg'] = 0;
                $data[$id]['ThirteenWeekAvg'] = 0;
                $data[$id]['AvgTrend'] = 0;
                $data[$id]['Total'] = 0;
                $data[$id]['Count'] = 0;
            }

            if (request()->view && request()->view == 'percentage') {
                $total1 = (($record[$report['goalField']] > 0) ? (($record['myCount'] / $record[$report['goalField']]) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if (floatval($data[$id]['Total']) != 0) {
                    $data[$id]['Count']++;
                }
            } else {

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['myCount'];
                }
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $record['myCount'];
                }

                $data[$id][$record['Monday']] = $record['myCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
            }
        }

        if (request()->view && request()->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach ($test as $key => $value) {
                if ($value['Count'] != 0) {
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'], 0);
                } else {
                    $data[$key]['Total'] = 0;
                }
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.intakeReports', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => 'branch'
        ]);
    }

    public function admits()
    {
        $period = 'week';
        if (isset(request()->period)) {
            $period = request()->period;
        }

        $source = 'facility';
        if(isset(request()->source)) {
            $source = request()->source;
        }

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Admits';
        $report['description'] = '';
        $report['id'] = 'admits';
        $report['period'] = $period;
        $report['goalField'] = 'candidate_start_hour_goals';
        //columns defines the fieldname, heading, optional format, width and style for each column
        if ($source == 'bdr') {
            $report['columns'] = [
                [
                    'field' => 'BD_Rep',
                    'heading' => [
                        'title' => 'Business Development Rep',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Facility',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else if ($source == 'state') {
            $report['columns'] = [
                [
                    'field' => 'state',
                    'heading' => [
                        'title' => 'State',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'region',
                    'heading' => [
                        'title' => 'Region',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'BranchName',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else {
            $report['columns'] = [
                [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Facility',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'BranchName',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('period:' . $report['period'],'intakereport.log');
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = request()->date['to'];
            $count = 0;
            if($report['period'] == 'quarter') {
                $count = H::datediffInQuarters($sdate, $tdate);
            }
            if($report['period'] == 'week') {
                $count = H::datediffInWeeks($sdate, $tdate);
            }
            if($report['period'] == 'month') {
                $count = H::monthDiff($sdate, $tdate);
            }
            if($report['period'] == 'day') {
                //add a day because we don't really want the difference, we want the number of days in the range
                $count = abs(round((strtotime($tdate) - strtotime($sdate)) / 86400)) + 1;
            }
            $periods = H::getReportPeriodColumns($report['period'], $count, request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;

        $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
        if ($selectedDatesDiffInWeek >= 13) {
            $showAvgColumn = 1;
        }
        H::ExcelLog("selectedDatesDiffInWeek==" . $selectedDatesDiffInWeek, 'intakereport.log');
        H::ExcelLog("showAvgColumn==" . $showAvgColumn, 'intakereport.log');

        foreach ($periods as $period) {
            $clickedFilters = [];
            if ($source == 'bdr') {
                $clickedFilters = [
                    'BD_Rep',
                    'Branch_Location',
                ];
            } else if ($source == 'state') {
                $clickedFilters = [
                    'state_id',
                    'region_id',
                    'BranchName'
                ];
            } else {
                $clickedFilters = [
                    'Branch_Location',
                    'BranchName'
                ];
            }
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => [
                    'title' => $period['label'],
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => 'sum',
                    'attributes' => '',
                    'style' => ''
                ],
                'clickable' => [
                    'jsfunction' => 'openNationalClientCareReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name']
                    ]
                ],
            ];
        }

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => [
                'title' => 'Total',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'pct' : '1decimal'),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => ((request()->view && request()->view == 'percentage') ? 'avg' : 'sum'),
                'attributes' => '',
                'style' => ''
            ],
        ];
        //rowStyle is any special style to apply to the <tr> tags for the data rows
        $report['rowStyle'] = '';
        /**
         * define the fields to group by for subtotals.  I set it up to allow up to 4 groupings.  The last one with id = 9 is for the grand totals.
         * leave 'field' empty for ones you do not need.  It will ignore it.
         * do not remove any or change the id values!!!!
         * make sure your query sorts the data in the same order as the grouping are defined!!!
         * it prints the subtotal row when the data in column 'field' changes
         * style = the style to apply to the <tr> tag
         * label = what to display before the grouping value use @group3 @group2 to insert the value of the grouping fields (number = groupID)
         *
         * Define groups with the lowest level first
         *
         */
        if ($source == 'bdr') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'Branch_Location',
                    'showCols' => 'Branch_Location',
                    'style' => self::BACKGROUND3,
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'BD_Rep',
                    'showCols' => 'BD_Rep',
                    'style' => self::BACKGROUND1,
                    'level' => 2,
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else if ($source == 'state') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'region',
                    'showCols' => 'region',
                    'style' => self::BACKGROUND4,
                    'level' => 1,
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'state',
                    'showCols' => 'state',
                    'style' => self::BACKGROUND1,
                    'level' => 2
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'BranchName',
                    'showCols' => 'BranchName',
                    'style' => self::BACKGROUND3,
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '@group2',
                    'field' => 'Branch_Location',
                    'showCols' => 'Branch_Location',
                    'style' => self::BACKGROUND1,
                    'level' => 2,
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        }
        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches = [];
        foreach ($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'], 'intakereport.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2', 'intakereport.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        $regions = H::getHelper("ConstantHelper")->getRegion();
        $lobCollection = H::getHelper('ConstantHelper')->getLob();
        $loboption = [];
        foreach ($lobCollection as $lob) {
            $loboption[] = ['value' => $lob->LOBID, 'text' => $lob->LOBName];
        }
        $filters = [
            [
                'label' => 'State',
                'name' => 'state',
                'type' => 'multiselect',
                'source' => $state
            ],
            [
                'label' => 'Region',
                'name' => 'region',
                'type' => 'select',
                'source' => $regions,
                'disabled' => true
            ],
            [
                'label' => 'Branch',
                'name' => 'aoc_facility_id',
                'type' => 'multiselect',
                'source' => $branches
            ],
            [
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'multiselect',
                'source' => $loboption
            ],
            [
                'label' => 'Intake Coordinator',
                'name' => 'intakecoordinator',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getIntakeCoordinator()
            ],
            [
                'label' => 'BD Rep',
                'name' => 'bdrep',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getBdRep(),
            ],
            [
                'label'=>'Payer',
                'name'=>'payer',
                'type'=>'multiselect',
                'source'=>H::getHelper('ConstantHelper')->getPayers()
            ],
            [
                'label' => 'Payer Group',
                'name' => 'payer_group',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayerGroup()
            ],
            [
                'label' => 'Referral Status',
                'name' => 'referral_status',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getReferralStatus()
            ],
            [
                'label' => 'Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ],
        ];

        if (LD::checkFeatureFlag('national-intake-add-lead-filter')) {
            array_push($filters, [
                'label' => 'Show Leads Only',
                'name' => 'leadonly',
                'type' => 'checkbox',
                'value' => false
            ]);
        }

        $sourceFilters = [
            [
                'label' => 'Facility',
                'value' => 'facility'
            ],
            [
                'label' => 'Business Dev. Rep',
                'value' => 'bdr'
            ],
            [
                'label' => 'State',
                'value' => 'state'
            ]
        ];


        $isLeadOnly = 0;
        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'intakereport.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mp.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "mf.State";
                        } elseif ($field == 'intakecoordinator') {
                            $tmp = 'u.id';
                        } elseif ($field == 'payer') {
                            $tmp = 'mp.Payer';
                        } elseif ($field == 'payer_group') {
                            $tmp = 'mp.payer_group';
                        } elseif ($field == 'bdrep') {
                            $tmp = $this->normalizeBdRepSql('IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)');
                        } elseif ($field == 'lob') {
                            $tmp = 'ink.LOBID';
                            if ($value == '1023') {
                                $value = '';
                            }
                        } elseif ($field == 'referral_status') {
                            $tmp = "(CASE
								WHEN (
									ink.intake_bucket = 'intake_prospective_clients'
								) THEN 'Prospective Clients'
								WHEN (
									ink.intake_bucket = 'intake_schedule'
								) THEN 'Intake Scheduled'
								WHEN (
									ink.intake_bucket = 'intake_pending_soc'
								) THEN 'Pending Start of Care'
								WHEN (
									ink.intake_bucket = 'intake_client_starts'
								) THEN 'Client Starts'
                                WHEN (
										ink.intake_bucket = 'intake_pending_non_admit'
									) THEN 'Pending Non Admit'
								WHEN (
									(
										ink.intake_bucket = 'intake_pending_non_admit' ||
										mp.Patient_CurrentStatusID = 3
									)
								) THEN 'Pending Non Admit'
								END)";
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'intakereport.log');
                        if ($value != '') {
                            if ($field == 'region') {
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            } else if ($field == 'leadonly') {
                                $where[] = "IFNULL(ink.intake_lead_exclude, 0) = 1";
                                $isLeadOnly = 1;
                            }
                        }
                    }
                }
            }
        }

        //fall back to mp.SOCDate when the admission visit was never synced into mssql_schedulemaster (PD-5144)
        $admitDateField = "COALESCE(mssql_schedulemaster.ScheduleDate, mp.SOCDate)";
        $periodQuery = H::getReportPeriodDefinition($report['period'], $admitDateField, true);
        //now get report data

        if (LD::checkFeatureFlag('national-intake-add-lead-filter')) {
            if ($isLeadOnly == 0) {
                $where[] = "IFNULL(ink.intake_lead_exclude, 0) = 0";
            }
        }

        if ($source == 'bdr') {
            $bdRepNormalized = $this->normalizeBdRepSql('IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)');
            $select = "distinct(ink.id), refmstr.name as Branch_Location, MIN(IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)) as BD_Rep, $bdRepNormalized as BDRepKey";
            $sortGroupBy = " group by NULLIF($bdRepNormalized,''), NULLIF(refmstr.name,''), " . $periodQuery . "
            order by NULLIF($bdRepNormalized,''), NULLIF(refmstr.name,''), " . $periodQuery;
        } else if ($source == 'state') {
            $select = 'distinct(ink.id), mf.State as state, mf.BranchName as BranchName,
            reg.region_name,reg.region_name as region,mf.id as state_id, reg.id as region_id';
            $sortGroupBy = " group by NULLIF(mf.State, ''), NULLIF(region, ''), mp.aoc_facility_id, " . $periodQuery . "
            order by NULLIF(mf.State, ''), NULLIF(region, ''), mp.aoc_facility_id, " . $periodQuery;
        } else {
            $select = 'distinct(ink.id), refmstr.name as Branch_Location, mf.BranchName as BranchName';
            $sortGroupBy = " group by NULLIF(refmstr.name, ''), NULLIF(mf.BranchName, ''), " . $periodQuery . "
            order by NULLIF(refmstr.name, ''), NULLIF(mf.BranchName, ''), " . $periodQuery;
        }

        if (request()->view && request()->view  == 'hours') {
            $caseSql = $this->intakeService->getPatientHoursFieldCaseSql('ink.LOBID', 'mp');
            $count = "SUM($caseSql) as myCount";
        } else if (request()->view && request()->view == 'percentage') {
            $count = 'count(ink.id) as myCount';
        } else {
            $count = 'count(ink.id) as myCount';
        }

        $qry = "SELECT " . $select . ",
        " . $periodQuery . " as Monday, ".$count.", 0 as candidate_start_hour_goals
        FROM intake ink
        left join mssql_patients mp on mp.id = ink.aoc_patient_id
        left join users u on u.id = ink.ic_id
        left join mssql_facilities mf on mf.id = mp.aoc_facility_id
        left join regions reg on reg.id = mf.region
        left join referralsource_master refmstr on refmstr.source_id = ink.ReferralSourceID
		left join mssql_schedulemaster on mssql_schedulemaster.CGTaskID = ink.intake_admission_visit
        where (ink.intake_admission_visit > 0 OR mp.SOCDate is not null)
        AND $admitDateField >= ?
        AND $admitDateField <= ?
        AND ifnull(mp.IsNonAdmit,0) = 0" .
        //((LD::checkFeatureFlag('client-new-changes')) ? " AND (mp.IsLatestAdmit = 1 OR IntakeID IS NULL) " : "") .
            (!empty($where) ? self::ANDCONDITION . implode(self::ANDCONDITION, $where) : '')  . $sortGroupBy;

        H::ExcelLog($qry, 'intakereport.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);
        $bdRepDisplayNames = $source === 'bdr' ? $this->intakeService->resolveBdRepDisplayNames($tmp) : [];
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if ($source == 'bdr') {
                $id = $record['BDRepKey'] . $record['Branch_Location'];
            } else if ($source == 'state') {
                $id = $record['state'] . $record['region'] . $record['BranchName'];
            } else {
                $id = $record['Branch_Location'] . $record['BranchName'];
            }
            if (!isset($data[$id])) {
                if ($source == 'bdr') {
                    $data[$id] = [
                        'Branch_Location' => $record['Branch_Location'],
                        'BD_Rep' => $bdRepDisplayNames[$record['BDRepKey']]
                    ];
                } else if ($source == 'state') {
                    $data[$id] = [
                        'state' => $record['state'],
                        'region' => $record['region'],
                        'BranchName' => $record['BranchName'],
                        'state_id' => $record['state_id'],
                        'region_id' => $record['region_id']
                    ];
                } else {
                    $data[$id] = [
                        'Branch_Location' => $record['Branch_Location'],
                        'BranchName' => $record['BranchName']
                    ];
                }
                //dynamically add fields for each period
                foreach ($periods as $period) {
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['SixWeekAvg'] = 0;
                $data[$id]['ThirteenWeekAvg'] = 0;
                $data[$id]['AvgTrend'] = 0;
                $data[$id]['Total'] = 0;
                $data[$id]['Count'] = 0;
            }

            if (request()->view && request()->view == 'percentage') {
                $total1 = (($record[$report['goalField']] > 0) ? (($record['myCount'] / $record[$report['goalField']]) * 100) : 0);
                $total1 = is_int($total1) ? $total1 : round($total1);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if (floatval($data[$id]['Total']) != 0) {
                    $data[$id]['Count']++;
                }
            } else {

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['myCount'];
                }
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $record['myCount'];
                }

                $data[$id][$record['Monday']] = $record['myCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
            }
        }

        if (request()->view && request()->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach ($test as $key => $value) {
                if ($value['Count'] != 0) {
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'], 0);
                } else {
                    $data[$key]['Total'] = 0;
                }
            }
        }
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.intakeReports', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => 'branch'
        ]);
    }

    public function conversion()
    {
        $period = 'week';
        if (isset(request()->period)) {
            $period = request()->period;
        }

        $source = 'facility';
        if(isset(request()->source)) {
            $source = request()->source;
        }

        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            $date = [];
            $date['from'] = date('Y-m-d', strtotime(request()->date['from']));
            $date['to'] = date('Y-m-d', strtotime(request()->date['to']));
            request()->merge(['date' => $date]);
        }

        //define report settings
        $report = [];
        $data = [];
        $report['title'] = 'Conversion Report';
        $report['description'] = '';
        $report['id'] = 'conversion';
        $report['period'] = $period;
        $report['goalField'] = 'candidate_start_hour_goals';
        //columns defines the fieldname, heading, optional format, width and style for each column
        if ($source == 'bdr') {
            $report['columns'] = [
                [
                    'field' => 'BD_Rep',
                    'heading' => [
                        'title' => 'Business Development Rep',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Facility',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'IntakeStatus',
                    'heading' => [
                        'title' => 'Status',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else if ($source == 'state') {
            $report['columns'] = [
                [
                    'field' => 'state',
                    'heading' => [
                        'title' => 'State',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'region',
                    'heading' => [
                        'title' => 'Region',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'BranchName',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'IntakeStatus',
                    'heading' => [
                        'title' => 'Status',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else if ($source == 'status') {
            $report['columns'] = [
                [
                    'field' => 'IntakeStatus',
                    'heading' => [
                        'title' => 'Status',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        } else {
            $report['columns'] = [
                [
                    'field' => 'Branch_Location',
                    'heading' => [
                        'title' => 'Facility',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'BranchName',
                    'heading' => [
                        'title' => 'Branch',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => 'hide',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ],
                [
                    'field' => 'IntakeStatus',
                    'heading' => [
                        'title' => 'Status',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'cell' => [
                        'format' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                    'totals' => [
                        'formula' => '',
                        'attributes' => '',
                        'style' => ''
                    ],
                ]
            ];
        }
        //dynamically add columns for the time periods
        //first get the column names based on  then set the column definition for each
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('period:' . $report['period'],'intakereport.log');
            $sdate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $tdate = request()->date['to'];
            $count = 0;
            if($report['period'] == 'quarter') {
                $count = H::datediffInQuarters($sdate, $tdate);
            }
            if($report['period'] == 'week') {
                $count = H::datediffInWeeks($sdate, $tdate);
            }
            if($report['period'] == 'month') {
                $count = H::monthDiff($sdate, $tdate);
            }
            if($report['period'] == 'day') {
                //add a day because we don't really want the difference, we want the number of days in the range
                $count = abs(round((strtotime($tdate) - strtotime($sdate)) / 86400)) + 1;
            }
            $periods = H::getReportPeriodColumns($report['period'], $count, request()->date['to']);
        } else {
            $periods = H::getReportPeriodColumns($report['period']);
        }
        $showAvgColumn = 0;

        $selectedDatesDiffInWeek = H::datediffInWeeks($periods[count($periods) - 1]['name'], $periods[0]['name']);
        if ($selectedDatesDiffInWeek >= 13) {
            $showAvgColumn = 1;
        }
        H::ExcelLog("selectedDatesDiffInWeek==" . $selectedDatesDiffInWeek, 'intakereport.log');
        H::ExcelLog("showAvgColumn==" . $showAvgColumn, 'intakereport.log');

        foreach ($periods as $period) {
            $clickedFilters = [];
            if ($source == 'bdr') {
                $clickedFilters = [
                    'BD_Rep',
                    'Branch_Location',
                    'IntakeStatus'
                ];
            } else if ($source == 'state') {
                $clickedFilters = [
                    'state_id',
                    'region_id',
                    'BranchName',
                    'IntakeStatus'
                ];
            } else if ($source == 'status') {
                $clickedFilters = [
                    'IntakeStatus'
                ];
            } else {
                $clickedFilters = [
                    'Branch_Location',
                    'BranchName',
                    'IntakeStatus'
                ];
            }
            $report['columns'][] = [
                'field' => $period['name'],
                'heading' => [
                    'title' => $period['label'],
                    'attributes' => '',
                    'style' => ''
                ],
                'cell' => [
                    'format' => ((request()->view && request()->view == 'percentage') ? 'roundPct' : '1decimal'),
                    'attributes' => '',
                    'style' => ''
                ],
                'totals' => [
                    'formula' => 'sum',
                    'attributes' => '',
                    'style' => ''
                ],
                'clickable' => [
                    'jsfunction' => 'openNationalClientCareReport',
                    'clickedfilters' => $clickedFilters,
                    'defaultval' => [
                        'start_of_week' => $period['name']
                    ]
                ],
            ];
        }

        $report['columns'][] = [
            'field' => 'Total',
            'heading' => [
                'title' => 'Total',
                'attributes' => '',
                'style' => ''
            ],
            'cell' => [
                'format' => ((request()->view && request()->view == 'percentage') ? 'roundPct' : '1decimal'),
                'attributes' => '',
                'style' => ''
            ],
            'totals' => [
                'formula' => ((request()->view && request()->view == 'percentage') ? 'sum' : 'sum'),
                'attributes' => '',
                'style' => ''
            ],
        ];
        //rowStyle is any special style to apply to the <tr> tags for the data rows
        $report['rowStyle'] = '';
        /**
         * define the fields to group by for subtotals.  I set it up to allow up to 4 groupings.  The last one with id = 9 is for the grand totals.
         * leave 'field' empty for ones you do not need.  It will ignore it.
         * do not remove any or change the id values!!!!
         * make sure your query sorts the data in the same order as the grouping are defined!!!
         * it prints the subtotal row when the data in column 'field' changes
         * style = the style to apply to the <tr> tag
         * label = what to display before the grouping value use @group3 @group2 to insert the value of the grouping fields (number = groupID)
         *
         * Define groups with the lowest level first
         *
         */
        if ($source == 'bdr') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'IntakeStatus',
                    'showCols' => 'IntakeStatus',
                    'style' => '',
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '',
                    'field' => 'Branch_Location',
                    'showCols' => 'Branch_Location',
                    'style' => self::BACKGROUND3,
                    'level' => 2
                ],
                [
                    'id' => 3,
                    'label' => '@group3',
                    'field' => 'BD_Rep',
                    'showCols' => 'BD_Rep',
                    'style' => self::BACKGROUND1,
                    'level' => 3,
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else if ($source == 'state') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'region',
                    'showCols' => 'region',
                    'style' => self::BACKGROUND3,
                    'level' => 1,
                ],
                [
                    'id' => 2,
                    'label' => '',
                    'field' => 'BranchName',
                    'showCols' => 'BranchName',
                    'style' => self::BACKGROUND4,
                    'level' => 2
                ],
                [
                    'id' => 3,
                    'label' => '@group3',
                    'field' => 'state',
                    'showCols' => 'state',
                    'style' => self::BACKGROUND1,
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else if ($source == 'status') {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '@group1',
                    'field' => 'IntakeStatus',
                    'showCols' => 'IntakeStatus',
                    'style' => self::BACKGROUND1,
                    'level' => 1,
                ],
                [
                    'id' => 2,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 2
                ],
                [
                    'id' => 3,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 3
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        } else {
            $report['groupby'] = [
                [
                    'id' => 1,
                    'label' => '',
                    'field' => 'IntakeStatus',
                    'showCols' => 'IntakeStatus',
                    'style' => '',
                    'level' => 1
                ],
                [
                    'id' => 2,
                    'label' => '',
                    'field' => 'BranchName',
                    'showCols' => 'BranchName',
                    'style' => self::BACKGROUND3,
                    'level' => 2
                ],
                [
                    'id' => 3,
                    'label' => '@group3',
                    'field' => 'Branch_Location',
                    'showCols' => 'Branch_Location',
                    'style' => self::BACKGROUND1,
                    'level' => 3,
                ],
                [
                    'id' => 4,
                    'label' => '',
                    'field' => '',
                    'showCols' => '',
                    'style' => '',
                    'level' => 4
                ],
                [
                    'id' => 9,
                    'label' => self::NATIONALTOTAL,
                    'field' => '',
                    'showCols' => '',
                    'style' => self::BACKGROUND2,
                    'level' => 5
                ],
            ];
        }
        $state = H::getHelper("ConstantHelper")->getDashboardState();
        $branchObject = H::getHelper("ConstantHelper")->getFacilities();
        $branches = [];
        foreach ($branchObject as $br) {
            $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
        }
        //set date ranges
        if (isset(request()->date) && !empty(request()->date['from']) && !empty(request()->date['to'])) {
            H::ExcelLog('here1: ' . $report['period'] . ' - ' . request()->date['to'], 'intakereport.log');
            $startDate = H::getReportStartingDate($report['period'], 0, request()->date['from']);
            $endingDate = request()->date['to'];
        } else {
            H::ExcelLog('here2', 'intakereport.log');
            $startDate = H::getReportStartingDate($report['period']);
            $endingDate = H::getReportEndingDate($report['period']);
        }
        $regions = H::getHelper("ConstantHelper")->getRegion();
        $lobCollection = H::getHelper('ConstantHelper')->getLob();
        $loboption = [];
        foreach($lobCollection as $lob) {
            $loboption[] = ['value' => $lob->LOBID, 'text' => $lob->LOBName];
        }
        $filters = [
            [
                'label' => 'State',
                'name' => 'state',
                'type' => 'multiselect',
                'source' => $state
            ],
            [
                'label' => 'Region',
                'name' => 'region',
                'type' => 'select',
                'source' => $regions,
                'disabled' => true
            ],
            [
                'label' => 'Branch',
                'name' => 'aoc_facility_id',
                'type' => 'multiselect',
                'source' => $branches
            ],
            [
                'label' => 'LOB',
                'name' => 'lob',
                'type' => 'multiselect',
                'source' => $loboption
            ],
            [
                'label' => 'Intake Coordinator',
                'name' => 'intakecoordinator',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getIntakeCoordinator()
            ],
            [
                'label' => 'BD Rep',
                'name' => 'bdrep',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getBdRep(),
            ],
            [
                'label'=>'Payer',
                'name'=>'payer',
                'type'=>'multiselect',
                'source'=>H::getHelper('ConstantHelper')->getPayers()
            ],
            [
                'label' => 'Payer Group',
                'name' => 'payer_group',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayerGroup()
            ],
            [
                'label' => 'Referral Status',
                'name' => 'referral_status',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getReferralStatus()
            ],
            [
                'label' => 'Date',
                'name' => 'daterange',
                'type' => 'daterange',
                'fromdate' => H::getFormatedDate($startDate),
                'todate' => H::getFormatedDate($endingDate),
            ],
        ];

        if (LD::checkFeatureFlag('national-intake-add-lead-filter')) {
            array_push($filters, [
                'label' => 'Show Leads Only',
                'name' => 'leadonly',
                'type' => 'checkbox',
                'value' => false
            ]);
        }

        $sourceFilters = [
            [
                'label' => 'Facility',
                'value' => 'facility'
            ],
            [
                'label' => 'Business Dev. Rep',
                'value' => 'bdr'
            ],
            [
                'label' => 'State',
                'value' => 'state'
            ],
            [
                'label' => 'Status',
                'value' => 'status'
            ]
        ];


        $isLeadOnly = 0;
        $where = [];
        $paramsArray = [];
        if (request()->input('isajax')) {
            $search = request()->input('search');
            if ($search) {
                foreach ($search as $field => $value) {
                    if (is_array($value)) {
                        H::ExcelLog($field . ' - ' . print_r($value, true), 'intakereport.log');
                        $tmp = $field;
                        if ($field == 'aoc_facility_id') {
                            $tmp = 'mp.aoc_facility_id';
                        } elseif ($field == 'state') {
                            $tmp = "mf.State";
                        } elseif ($field == 'intakecoordinator') {
                            $tmp = 'u.id';
                        } elseif ($field == 'payer') {
                            $tmp = 'mp.Payer';
                        } elseif ($field == 'payer_group') {
                            $tmp = 'mp.payer_group';
                        } elseif ($field == 'bdrep') {
                            $tmp = $this->normalizeBdRepSql('IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)');
                        } elseif ($field == 'lob') {
                            $tmp = 'ink.LOBID';
                            if ($value == '1023') {
                                $value = '';
                            }
                        } elseif ($field == 'referral_status') {
                            $tmp = "(CASE
                                WHEN (
                                    ink.intake_bucket = 'intake_prospective_clients'
                                ) THEN 'Prospective Clients'
                                WHEN (
                                    ink.intake_bucket = 'intake_schedule'
                                ) THEN 'Intake Scheduled'
                                WHEN (
                                    ink.intake_bucket = 'intake_pending_soc'
                                ) THEN 'Pending Start of Care'
                                WHEN (
                                    ink.intake_bucket = 'intake_client_starts'
                                ) THEN 'Client Starts'
                                WHEN (
										ink.intake_bucket = 'intake_pending_non_admit'
									) THEN 'Pending Non Admit'
                                WHEN (
                                    {$this->admittedConditionSql('sm', 'mp')}
                                ) THEN 'Admitted'
                                WHEN (
                                    (
                                       {$this->nonAdmitConditionSql('mp')}
                                    )
                                ) THEN 'Non Admit'
                                END)";
                        }
                        $fieldStr = '';
                        $fieldStr = $this->generateMultiSelectQuery($tmp, $value, $paramsArray);
                        $where[] = $fieldStr;
                    } else {
                        H::ExcelLog($field . ' - ' . $value, 'intakereport.log');
                        if ($value != '') {
                            if ($field == 'region') {
                                $where[] = "reg.region_name = ?";
                                array_push($paramsArray, $value);
                            } else if ($field == 'leadonly') {
                                $where[] = "IFNULL(ink.intake_lead_exclude, 0) = 1";
                                $isLeadOnly = 1;
                            }
                        }
                    }
                }
            }
        }

        $periodQuery = H::getReportPeriodDefinition($report['period'], 'IF(ink.LOBID = mp.LOBID, mp.ReferralDate, ink.ReferralDate)', true);
        //now get report data

        if (LD::checkFeatureFlag('national-intake-add-lead-filter')) {
            if ($isLeadOnly == 0) {
                $where[] = "IFNULL(ink.intake_lead_exclude, 0) = 0";
            }
        }

        $intakeStatus = "
            CASE
                WHEN (
                    ink.intake_bucket = 'intake_prospective_clients'
                ) THEN 'Prospective Clients'
                WHEN (
                    ink.intake_bucket = 'intake_schedule'
                ) THEN 'Intake Scheduled'
                WHEN (
                    ink.intake_bucket = 'intake_pending_soc'
                ) THEN 'Pending Start of Care'
                WHEN (
                    ink.intake_bucket = 'intake_client_starts'
                ) THEN 'Client Starts'
                WHEN (
                    ink.intake_bucket = 'intake_pending_non_admit'
                ) THEN 'Pending Non Admit'
                WHEN (
                    {$this->admittedConditionSql('sm', 'mp')}
                ) THEN 'Admitted'
                WHEN (
                    (
                        {$this->nonAdmitConditionSql('mp')}
                    )
                ) THEN 'Non Admit'
                END as IntakeStatus
        ";

        $intakeStatusSorting = "
            CASE
                WHEN (
					ink.intake_bucket = 'intake_prospective_clients'
                ) THEN 1
                WHEN (
                    ink.intake_bucket = 'intake_schedule'
                ) THEN 2
                WHEN (
                    ink.intake_bucket = 'intake_pending_soc'
                ) THEN 3
                WHEN (
                    ink.intake_bucket = 'intake_client_starts'
                ) THEN 4
                WHEN (
                    ink.intake_bucket = 'intake_pending_non_admit'
                ) THEN 5
                WHEN (
                    {$this->admittedConditionSql('sm', 'mp')}
                ) THEN 6
                WHEN (
                    (
						{$this->nonAdmitConditionSql('mp')}
					)
                ) THEN 7
                END
        ";

        if ($source == 'bdr') {
            $bdRepNormalized = $this->normalizeBdRepSql('IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)');
            $select = "refmstr.name as Branch_Location, MIN(IF(ink.LOBID = mp.LOBID, mp.BD_Rep, ink.BD_Rep)) as BD_Rep, $bdRepNormalized as BDRepKey, " . $intakeStatus ;
            $sortGroupBy = " group by NULLIF($bdRepNormalized, ''), NULLIF(refmstr.name, ''),
            ".str_replace(" as IntakeStatus", "", $intakeStatus).", " . $periodQuery . "
            order by NULLIF($bdRepNormalized, ''), NULLIF(refmstr.name, ''),
            ".$intakeStatusSorting.", " . $periodQuery;
        } else if ($source == 'state') {
            $select = 'mf.State as state, mf.BranchName as BranchName, reg.region_name,
                    reg.region_name as region, ' . $intakeStatus . ',
                    mf.id as state_id, reg.id as region_id';
            $sortGroupBy = " group by NULLIF(mf.State, ''), NULLIF(region, ''), mp.aoc_facility_id,
            ".str_replace(" as IntakeStatus", "", $intakeStatus).", " . $periodQuery . "
            order by NULLIF(mf.State, ''), NULLIF(region, ''), mp.aoc_facility_id,
            ".$intakeStatusSorting.", " . $periodQuery;
        } else if ($source == 'status') {
            $select = $intakeStatus;
            $sortGroupBy = " group by
            ".str_replace(" as IntakeStatus", "", $intakeStatus).", " . $periodQuery . "
            order by
            ".$intakeStatusSorting.", " . $periodQuery;
        } else {
            $select = 'refmstr.name as Branch_Location, mf.BranchName as BranchName, ' . $intakeStatus;
            $sortGroupBy = " group by NULLIF(refmstr.name, ''), NULLIF(mf.BranchName, ''),
            ".str_replace(" as IntakeStatus", "", $intakeStatus).", " . $periodQuery . "
            order by NULLIF(refmstr.name, ''), NULLIF(mf.BranchName, ''), ".
            $intakeStatusSorting.", " . $periodQuery;
        }

        if (request()->view && request()->view  == 'hours') {
            $caseSql = $this->intakeService->getPatientHoursFieldCaseSql('ink.LOBID', 'mp');
            $count = "SUM($caseSql) as myCount";
        } else if (request()->view && request()->view == 'percentage') {
            $count = 'count(ink.id) as myCount';
        } else {
            $count = 'count(ink.id) as myCount';
        }

        $qry = "SELECT
        " . $select . "," . $periodQuery . " as Monday, ".$count.", 0 as candidate_start_hour_goals
        FROM intake ink
		left join mssql_patients mp on mp.id = ink.aoc_patient_id
        left join mssql_schedulemaster sm on ink.intake_first_billable_visit = sm.CGTaskID
        left join users u on u.id = ink.ic_id
        left join mssql_facilities mf on mf.id = mp.aoc_facility_id
        left join regions reg on reg.id = mf.region
        left join `lob` on `lob`.`LOBID` = ink.`LOBID`
        left join `mssql_providers` on `mssql_providers`.`ClinicianID` = CAST(mp.SOCClinicianID AS CHAR)
        left join referralsource_master refmstr on refmstr.source_id = ink.ReferralSourceID
        where IF(ink.LOBID = mp.LOBID, mp.ReferralDate, ink.ReferralDate) >= ? and IF(ink.LOBID = mp.LOBID, mp.ReferralDate, ink.ReferralDate) <= ?" .
            (!empty($where) ? self::ANDCONDITION . implode(self::ANDCONDITION, $where) : '')  . $sortGroupBy;

        H::ExcelLog($qry, 'intakereport.log');
        $tmp = DB::select($qry, array_merge([
            $startDate,
            $endingDate
        ], $paramsArray));
        $tmp = json_decode(json_encode($tmp), true);
        $currentMonday = H::getMondayFromDate(date('Y-m-d'), 0);
        $currentSixMonday = H::getMondayFromDate($currentMonday, 6);
        $currentThirteenMonday = H::getMondayFromDate($currentMonday, 13);
        $bdRepDisplayNames = $source === 'bdr' ? $this->intakeService->resolveBdRepDisplayNames($tmp) : [];
        $data = [];
        //process each record in case we need to alter data programmatically
        foreach ($tmp as $record) {
            if ($source == 'bdr') {
                $id = $record['BDRepKey'] . $record['Branch_Location'] . $record['IntakeStatus'];
            } else if ($source == 'state') {
                $id = $record['state'] . $record['BranchName'] . $record['region'] . $record['IntakeStatus'];
            } else if ($source == 'status') {
                $id = $record['IntakeStatus'];
            } else {
                $id = $record['Branch_Location'] . $record['BranchName'] . $record['IntakeStatus'];
            }
            if (!isset($data[$id])) {
                if ($source == 'bdr') {
                    $data[$id] = [
                        'Branch_Location' => $record['Branch_Location'],
                        'BD_Rep' => $bdRepDisplayNames[$record['BDRepKey']],
                        'IntakeStatus' => $record['IntakeStatus'],
                    ];
                } else if ($source == 'state') {
                    $data[$id] = [
                        'state' => $record['state'],
                        'region' => $record['region'],
                        'BranchName' => $record['BranchName'],
                        'state_id' => $record['state_id'],
                        'region_id' => $record['region_id'],
                        'IntakeStatus' => $record['IntakeStatus'],
                    ];
                } else if ($source == 'status') {
                    $data[$id] = [
                        'IntakeStatus' => $record['IntakeStatus'],
                    ];
                } else {
                    $data[$id] = [
                        'Branch_Location' => $record['Branch_Location'],
                        'BranchName' => $record['BranchName'],
                        'IntakeStatus' => $record['IntakeStatus'],
                    ];
                }
                //dynamically add fields for each period
                foreach ($periods as $period) {
                    $data[$id][$period['name']] = 0;
                }
                $data[$id]['SixWeekAvg'] = 0;
                $data[$id]['ThirteenWeekAvg'] = 0;
                $data[$id]['AvgTrend'] = 0;
                $data[$id]['Total'] = 0;
                $data[$id]['Count'] = 0;
            }

            if (request()->view && request()->view == 'percentage') {
                //$total1 = (($record[$report['goalField']] > 0) ? (($record['myCount'] / $record[$report['goalField']]) * 100) : 0);
				$total1 = $record['myCount'];
                $total1 = is_int($total1) ? $total1 : round($total1);
                $data[$id][$record['Monday']] = $total1;
                $data[$id]['Total'] = $data[$id]['Total'] + $total1;
                if (floatval($data[$id]['Total']) != 0) {
                    $data[$id]['Count']++;
                }
            } else {

                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentSixMonday)
                ) {
                    $data[$id]['SixWeekAvg'] = $data[$id]['SixWeekAvg'] + $record['myCount'];
                }
                if (
                    strtotime($currentMonday) > strtotime($record['Monday']) &&
                    strtotime($record['Monday']) >= strtotime($currentThirteenMonday)
                ) {
                    $data[$id]['ThirteenWeekAvg'] = $data[$id]['ThirteenWeekAvg'] + $record['myCount'];
                }

                $data[$id][$record['Monday']] = $record['myCount'];
                $data[$id]['Total'] = $data[$id]['Total'] + $record['myCount'];
            }
        }

		if (request()->view && request()->view == 'percentage') {
            //average the Total columns
			$test = $data;
			$finalCanidateTotals = [];
			foreach ($test as $key => $value) {
                foreach ($value as $key1 => $value1) {
                    if (!in_array($key1, [
                        'Branch_Location',
                        'BranchName',
                        'IntakeStatus',
						'BD_Rep',
						'state',
						'region_name',
						'region',
                        'SixWeekAvg',
                        'ThirteenWeekAvg',
                        'AvgTrend',
                        'Count',
						'state_id',
						'region_id'
                    ])) {
                        if (!isset($finalCanidateTotals[$key1])) {
                            $finalCanidateTotals[$key1] = $data[$key][$key1];
                        } else {
                            $finalCanidateTotals[$key1] += $data[$key][$key1];
                        }
                    }
                }
            }
			foreach ($test as $key => $value) {
				foreach ($value as $key1 => $value1) {
                    if (isset($finalCanidateTotals[$key1])) {
                        $data[$key][$key1] = ($finalCanidateTotals[$key1] > 0) ?
                        (($data[$key][$key1] / $finalCanidateTotals[$key1] ) * 100) : 0;
                    }
				}
			}
        }

        /*if (request()->view && request()->view == 'percentage') {
            //average the Total columns
            $test = $data;
            foreach ($test as $key => $value) {
                if ($value['Count'] != 0) {
                    $data[$key]['Total'] = round($data[$key]['Total'] / $data[$key]['Count'], 0);
                } else {
                    $data[$key]['Total'] = 0;
                }
            }
        }*/
        /**
         * add one extra row with id of 'grand' so that we don't hit the end of the datasource before knowing we need to print grand totals.
         * this is a hack (but it works) instead of having to output subtotals again after end of datasource probably better to put in a function . . but this works so????
         */
        if (isset($id)) {
            $data['grand'] = $data[$id];
        }

        return view('reports.intakeReports', [
            'report' => $report,
            'data' => $data,
            'filters' => $filters,
            'sourceFilters' => $sourceFilters,
            'source' => 'branch'
        ]);
    }

    private function admittedConditionSql(string $scheduleAlias, string $patientAlias): string
    {
        $scheduleStatusIds = implode(', ', [
            MssqlScheduleMasterStatus::APPROVED->value,
            MssqlScheduleMasterStatus::COMPLETED->value,
            MssqlScheduleMasterStatus::IN_PROGRESS->value,
        ]);

        return "IFNULL({$scheduleAlias}.ScheduleStatusID, 0) IN ({$scheduleStatusIds})"
            . " OR {$patientAlias}.Patient_CurrentStatusID = " . MssqlPatientStatus::ACTIVE->value;
    }

    private function nonAdmitConditionSql(string $patientAlias): string
    {
        $patientStatusIds = implode(', ', [
            MssqlPatientStatus::NON_ADMIT->value,
            MssqlPatientStatus::INACTIVE->value,
            MssqlPatientStatus::DISCHARGED->value,
        ]);

        return "{$patientAlias}.Patient_CurrentStatusID IN ({$patientStatusIds})";
    }

    public function generateMultiSelectQuery($field, $value, &$array)
    {
        $fieldStr = [];
        foreach ($value as $fieldValue) {
            $fieldStr[] = '?';
            array_push($array, $fieldValue);
        }
        return ' ' . $field . ' IN (' . implode(", ", $fieldStr) . ') ';
    }

    public function get_days_in_month($month, $year)
    {
        if ($month == "02")
        {
            if ($year % 4 == 0) {
                return 29;
            } else {
                return 28;
            }
        }
        else if ($month == "01" || $month == "03" || $month == "05" || $month == "07" || $month == "08" || $month == "10" || $month == "12") {
            return 31;
        } else {
            return 30;
        }
    }
}
