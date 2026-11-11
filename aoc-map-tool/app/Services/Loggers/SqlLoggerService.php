<?php

namespace App\Services\Loggers;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;

class SqlLoggerService extends Logger
{
    private array $loggedQueries = [];
    private array $queryTraces = [];
    private array $queryCounts = [];

    public function __construct()
    {
        App::terminating(fn() => $this->reset());
        Event::listen(CommandFinished::class, fn() => $this->reset());
    }

    public function handle(QueryExecuted $query): void
    {
        if ($query->time < 200) {
            return;
        }

        $sql = $this->replaceBindings($query->sql, $query->bindings);
        $hash = md5($sql);
        $this->queryCounts[$hash] = ($this->queryCounts[$hash] ?? 0) + 1;

        if (isset($this->loggedQueries[$hash])) {
            return;
        }

        if (count($this->loggedQueries) > 100) {
            return;
        }

        $this->loggedQueries[$hash] = true;

        if (!isset($this->queryTraces[$hash])) {
            $this->queryTraces[$hash] = $this->findCaller();
        }

        $trace = $this->queryTraces[$hash];


        $msg = sprintf(
            'hash:%s !@@ time_ms:%s !@@ file:%s !@@ line:%s !@@ sql: %s \n',
            $hash,
            $query->time,
            $trace['file'] ?? null,
            $trace['line'] ?? null,
            $sql
        );

        error_log($msg,0);
    }

    private function replaceBindings(string $sql, array $bindings): string
    {
        foreach ($bindings as $binding) {
            $binding = is_numeric($binding)
                ? $binding
                : "'" . addslashes((string)$binding) . "'";

            $sql = preg_replace('/\?/', $binding, $sql, 1);
        }
        return $sql;
    }

    private function findCaller(): ?array
    {
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 30) as $frame) {
            $class = $frame['class'] ?? '';
            $file  = $frame['file'] ?? '';

            if (
                str_contains($file, 'vendor') ||
                str_contains($class, 'SqlLoggerService') ||
                str_contains($class, 'LoggerServiceProvider')
            ) {
                continue;
            }

            return $frame;
        }
        return null;
    }


    private function reset(): void
    {
        $this->loggedQueries = [];
        $this->queryTraces = [];
    }
}
