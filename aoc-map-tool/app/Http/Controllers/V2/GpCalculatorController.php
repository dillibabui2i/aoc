<?php

namespace App\Http\Controllers\V2;

use App\DTO\GpCalculator\ListGpDTO;
use App\Helpers\H;
use App\Helpers\LD;
use App\Repositories\GpCalculator\GpCalculatorRepository;
use App\Repositories\GpCalculator\ScheduleMasterRepositoryInterface;
use App\Services\GpCalculator\GpCalculatorService;
use App\Services\GpCalculator\GpClinicianService;
use App\Services\GpCalculator\PayerRatesService;
use App\Services\ManagePayer\ManagePayerService;
use App\UseCases\GpCalculator\GpCalculationUseCase;
use App\UseCases\GpCalculator\SaveCalculatorCommand;
use App\UseCases\GpCalculator\SaveCalculatorUseCase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use Throwable;

class GpCalculatorController extends \App\Http\Controllers\GPCalculatorController
{
    public function __construct(
        private readonly PayerRatesService $payerRatesService,
        private readonly SaveCalculatorUseCase $saveGpUseCase,
        private readonly ScheduleMasterRepositoryInterface $scheduleMasterRepository,
        private readonly GpCalculatorRepository $gpCalculatorRepository,
        private readonly GpClinicianService $gcClinicianService,
        private readonly ManagePayerService $managePayersService
    ) {
        parent::__construct($this->payerRatesService);
    }

    public function index(Request $request): View
    {
        $gpId = $request->get('gpId') ?? '';

        if (LD::checkFeatureFlag('change-data-source-for-payers-dropdown-list')) {
            $payers = $this->managePayersService->getPayers(false);
        } else {
            $payers = H::getHelper('ConstantHelper')->getPayers([]);
        }

        // Branch select works together with the Gross Margin Escalation Guide: no flag - no branches
        $branches = LD::checkFeatureFlag(GpCalculatorService::ESCALATION_GUIDE_FEATURE_FLAG)
            ? $this->managePayersService->getBranches()
            : [];

        return view('gpCalculator.indexV2', [
            'gpId' => $gpId,
            'payers' => $payers,
            'branches' => $branches,
        ]);
    }

    public function get(Request $request)
    {
        $id = $request->get('gpId');

        if (!$id) {
            new JsonResponse(['status' => 'error', 'message' => 'Not found'], );
        }

        $res = $this->gpCalculatorRepository->get($id);

        if (!$res) {
            new JsonResponse(['status' => 'error', 'message' => 'Not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['status' => 'ok', 'calculator' => json_decode($res->nurse_data, true)]);
    }

    public function saveGp(Request $request) : JsonResponse
    {
        $cmd = new SaveCalculatorCommand(
            $request->get('calculator') ?? '[]',
            Auth::user()->id,
            $request->get('id') ?? null,
        );

        $res = $this->saveGpUseCase->execute($cmd);

        return new JsonResponse(
            array_merge(['status' => $res->ok, 'message' => $res->message], $res->payload),
            $res->httpCode
        );
    }

    public function calculate(Request $request, GpCalculationUseCase $useCase): JsonResponse
    {
        return $useCase($request);
    }

    /**
     * @throws Throwable
     */
    public function requestWholeHome(Request $request) : JsonResponse
    {
        $patientId = $request->get('aocPatientId') ?? '';
        $permOnly = $request->get('showPermOnly', 'false') ?? '';
        $startWeek = $request->get('permOnlyWeekStart', '');
        $endWeek = $request->get('permOnlyWeekEnd', '');

        if (!$patientId) {
            return new JsonResponse(['status' => false, 'message' => 'Patient ID required.'], Response::HTTP_BAD_REQUEST);
        }

        $nurseData = $this->scheduleMasterRepository->getPatientScheduledData(
            $patientId,
            $permOnly === 'true',
            $startWeek,
            $endWeek,
            LD::checkFeatureFlag('gp-calculator-whole-home-calculation-change')
        );

        return response()->json(['data' => $nurseData, 'status' => true]);
    }

    public function list(Request $request)
    {
        $columns = [
            ['name' => 'id', 'label' => 'ID', 'visible' => false],
            ['name' => 'client', 'label' => 'Client'],
            ['name' => 'userName', 'label' => 'User'],
            ['name' => 'created_at', 'label' => 'Created At'],
            ['name' => 'updated_at', 'label' => 'Updated At'],
        ];
        if ($request->ajax()) {
            $cmd = new ListGpDTO(
                $request->input('search.value'),
                $request->input('length'),
                $request->input('start'),
                $columns[$request->input('order.0.column')]['name'],
                $request->input('order.0.dir')
            );
            $data = $this->gpCalculatorRepository->getList($cmd);
            $data['draw'] = $request->input('draw');

            return response()->json($data);
        }

        return view('gpCalculator.grid', ['columns' => $columns]);
    }

    public function getCandidateName(Request $request) : JsonResponse
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));
        $candidateType = $request->get('type', 'current');
        $clientListData = $this->gcClinicianService->getClinicianList($candidateType, $term);

        return response()->json($clientListData);
    }

    public function loadPayerDropdown(Request $request): JsonResponse
    {
        try {
            $isRespite = $request->input('isRespite');

            if ($isRespite == 'true') {
                $options = $this->managePayersService->getPayers(true);
            } else {
                $options = $this->managePayersService->getPayers(false);
            }

            return response()->json([
                'status' => true,
                'options' => $options
            ], ResponseAlias::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'msg' => $e->getMessage(),
            ], ResponseAlias::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
