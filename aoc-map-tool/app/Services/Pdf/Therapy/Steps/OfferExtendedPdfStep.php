<?php

namespace App\Services\Pdf\Therapy\Steps;

use App\Candidates;
use App\Models\Offer;
use App\Models\OfferField;
use App\Services\Pdf\Therapy\Contracts\TherapyPdfStepInterface;
use Illuminate\Support\Str;

class OfferExtendedPdfStep implements TherapyPdfStepInterface
{
    public function getData(int $candidateId): array
    {
        $candidate = Candidates::findOrFail($candidateId);
        $offer = Offer::where('employee_id', $candidateId)
            ->with([
                'originalValues.field',
                'finalValues.field',
                'originalCompensationItems.offerField',
                'finalCompensationItems.offerField',
            ])
            ->firstOrFail();
        $fields = OfferField::orderBy('id')->get()->keyBy('key');

        $originalValues = $offer->originalValues->keyBy('key');
        $finalValues = $offer->finalValues->keyBy('key');
        $originalCompensationItems = $offer->originalCompensationItems;
        $finalCompensationItems = $offer->finalCompensationItems;

        return compact(
            'candidate',
            'offer',
            'fields',
            'originalValues',
            'finalValues',
            'originalCompensationItems',
            'finalCompensationItems'
        );
    }

    public function getTemplate(): string
    {
        return 'pdf.therapy.offer_extended';
    }

    public function getFilename(array $data): string
    {
        return 'offer-extended-' . Str::slug($data['candidate']->fullName) . '.pdf';
    }
}
