<x-modal>
    <x-slot name="id">manageClassCategories</x-slot>
    <x-slot name="size">w60</x-slot>
    <x-slot name="title">Manage Class Types</x-slot>
    <x-slot name="class"></x-slot>
    <x-slot name="body">
        <div id="classCategoryFormDiv">
            <div class="legend" id="class-categories-list"></div>

            <form id="class-category-form" class="mt-3">
                @csrf

                <div class="form-group">
                    <label for="class_category_name">Class Name</label>
                    <input
                        type="text"
                        id="class_category_name"
                        name="name"
                        class="form-control"
                        required
                    >
                    <div class="invalid-feedback"></div>
                </div>
                <div class="form-group" id="class_category_color_group">
                    <label for="class_category_color_trigger">Color</label>

                    <div class="color-picker-wrapper">
                        <button
                            type="button"
                            id="class_category_color_trigger"
                            class="color-picker-trigger"
                        >
                            <span class="color-picker-swatch"></span>
                            <span class="color-picker-value">Select color...</span>
                        </button>

                        <input
                            type="hidden"
                            id="class_category_color"
                            name="color"
                            required
                        >

                        <div id="class_category_color_palette" class="color-picker-popup d-none">
                            <div class="color-picker-shades"></div>
                        </div>
                    </div>

                    <div class="invalid-feedback"></div>
                </div>

                <div class="row pt-2">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-primary" id="class-category-submit-btn">
                            Add
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </x-slot>
    <x-slot name="footer">
        <!-- Footer of the modal -->
    </x-slot>
</x-modal>

