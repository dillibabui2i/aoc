<?php

namespace App\Http\Controllers\V2;

use App\Enums\NRR\NRRTabs;
use App\Http\Controllers\NationalRecruitingReportController as OldController;
use App\Http\Requests\NRR\NationalRecruitingReportRequest;
use App\Services\JobCategory\JobCategoryService;
use App\Services\NationalRecruitingReport\NRRFactoryService;
use App\Services\Reports\ReportLobFilterService;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Log;

class NationalRecruitingReportController extends OldController
{
    /**
     * @param JobCategoryService $jobCategoryService
     * @param NRRFactoryService $nrrFactory
     */
    public function __construct(
         JobCategoryService $jobCategoryService,
        protected NRRFactoryService $nrrFactory,
        protected ReportLobFilterService $lobFilterService,
    ) {
        parent::__construct($jobCategoryService, $lobFilterService);
    }

    /**
     * @param NationalRecruitingReportRequest $request
     * @return View
     * @throws Exception
     */
    public function IPEAs(NationalRecruitingReportRequest $request): Renderable
    {
        try {
            $tab = $request->get('tab') ?? NRRTabs::IPEA->value;
            $params = $request->toDTO();
            $service = $this->nrrFactory->make($tab, $params);
            $result = $service->getTabData();

            return view('reports.recruitingReports', [
                'report' => $result['report'],
                'data' => $result['data'],
                'filters' => $result['filters'],
                'sourceFilters' => $result['sourceFilters'],
                'source' => $result['source'],
            ]);
        } catch (Exception $e) {
            Log::channel('nrr')->error('Exception caught', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * @param NationalRecruitingReportRequest $request
     * @return Factory|View|Application
     * @throws Exception
     */
    public function candidateStarts(NationalRecruitingReportRequest $request): Renderable
    {
        try {
            $params = $request->toDTO();
            $tab = $request->get('tab') ?? NRRTabs::CANDIDATE_STARTS->value;
            $service = $this->nrrFactory->make($tab, $params);
            $result = $service->getTabData();

            return view('reports.recruitingReports', [
                'report' => $result['report'],
                'data' => $result['data'],
                'filters' => $result['filters'],
                'sourceFilters' => $result['sourceFilters'],
                'source' => $result['source']
            ]);
        } catch (Exception $e) {
            Log::channel('nrr')->error('Exception caught', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * @param NationalRecruitingReportRequest $request
     * @return Renderable
     * @throws Exception
     */
    public function grossMargin(NationalRecruitingReportRequest $request): Renderable
    {
        try {
            $params = $request->toDTO();
            $tab = $request->get('tab') ?? NRRTabs::GROSS_MARGIN->value;
            $service = $this->nrrFactory->make($tab, $params);
            $result = $service->getTabData();

            return view('reports.NRR.grosMarginReport', [
                'report' => $result['report'],
                'data' => $result['data'],
                'filters' => $result['filters'],
                'sourceFilters' => $result['sourceFilters'],
                'source' => $result['source']
            ]);

        } catch (Exception $e) {
            Log::channel('nrr')->error('Exception caught', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
