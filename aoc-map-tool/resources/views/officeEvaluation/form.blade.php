<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $openMode = '';
    $isReadOnly = "";
    if(isset($mode) && $mode=='view') {
        $openMode = $mode;
        $isReadOnly = 'disabled="disabled"';
    }

    $user = H::getLoggedInUser();
    $userId = $user['id'];
    $isHR = H::getHelper('OfficeEvaluation')->isEvaluationHR($userId);

    if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
        $clinicianData->HiredDate = (
            !empty($clinicianData->RehireDate) &&
            $clinicianData->RehireDate != '0000-00-00' &&
            $clinicianData->RehireDate != '1900-01-01'
        ) ? $clinicianData->RehireDate : $clinicianData->HiredDate;
    }

    $evaluationYear = date('Y', strtotime($officeEvaluationData->created_at));
    $startDate = date('Y-m-d', strtotime(($evaluationYear - 1) . '-06-01'));

    //Specifically for the year 2026 was decided to shrink evaluation period to endDate 02-28,
    //any other year will stay 05-31
    $endDate = date('Y-m-d', strtotime($evaluationYear . ($evaluationYear === '2026' ? '-02-28' : '-05-31')));
    $hiredDate = date('Y-m-d', strtotime($clinicianData->HiredDate));

?>
<style>
.tracker {
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 20px 0;
}

.step {
    width: 100px;
    height: 50px;
    margin: 0 5px;
    background-color: #ccc;
    display: flex;
    justify-content: center;
    align-items: center;
    font-size: 12px;
    text-align: center;
    border-radius: 5px;
}

.step.light-blue {
    background-color: #c4dbef;
    color: black;
}

.step.dark-blue {
    background-color: #215eac;
    color: white;
}

.step.red {
    background-color: #ef335e;
    color: white;
}

