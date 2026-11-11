<?php

use App\Helpers\H;
use App\Helpers\LD;
use App\BranchLocations;

$renewablesRulesDiscipline = config('constant.renewablesRulesDiscipline');
?>
@extends('layouts.myaccount')
@section('title', 'Manage Renewables Rule')
@section('content')
<style>
    span.multiselect-native-select {
        width: 100%;
        display: flex;
        flex-direction: column;
    }

    span.multiselect-native-select .btn-group {
        width: 100%;
        max-width: 100%;
    }

    .multiselect-container.dropdown-menu.show {
        width: 100%;
        overflow-y: auto;
    }

    #discipline-error, #state-error {
        order: 2;
    }

    #rule_exists {
        display: none;
        text-align: center;
        position: absolute;
        top: 123%;
        left: 50%;
        transform: translate(-50%, -50%);
    }
</style>
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ isset($ruleDetail) ? 'Edit Renewables Rule' : 'Add Renewables Rule' }}</h1>
            <a href="{{ route('renewablesrule') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
        @if( isset($ruleDetail))
        <form method="POST" id="edit-form-validate" action="{{ route('updaterenewablesrule') }}">
            <input type="hidden" name="id" id="id" value="{{$ruleDetail->id}}">
            <input type="hidden" name="renewable_type_id" value="{{$ruleDetail->renewable_type_id}}">
            @else
            <form method="POST" id="add-form-validate" action="{{ route('saverenewablesrule') }}">
                @endif
                @csrf
                <div class="form-row">
                    @if(isset($ruleDetail))
                    <div class="form-group col-md-12">
                        <label for="name" class="text-left">Renewable</label>
                        <input type="text" class="form-control" id="name" name="name" value="{{ isset($ruleDetail)? $ruleDetail->name:''}}" readonly>
                    </div>
                    @else
                    <div class="form-group col-md-12">
                        <label for="renewable_name" class="text-left">Renewable</label>
                        <span style="width:100%;">
                            <select name="name" id="renewable_name" class="form-control">
                                <option value="">Select Renewable</option>
                                @foreach($renewablesname as $name)
                                <option value="{{$name->id}}">{{$name->name}}</option>
                                @endforeach
                            </select>
                        </span>
                    </div>
                    <p class="alert alert-danger" id="rule_exists">
                        Selected Renewable rule already exists, please edit the rule
                    </p>
                    @endif
                    <div class="form-group col-md-12" id="stateDiv">
                        @php
                            $ruleState = isset($ruleDetail)
                                ? explode(',', $ruleDetail->states)
                                : [];
                        @endphp
                        <label for="state" class="text-left">Which of the following states does this rule apply to?</label>
                        <select name="state[]" id="state" class="form-control" multiple>
                            @foreach($states as $state)
                                <option value="{{$state}}" {{(in_array($state, $ruleState) ? 'selected' : '')}}>
                                    {{$state}}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12" id="rolediv">
                        <label for="roles" class="text-left">Which of the following roles does this rule apply to?</label>
                        <select name="roles[]" id="roles" class="form-control" multiple>
                        @if(isset($ruleDetail))   
                            <option value="isClinician" {{(isset($ruleDetail) && $ruleDetail->isClinician == 1) ? 'selected' : ''}}>isClinician</option>
                            <option value="IsOfficeStaff" {{(isset($ruleDetail) && $ruleDetail->isOfficeStaff == 1) ? 'selected' : ''}}>IsOfficeStaff</option>
                        @else
                            <option value="isClinician">isClinician</option>
                            <option value="IsOfficeStaff">IsOfficeStaff</option>
                        @endif    
                        </select>
                    </div>
                    <div class="form-group col-md-12" id="disciplineDiv">
                        <label for="discipline" class="text-left">Which of the following disciplines does this rule apply to?</label>
                        <select name="discipline[]" id="discipline" class="form-control" multiple>
                            @foreach($renewablesRulesDiscipline as $discipline)
                            @if(isset($ruleDetail))
                                @php
                                    $ruleDiscipline = explode(",", $ruleDetail->discipline);
                                @endphp
                                <option value="{{$discipline}}" {{(in_array($discipline, $ruleDiscipline) ? 'selected' : '')}}>{{$discipline}}</option>
                            @else
                                <option value="{{$discipline}}">{{$discipline}}</option>
                            @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12" id="bucketdiv">
                        <label for="buckets" class="text-left">Which of the following buckets does this rule apply to?</label>
                        <select name="buckets[]" id="buckets" class="form-control" multiple>
                        @if(isset($ruleDetail))   
                            <option value="Expiring" {{(isset($ruleDetail) && $ruleDetail->Expiring == 1) ? 'selected' : ''}}>Expiring</option>
                            <option value="Missing" {{(isset($ruleDetail) && $ruleDetail->Missing == 1) ? 'selected' : ''}}>Missing</option>
                        @else
                            <option value="Expiring">Expiring</option>
                            <option value="Missing">Missing</option>
                        @endif    
                        </select>
                    </div>
                    <div class="form-group col-md-12" id="email_to_nurseDiv">
                        <label for="email_to_nurse" class="text-left">send automated email remiders to staff when expiring?</label>
                        <select name="email_to_nurse" id="email_to_nurse" class="form-control">
                            <option value="0" {{(isset($ruleDetail) && $ruleDetail->email_to_nurse == 0) ? 'selected' : ''}}>NO</option>
                            <option value="1" {{(isset($ruleDetail) && $ruleDetail->email_to_nurse == 1) ? 'selected' : ''}}>YES</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12" id="has_client_over21">
                        <label for="has_client_over21" class="text-left">Has client over 21?</label>
                        <select name="has_client_over21" id="has_client_over21" class="form-control">
                            <option value="0" {{(isset($ruleDetail) && $ruleDetail->has_client_over21 == 0) ? 'selected' : ''}}>NO</option>
                            <option value="1" {{(isset($ruleDetail) && $ruleDetail->has_client_over21 == 1) ? 'selected' : ''}}>YES</option>
                        </select>
                    </div>
                    @if( isset($ruleDetail))
                    <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
                    @else
                    <button type="submit" id="savebtn" class="btn btn-primary pull-left mt-3">Save</button>
                    @endif
            </form>
    </div>
