<?php

namespace App\Repositories\NthInquiry;

use App\DTO\NthInquiryFilterDTO;
use App\Enums\NthInquiriesStatus;
use App\Enums\NTHLeadsAndInqInquiriesTypeEnum;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

class NthInquiryReadRepository
{

    public function getLeads(NthInquiryFilterDTO $dto): Collection
    {
        $query = DB::table('nth_inquiries')
          ->select(
            'nth_inquiries.id',
            DB::raw("concat(UPPER(nth_inquiries.type), '-', nth_inquiries.id) as ticket_id"),
            'nth_inquiries.aoc_patient_id',
            DB::raw(
              "
                    CASE
                        WHEN nth_inquiries.aoc_nthaccounts_id = 0 THEN ' '
                        ELSE CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName)
                    END as patient_name
                "
            ),
            DB::raw("CONCAT(mssql_patients.Patient_City, ', ', mssql_patients.Patient_State) as city_state"),
            'nth_inquiries.Status',
            DB::raw("users.name AS Recruiter")
          )
          ->leftJoin('users', 'users.id', '=', 'nth_inquiries.recruiter_id')
          ->leftJoin('mssql_patients', function ($join) {
              $join->on('mssql_patients.id', '=', 'nth_inquiries.aoc_patient_id')
                ->where('nth_inquiries.aoc_nthaccounts_id', '>', 0);
          })
          ->where('nth_inquiries.type', 'lead')
          ->whereIn('nth_inquiries.Status', ['New', 'Pending'])
          ->where('ClinicianID', $dto->clinicianId);

        if ($dto->hasExcludeLeadId()) {
            $query->where('nth_inquiries.id', '!=', $dto->excludeLeadId);
        }

