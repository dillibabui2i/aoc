<?php

use App\Enums\Adjustments\AdjustmentTypeEnum;
use App\Helpers\H;
use App\Helpers\LD;
use App\BranchLocations;
use App\Models\Adjustment;

?>
@extends('layouts.myaccount')
@section('title', 'Manage Adjustment')
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
            width: 100%;
            height: 400px;
            overflow-y: auto;
        }
    </style>
    <div class="container-fluid">

        <!-- Page Heading -->
        <div class="row">
            <div class="form-group col-md-6 head-title">
                <h1 class="h3 mb-2 text-gray-800">{{ isset($adjustmentDetail) ? 'Edit Adjustment' : 'Add Adjustment' }}</h1>
                <a href="{{ route('adjustment.index') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
            <form method="POST" id="add-form-validate" action="{{ route('adjustment.save') }}">
                @if(isset($adjustmentDetail))
                    <input type="hidden" name="id" id="id" value="{{$adjustmentDetail->id}}">
                @endif
                @csrf
                <div class="form-row">
                    <div class="form-group col-md-12">
                        <label for="category" class="text-left">Category</label>
                        <input type="text" name="category" id="category"
                               value="{{isset($adjustmentDetail) ? $adjustmentDetail->category : ''}}"
                               class="form-control"
                            @readonly(isset($adjustmentDetail))>
                    </div>
                    <div class="col-md-12">
                        <div id="category-warning" class="alert alert-warning" style="display: none">
                            An Adjustment with this Category Name already exists.
                            Please double check this is not a duplicate entry.
                        </div>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="approval_level_1" class="text-left">Approval level 1 </label>
                        <select name="approval_level_1" id="approval_level_1"
                                class="form-control approval-select">
                            <option value="">None</option>
                            @foreach ($users as $key => $value)
                                <option value="{{$key}}" @if(isset($adjustmentDetail))
                                    @selected($adjustmentDetail->level_1?->name == $value)
                                    @endif> {{$value}}</option>
                            @endforeach
                            <option value="role:{{$regionArDirectorRoleId}}"
                            @if(isset($adjustmentDetail))
                                @selected($adjustmentDetail->level_1_type === AdjustmentTypeEnum::ROLE->value &&
                                    $adjustmentDetail->level_1_id = $regionArDirectorRoleId)
                                @endif> Region AR Director
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="approval_level_2" class="text-left">Approval level 2 </label>
                        <select name="approval_level_2" id="approval_level_2"
                                class="form-control approval-select">
                            <option value="">None</option>
                            @foreach ($users as $key => $value)
                                <option value="{{$key}}" @if(isset($adjustmentDetail))
                                    @selected($adjustmentDetail->level_2?->name === $value)
                                    @endif> {{$value}}</option>
                            @endforeach
                            <option value="role:{{$regionArDirectorRoleId}}"
                            @if(isset($adjustmentDetail))
                                @selected($adjustmentDetail->level_2_type === AdjustmentTypeEnum::ROLE->value &&
                                    $adjustmentDetail->level_2_id = $regionArDirectorRoleId)
                                @endif> Region AR Director
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="approval_level_3" class="text-left">Approval level 3 </label>
                        <select name="approval_level_3" id="approval_level_3"
                                class="form-control approval-select">
                            <option value="">None</option>
                            @foreach ($users as $key => $value)
                                <option value="{{$key}}" @if(isset($adjustmentDetail))
                                    @selected($adjustmentDetail->level_3?->name === $value)
                                    @endif> {{$value}}</option>
                            @endforeach
                            <option value="role:{{$regionArDirectorRoleId}}"
                            @if(isset($adjustmentDetail))
                                @selected($adjustmentDetail->level_3_type === AdjustmentTypeEnum::ROLE->value &&
                                    $adjustmentDetail->level_3_id = $regionArDirectorRoleId)
                                @endif> Region AR Director
                            </option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="requires_ops_review" class="text-left">Requires Ops Review?</label>
                        <select name="requires_ops_review" id="requires_ops_review" class="form-control">
                            <option value="1" @if(isset($adjustmentDetail))
                                {{ ($adjustmentDetail->requires_ops_review)?'selected':'' }}
                                @endif>Yes
                            </option>
                            <option value="0" @if(isset($adjustmentDetail))
                                {{ (!$adjustmentDetail->requires_ops_review)?'selected':'' }}
                                @endif>No
                            </option>
                        </select>
                    </div>
                    @if(isset($adjustmentDetail))
                        <div class="form-group col-md-12">
                            <label for="activity_log" class="text-left">Activity Log</label>
                            <div style="width: 100%; overflow-y: scroll; height: 225px;">
                                @if(count($logData) > 0)
                                    <table style="width: 100%;border: 1px solid #dee2e6;" class="table table-striped"
                                           aria-hidden="true">
                                        @foreach ($logData->toArray() as $data)
                                            <tr>
                                                <td class="text-left"
                                                    style="white-space: pre-line;">{!!$data['notes']!!}</td>
                                                <td class="text-left">{{date("M d Y", strtotime($data['created_at'])) . ' at ' . date("g:i A", strtotime($data['created_at']))}}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
                <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
            </form>
        </div>
    </div>
    <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function () {
            const selects = document.querySelectorAll('.approval-select');

            let masterOptions = [];
            if (selects.length > 0) {
                const firstSelect = selects[0];
                Array.from(firstSelect.options).forEach(opt => {
                    if (opt.value !== '') {
                        masterOptions.push({
                            value: opt.value,
                            text: opt.textContent.trim()
                        });
                    }
                });
            }

            function refreshSelects() {
                const selected = new Set();
                selects.forEach(s => {
                    if (s.value !== '') selected.add(s.value);
                });

                selects.forEach(select => {
                    const currentValue = select.value;

                    select.innerHTML = '';

                    masterOptions.forEach(opt => {
                        if (opt.value === currentValue || !selected.has(opt.value)) {
                            const newOpt = document.createElement('option');
                            newOpt.value = opt.value;
                            newOpt.textContent = opt.text;
                            select.appendChild(newOpt);
                        }
                    });

                    const noneOpt = document.createElement('option');
                    noneOpt.value = '';
                    noneOpt.textContent = ' None ';
                    select.appendChild(noneOpt);

                    select.value = currentValue;
                });
            }

            selects.forEach(select => {
                select.addEventListener('change', refreshSelects);
            });

            refreshSelects();
        });

        $('#category').on('input', function() {
            let val = $(this).val();

            if (!val) {
                $('#category-warning').hide();
                return;
            }

            $.get('{{ route("adjustment.check-category") }}', {category: val}, function(data) {
                if (data.exists) {
                    $('#category-warning').show();
                } else {
                    $('#category-warning').hide();
                }
            });
        });

    </script>
@endsection
