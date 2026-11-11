<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $categories = DB::table('class_categories')->pluck('id', 'name');
        foreach ($categories as $name => $id) {
            DB::table('trach_vent_classes')->where('type', $name)->update(['type' => $id]);
        }

        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->unsignedBigInteger('type')->change();
        });

        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->foreign('type')->references('id')->on('class_categories')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->dropForeign(['type']);
        });

        Schema::table('trach_vent_classes', function (Blueprint $table) {
            $table->string('type')->change();
        });

        $categories = DB::table('class_categories')->pluck('name', 'id');
        foreach ($categories as $id => $name) {
            DB::table('trach_vent_classes')->where('type', $id)->update(['type' => $name]);
        }
    }
};
