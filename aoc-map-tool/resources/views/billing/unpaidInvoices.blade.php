<?php
use App\Helpers\H;
$buckets = [
    'unpaid_invoices_no_response'=>'No Response',
    'unpaid_invoices_denied'=>'Denied',];
?>
<div class="card">
<div class="card-header" id="dailyactivityone">
    <h4 class="mb-0">
        <button class=" col-md-9 pull-left btn btn-link btn-block text-left collapsed"
        type="button" data-toggle="collapse"
            data-target="#billing_unpaid_invoices" aria-expanded="false" aria-controls="collapseOne">
            Unpaid Invoices<i class="fa fa-chevron-right"></i>
        </button>
    </h4>
</div>

<div id="billing_unpaid_invoices" class="collapse" aria-labelledby="dailyactivityone"
data-parent="#billing_invoices">
    <div class="card-body">
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
                        data-parent="#billing_unpaid_invoices">
                        @if($bucketKey == 'unpaid_invoices_no_response')
                        <p style="text-align: left;">
                            <u>
                            <b>Responsible Party:</b> Biller/Collector
                            </u><br>
                            <b>Description:</b> These are invoices that have not had a remittance response from the payer.<br>
                            <b>Timeline:</b> These claims should be followed up with every 30 days
                        </p>
                        @elseif($bucketKey == 'unpaid_invoices_denied')
                        <p style="text-align: left;">
                            <u>
                            <b>Responsible Party:</b> Biller/Collector
                            </u><br>
                            <b>Description:</b> These are invoices that have been denied from the payer.<br>
                            <b>Timeline:</b> These claims should be reviewed within 5 business days of denial posting
                        </p>
                        @endif
                        <div class="card-body">
                            <?php echo H::getHelper("Billing")->getDataTableHtml($bucketKey,'#billingInvoiceForm')?>
                        </div>
                    </div>
                </div>
        <?php } ?>
    </div>
</div>
</div>