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
        Schema::table('candidates', function (Blueprint $table) {
            if (!Schema::hasColumn('candidates', 'outlookEventOwnerId')) {
                $table->unsignedBigInteger('outlookEventOwnerId')->nullable()->after('outlookEventId');
            }
            if (!Schema::hasColumn('candidates', 'outlookEventICalUId')) {
                $table->string('outlookEventICalUId')->nullable()->after('outlookEventOwnerId');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('candidates', function (Blueprint $table) {
            if (Schema::hasColumn('candidates', 'outlookEventOwnerId')) {
                $table->dropColumn('outlookEventOwnerId');
            }
            if (Schema::hasColumn('candidates', 'outlookEventICalUId')) {
                $table->dropColumn('outlookEventICalUId');
            }
        });
    }
};