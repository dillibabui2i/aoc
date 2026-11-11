<?php

use App\Helpers\H;
use App\Helpers\Candidates;
use App\Helpers\LD;
use Illuminate\Support\Str;
use App\Transformer\Dashboard\Buckets\Jobvite\NursesApplicationsTransformer;

/**
 * @see resources/views/candidates/partials/outcome.blade.php
 */

if ($bucket == '') {
    $outcomes = [];
} else {
    $jobviteBuckets = config('constant')['jobviteTherapyOutcomes'];
    if (!array_key_exists($bucket, $jobviteBuckets)) {
        $bucket = 'New';
    }
    $outcomes = config('constant')['jobviteTherapyOutcomes'][$bucket];
}

if ($isTherapyClinicalManager) {
    if ($bucket === 'Clinical Interview') {
        unset($outcomes[0]);
    }

    if ($bucket === 'Offer Extended') {
        unset($outcomes[1]);
        unset($outcomes[2]);
        unset($outcomes[3]);
        unset($outcomes[4]);
    }
}
?>

<select id="jobViteOutcome" name="jobViteOutcome" class="form-control" {{ $isTherapyClinicalManager && $bucket === 'Offer Extended' ? 'disabled' : '' }} autocomplete="off">
    <option value="">-- Select One --</option>
    @foreach ($outcomes as $item)
        <option data-therapy="true"
                data-bucket-key={{ str_replace('-', "_", Str::slug($item)) }} {{ isset($defaultOutcome) && $defaultOutcome == $item ? "selected='selected'" : "" }} value="{{ $item }}">
            {{ $item }}
        </option>
    @endforeach
</select>


@if($isTherapyClinicalManager && $bucket === 'Offer Extended')
    <script>
        setTimeout(function () {
            $('#jobViteOutcome').val("{{$bucket}}").trigger('change');
        }, 200)
    </script>
@endif
