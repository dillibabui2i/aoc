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
        Schema::table('payer_rates', function (Blueprint $table) {
            $table->dropColumn('state');
            $table->dropColumn('discipline');
            $table->dropColumn('rate');
            $table->dropColumn('specRate');
            $table->decimal('lvnlpnrate', 13, 2)->nullable();
            $table->decimal('lvnlpnspecrate', 13, 2)->nullable();
            $table->decimal('rnrate', 13, 2)->nullable();
            $table->decimal('rnspecrate', 13, 2)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('payer_rates', function (Blueprint $table) {
            $table->string('state',255)->nullable();
            $table->string('discipline',50)->nullable();
            $table->decimal('rate', 13, 2)->nullable();
            $table->decimal('specRate', 13, 2)->nullable();
            $table->dropColumn('lvnlpnrate');
            $table->dropColumn('lvnlpnspecrate');
            $table->dropColumn('rnrate');
            $table->dropColumn('rnspecrate');
        });
    }
};
