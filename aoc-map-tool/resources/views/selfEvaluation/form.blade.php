<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $openMode = '';
    $isReadOnly = "";
    if(isset($mode) && $mode=='view') {
        $openMode = $mode;
        $isReadOnly = 'disabled="disabled"';
    }

    if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
        $clinicianData->HiredDate = (
            !empty($clinicianData->RehireDate) &&
            $clinicianData->RehireDate != '0000-00-00' &&
            $clinicianData->RehireDate != '1900-01-01'
        ) ? $clinicianData->RehireDate : $clinicianData->HiredDate;
    }

    $evaluationYear = date('Y', strtotime($selfEvaluationData->created_at));
    $startDate = date('Y-m-d', strtotime(($evaluationYear - 1) . '-06-01'));

    //Specifically for the year 2026 was decided to shrink evaluation period to endDate 02-28,
    //any other year will stay 05-31
    $endDate = date('Y-m-d', strtotime($evaluationYear . ($evaluationYear === '2026' ? '-02-28' : '-05-31')));
    $hiredDate = date('Y-m-d', strtotime($clinicianData->HiredDate));
?>
<x-modal>
    <x-slot name="id">selfEvaluationFormModal</x-slot>
    <x-slot name="size">w60</x-slot>
        <x-slot name="title">Self Evaluation</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="body">
    <form id="selfEvaluationForm" class="selfEvaluationForm">
        <input type="hidden" id="id" name="id" value="{{$selfEvaluationData->id}}"/>
        <input type="hidden" id="provider_id" name="provider_id" value="{{$clinicianData->id}}"/>
        <section class="employee-details mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Employee Information </h5>
            </header>
            <div class="col-md-12 d-flex p-0">
                <div class="col-md-6 p-0">
                    <div class="col-md-12 p-0">
                        <label class="col-sm-4 p-0 text-right">Employee Reviewed:</label>
                        <span class="col-sm-8 text-left">{{$clinicianData->FirstName . ' ' . $clinicianData->LastName}}</span>
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
                            H::getFormatedDate($startDate). ' - ' . H::getFormatedDate($endDate)
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
            <p>These core competencies have been identified by the company  that leads to success in one's position. Using the 1 - 5 scale, rate the level of each competency for the individual.</p>
            <table class="table table-bordered" aria-describedby="">
                <thead>
                    <tr>
                        <th style="width: 10%;vertical-align: top;">Competencies</th>
                        <th style="width: 80%;vertical-align: top;">These factors broadly measure overall job competency for all employees.</th>
                        <th style="width: 10%;vertical-align: top;">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th>Accountability</th>
                        <td>Effectively performs job while using company time and resources efficiently; meets deadlines; contributes to the success of the department and organization. Meets attendance and/or deliverable requirements.</td>
                        <td>
                            <select name="evaluation[item1_score]" id="item1_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item1_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item1_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item1_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item1_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($selfEvaluationData->item1_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Initiative</th>
                        <td>Assumes ownership and completes work with minimal supervision and without prompting.</td>
                        <td>
                            <select name="evaluation[item2_score]" id="item2_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item2_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item2_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item2_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item2_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($selfEvaluationData->item2_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Technical Job Knowledge</th>
                        <td>Competent in required job skills and knowledge; exhibits ability to learn and apply new skills; stays apprised of new/current developments.</td>
                        <td>
                            <select name="evaluation[item3_score]" id="item3_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item3_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item3_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item3_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item3_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($selfEvaluationData->item3_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Productivity / Quality</th>
                        <td>Ability to set goals, plan work, establish priorities, and works proactively; performs work accurately, thoroughly, and consistently demonstrates commitment to continually improve work quality. Results are consistently within acceptable quality and standards.</td>
                        <td>
                            <select name="evaluation[item4_score]" id="item4_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item4_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item4_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item4_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item4_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($selfEvaluationData->item4_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Communication and Teamwork</th>
                        <td>Clearly expresses ideas and thoughts both written and verbal; exhibits good listening and comprehension; keeps others adequately informed, selects and uses appropriate communication methods. Facilitates a positive and productive team-oriented work environment; encourages collaboration, respecting peer functions and disciplines.</td>
                        <td>
                            <select name="evaluation[item5_score]" id="item5_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item5_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item5_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item5_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item5_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($selfEvaluationData->item5_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>


                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Relationship / Customer Service Skills</th>
                        <td>Impresses clients/customers, both internal and external, with exceptional service; understands and anticipates customer needs, wants, and issues; proactively tries to build effective working relationships with others. Works effectively with others and responds positively to situations that require cooperation, courtesy, and tact. Values diverse work styles, approaches and thoughts, treating others with dignity and respect.</td>
                        <td>
                            <select name="evaluation[item6_score]" id="item6_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item6_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item6_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item6_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item6_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($selfEvaluationData->item6_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Adaptability</th>
                        <td>Adjusts to changes in work, conditions, or unusual situations quickly.</td>
                        <td>
                            <select name="evaluation[item7_score]" id="item7_score" <?php echo $isReadOnly?> class="competencies">

                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item7_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item7_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item7_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item7_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($selfEvaluationData->item7_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Judgment / Problem Solving and Decision Making</th>
                        <td>Ability to analyze problems or procedures, evaluate alternatives, and select best course of action; uses logic to solve difficult problems with effective solutions. Makes quality decisions after considering all applicable information.</td>
                        <td>
                            <select name="evaluation[item8_score]" id="item8_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item8_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item8_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item8_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item8_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($selfEvaluationData->item8_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Ethics</th>
                        <td>Demonstrates principles and sound business ethics with clear awareness of compliance and regulations for organization’s best interests; sets high standards regarding his/her work and working environment and acts accordingly. </td>
                        <td>
                            <select name="evaluation[item9_score]" id="item9_score" <?php echo $isReadOnly?> class="competencies">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item9_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item9_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item9_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item9_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>

                                <option value="1" {{($selfEvaluationData->item9_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                            </select>
                        </td>
                    </tr>
                    <?php if (LD::checkFeatureFlag('add-competencies-rating-for-halos-office-evaluation')) { ?>
                    <tr>
                        <th scope="row">HALOS</th>
                        <td>In every action taken, HALOS values are kept in mind, and used to guide decisions.</td>
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
                                <option value="5" {{($selfEvaluationData->item14_score == 5) ? 'selected' : ''}}>
                                    5 - Outstanding
                                </option>
                                <option value="4" {{($selfEvaluationData->item14_score == 4) ? 'selected' : ''}}>
                                    4 - Exceeds Expectations
                                </option>
                                <option value="3" {{($selfEvaluationData->item14_score == 3) ? 'selected' : ''}}>
                                    3 - Achieving Expectations
                                </option>
                                <option value="2" {{($selfEvaluationData->item14_score == 2) ? 'selected' : ''}}>
                                    2 - Partially Meets Expectations
                                </option>
                                <option value="1" {{($selfEvaluationData->item14_score == 1) ? 'selected' : ''}}>
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
                        <td align="right"><b>Competency Rating Average</b></td>
                        <td class="avg_competencies"></td>
                    </tr>
                </tfoot>
            </table>
        </section>
        <section class="supervisory_personnel mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Supervisory Personnel Only</h5>
            </header>
            <table class="table table-bordered" aria-describedby="">
                <thead>
                    <tr>
                        <th style="width: 10%;vertical-align: top;">Competencies</th>
                        <th style="width: 80%;vertical-align: top;"></th>
                        <th style="width: 10%;vertical-align: top;">Rating</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <th>Attracting, Developing and Retaining Talent</th>
                        <td>Identifies specific talent needs and staffs to meet them; develops employees and teams with diverse capabilities; accurately appraises employee competencies and performance;</td>
                        <td>
                            <select name="evaluation[item10_score]" id="item10_score" <?php echo $isReadOnly?> class="supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item10_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item10_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item10_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item10_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{($selfEvaluationData->item10_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                                <option value="0" {{(empty($selfEvaluationData->item10_score) || $selfEvaluationData->item10_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Financial Accountability</th>
                        <td>Makes sound financial decisions that are in the best interest of the organization while keeping within budget constraints; understands key financial indicators and uses them in planning and projecting.</td>
                        <td>
                            <select name="evaluation[item11_score]" id="item11_score" <?php echo $isReadOnly?> class="supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item11_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item11_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item11_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item11_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{($selfEvaluationData->item11_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                                <option value="0" {{(empty($selfEvaluationData->item10_score) || $selfEvaluationData->item10_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Vision / Direction Setting</th>
                        <td>Sets and communicates a clear direction to team members.</td>
                        <td>
                            <select name="evaluation[item12_score]" id="item12_score" <?php echo $isReadOnly?> class="supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item12_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item12_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item12_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item12_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{($selfEvaluationData->item12_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                                <option value="0" {{(empty($selfEvaluationData->item10_score) || $selfEvaluationData->item10_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th>Coaching</th>
                        <td>Sets clear goals, standards, and guidelines; encourages others to consistently meet and exceed performance goals.</td>
                        <td>
                            <select name="evaluation[item13_score]" id="item13_score" <?php echo $isReadOnly?> class="supervisory">
                                <option value=""> Please Select </option>
                                <option value="5" {{($selfEvaluationData->item13_score == 5) ? 'selected' : ''}}>5 - Outstanding</option>
                                <option value="4" {{($selfEvaluationData->item13_score == 4) ? 'selected' : ''}}>4 - Exceeds Expectations</option>
                                <option value="3" {{($selfEvaluationData->item13_score == 3) ? 'selected' : ''}}>3 - Achieving Expectations</option>
                                <option value="2" {{($selfEvaluationData->item13_score == 2) ? 'selected' : ''}}>2 - Partially Meets Expectations</option>
                                <option value="1" {{($selfEvaluationData->item13_score == 1) ? 'selected' : ''}}>1 - Unsatisfactory Performance</option>

                                <option value="0" {{(empty($selfEvaluationData->item10_score) || $selfEvaluationData->item10_score == 0) ? 'selected' : ''}}> N/A </option>

                            </select>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <td></td>
                        <td align="right"><b>Managerial Rating Average</b></td>
                        <td class="avg_supervisory"></td>
                    </tr>
                </tfoot>
            </table>
        </section>
        <section class="overall_performance mt-2 mb-2">
            <header class="panel-heading">
                <h5 class="panel-title">Overall Performance Summary</h5>
            </header>
            <table class="table table-bordered" aria-describedby="">
                <tbody>
                    <tr>
                        <td>
                            <span>List your most significant accomplishments or contributions since your last review.</span>
                            <textarea <?php echo $isReadOnly?> name="last_review_contributions" id="last_review_contributions" style="width:100%" placeholder="Comments">{{$selfEvaluationData->last_review_contributions}}</textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span>Since the last review period, have you successfully performed any new tasks or additional duties outside the scope of your regular responsibilities? If so, please specify.</span>
                            <textarea <?php echo $isReadOnly?> name="performed_additional_duties" id="performed_additional_duties" style="width:100%" placeholder="Comments">{{$selfEvaluationData->performed_additional_duties}}</textarea>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <span>Describe any areas you would like to focus on improving during the next year.</span>
                            <textarea <?php echo $isReadOnly?> name="areas_improve" id="areas_improve" style="width:100%" placeholder="Comments">{{$selfEvaluationData->areas_improve}}</textarea>
                        </td>
                    </tr>
                </tbody>
            </table>
        </section>

        <div class="form-group row">
            <div class="col-md-12 text-center mt-2">
                <?php if($openMode!='view') { ?>
                <button class="btn btn-primary submit" onclick="submitSelfEvaluations(event, this, 'save');">Save</button>
                <button class="btn btn-primary submit" onclick="submitSelfEvaluations(event, this, 'submit');">Submit</button>
                <?php } ?>
            </div>
        </div>
    </form>
    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
<script type="text/javascript">
    function submitSelfEvaluations(e, button, action) {
        e.preventDefault();
        button.innerHTML = 'Please wait...';
        button.disabled = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        let data = $('#selfEvaluationForm').serializeArray();
        data.push({name: "_token", value: _token});
        data.push({name: "action", value: action});
        $.ajax({
            url: "{{route('selfevaluation.save')}}",
            type: 'post',
            data: $.param(data),
            success: function(data) {
                button.disabled = false;
                if (action=='save') {
                    button.innerHTML = 'Save';
                } else {
                    button.innerHTML = 'Submit';
                }

                if (data.status) {
                    $('#selfEvaluationFormModal').modal('hide');
                }
            }
        });
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
        if (Math.ceil((selectedSectionTotal / selectedSection).toFixed(2)) > 0)
            $('#overall_performance_score').val(Math.ceil((selectedSectionTotal / selectedSection).toFixed(2)));
        else
            $('#overall_performance_score').val('');
    }

    $(document).ready(function(){
        calculateAvgCompetencies();
        calculateAvgSupervisory();

        $('select.competencies').change(function(){
            calculateAvgCompetencies();
        });

        $('select.supervisory').change(function(){
            calculateAvgSupervisory();
        });
    });
</script>
