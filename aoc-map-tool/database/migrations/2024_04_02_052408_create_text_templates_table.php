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
        Schema::create('text_templates', function (Blueprint $table) {
            $table->id();
            $table->string('department');
            $table->string('category');
            $table->string('template_name');
            $table->longtext('template');
            $table->datetime('created_at');
            $table->integer('created_by');
            $table->datetime('updated_at');
            $table->integer('updated_by');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('text_templates');
    }
};
