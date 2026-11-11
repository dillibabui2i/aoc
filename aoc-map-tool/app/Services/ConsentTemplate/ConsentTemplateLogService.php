<?php

namespace App\Services\ConsentTemplate;

use App\ConsentTemplate;
use App\Models\ConsentTemplateLog;
use Carbon\Carbon;
use Illuminate\Support\Arr;

class ConsentTemplateLogService
{
    /**
     * @param ConsentTemplate $template
     * @return void
     */
    public function created(ConsentTemplate $template): void
    {
        $data = $template->getAttributes();

        $payload = [
            'updated_date' => Carbon::now()->toDateTimeString(),
            'created_by' => Arr::get($data, 'created_by'),
            'document_type' => Arr::get($data, 'type'),
            'document_version' => Arr::get($data, 'version'),
            'document_language' => Arr::get($data, 'language'),
            'action_type' => 'create',
            'updated_content' => json_encode([
                'new' => $data
            ]),
        ];

        ConsentTemplateLog::create($payload);
    }

    /**
     * @param ConsentTemplate $data
     * @return void
     */
    public function updated(ConsentTemplate $data): void
    {
        $changes = $data->getChanges();

        if (empty($changes)) {
            return;
        }

        $original = $data->getOriginal();

        $snapshot = [];

        foreach ($changes as $field => $newValue) {
            $snapshot[$field] = [
                'old' => $original[$field] ?? null,
                'new' => $newValue,
            ];
        }

        $payload = [
            'updated_date' => Carbon::now()->toDateTimeString(),
            'created_by' => $data->created_by,
            'document_type' => $data->type,
            'action_type' => 'update',
            'document_version' => $data->version,
            'document_language' => $data->language,
            'updated_content' => json_encode($snapshot),
        ];

        ConsentTemplateLog::create($payload);
    }

    /**
     * @param ConsentTemplate $template
     * @return void
     */
    public function deleted(ConsentTemplate $template): void
    {
        $data = $template->getOriginal();

        $payload = [
            'updated_date' => Carbon::now()->toDateTimeString(),
            'created_by' => Arr::get($data, 'created_by'),
            'document_type' => Arr::get($data, 'type'),
            'document_version' => Arr::get($data, 'version'),
            'document_language' => Arr::get($data, 'language'),
            'action_type' => 'delete',
            'updated_content' => json_encode([
                'old' => $data
            ]),
        ];

        ConsentTemplateLog::create($payload);
    }
}
