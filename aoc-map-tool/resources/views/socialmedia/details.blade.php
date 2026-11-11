<?php
    use App\Helpers\H;
    use App\Helpers\LD;
    $states = H::getHelper("ConstantHelper")->getDashboardState();
?>

<link href="{{ mix('css/bootstrap-editable.css') }}" rel="stylesheet">
<script src="{{ mix('js/bootstrap-editable.min.js') }}"></script>
<script src="{{ mix('js/editable.js') }}"></script>
<style>
    .editable-pre-wrapped {
        white-space: inherit;
    }
    .tracker-details .textarea-size {
        width: auto !important;
    }
</style>
<x-modal>
    <x-slot name="id">socialMediaDetails</x-slot>
    <x-slot name="size">w80</x-slot>
    <x-slot name="title">Ad Details \ {{$socialMedia->ad_name}}</x-slot>
    <x-slot name="extraHeader">
    <div class="col-md-6 text-left pull-left">
        <a href="javascript:void(0);" onclick="markAsCompletedAds('{{$socialMedia->id}}')" >
            <i class="fa fa-check-square-o"></i>
        </a>
    </div>
    </x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div class="row d-flex">
            <div class="col-md-12 tracker-details">
                <div class="col-md-12 d-flex p-0">
                    <div class="col-md-6 p-0">
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Ad Name: </dt>
                            <dd class="col-sm-8 text-left">
                                <span data-mode='inline' class="editable" data-emptytext='Click to add Ad Name' data-type='text' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='ad_name' data-value="{{ $socialMedia->ad_name}}" data-table='socialMedia'>
                                    {{$socialMedia->ad_name}}
                                </span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Discipline: </dt>
                            <dd class="col-sm-8 text-left">
                                <span data-mode='inline' class="editable" data-emptytext='Click to add Discipline' data-type='text' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='discipline' data-value="{{ $socialMedia->discipline}}" data-table='socialMedia'>
                                    {{$socialMedia->discipline}}
                                </span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Start Date: </dt>
                            <dd class="col-sm-8 text-left">
                                <span class="editable" data-format="mm/dd/yyyy" data-emptytext='Click to add Start Date' data-type='date' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='start_date' data-value="{{ $socialMedia->start_date}}" data-table='socialMedia'>
                                    {{H::getFormatedDate($socialMedia->start_date)}}
                                </span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Skills Diagnosis: </dt>
                            <dd class="col-sm-8 text-left">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <span class="att-value editable-textarea" data-inputclass="textarea-size" data-emptytext='Click to add skills diagnosis' data-type='textarea' data-url='{{route("editable")}}' data-pk="{{$socialMedia->id }}" data-name='skills_diagnosis' data-value="{{$socialMedia->skills_diagnosis }}" data-table='socialMedia'>
                                            {{$socialMedia->skills_diagnosis}}
                                        </span>
                                    </div>
                                </div>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Sales: </dt>
                            <dd class="col-sm-8 text-left">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <span class="att-value editable-textarea" data-inputclass="textarea-size" data-emptytext='Click to add sales note' data-type='textarea' data-url='{{route("editable")}}' data-pk="{{$socialMedia->id }}" data-name='the_sale' data-value="{{$socialMedia->the_sale }}" data-table='socialMedia'>
                                            {{$socialMedia->the_sale}}
                                        </span>
                                    </div>
                                </div>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Post Engagement: </dt>
                            <dd class="col-sm-8 text-left">
                                <span data-mode='inline' class="att-value editable-text" data-emptytext='Click to add post engagement' data-type='number' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='post_engagement' data-value="{{ $socialMedia->post_engagement }}" data-table='socialMedia'>{{$socialMedia->post_engagement}}</span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Link Clicks: </dt>
                            <dd class="col-sm-8 text-left">
                                <span data-mode='inline' class="att-value editable-text" data-emptytext='Click to add Link Clicks' data-type='number' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='link_clicks' data-value="{{ $socialMedia->link_clicks }}" data-table='socialMedia'>{{$socialMedia->link_clicks}}</span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Cost Per Link: </dt>
                            <dd class="col-sm-8 text-left">
                                <span data-mode='inline' class="editable" data-emptytext='Click to add cost per link' data-type='text' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='cost_per_link' data-value="{{ $socialMedia->cost_per_link}}" data-table='socialMedia'>
                                    {{$socialMedia->cost_per_link}}
                                </span>
                            </dd>
                        </dl>
                    </div>
                    <div class="col-md-6 p-0">
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">State: </dt>
                            <dd class="col-sm-8 text-left">
                                <span data-mode='inline' class='editable-select' data-emptytext='Click to add state'
                                        data-type='select'
                                        data-source='<?php echo json_encode($states);?>'
                                        data-value='{{$socialMedia->state}}'
                                        data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}"
                                        data-name='state' data-table='socialMedia'>
                                        {{$socialMedia->state}}
                                </span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">City: </dt>
                            <dd class="col-sm-8 text-left">
                                <span data-mode='inline' class="editable" data-emptytext='Click to add city' data-type='text' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='city' data-value="{{ $socialMedia->city}}" data-table='socialMedia'>
                                    {{$socialMedia->city}}
                                </span>
                            </dd>
                        </dl>
                        <?php if(LD::checkFeatureFlag('social-media-dashboard-report')) { ?>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Category: </dt>
                            <dd class="col-sm-8 text-left">
                                <span data-mode='inline' class='editable-select' data-emptytext='Click to add category'
                                        data-type='select'
                                        data-source='<?php echo json_encode(config('constant')['adsCategory']);?>'
                                        data-value='{{$socialMedia->category}}'
                                        data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}"
                                        data-name='category' data-table='socialMedia'>
                                        {{$socialMedia->category}}
                                </span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Type: </dt>
                            <dd class="col-sm-8 text-left">
                                <span data-mode='inline' class='editable-select' data-emptytext='Click to add Type'
                                        data-type='select'
                                        data-source='<?php echo json_encode(config('constant')['adsType']);?>'
                                        data-value='{{$socialMedia->ad_type}}'
                                        data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}"
                                        data-name='ad_type' data-table='socialMedia'>
                                        {{$socialMedia->ad_type}}
                                </span>
                            </dd>
                        </dl>
                        <?php } ?>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Budget: </dt>
                            <dd class="col-sm-8 text-left">
                            $<span data-mode='inline' class="att-value editable-text" data-inputclass="float" data-emptytext='Click to add budget' data-type='text' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='budget' data-table='socialMedia'>
                                {{number_format((float)$socialMedia->budget, 2, '.', '')}}</span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Shifts Needed: </dt>
                            <dd class="col-sm-8 text-left">
                                <div class="card">
                                    <div class="card-body p-3">
                                        <span class="att-value editable-textarea" data-inputclass="textarea-size" data-emptytext='Click to add shifts needed' data-type='textarea' data-url='{{route("editable")}}' data-pk="{{$socialMedia->id }}" data-name='shifts_needed' data-value="{{$socialMedia->shifts_needed }}" data-table='socialMedia'>
                                            {{$socialMedia->shifts_needed}}
                                        </span>
                                    </div>
                                </div>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Actual Spend: </dt>
                            <dd class="col-sm-8 text-left">
                                <span class="editable" data-emptytext='Click to add actual spend' data-type='text' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='actual_spend' data-mode='inline' data-value="{{ $socialMedia->actual_spend}}" data-table='socialMedia'>
                                    {{$socialMedia->actual_spend}}
                                </span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right"> Messages Received: </dt>
                            <dd class="col-sm-8 text-left">
                                <span class="att-value editable-text" data-mode='inline' data-emptytext='Click to add message received' data-type='number' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='messages_received' data-value="{{ $socialMedia->messages_received }}" data-table='socialMedia'>{{$socialMedia->messages_received}}</span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Reach: </dt>
                            <dd class="col-sm-8 text-left">
                                <span class="att-value editable-text" data-mode='inline' data-emptytext='Click to add reach' data-type='number' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='reach' data-value="{{ $socialMedia->reach }}" data-table='socialMedia'>{{$socialMedia->reach}}</span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Cost Per Message: </dt>
                            <dd class="col-sm-8 text-left">
                                <span class="editable" data-mode='inline' data-emptytext='Click to add cost per message' data-type='text' data-url='{{route("editable")}}' data-pk="{{ $socialMedia->id }}" data-name='cost_per_message' data-value="{{ $socialMedia->cost_per_message}}" data-table='socialMedia'>
                                    {{$socialMedia->cost_per_message}}
                                </span>
                            </dd>
                        </dl>
                        <dl class="col-md-12 pb-3 d-flex">
                            <dt class="col-sm-4 p-0 text-right">Close: </dt>
                            <dd class="col-sm-8 text-left">
                                <input type="checkbox" class="completedSocialMediaAds" onclick="getSocialMediaClosePopup('{{$socialMedia->id}}'); this.disabled = true;"/>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>


    </x-slot>
    <x-slot name="footer"></x-slot>
</x-modal>
