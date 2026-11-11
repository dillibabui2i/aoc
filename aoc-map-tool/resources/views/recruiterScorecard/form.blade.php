<?php
    $utilityTotalFields = [
        $recruiterPossiblePer['skills_recruiter_dashboard'],
        $recruiterPossiblePer['skills_nth_checklists_worked'],
        $recruiterPossiblePer['skills_jobvite'],
    ];
    $utilityTotalPer = array_sum($utilityTotalFields);

    $performanceTotalFields = [
        $recruiterPossiblePer['perform_headhunting'],
        $recruiterPossiblePer['perform_ipea'],
        $recruiterPossiblePer['perform_conversion'],
        $recruiterPossiblePer['perform_pepiwepi'],
        $recruiterPossiblePer['perform_hrsremoved'],
        $recruiterPossiblePer['perform_first_shift_worked'],
    ];
    $performanceTotalPer = array_sum($performanceTotalFields);

    $influenceTotalFields = [
        $recruiterPossiblePer['phone_call_screening_score'],
        $recruiterPossiblePer['attendance_and_dependability'],
        $recruiterPossiblePer['professionalism']
    ];
    $influenceTotalPer = array_sum($influenceTotalFields);

    $phoneScreenTotalPer = $recruiterPossiblePer['phone_screen'];

    $phoneScreenOptions = [
        '' => ' Please Select ',
        '5' => '5 - Outstanding',
        '4' => '4 - Exceeds Expectations',
        '3' => '3 - Achieving Expectations',
        '2' => '2 - Partially Meets Expectations',
        '1' => '1 - Unsatisfactory Performance'
    ]
