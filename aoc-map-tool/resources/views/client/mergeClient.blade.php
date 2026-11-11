<?php
use App\Helpers\H;
use App\Helpers\LD;
?>
@extends('layouts.myaccount')
@section('title', 'Merge Duplicate Client')
@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Merge Duplicate Client</h1>
        </div>
    </div>
    <div class="col-md-12">
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
        <form action="" method="post">
            <div class="form-group row d-flex">
                <div class="col-md-3">
                    <label  class="w-100" for="patientAccount">Patient Account</label>
                    <input type="text" class="form-control" id="patientAccount" name="patientAccount" value=""/>
                </div>
                <div class="col-md-3 text-left align-self-end">
                    <button type="button" class="btn btn-primary" id="searchDuplicateClient" data-id="">Search</button>
                </div>
            </div>
        </form>
        <form action="{{route('client.mergingAllDuplicateClients')}}" method="post" id="mergeDuplicateClientForm">
            @csrf
        <div class="form-group row d-flex">
            <div class="col-md-3 text-left align-self-end">
                <label  class="w-100" for="patientAccount">- OR -</label>
                <button type="button" class="btn btn-primary" id="mergeDuplicateClient" data-id="">Merge All Duplicate Clients</button>
            </div>
        </div>
        </form>
        <div class="result-cnt">
            <div id="result">

            </div>
            <div class="text-left hide">
                <p class="text-danger">
                    Click to 'Merge' to merge data from ID
                    <span id="badClientId"></span>
                    into ID
                    <span id="goodClientId"></span>
                </p>
                <button type="button" class="btn btn-primary" id="mergeClient" data-id="">Merge</button>
            </div>
        </div>
    </div>
</div>
<script>
    $('#mergeDuplicateClient').click(function(){
        $('#mergeDuplicateClientForm').submit();
    });
    $('#searchDuplicateClient').click(function(){
        if($('#patientAccount').val() != ''){
            searchingDuplicatedClient($('#patientAccount').val());
        }
    });
    $('#mergeClient').click(function(){
        console.log('sdafd',$('#patientAccount').val());
        if($('#patientAccount').val() != ''){
            let _token = $('meta[name="csrf-token"]').attr('content');
            $.ajax({
                url: "{{route('client.mergingClients')}}",
                type: 'get',
                data: {
                    patientAccount:$('#patientAccount').val(),
                    _token: _token
                },
                success: function(data) {
                    location.reload();
                }
            });
        }
    });
    function searchingDuplicatedClient(patientAccount){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('client.searchingDuplicatedClient')}}",
            type: 'get',
            data: {
                patientAccount:patientAccount,
                _token: _token
            },
            success: function(data) {
                $('#result').html(data);
                console.log(duplicatedPatientsCount,'duplicatedPatientsCount');
                if(duplicatedPatientsCount > 1){
                    $('#mergeDuplicateClientForm').hide();
                    $('#mergeClient').parent().removeClass('hide');
                    $('#badClientId').html(badClientId);
                    $('#goodClientId').html(goodClientId);
                }
            }
        });
    }
</script>
@endsection
