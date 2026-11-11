@extends('layouts.myaccount')
@section('title', 'Manage Payer Rates')
@section('content')
    <!-- Begin Page Content -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@ttskch/select2-bootstrap4-theme@x.x.x/dist/select2-bootstrap4.min.css"> <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <style>
        .activity-log-container {
            width: 100%;
            overflow-y: scroll;
            height: 225px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .btn-del-row {
            margin-top: 2px;
        }
    </style>

    <div class="container-fluid">
        <!-- Page Heading -->
        <div class="row">
            <div class="form-group col-md-6 head-title">
                <h1 class="h3 mb-2 text-gray-800">
                    {{ isset($payerRates) ? 'Edit' : 'Add' }} Payer Rates
                </h1>
                <a href="{{ route('payerrates.list') }}" title="Back"><i class="fa fa-chevron-circle-left"></i></a>
            </div>
        </div>
        <!-- DataTales Example -->
        @if ($errors->any())
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">×</button>
                {!! implode('', $errors->all('<div>:message</div>')) !!}
            </div>
        @endif
        @if ($message = Session::get('error'))
            <div class="alert alert-danger">
                <button type="button" class="close" data-dismiss="alert">×</button>
                {{ $message }}
            </div>
        @endif
        @if ($message = Session::get('success'))
            <div class="alert alert-success">
                <button type="button" class="close" data-dismiss="alert">×</button>
                {{ $message }}
            </div>
        @endif
        <div>
            <form method="POST" id="add-form-validate" action="{{ isset($payerRates) ? route('payerrates.update') : route('payerrates.save') }}">
                @csrf
                @if(isset($payerRates))
                    @method('PUT')
                    <input type="hidden" name="id" value="{{ $payerRates->id }}">
                @endif
                <div class="form-row">
                    <div class="form-group col-md-7">
                        <label for="payer" class="col-sm-2 col-form-label">Payer</label>
                        <div class="col-sm-9">
                            <input type="hidden" id="payer_id" name="payer_id" value="{{ isset($payerRates) ? $payerRates->payer_id : '' }}">
                            <input @readonly(isset($payerRates)) type="text" class="form-control" id="payer" name="payer" value="{{ isset($payerRates) ? $payerRates->payer : '' }}" />
                        </div>
                    </div>

                    <div class="form-group col-md-7">
                        <label for="effectiveFrom" class="col-sm-2 col-form-label">Effective From</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control dt-picker" id="effectiveFrom" name="effectiveFrom" value="{{ isset($payerRates) ? $payerRates->effectiveFrom : '' }}" />
                        </div>
                    </div>

                    <div class="form-group col-md-7">
                        <label for="effectiveThrough" class="col-sm-2 col-form-label">Effective Through</label>
                        <div class="col-sm-9">
                            <input type="text" class="form-control dt-picker" id="effectiveThrough" name="effectiveThrough" value="{{ isset($payerRates) ? $payerRates->effectiveThrough : '' }}" />
                        </div>
                    </div>

                    <div class="form-group col-md-7">
                        <label for="comment" class="col-sm-2 col-form-label">Comment</label>
                        <div class="col-sm-9">
                            <textarea class="form-control" id="comment" name="comment">{{ isset($payerRates) ? $payerRates->comment : '' }}</textarea>                    </div>
                    </div>

                    <hr class="my-4">
                    <div class="col-md-5 offset-md-1">
                        <h5 class="text-gray-800 font-weight-bold mb-1 col-md-12">Services & Override Rates</h5>
                        <p class="text-muted small mb-3 col-md-12">
                            <i class="fa fa-refresh text-success mr-1"></i> Synced from Kantime > Payer Services. One rate per service.
                        </p>

                        <div class="row mb-2 font-weight-bold text-gray-700  d-none d-md-flex">
                            <div class="col-md-8">Service Name</div>
                            <div class="col-md-3">Override Rate</div>
                            <div class="col-md-1"></div>
                        </div>

                        <div id="svcRows"></div>

                        <div id="dupWarn" class="alert alert-warning py-2 px-3 mt-2" style="display: none; font-size: 13px;">
                            <i class="fa fa-exclamation-triangle mr-1"></i> This service already has a rate set. Duplicate services are not allowed.
                        </div>

                        <div class="form-group col-md-6">
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2 ml-auto " id="addBtn" onclick="addRow()">
                                <i class="fa fa-plus-circle mr-1"></i> Add Service Line
                            </button>
                        </div>
                    </div>
                </div>

                @if(isset($payerRates))
                    <div class="form-group col-md-7">
                        <label for="activity_log" class="col-sm-2 col-form-label text-left">Activity Log</label>
                        <div class="col-sm-9">
                            <div class="activity-log-container">
                                @if(count($logData) > 0)
                                    <table style="width: 100%;" class="table table-striped mb-0" aria-hidden="true">
                                        @foreach ($logData as $data)
                                            <tr>
                                                <td class="text-left" style="white-space: pre-line;">{!!$data['notes']!!}</td>
                                                <td class="text-left">{{date("M d Y", strtotime($data['created_at'])) . ' at ' . date("g:i A", strtotime($data['created_at']))}}</td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @else
                                    <p class="text-muted p-3 mb-0">No activity yet.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
                <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
            </form>
        </div>
    </div>

    <script type="text/javascript">
        const ALL_SERVICES = @json($services);

        let rowIndex = 0;

        function getUsedServices() {
            let used = [];
            $('.service-select').each(function() {
                let val = $(this).val();
                if(val) used.push(val);
            });
            return used;
        }

        function updateDropdowns() {
            let used = getUsedServices();

            $('.service-select').each(function() {
                let currentVal = $(this).val();

                $(this).find('option').each(function() {
                    let optVal = $(this).attr('value');
                    if(!optVal) return;

                    if(used.includes(optVal) && optVal !== currentVal) {
                        $(this).prop('disabled', true);
                    } else {
                        $(this).prop('disabled', false);

                        let originalService = ALL_SERVICES.find(s => s === optVal);
                        if(originalService) {
                            $(this).text(originalService.name);
                        }
                    }
                });
            });

            $('.service-select').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: "-- Select Service --"
            });

            $('#addBtn').prop('disabled', $('.svc-row').length >= ALL_SERVICES.length);
        }

        function addRow(serviceName = '', rate = 0) {
            const index = rowIndex++;
            let optionsHtml = '<option value="">-- Select Service --</option>';
            ALL_SERVICES.forEach(s => {
                const isSelected = serviceName === s ? 'selected' : '';
                optionsHtml += `<option value="${s}" ${isSelected}>${s}</option>`;
            });

            const rowHtml = `
            <div class="row no-gutters mb-2 align-items-center svc-row">
                <div class="col-md-8 pr-md-2 mb-2 mb-md-0">
                    <select name="services[${index}][service_name]" class="form-control service-select" onchange="updateDropdowns()">
                        ${optionsHtml}
                    </select>
                </div>
                <div class="col-md-3 pr-md-2 mb-2 mb-md-0">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">$</span>
                        </div>
                        <input type="number" name="services[${index}][rate]" class="form-control rate-input" step="0.01" min="0" placeholder="0.00" value="${rate ?? 0.00}">
                    </div>
                </div>
                <div class="col-md-1 text-md-right text-left">
                    <button type="button" class="btn btn-outline-danger btn-del-row" onclick="removeRow(this)" title="Remove">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            </div>
        `;

            $('#svcRows').append(rowHtml);
            updateDropdowns();
        }

        function removeRow(btnElement) {
            $(btnElement).closest('.svc-row').remove();
            updateDropdowns();
        }

        $(function() {
            let initialData = [];

            @if(isset($payerRates) && isset($payerRates->services))
                initialData = @json($payerRates->services).map(service => ({
                name: service?.service_name ?? '—',
                rate: service?.rate ?? '0.00',
            }));
            @else
                initialData = [];
            @endif

            initialData.forEach(item => {
                addRow(item.name, item.rate);
            });

            var payerFlag = false;
            @if(isset($payerRates)) payerFlag = true; @endif

            $("#payer").autocomplete({
                source: "{{ route('payerrates.getPayerId') }}",
                minLength: 2,
                select: function(event, ui) {
                    payerFlag = true;
                    $('#payer_id').val(ui.item.id);
                }
            });

            $("#add-form-validate").validate({
                rules: {
                    payer: "required",
                    effectiveFrom: "required",
                    effectiveThrough: "required",
                },
                submitHandler: function(form) {
                    let isInvalid = false;

                    $('.svc-row').each(function() {
                        let sId = $(this).find('.service-select').val();
                        let sRate = $(this).find('.rate-input').val();
                        if (!sId || !sRate) isInvalid = true;
                    });

                    if (isInvalid) {
                        alert("Please fill in all service names and rates, or remove empty lines.");
                        return false;
                    }
                    form.submit();
                }
            });

            $('.dt-picker').datepicker().bind('focus', function() {
                $('#ui-datepicker-div').appendTo($(this).parent());
            });
        });
    </script>
    <!-- /.container-fluid -->
@endsection
