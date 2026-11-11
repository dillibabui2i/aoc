<?php

namespace App\Services\WhiteBoard;

use App\DTO\WhiteBoard\RecruiterData;
use App\DTO\WhiteBoard\RecruiterRowData;
use App\Http\Requests\WhiteBoard\ManageWhiteBoardRequest;
use App\Repositories\Reports\WhiteBoard\TherapyWhiteBoardRepository;
use App\WhiteBoard;
use Carbon\Carbon;

class TherapyWhiteBoardService
{
    public function __construct(protected TherapyWhiteBoardRepository $repository)
    {
    }

    /**
     * @param ManageWhiteBoardRequest $request
     * @return void
     */
    public function saveWhiteBoard(ManageWhiteBoardRequest $request): void
    {
        if ($request->input('primary')) {
            WhiteBoard::where('user_name', $request->user_name)->update(['primary' => 0]);
        }

        if(isset($request->hire_date) && $request->hire_date !='') {
            $request->merge(['hire_date' => date('Y-m-d', strtotime($request->hire_date))]);
        }

        if ($request->input('id')) {
            WhiteBoard::where('id', $request->id)->update($request->except(['_token']));
        } else {
            WhiteBoard::create($request->except(['_token']));
        }
    }

    public function buildTherapyWhiteBoardReport(): array
    {
        $today = Carbon::now();
        $startOfWeek = $today->copy()->startOfWeek(Carbon::MONDAY);

        $days = [
            'Monday' => $startOfWeek->copy(),
            'Tuesday' => $startOfWeek->copy()->addDay(),
            'Wednesday' => $startOfWeek->copy()->addDays(2),
            'Thursday' => $startOfWeek->copy()->addDays(3),
            'Friday' => $startOfWeek->copy()->addDays(4),
            'Saturday' => $startOfWeek->copy()->addDays(5),
            'Sunday' => $startOfWeek->copy()->addDays(6),
        ];

        $data = [];

        foreach ($days as $dayName => $dayDate) {
            $isFuture = $dayDate->gt($today);

            $data[$dayName] = [
                'states' => [],
                'nationalTotals' => $this->emptyMetrics(),
                'disabled' => $isFuture,
                'date' => $dayDate->toDateString(),
            ];

            if ($isFuture) {
                continue;
            }

            $rows = $this->repository->getWhiteboardRowsByDate($dayDate->toDateString());

            file_put_contents(public_path('records.json'), json_encode($rows, JSON_PRETTY_PRINT));

            foreach ($rows as $row) {
                $stateCode = substr($row->row_id, 0, 2);
                $stateName = $stateCode . ' Team Totals';
                $userId = $row->user_id;
                $regionTitle = $row->territory ?? $row->regionname ?? '';

                $rowMetrics = [
                    'candidate_weekly_start_goal' => ($row->candidate_weekly_start_goal ?? null),
                    'phone_screens_scheduled' => (int)($row->PhoneScreen ?? 0),
                    'clinical_interviews_scheduled' => (int)($row->clinicalInterviewsScheduled ?? 0),
                    'offers_extended' => (int)($row->OfferExtended ?? 0),
                    'offers_accepted' => (int)($row->OfferAccepted ?? 0),
                    'offers_rejected' => (int)($row->OfferRejected ?? 0),
                    'hires_onboardings' => (int)($row->neo_date ?? 0),
                    'qualified_candidates' => (int)($row->qualified_candidates ?? 0),
                    'unqualified_candidates' => (int)($row->unqualified_candidates ?? 0),
                    'percent_to_goal' => (int)($row->percent_to_goal ?? 0). '%',
                ];

                if (!isset($data[$dayName]['states'][$stateCode])) {
                    $data[$dayName]['states'][$stateCode] = [
                        'stateName' => $stateName,
                        'metrics' => $this->emptyMetrics(),
                        'recruiters' => [],
                    ];
                }

                if (!isset($data[$dayName]['states'][$stateCode]['recruiters'][$userId])) {
                    $data[$dayName]['states'][$stateCode]['recruiters'][$userId] = [
                        'data' => [
                            'id' => $row->id,
                            'userId' => $row->user_id,
                            'name' => $row->user_name,
                            'region' => $row->regionname ?? '',
                        ],
                        'metrics' => $this->emptyMetrics(),
                        'regions' => [],
                    ];
                }

                $regionKey = md5(($row->regionname ?? '') . '|' . ($row->territory ?? ''));

                if (!isset($data[$dayName]['states'][$stateCode]['recruiters'][$userId]['regions'][$regionKey])) {
                    $data[$dayName]['states'][$stateCode]['recruiters'][$userId]['regions'][$regionKey] = [
                        'rowId' => $row->id,
                        'title' => $regionTitle,
                        'metrics' => $this->emptyMetrics(),
                    ];
                }

                $this->addMetrics(
                    $data[$dayName]['states'][$stateCode]['recruiters'][$userId]['regions'][$regionKey]['metrics'],
                    $rowMetrics
                );

                $this->addMetrics(
                    $data[$dayName]['states'][$stateCode]['recruiters'][$userId]['metrics'],
                    $rowMetrics
                );

                $this->addMetrics(
                    $data[$dayName]['states'][$stateCode]['metrics'],
                    $rowMetrics
                );

                $this->addMetrics(
                    $data[$dayName]['nationalTotals'],
                    $rowMetrics
                );
            }

            foreach ($data[$dayName]['states'] as &$state) {
                $this->finalizeMetrics($state['metrics']);

                uasort($state['recruiters'], fn ($a, $b) =>
                strcmp($a['data']['name'], $b['data']['name'])
                );

                foreach ($state['recruiters'] as &$recruiter) {
                    $this->finalizeMetrics($recruiter['metrics']);

                    uasort($recruiter['regions'], fn ($a, $b) =>
                    strcmp($a['title'], $b['title'])
                    );

                    foreach ($recruiter['regions'] as &$region) {
                        $this->finalizeMetrics($region['metrics']);
                    }
                    unset($region);
                }
                unset($recruiter);
            }
            unset($state);

            $this->finalizeMetrics($data[$dayName]['nationalTotals']);
        }

        return [
            'days' => $days,
            'data' => $data,
        ];
    }

