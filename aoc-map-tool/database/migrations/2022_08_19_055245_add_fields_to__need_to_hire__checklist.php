<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFieldsToNeedToHireChecklist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('NeedToHire_Checklist', function (Blueprint $table) {
            $table->integer('TextBlastAllTime')->after('TextBlastINT')->default(0);
            $table->dateTime('TextBlastStarted')->after('TextBlastAllTime')->nullable();
            $table->dateTime('TextBlastWorked')->after('TextBlastStarted')->nullable();
            $table->integer('PETAllTime')->after('PETINT')->default(0);
            $table->dateTime('PETStarted')->after('PETAllTime')->nullable();
            $table->dateTime('PETWorked')->after('PETStarted')->nullable();
            $table->integer('SocialMediaAllTime')->after('SocialMediaINT')->default(0);
            $table->dateTime('SocialMediaStarted')->after('SocialMediaAllTime')->nullable();
            $table->dateTime('SocialMediaWorked')->after('SocialMediaStarted')->nullable();
            $table->integer('PrivateMessageAllTime')->after('PrivateMessageINT')->default(0);
            $table->dateTime('PrivateMessageStarted')->after('PrivateMessageAllTime')->nullable();
            $table->dateTime('PrivateMessageWorked')->after('PrivateMessageStarted')->nullable();
            $table->integer('LinkedINAllTime')->after('LinkedININT')->default(0);
            $table->dateTime('LinkedINStarted')->after('LinkedINAllTime')->nullable();
            $table->dateTime('LinkedINWorked')->after('LinkedINStarted')->nullable();
            $table->integer('IndeedAllTime')->after('IndeedINT')->default(0);
            $table->dateTime('IndeedStarted')->after('IndeedAllTime')->nullable();
            $table->dateTime('IndeedWorked')->after('IndeedStarted')->nullable();
            $table->integer('ColdCallAllTime')->after('ColdCallINT')->default(0);
            $table->dateTime('ColdCallStarted')->after('ColdCallAllTime')->nullable();
            $table->dateTime('ColdCallWorked')->after('ColdCallStarted')->nullable();
            $table->integer('SnailMailAllTime')->after('SnailMailINT')->default(0);
            $table->dateTime('SnailMailStarted')->after('SnailMailAllTime')->nullable();
            $table->dateTime('SnailMailWorked')->after('SnailMailStarted')->nullable();
            $table->integer('BONNursePMAllTime')->after('BONNursePMINT')->default(0);
            $table->dateTime('BONNursePMStarted')->after('BONNursePMAllTime')->nullable();
            $table->dateTime('BONNursePMWorked')->after('BONNursePMStarted')->nullable();
            $table->integer('SocialMediaSponsoredAllTime')->after('SocialMediaSponsoredDate')->default(0);
            $table->dateTime('SocialMediaSponsoredStarted')->after('SocialMediaSponsoredAllTime')->nullable();
            $table->dateTime('SocialMediaSponsoredWorked')->after('SocialMediaSponsoredStarted')->nullable();
            $table->integer('EscalatedAllTime')->after('EscalatedDate')->default(0);
            $table->dateTime('EscalatedStarted')->after('EscalatedAllTime')->nullable();
            $table->dateTime('EscalatedWorked')->after('EscalatedStarted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('_need_to_hire__checklist', function (Blueprint $table) {
            //
        });
    }
}
