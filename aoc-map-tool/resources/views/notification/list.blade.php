<?php
    use App\Helpers\LD;
    use App\Helpers\H;
    use App\Helpers\Calendar;

    $isCleared = request()->is('notification/list/cleared') ? true : false;
    $pageType = 0;
    if(request()->is('notification/list/cleared')) {
        $pageType = 1;
    } elseif(request()->is('notification/sentlist')) {
        $pageType = 2;
    }
?>

@extends('layouts.myaccount')
@section('title', 'Notification')
@section('content')

<link href="{{ mix('css/jquery.datetimepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/jquery.datetimepicker.full.min.js') }}"></script>
<style>
.m-b-50 {
    margin-bottom: 50px;
}

.dark-link {
    color: #333;
}

.heading-line {
    position: relative;
    padding-bottom: 5px;
    text-align: left;
    margin-right: 30px;
    margin-bottom: 0;
}

.selected:after {
    content: "";
    height: 4px;
    width: 100%;
    background-color: #215eac;
    position: absolute;
    bottom: 0;
    left: 0;
}

.notification-ui_dd-content {
    margin-bottom: 15px;
}

.notification-list {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    -webkit-box-pack: justify;
    -ms-flex-pack: justify;
    justify-content: space-between;
    padding: 20px;
    margin-bottom: 7px;
    background: #fff;
    -webkit-box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.06);
}

.notification-list--unread {
    border-left: 2px solid #215eac;
}

.xdsoft_datetimepicker .xdsoft_calendar td.xdsoft_default,
.xdsoft_datetimepicker .xdsoft_calendar td.xdsoft_current,
.xdsoft_datetimepicker .xdsoft_timepicker .xdsoft_time_box>div>div.xdsoft_current {
    background: #215eac !important;
}

.xdsoft_datetimepicker .xdsoft_calendar td:hover,
.xdsoft_datetimepicker .xdsoft_timepicker .xdsoft_time_box>div>div:hover {
    background: #215eac !important;
}

.notification-list .notification-list_content {
    display: -webkit-box;
    display: -ms-flexbox;
    display: flex;
    text-align: left;
}


.notification-list .notification-list_content .notification-list_detail p {
    margin-bottom: 5px;
    line-height: 1.2;
}

.notification-section .buttons-cnt a {
    width: 100%;
    float: left;
    padding: 5px;
}

.notification-section .read .fa {
    color: #0f0
}

.notification-section .unread .fa {
    color: #6c757d
}


.notification-section button.dropdown-toggle {
    border: none;
    background: transparent;
}

.notification-section .dropleft .dropdown-toggle:before {
    display: none;
}

.notification-section .dropleft {
    float: left;
    width: 100%
}

.notification-type {
    margin-right: 20px;
    position: relative;
    text-decoration: none !important;
}

.notification-type h5 {
    font-size: 16px;
    margin-bottom: 0;
}

.notification-section .fa.fa-undo {
    color: #fff;
    padding: 10% 12%;
    background: #215eac;
    border-radius: 25px;
    font-size: 20px;
}

.notification-section .fa {
    font-size: 24px;
}
</style>

