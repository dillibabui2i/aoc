<?php
use App\Helpers\H;
$options = ['','Yes','No'];
$weeklyFrequency = config("constant.clientCallFrequency");
?>

<section class="tab-content-journalnotes">
    <div class="main-content">
        <div class="tab-content-area col-md-12 p-0">



            <div class="form-row mt-2 mb-2 p-4 border-bottom">
                

                <div class="form-group col-md-8 m-0 p-0">
                    <label
                        for="journalnotes_confirmation"
                        class="col-md-8 p-0 m-0 text-left"
                    >I see the following journal notes . Is there anything I can add or assist with further  ?</label>
                    <select
                        class="form-control"
                        id="journalnotes_confirmation"
                        name="journalnotes[confirmation]">
                        @foreach ($options as $option) 
                            <option value="{{ $option }}">{{ $option }}</option>
                        @endforeach
                    </select>
                </div>
            </div>



            @foreach ($journalNotes as  $note)
            <div class="form-row border-top p-3">
                <div class="form-group col-md-11 pb-0 mb-0">
                    <?php echo $note->notes?>
                </div>
                <div class="form-group col-md-1 pb-0 mb-0">
                    {{H::getFormatedDate($note->added_date)}}
                </div>
            </div>
            @endforeach

        </div>
    </div>
</section>

<style>
    .tab-content-journalnotes .main-content {
        height: 300px;
        overflow: auto;
    }
</style>