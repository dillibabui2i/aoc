<?php

namespace App\Mail\VBC;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CdcVaccineGroupsNeedAocNameMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    /**
     * @param array<int, array{cvx_group_code: int, cdc_name: string|null}> $groups
     */
    public function __construct(public array $groups)
    {
    }

    public function build(): self
    {
        return $this->subject('CDC vaccine groups require an AOC name')
            ->view('email.cdc-vaccine-groups-need-aoc-name');
    }
}
