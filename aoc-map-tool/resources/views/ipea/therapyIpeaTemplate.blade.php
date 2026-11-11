<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    use App\Helpers\ConstantHelper;

    $totalIpeaChecklist = 0;
    array_walk_recursive($ipeaChecklist, function($item) use (&$totalIpeaChecklist) {
        $totalIpeaChecklist++;
    });
?>

<style>
.w90 {
    max-width: 90% !important;
}

.w40 {
    width: 40%;
}

#ui-datepicker-div {
    position: absolute !important;
    left: auto !important;
    top: auto !important;
    box-shadow: 0px 0px 3px 0px;
}
 .ui-widget.ui-widget-content{
    z-index: 1051;
}
.top-sticky-container .ui-widget.ui-widget-content{
    z-index: 0;
}
.modal-title{
    width: 100%;
}
.created-at{
    font-size: 11px;
    text-align: right;
    margin-top: 5px;
    float: right;
}
.created-at span{
    font-size: 14px;
    vertical-align: middle;
}
.tabs-container .tab-content{
    max-height: 550px;
    min-height: 300px;
    overflow-x: hidden;
    position: relative;
}
.tag.skillset{
    background: #9747FF;
}
.tag.rehire{
    background: #3A99FF;
}
.tag.new-grand{
    background: #3AAE5C;
}
.tag.bilingual{
    background: #F24822;
}
#ipeaChecklistAccordion .btn{
    color: #fff;
}
#ipeaChecklistAccordion .checklist .card-body {
    display: flex;
    justify-content: space-between;
    align-items: center;
}
#ipeaChecklistAccordion .checklist .card-body .form-control {
    width: 20px;
}
#ipeaChecklistAccordion .checklist {
    padding: 10px 20px;
    border-bottom: 1px solid #ddd;
}
#ipeaChecklistAccordion .checklist label {
    margin: 0;
}
#ipeaChecklistAccordion .card-header{
    padding: 0px;
}
ul.att-cnt{
    margin-top:0px;
}
ul.att-cnt li {
    width: 30%;
    float: left;
    margin-bottom: 10px;
}
.tracker-details .card{
    width: 47.5%;
}

.tracker-details h5.card-title.bg-blue{
    width: 100%;
    /* max-height: 40px; */
}
.w-23{
    width: 23% !important;
}
.w-45{
    width: 45% !important;
}
.h-80{
    height: 80%;
}
.tracker-details .table td, .tracker-details .table th, .tracker-details .table tr {
    border: 0px !important;
}
.form-check-inline label:first-child{
    width: 20%!important;
}
</style>

