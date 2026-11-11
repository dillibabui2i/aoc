<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class InsertIpeaChecklist extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('ipeaChecklist')->insert([
            ['title' => 'welcome', 'checklist'=>'Update IPEA Calendar When They Arrive', 'created_by'=>'1' ],
            ['title' => 'welcome', 'checklist'=>'Introduction to Branch Team Members & Give contact list', 'created_by'=>'1' ],
            ['title' => 'welcome', 'checklist'=>'Give Welcome Grab Bag (Must include Stylus)', 'created_by'=>'1' ],
            ['title' => 'welcome', 'checklist'=>'Give Candidate Drug Screen', 'created_by'=>'1' ],
            ['title' => 'welcome', 'checklist'=>'Notify CM when ready for Interview (after drug screen)', 'created_by'=>'1' ],
            ['title' => 'welcome', 'checklist'=>'Obtain Candidate Lunch Order', 'created_by'=>'1' ],
            ['title' => 'welcome', 'checklist'=>'Ask for I-9 Credentials (Do not specify which documents)', 'created_by'=>'1' ],
            ['title' => 'welcome', 'checklist'=>'Give Candidate Paper Packets for completion', 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>'Add Employee to Paycom', 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>'Paycom Application (Review or Launch if not completed)', 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>'Paycom Onboarding Complete', 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>"Paycom Background Results (Carolina's & Florida)", 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>'Upload I-9 docs to Paycom', 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>'Proper Status: Part-time vs. Full-time', 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>'Direct Deposit Information or Paycard Selection', 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>'Proper PTO Accrual Selected if Full-time', 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>'Handbook Acknowledgement & Compliance Documents signed', 'created_by'=>'1' ],
            ['title' => 'paycom', 'checklist'=>'Paycom (LMS) Test Passed/Paycom Checklist Completed', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Add Employee - MUST Match Paycom ID [E]', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Payroll ID - Must Match Paycom', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Payroll Location - Must Match Paycom', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Payroll Group - Must Match Paycom', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Employment Type matches Paycom', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Pay Type matches Paycom (Hourly vs. Salary)', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Job Title and Supervisor corret', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Clinician and/or Office Staff Tabs selected', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Create or Reset User Password', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Verify Digital Signature has been created', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'General Tab', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Status Change', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Add Journal Note RE: status of IPEA', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Exclude From Field Chart Auto-Approval', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'Add Picture to General and Documents Tab', 'created_by'=>'1' ],
            ['title' => 'kantime', 'checklist'=>'KT University Account Setup and add KTU Training', 'created_by'=>'1' ],
            ['title' => 'clinical', 'checklist'=>'Complete Clinical Interview', 'created_by'=>'1' ],
            ['title' => 'clinical', 'checklist'=>'Set expectations for Staff', 'created_by'=>'1' ],
            ['title' => 'clinical', 'checklist'=>'Give POC to CCM (If Applicable)', 'created_by'=>'1' ],
            ['title' => 'clinical', 'checklist'=>'Complete Check-Offs', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'ANNUAL PERFORMANCE EVALUATION', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'AUTO INSURANCE', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CRIMINAL HISTORY/BACKGROUND CHECK', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'DRIVERS LICENSE', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'TX -EMR AND NAR', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'LEIE FEDERAL EXCLUSION LIST', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'FIRST AID', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'PROFESSIONAL LICENSE', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'TB Test or Chest X-Ray', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CO-EVV Exemption Form', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CO-Influenza Vaccine', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'ANNUAL IN-SERVICE', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'TX -Therapy - Monthly Supervision Requirements', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'AZ -Adult Protective Services Registry Check', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'AZ -Article 9', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'AZ- Fingerprint Card', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'Motor Vehicle Record Check', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CPA State Clearance', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'PA -FBI Clearance', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'PA -Child Abuse Registry', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'PA State Clearance', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'TB Screening\/Risk Assessment', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'Annual Skills Competency', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CMS COVID Mandate - Eligible', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CMS COVID Mandate - Non Eligible', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CMS COVID Mandate - Refused', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CO-DORA', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'NATIONAL SEX OFFENDER CHECK', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CO-CAPS CHECK', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'TX-OIG STATE EXCLUSION LIST', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'PA AGING FBI', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'PA PROOF OF RESIDENCY', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'CO-DORA ANNUAL VERIFICATION', 'created_by'=>'1' ],
            ['title' => 'credentials / renewables', 'checklist'=>'AZ-Central Registry', 'created_by'=>'1' ]
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
