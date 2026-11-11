<?php

use Illuminate\Support\Facades\Route;
use App\Helpers\H;
use App\Helpers\LD;
?>

<script>
        function ensureCounter($el) {
            var $counter = $el.data("charCounterEl");
            if ($counter && $counter.length) return $counter;

            $counter = $('<small class="char-counter" aria-live="polite"></small>');
            $el.after($counter);
            $el.data("charCounterEl", $counter);
            return $counter;
        }

        function getLimit($el) {
            var limit = parseInt($el.attr("data-chars"), 10);
            return isNaN(limit) || limit < 0 ? null : limit;
        }

        function updateCounter($el) {
            var limit = getLimit($el);
            if (limit === null) return;

            var val = $el.val() || "";
            var len = val.length;
            var remaining = limit - len;

            var $counter = ensureCounter($el);
            $counter.text(len + "/" + limit + " (" + remaining + " left)");
        }

        function clampValue($el) {
            var limit = getLimit($el);
            if (limit === null) return;

            var val = $el.val() || "";
            if (val.length > limit) {
                $el.val(val.slice(0, limit));
            }
        }

        function initCharsLimit(context) {
            var $ctx = context ? $(context) : $(document);

            $ctx.find("input.chars-limit:not([type='hidden']), textarea.chars-limit").each(function () {
                var $el = $(this);
                var limit = getLimit($el);
                if (limit === null) return;

                // Optional: set native maxlength too (helps on mobile + gives instant enforcement)
                // We still clamp manually to handle paste/IME edge cases consistently.
                $el.attr("maxlength", limit);

                ensureCounter($el);

                // Initial state
                clampValue($el);
                updateCounter($el);

                // Remove previous handlers if re-initialized
                $el.off(".charsLimit");

                // On any input change (typing, paste, drag-drop, autocomplete)
                $el.on("input.charsLimit", function () {
                    clampValue($el);
                    updateCounter($el);
                });

                // On paste: ensure we clamp after paste content lands
                $el.on("paste.charsLimit", function () {
                    var el = this;
                    setTimeout(function () {
                        clampValue($(el));
                        updateCounter($(el));
                    }, 0);
                });

                // On blur: final clamp (safe)
                $el.on("blur.charsLimit", function () {
                    clampValue($el);
                    updateCounter($el);
                });
            });
        }
        window.initCharsLimit = initCharsLimit;
        initCharsLimit(document);

$.ajaxSetup({
    headers: {
        'X-Requested-With': 'XMLHttpRequest'
    }
});
$(document).on('click', '#saveReschedule', function() {
    if ($("#rescheduleForm").valid() == false) {
        return false;
    }
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ipea.saveReschedule')}}",
        type: 'get',
        data: {
            scheduleId: $("#updateNurseIpeaInfoModal #scheduleId").val(),
            trackerId: $("#updateNurseIpeaInfoModal #trackerId").val(),
            ipea_location_id: $("#ipeaReschedule #ipea_location_id").val(),
            IPEA_Date: $('#ipeaReschedule #IPEA_Date').val(),
            IPEA_Time: $('#ipeaReschedule #IPEA_Time').val(),
            _token: _token
        },
        dataType: 'json',
        success: function(data) {
            if (data.status == true) {
                $('#ipeaReschedule').modal('hide');
            }
        }
    });
});
</script>
<script>
function officeTherapyEventDetails(scheduleId) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ipea.officeTherapyEventDetails')}}",
        type: 'get',
        data: {
            scheduleId: scheduleId,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#officeTherapyEventDetailsModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.log(xhr, status, error, 'error');
            if (status == 'error') {
                window.location.href = '{{route("login")}}';
            }
        }
    });
}
</script>
@if(LD::checkFeatureFlag('neo-ipea-template-change'))
<script>
function neoIpeaTemplateDetails(scheduleId) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ipea.neoIpeaTemplateDetails')}}",
        type: 'get',
        data: {
            scheduleId: scheduleId,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            $("#tempModalCntForIpea").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#neoIpeaTemplateModal').modal('show');
        },
        error: function(xhr, status, error) {
            console.log(xhr, status, error, 'error');
            if (status == 'error') {
                window.location.href = '{{route("login")}}';
            }
        }
    });
}

</script>
@endif
<script>
function openNthChecklistDetails(id, hideactivity = false) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('nthChecklistDetails')}}",
        type: 'get',
        data: {
            id: id,
            hideactivity: hideactivity,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#nthChecklistDetails').modal('show');
        }
    });
}
</script>


<script>
function showCandidateNextStepForm(id, type = '') {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('candidate.showNextStepForm')}}",
        type: 'get',
        data: {
            id: id,
            type: type,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#candidateShowNextStepForm').modal('show');
        }
    });
}
$(document).on("hidden.bs.modal", "#candidateShowNextStepForm", function() {
    $(document).off('click','#saveJobViteOutcomeBtn');
    $(document).off('change','#jobViteOutcome');
    $('.dataTable').DataTable().ajax.reload(null, false);
});
</script>


<script>
function showAdsInfoForm(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('socialmedia.getAdsInfoForm')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#adsInfoForm').modal('show');
        }
    });
}
function saveAdsForm(){
    let _token = $('meta[name="csrf-token"]').attr('content');
    var data = {};
    fields = $('#saveAdsForm').serializeArray();
    $.each( fields, function( i, field ) {
        data[field.name] = field.value;
    });
    data['_token'] = _token;
    $.ajax({
        url: "{{route('socialmedia.saveAdsForm')}}",
        type: 'post',
        data: data,
        success: function(data) {
            $('#adsInfoForm').modal('hide');
            $('.dataTable').DataTable().ajax.reload(null, false);
        }
    });
}
function markAsCompletedAds(id){
    let _token = $('meta[name="csrf-token"]').attr('content');
    if (confirm("Are you sure you want to mark as completed?")) {
        $.ajax({
            url: "{{route('socialmedia.markAsCompletedAds')}}",
            type: 'post',
            data: {
                id: id,
                _token: _token
            },
            success: function(data) {
                $('.dataTable').DataTable().ajax.reload(null, false);
                $('#socialMediaDetails').modal('hide');
            }
        });
    } else {
        return false;
    }
}
function getSocialMediaDetails(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('socialmedia.getSocialMediaDetails')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#socialMediaDetails').modal('show');
        }
    });
}

function getSocialMediaClosePopup(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('socialmedia.getSocialMediaClosePopup')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#closeSocialMediaAds').modal('show');
            $('#move_remaining_budget').multiselect({
                includeSelectAllOption: true
            });
        }
    });
}

function closeSocialMediaAds(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('socialmedia.markAsCompletedAds')}}",
        type: 'post',
        data: {
            id: id,
            ads_close_reason: jQuery('#ads_close_reason').val(),
            move_remaining_budget: jQuery('#move_remaining_budget').val(),
            _token: _token
        },
        success: function(data) {
            $('.dataTable').DataTable().ajax.reload(null, false);
            $('#socialMediaDetails').modal('hide');
        }
    });
}
</script>

<script>
function getClinicianProfile(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('clinician.getDetails')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#clinicianDetails').modal('show');
        }
    });
}
</script>

<script>
function getClientDetails(id, defaultTab = '', lobId = null, lobSource = null, nthAccountId = null) {
    let _token = $('meta[name="csrf-token"]').attr('content');

    let data = {
      id: id,
      _token: _token,
      defaultTab: defaultTab
    };

    if (lobId) {
      data.lobId = lobId;
    }

    if (lobSource) {
      data.lobSource = lobSource;
    }

    if (nthAccountId) {
      data.nthAccountId = nthAccountId;
    }

    $.ajax({
        url: "{{route('client.getDetails')}}",
        type: 'get',
        dateType:"html",
        data:data,
        success: function(data) {
            $("#tempModalCntCD").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntCD').html(data);
            $('#clientDetails').modal('show');
        }
    });
}
</script>

<script>
    $(document).ready(function(){
        $(document).on("dblclick",".nrrreport",function(){
            var json = decodeURIComponent(escape($(this).attr("data-json")));
            var reportType = $(this).attr("data-report");
            var formname = $("#ReportsFilterSearchForm").serializeArray();
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('nrr.getnrrpopup')}}",
                type: 'get',
                dateType:"html",
                data: {
                    rowfilter: json,
                    type: reportType,
                    form: formname,
                    _token: _token
                },
                success: function(data) {
                    $("#tempModalnrrreport").remove();
                    var $div = $('<div />').appendTo('body');
                    $div.attr('id', 'tempModalnrrreport').html(data);
                    $('#nrr-popup').modal('show');
                }
            });
        });
    });

</script>
<script>
(function($,undefined){
    '$:nomunge';
    $.fn.serializeObject = function(){
        var obj = {};
        $.each( this.serializeArray(), function(i,o){
        var n = o.name,
            v = o.value;
            obj[n] = obj[n] === undefined ? v
            : $.isArray( obj[n] ) ? obj[n].concat( v )
            : [ obj[n], v ];
        });
        return obj;
    };
})(jQuery);
function addNTHForm(id) {
    $('.modal').modal('hide')
    $('.modal-backdrop').remove()
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('nth.addNTHForm')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            setTimeout(function() {
                $('#addNTHForm').modal('show');
            }, 500);
        }
    });
}
</script>

