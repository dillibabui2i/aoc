<?php

namespace App\Services\Reports;

use App\MssqlRecruitertracker;

class ReportLobFilterService
{

    public function getLobOptions(): array
    {
        return MssqlRecruitertracker::query()
            ->selectRaw("DISTINCT TRIM(SUBSTRING_INDEX(whiteboard_row,' - ',-1)) as lob")
            ->whereRaw("TRIM(SUBSTRING_INDEX(whiteboard_row,' - ',-1)) <> ''")
            ->pluck('lob')
            ->map(fn($lob) => [
                'value' => $lob,
                'text' => $lob,
            ])
            ->values()
            ->toArray();
    }

}
