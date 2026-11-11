const SELECT_PAYER_PLACEHOLDER = 'Select a payer from SPGM…';
const CONFIRM_STATE_CHANGE = 'Changing the state will reset all selected payers for this state. Do you want to proceed?';
const DUPLICATE_PAYER_ALERT = 'This payer is already selected for this state. Please choose a different one.';
const DUPLICATE_STATE_ALERT = 'This state is already configured. Please choose a different one.';

$( function() {
    $( '#payer_priority_container' ).sortable({
        items: 'div:not(.unsortable)',
        update: function(event, ui) {
            var container = ui.item.closest('.state_payer_priority_container');
            recalculateRanks(container);
        }
    });
    $('.ui.dropdown').not('.state-selector').dropdown({
        fullTextSearch: true
    }).parent().addClass('unsortable').find('div').addClass('unsortable');
    $('.ui.dropdown.state-selector').dropdown().parent().addClass('unsortable').find('div').addClass('unsortable');
} );

function recalculateRanks(container) {
    var state = container.find('.state-selector select').val() || 'STATE_PLACEHOLDER';
    container.find('.card-field-container').each(function(i) {
        var newIndex = i + 1;
        $(this).attr('id', 'payer-group-' + newIndex);
        var rankInput = $(this).find('input[name*="payer_priority_rank"]');
        rankInput.attr('name', 'payer_priority_rank[' + state + '][' + newIndex + ']');
        rankInput.val(newIndex);
        var payerSelect = $(this).find('select[name*="payer_priority_name"]');
        payerSelect.attr('name', 'payer_priority_name[' + state + '][' + newIndex + ']');
        $(this).find('.remove_priority').attr('id', 'payer-group' + newIndex);
    });
}

function updateStatePayers(state, container, payerSelectors) {
    var payersByState = $('#payer_priority_container').data('payers-by-state');
    var statePayers = state ? payersByState[state] : [];

    payerSelectors.each(function() {
        var $select = $(this);
        var currentValue = $select.val();
        $select.empty().append('<option value="">' + SELECT_PAYER_PLACEHOLDER + '</option>');

        var payersToShow = state ? statePayers : [];

        payersToShow.forEach(function(payer) {
            var selected = (payer === currentValue) ? 'selected="selected"' : '';
            $select.append('<option value="' + payer + '" ' + selected + '>' + payer + '</option>');
        });

        $select.dropdown('refresh');
        if (state) {
            $select.parent().addClass('search');
            $select.dropdown({fullTextSearch: true});
            $select.parent().addClass('unsortable').find('div').addClass('unsortable');
        }
    });
}

$(document).on('focus', '.state-selector select', function() {
    $(this).data('prev', $(this).val());
});

$(document).on('change', '.state-selector select', function() {
    var $select = $(this);

    if ($select.data('reverting')) {
        return;
    }

    var prevState = $select.data('prev');
    var state = $select.val();

    if (state !== '') {
        var isDuplicateState = false;
        $('.state-selector select').not($select).each(function() {
            if ($(this).val() === state) {
                isDuplicateState = true;
                return false;
            }
        });

        if (isDuplicateState) {
            alert(DUPLICATE_STATE_ALERT);
            $select.data('reverting', true);
            $select.dropdown('set selected', prevState || '');
            $select.data('reverting', false);
            return;
        }
    }

    var container = $select.closest('.state_payer_priority_container');
    var payerSelectors = container.find('.payer-selector select');

    if (prevState && prevState !== '' && prevState !== state) {
        if (!confirm(CONFIRM_STATE_CHANGE)) {
            $select.data('reverting', true);
            $select.dropdown('set selected', prevState);
            $select.data('reverting', false);
            return;
        }
    }

    // Clear all child payer selectors
    payerSelectors.each(function() {
        $(this).dropdown('clear');
    });

    recalculateRanks(container);
    updateStatePayers(state, container, payerSelectors);
    $select.data('prev', state);
});

$(document).on('click', '.addPayerPriority', function() {
    var container = $(this).closest('.state_payer_priority_container');
    var lastGroup = container.find('.card-field-container').last();
    var lastId = lastGroup.attr('id');
    var index = 1;

    if (lastId) {
        index = parseInt(lastId.split('-').pop()) + 1;
    }

    var template = document.getElementById('payer-priority-row-template');
    var templateHtml = template.innerHTML.replace(/INDEX/g, index);
    var newGroup = $(templateHtml);
    newGroup.attr('id', 'payer-group-' + index);

    var payerSelect = newGroup.find('select[name^="payer_priority_name"]');

    newGroup.insertBefore(container.find('.button-container').first());

    // Re-initialize dropdown
    payerSelect.dropdown({
        fullTextSearch: true
    }).parent().addClass('unsortable').find('div').addClass('unsortable');

    // Populate the new dropdown if a state is selected
    var stateSelector = container.find('.state-selector select');
    var state = stateSelector.val();
    if (state) {
        updateStatePayers(state, container, payerSelect);
        recalculateRanks(container);
    }
});

$(document).on('click', '#addPayerPriorityState', function() {
    var container = $('#payer_priority_container');
    var template = document.getElementById('state-priority-container-template');
    var newContainer = $(template.innerHTML);

    newContainer.insertBefore(container.find('.button-container').last());

    // Initialize dropdowns
    newContainer.find('.ui.dropdown.state-selector').dropdown().parent().addClass('unsortable').find('div').addClass('unsortable');
    newContainer.find('.ui.dropdown').not('.state-selector').dropdown({
        fullTextSearch: true
    }).parent().addClass('unsortable').find('div').addClass('unsortable');
});

$(document).on('click', '.remove_state', function(e) {
    e.preventDefault();
    var container = $(this).closest('.state_payer_priority_container');
    container.remove();
});

$(document).on('click', '.toggle_expand_collapse', function(e) {
    e.preventDefault();
    var $link = $(this);
    var container = $link.closest('.state_payer_priority_container');
    var elementsToToggle = container.find('.card-field-container, .button-container, .heading-container, .note');

    if ($link.text() === 'Collapse All') {
        elementsToToggle.hide();
        $link.text('Expand All');
    } else {
        elementsToToggle.show();
        $link.text('Collapse All');
    }
});

$(document).on('change', '.payer-selector select', function() {
    var $currentSelect = $(this);
    var currentValue = $currentSelect.val();
    if (!currentValue) return;

    var container = $currentSelect.closest('.state_payer_priority_container');
    var otherSelects = container.find('.payer-selector select').not($currentSelect);
    var isDuplicate = false;

    otherSelects.each(function() {
        if ($(this).val() === currentValue) {
            isDuplicate = true;
            return false;
        }
    });

    if (isDuplicate) {
        alert(DUPLICATE_PAYER_ALERT);
        $currentSelect.dropdown('clear');
    }
});

$(document).on('click', '.remove_priority', function(e) {
    e.preventDefault();
    var container = $(this).closest('.state_payer_priority_container');
    $(this).closest('.card-field-container').remove();
    // Recalculate ranks
    recalculateRanks(container);
});

