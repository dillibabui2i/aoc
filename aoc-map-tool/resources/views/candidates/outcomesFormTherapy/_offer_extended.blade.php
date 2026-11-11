<?php use App\Services\Offer\OfferResourcesService; ?>
<style>
    .ui-widget.ui-widget-content {
        z-index: 10000;
    }
</style>

<div class="col-lg-12 offer-extended-container">
    <div class="row pt-3">
        <div class="col-md-12 p-0 mt-0 tabs-container">
            @if($isClinicalCareManager)
                <div class="card mr-5 no-border p-0">
                    <div class="form-group row">
                        <div class="col-md-12">
                            <label class="form-label">Therapy Care Manager Notes</label>
                            <textarea name="ccm_note" class="form-control"></textarea>
                        </div>
                    </div>
                </div>
            @endif
            @if($isRecruiter && !$offer->isSent())
                    <div class="col-md-12 card-cnt p-0 d-flex mb-3">
                        <div class="card mr-5 no-border p-0 w-100">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label" for="offerExtendedFiles">Upload files to be attached to the offer letter</label>

                                    <div class="custom-file mb-3">
                                        <input type="file"
                                               class="custom-file-input offer-extended-files"
                                               id="offerExtendedFiles"
                                               name="offer_extended_files[]"
                                               multiple
                                               accept="application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"/>
                                        <span class="custom-file-label">Attachments (optional)</span>
                                        <small class="form-text text-muted">
                                            Up to 10 files, 50 MB in total. Allowed formats: PDF, doc, docx.
                                        </small>
                                        <div id="offerExtendedFilesError" class="text-danger mt-1" style="display: none;"></div>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul id="offerExtendedFilesList" class="list-unstyled mt-2 mb-0"></ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            @if($isRecruiter)
                <ul class="nav nav-tabs col-md-12" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#application" role="tab">Job Offer details
                            from clinical manager</a>
                    </li>
                    @if(!$offer->isSent())
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#chatWidget" role="tab">Recruiter review</a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" data-toggle="tab" href="#follow-up" role="tab">Follow-up</a>
                        </li>
                    @endif
                </ul>
            @endif

            @if($isRecruiter)
                <div class="tab-content p-3">
                    <div class="tab-pane active" id="application" role="tabpanel">
                        <div class="col-md-12 card-cnt p-0 d-flex mt-5">
                            <div class="card mr-5 no-border p-0">
                                <div class="form-group row input-no-padding">
                                    <div class="col-md-12">
                                        @include('offer._partials.field', ['data' => $data->get('employee'), 'disabled' => true])
                                    </div>
                                    <div class="col-md-12">
                                        @include('offer._partials.field', ['data' => $data->get('job_title'), 'disabled' => true])
                                    </div>
                                    <div class="col-md-8">
                                        @include('offer._partials.field', ['data' => $data->get('direct_report'), 'disabled' => true])
                                    </div>
                                    <div class="col-md-4">
                                        @include('offer._partials.field', ['data' => $data->get('effective_date'), 'disabled' => true])
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-md-8">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p class="font-weight-bold">Compensation</p>
                                            </div>
                                            <div class="col-md-12">
                                                @include('offer._partials.compensation_items', [
                                                    'items' => $offer?->originalCompensationItems ?? collect(),
                                                    'disabled' => true,
                                                    'fieldOptions' => $compensationFieldOptions,
                                                    'name' => 'compensation_items',
                                                ])
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="row">
                                            <div class="col-md-12">
                                                <p class="font-weight-bold">Caseload</p>
                                            </div>
                                            <div class="col-md-12">
                                                @include('offer._partials.field', ['data' => $data->get('visits_per_week'), 'disabled' => true, 'moneyFormat' => false])
                                            </div>
                                            <div class="col-md-12">
                                                @include('offer._partials.field', ['data' => $data->get('territory'), 'disabled' => true])
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @if(!$offer->isSent())
                        <div class="tab-pane" id="chatWidget" role="tabpanel">
                            <div class="col-md-12 card-cnt p-0 d-flex mt-5">
                                <div class="card mr-5 no-border p-0">
                                    <div class="form-group row">
                                        <div class="col-md-12">
                                            @include('offer._partials.field', ['data' => $finalData->get('employee'), 'disabled' => false])
                                        </div>
                                        <div class="col-md-12">
                                            @include('offer._partials.field', ['data' => $finalData->get('job_title'), 'disabled' => false])
                                        </div>
                                        <div class="col-md-8">
                                            @include('offer._partials.field', ['data' => $finalData->get('direct_report'), 'disabled' => false])
                                        </div>
                                        <div class="col-md-4">
                                            @include('offer._partials.field', ['data' => $finalData->get('effective_date'), 'disabled' => false])
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <div class="col-md-8">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <p class="font-weight-bold">Compensation</p>
                                                </div>
                                                <div class="col-md-12">
                                                    @include('offer._partials.compensation_items', [
                                                        'items' => $offer?->finalCompensationItems ?? collect(),
                                                        'disabled' => false,
                                                        'fieldOptions' => $compensationFieldOptions,
                                                        'name' => 'compensation_items',
                                                    ])
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="row">
                                                <div class="col-md-12">
                                                    <p class="font-weight-bold">Caseload</p>
                                                </div>
                                                <div class="col-md-12">
                                                    @include('offer._partials.field', ['data' => $finalData->get('visits_per_week'), 'disabled' => false])
                                                </div>
                                                <div class="col-md-12">
                                                    @include('offer._partials.field', ['data' => $finalData->get('territory'), 'disabled' => false])
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="tab-pane" id="follow-up" role="tabpanel">
                            <div class="col-md-12 card-cnt p-0 d-flex mt-5">
                                <div class="card mr-5 no-border p-0">
                                    <div class="form-group row">
                                        <div class="col-md-12">
                                            <label class="form-label">Follow-up notes</label>
                                            <textarea name="note" class="form-control"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    $.validator.addClassRules("required-field", {
        required: true
    });

    $('#jobViteOutcomeForm').validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            if (validator.numberOfInvalids()) {
                validator.errorList[0].element.focus();
            }
        },
        errorPlacement: function(error, element) {}
    });

    // Attachments for the Offer Extended outcome. The files themselves are sent
    // with the whole form on the "Save" button in candidates/nextstep.blade.php.
    (function () {
        var MAX_FILES = {{ OfferResourcesService::OFFER_EXTENDED_FILES_MAX_COUNT }};
        var MAX_TOTAL_SIZE = {{ OfferResourcesService::OFFER_EXTENDED_FILES_MAX_TOTAL_SIZE }};
        var ALLOWED_EXTENSIONS = @json(OfferResourcesService::OFFER_EXTENDED_FILES_MIMES);
        var INPUT_ID = 'offerExtendedFiles';

        // The input's own FileList is replaced by the browser on every pick, so the
        // current selection is kept here and written back into the input.
        var selectedFiles = [];

        function getInput() {
            return document.getElementById(INPUT_ID);
        }

        function toArray(fileList) {
            return fileList ? Array.prototype.slice.call(fileList) : [];
        }

        function getExtension(file) {
            return file.name.split('.').pop().toLowerCase();
        }

        function formatSize(bytes) {
            if (bytes < 1024) {
                return bytes + ' B';
            }
            if (bytes < 1024 * 1024) {
                return (bytes / 1024).toFixed(1) + ' KB';
            }
            return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
        }

        function getTotalSize(files) {
            var total = 0;
            files.forEach(function (file) {
                total += file.size;
            });
            return total;
        }

        function getValidationError(files) {
            if (!files.length) {
                return '';
            }

            if (files.length > MAX_FILES) {
                return 'You can attach up to ' + MAX_FILES + ' files.';
            }

            var notAllowed = files.filter(function (file) {
                return ALLOWED_EXTENSIONS.indexOf(getExtension(file)) === -1;
            }).map(function (file) {
                return file.name;
            });

            if (notAllowed.length) {
                return 'Not allowed format: ' + notAllowed.join(', ')
                    + '. Allowed formats: PDF, doc, docx.';
            }

            var totalSize = getTotalSize(files);
            if (totalSize > MAX_TOTAL_SIZE) {
                return 'Total size of the attachments is ' + formatSize(totalSize)
                    + ', it must not exceed 50 MB.';
            }

            return '';
        }

        function applySelection(files) {
            var input = getInput();
            if (!input) {
                return;
            }

            if (window.DataTransfer) {
                var dataTransfer = new DataTransfer();
                files.forEach(function (file) {
                    dataTransfer.items.add(file);
                });
                input.files = dataTransfer.files;
                selectedFiles = toArray(input.files);
            } else {
                // No DataTransfer support - the input keeps whatever the browser put there.
                selectedFiles = toArray(input.files);
            }

            render();

            if ($(input).closest('form').data('validator')) {
                $(input).valid();
            }
        }

        function render() {
            var $list = $('#offerExtendedFilesList');
            if (!$list.length) {
                return;
            }

            $list.empty();

            selectedFiles.forEach(function (file, index) {
                var $remove = $('<button/>', {
                    'type': 'button',
                    'class': 'btn btn-primary offer-extended-file-remove',
                    'data-index': index
                }).html('<i class="fa fa-trash-o mr-0" aria-hidden="true"></i>');

                $('<li/>', {'class': 'd-flex align-items-center justify-content-between border rounded px-2 py-1 mb-1'})
                    .append($('<span/>').text(file.name + ' (' + formatSize(file.size) + ')'))
                    .append($remove)
                    .appendTo($list);
            });

            var message = getValidationError(selectedFiles);
            $('#offerExtendedFilesError').text(message).toggle(message !== '');
        }

        $.validator.addMethod('offerExtendedFiles', function (value, element) {
            return getValidationError(toArray(element.files)) === '';
        }, 'Please check the attached files.');

        $.validator.addClassRules('offer-extended-files', {
            offerExtendedFiles: true
        });

        $(document)
            .off('change.offerExtendedFiles', '#' + INPUT_ID)
            .on('change.offerExtendedFiles', '#' + INPUT_ID, function () {
                var picked = toArray(this.files);

                // Keep the files picked earlier so the list can be built up in several steps.
                var merged = selectedFiles.slice();
                picked.forEach(function (file) {
                    var alreadySelected = merged.some(function (selected) {
                        return selected.name === file.name
                            && selected.size === file.size
                            && selected.lastModified === file.lastModified;
                    });

                    if (!alreadySelected) {
                        merged.push(file);
                    }
                });

                applySelection(merged);
            });

        $(document)
            .off('click.offerExtendedFiles', '.offer-extended-file-remove')
            .on('click.offerExtendedFiles', '.offer-extended-file-remove', function () {
                var index = parseInt($(this).data('index'), 10);
                var remaining = selectedFiles.filter(function (file, fileIndex) {
                    return fileIndex !== index;
                });

                applySelection(remaining);
            });

        render();
    })();
</script>
