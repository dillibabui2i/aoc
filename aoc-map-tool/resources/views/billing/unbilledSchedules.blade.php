<?php
use App\Helpers\H;
use App\Helpers\LD;
$lobStatus = H::getHelper("ConstantHelper")->getLobStatus();
$lob  = [];
$cccList = H::getHelper('ConstantHelper')->getUsersByRoles(['Client Care Coordinator']);
foreach($lobStatus as $br) {
    $lob[] = ["value"=>$br->LOBID,'text'=>$br->LOBName];
}
$eChartStatusList = config('constant')['eChartStatus'];
foreach($eChartStatusList as $key => $val) {
    $eChartStatus[] = ["value"=>$key,'text'=>$val];
}

if(LD::checkFeatureFlag('move-common-filters-billing-dashboard')) {
    if(LD::checkFeatureFlag('move-me-mode-filter-to-common-filters-billing-dashboard')) {
        $billingUnbilledSchedulesFilter = [
            [
                'label'=>'Visit Date',
                'name'=>'ScheduleDate',
                'type'=>'dateRangePicker',
                'fromName'=>'fromUnbilledScheduleDate',
                'toName'=>'toUnbilledScheduleDate',
                'fromdate'=>'',
                'todate'=>'',
            ]
        ];
    } else{
        $billingUnbilledSchedulesFilter = [
            [
                'label'=>'Me Mode',
                'name'=>'unbilledschedule_memode',
                'type'=>'checkbox',
                'value'=>true
            ],
            [
                'label'=>'Visit Date',
                'name'=>'ScheduleDate',
                'type'=>'dateRangePicker',
                'fromName'=>'fromUnbilledScheduleDate',
                'toName'=>'toUnbilledScheduleDate',
                'fromdate'=>'',
                'todate'=>'',
            ]
        ];
    }
} else{
$billingUnbilledSchedulesFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'unbilledschedule_memode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'unbilledschedule_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label'=>'Region',
        'name'=>'unbilledschedule_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label'=>'Branch',
        'name'=>'unbilledschedule_branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label' => 'LOB',
        'name' => 'unbilledschedule_LOBID',
        'type' => 'multiselect',
        'source' => $lob
    ],
    [
        'label'=>'Payer',
        'name'=>'unbilledschedule_payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayersWithTherapy()
    ],[
        'label' => 'Special Payer Group',
        'name' => 'unbilledschedule_payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],
    [
        'label'=>'Visit Date',
        'name'=>'ScheduleDate',
        'type'=>'dateRangePicker',
        'fromName'=>'fromUnbilledScheduleDate',
        'toName'=>'toUnbilledScheduleDate',
        'fromdate'=>'',
        'todate'=>'',
    ]
];
}
$billingUnbilledSchedulesFilter2 = [
    [
        'label' => 'Status',
        'name' => 'unbilledschedule_Status',
        'type' => 'multiselect',
        'source' => $eChartStatus
    ],
];
if(LD::checkFeatureFlag('add-ccc-filter-not-bill-ready-bucket')){
$billingUnbilledSchedulesFilter2[] = [
        'label' => 'Client Care Coordinator',
        'name' => 'unbilledschedule_ccc',
        'type' => 'multiselect',
        'source' => $cccList,
];
}
$billReadyReason = $notBillReadyReason = [];
if(LD::checkFeatureFlag('change-outcome-reasons-unbilled-schedules-billing-dashboard')){
    $outcomes = config('constant.unbilled_reason_subreason_list');
    $billReadyReason = $outcomes['bill_ready'];
    foreach ($billReadyReason as $key => $value) {
        if (is_array($value)) {
            $billReadyReasonKey[] = $key;
        } else {
            $billReadyReasonKey[] = $value;
        }
    }
    foreach($billReadyReasonKey as $br) {
        $biling_rs[] = ["value"=>$br,'text'=>$br];
    }
    $billReadySubReason = [
    'No Authorization',
    'Incorrect Authorization',
    'Hours Added',
    'Update to Service',
    'Change in Insurance',
    'Charting Error',
    'Other'
    ];
    $billReadySubReasonArr = [];
    foreach($billReadySubReason as $bsr) {
        $billReadySubReasonArr[] = ["value"=>$bsr,'text'=>$bsr];
    }
    $billingUnbilledSchedulesFilter3[] = [
        'label' => 'Reason',
        'name' => 'billready_Reason',
        'type' => 'multiselect',
        'source' => $biling_rs
    ];
    $billingUnbilledSchedulesFilter3[] = [
        'label' => 'Sub Reason',
        'name' => 'billready_SubReason',
        'type' => 'multiselect',
        'source' => $billReadySubReasonArr,
        'disabled' => true
    ];
    $notBillReadyReason = $outcomes['bill_not_ready'];
    foreach ($notBillReadyReason as $key => $value) {
        if (is_array($value)) {
            $notBillReadyReasonKey[] = $key;
        } else {
            $notBillReadyReasonKey[] = $value;
        }
    }
    $notBillReadyRsArr = [];
    foreach($notBillReadyReasonKey as $nbr) {
        $notBillReadyRsArr[] = ["value"=>$nbr,'text'=>$nbr];
    }
    $notBillReadySubReason = [
    'Clinician Under PET',
    'Relative Caregiver PET'
    ];
    $notBillReadySubReasonArr = [];
    foreach($notBillReadySubReason as $nbsr) {
        $notBillReadySubReasonArr[] = ["value"=>$nbsr,'text'=>$nbsr];
    }
    $billingUnbilledSchedulesFilter2[] = [
        'label' => 'Reason',
        'name' => 'notbillready_Reason',
        'type' => 'multiselect',
        'source' => $notBillReadyRsArr
    ];
    $billingUnbilledSchedulesFilter2[] = [
        'label' => 'Sub Reason',
        'name' => 'notbillready_SubReason',
        'type' => 'multiselect',
        'source' => $notBillReadySubReasonArr,
        'disabled' => true
    ];
} else {
    $billreadyreason = [
    'Authorization Issue',
    'Late Notes',
    'Cancelled Claims (Internal)',
    'Cancelled Claims (External)',
    'Eligibility',
    'Other',
    ];
    $biling_rs = [];
    foreach($billreadyreason as $br) {
        $biling_rs[] = ["value"=>$br,'text'=>$br];
    }
    $billingUnbilledSchedulesFilter3 = [
        [
            'label' => 'Reason',
            'name' => 'billready_Reason',
            'type' => 'multiselect',
            'source' => $biling_rs
        ],
    ];
    $billReadySubReasonArr = [];
    $billingUnbilledSchedulesFilter3[] = [
            'label' => 'Sub Reason',
            'name' => 'billready_SubReason',
            'type' => 'multiselect',
            'source' => $billReadySubReasonArr
    ];

    $notBillReadReason = [
    'Late Note',
    'KanTime Hold/Issue',
    'Changed to Missed',
    'Authorization Issue',
    'Eligibility',
    'EVV Issue',
    'Payer Change Needed'
    ];
    $notBillReadyRsArr = [];
    foreach($notBillReadReason as $nbr) {
        $notBillReadyRsArr[] = ["value"=>$nbr,'text'=>$nbr];
    }
    $notBillReadySubReasonArr = [];
    $billingUnbilledSchedulesFilter2[] = [
            'label' => 'Reason',
            'name' => 'notbillready_Reason',
            'type' => 'multiselect',
            'source' => $notBillReadyRsArr
    ];
    $billingUnbilledSchedulesFilter2[] = [
            'label' => 'Sub Reason',
            'name' => 'notbillready_SubReason',
            'type' => 'multiselect',
            'source' => $notBillReadySubReasonArr
        ];
}
$buckets = [
    'bill_ready'=>'Bill Ready (Approved but not billed)',
    'bill_not_ready'=>'Not Bill Ready (In Progress or Completed)',];
