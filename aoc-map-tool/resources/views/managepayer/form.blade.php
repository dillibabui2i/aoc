<?php
use App\Helpers\H;
?>
@extends('layouts.myaccount')
@section('title', 'Manage Payer')
@section('content')
    <style>
        .gross-margin-wrapper {
            background: #f4f7fb;
            border: 1px solid #d9e2ef;
            border-radius: 8px;
            padding: 16px;
        }

        .gross-margin-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: #fff;
        }

        .gross-margin-table thead th {
            background: #215eac;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            text-align: center;
            vertical-align: middle;
            padding: 10px 10px;
            border: 1px solid #29518a;
            white-space: nowrap;
        }

        .gross-margin-table tbody td {
            border: 1px solid #dde6f0;
            padding: 10px;
            vertical-align: middle;
            background: #fff;
        }

        .gross-margin-row:nth-child(even) td {
            background: #f8fbff;
        }

        .gm-select {
            min-width: 180px;
            height: 37px;
            border-radius: 6px;
            border: 1px solid #ccd6e2;
            font-size: 14px;
            z-index: 9999999;
        }

        .gm-input {
            height: 25px;
            text-align: center;
            padding-right: 24px;
            border-radius: 6px;
            border: 1px solid #ccd6e2;
            font-size: 12px;
        }

        .percent-input-wrapper {
            position: relative;
            min-width: 92px;
        }

        .percent-symbol {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
            font-size: 13px;
        }

        .btn-remove-row {
            width: 28px;
            height: 28px;
            border: none;
            border-radius: 50%;
            background: #ef4444;
            color: white;
            font-size: 18px;
            line-height: 1;
            cursor: pointer;
            transition: .2s;
        }

        .btn-remove-row:hover {
            background: #dc2626;
        }

        .min-w-120 {
            min-width: 120px;
        }

        .min-w-220 {
            min-width: 220px;
        }

        .lob-btn {
            background-color: #d9e2ef;
            color: black;
            border: 1px dashed;
            border-radius: 16px;
        }

        .multiselect-native-select,
        .btn-group {
            width: 100% !important;
        }

        .gross-margin-wrapper {
            overflow: visible !important;
        }

        .error-message-area {
            margin: 0 auto;
            font-weight: 600;
            color: crimson;
        }
    </style>
<div class="container-fluid">
    <div class="row">
        <div class="form-group col-md-6 head-title">
            <h1 class="h3 mb-2 text-gray-800">Edit Payer</h1>
            <a href="{{ route('managePayer') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
        </div>
    </div>
    <div>
        <form method="POST" id="edit-payer-data">
        <input type="hidden" name="id" id="id" value="{{$payerData->id}}">
        @csrf
            <div class="form-row">
                <div class="form-group col-md-12">
                    <label for="payer_name" class="text-left">Payer Name</label>
                    <input type="text" name="payer_name" id="payer_name"
                    value="{{$payerData->name}}" class="form-control" disabled>
                </div>
                <div class="form-group col-md-12">
                    <label for="timely_filing" class="text-left">State</label>
                    <select type="text" name="state" id="state" class="form-control">
                        @foreach(config('constant.states') as $state)
                            <option @selected($payerData->state == $state)>{{$state}}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="table-responsive gross-margin-wrapper">

                <table class="table gross-margin-table align-middle">
                    <thead>
                    <tr>
                        <th class="min-w-120">LOB</th>
                        <th class="min-w-220">Branches</th>
                        <th>Recruiter &<br>Coverage Target</th>
                        <th>Recruiter<br>Escalated</th>
                        <th>TL/Manager<br>Escalated</th>
                        <th>TL/Manager Whole<br>Home Escalated</th>
                        <th>Exec Team<br>Escalated</th>
                        <th>Coverage<br>Escalated</th>
                        <th>Coverage TL/Manager<br>Escalated</th>
                        <th>Coverage TL/Manager Whole<br>Home Escalated</th>
                        <th>Coverage Exec Team<br>Escalated</th>
                    </tr>
                    </thead>

                    <tbody id="grossMarginTableBody">

                    @foreach($rows as $row)

                        <tr class="gross-margin-row">

                            <input type="hidden"
                                   name="rows[{{ $loop->index }}][id]"
                                   value="{{ $row->id }}">

                            <td>
                                <select name="rows[{{ $loop->index }}][lob]" class="form-select gm-select">
                                    @foreach($lob as $item)
                                        <option value="{{$item['id']}}" @selected($item['id'] === $row->lob_enum?->value)>
                                            {{$item['name']}}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            <td>
                                <select
                                    name="rows[{{ $loop->index }}][branch_ids][]"
                                    class="branch-select-picker form-select gm-select"
                                    data-live-search="true"
                                    data-placeholder="Select"
                                    id="payer-branch-{{ $loop->index }}"
                                    multiple
                                >
                                    @foreach($branches as $branch)
                                        <option value="{{$branch['id']}}" @selected(in_array($branch['id'], $row->branch_ids ?? []))>
                                            {{$branch['name']}}
                                        </option>
                                    @endforeach
                                </select>
                            </td>

                            @php
                                $fields = [
                                    'recruiter_coverage_target',
                                    'recruiter_escalated',
                                    'tl_manager_escalated',
                                    'tl_manager_whole_home_escalated',
                                    'exec_team_escalated',
                                    'coverage_escalated',
                                    'coverage_tl_manager_escalated',
                                    'coverage_tl_manager_whole_home_escalated',
                                    'coverage_exec_team_escalated',
                                ];
                            @endphp

                            @foreach($fields as $field)
                                <td>
                                    <div class="percent-input-wrapper">

                                        <input
                                            type="number"
                                            inputmode="decimal"
                                            step="0.1"
                                            min="0"
                                            max="100"
                                            class="form-control gm-input"
                                            name="rows[{{ $loop->parent->index }}][{{ $field }}]"
                                            value="{{ $row->$field }}"
                                        >

                                        <span class="percent-symbol">%</span>

                                    </div>
                                </td>
                            @endforeach

                        </tr>

                    @endforeach

                    </tbody>
                </table>

                <div class="mt-3 d-flex gap-2">

                    <button
                        type="button"
                        id="addGrossMarginRow"
                        class="btn lob-btn"
                    >
                        + Add LOB
                    </button>
                    <span id="error-message-area" class="error-message-area"></span>
                </div>

            </div>

            <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
        </form>
    </div>
