<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableSocialMediaAds extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_media_ads', function (Blueprint $table) {
            $table->id();
            $table->integer('aoc_nthaccounts_id')->nullable();
            $table->string('discipline', 50)->nullable();
            $table->text('skills_diagnosis')->nullable();
            $table->text('shifts_needed')->nullable();
            $table->text('the_sale')->nullable();
            $table->string('ad_name', 250)->nullable();
            $table->date('start_date')->nullable();
            $table->string('state', 250)->nullable();
            $table->string('city', 250)->nullable();
            $table->string('budget', 50)->nullable();
            $table->boolean('ad_placed')->nullable();
            $table->string('actual_spend', 50)->nullable();
            $table->integer('messages_received')->nullable();
            $table->integer('reach')->nullable();
            $table->integer('post_engagement')->nullable();
            $table->integer('link_clicks')->nullable();
            $table->string('cost_per_link', 200)->nullable();
            $table->string('cost_per_message', 200)->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->boolean('is_deleted')->default(0);
            $table->boolean('completed')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('social_media_ads');
    }
}
