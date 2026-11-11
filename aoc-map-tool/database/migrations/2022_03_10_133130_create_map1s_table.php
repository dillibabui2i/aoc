<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMap1sTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('map1', function (Blueprint $table) {
            $table->id();
            $table->string('Client')->nullable(true);
            $table->text('Schedule')->nullable(true);
            $table->text('ScheduleText')->nullable(true);
            $table->text('ScheduleFreeText')->nullable(true);
            $table->string('SumofhoursonNTH')->nullable(true);
            $table->string('SumofTotalauthorizedPDNhours')->nullable(true);
            $table->string('SumofTotalauthorizedRespitehours')->nullable(true);
            $table->string('SumofTotalauthorizedCNAhours')->nullable(true);
            $table->string('SumofAge')->nullable(true);
            $table->string('Gender')->nullable(true);
            $table->string('Weight')->nullable(true);
            $table->text('Biography')->nullable(true);
            $table->string('TheSale')->nullable(true);
            $table->string('AttendingSchool')->nullable(true);
            $table->string('Language')->nullable(true);
            $table->string('NurseLanguage')->nullable(true);
            $table->string('PetsinHome')->nullable(true);
            $table->string('PetsNurseCannotOwn')->nullable(true);
            $table->string('Liftingrequirements')->nullable(true);
            $table->string('SpecializedclientBIT')->nullable(true);
            $table->string('Skills')->nullable(true);
            $table->string('Serviceneeded')->nullable(true);
            $table->string('ClinicianGender')->nullable(true);
            $table->string('Cliniciandiscipline')->nullable(true);
            $table->text('Payer')->nullable(true);
            $table->text('RespitePayer')->nullable(true);
            $table->string('IntakeStatus')->nullable(true);
            $table->string('IntakeDate')->nullable(true);
            $table->string('SOCDate')->nullable(true);
            $table->string('Address')->nullable(true);
            $table->string('City')->nullable(true);
            $table->string('Zipcode')->nullable(true);
            $table->string('State')->nullable(true);
            $table->string('Location')->nullable(true);
            $table->string('Phone')->nullable(true);
            $table->string('CreatedOn')->nullable(true);
            $table->string('CreatedReason')->nullable(true);
            $table->string('MeetingNurses')->nullable(true);
            $table->string('WorkForceDevProj')->nullable(true);
            $table->text('NTHNotes')->nullable(true);
            $table->string('Covid19VaccineRequired')->nullable(true);
            $table->string('MediaConsent')->nullable(true);
            $table->string('NonSmokingNurses')->nullable(true);
            $table->string('SmokersLiveinHome')->nullable(true);
            $table->string('lat')->nullable(true);
            $table->string('lng')->nullable(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('map1s');
    }
}
