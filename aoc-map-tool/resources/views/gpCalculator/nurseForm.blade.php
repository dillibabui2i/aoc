<?php
    use App\Helpers\H;
    use App\Helpers\LD;
?>
@if(isset($mode) && $mode == 'edit')
@foreach($nurseData as $k => $v)
    @if ($v['isOldNurse'] == $isOldNurse || !empty($loadwholenurse))
    @php
        if(!empty($loadwholenurse)) {
            $k = $k + $formRowCount;
        }
    @endphp
    <div class="nurse-form-inner gp-inner mt-2 pt-3">
        <input
            type="hidden"
            id="isOldNurse{{$clientIndex}}{{$k}}"
            name="isOldNurse[{{$clientIndex}}][]"
            value="{{!empty($isOldNurse) ? 1 : 0}}"/>
        <a
            data-index="{{$k}}"
            data-client-index="{{$clientIndex}}"
            href="javascript:void(0);"
            class='nurse-remove-icon gp ml-2'>
                <i class="fa fa-trash-o"></i>
        </a>
        @if($k <= 0 && empty($loadwholenurse) && $clientIndex <= 0)
            <div class="form-group row m-0">
                <div class="col-md-6 d-flex">
                    <label>Status</label>
                    <div class="d-flex">
                        <input
                            class="form-control"
                            id="new{{$clientIndex}}{{$k}}"
                            type="radio"
                            {{$v["aoc_provider_id"] == 0 ? 'checked="true"' : ''}}
                            name="status[{{$clientIndex}}][]"
                            value="new" />
                        <label for="new">New</label>
                    </div>
                    <div class="d-flex ml-5">
                        <input
                            class="form-control"
                            type="radio"
                            id="current{{$clientIndex}}{{$k}}"
                            name="status[{{$clientIndex}}][]"
                            {{$v["aoc_provider_id"] != 0 ? 'checked="true"' : ''}} 
                            value="current" />
                        <label for="current">Current</label>
                    </div>
                </div>
            </div>
        @endif
        <div class="form-group row m-0">
            <div class="col-md-12 d-flex">
                <label for="nurseId{{$clientIndex}}{{$k}}">Clinician</label>
                <?php
                    $nurseName = $v['nurseName'] ?? '';
                ?>
                <input
                    data-index="{{$k}}"
                    data-client-index="{{$clientIndex}}"
                    type="text"
                    class="form-control nurseName"
                    id="nurseId{{$clientIndex}}{{$k}}"
                    name="nurseId[{{$clientIndex}}][]"
                    value="{{$nurseName}}"
                    {{($mode == 'edit' && $clientIndex > 0) ? 'readonly' : ''}}/>
                <input
                    data-index="{{$k}}"
                    data-client-index="{{$clientIndex}}"
                    type="hidden"
                    id="aoc_clinician_id{{$clientIndex}}{{$k}}"
                    name="aoc_clinician_id[{{$clientIndex}}][]"
                    value="{{($v['aoc_provider_id'] == 0 && $v['aoc_candidate_id'] == 0) ? -1
                        : ($v["aoc_provider_id"] != 0 ?
                        $v["aoc_provider_id"] :
                        $v["aoc_candidate_id"])
                    }}"/>
                {{-- this is the fkn hack until we figure out what to do with same client calculation \w different respite options from the business--}}
                {{-- as of now we have a bug AOC-1874/ If we add one client twice and choose respite and non-respite at the same time we would have wrong calculation--}}
                {{-- as we merge this records into one and first record is overridden with second, and only one saved--}}
                {{-- so we added selectedPayer to the nurse data and loading services using this field for it--}}
                @if (isset($v["selectedPayer"]))
                <input
                    data-index="{{$k}}"
                    data-client-index="{{$clientIndex}}"
                    data-value="{{$v["selectedPayer"]}}"
                    type="hidden"
                    id="selectedPayer{{$clientIndex}}{{$k}}"
                    name="selectedPayer[{{$clientIndex}}][]"
                    value="{{$v["selectedPayer"]}}"/>
                @endif

            </div>
            @if(LD::checkFeatureFlag('replace-auth-type-with-service-gpcalculator'))
                <div class="col-md-12 d-flex">
                    <label for="gpNurseDiscipline{{$clientIndex}}{{$k}}">Service</label>
                    <select
                        name="gpNurseDiscipline[{{$clientIndex}}][]"
                        id="gpNurseDiscipline{{$clientIndex}}{{$k}}"
                        class="form-control"
                        data-index="{{$k}}"
                        data-client-index="{{$clientIndex}}"
                        data-value="{{ $v['discipline'] }}">
                        <option value="">--- Select ---</option>
                    </select>
                </div>
            @else
                <div class="col-md-12 d-flex">
                    <label for="gpNurseDiscipline{{$clientIndex}}{{$k}}">Auth Type</label>
                    <select
                        name="gpNurseDiscipline[{{$clientIndex}}][]"
                        id="gpNurseDiscipline{{$clientIndex}}{{$k}}"
                        class="form-control"
                        data-index="{{$k}}"
                        data-client-index="{{$clientIndex}}">
                        <option value="">--- Select ---</option>
                        @foreach(config('constant')['gpNurseDiscipline'] as $label => $val)
                        <option {{$v['discipline'] == $val ? 'selected="selected"' : ''}} value="{{$val}}">{{$label}}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>
        <div class="form-group row m-0">
            <div class="col-md-12 d-flex p-0">
                <div class="col-md-6 d-flex">
                    <label class="fix-label" for="hours{{$clientIndex}}{{$k}}">Hours</label>
                    <input
                        type="number"
                        class="form-control bgform-control"
                        id="hours{{$clientIndex}}{{$k}}"
                        name="hours[{{$clientIndex}}][]"
                        value="{{number_format((float)$v['hours']??0, 1)}}"
                        pattern="^\d*(\.\d{0,1})?$"
                        data-index="{{$k}}"
                        data-client-index="{{$clientIndex}}"/>
                </div>
                <div class="col-md-6 d-flex">
                    <label class="fix-label" for="desiredRate{{$clientIndex}}{{$k}}">Desired Rate</label>
                    <input
                        type="number"
                        class="form-control bgform-control"
                        id="desiredRate{{$clientIndex}}{{$k}}"
                        name="desiredRate[{{$clientIndex}}][]"
                        value="{{$v['desiredRate']}}"
                        data-index="{{$k}}"
                        data-client-index="{{$clientIndex}}"/>
                </div>
            </div>
            <div class="col-md-12 d-flex p-0">
                <div class="col-md-6 d-flex">
                    <label class="fix-label" for="prevHours{{$clientIndex}}{{$k}}">Hours Already Scheduled</label>
                    <input
                        type="number"
                        class="form-control bgform-control"
                        id="prevHours{{$clientIndex}}{{$k}}"
                        name="prevHours[{{$clientIndex}}][]"
                        value="{{
                            isset($v['preHours']) ?
                            $v['preHours']??0 :
                            0
                        }}"
                        data-index="{{$k}}"
                        data-client-index="{{$clientIndex}}"/>
                </div>
                <div class="col-md-6 d-flex">
                    <label class="fix-label">GP: </label>
                    <div class="nurseGP" id="nurseGP{{$clientIndex}}{{$k}}"></div>
                </div>
            </div>
        </div>
    </div>
    @endif
