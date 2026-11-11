<?php

use App\Helpers\H;
use App\Http\Controllers\AdjustmentController;
use App\Http\Controllers\ConsentTemplateController;
use App\Http\Controllers\CoreSettingController;
use App\Http\Controllers\Dashboard;
use App\Http\Controllers\EditableController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\IpeaCalendarController;
use App\Http\Controllers\IPEAEmailTemplate;
use App\Http\Controllers\Login;
use App\Http\Controllers\LoginRestrictions;
use App\Http\Controllers\ManageIPEAAttachementController;
use App\Http\Controllers\MapAbstractController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PolygonController;
use App\Http\Controllers\RecruiterTrackerController;
use App\Http\Controllers\ReportSourceController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\RouteManagerController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SnowflakeVerifierController;
use App\Http\Controllers\TherapyWhiteBoardController;
use App\Http\Controllers\WhiteBoardController;
use App\Http\Controllers\NationalRecruitingReportController;
use App\Http\Controllers\NeedToHireReportController;
use App\Http\Controllers\SkillsMatchReportController;
use App\Http\Controllers\NurseChurnReportController;
use App\Http\Controllers\V2\NurseChurnReportController as ChurnReportControllerV2;
use App\Http\Controllers\BonusReportController;
use App\Http\Controllers\NotesController;
use App\Http\Controllers\CandidatesController;
use App\Http\Controllers\FeatureFlagController;
use App\Http\Controllers\RequestFormController;
use App\Http\Controllers\NeedToHireController;
use App\Http\Controllers\SocialMediaController;
use App\Http\Controllers\ClinicianController;
use App\Http\Controllers\MapIgnoreController;
use App\Http\Controllers\ClassCategoryController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\IpeaConversionReportController;
use App\Http\Controllers\TrachVentClassController;
use App\Http\Controllers\TrachVentClassOccurrenceController;
use App\Http\Controllers\OrientationCoordinatorController;
use App\Http\Controllers\HubCitiesController;
use App\Http\Controllers\IntakeController;
use App\Http\Controllers\AfterHoursController;
use App\Http\Controllers\TriageController;
use App\Http\Controllers\RecruiterScorecardController;
use App\Http\Controllers\CoverageController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecruitingController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\JobCategoryControllers;
use App\Http\Controllers\SuppliesController;
use App\Http\Controllers\ClientCareCoordinatorController;
use App\Http\Controllers\CCRController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\MassMessageController;
use App\Http\Controllers\SnowFlexController;
use App\Http\Controllers\RightSignatureController;
use App\Http\Controllers\FieldEvaluationController;
use App\Http\Controllers\OfficeEvaluationController;
use App\Http\Controllers\CCRAvailabilityController;
use App\Http\Controllers\CCRCalendarController;
use App\Http\Controllers\PayerRatesController;
use App\Http\Controllers\GPCalculatorController;
use App\Http\Controllers\SelfEvaluationController;
use App\Http\Controllers\TodoController;
use App\Http\Controllers\MobileController;
use App\Http\Controllers\RenewablesController;
use App\Http\Controllers\RenewableReportController;
use App\Http\Controllers\RenewablesRuleController;
use App\Http\Controllers\AdvocacyController;
use App\Http\Controllers\MiscController;
use App\Http\Controllers\StatusChangeRequestController;
use App\Http\Controllers\RenewablesDocumentController;
use App\Http\Controllers\PaycomKantimeController;
use App\Http\Controllers\JobviteRequisition;
use App\Http\Controllers\SupervisoryVisitController;
use App\Http\Controllers\AzureServiceController;
use App\Http\Controllers\SchoolSetupController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\NationalClientCareReportController;
use App\Http\Controllers\CoverageWhiteboardController;
use App\Http\Controllers\TextTemplateController;
use App\Http\Controllers\ManagingCustomFields;
use App\Http\Controllers\ManageCategoryController;
use App\Http\Controllers\ManagePayerController;
use App\Http\Controllers\KantimeFacilityController;
use App\Http\Controllers\IntakeCalendarController;
use App\Http\Controllers\IntakeReportController;
use App\Http\Controllers\CandidateConversionReportController;
use App\Http\Controllers\WekklyCallFormController;
use App\Http\Controllers\MonthlyCallController;
use App\Http\Controllers\IpeaSpeedReportController;
use App\Http\Controllers\NthLandingController;
use App\Http\Controllers\NTHAlertsController;
use App\Http\Controllers\DataConnectionController;
use App\Http\Controllers\FirstAdvantageController;
use App\Http\Controllers\TagController;
use App\Http\Controllers\ClaimTagController;
use App\Http\Controllers\InvoiceTagController;
use App\Http\Controllers\CustomFieldController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::group(['middleware' => ['XSS']], function () {

$listOfmiddleware = ['auth', 'adminrole'];
if (H::isMFAEnable()) {
    $listOfmiddleware = ['auth', '2fa', 'adminrole'];
}

Route::get('refresh-csrf', function () {
    return csrf_token();
});

Route::get('/status', [Dashboard::class, 'status']); // Health check for ALB

Route::group(['middleware' => ['snowflake.access']], function () {
    Route::get('/snowflake-query', [SnowflakeVerifierController::class, 'index'])->name('snowflake-verifier.index');
    Route::post('/snowflake-query', [SnowflakeVerifierController::class, 'execute'])->name('snowflake-verifier.execute');
});

// Route::get('/', [Dashboard::class, 'profile'])->middleware($listOfmiddleware);
Route::get('/', [Dashboard::class, 'dashboard'])->name('dashboard')->middleware($listOfmiddleware);
Route::get('/dashboard/allactivitydata', [Dashboard::class, 'getAllActivityData'])->name('dashboard.allactivitydata')->middleware($listOfmiddleware);
Route::any('/dashboard/removeactivity', [Dashboard::class, 'removeActivity'])->name('dashboard.removeactivity')->middleware($listOfmiddleware);
Route::get('/dashboard/ajaxallactivitydata', [Dashboard::class, 'ajaxAllActivityData'])->name('dashboard.ajaxallactivitydata')->middleware($listOfmiddleware);
Route::get('/getnthchecklist', [Dashboard::class, 'getNTHChecklistData'])->name('dashboard.getNTHChecklist')->middleware($listOfmiddleware);
Route::get('/activitydata', [Dashboard::class, 'getActivityData'])->name('dashboard.activitydata')->middleware($listOfmiddleware);
Route::get('/getNTHExpiringJobslist', [Dashboard::class, 'getNTHExpiringJobslist'])->name('dashboard.getNTHExpiringJobslist')->middleware($listOfmiddleware);
Route::any('/reloadCountNTHExpiringJobs', [Dashboard::class, 'reloadCountNTHExpiringJobs'])->name('reloadCountNTHExpiringJobs')->middleware($listOfmiddleware);
Route::get('/profile', [Dashboard::class, 'profile'])->name('users.profile')->middleware($listOfmiddleware);

Route::any('/prn/excludebucket', [Dashboard::class, 'excludePRN'])->name('prn.excludebucket')->middleware($listOfmiddleware);
Route::get('/prn/confirmdialog', [Dashboard::class, 'getPRNConfirmationDialog'])->name('prn.confirmationdialog')->middleware($listOfmiddleware);

Route::get('/login', [Login::class, 'index'])->name('login');
Route::get('/microsoftlogin', [Login::class, 'performMicrosoftLogin'])->name('microsoftlogin');
Route::get('/processmicrosoft', [Login::class, 'getMicrosoftResponse'])->name('getMicrosofftResponse');
Route::get('/logout', [Login::class, 'logout'])->name('logout');

Route::get('/google2fa', [Login::class, 'google2fa'])->name('google2fa');
Route::post('/complete-registration', [Login::class, 'completeRegistration'])->name('complete-registration');

Route::post('/2fasubmit', [Login::class, 'google2fasubmit'])->name('2fasubmit')->middleware($listOfmiddleware);
Route::post('/login', [Login::class, 'login'])->name('loginpost');
Route::get('/check-user-alive', [Login::class, 'checkUserAlive'])->name('checkUserAlive');

Route::get('/forgotpassword', [ForgotPasswordController::class, 'index'])->name('forgotpassword');
Route::post('/forgotpasswordpost', [ForgotPasswordController::class, 'sendmail'])->name('sendmail');
Route::get('/resetpassword/{token}', [ForgotPasswordController::class, 'showResetPasswordForm'])->name('reset.passsword');
Route::post('resetpassword', [ForgotPasswordController::class, 'submitResetPasswordForm'])->name('reset.password.post');

//Manage User
Route::group(['middleware' => $listOfmiddleware], function () {
    Route::get('/manageusers', [App\Http\Controllers\UserController::class, 'index'])->name('manageusers');
    Route::get('/getuserlistajax', [App\Http\Controllers\UserController::class, 'getUserList'])->name('getuserlistajax');
    Route::get('/getparentusers', [App\Http\Controllers\UserController::class, 'getParentPermissionUsers'])->name('manageusers.getparentusers');
    Route::get('/manageusers/add', [App\Http\Controllers\UserController::class, 'add'])->name('manageusers.add');
    Route::post('/saveuser', [App\Http\Controllers\UserController::class, 'saveuser'])->name('manageusers.saveuser');
    Route::get('/manageusers/edit/{id}', [App\Http\Controllers\UserController::class, 'edit'])->name('manageusers.edit');
    Route::post('/updateuser', [App\Http\Controllers\UserController::class, 'updateuser'])->name('manageusers.updateuser');
    Route::get('/manageusers/delete/{id}', [App\Http\Controllers\UserController::class, 'destroy'])->name('manageusers.destroy');
    Route::get('/manageusers/disable/{id}', [App\Http\Controllers\UserController::class, 'disable'])->name('manageusers.disable');
    Route::get('/user/get-user-list-for-auto-complete', [App\Http\Controllers\UserController::class, 'getListOfUsersForAutoComplete'])->name('user.getListOfUsersForAutoComplete');
    Route::get('/exportmanageuserdata', [App\Http\Controllers\UserController::class, 'exportUserData'])->name('exportUserData');
});

Route::post('/sendpasswordemail', [App\Http\Controllers\UserController::class, 'sendPasswordEmail'])->name('manageusers.sendpasswordemail')->middleware($listOfmiddleware);

//location data
Route::post('/searchLocationData', [MapAbstractController::class, 'searchLocationData'])->name('searchlocationdata')->middleware($listOfmiddleware);
//Generalized
Route::post('/map/choosemap', [MapAbstractController::class, 'chooseMap'])->name('map.choosemap')->middleware($listOfmiddleware);

Route::get('/map/resetfilters', [MapAbstractController::class, 'resetMapFilters'])->name('map.resetfilters')->middleware($listOfmiddleware);
Route::get('/map/showmap', [MapAbstractController::class, 'showMap'])->name('map.map')->middleware($listOfmiddleware);
Route::get('/map/getmainlocations', [MapAbstractController::class, 'getMainLocations'])->name('map.getmainlocations')->middleware($listOfmiddleware);
Route::get('/map/getlocationdata', [MapAbstractController::class, 'getLocationDataOfMap'])->name('map.getlocationdata')->middleware($listOfmiddleware);
Route::get('/map/getmultiplelocationdata', [MapAbstractController::class, 'getMultipleLocationDataOfMap'])->name('map.getmultiplelocations')->middleware($listOfmiddleware);
Route::get('/map/getjson', [MapAbstractController::class, 'getjson'])->name('map.json')->middleware($listOfmiddleware);  //temporary
Route::any('/map/showtable', [MapAbstractController::class, 'showTable'])->name('map.table')->middleware($listOfmiddleware);
Route::get('/map/import', [MapAbstractController::class, 'index'])->name('map.import')->middleware($listOfmiddleware);

Route::get('/map/ignore', [MapIgnoreController::class, 'getIgnoreGrid'])->name('map.ignoregrid')->middleware($listOfmiddleware);
Route::get('/map/ignoreform/edit', [MapIgnoreController::class, 'getIgnoreForm'])->name('map.ignoreform')->middleware($listOfmiddleware);
Route::get('/map/ajaxignoredata', [MapIgnoreController::class, 'ajaxIgnoreData'])->name('map.ajaxignoredata')->middleware($listOfmiddleware);
Route::any('/map/saveignore', [MapIgnoreController::class, 'saveIgnore'])->name('map.saveignore')->middleware($listOfmiddleware);
Route::get('/map/deleteignore', [MapIgnoreController::class, 'deleteIgnore'])->name('map.deleteIgnore')->middleware($listOfmiddleware);
Route::get('/map/globalsearch', [MapIgnoreController::class, 'performGlobalSearch'])->name('map.performglobalsearch')->middleware($listOfmiddleware);


Route::get('/map/configurations', [MapAbstractController::class, 'retrieveConfigurations'])->name('map.configurations')->middleware($listOfmiddleware);
Route::post('/map/saveconfigurations', [MapAbstractController::class, 'saveConfigurations'])->name('map.configuredata')->middleware($listOfmiddleware);
Route::get('/map/downloaddata', [MapAbstractController::class, 'downloadData'])->name('map.downloaddata')->middleware($listOfmiddleware);
Route::get('/map/getdistinct', [MapAbstractController::class, 'getDistinctValue'])->name('map.getdistinct')->middleware($listOfmiddleware);
Route::get('/map/mapcolors', [MapAbstractController::class, 'setMapColors'])->name('map.setmapcolors')->middleware($listOfmiddleware);
Route::get('/map/map-intro', [MapAbstractController::class, 'mapIntro'])->name('map.mapIntro')->middleware($listOfmiddleware);
Route::get('/managemap', [MapAbstractController::class, 'listmap'])->name('managemap')->middleware($listOfmiddleware);
Route::get('/getmaplistajax', [MapAbstractController::class, 'getMapList'])->name('getmaplistajax')->middleware($listOfmiddleware);
Route::get('/managemap/add', [MapAbstractController::class, 'add'])->name('managemap.add')->middleware($listOfmiddleware);
Route::post('managemap/savemap', [MapAbstractController::class, 'savemap'])->name('managemap.savemap')->middleware($listOfmiddleware);
Route::get('/managemap/status/{status}/id/{id}', [MapAbstractController::class, 'status'])->name('managemap.status')->middleware($listOfmiddleware);
Route::get('/managemap/editmap/id/{id}', [MapAbstractController::class, 'editmap'])->name('managemap.editmap')->middleware($listOfmiddleware);
Route::get('/map/remove-territory', [MapAbstractController::class, 'removeTerritory'])->name('map.removeTerritory')->middleware($listOfmiddleware);
Route::get('/map/get-route-distance', [MapAbstractController::class, 'getRouteDistance'])->name('map.getRouteDistance')->middleware($listOfmiddleware);
Route::post('/map/removePNM', [MapAbstractController::class, 'removePNM'])->name('map.removePNM')->middleware($listOfmiddleware);

// Core Settings Action

Route::get('/core-setting/settings', [CoreSettingController::class, 'index'])->name('coresetting.setting')->middleware($listOfmiddleware);
Route::any('/core-setting/save', [CoreSettingController::class, 'save'])->name('coresetting.save')->middleware($listOfmiddleware);

// Manage Roles
Route::get('/manageroles', [RolesController::class, 'listroles'])->name('manageroles')->middleware($listOfmiddleware);
Route::get('/getroleslistajax', [RolesController::class, 'getRolesList'])->name('getroleslistajax')->middleware($listOfmiddleware);

Route::get('/manageroles/add', [RolesController::class, 'add'])->name('manageroles.add')->middleware($listOfmiddleware);
Route::get('/manageroles/editrole/id/{id}', [RolesController::class, 'editrole'])->name('manageroles.editrole')->middleware($listOfmiddleware);
Route::post('manageroles/saverole', [RolesController::class, 'saverole'])->name('manageroles.saverole')->middleware($listOfmiddleware);
Route::get('/restricted', [RolesController::class, 'restricted'])->name('restricted')->middleware($listOfmiddleware);

Route::get('/checkduplicaterole', [RolesController::class, 'checkDuplicateRole'])->name('checkduplicaterole')->middleware($listOfmiddleware);
//Polygons
Route::post('/savepolygone', [PolygonController::class, 'save'])->name('savepolygone')->middleware($listOfmiddleware);
Route::post('/edit-territor-name', [PolygonController::class, 'editTerritoryName'])->name('editTerritoryName')->middleware($listOfmiddleware);
Route::post('/checkpolygonname', [PolygonController::class, 'checkNameExist'])->name('checkpolygonname')->middleware($listOfmiddleware);

//WhiteBoard
Route::any('/whiteboard', [WhiteBoardController::class, 'whiteboard'])->name('whiteboard')->middleware($listOfmiddleware);
Route::get('/managewhiteboard', [WhiteBoardController::class, 'managewhiteboard'])->name('managewhiteboard')->middleware($listOfmiddleware);
Route::get('/managewhiteboard/getwhiteboardlistajax', [WhiteBoardController::class, 'getwhiteboardlistajax'])->name('managewhiteboard.getwhiteboardlistajax')->middleware($listOfmiddleware);
Route::get('/managewhiteboard/add', [WhiteBoardController::class, 'add'])->name('managewhiteboard.add')->middleware($listOfmiddleware);
Route::post('/managewhiteboard/save', [WhiteBoardController::class, 'save'])->name('managewhiteboard.save')->middleware($listOfmiddleware);
Route::get('/managewhiteboard/edit/{id}', [WhiteBoardController::class, 'edit'])->name('managewhiteboard.edit')->middleware($listOfmiddleware);
Route::get('/managewhiteboard/delete/{id}', [WhiteBoardController::class, 'delete'])->name('managewhiteboard.delete')->middleware($listOfmiddleware);
Route::get('/whiteboardreport', [WhiteBoardController::class, 'getWhiteboardReport'])->name('managewhiteboard.whiteboardreport')->middleware($listOfmiddleware);
Route::post('/whiteboard/saveOutOfOffice', [WhiteBoardController::class, 'saveOutOfOffice'])->name('whiteboard.saveOutOfOffice')->middleware($listOfmiddleware);

//Therapy Whiteboard
Route::prefix('therapy-whiteboard')->group(function () {
    Route::get('/', [TherapyWhiteBoardController::class, 'index'])->name('therapy.whiteboard.index');
})->middleware($listOfmiddleware);

//National Recruiting Report
Route::any('/reports/nthHours', [NationalRecruitingReportController::class, 'nthHours'])->name('nthHours')->middleware($listOfmiddleware);
Route::any('/reports/candidates', [NationalRecruitingReportController::class, 'candidates'])->name('candidates')->middleware($listOfmiddleware);
Route::any('/reports/IPEAs', [NationalRecruitingReportController::class, 'IPEAs'])->name('IPEAs')->middleware($listOfmiddleware);
Route::any('/reports/PEPIs', [NationalRecruitingReportController::class, 'PEPIs'])->name('PEPIs')->middleware($listOfmiddleware);
Route::any('/reports/WEPIs', [NationalRecruitingReportController::class, 'WEPIs'])->name('WEPIs')->middleware($listOfmiddleware);
Route::any('/reports/candidateMatch', [NationalRecruitingReportController::class, 'candidateMatch'])->name('candidateMatch')->middleware($listOfmiddleware);
Route::any('/reports/hoursRemoved', [NationalRecruitingReportController::class, 'hoursRemoved'])->name('hoursRemoved')->middleware($listOfmiddleware);
Route::any('/reports/grossMargin', [NationalRecruitingReportController::class, 'grossMargin'])->name('grossMargin')->middleware($listOfmiddleware);
Route::any('/reports/candidateStarts', [NationalRecruitingReportController::class, 'candidateStarts'])->name('candidateStarts')->middleware($listOfmiddleware);
Route::post('/reports/getSource', [ReportSourceController::class, 'getSource'])->name('getSource')->middleware($listOfmiddleware);
Route::any('/reports/getnrrpopup', [NationalRecruitingReportController::class, 'getNRRPopup'])->name('nrr.getnrrpopup')->middleware($listOfmiddleware);

//Ipea Conversion Report
Route::any('/reports/ipeaConversion', [IpeaConversionReportController::class, 'IpeaConversion'])->name('IpeaConversion')->middleware($listOfmiddleware);
Route::any('/reports/ipeaConversionCounts', [IpeaConversionReportController::class, 'IpeaConversionCounts'])->name('IpeaConversionCounts')->middleware($listOfmiddleware);
Route::any('/reports/ipeaConversion/geticrpopup', [IpeaConversionReportController::class, 'getIcrPopup'])->name('icr.geticrpopup')->middleware($listOfmiddleware);

//Need To Hire Reports
Route::any('/reports/nth/hoursOnNTH', [NeedToHireReportController::class, 'hoursOnNTH'])->name('hoursOnNTH')->middleware($listOfmiddleware);
Route::any('/reports/nth/nthHoursAdded', [NeedToHireReportController::class, 'nthHoursAdded'])->name('nthHoursAdded')->middleware($listOfmiddleware);
Route::any('/reports/nth/nthHoursRemoved', [NeedToHireReportController::class, 'nthHoursRemoved'])->name('nthHoursRemoved')->middleware($listOfmiddleware);
Route::any('/reports/nth/nthHoursClassification', [NeedToHireReportController::class, 'nthHoursClassification'])->name('nthHoursClassification')->middleware($listOfmiddleware);
Route::any('/reports/nth/nthByMeetingNurses', [NeedToHireReportController::class, 'nthByMeetingNurses'])->name('nthByMeetingNurses')->middleware($listOfmiddleware);
Route::any('/reports/nth/getnthpopup', [NeedToHireReportController::class, 'getNthPopup'])->name('nth.getnthopup')->middleware($listOfmiddleware);

//Skills Mismatch report
Route::any('/reports/skillsMatch', [SkillsMatchReportController::class, 'skillsMatch'])->name('skillsMatch')->middleware($listOfmiddleware);

//Nurse Churn
Route::any('/reports/nurseChurn', [ChurnReportControllerV2::class, 'nurseChurn'])->name('nurseChurn')->middleware($listOfmiddleware);
Route::any('/reports/nurseChurnLegacy', [NurseChurnReportController::class, 'nurseChurn'])->name('nurseChurnLegacy')->middleware($listOfmiddleware);
Route::any('/reports/nurseChurn/getNurseChurnPopup', [NurseChurnReportController::class, 'getNurseChurnPopup'])->name('getNurseChurnPopup')->middleware($listOfmiddleware);

//Bonus Report
Route::any('/reports/recruiterBonus', [BonusReportController::class, 'bonusReport'])->name('bonusReport')->middleware($listOfmiddleware);
Route::any('/reports/recruiterBonus/getRecruiterBonusPopup', [BonusReportController::class, 'getRecruiterBonusPopup'])->name('getRecruiterBonusPopup')->middleware($listOfmiddleware);
Route::get('/downloadbonusreport', [BonusReportController::class, 'downloadBonusReport'])->name('downloadBonusReport')->middleware($listOfmiddleware);
Route::any('/setbonusreportpaid', [BonusReportController::class, 'setBonusPaidmodal'])->name('setBonusReportPaidmodal')->middleware($listOfmiddleware);

//Route Manager
Route::get('/routemanager', [RouteManagerController::class, 'getMissingRoutes'])->name('missingroutes')->middleware($listOfmiddleware);
Route::post('/routemanager/save', [RouteManagerController::class, 'setRouteManager'])->name('saveroutes')->middleware($listOfmiddleware);

//Schedules
Route::get('/schedules', [ScheduleController::class, 'getSchedules'])->name('schedules')->middleware($listOfmiddleware);
Route::get('/schedules/add-events', [ScheduleController::class, 'addEvents'])->name('schedules.addEvents')->middleware($listOfmiddleware);
Route::post('/schedules/save-events', [ScheduleController::class, 'saveEvents'])->name('schedules.saveEvents')->middleware($listOfmiddleware);
Route::get('/schedules/remove-schedule', [ScheduleController::class, 'removeSchedule'])->name('schedules.removeSchedule')->middleware($listOfmiddleware);
Route::get('/schedules/remove-neo-schedule', [ScheduleController::class, 'removeNeoSchedule'])->name('schedules.removeNeoSchedule')->middleware($listOfmiddleware);
Route::get('/schedules/remove-office-events', [ScheduleController::class, 'removeOfficeEvents'])->name('schedules.removeOfficeEvents')->middleware($listOfmiddleware);

Route::any('/orientationdata', [Dashboard::class, 'getOrientationData'])->name('dashboard.getorientationdata')->middleware($listOfmiddleware);
Route::any('/getOrientationForm', [OrientationCoordinatorController::class, 'getOrientationForm'])->name('getOrientationForm')->middleware($listOfmiddleware);
Route::post('/saveOrientationNext', [OrientationCoordinatorController::class, 'saveOrientationNext'])->name('saveOrientationNext')->middleware($listOfmiddleware);
Route::get('/oc/update-provider-badge', [OrientationCoordinatorController::class, 'updateProviderBadge'])->name('oc.updateProviderBadge')->middleware($listOfmiddleware);
Route::any('/jobvitedata', [Dashboard::class, 'getJobviteData'])->name('dashboard.getjobvitedata')->middleware($listOfmiddleware);
Route::any('/openassignrecruiter', [Dashboard::class, 'getAssignRecruiterForm'])->name('jobvite.assignrecruiterform')->middleware($listOfmiddleware);
Route::any('/saveassignrecruiter', [Dashboard::class, 'saveAssignRecruiter'])->name('jobvite.saverecruiter')->middleware($listOfmiddleware);

Route::any('/reloadcount', [Dashboard::class, 'reloadCount'])->name('reloadCount')->middleware($listOfmiddleware);
Route::any('/reloadcountnth', [Dashboard::class, 'reloadCountNTH'])->name('reloadCountNTH')->middleware($listOfmiddleware);
Route::any('/reloadcountorientation', [Dashboard::class, 'reloadCountOrientation'])->name('reloadCountOrientation')->middleware($listOfmiddleware);
Route::any('/reloadcountjobvite', [Dashboard::class, 'reloadCountJobvite'])->name('reloadCountJobvite')->middleware($listOfmiddleware);
Route::any('/getScheduleForm', [Dashboard::class, 'getScheduleForm'])->name('getScheduleForm')->middleware($listOfmiddleware);
Route::post('/saveScheduleNext', [Dashboard::class, 'saveScheduleNext'])->name('saveScheduleNext')->middleware(array_merge($listOfmiddleware, ['schedule.duplicate-submit']));
Route::get('/getScheduleClient', [ClientController::class, 'getScheduleClient'])->name('getScheduleClient')->middleware($listOfmiddleware);
Route::any('/getCandidateName', [Dashboard::class, 'getCandidateName'])->name('getCandidateName')->middleware($listOfmiddleware);
Route::any('/blacklisted', [LoginRestrictions::class, 'getLoginRestrictions'])->name('loginrestrictions')->middleware($listOfmiddleware);
Route::any('/ajaxblacklisted', [LoginRestrictions::class, 'ajaxLoginRestrictionsData'])->name('ajaxLoginRestrictionsData')->middleware($listOfmiddleware);
Route::any('/blacklistform', [LoginRestrictions::class, 'edit'])->name('blacklist.edit')->middleware($listOfmiddleware);
Route::any('/blacklistdelete', [LoginRestrictions::class, 'destroy'])->name('blacklist.delete')->middleware($listOfmiddleware);
Route::any('/blacklistsave', [LoginRestrictions::class, 'save'])->name('blacklist.save')->middleware($listOfmiddleware);
Route::any('/getnthschedulebyclientid', [NeedToHireController::class, 'getNthScheduleByClientId'])->name('getnthschedulebyclientid')->middleware($listOfmiddleware);
Route::any('/validateNthSchedules', [Dashboard::class, 'validateNthSchedules'])->name('validateNthSchedules')->middleware($listOfmiddleware);
Route::any('/getipeabranchnotes', [Dashboard::class, 'getIpeaBranchNotes'])->name('getipeabranchnotes')->middleware($listOfmiddleware);
Route::any('/getipeanearestbranch', [Dashboard::class, 'getIpeaNearestBranch'])->name('getipeanearestbranch')->middleware($listOfmiddleware);
Route::get('/getScheduleProvider', [Dashboard::class, 'getScheduleProvider'])->name('getScheduleProvider')->middleware($listOfmiddleware);
Route::get('/getPRNClient', [Dashboard::class, 'getPRNClient'])->name('getPRNClient')->middleware($listOfmiddleware);
Route::get('/getApprovedPayRatebyclientid', [Dashboard::class, 'getApprovedPayRatebyclientid'])->name('getApprovedPayRatebyclientid')->middleware($listOfmiddleware);

//RecruiterTrackerController
Route::get('/recruiter-tracker-details', [RecruiterTrackerController::class, 'index'])->name('recruiterDetails')->middleware($listOfmiddleware);
Route::get('/export-tracker-data', [RecruiterTrackerController::class, 'exportTrackerData'])->name('exportTrackerData')->middleware($listOfmiddleware);
Route::get('/trackers-audit', [RecruiterTrackerController::class, 'trackersAudit'])->name('trackersAudit')->middleware($listOfmiddleware);
Route::get('/export-trackers-audit', [RecruiterTrackerController::class, 'exportTrackersAudit'])->name('exportTrackersAudit')->middleware($listOfmiddleware);
Route::post('/remove-candidate', [RecruiterTrackerController::class, 'removeCandidate'])->name('removeCandidate')->middleware($listOfmiddleware);
Route::get('/get-tracker-nth-schedule', [RecruiterTrackerController::class, 'getTrackerNthSchedule'])->name('getTrackerNthSchedule')->middleware($listOfmiddleware);
Route::post('/save-tracker-nth-schedule', [RecruiterTrackerController::class, 'saveTrackerNthSchedule'])->name('saveTrackerNthSchedule')->middleware($listOfmiddleware);
Route::post('/remove-tracker-nth-schedule', [RecruiterTrackerController::class, 'removeTrackerNthSchedule'])->name('removeTrackerNthSchedule')->middleware($listOfmiddleware);
Route::post('/mass-update-recruiter', [RecruiterTrackerController::class, 'massUpdateRecruiter'])->name('massUpdateRecruiter')->middleware($listOfmiddleware);
Route::any('ccc/reloadclientmatch', [RecruiterTrackerController::class, 'reloadClientMatch'])->name('ccc.reloadclientmatch')->middleware($listOfmiddleware);
Route::any('ccc/getclientmatch', [RecruiterTrackerController::class, 'getClientMatch'])->name('ccc.getclientmatch')->middleware($listOfmiddleware);
Route::any('ccc/getclientmatchtemplate', [RecruiterTrackerController::class, 'getClientMatchTemplate'])->name('ccc.getclientmatchtemplate')->middleware($listOfmiddleware);
Route::get('/getpatientstate', [Dashboard::class, 'getPatientState'])->name('getpatientstatebyid')->middleware($listOfmiddleware);
Route::any('ccc/saveOutcome', [RecruiterTrackerController::class, 'saveClientMatchOutcome'])->name('saveClientMatchOutcome')->middleware($listOfmiddleware);

Route::any('ccm/reloadcandidatestarts', [CCRController::class, 'reloadCCMCandidateStarts'])->name('ccm.reloadcandidatestarts')->middleware($listOfmiddleware);
Route::any('ccm/getcandidatestarts', [CCRController::class, 'getCCMCandidateStarts'])->name('ccm.getcandidatestarts')->middleware($listOfmiddleware);
Route::any('ccm/candidatestarts/showall', [CCRController::class, 'getCCMCandidateStartsListing'])->name('ccm.getcandidatestartsall')->middleware($listOfmiddleware);
Route::any('ccm/candidatestarts/exportdata', [CCRController::class, 'exportCCMCandidateStart'])->name('ccm.exportData')->middleware($listOfmiddleware);

Route::any('ccm/candidatestratoutcomeform', [CCRController::class, 'getCandidateStartOutcomeForm'])->name('ccm.candidatestratoutcomeform')->middleware($listOfmiddleware);
Route::any('ccm/candidatestartoutcomesave', [CCRController::class, 'candidateStartOutcomeSave'])->name('ccm.candidatestartoutcomesave')->middleware($listOfmiddleware);


Route::any('ccc/getclientmatchcompletetemplate', [RecruiterTrackerController::class, 'getClientMatchCompleteTemplate'])->name('ccc.getclientmatchcompletetemplate')->middleware($listOfmiddleware);
Route::any('ccc/performclientmatchcomplete', [RecruiterTrackerController::class, 'saveClientMatchCompleteAction'])->name('ccc.performclientmatchcomplete')->middleware($listOfmiddleware);


//EditableController
Route::post('/editable/update', [EditableController::class, 'update'])->name('editable')->middleware($listOfmiddleware);

Route::get('/ipea/calendar', [IpeaCalendarController::class, 'index'])->name('ipea.calender')->middleware($listOfmiddleware);
Route::get('/ipea/get-week-events', [IpeaCalendarController::class, 'getWeekEvents'])->name('ipea.getWeekEvents')->middleware($listOfmiddleware);
Route::get('/ipea/update-nurse-ipea-info', [IpeaCalendarController::class, 'updateNurseIpeaInfo'])->name('ipea.updateNurseIpeaInfo')->middleware($listOfmiddleware);
Route::get('/ipea/save-nurse-info', [IpeaCalendarController::class, 'saveNurseInfo'])->name('ipea.saveNurseInfo')->middleware($listOfmiddleware);
Route::get('/ipea/get-calendar-search-string', [IpeaCalendarController::class, 'getCalendarSearchString'])->name('ipea.getCalendarSearchString')->middleware($listOfmiddleware);
Route::get('/ipea/save-reschedule', [IpeaCalendarController::class, 'saveReschedule'])->name('ipea.saveReschedule')->middleware($listOfmiddleware);
Route::get('/ipea/save-neo-reschedule', [IpeaCalendarController::class, 'saveNeoReschedule'])->name('ipea.saveNeoReschedule')->middleware($listOfmiddleware);
Route::get('/ipea/office-therapy-event-details', [IpeaCalendarController::class, 'officeTherapyEventDetails'])->name('ipea.officeTherapyEventDetails')->middleware($listOfmiddleware);
Route::get('/ipea/history', [NotesController::class, 'ipeaHistory'])->name('ipea.ipeaHistory')->middleware($listOfmiddleware);
Route::get('/ipea/neo-history', [NotesController::class, 'ipeaNeoHistory'])->name('ipea.ipeaNeoHistory')->middleware($listOfmiddleware);
Route::post('/ipea/save-checklist', [IpeaCalendarController::class, 'saveChecklist'])->name('ipea.saveChecklist')->middleware($listOfmiddleware);
Route::get('/ipea/neo-ipea-template-details', [IpeaCalendarController::class, 'neoIpeaTemplateDetails'])->name('ipea.neoIpeaTemplateDetails')->middleware($listOfmiddleware);
Route::any('/ipea/neo-ipea-save-values', [IpeaCalendarController::class, 'neoIpeaSaveValues'])->name('ipea.neoIpeaSaveValues')->middleware($listOfmiddleware);

//RecruiterTrackerController
Route::get('/ipea/attachment', [ManageIPEAAttachementController::class, 'index'])->name('manageipeaattachment.grid')->middleware($listOfmiddleware);
Route::any('/ipea/ajaxattachment', [ManageIPEAAttachementController::class, 'ajaxipeaattachment'])->name('ajaxipeaattachment')->middleware($listOfmiddleware);
Route::get('/ipea/attachment/edit', [ManageIPEAAttachementController::class, 'edit'])->name('manageipeaattachment.edit')->middleware($listOfmiddleware);
Route::any('/ipea/attachment/delete', [ManageIPEAAttachementController::class, 'destroy'])->name('manageipeaattachment.delete')->middleware($listOfmiddleware);

Route::any('/ipea/attachment/download', [ManageIPEAAttachementController::class, 'download'])->name('manageipeaattachment.download')->middleware($listOfmiddleware);

Route::get('/ipea/emailtemplate', [IPEAEmailTemplate::class, 'index'])->name('emailtemplate.grid')->middleware($listOfmiddleware);
Route::any('/ipea/ajaxemailtemplate', [IPEAEmailTemplate::class, 'ajaxipeatemplate'])->name('ajaxemailtemplate')->middleware($listOfmiddleware);
Route::get('/ipea/emailtemplate/edit', [IPEAEmailTemplate::class, 'edit'])->name('emailtemplate.edit')->middleware($listOfmiddleware);
Route::any('/ipea/emailtemplate/delete', [IPEAEmailTemplate::class, 'destroy'])->name('emailtemplate.delete')->middleware($listOfmiddleware);
Route::any('/ipea/emailtemplate/save', [IPEAEmailTemplate::class, 'save'])->name('emailtemplate.save')->middleware($listOfmiddleware);

Route::get('/ipea', [IpeaCalendarController::class, 'listing'])->name('ipeaListing')->middleware($listOfmiddleware);
Route::get('/ipea-export', [IpeaCalendarController::class, 'ipeaExport'])->name('ipea.export')->middleware($listOfmiddleware);
Route::get('/ipea/get-branch-upcoming-events', [IpeaCalendarController::class, 'getBranchUpcomingEvents'])->name('ipea.getBranchUpcomingEvents')->middleware($listOfmiddleware);
Route::post('/ipea/save-next-step', [IpeaCalendarController::class, 'saveNextStep'])->name('ipea.saveNextStep')->middleware($listOfmiddleware);
//Notifications

Route::get('/notification/list', [NotificationController::class, 'index'])->name('notification.list')->middleware($listOfmiddleware);
Route::get('/notification/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notification.markAsRead')->middleware($listOfmiddleware);
Route::post('/notification/add-snooze', [NotificationController::class, 'addSnooze'])->name('notification.addSnooze')->middleware($listOfmiddleware);
Route::get('/notification/push-notification', [NotificationController::class, 'pushNotification'])->name('notification.pushNotification')->middleware($listOfmiddleware);
Route::get('/notification/notification-count', [NotificationController::class, 'notificationCount'])->name('notification.notificationCount')->middleware($listOfmiddleware);
Route::get('/notification/list/cleared', [NotificationController::class, 'clearedList'])->name('notification.clearedList')->middleware($listOfmiddleware);
Route::get('/notification/undo-notification', [NotificationController::class, 'undoNotification'])->name('notification.undoNotification')->middleware($listOfmiddleware);
Route::get('/notification/sentlist', [NotificationController::class, 'sentList'])->name('notification.sentlist')->middleware($listOfmiddleware);
Route::post('/notification/mark-multiple-as-read', [NotificationController::class, 'markMultipleAsRead'])->name('notification.markMultipleAsRead')->middleware($listOfmiddleware);

//Notes

Route::get('/openotes', [NotesController::class, 'openNotes'])->name('opennotes')->middleware($listOfmiddleware);
Route::post('/savenotes', [NotesController::class, 'saveNotes'])->name('savenotes')->middleware($listOfmiddleware);
Route::get('/trackerhistory', [NotesController::class, 'getTrackerHistory'])->name('trackerhistory')->middleware($listOfmiddleware);
Route::get('/candidate-history', [NotesController::class, 'getCandidatesHistory'])->name('candidateHistory')->middleware($listOfmiddleware);
Route::get('/openemailtemplate', [NotesController::class, 'openEmailTemplate'])->name('openemailtemplate')->middleware($listOfmiddleware);
Route::get('/nth/history', [NotesController::class, 'nthHistory'])->name('nth.nthHistory')->middleware($listOfmiddleware);

//Feature flags
Route::get('/managefeatureflag', [FeatureFlagController::class, 'index'])->name('managefeatureflag')->middleware($listOfmiddleware);
Route::get('/getfeatureflaglistajax', [FeatureFlagController::class, 'getFeatureFlagList'])->name('getfeatureflaglistajax')->middleware($listOfmiddleware);
Route::get('/managefeatureflag/edit/{id}', [FeatureFlagController::class, 'edit'])->name('managefeatureflag.edit')->middleware($listOfmiddleware);
Route::post('/updatefeatureflag', [FeatureFlagController::class, 'updateFeatureFlagList'])->name('managefeatureflag.updatefeatureflag')->middleware($listOfmiddleware);
Route::get('/managefeatureflag/changestatus/{status}/{id}', [FeatureFlagController::class, 'changeStatus'])->name('managefeatureflag.disable')->middleware($listOfmiddleware);

//Suggestion form
Route::any('/getEnhancementSuggestionForm', [RequestFormController::class, 'getEnhancementSuggestionForm'])->name('getEnhancementSuggestionForm')->middleware($listOfmiddleware);
Route::post('/postEnhancementSuggestion', [RequestFormController::class, 'postEnhancementSuggestion'])->name('postEnhancementSuggestion')->middleware($listOfmiddleware);

Route::any('/getReportIssueForm', [RequestFormController::class, 'getReportIssueForm'])->name('getReportIssueForm')->middleware($listOfmiddleware);
Route::post('/postReportIssueForm', [RequestFormController::class, 'postReportIssueForm'])->name('postReportIssueForm')->middleware($listOfmiddleware);

    Route::middleware($listOfmiddleware)->prefix('managetags')->group(function () {
        Route::get('/managetags', [TagController::class, 'index'])->name('managetags');
        Route::get('/form', [TagController::class, 'tagForm'])->name('managetags.form');

        Route::get('/gettaglistajax', [TagController::class, 'data'])->name('gettaglistajax');

        Route::get('/create', [TagController::class, 'create'])->name('managetags.create');

        Route::post('/store', [TagController::class, 'store'])->name('managetags.store');

        Route::get('/edit/{tag}', [TagController::class, 'edit'])->name('managetags.edit');

        Route::put('/update/{tag}', [TagController::class, 'update'])->name('managetags.update');
        Route::post('/status/{id}', [TagController::class, 'toggleStatus']);
    });

    Route::middleware($listOfmiddleware)->prefix('billing')->name('billing.')->group(function () {
        Route::post('tag-modal', [InvoiceTagController::class, 'showModal'])->name('showTagInvoiceModal');
        Route::post('tag-invoices', [InvoiceTagController::class, 'saveTags'])->name('tagInvoices');
    });

    Route::middleware($listOfmiddleware)->prefix('claims')->group(function () {
        Route::get('/{claimId}/tags', [ClaimTagController::class, 'index']);
        Route::post('/{claimId}/tags', [ClaimTagController::class, 'store']);
    });

//Candidates
Route::get('/candidate-details', [CandidatesController::class, 'index'])->name('candidatesDetails')->middleware($listOfmiddleware);
Route::get('/go-to-need-to-hire-map', [CandidatesController::class, 'goToNeedToHireMap'])->name('goToNeedToHireMap')->middleware($listOfmiddleware);
Route::get('/candidates', [CandidatesController::class, 'listing'])->name('candidatesListing')->middleware($listOfmiddleware);
Route::any('/candidatesAjaxListing', [CandidatesController::class, 'ajaxCandidateListing'])->name('candidatesAjaxListing')->middleware($listOfmiddleware);
Route::get('/export-candidates-data', [CandidatesController::class, 'exportCandidatesData'])->name('exportCandidatesData')->middleware($listOfmiddleware);
Route::get('/candidate/show-next-step-form', [CandidatesController::class, 'showNextStepForm'])->name('candidate.showNextStepForm')->middleware($listOfmiddleware);
Route::get('/candidate/load-outcome-form', [CandidatesController::class, 'loadOutcomeForm'])->name('candidate.loadOutcomeForm')->middleware($listOfmiddleware);
Route::post('/candidate/save-outcome', [CandidatesController::class, 'saveOutcome'])->name('candidate.saveOutcome')->middleware($listOfmiddleware);
Route::get('/candidate/{candidateId}/therapy-pdf/{step}', [CandidatesController::class, 'generateTherapyPdf'])->name('candidate.therapy.pdf')->middleware($listOfmiddleware);
Route::any('/getAddCandidateForm', [CandidatesController::class, 'getAddCandidateForm'])->name('getAddCandidateForm')->middleware($listOfmiddleware);
Route::any('/saveNewCandidate', [CandidatesController::class, 'saveNewCandidate'])->name('saveNewCandidate')->middleware($listOfmiddleware);
Route::get('/checkCandidate', [CandidatesController::class, 'checkCandidate'])->name('checkCandidate')->middleware($listOfmiddleware);
Route::any('/getJobListByCategory', [CandidatesController::class, 'getJobListByCategory'])->name('getJobListByCategory')->middleware($listOfmiddleware);
Route::post('/candidate/check-candidate-match', [CandidatesController::class, 'checkCandidateMatch'])->name('candidate.checkCandidateMatch')->middleware($listOfmiddleware);
Route::any('/getcandidateresume', [CandidatesController::class, 'getCandidateResume'])->name('getcandidateresume')->middleware($listOfmiddleware);
Route::get('/candidates/import', [CandidatesController::class, 'getCandidateImport'])->name('candidates.import')->middleware($listOfmiddleware);
Route::post('/candidates/processimport', [CandidatesController::class, 'processCandidateImport'])->name('candidates.processimport')->middleware($listOfmiddleware);
Route::any('/candidates/exporttemplate', [CandidatesController::class, 'downloadSampleCSV'])->name('candidates.exporttemplate')->middleware($listOfmiddleware);
Route::patch('/candidates/update-field', [CandidatesController::class, 'updateField'])
  ->name('candidates.update-field');
//New Events
Route::get('/schedules/add-office-ipea-events', [ScheduleController::class, 'addOfficeIpeaEvent'])->name('schedules.addofficeipeaevent')->middleware($listOfmiddleware);
Route::post('/schedules/save-office-ipea-events', [ScheduleController::class, 'saveOfficeIpeaEvent'])->name('schedules.saveofficeipeaevent')->middleware($listOfmiddleware);
Route::get('/schedules/add-therapy-ipea-events', [ScheduleController::class, 'addTherapyIpeaEvent'])->name('schedules.addtherapyipeaevent')->middleware($listOfmiddleware);
Route::post('/schedules/save-therapy-ipea-events', [ScheduleController::class, 'saveTherapyIpeaEvent'])->name('schedules.savetherapyipeaevent')->middleware($listOfmiddleware);
Route::get('/schedules/attechmentprivew/{id}', [ScheduleController::class, 'privewAttachment'])->name('schedules.attechmentprivew')->middleware($listOfmiddleware);

Route::get('/getstatebranch', [Dashboard::class, 'getStateBranch'])->name('getstatebranch')->middleware($listOfmiddleware);
Route::get('/getocstatebranch', [Dashboard::class, 'getOCStateBranch'])->name('getocstatebranch')->middleware($listOfmiddleware);
Route::get('/getJobviteBranchByState', [Dashboard::class, 'getJobviteBranchByState'])->name('getJobviteBranchByState')->middleware($listOfmiddleware);

// Need to Hire Steps
Route::get('/getNthStepForm', [NeedToHireController::class, 'getNthStepForm'])->name('dashboard.getnthstepform')->middleware($listOfmiddleware);
Route::post('/saveNthSteps', [NeedToHireController::class, 'saveNthSteps'])->name('dashboard.saveNthSteps')->middleware($listOfmiddleware);
Route::get('/nth/list', [NeedToHireController::class, 'nthList'])->name('nth.nthList')->middleware($listOfmiddleware);
Route::get('/nth-checklist-details', [NeedToHireController::class, 'nthChecklistDetails'])->name('nthChecklistDetails')->middleware($listOfmiddleware);
Route::get('/nth/add-nth-form', [NeedToHireController::class, 'addNTHForm'])->name('nth.addNTHForm')->middleware($listOfmiddleware);
Route::get('/nth/get-client-details-in-json', [NeedToHireController::class, 'getClientDetailsInJSON'])->name('nth.getClientDetailsInJSON')->middleware($listOfmiddleware);
Route::post('/nth/save-build-schedule', [NeedToHireController::class, 'saveBuildSchedule'])->name('nth.saveBuildSchedule')->middleware($listOfmiddleware);
Route::get('/nth/export-nth-data', [NeedToHireController::class, 'exportNthData'])->name('exportNthData')->middleware($listOfmiddleware);
Route::get('/nth/get-terminal-payer', [NeedToHireController::class, 'getTerminalPayer'])->name('nth.getTerminalPayer')->middleware($listOfmiddleware);
Route::get('/nth/get-respite-terminal-payer', [NeedToHireController::class, 'getRespiteTerminalPayer'])->name('nth.getRespiteTerminalPayer')->middleware($listOfmiddleware);
Route::post('/nth/send-notification-blank-build-schedule', [NeedToHireController::class, 'blankBuildScheduleNotification'])->name('nth.blankBuildScheduleNotification')->middleware($listOfmiddleware);

Route::get('/getNthSocialMediaForm', [NeedToHireController::class, 'getNthSocialMediaForm'])->name('dashboard.getnthsocialmediaform')->middleware($listOfmiddleware);
Route::post('/saveNthSocialMedia', [NeedToHireController::class, 'saveNthSocialMedia'])->name('dashboard.saventhsocialmedia')->middleware($listOfmiddleware);
Route::post('/moveNthSteps', [NeedToHireController::class, 'moveNthSteps'])->name('dashboard.moventhsteps')->middleware($listOfmiddleware);
Route::get('/nth/validate-client-id', [NeedToHireController::class, 'validateClientId'])->name('nth.validateClientId')->middleware($listOfmiddleware);
Route::get('/nth/validate-client-name', [NeedToHireController::class, 'validateClientName'])->name('nth.validateClientName')->middleware($listOfmiddleware);

//Social Media
Route::any('/get-social-media-dashboard', [Dashboard::class, 'getSocialMediaDashboard'])->name('dashboard.getSocialMediaDashboard')->middleware($listOfmiddleware);
Route::any('/reload-count-social-media', [Dashboard::class, 'reloadCountSocialMedia'])->name('dashboard.reloadCountSocialMedia')->middleware($listOfmiddleware);
Route::get('/get-ads-info-form', [SocialMediaController::class, 'getAdsInfoForm'])->name('socialmedia.getAdsInfoForm')->middleware($listOfmiddleware);
Route::post('/save-ads-info-form', [SocialMediaController::class, 'saveAdsForm'])->name('socialmedia.saveAdsForm')->middleware($listOfmiddleware);
Route::post('/mark-as-completed-ads', [SocialMediaController::class, 'markAsCompletedAds'])->name('socialmedia.markAsCompletedAds')->middleware($listOfmiddleware);
Route::get('/get-social-media-details', [SocialMediaController::class, 'getSocialMediaDetails'])->name('socialmedia.getSocialMediaDetails')->middleware($listOfmiddleware);
Route::get('/get-social-media-close-popup', [SocialMediaController::class, 'getSocialMediaClosePopup'])->name('socialmedia.getSocialMediaClosePopup')->middleware($listOfmiddleware);
Route::any('/social-media-manager', [SocialMediaController::class, 'getAllSocialMedia'])->name('socialmedia.getAllSocialMedia')->middleware($listOfmiddleware);
Route::get('/export-social-media-ad', [SocialMediaController::class, 'exportSocialMediaAd'])->name('socialmedia.exportSocialMediaAd')->middleware($listOfmiddleware);


Route::any('/recruitingexception', [Dashboard::class, 'getRecruitingException'])->name('dashboard.getrecrutingexception')->middleware($listOfmiddleware);
Route::any('/recruitingexceptioncount', [Dashboard::class, 'getRecruitingExceptionCount'])->name('recruitingexceptioncount')->middleware($listOfmiddleware);

Route::any('/get-payrate-approvals', [Dashboard::class, 'getPayrateApprovalsDashboard'])->name('dashboard.getPayrateApprovalsDashboard')->middleware($listOfmiddleware);
Route::any('/reload-count-payrate-approvals', [Dashboard::class, 'reloadCountPayrateApproval'])->name('dashboard.reloadCountPayrateApproval')->middleware($listOfmiddleware);
Route::any('/get-payrate-approvals-confirmation', [Dashboard::class, 'getPayrateApprovalConfirmation'])->name('dashboard.getPayrateApprovalConfirmation')->middleware($listOfmiddleware);
Route::any('/save-payrate-approval', [Dashboard::class, 'savePayrateApproved'])->name('dashboard.savePayrateApproved')->middleware($listOfmiddleware);

//Clinician
Route::get('/clinician', [ClinicianController::class, 'listing'])->name('clinicianListing')->middleware($listOfmiddleware);
Route::get('/clinician/details', [ClinicianController::class, 'getDetails'])->name('clinician.getDetails')->middleware($listOfmiddleware);
Route::post('/clinician/save-clinician-custom-city', [ClinicianController::class, 'saveClinicianCustomCity'])->name('clinician.saveClinicianCustomCity')->middleware($listOfmiddleware);
//Status Change Requests
Route::get('/clinician/status-change-request', [ClinicianController::class, 'statusChangeRequest'])->name('clinician.statusChangeRequest')->middleware($listOfmiddleware);
Route::get('/clinician/getCandidateName', [ClinicianController::class, 'getCandidateName'])->name('clinician.getCandidateName')->middleware($listOfmiddleware);
Route::post('/clinician/status-change-request-save', [ClinicianController::class, 'statusChangeRequestSave'])->name('clinician.statusChangeRequestSave')->middleware($listOfmiddleware);

//Update to NTH
Route::any('/getDetailsForNthTextBlast', [NeedToHireController::class, 'getDetailsForNthTextBlast'])->name('getDetailsForNthTextBlast')->middleware($listOfmiddleware);
Route::any('/updateToNth', [NeedToHireController::class, 'updateToNth'])->name('updatetonth')->middleware($listOfmiddleware);

Route::get('/client', [ClientController::class, 'clientListing'])->name('clientlisting')->middleware($listOfmiddleware);
Route::get('/client/profile', [ClientController::class, 'getClientDetails'])->name('client.getDetails')->middleware($listOfmiddleware);
Route::get('/client/buildschedule', [ClientController::class, 'getBuildScheduleForm'])->name('client.buildschedule')->middleware($listOfmiddleware);
Route::any('/client/validateschedule', [ClientController::class, 'validateSchedule'])->name('client.validateschedule')->middleware($listOfmiddleware);
Route::any('/client/savebuildscheduleform', [ClientController::class, 'saveBuildScheduleForm'])->name('client.savebuildscheduleform')->middleware($listOfmiddleware);
Route::any('/client/showbuildschedulegrid', [ClientController::class, 'showBuildScheduleGrid'])->name('client.showBuildScheduleGrid')->middleware($listOfmiddleware);
Route::get('/exportclientdata', [ClientController::class, 'exportClientData'])->name('exportclientdata')->middleware($listOfmiddleware);
Route::any('/client/saveAcceptsCoverage', [ClientController::class, 'saveAcceptsCoverage'])->name('client.saveAcceptsCoverage')->middleware($listOfmiddleware);
Route::any('/client/saveEligibility', [ClientController::class, 'saveEligibility'])->name('client.saveEligibility')->middleware($listOfmiddleware);
Route::post(
    '/client/saveChangeCallFrequency',
    [ClientController::class, 'saveChangeCallFrequency']
)
->name('client.saveChangeCallFrequency')
->middleware($listOfmiddleware);

Route::get('/clienthistory', [NotesController::class, 'getClientHistory'])->name('clienthistory')->middleware($listOfmiddleware);
Route::get('/clinicianhistory', [NotesController::class, 'getClinicianHistory'])->name('clinicianhistory')->middleware($listOfmiddleware);
Route::any('/clientcareexceptions', [Dashboard::class, 'getClientCareExceptions'])->name('clientcare.getclientcareexceptions')->middleware($listOfmiddleware);
Route::any('/getweeklyinputcall', [Dashboard::class, 'getWeeklyInputCall'])->name('ccc.getweeklyinputcall')->middleware($listOfmiddleware);
Route::any('/saveweeklyinputcall', [Dashboard::class, 'saveWeeklyInputCall'])->name('ccc.saveweeklyinputcall')->middleware($listOfmiddleware);
Route::any('/getmonthlyinputcall', [Dashboard::class, 'getMonthlyInputCall'])->name('ccc.getmonthlyinputcall')->middleware($listOfmiddleware);
Route::any('/savemonthlyinputcall', [Dashboard::class, 'saveMonthlyInputCall'])->name('ccc.savemonthlyinputcall')->middleware($listOfmiddleware);
Route::any('/reload-clientcareexceptions', [Dashboard::class, 'reloadCountClientCareExceptions'])->name('clientcare.reloadcountclientcareexceptions')->middleware($listOfmiddleware);
Route::get('/exceptionaddressed', [Dashboard::class, 'getExceptionAddressed'])->name('dashboard.getexceptionaddressed')->middleware($listOfmiddleware);
Route::post('/exceptionaddressed', [Dashboard::class, 'saveExceptionAddressed'])->name('dashboard.saveexceptionaddressed')->middleware($listOfmiddleware);
//Trach Vent Class
Route::get('/managetrachventclass', [TrachVentClassController::class, 'index'])->name('managetrachventclass')->middleware($listOfmiddleware);
Route::get('/gettrachventclasslistajax', [TrachVentClassController::class, 'getTrachVentClassList'])->name('gettrachventclasslistajax')->middleware($listOfmiddleware);
Route::get('/gettrachventscheduleclass', [TrachVentClassOccurrenceController::class, 'getTrachVentScheduleClass'])->name('gettrachventscheduleclass')->middleware(array_merge($listOfmiddleware, ['trachventclass.scheduling']));
Route::get('/trachventclass/calendar', [TrachVentClassOccurrenceController::class, 'getTrachVentClassCalender'])->name('trachventclass.calender')->middleware($listOfmiddleware);
Route::get('/trachventclass/get-week-events', [TrachVentClassOccurrenceController::class, 'getTrachVentClassWeekEvents'])->name('trachventclass.getWeekEvents')->middleware($listOfmiddleware);
Route::get('/trachventclass/get-trachvent-class-info', [TrachVentClassOccurrenceController::class, 'getTrachVentClassInfo'])->name('getTrachVentClassInfo')->middleware($listOfmiddleware);
Route::get('/managetrachventclass/add', [TrachVentClassController::class, 'add'])->name('managetrachventclass.add')->middleware($listOfmiddleware);
Route::post('/savescheduleclass', [TrachVentClassController::class, 'saveScheduleClass'])->name('managetrachventclass.savescheduleclass')->middleware($listOfmiddleware);
Route::get('/managetrachventclass/edit/{id}', [TrachVentClassController::class, 'edit'])->name('managetrachventclass.edit')->middleware($listOfmiddleware);
Route::post('/updatescheduleclass', [TrachVentClassController::class, 'updateScheduleClass'])->name('managetrachventclass.updatescheduleclass')->middleware($listOfmiddleware);
Route::get('/trachventclass/get-calendar-search-string', [TrachVentClassOccurrenceController::class, 'getTrachVentCalendarSearchString'])->name('trachventclass.getTrachVentCalendarSearchString')->middleware($listOfmiddleware);
Route::get('/managetrachventclass/class-categories', [ClassCategoryController::class, 'index'])->name('managetrachventclass.class-categories.index')->middleware($listOfmiddleware);
Route::post('/managetrachventclass/class-categories', [ClassCategoryController::class, 'store'])->name('managetrachventclass.class-categories.store')->middleware($listOfmiddleware);
Route::delete('/managetrachventclass/class-categories/{id}', [ClassCategoryController::class, 'destroy'])->name('managetrachventclass.class-categories.destroy')->middleware($listOfmiddleware);
Route::get('/trachventclass/search-clients', [TrachVentClassOccurrenceController::class, 'searchClients'])->name('trachventclass.searchClients')->middleware(array_merge($listOfmiddleware, ['trachventclass.scheduling']));
Route::post('/trachventclass/{occurrence}/enroll', [TrachVentClassOccurrenceController::class, 'enroll'])->name('trachventclass.enroll')->middleware(array_merge($listOfmiddleware, ['trachventclass.scheduling']));
Route::delete('/trachventclass/{occurrence}/registrations/{registration}', [TrachVentClassOccurrenceController::class, 'cancelRegistration'])->name('trachventclass.cancelRegistration')->whereNumber(['occurrence', 'registration'])->middleware(array_merge($listOfmiddleware, ['trachventclass.scheduling']));
Route::get('/trachventclass/{occurrence}/registrations/{registration}/reschedule-options', [TrachVentClassOccurrenceController::class, 'rescheduleOptions'])->name('trachventclass.rescheduleOptions')->whereNumber(['occurrence', 'registration'])->middleware(array_merge($listOfmiddleware, ['trachventclass.scheduling']));
Route::patch('/trachventclass/{occurrence}/registrations/{registration}/reschedule', [TrachVentClassOccurrenceController::class, 'rescheduleRegistration'])->name('trachventclass.rescheduleRegistration')->whereNumber(['occurrence', 'registration'])->middleware(array_merge($listOfmiddleware, ['trachventclass.scheduling']));
Route::delete('/trachventclass/{occurrence}/destroyOccurrence', [TrachVentClassOccurrenceController::class, 'destroyOccurrence'])->name('trachventclass.destroyOccurrence')->whereNumber('occurrence')->middleware(array_merge($listOfmiddleware, ['trachventclass.cancellation']));
Route::delete('/trachventclass/{occurrence}/destroyFutureOccurrences', [TrachVentClassOccurrenceController::class, 'destroyFutureOccurrences'])->name('trachventclass.destroyFutureOccurrences')->whereNumber('occurrence')->middleware(array_merge($listOfmiddleware, ['trachventclass.cancellation']));
Route::delete('/trachventclass/{occurrence}/destroyClassOccurrences', [TrachVentClassOccurrenceController::class, 'destroyClassOccurrences'])->name('trachventclass.destroyClassOccurrences')->whereNumber('occurrence')->middleware(array_merge($listOfmiddleware, ['trachventclass.cancellation']));

// Manage Hub Cities
Route::get('/hubcities', [HubCitiesController::class, 'index'])->name('hubcities')->middleware($listOfmiddleware);
Route::get('/hubcities/add', [HubCitiesController::class, 'add'])->name('hubcities.add')->middleware($listOfmiddleware);
Route::post('/hubcities/save', [HubCitiesController::class, 'save'])->name('hubcities.save')->middleware($listOfmiddleware);
Route::get('/hubcities/edit', [HubCitiesController::class, 'edit'])->name('hubcities.edit')->middleware($listOfmiddleware);
Route::get('/hubcities/delete', [HubCitiesController::class, 'delete'])->name('hubcities.delete')->middleware($listOfmiddleware);

//Intake
Route::get('/intake/get-add-intake-form', [IntakeController::class, 'addIntakeForm'])->name('intake.addIntakeForm')->middleware($listOfmiddleware);
Route::get('/intake/load-more-nurse-form', [IntakeController::class, 'loadMoreNurseForm'])->name('intake.loadMoreNurseForm')->middleware($listOfmiddleware);
Route::post('/intake/save', [IntakeController::class, 'save'])->name('intake.save')->middleware($listOfmiddleware);
Route::get('/intake/get-edit-intake-form', [IntakeController::class, 'editIntakeForm'])->name('intake.editIntakeForm')->middleware($listOfmiddleware);
Route::get('/intake/check-client-patientAccount', [IntakeController::class, 'checkPatientAccount'])->name('intake.checkPatientAccount')->middleware($listOfmiddleware);
Route::get('/clinicians/search', [IntakeController::class, 'search'])->name('clinician.search');
Route::post('/clinician/validate', [IntakeController::class, 'validateClinician'])->name('clinician.validate');

//After Hours
Route::get('/afterhours/calendar', [AfterHoursController::class, 'getAfterHoursCalender'])->name('afterhours.calender')->middleware($listOfmiddleware);
Route::get('/afterhours/get-week-events', [AfterHoursController::class, 'getAfterHoursWeekEvents'])->name('afterhours.getWeekEvents')->middleware($listOfmiddleware);
Route::get('/afterhours/get-afterhours-schedule-info', [AfterHoursController::class, 'getAfterHoursInfo'])->name('afterhours.getAfterHoursInfo')->middleware($listOfmiddleware);
Route::post('/afterhours/save-afterhours-schedule', [AfterHoursController::class, 'saveAfterHoursInfo'])->name('afterhours.saveAfterHoursInfo')->middleware($listOfmiddleware);
Route::get('/manageafterhours/add', [AfterHoursController::class, 'add'])->name('manageafterhours.add')->middleware($listOfmiddleware);
Route::get('/manageafterhours/addafterhours', [AfterHoursController::class, 'addafterhours'])->name('manageafterhours.addafterhours')->middleware($listOfmiddleware);
Route::post('/saveafterhours', [AfterHoursController::class, 'saveAfterhoursShift'])->name('manageafterhours.saveafterhoursshift')->middleware($listOfmiddleware);
Route::get('/manageafterhours', [AfterHoursController::class, 'index'])->name('manageafterhours')->middleware($listOfmiddleware);
Route::get('/getafterhourslistajax', [AfterHoursController::class, 'getAfterhoursShiftList'])->name('getafterhourslistajax')->middleware($listOfmiddleware);
Route::get('/manageafterhours/edit/{id}', [AfterHoursController::class, 'edit'])->name('manageafterhours.edit')->middleware($listOfmiddleware);
Route::post('/updateafterhoursshift', [AfterHoursController::class, 'updateAfterhoursShift'])->name('manageafterhours.updateafterhoursshift')->middleware($listOfmiddleware);
Route::get('/afterhours/setschedule', [AfterHoursController::class, 'setSchedule'])->name('afterhours.setschedule')->middleware($listOfmiddleware);
Route::get('/afterhours/showcalendarpopup/{id}', [AfterHoursController::class, 'showAfterHoursCalenderPopup'])->name('afterhours.showcalenderpopup')->middleware($listOfmiddleware);
Route::post('/deleteafterhoursshift', [AfterHoursController::class, 'deleteAfterHoursShift'])->name('afterhours.deleteAfterHoursShift')->middleware($listOfmiddleware);
Route::get('/deleteafterhoursshiftrecord/{id}', [AfterHoursController::class, 'deleteAfterHoursShiftRecord'])->name('afterhours.deleteAfterHoursShiftrecord')->middleware($listOfmiddleware);
Route::get('/afterhours/get-ahjournal-data',
[AfterHoursController::class, 'getAhJournalData'])
->name('afterhours.getAhJournalData')->middleware($listOfmiddleware);
Route::get('/afterhours/clearnote',
[AfterHoursController::class, 'clearNote'])
->name('afterhours.clearNote')->middleware($listOfmiddleware);
Route::get('/afterhours/getJournalNoteDetails',
[AfterHoursController::class, 'getJournalNoteDetails'])
->name('afterhours.getJournalNoteDetails')->middleware($listOfmiddleware);
Route::any('/reload-journal-note',
[AfterHoursController::class, 'reloadAhJournalData'])
->name('afterhours.reloadAhJournalData')->middleware($listOfmiddleware);
//Triage
Route::get('/triage/get-add-triage-call-form', [TriageController::class, 'addTriageCallForm'])->name('triage.addTriageCallForm')->middleware($listOfmiddleware);
Route::post('/triage/save', [TriageController::class, 'save'])->name('triage.save')->middleware($listOfmiddleware);
Route::post('/triage/edit', [TriageController::class, 'editTriage'])->name('triage.edit')->middleware($listOfmiddleware);
Route::any('/triage/calls', [TriageController::class, 'getAllCalls'])->name('triage.getallcalls')->middleware($listOfmiddleware);
Route::post('/triagefollowup/save', [TriageController::class, 'saveFollowup'])->name('triagefollowup.save')->middleware($listOfmiddleware);
Route::get('/triage/checking-client-calls-availability', [TriageController::class, 'checkingClientCallsAvailability'])->name('triage.checkingClientCallsAvailability')->middleware($listOfmiddleware);
Route::any('/triagedata', [Dashboard::class, 'getTriageData'])->name('triage.getTriageData')->middleware($listOfmiddleware);
Route::any('/reload-triagedata', [Dashboard::class, 'reloadTriageData'])->name('triage.reloadTriageData')->middleware($listOfmiddleware);
Route::get('/triage/get-follow-up-form', [TriageController::class, 'getFollowUpForm'])->name('triage.getFollowUpForm')->middleware($listOfmiddleware);
Route::post('/triage/savepreviousdata', [TriageController::class, 'savePreviousData'])->name('triageprevious.save')->middleware($listOfmiddleware);
Route::get('/triage/export', [TriageController::class, 'exportTriageCallData'])->name('triage.exportTriageCallData')->middleware($listOfmiddleware);
Route::get('/triage/get-outcome-details', [TriageController::class, 'getOutcomeDetails'])->name('triage.getOutcomeDetails')->middleware($listOfmiddleware);
Route::post('/triage/save-notes-triage-follow-up', [TriageController::class, 'saveNotesTriageFollowUp'])->name('triagefollowup.saveNotesTriageFollowUp')->middleware($listOfmiddleware);
Route::get('/triage/get-terminal-payer', [TriageController::class, 'getTerminalPayer'])->name('triage.getTerminalPayer')->middleware($listOfmiddleware);
Route::any('/triage/edit-follow-up', [TriageController::class, 'editFollowUp'])->name('triage.editFollowUp')->middleware($listOfmiddleware);
Route::get('/triage/get-emergency-department', [TriageController::class, 'getEmergencyDepartment'])->name('triage.getEmergencyDepartment')->middleware($listOfmiddleware);
Route::get('/triage/get-client-name', [TriageController::class, 'getClientName'])->name('triage.getclientname')->middleware($listOfmiddleware);
Route::get('/triage/get-triage-edit-form', [TriageController::class, 'getTriageEditForm'])->name('triage.getTriageEditForm')->middleware($listOfmiddleware);
Route::get('/triage/load-triage-edit-form', [TriageController::class, 'loadTriageEditForm'])->name('triage.loadTriageEditForm')->middleware($listOfmiddleware);
Route::any('/triage/edit-triage-call-date', [TriageController::class, 'editTriageCallDate'])->name('triage.editTriageCallDate')->middleware($listOfmiddleware);
Route::any('/triage/get-reporter-list', [TriageController::class, 'getReporterList'])->name('triage.getReporterList')->middleware($listOfmiddleware);

//Recruiter Scorecard
Route::get('/recruiterscorecard/get-recruiterscorecard-form', [RecruiterScorecardController::class, 'index'])->name('recruiterScorecardForm')->middleware($listOfmiddleware);
Route::any('/recruiterscorecard/get-recruiterscorecard-data-by-recruiter', [RecruiterScorecardController::class, 'getRecruiterscorecardDataByRecruiter'])->name('getRecruiterscorecardDataByRecruiter')->middleware($listOfmiddleware);
Route::get('/recruiterscorecard', [RecruiterScorecardController::class, 'recruiterScoreCard'])->name('recruiterscorecard')->middleware($listOfmiddleware);
Route::get('/getrecruiterscorecardlist', [RecruiterScorecardController::class, 'getRecruiterScoreCardList'])->name('getrecruiterscorecardlist')->middleware($listOfmiddleware);
Route::post('/recruiterscorecard/submitRecruiterScorecard', [RecruiterScorecardController::class, 'submitRecruiterScorecard'])->name('submitRecruiterScorecard')->middleware($listOfmiddleware);
Route::any('/reports/recruiterscorecard', [RecruiterScorecardController::class, 'recruiterScorecardReport'])->name('recruiterScorecardReport')->middleware($listOfmiddleware);
Route::get('/recruiterscorecard/delete', [RecruiterScorecardController::class, 'delete'])->name('recruiterScoreCardDelete')->middleware($listOfmiddleware);

Route::get('/triage/get-triage-call-details', [TriageController::class, 'getTriageCallDetails'])->name('triage.getTriageCallDetails')->middleware($listOfmiddleware);
Route::get('/triage/get-triage-call-of-client', [TriageController::class, 'getTriageCallOfClient'])->name('triage.getTriageCallOfClient')->middleware($listOfmiddleware);
Route::get('/triage/get-follow-up-call-details', [TriageController::class, 'getFollowUpCallDetails'])->name('triage.getFollowUpCallDetails')->middleware($listOfmiddleware);
Route::get('/triage/get-triage-activity', [NotesController::class, 'getTriageActivity'])->name('triage.getTriageActivity')->middleware($listOfmiddleware);

//Recruiter report
Route::any('/reports/recruiting', [RecruitingController::class, 'recruiting'])->name('recruiting')->middleware($listOfmiddleware);
Route::get('/exportbonusreport', [RecruitingController::class, 'exportBonusReport'])->name('exportBonusReport')->middleware($listOfmiddleware);
Route::any('/setbonuspaid', [RecruitingController::class, 'setBonusPaidmodal'])->name('setBonusPaidmodal')->middleware($listOfmiddleware);
Route::any('/updatebonusoverride', [RecruitingController::class, 'updateBonusOverRide'])->name('updatebonusoverride')->middleware($listOfmiddleware);

//Coverage
Route::get('/coverage/validate-discipline-name', [CoverageController::class, 'validateDisciplineName'])->name('coverage.validateDisciplineName')->middleware($listOfmiddleware);
Route::post('/coverage/save', [CoverageController::class, 'save'])->name('coverage.save')->middleware($listOfmiddleware);
Route::get('/missedvisits/checkform', [Dashboard::class, 'getMissedVisitsCheckForm'])->name('dashboard.missedvisitform')->middleware($listOfmiddleware);
Route::any('/missedvisits/setmissedvisits', [Dashboard::class, 'setMissedVisits'])->name('dashboard.setmissedvisitform')->middleware($listOfmiddleware);
Route::get('/coverage/list', [CoverageController::class, 'coverageList'])->name('coverage.list')->middleware($listOfmiddleware);
Route::get('/export-coverage-data', [CoverageController::class, 'exportCoverageData'])->name('coverage.exportdata')->middleware($listOfmiddleware);
Route::any('/clientcarecoveragetracker', [Dashboard::class, 'getClientCareCoverageTracker'])->name('clientcare.getclientcarecoveragetracker')->middleware($listOfmiddleware);
Route::any('/reload-clientcarecoveragetracker', [Dashboard::class, 'reloadCountClientCareCoverageTracker'])->name('clientcare.reloadcountclientcarecoveragetracker')->middleware($listOfmiddleware);
Route::get('/getcoveragetrackerstepform', [CoverageController::class, 'getCoverageTrackerStepForm'])->name('dashboard.getcoveragetrackerstepform')->middleware($listOfmiddleware);
Route::post('/saveCoverageTrackerStep', [CoverageController::class, 'saveCoverageTrackerStep'])->name('dashboard.saveCoverageTrackerStep')->middleware($listOfmiddleware);
Route::get('/missedvisits/checkforms', [Dashboard::class, 'getMissedVisitsCheckForms'])->name('dashboard.missedvisitforms')->middleware($listOfmiddleware);
Route::get('/getcoverageshifttrackerform', [CoverageController::class, 'getCoverageShiftTrackerForm'])->name('coveragetracker.getcoverageshifttrackerform')->middleware($listOfmiddleware);
Route::post('/saveCoverageShiftTrackerForm', [CoverageController::class, 'saveCoverageShiftTrackerForm'])->name('coveragetracker.saveCoverageShiftTrackerForm')->middleware($listOfmiddleware);
Route::get('/get-coverage-tracker-activity', [CoverageController::class, 'getCoverageTrackerActivity'])->name('coverage.getCoverageTrackerActivity')->middleware($listOfmiddleware);
Route::get('/delete-coverage-tracker-record', [CoverageController::class, 'deleteCoverageTrackerRecord'])->name('coverage.deleteCoverageTrackerRecord')->middleware($listOfmiddleware);
Route::get('/get-edit-coverage-tracker-form', [CoverageController::class, 'getEditCoverageTrackerRecord'])->name('coverage.getEditCoverageTrackerRecord')->middleware($listOfmiddleware);
Route::post('/updateCoverageShiftTracker', [CoverageController::class, 'updateCoverageShiftTracker'])->name('coveragetracker.updateCoverageShiftTrackerForm')->middleware($listOfmiddleware);

Route::get('/getclientintakeoutcomform', [CoverageController::class, 'getClientIntakeOutcom'])->name('getclientintakeoutcom')->middleware($listOfmiddleware);
Route::post('/saveclientintakeoutcomform', [CoverageController::class, 'saveClientIntakeOutcom'])->name('saveclientintakeoutcom')->middleware($listOfmiddleware);
Route::get('/get-coverage-details', [CoverageController::class, 'getCoverageDetails'])->name('getCoverageDetails')->middleware($listOfmiddleware);
Route::get('/coverage/getdeletepopup', [CoverageController::class, 'showCoverageTrackerDeletePopup'])->name('coverage.showdeletecoveragepopup')->middleware($listOfmiddleware);
Route::get('/coverage/makecoveragecompleted', [CoverageController::class, 'makeCoverageCompleted'])->name('coverage.makecoveragecompleted')->middleware($listOfmiddleware);

//Regions
Route::get('/region', [RegionController::class, 'index'])->name('region')->middleware($listOfmiddleware);
Route::get('/regionajaxlist', [RegionController::class, 'getReginList'])->name('regionajaxlist')->middleware($listOfmiddleware);
Route::get('/region/add', [RegionController::class, 'add'])->name('region.add');
Route::post('/saveregion', [RegionController::class, 'saveRegion'])->name('region.saveregion');
Route::get('/manageregion/edit/{id}', [RegionController::class, 'edit'])->name('region.edit');
Route::post('/updateregion', [RegionController::class, 'updateRegion'])->name('region.updateregion');
Route::get('/manageregion/delete/{id}', [RegionController::class, 'destroy'])->name('region.destroy');

//job category
Route::get('/jobcategory', [JobCategoryControllers::class, 'index'])->name('jobcategory')->middleware($listOfmiddleware);
Route::get('/jobcategoryjaxlist', [JobCategoryControllers::class, 'getJobCategoryList'])->name('jobcategoryjaxlist')->middleware($listOfmiddleware);
Route::get('/jobcategory/add', [JobCategoryControllers::class, 'add'])->name('jobcategory.add');
Route::post('/savejobcategory', [JobCategoryControllers::class, 'saveJobCategory'])->name('jobcategory.save');
Route::get('/managejobcategory/edit/{id}', [JobCategoryControllers::class, 'edit'])->name('jobcategory.edit');
Route::get('/managejobcategory/delete/{id}', [JobCategoryControllers::class, 'destroy'])->name('jobcategory.destroy');
Route::get('/managejobcategory/toggle/{id}', [JobCategoryControllers::class, 'toggle'])->name('jobcategory.toggle');

//Supplies
Route::get('/supplies/add-supplies-request', [SuppliesController::class, 'addSuppliesRequest'])->name('supplies.addSuppliesRequest')->middleware($listOfmiddleware);
Route::get('/supplies/load-more-supplies-item-form', [SuppliesController::class, 'loadMoreSuppliesItemForm'])->name('supplies.loadMoreSuppliesItemForm')->middleware($listOfmiddleware);
Route::post('/supplies/save', [SuppliesController::class, 'save'])->name('supplies.save')->middleware($listOfmiddleware);
Route::any('/supplies/reload-count-supply-requests', [SuppliesController::class, 'getSupplyRequestsDashboard'])->name('supplies.reloadCountSupplyRequests')->middleware($listOfmiddleware);
Route::any('/supplies/reload-count-for-supply-requests', [SuppliesController::class, 'reloadCountSupplyRequests'])->name('supplies.reloadCountForSupplyRequests')->middleware($listOfmiddleware);
Route::get('/supplies/get-supply-requests-dashboard', [SuppliesController::class, 'getSupplyRequestsDashboard'])->name('supplies.getSupplyRequestsDashboard')->middleware($listOfmiddleware);
Route::get('/supplies/list', [SuppliesController::class, 'list'])->name('supplies.list')->middleware($listOfmiddleware);
Route::get('/supplies/get-supplies-request-details', [SuppliesController::class, 'getSuppliesRequestDetails'])->name('supplies.getSuppliesRequestDetails')->middleware($listOfmiddleware);
Route::get('pdfview',array('as'=>'pdfview','uses'=>'CandidatesController@pdfView'))->middleware($listOfmiddleware);
Route::get('/supplies/fulfill-supply-requests', [SuppliesController::class, 'fulfillSupplyRequests'])->name('supplies.fulfillSupplyRequests')->middleware($listOfmiddleware);
Route::get('/supplies/acknowledge-supply-requests', [SuppliesController::class, 'acknowledgeSupplyRequests'])->name('supplies.acknowledgeSupplyRequests')->middleware($listOfmiddleware);
Route::get('/supplies/export-supplies', [SuppliesController::class, 'exportSupplies'])->name('supplies.exportSupplies')->middleware($listOfmiddleware);
Route::post('/supplies/delete', [SuppliesController::class, 'delete'])
->name('supplies.delete')->middleware($listOfmiddleware);

Route::get('/supplies/validate-supply-requests-for-selected-client',
[SuppliesController::class, 'validateSupplyRequestsForSelectedClient'])
->name('supplies.validateSupplyRequestsForSelectedClient')
->middleware($listOfmiddleware);

Route::get('/supplies/change-outcome-to-supply-requests',
    [SuppliesController::class, 'changeOutcomeToSupplyRequests']
    )->name('supplies.changeOutcomeToSupplyRequests')
    ->middleware($listOfmiddleware);
Route::post('/supplies/save-outcome-for-request-supplies',
        [SuppliesController::class, 'saveOutcomeForRequestSupplies']
    )->name('supplies.saveOutcomeForRequestSupplies')
    ->middleware($listOfmiddleware);

Route::get('/supplies/checkCompleted',
[SuppliesController::class, 'checkCompleted'])
->name('supplies.checkCompleted')
->middleware($listOfmiddleware);

Route::any('/cccprogressbar', [Dashboard::class, 'getCCCProgressBar'])->name('ccc.progressbar')->middleware($listOfmiddleware);

//CCC Dashboard Schedules
Route::any('/ccc/get-client-care-coordinator-schedules', [ClientCareCoordinatorController::class, 'getClientCareCoordinatorSchedules'])->name('ccc.getClientCareCoordinatorSchedules')->middleware($listOfmiddleware);
Route::any('/ccc/reload-client-care-coordinator-schedules', [ClientCareCoordinatorController::class, 'reloadClientCareCoordinatorSchedules'])->name('ccc.reloadClientCareCoordinatorSchedules')->middleware($listOfmiddleware);
Route::any('/ccc/get-client-care-coordinator-hour-comparison', [ClientCareCoordinatorController::class, 'getClientCareCoordinatorHourComparison'])->name('ccc.getClientCareCoordinatorHourComparison')->middleware($listOfmiddleware);
Route::any('/ccc/reload-client-care-coordinator-hour-comparison', [ClientCareCoordinatorController::class, 'reloadClientCareCoordinatorHourComparison'])->name('ccc.reloadClientCareCoordinatorHourComparison')->middleware($listOfmiddleware);
Route::any('/ccc/get-schedules-by-week-and-compare', [ClientCareCoordinatorController::class, 'getSchedulesByWeekAndCompare'])->name('ccc.getSchedulesByWeekAndCompare')->middleware($listOfmiddleware);

Route::any('/ccc/get-clinician-no-longer-on-case-nth-schedule', [ClientCareCoordinatorController::class, 'getClinicianNoLongerOnCaseNthSchedule'])->name('ccc.getClinicianNoLongerOnCaseNthSchedule')->middleware($listOfmiddleware);
Route::any('/ccc/save-clinician-no-longer-on-case-nth-schedule', [ClientCareCoordinatorController::class, 'saveClinicianNoLongerOnCaseNthSchedule'])->name('ccc.saveNoLongerOnCaseNthSchedule')->middleware($listOfmiddleware);

Route::any('/ccc/get-clinician-no-longer-on-case', [ClientCareCoordinatorController::class, 'getClinicianNoLongerOnCase'])->name('ccc.getClinicianNoLongerOnCase')->middleware($listOfmiddleware);
Route::any('/ccc/reload-clinician-no-longer-on-case', [ClientCareCoordinatorController::class, 'reloadClinicianNoLongerOnCase'])->name('ccc.reloadClinicianNoLongerOnCase')->middleware($listOfmiddleware);
Route::any('/ccc/show-clinician-no-longer-on-case-next-step-form', [ClientCareCoordinatorController::class, 'showClinicianNoLongerOnCaseNextStepForm'])->name('ccc.showClinicianNoLongerOnCaseNextStepForm')->middleware($listOfmiddleware);
Route::any('/cc/save-clinician-no-longer-on-case-outcomeform', [ClientCareCoordinatorController::class, 'saveClinicianNoLongerOnCaseOutcomeForm'])->name('ccc.saveClinicianNoLongerOnCaseOutcomeForm')->middleware($listOfmiddleware);
//CCR
Route::get('/ccr', [CCRController::class, 'getCCRListing'])->name('ccrListing')->middleware($listOfmiddleware);
Route::get('/exportccr', [CCRController::class, 'exportCCRData'])->name('ccr.exportData')->middleware($listOfmiddleware);
Route::any('/clinicalcasemanager', [CCRController::class, 'getClinicalCase'])->name('dashboard.getclinicalcase')->middleware($listOfmiddleware);
Route::any('/reload-clinicalcasemanager', [CCRController::class, 'reloadCountClinicalCase'])->name('dashboard.reloadcountclinicalcase')->middleware($listOfmiddleware);
Route::get('/showsnsform', [CCRController::class, 'showSNSForm'])->name('ccr.showsnsform')->middleware($listOfmiddleware);
Route::any('/savesnsform', [CCRController::class, 'saveCCRScheduleNext'])->name('ccr.saveschedulenext')->middleware($listOfmiddleware);
Route::any('/assignedCcr', [CCRController::class, 'assignedCcr'])->name('assignedCcr')->middleware($listOfmiddleware);
Route::any('/seetheccr', [CCRController::class, 'acknowledgeCcr'])->name('acknowledgeCcr')->middleware($listOfmiddleware);
Route::any('/assignedCcm', [CCRController::class, 'assignedCcm'])->name('assignedCcm')->middleware($listOfmiddleware);
Route::any('/therapist/applications-list', [CCRController::class, 'getTherapistApplications'])->name('therapist.get-applications')->middleware($listOfmiddleware);

//Message Widget

// Route::any('/message', [MessageController::class, 'index'])->name('index')->middleware($listOfmiddleware);
Route::any('/message/get-token', [MessageController::class, 'getToken'])->name('messageWidget.getToken')->middleware($listOfmiddleware);
Route::any('/message/get-files-list', [MessageController::class, 'getFileList'])->name('messageWidget.getFileList')->middleware($listOfmiddleware);
Route::any('/message/full-page-view', [MessageController::class, 'fullPageView'])->name('messageWidget.fullPageView')->middleware($listOfmiddleware);
Route::any('/message/get-conversations-by-candidate-and-clinician', [MessageController::class, 'getConversationsByCandidateAndClinician'])->name('messageWidget.getConversationsByCandidateAndClinician')->middleware($listOfmiddleware);
Route::any('/message/add-conversation', [MessageController::class, 'addConversationToDB'])->name('messageWidget.addConversationToDB')->middleware($listOfmiddleware);
Route::any('/message/set-conversation-to-read', [MessageController::class, 'setConversationToRead'])->name('messageWidget.setConversationToRead')->middleware($listOfmiddleware);
Route::any('/message/get-messages-by-conversation-id', [MessageController::class, 'getMessagesByConversationId'])->name('messageWidget.getMessagesByConversationId')->middleware($listOfmiddleware);
Route::any('/message/get-conversation-detail', [MessageController::class, 'getConversationDetail'])->name('messageWidget.getConversationDetail')->middleware($listOfmiddleware);
Route::any('/message/set-conversation-to-archive', [MessageController::class, 'setConversationToArchive'])->name('messageWidget.setConversationToArchive')->middleware($listOfmiddleware);
Route::any('/message/add-participant-to-conversation', [MessageController::class, 'addParticipantToConversation'])->name('messageWidget.addParticipantToConversation')->middleware($listOfmiddleware);
Route::any('/message/update-user-id-for-take-over-conversation', [MessageController::class, 'updateUserIdForTakeOverConversation'])->name('messageWidget.updateUserIdForTakeOverConversation')->middleware($listOfmiddleware);





Route::get('/message/attachment', [MessageController::class, 'list'])->name('managemessageattachment.list')->middleware($listOfmiddleware);
Route::get('/message/attachment/add', [MessageController::class, 'add'])->name('managemessageattachment.add')->middleware($listOfmiddleware);
Route::get('/message/attachment/edit', [MessageController::class, 'edit'])->name('managemessageattachment.edit')->middleware($listOfmiddleware);
Route::any('/message/attachment/delete', [MessageController::class, 'destroy'])->name('managemessageattachment.delete')->middleware($listOfmiddleware);
Route::any('/message/attachment/download', [MessageController::class, 'download'])->name('managemessageattachment.download')->middleware($listOfmiddleware);
Route::get('/messagecenter', [MessageController::class, 'messageCenterList'])->name('messagecenter.list')->middleware($listOfmiddleware);
Route::any('/dashboardmessagecenter', [MessageController::class, 'getMessageCenterList'])->name('dashboard.getMessageCenterList')->middleware($listOfmiddleware);
Route::any('/reload-dashboardmessagecenter', [MessageController::class, 'reloadCountMessageCenterList'])->name('dashboard.reloadcountmessagecenterlist')->middleware($listOfmiddleware);

Route::any('/massmessage', [MassMessageController::class, 'list'])->name('massmessage.list')->middleware($listOfmiddleware);
Route::any('/massmessage/save', [MassMessageController::class, 'saveMassMessage'])->name('save.massmessage')->middleware($listOfmiddleware);
Route::any('/massmessage/getmasstextforms', [MassMessageController::class, 'getMassTextForms'])->name('getmasstextforms')->middleware($listOfmiddleware);

Route::any('/reload-intakeexceptions', [IntakeController::class, 'reloadCountIntakeExceptions'])->name('intake.reloadcountintakeexceptions')->middleware($listOfmiddleware);
Route::any('/intakeexceptions', [IntakeController::class, 'getIntakeExceptions'])->name('intake.getintakeexceptions')->middleware($listOfmiddleware);
Route::any('/intake/markunassignedcareteam', [IntakeController::class, 'markUnassignedCareTeam'])->name('intake.unassignedcareteam')->middleware($listOfmiddleware);

Route::any('/reload-intakestafftransfers', [
    IntakeController::class,
    'reloadCountIntakeStaffTransfers'
])
->name('intake.reloadcountintakestafftransfers')
->middleware($listOfmiddleware);
Route::any('/intakestafftransfers', [
    IntakeController::class,
    'getIntakeStaffTransfers'
])
->name('intake.getintakestafftransfers')
->middleware($listOfmiddleware);
Route::any('/intakestafftransfers-outcome', [
    IntakeController::class,
    'getIntakeStaffTransfersOutcome'
])
->name('intake.getIntakeStaffTransfersOutcome')
->middleware($listOfmiddleware);
Route::any('/intake', [
    IntakeController::class,
    'list'
])
->name('intake.list')
->middleware($listOfmiddleware);
Route::any('/intake/export', [
    IntakeController::class,
    'export'
])
->name('intake.export')
->middleware($listOfmiddleware);
Route::any('/intakestafftransfers-outcome-save', [
    IntakeController::class,
    'saveOutcome'
])
->name('intake.saveOutcome')
->middleware($listOfmiddleware);


Route::any('/testdbconnection', [SnowFlexController::class, 'testconnnection'])->name('testdbconnection');

Route::any('right-signature/pay-rate-agreements-list', [ClinicianController::class, 'payRateAgreementsList'])->name('rightSignature.payRateAgreementsList')->middleware($listOfmiddleware);
Route::any('right-signature/document-download', [ClinicianController::class, 'documentDownload'])->name('rightSignature.documentDownload')->middleware($listOfmiddleware);
Route::any('/rightsignature/gettoken', [RightSignatureController::class, 'getToken'])->name('gettocken');
Route::any('/rightsignature/responsetoken', [RightSignatureController::class, 'responseToken'])->name('responseTocken');
Route::any('/rightsignature/getdocument', [RightSignatureController::class, 'getDocumentDescription'])->name('getdocumentdescription');

Route::get('/fieldevaluation', [FieldEvaluationController::class, 'list'])->name('fieldevaluation.list')->middleware($listOfmiddleware);
Route::get('/editfieldevaluation', [FieldEvaluationController::class, 'edit'])->name('fieldevaluation.edit')->middleware($listOfmiddleware);
Route::post('/savefieldevaluation', [FieldEvaluationController::class, 'save'])->name('fieldevaluation.save')->middleware($listOfmiddleware);
Route::any('/fieldevaluation/unsentdocuments', [FieldEvaluationController::class, 'getUnsentDocuments'])->name('fieldevaluation.unsentdocuments')->middleware($listOfmiddleware);
Route::any('/fieldevaluation/reloadunsentdocuments', [FieldEvaluationController::class, 'reloadUnsentDocuments'])->name('fieldevaluation.reloadunsentdocuments')->middleware($listOfmiddleware);
Route::any('/fieldevaluation/exportData', [FieldEvaluationController::class, 'exportData'])->name('fieldevaluation.exportData')->middleware($listOfmiddleware);
Route::get('/fieldevaluation/delete', [FieldEvaluationController::class, 'delete'])->name('fieldevaluation.delete')->middleware($listOfmiddleware);

Route::get('/officeevaluation', [OfficeEvaluationController::class, 'list'])->name('officeevaluation.list')->middleware($listOfmiddleware);
Route::get('/editofficeevaluation', [OfficeEvaluationController::class, 'edit'])->name('officeevaluation.edit')->middleware($listOfmiddleware);
Route::post('/saveofficeevaluation', [OfficeEvaluationController::class, 'save'])->name('officeevaluation.save')->middleware($listOfmiddleware);
Route::any('/officeevaluation/notesform', [OfficeEvaluationController::class, 'getNotesForm'])->name('officeevaluation.getnotesform')->middleware($listOfmiddleware);
Route::any('/officeevaluation/savenotesform', [OfficeEvaluationController::class, 'saveNotesForm'])->name('officeevaluation.savenotesform')->middleware($listOfmiddleware);
Route::any('/officeevaluation/approvalform', [OfficeEvaluationController::class, 'getApprovalForm'])->name('officeevaluation.approvalform')->middleware($listOfmiddleware);
Route::any('/officeevaluation/saveapprovalform', [OfficeEvaluationController::class, 'saveApprovalForm'])->name('officeevaluation.saveapprovalform')->middleware($listOfmiddleware);
Route::any('/officeevaluation/exportData', [OfficeEvaluationController::class, 'exportData'])->name('officeevaluation.exportData')->middleware($listOfmiddleware);
Route::get('/officeevaluation/delete', [OfficeEvaluationController::class, 'delete'])->name('officeevaluation.delete')->middleware($listOfmiddleware);
Route::get('/officeevaluation/processofficeeval', [OfficeEvaluationController::class, 'processOfficeEvals'])->name('officeevaluation.processofficeeval')->middleware($listOfmiddleware);
Route::any('/editselfevaluation', [SelfEvaluationController::class, 'edit'])->name('selfevaluation.edit')->middleware($listOfmiddleware);
Route::post('/saveselfevaluation', [SelfEvaluationController::class, 'save'])->name('selfevaluation.save')->middleware($listOfmiddleware);

// CCR Calendar
Route::get('/ccr-calendar/fetch-schedule-details-of-ccr', [CCRCalendarController::class, 'fetchScheduleDetailsOfCCR'])->name('ccrCalendar.fetchScheduleDetailsOfCCR')->middleware($listOfmiddleware);
Route::get('/ccr/calendar', [CCRCalendarController::class, 'index'])->name('ccrCalendar.calendar')->middleware($listOfmiddleware);
Route::get('/ccr/get-week-events', [CCRCalendarController::class, 'getWeekEvents'])->name('ccrCalendar.getWeekEvents')->middleware($listOfmiddleware);
Route::get('/ccr/get-event-details', [CCRCalendarController::class, 'getEventDetails'])->name('ccrCalendar.getEventDetails')->middleware($listOfmiddleware);
Route::get('/ccr/add-events', [CCRCalendarController::class, 'addEvent'])->name('ccrCalendar.addEvent')->middleware($listOfmiddleware);
Route::post('/ccr/save-event', [CCRCalendarController::class, 'saveEvent'])->name('ccrCalendar.saveEvent')->middleware($listOfmiddleware);
Route::get('/ccr-availability/get', [CCRAvailabilityController::class, 'get'])->name('ccrAvailability.get')->middleware($listOfmiddleware);
Route::post('/ccr-availability/save', [CCRAvailabilityController::class, 'save'])->name('ccrAvailability.save')->middleware($listOfmiddleware);
Route::put('/ccr-availability/block/{id}', [CCRAvailabilityController::class, 'updateBlock'])->name('ccrAvailability.updateBlock')->whereNumber('id')->middleware($listOfmiddleware);
Route::delete('/ccr-availability/block/{id}', [CCRAvailabilityController::class, 'deleteBlock'])->name('ccrAvailability.deleteBlock')->whereNumber('id')->middleware($listOfmiddleware);

Route::any('/fieldevaluation/unsentdocuments', [FieldEvaluationController::class, 'getUnsentDocuments'])->name('fieldevaluation.unsentdocuments')->middleware($listOfmiddleware);
Route::any('/fieldevaluation/reloadunsentdocuments', [FieldEvaluationController::class, 'reloadUnsentDocuments'])->name('fieldevaluation.reloadunsentdocuments')->middleware($listOfmiddleware);

Route::any('/officeevaluation/documents', [OfficeEvaluationController::class, 'getOfficeEvaluationDocuments'])->name('officeevaluation.unsentdocuments')->middleware($listOfmiddleware);
Route::any('/officeevaluation/reloaddocuments', [OfficeEvaluationController::class, 'reloadOfficeEvaluationDocuments'])->name('officeevaluation.reloadunsentdocuments')->middleware($listOfmiddleware);
Route::any('/officeevaluation/downloadpdf', [OfficeEvaluationController::class, 'downloadpdf'])->name('officeevaluation.downloadpdf')->middleware($listOfmiddleware);


Route::get('/payerrates', [PayerRatesController::class, 'list'])->name('payerrates.list')->middleware($listOfmiddleware);
Route::get('/addpayerrates', [PayerRatesController::class, 'add'])->name('payerrates.add')->middleware($listOfmiddleware);
Route::get('/deletepayerrates', [PayerRatesController::class, 'delete'])->name('payerrates.delete')->middleware($listOfmiddleware);
Route::post('/savepayerrates', [PayerRatesController::class, 'save'])->name('payerrates.save')->middleware($listOfmiddleware);
Route::get('/approvepayerrates', [PayerRatesController::class, 'approve'])->name('payerrates.approve')->middleware($listOfmiddleware);
Route::get('/getPayerId', [PayerRatesController::class, 'getPayerId'])->name('payerrates.getPayerId')->middleware($listOfmiddleware);
Route::get('/denypayerrates', [PayerRatesController::class, 'denyPayerRates'])->name('payerrates.deny')->middleware($listOfmiddleware);
Route::get('/editpayerrates/{id}', [PayerRatesController::class, 'edit'])->name('payerrates.edit');
Route::put('/updatepayerrates', [PayerRatesController::class, 'update'])->name('payerrates.update');

//GP Calculator
Route::get('/gp-calculator', [GPCalculatorController::class, 'index'])->name('gpCalculator.index')->middleware($listOfmiddleware);
Route::get('/gp-calculator/load-gp-nurse-form', [GPCalculatorController::class, 'loadGPNurseForm'])->name('gpCalculator.loadGPNurseForm')->middleware($listOfmiddleware);
Route::post('/gp-calculator/save-gp', [GPCalculatorController::class, 'saveGp'])->name('gpCalculator.saveGp')->middleware($listOfmiddleware);
Route::get('/gp-calculator/calculate-gp', [GPCalculatorController::class, 'calculateGpTable'])->name('gpCalculator.calculateGpTable')->middleware($listOfmiddleware);
Route::get('/gp-calculator/list', [GPCalculatorController::class, 'list'])->name('gpCalculator.list')->middleware($listOfmiddleware);
Route::get('/gp-calculator/load-schedule-hours', [GPCalculatorController::class, 'loadScheduleHours'])->name('gpCalculator.loadScheduleHours')->middleware($listOfmiddleware);
Route::get('/gp-calculator/clear', [GPCalculatorController::class, 'clear'])->name('gpCalculator.clear')->middleware($listOfmiddleware);
Route::get('/gp-calculator/getService', [GPCalculatorController::class, 'getService'])->name('gpCalculator.getService')->middleware($listOfmiddleware);
Route::get('/gp-calculator/loadPayerDropdown', [GPCalculatorController::class, 'loadPayerDropdown'])->name('gpCalculator.loadPayerDropdown')->middleware($listOfmiddleware);
Route::get('/gp-calculator/getCandidateName', [GPCalculatorController::class, 'getCandidateName'])->name('gp-calculator.getCandidateName')->middleware($listOfmiddleware);

//GP V2
Route::get('/v2/gp-calculator/get', [\App\Http\Controllers\V2\GpCalculatorController::class, 'get'])->name('gpCalculatorV2.get')->middleware($listOfmiddleware);
Route::get('/v2/gp-calculator/list', [\App\Http\Controllers\V2\GpCalculatorController::class, 'list'])->name('gpCalculatorV2.list')->middleware($listOfmiddleware);
Route::post('/v2/gp-calculator/save-gp', [\App\Http\Controllers\V2\GpCalculatorController::class, 'saveGp'])->name('gpCalculatorV2.saveGp')->middleware($listOfmiddleware);
Route::get('/v2/gp-calculator', [\App\Http\Controllers\V2\GpCalculatorController::class, 'index'])->name('gpCalculatorV2.index')->middleware($listOfmiddleware);
Route::post('/v2/gp-calculator/calculate', [\App\Http\Controllers\V2\GpCalculatorController::class, 'calculate'])->name('gpCalculatorV2.calculateGp')->middleware($listOfmiddleware);
Route::get('/v2/gp-calculator/whole-home', [\App\Http\Controllers\V2\GpCalculatorController::class, 'requestWholeHome'])->name('gp-calculator.request-whole-home')->middleware($listOfmiddleware);
Route::get('v2/gp-calculator/getCandidateName', [\App\Http\Controllers\V2\GpCalculatorController::class, 'getCandidateName'])->name('gp-calculatorV2.getCandidateName')->middleware($listOfmiddleware);
Route::get('/v2/gp-calculator/loadPayerDropdown', [\App\Http\Controllers\V2\GpCalculatorController::class, 'loadPayerDropdown'])->name('gp-calculatorV2.loadPayerDropdown')->middleware($listOfmiddleware);
//Todo
Route::any('/dashboardtasklist', [TodoController::class, 'getToDoTaskList'])->name('dashboard.getToDoTaskList')->middleware($listOfmiddleware);
Route::any('/reload-dashboardtodo', [TodoController::class, 'reloadCountToDoTaskList'])->name('dashboard.reloadCounttodotasklist')->middleware($listOfmiddleware);
Route::any('/makrascompleted', [TodoController::class, 'makrAsCompleted'])->name('makrascompleted')->middleware($listOfmiddleware);

//Mobile App
Route::any('/getExternalUserAutoCompleteList', [MobileController::class, 'getExternalUserAutoCompleteList'])->name('mobile.getExternalUserAutoCompleteList')->middleware($listOfmiddleware);
Route::any('/saveMobileUser', [MobileController::class, 'saveMobileUser'])->name('saveMobileUser')->middleware($listOfmiddleware);
Route::any('/removeMobileUser', [MobileController::class, 'removeMobileUser'])->name('removeMobileUser')->middleware($listOfmiddleware);
Route::any('/getMobileUser', [MobileController::class, 'getMobileUser'])->name('getMobileUser')->middleware($listOfmiddleware);


//Expriing Renewables
Route::any('/renewables/getexpiringdata', [RenewablesController::class, 'getExpiringData'])->name('renewables.expiringdata')->middleware($listOfmiddleware);
Route::any('/renewables/reloadexpiringdata', [RenewablesController::class, 'reloadExpiringData'])->name('renewables.reloadexpiringdata')->middleware($listOfmiddleware);
Route::any('/renewables/outcomeform', [RenewablesController::class, 'getOutcomeForm'])->name('renewables.getoutcomeform')->middleware($listOfmiddleware);
Route::any('/renewables/saveoutcomeform', [RenewablesController::class, 'saveOutcomeForm'])->name('renewables.saveoutcomeform')->middleware($listOfmiddleware);
Route::any('/renewables/completerenewableform', [RenewablesController::class, 'getCompleteRenewableForm'])->name('renewables.getcompleterenewableform')->middleware($listOfmiddleware);
Route::any('/renewables/savecompleterenewableform', [RenewablesController::class, 'saveCompleteRenewableForm'])->name('renewables.savecompleterenewableform')->middleware($listOfmiddleware);
Route::any('/renewables/showrenewablenotesform', [RenewablesController::class, 'getRenewableNotesForm'])->name('renewables.getrenewablenotesform')->middleware($listOfmiddleware);
Route::any('/renewables/saverenewablenotesform', [RenewablesController::class, 'saveRenewableNotesForm'])->name('renewables.saverenewablenotesform')->middleware($listOfmiddleware);
Route::get('/renewables/refreshByProviderId', [RenewablesController::class, 'refreshByProviderId'])->name('renewables.refresh.by.providerId')->middleware($listOfmiddleware);


Route::any('/renewables/show-all', [RenewablesController::class, 'showAll'])->name('renewables.showAll')->middleware($listOfmiddleware);
Route::any('/renewables/export-renewables', [RenewablesController::class, 'exportRenewables'])->name('renewables.exportRenewables')->middleware($listOfmiddleware);

// Renewable Report

Route::any('/report/renewables/missing-renewables', [RenewableReportController::class, 'missingRenewable'])->name('renewablesReport.missingRenewable')->middleware($listOfmiddleware);
Route::any('/reports/renewables/getrenewablespopup', [RenewableReportController::class, 'getrenewablespopup'])
->name('renewablesReport.getrenewablespopup')
->middleware($listOfmiddleware);
//Renewables Rule
Route::get('/renewablesrule', [RenewablesRuleController::class, 'index'])->name('renewablesrule')->middleware($listOfmiddleware);
Route::get('/getrenewablesruleajax', [RenewablesRuleController::class, 'getRenewablesRule'])->name('getrenewablesruleajax');
//clinical well child
Route::any('/saveClinicalWellChild', [ClientController::class, 'saveClinicalWellChild'])->name('saveClinicalWellChild')->middleware($listOfmiddleware);
Route::any('/getClinicalWellChild', [ClientController::class, 'getClinicalWellChild'])->name('getClinicalWellChild')->middleware($listOfmiddleware);
Route::any('/exportWellChildVisitData', [ClientController::class, 'exportWellChildVisitData'])->name('exportWellChildVisitData')->middleware($listOfmiddleware);
Route::any('/deleteClinicalWellChild', [ClientController::class, 'deleteClinicalWellChild'])->name('deleteClinicalWellChild')->middleware($listOfmiddleware);
//Renewables Rule
Route::get('/renewablesrule', [RenewablesRuleController::class, 'index'])->name('renewablesrule')->middleware($listOfmiddleware);
Route::get('/getrenewablesruleajax', [RenewablesRuleController::class, 'getRenewablesRule'])->name('getrenewablesruleajax');
Route::get('/renewablesrule/add', [RenewablesRuleController::class, 'add'])->name('addrenewablesrule');
Route::post('/saverenewablesrule', [RenewablesRuleController::class, 'saveRenewablesRule'])->name('saverenewablesrule');
Route::get('/renewablesrule/edit/{id}', [RenewablesRuleController::class, 'edit'])->name('editrenewablesrule');
Route::post('/updaterenewablesrule', [RenewablesRuleController::class, 'updateRenewablesRule'])->name('updaterenewablesrule');
Route::get('/renewablesrule/delete/{id}', [RenewablesRuleController::class, 'destroy'])->name('destroyrenewablesrule');
Route::get('/checkrenewablesrule', [RenewablesRuleController::class, 'checkRenewableRule'])->name('checkrenewablesrule');


//Nursys Licence Mismatch
Route::any('/nursys/getmismatchdata', [RenewablesController::class, 'getNursysMismatchData'])->name('nursys.nursysmismatchdata')->middleware($listOfmiddleware);
Route::any('/nursys/reloadmismatchdata', [RenewablesController::class, 'reloadNursysMismatchData'])->name('nursys.reloadnursysmismatchdata')->middleware($listOfmiddleware);
Route::any('/nursys/getnursysform', [RenewablesController::class, 'getNursysExceptionForm'])->name('nursys.nursysexceptionform')->middleware($listOfmiddleware);
Route::any('/nursys/savenursysform', [RenewablesController::class, 'saveNursysExceptionForm'])->name('nursys.savenursysexceptionform')->middleware($listOfmiddleware);
Route::any('/nursys/showall', [RenewablesController::class, 'nursysShowAll'])->name('nursys.showAll')->middleware($listOfmiddleware);
Route::any('/nursys/exports', [RenewablesController::class, 'exportNursys'])->name('nursys.exports')->middleware($listOfmiddleware);
//Client
Route::get('/client/merge-duplicate', [ClientController::class, 'mergeClient'])->name('client.mergeClient')->middleware($listOfmiddleware);
Route::get('/client/search-duplicate-client', [ClientController::class, 'searchingDuplicatedClient'])->name('client.searchingDuplicatedClient')->middleware($listOfmiddleware);
Route::get('/client/merging-client', [ClientController::class, 'mergingClients'])->name('client.mergingClients')->middleware($listOfmiddleware);
Route::POST('/client/merging-all-client', [ClientController::class, 'mergingAllDuplicateClients'])->name('client.mergingAllDuplicateClients')->middleware($listOfmiddleware);

//Advocacy Status change Request
Route::any('/advocacy/statuschangerequest', [AdvocacyController::class, 'getAdvocacyStatusChangeRequest'])->name('advocacy.statuschangerequest')->middleware($listOfmiddleware);
Route::any('/advocacy/reloadstatuschangerequest', [AdvocacyController::class, 'reloadAdvocacyStatusChangeRequest'])->name('advocacy.reloadstatuschangerequest')->middleware($listOfmiddleware);
Route::any('/advocacy/setadvocacyspecialist', [AdvocacyController::class, 'setAdvocacySpecialist'])->name('advocacy.setadvocacyspecialist')->middleware($listOfmiddleware);
Route::any('/advocacy/showstatuschangerequestform', [AdvocacyController::class, 'showStatusChangeRequestForm'])->name('advocacy.showStatusChangeRequestNextStepForm')->middleware($listOfmiddleware);
Route::any('/advocacy/saveoutcome', [AdvocacyController::class, 'saveOutcomeForm'])->name('advocacy.saveoutcomeform')->middleware($listOfmiddleware);


//Service Request
Route::any('/statuschangerequests/reload-count-for-status-change-request', [StatusChangeRequestController::class, 'reloadCountStatusChangeRequests'])->name('statuschangerequests.reloadCountForSupplyRequests')->middleware($listOfmiddleware);
Route::get('/statuschangerequests/get-status-change-dashboard', [StatusChangeRequestController::class, 'getStatusChangeRequestsDashboard'])->name('statuschangerequests.getSupplyRequestsDashboard')->middleware($listOfmiddleware);
Route::get('/clinician/status-change-request/show-all', [StatusChangeRequestController::class, 'showAll'])->name('statusChangeRequest.showAll')->middleware($listOfmiddleware);
Route::get('/statuschangerequests/show-next-step-form', [StatusChangeRequestController::class, 'showStatusChangeRequestNextStepForm'])->name('statuschangerequests.showStatusChangeRequestNextStepForm')->middleware($listOfmiddleware);
Route::any('/statuschangerequests/saveoutcomeform', [StatusChangeRequestController::class, 'saveOutcomeForm'])->name('statuschangerequests.saveoutcomeform')->middleware($listOfmiddleware);
Route::any('/statuschangerequests/checkrequeststatus', [StatusChangeRequestController::class, 'checkRequestStatus'])->name('statuschangerequests.checkrequeststatus')->middleware($listOfmiddleware);
Route::any('/statuschangerequests/assignrequestoc', [StatusChangeRequestController::class, 'assignRequestOC'])->name('statuschangerequest.assignrequestoc')->middleware($listOfmiddleware);

Route::any('/misc/log', [MiscController::class, 'log'])->name('misc.log')->middleware($listOfmiddleware);


//Advocacy Status change Request
Route::any('/document', [RenewablesDocumentController::class, 'getDocumentUploadPage'])->name('renewabledocument.upload');
Route::any('/document/save', [RenewablesDocumentController::class, 'saveDocumentUpload'])->name('renewabledocument.save');
Route::any('/document/thankyou', [RenewablesDocumentController::class, 'getDocumentStatus'])->name('renewabledocument.thankyou');
Route::any('/document/view', [ClinicianController::class, 'viewDocument'])->name('document.view')->middleware($listOfmiddleware);;

//Paycom Kantime Exception bucket
Route::any('/paycomkantime/exceptionbucket', [PaycomKantimeController::class, 'getPaycomKantimeExceptionData'])->name('paycomkantime.exception')->middleware($listOfmiddleware);
Route::any('/paycomkantime/reloadexceptionbucket', [PaycomKantimeController::class, 'reloadPaycomKantimeExceptionData'])->name('paycomkantime.reloadexception')->middleware($listOfmiddleware);
Route::any('/paycomkantime/completedpaycomediscrepancy', [PaycomKantimeController::class, 'completedPaycomeDiscrepancy'])->name('paycomkantime.completedpaycomediscrepancy')->middleware($listOfmiddleware);

//Paycom Kantime Exception bucket
Route::any('/jobvite/requisitiondata', [JobviteRequisition::class, 'getJobivteRequisitionData'])->name('jobvite.requisitiondata')->middleware($listOfmiddleware);
Route::any('/jobvite/reloadrequisitiondata', [JobviteRequisition::class, 'reloadJobviteRequisitionData'])->name('jobvite.reloadrequisitiondata')->middleware($listOfmiddleware);
Route::any('/jobvite/completecloserequistion', [JobviteRequisition::class, 'completeCloseRequisition'])->name('jobvite.completecloserequisition')->middleware($listOfmiddleware);
Route::any('/jobvite/completeopenrequisition', [JobviteRequisition::class, 'completeOpenRequisition'])->name('jobvite.completeopenrequisition')->middleware($listOfmiddleware);

// Azure Service Controller
Route::any('/azureservice/form', [AzureServiceController::class, 'azureServiceForm'])
->name('azureservice.form')->middleware($listOfmiddleware);
Route::any('/azureservice/uploadfile', [AzureServiceController::class, 'azureServiceUploadfile'])
->name('azureservice.uploadfile')->middleware($listOfmiddleware);
Route::any('/azureservice/createcontainer', [AzureServiceController::class, 'createContainer'])
->name('azureservice.createcontainer')->middleware($listOfmiddleware);
Route::any('/azureservice/openblob', [AzureServiceController::class, 'openFileFromBlob'])
->name('azureservice.blob')->middleware($listOfmiddleware);


//Supervisory Visits
Route::get('/supervisory-visits/show-all', [SupervisoryVisitController::class, 'showAll'])
->name('supervisoryVisits.showAll')->middleware($listOfmiddleware);
Route::any('/supervisoryVisit', [SupervisoryVisitController::class, 'reloadSupervisoryVisit'])
->name('supervisoryVisit')->middleware($listOfmiddleware);
Route::any('/supervisoryVisit/bucket', [SupervisoryVisitController::class, 'supervisoryVisitBucket'])
->name('supervisoryVisit.bucket')->middleware($listOfmiddleware);
Route::any('/supervisoryVisit/assignedSuperCcm', [SupervisoryVisitController::class, 'assignedSuperCcm'])
->name('supervisoryVisit.assignedSuperCcm')->middleware($listOfmiddleware);
Route::get('/supervisory-visits/openForm', [SupervisoryVisitController::class, 'openForm'])
->name('supervisoryVisits.openForm')->middleware($listOfmiddleware);
Route::post('/supervisory-visits/saveForm', [SupervisoryVisitController::class, 'saveForm'])
->name('supervisoryVisits.saveForm')->middleware($listOfmiddleware);
Route::get('/supervisoryVisits/export-supervisory-visits-data',
[SupervisoryVisitController::class, 'exportSupervisoryVisitsData'])
->name('exportSupervisoryVisitsData')->middleware($listOfmiddleware);

Route::get('/supervisoryVisits/complete-supervisory-visits-data',
[SupervisoryVisitController::class, 'completeSupervisoryVisit'])
->name('supervisoryVisits.completeSupervisoryVisit')->middleware($listOfmiddleware);

Route::post('/supervisoryVisits/delete', [SupervisoryVisitController::class, 'delete'])
->name('supervisoryVisits.delete')->middleware($listOfmiddleware);

//School Setup
Route::any('/ccc/reload-ccc-client-school-setup', [SchoolSetupController::class, 'reloadClientSchoolSetup']
)->name('ccc.reloadClientSchoolSetup')->middleware($listOfmiddleware);
Route::any('/ccc/get-ccc-client-school-setup', [SchoolSetupController::class, 'getClientSchoolSetup']
)->name('ccc.getClientSchoolSetup')->middleware($listOfmiddleware);
Route::any('/school-setups', [SchoolSetupController::class, 'showAll']
)->name('schoolsetups.showall')->middleware($listOfmiddleware);
Route::any('/school-setups/export', [SchoolSetupController::class, 'export']
)->name('schoolsetups.export')->middleware($listOfmiddleware);
Route::any('/school-setup/form', [SchoolSetupController::class, 'schoolSetupForm']
)->name('schoolSetup.form')->middleware($listOfmiddleware);
Route::any('/school-setup/saveForm', [SchoolSetupController::class, 'saveForm']
)->name('schoolSetup.saveForm')->middleware($listOfmiddleware);

Route::any('/dashboard/reload-oepn-school-setup-request', [SchoolSetupController::class, 'reloadOpenSchoolSetupRequest']
)->name('dashboard.reloadOpenSchoolSetupRequest')->middleware($listOfmiddleware);

Route::any('/dashboard/get-school-setup-request', [SchoolSetupController::class, 'getSchoolSetUpRequest']
)->name('dashboard.getSchoolSetUpsRequest')->middleware($listOfmiddleware);

Route::any('/schoolSetup/get-no-need-school-setup', [SchoolSetupController::class, 'noNeedSchoolSetupForm']
)->name('schoolSetup.noNeedSchoolSetupForm')->middleware($listOfmiddleware);

Route::any('/schoolSetup/save-no-need-school-setup', [SchoolSetupController::class, 'saveNoNeedSchoolSetupForm']
)->name('schoolSetup.saveNoNeedSchoolSetupForm')->middleware($listOfmiddleware);

Route::any('/schoolSetup/get-school-setup-outcome', [SchoolSetupController::class, 'showOpenSchoolSetUpNextStepForm']
)->name('schoolSetup.showOpenSchoolSetUpNextStepForm')->middleware($listOfmiddleware);

Route::any('/schoolSetup/save-school-setup-outcome', [SchoolSetupController::class, 'saveSchoolSetupOutcomeInfo'])
->name('schoolSetup.saveOutcomeInfo')->middleware($listOfmiddleware);

Route::get('/schoolSetup/history', [NotesController::class, 'schoolSetupHistory'])
->name('schoolSetup.schoolSetupHistory')->middleware($listOfmiddleware);

Route::any('/schoolSetup/assignedCcc', [SchoolSetupController::class, 'assignedCcc']
)->name('schoolSetup.assignedCcc')->middleware($listOfmiddleware);

Route::any('/schoolSetup/get-ccc-acknowledged', [SchoolSetupController::class, 'schoolSetupCcAcknowledgedForm']
)->name('schoolSetup.schoolSetupCcAcknowledgedForm')->middleware($listOfmiddleware);

Route::any('/schoolSetup/save-ccc-acknowledged', [SchoolSetupController::class, 'saveSchoolSetupCcAcknowledgedForm']
)->name('schoolSetup.saveSchoolSetupCcAcknowledgedForm')->middleware($listOfmiddleware);


Route::any('/getClinicianByPatientId', [Dashboard::class, 'getClinicianByPatientId'])
->name('getClinicianByPatientId')->middleware($listOfmiddleware);

//Billing
Route::any('/billing/reload-unbilled-schedules', [BillingController::class, 'reloadUnbilledSchedulesData']
)->name('dashboard.reloadUnbilledSchedulesData')->middleware($listOfmiddleware);

Route::any('/billing/get-unbilled-schedules', [BillingController::class, 'getUnbilledSchedulesData']
)->name('dashboard.getUnbilledSchedulesData')->middleware($listOfmiddleware);
Route::any('/billing/show-outcome', [BillingController::class, 'showOutcome']
)->name('billing.showOutcome')->middleware($listOfmiddleware);
Route::any('/billing/save-billing-outcome', [BillingController::class, 'saveBillingOutcome']
)->name('billing.saveBillingOutcome')->middleware($listOfmiddleware);
Route::any('/billing/billing-resolve', [BillingController::class, 'billingResolve']
)->name('billing.billingResolve')->middleware($listOfmiddleware);
Route::any('/billing/billing-exclude', [BillingController::class, 'billingExclude']
)->name('billing.billingExclude')->middleware($listOfmiddleware);
Route::any('/billing/export-billing', [BillingController::class, 'exportBilling']
)->name('billing.exportBilling')->middleware($listOfmiddleware);

Route::any('/billing/get-billing-invoices', [BillingController::class, 'getInvoicesData']
)->name('dashboard.getInvoicesData')->middleware($listOfmiddleware);

Route::any('/billing/invoice-show-outcome', [BillingController::class, 'showInvoiceOutcome']
)->name('billing.showInvoiceOutcome')->middleware($listOfmiddleware);
Route::any('/billing/save-billing-invoice-outcome', [BillingController::class, 'saveBillingInvoiceOutcome']
)->name('billing.saveBillingInvoiceOutcome')->middleware($listOfmiddleware);

Route::get('/invoice/details', [BillingController::class, 'getInvoiceDetails']
)->name('billing.getInvoiceDetails')->middleware($listOfmiddleware);

Route::get('/billing/details', [BillingController::class, 'getBillingDetails']
)->name('billing.getBillingDetails')->middleware($listOfmiddleware);

Route::get('/invoice/history', [NotesController::class, 'billingInvoiceHistory'])
->name('billing.billingInvoiceHistory')->middleware($listOfmiddleware);

Route::get('/billing/invoice-show-all', [BillingController::class, 'invoiceIndex'])
->name('billing.invoiceShowAll')->middleware($listOfmiddleware);
Route::any('/billing/invoice-show-all-ajax', [BillingController::class, 'getAllInvoice'])
->name('billing.invoiceShowAllAjax')->middleware($listOfmiddleware);
Route::any('/billing/invoice-export', [BillingController::class, 'exportInvoiceData'])
->name('billing.invoiceExport')->middleware($listOfmiddleware);
Route::any('/billing/export-invoice-bucket', [BillingController::class, 'exportInvoiceBucket'])
->name('billing.exportInvoiceBucket')->middleware($listOfmiddleware);

Route::any('/billing/invoice-show-adjustments-outcome', [BillingController::class, 'showAdjustmentsInvoiceOutcome']
)->name('billing.showAdjustmentsInvoiceOutcome')->middleware($listOfmiddleware);
Route::any('/billing/save-adjustments-invoice-outcome', [BillingController::class, 'saveAdjustmentsInvoiceOutcome']
)->name('billing.saveAdjustmentsInvoiceOutcome')->middleware($listOfmiddleware);


Route::any('/billing/invoice-show-adjustments-reassign', [BillingController::class, 'showAdjustmentsInvoiceReassign']
)->name('billing.showAdjustmentsInvoiceReassign')->middleware($listOfmiddleware);
Route::any('/billing/save-adjustments-invoice-reassign', [BillingController::class, 'saveAdjustmentsInvoiceReassign']
)->name('billing.saveAdjustmentsInvoiceReassign')->middleware($listOfmiddleware);


//Schedule Vs Auth
Route::any('/ccc/reload-client-care-coordinator-schedules-auth',
[ClientCareCoordinatorController::class, 'reloadClientCareCoordinatorSchedulesBuiltAuth'])
->name('ccc.reloadClientCareCoordinatorSchedulesBuiltAuth')->middleware($listOfmiddleware);

Route::any('/ccc/get-client-care-coordinator-schedules-auth',
[ClientCareCoordinatorController::class, 'getClientCareCoordinatorSchedulesBuiltAuth'])
->name('ccc.getClientCareCoordinatorSchedulesBuiltAuth')->middleware($listOfmiddleware);

Route::get('/schedulecorrected', [ClientCareCoordinatorController::class, 'getScheduleCorrected'])->name('dashboard.getschedulecorrected')->middleware($listOfmiddleware);
Route::post('/schedulecorrected', [ClientCareCoordinatorController::class, 'saveScheduleCorrected'])->name('dashboard.saveScheduleCorrected')->middleware($listOfmiddleware);

Route::any('/ccc/get-zero-rates',
[ClientCareCoordinatorController::class, 'getZeroRatesData'])
->name('ccc.getZeroRatesData')->middleware($listOfmiddleware);
Route::any('/ccc/reload-zero-rates',
[ClientCareCoordinatorController::class, 'reloadZeroRatesData'])
->name('ccc.reloadZeroRatesData')->middleware($listOfmiddleware);

Route::any('/ccc/getcaseload',
[ClientCareCoordinatorController::class, 'getCaseLoadData'])
->name('ccc.getcaseloaddata')->middleware($listOfmiddleware);
Route::any('/ccc/reloadcaseload',
[ClientCareCoordinatorController::class, 'reloadCaseLoadData'])
->name('ccc.reloadcaseloaddata')->middleware($listOfmiddleware);


Route::any('/nthalerts/getleadsandinquiries',
[NTHAlertsController::class, 'getLeadsAndInquiries'])
->name('nthalerts.getleadsandinquiries')->middleware($listOfmiddleware);
Route::any('/ccc/getleads',
[NTHAlertsController::class, 'reloadLeadsAndInquiries'])
->name('nthalerts.reloadleadsandinquiries')->middleware($listOfmiddleware);
Route::any('/nthalerts/saverecruiter',[NTHAlertsController::class, 'recruiterAssignment'])
->name('nthalerts.recruiterassignment')->middleware($listOfmiddleware);

Route::any('/ccc/getactivecoverage',
[ClientCareCoordinatorController::class, 'getActiveCoverageData'])
->name('ccc.getactivecoveragedata')->middleware($listOfmiddleware);
Route::any('/ccc/reloadactivecoverage',
[ClientCareCoordinatorController::class, 'reloadActiveCoverageData'])
->name('ccc.reloadactivecoveragedata')->middleware($listOfmiddleware);

Route::any('/activecoverage/outcomeform',
[ClientCareCoordinatorController::class, 'getActiveCoverageOutcomeForm'])
->name('activecoverage.outcomeform')->middleware($listOfmiddleware);

Route::any('/activecoverage/saveoutcomeform',
[ClientCareCoordinatorController::class, 'saveActiveCoverageOutcomeForm'])
->name('activecoverage.saveoutcomeform')->middleware($listOfmiddleware);

//All files upload routes should be listed in this group so file.upload middleware can check the type of files
//image/jpeg,image/png,application/pdf these are the mime type not the extension[extension might be compromised] that we need to define
Route::group(['middleware' => ['file.upload:image/jpeg,image/png,application/pdf']], function () use ($listOfmiddleware) {
    Route::any('/ipea/attachment/save', [ManageIPEAAttachementController::class, 'save'])->name('manageipeaattachment.save')->middleware($listOfmiddleware);
    Route::any('/ipea/emailtemplate/uploadfiles', [IPEAEmailTemplate::class, 'uploadfiles'])->name('emailtemplate.uploadfiles')->middleware($listOfmiddleware);
    Route::any('/profile/uploadfiles', [Dashboard::class, 'uploadfiles'])->name('profile.uploadfiles')->middleware($listOfmiddleware);
    Route::post('/profilesave', [Dashboard::class, 'profilesave'])->name('users.profilesave')->middleware($listOfmiddleware);
    Route::any('/message/attachment/save', [MessageController::class, 'save'])->name('managemessageattachment.save')->middleware($listOfmiddleware);
});


//Manage Kantime Facility
Route::get('/kantimefacility', [App\Http\Controllers\KantimeFacilityController::class, 'getKantimeFacility'])->name('view.kantimefacility')->middleware($listOfmiddleware);
Route::get('/kantimefacilityajax', [App\Http\Controllers\KantimeFacilityController::class, 'ajaxKantimeFacility'])->name('ajax.kantimefacility')->middleware($listOfmiddleware);
Route::get('/kantimefacility/add', [App\Http\Controllers\KantimeFacilityController::class, 'addKantimeFacility'])->name('add.kantimefacility')->middleware($listOfmiddleware);
Route::post('/savekantimefacility', [App\Http\Controllers\KantimeFacilityController::class, 'saveKantimeFacility'])->name('save.kantimefacility')->middleware($listOfmiddleware);



//National Client Care Report
Route::any('/nccreports/missedvisit', [NationalClientCareReportController::class, 'missedVisit'])
->name('missedVisit')->middleware($listOfmiddleware);
Route::any('/nccreports/coveragebooked', [NationalClientCareReportController::class, 'coverageBooked'])
->name('coverageBooked')->middleware($listOfmiddleware);
Route::any('/nccreports/nthweeklycalls', [NationalClientCareReportController::class, 'nthWeeklyCalls'])
->name('nthWeeklyCalls')->middleware($listOfmiddleware);
Route::any('/nccreports/monthlycalls', [NationalClientCareReportController::class, 'monthlycalls'])
->name('monthlycalls')->middleware($listOfmiddleware);
Route::any('/nccreports/getnccpopup', [NationalClientCareReportController::class, 'getnccpopup'])
->name('getnccpopup')->middleware($listOfmiddleware);

//this is only for the CSV type
Route::group(['middleware' => ['file.upload:text/csv#csv,text/plain#csv']], function () use ($listOfmiddleware) {
    Route::post('/map/importdata', [MapAbstractController::class, 'importdata'])->name('map.importdata')->middleware($listOfmiddleware);
    Route::post('/candidates/processimport', [CandidatesController::class, 'processCandidateImport'])->name('candidates.processimport')->middleware($listOfmiddleware);

});

Route::get('/clinician/customfields/{clinicianId}', [CustomFieldController::class, 'clinician'])
    ->name('custom-fields.clinician')->middleware($listOfmiddleware);

//Coverage whiteboard
Route::any('/nccreports/whiteboard', [CoverageWhiteboardController::class, 'coverageWhiteBoard'])
->name('coverageWhiteBoard')->middleware($listOfmiddleware);
Route::any('/nccreports/getWhiteboardPopup', [CoverageWhiteboardController::class, 'getWhiteboardPopup'])
->name('nccreports.getWhiteboardPopup')->middleware($listOfmiddleware);

//Manage Text Template Fields
Route::get('/manageTextTemplate', [TextTemplateController::class, 'index'])
->name('manageTextTemplate')->middleware($listOfmiddleware);
Route::get('/getTextTemplatelistajax', [TextTemplateController::class, 'textTemplatelist'])
->name('getTextTemplatelistajax')->middleware($listOfmiddleware);
Route::get('/addTextTemplate', [TextTemplateController::class, 'addTextTemplate'])
->name('addTextTemplate')->middleware($listOfmiddleware);
Route::get('/getTextTemplateCategory/{departmentName}', [TextTemplateController::class,
'getTextTemplateCategory'])
->name('getTextTemplateCategory')->middleware($listOfmiddleware);
Route::post('/saveTextTemplate', [TextTemplateController::class,
'saveTextTemplate'])
->name('saveTextTemplate')->middleware($listOfmiddleware);
Route::get('/editTextTemplate', [TextTemplateController::class,
'editTextTemplate'])
->name('editTextTemplate')->middleware($listOfmiddleware);
Route::post('/deleteTextTemplate', [TextTemplateController::class,
'deleteTextTemplate'])
->name('deleteTextTemplate')->middleware($listOfmiddleware);

//Manage Custom Fields
Route::get('/manageCustomFields', [ManagingCustomFields::class, 'index'])
->name('manageCustomFields')->middleware($listOfmiddleware);
Route::get('/getCustomFieldslistajax', [ManagingCustomFields::class, 'customFieldslist'])
->name('getCustomFieldslistajax')->middleware($listOfmiddleware);
Route::get('/openCustomFieldForm', [ManagingCustomFields::class, 'openCustomFieldForm'])
->name('openCustomFieldForm')->middleware($listOfmiddleware);
Route::post('/manageCustomFields/save', [ManagingCustomFields::class, 'saveForm'])
->name('manageCustomFields.save')->middleware($listOfmiddleware);
Route::get('/manageCustomFields/delete', [ManagingCustomFields::class, 'delete'])
->name('manageCustomFields.delete')->middleware($listOfmiddleware);

//intaketab
Route::any('/getIntakeData',
[ClientController::class, 'getIntakeData'])
->name('getIntakeData')
->middleware($listOfmiddleware);
//Manage Category
Route::get('/manageTemplateCategory', [ManageCategoryController::class, 'index'])
->name('manageTemplateCategory')->middleware($listOfmiddleware);
Route::get('/getTemplateCategorylistajax', [ManageCategoryController::class, 'templateCategoryList'])
->name('getTemplateCategorylistajax')->middleware($listOfmiddleware);
Route::get('/addTemplateCategory', [ManageCategoryController::class, 'addTemplateCategory'])
->name('addTemplateCategory')->middleware($listOfmiddleware);
Route::post('/saveTemplateCategory', [ManageCategoryController::class,
'saveTemplateCategory'])
->name('saveTemplateCategory')->middleware($listOfmiddleware);
Route::get('/editTemplateCategory', [ManageCategoryController::class,
'editTemplateCategory'])
->name('editTemplateCategory')->middleware($listOfmiddleware);
Route::post('/deleteTemplateCategory', [ManageCategoryController::class,
'deleteTemplateCategory'])
->name('deleteTemplateCategory')->middleware($listOfmiddleware);

//Manage Payer Page
Route::get('/managePayer', [ManagePayerController::class, 'index'])
->name('managePayer')->middleware($listOfmiddleware);
Route::get('/getPayerlistajax', [ManagePayerController::class, 'getPayerList'])
->name('getPayerlistajax')->middleware($listOfmiddleware);
Route::get('/exportmanagepayerdata', [ManagePayerController::class, 'exportPayerData'])
->name('exportPayerData')->middleware($listOfmiddleware);
Route::get('/managepayerdata/edit/{id}', [ManagePayerController::class, 'edit'])
->name('managepayerdata.edit')->middleware($listOfmiddleware);
Route::post('/managepayerdata/update', [ManagePayerController::class, 'updatePayerData'])
->name('managepayerdata.update')->middleware($listOfmiddleware);

Route::get('/intake/calendar', [IntakeCalendarController::class, 'index'])
->name('intake.calendar')->middleware($listOfmiddleware);

Route::get('/intake/get-week-events', [IntakeCalendarController::class, 'getWeekEvents'])
->name('intake.getWeekEvents')->middleware($listOfmiddleware);
Route::get('/intake/get-calendar-search-string', [IntakeCalendarController::class, 'getCalendarSearchString'])
->name('intake.getCalendarSearchString')->middleware($listOfmiddleware);

//get referral source
Route::get('getreferralsource', [IntakeController::class, 'getReferralSource'])
->name('getreferralsource')->middleware($listOfmiddleware);
Route::any('/intakeReport/referrals', [IntakeReportController::class, 'referrals'])
->name('intakeReport.referrals')->middleware($listOfmiddleware);
Route::any('/intakeReport/nonadmit', [IntakeReportController::class, 'nonadmit'])
->name('intakeReport.nonadmit')->middleware($listOfmiddleware);
Route::any('/intakeReport/admits', [IntakeReportController::class, 'admits'])
->name('intakeReport.admits')->middleware($listOfmiddleware);
Route::any('/intakeReport/conversion', [IntakeReportController::class, 'conversion'])
->name('intakeReport.conversion')->middleware($listOfmiddleware);

Route::any('/intakeReport/getintakepopup', [IntakeReportController::class, 'getintakepopup'])
->name('getintakepopup')->middleware($listOfmiddleware);

Route::get('getreferralphysician', [IntakeController::class, 'getReferralPhysician'])
->name('getreferralphysician')->middleware($listOfmiddleware);

Route::get('/intake/get-terminal-payer', [IntakeController::class, 'getTerminalPayer'])
->name('intake.getTerminalPayer')->middleware($listOfmiddleware);
Route::get('/intake/get-respite-terminal-payer', [IntakeController::class, 'getRespiteTerminalPayer'])
->name('intake.getRespiteTerminalPayer')->middleware($listOfmiddleware);


// Recruiting Candidate Conversion Report
Route::any('/report/candidateConversion', [CandidateConversionReportController::class, 'candidateConversion'])
->name('candidateConversion')->middleware($listOfmiddleware);
Route::any('/report/getcandidateconversionreportpopup', [CandidateConversionReportController::class, 'getcandidateconversionreportpopup'])
->name('getcandidateconversionreportpopup')->middleware($listOfmiddleware);
// Recruiting IPEA Speed Report
Route::any('/report/ipeaSpeed', [IpeaSpeedReportController::class, 'ipeaSpeed'])
->name('ipeaSpeed')->middleware($listOfmiddleware);
Route::any('/report/getipeaspeedreportpopup', [IpeaSpeedReportController::class, 'getipeaspeedreportpopup'])
->name('getipeaspeedreportpopup')->middleware($listOfmiddleware);
//nth-weekly-call-show-all-page
Route::get('/nth/weekly-show-all', [WekklyCallFormController::class, 'weeklyShowAllCall'])
->name('weeklyShowAll')->middleware($listOfmiddleware);
Route::get('/nth/weekly-show-all-ajax', [WekklyCallFormController::class, 'getAllWeeklyShowAll'])
->name('weeklyShowAllAjax')->middleware($listOfmiddleware);
Route::get('/nth/weekly-show-all-export', [WekklyCallFormController::class, 'exportWeeklyShowAll'])
->name('exportWeeklyShowAll')->middleware($listOfmiddleware);

//montly-call-show-all-page
Route::get('/monthly-show-all', [MonthlyCallController::class, 'monthlyShowAllCall'])
->name('monthlyShowAll')->middleware($listOfmiddleware);
Route::get('/monthly-show-all-ajax', [MonthlyCallController::class, 'getAllMonthlyShowAll'])
->name('monthlyShowAllAjax')->middleware($listOfmiddleware);
Route::get('/monthly-show-all-export', [MonthlyCallController::class, 'exportMonthlyShowAll'])
->name('exportMonthlyShowAll')->middleware($listOfmiddleware);

//NTH landing page
Route::get('/nthlanding/redirect', [NthLandingController::class, 'nthRedirectPage'])->name('nthlanding.nthRedirectPage');
Route::get('/nthlanding/available-cases', [NthLandingController::class, 'nthAvailableCases'])->name('nthlanding.nthAvailableCases');

Route::get('/nthlanding/results', [NthLandingController::class, 'getResults'])->name('nthlanding.results');

Route::get('/nthlanding/get-cities-geo-code', [NthLandingController::class, 'getGeoCities'])->name('nthlanding.getGeoCities');
Route::get('/nthlanding/error-page', [NthLandingController::class, 'nthErrorPage'])->name('nthlanding.nthErrorPage');
Route::any('/nthlanding/savepreference', [NthLandingController::class, 'savePreferenceFromResult'])->name('nthlanding.savepreference');
Route::post('/nthlanding/update-clinician-preferences', [NthLandingController::class, 'updateClinicianPreferences'])->name('nthlanding.updateClinicianPreferences');
Route::get('/nthlanding/generate-tinyURL-temp/{clinicianId}', [NthLandingController::class, 'generateTinyURL'])->name('nthlanding.generateTinyURL');
Route::any('/nthlanding/get-general-inquiry', [NthLandingController::class, 'getNthGeneralInquiry'])->name('nthlanding.getNthGeneralInquiry');
Route::any('/nthlanding/save-general-inquiry', [NthLandingController::class, 'saveNthGeneralInquiry'])->name('nthlanding.saveNthGeneralInquiry');
Route::any('/nthlanding/save-nth-case-interest', [NthLandingController::class, 'saveNthCaseInterest'])->name('nthlanding.saveNthCaseInterest');

Route::get('/nthalerts', [NTHAlertsController::class, 'list'])
->name('nthalerts.list')->middleware($listOfmiddleware);
Route::get('/nthalerts/exportdata', [NTHAlertsController::class, 'exportData'])
->name('nthalerts.exportData')->middleware($listOfmiddleware);
Route::any('/nthalerts/getnthleadsandinquiriesdetail',
[NTHAlertsController::class, 'getNthLeadsInquiriesDetail'])
->name('nthalerts.getNthLeadsInquiriesDetail')->middleware($listOfmiddleware);
Route::any('/nthalerts/getclinicianleadsandinquiries',
[NTHAlertsController::class, 'getClinicianAllNthLeadsInquiries'])
->name('nthalerts.getClinicianAllNthLeadsInquiries')->middleware($listOfmiddleware);
Route::post('nthalerts/get-leads-for-bulk', [NTHAlertsController::class, 'getLeadsForBulk'])
    ->name('nthalerts.get-leads-for-bulk')
    ->middleware($listOfmiddleware);
Route::post('nthalerts/check-other-leads', [NTHAlertsController::class, 'checkOtherLeads'])
    ->name('nthalerts.check-other-leads')
    ->middleware($listOfmiddleware);
Route::post('/nthalerts/bulk-resolve-leads', [NTHAlertsController::class, 'bulkResolveLeads'])
    ->name('nthalerts.bulk-resolve-leads');
Route::any('/nthalerts/saveleadsandinquiriesoutcome',
[NTHAlertsController::class, 'saveLeadInquiryOutcome'])
->name('nthalerts.saveLeadInquiryOutcome')->middleware($listOfmiddleware);
Route::get('/nthalerts/history', [NotesController::class, 'nthLeadsInquiriesHistory'])
->name('nthalerts.nthLeadsInquiriesHistory')->middleware($listOfmiddleware);
Route::get('/nthalerts/getnthclientlist', [NTHAlertsController::class, 'getNTHClientList'])
->name('nthalerts.getNTHClientList')->middleware($listOfmiddleware);
//ssu
Route::get('/ssu/mark-ssu-request', [SchoolSetupController::class, 'markSsuRequest'])->name('markSsuRequest')->middleware($listOfmiddleware);

//check client LOB
Route::post('/check-patient-lob', function (Request $request) {
    $result = H::checkPatientHasLobInCol($request->clientPId);
    return response()->json([
        'success' => $result
    ]);
})->name('check.patient.lob')->middleware($listOfmiddleware);

//tracker import
Route::get('/tracker/import', [RecruiterTrackerController::class, 'getTrackerImport'])->name('tracker.import')->middleware($listOfmiddleware);
Route::post('/tracker/processimport', [RecruiterTrackerController::class, 'processTrackerImport'])->name('tracker.processimport')->middleware($listOfmiddleware);

//Data Connection
Route::get('/managesyncdataconnections', [DataConnectionController::class, 'index'])->name('managesyncdataconnections')->middleware($listOfmiddleware);
Route::get('/getdataconnectionlistajax', [DataConnectionController::class, 'getDataConnectionList'])->name('getdataconnectionlistajax')->middleware($listOfmiddleware);
Route::get('/syncdataconnection/edit/{id}', [DataConnectionController::class, 'edit'])->name('syncdataconnection.edit')->middleware($listOfmiddleware);
Route::post('/updatesyncdataconnection', [DataConnectionController::class, 'updateDataConnection'])->name('syncdataconnection.updatesyncdataconnection')->middleware($listOfmiddleware);

//First Advantage Webhook
Route::post('/fi/webhook', [FirstAdvantageController::class, 'webhook'])->name('firstadvantage.webhook');

Route::post('/first-advantage/retry', [FirstAdvantageController::class, 'retry'])->name('first-advantage.retry')->middleware($listOfmiddleware);
Route::post('/first-advantage/get-statuses', [FirstAdvantageController::class, 'getStatuses'])->name('first-advantage.get-statuses')->middleware($listOfmiddleware);
Route::post('/first-advantage/get-errors', [FirstAdvantageController::class, 'getErrors'])->name('first-advantage.get-errors')->middleware($listOfmiddleware);

//Consent Tracking Template
    Route::prefix('consent-tracking')->group(function () {
        Route::get('/', [ConsentTemplateController::class, 'index'])->name('consent.tracking.index');
        Route::get('/data', [ConsentTemplateController::class, 'data'])->name('consent.tracking.data');
        Route::get('/show/{id}', [ConsentTemplateController::class, 'show'])->name('consent.tracking.show');
        Route::post('/save', [ConsentTemplateController::class, 'save'])->name('consent.tracking.save');
        Route::delete('/{id}', [ConsentTemplateController::class, 'destroy'])->name('consent.tracking.destroy');
    })->middleware($listOfmiddleware);


//Adjustments
    Route::prefix('adjustments')->group(function () {
        Route::get('/', [AdjustmentController::class, 'index'])->name('adjustment.index');
        Route::get('/data', [AdjustmentController::class, 'data'])->name('adjustment.data');
        Route::get('/add', [AdjustmentController::class, 'add'])->name('adjustment.add');
        Route::post('/', [AdjustmentController::class, 'save'])->name('adjustment.save');
        Route::get('/edit/{id}', [AdjustmentController::class, 'edit'])->name('adjustment.edit');
        Route::get('/check-category', [AdjustmentController::class, 'checkCategory'])->name('adjustment.check-category');;
    })->middleware($listOfmiddleware);

});


Route::get('/test-vue-page', function () {
   return view('vue-test');
})->name('vue.test');
