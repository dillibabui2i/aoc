<?php

use App\Enums\ConsentTemplates\TypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('consent_template_logs', function (Blueprint $table) {
            $table->id();
            $table->dateTime('updated_date');
            $table->unsignedBigInteger('created_by');

            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();

            $table->enum('document_type', [
                TypeEnum::PRIVACY_POLICY->value,
                TypeEnum::TERMS_AND_CONDITIONS->value,
            ]);

            $table->enum('action_type', [
                'create',
                'update',
                'delete',
            ]);

            $table->decimal('document_version', 4, 1)->nullable();
            $table->string('document_language', 10);
            $table->text('updated_content');

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
        Schema::dropIfExists('consent_template_logs');
    }
};
