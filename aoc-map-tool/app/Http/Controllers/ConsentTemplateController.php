<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConsentTrack\FilterConsentRequest;
use App\Http\Requests\ConsentTrack\StoreConsentRequest;
use App\Http\Resources\ConsentTemplate\ConsentTemplateResource;
use App\Services\ConsentTemplate\ConsentTrackingService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class ConsentTemplateController extends Controller
{
    public function __construct(protected ConsentTrackingService $consentService)
    {
    }

    public function index(): Renderable
    {
        $filters = config('consentTrackingTemplate.filters');

        foreach ($filters as &$filter) {
            if ($filter['name'] === 'consent_version') {
                $filter['source'] = $this->consentService->getListOfVersions();
            }
        }
        unset($filter);

        return view('consentTemplateTracking.index', compact('filters'));
    }

    /**
     * @param StoreConsentRequest $request
     * @return JsonResponse
     * @throws \Exception
     */
    public function save(StoreConsentRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        if ($dto->getId()) {
            $this->consentService->update($dto);

            return response()->json(['message' => 'Consent template updated successfully']);
        }

        $this->consentService->create($dto);
        return response()->json(['message' => 'Consent template saved successfully'], Response::HTTP_CREATED);
    }

    /**
     * @param FilterConsentRequest $request
     * @return JsonResponse
     */
    public function data(FilterConsentRequest $request): JsonResponse
    {
        $dto = $request->toDTO();
        return $this->consentService->getData($dto);
    }

    /**
     * @param int $id
     * @return ConsentTemplateResource
     */
    public function show(int $id): ConsentTemplateResource
    {
        return new ConsentTemplateResource($this->consentService->getConsentTemplateById($id));
    }

    /**
     * @param int $id
     * @return JsonResponse
     * @throws \Exception
     */
    public function destroy(int $id): JsonResponse
    {
        $consent = $this->consentService->getConsentTemplateById($id);
        $this->consentService->delete($consent);
        return response()->json(['message' => 'Consent template was deleted successfully']);
    }
}
