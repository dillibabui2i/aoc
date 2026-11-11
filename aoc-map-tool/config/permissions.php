<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Permissions Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the permissions that should be used
    | by the framework.
    |
    */
    'Core Settings' => [
        'Core Settings' => [
            'route' => 'coresetting.setting',
            'related' => ['coresetting.save'],
        ],
        'Manage IP' => [
            'route' => 'manageip',
            'related' => ['loginrestrictions', 'ajaxLoginRestrictionsData', 'blacklist.edit', 'blacklist.delete', 'blacklist.save'],
        ],
        'Notes' => [
            'route'=>'opennotes',
            'related'=>['savenotes']
        ],

    ],

    'Manage Users' => [

        'Users Grid' => [
            'route' => 'manageusers',
            'related' => ['getuserlistajax'],
        ],

        'Add New User' => [
            'route' => 'manageusers.add',
            'related' => ['manageusers.saveuser'],
        ],

        'Edit User' => [
            'route' => 'manageusers.edit',
            'related' => ['manageusers.updateuser'],
        ],
        'Remove User' => [
            'route' => 'manageusers.destroy',
            'related' => [''],
        ],
        'Disable User' => [
            'route' => 'manageusers.disable',
            'related' => [''],
        ],
        'Send User Password Email' => [
            'route' => 'manageusers.sendpasswordemail',
            'related' => [''],
        ],
		'Additional Permissions' => [
            'route' => 'additional.permission',
            'related' => [''],
        ],
		'Is a Supervisor' => [
            'route' => 'is_supervisor',
            'related' => [''],
        ],
    ],

    'Manage Maps' => [

        'Maps Grid' => [
            'route' => 'managemap',
            'related' => ['getmaplistajax'],
        ],

        'Add New Map' => [
            'route' => 'managemap.add',
            'related' => ['managemap.savemap'],
        ],

        'Map Status' => [
            'route' => 'managemap.status',
            'related' => [''],
        ],
        'Edit Map' => [
            'route' => 'managemap.editmap',
            'related' => ['managemap.savemap'],
        ],
    ],

    'Manage Roles' => [

        'Roles Grid' => [
            'route' => 'manageroles',
            'related' => ['getroleslistajax'],
        ],

        'Add Role' => [
            'route' => 'manageroles.add',
            'related' => ['manageroles.saverole'],
        ],

        'Edit Role' => [
            'route' => 'manageroles.editrole',
            'related' => ['manageroles.saverole'],
        ],

    ],

    'Map' => [

        'Choose Map' => [
            'route' => 'map.choosemap',
            'related' => ['searchlocationdata',
                            'map.resetfilters',
                            'map.getmainlocations',
                            'map.getlocationdata',
                            'map.json',
                            'map.table',
                            'map.downloaddata',
                            'map.map',
                            'map.mapIntro',
                            'map.getdistinct',
                            'map.getRouteDistance',
            ],
        ],

        'Map Import' => [
            'route' => 'map.import',
            'related' => ['map.importdata'],
        ],

        'Map Ignore' => [
            'route' => 'map.ignoregrid',
            'related' => ['map.ignoreform','map.performglobalsearch','map.ajaxignoredata','map.saveignore','map.deleteIgnore'],
        ],

        'Map Configuration' => [
            'route' => 'map.configurations',
            'related' => ['map.configuredata'],
        ],
        'Create Territories' => [
            'route' => 'savepolygone',
            'related' => ['checkpolygonname', 'map.removeTerritory', 'editTerritoryName'],
        ],
        'Remove From Potential Nurse Map' => [
            'route' => 'map.removePNM',
            'related' => [],
        ]
    ],

    'Dashboard' => [
        'Daily Activity' => [
            'route' => 'dashboard.activitydata',
            'related' => ['recruiterDetails', 'editable','getstatebranch','reloadCount'],
        ],
        'All Actiivty'=>[
            'route'=>'dashboard.allactivitydata',
            'related' => ['dashboard.ajaxallactivitydata', 'recruiterDetails', 'editable','openemailtemplate'],
        ],
        'Dashboard Jobvite'=>[
            'route'=>'dashboard.getjobvitedata',
            'related' => ['reloadCountJobvite','candidate.showNextStepForm','candidate.loadOutcomeForm','candidate.saveOutcome'],
        ],
        'Orientation Coordinator'=>[
            'route'=>'dashboard.getorientationdata',
            'related' => ['reloadCountOrientation','getocstatebranch','renewables.expiringdata','renewables.reloadexpiringdata','renewables.getoutcomeform','renewables.saveoutcomeform','renewables.getcompleterenewableform','renewables.savecompleterenewableform','renewables.getrenewablenotesform','renewables.saverenewablenotesform','renewables.showAll','renewables.exportRenewables'],
        ],
        'Social Media'=>[
            'route'=>'dashboard.getSocialMediaDashboard',
            'related' => ['dashboard.reloadCountSocialMedia','socialmedia.saveAdsForm','socialmedia.markAsCompletedAds','socialmedia.getSocialMediaDetails'],
        ],

        'NTH Checklist'=>[
            'route'=>'dashboard.getNTHChecklist',
            'related' => ['client.validateschedule','client.showBuildScheduleGrid','client.savebuildscheduleform','client.buildschedule','getNthStepForm', 'saveNthSteps','nthChecklistDetails','nth.nthHistory','nth.nthList','reloadCountNTH','nth.addNTHForm','nth.getClientDetailsInJSON','nth.saveBuildSchedule'],
        ],

        'NTH Expiring Jobs'=>[
            'route'=>'dashboard.getNTHExpiringJobslist',
            'related' => ['reloadCountNTHExpiringJobs'],
        ],

        'Recruiting NTH Exception'=>[
            'route'=>'dashboard.getrecrutingexception',
            'related' => ['recruitingexceptioncount'],
        ],
        'Active Tracker with Inactive Recruiter'=>[
            'route'=>'dashboard.active.tracker.with.inactive.recruiter',
            'related' => ['massUpdateRecruiter'],
        ],
        'Client Care Coordinator'=>[
            'route'=>'clientcare.getclientcareexceptions',
            'related' => ['clientcare.reloadcountclientcareexceptions','getstatebranch'],
        ],
        'Clinical Case Manager'=>[
            'route'=>'dashboard.getclinicalcase',
            'related' => ['ccrListing','ccr.exportData','ccr.showsnsform','ccr.saveschedulenext'],
        ],
        'Triage'=>[
            'route'=>'triage.getTriageData',
            'related' => ['triage.reloadTriageData'],
        ],
        'Intensive Case Management' => [
            'route' => 'hospitalization.index',
            'related' => [
                'hospitalization.data',
                'hospitalization.all-data',
                'hospitalization.all-data.export',
                'filters.index',
                'hospitalization.clients.index',
                'hospitalization.clients.show',
                'hospitalization.assignees.index',
                'hospitalization.trackers.store',
                'hospitalization.trackers.show',
                'hospitalization.trackers.update',
                'hospitalization.trackers.outcome.update',
                'hospitalization.trackers.current-info.update',
                'hospitalization.trackers.ed-info.update',
                'hospitalization.trackers.ed-follow-up.update',
                'hospitalization.trackers.notes.index',
                'hospitalization.trackers.history.index',
                'hospitalization.trackers.sick-plan.show',
                'hospitalization.trackers.sick-plan.items.update',
                'hospitalization.trackers.notes.store',
                'hospitalization.contact-types.index',
                'hospitalization.trackers.contacts.store',
                'hospitalization.trackers.contacts.update',
                'hospitalization.diagnosis-codes.search',
                'hospitalization.trackers.diagnoses.store',
                'hospitalization.trackers.diagnoses.update',
                'hospitalization.trackers.diagnosis-toggle.update',
                'hospitalization.training-types.index',
                'hospitalization.training-sources.index',
                'hospitalization.trackers.educations.store',
                'hospitalization.trackers.educations.update',
                'hospitalization.trackers.education-toggle.update',
                'care-coordination.index',
                'care-coordination.clients.index',
                'care-coordination.types.index',
                'care-coordination.store',
                'care-coordination.show',
                'care-coordination.update',
                'follow-up.index',
                'follow-up.clients.index',
                'follow-up.store',
                'follow-up.show',
                'follow-up.outcome.update',
            ],
        ],
        // 'immunization.index' is the All Immunizations page; it also gates the Immunizations dashboard bucket.
        'Immunization' => [
            'route' => 'immunization.index',
            'related' => [
                'immunization.all-data',
                'immunization.bucket-data',
                'immunization.all-data.export',
                'filters.index',
            ],
        ],
        'Intake Coordinator'=>[
            'route'=>'intake.getintakeexceptions',
            'related' => ['intake.reloadcountintakeexceptions'],
        ],
        'Pay Rate Approvals'=>[
            'route'=>'dashboard.getPayrateApprovalsDashboard',
            'related' => ['dashboard.reloadCountPayrateApproval', 'dashboard.getPayrateApprovalConfirmation', 'dashboard.savePayrateApproved'],
        ],
        'Conversations' => [
            'route' => 'dashboard.getMessageCenterList',
            'related' => [],
        ],
        'Evaluations' => [
            'route' => 'dashboard.evaluations',
            'related' => [],
        ],
        'Todo List' => [
            'route' => 'todolist',
            'related' => [],
        ],
        'Advocacy' => [
            'route' => 'advocacy.statuschangerequest',
            'related' => ['advocacy.reloadstatuschangerequest'],
        ],
        'School Set Ups' => [
            'route' => 'dashboard.getSchoolSetUpsRequest',
            'related' => ['dashboard.reloadOpenSchoolSetupRequest'],
        ],
        'Supply Request' => [
            'route' => 'supplies.getSupplyRequestsDashboard',
            'related' => [],
        ],
        'Billing' => [
            'route' => 'dashboard.getBillingData',
            'related' => [],
        ],
        'Delete Supervisory Visit' => [
            'route' => 'supervisoryVisits.delete',
            'related' => [],
        ],
        'Add School Set Up' => [
            'route' => 'schoolSetup.form',
            'related' => [],
        ],
    ],
    'Client Care'=>[
        'Missed Visits Bucket'=>[
            'route'=>'missed_visit_bucket',
            'related' => ['clientcare.reloadcountclientcareexceptions','getstatebranch'],
        ]
    ],
    'Schedule' => [
        'Add Nurse' => [
            'route' => 'getScheduleForm',
            'related' => ['saveScheduleNext', 'getScheduleClient', 'getCandidateName'],
        ],
        'Add Events' => [
            'route' => 'schedules.addEvents',
            'related' => ['schedules.saveEvents'],
        ],
        'Delete Schedule Records' => [
            'route' => 'schedules.removeSchedule',
            'related' => [],
        ],
        'Add Office NEO' => [
            'route' => 'schedules.addofficeipeaevent',
            'related' => ['schedules.saveofficeipeaevent'],
        ],
        'Add Therapy NEO' => [
            'route' => 'schedules.addtherapyipeaevent',
            'related' => ['schedules.savetherapyipeaevent'],
        ],
        "Manage Office Events"  => [
            'route' => 'schedules.removeOfficeEvents',
            'related' => [],
        ],
		"View Office Events"  => [
            'route' => 'schedules',
            'related' => [],
        ],
    ],
    'CCR Calendar' => [
        'View Calendar' => [
            'route' => 'ccrCalendar.calendar',
            'related' => [
                'ccrCalendar.getWeekEvents',
                'ccrCalendar.getEventDetails',
                'ccrCalendar.addEvent',
                'ccrCalendar.saveEvent',
            ],
        ],
        'Manage Blocked Times' => [
            'route' => 'ccrCalendar.removeCcrEvents',
            'related' => [],
        ],
        'Manage PRC Availability' => [
            'route' => 'ccrAvailability.manageOthers',
            'related' => [
                'ccrAvailability.get',
                'ccrAvailability.save',
                'ccrAvailability.updateBlock',
                'ccrAvailability.deleteBlock'
            ],
        ],
    ],
    'IPEA' => [
        'Calendar' => [
            'route' => 'ipea.calender',
            'related' => ['ipea.getWeekEvents', 'ipea.updateNurseIpeaInfo', 'ipea.saveNurseInfo', 'ipea.getCalendarSearchString','saveReschedule','ipea.officeTherapyEventDetails'],
        ],
        'Attachment' => [
            'route' => 'manageipeaattachment.grid',
            'related' => ['emailtemplate.uploadfiles', 'ajaxipeaattachment', 'manageipeaattachment.edit', 'manageipeaattachment.delete', 'manageipeaattachment.save', 'manageipeaattachment.download'],
        ],
        'Manage Branches' => [
            'route'=>'emailtemplate.grid',
            'related'=>['ajaxemailtemplate'],
        ],
        'Add Branch' => [
            'route'=>'add_branch',
            'related'=>['ajaxemailtemplate','emailtemplate.grid','emailtemplate.edit','emailtemplate.save'],
        ],
        'Edit Branch' => [
            'route'=>'edit_branch',
            'related'=>['ajaxemailtemplate','emailtemplate.grid','emailtemplate.edit','emailtemplate.save','emailtemplate.delete'],
        ],
        'Edit IPEA Data' => [
            'route'=>'edit_ipea_data',
            'related'=>['ajaxemailtemplate','emailtemplate.grid','emailtemplate.edit','emailtemplate.save'],
        ],
        'IPEA List' => [
            'route' => 'ipeaListing',
            'related' => [],
        ],
        'remove IPEA template' => [
            'route' => 'can_remove_ipea_template',
            'related' => [],
        ],
        'Retry Background Check' => [
            'route' => 'first-advantage.retry',
            'related' => [],
        ]

    ],

    'Recruiting' => [

        'Recruiting Activities' => [
            'route' => 'recruiting_activities',
            'related' => [''],
        ],
        'Is Recruiting Team Leader' => [
            'route' => 'is_recruiting_team_leader',
            'related' => [''],
        ],
        'Is Recruiting Manager' => [
            'route' => 'is_recruiting_manager',
            'related' => [''],
        ],
        'Can view all Trackers' => [
            'route' => 'is_recruiting_admin',
            'related' => [''],
        ],
        'is Recruiting Admin' => [
            'route' => 'is_recruiting_admin_form',
            'related' => [''],
        ],
        'Delete Tracker Record' => [
            'route'=> 'dashboard.removeactivity',
            'related' => [],
        ],
        'Edit Whiteboard Row' => [
            'route'=> 'edit-whiteboard-row',
            'related' => ['editable'],
        ],
        'Create Recruiter Scorecards' => [
            'route'=> 'recruiterScorecardForm',
            'related' => ['getRecruiterscorecardDataByRecruiter', 'submitRecruiterScorecard'],
        ],
        'Manage Recruiter Scorecards' => [
            'route'=> 'recruiterscorecard',
            'related' => ['getrecruiterscorecardlist','getRecruiterscorecardDataByRecruiter', 'submitRecruiterScorecard'],
        ],
        'Update Non-VBC' => [
            'route' => 'update_non_vbc',
            'related' => [''],
        ],
        'Can Approve Pay Rates' => [
            'route' => 'can_approve_pay_rates',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 2' => [
            'route' => 'can_approve_pay_rates_Level_2',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 3' => [
            'route' => 'can_approve_pay_rates_Level_3',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 4' => [
            'route' => 'can_approve_pay_rates_Level_4',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 5' => [
            'route' => 'can_approve_pay_rates_Level_5',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 6' => [
            'route' => 'can_approve_pay_rates_Level_6',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 7' => [
            'route' => 'can_approve_pay_rates_Level_7',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 8' => [
            'route' => 'can_approve_pay_rates_Level_8',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 9' => [
            'route' => 'can_approve_pay_rates_Level_9',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 10' => [
            'route' => 'can_approve_pay_rates_Level_10',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 11' => [
            'route' => 'can_approve_pay_rates_Level_11',
            'related' => [''],
        ],
        'Can Approve Pay Rates - Level 12' => [
            'route' => 'can_approve_pay_rates_Level_12',
            'related' => [''],
        ],
        'Set trackers as uncontrollable' => [
            'route'=> 'edit-uncontrollable',
            'related' => ['editable'],
        ],
        'Recruiter Bonuses' => [
            'route' => 'view-recruiter-bonus',
            'related' => [''],
        ],
        'Can Edit NTH Manager Comment' => [
            'route' => 'manager-comments',
            'related' => ['editable'],
        ],
        'Can Manage Recruiter Bonuses' => [
            'route' => 'manage-recruiter-bonus',
            'related' => [''],
        ],
        'View NTH Leads and Inquiries' => [
            'route' => 'view-nthleads-inquiries',
            'related' => ['nthalerts.getleadsandinquiries','nthalerts.reloadleadsandinquiries'],
        ],
        'Assign NTH Inquiries to Self' => [
            'route' => 'selfassignment-nthleads-inquiries',
            'related' => ['nthalerts.selfassignment'],
        ],
        'Reassign NTH Leads and Inquiries' => [
            'route' => 're-assignment-nthleads-inquiries',
            'related' => ['nthalerts.reassignemnt'],
        ]
    ],

    'Reporting' => [

        'Recruiting Whiteboard' => [
            'route' => 'whiteboard',
            'related' => [''],
        ],
        'Editable Whiteboard' => [
            'route' => 'editableWhiteboard',
            'related' => ['editableWhiteboard'],
        ],
        'National Recruiting Report' => [
            'route' => 'national-recruiting-report',
            'related' => ['hoursOnNTH'],
        ],
        'Nurse Churn Report' => [
            'route' => 'nurse-churn-report',
            'related' => [''],
        ],
        'Need To Hire Report' => [
            'route' => 'hoursOnNTH',
            'related' => ['nthHoursAdded', 'nthHoursRemoved', 'nthHoursClassification', 'nthByMeetingNurses', 'getSource', 'nth.getnthopup'],
        ],
        'Recruiter Scorecard Report' => [
            'route' => 'recruiterScorecardReport',
            'related' => [''],
        ],
        'Ipea Conversion Report' => [
            'route' => 'ipeaconversion',
            'related' => ['IpeaConversionCounts'],
        ],
        'Missing Renewable Report' => [
            'route' => 'renewablesReport.missingRenewable',
            'related' => [],
        ],
        'Coverage Whiteboard' => [
            'route' => 'coverageWhiteBoard',
            'related' => [''],
        ],
        'National Client Care Report' => [
            'route' => 'national-client-care-report',
            'related' => [
                'missedVisit',
                'coverageBooked',
                'nthWeeklyCalls',
                'monthlycalls',
                'getnccpopup'
            ],
        ],
        'National Intake' => [
            'route' => 'intakeReport.referrals',
            'related' => [
            ],
        ],
    ],
    'Manage Feature Flag' => [
        'Feature Flag Grid' => [
            'route' => 'managefeatureflag',
            'related' => ['getfeatureflaglistajax'],
        ]
    ],
    'Manage Kantime Facility' => [
        'Manage Kantime Facility' => [
            'route' => 'managekantimefacility',
            'related' => ['view.kantimefacility','ajax.kantimefacility','add.kantimefacility','save.kantimefacility'],
        ]
    ],
    'Candidates' => [
        'Candidates View' => [
            'route' => 'candidatesDetails',
            'related' => [],
        ],
        'Add New Candidate' => [
            'route' => 'getAddCandidateForm',
            'related' => [],
        ],
        'Manage Hub Cities' => [
            'route' => 'hubcities',
            'related' => ['hubcities.add', 'hubcities.save', 'hubcities.edit', 'hubcities.delete'],
        ],
        'View All Candidates on Dashboard' => [
            'route' => 'can_view_all_candidates',
            'related' => ['dashboard.getjobvitedata', 'reloadCountJobvite'],
        ],
        'Jobvite Candidate Assign Recruiter' => [
            'route' => 'jobvite.assignrecruiterform',
            'related' => ['jobvite_assign_recruiter','jobvite.saverecruiter'],
        ]
    ],
    'Manage Whiteboard' => [
        'Whiteboard Grid' => [
            'route' => 'managewhiteboard',
            'related' => ['managewhiteboard.getwhiteboardlistajax','managewhiteboard.whiteboardreport'],
        ],
        'Add New Whiteboard' => [
            'route' => 'managewhiteboard.add',
            'related' => ['managewhiteboard.save'],
        ],
        'Edit Whiteboard' => [
            'route' => 'managewhiteboard.edit',
            'related' => ['managewhiteboard.save'],
        ],
        'Remove Whiteboard' => [
            'route' => 'managewhiteboard.delete',
            'related' => [''],
        ],
    ],
    'Clinician' => [
        'Clinician Profile' => [
            'route' => 'clinician.getDetails',
            'related' => [],
        ],
        'Clinician List' => [
            'route' => 'clinicianListing',
            'related' => [],
        ],
        'Clinician Care Manager Dashboard' => [
            'route' => 'fieldevaluation.unsentdocuments',
            'related' => ['fieldevaluation.reloadunsentdocuments'],
        ],
        'View Pay Rate Agreements' => [
            'route' => 'view_payrate_agreeement_document',
            'related' => [''],
        ] ,
        'View Field Evaluations' => [
            'route' => 'view_field_evaluation_document',
            'related' => [''],
        ] ,
        'View Therapy Evaluations' => [
            'route' => 'view_therapy_evaluation_document',
            'related' => [''],
        ] ,
        'View Office Evaluations' => [
            'route' => 'view_office_evaluation_document',
            'related' => [''],
        ],
        'View Renewable Documents' => [
            'route' => 'view_renewable_document',
            'related' => [''],
        ],
        'Download Documents' => [
            'route' => 'rightSignature.documentDownload',
            'related' => [''],
        ]
    ],
    'Client' => [
        'Client Profile' => [
            'route' => 'client.getDetails',
            'related' => [],
        ],
        'Client List' => [
            'route' => 'clientlisting',
            'related' => [],
        ],
        'Merge Duplicate Client' => [
            'route' => 'client.mergeClient',
            'related' => [],
        ],
        'Change Frequency' => [
            'route' => 'can_edit_ccc_call_frequency',
            'related' => ['client.saveChangeCallFrequency']
        ],
        'Add NTH - Allow Adding New Client' => [
            'route' => 'nthallownewclient',
            'related' => []
        ],
    ],
    'Intake' => [
        'Add Intake' => [
            'route' => 'intake.addIntakeForm',
            'related' => ['intake.loadMoreNurseForm','intake.save'],
        ],
        'Send SMS to Care Team' => [
            'route' => 'intake.sendSmsToCareTeam',
            'related' => [],
        ],
        'Approve Non Admit' => [
            'route' => 'can_approve_non_admit',
            'related' => [],
        ]
    ],
    'Triage' => [
        'Add Triage Call' => [
            'route' => 'triage.addTriageCallForm',
            'related' => ['triage.save','triage.checkingClientCallsAvailability'],
        ],
        'Edit Triage Dates' => [
            'route'=>'edit-triage-call-date',
            'related' => []
        ],
        // The single permission for all ED Visits functionality (PD-4057/PD-3983/PD-3969/PD-3973/PD-3970; it
        // replaced "Triage ED Visits"): the Triage "ED Visits" and "Care Coordination Activities" buckets,
        // the ED Visit tracker modal and all its tabs, the "ED Visit Record" creation form, and the ED
        // "Assigned To" list. It has no page of its own, so 'route' is only the permission's identity (the
        // Blade @canPermission check); `related` is what the ED UI calls. Deliberately excludes the
        // ICM-only screens (ICM bucket data, All Hospitalizations page/export, Communication bucket).
        'Manage ED Visits' => [
            'route' => 'ed-visits.manage',
            'related' => [
                'filters.index',
                'hospitalization.ed-visits.data',
                'hospitalization.clients.index',
                'hospitalization.clients.show',
                'hospitalization.ed-visits.trackers.store',
                'hospitalization.assignees.index',
                'hospitalization.trackers.show',
                'hospitalization.trackers.update',
                'hospitalization.trackers.outcome.update',
                'hospitalization.trackers.current-info.update',
                'hospitalization.trackers.ed-info.update',
                'hospitalization.trackers.ed-follow-up.update',
                'hospitalization.trackers.notes.index',
                'hospitalization.trackers.notes.store',
                'hospitalization.trackers.sick-plan.show',
                'hospitalization.trackers.sick-plan.items.update',
                'hospitalization.contact-types.index',
                'hospitalization.trackers.contacts.store',
                'hospitalization.trackers.contacts.update',
                'follow-up.clients.index',
                'follow-up.store',
                'follow-up.show',
                'follow-up.outcome.update',
                'hospitalization.training-types.index',
                'hospitalization.training-sources.index',
                'hospitalization.trackers.educations.store',
                'hospitalization.trackers.educations.update',
                'hospitalization.trackers.education-toggle.update',
                'hospitalization.diagnosis-codes.search',
                'hospitalization.trackers.diagnoses.store',
                'hospitalization.trackers.diagnoses.update',
                'hospitalization.trackers.diagnosis-toggle.update',
                'care-coordination.index',
                'care-coordination.clients.index',
                'care-coordination.types.index',
                'care-coordination.store',
                'care-coordination.show',
                'care-coordination.update',
            ],
        ],
    ],
    'Immunizations' => [
        'Manage Immunizations Schedule' => [
            'route' => 'immunization.schedule.manage',
            'related' => [
                'immunization.schedule.data',
                'immunization.schedule.show',
                'immunization.schedule.store',
                'immunization.schedule.update',
                'filters.index',
            ],
        ],
        // TODO: gates the immunization tab in the client profile (view-only for everyone, edit only
        // with this permission). Not built yet. When it is, name its VBC route
        // 'immunization.client-info.manage' (or update 'route' below to match) and gate it with the
        // 'vbc.permission' middleware.
        'Manage Client Immunization information' => [
            'route' => 'immunization.client-info.manage',
            'related' => [],
        ],
    ],
    'Trach/Vent Classes' => [
        'Manage Trach/Vent Class' => [
            'route' => 'managetrachventclass',
            'related' => ['gettrachventclasslistajax','managetrachventclass.add','managetrachventclass.edit','managetrachventclass.savescheduleclass','managetrachventclass.updatescheduleclass','gettrachventscheduleclass'],
        ],
        'Calendar' => [
            'route' => 'trachventclass.calender',
            'related' => ['trachventclass.getWeekEvents', 'getTrachVentClassInfo'],
        ],
        'Trach/Vent Class Request Notifications' =>[
            'route' => 'trachvent_class_request_notify',
            'related' => [''],
        ],
        'Manage Class Types' => [
            'route' => 'managetrachventclass.class-categories.index',
            'related' => ['managetrachventclass.class-categories.store', 'managetrachventclass.class-categories.destroy'],
        ],
        'Cancel Class' => [
            'route' => 'trachventclass.destroyOccurrence',
            'related' => ['trachventclass.destroyFutureOccurrences', 'trachventclass.destroyClassOccurrences'],
        ]
    ],
    'After Hours' => [
        'Manage After Hours Schedules' => [
            'route' => 'manageafterhoursclass',
            'related' => [],
        ],
        'Manage After Hours All Schedules' => [
            'route' => 'manageafterhoursallclass',
            'related' => [],
        ],
        'Calendar' => [
            'route' => 'afterhours.calender',
            'related' => ['afterhours.getWeekEvents', 'afterhours.getAfterHoursInfo','afterhours.saveAfterHoursInfo'],
        ],
        'Assign Shifts' => [
            'route' => 'assign.shifts',
            'related' => [],
        ],
        'Assign All Shifts' => [
            'route' => 'assign.allshifts',
            'related' => [],
        ],
        'Delete After Hour' => [
            'route' => 'deleteafterhoursclass',
            'related' => [],
        ],
    ],
    'Coverage Tracker' => [
        'Add to Coverage Tracker from map' => [
            'route' => 'coverage.save',
            'related' => ['coverage.validateDisciplineName'],
        ],
        'Coverage Tracker Page' => [
            'route' => 'coverage.list',
            'related' => ['coverage.exportdata'],
        ]
    ],
    'Manage Region' => [
        'View Region' => [
            'route' => 'region',
            'related' => [],
        ],
        'Edit Region' => [
            'route' => 'region.edit',
            'related' => [],
        ]
    ],
    'Manage jobcategory' => [
        'View jobcategory' => [
            'route' => 'jobcategory',
            'related' => [],
        ],
        'Edit jobcategory' => [
            'route' => 'jobcategory.edit',
            'related' => [],
        ]
    ],
    'Message Center' => [
        'Attachment' => [
            'route' => 'managemessageattachment.list',
            'related' => ['managemessageattachment.edit', 'managemessageattachment.delete', 'managemessageattachment.save', 'managemessageattachment.download'],
        ],
        'Send Mass Text' => [
            'route' => 'save.massmessage',
            'related' => [],
        ],
        'Message Center List' => [
            'route' => 'messagecenter.list',
            'related' => [],
        ],
        'Join Conversation' => [
            'route' => 'join_conversation',
            'related' => [],
        ],
        'Take Over Conversation' => [
            'route' => 'take_over_conversation',
            'related' => [],
        ],
    ],
    'GP Calculator' => [
        'Main Page' => [
            'route' => 'gpCalculator.index',
            'related' => ['gpCalculator.list'],
        ],
        'Override Reimbursements' => [
            'route' => 'payerrates.add',
            'related' => ['payerrates.list', 'payerrates.save'],
        ],
        'Approve Override Reimbursements' => [
            'route' => 'payerrates.approve',
            'related' => ['payerrates.approve'],
        ],
        'Get Reimbursement Notification' => [
            'route' => 'get_reimbursement_notification',
            'related' => [''],
        ],
        'Delete Payer Rates' => [
            'route' => 'payerrates.delete',
            'related' => [''],
        ],
    ],

    'Reports' => [
        'report' => [
            'route' => 'report',
            'related' => [],
        ]
    ],
    'Notification' => [
        'Mass Clear Notifications' => [
            'route' => 'notification.mark-multiple-as-read',
            'related' => [],
        ],
        'Daily Wind Down Notifications' => [
            'route' => 'notification.daily-wind-down',
            'related' => [],
        ],
    ],
    'Evaluations' => [
        'View All Office Evaluations' => [
            'route' => 'officeevaluation.list',
            'related' => ['officeevaluation.edit', 'officeevaluation.save'],
        ],
        'Office Evaluation HR' => [
            'route' => 'evaluationhr'
        ],
        'Manage Field Evaluations' => [
            'route' => 'fieldevaluation.list',
            'related' => ['fieldevaluation.edit', 'fieldevaluation.save'],
        ],
        'Field Evaluation Dashboard Widget' => [
            'route' => 'fieldevaluation.unsentdocuments',
            'related' => ['fieldevaluation.reloadunsentdocuments'],
        ]        ,
        'Office Evaluation Dashboard Widget' => [
            'route' => 'officeevaluation.unsentdocuments',
            'related' => ['officeevaluation.reloadunsentdocuments'],
        ],
        'Delete Evaluations' => [
            'route' => 'delete_evaluations',
            'related' => ['fieldevaluation.delete', 'officeevaluation.delete'],
        ]
    ],
    'Mobile App' => [
        'Manage Mobile App User' => [
            'route' => 'mobile.addnewuser',
            'related' => ['mobile.getExternalUserAutoCompleteList','saveMobileUser','removeMobileUser','getMobileUser'],
        ]
    ],
    'Manage Renewables Rules' => [
        'Renewable Grid' => [
            'route' => 'managerenewablesrules',
            'related' => [''],
        ],
    ],
    'Manage Text Templates' => [
        'Text Templates Grid' => [
            'route' => 'manageTextTemplate',
            'related' => ['getTextTemplatelistajax'],
        ],
        'add/edit/delete Text Templates' => [
            'route' => 'addTextTemplate',
            'related' => ['editTextTemplate', 'deleteTextTemplate'],
        ],
    ],
    'Manage Custom Fields' => [
        'Custom Fields Grid' => [
            'route' => 'manageCustomFields',
            'related' => ['getCustomFieldslistajax'],
        ],
        'Add/Edit/Delete Custom Fields' => [
            'route' => 'customfieldaction',
            'related' => [
                'openCustomFieldForm',
                'manageCustomFields.save',
                'manageCustomFields.delete'
            ],
        ],
        'Edit Custom Field Values' => [
            'route' => 'editCustomFieldValue',
            'related' => [
                'editable',
            ],
        ]
    ],
    'Manage Payer Page' => [
        'payer Page Grid' => [
            'route' => 'managePayer',
            'related' => ['getPayerlistajax', 'exportPayerData',
            'managepayerdata.edit', 'managepayerdata.update'],
        ],
    ],
    'Biling' => [
        'Adjustment Bucket' => [
            'route' => 'adjustments.bucket',
            'related' => [],
        ],
        'Can Complete Adjustment' => [
            'route' => 'can_complete_adjustments',
            'related' => [],
        ],
        'Exclude Unbilled Schedules' => [
            'route' => 'billing.billingExclude',
            'related' => ['dashboard.getUnbilledSchedulesData'],
        ],
        'View adjustment' => [
            'route' => 'adjustments',
            'related' => [],
        ],
        'Edit adjustment' => [
            'route' => 'adjustments.edit',
            'related' => [],
        ]
    ],
    'NTH Landing Page' => [
        'NTH Form Fields for Card' => [
            'route' => 'nthFormFieldsForNthCard',
            'related' => [],
        ]
    ]
];