</div>
<script>
    /*$("#renewable_name").on('change', function() {
        var selectedValue = $(this).val();
        if(selectedValue !== ''){
            $.ajax({
                url: "{{ route('checkrenewablesrule') }}",
                type: "GET",
                dataType: "json",
                data: {
                    id: selectedValue
                },
                success: function(data) {
                    if (data.success == 0) {
                        $("#rule_exists").show();
                        $("#stateDiv").hide();
                        $("#disciplineDiv").hide();
                        $("#email_to_nurseDiv").hide();
                        $("#savebtn").hide();
                        $("#has_client_over21").hide();
                        $("#bucketdiv").hide();
                        $("#rolediv").hide();
                    } else {
                        $("#rule_exists").hide();
                        $("#stateDiv").show();
                        $("#disciplineDiv").show();
                        $("#email_to_nurseDiv").show();
                        $("#savebtn").show();
                        $("#has_client_over21").show();
                        $("#bucketdiv").show();
                        $("#rolediv").show();
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        }
        $("#rule_exists").hide();
    });*/
    $(document).ready(function() {
        @php
            $formvalidation = isset($ruleDetail) ? '#edit-form-validate' : '#add-form-validate';
        @endphp
        var formvalidation = '<?php echo  $formvalidation ?>'
        $(formvalidation).validate({
        ignore: [],
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        rules: {
            "name":{
                required: true
            },
            "state[]":{
                required: true
            },
            "discipline[]":{
                required: true
            },
        }
        });
        $("#state").multiselect({
            includeSelectAllOption: true
        });
        $("#roles").multiselect({
            includeSelectAllOption: true
        });
        $("#discipline").multiselect({
            includeSelectAllOption: true
        });
        $("#buckets").multiselect({
            includeSelectAllOption: true
        })

        @php
            $formtype = isset($ruleDetail) ? 'edit' : 'save';
        @endphp
        /*let formtype = '<?php echo  $formtype ?>';
        if(formtype == 'save'){
            $("#stateDiv").hide();
            $("#disciplineDiv").hide();
            $("#email_to_nurseDiv").hide();
            $("#savebtn").hide();
            $("#has_client_over21").hide();
            $("#bucketdiv").hide();
            $("#rolediv").hide();
        }*/
    });
</script>
@endsection