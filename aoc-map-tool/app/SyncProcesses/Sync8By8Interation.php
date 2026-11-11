<?php

namespace App\SyncProcesses;

use App\Helpers\H;
use App\Calllegs;
use Illuminate\Support\Facades\DB;

class Sync8By8Interation
{
    public function callLegs()
    {
        try {
            $syncType = '8by8_calllegs';
            $batch_status = H::getHelper('Sync')->startBatch($syncType);
            if ($batch_status == -1) {
                echo 'stop process';
                exit;
            }
            H::ExcelLog("Sync Process Start",'8by8_calllegs.log');
            $batch_info = H::getHelper('Sync')->get_batch_information($syncType);
            $last_updated_date = $batch_info['last_updated_date'];
            $limit = $batch_info['batch_size'];
            $this->processCallLegs($last_updated_date, $limit, $syncType);
            H::getHelper('Sync')->stopBatch($syncType);
            H::ExcelLog("Sync Process Stop",'8by8_calllegs.log');
        } catch (\Exception $e) {
            H::ExcelLog("Error : " . $e->getMessage(),'8by8_calllegs.log');
            H::getHelper('Sync')->stopBatch($syncType);
        }
    }

    protected function processCallLegs($last_updated_date, $limit, $syncType, $scrollId = '')
    {
        $fromDate = (string) \Carbon\Carbon::parse($last_updated_date)->format('Y-m-d') . ' 00:00:00';
        $endDate = (string) \Carbon\Carbon::parse($last_updated_date)->addHour()->format('Y-m-d') . ' 23:59:59';
        H::ExcelLog("From Date : " . $fromDate,'8by8_calllegs.log');
        H::ExcelLog("End Date : " . $endDate,'8by8_calllegs.log');
        $client = new \GuzzleHttp\Client();
        $response = $client->request('POST', env('8BY8_URL') . '/v1/oauth/token', [
            'form_params' => [
                'username' => env('8BY8_USERNAME'),
                'password' => env('8BY8_PASSWORD')
            ],
            'headers' => [
                '8x8-apikey' => env('8BY8_KEY'),
                'accept' => 'application/json',
                'content-type' => 'application/x-www-form-urlencoded',
            ]
        ]);

        $authResponse = json_decode($response->getBody(), true);
        $respTokn = 'Bearer '.$authResponse['access_token'];
        H::ExcelLog("Get Token",'8by8_calllegs.log');
        $params = [
            'pbxId' => env('8BY8_PBXID'),
            'startTime' => $fromDate,
            'endTime' => $endDate,
            'timeZone' => env('8BY8_TIMEZONE'),
            'pageSize' => $limit,
            'isSimplified' => 'false',
        ];
        if (!empty($scrollId)) {
            $params['scrollId'] = $scrollId;
        }
        $respTokn = 'Bearer '.$authResponse['access_token'];
        $endPoint = env('8BY8_URL') . '/v2/call-legs?' . $this->buidQuery($params);
		H::ExcelLog("EndPoint : " . $endPoint,'8by8_calllegs.log');
        $callResponse = $client->request('GET', $endPoint , [
            'headers' => [
                '8x8-apikey' => env('8BY8_KEY'),
                'accept' => 'application/json',
                'authorization' => $respTokn
            ],
        ]);
        
        $callListResponse = json_decode($callResponse->getBody(), true);
        if ($callListResponse['meta']['totalRecordCount'] > 0) {
            foreach ($callListResponse['data'] as $key => $data) {
                $isRecordExists = Calllegs::where('callId', $data['callId'])
                    ->where('legId', $data['legId'])
                    ->get()
                    ->count();
                if (!$isRecordExists) {
                    $data['user_id'] = 1;
                    $data['created_at'] = \Carbon\Carbon::now()->format('Y-m-d');
                    if (is_array($data['departments'])) {
                        $data['departments'] = implode(",", $data['departments']);
                    }
                    if (is_array($data['branches'])) {
                        $data['branches'] = implode(",", $data['branches']);
                    }
                    Calllegs::insert($data);
                    H::ExcelLog("Record inserted. callId: " . $data['callId'],'8by8_calllegs.log');
                } else {
                    H::ExcelLog("Record already exists so skip the record. callId: " . $data['callId'],'8by8_calllegs.log');
                }
            }
            sleep(15);
            $this->processCallLegs($last_updated_date, $limit, $syncType, $callListResponse['meta']['scrollId']);
        } else {
            if (
                strtotime(\Carbon\Carbon::parse($last_updated_date)->format('Y-m-d')) <=
                strtotime(\Carbon\Carbon::now()->format('Y-m-d'))
            ) {
                $nextDate = \Carbon\Carbon::parse($last_updated_date)->addDay()->format('Y-m-d');
                H::getHelper('Sync')->update_last_updated($syncType, $nextDate, 0);
            }
        }
    }

    protected function buidQuery($params)
    {
        $paramsJoined = [];
        foreach($params as $param => $value) {
            $paramsJoined[] = "$param=$value";
        }
        return implode('&', $paramsJoined);
    }
}
