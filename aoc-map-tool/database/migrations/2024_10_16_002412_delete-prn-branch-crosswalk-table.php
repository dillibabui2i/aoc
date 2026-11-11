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
        Schema::dropIfExists('prn_branch_crosswalk');
        if (Schema::hasColumn('comment_count', 'view_count')){
  
            Schema::table('mssql_patientstatus', function (Blueprint $table) {
                $table->dropColumn('prn_branch');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
