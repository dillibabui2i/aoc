<?php
use \App\Helpers\H;
use App\Helpers\LD;
$loggedInUser = H::getLoggedInUser();
$mapInfo = H::getSelectedMapInfo(true);
$enabledAddressFilter = $mapInfo[0]->enable_address_filter;
$mapSearchRadius = $mapInfo[0]->map_search_radius;
$fieldsOnMap = json_decode($mapInfo[0]->fields_on_map);
$latlongFields = json_decode($mapInfo[0]->latlong_fields);
$latlongFields2 = null;
$dataTableSections = json_decode($mapInfo[0]->fields_datatable);
$map_filters = json_decode($mapInfo[0]->map_filters);
$map_default_filter = json_decode($mapInfo[0]->map_default_filter,true);
$selected_unique_identifier = json_decode($mapInfo[0]->unique_identifier_col, true) ?? [];

$filterTypes = H::getFilterTypes();
$viewPreferences = H::viewPreferences();

$selectedPreference = $mapInfo[0]->map_preferences;

$map_select_preference = isset($mapInfo[0]->map_select_preference)?$mapInfo[0]->map_select_preference:'';

$selectedDataPreference = $mapInfo[0]->map_data_preference;
$map_boolean_field = json_decode($mapInfo[0]->map_boolean_field,true);
$mapDataPreferences = ["0"=>"No","1"=>"Yes"];
$bool = ["false","true"];

$fields = H::getTableFieldsForSelectedMap();

$layers = $mapInfo[0]->layers ?? [];

?>
@extends('layouts.myaccount')

@section('title', 'Configure '.$mapInfo[0]->map_name.' Data')

@section('content')

<script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/semantic.min.js"
    integrity="sha512-dqw6X88iGgZlTsONxZK9ePmJEFrmHwpuMrsUChjAw1mRUhUITE5QU9pkcSox+ynfLhL15Sv2al5A0LVyDCmtUw=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/components/transition.min.js"
    integrity="sha512-MCuLP92THkMwq8xkT2cQg5YpF30l3qzJuBRf/KsbQP1czFkRYkr2dSkCHmdJETqVmvIq5Y4AOVE//Su+cH+8QA=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/components/transition.min.css"
    integrity="sha512-5StPzJo8hFyTvXfJ31FMB37EXRMVeUg+J3yvUNOJcL83MEMr7VrhZSNsoL3GDmUDBGBBhoTjnJx0Ql7cH9LY7g=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/components/dropdown.min.css"
    integrity="sha512-YYS7fyqDxVE/yJ1280i8KjA+nC7wAtv2u/qkulKbdMpmp8DBWX0Wj+HtILsFyvq+fouCwCyr0hasPAz1eBlvwA=="
    crossorigin="anonymous" referrerpolicy="no-referrer" />
<script src="https://cdnjs.cloudflare.com/ajax/libs/semantic-ui/2.4.1/components/dropdown.min.js"
    integrity="sha512-8F/2JIwyPohlMVdqCmXt6A6YQ9X7MK1jHlwBJv2YeZndPs021083S2Z/mu7WZ5g0iTlGDYqelA9gQXGh1F0tUw=="
    crossorigin="anonymous" referrerpolicy="no-referrer"></script>
<div class="">
    <div class="form-group col-md-7 head-title">
        <h1 class="h3 mb-2 text-gray-800">Configure {{ $mapInfo[0]->map_name }} Data</h1>
        <a href="{{ route('managemap') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
    </div>
</div>
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

