<?php

namespace App\Enums\ConsentTemplates;

enum ConsentTypeEnum: string
{
    case TERMS_AND_CONDITIONS = 'Terms & Conditions';
    case PRIVACY_POLICY = 'Privacy Policy';
}
