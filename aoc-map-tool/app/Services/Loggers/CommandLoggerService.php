<?php

namespace App\Services\Loggers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Facades\Log;

class CommandLoggerService extends Logger
{
    private array $commandTimings = [];

    public function handleStarting(CommandStarting $event): void
    {
        $this->commandTimings[$event->command] = microtime(true);
    }

    public function handleFinished(CommandFinished $event): void
    {
        $startedAt = $this->commandTimings[$event->command] ?? null;
        $durationMs = $startedAt ? round((microtime(true) - $startedAt) * 1000, 2) : null;

        Log::channel(self::COMMANDS_LOG_DRIVER)
            ->info(self::COMMANDS_FINISHED_MSG, [
                'command' => $event->command,
                'exitCode' => $event->exitCode,
                'duration_ms' => $durationMs,
            ]);
    }
}
