<?php
    use App\Helpers\LD;
    use App\Helpers\H;
    $gpId = isset($gpData->id) ? $gpData->id : '';
    $payers = H::getHelper('ConstantHelper')->getPayers();
?>

@extends('layouts.myaccount')
@section('title', 'GP Calculator')
@section('content')

<style>
    .nurse-gp-form-cnt input[type=radio] {
        width: 20px;
        margin-right: 5px;
    }
    .gp-calculator-cnt .btn.list{
        min-width: auto !important;
    }
    .gp-calculator-cnt .btn.list i.fa{
        margin-right: 0px !important;
    }
    label.label-50 {
        width: initial !important;
        min-width: 50px;
    }
    .gp-calculator-cnt label{
        width: initial !important;
        min-width: 83px;
        font-size: 16px;
    }
    .gp-calculator-cnt table td, .gp-calculator-cnt table th, .gp-calculator-cnt .form-control {
        font-size: 20px;
    }
    .gp-calculator-cnt .bgform-control {
        color: #2f5baa;
        background: #e9ecef;
        border: 0px;
    }
    .gp-calculator-cnt label.fix-label {
        min-width: 185px;
    }
    label.fix-width {
        width: 10% !important;
    }
    h3.extra-heading{
        background: #2f5baa;
        padding: 5px;
        color: #fff !important;
        width: 97%;
        margin: 10px auto;
    }
    .table th {
        background: #2f5baa;
        color: #fff;
    }
    .nurseGP {
        color: #2f5baa;
        font-weight: 700;
        padding: 0px 0px;
        line-height: 39px !important;
        width: 100%;
        background: #e9ecef;
        height: 40px;
        border-radius: 5px;
    }
    .newbgcolor {
        background: #DBDBDB !important;
        color : #495057 !important
    }
    .multiselect-native-select .btn-group {
        max-width: 100%;
        width: 100%;
    }
    span.multiselect-native-select {
        width: 100%;
    }
    .clientPayer.error + .btn-group{
        border: 1px dashed red;
        color: red;
        font-weight: 300;
    }

    .size-4 label,
    .size-4 .form-control,
    .size-4 .nurseGP {
        font-size: 20px;
    }

    .size-4 .nurseGP {
        max-width: 150px;
    }

    .size-4 label.fix-label {
        min-width: 20px;
    }

    .size-4 .form-control {
        max-width: 150px;
    }

    .size-4 .col-md-6.d-flex {
        justify-content: flex-end;
    }