?>

<div class="card-header" id="dailyactivityone">
    <h4 class="mb-0">
        <button class=" col-md-9 pull-left btn btn-link btn-block text-left collapsed"
        type="button" data-toggle="collapse"
            data-target="#billing_unbilled_schedules" aria-expanded="false" aria-controls="collapseOne">
            Unbilled Schedules<i class="fa fa-chevron-right"></i>
        </button>
    </h4>
</div>

<div id="billing_unbilled_schedules" class="collapse" aria-labelledby="dailyactivityone"
data-parent="#billing_dashboard_childs">
    <div class="filters-common">
        @component('components.datatable-filters',['filters'=>$billingUnbilledSchedulesFilter,
        'formname'=>'billingUnbilledSchedulesForm'])
        @endcomponent
    </div>
    <div class="card-body">
        <?php
            foreach($buckets as $bucketKey => $bucket) {?>
                <div class="card">
                    <div class="card-header" id="triage-<?php echo $bucketKey?>">
                        <h4 class="mb-0">
                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                data-toggle="collapse" data-target="#<?php echo $bucketKey?>" aria-expanded="false"
                                aria-controls="collapseOne">
                                <?php echo $bucket?>
                                <span id="<?php echo $bucketKey?>-counter"></span> <i class="fa fa-chevron-right">
                                </i>
                            </button>
                        </h4>
                    </div>
                    <div id="<?php echo $bucketKey?>" class="collapse " aria-labelledby="dailyactivityone"
                        data-parent="#billing_unbilled_schedules">
                        @if($bucketKey == 'bill_ready')
                        <p style="text-align: left;">
                            <u>
                                <b>Responsible Party:</b> Biller
                            </u><br>
                            <b>Description:</b> Schedules that are bill ready but not billed.<br>
                            <b>Timeline:</b> TBD
                        </p>
                        @elseif ($bucketKey == 'bill_not_ready')
                        <p style="text-align: left;">
                            <u>
                                <b>Responsible Party:</b> Operations
                            </u><br>
                            <b>Description:</b> Schedules that are not bill ready. <br>
                            <b>Timeline:</b> End of Day Friday
                        </p>
                        @endif
                        <div class="card-body">
                            @if($bucketKey == 'bill_not_ready')
                                @component('components.datatable-filters',['filters'=>$billingUnbilledSchedulesFilter2,
                                'formname'=>'billingUnbilledSchedulesForm2'])
                                @endcomponent
                            @elseif($bucketKey == 'bill_ready')
                                @if(LD::checkFeatureFlag('add-reason-filter-bill-ready-bucket'))
                                    @component('components.datatable-filters',['filters'=>$billingUnbilledSchedulesFilter3,
                                    'formname'=>'billingUnbilledSchedulesForm3'])
                                    @endcomponent
                                @endif
                            @endif
                            <?php echo H::getHelper("Billing")->getDataTableHtml($bucketKey,
                            '#billingUnbilledSchedulesForm')?>
                        </div>
                    </div>
                </div>
                @if($bucketKey == 'bill_not_ready')
                <script>
                    $(document).on('submit', '#billingUnbilledSchedulesForm2', function(e) {
                        e.preventDefault();
                        $("#dataTable-bill_not_ready").DataTable().ajax.reload();
                    });
                </script>
                @endif

                @if($bucketKey == 'bill_ready')
                <script>
                    $(document).on('submit', '#billingUnbilledSchedulesForm3', function(e) {
                        e.preventDefault();
                        $("#dataTable-bill_ready").DataTable().ajax.reload();
                    });
                </script>
                @endif
        <?php } ?>
    </div>
