<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableSocialMediaBudgets extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('social_media_budgets', function (Blueprint $table) {
            $table->id();
            $table->string('category')->nullable()->comment('PDN Ad,Therapy Ad,Academy Ad');
            $table->string('type')->nullable()->comment('Custom Ad,General Ad');
            $table->integer('budget')->nullable();
            $table->string('state')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('table_social_media_budgets');
    }
}
