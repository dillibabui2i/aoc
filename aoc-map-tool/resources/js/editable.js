(function ($) {
    "use strict";

    let Contenteditable = function (options) {
        this.init('contenteditable', options, Contenteditable.defaults);
    };

    //inherit from Abstract input
    $.fn.editableutils.inherit(Contenteditable, $.fn.editabletypes.abstractinput);

    $.extend(Contenteditable.prototype, {
        activate: function() {
            this.$input.focus();
        },
        value2html: function(value, element) {
            //render value into element
            $(element).html(value);
        },
        html2value: function(html) {
            //parse html and return value
            return html;
        },
        value2input: function(value) {
            //set input value using element's html
            this.$input.html(value);
        },
        input2value: function() {
            //return value using element's html
            return this.$input.html();
        },
        postrender: function() {
            this.$input.attr('contenteditable', true);
        }
    });
    Contenteditable.defaults = $.extend({}, $.fn.editabletypes.abstractinput.defaults, {
        tpl: "<div class='contenteditable content-editable' contenteditable='true'/>",
        inputclass: ''
    });

    $.fn.editabletypes.contenteditable = Contenteditable;
}(window.jQuery));
function findByKey(arr, key, value) {
    if (typeof arr !== "undefined") {
        for (let i = 0; i < arr.length; i++) {
            if (arr[i][key] == value) {
                return i;
            }
        }
    } else {
        return 0;
    }
}

