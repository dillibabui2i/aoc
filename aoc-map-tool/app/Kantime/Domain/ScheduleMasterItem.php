<?php

namespace App\Kantime\Domain;

final class ScheduleMasterItem implements DataItem
{
    public function __construct(
        // Identifiers / core
        public readonly int $CGTaskID,
        public readonly ?string $CreatedBy,
        public readonly ?string $BillingFacilityID,
        public readonly ?string $FacilityID,
        public readonly ?int $PatientID,
        public readonly ?int $ProviderID,
        public readonly ?int $BillingPatientID,
        public readonly ?int $BillingProviderID,
        public readonly ?int $InsuranceID,
        public readonly ?string $ServiceCodeID,
        public readonly ?string $ServiceName,

        // Dates / times
        public readonly ?string $ScheduleDate,
        public readonly ?string $PlannedStart,
        public readonly ?string $PlannedEnd,
        public readonly ?string $ActualStart,
        public readonly ?string $ActualEnd,

        // Hours
        public readonly ?float $PlannedHours,
        public readonly ?float $ActualHours,
        public readonly ?float $Hours,

        // Statuses
        public readonly ?int $ScheduleStatusID,
        public readonly ?int $EChartStatusID,
        public readonly ?int $AuthorizationStatusID,

        // Misc / visit meta
        public readonly ?string $Miles,
        public readonly ?int $IsAuthRequired,
        public readonly ?string $PaybleHours,
        public readonly ?int $MissedVisitReasonID,
        public readonly ?string $MissedVisitReason,
        public readonly ?string $WeekStart,
        public readonly ?string $EpisodeID,
        public readonly ?int $IsPRNVisit,
        public readonly ?string $RevenueCode,
        public readonly ?string $GCode,

        // Modifiers / units / child flags
        public readonly ?string $Modifier1,
        public readonly ?string $Modifier2,
        public readonly ?string $Modifier3,
        public readonly ?string $Units,
        public readonly ?int $HasChild,
        public readonly ?int $IsChild,
        public readonly ?string $ParentCgTaskID,

        // Bill/Pay flags and rates
        public readonly ?int $IsBillable,
        public readonly ?int $IsPayable,
        public readonly ?int $IsBilled,
        public readonly ?int $IsPaid,
        public readonly ?float $BillRate,
        public readonly ?float $PayRate,
        public readonly ?string $BillModeID,
        public readonly ?string $PayMode,

        // Amounts
        public readonly ?string $BilledAmount,
        public readonly ?string $PayrolledAmount,
        public readonly ?string $RegularAmount,
        public readonly ?string $OTAmount,
        public readonly ?string $BonusAmount,
        public readonly ?string $BaseRate,

        // Cancellation / contractual
        public readonly ?string $CancellationReason,
        public readonly ?string $ContractualRate,
        public readonly ?int $ContractualModeID,
        public readonly ?string $ContractualAmount,
        public readonly ?string $NonBillableReason,

        // Hours breakdown
        public readonly ?float $RegularHours,
        public readonly ?float $OTHours,

        // Financials
        public readonly ?int $isMiscVisit,
        public readonly ?string $Cost,
        public readonly ?string $Revenue,
        public readonly ?string $ContractualRevenue,
        public readonly ?string $Profit,
        public readonly ?string $ProfitContractual,
        public readonly ?string $Margin,
        public readonly ?string $MarginContractual,
        public readonly ?string $BilledHours,
        public readonly ?string $ScheduleProductivityPoints,
        public readonly ?string $PayrollWeekStartDate,
        public readonly ?string $WeekNo,
        public readonly ?string $ClinicianProductivityPerWeek,
        public readonly ?string $AuthorizationID,
        public readonly ?string $ScheduleMiscVisitID,
        public readonly ?string $SubmittedOn,
        public readonly ?string $ApprovedOn,
        public readonly ?string $ScheduleInvoiceDate,
        public readonly ?string $PaymentReceivedOn,
        public readonly ?string $HolidayHours,
        public readonly ?string $HolidayPayrollAmount,
        public readonly ?string $HolidayPayrollRates,
        public readonly ?string $OTPayrollRate,
        public readonly ?string $AddedOn,
        public readonly ?string $SecondaryPayerSourceNames,
        public readonly ?string $TertiaryPayerSourceNames,
        public readonly ?string $MiscVisitReason,
        public readonly int $EMRID,
        public readonly ?string $ClaimDetailID,
        public readonly ?string $CalculatedRegularHours,
        public readonly ?string $CalculatedRegularRate,
        public readonly ?string $ReportingCompletedHours,
        public readonly ?string $ReportingMissedHours,
        public readonly ?string $ReportingPlannedHours,
        public readonly ?string $MissedVisitReasonCategoryID,
        public readonly ?string $CalculatedRevenue,
        public readonly ?string $CalculatedCost,
        public readonly ?string $GrossMargin,
        public readonly ?string $DistinctServiceCodeID,
        public readonly ?string $PlacedHours,
        public readonly ?string $ReportingHours,
        public readonly ?string $SecondaryPayer,
        public readonly ?string $TertiaryPayer,

        //AOC flags
        public ?string $updated_at,
        public int $is_updated,
        public int $is_deleted,
        public ?string $etl_updated_timestamp = null,
        public ?string $checked_off_at = null,
        public ?string $Modifier4 = null,
        public ?int $is_no_provider = null,
        public readonly bool $isValid = true,
    ) {
    }

    public function toArray(): array
    {
        // Important: keep keys matching DB column names.
        return get_object_vars($this);
    }
}
