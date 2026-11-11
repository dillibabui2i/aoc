<?php
namespace App\Helpers;
use App\Enums\Intake\AdmissionServiceNameEnum;
use App\Enums\LOB;
use App\Enums\Intake\AdmissionVisitSchedule;
use App\Enums\MssqlPatientStatus;
use App\Enums\MssqlScheduleMasterStatus;
use App\Services\Intake\IntakeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\MssqlPatients;
use Arr;
use Illuminate\Support\Facades\Log;

class IntakeCoordinatorStaffTransfers
{
    const INTAKECOORDINATOR = 'Intake Coordinator';
    private const OLD_INTAKE_HISTORICAL_DATE = '2024-01-01';

    public $buckets;

    public function __construct() {
        $this->buckets = [
            "intake_need_to_schedule_ipea" => [
                "heading" => "Need to Schedule IPEA",
                "description" => "<p style='text-align: left;'><u><b>Responsible Party:</b> Intake Coordinator</u><br>
                <b>Description:</b> Clinicians transferring with new referral that need to be scheduled for an IPEA.<br>
                <b>Timeline:</b> IPEA to be scheduled same day as the initial call.</p>",
                "bucketFitler" => "",
            ],
            "intake_todays_ipea" => [
                "heading" => "Today's IPEAs",
                "description" => "<p style='text-align: left;'><u><b>Responsible Party:</b> Intake Coordinator</u><br>
                <b>Description:</b> Clinicians scheduled for IPEA today or dates in the past with an unresolved outcome.
                Once clinician is confirmed, Intake Coordinator should document the client match.<br>
                <b>Timeline:</b> 1 hour from time IPEA starts.</p>",
                "bucketFitler" => "getTodaysIPEAFilters",
            ],
            "intake_pending_candidate_starts" => [
                "heading" => "Pending Candidate Starts",
                "description" => "<p style='text-align: left;'><u><b>Responsible Party:</b> For informational purposes for Intake Coordinator and Branch</u><br>
                <b>Description:</b> Candidates scheduled for first billable shift in the future.",
                "bucketFitler" => "",
            ],
            "intake_candidate_starts" => [
                "heading" => "Candidate Starts",
                "description" => "<p style='text-align: left;'><u><b>Responsible Party:</b>  Intake Coordinator</u><br>
                <b>Description:</b> Candidates scheduled for candidate start today or dates in the past with unresolved outcome. <br>
                <b>Timeline:</b> 1 hour from start of shift </p>",
                "bucketFitler" => "",
            ],
            "intake_prospective_clients" => [
                "heading" => "Prospective Clients",
                "description" => "<p style='text-align: left;'><u><b>Responsible Party:</b> Intake Coordinator</u><br>
                <b>Description:</b> Referrals in Pending Status.<br>
                <b>Timeline:</b>".(LD::checkFeatureFlag('red-highlights-intake-all-buckets') ? " All clients must be contacted on their designated follow-up date. Non-Therapy – Follow-up date should not be more than 7 days in the future." : "")."</p>",
                "bucketFitler" => "getProspectiveClientsFilters",
                "defaultSortColumnNumber" => [10, 'asc']
            ],
            "intake_schedule" => [
                "heading" => "Intake Scheduled",
                "description" => "<p style='text-align: left;'><u><b>Responsible Party:</b> Intake Coordinator</u><br>
                <b>Description:</b> Referrals with intake scheduled in the future or in the past
                 that have not been handed off to the branch.<br>
                <b>Timeline:</b>".(LD::checkFeatureFlag('red-highlights-intake-all-buckets') ? " All clients must be contacted on their designated follow-up date." : "")." </p>",
                "bucketFitler" => "getScheduleFilters",
                "defaultSortColumnNumber" => [9, 'asc']
            ],
            "intake_pending_soc" => [
                "heading" => "Pending Start of Care",
                "description" => "<p style='text-align: left;'><u><b>Responsible Party:</b> Intake Coordinator</u><br>
                <b>Description:</b> Referrals that have been admitted and SOC date is in the future.<br>
                <b>Timeline:</b>".(LD::checkFeatureFlag('red-highlights-intake-all-buckets') ? " All clients must be contacted on their designated follow-up date." : "")."  </p>",
                "bucketFitler" => "getPendingSocFilters",
                "defaultSortColumnNumber" => [11, 'asc']
            ],
            "intake_client_starts" => [
                "heading" => "Client Starts",
                "description" => "<p style='text-align: left;'><u><b>Responsible Party:</b> Intake Coordinator</u><br>
                <b>Description:</b> Referrals with a Goal SOC Date today.<br>
                <b>Timeline:</b>".(LD::checkFeatureFlag('red-highlights-intake-all-buckets') ? " Goal SOC date should be updated daily to match the first billable shift." : "")."  </p>",
                "bucketFitler" => "getClientStartFilters",
                "defaultSortColumnNumber" => [6, 'asc']
            ],
            "intake_pending_non_admit" => [
                "heading" => "Pending Non Admit",
                "description" => "<p style='text-align: left;'><u><b>Responsible Party:</b> RDO/VP/RVP</u><br>
                <b>Description:</b> Referrals pending approval to non admit.<br>
                <b>Timeline:</b>".(LD::checkFeatureFlag('red-highlights-intake-all-buckets') ? " Approved non admits must be processed in KanTime within 2 business days." : "")." </p>",
                "bucketFitler" => "getPendingNonAdmitFilters",
            ],
        ];
    }

    public function getClientStartFilters()
    {
        $filters = [
            [
                'label' => 'SOC',
                'name' => 'intakestafftransferssoc',
                'fromName' => 'from_intakeclientstartsoc',
                'toName' => 'to_intakeclientstartsoc',
                'type' => 'dateRangePicker',
                'fromdate' => '',
                'todate' => '',
                'extra_class' => ''
            ],
        ];

        return $filters;
    }

    public function getPendingNonAdmitFilters()
    {
        $filters = [
            [
                'label' => 'Non Admit Reason',
                'name' => 'intakestafftransfersnonadmitreason',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getNonAdmitReason()
            ]
        ];

        return $filters;
    }

    public function getTodaysIPEAFilters()
    {
        $filters = [
            [
                'label' => 'Show All',
                'name' => 'intakestafftransfersshowall',
                'type' => 'checkbox',
                'value' => false
            ],
            [
                'label' => 'IPEA Date',
                'name' => 'intakestafftransferipeadate',
                'type' => 'dateRangePicker',
                'fromName' => 'intakestafftransferipeadatefrom',
                'toName' => 'intakestafftransferipeadateto',
                'fromdate' => '',
                'todate' => '',
            ]
        ];

        return $filters;
    }

    public function getScheduleFilters()
    {
        $filters = [
            [
                'label' => 'Intake Date',
                'name' => 'intakestafftransfersintakedate',
                'type' => 'dateRangePicker',
                'fromName' => 'intakestafftransfersintakedatefrom',
                'toName' => 'intakestafftransfersintakedateto',
                'fromdate' => '',
                'todate' => '',
            ]
        ];

        return $filters;
    }

    public function getProspectiveClientsFilters()
    {
        $filters = [
            [
                'label' => 'Pending Reason',
                'name' => 'intakestafftransferspendingreason',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPendingReason()
            ],
            [
                'label' => 'Skills',
                'name' => 'intakestafftransfersskills',
                'type' => 'multiselectsearch',
                'source' => H::getHelper('ConstantHelper')->getSkillsForNTH()
            ],
            [
                'label' => 'Referral Date',
                'name' => 'intakestafftransfersreferraldate',
                'type' => 'dateRangePicker',
                'fromName' => 'intakestafftransfersreferraldatefrom',
                'toName' => 'intakestafftransfersreferraldateto',
                'fromdate' => '',
                'todate' => '',
            ]
        ];

        return $filters;
    }

    public function getPendingSocFilters()
    {
        $readyToStartReason = H::getHelper('ConstantHelper')->getReadyToStartReason();
        if(LD::checkFeatureFlag('add-new-reason-in-pending-start-of-care')){
            $readyToStartReason[] = "Post Hospital Reassessment";
            $readyToStartReason = Arr::sort($readyToStartReason);
        }

        $filters = [
            [
                'label' => 'SOC',
                'name' => 'intakestafftransferssoc',
                'fromName' => 'from_intakestafftransferssoc',
                'toName' => 'to_intakestafftransferssoc',
                'type' => 'dateRangePicker',
                'fromdate' => '',
                'todate' => '',
                'extra_class' => ''
            ],
            [
                'label' => 'Pending Reason',
                'name' => 'intakestafftransfersreadytostartreason',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getReadyToStartReason()
            ],
        ];

        return $filters;
    }

    public function getFilters()
    {
        if(LD::checkFeatureFlag('add-soc-clinician-filter-into-intake-scheduled-bucket')){
            $filters = [
                [
                    'label' => 'Me Mode',
                    'name' => 'intakestafftransfersdefault',
                    'type' => 'checkbox',
                    'value' => true
                ],
                [
                    'label' => 'State',
                    'name' => 'intakestafftransfersstate',
                    'type' => 'multiselect',
                    'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
                ],
                [
                    'label' => 'Region',
                    'name' => 'intakestafftransfersregion',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
                    'disabled' => true
                ],
                [
                    'label' => 'Branch',
                    'name' => 'intakestafftransfersbranch',
                    'type' => 'multiselect',
                    'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
                ],
                [
                    'label' => 'LOB',
                    'name' => 'intakestafflob',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getLOBDropdown()
                ],
                [
                    'label' => self::INTAKECOORDINATOR,
                    'name' => 'intakestafftransferscare',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getIntakeCoordinator()
                ],
                [
                    'label' => 'SOC Clinician',
                    'name' => 'intakestafftransfersClinician',
                    'type' => 'multiselect',
                    'source' => H::getListOfActiveClinican(),
                ],
                [
                    'label' => 'Payer',
                    'name' => 'intakestafftransfersPayer',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getPayers()
                ],
                [
                    'label' => 'Special Payer Group',
                    'name' => 'intakestafftransfersspecialpayergroup',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getPayerGroup()
                ]
            ];

        }else{
            $filters = [
                [
                    'label' => 'Me Mode',
                    'name' => 'intakestafftransfersdefault',
                    'type' => 'checkbox',
                    'value' => true
                ],
                [
                    'label' => 'State',
                    'name' => 'intakestafftransfersstate',
                    'type' => 'multiselect',
                    'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
                ],
                [
                    'label' => 'Region',
                    'name' => 'intakestafftransfersregion',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
                    'disabled' => true
                ],
                [
                    'label' => 'Branch',
                    'name' => 'intakestafftransfersbranch',
                    'type' => 'multiselect',
                    'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
                ],
                [
                    'label' => 'LOB',
                    'name' => 'intakestafflob',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getLOBDropdown()
                ],
                [
                    'label' => self::INTAKECOORDINATOR,
                    'name' => 'intakestafftransferscare',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getIntakeCoordinator()
                ],
                [
                    'label' => 'Payer',
                    'name' => 'intakestafftransfersPayer',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getPayers()
                ],
                [
                    'label' => 'Special Payer Group',
                    'name' => 'intakestafftransfersspecialpayergroup',
                    'type' => 'multiselect',
                    'source' => H::getHelper('ConstantHelper')->getPayerGroup()
                ]
            ];

        }

        return $filters;
    }

    public function getBuckets()
    {
        return $this->buckets;
    }

    public function getBucketTitle($type)
    {
        return $this->buckets[$type]['heading'];
    }

    public function getBucketDescription($type)
    {
        return $this->buckets[$type]['description'];
    }

