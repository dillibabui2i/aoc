<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\BranchLocations;
?>
@extends('layouts.myaccount')
@section('title', 'Manage Region')
@section('content')
<!-- Begin Page Content -->

<style>
.recruiting-section {
    display: none;
}
</style>
<style>
    span.multiselect-native-select {
        width: 100%;
    }
    span.multiselect-native-select .btn-group {
        width: 100%;
    }
    .multiselect-container.dropdown-menu.show {
        width:100%;
        height: 400px;
        overflow-y: auto;
    }
</style>
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ isset($regionDetail) ? 'Edit Region' : 'Add Region' }}</h1>
            <a href="{{ route('region') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
        </div>
    </div>
    <!-- DataTales Example -->
    @if ($errors->any())
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {!! implode('', $errors->all('<div>:message</div>')) !!}
    </div>
    @endif
    @if ($message = Session::get('error'))
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert">×</button>
        {{ $message }}
    </div>
    @endif
    <div>
        @if( isset($regionDetail))
        <form method="POST" id="add-form-validate" action="{{ route('region.updateregion') }}">
            <input type="hidden" name="id" id="id" value="{{$regionDetail->id}}">
            @else
            <form method="POST" id="add-form-validate" action="{{ route('region.saveregion') }}">
                @endif
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="name" class="text-left">Region Name</label>
                        <input type="text" class="form-control" id="name" name="region_name"
                            value="{{ isset($regionDetail)?$regionDetail->region_name:''}}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="title" class="text-left">Team Leader</label>
                        <input type="text" class="form-control" id="team_leader" name="team_leader"
                            value="{{ isset($regionDetail)?$regionDetail->team_leader:''}}">
                    </div>
                </div>
    </div>
    <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
    </form>
</div>
<script type="text/javascript">
</script>
<!-- /.container-fluid -->
@endsection
