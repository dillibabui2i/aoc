<?php
use App\Helpers\H;
use App\Helpers\LD;
$loggedInUser = H::getLoggedInUser();
?>
<section class="tab-content-introduction">
    <div class="main-content">
        <div class="tab-content-area m-2">
            
            <div class="form-row">
                <div class="form-group col-md-6 pb-0 mb-0">
                    <label
                        for="intro_guardian"
                        class="col-md-6 p-0 m-0 text-left"
                    >Hello, this is {{  $loggedInUser['name']}} may I speak with... </label>
                    <select
                        class="form-control"
                        id="intro_guardian"
                        name="intro[guardian]">
                        @foreach ($emergencyContact as $emergencyContact)
                            <option value="{{ $emergencyContact->FullName }}">{{ $emergencyContact->FullName }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12 pb-0 mb-0">
                    <label class="col-md-12 p-0 m-0 text-left">I am calling to check in, provide updates, and make any changes to information that is needed. Can you please verify the clients date of birth and address?</label>
                </div>
            </div>
        </div>
    </div>
</section>