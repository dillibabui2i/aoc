<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\FeatureFlags;
use App\Helpers\H;
use App\Helpers\LD;

class FeatureFlagController extends Controller
{
    //
    /**
     * Show the User list.
     */
    public function index()
    {
        return view('featureflag.grid');
    }

    /**
     * Show the User ajax list.
     */
    public function getFeatureFlagList(Request $request)
    {
        $columns = [
            0 => 'flag',
            1 => 'users',
            2 => 'flag_description',
            3 => 'ips',
            4 => 'created_by',
            5 => 'status',
        ];
        $featureFlagList = new FeatureFlags();
        $status = '';
        foreach($request['filters'] as $data){
            $status = $data['value'];
        }
        if (isset($request->search['value']) && $request->search['value'] != '') {
            $searchValue = '%' . $request->search['value'] . '%';
            $featureFlagList = $featureFlagList
            ->where(function ($query) use ($searchValue) {
                $query->where('flag', 'like', $searchValue)
                    ->orWhere('users', 'like', $searchValue)
                    ->orWhere('ips', 'like', $searchValue)
                    ->orWhere('created_by', 'like', $searchValue)
                    ->orWhere('flag_description', 'like', $searchValue);
            });
        }
        if (isset($request['order'][0]) && $request['order'][0] != '') {
            $featureFlagList = $featureFlagList->orderby($columns[intval($request['order'][0]['column'])], $request['order'][0]['dir']);
        }
        if($status != NULL){
            $featureFlagList = $featureFlagList->where('status', $status);
        }
        $total_records = $featureFlagList->get();

        if (isset($request['start']) && $request['length'] != '-1') {
            $featureFlagList = $featureFlagList->offset($request['start'])->limit($request['length']);
        }
        $statuestext = [
            '0' => 'Targeting Off',
            '1' => 'Targeting On',
            '-1' => 'Flag Disabled',
        ];
        $featureFlagList = $featureFlagList->get();
        $cnt = 0;
        foreach ($featureFlagList as $featureFlagData) {
            $featureFlagList[$cnt]['display_created'] = date('m-d-Y H:i:s', strtotime($featureFlagData['created_at']));

            $btn = '';
            $btn .= '<a href="' . url('/managefeatureflag/edit', $featureFlagData['id']) . '" class="edit"><i class="fa fa-edit" title="Edit"></i></a>';
            $featureFlagList[$cnt]['action'] = $btn;
            $status = 'Targeting Off';
            if ($featureFlagData['status'] == 1) {
                $status = 'Targeting On';
            } else if ($featureFlagData['status'] == -1) {
                $status = 'Flag Disabled';
            }
            $featureFlagList[$cnt]['disaply_status'] = "<span class='editable-select tag' data-emptytext='Click to status' data-type='select' data-source='".json_encode($statuestext)."' data-value='".$featureFlagData['status']."' data-url='".route("editable")."' data-pk='".$featureFlagData->id."' data-name='status' data-table='feature_flags'>".$status."</span>";
            $featureFlagList[$cnt]['flag_description'] = $featureFlagData['flag_description'];
            $wordWrapLimit = 50;
            if (strlen($featureFlagData['users']) > $wordWrapLimit) {
                $featureFlagList[$cnt]['users'] = wordwrap($featureFlagData['users'],$wordWrapLimit,"<br>\n",TRUE);
            }
            if (strlen($featureFlagData['ips']) > $wordWrapLimit) {
                $featureFlagList[$cnt]['ips'] = wordwrap($featureFlagData['ips'],$wordWrapLimit,"<br>\n",TRUE);
            }
            $cnt++;
        }
        $data = [
            'draw'            => intval($request['draw']),
            'recordsTotal'    => intval(count($total_records)),
            'recordsFiltered' => intval(count($total_records)),
            'data'            => $featureFlagList,
        ];

        return $data;
    }

    public function edit($id)
    {
        $featureFlagDetail =  FeatureFlags::find($id);
        return view('featureflag.form', compact('featureFlagDetail'));
    }

    public function updateFeatureFlagList(Request $request)
    {
        try {
            $updateArr = ['status' => $request->status, 'ips' => $request->ips, 'users' => $request->users];
            FeatureFlags::findOrFail($request->id)->update($updateArr);

            return redirect('/managefeatureflag')->with('success', 'Feature Flag updated!');
        } catch (\Exception $e) {
            return redirect('/managefeatureflag/edit/' . $request->id)->with('error', $e->getMessage());
        }
    }
}
