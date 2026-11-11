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
        Schema::table('gross_margin_configurations', function (Blueprint $table) {
            $table->integer('lob_enum')->nullable()->after('payer_id');
            $table->dropForeign(['lob_id']);
            $table->integer('lob_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('gross_margin_configurations', function (Blueprint $table) {
            $table->integer('lob_id')->nullable(false)->change();
            $table->foreign('lob_id')
                ->references('LOBID')
                ->on('lob')
                ->cascadeOnDelete();
            $table->dropColumn('lob_enum');
        });
    }
};
