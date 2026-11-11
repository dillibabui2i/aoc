<?php

namespace App\Enums;

enum AuthSourceEnum: string
{
    case SITE = 'site';
    case MOBILE = 'mobile';
}