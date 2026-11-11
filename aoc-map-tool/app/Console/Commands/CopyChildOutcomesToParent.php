<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Copy biller outcomes and billing notes from child schedules of split shifts to the parent schedule.
 *
 * Usage:
 *   php artisan billing:copy-child-outcomes-to-parent            — copy for all parents
 *   php artisan billing:copy-child-outcomes-to-parent --dry-run  — report what would be copied, no DB writes
 *
 * Logic covered by this command:
 *   CASE 0: Parent has no billingReason, one of its children has
 *           → outcome fields of the child with the latest billingReasonAddedAt are set on the parent
 *
 *   CASE 1: Children have billing notes the parent does not have
 *           → the notes are copied to the parent
 *
 *   CASE 2: Writing failed
 *           → the parent's changes are rolled back, error logged, the rest of the run continues
 *
 * Notes:
 *   - Only parents whose children have billing notes are processed.
 *   - Parent: HasChild = 1, is_deleted <> 1, ScheduleDate >= 2023-01-01 (billed parents too: the bucket's week record
 *     lists billed schedules as well). Child: IsChild = 1, ParentCgTaskID = parent's CGTaskID, same PatientID
 *     as the parent, is_deleted <> 1. ParentCgTaskID is not indexed, so children are looked up by PatientID and
 *     ScheduleDate within a day of the parent's. Children are never changed.
 *   - One saved outcome writes a note per schedule with misc4 = time() of that write, so the same author / text
 *     within NOTE_SAME_SAVE_SECONDS counts as one note. A note is copied only if the parent does not have it yet,
 *     so re-running the command changes nothing. Copies keep author and dates, misc3 = "copied_from:<child id>".
 *   - Rollback: delete notes with misc3 LIKE 'copied_from:%', clear billing fields of the parents listed in the log.
 */
class CopyChildOutcomesToParent extends Command
{
    protected $signature = 'billing:copy-child-outcomes-to-parent
                            {--dry-run : Report what would be copied, do not write}';

    protected $description = 'PD-5516: copy biller outcomes and notes from child schedules of split shifts to the parent';

    // parents per chunk, also the number of OR'ed (PatientID, ScheduleDate) ranges in the children query
    private const CHUNK_SIZE = 200;

    private const COPIED_NOTE_MARKER = 'copied_from:';

    private const NOTE_SAME_SAVE_SECONDS = 60;

    private const BILLING_FIELDS = [
        'billingNotes',
        'billingReason',
        'billingSubReason',
        'billingComments',
        'billingReasonAddedAt',
        'billingReasonAddedBy',
    ];

    private bool $isDryRun = false;

    private array $stats = [
        'parents' => 0,
        'fields_copied' => 0,
        'notes_copied' => 0,
        'failed' => 0,
    ];

    /** @var int[] CGTaskIDs of parents that got outcome fields, needed for a rollback */
    private array $fieldsCopiedIds = [];

