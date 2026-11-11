<?php

namespace App\Http\Controllers;

use App\Enums\ManagePayer\GrossMarginLobEnum;
use App\Http\Requests\ManagePayer\ManagePayerRequest;
use App\Models\GrossMarginConfiguration;
use App\Services\ManagePayer\ManagePayerExportService;
use App\Services\ManagePayer\ManagePayerService;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;
use App\Helpers\H;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ManagePayerController extends Controller
{
    public function __construct(
        protected ManagePayerService       $managePayerService,
        protected ManagePayerExportService $managePayerExportService
    )
    {
    }

    /**
     * @return Renderable
     */
    public function index(): Renderable
    {
        return view('managepayer.grid');
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getPayerList(Request $request): JsonResponse
    {
        return $this->managePayerService->buildPayersTable($request);
    }

    /**
     * @param Request $request
     * @return StreamedResponse
     */
    public function exportPayerData(Request $request): StreamedResponse
    {
        return $this->managePayerExportService->exportPayers($request);
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    public function edit(Request $request): Renderable
    {
        $id = $request->id;
        $payerData = DB::table('payment_source')->where('id', $id)->first();
        $rows = GrossMarginConfiguration::where('payer_id', $id)->get();

        //todo need to move them from "H"
        $branches = H::getHelper('ConstantHelper')
            ->getFacilities()
            ->map(function ($branch) {
                return [
                    'id' => $branch->id,
                    'name' => $branch->FacilityName,
                ];
            })
            ->values();

        $lob = GrossMarginLobEnum::options();


        return view('managepayer.form', [
            'payerData' => $payerData,
            'rows' => $rows,
            'branches' => $branches,
            'lob' => $lob,
        ]);
    }

    /**
     * @param ManagePayerRequest $request
     * @return JsonResponse
     * @throws \Throwable
     */
    public function updatePayerData(ManagePayerRequest $request): JsonResponse
    {
        $dto = $request->toDTO();

        DB::table('payment_source')
            ->where('id', $dto->id)
            ->update([
                'state' => $dto->state,
            ]);

        $this->managePayerService->saveGrossMarginConfiguration($dto);

        return response()->json(['success' => 1]);
    }
}
