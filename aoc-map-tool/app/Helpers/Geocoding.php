<?php

namespace App\Helpers;

use App\Location;

class Geocoding
{
    public static function processAddress($address, $mode)
    {
        //look for address in Location table
        //mode = api - try to geocode from API if not found
        //mode <> api - queue to code later with needs_geocoding = 1
        $return = [];
        $latlong = self::getLatLngForAddress($address);
        if (empty($latlong)) {
            if ($mode == 'api') {
                //geocode from API
                H::ExcelLog('searching for: ' . print_r($address, true), 'locationtable.log');
                $latlong = self::getGeoTomTom(implode(',', $address), 0); //census api
                if ($latlong) {
                    //save address to location table
                    $address['lat'] = $latlong['lat'];
                    $address['lng'] = $latlong['lng'];
                    $return['result'] = 'added';
                    H::ExcelLog('adding: ' . print_r($address, true), 'locationtable.log');
                    self::saveAddressData($address);
                    $return['needs_geocoding'] = 0;
                } else {
                    $return['result'] = 'notfound';
                }
            } else {
                $return['needs_geocoding'] = 1;
            }
        } else {
            $return['result'] = 'found';
            $return['needs_geocoding'] = 0;
        }
        if ($latlong) {
            $return['lat'] = $latlong['lat'];
            $return['lng'] = $latlong['lng'];
        }

        return $return;
    }

    public static function getLatLngForAddress($mapAdress)
    {
        $latlong = [];
        if (!empty($mapAdress)) {
            $latLongDetails = Location::select('lat', 'lng');
            $mapquery = '';
            foreach ($mapAdress as $key => $value) {
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
            $latLongDetails = Location::select('lat', 'lng');
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
            $latLongDetails = $latLongDetails->first();
            if (!empty($latLongDetails)) {
                $latlong['lat'] = $latLongDetails->lat;
                $latlong['lng'] = $latLongDetails->lng;
            }
        }

        return $latlong;
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
            Location::create($locationAddress);
        } catch (\Exception $e) {
            logger('--saveAddressData-' . $e->getMessage());
        }
    }

