<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLobTable extends Migration
{
    /**
     * Run the migrations.
     * @author Nayan
     * @return void
     */
    public function up()
    {
        Schema::create('lob', function (Blueprint $table) {
            $table->id();
            $table->integer('LOBID')->nullable();
            $table->string('LOBName', 250)->nullable()->default('');
            $table->tinyInteger('EMRID')->nullable();
            $table->timestamp('CreateDate')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->default(DB::raw('CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP')); 
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lob');
    }
}
