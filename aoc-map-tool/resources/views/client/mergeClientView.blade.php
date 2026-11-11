<?php
use App\Helpers\H;
use App\Helpers\LD;
?>

<h2 class="text-left">Clients Found</h2>
<table class="table" aria-describedby="">
    <tr>
        <th scope="col"></th>
        <th scope="col">ID</th>
        <th scope="col">Patient Account</th>
        <th scope="col">Patient Name</th>
        <th scope="col">Patient City</th>
        <th scope="col">Patient State</th>
        <th scope="col">Created At</th>
    </tr>
    @php
        $i = 0;
    @endphp
    @if(count($patients) > 1)
        @foreach ($patients as $patient)
        <tr>
            <th scope="row">Patient</th>
            <td>{{$patient->id}}</td>
            <td>{{$patient->PatientAccount}}</td>
            <td>{{$patient->PatientName}}</td>
            <td>{{$patient->Patient_City}}</td>
            <td>{{$patient->Patient_State}}</td>
            <td>{{H::getFormatedDatetime($patient->created_at)}}</td>
        </tr>
        @endforeach
    @else
        <tr>
            <td colspan="4">No duplicate patients found!</td>
        </tr>
    @endif

</table>
<script>
    var duplicatedPatientsCount = <?php echo count($patients);?>;
    var goodClientId = '<?php echo isset($patients[0]) ? $patients[0]->id : '';?>';
    var badClientId = '<?php echo isset($patients[1]) ? $patients[1]->id : '';?>';
</script>
