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
        Schema::table('special_payer_group', function (Blueprint $table) {
            $table->decimal('SCA_RN_Rate', 8, 2)->after('PatientAccount')->nullable();
            $table->decimal('SCA_RN_SP_Rate', 8, 2)->after('SCA_RN_Rate')->nullable();
            $table->decimal('SCA_LVN_LPN_Rate', 8, 2)->after('SCA_RN_SP_Rate')->nullable();
            $table->decimal('SCA_LVN_LPN_SP_Rate', 8, 2)->after('SCA_LVN_LPN_Rate')->nullable();
            $table->decimal('SCA_CNA_Rate', 8, 2)->after('SCA_LVN_LPN_SP_Rate')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('special_payer_group', function (Blueprint $table) {
            $table->dropColumn('SCA_RN_Rate');
            $table->dropColumn('SCA_RN_SP_Rate');
            $table->dropColumn('SCA_LVN_LPN_Rate');
            $table->dropColumn('SCA_LVN_LPN_SP_Rate');
            $table->dropColumn('SCA_CNA_Rate');
        });
    }
};
