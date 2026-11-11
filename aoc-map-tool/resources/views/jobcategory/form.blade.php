<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\BranchLocations;
?>
@extends('layouts.myaccount')
@section('title', 'Manage Job Category')
@section('content')
<!-- Begin Page Content -->

<style>
.recruiting-section {
    display: none;
}
</style>
<style>
    span.multiselect-native-select {
        width: 100%;
    }
    span.multiselect-native-select .btn-group {
        width: 100%;
    }
    .multiselect-container.dropdown-menu.show {
        width:100%;
        height: 400px;
        overflow-y: auto;
    }
</style>
<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ isset($jobCategory) ? 'Edit Job Category' : 'Add Job Category' }}</h1>
            <a href="{{ route('jobcategory') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
        @if( isset($jobCategoryDetail))
        <form method="POST" id="add-form-validate" action="{{ route('jobcategory.save') }}">
            <input type="hidden" name="id" id="id" value="{{$jobCategoryDetail->id}}">
            @else
            <form method="POST" id="add-form-validate" action="{{ route('jobcategory.save') }}">
                @endif
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="name" class="text-left">State</label>
                        <input type="text" class="form-control" id="State" name="State"
                            value="{{ isset($jobCategoryDetail)?$jobCategoryDetail->State:''}}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="title" class="text-left">Type</label>
                        <input type="text" class="form-control" id="Type" name="Type"
                            value="{{ isset($jobCategoryDetail)?$jobCategoryDetail->Type:''}}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="title" class="text-left">Category</label>
                        <input type="text" class="form-control" id="category" name="category"
                            value="{{ isset($jobCategoryDetail)?$jobCategoryDetail->category:''}}">
                    </div>
                </div>
                <div class="form-group w-100 mt-4">
                    <label for="activityLog" class="text-left font-weight-bold d-block">Activity Log</label>
                    <div class="border rounded bg-light p-2" style="width:100%;">
                        <div id="activityLog" class="form-control w-100" readonly
                             style="height:200px; overflow-y:auto; line-height:1.4; white-space:normal; resize:none;">
                            @if(isset($logs) && count($logs))
                                @foreach($logs as $log)
                                    @php
                                        $user = $log->user?->name ?? 'System';
                                        $time = \Carbon\Carbon::parse($log->created_at)->format('M d Y \a\t h:i A');
                                        $old = json_decode($log->old_value ?? '{}', true);
                                        $new = json_decode($log->new_value ?? '{}', true);
                                        $action = $log->action;
                                    @endphp

                                    @if(in_array($action, ['created', 'enabled', 'disabled']))
                                        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                            <div>
                                                Job Category <b>{{ $action }}</b> by {{ $user }}
                                            </div>
                                            <div class="text-muted" style="font-size: 12px;">{{ $time }}</div>
                                        </div>
                                    @elseif($action === 'updated')
                                        @foreach(['State', 'Type', 'category'] as $field)
                                            @if(isset($old[$field]) && isset($new[$field]) && $old[$field] !== $new[$field])
                                                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                                    <div>
                                                        <b>{{ ucfirst($field) }}</b> was <b>updated</b> from "{{ $old[$field] ?? 'N/A' }}"
                                                        to "{{ $new[$field] ?? 'N/A' }}" by {{ $user }}
                                                    </div>
                                                    <div class="text-muted" style="font-size: 12px;">{{ $time }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    @else
                                        <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-1">
                                            <div>{{ ucfirst($action) }} by {{ $user }}</div>
                                            <div class="text-muted" style="font-size: 12px;">{{ $time }}</div>
                                        </div>
                                    @endif
                                @endforeach
                            @else
                                <div>No activity found.</div>
                            @endif
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
            </form>
    </div>
</div>
<script type="text/javascript">
</script>
<!-- /.container-fluid -->
@endsection
