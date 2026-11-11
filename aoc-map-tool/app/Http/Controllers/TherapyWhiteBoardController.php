<?php

namespace App\Http\Controllers;

use App\Helpers\H;
use App\Helpers\LD;
use App\Http\Requests\WhiteBoard\ManageWhiteBoardRequest;
use App\RecruitingSchedule;
use App\Services\WhiteBoard\TherapyWhiteBoardService;
use App\WhiteBoard;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Redirector;
use Illuminate\Support\Facades\DB;


class TherapyWhiteBoardController extends Controller
{
    /**
     * @param TherapyWhiteBoardService $whiteBoardService
     */
    public function __construct(protected TherapyWhiteBoardService $whiteBoardService)
    {
    }

    public function index(): Renderable
    {
        $payload = $this->whiteBoardService->buildTherapyWhiteBoardReport();

        return view('therapyWhiteboard.index', [
            'days' => $payload['days'],
            'data' => $payload['data']
        ]);
    }
}
