<h3 class='scheduler-border'>Priority Payer Configuration</h3>
<fieldset class='scheduler-border'>
    <legend class='scheduler-border'>Payers Priority</legend>

    <div  id='payer_priority_container' class='form-container-table_heading col-md-12' data-payers-by-state='{{ $payersByStateJson }}'>
        <div class='intro-text unsortable'>Payers added here will be highlighted in green on the NTH Map and shown ahead
            of other results, in rank order, within the search location's state. Payers not listed below
            are shown unhighlighted, ordered by distance.</div>

        <template id='state-priority-container-template'>
            <div class='state_payer_priority_container col-md-12 mb-4 unsortable'>

                <div class='pb-5 unsortable'>
                    <div class='col-md-12 pull-left clearboth unsortable'>
                        <p class='pull-left mb-2'>State</p>
                    </div>
                    <div class='col-md-4 pull-left unsortable'>
                        <select class='ui fluid dropdown state-selector'>
                            <option value=''>Select a State...</option>
                            @foreach(array_keys($payersByState) as $state)
                                <option value='{{ $state }}'>{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class='col-md-8 pt-2 pull-left unsortable'>
                        <a href='#' class='pull-left remove_state'>Remove State</a>
                        <span class='pull-left mx-2'>|</span>
                        <a href='#' class='pull-left toggle_expand_collapse'>Collapse All</a>
                    </div>
                    <div class='col-md-12 pt-2 mb-2 pull-left clearboth unsortable'>
                        <p class='note pull-left'>Drag rows to set priority rank, or edit the Rank field directly. Payers are selected from the SPGM table.</p>
                    </div>
                </div>

                <div class='col-md-12 clearboth unsortable heading-container'>
                    <div class='col-md-2 heading pull-left unsortable'>Rank </div>
                    <div class='col-md-10 heading pull-left unsortable'>Payer (SPGM)</div>
                </div>

                <div id='payer-group-1' class='card-field-container pb-5 col-md-12 clearboth'>
                    <div class='form-group col-md-2 pull-left unsortable'>
                        <input name='payer_priority_rank[STATE_PLACEHOLDER][1]' type='text' class='form-control' value='1' readonly />
                    </div>
                    <div class='form-group col-md-9 pull-left unsortable'>
                        <select name='payer_priority_name[STATE_PLACEHOLDER][1]' class='ui fluid search dropdown payer-selector'>
                            <option value=''>Select a payer from SPGM…</option>
                        </select>
                    </div>
                    <div class='form-group col-md-1 pull-left unsortable'>
                        <a href='javascript:void(0)' class='remove_priority mt-2'>X</a>
                    </div>
                </div>

                <div class='button-container col-md-12 unsortable' style='clear:both'>
                    <div class='unsortable'>
                        <input type='button' class='addPayerPriority btn btn-info login-btn btn-md' value='Add More'>
                    </div>
                </div>
            </div>
        </template>

        @if($payerPriorities->isEmpty())
            <div class='state_payer_priority_container col-md-12 mb-4 unsortable'>

                <div class='pb-5 unsortable'>
                    <div class='col-md-12 pull-left clearboth unsortable'>
                        <p class='pull-left mb-2'>State</p>
                    </div>
                    <div class='col-md-4 pull-left unsortable'>
                        <select class='ui fluid dropdown state-selector'>
                            <option value=''>Select a State...</option>
                            @foreach(array_keys($payersByState) as $state)
                                <option value='{{ $state }}'>{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class='col-md-8 pt-2 pull-left unsortable'>
                        <a href='#' class='pull-left remove_state'>Remove State</a>
                        <span class='pull-left mx-2'>|</span>
                        <a href='#' class='pull-left toggle_expand_collapse'>Collapse All</a>
                    </div>
                    <div class='col-md-12 pt-2 mb-2 pull-left clearboth unsortable'>
                        <p class='note pull-left'>Drag rows to set priority rank, or edit the Rank field directly. Payers are selected from the SPGM table.</p>
                    </div>
                </div>

                <div class='col-md-12 clearboth unsortable heading-container'>
                    <div class='col-md-2 heading pull-left unsortable'>Rank </div>
                    <div class='col-md-10 heading pull-left unsortable'>Payer (SPGM)</div>
                </div>

                <div id='payer-group-1' class='card-field-container pb-5 col-md-12 clearboth'>
                    <div class='form-group col-md-2 pull-left unsortable'>
                        <input name='payer_priority_rank[STATE_PLACEHOLDER][1]' type='text' class='form-control' value='1' readonly />
                    </div>
                    <div class='form-group col-md-9 pull-left unsortable'>
                        <select name='payer_priority_name[STATE_PLACEHOLDER][1]' class='ui fluid search dropdown payer-selector'>
                            <option value=''>Select a payer from SPGM…</option>
                        </select>
                    </div>
                    <div class='form-group col-md-1 pull-left unsortable'>
                        <a href='javascript:void(0)' class='remove_priority mt-2'>X</a>
                    </div>
                </div>

                <div class='button-container col-md-12 unsortable' style='clear:both'>
                    <div class='unsortable'>
                        <input type='button' class='addPayerPriority btn btn-info login-btn btn-md' value='Add More'>
                    </div>
                </div>
            </div>
        @else
            @foreach($payerPriorities as $stateName => $priorities)
                <div class='state_payer_priority_container col-md-12 mb-4 unsortable'>

                    <div class='pb-5 unsortable'>
                        <div class='col-md-12 pull-left clearboth unsortable'>
                            <p class='pull-left mb-2'>State</p>
                        </div>
                        <div class='col-md-4 pull-left unsortable'>
                            <select class='ui fluid dropdown state-selector' data-prev='{{ $stateName }}'>
                                <option value=''>Select a State...</option>
                                @foreach(array_keys($payersByState) as $stateOption)
                                    <option value='{{ $stateOption }}' {{ $stateOption === $stateName ? 'selected' : '' }}>{{ $stateOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class='col-md-8 pt-2 pull-left unsortable'>
                            <a href='#' class='pull-left remove_state'>Remove State</a>
                            <span class='pull-left mx-2'>|</span>
                            <a href='#' class='pull-left toggle_expand_collapse'>Collapse All</a>
                        </div>
                        <div class='col-md-12 pt-2 mb-2 pull-left clearboth unsortable'>
                            <p class='note pull-left'>Drag rows to set priority rank, or edit the Rank field directly. Payers are selected from the SPGM table.</p>
                        </div>
                    </div>

                    <div class='col-md-12 clearboth unsortable heading-container'>
                        <div class='col-md-2 heading pull-left unsortable'>Rank </div>
                        <div class='col-md-10 heading pull-left unsortable'>Payer (SPGM)</div>
                    </div>

                    @foreach($priorities as $index => $priority)
                        <div id='payer-group-{{ $index + 1 }}' class='card-field-container pb-5 col-md-12 clearboth'>
                            <div class='form-group col-md-2 pull-left unsortable'>
                                <input name='payer_priority_rank[{{ $stateName }}][{{ $index + 1 }}]' type='text' class='form-control' value='{{ $index + 1 }}' readonly />
                            </div>
                            <div class='form-group col-md-9 pull-left unsortable'>
                                <select name='payer_priority_name[{{ $stateName }}][{{ $index + 1 }}]' class='ui fluid search dropdown payer-selector'>
                                    <option value=''>Select a payer from SPGM…</option>
                                    @foreach($payersByState[$stateName] ?? [] as $payerOption)
                                        <option value='{{ $payerOption }}' {{ $payerOption === $priority->payer ? 'selected' : '' }}>{{ $payerOption }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class='form-group col-md-1 pull-left unsortable'>
                                <a href='javascript:void(0)' class='remove_priority mt-2'>X</a>
                            </div>
                        </div>
                    @endforeach

                    <div class='button-container col-md-12 unsortable' style='clear:both'>
                        <div class='unsortable'>
                            <input type='button' class='addPayerPriority btn btn-info login-btn btn-md' value='Add More'>
                        </div>
                    </div>
                </div>
            @endforeach
        @endif

        <template id='payer-priority-row-template'>
            <div class='card-field-container pb-5 col-md-12 clearboth'>
                <div class='form-group col-md-2 pull-left unsortable'>
                    <input name='payer_priority_rank[STATE_PLACEHOLDER][INDEX]' type='text' class='form-control' value='INDEX' readonly />
                </div>
                <div class='form-group col-md-9 pull-left unsortable'>
                    <select name='payer_priority_name[STATE_PLACEHOLDER][INDEX]' class='ui fluid search dropdown payer-selector'>
                        <option value=''>Select a payer from SPGM…</option>
                    </select>
                </div>
                <div class='form-group col-md-1 pull-left unsortable'>
                    <a href='javascript:void(0)' class='remove_priority mt-2'>X</a>
                </div>
            </div>
        </template>

        <div class='button-container col-md-12 unsortable' style='clear:both'>
            <div class='unsortable'>
                <input type='button' class='btn btn-info login-btn btn-md mr-3' id='addPayerPriorityState' value='+ Add Another State'>
            </div>
        </div>

    </div>


</fieldset>

@push('styles')
    <link href='{{ mix('css/components/priorityPayersConfig/priority-payer-config.css') }}' rel='stylesheet'>
@endpush

@push('scripts')
    <script src="{{ mix('js/components/priorityPayersConfig/priority-payer-config.js') }}"></script>
@endpush
