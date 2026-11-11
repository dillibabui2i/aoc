<?php

namespace App\Console\Commands;

use App\MssqlProviders;
use App\OfficeEvaluation;
use App\RightSignatureDocuments;
use App\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResetEvaluation extends Command
{
    private const STATUS_PENDING_SIGNATURE = 'Pending Employee Signature';
    private const STATUS_NEED_CHANGES = 'Needs Changes';
    private const DOCUMENT_STATUS_VOIDED = 'voided';
    private const MSG_SUPERVISOR_NOT_FOUND = "Didn't find supervisor %s in table users";
    private const MSG_PROVIDER_NOT_FOUND = "Didn't find provider %s %s";
    private const MSG_EVALUATION_NOT_FOUND = "Evaluation for %s %s not found in status %s";
    private const MSG_FOUND_EVALUATION = "Found evaluation #%d with status %s";
    private const MSG_RESET_OK = "Evaluation #%d successfully reset to status %s";
    private const MSG_RESET_ERR = "Error during reset: %s";
    protected $signature = 'evaluation:reset {firstName} {lastName} {supervisorName}';
    protected $description = 'Reset office evaluation for provider (void docs and set status=Needs Changes)';

    public function handle(User $user, MssqlProviders $mssqlProviders, OfficeEvaluation $officeEvaluation, RightSignatureDocuments $documents): int
    {
        $firstName = $this->argument('firstName');
        $lastName = $this->argument('lastName');
        $supervisorName = $this->argument('supervisorName');

        $supervisorId = $user->query()
            ->where('name', $supervisorName)
            ->value('id');

        if (!$supervisorId) {
            $this->error(sprintf(self::MSG_SUPERVISOR_NOT_FOUND, $supervisorName));
            return self::FAILURE;
        }

        $providerId = $mssqlProviders->query()
            ->where('FirstName', $firstName)
            ->where('LastName', $lastName)
            ->value('id');

        if (!$providerId) {
            $this->error(sprintf(self::MSG_PROVIDER_NOT_FOUND, $firstName, $lastName));
            return self::FAILURE;
        }

        $evaluation = $officeEvaluation->query()
            ->where('aoc_provider_id', $providerId)
            ->where('status', self::STATUS_PENDING_SIGNATURE)
            ->whereYear('created_at', 2025)
            ->where(function ($q) {
                $q->whereNull('is_deleted')->orWhere('is_deleted', 0);
            })
            ->orderByDesc('created_at')
            ->first();

        if (!$evaluation) {
            $this->error(sprintf(self::MSG_EVALUATION_NOT_FOUND, $firstName, $lastName, self::STATUS_PENDING_SIGNATURE));
            return self::FAILURE;
        }

        $this->info(sprintf(self::MSG_FOUND_EVALUATION, $evaluation->id, $evaluation->status));

        try {
            DB::beginTransaction();

            $documents->query()
                ->where('evaluation_id', $evaluation->id)
                ->where('entity_type', 'provider')
                ->where('document_type', 'office_eval')
                ->update([
                    'status' => self::DOCUMENT_STATUS_VOIDED,
                    'updated_at' => now(),
                ]);

            $officeEvaluation->query()
                ->where('id', $evaluation->id)
                ->update([
                    'status' => self::STATUS_NEED_CHANGES,
                    'updated_by' => $supervisorId,
                    'updated_at' => now(),
                ]);

            DB::commit();

            $this->info(sprintf(self::MSG_RESET_OK, $evaluation->id, self::STATUS_NEED_CHANGES));
            return self::SUCCESS;
        } catch (Throwable $e) {
            DB::rollBack();
            $this->error(sprintf(self::MSG_RESET_ERR, $e->getMessage()));
            Log::error('ResetEvaluation failed', [
                'evaluation_id' => $evaluation->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return self::FAILURE;
        }
    }
}
