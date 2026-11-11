<?php

namespace App\Services\TrachVentClass;

use App\DTO\TrachVentClass\ClassEmailDetailsDTO;
use App\Enums\TrachVentClassClinicianEmailEnum;
use App\Helpers\ActivityNotes;
use App\Helpers\LD;
use App\Mail\TrachVentClass\ClinicianClassMail;
use App\Repositories\User\UserReadRepository;
use App\TrachVentClassOccurrence;
use App\TrachVentClassRequest;
use Illuminate\Contracts\Mail\Mailer;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Emails the clinician about a registration change and records the outcome (sent, failed, or no address)
 * as a note on the clinician profile and on the registration's source tracker. A failure here never
 * affects the registration change itself.
 */
final readonly class TrachVentClassClinicianNotifier
{
    private const FEATURE_FLAG = 'schedule_trach_vent_tpn_class';

    public function __construct(
        private UserReadRepository $userReadRepository,
        private ActivityNotes $activityNotes,
        private Mailer $mailer,
        private LoggerInterface $logger,
    ) {
    }

    public function registered(TrachVentClassRequest $registration, TrachVentClassOccurrence $occurrence, string $scheduledByName): void
    {
        $class = $this->details($occurrence);
        $clinicianName = (string) $registration->display_name;

        $this->send(
            TrachVentClassClinicianEmailEnum::REGISTERED,
            $registration,
            $class,
            sprintf(
                '%s was scheduled for class %s on %s at %s (instructor %s) by %s.',
                e($clinicianName),
                e($class->classType),
                $class->shortDate,
                $class->startTime,
                e($class->instructor),
                e($scheduledByName),
            ),
            new ClinicianClassMail(TrachVentClassClinicianEmailEnum::REGISTERED, $clinicianName, $class),
        );
    }

    public function cancelled(TrachVentClassRequest $registration, TrachVentClassOccurrence $occurrence, string $cancelledByName): void
    {
        $class = $this->details($occurrence);
        $clinicianName = (string) $registration->display_name;

        $this->send(
            TrachVentClassClinicianEmailEnum::CANCELLED,
            $registration,
            $class,
            sprintf(
                '%s was cancelled from class %s on %s at %s (instructor %s) by %s.',
                e($clinicianName),
                e($class->classType),
                $class->shortDate,
                $class->startTime,
                e($class->instructor),
                e($cancelledByName),
            ),
            new ClinicianClassMail(TrachVentClassClinicianEmailEnum::CANCELLED, $clinicianName, $class),
        );
    }

    public function rescheduled(
        TrachVentClassRequest $registration,
        TrachVentClassOccurrence $original,
        TrachVentClassOccurrence $new,
        string $rescheduledByName,
    ): void {
        $previous = $this->details($original);
        $class = $this->details($new);
        $clinicianName = (string) $registration->display_name;

        $this->send(
            TrachVentClassClinicianEmailEnum::RESCHEDULED,
            $registration,
            $class,
            sprintf(
                '%s was rescheduled from class %s on %s at %s to %s at %s (instructor %s) by %s.',
                e($clinicianName),
                e($previous->classType),
                $previous->shortDate,
                $previous->startTime,
                $class->shortDate,
                $class->startTime,
                e($class->instructor),
                e($rescheduledByName),
            ),
            new ClinicianClassMail(TrachVentClassClinicianEmailEnum::RESCHEDULED, $clinicianName, $class, $previous),
        );
    }

    private function send(
        TrachVentClassClinicianEmailEnum $type,
        TrachVentClassRequest $registration,
        ClassEmailDetailsDTO $class,
        string $actionText,
        ClinicianClassMail $mail,
    ): void {
        if (!LD::checkFeatureFlag(self::FEATURE_FLAG)) {
            return;
        }

        $email = trim((string) ($registration->provider?->External_Email ?? $registration->candidate?->email ?? ''));
        $label = $type->noteLabel();

        if ($email === '') {
            $outcome = sprintf('No %s was sent because no email address is on file.', lcfirst($label));
        } else {
            try {
                $this->mailer->to($email)->send($mail);
                $outcome = sprintf('%s sent to %s.', $label, e($email));
            } catch (Throwable $exception) {
                $this->logger->error('Trach/Vent clinician email failed', [
                    'type' => $type->value,
                    'registration_id' => $registration->id,
                    'error' => $exception->getMessage(),
                ]);
                $outcome = sprintf('The %s to %s failed to send, please follow up.', lcfirst($label), e($email));
            }
        }

        $this->postNote($registration, sprintf('%s %s %s', $actionText, $outcome, $this->classLink($class)));
    }

    private function postNote(TrachVentClassRequest $registration, string $note): void
    {
        $targets = [];

        if ($registration->provider_id) {
            $targets[] = ['clinician', $registration->provider_id];
        } elseif ($registration->candidate_id) {
            $targets[] = ['candidate', $registration->candidate_id];
        }

        if ($registration->tracker_id) {
            $targets[] = ['tracker', $registration->tracker_id];
        }

        foreach ($targets as [$entity, $entityId]) {
            $this->activityNotes->setActivityNotesFromConsole([
                'entity' => $entity,
                'entity_id' => $entityId,
                'notes' => $note,
                'completed' => 1,
            ]);
        }
    }

    private function classLink(ClassEmailDetailsDTO $class): string
    {
        return sprintf(
            '<a href="#" class="js-trach-vent-class-details" data-occurrence-id="%d">Click here to view class details.</a>',
            $class->occurrenceId,
        );
    }

    private function details(TrachVentClassOccurrence $occurrence): ClassEmailDetailsDTO
    {
        $instructorName = $occurrence->instructor_id
            ? (string) $this->userReadRepository->getInstructorsByIds([$occurrence->instructor_id])->first()?->name
            : '';

        return ClassEmailDetailsDTO::fromOccurrence($occurrence, $instructorName);
    }
}
