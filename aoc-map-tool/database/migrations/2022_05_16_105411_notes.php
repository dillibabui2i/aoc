<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Notes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notes', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 100)->nullable()->default('')->comment('Module Or Table Name');
            $table->integer('entity_id')->nullable()->comment('Table Primary ID for Ref');
            $table->text('notes')->nullable()->default('');
            $table->integer('created_by')->nullable()->comment('Users ID Ref');
            $table->integer('client_id')->nullable()->comment('optionally some notes will link to the original entity');
            $table->integer('clinician_id')->nullable()->comment('optionally some notes will link to the original entity');
            $table->integer('assigned_to')->nullable()->comment('Users ID Ref');
            $table->datetime('due_date')->nullable();
            $table->boolean('completed')->nullable()->default(false);
            $table->datetime('completed_at')->nullable();
            $table->integer('completed_by')->nullable()->comment('Users ID Ref');
            $table->integer('priority')->nullable()->comment('0 - none, 1 - low, 2 - normal, 3 - escalated, 4 - high, 5 - urgent');
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
        //
    }
}
