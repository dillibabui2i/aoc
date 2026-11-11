<?php

use Illuminate\Database\Migrations\Migration;

use Illuminate\Support\Facades\DB;
use App\MssqlRecruitertracker;

return new class extends Migration {
    public function up()
    {
        MssqlRecruitertracker::query()
            ->where('IPEA_Date', '<>', '0000-00-00 00:00:00')
            ->where(DB::raw('date(IPEA_Date)'), '<', '2026-08-01')
            ->where('Client_Name', 'like', '%PRN%')
            ->update([
                'is_deleted' => 1,
                'deleted_at' => now(),
            ]);
    }

    public function down()
    {
        MssqlRecruitertracker::query()
            ->withoutGlobalScopes()
            ->where('IPEA_Date', '<>', '0000-00-00 00:00:00')
            ->where(DB::raw('date(IPEA_Date)'), '<', '2026-08-01')
            ->where('Client_Name', 'like', '%PRN%')
            ->where('is_deleted', 1)
            ->whereNotNull('deleted_at')
            ->update([
                'is_deleted' => 0,
                'deleted_at' => null,
            ]);
    }
};