</style>
<div class="gp-calculator-cnt">
    <form action="" id="nurseGPForm">
        <input type="hidden" name="gpId" id="gpId" value="{{$gpId}}" />
        @if(isset($mode) && $mode == 'edit')
        <div class="clientSelectionContainer">
            @foreach($patientsData as $k => $patientData)
                <div class="clientSelectionContainerRow">
                    <a
                        data-index="{{$k}}"
                        href="javascript:void(0);"
                        class='hide'
                        style="padding-top: 140px;">
                            <i class="fa fa-trash-o"></i>
                    </a>
                    <div class="col-md-12 d-flex">
                        <div class="col-md-4">
                            <div class="form-group row">
                                <label for="">Select Client</label>
                                <input
                                    placeholder="Select Client"
                                    type="text"
                                    id="clientSelectionForGP_{{$k}}"
                                    name="clientSelectionForGP[]"
                                    class="form-control clientSelectionForGP"
                                    data-index = "{{$k}}"
                                    value="{{($patientData->id < 0) ? $patientData->name : $patientData->Patient_FirstName.' '.$patientData->Patient_LastName}}" />
                                <input
                                    type="hidden"
                                    name="aoc_patient_id[]"
                                    id="aoc_patient_id_{{$k}}"
                                    data-index = "{{$k}}"
                                    value="{{$patientData->id}}" />
                            </div>
                            <div class="form-group row">
                                <label for="">Insurance/Payer</label>
                                <select
                                    name="clientPayer[]"
                                    id="clientPayer_{{$k}}"
                                    class="form-control clientPayer"
                                    data-index = "{{$k}}">
                                        <option value="">--- Select ---</option>
                                        @php
                                            if ($respite[$k] == 1) {
                                                $payers = H::getHelper('ConstantHelper')->getRespitePayers();
                                            }
                                        @endphp
                                        @foreach($payers as $key => $value)
                                        <option
                                            {{ ($gpAocPatientProviders[$k] == $value['value']) ? 'selected' : ''}}
                                            value="{{$value['value']}}">{{$value['text']}}</option>
                                        @endforeach
                                </select>
                            </div>
                            <div class="form-group row">
                                <div class="col-md-6 d-flex p-0">
                                    <label class="w-100 align-self-center m-0 mr-3">Specialized?</label>
                                    <div class="custom-control custom-switch custom-switch-lg">
                                        <input
                                            type="hidden"
                                            name="specialized[{{$k}}]"
                                            value="0"
                                            class="specialized1"/>
                                        <input
                                        class="custom-control-input specialized"
                                        type="checkbox"
                                        id="specialized_{{$k}}"
                                        data-index = "{{$k}}"
                                        value="1"
                                        name="specialized[{{$k}}]"
                                        {{
                                            ($specialized[$k] == 1) ? 'checked="true"' : ''}}/>
                                        <label class="custom-control-label" for="specialized_{{$k}}"></label>
                                    </div>
                                </div>
                                <div class="col-md-6 d-flex p-0">
                                    <label class="w-100 align-self-center m-0 mr-3">Respite?</label>
                                    <div class="custom-control custom-switch custom-switch-lg">
                                        <input
                                            type="hidden"
                                            name="respite[{{$k}}]"
                                            value="0"
                                            class="respite1"/>
                                        <input
                                        class="custom-control-input respite"
                                        type="checkbox"
                                        id="respite_{{$k}}"
                                        data-index = "{{$k}}"
                                        value="1"
                                        name="respite[{{$k}}]"
                                        {{
                                            ($respite[$k] == 1) ? 'checked="true"' : ''}}/>
                                        <label class="custom-control-label" for="respite_{{$k}}"></label>
                                    </div>
                                </div>
                                <span id="respiteMsg_{{ $k }}" class="hide alert alert-warning mt-3">
                                    Client does not have an active respite payer. Please unselect the respite option to proceed.
                                </span>
                            </div>
                            @if($k == 0)
                            <div class="col-md-12 p-0" id="gpIdeaTable">

                            </div>
                            @endif
                        </div>
                        <div class="col-md-8">
                            @if ($k == 0)
                            <div class="form-group row ml-5 action">
                                <a href="javascript:void(0);" class="btn btn-primary list" id="gpInformationPopupTag"><i class="fa fa-info-circle fa-4x"></i></a>
                                <button class="btn btn-primary ml-3" id="addNurseBtn">Add Clinician</button>
                                <button class="btn btn-primary ml-3" id="calculate">Calculate</button>
                                <button class="btn btn-primary ml-3" id="wholeHome">Show Whole Home</button>
                                <a class="btn btn-primary list ml-3" title="Add GP" href="{{route('gpCalculator.list')}}">Save to List</a>
                                <button class="btn btn-primary ml-3" id="clear">Clear</button>
                            </div>
                            @endif
                            <div class="nurse-gp-form-cnt col-md-10 ml-3">
                                    @if(isset($mode) && $mode== 'edit')
                                        @include('gpCalculator.nurseForm', [
                                            'isOldNurse' => 0,
                                            'nurseData' => $nurseData[$patientData->id],
                                            'clientIndex' => $k
                                        ])
                                    @endif
                                </div>
                            <div class="col-md-12 ml-3 nurseGPFormCnt" id="nurseGPFormCnt">
                                @if(isset($mode) && $mode== 'edit')
                                    @php
                                        $isOldNurse = array_count_values(
                                            array_map(function ($ar) {return $ar['isOldNurse'];}, $nurseData[$patientData->id])
                                        );
                                    @endphp
                                    @if(isset($isOldNurse[1]) && $isOldNurse[1] > 0)
                                        <h3 class='extra-heading'> Whole Home </h3>
                                    @endif
                                    @include('gpCalculator.nurseForm', [
                                            'isOldNurse' => 1,
                                            'nurseData' => $nurseData[$patientData->id],
                                            'clientIndex' => $k
                                        ])
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @else
        <div class="clientSelectionContainer">
            <div class="clientSelectionContainerRow">
                <a
                    data-index="0"
                    href="javascript:void(0);"
                    class='hide'
                    style="padding-top: 140px;">
                        <i class="fa fa-trash-o"></i>
                </a>
                <div class="col-md-12 d-flex">
                    <div class="col-md-4">
                        <div class="form-group row">
                            <label for="">Select Client</label>
                            <input
                                placeholder="Select Client"
                                type="text"
                                id="clientSelectionForGP_0"
                                name="clientSelectionForGP[]"
                                class="form-control clientSelectionForGP"
                                data-index = "0"
                                value="" />
                            <input
                                type="hidden"
                                name="aoc_patient_id[]"
                                id="aoc_patient_id_0"
                                data-index = "0"
                                value="" />
                        </div>
                        <div class="form-group row">
                            <label for="">Insurance/Payer</label>
                            <select
                                name="clientPayer[]"
                                id="clientPayer_0"
                                class="form-control clientPayer"
                                data-index = "0">
                                    <option value="">--- Select ---</option>
                                    @foreach($payers as $key => $value)
                                    <option
                                        value="{{$value['value']}}">{{$value['text']}}</option>
                                    @endforeach
                            </select>
                        </div>
                        <div class="form-group row">
                            <div class="col-md-6 d-flex p-0">
                                <label class="w-100 align-self-center m-0 mr-3">Specialized?</label>
                                <input
                                    type="hidden"
                                    name="specialized[0]"
                                    value="0"
                                    class="specialized1"/>
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input
                                    class="custom-control-input specialized"
                                    type="checkbox"
                                    id="specialized_0"
                                    data-index = "0"
                                    value="1"
                                    name="specialized[0]"/>
                                    <label class="custom-control-label" for="specialized_0"></label>
                                </div>
                            </div>
                            <div class="col-md-6 d-flex p-0">
                                <label class="w-100 align-self-center m-0 mr-3">Respite?</label>
                                <input
                                    type="hidden"
                                    name="respite[0]"
                                    value="0"
                                    class="respite1"/>
                                <div class="custom-control custom-switch custom-switch-lg">
                                    <input
                                    class="custom-control-input respite"
                                    type="checkbox"
                                    id="respite_0"
                                    data-index = "0"
                                    value="1"
                                    name="respite[0]"/>
                                    <label class="custom-control-label" for="respite_0"></label>
                                </div>
                            </div>
                            <span id="respiteMsg_0" class="hide alert alert-warning mt-3">
                                Client does not have an active respite payer. Please unselect the respite option to proceed.
                            </span>
                        </div>
                        <div class="col-md-12 p-0" id="gpIdeaTable">

                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="form-group row ml-5 action">
                            <a href="javascript:void(0);" class="btn btn-primary list" id="gpInformationPopupTag"><i class="fa fa-info-circle fa-4x"></i></a>
                            <button class="btn btn-primary ml-3" id="addNurseBtn">Add Clinician</button>
                            <button class="btn btn-primary ml-3" id="calculate">Calculate</button>
                            <button class="btn btn-primary ml-3" id="wholeHome">Show Whole Home</button>
                            <a class="btn btn-primary list ml-3" title="Add GP" href="{{route('gpCalculator.list')}}">Save to List</a>
                            <button class="btn btn-primary ml-3" id="clear">Clear</button>
                        </div>
                        <div class="nurse-gp-form-cnt col-md-10 ml-3">
                                @if(isset($mode) && $mode== 'edit')
                                    @include('gpCalculator.nurseForm', ['isOldNurse' => 0])
                                @endif
                            </div>
                        <div class="col-md-12 ml-3 nurseGPFormCnt" id="nurseGPFormCnt">
                            @if(isset($mode) && $mode== 'edit')
                                @if(count($nurseData) > 0)
                                    <h3 class='extra-heading'> Whole Home </h3>
                                @endif
                                @include('gpCalculator.nurseForm', ['isOldNurse' => 1])
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
  @endif
        <div class="col-md-12 d-flex hide" id="addNewClientContainer">
            <button class="btn btn-primary ml-3" id="addClientBtn">Add Client</button>
        </div>
    </form>
