<?php
use App\Helpers\H;
$buckets = [
    'invoices_under_paid'=>'Under Paid',
    'invoices_over_paid'=>'Over Paid',
    'invoices_pending'=>'Pending Invoices',
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
                @if($bucketKey == 'invoices_under_paid')
                    <p style="text-align: left;">
                        <u>
                        <b>Responsible Party:</b> Biller/Collector
                        </u><br>
                        <b>Description:</b> These are invoices that have a payment, but the payment is less than expected.<br>
                        <b>Timeline:</b> These should be reviewed within 5 business days and followed up on every 30 days
                    </p>
                @elseif($bucketKey == 'invoices_over_paid')
                    <p style="text-align: left;">
                        <u>
                        <b>Responsible Party:</b> Biller/Collector
                        </u><br>
                        <b>Description:</b> These invoices have paid more than what was expected resulting in a credit balance.<br>
                        <b>Timeline:</b> These should be reviewed within 30 days
                    </p>
                @endif
                <div class="card-body">
                    @if($bucketKey == 'invoices_pending')
                        @component('components.datatable-filters',['filters'=>$billingInvoiceFilter2,
                        'formname'=>'billingInvoiceForm2'])
                        @endcomponent
                    @endif
                    <?php echo H::getHelper("Billing")->getDataTableHtml($bucketKey,'#billingInvoiceForm')?>
                </div>
            </div>
        </div>
        @if($bucketKey == 'invoices_pending')
        <script>
            $(document).on('submit', '#billingInvoiceForm2', function(e) {
                e.preventDefault();
                $("#dataTable-invoices_pending").DataTable().ajax.reload();
            });
        </script>
        @endif
<?php } ?>
