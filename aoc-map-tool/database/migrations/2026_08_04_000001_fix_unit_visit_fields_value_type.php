<?php

use App\Models\OfferValue;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const KEYS = ['1_unit_visit', '2_unit_visit', '3_unit_visit', '4_unit_visit'];

    public function up(): void
    {
        DB::table('offer_fields')
            ->whereIn('key', self::KEYS)
            ->update(['value_type' => 'decimal', 'last_modified_at' => now()]);

        OfferValue::whereIn('key', self::KEYS)
            ->whereNotNull('value_int')
            ->get()
            ->each(fn (OfferValue $value) => $value->update([
                'value_decimal' => $value->value_int,
                'value_int' => null,
            ]));
    }

    public function down(): void
    {
        DB::table('offer_fields')
            ->whereIn('key', self::KEYS)
            ->update(['value_type' => 'int']);

        OfferValue::whereIn('key', self::KEYS)
            ->whereNotNull('value_decimal')
            ->get()
            ->each(fn (OfferValue $value) => $value->update([
                'value_int' => (int) $value->value_decimal,
                'value_decimal' => null,
            ]));
    }
};
