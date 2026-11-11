<?php
use App\Helpers\H;
use App\Helpers\LD;
if(LD::checkFeatureFlag('move-common-filters-billing-dashboard')) {
    if(!LD::checkFeatureFlag('move-me-mode-filter-to-common-filters-billing-dashboard')) {
        $billingInvoiceFilter = [
            [
                'label'=>'Me Mode',
                'name'=>'billInvoices_memode',
                'type'=>'checkbox',
                'value'=>true
            ],
        ];
    }
} else {
$billingInvoiceFilter = [
    [
        'label'=>'Me Mode',
        'name'=>'billInvoices_memode',
        'type'=>'checkbox',
        'value'=>true
    ],
    [
        'label'=>'State',
        'name'=>'billInvoices_state',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
    ],
    [
        'label'=>'Region',
        'name'=>'billInvoices_region',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ],
    [
        'label'=>'Branch',
        'name'=>'billInvoices_branch',
        'type'=>'multiselect',
        'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ],
    [
        'label'=>'Payer',
        'name'=>'billInvoices_payer',
        'type'=>'multiselect',
        'source'=>H::getHelper('ConstantHelper')->getPayers()
    ],
    [
        'label' => 'Special Payer Group',
        'name' => 'billInvoices_payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ],
];
}
$billInvoicesStatus = [
  ['value' => 'Paid','text' => 'Paid'],
  ['value' => 'Cancelled','text' => 'Cancelled'],
  ['value' => 'Receivable','text' => 'Receivable']
];
$invoiceAging = [
    ['value' => '1', 'text' => '0-30'],
    ['value' => '2', 'text' => '31-60'],
    ['value' => '3', 'text' => '61-90'],
    ['value' => '4', 'text' => '91-120'],
    ['value' => '5', 'text' => '121-150'],
    ['value' => '6', 'text' => '151-180'],
    ['value' => '7', 'text' => '181-210'],
    ['value' => '8', 'text' => '210+'],
];
$billingInvoiceFilter2 = [
    [
        'label' => 'Status',
        'name' => 'billInvoices_Status',
        'type' => 'multiselect',
        'source' => $billInvoicesStatus
    ],
];
$billingInvoiceFilter[] = [
        'label' => 'Aging',
        'name' => 'aging',
        'type' => 'select',
        'source' => $invoiceAging
];
if(LD::checkFeatureFlag('manage-project-tags')){
$billingInvoiceFilter[] = [
    'label'=>'Tags',
    'name'=>'billInvoices_tag',
    'type'=>'multiselect',
    'source'=> H::getHelper('TagHelper')->getEnabledTags()
];
}
$billingInvoiceFilter[] = [
    'label' => 'Visit Date',
    'name' => 'ScheduleDate',
    'type' => 'dateRangePicker',
    'fromName' => 'fromInvoiceScheduleDate',
    'toName' => 'toInvoiceScheduleDate',
    'fromdate' => '',
    'todate' => '',
];
?>
<div class="card-header" id="dailyactivityone">
    <h4 class="mb-0">
        <button class=" col-md-9 pull-left btn btn-link btn-block text-left collapsed"
        type="button" data-toggle="collapse"
            data-target="#billing_invoices" aria-expanded="false" aria-controls="collapseOne">
            Invoices<i class="fa fa-chevron-right"></i>
        </button>
        <div class="col-md-3 text-right pull-left p-0">
            <a href="{{route('billing.invoiceShowAll')}}"
            class="dashboard-showall" title="Show All Invoices">
            Show All Invoices</a></div>
    </h4>
</div>

<div id="billing_invoices" class="collapse" aria-labelledby="dailyactivityone"
data-parent="#billing_dashboard_childs">
    <div class="filters-common">
        @component('components.datatable-filters',['filters'=>$billingInvoiceFilter,
        'formname'=>'billingInvoiceForm'])
        @endcomponent
    </div>
    <div class="card-body">
        <!---Bucket goes here--->
        @include('billing.unpaidInvoices')
        @include('billing.underPaidOverPaidInvoices')
        @if(LD::checkFeatureFlag('adjustments-bucket-invoices-widget'))
            @canPermission(['adjustments.bucket'])
                @include('billing.adjustmentsInvoices')
            @endcanPermission
        @endif
    </div>
</div>
<script>
@if(LD::checkFeatureFlag('adjustments-bucket-invoices-widget'))
const billingInvoicesBucket = ['unpaid_invoices_no_response','unpaid_invoices_denied','invoices_under_paid','invoices_over_paid','invoices_pending','invoices_adjustments'];
@else
const billingInvoicesBucket = ['unpaid_invoices_no_response','unpaid_invoices_denied','invoices_under_paid','invoices_over_paid','invoices_pending'];
@endif
var allFormNameStr = '#billingInvoiceForm';

$(document).on('submit', allFormNameStr, function(e) {
    e.preventDefault();
    for(var i=0; i < billingInvoicesBucket.length;i++) {
        var tempObject = billingInvoicesBucket[i].replace(/ /g,"_");
        if($("#reload-"+tempObject).val()==1) {
        } else {
            $("#dataTable-"+tempObject).DataTable().ajax.reload();
        }
    }
});
$(document).on("change",'#billInvoices_state',function(){
        var state = $(this).val().join(",");
        let _token = $('meta[name="csrf-token"]').attr('content');
        if (state.indexOf('TX') !== -1) {
            $('#billInvoices_region').multiselect('enable');
        } else {
            $('#billInvoices_region').multiselect('disable');
        }
        $.ajax({
        url: "{{ route('getstatebranch') }}",
        type: "GET",
        data: {
            state: state,
            _token: _token
        },
        success: function(response) {
            $('#billInvoices_branch').empty();
            $.each(response, function( index, value ) {
                $("#billInvoices_branch").append('<option value="'+value.id+'">'+value.FacilityName+'</option>')
            });
            $("#billInvoices_branch").multiselect("rebuild");
        },
    });
});

</script>