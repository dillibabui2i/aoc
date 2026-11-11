<?php
    $options = ['','Yes','No'];
?>

<section class="tab-content-preferences">
    <div class="main-content pt-2 pb-2">
        <div class="tab-content-area col-md-12">
            <div class="form-row">
                <div class="form-group col-md-12 pb-0 mb-0">
                    <label
                        for="callrecap_lastcall"
                        class="col-md-1 p-0 m-0 text-left"
                    >Last call Recap </label>
                    <textarea readonly class="form-control" name="callrecap[lastcall]" id="callrecap_lastcall"></textarea>
                </div>
            </div>

            <div class="form-row  pt-2">
                <div class="form-group col-md-12 pb-0 mb-0">
                    <label
                        for="callrecap_thiscall"
                        class="col-md-1 p-0 m-0 text-left"
                    >Recap of this call </label>
                    <textarea class="form-control" name="callrecap[thiscall]" id="callrecap_thiscall"></textarea>
                </div>
            </div>
        </div>
    </div>
</section>
