<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateLoginRestrictions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('login_restrictions', function (Blueprint $table) {
            $table->boolean('blacklisted')->nullable()->change();
            $table->boolean('whitelisted')->nullable()->change();
            $table->string('username', 255)->nullable()->change();
            $table->bigInteger('attempts')->nullable()->change();
            $table->bigInteger('created_by')->nullable()->change();
            $table->bigInteger('updated_by')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
