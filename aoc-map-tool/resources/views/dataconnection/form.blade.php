<?php

use App\Helpers\H;
use App\Helpers\LD;

?>
@extends('layouts.myaccount')
@section('title', 'Data Connections')
@section('content')
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Edit Data Connections</h1>
        </div>
    </div>
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
    <div id="dataconnectionform" class="dataconnectionform">
        <form method="POST" id="schedule_class_form" class="scaffold-form" enctype="multipart/form-data" action="{{ route('syncdataconnection.updatesyncdataconnection') }}">
            <input type="hidden" name="id" id="id" value="{{$dataConnectionDetail->id}}">
            @csrf
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="title">Title</label>
                        {{$dataConnectionDetail->process}}
                    </div>
                    <div class="form-group col-md-12">
                        <label for="start_time">Purpose</label>
                        <input type="input" class="form-control" id="purpose" name="purpose" @if(isset($dataConnectionDetail)) value="{{ $dataConnectionDetail->purpose}}" @endif>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="start_time">Frequency</label>
                        <input type="input" class="form-control" id="frequency" name="frequency" @if(isset($dataConnectionDetail)) value="{{ $dataConnectionDetail->frequency}}" @endif>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="start_time">Comment</label>
                        <input type="input" class="form-control" id="info" name="info" @if(isset($dataConnectionDetail)) value="{{ $dataConnectionDetail->info}}" @endif>
                    </div>
                    <div class="form-group col-md-12">
                        <button type="submit" id="savebtn" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>
    </div>
</div>

<script type="text/javascript" id="addhoursjs">
    $(function() {
        $("#schedule_class_form").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "title": {
                    required: true
                },
                "roles_id[]": {
                    required: true
                },
                "start_time": {
                    required: true
                },
                "end_time": {
                    required: true
                },
                "color_filled": {
                    required: true
                },
                "color_open": {
                    required: true
                },
                "repeat": {
                    required: true
                },
                "branchid[]": {
                    required: true
                },
                "repeat_on[]": {
                    required: function (element) {
                        if($('#repeat option:selected').val() =='Day') {
                            return true;
                        }
                    }
                },
                "send_notification": {
                    required: true
                },
                "send_notification_duration[]": {
                    required: function (element) {
                        if($('input[name="send_notification"]:checked').val() == 1) {
                            return true;
                        }
                    }
                },
                "specific_date": {
                    required: function (element) {
                        if($('#repeat option:selected').val() =='Specific') {
                            return true;
                        }
                    }
                },
            },
            errorPlacement: function(error, element) { 
                if($(element).attr("id") == 'branchid' || $(element).attr("id") == 'roles_id' || $(element).attr("id") == 'send_notification_duration') {
                    error.insertAfter(element);
                } else if($(element).attr("name") == 'send_notification') {
                    element.parent().parent().append(error);
                }
            }
        });
    });
    $(document).ready(function() {
        $('#branchid').multiselect({
            includeSelectAllOption: true,
            enableClickableOptGroups: true
        });
        $('#roles_id').multiselect({
            includeSelectAllOption: true
        });
        $('#send_notification_duration').multiselect({
            includeSelectAllOption: true
        });
    });
</script>
@endsection
