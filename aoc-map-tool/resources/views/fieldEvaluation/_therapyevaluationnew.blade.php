<?php
    use App\Helpers\H;
    use App\Helpers\LD;

    if(LD::checkFeatureFlag('change-logic-to-consider-rehire-date')) {
        $clinicianData->HiredDate = (
            !empty($clinicianData->RehireDate) &&
            $clinicianData->RehireDate != '0000-00-00' &&
            $clinicianData->RehireDate != '1900-01-01'
        ) ? $clinicianData->RehireDate : $clinicianData->HiredDate;
    }
?>
<section class="employee-details mt-2 mb-2">
    <header class="panel-heading">
        <h5 class="panel-title">Employee Information</h5>
    </header>
    <div class="col-md-12 d-flex p-0">
        <div class="col-md-6 p-0">
            <label class="col-sm-4 p-0 text-right">Name:</label>
            <span class="col-sm-8 text-left">{{$clinicianData->FirstName . ' ' . $clinicianData->LastName}}</span>
        </div>
        <div class="col-md-6 p-0">
            <label class="col-sm-4 p-0 text-right">Hire Date:</label>
            <span class="col-sm-8 text-left">{{H::getFormatedDate($clinicianData->HiredDate)}}</span>
        </div>
    </div>
    <div class="col-md-12 d-flex p-0">
        <div class="col-md-6 p-0">
            <label class="col-sm-4 p-0 text-right">Job Title:</label>
            <span class="col-sm-8 text-left">{{$clinicianData->DisciplineName}}</span>
        </div>
        <div class="col-md-6 p-0">
            <label class="col-sm-4 p-0 text-right">Productivity:</label>
            <span class="col-sm-8 text-left">
                <input type="text" name="productivity"
                id="productivity"
                value="{{$fieldEvaluationData->productivity}}"/>
            </span>
        </div>
    </div>
    <div class="col-md-12 d-flex p-0">
        <div class="col-md-6 p-0">
            <label class="col-sm-4 p-0 text-right">Branch Office:</label>
            <span class="col-sm-8 text-left">{{$clinicianData->BranchName}}</span>
        </div>
        <div class="col-md-6 p-0">
            <label class="col-sm-4 p-0 text-right">IE/RE Sub. Time:</label>
            <span class="col-sm-8 text-left">
                <input type="text" name="ie_re_time"
                id="ie_re_time"
                value="{{$fieldEvaluationData->ie_re_time}}"/>
            </span>
        </div>
    </div>
    <div class="col-md-12 d-flex p-0">
        <div class="col-md-6 p-0">
            <label class="col-sm-4 p-0 text-right">Review Period:</label>
            <span class="col-sm-8 text-left">
                {{H::getFormatedDate(date('Y', strtotime("-1 year")) .'-'. date('m-d', strtotime($clinicianData->HiredDate))) . ' - ' . H::getFormatedDate(date('Y') .'-'. date('m-d', strtotime("-1 day", strtotime($clinicianData->HiredDate))))}}
            </span>
        </div>
        <div class="col-md-6 p-0">
            <label class="col-sm-4 p-0 text-right">RV/SV/DC Sub. Time:</label>
            <span class="col-sm-8 text-left">
                <input type="text" name="rv_sv_dc_time"
                id="rv_sv_dc_time"
                value="{{$fieldEvaluationData->rv_sv_dc_time}}"/>
            </span>
        </div>
    </div>
