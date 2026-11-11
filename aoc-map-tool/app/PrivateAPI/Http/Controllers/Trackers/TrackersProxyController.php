<?php

namespace App\PrivateAPI\Http\Controllers\Trackers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Dashboard;
use App\Repositories\Notes\NotesWriteRepository;
use App\Services\FirstAdvantage\FirstAdvantageService;
use App\Services\MssqlRecruiterTracker\TrackerHoursEligibilityService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Throwable;

class TrackersProxyController extends Controller
{
    public function __construct(
        private readonly Dashboard $dashboardController,
        private readonly FirstAdvantageService $advantageService,
        private readonly NotesWriteRepository $notesWriteRepository,
        private readonly TrackerHoursEligibilityService $trackerHoursEligibilityService,
    ) {
    }

    public function create(Request $request): Response
    {
        return $this->proxyToSaveScheduleNext($request);
    }

    public function save(Request $request): Response
    {
        return $this->proxyToSaveScheduleNext($request);
    }

    private function proxyToSaveScheduleNext(Request $request): Response
    {
        try {
            $result = $this->dashboardController->saveScheduleNext(
                $request,
                $this->advantageService,
                $this->notesWriteRepository,
                $this->trackerHoursEligibilityService,
            );
            $data = json_decode($result->getContent(), true);

            if (!empty($data['error']) || (isset($data['success']) && $data['success'] != 1)) {
                return response()->noContent(Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->noContent(Response::HTTP_OK);
        } catch (Throwable $e) {
            // saveScheduleNext is legacy and fails by throwing (a missing request key it indexes
            // directly is enough), so keep a trace - the response itself carries no body.
            Log::error('Trackers proxy -> saveScheduleNext failed', [
                'route' => $request->route()?->getName(),
                'payload' => $request->all(),
                'exception' => $e,
            ]);

            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
