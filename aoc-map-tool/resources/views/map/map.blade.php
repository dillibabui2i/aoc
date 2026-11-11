<?php
use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Support\Facades\Session;

$loggedInUser = H::getLoggedInUser();
$data = H::getMapTableData();
$mapInfo = H::getSelectedMapInfo();
$mapDataPrefereence = ($mapInfo[0]->map_data_preference=="1")?1:0;
$defaultView = ($mapInfo[0]->map_preferences=="0")?0:1;

$userType = config('constant.map_polygon_user_type');
$assignedRoles = H::getHelper("ConstantHelper")->getCurrentRolesNameByUserId($loggedInUser['id']);
$isUserInRole = 0;
if(in_array("Client Care Manager",$assignedRoles)){
    $isUserInRole = 1;
}

//var_dump($mapDataPrefereence);
?>
@extends('layouts.myaccount')

@section('title', $mapInfo[0]->map_name.' Map')
@section('content')
<link href="{{ mix('css/map.css') }}" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/0.2.3/leaflet.draw.css" rel="stylesheet">

<script>
var core = {};
var locationDataUrl = "{{route('map.getlocationdata')}}"
var multipleLocationsUrl = "{{route('map.getmultiplelocations')}}"
</script>
<script src="{{ mix('js/map.js') }}"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/0.2.3/leaflet.draw.js"></script>
<style>
.leaflet-popup-content .card {
    max-height: 450px;
    overflow-y: auto;
}

.leaflet-popup-content {
    min-width: 450px;
}

.leaflet-popup-content table {
    width: 100%;
}
</style>

@if ($errors->any())
<div class="col-sm-12">
    <div class="alert  alert-warning alert-dismissible fade show" role="alert">
        @foreach ($errors->all() as $error)
        <span>
            <p>{{ $error }}</p>
        </span>
        @endforeach
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>
@endif

@if (session('success'))
<div class="col-sm-12">
    <div class="alert  alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
</div>
@endif

<div class="heading-title-map">
    <h2>{{ $mapInfo[0]->map_name }}</h2>
    @if(!empty($mapInfo[0]->description))
        <p>{{ $mapInfo[0]->description }}</p>
    @endif

    <p class="text-muted mb-2">
        Last Uploaded: {{$uploaded_at}}
    </p>

    <a class="" title="Chart View" href="{{route('map.table')}}"><i class="fa fa-table"></i></a>
    <div class="menu-cnt">
        <ul class="list-unstyled components">
            @canAnyPermission(['map.import','map.configurations'])
            @if($mapInfo[0]->is_protected != 1)
            <li><a href="#setting-data" title="Configure Map" data-toggle="collapse" class="dropdown-toggle"><i
                        class="fa fa-cog"></i></a>
                <ul class="list-unstyled collapse" id="setting-data">
                    @if(Session::has('selectedmap'))
                    @canPermission('map.import')
                    <li>
                        <a href="{{route('map.import')}}"><i class="fa fa-upload"></i>Import Map Data</a>

                    </li>
                    @endcanPermission
                    @canPermission('map.configurations')
                    <li>
                        <a href="{{route('map.configurations')}}"><i class="fa fa-cogs"></i>Configure
                            Map</a>

                    </li>
                    @endcanPermission
                    @else
                    @canPermission('map.import')
                    <li>
                        <a href="javascript:void(0);" id="importunselected"><i class="fa fa-upload"></i>Import Map
                            Data</a>
                    </li>
                    @endcanPermission
                    @canPermission('map.configurations')
                    <li>
                        <a href="javascript:void(0);" id="importunselectedconfig"><i class="fa fa-cogs"></i>Configure
                            Map</a>
                    </li>
                    @endcanPermission
                    @endif
                </ul>
            </li>
            @endif
            @endcanAnyPermission
        </ul>
    </div>
    <a class="" title="Filters" id="mobile-filter-btn" href="javascript:void(0);"><i class="fa fa-filter"></i></a>
