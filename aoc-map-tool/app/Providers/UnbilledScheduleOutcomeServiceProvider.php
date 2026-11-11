<?php

namespace App\Providers;

use App\ScheduleMaster;
use App\Services\UnbilledScheduleOutcome\Handlers\PayerChangeHandler;
use App\Services\UnbilledScheduleOutcome\Handlers\ServiceCorrectionHandler;
use App\Services\UnbilledScheduleOutcome\UnbilledScheduleOutcomeService;
use App\User;
use Illuminate\Support\ServiceProvider;

class UnbilledScheduleOutcomeServiceProvider extends ServiceProvider
{
    /**
     * @return void
     */
    public function register(): void
    {
        $this->app->bind(UnbilledScheduleOutcomeService::class, function ($app) {
            return new UnbilledScheduleOutcomeService(
                $app->make(ScheduleMaster::class),
                $app->make(User::class),
                [
                    $app->make(ServiceCorrectionHandler::class),
                    $app->make(PayerChangeHandler::class),
                ]
            );
        });
    }
}
