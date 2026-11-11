<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::table('referralsource_master', function (Blueprint $table) {
            $table->renameColumn('address_line_2', 'city');
        });

        DB::statement("
            UPDATE process_settings
            SET comment = REPLACE(comment, 'cast(RS_ADDRESS_LINE_2 as text) as ADDRESS_LINE_2', 'cast(RS_CITY as text) as CITY')
            WHERE process = 'daas_referralsource_master';
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('referralsource_master', function (Blueprint $table) {
            $table->renameColumn('city', 'address_line_2');
        });

        DB::statement("
            UPDATE process_settings
            SET comment = REPLACE(comment, 'cast(RS_CITY as text) as CITY', 'cast(RS_ADDRESS_LINE_2 as text) as ADDRESS_LINE_2')
            WHERE process = 'daas_referralsource_master';
        ");
    }
};
