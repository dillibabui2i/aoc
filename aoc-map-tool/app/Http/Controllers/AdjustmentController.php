<?php

namespace App\Http\Controllers;

use App\Enums\JobCategoryAction;
use App\Events\JobCategoryChanged;
use App\Helpers\H;
use App\Http\Requests\Adjustment\StoreAdjustmentRequest;
use App\Models\Adjustment;
use App\Models\JobCategories;
use App\Services\Adjustment\AdjustmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdjustmentController extends Controller
{
    public function __construct(private AdjustmentService $adjustmentService)
    {}

    public function index(): View
    {
        return view('adjustment.grid');
    }

    public function data(Request $request): array
    {
        $columns = [
            0 => 'category',
            1 => 'level_1_name',
            2 => 'level_2_name',
            3 => 'level_3_name',
        ];

        $adjustmentLists = Adjustment::with(['level_1:id,name', 'level_2:id,name', 'level_3:id,name']);

        $adjustmentLists = $this->adjustmentService->applySearch($adjustmentLists, $request->search['value']);

        $adjustmentLists = $this->adjustmentService->applyOrderBy($adjustmentLists, $request['order'][0], $columns);

        $total_records = $adjustmentLists->get();

        if (isset($request['start']) && $request['length'] != '-1') {
            $adjustmentLists = $adjustmentLists->offset($request['start'])->limit($request['length']);
        }

        $adjustmentLists = $adjustmentLists->get();
        $cnt = 0;
        foreach ($adjustmentLists as $adjustment) {
            $adjustmentLists[$cnt]['category'] = $adjustment['category'];
            $adjustmentLists[$cnt]['level_1_name'] = $adjustment->level_1_display_name ?? 'None';
            $adjustmentLists[$cnt]['level_2_name'] = $adjustment->level_2_display_name ?? 'None';
            $adjustmentLists[$cnt]['level_3_name'] = $adjustment->level_3_display_name ?? 'None';

            if (H::canPermissions('adjustments.edit')) {
                $adjustmentLists[$cnt]['action'] = '<a href="' . url('adjustments/edit', $adjustmentLists[$cnt]->id) .
                    '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
            }

            $cnt++;
        }

        $data = [
            'draw' => intval($request['draw']),
            'recordsTotal' => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data' => $adjustmentLists,
        ];

        return $data;
    }

    public function add(): View
    {
        $users = $this->adjustmentService->getUsersForDropdowns();

        $regionArDirectorRoleId = Adjustment::REGION_AR_DIRECTOR_ROLE_ID;

        return view('adjustment.form', ['users' => $users, 'regionArDirectorRoleId' => $regionArDirectorRoleId]);
    }

    public function edit(int $id): View
    {
        $adjustmentDetail = Adjustment::with(['level_1:id,name', 'level_2:id,name', 'level_3:id,name'])->find($id);

        $users = $this->adjustmentService->getUsersForDropdowns();

        $regionArDirectorRoleId = Adjustment::REGION_AR_DIRECTOR_ROLE_ID;

        $logData = $this->adjustmentService->getAdjustmentActivityNotes([
            'entity'    => 'adjustment-change',
            'entity_id' => $adjustmentDetail?->id
        ]);

        return view('adjustment.form', [
            'adjustmentDetail' => $adjustmentDetail,
            'users' => $users,
            'regionArDirectorRoleId' => $regionArDirectorRoleId,
            'logData' => $logData
        ]);
    }

    public function save(StoreAdjustmentRequest $request): RedirectResponse
    {
        $message = $this->adjustmentService->save($request->validated());

        return redirect()->route('adjustment.index')->with('success', $message);
    }

    public function checkCategory(Request $request): JsonResponse
    {
        $exists = Adjustment::where('category', $request->input('category'))->exists();

        return response()->json([
            'exists' => $exists
        ]);

    }
}
