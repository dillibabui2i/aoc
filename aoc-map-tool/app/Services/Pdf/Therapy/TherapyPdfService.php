<?php

namespace App\Services\Pdf\Therapy;

use App\Services\Pdf\Therapy\Contracts\TherapyPdfStepInterface;
use App\Services\Pdf\Therapy\Steps\ClinicalInterviewPdfStep;
use App\Services\Pdf\Therapy\Steps\OfferExtendedPdfStep;
use App\Services\Pdf\Therapy\Steps\RecruiterPhoneScreenPdfStep;
use Illuminate\Http\Response;
use InvalidArgumentException;
use PDF;

class TherapyPdfService
{
    private array $steps = [
        'recruiter_phone_screen' => RecruiterPhoneScreenPdfStep::class,
        'clinical_interview' => ClinicalInterviewPdfStep::class,
        'offer_extended' => OfferExtendedPdfStep::class,
    ];

    public function generate(int $candidateId, string $step): Response
    {
        $handler = $this->resolveStep($step);
        $data = $handler->getData($candidateId);

        return PDF::loadView($handler->getTemplate(), $data)
            ->download($handler->getFilename($data));
    }

    public function store(int $candidateId, string $step): string
    {
        $handler = $this->resolveStep($step);
        $data = $handler->getData($candidateId);
        $filename = time() . '_' . $handler->getFilename($data);
        $path = storage_path('documents') . '/' . $filename;

        if (!is_dir(storage_path('documents'))) {
            mkdir(storage_path('documents'), 0755, true);
        }

        PDF::loadView($handler->getTemplate(), $data)->save($path);

        return $filename;
    }

    private function resolveStep(string $step): TherapyPdfStepInterface
    {
        if (!array_key_exists($step, $this->steps)) {
            throw new InvalidArgumentException("Unknown therapy PDF step: [{$step}]");
        }

        return app($this->steps[$step]);
    }
}
