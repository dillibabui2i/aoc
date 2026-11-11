<?php

namespace App\Providers;

use App\Services\Loggers\CommandLoggerService;
use App\Services\Loggers\SqlLoggerService;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class LoggerServiceProvider extends ServiceProvider
{
    public function boot(SqlLoggerService $sqlLogger, CommandLoggerService $commandLogger): void
    {
//        DB::listen(fn($query) => $sqlLogger->handle($query));

//        $this->app['events']->listen(CommandStarting::class, fn($event) => $commandLogger->handleStarting($event));
//        $this->app['events']->listen(CommandFinished::class, fn($event) => $commandLogger->handleFinished($event));
    }
}
