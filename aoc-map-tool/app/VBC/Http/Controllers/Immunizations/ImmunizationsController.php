<?php

namespace App\VBC\Http\Controllers\Immunizations;

use App\Http\Controllers\Controller;
use App\VBC\Http\Requests\Immunization\GetImmunizationsRequest;
use App\VBC\Http\Resources\Immunization\AllImmunizationCollection;
use App\VBC\Http\Resources\Immunization\ImmunizationBucketCollection;
use App\VBC\Repositories\Immunization\ImmunizationReadRepository;
use App\VBC\Services\Immunization\ImmunizationExportService;
use Illuminate\Contracts\Support\Renderable;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImmunizationsController extends Controller
{
    public function __construct(
        private readonly ImmunizationReadRepository $repository,
        private readonly ImmunizationExportService $exportService,
    ) {
    }

    public function index(): Renderable
    {
        return view('vbc.immunization.index');
    }

    public function getAllImmunizations(GetImmunizationsRequest $request): AllImmunizationCollection
    {
        return new AllImmunizationCollection($this->repository->getPaginated($request->toDto()));
    }

    /** Immunizations bucket widget on the dashboard. Planned doses only; the scope lives in the repository. */
    public function getImmunizationBucket(GetImmunizationsRequest $request): ImmunizationBucketCollection
    {
        return new ImmunizationBucketCollection($this->repository->getBucketPaginated($request->toDto()));
    }

    public function exportAllImmunizations(GetImmunizationsRequest $request): StreamedResponse
    {
        return $this->exportService->export($request->toDto());
    }
}
