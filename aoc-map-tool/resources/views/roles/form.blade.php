<?php

use App\Helpers\LD;

?>


@extends('layouts.myaccount')
@section('title', 'Manage Roles')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-10 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ isset($roleDetail) ? 'Edit Role' : 'Add Role' }}</h1>
            <a href="{{ route('manageroles') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
        <form method="POST" enctype="multipart/form-data" class="rolesform" id="add-form-validate"
            action="{{ route('manageroles.saverole') }}">
            @if( isset($roleDetail))
            <input type="hidden" name="id" id="id" value="{{$roleDetail->id}}">
            @endif
            @csrf
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="name" class="text-left">Name</label>
                    <input type="text" class="form-control" id="name" name="name"
                        value="{{ isset($roleDetail)?$roleDetail->name:''}}" data-val="{{ isset($roleDetail)?$roleDetail->name:''}}">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="name" class="text-left">Department</label>
                    <select class="form-control" name="department" id="department">
                        <option value="0">Select Department</option>
                        <?php
                    $departments = config('constant')['roleDepartment'];
                    foreach ($departments as $key => $value) {?>
                        <option
                            <?php echo (isset($roleDetail) && $roleDetail->department == $value) ? "selected='selected'" : ''; ?>
                            value="<?php echo $value;?>"><?php echo $value;?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="name" class="text-left">Permissions</label>
                    <div id="accordion" style="width:100%;">
                        @foreach($permissionsDetail as $group => $permissions)
                        <h3>{{$group}}</h3>
                        <div>
                            <ul>
                                @foreach($permissions as $permission)
                                    @if($permission['name'] === 'Retry Background Check' && !LD::checkFeatureFlag('new-background-check'))
                                        @continue(1)
                                    @endif

                                <li><input name="permissions[]" type="checkbox" value="{{$permission['id']}}"
                                        {{ isset($roleDetail) ? (in_array($permission['id'], $selectedPermissions) ? "checked" : "") : ""}} />
                                    {{$permission['name']}}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="form-row">
                <button type="submit" class="btn btn-primary pull-left">Save</button> &nbsp;&nbsp;&nbsp; OR
                &nbsp;&nbsp;&nbsp; <a href="{{route('missingroutes')}}">Create Permission Rule</a>
            </div>
        </form>
    </div>
</div>
<script type="text/javascript">
// Wait for the DOM to be ready
$(function() {
    // Initialize form validation on the registration form.
    // It has the name attribute "registration"
    let _t = $('meta[name="csrf-token"]').attr('content');
    $("#add-form-validate").validate({
        // Specify validation rules
        errorElement: 'span',
        rules: {
            name: {
                required: true,
                remote: {
                    param:"{{route('checkduplicaterole')}}?_token=" + _t,
                    depends: function() {
                        return ($('#name').val() != $('#name').data('val'));
                    }
                }
            },
        },
        // Specify validation error messages
        messages: {
            name: {
                required: "Please provide Role Name here",
                remote: "This role already exists"
            },
        },
        errorPlacement: function(error, element) {
            error.insertAfter(element.parent());
            element.parent().css('margin', '0px');
        },
    });

    $("#accordion").accordion();
});
</script>
<!-- /.container-fluid -->
@endsection
