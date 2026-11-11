<?php

use App\Http\Controllers\Api\PolicyController;
use Illuminate\Http\Request;
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

Route::get('/envs', function() {
    file_put_contents(storage_path('envs_test.json'), json_encode([
        'TWILIO_CAMPAIGN_SID' => env('TWILIO_CAMPAIGN_SID'),
        'TWILIO_ACCOUNT_SID' => env('TWILIO_ACCOUNT_SID'),
        'TWILIO_AUTH_TOKEN' => env('TWILIO_AUTH_TOKEN'),
        'TWILIO_TOLL_FREE' => env('TWILIO_TOLL_FREE'),
    ]));
});

// To Register Device - START
Route::middleware(['jsonrequest'])->post('/registerdevice', [App\Http\Controllers\Api\DeviceController::class, 'registerDevice'])->name('registerdevice');
// To Register Device - END

Route::get('/test', function() {
    return response()->json(['message' => 'Hello world!']);
})->name('healthCheck');

Route::post('/login', [App\Http\Controllers\Api\LoginController::class, 'login'])->name('login');
Route::post('/getconfigsetting', [App\Http\Controllers\Api\ConfigController::class, 'getConfigSetting'])->name('getconfigsetting');
Route::post('/sendLoginOtp', [App\Http\Controllers\Api\LoginController::class, 'sendLoginOtp'])->name('sendLoginOtp');
Route::post('/verifyLoginOtp', [App\Http\Controllers\Api\LoginController::class, 'verifyLoginOtp'])->name('verifyLoginOtp');

Route::group(['middleware' => ['hybrid.auth', 'jsonrequest', 'externalApiContext']], function() {

    Route::prefix('policies')->group(function () {
        Route::get('/', [PolicyController::class, 'index'])->name('policy.index');
        Route::post('/accept', [PolicyController::class, 'accept'])->name('policy.accept');
        Route::get('/status', [PolicyController::class, 'status'])->name('policy.status');
    });

    Route::post('/getappmenuitems', [App\Http\Controllers\Api\ConfigController::class, 'getAppMenuItems'])->name('getappmenuitems');
    Route::post('/getuserdetails', [App\Http\Controllers\Api\UserController::class, 'getUserDetails'])->name('getUserDetails');
    Route::post('/logout', [App\Http\Controllers\Api\LoginController::class, 'logout'])->name('logout');
    Route::prefix('client')->group(function () {
        Route::post('/list', [App\Http\Controllers\Api\ClientController::class, 'clientList'])->name('clientList');
        Route::post('/details', [App\Http\Controllers\Api\ClientController::class, 'clientDetails'])->name('clientdetails');
        Route::post('/addToHH', [App\Http\Controllers\Api\ClientController::class, 'addToHH'])->name('addToHH');
        Route::post('/updateClient', [App\Http\Controllers\Api\ClientController::class, 'updateClient'])->name('updateClient');
        Route::post('/postMediaRequest', [App\Http\Controllers\Api\ClientController::class, 'postMediaRequest'])->name('postMediaRequest');
        Route::post('/postMediaConfig', [App\Http\Controllers\Api\ClientController::class, 'postMediaConfig'])->name('postMediaConfig');
        Route::post('/payers', [App\Http\Controllers\Api\ClientController::class, 'payers'])->name('payers');
        Route::post('/status', [App\Http\Controllers\Api\ClientController::class, 'status'])->name('status');
        Route::post('/state', [App\Http\Controllers\Api\ClientController::class, 'state'])->name('state');
        Route::post('/facilities', [App\Http\Controllers\Api\ClientController::class, 'facilities'])->name('facilities');
        Route::post('/submitFormRequest', [App\Http\Controllers\Api\ClientController::class, 'submitFormRequest'])->name('submitformrequest');
        Route::post('/getNotifications', [App\Http\Controllers\Api\ClientController::class, 'getNotifications'])->name('getnotifications');
        Route::post('/clearNotification', [App\Http\Controllers\Api\ClientController::class, 'clearNotification'])->name('clearnotification');
        Route::post('/getPhysiciansName', [App\Http\Controllers\Api\ClientController::class, 'getPhysiciansName'])->name('getphysiciansname');
        Route::post('/patientList', [App\Http\Controllers\Api\ClientController::class, 'getPatientList'])->name('getPatientList');
        Route::post('/careTeamList', [App\Http\Controllers\Api\ClientController::class, 'getCareTeamList'])->name('getCareTeamList');
    });

    Route::prefix('nth')->group(function () {
        Route::post('getNTHcheklist',[App\Http\Controllers\Api\NeedToHireController::class, 'getNThcheklist'])->name('getNThcheklist');
        Route::post('getUpcomingAppointments',[App\Http\Controllers\Api\NeedToHireController::class, 'getUpcomingAppointments'])->name('getUpcomingAppointments');
        Route::post('getNTHSchedules',[App\Http\Controllers\Api\NeedToHireController::class, 'getNTHSchedules'])->name('getNTHSchedules');
        Route::post('updateNTHSchedules',[App\Http\Controllers\Api\NeedToHireController::class, 'updateNTHSchedules'])->name('updateNTHSchedules');
    });

    Route::prefix('clinician')->group(function () {
        Route::post('getkantimeschedule', [App\Http\Controllers\Api\ProviderController::class, 'getKanTimeSchedule'])->name('getkantimeschedule');
        Route::post('getUpcomingAppointments', [App\Http\Controllers\Api\ProviderController::class, 'getUpcomingAppointments'])->name('getUpcomingAppointments');
        Route::post('/submitFormRequest', [App\Http\Controllers\Api\ProviderController::class, 'submitFormRequest'])->name('submitformrequest');
        Route::post('/details', [App\Http\Controllers\Api\ProviderController::class, 'clinicianDetails'])->name('cliniciandetails');
        Route::post('/getSupplyRequest', [App\Http\Controllers\Api\ProviderController::class, 'getSupplyRequest'])->name('getSupplyRequest');
        Route::post('/getPrnDays', [App\Http\Controllers\Api\ProviderController::class, 'getPrnDays'])->name('getPrnDays');
        Route::post('/getExpiringRenewables', [App\Http\Controllers\Api\ProviderController::class, 'getExpiringRenewables'])->name('getExpiringRenewables');
    });

    //Used for HH
    Route::post('/provider/list', [App\Http\Controllers\Api\ProviderController::class, 'providerList'])->name('providerList');
    Route::post('/provider/details', [App\Http\Controllers\Api\ProviderController::class, 'providerDetails'])->name('providerDetails');

});
