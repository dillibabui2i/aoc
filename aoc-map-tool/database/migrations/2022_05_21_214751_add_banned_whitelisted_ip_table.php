<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBannedWhitelistedIpTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('login_restrictions', function (Blueprint $table) {
            $table->id();
            $table->boolean('blacklisted');
            $table->boolean('whitelisted');
            $table->ipaddress('ipaddress');
            $table->string('username', 255);
            $table->bigInteger('attempts')->length(11)->unsigned();
            $table->bigInteger('created_by')->length(11)->unsigned();
            $table->bigInteger('updated_by')->length(11)->unsigned();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
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
