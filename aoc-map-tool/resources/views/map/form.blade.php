@extends('layouts.myaccount')
@section('title', 'Manage Map')
@section('content')
<!-- Begin Page Content -->

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ isset($mapDetail) ? 'Edit Map' : 'Add Map' }}</h1>
            <a href="{{ route('managemap') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
        <form method="POST" enctype="multipart/form-data" id="add-form-validate"
            action="{{ route('managemap.savemap') }}">
            @if( isset($mapDetail))
            <input type="hidden" name="map_id" id="map_id" value="{{$mapDetail->map_id}}">
            @endif
            @csrf

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="map_name" class="text-left">Name</label>
                    <input type="text" class="form-control" id="map_name" name="map_name"
                        value="{{ isset($mapDetail)?$mapDetail->map_tablename:''}}">
                </div>
                <div class="form-group col-md-12">
                    <label for="map_status" class="text-left">Status</label>
                    <select name="map_status" id="map_status" class="form-control">
                        <option value="1" @if(isset($mapDetail)) {{ ($mapDetail->map_status == 1)?'selected':'' }}
                            @endif>Enabled</option>
                        <option value="2" @if(isset($mapDetail)) {{ ($mapDetail->map_status == 2)?'selected':'' }}
                            @endif>Disabled</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="map_csv" class="text-left">CSV</label>
                    <input type="file" class="form-control" id="map_csv" name="map_csv">
                </div>
                <div class="form-group col-md-9">
                    <label for="isExistMap" class="text-left">Use Existing Map</label>
                    <input type="checkbox" class="form-control" id="isExistMap" name="isExistMap" />
                </div>
                <div class="form-group col-md-12 map-list hide">
                    <label for="mapList" class="text-left">Select Existing Map</label>
                    <select name="mapList" id="mapList" class="form-control">
                        <option value="0">Select Map</option>
                        <?php foreach($mapData as $value){ ?>
                        <option value="<?php echo $value->map_id;?>"><?php echo $value->map_name;?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
        </form>
    </div>
</div>

<script type="text/javascript">
// Wait for the DOM to be ready
$(function() {

    $("#isExistMap").change(function() {
        if (this.checked) {
            $('#mapList').parent().removeClass('hide');
            $('#map_csv').parent().addClass('hide');
            $('#mapList').parent().css({
                display: "flex"
            });
        } else {
            $('#mapList').parent().addClass('hide');
            $('#map_csv').parent().removeClass('hide');
            $('#map_csv').parent().css({
                display: "flex"
            });
        }
    });


    // Initialize form validation on the registration form.
    // It has the name attribute "registration"
    $("#add-form-validate").validate({
        // Specify validation rules
        rules: {
            map_name: "required",
            map_csv: "required",
        },
        // Specify validation error messages
        messages: {
            map_name: "Please enter Name",
            map_csv: "Please select CSV file",
        },
        errorPlacement: function() {
            return false;
        },
    });
});
</script>
<!-- /.container-fluid -->
@endsection
