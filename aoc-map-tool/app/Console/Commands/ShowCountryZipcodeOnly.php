<?php

namespace App\Console\Commands;

use App\Location;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ShowCountryZipcodeOnly extends Command
{
    const LOG_MESSAGE = 'Deleted %s rows';

    protected $signature = 'location:country-zipcode';
    protected $description = 'Delete in location table only rows which has only country and zipcode values';

    public function handle(Location $location)
    {
        $locations = $location->getLocationWithBrokenAddressData();

        if ($locations->isEmpty()) {
            $this->info('Rows not found');

            return Command::SUCCESS;
        }

        Log::build([
            'driver' => 'single',
            'path' => storage_path('logs/location_deleted_rows.log'),
            'level' => 'info',
        ])->info('Deleted', [$locations->toArray()]);

        $location->whereIn('id', $locations->pluck('id'))->delete();
        $this->info(sprintf(self::LOG_MESSAGE, $locations->count()));

        return Command::SUCCESS;
    }
}
