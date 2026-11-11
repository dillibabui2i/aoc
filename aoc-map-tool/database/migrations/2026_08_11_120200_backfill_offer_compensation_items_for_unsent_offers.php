<?php

use App\Models\Offer;
use App\Models\OfferField;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    // Plain compensation keys that map 1:1 to one dynamic item each.
    private const SIMPLE_KEYS = [
        'evaluation_and_intake', 're_evaluation', 'routine_visit', 'supervision_visit',
        're_evaluation_and_supervision_visit', 'meetings',
        '1_unit_visit', '2_unit_visit', '3_unit_visit', '4_unit_visit',
    ];

    public function up(): void
    {
        $fieldIdsByKey = OfferField::pluck('id', 'key');
        $signOnBonusFieldId = $fieldIdsByKey['sign_on_bonus'] ?? null;

        $offers = Offer::where('status', '!=', Offer::STATUS_SENT)
            ->with('values')
            ->get();

        foreach ($offers as $offer) {
            foreach ([0, 1] as $version) {
                $valuesByKey = $offer->values->where('version', $version)->keyBy('key');
                $rows = [];
                $sortOrder = 0;

                foreach (self::SIMPLE_KEYS as $key) {
                    $value = $valuesByKey->get($key);

                    if (!$value || (int) $value->value_int === 0) {
                        continue;
                    }

                    $rows[] = [
                        'offer_id' => $offer->id,
                        'version' => $version,
                        'offer_field_id' => $fieldIdsByKey[$key] ?? null,
                        'custom_label' => null,
                        'amount' => (int) $value->value_int,
                        'notes' => null,
                        'sort_order' => $sortOrder++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                $bonus = $valuesByKey->get('bonus');

                if ($bonus && (int) $bonus->value_int !== 0 && $signOnBonusFieldId) {
                    $installment = $valuesByKey->get('20_visits');
                    $notes = '$500 to be paid at 90 days.';

                    if ($installment && (int) $installment->value_int !== 0) {
                        $notes .= sprintf(
                            ' An additional $%d to be paid at 6 months if full time status of 80 visits completed a month has been achieved.',
                            (int) $installment->value_int
                        );
                    }

                    $rows[] = [
                        'offer_id' => $offer->id,
                        'version' => $version,
                        'offer_field_id' => $signOnBonusFieldId,
                        'custom_label' => null,
                        'amount' => (int) $bonus->value_int,
                        'notes' => $notes,
                        'sort_order' => $sortOrder++,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                if (!empty($rows)) {
                    DB::table('offer_compensation_items')->insert($rows);
                }
            }
        }
    }

    public function down(): void
    {
        $unsentOfferIds = Offer::where('status', '!=', Offer::STATUS_SENT)->pluck('id');
        DB::table('offer_compensation_items')->whereIn('offer_id', $unsentOfferIds)->delete();
    }
};
