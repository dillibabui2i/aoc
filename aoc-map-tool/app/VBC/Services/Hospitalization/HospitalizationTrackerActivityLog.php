<?php

namespace App\VBC\Services\Hospitalization;

use App\Helpers\ActivityNotes;
use App\VBC\Enum\Encounter\EncounterTypeEnum;
use App\VBC\Enum\HospitalizationTracker\HospitalizationTrackerFieldEnum;
use App\VBC\Models\HospitalizationTracker\HospitalizationTrackerModel;

/**
 * Writes the tracker's Activity Log through the project-wide notes mechanism
 * (App\Helpers\ActivityNotes), the same one every other tracker/profile change uses — so VBC entries sit
 * in `notes` alongside the rest instead of in a store of their own.
 *
 * Only the fields the ticket marks as logged produce an entry, and only when the value actually changed.
 */
class HospitalizationTrackerActivityLog
{
    /** notes.entity for a hospitalization tracker; notes.entity_id is the tracker id. */
    public const ENTITY = 'hospitalization_tracker';

    public function __construct(private readonly ActivityNotes $activityNotes)
    {}

    /**
     * @param  array<string, mixed>  $before  field values as they were, keyed by field name
     * @param  array<string, mixed>  $after   field values as they now are
     */
    public function recordChanges(HospitalizationTrackerModel $tracker, array $before, array $after): void
    {
        $author = auth()->user()?->name;
        $isEdVisit = $tracker->encounter->type === EncounterTypeEnum::ED;

        foreach ($after as $key => $newValue) {
            $field = HospitalizationTrackerFieldEnum::from($key);
            if (!$field->logsActivity() && !($isEdVisit && $field->logsActivityForEdVisit())) {
                continue;
            }

            $oldValue = $before[$key] ?? null;
            if ($oldValue === $newValue) {
                continue;
            }

            $this->write($tracker, $this->describeChange($field, $oldValue, $newValue, $author));
        }
    }

    /**
     * The tracker's Activity Log, newest first. Read through the same helper the write path uses, so the
     * entries this module shows are the same rows the rest of the app writes and reads.
     *
     * @return iterable<int, object>
     */
    public function notesFor(int $trackerId): iterable
    {
        return $this->activityNotes->getActivityNotes([
            'entity' => self::ENTITY,
            'entity_id' => $trackerId,
        ]);
    }

    /** @return bool false when ActivityNotes swallowed an insert failure */
    public function write(HospitalizationTrackerModel $tracker, string $notes): bool
    {
        return $this->activityNotes->setActivityNotes([
            'entity' => self::ENTITY,
            'entity_id' => $tracker->id,
            'notes' => $notes,
        ]);
    }

    /**
     * Phrased like the rest of the app's activity entries ("... updated from X to Y by Name"), with the
     * shorter "set to" wording when there was nothing there before.
     */
    private function describeChange(
        HospitalizationTrackerFieldEnum $field,
        mixed $oldValue,
        mixed $newValue,
        ?string $author
    ): string {
        $old = $field->describeValue($oldValue);
        $new = $field->describeValue($newValue);
        $by = empty($author) ? '' : ' by ' . $author;

        return $old === '(empty)'
            ? "{$field->shortLabel()} set to {$new}{$by}"
            : "{$field->shortLabel()} updated from {$old} to {$new}{$by}";
    }
}