    private function emptyMetrics(): array
    {
        return [
            'candidate_weekly_start_goal' => null,
            'percent_to_goal' => '0%',
            'phone_screens_scheduled' => 0,
            'clinical_interviews_scheduled' => 0,
            'offers_extended' => 0,
            'offers_accepted' => 0,
            'offers_rejected' => 0,
            'hires_onboardings' => 0,
            'qualified_candidates' => 0,
            'unqualified_candidates' => 0,
        ];
    }

    private function addMetrics(array &$target, array $source): void
    {

        if ($source['candidate_weekly_start_goal'] !== null) {
            $target['candidate_weekly_start_goal'] =
                ($target['candidate_weekly_start_goal'] ?? 0)
                + $source['candidate_weekly_start_goal'];
        }

        $target['phone_screens_scheduled'] += (int)($source['phone_screens_scheduled'] ?? 0);
        $target['clinical_interviews_scheduled'] += (int)($source['clinical_interviews_scheduled'] ?? 0);
        $target['offers_extended'] += (int)($source['offers_extended'] ?? 0);
        $target['offers_accepted'] += (int)($source['offers_accepted'] ?? 0);
        $target['offers_rejected'] += (int)($source['offers_rejected'] ?? 0);
        $target['hires_onboardings'] += (int)($source['hires_onboardings'] ?? 0);
        $target['qualified_candidates'] += (int)($source['qualified_candidates'] ?? 0);
        $target['unqualified_candidates'] += (int)($source['unqualified_candidates'] ?? 0);
    }

    private function finalizeMetrics(array &$metrics): void
    {
        $goal = ($metrics['candidate_weekly_start_goal'] ?? null);

//        $actual = (int)($metrics['offers_accepted'] ?? 0);
        $actual = (int)($metrics['hires_onboardings'] ?? 0);

        $metrics['percent_to_goal'] = $goal > 0
            ? round(($actual / $goal) * 100) . '%'
            : '0%';
    }
}
