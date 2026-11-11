@php
    use App\Helpers\H;
@endphp

<style>
  #candidatesDetails .is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
  }
</style>

<div class="row">
    @foreach($customFields as $field)
        @php
            $isState = $field->db_field_name === 'aoc_cf_state';
            $rawValue = $candidatesDetails->{$field->db_field_name} ?? '';

            $decoded = json_decode($rawValue, true);
            $value = is_array($decoded) ? $decoded : $rawValue;

            $selectedValues = is_array($value)
                ? $value
                : (is_string($value)
                    ? array_map(fn($v) => trim($v, "\" \t\n\r\0\x0B"), explode(',', trim($value, "[]")))
                    : []);
        @endphp

        <div class="form-group col-md-6" @if($isState) style="display:none;" @endif>
            <label for="{{ $field->db_field_name }}">{{ $field->frontend_label }}</label>

            {{-- TEXT --}}
            @if($field->input_type === 'text')
                @php
                    if ($field->db_field_name === 'aoc_cf_city') {
                        $city = $candidatesDetails->aoc_cf_city ?? '';
                        $state = $candidatesDetails->aoc_cf_state ?? '';
                        $displayValue = trim($city . ($state ? ', ' . $state : ''));
                    } else {
                        $displayValue = is_array($value) ? implode(', ', $value) : $value;
                    }
                @endphp

                <input type="text"
                       id="{{ $field->db_field_name }}"
                       class="form-control editable-field"
                       data-name="{{ $field->db_field_name }}"
                       data-type="{{ $field->input_type }}"
                       value="{{ $displayValue }}">
            @endif

            {{-- TEXTAREA --}}
            @if($field->input_type === 'textarea')
                <textarea
                        id="{{ $field->db_field_name }}"
                        class="form-control editable-field"
                        data-name="{{ $field->db_field_name }}"
                        data-type="{{ $field->input_type }}"
                        rows="3">{{ is_array($value) ? implode(', ', $value) : $value }}</textarea>
            @endif

            {{-- DATE --}}
            @if($field->input_type === 'date')
                @php
                    $dateValue = null;

                    if (!empty($rawValue)) {
                        $dateValue = \Carbon\Carbon::parse($rawValue)->format('m/d/Y');
                    }
                @endphp
                <input type="text"
                       id="{{ $field->db_field_name }}"
                       class="form-control datepicker"
                       data-name="{{ $field->db_field_name }}"
                       data-type="{{ $field->input_type }}"
                       value="{{ $dateValue }}"
                       autocomplete="off">
            @endif

            {{-- BOOLEAN --}}
            @if($field->input_type === 'boolean')
                <select class="form-control editable-field"
                        data-name="{{ $field->db_field_name }}"
                        data-type="{{ $field->input_type }}">
                    <option value="">Select One</option>
                    <option value="1" {{ (string)$value === '1' ? 'selected' : '' }}>Yes</option>
                    <option value="0" {{ (string)$value === '0' ? 'selected' : '' }}>No</option>
                </select>
            @endif

            {{-- DROPDOWN --}}
            @if($field->input_type === 'dropdown')
                <select class="form-control editable-field"
                        data-name="{{ $field->db_field_name }}"
                        data-type="{{ $field->input_type }}">
                    <option value="">Select One</option>
                    @foreach(explode("\n", trim($field->options ?? '')) as $option)
                        @php $trimmed = trim($option); @endphp
                        <option value="{{ $trimmed }}"
                                {{ $trimmed === $value ? 'selected' : '' }}>
                            {{ $trimmed }}
                        </option>
                    @endforeach
                </select>
            @endif

            {{-- MULTI DROPDOWN --}}
            @if($field->input_type === 'mdropdown')
                <select id="{{ $field->db_field_name }}"
                        class="form-control multiselect-editable editable-field"
                        multiple
                        data-name="{{ $field->db_field_name }}"
                        data-type="{{ $field->input_type }}">
                    @foreach(explode("\n", trim($field->options ?? '')) as $option)
                        @php $trimmed = trim($option); @endphp
                        <option value="{{ $trimmed }}"
                                {{ in_array($trimmed, $selectedValues, true) ? 'selected' : '' }}>
                            {{ $trimmed }}
                        </option>
                    @endforeach
                </select>
            @endif
        </div>
    @endforeach
</div>