<script>
function openBuildScheduleForm(nthaccountid, openfrom = 'listing') {
    let _token = $('meta[name="csrf-token"]').attr('content');

   $.ajax({
        url: "{{route('client.buildschedule')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            id: nthaccountid
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#buildschedulepopup').modal('show');
            $("#buildSchedule_aoc_nthaccounts_id").val(nthaccountid);
            $('<input type="hidden" name="openfrom" id="openfrom" value="'+openfrom+'"/>').insertAfter("#buildSchedule_aoc_nthaccounts_id");
            showBuildScheduleGrid(nthaccountid,"buildschedule-grid");
        }
    });
}
$(document).on("hidden.bs.modal", "#buildschedulepopup", function() {
    $(".modal:visible").length && $(document.body).addClass("modal-open");

    let _token = $('meta[name="csrf-token"]').attr('content');
    if ($("#openfrom").val() == 'nthform') {
        $.ajax({
            url: "{{route('nth.blankBuildScheduleNotification')}}",
            type: 'post',
            dateType:"html",
            data: {
                _token: _token,
                id: $('#buildSchedule_aoc_nthaccounts_id').val()
            },
            success: function(data) {
                console.log(data);
            }
        });
    }
});

function showBuildScheduleGrid(nthaccountid,targetid) {
    let _token = $('meta[name="csrf-token"]').attr('content');

   $.ajax({
        url: "{{route('client.showBuildScheduleGrid')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            'nthaccountid':nthaccountid
        },
        success: function(data) {
            $("#buildschedule-grid").html(data);
            var totalHours = 0;
            if($(".buildschedule-totalhours").length < 1 ) {
                $("#add-new-schedule-title").after("<div class='buildschedule-totalhours text-right'></div>");
            }
            $($(".buildschedule-grid-hours")).each(function( index ) {
                totalHours+=parseFloat($( this ).text());
            });
            $(".buildschedule-totalhours").html("Total Hours : "+totalHours.toFixed(2));

            $('#selectall').off().bind('click', function(){
                if ($(this).is(':checked')) {
                    $('input[name="schedule_id[]"]').prop('checked', true);
                } else {
                    $('input[name="schedule_id[]"]').prop('checked', false);
                }
                enableDisableScheduleButtons();
            });
        }
    });
}

function prepareReasonCategory(){
    var reasonCategories = ['AOC','Caregiver','Clinician'];
    var reasonCategoryObject = $("#reason_category");
    reasonCategoryObject.empty();
    var jsonObject = jQuery.parseJSON(reasonCategory);
     var hasOptions = false;
     reasonCategoryObject.append($('<option></option>').val('').html('Select Category'));
    $(jsonObject["global"]).each(function (index,val) {
        for(var i=1; i<=5;i++) {
            if(buildScheduleMode.includes(i)) {
                $(val[i]).each(function(ai,av){
                    hasOptions=true;

                });
            }
        }
    });

    if(hasOptions) {
        for(var i=0; i < reasonCategories.length; i++) {
            reasonCategoryObject.append($('<option></option>').val(reasonCategories[i]).html(reasonCategories[i]));
        }
    } else {
        for(var i=0; i < reasonCategories.length; i++) {
            hasOptions = false;
            $(jsonObject[reasonCategories[i]]).each(function (index,val) {
                for(var i=1; i<=5;i++) {
                    if(buildScheduleMode.includes(i)) {
                        $(val[i]).each(function(ai,av){
                            hasOptions=true;
                        });
                    }
                }
            });
            if(hasOptions) {
                reasonCategoryObject.append($('<option></option>').val(reasonCategories[i]).html(reasonCategories[i]));
            }
        }
    }


}

function fillBuildScheduleGridItem(obj,mode) {
    $("#selectdays").multiselect("enable");
    $("#start_time").prop('disabled', false);
    $("#end_time").prop('disabled', false);
   // $("#reason_category option").removeAttr('disabled')
    if(mode=='add') {
        $("#buildSchedule_item_id").val("");
        $("#selectdays").multiselect("clearSelection");
        $("#selectdays").multiselect("refresh");
        $("#start_time").val("");
        $("#end_time").val("");
        $(".schedule_address").hide();
        $(".schedule_address").val("");
        $("INPUT[name=scheduletype]").val(['1']);
        $("#buildSchedule_mode").val("add");
        buildScheduleMode = [1,3,5];
        prepareReasonCategory();
        $("#btnbuildschedule").text("Add");
        $("#reason_category").trigger("change");
        @if(LD::checkFeatureFlag('add-pay-range-fields-nth-form'))
        @canPermission('nthFormFieldsForNthCard')
        $('#flexibleschedule').prop('checked', false);
        @endcanPermission
        @endif
    } else {

        var jsonObject = jQuery.parseJSON($(obj).attr("data-obj"));
        $("#selectdays").multiselect("disable");
        $("#buildSchedule_item_id").val(jsonObject.id);
        $("#selectdays").multiselect("clearSelection");
        $("#selectdays").val(jsonObject.StartDayOfWeek);
        $("#selectdays").multiselect("refresh");

        if(mode=='remove') {
            $("#start_time").prop('disabled', true);
            $("#end_time").prop('disabled', true);

        }
        var start_minute = jsonObject.StartMinute;
        var end_minute = jsonObject.EndMinute;

        var start_time = ("0" + jsonObject.StartHour).slice(-2)+':'+("0" + start_minute).slice(-2);
        var end_time = ("0" + jsonObject.EndHour).slice(-2)+':'+("0" + end_minute).slice(-2);

        $("#start_time").val(start_time);
        $("#end_time").val(end_time);


        if(jsonObject.alternate_address==null || jsonObject.alternate_address=='' ) {
            $(".schedule_address").hide();
            $("INPUT[name=scheduletype]").val(['1']);

        } else {
            $(".schedule_address").show();
            $("INPUT[name=scheduletype]").val(['2']);

        }
        $("#schedulebuildform_address").val(jsonObject.alternate_address);
        @if(LD::checkFeatureFlag('add-pay-range-fields-nth-form'))
        @canPermission('nthFormFieldsForNthCard')
        if(jsonObject.ScheduleType == 1){
            $('#flexibleschedule').prop('checked', true);
        } else {
            $('#flexibleschedule').prop('checked', false);
        }
        @endcanPermission
        @endif
        if(mode=='remove') {
            buildScheduleMode = [2,3];
            $("#buildSchedule_mode").val("remove");
            prepareReasonCategory();
            $("#reason_category").trigger("change");
            $("#btnbuildschedule").text("Remove");
            if (jQuery('input[name="schedule_id[]"]').length == 1) {
                $('#removeallschedules').val(1);
                showclientremovepopup();
            } else {
                $('#removeallschedules').val(0);
            }
        } else {
            buildScheduleMode = [4,5];
            $("#buildSchedule_mode").val("edit");
            prepareReasonCategory();
            $("#reason_category").trigger("change");
            $("#btnbuildschedule").text("Update");
        }
    }
}

function showclientremovepopup() {
    //var $options = $("#reason > option").clone();
    $("#reason").empty();
    $("#reason").append('<option value="">Select Reason</option>');
    $("#reason").append('<option value="Discharged">Discharged</option>');
    $("#reason").append('<option value="Family declined hours">Family declined hours</option>');
    $("#reason").append('<option value="Non-admit">Non-admit</option>');
    $("#reason").append('<option value="Reduced hours">Reduced hours</option>');
    $("#reason").append('<option value="Staffed">Staffed</option>');

    $('#select_nth_reason').empty();
    $("#select_nth_reason").append('<option value="">Select Reason</option>');
    $("#select_nth_reason").append('<option value="Discharged">Discharged</option>');
    $("#select_nth_reason").append('<option value="Family declined hours">Family declined hours</option>');
    $("#select_nth_reason").append('<option value="Non-admit">Non-admit</option>');
    $("#select_nth_reason").append('<option value="Reduced hours">Reduced hours</option>');
    $("#select_nth_reason").append('<option value="Staffed">Staffed</option>');

    $('#select_nth_reason').off().change(function(){
        $('#reason').val($(this).val());
    });
    $('#clientRemovedConfirmation').modal({
        keyboard: false
    });
    $('#clientRemovedConfirmation').on('hidden.bs.modal', function (e) {
        $("#reason").empty();
        $("#reason").append('<option value="">Select Reason</option>');
    });
}

function checkAllSchedulesAreSelected() {
    if (jQuery('input[name="schedule_id[]"]').length == jQuery('input[name="schedule_id[]"]:checked').length) {
        $('#removeallschedules').val(1);
    } else {
        $('#removeallschedules').val(0);
    }
}

function setupFormOptionsForMassUpdate(ids,mode){
    if(mode=='removeall'){
        buildScheduleMode = [2,3];
        $("#start_time").prop('disabled', true);
        $("#end_time").prop('disabled', true);
        $("#buildSchedule_mode").val("removeall");
        $("#btnbuildschedule").text("Remove ");
    }
    if(mode=='editall'){
        $("#start_time").prop('disabled', false);
        $("#end_time").prop('disabled', false);
        buildScheduleMode = [4,5];
        $("#buildSchedule_mode").val("editall");
        $("#btnbuildschedule").text("Edit ");
    }

    $("#buildSchedule_item_id").val(ids);
    prepareReasonCategory();
    $("#reason_category").trigger("change");
    if(mode=='removeall' && (jQuery('input[name="schedule_id[]"]').length == jQuery('input[name="schedule_id[]"]:checked').length)) {
        showclientremovepopup();
    }

}

function closeBuildScheduleGrid(){
    $('#buildschedulepopup').modal('hide');
}

