<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hospitalization_tracker_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hospitalization_tracker_id');
            $table->unsignedBigInteger('contact_type_id');
            $table->string('contact_name');
            // At least one of phone/email is required (enforced in the request classes), so both
            // columns are nullable here.
            $table->string('phone_number')->nullable();
            $table->string('email')->nullable();
            $table->dateTime('contact_date');
            // Who logged this contact — always the current user at the moment of creation, never a
            // free-text input and never changed on edit (see HospitalizationTrackerContactService::create()).
            $table->unsignedBigInteger('received_by_id');
            $table->text('contact_notes')->nullable();
            $table->timestamps();

            $table->foreign('hospitalization_tracker_id', 'fk_ht_contacts_tracker_id')
                ->references('id')
                ->on('hospitalization_trackers');
            $table->foreign('contact_type_id', 'fk_ht_contacts_contact_type_id')
                ->references('id')
                ->on('contact_types');
            $table->foreign('received_by_id', 'fk_ht_contacts_received_by_id')
                ->references('id')
                ->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hospitalization_tracker_contacts');
    }
};
