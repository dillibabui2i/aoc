<?php

use Illuminate\Support\Facades\Route;
use App\Helpers\H;
use App\Helpers\LD;

$loggedInUser = H::getLoggedInUser();
$action = Route::getCurrentRoute()->getActionName();
$actionParts = explode("@", $action);
$actionName = isset($actionParts[1]) ? $actionParts[1] : $action;



$mapActive = '';
$tableActive = '';
if ($actionName == 'showMap' || $actionName == 'showMap2' || $actionName == 'mapIntro') {
    $mapActive = "active";
}

if ($actionName == 'showTable' || $actionName == 'showTableMap2') {
    $tableActive = 'active';
}
$loggedInUserData = [];
if (!empty($loggedInUser)) {
    $loggedInUserData = ['name' => $loggedInUser['name'], 'email' => $loggedInUser['email'], 'ip' => $_SERVER['REMOTE_ADDR']];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <?php if (env('APP_ENV') != 'production') { ?>
        <link rel="shortcut icon" href="{{ asset('resources/images/faviconred.png') }}" />
    <?php }else {?>
        <link rel="shortcut icon" href="{{ asset('resources/images/favicon.png') }}" />
    <?php }?>
    <link rel="preconnect" href="https://fonts.googleapis.com" integrity="sha256-k4yxU2BV0cc04MA+Aj0OlUC1n5t3as/RDH4QfPnH0z0=" crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.gstatic.com" integrity="sha256-I5JukYXY1DwCgHqDj/s3PMGXdyYJSk5GgHxmranddmA=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100;0,300;0,400;0,500;0,700;0,900;1,100;1,300;1,400;1,500;1,700;1,900&display=block" integrity="sha256-j7vOETZG08NNRPKfyQND7PvDQR83cQ/1b1yaCVI0BSA=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&display=block" integrity="sha256-btzlqBMUKqG4hCG6ugjU4oHmNMUkKv/HXHImld4Is2M=" crossorigin="anonymous">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css" integrity="sha256-eZrrJcwDc/3uDhsdt61sL2oOBY362qM3lon1gyExkL0=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.min.css" integrity="sha256-Xmvvd2qyBwEhXxVetnuRd6P824S8MwqWY98eqSRLzCY=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.2.9/css/responsive.dataTables.min.css" integrity="sha256-Y/AdBW1nhvzPowuT1lvF4PkY6QR+nqYzBcbmkDCG30Y=" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.datatables.net/fixedheader/3.2.4/css/fixedHeader.dataTables.min.css" integrity="sha256-N0OMoGMavrVJsj9nOvuyNgRw/4OG+xB+La2fzlOkOkI=" crossorigin="anonymous">
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/themes/base/jquery-ui.min.css"
        integrity="sha512-ELV+xyi8IhEApPS/pSj66+Jiw+sOT1Mqkzlh8ExXihe4zfqbWkxPRi8wptXIO9g73FSlhmquFlUOuMSoXz5IRw=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer" />
    <link href="{{ mix('css/myaccount.css') }}" rel="stylesheet">
    <link href="{{ mix('css/extra_style.css') }}?v={{ filemtime(public_path('css/extra_style.css')) }}" rel="stylesheet">
    <link href="{{ mix('css/bootstrap-multiselect.min.css') }}" rel="stylesheet">
    <!-- <link href="{{ mix('css/bootstrap-datepicker.min.css') }}" rel="stylesheet"> -->

    <script>
    var baseUrl = "<?php echo \URL::to('/'); ?>";
    var logUrl = "<?php echo route('misc.log'); ?>"
    </script>

    <title>AOC - @yield('title')</title>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"
        integrity="sha512-894YE6QWD5I59HgZOGReFYm4dnWc1Qt5NtvYSaNcOP+u1T9qYdvdihz0PPSiiqn/+/3e7Jo4EaG7TubfWGUrMQ=="
        crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <script src="{{ mix('js/app.js') }}"></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.13.2/jquery-ui.min.js"
        integrity="sha512-57oZ/vW8ANMjR/KQ6Be9v/+/h6bq9/l3f0Oc7vn6qMqyhvPd1cvKBRWWpzu0QoneImqr2SkmO4MSqU+RpHom3Q=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/jquery.validate.min.js"
        integrity="sha512-WMEKGZ7L5LWgaPeJtw9MBM4i5w5OSBlSjTjCtSnvFJGSVD26gE5+Td12qN5pvWXhuWaWcVwF++F7aqu9cvqP0A=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.20.0/additional-methods.min.js"
        integrity="sha512-TiQST7x/0aMjgVTcep29gi+q5Lk5gVTUPE9XgN0g96rwtjEjLpod4mlBRKWHeBcvGBAEvJBmfDqh2hfMMmg+5A=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js" integrity="sha256-lpQbyCSrPqrv7IZbdk1u4zJ3Ft/DUAIfZElc0Zi25Kw=" crossorigin="anonymous"></script>
    <script src="https://cdn.datatables.net/fixedheader/3.2.4/js/dataTables.fixedHeader.min.js" integrity="sha256-ri7GycJ5c18ATc0nwAl/ExYL/9ycSfakyEoGYpz7pCA=" crossorigin="anonymous"></script>
    <script type="text/javascript">
        let datatableMsg = 'Invalid data or session timed out. Please reload the page. ';
        datatableMsg += 'If the problem still exists please contact support.';
        $.fn.dataTable.ext.errMode = () => alert(datatableMsg);
    </script>
    <script src="{{ mix('js/bootstrap-multiselect.min.js') }}"></script>
    <script src="{{ mix('js/push.min.js') }}"></script>
    <script
        src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.8.3/tinymce.min.js"
        integrity="sha512-VCEWnpOl7PIhbYMcb64pqGZYez41C2uws/M/mDdGPy+vtEJHd9BqbShE4/VNnnZdr7YCPOjd+CBmYca/7WWWCw=="
        crossorigin="anonymous"
        referrerpolicy="no-referrer"></script>
        <script src="{{ mix('js/custom.js') }}"></script>
        <script src="{{ mix('js/confetti.js') }}"></script>
        <script src="{{ mix('js/ESSLog.js') }}"></script>
        <script src="{{ mix('js/globalLocationFilter.js') }}"></script>

        @push('page-scripts')
            {{-- Route list for the Vue app, emitted immediately before the bundle
                 that resolves its endpoints by name. Limited to the groups the
                 frontend actually calls (config/ziggy.php), not all ~850 routes. --}}
            @routes('vbc')
            <script src="{{ mix('js/vue-app.js') }}"></script>
        @endpush
</head>

<body class="login-page {{$actionName}}">

    <?php if (LD::checkFeatureFlag('app-loader')) { ?>
    <div id="loader">
        <div class="cv-spinner">
            <span class="spinner"></span>
        </div>
    </div>
    <?php } ?>


    <!-- Left Sidebar -->
        @include('layouts.newSidebar')

    <div
        class="container main-container <?php echo ($mapActive == 'active' || $tableActive == 'active') ? "filter-custom" : '' ?>">
        <header class='header'>
            <?php if (LD::checkFeatureFlag('red-banner-on-top')) { ?>
                <?php if (env('APP_ENV') != 'production') { ?>
                    <div class="red-banner"></div>
                <?php }?>
            <?php }?>
            <p class="text-right">
                <!-- <strong> Angel of Care</strong> -->
                <span>Logged in as <?php echo isset($loggedInUser['name']) ? $loggedInUser['name'] : '' ?></span>
            </p>
            <!-- <div class="logo">
                <a href="{{route('dashboard')}}"><img src="{{ asset('images/logo.png') }}" /> </a>
            </div> -->
        </header>
        <div class="login-cnt">
            <div class="right-side p-1">
                <?php
                $className = 'col-md-12';
                if ($mapActive == 'active' || $tableActive == 'active') {
                    $className = 'col-md-9';
                ?>
                <div class="col-md-3 pull-left px-0">
                    <!-- Filter Bar for Map View -->
                    @include('layouts.filterSidebar')
                </div>
                <?php } ?>
                <div class="<?php echo $className; ?> pull-left px-0">
                    @yield("content")
                </div>
            </div>
        </div>

        <footer>
            <p>© Copyright <?php echo date('Y'); ?> Angels of Care. All rights reserved.<a href="#">Privacy Policy</a></p>
        </footer>
    </div>
    <div id="getScheduleNextCode"></div>
    @if (LD::checkFeatureFlag('poop-sky-animation'))
    @include('layouts.poopSkyAnimation')
    @endif
    @if (LD::checkFeatureFlag('message-widget'))
    <?php if($actionName != 'fullPageView'){?>
        @include('messageWidget.messageWidget')
    <?php } ?>
    @endif

</body>
<?php if ($mapActive == 'active' || $tableActive == 'active') { ?>
<script>
$(document).ready(function() {
    SidebarCollapse();

    $(document).on('click', '#mobile-filter-btn', function(e) {
        $('.login-page .left-side').show('slideRight');
    });
    $(document).on('click', '#mobile-filter-close', function(e) {
        $('.login-page .left-side').hide('slideLeft');
    });

});
</script>

<?php } else { ?>
<script>
$(document).ready(function() {
    if ($(window).width() <= 768) {
        SidebarCollapse();
    }
});
</script>

<?php } ?>

<?php if (LD::checkFeatureFlag('app-loader')) { ?>
<script>
$(document).on({
    ajaxStart: function() {
        $("#loader").fadeIn(300);
    },
    ajaxStop: function() {
        $("#loader").fadeOut(300);
    },
    ajaxError: function(event, jqxhr, settings, exception) {
        console.log(exception, settings, "on expire");
        if (exception == 'Unauthorized') {
            window.location = '/';
        }
    }
});
</script>
<?php } ?>

<script>
window.scheduleNextSubmitLock = {
    busy: false,
    $locked: null,
    selector: '[onclick*="checkCandidateMatch"], [onclick*="setSchedule"]',
    acquire: function () {
        if (this.busy) {
            return false;
        }
        this.busy = true;
        this.$locked = $(this.selector).filter(':not(:disabled)').prop('disabled', true);

        return true;
    },
    release: function () {
        if (this.$locked) {
            this.$locked.prop('disabled', false);
            this.$locked = null;
        }
        this.busy = false;
    }
};

function showScheduleForm(tracker_id, bucket, clientid = '',show_save_next_btn=1) {

    let _token = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ route('getScheduleForm') }}",
        type: "POST",
        data: {
            tracker_id: tracker_id,
            bucket: bucket,
            clientid: clientid,
            show_save_next_btn: show_save_next_btn,
            _token: _token
        },
        success: function(response) {
            if (response) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(response);
                $('#schedule_next').modal('show');
                $(".modal:visible").length && $(document.body).addClass("modal-open");

            }
        },
    });
}