<section class="col-md-12 notification-section">
    <div class="container">
        <div class="notification-header col-md-12 p-0 d-flex m-b-50">
            <h3 class="heading-line"><i class="fa fa-bell text-muted mr-0"></i> Notifications</h3>
            <a href="{{route('notification.list')}}" class="notification-type {{ ($pageType ==0) ? 'selected' : '' }} ">
                <h5>New</h5>
            </a>
            <a href="{{route('notification.clearedList')}}"
                class="notification-type {{ ($pageType ==1) ? 'selected' : '' }}">
                <h5>Cleared</h5>
            </a>
            <a href="{{route('notification.sentlist')}}"
                class="notification-type {{ ($pageType ==2) ? 'selected' : '' }}">
                <h5>Sent</h5>
            </a>
        </div>
        @if(Session::has('message'))
        <div class="alert alert-success displayMsg">{{ Session::get('message') }}</div>
        @endif
        <div class="col-md-12 pb-1 displayMsg" style="display:none;">
        </div>
        @if($pageType == 0 && H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew('notification.mark-multiple-as-read') && count($notifications) > 0)
        <div class="row d-flex pb-1" style="height:50px;">
            <div class="col-md-2 text-left"><input class="form-input" id="select-all-checkbox-new_notification" type="checkbox" name="select-new_notification" value="0">&nbsp;&nbsp;<label for="select-all-checkbox-new_notification">Select all on this page</label></div>
            <div class="col-md-3 text-left"><input class="form-input" id="select-all-unread-checkbox-notification" type="checkbox" name="select-new_notification" value="0">&nbsp;&nbsp;<label for="select-all-unread-checkbox-notification">Select all Unread Notifications</label></div>
            <div class="col-md-7 pl-1 text-left pb-1" id="clear-all-button-div"></div>
        </div>
        @endif
        @foreach($notifications as $notification)
        <div class="notification-ui_dd-content d-flex">
            @if($pageType == 0 && H::getHelper('UserPermissionResolver')->isRoleHasPermissionNew('notification.mark-multiple-as-read'))
            <div class="pt-4 pr-2">
                <input type="checkbox" name="checkbox-new_notification[]" class="form-input td select-all-new_notification" value="{{$notification->id}}">
            </div>
            @endif
            <div class="notification-list notification-list--unread col-md-11">
                <div class="notification-list_content col-md-12 p-0 d-flex">
                    <div class="notification-list_detail col-md-10">
                        @if(in_array($notification->type, ['tracker-activity', 'trackerDetails', 'tracker']) &&
                        $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="openRecruiterDetails('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "ipea" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="updateNurseIpeaInfo('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "candidate" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="openCandidateDetails('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "NeedToHire" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="openNthChecklistDetails('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "client" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="getClientDetails('{{$notification->related_item}}', 'client')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "nthScheduleChange" && $notification->related_item != '' && isset($hasNTHScheduleChangePermission))
                        <a href="javascript:void(0);"
                            onclick="getClientDetails('{{$notification->related_item}}', 'nthScheduleChange')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "intake" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="getClientDetails('{{$notification->related_item}}', 'clientintake')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "AfterHours" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="getAfterHoursInfo('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "AfterHoursCanceled" && $notification->related_item != '')
                        <a href="{{env('APP_URL').'/afterhours/calendar'}}" target="_blank"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "supplyRequests" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="getSupplyRequestsDetails('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "office_eval_edit" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="editOfficeEvaluation('{{$notification->related_item}}','edit')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "office_eval_view" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="editOfficeEvaluation('{{$notification->related_item}}','view')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "nthAccount" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="openNthChecklistDetails('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "statusChange" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="openSatusChange('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "ReimbursementRequest" && $notification->related_item != '')
                        <a href="{{route('payerrates.list')}}" target="_blank"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "renewable" && $notification->related_item != '')
                        @php $providerId = H::getProviderIdFromRenewableId($notification->related_item) @endphp
                        <a href="javascript:void(0);" onclick="getClinicianProfile('{{ $providerId }}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "clinician" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                            onclick="getClinicianProfile('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "supersededLead" && $notification->related_item != '')
                        <a href="javascript:void(0);"
                               onclick="getNthLeadsInquiriesDetail('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type === "trachVentScheduled" && $notification->related_item !== '')
                        <a href="javascript:void(0);"
                               onclick="goToTrachVentClassInfo('{{$notification->related_item}}')"><b>{{$notification->title}}</b>
                        </a>
                        @elseif($notification->type == "Clinician" && $notification->related_item != '')
                            @if(LD::checkFeatureFlag('make-status-change-request-notification-to-clickable'))
                                <a href="javascript:void(0);"
                                    onclick="getClinicianProfile('{{$notification->related_item}}')">
                                    <b>{{$notification->title}}</b>
                                </a>
                            @else
                                <b>{{$notification->title}}</b>
                            @endif
                        @elseif($notification->type == "statusChangeRequest" && $notification->related_item != '')
                            @if(LD::checkFeatureFlag('create-notification-for-statuschange-request-update'))
                                <a href="javascript:void(0);"
                                    onclick="getClinicianProfile('{{$notification->related_item}}')">
                                    <b>{{$notification->title}}</b>
                                </a>
                            @endif
                        @elseif($notification->type == "invoice" && $notification->related_item != '')
                            <a href="javascript:void(0);"
                                onclick="getInvoiceDetails('{{$notification->related_item}}',null)"><b>{{$notification->title}}</b>
                            </a>
                        @elseif($notification->type == "billing" && $notification->related_item != '')
                            @if(LD::checkFeatureFlag('send-one-notifications-per-week-row-unbilled-schedules'))
                                <a href="javascript:void(0);" onclick="getBillingDetails('{{$notification->misc1??$notification->related_item}}',null)"><b>{{$notification->title}}</b></a>
                            @else
                                @if(LD::checkFeatureFlag('change-notification-unbilled-schedules-billing-dashboard'))
                                    <a href="javascript:void(0);" onclick="getBillingDetails('{{$notification->related_item}}',null)"><b>{{$notification->title}}</b></a>
                                @else
                                    <b>{{$notification->title}}</b>
                                @endif
                            @endif
                        </a>
                        @else
                        <b>{{$notification->title}}</b>
                        @endif
                        @if(LD::checkFeatureFlag('tagging-branch-notes'))
                        <p class="text-muted">{!! $notification->notes !!}  @if($pageType == 2)<strong>({{ $notification->receiver_name }})</strong>@endif
                        @else
                        <p class="text-muted">{{$notification->notes}} @if($pageType == 2)<strong>({{ $notification->receiver_name }})</strong>@endif
                        @endif
                        </p>
                    </div>
                    <div class="col-md-2 text-right">
                        <p class="text-muted mt-2">
                            <small>{{Calendar::getNotificationFormatDateTime($notification->created_at)}}</small>
                        </p>
                    </div>
                </div>
            </div>
            @if($pageType !=2)
                @if(!$isCleared)
                <div class="col-md-1 p-3 buttons-cnt">
                    <a href="javascript:void(0);" class="unread" data-id="{{$notification->id}}">
                        <i class="fa fa-check-square-o" aria-hidden="true"></i>
                    </a>
                    <div class="dropleft">
                        <button type="button" class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true"
                            aria-expanded="false">
                            <i class="fa fa-clock-o text-muted" aria-hidden="true"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item snooze" data-id="{{$notification->id}}" data-value="15 minute"
                                href="javascript:void(0);">15 Minutes</a>
                            <a class="dropdown-item snooze" data-id="{{$notification->id}}" data-value="1 hour"
                                href="javascript:void(0);">1 hour</a>
                            <a class="dropdown-item snooze" data-id="{{$notification->id}}" data-value="5 hours"
                                href="javascript:void(0);">Later today</a>
                            <a class="dropdown-item snooze" data-id="{{$notification->id}}" data-value="1 day"
                                href="javascript:void(0);">Tomorrow</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item pick-date" data-id="{{$notification->id}}" href=" javascript:void(0);">
                                <input type="text" value="" data-id="{{$notification->id}}" class="datetime"
                                    style="position: absolute;opacity: 0;" name="datetime" id="datetime" />
                                Pick Date & Time
                            </a>
                        </div>
                    </div>
                </div>
                @else
                <div class="col-md-1 p-3 buttons-cnt">
                    <a href="javascript:void(0);" class="undo" data-id="{{$notification->id}}">
                        <i class="fa fa-undo" aria-hidden="true"></i>
                    </a>
                </div>
                @endif
            @elseif($pageType == 2)
            <div class="col-md-1 p-3 buttons-cnt">
                @if($notification->cleared == 1)
                    <a href="javascript:void(0);" title="Cleared" id="clear{{$notification->id}}">
                        <i class="fa fa-check-square-o" aria-hidden="true"></i>
                    </a>
                    <a href="javascript:void(0);" class="resend" data-id="{{$notification->id}}" id="resend{{$notification->id}}" title="Un-Clear">
                        <i class="fa fa-undo" aria-hidden="true"></i>
                    </a>
                    <a href="javascript:void(0);" title="New" data-id="{{$notification->id}}" id="new{{$notification->id}}" style="display: none;">
                        <i class="fa fa-square-o" aria-hidden="true"></i>
                    </a>
                @else
                <a href="javascript:void(0);" title="New" data-id="{{$notification->id}}" id="new{{$notification->id}}">
                    <i class="fa fa-square-o" aria-hidden="true"></i>
                </a>
                @endif
            </div>
            @endif
        </div>
        @endforeach

    </div>
</section>

<script>
function goToTrachVentClassInfo(occurrenceId) {
    if (window.location.pathname === '{{ parse_url(route('trachventclass.calender'), PHP_URL_PATH) }}') {
        getTrachVentClassInfo(occurrenceId);
    } else {
        window.location.href = '{{ route('trachventclass.calender') }}?occurrence_id=' + occurrenceId;
    }
}
$(document).on('click', '.pick-date', function(e) {
    e.stopPropagation();
});
$('.datetime').datetimepicker({
    format: 'Y-m-d H:i:s',
    lang: 'en',
    onSelectTime: function(time, input) {
        console.log(input.val(), input.attr('data-id'));
        if (typeof input.attr('data-id') != 'undefined' && input.attr('data-id') != '' && typeof input
            .val() != 'undefined' && input.val() != '') {
            addSnooze(input.attr('data-id'), input.val(), true, function(data) {
                if (data.status) {
                    input.closest('.notification-ui_dd-content').remove();
                }
            });
        }
    }
});
$('.unread').click(function(e) {
    var id = $(this).attr('data-id');
    var obj = $(this);
    if (typeof id != 'undefined' && id != '') {
        markAsRead(id, function(data) {
            if (data.status) {
                obj.closest('.notification-ui_dd-content').remove();
            }
        });
    }
});
$('.undo').click(function(e) {
    var id = $(this).attr('data-id');
    var obj = $(this);
    if (typeof id != 'undefined' && id != '') {
        undoNotification(id, function(data) {
            if (data.status) {
                obj.closest('.notification-ui_dd-content').remove();
            }
        });
    }
});
$('.snooze').click(function(e) {
    var id = $(this).attr('data-id');
    var value = $(this).attr('data-value');
    var obj = $(this);
    if (typeof id != 'undefined' && id != '' && typeof value != 'undefined' && value != '') {
        addSnooze(id, value, false, function(data) {
            if (data.status) {
                obj.closest('.notification-ui_dd-content').remove();
            }
        });
    }
});
$('.resend').click(function(e) {
    var id = $(this).attr('data-id');
    var obj = $(this);
    if (typeof id != 'undefined' && id != '') {
        markAsUnread(id);
    }
});
function addSnooze(notificationId, datetime, isCustomDatetime, cb) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('notification.addSnooze')}}",
        type: 'post',
        data: {
            id: notificationId,
            datetime: datetime,
            isCustomDatetime: isCustomDatetime,
            _token: _token
        },
        success: function(data) {
            cb(data);
        }
    });
}

