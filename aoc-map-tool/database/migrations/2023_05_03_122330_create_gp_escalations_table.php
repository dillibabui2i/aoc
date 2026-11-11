<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGpEscalationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('gp_escalations', function (Blueprint $table) {
            $table->id();
            $table->integer('payer_id');
            $table->string('payer_group');
            $table->string('state');
            $table->string('service', 10);
            $table->string('Level1')->nullable();
            $table->string('Level2')->nullable();
            $table->string('Level3')->nullable();
            $table->string('Level4')->nullable();
            $table->string('Level5')->nullable();
            $table->string('Level6')->nullable();
            $table->string('Level7')->nullable();
            $table->string('Level8')->nullable();
            $table->string('Level9')->nullable();
            $table->string('Level10')->nullable();
            $table->string('Level11')->nullable();
            $table->string('Level12')->nullable();
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
        Schema::dropIfExists('gp_escalations');
    }
}
