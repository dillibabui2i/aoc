<?php

namespace App\Http\Controllers;

use App\Enums\PayerRatesStatus;
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Notifications;
use App\Http\Requests\PayerRates\StorePayerRateRequest;
use App\Http\Requests\PayerRates\UpdatePayerRateRequest;
use App\Models\PaymentSource;
use App\PayerRates;
use App\Services\GpCalculator\PayerRatesService;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PayerRatesController extends Controller
{
    public function __construct(private PayerRatesService $payerRatesService)
    {}
    protected array $pendingFields = [
        'effectiveFrom',
        'effectiveThrough',
        'comment',
    ];

    public function list(Request $request)
    {
        $columns = [
            ['name' => 'id','label' => 'id', 'visible' => false, 'width' => '0'],
            ['name' => 'name', 'label' => 'Payer', 'width' => '20%'],
            ['name' => 'effectiveFrom', 'label' => 'Effective From', 'width' => '10%'],
            ['name' => 'effectiveThrough', 'label' => 'Effective Through', 'width' => '10%'],
        ];

        if (!LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
            $columns = array_merge($columns, [
                ['name' => 'hhagroupprimaryrate', 'label' => 'HHA Group Primary Rate', 'width' => '5%'],
                ['name' => 'cnagroupprimaryrate', 'label' => 'CNA Group Primary Rate', 'width' => '5%'],
                ['name' => 'lvnlpnrate', 'label' => 'LVN Rate', 'width' => '5%'],
                ['name' => 'lvnlpnspecrate', 'label' => 'LVN SP Rate', 'width' => '5%'],
                ['name' => 'rnrate', 'label' => 'RN Rate', 'width' => '5%'],
                ['name' => 'rnspecrate', 'label' => 'RN SP Rate', 'width' => '5%'],
            ]);
        }

        $columns[] = ['name' => 'comment', 'label' => 'Comment', 'width' => '20%'];

        if ($request->ajax()) {
            $search = $request->input('search.value');
            $limit = $request->input('length');
            $start = $request->input('start');
            $dir = $request->input('order.0.dir');
            $sortIndex = $request->input('order.0.column');

            if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
                $sortIndex -= 1; //Shifted by 1
            }
            $order = $columns[$sortIndex]['name'];

            $q = PayerRates::select(
                'payer_rates.id',
                DB::raw('COALESCE(payment_source.name, payer_rates.payer) as name'),
                'payer_rates.effectiveFrom',
                'payer_rates.effectiveThrough',
                'payer_rates.hhagroupprimaryrate',
                'payer_rates.cnagroupprimaryrate',
                'payer_rates.lvnlpnrate',
                'payer_rates.lvnlpnspecrate',
                'payer_rates.rnrate',
                'payer_rates.rnspecrate',
                'payer_rates.status',
                'payer_rates.comment',
                'payer_rates.pending_data',
                'payer_rates.denied_at'
            )
            ->leftJoin('payment_source', 'payer_id', '=', 'payment_source.payersource_id')
            ->where('payer_rates.is_deleted', '0')
            ->with([
                'services' => function ($query) {
                    $query
                        ->where('pending_deletion', false)
                        ->select('id', 'payer_rate_id', 'service_name', 'rate', 'pending_rate');
                }
            ]);

            $totalData = $q->count();
            $totalFiltered = $totalData;
            $filters = $request->filters;
            $payer = [];
            if(!empty($filters)){
                foreach($filters as $filter){
                    if($filter['name'] == 'payrate-payer'){
                        $payer[] = $filter['value'];
                    }
                }
            }
            if (!empty($search)) {
                $q->whereNested(function($q) use ($search) {
                    $q->where(DB::raw('COALESCE(payment_source.name, payer_rates.payer)'), 'LIKE', "%{$search}%")
                    ->orWhere('lvnlpnrate', 'LIKE', "%{$search}%")
                    ->orWhere('lvnlpnspecrate', 'LIKE', "%{$search}%")
                    ->orWhere('rnrate', 'LIKE', "%{$search}%")
                    ->orWhere('rnspecrate', 'LIKE', "%{$search}%")
                    ->orWhere('comment', 'LIKE', "%{$search}%")
                    ->orWhere(DB::raw('DATE_FORMAT(effectiveFrom, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%")
                    ->orWhere(DB::raw('DATE_FORMAT(effectiveThrough, "' . config('constant')['mysqlsearchdateformat'] . '")'), 'LIKE', "%{$search}%");
                });
                $totalFiltered = $q->count();
            }
            if(!empty($payer)){
                $q->whereIn('payment_source.name', $payer);
            }
            $data = $q->offset($start)->limit($limit)->orderBy($order, $dir)->get();

            return response()->json([
                'draw'            => intval($request->input('draw')),
                'recordsTotal'    => intval($totalData),
                'recordsFiltered' => intval($totalFiltered),
                'data'            => $this->DataProcess($data),
            ]);
        }

        $payers = PaymentSource::select('name')->distinct()->orderBy('name', 'asc')->get();
        $payer = [];
        foreach($payers as $value){
            $payer[] = ['value' => $value->name, 'text' => $value->name];
        }

        if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
            return view('payerRates.V2.grid', ['columns' => $columns, 'payer' => $payer]);
        }
        return view('payerRates.grid', ['columns' => $columns, 'payer' => $payer]);
    }

    private function DataProcess($data)
    {
        foreach ($data as $value) {
            $display = $value->pending_data ?? [];
            $value->effectiveFrom = getFormatedDate($display['effectiveFrom'] ?? $value->effectiveFrom);
            $value->effectiveThrough = getFormatedDate($display['effectiveThrough'] ?? $value->effectiveThrough);

            $value->hhagroupprimaryrate = $display['hhagroupprimaryrate'] ?? $value->hhagroupprimaryrate;
            $value->cnagroupprimaryrate = $display['cnagroupprimaryrate'] ?? $value->cnagroupprimaryrate;
            $value->lvnlpnrate = $display['lvnlpnrate'] ?? $value->lvnlpnrate;
            $value->lvnlpnspecrate = $display['lvnlpnspecrate'] ?? $value->lvnlpnspecrate;
            $value->rnrate = $display['rnrate'] ?? $value->rnrate;
            $value->rnspecrate = $display['rnspecrate'] ?? $value->rnspecrate;
            $value->comment = $display['comment'] ?? $value->comment;
        }
        return $data;
    }

    public function add()
    {
        if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
            $services = DB::table('service_codes_daas as SVM')->distinct()
                ->orderBy('name')->pluck('SVM.name')->toArray();

            return view('payerRates.V2.form', ['mode' => 'add', 'services' => $services]);
        }

        return view('payerRates.form', ['mode' => 'add']);
    }

    public function save(StorePayerRateRequest $request)
    {
        $data =  $request->validated();
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        if ($request->payer_id !== null && (int)$request->payer_id > 0) {
            // Kantime payer
            $data['payer_id'] = $request->payer_id;
            $data['payer'] = $request->payer;
            $data['is_custom'] = false;
        } else {
            // Custom payer
            $data['payer_id'] = null;
            $data['payer'] = $request->payer;
            $data['is_custom'] = true;
        }

        $createRate = PayerRates::query()->create($data);

        if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
            if (isset($data['services'])) {
                $now = now();
                foreach ($data['services'] as $service) {
                    DB::table('payer_rate_services')->updateOrInsert(
                        ['payer_rate_id' => $createRate->id, 'service_name' => $service['service_name']],
                        ['rate' => $service['rate'], 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }

        if($createRate !== null){
            $loggedInUser = H::getLoggedInUser();
            $userlist = H::getHelper('UserPermissionResolver')->getUserByPermission('get_reimbursement_notification');
            if(count($userlist) > 0){
                foreach($userlist as $user){
                    $notification = [
                        "related_item" => $createRate->id,
                        "type" => 'ReimbursementRequest',
                        "title" => "Notification of Request for Reimbursement Change",
                        "notes" => "".$loggedInUser['name']." has requested the reimbursement rate for ".$createRate->payer." be updated. Please review and approve or deny the request",
                        "user_id" => $user->id,
                    ];
                    Notifications::addNotification($notification);
                }
            }
        }
        return redirect('/payerrates')->with('success', 'Payer rates saved successfully!');
    }

    public function delete(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                PayerRates::where('id', $request->input('id'))->update(['is_deleted' => 1]);
                return response()->json(["success" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function approve(Request $request)
    {
        try {
            if ($request->input('id') != '') {
                $rateApprove = PayerRates::with('services')->find($request->input('id'));
                $loggedInUser = H::getLoggedInUser();
                $isChangeRequest = !empty($rateApprove->pending_data);

                if (!empty($rateApprove)) {
                    $data = [
                        'status' => PayerRatesStatus::APPROVED,
                        'approved_by' => Auth::id(),
                        'approved_at' => Carbon::now(),
                        'denied_by' => null,
                        'denied_at' => null,
                        'pending_data' => null,
                    ];
                    if ($rateApprove->pending_data) {
                        $pending = $rateApprove->pending_data;
                        $data = array_merge($data, [
                            'effectiveFrom' => $pending['effectiveFrom'],
                            'effectiveThrough' => $pending['effectiveThrough'],
                            'comment' => $pending['comment'],
                        ]);

                        if(!LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
                            $data = array_merge($data, [
                                'hhagroupprimaryrate' => $pending['hhagroupprimaryrate'],
                                'cnagroupprimaryrate' => $pending['cnagroupprimaryrate'],
                                'lvnlpnrate' => $pending['lvnlpnrate'],
                                'lvnlpnspecrate' => $pending['lvnlpnspecrate'],
                                'rnrate' => $pending['rnrate'],
                                'rnspecrate' => $pending['rnspecrate'],
                            ]);
                        }
                    }

                    $rateApprove->update($data);

                    if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
                        $ids = $rateApprove->services->where('pending_deletion', true)->pluck('id')->toArray();
                        foreach ($rateApprove->services as $service) {
                            if (in_array($service->id, $ids)) {
                                $service->delete();
                            } else {
                                $service->update([
                                        'rate' => $service->pending_rate ?? $service->rate,
                                        'pending_rate' => null,
                                    ]
                                );
                            }
                        }
                    }

                    $userlist = H::getHelper('UserPermissionResolver')->getUserByPermission(
                        'get_reimbursement_notification'
                    );
                    if (count($userlist) > 0) {
                        foreach ($userlist as $user) {
                            $notification = [
                                'related_item' => $request->input('id'),
                                'type' => 'ReimbursementRequest',
                                'title' => 'Notification of Approval for Reimbursement Change',
                                'notes'        => $loggedInUser['name'] .
                                    ' has approved the reimbursement rate' .
                                    ($isChangeRequest ? ' changes' : '') .
                                    ' for ' . $rateApprove->payer .
                                    '. The new rate is now active.',
                                'user_id' => $user->id,
                            ];
                            Notifications::addNotification($notification);
                        }
                    }

                    $log = Auth::user()->name . ' approved the overridden rates ' .
                        ($isChangeRequest ? 'changes' : '') .
                        ' for ' . $rateApprove->payer;
                    H::getHelper("ActivityNotes")->setActivityNotes([
                        'entity' => 'payer-rate',
                        'entity_id' => $rateApprove->id,
                        'notes' => $log,
                    ]);
                }
                return response()->json(["success" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function getPayerId(Request $request)
    {
        $term = str_replace('  ', ' ', str_replace(',', '', $request->term));

        $payerList = DB::table('payment_source')
                ->select(
                    'payment_source.payersource_id',
                    'payment_source.name'
                )
                ->where(
                    function ($query) use($term) {
                        $query->where('payment_source.name', 'like', '%' . $term . '%');
                    }
                )
                ->limit(15)
                ->get();
        $payerList = json_decode(json_encode($payerList), true);

        $payerListData = [];
        foreach ($payerList as $data) {
            $payerListData[] = [
                'id' => $data['payersource_id'],
                'label' => $data['name']
            ];
        }
        return response()->json($payerListData);
    }

    public function denyPayerRates(Request $request){
        $loggedInUser = H::getLoggedInUser();
        try {
            if ($request->input('id') != '') {
                $rateDeny = PayerRates::with('services')->find($request->input('id'));
                $notificationMessage = "%s has denied the reimbursement rate %s for %s.";

                if (!empty($rateDeny)) {
                    $isChangeRequest = !empty($rateDeny->pending_data);
                    $data = [
                        'pending_data' => null,
                    ];

                    if (!$isChangeRequest) {
                        $data = array_merge($data, [
                            'status' => PayerRatesStatus::DENIED,
                            'denied_by' => Auth::id(),
                            'denied_at' => Carbon::now(),
                        ]);
                    }

                    $rateDeny->update($data);

                    if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
                        $ids = $rateDeny->services->where('pending_deletion', true)->pluck('id')->toArray();
                        foreach ($rateDeny->services as $service) {
                            if (in_array($service->id, $ids)) {
                                $service->update(['pending_deletion' => false]);
                                continue;
                            }

                            if (empty($service->rate)) {
                                $service->delete();
                                continue;
                            }

                            if (!empty($service->pending_rate)) {
                                $service->update([
                                    'pending_rate' => null,
                                ]);
                            }
                        }
                    }

                    $userlist = H::getHelper('UserPermissionResolver')->getUserByPermission(
                        'get_reimbursement_notification'
                    );
                    if (count($userlist) > 0) {
                        foreach ($userlist as $user) {
                            $notification = [
                                'related_item' => $rateDeny->id,
                                'type' => 'ReimbursementRequest',
                                'title' => 'Notification of Denial for Reimbursement Change',
                                'notes' => sprintf($notificationMessage,
                                    $loggedInUser['name'],
                                    $isChangeRequest ? 'changes' : '',
                                    $rateDeny->payer),
                                'user_id' => $user->id,
                            ];
                            Notifications::addNotification($notification);
                        }
                    }
                    $log = Auth::user()->name . ' denied the overridden rates ' .
                        ($isChangeRequest ? 'changes' : '') .
                        ' for ' . $rateDeny->payer;

                    H::getHelper("ActivityNotes")->setActivityNotes([
                        'entity' => 'payer-rate',
                        'entity_id' => $rateDeny->id,
                        'notes' => $log,
                    ]);
                }
                return response()->json(["success" => true]);
            } else {
                return response()->json(['status' => false]);
            }
        }catch(\Exception $e){
            return response()->json(['status' => false]);
        }
    }

    public function edit(Request $request, $id): View
    {
        if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
            $services = DB::table('service_codes_daas as SVM')->distinct()
                ->orderBy('name')->pluck('SVM.name')->toArray();
        }

        $payerRates = PayerRates::query()->with([
            'services:id,payer_rate_id,service_name,rate',
        ])->find($id);

        foreach ($this->pendingFields as $field) {
            if (isset($payerRates->pending_data[$field])) {
                $payerRates->{$field} = $payerRates->pending_data[$field];
            }
        }

        $logs = H::getHelper('ActivityNotes')
            ->getActivityNotes(['entity' => 'payer-rate', 'entity_id' => $id]);

        if(LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
            return view('payerRates.V2.form', [
                'mode' => 'edit',
                'payerRates' => $payerRates,
                'logData' => array_reverse(is_array($logs) ? $logs : $logs->toArray()),
                'services' => $services,
            ]);
        }
        return view('payerRates.form', [
            'mode' => 'edit',
            'payerRates' => $payerRates,
            'logData' => array_reverse(is_array($logs) ? $logs : $logs->toArray()),
        ]);
    }

    public function update(UpdatePayerRateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $pendingFields = $this->pendingFields;
        if (!LD::checkFeatureFlag('add-scalable-service-manage-payer-rates')) {
            $pendingFields = array_merge($pendingFields, [
                'hhagroupprimaryrate',
                'cnagroupprimaryrate',
                'lvnlpnrate',
                'lvnlpnspecrate',
                'rnrate',
                'rnspecrate',
            ]);
        }
        $this->payerRatesService->update($data, $pendingFields);

        return redirect('/payerrates')->with('success', 'Changes saved as pending approval!');
    }
}
