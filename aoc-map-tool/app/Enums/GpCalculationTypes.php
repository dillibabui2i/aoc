<?php

namespace App\Enums;

enum GpCalculationTypes: string
{
    /**  Whole Nurse */
    case MULTIPLE_CLIENTS_SINGLE_NURSE = 'multipleClientsSingleNurse';
    /**  Whole Home */
    case SINGLE_CLIENT_MULTIPLE_NURSES = 'singleClientMultipleNurses';

    case SINGLE_CLIENT_SINGLE_NURSE = 'singleClientSingleNurse';
}
