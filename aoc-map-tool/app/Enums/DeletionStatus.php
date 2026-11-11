<?php

namespace App\Enums;

enum DeletionStatus: int
{
    case ACTIVE = 0;
    case DELETED = 1;
}