<script type="text/javascript">

    const branches = @json($branches);
    const lob = @json($lob);

    $(document).ready(function() {
        $('.branch-select-picker').multiselect({
            includeSelectAllOption: false,
            nonSelectedText: 'Select Branch'
        });

        $('#edit-payer-data').validate({
            rules: {
                state: {
                    required: true,
                },
            },
            errorPlacement: function(error, element) {
                error.appendTo(element.closest('.form-group').find('.error-message'));
            },
            submitHandler: function(form) {
                $.ajax({
                    url: '{{ route("managepayerdata.update") }}',
                   type: 'POST',
                    data: $(form).serialize(),
                    success: function(response) {
                        if(response.success == 1){
                            window.location.href = '{{ route("managePayer") }}';
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                        $('#error-message-area').text(xhr.responseJSON.message);
                    }
                });
            }
        });
    });

    let rowIndex = {{ count($rows) }};

    $('#addGrossMarginRow').on('click', function () {

        let row = `
            <tr class="gross-margin-row">

                <td>
                    <select
                        name="rows[${rowIndex}][lob]"
                        class="form-select gm-select"
                        data-placeholder="Select LOB"
                    >
                        <<option>Select LOB</option>
                        ${getOptions(lob)}
                    </select>
                </td>

                <td>
                    <select
                        name="rows[${rowIndex}][branch_ids][]"
                        class="branch-select-picker form-select gm-select"
                        data-live-search="true"
                        data-placeholder="Select Branch"
                        id="payer-branch-${rowIndex}"
                        multiple
                    >
                       ${getOptions(branches)}
                    </select>
                </td>

                ${generateInputs(rowIndex)}

                <td class="text-center">
                    <button
                        type="button"
                        class="btn-remove-row"
                    >
                        ×
                    </button>
                </td>

            </tr>
        `;

        let $row = $(row);

        $('#grossMarginTableBody').append($row);

        let $select = $row.find('.branch-select-picker');

        $select.multiselect({
            includeSelectAllOption: false,
            nonSelectedText: 'Select Branch'
        });

        rowIndex++;
    });

    function generateInputs(index) {
        let fields = [
            'recruiter_coverage_target',
            'recruiter_escalated',
            'tl_manager_escalated',
            'tl_manager_whole_home_escalated',
            'exec_team_escalated',
            'coverage_escalated',
            'coverage_tl_manager_escalated',
            'coverage_tl_manager_whole_home_escalated',
            'coverage_exec_team_escalated',
        ];

        let html = '';

        fields.forEach(field => {

            html += `
                <td>
                    <div class="percent-input-wrapper">

                        <input
                            type="number"
                            inputmode="decimal"
                            step="0.1"
                            min="0"
                            max="100"
                            placeholder="0.0"
                            class="form-control gm-input"
                            name="rows[${index}][${field}]"
                        >

                        <span class="percent-symbol">%</span>

                    </div>
                </td>
            `;
        });

        return html;
    }

    function getOptions(data) {
        let html = '';

        data.forEach(item => {

            html += `
            <option value="${item.id}">
                ${item.name}
            </option>
        `;
        });

        return html;
    }

    function initMultiselect(context) {
        $(context).find('.branch-select-picker').each(function () {
            $(this).multiselect({
                includeSelectAllOption: true
            });
        });
    }

    $(document).on('click', '.btn-remove-row', function () {
        $(this).closest('tr').remove();
    });
</script>
@endsection
