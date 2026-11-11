<?php

namespace App\VBC\Http\Controllers\CareCoordination;

use App\Http\Controllers\Controller;
use App\VBC\Http\Requests\CareCoordination\GetCareCoordinationRequest;
use App\VBC\Http\Requests\CareCoordination\SearchCareCoordinationClientsRequest;
use App\VBC\Http\Requests\CareCoordination\StoreCareCoordinationRequest;
use App\VBC\Http\Requests\CareCoordination\UpdateCareCoordinationRequest;
use App\VBC\Http\Resources\CareCoordination\CareCoordinationCollection;
use App\VBC\Http\Resources\CareCoordination\CareCoordinationEligibleClientResource;
use App\VBC\Http\Resources\CareCoordination\CareCoordinationResource;
use App\VBC\Repositories\CareCoordination\CareCoordinationReadRepository;
use App\VBC\Repositories\CareCoordination\CareCoordinationWriteRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CareCoordinationController extends Controller
{
    public function __construct(
        private readonly CareCoordinationReadRepository $repository,
        private readonly CareCoordinationWriteRepository $writeRepository,
    ) {
    }

    public function getCareCoordinations(GetCareCoordinationRequest $request): CareCoordinationCollection
    {
        $dto = $request->toDto();
        $paginated = $this->repository->getPaginated($dto);

        return new CareCoordinationCollection($paginated);
    }

    /** Autocomplete for the "Patient" field on the Care Coordination create flow (AC5). */
    public function searchEligibleClients(SearchCareCoordinationClientsRequest $request): ResourceCollection
    {
        $clients = $this->repository->searchEligibleClients($request->validated()['search'], $request->encounterType());

        return CareCoordinationEligibleClientResource::collection($clients);
    }

    /** Options for the "Coordination Type" dropdown. */
    public function getTypes(): JsonResponse
    {
        return response()->json(
            $this->repository->getActiveTypeOptions()->map(fn ($type) => [
                'value' => $type->id,
                'label' => $type->name,
            ])
        );
    }

    public function store(StoreCareCoordinationRequest $request): JsonResponse
    {
        $dto = $request->toDto();
        $careCoordination = $this->writeRepository->create($dto);

        return CareCoordinationResource::make($this->repository->findById($careCoordination->id))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $id): JsonResponse
    {
        $careCoordination = $this->repository->findById($id);

        if (empty($careCoordination)) {
            return response()->json(['message' => 'Care coordination activity not found.'], Response::HTTP_NOT_FOUND);
        }

        return CareCoordinationResource::make($careCoordination)->response();
    }

    public function update(UpdateCareCoordinationRequest $request, int $id): JsonResponse
    {
        $careCoordination = $this->repository->findById($id);

        if (empty($careCoordination)) {
            return response()->json(['message' => 'Care coordination activity not found.'], Response::HTTP_NOT_FOUND);
        }

        $updated = $this->writeRepository->update($careCoordination, $request->toDto());

        return CareCoordinationResource::make($updated)->response();
    }
}
