<?php
    use App\Helpers\H;
    use App\Helpers\Ld;
    $categoryCount = count($customFields);
    $catCount = 1;
    if(LD::checkFeatureFlag('city-state-custom-fields-on-clinician-profile')) {
        $cityState = trim(($clinicianDetails->aoc_cf_city ?? '') . (isset($clinicianDetails->aoc_cf_city, $clinicianDetails->aoc_cf_state) ? ', ' : '') . ($clinicianDetails->aoc_cf_state ?? ''));
    }
?>
{{--<link rel="stylesheet" href="{{ mix('css/bootstrap-editable.css') }}?v={{ filemtime(public_path('css/bootstrap-editable.css')) }}">--}}
{{--<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">--}}
<link rel="stylesheet" href="{{ mix('css/bootstrap-editable.css') }}">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>

<style>
     .multiselect-container {
        left: -60px !important;
    }
</style>

@foreach($customFields as $category => $fields)
    @if ($category != 'Default')
        <div class="row ml-0"><h3>{{$category}}</h3></div>
    @endif
    @if(LD::checkFeatureFlag('city-state-custom-fields-on-clinician-profile'))
    <div class="row mb-2">
        <div class="col-md-6 d-flex align-items-center">
            <label for="cityInput" class="form-label mb-0 me-2" style="font-weight:bold;">City&nbsp;:&nbsp;</label>
            <!-- Display Mode (Visible Initially) -->
            <div id="cityDisplayRow" class="w-100">
                <div class="d-flex align-items-center" style="min-height: 38px;">
                    <span id="cityText">{{ $cityState }}</span>
                    <a href="javascript:void(0)" id="editCity" class="ms-2 text-primary">
                        &nbsp;&nbsp;<i class="fa fa-pencil" aria-hidden="true"></i>
                    </a>
                </div>
            </div>
            <!-- Edit Mode (Hidden Initially) -->
            <div id="cityEditRow" class="w-100" style="display: none;">
                <div class="d-flex align-items-center"  style="min-height: 38px;">
                    <input type="text" class="form-control form-control-sm me-2" id="cityInput" name="city" style="width: 180px;" value="">
                    <a href="javascript:void(0)" id="saveCity" class="btn btn-primary btn-sm me-1">
                        <i class="fa fa-check" aria-hidden="true"></i>
                    </a>
                    <a href="javascript:void(0)" id="cancelEdit" class="btn btn-light btn-sm border">
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </a>
                </div>
                <div id="cityError" class="text-danger small mt-1" style="display: none;"></div>
            </div>
        </div>
    </div>
    @endif
    <div class="row {{($catCount != $categoryCount) ? 'border-bottom mb-3' : ''}}">
        @foreach($fields as $key => $field)
            @if (!empty($field))
                <div class="col-md-6 pt-1 pb-1">
                    {!! $field !!}
                </div>
            @endif
        @endforeach
        @php
            $catCount++;
        @endphp
    </div>
@endforeach
<script type="text/javascript">
    $(document).ready(function() {
        @if(!H::canPermissions('editCustomFieldValue'))
            $('#customFields .editable').editable('disable');
        @endif
    });

</script>
@if(LD::checkFeatureFlag('city-state-custom-fields-on-clinician-profile'))
<script>
$(document).ready(function() {
    var cityFlag = true;
    $("#cityInput").autocomplete({
        source: function(request, response) {
            $.getJSON("{{ route('nthlanding.getGeoCities') }}", {
                term: $('#cityInput').val()
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
    $('#cityInput').keyup(function() {
        cityFlag = false;
        if ($(this).val() != '') {
            cityFlag = true;
        }
    });
    $('#cityInput').blur(function() {
        if (cityFlag == false) {
            $(this).val('');
        }
    });

    $('#editCity').click(function() {
        $('#cityInput').val($('#cityText').text());
        $('#cityDisplayRow').hide();
        $('#cityEditRow').show();
        $('#cityError').hide();
    });

    $('#cancelEdit').click(function() {
        $('#cityEditRow').hide();
        $('#cityDisplayRow').show();
        $('#cityError').hide();
    });

    $('#saveCity').click(function() {
        var city = $('#cityInput').val().trim();

        if (city === '') {
            // Show error if input is empty
            $('#cityError').text('City name is required.').show();
            return;
        } else {
            $.ajax({
                url: "{{ route('clinician.saveClinicianCustomCity')}}",
                method: 'POST',
                data: {
                    _token: "{{ csrf_token() }}",
                    city: city,
                    ClinicianID: '{{  $clinicianDetails->ClinicianID }}'
                },
                success: function(response) {
                    $('#cityText').text(response.city);
                    $('#cityEditRow').hide();
                    $('#cityDisplayRow').show();
                },
                error: function(xhr) {
                    alert("Error saving city");
                }
            });
        }
    });
});
</script>
@endif
