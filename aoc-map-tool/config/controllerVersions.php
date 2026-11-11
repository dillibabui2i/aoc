<?php

use App\Http\Controllers\ConsentTemplateController;
use App\Http\Controllers\TherapyWhiteBoardController;
use App\Http\Controllers\V2\NationalRecruitingReportController;
use App\Http\Controllers\V2\Map\MapAbstractController;

//// feature-flag => new controller
return [
    NationalRecruitingReportController::class => [
        'methods' => [
            'grossMargin' => ['nrr-gross-margin-report'],
            'IPEAs' => ['nrr-move-intake-coordinators'],
            'candidateStarts' => ['nrr-move-intake-coordinators'],
        ],
        'check' => 'any'
    ],
    MapAbstractController::class => [
        'methods' => [
            'showTable' => ['map-need-to-hire-v2'],
        ],
    ],
    ConsentTemplateController::class => [
        'methods' => [
            'index' => ['consent-template-tracking-main'],
            'store' => ['consent-template-tracking-main'],
            'data' => ['consent-template-tracking-main'],
        ],
    ],
    TherapyWhiteBoardController::class => [
        'methods' => [
            'index' => ['therapy-whiteboard-report'],
        ]
    ]
];
