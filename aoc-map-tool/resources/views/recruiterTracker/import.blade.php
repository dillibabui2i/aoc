<?php
use App\Helpers\H;
?>
@extends('layouts.myaccount')
@section('title', 'Import Tracker')
@section('content')

<style>
    .importcandidate label {
        display: block;
    }
</style>

<div class="container-fluid importTracker">
    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Import Tracker</h1>
        </div>
    </div>

    @if ($errors->any())
        <div class="col-sm-12">
            <div class="alert  alert-warning alert-dismissible fade show" role="alert">
                @foreach ($errors->all() as $error)
                <span>
                    <p>{{ $error }}</p>
                </span>
                @endforeach
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        </div>
    @endif
    @if ($message = Session::get('error'))
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {{ $message }}
        </div>
    @endif
    @if (session('success'))
        <div class="col-sm-12">
            <div class="alert  alert-success alert-dismissible fade show" role="alert">
                {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
            </div>
        </div>
    @endif
    <form method="POST" enctype="multipart/form-data" id="import-form-validate" action="{{ route('tracker.processimport') }}">
        @csrf
        <div class="col-md-6">
            <div class="form-group row">
                <div class="col-md-12">
                    <label for="trackercsv" style="width: 100%;">Import CSV</label>
                    <input type="file" class="form-control"  name="trackercsv" id="trackercsv" value=""/>
                </div>
            </div>
            <div class="form-group row pt-2">
                <div class="col-md-6 text-left">
                    <button class="btn btn-primary" onclick="processImportTracker(this);" type="button">Import Tracker</button>
                </div>
            </div>

        </div>
    </form>

</div>

<script type="text/javascript">
    function processImportTracker(button){
        $("#import-form-validate").submit();
    }
</script>

@endsection
