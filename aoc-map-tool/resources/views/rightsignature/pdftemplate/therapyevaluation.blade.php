

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
        font-size: 25px;
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
span {
  content: "\2713";
}
.angel-logo2 {
    text-align: center;
    /* line-height: 5px; */
    height: 100px;
    vertical-align: middle;
    position: relative;
    padding-top: 30px;
    font-size: 25px;
}
</style>


<div class="main-container" style="width:100%;">
    <div class="logo-container"  width="100%">
        <table cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td width="50%" align="center">
                    <img width="50%" src="{{ base_path('images/blue-logo.png') }}" /> 
                </td>
                <td width="50%" align="center" style="font-size:20px;">
                    <strong>Angels of Care Pediatric Home Health </strong>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="angel-logo" style="text-align:center;">
                    Annual Employee Performance Review  
                </td>
            </tr>
        </table>

       
        
    </div>

   

    <div class="sub-container">
            <div class="sub-heading">Employee Information</div>

            <table cellspacing="0" border="1" cellpadding="15" width="100%">
                <tr>
                    <td width="20%" class="field-title">Name</td>
                    <td width="30%" class="field-value"><?php echo !empty($name)?$name:'&nbsp;';?></td>
                    <td width="20%" class="field-title">Hire Date</td>
                    <td width="30%"><?php echo !empty($hiredate)?$hiredate:'&nbsp;';?></td>
                </tr>

                <tr>
                    <td class="field-title">Job Title</td>
                    <td class="field-value"><?php echo !empty($jobtitle)?$jobtitle:'&nbsp;';?></td>
                    <td class="field-title">Productivity</td>
                    <td class="field-value"><?php echo !empty($head_productivity)?$head_productivity:'&nbsp;';?></td>
                </tr>

                <tr>
                    <td class="field-title">Branch Office</td>
                    <td class="field-value"><?php echo !empty($BranchName)?$BranchName:'&nbsp;';?></td>
                    <td class="field-title">IE/RE Sub.
                        Time </td>
                    <td class="field-value"><?php echo !empty($ie_re_time)?$ie_re_time:'&nbsp;';?></td>
                </tr>

                <tr>
                    <td class="field-title">Review Period</td>
                    <td class="field-value"><?php echo !empty($review_period)?$review_period:'&nbsp;';?></td>
                    <td class="field-title">RV/SV/DC Sub. 
                        Time </td>
                    <td class="field-value"><?php echo !empty($rv_sv_dc_time)?$rv_sv_dc_time:'&nbsp;';?></td>
                </tr>
            </table>
            
           
    </div>


    <div class="sub-container">
        <div class="sub-heading">Ratings</div>
        <div class="sub-fields">
               <table cellspacing="0" cellpadding="5" border="1" >
                    <tr>
                        <td width="30%">&nbsp;</td>
                        <td width="14%">1</td>
                        <td width="14%">2</td>
                        <td width="14%">3</td>
                        <td width="14%">4</td>
                        <td width="14%">5</td>
                    </tr>

                    <tr>
                        <td><strong>Clinical Quality</strong></td>
                        <td>
                            <?php if($clinical_quality==1) { ?>
                            <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                            <?php } else { ?>
                                    &nbsp;
                            <?php } ?>
                        </td>
                        <td> 
                            <?php if($clinical_quality==2) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($clinical_quality==3) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($clinical_quality==4) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td>
                            <?php if($clinical_quality==5) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="table-sub-title">
                            Clinical Quality is measured on an expectation basis 
under the subjection of the Clinical Manager 
                        </td>
                        <td>
                            Needs 
                            Improvement
                        </td>
                        <td> 
                            Below 
                            Expectations
                       </td>
                        <td>
                            Meets 
                            Expectations
                       </td>
                        <td>
                            Above 
                            Expectations
                        </td>
                        <td>
                            Exceeds 
                            Expectations
                        </td>
                    </tr>


                    <tr>
                        <td><strong>Communication</strong></td>
                        <td>
                            <?php if($communication==1) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td> 
                            <?php if($communication==2) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($communication==3) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($communication==4) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td>
                            <?php if($communication==5) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="table-sub-title">
                            Communication is measured on an expectation basis 
                            under the subjection of the Clinical Manager
                        </td>
                        <td>
                            Needs 
                            Improvement
                        </td>
                        <td> 
                            Below 
                            Expectations
                       </td>
                        <td>
                            Meets 
                            Expectations
                       </td>
                        <td>
                            Above 
                            Expectations
                        </td>
                        <td>
                            Exceeds 
                            Expectations
                        </td>
                    </tr>


                    <tr>
                        <td><strong>Productivity</strong></td>
                        <td>
                            <?php if($productivity==1) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td> 
                            <?php if($productivity==2) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($productivity==3) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($productivity==4) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td>
                            <?php if($productivity==5) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="table-sub-title">
                            Productivity is the completed visits divided by the total 
