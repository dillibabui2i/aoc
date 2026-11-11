<?php
    use App\Helpers\LD;
?>
<style>
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
.bluebackground {
    background-color: #2f5baa;
    color:#fff;
}
.rowbackground {
    background-color: #3490dc;
    color:#fff;
    text-align: center;
}
.cellheight{
    height:30px;
    padding:5px;
}
</style>


<div class="main-container" style="width:100%;">
    <div class="logo-container"  width="100%">
        <table cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td width="50%" align="center">
                    <img width="30%" src="{{ base_path('images/blue-logo.png') }}" /> 
                </td>
            </tr>
        </table>

        <table cellspacing="0" cellpadding="0" border="0" width="100%">
            <tr>
                <td width="100%" colspan="2" align="left">
                    Employee Information
                </td>
            </tr>
            <tr>
                <td width="50%" align="left">
                   <table width="100%">
                        <tr>
                            <td>
                                Employee Reviewed: <?php echo !empty($name)?$name:''?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Supervisor Name: <?php echo !empty($supervisor)?$supervisor:''?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Manager Name: <?php echo !empty($manager)?$manager:''?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Position Title: <?php echo !empty($positionTitle)?$positionTitle:''?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                Evaluation Period: <?php echo !empty($reviewPeriod)?$reviewPeriod:''?>
                            </td>
                        </tr>
                    </table>
                </td>
                <td width="50%" align="left">
                        <table width="100%">
                            <tr>
                                <td>
                                    Rating Scale
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <ul>
                                        <li>
                                            5 - Outstanding
                                        </li>
                                        <li>
                                            4 - Exceeds Expectations
                                        </li>
                                        <li>
                                            3 - Achieving Expectations
                                        </li>
                                        <li>
                                            2 - Partially Meets Expectations
                                        </li>
                                        <li>
                                            1 - Unsatisfactory Performance
                                        </li>
                                    </ul>
                                    
                                </td>
                            </tr>
                            
                        </table>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="bluebackground">
                    Competencies
                </td>
            </tr>

            <tr>
                <td  colspan="2">
                    These core competencies have been identified by the company that leads to success in one's position. Using the 1 - 5 scale, rate the level of each competency for the individual.
                </td>
            </tr>

            <tr>
                <td colspan="2" >
                    <table width="100%" cellspacing="0" cellpadding="3" border="1" >
                        <tr>
                            <td  class="bluebackground">Competencies
                            </td>
                            <td  class="bluebackground">These factors broadly measure overall job competency for all employees.	
                            </td>
                            <td  class="bluebackground" >Rating
                            </td>
                        </tr>

                        <tr >
                            <td ><strong>Accountability</strong>
                            </td>
                            <td>Effectively performs job while using company time and resources efficiently; meets deadlines; contributes to the success of the department and organization. Meets attendance and/or deliverable requirements.
                            </td>
                            <td class="rowbackground"><?php echo isset($item1_score)?$item1_score:''?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Initiative</strong>
                            </td>
                            <td>Assumes ownership and completes work with minimal supervision and without prompting.	
                            </td>
                            <td class="rowbackground"><?php echo isset($item2_score)?$item2_score:''?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Technical Job Knowledge</strong>
                            </td>
                            <td>Competent in required job skills and knowledge; exhibits ability to learn and apply new skills; stays apprised of new/current developments.		
                            </td>
                            <td class="rowbackground"><?php echo isset($item3_score)?$item3_score:''?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Productivity / Quality</strong>	
                            </td>
                            <td>Ability to set goals, plan work, establish priorities, and works proactively; performs work accurately, thoroughly, and consistently demonstrates commitment to continually improve work quality. Results are consistently within acceptable quality and standards.	
                            </td>
                            <td class="rowbackground"><?php echo isset($item4_score)?$item4_score:''?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Communication and Teamwork</strong>
                            </td>
                            <td>Clearly expresses ideas and thoughts both written and verbal; exhibits good listening and comprehension; keeps others adequately informed, selects and uses appropriate communication methods. Facilitates a positive and productive team-oriented work environment; encourages collaboration, respecting peer functions and disciplines.	
                            </td>
                            <td class="rowbackground"><?php echo isset($item5_score)?$item5_score:''?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Relationship / Customer Service Skills</strong>	
                            </td>
                            <td>Impresses clients/customers, both internal and external, with exceptional service; understands and anticipates customer needs, wants, and issues; proactively tries to build effective working relationships with others. Works effectively with others and responds positively to situations that require cooperation, courtesy, and tact. Values diverse work styles, approaches and thoughts, treating others with dignity and respect.	
                            </td>
                            <td class="rowbackground"><?php echo isset($item6_score)?$item6_score:''?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Adaptability</strong>
                            </td>
                            <td>Adjusts to changes in work, conditions, or unusual situations quickly.	
                            </td>
                            <td class="rowbackground"><?php echo isset($item7_score)?$item7_score:''?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Judgment / Problem Solving and Decision Making</strong>
                            </td>
                            <td>Ability to analyze problems or procedures, evaluate alternatives, and select best course of action; uses logic to solve difficult problems with effective solutions. Makes quality decisions after considering all applicable information.	
                            </td>
                            <td class="rowbackground"><?php echo isset($item8_score)?$item8_score:''?>
                            </td>
                        </tr>

                        <tr>
                            <td><strong>Ethics</strong>
                            </td>
                            <td>Demonstrates principles and sound business ethics with clear awareness of compliance and regulations for organization’s best interests; sets high standards regarding his/her work and working environment and acts accordingly.	
                            </td>
                            <td class="rowbackground"><?php echo isset($item9_score)?$item9_score:''?>
                            </td>
                        </tr>

                        <?php if (LD::checkFeatureFlag('add-competencies-rating-for-halos-office-evaluation')) { ?>
                            <tr>
                                <td><strong>HALOS</strong></td>
                                <td>In every action taken, HALOS values are kept in mind, and used to guide decisions.</td>
                                <td class="rowbackground"><?php echo isset($item14_score)?$item14_score:''?></td>
                            </tr>
                        <?php } ?>
                       
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="2">
                    <table width="100%">
                        <tr>
                            <td width="90%"  align="right" class="bluebackground">Competency Rating Average
                            </td>
                            <td width="10%" class="rowbackground" align="right">
                                <?php echo !empty($comp_average)?$comp_average:''?>
                            </td> 
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td colspan="2" align="right" >&nbsp; 
                </td>
            </tr>
            <tr>
                <td colspan="2"  class="bluebackground cellheight" >Supervisory Personnel Only
                </td>
            </tr>

            <tr>
                <td colspan="2" class="cellheight" >&nbsp;
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <table cellspacing="0" cellpadding="0">
                        <tr>
                            <td  class="bluebackground cellheight">Competencies</td>
                            <td  class="bluebackground cellheight">&nbsp;</td>
                            <td  class="bluebackground cellheight">Rating</td>
                        </tr>

                        <tr>
                            <td>Attracting, Developing and Retaining Talent</td>
                            <td>Identifies specific talent needs and staffs to meet them; develops employees and teams with diverse capabilities; accurately appraises employee competencies and performance;	</td>
                            <td class="rowbackground"><?php echo isset($item10_score)?$item10_score:''?></td>
                        </tr>

                        <tr>
                            <td>Financial Accountability	</td>
                            <td>Makes sound financial decisions that are in the best interest of the organization while keeping within budget constraints; understands key financial indicators and uses them in planning and projecting.		</td>
                            <td class="rowbackground"><?php echo isset($item11_score)?$item11_score:''?></td>
                        </tr>

                        <tr>
                            <td>Vision / Direction Setting</td>
                            <td>Sets and communicates a clear direction to team members.	</td>
                            <td class="rowbackground"><?php echo isset($item12_score)?$item12_score:''?></td>
                        </tr>

                        <tr>
                            <td>Coaching</td>
                            <td>Sets clear goals, standards, and guidelines; encourages others to consistently meet and exceed performance goals.	</td>
                            <td class="rowbackground"><?php echo isset($item13_score)?$item13_score:''?></td>
                        </tr>

                        <tr>
                            <td align="right"  class="bluebackground cellheight" colspan="2">
                                Managerial Rating Average	
                            </td>
                            
                            <td class="rowbackground cellheight">
                                <?php echo !empty($manage_average)?$manage_average:''?>
                            </td>
                            
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="cellheight" >&nbsp;
                </td>
            </tr>

            <tr>
                <td colspan="2"  class="bluebackground cellheight">Strengths
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <?php echo !empty($strengths)?$strengths:''?>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="cellheight" >&nbsp;
                </td>
            </tr>
            <tr>
                <td colspan="2"  class="bluebackground cellheight">Development Areas

                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <?php echo !empty($development_areas)?$development_areas:''?>

                </td>
            </tr>

            <?php if (LD::checkFeatureFlag('add-input-goal-setting-professional-development-office-evaluation')) { ?>
                <tr>
                <td colspan="2" class="cellheight" >&nbsp;
                </td>
            </tr>
            <tr>
                <td colspan="2"  class="bluebackground cellheight">Goal Setting and Professional Development Plan

                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <?php echo !empty($goal_setting_professional_development)?$goal_setting_professional_development:''?>

                </td>
            </tr>
            <?php } ?>

            <tr>
                <td colspan="2" class="cellheight" >&nbsp;
                </td>
            </tr>

            <tr>
                <td colspan="2"  class="bluebackground cellheight">Compliance

                </td>
            </tr>

            <tr>
                <td colspan="2">
                    Has the employee completed all training required for their position w/in the relevant deadlines? 
                    <strong>
                    <?php if(!empty($completed_all_training) && $completed_all_training==1) {?>
                            Yes
                    <?php } else { ?>
                            No
                    <?php } ?>
                    </strong>
                    
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    Has the employee been the subject of a compliance incident?	
                    
                    <strong>
                        <?php if(!empty($subject_of_compliance) && $subject_of_compliance==1) {?>
                                Yes
                        <?php } else { ?>
                                No
                        <?php } ?>
                    </strong>
                    
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    Has the employee demonstrated a commitment to compliance with all federal, state and local laws and regulations applicable to their position within the organization?	
                    
                    <strong>
                        <?php if(!empty($commitment_to_compliance) && $commitment_to_compliance==1) {?>
                                Yes
                        <?php } else { ?>
                                No
                        <?php } ?>
                    </strong>
                </td>
            </tr>
            <tr>
                <td colspan="2" class="cellheight" >&nbsp;
                </td>
            </tr>
            <tr>
                <td colspan="2"   class="bluebackground cellheight">
                    Overall Performance Summary
	
                </td>
            </tr>

            <tr>
                <td colspan="2">
                    <table cellspacing="0" cellpadding="0">
                        <tr>
                            <td> <?php echo !empty($overall_performance)?$overall_performance:''?>
                            </td>
                            <td class="rowbackground"> <?php echo 'Score : '.!empty($overall_performance_score)?$overall_performance_score:''?>

                            </td>
                        </tr>
                    </table>
                   
                   
                    
                </td>
            </tr>


            <tr>
                <td colspan="2" class="cellheight" >&nbsp;
                </td>
            </tr>

            <tr>
                <td  class="bluebackground cellheight">Employee Signature
                    
                </td>
                <td  class="bluebackground cellheight " align="right">Date
                   
                </td>
            </tr>

            <tr>
                <td  class=" cellheight">
                    [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;s:a:r&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]
                </td>
                <td  class=" cellheight " align="right">
                    [&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;d:a:r&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;]
                </td>
            </tr>

            <tr>
                <td colspan="2" class="cellheight" >&nbsp;
                </td>
            </tr>

            <tr>
                <td  class="bluebackground cellheight">Supervisor / Manager's Signature

                </td>
                <td  class="bluebackground cellheight" align="right">Date

                </td>
            </tr>

            <tr>
                <td  class=" cellheight">
                    <?php echo !empty($supervisor)?$supervisor:''?>
                </td>
                <td  class=" cellheight " align="right">
                    <?php echo !empty($currentDate)?$currentDate:''?>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="cellheight" >&nbsp;
                </td>
            </tr>

            <tr>
                <td  class="bluebackground cellheight">2nd Level Superior (if applicable)

                </td>
                <td  class="bluebackground cellheight" align="right">Date

                </td>
            </tr>

            <tr>
                <td  class=" cellheight">
                    <?php echo !empty($manager)?$manager:''?>
                </td>
                <td  class=" cellheight " align="right">
                    <?php echo !empty($currentDate)?$currentDate:''?>
                </td>
            </tr>
           
        </table>
    </div>
</div>

