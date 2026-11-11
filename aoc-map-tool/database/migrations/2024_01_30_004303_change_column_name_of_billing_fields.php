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
        Schema::table('mssql_schedulemaster', function (Blueprint $table) {
            $table->renameColumn('reasonNotBilled', 'billingReason');
            $table->renameColumn('reasonNotBilledCreatedAt', 'billingReasonAddedAt');
            $table->renameColumn('reasonNotBilledAddedBy', 'billingReasonAddedBy');
            $table->dateTime('billingResolvedDate')->nullable();
            $table->integer('billingResolvedBy')->nullable()->comment('Users ID Ref');
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
};
