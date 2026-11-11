<?php

namespace App\VBC\Http\Controllers\Immunizations;

use App\Http\Controllers\Controller;
use App\VBC\Http\Requests\Immunization\GetImmunizationSchedulesRequest;
use App\VBC\Http\Requests\Immunization\StoreImmunizationScheduleRequest;
use App\VBC\Http\Requests\Immunization\UpdateImmunizationScheduleRequest;
use App\VBC\Http\Resources\Immunization\ImmunizationScheduleCollection;
use App\VBC\Http\Resources\Immunization\ImmunizationScheduleResource;
use App\VBC\Repositories\Immunization\ImmunizationScheduleReadRepository;
use App\VBC\Services\Immunization\ImmunizationScheduleService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ImmunizationScheduleController extends Controller
{
    public function __construct(
        private readonly ImmunizationScheduleReadRepository $repository,
        private readonly ImmunizationScheduleService $service,
    ) {
    }

    public function index(): Renderable
    {
        return view('vbc.immunization-schedule.index');
    }

    public function getAll(GetImmunizationSchedulesRequest $request): ImmunizationScheduleCollection
    {
        return new ImmunizationScheduleCollection($this->repository->getPaginated($request->toDto()));
    }

    public function show(int $scheduleId): JsonResponse
    {
        $schedule = $this->repository->findForEdit($scheduleId);

        if (empty($schedule)) {
            return response()->json(['message' => 'Immunization schedule not found.'], Response::HTTP_NOT_FOUND);
        }

        return ImmunizationScheduleResource::make($schedule)->response();
    }

    public function store(StoreImmunizationScheduleRequest $request): JsonResponse
    {
        $schedule = $this->service->create($request->toDto());

        return ImmunizationScheduleResource::make($schedule)->response()->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(UpdateImmunizationScheduleRequest $request, int $scheduleId): JsonResponse
    {
        $schedule = $this->repository->findForEdit($scheduleId);

        if (empty($schedule)) {
            return response()->json(['message' => 'Immunization schedule not found.'], Response::HTTP_NOT_FOUND);
        }

        return ImmunizationScheduleResource::make($this->service->update($schedule, $request->toDto()))->response();
    }
}
