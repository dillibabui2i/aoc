<?php
use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Route;
use App\Map;


$loggedInUser = H::getLoggedInUser();

$selectedMap = H::getSelectedMap();

$allMaps = H::getAllMaps();

$selectedMapInfo = H::getSelectedMapInfo(true);

$polygonList = H::getPolygonList();

$childLayers = [];
$ringLayerName = '';

$searchData = [];
$defaultPreference = 0 ;
$canValidateRadius = 0;
$radiusLimit = 50;
if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
    $radiusLimit = is_numeric($loggedInUser['sms_radius_limit'])?$loggedInUser['sms_radius_limit']:50;
}
if($selectedMap) {

	$searchDataSession = Session::get($selectedMapInfo[0]->map_tablename);

    $childLayers = $selectedMapInfo[0]->layers ?? [];

    if ($selectedMapInfo[0]->ring_layer_id) {
        foreach ($childLayers as $layer) {
            if ($layer->map_id == $selectedMapInfo[0]->ring_layer_id) {
                $ringLayerName = $layer->map_name;
                break;
            }
        }
    }
    // print_r($searchDataSession);exit;
	if(isset($searchDataSession['lastmapsearch'])) {
		$searchData = $searchDataSession['lastmapsearch'];
	}
    if(!empty($selectedMapInfo[0]->map_preferences)) {
        $defaultPreference = $selectedMapInfo[0]->map_preferences;
    }
    if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
        if(H::getHelper('ConstantHelper')->isTextBlastMap($selectedMapInfo[0]->map_tablename)) {
            $canValidateRadius = 1;
        }
    }


}

 $action = Route::getCurrentRoute()->getActionName();
  $actionName = explode("@",$action)[1];
$isMapPage = 1;
if($actionName=='showMap') {
    $isMapPage = 2;
}



// $mapActive = '';
// $tableActive = '';
// if($actionName=='showMap' || $actionName=='showMap2'){
// 	$mapActive = "active";
// }

// if($actionName=='showTable' || $actionName=='showTableMap2') {
// 	$tableActive = 'active';
// }
?>

