<?php
use App\Helpers\LD;
use App\Helpers\H;
$intakeDiscipline = config('constant.intakeDiscipline');
$approvedByList = [];
$approvedUserList = H::getHelper('UserPermissionResolver')->getUserByPermission('can_approve_pay_rates');
foreach($approvedUserList as $user) {
    $approvedByList[$user->id] = $user->name;
}
?>
<div class="nurse-form-inner mt-5" data-nurse-index="{{ $nursesCount }}">

    <a href="javascript:void(0);" class='nurse-remove-icon'><i class="fa fa-times-circle-o"></i></a>
    <div class="form-group row">
        <input type="hidden" name="candidateId{{$nursesCount}}">
        <div class="col-md-6">
            <label class="w-100" for="firstLastName">First & Last Name</label>
            <input type="text" class="form-control clinician-search" id="firstLastName{{$nursesCount}}" name="firstLastName{{$nursesCount}}" />
        </div>
        <div class="col-md-6">
            <label  class="w-100" for="phoneNumber">Phone Number</label>
            <input type="text" class="form-control phoneNumber" id="phoneNumber{{$nursesCount}}" name="phoneNumber{{$nursesCount}}" value=""/>
        </div>
    </div>
    <div class="form-group row">
        <div class="col-md-6">
            <label class="w-100" for="intakeNurse">Email Address</label>
            <input type="text" class="form-control emailAddress" id="email{{$nursesCount}}" name="email{{$nursesCount}}" />
        </div>
        <div class="col-md-6 d-flex">
            <div class="col-md-6 p-0 pr-2">
                <label  class="w-100" for="currentRate">Current Rate</label>
                <input type="text" class="form-control" id="currentRate{{$nursesCount}}" name="currentRate{{$nursesCount}}" value=""/>
            </div>
            <div class="col-md-6 form-check form-check-inline p-0 mt-4">
                <input type="checkbox" class="form-check-input" id="paystubsRequest{{$nursesCount}}" name="paystubsRequest{{$nursesCount}}" />
                <label class="form-check-label ml-1" for="paystubsRequest">Paystubs Request</label>
            </div>
        </div>
    </div>
    @if(LD::checkFeatureFlag('intake-form-new-change'))
    <div class="form-group row">
        <div class="col-md-6">
            <label class="w-100" for="intakeNurse">Discipline</label>
            <select name="discipline{{$nursesCount}}"
            class="form-control"
            id="discipline{{$nursesCount}}">
                <option value="">Select option</option>
                @foreach($intakeDiscipline as $discipline)
                <option value="{{$discipline}}">{{$discipline}}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="w-100" for="intakeNurse">GP%</label>
            <input type="number" class="form-control" id="gp{{$nursesCount}}" name="gp{{$nursesCount}}" />
        </div>
        <div class="col-md-3">
            <label class="w-100" for="intakeNurse">Approved By</label>
            <select name="approved_by{{$nursesCount}}"
            class="form-control"
            id="approved_by{{$nursesCount}}">
                <option value="">Select option</option>
                @foreach($approvedByList as $key=>$user)
                <option value="{{$key}}">{{$user}}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="nurse-error text-danger" style="display: none;"></div>
    @endif
</div>
