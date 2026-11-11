<?php
use App\Helpers\H;
use App\Helpers\LD;
use App\BranchLocations;

?>
@extends('layouts.myaccount')
@section('title', 'Manage Kantime')
@section('content')
<!-- Begin Page Content -->

<div class="container-fluid">

    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ isset($facilityDetail) ? 'Edit Kantime Facility' : 'Add Kantime Facility' }}</h1>
            <a href="{{ route('view.kantimefacility') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
       
        <form method="POST" id="add-form-validate" action="{{ route('save.kantimefacility') }}">
            <input type="hidden" name="id" id="id" value="{{$facilityDetail->id}}">
            @csrf
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="FacilityName" class="text-left">FacilityName</label>
                        <select name="FacilityName" id="FacilityName" class="form-control" disabled>
                            <?php
                                foreach($facilities as  $facility) {?>
                                    <option value="<?php echo $facility->id?>" <?php if($facilityDetail->id==$facility->id) { ?> selected="selected" <?php }?>><?php echo $facility->FacilityName?></option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="State" class="text-left">State</label>
                        <select name="State" id="State" class="form-control" disabled>
                            <?php
                            foreach($states as  $state) {?>
                                <option value="<?php echo $state?>" <?php if($facilityDetail->State==$state) { ?> selected="selected" <?php }?>><?php echo $state?></option>
                        <?php } ?>
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="region" class="text-left">Region</label>
                        <select name="region" id="region" class="form-control">
                            <option value="">Select Region</option>
                            <?php
                            foreach($regions as  $region) {?>
                                <option value="<?php echo $region->id?>"  <?php if($facilityDetail->region==$region->id) { ?> selected="selected" <?php }?> ><?php echo $region->region_name?></option>
                        <?php } ?>
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="branch" class="text-left">Associated Branch</label>
                        <select name="branch" id="branch" class="form-control">
                            <option value="">Select Branch</option>
                            <?php
                            foreach($branchLocations as  $branch) {?>
                                <option value="<?php echo $branch->id?>" <?php if($facilityDetail->branchid==$branch->id) { ?> selected="selected" <?php }?>><?php echo $branch->branch?></option>
                        <?php } ?>
                        </select>
                    </div>

                    <div class="form-group col-md-12">
                        <label for="branch_coverage_booked_goal" class="text-left">Coverage Booked Branch Goal</label>
                        <input type="number" class="form-control" id="branch_coverage_booked_goal" name="branch_coverage_booked_goal" value="{{$facilityDetail->branch_coverage_booked_goal}}">
                    </div>


                   
                   
                </div>
                <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
            </form>

       
    </div>
   

@endsection
