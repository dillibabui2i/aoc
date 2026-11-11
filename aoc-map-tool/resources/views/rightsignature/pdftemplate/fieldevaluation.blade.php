
<?php
use App\Helpers\H;
use App\Helpers\LD;
?>
<style>
    @font-face {
        font-family: cedarville;
        src: url(css/CedarvilleCursive-Regular.ttf);
    } 
    .managersignature {
        font-family: cedarville, Arial, Verdana, Tahoma, sans-serif;
    }
    .angel-logo {
        font-weight: 700;
        font-size: 20px;
    }
    .sub-heading {
        width: 100%;
        background: grey;
        color: white;
        font-weight: 700;
        padding: 7px;
        margin-top: 25px;
    }
    .sub-fields {
    padding-top: 20px;
    padding-bottom: 20px;
    /* padding: 20px; */
}td.table-sub-title {
    font-style: italic;
} .sub-text {
    font-style: italic;
}
table {
    width: 100%;
}
    .sub-container {
    width: 100%;
    clear: both;
    padding-top: 10px;
}
.field-group {
    float: left;
    width: 50%;
}
.field-title {
   
}
.field-value {
}
table, th, td {
  border: 1px solid;
}
span {
  content: "\2713";
}
.page-break {
    page-break-after: always;
}
</style>


<div class="main-container" style="width:100%;">
    <div class="logo">
        <div class="angel-logo" style="text-align:center;">
            Performance Evaluation
        </div>
    </div>

    <div class="sub-container">
            <div class="sub-heading">Employee Information</div>

            <table cellspacing="0" cellpadding="15" width="100%">
                <tr>
                    <td width="20%" class="field-title">Name</td>
                    <td width="30%" class="field-value"><?php echo !empty($name)?$name:'&nbsp';?></td>
                    <td width="20%" class="field-title">Hire Date</td>
                    <td width="30%"><?php echo !empty($hiredate)?$hiredate:'&nbsp';?></td>
                </tr>

                <tr>
                    <td class="field-title">Job Title</td>
                    <td class="field-value"><?php echo !empty($jobtitle)?$jobtitle:'&nbsp';?></td>
                    <td class="field-title">Review Period</td>
                    <td class="field-value"><?php echo !empty($review_period)?$review_period:'&nbsp';?></td>
                </tr>
            </table>
            
           
    </div>


    <div class="sub-container">
        <div class="sub-heading">Ratings</div>
        <div class="sub-fields">
               <table cellspacing="0" cellpadding="15" >
                    <tr>
                        <td>&nbsp;</td>
                        <td>Exceeds Expectation</td>
                        <td>Meets Expectations</td>
                        <td>Partially Meets Expectations</td>
                        <td>Does Not Meet Expectations</td>
                    </tr>

                    <tr>
                        <td><strong>Job Knowledge</strong></td>
                        <td>
                            <?php if($job_knowledge==1) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                            <?php } else {?>
                                    &nbsp; 
                                <?php } ?>
                            </td>
                        <td> <?php if($job_knowledge==2) { ?>
                            <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                        <?php } else {?>
                                &nbsp; 
                            <?php } ?></td>
                        <td> <?php if($job_knowledge==3) { ?>
                            <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                        <?php } else {?>
                                &nbsp; 
                            <?php } ?></td>
                        <td> <?php if($job_knowledge==4) { ?>
                            <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                        <?php } else {?>
                                &nbsp; 
                            <?php } ?></td>
                    </tr>

                    <tr>
                        <td class="table-sub-title">Functions proficiently within the job description for his/her position</td>
                        <td colspan="4"><?php echo !empty($job_knowledge_comment)?$job_knowledge_comment:'&nbsp;';?></td>
                    </tr>


                    <tr>
                        <td><strong>Work Quality</strong></td>
                        <td>
                            <?php if($work_quality==1) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                            <?php } else {?>
                                    &nbsp; 
                                <?php } ?>
                            </td>
                        <td> <?php if($work_quality==2) { ?>
                            <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                        <?php } else {?>
                                &nbsp; 
                            <?php } ?></td>
                        <td> <?php if($work_quality==3) { ?>
                            <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                        <?php } else {?>
                                &nbsp; 
                            <?php } ?></td>
                        <td> <?php if($work_quality==4) { ?>
                            <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                        <?php } else {?>
                                &nbsp; 
                            <?php } ?></td>
                    </tr>

                    <tr>
                        <td class="table-sub-title">  Follows Plan Of Care, Makes appropriate action always mindful of safety standards/precautions </td>
                        <td colspan="4"><?php echo !empty($work_quality_commnet)?$work_quality_commnet:'&nbsp;';?></td>
                    </tr>

                    <tr>
                        <td><strong>Attendance, Punctuality, and Dependability</strong></td>
                        <td>  <?php if($attendance==1) { ?>
                            <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                        <?php } else {?>
                                &nbsp; 
                            <?php } ?>
                        </td>
                    <td> <?php if($attendance==2) { ?>
                        <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                    <?php } else {?>
                            &nbsp; 
                        <?php } ?></td>
                    <td> <?php if($attendance==3) { ?>
                        <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                    <?php } else {?>
                            &nbsp; 
                        <?php } ?></td>
                    <td> <?php if($attendance==4) { ?>
                        <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                    <?php } else {?>
                            &nbsp; 
                        <?php } ?></td>
                    </tr>

                    <tr>
                        <td class="table-sub-title">Reports for duty promptly for scheduled shifts, Timely submission of notes. Responds promptly to communication from office staff</td>
                        <td colspan="4"><?php echo !empty($attendance_comment)?$attendance_comment:'&nbsp;';?></td>
                    </tr>


                    <tr>
                        <td><strong>Professionalism and Communication</strong></td>
                        <td>  <?php if($professionalism==1) { ?>
                            <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                        <?php } else {?>
                                &nbsp; 
                            <?php } ?>
                        </td>
                    <td> <?php if($professionalism==2) { ?>
                        <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                    <?php } else {?>
                            &nbsp; 
                        <?php } ?></td>
                    <td> <?php if($professionalism==3) { ?>
                        <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                    <?php } else {?>
                            &nbsp; 
                        <?php } ?></td>
                    <td> <?php if($professionalism==4) { ?>
                        <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                    <?php } else {?>
                            &nbsp; 
                        <?php } ?></td>
                    </tr>

                    <tr>
                        <td class="table-sub-title">Handles situations in a professional manner with few to no complaints, Maintains good communication with branch regarding shifts and client information</td>
                        <td colspan="4"><?php echo !empty($professionalism_comment)?$professionalism_comment:'&nbsp;';?></td>
                    </tr>
                  
                </table>
        </div>
    </div>

    <div class="sub-container">
        <div class="sub-heading">Employee Comments</div>
        <div class="sub-fields">
            [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            t:a:r
            &nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            ]
            <?php //echo !empty($general_comment)?$general_comment:'&nbsp;';?>
        </div>
    </div>

    <div class="sub-container">
        <div class="sub-heading">Verification of Review</div>
        <div class="sub-text">
            By signing this form, you confirm that you have read and understand this performance review. Signing this form does not necessarily indicate that you agree with this evaluation.
            
            </div>    
    </div>


    <div class="sub-container nopad">
        <table cellspacing="0" cellpadding="15"> 
            <tr>
                <td class="first-cell" width="25%"> Employee Signature </td>
                <td class="second-cell" width="45%"> [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;s:a:r&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;] </td>
                <td class="first-cell" width="20%">Date</td>
                <td class="second-cell" width="20%">[&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;d:a:r&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]</td>
            </tr>

            <tr>
                <td class="first-cell"> Manager  </td>
                <td class="second-cell managersignature"><?php echo isset($manager)?$manager:''?></td>
                <td class="first-cell">Date</td>
                <td class="second-cell"><?php echo $createdDate?></td>
            </tr>

        </table>
    </div>

