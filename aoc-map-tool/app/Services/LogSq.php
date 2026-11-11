<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LogSq
{
    public static function processSqlLogs(string $filename): void
    {
        $queries = DB::getQueryLog();
        $logContent = "";

        foreach ($queries as $query) {
            $sql = $query['query'];
            $bindings = $query['bindings'];

            foreach ($bindings as $binding) {
                if (is_string($binding)) {
                    $binding = "'" . addslashes($binding) . "'";
                } elseif (is_null($binding)) {
                    $binding = 'NULL';
                }
                $sql = preg_replace('/\?/', $binding, $sql, 1);
            }

            $logContent .= $sql . ";\n";
        }

        file_put_contents(storage_path("logs/$filename-sql.log"), $logContent, FILE_APPEND);

        DB::disableQueryLog();
    }
}
