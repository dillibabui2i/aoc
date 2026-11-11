<?php
    use App\Helpers\LD;
    $gpFixedPercentage =config('constant')['gpFixedPercentage'];
?>
@if(!empty($calculatedData))
<div class="row">
    <div class="col-md-12 p-0 d-flex">
        <input type="hidden" id="totalReimbursement" value="{{ $calculatedData['final_reimbursement'] }}">
        <input type="hidden" id="insurance" value="{{ $calculatedData['final_insurance'] }}">
        <input type="hidden" id="totalOTHours" value="{{ $calculatedData['overtime_final_hours'] }}">
        <input type="hidden" id="regularHours" value="{{ $calculatedData['regular_final_hours'] }}">
        <input type="hidden" id="totalHours" value="{{ $calculatedData['all_final_hours'] }}">
        <table class="table border" aria-describedby="">
            <tr>
                <th>Weekly Pay (Pre-Tax)</th>
                <th>Home GP</th>
            </tr>
            <tr>
                <td>${{ $calculatedData['all_final_pay'] }}</td>
                <td>{{ $calculatedData['final_gp'] }}%</td>
            </tr>
        </table>
    </div>
    <div class="col-md-12 d-flex p-0">
        <table class="table border" aria-describedby="">
            <tr>
                <th width="20%">GP</th>
                <th width="30%">Total Profitability</th>
                <th width="25%">Total Paid</th>
                <th width="25%">Base Rate</th>
            </tr>
            @foreach($calculatedData['ideal_table'] as $per)
            <tr>
                <td>{{$per['ideal_gp']}}%</td>
                <td>${{$per['ideal_profitability']}}</td>
                <td>${{$per['ideal_paid']}}</td>
                <td>${{$per['ideal_base_rate']}}</td>
            </tr>
            @endforeach
            <tr>
                <td><input type="text" id="custom_rate" name="custom_rate" class="form-control" /></td>
                <td id="totalProfitabilityCell">0</td>
                <td id="totalPaidCell">0</td>
                <td id="baseRateCell">0</td>
            </tr>
        </table>
    </div>
</div>
<x-modal>
    <x-slot name="id">getGPCalculatorInformationPopup</x-slot>
    <x-slot name="size">w50</x-slot>
    <x-slot name="title">Information</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
    <table class="table border" aria-describedby="">
        <tr>
            <th scope="col">Client Name</th>
            <th scope="col">Insurance</th>
            <th scope="col"></th>
        </tr>
        @foreach($calculatedData['client_data'] as $clientId => $value)
        <tr>
            <td>{{ $value['client_name'] }}</td>
            <td>{{ $value['payer'] }}</td>
            <td></td>
        </tr>
        @endforeach
        <tr>
            <th scope="col">Reimbursement Rate</th>
            <th scope="col">Total Paid</th>
            <th scope="col">Total Reimbursement</th>
        </tr>
        <tr>
            <td>${{ $calculatedData['final_reimbursement_rate'] }}</td>
            <td>${{ $calculatedData['final_paid'] }}</td>
            <td>${{ $calculatedData['final_reimbursement'] }}</td>
        </tr>
        <tr>
            <th scope="col">Total Profitability</th>
            <th scope="col">Insurance</th>
            <th scope="col">GP</th>
        </tr>
        <tr>
            <td>${{ $calculatedData['final_profitability'] }}</td>
            <td>${{ $calculatedData['final_insurance'] }}</td>
            <td>{{ $calculatedData['final_gp'] }}%</td>
        </tr>
        <tr>
            <th scope="row" class="newbgcolor">Weekly Pay (Pre-Tax)</th>
            <td colspan="2"  class="newbgcolor" style="text-align: center;">${{ $calculatedData['all_final_pay'] }}</td>
        </tr>
    </table>
    <table class="table border" aria-describedby="">
        <tr>
            <th scope="col"></th>
            <th scope="col">Regular</th>
            <th scope="col">OT</th>
            <th scope="col">All</th>
        </tr>
        <tr>
            <th scope="row" class="newbgcolor">Blended Rate</th>
            <td>${{ $calculatedData['regular_blended_rate'] }}</td>
            <td>
                {{ ($calculatedData['overtime_blended_rate'] > 0)? '$'.$calculatedData['overtime_blended_rate']:0 }}
            </td>
            <td>${{ $calculatedData['all_blended_rate'] }}</td>
        </tr>
        <tr>
            <th scope="row" class="newbgcolor">Hours</th>
            <td>{{ $calculatedData['regular_final_hours'] }}</td>
            <td>{{ $calculatedData['overtime_final_hours'] }}</td>
            <td>{{ $calculatedData['all_final_hours'] }}</td>
        </tr>
        <tr>
            <th scope="row" class="newbgcolor">Pay</th>
            <td>${{ $calculatedData['regular_final_pay'] }}</td>
            <td>{{ ($calculatedData['overtime_final_pay'] > 0)? '$'.$calculatedData['overtime_final_pay']:0 }}</td>
            <td>${{ $calculatedData['all_final_pay'] }}</td>
        </tr>
    </table>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
