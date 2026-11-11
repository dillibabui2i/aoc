<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableForRenewablesTypes extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renewables_types', function (Blueprint $table) {
            $table->id();
            $table->string('name','100')->nullable();
            $table->text('description')->nullable();
            $table->integer('created_by')->nullable()->comment('Users ID Ref');
            $table->integer('updated_by')->nullable()->comment('Users ID Ref');
            $table->timestamps();
            $table->boolean('is_deleted')->default(false);
            $table->boolean('is_updated')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('renewables_types');
    }
}
