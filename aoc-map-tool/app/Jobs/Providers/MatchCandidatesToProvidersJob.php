<?php

namespace App\Jobs\Providers;

use App\DTO\CandidateDTO;
use App\Jobs\Job;
use App\Repositories\Candidates\CandidateReadRepository;
use App\Repositories\MssqlProviders\MssqlProvidersReadRepository;
use App\Repositories\MssqlProviders\MssqlProvidersWriteRepository;
use Illuminate\Support\Facades\Log;
use Throwable;

class MatchCandidatesToProvidersJob extends Job
{

    public $timeout = 600;

    public $tries = 1;

    public function __construct(public array $providersIds) {}

    public function handle(
      CandidateReadRepository $candidateReadRepository,
      MssqlProvidersReadRepository $providersReadRepository,
      MssqlProvidersWriteRepository $providersWriteRepository
    ): void {
        $providers = $providersReadRepository->getProviders($this->providersIds);
        $notFoundCandidates = [];
        $count = 0;
        $batchSize = 50;
        foreach ($providers as $provider) {
            $email = $this->normalizeEmail($provider->AOC_Email);
            $externalEmail = $this->normalizeEmail($provider->External_Email);
            $phone = $this->normalizePhone($provider->Phone);
            $mobile = $this->normalizePhone($provider->Mobile);
            $candidateDto = new CandidateDto($email, $externalEmail, $phone, $mobile);

            $candidate = $candidateReadRepository->getCandidateByPhoneOrEmail($candidateDto);

            if ($candidate) {
                $providersWriteRepository->update($provider, [
                  'aoc_cf_city' => $candidate->aoc_cf_city,
                  'aoc_cf_state' => $candidate->aoc_cf_state,
                  'aoc_cf_mile_radius' => $candidate->aoc_cf_mile_radius,
                  'aoc_cf_schedule' => $candidate->aoc_cf_schedule,
                  'aoc_cf_environment' => $candidate->aoc_cf_environment,
                  'aoc_cf_lifting_requirements' => $candidate->aoc_cf_lifting_requirements,
                  'aoc_cf_skills' => $candidate->aoc_cf_skills,
                  'aoc_cf_language' => $candidate->aoc_cf_language,
                ]);
            } else {
                $count++;
                $notFoundCandidates[] = sprintf(
                  "[%s] #%d Candidate not found => providerId: %d, phone: %s, mobile: %s, email: %s, external: %s",
                  now()->format('Y-m-d H:i:s'),
                  $count,
                  $provider->id,
                  $phone ?? '-',
                  $mobile ?? '-',
                  $email ?? '-',
                  $externalEmail ?? '-'
                );

                if ($count % $batchSize === 0) {
                    $this->writeLogChunk($notFoundCandidates, $count);
                    $notFoundCandidates = [];
                }
            }
        }

        if (!empty($notFoundCandidates)) {
            $this->writeLogChunk($notFoundCandidates, $count);
        }
    }

    private function normalizePhone(?string $phone): ?string
    {
        if (!$phone) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $phone);
        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            $digits = substr($digits, 1);
        }
        return strlen($digits) === 10 ? $digits : null;
    }

    private function normalizeEmail(?string $email): ?string
    {
        if (!$email) {
            return null;
        }
        return strtolower(trim($email));
    }

    public function failed(Throwable $t): void
    {
        Log::error('MatchCandidatesToProvidersJob ', [
          'massage' => $t->getMessage(),
          'exception' => $t,
        ]);
    }

    private function writeLogChunk(array $rows, int $count): void
    {
        $file = storage_path('logs/MatchCandidatesToProvidersJob_' . now()->format('Y_m_d') . '.log');

        file_put_contents(
          $file,
          "[Batch {$count}] " . implode(PHP_EOL, $rows) . PHP_EOL,
          FILE_APPEND
        );
    }

}
