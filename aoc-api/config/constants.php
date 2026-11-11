<?php

return [
     // Http response Code
     'response_code' => [
        'OK'=>200,
        'SOMETHING_WENT_WRONG'=>600,
        'INVALID_TOKEN'=>601,
        'NO_SCHEDULE'=>602,
        'ACCOUNT_DISABLED'=>603,
        'MAX_LOGIN_ATTEMPT'=>604,
    ],
    
    //Common Validation(s) rules and messages
    'validations' => [
        'common' => [
            'rules' => [
                'pf'                    =>  'required|in:1,2,3',
                'app_ver'               =>  'required_if:pf,1,2',
            ],
            'messages' => [
                'pf.required'           =>  'Something went wrong (code: 1502).',
                'pf.in'                 =>  'Invalid Platform. Please try again.',
                'app_ver.required_if'      =>  'Something went wrong (code: 1501).'
            ]
        ],
    ],
    'validationsError' => [
        'patient_id_required' => 'Client Id is required',
    ],
    'reasonCategory'=>[
        'AOC'=>[
            '2'=>['New Intake','Advocacy Initiated Removal','Termination of Nurse for Safety Concern','Nurse Charting Issues/Late Notes','Ticket Made in Error'],
            '5'=>['New Intake','Advocacy Initiated Removal','Termination of Nurse for Safety Concern','Nurse Charting Issues/Late Notes','Ticket Made in Error'],
        ],
        'Caregiver'=>[
            '2'=>['Allergy to Nurse','Authorized for Additional Hours','Complaint On Nurse','Complaint On Nurse: Nurse Fell Asleep',
                'Concern with Nurse Skill Level','Conflict/Fit with Nurse','Covid Hold','Home Environment','Hospitalized too Long',
                'New Eligibility','Not Comfortable with Nurse Lifting','Nurse after NC/NS, Calling-In, Reliability','Nurse Covid Exposure',
                'Reduction in Authorized Hours','Required to use Hours','Utilizing Additional Hours','Schedule Change'],
            '5'=>['Allergy to Nurse','Authorized for Additional Hours','Complaint On Nurse','Complaint On Nurse: Nurse Fell Asleep',
                'Concern with Nurse Skill Level','Conflict/Fit with Nurse','Covid Hold','Home Environment','Hospitalized too Long',
                'New Eligibility','Not Comfortable with Nurse Lifting','Nurse after NC/NS, Calling-In, Reliability','Nurse Covid Exposure',
                'Reduction in Authorized Hours','Required to use Hours','Utilizing Additional Hours','Schedule Change'],
        ],
        'Clinician'=>[
            '2'=>['Allergy in home','Bad Experience with AOC','Changed to Another AOC Case','Conflict/Fit with Parent','Location Too Far',
                'Medical Leave','Misinformed About Details of the Case','Moving','Not Comfortable with Lifting','Not Comfortable with Skill Level',
                'Not Hirable','Personal Leave','Promotion/New Field','Quit due to Covid','Reports Unsafe Working Environment','Stating Never Accepted Perm','Moving','Schedule Change'],
            '5'=>['Allergy in home','Bad Experience with AOC','Changed to Another AOC Case','Conflict/Fit with Parent','Location Too Far',
                'Medical Leave','Misinformed About Details of the Case','Moving','Not Comfortable with Lifting','Not Comfortable with Skill Level',
                'Not Hirable','Personal Leave','Promotion/New Field','Quit due to Covid','Reports Unsafe Working Environment','Schedule Change',
                'Stating Never Accepted Perm','Moving'],
        ],
        'global'=>[
            '2'=>['Discharged','Family declined hours','Non-admit','Reduced hours','Staffed'],
        ]
    ],
    'dashboard_state' =>['AZ'=>'Arizona','CO'=>'Colorado','FL'=>'Florida','NC'=>'North Carolina','PA'=>'Pennsylvania','SC'=>'South Carolina','TX'=>'Texas'],
    'adsType'=>['Custom Ad','General Ad'],
    'adsCategory'=>['PDN','Therapy','Academy'],
    'schedulestatus' => ["1" => "Approved",
    "2" => "Completed",
    "3"	=> "In_Progress",
    "4"	=> "MissedVisit",
    "5"	=> "Planned",
    "6"	=> "NA",
    "7"	=> "NO",
    "8"	=> "Office Auth",
    "9"	=> "Real Auth"],
    'mysqlsearchdatetimeformat' => '%m/%d/%Y %h:%i %p'
];
