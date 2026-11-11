<?php
use App\Helpers\H;
?>
@section('title', 'Manage Branch')
@section('datatabletop')
<div class="form-group col-md-6 head-title">
    <h1 class="h3 mb-2 text-gray-800">Manage Branch</h1> 
    @canPermission('add_branch') 
    <a title="Manage IPEA Email Template" href="{{ route('emailtemplate.edit') }}"><i class="fa fa-plus-circle"></i></a>
    @endcanPermission
</div>
@if ($message = Session::get('success'))
<div class="alert alert-success col-md-12">
    <button type="button" class="close" data-dismiss="alert">×</button>
    {{ $message }}
</div>
@endif
@endsection

@extends('layouts.datatable')

@section('datatablebottom')

@endsection