<script type="text/javascript">
  $(function () {
    const $modal = $('#candidatesDetails');

    // -----------------------------
    // INIT MULTISELECT
    // -----------------------------
    $modal.find('.multiselect-editable').multiselect({
      includeSelectAllOption: true,
      enableFiltering: true,
      nonSelectedText: 'Select options',
      selectAllText: 'Select All',
      nSelectedText: 'selected',
      allSelectedText: 'All selected',
      buttonWidth: '100%',
      numberDisplayed: 100,
      buttonContainer: '<div class="btn-group w-100" />',
      buttonText: function (options, select) {
        if (options.length === 0) {
          return 'Select options';
        } else {
          let labels = [];
          options.each(function () {
            labels.push($(this).text());
          });
          return labels.join(', ');
        }
      },
    });

    $modal.find('.multiselect-editable').each(function () {
      const $btn = $(this).next('.btn-group').find('button.multiselect');
      $btn.css({
        'height': 'auto',
        'min-height': '40px',
        'white-space': 'normal',
        'line-height': '1.5',
        'overflow': 'visible',
      });
    });

    const canEdit = @json(H::canPermissions('editCustomFieldValue'));

    if (!canEdit) {
      $modal.find('.editable-field').prop('disabled', true);
      $modal.find('.multiselect-editable').multiselect('disable');
      return;
    }

    // -----------------------------
    // EDITABLE FIELDS (all others)
    // -----------------------------
    const editableChangeHandler = function () {
      const $field = $(this);
      const name = $field.data('name');
      const type = $field.data('type');
      let value = $field.val();

      if ($field.is('#aoc_cf_city') || name === 'aoc_cf_state') return;

      if ($field.is('select[multiple]')) {
        value = JSON.stringify(value);
      }

      updateField(name, value, type);
    };

    $modal.on('change', '.editable-field', editableChangeHandler);
    $modal.on('change', '.datepicker', editableChangeHandler);

    // -----------------------------
    //  CITY AUTOCOMPLETE
    // -----------------------------
    const $city = $('#aoc_cf_city');
    const $state = $('#aoc_cf_state');

    let cityFlag = true;
    let citySelected = false;

    if ($city.length) {
      $city.autocomplete({
        minLength: 2,
        source: function (request, response) {
          $.getJSON("{{ route('nthlanding.getGeoCities') }}", { term: $city.val() }, function (data) {
            cityFlag = data.length > 0;
            response($.map(data, function (item) {
              let parts = item.label.split(',');
              let city = parts[0].trim();
              let state = parts[1] ? parts[1].trim() : '';
              return {
                label: `${city}${state ? ', ' + state : ''}`,
                city: city,
                state: state,
              };
            }));
          });
        },
        select: function (event, ui) {
          cityFlag = true;
          citySelected = true;
          $city.off('change');
          const displayLabel = `${ui.item.city}${ui.item.state ? ', ' + ui.item.state : ''}`;
          $city.val(displayLabel);
          $city.data('pureCity', ui.item.city);
          $state.val(ui.item.state);

          updateField('aoc_cf_city', ui.item.city,  $city.data('type'));
          updateField('aoc_cf_state', ui.item.state, $state.data('type'));

          setTimeout(() => {
            $city.on('change', editableChangeHandler);
          }, 300);
          return false;
        },
        change: function (event, ui) {
          if (!ui.item) cityFlag = false;
        },
      });

      $city.on('blur', function () {
        if (!citySelected) {
          const pureCity = $city.data('pureCity');
          if (pureCity) {
            $city.val(`${pureCity}${$state.val() ? ', ' + $state.val() : ''}`);
          }
        }
        citySelected = false;
      });

      $city.keyup(function () {
        cityFlag = $(this).val() !== '';
      });

      $city.blur(function () {
        if (!cityFlag) $(this).val('');
      });
    }

    // -----------------------------
    // SAVE FUNCTION
    // -----------------------------
    function updateField(name, value, type) {
      const $input = $modal.find('[data-name="' + name + '"]');
      $input.removeClass('is-invalid');

      $.ajax({
        url: '{{ route('candidates.update-field') }}',
        type: 'PATCH',
        data: {
          _token: '{{ csrf_token() }}',
          candidate_id: '{{ $candidatesDetails->id }}',
          field: name,
          value: value,
          type: type
        },
        success: function () {
          showToast('Saved successfully', 'success');
        },
        error: function (xhr) {
          if (xhr.status !== 422) console.debug('Request failed', xhr.status);

          if (xhr.status === 422) {
            try {
              const response = JSON.parse(xhr.responseText);
              if (response.errors && response.errors.value) {
                const msg = response.errors.value.join('<br>');
                $input.addClass('is-invalid');
                showToast(msg, 'error');
                return;
              }
            } catch (e) {}
          }

          showToast('Failed to update field', 'error');
        },
      });
    }

    // -----------------------------
    // TOAST FUNCTION
    // -----------------------------
    function showToast(message, type = 'info') {
      const bg = type === 'success' ? '#28a745'
        : type === 'error' ? '#dc3545'
          : '#007bff';

      const toast = $(`
        <div style="position:fixed;bottom:30px;right:30px;
          background:${bg};color:white;padding:10px 20px;
          border-radius:6px;z-index:9999;max-width:400px;">
          ${message}
        </div>
      `);

      $('body').append(toast);
      setTimeout(() => toast.fadeOut(400, () => toast.remove()), 3000);
    }
  });
</script>