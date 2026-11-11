<?php
namespace App\Helpers;
$loggedInUser = H::getLoggedInUser();
$mapInfo = H::getSelectedMapInfo();
use Illuminate\Support\Facades\Session;
?>
@extends('layouts.myaccount')

@section('title', 'Import '.$mapInfo[0]->map_name.' Data')

@section('content')
<div class="heading-title">
    <h2>Import {{$mapInfo[0]->map_name}} Data</h2>
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

@if ($message = Session::get('error'))
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
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
    <form action="{{ route('map.importdata') }}" enctype='multipart/form-data' id="importForm" method="POST"
        class="needs-validation" novalidate>
        @csrf

        <div class="form-group">
            <label for="name" class="">Upload CSV</label>
            <input type="file" name="map_csv" id="map1csv" class="form-control login">
        </div>
        <div class="form-group append">
            <input type="checkbox" name="isAppend" id="isAppend" class="form-control login">
            <label for="name" class="">Append to existing map data</label>
        </div>


        <div class="form-group">
            <input type="submit" name="submit" class="btn btn-info login-btn btn-md" value="Verify and Import">
        </div>

    </form>
</div>
<script>
$('#importForm').submit(function() {
    if (!$("#isAppend").is(':checked')) {
        return confirm(
            "This will delete all existing data for this map.  Press OK to continue or Cancel to exit.");
    } else {
        return true;
    }
});
</script>

<style>
.form-container {
    width: 50%;
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

.append input {
    float: left;
    width: 25px;
    margin-right: 10px;
}
</style>


@stop
