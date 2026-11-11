<?php
    use App\Helpers\H;
?>
<ul class="tabs-activity d-flex">
    @foreach($upcomingAppointments as $appointment)
    <li>
        <div class="card mr-5">
            <div class="card-body">
                <h5 class="card-title">{{$appointment->type}}</h5>
                <div class="detail-section">
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-auto p-0">Date: </dt>
                    <dd class="col-sm-auto text-left">{{H::getFormatedDate($appointment->appointment_date)}}</dd>
                </dl>
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-auto p-0">Time: </dt>
                    <dd class="col-sm-auto text-left">{{H::getFormatedTime($appointment->appointment_time)}}</dd>
                </dl>
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-auto p-0">Client: </dt>
                    <dd class="col-sm-auto text-left">{{$appointment->client}}</dd>
                </dl>
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-auto p-0">Outcome: </dt>
                    <dd class="col-sm-auto text-left">{{$appointment->outcome}}</dd>
                </dl>
                </div>
            </div>
        </div>
    </li>
    @endforeach
</ul>
