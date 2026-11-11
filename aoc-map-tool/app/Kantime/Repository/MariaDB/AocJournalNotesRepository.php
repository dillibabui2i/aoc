<?php

namespace App\Kantime\Repository\MariaDB;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class AocJournalNotesRepository
{
    private const TABLE_NAME = 'journal_notes';

    public function create(array $row): void
    {
        DB::table(self::TABLE_NAME)->insert($row);
    }

    public function update(int $noteId, array $row): void
    {
        DB::table(self::TABLE_NAME)
            ->where('note_id', $noteId)
            ->update($row);
    }

    public function upsertByNoteId(int $noteId, array $row): void
    {
        if (!Schema::hasTable(self::TABLE_NAME)) {
            return;
        }

        $exists = DB::table(self::TABLE_NAME)
            ->where('note_id', $noteId)
            ->exists();

        if ($exists) {
            $row['updated_by'] = 1;
            $this->update($noteId, $row);
            return;
        }

        $this->create($row);
    }

    public function deleteByNoteId(int $noteId): void
    {
        DB::table(self::TABLE_NAME)
            ->where('note_id', $noteId)
            ->delete();
    }
}
