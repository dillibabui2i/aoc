<?php

namespace App\Kantime\Jobs;

use App\Kantime\Services\JournalNotesSyncService;

class SyncJournalNotes extends AbstractSyncJob
{
    public function handle(JournalNotesSyncService $journalNotesSyncService): void
    {
        $journalNotesSyncService->sync();
    }

    public function getType(): string
    {
        return JournalNotesSyncService::SYNC_TYPE;
    }
}
