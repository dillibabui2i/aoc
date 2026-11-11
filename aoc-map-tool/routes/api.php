<?php

use App\Http\Controllers\CandidatesController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\EditableController;
use App\Http\Controllers\NotesController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::get('/testdeploy', [App\Http\Controllers\TestDeployController::class, 'index'])->name('testdeploy.index');
Route::post('/conversationwebhook', [App\Http\Controllers\MassMessageController::class, 'conversationWebhook'])->name('conversationwebhook');
Route::post('/directconversationwebhook', [App\Http\Controllers\MassMessageController::class, 'directConversationWebhook'])->name('directconversationwebhook');
Route::any('/rightsignature/handledocumentevent', [App\Http\Controllers\RightSignatureController::class, 'handleCallBackRightSignatureEventLevel'])->name('handleeventdocumentlevel');
Route::any('/rightsignature/handledocumentstatus', [App\Http\Controllers\RightSignatureController::class, 'handleCallBackRightSignatureDocumentLevel'])->name('handledocumentstatus');
Route::any('/fieldevaluation/generateandsendnontherapypdf', [App\Http\Controllers\RightSignatureController::class, 'generateAndSendNonTherapyPDF'])->name('generateandsendpdf');
Route::any('/rightsignature/sendevaluationtest', [App\Http\Controllers\RightSignatureController::class, 'sendEvaluationTest'])->name('sendevaluationtest');
Route::any('/rightsignature/executepastevaluations', [App\Http\Controllers\RightSignatureController::class, 'executePastEvaluations'])->name('renewableupdate');
Route::post('/payratewebhook',[App\Http\Controllers\MassMessageController::class,
'payRateWebhook'])->name('payratewebhook');
Route::any('/checkDeliveryStatusWebhook', [App\Http\Controllers\MassMessageController::class, 'checkDeliveryStatusWebhook'])->name('checkDeliveryStatusWebhook');


