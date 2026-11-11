<?php
    use App\Helpers\LD;
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
        font-size: 16px;
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

    select[name^="clientPayer"] + .btn-group .multiselect-container,
    select[name^="gpNurseDiscipline"] + .btn-group .multiselect-container {
      width: 100% !important;
    }

    .multiselect-container .multiselect-search,
    .multiselect-container .input-group .form-control {
        font-size: 14px !important;
    }

    /* Base styles for desktop modal */
    .gp-calc-information-modal .modal-dialog {
      width: min(54vw, 1160px);
      margin: 6vh 1.2vw 0 auto !important;
    }

    .gp-calc-information-modal .modal-body {
      overflow-x: auto;
    }

    /* Up to ~1700px viewport width
       Covers smaller desktop viewports, including Full HD with 125% browser/OS scaling */
    @media (max-width: 1700px) {
      .gp-calc-information-modal .modal-dialog {
        width: min(56vw, 960px);
        margin: 3vh 0 0 auto !important;
      }
    }

    /* From 1701px to 2559px viewport width
       Covers standard desktop screens: Full HD, 2K, and similar without heavy scaling */
    @media (min-width: 1701px) and (max-width: 2559px) {
      .gp-calc-information-modal .modal-dialog {
        width: min(54vw, 1160px);
        margin: 8vh 1vw 0 auto !important;
      }
    }

    /* 2560px and wider viewport
       Covers large monitors and 4K screens */
    @media (min-width: 2560px) {
      .gp-calc-information-modal .modal-dialog {
        width: min(46vw, 1280px);
        margin: -4vh 4vw 0 auto !important;
      }
    }


</style>
<div class="gp-calculator-cnt">
    <form action="" id="nurseGPForm">
        <input type="hidden" name="gpId" id="gpId" value="{{$gpId}}" />
        <div id="clientSelectionContainer" class="clientSelectionContainer"></div>
        <div class="col-md-12 d-flex hide" id="addNewClientContainer">
            <button class="btn btn-primary ml-3" id="addClientBtn">Add Client</button>
        </div>
    </form>
</div>

<template id="gpCalcInformationPopupTpl">
    <x-modal>
        <x-slot name="id">getGPCalculatorInformationPopup</x-slot>
        <x-slot name="size">w50</x-slot>
        <x-slot name="title">Information</x-slot>
        <x-slot name="class">gp-calc-information-modal</x-slot>
        <x-slot name="body">
            <div class="gp-calc-info-popup">
                <div data-role="content"></div>
            </div>
        </x-slot>
        <x-slot name="footer">
        </x-slot>
    </x-modal>
</template>

@push('page-scripts')
    <script type="text/javascript">
      const newSource = @json(LD::checkFeatureFlag('change-data-source-for-payers-dropdown-list'));

        window.GpCalculatorConfig = {
            gpId: '{{$gpId}}',
            $: window.jQuery,
            //configuration needed for gp-calculator.js
            payers: @json($payers, true),
            branches: @json($branches ?? [], true),
            disciplines: @json(config('constant')['gpNurseDiscipline'] ?? [], true),
            gpMode: '<?php echo (isset($mode) && $mode=='edit') ? 'edit' : 'add';?>',
            selectedPatientPayer: @json($patientsPayer ?? [], true),
            selectedPatientRespitePayer: @json($patientsRespitePayer ?? [], true),
            useServiceLabel: {{LD::checkFeatureFlag('replace-auth-type-with-service-gpcalculator') ? 1 : 0}},
            renderEscalationGuideTable: @json(LD::checkFeatureFlag('add-GM-table-to-gp-calc')),
            popupBlock: 'gpCalcInformationPopupTpl',
            ideaTable: 'gpIdeaTable',
            escalationGuideTable: 'gpEscalationGuideTable',
            modalId: 'getGPCalculatorInformationPopup',
            permOnlyBlock: {identifierType: 'class', value: 'perm-only1'},
            renderPermOnly: {{LD::checkFeatureFlag('toggle-coverage-shifts') ? 'true' : 'false'}},
            useNewWeekLogic: {{LD::checkFeatureFlag('update-hours-logic-for-new-week') ? 'true' : 'false'}},

            //to map actions under gp-calculator.js
            actions: {
                addClinician: '#addNurseBtn',
                wholeHome: '#wholeHome',
                save: '#saveGp',
                calculate: '#calculate',
                clear: '#clear',
                showCalculationDetails: '#gpInformationPopupTag',
                addClient: 'addClientBtn',
            },
            routes: {
                getScheduleClient: '{{route('getScheduleClient')}}',
                getService: '{{route('gpCalculator.getService')}}',
                loadPayerDropdown: newSource ? '{{route('gp-calculatorV2.loadPayerDropdown')}}' : '{{route('gpCalculator.loadPayerDropdown')}}',
                getCandidateName: '{{route('gp-calculatorV2.getCandidateName')}}',
                saveGp: '{{route('gpCalculatorV2.saveGp')}}',
                calculate: '{{route('gpCalculatorV2.calculateGp')}}',
                requestWholeHome: '{{route('gp-calculator.request-whole-home')}}',
                loadScheduledHours: '{{route('gpCalculator.loadScheduleHours')}}',
                getGp: '{{route('gpCalculatorV2.get')}}',
            }
        }


    </script>
    <script src="{{ mix('js/gp/gp-calculator.js') }}"></script>
@endpush


@endsection
