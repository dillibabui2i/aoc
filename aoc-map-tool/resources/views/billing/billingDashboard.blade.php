<?php
use App\Helpers\H;
use App\Helpers\LD;
if(LD::checkFeatureFlag('move-common-filters-billing-dashboard')) {
    $lobStatus = H::getHelper("ConstantHelper")->getLobStatus();
    $lob  = [];
    foreach($lobStatus as $br) {
        $lob[] = ["value"=>$br->LOBID,'text'=>$br->LOBName];
    }
    $billingMainFilter = [
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
            'label' => 'Discipline',
            'name' => 'unbilledschedule_Discipline',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getDiscipline()
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
        ]
    ];
}
if(LD::checkFeatureFlag('move-me-mode-filter-to-common-filters-billing-dashboard')) {
    $billingMeModeFilter = [
        [
            'label'=>'Me Mode',
            'name'=>'unbilledschedule_memode',
            'type'=>'checkbox',
            'value'=>true
        ]
    ];
    $billingMainFilter = array_merge($billingMeModeFilter, $billingMainFilter);
}
?>
<div class="card" data-follows-global="true">
    <div class="card-header" id="billing_dashboard_manager">
        <h2 class="mb-0">
            <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed"
            type="button" data-toggle="collapse"
                data-target="#billing_dashboard_childs" aria-expanded="false" aria-controls="collapseThree">
                Billing
                @include('account.partials.globalFilterBadge')
                <i class="fa fa-chevron-right"></i>
            </button>

        </h2>

    </div>
    <div id="billing_dashboard_childs" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
        <div class="card-body">
            <div
                class="filters-common"
                data-parent="#leadership_childs"
                data-bucket-filter="true"
                data-global-filter-fields='{"state":"unbilledschedule_state","region":"unbilledschedule_region","branch":"unbilledschedule_branch"}'
            >
                @if(LD::checkFeatureFlag('move-common-filters-billing-dashboard'))
                    @component('components.datatable-filters',['filters'=>$billingMainFilter,'formname'=>'billingMainForm'])
                    @endcomponent
                @endif
            </div>
            <div class="card">
                <!---Widget goes here--->
                @include('billing.unbilledSchedules')
                @if(LD::checkFeatureFlag('invoices-widget-billing-dashboard'))
                    @include('billing.invoices')
                @endif
            </div>
        </div>
    </div>
</div>
@if(LD::checkFeatureFlag('move-common-filters-billing-dashboard'))
<script>
    const billingAllBuckets = ['bill_ready','bill_not_ready','unpaid_invoices_no_response','unpaid_invoices_denied','invoices_under_paid','invoices_over_paid','invoices_pending'];
    @if(LD::checkFeatureFlag('adjustments-bucket-invoices-widget'))
        billingAllBuckets.push('invoices_adjustments');
    @endif
     $(document).on('submit', '#billingMainForm', function(e) {
         e.preventDefault();
         for(var i=0; i < billingAllBuckets.length;i++) {
             var tempObject = billingAllBuckets[i].replace(/ /g,"_");
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
</script>
@endif
