<?php

namespace App\VBC\Http\Controllers\Followup;

use App\Http\Controllers\Controller;
use App\VBC\Http\Requests\Followup\CreateFollowupRequest;
use App\VBC\Http\Requests\Followup\GetFollowupsRequest;
use App\VBC\Http\Requests\Followup\SearchFollowupClientsRequest;
use App\VBC\Http\Requests\Followup\UpdateFollowupOutcomeRequest;
use App\VBC\Http\Resources\Followup\FollowupClientSearchResource;
use App\VBC\Http\Resources\Followup\FollowupCollection;
use App\VBC\Http\Resources\Followup\FollowupResource;
use App\VBC\Repositories\Followup\FollowupReadRepository;
use App\VBC\Services\Followup\FollowupCreationService;
use App\VBC\Services\Followup\FollowupOutcomeService;
use App\VBC\Services\Followup\InvalidFollowupOutcomeTransitionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class FollowupController extends Controller
{
    public function __construct(
        private readonly FollowupReadRepository $repository,
        private readonly FollowupCreationService $creationService,
        private readonly FollowupOutcomeService $outcomeService,
    ) {
    }

    public function getFollowups(GetFollowupsRequest $request): FollowupCollection
    {
        $dto = $request->toDto();
        $paginated = $this->repository->getPaginated($dto);

        return new FollowupCollection($paginated);
    }

    /** Autocomplete for the Follow-up modal's "Hospitalized Client Name" field (AC4/AC5). */
    public function searchClients(SearchFollowupClientsRequest $request): ResourceCollection
    {
        return FollowupClientSearchResource::collection($this->repository->searchEligibleClients($request->toDto()));
    }

    /**
     * @throws Throwable
     */
    public function store(CreateFollowupRequest $request): JsonResponse
    {
        $followup = $this->creationService->create($request->toDto());

        return FollowupResource::make($followup)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(int $followupId): JsonResponse
    {
        $followup = $this->repository->findForForm($followupId);

        if (empty($followup)) {
            return response()->json(['message' => 'Follow-up not found.'], Response::HTTP_NOT_FOUND);
        }

        return FollowupResource::make($followup)->response();
    }

    /**
     * @throws Throwable
     */
    public function updateOutcome(UpdateFollowupOutcomeRequest $request, int $followupId): JsonResponse
    {
        $followup = $this->repository->findForForm($followupId);

        if (empty($followup)) {
            return response()->json(['message' => 'Follow-up not found.'], Response::HTTP_NOT_FOUND);
        }

        try {
            $updated = $this->outcomeService->transition($followup, $request->toDto());
        } catch (InvalidFollowupOutcomeTransitionException $e) {
            return response()->json(['message' => $e->getMessage()], Response::HTTP_CONFLICT);
        }

        return FollowupResource::make($updated)->response();
    }
}
