<?php
    use App\Helpers\H;
    $acceptCoverageOptions = [
        '',
        'Yes',
        'No',
        'Accepts coverage with stipulations'
    ];
    $options = [
        '',
        'Yes',
        'No'
    ];
    $reasonNotAcceptCoverageOptions = [
        '',
        'Age',
        'AM/PM Shifts',
        'Clinician discipline',
        'Family requires a week notice for coverage found',
        'Family requiring multiple shifts to be covered 2+',
        'Female nurses not accepted',
        'Male nurses not accepted',
        'Language barrier',
        'Meet & greet required',
        'No lift in the home',
        'Pay rate',
        'Payer',
        'Pets in the home',
        'School setup needed',
        'Skills needed',
        'Smoking',
        'Specialized (T/V or TPN)',
        'Training shift required',
        'Vaccination required',
        'Weight limitations for lifting',
    ];
?>
<section class="tab-content-preferences" style="width: 100%;">
    <div class="main-content">
        <div class="tab-content-area m-2">
            <div class="form-row mt-2 mb-2">
                <div class="form-group col-md-4 m-0 p-0 mr-5">
                    <label
                        for="scheduling_accept_coverage"
                        class="col-md-6 p-0 m-0 text-left"
                    >Do you accept coverage?</label>
                    <select
                        class="form-control"
                        id="scheduling_accept_coverage"
                        name="scheduling[accept_coverage]">
                        @foreach ($acceptCoverageOptions as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-7 m-0 p-0">
                    <label
                        for="scheduling_coverage_shift_schedule"
                        class="col-md-8 p-0 m-0 text-left"
                    >Coverage shifts are currently scheduled as... Is still correct?</label>
                    <select
                        class="form-control"
                        id="scheduling_coverage_shift_schedule"
                        name="scheduling[coverage_shift_schedule]">
                        @foreach ($options as $option) 
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row  mt-2 mb-2">
                <div class="form-group col-md-4 m-0 p-0 mr-5 invisible" id="td_reason_not_accept_coverage">
                    <label
                        for="scheduling_accept_coverage"
                        class="col-md-8 p-0 m-0 text-left"
                    >Reasons for not accepting coverage</label>
                    <select
                        class="form-control"
                        id="scheduling_reason_not_accept_coverage"
                        name="scheduling[reason_not_accept_coverage]">
                        @foreach ($reasonNotAcceptCoverageOptions as $option) 
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="form-row  mt-2 mb-2">
                <div class="form-group col-md-7 m-0 p-0" style="background:#dfe6ed;">
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
            </div>
            <div class="form-row mt-2 mb-2">
                <div class="form-group col-md-5 m-0 p-0 mr-5">
                    <label
                        for="scheduling_available_days_hours_still"
                        class="col-md-9 p-0 m-0 text-left"
                    >Are you still currently available for the following days and hours?</label>
                    <select
                        class="form-control"
                        id="scheduling_available_days_hours_still"
                        name="scheduling[available_days_hours_still]">
                        @foreach ($options as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if (!empty($nthSchedules))
            <div class="form-row mt-2 mb-2">
                <div class="form-group col-md-7 m-0 p-0" style="background:#dfe6ed;">
                    {!! $nthSchedules !!}
                </div>
            </div>
            @endif
        </div>
    </div>
</section>

<script>
    $(document).on("change","#scheduling_accept_coverage", function() {
        if($(this).val() == 'Accepts coverage with stipulations') {
            $("#td_reason_not_accept_coverage").removeClass('invisible');
        } else {
            $("#td_reason_not_accept_coverage").addClass('invisible');
        }
    });
    $('#scheduling_available_days_hours_still').off().change(function() {
        if($(this).find('option:selected').val() == 'No') {
            openBuildScheduleForm('{{$needToHireChecklistDetails->needToHireId}}');
        }
    });
</script>
