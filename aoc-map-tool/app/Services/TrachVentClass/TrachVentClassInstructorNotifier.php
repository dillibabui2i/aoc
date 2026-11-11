<?php

namespace App\Services\TrachVentClass;

use App\Candidates;
use App\Events\TrachVentClass\ClinicianScheduledIntoTrachVentClass;
use App\MssqlProviders;
use App\Repositories\TrachVentClass\TrachVentClassOccurrenceReadRepository;
use App\TrachVentClassRequest;
use App\User;

final readonly class TrachVentClassInstructorNotifier
{
    public function __construct(
        private TrachVentClassOccurrenceReadRepository $trachVentClassOccurrenceReadRepository,
        private Candidates $candidates,
        private MssqlProviders $mssqlProviders,
        private User $user,
    ) {
    }

    public function notify(?string $occurrenceId, ?int $candidateId, ?int $providerId, ?int $createdBy, TrachVentClassRequest $registration): void
    {
        if (empty($occurrenceId) || (empty($candidateId) && empty($providerId))) {
            return;
        }

        $occurrence = $this->trachVentClassOccurrenceReadRepository->findWithDetails((int) $occurrenceId);
        $scheduledBy = $createdBy !== null ? $this->user->find($createdBy) : null;

        if (!$occurrence || !$scheduledBy instanceof User) {
            return;
        }

        [$firstName, $lastName] = $this->resolveScheduledPersonName($candidateId, $providerId);

        if ($firstName === null) {
            return;
        }

        event(new ClinicianScheduledIntoTrachVentClass(
            $scheduledBy->name,
            $occurrence,
            $firstName,
            $lastName,
            $registration,
        ));
    }

    private function resolveScheduledPersonName(?int $candidateId, ?int $providerId): array
    {
        if (!empty($candidateId)) {
            $candidate = $this->candidates->find($candidateId);

            if ($candidate) {
                return [$candidate->firstName ?? '', $candidate->lastName ?? ''];
            }
        }

        if (!empty($providerId)) {
            $provider = $this->mssqlProviders->find($providerId);

            if ($provider) {
                return [$provider->FirstName ?? '', $provider->LastName ?? ''];
            }
        }

        return [null, null];
    }
}
