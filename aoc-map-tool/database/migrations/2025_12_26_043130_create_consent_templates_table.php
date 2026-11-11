<?php

use App\Enums\ConsentTemplates\StatusEnum;
use App\Enums\ConsentTemplates\TypeEnum;
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
    public function up(): void
    {
        Schema::create('consent_templates', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->decimal('version', 4, 1)->nullable();
            $table->string('status', 20)->default(StatusEnum::DRAFT->value);
            $table->string('language', 10);
            $table->longText('content');
            $table->unsignedBigInteger('created_by');

            $table->timestamps();
            $table->dateTime('activated_at')->nullable();
            $table->foreign('created_by')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::table('consent_templates', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });

        Schema::dropIfExists('consent_templates');
    }
};