<?php if (LD::checkFeatureFlag('add-new-candidate')) { ?>
function showAddCandidateForm(jobCategoryType) {

    let _token = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ route('getAddCandidateForm') }}",
        type: "POST",
        data: {
            _token: _token,
            jobCategoryType: jobCategoryType
        },
        success: function(response) {
            if (response) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(response);
                $('#add_candidate').modal('show');
                $(".modal:visible").length && $(document.body).addClass("modal-open");

            }
        },
    });
}
<?php } ?>
<?php if (LD::checkFeatureFlag('oc-next-step')) { ?>

function showOrientationForm(tracker_id, bucket) {

    let _token = $('meta[name="csrf-token"]').attr('content');

    $.ajax({
        url: "{{ route('getOrientationForm') }}",
        type: "POST",
        data: {
            tracker_id: tracker_id,
            bucket: bucket,
            _token: _token
        },
        success: function(response) {
            if (response) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(response);
                $('#oc-next-step').modal('show');
                $(".modal:visible").length && $(document.body).addClass("modal-open");

            }
        },
    });
}
<?php } ?>

function openEmailTemplate(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('openemailtemplate')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $("#tempemailModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempemailModalCnt').html(data);
            $('#emailtemplate-popup').modal('show');
        },

    });
}

function openRecruiterDetails(id, arg = '', removeHideEvent = false) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('recruiterDetails')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
			if (removeHideEvent) {
				$(document).off("hidden.bs.modal", "#recruiterTrackerDetails");
			}
            if(arg == 'append'){
                $('span[data-name="bonus_amount"]').editable('option', 'value', $(data).find('span[data-name="bonus_amount"]').text());
            }else{
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#recruiterTrackerDetails').modal('show');
            }
        },
        error: function(xhr, status, error) {
            console.log(xhr, status, error, 'error');
        }
    });
}


