<?php

namespace App\Providers;

use App\Events\Adjustment\AdjustmentCreated;
use App\Events\InactivateNRNCTrackers;
use App\Events\CandidateFieldUpdated;
use App\Events\JobCategoryChanged;
use App\Events\Adjustment\AdjustmentChanged;
use App\Events\Map\MapUpdatedEvent;
use App\Events\MssqlRecruiterTracker\MssqlRecruiterTrackerOutdated;
use App\Events\NTH\NTHScheduleChanged;
use App\Events\NthInquiryCreated;
use App\Events\Offer\OfferApprovedEvent;
use App\Events\TrachVentClass\ClinicianCancelledFromTrachVentClass;
use App\Events\TrachVentClass\ClinicianRescheduledWithinTrachVentClass;
use App\Events\TrachVentClass\ClinicianScheduledIntoTrachVentClass;
use App\Events\TrachVentClass\TrachVentClassOccurrenceCancelled;
use App\Events\User\UserHasTrachVentClassesDisabled;
use App\Listeners\Adjustment\LogAdjustmentCreated;
use App\Listeners\AutoInactivateNRNCTrackers;
use App\Events\Providers\ProvidersBatchSynced;
use App\Listeners\HandleNthInquiryCreated;
use App\Listeners\LogCandidateFieldChange;
use App\Listeners\LogJobCategoryChange;
use App\Listeners\Adjustment\LogAdjustmentChanged;
use App\Listeners\Map\MapUpdatedListener;
use App\Listeners\NTH\NTHSchedule;
use App\Listeners\Offer\OfferListener;
use App\Listeners\Providers\ProviderMatchListener;
use App\Listeners\TrachVentClass\EmailClinicianAboutCancelledClass;
use App\Listeners\TrachVentClass\EmailClinicianAboutRescheduledClass;
use App\Listeners\TrachVentClass\EmailClinicianAboutScheduledClass;
use App\Listeners\TrachVentClass\NotifyInstructorAboutCancelledClinician;
use App\Listeners\TrachVentClass\NotifyInstructorAboutRescheduledClinician;
use App\Listeners\TrachVentClass\NotifyInstructorAboutScheduledClinician;
use App\Listeners\TrachVentClass\NotifyManagerAboutDisabledUser;
use App\Listeners\TrachVentClass\NotifySchedulerAboutCancelledOccurrence;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        JobCategoryChanged::class => [
            LogJobCategoryChange::class,
        ],
        AdjustmentCreated::class => [
            LogAdjustmentCreated::class,
        ],
        AdjustmentChanged::class => [
            LogAdjustmentChanged::class,
        ],
        CandidateFieldUpdated::class => [
            LogCandidateFieldChange::class,
        ],
        ProvidersBatchSynced::class => [
            ProviderMatchListener::class,
        ],
        InactivateNRNCTrackers::class => [
            AutoInactivateNRNCTrackers::class,
        ],
        NthInquiryCreated::class => [
            HandleNthInquiryCreated::class,
        ],
        NTHScheduleChanged::class => [
            NTHSchedule::class,
        ],
        OfferApprovedEvent::class => [
            OfferListener::class,
        ],
        MapUpdatedEvent::class => [
            MapUpdatedListener::class,
        ],
        UserHasTrachVentClassesDisabled::class => [
            NotifyManagerAboutDisabledUser::class,
        ],
        ClinicianScheduledIntoTrachVentClass::class => [
            NotifyInstructorAboutScheduledClinician::class,
            EmailClinicianAboutScheduledClass::class,
        ],
        ClinicianCancelledFromTrachVentClass::class => [
            NotifyInstructorAboutCancelledClinician::class,
            EmailClinicianAboutCancelledClass::class,
        ],
        ClinicianRescheduledWithinTrachVentClass::class => [
            NotifyInstructorAboutRescheduledClinician::class,
            EmailClinicianAboutRescheduledClass::class,
        ],
        TrachVentClassOccurrenceCancelled::class => [
            NotifySchedulerAboutCancelledOccurrence::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        //
    }
}
