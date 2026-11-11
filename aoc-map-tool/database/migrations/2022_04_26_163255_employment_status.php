<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EmploymentStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mssql_employmentstatus', function (Blueprint $table) {
            $table->id('id');
            $table->integer('EmploymentStatusID')->nullable();
            $table->string('EmploymentStatus', 50)->nullable()->default('');
            $table->integer('EMRID')->nullable();
            $table->timestamp('CreatedOn')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->boolean('is_updated')->default(0);
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
}
