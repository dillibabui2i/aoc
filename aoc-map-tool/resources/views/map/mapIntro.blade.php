<?php
use App\Helpers\H;
use Illuminate\Support\Facades\Session;

$loggedInUser = H::getLoggedInUser();
// $data = H::getMapTableData();
$mapInfo = H::getSelectedMapInfo();
$selectedMap = H::getSelectedMap();
?>

@extends('layouts.myaccount')


@section('content')

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
    <?php if($selectedMap){ ?>
    <a class="" href="{{route('map.table')}}"><i class="fa fa-table"></i></a>
    <div class="menu-cnt">
        <ul class="list-unstyled components">
            @canAnyPermission(['map.import','map.configurations'])
            <li><a href="#setting-data" title="Setting" data-toggle="collapse" class="dropdown-toggle"><i
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
            @endcanAnyPermission
        </ul>
    </div>
    <?php } ?>
    <a class="" id="mobile-filter-btn" href="javascript:void(0);"><i class="fa fa-filter"></i></a>
</div>
<div class="map-container">
    <h3 class="map-intro-text">Select a map from the drop down box to the left</h3>
</div>


@stop