        return $query
          ->orderBy($dto->orderColumn, $dto->orderDirection)
          ->get();
    }

    public function getOtherLeadsCountFilteredByClinicianID(NthInquiryFilterDTO $dto): int
    {
        return DB::table('nth_inquiries')
          ->where('ClinicianID', $dto->clinicianId)
          ->when($dto->hasExcludeLeadId(), fn($q) => $q->where('id', '!=', $dto->excludeLeadId))
          ->where('type', 'lead')
          ->whereIn('Status', ['New', 'Pending'])
          ->count();
    }

    public function getLeadWithClientName(int $leadId): ?stdClass
    {
        return DB::table('nth_inquiries')
          ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'nth_inquiries.aoc_patient_id')
          ->select(
            'nth_inquiries.id',
            DB::raw("concat(UPPER(nth_inquiries.type),'-',nth_inquiries.id) as ticket_id"),
            DB::raw(
              "IFNULL(CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName), '') as client_name"
            )
          )
          ->where('nth_inquiries.id', $leadId)
          ->first();
    }

    private function getLeadsAndInquiriesCountBuilder(): Builder
    {
        $query = DB::table('nth_inquiries')
            ->select(
                'nth_inquiries.*',
                'mssql_providers.State',
                DB::raw('users.name AS Recruiter'),
                DB::raw("concat(UPPER(nth_inquiries.type),'-',nth_inquiries.id) as ticket_id"),
                DB::raw("concat(mssql_providers.FirstName,' ',mssql_providers.LastName) as clinician_name"),
                'mssql_facilities.FacilityName',
                'mssql_facilities.BranchName',
                DB::raw("DATE_FORMAT(nth_inquiries.created_at, '%m/%d/%Y %H:%i %p') as created_at"),
                DB::raw("IF(nth_inquiries.type = 'inq', 'Inquiry',CONCAT(UPPER(LEFT(nth_inquiries.type, 1)), LOWER(SUBSTRING(nth_inquiries.type, 2)) )  ) AS category"),
                DB::raw("IF(nth_inquiries.updated_at = '', '-', DATE_FORMAT(nth_inquiries.updated_at, '%m/%d/%Y %H:%i %p')) AS updated_at"),
                DB::raw("(select notes.notes from notes where entity = 'nthalerts' and entity_id = nth_inquiries.id order by notes.id desc limit 1) as Notes"),
                DB::raw("IFNULL(mssql_patients.PatientAccount,'-') as ClientID"),
                DB::raw("IF(mssql_patients.id IS NULL,'-', CONCAT(mssql_patients.Patient_FirstName, ' ', mssql_patients.Patient_LastName)) as ClientName")
            )
            ->leftJoin('mssql_providers', 'mssql_providers.ClinicianID', '=', 'nth_inquiries.ClinicianID')
            ->leftJoin('mssql_patients', 'mssql_patients.id', '=', 'nth_inquiries.aoc_patient_id')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
            ->leftJoin('users', 'users.id', '=', 'nth_inquiries.recruiter_id');
        $query->where('nth_inquiries.Status', '!=', NthInquiriesStatus::RESOLVED->value);
        $query->where('not_interested', 0);

        return $query;
    }

    public function getLeadsAndInquiriesCountClinician(): int
    {
        $query = $this->getLeadsAndInquiriesCountBuilder();
        return $query->distinct()->count('aoc_provider_id');
    }

    public function getLeadsCount(): int
    {
        $query = $this->getLeadsAndInquiriesCountBuilder();
        $query
            ->where('nth_inquiries.type', NTHLeadsAndInqInquiriesTypeEnum::LEAD->value)
            ->whereNotNull('mssql_patients.id')
            ->whereNotNull('mssql_patients.PatientAccount')
            ->where('mssql_patients.PatientAccount', '!=', '');
        
        return $query->count();
    }

    public function getInquiriesCount(): int
    {
        $query = $this->getLeadsAndInquiriesCountBuilder();

        return $query
            ->where('nth_inquiries.type', NTHLeadsAndInqInquiriesTypeEnum::INQUIRY->value)
            ->count();
    }

    private function getSupersededLeadsBuilder(int $leadId, int $providerId): Builder
    {
        return DB::table('nth_inquiries as ni')
            ->leftJoin('mssql_patients as mp', 'mp.id', '=', 'ni.aoc_patient_id')
            ->where('ni.aoc_provider_id', $providerId)
            ->where('ni.not_interested', 0)
            ->where('ni.Status', '<>', NthInquiriesStatus::RESOLVED->value)
            ->whereNotNull('mp.id')
            ->whereNotNull('mp.PatientAccount')
            ->where('mp.PatientAccount', '<>', '')
            ->where('ni.id', '<>', $leadId)
            ->orderByDesc('ni.created_at');
    }

    public function getAllSupersededPendingLeads(int $leadId, int $providerId): Collection
    {
        return $this->getSupersededLeadsBuilder($leadId, $providerId)
            ->where('ni.Status', NthInquiriesStatus::PENDING->value)
            ->leftJoin('users as u', 'u.id', '=', 'ni.recruiter_id')
            ->select([
                'ni.id as id',
                'u.email as email',
                'ni.created_at as created_at',
                'ni.recruiter_id as recruiter_id'
            ])->get();
    }

    public function getSupersededLeadsBanner(Object $lead, int $providerId): array
    {
        $base = $this->getSupersededLeadsBuilder($lead->id, $providerId);

        $newestLead = (clone $base)
            ->leftJoin('users as u', 'u.id', '=', 'ni.recruiter_id')
            ->select('ni.id', 'u.name', 'ni.created_at')
            ->first();

        if($newestLead && $newestLead->created_at > $lead->created_at){
            $base->where('ni.created_at', '>', $lead->created_at);
        }

        $count = (clone $base)->count();

        return [
            'newestLead' => $newestLead,
            'count' => $count
        ];
    }

    public function getNewestLead(int $providerId): ?stdClass
    {
        $lead = DB::table('nth_inquiries as ni')
            ->leftJoin('mssql_patients as mp', 'mp.id', '=', 'ni.aoc_patient_id')
            ->leftJoin('users as u', 'u.id', '=', 'ni.recruiter_id')
            ->where('ni.aoc_provider_id', $providerId)
            ->where('ni.not_interested', 0)
            ->where('ni.Status', '<>', NthInquiriesStatus::RESOLVED->value)
            ->whereNotNull('mp.id')
            ->whereNotNull('mp.PatientAccount')
            ->where('mp.PatientAccount', '<>', '')
            ->orderByDesc('ni.created_at')
            ->select(['ni.id', 'ni.recruiter_id', 'u.email'])
            ->first();

        return $lead;
    }
}