</style>
<x-modal>
    <x-slot name="id">officeEvaluationFormModal</x-slot>
    <x-slot name="size">w80</x-slot>
        <x-slot name="title">Office Evaluation</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="officeEvaluationForm" class="officeEvaluationForm">
        <input type="hidden" id="id" name="id" value="{{$officeEvaluationData->id}}"/>
        <input type="hidden" id="provider_id" name="provider_id" value="{{$clinicianData->id}}"/>
        @if(LD::checkFeatureFlag('add-visual-tracker-to-top-of-Office-evaluation'))
            @if(isset($mode) && $mode == 'view')
                <div class="row justify-content-center">
                    <div class="tracker">
                        @foreach($statuses as $status)
                            <div class="step {{ $status['class'] }}">
                                {{ $status['status'] }}
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
        <section class="employee-details mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Employee Information </h5>
            </header>
            <div class="col-md-12 d-flex p-0">
                <div class="col-md-6 p-0">
                    <div class="col-md-12 p-0">
                        <label class="col-sm-4 p-0 text-right">Employee Reviewed:</label>
                        <span
                        class="col-sm-8 text-left">
                            {{$clinicianData->FirstName . ' ' . $clinicianData->LastName}}
                        </span>
                    </div>
					<div class="col-md-12 p-0">
                        <label class="col-sm-4 p-0 text-right">Supervisor Name:</label>
                        <span class="col-sm-8 text-left">{{$clinicianData->supervisor_name}}</span>
                    </div>
                    <div class="col-md-12 p-0">
                        <label class="col-sm-4 p-0 text-right">Manager Name:</label>
                        <span class="col-sm-8 text-left">{{$clinicianData->manager}}</span>
                    </div>
                    <div class="col-md-12 p-0">
                        <label class="col-sm-4 p-0 text-right">Position Title:</label>
                        <span class="col-sm-8 text-left">{{$clinicianData->user_title}}</span>
                    </div>
                    <div class="col-md-12 p-0">
                        <label class="col-sm-4 p-0 text-right">Evaluation Period:</label>
                        <span class="col-sm-8 text-left">{{ (
                            ($hiredDate >= $startDate) && ($hiredDate <= $endDate)) ?
                            H::getFormatedDate($hiredDate) . ' - ' . H::getFormatedDate($endDate) :
                            H::getFormatedDate($startDate) . ' - ' . H::getFormatedDate($endDate)
                        }}</span>
                    </div>
                </div>
                <div class="col-md-6 p-0">
                    <div class="col-md-12 p-0">
                        <h3>Rating Scale</h3>
                        <ul>
                            <li>5 - Outstanding</li>
                            <li>4 - Exceeds Expectations</li>
                            <li>3 - Achieving Expectations</li>
                            <li>2 - Partially Meets Expectations</li>
                            <li>1 - Unsatisfactory Performance </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
        <section class="competencies mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Competencies</h5>
            </header>
            <p>
                These core competencies have been identified by the company  that leads
                to success in one's position. Using the 1 - 5 scale, rate the level of
                each competency for the individual.
            </p>
            <table class="table table-bordered" summary="Competencies">
                <thead>
                    <tr>
                        <th scope="col" style="width: 10%;vertical-align: top;">Competencies</th>
                        <th scope="col" style="width: 70%;vertical-align: top;">
                            These factors broadly measure overall job competency for all employees.
                        </th>
                        <th scope="col" style="width: 10%;vertical-align: top;">Self Evaluation</th>
                        <th scope="col" style="width: 10%;vertical-align: top;">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row">Accountability</th>
                        <td>
                            Effectively performs job while using company time and
                            resources efficiently; meets deadlines; contributes to the
                            success of the department and organization. Meets attendance
                            and/or deliverable requirements.
                        </td>
                        <td>
                            <select name="selfevaluation[item1_score]"
                            id="self_item1_score" disabled="disabled" class="self_competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{
									(!empty($selfevaluation) && $selfevaluation->item1_score == 5) ?
									'selected' :
									''
								}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{
									(!empty($selfevaluation) && $selfevaluation->item1_score == 4) ?
									'selected' :
									''
								}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{
									(!empty($selfevaluation) && $selfevaluation->item1_score == 3) ?
									'selected' :
									''
								}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{
									(!empty($selfevaluation) && $selfevaluation->item1_score == 2) ?
									'selected' :
									''
								}}>
                                    2 - Partially Meets Expectations
                                </option>

                                <option value="1" {{
									(!empty($selfevaluation) && $selfevaluation->item1_score == 1) ?
									'selected' :
									''
								}}>
                                    1 - Unsatisfactory Performance
                                </option>

                            </select>
                        </td>
                        <td>
                            <select name="evaluation[item1_score]"
                            id="item1_score" <?php echo $isReadOnly?>
                            class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item1_score == 5) ? 'selected' : ''}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{($officeEvaluationData->item1_score == 4) ? 'selected' : ''}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{($officeEvaluationData->item1_score == 3) ? 'selected' : ''}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{($officeEvaluationData->item1_score == 2) ? 'selected' : ''}}>
                                    2 - Partially Meets Expectations
                                </option>

                                <option value="1" {{($officeEvaluationData->item1_score == 1) ? 'selected' : ''}}>
                                    1 - Unsatisfactory Performance
                                </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Initiative</th>
                        <td>Assumes ownership and completes work with minimal supervision and without prompting.</td>

                        <td>
                            <select name="selfevaluation[item2_score]"
                            id="self_item2_score" disabled="disabled"
                            class="self_competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{
									(!empty($selfevaluation) && $selfevaluation->item2_score == 5) ?
									'selected' :
									''
								}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{
									(!empty($selfevaluation) && $selfevaluation->item2_score == 4) ?
									'selected' :
									''
								}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{
									(!empty($selfevaluation) && $selfevaluation->item2_score == 3) ?
									'selected' :
									''
								}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{
									(!empty($selfevaluation) && $selfevaluation->item2_score == 2) ?
									'selected' :
									''
								}}>
                                    2 - Partially Meets Expectations
                                </option>

                                <option value="1" {{
									(!empty($selfevaluation) && $selfevaluation->item2_score == 1) ?
									'selected' :
									''
								}}>
                                    1 - Unsatisfactory Performance
                                </option>

                            </select>
                        </td>

                        <td>
                            <select name="evaluation[item2_score]"
                            id="item2_score" <?php echo $isReadOnly?>
                            class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item2_score == 5) ? 'selected' : ''}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{($officeEvaluationData->item2_score == 4) ? 'selected' : ''}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{($officeEvaluationData->item2_score == 3) ? 'selected' : ''}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{($officeEvaluationData->item2_score == 2) ? 'selected' : ''}}>
                                    2 - Partially Meets Expectations
                                </option>

                                <option value="1" {{($officeEvaluationData->item2_score == 1) ? 'selected' : ''}}>
                                    1 - Unsatisfactory Performance
                                </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Technical Job Knowledge</th>
                        <td>
                            Competent in required job skills and knowledge; exhibits
                            ability to learn and apply new skills; stays apprised of new/
                            current developments.
                        </td>
                        <td>
                            <select name="selfevaluation[item3_score]"
                            id="self_item3_score" disabled="disabled"
                            class="self_competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{
									(!empty($selfevaluation) && $selfevaluation->item3_score == 5) ?
									'selected' :
									''
								}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{
									(!empty($selfevaluation) && $selfevaluation->item3_score == 4) ?
									'selected' :
									''
								}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{
									(!empty($selfevaluation) && $selfevaluation->item3_score == 3) ?
									'selected' :
									''
								}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{
									(!empty($selfevaluation) && $selfevaluation->item3_score == 2) ?
									'selected' :
									''
								}}>
                                    2 - Partially Meets Expectations
                                </option>

                                <option value="1" {{
									(!empty($selfevaluation) && $selfevaluation->item3_score == 1) ?
									'selected' :
									''
								}}>
                                    1 - Unsatisfactory Performance
                                </option>

                            </select>
                        </td>

                        <td>
                            <select name="evaluation[item3_score]"
                            id="item3_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item3_score == 5) ? 'selected' : ''}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{($officeEvaluationData->item3_score == 4) ? 'selected' : ''}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{($officeEvaluationData->item3_score == 3) ? 'selected' : ''}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{($officeEvaluationData->item3_score == 2) ? 'selected' : ''}}>
                                    2 - Partially Meets Expectations
                                </option>

                                <option value="1" {{($officeEvaluationData->item3_score == 1) ? 'selected' : ''}}>
                                    1 - Unsatisfactory Performance
                                </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Productivity / Quality</th>
                        <td>
                            Ability to set goals, plan work, establish priorities, and
                            works proactively; performs work accurately, thoroughly, and
                            consistently demonstrates commitment to continually improve
                            work quality. Results are consistently within acceptable
                            quality and standards.
                        </td>

                        <td>
                            <select name="selfevaluation[item4_score]"
                            id="self_item4_score" disabled="disabled"
                            class="self_competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{
									(!empty($selfevaluation) && $selfevaluation->item4_score == 5) ?
									'selected' :
									''
								}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{
									(!empty($selfevaluation) && $selfevaluation->item4_score == 4) ?
									'selected' :
									''
								}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{
									(!empty($selfevaluation) && $selfevaluation->item4_score == 3) ?
									'selected' :
									''
								}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{
									(!empty($selfevaluation) && $selfevaluation->item4_score == 2) ?
									'selected' :
									''
								}}>
                                    2 - Partially Meets Expectations
                                </option>

                                <option value="1" {{
									(!empty($selfevaluation) && $selfevaluation->item4_score == 1) ?
									'selected' :
									''
								}}>
                                    1 - Unsatisfactory Performance
                                </option>

                            </select>
                        </td>

                        <td>
                            <select name="evaluation[item4_score]"
                            id="item4_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item4_score == 5) ? 'selected' : ''}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{($officeEvaluationData->item4_score == 4) ? 'selected' : ''}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{($officeEvaluationData->item4_score == 3) ? 'selected' : ''}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{($officeEvaluationData->item4_score == 2) ? 'selected' : ''}}>
                                    2 - Partially Meets Expectations
                                </option>

                                <option value="1" {{($officeEvaluationData->item4_score == 1) ? 'selected' : ''}}>
                                    1 - Unsatisfactory Performance
                                </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Communication and Teamwork</th>
                        <td>
                            Clearly expresses ideas and thoughts both written and verbal;
                            exhibits good listening and comprehension; keeps others
                            adequately informed, selects and uses appropriate
                            communication methods. Facilitates a positive and productive
                            team-oriented work environment; encourages collaboration,
                            respecting peer functions and disciplines.
                        </td>

                        <td>
                            <select name="selfevaluation[item5_score]" id="self_item5_score" disabled="disabled" class="self_competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{
									(!empty($selfevaluation) && $selfevaluation->item5_score == 5) ?
									'selected' :
									''
								}}>5 - Outstanding</option>
                                <option value="4" {{
									(!empty($selfevaluation) && $selfevaluation->item5_score == 4) ?
									'selected' :
									''
								}}>4 - Exceeds Expectations</option>
                                <option value="3" {{
									(!empty($selfevaluation) && $selfevaluation->item5_score == 3) ?
									'selected' :
									''
								}}>3 - Achieving Expectations</option>
                                <option value="2" {{
									(!empty($selfevaluation) && $selfevaluation->item5_score == 2) ?
									'selected' :
									''
								}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{
									(!empty($selfevaluation) && $selfevaluation->item5_score == 1) ?
									'selected' :
									''
								}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>

                        <td>
                            <select name="evaluation[item5_score]" id="item5_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item5_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($officeEvaluationData->item5_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($officeEvaluationData->item5_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($officeEvaluationData->item5_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($officeEvaluationData->item5_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Relationship / Customer Service Skills</th>
                        <td>Impresses clients/customers, both internal and external, with exceptional service; understands and anticipates customer needs, wants, and issues; proactively tries to build effective working relationships with others. Works effectively with others and responds positively to situations that require cooperation, courtesy, and tact. Values diverse work styles, approaches and thoughts, treating others with dignity and respect.</td>

                        <td>
                            <select name="selfevaluation[item6_score]" id="self_item6_score" disabled="disabled" class="self_competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{
									(!empty($selfevaluation) && $selfevaluation->item6_score == 5) ?
									'selected' :
									''
								}}>5 - Outstanding</option>
                                <option value="4" {{
									(!empty($selfevaluation) && $selfevaluation->item6_score == 4) ?
									'selected' :
									''
								}}>4 - Exceeds Expectations</option>
                                <option value="3" {{
									(!empty($selfevaluation) && $selfevaluation->item6_score == 3) ?
									'selected' :
									''
								}}>3 - Achieving Expectations</option>
                                <option value="2" {{
									(!empty($selfevaluation) && $selfevaluation->item6_score == 2) ?
									'selected' :
									''
								}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{
									(!empty($selfevaluation) && $selfevaluation->item6_score == 1) ?
									'selected' :
									''
								}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>

                        <td>
                            <select name="evaluation[item6_score]" id="item6_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item6_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($officeEvaluationData->item6_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($officeEvaluationData->item6_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($officeEvaluationData->item6_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($officeEvaluationData->item6_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Adaptability</th>
                        <td>Adjusts to changes in work, conditions, or unusual situations quickly.</td>

                        <td>
                            <select name="selfevaluation[item7_score]" id="self_item7_score" disabled="disabled" class="self_competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{
									(!empty($selfevaluation) && $selfevaluation->item7_score == 5) ?
									'selected' :
									''
								}}>5 - Outstanding</option>
                                <option value="4" {{
									(!empty($selfevaluation) && $selfevaluation->item7_score == 4) ?
									'selected' :
									''
								}}>4 - Exceeds Expectations</option>
                                <option value="3" {{
									(!empty($selfevaluation) && $selfevaluation->item7_score == 3) ?
									'selected' :
									''
								}}>3 - Achieving Expectations</option>
                                <option value="2" {{
									(!empty($selfevaluation) && $selfevaluation->item7_score == 2) ?
									'selected' :
									''
								}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{
									(!empty($selfevaluation) && $selfevaluation->item7_score == 1) ?
									'selected' :
									''
								}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>

                        <td>
                            <select name="evaluation[item7_score]" id="item7_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item7_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($officeEvaluationData->item7_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($officeEvaluationData->item7_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($officeEvaluationData->item7_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($officeEvaluationData->item7_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Judgment / Problem Solving and Decision Making</th>
                        <td>Ability to analyze problems or procedures, evaluate alternatives, and select best course of action; uses logic to solve difficult problems with effective solutions. Makes quality decisions after considering all applicable information.</td>

                        <td>
                            <select name="selfevaluation[item8_score]" id="self_item8_score" disabled="disabled" class="self_competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{
									(!empty($selfevaluation) && $selfevaluation->item8_score == 5) ?
									'selected' :
									''
								}}>5 - Outstanding</option>
                                <option value="4" {{
									(!empty($selfevaluation) && $selfevaluation->item8_score == 4) ?
									'selected' :
									''
								}}>4 - Exceeds Expectations</option>
                                <option value="3" {{
									(!empty($selfevaluation) && $selfevaluation->item8_score == 3) ?
									'selected' :
									''
								}}>3 - Achieving Expectations</option>
                                <option value="2" {{
									(!empty($selfevaluation) && $selfevaluation->item8_score == 2) ?
									'selected' :
									''
								}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{
									(!empty($selfevaluation) && $selfevaluation->item8_score == 1) ?
									'selected' :
									''
								}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>

                        <td>
                            <select name="evaluation[item8_score]" id="item8_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item8_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($officeEvaluationData->item8_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($officeEvaluationData->item8_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($officeEvaluationData->item8_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($officeEvaluationData->item8_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Ethics</th>
                        <td>Demonstrates principles and sound business ethics with clear awareness of compliance and regulations for organization’s best interests; sets high standards regarding his/her work and working environment and acts accordingly. </td>

                        <td>
                            <select name="selfevaluation[item9_score]" id="self_item9_score" disabled="disabled" class="self_competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{
									(!empty($selfevaluation) && $selfevaluation->item9_score == 5) ?
									'selected' :
									''
								}}>5 - Outstanding</option>
                                <option value="4" {{
									(!empty($selfevaluation) && $selfevaluation->item9_score == 4) ?
									'selected' :
									''
								}}>4 - Exceeds Expectations</option>
                                <option value="3" {{
									(!empty($selfevaluation) && $selfevaluation->item9_score == 3) ?
									'selected' :
									''
								}}>3 - Achieving Expectations</option>
                                <option value="2" {{
									(!empty($selfevaluation) && $selfevaluation->item9_score == 2) ?
									'selected' :
									''
								}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{
									(!empty($selfevaluation) && $selfevaluation->item9_score == 1) ?
									'selected' :
									''
								}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>

                        <td>
                            <select name="evaluation[item9_score]" id="item9_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item9_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($officeEvaluationData->item9_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($officeEvaluationData->item9_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($officeEvaluationData->item9_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($officeEvaluationData->item9_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <?php if (LD::checkFeatureFlag('add-competencies-rating-for-halos-office-evaluation')) { ?>
                    <tr>
                        <th scope="row">HALOS</th>
                        <td>In every action taken, HALOS values are kept in mind, and used to guide decisions.</td>
                        <td>
                            <select
                                name="selfevaluation[item14_score]"
                                id="item14_score"
                                disabled="disabled"
                                class="self_competencies"
                            >
                                <option value="">
                                     Please Select
                                </option>
                                <option value="5" {{(!empty($selfevaluation) && $selfevaluation->item14_score == 5) ? 'selected' : ''}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{(!empty($selfevaluation) && $selfevaluation->item14_score == 4) ? 'selected' : ''}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{(!empty($selfevaluation) && $selfevaluation->item14_score == 3) ? 'selected' : ''}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{(!empty($selfevaluation) && $selfevaluation->item14_score == 2) ? 'selected' : ''}}>
                                    2 - Partially Meets Expectations
                                </option>
                                <option value="1" {{(!empty($selfevaluation) && $selfevaluation->item14_score == 1) ? 'selected' : ''}}>
                                    1 - Unsatisfactory Performance
                                </option>
                            </select>
                        </td>

                        <td>
                            <select
                                name="evaluation[item14_score]"
                                id="item14_score"
                                <?php echo $isReadOnly?>
                                class="competencies"
                            >
                                <option value="">
                                     Please Select
                                </option>
                                <option value="5" {{($officeEvaluationData->item14_score == 5) ? 'selected' : ''}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{($officeEvaluationData->item14_score == 4) ? 'selected' : ''}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{($officeEvaluationData->item14_score == 3) ? 'selected' : ''}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{($officeEvaluationData->item14_score == 2) ? 'selected' : ''}}>
                                    2 - Partially Meets Expectations
                                </option>
                                <option value="1" {{($officeEvaluationData->item14_score == 1) ? 'selected' : ''}}>
                                    1 - Unsatisfactory Performance
                                </option>
                            </select>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td align="right">&nbsp;</td>
                        <td align="right" nowrap><b>Competency Rating Average</b>&nbsp;&nbsp;<span class="self_avg_competencies"></span></td>
                        <td align="right" nowrap><b>Competency Rating Average</b>&nbsp;&nbsp;<span class="avg_competencies"></span></td>

                    </tr>
                </tfoot>
            </table>
        </section>
        <section class="supervisory_personnel mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Supervisory Personnel Only</h5>
            </header>
            <table class="table table-bordered" summary="Supervisory Personnel Only">
                <thead>
                    <tr>
                        <th scope="col" style="width: 10%;vertical-align: top;">Competencies</th>
                        <th scope="col" style="width: 70%;vertical-align: top;"></th>
                        <th scope="col" style="width: 10%;vertical-align: top;">Self Evaluation</th>
                        <th scope="col" style="width: 10%;vertical-align: top;">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th scope="row">Attracting, Developing and Retaining Talent</th>
                        <td>Identifies specific talent needs and staffs to meet them; develops employees and teams with diverse capabilities; accurately appraises employee competencies and performance;</td>
                        <td>
                            <select name="selfevaluation[item10_score]" id="self_item10_score" disabled="disabled" class="self_supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{(!empty($selfevaluation) && $selfevaluation->item10_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{(!empty($selfevaluation) && $selfevaluation->item10_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{(!empty($selfevaluation) && $selfevaluation->item10_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{(!empty($selfevaluation) && $selfevaluation->item10_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{(!empty($selfevaluation) && $selfevaluation->item10_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>
                                <option value="0" {{(empty(!empty($selfevaluation) && $selfevaluation->item10_score) || !empty($selfevaluation) && $selfevaluation->item10_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                        <td>
                            <select name="evaluation[item10_score]" id="item10_score" <?php echo $isReadOnly?> class="supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item10_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($officeEvaluationData->item10_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($officeEvaluationData->item10_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($officeEvaluationData->item10_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{($officeEvaluationData->item10_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                                <option value="0" {{(empty($officeEvaluationData->item10_score) || $officeEvaluationData->item10_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Financial Accountability</th>
                        <td>Makes sound financial decisions that are in the best interest of the organization while keeping within budget constraints; understands key financial indicators and uses them in planning and projecting.</td>
                        <td>
                            <select name="selfevaluation[item11_score]" id="self_item11_score" disabled="disabled" class="self_supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{(!empty($selfevaluation) && $selfevaluation->item11_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{(!empty($selfevaluation) && $selfevaluation->item11_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{(!empty($selfevaluation) && $selfevaluation->item11_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{(!empty($selfevaluation) && $selfevaluation->item11_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{(!empty($selfevaluation) && $selfevaluation->item11_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>
                                <option value="0" {{(empty(!empty($selfevaluation) && $selfevaluation->item11_score) || !empty($selfevaluation) && $selfevaluation->item11_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                        <td>
                            <select name="evaluation[item11_score]" id="item11_score" <?php echo $isReadOnly?> class="supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item11_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($officeEvaluationData->item11_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($officeEvaluationData->item11_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($officeEvaluationData->item11_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{($officeEvaluationData->item11_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                                <option value="0" {{(empty($officeEvaluationData->item11_score) || $officeEvaluationData->item11_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Vision / Direction Setting</th>
                        <td>Sets and communicates a clear direction to team members.</td>
                        <td>
                            <select name="selfevaluation[item12_score]" id="self_item12_score" disabled="disabled" class="self_supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{(!empty($selfevaluation) && $selfevaluation->item12_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{(!empty($selfevaluation) && $selfevaluation->item12_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{(!empty($selfevaluation) && $selfevaluation->item12_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{(!empty($selfevaluation) && $selfevaluation->item12_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{(!empty($selfevaluation) && $selfevaluation->item12_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>
                                <option value="0" {{(empty(!empty($selfevaluation) && $selfevaluation->item12_score) || !empty($selfevaluation) && $selfevaluation->item12_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                        <td>
                            <select name="evaluation[item12_score]" id="item12_score" <?php echo $isReadOnly?> class="supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item12_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($officeEvaluationData->item12_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($officeEvaluationData->item12_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($officeEvaluationData->item12_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{($officeEvaluationData->item12_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                                <option value="0" {{(empty($officeEvaluationData->item12_score) || $officeEvaluationData->item12_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">Coaching</th>
                        <td>Sets clear goals, standards, and guidelines; encourages others to consistently meet and exceed performance goals.</td>
                        <td>
                            <select name="selfevaluation[item13_score]" id="self_item13_score" disabled="disabled" class="self_supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{(!empty($selfevaluation) && $selfevaluation->item13_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{(!empty($selfevaluation) && $selfevaluation->item13_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{(!empty($selfevaluation) && $selfevaluation->item13_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{(!empty($selfevaluation) && $selfevaluation->item13_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{(!empty($selfevaluation) && $selfevaluation->item13_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>
                                <option value="0" {{(empty(!empty($selfevaluation) && $selfevaluation->item13_score) || !empty($selfevaluation) && $selfevaluation->item13_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                        <td>
                            <select name="evaluation[item13_score]" id="item13_score" <?php echo $isReadOnly?> class="supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{($officeEvaluationData->item13_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($officeEvaluationData->item13_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($officeEvaluationData->item13_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($officeEvaluationData->item13_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{($officeEvaluationData->item13_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                                <option value="0" {{(empty($officeEvaluationData->item13_score) || $officeEvaluationData->item13_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <td align="right"><b>Managerial Rating Average</b>&nbsp;&nbsp; <span class="self_avg_supervisory"></span></td>
                        <td align="right"><b>Managerial Rating Average</b>&nbsp;&nbsp; <span class="avg_supervisory"></span></td>

                    </tr>
                </tfoot>
            </table>
        </section>
        <section class="strengths mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Strengths</h5>
            </header>
            <p>Provide areas of strength demonstrated over the last year.</p>
            <table class="table table-bordered" summary="Provide areas of strength">
                <tbody>
                    <tr>
                        <td><textarea <?php echo $isReadOnly?> name="strengths"  id="strengths" style="width:100%" placeholder="Comments">{{$officeEvaluationData->strengths}}</textarea></td>
                    </tr>
                </tbody>
            </table>
        </section>
        <section class="development_areas mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Development Areas</h5>
            </header>
            <p>The development areas may be for the purpose of improvement in employee's current job, for the preparation of possible advancement, or job enrichment. The information provided will support the creation of employee's development plan.</p>
            <table class="table table-bordered" summary="Development areas">
                <tbody>
                    <tr>
                        <td><textarea <?php echo $isReadOnly?> name="development_areas"  id="development_areas" style="width:100%" placeholder="Comments">{{$officeEvaluationData->development_areas}}</textarea></td>
                    </tr>
                </tbody>
            </table>
        </section>
        <?php if (LD::checkFeatureFlag('add-input-goal-setting-professional-development-office-evaluation')) { ?>
            <section class="goal_setting_professional_development mt-2 mb-2">
                <header class="panel-heading">
                    <h5 class="panel-title">Goal Setting and Professional Development Plan</h5>
                </header>
                <p></p>
                <table class="table table-bordered" summary="Goal Setting and Professional Development Plan" aria-hidden="true">
                    <tbody>
                        <tr>
                            <td>
                                <textarea
                                    <?php echo $isReadOnly?>
                                    name="goal_setting_professional_development"
                                    id="goal_setting_professional_development"
                                    style="width:100%"
                                    placeholder="Comments">{{$officeEvaluationData->goal_setting_professional_development}}</textarea>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </section>
        <?php } ?>
        <section class="compliance mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Compliance</h5>
            </header>
            <table class="table table-bordered" summary="Compliance">
                <tbody>
                    <tr>
                        <th scope="row" style="width: 80%;">Has the employee completed all training required for their position w/in the relevant deadlines?</th>
                        <td style="width: 10%;"><input <?php echo $isReadOnly?> type="radio" name="completed_all_training" id="completed_all_training_1" value="1" {{($officeEvaluationData->completed_all_training == 1) ? 'checked' : ''}}> Yes</td>
                        <td style="width: 10%;"><input <?php echo $isReadOnly?> type="radio" name="completed_all_training" id="completed_all_training_2" value="2" {{($officeEvaluationData->completed_all_training == 2) ? 'checked' : ''}}> No</td>
                    </tr>
                    <tr>
                        <th scope="row" style="width: 80%;">Has the employee been the subject of a compliance incident?</th>
                        <td style="width: 10%;"><input <?php echo $isReadOnly?> type="radio" name="subject_of_compliance" id="subject_of_compliance_1" value="1" {{($officeEvaluationData->subject_of_compliance == 1) ? 'checked' : ''}}> Yes</td>
                        <td style="width: 10%;"><input <?php echo $isReadOnly?> type="radio" name="subject_of_compliance" id="subject_of_compliance_2" value="2" {{($officeEvaluationData->subject_of_compliance == 2) ? 'checked' : ''}}> No</td>
                    </tr>
                    <tr>
                        <th scope="row" style="width: 80%;">Has the employee demonstrated a commitment to compliance with all federal, state and local laws and regulations applicable to their position within the organization?</th>
                        <td style="width: 10%;"><input <?php echo $isReadOnly?> type="radio" name="commitment_to_compliance" id="commitment_to_compliance_1" value="1" {{($officeEvaluationData->commitment_to_compliance == 1) ? 'checked' : ''}}> Yes</td>
                        <td style="width: 10%;"><input <?php echo $isReadOnly?> type="radio" name="commitment_to_compliance" id="commitment_to_compliance_2" value="2" {{($officeEvaluationData->commitment_to_compliance == 2) ? 'checked' : ''}}> No</td>
                    </tr>
                </tbody>
            </table>
        </section>
        <section class="overall_performance mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Overall Performance Summary</h5>
            </header>
            <p>Please provide final overall rating with a summary of performance, including any comments to the various sections:</p>
            <table class="table table-bordered" summary="Overall Performance Summary" aria-hidden="true">
                <tbody>
                    <tr>
                        <td style="width:90%">
                            <textarea
                                <?php echo $isReadOnly?>
                                name="overall_performance"
                                id="overall_performance"
                                style="width:100%"
                                placeholder="Comments">{{$officeEvaluationData->overall_performance}}</textarea>
                        </td>
                        <td style="width:10%">
                            <input type="hidden" name="overall_performance_score" <?php echo $isReadOnly?> id="overall_performance_score" value="{{$officeEvaluationData->overall_performance_score}}" />
                            <span class="overall_performance_score_span"></span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <?php if($openMode=='view') { ?>
        <section class="evaluation_notes mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Manager/HR Comments</h5>
            </header>
            <table class="table table-bordered" summary="Manager HR Comment" aria-hidden="true">
                <tbody>
                    <tr>
                        <?php foreach($evaluationnotes as $note) { ?>
                        <tr>
                            <td><?php echo $note->notes?></td>
                            <td><?php echo $note->name?></td>
                            <td><?php echo H::getFormatedDate($note->created_at).' '.H::getFormatedTime($note->created_at)?></td>
                        </tr>
                        <?php } ?>
                    </tr>
                </tbody>
            </table>
        </section>

        <hr>
        <?php } ?>

        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <?php if($openMode!='view') { ?>
                <button class="btn btn-primary save" id="savebtn" onclick="submitOfficeEvaluations(event, this, 'save');">Save</button>
                <button class="btn btn-primary submit" id="submitbtn" onclick="submitOfficeEvaluations(event, this, 'submit');">Submit</button>
                <?php } ?>



                <?php if($openMode=='view') { ?>

                <?php  if(($clinicianData->supervisor_id==$userId) && $officeEvaluationData->status == 'Pending Employee Review'){ ?>

                    <button class="btn btn-primary submit" onclick="submitOfficeEvaluations(event, this, 'signature');">Send for Signature</button>

                     <?php } ?>


                     <button class="btn btn-primary submit" type="button" onclick="window.location.href='{{route('officeevaluation.downloadpdf')}}?id={{$officeEvaluationData->id}}'">Download</button>


                <?php
                 if(($isHR && $officeEvaluationData->status == 'Pending HR Approval') || (($clinicianData->manager_id == $userId) && $officeEvaluationData->status == 'Pending Manager Approval') ) { ?>
                    <button class="btn btn-primary submit" onclick="approveEvaluation(event, '{{$officeEvaluationData->id}}');">Approve</button>
                    <button class="btn btn-primary submit" onclick="openEvaluationNotes(event, '{{$officeEvaluationData->id}}');">Request Changes</button>
                <?php } ?>
                <?php }  ?>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script type="text/javascript">
        var validator = $('#officeEvaluationForm').validate(
                        {   rules:
                            {   strengths:{ required:true },
                                development_areas:{ required:true },
                                overall_performance:{ required:true },
                                completed_all_training:{ required:true },
                                subject_of_compliance:{ required:true },
                                commitment_to_compliance:{ required:true },
                            },
                            errorPlacement: function (error, element) {
                                var name = $(element).attr("name");
                                if (
                                    name == 'completed_all_training' ||
                                    name == 'subject_of_compliance' ||
                                    name == 'commitment_to_compliance'
                                ) {
                                    $('input[name="' + $(element).attr('name') + '"]').off().click(function(){
                                        $('tr.radioerror'+$(element).attr('name')).remove();
                                    });
                                    $('tr.radioerror'+$(element).attr('name')).remove();
                                    $('<tr class="radioerror' + $(element).attr('name') + '"><td></td><td colspan="2" style="padding:0px;">' + error[0].outerHTML + '</td></tr>').insertAfter($(element).parent().parent());
                                }
                            },
                        }
                    );

    function submitOfficeEvaluations(e, button, action) {
        e.preventDefault();
        var isValidated = true;

        if(action=='submit') {
            if(!$('#officeEvaluationForm').valid()) {
                isValidated = false;
            }
        } else {
            validator.resetForm();
            isValidated=true;
        }



        if(isValidated){

            button.innerHTML = 'Please wait...';
            button.disable = true;
            let _token = $('meta[name="csrf-token"]').attr('content');
            let data = $('#officeEvaluationForm').serializeArray();
            data.push({name: "_token", value: _token});
            data.push({name: "action", value: action});
            $.ajax({
                url: "{{route('officeevaluation.save')}}",
                type: 'post',
                data: $.param(data),
                success: function(data) {
                    button.disable = false;
                    if(action=='signature') {
                        button.innerHTML = 'Send for Signature';
                    } else if(action=='save') {
                        button.innerHTML = 'Save';
                    } else {
                        button.innerHTML = 'Submit';
                    }

                    if (data.status) {
                        $('#officeEvaluationFormModal').modal('hide');
                        $('#dataTable').DataTable().ajax.reload();
                        $('#dataTable-leadership-office_evaluation').DataTable().ajax.reload();
                    }
                }
            });
        }
    }




    function calculateAvgCompetencies() {
        var total = 0;
        var selectedEle = 0;
        $('select.competencies').each(function() {
            if ($(this).find('option:selected').val() != "") {
                total += parseInt($(this).find('option:selected').val());
                selectedEle++;
            }
        });
        if (selectedEle == $('select.competencies').length) {
            $('.avg_competencies').html((total / $('select.competencies').length).toFixed(2));
        } else {
            $('.avg_competencies').html('N/A');
        }
        calculateFinalAvg();
    }

    function selfcalculateAvgCompetencies() {
        var total = 0;
        var selectedEle = 0;
        $('select.self_competencies').each(function() {
            if ($(this).find('option:selected').val() != "") {
                total += parseInt($(this).find('option:selected').val());
                selectedEle++;
            }
        });
        if (selectedEle == $('select.self_competencies').length) {
            $('.self_avg_competencies').html((total / $('select.self_competencies').length).toFixed(2));
        } else {
            $('.self_avg_competencies').html('N/A');
        }
        //calculateFinalAvg();
    }

    function calculateAvgSupervisory() {
        var total = 0;
        var selectedEle = 0;
        $('select.supervisory').each(function() {
            if ($(this).find('option:selected').val() != "" && $(this).find('option:selected').val() != 0) {
                total += parseInt($(this).find('option:selected').val());
                selectedEle++;
            }
        });
        if (selectedEle == $('select.supervisory').length) {
            $('.avg_supervisory').html((total / $('select.supervisory').length).toFixed(2));
        } else {
            $('.avg_supervisory').html('N/A');
        }
        calculateFinalAvg();
    }
    function selfcalculateAvgSupervisory() {
        var total = 0;
        var selectedEle = 0;
        $('select.self_supervisory').each(function() {
            if ($(this).find('option:selected').val() != "" && $(this).find('option:selected').val() != 0) {
                total += parseInt($(this).find('option:selected').val());
                selectedEle++;
            }
        });
        if (selectedEle == $('select.supervisory').length) {
            $('.self_avg_supervisory').html((total / $('select.self_supervisory').length).toFixed(2));
        } else {
            $('.self_avg_supervisory').html('N/A');
        }
        //calculateFinalAvg();
    }

    function calculateFinalAvg() {
        selectedSection = 0;
        selectedSectionTotal = 0;
        if (parseFloat($('.avg_competencies').html()) > 0) {
            selectedSectionTotal += parseFloat($('.avg_competencies').html());
            selectedSection++;
        }
        if (parseFloat($('.avg_supervisory').html()) > 0) {
            selectedSectionTotal += parseFloat($('.avg_supervisory').html());
            selectedSection++;
        }
        var overallPerformanceScore = (selectedSectionTotal / selectedSection).toFixed(2);
        if (overallPerformanceScore > 0) {
            $('#overall_performance_score').val(overallPerformanceScore);
            $('.overall_performance_score_span').text(overallPerformanceScore);
        } else {
            $('#overall_performance_score').val('');
            $('.overall_performance_score_span').text('');
        }
    }

    function openEvaluationNotes(e, id) {
        e.preventDefault();
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('officeevaluation.getnotesform')}}",
            type: 'get',
            dateType:"html",
            data: {
                _token: _token,
                id: id
            },
            success: function(data) {
                $(".modal-backdrop").remove();
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#officeEvaluationnotes').modal('show');
            }
        });
    }

    function approveEvaluation(e, id) {
        e.preventDefault();
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('officeevaluation.approvalform')}}",
            type: 'get',
            dateType:"html",
            data: {
                _token: _token,
                id: id
            },
            success: function(data) {
                $(".modal-backdrop").remove();
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#approveevaluation').modal('show');
            }
        });
    }

    $(document).ready(function(){
        calculateAvgCompetencies();
        selfcalculateAvgCompetencies();
        calculateAvgSupervisory();
        selfcalculateAvgSupervisory();

        $('select.competencies').change(function(){
            calculateAvgCompetencies();
        });

        $('select.supervisory').change(function(){
            calculateAvgSupervisory();
        });
    });
</script>