function massUpdate(mode) {
    var IdsArray = [];
    var daysArray = [];
    var counter = 0;

    $("#schedule_ids:checked").each(function(){
        IdsArray[counter] = $(this).val();
        daysArray[counter] = $(this).attr("data-day");
        counter++;
    });
    if(IdsArray.length>0) {
        var str = IdsArray.join(",");
        $("#selectdays").multiselect("clearSelection");
        $("#selectdays").val(daysArray);
        $("#selectdays").multiselect("refresh");
        $("#selectdays").multiselect("disable");
        setupFormOptionsForMassUpdate(str,mode);
    } else {
        // default mode
    }

}

function enableDisableScheduleButtons(){
    var val = '';
    var selectAllCheckbox = $("#selectall");
    var checkboxes = document.getElementsByName('schedule_id[]');

    selectAllCheckbox.on('change', function () {
        checkboxes.forEach(function (checkbox) {
            checkbox.checked = selectAllCheckbox.prop('checked');
            var row = checkbox.closest('tr');
            if (checkbox.checked) {
                row.classList.add('highlighted-row');
            } else {
                row.classList.remove('highlighted-row');
            }
        });
    });

    $("#schedule_ids:checked").each(function(){
        val = $(this).val();
    });
    fillBuildScheduleGridItem('{}','add');
    if(val!=''){
        $('.massbtn').show();
    } else {
        $('.massbtn').hide();
    }
    checkAllSchedulesAreSelected();

    checkboxes.forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var row = checkbox.closest('tr');
            if (checkbox.checked) {
                row.classList.add('highlighted-row');
            } else {
                row.classList.remove('highlighted-row');
            }
            checkAllSchedulesAreSelected();
        });
    });
}

$(document).on('show.bs.modal', '.modal', function (event) {
    var zIndex = 1040 + (10 * $('.modal:visible').length);
    $(this).css('z-index', zIndex);
    setTimeout(function() {
        $('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
    }, 0);
});
</script>

@if(LD::checkFeatureFlag('badge-request-bucket'))
<script>
function processProviderBadge(id){
    $('#badgeRequestConfirmationModal').modal('show');
    $('#badgeRequestConfirmationBtn').attr('data-id',id);
}
$(document).on('click', '#badgeRequestConfirmationBtn', function(e) {
    var id = $(this).attr('data-id');
    if(id != ''){
        updateProviderBadge(id);
    }

});
function updateProviderBadge(id){
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('oc.updateProviderBadge')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            id: id
        },
        success: function(data) {
            $('#badgeRequestConfirmationModal').modal('hide');
            if(data.status){
                $('.dataTable').DataTable().ajax.reload(null, false);
            }
        }
    });
}
</script>
@endif

@if(LD::checkFeatureFlag('add-intake'))
<script>
function addIntakeForm() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('intake.addIntakeForm')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addIntakeFormModal').modal('show');
        }
    });
}
</script>
@endif

<!-- Confirmation Popup , we can use it everywhere with one function only -->
<x-modal>
    <x-slot name="id">confirmationDialogNew</x-slot>
    <x-slot name="size">w25</x-slot>
    <x-slot name="title">Confirm</x-slot>
    <x-slot name="class">confirmationDialog</x-slot>
    <x-slot name="isStatic">true</x-slot>
    <x-slot name="isCloseBtn">false</x-slot>
    <x-slot name="body">
        <h5 id="confirmationDialogMessageNew"></h5>
    </x-slot>
    <x-slot name="footer">
        <button class="btn btn-primary" id="confirmationDialogYesButtonNew"></button>
        <button class="btn btn-primary" id="confirmationDialogNoButtonNew"></button>
    </x-slot>
</x-modal>

<script>
    function modalConfirm(msg,yesButton,noButton,cb){
        $("#confirmationDialogMessageNew").html(msg);
        $("#confirmationDialogYesButtonNew").html(yesButton);
        $("#confirmationDialogNoButtonNew").html(noButton);
        $("#confirmationDialogYesButtonNew").off().click(function(){
            cb(true,$('#confirmationDialogNew'));
        });
        $("#confirmationDialogNoButtonNew").off().click(function(){
            cb(false,$('#confirmationDialogNew'));
        });
        $('#confirmationDialogNew').modal('show');
    }
    $(document).on("hidden.bs.modal", "#confirmationDialogNew", function() {
        $(".modal:visible").length && $(document.body).addClass("modal-open");
    });
	$(document).on('input', 'input.float', function(e) {
        this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*?)\..*/g, '$1');
    });
</script>

<link href="{{ mix('css/choices.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/choices.min.js') }}"></script>
<script>
function addTriageCallForm() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('triage.addTriageCallForm')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addTriageCallFormModal').modal('show');
        }
    });
}
</script>
<script>
function getFollowUpForm(id,followType) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('triage.getFollowUpForm')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            followupType:followType,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#followUpTriageCallFormModal').modal('show');
        }
    });
}
function getOutcomeDetails(id,followType) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('triage.getOutcomeDetails')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            followupType:followType,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#triageOutcomeDetailsModal').modal('show');
        }
    });
}
$(document).on("hidden.bs.modal", "#triageOutcomeDetailsModal", function() {
    $('#followUpTriageCallForm #despositionActionDate,#followUpTriageCallForm #hospitalizationStartDate,#followUpTriageCallForm #hospitalizationEndDate').datetimepicker('destroy');
});

$(document).on("hidden.bs.modal", "#followUpTriageCallFormModal", function() {
    if($("#reload-triage_24h_follow_up").val()==0) {
        $('#dataTable-triage-triage_24h_follow_up').DataTable().ajax.reload();
    }

    if($("#reload-triage_48h_follow_up").val()==0) {
        $('#dataTable-triage-triage_48h_follow_up').DataTable().ajax.reload();
    }

    if($("#reload-triage_7d_follow_up").val()==0) {
        $('#dataTable-triage-triage_7d_follow_up').DataTable().ajax.reload();
    }

    if($("#reload-triage_30d_follow_up").val()==0) {
        $('#dataTable-triage-triage_30d_follow_up').DataTable().ajax.reload();
    }
});
</script>

<script>
function recruiterScorecard(mode, id = '') {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('recruiterScorecardForm')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            mode: mode
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#recruiterScorecardFormModal').modal('show');

            if (mode != 'add') {
                loadRecruiterScorecardData(id,null,null,mode);
            }
        }
    });
}
</script>

@if(LD::checkFeatureFlag('triage-call-details'))
<script>
function getTriageCallDetails(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('triage.getTriageCallDetails')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#triageCallsDetailModal').modal('show');
        }
    });
}
</script>
@endif

@if(LD::checkFeatureFlag('ccc-weekly-monthly-form'))
<script>
    function getWeeklyMonthlyInputCall(id,type) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('ccc.getweeklyinputcall')}}",
            type: 'get',
            dateType:"html",
            data: {
                id:id,
                type:type,
                _token: _token
            },
            success: function(data) {
                $("#tempModalCntWeeklyInputCall").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCntWeeklyInputCall').html(data);
                $('#weeklyFormModal').modal('show');
            }
        });
    }
</script>
@else
<script>
function getWeeklyInputCall(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccc.getweeklyinputcall')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCntWeeklyInputCall").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntWeeklyInputCall').html(data);
            $('#weeklyFormModal').modal('show');
        }
    });
}
</script>
@endif

<script>
function getMonthlyInputCall(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccc.getmonthlyinputcall')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCntMonthlyInputCall").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntMonthlyInputCall').html(data);
            $('#monthlyFormModal').modal('show');
        }
    });
}
</script>

<script>
function getCandidateResume(candidateid) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('getcandidateresume')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:candidateid,
            _token: _token
        },
        success: function(data) {
            $("#tempEmailModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempEmailModalCnt').html(data);
            $('#resumepopup-popup').modal('show');
        }
    });
}
</script>

<script>
function getCheckedOffMissedVisitForm(id,type) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('dashboard.missedvisitforms')}}",
        type: 'get',
        data: {
            ids: id,
            type: type,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#missedvisitsmodal').modal('show');
        }
    });
}
</script>

@if(LD::checkFeatureFlag('supply-requests'))
<script>
function addSuppliesRequest() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('supplies.addSuppliesRequest')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addSuppliesRequestFormModal').modal('show');
        }
    });
}
function getSupplyRequestsDetails(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('supplies.getSuppliesRequestDetails')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addSuppliesRequestFormModal').modal('show');
        }
    });
}

function changeOutcomeToSupplyRequests(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('supplies.changeOutcomeToSupplyRequests')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addSuppliesRequestFormModal').modal('show');
        }
    });
}
function editSupplyRequests(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('supplies.getSuppliesRequestDetails')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            mode:'Edit',
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addSuppliesRequestFormModal').modal('show');
        }
    });
}

</script>
@endif



@if(LD::checkFeatureFlag('supply-requests'))
<script>
function openClientMatchTemplate(id, outcome = false) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccc.getclientmatchtemplate')}}",
        type: 'get',
        dataType:"html",
        data: {
             id:id,
             outcome:outcome,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#cccClientMatchTemplate').modal('show');
        }
    });
}
</script>
@endif

@if(LD::checkFeatureFlag('ccc-intake-widget'))
<script>
function getClientIntakeOutcomForm(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('getclientintakeoutcom')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#client_intake_outcom_modal').modal('show');
        }
    });
}
</script>
@endif