    public function handle(): int
    {
        $this->isDryRun = (bool) $this->option('dry-run');

        $this->info('=== Copy child outcomes to parent ===');
        $this->info('Mode  : ' . ($this->isDryRun ? 'DRY RUN' : 'LIVE'));

        $parentCgTaskIds = $this->parentCgTaskIdsWithChildNotes();
        $this->info('Parents with child notes: ' . count($parentCgTaskIds));

        foreach (array_chunk($parentCgTaskIds, self::CHUNK_SIZE) as $cgTaskIds) {
            $parents = DB::table('mssql_schedulemaster')
                ->select('id', 'CGTaskID', 'PatientID', 'ScheduleDate', 'billingReason')
                ->whereIn('CGTaskID', $cgTaskIds)
                ->where('HasChild', 1)
                ->where('is_deleted', '<>', 1)
                ->whereDate('ScheduleDate', '>=', '2023-01-01')
                ->get();

            if ($parents->isNotEmpty()) {
                $this->processChunk($parents);
            }
        }

        $this->printFinalReport();

        return $this->stats['failed'] === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Most parents have nothing to copy, so the work list starts from children with billing notes (notes by
     * entity_id). A child outcome always comes with a note (checked on the data), and billingReasonAddedAt
     * has no index, so outcomes are not looked up separately.
     *
     * @return int[]
     */
    private function parentCgTaskIdsWithChildNotes(): array
    {
        return DB::table('notes as n')
            ->join('mssql_schedulemaster as sm', 'sm.id', '=', 'n.entity_id')
            ->where('n.entity', 'billing')
            ->where('sm.IsChild', 1)
            ->where('sm.is_deleted', '<>', 1)
            ->distinct()
            ->pluck('sm.ParentCgTaskID')
            ->map(fn ($cgTaskId) => (int) $cgTaskId)
            ->filter()
            ->sort()
            ->values()
            ->all();
    }

    private function processChunk(Collection $parents): void
    {
        $children = DB::table('mssql_schedulemaster')
            ->select(array_merge(['id', 'PatientID', 'ParentCgTaskID'], self::BILLING_FIELDS))
            ->where(function ($query) use ($parents) {
                // a child starts on the parent's day or, for an overnight shift, the next one
                foreach ($parents as $parent) {
                    $query->orWhere(function ($query) use ($parent) {
                        $query->where('PatientID', $parent->PatientID)
                            ->whereBetween('ScheduleDate', [
                                date('Y-m-d', strtotime($parent->ScheduleDate . ' -1 day')),
                                date('Y-m-d', strtotime($parent->ScheduleDate . ' +1 day')),
                            ]);
                    });
                }
            })
            ->whereIn('ParentCgTaskID', $parents->pluck('CGTaskID')->all())
            ->where('IsChild', 1)
            ->where('is_deleted', '<>', 1)
            ->get();

        $notes = DB::table('notes')
            ->where('entity', 'billing')
            ->whereIn('entity_id', array_merge($parents->pluck('id')->all(), $children->pluck('id')->all()))
            ->orderBy('id')
            ->get()
            ->groupBy('entity_id');

        $childrenByParent = $children->groupBy('ParentCgTaskID');

        foreach ($parents as $parent) {
            $this->stats['parents']++;
            // PatientID is matched per pair: the children query only limits it to the chunk's patients and days
            $parentChildren = $childrenByParent->get($parent->CGTaskID, collect())
                ->where('PatientID', $parent->PatientID);
            $this->processParent($parent, $parentChildren, $notes);
        }
    }

    private function processParent(object $parent, Collection $children, Collection $notes): void
    {
        $sourceChild = $this->hasOutcome($parent) ? null : $children
            ->filter(fn ($child) => $this->hasOutcome($child))
            ->sortByDesc('billingReasonAddedAt')
            ->first();

        $notesToCopy = $this->notesToCopy($parent, $children, $notes);

        if ($sourceChild === null && $notesToCopy === []) {
            return;
        }

        if ($this->isDryRun) {
            $this->countParent($parent, $sourceChild !== null, count($notesToCopy));

            return;
        }

        try {
            DB::transaction(function () use ($parent, $sourceChild, $notesToCopy) {
                $fieldsCopied = false;
                if ($sourceChild !== null) {
                    // billingReason re-checked in the update: a biller may have saved an outcome on the parent meanwhile
                    $fieldsCopied = DB::table('mssql_schedulemaster')
                        ->where('id', $parent->id)
                        ->where(fn ($query) => $query->whereNull('billingReason')->orWhere('billingReason', ''))
                        ->update(array_intersect_key((array) $sourceChild, array_flip(self::BILLING_FIELDS))) > 0;
                }

                foreach ($notesToCopy as [$childId, $note]) {
                    $copy = (array) $note;
                    unset($copy['id']);
                    $copy['entity_id'] = $parent->id;
                    $copy['misc3'] = self::COPIED_NOTE_MARKER . $childId;
                    DB::table('notes')->insert($copy);
                }

                $this->countParent($parent, $fieldsCopied, count($notesToCopy));
            });
        } catch (\Throwable $e) {
            $this->stats['failed']++;
            $message = "Copy failed for parent CGTaskID={$parent->CGTaskID} id={$parent->id}: {$e->getMessage()}";
            $this->error($message);
            Log::error($message);
        }
    }

    /**
     * Children notes the parent doesn't have yet, one per saved outcome (see NOTE_SAME_SAVE_SECONDS).
     *
     * @return array<int, array{0: int, 1: object}> [child id, note row]
     */
    private function notesToCopy(object $parent, Collection $children, Collection $notes): array
    {
        // author|text => misc4 values already on the parent or picked for copying
        $known = [];
        foreach ($notes->get($parent->id, collect()) as $note) {
            $known[$this->noteKey($note)][] = (int) $note->misc4;
        }

        $notesToCopy = [];
        foreach ($children as $child) {
            foreach ($notes->get($child->id, collect()) as $note) {
                $key = $this->noteKey($note);
                $savedAt = (int) $note->misc4;
                $isKnown = false;
                foreach ($known[$key] ?? [] as $knownSavedAt) {
                    if (abs($knownSavedAt - $savedAt) <= self::NOTE_SAME_SAVE_SECONDS) {
                        $isKnown = true;
                        break;
                    }
                }

                if (!$isKnown) {
                    $known[$key][] = $savedAt;
                    $notesToCopy[] = [(int) $child->id, $note];
                }
            }
        }

        return $notesToCopy;
    }

    private function noteKey(object $note): string
    {
        return $note->created_by . '|' . $note->notes;
    }

    private function hasOutcome(object $schedule): bool
    {
        return (string) ($schedule->billingReason ?? '') !== '';
    }

    private function countParent(object $parent, bool $fieldsCopied, int $notesCount): void
    {
        if ($fieldsCopied) {
            $this->stats['fields_copied']++;
            $this->fieldsCopiedIds[] = (int) $parent->CGTaskID;
        }
        $this->stats['notes_copied'] += $notesCount;
    }

    private function printFinalReport(): void
    {
        $prefix = $this->isDryRun ? 'Would be ' : '';

        $this->info('');
        $this->info('=== Copy child outcomes to parent complete ===');
        $this->info('Parents checked        : ' . $this->stats['parents']);
        $this->info(str_pad($prefix . 'outcome copied', 23) . ': ' . $this->stats['fields_copied']);
        $this->info(str_pad($prefix . 'notes copied', 23) . ': ' . $this->stats['notes_copied']);
        $this->info('Failed                 : ' . $this->stats['failed']);

        if (!$this->isDryRun) {
            Log::info('billing:copy-child-outcomes-to-parent finished', $this->stats + [
                'fields_copied_cg_task_ids' => $this->fieldsCopiedIds,
            ]);
        }
    }
}