<div class="form-container">
    <form action="{{ route('map.configuredata') }}" enctype='multipart/form-data' id="configuration-form" method="POST"
        class="needs-validation" novalidate>
        @csrf
        <input type="hidden" name="map_id" value="{{ $mapInfo[0]->map_id }}">

        <div class="form-group">
            <label for="name" class="">Map Name</label>
            <input type="text" name="map_name" id="map_name" value="{{ $mapInfo[0]->map_name }}"
                class="form-control login">
        </div>

        <div class="form-group">
            <label for="name" class="">Map Category</label>
            <select name="map_category" class="form-control login">
                <option value=""></option>
                <option value="Operations" <?php if($mapInfo[0]->map_category=='Operations') { ?> selected="selected"
                    <?php } ?>>Operations</option>
                <option value="Recruiting" <?php if($mapInfo[0]->map_category=='Recruiting') { ?> selected="selected"
                    <?php } ?>>Recruiting</option>
                <option value="Client Care Coordinators"
                    <?php if($mapInfo[0]->map_category=='Client Care Coordinators') { ?> selected="selected" <?php } ?>>
                    Client Care Coordinators</option>
                <option value="Therapy" <?php if($mapInfo[0]->map_category=='Therapy') { ?> selected="selected"
                    <?php } ?>>Therapy</option>
            </select>
        </div>

        <div class="form-group">
            <label for="name" class="">Map Type</label>
            <select name="map_type" class="form-control login">
                <option value=""></option>
                <option value="PHI" <?php if($mapInfo[0]->map_type=="PHI") { ?> selected="selected" <?php } ?>>PHI
                </option>
                <option value="non-PHI" <?php if($mapInfo[0]->map_type=="non-PHI") { ?> selected="selected" <?php } ?>>
                    non-PHI</option>
            </select>

        </div>

        <h3 class="scheduler-border">Layers Configuration</h3>
        <fieldset class="scheduler-border" style="text-align: left;">
            <legend class="scheduler-border">Layers</legend>
            <p style="font-size:12px;color:#6b7280;margin-bottom:14px;">Each layer is a separate dataset plotted on the same map. Users can toggle layers on or off in the map view.</p>

            <div id="layers-container">
                @foreach($layers as $layer)
                        <?php
                        $layerId = $layer->map_id;
                        $idx = $loop->iteration;

                        $layerFields = Schema::hasTable($layer->map_tablename) ? H::getTableFieldsForSelectedMap($layer->map_tablename) : [];
                        $savedLatLong = json_decode($layer->latlong_fields, true) ?? [];
                        $fieldsOnMapLayer = json_decode($layer->fields_on_map, true) ?? [];
                        $savedPopups = [];
                        if (!empty($fieldsOnMapLayer['fields']) && is_array($fieldsOnMapLayer['fields'])) {
                            foreach ($fieldsOnMapLayer['fields'] as $fItem) {
                                if (isset($fItem['name'])) {
                                    $savedPopups[] = $fItem['name'];
                                }
                            }
                        }

                        $recordCount = Schema::hasTable($layer->map_tablename) ? DB::table($layer->map_tablename)->count() : 0;
                        ?>
                    <div class="layer-config-card" id="layer-card-{{$idx}}">
                        <input type="hidden" name="layers[{{$idx}}][map_id]" value="{{$layerId}}">

                        <div class="layer-config-header" onclick="toggleLayerConfig('{{$idx}}')">
                            <div class="layer-color-swatch" style="background:{{$layer->pin_color}};"></div>
                            <span class="layer-config-name">{{$layer->map_name}}</span>
                            @if($recordCount > 0)
                                <span class="layer-status-badge badge-uploaded">{{$recordCount}} records uploaded</span>
                            @else
                                <span class="layer-status-badge badge-nodata">No data uploaded</span>
                            @endif
                            <span class="layer-chevron" id="chevron-{{$idx}}">▼</span>
                        </div>

                        <div class="layer-config-body" id="body-{{$idx}}">
                            <div class="layer-inner-grid">
                                <div>
                                    <div class="field-label">Layer name</div>
                                    <input type="text" class="form-input" name="layers[{{$idx}}][map_name]" value="{{$layer->map_name}}" required>
                                </div>
                                <div>
                                    <div class="field-label">Pin Color</div>
                                    <div class="color-pick-row">
                                        <input type="color" name="layers[{{$idx}}][pin_color]" value="{{$layer->pin_color}}" class="layer-color-picker">
                                    </div>
                                </div>
                                <div>
                                    <div class="field-label">Pin style</div>
                                    <div class="pin-shape-row">
                                        <input type="hidden" name="layers[{{$idx}}][pin_shape]" class="pin-shape-input" value="{{$layer->pin_icon ?: 'circle'}}">
                                        <div class="pin-shape-opt {{$layer->pin_icon == 'circle' || !$layer->pin_icon ? 'selected' : ''}}" data-value="circle" title="Circle">
                                            <svg width="16" height="16" viewBox="0 0 16 16"><circle cx="8" cy="8" r="6" fill="{{$layer->pin_color}}"/></svg>
                                        </div>
                                        <div class="pin-shape-opt {{$layer->pin_icon == 'diamond' ? 'selected' : ''}}" data-value="diamond" title="Diamond">
                                            <svg width="16" height="16" viewBox="0 0 16 16"><rect x="3" y="3" width="10" height="10" transform="rotate(45 8 8)" fill="{{$layer->pin_color}}"/></svg>
                                        </div>
                                        <div class="pin-shape-opt {{$layer->pin_icon == 'teardrop' ? 'selected' : ''}}" data-value="teardrop" title="Teardrop">
                                            <svg width="16" height="16" viewBox="0 0 16 16"><path d="M8 15 C8 15 2 10 2 6 C2 3.2 4.7 1 8 1 C11.3 1 14 3.2 14 6 C14 10 8 15 8 15Z" fill="{{$layer->pin_color}}"/></svg>
                                        </div>
                                        <div class="pin-shape-opt {{$layer->pin_icon == 'square' ? 'selected' : ''}}" data-value="square" title="Square">
                                            <svg width="16" height="16" viewBox="0 0 16 16"><rect x="3" y="3" width="10" height="10" rx="2" fill="{{$layer->pin_color}}"/></svg>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="field-label">Data source (CSV)</div>
                            <div class="upload-mode-row">
                                <label class="upload-mode-opt">
                                    <input type="radio" name="layers[{{$idx}}][isAppend]" value="off" checked> Replace existing data
                                </label>
                                <label class="upload-mode-opt">
                                    <input type="radio" name="layers[{{$idx}}][isAppend]" value="on"> Append to existing data
                                </label>
                            </div>
                            <input type="file" name="layers[{{$idx}}][file]" accept=".csv" class="form-control mb-3 layer-file-input" data-layer-idx="{{$idx}}">

                            <div class="field-label">Address field mapping</div>
                            <div class="address-grid">
                                @foreach(['address' => 'address1', 'city' => 'city', 'state' => 'state', 'zip' => 'zipcode'] as $label => $dbKey)
                                    <div>
                                        <div style="font-size:11px;color:#9ca3af;margin-bottom:3px;">{{ ucfirst($label) }}</div>
                                        <select name="layers[{{$idx}}][latlong_fields][{{$dbKey}}]" class="form-select columnsSelect-{{$idx}}">
                                            <option value="">-- Select --</option>
                                            @foreach($layerFields as $f)
                                                <option value="{{$f->Field}}" {{ ($savedLatLong[$dbKey] ?? '') == $f->Field ? 'selected' : '' }}>{{$f->Field}}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endforeach
                            </div>

                            <div class="popup-filter-row mt-3">
                                <div>
                                    <div class="field-label">Map popup fields</div>
                                    <select class="form-select columnsSelect-{{$idx}}" name="layers[{{$idx}}][card_fields][]" multiple size="5" required>
                                        @foreach($layerFields as $f)
                                            <option value="{{$f->Field}}" {{ in_array($f->Field, $savedPopups) ? 'selected' : '' }}>{{$f->Field}}</option>
                                        @endforeach
                                    </select>
                                    <p class="info-hint">Hold Ctrl to select multiple</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" class="add-layer-btn mt-2" onclick="addNewLayer()">
                <div class="plus-circle">+</div>Add another layer
            </button>
        </fieldset>

        <h3 class="scheduler-border">Coverage Radius Settings</h3>
        <fieldset class="scheduler-border" style="text-align: left;">
            <p style="font-size:12px;color:#6b7280;margin-bottom:14px;">Select which layer displays coverage rings, and at what radius.</p>

            <div class="form-row">
                <span class="form-label">Ring layer</span>
                <select name="ring_layer_id" class="form-control" style="max-width:400px;">
                    <option value="">-- None --</option>
                    @foreach($layers as $l)
                        <option value="{{$l->map_id}}" {{ $mapInfo[0]->ring_layer_id == $l->map_id ? 'selected' : '' }}>{{$l->map_name}}</option>
                    @endforeach
                </select>
            </div>

            <div class="form-row mt-3">
                <span class="form-label">Coverage radius (miles)</span>
                <input type="number" name="layer_ring_radius" class="form-control" style="max-width:200px;" value="{{ $mapInfo[0]->layer_ring_radius }}">
            </div>

            <div class="form-row mt-3">
                <span class="form-label">Show rings by default</span>
                <input type="checkbox" name="show_rings_by_default" value="1" {{ $mapInfo[0]->show_rings_by_default ? 'checked' : '' }} style="width:16px;height:16px;">
            </div>
        </fieldset>

        <h3 class="scheduler-border">Map View</h3>

        <fieldset class="scheduler-border">
            <legend class="scheduler-border">Popup Title</legend>

            <div class="form-container-card_heading col-md-12">
                <?php
                $cardHeadingCount = 0;
                if(!empty($fieldsOnMap->card_heading)) {
                    foreach($fieldsOnMap->card_heading as $cardHeading) {
                        $id = "card_heading";

                        if($cardHeadingCount>0){

                            $id = "card_heading".$cardHeadingCount;
                        }
                        $cardHeadingCount++;
                    ?>
                    <div class="form-group col-md-6 pull-left">
                        <select name="card_heading[]" id="{{ $id }}" class="ui fluid dropdown">
                            <option value="">Fields for Map Popup Heading</option>
                            <?php foreach($fields as $key => $field){ ?>
                            <option value="{{ $field->Field }}"
                                <?php echo $field->Field==$cardHeading?"selected='selected'":''?>>{{ $field->Field }}
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                <?php
					}
                } else { $cardHeadingCount++; ?>
                    <div class="form-group col-md-6 pull-left">
                        <select name="card_heading[]" id="card_heading" class="ui fluid dropdown">
                            <option value="">Fields for Map Popup Heading</option>
                            <?php foreach($fields as $key => $field){ ?>
                            <option value="{{ $field->Field }}">{{ $field->Field }}</option>
                            <?php } ?>
                        </select>
                    </div>
            <?php } ?>
            </div>

            <div class="button-container col-md-12" style="clear:both">
                <div class=""><input type="button" class="btn btn-info login-btn btn-md" id="addMoreCardHeading"
                        value="Add More"></div>
            </div>


        </fieldset>

        <fieldset class="scheduler-border">
            <legend class="scheduler-border">Address</legend>
            <div class="form-container-card_subheading  col-md-12">

                <?php
						$cardSubHeadingCount = 0;
						if(!empty($fieldsOnMap->card_subheading)) {
							foreach($fieldsOnMap->card_subheading as $cardSubHeading) {
								$id = "card_subheading";

								if($cardSubHeadingCount>0){

									$id = "card_subheading".$cardSubHeadingCount;
								}
							$cardSubHeadingCount++;
							?>
                <div class="form-group col-md-3 pull-left">
                    <select name="card_subheading[]" id="{{ $id }}" class="ui fluid dropdown">
                        <option value="">Select Fields for Map Popup Sub Heading (Address)</option>
                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}"
                            <?php echo $field->Field==$cardSubHeading?"selected='selected'":''?>>
                            {{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>
                <?php

						}

						} else { $cardSubHeadingCount++; ?>


                <div class="form-group col-md-3 pull-left">

                    <select name="card_subheading[]" id="card_subheading" class="ui fluid dropdown">
                        <option value="">Select Fields for Map Popup Sub Heading (Address)</option>
                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}">{{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>
                <?php }?>
            </div>

            <div class="button-container col-md-12" style="clear:both">
                <div class=""><input type="button" class="btn btn-info login-btn btn-md" id="addMoreCardSubheading"
                        value="Add More"></div>
            </div>


        </fieldset>
        <fieldset class="scheduler-border">
            <legend class="scheduler-border">Map View Pop-up Fields</legend>
            <div id="important_fields_container" class="form-container-cart-fields  col-md-12">
                <div class="unsortable">
                    <div class="col-md-6 heading pull-left unsortable" > Database Field  </div>
                    <div class="col-md-6 heading pull-left unsortable" >  Title  </div>
                </div>
                <?php
                    $importantFieldCount = 0;
                    if(!empty($fieldsOnMap->fields)) {
                        foreach($fieldsOnMap->fields as $card_fields) {
                            $id = "card_fields";

                            if($importantFieldCount>0){

                                $id = "card_fields".$importantFieldCount;
                            }
                            $importantFieldCount++;
                ?>
                            <div id="important_fields_<?php echo $importantFieldCount?>" class="card-field-container  pb-5" style="clear:both">
                                <div class="col-md-6  pull-left unsortable" >
                                    <select name="card_fields[fields][]" id="{{ $id }}" class="ui fluid dropdown">
                                        <option value="">Select Fields for Map Popup</option>
                                        <?php foreach($fields as $key => $field){ ?>
                                        <option value="{{ $field->Field }}"
                                            <?php echo $field->Field==$card_fields->name?"selected='selected'":''?>>
                                            {{ $field->Field }}</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-6  pull-left unsortable" >
                                    <input type="text" class="form-control" name="card_fields[title][]" value="{{ $card_fields->title }}" />
                                </div>
                            </div>
                    <?php }
                    }  $importantFieldCount++; ?>

                            <div id="important_fields" class="card-field-container  pb-5" style="clear:both">
                                <div class="col-md-6  pull-left unsortable" >
                                    <select name="card_fields[fields][]" id="card_fields" class="ui fluid dropdown">
                                        <option value="">Select Fields for Map Popup</option>
                                        <?php foreach($fields as $key => $field){ ?>
                                        <option value="{{ $field->Field }}">{{ $field->Field }}</option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="col-md-6  pull-left unsortable" >
                                    <input type="text" class="form-control" name="card_fields[title][]" value="" />
                                </div>
                            </div>

            </div>

            <div class="button-container col-md-12" style="clear:both">
                <div class=""><input type="button" class="btn btn-info login-btn btn-md" id="addMoreImportantFields"
                        value="Add More"></div>
            </div>
        </fieldset>

        <h3>Lat / Lng Configurations</h3>
        <fieldset class="scheduler-border">
            <legend class="scheduler-border">Address Fields</legend>
            <div class="form-container-latlong_fields  col-md-12">
                <?php $latlongFieldArr = json_decode( json_encode($latlongFields), true ); $latlongCount=1; ?>
                <div class="form-group  col-md-3 pull-left">
                    <select name="latlong_fields[address1]" id="latlong_fields1" class="ui fluid dropdown">
                        <option value="">Select Address1</option>
                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}"
                            <?php if(!empty($latlongFieldArr) && isset($latlongFieldArr['address1'])){ echo ($field->Field==$latlongFieldArr['address1'])?"selected='selected'":''; } ?>>
                            {{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group  col-md-3 pull-left">
                    <select name="latlong_fields[address2]" id="latlong_fields2" class="ui fluid dropdown">
                        <option value="">Select Address2</option>
                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}"
                            <?php if(!empty($latlongFieldArr) && isset($latlongFieldArr['address2'])){ echo ($field->Field==$latlongFieldArr['address2'])?"selected='selected'":''; } ?>>
                            {{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group  col-md-3 pull-left">
                    <select name="latlong_fields[address3]" id="latlong_fields3" class="ui fluid dropdown">
                        <option value="">Select Address3</option>
                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}"
                            <?php if(!empty($latlongFieldArr) && isset($latlongFieldArr['address3'])){ echo ($field->Field==$latlongFieldArr['address3'])?"selected='selected'":''; } ?>>
                            {{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group  col-md-3 pull-left">
                    <select name="latlong_fields[city]" id="latlong_fields4" class="ui fluid dropdown">
                        <option value="">Select City</option>
                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}"
                            <?php if(!empty($latlongFieldArr) && isset($latlongFieldArr['city'])){ echo ($field->Field==$latlongFieldArr['city'])?"selected='selected'":''; } ?>>
                            {{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group  col-md-3 pull-left">
                    <select name="latlong_fields[state]" id="latlong_fields5" class="ui fluid dropdown">
                        <option value="">Select State</option>
                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}"
                            <?php if(!empty($latlongFieldArr) && isset($latlongFieldArr['state'])){ echo ($field->Field==$latlongFieldArr['state'])?"selected='selected'":''; } ?>>
                            {{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-group  col-md-3 pull-left">
                    <select name="latlong_fields[zipcode]" id="latlong_fields5" class="ui fluid dropdown">
                        <option value="">Select Zipcode</option>
                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}"
                            <?php if(!empty($latlongFieldArr) && isset($latlongFieldArr['zipcode'])){ echo ($field->Field==$latlongFieldArr['zipcode'])?"selected='selected'":''; } ?>>
                            {{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>

                <div class="form-group  col-md-3 pull-left">
                    <select name="latlong_fields[country]" id="latlong_fields5" class="ui fluid dropdown">
                        <option value="">Select Country</option>
                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}"
                            <?php if(!empty($latlongFieldArr) && isset($latlongFieldArr['country'])){ echo ($field->Field==$latlongFieldArr['country'])?"selected='selected'":''; } ?>>
                            {{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>

            </div>
            <div class="button-container col-md-12" style="clear:both">
                <!--<div class=""><input type="button" class="btn btn-info login-btn btn-md" id="addMoreLatlongFields" value="Add More"></div>-->
            </div>
        </fieldset>


        <h3>Chart View</h3>
        <fieldset class="scheduler-border">
            <legend class="scheduler-border">Datatable columns</legend>
            <div  id="chart_view_container" class="form-container-table_heading col-md-12">
                <div class="">
                    <div class="col-md-6 heading pull-left unsortable" > Database Field  </div>
                    <div class="col-md-6 heading pull-left unsortable" >  Title  </div>
                </div>
                <?php
                    $tableHeadingCount = 0;
                    if(!empty($dataTableSections->datatable) && count($dataTableSections->datatable) > 1 ) {
                        for($i=1;$i<count($dataTableSections->datatable);$i++) {
                            $id = "table_heading";

                            if($tableHeadingCount>0){
                                $id = "table_heading".$tableHeadingCount;
                            }

                            ?>
                            <div id="chart_view_fields_<?php echo $tableHeadingCount?>" class="card-field-container  pb-5" style="clear:both">
                            <div class="col-md-6  pull-left unsortable" >
                            <select name="table_heading[field][]" id="{{ $id }}" class="ui fluid dropdown">
                                <option value="">Database Field</option>
                                <?php foreach($fields as $key => $field){ ?>
                                <option value="{{ $field->Field }}"
                                    <?php echo $field->Field==$dataTableSections->datatable[$i]?"selected='selected'":''?>>
                                    {{ $field->Field }}</option>
                                <?php } ?>
                                </select>
                            </div>
                            <div class="col-md-6  pull-left unsortable" >
                                <input type="text" class="form-control" name="table_heading[title][]" value="{{$dataTableSections->table_heading[$i]}}" />
                            </div>
                        </div>
                        <?php
						    $tableHeadingCount++;
						}  }
						 $tableHeadingCount++; ?>


                            <div id="chart_view_fields" class="card-field-container  pb-5" style="clear:both">
                                <div class="col-md-6  pull-left unsortable" >
                                <select name="table_heading[field][]" id="table_heading" class="ui fluid dropdown">
                                    <option value="">Database Field</option>
                                    <?php foreach($fields as $key => $field){ ?>
                                    <option value="{{ $field->Field }}">
                                        {{ $field->Field }}</option>
                                    <?php } ?>
                                 </select>
                                </div>
                                <div class="col-md-6  pull-left unsortable" >
                                    <input type="text" class="form-control" name="table_heading[title][]" value="" />
                                </div>
                            </div>


            </div>

            <div style="padding-bottom:50px">
                &nbsp;
            </div>

            <div class="map-boolean_field" style="clear:both">

                <div class="form-group  col-md-6 pull-left">
                <label><strong>YES / NO Fields</strong></label>
                    <select name="map_boolean_field[]" id="map_boolean_field" style="height:150px" multiple="multiple" class="ui fluid dropdown">

                        <?php foreach($fields as $key => $field){ ?>
                        <option value="{{ $field->Field }}" <?php if(is_array($map_boolean_field) && in_array($field->Field, $map_boolean_field)){ ?> selected="selected" <?php } ?>>{{ $field->Field }}</option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="button-container col-md-12" style="clear:both">
                <div class=""><input type="button" class="btn btn-info login-btn btn-md" id="addMoreTableHeading" value="Add More"></div>
            </div>
        </fieldset>





        <h3>Map Filters</h3>
        <fieldset class="scheduler-border">
            <legend class="scheduler-border">Create Filters</legend>

            <div class="form-container-map-filters">
                <div class="col-md-12 clearboth">
                    <div class="form-group col-md-2 pull-left">
                        <strong>Filter Label</strong>
                    </div>

                    <div class="form-group col-md-2 pull-left">
                        <strong>Filter Description</strong>
                    </div>

                    <div class="form-group col-md-2 pull-left">
                        <strong>Database Field</strong>
                    </div>

                    <div class="form-group col-md-2 pull-left">
                        <strong>Manual Options</strong>
                    </div>
                    <div class="form-group col-md-1 pull-left">
                        <strong>Is Required</strong>
                    </div>
                    <div class="form-group col-md-1 pull-left">
                        <strong>Sort Order</strong>
                    </div>
                    <div class="form-group col-md-2 pull-left">
                        <strong>Is Special Filter</strong>
                    </div>

                </div>

                <div class="filter-container" id="filter-container">
                    <?php
                    $filterCounter = 0;
                    $requiredCounter = 0;
                    if(!empty($map_filters)){

                        foreach($map_filters as $mapFilter) {

                            $filterGroupId = "filters-group";
                            $filterLabelId = "filter_label";
                            $filterDescriptionId = "filter_description";
                            $tableFilterId = "table_filter";
                            $defaultValueId = "default_value";
                            $sortOrderId = "sort_order";
                            $removeId = "removediv";
                            $importantId = "important";
                            $isRequiredId = 'is_required';

                            if($filterCounter>0){
                                $filterGroupId = "filters-group".$filterCounter;
                                $filterLabelId = "filter_label".$filterCounter;
                                $filterDescriptionId = "filter_description".$filterCounter;
                                $tableFilterId = "table_filter".$filterCounter;
                                $sortOrderId = "sort_order".$filterCounter;
                                $defaultValueId = "default_value".$filterCounter;
                                $removeId = "removediv".$filterCounter;
                                $importantId = "important".$filterCounter;
                                $isRequiredId = "is_required".$filterCounter;
                            }
                            $filterCounter++;
                        ?>

                    <div id="<?php echo $filterGroupId?>" class="col-md-12 clearboth">
                        <div class="form-group col-md-2 pull-left">
                            <input type="text" name="filter_label[]" placeholder="Filter Label"
                                id="<?php echo $filterLabelId?>" value="{{$mapFilter->filter_label}}"
                                class="form-control login">
                        </div>

                        <div class="form-group col-md-2 pull-left">
                            <input type="text" name="filter_description[]" placeholder="Filter Description"
                                id="<?php echo $filterDescriptionId?>"
                                value="{{$mapFilter->filter_description}}" class="form-control login">
                        </div>

                        <div class="form-group col-md-2 pull-left">
                            <select name="table_filter[]" id="<?php echo $tableFilterId?>" class="ui fluid dropdown">
                                <option value="">Table Filter</option>
                                <?php foreach($fields as $key => $field){ ?>
                                <option value="{{ $field->Field }}"
                                    <?php echo $field->Field==$mapFilter->field?"selected='selected'":''?>>
                                    {{ $field->Field }}</option>
                                <?php } ?>
                            </select>
                        </div>
                        <div class="form-group col-md-2 pull-left">
                            <input type="text" name="default_value[]"
                                placeholder="Options Seperated by | (pipe) / Blank for distinct"
                                id="<?php echo $defaultValueId?>" value="{{$mapFilter->value}}"
                                class="form-control login">
                        </div>

                        <div class="form-group col-md-1 pull-left">
                            <input type="hidden" name="is_required[<?php echo $requiredCounter?>]" id="is_required_hidden" value="false" />
                            <input type="checkbox"  class="form-control" id="is_required_checkbox" <?php if(isset($mapFilter->is_required) && $mapFilter->is_required=='true') { ?> checked="checked" <?php } ?> name="is_required[<?php echo $requiredCounter++?>]" value="true" />
                        </div>

                        <div class="form-group col-md-1 pull-left">
                            <input type="text" name="sort_order[]" placeholder="Sort order"
                                id="<?php echo $sortOrderId?>" value="{{$mapFilter->sort_order}}"
                                class="form-control login">
                        </div>

                        <div class="col-md-1 pull-left">
                            <select class="form-control" name="important[]" id="<?php echo $importantId?>">
                                <?php foreach ($filterTypes as $key => $value) { ?>
                                <option value="<?php echo $key?>"
                                    <?php if(isset($mapFilter->imp) && $mapFilter->imp==$key) { ?> selected='selected'
                                    <?php } ?>><?php echo $value?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group col-md-1 pull-left">
                            <?php if($filterCounter>1) { ?>
                            <a href="javascript:void(0)" id="<?php echo $filterGroupId?>" class="remove_element">X</a>
                            <?php } ?>
                        </div>
                    </div>

                    <?php	}


							} else { $filterCounter++;  ?>

                    <div id="filters-group" class="col-md-12">
                        <div class="form-group col-md-2 pull-left">
                            <input type="text" name="filter_label[]" placeholder="Filter Label" id="filter_label"
                                value="" class="form-control login">
                        </div>

                        <div class="form-group col-md-2 pull-left">
                            <input type="text" name="filter_description[]" placeholder="Filter Description"
                                id="filter_description" value="" class="form-control login">
                        </div>


                        <div class="form-group col-md-2 pull-left">
                            <select name="table_filter[]" id="table_filter" class="ui fluid dropdown">
                                <option value="">Database Field</option>
                                <?php foreach($fields as $key => $field){ ?>
                                <option value="{{ $field->Field }}">{{ $field->Field }}</option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group col-md-2 pull-left">
                            <input type="text" name="default_value[]"
                                placeholder="Options Seperated by | (pipe) / Blank for distinct" id="default_value"
                                value="" class="form-control login">
                        </div>

                        <div class="form-group col-md-1 pull-left">

                            <input type="hidden" name="is_required[0]" value="false" id="is_required_hidden" />
                            <input type="checkbox"  class="form-control" id="is_required_checkbox" name="is_required[0]" value="true" />
                        </div>

                        <div class="form-group col-md-1 pull-left">
                            <input type="text" name="sort_order[]" placeholder="Sort order" id="sort_order" value=""
                                class="form-control login">
                        </div>

                        <div class="form-group col-md-1 pull-left">
                            <select class="form-control" name="important[]" id="important">
                                <?php foreach ($filterTypes as $key => $value) { ?>
                                <option value="<?php echo $key?>"><?php echo $value?></option>
                                <?php } ?>

                            </select>
                        </div>

                    </div>

                    <?php } ?>

                </div>

                <div class="default-filter col-md-12">
                    <?php
								$checked = '';
								if($enabledAddressFilter) {
									$checked = "checked";
								}
							?>

                    <input type="checkbox" name="enable_address_filter" id="enable_address_filter" value="1"
                        <?php echo $checked?> /> <label for="enable_address_filter">Check if you want to Enable Location
                        Radius Filter ?</label>
                    <div class="form-group col-md-6 hide " style="padding-left:0px;">
                        <input type="hidden" name="map_search_radius" placeholder="Radius Options Sepereted by | (Pipe)"
                            id="map_search_radius" value="{{$mapSearchRadius}}" class="form-control login">
                    </div>
                </div>
            </div>


            <div class="button-container col-md-12" style="clear:both">
                <div class=""><input type="button" class="btn btn-info login-btn btn-md" id="addMoreFilter"
                        value="Add More"></div>
            </div>

        </fieldset>

        @if ($mapInfo[0]->map_tablename === 'maps_nth')
            <x-priority-payers-config />
        @endif

        <h3 class="scheduler-border">Map Preferences</h3>

        <fieldset class="scheduler-border">
            <legend class="scheduler-border"> Default Preferences</legend>

            <div class="form-container-card_heading  col-md-12">
                <div class="form-group col-md-3 pull-left"><strong>Default View For Search Results</strong></div>
                <div class="form-group col-md-6 pull-left">
                    <select name="default_view" id="default_view" class="ui fluid dropdown">
                        <?php foreach($viewPreferences as $key => $value) { ?>
                        <option value="<?php echo $key?>" <?php if($key==$selectedPreference) { ?> selected="selected"
                            <?php } ?>><?php echo $value;?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <?php

                if (LD::checkFeatureFlag('map-selection-setting')) { ?>
                    <div class="form-container-card_heading  col-md-12" style="clear:both">
                        <div class="form-group col-md-3 pull-left"><strong>Default View</strong></div>
                        <div class="form-group col-md-6 pull-left">
                            <select name="map_select_preference" id="map_select_preference" class="ui fluid dropdown">
                                <?php foreach($viewPreferences as $key => $value) { ?>
                                <option value="<?php echo $key?>" <?php if($key==$map_select_preference) { ?> selected="selected"
                                    <?php } ?>><?php echo $value;?></option>
                                <?php } ?>
                            </select>
                        </div>
                    </div>
                <?php } ?>


            <div class="form-container-card_heading  col-md-12" style="clear:both">
                <div class="form-group col-md-3 pull-left"><strong>Hide map results until Search</strong></div>
                <div class="form-group col-md-6 pull-left">
                    <select name="map_data_preference" id="map_data_preference" class="ui fluid dropdown">
                        <?php foreach($mapDataPreferences as $key => $value) { ?>
                        <option value="<?php echo $key?>" <?php if($key==$selectedDataPreference) { ?> selected="selected"
                            <?php } ?>><?php echo $value;?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            @if (LD::checkFeatureFlag('unique-identifier-map-config'))
                <div class="form-container-card_heading  col-md-12" style="clear:both">
                    <div class="form-group col-md-3 pull-left"><strong>Unique Identifier</strong></div>
                    <div class="form-group col-md-6 pull-left">
                        <select name="unique_identifier_col[]" id="unique_identifier_col" style="height:150px" multiple="multiple" class="ui fluid dropdown">
                            <?php foreach ($fields as $key => $field) { ?>
                                <option value="{{ $field->Field }}"
                                    <?php if(in_array($field->Field, $selected_unique_identifier)) { ?>
                                    selected="selected" <?php } ?>>
                                    {{ $field->Field }}
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            @endif

        </fieldset>

        <fieldset class="scheduler-border">
            <legend class="scheduler-border">Default Filters</legend>

            <div class="form-container-map-filters">
                <div class="col-md-12 clearboth">

                    <div class="form-group col-md-5 pull-left">
                        <strong>Database Field</strong>
                    </div>

                    <div class="form-group col-md-5 pull-left">
                        <strong>Operator</strong>
                    </div>

                    <div class="form-group col-md-2 pull-left">
                        <strong>Value</strong>
                    </div>

                </div>

                <?php
                    $defaultFilterFieldId = "default_filter_field";
                    $defaultFilterOperatorId = "default_filter_operator";
                    $defaultFilterValueId = 'default_filter_value';
                ?>

                <div class="filter-container" id="filter-container">
                    <div id="filters-group" class="col-md-12">

                        <div class="form-group col-md-5 pull-left">
                            <select name="default_filter_field[]" id="<?php echo $defaultFilterFieldId?>"
                                class="ui fluid dropdown">
                                <option value="">Default Filter</option>
                                <?php foreach($fields as $key => $field){ ?>
                                <option value="{{ $field->Field }}"
                                    <?php if(isset($map_default_filter[0]['field']) && $field->Field==$map_default_filter[0]['field']) { ?>
                                    selected="selected" <?php } ?>>
                                    {{ $field->Field }}</option>
                                <?php } ?>
                            </select>
                        </div>


                        <div class="form-group col-md-5 pull-left">
                            <select name="default_filter_operator[]" id="<?php echo $defaultFilterOperatorId?>"
                                class="ui fluid dropdown">
                                <option value="">Operator</option>
                                <?php foreach(H::getConfigurationOperators() as $key => $value){ ?>
                                <option value="<?php echo $value?>"
                                    <?php if(isset($map_default_filter[0]['operator']) && $value==$map_default_filter[0]['operator']) { ?>
                                    selected="selected" <?php } ?>>
                                    <?php echo $value?></option>
                                <?php } ?>
                            </select>
                        </div>

                        <div class="form-group col-md-2 pull-left">
                            <input type="text" name="default_filter_value[]" placeholder="Value"
                                id="<?php echo $defaultFilterValueId?>"
                                value="{{ $map_default_filter[0]['value'] ?? '' }}"
                                class="form-control login">
                        </div>

                    </div>
                </div>
            </div>


        </fieldset>


    </form>
</div>
<div class="form-group">
    <input type="button" name="submit" class="btn btn-info submit-config-btn btn-md" value="Save">
</div>

<style>
    .card-field-container, .chart_view_container {
        position:relative;
        cursor: move;
    }
    .card-field-container:before, .chart_view_container:before {
        content: "\f0dc";
        display: block;
        font: normal normal normal 14px/1 FontAwesome;
        font-size: inherit;
        text-rendering: auto;
        -webkit-font-smoothing: antialiased;
        position: absolute;
        top: 25%;

    }
</style>

<script type="text/javascript">

    $(document).on('change', '.layer-color-picker', function() {
        const color = $(this).val();
        const card = $(this).closest('.layer-config-card');
        card.find('.layer-color-swatch').css('background', color);
        card.find('.pin-shape-opt svg *').attr('fill', color);
    });

  $( function() {
        $( "#important_fields_container" ).sortable({
            items: "div:not(.unsortable)"
        });
        $( "#chart_view_container" ).sortable({
            items: "div:not(.unsortable)"
        });
  } );

  let layerIndexCounter = {{ count($layers) + 1 }};

  function toggleLayerConfig(id) {
      $('#' + 'body-' + id).toggleClass('open');
      $('#' + 'chevron-' + id).toggleClass('open');
  }

  function handleFile(e) {
      const input = e.target;
      const file = input.files[0];
      const idx = input.dataset.layerIdx;

      if (!file) return;

      const reader = new FileReader();
      reader.onload = function (event) {
          const text = event.target.result;
          const lines = text.split(/\r?\n/);
          if (lines.length > 0) {
              const headers = lines[0].replace(/[\x00-\x1F\x80-\xFF]/g, "").split(',');
              updateSelect(idx, headers);
          }
      };
      reader.readAsText(file);
  }

  function updateSelect(idx, headers) {
      const selects = document.querySelectorAll(`.columnsSelect-${idx}`);
      if (!selects) return;

      selects.forEach(select => {
          const selectedVals = Array.from(select.selectedOptions).map(opt => opt.value);

          select.innerHTML = '<option value="">-- Select column --</option>';
          headers.forEach(header => {
              const cleanHeader = header.trim();
              if (cleanHeader) {
                  const dbName = cleanHeader.replace(/ /g, '_');
                  const option = document.createElement('option');
                  option.value = dbName;
                  option.textContent = cleanHeader;
                  if (selectedVals.includes(dbName)) option.selected = true;
                  select.appendChild(option);
              }
          });
      });
  }

  function addNewLayer() {
      const idx = layerIndexCounter++;
      const html = `
        <div class="layer-config-card" id="layer-card-${idx}">
            <div class="layer-config-header" onclick="toggleLayerConfig('${idx}')">
                <div class="layer-color-swatch" style="background:#1a5ca8;"></div>
                <span class="layer-config-name">New Layer (Unsaved)</span>
                <span class="layer-status-badge badge-nodata">No data uploaded</span>
                <span class="layer-chevron open" id="chevron-${idx}">▼</span>
            </div>
            <div class="layer-config-body open" id="body-${idx}">
                <div class="layer-inner-grid">
                    <div>
                        <div class="field-label">Layer name</div>
                        <input type="text" class="form-input" name="layers[${idx}][map_name]" placeholder="E.g. Clinics" required>
                    </div>
                    <div>
                        <div class="field-label">Pin Color</div>
                        <div class="color-pick-row">
                            <input type="color" name="layers[${idx}][pin_color]" value="#1a5ca8" class="layer-color-picker">
                        </div>
                    </div>
                    <div>
                        <div class="field-label">Pin style</div>
                        <div class="pin-shape-row">
                            <input type="hidden" name="layers[${idx}][pin_shape]" class="pin-shape-input" value="circle">
                            <div class="pin-shape-opt selected" data-value="circle" title="Circle"><svg width="16" height="16" viewBox="0 0 16 16"><circle cx="8" cy="8" r="6" fill="#1a5ca8"/></svg></div>
                            <div class="pin-shape-opt" data-value="diamond" title="Diamond"><svg width="16" height="16" viewBox="0 0 16 16"><rect x="3" y="3" width="10" height="10" transform="rotate(45 8 8)" fill="#1a5ca8"/></svg></div>
                            <div class="pin-shape-opt" data-value="teardrop" title="Teardrop"><svg width="16" height="16" viewBox="0 0 16 16"><path d="M8 15 C8 15 2 10 2 6 C2 3.2 4.7 1 8 1 C11.3 1 14 3.2 14 6 C14 10 8 15 8 15Z" fill="#1a5ca8"/></svg></div>
                            <div class="pin-shape-opt" data-value="square" title="Square"><svg width="16" height="16" viewBox="0 0 16 16"><rect x="3" y="3" width="10" height="10" rx="2" fill="#1a5ca8"/></svg></div>
                        </div>
                    </div>
                </div>

                <div class="field-label">Data source (CSV) <span class="text-danger">*</span></div>
                <div class="upload-mode-row">
                    <label class="upload-mode-opt"><input type="radio" name="layers[${idx}][upload_mode]" value="replace" checked> Initial Upload</label>
                </div>
                <input type="file" name="layers[${idx}][file]" accept=".csv" class="form-control mb-3 layer-file-input" data-layer-idx="${idx}" required>

                <div class="field-label">Address field mapping</div>
                <div class="address-grid">
                    <div><div style="font-size:11px;color:#9ca3af;margin-bottom:3px;">Address</div><select name="layers[${idx}][latlong_fields][address1]" class="form-select columnsSelect-${idx}"><option value="">-- Upload CSV first --</option></select></div>
                    <div><div style="font-size:11px;color:#9ca3af;margin-bottom:3px;">City</div><select name="layers[${idx}][latlong_fields][city]" class="form-select columnsSelect-${idx}"><option value="">-- Upload CSV first --</option></select></div>
                    <div><div style="font-size:11px;color:#9ca3af;margin-bottom:3px;">State</div><select name="layers[${idx}][latlong_fields][state]" class="form-select columnsSelect-${idx}"><option value="">-- Upload CSV first --</option></select></div>
                    <div><div style="font-size:11px;color:#9ca3af;margin-bottom:3px;">Zip</div><select name="layers[${idx}][latlong_fields][zipcode]" class="form-select columnsSelect-${idx}"><option value="">-- Upload CSV first --</option></select></div>
                </div>

                <div class="popup-filter-row mt-3">
                    <div>
                        <div class="field-label">Map popup fields</div>
                        <select class="form-select columnsSelect-${idx}" name="layers[${idx}][card_fields][]" multiple size="5" required></select>
                    </div>
                </div>
            </div>
        </div>`;
      $('#layers-container').append(html);
  }

  $(document).ready(function() {

      $(document).on('change', '.layer-file-input', function (e) {
          handleFile(e);
      });

      $(document).on('click', '.pin-shape-opt', function() {
        var parentContainer = $(this).closest('.pin-shape-row');
        console.log(parentContainer);

        parentContainer.find('.pin-shape-opt').css({
            'border-color': '#e5e7eb',
            'background': '#fff'
        }).removeClass('selected');

        $(this).css({
            'border-color': '#1a5ca8',
            'background': '#eef3fa'
        }).addClass('selected');

        var chosenShape = $(this).data('value');
        parentContainer.find('.pin-shape-input').val(chosenShape);
    });

    $(document).on("click", ".submit-config-btn", function() {
        var form = $('#configuration-form')[0];

        if (form && !form.checkValidity()) {
            form.reportValidity();
            return false;
        }

        $("#configuration-form").submit();
    });

    $(document).on("click", '.remove_element', function() {
        $("#" + $(this).attr('id')).remove();
    });


    var importantFieldCounter = <?php echo $importantFieldCount?>;
    var cardHeading = <?php echo $cardHeadingCount?>;
    var cardsubHeading = <?php echo $cardSubHeadingCount?>;
    var latlong = <?php echo $latlongCount?>;
    var tableHeading = <?php echo $tableHeadingCount?>;

    var filters = <?php echo $filterCounter?>;


    //Radius setting

    $("#enable_address_filter").change(function() {

        $('#map_search_radius').parent().addClass('hide');
    });

    $("#addMoreFilter").on("click", function() {
        filters++;
        var filterLabel = $("#filter_label").eq(0).clone();
        var filterDescription = $("#filter_description").eq(0).clone();
        var tablefilter = $("#table_filter").eq(0).clone();
        var defaultVal = $("#default_value").eq(0).clone();
        var sortOrder = $("#sort_order").eq(0).clone();
        var isRequired = $("#is_required_checkbox").eq(0).clone();
        var isRequiredHidden = $("#is_required_hidden").eq(0).clone();
        var important = $("#important").eq(0).clone();

        $(filterLabel).attr("id", "filter_label" + filters);
        $(filterDescription).attr("id", "filter_description" + filters);
        $(tablefilter).attr("id", "table_filter" + filters);
        $(defaultVal).attr("id", "default_value" + filters);
        $(sortOrder).attr("id", "sort_order" + filters);
        $(isRequired).attr("id", "is_required_checkbox" + filters);
        $(isRequired).attr("checked", false);
        $(isRequired).attr("name", "is_required["+(filters-1)+"]");

        $(isRequiredHidden).attr("id", "is_required_hidden" + filters);
        $(isRequiredHidden).attr("name", "is_required["+(filters-1)+"]");

        $(important).attr("id", "important" + filters);

        $("#filter-container").append('<div id="filters-group' + filters +
            '" class="col-md-12 clearboth"></div>');

        $("#filters-group" + filters).append('<div id="div-filter_label' + filters +
            '" class="form-group col-md-2 pull-left"></div><div id="div-filter_description' +
            filters + '" class="form-group col-md-2 pull-left"></div><div id="div-table_filter' +
            filters + '" class="form-group col-md-2 pull-left"></div><div id="div-default_value' +
            filters + '" class="form-group col-md-2 pull-left"></div><div id="div-isrequired' +
            filters + '" class="form-group col-md-1 pull-left"></div><div id="div-sort_order' +
            filters + '" class="form-group col-md-1 pull-left"></div><div id="div-important' +
            filters + '" class="form-group col-md-1 pull-left"></div><div id="div-remove' +
            filters + '" class="form-group col-md-1 pull-left"></div>');

        $("#div-filter_label" + filters).append(filterLabel);
        $("#div-filter_description" + filters).append(filterDescription);
        $("#div-table_filter" + filters).append(tablefilter);
        $("#div-default_value" + filters).append(defaultVal);
        $("#div-isrequired" + filters).append(isRequiredHidden);
        $("#div-isrequired" + filters).append(isRequired);
        $("#div-sort_order" + filters).append(sortOrder);
        $("#div-important" + filters).append(important);
        $("#div-remove" + filters).append("<a href='javscript:void(0)' id='filters-group" + filters +
            "' class='remove_element'>X</a>");

        $("#table_filter" + filters).dropdown({
            "clearable": true
        });

    });






    $("#addMoreCardHeading").on("click", function() {
        cardHeading++;
        var newaddress = $("#card_heading").eq(0).clone();
        $(newaddress).attr("id", "card_heading" + cardHeading);

        $(".form-container-card_heading").append('<div id="card_heading_html' + cardHeading +
            '" class="form-group col-md-6 pull-left"></div>');
        $("#card_heading_html" + cardHeading).append(newaddress);
        $('#card_heading' + cardHeading).dropdown({
            "clearable": true
        });
    });

    $("#addMoreCardSubheading").on("click", function() {
        cardsubHeading++;
        var newaddress = $("#card_subheading").eq(0).clone();
        $(newaddress).attr("id", "card_subheading" + cardsubHeading);

        $(".form-container-card_subheading").append('<div id="card_subheading_html' + cardsubHeading +
            '" class="form-group col-md-3 pull-left"></div>');
        $("#card_subheading_html" + cardsubHeading).append(newaddress);
        $('#card_subheading' + cardsubHeading).dropdown({
            "clearable": true
        });
    });




    $("#addMoreImportantFields").on("click", function() {
        importantFieldCounter++;
        var newaddress = $("#important_fields").eq(0).clone();
        $(newaddress).attr("id", "card_fields_container" + importantFieldCounter);
        $(newaddress).find("select").attr("id", "card_fields" + importantFieldCounter);

        $("#important_fields_container").append(newaddress);

    });

    $("#addMoreLatlongFields").on("click", function() {
        latlong++;
        var newaddress = $("#latlong_fields").eq(0).clone();
        $(newaddress).attr("id", "latlong_fields" + latlong);

        $(".form-container-latlong_fields").append('<div id="latlong_fields_html' + latlong +
            '" class="form-group col-md-3 pull-left"></div>');
        $("#latlong_fields_html" + latlong).append(newaddress);
        $('#latlong_fields' + latlong).dropdown({
            "clearable": true
        });
    });

    $("#addMoreTableHeading").on("click", function() {
        tableHeading++;
        var newaddress = $("#chart_view_fields").eq(0).clone();
        $(newaddress).attr("id", "chart_view_container" + tableHeading);
        $(newaddress).find("select").attr("id", "chart_view" + tableHeading);

        $("#chart_view_container").append(newaddress);
        /*
        $(".form-container-table_heading").append('<div id="table_heading_html' + tableHeading +
            '" class="form-group col-md-3 pull-left"></div>');
        $("#table_heading_html" + tableHeading).append(newaddress);
        $('#table_heading' + tableHeading).dropdown({
            "clearable": true
        });*/
    });


    $("#addMoreTableFields").on("click", function() {
        tableFields++;
        var newaddress = $("#table_fields").eq(0).clone();
        $(newaddress).attr("id", "table_fields" + tableFields);

        $(".form-container-table_fields").append('<div id="table_fields_html' + tableFields +
            '" class="form-group col-md-3 pull-left"></div>');
        $("#table_fields_html" + tableFields).append(newaddress);
        $('#table_fields' + tableFields).dropdown({
            "clearable": true
        });
    });

    for (var i = 0; i < filters; i++) {
        if (i == 0) {
            $('#table_filter').dropdown({
                "clearable": true
            });
            $('#filter_type').dropdown({
                "clearable": true
            });
        } else {
            $('#table_filter' + i).dropdown({
                "clearable": true
            });

            $('#filter_type' + i).dropdown({
                "clearable": true
            });
        }
    }


    for (var i = 0; i < cardHeading; i++) {
        if (i == 0) {
            $('#card_heading').dropdown({
                "clearable": true
            });
        } else {
            $('#card_heading' + i).dropdown({
                "clearable": true
            });
        }
    }

    for (var i = 0; i < cardsubHeading; i++) {
        if (i == 0) {
            $('#card_subheading').dropdown({
                "clearable": true
            });
        } else {
            $('#card_subheading' + i).dropdown({
                "clearable": true
            });
        }
    }



    for (var i = 0; i < latlong; i++) {
        if (i == 0) {
            $('#latlong_fields').dropdown({
                "clearable": true
            });
        } else {
            $('#latlong_fields' + i).dropdown({
                "clearable": true
            });
        }
    }







    $('#table_filter').dropdown({
        "clearable": true
    });

    $('#filter_type').dropdown({
        "clearable": true
    });


});
</script>
<style>
.remove_element {
    padding-top: 10px;
}
.heading {
    text-align:left;
    padding-bottom:10px;
    font-weight:500;
}

.clearboth {
    clear: both;
}

.hide {
    display: none !important;
}

.show {
    display: block !important;
}

.default-filter {
    text-align: left;
    padding-left: 30px;
    clear: both;
}

.selection.ui.dropdown {
    width: 100%;
}

.form-container {
    width: 100%;
}

.login-page form {
    width: 100%;
}

.form-group,
.form-group label {
    display: flex;
}

.form-group label {
    display: inline-block;
    vertical-align: middle;
    line-height: 40px;
    margin-right: 10px;
    width: 40%;
    text-align: left;
}

.right-side {
    display: block;
}

.heading-title {
    text-align: left;
    margin-bottom: 50px;
}

fieldset.scheduler-border {
    border: 1px groove #ddd !important;
    padding: 0 1.4em 1.4em 1.4em !important;
    margin: 0 0 1.5em 0 !important;
    -webkit-box-shadow: 0px 0px 0px 0px #000;
    box-shadow: 0px 0px 0px 0px #000;
}

legend.scheduler-border {
    font-size: 1.2em !important;
    font-weight: bold !important;
    text-align: left !important;
    width: inherit;
    /* Or auto */
    padding: 0 10px;
    /* To give a bit of padding on the left and right */
    border-bottom: none;
}
</style>

<style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; font-size: 13px; background: #f0f2f5; color: #1a1a1a; min-height: 100vh; }

  .form-row { display: grid; grid-template-columns: 180px 1fr; align-items: center; gap: 12px; margin-bottom: 12px; }
  .form-label { font-size: 13px; color: #374151; font-weight: 500; }
  .form-input { padding: 8px 12px; border: 1px solid #dde0e5; border-radius: 6px; font-size: 13px; color: #1a1a1a; font-family: inherit; width: 100%; }
  .form-select { padding: 8px 12px; border: 1px solid #dde0e5; border-radius: 6px; font-size: 13px; color: #1a1a1a; font-family: inherit; width: 100%; background: #fff; }
  .layer-config-card { border: 1px solid #dde0e5; border-radius: 8px; margin-bottom: 10px; overflow: hidden; }
  .layer-config-header { display: flex; align-items: center; gap: 10px; padding: 12px 16px; cursor: pointer; background: #f9fafb; user-select: none; }
  .layer-config-header:hover { background: #f3f4f6; }
  .layer-color-swatch { width: 18px; height: 18px; border-radius: 4px; flex-shrink: 0; border: 1px solid rgba(0,0,0,0.1); }
  .layer-config-name { flex: 1; font-size: 13px; font-weight: 600; color: #1a1a1a; }
  .layer-status-badge { font-size: 11px; padding: 3px 9px; border-radius: 10px; font-weight: 500; }
  .badge-uploaded { background: #e1f5ee; color: #0f6e56; }
  .badge-nodata { background: #faeeda; color: #854F0B; }
  .layer-chevron { font-size: 12px; color: #9ca3af; margin-left: 4px; }
  .layer-chevron.open { transform: rotate(180deg); }
  .layer-config-body { padding: 16px; border-top: 1px solid #f0f2f5; display: none; }
  .layer-config-body.open { display: block; }
  .layer-inner-grid { display: grid; grid-template-columns: 4fr 1fr 3fr; gap: 14px; margin-bottom: 14px; }
  .field-label { font-size: 12px; color: #6b7280; font-weight: 500; margin-bottom: 4px; }
  .address-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 8px; margin-bottom: 14px; }
  .upload-zone strong { color: #1a3a5c; }
  .upload-zone .reupload { color: #1a3a5c; cursor: pointer; text-decoration: underline; margin-left: 4px; }
  .popup-filter-row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
  .add-layer-btn { display: flex; align-items: center; gap: 8px; padding: 10px 14px; border: 1.5px dashed #dde0e5; border-radius: 8px; background: transparent; color: #6b7280; font-size: 13px; cursor: pointer; width: 100%; font-family: inherit; }
  .add-layer-btn:hover { background: #f9fafb; }
  .plus-circle { width: 22px; height: 22px; border-radius: 50%; background: #f3f4f6; border: 1px solid #dde0e5; display: flex; align-items: center; justify-content: center; font-size: 16px; color: #6b7280; line-height: 1; flex-shrink: 0; }
  .color-pick-row { display: flex; align-items: center; gap: 8px; width: 100%; }
  .color-pick-row input[type=color] { flex-grow: 1; width: 100%; height: 34px; border: 1px solid #dde0e5; border-radius: 6px; cursor: pointer; padding: 2px; }
  .pin-shape-row { display:flex; gap:6px; margin:4px 0 10px; }
  .pin-shape-opt { width:34px; height:34px; border:2px solid #e5e7eb; border-radius:6px; display:flex; align-items:center; justify-content:center; cursor:pointer; background:#fff; transition:border-color .15s,background .15s; flex-shrink:0; }
  .pin-shape-opt:hover { border-color:#93b5e1; }
  .pin-shape-opt.selected { border-color:#1a5ca8; background:#eef3fa; }
  .upload-mode-row { display:flex; gap:16px; margin:6px 0 12px; }
  .upload-mode-opt { display:flex; align-items:center; gap:6px; font-size:12px; color:#374151; cursor:pointer; }
  .upload-mode-opt input[type=radio] { accent-color:#1a5ca8; cursor:pointer; }
  .info-hint { font-size: 11px; color: #9ca3af; font-style: italic; margin-top: 4px; }
</style>

@stop