<style>
    .legend {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        flex-direction: column;
    }

    .legend-item {
        display: flex;
        align-items: center;
        padding: 4px 10px 4px 8px;
        border-radius: 4px;
        background: #f5f5f5;
        font-size: 13px;
    }

    .legend-dot {
        width: 12px;
        height: 12px;
        margin-right: 6px;
        border-radius: 2px;
        background: color-mix(in srgb, var(--category-color) 20%, transparent);
        border-left: 3px solid var(--category-color);
        flex-shrink: 0;
    }

    .legend-item .legend-actions {
        margin-left: auto;
        white-space: nowrap;
    }

    .legend-item .legend-actions a {
        margin-left: 4px;
        color: #6c757d;
    }
    #class_category_name {
        width: 55%;
    }
    #class-category-form .form-group {
        flex-wrap: wrap;
    }
    #class-category-form .invalid-feedback {
        flex-basis: 100%;
    }

    .color-picker-wrapper {
        position: relative;
        width: 55%;
    }

    .color-picker-trigger {
        display: flex;
        align-items: center;
        width: 100%;
        gap: 8px;
        padding: 6px 10px;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 4px;
        cursor: pointer;
    }

    .color-picker-trigger:hover {
        border-color: #0d99ff;
    }

    .color-picker-swatch {
        width: 20px;
        height: 20px;
        border-radius: 3px;
        border: 1px solid rgba(0, 0, 0, 0.15);
        flex-shrink: 0;
        background: #fff;
    }

    .color-picker-value {
        font-size: 13px;
        color: #495057;
    }

    .color-picker-popup {
        position: absolute;
        z-index: 1060;
        bottom: calc(100% + 4px);
        left: 0;
        padding: 10px;
        background: #fff;
        border: 1px solid #ced4da;
        border-radius: 6px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        min-width: 260px;
    }

    .color-picker-shades {
        display: flex;
        gap: 4px;
    }

    .color-picker-shades .color-family-column {
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .class-category-color-option {
        width: 25px;
        height: 20px;
        padding: 0;
        border: 1px solid rgba(0, 0, 0, 0.15);
        border-radius: 3px;
        cursor: pointer;
        transition: box-shadow 0.15s ease-in-out, border-color 0.15s ease-in-out;
    }

    .class-category-color-option:hover {
        border-color: #0d99ff;
    }

    .class-category-color-option:focus {
        outline: none;
        box-shadow: 0 0 0 0.2rem rgba(13, 153, 255, 0.35);
    }

    .class-category-color-option.selected {
        border-color: #0d99ff;
        box-shadow: 0 0 0 0.2rem rgba(13, 153, 255, 0.35);
    }
</style>

<script>
    function loadClassCategories() {
        $.ajax({
            url: '{{ route('managetrachventclass.class-categories.index') }}',
            method: 'GET',
            success: function (response) {
                renderClassCategories(response.data);
            },
            error: function () {
                $('#class-categories-list').html(
                    '<div class="alert alert-danger">Unable to load class types.</div>'
                );
            }
        });
    }

    function renderClassCategories(classCategories) {
        let html = '';

        classCategories.forEach(function (classCategory) {
            html += `
                <div class="legend-item">
                    <div class="legend-dot" style="--category-color: ${escapeHtml(classCategory.color)};"></div>
                    <span>${escapeHtml(classCategory.name)}</span>

                    ${classCategory.is_custom
                        ? renderClassCategoryActions(classCategory)
                        : `<span class="legend-actions">System</span>`
                    }
                </div>
            `;
        });

        $('#class-categories-list').html(html);
    }

    function renderClassCategoryActions(classCategory) {
        return `<span class="legend-actions">
            <a
                href="javascript:void(0)"
                class="delete-class-category"
                title="Remove"
                data-id="${classCategory.id}"
            ><i class="fa fa-trash-o" aria-hidden="true"></i></a>
        </span>`;
    }

    function escapeHtml(value) {
        return $('<div>').text(value ?? '').html();
    }

    function resetClassCategoryForm() {
        $('#class-category-form')[0].reset();
        $('#class_category_color').val('');
        hideColorPickerPopup();
        setColorPickerValue('', 'Select color...');
        clearClassCategoryFormErrors();
    }

    function clearClassCategoryFormErrors() {
        $('#class-category-form .form-control').removeClass('is-invalid');
        $('#class-category-form .invalid-feedback').removeClass('d-block').text('');
    }

    function showClassCategoryFormErrors(errors) {
        clearClassCategoryFormErrors();

        Object.keys(errors || {}).forEach(function (field) {
            const message = Array.isArray(errors[field]) ? errors[field][0] : errors[field];

            $('#class-category-form [name="' + field + '"]')
                .addClass('is-invalid')
                .closest('.form-group')
                .find('.invalid-feedback')
                .text(message)
                .addClass('d-block');
        });
    }

    function hslToHex(hue, saturation, lightness) {
        const s = saturation / 100;
        const l = lightness / 100;
        const k = n => (n + hue / 30) % 12;
        const a = s * Math.min(l, 1 - l);
        const f = n => l - a * Math.max(-1, Math.min(k(n) - 3, Math.min(9 - k(n), 1)));

        const toHex = n => Math.round(f(n) * 255).toString(16).padStart(2, '0');

        return `#${toHex(0)}${toHex(8)}${toHex(4)}`;
    }

    const SHADE_COUNT = 10;
    const SHADE_MIN_LIGHTNESS = 25;
    const SHADE_MAX_LIGHTNESS = 75;

    function getShadeLightnesses() {
        const step = (SHADE_MAX_LIGHTNESS - SHADE_MIN_LIGHTNESS) / (SHADE_COUNT - 1);

        return Array.from({ length: SHADE_COUNT }, (_, index) => SHADE_MIN_LIGHTNESS + index * step);
    }

    // Red excluded per design request; families ordered light hue rotation, gray last.
    const colorFamilies = {
        orange:   { hue: 25,  saturation: 70 },
        amber:    { hue: 40,  saturation: 70 },
        yellow:   { hue: 50,  saturation: 70 },
        lime:     { hue: 90,  saturation: 70 },
        green:    { hue: 135, saturation: 70 },
        teal:     { hue: 175, saturation: 70 },
        cyan:     { hue: 190, saturation: 70 },
        azure:    { hue: 205, saturation: 70 },
        blue:     { hue: 220, saturation: 70 },
        indigo:   { hue: 245, saturation: 70 },
        purple:   { hue: 275, saturation: 70 },
        violet:   { hue: 295, saturation: 70 },
        magenta:  { hue: 320, saturation: 70 },
        brown:    { hue: 20,  saturation: 40 },
        gray:     null
    };

    function makeColorOption(hex, onSelect) {
        return $('<button>', {
            type: 'button',
            class: 'class-category-color-option',
            css: { background: hex },
            title: hex,
            click() {
                $('.class-category-color-option').removeClass('selected');
                $(this).addClass('selected');
                onSelect(hex);
            }
        });
    }

    function setColorPickerValue(hex, label) {
        $('#class_category_color').val(hex);
        $('.color-picker-swatch').css('background', hex || '#fff');
        $('.color-picker-value').text(label || hex || 'Select color...');
    }

    function selectClassCategoryColor(hex) {
        setColorPickerValue(hex, hex);
        hideColorPickerPopup();
    }

    function renderColorPickerPopup() {
        const $shades = $('.color-picker-shades').empty();

        Object.keys(colorFamilies).forEach(function (family) {
            const config = colorFamilies[family];
            const $column = $('<div>', { class: 'color-family-column' }).appendTo($shades);

            // Light to dark, top to bottom.
            getShadeLightnesses().forEach(lightness => {
                const hex = config === null
                    ? hslToHex(0, 0, lightness)
                    : hslToHex(config.hue, config.saturation, lightness);

                makeColorOption(hex, selectClassCategoryColor).appendTo($column);
            });
        });
    }

    function hideColorPickerPopup() {
        $('#class_category_color_palette').addClass('d-none');
    }

    renderColorPickerPopup();

    $('#class_category_color_trigger').on('click', function (event) {
        event.stopPropagation();
        $('#class_category_color_palette').toggleClass('d-none');
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest('.color-picker-wrapper').length) {
            hideColorPickerPopup();
        }
    });

    $('#class-category-form').on('input', '.form-control', function () {
        $(this)
            .removeClass('is-invalid')
            .closest('.form-group')
            .find('.invalid-feedback')
            .removeClass('d-block');
    });

    $('#manageClassCategories').on('show.bs.modal', function () {
        loadClassCategories();
    });

    $('#manageClassCategories').on('hidden.bs.modal', function () {
        resetClassCategoryForm();
    });

    $('#class-category-submit-btn').on('click', function () {
        clearClassCategoryFormErrors();

        const data = {
            name: $('#class_category_name').val(),
            color: $('#class_category_color').val(),
            _token: $('meta[name="csrf-token"]').attr('content'),
        };

        const submitBtn = $('#class-category-submit-btn');
        submitBtn.attr('disabled', true).text('Please wait...');

        $.ajax({
            url: '{{ route('managetrachventclass.class-categories.store') }}',
            method: 'POST',
            data: data,
            success: function () {
                resetClassCategoryForm();
                loadClassCategories();
            },
            error: function (xhr) {
                if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.errors) {
                    showClassCategoryFormErrors(xhr.responseJSON.errors);
                    return;
                }

                alert('Unable to save class type.');
            },
            complete: function () {
                submitBtn.attr('disabled', false).text('Add');
            }
        });
    });

    $('#class-categories-list').on('click', '.delete-class-category', function () {
        if (!confirm('Are you sure you want to delete this class type?')) {
            return;
        }

        const id = $(this).data('id');

        $.ajax({
            url: '{{ url('managetrachventclass/class-categories') }}/' + id,
            method: 'DELETE',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
            },
            success: function () {
                loadClassCategories();
            },
            error: function (xhr) {
                const message = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Unable to delete class type.';
                alert(message);
            }
        });
    });
</script>
