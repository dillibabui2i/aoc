<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastUpdatedCountInMassMessageTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mass_message', function (Blueprint $table) {
            $table->integer('last_updated_count')->comment("count where send sms stopped")->after('message')->default(0);
            $table->integer('total_count')->comment("count where send sms stopped")->after('last_updated_count')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mass_message', function (Blueprint $table) {
            $table->dropColumn('last_updated_count');
            $table->dropColumn('total_count');
        });
    }
}
