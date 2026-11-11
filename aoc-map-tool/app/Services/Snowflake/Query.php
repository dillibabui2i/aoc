<?php

namespace App\Services\Snowflake;

use App\Helpers\Sync;
use Illuminate\Support\Facades\DB;

class Query
{
    private Sync $syncHelper;
    private Anonymize $anonymizeService;

    public function __construct(Sync $syncHelper, Anonymize $anonymizeService)
    {
        $this->syncHelper = $syncHelper;
        $this->anonymizeService = $anonymizeService;
    }

    public function executeQuery(string $query)
    {
        try {
            $rawData = DB::connection('daas')->select(DB::raw($query));
            $rows = json_decode(json_encode($rawData), true);

            foreach ($rows as $row) {
                echo '<pre>';
                    print_r($row);
                echo '</pre>';

            }
        } catch (\Exception $exception) {
            dd($exception->getMessage(), $exception->getTraceAsString());
        }
    }
}
