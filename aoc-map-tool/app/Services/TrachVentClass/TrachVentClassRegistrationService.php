<?php

namespace App\Services\TrachVentClass;

use App\Events\TrachVentClass\ClinicianCancelledFromTrachVentClass;
use App\Events\TrachVentClass\ClinicianRescheduledWithinTrachVentClass;
use App\Exceptions\TrachVentClassRegistrationException;
use App\Repositories\TrachVentClass\TrachVentClassOccurrenceReadRepository;
use App\Repositories\User\UserReadRepository;
use App\Enums\DeletionStatus;
use App\TrachVentClassOccurrence;
use App\TrachVentClassRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

final readonly class TrachVentClassRegistrationService
{
    public function __construct(
        private TrachVentClassOccurrenceReadRepository $trachVentClassOccurrenceReadRepository,
        private UserReadRepository $userReadRepository,
    ) {
    }

    /**
     * Soft-deletes the registration and notifies the instructor. The notification shares the
     * transaction, so if it fails the registration stays in place.
     *
     * @throws TrachVentClassRegistrationException
     */
    public function cancel(int $occurrenceId, int $registrationId): void
    {
        $userId = Auth::id();
        $userName = (string) Auth::user()?->name;

        DB::transaction(function () use ($occurrenceId, $registrationId, $userId, $userName) {
            $occurrence = $this->lockOccurrences([$occurrenceId])->get($occurrenceId)
                ?? throw TrachVentClassRegistrationException::classNotFound();
            $registration = $this->lockRegistration($registrationId, $occurrenceId);

            $registration->update(['is_deleted' => DeletionStatus::DELETED->value, 'updated_by' => $userId]);

            event(new ClinicianCancelledFromTrachVentClass($userName, $occurrence, (string) $registration->display_name, $registration));
        });
    }

    /**
     * Moves the registration to the target class and notifies the instructor of the original class.
     * Both classes are locked in id order so concurrent moves cannot deadlock or overfill a class.
     *
     * @throws TrachVentClassRegistrationException
     */
    public function reschedule(int $occurrenceId, int $registrationId, int $targetOccurrenceId): void
    {
        $userId = Auth::id();
        $userName = (string) Auth::user()?->name;

        DB::transaction(function () use ($occurrenceId, $registrationId, $targetOccurrenceId, $userId, $userName) {
            $locked = $this->lockOccurrences([$occurrenceId, $targetOccurrenceId]);
            $occurrence = $locked->get($occurrenceId) ?? throw TrachVentClassRegistrationException::classNotFound();
            $target = $locked->get($targetOccurrenceId) ?? throw TrachVentClassRegistrationException::targetClassNotFound();
            $registration = $this->lockRegistration($registrationId, $occurrenceId);

            $this->assertValidTarget($occurrence, $target, $registration);

            $registration->update(['trach_vent_class_occurrence_id' => $target->id, 'updated_by' => $userId]);

            event(new ClinicianRescheduledWithinTrachVentClass($userName, $occurrence, $target, (string) $registration->display_name, $registration));
        });
    }

    /**
     * Upcoming classes of the same class type and branch the registration could move to.
     *
     * @throws TrachVentClassRegistrationException
     */
    public function rescheduleOptions(int $occurrenceId, int $registrationId): array
    {
        $occurrence = $this->trachVentClassOccurrenceReadRepository->findWithDetails($occurrenceId)
            ?? throw TrachVentClassRegistrationException::classNotFound();
        $registration = TrachVentClassRequest::where('id', $registrationId)
            ->where('trach_vent_class_occurrence_id', $occurrenceId)
            ->active()
            ->first() ?? throw TrachVentClassRegistrationException::registrationNotFound();

        $targets = $this->trachVentClassOccurrenceReadRepository->findRescheduleTargets($occurrence);
        $instructorNames = $this->userReadRepository
            ->getInstructorsByIds($targets->pluck('instructor_id')->filter()->unique()->all())
            ->pluck('name', 'id');
        $registeredIn = $this->registeredOccurrenceIds($registration, $targets->pluck('id')->all());

        return $targets->map(fn (TrachVentClassOccurrence $target) => [
            'id' => $target->id,
            'class_date' => getFormatedDate($target->class_date),
            'class_time' => getFormatedTime($target->class_time),
            'end_time' => getFormatedTime($target->end_time),
            'instructor' => $instructorNames->get($target->instructor_id, ''),
            'enrolled_count' => (int) $target->enrollments_count,
            'capacity' => (int) $target->capacity,
            'is_full' => (int) $target->enrollments_count >= (int) $target->capacity,
            'already_registered' => in_array($target->id, $registeredIn, true),
        ])->values()->all();
    }

    private function assertValidTarget(
        TrachVentClassOccurrence $occurrence,
        TrachVentClassOccurrence $target,
        TrachVentClassRequest $registration,
    ): void {
        $isUpcoming = Carbon::parse($target->class_date . ' ' . $target->class_time)->greaterThanOrEqualTo(now());

        if (
            $target->id === $occurrence->id
            || !$isUpcoming
            || $target->trachVentClass->location_id !== $occurrence->trachVentClass->location_id
            || $target->trachVentClass->type !== $occurrence->trachVentClass->type
            || $target->class_type !== $occurrence->class_type
        ) {
            throw TrachVentClassRegistrationException::targetClassNotFound();
        }

        if ($target->enrolledCount() >= (int) $target->capacity) {
            throw TrachVentClassRegistrationException::targetClassFull();
        }

        if ($this->registeredOccurrenceIds($registration, [$target->id]) !== []) {
            throw TrachVentClassRegistrationException::alreadyRegisteredInTarget();
        }
    }

    /**
     * @param  array<int, int>  $occurrenceIds
     * @return array<int, int>
     */
    private function registeredOccurrenceIds(TrachVentClassRequest $registration, array $occurrenceIds): array
    {
        if ($occurrenceIds === []) {
            return [];
        }

        $query = TrachVentClassRequest::whereIn('trach_vent_class_occurrence_id', $occurrenceIds)->active();

        if ($registration->candidate_id !== null) {
            $query->where('candidate_id', $registration->candidate_id);
        } else {
            $query->where('provider_id', $registration->provider_id);
        }

        return $query->pluck('trach_vent_class_occurrence_id')->map(fn ($id) => (int) $id)->all();
    }

    /**
     * @param  array<int, int>  $ids
     * @return \Illuminate\Support\Collection<int, TrachVentClassOccurrence>
     */
    private function lockOccurrences(array $ids): \Illuminate\Support\Collection
    {
        return TrachVentClassOccurrence::with('trachVentClass.classCategory')
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    private function lockRegistration(int $registrationId, int $occurrenceId): TrachVentClassRequest
    {
        return TrachVentClassRequest::where('id', $registrationId)
            ->where('trach_vent_class_occurrence_id', $occurrenceId)
            ->active()
            ->lockForUpdate()
            ->first() ?? throw TrachVentClassRegistrationException::registrationNotFound();
    }
}