<link href="{{ mix('css/bootstrap-datepicker.min.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-datepicker.min.js') }}"></script>
<div id="login-box" class="left-side">
    <a class="" id="mobile-filter-close" href="javascript:void(0);"><i class="fa fa-close"></i></a>
    <div class="login-row">
        <nav id="sidebar">
            <!-- <div class="sidebar-header">
                <div class="logo">
                    <a href="{{route('dashboard')}}"><img src="{{ asset('images/logo.png') }}" /> </a>
                </div>
            </div>
            <div style="clear:both">&nbsp;</div> -->

            <ul class="list-unstyled components">

                @canPermission('map.choosemap')
                <li>
                    <form name="form_switch_map" id="form_switch_map" action="{{route('map.choosemap')}}" method="post">
                        @csrf
                        <!-- {{ csrf_field() }} -->

                        <div class="form-group">
                            <select class="form-control" name="map-selector" id="map-selector">
                                <option value=""> Select Map </option>
                                <?php
								$allMaps = H::arrGroupBy($allMaps, 'map_category');

								foreach($allMaps as $key => $cMap){
									$keys = array_column($cMap, 'map_name');
									array_multisort($keys, SORT_ASC, $cMap);

                                   if(!empty($key)) {
								?>
                                <optgroup label="<?php echo $key;?>">
                                    <?php foreach($cMap as $map){ ?>
                                    <option value="{{$map->map_id}}"
                                        {{($selectedMap==$map->map_id)?"selected = 'selected'": ""}}>
                                        {{$map->map_name}}</option>
                                    <?php } ?>
                                </optgroup>
                                <?php } } ?>

                            </select>
                        </div>
                    </form>
                </li>
                @endcanPermission

                <?php if($selectedMap){ $filterHtml = H::getDynamicFiltersHTMLForMap();?>
                <form name="filter_search" id="filter_search">

                    <li>
                        <div class='filter geolocation' id='filter'>
                            <input type="hidden" id="search_text_hidden" name="search_text" value="" />
                            <input type="hidden" name="mode" value="search" />
                            <input type="hidden" name="isbtnclicked" id="isbtnclicked" value="2" />
                            <input type="hidden" name="ismappage" value="<?php echo $isMapPage?>" />
                            <input type="hidden" id="already_search" name="already_search"
                                value="<?php echo is_countable($searchData) ? count($searchData) : ''?>" />
                            <input type="hidden"
                                value="{{$searchData['candidateId'] ?? ''}}"
                                name='candidateId' id='candidateId' />
                            <?php if($selectedMapInfo[0]->enable_address_filter==1) { ?>

                                <div class="form-group" id='location-cnt'>
                                    <input class="form-control" placeholder="Location" type="text" name='location' value="{{$searchData['location'] ?? ''}}" id='location' autocomplete="off" />
                                    <p id="locationmessage"  class="alert alert-danger" style="display:none;">This is required.</p>
                                    <ul id="searchResult"></ul>
                                    <div class="clear"></div>
                                    <input type="hidden" value="{{$searchData['locationString'] ?? ''}}" name='locationString' id='locationString' />
                                    <input type="hidden" value="{{$searchData['formatedString'] ?? ''}}" name='formatedString' id='formatedString' />
                                </div>
                                <div class="form-group">
                                    <input class="form-control" placeholder="Radius" type="number" name='radius' value="{{$searchData['radius'] ?? ''}}" id='radius' />
                                    <p id="radiusmessage"  class="alert alert-danger" style="display:none;">This is required.</p>
                                    <p id="radius_limit"  class="alert alert-danger" style="display:none;">This is required.</p>
                                </div>

                            <?php } ?>

                            @if(!empty($filterHtml))
                                {!! $filterHtml !!}
                            @endif

                            <div class="form-group clearboth">
                                <input type="button" name="search" id="search" class="btn btn-info login-btn btn-md"
                                    value="Search">
                                <input type="button" name="reset" id="reset" class="btn btn-info login-btn btn-md"
                                    value="Clear Map">
                            </div>
                            <div class="alert alert-danger hide" id="badaddressmsg" role="alert">
                                <span id="msgspan"></span>
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        </div>

                    </li>

                    <li>
                        <div class="grouping-title"> <input type="checkbox" name="enable_grouping" id="enable_grouping"
                                value="1">&nbsp;<label for="enable_grouping">Enable
                                Grouping </label> </div>
                        <div class="grouping-fields-container" style="display:none;">

                            <div class="grouping-fields col-md-12">
                                <select id="grouping-field" class="form-control" name="grouping-field">
                                    <?php $mapGroupfields = H::getTableFieldsForSelectedMap();
                                                    foreach ($mapGroupfields as $key => $fields) { ?>
                                    <option value="{{$fields->Field}}"><?php echo $fields->Field?>
                                    </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div id="grouping-checkbox" class="grouping-checkbox col-md-12">
                            </div>


                        </div>
                    </li>
                </form>
                <?php } ?>

                <?php if($selectedMap){ ?>
                <li>
                    <div class="territories-title">
                        <input type="checkbox" name="enable_territories" id="enable_territories" value="1">
                        <label for="enable_territories">Enable Territories</label>
                    </div>

                    <?php if(count($childLayers) > 0){ ?>
                <li>
                    <div>
                        <a href="#layers-checkbox" data-toggle="collapse" class="dropdown-toggle">Map Layers</a>
                    </div>
                    <div id="layers-checkbox" class="territories-checkbox collapse col-md-12 show">
                        <ul id="mapLayersList" style="list-style: none; padding-left: 0;">
                                <?php foreach($childLayers as $layer){ ?>
                            <li>
                                <input class="mapLayerCheckbox" type="checkbox" id="layer_{{ $layer->map_id }}" value="{{ $layer->map_id }}" checked="checked" />
                                <label for="layer_{{ $layer->map_id }}">
                                    <x-layer-icon-shape :icon="$layer->pin_icon" :color="$layer->pin_color" />
                                    {{$layer->map_name}}
                                </label>
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
                <?php } ?>
                    <?php if($selectedMapInfo[0]->ring_layer_id && $selectedMapInfo[0]->layer_ring_radius): ?>
                <li style="padding: 10px 15px; border-bottom: 1px solid #dde0e5; background: #f9fafb;">
                    <div class="coverage-toggle" style="display: flex; align-items: center; gap: 7px; cursor: pointer;">
                        <input type="checkbox" id="chk-rings" style="width: 14px; height: 14px; cursor: pointer;"
                                <?php echo $selectedMapInfo[0]->show_rings_by_default ? 'checked' : ''; ?>>
                        <label for="chk-rings" style="margin: 0; font-weight: 600; color: #374151; cursor: pointer;">
                            Show coverage rings
                        </label>
                    </div>
                    <div style="font-size: 11px; color: #6b7280; margin-top: 5px; margin-left: 21px;">
                            <?php echo $selectedMapInfo[0]->layer_ring_radius; ?> mi radius · <?php echo $ringLayerName; ?>
                    </div>
                </li>
                <?php endif; ?>

                    <div id="territories-checkbox" class="territories-checkbox col-md-12" style="display:none;">
                        <ul id="polygonList">
                            <?php if(!empty($polygonList)){ ?>
                            <li>
                                <input id="showAllTerritories" type="checkbox" name='showAllTerritories' />
                                <label for="showAllTerritories">Show All</label>
                            </li>
                            <?php } ?>

                            <?php foreach($polygonList as $k => $v){ ?>
                            <li>
                                <input id="{{ $v->name }}" class="polygonCheckbox" type="checkbox"
                                    name='{{$v->name}}' value="{{$v->name}}"
                                    data-coordinates='{{$v->coordinates}}'
                                    data-color="{{$v->color}}" />
                                <label for="{{ $v->name }}"><i class="fa fa-square"
                                        style="color:{{$v->color}}"></i>{{$v->name}}</label>

                                @canAnyPermission(['savepolygone'])
                                <span class="remove-territory pull-right" data-name='{{$v->name}}'
                                    data-id="{{$v->id}}" href="javascript:void(0);"><i
                                        class="fa fa-trash-o"></i></span>
                                <span class="edit-territory pull-right" data-name='{{$v->name}}'
                                    data-id="{{$v->id}}" href="javascript:void(0);"><i
                                        class="fa fa-pencil-square-o"></i></span>
                                @endcanPermission
                            </li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
                <?php } ?>
            </ul>

            <hr />
        </nav>
    </div>

    <!-- <div class="login-sec-row col-md-12">
        <div>
            <p>© Copyright 2022 Angels of Care. All rights reserved.</p>
            <p>
                <a href="#">Privacy Policy</a>

            </p>
            <hr />
        </div>
    </div> -->


</div>

<script>
    var searchLatLong = "{{ isset($searchData['lat']) && isset($searchData['lng']) ? $searchData['lat'] . ',' . $searchData['lng'] : '' }}";
</script>

<script>
$(document).ready(function() {
    $("#map-selector").change(function() {
        $("#form_switch_map").submit();
    });
});
</script>

<?php
$currentControllerName = explode('@',Route::currentRouteAction())[1];

?>

<script>
var currentControllerName = "<?php echo $currentControllerName; ?>"
var mapName = '<?php echo ($selectedMap==1) ? "map1":"map2";?>';
</script>

<script>

    $(document).ready(function() {
        var checkExist = setInterval(function() {
            if (typeof map !== 'undefined' && typeof L !== 'undefined') {
                clearInterval(checkExist);

                window.coverageRingsGroup = L.featureGroup().addTo(map);
                window.config = {
                    ringLayerId: {{ $selectedMapInfo[0]->ring_layer_id ?? 'null' }},
                    ringRadiusMeters: {{ ($selectedMapInfo[0]->layer_ring_radius ?? 0) * 1609.344 }}
                };

                console.log("Coverage Rings Group ініціалізовано успішно");

                $('.mapLayerCheckbox:checked').each(function() {
                    var layerId = $(this).val();
                    loadMapLayer(layerId);
                });

                updateRingsVisibility();
            }
        }, 100);
    });

var loadedMapLayers = {};

function loadMapLayer(layerId) {
    var form = $("#filter_search");

    var requestData = form.serialize() + "&layer_id=" + layerId;

    $.ajax({
        url: "{{route('map.getmainlocations')}}",
        method: "GET",
        data: requestData,
        success: function(data) {
            var clusterRadius = isGroupingEnabled ? 0.01 : 55;

            var layerCluster = new L.MarkerClusterGroup({
                showCoverageOnHover: true,
                zoomToBoundsOnClick: true,
                spiderfyOnMaxZoom: true,
                maxClusterRadius: clusterRadius,
                iconCreateFunction: function(cluster) {
                    var markers = cluster.getAllChildMarkers();
                    var color = markers[0].options.pinColor || '#1a5ca8';
                    var shape = markers[0].options.pinShape || 'circle';

                    return L.divIcon({
                        html: core.map.getCustomClusterHtml(shape, color, cluster.getChildCount()),
                        className: 'custom-cluster-wrapper',
                        iconSize: [40, 40]
                    });
                }
            });

            var dataObject = JSON.parse(JSON.stringify(data));

            for (var u = 0; u < dataObject.features.length; u++) {
                var f = dataObject.features[u];
                var c = f.geometry.coordinates;

                var customIcon;
                if (f.properties.pin_icon && f.properties.pin_color) {
                    customIcon = L.divIcon({
                        className: 'custom-svg-marker',
                        html: core.map.getCustomMapIcon(f.properties.pin_icon, f.properties.pin_color),
                        iconSize: [24, 24],
                        iconAnchor: [12, 24],
                        popupAnchor: [0, -24]
                    });
                } else {
                    customIcon = L.icon(f.properties.icon);
                }

                var marker = L.marker(new L.LatLng(c[1], c[0]), {
                    id: f.properties.id,
                    icon: customIcon,
                    pinColor: f.properties.pin_color,
                    pinShape: f.properties.pin_icon,
                    layerId: layerId
                }).on("click", function (n) {
                    if (n.target.options.id != '-1') {
                        var i = n.target.getPopup();
                        $("#tempLocation").val("-1");

                        $.ajax({
                            url: "{{ route('map.getlocationdata') }}",
                            data: {
                                facilityID: n.target.options.id,
                                layer_id: n.target.options.layerId
                            },
                            success: function (res) {
                                i.setContent(res);
                            },
                        });
                    }
                });

                if (marker.options.id != '-1') {
                    marker.bindPopup("Loading....");
                    layerCluster.addLayer(marker);
                }
                if (layerId == config.ringLayerId) {
                    var ringColor = f.properties.pin_color || '#c43f3f';

                    var radiusInMeters = config.ringRadiusMeters;

                    var ring = L.circle(
                        new L.LatLng(c[1], c[0]),
                        radiusInMeters,
                        {
                            color: ringColor,
                            weight: 2,
                            dashArray: '8, 5',
                            fillColor: ringColor,
                            fillOpacity: 0.06,
                            interactive: false
                        }
                    );

                    coverageRingsGroup.addLayer(ring);
                }
            }

            map.addLayer(layerCluster);
            loadedMapLayers[layerId] = layerCluster;
            updateRingsVisibility();
        }
    });
}

$(document).on('change', '.mapLayerCheckbox', function() {
    var layerId = $(this).val();

    if ($(this).is(':checked')) {
        if (loadedMapLayers[layerId]) {
            map.addLayer(loadedMapLayers[layerId]);
        } else {
            loadMapLayer(layerId);
        }
    } else {
        if (loadedMapLayers[layerId]) {
            map.removeLayer(loadedMapLayers[layerId]);
        }
    }
    updateRingsVisibility();
});

    $(document).on('change', '#chk-rings', function() {
        updateRingsVisibility();
    });


    function updateRingsVisibility() {
        if (typeof map === 'undefined' || typeof config === 'undefined' || !config.ringLayerId) return;

        var isRingsChecked = $('#chk-rings').is(':checked');
        var isLayerChecked = $('#layer_' + config.ringLayerId).is(':checked');

        if (isRingsChecked && isLayerChecked) {
            if (!map.hasLayer(coverageRingsGroup)) {
                map.addLayer(coverageRingsGroup);
            }
        } else {
            if (map.hasLayer(coverageRingsGroup)) {
                map.removeLayer(coverageRingsGroup);
            }
        }
    }

var isSearched = $("#already_search").val();

$(function() {
    $(".collapse").on('show.bs.collapse', function(e) {
        if ($(this).is(e.target)) {
            $('.menu-cnt').find('.collapse.show').removeClass('show');
        }
    })
});

var allMarkers = [];
var isGroupingEnabled = false;
var clickBtnPref = <?php echo $defaultPreference?>;
var requiredJson = '<?php echo json_encode(H::getRequiredFields());?>';
function validateFilters(){
    req = jQuery.parseJSON(requiredJson);
    var isValid = true;
    var location = $("#location").val();
    var radius = $("#radius").val();
    $.each(req, function( index, value ) {
       var last = value.slice(-1);
       if(last=="9") {
            str = value.split("-");
            var fieldName = "element-"+str[0]+"__"+str[1];
            if($("#"+fieldName).val()=="") {
                isValid = false;
               // console.log(value);
                $(".parent-"+value).addClass("show");
                $(".parent-"+value+" .alert-danger").show();
            } else {
                $(".parent-"+value).removeClass("show");
                $(".parent-"+value+" .alert-danger").hide();
            }
       } else {
            if($("."+value+":checked").length==0) {
                isValid = false;
              // console.log(value);
                $(".parent-"+value).addClass("show");

                $(".parent-"+value+" .alert-danger").show();
            } else {
               // $(".parent-"+value).removeClass("show");
                $(".parent-"+value+" .alert-danger").hide();
            }
        }
    });

    if (location === '' && radius === '') {
        isValid = true;
        $("#locationmessage").hide();
        $("#radiusmessage").hide();
    } else if (location === '' && radius !== '') {
        isValid = false;
        $("#locationmessage").show();
        $("#radiusmessage").hide();
    } else if (location !== '' && radius === '') {
        isValid = false;
        $("#locationmessage").hide();
        $("#radiusmessage").show();
    } else {
        $("#locationmessage").hide();
        $("#radiusmessage").hide();
    }

    <?php if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
            if(!empty($selectedMapInfo) && H::getHelper('ConstantHelper')->isTextBlastMap($selectedMapInfo[0]->map_tablename)) {?>
                if(!radiusByPass && radius!='' && (radius > radiusLimit)) {
                    isValid = false;
                    $('#radiusLimitDialog').modal('show');
                }


        <?php  } } ?>


    if(isValid==false) {
        return false;
    } else {
        return true;
    }
}
var radiusByPass = 0;
var radiusLimit = <?php echo $radiusLimit?>;
$(document).ready(function(){
    $("#radius_search_confirmed").click(function(){
        radiusByPass = 1;
        performSearch();
        $('#radiusLimitDialog').modal('hide');

    });

});
function performSearch(callback) {
    if(!validateFilters()){
        return false;
    }

    var form = $("#filter_search");
    var frmSearch = $("#map_search");

    if (frmSearch.length) {
        $("#search_text_hidden").val($("#search_text").val());
    }
    $('#badaddressmsg').addClass('hide');
    $('#badaddressmsg').removeClass('show');
    $('#msgspan').html('');
    $.ajax({
        url: "{{route('map.getmainlocations')}}",
        method: "GET",
        data: form.serialize(),

        success: function(data) {
            $("#isbtnclicked").val(2);
            //console.log(data.success);
            if (typeof(data.success) != "undefined" && data.success !== null) {
                if (data.success == 0) {
                    $('#badaddressmsg').removeClass('hide');
                    $('#badaddressmsg').addClass('show');
                    $('#msgspan').html(data.message);
                    return false;
                }
            }
            isSearched = 1;

            //console.log("test");
            if (typeof callback === "function") {
                callback();

                if (currentControllerName.indexOf('showMap') == 0) {
                    data = JSON.stringify(data);
                    if (JSON.parse(data).features.length > 0) {
                        $("#tempLocation").val('-1')
                    } else {
                        $("#tempLocation").val('41.031279, -97.016969');
                    }
                    map.removeLayer(clusterGroup);
                    core.map.clearMapArea();
                    core.map.processMapLocations(data, !0);
                }


            } else if (currentControllerName.indexOf('showMap') == 0) {

                data = JSON.stringify(data);
                if (JSON.parse(data).features.length > 0) {
                    $("#tempLocation").val('-1')
                } else {
                    $("#tempLocation").val('41.031279, -97.016969');
                }
                map.removeLayer(clusterGroup);
                core.map.clearMapArea();
                core.map.processMapLocations(data, !0);
            } else if (currentControllerName.indexOf('showTable') == 0) {
                $('.data-table').DataTable().ajax.reload()
            } else {
                window.location.href = "{{route('map.map')}}"
            }


        },
    });
}

function getFullNumber(d) {
    return (d < 10 ? "0" : '') + d;
}

$(document).ready(function() {


    $("#importunselected").click(function() {
        alert("Please select a map to import data");
        return false;
    });

    $("#importunselectedconfig").click(function() {
        alert("Please select a map to configure data");
        return false;
    });

    let _token = $('meta[name="csrf-token"]').attr('content');
    $(document).on("click", ".group-options, #enable_grouping", function() {
        $("#group-checkbox-val-default").prop("checked", false);
        performSearch();
    });

    $(document).on("click", "#enable_grouping", function() {
        if ($(this).is(":checked")) {
            $(".grouping-fields-container").show();
            isGroupingEnabled = true;
            if (currentControllerName.indexOf('showMap') != 0) {
                window.location.href = "{{route('map.map')}}"
            }
        } else {
            $(".grouping-fields-container").hide();
            isGroupingEnabled = false;
        }

        if (currentControllerName.indexOf('showMap') != 0) {
            window.location.href = "{{route('map.map')}}"
        }

    });

    $(document).on("click", "#group-checkbox-val-default", function() {
        if ($(this).is(":checked")) {
            $('.group-options').each(function(i, obj) {
                $(this).prop("checked", false);
            });
            performSearch(function() {
                if (currentControllerName.indexOf('showMap') != 0) {
                    window.location.href = "{{route('map.map')}}"
                }
            });
        }
    });



    $("#grouping-field").change(function() {
        $.ajax({
            url: "{{route('map.getdistinct')}}",
            type: 'get',
            data: {
                groupfield: $("#grouping-field").val(),
                _token: _token
            },
            dataType: 'json',
            beforeSend: function() {
                //$("#location").css("background","#FFF url({{ asset('images/LoaderIcon.gif') }}) no-repeat 230px");
            },
            success: function(response) {
                $('#grouping-checkbox').html("");
                $('#grouping-checkbox').append(
                    $(document.createElement('input')).prop({
                        id: 'group-checkbox-val-default',
                        class: 'group-options-main',
                        name: 'colors[' + $("#grouping-field").val() + '][-1]',
                        value: "-1",
                        type: 'checkbox',
                        checked: true
                    })
                ).append(
                    $(document.createElement('label')).prop({
                        for: 'group-checkbox-val-default',
                        class: "group-label-main"
                    }).html("Show All Groups")
                ).append(document.createElement('br'));
                $.each(response, function(i, item) {
                    var pinno = i % 9;
                    var img = 'marker-icon-' + pinno + '.png';
                    $('#grouping-checkbox').append(
                        $(document.createElement('input')).prop({
                            id: 'group-checkbox-val-' + item,
                            class: 'group-options',
                            name: 'colors[' + $("#grouping-field").val() +
                                '][' + i + ']',
                            value: item,
                            type: 'checkbox'
                        })
                    ).append(
                        $(document.createElement('img')).prop({
                            src: "<?php echo H::getResourceMarkerPath()?>/" +
                                img,
                            class: "group-img"
                        })
                    ).append(
                        $(document.createElement('label')).prop({
                            for: 'group-checkbox-val-' + item,
                            class: "group-label"
                        }).html(item)
                    ).append(document.createElement('br'));

                });
                performSearch(function() {
                    if (currentControllerName.indexOf('showMap') != 0) {
                        window.location.href = "{{route('map.map')}}"
                    }
                });
            }
        });
    });


    $(".dynamic_filter_elements").click(function() {

        if (isSearched > 0) {

            performSearch();

        }
    });


    $("#location").keyup(function() {
        return false;
        var key = event.keyCode || event.charCode;
        //press backspace
        if (key == 8 || key == 46) {
            $("#locationString").val('');
            $("#formatedString").val('');
            $("#searchResult").empty();
        }

        var search = $.trim($(this).val());
        let _token = $('meta[name="csrf-token"]').attr('content');
        if (search != "" && search.length > 2) {

            $.ajax({
                url: "{{route('searchlocationdata')}}",
                type: 'post',
                data: {
                    search: search,
                    _token: _token
                },
                dataType: 'json',
                beforeSend: function() {
                    //$("#location").css("background","#FFF url({{ asset('images/LoaderIcon.gif') }}) no-repeat 230px");
                },
                success: function(response) {

                    $("#location").css("background", "#FFF");
                    var len = response.length;
                    $("#searchResult").empty();
                    for (var i = 0; i < len; i++) {
                        var id = response[i]['use_val'];
                        var name = response[i]['display_address'];

                        $("#searchResult").append("<li data-value='" + id + "'>" + name +
                            "</li>");

                    }

                    // binding click event to li
                    $("#searchResult li").bind("click", function() {
                        setText(this);
                    });

                }
            });
        } else {
            $("#locationString").val('');
            $("#formatedString").val('');
            $("#searchResult").empty();
        }

    });

});

// Set Text to search box and get details
function setText(element) {

    var value = $(element).text();
    var userid = $(element).attr('data-value');

    $("#locationString").val(userid);
    $("#location").val(value);
    $("#formatedString").val(value);
    $("#searchResult").empty();


}



$('#search').click(function() {
    $("#isbtnclicked").val(1);
    performSearch(function() {
        if (clickBtnPref == 1) {
            if (currentControllerName.indexOf('showTable') == 0) {
                $('.data-table').DataTable().ajax.reload()
            } else {
                window.location.href = "{{route('map.table')}}"
            }
        } else {
            if (currentControllerName.indexOf('showMap') != 0) {
                window.location.href = "{{route('map.map')}}"
            }
        }


    });


});

$('#reset').click(function() {
    $.ajax({
        url: "{{route('map.resetfilters')}}",
        method: "GET",
        data: {
            locationString: $('#locationString').val(),
            radius: $('#radius').val(),
            formatedString: $('#formatedString').val()
        },
        success: function(data) {
            if (currentControllerName.indexOf('showMap') == 0 || currentControllerName.indexOf(
                    'showTable') == 0) {
                location.reload();
            } else {
                window.location.href = "{{route('map.map')}}";
            }

        },
    });
});

$('.remove-territory').click(function() {
    if (confirm("Are you sure you want to delete this territory?")) {
        $.ajax({
            url: "{{route('map.removeTerritory')}}",
            method: "GET",
            data: {
                id: $(this).attr('data-id')
            },
            success: function(data) {
                var data = JSON.parse(JSON.stringify(data));
                if (data.status) {
                    location.reload();
                }
            },
        });
    } else {
        return false;
    }
});
</script>
<?php if(LD::checkFeatureFlag('text-blast-map-sms-radius-limit')) {
        if(!empty($selectedMapInfo) && H::getHelper('ConstantHelper')->isTextBlastMap($selectedMapInfo[0]->map_tablename)) {?>
            <div class="modal fade" id="radiusLimitDialog" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Radius Limit</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Reminder: Your campaign radius exceeds <?php echo $radiusLimit?> miles. Do you wish to continue?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" id="radius_search_confirmed">Yes</button>
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                </div>
                </div>
            </div>
            </div>
        <?php } ?>
<?php  } ?>
