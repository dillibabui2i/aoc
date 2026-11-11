<?php

namespace App\Enums;

enum JobCategoryAction: string
{
    case CREATED = 'created';
    case UPDATED = 'updated';
    case ENABLED = 'enabled';
    case DISABLED = 'disabled';
}