@if(LD::checkFeatureFlag('add-color-highlights-ccc-exception-bucket'))
<script>
function getExceptionAddressed(id, bucket) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('dashboard.getexceptionaddressed')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            bucket: bucket,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#exception_addressed_modal').modal('show');
        }
    });
}
</script>
@endif

<script>
function openCCRSNSForm(id,type='tracker') {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccr.showsnsform')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            type:type,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#ccr_sns_form').modal('show');
        }
    });
}

$(document).on("hidden.bs.modal", "#ccr_sns_form", function() {
    $('.dataTable').DataTable().ajax.reload(null, false);
    setTimeout(function() {
        $('.modal-backdrop').remove();
    }, 0);
});

function openCandidateStartOutcome(id,type) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccm.candidatestratoutcomeform')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            type:type,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#ccmCandidateStartOutcome').modal('show');
        }
    });
}

$(document).on("hidden.bs.modal", "#ccmCandidateStartOutcome", function() {
    $('.dataTable').DataTable().ajax.reload(null, false);
});

function getCoverageDetails(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('getCoverageDetails')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#coverageDetailsModal').modal('show');
        }
    });
}
function getMassTextForms(frompage,department=null) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    let ids = 0;
    var selectedIds = [];
    var isError = false;

    let selectAllCheckbox = $('.table-view.active .select-all-checkbox-candidate');

    if(selectAllCheckbox.is(':checked') &&
    (frompage == 'maps_textblast' || frompage == 'maps_bon_fl')) {
        ids = 'selectallmap';


    } else if(frompage == 'caseload') {
        $('.select-all-case_load.td:checked').each(function() {
            selectedIds.push(this.value);
        });
        ids = selectedIds.join(",");
    } else {
        $('.select-all-candidate.td:checked').each(function() {
            selectedIds.push(this.value);
        });
        ids = selectedIds.join(",");
    }
    <?php if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) { ?>
                if(frompage=='maps_textblast') {
                    $('.select-all-candidate.td:checked').each(function() {
                        var distance = $(this).parent().parent().attr('data-distance');
                        if(distance > radiusLimit) {
                            isError = true;
                        }
                    });
                    if(isError && !$('#select-all-checkbox-candidate').is(':checked')){
                        $("#radiusSMSDialog").modal("show");
                        return false;
                    }
                }
    <?php } ?>
    var dtSearch = $('input[type="search"]').val();
    $.ajax({
        url: "{{route('getmasstextforms')}}",
        type: 'get',
        data: {
			_token: _token,
			ids: ids,
            frompage: frompage,
			dtsearch: dtSearch,
            department: department
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#masstextmodal').modal('show');
        }
    });
}

@if (LD::checkFeatureFlag('coverage-clinician-map-prn-process'))

function addCoverageTrackerFromCoverageClinician(source, bucket = '') {

    var mapIds = new Array();
    var patientId = '';
    var aocpatientid = '';
    var daysAvailable = new Array();
    var clinicianId = '';
    var clinicianName = '';
    var aocproviderid = '';
    var patientName = '';
    var shift_type = new Array();
    var isDaysError = false;
        var isPatientIdSelected = true;
        $.each(($("td.multi-select input[type='checkbox']:checked")), function() {


            mapIds.push($(this).parent().parent().attr('data-id'));
            if(aocpatientid=='') {
                aocpatientid=$(this).parent().parent().attr('data-aocpatientid');
            }
            patientName=$(this).parent().parent().attr('data-patientname');
            clinicianName=$(this).parent().parent().attr('data-clinicianname');
            daysAvailable.push($(this).parent().parent().attr('data-days_available'));
            shift_type.push($(this).parent().parent().attr('data-shift_type'));
            patientId=$(this).parent().parent().attr('data-patientid');
            aocproviderid=$(this).parent().parent().attr('data-aocproviderid');
            clinicianId=$(this).parent().parent().attr('data-clinicianid');

            if (aocpatientid != $(this).parent().parent().attr('data-aocpatientid')) {
                isPatientIdSelected = true;
            } else {
                isPatientIdSelected = false;
            }
            if($.trim($(this).parent().parent().attr('data-days_available'))=='') {
                isDaysError = true;
            }

        });

        if(isDaysError) {
            alert("Days Available should not be empty");
            return false;
        }

        if (isPatientIdSelected) {
            alert("You can select same client only.");
            return false;
        }

    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('coveragetracker.getcoverageshifttrackerform')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            patientId: aocpatientid,
            aocpatientid: patientId,
            aocproviderid:aocproviderid,
            mapIds: mapIds,
            daysAvailable: daysAvailable,
            shift_type: shift_type,
            clinicianId: clinicianId,
            cccId:'-1',
            patientName:patientName,
            clinicianName:clinicianName,
            source: source,
            bucket: bucket,
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addCoverageShiftTrackerFormModal').modal('show');
        }
    });
}
@endif

@if (LD::checkFeatureFlag('add-to-tracker-from-dashboard-ccc'))
const allEqual = arr => arr.every( v => v === arr[0] );

function addCoverageShiftTracker(source, bucket = '') {
    var selectedPatientId = '';
    var selectedCCCId = '';
    var selectedPatientName = '';
    var selectedMapIds = new Array();
    var selectedScheduleTime = new Array();
    if (bucket && bucket != 'map_buckets') {
        var isPatientIdSelected = true;
        $.each(($('input[name="checkbox-'+bucket+'[]"]:checked')), function() {
            if (selectedPatientId == '') {
                selectedPatientId = $(this).data('patient_id');
                selectedPatientName = $(this).data('patient_name');
                selectedCCCId = $(this).data('ccc_id');
            }
            selectedScheduleTime.push($(this).attr('data-schedule_time'));
            selectedMapIds.push($(this).val());
            if (selectedPatientId != $(this).data('patient_id')) {
                isPatientIdSelected = true;
            } else {
                isPatientIdSelected = false;
            }
        });

        if (isPatientIdSelected) {
            alert("You can select same client only.");
            return false;
        }
    } else if (bucket && bucket == 'map_buckets') {
        var isPatientIdSelected = true;
        $.each(($("td.multi-select input[type='checkbox']:checked")), function() {
            if (selectedPatientId == '') {
                selectedPatientId = $(this).parent().parent().attr('data-patient_id');
                selectedPatientName = $(this).parent().parent().attr('data-patient_name');
                selectedCCCId = $(this).parent().parent().attr('data-ccc_id');
            }
            selectedScheduleTime.push($(this).parent().parent().attr('data-schedule_time'));
            selectedMapIds.push($(this).parent().parent().attr('data-id'));
            if (selectedPatientId != $(this).parent().parent().attr('data-patient_id')) {
                isPatientIdSelected = true;
            } else {
                isPatientIdSelected = false;
            }
        });
        if (isPatientIdSelected) {
            alert("You can select same client only.");
            return false;
        }
    }
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('coveragetracker.getcoverageshifttrackerform')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            patientId: selectedPatientId,
            patientName: selectedPatientName,
            cccId: selectedCCCId,
            mapIds: selectedMapIds,
            source: source,
            bucket: bucket,
            scheduleTimeEditable: allEqual(selectedScheduleTime) ? selectedScheduleTime[0] : ''
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addCoverageShiftTrackerFormModal').modal('show');
        }
    });
}
@else
function addCoverageShiftTracker() {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('coveragetracker.getcoverageshifttrackerform')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addCoverageShiftTrackerFormModal').modal('show');
        }
    });
}
@endif
</script>

@if(LD::checkFeatureFlag('edit-triage-call'))
<script>
    function editFollowUp(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('triage.editFollowUp')}}",
            type: 'get',
            dateType:"html",
            data: {
                id:id,
                _token: _token
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#triageOutcomeDetailsModal').modal('show');
            }
        });
    }
</script>
@endif
<script>
    function acknowledgeCcr(id) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt');
            $('#trackerid').val(id);
            $('#ccrmodel').modal('show');
    }
</script>

@if(LD::checkFeatureFlag('edit-icon-coverage-tracker-show-all-page'))
<script>
    function editCoverageTrackerRecord(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('coverage.getEditCoverageTrackerRecord')}}",
            type: 'get',
            data: {
                id: id,
                _token: _token
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#addCoverageShiftTrackerFormModal').modal('show');
            }
        });
    }
</script>
@endif

@if(LD::checkFeatureFlag('message-widget'))
@include('messageWidget.widget')

