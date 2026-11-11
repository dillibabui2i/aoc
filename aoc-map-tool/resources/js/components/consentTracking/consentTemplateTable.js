$(document).ready(function () {
    const $table = $('#consentTrackingDataTable');

    if (!$table.length) {
        return;
    }

    const ajaxUrl = $table.data('url');

    const table = $table.DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: ajaxUrl,
            type: 'GET',
            data: function (d) {
                const $form = $('#datatableFilterSearchForm');

                if (!$form.length) {
                    return;
                }

                $form.find('[name]').each(function () {
                    const $field = $(this);
                    const name = $field.attr('name');

                    let value = $field.val();

                    if (value === null || value === '' || (Array.isArray(value) && value.length === 0)) {
                        return;
                    }

                    if (!Array.isArray(value)) {
                        value = [value];
                    }

                    d[name] = value;
                });
            },
        },
        columns: [
            { data: 'id', name: 'id', visible: false, searchable: false },
            { data: 'type', name: 'type' },
            { data: 'version', name: 'version' },
            { data: 'status', name: 'status' },
            { data: 'language', name: 'language' },
            { data: 'created_by', name: 'created_by' },
            { data: 'created_at', name: 'created_at' },
            { data: 'updated_at', name: 'updated_at' },
            {
                data: 'actions',
                name: 'actions',
                orderable: false,
                searchable: false
            },
        ],
        order: [[0, 'desc']],
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        responsive: true,
    });

    $(document).on('change', '.js-dt-filter', function () {
        table.ajax.reload();
    });

    $(document).on('click', '#resetFilters', function () {
        $('.js-dt-filter').val('');
        table.ajax.reload();
    });
});
