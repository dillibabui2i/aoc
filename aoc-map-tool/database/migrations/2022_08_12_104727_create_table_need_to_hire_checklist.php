<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTableNeedToHireChecklist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('NeedToHire_Checklist', function (Blueprint $table) {
            $table->id();
			$table->integer('NeedToHireAccountsID')->nullable();
			$table->boolean('HUBcity')->default(0);
			$table->boolean('TextBlast')->default(0);
			$table->integer('TextBlastINT')->nullable();
			$table->boolean('PET')->default(0);
			$table->integer('PETINT')->nullable();
			$table->boolean('SocialMedia')->default(0);
			$table->integer('SocialMediaINT')->nullable();
			$table->boolean('PrivateMessage')->default(0);
			$table->integer('PrivateMessageINT')->nullable();
			$table->boolean('LinkedIN')->default(0);
			$table->integer('LinkedININT')->nullable();
			$table->boolean('Indeed')->default(0);
			$table->integer('IndeedINT')->nullable();
			$table->boolean('ColdCall')->default(0);
			$table->integer('ColdCallINT')->nullable();
			$table->boolean('SnailMail')->default(0);
			$table->integer('SnailMailINT')->nullable();
			$table->boolean('BONNursePM')->default(0);
			$table->integer('BONNursePMINT')->nullable();
			$table->boolean('SocialMediaSponsored')->default(0);
			$table->integer('SocialMediaSponsoredDATE')->nullable();
			$table->boolean('Escalated')->default(0);
			$table->integer('EscalatedDATE')->nullable();
            $table->timestamp('CreatedOn')->useCurrent();
			$table->integer('CreatedBy')->nullable();
            $table->timestamp('ModifiedOn')->useCurrent();
            $table->integer('ModifiedBy')->nullable();
			$table->boolean('is_deleted')->default(0);
			$table->boolean('is_updated')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('NeedToHire_Checklist');
    }
}