function openNotes(entity_id, entity_type, notesTitle = '', type = '', page = '') {

    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('opennotes')}}",
        type: 'get',
        data: {
            entity_id: entity_id,
            entity_type: entity_type,
            type: type,
            notesTitle: notesTitle,
            page: page,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $("#tempModalCntNotes").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntNotes').html(data);
            $('#notes-popup').modal('show');
        }
    });
}


function getWhiteboardReport(day,whiteboard_row,userid,typeid,type) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('managewhiteboard.whiteboardreport')}}",
        type: 'get',
        data: {
            day: day,
            whiteboard_row: whiteboard_row,
            userid: userid,
            typeid: typeid,
            type: type,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $("#tempModalWhiteboard").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalWhiteboard').html(data);
            $('#whiteboard-popup').modal('show');
        }
    });
}
function addEvents(id = -1,current_mode='add') {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('schedules.addEvents')}}",
        type: 'get',
        data: {
            _token: _token,
            id: id,
            mode: current_mode
        },
        dataType: 'html',
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addEvents').modal('show');
        }
    });
}
$(document).on('click', '#addScheduleForm', function() {
    $('.modal').modal('hide');
    showScheduleForm($(this).attr('data-id'));
});
$(document).on('click', '#addScheduleFormFromCandidate', function() {
    $('.modal').modal('hide');
    showScheduleForm('', 'candidates##' + $(this).attr('data-id'), '');
});
$(document).on("hidden.bs.modal", "#notes-popup", function() {
    if (typeof $('#addScheduleFormFromCandidate').attr('data-id') !== "undefined" && $(
            '#addScheduleFormFromCandidate')
        .attr('data-id') != '') {
        reloadTrackerHistory($('#addScheduleFormFromCandidate').attr('data-id'), 'candidate', '.activity-log');
    }

});
$(document).on("hidden.bs.modal", "#candidatesDetails", function() {
    $('.dataTable').DataTable().ajax.reload(null, false);
});
$(document).on("hidden.bs.modal", "#recruiterTrackerDetails", function() {
    <?php if(LD::checkFeatureFlag('client-profile-upcoming-recruitment-clinician-clickable')){ ?>
        $('.dataTable:not(#nthauditlist)').DataTable().ajax.reload(null, false);
    <?php } else { ?>
        $('.dataTable').DataTable().ajax.reload(null, false);
    <?php } ?>
});
$(document).on("hidden.bs.modal", "#schedule_next", function() {
    $('.dataTable').DataTable().ajax.reload(null, false);
});

