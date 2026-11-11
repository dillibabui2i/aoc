<?php

use App\Enums\FeatureFlagsEnum;
use App\Enums\JobViteRecruiterJobType;
use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Http\Request;
use App\Models\JobCategories;

$request = new Request;

//$request['filters'][0]['name'] = 'default';
//$request['filters'][0]['value'] = 'on';

$request->merge(['filters' => [["name" => "default", "value" => "on"]]]);

//var_dump($request->all()); exit;
$loggedInUser = H::getLoggedInUser();
$userRole = [];
$isRecruiterAdmin = H::getHelper("UserPermissionResolver")->isRoleHasPermissionNew("is_recruiting_admin");

$showallPermission = H::canPermissions(['dashboard.allactivitydata']);
$class = "col-md-12";
if ($isRecruiterAdmin || $showallPermission) {
    $class = "col-md-10";
}

?>
@extends('layouts.myaccount')

@section('title', 'Dashboard')

@section('content')

    <?php
    $branchObject = H::getHelper("ConstantHelper")->getFacilities();

    $ocBranchObject = H::getHelper("ConstantHelper")->getBranches();

    $ocBranch = [];
    foreach ($ocBranchObject as $br) {
        $ocBranch[] = ["value" => $br->id, 'text' => $br->branch];
    }

