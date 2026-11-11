<?php

namespace App\Repositories\NthInquiry;

use App\DTO\NthInquiryBulkResolveDTO;
use App\Helpers\H;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use stdClass;

class NthInquiryWriteRepository
{

    public function bulkResolveLeads(NthInquiryBulkResolveDTO $dto, stdClass $advancingLead): array
    {
        $now = now();
        $failedIds = collect();
        $resolvedCount = 0;

        $resolutionDetail = "Moved forward with {$advancingLead->ticket_id}";
        if ($advancingLead->client_name) {
            $resolutionDetail .= " – {$advancingLead->client_name}";
        }

        DB::beginTransaction();

        try {
            $leads = DB::table('nth_inquiries')->whereIn('id', $dto->ids)->get();

            foreach ($leads as $lead) {
                DB::table('nth_inquiries')
                  ->where('id', $lead->id)
                  ->update([
                    'outcome' => 'Resolved',
                    'Status' => 'Resolved',
                    'resolution' => 'Advanced via another Lead',
                    'resolution_detail' => $resolutionDetail,
                    'recruiter_id' => $dto->recruiterId,
                    'resolved_by' => $dto->recruiterId,
                    'resolved_at' => $now,
                    'updated_at' => $now,
                  ]);

                H::getHelper('ActivityNotes')->setActivityNotes([
                  'entity' => 'nthalerts',
                  'entity_id' => $lead->id,
                  'notes' => "Resolved via another Lead: {$resolutionDetail} (by {$dto->recruiterName})",
                ]);

                $resolvedCount++;
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("NTH bulkResolveLeads failed: {$e->getMessage()}");
            throw $e;
        }

        return [
          'resolved_count' => $resolvedCount,
          'failed_ids' => $failedIds,
          'message' => "Resolved {$resolvedCount} lead(s)." .
            ($failedIds->isNotEmpty() ? " Skipped: " . $failedIds->implode(', ') : ''),
        ];
    }

}
