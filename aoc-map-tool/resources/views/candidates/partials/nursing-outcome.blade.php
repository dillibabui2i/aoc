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
    if (LD::checkFeatureFlag('jobvite-add-action-item-on-show-all-page')) {
        $jobviteBuckets = config('constant')['jobViteOutcomes'];
        if (!array_key_exists($bucket, $jobviteBuckets)) {
            $bucket = 'New';
        }
    }
    $outcomes = config('constant')['jobViteOutcomes'][$bucket];
    if (LD::checkFeatureFlag('add-reschedule-recruiter-phone-screen-in-jobvite')) {
        if ($bucket == 'Recruiter Phone Screen') {
            $outcomes = config('constant')['jobViteOutcomes']['Recruiter Phone Screen New'];
        }
    }
    if (LD::checkFeatureFlag('add-do-not-contact-in-jobvite')) {
        $outcomes['Do Not Contact/Do Not Hire'] = 'Do Not Contact/Do Not Hire';
    }
    if (LD::checkFeatureFlag('jobvite-outcome-reschedule-follow-up')) {
        $outcomes['reschedule_follow_up'] = 'Reschedule Follow Up';
    }
}
?>

<select id="jobViteOutcome" name="jobViteOutcome" class="form-control" autocomplete="off">
    <option value="">-- Select One --</option>
    @foreach ($outcomes as $item)
        <option data-bucket-key={{str_replace('-',"_",Str::slug($item))}} {{isset($defaultOutcome) && $defaultOutcome == $item ? "selected='selected'" : ""}} value="{{  $item }}">
            {{ $item }}
        </option>
    @endforeach
</select>