</div>
<script>
const billingUnbilledSchedulesBucket = ['bill_ready','bill_not_ready'];

$(document).on('submit', '#billingUnbilledSchedulesForm', function(e) {
    e.preventDefault();
    for(var i=0; i < billingUnbilledSchedulesBucket.length;i++) {
        var tempObject = billingUnbilledSchedulesBucket[i].replace(/ /g,"_");
        if($("#reload-"+tempObject).val()==1) {
        } else {
            $("#dataTable-"+tempObject).DataTable().ajax.reload();
        }
    }
});

$(document).on("change",'#unbilledschedule_state',function(){
        var state = $(this).val().join(",");
        let _token = $('meta[name="csrf-token"]').attr('content');
        <?php if(LD::checkFeatureFlag('region-filter-for-all-dashboard'))  { ?>
            if (state.indexOf('TX') !== -1) {
                $('#unbilledschedule_region').multiselect('enable');
            } else {
                $('#unbilledschedule_region').multiselect('disable');
            }
        <?php } ?>
        $.ajax({
        url: "{{ route('getstatebranch') }}",
        type: "GET",
        data: {
            state: state,
            _token: _token
        },
        success: function(response) {
            $('#unbilledschedule_branch').empty();
            $.each(response, function( index, value ) {
                $("#unbilledschedule_branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
            });
            $("#unbilledschedule_branch").multiselect("rebuild");
        },
    });
});

$(document).ready(function () {
    // Initialize multiselect on page load
    $('#billready_SubReason').multiselect();
    var reasons = JSON.parse('<?php echo json_encode($billReadyReason); ?>');
    $('#billready_Reason').change(function () {
        var selectedReasons = $(this).val();
        console.log("Selected Reasons:", selectedReasons);
        $('#billready_SubReason').empty();
        if (selectedReasons && selectedReasons.length > 0) {
            var subReasonList = [];
            selectedReasons.forEach(function (reason) {
                if (typeof reasons[reason] !== 'undefined') {
                    var subReasons = reasons[reason];

                    if (!Array.isArray(subReasons)) {
                        subReasons = [subReasons];
                    }
                    subReasons.forEach(function (subReason) {
                        if (!subReasonList.includes(subReason)) {
                            subReasonList.push(subReason);
                        }
                    });
                }
            });
            if (subReasonList.length > 0) {
                var subReasonHtml = "";
                subReasonList.forEach(function (subReason) {
                    subReasonHtml += '<option value="' + subReason + '">' + subReason + '</option>';
                });
                $('#billready_SubReason').html(subReasonHtml);
                $('#billready_SubReason').multiselect('rebuild');
                $('#billready_SubReason').multiselect('enable');
            }
        } else {
            $('#billready_SubReason').empty();
            $('#billready_SubReason').multiselect('rebuild');
            $('#billready_SubReason').multiselect('disable');
        }
    });
});


 </script>
