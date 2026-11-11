<?php

namespace App\Enums\ConsentTemplates;

enum StatusEnum: string
{
    case DRAFT = 'Draft';
    case PUBLISHED = 'Published';
    case SUPERSEDED = 'Superseded';
}
