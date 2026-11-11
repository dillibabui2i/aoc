<?php

namespace App\Http\Controllers;

use App\Http\Requests\Snowflake\SnowflakeRequest;
use App\Services\Snowflake\Query;
use Illuminate\Http\Response;
use Illuminate\View\View;

class SnowflakeVerifierController extends Controller
{
    public function index(): View
    {
        return view('snowflake.form');
    }

    public function execute(SnowflakeRequest $snowflakeRequest, Query $query)
    {
        return $query->executeQuery($snowflakeRequest->validated()['query']);
    }
}