</div>
<div class="map-container">
    @canAnyPermission(['savepolygone'])
    <div class="poly-cnt">
        <div class="form-group">
            <input class="form-control form-control-color" type="color" name="polyColor" id="polyColor"
                title="Choose Polygon Color" />
        </div>
    </div>
    <div class="save-poly">
        <div class="form-group">
            <button title="Save Polygon to Database" class="form-control" name="savePolygonDB" id="savePolygonDB">
                <i class="fa fa-floppy-o"></i>
            </button>
        </div>
    </div>
    @endcanPermission
    <div class="search-container">
        <form name="map_search" id="map_search" action="{{route('map.getmainlocations')}}" method="get">
            <div class="search-icon ">
                <a href="javascript:void(0);" class="magnify"><i class="fa fa-search" aria-hidden="true"></i></a>
            </div>
            <div class="search-text-div" style="display:none" id="search-textfield-container">
                <input type="search" class="form-control" autocomplete="off" id="search_text" name="search_text"
                    value="" placeholder="Search here...">
            </div>
        </form>
    </div>

    <div id="map">

    </div>
    <input type="hidden" id="tempLocation" value="-1" aria-hidden="true">

    @canAnyPermission(['savepolygone'])
    <div class="modal fade" id="polyModal" tabindex="-1" role="dialog" aria-labelledby="polyModal" aria-hidden="true"
        data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="polyModalTitle">Add a name to the new territory</h5>
                </div>
                <div class="modal-body" id="polyModalForm">
                    <form action="">
                        <div class="form-group">
                            <input
                                class="form-control"
                                required
                                type="text"
                                list="coverage_coordinators"
                                name="polyName"
                                id="polyName"
                                placeholder="Enter Territory Name" />

                            <datalist id="coverage_coordinators">
                                @foreach($coverage_coordinators as $coverage_coordinator)
                                    <option value="{{ $coverage_coordinator->name }}"></option>
                                @endforeach
                            </datalist>
                        </div>

                        @if (LD::checkFeatureFlag('map-polygon-usertype-client-care-coordinator'))
                                <div class="form-group">
                                    <select class="form-control" id="userType" name="userType">
                                        <option value="">Select Territory User</option>

                                        @foreach($userType as $key => $value)
                                            <option
                                                    value="{{ $key }}"
                                                    @if($isUserInRole && $key === 'client_care_coordinator')
                                                        selected="selected"
                                                    @endif
                                            >
                                                {{ $value }}
                                            </option>
                                        @endforeach

                                    </select>
                                </div>
                        @endif

                        <small class="text-danger polyNameErr" style="display:none;">Territory name is required</small>
                        <small class="text-danger userTypeErr" style="display:none;">Territory User is required</small>
                    </form>
                </div>


                <div class="modal-footer">
                    <button type="button" id="savePolyName" class="btn btn-primary">Save</button>
                    <button type="button" id="cancelPolyName" class="btn btn-primary">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    @endcanPermission


</div>

<?php
$res = json_encode(['id'=>'Aochippa.map.data','type'=>null,'features'=>[]]);
?>


<script>
var dataPreference = "<?php echo $mapDataPrefereence?>";
$(document).ready(function() {
    $(document).on("click", ".magnify", function() {
        $('.search-text-div').toggle("slide", {
            direction: "left"
        }, 200);

    });

    if (!navigator.geolocation) {
        $('#geo-button').remove();
    }
    $('#geo-button').on('click', function() {
        getLocation();
    });
    $('.geolocation-close').click(function() {
        $('.geolocation-warning-message').fadeOut();
    });
});

function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(showPosition, function(error) {
            if (!enablePosition) {
                $('.geolocation-warning-message').fadeIn();
                console.log('Error: ' + error);
                setTimeout(function() {
                    $('.geolocation-warning-message').fadeOut();
                }, 60000);
            }
        }, {
            maximumAge: 60 * 60 * 1000,
            timeout: 3500
        });
    } else {
        alert('Geolocation is not supported by this browser');
    }
};

