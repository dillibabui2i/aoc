(function (window, document) {
    'use strict';

    var STORAGE_KEY = 'globalLocationFilter';
    var BUCKET_SELECTOR = '.card[data-follows-global="true"]';
    var EVENT_APPLIED = 'global-filter:applied';
    var EVENT_CLEARED = 'global-filter:cleared';

    var toastEl = null;
    var toastTimer = null;
    var modalEl = null;
    var modalOnConfirm = null;
    // Guards bindLocalFilterChangeDetection() against misinterpreting a
    // 'change' event WE fired programmatically (see applyBranchAfterStateAjax)
    // as the user manually editing a bucket's local filter.
    var isSyncingFromGlobal = false;

    function getFilters() {
        try {
            var raw = localStorage.getItem(STORAGE_KEY);
            return raw ? JSON.parse(raw) : null;
        } catch (e) {
            return null;
        }
    }

    function isActive() {
        var filters = getFilters();
        if (!filters) {
            return false;
        }
        return hasValues(filters.state) || hasValues(filters.region) || hasValues(filters.branch);
    }

    // Accepts an array (current shape) or a legacy scalar string
    // (pre-multiselect localStorage payload) and reports non-emptiness.
    function hasValues(value) {
        if (Array.isArray(value)) {
            return value.length > 0;
        }
        return Boolean(value);
    }

    // Normalizes a filter field to an array, tolerating a legacy scalar
    // string left over in localStorage from before multi-select support.
    function toArray(value) {
        if (Array.isArray(value)) {
            return value;
        }
        return value ? [value] : [];
    }

    function saveFilters(filters) {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(filters));
    }

    function clearFilters() {
        localStorage.removeItem(STORAGE_KEY);
    }

    function readSelectValues(root) {
        return {
            state: getSelectValues(root, 'state'),
            region: getSelectValues(root, 'region'),
            branch: getSelectValues(root, 'branch')
        };
    }

    function getSelectValues(root, name) {
        var select = root.querySelector('select[name="' + name + '"]');
        if (!select) {
            return [];
        }
        return Array.prototype.slice.call(select.selectedOptions).map(function (opt) {
            return opt.value;
        });
    }

    function writeSelectValues(root, filters) {
        ['state', 'region', 'branch'].forEach(function (name) {
            var select = root.querySelector('select[name="' + name + '"]');
            if (!select) {
                return;
            }
            var values = toArray(filters && filters[name]);
            Array.prototype.slice.call(select.options).forEach(function (opt) {
                opt.selected = values.indexOf(opt.value) !== -1;
            });
            if (window.jQuery && window.jQuery.fn.multiselect) {
                window.jQuery(select).multiselect('refresh');
            }
        });
    }

    // Initializes bootstrap-multiselect on the filter bar's own selects.
    // Must run before writeSelectValues()/multiselect('refresh') below, so
    // the widget instance already exists with the right options attached.
    function initMultiselects(root) {
        if (!window.jQuery || !window.jQuery.fn.multiselect) {
            return;
        }
        var $ = window.jQuery;
        $(root).find('.selectpicker').multiselect({
            includeSelectAllOption: true
        });
        $(root).find('.selectpickersearch').multiselect({
            includeSelectAllOption: true,
            enableCaseInsensitiveFiltering: true
        });
    }

    // Restricts the filter bar's Branch field to those belonging to the
    // selected State(s), reusing the same route('getstatebranch') endpoint
    // every other State->Branch dropdown in the app already calls (it
    // already accepts multiple states as a comma-separated list). Branch
    // is only active once a State is selected - AC: no State selected
    // means Branch can't be used at all, not just "shows everything",
    // so an impossible State+Branch combination (e.g. TX state + a CO
    // branch, which silently returns no data with no error) can't happen.
    // Returns a `sync` function callers can re-invoke after programmatically
    // changing the State select (e.g. restoring a saved filter), since that
    // doesn't fire a 'change' event on its own.
    function bindStateBranchNarrowing(bar) {
        if (!window.jQuery) {
            return null;
        }
        var $ = window.jQuery;
        var $state = $(bar).find('select[name="state"]');
        var $branch = $(bar).find('select[name="branch"]');
        var url = bar.getAttribute('data-state-branch-url');
        if (!$state.length || !$branch.length || !url) {
            return null;
        }

        // Full, unfiltered branch list as rendered by the server -
        // restored (but left disabled) whenever no State is selected.
        var allBranchOptionsHtml = $branch.html();

        function rebuild(optionsHtml, keepSelected) {
            $branch.html(optionsHtml);
            Array.prototype.slice.call($branch[0].options).forEach(function (opt) {
                opt.selected = keepSelected.indexOf(opt.value) !== -1;
            });
            $branch.multiselect('rebuild');
        }

        function sync() {
            var states = Array.prototype.slice.call($state[0].selectedOptions).map(function (opt) {
                return opt.value;
            });

            if (states.length === 0) {
                rebuild(allBranchOptionsHtml, []);
                $branch.multiselect('disable');
                return;
            }

            var keepSelected = Array.prototype.slice.call($branch[0].selectedOptions).map(function (opt) {
                return opt.value;
            });

            $.ajax({
                url: url,
                type: 'GET',
                data: {
                    state: states.join(','),
                    _token: $('meta[name="csrf-token"]').attr('content')
                }
            }).done(function (response) {
                var html = (response || []).map(function (branch) {
                    return '<option value="' + branch.id + '">' + branch.FacilityName + '</option>';
                }).join('');
                rebuild(html, keepSelected);
                $branch.multiselect('enable');
            });
        }

        $state.on('change', sync);
        sync();

        return sync;
    }

    function toggleBucketBadges() {
        var active = isActive();
        document.querySelectorAll(BUCKET_SELECTOR).forEach(function (bucket) {
            var badge = bucket.querySelector('.badge-global');
            if (badge) {
                badge.style.display = active ? '' : 'none';
            }
        });
    }

    // Only reloads tables that are already initialized (bucket accordion
    function reloadBuckets() {
        document.querySelectorAll(BUCKET_SELECTOR).forEach(reloadSingleBucket);
    }

    function reloadSingleBucket(card) {
        if (!window.jQuery) {
            return;
        }
        var $ = window.jQuery;

        var refreshForm = $(card).find('[data-triggers-counter-refresh="true"] form');
        if (refreshForm.length) {
            refreshForm.trigger('submit');
            return;
        }

        if (!$.fn || !$.fn.DataTable) {
            return;
        }
        $(card).find('table[id^="dataTable-"]').each(function () {
            if ($.fn.DataTable.isDataTable(this)) {
                $(this).DataTable().ajax.reload(null, false);
            }
        });
    }

    // Lets a single bucket opt out of the global filter ("Local override")
    // or opt back in. Toggled by clicking either badge inside the card.
    function setBucketFollowsGlobal(card, follows) {
        card.setAttribute('data-follows-global', follows ? 'true' : 'false');

        var globalBadge = card.querySelector('.badge-global');
        var overrideBadge = card.querySelector('.badge-override');
        var active = isActive();

        if (globalBadge) {
            globalBadge.style.display = (follows && active) ? '' : 'none';
        }
        if (overrideBadge) {
            overrideBadge.style.display = follows ? 'none' : '';
        }

        // switching to override leaves the bucket's current data as-is
        if (follows) {
            prepopulateBucketLocalFilters(card);
            reloadSingleBucket(card);
        }
    }

    // The "Global"/"Local override" badges are informational only - clicking
    // them does not change a bucket's follow/override state. The only way
    // out of "Local override" is clearing the bucket's own local filter
    // (see bindLocalClearDetection below).

    // In-scope buckets currently in "Local override" state (data-follows-global="false").
    function getOverriddenCards() {
        return Array.prototype.slice.call(
            document.querySelectorAll('.card[data-follows-global="false"]')
        );
    }

    // Writes values into a single named field inside a bucket's local filter
    // container. Local state/region/branch fields are bootstrap-multiselect
    // (type: multiselect) selects, so setting .val() alone doesn't update
    // their rendered UI - refresh the widget after setting the value(s).
    function setLocalFilterValue(container, fieldName, values) {
        var $ = window.jQuery;
        var $field = $(container).find('[name="' + fieldName + '"]');
        if (!$field.length) {
            return;
        }
        $field.val(toArray(values));
        if ($.fn.multiselect && $field.data('multiselect')) {
            $field.multiselect('refresh');
        }
        // No else branch: intentionally never fire a plain 'change' here -
        // that would cascade into unrelated business-logic handlers bound
        // to this field's real 'change' event (e.g. dependent-dropdown
        // resets), the same reason the original code used the inert,
        // namespaced 'change.select2' trigger instead of a plain one. If
        // the multiselect widget isn't initialized yet, the value is
        // already set on the underlying <select> and will be picked up
        // once bootstrap-multiselect initializes on it.
    }

    // Some buckets (opt in via "stateTriggersAjax": true in
    // data-global-filter-fields, e.g. officeEvaluation/dashboard.blade.php)
    // load their Branch <select> options dynamically via an AJAX call bound
    // to a genuine 'change' event on the State field - Branch starts with
    // no <option>s at all, so setting its value has no effect until that
    // AJAX has rebuilt the options. Fire the real 'change' to trigger it,
    // then wait for the Branch <select>'s options to be rebuilt (via
    // MutationObserver) before applying the global filter's branch value.
    function applyBranchAfterStateAjax(container, stateField, branchField, branchValues) {
        var $ = window.jQuery;
        var $state = $(container).find('[name="' + stateField + '"]');
        var branchEl = $(container).find('[name="' + branchField + '"]')[0];
        if (!$state.length || !branchEl) {
            return;
        }

        var applied = false;
        var apply = function () {
            if (applied) {
                return;
            }
            applied = true;
            observer.disconnect();
            setLocalFilterValue(container, branchField, branchValues);
        };

        var observer = new MutationObserver(apply);
        observer.observe(branchEl, { childList: true });
        // Safety net in case the bucket's AJAX never resolves/rebuilds options.
        setTimeout(apply, 5000);

        // Guard: jQuery dispatches 'change' handlers synchronously within
        // trigger(), so this flag is only ever observed as true by handlers
        // running as a direct result of this specific trigger() call below.
        isSyncingFromGlobal = true;
        $state.trigger('change');
        isSyncingFromGlobal = false;
    }

    // Writes the global filter's state/region/branch into a single bucket's
    // own filter bar, using the field NAMES declared in its
    // data-global-filter-fields attribute (JSON, set by the same
    // $globalFilterFields convention every bucket's Blade template follows).
    function syncBucketFilterBar(bar, gf) {
        var fieldNames = {};
        try {
            fieldNames = JSON.parse(bar.getAttribute('data-global-filter-fields') || '{}');
        } catch (e) {
            fieldNames = {};
        }
        var stateField = fieldNames.state || 'state';
        var regionField = fieldNames.region || 'region';
        var branchField = fieldNames.branch || 'aoc_facility_id';

        // Some buckets (e.g. Jobvite/Therapist Applications) store their own
        // State options as full names ("Colorado") instead of the two-letter
        // codes ("CO") the global filter and most other buckets use -
        // declared via a "stateValueMap" (code -> name) in
        // data-global-filter-fields so we can translate before assigning,
        // otherwise .val() silently matches no <option> and selects nothing.
        var stateValues = gf.state;
        if (fieldNames.stateValueMap) {
            stateValues = toArray(gf.state).map(function (code) {
                return fieldNames.stateValueMap[code] || code;
            });
        }

        setLocalFilterValue(bar, stateField, stateValues);
        setLocalFilterValue(bar, regionField, gf.region);

        if (fieldNames.stateTriggersAjax) {
            applyBranchAfterStateAjax(bar, stateField, branchField, gf.branch);
        } else {
            setLocalFilterValue(bar, branchField, gf.branch);
        }
    }

    function prepopulateBucketLocalFilters(card, filtersOverride) {
        if (!window.jQuery) {
            return;
        }
        var gf = filtersOverride || getFilters() || {};
        var $ = window.jQuery;
        $(card).find('[data-bucket-filter]').each(function () {
            syncBucketFilterBar(this, gf);
        });
    }

    // Pre-populates every currently-in-scope bucket (skips overridden ones -
    // their local fields are intentionally different and must not be touched).
    function prepopulateAllInScopeLocalFilters(filtersOverride) {
        document.querySelectorAll(BUCKET_SELECTOR).forEach(function (card) {
            prepopulateBucketLocalFilters(card, filtersOverride);
        });
    }

    // bootstrap-multiselect only redraws its visible button label for
    // widgets whose <select> is visible at refresh() time - a bucket whose
    // accordion is still collapsed (display:none) when the global filter is
    // applied ends up with the correct value on the underlying <select> but
    // a stale "Select X" button label, since expanding the accordion later
    // is a pure CSS toggle that doesn't re-run our refresh. Re-sync the
    // bucket's own filter bar every time its accordion becomes visible.
    function bindCollapseResync() {
        if (!window.jQuery) {
            return;
        }
        window.jQuery(document).on('shown.bs.collapse', function (e) {
            var bar = e.target.querySelector('[data-bucket-filter]');
            if (!bar) {
                return;
            }
            var card = bar.closest('[data-follows-global]');
            if (!card || card.getAttribute('data-follows-global') === 'false') {
                return;
            }
            syncBucketFilterBar(bar, getFilters() || {});
        });
    }

    // Detects when the user edits a bucket's OWN local filter (the
    function bindLocalFilterChangeDetection() {
        if (!window.jQuery) {
            return;
        }
        window.jQuery(document).on(
            'change',
            '[data-bucket-filter] select, [data-bucket-filter] input, [data-bucket-filter] textarea',
            function (e) {
                if (isSyncingFromGlobal) {
                    return;
                }
                var control = e.target;
                if (control.hasAttribute('data-ignore-override')) {
                    return;
                }
                var card = control.closest('[data-follows-global]');
                if (card && card.getAttribute('data-follows-global') !== 'false') {
                    setBucketFollowsGlobal(card, false);
                }
            }
        );
    }

    // Clearing a bucket's own local filter ("Clear All", from
    // components/datatable-filters.blade.php) is the one way out of "Local
    // override" - it hands the bucket back to the global filter.
    function bindLocalClearDetection() {
        if (!window.jQuery) {
            return;
        }
        window.jQuery(document).on('click', '[data-follows-global="false"] .clearall', function (e) {
            var card = e.target.closest('[data-follows-global]');
            if (card) {
                setBucketFollowsGlobal(card, true);
            }
        });
    }

    function init() {
        var bar = document.querySelector('.filter-bar');
        if (!bar) {
            return;
        }

        var applyBtn = bar.querySelector('.btn-primary');
        var clearBtn = bar.querySelector('.btn-secondary');

        initMultiselects(bar);
        var syncBranchToState = bindStateBranchNarrowing(bar);
        writeSelectValues(bar, getFilters());
        // Re-sync Branch's enabled/disabled state and option list now that
        // writeSelectValues() may have restored a saved State selection
        // (setting .selected directly doesn't fire 'change' on its own).
        if (syncBranchToState) {
            syncBranchToState();
        }
        toggleBucketBadges();
        prepopulateAllInScopeLocalFilters();
        bindLocalFilterChangeDetection();
        bindLocalClearDetection();
        bindCollapseResync();

        if (applyBtn) {
            applyBtn.addEventListener('click', function (e) {
                e.preventDefault();

                var filters = readSelectValues(bar);
                filters.appliedAt = Date.now();

                function commit() {
                    saveFilters(filters);

                    getOverriddenCards().forEach(function (card) {
                        card.setAttribute('data-follows-global', 'true');
                        var overrideBadge = card.querySelector('.badge-override');
                        if (overrideBadge) { overrideBadge.style.display = 'none'; }
                    });

                    toggleBucketBadges();
                    prepopulateAllInScopeLocalFilters(filters);
                    reloadBuckets();
                    document.dispatchEvent(new CustomEvent(EVENT_APPLIED, { detail: filters }));
                    showToast('Global filter applied to all in-scope buckets.');
                }

                var overridden = getOverriddenCards();
                if (overridden.length === 0) {
                    commit();
                    return;
                }

                openOverrideModal(overridden.map(getBucketLabel), commit);
            });
        }

        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.preventDefault();
                clearFilters();
                writeSelectValues(bar, null);
                if (syncBranchToState) {
                    syncBranchToState();
                }
                toggleBucketBadges();
                // blank out local fields too, but only for in-scope buckets
                // currently following global - overridden ones are skipped
                // by prepopulateAllInScopeLocalFilters itself (BUCKET_SELECTOR)
                prepopulateAllInScopeLocalFilters({ state: '', region: '', branch: '' });
                reloadBuckets();
                document.dispatchEvent(new CustomEvent(EVENT_CLEARED));
            });
        }
    }

    document.addEventListener('DOMContentLoaded', init);

    // Single place every bucket's own "ajax.data" callback calls into.
    function applyToParams(d, tableSelector, fieldNames) {
        if (!window.jQuery) {
            return;
        }
        fieldNames = fieldNames || {};
        var stateName = fieldNames.state || 'state';
        var regionName = fieldNames.region || 'region';
        var branchName = fieldNames.branch || 'aoc_facility_id';

        var $ = window.jQuery;
        var $card = $(tableSelector).closest('[data-follows-global]');
        var followsGlobal = !$card.length || $card.attr('data-follows-global') !== 'false';
        if (!followsGlobal) {
            return;
        }
        var gf = getFilters();
        if (!gf) {
            return;
        }
        d.filters = d.filters || [];
        // One {name, value} entry per selected value - matches the shape
        // serializeArray() produces for existing <select multiple> filters,
        // which backend filter handling already accumulates per field name.
        toArray(gf.state).forEach(function (v) { d.filters.push({ name: stateName, value: v }); });
        toArray(gf.region).forEach(function (v) { d.filters.push({ name: regionName, value: v }); });
        toArray(gf.branch).forEach(function (v) { d.filters.push({ name: branchName, value: v }); });
    }

    function buildReloadFilters(formSelector, fieldNames) {
        var d = { filters: window.jQuery ? window.jQuery(formSelector).serializeArray() : [] };
        applyToParams(d, formSelector, fieldNames);
        return d;
    }

    function showToast(message, duration) {
        if (!toastEl) {
            toastEl = document.createElement('div');
            toastEl.className = 'gf-toast';
            toastEl.setAttribute('role', 'status');
            toastEl.setAttribute('aria-live', 'polite');
            document.body.appendChild(toastEl);
        }

        toastEl.textContent = message;
        clearTimeout(toastTimer);

        // форсируем reflow, иначе повторный показ не переиграет transition
        void toastEl.offsetWidth;
        toastEl.classList.add('is-visible');

        toastTimer = setTimeout(function () {
            toastEl.classList.remove('is-visible');
        }, duration || 2500);
    }

    function buildModal() {
        var backdrop = document.createElement('div');
        backdrop.className = 'gf-modal-backdrop';
        backdrop.innerHTML =
            '<div class="gf-modal" role="dialog" aria-modal="true" aria-labelledby="gf-modal-title">' +
            '<h5 id="gf-modal-title">Update the global filter?</h5>' +
            '<p>Some buckets have their own filter settings that don\'t match the ' +
            'global filter. Applying this change will reset those buckets to the ' +
            'new global values.</p>' +
            '<div class="gf-modal-list"></div>' +
            '<div class="gf-modal-actions">' +
            '<button type="button" class="btn btn-outline-primary" data-gf-cancel>Cancel</button>' +
            '<button type="button" class="btn btn-primary" data-gf-confirm>Apply and reset overrides</button>' +
            '</div>' +
            '</div>';

        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop || e.target.hasAttribute('data-gf-cancel')) {
                closeModal();
            }
            if (e.target.hasAttribute('data-gf-confirm')) {
                var cb = modalOnConfirm;
                closeModal();
                if (cb) { cb(); }
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && backdrop.classList.contains('is-open')) {
                closeModal();
            }
        });

        document.body.appendChild(backdrop);
        return backdrop;
    }

    function closeModal() {
        modalOnConfirm = null;
        if (modalEl) { modalEl.classList.remove('is-open'); }
    }

    function openOverrideModal(bucketNames, onConfirm) {
        if (!modalEl) { modalEl = buildModal(); }

        modalEl.querySelector('.gf-modal-list').textContent =
            'Buckets with overrides: ' + bucketNames.join(', ');

        modalOnConfirm = onConfirm;
        modalEl.classList.add('is-open');
        modalEl.querySelector('[data-gf-confirm]').focus();
    }

    function getBucketLabel(card) {
        var btn = card.querySelector('.card-header button');
        if (!btn) { return 'Bucket'; }

        var clone = btn.cloneNode(true);
        clone.querySelectorAll('span, i, .badge').forEach(function (n) { n.remove(); });

        var text = (clone.textContent || '').replace(/\s+/g, ' ').trim();
        if (!text) { return 'Bucket'; }

        if (text === text.toUpperCase()) {
            text = text.toLowerCase().replace(/\b[a-z]/g, function (c) {
                return c.toUpperCase();
            });
        }
        return text;
    }

    // public API for bucket-specific DataTable configs
    window.GlobalLocationFilter = {
        getFilters: getFilters,
        isActive: isActive,
        applyToParams: applyToParams,
        buildReloadFilters: buildReloadFilters,
        EVENT_APPLIED: EVENT_APPLIED,
        EVENT_CLEARED: EVENT_CLEARED
    };
})(window, document);
