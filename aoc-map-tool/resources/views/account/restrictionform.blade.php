<?php
use App\Helpers\H;
use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Manage IP')
@section('content')
<!-- Begin Page Content -->


<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Manage IP</h1>
            <a href="{{ route('loginrestrictions') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
      
        <form method="POST" id="add-form-validate" action="{{ route('blacklist.save') }}">
            <input type="hidden" name="id" id="id" value="{{ isset($data)?$data->id:''}}">
                @csrf
                <div class="form-row">
                    
                    <div class="form-group col-md-12">
                        <label for="ipaddress" class="text-left">IP Address</label>
                        <input type="text" class="form-control" id="ipaddress" name="ipaddress" value="{{ isset($data)?$data->ipaddress:''}}">
                    </div>

                    <div class="form-group col-md-12">
                        <label for="username" class="text-left">Username</label>
                        <input type="text" class="form-control" id="username" name="username" value="{{ isset($data)?$data->username:''}}">
                    </div>
                    
                    <div class="form-group col-md-12">
                        <label for="whitelisted" class="text-left">Whitelist</label>
                        <select name="whitelisted" id="whitelisted" class="form-control">
                            <option value="1" @if(isset($data)) {{ ($data->whitelisted == 1)?'selected':'' }}
                                @endif>True</option>
                            <option value="0" @if(isset($data)) {{ ($data->whitelisted == 0)?'selected':'' }}
                                @endif>False</option>
                        </select>
                    </div>

                   
                    <div class="form-group col-md-12">
                        <label for="blacklisted" class="text-left">Blacklist</label>
                        <select name="blacklisted" id="blacklisted" class="form-control">
                            <option value="1" @if(isset($data)) {{ ($data->blacklisted == 1)?'selected':'' }}
                                @endif>True</option>
                            <option value="0" @if(isset($data)) {{ ($data->blacklisted == 0)?'selected':'' }}
                                @endif>False</option>
                        </select>
                    </div>
                </div>
    </div>
    
    <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>

    </form>
</div>
</script>
<!-- /.container-fluid -->
@endsection