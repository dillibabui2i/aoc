<x-modal>
    <x-slot name="id">closeSocialMediaAds</x-slot>
    <x-slot name="size">w50</x-slot>
    <x-slot name="title">Close Ads</x-slot>
    <x-slot name="extraHeader">
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="form-row">
            <div class="form-group col-md-12">
                <label for="name" class="text-left">Reason for Closing: </label>
                <select name="ads_close_reason" id="ads_close_reason" class="form-control">
                    <?php
                        $reason = Config::get('constant.socialMediaCloseReason');
                        foreach($reason as $item):
                    ?>
                        <option value="{{$item}}">{{$item}}</option>
                    <?php
                        endforeach;
                    ?>
                </select>
            </div>
            <div class="form-group col-md-12">
                <label for="name" class="text-left">Move Remaining Budget: </label>
                <select name="move_remaining_budget" id="move_remaining_budget" multiple data-placeholder="Select Ads" data-live-search="true" >
                    <?php
                        foreach($socialMedia as $item):
                    ?>
                        <option value="{{$item->sid}}">{{$item->sname}}</option>
                    <?php
                        endforeach;
                    ?>
                </select>                    
            </div>
        </div>
    </x-slot>
    <x-slot name="footer">
        <button type="button" class="btn btn-primary" onclick="closeSocialMediaAds('{{$socialMediaId}}')" data-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" data-dismiss="modal">Cancel</button>
    </x-slot>
</x-modal>