function showEnhancementSuggestionForm() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('getEnhancementSuggestionForm') }}",
        type: "get",
        data: {
            _token: _token
        },
        success: function(response) {
            if (response) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(response);
                $('#enhancement_suggestion').modal('show');
            }
        },
    });
}

function showReportIssueForm() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{ route('getReportIssueForm') }}",
        type: "get",
        data: {
            _token: _token
        },
        success: function(response) {
            if (response) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(response);
                $('#report_issue').modal('show');
            }
        },
    });
}

function addOfficeIpeaEvent(id = -1,current_mode='add') {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('schedules.addofficeipeaevent')}}",
        type: 'get',
        data: {
            _token: _token,
            id: id,
            mode: current_mode
        },
        dataType: 'html',
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addOfficeIpeaEvent').modal('show');
        }
    });
}

function addTherapyIpeaEvent(id = -1,current_mode='add') {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('schedules.addtherapyipeaevent')}}",
        type: 'get',
        data: {
            _token: _token,
            id: id,
            mode: current_mode
        },
        dataType: 'html',
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addTherapyIpeaEvent').modal('show');
        }
    });
}
// Notification
</script>

<script>
function getPushNotification() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('notification.pushNotification')}}",
        type: 'get',
        data: {
            _token: _token
        },
        success: function(data) {
            if (data.status == true) {
                if(data.count > 0){
                    $(".notification-cnt").addClass('unread-notification');
                }else{
                    $(".notification-cnt").removeClass('unread-notification');
                }
                $("#notificationCount").html(data.count);
                data.messages.forEach(message => {
                    Push.create(message.title, {
                        body: message.notes,
                        timeout: 5000
                    });
                });
            }
        }
    });
}
setInterval(function() {
    getPushNotification();
}, 60000)
// if (typeof(EventSource) == "undefined") {
//     console.log("Error: Server-Sent Events are not supported in your browser");
// } else {
//     let stream = new EventSource("{{route('notification.pushNotification')}}");
//     console.log(stream, 'stream')
//     if (typeof(stream) !== "undefined") {
//         stream.close();
//     }
//     stream.addEventListener('ping', event => {
//         if (event.data != false && event.data.length > 0) {
//             JSON.parse(event.data).forEach(message => {
//                 Push.create(message.title, {
//                     body: message.notes,
//                     timeout: 5000
//                 });
//             });
//         }
//     });
//     let notificationCountStream = new EventSource("{{route('notification.notificationCount')}}");
//     if (typeof(notificationCountStream) !== "undefined") {
//         notificationCountStream.close();
//     }
//     notificationCountStream.addEventListener('count', event => {
//         if (typeof event.data !== 'undefined') {
//             $("#notificationCount").html(event.data);
//         }
//     });
// }
</script>

