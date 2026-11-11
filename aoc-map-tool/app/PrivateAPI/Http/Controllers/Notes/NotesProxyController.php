<?php

namespace App\PrivateAPI\Http\Controllers\Notes;

use App\Http\Controllers\Controller;
use App\Http\Controllers\NotesController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Throwable;

class NotesProxyController extends Controller
{
    public function __construct(private readonly NotesController $notesController)
    {
    }

    public function save(Request $request): Response
    {
        //TODO TBD
        try {
            $result = $this->notesController->saveNotes($request);

            if ($result != 1) {
                return response()->noContent(Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return response()->noContent(Response::HTTP_OK);
        } catch (Throwable $e) {
            return response()->noContent(Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
