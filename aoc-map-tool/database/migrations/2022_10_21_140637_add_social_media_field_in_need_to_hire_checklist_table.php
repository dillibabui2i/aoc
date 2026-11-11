<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSocialMediaFieldInNeedToHireChecklistTable extends Migration
{
    /**
     * Run the migrations.
     * @author Nayan
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Checklist', function (Blueprint $table) {
            //
            $table->integer('SocialMediaPostsINT')->after('SocialMediaAllTime')->default(0);
            $table->integer('SocialMediaPostsAllatime')->after('SocialMediaPostsINT')->default(0);

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('NeedToHire_Checklist', function (Blueprint $table) {
            //
            $table->dropColumn('SocialMediaPostsINT');
            $table->dropColumn('SocialMediaPostsINT');
        });
    }
}
