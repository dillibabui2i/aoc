<?php
use App\Helpers\LD;
?>
<style>
.datatable-filter-cnt {
    padding: 5px 20px 30px 20px;
    border-bottom: 1px solid #ddd;
    margin-bottom: 10px;
}

.datatable-filter-cnt .form-row {
    margin: 0px;
}

input[type="date"]:before {
    content: attr(placeholder) !important;
    color: #aaa;
    margin-right: 0.5em;
}

input[type="date"]:focus:before,
input[type="date"]:valid:before {
    content: "";
}
.datatable-filter-cnt .col-auto {
    padding-top: 5px;
}
.ui-datepicker td {
    padding: 1px !important;
}
.hide_daterang{
    display: none !important;
}
.hide_pepi_daterang{
    display: none !important;
}
.hide_wepi_daterang{
    display: none !important;
}
.hide_trainig_daterang{
    display: none !important;
}
/* input[type="date"] {
    position: relative;
    overflow: hidden;
}

input[type="date"]::-webkit-calendar-picker-indicator {
    display: block;
    top: 0;
    left: 0;
    background: #0000;
    position: absolute;
    transform: scale(12)
} */
</style>
<?php
$suffix = '';

$button = '';
$frmName = 'datatableFilterSearchForm';
if(isset($btn)) {
    $button = $btn;
}
if(isset($formname)) {
    $frmName = $formname;
    $suffix = $formname === 'dashboardjobvitetherapy' ? 'Therapy' : '';
}

?>
@props([
    'enable' => false,
    'btnId' => null,
    'showAllRouteName' => null,
    'label' => null,
])

