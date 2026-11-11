<?php

/**
 *
 * @see ApiRequestFactory to define request class for validation
 */

use App\PrivateAPI\Factories\ApiRequestFactory;
use App\PrivateAPI\Http\Controllers\Candidates\CandidatesProxyController;
use App\PrivateAPI\Http\Controllers\NHT\NTHProxyController;
use App\PrivateAPI\Http\Controllers\Notes\NotesProxyController;
use App\PrivateAPI\Http\Controllers\Providers\ProvidersProxyController;
use App\PrivateAPI\Http\Controllers\Trackers\TrackersProxyController;

Route::prefix('_')->middleware('api.context')->group(function () {
    Route::prefix('trackers')->group(function () {
        Route::post('/', [TrackersProxyController::class, 'create'])->name('trackers-proxy.create');
        Route::patch('/{id}', [TrackersProxyController::class, 'save'])->name('trackers-proxy.save');
    });

    Route::prefix('needs-to-hire')->group(function () {
        Route::patch('/', [NTHProxyController::class, 'save'])->name('nth-proxy.save');
    });

    Route::prefix('notes')->group(function () {
        Route::post('/', [NotesProxyController::class, 'save'])->name('notes-proxy.save');
    });

    Route::prefix('candidates')->group(function () {
        Route::patch('/update-field', [CandidatesProxyController::class, 'updateField'])
            ->name('candidates-proxy.update-field');
        Route::patch('/{id}', [CandidatesProxyController::class, 'update'])
            ->name('candidates-proxy.update')
            ->middleware(['api.candidate-update-cooldown']);
    });

    Route::prefix('providers')->group(function () {
        Route::patch('/{id}', [ProvidersProxyController::class, 'updateCustomField'])->name('providers-proxy.custom-field.update');
    });
});
