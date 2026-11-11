<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>
<style>
    .tracker-history-container {
        height: 500px !important;
    }
    .table-container {
        width: 100%;
        background-color: #0056A3;
        color: #ffffff;
        text-align: left;
        border-collapse: collapse;
        padding-bottom: 5px;
    }
    .table-container th, .table-container td {
        padding: 10px;
        border: 1px solid #ddd;
        font-size: 12px;
    }
    .table-container th {
        background-color: #0056A3;
        color: #ffffff;
        font-size: 12px;
    }
    .table-container td {
        background-color: #ffffff;
        color: #333;
    }
    .checkbox-cell, .icon-cell {
        text-align: center;
    }
</style>
<x-modal>
    <x-slot name="id">billingDetailsModal</x-slot>
    <x-slot name="size">w30</x-slot>
    <x-slot name="title">
        Activity History
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12">
                @if(LD::checkFeatureFlag('change-to-activity-log-unbilled-widget'))
                <table class="table-container">
                    <thead>
                        <tr>
                            <th>Branch Location</th>
                            <th>Client</th>
                            <th>LOB</th>
                            <th>Clinician</th>
                            <th>Visit Date</th>
                            <th>Days Past DOS</th>
                            <th>Billable Hours</th>
                            <th>Expected Amount</th>
                            @if(LD::checkFeatureFlag('Move-schedule-status-and-echart-status-columns-from-bucket-to-detail-screen'))
                                <th>Schedule Status</th>
                                <th>Echart Status</th>
                            @endif
                            <th>Terminal Payer</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(LD::checkFeatureFlag('combine-schedules-into-one-row-unbilled-schedules-billing-dashboard'))
                        @foreach($billingData as $billingDs)
                        <tr>
                            <td>{{ $billingDs->FacilityName }}</td>
                            <td>{{ $billingDs->clientName }}</td>
                            <td>{{ $billingDs->LOBName }}</td>
                            <td>{{ $billingDs->clinicianName }}</td>
                            <td>{{ $billingDs->ScheduleDate }}</td>
                            <td>{{ $billingDs->DaysPastDOS }}</td>
                            <td>{{ $billingDs->Hours }}</td>
                            <td>{{ $billingDs->Contractualamount }}</td>
                            @if(LD::checkFeatureFlag('Move-schedule-status-and-echart-status-columns-from-bucket-to-detail-screen'))
                                <td>{{ $billingDs->scheduleStatus }}</td>
                                <td>{{ $billingDs->Status }}</td>
                            @endif
                            <td>{{ $billingDs->Payer }}</td>
                        </tr>
                        @endforeach
                        @else
                        <tr>
                            <td>{{ $billingData->FacilityName }}</td>
                            <td>{{ $billingData->clientName }}</td>
                            <td>{{ $billingData->LOBName }}</td>
                            <td>{{ $billingData->clinicianName }}</td>
                            <td>{{ $billingData->ScheduleDate }}</td>
                            <td>{{ $billingData->DaysPastDOS }}</td>
                            <td>{{ $billingData->Hours }}</td>
                            <td>{{ $billingData->Contractualamount }}</td>
                            @if(LD::checkFeatureFlag('Move-schedule-status-and-echart-status-columns-from-bucket-to-detail-screen'))
                                <td>{{ $billingData->scheduleStatus }}</td>
                                <td>{{ $billingData->Status }}</td>
                            @endif
                            <td>{{ $billingData->Payer }}</td>
                        </tr>
                        @endif
                    </tbody>
                </table>
                @endif
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
        reloadBillingInvoiceHistory('{{ $billingDetails }}', 'billing');
    });
</script>