<div class="datatable-filter-cnt col-md-12">
    @includeWhen(($isCreateBtnEnable ?? false), 'consentTemplateTracking.createBtn', ['id' => $btnId])
    <form id="<?php echo $frmName?>">
        <div class="form-row align-items-center">
            <span id="filter-label" class="filter-label">Filters:</span>
            @foreach($filters as $filter)
            @if($filter['type'] == 'select')
            <div class="col-auto">
                <label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
                <select class="form-control" name="{{$filter['name']}}" id="{{$filter['name']}}">
                    <option value="">Select {{$filter['label']}}</option>
                    @foreach($filter['source'] as $option)
                    <option value="{{$option['value']}}">{{$option['text']}}</option>
                    @endforeach
                </select>
            </div>
            @else
            @if($filter['type'] == 'checkbox')
            &nbsp &nbsp &nbsp
            <div class="form-check col-auto {{$filter['name']}}">
                <input
                    type="{{$filter['type']}}"
                    name="{{$filter['name']}}"
                    class="form-check-input"
                    id="{{$filter['id'] ?? $filter['name']}}"
                    {{$filter['value'] == true ? 'checked="checked"' : ''}}
                    placeholder="{{$filter['label']}}"
                    {{isset($filter['readonly']) && $filter['readonly'] == true ? 'onclick=returnFn(event)' : ''}}
                    {{($filter['name'] === 'default' || ($filter['label'] ?? '') === 'Me Mode') ? 'data-ignore-override' : ''}}
                />
                <label class="form-check-label" for="{{$filter['id'] ?? $filter['name']}}">{{$filter['label']}}</label>
            </div>
            @elseif($filter['type'] == 'text')
            &nbsp &nbsp &nbsp
            <div class="form-check d-flex col-auto {{$filter['name']}}">
                <input type="{{$filter['type']}}" class="form-control filter-textbox-adjust " name="{{$filter['name']}}" id = "{{$filter['name']}}" />
                <label class="form-check-label" for="{{$filter['name']}}">{{$filter['label']}}</label>
            </div>
            @else
            @if($filter['type']=='multiselect')
            <div class="col-auto">
                <select multiple {{((isset($filter['disabled']) && $filter['disabled'] == true) ? 'disabled' : '')}} class="selectpicker" data-placeholder="Select {{$filter['label']}}" data-live-search="true" name="{{$filter['name']}}" id="{{$filter['id'] ?? $filter['name']}}" {!! (isset($filter['form']) ? 'form="' . $filter['form'] . '"' : '') !!}>
                    @foreach($filter['source'] as $option)
                    <option value="{{$option['value']}}" {{(isset($filter['default']) && $filter['default'] == $option['value']) ? 'selected':''}}>{{$option['text']}}</option>
                    @endforeach
                </select>
            </div>
            @elseif($filter['type'] == 'multiselectsearch')
            <div class="col-auto">
                <select
                    multiple
                    {{((isset($filter['disabled']) && $filter['disabled'] == true) ? 'disabled' : '')}}
                    class="selectpickersearch"
                    data-placeholder="Select {{$filter['label']}}"
                    data-live-search="true"
                    name="{{$filter['name']}}"
                    id="{{$filter['name']}}"
                    {!! (isset($filter['form']) ? 'form="' . $filter['form'] . '"' : '') !!}>
                    @foreach($filter['source'] as $option)
                        <option
                            value="{{$option['value']}}"
                            {{(isset($filter['default']) && $filter['default'] == $option['value']) ? 'selected':''}}>
                                {{$option['text']}}
                        </option>
                    @endforeach
                </select>
            </div>
            @elseif($filter['type'] == 'dateRangePicker')
            <div class="col-auto input-daterange d-flex mr-2 {{(isset($filter['extra_class']) && $filter['extra_class'] != '') ? $filter['extra_class'] : '' }}">
                <label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
                <div class="input-group col-md-6 p-0" >
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">From</span>
                    </div>
                    <input type="text" readonly class="form-control dt-picker {{$filter['fromName']}} fromDateInput" name="{{$filter['fromName']}}" id="{{$filter['fromName']}}" placeholder="{{$filter['label']}}" value="{{$filter['fromdate']}}"/>
                </div>
                <div class="align-self-center ml-1 mr-1 todiv">-</div>
                <div class="input-group col-md-6 p-0">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">To</span>
                    </div>
                    <input type="text" readonly class="form-control dt-picker {{$filter['toName']}} toDateInput" name="{{$filter['toName']}}" id="{{$filter['toName']}}" placeholder="{{$filter['label']}}" value="{{$filter['todate']}}"/>
                </div>
            </div>
            <script type="text/javascript">
                function getDate( element ) {
                    var date;
                    try {
                        date = $.datepicker.parseDate( 'mm/dd/yy', element.value );
                    } catch( error ) {
                        date = null;
                    }
                    return date;
                }
                $(document).ready(function() {
                    $("#{{$frmName}} #{{$filter['fromName']}}").datepicker({
                        dateFormat: 'mm/dd/yy',
                        //maxDate: 0,
                    }).on('change', function () {
                        $("#{{$filter['toName']}}").datepicker("option", 'minDate', getDate( this ) );
                    });

                    $("#{{$frmName}} #{{$filter['toName']}}").datepicker({
                        dateFormat: 'mm/dd/yy',
                        //maxDate: 0
                    }).on('change', function () {
                        $("#{{$filter['fromName']}}").datepicker("option", 'maxDate', getDate( this ) );
                    });
                });
            </script>
            @elseif($filter['type'] == 'daterange')
            <div class="col-auto input-daterange">
                <label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
                <div class="input-group float-left" style="width: 46%;">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">From</span>
                    </div>
                    <input type="text" readonly class="form-control dt-picker from_date" name="from_date" id="from_date" value="{{$filter['fromdate']}}"/>
                </div>
                <div class="align-items-center d-flex ml-1 mr-1 float-left todiv" style="min-height: 37px;">-</div>
                <div class="input-group float-left" style="width: 46%;">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">To</span>
                    </div>
                    <input type="text" readonly class="form-control dt-picker to_date" name="to_date" id="to_date" value="{{$filter['todate']}}"/>
                </div>
            </div>
            <script type="text/javascript">
                function getDate( element ) {
                    var date;
                    try {
                        date = $.datepicker.parseDate( 'mm/dd/yy', element.value );
                    } catch( error ) {
                        date = null;
                    }
                    return date;
                }
                $(document).ready(function() {
                    $("#from_date").datepicker({
                        dateFormat: 'mm/dd/yy',
                        //maxDate: 0,
                    }).on('change', function () {
                        $('#to_date').datepicker("option", 'minDate', getDate( this ) );
                    });

                    $("#to_date").datepicker({
                        dateFormat: 'mm/dd/yy',
                        //maxDate: 0
                    }).on('change', function () {
                        $('#from_date').datepicker("option", 'maxDate', getDate( this ) );
                    });
                });
            </script>
            @elseif($filter['type'] == 'daterangemissedbucket')
            <div class="col-auto input-daterange">
                <label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
                <div class="input-group float-left" style="width: 46%;">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">From</span>
                    </div>
                    <input type="text" readonly class="form-control dt-picker schedule_from_date" name="schedule_from_date" id="schedule_from_date" value="{{$filter['fromdate']}}"/>
                </div>
                <div class="align-items-center d-flex ml-1 mr-1 float-left todiv" style="min-height: 37px;">-</div>
                <div class="input-group float-left" style="width: 46%;">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">To</span>
                    </div>
                    <input type="text" readonly class="form-control dt-picker schedule_to_date" name="schedule_to_date" id="schedule_to_date" value="{{$filter['todate']}}"/>
                </div>
            </div>
            <script type="text/javascript">
                function getDate( element ) {
                    var date;
                    try {
                        date = $.datepicker.parseDate( 'mm/dd/yy', element.value );
                    } catch( error ) {
                        date = null;
                    }
                    return date;
                }
                $(document).ready(function() {
                    $("#schedule_from_date").datepicker({
                        dateFormat: 'mm/dd/yy',
                        //maxDate: 0,
                    }).on('change', function () {
                        $('#schedule_to_date').datepicker("option", 'minDate', getDate( this ) );
                    });

                    $("#schedule_to_date").datepicker({
                        dateFormat: 'mm/dd/yy',
                        //maxDate: 0
                    }).on('change', function () {
                        $('#schedule_from_date').datepicker("option", 'maxDate', getDate( this ) );
                    });
                });
            </script>
            @elseif($filter['type'] == 'coveragetrackerdaterange')
            <div class="col-auto input-daterange">
                <label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
                <div class="input-group float-left" style="width: 46%;">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">From</span>
                    </div>
                    <input type="text" readonly class="form-control dt-picker coveragetracker_from_date" name="coveragetracker_from_date" id="coveragetracker_from_date" value="{{$filter['fromdate']}}" data-defaultval="{{$filter['fromdate']}}"/>
                </div>
                <div class="align-items-center d-flex ml-1 mr-1 float-left todiv" style="min-height: 37px;">-</div>
                <div class="input-group float-left" style="width: 46%;">
                    <div class="input-group-prepend">
                        <span class="input-group-text" id="basic-addon1">To</span>
                    </div>
                    <input type="text" readonly class="form-control dt-picker coveragetracker_to_date" name="coveragetracker_to_date" id="coveragetracker_to_date" value="{{$filter['todate']}}" data-defaultval="{{$filter['todate']}}"/>
                </div>
            </div>
            <script type="text/javascript">
                function getDate( element ) {
                    var date;
                    try {
                        date = $.datepicker.parseDate( 'mm/dd/yy', element.value );
                    } catch( error ) {
                        date = null;
                    }
                    return date;
                }
                $(document).ready(function() {
                    $("#coveragetracker_from_date").datepicker({
                        dateFormat: 'mm/dd/yy',
                        //maxDate: 0,
                    }).on('change', function () {
                        $('#coveragetracker_to_date').datepicker("option", 'minDate', getDate( this ) );
                    });

                    $("#coveragetracker_to_date").datepicker({
                        dateFormat: 'mm/dd/yy',
                        //maxDate: 0
                    }).on('change', function () {
                        $('#coveragetracker_from_date').datepicker("option", 'maxDate', getDate( this ) );
                    });
                });
            </script>
            @elseif($filter['type'] == 'singledate')
            <div class="col-auto input-daterange">
                <label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
                <div class="input-group float-left" style="width: 92%;">
                    <input type="text" class="form-control dt-picker date_picker"
                    name="{{$filter['fromName']}}"
                    id="{{$filter['fromName']}}" title="{{$filter['label']}}" placeholder="{{$filter['label']}}" value=""/>
                </div>
            </div>

            <script type="text/javascript">
                $(document).ready(function () {
                    $("#<?php echo $filter['fromName'] ?>").datepicker({
                        dateFormat: 'mm/dd/yy',
                        // maxDate: 0,
                    });
                });
            </script>
            @else
            <div class="col-auto">
                <label class="sr-only" for="{{$filter['name']}}">{{$filter['label']}}</label>
                <input type="{{$filter['type']}}" name="{{$filter['name']}}" class="form-control"
                    id="{{$filter['name']}}" placeholder="{{$filter['label']}}" value="{{!empty($filter['value']) ? $filter['value'] : ''}}" data-defaultval="{{!empty($filter['defaultvalue']) ? $filter['defaultvalue'] : ''}}"/>
            </div>
            @endif
            @endif
            @endif
            @endforeach
            <div class="col-auto">
                <button type="submit" id="datatableFilterSearch" class="btn btn-primary">Search</button>
            </div>
            <div class="col-auto">
                <button type="button"  data-filter="<?php echo htmlspecialchars(json_encode($filters),ENT_QUOTES, 'UTF-8'); ?>" data-formname="<?php echo $frmName?>" class="btn btn-primary clearall">Clear All</button>
            </div>
        </div>
    </form>
    @includeWhen(($isShowAllEnabled ?? false), 'consentTemplateTracking.showAll', ['showAllRouteName' => $showAllRouteName, 'label' => $label])