</div>


<script type="text/javascript">
    var isMultipleClient = true;
    var selectedClientData = {};
    var nursesCount = [];
    nursesCount[0] = <?php echo (isset($mode) && $mode='edit') ? $nursesCount : 0; ?>;
    var mode = '<?php echo (isset($mode) && $mode=='edit') ? 'edit' : 'add';?>';
    var clientCount = <?php echo (isset($mode) && $mode='edit') ? $clientCount : 0; ?>;
    var wholeHomeFlag = false;
    var nursesArr = [];
    var selectedPatientPayer = {!! (isset($mode) && $mode='edit') ? json_encode($patientsPayer) : '[]' !!};
    var selectedPatientRespitePayer = {!! (isset($mode) && $mode='edit') ? json_encode($patientsRespitePayer) : '[]' !!};

    $(document).on('keydown.autocomplete', "input.clientSelectionForGP", function() {
        $(this).autocomplete({
            source: "{{ route('getScheduleClient') }}",
            minLength: 2,
            classes: {
                "ui-autocomplete": "client-selection-for-gp"
            },
            select: function(event, ui) {
                selectedClientData = ui.item;
                let index = $(this).data('index');
                $('#aoc_patient_id_' + index).val(ui.item.id);
                $('#respiteMsg_' + index).addClass('hide');
                if ($('#respite_' + index).is(':checked')) {
                    $('#clientPayer_' + index).val(ui.item.respiteTerminalPlayer).multiselect("rebuild");
                    if (
                        ui.item.respiteTerminalPlayer == '' ||
                        ui.item.respiteTerminalPlayer == undefined ||
                        ui.item.respiteTerminalPlayer == null
                    ) {
                        $('#respiteMsg_' + index).removeClass('hide');
                    }
                } else {
                    $('#clientPayer_' + index).val(ui.item.payer).multiselect("rebuild");
                }
                selectedPatientPayer[index] = ui.item.payer;
                selectedPatientRespitePayer[index] = ui.item.respiteTerminalPlayer;
                $('#clientPayer_' + index).change();
                if(ui.item.is_specialized === 0){
                    $("#specialized_" + index).prop('checked', false);;
                }else{
                    $("#specialized_" + index).prop('checked', true);;
                }
                $('#addNewClientContainer').removeClass('hide');
                $('.clientSelectionContainerRow').addClass('addBorder');
                @if(LD::checkFeatureFlag('replace-auth-type-with-service-gpcalculator'))
                for(i=0; i < nursesArr.length; i++) {
                    let nurseArrVal = nursesArr[i].split('#');
                    let clientId = $('#aoc_patient_id_' + nurseArrVal[0]).val();
                    if (index == nurseArrVal[0]) {
                        getServices(clientId, nurseArrVal[0], nurseArrVal[1]);
                    }
                }
                @endif
            },
            change: function(event,ui) {
                if (ui.item == null) {
                    let index = $(this).data('index');
                    selectedPatientPayer.splice(index, 1);
                    selectedPatientRespitePayer.splice(index, 1);
                    //$(this).val('');
                    let timestamp = new Date().valueOf();
                    $('#aoc_patient_id_' + index).val(-1 * timestamp);
                    $('#clientPayer_' + index).val('').multiselect("rebuild");
                    $("#specialized_" + index).prop('checked', false);
                    $("#respite_" + index).prop('checked', false);
                    $('#addNewClientContainer').addClass('hide');
                    $('.clientSelectionContainerRow').removeClass('addBorder');

                    $('#clientPayer_' + index).off().change(function() {
                        for(i=0; i < nursesArr.length; i++) {
                            let nurseArrVal = nursesArr[i].split('#');
                            let clientId = $('#aoc_patient_id_' + nurseArrVal[0]).val();
                            if (index == nurseArrVal[0]) {
                                getServices(clientId, nurseArrVal[0], nurseArrVal[1]);
                            }
                        }
                    });

                    $('#wholeHome').attr('disabled','disabled');
                }
            }
        });
    });

    $('#addClientBtn').click(function(e) {
        e.preventDefault();
        if ($('#nurseGPForm').valid()) {
            $('#addNurseBtn').attr('disabled','disabled');
            $('#wholeHome').attr('disabled','disabled');
            $('.nurseGPFormCnt').empty();
            clientCount++;
            nursesCount[clientCount] = 0;
            let rowHtml = $('.clientSelectionContainerRow:first').clone();
            rowHtml.find('.action').remove();
            rowHtml.find('.nurse-form-inner .form-group:first').remove();

            rowHtml.find('input[name="clientSelectionForGP[]"]').attr('id', function(_, currentID) {
                return currentID.replace('_0', "_" + clientCount);
            });
            rowHtml.find('input[name="clientSelectionForGP[]"]').attr('data-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[name="clientSelectionForGP[]"]').val('');

            rowHtml.find('input[name="aoc_patient_id[]"]').attr('id', function(_, currentID) {
                return currentID.replace('_0', "_" + clientCount);
            });
            rowHtml.find('input[name="aoc_patient_id[]"]').attr('data-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[name="aoc_patient_id[]"]').val('');

            rowHtml.find('select[name="clientPayer[]"]').attr('id', function(_, currentID) {
                return currentID.replace('_0', "_" + clientCount);
            });
            rowHtml.find('select[name="clientPayer[]"]').attr('data-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('select[name="clientPayer[]"]').val('');
            rowHtml.find('select[name="clientPayer[]"] + .btn-group').remove();
            rowHtml.find('select[name="clientPayer[]"]')
                    .parent('span')
                    .removeClass('multiselect-native-select')
                    .css('display', 'contents');
            rowHtml.find('select[name="clientPayer[]"]')
            .multiselect({includeSelectAllOption: false, enableCaseInsensitiveFiltering: true});

            rowHtml.find('input[type="checkbox"].specialized').attr('id', function(_, currentID) {
                return currentID.replace('_0', "_" + clientCount);
            });
            rowHtml.find('input[type="checkbox"].specialized').attr('data-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[type="checkbox"].specialized').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[type="hidden"].specialized1').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[type="checkbox"].specialized').attr('checked', false);
            rowHtml.find('input[type="checkbox"].specialized').next('label').attr('for', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });

            rowHtml.find('input[type="checkbox"].respite').attr('id', function(_, currentID) {
                return currentID.replace('_0', "_" + clientCount);
            });
            rowHtml.find('input[type="checkbox"].respite').attr('data-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[type="checkbox"].respite').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[type="hidden"].respite1').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[type="checkbox"].respite').attr('checked', false);
            rowHtml.find('input[type="checkbox"].respite').next('label').attr('for', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });

            rowHtml.find('input[name="nurseId[0][]"]').attr('data-index', function(_, currentID) {
                return currentID.replace('0', nursesCount[clientCount]);
            });
            rowHtml.find('input[name="nurseId[0][]"]').attr('data-client-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[name="nurseId[0][]"]').attr('id', function(_, currentID) {
                return currentID.replace('00', clientCount + '' + nursesCount[clientCount]);
            });
            rowHtml.find('input[name="nurseId[0][]"]').attr('readonly', 'readonly');
            rowHtml.find('input[name="nurseId[0][]"]').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });

            rowHtml.find('input[name="aoc_clinician_id[0][]"]').attr('data-index', function(_, currentID) {
                return currentID.replace('0', nursesCount[clientCount]);
            });
            rowHtml.find('input[name="aoc_clinician_id[0][]"]').attr('data-client-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[name="aoc_clinician_id[0][]"]').attr('id', function(_, currentID) {
                return currentID.replace('00', clientCount + '' + nursesCount[clientCount]);
            });
            rowHtml.find('input[name="aoc_clinician_id[0][]"]').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });

            rowHtml.find('select[name="gpNurseDiscipline[0][]"]').attr('data-index', function(_, currentID) {
                return currentID.replace('0', nursesCount[clientCount]);
            });
            rowHtml.find('select[name="gpNurseDiscipline[0][]"]').attr('data-client-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('select[name="gpNurseDiscipline[0][]"]').attr('id', function(_, currentID) {
                return currentID.replace('00', clientCount + '' + nursesCount[clientCount]);
            });
            @if(LD::checkFeatureFlag('replace-auth-type-with-service-gpcalculator'))
                rowHtml.find('select[name="gpNurseDiscipline[0][]"]').val('');
            @else
                rowHtml.find('select[name="gpNurseDiscipline[0][]"]').val($('select[name="gpNurseDiscipline[0][]').val());
            @endif
            rowHtml.find('select[name="gpNurseDiscipline[0][]"]').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });

            rowHtml.find('input[name="hours[0][]"]').attr('data-index', function(_, currentID) {
                return currentID.replace('0', nursesCount[clientCount]);
            });
            rowHtml.find('input[name="hours[0][]"]').attr('data-client-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[name="hours[0][]"]').attr('id', function(_, currentID) {
                return currentID.replace('00', clientCount + '' + nursesCount[clientCount]);
            });
            rowHtml.find('input[name="hours[0][]"]').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });

            rowHtml.find('input[name="desiredRate[0][]"]').attr('data-index', function(_, currentID) {
                return currentID.replace('0', nursesCount[clientCount]);
            });
            rowHtml.find('input[name="desiredRate[0][]"]').attr('data-client-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[name="desiredRate[0][]"]').attr('id', function(_, currentID) {
                return currentID.replace('00', clientCount + '' + nursesCount[clientCount]);
            });
            rowHtml.find('input[name="desiredRate[0][]"]').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });

            rowHtml.find('input[name="prevHours[0][]"]').attr('data-index', function(_, currentID) {
                return currentID.replace('0', nursesCount[clientCount]);
            });
            rowHtml.find('input[name="prevHours[0][]"]').attr('data-client-index', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('input[name="prevHours[0][]"]').attr('id', function(_, currentID) {
                return currentID.replace('00', clientCount + '' + nursesCount[clientCount]);
            });
            rowHtml.find('input[name="prevHours[0][]"]').attr('name', function(_, currentID) {
                return currentID.replace('0', clientCount);
            });
            rowHtml.find('#gpIdeaTable').remove();

            rowHtml.find('#nurseGP00').attr('id', function(_, currentID) {
                return currentID.replace('00', clientCount + '' + nursesCount[clientCount]);
            });
            rowHtml.find('#nurseGP'+clientCount+nursesCount[clientCount]).text('');
            rowHtml.appendTo('.clientSelectionContainer');

            nursesArr.push(clientCount+"#"+nursesCount[clientCount]);
        }
    })

    $('#addNurseBtn').click(function(e){
        e.preventDefault();
        $('#addClientBtn').attr('disabled','disabled');
        loadGPNurseForm();
    });

    $('#wholeHome').click(function(e){
        e.preventDefault();
        if ($('#aoc_patient_id_0').val() == '') {
            $('#clientSelectionForGP_0').valid();
        }
        if ($('#aoc_patient_id_0').val() != '' && wholeHomeFlag == false) {
            autoFillNurses($('#aoc_patient_id_0').val());
        } else {
            wholeHomeFlag = false;
            jQuery('.nurseGPFormCnt').empty();
        }
    });

    function autoFillNurses(aoc_patient_id) {
        $.ajax({
            url: "{{route('gpCalculator.index')}}?aoc_patient_id=" +
            aoc_patient_id + '&formRowCount=' + nursesCount[clientCount],
            type: 'GET',
            success: function(data) {
                if (data.nurseHtml != '') {
                    wholeHomeFlag = true;
                }
                $('.nurseGPFormCnt').empty();
                $('.nurseGPFormCnt').append("<h3 class='extra-heading'> Whole Home </h3>");
                if (data.nurseCount > 0) {
                    $('.nurseGPFormCnt').append(data.nurseHtml);
                    for (let index = 0; index < parseInt(data.nurseCount); index++) {
                        setValidationAndAutoComplete(nursesCount[clientCount], clientCount);
                        nursesArr.push(clientCount+"#"+nursesCount[clientCount]);
                        nursesCount[clientCount]++;
                    }
                    $('#addClientBtn').attr('disabled','disabled');
                }

                @if(LD::checkFeatureFlag('replace-auth-type-with-service-gpcalculator'))
                for(i=0; i < nursesArr.length; i++) {
                    let nurseArrVal = nursesArr[i].split('#');
                    let clientId = $('#aoc_patient_id_' + nurseArrVal[0]).val();
                    getServices(clientId, nurseArrVal[0], nurseArrVal[1]);
                }
                @endif
            }
        });
    }

    function saveGp(cb){
        $('#calculate').attr('disabled',true);
        $('#calculate').html('Please Wait...');
        if($('#nurseGPForm').valid()){
            $('.specialized').each(function () {
                const index = $(this).data('index');
                if ($(this).is(':checked')) {
                    $(`input[type="hidden"][name="specialized[${index}]"]`).remove();
                }
            });
            $('.respite').each(function () {
                const index = $(this).data('index');
                if ($(this).is(':checked')) {
                    $(`input[type="hidden"][name="respite[${index}]"]`).remove();
                }
            });
            let _token = $('meta[name="csrf-token"]').attr('content');
            var data = $('#nurseGPForm').serializeObject();
            data['_token'] = _token;
            $.ajax({
                url: "{{route('gpCalculator.saveGp')}}",
                type: 'POST',
                data: data,
                success: function(data) {
                    $('#calculate').html('Calculate');
                    $('#calculate').attr('disabled',false);
                    if(data.status === true){
                        $('#gpId').val(data.data.id);
                        cb(data);
                    }
                }
            });
        }else{
            $('#calculate').html('Calculate');
            $('#calculate').attr('disabled',false);
        }
    }
    $(document).on('change', '.specialized', function() {
        if (!$(this).is(':checked')) {
            dataindex = $(this).data('index');
            if($(`input[type="hidden"][name="specialized[${dataindex}]"]`).length == 0) {
                $(`<input type="hidden" name="specialized[${dataindex}]" value="0" class="specialized1">`).insertBefore($(this));
            }
        }
    });
    $(document).on('change', '.respite', function() {
        if (!$(this).is(':checked')) {
            dataindex = $(this).data('index');
            if($(`input[type="hidden"][name="respite[${dataindex}]"]`).length == 0) {
                $(`<input type="hidden" name="respite[${dataindex}]" value="0" class="respite1">`).insertBefore($(this));
            }
        }
    });
    $('#calculate').click(function(e){
        e.preventDefault();
        saveGp(function(data){
            //Calculation
            var preview = (mode === 'edit') ? 0 : 1;
            loadGPCalculationTable(data.data.id, preview)
        });
    });

    $('#saveGp').click(function(e){
        e.preventDefault();
        saveGp(function(data){
            location.href = '{{route('gpCalculator.list')}}'
        });
    });

    //Validation

    $("#nurseGPForm").validate({
            onfocusout: false,
            invalidHandler: function(form, validator) {
                var errors = validator.numberOfInvalids();
                if (errors) {
                    validator.errorList[0].element.focus();
                }
            },
            rules: {
                "clientSelectionForGP[]": {
                    required: function(element) {
                        let index = $(element).data('index');
                        return $("#aoc_patient_id_" + index).val() == '';
                    }
                },
                "clientPayer[]": {
                    required: function(element) {
                        let index = $(element).data('index');
                        return $("#clientPayer_" + index).val() == '';
                    }
                },
            },
            errorPlacement: function(error, element) {}
    });

    $(document).on('click','.nurse-remove-icon.gp',function(e){
        e.preventDefault();
        if (confirm("Are you sure you want to delete?")) {
            $(this).parent().remove();
            var idx = nursesArr.indexOf(
                parseInt($(this).attr('data-client-index')) + '#' + parseInt($(this).attr('data-index'))
            );
            if (idx !== -1) {
                nursesArr.splice(idx, 1);
            }
            if (nursesArr.length == 1) {
                $('#addClientBtn').removeAttr('disabled');
            }
        }else{
            return false;
        }
    });

    function setValidationAndAutoComplete(ind, clientInd){
        $("#nurseId" + clientInd + '' + ind).rules("add", "required");
        (function(ind, clientInd) {
            $.validator.addMethod('customClinicianRule'  + clientInd + '' + ind, function(value, element) {
                return !(
                    value && (
                        $("#aoc_clinician_id"  + clientInd + '' + ind).val() === '0' ||
                        $("#aoc_clinician_id"  + clientInd + '' + ind).val() == ''
                    )
                );
            }, 'Custom error message');

            var obj = {};
            obj["customClinicianRule"  + clientInd + '' + ind] = true;
            $("#nurseId"  + clientInd + '' + ind).rules("add", obj);
        })(ind, clientInd);

        $("#gpNurseDiscipline" + clientInd + '' + ind).rules("add", "required");
        $("#hours" + clientInd + '' + ind).rules("add", "required");
        $("#desiredRate" + clientInd + '' + ind).rules("add", "required");
        $("#prevHours" + clientInd + '' + ind).rules("add", "required");

        window["clientSelectionFlag" + clientInd + '' + ind] = false;
        $("#nurseId" + clientInd + '' + ind).autocomplete({
            source: function(request, response) {
                var input = this.element;
                $.getJSON("{{ route('getCandidateName') }}", {
                    status: $('input[name="status['+clientInd+'][]"]:checked').val(),
                    term: $("#nurseId" + clientInd + '' + ind).val()
                }, response);
            },
            minLength: 2,
            classes: {
                "ui-autocomplete": "client-selection-for-gp"
            },
            select: function(event, ui) {
                setTimeout(function(){
                    window["clientSelectionFlag" + clientInd + '' + ind] = true;
                }, 100);
                let ind = $(this).attr('data-index');
                let clientInd = $(this).attr('data-client-index');
                $('#aoc_clinician_id' + clientInd + '' + ind).val(ui.item.id);
                $("#gpNurseDiscipline" + clientInd + '' + ind).val(ui.item.title);
                if((clientInd == 0 && $('input[name="status['+clientInd+'][]"]:checked').val() == 'current')){
                    loadScheduleHours(ui.item.id,function(data){
                        $('#prevHours' + clientInd + '' + ind).val(data.hour??0);
                    });
                }
            }
        });

        $("#nurseId" + clientInd + '' + ind).keyup(function() {
            if (window["clientSelectionFlag" + clientInd + '' + ind]) {
                window["clientSelectionFlag" + clientInd + '' + ind] = false;
                $('#aoc_clinician_id' + clientInd + '' + ind).val(0);
                $("#gpNurseDiscipline" + clientInd + '' + ind).val('');
                $('#prevHours' + clientInd + '' + ind).val(0);
            } else {
                $('#aoc_clinician_id' + clientInd + '' + ind).val('-1');
                $('#hours' + clientInd + '' + ind).val(0);
                $('#desiredRate' + clientInd + '' + ind).val(0);
                $('#prevHours' + clientInd + '' + ind).val(0);
            }
        });
    }
    function loadScheduleHours(providerId,cb){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('gpCalculator.loadScheduleHours')}}",
            type: 'get',
            dateType:"html",
            data: {
                providerId:providerId,
                _token: _token
            },
            success: function(data) {
                cb(data);
            }
        });
    }
    function loadGPNurseForm(){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $('#addNurseBtn').attr('disabled','disabled');
        $.ajax({
            url: "{{route('gpCalculator.loadGPNurseForm')}}",
            type: 'get',
            dateType:"html",
            data: {
                nursesCount:nursesCount[clientCount],
                clientCount:clientCount,
                _token: _token
            },
            success: function(data) {
                $('#addNurseBtn').removeAttr('disabled');
                $('.nurse-gp-form-cnt').append(data);
                //Validation for the nurse form
                setValidationAndAutoComplete(nursesCount[clientCount], clientCount);
                nursesArr.push(clientCount+"#"+nursesCount[clientCount]);
                @if(LD::checkFeatureFlag('replace-auth-type-with-service-gpcalculator'))
                    let clientId = $('#aoc_patient_id_' + clientCount).val();
                    getServices(clientId, clientCount, nursesCount[clientCount]);
                @endif
                nursesCount[clientCount]++;
            }
        });
    }

    function loadGPCalculationTable(id, preview = 0){
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{route('gpCalculator.calculateGpTable')}}",
            type: 'get',
            dateType:"html",
            data: {
                id:id,
                preview: preview,
                _token: _token
            },
            success: function(data) {
                $('#gpIdeaTable').html(data);
                $('#gpInformationPopupTag').attr('onClick', 'gpInformationPopup();');
            }
        });
    }
    $(document).on('change','#nurseGPForm input[type="number"]:not(input[pattern])',function(){
        $(this).val(parseFloat($(this).val()).toFixed(2));
    });

    $(document).on('keydown', '#nurseGPForm input[pattern]', function(e){
        var input = $(this);
        var oldVal = input.val();
        var regex = new RegExp(input.attr('pattern'), 'g');

        setTimeout(function() {
            var newVal = input.val();
            if (!regex.test(newVal)) {
                input.val(oldVal);
            }
        }, 1);
    });

    $(document).ready(function(){
        if(mode != 'edit'){
            loadGPNurseForm();
        }else{
            if(clientCount == 0) {
                for (let index = 0; index < nursesCount[clientCount]; index++) {
                    setValidationAndAutoComplete(index, 0);
                    nursesArr.push(0+"#"+index);
                }
            } else {
                for (let index = 0; index <= clientCount; index++) {
                    setValidationAndAutoComplete(0, index);
                    nursesArr.push(index+"#"+0);
                }
            }
            if (clientCount >=0 && nursesCount[clientCount] == 1) {
                $('#addNewClientContainer').removeClass('hide');
            }
            if (clientCount > 0) {
                $('#addNewClientContainer').removeClass('hide');
                $('.clientSelectionContainerRow').addClass('addBorder');
                $('#addNurseBtn').attr('disabled','disabled');
                $('#wholeHome').attr('disabled','disabled');
            }
            loadGPCalculationTable("{{$gpId}}")
        }

        $('.clientPayer').multiselect({includeSelectAllOption: false, enableCaseInsensitiveFiltering: true});
        $(document).on('change', '.clientPayer', function() {
            let index = $(this).data('index');
            $('#clientPayer_' + index).removeClass('error');
        });

    });

    $(document).ready(function(){
        $('#clear').click(function(e) {
            e.preventDefault();
            $.ajax({
                url: "{{route('gpCalculator.clear')}}?gpId=" + $('#gpId').val(),
                type: 'GET',
                success: function(data) {
                    if (data.status) {
                        location.href = "{{ route('gpCalculator.index') }}";
                    }
                }
            });
        });
    });

    function getServices(aoc_patient_id, clientIndex = 0, nurseIndex = 0) {
        let isRespite = $('#respite_' + clientIndex).is(':checked');

        let selectedPayer = $("#selectedPayer" + clientIndex + '' + nurseIndex);
        let payer = $('#clientPayer_' + clientIndex).val();
        if (selectedPayer) {
            //this is the fkn hack until we figure out what to do with same client calculation \w different respite options from the business
            //as of now we have a bug AOC-1874/ If we add one client twice and choose respite and non-respite at the same time we would have wrong calculation
            //as we merge this records into one and first record is overridden with second, and only one saved
            //so we added selectedPayer to the nurse data and loading services using this field for it in edit mode
            if (selectedPayer.val() && !selectedPayer.val().toLowerCase().includes("respite")) {
                isRespite = false;
            }

            if (selectedPayer.val() && selectedPayer.val().toLowerCase().includes("respite")) {
                isRespite = true;
            }

            payer = selectedPayer.val()
        }
        $.ajax({
            url: "{{route('gpCalculator.getService')}}",
            type: 'GET',
            data: { aoc_patient_id: aoc_patient_id, isRespite: isRespite, payer: payer },
            success: function(data) {
                if (data.status) {
                    var $dropdown = $("#gpNurseDiscipline" + clientIndex + '' + nurseIndex);
                    $dropdown.empty();
                    $dropdown.append($('<option>', {
                        value: '',
                        text: '--- Select ---'
                    }));
                    $.each(data.options, function(index, item) {
                        $dropdown.append($('<option>', {
                            value: item.name,
                            text: item.name
                        }));
                    });

                    let predefinedValue = $dropdown.data('value');
                    if (predefinedValue != '') {
                        $dropdown.val(predefinedValue);
                    }
                }
            }
        });
    }

    @if(LD::checkFeatureFlag('replace-auth-type-with-service-gpcalculator'))
    if (mode == 'edit') {
        $(document).ready(function() {
            for(i=0; i < nursesArr.length; i++) {
                let nurseArrVal = nursesArr[i].split('#');
                let clientId = $('#aoc_patient_id_' + nurseArrVal[0]).val();
                if (clientId < 0) {
                    $('#wholeHome').attr('disabled','disabled');
                    $('#clientPayer_' + nurseArrVal[0]).off().change(function() {
                        getServices(clientId, nurseArrVal[0], nurseArrVal[1]);
                    });
                }
                getServices(clientId, nurseArrVal[0], nurseArrVal[1]);
            }
        });
    }
    @endif

    function loadPayerDropdown(isRespite = false, clientIndex) {
        $.ajax({
            url: "{{route('gpCalculator.loadPayerDropdown')}}?isRespite=" + isRespite,
            type: 'GET',
            success: function(data) {
                if (data.status) {
                    var $dropdown = $("#clientPayer_" + clientIndex);
                    $dropdown.empty();
                    $dropdown.append($('<option>', {
                        value: '',
                        text: '--- Select ---'
                    }));
                    $.each(data.options, function(index, item) {
                        $dropdown.append($('<option>', {
                            value: item.value,
                            text: item.text
                        }));
                    });

                    $('#respiteMsg_' + clientIndex).addClass('hide');
                    var predefinedValue = '';
                    if (isRespite == false) {
                        predefinedValue = selectedPatientPayer[clientIndex];
                    } else {
                        predefinedValue = selectedPatientRespitePayer[clientIndex];
                        if (
                            predefinedValue == '' ||
                            predefinedValue == undefined ||
                            predefinedValue == null
                        ) {
                            $('#respiteMsg_' + clientIndex).removeClass('hide');
                        }
                    }
                    if (predefinedValue != '' || predefinedValue != undefined || predefinedValue != null) {
                        $dropdown.val(predefinedValue);
                    }
                    $dropdown.multiselect("rebuild");

                    for(i=0; i < nursesArr.length; i++) {
                        let nurseArrVal = nursesArr[i].split('#');
                        let clientId = $('#aoc_patient_id_' + nurseArrVal[0]).val();
						if (clientIndex == nurseArrVal[0]) {
							getServices(clientId, nurseArrVal[0], nurseArrVal[1]);
						}
                    }
                }
            }
        });
    }

    $(document).ready(function(){
        $(document).on('change', '.respite', function() {
            loadPayerDropdown($(this).is(':checked'), $(this).data('index'));
        });
    });
</script>

@endsection
