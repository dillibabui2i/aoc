<?php

namespace App\Services\MssqlRecruiterTracker;

use App\DTO\NotesDTO;
use App\Repositories\MssqlRecruiterTracker\MssqlRecruiterTrackerReadRepository;
use App\Repositories\Notes\NotesWriteRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class MssqlRecruiterTrackerService
{
    private const ERROR_MASSAGE = 'Failed to auto-inactivate NRNC trackers: ';

    public function __construct(
        private NotesWriteRepository $notesWriteRepository,
        private MssqlRecruiterTrackerReadRepository $trackerReadRepository
    ) {}

    public function inactivateNRNCTrackers(int $trackerId, int $providerId): void
    {
        DB::beginTransaction();

        try {
            $trackers = $this->trackerReadRepository->getActiveNRNCTrackers($trackerId, $providerId);

            foreach ($trackers as $tracker) {
                $tracker->is_active = 0;
                $tracker->inactive_reason = 'Inactivate on tracker ' . $trackerId;
                $tracker->save();

                $this->notesWriteRepository->create(
                    new NotesDTO(
                        'tracker-activity',
                        $tracker->id,
                        'edit',
                        'Auto-closed on tracker creation of ' . $trackerId,
                        now(),
                    )
                );
            }
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error(sprintf('%s%s', self::ERROR_MASSAGE, $e->getMessage()), [
                'providerId' => $providerId,
                'newTrackerId' => $trackerId,
            ]);
        }
    }
}