<script>
    $(document).on('show.bs.modal','#candidatesDetails', function () {
        console.log('shown candidate details');
        if($('#startConversationWithCandidateBtn').attr('data-candidate-id') != ''){
            loadConversationOfCandidateToTab($('#startConversationWithCandidateBtn').attr('data-candidate-id'),'candidate');
        }
    });
    $(document).on('show.bs.modal','#clinicianDetails', function () {
        console.log('shown candidate details');
        if($('#startConversationWithClinicianBtn').attr('data-candidate-id') != ''){
            loadConversationOfCandidateToTab($('#startConversationWithClinicianBtn').attr('data-candidate-id'),'clinician');
        }
    });
    /**
     * To load the Conversation from the twilio into the Candidate tab
     */
    async function loadConversationOfCandidateToTab(candidateId,type){
       // const client = await widget.getClient();
        const conversations = await widget.ajaxCall("{{route('messageWidget.getConversationsByCandidateAndClinician')}}",'GET',{id:candidateId,type:type});
        console.log(conversations,'conversations');
        if(conversations.status){
            let listOfConversationStr = '';
            conversations.data.forEach(async function(conversation) {
                const profileImage = conversation.name.split(' ').map(word => word.charAt(0)).join('').slice(0, 2);
                listOfConversationStr += '<li data-author="'+conversation.sending_sms_number.replace('+','')+'" data-conversation-id="'+conversation.sid+'"><div class="profile-letter-image">'+profileImage+'</div><p>'+conversation.name+'</p></li>';
            });
            $('.messages-thread ul').html(listOfConversationStr);
        }
    }

    /**
     * To load the Messages of the Conversation from the twilio into the widget
     */
    async function loadMessagesByConversationForCandidateClinician(conversationId,currentElem){
        $("#candidateClinicianMessages").removeClass('hide');
        const author = currentElem.attr('data-author');
        const client = await widget.getClient();
        client.on('initialized', async() => {
            const conversation = await widget.getConversationsById(conversationId,client);
            const messages = await conversation.getMessages();
            let messageStr = '';
            messages.items.forEach(function(message) {
                console.log(message,'message')
                messageClass = (widget.allAuthorNumber.indexOf(message.author) !== -1) ? 'right' : 'left';
                let msg = widget.parseMessageToCheckUrlAndFiles(message.body);
                messageStr += '<li data-message-id="'+message.sid+'" class="'+messageClass+'"><p>'+msg+'</p></li>';
            });
            $('#candidateClinicianMessages ul').html(messageStr);
            $('#candidateClinicianMessages ul').animate({scrollTop: $('#candidateClinicianMessages ul li').last().offset().top}, "slow");
        });
    }
    /**
     * To load the Messages of the Conversation from the twilio into the widget
     */
    async function loadMessagesByConversationForCandidateClinicianFromDB(conversationId,currentElem){
        $("#candidateClinicianMessages").removeClass('hide');
        const author = currentElem.attr('data-author');
        const messages = await widget.ajaxCall("{{route('messageWidget.getMessagesByConversationId')}}",'GET',{conversationId:conversationId});
        console.log(messages.messages,'messages');
        let messageStr = '';
        messages.messages.forEach(function(message) {
            console.log(message,'message')
            messageClass = (message.user_id != 0 && message.user_id != '') ? 'right' : 'left';
            let msg = widget.parseMessageToCheckUrlAndFiles(message.body);
            messageStr += '<li data-message-id="'+message.sid+'" class="'+messageClass+'"><p>'+msg+'</p></li>';
        });
        $('#candidateClinicianMessages ul').html(messageStr);
        $('#candidateClinicianMessages ul').animate({scrollTop: $('#candidateClinicianMessages ul li').last().offset().top}, "slow");
    }
    $(document).on('click','.messages-thread li',async function(e){
        if($(this).attr('data-conversation-id') != ''){
            $('#continueConversation').attr('data-current-conversation-id',$(this).attr('data-conversation-id'));
            $('#candidateClinicianMessages ul').html('Fetching Messages...')
            $('.messages-thread li').removeClass('active');
            $(this).addClass('active');
            <?php if(LD::checkFeatureFlag('clinician-candidate-messages-tab-change')){?>
                await loadMessagesByConversationForCandidateClinicianFromDB($(this).attr('data-conversation-id'),$(this));
            <?php }else{?>
                await loadMessagesByConversationForCandidateClinician($(this).attr('data-conversation-id'),$(this));
            <?php }?>

        }
    });
    $(document).on('click','#continueConversation',async function(e){
        if($(this).attr('data-current-conversation-id') != ''){
            await widget.addParticipantAndLoadWidget($(this).attr('data-current-conversation-id'));
        }
    });
    function editTriageCallForm(trigeid) {
    let id = trigeid;
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('triage.addTriageCallForm')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addTriageCallFormModal').modal('show');
        }
    });
}
</script>
</script>
@endif

<script>
    function getConversationDetail(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('messageWidget.getConversationDetail')}}",
            type: 'get',
            data: {
                conversationId: id,
                _token: _token
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#getConversationDetailModal').modal('show');
            }
        });
    }
</script>
<script>
$(document).ready(function(){
    $('body').tooltip({selector: '[data-toggle="tooltip"]',}).off('focus');
    $(document).on("show.bs.modal", ".modal", function() {
        $('.ui-tooltip').remove();
    });
    $(document).on("mouseover", ".ui-tooltip", function() {
        $('.ui-tooltip').remove();
    });
});
</script>

<script>
function getPayrateApprovalConfirmation(id, payrate, gp, candidate) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('dashboard.getPayrateApprovalConfirmation')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            payrate: payrate,
            gp: gp,
            candidate: candidate,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#payrate_approval_modal').modal('show');
        }
    });
}

function clearNotification(button,selectType) {
    $('#displayMsg').hide();
    let _token = $('meta[name="csrf-token"]').attr('content');
    let ids = 0;
    if(selectType == 'few') {
        var selectedIds = [];
        $('.select-all-new_notification:checked').each(function() {
            selectedIds.push(this.value);
        });
        ids = selectedIds.join(",");
    }
    $.ajax({
        url: "{{route('notification.markMultipleAsRead')}}",
        type: "POST",
        data: {
            _token: _token,
            ids: ids,
            selectType: selectType
        },
        success: function(response) {
            button.disabled = false;
            $('.select-all-new_notification').prop('checked', false);
            $('#clear-all-button-div div.updateall').remove();
            if(response.success==1) {
                window.location.reload(true);
            }else {
                $('#displayMsg').html('<div class="alert alert-danger">'+response.message+'</div>');
            }
            $('#displayMsg').show();
        },
    });
}
</script>

@if(LD::checkFeatureFlag('refresh-screen-to-last-bucket-expanded'))
<script>
var shownOnRefresh = localStorage.getItem('shownOnRefresh');
if (!shownOnRefresh) {
    shownOnRefresh = ['collapseThree','collapseOne'];
    localStorage.setItem('shownOnRefresh', JSON.stringify(shownOnRefresh));
}

$('#accordionExample').on('shown.bs.collapse', function (e) {
    shownOnRefresh = JSON.parse(localStorage.getItem('shownOnRefresh'));
    if ($.inArray($(e.target).attr("id"), shownOnRefresh) == -1) {
        shownOnRefresh.push($(e.target).attr("id"));
    };
    localStorage.setItem('shownOnRefresh', JSON.stringify(shownOnRefresh));
});

$('.collapse').on('hidden.bs.collapse', function () {
    if (!$(this).is(':visible')) {
        shownOnRefresh = JSON.parse(localStorage.getItem('shownOnRefresh'));
        shownOnRefresh.splice( $.inArray($(this).attr('id'), shownOnRefresh), 1 );
        localStorage.setItem('shownOnRefresh', JSON.stringify(shownOnRefresh));
    }
});

$(document).ready(function(){
    var shownOnRefresh = JSON.parse(localStorage.getItem('shownOnRefresh'));
    var arrayLength = shownOnRefresh.length;
    for (var i = 0; i < arrayLength; i++) {
        $('#' + shownOnRefresh [i]).collapse('show');
    }
});
</script>
@endif

@if (LD::checkFeatureFlag('force-login-page-when-inactive'))
<script>
$(function() {
    function handleInactivity() {
        var mousePosition = [-1, -1];
        var sessionMaxLifeTime = <?php echo ini_get("session.gc_maxlifetime") - 500 ?>;
        var maxIdleTime = sessionMaxLifeTime * 1000;
        var timeout = setTimeout(redirectToLoginPage, maxIdleTime);

        function resetTimer() {
            clearTimeout(timeout);
            timeout = setTimeout(redirectToLoginPage, maxIdleTime);
        }

        function redirectToLoginPage() {
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('logout')}}",
                type: 'get',
                data: {
                    _token: _token
                },
                success: function(data) {
                    if (data.status == true) {
                        window.location.href = "{{route('login')}}";
                    } else {
                        timeout = setTimeout(redirectToLoginPage, maxIdleTime);
                    }
                }
            });
        }

        $(document).on('keyup mouseup mousemove touchend touchmove', function(e) {
            if (e.type === 'mousemove') {
                if (e.clientX === mousePosition[0] && e.clientY === mousePosition[1]) {
                    return;
                }
                mousePosition[0] = e.clientX;
                mousePosition[1] = e.clientY;
            }
            resetTimer();
        });
    }
    handleInactivity();
});
</script>
@endif

<script>
    function selfEvaluation(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('selfevaluation.edit')}}",
            type: 'get',
            dateType:"html",
            data: {
                _token: _token,
                id: id,
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#selfEvaluationFormModal').modal('show');
            }
        });
    }
</script>

