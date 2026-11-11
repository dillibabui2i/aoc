<?php
use App\Helpers\H;
use App\Helpers\LD;
?>
<style>
    #invoiceDetailsTable table ,#invoiceLastPay table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
    }

    #invoiceDetailsTable td {
        border: 1px solid #ddd;
        height: 40px;
    }

    #invoiceDetailsTable th, #invoiceDetailsTable td {
        text-align: center;
        background-color: white;
    }

    #invoiceDetailsTable thead th {
        position: sticky;
        top: 0;
        background-color: #c4dbef;
        z-index: 2;
    }

    .table-wrapper {
        overflow-y: auto;
        max-height: 400px; /* Adjust as needed */
    }

    #invoiceDetailsTable tfoot th {
        position: sticky;
        bottom: 0;
        background-color: #c4dbef;
        z-index: 2;
        height: 40px;
    }

    #invoiceDetailsTable .fixed-width {
        width: 100px;
    }
</style>
<x-modal>
    <x-slot name="id">invoiceDetailsModal</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">
        Invoice Details \ {{$invoiceDetails->invoice_number}}
    </x-slot>
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            <a href="javascript:void(0);" title="Set Outcome"
               onclick="invoicesOutcomeFromDetail('{{$bucket_type}}', '{{$invoiceDetails->id}}')">
                <i class="fa fa-calendar-times-o"></i></a>
            <a data-bs-dismiss="modal" href="javascript:void(0);"
               onclick="event.preventDefault();openNotes('{{$invoiceDetails->id}}',
                'invoice','Invoice Notes Added')">
                <i class="fa  fa-sticky-note-o"></i>
            </a>
            <a href="javascript:void(0);" title="Client Profile"
               onclick="getClientDetails('{{$patientsDetails->id}}')">
                <i class="fa fa-user"></i>
            </a>
        </div>
        <div class="col-md-6 text-left pull-left">
            <a href="javascript:void(0);" title="Set Tag"
               onclick="invoicesOutcomeFromDetail('{{$bucket_type}}', '{{$invoiceDetails->id}}')">
                <i class="fa fa-calendar-times-o"></i></a>
            <a data-bs-dismiss="modal" href="javascript:void(0);"
               onclick="event.preventDefault();openNotes('{{$invoiceDetails->id}}',
                'invoice','Invoice Notes Added')">
                <i class="fa  fa-sticky-note-o"></i>
            </a>
            <a href="javascript:void(0);" title="Client Profile"
               onclick="getClientDetails('{{$patientsDetails->id}}')">
                <i class="fa fa-user"></i>
            </a>
        </div>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="col-md-12 p-0 d-flex nurse-info-cnt">
            <div class="col-md-9 pl-3 tracker-details tabs-container">
                <div class="col-md-12 card-cnt p-0 d-flex mb-5">
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Client</h5>
                            <div class="detail-section">
                                <div class="row">
                                    <div class="col-md-6"><b>Client Name</b>: {{$patientsDetails->Patient_FirstName}} {{$patientsDetails->Patient_LastName}}</div>
                                    <div class="col-md-6"><b>Medicaid Number</b>: {{$patientsDetails->MedicaidNumber}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>Patient ID</b>: {{$patientsDetails->PatientAccount}}</div>
                                    <div class="col-md-6"><b>SOC</b>: {{H::getFormatedDate($patientsDetails->SOCDate)}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>Location</b>: {{$patientsDetails->FacilityName}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>DOB</b>: {{H::getFormatedDate($patientsDetails->Patient_DOB)}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>Phone</b>: {{!empty($patientsDetails->Patient_MobilPhone)?$patientsDetails->Patient_MobilPhone:$patientsDetails->Patient_Phone}}</div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6"><b>Payer</b>: {{$patientsDetails->Payer}}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title bg-blue">Invoice Details</h5>
                            <div class="detail-section">
                                <p class="card-text">
                                    <b>Invoice #</b>: {{$invoiceDetails->invoice_number}}
                                </p>
                                <p class="card-text">
                                    <b>Statement Period</b>: {{H::getFormatedDate($invoiceDetails->start_date)}} -
                                    {{H::getFormatedDate($invoiceDetails->end_date)}}
                                </p>
                                <p class="card-text">
                                    <b>Invoice Date</b>: {{H::getFormatedDate($invoiceDetails->invoice_date)}}
                                </p>
                                <p class="card-text">
                                    <b>Invoice Amount</b>:
                                    {{ H::getFormatedAmountWithCurrency($invoiceDetails->totalamount) }}
                                </p>
                                <p class="card-text">
                                    <b>Paid Amount</b>:
                                    {{ H::getFormatedAmountWithCurrency($invoiceDetails->paidamount) }}
                                </p>
                                <p class="card-text">
                                    <b>Adjustment Amount</b>:
                                    {{ H::getFormatedAmountWithCurrency($invoiceDetails->other_adjustments +
                                    $invoiceDetails->contractual_adjustments) }}
                                </p>
                                <p class="card-text">
                                    <b>Expected Amount</b>:
                                    {{ H::getFormatedAmountWithCurrency($invoiceDetails->contractual_amount) }}
                                </p>
                            </div>
                            @if(LD::checkFeatureFlag('manage-project-tags'))
                                @if(!empty($tags) && $tags->count())
                                    <div class="mb-3">
                                        <b>Tags:</b>
                                        @foreach ($tags as $tag)
                                            <span class="badge badge-pill badge-primary">{{ $tag->name }}</span>
                                        @endforeach
                                    </div>
                               @endif
                            @endif
                        </div>
                    </div>
                </div>
                <div class="col-md-12 p-0 card">
                    <div class="col-md-10 pt-3 pl-3">
                        <h1>Last Payment Info</h1>
                    </div>
                    <div class="col-md-6" id="invoiceLastPay">
                        <table aria-describedby="mydesc">
                            <tr>
                                <td><b>Invoice #</b>: {{ $lastPaymentinfo['invoice_number']??'' }}</td>
                                <td><b>Last 90 Days</b>: {{ $lastPaymentinfo['ninty_days_paid_amt']??'$0.00'}}</td>
                            </tr>
                            <tr><td><b>Amount</b>: {{ $lastPaymentinfo['paid_amt']??'$0.00'}}</td>
                                <td><b>YTD</b>: {{ $lastPaymentinfo['ytd_paid_amt']??'$0.00'}}</td></tr>
                            <tr><td><b>Date</b>: {{ $lastPaymentinfo['transaction_date']??''}}</td>
                                <td>&nbsp;</td></tr>
                        </table>
                    </div>
                    <div class="col-md-10 pt-3 pl-3">
                        <h1>Outstanding Invoice Summary</h1>
                    </div>
                    <div class="col-md-12 table-wrapper" id="invoiceDetailsTable">
                        <table aria-describedby="mydesc">
                            <thead>
                            <tr>
                                <th scope="col" class="fixed-width">Invoice #</th>
                                <th scope="col">Invoice Date</th>
                                <th scope="col">Invoice Amount</th>
                                <th scope="col">Balance</th>
                                <th scope="col">Aging Days</th>
                                <th scope="col">0-30</th>
                                <th scope="col">31-60</th>
                                <th scope="col">61-90</th>
                                <th scope="col">91-120</th>
                                <th scope="col">121-150</th>
                                <th scope="col">151-180</th>
                                <th scope="col">181-210</th>
                                <th scope="col">210+</th>
                                <th scope="col">Total</th>
                            </tr>
                            </thead>
                            @if(count($invoicesData) > 0)
                                <tbody>
                                @php
                                    $total = 0;
                                    $buckets = [
                                        '0-30' => 0,
                                        '31-60' => 0,
                                        '61-90' => 0,
                                        '91-120' => 0,
                                        '121-150' => 0,
                                        '151-180' => 0,
                                        '181-210' => 0,
                                        '210+' => 0,
                                    ];
                                @endphp
                                @foreach($invoicesData as $invoice)
                                    <tr>
                                        <td><a href="javascript:void(0);" title="View Detail"
                                               onclick="getInvoiceDetails('{{ $invoice->id }}','{{$bucket_type}}')">
                                            {{ $invoice->invoice_number }}
                                        </td>
                                        <td>{{ $invoice->invoice_date }}</td>
                                        <td>{{ H::getFormatedAmountWithCurrency($invoice->contractual_amount) }}</td>
                                        <td>{{ H::getFormatedAmountWithCurrency($invoice->balance)}}</td>
                                        <td>{{ $invoice->invoice_age }}</td>
                                        @foreach($buckets as $bucket => $value)
                                            <td>
                                                @if($invoice->bucket == $bucket)
                                                    {{ H::getFormatedAmountWithCurrency($invoice->balance)}}
                                                    @php
                                                        $buckets[$bucket] += $invoice->balance;
                                                        $total += $invoice->balance;
                                                    @endphp
                                                @endif
                                            </td>
                                        @endforeach
                                        <td>{{ H::getFormatedAmountWithCurrency($invoice->balance)}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
                                <tfoot>
                                <tr>
                                    <th colspan="5"  scope="col">Total</th>
                                    @foreach($buckets as $bucket => $value)
                                        <th scope="col">
                                            @if($value != 0)
                                                {{ H::getFormatedAmountWithCurrency($value, 2)}}
                                            @endif
                                        </th>
                                    @endforeach
                                    <th scope="col">{{ H::getFormatedAmountWithCurrency($total)}}</th>
                                </tr>
                                </tfoot>
                            @else
                                <tbody>
                                <tr>
                                    <td colspan="14">No record found</td>
                                </tr>
                                </tbody>
                            @endif
                        </table>
                    </div>
                    @if(LD::checkFeatureFlag('add-adjustments-details-on-invoice-details-page'))
                        @if($invoiceDetails->reason == 'Write off request')
                            @if(LD::checkFeatureFlag('update-adjustment-workflow'))
                                @include('billing.partial.adjusmentLevels')
                            @else
                            <div class="col-md-6 pt-3 pl-3">
                                <h1>Adjustment Request</h1>
                            </div>
                            <div class="col-md-6 table-wrapper" id="invoiceDetailsTable">
                                <table aria-describedby="mydesc">
                                    <thead>
                                    <tr>
                                        <th scope="col" class="fixed-width">Level</th>
                                        <th scope="col">Outcome</th>
                                        <th scope="col">Date</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @php
                                        $levels = [
                                            'Team Lead' => ['approved_by' => 'teamlead_approved_by', 'approved_at' => 'teamlead_approved_at'],
                                            'Manager' => ['approved_by' => 'manager_approved_by', 'approved_at' => 'manager_approved_at'],
                                            'Director' => ['approved_by' => 'director_approved_by', 'approved_at' => 'director_approved_at'],
                                            'VP' => ['approved_by' => 'vp_approved_by', 'approved_at' => 'vp_approved_at'],
                                            'Operations' => ['approved_by' => 'operations_approved_by', 'approved_at' => 'operations_approved_at'],
                                        ];
                                    @endphp

                                    @foreach ($levels as $level => $fields)
                                        <tr>
                                            <td>{{ $level }}</td>
                                            <td>
                                                @if ($level === 'VP' && ($invoiceDetails->adjustment_status === 'Pending Ops Review' || !empty($invoiceDetails->requested_ops_approval_date)))
                                                    Request Ops Review
                                                @else
                                                    @if (!empty($invoiceDetails->{$fields['approved_by']}))
                                                        Approved
                                                    @elseif (strtolower($invoiceDetails->rejected_level) === strtolower($level))
                                                        Rejected
                                                    @endif
                                                @endif
                                            </td>
                                            <td>
                                                @if ($level === 'VP' && ($invoiceDetails->adjustment_status === 'Pending Ops Review' || !empty($invoiceDetails->requested_ops_approval_date)))
                                                    {{ !empty($invoiceDetails->requested_ops_approval_date) ? H::getFormatedDate($invoiceDetails->requested_ops_approval_date) : '' }}
                                                @else
                                                    @if (!empty($invoiceDetails->{$fields['approved_at']}))
                                                        {{ H::getFormatedDate($invoiceDetails->{$fields['approved_at']}) }}
                                                    @elseif (strtolower($invoiceDetails->rejected_level) === strtolower($level) && !empty($invoiceDetails->rejected_at))
                                                        {{ H::getFormatedDate($invoiceDetails->rejected_at) }}
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    @endif
                    @endif
                </div>
            </div>
            <div class="col-md-3 d-flex p-0">
                <x-activity-log>

                </x-activity-log>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
    $(document).ready(function(){
        reloadBillingInvoiceHistory('{{ $invoiceDetails->id }}', 'invoice');
    });
    $('#invoiceDetailsModal').on('hidden.bs.modal', function (e) {
        if($.fn.dataTable.isDataTable('#dataTable-<?php echo $bucket_type; ?>')){
            $('#dataTable-<?php echo $bucket_type; ?>').DataTable().ajax.reload();
        }
    });
</script>
