<?php

namespace App\Observers;

use App\ConsentTemplate;
use App\Enums\ConsentTemplates\StatusEnum;
use App\Services\ConsentTemplate\ConsentTemplateLogService;

class ConsentTrackingObserver
{
    /**
     * @param ConsentTemplateLogService $logService
     */
    public function __construct(protected ConsentTemplateLogService $logService)
    {
    }

    /**
     * @param ConsentTemplate $template
     * @return void
     */
    public function created(ConsentTemplate $template): void
    {
        if ($template->status === StatusEnum::PUBLISHED->value) {
            $template->supersedeOtherPublished();
        }

        $this->logService->created($template);
    }

    /**
     * @param ConsentTemplate $template
     * @return void
     */
    public function updated(ConsentTemplate $template): void
    {
        if ($template->status === StatusEnum::PUBLISHED->value) {
            $template->supersedeOtherPublished();
        }

        $this->logService->updated($template);
    }

    /**
     * @param ConsentTemplate $template
     * @return void
     */
    public function deleted(ConsentTemplate $template): void
    {
        $this->logService->deleted($template);
    }
}
