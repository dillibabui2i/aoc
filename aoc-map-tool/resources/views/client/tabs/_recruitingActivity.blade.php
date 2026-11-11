<?php
    use App\Helpers\H;
    $bucketArr = H::getHelper('NTHChecklist')->getBuckets();
?>
<div class="col-md-12 p-0 mt-3">
    <table class="table" aria-describedby="">
        <tr>
            <th>NTH Checklist Step</th>
            <th>Date Started</th>
            <th>Last Activity Date</th>
            <th>Last 7 Days Number</th>
            <th>Number</th>
        </tr>
        @php
        if(!empty($needToHireChecklistDetails)) {
        $i=1;
        @endphp
        @foreach($bucketArr as $k => $v)

        <tr>
            <td>{{$i}}. {{$v['subheading']}}</td>
            <td>{{ isset($v['start']) ? H::getFormatedDate($needToHireChecklistDetails->{$v['start']}) : "" }}
            </td>
            <td>{{ isset($v['work']) ? H::getFormatedDate($needToHireChecklistDetails->{$v['work']}) : "" }}
            </td>
            <td>{{isset($v['number']) ? H::getHelper('NTHChecklist')->getLast7DaysNumber($v['number'],$needToHireChecklistDetails->needToHireChecklistId): ''}}</td>
            <td>{{isset($v['number']) && $needToHireChecklistDetails->{$v['number']} != 0 ? $needToHireChecklistDetails->{$v['number']} : ""}}</td>
        </tr>
        @php
        $i++;
        @endphp
        @endforeach
        @php } else { @endphp
            <tr><td colspan="5">No Details found</td></tr>
        @php } @endphp
    </table>
</div>
