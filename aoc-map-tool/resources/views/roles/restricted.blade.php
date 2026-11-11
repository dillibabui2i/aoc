@extends('layouts.myaccount')
@section('title', 'Restricted')
@section('content')
<!-- Begin Page Content -->
<div class="container-fluid">

<!-- Page Heading -->
<div class="row">
  <div class="form-group col-md-11 head-title">
    <h1 class="h3 mb-2 text-gray-800">Restricted</h1>
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
You are restricted to view this page.		  
 </div>
</div>
@endsection
