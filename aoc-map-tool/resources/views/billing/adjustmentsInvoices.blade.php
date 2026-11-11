<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\Services\Adjustment\AdjustmentService;
use App\Services\Adjustment\AdjustmentHistoryService;

$buckets = [
    'invoices_adjustments'=>'Adjustments',
    ];

$assignedToList = H::getHelper('Billing')->getAllAssignedTo();
if (LD::checkFeatureFlag('update-adjustment-workflow')) {
    $assignedToList = app(AdjustmentHistoryService::class)->getAllAssignedTo();
}

$assignedTo = [];
foreach ($assignedToList as $assignedToData) {
    $value = $assignedToData->assigned_to;

    if (LD::checkFeatureFlag('update-adjustment-workflow')) {
        $value = $assignedToData->assignee_type . ':' . $assignedToData->assigned_to;
    }
    $assignedTo[] = [
        'value' => $value,
        'text'=> $assignedToData->name
    ];
}

if (LD::checkFeatureFlag('update-adjustment-workflow')) {
    $assignedTo[] = [
        'value' => 'Unassigned',
        'text'  => 'Unassigned',
    ];
}

if (!LD::checkFeatureFlag('update-adjustment-workflow')) {
    $billingCategoryArr = (LD::checkFeatureFlag('update-write-off-request-options')) ? config('constant.billingUpdatedOutcomeCategory') : config('constant.billingOutcomeCategory');
    foreach ($billingCategoryArr as $billingCategoryData) {
        $billingCategory[] = ['value' => $billingCategoryData, 'text' => $billingCategoryData];
    }
}
$billingCategories = app(AdjustmentService::class)->getAvailableCategories();

$billingCategories = array_map(
    fn ($text, $value) => [
        'value' => $value,
        'text' => $text,
    ],
    $billingCategories,
    array_keys($billingCategories)
);

$billingInvoiceFilter3 = [
    [
        'label' => 'Assigned To',
        'name' => 'assigned_to',
        'type' => 'multiselect',
        'source' => $assignedTo
    ],
    [
        'label' => 'Category',
        'name' => 'category',
        'type' => 'multiselect',
        'source' => LD::checkFeatureFlag('update-adjustment-workflow') ? $billingCategories : $billingCategory
    ]
];
?>
<?php
    foreach($buckets as $bucketKey => $bucket) {?>
        <div class="card">
            <div class="card-header" id="billing-<?php echo $bucketKey?>">
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
                data-parent="#billing_invoices">
                <p style="text-align: left;">
                    <u>
                    <b>Responsible Party:</b> Assigned Team Member
                    </u><br>
                    <b>Description:</b> Invoices included in this bucket have been submitted for an adjustment. Please review and indicate if adjustment is approved or denied.<br>
                    <b>Timeline:</b> All requests should be reviewed weekly.
                </p>
                <div class="card-body">
                        @component('components.datatable-filters',['filters'=>$billingInvoiceFilter3,
                        'formname'=>'billingInvoiceForm3'])
                        @endcomponent
                    <?php echo H::getHelper("Billing")->getDataTableHtml($bucketKey,'#billingInvoiceForm')?>
                </div>
            </div>
        </div>
<?php } ?>
<script>
    $(document).on('submit', '#billingInvoiceForm3', function(e) {
        e.preventDefault();
        $("#dataTable-invoices_adjustments").DataTable().ajax.reload();
    });
</script>
