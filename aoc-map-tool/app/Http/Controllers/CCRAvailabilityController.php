<?php

namespace App\Http\Controllers;

use App\Http\Requests\CCRAvailability\DeleteAvailabilityBlockRequest;
use App\Http\Requests\CCRAvailability\GetAvailabilityRequest;
use App\Http\Requests\CCRAvailability\SaveAvailabilityRequest;
use App\Http\Requests\CCRAvailability\UpdateAvailabilityBlockRequest;
use App\Repositories\CCRAvailability\CCRAvailabilityRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CCRAvailabilityController extends Controller
{
    public function __construct(
        private CCRAvailabilityRepository $availabilityRepository
    ) {
    }

    public function get(GetAvailabilityRequest $request): JsonResponse
    {
        $facilityId = $request->input('facility_id');

        return response()->json([
            'success' => 1,
            'facility_id' => $facilityId,
            'blocks' => $this->availabilityRepository->getBlocks($facilityId)->map(fn ($row) => [
                'id' => $row->id,
                'block_date' => $row->block_date,
                'start_time' => substr($row->start_time, 0, 5),
                'end_time' => substr($row->end_time, 0, 5),
                'note' => $row->note,
            ])->values(),
            'segments' => $this->availabilityRepository->getCapacitySegments($facilityId)->map(fn ($row) => [
                'day_of_week' => (int) $row->day_of_week,
                'start_time' => substr($row->start_time, 0, 5),
                'end_time' => substr($row->end_time, 0, 5),
                'capacity_per_hour' => (int) $row->capacity_per_hour,
            ])->values(),
        ]);
    }

    public function save(SaveAvailabilityRequest $request): JsonResponse
    {
        $this->availabilityRepository->saveForFacility(
            $request->input('facility_id'),
            $request->validated('blocks', []),
            $request->validated('segments', []),
            Auth::user()->id
        );

        return response()->json(['success' => 1, 'message' => 'PRC availability saved successfully.']);
    }

    public function updateBlock(UpdateAvailabilityBlockRequest $request, int $id): JsonResponse
    {
        if ($this->availabilityRepository->findBlock($id) === null) {
            return response()->json(['success' => 0, 'message' => 'This blocked time no longer exists.'], 404);
        }

        $this->availabilityRepository->updateBlock($id, $request->validated(), Auth::user()->id);

        return response()->json(['success' => 1, 'message' => 'Blocked time updated successfully.']);
    }

    public function deleteBlock(DeleteAvailabilityBlockRequest $request, int $id): JsonResponse
    {
        if ($this->availabilityRepository->findBlock($id) === null) {
            return response()->json(['success' => 0, 'message' => 'This blocked time no longer exists.'], 404);
        }

        $this->availabilityRepository->deleteBlock($id);

        return response()->json(['success' => 1, 'message' => 'Blocked time deleted successfully.']);
    }
}
