@php
    /**
     * @var \Illuminate\Support\Collection $items ordered OfferCompensationItem collection
     * @var bool $disabled
     * @var \Illuminate\Support\Collection $fieldOptions OfferField rows (section = compensation)
     * @var string $name POST field prefix, e.g. "compensation_items"
     */
@endphp
<style>
    .comp-amount-wrap {
        display: flex;
        align-items: center;
        border: 1px solid #ced4da;
        border-radius: .25rem;
        overflow: hidden;
    }
    .comp-amount-wrap .comp-amount-prefix {
        padding: 0 8px;
        color: #6c757d;
        line-height: 1;
        flex-shrink: 0;
    }
    .comp-amount-wrap .form-control {
        border: none;
        flex: 1 1 auto;
        min-width: 0;
    }
    .comp-amount-wrap .form-control:focus {
        box-shadow: none;
        outline: none;
    }
</style>
<div class="comp-items-block" data-name="{{ $name }}" data-next-index="{{ $items->count() }}">
    <table class="table table-sm comp-items-table">
        <thead>
            <tr>
                <th style="width:38%">Type</th>
                <th style="width:20%">Amount</th>
                <th>Notes</th>
                @unless($disabled)
                    <th style="width:30px"></th>
                @endunless
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr class="comp-item-row">
                    @if($disabled)
                        <td>{{ $item->label() }}</td>
                        <td>${{ number_format($item->amount, 2) }}</td>
                        <td>{{ $item->notes }}</td>
                    @else
                        <td>
                            <select class="form-control comp-item-type" name="{{ $name }}[{{ $index }}][type_key]">
                                <option value="">-- Select --</option>
                                @foreach($fieldOptions as $option)
                                    <option value="{{ $option->key }}" @selected($item->offer_field_id === $option->id)>{{ $option->label }}</option>
                                @endforeach
                            </select>
                            @if($item->offerField?->key === 'other')
                                <input type="text" class="form-control comp-item-custom mt-1"
                                       name="{{ $name }}[{{ $index }}][custom_label]"
                                       value="{{ $item->custom_label }}"
                                       placeholder="Describe compensation type..."
                                       aria-label="Other compensation type">
                            @endif
                        </td>
                        <td>
                            <div class="comp-amount-wrap">
                                <span class="comp-amount-prefix">$</span>
                                <input type="text" class="form-control" name="{{ $name }}[{{ $index }}][amount]" value="{{ $item->amount }}">
                            </div>
                        </td>
                        <td>
                            <input type="text" class="form-control" name="{{ $name }}[{{ $index }}][notes]" value="{{ $item->notes }}" placeholder="Optional">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-link text-danger comp-item-remove" title="Remove">&times;</button>
                        </td>
                    @endif
                </tr>
            @empty
                <tr class="comp-item-empty-row">
                    <td colspan="{{ $disabled ? 3 : 4 }}" class="text-muted">No compensation items.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @unless($disabled)
        <button type="button" class="btn btn-outline-primary btn-sm comp-item-add">+ Add Compensation Item</button>

        <script type="text/template" class="comp-item-template">
            <tr class="comp-item-row">
                <td>
                    <select class="form-control comp-item-type" name="{{ $name }}[__IDX__][type_key]">
                        <option value="">-- Select --</option>
                        @foreach($fieldOptions as $option)
                            <option value="{{ $option->key }}">{{ $option->label }}</option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <div class="comp-amount-wrap">
                        <span class="comp-amount-prefix">$</span>
                        <input type="text" class="form-control" name="{{ $name }}[__IDX__][amount]" placeholder="0.00">
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control" name="{{ $name }}[__IDX__][notes]" placeholder="Optional">
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-link text-danger comp-item-remove" title="Remove">&times;</button>
                </td>
            </tr>
        </script>
    @endunless
</div>

<script>
(function ($) {
    if (window.__compItemsBound) {
        return;
    }
    window.__compItemsBound = true;

    $(document).on('click', '.comp-item-add', function () {
        var $block = $(this).closest('.comp-items-block');
        var idx = parseInt($block.attr('data-next-index'), 10) || 0;
        var html = $block.find('.comp-item-template').html().split('__IDX__').join(idx);
        var $row = $(html);

        $block.find('tbody .comp-item-empty-row').remove();
        $block.find('tbody').append($row);
        $block.attr('data-next-index', idx + 1);
        $row.find('.comp-item-type').focus();
    });

    $(document).on('click', '.comp-item-remove', function () {
        var $block = $(this).closest('.comp-items-block');
        $(this).closest('tr').remove();

        if ($block.find('tbody .comp-item-row').length === 0) {
            var colspan = $block.find('thead th').length - 1;
            $block.find('tbody').append(
                '<tr class="comp-item-empty-row"><td colspan="' + colspan + '" class="text-muted">No compensation items.</td></tr>'
            );
        }
    });

    $(document).on('change', '.comp-item-type', function () {
        var $select = $(this);
        var $td = $select.closest('td');
        var $existing = $td.find('.comp-item-custom');

        if ($select.val() === 'other') {
            if ($existing.length) {
                $existing.focus();
                return;
            }

            var name = $select.attr('name').replace('[type_key]', '[custom_label]');
            var $input = $('<input type="text" class="form-control comp-item-custom mt-1" placeholder="Describe compensation type..." aria-label="Other compensation type">')
                .attr('name', name);
            $td.append($input);
            $input.focus();
        } else if ($existing.length) {
            $existing.remove();
        }
    });
})(jQuery);
</script>
