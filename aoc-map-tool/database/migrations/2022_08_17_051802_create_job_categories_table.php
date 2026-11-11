<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateJobCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     * @author Nayan 17 Aug 2022
     * @return void
     */
    public function up()
    {
        Schema::create('job_categories', function (Blueprint $table) {
            $table->id();
            $table->string('State', 20)->nullable()->default('');
            $table->string('Type', 100)->nullable()->default('');
            $table->string('category', 255)->nullable()->default('');
            $table->timestamp('created_at')->useCurrent();
            $table->bigInteger('created_by')->nullable()->comment('Users ID Ref who created the record');
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'));
            $table->bigInteger('updated_by')->nullable()->comment('Users ID Ref who updated the record');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('job_categories');
    }
}
