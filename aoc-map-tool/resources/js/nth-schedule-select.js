// Shared "Select Schedule" (NTH schedules) multiselect handler.
// Reused by the Add Clinician form (resources/views/common/schedule_next_step.blade.php)
// and the Schedule Next Step outcome modal (resources/views/recruiterTracker/nextstep.blade.php).
// Both views load this file via a plain <script src> tag inside AJAX-loaded modal
// partials, so it can be injected into the page more than once per session —
// guard against redefining/rebinding on repeat injection.
(function () {
    if (window.NthScheduleSelect) {
        return;
    }

    function initNthScheduleSelect(options) {
        var $select = $(options.selectSelector);
        var $totalHours = $(options.totalHoursSelector);
        var $loadingGif = options.loadingGifSelector ? $(options.loadingGifSelector) : $();
        var $errorMessage = options.errorMessageSelector ? $(options.errorMessageSelector) : $();
        var autoPopulateHours = !!options.autoPopulateHours;
        var totalHours = 0;

        if ($select.data('multiselect')) {
            $select.multiselect('destroy');
        }

        function setTotalHours(value) {
            totalHours = value;
            $totalHours.val(totalHours);
            if (typeof options.onTotalHoursChange === 'function') {
                options.onTotalHoursChange(totalHours);
            }
        }

        $select.multiselect({
            includeSelectAllOption: true,
            buttonWidth: '100%',
            onChange: function (option, checked) {
                if (!autoPopulateHours) {
                    return;
                }
                var delta = parseFloat($(option).data('shifthours')) || 0;
                setTotalHours(totalHours + (checked ? delta : -delta));
            },
            onSelectAll: function (selectedOptions) {
                if (!autoPopulateHours) {
                    return;
                }
                var sum = totalHours;
                $.each(selectedOptions, function (index, option) {
                    sum += parseFloat($(option).data('shifthours')) || 0;
                });
                setTotalHours(sum);
            },
            onDeselectAll: function (deselectedOptions) {
                if (!autoPopulateHours) {
                    return;
                }
                var sum = totalHours;
                $.each(deselectedOptions, function (index, option) {
                    sum -= parseFloat($(option).data('shifthours')) || 0;
                });
                setTotalHours(sum);
            },
        });
        $select.multiselect('disable');

        function reset() {
            $select.empty();
            $select.multiselect('rebuild');
            $select.multiselect('disable');
            setTotalHours(0);
        }

        function loadForClient(clientId) {
            reset();
            $errorMessage.text('');
            $loadingGif.show();

            $.ajax({
                url: options.url,
                type: 'post',
                data: {
                    client_id: clientId,
                    _token: $('meta[name="csrf-token"]').attr('content'),
                },
                success: function (response) {
                    if (!response.length) {
                        if (autoPopulateHours) {
                            $totalHours.removeAttr('readonly');
                            $select.rules('remove', 'required');
                        }
                        $errorMessage.text('The schedule is empty');
                    } else {
                        $.each(response, function (index, value) {
                            $select.append(
                                '<option data-shifthours="' + value.ShiftHours + '" value="' + value.value + '">' + value.label + '</option>'
                            );
                        });
                        $select.multiselect('rebuild');
                        $select.multiselect('enable');
                        if (autoPopulateHours) {
                            $totalHours.attr('readonly', 'true');
                            $select.rules('add', {
                                required: true,
                                messages: {
                                    required: options.requiredMessage || 'This field is required.',
                                },
                            });
                        }
                    }
                    $loadingGif.hide();
                },
                error: function () {
                    $loadingGif.hide();
                },
            });
        }

        return {
            reset: reset,
            loadForClient: loadForClient,
        };
    }

    window.NthScheduleSelect = {
        init: initNthScheduleSelect,
    };
})();