<script>
function openCandidateDetails(id, removeHideEvent = false, type = '') {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('candidatesDetails')}}",
        type: 'get',
        data: {
            id: id,
            type: type,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
			if (removeHideEvent) {
				$(document).off("hidden.bs.modal", "#candidatesDetails");
			}
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#candidatesDetails').modal('show');
        },
        // error: function(xhr, status, error) {
        //     console.log(xhr, status, error, 'error');
        //     if (status == 'error') {
        //         window.location.href = '{{route("login")}}';
        //     }
        // }
    });
}
</script>
<script>
//IPEA Template
function updateNurseIpeaInfo(scheduleId) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ipea.updateNurseIpeaInfo')}}",
        type: 'get',
        data: {
            scheduleId: scheduleId,
            _token: _token
        },
        success: function(data) {
            @if(LD::checkFeatureFlag('ipea-template-redesign'))
                $("#tempModalCntForIpea").remove();
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCntForIpea').html(data);
                $('#updateNurseIpeaInfoModal').modal('show');
            @else
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#updateNurseIpeaInfoModal').modal('show');
            @endif
        },
        error: function(xhr, status, error) {
            alert(error);
        }
    });
}
$(document).on('change', '.on-change', function(e) {
    console.log($(this).val());
    if ($(this).val() == 'Rescheduled') {
        $('#ipeaReschedule').modal('show');
    }
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ipea.saveNurseInfo')}}",
        type: 'get',
        data: {
            scheduleId: $("#scheduleId").val(),
            name: $(this).attr('name'),
            value: $(this).val()
        },
        dataType: 'json',
        success: function(data) {
            if (data.status == false) {
                console.log('Something went wrong!');
            }
            reloadIpeaHistory($('#trackerId').val());
        }
    });
});
$(document).ready(function() {
    $('a.popup-item').click(function(e){
        if ($(this).hasClass('clicked')) {
            e.preventDefault(); return false;
        } else {
            $(this).addClass('clicked');
        }
    });
});
$( document ).ajaxStop(function() {
    $( "a.popup-item" ).removeClass('clicked');
});
function showNthSocialMediaForm(id = 0,button) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    button.disabled = true;
    $.ajax({
        url: "{{route('dashboard.getnthsocialmediaform')}}",
        type: "GET",
        data: {
            _token: _token,
            id: id
        },
        success: function(response) {
            button.disabled = false;
            if (response) {
                $('.modal-backdrop').removeClass('show');
                $('.modal-backdrop').addClass('hide');
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(response);
                $('#nthsocialmedia_form').modal('show');
                $(".modal:visible").length && $(document.body).addClass("modal-open");

            }
        },
    });
}
//Trach Vent Class
function getTrachVentClassInfo(occurrence_id, onLoaded, stacked) {
    // Once opened stacked, refreshes of the pop-up stay stacked too.
    stacked = stacked || $('#trachVentClassStackedCnt').length > 0;
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('getTrachVentClassInfo')}}",
        type: 'GET',
        data: {
            occurrence_id: occurrence_id,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            if (stacked) {
                showTrachVentClassInfoStacked(data, onLoaded);
                return;
            }
            $("#tempModalCnt").remove();
            $('.modal-backdrop').remove();
            $(document.body).removeClass('modal-open');
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#getTrachVentClassInfoModal').modal('show');
            if (typeof onLoaded === 'function') {
                onLoaded($div);
            }
        },
        error: function(xhr, status, error) {
            console.log(xhr, status, error, 'error');
            if (status == 'error') {
               // window.location.href = '{{route("login")}}';
            }
        }
    });
}
// Opens Class Details on top of whatever is already open (e.g. the clinician profile), leaving those pop-ups untouched.
function showTrachVentClassInfoStacked(html, onLoaded) {
    var $old = $('#trachVentClassStackedCnt');
    if ($old.length) {
        // Refresh: drop only our own pop-up and the backdrops stacked above the pop-ups underneath.
        var underneath = $('.modal.show').not($old.find('.modal')).length;
        $old.remove();
        $('.modal-backdrop').slice(underneath).remove();
    }
    var $div = $('<div />').attr('id', 'trachVentClassStackedCnt').appendTo('body').html(html);
    $('#getTrachVentClassInfoModal').on('hidden.bs.modal', function (e) {
        if (e.target !== this) {
            return;
        }
        $div.remove();
        // Bootstrap clears body.modal-open when any modal closes; keep it while the pop-ups underneath are open.
        $(".modal:visible").length && $(document.body).addClass('modal-open');
    }).on('shown.bs.modal', function () {
        $(document.body).addClass('modal-open');
    }).modal('show');
    if (typeof onLoaded === 'function') {
        onLoaded($div);
    }
}
// Class links inside Activity Log notes.
$(document).on('click', '.js-trach-vent-class-details', function (e) {
    e.preventDefault();
    getTrachVentClassInfo($(this).data('occurrence-id'), null, true);
});
//After hours
var afterHoursDetailPopupOpened = false;
function getAfterHoursInfo(afterhours_schedule_id) {
    if (afterHoursDetailPopupOpened == false) {
        afterHoursDetailPopupOpened = true;
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('afterhours.getAfterHoursInfo')}}",
            type: 'GET',
            data: {
                afterhours_schedule_id: afterhours_schedule_id,
                _token: _token
            },
            dataType: 'html',
            success: function(data) {
                afterHoursDetailPopupOpened = false;
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#getAfterHoursInfoModal').modal('show');
            },
            error: function(xhr, status, error) {
                afterHoursDetailPopupOpened = false;
                console.log(xhr, status, error, 'error');
                if (status == 'error') {
                // window.location.href = '{{route("login")}}';
                }
            }
        });
    }
}


