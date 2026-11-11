@php
    use App\Helpers\H;
    use App\Helpers\LD;
    $recruiters = H::getHelper('ConstantHelper')->getRecruiters('id');
    $isTherapy = ($mode == 'edit') ? (bool)($data['is_therapy'] ?? 0) : false;
@endphp

@extends('layouts.myaccount')
@section('title', 'Manage Whiteboard')
@section('content')
<!-- Begin Page Content -->

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">{{ ($mode == 'edit') ? 'Edit Whiteboard' : 'Add Whiteboard' }}</h1>
            <a href="{{ route('managewhiteboard') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
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
        <form method="POST" id="add-form-validate" action="{{ route('managewhiteboard.save') }}">
            @if($mode == 'edit')
                <input type="hidden" name="id" id="id" value="{{$data['id']}}">
            @endif
            @csrf
            <input type="hidden" name="is_therapy" id="is_therapy" value="{{ $isTherapy ? '1' : '0' }}">

            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="user_name" class="col-sm-2 col-form-label">Recruiter</label>
                    <div class="col-sm-9">
                        <input type="hidden" name="user_id" id="user_id" value="{{($mode == 'edit')?$data['user_id']:''}}"/>
                        <select name="user_name" id="user_name" class="form-control">
                            <option value=""></option>
                            @foreach($recruiters as $recruiter)
                                <option data-id= "{{$recruiter['value']}}" value="{{$recruiter['text']}}" {{($mode == 'edit' && $data['user_name'] == $recruiter['text'])?'selected':''}}>{{$recruiter['text']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="wb_type" class="col-sm-2 col-form-label">Recruiter Type</label>
                    <div class="col-sm-9">
                        <select id="wb_type" class="form-control">
                            <option value="0" {{ !$isTherapy ? 'selected' : '' }}>PDN Recruiter</option>
                            <option value="1" {{ $isTherapy ? 'selected' : '' }}>Therapy Recruiter</option>
                        </select>
                    </div>
                </div>

                <div class="form-group col-md-12" id="region-pdn-group">
                    <label for="region" class="col-sm-2 col-form-label">Region</label>
                    <div class="col-sm-9">
                        <select name="region" id="region" class="form-control">
                            <option value=""></option>
                            @foreach($regions as $region)
                                <option value="{{$region['value']}}" {{($mode == 'edit' && !$isTherapy && $data['region'] == $region['value'])?'selected':''}}>{{$region['text']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group col-md-12" id="region-therapy-group">
                    <label for="region_therapy" class="col-sm-2 col-form-label">Region</label>
                    <div class="col-sm-9">
                        <select name="region" id="region_therapy" class="form-control">
                            <option value=""></option>
                            @foreach($therapyRegions as $region)
                                <option value="{{$region['value']}}" {{($mode == 'edit' && $isTherapy && $data['region'] == $region['value'])?'selected':''}}>{{$region['text']}}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="user_name" class="col-sm-2 col-form-label">Primary</label>
                    <div class="col-sm-9 text-left mt-3">
                        <input type="checkbox" name="primary" value="1" {{($mode == 'edit' && $data['primary'] == 1)?'checked':''}}/>
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="row_id" class="col-sm-2 col-form-label">Service</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="row_id" name="row_id"
                            value="{{($mode == 'edit')?$data['row_id']:''}}">
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="territory" class="col-sm-2 col-form-label">Territory</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="territory" name="territory"
                            value="{{($mode == 'edit')?$data['territory']:''}}">
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="hire_date" class="col-sm-2 col-form-label">Hired</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="hire_date" name="hire_date"
                            value="{{($mode == 'edit')?$data['hire_date']:''}}">
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="goals" class="col-sm-2 col-form-label">Goals</label>
                    <div class="col-md-9">
                        <div class="form-group col-md-12 pdn-goal">
                            <label for="candidate_start_hour_goals" class="col-sm-3 col-form-label">Candidate Start Hours</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="candidate_start_hour_goals" name="candidate_start_hour_goals"
                                    value="{{($mode == 'edit')?$data['candidate_start_hour_goals']:''}}">
                            </div>
                        </div>
                        <div class="form-group col-md-12 pdn-goal">
                            <label for="ipea_goal" class="col-sm-3 col-form-label">IPEA</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="ipea_goal" name="ipea_goal"
                                    value="{{($mode == 'edit')?$data['ipea_goal']:''}}">
                            </div>
                        </div>
                        <div class="form-group col-md-12 pdn-goal">
                            <label for="pepi_wepi_goal" class="col-sm-3 col-form-label">PEPI/WEPI</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="pepi_wepi_goal" name="pepi_wepi_goal"
                                    value="{{($mode == 'edit')?$data['pepi_wepi_goal']:''}}">
                            </div>
                        </div>
                        <div class="form-group col-md-12 pdn-goal">
                            <label for="client_match_goal" class="col-sm-3 col-form-label">Client Match</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="client_match_goal" name="client_match_goal"
                                    value="{{($mode == 'edit')?$data['client_match_goal']:''}}">
                            </div>
                        </div>
                        <div class="form-group col-md-12 pdn-goal">
                            <label for="client_match_hours_goal" class="col-sm-3 col-form-label">Client Match Hours</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="client_match_hours_goal" name="client_match_hours_goal"
                                    value="{{($mode == 'edit')?$data['client_match_hours_goal']:''}}">
                            </div>
                        </div>
                        <div class="form-group col-md-12 pdn-goal">
                            <label for="candidate_start_goal" class="col-sm-3 col-form-label">Candidate Start</label>
                            <div class="col-sm-9">
                                <input type="number" class="form-control" id="candidate_start_goal" name="candidate_start_goal"
                                    value="{{($mode == 'edit')?$data['candidate_start_goal']:''}}">
                            </div>
                        </div>
                        <div class="form-group col-md-12">
                            <label for="candidate_weekly_start_goal" class="col-sm-3 col-form-label">Candidate Weekly Start Goal</label>
                            <div class="col-sm-9">
                                <input type="number" min="0" class="form-control" id="candidate_weekly_start_goal" name="candidate_weekly_start_goal"
                                    value="{{ ($mode == 'edit') ? Arr::get($data, 'candidate_weekly_start_goal', '') : '' }}">
                            </div>
                        </div>
                    </div>
                </div>
                <?php if(LD::checkFeatureFlag('add-scorecard-goals-to-whiteboard-edit')) { ?>
                <div class="form-group col-md-12 pdn-goal">
                    <label for="scorecard_goals" class="col-sm-2 col-form-label">Scorecard Goals</label>
                    <div class="col-md-9">
                            <div class="form-group col-md-12">
                                <label for="ipea_coversion_per" class="col-sm-3 col-form-label">IPEA Coversion %</label>
                                <div class="col-sm-9">
                                    <input type="number" class="form-control" id="ipea_coversion_per" name="ipea_coversion_per"
                                        value="{{($mode == 'edit')?$data['ipea_coversion_per']:''}}">
                                </div>
                            </div>
                    </div>
                </div>
                <?php } ?>
            </div>
            <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
        </form>
    </div>
</div>

<script type="text/javascript">
$(function() {
    $("#add-form-validate").validate({
        rules: {
            user_name: "required",
            row_id: "required",
            territory: "required",
        },
        messages: {
            user_name: "Please select recruiter",
            row_id: "Please enter service",
            territory: "Please enter territory"
        },
        errorPlacement: function() {
            return true;
        },
    });

    $('#hire_date').datepicker();

    $('#user_name').change(function() {
        $('#user_id').val($(this).find(':selected').data('id'));
    });

    function applyType(isTherapy) {
        $('#is_therapy').val(isTherapy ? '1' : '0');

        if (isTherapy) {
            $('#region-pdn-group').hide();
            $('#region-pdn-group select').prop('disabled', true);
            $('#region-therapy-group').show();
            $('#region-therapy-group select').prop('disabled', false);
            $('.pdn-goal').hide();
        } else {
            $('#region-therapy-group').hide();
            $('#region-therapy-group select').prop('disabled', true);
            $('#region-pdn-group').show();
            $('#region-pdn-group select').prop('disabled', false);
            $('.pdn-goal').show();
        }
    }

    $('#wb_type').change(function() {
        applyType($(this).val() === '1');
    });

    // init
    applyType({{ $isTherapy ? 'true' : 'false' }});
});
</script>
<!-- /.container-fluid -->
@endsection