<script>
    function markAsCompleted(id){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('makrascompleted')}}",
            type: 'get',
            dateType:"html",
            data: {
                _token: _token,
                id: id,
            },
            success: function(response) {
                if(response.success == true){
                    $('#dataTable-task_list-task_list').DataTable().ajax.reload();
                }
            }
        });
    }

    function setUnassignedCareTeam(id,bucket){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('intake.unassignedcareteam')}}",
            type: 'get',
            dateType:"html",
            data: {
                _token: _token,
                id: id,
            },
            success: function(response) {
                if(response.status == true){
                    $('#dataTable-intake-exception-intake_unassigned_care_team').DataTable().ajax.reload();
                }
            }
        });
    }

    function returnFn(e){
        e.preventDefault();
        return false;
    }


    function showRenewableForm(id,type) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('renewables.getoutcomeform')}}",
            type: 'get',
            data: {
                renewableid: id,
                buckettype: type,
                _token: _token
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#getrenewableoutcomeform').modal('show');
            }
        });
    }

    function showCompleteRenewableForm(id,type) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('renewables.getcompleterenewableform')}}",
            type: 'get',
            data: {
                renewable_id: id,
                bucket_type: type,
                _token: _token
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#completerenewableform').modal('show');
            }
        });
    }

    function showRenewableNotesForm(id,type) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('renewables.getrenewablenotesform')}}",
            type: 'get',
            data: {
                renewable_id: id,
                bucket_type: type,
                _token: _token
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#renewablenotesform').modal('show');
            }
        });
    }

    function showNursysExceptionForm(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('nursys.nursysexceptionform')}}",
            type: 'get',
            data: {
                renewable_id: id,
                _token: _token
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#nursyskantimepopup').modal('show');
            }
        });
    }

function showStatusChangeRequestNextStepForm(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('statuschangerequests.showStatusChangeRequestNextStepForm')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#statusChangeRequestNextStepForm').modal('show');
        }
    });
}

function advocacyStatusChangeRequestNextStepForm(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('advocacy.showStatusChangeRequestNextStepForm')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#advocacystatusChangeRequestNextStepForm').modal('show');
        }
    });
}

function showDeleteCoverageTrackerPopup(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('coverage.showdeletecoveragepopup')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#coverage_delete_popup').modal('show');
        }
    });
}


function deleteCoverageTrackerRecord(button) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    var id = $("#coverageId").val();
    button.disable = true;

    if($('#delete_coverage_form').valid()){
        $.ajax({
            url: "{{route('coverage.deleteCoverageTrackerRecord')}}",
            type: 'get',
            data: {
                id: id,
                reason:$("#reason").find(":selected").val(),
                reason_category:$("#reason_category").find(":selected").val(),
                _token: _token
            },
            success: function(data) {
                button.disable= false;
                if(data.status){
                    $('.dataTable').DataTable().ajax.reload(null, false);
                    $('#coverage_delete_popup').modal('hide');

                }

            }
        });
    }
}

function showClinicianNoLongerOnCaseNextStepForm(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('ccc.showClinicianNoLongerOnCaseNextStepForm')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#clinicianNoLongerOnCaseNextStepForm').modal('show');
        }
    });
}

var reasonOption = [
    ['Going out of town / last minute plans made','Parents request different gender','Parent changed mind/declined coverage'],
    ['Payrate','Times of shift','Distance of shift','Skills required for client (If they are T/V and clincian doesn\’t want to work T/V)'],
    ['Date/Day Change', 'Ticket made in error','Duplicated shift','Shift already covered','Service updated', 'Client Hospitalized'],
];
$(document).on("change","#reason_category",function(){
    var category = $(this).find(":selected").val();
    reloadReasonOptions(category);
});
function reloadReasonOptions(category){
    var reasonCategoryObject = $("#reason");
    reasonCategoryObject.empty();

     reasonCategoryObject.append($('<option></option>').val('').html('Select Reason'));

     var dataOptions = [];
     if(category == 'AOC Reasons') {
        dataOptions = reasonOption[2];
     } else if(category == 'Parent Cancels') {
        dataOptions = reasonOption[0];
     } else if(category == 'Clinician cancels') {
        dataOptions = reasonOption[1];
     }
     for(var i=0; i < dataOptions.length; i++) {
        reasonCategoryObject.append($('<option></option>').val(dataOptions[i]).html(dataOptions[i]));
        }
}

function hideCoverageTrackerPopup(button){
    $('#coverage_delete_popup').modal('hide');
}
</script>

@if (LD::checkFeatureFlag('status-change-request-form'))
<script>
    function statusChangeRequest() {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('clinician.statusChangeRequest')}}",
            type: 'get',
            dateType:"html",
            data: {
                _token: _token,
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#statusChangeRequestFormModal').modal('show');
            }
        });
    }
</script>
@endif

<script>
function showCompletePayComForm(paycomId, providerId, Message, datatableId) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('paycomkantime.completedpaycomediscrepancy')}}",
        type: 'get',
        data: {
            paycomId: paycomId,
            providerId: providerId,
            Message: Message,
            _token: _token
        },
        success: function(data) {
            $('#' + datatableId).DataTable().ajax.reload(null, false);
        }
    });
}
var isTriageEventRegistered = false;
</script>

<script>
   function completeCloseRequistion(id){
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('jobvite.completecloserequisition')}}",
        type: 'post',
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {

            $('#dataTable-jobvite_requisition_jobviteclosed').DataTable().ajax.reload(null, false);
        }
    });
   }
</script>


<script>
   function completeOpenRequistion(hub,jobviteCategory){
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('jobvite.completeopenrequisition')}}",
        type: 'post',
        data: {
            hub : hub,
            jobviteCategory: jobviteCategory,
            _token: _token
        },
        success: function(data) {

            $('#dataTable-jobvite_requisition_jobviteopen').DataTable().ajax.reload(null, false);
        }
    });
   }
</script>


<script>
   function showClinicianDocument(id){
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('document.view')}}",
        type: 'get',
        data: {
            id : id,
        },
        success: function(data) {
                $("#tempModalCntNotes").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCntNotes').html(data);
                $('#ViewClinicianDocumentModal').modal('show');
        }
    });
   }

   function showPDFDocument(id){

        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('document.view')}}",
            type: 'get',
            data: {
                id : id,
            },
            success: function(data) {
                var filename = jQuery.parseJSON(JSON.stringify(data));
                window.open(filename.filename);
            }
        });
   }

   let isNeoTemplateOpened = true;
    $(document).on("show.bs.modal", "#updateNurseIpeaInfoModal", function() {
        isNeoTemplateOpened = false;
    });
    $(document).on("hidden.bs.modal", "#updateNurseIpeaInfoModal", function() {
        isNeoTemplateOpened = true;
    });
    $(document).on("show.bs.modal", "#neoIpeaTemplateModal", function() {
        isNeoTemplateOpened = true;
    });
    $(document).on("hidden.bs.modal", "#neoIpeaTemplateModal", function() {
        isNeoTemplateOpened = true;
    });
</script>

<script>
   function openSupervisoryVisitForm(id, provider_id, patient_id, mode) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('supervisoryVisits.openForm')}}",
            type: 'get',
            data: {
                id: id,
                provider_id: provider_id,
                patient_id: patient_id,
                mode: mode,
            },
            success: function (data) {
                $("#tempModalCntsupervisoryVisitForm").remove();
                let $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCntsupervisoryVisitForm').html(data);
                $('#supervisoryVisitFormModal').modal('show');
            }
        });
    }
</script>

<script>
   function loadSchoolSetupForm(aoc_patient_id=0, school_setup_id = 0, mode = 'add') {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('schoolSetup.form')}}",
            type: 'get',
            data: {
                aoc_patient_id: aoc_patient_id,
                mode: mode,
                school_setup_id: school_setup_id,
            },
            success: function (data) {
                $("#tempModalCntSchoolSetupsForm").remove();
                let $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCntSchoolSetupsForm').html(data);
                $('#schoolSetupsFormModal').modal('show');
            }
        });
    }
</script>
<script>
   function loadNoNeedSchoolSetUp(aoc_patient_id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('schoolSetup.noNeedSchoolSetupForm')}}",
            type: 'get',
            data: {
                aoc_patient_id: aoc_patient_id
            },
            success: function (data) {
                $("#tempModalCntNoNeedSchoolSetupsForm").remove();
                let $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCntNoNeedSchoolSetupsForm').html(data);
                $('#noNeedSchoolSetupFormModal').modal('show');
            }
        });
    }
    function getOpenSchoolSetUpNextStepForm(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('schoolSetup.showOpenSchoolSetUpNextStepForm')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCntSchoolSetupsOutcomeForm").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntSchoolSetupsOutcomeForm').html(data);
            $('#schoolSetupsOutcomeFormModal').modal('show');
        }
    });
}
function loadSchoolSetupCccAcknowledged(school_setup_id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('schoolSetup.schoolSetupCcAcknowledgedForm')}}",
        type: 'get',
        data: {
            school_setup_id: school_setup_id
        },
        success: function (data) {
            $("#tempModalCntSchoolSetupCccAcknowledged").remove();
            let $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntSchoolSetupCccAcknowledged').html(data);
            $('#schoolSetupCccAcknowledgedFormModal').modal('show');
        }
    });
}
</script>
@if(LD::checkFeatureFlag('coverage-tracker-view-action'))
<script>
    function getDetailsOfCoverageTracker(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('coverage.getEditCoverageTrackerRecord')}}",
            type: 'get',
            data: {
                id: id,
                isViewMode: true,
                _token: _token
            },
            success: function(data) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(data);
                $('#addCoverageShiftTrackerFormModal').modal('show');
            }
        });
    }
