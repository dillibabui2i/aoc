<?php

namespace App;

use App\Helpers\H;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    //
    protected $table = 'location';
    public $timestamps = false;

    protected $fillable = [
        'address1',
        'address2',
        'address3',
        'city',
        'state',
        'country',
        'zipcode',
        'lat',
        'lng',
        'location_search',
    ];

    public static function getLatLngForAddress($mapAdress)
    {
        //echo "mapAdress11<pre>";print_r($mapAdress);exit;
        $latlong = [];
        if (!empty($mapAdress)) {
            $latLongDetails = self::select('lat', 'lng');
            $mapquery = '';
            foreach ($mapAdress as $key => $value) {
                echo $key . ' ';
                var_dump($value);
                echo "\n";
                $latLongDetails = $latLongDetails->where($key, $value);
            }
            $latLongDetails = $latLongDetails->first();
            if (!empty($latLongDetails)) {
                $latlong['lat'] = $latLongDetails->lat;
                $latlong['lng'] = $latLongDetails->lng;
            }
        }

        return $latlong;
    }

    /**
     * New function to replace getLatLngForAddress.
     */
    public static function getLatLngForAddress2($mapAdress)
    {
        $latlong = [];
        if (!empty($mapAdress)) {
            $latLongDetails = self::select('lat', 'lng');
            $locationAddressStr = '';
            foreach ($mapAdress as $lk => $lv) {
                echo $lk . "\n";
                var_dump($lv);
                echo "\n";
                if ($lk != 'lat' && $lk != 'lng') {
                    if ($lv != '') {
                        $locationAddressStr = $locationAddressStr . ' ' . $lv;
                    }
                }
            }
            echo 'in 2' . "\n";
            var_dump($locationAddressStr);
            echo "\n";
            $searchVal = H::removeSpecialChar($locationAddressStr);
            echo 'search value: ' . "\n";
            var_dump($searchVal);
            echo "\n";
            $searchVal = trim(strtolower($searchVal));
            $latLongDetails = $latLongDetails->where('location_search', $searchVal);
            //$latLongDetails = $latLongDetails->whereRaw('trim(Lower(`location_search`)) = ? ',[trim(strtolower($searchVal))]);
            $latLongDetails = $latLongDetails->first();
            if (!empty($latLongDetails)) {
                $latlong['lat'] = $latLongDetails->lat;
                $latlong['lng'] = $latLongDetails->lng;
            }
        }

        return $latlong;
    }

    public static function searchLocationData($request)
    {
        $latLongDetails = [];
        $search_keywords = $request->search;
        $search_keywords = H::removeSpecialChar($search_keywords);
        $latLongDetails = self::select('*');
        $latLongDetails = $latLongDetails->whereRaw(" location_search LIKE ?", ["%" . $search_keywords . "%"]);
        $latLongDetails = $latLongDetails->get();

        return $latLongDetails;
    }

    public static function saveAddressData($locationAddress)
    {
        $locationAddressStr = '';
        try {
            foreach ($locationAddress as $lk => $lv) {
                if ($lk != 'lat' && $lk != 'lng') {
                    if ($lv != '') {
                        $locationAddressStr = $locationAddressStr . ' ' . $lv;
                    }
                }
            }
            $locationAddressStr = trim(strtolower($locationAddressStr));
            $locationAddress['location_search'] = H::removeSpecialChar($locationAddressStr);
            self::create($locationAddress);
        } catch (\Exception $e) {
            logger('--saveAddressData-' . $e->getMessage());
        }
    }

    public function processLocationWithBrokenAddressData(callable $callback): void
    {
        $this->query()
            ->where(function (Builder $q) {
                $q->whereNotNull('country')
                    ->whereRaw("TRIM(country) != ''");
            })
            ->whereNotNull('zipcode')
            ->where(function (Builder $q) {
                $q->whereRaw("TRIM(address1) = '' OR address1 IS NULL")
                    ->orWhereRaw("TRIM(address2) = '' OR address2 IS NULL")
                    ->orWhereRaw("TRIM(address3) = '' OR address3 IS NULL")
                    ->orWhereRaw("TRIM(city) = '' OR city IS NULL")
                    ->orWhereRaw("TRIM(state) = '' OR state IS NULL");
            })->chunk(1000, $callback);
    }
}
