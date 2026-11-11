<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeFieldTypeInFeatureFlagTable extends Migration
{
    /**
     * Run the migrations.
     * @author Nayan
     * @return void
     */
    public function up()
    {
        Schema::table('feature_flags', function (Blueprint $table) {
            //
            $table->text('users')->nullable(true)->change();
            $table->text('ips')->nullable(true)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('feature_flags', function (Blueprint $table) {
            //
            $table->string('users')->nullable()->change();
            $table->string('ips')->nullable()->change();
        });
    }
}
