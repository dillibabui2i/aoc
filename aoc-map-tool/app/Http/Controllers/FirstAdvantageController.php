<?php

namespace App\Http\Controllers;

use App\Candidates;
use App\MssqlProviders;
use App\FirstAdvantageCheck;
use App\FirstAdvantageErrors;
use App\RecruitingSchedule;
use App\Repositories\OfficeNeo\OfficeNeoFAHistoryReadRepository;
use App\Services\FirstAdvantage\RetryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\FirstAdvantageCheckRequest;

class FirstAdvantageController extends Controller
{
    public function webhook(FirstAdvantageCheckRequest $request, FirstAdvantageCheck $firstAdvantageCheck)
    {
        $entity = $request->getEntity();
        $candidateId = $entity->extractFICandidateId();

        if (is_null($candidateId)) {
            Log::error('First advantage error', [
                'method' => 'First advantage controller -> webhook',
                'response' => 'candidate id is NULL'
            ]);
        }

        if ($firstAdvantageCheck->hasFACheck($candidateId)) {
            $firstAdvantageCheck->updateFields($candidateId, $entity->getMappedData());
        }

        Log::info('First advantage [info]', [
            'method' => 'First advantage controller -> webhook',
            'body' => json_encode($request->all())
        ]);

        return response('OK');
    }

    public function retry(Request $request, RetryService $retryService, FirstAdvantageErrors $firstAdvantageErrorsModel, FirstAdvantageCheck $firstAdvantageCheckModel)
    {
        if ($request->get('type') === 'neo') {
            $type = 'neo';
            $id = $request->get('id');
        } else {
            $schedule = RecruitingSchedule::find($request->get('id'));
            $type = 'provider';
            $id = $schedule->aoc_provider_id;

            if (!empty($schedule->aoc_candidate_id)) {
                $type = 'candidate';
                $id = $schedule->aoc_candidate_id;
            }
        }

        if ($type === 'provider') {
            $p = MssqlProviders::find($id);
            $searchIds = MssqlProviders::where('AOC_Email', '=', $p->AOC_Email)->pluck('id')->toArray();
        } else {
            $p = Candidates::find($id);
            $searchIds = Candidates::where('email', '=', $p->email)->pluck('id')->toArray();
        }

        foreach ($searchIds as $searchId) {
            $firstAdvantageErrorsModel->deleteAll($searchId, $type);
        }

        foreach ($searchIds as $searchId) {
            $r = $firstAdvantageCheckModel->hasInitiated($searchId, $type);

            if ($r) {
                continue;
            }

            $id = $searchId;
        }

        $retryService->retry($id, $type);

        $firstAdvantageError = $firstAdvantageErrorsModel->getLatestError($id, $type);

        return view('firstAdvantage.partials.background-check-error', [
            'lastAvailableError' => $firstAdvantageError
        ]);
    }

    public function getStatuses(Request $request, FirstAdvantageCheck $firstAdvantageCheck, OfficeNeoFAHistoryReadRepository $officeNeoFAHistoryReadRepository)
    {
        if ($request->get('type') === 'neo') {
            $id = $request->get('id');
            $type = 'neo';
        } else {
            $schedule = RecruitingSchedule::find($request->get('id'));
            $type = 'provider';
            $id = $schedule->aoc_provider_id;

            if (!empty($schedule->aoc_candidate_id)) {
                $type = 'candidate';
                $id = $schedule->aoc_candidate_id;
            }
        }

        if ($type === 'provider') {
            $p = MssqlProviders::find($id);
            $searchIds = MssqlProviders::where('AOC_Email', '=', $p->AOC_Email)->pluck('id')->toArray();
        } else {
            $p = Candidates::find($id);
            $searchIds = Candidates::where('email', '=', $p->email)->pluck('id')->toArray();
        }

        foreach ($searchIds as $searchId) {
            $faCheck = $firstAdvantageCheck->findByScheduleIDAndType($searchId, $type);

            if (is_array($faCheck)) {
                return $faCheck;
            }
        }

        return $firstAdvantageCheck->findByScheduleIDAndType($id, $type);
    }

    public function getErrors(Request $request, FirstAdvantageErrors $firstAdvantageErrorsModel, OfficeNeoFAHistoryReadRepository $officeNeoFAHistoryReadRepository)
    {
        if ($request->get('type') === 'neo') {
            $id = $request->get('id');
            $type = 'neo';
        } else {
            $type = 'provider';
            $schedule = RecruitingSchedule::find($request->get('id'));
            $id = $schedule->aoc_provider_id;

            if (!empty($schedule->aoc_candidate_id)) {
                $type = 'candidate';
                $id = $schedule->aoc_candidate_id;
            }
        }

        if ($type === 'provider') {
            $p = MssqlProviders::find($id);
            $searchIds = MssqlProviders::where('AOC_Email', '=', $p->AOC_Email)->pluck('id')->toArray();
        } else {
            $p = Candidates::find($id);
            $searchIds = Candidates::where('email', '=', $p->email)->pluck('id')->toArray();
        }

        foreach ($searchIds as $searchId) {
            $faCheck = $firstAdvantageErrorsModel->getLatestError($searchId, $type);

            if (!is_null($faCheck)) {
                return view('firstAdvantage.partials.background-check-error', [
                    'lastAvailableError' => $faCheck
                ]);
            }
        }

        return view('firstAdvantage.partials.background-check-error', [
            'lastAvailableError' => $firstAdvantageErrorsModel->getLatestError($id, $type)
        ]);
    }
}
