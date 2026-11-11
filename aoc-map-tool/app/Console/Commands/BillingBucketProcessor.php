<?php

namespace App\Console\Commands;

use App\Helpers\Billing;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class BillingBucketProcessor extends Command
{
    private const WARNING_THRESHOLD = 20;

    protected $signature = 'saver:billing-buckets';
    protected $description = 'In random moment billing related can become empty, there is no understanding why. This command will be fixing it automatically.';

    //TODO temporary job, should be deleted in scope of AOC-1092
    public function handle(Billing $billingHelper): int
    {
        $showCount = 0;
        $request = $this->getRequest();

        $combineUnbilledSchedulesData = $billingHelper->getCombineUnbilledSchedulesData(
            $request->input('type'),
            $request->input('filters'),
            $showCount,
            $request
        );

        if ($combineUnbilledSchedulesData['totalData'] < self::WARNING_THRESHOLD) {
            Artisan::call('set:billingbucketgroup');
        }

        return Command::SUCCESS;
    }

    private function getRequest(): Request
    {
        $requestData = [
            'draw' => '1',
            'columns' => [
                [
                    'data' => null,
                    'name' => null,
                    'searchable' => 'true',
                    'orderable' => 'false',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'id',
                    'name' => 'id',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'FacilityName',
                    'name' => 'FacilityName',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'clientName',
                    'name' => 'clientName',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'LOBName',
                    'name' => 'LOBName',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'ScheduleDate',
                    'name' => 'ScheduleDate',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'DaysPastDOS',
                    'name' => 'DaysPastDOS',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'Hours',
                    'name' => 'Hours',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'Contractualamount',
                    'name' => 'Contractualamount',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'Payer',
                    'name' => 'Payer',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'Notes',
                    'name' => 'Notes',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => 'billingNotes',
                    'name' => 'billingNotes',
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
                [
                    'data' => null,
                    'name' => null,
                    'searchable' => 'true',
                    'orderable' => 'true',
                    'search' => [
                        'value' => null,
                        'regex' => 'false',
                    ],
                ],
            ],
            'order' => [
                [
                    'column' => '6',
                    'dir' => 'desc',
                ],
            ],
            'start' => '0',
            'length' => '10',
            'search' => [
                'value' => null,
                'regex' => 'false',
            ],
            'filters' => [
                [
                    'name' => 'fromUnbilledScheduleDate',
                    'value' => null,
                ],
                [
                    'name' => 'toUnbilledScheduleDate',
                    'value' => null,
                ],
            ],
            'type' => 'bill_ready',
        ];

        return new Request($requestData);
    }
}