function formatDate(date) {
    if (typeof date === "string" && /^\d{4}-\d{2}-\d{2}$/.test(date)) {
        return date;
    }

    let d = new Date(date),
        month = "" + (d.getMonth() + 1),
        day = "" + d.getDate(),
        year = d.getFullYear();

    if (month.length < 2) month = "0" + month;
    if (day.length < 2) day = "0" + day;

    return [year, month, day].join("-");
}
window.customFieldEditable = function() {
    let _token = $('meta[name="csrf-token"]').attr("content");
    $(".custom-field-editable").editable({
        params: function(params) {
            params.table = $(this).editable().data("table");
            const type = $(this).editable().data("type");
            const preference_tag = $(this).editable().data("preference_tag");
            const label = $(this).editable().data("label");
            const customFieldType = $(this).editable().data("customfield_type");
            const category = $(this).editable().data("category");
            if (type == "date") {
                params.value = formatDate(params.value);
            }
            if ($.isArray(params.value)) {
                params.value = params.value.join(",");
            }
            if (preference_tag != undefined) {
                params.preference_tag = preference_tag;
            }
            if (label != undefined) {
                params.label = label;
            }
            if (type != undefined) {
                params.type = type;
            }
            if (customFieldType != undefined) {
                params.customFieldType = customFieldType;
            }
            if (category != undefined) {
                params.category = category;
            }
            params.customfields = true;
            params._token = _token;
            return params;
        },
        success: function(response, newValue) {
            if (!response.success) return response.msg;
        },
        display: function(value, sourceData) {
            if ($(this).attr('data-name') == 'CF_Skills') {
                var isSpecialized = false;
                var newVal = value;
                if (newVal == null) {
                    newVal = '';
                }
                newVal = newVal.toString().toLowerCase();
                var skills = newVal.split(",");
                for (const skill of skills) {
                    const trimmedSkill = skill.trim().toLowerCase();
                    if (trimmedSkill === 'trach' || trimmedSkill === 'trach & vent' || (trimmedSkill === 'vent' && $("#addNTHBuildScheduleForm #scheduleSpecialized").val() === '1')) {
                        isSpecialized = true;
                        break;
                    }
                }
                if(isSpecialized) {
                    $("#addNTHBuildScheduleForm #specializedClient").attr('checked',true);
                }else{
                    $("#addNTHBuildScheduleForm #specializedClient").attr('checked',false);
                }
            }
            if (typeof sourceData !== "undefined") {
                if ($.isArray(value)) {
                    $(this).html(value.join(','));
                } else {
                    const k = findByKey(sourceData, "value", value);
                    if (typeof k !== "undefined") {
                        $(this).html(sourceData[k].text);
                    } else {
                        $(this).html(value);
                    }
                }
            } else {
                $(this).html(value);
            }
        },
		validate: function(value) {
            if($(this).editable().data("required") == 1 && $.trim(value) == '')
                return 'This field is required';
        }
    });
}
$(function() {
    $(document).on("hidden.bs.modal", ".modal", function() {
        $(".modal-backdrop").remove();
        $(".modal:visible").length && $(document.body).addClass("modal-open");
    });
    $(document).on("keypress", ".autocomplete-client", function(e) {
        if (e.which == 13) {
            e.preventDefault();
            return false;
        }
    });

    let _token = $('meta[name="csrf-token"]').attr("content");
    $.fn.editable.defaults.mode = "inline";

    $(".editable-select").editable({
        mode: "inline",
        params: function(params) {
            params.table = $(this).editable().data("table");
            params._token = _token;
            return params;
        },
        display: function(value, sourceData, response) {
            if (typeof sourceData !== "undefined") {
                const k = findByKey(sourceData, "value", value);
                if (typeof k !== "undefined") {
                    $(this).html(sourceData[k].text);
                }
            } else {
                $(this).html(value);
            }
        },
        success: function(response, newValue) {
            if($(this).editable().data("name") == 'Skillset'){
                openRecruiterDetails($(this).editable().data("pk"), 'append');
            }
            if (!response.success) return response.msg;
        },
    });
    $(".editable-text,.editable").editable({
        params: function(params) {
            params.table = $(this).editable().data("table");
            const type = $(this).editable().data("type");
            if (params.name == "GP") {
                params.value = params.value / 100;
            }
            if (type == "date") {
                params.value = formatDate(params.value);
            }
            params._token = _token;
            return params;
        },
        success: function(response, newValue) {
            if($(this).editable().data("name") == 'GP'){
                openRecruiterDetails($(this).editable().data("pk"), 'append');
            }
            if (!response.success) return response.msg;
        },
        validate: function(value) {
            if (!isNaN(value) && Number(value) < 0) {
                return 'Negative values are not allowed';
            }
        }
    });

    $(".editable-scorecard").editable({
        params: function(params) {
            params.table = $(this).editable().data("table");
            params._token = _token;
            return params;
        },
        success: function(response, newValue) {
            loadRecruiterScorecardData($('#scorecardid').val(),null,null,'edit');
            if (!response.success) return response.msg;
        },
        validate: function(value) {
            if ($(this).editable().data("maxperlimmit")) {
                if (value > $(this).editable().data("maxperlimmit")) {
                    return "Possible percentage values less or equal to " +$(this).editable().data("maxperlimmit")+ ".";
                }
            }
        }
    });

    $(".editable-autocomplete").editable({
        mode: "inline",
        onblur: "ignore",
        params: function(params) {
            params.table = $(this).editable().data("table");
            params._token = _token;
            return params;
        },
        success: function(response, newValue) {
            if (!response.success) return response.msg;
        },
        validate: function(value) {
            if ($(this).editable().data("autocompletevalidate")) {
                if (value != $(this).editable().data("autocompletevalidate")) {
                    return "Please select value from suggestions only.";
                }
            }
        }
    });

    $(".editable-textarea").editable({
        mode: "inline",
        params: function(params) {
            params.table = $(this).editable().data("table");
            params._token = _token;
            return params;
        },
        onblur: 'ignore',
        success: function(response, newValue) {
            if (!response.success) return response.msg;
            if($(this).editable().data("name") == 'SchoolSetUpNotes'){
                reloadSchoolSetupHistory($(this).editable().data("pk"));
            }
            if($(this).editable().data("name") == 'branch_notes' || $(this).editable().data("name") == 'why_not_qualified'){
                reloadIpeaHistory($('#trackerId').val());
            }
        },
    });


    $(".contenteditable").editable({
        mode: "inline",
        params: function(params) {
            params.table = $(this).editable().data("table");
            params.schedule = typeof $(this).editable().data("schedule") !== 'undefined' ? $(this).editable().data("schedule") : '';
            params._token = _token;

            //extracting the mentioned user ids and sending them to controller
            var $obj = $("<div>"+params.value+"</div>").find("a");
            var idArray = [];
            var cntr = 0;
            $obj.each(function(i,item){
                idArray[cntr] = $(item).attr("data-id");
                cntr++;
            });
            params.mentions = idArray.join(",");

            return params;
        },
        onblur: 'ignore',
        escape: false,
        success: function(response, newValue) {
            if (!response.success) return response.msg;
            if($(this).editable().data("name") == 'clinician_availability' || $(this).editable().data("name") == 'branch_notes' || $(this).editable().data("name") == 'why_nurse_chosen'){
                reloadIpeaHistory($('#trackerId').val());
            }
        },
    });
    let coverageStepParam = '';
    $(".editable-select-coverage-step").editable({
        mode: "inline",
        params: function(params) {
            params.table = $(this).editable().data("table");
            params._token = _token;
            console.log(params);
            coverageStepParam = params.value;
            return params;
        },
        display: function(value, sourceData, response) {
            if (typeof sourceData !== "undefined") {
                const k = findByKey(sourceData, "value", value);
                if (typeof k !== "undefined") {
                    $(this).html(sourceData[k].text);
                }
            } else {
                $(this).html(value);
            }
        },
        success: function(response) {
            let currentDatatable = $(this).editable('getValue').last_activity;
            $('#' + coverageStepParam + '-counter').html(response.next_step_counter);
            if ( $.fn.DataTable.isDataTable( '#dataTable-clientcare-coverage-tracker-'+coverageStepParam) ) {
                $('#dataTable-clientcare-coverage-tracker-'+coverageStepParam).DataTable().ajax.reload();
            }
            $('#dataTable-clientcare-coverage-tracker-'+currentDatatable).DataTable().ajax.reload();
            if (!response.success) return response.msg;
        },
    });

    window.customFieldEditable();
});
