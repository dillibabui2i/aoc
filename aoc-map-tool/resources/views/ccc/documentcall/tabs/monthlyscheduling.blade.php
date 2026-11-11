<?php
    use App\Helpers\H; 
    $options = [
        '',
        'Yes',
        'No'
    ];
    
?>
<section class="tab-content-preferences" style="width: 100%;">
    <div class="main-content">
        <div class="tab-content-area m-2">
            <div class="form-row mt-2 mb-2">
                <div class="form-group col-md-4 m-0 p-0 mr-5">
                    <label
                        for="monthlyscheduling_upcomingvacation"
                        class="col-md-6 p-0 m-0 text-left"
                    >Do you have any upcoming vacations?</label>
                    <select
                        class="form-control"
                        id="monthlyscheduling_upcomingvacation"
                        name="scheduling[upcomingvacation]">
                        @foreach ($options as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-7 m-0 p-0 invisible" id="monthlyscheduling_nurseattendvacation_container">
                    <label
                        for="monthlyscheduling_nurseattendvacation"
                        class="col-md-8 p-0 m-0 text-left"
                    >Will you be needing a nurse to attend the vacation?</label>
                    <select
                        class="form-control"
                        id="monthlyscheduling_nurseattendvacation"
                        name="scheduling[nurseattendvacation]">
                        @foreach ($options as $option) 
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="form-row mt-2 mb-2">
                <div class="form-group col-md-4 m-0 p-0 mr-5">
                    <label
                        for="monthlyscheduling_workschedulechanged"
                        class="col-md-8 p-0 m-0 text-left"
                    >(Respite) Has the work schedule changed</label>
                    <select
                        class="form-control"
                        id="monthlyscheduling_workschedulechanged"
                        name="scheduling[workschedulechanged]">
                        @foreach ($options as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group col-md-7 m-0 p-0">
                    <label
                        for="monthlyscheduling_currentschedulechanged"
                        class="col-md-8 p-0 m-0 text-left"
                    >And your current schedule is - has this changed ?</label>
                    <select
                        class="form-control"
                        id="monthlyscheduling_currentschedulechanged"
                        name="scheduling[currentschedulechanged]">
                        @foreach ($options as $option) 
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>



            <div class="form-row  mt-2 mb-2" >
                
                <div class="form-group col-md-12 m-0 p-0" id="coverageshift_container" style="background:#dfe6ed;">
                    <div class="w-100 m-2">
                        <div class="heading">
                            <h3>Coverage Shift</h3>
                        </div>
                        <table width="100%" class="table bg-white">
                            <thead>
                                <tr>
                                    <th>Clinician</th>
                                    <th>Day</th>
                                    <th>Shift Date</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($coverageShifts as $shift)
                                    @php
										$startTime = $endTime = '';
										if(!empty($shift->Schedule_Time)){
											list($startTime, $endTime) = explode(" - ", $shift->Schedule_Time);
										}
                                    @endphp
                                    <tr>
                                        <th>{{$shift->ClinicianName}}</th>
                                        <th>{{$shift->day}}</th>
                                        <th>{{H::getFormatedDate($shift->shift_date)}}</th>
                                        <th>{{$startTime}}</th>
                                        <th>{{$endTime}}</th>
                                        <th>{{$shift->Hours}}</th>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>


                <div class="form-group col-md-12 m-0 p-0">
                    &nbsp;
                </div>

                <div class="form-group col-md-8 m-0 p-0" style="background:#dfe6ed;">
                    <div class="w-100 m-2">
                        <div class="heading">
                            <h3> Schedule</h3>
                        </div>
                        <table width="100%" class="table bg-white">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Clinician</th>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Hours</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($scheduleMasterData as $schedulemaster)
                                   
                                    <tr>
                                        <th>{{H::getFormatedDate($schedulemaster->ScheduleDate)}}</th>
                                        <th>{{$schedulemaster->day}}</th>
                                        <th>{{str_replace("_Unidentified _Unidentified","N/A",$schedulemaster->ProviderName)}}</th>
                                        <th>{{H::getFormatedDateTime($schedulemaster->PlannedStart)}}</th>
                                        <th>{{H::getFormatedDateTime($schedulemaster->PlannedEnd)}}</th>
                                        <th>{{$schedulemaster->Hours}}</th>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>


            </div>
        </div>
    </div>
</section>

<script>
    $(document).on("change","#monthlyscheduling_upcomingvacation", function() {
        if($(this).val() == 'Yes') {
            $("#monthlyscheduling_nurseattendvacation_container").removeClass('invisible');
        } else {
            $("#monthlyscheduling_nurseattendvacation_container").addClass('invisible');
        }
    });

</script>
