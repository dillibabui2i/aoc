<?php

namespace App\DTO\NationalRecruitingReport;

class TableParams
{
    /**
     * @param string|null $tab
     * @param string|null $isajax
     * @param string|null $period
     * @param string|null $source
     * @param string|null $view
     * @param array $search
     * @param array $date
     */
    public function __construct(
        public ?string $tab = null,
        public ?string $isajax = null,
        public ?string $period = null,
        public ?string $source = null,
        public ?string $view = null,
        public array $search = [],
        public array $date = [],
    ) {}
}
