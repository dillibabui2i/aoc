<?php

use App\Helpers\LD;

/**
 * @see resources/views/candidates/partials/titles.blade.php
 */
?>

@if(LD::checkFeatureFlag('change-att-to-aide-in-add-clinician-form'))
    @foreach(config('constant')['newSkills'] as $k => $v)
        <option value="{{$v}}">{{$v}}</option>
    @endforeach
@else
    @foreach(config('constant')['skills'] as $k => $v)
        <option value="{{$v}}">{{$v}}</option>
    @endforeach
@endif
