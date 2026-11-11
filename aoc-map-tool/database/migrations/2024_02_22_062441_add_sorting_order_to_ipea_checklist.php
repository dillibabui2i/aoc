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
        Schema::table('ipeaChecklist', function (Blueprint $table) {
            $table->integer('sortOrder')->default(0);
        });
        \DB::statement('UPDATE ipeaChecklist SET sortOrder = id');
        \DB::statement('UPDATE ipeaChecklist SET title = "kanTime" where title ="kantime"');
        \DB::statement('UPDATE ipeaChecklist SET sortOrder = 26 where id = 25');
        DB::table('ipeaChecklist')->insert(['title' => 'kanTime', 'checklist'=>'Upload Payrate Agreement', 'created_by'=>'1' ]);
        \DB::statement('UPDATE ipeaChecklist SET sortOrder = 25 where checklist = "Upload Payrate Agreement"');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('ipea_checklist', function (Blueprint $table) {
            //
        });
    }
};