</section>
<section class="ratings mt-2 mb-2">
    <header class="panel-heading">
        <h5 class="panel-title">Ratings</h5>
    </header>
    <table class="table table-bordered" aria-describedby="">
        <thead>
            <tr>
                <th scope="col" style="width: 40%;" class="text-center"></th>
                <th scope="col" style="width: 10%;" class="text-center">1</th>
                <th scope="col" style="width: 10%;" class="text-center">2</th>
                <th scope="col" style="width: 10%;" class="text-center">3</th>
                <th scope="col" style="width: 10%;" class="text-center">4</th>
                <th scope="col" style="width: 10%;" class="text-center">5</th>
                <th scope="col" style="width: 10%;" class="text-center">N/A</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th scope="row">Clinical Quality</th>
                <td>
                    <input type="radio"
                    name="ratings[item1_score]"
                    id="item1_score_1"
                    value="1" {{($fieldEvaluationData->item1_score == 1) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item1_score]"
                    id="item1_score_2"
                    value="2"
                    {{($fieldEvaluationData->item1_score == 2) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item1_score]"
                    id="item1_score_3"
                    value="3"
                    {{empty($fieldEvaluationData->item1_score) ? 'checked' : ''}}
                    {{($fieldEvaluationData->item1_score == 3) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item1_score]"
                    id="item1_score_4"
                    value="4"
                    {{($fieldEvaluationData->item1_score == 4) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item1_score]"
                    id="item1_score_5"
                    value="5"
                    {{($fieldEvaluationData->item1_score == 5) ? 'checked' : ''}}>
                </td>
                <td></td>
            </tr>
            <tr>
                <td class="text-left">
                    Clinical Quality is measured on an expectation basis under the subjection of the Clinical Manager
                </td>
                <td>Needs Improvement</td>
                <td>Below Expectations</td>
                <td>Meets Expectations</td>
                <td>Above Expectations</td>
                <td>Exceeds Expectations</td>
                <td></td>
            </tr>
            <tr>
                <th scope="row">Communication</th>
                <td>
                    <input type="radio"
                    name="ratings[item2_score]"
                    id="item2_score_1"
                    value="1"
                    {{($fieldEvaluationData->item2_score == 1) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item2_score]"
                    id="item2_score_2" value="2"
                    {{($fieldEvaluationData->item2_score == 2) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item2_score]"
                    id="item2_score_3"
                    value="3"
                    {{empty($fieldEvaluationData->item2_score) ? 'checked' : ''}}
                    {{($fieldEvaluationData->item2_score == 3) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item2_score]"
                    id="item2_score_4"
                    value="4"
                    {{($fieldEvaluationData->item2_score == 4) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item2_score]"
                    id="item2_score_5"
                    value="5"
                    {{($fieldEvaluationData->item2_score == 5) ? 'checked' : ''}}>
                </td>
                <td></td>
            </tr>
            <tr>
                <td class="text-left">
                    Communication is measured on an expectation basis under the subjection of the Clinical Manager
                </td>
                <td>Needs Improvement</td>
                <td>Below Expectations</td>
                <td>Meets Expectations</td>
                <td>Above Expectations</td>
                <td>Exceeds Expectations</td>
                <td></td>
            </tr>
            <tr>
                <th scope="row">Productivity</th>
                <td>
                    <input type="radio"
                    name="ratings[item3_score]"
                    id="item3_score_1"
                    value="1"
                    {{($fieldEvaluationData->item3_score == 1) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item3_score]"
                    id="item3_score_2"
                    value="2"
                    {{($fieldEvaluationData->item3_score == 2) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item3_score]"
                    id="item3_score_3"
                    value="3"
                    {{empty($fieldEvaluationData->item3_score) ? 'checked' : ''}}
                    {{($fieldEvaluationData->item3_score == 3) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item3_score]"
                    id="item3_score_4"
                    value="4"
                    {{($fieldEvaluationData->item3_score == 4) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item3_score]"
                    id="item3_score_5"
                    value="5"
                    {{($fieldEvaluationData->item3_score == 5) ? 'checked' : ''}}>
                </td>
                <td></td>
            </tr>
            <tr>
                <td class="text-left">
                    Productivity is the completed visits divided by the total visits within the review period
                </td>
                <td>≤ 80%</td>
                <td>81%-88%</td>
                <td>89%-92%</td>
                <td>93%-95%</td>
                <td>≥ 96%</td>
                <td></td>
            </tr>
            <tr>
                <th scope="row">IE/RE Submission Time</th>
                <td>
                    <input type="radio"
                    name="ratings[item4_score]"
                    id="item4_score_1"
                    value="1"
                    {{($fieldEvaluationData->item4_score == 1) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item4_score]"
                    id="item4_score_2"
                    value="2"
                    {{($fieldEvaluationData->item4_score == 2) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item4_score]"
                    id="item4_score_3"
                    value="3"
                    {{empty($fieldEvaluationData->item4_score) ? 'checked' : ''}}
                    {{($fieldEvaluationData->item4_score == 3) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item4_score]"
                    id="item4_score_4"
                    value="4"
                    {{($fieldEvaluationData->item4_score == 4) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item4_score]"
                    id="item4_score_5"
                    value="5"
                    {{($fieldEvaluationData->item4_score == 5) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item4_score]"
                    id="item4_score_6"
                    value="6"
                    {{($fieldEvaluationData->item4_score == 6) ? 'checked' : ''}}>
                </td>
            </tr>
            <tr>
                <td class="text-left">Submission Time is from the clock out time to the time of submission</td>
                <td>≥ 120hrs</td>
                <td>119hrs-73hrs</td>
                <td>72hrs-48hrs</td>
                <td>47hrs-24hrs</td>
                <td>≤ 23hrs</td>
                <td></td>
            </tr>
            <tr>
                <th scope="row">Routine Visit/SPV Visit/DC Submission Time</th>
                <td>
                    <input type="radio"
                    name="ratings[item5_score]"
                    id="item5_score_1"
                    value="1"
                    {{($fieldEvaluationData->item5_score == 1) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item5_score]"
                    id="item5_score_2" value="2"
                    {{($fieldEvaluationData->item5_score == 2) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item5_score]"
                    id="item5_score_3" value="3"
                    {{empty($fieldEvaluationData->item5_score) ? 'checked' : ''}}
                    {{($fieldEvaluationData->item5_score == 3) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item5_score]"
                    id="item5_score_4" value="4"
                    {{($fieldEvaluationData->item5_score == 4) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item5_score]"
                    id="item5_score_5"
                    value="5"
                    {{($fieldEvaluationData->item5_score == 5) ? 'checked' : ''}}>
                </td>
                <td></td>
            </tr>
            <tr>
                <td class="text-left">Submission Time is from the clock out time to the time of submission</td>
                <td>≥ 120hrs</td>
                <td>119hrs-73hrs</td>
                <td>72hrs-48hrs</td>
                <td>47hrs-24hrs</td>
                <td>≤ 23hrs</td>
                <td></td>
            </tr>
            <tr>
                <th scope="row">Onsite Inservice Meeting/Participation</th>
                <td>
                    <input type="radio"
                    name="ratings[item6_score]"
                    id="item6_score_1" value="1"
                    {{($fieldEvaluationData->item6_score == 1) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item6_score]"
                    id="item6_score_2" value="2"
                    {{($fieldEvaluationData->item6_score == 2) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio"
                    name="ratings[item6_score]"
                    id="item6_score_3" value="3"
                    {{empty($fieldEvaluationData->item6_score) ? 'checked' : ''}}
                    {{($fieldEvaluationData->item6_score == 3) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio" name="ratings[item6_score]"
                     id="item6_score_4" value="4"
                     {{($fieldEvaluationData->item6_score == 4) ? 'checked' : ''}}>
                </td>
                <td>
                    <input type="radio" name="ratings[item6_score]"
                     id="item6_score_5" value="5"
                     {{($fieldEvaluationData->item6_score == 5) ? 'checked' : ''}}>
                </td>
                <td></td>
            </tr>
            <tr>
                <td class="text-left">The total percentage of onsite inservice meetings attended</td>
                <td>≤ 35%</td>
                <td>36%-49%</td>
                <td>50%-74%</td>
                <td>75%-89%</td>
                <td>≥ 90%</td>
                <td></td>
            </tr>
        </tbody>
    </table>
</section>
<section class="evaluation mt-2 mb-2">
    <header class="panel-heading">
        <h5 class="panel-title">Evaluation</h5>
    </header>
    <table class="table table-bordered" aria-describedby="">
        <tbody>
            <tr>
                <th class="text-left">ADDITIONAL COMMENTS</th>
            </tr>
            <tr>
                <td>
                    <textarea name="additional_comments"
                        id="additional_comments"
                        style="width:100%"
                        placeholder="Comments"
                    >{{$fieldEvaluationData->additional_comments}}</textarea>
            </td>
            </tr>
            <tr>
                <td class="text-left">GOALS</td>
            </tr>
            <tr>
                <td>
                    <textarea name="goals"
                    id="goals" style="width:100%"
                    placeholder="Comments">{{$fieldEvaluationData->goals}}
                    </textarea>
                </td>
            </tr>
        </tbody>
    </table>
</section>
<input type="hidden" name="comments" id="comments" value=""/>
