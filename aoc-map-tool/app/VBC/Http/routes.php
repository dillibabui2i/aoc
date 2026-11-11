<?php

use App\VBC\Http\Controllers\CareCoordination\CareCoordinationController;
use App\VBC\Http\Controllers\Common\FiltersController;
use App\VBC\Http\Controllers\Diagnosis\Icd10SearchController;
use App\VBC\Http\Controllers\Followup\FollowupController;
use App\VBC\Http\Controllers\Hospitalization\HospitalizationController;
use App\VBC\Http\Controllers\HospitalizationTrackerContact\HospitalizationTrackerContactController;
use App\VBC\Http\Controllers\HospitalizationTrackerDiagnosis\HospitalizationTrackerDiagnosisController;
use App\VBC\Http\Controllers\HospitalizationTrackerTraining\HospitalizationTrackerTrainingController;
use App\VBC\Http\Controllers\Immunizations\ImmunizationScheduleController;
use App\VBC\Http\Controllers\Immunizations\ImmunizationsController;
use App\VBC\Http\Controllers\SickPlan\SickPlanController;
use Illuminate\Support\Facades\Route;

Route::prefix('vbc')->group(function () {
    // Shared filter-options payload for every bucket below (and the All Hospitalizations page) —
    // no single entity owns it, see FiltersService. Also doubles as the frontend's permission probe
    // (see usePermissions.js) — every route in this file already 403s for a user without the Intensive
    // Case Management permission, so no dedicated "am I allowed" endpoint is needed on top of it.
    Route::get('/filters', [FiltersController::class, 'index'])->name('filters.index');

    Route::prefix('hospitalization')->group(function () {
        Route::get('/', [HospitalizationController::class, 'index'])->name('hospitalization.index');
        Route::get('/all-data', [HospitalizationController::class, 'getAllHospitalizations'])->name('hospitalization.all-data');
        Route::get('/all-data/export', [HospitalizationController::class, 'exportAllHospitalizations'])->name('hospitalization.all-data.export');
        // Reserved for the ICM dashboard "Hospitalization" bucket widget, not yet wired to a real frontend.
        Route::get('/data', [HospitalizationController::class, 'getHospitalizations'])->name('hospitalization.data');
        // Triage dashboard "ED Visits" bucket (PD-4057) — gated by the "Manage ED Visits" permission,
        // not the Intensive Case Management one.
        Route::get('/ed-visits', [HospitalizationController::class, 'getEdVisits'])->name('hospitalization.ed-visits.data');
        // "ED Visit Record" form (PD-3983) — the "+" on the ED Visits bucket. Reuses the client lookup
        // and tracker endpoints below; only creation gets its own route, since its DTO differs (ED
        // encounter / "In Progress" status instead of IP / "Open").
        Route::post('/ed-visits/trackers', [HospitalizationController::class, 'storeEdVisitTracker'])
            ->name('hospitalization.ed-visits.trackers.store');

        // "Hospitalization Record" form: client lookup, duplicate-tracker guard, and the tracker itself.
        Route::get('/clients', [HospitalizationController::class, 'searchClients'])->name('hospitalization.clients.index');
        Route::get('/clients/{patientId}', [HospitalizationController::class, 'showClient'])
            ->whereNumber('patientId')
            ->name('hospitalization.clients.show');
        Route::get('/assignees', [HospitalizationController::class, 'getAssignees'])->name('hospitalization.assignees.index');
        Route::post('/trackers', [HospitalizationController::class, 'storeTracker'])->name('hospitalization.trackers.store');
        Route::get('/trackers/{trackerId}/{encounterType}', [HospitalizationController::class, 'showTracker'])
            ->whereNumber('trackerId')
            ->where('encounterType', 'IP|ED')
            ->name('hospitalization.trackers.show');
        Route::patch('/trackers/{trackerId}', [HospitalizationController::class, 'updateTracker'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.update');
        Route::patch('/trackers/{trackerId}/outcome', [HospitalizationController::class, 'updateOutcome'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.outcome.update');
        // The Current Hospitalization Info section is saved as a whole, so it gets its own route rather
        // than going through the single-field PATCH above.
        Route::put('/trackers/{trackerId}/current-info', [HospitalizationController::class, 'updateCurrentInfo'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.current-info.update');
        // "ED Visit Record" form's "Hospitalization Info" tab (PD-3983): its own field set
        // (HospitalizationTrackerFieldEnum::edVisitInfoFields()), so its own route rather than reusing
        // current-info above.
        Route::put('/trackers/{trackerId}/ed-info', [HospitalizationController::class, 'updateEdVisitInfo'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.ed-info.update');
        Route::patch('/trackers/{trackerId}/ed-follow-up', [HospitalizationController::class, 'updateEdVisitFollowup'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.ed-follow-up.update');
        Route::get('/trackers/{trackerId}/notes', [HospitalizationController::class, 'getTrackerNotes'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.notes.index');
        Route::get('/trackers/{trackerId}/history', [HospitalizationController::class, 'getTrackerHistory'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.history.index');
        Route::post('/trackers/{trackerId}/notes',[HospitalizationController::class, 'storeTrackerNote'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.notes.store');
        // Sick Plan belongs to the client, not the tracker, but is only ever viewed/edited through a
        // tracker's Sick Plan tab, so it's nested here the same way Notes is.
        Route::get('/trackers/{trackerId}/sick-plan', [SickPlanController::class, 'show'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.sick-plan.show');
        Route::patch('/trackers/{trackerId}/sick-plan/items/{itemId}', [SickPlanController::class, 'update'])
            ->whereNumber('trackerId')
            ->whereNumber('itemId')
            ->name('hospitalization.trackers.sick-plan.items.update');
        // Contacts (PD-3693): scoped to a single tracker, editable only while it's "In Progress" —
        // see HospitalizationTrackerContactService::guardEditable().
        Route::get('/contact-types', [HospitalizationTrackerContactController::class, 'getTypes'])
            ->name('hospitalization.contact-types.index');
        Route::post('/trackers/{trackerId}/contacts', [HospitalizationTrackerContactController::class, 'store'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.contacts.store');
        Route::patch('/trackers/{trackerId}/contacts/{contactId}', [HospitalizationTrackerContactController::class, 'update'])
            ->whereNumber('trackerId')
            ->whereNumber('contactId')
            ->name('hospitalization.trackers.contacts.update');

        // Diagnosis (PD-3498): scoped to a single tracker's encounter, editable only while it's
        // "In Progress" — see HospitalizationTrackerDiagnosisService::guardEditable(). The toggle is a
        // separate endpoint from the diagnoses list (see the controller).
        Route::get('/diagnosis-codes/search', [Icd10SearchController::class, 'search'])
            ->name('hospitalization.diagnosis-codes.search');
        Route::post('/trackers/{trackerId}/diagnoses', [HospitalizationTrackerDiagnosisController::class, 'store'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.diagnoses.store');
        Route::patch('/trackers/{trackerId}/diagnoses/{diagnosisId}', [HospitalizationTrackerDiagnosisController::class, 'update'])
            ->whereNumber('trackerId')
            ->whereNumber('diagnosisId')
            ->name('hospitalization.trackers.diagnoses.update');
        Route::patch('/trackers/{trackerId}/diagnosis-toggle', [HospitalizationTrackerDiagnosisController::class, 'updateWasDiagnosisMade'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.diagnosis-toggle.update');

        // Education / Training records: scoped to a single tracker, editable only while it's
        // "In Progress" — see HospitalizationTrackerTrainingService::guardEditable(). The
        // "Staff/Family Training/Education required?" toggle is a separate endpoint from the
        // records list, mirroring the Diagnosis toggle above.
        Route::get('/training-types', [HospitalizationTrackerTrainingController::class, 'getTypes'])
            ->name('hospitalization.training-types.index');
        Route::get('/training-sources', [HospitalizationTrackerTrainingController::class, 'getSources'])
            ->name('hospitalization.training-sources.index');
        Route::post('/trackers/{trackerId}/educations', [HospitalizationTrackerTrainingController::class, 'store'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.educations.store');
        Route::patch('/trackers/{trackerId}/educations/{educationId}', [HospitalizationTrackerTrainingController::class, 'update'])
            ->whereNumber('trackerId')
            ->whereNumber('educationId')
            ->name('hospitalization.trackers.educations.update');
        Route::patch('/trackers/{trackerId}/education-toggle', [HospitalizationTrackerTrainingController::class, 'updateStaffFamilyTrainingRequired'])
            ->whereNumber('trackerId')
            ->name('hospitalization.trackers.education-toggle.update');
    });
    Route::prefix('care-coordination')->group(function () {
        Route::get('/', [CareCoordinationController::class, 'getCareCoordinations'])->name('care-coordination.index');
        Route::get('/clients', [CareCoordinationController::class, 'searchEligibleClients'])->name('care-coordination.clients.index');
        Route::get('/types', [CareCoordinationController::class, 'getTypes'])->name('care-coordination.types.index');
        Route::post('/', [CareCoordinationController::class, 'store'])->name('care-coordination.store');
        Route::get('/{id}', [CareCoordinationController::class, 'show'])
            ->whereNumber('id')
            ->name('care-coordination.show');
        Route::patch('/{id}', [CareCoordinationController::class, 'update'])
            ->whereNumber('id')
            ->name('care-coordination.update');
    });
    Route::prefix('follow-up')->group(function () {
        Route::get('/', [FollowupController::class, 'getFollowups'])->name('follow-up.index');
        // Autocomplete for the Follow-up modal's "Hospitalized Client Name" field; listed before the
        // numeric {followupId} route below so "clients" is never captured by it.
        Route::get('/clients', [FollowupController::class, 'searchClients'])->name('follow-up.clients.index');
        Route::post('/', [FollowupController::class, 'store'])->name('follow-up.store');
        Route::get('/{followupId}', [FollowupController::class, 'show'])
            ->whereNumber('followupId')
            ->name('follow-up.show');
        Route::patch('/{followupId}/outcome', [FollowupController::class, 'updateOutcome'])
            ->whereNumber('followupId')
            ->name('follow-up.outcome.update');
    });
    Route::prefix('immunizations')->group(function () {
        Route::get('/', [ImmunizationsController::class, 'index'])->name('immunization.index');
        Route::get('/all', [ImmunizationsController::class, 'getAllImmunizations'])->name('immunization.all-data');
        Route::get('/all/export', [ImmunizationsController::class, 'exportAllImmunizations'])->name('immunization.all-data.export');
        Route::get('/bucket', [ImmunizationsController::class, 'getImmunizationBucket'])->name('immunization.bucket-data');
    });
    Route::prefix('immunization-schedules')->group(function () {
        Route::get('/', [ImmunizationScheduleController::class, 'index'])->name('immunization.schedule.manage');
        Route::get('/data', [ImmunizationScheduleController::class, 'getAll'])->name('immunization.schedule.data');
        Route::post('/', [ImmunizationScheduleController::class, 'store'])->name('immunization.schedule.store');
        Route::get('/{scheduleId}', [ImmunizationScheduleController::class, 'show'])
            ->whereNumber('scheduleId')
            ->name('immunization.schedule.show');
        Route::put('/{scheduleId}', [ImmunizationScheduleController::class, 'update'])
            ->whereNumber('scheduleId')
            ->name('immunization.schedule.update');
    });
});