    public function getDataTableHtml($type, $filterFormName, $defaultSortColumnNumber)
    {
        $dataTable = [];
        switch ($type) {
            case 'intake_need_to_schedule_ipea':
                $dataTable = [
                    'intakeCordinator' => [
                        'data' => 'intakeCordinator',
                        'name' => 'intakeCordinator',
                        'label' => self::INTAKECOORDINATOR
                    ],
                    'FacilityName' => [
                        'data' => 'FacilityName',
                        'name' => 'FacilityName',
                        'label' => 'Branch'
                    ],
                    'clinician' => [
                        'data' => 'clinician',
                        'name' => 'clinician',
                        'label' => 'Clinician'
                    ],
                    'mobile' => [
                        'data' => 'mobile',
                        'name' => 'mobile',
                        'label' => 'Phone Number'
                    ],
                    'patient_name' => [
                        'data' => 'patient_name',
                        'name' => 'patient_name',
                        'label' => 'Client'
                    ],
                    'notes' => [
                        'data' => 'notes',
                        'name' => 'notes',
                        'label' => 'Notes'
                    ],
                    'Action' => [
                        'data' => 'Action',
                        'name' => 'Action',
                        'label' => 'Action'
                    ]
                ];
                break;
            case 'intake_todays_ipea':
                $dataTable = [
                    'intakeCordinator' => [
                        'data' => 'intakeCordinator',
                        'name' => 'intakeCordinator',
                        'label' => self::INTAKECOORDINATOR
                    ],
                    'FacilityName' => [
                        'data' => 'FacilityName',
                        'name' => 'FacilityName',
                        'label' => 'Branch'
                    ],
                    'clinician' => [
                        'data' => 'clinician',
                        'name' => 'clinician',
                        'label' => 'Clinician'
                    ],
                    'patient_name' => [
                        'data' => 'patient_name',
                        'name' => 'patient_name',
                        'label' => 'Client'
                    ],
                    'IPEA_Date' => [
                        "data" => [
                            '_' => 'IPEA_Date.display',
                            'sort' => 'IPEA_Date.timestamp'
                        ],
                        "name" => 'IPEA_Date',
                        "searchable" => false,
                        'label' => 'IPEA Date'
                    ],
                    'IPEA_Time' => [
                        'data' => 'IPEA_Time',
                        'name' => 'IPEA_Time',
                        'label' => 'IPEA Time'
                    ],
                    'live_ipea_status' => [
                        'data' => 'live_ipea_status',
                        'name' => 'live_ipea_status',
                        'label' => 'IPEA Status'
                    ],
                    'notes' => [
                        'data' => 'notes',
                        'name' => 'notes',
                        'label' => 'Notes'
                    ],
                    'Action' => [
                        'data' => 'Action',
                        'name' => 'Action',
                        'label' => 'Action'
                    ]
                ];
                break;

            case 'intake_pending_candidate_starts':
            case 'intake_candidate_starts':
                $dataTable = [
                    'intakeCordinator' => [
                        'data' => 'intakeCordinator',
                        'name' => 'intakeCordinator',
                        'label' => self::INTAKECOORDINATOR
                    ],
                    'FacilityName' => [
                        'data' => 'FacilityName',
                        'name' => 'FacilityName',
                        'label' => 'Branch Location'
                    ],
                    'clinician' => [
                        'data' => 'clinician',
                        'name' => 'clinician',
                        'label' => 'Candidate'
                    ],
                    'patient_name' => [
                        'data' => 'patient_name',
                        'name' => 'patient_name',
                        'label' => 'Client'
                    ],
                    'PatientStatus' => [
                        'data' => 'PatientStatus',
                        'name' => 'PatientStatus',
                        'label' => 'Client Status'
                    ],

                    'CF_GoalSOCDate' => [
                        "data" => [
                            '_' => 'CF_GoalSOCDate.display',
                            'sort' => 'CF_GoalSOCDate.timestamp'
                        ],
                        "name" => 'CF_GoalSOCDate',
                        "searchable" => false,
                        'label' => 'Goal SOC Date',
                        'type' => 'date'
                    ],

                    'Candidate_Start_Date_Time' => [
                        "data" => [
                            '_' => 'Candidate_Start_Date_Time.display',
                            'sort' => 'Candidate_Start_Date_Time.timestamp'
                        ],
                        "name" => 'Candidate_Start_Date_Time',
                        "searchable" => false,
                        'label' => 'Candidate Start Date',
                        'type' => 'date'
                    ],

                    'Candidate_Notes' => [
                        'data' => 'Candidate_Notes',
                        'name' => 'Candidate_Notes',
                        'label' => 'Candidate Notes'
                    ],
                    'Action' => [
                        'data' => 'Action',
                        'name' => 'Action',
                        'label' => 'Action'
                    ]
                ];
                break;

            case 'intake_prospective_clients':
                $dataTable = [
                    'intakeCordinator' => [
                        'data' => 'intakeCordinator',
                        'name' => 'intakeCordinator',
                        'label' => self::INTAKECOORDINATOR
                    ],
                    'FacilityName' => [
                        'data' => 'FacilityName',
                        'name' => 'FacilityName',
                        'label' => 'Branch'
                    ],

                    'patient_name' => [
                        'data' => 'patient_name',
                        'name' => 'patient_name',
                        'label' => 'Client'
                    ],
                    'LOBName' => [
                        'data' => 'LOBName',
                        'name' => 'LOBName',
                        'label' => 'LOB'
                    ],
                    'Payer' => [
                        'data' => 'Payer',
                        'name' => 'Payer',
                        'label' => 'Payer'
                    ],
                    'AuthorizedWeeklyHours' => [
                        'data' => 'AuthorizedWeeklyHours',
                        'name' => 'AuthorizedWeeklyHours',
                        'label' => 'Hours / Visits'
                    ],
                    'ReferralDate' => [
                        "data" => 'ReferralDate',
                        "name" => 'ReferralDate',
                        "searchable" => false,
                        'label' => 'Referral Date',
                        'type' => 'date'
                    ],
                    'CF_GoalSOCDate' => [
                        "data" => 'CF_GoalSOCDate',
                        "name" => 'CF_GoalSOCDate',
                        "searchable" => false,
                        'label' => 'Goal SOC Date',
                        'type' => 'date'
                    ],
                    'intake_pending_reason' => [
                        'data' => 'intake_pending_reason',
                        'name' => 'intake_pending_reason',
                        'label' => 'Pending Reason'
                    ],
                    'notes' => [
                        'data' => 'notes',
                        'name' => 'notes',
                        'label' => 'Notes'
                    ],
                    'intake_followup' => [
                        "data" => 'intake_followup',
                        "name" => 'intake_followup',
                        "searchable" => false,
                        'label' => 'Follow Up Date',
                        'type' => 'date'
                    ],
                    'Action' => [
                        'data' => 'Action',
                        'name' => 'Action',
                        'label' => 'Action'
                    ]
                ];
                break;
                case 'intake_schedule':
                    $dataTable = [
                        'intakeCordinator' => [
                            'data' => 'intakeCordinator',
                            'name' => 'intakeCordinator',
                            'label' => self::INTAKECOORDINATOR
                        ],
                        'FacilityName' => [
                            'data' => 'FacilityName',
                            'name' => 'FacilityName',
                            'label' => 'Branch'
                        ],
                        'patient_name' => [
                            'data' => 'patient_name',
                            'name' => 'patient_name',
                            'label' => 'Client'
                        ],
                        'LOBName' => [
                            'data' => 'LOBName',
                            'name' => 'LOBName',
                            'label' => 'LOB'
                        ],
                        'Payer' => [
                            'data' => 'Payer',
                            'name' => 'Payer',
                            'label' => 'Payer'
                        ],
                        'AuthorizedWeeklyHours' => [
                            'data' => 'AuthorizedWeeklyHours',
                            'name' => 'AuthorizedWeeklyHours',
                            'label' => 'Hours / Visits'
                        ],
                        'IntakeDate' => [
                            "data" => 'IntakeDate',
                            "name" => 'IntakeDate',
                            "searchable" => false,
                            'label' => 'Intake Date',
                            'type' => 'date'
                        ],
                        'CF_GoalSOCDate' => [
                            "data" => 'CF_GoalSOCDate',
                            "name" => 'CF_GoalSOCDate',
                            "searchable" => false,
                            'label' => 'Goal SOC Date',
                            'type' => 'date'
                        ],
                        'notes' => [
                            'data' => 'notes',
                            'name' => 'notes',
                            'label' => 'Notes'
                        ],
                        'intake_followup' => [
                            "data" => 'intake_followup',
                            "name" => 'intake_followup',
                            "searchable" => false,
                            'label' => 'Follow Up Date',
                            'type' => 'date'
                        ],
                        'soc_clinician' => [
                            'data' => 'soc_clinician',
                            'name' => 'soc_clinician',
                            'label' => 'SOC Clinician'
                        ],
                        'Action' => [
                            'data' => 'Action',
                            'name' => 'Action',
                            'label' => 'Action'
                        ]
                    ];
                break;
                case 'intake_pending_soc':
                    $dataTable = [
                        'intakeCordinator' => [
                            'data' => 'intakeCordinator',
                            'name' => 'intakeCordinator',
                            'label' => self::INTAKECOORDINATOR
                        ],
                        'FacilityName' => [
                            'data' => 'FacilityName',
                            'name' => 'FacilityName',
                            'label' => 'Branch'
                        ],
                        'patient_name' => [
                            'data' => 'patient_name',
                            'name' => 'patient_name',
                            'label' => 'Client'
                        ],
                        'LOBName' => [
                            'data' => 'LOBName',
                            'name' => 'LOBName',
                            'label' => 'LOB'
                        ],
                        'Payer' => [
                            'data' => 'Payer',
                            'name' => 'Payer',
                            'label' => 'Payer'
                        ],
                        'AuthorizedWeeklyHours' => [
                            'data' => 'AuthorizedWeeklyHours',
                            'name' => 'AuthorizedWeeklyHours',
                            'label' => 'Hours / Visits'
                        ],
                        'admission_visit_date' => [
                            "data" => 'admission_visit_date',
                            "name" => 'admission_visit_date',
                            "searchable" => false,
                            'label' => 'Intake Completed',
                            'type' => 'date'
                        ],
                        'CF_GoalSOCDate' => [
                            "data" => 'CF_GoalSOCDate',
                            "name" => 'CF_GoalSOCDate',
                            "searchable" => false,
                            'label' => 'Goal SOC Date',
                            'type' => 'date'
                        ],
                        'intake_readytostart' => [
                            'data' => 'intake_readytostart',
                            'name' => 'intake_readytostart',
                            'label' => 'Ready to Start?'
                        ],
                        'intake_readytostart_reason' => [
                            'data' => 'intake_readytostart_reason',
                            'name' => 'intake_readytostart_reason',
                            'label' => 'Reason'
                        ],
                        'notes' => [
                            'data' => 'notes',
                            'name' => 'notes',
                            'label' => 'Notes'
                        ],
                        'intake_followup' => [
                            "data" => 'intake_followup',
                            "name" => 'intake_followup',
                            "searchable" => false,
                            'label' => 'Follow Up Date',
                            'type' => 'date'
                        ],
                        'soc_clinician' => [
                            'data' => 'soc_clinician',
                            'name' => 'soc_clinician',
                            'label' => 'SOC Clinician'
                        ],
                        'Action' => [
                            'data' => 'Action',
                            'name' => 'Action',
                            'label' => 'Action'
                        ]
                    ];
                break;
                case 'intake_client_starts':
                    $dataTable = [
                        'intakeCordinator' => [
                            'data' => 'intakeCordinator',
                            'name' => 'intakeCordinator',
                            'label' => self::INTAKECOORDINATOR
                        ],
                        'FacilityName' => [
                            'data' => 'FacilityName',
                            'name' => 'FacilityName',
                            'label' => 'Branch'
                        ],
                        'patient_name' => [
                            'data' => 'patient_name',
                            'name' => 'patient_name',
                            'label' => 'Client'
                        ],
                        'LOBName' => [
                            'data' => 'LOBName',
                            'name' => 'LOBName',
                            'label' => 'LOB'
                        ],
                        'Payer' => [
                            'data' => 'Payer',
                            'name' => 'Payer',
                            'label' => 'Payer'
                        ],
                        'AuthorizedWeeklyHours' => [
                            'data' => 'AuthorizedWeeklyHours',
                            'name' => 'AuthorizedWeeklyHours',
                            'label' => 'Hours / Visits'
                        ],
                        'CF_GoalSOCDate' => [
                            "data" => 'CF_GoalSOCDate',
                            "name" => 'CF_GoalSOCDate',
                            "searchable" => false,
                            'label' => 'Goal SOC Date',
                            'type' => 'date'
                        ],
                        'notes' => [
                            'data' => 'notes',
                            'name' => 'notes',
                            'label' => 'Notes'
                        ],
                        'soc_clinician' => [
                            'data' => 'soc_clinician',
                            'name' => 'soc_clinician',
                            'label' => 'SOC Clinician'
                        ],
                        'Action' => [
                            'data' => 'Action',
                            'name' => 'Action',
                            'label' => 'Action'
                        ]
                    ];
                    break;
                case 'intake_pending_non_admit':
                    $dataTable = [
                        'Assignedto' => [
                            'data' => 'Assignedto',
                            'name' => 'Assignedto',
                            'label' => 'Assigned to',
                            "searchable" => false,
                            "sortable" => false
                        ],
                        'FacilityName' => [
                            'data' => 'FacilityName',
                            'name' => 'FacilityName',
                            'label' => 'Branch'
                        ],
                        'patient_name' => [
                            'data' => 'patient_name',
                            'name' => 'patient_name',
                            'label' => 'Client'
                        ],
                        'PatientStatus' => [
                            'data' => 'PatientStatus',
                            'name' => 'PatientStatus',
                            'label' => 'KanTime Status'
                        ],
                        'LOBName' => [
                            'data' => 'LOBName',
                            'name' => 'LOBName',
                            'label' => 'LOB'
                        ],
                        'Payer' => [
                            'data' => 'Payer',
                            'name' => 'Payer',
                            'label' => 'Payer'
                        ],
                        'AuthorizedWeeklyHours' => [
                            'data' => 'AuthorizedWeeklyHours',
                            'name' => 'AuthorizedWeeklyHours',
                            'label' => 'Hours / Visits'
                        ],
                        'intake_nonadmit_reason' => [
                            'data' => 'intake_nonadmit_reason',
                            'name' => 'intake_nonadmit_reason',
                            'label' => 'Non Admit Reason'
                        ],
                        'non_admit_status' => [
                            'data' => 'non_admit_status',
                            'name' => 'non_admit_status',
                            'label' => 'Status',
                            "searchable" => false,
                        ],
                        'intake_approved_date' => [
                            "data" => 'intake_approved_date',
                            "name" => 'intake_approved_date',
                            "searchable" => false,
                            'label' => 'Approved Date',
                            'type' => 'date'
                        ],
                        'notes' => [
                            'data' => 'notes',
                            'name' => 'notes',
                            'label' => 'Notes'
                        ],
                        'Action' => [
                            'data' => 'Action',
                            'name' => 'Action',
                            'label' => 'Action'
                        ]
                    ];
                    break;
                default:
        }
        return view('intake.staffTransfersDatatable', [
            "type" => $type,
            'dataTable' => $dataTable,
            'filterFormName' => $filterFormName,
            'defaultSortColumnNumber' => $defaultSortColumnNumber
        ]);
    }

