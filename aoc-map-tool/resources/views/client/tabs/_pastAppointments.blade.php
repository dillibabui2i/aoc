<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>
@if(LD::checkFeatureFlag('add-section-to-upcoming-and-previous-appointments-tab-in-client-profile'))
<div class="scrollable-sections-container">
    <!-- First Scrollable Section -->
    @if(count($pastAppointments) > 0)
        <div class="scrollable-section">
            <ul class="tabs-activity d-flex">
                @foreach($pastAppointments as $appointment)
                <li>
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title">{{$appointment->type}}</h5>
                            <div class="detail-section">
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Date: </dt>
                                <dd class="col-sm-8 text-left">{{H::getFormatedDate($appointment->appointment_date)}}</dd>
                            </dl>
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Time: </dt>
                                <dd class="col-sm-8 text-left">{{H::getFormatedTime($appointment->appointment_time)}}</dd>
                            </dl>
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Clinician: </dt>
                                <?php if(LD::checkFeatureFlag('client-profile-upcoming-recruitment-clinician-clickable')){ ?>
                                        <dd class="col-sm-8 text-left"><a href="javascript:void(0)" onclick="openRecruiterDetails(<?php echo $appointment->tracker_id?>);">{{$appointment->Candidate}}</a></dd>
                                <?php } else { ?>
                                        <dd class="col-sm-8 text-left">{{$appointment->Candidate}}</dd>
                                <?php } ?>
                            </dl>
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Skillset: </dt>
                                <dd class="col-sm-8 text-left">{{$appointment->Skillset}}</dd>
                            </dl>
                            </div>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Second Scrollable Section -->
    @if(count($coveragePastAppoinments) > 0)
        <div class="scrollable-section">
            <ul class="tabs-activity d-flex">
                @foreach($coveragePastAppoinments as $coverageAppointment)
                <li>
                    <div class="card mr-5">
                        <div class="card-body">
                            <h5 class="card-title">Coverage</h5>
                            <div class="detail-section">
                                <dl class="col-md-12 p-0 d-flex">
                                    <dt class="col-sm-5 p-0 text-right">Date: </dt>
                                    <dd class="col-sm-7 text-left">{{H::getFormatedDate($coverageAppointment->shift_date)}}</dd>
                                </dl>
                                <dl class="col-md-12 p-0 d-flex">
                                    <dt class="col-sm-5 p-0 text-right">Time: </dt>
                                    <dd class="col-sm-7 text-left">{{ explode('-', $coverageAppointment->Schedule_Time)[0] }}</dd>
                                </dl>
                                <dl class="col-md-12 p-0 d-flex">
                                    <dt class="col-sm-5 p-0 text-right">Clinician: </dt>
                                    <dd class="col-sm-7 text-left">{{$coverageAppointment->fullName}}</dd>
                                </dl>
                                <dl class="col-md-12 p-0 d-flex">
                                    <dt class="col-sm-5 p-0 text-right">Discipline: </dt>
                                    <dd class="col-sm-7 text-left">{{$coverageAppointment->Discipline}}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
@else
<ul class="tabs-activity d-flex">
    @foreach($pastAppointments as $appointment)
    <li>
        <div class="card mr-5">
            <div class="card-body">
                <h5 class="card-title">{{$appointment->type}}</h5>
                <div class="detail-section">
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-4 p-0 text-right">Date: </dt>
                    <dd class="col-sm-8 text-left">{{H::getFormatedDate($appointment->appointment_date)}}</dd>
                </dl>
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-4 p-0 text-right">Time: </dt>
                    <dd class="col-sm-8 text-left">{{H::getFormatedTime($appointment->appointment_time)}}</dd>
                </dl>
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-4 p-0 text-right">Clinician: </dt>
                    <?php if(LD::checkFeatureFlag('client-profile-upcoming-recruitment-clinician-clickable')){ ?>
                            <dd class="col-sm-8 text-left"><a href="javascript:void(0)" onclick="openRecruiterDetails(<?php echo $appointment->tracker_id?>);">{{$appointment->Candidate}}</a></dd>
                    <?php } else { ?>
                            <dd class="col-sm-8 text-left">{{$appointment->Candidate}}</dd>
                    <?php } ?>
                </dl>
                <dl class="col-md-12 p-0 d-flex">
                    <dt class="col-sm-4 p-0 text-right">Skillset: </dt>
                    <dd class="col-sm-8 text-left">{{$appointment->Skillset}}</dd>
                </dl>
                </div>
            </div>
        </div>
    </li>
    @endforeach
</ul>
@endif
