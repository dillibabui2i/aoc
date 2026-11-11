<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReasonMoveRemainingBudgetFieldsToSocialMediaAdsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('social_media_ads', function (Blueprint $table) {
            $table->string('ads_close_reason', 255)->default('');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('social_media_ads', function (Blueprint $table) {
            $table->dropColumn('ads_close_reason');
        });
    }
}