    protected function applyFilterForUnion($request, &$bucket)
    {
        $bindingParams = [];
        $branch = [];
        $state = [];
        $region = [];
        $users = [];
        $payer = [];
        $payerGroup = [];
        $pendingReason = [];
        $readyToStartReason = [];
        $skills = [];
        $bdRep = [];
        $isMeMode = 0;
        $showAll = 0;
        $lob = [];
        $nonAdmitReason = [];
        $socClinician = [];
        $intakeDateFrom = $intakeDateTo = '';
        $socDateFrom = $socDateTo = '';
        $referralDateFrom = $referralDateTo = '';
        if (isset($request['filters'])) {
            foreach ($request['filters'] as $value) {
                switch ($value['name']) {
                    case "intakestafftransfersshowall":
                        $showAll = 1;
                        break;
                    case "intakestafflob":
                        $lob[] = $value['value'];
                        break;
                    case "intakestafftransfersstate":
                        $state[] = $value['value'];
                        break;
                    case "intakestafftransfersbranch":
                        $branch[] = $value['value'];
                        break;
                    case "intakestafftransfersregion":
                        $region[] = $value['value'];
                        break;
                    case "intakestafftransferscare":
                        $users[] = $value['value'];
                        break;
                    case "intakestafftransfersPayer":
                        $payer[] = $value['value'];
                        break;
                    case "intakestafftransfersspecialpayergroup":
                        $payerGroup[] = $value['value'];
                        break;
                    case "intakestafftransfersdefault":
                        $isMeMode = 1;
                        break;
                    case "intakestafftransferspendingreason":
                        $pendingReason[] = $value['value'];
                        break;
                    case "intakestafftransfersreadytostartreason":
                        $readyToStartReason[] = $value['value'];
                        break;
                    case "intakestafftransfersskills":
                        $skills[] = $value['value'];
                        break;
                    case "intakestafftransfersbdrep":
                        $bdRep[] = $value['value'];
                        break;
                    case "intakestafftransfersintakedatefrom":
                        $intakeDateFrom = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 00:00:00' : '';
                        break;
                    case "intakestafftransfersintakedateto":
                        $intakeDateTo = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 23:59:59' : '';
                        break;
                    case "from_intakestafftransferssoc":
                        $socDateFrom = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 00:00:00' : '';
                        break;
                    case "to_intakestafftransferssoc":
                        $socDateTo = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 23:59:59' : '';
                        break;
                    case "from_intakeclientstartsoc":
                        $socDateFrom = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 00:00:00' : '';
                        break;
                    case "to_intakeclientstartsoc":
                        $socDateTo = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 23:59:59' : '';
                        break;
                    case "intakestafftransfersreferraldatefrom":
                        $referralDateFrom = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 00:00:00' : '';
                        break;
                    case "intakestafftransfersreferraldateto":
                        $referralDateTo = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 23:59:59' : '';
                        break;
                    case "intakestafftransfersnonadmitreason":
                        $nonAdmitReason[] = $value['value'];
                        break;
                    case "intakestafftransfersClinician":
                        $socClinician[] = $value['value'];
                        break;
                    default:
                }
            }
        }
        if (!empty($state)) {
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $queryStr = [];
            foreach(array_column($facilities->toArray(), 'id') as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("aoc_facility_id IN (".(implode(",", $queryStr)).")");
        }

        if (!empty($branch)) {
            $queryStr = [];
            foreach($branch as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("aoc_facility_id IN (".(implode(",", $queryStr)).")");
        }

        if(!empty($lob)) {
            $queryStr = [];
            foreach($lob as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("LOBID IN (".(implode(",", $queryStr)).")");
        }

        if (!empty($region)) {
            $queryStr = [];
            foreach($region as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("region IN (".(implode(",", $queryStr)).")");
        }

        if (!empty($users) && !$isMeMode) {
            $queryStr = [];
            foreach($users as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("ic_id IN (".(implode(",", $queryStr)).")");
        } elseif ($isMeMode && !$showAll) {
            $bucket->whereRaw("ic_id = ?");
            array_push($bindingParams, Auth::user()->id);
        }

        if (!empty($payer)) {
            $queryStr = [];
            foreach($payer as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("Payer IN (".(implode(",", $queryStr)).")");
        }

        if (!empty($payerGroup)) {
            $queryStr = [];
            foreach($payerGroup as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("payer_group IN (".(implode(",", $queryStr)).")");
        }

        if (!empty($pendingReason)) {
            $queryStr = [];
            foreach($pendingReason as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("intake_pending_reason IN (".(implode(",", $queryStr)).")");
        }

        if (!empty($readyToStartReason)) {
            $queryStr = [];
            foreach($readyToStartReason as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("intake_readytostart_reason IN (".(implode(",", $queryStr)).")");
        }

        if (!empty($bdRep)) {
            $queryStr = [];
            foreach($bdRep as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("BD_Rep IN (".(implode(",", $queryStr)).")");
        }

        if (!empty($skills)) {
            $bucket->whereRaw("CF_Skills REGEXP CONCAT('(^|,)(', REPLACE(?, ',', '|'), ')(,|$)')");
            array_push($bindingParams, implode(",", $skills));
        }

        if (!empty($nonAdmitReason)) {
            $queryStr = [];
            foreach($nonAdmitReason as $val) {
                array_push($bindingParams, $val);
                $queryStr[] = '?';
            }
            $bucket->whereRaw("intake_nonadmit_reason IN (".(implode(",", $queryStr)).")");
        }

        if (!empty($intakeDateFrom) && !empty($intakeDateTo)) {
			$bucket->whereRaw("IntakeDate BETWEEN ? AND  ?");
			array_push($bindingParams, $intakeDateFrom);
            array_push($bindingParams, $intakeDateTo);
        }

        if (!empty($socDateFrom) && !empty($socDateTo)) {
            $bucket->whereRaw("CF_GoalSOCDate BETWEEN ? AND  ?");
            array_push($bindingParams, $socDateFrom);
            array_push($bindingParams, $socDateTo);
        }

        if (!empty($referralDateFrom) && !empty($referralDateTo)) {
            $bucket->whereRaw("ReferralDate BETWEEN ? AND  ?");
            array_push($bindingParams, $referralDateFrom);
            array_push($bindingParams, $referralDateTo);
        }

        if(LD::checkFeatureFlag('add-soc-clinician-filter-into-intake-scheduled-bucket')){
            if($request['type'] != 'showall'){
                if (!empty($socClinician)) {
                    $bucket->whereIn("soc_clinician", $socClinician);
                }
            }
        }
        $bindings = $bucket->getBindings();
        $bucket->setBindings(array_merge($bindings, $bindingParams));
    }

    protected function applyFilter($request, &$bucket)
    {
        $branch = [];
        $state = [];
        $region = [];
        $users = [];
        $payer = [];
        $payerGroup = [];
        $pendingReason = [];
        $readyToStartReason = [];
        $skills = [];
        $bdRep = [];
        $isMeMode = 0;
        $showAll = 0;
        $lob = [];
        $nonAdmitReason = [];
        $socClinician = [];
        $intakeDateFrom = $intakeDateTo = '';
        $socDateFrom = $socDateTo = '';
        $intakeIpeaDateFrom = $intakeIpeaDateTo = '';
        $referralDateFrom = $referralDateTo = '';
        if (isset($request['filters'])) {
            foreach ($request['filters'] as $value) {
                switch ($value['name']) {
                    case "intakestafftransferipeadatefrom":
                        $intakeIpeaDateFrom= (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) : '';

                        break;
                    case "intakestafftransferipeadateto":
                        $intakeIpeaDateTo= (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) : '';
                        break;
                    case "intakestafftransfersshowall":
                        $showAll = 1;
                        break;
                    case "intakestafflob":
                        $lob[] = $value['value'];
                        break;
                    case "intakestafftransfersstate":
                        $state[] = $value['value'];
                        break;
                    case "intakestafftransfersbranch":
                        $branch[] = $value['value'];
                        break;
                    case "intakestafftransfersregion":
                        $region[] = $value['value'];
                        break;
                    case "intakestafftransferscare":
                        $users[] = $value['value'];
                        break;
                    case "intakestafftransfersPayer":
                        $payer[] = $value['value'];
                        break;
                    case "intakestafftransfersspecialpayergroup":
                        $payerGroup[] = $value['value'];
                        break;
                    case "intakestafftransfersdefault":
                        $isMeMode = 1;
                        break;
                    case "intakestafftransferspendingreason":
                        $pendingReason[] = $value['value'];
                        break;
                    case "intakestafftransfersreadytostartreason":
                        $readyToStartReason[] = $value['value'];
                        break;
                    case "intakestafftransfersskills":
                        $skills[] = $value['value'];
                        break;
                    case "intakestafftransfersbdrep":
                        $bdRep[] = $value['value'];
                        break;
                    case "intakestafftransfersintakedatefrom":
                        $intakeDateFrom = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 00:00:00' : '';
                        break;
                    case "intakestafftransfersintakedateto":
                        $intakeDateTo = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 23:59:59' : '';
                        break;
                    case "from_intakestafftransferssoc":
                        $socDateFrom = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 00:00:00' : '';
                        break;
                    case "to_intakestafftransferssoc":
                        $socDateTo = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 23:59:59' : '';
                        break;
                    case "from_intakeclientstartsoc":
                        $socDateFrom = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 00:00:00' : '';
                        break;
                    case "to_intakeclientstartsoc":
                        $socDateTo = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 23:59:59' : '';
                        break;
                    case "intakestafftransfersreferraldatefrom":
                        $referralDateFrom = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 00:00:00' : '';
                        break;
                    case "intakestafftransfersreferraldateto":
                        $referralDateTo = (!empty($value['value'])) ? date('Y-m-d',strtotime($value['value'])) . ' 23:59:59' : '';
                        break;
                    case "intakestafftransfersnonadmitreason":
                        $nonAdmitReason[] = $value['value'];
                        break;
                    case "intakestafftransfersClinician":
                        $socClinician[] = $value['value'];
                        break;
                    default:
                }
            }
        }


        if (!empty($state)) {
            $facilities = H::getHelper("ConstantHelper")->getFacilitiesByState($state);
            $bucket->whereIn("mssql_patients.aoc_facility_id", array_column($facilities->toArray(), 'id'));
        }

        if (!empty($branch)) {
            $bucket->whereIn("mssql_patients.aoc_facility_id", $branch);
        }
       // var_dump($lob);
        if(!empty($lob)) {
            $bucket->whereIn("intake.LOBID", $lob);
        }

        if (!empty($region)) {
            $bucket->whereIn("mssql_facilities.region", $region);
        }


        if (!empty($users) && !$isMeMode) {
            $bucket->whereIn("intake.ic_id", $users);
        } elseif ($isMeMode && !$showAll) {
            $bucket->where("intake.ic_id", "=", Auth::user()->id);
        }

        if (!empty($payer)) {
            $bucket->whereIn("mssql_patients.Payer", $payer);
        }

        if (!empty($payerGroup)) {
            $bucket->whereIn("mssql_patients.payer_group", $payerGroup);
        }

        if (!empty($pendingReason)) {
            $bucket->whereIn("mssql_patients.intake_pending_reason", $pendingReason);
        }

        if (!empty($readyToStartReason)) {
            $bucket->whereIn("mssql_patients.intake_readytostart_reason", $readyToStartReason);
        }

        if (!empty($bdRep)) {
            $bucket->whereIn("mssql_patients.BD_Rep", $bdRep);
        }

        if (!empty($skills)) {
            $bucket->whereRaw("mssql_patients.CF_Skills REGEXP CONCAT('(^|,)(', REPLACE(?, ',', '|'), ')(,|$)')", implode(",", $skills));
        }

        if (!empty($nonAdmitReason)) {
            $bucket->whereIn("mssql_patients.intake_nonadmit_reason", $nonAdmitReason);
        }

        if (!empty($intakeDateFrom) && !empty($intakeDateTo)) {
            $bucket->whereRaw("intake.admission_visit_date Between ? and ?", [$intakeDateFrom, $intakeDateTo]);
        }

        if (!empty($socDateFrom) && !empty($socDateTo)) {
            $bucket->whereBetween("intake.CF_GoalSOCDate", [$socDateFrom, $socDateTo]);
        }

        if (!empty($referralDateFrom) && !empty($referralDateTo)) {
            $bucket->whereBetween("intake.ReferralDate", [$referralDateFrom, $referralDateTo]);
        }

        if (!empty($intakeIpeaDateFrom) && !empty($intakeIpeaDateTo) && $showAll) {
            $bucket->whereBetween("mssql_recruitertracker.IPEA_Date", [$intakeIpeaDateFrom, $intakeIpeaDateTo]);
        }
        if (LD::checkFeatureFlag('add-soc-clinician-filter-into-intake-scheduled-bucket')) {
            if (!empty($socClinician)) {
                $placeholders = rtrim(str_repeat('?,', count($socClinician)), ',');
                $bucket->havingRaw("soc_clinician IN ($placeholders)", $socClinician);
            }
        }

    }

    public function getIntakeStaffTransfers($request, $showCount = 0)
    {
        $intakeService = app(IntakeService::class);
        $type = $request->input("type");
        $request = $request->all();

        if (
            $type == 'intake_todays_ipea' ||
            $type == 'intake_need_to_schedule_ipea'  ||
            $type == 'intake_pending_candidate_starts' ||
            $type == 'intake_candidate_starts'
        ) {
            $bucket = DB::table("mssql_recruitertracker")
                ->select(
                    'mssql_recruitertracker.id',
                    'mssql_recruitertracker.aoc_patient_id',
                    'mssql_recruitertracker.aoc_candidate_id',
                    DB::raw("users.name AS intakeCordinator"),
                    'mssql_facilities.FacilityName',
                    DB::raw("CONCAT(candidates.firstName, ' ', candidates.lastName) AS clinician"),
                    DB::raw("concat('(',substr(REGEXP_REPLACE(candidates.mobile, '[^0-9]+', ''),1,3),') ',
                        substr(REGEXP_REPLACE(candidates.mobile, '[^0-9]+', ''),4,3),'-',
                        substr(REGEXP_REPLACE(candidates.mobile, '[^0-9]+', ''),7)) AS mobile"
                    ),
                    DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                    DB::raw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
                                where mssql_recruitertracker.id = notes.entity_id and entity='tracker' order by created_at desc limit 1) AS notes"),
                    ((LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) ?
                    DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                        left join mssql_providers p on sm.ProviderID = p.ProviderID
                        where sm.CGTaskID  = intake.intake_admission_visit order by sm.ScheduleDate desc limit 1), '') as soc_clinician") :
                    DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                        left join mssql_providers p on sm.ProviderID = p.ProviderID
                        where sm.CGTaskID  = mssql_patients.intake_admission_visit order by sm.ScheduleDate desc limit 1), '') as soc_clinician")),
                );
			$bucket->leftJoin("mssql_patients", function ($query) {
                $query->on("mssql_recruitertracker.aoc_patient_id", "=", "mssql_patients.id");
            });
            if(LD::checkFeatureFlag('todays_ipea_fix_records_appearing_multiple_times')) {
                $bucket->leftJoin("intake", function ($query) {
                    $query->on("mssql_recruitertracker.aoc_intake_id", "=", "intake.id");
                });
            } else {
                $bucket->leftJoin("intake", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
            }

            $bucket->leftJoin("mssql_facilities", function ($query) {
                $query->on("mssql_recruitertracker.aoc_facility_id", "=", "mssql_facilities.id");
            });
            $bucket->leftJoin("candidates", function ($query) {
                $query->on("mssql_recruitertracker.aoc_candidate_id", "=", "candidates.id");
            });
            $bucket->leftJoin("users", function ($query) {
                $query->on("intake.ic_id", "=", "users.id");
            });
            $bucket->whereRaw('ifnull(aoc_intake_id,0) <> 0');
			$bucket->where('candidates.is_deleted', 0);

            if(LD::checkFeatureFlag('exclude-intake-records-which-are-completed')){
                $bucket->where('intake.intake_completed', '<>', 1);
            }
            if (LD::checkFeatureFlag('fixed-non-admit-bucket')) {
                $bucket->whereRaw("ifnull(intake.intake_outcome,'') <> 'Non Admit'");
                $bucket->whereRaw("ifnull(intake.intake_outcome,'') <> 'Approve Non Admit'");
            }

        }
        if ($type == 'intake_need_to_schedule_ipea') {
            if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                $bucket->addSelect('mssql_patients.LOBID', 'mssql_recruitertracker.created_at as tracker_created_date');
            }
            $bucket->where('candidate_current_status', "Need to Schedule IPEA");
        }

        if($type=='intake_pending_candidate_starts') {
            $bucket->leftJoin("mssql_patientstatus", function ($query) {
                $query->on("mssql_patients.Patient_CurrentStatusID", "=", "mssql_patientstatus.PatientStatusID");
            });

            $bucket->addSelect('mssql_patientstatus.PatientStatus',
                DB::raw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
                    where mssql_recruitertracker.id = notes.entity_id and entity='tracker' order by created_at desc limit 1
                ) AS Candidate_Notes"),
                'intake.CF_GoalSOCDate',
                DB::raw(
                    'DATE_FORMAT(
                        concat(
                            DATE(mssql_recruitertracker.Candidate_Start_Date),
                            " ",
                            IFNULL(mssql_recruitertracker.Candidate_Start_Time,"00:00:00")
                        ),
                        "' . config('constant')['mysqlsearchdatetimeformat'] . '"
                    )AS Candidate_Start_Date_Time'
                )
            );

            $bucket->
            whereIn(
                'Candidate_Current_Status',
                ['Client Match', 'Current Nurse Placed', 'New Nurse Placed', 'Pending Start']
            );

            $bucket->whereDate('mssql_recruitertracker.Candidate_Start_Date', '>=', date('Y-m-d'));
            if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                $bucket->addSelect('mssql_patients.LOBID');
            }
        }

        if($type=='intake_candidate_starts') {

            $tempDateTime = date('Y-m-d');
            $tempDateTime.=" 23:59:59";
            $varNoDateTimeEntry = '0000-00-00 00:00:00';
            $bucket->leftJoin("mssql_patientstatus", function ($query) {
                $query->on("mssql_patients.Patient_CurrentStatusID", "=", "mssql_patientstatus.PatientStatusID");
            });
            $bucket->addSelect('mssql_patientstatus.PatientStatus',
            DB::raw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
                    where mssql_recruitertracker.id = notes.entity_id and entity='tracker' order by created_at desc limit 1
                ) AS Candidate_Notes"),
            'intake.CF_GoalSOCDate',
            DB::raw(
                'DATE_FORMAT(
                    concat(
                        DATE(mssql_recruitertracker.Candidate_Start_Date),
                        " ",
                        IFNULL(mssql_recruitertracker.Candidate_Start_Time,"00:00:00")
                    ),
                    "' . config('constant')['mysqlsearchdatetimeformat'] . '"
                )AS Candidate_Start_Date_Time'
            )
            );
            $bucket
            ->whereIn(
                'Candidate_Current_Status',
                ['Client Match', 'New Nurse Placed', 'Current Nurse Placed']
            );
            $bucket
            ->where('Candidate_Start_Date', '<=', $tempDateTime)
            ->where('Candidate_Start_Date', '!=', $varNoDateTimeEntry);
            if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                $bucket->addSelect('mssql_patients.LOBID');
            }
        }

