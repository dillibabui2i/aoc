<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('agencyAccessEmails', 255);
            $table->text('applyFormId');
            $table->string('applyLink', 500);
            $table->string('bonus', 50);
            $table->text('briefDescription');
            $table->string('category', 255);
            $table->datetime('closeDate');
            $table->string('company', 255);
            $table->string('companyId', 50);
            $table->string('createdBy', 255);
            $table->json('createdByJson');
            $table->text('description');
            $table->string('detailLink', 500);
            $table->boolean('distribution');
            $table->string('eId', 50);
            $table->string('eeoCategory', 500);
            $table->string('evaluationFormName', 255);
            $table->string('hiringManagers', 500);
            $table->json('hiringManagersJson');
            $table->boolean('internalOnly');
            $table->string('jobSource', 255);
            $table->string('jobState', 50);
            $table->datetime('lastUpdatedDate');
            $table->string('location', 255);
            $table->string('locationCity', 255);
            $table->string('locationCountry', 255);
            $table->string('locationEId', 50);
            $table->string('locationPostalCode', 50);
            $table->string('locationState', 100);
            $table->string('openings', 255);
            $table->json('jobLocations');
            $table->string('jobType', 255);
            $table->string('region', 255);
            $table->json('customField');
            $table->string('department', 255);
            $table->string('preInterviewFormName', 255);
            $table->json('otherLocations');
            $table->json('postingType', 100);
            $table->string('primaryHiringManager', 255);
            $table->json('primaryHiringManagerJson');
            $table->string('primaryHiringManagerEmail', 255);
            $table->string('primaryRecruiter', 255);
            $table->json('primaryRecruiterJson');
            $table->string('primaryRecruiterEmail', 255);
            $table->boolean('private');
            $table->json('recruiters');
            $table->string('requisitionId', 50);
            $table->datetime('sentDate');
            $table->string('title', 255);
            $table->string('workflow', 100);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
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
        //
    }
}
