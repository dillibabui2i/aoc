<?php

use App\Models\Offer;
use App\Models\OfferValue;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $offers = Offer::where('status', '!=', Offer::STATUS_SENT)->get();

        $keys = ['1_unit_visit', '2_unit_visit', '3_unit_visit', '4_unit_visit'];

        foreach ($offers as $offer) {
            $existingKeys = $offer->values()->whereIn('key', $keys)->pluck('key')->toArray();

            $rows = [];
            foreach ($keys as $key) {
                if (in_array($key, $existingKeys)) {
                    continue;
                }

                $rows[] = ['offer_id' => $offer->id, 'key' => $key, 'version' => 0];
                $rows[] = ['offer_id' => $offer->id, 'key' => $key, 'version' => 1];
            }

            if (!empty($rows)) {
                OfferValue::insert($rows);
            }
        }
    }

    public function down(): void
    {
        //
    }
};