        if ($type == 'intake_todays_ipea') {
            $isShowAll = 0;
            foreach($request['filters'] as $filter) {
                if($filter['name']=='intakestafftransfersshowall'){
                    $isShowAll = 1;
                }
            }
            if(!$isShowAll) {
                $bucket->where('IPEA_Date', '<=', Carbon::now());
            }

            $bucket->addSelect(
                DB::raw('IF(
                    (
                        mssql_recruitertracker.IPEA_Date IS NULL or
                        mssql_recruitertracker.IPEA_Date = \'\' or
                        mssql_recruitertracker.IPEA_Date = \'0000-00-00 00:00:00\'), \'\',
                        mssql_recruitertracker.IPEA_Date
                    )
                as IPEA_Date'),
                'mssql_recruitertracker.IPEA_Time',
                'mssql_recruitertracker.live_ipea_status',
                'mssql_recruitertracker.id',
                'mssql_recruitertracker.linked_IPEA',
            );

            $bucket->where('candidate_current_status', "IPEA - Scheduled");
            if(LD::checkFeatureFlag('red-highlights-intake-all-buckets')){
                $bucket->addSelect('mssql_patients.LOBID');
            }
        }
        if ($type == 'intake_prospective_clients') {
                    $patients = DB::table("intake")
                    ->select(
                        DB::raw('\'intake\' as type'),
                        'mssql_facilities.id as aoc_facility_id',
                        'lob.LOBID as LOBID',
                        'mssql_facilities.region as region',
                        'intake.ic_id as ic_id',
                        DB::raw(' IF((mssql_patients.Payer is NULL OR mssql_patients.Payer = \'\'),
                        mssql_patients.RespitePayer, mssql_patients.Payer) as Payer'),
                        'mssql_patients.payer_group as payer_group',
                        'intake.intake_pending_reason as intake_pending_reason',
                        'intake.intake_readytostart_reason as intake_readytostart_reason',
                        'mssql_patients.BD_Rep as BD_Rep',
                        'mssql_patients.CF_Skills as CF_Skills',
                        'intake.intake_nonadmit_reason as intake_nonadmit_reason',
                        DB::raw('IF(
                            (
                                intake.CF_GoalSOCDate IS NULL or
                                intake.CF_GoalSOCDate = \'\' or
                                intake.CF_GoalSOCDate = \'0000-00-00 00:00:00\'), \'\',
                                intake.CF_GoalSOCDate
                            )
                        as CF_GoalSOCDate'),
                        DB::raw('IF(
                            (
                                intake.ReferralDate IS NULL or
                                intake.ReferralDate = \'\' or
                                intake.ReferralDate = \'0000-00-00 00:00:00\'), \'\',
                                intake.ReferralDate
                            )
                        as ReferralDate'),
                        'intake.id',
						'mssql_patients.id as pid',
                        DB::raw("users.name AS intakeCordinator"),
                        'mssql_facilities.FacilityName',
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                        'lob.LOBName',
                        DB::raw($intakeService->getPatientHoursFieldCaseSql('lob.LOBID') . ' AS AuthorizedWeeklyHours'),
                        DB::raw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
                                    where mssql_patients.id = notes.entity_id and entity='client' order by created_at desc limit 1) AS notes"),
                        DB::raw('IF(
                            (
                                intake.intake_followup IS NULL or
                                intake.intake_followup = \'\' or
                                intake.intake_followup = \'0000-00-00 00:00:00\'), \'\',
                                intake.intake_followup
                            )
                        as intake_followup'),
                        ((LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) ?
                        DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                            left join mssql_providers p on sm.ProviderID = p.ProviderID
                            where sm.CGTaskID  = intake.intake_admission_visit order by sm.ScheduleDate desc limit 1), '') as soc_clinician") :
                        DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                            left join mssql_providers p on sm.ProviderID = p.ProviderID
                            where sm.CGTaskID  = mssql_patients.intake_admission_visit order by sm.ScheduleDate desc limit 1), '') as soc_clinician")),
                        );
                $patients->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $patients->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $patients->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $patients->leftJoin("users", function ($query) {
                    $query->on("users.id", "=", "intake.ic_id");
                });
                $patients->where(function ($query) {
                    // non-therapy
                    $query->where(function ($query) {
                        $query->whereNotIn('lob.LOBID', [
                            LOB::PT->value,
                            LOB::OT->value,
                            LOB::ST->value,
                            LOB::COLORADO->value,
                        ]);
                        $query->where('mssql_patients.Patient_CurrentStatusID', 4);
                        $query->whereRaw('(ifnull(intake.admission_visit_scheduled,0) <> 1 OR mssql_patients.IntakeID IS NULL)');
                        $query->where(function ($query) {
                            $query->whereNull('mssql_patients.ActualIntakeDate')
                            ->orWhereRaw('mssql_patients.ActualIntakeDate = \'0000-00-00\'');
                        });
                    });

                    // therapy
                    $query->orWhere(function ($query) {
                        $query->whereIn('lob.LOBID', [
                            LOB::PT->value,
                            LOB::OT->value,
                            LOB::ST->value,
                            LOB::COLORADO->value,
                        ]);
                        //add clause so that Status = 4 OR (Status = 7 AND there is NO intake_admission_visit
                        $query->where(function ($query) {
                            $query->where('mssql_patients.Patient_CurrentStatusID', 4);
                            $query->orWhere(function ($query) {
                                $query->where('mssql_patients.Patient_CurrentStatusID', 7);
                                if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                                    $query->whereRaw('ifnull(intake.intake_admission_visit,0) = 0');
                                } else {
                                    $query->whereRaw('ifnull(intake.intake_admission_visit,0) = 0');
                                }
                            });
                        });
                        $query->whereIn('mssql_patients.IntakeStatus', [
                            'T-Intake Pending',
                            'T-Staffing Pending',
                            'T-Waitlist for Eval',
                            'T - Intake Pending',
                            'T - Staffing Pending',
                            'T - Waitlist for Eval'
                        ]);
                    });

					//New referral
					if(LD::checkFeatureFlag('intake-new-referral-change')) {
						$query->orWhere(function ($query) {
							$query->where('intake.IntakeStatus', 'Pending');
							$query->whereRaw('ifnull(intake.admission_visit_scheduled,0) <> 1');
                            $query->whereRaw('ifnull(intake.intake_admission_visit,0) = 0');
							//$query->whereRaw('mssql_patients.Patient_CurrentStatusID not in (1,2,5) AND mssql_patients.LOBID <> IFNULL(intake.LOBID, 0)');
                            $query->whereRaw('mssql_patients.Patient_CurrentStatusID not in (2,5) AND mssql_patients.LOBID <> IFNULL(intake.LOBID, 0)');
						});
					}

                    if(LD::checkFeatureFlag('intake-colorado-bucket-logic-change')) {
                        // specific to colorado fix to show all records with no LOBID on the intake form regardless of other data
                        $query->orWhere(function ($query) {
                            $query->whereNull("intake.LOBID");
                        });
                    }
                });
                //$patients->where('mssql_patients.is_intake', '1');
                $patients->whereRaw('ifnull(mssql_patients.IsNonAdmit,0) = 0');
                if(LD::checkFeatureFlag('client-new-changes')){
                    $patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
                }
                $patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
                mssql_patients.Patient_LastName) Not Like ?',['%test%']);

                if(LD::checkFeatureFlag('exclude-intake-records-which-are-completed')){
                    $patients->where('intake.intake_completed', '<>', 1);
                }
                if (LD::checkFeatureFlag('fixed-non-admit-bucket')) {
                    $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Non Admit'");
                    $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Approve Non Admit'");
                }

                $bindings = $patients->getBindings();
                $bucket = DB::table(DB::raw("({$patients->toSql()}) as combined_table"))
                            ->setBindings($bindings);
        }
        if ($type == 'showall') {
                $patients = DB::table("intake")
                    ->select(
                        DB::raw('\'intake\' as type'),
                        'mssql_facilities.id as aoc_facility_id',
                        'lob.LOBID as LOBID',
                        'mssql_facilities.region as region',
                        'intake.ic_id as ic_id',
                        'mssql_patients.payer_group as payer_group',
                        'intake.intake_pending_reason as intake_pending_reason',
                        'intake.intake_readytostart_reason as intake_readytostart_reason',
                        'mssql_patients.BD_Rep as BD_Rep',
                        'mssql_patients.CF_Skills as CF_Skills',
                        'intake.intake_nonadmit_reason as intake_nonadmit_reason',
                        'intake.id',
						'mssql_patients.id as pid',
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                        'mssql_facilities.FacilityName',
                        DB::raw('IF(
                            (
                                intake.ReferralDate IS NULL or
                                intake.ReferralDate = \'\' or
                                intake.ReferralDate = \'0000-00-00 00:00:00\'), \'\',
                                intake.ReferralDate
                            )
                        as ReferralDate'),
                        'mssql_patients.IntakeStatus',
                        'lob.LOBName',
                        'mssql_patients.CF_Specialized',
                        DB::raw(' IF((mssql_patients.Payer is NULL OR mssql_patients.Payer = \'\'),
                        mssql_patients.RespitePayer, mssql_patients.Payer) as Payer'),
                        DB::raw("(select name
                            from payment_source
                            where payment_source.payersource_id = mssql_patients.secondary_payer_id
                            ) as Secondary_Payer"
                        ),
                        DB::raw("(select name
                            from payment_source
                            where payment_source.payersource_id = mssql_patients.tertiary_payer_id
                            ) as Tertiary_Payer"
                        ),
                        'mssql_patients.CF_TerminalPayer',
                        DB::raw($intakeService->getPatientHoursFieldCaseSql('lob.LOBID') . ' AS AuthorizedWeeklyHours'),
                        'mssql_patients.AuthorizedWeeklyVisits',
                        DB::raw("ROUND((select sum(ShiftHours)
                            from NeedToHire_Schedules
                            left join NeedToHire_Accounts on NeedToHire_Accounts.aoc_patient_id = mssql_patients.id
                            where NeedToHire_Accounts.id = NeedToHire_Schedules.aoc_nthaccounts_id
                            and NeedToHire_Schedules.IsActive=1),2) as totalhours"
                        ),
                        DB::raw('IF(
                            (
                                mssql_patients.INTAKE_ACCEPTEDDATE IS NULL or
                                mssql_patients.INTAKE_ACCEPTEDDATE = \'\' or
                                mssql_patients.INTAKE_ACCEPTEDDATE = \'0000-00-00 00:00:00\'), \'\',
                                mssql_patients.INTAKE_ACCEPTEDDATE
                            )
                        as INTAKE_ACCEPTEDDATE'),
                        DB::raw('IF(
                            (
                                intake.CF_GoalSOCDate IS NULL or
                                intake.CF_GoalSOCDate = \'\' or
                                intake.CF_GoalSOCDate = \'0000-00-00 00:00:00\'), \'\',
                                intake.CF_GoalSOCDate
                            )
                        as CF_GoalSOCDate'),
                        DB::raw('IF(
                            (
                                mssql_patients.requested_soc_date IS NULL or
                                mssql_patients.requested_soc_date = \'\' or
                                mssql_patients.requested_soc_date = \'0000-00-00 00:00:00\'), \'\',
                                mssql_patients.requested_soc_date
                            )
                        as requested_soc_date'),
                        DB::raw("(select hospital
                            from intake
                            where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                            ) as Referral_Source"
                        ),
                        DB::raw("(select referral_type
                            from intake
                            where intake.aoc_patient_id = mssql_patients.id order by id desc limit 1
                            ) as Referral_Type"
                        ),
                        DB::raw('IF(
                            (
                                mssql_patients.NonAdmitDate IS NULL or
                                mssql_patients.NonAdmitDate = \'\' or
                                mssql_patients.NonAdmitDate = \'0000-00-00 00:00:00\'), \'\',
                                mssql_patients.NonAdmitDate
                            )
                        as NonAdmitDate'),
                        DB::raw('IF(
                            (
                                intake.intake_controllable IS NULL or
                                intake.intake_controllable = \'\'), \'\',
                                IF (intake.intake_controllable = 0, \'No\', \'Yes\')
                            )
                        as intake_controllable'),
                        DB::raw("users.name AS intakeCordinator"),
                        'intake.intake_bucket as bucket'
                    );
                if(LD::checkFeatureFlag('add-client-address-intake-export')) {
                    $patients->addSelect(
                        'mssql_patients.PatientAccount',
                        'mssql_patients.Patient_Address1 as Address1',
                        'mssql_patients.Patient_Address2 as Address2',
                        'mssql_patients.Patient_City as City',
                        'mssql_patients.Patient_State as State',
                        'mssql_patients.Patient_Zip as Zip',
                    );
                }
                $patients->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $patients->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $patients->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $patients->leftJoin("users", function ($query) {
                    $query->on("users.id", "=", "intake.ic_id");
                });
                if(LD::checkFeatureFlag('client-new-changes')){
                    $patients->where('mssql_patients.IsLatestAdmit', 1);
                }
                if(!LD::checkFeatureFlag('hotfix-intake-show-all-page')) {
                    $patients->where(function ($query) {
                        $query->whereNotNull('intake.intake_bucket');
                        $query->where('intake.intake_bucket','<>','');
                    });
                }
                $patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
                mssql_patients.Patient_LastName) Not Like ?',['%test%']);

                $bindings = $patients->getBindings();
                $bucket = DB::table(DB::raw("({$patients->toSql()}) as combined_table"))
                            ->setBindings($bindings);

            if (!empty($request['search']['value'])) {
                $searchText = $request['search']['value'];
                $bucket->where(function ($query) use ($searchText){
                    $query->where('patient_name', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('FacilityName', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('IntakeStatus', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('intake_pending_reason', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('LOBName', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('CF_Specialized', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('CF_TerminalPayer', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('AuthorizedWeeklyHours', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('AuthorizedWeeklyVisits', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('BD_Rep', 'LIKE', '%'. $searchText .'%')
                    ->orWhere('intakeCordinator', 'LIKE', '%'. $searchText .'%');
                });
            }
        }
        if ($type == 'intake_schedule') {
                $patients = DB::table("intake")
                    ->select(
                        DB::raw('\'intake\' as type'),
                        'mssql_facilities.id as aoc_facility_id',
                        'lob.LOBID as LOBID',
                        'mssql_facilities.region as region',
                        'intake.ic_id as ic_id',
                        'mssql_patients.payer_group as payer_group',
                        'intake.intake_pending_reason as intake_pending_reason',
                        'intake.intake_readytostart_reason as intake_readytostart_reason',
                        'mssql_patients.BD_Rep as BD_Rep',
                        'mssql_patients.CF_Skills as CF_Skills',
                        'intake.intake_nonadmit_reason as intake_nonadmit_reason',
                        'intake.id',
						'mssql_patients.id as pid',
                        DB::raw("users.name AS intakeCordinator"),
                        'mssql_facilities.FacilityName',
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                        'lob.LOBName',
                        DB::raw(' IF((mssql_patients.Payer is NULL OR mssql_patients.Payer = \'\'),
                        mssql_patients.RespitePayer, mssql_patients.Payer) as Payer'),
                        DB::raw($intakeService->getPatientHoursFieldCaseSql('lob.LOBID') . ' AS AuthorizedWeeklyHours'),
                        DB::raw('IF(
                            intake.admission_visit_date IS NULL or
                            intake.admission_visit_date = \'\' or
                            intake.admission_visit_date = \'0000-00-00 00:00:00\'
                            , mssql_patients.ActualIntakeDate,
                                intake.admission_visit_date
                            )
                        as IntakeDate'),
                        DB::raw('IF(
                            (
                                intake.CF_GoalSOCDate IS NULL or
                                intake.CF_GoalSOCDate = \'\' or
                                intake.CF_GoalSOCDate = \'0000-00-00 00:00:00\'), \'\',
                                intake.CF_GoalSOCDate
                            )
                        as CF_GoalSOCDate'),
                        DB::raw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
                                    where mssql_patients.id = notes.entity_id and entity='client' order by created_at desc limit 1) AS notes"),
                        DB::raw('IF(
                            (
                                intake.intake_followup IS NULL or
                                intake.intake_followup = \'\' or
                                intake.intake_followup = \'0000-00-00 00:00:00\'), \'\',
                                intake.intake_followup
                            )
                        as intake_followup'),
                        //DB::raw("mssql_providers.fullName as soc_clinician"),
                        (((LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table'))) ?
                            DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                                    left join mssql_providers p on sm.ProviderID = p.ProviderID
                                    where sm.CGTaskID  = intake.intake_admission_visit order by sm.ScheduleDate desc limit 1),
                                    mssql_providers.fullName, '') as soc_clinician") :
                            DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                                    left join mssql_providers p on sm.ProviderID = p.ProviderID
                                    where sm.CGTaskID  = mssql_patients.intake_admission_visit order by sm.ScheduleDate desc limit 1),
                                    mssql_providers.fullName, '') as soc_clinician")),
                    );
                //Nik joining intake here so that we can get intake.admission_visit_scheduled because if it's scheduled the record should
                //      show in intake_schedule bucket but it's not because the admission visit hasn't been synced from snowflake yet
                //      I know this could return multiple records but if they are active I'm not sure it shouldn't.  But there shouldnt be more than one
                //      active and we need to make sure we account for that in the join on clauses.  I just don't know what columns that would be
                $patients->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $patients->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $patients->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $patients->leftJoin("users", function ($query) {
                    $query->on("users.id", "=", "intake.ic_id");
                });
                $patients->leftJoin("mssql_providers", function ($query) {
                    $query->on("mssql_providers.ProviderID", "=", "mssql_patients.SOCClinicianID");
                });

                $patients->where(function ($query) {
                    // non-theray
                    $query->where(function ($query) {
                        $query->whereNotIn('lob.LOBID', [
                            LOB::PT->value,
                            LOB::OT->value,
                            LOB::ST->value,
                            LOB::COLORADO->value,
                        ]);
                        $query->where(function ($query) {
                            $query->where(function ($query) {
                                $query->where('mssql_patients.IntakeDate', '>', self::OLD_INTAKE_HISTORICAL_DATE);
                                $query->whereRaw('ifnull(intake.admission_visit_scheduled, 0) = ' . AdmissionVisitSchedule::NOT_SCHEDULED->value);
                            });
                            $query->orWhere(function ($query) {
                                $query->where('intake.admission_visit_date', '>', self::OLD_INTAKE_HISTORICAL_DATE);
                                $query->Where('intake.admission_visit_scheduled', AdmissionVisitSchedule::SCHEDULED->value);
                            });
                        });
                        if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                            $query->whereRaw(
                                "((
                                    ifnull(mssql_patients.ActualIntakeDate,'0000-00-00') <> '0000-00-00' and
                                    mssql_patients.Patient_CurrentStatusID = 4
                                ) OR (
                                    intake.admission_visit_scheduled = 1
                                    and mssql_patients.Patient_CurrentStatusID <> 1
                                    and mssql_patients.IntakeID IS NOT NULL
                                    and IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                                where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit
                                                order by ScheduleDate desc limit 1),-1) not in (1,2,3)
                                ) OR (
                                    mssql_patients.Patient_CurrentStatusID = 7
                                        and IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                                where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit
                                                order by ScheduleDate desc limit 1),-1) in (4,5)
                                ))
                                "
                            );
                        } else {
                            $query->whereRaw(
                                "((
                                    ifnull(mssql_patients.ActualIntakeDate,'0000-00-00') <> '0000-00-00' and
                                    mssql_patients.Patient_CurrentStatusID = 4
                                ) OR (
                                    intake.admission_visit_scheduled = 1 and mssql_patients.Patient_CurrentStatusID <> 1 and mssql_patients.IntakeID IS NOT NULL
                                ) OR (
                                    mssql_patients.Patient_CurrentStatusID = 7
                                        and IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                                where mssql_schedulemaster.CGTaskID = mssql_patients.intake_admission_visit
                                                order by ScheduleDate desc limit 1),-1) in (4,5)
                                ))
                                "
                            );
                        }
                    });

                    // theray
                    $query->orWhere(function ($query) {
                        $query->whereIn('lob.LOBID', [
                            LOB::PT->value,
                            LOB::OT->value,
                            LOB::ST->value,
                            LOB::COLORADO->value,
                        ]);
                        $query->where(function ($query) {
                            $query->where(function ($query) {
                                $query->where('mssql_patients.IntakeDate', '>', self::OLD_INTAKE_HISTORICAL_DATE);
                                $query->whereRaw('ifnull(intake.admission_visit_scheduled, 0) = ' . AdmissionVisitSchedule::NOT_SCHEDULED->value);
                            });
                            $query->orWhere(function ($query) {
                                $query->where('intake.admission_visit_date', '>', self::OLD_INTAKE_HISTORICAL_DATE);
                                $query->Where('intake.admission_visit_scheduled', AdmissionVisitSchedule::SCHEDULED->value);
                            });
                        });
                        if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                            $query->whereRaw(
                                "((
                                    mssql_patients.IntakeStatus = 'Scheduled' and
                                    mssql_patients.Patient_CurrentStatusID = 4
                                ) OR (
                                    intake.admission_visit_scheduled = 1 and mssql_patients.Patient_CurrentStatusID <> 1
                                    and mssql_patients.IntakeID IS NOT NULL
                                    and IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                                where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit
                                                order by ScheduleDate desc limit 1),-1) not in (1,2,3)
                                ) OR (
                                    mssql_patients.Patient_CurrentStatusID = 7
                                        and IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                                where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit
                                                order by ScheduleDate desc limit 1),-1) in (4,5)
                                ))"
                            );
                        } else {
                            $query->whereRaw(
                                "((
                                    mssql_patients.IntakeStatus = 'Scheduled' and
                                    mssql_patients.Patient_CurrentStatusID = 4
                                ) OR (
                                    intake.admission_visit_scheduled = 1 and mssql_patients.Patient_CurrentStatusID <> 1 and mssql_patients.IntakeID IS NOT NULL
                                ) OR (
                                    mssql_patients.Patient_CurrentStatusID = 7
                                        and IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                                where mssql_schedulemaster.CGTaskID = mssql_patients.intake_admission_visit
                                                order by ScheduleDate desc limit 1),-1) in (4,5)
                                ))"
                            );
                        }
                    });

					//new referral
					if(LD::checkFeatureFlag('intake-new-referral-change')) {
						$query->orWhere(function ($query) {
							$query->whereRaw("intake.IntakeStatus = 'Scheduled' and intake.admission_visit_scheduled = 1");
                            $query->whereRaw("IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                                where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit
                                                order by ScheduleDate desc limit 1),5) in (4,5)");
							$query->whereRaw('mssql_patients.Patient_CurrentStatusID not in (2,5)');
                            $query->whereRaw('mssql_patients.LOBID <> IFNULL(intake.LOBID, 0)');
						});
					}

                    //Active clients with a planned admission visit
                    if (LD::checkFeatureFlag('intake-scheduled-active-clients')) {
                        $query->orWhere(function ($query) {
                            $query->where('mssql_patients.Patient_CurrentStatusID', MssqlPatientStatus::ACTIVE->value);
                            $query->whereExists(function ($sub) {
                                $sub->select(DB::raw(1))
                                    ->from('mssql_schedulemaster as sm')
                                    ->whereColumn('sm.PatientID', 'mssql_patients.PatientID')
                                    ->whereIn('sm.ScheduleStatusID', [
                                        MssqlScheduleMasterStatus::MISSED_VISIT->value,
                                        MssqlScheduleMasterStatus::PLANNED->value,
                                    ])
                                    ->where('sm.is_deleted', 0)
                                    ->whereRaw('sm.ScheduleDate >= CURDATE()')
                                    ->whereIn('sm.ServiceName', AdmissionServiceNameEnum::bucketFilterValues());
                            });
                        });
                    }
                });

                if(LD::checkFeatureFlag('client-new-changes')){
                    $patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
                }
                $patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
                mssql_patients.Patient_LastName) Not Like ?',['%test%']);

                $patients->where('mssql_patients.is_deleted', '!=', '1');

                if(LD::checkFeatureFlag('exclude-intake-records-which-are-completed')){
                    $patients->where('intake.intake_completed', '<>', 1);
                }
                if (LD::checkFeatureFlag('fixed-non-admit-bucket')) {
                    $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Non Admit'");
                    $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Approve Non Admit'");
                }

                $patients->whereNotIn('mssql_patients.Patient_CurrentStatusID', [
                    MssqlPatientStatus::INACTIVE->value,
                    MssqlPatientStatus::NON_ADMIT->value,
                    MssqlPatientStatus::DISCHARGED->value,
                ]);

                $bindings = $patients->getBindings();
                $bucket = DB::table(DB::raw("({$patients->toSql()}) as combined_table"))
                            ->setBindings($bindings);
        }
        if ($type == 'intake_pending_soc') {
                $patients = DB::table("intake")
                    ->select(
                        DB::raw('\'intake\' as type'),
                        'mssql_facilities.id as aoc_facility_id',
                        'lob.LOBID as LOBID',
                        'mssql_facilities.region as region',
                        'intake.ic_id as ic_id',
                        'mssql_patients.payer_group as payer_group',
                        'intake.intake_pending_reason as intake_pending_reason',
                        'intake.intake_readytostart_reason as intake_readytostart_reason',
                        'mssql_patients.BD_Rep as BD_Rep',
                        'mssql_patients.CF_Skills as CF_Skills',
                        'intake.intake_nonadmit_reason as intake_nonadmit_reason',
                        'intake.id',
						'mssql_patients.id as pid',
                        DB::raw("users.name AS intakeCordinator"),
                        'mssql_facilities.FacilityName',
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                        'lob.LOBName',
                        DB::raw(' IF((mssql_patients.Payer is NULL OR mssql_patients.Payer = \'\'),
                        mssql_patients.RespitePayer, mssql_patients.Payer) as Payer'),
                        DB::raw($intakeService->getPatientHoursFieldCaseSql('lob.LOBID') . ' AS AuthorizedWeeklyHours'),
                        DB::raw('IF(
                            intake.admission_visit_date IS NULL or
                            intake.admission_visit_date = \'\' or
                            intake.admission_visit_date = \'0000-00-00 00:00:00\'
                            , mssql_patients.ActualIntakeDate,
                                intake.admission_visit_date
                            )
                        as admission_visit_date'),
                        DB::raw('IF(
                            (
                                intake.CF_GoalSOCDate IS NULL or
                                intake.CF_GoalSOCDate = \'\' or
                                intake.CF_GoalSOCDate = \'0000-00-00 00:00:00\'), \'\',
                                intake.CF_GoalSOCDate
                            )
                        as CF_GoalSOCDate'),
                        'intake.intake_readytostart',
                        DB::raw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
                                    where mssql_patients.id = notes.entity_id and entity='client' order by created_at desc limit 1) AS notes"),
                        DB::raw('IF(
                            (
                                intake.intake_followup IS NULL or
                                intake.intake_followup = \'\' or
                                intake.intake_followup = \'0000-00-00 00:00:00\'), \'\',
                                intake.intake_followup
                            )
                        as intake_followup'),
                        ((LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) ?
                                    DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                                        left join mssql_providers p on sm.ProviderID = p.ProviderID
                                        where sm.CGTaskID  = intake.intake_admission_visit order by sm.ScheduleDate desc limit 1),
                                        mssql_providers.fullName,'') as soc_clinician") :
                                    DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                                        left join mssql_providers p on sm.ProviderID = p.ProviderID
                                        where sm.CGTaskID  = mssql_patients.intake_admission_visit order by sm.ScheduleDate desc limit 1),
                                        mssql_providers.fullName,'') as soc_clinician")),
                    );
                $patients->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
				$patients->leftJoin("mssql_schedulemaster", function ($query) {
                    if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                        $query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_first_billable_visit");
                    } else {
                        $query->on("mssql_schedulemaster.CGTaskID", "=", "mssql_patients.intake_first_billable_visit");
                    }
                });
                $patients->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $patients->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $patients->leftJoin("users", function ($query) {
                    $query->on("users.id", "=", "intake.ic_id");
                });
                $patients->leftJoin("mssql_providers", function ($query) {
                    $query->on("mssql_providers.ProviderID", "=", "mssql_patients.SOCClinicianID");
                });
                $patients->where(function ($query) {
                    // non-theray
                    $query->where(function ($query) {
                        $query->whereNotIn('lob.LOBID', ['1003', '1004', '1006']);
                        $query->whereIn('mssql_patients.Patient_CurrentStatusID', [1,6,8]);
						$query->where(function ($query) {
                            $query->where('mssql_patients.LOBID', '<>', 1023);
							$query->orWhere(DB::raw('IFNULL(intake.LOBID, 1023)'), '<>', 1023);
                        });
                        $query->where(function ($query) {
                            $query->whereDate('intake.CF_GoalSOCDate', '>', Carbon::now());
							$query->orWhere(DB::raw("IFNULL(intake.intake_readytostart,0)"), "0");
                        });
                    });
                });
				$patients->whereRaw('IFNULL(mssql_schedulemaster.ScheduleStatusID,-1) NOT IN (1,2,3) and IFNULL(mssql_schedulemaster.is_deleted, 0) = 0');
                if(LD::checkFeatureFlag('client-new-changes')){
                    $patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
                }

            $patients->whereRaw("(IFNULL(intake.intake_admission_visit, 0) = 0 OR
                                    IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                        where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit
                                        order by ScheduleDate desc limit 1),0) in (
                                        " . MssqlPatientStatus::ACTIVE->value . ",
                                        " . MssqlPatientStatus::INACTIVE->value . ",
                                        " . MssqlPatientStatus::NON_ADMIT->value . "
                                        ))");

                $patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
                mssql_patients.Patient_LastName) Not Like ?',['%test%']);

                if(LD::checkFeatureFlag('exclude-intake-records-which-are-completed')){
                    $patients->where('intake.intake_completed', '<>', 1);
                }
                if (LD::checkFeatureFlag('fixed-non-admit-bucket')) {
                    $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Non Admit'");
                    $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Approve Non Admit'");
                }

                $bindings = $patients->getBindings();
                $bucket = DB::table(DB::raw("({$patients->toSql()}) as combined_table"))
                            ->setBindings($bindings);
        }
        if ($type == 'intake_client_starts') {
                $patients = DB::table("intake")
                    ->select(
                        DB::raw('\'intake\' as type'),
                        'mssql_facilities.id as aoc_facility_id',
                        'lob.LOBID as LOBID',
                        'mssql_facilities.region as region',
                        'intake.ic_id as ic_id',
                        'mssql_patients.payer_group as payer_group',
                        'intake.intake_pending_reason as intake_pending_reason',
                        'intake.intake_readytostart_reason as intake_readytostart_reason',
                        'mssql_patients.BD_Rep as BD_Rep',
                        'mssql_patients.CF_Skills as CF_Skills',
                        'intake.intake_nonadmit_reason as intake_nonadmit_reason',
                        'intake.id',
						'mssql_patients.id as pid',
                        DB::raw("users.name AS intakeCordinator"),
                        'mssql_facilities.FacilityName',
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName) AS patient_name"),
                        'lob.LOBName',
                        DB::raw(' IF((mssql_patients.Payer is NULL OR mssql_patients.Payer = \'\'),
                        mssql_patients.RespitePayer, mssql_patients.Payer) as Payer'),
                        DB::raw($intakeService->getPatientHoursFieldCaseSql('lob.LOBID') . ' AS AuthorizedWeeklyHours'),
                        DB::raw('IF(
                            (
                                intake.CF_GoalSOCDate IS NULL or
                                intake.CF_GoalSOCDate = \'\' or
                                intake.CF_GoalSOCDate = \'0000-00-00 00:00:00\'), \'\',
                                intake.CF_GoalSOCDate
                            )
                        as CF_GoalSOCDate'),
                        DB::raw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
                                    where mssql_patients.id = notes.entity_id and entity='client' order by created_at desc limit 1) AS notes"),
                        ((LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) ?
                        DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                            left join mssql_providers p on sm.ProviderID = p.ProviderID
                            where sm.CGTaskID  = intake.intake_admission_visit order by sm.ScheduleDate desc limit 1),
                            mssql_providers.fullName,'') as soc_clinician") :
                        DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                            left join mssql_providers p on sm.ProviderID = p.ProviderID
                            where sm.CGTaskID  = mssql_patients.intake_admission_visit order by sm.ScheduleDate desc limit 1),
                            mssql_providers.fullName,'') as soc_clinician")),
                    );
                $patients->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $patients->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $patients->leftJoin("mssql_schedulemaster", function ($query) {
                    if (LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) {
                        $query->on("mssql_schedulemaster.CGTaskID", "=", "intake.intake_first_billable_visit");
                    } else {
                        $query->on("mssql_schedulemaster.CGTaskID", "=", "mssql_patients.intake_first_billable_visit");
                    }
                });
                $patients->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $patients->leftJoin("users", function ($query) {
                    $query->on("users.id", "=", "intake.ic_id");
                });
                $patients->leftJoin("mssql_providers", function ($query) {
                    $query->on("mssql_providers.ProviderID", "=", "mssql_patients.SOCClinicianID");
                });


                $patients->where(function ($query) {
                    // non-theray
                    $query->where(function ($query) {
                        $query->whereNotIn('lob.LOBID', ['1003', '1004', '1006']);
                        //$query->where('mssql_patients.Patient_CurrentStatusID', 1);
                        $query->whereIn('mssql_patients.Patient_CurrentStatusID', [1,6,8]);
                        $query->whereRaw("ifnull(intake.CF_GoalSOCDate,'0000-00-00 00:00:00') <= NOW()");
                        $query->where('mssql_patients.IntakeDate', '>', '2024-01-01');
                        /*$query->whereRaw('IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                            where mssql_schedulemaster.CGTaskID = mssql_patients.intake_first_billable_visit
                                            order by ScheduleDate desc limit 1),-1) NOT IN (1,2,3)');*/
                        $query->whereRaw('IFNULL(mssql_schedulemaster.ScheduleStatusID,-1) NOT IN (1,2,3) and IFNULL(mssql_schedulemaster.is_deleted, 0) = 0');
                        //$query->whereNull('mssql_patients.intake_first_billable_visit');
                    });

                    if(LD::checkFeatureFlag('TICKET-6195_ready_to_start_fix')) {
                        $query->where(DB::raw("IFNULL(intake.intake_readytostart,0)"),"1");
                    }
                });



                if(LD::checkFeatureFlag('client-new-changes')){
                    $patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
                }

                if(LD::checkFeatureFlag('intake-new-referral-change')) {
                    $patients->whereRaw("IFNULL((select ScheduleStatusID from mssql_schedulemaster
                                        where mssql_schedulemaster.CGTaskID = intake.intake_admission_visit
                                        order by ScheduleDate desc limit 1),0) in (1,2,3)");
                }

                $patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
                mssql_patients.Patient_LastName) Not Like ?',['%test%']);

                if(LD::checkFeatureFlag('exclude-intake-records-which-are-completed')){
                    $patients->where('intake.intake_completed', '<>', 1);
                }
                if (LD::checkFeatureFlag('fixed-non-admit-bucket')) {
                    $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Non Admit'");
                    $patients->whereRaw("ifnull(intake.intake_outcome,'') <> 'Approve Non Admit'");
                }

                $bindings = $patients->getBindings();
                $bucket = DB::table(DB::raw("({$patients->toSql()}) as combined_table"))
                            ->setBindings($bindings);
        }
        if ($type == 'intake_pending_non_admit') {

            $isNewCriteria = LD::checkFeatureFlag('intake-change-criteria-pending-non-admit');

                $patients = DB::table("intake")
                    ->select(
                        DB::raw('\'intake\' as type'),
                        'mssql_facilities.id as aoc_facility_id',
                        'lob.LOBID as LOBID',
                        'mssql_facilities.region as region',
                        'intake.ic_id as ic_id',
                        'mssql_patients.payer_group as payer_group',
                        'intake.intake_pending_reason as intake_pending_reason',
                        'intake.intake_readytostart_reason as intake_readytostart_reason',
                        'mssql_patients.BD_Rep as BD_Rep',
                        'mssql_patients.CF_Skills as CF_Skills',
                        'intake.intake_nonadmit_reason as intake_nonadmit_reason',
                        'intake.id',
						'mssql_patients.id as pid',
                        DB::raw("users.name AS intakeCordinator"),
                        'mssql_facilities.FacilityName',
                        DB::raw("CONCAT(mssql_patients.Patient_FirstName,' ',mssql_patients.Patient_LastName)
                        AS patient_name"),
                        'lob.LOBName',
                        DB::raw(' IF((mssql_patients.Payer is NULL OR mssql_patients.Payer = \'\'),
                        mssql_patients.RespitePayer, mssql_patients.Payer) as Payer'),
                        DB::raw($intakeService->getPatientHoursFieldCaseSql('lob.LOBID') . ' AS AuthorizedWeeklyHours'),
                        DB::raw("(select CONCAT(notes.created_at,'#',notes.notes,'#',notes.created_by) from notes
                        where mssql_patients.id = notes.entity_id and entity='client'
                        order by created_at desc limit 1) AS notes"),
                        DB::raw("
                            CASE
                                WHEN intake.IntakeStatus = 'Pending Non Admit' THEN 'Pending Approval'
                                WHEN intake.IntakeStatus = 'Non Admit Approved' THEN 'Non Admit Approved'
                            END AS non_admit_status
                        "),
                        'intake.intake_approved_date',
                        'intake.IntakeStatus',
                        'mssql_patientstatus.PatientStatus',
                        ((LD::checkFeatureFlag('move-first-billable-shift-fields-to-intake-table')) ?
                        DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                            left join mssql_providers p on sm.ProviderID = p.ProviderID
                            where sm.CGTaskID  = intake.intake_admission_visit order by sm.ScheduleDate desc limit 1), '') as soc_clinician") :
                        DB::raw("COALESCE((select p.fullName from mssql_schedulemaster sm
                            left join mssql_providers p on sm.ProviderID = p.ProviderID
                            where sm.CGTaskID  = mssql_patients.intake_admission_visit order by sm.ScheduleDate desc limit 1), '') as soc_clinician")),
                    );
                $patients->leftJoin("mssql_patients", function ($query) {
                    $query->on("mssql_patients.id", "=", "intake.aoc_patient_id");
                });
                $patients->leftJoin("mssql_facilities", function ($query) {
                    $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                });
                $patients->leftJoin("lob", function ($query) {
                    $query->on("lob.LOBID", "=", "intake.LOBID");
                });
                $patients->leftJoin("users", function ($query) {
                    $query->on("users.id", "=", "intake.ic_id");
                });
                $patients->leftJoin("mssql_patientstatus", function ($query) {
                    $query->on("mssql_patientstatus.PatientStatusID", "=", "mssql_patients.Patient_CurrentStatusID");
                });

                if ($isNewCriteria) {
                    $patients->where(function ($query) {
                        $query->where(function ($sub) {
                            // branch 1: Non Admit was requested
                            if (LD::checkFeatureFlag('red-highlights-intake-all-buckets')) {
                                $sub->whereIn('intake.intake_outcome', ['Non Admit', 'Approve Non Admit']);
                            } else {
                                $sub->where('intake.intake_outcome', 'Non Admit');
                            }

                            $sub->whereRaw("IFNULL(intake.IntakeStatus, '') <> 'Non Admit Approved'");
                        })->orWhere(function ($sub) {
                            // branch 2: Patient Status is Non Admit
                            $sub->where('mssql_patients.Patient_CurrentStatusID', 3);
                            $sub->whereRaw("IFNULL(intake.intake_nonadmit_reason, '') = ''");
                            $sub->whereRaw("IFNULL(intake.IntakeStatus, '') <> 'Non Admit Approved'");
                        });
                    });
                } else {
                    if (LD::checkFeatureFlag('red-highlights-intake-all-buckets')) {
                        $patients->whereIn('intake.intake_outcome', ['Non Admit', 'Approve Non Admit']);
                    } else {
                        $patients->where('intake.intake_outcome', 'Non Admit');
                    }

                    $patients->whereNotIn("mssql_patients.Patient_CurrentStatusID", [2, 3, 5]);
                    $patients->whereRaw('IFNULL(intake.intake_nonadmit_reason,\'\') <> \'\'');
                }

                $patients->where('mssql_patients.is_deleted', '=', 0);
                //$patients->where('mssql_patients.is_intake', '1');
                if(LD::checkFeatureFlag('client-new-changes')){
                    $patients->whereRaw('(mssql_patients.IsLatestAdmit = 1 OR IntakeID IS NULL)');
                }
                $patients->whereRaw('CONCAT(mssql_patients.Patient_FirstName,\' \',
                mssql_patients.Patient_LastName) Not Like ?',['%test%']);

                $bindings = $patients->getBindings();
                $bucket = DB::table(DB::raw("({$patients->toSql()}) as combined_table"))
                            ->setBindings($bindings);
        }
        if (
            $type == 'intake_prospective_clients' ||
            $type == 'intake_schedule' ||
            $type == 'intake_pending_soc' ||
            $type == 'intake_client_starts' ||
            $type == 'intake_pending_non_admit' ||
            $type == 'showall'
        ) {
            $this->applyFilterForUnion($request, $bucket);
        } else {
            $this->applyFilter($request, $bucket);
        }
        if ($showCount) {
            return '(' . $bucket->get()->count() . ')';
        }
        return $bucket;
    }

    public function getPatientAssignedTo($data) {
        // Fetch relevant data from MssqlPatients based on $data->id
        $patientData = MssqlPatients::select('mssql_facilities.id as FacilityID')
                                    ->leftJoin("mssql_facilities", function ($query) {
                                        $query->on("mssql_facilities.id", "=", "mssql_patients.aoc_facility_id");
                                    })
                                    ->where('mssql_patients.id', $data->pid)
                                    ->first();
        // Default names to '-' if $patientData is empty
        $names = '-';
        if (!empty($patientData)) {
            if (!empty($patientData->FacilityID)) {
                // Fetch user ids with 'State VP' role for the given FacilityID
                $users = H::getHelper("UserPermissionResolver")
                            ->getUserIdByFacilityAndRole($patientData->FacilityID, 'Regional Director of Operations');
                if (!empty($users)) {
                    // Fetch names of users with found user ids
                    $names = User::whereIn('id', $users)->pluck('name')->toArray();
                    if (!empty($names)) {
                        $names = implode(', ', $names);
                    }
                } else {
                    // If no 'State VP' users found, try 'Regional VP' users
                    $usersStateVp = H::getHelper("UserPermissionResolver")
                                 ->getUserIdByFacilityAndRole($patientData->FacilityID, 'State VP');
                    if (!empty($usersStateVp)) {
                        // Fetch names of users with found user ids
                        $names = User::whereIn('id', $usersStateVp)->pluck('name')->toArray();
                        if (!empty($names)) {
                            $names = implode(', ', $names);
                        } else {
                            $names = '';
                        }
                    } else {
						$usersRvp = H::getHelper("UserPermissionResolver")
                                 ->getUserIdByFacilityAndRole($patientData->FacilityID, 'Regional VP');
						if (!empty($usersRvp)) {
							// Fetch names of users with found user ids
							$names = User::whereIn('id', $usersRvp)->pluck('name')->toArray();
							if (!empty($names)) {
								$names = implode(', ', $names);
							} else {
								$names = '';
							}
						}
					}
                }
            } else {
                // If branchid is empty, default to '-'
                $names = '-';
            }
        }
		H::ExcelLog("facility name",'getPatientAssignedTo.log');
        H::ExcelLog(print_r($names,true),'getPatientAssignedTo.log');
        return $names;
    }

    public function getPlannedStartByPatientId($id){
        $isNonth = MssqlPatients::where('PatientID',$id)
        ->whereNotIn('LOBID', ['1003', '1004', '1006'])->exists();
        if($isNonth){
            $data = DB::table('mssql_schedulemaster')
                    ->select('PlannedStart')
                    ->where('mssql_schedulemaster.PatientID', $id)
                    ->where('mssql_schedulemaster.ScheduleStatusID', 5)
                    ->whereIN('mssql_schedulemaster.ServiceName',
                    ['CNA Admission (Add LOB)',
                            'CNA Admission Field (Add LOB)',
                            'IHSS Admission',
                            'PDN Admission (Add LOB)',
                            'PDN Admission (Billable)',
                            'PDN Admission Field (Add LOB)',
                            'RN ADMISSION',
                            'RN Admission Field',
                            'RN Admission OASIS',
                            'RN Admission OASIS Field',
                            'SV Admission',
                            'SV Admission (Add LOB)',
                            'SV Admission (NONBILL)',
                            'SV Admission (OFFICE NONBILL)',
                            'SV Admission (OFFICE)']
                    )
                    ->first();
        }else{
            $data = DB::table('mssql_schedulemaster')
            ->select('PlannedStart')
            ->where('mssql_schedulemaster.PatientID', $id)
            ->where('mssql_schedulemaster.ScheduleStatusID', 5)
            ->whereIN('mssql_schedulemaster.ServiceName',
            ['Physical Therapy Initial Eval - High Complexity',
								'Physical Therapy Initial Eval - Low Complexity',
								'Physical Therapy Initial Eval - Mod Complexity',
								'Physical Therapy Initial Evaluation',
								'Tele-Physical Therapy Initial Evaluation',
								'PT Initial Evaluation - Acute',
								'PT Initial Evaluation - Acute EI',
								'PT Initial Evaluation - Long Term',
								'PT Initial Evaluation - Long Term EI',
								'PT SOC Initial Evaluation - Acute',
								'PT SOC Initial Evaluation - Acute - OASIS',
								'PT SOC Initial Evaluation - Acute EI',
								'PT SOC Initial Evaluation - Long Term',
								'PT SOC Initial Evaluation - Long Term - OASIS',
								'PT SOC Initial Evaluation - Long Term EI',
								'Occupational Therapy Initial Eval - Low Complexity',
								'Occupational Therapy Initial Eval - Mod Complexity',
								'Occupational Therapy Initial Eval High Complexity',
								'Occupational Therapy Initial Evaluation',
								'Tele-Occupational Therapy Initial Evaluation',
								'OT Initial Evaluation - Acute',
								'OT Initial Evaluation - Acute EI',
								'OT Initial Evaluation - Long Term',
								'OT Initial Evaluation - Long Term EI',
								'OT SOC Initial Evaluation - Acute',
								'OT SOC Initial Evaluation - Acute - OASIS',
								'OT SOC Initial Evaluation - Acute EI',
								'OT SOC Initial Evaluation - Long Term',
								'OT SOC Initial Evaluation - Long Term - OASIS',
								'OT SOC Initial Evaluation - Long Term EI',
								'Behavioral & Qual Analys of Voice & Resonance',
								'Eval of Oral & Pharyngeal Swallow Function',
								'Eval of Speech Sound Production w/Language Comp & Expr',
								'Evaluation of Speech Fluency',
								'Evaluation of Speech Sound Production',
								'Tele-Behavioral & Qual Analys of Voice & Resonance',
								'Tele-Eval of Oral & Pharyngeal Swallow Function',
								'Tele-Eval of Speech Sound Production w/Language Comp & Expr',
								'Tele-Evaluation of Speech Fluency',
								'Tele-Evaluation of Speech Sound Production',
								'ST Initial Evaluation - Acute',
								'ST Initial Evaluation - Acute EI',
								'ST Initial Evaluation - Long Term',
								'ST Initial Evaluation - Long Term EI',
								'ST SOC Initial Evaluation - Acute',
								'ST SOC Initial Evaluation - Acute - OASIS',
								'ST SOC Initial Evaluation - Acute EI',
								'ST SOC Initial Evaluation - Long Term',
								'ST SOC Initial Evaluation - Long Term - OASIS',
								'ST SOC Initial Evaluation - Long Term EI',
								'Eval of Speech Sound Prod w/Language Comp & Expr',
								'Behavioral & Qual Anlys of Voice & Resonance',
								'Tele-Behavioral & Qual Anlys of Voice & Resonance',
								'Tele-Eval of Speech Sound Prod w/Language Comp &']
            )
            ->first();
        }
        $date = '';
        if($data){
            $date = $data->PlannedStart;
        }
        return $date;
    }
}
