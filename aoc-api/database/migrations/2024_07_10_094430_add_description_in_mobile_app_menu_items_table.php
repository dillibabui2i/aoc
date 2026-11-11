<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDescriptionInMobileAppMenuItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('mobile_app_menu_items', function (Blueprint $table) {
            $table->string('description')->comment('Page Information')->after('menu_item_value');
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
            $table->dropColumn('description');
        });
    }
}
