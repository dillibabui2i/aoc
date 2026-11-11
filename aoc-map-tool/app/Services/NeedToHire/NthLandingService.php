<?php

namespace App\Services\NeedToHire;

use App\Events\NthInquiryCreated;
use App\Repositories\NthInquiry\NthInquiryReadRepository;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Support\Facades\Log;
use Exception;

class NthLandingService
{
    private const ERROR_SAVE_NTH_CASE_INTEREST = 'saveNthCaseInterest() - rollback for clinician %s and NthAccount %d: %s | trace: %s';
    protected $nthInquiryReadRepository;

    public function __construct(NthInquiryReadRepository $nthInquiryReadRepository)
    {
        $this->nthInquiryReadRepository = $nthInquiryReadRepository;
    }

    public function saveNthCaseInterest(int $aoc_nthaccounts_id)
    {
        $clinicianID = Session::get('clinicianID');

        $clinicianData = DB::table('mssql_providers')->where('ClinicianID', $clinicianID)->first();
        if (empty($clinicianData)) {
            H::ExcelLog('clinicianData not exists ', 'saveNthCaseInterest.log');
            return ['status' => false, 'message' => 'Something went wrong. Please try again later.'];
        }

        $nthData = DB::table('NeedToHire_Accounts')->where('id', $aoc_nthaccounts_id)->first();
        $inquiryData = DB::table('nth_inquiries')->where('ClinicianID', $clinicianID)
            ->where('aoc_nthaccounts_id', $aoc_nthaccounts_id)
            ->where('not_interested', 0)->first();

        DB::beginTransaction();
        try {
            $nthAction = 'added';
            if (!empty($inquiryData)) {
                $nthAction = 'removed';
                $updateData['not_interested'] = 1;
                DB::table('nth_inquiries')->where('id', $inquiryData->id)->update($updateData);
                H::getHelper('ActivityNotes')->setActivityNotes([
                    'entity' => 'nthalerts',
                    'entity_id' => $inquiryData->id,
                    'notes' => "Clinician withdrew interest in " . strtoupper(
                            $inquiryData->type
                        ) . "-" . $inquiryData->id . " on " . Carbon::now()
                ]);
            } else {
                $user = DB::table("users")->select("id")->where("name", "=", $nthData->Recruiter)->first();

                if ($user) {
                    $insertData['recruiter_id'] = $user->id;
                }

                $insertData['aoc_patient_id'] = $nthData->aoc_patient_id;
                $insertData['aoc_nthaccounts_id'] = $aoc_nthaccounts_id;
                $insertData['ClinicianID'] = $clinicianID;
                $insertData['aoc_provider_id'] = $clinicianData->id;
                $insertData['type'] = 'lead';
                $insertData['created_at'] = Carbon::now();
                $insertData['updated_at'] = Carbon::now();

                $newestLead = $this->nthInquiryReadRepository->getNewestLead($clinicianData->id);

                $createdLeadId = DB::table('nth_inquiries')->insertGetId($insertData);
                if ($newestLead !== null) {
                    event(new NthInquiryCreated($createdLeadId, $clinicianData, $nthData, $newestLead));
                }
            }

            DB::commit();

            return ['status' => true, 'action' => $nthAction];
        } catch (Exception $e) {
            DB::rollBack();

            Log::error(
                sprintf(
                    self::ERROR_SAVE_NTH_CASE_INTEREST,
                    $clinicianID,
                    $aoc_nthaccounts_id,
                    $e->getMessage(),
                    $e->getTraceAsString()
                )
            );

            return ['status' => false, 'message' => 'Something went wrong. Please try again later.'];
        }
    }
}