<x-modal>
    <x-slot name="id">neoIpeaTemplateModal</x-slot>
    <x-slot name="size">w90</x-slot>
    <x-slot name="title">
        IPEA Record / {{isset($data['name']) ? $data['name'] : ''}}
        <div class="created-at">
            Created at <span>{{H::getFormatedDateTime($scheduleDetails->created_at)}}</span>
        </div>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="col-md-12 p-0 d-flex nurse-info-cnt">
            <div class="col-md-9 pl-3 tracker-details tabs-container">
                <div class="col-md-12 d-flex p-0">
                    <div class="card border-0 p-2 d-block">
                        <div class="col-md-12 p-0 d-flex">
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-2 p-0 text-left">Date:</dt>
                                <dd class='col-sm-8 text-left'>
                                    {{H::getFormatedDate($scheduleDetails->appointment_date)}}
                                    {{H::getFormatedTime(date('Y-m-d').' '.$scheduleDetails->appointment_time)}}
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-12 p-0 d-flex">
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-2 p-0 text-left">Location: </dt>
                                <dd class="col-sm-8 text-left">
                                    {{isset($ipeaLocationDetails->branch) ? $ipeaLocationDetails->branch : ""}}
                                </dd>
                            </dl>
                        </div>
                    </div>
                    <div class="card border-0 p-2 d-block ml-auto">
                        <div class="col-md-12 p-0 d-flex">
                            <dl class="col-md-12 p-0 d-flex">
                                <dt class="col-sm-2 p-0 text-left">Recruiter:</dt>
                                <dd class='col-sm-8 text-left'>
                                    {{isset($data['Recruiter']) ? $data['Recruiter'] : ''}}
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-md-12 card-cnt p-0 d-flex mb-5">
                    <div class="col-md-12 p-0 d-flex">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title bg-blue">Candidate</h5>
                                <div class="d-flex flex-wrap h-80">
                                    <div class="detail-section d-flex w-100">
                                        <div class="col-md-6 pl-0">
                                            <a href="javascript:void(0);"><h6>{{isset($data['name']) ? $data['name'] : ''}}</h6></a>
                                            <p class="card-text">
                                                {{isset($data['Phone']) ? $data['Phone'] : ''}}
                                            </p>
                                            <p class="card-text">
                                                Zipcode {{isset($data['Zipcode']) ? $data['Zipcode'] : ''}}
                                            </p>
                                            <p class="card-text">
                                            {{isset($data['Email']) ? $data['Email'] : ''}}
                                            </p>
                                        </div>
                                        <div class="col-md-6 pl-0">
                                            <p class="card-text d-flex">
                                                <label class="w-50" for=""> Attachment </label>
                                                <td><a href="{{ route('schedules.attechmentprivew', [$scheduleDetails->id]) }}" target="_blank">View Attachment</a></td>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="detail-section d-flex w-100 flex-wrap">
                                        <div class="tags-cnt">
                                            @if(isset($data['Discipline']) && $data['Discipline'] != '')
                                            <span class='tag skillset'>{{$data['Discipline']}}</span>
                                            @endif

                                            @if(isset($data['Bilingual']) && $data['Bilingual'] == 1)
                                            <span class='tag bilingual'>Bilingual</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card client-cnt">
                            <div class="card-body">
                                <h5 class="card-title bg-blue">&nbsp</h5>
                                <div class="d-flex flex-wrap h-80">
                                    <div class="detail-section d-flex w-100">
                                        <div class="col-md-5 pl-0">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <input class="form-control" type="hidden" name="scheduleId" id="scheduleId"
                    value="{{$scheduleDetails->id}}" />

                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#summary" role="tab">Summary</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#checklist" role="tab">Checklist (<span class="selectedChecklistTotal"></span> / <span class="totalIpeaChecklist">{{$totalIpeaChecklist}}</span>)</a>
                    </li>
                </ul>
                <div class="tab-content p-1 ">
                    <div class="tab-pane active" id="summary" role="tabpanel">
                        <div class="col-md-12 p-0 d-flex">
                            <div class="col-md-8 p-3 ipea-cnt">
                                <div class="form-group row">
                                    <div class="form-check-inline col-md-12">
                                        <label for=""> Clinician Arrived </label>
                                        <label for="IPEA_clinician_arrived-none" class="form-check-label">
                                            <input class="form-check-input radio-inline on-change-neo" type="radio"
                                                name="IPEA_clinician_arrived" id="IPEA_clinician_arrived-none" value="None"
                                                {{!isset($scheduleDetails->IPEA_clinician_arrived) || $scheduleDetails->IPEA_clinician_arrived == NULL ? "checked='checked'" : ""}} />
                                            None</label>
                                        <label for="IPEA_clinician_arrived" class="form-check-label">
                                            <input class="form-check-input radio-inline on-change-neo" type="radio"
                                                name="IPEA_clinician_arrived" id="IPEA_clinician_arrived" value="Yes"
                                                {{isset($scheduleDetails->IPEA_clinician_arrived) && $scheduleDetails->IPEA_clinician_arrived == "Yes" ? "checked='checked'" : ""}} />
                                            Yes</label>
                                        <label for="IPEA_clinician_arrived_nc_ns" class="form-check-label">
                                            <input class="form-check-input radio-inline on-change-neo" type="radio"
                                                name="IPEA_clinician_arrived" id="IPEA_clinician_arrived_nc_ns" value="NC/NS"
                                                {{isset($scheduleDetails->IPEA_clinician_arrived) && $scheduleDetails->IPEA_clinician_arrived == "NC/NS" ? "checked='checked'" : ""}} />
                                            NC/NS</label>
                                        <label for="IPEA_clinician_arrived_no" class="form-check-label">
                                            <input class="form-check-input radio-inline on-change-neo" type="radio"
                                                name="IPEA_clinician_arrived" id="IPEA_clinician_arrived_no" value="Rescheduled"
                                                {{isset($scheduleDetails->IPEA_clinician_arrived) && $scheduleDetails->IPEA_clinician_arrived == "Rescheduled" ? "checked='checked'" : ""}} />
                                            Rescheduled</label>
                                        <label for="IPEA_clinician_arrived_Cancelled" class="form-check-label">
                                            <input class="form-check-input radio-inline on-change-neo" type="radio"
                                                name="IPEA_clinician_arrived" id="IPEA_clinician_arrived_Cancelled"
                                                value="Cancelled"
                                                {{isset($scheduleDetails->IPEA_clinician_arrived) && $scheduleDetails->IPEA_clinician_arrived == "Cancelled" ? "checked='checked'" : ""}} />
                                            Cancelled</label>

                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="" class="w-100"> Notes </label>
                                <div class="textarea-border">
                                    {{isset($data['notes']) ? $data['notes'] : ''}}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="tab-pane" id="checklist" role="tabpanel">
                        @include('ipea.checklist',['ipeaChecklist'=>$ipeaChecklist,'currentChecklists'=>$currentChecklists])
                    </div>
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

