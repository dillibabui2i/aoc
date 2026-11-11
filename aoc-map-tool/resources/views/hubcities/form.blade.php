<?php
    use App\Helpers\H;
    $states = config('constant.states');
?>
@extends('layouts.myaccount')
@section('title', 'Manage Hub City')
@section('content')
<!-- Begin Page Content -->

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ ($mode == 'edit') ? 'Edit Hub City' : 'Add Hub City' }}</h1>
            <a href="{{ route('hubcities') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
        <form method="POST" id="add-form-validate" action="{{ route('hubcities.save') }}">
            @if($mode == 'edit')
                <input type="hidden" name="id" id="id" value="{{$data['id']}}">
            @endif
            @csrf
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="State" class="col-sm-2 col-form-label">State</label>
                    <div class="col-sm-9">
                        <input type="hidden" name="ShortState" id="ShortState" value="{{($mode == 'edit')?$data['ShortState']:''}}"/>
                        <select name="State" id="State" class="form-control">
                            <option value=""></option>
                            @foreach($states as $abb => $state)
                                <option data-abb= "{{$abb}}" value="{{$state}}" {{($mode == 'edit' && $data['State'] == $state)?'selected':''}}>{{$state}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="City" class="col-sm-2 col-form-label">City</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="City" name="City"
                            value="{{($mode == 'edit')?$data['City']:''}}">
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="HUB" class="col-sm-2 col-form-label">HUB</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="HUB" name="HUB"
                            value="{{($mode == 'edit')?$data['HUB']:''}}">
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="Branch" class="col-sm-2 col-form-label">Branch</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="Branch" name="Branch"
                            value="{{($mode == 'edit')?$data['Branch']:''}}">
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="MilesFromHub" class="col-sm-2 col-form-label">Miles From Hub</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="MilesFromHub" name="MilesFromHub"
                            value="{{($mode == 'edit')?$data['MilesFromHub']:''}}">
                        <small id="MilesFromHubHelp" class="form-text text-muted text-left">Enter miles up to 2 decimal only.</small>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="Population" class="col-sm-2 col-form-label">Population</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="Population" name="Population"
                            value="{{($mode == 'edit')?$data['Population']:''}}">
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="TextBlastRadius" class="col-sm-2 col-form-label">Text Blast Radius</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="TextBlastRadius" name="TextBlastRadius"
                            value="{{($mode == 'edit')?$data['TextBlastRadius']:''}}">
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="Notes" class="col-sm-2 col-form-label">Notes</label>
                    <div class="col-sm-9">
                        <textarea class="form-control" id="Notes" name="Notes">{{($mode == 'edit')?$data['Notes']:''}}</textarea>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
        </form>
    </div>
</div>

<script type="text/javascript">
// Wait for the DOM to be ready
$(function() {
    $.validator.addMethod('decimal', function(value, element) {
        return this.optional(element) || /^((\d+(\\.\d{0,2})?)|((\d*(\.\d{1,2}))))$/.test(value);
    }, "Please enter a correct number, format 0.00");

    $("#add-form-validate").validate({
        // Specify validation rules
        rules: {
            State: "required",
            City: "required",
            HUB: "required",
            Branch: "required",
            TextBlastRadius: {
                digits: true
            }
        },
        // Specify validation error messages
        messages: {
            State: "Please select state",
            City: "Please enter city",
            HUB: "Please enter hub",
            Branch: "Please enter branch",
        },
        errorPlacement: function() {
            return true;
        },
    });

    $('#State').change(function() {
        $('#ShortState').val($(this).find(':selected').data('abb'));
    });
});
</script>
<!-- /.container-fluid -->
@endsection
