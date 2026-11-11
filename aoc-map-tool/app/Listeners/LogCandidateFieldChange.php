<?php

namespace App\Listeners;

use App\CustomFields;
use App\Events\CandidateFieldUpdated;
use App\Notes;
use App\User;

class LogCandidateFieldChange
{

    public function handle(CandidateFieldUpdated $event): void
    {
        $user = User::query()->find($event->userId);
        $frontendCustomFieldName = CustomFields::query()
          ->where('db_field_name', $event->field)
          ->value('field_name')
          ?? $event->field;

        $note = sprintf(
          '%s has changed %s from "%s" to "%s"',
          $user->name,
          $frontendCustomFieldName,
          $event->oldValue ?? '',
          $event->newValue ?? ''
        );

        Notes::query()->create([
          'type' => 'edit',
          'entity' => 'candidate',
          'entity_id' => $event->candidate->id,
          'notes' => $note,
          'created_by' => $user->id,
          'completed' => 1,
        ]);
    }

}
