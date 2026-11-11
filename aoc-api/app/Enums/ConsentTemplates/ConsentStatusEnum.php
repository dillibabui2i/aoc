<?php

namespace App\Enums\ConsentTemplates;

enum ConsentStatusEnum: string
{
    case DRAFT = 'Draft';
    case PUBLISHED = 'Published';
    case SUPERSEDED = 'Superseded';
}
