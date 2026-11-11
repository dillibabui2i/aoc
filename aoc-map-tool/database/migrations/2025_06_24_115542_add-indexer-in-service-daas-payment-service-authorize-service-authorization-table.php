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
    public function up()
    {
        Schema::table('service_codes_daas', function(Blueprint $table)
        {
            $table->index('payerid');
        });
        Schema::table('authorizations', function(Blueprint $table)
        {
            $table->index('auth_id');
        });
        Schema::table('authorizations_service', function(Blueprint $table)
        {
            $table->index('authorization_id');
        });
        Schema::table('payment_source', function(Blueprint $table)
        {
            $table->index('payersource_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('service_codes_daas', function(Blueprint $table)
        {
            $table->dropIndex(['payerid']);
        });
        Schema::table('authorizations', function(Blueprint $table)
        {
            $table->dropIndex(['auth_id']);
        });
        Schema::table('authorizations_service', function(Blueprint $table)
        {
            $table->dropIndex(['authorization_id']);
        });
        Schema::table('payment_source', function(Blueprint $table)
        {
            $table->dropIndex(['payersource_id']);
        });
    }
};
