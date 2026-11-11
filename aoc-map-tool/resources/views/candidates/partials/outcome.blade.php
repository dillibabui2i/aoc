<?php
use App\Helpers\H;
use App\Helpers\Candidates;
use App\Helpers\LD;
use Illuminate\Support\Str;
use App\Transformer\Dashboard\Buckets\Jobvite\NursesApplicationsTransformer;

$isNursingOutcome = str_contains($type, 'nurs')
/**
 * @see resources/views/candidates/nextstep.blade.php
 */
?>

@if($isNursingOutcome)
    @include('candidates.partials.nursing-outcome', ['bucket' => $bucket, 'isTherapyClinicalManager' => $isTherapyClinicalManager])
@else
    @include('candidates.partials.therapist-outcome', ['bucket' => $bucket, 'isTherapyClinicalManager' => $isTherapyClinicalManager])
@endif
