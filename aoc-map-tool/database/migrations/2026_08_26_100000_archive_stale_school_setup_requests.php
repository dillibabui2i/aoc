<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('
        UPDATE `school_setup_requests`
            SET is_deleted = 1,
                deleted_at = NOW()
            WHERE is_deleted = 0
                AND created_at < "2026-01-01"'
        );
    }

    public function down()
    {
        DB::statement('
        UPDATE `school_setup_requests`
            SET is_deleted = 0,
                deleted_at = NULL
            WHERE is_deleted = 1
                AND created_at < "2026-01-01"'
        );
    }
};
