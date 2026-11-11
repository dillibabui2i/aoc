<?php

namespace App\Http\Controllers;


use App\Helpers\H;
use App\Helpers\LD;
use DataTables;
use Illuminate\Http\Request;
use App\MssqlProviders;
use DB;

class TestDeployController extends Controller
{
    public function index(Request $request)
    {
        return json_encode(['deplyed' => true]);
    }
}
