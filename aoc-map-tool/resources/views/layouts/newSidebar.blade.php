<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\Helpers\Notifications;
use App\MssqlProviders;
use App\Helpers\MenuHelper;

$loggedInUser = H::getLoggedInUser();
$selectedMap = H::getSelectedMap();
$notificationCount = Notifications::getNotificationCount();
?>
<section class="sidebar-section">
    <span id="collapse-icon" data-toggle="sidebar-colapse" class="fa fa-2x mr-3"></span>
    <div class="angel-logo pull-left">
        <a href="{{route('dashboard')}}"><img src="{{ asset('images/white-logo.png') }}" alt=""/> </a>
    </div>

    <div id="sidebar-container" class="sidebar-expanded d-none d-md-block">
        <ul class="list-group">
            <!-- Menu with submenu -->
            <a href="{{route('dashboard')}}" title="Dashboard"
                class="bg-sidebar list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-dashboard fa-fw mr-3"></span>
                    <span class="menu-collapsed">Dashboard</span>
                </div>
            </a>
            <a href="{{route('notification.list')}}" title="Notifications"
                class="bg-sidebar list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-start align-items-center notification-cnt
                <?php echo $notificationCount > 0 ? 'unread-notification': ''; ?>">
                    <span class="fa fa-bell fa-fw mr-3"></span>
                    <span class="menu-collapsed">Notifications<b>(<span
                                id="notificationCount"><?php echo $notificationCount; ?></span>)</b></span>
                </div>
            </a>
            <a href="#formSubMenu2" title="Forms" data-toggle="collapse" aria-expanded="false"
                class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-file-text-o fa-fw mr-3"></span>
                    <span class="menu-collapsed">Forms</span>
                    <span class="submenu-icon ml-auto"></span>
                </div>
            </a>
            <!-- Submenu content -->
            <div id='formSubMenu2' title="Forms" class="collapse sidebar-submenu">
                @canPermission('getScheduleForm')
                <a href="javascript:showScheduleForm('','')" title="Add Clinician"
                    class="bg-sidebar list-group-item list-group-item-action popup-item">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Add Clinician</span>
                    </div>
                </a>
                @endcanPermission
                @if (LD::checkFeatureFlag('add-new-menu-to-open-candidate-form'))
                    @canPermission('getAddCandidateForm')
                    <a href="javascript:void(0)" onclick="showAddCandidateForm()"
                    title="Add Candidate"
                    class="bg-sidebar list-group-item list-group-item-action popup-item">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">Add Candidate</span>
                        </div>
                    </a>
                    @endcanPermission
                @endif
                @if (LD::checkFeatureFlag('add-intake'))
                    @canPermission('intake.addIntakeForm')
                    <a href="javascript:void(0)" onclick="addIntakeForm()" title="Add Intake"
                        class="bg-sidebar list-group-item list-group-item-action popup-item">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">Add Intake</span>
                        </div>
                    </a>
                    @endcanPermission
                @endif
                    @canPermission('triage.addTriageCallForm')
                    <a href="javascript:void(0)" onclick="addTriageCallForm()" title="Add Triage Call"
                        class="bg-sidebar list-group-item list-group-item-action popup-item">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">Add Triage Call</span>
                        </div>
                    </a>
                    @endcanPermission
                @canPermission('schedules.addofficeipeaevent')
                <a href="javascript:addOfficeIpeaEvent();" title="Add Office NEO"
                    class="bg-sidebar list-group-item list-group-item-action popup-item">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Add Office NEO</span>
                    </div>
                </a>
                @endcanPermission
                @canPermission('schedules.addtherapyipeaevent')
                <a href="javascript:addTherapyIpeaEvent();" title="Add Therapy NEO"
                    class="bg-sidebar list-group-item list-group-item-action popup-item">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Add Therapy NEO</span>
                    </div>
                </a>
                @endcanPermission
                @if (LD::checkFeatureFlag('status-change-request-form'))
                <a href="javascript:statusChangeRequest();" title="Status Change Request"
                    class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Status Change Request</span>
                    </div>
                </a>
                @endif
                @if (LD::checkFeatureFlag('add-school-setup-form'))
                @canPermission(['schoolSetup.form'])
                <a href="javascript:loadSchoolSetupForm();" title="Add School Set Up"
                    class="bg-sidebar list-group-item list-group-item-action popup-item">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Add School Set Up</span>
                    </div>
                </a>
                @endcanPermission
                @endif
            </div>
            <a href="#calendarSubMenu2" title="Calendars" data-toggle="collapse" aria-expanded="false"
                class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-file-text-o fa-fw mr-3"></span>
                    <span class="menu-collapsed">Calendars</span>
                    <span class="submenu-icon ml-auto"></span>
                </div>
            </a>
            <!-- Submenu content -->
            <div id='calendarSubMenu2' title="Forms" class="collapse sidebar-submenu">
                @canPermission('ipea.calender')
                <a href="{{route('ipea.calender')}}" title="IPEA Calendar"
                    class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">IPEA Calendar</span>
                    </div>
                </a>
                @endcanPermission
                @if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class'))
                    @canPermission('trachventclass.calender')
                    <a href="{{route('trachventclass.calender')}}" title="Class Calendar"
                        class="bg-sidebar list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">Class Calendar</span>
                        </div>
                    </a>
                    @endcanPermission
                @endif
                @if (LD::checkFeatureFlag('after-hours-schedules'))
                    @canPermission('afterhours.calender')
                    <a href="{{route('afterhours.calender')}}" title="After Hours Calendar"
                        class="bg-sidebar list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">After Hours Calendar</span>
                        </div>
                    </a>
                    @endcanPermission
                @endif
                    @canPermission('ccrCalendar.calendar')
                    <a href="{{route('ccrCalendar.calendar')}}" title="PRC Calendar"
                        class="bg-sidebar list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">PRC Calendar</span>
                        </div>
                    </a>
                    @endcanPermission

                @if (LD::checkFeatureFlag('intake-calendar-navigation-menu'))
                <a href="{{route('intake.calendar')}}" title="Intake Calendar"
                    class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Intake Calendar</span>
                    </div>
                </a>
                @endif

            </div>
            <?php
                if($selectedMap){
                    $mapRoute = route('map.map');
                }else{
                    $mapRoute = route('map.mapIntro');
                }
            ?>
            <a href="<?php echo $mapRoute;?>" title="Maps" class="bg-sidebar list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-map-marker fa-fw mr-3"></span>
                    <span class="menu-collapsed">Map</span>
                </div>
            </a>

                @canPermission('clinicianListing')
                    <a href="{{route('clinicianListing')}}" title="Clinicians"
                        class="bg-sidebar list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="fa fa-user-md fa-fw mr-3"></span>
                            <span class="menu-collapsed">Clinicians</span>
                        </div>
                    </a>
                @endcanPermission
            @canPermission('clientlisting')
            <a href="{{route('clientlisting')}}" title="Notifications"
                class="bg-sidebar list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-users fa-fw mr-3"></span>
                    <span class="menu-collapsed">Clients</span>
                </div>
            </a>
            @if(LD::checkFeatureFlag('add-nth-menu-in-sidebar'))
                <a href="{{route('nth.nthList')}}" title="Need To Hire"
                    class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="fa fa-file-text-o fa-fw mr-3"></span>
                        <span class="menu-collapsed">Need To Hire</span>
                    </div>
                </a>
            @endif
            @endcanPermission
            @if (LD::checkFeatureFlag('bonus_report'))
                @canAnyPermission(['view-recruiter-bonus'])
                <a href="{{route('recruiting')}}" title="Recruiter Bonus"
                    class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="fa fa-bar-chart fa-fw mr-3"></span>
                        <span class="menu-collapsed">Recruiter Bonus</span>
                    </div>
                </a>
                @endcanPermission
            @endif
            @canAnyPermission(['report'])
            <a href="#submenu2" title="Reports" data-toggle="collapse" aria-expanded="false"
                class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-file-text-o fa-fw mr-3"></span>
                    <span class="menu-collapsed">Reports</span>
                    <span class="submenu-icon ml-auto"></span>
                </div>
            </a>
            <!-- Submenu content -->
            <div id='submenu2' title="Reports" class="collapse sidebar-submenu {{ MenuHelper::isActiveSubMenu(Route::currentRouteName()) }}">
                <a href="#recruitingSubmenu" title="Recruiting" data-toggle="collapse" aria-expanded="false"
                    class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Recruiting</span>
                        <span class="submenu-icon ml-auto"></span>
                    </div>
                </a>
                <div id='recruitingSubmenu' title="Recruiting" class="collapse sidebar-submenu {{ MenuHelper::isActiveSubMenu(Route::currentRouteName(), 'reports.recruiting') }}">
                    @canPermission('whiteboard')
                        <a href="{{route('whiteboard')}}" title="Recruiting Whiteboard"
                            class="bg-sidebar list-group-item list-group-item-action {{ MenuHelper::isActiveMenuItem('whiteboard') }}">
                            <div class="d-flex w-100 justify-content-start align-items-center">
                                <span class="menu-collapsed">Recruiting Whiteboard</span>
                            </div>
                        </a>
                    @endcanPermission
                    @canPermission('therapy.whiteboard.index')
                    @if(LD::checkFeatureFlag('therapy-whiteboard-report'))
                        <a href="{{ route('therapy.whiteboard.index') }}" title="Recruiting Whiteboard"
                            class="bg-sidebar list-group-item list-group-item-action {{ MenuHelper::isActiveMenuItem('therapy.whiteboard.index') }}">
                            <div class="d-flex w-100 justify-content-start align-items-center">
                                <span class="menu-collapsed">Therapy Whiteboard</span>
                            </div>
                        </a>
                    @endif
                    @endcanPermission
                    @canPermission('national-recruiting-report')
                        <a href="{{route('candidates')}}" title="National Recruiting"
                            class="bg-sidebar list-group-item list-group-item-action {{ MenuHelper::isActiveMenuItem('candidates') }}">
                            <div class="d-flex w-100 justify-content-start align-items-center">
                                <span class="menu-collapsed">National Recruiting</span>
                            </div>
                        </a>
                    @endcanPermission
                    @if (LD::checkFeatureFlag('recruiting-candidate-conversion-report'))
                        <a href="{{route('candidateConversion')}}" title="Candidate Conversion"
                            class="bg-sidebar list-group-item list-group-item-action {{ MenuHelper::isActiveMenuItem('candidateConversion') }}">
                            <div class="d-flex w-100 justify-content-start align-items-center">
                                <span class="menu-collapsed">Candidate Conversion</span>
                            </div>
                        </a>
                    @endif
                    @if (LD::checkFeatureFlag('recruiting-ipea-speed-report'))
                        <a href="{{route('ipeaSpeed')}}" title="Recruiting SPEED"
                            class="bg-sidebar list-group-item list-group-item-action {{ MenuHelper::isActiveMenuItem('ipeaSpeed') }}">
                            <div class="d-flex w-100 justify-content-start align-items-center">
                                <span class="menu-collapsed">Recruiting SPEED</span>
                            </div>
                        </a>
                    @endif
                    @canPermission('manage-recruiter-bonus')
                        <a href="{{route('bonusReport')}}" title="Bonus Report"
                            class="bg-sidebar list-group-item list-group-item-action {{ MenuHelper::isActiveMenuItem('bonusReport') }}">
                            <div class="d-flex w-100 justify-content-start align-items-center">
                                <span class="menu-collapsed">Bonus Report</span>
                            </div>
                        </a>
                    @endcanPermission
                </div>
                @canPermission('nurse-churn-report')
                <a href="{{route('nurseChurn')}}" title="NurseChurn"
                    class="bg-sidebar list-group-item list-group-item-action {{ MenuHelper::isActiveMenuItem('nurseChurn') }}">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Nurse Churn</span>
                    </div>
                </a>
                @endcanPermission
                @canPermission('hoursOnNTH')
                <a href="{{route('hoursOnNTH')}}" title="Need To Hire"
                    class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Need To Hire</span>
                    </div>
                </a>
                @endcanPermission
                @canPermission('ipeaconversion')
                <a href="{{route('IpeaConversion')}}" title="IPEA Conversion"
                    class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">IPEA Conversion</span>
                    </div>
                </a>
                @endcanPermission
                @if (LD::checkFeatureFlag('missing-renewable-report'))
                @canPermission('renewablesReport.missingRenewable')
                <a href="{{route('renewablesReport.missingRenewable')}}" title="Missing Renewables"
                    class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Missing Renewables</span>
                    </div>
                </a>
                @endcanPermission
                @endif
                @if(LD::checkFeatureFlag('clinician-client-skills-match'))
                    <a href="{{route('skillsMatch')}}" title="Clinician/Client Skills Match"
                        class="bg-sidebar list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">Clinician/Client Skills Match</span>
                        </div>
                    </a>
                @endif
                @canPermission('coverageWhiteBoard')
                @if(LD::checkFeatureFlag('coverage-whiteboard-add-menu'))
                    <a href="{{route('coverageWhiteBoard')}}" title="Coverage Whiteboard"
                        class="bg-sidebar list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">Coverage Whiteboard</span>
                        </div>
                    </a>
                @endif
                @endcanPermission
                @if (LD::checkFeatureFlag('national-client-care-report'))
                    @canPermission('national-client-care-report')
                        <a href="{{route('missedVisit')}}" title="National Client Care Report"
                            class="bg-sidebar list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-start align-items-center">
                                <span class="menu-collapsed">National Client Care Report</span>
                            </div>
                        </a>
                    @endcanPermission
                @endif
                @if (LD::checkFeatureFlag('intake-report'))
                    @canPermission('intakeReport.referrals')
                        <a href="{{route('intakeReport.referrals')}}" title="National Intake"
                            class="bg-sidebar list-group-item list-group-item-action">
                            <div class="d-flex w-100 justify-content-start align-items-center">
                                <span class="menu-collapsed">National Intake</span>
                            </div>
                        </a>
                    @endcanPermission
                @endif
            </div>
            @endcanAnyPermission

            @if (LD::checkFeatureFlag('tracker-audit'))
            <a href="{{route('trackersAudit')}}" title="Tracker Audit"
                class="bg-sidebar list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-history fa-fw mr-3"></span>
                    <span class="menu-collapsed">Tracker Audit</span>
                </div>
            </a>
            @endif

            @if (LD::checkFeatureFlag('manage-message-center'))
            @canPermission('messagecenter.list')
            <a href="{{route('messagecenter.list')}}" title="Message Center"
                class="bg-sidebar list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-envelope-open fa-fw mr-3"></span>
                    <span class="menu-collapsed">Message Center</span>
                </div>
            </a>
            @endcanPermission
            @endif

            @if (LD::checkFeatureFlag('gp-calculator-main-page'))
            @canPermission('gpCalculator.list')
            <a href="{{route('gpCalculatorV2.list')}}" title="GP Calculator"
                class="bg-sidebar list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-calculator fa-fw mr-3"></span>
                    <span class="menu-collapsed">GP Calculator</span>
                </div>
            </a>
            @endcanPermission
            @endif

            <li class="list-group-item sidebar-separator-title text-muted d-flex align-items-center menu-collapsed">
            </li>


            @canAnyPermission(['schedules','manageusers','managemap','manageipeaattachment.grid','emailtemplate.grid',
            'manageroles','manageip','coresetting.setting','map.ignoregrid', 'region', 'jobcategory',
            'manageafterhoursclass','manageafterhoursallclass', 'fieldevaluation.list', 'immunization.schedule.manage'])
            <a href="#submenu" title="Admin" data-toggle="collapse" aria-expanded="false"
                class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-male fa-fw mr-3"></span>
                    <span class="menu-collapsed">Admin</span>
                    <span class="submenu-icon ml-auto"></span>
                </div>
            </a>
            <!-- Submenu content -->
            <div id='submenu' title="Manage Users" class="collapse sidebar-submenu">
                @canAnyPermission(['manageusers', 'manageroles', 'manageip', 'coresetting.setting',
                 'emailtemplate.grid', 'region', 'jobcategory', 'hubcities','payerrates.list', 'managerenewablesrules',
                 'manageCustomFields', 'manageTextTemplate', 'managePayer', 'managekantimefacility',
                 'immunization.schedule.manage'])
                    <a href="#configurationsubmenu" title="Configurations" data-toggle="collapse" aria-expanded="false"
                        class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">Configurations</span>
                            <span class="submenu-icon ml-auto"></span>
                        </div>
                    </a>
                    <div id='configurationsubmenu' title="" class="collapse sidebar-submenu">

                        @canPermission('manageusers')
                        <a href="{{route('manageusers')}}" class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">Users</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission



                        @canPermission('manageroles')
                        <a href="{{route('manageroles')}}" title="Manage Roles"
                            class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">Roles</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission

                        @canPermission('manageip')
                        <a href="{{route('loginrestrictions')}}" title="Manage IP"
                            class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">Manage IP</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission

                        @canPermission('coresetting.setting')
                        <a href="{{route('coresetting.setting')}}" title="Core Settings"
                            class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">Core Settings</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission

                        @canPermission('emailtemplate.grid')
                        <a href="{{route('emailtemplate.grid')}}" title="IPEA Email"
                            class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">Branches</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission

                        @canPermission('managekantimefacility')
                        <a href="{{route('view.kantimefacility')}}" class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">Kantime Facility Data</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission

                        @if(LD::checkFeatureFlag('manage_regions'))
                            @canPermission('region')
                            <a href="{{route('region')}}" title="Manage Region"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Region</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif

                        @if(LD::checkFeatureFlag('manage_job_categories'))
                            @canPermission('jobcategory')
                            <a href="{{route('jobcategory')}}" title="Manage Job Categories"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Job Categories</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif

                        @if(LD::checkFeatureFlag('manage-hub-cities'))
                            @canPermission('hubcities')
                            <a href="{{route('hubcities')}}" title="Manage Hub Cities"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Hub Cities</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif

                        @canPermission('payerrates.list')
                        <a href="{{route('payerrates.list')}}"
                        class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">Manage Payer Rates</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission

                        @if(LD::checkFeatureFlag('Manage-renewables-rules'))
                            @canPermission('managerenewablesrules')
                            <a href="{{route('renewablesrule')}}" title="Renewables Rules"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Renewable Rules</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif
                        @if(LD::checkFeatureFlag('manage-custom-fields'))
                            @canPermission('manageCustomFields')
                            <a href="{{route('manageCustomFields')}}" title="Manage Custom Fields"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Manage Custom Fields</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif
                        @if(LD::checkFeatureFlag('manage-text-templates'))
                            @canPermission('manageTextTemplate')
                            <a href="{{route('manageTextTemplate')}}" title="Manage Templates"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Manage Templates</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif
                        @if(LD::checkFeatureFlag('manage-text-template-category'))
                            @canPermission('manageTextTemplate')
                            <a href="{{route('manageTemplateCategory')}}" title="Manage Categories"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Manage Categories</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif
                        @if(LD::checkFeatureFlag('manage-payer-page'))
                            @canPermission('managePayer')
                            <a href="{{route('managePayer')}}" title="Manage Payer"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Manage Payer</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif
                        {{-- VBC module, hidden from prod --}}
                        @if (config('vbc.is_available'))
                            @canPermission('immunization.schedule.manage')
                            <a href="{{route('immunization.schedule.manage')}}" title="Manage Immunization Schedule"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Manage Immunization Schedule</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif
                    </div>
                @endcanPermission
                @canAnyPermission(['managemap', 'map.ignoregrid'])
                <a href="#mapssubmenu" title="maps" data-toggle="collapse" aria-expanded="false"
                    class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Maps</span>
                        <span class="submenu-icon ml-auto"></span>
                    </div>
                </a>
                <div id='mapssubmenu' title="" class="collapse sidebar-submenu">
                        @canPermission('managemap')
                        <a href="{{route('managemap')}}" title="Manage Maps"
                            class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">Manage Maps</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission
                            @canPermission('map.ignoregrid')
                            <a href="{{route('map.ignoregrid')}}" title="Maps Ignore"
                                class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Maps Ignore</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                </div>
                @endcanPermission
                @canAnyPermission(['manageipeaattachment.grid', 'managewhiteboard',
                'schedules', 'managetrachventclass','recruiterscorecard'])
                    <a href="#recruitingsubmenu" title="Recruiting" data-toggle="collapse" aria-expanded="false"
                        class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">Recruiting</span>
                            <span class="submenu-icon ml-auto"></span>
                        </div>
                    </a>
                    <div id='recruitingsubmenu' title="" class="collapse sidebar-submenu">
                        @canPermission('manageipeaattachment.grid')
                        <a href="{{route('manageipeaattachment.grid')}}" title="IPEA Attachment"
                            class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">IPEA Attachment</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission

                        @if(LD::checkFeatureFlag('manage-whiteboard'))
                            @canPermission('managewhiteboard')
                                <a href="{{route('managewhiteboard')}}" title="Manage Whiteboard"
                                    class="list-group-item list-group-item-action bg-sidebar">
                                    <span class="menu-collapsed">Whiteboard</span>
                                    <span class="submenu-icon ml-auto"></span>
                                </a>
                            @endcanPermission
                        @endif

                        @canPermission('schedules')
                        <a href="{{route('schedules')}}" title="Schedules"
                            class="list-group-item list-group-item-action bg-sidebar">
                            <span class="menu-collapsed">Schedules</span>
                            <span class="submenu-icon ml-auto"></span>
                        </a>
                        @endcanPermission

                        @if (LD::checkFeatureFlag('schedule_trach_vent_tpn_class'))
                            @canPermission('managetrachventclass')
                            <a href="{{route('managetrachventclass')}}"
                            title="Manage Trach/vent Class" class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Trach/Vent Classes</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
                            @endcanPermission
                        @endif

                        @if (LD::checkFeatureFlag('add-recruiter-scorecard-menu'))
							@canPermission('recruiterscorecard')
                            <a href="{{route('recruiterscorecard')}}"
                            title="Manage Scorecards" class="list-group-item list-group-item-action bg-sidebar">
                                <span class="menu-collapsed">Manage Scorecards</span>
                                <span class="submenu-icon ml-auto"></span>
                            </a>
							@endcanPermission
                        @endif
                    </div>
                @endcanPermission
                @if (LD::checkFeatureFlag('after-hours-schedules'))
                    @canPermission(['manageafterhoursclass','manageafterhoursallclass'])
                    <a href="{{route('manageafterhours')}}" title="After Hours Shifts"
                    class="list-group-item list-group-item-action bg-sidebar">
                        <span class="menu-collapsed">After Hours Shifts</span>
                        <span class="submenu-icon ml-auto"></span>
                    </a>
                    @endcanPermission
                @endif
                    @canPermission(['fieldevaluation.list'])
                    <a href="{{route('fieldevaluation.list')}}" title="Field Evaluations"
                    class="list-group-item list-group-item-action bg-sidebar">
                        <span class="menu-collapsed">Field Evaluations</span>
                        <span class="submenu-icon ml-auto"></span>
                    </a>
                    @endcanPermission
                @if (LD::checkFeatureFlag('manage-message-attachments'))
                    @canPermission(['managemessageattachment.list'])
                    <a href="{{route('managemessageattachment.list')}}" title="SMS Attachments"
                    class="list-group-item list-group-item-action bg-sidebar">
                        <span class="menu-collapsed">SMS Attachments</span>
                        <span class="submenu-icon ml-auto"></span>
                    </a>
                    @endcanPermission
                @endif
                @if (LD::checkFeatureFlag('merge-duplicate-client'))
                    @canPermission(['client.mergeClient'])
                    <a href="{{route('client.mergeClient')}}" title="Merge Duplicate Client"
                    class="list-group-item list-group-item-action bg-sidebar">
                        <span class="menu-collapsed">Merge Duplicate Client</span>
                        <span class="submenu-icon ml-auto"></span>
                    </a>
                    @endcanPermission
                @endif
                @if (LD::checkFeatureFlag('data-connections-sync-page'))
                    <a href="{{route('managesyncdataconnections')}}" title="Data Connections"
                    class="list-group-item list-group-item-action bg-sidebar">
                        <span class="menu-collapsed">Data Connections</span>
                        <span class="submenu-icon ml-auto"></span>
                    </a>
                @endif
                @if (LD::checkFeatureFlag('consent-template-tracking-main'))
                    @canPermission('consent.tracking.index')
                    <a href="{{route('consent.tracking.index')}}" title="Consent Tracking Templates"
                       class="list-group-item list-group-item-action bg-sidebar">
                        <span class="menu-collapsed">Consent Tracking Templates</span>
                        <span class="submenu-icon ml-auto"></span>
                    </a>
                    @endcanPermission
                @endif
            </div>
            @if(LD::checkFeatureFlag('manage-project-tags'))
            <a href="#billingSubMenu2" title="Billing" data-toggle="collapse" aria-expanded="false"
               class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-file-text-o fa-fw mr-3"></span>
                    <span class="menu-collapsed">Billing</span>
                    <span class="submenu-icon ml-auto"></span>
                </div>
            </a>
            <!-- Submenu content -->
            <div id='billingSubMenu2' title="Forms" class="collapse sidebar-submenu">
                <a href="{{route('managetags')}}" title="Tags"
                   class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">Project Tags</span>
                    </div>
                </a>
            </div>
            @endif
            @endcanAnyPermission
            @if(LD::checkFeatureFlag('manage-payer-page'))
                @canPermission('managePayer')
                <div id='billingSubMenu2' title="Forms" class="collapse sidebar-submenu">
                <a href="{{route('managePayer')}}" title="Manage Payer"
                   class="list-group-item list-group-item-action bg-sidebar">
                    <span class="menu-collapsed">Manage Payer</span>
                    <span class="submenu-icon ml-auto"></span>
                </a>
                </div>
                @endcanPermission
            @endif
            @if (LD::checkFeatureFlag('feature-flag-page'))
            @canPermission('managefeatureflag')
            <a href="{{route('managefeatureflag')}}" title="Feature Flag"
                class="bg-sidebar list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-flag-o fa-fw mr-3"></span>
                    <span class="menu-collapsed">Feature Flag</span>
                </div>
            </a>
            @endcanPermission
            @endif
            @canPermission('adjustments')
            <a href="{{route('adjustment.index')}}" class="list-group-item list-group-item-action bg-sidebar">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-file-text-o fa-fw mr-3"></span>
                    <span class="menu-collapsed">Manage Adjustments</span>
                </div>
            </a>
            @endcanPermission
            <a href="#profileSubMenu2" title="My Account" data-toggle="collapse" aria-expanded="false"
                class="bg-sidebar list-group-item list-group-item-action flex-column align-items-start">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-user fa-fw mr-3"></span>
                    <span class="menu-collapsed">My Account</span>
                    <span class="submenu-icon ml-auto"></span>
                </div>
            </a>
            <!-- Submenu content -->
            <div id='profileSubMenu2' title="Forms" class="collapse sidebar-submenu">
                <a href="{{route('users.profile')}}" title="My Profile"
                    class="bg-sidebar list-group-item list-group-item-action">
                    <div class="d-flex w-100 justify-content-start align-items-center">
                        <span class="menu-collapsed">My Profile</span>
                    </div>
                </a>
                @if (LD::checkFeatureFlag('self-evaluation'))
					@php
						$provider = MssqlProviders::where('AOC_Email', auth()->user()->email)->get();
					@endphp
                    <a href="javascript:{{
							$provider->count() == 0 ? "void(0)" : "selfEvaluation('')"
						}};" title="Self Evaluation"
						style="{{
							$provider->count() == 0 ? "cursor:not-allowed" : ""
						}};"
                        class="bg-sidebar list-group-item list-group-item-action popup-item">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="menu-collapsed">Self Evaluation</span>
                        </div>
                    </a>
                @endif
            </div>
            <a href="{{route('logout')}}" title="Logout" class="bg-sidebar list-group-item list-group-item-action">
                <div class="d-flex w-100 justify-content-start align-items-center">
                    <span class="fa fa-sign-out fa-fw mr-3"></span>
                    <span class="menu-collapsed">Logout</span>
                </div>
            </a>
            <li class="list-group-item sidebar-separator-title text-muted d-flex align-items-center menu-collapsed">
            </li>
            <div class="d-flex w-100 justify-content-start align-items-center">
                @if(LD::checkFeatureFlag('change-submit-Suggestion-and-problem-link'))
                    <a href="https://aoc.freshservice.com/support/freddy" title="Browse help articles or submit a support request."
                       class="bg-sidebar list-group-item list-group-item-action" target="_blank">
                        <div class="d-flex w-100 justify-content-start align-items-center">
                            <span class="fa fa-question-circle fa-fw mr-3"></span>
                            <span class="menu-collapsed">Need help</span>
                        </div>
                    </a>
                @else
                    <a href="https://aoc.freshservice.com/support/catalog/items/50" title="Submit a Suggestion"
                    class="bg-sidebar extra-sidemenu popup-item" target="_blank">
                    <span class="menu-collapsed">Submit a Suggestion</span></a>|<a
                    href="https://aoc.freshservice.com/support/tickets/new" title="Submit a Problem"
                    class="bg-sidebar extra-sidemenu popup-item" target="_blank">
                    <span class="menu-collapsed">Submit a Problem</span></a>
                @endif
            </div>
        </ul><!-- List Group END-->

    </div><!-- sidebar-container END -->