</script>
@endif
<script>
$(document).on('change', '.school-on-change', function(e) {
    if($(this).attr('name') == 'school_clearance_packet') {
        if($(this).val() == 'Pending Nurse') {
            $("#div_pending_nurse_reason").show();
        } else {
            $("#div_pending_nurse_reason").hide();
        }
    }
    if($(this).attr('name') == 'school_setup_status') {
        if($(this).val() == 'Cancelled') {
            $("#div_school_setup_status_cancelled_reason").show();
        } else {
            $("#div_school_setup_status_cancelled_reason").hide();
        }
    }
    if($(this).attr('name') == 'school_setup_status' &&  $(this).val() == 'Completed') {
        showSchoolSetupStatusConfetti()
    }
    saveSchoolOutcomeFormInfo($(this));
});
function saveSchoolOutcomeFormInfo(ele) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('schoolSetup.saveOutcomeInfo')}}",
        type: 'get',
        data: {
            school_setup_id: $("#schoolSetupsOutcomeFormModal #school_setup_id").val(),
            name: ele.attr('name'),
            value: ele.val()
        },
        dataType: 'json',
        success: function(data) {
            if (data.status == false) {
                console.log('Something went wrong!');
            }
            reloadSchoolSetupHistory($("#schoolSetupsOutcomeFormModal #school_setup_id").val());
        }
    });
}
function reloadSchoolSetupHistory(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('schoolSetup.schoolSetupHistory')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $('.activity-log').html(data);
        }
    });
}
function showSchoolSetupStatusConfetti() {
    startConfetti();
    setTimeout(function() {
        stopConfetti();
    }, 1000);
}
</script>
<script>
   function completeSupervisoryVisit(id) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('supervisoryVisits.completeSupervisoryVisit')}}",
            type: 'get',
            data: {
                id: id,
                _token: _token
            },
            success: function (data) {
                if ($('#supervisoryVisitDataTable').length) {
                    $('#supervisoryVisitDataTable').DataTable().ajax.reload();
                }
                if ($('#dataTable-super_visory_visits_bucket').length) {
                    $('#dataTable-super_visory_visits_bucket').DataTable().ajax.reload();
                }
            }
        });
    }
</script>

<script>
    $(document).ready(function(){
        $(document).on("dblclick",".nccreport",function(){
            var json = decodeURIComponent(escape($(this).attr("data-json")));
            var reportType = $(this).attr("data-report");
            var formname = $("#ReportsFilterSearchForm").serializeArray();
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('getnccpopup')}}",
                type: 'get',
                dateType:"html",
                data: {
                    rowfilter: json,
                    type: reportType,
                    form: formname,
                    _token: _token
                },
                success: function(data) {
                    $("#tempModalnccreport").remove();
                    var $div = $('<div />').appendTo('body');
                    $div.attr('id', 'tempModalnccreport').html(data);
                    $('#nrr-popup').modal('show');
                }
            });
        });
    });
    $(document).on("hidden.bs.modal", function() {
        if ($(".modal:visible").length > 0) {
            $(document.body)
            .addClass("modal-open")
            .append('<div class="modal-backdrop fade show modal-stack" style="z-index: 1039;"></div>');
        }
    });
    $(document).on("hidden.bs.modal", function() {
        if ($(".modal:visible").length == 0) {
            $('.modal-backdrop').remove();
        }
    });
</script>
<script>
    @if(LD::checkFeatureFlag('add-reason-field-in-supervisory-visit-delete-pop-up'))
        function supervisoryvisitDeletePopUp(id, type) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt');
            $('#supervisoryVisitId').val(id);
            $('#supervisoryVisittype').val(type);
            $('#delete_reason').val('');
            $('#delete_reason').removeClass('error-field').addClass('valid-field');
            $('#supervisoryVisit').modal('show');
        }
        function deletesupervisoryVisit() {
            let type = $('#supervisoryVisittype').val();
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('supervisoryVisits.delete')}}",
                type: 'post',
                data: {
                    id: $("#supervisoryVisitId").val(),
                    reason: $("#delete_reason").val(),
                    _token: _token
                },
                success: function(data) {
                    if(type == 'showall'){
                        $("#supervisoryVisitDataTable").DataTable().ajax.reload();
                    }else{
                        $("#dataTable-super_visory_visits_bucket").DataTable().ajax.reload();
                    }
                    $('#supervisoryVisit').modal('hide');
                }
            });
        }
    @else
        function supervisoryvisitDeletePopUp(id, type) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt');
            $('#supervisoryVisitId').val(id);
            $('#supervisoryVisittype').val(type);
            $('#supervisoryVisit').modal('show');
        }
        function deletesupervisoryVisit() {
            let type = $('#supervisoryVisittype').val();
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('supervisoryVisits.delete')}}",
                type: 'post',
                data: {
                    id: $("#supervisoryVisitId").val(),
                    _token: _token
                },
                success: function(data) {
                    if(type == 'showall'){
                        $("#supervisoryVisitDataTable").DataTable().ajax.reload();
                    }else{
                        $("#dataTable-super_visory_visits_bucket").DataTable().ajax.reload();
                    }
                    $('#supervisoryVisit').modal('hide');
                }
            });
        }
    @endif
    function closesupervisoryVisit(){
        $('#supervisoryVisit').modal('hide');
    }
</script>
<script>
    function supplyReqDelete(id, type) {
        $("#tempModalCnt").remove();
        var $div = $('<div />').appendTo('body');
        $div.attr('id', 'tempModalCnt');
        $('#supplyRequestId').val(id);
        $('#supplyRequesttype').val(type);
        $('#supplyRequestDelPopUp').modal('show');
    }
    function deleteSupplyRequest() {
        let type = $('#supplyRequesttype').val();
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('supplies.delete')}}",
            type: 'post',
            data: {
                id: $("#supplyRequestId").val(),
                _token: _token
            },
            success: function(data) {
                if(type == 'showall'){
                    $("#dataTable").DataTable().ajax.reload();
                }else{
                    $("#dataTable-supplyRequests-supply_requests_new").DataTable().ajax.reload();
                }
                $('#supplyRequestDelPopUp').modal('hide');
            }
        });
    }
    function closesupplyRequest(){
        $('#supplyRequestDelPopUp').modal('hide');
    }


</script>
<script>
function getScheduleCorrected(id, bucket) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('dashboard.getschedulecorrected')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            bucket: bucket,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCntSc").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntSc').html(data);
            $('#schedule_corrected_modal').modal('show');
        }
    });
}
</script>

@if(LD::checkFeatureFlag('ah-journal-note-bucket'))
<script>
function getJournalNoteDetails(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('afterhours.getJournalNoteDetails')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCntCD").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntCD').html(data);
            $('#journalNoteDetails').modal('show');
        }
    });
}
</script>
@endif

<script>
function showTextTemplateForm(id = null) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    let url = (id == null) ? "{{ route('addTextTemplate') }}" : "{{ route('editTextTemplate') }}";
    $.ajax({
        url: url,
        type: "get",
        data: {
            _token: _token,
            id: id
        },
        success: function(response) {
            if (response) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(response);
                $('#text_template').modal('show');
            }
        },
    });
}
function templateDelete(id) {
    $("#tempModalCnt").remove();
    var $div = $('<div />').appendTo('body');
    $div.attr('id', 'tempModalCnt');
    $('#textTemplateId').val(id);
    $('#textTemplateDelPopUp').modal('show');
    return false;
}
</script>
<script>
function openCustomFieldForm(mode, id = '') {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('openCustomFieldForm')}}",
        type: 'get',
        dateType:"html",
        data: {
            mode: mode,
            id: id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCntCD").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntCD').html(data);
            $('#customFieldFormModal').modal('show');
        }
    });
}
</script>
<script>
function showTemplateCategoryForm(id = null) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    let url = (id == null) ? "{{ route('addTemplateCategory') }}" : "{{ route('editTemplateCategory') }}";
    $.ajax({
        url: url,
        type: "get",
        data: {
            _token: _token,
            id: id
        },
        success: function(response) {
            if (response) {
                $("#tempModalCnt").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCnt').html(response);
                $('#template_category').modal('show');
            }
        },
    });
}
function categoryDelete(id) {
    $("#tempModalCnt").remove();
    var $div = $('<div />').appendTo('body');
    $div.attr('id', 'tempModalCnt');
    $('#categoryId').val(id);
    $('#templateCategoryDelPopUp').modal('show');
    return false;
}
</script>
@if(LD::checkFeatureFlag('intake-form-new-change'))
<script>
function editIntakeForm(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('intake.editIntakeForm')}}",
        type: 'get',
        dateType:"html",
        data: {
            _token: _token,
            id:id
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#addIntakeFormModal').modal('show');
        }
    });
}
function getIntakeData(aoc_patient_id, intake_id) {
let _token = $('meta[name="csrf-token"]').attr('content');
$.ajax({
    url: "{{ route('getIntakeData') }}",
    type: "POST",
    data: {
        aoc_patient_id: aoc_patient_id,
        intakeId: intake_id,
        _token: _token
    },
    success: function(response) {
        $('#intakechildtab').html(response);
    },
});
}
</script>
@endif
@if(LD::checkFeatureFlag('invoices-widget-billing-dashboard'))
<script>
function getInvoiceDetails(id,bucket_type) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('billing.getInvoiceDetails')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token,
            bucket_type:bucket_type
        },
        success: function(data) {
            $("#tempModalInvoiceDetails").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalInvoiceDetails').html(data);
            $('#invoiceDetailsModal').modal('show');
        }
    });
}
function invoicesOutcomeFromDetail(bucket_type, id){
    let _token = $('meta[name="csrf-token"]').attr('content');
    var selectedIds = [];
    selectedIds.push(id);
    $.ajax({
        url: "{{route('billing.showInvoiceOutcome')}}",
        type: "POST",
        dateType:"html",
        data: {
            _token: _token,
            ids: selectedIds.join(","),
            type: bucket_type,
        },
        success: function(data) {
            $("#tempModalOutcomeCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalOutcomeCnt').html(data);
            $('#billingInvoiceOutcomeModal').modal('show');
        },
    });
}
</script>
@endif
<script>
function reloadBillingInvoiceHistory(id, type) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('billing.billingInvoiceHistory')}}",
        type: 'get',
        data: {
            id: id,
            type: type,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $('.activity-log').html(data);
        }
    });
}

    function getIntakeStaffTransfersOutcome(id, bucket, type) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('intake.getIntakeStaffTransfersOutcome')}}",
            type: 'get',
            dateType:"html",
            data: {
                id: id,
                bucket: bucket,
                _token: _token,
                type: type
            },
            success: function(data) {
                $("#tempModalCntIntakeStaffOutcome").remove();
                var $div = $('<div />').appendTo('body');
                $div.attr('id', 'tempModalCntIntakeStaffOutcome').html(data);
                $('#intakeStaffOutcomeForm').modal('show');
            }
        });
    }
