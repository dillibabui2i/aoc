<?php

namespace App\Contracts\NationalRecruitingReport;

interface ReportTabInterface
{
    public const INTAKE_ROW_NAME = 'Intake';

    /**
     * @return array
     */
    public function getTabData(): array;
}
