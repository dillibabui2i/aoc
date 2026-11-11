<?php

namespace App\Console\Commands;

use App\Location;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CoordinatesModifier extends Command
{
    const THERAPY_REFERRALS_TX_TABLE = 'map79';
    const DELETED_ROWS = "%s were deleted\n";

    protected $signature = 'location:country-zipcode';
    protected $description = 'Delete in location table only rows which has only country and zipcode values';

    public function handle(Location $location): void
    {
        $location->processLocationWithBrokenAddressData($this->processLocationChunk($location));

        echo 'done';
    }

    private function processLocationChunk(Location $location): callable
    {
        return function (Collection $chunk) use ($location) {
            DB::beginTransaction();

            try {
                //TODO probably we should create models for those tables and use DI, so we can then properly mock dependencies in tests
                $therapyData = DB::table(self::THERAPY_REFERRALS_TX_TABLE)
                    ->whereIn('lat', $chunk->pluck('lat'))
                    ->whereIn('lng', $chunk->pluck('lng'))
                    ->select('id', 'lng', 'lat')
                    ->get();

                echo sprintf(self::DELETED_ROWS, $therapyData->count());

                $location->whereIn('lat', $therapyData->pluck('lat'))
                    ->whereIn('lng', $therapyData->pluck('lng'))
                    ->delete();

                DB::table(self::THERAPY_REFERRALS_TX_TABLE)
                    ->whereIn('id', $therapyData->pluck('id'))
                    ->update(['needs_geocoding' => 1]);
            } catch (Exception $exception) {
                DB::rollBack();
                Log::error('Failed to update or delete in therapy referrals transaction', [
                    'error' => $exception->getMessage(),
                    'trace' => $exception->getTraceAsString(),
                    'chunk' => $chunk->toArray(),
                ]);
            }

            DB::commit();
        };
    }
}