<x-modal>
    <x-slot name="id">ipeaNeoReschedule</x-slot>
    <x-slot name="size">w40</x-slot>
    <x-slot name="title">Reschedule</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="col-md-12">
            <form method="get" id="rescheduleNeoForm">
                <div class="pt-2">
                    <div class="col-md-12">
                        <label for="ipea_location_id">IPEA Location</label>
                        <select name="ipea_location_id" class="form-control" id="ipea_location_id">
                            <option value="">-- Select One --</option>
                            @foreach($branches as $key => $branch)
                            <optgroup label="{{$key}}">
                                @foreach($branch as $b)
                                <option {{isset($ipeaLocationDetails->id) && $ipeaLocationDetails->id == $b['id'] ? "selected='selected'" : ""}} value="{{$b['id']}}">{{$b['branch']}}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="pt-2">
                    <div class="col-md-12">
                        <label for="datepicker">Date</label>
                        <input type="input" class="form-control" id="IPEA_Date" name="IPEA_Date" />
                    </div>
                </div>
                <div class="pt-2">
                    <div class="col-sm-12">
                        <label for="appointment_time">Time</label>
                        <input type="input" class="form-control" id="IPEA_Time" name="IPEA_Time">
                    </div>
                </div>
                <div class="pt-2">
                    <div class="col-sm-12">
                        <button type="button" id="saveNeoReschedule" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<script src="{{ mix('js/jquery.timepicker.min.js') }}"></script>
<link href="{{ mix('css/jquery.timepicker.min.css') }}" rel="stylesheet">

<script>
    $(document).ready(function(){
        reloadIpeaNeoHistory($("#scheduleId").val());

        $('#IPEA_Date').datepicker().bind('click', function() {
            $('#ui-datepicker-div').appendTo($('#IPEA_Date').parent());
        });
        $('#IPEA_Time').timepicker({
            "step": 15,
            'forceRoundTime': true,
            'minTime': '06:00:00',
            'maxTime': '21:00:00'
        });
        $("#rescheduleNeoForm").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "ipea_location_id": {
                    required: true
                },
                "IPEA_Date": {
                    required: true
                },
                "IPEA_Time": {
                    required: true
                },
            }
        });

    });
    $(document).off('change', '.on-change-neo');
    $(document).on('change', '.on-change-neo', function(e) {
        console.log($(this).val());
        if ($(this).val() == 'Rescheduled') {
            $('#ipeaNeoReschedule').modal('show');
            return;
        }
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('ipea.neoIpeaSaveValues')}}",
            type: 'get',
            data: {
                scheduleId: $("#scheduleId").val(),
                name: $(this).attr('name'),
                value: $(this).val()
            },
            dataType: 'json',
            success: function(data) {
                if (data.status == false) {
                    console.log('Something went wrong!');
                }
                reloadIpeaNeoHistory($("#scheduleId").val());
            }
        });
    });
    $(document).off('click', '#saveNeoReschedule');
    $(document).on('click', '#saveNeoReschedule', function() {
        if ($("#rescheduleNeoForm").valid() == false) {
            return false;
        }
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('ipea.saveNeoReschedule')}}",
            type: 'get',
            data: {
                scheduleId: $("#neoIpeaTemplateModal #scheduleId").val(),
                ipea_location_id: $("#ipeaNeoReschedule #ipea_location_id").val(),
                IPEA_Date: $('#ipeaNeoReschedule #IPEA_Date').val(),
                IPEA_Time: $('#ipeaNeoReschedule #IPEA_Time').val(),
                _token: _token
            },
            dataType: 'json',
            success: function(data) {
                if (data.status == true) {
                    $('#ipeaNeoReschedule').modal('hide');
                    reloadIpeaNeoHistory($("#scheduleId").val());
                }
            }
        });
    });
    function reloadIpeaNeoHistory(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('ipea.ipeaNeoHistory')}}",
            type: 'get',
            data: {
                id: id,
                _token: _token
            },
            dataType: 'html',
            success: function(data) {
                $('.activity-log').html(data);
            }
        });
    }
</script>