<script>
    var gpData = '<?php echo json_encode($calculatedData['data']);?>';
    var previewMode = {{$previewMode}};
    $(document).ready(function() {
        const options = {
            style: 'decimal',
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        };
        gpData = JSON.parse(gpData);

        if (previewMode) {
            $.each(gpData, function(clientId, nurses) {
                $.each(nurses, function(index, item)  {
                    var providerIdFromJson = item.aoc_provider_id;
                    var disciplineRaw = item.discipline;
                    var totalGp = item.total_gp;
                    var dataIdx = item.preview;
                    var $nurseGp = $('#nurseGP' + dataIdx);
                    if ($nurseGp.length) {
                        $nurseGp.html(totalGp + " %");
                    }

                });
            });
        } else {
            for(i=0; i < nursesArr.length; i++) {
                let nurseArrVal = nursesArr[i].split('#');
                let clientId = $('#aoc_patient_id_' + nurseArrVal[0]).val();
                //$.each(gpData[clientId], function(nurseIndex, NurseData) {
                $('#nurseGP'+nurseArrVal[0]+''+nurseArrVal[1]).html(gpData[clientId][nurseArrVal[1]].total_gp + " %");
                //});
            }
        }

        $('#custom_rate').keyup(function() {
            var custom_rate=$(this).val();
            if(isNaN(custom_rate) || custom_rate == '') {
                $('#totalProfitabilityCell').text(0);
                $('#totalPaidCell').text(0);
                $('#baseRateCell').text(0);
                return false;
            }
            var totalReimbursement = parseFloat($('#totalReimbursement').val().replace(/,/g, ''));
            var insurance = parseFloat($('#insurance').val().replace(/,/g, ''));
            var totalOTHours = parseFloat($('#totalOTHours').val().replace(/,/g, ''));
            var regularHours = parseFloat($('#regularHours').val().replace(/,/g, ''));
            var totalHours = parseFloat($('#totalHours').val().replace(/,/g, ''));

            var totalProfitability = (totalReimbursement * parseFloat(custom_rate.replace(/,/g, ''))) / 100;
            var totalPaid = totalReimbursement - totalProfitability;

            var weeklyPay = (totalPaid - insurance) /1.1;

            var baseRate;
            if (totalOTHours > 0) {
                var calculatedHours = regularHours + (totalOTHours * 1.5);
                baseRate = weeklyPay / calculatedHours;
            } else {
                baseRate = weeklyPay / totalHours;
            }

            // Update table cells with the calculated values
            $('#totalProfitabilityCell').text('$'+totalProfitability.toLocaleString('en-US', options));
            $('#totalPaidCell').text('$'+totalPaid.toLocaleString('en-US', options));
            $('#baseRateCell').text('$'+baseRate.toLocaleString('en-US', options));
        });
    });
    function gpInformationPopup() {
        $('#getGPCalculatorInformationPopup').modal('show');
    };
</script>
@endif
