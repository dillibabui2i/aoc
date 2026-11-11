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
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->string('entity', 20)->nullable()->change();
            $table->string('field_name', 100)->nullable()->change();
            $table->string('category', 100)->nullable()->change();
            $table->string('db_field_name', 150)->nullable()->change();
            $table->string('input_type', 20)->nullable()->change();
            $table->string('frontend_label', 255)->nullable()->change();
            $table->string('frontend_instruction', 500)->nullable()->change();
            $table->string('default_value', 255)->nullable()->change();
            $table->integer('max_length')->nullable()->change();
            $table->boolean('is_required')->nullable()->change();
            $table->string('backend_type', 20)->nullable()->change();
            $table->text('options')->nullable()->change();
            $table->datetime('created_at')->nullable()->change();
            $table->integer('created_by')->nullable()->change();
            $table->datetime('updated_at')->nullable()->change();
            $table->integer('updated_by')->nullable()->change();
            $table->integer('is_deleted')->nullable()->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('custom_fields', function (Blueprint $table) {
            $table->dropColumn('is_deleted');
        });
    }
};
