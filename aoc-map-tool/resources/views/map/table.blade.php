<?php
use App\Helpers\H;
use App\Map;
use App\Helpers\LD;
use Illuminate\Support\Facades\Session;

$loggedInUser = H::getLoggedInUser();
$mapInfo = H::getSelectedMapInfo(true);
$parentMap = $mapInfo[0];
$childLayers = $parentMap->layers ?? [];
$isProtected = $parentMap->is_protected;

$showMasstextOption = (Auth::user()->sending_sms_number == '') ? 0 : 1;
$smsLimit = 50;
if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
    $smsLimit = is_numeric($loggedInUser['sms_radius_limit']) ? $loggedInUser['sms_radius_limit'] : 50;
}
$showMassTextOptionMapArray = LD::checkFeatureFlag('fl-bon-mass-texting')
    ? ['maps_textblast','maps_covclin','candidates','maps_bon_fl']
    : ['maps_textblast','maps_covclin','candidates'];

$tableConfigs = [];

$parentJsonFields = json_decode($parentMap->fields_datatable);
$parentHeaders = $parentJsonFields->datatable ?? [];
$parentHeadings = $parentJsonFields->table_heading ?? [];

$extraFieldData = [];
$defaultSort = 1;
$defaultOrder = 'asc';

$isNth = H::getHelper('ConstantHelper')->isNTHMap($parentMap->map_tablename);
$isNthAlerts = ($parentMap->map_tablename === 'maps_nthalerts');
$isCovClin = H::getHelper('ConstantHelper')->isCoverageClinicianMap($parentMap->map_tablename);
$isCoverageMap = (LD::checkFeatureFlag('coverage-tracker-map') && H::getHelper('ConstantHelper')->isCoverageMap($parentMap->map_tablename)) ? 1 : 0;

if($isNth) {
    if(!in_array("has_payer_group", $parentHeaders)) {
        $extraFieldData = ["has_payer_group"];
        $defaultOrder = 'desc';
    }
    $parentHeaders = array_merge($parentHeaders, $extraFieldData);
    $parentHeadings = array_merge($parentHeadings, $extraFieldData);
    $defaultSort = array_search("has_payer_group", $parentHeaders) + 1;
}

if ($isNthAlerts) {
    $parentHeaders = array_merge(array_slice($parentJsonFields->datatable, 0, 17), ['Subscription_Status', 'SMS_Status', 'Preference_Status']);
    $parentHeadings = array_merge(array_slice($parentJsonFields->table_heading, 0, 17), ['Subscription Status', 'SMS Status', 'Preference Status']);
}

$isCheckBoxParent = 0;
if ((LD::checkFeatureFlag('show-mass-text-option') && H::canPermissions('save.massmessage') && in_array($parentMap->map_tablename, $showMassTextOptionMapArray) && $showMasstextOption == 1) ||
    (LD::checkFeatureFlag('coverage-clinician-map-prn-process') && $isCovClin)) {
    $isCheckBoxParent = 1;
}

$tableConfigs[$parentMap->map_id] = [
    'map_id' => $parentMap->map_id,
    'map_name' => $parentMap->map_name,
    'pin_color' => $parentMap->pin_color ?? '#FFFFFF',
    'pin_icon' => $parentMap->pin_icon ?? 'diamond',
    'map_tablename' => $parentMap->map_tablename,
    'headers' => $parentHeaders,
    'headings' => $parentHeadings,
    'defaultSort' => $defaultSort,
    'defaultOrder' => $defaultOrder,
    'isCheckBox' => $isCheckBoxParent,
    'isCoverageMap' => $isCoverageMap,
    'isNthAlerts' => $isNthAlerts,
    'isNth' => $isNth,
    'canAddNurse' => (H::canPermissions('getScheduleForm') && in_array($parentMap->map_name, config('constant.addNurseToMap'))) ? 1 : 0,
    'canRemovePNM' => ($parentMap->map_tablename == 'maps_potential_nurse' && LD::checkFeatureFlag('remove-from-potential-nurse-map') && H::canPermissions('map.removePNM')) ? 1 : 0
];