// $recruiters = H::getHelper("ConstantHelper")->getRecruiters();
    $recruiters = H::getHelper("ConstantHelper")->getRecruitersList(false);
    $state = H::getHelper("ConstantHelper")->getDashboardState();
    $branch = [];
    foreach ($branchObject as $br) {
        $branches[] = ["value" => $br->id, 'text' => $br->FacilityName];
    }

    $recruitersAdminList = H::getAllRecruitersAdmin();
    $recruitersAdmin = [];
    foreach ($recruitersAdminList as $id => $name) {
        $recruitersAdmin[] = ["value" => $id, 'text' => $name];
    }
    $serviceneeded = [];
    foreach (config('constant')['serviceNeededForNTH'] as $v) {
        $serviceneeded[] = ['value' => $v, 'text' => $v];
    }
    $filters = [];
    $filters = [
        [
            'label' => 'Me Mode',
            'name' => 'default',
            'type' => 'checkbox',
            'value' => true
        ],
        [
            'label' => 'State',
            'name' => 'state',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
    ];
    $filters[] = [
        'label' => 'Region',
        'name' => 'region',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
        'disabled' => true
    ];
    $filters[] = [
        'label' => 'Branch',
        'name' => 'aoc_facility_id',
        'type' => 'multiselect',
        'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
    ];
    $filters[] = [
        'label' => 'Recruiter',
        'name' => 'Recruiter',
        'type' => 'multiselect',
        'source' => $recruiters
    ];

    $filters[] = [
        'label' => 'Recruiting Admin',
        'name' => 'user_id',
        'type' => 'multiselect',
        'source' => $recruitersAdmin
    ];

    $filters[] = [
        'label' => 'Payer',
        'name' => 'Payer',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayers()
    ];
    $filters[] = [
        'label' => 'Special Payer Group',
        'name' => 'payer_group',
        'type' => 'multiselect',
        'source' => H::getHelper('ConstantHelper')->getPayerGroup()
    ];

    $filters[] = [
        'label' => 'Exclude PRN',
        'name' => 'excludeprn',
        'type' => 'checkbox',
        'value' => false
    ];

    $orientationFilter = [];

    $orientationFilter = [
        [
            'label' => 'Me Mode',
            'name' => 'ocdefault',
            'type' => 'checkbox',
            'value' => true
        ],
        [
            'label' => 'State',
            'name' => 'ocstate',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('ocstate')
        ],
        [
            'label' => 'Region',
            'name' => 'ocregion',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label' => 'Branch',
            'name' => 'ipea_location_id',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('ipea_location_id')
        ],
        [
            'label' => 'Orientation Coordinator',
            'name' => 'oc',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('oc')
        ]
    ];

    $clientCareFilter = [
        [
            'label' => 'Me Mode',
            'name' => 'ccdefault',
            'type' => 'checkbox',
            'value' => true
        ],
        [
            'label' => 'State',
            'name' => 'ccstate',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label' => 'Region',
            'name' => 'ccregion',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label' => 'Branch',
            'name' => 'ccbranch',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label' => 'Coordinator',
            'name' => 'clientcare',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
        ]
    ];
    if (LD::checkFeatureFlag('payer-filters-on-exception-bucket')) {
        $clientCareFilter[] = [
            'label' => 'Payer',
            'name' => 'exPayer',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayers()
        ];
        $clientCareFilter[] = [
            'label' => 'Special Payer Group',
            'name' => 'ex_payer_group',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ];
    }

    $intakeFilter = [
        [
            'label' => 'Me Mode',
            'name' => 'intakeexceptiondefault',
            'type' => 'checkbox',
            'value' => true
        ],
        [
            'label' => 'State',
            'name' => 'intakeexceptionstate',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label' => 'Region',
            'name' => 'intakeexceptionregion',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
            'disabled' => true
        ],
        [
            'label' => 'Branch',
            'name' => 'intakeexceptionbranch',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label' => 'Intake Coordinator',
            'name' => 'intakeexceptioncare',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getIntakeCoordinator()
        ],
        [
            'label' => 'Payer',
            'name' => 'intakeexceptionPayer',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayers()
        ],
        [
            'label' => 'Special Payer Group',
            'name' => 'intakeexceptionspecialpayergroup',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayerGroup()
        ]
    ];
    $lobName = H::getHelper('ConstantHelper')->getLob();
    $loboption = [];
    foreach ($lobName as $lob) {
        $loboption[] = ['value' => $lob->LOBID, 'text' => $lob->LOBName];
    }
    if (LD::checkFeatureFlag('add-lob-filter-in-ccc-follow-up-calls')) {
        $weeklyFilter = [
            [
                'label' => 'Me Mode',
                'name' => 'weeklymemode',
                'type' => 'checkbox',
                'value' => true
            ],
            [
                'label' => 'State',
                'name' => 'weeklyccstate',
                'type' => 'multiselect',
                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
            ],
            [
                'label' => 'Region',
                'name' => 'weeklyregion',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
                'disabled' => true
            ],
            [
                'label' => 'Branch',
                'name' => 'weeklyccbranch',
                'type' => 'multiselect',
                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
            ],
            [
                'label' => 'Client Care Coordinator',
                'name' => 'weeklyclientcare',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
            ],
            [
                'label' => 'LOB',
                'name' => 'weeklyLOB',
                'type' => 'multiselect',
                'source' => $loboption
            ],
            [
                'label' => 'Payer',
                'name' => 'weeklypayer',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayers()
            ],
            [
                'label' => 'Payer Group',
                'name' => 'weeklypayergroup',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayerGroup()
            ]
        ];
    } else {
        $weeklyFilter = [
            [
                'label' => 'Me Mode',
                'name' => 'weeklymemode',
                'type' => 'checkbox',
                'value' => true
            ],
            [
                'label' => 'State',
                'name' => 'weeklyccstate',
                'type' => 'multiselect',
                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
            ],
            [
                'label' => 'Region',
                'name' => 'weeklyregion',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
                'disabled' => true
            ],
            [
                'label' => 'Branch',
                'name' => 'weeklyccbranch',
                'type' => 'multiselect',
                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
            ],
            [
                'label' => 'Client Care Coordinator',
                'name' => 'weeklyclientcare',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
            ],
            [
                'label' => 'Payer',
                'name' => 'weeklypayer',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayers()
            ],
            [
                'label' => 'Payer Group',
                'name' => 'weeklypayergroup',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayerGroup()
            ]
        ];
    }

    if (LD::checkFeatureFlag('add-lob-filter-in-ccc-follow-up-calls')) {
        $monthlyFilter = [
            [
                'label' => 'Me Mode',
                'name' => 'monthlymemode',
                'type' => 'checkbox',
                'value' => true
            ],
            [
                'label' => 'State',
                'name' => 'monthlyccstate',
                'type' => 'multiselect',
                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
            ],
            [
                'label' => 'Region',
                'name' => 'monthlyccregion',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
                'disabled' => true
            ],
            [
                'label' => 'Branch',
                'name' => 'monthlyccbranch',
                'type' => 'multiselect',
                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource(
                    'aoc_facility_id',
                    'monthlycall'
                )
            ],
            [
                'label' => 'Client Care Coordinator',
                'name' => 'monthlyclientcare',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
            ],
            [
                'label' => 'LOB',
                'name' => 'monthlyLOB',
                'type' => 'multiselect',
                'source' => $loboption
            ],
            [
                'label' => 'Payer',
                'name' => 'monthlypayer',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayers()
            ],
            [
                'label' => 'Payer Group',
                'name' => 'monthlypayergroup',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayerGroup()
            ]
        ];
    } else {
        $monthlyFilter = [
            [
                'label' => 'Me Mode',
                'name' => 'monthlymemode',
                'type' => 'checkbox',
                'value' => true
            ],
            [
                'label' => 'State',
                'name' => 'monthlyccstate',
                'type' => 'multiselect',
                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
            ],
            [
                'label' => 'Region',
                'name' => 'monthlyccregion',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
                'disabled' => true
            ],
            [
                'label' => 'Branch',
                'name' => 'monthlyccbranch',
                'type' => 'multiselect',
                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource(
                    'aoc_facility_id',
                    'monthlycall'
                )
            ],
            [
                'label' => 'Client Care Coordinator',
                'name' => 'monthlyclientcare',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getClientCareCoordinator()
            ],
            [
                'label' => 'Payer',
                'name' => 'monthlypayer',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayers()
            ],
            [
                'label' => 'Payer Group',
                'name' => 'monthlypayergroup',
                'type' => 'multiselect',
                'source' => H::getHelper('ConstantHelper')->getPayerGroup()
            ]
        ];
    }

    //Job vite filters
    $category = [];
    $categoryList = JobCategories::query()->where('is_enabled', true);

    if (LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_NURSING_FILTER->value)) {
        $categoryList = $categoryList->where('category', 'like', '%nurs%');
    }

    $categoryList = $categoryList->pluck('category')->toArray();

    foreach ($categoryList as $key => $value) {
        $category[] = ['value' => $value, 'text' => $value];
    }
    $dashboardState = config('constant.dashboard_state');
    $jobViteState = [];
    foreach ($dashboardState as $key => $value) {
        $jobViteState[] = ["value" => $value, "text" => $value];
    }
    $jobvitefilters = [
        [
            'label' => 'Me Mode',
            'name' => 'default',
            'type' => 'checkbox',
            'value' => true
        ],
        [
            'label' => 'State',
            'name' => 'jobvitestate',
            'type' => 'multiselect',
            'source' => $jobViteState
        ],
        [
            'label' => 'Branch',
            'name' => 'branch',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getJobsBranches()
        ],
        [
            'label' => 'Region',
            'name' => 'region',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getRegion('jobvite_branches'),
            'disabled' => true
        ],
        [
            'label' => 'Recruiter',
            'name' => 'recruiter',
            'type' => 'multiselect',
            'source' => H::getHelper("ConstantHelper")->getRecruiters('id')
        ],
        [
            'label' => 'Category',
            'name' => 'Category',
            'type' => 'multiselect',
            'source' => $category
        ],
    ];

    $categoryTherapy = [];
    $categoryList = JobCategories::query()
        ->where('is_enabled', true)
        ->where('category', 'like', '%thera%')
        ->pluck('category')
        ->toArray();
    foreach ($categoryList as $key => $value) {
        $categoryTherapy[] = ['value' => $value, 'text' => $value];
    }
    $jobviteTherapyfilters = [
        [
            'label'=>'Me Mode',
            'name'=>'default',
            'type'=>'checkbox',
            'value'=>true
        ],
        [
            'label'=>'State',
            'name'=>'jobvitestate',
            'type'=>'multiselect',
            'source'=> $jobViteState
        ],
        [
            'label'=>'Branch',
            'name'=>'branch',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getJobsBranches()
        ],
        [
            'label'=>'Region',
            'name'=>'region',
            'type'=>'multiselect',
            'source'=>H::getHelper('ConstantHelper')->getRegion('jobvite_branches'),
            'disabled' => true
        ],
        [
            'label' => 'Recruiter',
            'name' => 'recruiter',
            'type' => 'multiselect',
            'source' => H::getHelper("ConstantHelper")->getRecruiters('id')
        ],
        [
            'label' => 'Category',
            'name' => 'Category',
            'type' => 'multiselect',
            'source' => $categoryTherapy
        ],
    ];

    $petdata = [];
    $petdatas = config('constant')['petopetionarray'];
    foreach ($petdatas as $key => $value) {
        $petdata[] = ['value' => $value, 'text' => $value];
    }
    $petfilter = [
        [
            'label' => 'P.E.T',
            'name' => 'pet',
            'type' => 'select',
            'source' => $petdata,
        ]
    ];

    $triagefilter = [
        [
            'label' => 'State',
            'name' => 'triagestate',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
        ],
        [
            'label' => 'Branch',
            'name' => 'triagebranch',
            'type' => 'multiselect',
            'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
        ],
        [
            'label' => 'Terminal Payor',
            'name' => 'terminal_payor',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getPayers()
        ],
        [
            'label' => 'Reporter',
            'name' => 'reporter',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getReporterList(true)
        ],
        [
            'label' => 'Disposition',
            'name' => 'triagedisposition',
            'type' => 'multiselect',
            'source' => H::getHelper('ConstantHelper')->getTriageDisposition()
        ],
        [
            'label' => 'Full Name',
            'name' => 'triagefullname',
            'type' => 'text',
            'placeholder' => 'Enter FullName'
        ],
        [
            'label' => 'DOB',
            'name' => 'triagedob',
            'type' => 'text',
            'placeholder' => 'Enter DOB'
        ]
    ];
    ?>

    <style>
        .dashboard-showall {
            font-size: 14px;
            font-weight: normal;
        }

        .filter-label {
            display: none;
        }

        .bucket-subheading {
            font-size: 12px;
            padding-top: 10px;
            padding-left: 0px;
        }

        .sub-level-cnt {
            margin-left: 15px;
        }

        #social_media_active_ads .datatable-filter-cnt {
            padding-right: 0;
            border: 0px;
        }

        #social_media_active_ads .head-title i {
            font-size: 28px !important;
            line-height: 30px !important;
            margin-left: 15px !important;
        }

        .badge{font-size:10px;font-weight:700;letter-spacing:.4px;padding:2px 7px;border-radius:10px;text-transform:uppercase;}
        .badge-override{background:var(--override-bg);color:var(--override);border:1px solid #eac54f;}
        .badge-global{background:#aed1f5;color:#1f5494;border:1px solid var(--global-border);}
    </style>
    <div class="accordion dashboard" id="accordionExample">

        @if(LD::checkFeatureFlag('dashboard-global-location-filter-v1'))
            @include('components.global-filter')
        @endif

        <!---Todo--->
        @canPermission(['todolist'])
        @include('todowidgt.tododashboard')
        @endcanPermission
        @canPermission(['dashboard.activitydata'])

        <div class="card">
            <div class="card-header" id="recruiting">
                <h2 class="mb-0">
                    <button class="<?php echo $class?> pull-left btn btn-link btn-block text-left " type="button"
                            data-toggle="collapse"
                            data-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree">
                        RECRUITING<i class="fa fa-chevron-right"></i>
                    </button>
                    <?php
                    if ($isRecruiterAdmin || $showallPermission) { ?>
                    <div class="col-md-2 text-right pull-left">
                        <a
                            href="{{route('dashboard.allactivitydata')}}"
                            class="dashboard-showall"
                            title="Show Recruiter Tracker">
                            Show Recruiter Tracker
                        </a></div>
                    <?php
                    } ?>
                </h2>
            </div>

            <div id="collapseThree" class="collapse show" aria-labelledby="headingThree"
                 data-parent="#accordionExample">

                @if (LD::checkFeatureFlag('dashboard-nthalerts-inquiry-bucket'))
                    @canPermission(['view-nthleads-inquiries'])
                    @include('nthalerts.leads')
                    @endcanPermission
                @endif

                <div class="card-body">
                    <div class="card" data-follows-global="true">
                        <div class="card-header" id="headingOne">
                            <h3 class="mb-0">
                                <button class="btn btn-link btn-block text-left" type="button" data-toggle="collapse" data-target="#collapseOne" aria-expanded="fa;se" aria-controls="collapseOne">
                                    DAILY ACTIVITY
                                    @include('account.partials.globalFilterBadge')
                                    <i class="fa fa-chevron-right"></i>
                                </button>
                            </h3>
                        </div>
                        <div id="collapseOne" class="collapse show " aria-labelledby="headingOne" data-parent="#recruiting">

                            <div
                                class="filters-common"
                                data-bucket-filter="true"
                                data-global-filter-fields='{"state":"state","region":"region","branch":"aoc_facility_id","stateTriggersAjax":true}'
                                data-triggers-counter-refresh="true"
                            >
                                @component('components.datatable-filters',['filters'=>$filters,'formname'=>'dashboardactivity'])
                                @endcomponent
                            </div>

                            <div class="card-body">
                                <div class="accordion sub-level-cnt" id="dailyactivityaccordion">

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse"
                                                        data-target="#NEED_TO_REVIEW_NEW_CLIENTS" aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    NEED TO REVIEW NEW CLIENTS <span
                                                        id="NEED_TO_REVIEW_NEW_CLIENTS-counter"><?php
                                                                                                // echo H::getHelper('Activity')->getActivityByStatus('NEED TO REVIEW NEW CLIENTS',$request,'1')?></span><i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="NEED_TO_REVIEW_NEW_CLIENTS" class="collapse"
                                             aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                                <b>Description:</b> Candidates who need to review new clients due to not
                                                making it to candidate start with previous client.<br>
                                                <b>Timeline:</b> 1 business hour, should not be in bucket for more than
                                                72 hours (PET 1, 2, &3). After one business hour has passed any
                                                recruiter in the region can and should call the candidate if the
                                                candidate has not been contacted once entering the bucket.</p>
                                            <div class="card-body">
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml(
                                                    "NEED TO REVIEW NEW CLIENTS"
                                                ) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse"
                                                        data-target="#NEED_TO_BE_SCHEDULED_BY_RA" aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    NEED TO BE SCHEDULED BY RA <span
                                                        id="NEED_TO_BE_SCHEDULED_BY_RA-counter"><?php
                                                                                                // echo H::getHelper('Activity')->getActivityByStatus('NEED TO BE SCHEDULED BY RA',$request,'1')?></span><i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="NEED_TO_BE_SCHEDULED_BY_RA" class="collapse "
                                             aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> RA</u><br>
                                                <b>Description:</b> Candidates who are pending next steps to be
                                                scheduled.<br>
                                                <b>Timeline:</b> 1 business hour.</p>
                                            <div class="card-body">
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml(
                                                    "NEED TO BE SCHEDULED BY RA"
                                                ) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse"
                                                        data-target="#IPEA" aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    IPEAS <span id="IPEA-counter"><?php
                                                                                  // echo H::getHelper('Activity')->getActivityByStatus('IPEA',$request,'1')?></span><i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="IPEA" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> RA & Recruiter</u><br>
                                                <b>Description:</b> Candidates scheduled for IPEA today or dates in the
                                                past with an unresolved outcome. NCNS candidates are to be contacted by
                                                the recruiter.<br>
                                                <b>Timeline:</b> 1 hour from time IPEA starts.</p>
                                            @php
                                                $ipeaFilters[] = [
                                                    'label' => 'Show All',
                                                    'name' => 'ipea_all',
                                                    'type' => 'checkbox',
                                                    'value' => 0
                                                ];
                                                $ipeaFilters[] = [
                                                    'label'=>'Date',
                                                    'name' => 'ipea_date',
                                                    'fromName'=>'from_ipea_date',
                                                    'toName'=>'to_ipea_date',
                                                    'type'=>'dateRangePicker',
                                                    'fromdate'=> '',
                                                    'todate'=> '',
                                                    'extra_class' => 'hide_daterang'
                                                ];
                                                @endphp
                                                @php
                                                     $ipeaFilters[] = [
                                                        'label' => 'IPEA Status',
                                                        'name' => 'live_ipea_status',
                                                        'type' => 'multiselect',
                                                        'isGrouped'=>true,
                                                        'source' => H::getHelper('ConstantHelper')->getIpeaStatus(),
                                                    ];
                                                @endphp
                                            <div class="filters-common" data-bucket-filter="true">
                                                @component('components.datatable-filters', ['filters' => $ipeaFilters, 'formname' => 'daily_activity_ipea'])
                                                @endcomponent
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml("IPEA") ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#CCRS" aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    CCRS <span id="CCRS-counter"><?php
                                                                                 // echo H::getHelper('Activity')->getActivityByStatus('CCRS',$request,'1')?></span>
                                                    <i class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="CCRS" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> RA</u><br>
                                                <b>Description:</b> Candidates who are pending next steps to be
                                                scheduled after completion of CCR.<br>
                                                <b>Timeline:</b> 1 business hour, and if not completed or acknowledged
                                                then reach out to the branch.</p>
                                            <div class="card-body">
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml("CCRS") ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#PEPI" aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    PEPIS <span id="PEPI-counter"><?php
                                                                                  // echo H::getHelper('Activity')->getActivityByStatus('PEPI',$request,'1')?></span>
                                                    <i class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="PEPI" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> RA</u><br>
                                                <b>Description:</b> Candidates scheduled for PEPI today or dates in the
                                                past with an unresolved outcome.<br>
                                                <b>Timeline:</b> 1 hour from time of PEPI.</p>
                                            @php
                                                $pepiFilters[] = [
                                                    'label' => 'Show All',
                                                    'name' => 'pepi_all',
                                                    'type' => 'checkbox',
                                                    'value' => 0
                                                ];
                                                $pepiFilters[] = [
                                                    'label'=>'Date',
                                                    'name' => 'pepi_date',
                                                    'fromName'=>'from_pepi_date',
                                                    'toName'=>'to_pepi_date',
                                                    'type'=>'dateRangePicker',
                                                    'fromdate'=> '',
                                                    'todate'=> '',
                                                    'extra_class' => 'hide_pepi_daterang'
                                                ];
                                            @endphp
                                            <div class="filters-common" data-bucket-filter="true">
                                                @component('components.datatable-filters', ['filters' => $pepiFilters, 'formname' => 'daily_activity_pepi'])
                                                @endcomponent
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml("PEPI") ?>
                                            </div>
                                        </div>
                                    </div>


                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#WEPI" aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    WEPIS <span id="WEPI-counter"><?php
                                                                                  // echo H::getHelper('Activity')->getActivityByStatus('WEPI',$request,'1')?></span>
                                                    <i class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="WEPI" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> RA</u><br>
                                                <b>Description:</b> Candidates scheduled for WEPI today or dates in the
                                                past with an unresolved outcome.<br>
                                                <b>Timeline:</b> 1 hour from WEPI ending time.</p>
                                            @php
                                                $wepiFilters[] = [
                            'label' => 'Show All',
                            'name' => 'wepi_all',
                            'type' => 'checkbox',
                            'value' => 0
                        ];
                        $wepiFilters[] = [
                            'label'=>'Date',
                            'name' => 'wepi_date',
                            'fromName'=>'from_wepi_date',
                            'toName'=>'to_wepi_date',
                            'type'=>'dateRangePicker',
                            'fromdate'=> '',
                            'todate'=> '',
                            'extra_class' => 'hide_wepi_daterang'
                        ];
                                            @endphp
                                            <div class="filters-common">
                                                @component('components.datatable-filters', ['filters' => $wepiFilters, 'formname' => 'daily_activity_wepi'])
                                                @endcomponent
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml("WEPI") ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#TRAINING_SHIFT"
                                                        aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    TRAINING SHIFT <span id="TRAINING_SHIFT-counter"><?php
                                                                                                     // echo H::getHelper('Activity')->getActivityByStatus('TRAINING SHIFT',$request,'1')?></span>
                                                    <i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="TRAINING_SHIFT" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <div class="card-body">
                                                <p style="text-align: left;"><u><b>Responsible Party:</b> RA</u><br>
                                                    <b>Description:</b> Candidates scheduled for training shift today or
                                                    dates in the past with an unresolved outcome.<br>
                                                    <b>Timeline:</b> 1 hour from end of training shift.</p>
                                                @php
                                                    $trainigFilters[] = [
                            'label' => 'Show All',
                            'name' => 'trainig_all',
                            'type' => 'checkbox',
                            'value' => 0
                        ];
                        $trainigFilters[] = [
                            'label'=>'Date',
                            'name' => 'trainig_date',
                            'fromName'=>'from_trainig_date',
                            'toName'=>'to_trainig_date',
                            'type'=>'dateRangePicker',
                            'fromdate'=> '',
                            'todate'=> '',
                            'extra_class' => 'hide_trainig_daterang'
                        ];
                                                @endphp
                                                <div class="filters-common">
                                                    @component('components.datatable-filters', ['filters' => $trainigFilters, 'formname' => 'daily_activity_trainig'])
                                                    @endcomponent
                                                </div>
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml("TRAINING SHIFT") ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#PENDING_CANDIDATE_STARTS"
                                                        aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    PENDING CANDIDATE STARTS <span
                                                        id="PENDING_CANDIDATE_STARTS-counter"><?php
                                                                                              // echo H::getHelper('Activity')->getActivityByStatus('PENDING CANDIDATE STARTS',$request,'1')?></span><i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="PENDING_CANDIDATE_STARTS" class="collapse "
                                             aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> For informational
                                                    purposes for Recruiter, branch and RA</u><br>
                                                <b>Description:</b> Candidates scheduled for first billable shift in the
                                                future.<br>
                                            @php
                                                $pendingCandidateStartsFilter = [
                            [
                                'label' => 'Date',
                                'name' => 'daterange',
                                'type' => 'daterange',
                                'fromdate' => '',
                                'todate' => '',
                            ]
                        ];
                                            @endphp
                                            <div class="filters-common">
                                                @component('components.datatable-filters', ['filters' => $pendingCandidateStartsFilter, 'formname' => 'pendingcandidatestarts'])
                                                @endcomponent
                                            </div>
                                            <div class="card-body">
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml(
                                                    "PENDING CANDIDATE STARTS"
                                                ) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#CANDIDATE_STARTS"
                                                        aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    CANDIDATE STARTS <span id="CANDIDATE_STARTS-counter"><?php
                                                                                                         // echo H::getHelper('Activity')->getActivityByStatus('CANDIDATE STARTS',$request,'1')?></span><i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="CANDIDATE_STARTS" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> RA</u><br>
                                                <b>Description:</b> Candidates scheduled for candidate start today or
                                                dates in the past with unresolved outcome.<br>
                                                <b>Timeline:</b> 1 hour from start of shift</p>
                                            <div class="card-body">
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml("CANDIDATE STARTS") ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    if (LD::checkFeatureFlag('prn-bucket-modification-6736')) {
                                    if (H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew(
                                            'is_recruiting_team_leader'
                                        ) ||
                                        H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew(
                                            'is_recruiting_manager'
                                        )){ ?>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#PRN" aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    Active Coverage<span id="PRN-counter"><?php
                                                                                              // echo H::getHelper('Activity')->getActivityByStatus('PRN',$request,'1')
                                                                                              ?></span><i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="PRN" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;">
                                                @if (LD::checkFeatureFlag('update-active-coverage-description'))
                                                    <b>Responsible Party:</b> Recruiting Team Lead</br>
                                                    <b>Description:</b> Active Coverage clinicians pending 40 hours
                                                    following their candidate start date once their IPEA status is
                                                    marked as Ready to Work. Once a clinician has worked 40 hours,
                                                    they will no longer appear in the bucket. All records require
                                                    review,
                                                    follow up, and documentation of the applicable next step:
                                                    Inactivate the recruiter tracker if the clinician is no
                                                    longer interested in working, or move the tracker to
                                                    Needs to Review New Clients to identify permanent opportunities.
                                                    <a target="_blank"
                                                          href="https://angelsofcare.sharepoint.com/sites/Recruiting/SitePages/Active-Coverage-for-Recruiting.aspx">
                                                    Visit the Detailed Training Page</a><br>
                                                    <b>Timeline:</b> Review daily and document the appropriate
                                                        outcome.
                                                    @else
                                                            <b>Description:</b> Active Coverage nurses pending 40 hours
                                                            following
                                                            their hired date<br>
                                                    @endif
                                            </p>
                                            <div class="card-body">
                                                    <?php
                                                    echo H::getHelper("Activity")->getDataTableHtml("PRN") ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    } ?>

                                    <?php
                                    } else { ?>
                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#PRN" aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    PRN <span id="PRN-counter"><?php
                                                                                   // echo H::getHelper('Activity')->getActivityByStatus('PRN',$request,'1')?></span><i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="PRN" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                                <b>Description:</b> Work with branch to schedule candidate during
                                                IPEA.<br>
                                                <b>Timeline:</b> 1 hour from IPEA start time.</p>
                                            <div class="card-body">
                                                    <?php
                                                    echo H::getHelper("Activity")->getDataTableHtml("PRN") ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    } ?>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#SCHOOL_SETS_UP"
                                                        aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    SCHOOL SET UP <span id="SCHOOL_SETS_UP-counter"><?php
                                                                                                    // echo H::getHelper('Activity')->getActivityByStatus('SCHOOL SETS UP',$request,'1')?></span>
                                                    <i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="SCHOOL_SETS_UP" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> RA</u><br>
                                                <b>Description:</b> Candidates pending a school set up request.<br>
                                                <b>Timeline:</b> Day of due date.</p>
                                            <div class="card-body">
                                                <?php
                                                echo H::getHelper("Activity")->getDataTableHtml("SCHOOL SETS UP") ?>
                                            </div>

                                        </div>
                                    </div>

                                    <?php
                                    if (LD::checkFeatureFlag('dashboard-additional-bucket')) {
                                        ?>
                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#NO_CANDIDATE_MATCH"
                                                        aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    NO CANDIDATE MATCH <span id="NO_CANDIDATE_MATCH-counter"><?php
                                                                                                                 // echo H::getHelper('Activity')->getActivityByStatus('NO CANDIDATE MATCH',$request,'1')?></span>
                                                    <i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="NO_CANDIDATE_MATCH" class="collapse "
                                             aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <div class="card-body">
                                                    <?php
                                                    echo H::getHelper("Activity")->getDataTableHtml(
                                                        "NO CANDIDATE MATCH"
                                                    ) ?>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#NO_PROVIDER_MATCH"
                                                        aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    NO PROVIDER MATCH <span id="NO_PROVIDER_MATCH-counter"><?php
                                                                                                               // echo H::getHelper('Activity')->getActivityByStatus('NO PROVIDER MATCH',$request,'1')?></span>
                                                    <i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="NO_PROVIDER_MATCH" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <div class="card-body">
                                                    <?php
                                                    echo H::getHelper("Activity")->getDataTableHtml(
                                                        "NO PROVIDER MATCH"
                                                    ) ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    } ?>

                                    <?php
                                    if (LD::checkFeatureFlag('dashboard-exception-bucket')) {
                                        ?>
                                    <div class="card">
                                        <div class="card-header" id="dailyactivityone">
                                            <h4 class="mb-0">
                                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                        data-toggle="collapse" data-target="#EXCEPTION"
                                                        aria-expanded="false"
                                                        aria-controls="collapseOne">
                                                    EXCEPTION <span id="EXCEPTION-counter"><?php
                                                                                               // echo H::getHelper('Activity')->getActivityByStatus('NO PROVIDER MATCH',$request,'1')?></span>
                                                    <i
                                                        class="fa fa-chevron-right"></i>
                                                </button>
                                            </h4>
                                        </div>

                                        <div id="EXCEPTION" class="collapse " aria-labelledby="dailyactivityone"
                                             data-parent="#dailyactivityaccordion">
                                            <div class="card-body">
                                                    <?php
                                                    echo H::getHelper("Activity")->getDataTableHtml("EXCEPTION") ?>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                    } ?>
                                </div>

                            </div>
                        </div>
                    </div>


                    @canPermission(['dashboard.getjobvitedata'])
                    <div class="card">
                        <div class="card-header" id="headingThree">
                            <h3 class="mb-0">
                                <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed"
                                        type="button"
                                        data-toggle="collapse"
                                        data-target="#jobvite"
                                        aria-expanded="false"
                                        aria-controls="collapseThree">
                                    JOBVITE {{ LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_NURSING_FILTER->value) ? '- NURSING' : '' }}<i class="fa fa-chevron-right"></i>
                                </button>
                                <div class="col-md-2 text-right pull-left">
                                    <a href="{{route('candidatesListing', ['type' => 'nursing'])}}"
                                       class="dashboard-showall"
                                       title="Show All Candidates">Show All Candidates
                                    </a>
                                </div>
                            </h3>
                        </div>
                        <div id="jobvite" class="collapse sub-level-cnt" aria-labelledby="headingThree"
                             data-parent="#recruiting">
                            <div class="filters-common" style="display: flex;">
                                @canPermission('getAddCandidateForm')
                                <div class="pt-3">
                                    <a href="javascript:void(0);" onclick="showAddCandidateForm('nursing');">
                                        <i class="fa fa-plus-circle"
                                           aria-hidden="true"
                                           style="font-size:28px;color:#2f5baa;"></i>
                                    </a>
                                </div>
                                @endcanPermission
                                @component('components.datatable-filters',['filters'=>$jobvitefilters,'formname'=>'dashboardjobvite'])
                                @endcomponent
                            </div>
                            <div class="card-body">
                                <div class="card">
                                    <input type="hidden" name="reload-NEW" id="reload-NEW" value="1"/>
                                    <div class="card-header" id="jobvite-new">
                                        <h4 class="mb-0">
                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                    data-toggle="collapse" data-target="#NEW" aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                NEW <span id="NEW-counter"></span> <i class="fa fa-chevron-right"></i>
                                            </button>
                                        </h4>
                                    </div>

                                    <div id="NEW" class="collapse " aria-labelledby="dailyactivityone"
                                         data-parent="#jobvite">
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Candidates that have applied to a requisition.<br>
                                            <b>Timeline:</b> 1 business hour from application time. After 1 business
                                            hour has passed any recruiter in the region can and should call the
                                            candidate if the candidate is still in NEW status.</p>
                                        <div class="card-body">
                                            <?php
                                            echo H::getHelper("Jobvite")->getDataTableHtml("NEW") ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <input type="hidden" name="reload-recruiter_phone_screen"
                                           id="reload-recruiter_phone_screen" value="1"/>
                                    <div class="card-header" id="jobvite-recruiter-phone-screen">
                                        <h4 class="mb-0">
                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                    data-toggle="collapse" data-target="#recruiter_phone_screen"
                                                    aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                RECRUITER PHONE SCREEN <span id="recruiter_phone_screen-counter"></span>
                                                <i class="fa fa-chevron-right"></i>
                                            </button>
                                        </h4>
                                    </div>

                                    <div id="recruiter_phone_screen" class="collapse "
                                         aria-labelledby="dailyactivityone"
                                         data-parent="#jobvite">
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Candidates scheduled for phone call from recruiter to
                                            discuss opportunities.<br>
                                            <b>Timeline:</b> Time of next scheduled follow-up with candidate (move to
                                            PET status if unable to reach at scheduled time). After 1 business hour from
                                            follow-up time has passed any recruiter in the region can and should call
                                            the candidate if the candidate is still in RECRUITER PHONE SCREEN status
                                            with a past date follow-up.</p>
                                        <div class="card-body">
                                            <?php
                                            echo H::getHelper("Jobvite")->getDataTableHtml("recruiter_phone_screen") ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <input type="hidden" name="reload-jpet" id="reload-jpet" value="1"/>
                                    <div class="card-header" id="jobvite-jpet">
                                        <h4 class="mb-0">
                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                    data-toggle="collapse" data-target="#jpet" aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                P.E.T<span id="jpet-counter"></span> <i class="fa fa-chevron-right"></i>
                                            </button>
                                        </h4>
                                    </div>

                                    <div id="jpet" class="collapse " aria-labelledby="dailyactivityone"
                                         data-parent="#jobvite">
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Candidates that have contact attempts.<br>
                                            <b>Timeline:</b> <span>Daily every 24 hours and then rejected by the 72 hour mark.
                                            After 1 business hour has passed from the 24 hour mark any recruiter in the
                                            region can and should call the candidate if the candidate is still in the
                                            same PET status.</span></p>
                                        <div class="filters-common">
                                            @component('components.datatable-filters',['filters'=>$petfilter,'formname'=>'dashboardjobvitepet'])
                                            @endcomponent
                                        </div>
                                        <div class="card-body">
                                            <?php
                                            echo H::getHelper("Jobvite")->getDataTableHtml("jpet") ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="card">
                                    <input type="hidden" name="reload-pending_graduation_followup"
                                           id="reload-pending_graduation_followup" value="1"/>
                                    <div class="card-header" id="jobvite-pending_graduation_followup">
                                        <h4 class="mb-0">
                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                    data-toggle="collapse" data-target="#pending_graduation_followup"
                                                    aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                PENDING GRADUATION FOLLOW UPS <span
                                                    id="pending_graduation_followup-counter"></span> <i
                                                    class="fa fa-chevron-right"></i>
                                            </button>
                                        </h4>
                                    </div>

                                    <div id="pending_graduation_followup" class="collapse "
                                         aria-labelledby="dailyactivityone"
                                         data-parent="#jobvite">
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Candidates pending graduation dates.<br>
                                            <b>Timeline:</b> Contact candidate 30, 60, & 90 days prior to graduation.
                                            Onboard nursing students prior to their graduation if possible and they will
                                            be pending NCLEX.</p>
                                        <div class="card-body">
                                            <?php
                                            echo H::getHelper("Jobvite")->getDataTableHtml(
                                                "pending_graduation_followup"
                                            ) ?>
                                        </div>
                                    </div>
                                </div>

                                @if(LD::checkFeatureFlag('jobvite-dashboard-requisitions'))
                                    @include('jobvite.requisitionJobvite')
                                @endif

                            </div>
                        </div>
                    </div>
                    @endcanPermission

                    @if(LD::checkFeatureFlag(FeatureFlagsEnum::ENABLE_THERAPIST_BUCKET->value))
                        @canPermission(['dashboard.getjobvitedata'])
                        @if($isRecruiter || !$isTherapyClinicalManager)
                            <div class="card">
                                <div class="card-header" id="headingTherapy">
                                    <h3 class="mb-0">
                                        <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed"
                                                type="button" data-toggle="collapse"
                                                data-target="#jobvite-therapy" aria-expanded="false" aria-controls="jobvite-therapy">
                                            JOBVITE - THERAPY<i class="fa fa-chevron-right"></i>
                                        </button>
                                        <div class="col-md-2 text-right pull-left">
                                            <a href="{{route('candidatesListing', ['type' => 'therapy'])}}"
                                               class="dashboard-showall" title="Show All Candidates">Show All Candidates</a>
                                        </div>
                                    </h3>
                                </div>

                                <div id="jobvite-therapy" class="collapse sub-level-cnt"
                                     aria-labelledby="headingTherapy" data-parent="#recruiting">

                                    <div class="filters-common" style="display: flex;">
                                        @canPermission('getAddCandidateForm')
                                        <div class="pt-3">
                                            <a href="javascript:void(0);" onclick="showAddCandidateForm('therapy');">
                                                <i class="fa fa-plus-circle" aria-hidden="true"
                                                   style="font-size:28px;color:#2f5baa;"></i>
                                            </a>
                                        </div>
                                        @endcanPermission
                                        @component('components.datatable-filters',['filters'=> $jobviteTherapyfilters,'formname'=>'dashboardjobvitetherapy'])
                                        @endcomponent
                                    </div>

                                    <div class="card-body">
                                        <!-- NEW -->
                                        <div class="card">
                                            <input type="hidden" name="reload-NEW_therapy" id="reload-NEW_therapy" value="1" />
                                            <div class="card-header" id="jobvite-new_therapy">
                                                <h4 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                            data-toggle="collapse" data-target="#NEW_therapy"
                                                            aria-expanded="false" aria-controls="NEW_therapy">
                                                        NEW <span id="NEW-therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                                                    </button>
                                                </h4>
                                            </div>

                                            <div id="NEW_therapy" class="collapse" aria-labelledby="jobvite-new-therapy"
                                                 data-parent="#jobvite-therapy">
                                                <p style="text-align: left;">
                                                    <u><b>Responsible Party:</b> Recruiter</u><br>
                                                    <b>Description:</b> Candidates that have applied to a requisition, and are ready to be called by the recruiter to discuss opportunities.<br>
                                                    <b>Timeline:</b> 1 business hour from application time. After 1 business hour has passed any recruiter in the region can and should call the candidate if the candidate is still in NEW status.
                                                </p>
                                                <div class="card-body">
                                                        <?php echo H::getHelper("Jobvite")->getDataTableHtml("NEW_therapy")?>
                                                </div>
                                            </div>
                                        </div>


                                        <!-- P.E.T. -->
                                        <div class="card">
                                            <input type="hidden" name="reload-PET_therapy" id="reload-PET_therapy" value="1" />
                                            <div class="card-header" id="jobvite-PET_therapy">
                                                <h4 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                            data-toggle="collapse" data-target="#PET_therapy"
                                                            aria-expanded="false" aria-controls="PET_therapy">
                                                        P.E.T. <span id="PET-therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                                                    </button>
                                                </h4>
                                            </div>

                                            <div id="PET_therapy" class="collapse" aria-labelledby="jobvite-pet-therapy"
                                                 data-parent="#jobvite-therapy">
                                                <p style="text-align: left;">
                                                    <u><b>Responsible Party:</b> Recruiter</u><br>
                                                    <b>Description:</b> Candidates that have contact attempts.<br>
                                                    <b>Timeline:</b> Daily every 24 hours and then rejected by the 72 hour mark. After 1 business hour has passed from the 24 hour mark any recruiter in the region can and should call the candidate if the candidate is still in the same PET status.
                                                </p>
                                                <div class="card-body">
                                                        <?php echo H::getHelper("Jobvite")->getDataTableHtml("PET_therapy")?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Scheduled Recruiter Phone Screen -->
                                        <div class="card">
                                            <input type="hidden" name="reload-scheduled_recruiter_phone_screen_therapy" id="reload-scheduled_recruiter_phone_screen_therapy" value="1" />
                                            <div class="card-header" id="jobvite-scheduled_recruiter_phone_screen_therapy">
                                                <h4 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                            data-toggle="collapse" data-target="#scheduled_recruiter_phone_screen_therapy"
                                                            aria-expanded="false" aria-controls="scheduled_recruiter_phone_screen_therapy">
                                                        Scheduled Recruiter Phone Screen <span id="scheduled_recruiter_phone_screen_therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                                                    </button>
                                                </h4>
                                            </div>

                                            <div id="scheduled_recruiter_phone_screen_therapy" class="collapse" aria-labelledby="jobvite-scheduled_recruiter_phone_screen_therapy"
                                                 data-parent="#jobvite-therapy">
                                                <p style="text-align: left;">
                                                    <u><b>Responsible Party:</b> Therapy Recruiter</u><br>
                                                    <b>Description:</b> Candidates scheduled for recruiter phone screening.<br>
                                                    <b>Timeline:</b> 1 business hour after the scheduled recruiter phone screen.
                                                </p>
                                                <div class="card-body">
                                                        <?php echo H::getHelper('Jobvite')->getDataTableHtml(JobViteRecruiterJobType::SCHEDULED_RECRUITER_PHONE_SCREEN_THERAPY->value)?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Recruiter Phone Screen -->
                                        <div class="card">
                                            <input type="hidden" name="reload-recruiter_phone_screen_therapy" id="reload-recruiter_phone_screen_therapy" value="1" />
                                            <div class="card-header" id="jobvite-recruiter_phone_screen_therapy">
                                                <h4 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                            data-toggle="collapse" data-target="#recruiter_phone_screen_therapy"
                                                            aria-expanded="false" aria-controls="recruiter_phone_screen_therapy">
                                                        Recruiter Phone Screen <span id="recruiter_phone_screen-therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                                                    </button>
                                                </h4>
                                            </div>

                                            <div id="recruiter_phone_screen_therapy" class="collapse" aria-labelledby="jobvite-recruiter_phone_screen-therapy"
                                                 data-parent="#jobvite-therapy">
                                                <p style="text-align: left;">
                                                    <u><b>Responsible Party:</b> Recruiter</u><br>
                                                    <b>Description:</b> Candidates that have passed by the recruiter phone screen, discussed opportunities and are ready to be scheduled.<br>
                                                    <b>Timeline:</b> Time of next scheduled follow-up with candidate (move to PET status if unable to reach at scheduled time). After 1 business hour from follow-up time has passed any recruiter in the region can and should call the candidate if the candidate is still in RECRUITER PHONE SCREEN status with a past date follow-up.
                                                </p>
                                                <div class="card-body">
                                                        <?php echo H::getHelper("Jobvite")->getDataTableHtml("recruiter_phone_screen_therapy")?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Scheduled Clinical Interview -->
                                        <div class="card">
                                            <input type="hidden" name="reload-scheduled_clinical_interview_therapy" id="reload-scheduled_clinical_interview_therapy" value="1" />
                                            <div class="card-header" id="jobvite-scheduled_clinical_interview_therapy">
                                                <h4 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                            data-toggle="collapse" data-target="#scheduled_clinical_interview_therapy"
                                                            aria-expanded="false" aria-controls="scheduled_clinical_interview_therapy">
                                                        Scheduled Clinical Interview <span id="scheduled_clinical_interview-therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                                                    </button>
                                                </h4>
                                            </div>

                                            <div id="scheduled_clinical_interview_therapy" class="collapse" aria-labelledby="jobvite-scheduled_clinical_interview-therapy"
                                                 data-parent="#jobvite-therapy">
                                                <p style="text-align: left;">
                                                    <u><b>Responsible Party:</b> Therapy Clinical Manager</u><br>
                                                    <b>Description:</b> Candidates scheduled for the clinical interview, ready for the clinical interview with the Therapy Clinical Manager.<br>
                                                    <b>Timeline:</b> 1 business day after the scheduled date of the interview.
                                                </p>
                                                <div class="card-body">
                                                        <?php echo H::getHelper("Jobvite")->getDataTableHtml("scheduled_clinical_interview_therapy")?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Rescheduled Clinical Interview -->
                                        <div class="card">
                                            <input type="hidden" name="reload-rescheduled_clinical_interview_therapy" id="reload-rescheduled_clinical_interview_therapy" value="1" />
                                            <div class="card-header" id="jobvite-rescheduled_clinical_interview_therapy">
                                                <h4 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                            data-toggle="collapse" data-target="#rescheduled_clinical_interview_therapy"
                                                            aria-expanded="false" aria-controls="rescheduled_clinical_interview_therapy">
                                                        Rescheduled Clinical Interview <span id="rescheduled_clinical_interview-therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                                                    </button>
                                                </h4>
                                            </div>

                                            <div id="rescheduled_clinical_interview_therapy" class="collapse" aria-labelledby="jobvite-rescheduled_clinical_interview-therapy"
                                                 data-parent="#jobvite-therapy">
                                                <p style="text-align: left;">
                                                    <u><b>Responsible Party:</b> Therapy Clinical Manager</u><br>
                                                    <b>Description:</b> Candidates rescheduled for the clinical interview, ready for the clinical interview with the Therapy Clinical Manager.<br>
                                                    <b>Timeline:</b> 1 business day after the scheduled date of the interview.
                                                </p>
                                                <div class="card-body">
                                                        <?php echo H::getHelper("Jobvite")->getDataTableHtml("rescheduled_clinical_interview_therapy")?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Clinical Interview -->
                                        <div class="card">
                                            <input type="hidden" name="reload-clinical_interview_therapy" id="reload-clinical_interview_therapy" value="1" />
                                            <div class="card-header" id="jobvite-clinical_interview_therapy">
                                                <h4 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                            data-toggle="collapse" data-target="#clinical_interview_therapy"
                                                            aria-expanded="false" aria-controls="clinical_interview_therapy">
                                                        Clinical Interview <span id="scheduled_clinical_interview-therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                                                    </button>
                                                </h4>
                                            </div>

                                            <div id="clinical_interview_therapy" class="collapse" aria-labelledby="jobvite-scheduled_clinical_interview-therapy"
                                                 data-parent="#jobvite-therapy">
                                                <p style="text-align: left;">
                                                    <u><b>Responsible Party:</b> Recruiter</u><br>
                                                    <b>Description:</b> Candidates who passed by the the clinical interview and can receive the job offer letter if eligible.<br>
                                                    <b>Timeline:</b> 2 hours after the scheduled date and time of the clinical interview.
                                                </p>
                                                <div class="card-body">
                                                        <?php echo H::getHelper("Jobvite")->getDataTableHtml("clinical_interview_therapy")?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Offer Extended -->
                                        <div class="card">
                                            <input type="hidden" name="reload-offer_extended_therapy" id="reload-offer_extended_therapy" value="1" />
                                            <div class="card-header" id="jobvite-offer_extended_therapy">
                                                <h4 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                            data-toggle="collapse" data-target="#offer_extended_therapy"
                                                            aria-expanded="false" aria-controls="offer_extended_therapy">
                                                        Offer Extended <span id="scheduled_clinical_interview-therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                                                    </button>
                                                </h4>
                                            </div>

                                            <div id="offer_extended_therapy" class="collapse" aria-labelledby="jobvite-scheduled_clinical_interview-therapy"
                                                 data-parent="#jobvite-therapy">
                                                <p style="text-align: left;">
                                                    <u><b>Responsible Party:</b> Recruiter</u><br>
                                                    <b>Description:</b> All candidates who passed the interview and received a job offer letter. At this point, the recruiter can record a follow-up (if the candidate has not yet returned the signed job offer letter), or set the final status of the application.<br>
                                                    <b>Timeline:</b> 1 business day after the job letter be sent or the last follow-up date.
                                                </p>
                                                <div class="card-body">
                                                        <?php echo H::getHelper("Jobvite")->getDataTableHtml("offer_extended_therapy")?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Pending Graduation Follow-up -->
                                        <div class="card">
                                            <input type="hidden" name="reload-pending_graduation_follow_up_therapy" id="reload-pending_graduation_follow_up_therapy" value="1" />
                                            <div class="card-header" id="jobvite-pending_graduation_follow_up_therapy">
                                                <h4 class="mb-0">
                                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                            data-toggle="collapse" data-target="#pending_graduation_follow_up_therapy"
                                                            aria-expanded="false" aria-controls="pending_graduation_follow_up_therapy">
                                                        Pending Graduation Follow-up <span id="scheduled_clinical_interview-therapy-counter"></span> <i class="fa fa-chevron-right"></i>
                                                    </button>
                                                </h4>
                                            </div>

                                            <div id="pending_graduation_follow_up_therapy" class="collapse" aria-labelledby="jobvite-scheduled_clinical_interview-therapy"
                                                 data-parent="#jobvite-therapy">
                                                <p style="text-align: left;">
                                                    <u><b>Responsible Party:</b> Recruiter</u><br>
                                                    <b>Description:</b> Candidates pending graduation dates.<br>
                                                    <b>Timeline:</b> Manual follow-up date set up by the recruiter.
                                                </p>
                                                <div class="card-body">
                                                        <?php echo H::getHelper("Jobvite")->getDataTableHtml("pending_graduation_follow_up_therapy")?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                        @endcanPermission
                    @endif

                    @canPermission(['dashboard.getNTHChecklist'])
                    <div class="card" data-follows-global="true">
                        <div class="card-header" id="jobvite-nthchecklist">
                            <h3 class="mb-0">
                                <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed"
                                        type="button"
                                        data-toggle="collapse" data-target="#nthchecklist-child" aria-expanded="false"
                                        aria-controls="collapseOne">
                                    NTH CHECKLIST
                                    @include('account.partials.globalFilterBadge')
                                    <i class="fa fa-chevron-right"></i>
                                </button>
                                @canPermission('nth.nthList')
                                <div class="col-md-2 text-right pull-left">
                                    <a href="{{route('nth.nthList')}}" class="dashboard-showall"
                                       title="Show Need To Hire List">Show Need To Hire List</a>
                                </div>
                                @endcanPermission
                            </h3>
                        </div>
                        <?php
                        $nthChecklistFiltersDashboard = [
                            [
                                'label' => 'Me Mode',
                                'name' => 'nth-memode',
                                'type' => 'checkbox',
                                'value' => true
                            ],
                            [
                                'label' => 'State',
                                'name' => 'nth-state',
                                'type' => 'multiselect',
                                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
                            ],
                            [
                                'label' => 'Region',
                                'name' => 'nth-region',
                                'type' => 'multiselect',
                                'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
                                'disabled' => true
                            ],
                            [
                                'label' => 'Branch',
                                'name' => 'nth-aoc_facility_id',
                                'type' => 'multiselect',
                                'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource(
                                    'aoc_facility_id'
                                )
                            ],
                            [
                                'label' => 'Recruiter',
                                'name' => 'nth-Recruiter',
                                'type' => 'multiselect',
                                'source' => $recruiters
                            ],
                            [
                                'label' => 'Payer',
                                'name' => 'nth-payer',
                                'type' => 'multiselect',
                                'source' => H::getHelper('ConstantHelper')->getPayers()
                            ],
                            [
                                'label' => 'Special Payer Group',
                                'name' => 'nth-payer-group',
                                'type' => 'multiselect',
                                'source' => H::getHelper('ConstantHelper')->getPayerGroup()
                            ],
                            [
                                'label' => 'Search by Text',
                                'name' => 'search-by-text',
                                'type' => 'text'
                            ]
                        ];

                        if (LD::checkFeatureFlag('add-service-needed-filter-on-nthcheklist')) {
                            foreach ($nthChecklistFiltersDashboard as $index => $item) {
                                if ($item['name'] === 'nth-Recruiter') {
                                    array_splice($nthChecklistFiltersDashboard, $index + 1, 0, [
                                        [
                                            'label' => 'Service Needed',
                                            'name' => 'serviceneeded',
                                            'type' => 'multiselect',
                                            'source' => $serviceneeded
                                        ]
                                    ]);
                                    break;
                                }
                            }
                        }
                        ?>
                        <div id="nthchecklist-child" class="collapse sub-level-cnt" aria-labelledby="dailyactivityone"
                             data-parent="#recruiting">
                            <div
                                class="filters-common"
                                data-bucket-filter="true"
                                data-global-filter-fields='{"state":"nth-state","region":"nth-region","branch":"nth-aoc_facility_id"}'
                                data-triggers-counter-refresh="true"
                            >
                                @component('components.datatable-filters',['filters'=>$nthChecklistFiltersDashboard,'formname'=>'nthChecklistFiltersDashboard'])
                                @endcomponent
                            </div>
                            <div class="card-body">
                                <?php
                                $NTHBuckets = H::getHelper("NTHChecklist")->getBuckets();
                                foreach ($NTHBuckets as $bucketKey => $bucket) { ?>
                                <div class="card">
                                    <div class="card-header" id="nth-test1">
                                        <h4 class="mb-0">
                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                    data-toggle="collapse" data-target="#<?php echo $bucketKey?>"
                                                    aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                    <?php
                                                    echo $bucket['heading'] ?> <span
                                                    id="<?php echo $bucketKey?>-counter"></span> <i
                                                    class="fa fa-chevron-right"></i>
                                            </button>
                                        </h4>
                                    </div>
                                    <div id="<?php echo $bucketKey?>" class="collapse "
                                         aria-labelledby="dailyactivityone"
                                         data-parent="#nthchecklist-child">
                                            <?php
                                        if ($bucket['heading'] == 'Hub City Requisition'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Confirm hub city requisition is open on JV and
                                            indeed.<br>
                                            <b>Timeline:</b> 1st business hour on NTH checklist.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Text Blast'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Text and email all nurses on text blast map and
                                            potential nurse list map.<br>
                                            <b>Timeline:</b> 1st business hour on NTH checklist.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'P.E.T'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> PET candidates in show all candidates page filtered to
                                            the city, category, include completed, and not an active workflow.<br>
                                            <b>Timeline:</b> 1st business day on NTH checklist.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Social Media'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Join and post in FB groups in the clients city,
                                            surrounding cities, and county.<br>
                                            <b>Timeline:</b> 1st business day on NTH checklist.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Private Message'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Private message nurses I am connected with in closed FB
                                            groups or have commented on competition page(s).<br>
                                            <b>Timeline:</b> 2nd business day and continuous.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Linkedin'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Find, connect and message nurses on LinkedIn.<br>
                                            <b>Timeline:</b> 2nd business day and continuous.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Indeed'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Use indeed resume search to headhunt nurses.<br>
                                            <b>Timeline:</b> 2nd business day and continuous.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Cold Call'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Cold call nurses in the text blast and cold calling
                                            maps.<br>
                                            <b>Timeline:</b> 3rd business day and continuous.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Mail Matters'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Mail handwritten letters or postcards using the BON
                                            list.<br>
                                            <b>Timeline:</b> 5th business day and continuous.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Bon Nurse Search'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Search for BON nurses on Facebook and LinkedIn and
                                            private message them.<br>
                                            <b>Timeline:</b> 2nd business week and continuous.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Social Media Sponsored'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Submit a social media ad request.<br>
                                            <b>Timeline:</b> 3rd usiness week or for escalated clients.</p>
                                        <?php
                                        }elseif($bucket['heading'] == 'Escalated'){ ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiter</u><br>
                                            <b>Description:</b> Escalate to RTL and manager.<br>
                                            <b>Timeline:</b> 1 business day to give direction or escalate to leadership
                                        </p>
                                        <?php
                                        } ?>
                                        <div class="card-body">
                                                <?php
                                                echo H::getHelper("NTHChecklist")->getDataTableHtml(
                                                    $bucketKey,
                                                    $bucket['heading']
                                                ) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                } ?>
                            </div>
                        </div>
                    </div>
                    @endcanPermission

                    <?php
                    if (LD::checkFeatureFlag('dashboard-recruiting-nth-exception')) {
                        ?>
                    @canPermission(['dashboard.getrecrutingexception'])
                        <?php

                        if (LD::checkFeatureFlag('make-exception-bucket-filter-multiselect')) {
                            $recruitingexception = [
                                [
                                    'label' => 'State',
                                    'name' => 'recruiter-state',
                                    'type' => 'multiselect',
                                    'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
                                ],
                                [
                                    'label' => 'Region',
                                    'name' => 'recruitingregion',
                                    'type' => 'multiselect',
                                    'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
                                    'disabled' => true
                                ],
                                [
                                    'label' => 'Branch',
                                    'name' => 'recruiter-aocfacility_id',
                                    'type' => 'multiselect',
                                    'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource(
                                        'aoc_facility_id'
                                    )
                                ],
                                [
                                    'label' => 'Recruiter',
                                    'name' => 'recruiter-Recruiter',
                                    'type' => 'multiselect',
                                    'source' => $recruiters
                                ]
                            ];
                        } else {
                            $recruitingexception = [
                                [
                                    'label' => 'State',
                                    'name' => 'recruiter-state',
                                    'type' => 'select',
                                    'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource('state')
                                ],
                                [
                                    'label' => 'Region',
                                    'name' => 'recruitingregion',
                                    'type' => 'multiselect',
                                    'source' => H::getHelper('ConstantHelper')->getRegionForDaily(),
                                    'disabled' => true
                                ],
                                [
                                    'label' => 'Branch',
                                    'name' => 'recruiter-aoc_facility_id',
                                    'type' => 'select',
                                    'source' => H::getHelper('CommonDatatableFilters')->getFilterDataSource(
                                        'aoc_facility_id'
                                    )
                                ],
                                [
                                    'label' => 'Recruiter',
                                    'name' => 'recruiter-Recruiter',
                                    'type' => 'select',
                                    'source' => $recruiters
                                ]
                            ];
                        }
                        ?>
                    <div class="card" data-follows-global="true">
                        <div class="card-header" id="recruiting-exception">
                            <h3 class="mb-0">
                                <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed"
                                        type="button"
                                        data-toggle="collapse" data-target="#recruiting-exception-child"
                                        aria-expanded="false"
                                        aria-controls="collapseOne">
                                    EXCEPTION
                                    @include('account.partials.globalFilterBadge')
                                    <i class="fa fa-chevron-right"></i>
                                </button>
                            </h3>
                        </div>
                        <div id="recruiting-exception-child" class="collapse sub-level-cnt"
                             aria-labelledby="dailyactivityone"
                             data-parent="#recruiting-exception">
                            <div
                                class="filters-common"
                                data-bucket-filter="true"
                                data-global-filter-fields='{"state":"recruiter-state","region":"recruitingregion","branch":"recruiter-aoc_facility_id"}'
                                data-triggers-counter-refresh="true"
                            >
                                @component('components.datatable-filters',['filters'=>$recruitingexception,'formname'=>'recruitingexception'])
                                @endcomponent
                            </div>
                            <div class="card-body">
                                    <?php
                                    $recruitingException = H::getHelper("RecruitingException")->getBuckets();
                                foreach ($recruitingException as $bucketKey => $bucket) { ?>
                                <div class="card">
                                    <div class="card-header" id="recruiting-exception-<?php echo $bucketKey?>">
                                        <h4 class="mb-0">
                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                    data-toggle="collapse" data-target="#<?php echo $bucketKey?>"
                                                    aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                    <?php
                                                    echo $bucket['heading'] ?> <span
                                                    id="<?php echo $bucketKey?>-counter"></span> <i
                                                    class="fa fa-chevron-right"></i>
                                            </button>
                                        </h4>
                                    </div>
                                    <div id="<?php echo $bucketKey?>" class="collapse "
                                         aria-labelledby="dailyactivityone" data-parent="#recruiting-exception">

                                        <div class="card-body">
                                            @if (LD::checkFeatureFlag('recruiting-dashboard-update-descriptions'))
                                                @if($bucketKey === 'nth_clients_with_no_recruiter')
                                                    <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiting Team Lead</u><br>
                                                        <b>Description:</b> Clients on the NTH who do not have a recruiter assigned. Review and
                                                        update the NTH record with the correct recruiter, which will automatically remove the record from the bucket.<br>
                                                        <b>Timeline:</b> Review daily - any NTH client without a recruiter assigned should be updated immediately.
                                                        Any record remaining in the bucket 1 business day after the NTH Created Date will be highlighted in red.</p>
                                                @elseif($bucketKey === 'nth_active_tracker_disabled_recruiter')
                                                    <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiting Team Lead</u><br>
                                                        <b>Description:</b> Clinicians with an active tracker that have an inactive recruiter assigned. Review and update
                                                        the record with the correct recruiter, which will automatically remove the record from the bucket.<br>
                                                        <b>Timeline:</b> Review daily - all active recruiter trackers should be reassigned to the correct recruiter.
                                                        Any record remaining in the bucket 1 business day after the Tracker Created Date will be highlighted in red.</p>
                                                @elseif($bucketKey === 'mismatched_first_shift_confirmed')
                                                    <p style="text-align: left;"><u><b>Responsible Party:</b> Recruiting Team Lead</u><br>
                                                        <b>Description:</b> Clinicians who have had their first shift confirmed where the Candidate Start Date does not match
                                                        the first billable shift from Kantime. All records require review and documentation of the applicable next step: updating
                                                        the outcome to reflect why the shift is mismatched, confirming the first shift was worked, or rescheduling the candidate start.<br>
                                                        <b>Timeline:</b> Review daily and document the appropriate outcome. Any record remaining in the bucket 1 business day after
                                                        the candidate start date will be highlighted in red.</p>
                                                @endif
                                            @endif
                                                <?php
                                                echo H::getHelper("RecruitingException")->getDataTableHtml(
                                                    $bucketKey
                                                ) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                } ?>
                            </div>
                        </div>
                    </div>
                    @endcanPermission
                    <?php
                    } ?>

                    @if (LD::checkFeatureFlag('nth-expiring-jobs'))
                        @canPermission(['dashboard.getNTHExpiringJobslist'])
                        <div class="card">
                            <div class="card-header" id="nth-expiring-jobs">
                                <h3 class="mb-0">
                                    <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed"
                                            type="button"
                                            data-toggle="collapse" data-target="#nth_expiring_jobs"
                                            aria-expanded="false"
                                            aria-controls="collapseOne">
                                        NTH Expiring Jobs <span id="nth_expiring_jobs-counter"></span><i
                                            class="fa fa-chevron-right"></i>
                                    </button>
                                </h3>
                            </div>
                            <div id="nth_expiring_jobs" class="collapse " aria-labelledby="dailyactivityone"
                                 data-parent="#jobvite-nthchecklist">
                                <div class="card-body">
                                        <?php
                                        echo H::getHelper("Jobvite")->getDataTableHtml("nth_expiring_jobs") ?>
                                </div>
                            </div>
                        </div>
                        @endcanPermission
                    @endif

                    @canPermission(['dashboard.getPayrateApprovalsDashboard'])
                    @php
                        $managersList = H::getHelper("UserPermissionResolver")->getUserByPermission("can_approve_pay_rates");
                    $managers = [];
                    foreach($managersList as $key => $val) {
                        $managers[] = ["value"=>$val->name,'text'=>$val->name];
                    }
                    $payrateapprovalfilters = [
                        [
                            'label'=>'Branch',
                            'name'=>'payrate-aoc_facility_id',
                            'type'=>'multiselect',
                            'source'=>H::getHelper('CommonDatatableFilters')->getFilterDataSource('aoc_facility_id')
                        ],
                        [
                            'label'=>'Recruiter',
                            'name'=>'payrate-Recruiter',
                            'type'=>'multiselect',
                            'source'=>$recruiters
                        ],
                        [
                            'label'=>'Manager',
                            'name'=>'payrate-Manager',
                            'type'=>'multiselect',
                            'source'=> $managers
                        ]
                    ];
                    @endphp
                    <div class="card">
                        <input type="hidden" name="reload-PAYRATE_APPROVALS" id="reload-PAYRATE_APPROVALS" value="1"/>
                        <div class="card-header" id="pay-rate-approvals">
                            <h4 class="mb-0">
                                <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed"
                                        type="button"
                                        data-toggle="collapse" data-target="#PAYRATE_APPROVALS" aria-expanded="false"
                                        aria-controls="collapseOne">
                                    Pay Rate Approvals <span id="PAYRATE_APPROVALS-counter"></span> <i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>

                        <div id="PAYRATE_APPROVALS" class="collapse " aria-labelledby="dailyactivityone"
                             data-parent="#dailyactivityaccordion">
                            <div class="card-body">
                                <div class="filters-common">
                                    @component('components.datatable-filters',['filters'=>$payrateapprovalfilters,'formname'=>'payrateapprovals'])
                                    @endcomponent
                                </div>
                                <?php
                                echo H::getHelper("Payrate")->getDataTableHtml("PAYRATE APPROVALS") ?>
                            </div>
                        </div>
                    </div>
                    @endcanPermission
                </div>
            </div>
        </div>

        @endcanPermission

        <!-------------VBC----------------->
        <!---temporary hidden from prod---->
        @if (config('vbc.is_available'))
        @canPermission(['hospitalization.index'])
            <div class="card">
            <div class="card-header" id="intensive-case-management-2">
                <h2 class="mb-0">
                    <button class="<?php echo $class?> pull-left btn btn-link btn-block text-left collapsed" type="button"
                            data-toggle="collapse"
                            data-target="#collapseThree-2" aria-expanded="false" aria-controls="collapseThree-2">
                        Intensive Case Management<i class="fa fa-chevron-right"></i>
                    </button>
                </h2>
            </div>

            <div id="collapseThree-2" class="collapse" aria-labelledby="intensive-case-management-2" data-parent="#accordionExample">
                <div class="card-body">
                    @include('vbc.dashboard.hospitalization')

                    @include('vbc.dashboard.careCoordinationActivities')

                    @include('vbc.dashboard.communication')
                </div>
            </div>
        </div>
        @endcanPermission
        @endif
        <!--------------------------------->
        @canPermission(['dashboard.getorientationdata'])
        <div class="card">
            <div class="card-header" id="orientation_coordinator">
                <h2 class="mb-0">
                    <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button"
                            data-toggle="collapse"
                            data-target="#orientation" aria-expanded="false" aria-controls="collapseThree">
                        ORIENTATION COORDINATOR<i class="fa fa-chevron-right"></i>
                    </button>
                    @canPermission(['ipeaListing'])
                    <div class="col-md-2 text-right pull-left"><a href="{{route('ipeaListing')}}"
                                                                  class="dashboard-showall" title="Show All IPEAs">Show
                            All IPEAs</a></div>
                    @endcanPermission
                </h2>
            </div>
            <div id="orientation" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                <div class="card-body">
                    <div class="filters-common">
                        @component('components.datatable-filters',['filters'=>$orientationFilter,'formname'=>'orientationForm'])
                        @endcomponent
                    </div>
                    <div class="card">
                        <input type="hidden" name="reload-ipeas_to_confirm" id="reload-ipeas_to_confirm" value="1"/>
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#ipeas_to_confirm" aria-expanded="false"
                                        aria-controls="collapseOne">
                                    IPEAs TO CONFIRM <span id="ipeas_to_confirm-counter"></span><i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>

                        <div id="ipeas_to_confirm" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#orientation">
                            <div class="card-body">
                                <?php
                                echo H::getHelper("Orientation")->getDataTableHtml("ipeas_to_confirm") ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <input type="hidden" name="reload-todays_ipeas" id="reload-todays_ipeas" value="1"/>
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#todays_ipeas" aria-expanded="false" aria-controls="collapseOne">
                                    TODAY's IPEAS <span id="todays_ipeas-counter"></span><i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>

                        <div id="todays_ipeas" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#orientation">
                            <p style="text-align: left; margin-top: 9px; position: absolute;">* In Home IPEA</p>
                            <div class="card-body">
                                <?php
                                echo H::getHelper("Orientation")->getDataTableHtml("todays_ipeas") ?>
                            </div>
                        </div>
                    </div>


                    <div class="card">
                        <input type="hidden" name="reload-tomorrow_ipeas" id="reload-tomorrow_ipeas" value="1"/>
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#tomorrow_ipeas" aria-expanded="false" aria-controls="collapseOne">
                                    Tomorrows Confirmed IPEAS <span id="tomorrow_ipeas-counter"></span><i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>

                        <div id="tomorrow_ipeas" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#orientation">
                            <div class="card-body">
                                <?php
                                echo H::getHelper("Orientation")->getDataTableHtml("tomorrow_ipeas") ?>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <input type="hidden" name="reload-pending_orientation" id="reload-pending_orientation"
                               value="1"/>
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#pending_orientation" aria-expanded="false"
                                        aria-controls="collapseOne">
                                    PENDING ORIENTATION <span id="pending_orientation-counter"></span><i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>

                        <div id="pending_orientation" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#orientation">
                            <?php
                            $ipeaPendingItems = [];
                            foreach (config('constant')['ipeaPendings'] as $ipeaPendingKey => $ipeaPendingValue) {
                                $ipeaPendingItems[] = ["value" => $ipeaPendingValue, 'text' => $ipeaPendingValue];
                            }
                            $pendingFilter = [
                                [
                                    'label' => 'Pending',
                                    'name' => 'pending',
                                    'type' => 'multiselect',
                                    'source' => $ipeaPendingItems,
                                    'form' => 'orientationForm'
                                ]
                            ];
                            ?>
                            <div class="filters-common">
                                @component('components.datatable-filters',['filters' => $pendingFilter])
                                @endcomponent
                            </div>
                            <div class="card-body">
                                <?php
                                echo H::getHelper("Orientation")->getDataTableHtml("pending_orientation") ?>
                            </div>
                        </div>
                    </div>
                    @include('orientation.renewables')

                    @include('orientation.nursysexception')

                    @include('orientation.statusChangeRequests')

                    @if(LD::checkFeatureFlag('paycom_kantime_exception_bucket'))
                        @include('orientation.paycomKantimeException')
                    @endif
                </div>
            </div>
        </div>
        @endcanPermission

        @canPermission(['dashboard.getSocialMediaDashboard'])
        <div class="card">
            <div class="card-header" id="socialMediaManager">
                <h2 class="mb-0">
                    <button class="col-md-9 pull-left btn btn-link btn-block text-left collapsed" type="button"
                            data-toggle="collapse"
                            data-target="#socialMedia" aria-expanded="false" aria-controls="collapseThree">
                        Social Media Manager<i class="fa fa-chevron-right"></i>
                    </button>
                    <div class="col-md-3 text-right pull-left"><a href="{{route('socialmedia.getAllSocialMedia')}}"
                                                                  class="dashboard-showall"
                                                                  title="Show Social Media Spend Tracker">Show Social
                            Media Spend Tracker</a></div>
                </h2>
            </div>
            <div id="socialMedia" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                <div class="card-body">
                    <div class="card">
                        <input type="hidden" name="reload-social_media_request" id="reload-social_media_request"
                               value="1"/>
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#social_media_request" aria-expanded="false"
                                        aria-controls="collapseOne">
                                    Social Media Requests <span id="social_media_request-counter"></span><i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>
                        <div id="social_media_request" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#socialMedia">
                            <p style="text-align: left;"><u><b>Responsible Party:</b> Social Media Manager</u><br>
                                <b>Description:</b> Social Media Ad Requests submitted by the Recruiting Team for clients currently on the NTH. Each request should be reviewed and ad information documented as requests are fulfilled. If a client is staffed prior to the request being filled, it will automatically remove from this bucket.<br>
                                <b>Timeline:</b> Review daily &mdash; all requests should be updated within 1 business day of the created date. Any record remaining in the bucket after 1 business day from the created date will be highlighted in red.</p>
                            <div class="card-body">
                                <?php
                                echo H::getHelper("SocialMedia")->getDataTableHtml(
                                    "social_media_request",
                                    'Social Media Requests'
                                ) ?>
                            </div>
                        </div>
                    </div>
                    <div class="card">
                        <input type="hidden" name="reload-social_media_active_ads" id="reload-social_media_active_ads"
                               value="1"/>
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#social_media_active_ads" aria-expanded="false"
                                        aria-controls="collapseOne">
                                    Active Ads <span id="social_media_active_ads-counter"></span><i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>
                        <div id="social_media_active_ads" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#socialMedia">
                            <div class="row justify-content-between">
                                <div class="col-md-auto text-left head-title pt-2">
                                    <a href="javascript:void(0)" onclick="showAdsInfoForm(0)"><i
                                            class="fa fa-plus-circle" aria-hidden="true"></i></a>
                                </div>
                                <div class="col-md-auto text-right">
                                    <div class="filters-common">
                                        <?php
                                        $social_media_active_ads = [
                                            [
                                                'label' => 'Show All',
                                                'name' => 'include_completed',
                                                'type' => 'checkbox',
                                                'value' => 0
                                            ]
                                        ];
                                        ?>
                                        @component('components.datatable-filters',['filters' => $social_media_active_ads])
                                        @endcomponent
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php
                                echo H::getHelper("SocialMedia")->getDataTableHtml(
                                    "social_media_active_ads",
                                    'Social Media Requests'
                                ) ?>
                            </div>
                        </div>
                    </div>
                    @include('socialmedia.report')
                </div>
            </div>
        </div>
        @endcanPermission



        @canPermission(['clientcare.getclientcareexceptions'])
        <div class="card">
            <div class="card-header" id="client_care_coordinator">
                <h2 class="mb-0">
                    <button class="col-md-11 pull-left btn btn-link btn-block text-left collapsed" type="button"
                            data-toggle="collapse"
                            data-target="#client_care_childs" aria-expanded="false" aria-controls="collapseThree">
                        CLIENT CARE<i class="fa fa-chevron-right"></i>
                    </button>
                </h2>
            </div>
            <div id="client_care_childs" class="collapse" aria-labelledby="headingThree"
                 data-parent="#accordionExample">
                @include('ccc.coveragetracker')
                @include('ccc.missedvisits')
                <div class="card-body">
                    <div class="card" data-follows-global="true">
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0 d-flex">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#weekly_nth_calls" aria-expanded="false"
                                        aria-controls="collapseOne">
                                    WEEKLY NTH CALLS  <span id="weekly_nth_calls-counter"></span>
                                    @include('account.partials.globalFilterBadge')
                                    <i class="fa fa-chevron-right"></i>
                                </button>
                                <div class="col-md-2 text-right pull-left">
                                    <a href="{{route('weeklyShowAll')}}" class="dashboard-showall"
                                       title="Show Weekly Call">Show Weekly Call</a>
                                </div>
                            </h4>
                        </div>

                        <div id="weekly_nth_calls" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#client_care_childs">
                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care Coordinator</u><br>
                                <b>Description:</b> Call and document all attempts to contact every client on the Need
                                to Hire list using the CCC NTH Call Checklist. This should be a phone call in order to
                                build a relationship, verify all of the information, and prevent future issues.<br>
                                <b>Timeline:</b> Make first attempt on all clients by end of business Wednesday. As you
                                add clients to the NTH, document that as call 1.</p>
                            <div
                                class="filters-common"
                                data-bucket-filter="true"
                                data-global-filter-fields='{"state":"weeklyccstate","region":"weeklyregion","branch":"weeklyccbranch"}'
                                data-triggers-counter-refresh="true"
                            >
                                @component('components.datatable-filters',['filters'=>$weeklyFilter,'formname'=>'weeklyclientcareform'])
                                @endcomponent
                            </div>

                            <div id="weekly_nth_calls_progressbar">
                            </div>
                            <?php
                            // echo H::getHelper('ConstantHelper')->getProgressBar("weekly_nth_calls");?>
                            <div class="card-body">
                                <?php
                                echo H::getHelper("ClientCareCoordinatorException")->getDataTableHtml(
                                    'weekly_nth_calls'
                                ) ?>
                            </div>
                        </div>
                    </div>
                </div>
                @if(LD::checkFeatureFlag('ccc-monthly-calls-bucket'))
                    <div class="card-body">
                        <div class="card">
                            <div class="card-header" id="dailyactivityone">
                                <h4 class="mb-0  d-flex">
                                    <button class="btn btn-link btn-block text-left collapsed" type="button"
                                            data-toggle="collapse"
                                            data-target="#monthly_calls" aria-expanded="false" aria-controls="collapseOne">
                                        MONTHLY CALLS <span id="monthly_calls-counter"></span><i
                                            class="fa fa-chevron-right"></i>
                                    </button>
                                    <div class="col-md-2 text-right pull-left">
                                        <a href="{{route('monthlyShowAll')}}" class="dashboard-showall"
                                           title="Show Monthly Call">Show Monthly Call</a>
                                    </div>
                                </h4>
                            </div>

                            <div id="monthly_calls" class="collapse" aria-labelledby="dailyactivityone"
                                 data-parent="#client_care_childs">
                                <p style="text-align: left;"><u><b>Responsible Party:</b> Client Care Coordinator</u><br>
                                    <b>Description:</b> Call and document all attempts to contact every client at AOC using
                                    the CCC Monthly Call Checklist.. NTH Calls and Monthly Calls can be completed at the
                                    same time by opening a duplicate tab. If a client has more than one service line, such
                                    as CNA and PDN, complete one call but mark as completed for both. This should be a phone
                                    call in order to build a relationship, verify all of the information, and prevent future
                                    issues.<br>
                                    <b>Timeline:</b> Make first attempt on all clients by the 15th of the month, and
                                    complete at 100% by month end.</p>
                                <div class="filters-common">
                                    @component('components.datatable-filters',['filters'=>$monthlyFilter,'formname'=>'monthlyclientcareform'])
                                    @endcomponent

                                    <div id="monthly_calls_progressbar">
                                    </div>
                                </div>
                                <div class="card-body">
                                    <?php
                                    echo H::getHelper("ClientCareCoordinatorException")->getDataTableHtml(
                                        'monthly_calls'
                                    ) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
                @include('ccc.schoolDashboard')
                @if(LD::checkFeatureFlag('ccc-schedules-built-auth'))
                    @include('ccc.schedulesBuiltAuth')
                @endif
                <div class="card-body">

                    <div class="card">
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#client_care_exceptions" aria-expanded="false"
                                        aria-controls="collapseOne">
                                    EXCEPTIONS <span id="ipeas_to_confirm-counter"></span><i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>

                        <div id="client_care_exceptions" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#client_care_childs">
                            <p style="text-align: left;"><u>Exceptions should be worked as they come up; they relate to
                                    data that is inaccurate or discrepant within the app or KanTime.</u></p>
                            <div class="filters-common">
                                @component('components.datatable-filters',['filters'=>$clientCareFilter,'formname'=>'clientcareform'])
                                @endcomponent
                            </div>
                            <div class="card-body">
                                <?php
                                $clientCareException = H::getHelper("ClientCareCoordinatorException")->getBuckets();
                                foreach ($clientCareException as $bucketKey => $bucket) { ?>
                                <div class="card">
                                    <div class="card-header" id="client_care_exceptions-<?php echo $bucketKey?>">
                                        <h4 class="mb-0">
                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                    data-toggle="collapse" data-target="#<?php echo $bucketKey?>"
                                                    aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                    <?php
                                                    echo $bucket['heading'] ?> <span
                                                    id="<?php echo $bucketKey?>-counter"></span> <i
                                                    class="fa fa-chevron-right"></i>
                                            </button>
                                        </h4>
                                    </div>
                                    <div id="<?php echo $bucketKey?>" class="collapse "
                                         aria-labelledby="dailyactivityone" data-parent="#client_care_exceptions">
                                        @if($bucketKey == 'not_meeting_nurses')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client
                                                    Care</u><br>
                                                <b>Description:</b> Clients on the NTH who are Not Meeting Nurses in
                                                KanTime. Confirm that the patient is not meeting nurses in Kantime, and
                                                correct if necessary.<br>
                                                <b>Timeline:</b> As they occur on this list for your branch—review daily
                                                at a minimum.</p>
                                        @elseif($bucketKey == 'the_sale_255_characters')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client
                                                    Care</u><br>
                                                <b>Description:</b> Clients on the NTH who do not have at least 255
                                                characters in "The Sale" field who are not in intake status. This is
                                                where you would type details that are selling points for the client,
                                                such as hobbies, things they enjoy, etc so recruiting can find a good
                                                fit for the family. The goal is to lovingly tell the story in a minimum
                                                of 3 sentences (255 characters) that explain what it's like to work with
                                                the family. It should not include “filler” information to remove it from
                                                this bucket. Every team member should be updating this field, so work
                                                with your CCMs or anyone else who knows the client and family.  It
                                                should never be blank - this is your chance to talk about the positive
                                                items for this family.<br>
                                                <b>Timeline:</b> As they occur on this list for your branch—review daily
                                                at a minimum.</p>
                                        @elseif($bucketKey == 'gender_preference_mismatch_in_kantime')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client
                                                    Care</u><br>
                                                <b>Description:</b> Clients on the NTH who show a gender preference
                                                mismatch in Kantime. Confirm the correct preference and update in
                                                Kantime.<br>
                                                <b>Timeline:</b> As they occur on this list for your branch—review daily
                                                at a minimum.</p>
                                        @elseif($bucketKey == 'no_match_in_kantime')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client
                                                    Care</u><br>
                                                <b>Description:</b> Clients on the NTH who do not have a matching active
                                                account in KanTime. Check to see if the client is still active, and if
                                                non-admitted or discharged, close the NTH ticket.<br>
                                                <b>Timeline:</b> As they occur on this list for your branch—review daily
                                                at a minimum.</p>
                                        @elseif($bucketKey == 'no_hours')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client
                                                    Care</u><br>
                                                <b>Description:</b> Clients without a schedule in KanTime. Confirm the
                                                correct hours and update the schedule.<br>
                                                <b>Timeline:</b> As they occur on this list for your branch—review daily
                                                at a minimum.</p>
                                        @elseif($bucketKey == 'no_payor')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client
                                                    Care</u><br>
                                                <b>Description:</b> Clients on the NTH who do not have a payor assigned
                                                as the terminal payor in Kantime. Confirm the correct payor and update
                                                in Kantime.<br>
                                                <b>Timeline:</b> As they occur on this list for your branch—review daily
                                                at a minimum.</p>
                                        @elseif($bucketKey == 'no_ccc')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client
                                                    Care</u><br>
                                                <b>Description:</b> Clients on the NTH who do not have a CCC assigned in
                                                Kantime. Confirm the correct CCC and update in Kantime.<br>
                                                <b>Timeline:</b> As they occur on this list for your branch—review daily
                                                at a minimum.</p>
                                        @elseif($bucketKey == 'ccc_respite_no_respite_payor')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client
                                                    Care</u><br>
                                                <b>Description:</b> Clients on the NTH who do not have a respite payor
                                                selected but have either PDN/Respite or Respite as the NTH service.
                                                Confirm the correct information and update in Kantime. Timeline: As they
                                                occur on this list for your branch—review daily at a minimum.<br></p>
                                        @elseif($bucketKey == 'staffed_coverage_shifts_not_updated_in_kantime')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Client
                                                    Care</u><br>
                                                <b>Description:</b> Shifts that were marked as staffed with no clinician
                                                in Kantime. Add the nurse to the schedule or remove the hours from the
                                                coverage tracker if cancelled/inaccurate.<br>
                                                <b>Timeline:</b> As they occur on this list for your branch—review daily
                                                at a minimum.</p>
                                        @endif
                                        <div class="card-body">
                                                <?php
                                                echo H::getHelper("ClientCareCoordinatorException")->getDataTableHtml(
                                                    $bucketKey
                                                ) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                } ?>
                            </div>
                        </div>
                    </div>


                </div>
                @include('ccc.clientMatch.accordion')
                @include('ccc.intakeclients')

                @include('ccc.expiringrenewables')

                @if(LD::checkFeatureFlag('clinician-no-longer-on-case-widget'))
                    @include('ccc.clinicianNoLongerOnCase')
                @endif
                @include('ccc.zeroRates')
                @include('afterhours.ahJournal')
                @include('ccc.caseload')

                @if(LD::checkFeatureFlag('active-coverage-bucket'))
                    @include('ccc.activecoverage')
                @endif
            </div>
        </div>
        @endcanPermission



        @canPermission(['triage.getTriageData'])
        <div class="card" data-follows-global="true">
            <div class="card-header" id="client_care_coordinator">
                <h2 class="mb-0">
                    <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button"
                            data-toggle="collapse"
                            data-target="#triage_childs" aria-expanded="false" aria-controls="collapseThree">
                        TRIAGE
                        @include('account.partials.globalFilterBadge')
                        <i class="fa fa-chevron-right"></i>
                    </button>
                    <div class="col-md-2 text-right pull-left"><a href="{{route('triage.getallcalls')}}"
                                                                  class="dashboard-showall"
                                                                  title="Show All Triage Calls">Show All Triage
                            Calls</a></div>
                </h2>
            </div>
            <div id="triage_childs" class="collapse" aria-labelledby="headingThree" data-parent="#accordionExample">
                <div class="card-body">

                    <div class="card">
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#upcoming_calls" aria-expanded="false" aria-controls="collapseOne">
                                    UPCOMING CALLS <span id="upcoming-calls-counter"></span><i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>

                        <div id="upcoming_calls" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#triage_childs">
                            <div
                                class="filters-common"
                                data-bucket-filter="true"
                                data-global-filter-fields='{"state":"triagestate","region":"","branch":"triagebranch"}'
                                data-triggers-counter-refresh="true"
                            >
                                @component('components.datatable-filters',['filters'=>$triagefilter,'formname'=>'triageform'])
                                @endcomponent
                            </div>
                            <div class="card-body">
                                <?php
                                $triage = H::getHelper("Triage")->getBuckets();
                                foreach ($triage as $bucketKey => $bucket) { ?>
                                <div class="card">
                                    <div class="card-header" id="triage-<?php echo $bucketKey?>">
                                        <h4 class="mb-0">
                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                    data-toggle="collapse" data-target="#<?php echo $bucketKey?>"
                                                    aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                    <?php
                                                    echo $bucket['heading'] ?> <span
                                                    id="<?php echo $bucketKey?>-counter"></span> <i
                                                    class="fa fa-chevron-right"></i>
                                            </button>
                                        </h4>
                                    </div>
                                    <div id="<?php echo $bucketKey?>" class="collapse "
                                         aria-labelledby="dailyactivityone" data-parent="#upcoming_calls">
                                        @if($bucketKey == 'triage_24h_follow_up')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Triage Team Member</u><br>
                                                <b>Description:</b> Call and document all attempts to contact triage
                                                patients 24 hours after their initial triage call. This should be a
                                                phone call in order to build a relationship, verify all of the
                                                information, and prevent future issues.<br>
                                                <b>Timeline:</b> Make attempt on all calls daily by 4 p.m.</p>
                                        @elseif($bucketKey == 'triage_48h_follow_up')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Triage Team Member</u><br>
                                                <b>Description:</b> Call and document all attempts to contact triage
                                                patients 48 hours after their initial triage call. This should be a
                                                phone call in order to build a relationship, verify all of the
                                                information, and prevent future issues.<br>
                                                <b>Timeline:</b> Make one call attempt daily, up to 3 attempts. All
                                                calls should be made by 4 p.m.</p>
                                        @elseif($bucketKey == 'triage_7d_follow_up')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Triage Team Member</u><br>
                                                <b>Description:</b> Call and document all attempts to contact triage
                                                patients 7 days after their initial triage call. This should be a phone
                                                call in order to build a relationship, verify all of the information,
                                                and prevent future issues.<br>
                                                <b>Timeline:</b> Make one call attempt daily, up to 3 attempts. All
                                                calls should be made by 4 p.m.</p>
                                        @elseif($bucketKey == 'triage_30d_follow_up')
                                            <p style="text-align: left;"><u><b>Responsible Party:</b> Triage Team Member</u><br>
                                                <b>Description:</b> Call and document all attempts to contact triage
                                                patients 30 days after their initial triage call. This should be a phone
                                                call in order to build a relationship, verify all of the information,
                                                and prevent future issues.<br>
                                                <b>Timeline:</b> Make one call attempt daily, up to 3 attempts. All
                                                calls should be made by 4 p.m.</p>
                                        @endif
                                        <div class="card-body">

                                                <?php
                                                echo H::getHelper("Triage")->getDataTableHtml($bucketKey) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                } ?>
                            </div>
                        </div>
                    </div>

                    {{-- PD-4057: ED Visit buckets, below Upcoming Calls. VBC module, so hidden from prod like the ICM section. --}}
                    {{-- All ED Visits functionality (both buckets, the tracker modal and its tabs) needs "Manage ED Visits". --}}
                    @if (config('vbc.is_available'))
                    @canPermission(['ed-visits.manage'])
                        <script>window.vbcCanManageEdVisits = true;</script>
                        @include('vbc.dashboard.edVisits')
                        @include('vbc.dashboard.triageCareCoordinationActivities')
                    @endcanPermission
                    @endif
                </div>
            </div>
        </div>
        @endcanPermission

        <!---temporary hidden from prod---->
        @if (config('vbc.is_available'))
        @canPermission(['immunization.index'])
            <div class="card vbc-bucket-card">
                <div class="card-header" id="immunizations-heading">
                    <h2 class="mb-0 d-flex align-items-center">
                        <button class="col-md-10 btn btn-link btn-block text-left collapsed" type="button"
                                data-toggle="collapse"
                                data-target="#immunizations-collapse" aria-expanded="false" aria-controls="immunizations-collapse">
                            Immunizations<i class="fa fa-chevron-right"></i>
                        </button>
                        <div class="col-md-2 text-right">
                            <a href="{{ route('immunization.index') }}" class="dashboard-showall"
                               title="Show All Immunizations">Show All Immunizations</a>
                        </div>
                    </h2>
                </div>

                <div id="immunizations-collapse" class="collapse" aria-labelledby="immunizations-heading" data-parent="#accordionExample">
                    <div class="card-body">
                        <div class="v-app">
                            <div id="vbc-bucket-immunization"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endcanPermission
        @endif

        @canPermission(['dashboard.getclinicalcase'])
            <?php
                // Therapist Applications (Clinical Care Manager) shares the
                // exact 'name' values (default/jobvitestate/branch/region)
                // with #dashboardjobvite and #dashboardjobvitetherapy on this
                // same page, which produces duplicate DOM ids. Give this
                // instance unique ids (name stays the same, so form
                // serialization/backend filtering are unaffected) so
                // page-wide id-based jQuery selectors elsewhere don't pick
                // up or reset the wrong instance.
                $ccmJobviteTherapyFilters = array_map(function ($filter) {
                    if (in_array($filter['name'], ['default', 'jobvitestate', 'branch', 'region'], true)) {
                        $filter['id'] = 'ccm_' . $filter['name'];
                    }
                    return $filter;
                }, $jobviteTherapyfilters);
            ?>
            @include('ccr.ccmdashboard', ['filters' => $ccmJobviteTherapyFilters])
        @endcanPermission


        @canPermission(['intake.getintakeexceptions'])
        <div class="card" data-follows-global="true">
            <div class="card-header" id="intake_coordinator">
                <h2 class="mb-0">
                    <button class="col-md-10 pull-left btn btn-link btn-block text-left collapsed" type="button"
                            data-toggle="collapse"
                            data-target="#intake_coordinator_childs" aria-expanded="false"
                            aria-controls="collapseThree">
                        Intake Coordinator
                        @include('account.partials.globalFilterBadge')
                        <i class="fa fa-chevron-right"></i>
                    </button>
                    <div class="col-md-2 text-right pull-left">
                        <a
                            href="{{route('intake.list')}}"
                            class="dashboard-showall"
                            title="Show All Intake">Show All Intake</a>
                    </div>
                </h2>
            </div>
            <div id="intake_coordinator_childs" class="collapse" aria-labelledby="headingThree"
                 data-parent="#accordionExample">
                <div class="card-body">
                    @include('intake.staffTransfers')
                    <div class="card">
                        <div class="card-header" id="dailyactivityone">
                            <h4 class="mb-0">
                                <button class="btn btn-link btn-block text-left collapsed" type="button"
                                        data-toggle="collapse"
                                        data-target="#intake_coordinator_exceptions" aria-expanded="false"
                                        aria-controls="collapseOne">
                                    EXCEPTIONS <span id="ipeas_to_confirm-counter"></span><i
                                        class="fa fa-chevron-right"></i>
                                </button>
                            </h4>
                        </div>

                        <div id="intake_coordinator_exceptions" class="collapse" aria-labelledby="dailyactivityone"
                             data-parent="#intake_coordinator_childs">
                            <div
                                class="filters-common"
                                data-bucket-filter="true"
                                data-global-filter-fields='{"state":"intakeexceptionstate","region":"intakeexceptionregion","branch":"intakeexceptionbranch"}'
                                data-triggers-counter-refresh="true"
                            >
                                @component('components.datatable-filters',['filters'=>$intakeFilter,'formname'=>'intakeexceptionform'])
                                @endcomponent
                            </div>
                            <div class="card-body">
                                <?php
                                $IntakeException = H::getHelper("IntakeCoordinatorException")->getBuckets();
                                foreach ($IntakeException as $bucketKey => $bucket) { ?>
                                <div class="card" data-follows-global="true">
                                    <div class="card-header" id="intake_coordinator_exceptions-<?php echo $bucketKey?>">
                                        <h4 class="mb-0">
                                            <button class="btn btn-link btn-block text-left collapsed" type="button"
                                                    data-toggle="collapse" data-target="#<?php echo $bucketKey?>"
                                                    aria-expanded="false"
                                                    aria-controls="collapseOne">
                                                    <?php
                                                    echo $bucket['heading'] ?> <span
                                                    id="intake-<?php echo $bucketKey?>-counter"></span> <i
                                                    class="fa fa-chevron-right"></i>
                                            </button>
                                        </h4>
                                    </div>
                                    <div id="<?php echo $bucketKey?>" class="collapse "
                                         aria-labelledby="dailyactivityone"
                                         data-parent="#intake_coordinator_exceptions">
                                            <?php
                                        if ($bucketKey == 'intake_unassigned_care_team') { ?>
                                        <p style="text-align: left;"><u><b>Responsible Party:</b> Branch Leader</u><br>
                                            <b>Description:</b> Intake clients who do not have assigned care team
                                            members.
                                            To resolve this exception, you need to update the care team member in the
                                            KanTime profile.<br>
                                            <b>Timeline:</b> Care team assignments should be completed within 24 hours.
                                        </p> <?php
                                             } ?>

                                        <div class="card-body">
                                                <?php
                                                echo H::getHelper("IntakeCoordinatorException")->getDataTableHtml(
                                                    $bucketKey
                                                ) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php
                                } ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endcanPermission



        @canPermission(['dashboard.evaluations'])
        @include('officeEvaluation.dashboard')
        @endcanPermission

        <!---Advocacy--->
        @canPermission(['advocacy.statuschangerequest'])
        @include('advocacy.dashboard')
        @endcanPermission

        @canPermission(['dashboard.getSchoolSetUpsRequest'])
        @include('schoolSetUp.schooldashboard')
        @endcanPermission

        <!---supply-requests--->
        @canPermission(['supplies.getSupplyRequestsDashboard'])
        @include('supplies.dashboard')
        @endcanPermission

        <!---Billing Dashboard--->
        @canPermission(['dashboard.getBillingData'])
        @include('billing.billingDashboard')
        @endcanPermission

        <!---Conversations should stay at the bottom unless specified-->
        @canPermission(['dashboard.getMessageCenterList'])
        @include('messageWidget.dashboard_conversation')
        @endcanPermission

    </div>

    <script>
        $(document).ready(function () {
            let _token = $('meta[name="csrf-token"]').attr('content');

            function checkFirstAdvantageErrors(id, type) {
                $.ajax({
                    url: "{{route('first-advantage.get-errors')}}",
                    type: 'POST',
                    data: {
                        id: id,
                        type: type,
                        _token: _token
                    },
                    success: function (data) {
                        if (data) {
                            $('#render-background-check-error').html(data);
                        }
                        if (data.length === 0) {
                            $('.retry-background-check-label').remove();
                        }
                    }
                });
            }

            function getStatus(id, t) {
                $.ajax({
                    url: "{{route('first-advantage.get-statuses')}}",
                    type: 'POST',
                    data: {
                        id: id,
                        _token: _token,
                        type: t,
                    },
                    success: function (data) {
                        if (data) {
                            $("#fa-application-status").text(data['application_status'] || '--');
                            $("#fa-order-status").text(data['status'] || '--');
                            $("#fa-result-status").text(data['result_status'] || '--');
                            $("#last-attempt-id").html(data['updated_at']);
                        }
                    }
                });
            }

            let interval;

            function isFAModal(modalId) {
                if (modalId === 'updateNurseIpeaInfoModal' || modalId === 'neoIpeaTemplateModal') {
                    return true
                }
                return false;
            }

            $(document).on("show.bs.modal", ".modal", function (e) {
                let modalId = $(e.target).attr('id');
                let id = $(document).find('#fa-application-status').data('id');
                let type = $('#fa-application-status').data('type');
                console.log(type);
                console.log(id);
                console.log(modalId);
                if (isFAModal(modalId)) {
                    interval = setInterval(function () {
                        if (id !== 0) {
                            getStatus(id, type);
                            checkFirstAdvantageErrors(id, type);
                        }
                    }, 10000);
                }
            });

            $(document).on("hidden.bs.modal", ".modal", function (e) {
                let modalId = $(e.target).attr('id');
                if (isFAModal(modalId)) {
                    clearInterval(interval);
                }
            });

            $(document).on('click', 'a.retry-background-check', function (e) {
                e.preventDefault();
                let recruitingScheduleId = $(this).data('id');
                let type = $('#fa-application-status').data('type');
                $.ajax({
                    url: "{{route('first-advantage.retry')}}",
                    type: 'POST',
                    data: {
                        id: recruitingScheduleId,
                        type: type,
                        _token: _token
                    },
                    success: function (data) {
                        $('#render-background-check-error').html(data);
                        getStatus(type);
                    }
                });
            });
        });
    </script>
    <script>

        $(document).ready(function () {
            $('#accordionExample').on('shown.bs.collapse', function (e) {
                var id = $(e.target).attr("id");
                if ($("#reload-" + id).val() == 1) {
                    $("#reload-" + id).val(0);
                    window[$(e.target).attr("id")]();
                    if ($(e.target).attr("id") == 'IPEA') {
                        reloadIPEA();
                    }
                }
                if (id == 'conversations_childs') {
                    $('#message_center_conversations').collapse('show');
                }
            });
            $(document).on("click", '#default', function () {
                $("#aoc_facility_id").multiselect("clearSelection");
                $("#Recruiter").multiselect("clearSelection");
                $("#state").multiselect("clearSelection");

            });
            $(document).on("change", '#state', function () {
                $('#default').prop('checked', false);
                $("#aoc_facility_id").multiselect("clearSelection");
                $("#Recruiter").multiselect("clearSelection");

                var state = $(this).val().join(",");

                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('getstatebranch') }}",
                    type: "GET",
                    data: {
                        state: state,
                        _token: _token
                    },
                    success: function (response) {
                        $('#aoc_facility_id').empty();
                        $.each(response, function (index, value) {
                            $("#aoc_facility_id").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                        });
                        $("#aoc_facility_id").multiselect("rebuild");
                    },
                });

            });
            $(document).on("change", '#aoc_facility_id', function () {
                $('#default').prop('checked', false);
                $("#Recruiter").multiselect("clearSelection");

            });

            $(document).on("change", '#nth-state', function () {
                $("#nth-aoc_facility_id").multiselect("clearSelection");
                $("#nth-Recruiter").multiselect("clearSelection");

                var state = $(this).val().join(",");


                if (state.indexOf('TX') !== -1) {
                    $('select[name="nth-region"]').multiselect('enable');
                } else {
                    $('select[name="nth-region"]').multiselect('disable');
                }

                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('getstatebranch') }}",
                    type: "GET",
                    data: {
                        state: state,
                        _token: _token
                    },
                    success: function (response) {
                        $('#nth-aoc_facility_id').empty();
                        $.each(response, function (index, value) {
                            $("#nth-aoc_facility_id").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                        });
                        $("#nth-aoc_facility_id").multiselect("rebuild");
                    },
                });
            });
            $(document).on("change", '#nth-aoc_facility_id', function () {
                $("#nth-Recruiter").multiselect("clearSelection");
            });

            $(document).on("change", '#ocstate', function () {

                var state = $(this).val().join(",");

                let _token = $('meta[name="csrf-token"]').attr('content');

                if (state.indexOf('Texas') !== -1) {
                    $('select[name="ocregion"]').multiselect('enable');
                } else {
                    $('select[name="ocregion"]').multiselect('disable');
                }

                $.ajax({
                    url: "{{ route('getocstatebranch') }}",
                    type: "GET",
                    data: {
                        state: state,
                        _token: _token
                    },
                    success: function (response) {
                        $('#ipea_location_id').empty();
                        $.each(response, function (index, value) {
                            $("#ipea_location_id").append('<option value="' + value.id + '">' + value.branch + '</option>')
                        });
                        $("#ipea_location_id").multiselect("rebuild");
                    },
                });

            });


            $(document).on("click", '#ocdefault', function () {
                if ($(this).is(":checked")) {
                    $("#ocstate").multiselect("clearSelection");
                    $("#ipea_location_id").multiselect("clearSelection");
                    $("#oc").multiselect("clearSelection");
                }
            });
            $(document).on("change", '#ocstate,#ipea_location_id,#oc', function () {
                $('#ocdefault').prop('checked', false);
            });

            $(document).on("change", '#ccstate', function () {
                var state = $(this).val().join(",");
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('getstatebranch') }}",
                    type: "GET",
                    data: {
                        state: state,
                        _token: _token
                    },
                    success: function (response) {
                        $('#ccbranch').empty();
                        $.each(response, function (index, value) {
                            $("#ccbranch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                        });
                        $("#ccbranch").multiselect("rebuild");
                    },
                });
            });

            $(document).on("change", '#intakeexceptionstate', function () {
                var state = $(this).val().join(",");
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('getstatebranch') }}",
                    type: "GET",
                    data: {
                        state: state,
                        _token: _token
                    },
                    success: function (response) {
                        $('#intakeexceptionbranch').empty();
                        $.each(response, function (index, value) {
                            $("#intakeexceptionbranch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                        });
                        $("#intakeexceptionbranch").multiselect("rebuild");
                    },
                });

                if (state.indexOf('TX') !== -1) {
                    $('#intakeexceptionregion').multiselect('enable');
                } else {
                    $('#intakeexceptionregion').multiselect('disable');
                }
            });

            $(document).on("change", '#todayipeaform #ipeastate', function () {
                var state = $(this).val().join(",");
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('getstatebranch') }}",
                    type: "GET",
                    data: {
                        state: state,
                        _token: _token
                    },
                    success: function (response) {
                        $('#todayipeaform #ipeabranch').empty();
                        $.each(response, function (index, value) {
                            $("#todayipeaform #ipeabranch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                        });
                        $("#todayipeaform #ipeabranch").multiselect("rebuild");
                    },

                });

                if (state.indexOf('TX') !== -1) {
                    $('#todayipeaform #ipearegion').multiselect('enable');
                } else {
                    $('#todayipeaform #ipearegion').multiselect('disable');
                }
            });

            $(document).on("change", '#Recruiter', function () {
                $('#default').prop('checked', false);
                $("#aoc_facility_id").multiselect("clearSelection");
                $("#state").multiselect("clearSelection");
            });

            const bucketArray = ["NEED TO REVIEW NEW CLIENTS", "NEED TO BE SCHEDULED BY RA", "IPEA", "CCRS", "PEPI", "WEPI", "TRAINING SHIFT", "PENDING CANDIDATE STARTS", "CANDIDATE STARTS", "PRN", "SCHOOL SETS UP", 'NO CANDIDATE MATCH', 'NO PROVIDER MATCH', 'EXCEPTION'];

            $(document).on('submit', '#dashboardactivity', function (e) {
                e.preventDefault();
                var reloadCountData = window.GlobalLocationFilter
                    ? window.GlobalLocationFilter.buildReloadFilters('#dashboardactivity')
                    : { filters: $("#dashboardactivity").serializeArray() };
                for (var i = 0; i < bucketArray.length; i++) {
                    var tempObject = bucketArray[i].replace(/ /g, "_");
                    if ($("#reload-" + tempObject).val() == 1) {
                        let _token = $('meta[name="csrf-token"]').attr('content');
                        $.ajax({
                            url: "{{ route('reloadCount') }}?type=" + bucketArray[i],
                            type: "POST",
                            data: {
                                filters: reloadCountData.filters,
                                _token: _token
                            },
                            success: function (response) {
                                var jsonObject = JSON.parse(response);
                                var type = jsonObject.type;
                                var idObject = type.replace(/ /g, "_");
                                $("#" + idObject + '-counter').html(jsonObject.counter);
                            },
                        });
                    } else {
                        $("#dataTable-" + tempObject).DataTable().ajax.reload();
                    }
                }

            });
            $("#dashboardactivity").trigger("submit");
            const orientationBucket = ["ipeas_to_confirm", "todays_ipeas", "tomorrow_ipeas", "pending_orientation", 'badge_request'];
            $(document).on('submit', '#orientationForm', function (e) {
                e.preventDefault();
                var serializeData = $("#orientationForm").serializeArray();
                for (var i = 0; i < orientationBucket.length; i++) {
                    var tempObject = orientationBucket[i].replace(/ /g, "_");
                    if ($("#reload-" + tempObject).val() == 1) {
                        let _token = $('meta[name="csrf-token"]').attr('content');
                        $.ajax({
                            url: "{{ route('reloadCountOrientation') }}?type=" + orientationBucket[i],
                            type: "POST",
                            data: {
                                filters: serializeData,
                                _token: _token
                            },
                            success: function (response) {
                                var jsonObject = JSON.parse(response);
                                var type = jsonObject.type;
                                var idObject = type.replace(/ /g, "_");
                                $("#" + idObject + '-counter').html(jsonObject.counter);
                            },
                        });
                    } else {
                        $("#dataTable-orientation-" + tempObject).DataTable().ajax.reload();
                    }
                }

            });
            $("#orientationForm").trigger("submit");
            const jobviteBucket = ["NEW","recruiter_phone_screen","jpet",'pending_graduation_followup'];
            $(document).on('submit', '#dashboardjobvite', function (e) {
                e.preventDefault();
                var serializeJobViteData = $("#dashboardjobvite").serializeArray();
                for (var i = 0; i < jobviteBucket.length; i++) {
                    var tempObject = jobviteBucket[i].replace(/ /g, "_");
                    if (jobviteBucket[i] == 'jpet') {
                        var serializeJobViteData = $("#dashboardjobvitepet, #dashboardjobvite").serializeArray();
                    }
                    //if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('reloadCountJobvite') }}?type=" + jobviteBucket[i],
                        type: "POST",
                        data: {
                            filters: serializeJobViteData,
                            _token: _token
                        },
                        success: function (response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g, "_");
                            $("#" + idObject + '-counter').html(jsonObject.counter);
                        },
                    });
                    //}
                    if ($.fn.DataTable.isDataTable('#dataTable-jobvite-' + tempObject)) {
                        $('#dataTable-jobvite-' + tempObject).DataTable().ajax.reload();
                    }
                }
            });
            $("#dashboardjobvite").trigger("submit");



            const jobviteBucketTherapy = ['NEW_therapy', 'PET_therapy', 'scheduled_recruiter_phone_screen_therapy', 'recruiter_phone_screen_therapy', 'scheduled_clinical_interview_therapy', 'rescheduled_clinical_interview_therapy', 'clinical_interview_therapy', 'offer_extended_therapy', 'pending_graduation_follow_up_therapy'];
            $(document).on('submit', '#dashboardjobvitetherapy', function (e) {
                e.preventDefault();
                var serializeJobViteData = $("#dashboardjobvitetherapy").serializeArray();
                for (var i = 0; i < jobviteBucketTherapy.length; i++) {
                    var tempObject = jobviteBucketTherapy[i].replace(/ /g, "_");
                    if (jobviteBucketTherapy[i] == 'jpet') {
                        var serializeJobViteData = $("#dashboardjobvitepet, #dashboardjobvitetherapy").serializeArray();
                    }
                    //if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('reloadCountJobvite') }}?type=" + jobviteBucketTherapy[i],
                        type: "POST",
                        data: {
                            filters: serializeJobViteData,
                            _token: _token
                        },
                        success: function (response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g, "_");
                            $("#" + idObject + '-counter').html(jsonObject.counter);
                        },
                    });
                    //}
                    if ($.fn.DataTable.isDataTable('#dataTable-jobvite-' + tempObject)) {
                        $('#dataTable-jobvite-' + tempObject).DataTable().ajax.reload();
                    }
                }
            });
            $("#dashboardjobvitetherapy").trigger("submit");






            $(document).on('submit', '#dashboardjobvitepet', function (e) {
                e.preventDefault();
                var serializeJobVitePetData = $("#dashboardjobvitepet, #dashboardjobvite").serializeArray();
                for (var i = 0; i < jobviteBucket.length; i++) {
                    var tempObject = jobviteBucket[i].replace(/ /g, "_");
                    //if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('reloadCountJobvite') }}?type=jpet",
                        type: "POST",
                        data: {
                            filters: serializeJobVitePetData,
                            _token: _token
                        },
                        success: function (response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g, "_");
                            $("#" + idObject + '-counter').html(jsonObject.counter);
                        },
                    });
                    //}
                    if ($.fn.DataTable.isDataTable('#dataTable-jobvite-' + tempObject)) {
                        $('#dataTable-jobvite-' + tempObject).DataTable().ajax.reload();
                    }
                }
            });
            $("#dashboardjobvitepet").trigger("submit");
            const nthBucket = JSON.parse('<?php
                                          echo json_encode(
                                              array_keys(H::getHelper("NTHChecklist")->getBuckets())
                                          ); ?>');
            console.log(nthBucket, 'nthBucket');
            $(document).on('submit', '#nthChecklistFiltersDashboard', function (e) {
                e.preventDefault();
                var serializeJobViteData = $("#nthChecklistFiltersDashboard").serializeArray();
                for (var i = 0; i < nthBucket.length; i++) {
                    var tempObject = nthBucket[i].replace(/ /g, "_");
                    //if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('reloadCountNTH') }}?type=" + nthBucket[i],
                        type: "POST",
                        data: {
                            filters: serializeJobViteData,
                            _token: _token
                        },
                        success: function (response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g, "_");
                            $("#" + idObject + '-counter').html(jsonObject.counter);
                        },
                    });
                    //}
                }
                $('#nthchecklist-child .dataTable').DataTable().ajax.reload();
            });
            $("#nthChecklistFiltersDashboard").trigger("submit");

        });
        @if(LD::checkFeatureFlag('make-exception-bucket-filter-multiselect'))
        $(document).on("change", '#recruiter-state', function () {
            $('#default').prop('checked', false);
            var state = $(this).val();
            if (state.indexOf('TX') !== -1) {
                $('#recruitingexception #recruitingregion').multiselect('enable');
            } else {
                $('#recruitingexception #recruitingregion').multiselect('disable');
            }
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('getstatebranch') }}",
                type: "GET",
                data: {
                    state: state,
                    _token: _token
                },
                success: function (response) {
                    $('#recruiter-aocfacility_id').empty();
                    $.each(response, function (index, value) {
                        $("#recruiter-aocfacility_id").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                    });
                    $("#recruiter-aocfacility_id").multiselect("rebuild");
                },
            });

        });
        @else
        $(document).on("change", '#recruiter-state', function () {
            $('#default').prop('checked', false);
            var state = $(this).val();
            if (state.indexOf('TX') !== -1) {
                $('#recruitingexception #recruitingregion').multiselect('enable');
            } else {
                $('#recruitingexception #recruitingregion').multiselect('disable');
            }
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('getstatebranch') }}",
                type: "GET",
                data: {
                    state: state,
                    _token: _token
                },
                success: function (response) {
                    $('#recruiter-aoc_facility_id').empty();
                    $("#recruiter-aoc_facility_id").append('<option value="">Select Branch</option>');
                    $.each(response, function (index, value) {
                        $("#recruiter-aoc_facility_id").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                    });
                },
            });

        });
        @endif
        const bucketArrayException = ['nth_clients_with_no_activity', 'nth_clients_with_no_recruiter', 'nth_active_tracker_disabled_recruiter', 'mismatched_first_shift_confirmed'];
        $(document).on('submit', '#recruitingexception', function (e) {
            e.preventDefault();
            var serializeData = $("#recruitingexception").serializeArray();
            for (var i = 0; i < bucketArrayException.length; i++) {
                var tempObject = bucketArrayException[i].replace(/ /g, "_");
                if ($("#reload-" + tempObject).val() == 1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('recruitingexceptioncount') }}?type=" + bucketArrayException[i],
                        type: "POST",
                        data: {
                            filters: serializeData,
                            _token: _token
                        },
                        success: function (response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g, "_");
                            $("#" + idObject + '-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                    $("#dataTable-recruiting-exception-" + tempObject).DataTable().ajax.reload();
                }
            }

        });

        const IntakeException = JSON.parse('<?php
                                            echo json_encode(
                                                array_keys(H::getHelper("IntakeCoordinatorException")->getBuckets())
                                            ); ?>');
        $(document).on('submit', '#intakeexceptionform', function (e) {
            e.preventDefault();
            var serializeData = $("#intakeexceptionform").serializeArray();
            for (var i = 0; i < IntakeException.length; i++) {
                var tempObject = IntakeException[i].replace(/ /g, "_");
                if ($("#reload-" + tempObject).val() == 1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('intake.reloadcountintakeexceptions') }}?type=" + IntakeException[i],
                        type: "POST",
                        data: {
                            filters: serializeData,
                            _token: _token
                        },
                        success: function (response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g, "_");
                            $("#intake-" + idObject + '-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                    $("#dataTable-intake-exception-" + tempObject).DataTable().ajax.reload();
                }
            }

        });
        $("#intakeexceptionform").trigger("submit");


        const ccBucketArrayException = ['not_meeting_nurses', 'the_sale_255_characters', 'gender_preference_mismatch_in_kantime', 'no_match_in_kantime', 'no_hours', 'no_payor', 'intake_no_intake_coordinator', 'no_ccc', 'ccc_respite_no_respite_payor', 'staffed_coverage_shifts_not_updated_in_kantime'];
        @if (LD::checkFeatureFlag('add-new-widget-to-client-care-exception'))
        ccBucketArrayException.push('nth_hours_exceed_authorized_hours');
        @endif
        @if (LD::checkFeatureFlag('ccc-no-services-needed-widget-exception-bucket'))
        ccBucketArrayException.push('no_services_needed');
        @endif
        ccBucketArrayException.push('coverage_shift_without_kantime_clinician');
        ccBucketArrayException.push('ccc_intake_coverage_shift_covered_by_perm_nurse');
        ccBucketArrayException.push('ccc_intake_coverage_shift_before_goal_soc_date');
        @if (LD::checkFeatureFlag('mismatched-clients-bucket'))
        ccBucketArrayException.push('clients_without_kantime_match');
        @endif
        @if (LD::checkFeatureFlag('active-coverage-exception'))
        ccBucketArrayException.push('active_coverage_exception');
        @endif
        $(document).on('submit', '#clientcareform', function (e) {
            e.preventDefault();
            var serializeData = $("#clientcareform").serializeArray();
            for (var i = 0; i < ccBucketArrayException.length; i++) {
                var tempObject = ccBucketArrayException[i].replace(/ /g, "_");
                if ($("#reload-" + tempObject).val() == 1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('clientcare.reloadcountclientcareexceptions') }}?type=" + ccBucketArrayException[i],
                        type: "POST",
                        data: {
                            filters: serializeData,
                            _token: _token
                        },
                        success: function (response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g, "_");
                            $("#" + idObject + '-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                    $("#dataTable-clientcare-exception-" + tempObject).DataTable().ajax.reload();
                }
            }

        });
        $("#clientcareform").trigger("submit");

        $(document).on('submit', '#pendingcandidatestarts', function (e) {
            e.preventDefault();
            $("#dataTable-PENDING_CANDIDATE_STARTS").DataTable().ajax.reload();
        });
        $(document).on('submit', '#daily_activity_ipea', function (e) {
            e.preventDefault();
            $("#dataTable-IPEA").DataTable().ajax.reload();
        });
        $(document).on('submit', '#daily_activity_pepi', function (e) {
            e.preventDefault();
            $("#dataTable-PEPI").DataTable().ajax.reload();
        });
        $(document).on('submit', '#daily_activity_wepi', function (e) {
            e.preventDefault();
            $("#dataTable-WEPI").DataTable().ajax.reload();
        });
        $(document).on('submit', '#daily_activity_trainig', function (e) {
            e.preventDefault();
            $("#dataTable-TRAINING_SHIFT").DataTable().ajax.reload();
        });
        const triageBucket = ['triage_24h_follow_up', 'triage_48h_follow_up', 'triage_7d_follow_up', 'triage_30d_follow_up'];
        $(document).on('submit', '#triageform', function (e) {
            e.preventDefault();
            var serializeData = $("#triageform").serializeArray();
            for (var i = 0; i < triageBucket.length; i++) {
                var tempObject = triageBucket[i].replace(/ /g, "_");
                if ($("#reload-" + tempObject).val() == 1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('triage.reloadTriageData') }}?type=" + triageBucket[i],
                        type: "POST",
                        data: {
                            _token: _token,
                            filters: serializeData,
                        },
                        success: function (response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g, "_");
                            $("#" + idObject + '-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                    $("#dataTable-triage-" + tempObject).DataTable().ajax.reload();
                }
            }

        });
        $("#triageform").trigger("submit");

        $(document).on("change", '#triagestate', function () {
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('getstatebranch') }}",
                type: "GET",
                data: {
                    state: state,
                    _token: _token
                },
                success: function (response) {
                    $('#triagebranch').empty();
                    $.each(response, function (index, value) {
                        $("#triagebranch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                    });
                    $("#triagebranch").multiselect("rebuild");
                },
            });
        });

        $('#jobvitestate').change(function () {
            if ($(this).val().indexOf('Texas') !== -1) {
                $('#dashboardjobvite select[name="region"]').multiselect('enable');
            } else {
                $('#dashboardjobvite select[name="region"]').multiselect('disable');
            }
        })

        $('#state').change(function () {
            if ($(this).val().indexOf('TX') !== -1) {
                $('#dashboardactivity select[name="region"]').multiselect('enable');
            } else {
                $('#dashboardactivity select[name="region"]').multiselect('disable');
            }
        })
        $('.clearall').on('click', function () {
            $('#dashboardactivity select[name="region"]').multiselect('disable');
        });

        $('#ccstate').change(function () {
            if ($(this).val().indexOf('TX') !== -1) {
                $('#ccregion').multiselect('enable');
            } else {
                $('#ccregion').multiselect('disable');
            }
        })
        $('#clientcareform .clearall').on('click', function () {
            $('#ccregion').multiselect('disable');
        });


        $('#intakeexceptionform .clearall').on('click', function () {
            $('#intakeregion').multiselect('disable');
        });

        const weeklyBucket = ['weekly_nth_calls'];
        $(document).on('submit', '#weeklyclientcareform', function (e) {
            e.preventDefault();
            var serializeData = $("#weeklyclientcareform").serializeArray();
            for (var i = 0; i < weeklyBucket.length; i++) {
                var tempObject = weeklyBucket[i].replace(/ /g, "_");
                if ($("#reload-" + tempObject).val() == 1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('clientcare.reloadcountclientcareexceptions') }}?type=" + weeklyBucket[i],
                        type: "POST",
                        data: {
                            filters: serializeData,
                            _token: _token
                        },
                        success: function (response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g, "_");
                            $("#" + idObject + '-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                    $("#dataTable-clientcare-exception-" + tempObject).DataTable().ajax.reload();
                }
            }

        });
        $("#weeklyclientcareform").trigger("submit");

        $(document).on("change", '#weeklyccstate', function () {
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');
            if (state.indexOf('TX') !== -1) {
                $('#weeklyregion').multiselect('enable');
            } else {
                $('#weeklyregion').multiselect('disable');
            }
            $.ajax({
                url: "{{ route('getstatebranch') }}",
                type: "GET",
                data: {
                    state: state,
                    _token: _token
                },
                success: function (response) {
                    $('#weeklyccbranch').empty();
                    $.each(response, function (index, value) {
                        $("#weeklyccbranch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                    });
                    $("#weeklyccbranch").multiselect("rebuild");
                },
            });
        });


        @if(LD::checkFeatureFlag('ccc-monthly-calls-bucket'))
            const monthlyBucket = ['monthly_calls'];

            $(document).on('submit', '#monthlyclientcareform', function (e) {
                e.preventDefault();
                var serializeData = $("#monthlyclientcareform").serializeArray();

                for (var i = 0; i < monthlyBucket.length; i++) {
                    var tempObject = monthlyBucket[i].replace(/ /g, "_");
                    if ($("#reload-" + tempObject).val() == 1) {
                        let _token = $('meta[name="csrf-token"]').attr('content');
                        $.ajax({
                            url: "{{ route('clientcare.reloadcountclientcareexceptions') }}?type=" + monthlyBucket[i],
                            type: "POST",
                            data: {
                                filters: serializeData,
                                _token: _token
                            },
                            success: function (response) {
                                var jsonObject = JSON.parse(response);
                                var type = jsonObject.type;
                                var idObject = type.replace(/ /g, "_");
                                $("#" + idObject + '-counter').html(jsonObject.counter);
                            },
                        });
                    } else {
                        $("#dataTable-clientcare-exception-" + tempObject).DataTable().ajax.reload();
                    }
                }
            });
            $("#monthlyclientcareform").trigger("submit");
        @endif

        $(document).on("change", '#monthlyccstate', function () {
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');
            if (state.indexOf('TX') !== -1) {
                $('#monthlyccregion').multiselect('enable');
            } else {
                $('#monthlyccregion').multiselect('disable');
            }
            $.ajax({
                url: "{{ route('getstatebranch') }}",
                type: "GET",
                data: {
                    state: state,
                    _token: _token,
                    btype: 'monthlycall'
                },
                success: function (response) {
                    $('#monthlyccbranch').empty();
                    $.each(response, function (index, value) {
                        $("#monthlyccbranch").append('<option value="' + value.id + '">' + value.FacilityName + '</option>')
                    });
                    $("#monthlyccbranch").multiselect("rebuild");
                },
            });
        });

        /* const weeklyBucket = ['weekly_nth_calls'];
        $(document).on('submit', '#weeklyclientcareform', function(e) {
            console.log("test");
            e.preventDefault();
            var serializeData = $("#weeklyclientcareform").serializeArray();
            for(var i=0; i < weeklyBucket.length;i++) {
                var tempObject = weeklyBucket[i].replace(/ /g,"_");
                console.log(tempObject);
                console.log($("#reload-"+tempObject).val());
                if($("#reload-"+tempObject).val()==1) {
                    let _token = $('meta[name="csrf-token"]').attr('content');
                    $.ajax({
                        url: "{{ route('clientcare.reloadcountclientcareexceptions') }}?type="+weeklyBucket[i],
                        type: "POST",
                        data: {
                            filters: serializeData,
                            _token: _token
                        },
                        success: function(response) {
                            var jsonObject = JSON.parse(response);
                            var type = jsonObject.type;
                            var idObject = type.replace(/ /g,"_");
                            $("#"+idObject+'-counter').html(jsonObject.counter);
                        },
                    });
                } else {
                    console.log("#dataTable-"+tempObject);
                    $("#dataTable-"+tempObject).DataTable().ajax.reload();
                }
            }

        });
        $("#weeklyclientcareform").trigger("submit");
        */

        $('#dashboardjobvite #default').click(function () {
            if ($(this).is(':checked')) {
                $('#dashboardjobvite #jobvitestate').multiselect("clearSelection");
                $('#dashboardjobvite #branch').multiselect("clearSelection");
                $('#dashboardjobvite #region').multiselect("clearSelection");
                $('#dashboardjobvite #recruiter').multiselect("clearSelection");
                $('#dashboardjobvite #Category').multiselect("clearSelection");
            }
        });

        $('#dashboardjobvite select').change(function () {
            $('#dashboardjobvite #default').prop('checked', false);
        });

        $('#dashboardjobvite .clearall').click(function () {
            $('#dashboardjobvite select[name="region"]').multiselect('disable');
        });
        $(document).on("change", '#jobvitestate', function () {
            var state = $(this).val().join(",");
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{ route('getJobviteBranchByState') }}",
                type: "GET",
                data: {
                    state: state,
                    source: 'jobs', // PD-5392: branches from `jobs`, not NeedToHire_Hub
                    _token: _token
                },
                success: function (response) {
                    $('#dashboardjobvite #branch').empty();
                    $.each(response, function (index, value) {
                        $("#dashboardjobvite #branch").append('<option value="' + value.Branch + '">' + value.Branch + '</option>')
                    });
                    $("#dashboardjobvite #branch").multiselect("rebuild");
                },
            });
        });


        $(document).on('submit', '#payrateapprovals', function (e) {
            e.preventDefault();
            var serializeData = $("#payrateapprovals").serializeArray();
            if ($("#reload-PAYRATE_APPROVALS").val() == 1) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                $.ajax({
                    url: "{{ route('dashboard.reloadCountPayrateApproval') }}",
                    type: "POST",
                    data: {
                        filters: serializeData,
                        _token: _token
                    },
                    success: function (response) {
                        var jsonObject = JSON.parse(response);
                        $("#PAYRATE_APPROVALS-counter").html(jsonObject.counter);
                    },
                });
            } else {
                $('#dataTable-PAYRATE_APPROVALS').DataTable().ajax.reload(null, false);
            }
        });
        $("#payrateapprovals").trigger("submit");

        $('#ipea_all').change(function () {
            let hide_daterang = $('.hide_daterang');
            if ($(this).is(':checked')) {
                hide_daterang.css('cssText', 'display: flex !important;');
            } else {
                hide_daterang.css('cssText', 'display: none !important;');
            }
        })
        $('#pepi_all').change(function () {
            let hide_pepi_daterang = $('.hide_pepi_daterang');
            if ($(this).is(':checked')) {
                hide_pepi_daterang.css('cssText', 'display: flex !important;');
            } else {
                hide_pepi_daterang.css('cssText', 'display: none !important;');
            }
        })
        $('#wepi_all').change(function () {
            let hide_wepi_daterang = $('.hide_wepi_daterang');
            if ($(this).is(':checked')) {
                hide_wepi_daterang.css('cssText', 'display: flex !important;');
            } else {
                hide_wepi_daterang.css('cssText', 'display: none !important;');
            }
        })
        $('#trainig_all').change(function () {
            let hide_trainig_daterang = $('.hide_trainig_daterang');
            if ($(this).is(':checked')) {
                hide_trainig_daterang.css('cssText', 'display: flex !important;');
            } else {
                hide_trainig_daterang.css('cssText', 'display: none !important;');
            }
        })
    </script>
@stop
