<?php

namespace App\Kantime\DataMappers;

use App\Kantime\Domain\DataItem;
use App\Kantime\Domain\ScheduleMasterItem;
use App\Kantime\Repository\MariaDB\FacilityRepository;

final class ScheduleMasterDataMapper implements DataMapperInterface
{
    /** @var array<string,string>|null */
    private static ?array $facilityMapCache = null;

    public function __construct(private readonly FacilityRepository $facilityRepository)
    {
    }

    /**
     * Ensure FacilityID is populated using BillingFacilityID like the legacy job.
     */
    public function getFacilityId(array $row): string|null
    {
        $facilityId = $row['HHABranchID'] ?? '';

        if ($facilityId === '') {
            return null;
        }

        $map = $this->getFacilityList();
        if (isset($map[$facilityId])) {
            return $map[$facilityId];
        }

        return null;
    }

    /**
     * @param array<string,mixed> $item
     */
    public function fromDaasRow(array $item): ScheduleMasterItem
    {
        return new ScheduleMasterItem(
            CGTaskID: (int) ($item['CGTaskID'] ?? 0),
            CreatedBy: $item['CreatedBy'] ?? null,
            BillingFacilityID: $item['HHABranchID'] ?? null,
            FacilityID: $this->getFacilityId($item) ?? null,
            PatientID: $item['ClientID'] ?? null,
            ProviderID: $item['Caregiver'] ?? null,
            BillingPatientID: $item['ClientID'] ?? null,
            BillingProviderID: $item['Caregiver'] ?? null,
            InsuranceID: $item['PayerID'] ?? null,
            ServiceCodeID: $item['ServiceCodeID'] ?? null,
            ServiceName: '',
            ScheduleDate: $item['ScheduleDate'] ?? null,
            PlannedStart: $item['PlannedStart'] ?? null,
            PlannedEnd: $item['PlannedEnd'] ?? null,
            ActualStart: $item['ActualStart'] ?? null,
            ActualEnd: $item['ActualEnd'] ?? null,
            PlannedHours: isset($item['PlannedHours']) ? (float) $item['PlannedHours'] : null,
            ActualHours: isset($item['ActualHours']) ? (float) $item['ActualHours'] : null,
            Hours: isset($item['Hours']) ? (float) $item['Hours'] : null,
            ScheduleStatusID: $this->getScheduleStatusID((string) ($item['ScheduleStatus'] ?? '')),
            EChartStatusID: $this->getEchartStatusID((string) ($item['EChartStatus'] ?? '')),
            AuthorizationStatusID: $this->getAuthStatusID((string) ($item['Authorizations'] ?? '')),
            Miles: isset($item['Miles']) ? (float) $item['Miles'] : null,
            IsAuthRequired: isset($item['IsAuthRequired']) ? ($item['IsAuthRequired'] ? '1' : '0') : null,
            PaybleHours: $item['PaybleHours'] ?? null,
            MissedVisitReasonID: (int) ($item['MissedVisitReasonID'] ?? 0),
            MissedVisitReason: $item['MissedVisitReason'] ?? null,
            WeekStart: $item['WeekStart'] ?? null,
            EpisodeID: $item['EpisodeID'] ?? null,
            IsPRNVisit: isset($item['IsPRNVisit']) ? ($item['IsPRNVisit'] ? 1 : 0) : null,
            RevenueCode: $item['RevenueCode'] ?? null,
            GCode: $item['GCode'] ?? null,
            Modifier1: $item['Modifier1'] ?? null,
            Modifier2: $item['Modifier2'] ?? null,
            Modifier3: $item['Modifier3'] ?? null,
            Units: $item['Units'] ?? null,
            HasChild: isset($item['HasChild']) ? ($item['HasChild'] ? 1 : 0) : null,
            IsChild: isset($item['IsChild']) ? ($item['IsChild'] ? 1 : 0) : null,
            ParentCgTaskID: $item['ParentCgTaskID'] ?? null,
            IsBillable: isset($item['IsBillable']) ? ($item['IsBillable'] ? 1 : 0) : null,
            IsPayable: isset($item['IsPayable']) ? ($item['IsPayable'] ? 1 : 0) : null,
            IsBilled: isset($item['IsBilled']) ? ($item['IsBilled'] ? 1 : 0) : null,
            IsPaid: isset($item['IsPaid']) ? ($item['IsPaid'] ? 1 : 0) : null,
            BillRate: isset($item['BillRate']) ? (float) (string) $item['BillRate'] : null,
            PayRate: isset($item['PayRate']) ? (float) (string) $item['PayRate'] : null,
            BillModeID: $this->getBillModeID((string) ($item['BillMode'] ?? '')),
            PayMode: $item['PayMode'] ?? null,
            BilledAmount: isset($item['BilledAmount']) ? (float) (string) $item['BilledAmount'] : null,
            PayrolledAmount: isset($item['PayrolledAmount']) ? (float) (string) $item['PayrolledAmount'] : null,
            RegularAmount: isset($item['RegularAmount']) ? (float) (string) $item['RegularAmount'] : null,
            OTAmount: isset($item['OTAmount']) ? (float) (string) $item['OTAmount'] : null,
            BonusAmount: isset($item['BonusAmount']) ? (float) (string) $item['BonusAmount'] : null,
            BaseRate: isset($item['BaseRate']) ? (float) (string) $item['BaseRate'] : null,
            CancellationReason: $item['CancellationReason'] ?? null,
            ContractualRate: isset($item['ContractualRate']) ? (float) $item['ContractualRate'] : null,
            ContractualModeID: $this->getContractualModeID((string) ($item['ContractualMode'] ?? '')),
            ContractualAmount: isset($item['ContractualAmount']) ? (float) (string) $item['ContractualAmount'] : null,
            NonBillableReason: $item['NonBillableReason'] ?? null,
            RegularHours: isset($item['RegularHours']) ? (float) $item['RegularHours'] : null,
            OTHours: isset($item['OTHours']) ? (float) $item['OTHours'] : null,
            isMiscVisit: isset($item['isMiscVisit']) ? ($item['isMiscVisit'] ? 1 : 0) : null,
            Cost: isset($item['Cost']) ? (float) (string) $item['Cost'] : null,
            Revenue: isset($item['Revenue']) ? (float) (string) $item['Revenue'] : null,
            ContractualRevenue: isset($item['ContractualRevenue']) ? (float) (string) $item['ContractualRevenue'] : null,
            Profit: isset($item['Profit']) ? (float) (string) $item['Profit'] : null,
            ProfitContractual: isset($item['ProfitContractual']) ? (float) (string) $item['ProfitContractual'] : null,
            Margin: isset($item['Margin']) ? (float) (string) $item['Margin'] : null,
            MarginContractual: isset($item['MarginContractual']) ? (float) (string) $item['MarginContractual'] : null,
            BilledHours: isset($item['BilledHours']) ? (float) $item['BilledHours'] : null,
            ScheduleProductivityPoints: isset($item['ScheduleProductivityPoints']) ? (float) $item['ScheduleProductivityPoints'] : null,
            PayrollWeekStartDate: $item['PayrollWeekStartDate'] ?? null,
            WeekNo: $item['WeekNo'] ?? null,
            ClinicianProductivityPerWeek: $item['ClinicianProductivityPerWeek'] ?? null,
            AuthorizationID: $item['AuthorizationID'] ?? null,
            ScheduleMiscVisitID: $item['ScheduleMiscVisitID'] ?? null,
            SubmittedOn: $item['SubmittedOn'] ?? null,
            ApprovedOn: $item['ApprovedOn'] ?? null,
            ScheduleInvoiceDate: $item['InvoicedOn'] ?? null,
            PaymentReceivedOn: $item['PaymentRecievedOn'] ?? null,
            HolidayHours: isset($item['HolidayHours']) ? (float) $item['HolidayHours'] : null,
            HolidayPayrollAmount: isset($item['HolidayPayrollAmount']) ? (float) (string) $item['HolidayPayrollAmount'] : null,
            HolidayPayrollRates: isset($item['HolidayPayrollRate']) ? (float) (string) $item['HolidayPayrollRate'] : null,
            OTPayrollRate: isset($item['OTPayrollRate']) ? (float) (string) $item['OTPayrollRate'] : null,
            AddedOn: $item['AddedOn'] ?? null,
            SecondaryPayerSourceNames: $item['SecondaryPayerSourceNames'] ?? null,
            TertiaryPayerSourceNames: $item['TertiaryPayerSourceNames'] ?? null,
            MiscVisitReason: $item['MiscVisitReason'] ?? null,
            EMRID: 1,
            ClaimDetailID: $item['ClaimDetailID'] ?? '',
            CalculatedRegularHours: $item['CalculatedRegularHours'] ?? '',
            CalculatedRegularRate: $item['CalculatedRegularRate'] ?? '',
            ReportingCompletedHours: $item['ReportingCompletedHours'] ?? '',
            ReportingMissedHours: $item['ReportingMissedHours'] ?? '',
            ReportingPlannedHours: $item['ReportingPlannedHours'] ?? '',
            MissedVisitReasonCategoryID: $item['MissedVisitReasonCategoryID'] ?? '',
            CalculatedRevenue: $item['CalculatedRevenue'] ?? '',
            CalculatedCost: $item['CalculatedCost'] ?? '',
            GrossMargin: $item['GrossMargin'] ?? '',
            DistinctServiceCodeID: $item['DistinctServiceCodeID'] ?? '',
            PlacedHours: $item['PlacedHours'] ?? '',
            ReportingHours: $item['ReportingHours'] ?? '',
            SecondaryPayer: $item['SecondaryPayer'] ?? '',
            TertiaryPayer: $item['TertiaryPayer'] ?? '',
            updated_at: $item['updated_at'] ?? null,
            is_updated: (int) ($item['is_updated'] ?? 1),
            is_deleted: (int) ($item['is_deleted'] ?? 0),
            etl_updated_timestamp: $item['LASTMODIFIED'] ?? null,
            checked_off_at: $item['checked_off_at'] ?? null,
            Modifier4: $item['Modifier4'] ?? null,
            is_no_provider: isset($item['is_no_provider']) ? (int) $item['is_no_provider'] : null,
            isValid: !in_array(strtolower((string) ($item['ISVALIDRECORD'] ?? 'true')), ['false', '0', 'no'], true),
        );
    }