@endforeach
@else
<div class="nurse-form-inner gp-inner mt-2 pt-3">
    <input
        type="hidden"
        id="isOldNurse{{$clientCount}}{{$nursesCount}}"
        name="isOldNurse[{{$clientCount}}][]"
        value="{{!empty($isOldNurse) ? 1 : 0}}"/>
    <a
        data-index="{{$nursesCount}}"
        data-client-index="{{$clientCount}}"
        href="javascript:void(0);"
        class='nurse-remove-icon gp ml-2'>
            <i class="fa fa-trash-o"></i>
    </a>
    @if($nursesCount <= 0)
    <div class="form-group row m-0">
        <div class="col-md-6 d-flex">
            <label for="status{{$clientCount}}{{$nursesCount}}">Status</label>
            <div class="d-flex">
                <input
                    class="form-control"
                    id="new_{{$clientCount}}{{$nursesCount}}"
                    type="radio"
                    name="status[{{$clientCount}}][]"
                    value="new"
                    data-index="{{$nursesCount}}"
                    data-client-index="{{$clientCount}}"/>
                <label for="new">New</label>
            </div>
            <div class="d-flex ml-5">
                <input
                    class="form-control"
                    type="radio"
                    id="current_{{$clientCount}}{{$nursesCount}}"
                    name="status[{{$clientCount}}][]"
                    checked="true"
                    value="current"
                    data-index="{{$nursesCount}}"
                    data-client-index="{{$clientCount}}"/>
                <label for="current">Current</label>
            </div>
        </div>
    </div>
    @endif
    <div class="form-group row m-0">
        <div class="col-md-12 d-flex">
            <label for="nurseId{{$clientCount}}{{$nursesCount}}">Clinician</label>
            <input
                data-index="{{$nursesCount}}"
                data-client-index="{{$clientCount}}"
                type="text"
                class="form-control nurseName"
                id="nurseId{{$clientCount}}{{$nursesCount}}"
                name="nurseId[{{$clientCount}}][]"
                value=""/>
            <input
                data-index="{{$nursesCount}}"
                data-client-index="{{$clientCount}}"
                type="hidden"
                id="aoc_clinician_id{{$clientCount}}{{$nursesCount}}"
                name="aoc_clinician_id[{{$clientCount}}][]"
                value=""/>
        </div>
        @if(LD::checkFeatureFlag('replace-auth-type-with-service-gpcalculator'))
            <div class="col-md-12 d-flex">
                <label for="gpNurseDiscipline{{$clientCount}}{{$nursesCount}}">Service</label>
                <select
                    name="gpNurseDiscipline[{{$clientCount}}][]"
                    id="gpNurseDiscipline{{$clientCount}}{{$nursesCount}}"
                    class="form-control"
                    data-index="{{$nursesCount}}"
                    data-client-index="{{$clientCount}}">
                    <option value="">--- Select ---</option>
                </select>
            </div>
        @else
            <div class="col-md-12 d-flex">
                <label for="gpNurseDiscipline{{$clientCount}}{{$nursesCount}}">Auth Type</label>
                <select
                    name="gpNurseDiscipline[{{$clientCount}}][]"
                    id="gpNurseDiscipline{{$clientCount}}{{$nursesCount}}"
                    class="form-control"
                    data-index="{{$nursesCount}}"
                    data-client-index="{{$clientCount}}">
                        <option value="">--- Select ---</option>
                        @foreach(config('constant')['gpNurseDiscipline'] as $label => $val)
                        <option value="{{$val}}">{{$label}}</option>
                        @endforeach
                </select>
            </div>
        @endif
    </div>
    <div class="form-group row m-0 size-4">
        <div class="col-md-12 d-flex p-0">
            <div class="col-md-6 d-flex">
                <label class="fix-label" for="hours{{$clientCount}}{{$nursesCount}}">Hours</label>
                <input
                    type="number"
                    class="form-control bgform-control"
                    id="hours{{$clientCount}}{{$nursesCount}}"
                    name="hours[{{$clientCount}}][]"
                    value="0"
                    pattern="^\d*(\.\d{0,1})?$"
                    data-index="{{$nursesCount}}"
                    data-client-index="{{$clientCount}}"/>
            </div>
            <div class="col-md-6 d-flex">
                <label class="fix-label" for="desiredRate{{$clientCount}}{{$nursesCount}}">Desired Rate</label>
                <input
                    type="number"
                    class="form-control  bgform-control"
                    id="desiredRate{{$clientCount}}{{$nursesCount}}"
                    name="desiredRate[{{$clientCount}}][]"
                    value=""
                    data-index="{{$nursesCount}}"
                    data-client-index="{{$clientCount}}"/>
            </div>
        </div>
        <div class="col-md-12 d-flex p-0">
            <div class="col-md-6 d-flex">
                <label class="fix-label" for="prevHours{{$clientCount}}{{$nursesCount}}">Hours Already Scheduled</label>
                <input
                    type="number"
                    class="form-control bgform-control"
                    id="prevHours{{$clientCount}}{{$nursesCount}}"
                    name="prevHours[{{$clientCount}}][]"
                    value="0"
                    data-index="{{$nursesCount}}"
                    data-client-index="{{$clientCount}}"/>
            </div>
            <div class="col-md-6 d-flex">
                <label class="fix-label">GP </label>
                <span class="nurseGP" id="nurseGP{{$clientCount}}{{$nursesCount}}"></span>
            </div>
        </div>
    </div>
</div>
@endif
