<?php
    $options = ['','Yes','No'];

    $weeklyFrequency = config("constant.nthCallFrequency");
    $monthlyFrequency = config("constant.clientCallFrequency");

    $currentWeeklyFrequency = $clientDetails->ccc_nth_call_frequency;
    $currentMonthlyFrequency = $clientDetails->ccc_client_call_frequency;
    
?>

<section class="tab-content-preferences">
    <div class="main-content">
        <div class="tab-content-area col-md-12 pt-3 pb-3">

            <div class="form-row">
                <div class="form-group col-md-12 pb-0 mb-0">
                    <label
                        for="clientsatisfaction_weekly_frequency"
                        class="col-md-4 p-0 m-0 text-left"
                    >Parent request frequency of the NTH weekly call to be adjusted to?</label>
                    <select
                        class="form-control col-md-2"
                        id="clientsatisfaction_weekly_frequency"
                        name="clientsatisfaction[weekly_frequency]">
                        <option value=""></option>
                        @foreach ($weeklyFrequency as $key=> $freq)
                            <option value="{{$key}}" <?php if($key==$currentWeeklyFrequency) { ?> selected="selected" <?php } ?>>{{$freq}}</option>
                        @endforeach
                    </select>
                    
                </div>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12 pb-0 mb-0">
                    <label
                        for="clientsatisfaction_monthly_frequency"
                        class="col-md-4 p-0 m-0 text-left"
                    >Parent request frequency of the monthly call to be adjusted to?</label>
                    <select
                        class="form-control col-md-2"
                        id="clientsatisfaction_monthly_frequency"
                        name="clientsatisfaction[monthly_frequency]">
                        <option value=""></option>
                        @foreach ($monthlyFrequency as $key=> $freq)
                            <option value="{{$key}}" <?php if($key==$currentMonthlyFrequency) { ?> selected="selected" <?php } ?>>{{$freq}}</option>
                        @endforeach
                    </select>
                    
                </div>
            </div>

            <div class="form-row">
                <label
                        for="clientsatisfaction_weekly_frequency"
                        class="col-md-6 p-0 m-0 text-left"
                        style="color: red;"
                    >*Manager must approve before frequency changes</label>
            </div>

            <div class="form-row">
                <div class="form-group col-md-12 pb-0 mb-0">
                    <label class="col-md-4 p-0 m-0 text-left">On a scale of 1 to 10, how would you rate your experience at Angels of Care?</label>
                    <div class="weeklysatisfaction-rating">
                        <input type="radio" name="rate" id="rate2-10" value="10"><label for="rate2-10">10</label>
                        <input type="radio" name="rate" id="rate2-9" value="9"><label for="rate2-9">9</label>
                        <input type="radio" name="rate" id="rate2-8" value="8"><label for="rate2-8">8</label>
                        <input type="radio" name="rate" id="rate2-7" value="7"><label for="rate2-7">7</label>
                        <input type="radio" name="rate" id="rate2-6" value="6"><label for="rate2-6">6</label>
                        <input type="radio" name="rate" id="rate2-5" value="5"><label for="rate2-5">5</label>
                        <input type="radio" name="rate" id="rate2-4" value="4"><label for="rate2-4">4</label>
                        <input type="radio" name="rate" id="rate2-3" value="3"><label for="rate2-3">3</label>
                        <input type="radio" name="rate" id="rate2-2" value="2"><label for="rate2-2">2</label>
                        <input type="radio" name="rate" id="rate2-1" value="1"><label for="rate2-1">1</label>
                        <input type="radio" name="rate" id="rate2-0" value="0"><label for="rate2-0">0</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
