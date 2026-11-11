<?php

use App\Enums\ConsentTemplates\StatusEnum;
use App\Enums\ConsentTemplates\TypeEnum;
use App\Services\ConsentTemplate\ConsentTrackingService;

return [
    'title' => 'Consent Tracking Templates',
    'columns' => [
        ['name' => 'id', 'label' => 'id', 'visible' => false, 'width' => '0'],
        ['name' => 'type', 'label' => 'Type', 'visible' => false, 'width' => '0'],
        ['name' => 'version', 'label' => 'Version', 'width' => '15%'],
        ['name' => 'status', 'label' => 'Status', 'width' => '15%'],
        ['name' => 'language', 'label' => 'Language', 'width' => '20%'],
        ['name' => 'createdBy', 'label' => 'Author', 'width' => '20%'],
        ['name' => 'created_at', 'label' => 'Created Date', 'width' => '15%'],
        ['name' => 'updated_at', 'label' => 'Last Updated Date ', 'width' => '15%'],
        ['name' => 'actions', 'label' => 'Actions', 'width' => '15%'],
    ],
    'filters' => [
        [
            'label' => 'Version',
            'name' => 'consent_version',
            'type' => 'multiselect',
            'source' => [],
        ],
        [
            'label' => 'Type',
            'name' => 'consent_type',
            'type' => 'multiselect',
            'source' => [
                [
                    'text' => TypeEnum::TERMS_AND_CONDITIONS->value,
                    'value' => TypeEnum::TERMS_AND_CONDITIONS->value
                ],
                [
                    'text' => TypeEnum::PRIVACY_POLICY->value,
                    'value' => TypeEnum::PRIVACY_POLICY->value,
                ]
            ],
        ],
        [
            'label' => 'Status',
            'name' => 'consent_status',
            'type' => 'multiselect',
            'source' => [
                [
                    'text' => StatusEnum::DRAFT->value,
                    'value' => StatusEnum::DRAFT->value,
                ], [
                    'text' => StatusEnum::PUBLISHED->value,
                    'value' => StatusEnum::PUBLISHED->value,
                ], [
                    'text' => StatusEnum::SUPERSEDED->value,
                    'value' => StatusEnum::SUPERSEDED->value,
                ],
            ]
        ]
    ],
    'types' => [
        TypeEnum::PRIVACY_POLICY->value,
        TypeEnum::TERMS_AND_CONDITIONS->value,
    ],
    'language' => [
        'en' => 'English',
        'es' => 'Spanish',
    ]
];