function editOfficeEvaluation(id,mode='edit') {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('officeevaluation.edit')}}?mode="+mode,
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            id: id
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#officeEvaluationFormModal').modal('show');
        }
    });
}

function openSatusChange(id){
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('statuschangerequests.checkrequeststatus')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            id: id
        },
        success: function(data) {
            if(data.statusValue == 1){
                getClinicianProfile(id);
            }else{
                showStatusChangeRequestNextStepForm(id);
            }
        }
    });
}
</script>

<style>
.datepicker-filter a {
    padding: 5px !important;
    text-align: center !important;
}

.datepicker-filter .datepicker table {
    width: 100%;
}

.datepicker-filter .datepicker-inline {
    width: 100%;
}
</style>

<script>
    $(document).ready(function() {
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
                success: function(data) {
                    if (data) {
                        $('#render-background-check-error').html(data);
                    }
                    if(data.length === 0) {
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
                success: function(data) {
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
        $(document).on("show.bs.modal", ".modal", function(e) {
            let modalId = $(e.target).attr('id');
            let id = $(document).find('#fa-application-status').data('id');
            let type = $('#fa-application-status').data('type');
            console.log(type);
            console.log(id);
            console.log(modalId);
            if(isFAModal(modalId)) {
                interval = setInterval(function () {
                    if (id !== 0) {
                        getStatus(id, type);
                        checkFirstAdvantageErrors(id, type);
                    }
                }, 10000);
            }
        });
        $(document).on("hidden.bs.modal", ".modal", function(e) {
            let modalId = $(e.target).attr('id');
            if(isFAModal(modalId)) {
                clearInterval(interval);
            }
        });
        $(document).on('click', 'a.retry-background-check', function(e) {
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
                success: function(data) {
                    $('#render-background-check-error').html(data);
                    getStatus(type);
                }
            });
        });
    });
</script>

@include('layouts.globalScript')
@stack('page-scripts')

@stack('scripts')

@stack('styles')
</html>
