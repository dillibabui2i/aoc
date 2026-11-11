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
            $table->decimal('hhagroupprimaryrate', 13, 2)->nullable()->after('denied_at');
            $table->decimal('cnagroupprimaryrate', 13, 2)->nullable()->after('hhagroupprimaryrate');
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
            $table->dropColumn('hhagroupprimaryrate');
            $table->dropColumn('cnagroupprimaryrate');
        });
    }
};