    public function toDbRow(ScheduleMasterItem | DataItem $item): array
    {
        if (!$item instanceof ScheduleMasterItem) {
            throw new \InvalidArgumentException('ScheduleMasterDataMapper::toDbArray expects ScheduleMasterItem');
        }

        return [
            'CGTaskID' => $item->CGTaskID,
            'CreatedBy' => $item->CreatedBy,
            'BillingFacilityID' => $item->BillingFacilityID,
            'FacilityID' => $item->FacilityID,
            'PatientID' => $item->PatientID,
            'ProviderID' => $item->ProviderID,
            'BillingPatientID' => $item->BillingPatientID,
            'BillingProviderID' => $item->BillingProviderID,
            'InsuranceID' => $item->InsuranceID,
            'ServiceCodeID' => $item->ServiceCodeID,
            'ServiceName' => $item->ServiceName,
            'ScheduleDate' => $item->ScheduleDate,
            'PlannedStart' => $item->PlannedStart,
            'PlannedEnd' => $item->PlannedEnd,
            'ActualStart' => $item->ActualStart,
            'ActualEnd' => $item->ActualEnd,
            'PlannedHours' => $item->PlannedHours,
            'ActualHours' => $item->ActualHours,
            'Hours' => $item->Hours,
            'ScheduleStatusID' => $item->ScheduleStatusID,
            'EChartStatusID' => $item->EChartStatusID,
            'AuthorizationStatusID' => $item->AuthorizationStatusID,
            'Miles' => $item->Miles,
            'IsAuthRequired' => $item->IsAuthRequired,
            'PaybleHours' => $item->PaybleHours,
            'MissedVisitReasonID' => $item->MissedVisitReasonID,
            'MissedVisitReason' => $item->MissedVisitReason,
            'WeekStart' => $item->WeekStart,
            'EpisodeID' => $item->EpisodeID,
            'IsPRNVisit' => $item->IsPRNVisit,
            'RevenueCode' => $item->RevenueCode,
            'GCode' => $item->GCode,
            'Modifier1' => $item->Modifier1,
            'Modifier2' => $item->Modifier2,
            'Modifier3' => $item->Modifier3,
            'Modifier4' => $item->Modifier4,
            'Units' => $item->Units,
            'HasChild' => $item->HasChild,
            'IsChild' => $item->IsChild,
            'ParentCgTaskID' => $item->ParentCgTaskID,
            'IsBillable' => $item->IsBillable,
            'IsPayable' => $item->IsPayable,
            'IsBilled' => $item->IsBilled,
            'IsPaid' => $item->IsPaid,
            'BillRate' => $item->BillRate,
            'PayRate' => $item->PayRate,
            'BillModeID' => $item->BillModeID,
            'PayMode' => $item->PayMode,
            'BilledAmount' => $item->BilledAmount,
            'PayrolledAmount' => $item->PayrolledAmount,
            'RegularAmount' => $item->RegularAmount,
            'OTAmount' => $item->OTAmount,
            'BonusAmount' => $item->BonusAmount,
            'BaseRate' => $item->BaseRate,
            'CancellationReason' => $item->CancellationReason,
            'ContractualRate' => $item->ContractualRate,
            'ContractualModeID' => $item->ContractualModeID,
            'ContractualAmount' => $item->ContractualAmount,
            'NonBillableReason' => $item->NonBillableReason,
            'RegularHours' => $item->RegularHours,
            'OTHours' => $item->OTHours,
            'isMiscVisit' => $item->isMiscVisit,
            'Cost' => $item->Cost,
            'Revenue' => $item->Revenue,
            'ContractualRevenue' => $item->ContractualRevenue,
            'Profit' => $item->Profit,
            'ProfitContractual' => $item->ProfitContractual,
            'Margin' => $item->Margin,
            'MarginContractual' => $item->MarginContractual,
            'BilledHours' => $item->BilledHours,
            'ScheduleProductivityPoints' => $item->ScheduleProductivityPoints,
            'PayrollWeekStartDate' => $item->PayrollWeekStartDate,
            'WeekNo' => $item->WeekNo,
            'ClinicianProductivityPerWeek' => $item->ClinicianProductivityPerWeek,
            'AuthorizationID' => $item->AuthorizationID,
            'ScheduleMiscVisitID' => $item->ScheduleMiscVisitID,
            'SubmittedOn' => $item->SubmittedOn,
            'ApprovedOn' => $item->ApprovedOn,
            'ScheduleInvoiceDate' => $item->ScheduleInvoiceDate,
            'PaymentReceivedOn' => $item->PaymentReceivedOn,
            'HolidayHours' => $item->HolidayHours,
            'HolidayPayrollAmount' => $item->HolidayPayrollAmount,
            'HolidayPayrollRates' => $item->HolidayPayrollRates,
            'OTPayrollRate' => $item->OTPayrollRate,
            'AddedOn' => $item->AddedOn,
            'SecondaryPayerSourceNames' => $item->SecondaryPayerSourceNames,
            'TertiaryPayerSourceNames' => $item->TertiaryPayerSourceNames,
            'MiscVisitReason' => $item->MiscVisitReason,
            'EMRID' => $item->EMRID,
            'ClaimDetailID' => $item->ClaimDetailID,
            'CalculatedRegularHours' => $item->CalculatedRegularHours,
            'CalculatedRegularRate' => $item->CalculatedRegularRate,
            'ReportingCompletedHours' => $item->ReportingCompletedHours,
            'ReportingMissedHours' => $item->ReportingMissedHours,
            'ReportingPlannedHours' => $item->ReportingPlannedHours,
            'MissedVisitReasonCategoryID' => $item->MissedVisitReasonCategoryID,
            'CalculatedRevenue' => $item->CalculatedRevenue,
            'CalculatedCost' => $item->CalculatedCost,
            'GrossMargin' => $item->GrossMargin,
            'DistinctServiceCodeID' => $item->DistinctServiceCodeID,
            'PlacedHours' => $item->PlacedHours,
            'ReportingHours' => $item->ReportingHours,
            'SecondaryPayer' => $item->SecondaryPayer,
            'TertiaryPayer' => $item->TertiaryPayer,
            'updated_at' => $item->updated_at,
            'is_updated' => $item->is_updated,
            'is_deleted' => $item->is_deleted,
            'etl_updated_timestamp' => $item->etl_updated_timestamp,
            'checked_off_at' => $item->checked_off_at,
            'is_no_provider' => $item->is_no_provider,
        ];
    }