</div>
<?php if(LD::checkFeatureFlag('pay-rate-acknowledgement-field-evaluation')) {
        if(in_array($jobtitle,['RN','LVN','LPN','AIDE','CNA','IHSS','ATTN','LHA'])) { ?> 
            <div class="page-break"></div>
            <div class="main-container pay-rate-ack" style="width:100%;">
                    <div class="ack-heading" style="text-align:center;">
                        <u><strong>Pay Rate Acknowledgement</strong></u>
                    </div>

                    <div> 
                        <p>
                            I acknowledge that pay rates for home health services are determined by
                             Angels of Care on a client-specific basis and may vary depending on the client.
                               I acknowledge that I am responsible for reporting all time worked
                                according to Angels of Care procedures to receive proper compensation.
                                 Client-specific rates are paid for scheduled or approved patient care hours.
                                  I understand that any work performed outside of scheduled patient
                                   care hours will be paid at the minimum wage rate for my location.
                                    I acknowledge receipt of the Service Delivery Compliance Policy.
                                      I recognize that compensation for late charting and non-compliant
                                       nursing services will be paid at the minimum wage rate for my location.
                        </p>
                    </div>

                    <div>
                        <table cellspacing="0" cellpadding="15"> 
                            <tr>
                                <td class="first-cell" width="25%">Signature </td>
                                <td class="second-cell" width="45%"> [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;s:a:r&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;] </td>
                            </tr>
                            <tr>
                                <td class="first-cell" width="20%">Date</td>
                                <td class="second-cell" width="20%">[&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;d:a:r&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]</td>
                            </tr>
                        </table>
                    </div>
            </div>
    <?php } ?>
<?php } ?>