</div>




<script>

$(document).on("click","#<?php echo $frmName?> .clearall",function(e){
   var frmObject = $("#"+$(this).attr("data-formname"));
   var filters =  jQuery.parseJSON($(this).attr("data-filter"));

   $.each(filters, function(i, item) {
        var elem = frmObject.find("#"+item.name);
        if(item.type=='checkbox') {
            if(item.value==true){
                elem.prop('checked', true);
            } else {
                elem.prop('checked', false);
            }
        } else if(item.type=='select') {
            elem.val(elem.prop("selectedIndex", 0).val())
        } else if(item.type=='multiselect') {
            elem.multiselect("clearSelection");
            if(item.disabled== true) {
                elem.multiselect('disable');
            }
        } else if(item.type == 'daterange') {
            $('#from_date').val(item.fromdate);
            $('#to_date').val(item.todate);
        }else if(item.type == 'dateRangePicker') {
            if ($('#' + item.fromName).length > 0 && item.fromdate != '') {
                $('#' + item.fromName).val(item.fromdate);
            } else {
                $('.fromDateInput').val('');
                $("#"+item.fromName).datepicker("option", 'maxDate', null );
            }
            if ($('#' + item.toName).length > 0 && item.todate != '') {
                $('#' + item.toName).val(item.todate);
            } else {
                $('.toDateInput').val('');
                $("#"+item.toName).datepicker("option", 'minDate', null );
            }
        } else if(item.type == 'coveragetrackerdaterange') {
            $('#coveragetracker_from_date').val(($('#coveragetracker_from_date').data('defaultval') ? $('#coveragetracker_from_date').data('defaultval') : ''));
            $('#coveragetracker_to_date').val(($('#coveragetracker_to_date').data('defaultval') ? $('#coveragetracker_to_date').data('defaultval') : ''));
        } else {
            elem.val((elem.data('defaultval') ? elem.data('defaultval') : ''));
        }
    });
    if($(this).attr("data-formname")=='cccmissedvisitsform'){
        $("#schedule_from_date").val('');
        $("#schedule_to_date").val('');
    }
   frmObject.trigger("submit");
});


$(document).on('submit', '#datatableFilterSearchForm', function(e) {
    e.preventDefault();
    $('.dataTable').DataTable().ajax.reload();
});

$(document).ready(function() {
    $('.selectpicker').multiselect({
        includeSelectAllOption: true,
        @if(LD::checkFeatureFlag('ipea-filter-group-selection'))
            enableClickableOptGroups: true,
            enableCollapsibleOptGroups: true
        @endif
    });
    $('.selectpickersearch').multiselect({
        includeSelectAllOption: true,
        enableCaseInsensitiveFiltering: true,
        @if(LD::checkFeatureFlag('ipea-filter-group-selection'))
            enableClickableOptGroups: true,
            enableCollapsibleOptGroups: true
        @endif
    });
    $('.dt-picker').datepicker().bind('click', function() {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });
});


</script>