function markAsRead(notificationId, cb) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('notification.markAsRead')}}",
        type: 'get',
        data: {
            id: notificationId,
            _token: _token
        },
        success: function(data) {
            cb(data);
        }
    });
}

function undoNotification(notificationId, cb) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('notification.undoNotification')}}",
        type: 'get',
        data: {
            id: notificationId,
            _token: _token
        },
        success: function(data) {
            cb(data);
        }
    });
}
function markAsUnread(notificationId) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('notification.undoNotification')}}",
        type: 'get',
        data: {
            id: notificationId,
            _token: _token
        },
        success: function(data) {
            if (data.status) {
                $('#resend'+notificationId).hide();
                $('#clear'+notificationId).hide();
                $('#new'+notificationId).show();
            }
        }
    });
}
$(document).ready(function(){
    $('#select-all-checkbox-new_notification').click(function() {
        $('#select-all-unread-checkbox-notification').prop('checked', false);
        $('.select-all-new_notification').prop('checked', $(this).prop("checked"));
        showHideUdateButtonForNotification($(this).is(':checked'),'currentpage');
    });
    $('#select-all-unread-checkbox-notification').click(function() {
        $('#select-all-checkbox-new_notification').prop('checked', false);
        $('.select-all-new_notification').prop('checked', $(this).prop("checked"));
        showHideUdateButtonForNotification($(this).is(':checked'),'allrecords');
    });
    $('.select-all-new_notification').bind('click', function() {
        showHideUdateButtonForNotification($('.select-all-new_notification:checked').length ? true : false,'few');
    });
    setTimeout(function(){
        $('.displayMsg').hide();
    }, 3000);
});
function showHideUdateButtonForNotification(isChecked,selectType) {
    $('#clear-all-button-div div.updateall').remove();
    if (isChecked) {
        var html = '<div style="display: inline-block;" class="dataTables_length updateall">';
        html += '<button type="button" onclick="clearNotification(this,\''+selectType+'\')" class="btn btn-primary">Clear</button></div>';
        $('#clear-all-button-div').prepend(html);
    }
}
</script>

@endsection
