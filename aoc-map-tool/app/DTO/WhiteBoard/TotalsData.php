<?php

namespace App\DTO\Whiteboard;

class TotalsData
{
    // Goals
    public int $weekly_start_goal = 0;

    // Daily counts
    public int $phone_screens = 0;
    public int $clinical_interviews = 0;
    public int $offers_extended = 0;
    public int $offers_accepted = 0;
    public int $offers_rejected = 0;
    public int $hires = 0;
    public int $qualified = 0;
    public int $unqualified = 0;

    // WTD totals
    public int $wtd_phone_screens = 0;
    public int $wtd_clinical_interviews = 0;
    public int $wtd_offers_extended = 0;
    public int $wtd_offers_accepted = 0;
    public int $wtd_offers_rejected = 0;
    public int $wtd_hires = 0;
    public int $wtd_qualified = 0;
    public int $wtd_unqualified = 0;
}
