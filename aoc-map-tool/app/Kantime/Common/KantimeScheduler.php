<?php

namespace App\Kantime\Common;

use Illuminate\Console\Scheduling\Schedule;

/**
 * This class is a wrapper for jobs scheduling. The job list itself placed in kantime.php config
 */
final class KantimeScheduler
{
    public function register(Schedule $schedule): void
    {
        $config = config('kantime.schedule');
        if (!$config || empty($config['jobs'])) {
            return;
        }

        $defaults = $config['defaults'] ?? [];

        foreach ($config['jobs'] as $name => $jobConfig) {
            if (!($jobConfig['enabled'] ?? false)) {
                continue;
            }

            $cron = $jobConfig['cron'] ?? null;
            if (!$cron) {
                continue;
            }

            $queue = $jobConfig['queue'] ?? ($defaults['queue'] ?? 'default');
            $overlap = $jobConfig['overlap'] ?? ($defaults['overlap'] ?? false);
            $overlapTtl = $jobConfig['overlap_ttl'] ?? ($defaults['overlap_ttl'] ?? null);

            // Prefer queued jobs
            if (!empty($jobConfig['job'])) {
                $jobClass = $jobConfig['job'];
                $args = $jobConfig['args'] ?? [];

                $jobInstance = new $jobClass(...$args);
                $jobInstance->onConnection($defaults['connection']);
                $event = $schedule
                    ->job($jobInstance->onQueue($queue))
                    ->cron($cron)
                    ->name($name);
            } elseif (!empty($jobConfig['command'])) {
                $event = $schedule
                    ->command($jobConfig['command'])
                    ->cron($cron)
                    ->name($name);
            } else {
                continue;
            }

            if ($overlap) {
                $overlapTtl ? $event->withoutOverlapping((int) $overlapTtl) : $event->withoutOverlapping();
            }
        }
    }
}
