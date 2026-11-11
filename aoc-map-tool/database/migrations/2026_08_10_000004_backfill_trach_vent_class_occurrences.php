<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Classes created before recurrence support had their single class_date stored directly
        // on trach_vent_classes. Give each of them the one occurrence row the new model expects,
        // leaving override fields null so future class-level edits continue to flow through.
        $classesWithoutOccurrences = DB::table('trach_vent_classes')
            ->leftJoin('trach_vent_class_occurrences', 'trach_vent_class_occurrences.class_id', '=', 'trach_vent_classes.id')
            ->whereNull('trach_vent_class_occurrences.id')
            ->whereNotNull('trach_vent_classes.class_date')
            ->select('trach_vent_classes.*')
            ->get();

        $now = now();

        foreach ($classesWithoutOccurrences as $class) {
            $occurrenceId = DB::table('trach_vent_class_occurrences')->insertGetId([
                'class_id' => $class->id,
                'class_date' => $class->class_date,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            // Legacy classes were always one-off: leave repeat_every/repeat_on/repeat_until null.

            // Old requests pointed at the class directly; now they must point at its occurrence.
            DB::table('trach_vent_class_request')
                ->where('trach_vent_class_id', $class->id)
                ->update(['trach_vent_class_occurrence_id' => $occurrenceId]);
        }
    }

    public function down(): void
    {
        // Not reversible: we can't tell which occurrence rows were backfilled by this
        // migration versus created normally afterward.
    }
};
