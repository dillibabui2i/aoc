<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMobileAppMenuItemsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mobile_app_menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('menu_item_key')->comment('Unique for each menu')->nullable();
            $table->string('menu_item_value')->comment('Menu name to display on the mobile app')->nullable();
            $table->integer('sort_order')->comment('Menu item display position')->default(1);
            $table->integer('parent_id')->comment('Id of the parent menu ')->default(0);
            $table->boolean('status')->comment('1=Active, 0=Inactive')->default(1);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mobile_app_menu_items');
    }
}
