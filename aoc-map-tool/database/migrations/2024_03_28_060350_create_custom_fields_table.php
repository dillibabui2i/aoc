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
        Schema::create('custom_fields', function (Blueprint $table) {
            $table->id();
            $table->string('entity', 20);
            $table->string('field_name', 100);
            $table->string('category', 100);
            $table->string('db_field_name', 150);
            $table->string('input_type', 20);
            $table->string('frontend_label', 255);
            $table->string('frontend_instruction', 500);
            $table->string('default_value', 255);
            $table->integer('max_length');
            $table->boolean('is_required');
            $table->string('backend_type', 20);
            $table->text('options');
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
        Schema::dropIfExists('custom_fields');
    }
};
