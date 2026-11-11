<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableUserHasPermission extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
		Schema::create('user_has_permission', function (Blueprint $table) {
            $table->unsignedBigInteger('permissions_id');
            $table->unsignedBigInteger('user_id');

            $table->foreign('permissions_id')
                ->references('id')
                ->on('permissions')
                ->onDelete('cascade');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');

            $table->primary(['permissions_id', 'user_id'], 'user_has_permissions_permission_id_user_id_primary');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_has_permission');
    }
}
