$(document).ready(function () {
    const modalSelector = $('#createConsentTemplateModal');

    $('#createConsentTemplate').on('click', function (e) {
        resetForm();
        e.preventDefault();
        modalSelector.modal('show');
    });

    /** TinyMCE initialization **/
    tinymce.init({
        selector: 'textarea.tinymce',
        plugins: 'image code lists',
        toolbar: 'undo redo bold italic underline alignleft aligncenter alignright alignjustify code numlist bullist',
        height: 600,
        relative_urls : false,
        remove_script_host : false,
        convert_urls : true,
    });

    function submitForm(actionType) {
        let $form = $('#createConsentTemplateForm');
        let formData = new FormData($form[0]);
        $('#loadingGif').show();

        const editor = tinymce.get('signature');
        const html = editor ? editor.getContent() : '';

        formData.set('consentContent', html);
        formData.set('action', actionType);

        $.ajax({
            url: $form.data('url'),
            method: $form.attr('method'),
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            success: function(response) {
                showSuccess(response.message ?? 'Saved successfully');
                setTimeout(function() {
                    modalSelector.modal('hide');
                    window.location.reload();
                }, 2000)
                $('#loadingGif').hide();
            },
            error: function(xhr) {
                $('#loadingGif').hide();
                if (xhr.status === 422) {
                    let errors = xhr.responseJSON.errors;
                    let html = '<ul class="mb-0">';
                    $.each(errors, function(key, messages) {
                        html += `<li>${messages[0]}</li>`;
                    });
                    html += '</ul>';
                    showError(html);
                } else {
                    showError('Something went wrong. Please try again.');
                }
            },
            complete: function() {
                $('#loadingGif').hide();
                $button.find('.btn-text').removeClass('d-none');
                $button.find('.spinner-border').addClass('d-none');
                $button.prop('disabled', false);
            }
        });
    }

    $('#btnSavePublish').on('click', function() {
        submitForm('Published');
    });

    $('#btnSaveDraft').on('click', function() {
        submitForm('Draft');
    });

    $(document).on('click', '.js-consent-open-modal', function () {
        resetForm();

        const id   = $(this).data('id');
        const mode = $(this).data('mode');
        const showUrl = $(this).data('show-url');
        const deleteUrl = $(this).data('delete-url') || null;

        const $modal = $('#createConsentTemplateModal');
        const $form   = $('#createConsentTemplateForm');

        $modal.data('consent-id', id);
        $modal.data('mode', mode);
        $modal.data('delete-url', deleteUrl);

        $form.find('input[name="id"]').val(id);

        const editor = tinymce.get('signature');

        loadViewData(showUrl);

        if (mode === 'view') {
            $('#createConsentTemplateForm select, #createConsentTemplateForm input').prop('disabled', true);
            $('#btnSavePublish, #btnSaveDraft, #btnDeleteDraft').hide();

            if (editor) {
                const body = editor.getBody();
                body.setAttribute('contenteditable', 'false');
            }
        } else {
            $('#createConsentTemplateForm select, #createConsentTemplateForm input').prop('disabled', false);
            $('#btnSavePublish, #btnSaveDraft, #btnDeleteDraft').show();

            if (editor) {
                const body = editor.getBody();
                body.setAttribute('contenteditable', 'true');
            }
        }

        $modal.modal('show');
    });

    /***Delete Draft Consent Template ***/
    $('#btnDeleteDraft').on('click', function (e) {
        e.preventDefault();

        const $modal = $('#createConsentTemplateModal');
        const deleteUrl = $modal.data('delete-url');

        if (!deleteUrl) {
            $modal.modal('hide');
            return;
        }

        if (!confirm('Are you sure you want to delete this draft?')) {
            return;
        }

        $('#loadingGif').show();
        resetAlert();

        $.ajax({
            url: deleteUrl,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').val()
            },
            success: function () {
                window.location.reload();
            },
            error: function (xhr) {
                let msg = 'Unable to delete draft. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showError(msg);
                $('#loadingGif').hide();
            },
            complete: function () {
                $('#loadingGif').hide();
            }
        });
    });

    function loadViewData(url) {
        $('#loadingGif').show();

        $.ajax({
            url: url,
            method: 'GET',
            processData: false,
            contentType: false,
            success: function(response) {
                let result = response.data;

                const editor = tinymce.get('signature');
                if (editor) {
                    editor.setContent(result.content || '');
                } else {
                    $('#signature').val(result.content || '');
                }

                $('#createConsentTemplateForm select[name="type"]').val(result.type);
                $('#createConsentTemplateForm select[name="language"]').val(result.language);

                $('#loadingGif').hide();
            },
            error: function(xhr) {
                $('#loadingGif').hide();
                showError(xhr.message ?? 'Something went wrong. Please try again.');
            }
        });
    }

    function showSuccess(message) {
        $('#formAlert')
            .removeClass('d-none alert-danger')
            .addClass('alert-success')
            .html(message);
    }

    function showError(message) {
        $('#formAlert')
            .removeClass('d-none alert-success')
            .addClass('alert-danger')
            .html(message);
    }

    function resetAlert() {
        $('#formAlert')
            .addClass('d-none')
            .removeClass('alert-success alert-danger')
            .html('');
    }

    function resetForm() {
        const $form  = $('#createConsentTemplateForm');
        const $modal = $('#createConsentTemplateModal');

        if ($form.length) {
            $form[0].reset();
        }

        $form.find('select[name="type"]').val('');
        $form.find('select[name="language"]').val('');
        $form.find('input[name="id"]').val('');

        const editor = tinymce.get('signature');
        if (editor) {
            editor.setContent('');

            const body = editor.getBody();
            body.setAttribute('contenteditable', 'true');

        } else {
            $('#signature').val('');
        }

        $form.find('select[name="type"]').prop('disabled', false);
        $form.find('select[name="language"]').prop('disabled', false);


        $('#btnSavePublish, #btnSaveDraft, #btnDeleteDraft').show();

        $('#formAlert')
            .addClass('d-none')
            .removeClass('alert-danger alert-success')
            .text('');

        $modal.removeData('consent-id').removeData('mode').removeData('delete-url');
    }
});