</script>

<script>
function getBillingDetails(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('billing.getBillingDetails')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token,
        },
        success: function(data) {
            $("#tempModalbillingDetailsModal").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalbillingDetailsModal').html(data);
            $('#billingDetailsModal').modal('show');
        }
    });
}
</script>

@if(LD::checkFeatureFlag('intake-report'))
<script>
    $(document).ready(function(){
        $(document).on("dblclick",".intakereport",function(){
            var json = decodeURIComponent(escape($(this).attr("data-json")));
            var reportType = $(this).attr("data-report");
            var formname = $("#ReportsFilterSearchForm").serializeArray();
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('getintakepopup')}}",
                type: 'get',
                dateType:"html",
                data: {
                    rowfilter: json,
                    type: reportType,
                    form: formname,
                    _token: _token
                },
                success: function(data) {
                    $("#tempModalintakereport").remove();
                    var $div = $('<div />').appendTo('body');
                    $div.attr('id', 'tempModalintakereport').html(data);
                    $('#nrr-popup').modal('show');
                }
            });
        });
    });
    $(document).on("hidden.bs.modal", function() {
        if ($(".modal:visible").length > 0) {
            $(document.body)
            .addClass("modal-open")
            .append('<div class="modal-backdrop fade show modal-stack" style="z-index: 1039;"></div>');
        }
    });
    $(document).on("hidden.bs.modal", function() {
        if ($(".modal:visible").length == 0) {
            $('.modal-backdrop').remove();
        }
    });
</script>
@endif
<script>
    $(document).ready(function(){
        $(document).on("dblclick",".candidateReport",function(){
            var json = decodeURIComponent(escape($(this).attr("data-json")));
            var reportType = $(this).attr("data-report");
            var formname = $("#ReportsFilterSearchForm").serializeArray();
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('nccreports.getWhiteboardPopup')}}",
                type: 'get',
                dateType:"html",
                data: {
                    rowfilter: json,
                    type: reportType,
                    form: formname,
                    _token: _token
                },
                success: function(data) {
                    $("#tempModalcandidateReport").remove();
                    var $div = $('<div />').appendTo('body');
                    $div.attr('id', 'tempModalcandidateReport').html(data);
                    $('#nrr-popup').modal('show');
                }
            });
        });
    });

</script>

@if(LD::checkFeatureFlag('prn-bucket-modification-6736'))
<script>
    function showConfirmationPRN(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('prn.confirmationdialog')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCnt').html(data);
            $('#prnconfirmationdialog').modal('show');
        }
    });
}
</script>
@endif

@if(LD::checkFeatureFlag('prn-bucket-modification-6736'))
<script>
    function showActiveCoverageOutcome(id, bucket) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('activecoverage.outcomeform')}}",
        type: 'get',
        dateType:"html",
        data: {
            id:id,
            bucket: bucket,
            _token: _token
        },
        success: function(data) {
            $("#tempModalCntActiveCoverage").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalCntActiveCoverage').html(data);
            $('#activecoverageoutcomeform').modal('show');
        }
    });
}
</script>
@endif

@if(LD::checkFeatureFlag('recruiting-candidate-conversion-report'))
<script>
    $(document).ready(function(){
        $(document).on("dblclick",".candidateconversionreport",function(){
            var json = decodeURIComponent(escape($(this).attr("data-json")));
            var reportType = $(this).attr("data-report");
            var formname = $("#ReportsFilterSearchForm").serializeArray();
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('getcandidateconversionreportpopup')}}",
                type: 'get',
                dateType:"html",
                data: {
                    rowfilter: json,
                    type: reportType,
                    form: formname,
                    _token: _token
                },
                success: function(data) {
                    $("#tempModalcandidateconversionreport").remove();
                    var $div = $('<div />').appendTo('body');
                    $div.attr('id', 'tempModalcandidateconversionreport').html(data);
                    $('#nrr-popup').modal('show');
                }
            });
        });
    });
    $(document).on("hidden.bs.modal", function() {
        if ($(".modal:visible").length > 0) {
            $(document.body)
            .addClass("modal-open")
            .append('<div class="modal-backdrop fade show modal-stack" style="z-index: 1039;"></div>');
        }
    });
    $(document).on("hidden.bs.modal", function() {
        if ($(".modal:visible").length == 0) {
            $('.modal-backdrop').remove();
        }
    });
</script>
@endif
@if(LD::checkFeatureFlag('adjustments-bucket-invoices-widget'))
<script>
function invoicesAdjustmentOutcome(id){
    let _token = $('meta[name="csrf-token"]').attr('content');
    var selectedIds = [];
    selectedIds.push(id);
    $.ajax({
        url: "{{route('billing.showAdjustmentsInvoiceOutcome')}}",
        type: "POST",
        dateType:"html",
        data: {
            _token: _token,
            ids: selectedIds.join(",")
        },
        success: function(data) {
            $("#tempAdjustmentModalOutcomeCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempAdjustmentModalOutcomeCnt').html(data);
            $('#adjustmentsInvoiceOutcomeModal').modal('show');
        },
    });
}
function invoicesAdjustmentReassign(id){
    let _token = $('meta[name="csrf-token"]').attr('content');
    var selectedIds = [];
    selectedIds.push(id);
    $.ajax({
        url: "{{route('billing.showAdjustmentsInvoiceReassign')}}",
        type: "POST",
        dateType:"html",
        data: {
            _token: _token,
            ids: selectedIds.join(",")
        },
        success: function(data) {
            $("#tempAdjustmentModalReassignCnt").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempAdjustmentModalReassignCnt').html(data);
            $('#adjustmentsInvoiceReassignModal').modal('show');
        },
    });
}
</script>
@endif

@if(LD::checkFeatureFlag('change-export-method-billing-dashboard'))
<script>
    function postInNewTab(actionUrl, data) {
        const form = $('<form>', {
            action: actionUrl,
            method: 'POST',
            target: '_blank',
            style: 'display: none;'
        });
        $.each(data, function(key, value) {
            $('<input>', {
            type: 'hidden',
            name: key,
            value: value
            }).appendTo(form);
        });
        form.appendTo('body').submit();
        form.remove();
    }
</script>
@endif

@if(LD::checkFeatureFlag('recruiting-ipea-speed-report'))
<script>
    $(document).ready(function(){
        $(document).on("dblclick",".ipeasspeedreport",function(){
            var json = decodeURIComponent(escape($(this).attr("data-json")));
            var reportType = $(this).attr("data-report");
            var formname = $("#ReportsFilterSearchForm").serializeArray();
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('getipeaspeedreportpopup')}}",
                type: 'get',
                dateType:"html",
                data: {
                    rowfilter: json,
                    type: reportType,
                    form: formname,
                    _token: _token
                },
                success: function(data) {
                    $("#tempModalipeaspeedreport").remove();
                    var $div = $('<div />').appendTo('body');
                    $div.attr('id', 'tempModalipeaspeedreport').html(data);
                    $('#nrr-popup').modal('show');
                }
            });
        });
    });
    $(document).on("hidden.bs.modal", function() {
        if ($(".modal:visible").length > 0) {
            $(document.body)
            .addClass("modal-open")
            .append('<div class="modal-backdrop fade show modal-stack" style="z-index: 1039;"></div>');
        }
    });
    $(document).on("hidden.bs.modal", function() {
        if ($(".modal:visible").length == 0) {
            $('.modal-backdrop').remove();
        }
    });
</script>
@endif

@if (LD::checkFeatureFlag('dashboard-nthalerts-inquiry-bucket'))
<script>
function getNthLeadsInquiriesDetail(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('nthalerts.getNthLeadsInquiriesDetail')}}",
        type: 'get',
        dateType:"html",
        data: {
            id: id,
            _token: _token,
        },
        success: function(data) {
            $("#tempModalNthLeadsInquiriesDetailModal").remove();
            var $div = $('<div />').appendTo('body');
            $div.attr('id', 'tempModalNthLeadsInquiriesDetailModal').html(data);
            $('#nthLeadsInquiriesDetailModal').modal('show');
        }
    });
}

function reloadNthLeadsInquiriesHistory(id) {
    let _token = $('meta[name="csrf-token"]').attr('content');
    $.ajax({
        url: "{{route('nthalerts.nthLeadsInquiriesHistory')}}",
        type: 'get',
        data: {
            id: id,
            _token: _token
        },
        dataType: 'html',
        success: function(data) {
            $('.activity-log').html(data);
        }
    });
}
</script>
@endif
