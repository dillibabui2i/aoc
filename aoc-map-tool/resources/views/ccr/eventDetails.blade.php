<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>
<style>
    .w40{
        width: 40%;
    }
</style>
<x-modal>
    <x-slot name="id">ccrEventDetailsModal</x-slot>
    <x-slot name="title">CCR Event Details</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="class"></x-slot>
    @if($schedule->type_id != 996)
    <x-slot name="extraHeader">
        <div class="col-md-6 text-left pull-left">
            @if(LD::checkFeatureFlag('coverage-tracker-view-action') && !empty($schedule->coverage_id))
                <a href="javascript:void(0);" title="Document Task" onclick="openCCRSNSForm('{{$schedule->id}}','coverage')">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>
                <a href="javascript:void(0);" title="View Tracker Details" onclick="getDetailsOfCoverageTracker('{{$schedule->coverage_id}}')">
                    <i class="fa fa-eye" aria-hidden="true"></i>
                </a>
            @else
                <a href="javascript:void(0);" title="Document Task" onclick="openCCRSNSForm('{{$schedule->id}}')">
                    <i class="fa fa-calendar" aria-hidden="true"></i>
                </a>
                <a href="javascript:void(0);" title="View Tracker Details" onclick="openRecruiterDetails('{{$schedule->tracker_id}}')">
                    <i class="fa fa-eye" aria-hidden="true"></i>
                </a>
            @endif
        </div>
    </x-slot>
    @endif
    <x-slot name="body">
        <div class="modal-body">
            <div class="row d-flex">
                <div class="col-md-12 p-0">
                    @if($schedule->type_id == 996)
                        <div class="col-md-12 p-0">
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Title: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->title}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Description: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->description}}
                                </dd>
                            </dl>
                        </div>
                    @elseif(!empty($schedule->coverage_id))
                        <div class="col-md-12 p-0">
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Clinician Name: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->candidate}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Client: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->client}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Coverage Shift Date: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{H::getFormatedDate($schedule->coverage_shift_date)}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Coverage Shift Time: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->coverage_schedule_time}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">PRC Notes: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->ccrNotes}}
                                </dd>
                            </dl>
                        </div>
                    @else
                        <div class="col-md-12 p-0">
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Candidate Name: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->candidate}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Candidate Phone Number: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->phone}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Client: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->client}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Proposed Next Step: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->proposed_next_step}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Next Step Date: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{H::getFormatedDate($schedule->proposed_ns_date)}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">Next Step Time: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{H::getFormatedTime($schedule->proposed_ns_time)}}
                                </dd>
                            </dl>
                            <dl class="col-md-12 p-0 pb-3 d-flex">
                                <dt class="col-sm-4 p-0 text-right">CCR Notes: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{$schedule->ccrNotes}}
                                </dd>
                            </dl>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>