visits within the review period.
                        </td>
                        <td>
                            &lt;= 80% 
                        </td>
                        <td> 
                            81%-88%
                       </td>
                        <td>
                            89%-92%
                       </td>
                        <td>
                            93%-95% 
                        </td>
                        <td>
                        >= 96%
                        </td>
                    </tr>


                    <tr>
                        <td><strong>IE/RE Submission Time</strong></td>
                        <td>
                            <?php if($resubmissiontime==1) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td> 
                            <?php if($resubmissiontime==2) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($resubmissiontime==3) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($resubmissiontime==4) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td>
                            <?php if($resubmissiontime==5) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                    </tr>
 
                    <tr>
                        <td class="table-sub-title">
                            Submission Time is from the clock out time to the 
                            time of submission
                        </td>
                        <td>
                            &lt;= 120hrs 
                        </td>
                        <td> 
                            119hrs-73hrs
                       </td>
                        <td>
                            72hrs-48hrs
                       </td>
                        <td>
                            47hrs-24hrs 
                        </td>
                        <td>
                        >= 23hrs
                        </td>
                    </tr>

                    <tr>
                        <td><strong>Routine Visit/SPV Visit/DC Submission Time</strong></td>
                        <td>
                            <?php if($routinesubmissiontime==1) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td> 
                            <?php if($routinesubmissiontime==2) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($routinesubmissiontime==3) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($routinesubmissiontime==4) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td>
                            <?php if($routinesubmissiontime==5) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="table-sub-title">
                            Submission Time is from the clock out time to the 
time of submission
                        </td>
                        <td>
                            &lt;= 120hrs 
                        </td>
                        <td> 
                            119hrs-48hrs
                       </td>
                        <td>
                            47hrs-24hrs
                       </td>
                        <td>
                            23hrs-13hrs  
                        </td>
                        <td>
                            &lt;= 12hrs
                        </td>
                    </tr>

                    <tr>
                        <td><strong>Onsite Inservice Meeting/Participation</strong></td>
                        <td>
                            <?php if($onsitemeeting==1) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td> 
                            <?php if($onsitemeeting==2) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($onsitemeeting==3) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                       </td>
                        <td>
                            <?php if($onsitemeeting==4) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                        <td>
                            <?php if($onsitemeeting==5) { ?>
                                <img width='15%' src='{{ base_path("images/black-checkmark.png") }}' />
                                <?php } else { ?>
                                    &nbsp;
                                <?php } ?>
                        </td>
                    </tr>

                    <tr>
                        <td class="table-sub-title">
                            The total percentage of onsite inservice meetings 

attended
                        </td>
                        <td>
                            &lt;= 35%
                        </td>
                        <td> 
                            36%-49% 
                       </td>
                        <td>
                            50%-74%
                       </td>
                        <td>
                            75%-89%  
                        </td>
                        <td>
                            &lt;= 90%
                        </td>
                    </tr>

                    

                  
                </table>
        </div>
    </div>

    <div class="sub-container">
        <div class="sub-heading">Evaluation</div>
        <div class="sub-fields">
            Additional Comments
            <br><?php echo !empty($additional_comments)?$additional_comments:'';?>
        </div>

        <div class="sub-fields">
            GOALS
            <br><?php echo !empty($goals)?$goals:'';?>
        </div>
    </div>

    <div class="sub-container">
        <div class="sub-heading">Employee Comments</div>
        <div class="sub-fields">
             [   t:a:r  ] 
            <br><?php // echo !empty($comments)?$comments:'';?>
        </div>

    </div>

    <div class="sub-container">
        <div class="sub-heading">Verification of Review</div>
        <div class="sub-text">
            By signing this form, you confirm that you have read and understand this performance review. Signing this form does not necessarily indicate that you 
agree with this evaluation.     
            </div>    
    </div>


    <div class="sub-container nopad">
        <table cellspacing="0" cellpadding="15"> 
            <tr>
                <td class="first-cell" width="25%"> Employee Signature </td>
                <td class="second-cell" width="45%"> [       s:a:r       ] </td>
                <td class="first-cell" width="20%">Date</td>
                <td class="second-cell" width="20%">[     d:a:r    ]</td>
            </tr>

            <tr>
                <td class="first-cell"> Manager Signature </td>
                <td class="second-cell managersignature"><?php echo isset($manager)?$manager:''?></td>
                <td class="first-cell">Date</td>
                <td class="second-cell"><?php echo $createdDate?></td>
            </tr>

        </table>
    </div>

</div>