function showPosition(position) {
    enablePosition = true;
    $('.geolocation-warning-message').hide();
    var zoom = 10;
    var lat = position.coords.latitude;
    var lng = position.coords.longitude;
    var homeIcon = L.icon({
        iconUrl: '/resources/images/find-me-home.png',
        iconSize: [40, 'initial']
    });
    L.Icon.Default.imagePath = '/resources/images';
    var marker = L.marker([lat, lng], {
        icon: homeIcon
    }).addTo(map);
    map.setView([lat, lng], zoom);
}
var clickMarker;
var oiseLayer;
var dataObject;
var wdata;
var locationManager;
var oiseLayer;
var isSet;
var map;
var mi;
var clusterGroup;
$(document).ready(function() {
    $('.ajax-spinner').show();

    map = core.map.initLocationsMap();
    L.control.zoom({
        position: 'bottomright'
    }).addTo(map);
    var layer = core.map.initLocationsMapLayer('https://c.tile.openstreetmap.org/{z}/{x}/{y}.png');
    map.addLayer(layer);
    //core.map.clearMapArea();

    //console.log(isSearched);
    if (isSearched != "0" || dataPreference == "0") {
        $.getJSON('getmainlocations', function(data) {
            data = JSON.stringify(data);
            if (JSON.parse(data).features.length > 0) {
                $("#tempLocation").val('-1')
            } else {
                $("#tempLocation").val('41.031279, -97.016969');
            }
            $('.leaflet-draw-draw-polygon').addClass('disable');
            $('.leaflet-draw-draw-polygon').parent().attr('title',
                'Please choose color before you draw the polygon.');
            core.map.processMapLocations(data, true);
        });
    } else {
        $("#tempLocation").val('41.031279, -97.016969');
        core.map.processMapLocations(JSON.parse(JSON.stringify('<?php echo $res;?>')), true);
    }


    // Polygon
    var editableLayers = false;
    var drawControl = false;
    var editableLayers = false;
    var layers = [];

    editableLayers = new L.FeatureGroup();
    map.addLayer(editableLayers);

    var drawPluginOptions = {
        position: 'topright',
        draw: {
            polygon: {
                allowIntersection: false,
                drawError: {
                    color: '#e1e100',
                    message: '<strong>you can\'t draw that!'
                },
                shapeOptions: {
                    color: polyColor
                }
            },

            polyline: false,
            circle: false,
            rectangle: false,
            marker: false,
        },
        edit: {
            featureGroup: editableLayers,
            poly: {
                allowIntersection: false
            }
        }
    };

    // Initialise the draw control and pass it the FeatureGroup of editable layers
    drawControl = new L.Control.Draw(drawPluginOptions);
    map.addControl(drawControl);

    map.on('draw:created', function(e) {
        var layer = e.layer;
        if ($('#polyName').val() != 'undefined') {
            layer.polyName = $('#polyName').val();
        }
        editableLayers.addLayer(layer);
        console.log(layer);
        layers[layer.polyName] = layer;

        $('.leaflet-draw-draw-polygon').addClass('disable');
        $('.save-poly').show();

    });
    map.on('draw:edited', function(e) {
        var editedLayers = e.layers;
        var countOfEditedLayers = 0;
        editedLayers.eachLayer(function(layer) {
            if (layer.polyName != '') {
                layers[layer.polyName] = layer;
            }
        });
    });
    map.on('draw:deleted', function(e) {
        $('.leaflet-draw-draw-polygon').removeClass('disable');
        $('.save-poly').hide();
    });

    var polyColor = '#97009c';
    $('#polyColor').change(function(e) {
        drawControl.setDrawingOptions({
            polygon: {
                shapeOptions: {
                    color: $(this).val()
                }
            }
        });
        $("#polyModal").find('#polyName').val('');
        $("#polyModal").find('input[type="hidden"]').remove();
        $("#polyModal").modal('show');
        $('.leaflet-draw-draw-polygon').removeClass('disable');
    });

    $(document).on('click', '#cancelPolyName', function(e) {
        $("#polyModal").modal('hide');
        $("#polyModalTitle").html("Add a name to the new territory");
        $('.leaflet-draw-draw-polygon').addClass('disable');
    });

    $(document).on('click', '.edit-territory', function(e) {
        $("#polyModal").modal('show');
        $("#polyModal").find('#polyName').val($(this).attr('data-name'));
        $("#polyModalTitle").html("Edit Territory Name");
        $('<input>').attr({
            type: 'hidden',
            id: 'polyId',
            name: 'polyId',
            value: $(this).attr('data-id')
        }).appendTo('#polyModalForm');
        $('.leaflet-draw-draw-polygon').addClass('disable');
    });

    function editTerritoryName() {
        var userType = $("#userType").val();

        if (userType == undefined || userType == '') {
            userType = 'client_care_coordinator';
        }


        let _token = $('meta[name="csrf-token"]').attr('content');
        if ($("#polyId").val() != 'undefined' && $("#polyId").val() != '' && $("#polyName").val() != '') {
            $.ajax({
                url: "{{route('editTerritoryName')}}",
                type: 'post',
                data: {
                    name: $("#polyName").val(),
                    id: $("#polyId").val(),
                    user_type: userType,
                    _token: _token
                },
                dataType: 'json',
                success: function(response) {
                    location.reload();
                }
            });
        } else {
            $(".polyNameErr").html('Someting goes wrong').show();
        }
    }

        //Save polygon name for the layer
        $(document).on('click', '#savePolyName', function(e) {
            let _token = $('meta[name="csrf-token"]').attr('content');
            if ($("#polyName").val() != '' && ($('#userType').length === 0 ||  $('#userType').val() != '')) {
                $.ajax({
                    url: "{{route('checkpolygonname')}}",
                    type: 'post',
                    data: {
                        name: $("#polyName").val(),
                        id: $("#polyId").val(),
                        _token: _token
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.status) {
                            if (typeof $("#polyId").val() !== 'undefined' && $("#polyId")
                            .val() != '') {
                                editTerritoryName();
                            } else {
                                $(".polyNameErr").hide();
                                $(".userTypeErr").hide();
                                $("#polyModal").modal('hide');
                            }
                        } else {
                            $(".polyNameErr").html('This territory name already exists').show();
                        }
                    }
                });

            } else {
                if ($("#polyName").val() === '') {
                    $(".polyNameErr").show();
                }
                if ($('#userType').val() === '') {
                    $(".userTypeErr").show();
                }
            }
        });
    <?php  if (LD::checkFeatureFlag('map-polygon-usertype-client-care-coordinator')) { ?>
                //Save the polygon to DB
                $(document).on('click', '#savePolygonDB', function(e) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                if ($("#polyName").val() != '' && layers[$("#polyName").val()] != 'undefined') {
                    $.ajax({
                        url: "{{route('savepolygone')}}",
                        type: 'post',
                        data: {
                            name: $("#polyName").val(),
                            color: $('#polyColor').val(),
                            user_type: $('#userType').val(),
                            coordinates: JSON.stringify(layers[$("#polyName").val()].getLatLngs()),
                            _token: _token
                        },
                        dataType: 'json',
                        success: function(response) {
                            location.reload();
                        }
                    });
                } else {
                    alert("Draw your territory before you save");
                }
            });
        <?php } else { ?>
            //Save the polygon to DB
            $(document).on('click', '#savePolygonDB', function(e) {
                let _token = $('meta[name="csrf-token"]').attr('content');
                if ($("#polyName").val() != '' && layers[$("#polyName").val()] != 'undefined') {
                    $.ajax({
                        url: "{{route('savepolygone')}}",
                        type: 'post',
                        data: {
                            name: $("#polyName").val(),
                            color: $('#polyColor').val(),
                            coordinates: JSON.stringify(layers[$("#polyName").val()].getLatLngs()),
                            _token: _token
                        },
                        dataType: 'json',
                        success: function(response) {
                            location.reload();
                        }
                    });
                } else {
                    alert("Draw your territory before you save");
                }
            });
    <?php } ?>

    function getCoordinates(str) {
        var arr = JSON.parse(str);
        var res = [];
        for (var i = 0; i < arr.length; i++) {
            res.push([arr[i].lat, arr[i].lng]);
        }
        return res;
    }


    $(document).on('click', '#enable_territories', function(e) {
        if ($(this).is(":checked")) {
            $("#territories-checkbox").show();
        } else {
            $("#territories-checkbox").hide();
        }
    });

    var mapPolygon = {};
    $(document).on('click', '#showAllTerritories', function(e) {
        $('.polygonCheckbox').trigger('click');
    });
    $(document).on('click', '.polygonCheckbox', function(e) {
        if ($(this).is(":checked")) {
            mapPolygon[$(this).attr('name')] = {
                "coordinates": getCoordinates($(this).attr('data-coordinates')),
                "color": $(this).attr('data-color')
            };
        } else {
            delete mapPolygon[$(this).attr('name')]
        }

        addPolygon(mapPolygon);
    });
    var lastPolygonFeaturedLayer = false;

    function addPolygon(polygons) {

        if (lastPolygonFeaturedLayer !== false) {
            map.removeLayer(lastPolygonFeaturedLayer);
        }
        var polyFeaturedGroup = new L.FeatureGroup();
        console.log(polygons);
        $.each(polygons, function(key, value) {
            console.log(value);
            L.polygon(value.coordinates).setStyle({
                "fillColor": value.color,
                "color": value.color
            }).addTo(polyFeaturedGroup);
        });
        map.addLayer(polyFeaturedGroup);
        lastPolygonFeaturedLayer = polyFeaturedGroup;
    }

    $(document).on("input", "#search_text", function(e) {
        if ($(this).val() == '') {
            performSearch();
        }

    });

    $(document).on("keypress", "#search_text", function(e) {
        if (e.keyCode == 13) {
            e.preventDefault();
            performSearch();
        }
    });
});



