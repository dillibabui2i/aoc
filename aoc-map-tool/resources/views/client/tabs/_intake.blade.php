<?php
use app\Helpers\H;
?>
@if(!empty($intakeData))
<style>
    .box {
        border: 1px #000;
        padding: 10px;
        border-radius: 5px;
    }
</style>
<section class="intake-section">
	<div class="row" id="intakechildtab">
		@include('client.tabs._intakenew')
	</div>
</section>
@endif



