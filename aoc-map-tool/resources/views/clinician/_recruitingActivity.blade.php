<?php
    use App\Helpers\H;
?>
<ul class="tabs-activity d-flex">
    @foreach($recruitingActivity as $activity)
    <li>
        <div class="card mr-5">
            <div class="card-body">
                <h5 class="card-title">{{$activity->type}}</h5>
                <div class="detail-section">
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-auto p-0">Date: </dt>
                    <dd class="col-sm-auto text-left">{{H::getFormatedDate($activity->appointment_date)}}</dd>
                </dl>
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-auto p-0">Time: </dt>
                    <dd class="col-sm-auto text-left">{{H::getFormatedTime($activity->appointment_time)}}</dd>
                </dl>
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-auto p-0">Client: </dt>
                    <dd class="col-sm-auto text-left">{{$activity->client}}</dd>
                </dl>
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-auto p-0">Outcome: </dt>
                    <dd class="col-sm-auto text-left">{{$activity->outcome}}</dd>
                </dl>
                </div>
            </div>
        </div>
    </li>
    @endforeach
</ul>
