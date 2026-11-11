<?php

use App\Helpers\H;
use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Manage Feature Flags')
@section('content')
<!-- Begin Page Content -->

<style>
    .recruiting-section {
        display: none;
    }
</style>

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage Feature Flags</h1>
            <a href="{{ route('managefeatureflag') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
        <form method="POST" id="add-form-validate" action="{{ route('managefeatureflag.updatefeatureflag') }}">
            <input type="hidden" name="id" id="id" value="{{$featureFlagDetail->id}}">
            @csrf
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label class="text-left  col-md-3">Flag Name</label>
                    <label class="text-left">{{ $featureFlagDetail->flag}}</label>
                </div>

                <div class="form-group col-md-12">
                    <label for="users" class="text-left col-md-3">Users</label>
                    <input type="text" class="form-control" id="users" name="users" value="{{ $featureFlagDetail->users}}">
                </div>

                <div class="form-group col-md-12">
                    <label for="ips" class="text-left col-md-3">IP Address</label>
                    <input type="text" class="form-control" id="ips" name="ips" value="{{ $featureFlagDetail->ips}}">
                </div>

                <div class="form-group col-md-12">
                    <label for="status" class="text-left col-md-3">Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="0" @if(isset($featureFlagDetail)) {{ ($featureFlagDetail->status == 0)?'selected':'' }} @endif>Targeting Off</option>
                        <option value="1" @if(isset($featureFlagDetail)) {{ ($featureFlagDetail->status == 1)?'selected':'' }} @endif>Targeting On</option>
                        <option value="-1" @if(isset($featureFlagDetail)) {{ ($featureFlagDetail->status == -1)?'selected':'' }} @endif>Flag Disabled</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
                </div>
            </div>
        </form>
    </div>

</div>
<!-- /.container-fluid -->
@endsection