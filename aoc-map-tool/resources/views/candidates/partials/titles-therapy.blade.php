<?php

use App\Helpers\LD;

/**
 * @see resources/views/candidates/partials/titles.blade.php
 */
?>

@foreach(config('constant')['therapySkills'] as $k => $v)
    <option value="{{$v}}">{{$v}}</option>
@endforeach