function getMapsKey() {
    return 'AmYiuXlVajrhKD5Uk0F9VNg0U0toRXEiSHoD3ESc_pavcVvtOrQJVZY-1lrwgpom';
};
</script>


<script>
var currentShowing = 0;

$(document).on("click", "#next", function() {
    if (currentShowing < $(".map-detail.item").length - 1) {
        $(".map-detail.item").removeClass("active");
        $(".map-detail.item").eq(currentShowing + 1).addClass("active");
        currentShowing++;
        $("#map-cntr").html(currentShowing + 1);
    }

});

$(document).on("click", "#prev", function() {
    if (currentShowing > 0) {
        $(".map-detail.item").removeClass("active");
        $(".map-detail.item").eq(currentShowing - 1).addClass("active");

        $("#map-cntr").html(currentShowing);
        currentShowing--;

    }
});
</script>

<style>
.map-detail.item {
    display: none;
}

.map-detail.item.active {
    display: block;
}
.custom-svg-marker {
    background: transparent !important;
    border: none !important;
}
.custom-cluster-circle div {
    width: 36px;
    height: 36px;
    line-height: 36px;
    text-align: center;
    border-radius: 50%;
    color: #ffffff;
    font-weight: bold;
    font-size: 14px;
    border: 3px solid rgba(0, 0, 0, 0.5);
    box-shadow: 0 0 5px rgba(0,0,0,0.3);
    margin-left: 2px;
    margin-top: 2px;
}

.custom-cluster-wrapper {
    background: transparent !important;
    border: none !important;
}

.custom-shape-cluster {
    position: relative;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.custom-shape-cluster svg {
    position: absolute;
    top: 0;
    left: 0;
    z-index: 1;
}

.custom-shape-cluster .cluster-count {
    position: relative;
    z-index: 2;
    color: #ffffff;
    font-weight: bold;
    font-size: 14px;
}

.custom-shape-cluster.shape-teardrop .cluster-count {
    margin-top: -4px;
}
</style>

@stop
