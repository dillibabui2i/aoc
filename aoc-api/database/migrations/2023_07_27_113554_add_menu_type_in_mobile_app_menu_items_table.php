<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMenuTypeInMobileAppMenuItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mobile_app_menu_items', function (Blueprint $table) {
            $table->tinyInteger('menu_type')->comment('1= parent, 2=clinician')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('mobile_app_menu_items', function (Blueprint $table) {
            $table->dropColumn('menu_type');
        });
    }
}
