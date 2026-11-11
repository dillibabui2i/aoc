

<style>
    .heading-title, span.information-left {
        font-weight: 700;
    }

    
    .main-text-content {
        padding-top: 50px;
    }
    .text-content-title {
    text-decoration: underline;
    font-weight: 700;
    }
</style>

<div class="main-container" style="width:100%;">
    <div class="logo">
        <div class="angel-logo" style="text-align:center;">
            <img width="50%" src="{{ base_path('images/blue-logo.png') }}" /> 
        </div>

        <div class="heading-title" style="text-align:center;">
                <p>Pay Rate Agreement/Acknowledgement </p>
        </div>
    </div>

    <div class="main-heading-content" style="width:100%;padding-top:50px">
        <div class="information" style="width:100%">
            <span class="information-left">Employee Name :</span> <span class="inforamtion-right"><?php echo $emp_name?></span>
        </div>

        <div class="information-container" style="width:100%">
            <div class="information" style="width:50%; float:left;">
                <span class="information-left">Client Name :</span> <span class="inforamtion-right"><?php echo $client_name?></span>
            </div>
            <div class="information" style="width:50%; float:left;">
                <span class="information-left">Client ID :</span> <span class="inforamtion-right"><?php echo $case_number?></span>
            </div>
        </div>
    </div>

    <div class="main-text-content" style="width:100%">
        <div class="information" style="width:100%">
            
            <div class="text-content-title">Compensation:</div> 
            <div class="text-content">
                Angels of Care candidates are compensated at an hourly rate that varies based on the nature of the case assignment, the number of hours worked, and the individual candidate’s compliance with policies regarding submission of case notes and authorized hours. The compliance requirements for case notes and authorized casework hours are set forth in the Service Delivery Compliance Policy. A candidate’s failure to comply with the Service Delivery Compliance Policy may adversely affect the candidate’s compensation for particular case assignments and for periods of non-compliance, subject to the requirements of any applicable state or local laws.
            </div> 
        </div>
    </div>


    <div class="main-text-content" style="width:100%">
        <div class="information" style="width:100%">
            
            <div class="text-content-title">Compensation for periods if candidate follows the Service Delivery Compliance Policy:</div> 

            <div class="text-content-strong">
                Pay Rate : <?php echo !empty($case_rate)?"$".$case_rate:"N/A"; ?>
            </div> 

            <div class="text-content-strong">
                Schedule : <?php echo !empty($days_shift)?$days_shift:"N/A"; ?>
            </div> 
        </div>
    </div>

    <div class="main-text-content" style="width:100%">
        <div class="information" style="width:100%">
            
            <div class="text-content-title">Compensation for periods if candidate is in violation of the Service Delivery Compliance Policy after written warnings:</div> 
            
            <div class="text-content">
                Minimum Wage of <?php echo !empty($minimum_wage)?"$".$minimum_wage:"N/A"; ?> per hour. This reduced case rate per hour will apply to work performed on cases in workweeks after the candidate is notified of additional and uncorrected violations of the Service Delivery Compliance Policy.

            </div> 
        </div>
    </div>

    <div class="main-text-content" style="width:100%">
        <div class="information" style="width:100%">
            
            <div class="text-content-title">Compensation for training:</div> 
            
            <div class="text-content">
                Training time will be paid according to the following.
            </div> 

            <div class="text-content-strong">
                <?php echo $skill?>:<?php echo ($training_rate!='N/A')?"$".$training_rate:"N/A";?>
            </div> 
            

        </div>
    </div>


    <div class="main-text-content" style="width:100%">
        <div class="information" style="width:100%">
            <div class="text-content">
                All compensation pursuant to this Pay Rate Agreement/Acknowledgement will be subject to applicable deductions for taxes, employment benefits, and unemployment insurance. By signing below, I am authorizing these deductions.
            </div> 
        </div>
    </div>

    <div class="main-text-content" style="width:100%">
        <div class="information" style="width:100%">
            
            <div class="text-content-title">Additional Casework Guidelines:</div> 
            
            <div class="text-content-dynamic">
                [   i:a:r  ] The schedule listed above has been discussed with me and I agree to work this scheudle. I acknowledge I am not authorized to change my schedule with the client or the parent/caregiver. All schedule requests and changes must go through the Client Care Coordinator. I acknowledge that accepting a scheudle and then changing it without proper authorization will subject me to disciplinary action, up to and including immediate termination.
            </div> 

            <div class="text-content-dynamic">
                [   i:a:r  ]  I understand that case information and case assignments are considered confidential information of Angels of Care and are not to be discussed with patients, parents/caregivers, or other employees (except for purposes of coordinating patient care). The failure to maintain the confidentiality of this information may subject me to disciplinary action, up to and including immediate termination.
            </div> 

            <div class="text-content-dynamic">
                [   i:a:r  ]  I understand it is my responsibility to contact Angels of Care immediately upon notice of the possible loss or cancellation of a case assignment. Angels of Care will attempt to find another case with similar hours and close to my home; however, I understand the same schedule may not be available immediately. 
            </div> 

            <div class="text-content-dynamic">
                [   i:a:r  ]  I understand I must report all time worked accurately to Angels of Care, I am not expected to work off the clock, and no manager should ever ask me to do so. I also understand that I am eligible for overtime payments according to applicable state or local laws.
            </div> 

            <div class="text-content-dynamic">
                [   i:a:r  ]  I understand by informing Angels of Care that I accept a case assignment, I am agreeing to work at least two weeks on that assignment before I may request or be considered for a different case assignment.
            </div> 

            <div class="text-content-dynamic">
                This Pay Rate Agreement/Acknowledgement is intended to be in compliance with all federal, state, and local wage laws. As such it will be interpreted and applied to compensate candidates according to all applicable laws.
            </div> 

            <div class="text-content-dynamic">
                By signing this Pay Rate Agreement/Acknowledgement, I am agreeing to comply with the Casework Guidelines and am agreeing to, among other things, the reduced rate per hour applicable to work performed on cases in any non-compliance period of the Service Delivery Compliance Policy.
            </div> 

        </div>
    </div>


    <div class="main-text-content" style="width:100%">
        <div class="information" style="width:100%"> 

            <div class="text-content-strong" style="width:50%;">
                Employee Name: [                t:a:r               ]
            </div> 
            <div class="text-content-strong">
                Employee Signature: [       s:a:r       ] 
            </div> 
            <div class="text-content-strong">
                Date: [     d:a:r    ]
            </div> 
        </div>
    </div>
</div>

