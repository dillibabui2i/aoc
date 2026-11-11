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
            <label class="col-sm-4 p-0 text-right">Review Period:</label>
			@if($clinicianData->State == 'CO')
            <span class="col-sm-8 text-left">
                {{H::getFormatedDate(
                        date('Y', strtotime("-1 year")) .'-'.
                        date('m-d', strtotime($clinicianData->HiredDate))) . ' - ' .
                        H::getFormatedDate(date('Y') .'-'.
                        date('m-d', strtotime("-1 day", strtotime($clinicianData->HiredDate)))
                    )}}
            </span>
            @else
            <span class="col-sm-8 text-left">{{ config('fieldevaluation.review_period') }}</span>
            @endif
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
                <th style="width: 40%;" scope="col"></th>
                <th style="width: 15%;" scope="col">Exceeds Expectation</th>
                <th style="width: 15%;" scope="col">Meets Expectations</th>
                <th style="width: 15%;" scope="col">Partially Meets Expectations</th>
                <th style="width: 15%;" scope="col">Does Not Meet Expectations</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>Job Knowledge</th>
                <td>
                    <input
                        type="radio"
                        name="ratings[item1_score]"
                        id="item1_score_1"
                        value="1"
                        {{empty($fieldEvaluationData->item1_score) ? 'checked' : ''}}
                        {{($fieldEvaluationData->item1_score == 1) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item1_score]"
                        id="item1_score_2"
                        value="2"
                        {{($fieldEvaluationData->item1_score == 2) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item1_score]"
                        id="item1_score_3"
                        value="3"
                        {{($fieldEvaluationData->item1_score == 3) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item1_score]"
                        id="item1_score_4"
                        value="4"
                        {{($fieldEvaluationData->item1_score == 4) ? 'checked' : ''}}
                    >
                </td>
            </tr>
            <tr>
                <td class="text-left">Functions proficiently within the job description for his/her position</td>
                <td colspan="4">
                    <textarea
                        name="ratings[item1_note]"
                        id="item1_note"
                        style="width:100%"
                        placeholder="Comments"
                    >{{$fieldEvaluationData->item1_note}}</textarea>
                </td>
            </tr>
            <tr>
                <th>Work Quality</th>
                <td>
                    <input
                        type="radio"
                        name="ratings[item2_score]"
                        id="item2_score_1"
                        value="1"
                        {{empty($fieldEvaluationData->item2_score) ? 'checked' : ''}}
                        {{($fieldEvaluationData->item2_score == 1) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item2_score]"
                        id="item2_score_2"
                        value="2"
                        {{($fieldEvaluationData->item2_score == 2) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item2_score]"
                        id="item2_score_3"
                        value="3"
                        {{($fieldEvaluationData->item2_score == 3) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item2_score]"
                        id="item2_score_4"
                        value="4"
                        {{($fieldEvaluationData->item2_score == 4) ? 'checked' : ''}}
                    >
                </td>
            </tr>
            <tr>
                <td class="text-left">Follows Plan Of Care, Makes appropriate action always mindful of safety standards/precautions</td>
                <td colspan="4">
                    <textarea
                        name="ratings[item2_note]"
                        id="item2_note"
                        style="width:100%"
                        placeholder="Comments"
                    >{{$fieldEvaluationData->item2_note}}</textarea>
                </td>
            </tr>
            <tr>
                <th>Attendance, Punctuality, and Dependability</th>
                <td>
                    <input
                        type="radio"
                        name="ratings[item3_score]"
                        id="item3_score_1"
                        value="1"
                        {{empty($fieldEvaluationData->item3_score) ? 'checked' : ''}}
                        {{($fieldEvaluationData->item3_score == 1) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item3_score]"
                        id="item3_score_2"
                        value="2"
                        {{($fieldEvaluationData->item3_score == 2) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item3_score]"
                        id="item3_score_3"
                        value="3"
                        {{($fieldEvaluationData->item3_score == 3) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item3_score]"
                        id="item3_score_4"
                        value="4"
                        {{($fieldEvaluationData->item3_score == 4) ? 'checked' : ''}}
                    >
                </td>
            </tr>
            <tr>
                <td class="text-left">Reports for duty promptly for scheduled shifts, Timely submission of notes. Responds promptly to communication from office staff</td>
                <td colspan="4">
                    <textarea
                        name="ratings[item3_note]"
                        id="item3_note"
                        style="width:100%"
                        placeholder="Comments"
                    >{{$fieldEvaluationData->item3_note}}</textarea>
                </td>
            </tr>
            <tr>
                <th>Professionalism and Communication</th>
                <td>
                    <input
                        type="radio"
                        name="ratings[item4_score]"
                        id="item4_score_1"
                        value="1"
                        {{empty($fieldEvaluationData->item4_score) ? 'checked' : ''}}
                        {{($fieldEvaluationData->item4_score == 1) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item4_score]"
                        id="item4_score_2"
                        value="2"
                        {{($fieldEvaluationData->item4_score == 2) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item4_score]"
                        id="item4_score_3"
                        value="3"
                        {{($fieldEvaluationData->item4_score == 3) ? 'checked' : ''}}
                    >
                </td>
                <td>
                    <input
                        type="radio"
                        name="ratings[item4_score]"
                        id="item4_score_4"
                        value="4"
                        {{($fieldEvaluationData->item4_score == 4) ? 'checked' : ''}}
                    >
                </td>
            </tr>
            <tr>
                <td class="text-left">Handles situations in a professional manner with few to no complaints, Maintains good communication with branch regarding shifts and client information</td>
                <td colspan="4">
                    <textarea
                        name="ratings[item4_note]"
                        id="item4_note"
                        style="width:100%"
                        placeholder="Comments"
                    >{{$fieldEvaluationData->item4_note}}</textarea>
                </td>
            </tr>
        </tbody>
    </table>
</section>
<section class="comments mt-2 mb-2">
    <header class="panel-heading">
        <h5 class="panel-title">Comments</h5>
    </header>
    <table class="table table-bordered" aria-describedby="" aria-hidden="true">
        <tbody>
            <tr>
                <td>
                    <textarea
                        name="comments"
                        id="comments"
                        style="width:100%"
                        placeholder="Comments">{{$fieldEvaluationData->comments}}</textarea>
                </td>
            </tr>
        </tbody>
    </table>
</section>