</section>

<script>
function SidebarCollapse() {
    $('.menu-collapsed').toggleClass('d-none');
    $('.sidebar-submenu').toggleClass('d-none');
    $('.submenu-icon').toggleClass('d-none');
    $('.angel-logo').toggleClass('d-none');
    // $('.angel-logo').toggleClass('collapsed-logo');

    $('#sidebar-container').toggleClass('sidebar-expanded sidebar-collapsed');
    $('#aboutContent').toggleClass('sidebar-expanded sidebar-collapsed');

    // Treating d-flex/d-none on separators with title
    var SeparatorTitle = $('.sidebar-separator-title');
    if (SeparatorTitle.hasClass('d-flex')) {
        SeparatorTitle.removeClass('d-flex');
    } else {
        SeparatorTitle.addClass('d-flex');
    }

    // Collapse/Expand icon
    $('#collapse-icon').toggleClass('fa-angle-double-left fa-angle-double-right');
}
$(document).ready(function() {
    // Collapse/Expand icon
    $('#collapse-icon').addClass('fa-angle-double-left');

    // Collapse click
    $('[data-toggle=sidebar-colapse]').click(function() {
        SidebarCollapse();
    });
});
</script>

@if (LD::checkFeatureFlag('nrr-session-filter-retention'))
    <script>
      document.addEventListener('DOMContentLoaded', function () {

        const logoutLink = document.querySelector('a[href*="logout"]');

        if (!logoutLink) return;

        logoutLink.addEventListener('click', function () {
          Object.keys(sessionStorage).forEach(key => {
            if (key.startsWith('nrr.session.filters.')) {
              sessionStorage.removeItem(key);
            }
          });
        });

      });
    </script>
@endif