    private function getScheduleStatusID(string $statusID): int
    {
        return match ($statusID) {
            'Approved' => 1,
            'Completed' => 2,
            'In_Progress' => 3,
            'MissedVisit' => 4,
            'Planned' => 5,
            'NA' => 6,
            'NO' => 7,
            'Office Auth' => 8,
            'Real Auth' => 9,
            'ReSchedule' => 10,
            'Deleted' => 11,
            default => 0,
        };
    }

    private function getBillModeID(string $statusID): int
    {
        return match ($statusID) {
            '0' => 1,
            'Fixed' => 2,
            'Hourly' => 3,
            'Unit Based' => 4,
            default => 0,
        };
    }

    private function getContractualModeID(string $statusID): int
    {
        return match ($statusID) {
            '' => 1,
            '0' => 2,
            'Fixed' => 3,
            'Hourly' => 4,
            'Unit Based' => 5,
            default => 0,
        };
    }

    private function getEchartStatusID(string $statusID): int
    {
        return match ($statusID) {
            'Approved' => 2,
            'Planned' => 4,
            'Submitted' => 5,
            'OnCorrection' => 3,
            default => 0,
        };
    }

    private function getAuthStatusID(string $statusID): int
    {
        return match ($statusID) {
            'NA' => 1,
            'NO' => 2,
            'Office Auth' => 3,
            'Real Auth' => 4,
            default => 0,
        };
    }

    private function getFacilityList(): array
    {
        if (self::$facilityMapCache !== null) {
            return self::$facilityMapCache;
        }

        $facilities = [];
        $rows = $this->facilityRepository->getFacilityList();

        foreach ($rows as $r) {
            $facilities[(string) $r->BillingFacilityID] = (string) $r->FacilityID;
        }

        return self::$facilityMapCache = $facilities;
    }
}
