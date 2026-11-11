<?php
    $options = [
        '',
        'Yes',
        'No'
    ];
?>
<section class="tab-content-preferences">
    <div class="main-content">
        <div class="tab-content-area m-2">
            <p><b>The current insurances providers we have at this time are...</b></p>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label
                        for="insurance_pimary"
                        class="col-md-4 p-0 m-0 text-left"
                    >Primary:</label>
                    <span>{{$primaryInsurance[0]->name??''}}</span>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-6">
                    <label
                        for="insurance_secondary"
                        class="col-md-4 p-0 m-0 text-left"
                    >Secondary:</label>
                    <span>{{$secondaryInsurance[0]->name??''}}</span>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group col-md-4">
                    <label
                        for="insurance_provider_correct"
                        class="col-md-6 p-0 m-0 text-left"
                    >Are these still correct</label>
                    <select
                        class="form-control"
                        id="insurance_provider_correct"
                        name="insurance[provider_correct]">
                        @foreach ($options as $option)
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>
</section>