foreach($childLayers as $layer) {
    $jsonFields = json_decode($layer->fields_datatable);
    if(!$jsonFields) continue;

    $tableConfigs[$layer->map_id] = [
        'map_id' => $layer->map_id,
        'map_name' => $layer->map_name,
        'pin_color' => $layer->pin_color ?? '#FFFFFF',
        'pin_icon' => $layer->pin_icon,
        'map_tablename' => $layer->map_tablename,
        'headers' => $jsonFields->datatable ?? [],
        'headings' => $jsonFields->table_heading ?? [],
        'defaultSort' => 1,
        'defaultOrder' => 'asc',
        'isCheckBox' => 0,
        'isCoverageMap' => 0,
        'isNthAlerts' => false,
        'isNth' => false,
        'canAddNurse' => 0,
        'canRemovePNM' => 0
    ];
}
?>
@extends('layouts.myaccount')

@section('title', $parentMap->map_name.' Chart View')

@section('content')

    <button type="button" id="showmodel" style="display:none;" class="btn btn-primary" data-toggle="modal" data-target="#exampleModal">Launch demo modal</button>

    <style>
        /* --- Expand/Collapse control cell --- */
        table.dataTable tbody td.dt-control { cursor: pointer; }
        table.dataTable tbody td.dt-control::before { font-family: "FontAwesome"; content: "\f067"; display: inline-block; margin-right: 8px; }
        table.dataTable tbody tr.shown td.dt-control::before { content: "\f068"; }
        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control:before, table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control:before { margin-top: 0px; position: relative; display: inline-block; left: 0px; }
        table.dataTable.dtr-inline.collapsed>tbody>tr>td.dtr-control, table.dataTable.dtr-inline.collapsed>tbody>tr>th.dtr-control { padding-left: 0px; }
        td.dt-control.dtr-control:first-child { min-width: 75px; }
        .child-wrap { padding: 8px 12px; }
        .child-wrap table { width: 100%; }
        .child-wrap table td { padding: 4px 6px; vertical-align: top; }
        .child-wrap table td.label { width: 28%; font-weight: 600; white-space: nowrap; }

        /* --- Tabs Styling --- */
        .table-tabs { display: flex; background: #fff; border-bottom: 1px solid #dde0e5; flex-shrink: 0; overflow-x: auto; margin-bottom: 15px; border-radius: 8px 8px 0 0; clear: both; width: 100%;}
        .table-tab { padding: 12px 18px; font-size: 14px; cursor: pointer; color: #6b7280; border-bottom: 3px solid transparent; display: flex; align-items: center; gap: 7px; white-space: nowrap; transition: 0.2s; }
        .table-tab:hover { color: #1a3a5c; background: #f9fafb; }
        .table-tab.active { color: #1a3a5c; border-bottom-color: #1a3a5c; font-weight: 600; background: #fff; }
        .tab-dot { width: 12px; height: 12px; border-radius: 50%; flex-shrink: 0; box-shadow: 0 1px 3px rgba(0,0,0,0.2); }
        .tab-count { font-size: 11px; color: #6b7280; background: #f3f4f6; border-radius: 10px; padding: 2px 8px; font-weight: normal; margin-left: 5px;}
        .table-view { display: none; }
        .table-view.active { display: block; }
    </style>

    <div class="heading-title-map">
        <h2>{{ $parentMap->map_name }}<span class="totalhours"></span></h2>
        @if(!empty($parentMap->description))
            <p>{{ $parentMap->description }}</p>
        @endif

        <a title="Map View" class="" href="{{route('map.map')}}"><i class="fa fa-map"></i></a>

        <div class="menu-cnt">
            <ul class="list-unstyled components">
                @canAnyPermission(['map.import','map.configurations'])
                @if($isProtected == 0)
                    <li><a href="#setting-data" title="Configure Map" data-toggle="collapse" class="dropdown-toggle"><i class="fa fa-cog"></i></a>
                        <ul class="list-unstyled collapse" id="setting-data">
                            @if(Session::has('selectedmap'))
                                @canPermission('map.import')
                                <li><a href="{{route('map.import')}}"><i class="fa fa-upload"></i>Import Map Data</a></li>
                                @endcanPermission
                                @canPermission('map.configurations')
                                <li><a href="{{route('map.configurations')}}"><i class="fa fa-cogs"></i>Configure Map</a></li>
                                @endcanPermission
                            @else
                                @canPermission('map.import')
                                <li><a href="javascript:void(0);" id="importunselected"><i class="fa fa-upload"></i>Import Map Data</a></li>
                                @endcanPermission
                                @canPermission('map.configurations')
                                <li><a href="javascript:void(0);" id="importunselectedconfig"><i class="fa fa-cogs"></i>Configure Map</a></li>
                                @endcanPermission
                            @endif
                        </ul>
                    </li>
                @endif
                @endcanAnyPermission
            </ul>
        </div>
        <a title="Download Data" href="javascript:void(0)" id="download-data"><i class="fa fa-download"></i></a>

        <?php if(H::getHelper('ConstantHelper')->isTextBlastMap($parentMap->map_tablename)) { ?>
            <?php if (LD::checkFeatureFlag('updatetonth')) { ?>
        <a title="Update to NTH" href="javascript:void(0)" id="update-nth"><i class="fa fa-check-circle"></i></a>
        <input type="hidden" name="textBlastCount" id="textBlastCount">
        <?php } }?>
        <a class="" title="Filters" id="mobile-filter-btn" href="javascript:void(0);"><i class="fa fa-filter"></i></a>

        <?php if ($tableConfigs[$parentMap->map_id]['isCoverageMap']) { ?>
        @canPermission('coverage.save')
        @if (LD::checkFeatureFlag('add-to-tracker-from-dashboard-ccc'))
            <button class="btn primary-btn p-0 pl-2 pr-2 hide btn-info" title="Add to Tracker" id="addCoverageTracker2" onclick="addCoverageShiftTracker('map_buckets', 'map_buckets')"><small>Add to Tracker</small></button>
        @else
            <button class="btn primary-btn p-0 pl-2 pr-2 hide btn-info" title="Add to Tracker" id="addCoverageTracker"><small>Add to Tracker</small></button>
        @endif
        @endcanPermission
        <?php } ?>
    </div>

    @if ($errors->any())
        <div class="col-sm-12">
            <div class="alert  alert-warning alert-dismissible fade show" role="alert">
                @foreach ($errors->all() as $error)<span><p>{{ $error }}</p></span>@endforeach
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="col-sm-12">
            <div class="alert  alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
        </div>
    @endif

    @if(count($tableConfigs) > 1)
        <div style="clear: both;"></div>
        <div class="table-tabs">
            @foreach($tableConfigs as $index => $config)
                <div class="table-tab {{ $loop->first ? 'active' : '' }}" onclick="switchTableTab(this, 'view-layer-{{ $config['map_id'] }}')">
                    <x-layer-icon-shape :icon="$config['pin_icon']" :color="$config['pin_color']" />
                    {{ $config['map_name'] }}
                    <span class="tab-count" id="count-layer-{{ $config['map_id'] }}">...</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="data-table-container col-md-12 popup-table-container">
        <div class="tag-container"><ul id="clicked-tags"></ul></div>

        @foreach($tableConfigs as $index => $config)
            <div id="view-layer-{{ $config['map_id'] }}" class="table-view {{ $loop->first ? 'active' : '' }}">

                @if($config['isCheckBox'])
                    <div id="new_dataTable_wrapper_{{ $config['map_id'] }}" style="float:left; width:100%;"></div>
                @endif

                <table id="table-{{ $config['map_id'] }}" class="table table-bordered data-table layer-data-table" data-layer-id="{{ $config['map_id'] }}" style="width:100%;">
                    <thead>
                    <tr>
                        @if($config['isCoverageMap'])
                            <th><input class="form-input selectAllCoverageMap" data-layer-id="{{ $config['map_id'] }}" type="checkbox" value="0" /></th>
                        @endif

                        @if($config['isCheckBox'])
                            <th><input class="form-input select-all-checkbox-candidate" data-layer-id="{{ $config['map_id'] }}" type="checkbox" value="0" /></th>
                        @endif
                        <th width="50"></th>
                        @foreach($config['headings'] as $heading)
                            <th>{{str_replace('_',' ',$heading)}}</th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        @endforeach
    </div>

    <x-modal>
        <x-slot name="id">routeDistanceInfo</x-slot>
        <x-slot name="title">Driving Distance</x-slot>
        <x-slot name="body"><table class="table" id="routeDistanceInfoTable"></table></x-slot>
        <x-slot name="footer"></x-slot>
    </x-modal>

    <x-modal>
        <x-slot name="id">update_nth_model</x-slot>
        <x-slot name="title">Info</x-slot>
        <x-slot name="body">
            <div class="table" id="update_nth_model_div" style="display: none;">
                <div class="table" id="update_nth_model_table"></div>
                <div class="text-center"><button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button></div>
            </div>
            <div class="table" id="get_nth_text_blast_div"  style="display: none;">
                <div class="table" id="get_nth_text_blast_table"></div>
                <div class="text-center">
                    <button type="button" class="btn btn-secondary" id="updateNthTextBlast">OK</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </x-slot>
        <x-slot name="footer"></x-slot>
    </x-modal>

    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="{{ mix('js/dataTables.responsive.min.js') }}"></script>
    <script type="text/javascript">
        /* === NTH Alerts custom-fields: endpoint + helpers === */
        const CUSTOM_FIELDS_URL_TMPL = "{{ route('custom-fields.clinician', ['clinicianId' => '__ID__']) }}";
        function customFieldsUrl(id){ return CUSTOM_FIELDS_URL_TMPL.replace('__ID__', encodeURIComponent(id)); }

        function esc(v){ return (v===undefined || v===null) ? '' : String(v).replace(/[&<>"]/g, s=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;' }[s])); }
        function escCell(v){ return esc(v); }
        function getMiles(meters) { return (meters * 0.000621371192).toFixed(2); }
        function getTravelTime(totalSeconds) {
            let hours = Math.floor(totalSeconds / 3600);
            totalSeconds %= 3600;
            let minutes = Math.floor(totalSeconds / 60);
            let seconds = totalSeconds % 60;
            return String(hours).padStart(2,"0")+":"+String(minutes).padStart(2,"0")+":"+String(seconds).padStart(2,"0");
        }

        if (typeof CSS === 'undefined' || typeof CSS.escape !== 'function') {
            window.CSS = window.CSS || {};
            CSS.escape = function(value) {
                return String(value).replace(/[^a-zA-Z0-9_\-]/g, function(ch) {
                    const hex = ch.charCodeAt(0).toString(16).toUpperCase();
                    return '\\' + hex + ' ';
                });
            };
        }

        function renderCustomFieldsBlock(payload){
            const arr = Array.isArray(payload) ? payload : (payload && Array.isArray(payload.data)) ? payload.data : [];
            if (!arr.length) return `<tr><td class="label">Custom Fields</td><td><em>No custom fields found</em></td></tr>`;
            const byCat = {};
            arr.forEach(f => {
                const cat = f.category || 'General';
                (byCat[cat] ||= []).push(f);
            });
            let html = 'Custom fields';
            Object.keys(byCat).forEach(cat => {
                html += `<tr><td class="label" colspan="2"><strong>${esc(cat)}</strong></td></tr>`;
                byCat[cat].forEach(f => {
                    let label = f.frontend_label || f.label || f.db_field_name || 'Field';
                    let value = f.value;
                    if (Array.isArray(value)) value = value.join(', ');
                    if (value === null || value === undefined || value === '') value = '—';
                    html += `<tr><td class="label">${esc(label)}</td><td class="value">${esc(value)}</td></tr>`;
                });
            });
            return html;
        }

        var pinbarItems = [];
        var downloadURL = '{{route("map.downloaddata")}}';
        let _token = $('meta[name="csrf-token"]').attr('content');

        var tableConfigs = @json($tableConfigs);
        var dataTablesInstances = {};

        function switchTableTab(el, viewId) {
            $('.table-tab').removeClass('active');
            $(el).addClass('active');
            $('.table-view').removeClass('active');
            $('#' + viewId).addClass('active');

            let layerId = viewId.replace('view-layer-', '');
            if(dataTablesInstances[layerId]) {
                dataTablesInstances[layerId].columns.adjust().responsive.recalc();
            }
        }

        function showHideButtonForMassText(isChecked, layerId) {
            let config = tableConfigs[layerId];
            let wrapperId = '#new_dataTable_wrapper_' + layerId;
            $(wrapperId + ' div.updateall').remove();
            $(wrapperId).empty();

            if (isChecked && config.isCheckBox) {
                var html = '<div class="row pb-2 pb-2"><div class="col-md-6 pl-1 text-left"><div style="display: inline-block;" class="dataTables_length updateall">';
                html += '&nbsp;&nbsp;&nbsp;<button type="button" onclick="getMassTextForms(\'maps_textblast\')" class="btn btn-primary">Send Message</button>';

                if(config.map_tablename === 'maps_covclin') {
                    html+='&nbsp;&nbsp;&nbsp;<button class="btn btn-primary" title="Add to Tracker" id="addCoverageTrackerFromCoverageClinician" onclick="addCoverageTrackerFromCoverageClinician(\'map_buckets\', \'map_coverageclinician\')">Add to Tracker</button>';
                }
                html += '</div></div><div class="col-md-6 pl-1"></div></div>';
                $(wrapperId).prepend(html);
            }
        }

        function getFieldJSON(config) {
            const cols = [];
            let item = { className: '', orderable: false, data: null };

            if(config.isNthAlerts || config.isNth) {
                item.className = 'dt-control';
            }

            item.defaultContent = "<a href='javascript:void(0);' class='row-pinbar' title='Pin'><i class='fa fa-thumb-tack' aria-hidden='true'></i></a>" +
                "<a href='javascript:void(0);' class='getDirection' data-lat='' data-lng='' title='Directions'><i class='fa fa-arrows-alt' aria-hidden='true'></i></a>";

            if (config.canAddNurse) {
                item.defaultContent += "<a href='javascript:void(0);' class='add-new-nurse' title='Add Nurse'><i class='fa fa-plus-circle fa-fw mr-3' aria-hidden='true'></i></a>";
            }
            if(config.isCoverageMap) {
                item.defaultContent = '<span class="selectCoverageInput"></span>' + item.defaultContent;
            }
            if(config.canRemovePNM) {
                item.defaultContent += "<a href='javascript:void(0);' class='removePNM' title='Remove'><i class='fa fa-trash' aria-hidden='true'></i></a>";
            }

            item.targets = 0;
            cols.push(item);

            $(config.headers).each(function(ind, val) {
                const c = { data: val, name: val };
                if (val === 'has_payer_group') c.visible = false;
                cols.push(c);
            });

            return cols;
        }

        $(document).ready(function() {
            var dataTableDelay = 1000;

            $('.layer-data-table').each(function() {
                let tableEl = $(this);
                let layerId = tableEl.data('layer-id');
                let config = tableConfigs[layerId];

                dataTablesInstances[layerId] = tableEl.on('error.dt', function (e, settings, techNote, message) {}).DataTable({
                    processing: true,
                    serverSide: true,
                    responsive: true,
                    fixedHeader: true,
                    order: [[config.defaultSort, config.defaultOrder]],
                    ajax: {
                        "url": "{{ route('map.table') }}",
                        "type": "POST",
                        "data": function(d) {
                            return $.extend({}, d, { '_token': _token, 'isCheckBox': config.isCheckBox, 'layer_id': layerId })
                        }
                    },
                    createdRow: function(row, data, dataIndex) {
                        $(row).find('td').eq(2).attr('data-clientid', data.ClientID);
                        $(row).find('td').eq(0).attr("nowrap", "nowrap");

                        if (config.isNthAlerts) {
                            var cid = data.ClinicianID || data.Clinician_ID || data.id || data.ProviderID || data.aoc_provider_id || '';
                            if (cid) $(row).attr('data-clinicianid', cid);
                        }

                        if(config.isCoverageMap) {
                            $(row).attr('data-id',data.id);
                            if(typeof data.aoc_patient_id !== 'undefined') $(row).attr('data-patient_id', data.aoc_patient_id);
                            if(typeof data.Name !== 'undefined') $(row).attr('data-patient_name', data.Name);
                            $(row).attr('data-ccc_id', -1);
                            if(typeof data.Schedule_Time !== 'undefined') $(row).attr('data-schedule_time', data.Schedule_Time);
                        }

                        if(config.isCheckBox) {
                            $(row).attr('data-id',data.id);
                            var cId2 = data.Clinician_ID || data.ClinicianID || data.id;
                            $(row).attr('data-clinicianid', cId2);
                            if(data.distance) $(row).attr('data-distance',data.distance);

                            if(typeof data.PatientID !== 'undefined') $(row).attr('data-patientid', data.PatientID);
                            if(typeof data.Days_Available !== 'undefined') $(row).attr('data-days_available', data.Days_Available);
                            if(typeof data.Shift_Type !== 'undefined') $(row).attr('data-shift_type', data.Shift_Type);
                            if(typeof data.Patient_FirstName !== 'undefined') $(row).attr('data-patientname', data.Patient_FirstName+' '+data.Patient_LastName);
                            if(typeof data.FirstName !== 'undefined') $(row).attr('data-clinicianname', data.FirstName+' '+data.LastName);
                            if(typeof data.ProviderID !== 'undefined') $(row).attr('data-providerid', data.ProviderID);
                            if(typeof data.aoc_provider_id !== 'undefined') $(row).attr('data-aocproviderid', data.aoc_provider_id);
                        }
                    },
                    "initComplete": function(settings, json) {
                        if (config.isNthAlerts) tableEl.removeClass('dtr-inline collapsed');

                        var filterInput = $('#table-' + layerId + '_filter input');
                        filterInput.off('keyup.DT input.DT');
                        var searchDelay = null;
                        filterInput.on('keyup', function(e) {
                            var search = $(this).val();
                            clearTimeout(searchDelay);
                            searchDelay = setTimeout(function() {
                                if (search != null && search.length >= 3) dataTablesInstances[layerId].search(search).draw();
                                if (search.length == 0 && e.keyCode == 8) dataTablesInstances[layerId].search(search).draw();
                            }, dataTableDelay);
                        });
                    },
                    "drawCallback": function(settings) {
                        var response = settings.json;

                        $('#count-layer-' + layerId).text(Number(response.recordsTotal).toLocaleString('en'));
                        if (response.totalhours > 0 && tableEl.closest('.table-view').hasClass('active')) {
                            var fixedNumbr = parseFloat(response.totalhours).toFixed();
                            $(".totalhours").html("(" + Number(fixedNumbr).toLocaleString('en') + " Hours) ");
                        }

                        if (config.isCoverageMap) {
                            $(this).find("tbody tr").each(function() {
                                $(this).find("td:first-child").parent().prepend('<td class="multi-select"><input class="form-input" type="checkbox" name="selectRow[]" value="0" /></td>');
                            });
                        }

                        if (config.isCheckBox) {
                            $(this).find("tbody tr").each(function() {
                                $(this).find("td:first-child").parent().prepend('<td class="multi-select"><input class="form-input td select-all-candidate" type="checkbox" name="checkbox-candidate[]" value="'+$(this).data('clinicianid')+'" /></td>');
                            });

                            tableEl.find('td.multi-select input[type="checkbox"]').off().bind('click', function() {
                                showHideButtonForMassText(tableEl.find('td.multi-select input[type="checkbox"]:checked').length ? true : false, layerId);
                            });
                        }

                        tableEl.find('thead th').each(function(index) {
                            if ($(this).text().trim() === '') $(this).removeClass('sorting sorting_asc sorting_desc').off('click.DT');
                        });

                        $(response.data).each(function(ind, val) {
                            if(val.isPriorityzed === true || val.custom_sort_prn > 0) {
                                tableEl.find('tr').eq(ind + 1).addClass("prioritycolor");
                            }
                            if (val.distance == '-') {
                                tableEl.find('tr').eq(ind + 1).find('.getDirection').hide();
                            } else {
                                tableEl.find('tr').eq(ind + 1).find('.getDirection').show().attr('data-lat', val.lat).attr('data-lng', val.lng);
                            }
                        });
                    },
                    columns: getFieldJSON(config)
                });

                // Child row click & expand
                dataTablesInstances[layerId].on('responsive-display', function (e, datatable, row, showHide, update) {
                    if (showHide) {
                        datatable.responsive.recalc();
                        if(datatable.fixedHeader) datatable.fixedHeader.adjust();

                        if (config.isNthAlerts) {
                            let childRow = row.child();
                            if (childRow && childRow.length) {
                                if($(childRow).find('.additional-content').length === 0) {
                                    let d = row.data() || {};
                                    let clinicianId = d.ClinicianID || d.Clinician_ID || d.id || d.ProviderID || d.aoc_provider_id;
                                    if (!clinicianId) clinicianId = row.node().getAttribute('data-clinicianid');

                                    if (clinicianId) {
                                        $.getJSON(customFieldsUrl(clinicianId)).done(resp => {
                                            const html = renderCustomFieldsBlock(resp);
                                            $(childRow).find('ul').append(`<table class="table table-sm table-borderless mb-0">${html}</table>`);
                                        }).fail(() => {
                                            $(childRow).find('ul').html('<div class="text-danger">Failed to load custom fields.</div>');
                                        });
                                    }
                                }
                            }
                        }
                    }
                });
            });

            $('.layer-data-table tbody').on('click', 'td.dt-control a', function(e){ e.stopPropagation(); });

            $('.layer-data-table tbody').on('click', '.getDirection', function(e) {
                var self = $(this);
                e.stopPropagation();
                $.ajax({
                    url: "{{route('map.getRouteDistance')}}",
                    type: "get",
                    data: { from: searchLatLong, to: self.attr('data-lat') + ',' + self.attr('data-lng') },
                    success: function(response) {
                        if (response.status == true) {
                            $('#routeDistanceInfoTable').html('');
                            $.each(response.data.routes, function(i, item) {
                                $('<tr>').append($('<td>').html('<strong>Distance(Miles)</strong>'), $('<td>').text(getMiles(item.summary.lengthInMeters))).appendTo('#routeDistanceInfoTable');
                                $('<tr>').append($('<td>').html('<strong>Travel Time</strong>'), $('<td>').text(getTravelTime(item.summary.travelTimeInSeconds))).appendTo('#routeDistanceInfoTable');
                            });
                            $('#routeDistanceInfo').modal('show');
                        }
                    }
                });
            });

            $('.layer-data-table tbody').on('click', '.row-pinbar', function(e) {
                e.stopPropagation();
                var tr  = $(this).closest('tr');
                var tableEl = $(this).closest('table');
                var layerId = tableEl.data('layer-id');
                var row = dataTablesInstances[layerId].row(tr);
                var text = $(tr).find("td").eq(2).html();

                var dataId = row.data().id || row.data().ClientID || row.data().ClinicianID;
                if (pinbarItems.indexOf(dataId) == -1) {
                    $("#clicked-tags").append("<li class='clickeable-tag' data-label-id='"+ dataId +"'>"+ text +"<i class='fa fa-close'></i></li>");
                    pinbarItems.push(dataId);
                }
                return false;
            });

            $('.layer-data-table tbody').on('click', '.add-new-nurse', function(e) {
                e.stopPropagation();
                var tr = $(this).closest('tr');
                var clientid = $(tr).find("td").eq(2).attr('data-clientid');
                if (typeof $('#candidateId').val() != 'undefined' && $('#candidateId').val() != '') {
                    showScheduleForm('', 'candidates##' + $('#candidateId').val(), clientid, 0);
                } else {
                    showScheduleForm('', '', clientid, 0);
                }
                return false;
            });

            $(document).on('click', '.select-all-checkbox-candidate', function() {
                let layerId = $(this).data('layer-id');
                let tableEl = $('#table-' + layerId);
                tableEl.find('td.multi-select input[type="checkbox"]').prop('checked', $(this).prop("checked"));
                showHideButtonForMassText($(this).is(':checked'), layerId);
            });

            $(document).on('click', '.clickeable-tag', function(e) {
                e.stopPropagation();
                if($(e.target).is('i')) return;
                var self = $(this);
                $.ajax({
                    url: "{{route('map.getlocationdata')}}",
                    type: "get",
                    data: { facilityID: self.attr("data-label-id") },
                    success: function(response) {
                        $("#exampleModal .modal-body").html(response);
                        $("#showmodel").trigger("click");
                    }
                });
                return false;
            });

            $(document).on('click', '#clicked-tags li i', function(e) {
                e.stopPropagation();
                var dataId = $(this).parent().attr('data-label-id');
                var index = pinbarItems.findIndex(item => String(item) === String(dataId));
                if (index > -1) pinbarItems.splice(index, 1);
                $(this).parent().remove();
            });

            $(document).on("click", "#download-data", function() {
                var dtSearch = $('input[type="search"]:visible').val() || '';

                var activeLayerId = $('.table-view.active .layer-data-table').data('layer-id');

                var mainMapId = {{ $parentMap->map_id }};

                var finalUrl = downloadURL + "?dtsearch=" + encodeURIComponent(dtSearch);

                if (activeLayerId && activeLayerId !== mainMapId) {
                    finalUrl += "&layer_id=" + activeLayerId;
                }
                window.location.href = finalUrl;
            });

            $(document).on('click', '#search',function() {
                $('.select-all-candidate').prop('checked', false);
                $('.select-all-checkbox-candidate').prop('checked', false);
                $('[id^="new_dataTable_wrapper_"] div.updateall').remove();
            });

            $(document).on('click', 'td.multi-select', function(e) {
                let tableEl = $(this).closest('table');
                let anyChecked = tableEl.find("td.multi-select input[type='checkbox']:checked").length > 0;
                if(anyChecked){
                    $("#addCoverageTracker2, #addCoverageTracker").removeClass('hide');
                } else {
                    $("#addCoverageTracker2, #addCoverageTracker").addClass('hide');
                }
            });

            $(document).on('click', '.selectAllCoverageMap', function(e) {
                let layerId = $(this).data('layer-id');
                let tableEl = $('#table-' + layerId);
                let isChecked = $(this).is(":checked");

                tableEl.find("td.multi-select input[type='checkbox']").prop("checked", isChecked);
                if(isChecked){
                    $("#addCoverageTracker2, #addCoverageTracker").removeClass('hide');
                } else {
                    $("#addCoverageTracker2, #addCoverageTracker").addClass('hide');
                }
            });

            $(document).on('click', '#addCoverageTracker', function(e) {
                $('#addCoverageTrackerFormModal').modal('show');
            });

            $('#addCoverageTrackerFormModal').on('shown.bs.modal', function (e) {
                var ids = $(".table-view.active td.multi-select input[type='checkbox']:checked").map(function(){
                    return $(this).parent().parent().attr('data-id');
                }).get();
                $('#selectedCoverageRows').val(ids.join(','));
            });
        });
    </script>

    <?php if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
    if(H::getHelper('ConstantHelper')->isTextBlastMap($parentMap->map_tablename)) {?>
    <div class="modal fade" id="radiusSMSDialog" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document"><div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Radius Limit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">Error: You do not have permission to send campaigns beyond a <?php echo $smsLimit?>-mile radius.</div>
                <div class="modal-footer"><button type="button" class="btn btn-primary" data-dismiss="modal">Close</button></div>
            </div></div>
    </div>
    <?php } } ?>

    <div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
         aria-hidden="true">
        <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                </div>
                <div class="modal-body"></div>
            </div></div>
    </div>
@stop