    public static function getGeoTomTom($address, $usedfor = 1)
    {
        //US census API
        if ($usedfor == 1) {
            H::ExcelLog($address, 'tomtom-' . date('Y-m-d') . '.log');
        }
        try {
            $urlencoded = urlencode($address);
            $url = 'https://api.tomtom.com/search/2/geocode/' . $urlencoded . '.json?key='.env('TOMTOM_KEY').'&limit=1&countrySet=US';
            $latLongData = H::getCurl($url);

            if (isset($latLongData['errors'])) {
                throw new \Exception(implode(',', $latLongData['errors']));
            }
            $latLongFinalData = [];

            if (isset($latLongData['results'][0]['matchConfidence']['score']) && ($latLongData['results'][0]['matchConfidence']['score'] > .2)) {
                $latLongFinalData['lng'] = $latLongData['results'][0]['position']['lon'];
                $latLongFinalData['lat'] = $latLongData['results'][0]['position']['lat'];

                return $latLongFinalData;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            H::ExcelLog('Address Of  : ' . $address . ' Error:  ' . $e->getMessage(), 'getGeoLatLong.log');

            return false;
        }
    }

    public static function getGeoLatLong($address, $usedfor = 1)
    {
        //US census API
        try {
            $latLongArray1 = 'https://geocoding.geo.census.gov/geocoder/locations/onelineaddress?address=' . urlencode($address) . '&benchmark=2020&format=json';
            $latLongArray = H::getCurl($latLongArray1);
            if (isset($latLongArray['errors'])) {
                throw new \Exception(implode(',', $latLongArray['errors']));
            }
            $latLongData = json_decode($latLongArray, true);
            $latLongFinalData = [];
            if (isset($latLongData['result']['addressMatches']) && !empty($latLongData['result']['addressMatches'])) {
                $latLongFinalData['lng'] = $latLongData['result']['addressMatches'][0]['coordinates']['x'];
                $latLongFinalData['lat'] = $latLongData['result']['addressMatches'][0]['coordinates']['y'];

                return $latLongFinalData;
            } else {
                //try google API
                $latLongArray = H::getCurl(env('GOOGLE_API_URL') . '?address=' . urlencode($address) . '&key=' . env('GOOGLE_API_KEY'));
                if (isset($latLongArray['results'][0])) {
                    if ($usedfor == 2) {
                        return $latLongArray['results'][0];
                    }

                    return $latLongArray['results'][0]['geometry']['location'];
                } else {
                    return false;
                }
            }
        } catch (\Exception $e) {
            H::ExcelLog('Address Of :  ' . $address . ' Error:   ' . $e->getMessage(), 'getGeoLatLong.log');

            return false;
        }
    }

    public static function getRouteDistance($from, $to)
    {
        try {
            if (!empty($from) && !empty($to)) {
                $from = is_array($from) ? implode(',', $from) : $from;
                $to = is_array($to) ? implode(',', $to) : $to;
                $latLongSting = $from . ':' . $to;
                $url = 'https://api.tomtom.com/routing/1/calculateRoute/' . $latLongSting . '/json?instructionsType=text&language=en-US&vehicleHeading=90&sectionType=traffic&report=effectiveSettings&routeType=eco&traffic=true&avoid=unpavedRoads&travelMode=car&vehicleMaxSpeed=120&vehicleCommercial=false&vehicleEngineType=combustion&key='.env('TOMTOM_KEY').'&computeTravelTimeFor=all&routeRepresentation=summaryOnly';
                $latLongData = H::getCurl($url);

                return $latLongData;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            H::ExcelLog('Function:getRouteDistance File:Geocoding Error: ' . $e->getMessage(), 'getGeoLatLong.log');

            return false;
        }
    }

    public static function getGeoGoogle($address)
    {
        H::ExcelLog($address, 'getGeoGoogle.log');
        try {
            $latLongArray = H::getCurl(env('GOOGLE_API_URL') . '?address=' . urlencode($address) . '&key=' . env('GOOGLE_API_KEY'));
            if (isset($latLongArray['results'][0])) {
                H::ExcelLog(print_r($latLongArray['results'][0]['geometry']['location'],true), 'getGeoGoogle.log');
                return $latLongArray['results'][0]['geometry']['location'];
            } else {
                return false;
            }
        } catch (\Exception $e) {
            H::ExcelLog('Address Of   : ' . $address . '  Error:  ' . $e->getMessage(), 'getGeoGoogle.log');
            return false;
        }
    }

    public function getNthHub($start,$limit)
    {
        $nthHubList = \DB::table('NeedToHire_Hub')->whereRaw('(MilesFromHub IS NULL)')->offset($start)->limit($limit)->orderBy('id','ASC')->get()->toArray();
        $nthHubList = H::objToArr($nthHubList);
        if(count($nthHubList) >0) {
            foreach($nthHubList as $nthHubData) {
                try {
                    $cityLocationAddress['city'] = $nthHubData['City'];
                    $cityLocationAddress['state'] = $nthHubData['ShortState'];
                    $cityLocationAddress['country'] = 'USA';
                    $citylatlong = Geocoding::getGeoGoogle(implode(',', $cityLocationAddress));

                    H::ExcelLog('citylatlong Lat=' . $citylatlong['lat'] . ' And Lng=' . $citylatlong['lng'], 'getNthHub.log');
                    $hubLocationAddress['city'] = $nthHubData['HUB'];
                    $hubLocationAddress['state'] = $nthHubData['ShortState'];
                    $hubLocationAddress['country'] = 'USA';
                    $hublatlong = Geocoding::getGeoGoogle(implode(',', $hubLocationAddress));
                    H::ExcelLog('hublatlong Lat=' . $hublatlong['lat'] . ' And Lng=' . $hublatlong['lng'], 'getNthHub.log');

                    $distance = self::haversineDistance($citylatlong['lat'], $citylatlong['lng'], $hublatlong['lat'], $hublatlong['lng']);
                    H::ExcelLog('distance=' . $distance, 'getNthHub.log');
                    \DB::table('NeedToHire_Hub')->where('id',$nthHubData['id'])->update(['MilesFromHub' => $distance]);
                    echo "Process=".$nthHubData['id']."\n";
                } catch (\Exception $e) {
                    H::ExcelLog(' Error: ' . $e->getMessage(), 'getNthHub.log');
                }
            }
        }
    }
    public static function haversineDistance($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 3958.8; // Earth's radius in miles
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;
        return round($distance,2);
      }
}
