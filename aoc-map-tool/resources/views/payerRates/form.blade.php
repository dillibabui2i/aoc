@extends('layouts.myaccount')
@section('title', 'Manage Payer Rates')
@section('content')
<!-- Begin Page Content -->

<style>
    .activity-log-container {
        width: 100%;
        overflow-y: scroll;
        height: 225px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
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
                <div class="form-group col-md-12">
                    <label for="payer" class="col-sm-2 col-form-label">Payer</label>
                    <div class="col-sm-9">
                        <input type="hidden" id="payer_id" name="payer_id" value="{{ isset($payerRates) ? $payerRates->payer_id : '' }}">
                        <input @readonly(isset($payerRates)) type="text" class="form-control" id="payer" name="payer" value="{{ isset($payerRates) ? $payerRates->payer : '' }}" />
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="effectiveFrom" class="col-sm-2 col-form-label">Effective From</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control dt-picker" id="effectiveFrom" name="effectiveFrom" value="{{ isset($payerRates) ? $payerRates->effectiveFrom : '' }}" />
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="effectiveThrough" class="col-sm-2 col-form-label">Effective Through</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control dt-picker" id="effectiveThrough" name="effectiveThrough" value="{{ isset($payerRates) ? $payerRates->effectiveThrough : '' }}" />
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="hhagroupprimaryrate" class="col-sm-2 col-form-label">HHA Group Primary Rate</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="hhagroupprimaryrate" name="hhagroupprimaryrate" value="{{ isset($payerRates) ? $payerRates->hhagroupprimaryrate : '' }}" />
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="cnagroupprimaryrate" class="col-sm-2 col-form-label">CNA Group Primary Rate</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="cnagroupprimaryrate" name="cnagroupprimaryrate" value="{{ isset($payerRates) ? $payerRates->cnagroupprimaryrate : '' }}" />
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="lvnlpnrate" class="col-sm-2 col-form-label">LVN/LPN Rate</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="lvnlpnrate" name="lvnlpnrate" value="{{ isset($payerRates) ? $payerRates->lvnlpnrate : '' }}" />
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="lvnlpnspecrate" class="col-sm-2 col-form-label">LVN/LPN SP Rate</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="lvnlpnspecrate" name="lvnlpnspecrate" value="{{ isset($payerRates) ? $payerRates->lvnlpnspecrate : '' }}" />
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="rnrate" class="col-sm-2 col-form-label">RN Rate</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="rnrate" name="rnrate" value="{{ isset($payerRates) ? $payerRates->rnrate : '' }}" />
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="rnspecrate" class="col-sm-2 col-form-label">RN SP Rate</label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="rnspecrate" name="rnspecrate" value="{{ isset($payerRates) ? $payerRates->rnspecrate : '' }}" />
                    </div>
                </div>

                <div class="form-group col-md-12">
                    <label for="comment" class="col-sm-2 col-form-label">Comment</label>
                    <div class="col-sm-9">
                        <textarea class="form-control" id="comment" name="comment">{{ isset($payerRates) ? $payerRates->comment : '' }}</textarea>                    </div>
                </div>

                @if(isset($payerRates))
                    <div class="form-group col-md-12">
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
            </div>
            <button type="submit" class="btn btn-primary pull-left mt-3">Save</button>
        </form>
    </div>
</div>

<script type="text/javascript">
// Wait for the DOM to be ready
$(function() {
    var payerFlag = false;

    @if(isset($payerRates))
        payerFlag = true;
    @endif

    $("#add-form-validate #payer").autocomplete({
        source: "{{ route('payerrates.getPayerId') }}",
        minLength: 2,
        select: function(event, ui) {
            payerFlag = true;
            $('#payer_id').val(ui.item.id);
        }
    });

    $('#add-form-validate #payer').keyup(function() {
        payerFlag = false;
        if ($(this).val() == '') {
            $('#payer_id').val('');
        }
    });
    $('#add-form-validate #payer').blur(function() {
        if (payerFlag == false) {
            // $(this).val('');
            $('#payer_id').val('');
        }
    });

    $.validator.addMethod('decimal', function(value, element) {
        return this.optional(element) || /^((\d+(\\.\d{0,2})?)|((\d*(\.\d{1,2}))))$/.test(value);
    }, "Please enter a correct number, format 0.00");

    $("#add-form-validate").validate({
        // Specify validation rules
        rules: {
            payer: "required",
            effectiveFrom: "required",
            effectiveThrough: "required",
            hhagroupprimaryrate: "required",
            cnagroupprimaryrate: "required",
            lvnlpnrate: "required",
            lvnlpnspecrate: "required",
            rnrate: "required",
            rnspecrate: "required",
            comment: "required",
        },
        errorPlacement: function() {
            error.addClass("text-danger");
            error.insertAfter(element);
        },
    });

    $('.dt-picker').datepicker().bind('focus', function() {
        $('#ui-datepicker-div').appendTo($(this).parent());
    });
});
</script>
<!-- /.container-fluid -->
@endsection
