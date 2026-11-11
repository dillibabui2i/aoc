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
        Schema::table('supply_requests', function (Blueprint $table) {
            $table->tinyInteger('status')->default(0)->comment('0=New,1=Ordered,2=Completed');
            $table->dateTime('ordered_at');
            $table->integer('ordered_by');
            $table->boolean('acknowledged')->default(false);
            $table->dateTime('acknowledged_at');
            $table->integer('acknowledged_by');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {}
};
