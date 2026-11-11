<?php

namespace App\Enums;

//Line of business
enum LOB: int
{
    case PDN = 1002;
    case PT = 1003;
    case OT = 1004;
    case SKILLED_VISITS = 1005;
    case ST = 1006;
    case ATTENDANT_CARE = 1007;
    case COLORADO = 1023;
    case ARIZONA_ADULT_OASIS = 1028;
    case IHSS = 1029;
    case CNA = 1030;
    case PPEC = 1031;
    case PCS = 1034;
    case ABA = 1041;
    case HHA = 1042;
    case LHA = 1043;
    case RESPITE_NURSING = 1111;
    case RESPITE_ATTENDANT = 2222;
}
