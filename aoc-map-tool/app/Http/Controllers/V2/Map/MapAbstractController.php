<?php

namespace App\Http\Controllers\V2\Map;

use App\Factories\Map\MapFactory;
use App\Helpers\H;
use App\Http\Controllers\MapAbstractController as OldController;
use App\Http\Requests\Map\MapTableRequest;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Support\Facades\Log;

///todo rename it after old one will be removed
class MapAbstractController extends OldController
{
    public function __construct(protected MapFactory $mapFactoryService)
    {
    }

    /**
     * @param MapTableRequest $request
     * @return Renderable | array
     * @throws \Exception
     */
    public function showTable(MapTableRequest $request): Renderable|array
    {
        if (!$request->ajax()) {
            return view('map.table');
        }

        try {
            $params = $request->toDTO();

            $mapInfo = H::getSelectedMapInfo();
            if (empty($mapInfo)) {
                return app(OldController::class)->showTable($request);
            }

            $tableName = $mapInfo[0]->map_tablename;

            if ($tableName !== 'maps_nth') { ///todo fix it. Temporary decision
                return app(OldController::class)->showTable($request);
            }

            $service = $this->mapFactoryService->getInstance($tableName, $params);

            return $service->getNeedToHireData($params);
        } catch (\Exception $e) {
            Log::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return ['error' => $e->getMessage()];
        }
    }
}