?>
<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<x-modal>
    <x-slot name="id">recruiterScorecardFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
    @if($viewMode == 'add')
        <x-slot name="title">Add Recruiter Scorecard</x-slot>
    @elseif($viewMode == 'edit')
        <x-slot name="title">Edit Recruiter Scorecard</x-slot>
    @else
        <x-slot name="title">Recruiter Scorecard \ <span id="title_recruiter_id"></span></x-slot>
    @endif
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="recruiterScorecardForm" class="recruiterScorecardForm">
        <input type="hidden" id="scorecardid" name="scorecardid"/>
        <section class="recruiter-details">
            @if($viewMode != 'view')
            <div class="form-group row d-flex">
                <label  class="col-sm-2" for="recruiter_id">Recruiter</label>
                <div class="col-sm-8">
                    <select class="form-control" name="recruiter_id" id="recruiter_id" {{($viewMode == 'edit') ? 'disabled' : ''}}>
                        <option value="">--- Select ----</option>
                        @foreach($recruiter as $key => $opt)
                            <option value="{{$opt['value']}}">{{$opt['text']}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
            @if($viewMode == 'view')
            <div class="col-md-12 d-flex p-0">
                <div class="col-md-4 p-0">
                    <label class="col-sm-4 p-0 text-right">Recruiter:</label>
                    <span id="recruiter_id" class="col-sm-8 text-left"></span>
                </div>
                <div class="col-md-4 p-0">
                    <label class="col-sm-4 p-0 text-right">Manager:</label>
                    <span id="manager_id" class="col-sm-8 text-left"></span>
                </div>
                <div class="col-md-4 p-0">
                    <label class="col-sm-4 p-0 text-right">Date:</label>
                    <span id="scorecard_date" class="col-sm-8 text-left"></span>
                </div>
            </div>
            <div class="col-md-12 d-flex p-0">
                <div class="col-md-4 p-0">
                    <label class="col-sm-4 p-0 text-right">Total Score:</label>
                    <span id="score" class="col-sm-8 text-left"></span>
                </div>
                <div class="col-md-4 p-0">
                    <label class="col-sm-4 p-0 text-right">Grade:</label>
                    <span id="grade" class="col-sm-8 text-left"></span>
                </div>
                <div class="col-md-4 p-0">
                    <label class="col-sm-4 p-0 text-right">Action Item:</label>
                    <span id="action_item" class="col-sm-8 text-left"></span>
                </div>
            </div>
            @endif
        </section>
        <section class="utilizing-tools-resources-detail showbasedonrecruiter">
            <table class="table table-bordered" aria-describedby="">
                <thead>
                    <tr>
                        <th style="width: 60%;">Utilizing Tools and Resources (Weighs {{$utilityTotalPer}}%)</th>
                        <th style="width: 20%;">%</th>
                        <th style="width: 20%;">Possible %</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Recruiter Dashboard: Keep clean and up to date for daily workflow, leaving no nurse behind</td>
                        <td>
                            <span id="skills_recruiter_dashboard" class="editable-scorecard" data-emptytext='Click to add'
                            data-type='text' data-url='{{route("editable")}}'
                            data-pk="" data-name='skills_recruiter_dashboard'
                            data-table='recruiter_scorecards'></span>
                        </td>
                        <td>{{$recruiterPossiblePer['skills_recruiter_dashboard']}}%</td>
                    </tr>
                    <tr>
                        <td>NTH Checklists<br/>worked for all<br/>patients</td>
                        <td><span id="skills_nth_checklists_worked"></span></td>
                        <td>{{$recruiterPossiblePer['skills_nth_checklists_worked']}}%</td>
                    </tr>
                    <tr>
                        <td>Jobvite: Working all candidates in a timely manner through PET1 > PET2 > PET3 > Reject</td>
                        <td><span id="skills_jobvite"></span></td>
                        <td>{{$recruiterPossiblePer['skills_jobvite']}}%</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Skills Total</td>
                        <td><span id="utilityTotalPer"></span></td>
                        <td>{{$utilityTotalPer}}%</td>
                    </tr>
                </tfoot>
            </table>
        </section>
        <section class="performance-detail showbasedonrecruiter">
            <table class="table table-bordered" aria-describedby="">
                <thead>
                    <tr>
                        <th style="width: 60%;">Performance: Business and Customer Relations (Weighs {{$performanceTotalPer}}%)</th>
                        <th style="width: 10%;">%</th>
                        <th style="width: 10%;">Possible %</th>
                        <th style="width: 10%;">Goal</th>
                        <th style="width: 10%;">Actual</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Headhunting: Minimum 2 candidates / day</td>
                        <td>
                            <span id="perform_headhunting" class="editable-scorecard" data-emptytext='Click to add'
                            data-type='text' data-url='{{route("editable")}}'
                            data-pk="" data-name='perform_headhunting'
                            data-table='recruiter_scorecards'></span>
                        </td>
                        <td>{{$recruiterPossiblePer['perform_headhunting']}}%</td>
                        <td><span id="perform_headhunting_goal"></span></td>
                        <td><span id="perform_headhunting_actual"></span></td>
                    </tr>
                    <tr>
                        <td>IPEAs Scheduled</td>
                        <td><span id="perform_ipea"></span></td>
                        <td>{{$recruiterPossiblePer['perform_ipea']}}%</td>
                        <td><span id="perform_ipea_goal"></span></td>
                        <td><span id="perform_ipea_actual"></span></td>
                    </tr>
                    <tr>
                        <td>IPEA Conversion</td>
                        <td><span id="perform_conversion"></span></td>
                        <td>{{$recruiterPossiblePer['perform_conversion']}}%</td>
                        <td><span id="perform_conversion_goal"></span></td>
                        <td><span id="perform_conversion_actual"></span></td>
                    </tr>
                    <tr>
                        <td>PEPIs / WEPIs Scheduled</td>
                        <td><span id="perform_pepiwepi"></span></td>
                        <td>{{$recruiterPossiblePer['perform_pepiwepi']}}%</td>
                        <td><span id="perform_pepiwepi_goal"></span></td>
                        <td><span id="perform_pepiwepi_actual"></span></td>
                    </tr>
                    <tr>
                        <td>Client Match Hours</td>
                        <td><span id="perform_hrsremoved"></span></td>
                        <td>{{$recruiterPossiblePer['perform_hrsremoved']}}%</td>
                        <td><span id="perform_hrsremoved_goal"></span></td>
                        <td><span id="perform_hrsremoved_actual"></span></td>
                    </tr>
                    <tr>
                        <td>Candidate Start Hours</td>
                        <td><span id="perform_first_shift_worked"></span></td>
                        <td>{{$recruiterPossiblePer['perform_first_shift_worked']}}%</td>
                        <td><span id="perform_first_shift_worked_goal"></span></td>
                        <td><span id="perform_first_shift_worked_actual"></span></td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Performance Total</td>
                        <td><span id="performanceTotalPer"></span></td>
                        <td>{{$performanceTotalPer}}%</td>
                        <td></td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </section>
        <section class="phone-screen-detail showbasedonrecruiter">
            <table class="table table-bordered" aria-describedby="">
                <thead>
                    <tr>
                        <th colspan="4">New Phone Call Screening (Weighs {{$phoneScreenTotalPer}}%)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Tone and Confidence</td>
                        <td>User applies good tone when speaking on call and handles call with confidence</td>
                        <td>
                            <select name="phone_screen_tone_confidence" id="phone_screen_tone_confidence" class="form-control">
                                @foreach($phoneScreenOptions as $value => $title)
                                    <option value="{{$value}}">{{$title}}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>AOC Values</td>
                        <td>User discusses AOC values including HALOs</td>
                        <td>
                            <select name="phone_screen_aoc_values" id="phone_screen_aoc_values" class="form-control">
                                @foreach($phoneScreenOptions as $value => $title)
                                    <option value="{{$value}}">{{$title}}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Case Specifics</td>
                        <td>User discusses case specifics including client's address, T/V client, pets, lifting, etc.</td>
                        <td>
                            <select name="phone_screen_case_specifics" id="phone_screen_case_specifics" class="form-control">
                                @foreach($phoneScreenOptions as $value => $title)
                                    <option value="{{$value}}">{{$title}}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>HH Nurse Role</td>
                        <td>User explains the role of a pediatric home health nurse and what is expected of them</td>
                        <td>
                            <select name="phone_screen_hh_nurse_role" id="phone_screen_hh_nurse_role" class="form-control">
                                @foreach($phoneScreenOptions as $value => $title)
                                    <option value="{{$value}}">{{$title}}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Pay and Benefits</td>
                        <td>User discusses the pay and benefits of the case and company answering any questions candidate has</td>
                        <td>
                            <select name="phone_screen_pay_and_benefits" id="phone_screen_pay_and_benefits" class="form-control">
                                @foreach($phoneScreenOptions as $value => $title)
                                    <option value="{{$value}}">{{$title}}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>Clear expectations of next steps</td>
                        <td>User gives candidate clear expectations of what is to come next</td>
                        <td>
                            <select name="phone_screen_clear_expectations_next_steps" id="phone_screen_clear_expectations_next_steps" class="form-control">
                                @foreach($phoneScreenOptions as $value => $title)
                                    <option value="{{$value}}">{{$title}}</option>
                                @endforeach
                            </select>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td></td>
                        <td>Rating Average: <span id="phoneScreenAvg"></span></td>
                    </tr>
                </tfoot>
            </table>
        </section>
        <section class="influence-leadership-detail showbasedonrecruiter">
            <table class="table table-bordered" aria-describedby="">
                <thead>
                    <tr>
                        <th style="width: 60%;">Influence and Leadership - HALOS (Weighs {{$influenceTotalPer}}%)</th>
                        <th style="width: 20%;">%</th>
                        <th style="width: 20%;">Possible %</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Phone Call Screening Score</td>
                        <td><span id="phone_call_screening_score"></span></td>
                        <td>{{$recruiterPossiblePer['phone_call_screening_score']}}%</td>
                    </tr>
                    <tr>
                        <td>Attendance and Dependability</td>
                        <td>
                            <span id="attendance_and_dependability" class="editable-scorecard" data-emptytext='Click to add'
                            data-type='text' data-url='{{route("editable")}}'
                            data-pk="" data-name='attendance_and_dependability'
                            data-table='recruiter_scorecards'></span>
                        </td>
                        <td>{{$recruiterPossiblePer['attendance_and_dependability']}}%</td>
                    </tr>
                    <tr>
                        <td>Professionalism (with clients and co-workers)</td>
                        <td>
                            <span id="professionalism" class="editable-scorecard" data-emptytext='Click to add'
                            data-type='text' data-url='{{route("editable")}}'
                            data-pk="" data-name='professionalism'
                            data-table='recruiter_scorecards'></span>
                        </td>
                        <td>{{$recruiterPossiblePer['professionalism']}}%</td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>Influence and Leadership Total</td>
                        <td><span id="influenceTotalPer"></span></td>
                        <td>{{$influenceTotalPer}}%</td>
                    </tr>
                </tfoot>
            </table>
        </section>
        @if($viewMode != 'view')
        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <button
                    class="btn btn-primary load"
                    onclick="loadRecruiterScorecardData($('#recruiter_id').val(), event, this);" disabled>Load</button>
                <button class="btn btn-primary submit" onclick="submitRecruiterScorecard(event, this);" style="display: none;">Submit</button>
            </div>
        </div>
        @endif
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>

<script type="text/javascript">
    function submitRecruiterScorecard(e, button) {
        e.preventDefault();
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = {};
        data['id'] = $('#scorecardid').val();
        data['_token'] = _token;
        $.ajax({
            url: "{{route('submitRecruiterScorecard')}}",
            type: 'post',
            data: data,
            success: function(data) {
                button.disabled = false;
                if (data.status) {
                    $('#recruiterScorecardFormModal').modal('hide');
                    $('#dataTable').DataTable().ajax.reload();
                }
            }
        });
    }

    function loadRecruiterScorecardData(id, e = null, button = null, mode = 'add') {
        if (e != null)
            e.preventDefault();
        if (button != null)
            button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = {};
        data['id'] = id;
        data['_token'] = _token;
        data['mode'] = mode;
        $('button.load').html('<i class="fa fa-spinner fa-spin"></i>Loading');
        $.ajax({
            url: "{{route('getRecruiterscorecardDataByRecruiter')}}",
            type: 'post',
            data: data,
            success: function(data) {
                if (button != null)
                    button.disabled = false;
                let element = [
                    'skills_nth_checklists_worked',
                    'skills_jobvite',
                    'perform_ipea',
                    'perform_conversion',
                    'perform_pepiwepi',
                    'perform_hrsremoved',
                    'perform_first_shift_worked',
                    'phone_call_screening_score'
                ];
                let editableElement = [
                    'skills_recruiter_dashboard',
                    'perform_headhunting',
                    'attendance_and_dependability',
                    'professionalism',
                ];
                let goalElements = [
                    'perform_headhunting_goal',
                    'perform_ipea_goal',
                    'perform_conversion_goal',
                    'perform_pepiwepi_goal',
                    'perform_hrsremoved_goal',
                    'perform_first_shift_worked_goal',
                    'perform_headhunting_actual',
                    'perform_ipea_actual',
                    'perform_conversion_actual',
                    'perform_pepiwepi_actual',
                    'perform_hrsremoved_actual',
                    'perform_first_shift_worked_actual'
                ]
                let selectElements = [
                    'phone_screen_tone_confidence',
                    'phone_screen_aoc_values',
                    'phone_screen_case_specifics',
                    'phone_screen_hh_nurse_role',
                    'phone_screen_pay_and_benefits',
                    'phone_screen_clear_expectations_next_steps'
                ]
                $.each( data.data, function( key, val ) {
                    if (element.indexOf(key) != -1) {
                        $('span#' + key).text((+Number(val)).toFixed(2).replace(/([0-9]+(\.[0-9]+[1-9])?)(\.?0+$)/,'$1') + '%');
                    }
                    if (goalElements.indexOf(key) != -1) {
                        $('span#' + key).text((+Number(val)).toFixed(2).replace(/([0-9]+(\.[0-9]+[1-9])?)(\.?0+$)/,'$1'));
                    }
                    if (selectElements.indexOf(key) != -1) {
                        $('#' + key).val((+Number(val)).toFixed(2).replace(/([0-9]+(\.[0-9]+[1-9])?)(\.?0+$)/,'$1'));
                    }
                    if (editableElement.indexOf(key) != -1) {
                        if (data.mode != 'view') {
                            $('span#' + key).editable('option', 'pk', data.id);
                            $('span#' + key).attr('data-maxperlimmit', data.recruiterPossiblePer[key]);
                            if (val > 0)
                            {
                                $('span#' + key).editable('option', 'value', (+Number(val)).toFixed(2).replace(/([0-9]+(\.[0-9]+[1-9])?)(\.?0+$)/,'$1'));
                                $('span#' + key).parent().find('span.persign').remove();
                                $('<span class="persign">%</span>').insertAfter($('span#' + key));
                            }
                        } else {
                            $('span#' + key).editable('option', 'disabled', 1);
                            $('span#' + key).text((+Number(val)).toFixed(2).replace(/([0-9]+(\.[0-9]+[1-9])?)(\.?0+$)/,'$1') + '%');
                            $('.phone-screen-detail select').attr('disabled', 'disabled');
                        }
                    }
                });
                $('#recruiter_id').attr('disabled','disabled');
                $('#scorecardid').val(data.id);
                $('span#utilityTotalPer').text(data.utilityTotalPer + '%');
                $('span#performanceTotalPer').text(data.performanceTotalPer + '%');
                $('span#influenceTotalPer').text(data.influenceTotalPer + '%');
                $('span#phoneScreenAvg').text(data.phoneScreenAvg);
                $('span#grade').text(data.data.grade);
                $('span#score').text(data.data.score ? (+Number(data.data.score)).toFixed(2).replace(/([0-9]+(\.[0-9]+[1-9])?)(\.?0+$)/,'$1') + "%" : '');
                $('span#action_item').text(data.data.action);
                $('.showbasedonrecruiter').show();
                $('span#scorecard_date').text(data.data.scorecard_date);
                if (data.mode == 'edit') {
                    $('#recruiter_id').val(data.data.recruiter_id);
                } else {
                    $('span#recruiter_id').text(data.data.recruiter_name);
                    $('span#title_recruiter_id').text(data.data.recruiter_name);
                }
                $('span#manager_id').text(data.data.manager_name);

                if (data.mode == 'add') {
                    $('#dataTable').DataTable().ajax.reload();
                }

                if (data.mode != 'view') {
                    $('button.load').hide();
                    $('button.submit').show();
                } else {
                    $('button.load').hide();
                    $('button.submit').hide();
                }

                $('button.load').html('Load');
            }
        });
    }

    $('#recruiter_id').change(function(){
        if ($(this).val() != '') {
            $('button.load').removeAttr('disabled');
        } else {
            $('button.load').attr('disabled', 'disabled');
            $('button.load').show();
            $('button.submit').hide();
            $('.showbasedonrecruiter').hide();
        }
    });

    $('.phone-screen-detail select').change(function() {
        $('.phone-screen-detail select').attr('disabled', 'disabled');
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = {};
        data['name'] = $(this).attr('name');
        data['value'] = $(this).val();
        data['table'] = 'recruiter_scorecards';
        data['pk'] = $('#scorecardid').val();
        data['_token'] = _token;
        $.ajax({
            url: "{{route('editable')}}",
            type: 'post',
            data: data,
            success: function(data) {
                if (data.success) {
                    loadRecruiterScorecardData($('#scorecardid').val(),null,null,'edit');
                    $('.phone-screen-detail select').removeAttr('disabled');
                }
            }
        });
    });
</script>
