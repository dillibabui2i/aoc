@extends('layouts.nthLandingLayout')
@section('title', 'NTH - Available Cases')
@section('content')
<?php
$schedule = [
    ['value' => 'Weekday Days (AM-PM)', 'text' => 'Weekday Days (AM-PM)'],
    ['value' => 'Weekday Evenings (PM-PM)', 'text' => 'Weekday Evenings (PM-PM)'],
    ['value' => 'Weekday Nights (PM-AM)', 'text' => 'Weekday Nights (PM-AM)'],
    ['value' => 'Weekend Days (AM-PM)', 'text' => 'Weekend Days (AM-PM)'],
    ['value' => 'Weekend Evenings (PM-PM)', 'text' => 'Weekend Evenings (PM-PM)'],
    ['value' => 'Weekend Nights (PM-AM)', 'text' => 'Weekend Nights (PM-AM)']
];
$cityState = trim(($clinicianData->aoc_cf_city ?? '') . (isset($clinicianData->aoc_cf_city, $clinicianData->aoc_cf_state) ? ', ' : '') . ($clinicianData->aoc_cf_state ?? ''));
?>
<div class="container mt-5">
    <div class="text-center mb-4 row name">
      <h2>Welcome, {{$clinicianData->FirstName}}!</h2>
      
    </div>
    <div class="text-left mb-4 row name">
        Find and apply for cases that match your preferences and skills.
    </div>
	<div class="main-form">
    <div class="text-center mb-4">
      <h2>Find Available Cases</h2>
      <p class="text-muted">Begin viewing available cases by filling out your case preferences.</p>
    </div>
    @if ($errors->has('form_error'))
    <div class="alert alert-danger">
        {{ $errors->first('form_error') }}
    </div>
    @endif
    <form method="post" action="{{ route('nthlanding.updateClinicianPreferences') }}" autocomplete="off" id="available_cases_form" class="scaffold-form" enctype="multipart/form-data">
      @csrf
      <div class="row g-3">
        <div class="col-md-4">
			<label>Your Location</label>
          <input type="text" class="form-control" placeholder="City, State" name="city" id="city" value="{{ old('city', $cityState ?? '') }}">
			<div class="desc">City and state where you want to find cases.</div>
          @error('city')
                <div class="old-error">{{ $message }}</div>
          @enderror
        </div>
        <div class="col-md-4">
            <div class="desktop-element">
				 <label>Search Radius (miles)</label>
                <select name="mile_radius" class="form-control desktop-control" id="mile_radius">
                <?php for($i=15;$i<=75;$i+=15) { ?>
                    <option value="<?php echo $i?>" <?php if($clinicianData->aoc_cf_mile_radius==$i) { ?> selected="selected" <?php }?> ><?php echo $i?> Miles</option>
                <?php } ?>
            </select>
				<div class="desc">How far are you willing to travel?</div>
                @error('mile_radius')
                        <div class="old-error">{{ $message }}</div>
                @enderror
          </div>

          <div class="mobile-element">
			<label>Search Radius (miles)</label>
            <select name="mile_radius" class="form-control mobile-control" id="mile_radius">
                <?php for($i=15;$i<=75;$i+=15) { ?>
                    <option value="<?php echo $i?>" <?php if($clinicianData->aoc_cf_mile_radius==$i) { ?> selected="selected" <?php }?> ><?php echo $i?> Miles</option>
                <?php } ?>
            </select>
           <div class="desc">How far are you willing to travel?</div>
            </div>

        </div>

        <div class="col-md-4">
            <div class="">
				<label>Preferred Schedule</label>
                <select multiple class="selectpicker " data-placeholder="Select Schedule" data-live-search="true" name="schedule[]" id="schedule">
                    @foreach($schedule as $option)
                    <option value="{{$option['value']}}" {{ in_array($option['value'], old('schedule', explode(',', $clinicianData->aoc_cf_schedule ?? ''))) ? 'selected' : '' }}>{{$option['text']}}</option>
                    @endforeach
                </select>
				<div class="desc">Days or times you are willing to work.</div>
            </div>
        </div>
      </div>

      <div class="text-center mt-4">
        <div id="cover-spin"></div>
        <button type="submit" class="btn btn-primary px-4">Find Cases</button>
      </div>
    </form>
</div>
  </div>
<script>
$(document).ready(function() {
    $('.selectpicker').multiselect({
        includeSelectAllOption: true,
    });
    var cityFlag = true;
    $("#city").autocomplete({
        source: function(request, response) {
            $.getJSON("{{ route('nthlanding.getGeoCities') }}", {
                term: $('#city').val()
            }, function(data) {
                if (data.length === 0) {
                    cityFlag = false;
                } else {
                    cityFlag = true;
                }
                response(data);
            });
        },
        minLength: 2,
        select: function(event, ui) {
            cityFlag = true;
        },
        change: function(event, ui) {
            if (!ui.item) {
                cityFlag = false;
            }
        }
    });
    $('#city').keyup(function() {
        cityFlag = false;
        if ($(this).val() != '') {
            cityFlag = true;
        }
    });
    $('#city').blur(function() {
        if (cityFlag == false) {
            $(this).val('');
        }
    });
});
$(function() {
        $("#available_cases_form").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
               "mile_radius": {
                    required: true
                },
                "city": {
                    required: true
                }
            },
            errorPlacement: function(error, element) {}
        });
});
</script>

@endsection
