<?php
use App\Helpers\H;
use App\Helpers\LD;
$data = H::getHelper('SocialMedia')->getReportData();
?>

<div class="card-body">
    <div class="card">
        <div class="card-header" id="dailyactivityone">
            <h4 class="mb-0">
                <button class="btn btn-link btn-block text-left collapsed col-md-11 pull-left" type="button" data-toggle="collapse"
                    data-target="#social_media_report-container" aria-expanded="false" aria-controls="collapseOne">
                    Report <i class="fa fa-chevron-right"></i>
                </button>
            </h4>
        </div>

        <div id="social_media_report-container" class="collapse" aria-labelledby="dailyactivityone"
            data-parent="#client_care_childs">
            <div class="card-body">
                <table class="table social-report">
                    <tr>
                        <th>State</th>
                        <th>Category</th>
                        <th>Monthly Budget</th>
                        <th>Allocated Budget</th>
                        <th>Current Spend</th>
                        <th>Budget Remaining</th>
                    </tr>
                    @foreach($data as $key => $val)
                        @php
                            $totalMonth = 0;
                            $totalCurrent = 0;
                            $totalAllocated = 0;
                            $totalRemaining = 0;
                        @endphp
                        @foreach($val as $k => $v)
                        @php
                            $totalMonth += $v->budget;
                            $totalAllocated += $v->allocated_budget;
                            $totalCurrent += $v->actual_spend;
                            $totalRemaining += ($v->budget - $v->actual_spend);
                        @endphp
                        <tr>
                            @if($k == 0)
                            <td rowspan="{{count($val) > 1 ? (count($val) + 1) : count($val)}}">{{config('constant')['states'][$v->state]}}</td>
                            @endif
                            <td>{{$v->category}}</td>
                            <td>${{number_format($v->budget, 2)}}</td>
                            <td>${{number_format($v->allocated_budget,2)}}</td>
                            <td>${{number_format($v->actual_spend,2)}}</td>
                            <td>${{number_format($v->budget - $v->actual_spend,2)}}</td>
                        </tr>
                        @endforeach

                        @if(count($val) > 1)
                            <tr class="social-total-row">
                                <td><strong>Total</strong></td>
                                <td><strong>${{number_format($totalMonth,2)}}</strong></td>
                                <td><strong>${{number_format($totalAllocated,2)}}</strong></td>
                                <td><strong>${{number_format($totalCurrent,2)}}</strong></td>
                                <td><strong>${{number_format($totalRemaining,2)}}</strong></td>
                            </tr>
                        @endif
                    @endforeach

                </table>
            </div>
        </div>
    </div>
</div>
