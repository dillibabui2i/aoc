<?php

namespace App\Console\Commands;

use App\Repositories\MssqlPatientsStatus\MssqlPatientStatusReadRepository;
use App\Repositories\NeedToHireAccounts\NeedToHireAccountsWriteRepository;
use App\Services\NeedToHire\SocialMediaAdAutoCloseService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncAdmitsNTH extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'not-active-admits-nth:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close NTH record if admit status is not active';

    public function __construct(
        private MssqlPatientStatusReadRepository  $mssqlPatientStatusReadRepository,
        private NeedToHireAccountsWriteRepository $needToHireAccountsWriteRepository,
        private SocialMediaAdAutoCloseService $socialMediaAdAutoCloseService
    )
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $notActiveStatusesIds = $this->mssqlPatientStatusReadRepository->getNotActiveStatusesIds();
        $closedNthIds = $this->needToHireAccountsWriteRepository->syncOpenNthWithNotActiveAdmits($notActiveStatusesIds);
        $this->socialMediaAdAutoCloseService->closeOpenAdsForNths($closedNthIds);

        return 0;
    }
}
