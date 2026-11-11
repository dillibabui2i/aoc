// Client.js
import ClientFormItem from './ClientFormItem.js';

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function startOfIsoWeekMonday(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    // JS: 0=Sun..6=Sat. Convert so Monday=0..Sunday=6
    const day = (d.getDay() + 6) % 7;
    d.setDate(d.getDate() - day);
    return d;
}

function startOfPayrollWeekSaturday(date) {
    const d = new Date(date);
    d.setHours(0, 0, 0, 0);
    const day = d.getDay(); // 0=Sun..6=Sat
    const daysSinceSaturday = (day + 1) % 7;
    d.setDate(d.getDate() - daysSinceSaturday);
    return d;
}

function addDays(date, days) {
    const d = new Date(date);
    d.setDate(d.getDate() + days);
    return d;
}

function formatUs(date) {
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    const y = date.getFullYear();
    return `${m}/${d}/${y}`;
}

function parseUs(value) {
    if (!value) return null;
    // Expect MM/DD/YYYY
    const m = String(value).match(/^(\d{2})\/(\d{2})\/(\d{4})$/);
    if (!m) return null;
    const dt = new Date(Number(m[3]), Number(m[1]) - 1, Number(m[2]));
    dt.setHours(0, 0, 0, 0);
    return dt;
}

/**
 * One "client" block in the GP calculator.
 *
 * clientKey  – first dimension in names: client_id or temporary key
 * slotIndex  – second dimension in names: 0,1,... to differentiate respite/non-respite rows
 */
export default class Client {

    constructor({
                    clientKey,              // client_id or temp key
                    slotIndex,              // index for this clientKey
                    data = {},              // initial values from backend (if any)
                    payers = [],            // [{ value, text }]
                    branches = [],          // [{ value, text }]
                    useNewWeekLogic = false
                }) {
        this.clientKey = clientKey;
        this.slotIndex = slotIndex;
        this.useNewWeekLogic = (useNewWeekLogic === true || useNewWeekLogic === 'true');

        let patientId = data.patientId ?? '';       // aoc_patient_id value
        let clientLabel = data.clientLabel ?? '';   // text in clientSelectionForGP
        let payerValue = data.clientPayer ?? '';
        let branchValue = data.branchId ?? '';
        // Branch is rendered/required only when branches were provided (escalation guide feature flag)
        this.showBranch = branches.length > 0;
        let isSpecialized = !!data.specialized;
        let isRespite = !!data.respite;
        // Normalize showPermOnly coming from backend (can be 1/0, '1'/'0', true/false, 'true'/'false')
        const showPermOnlyRaw = data.showPermOnly ?? 1;
        let showPermOnly = (showPermOnlyRaw === 1
            || showPermOnlyRaw === '1'
            || showPermOnlyRaw === true
            || showPermOnlyRaw === 'true') ? 1 : 0;
        let permOnlyWeekStart = data.permOnlyWeekStart ?? '';
        let permOnlyWeekEnd = data.permOnlyWeekEnd ?? '';


        // ---- Client selection (text) ----
        this.clientSelection = new ClientFormItem({
            type: 'text',
            baseId: 'clientSelectionForGP_',
            baseName: 'clientSelectionForGP',
            clientKey,
            slotIndex,
            fieldKey: 'clientSelection',
            value: clientLabel,
            attributes: {
                required: 'required',
                placeholder: 'Select Client',
                class: 'form-control clientSelectionForGP'
            }
        });

        // ---- Hidden aoc_patient_id ----
        this.aocPatientId = new ClientFormItem({
            type: 'hidden',
            baseId: 'aoc_patient_id_',
            baseName: 'aoc_patient_id',
            clientKey,
            slotIndex,
            fieldKey: 'aocPatientId',
            value: patientId,
            attributes: {}
        });

        // ---- Payer select ----
        this.clientPayer = new ClientFormItem({
            type: 'select',
            baseId: 'clientPayer_',
            baseName: 'clientPayer',
            clientKey,
            slotIndex,
            fieldKey: 'clientPayer',
            value: payerValue,
            attributes: {
                class: 'form-control clientPayer',
                required: 'required'
            },
            options: [
                { value: '', label: '--- Select ---', selected: !payerValue },
                ...payers.map(p => ({
                    value: p.value,
                    label: p.text,
                    selected: String(p.value) === String(payerValue)
                }))
            ]
        });

        // ---- Branch select ----
        this.branch = new ClientFormItem({
            type: 'select',
            baseId: 'clientBranch_',
            baseName: 'clientBranch',
            clientKey,
            slotIndex,
            fieldKey: 'branch',
            value: branchValue,
            attributes: {
                class: 'form-control clientBranch',
                ...(this.showBranch ? { required: 'required' } : {})
            },
            options: [
                { value: '', label: '— Select a branch —', selected: !branchValue },
                ...branches.map(b => ({
                    value: b.value,
                    label: b.text,
                    selected: String(b.value) === String(branchValue)
                }))
            ]
        });

        // ---- Specialized toggle (hidden + switch) ----
        // Hidden => always 0; checkbox => 1 if checked
        this.specializedHidden = new ClientFormItem({
            type: 'hidden',
            baseId: 'specializedHidden_',
            baseName: 'specialized',
            clientKey,
            slotIndex,
            fieldKey: 'specializedHidden',
            value: '0',
            attributes: {
                class: 'specialized1'
            }
        });

        this.specializedSwitch = new ClientFormItem({
            type: 'checkbox',
            baseId: 'specialized_',
            baseName: 'specialized',
            clientKey,
            slotIndex,
            fieldKey: 'specializedSwitch',
            value: '1',
            attributes: {
                class: 'custom-control-input specialized',
                checked: isSpecialized || undefined
            }
        });

        // ---- Respite toggle (hidden + switch) ----
        this.respiteHidden = new ClientFormItem({
            type: 'hidden',
            baseId: 'respiteHidden_',
            baseName: 'respite',
            clientKey,
            slotIndex,
            fieldKey: 'respiteHidden',
            value: isRespite || '0',
            attributes: {
                class: 'respite1'
            }
        });

        this.respiteSwitch = new ClientFormItem({
            type: 'checkbox',
            baseId: 'respite_',
            baseName: 'respite',
            clientKey,
            slotIndex,
            fieldKey: 'respiteSwitch',
            value: '1',
            attributes: {
                class: 'custom-control-input respite',
                checked: isRespite || undefined
            }
        });


        this.showPermOnlyHidden = new ClientFormItem({
            type: 'hidden',
            baseId: 'show_perm_only_hidden_',
            baseName: 'show_perm_only_hidden',
            clientKey,
            slotIndex,
            fieldKey: 'showPermOnlyHidden',
            value: showPermOnly === 1 ? 'true' : 'false',
            attributes: {
                class: 'perm-only1'
            }
        });

        this.showPermOnlySwitch = new ClientFormItem({
            type: 'checkbox',
            baseId: 'show_perm_only_',
            baseName: 'show_perm_only',
            clientKey,
            slotIndex,
            fieldKey: 'showPermOnlySwitch',
            value: '1',
            attributes: {
                class: 'custom-control-input perm-only',
                checked: showPermOnly === 1 ? 'checked' : undefined
            }
        });




        this.permOnlyWeekStart = new ClientFormItem({
            type: 'text',
            baseId: 'perm_only_week_start_',
            baseName: 'perm_only_week_start',
            clientKey,
            slotIndex,
            fieldKey: 'permOnlyWeekStart',
            value: permOnlyWeekStart,
            attributes: {
                class: 'form-control perm-only-week-start',
                placeholder: 'MM/DD/YYYY',
                autocomplete: 'off',
                readonly: 'readonly'
            }
        });

        this.permOnlyWeekEnd = new ClientFormItem({
            type: 'text',
            baseId: 'perm_only_week_end_',
            baseName: 'perm_only_week_end',
            clientKey,
            slotIndex,
            fieldKey: 'permOnlyWeekEnd',
            value: permOnlyWeekEnd,
            attributes: {
                class: 'form-control perm-only-week-end',
                placeholder: 'MM/DD/YYYY',
                autocomplete: 'off',
                readonly: 'readonly'
            }
        });

        // ---- Respite warning span (non-submit) ----
        this.respiteMsg = new ClientFormItem({
            type: 'output',
            baseId: 'respiteMsg_',
            baseName: null,
            clientKey,
            slotIndex,
            fieldKey: 'respiteMsg',
            value: 'Client does not have an active respite payer. Please unselect the respite option to proceed.',
            attributes: {
                class: 'hide alert alert-warning mt-3'
            }
        });
    }



    setContext({ clientKey, slotIndex }) {
        this.clientKey = clientKey;
        this.slotIndex = slotIndex;

        Object.values(this).forEach(item => {
            if (item instanceof ClientFormItem) {
                item.setContext({ clientKey, slotIndex });
            }
        });
    }

    /**
     * Called by event handler when any input with data-client-field changes.
     */
    syncFieldFromElement(fieldKey, el) {
        const item = this[fieldKey];
        if (!item || !(item instanceof ClientFormItem)) return;
        item.syncFromElement(el);
    }

    /**
     * Convenience booleans
     */
    get specialized() {
        return !!this.specializedSwitch.attributes.checked;
    }

    get respite() {
        return !!this.respiteSwitch.attributes.checked;
    }

    set respite(respite) {
        this.respiteHidden.value = respite.isBoolean ? (respite ? 1 : 0) : respite;
        this.respiteSwitch.attributes.checked(Boolean(respite))
    }

    set showPermOnly(showPermOnly) {
        this.showPermOnlyHidden.value = showPermOnly.isBoolean ? (respite ? 1 : 0) : respite;
        this.showPermOnlySwitch.attributes.checked(Boolean(showPermOnly))
    }

    get patientId() {
        return this.aocPatientId.value;
    }

    renderRemoveIcon() {
        return this.removeLink.renderLink('<i class="fa fa-trash-o"></i>');
    }

    /**
     * Convert to data payload (for sending to backend).
     */
    toJSON() {
        return {
            clientKey: this.clientKey,
            slotIndex: this.slotIndex,
            patientId: this.aocPatientId.value,
            clientSelection: this.clientSelection.value,
            clientPayer: this.clientPayer.value,
            branchId: this.branch.value,
            specialized: this.specializedHidden.value,
            respite: parseInt(this.respiteHidden.value),
            payerNormal: this._payerNormal ?? '',
            payerRespite: this._payerRespite ?? '',
            showPermOnly: this.showPermOnlyHidden.value === 'true' ? 1 : 0,
            permOnlyWeekStart: this.permOnlyWeekStart?.value ?? '',
            permOnlyWeekEnd: this.permOnlyWeekEnd?.value ?? ''
        };
    }

    initializeCalculationBlock(addCalculationBlock) {
        return addCalculationBlock ? `<div class="col-md-12 p-0" id="gpIdeaTable" style="display: none;"></div>` : '';
    }
    // ---- render HTML mirroring your Blade snippet ----

    getRenderedPayerRespite() {
        return `<div class="form-group row m-0">
                    <div class="col col-md-2 m-0"><label for="${escapeHtml(this.clientPayer.id)}">Insurance/Payer</label></div>
                    <div class="col col-md-7 m-0">${this.clientPayer.renderSelect()}</div>
                   <div class="col col-md-3 m-0 d-flex align-items-center">
                      <label class="mb-0 mr-3">Respite?</label>

                      ${this.respiteHidden.renderInput()}
                          <div class="custom-control custom-switch custom-switch-lg ml-auto" data-placement="bottom" data-toggle="tooltip" title="Respite switch shows only respite services and calculates rates accordingly for the given nurse.">
                        ${this.respiteSwitch.renderInput()}
                        <label class="custom-control-label" for="${escapeHtml(this.respiteSwitch.id)}"></label>
                      </div>
                    </div>
                </div>`.trim();
    }

    showHidePermOnly(show = true) {
        const permOnlyBlock = document.getElementById('permOnlyBlock')
        const permOnlyDates = document.getElementById('permOnlyDates')
        const switchClass = permOnlyBlock?.getAttribute('class')
        const datesStyle = permOnlyDates?.getAttribute('style')
        if (show) {
            permOnlyBlock?.setAttribute('class', switchClass.replace('invisible', ''));
            this.initPermOnlyWeekPicker();
        } else {
            permOnlyBlock?.setAttribute('class', switchClass +' invisible');
            permOnlyDates?.setAttribute('style', datesStyle.replace('display: block;', 'display: none;'));
        }
    }

    setPermOnly(permOnly = 1) {
        const isOn = permOnly === 1;

        this.showPermOnlyHidden.value = isOn ? 'true' : 'false';
        this.showPermOnlySwitch.attributes.checked = isOn ? 'checked' : undefined;

        // If DOM exists, keep it in sync too
        if (typeof document !== 'undefined' && this.showPermOnlySwitch?.id) {
            const el = document.getElementById(this.showPermOnlySwitch.id);
            if (el && el.type === 'checkbox') {
                el.checked = isOn;
            }
        }

        if (typeof document !== 'undefined' && this.showPermOnlyHidden?.id) {
            const hid = document.getElementById(this.showPermOnlyHidden.id);
            if (hid) hid.value = this.showPermOnlyHidden.value;
        }
    }

    /**
     * jQuery UI Datepicker options for "Perm Only" week selection.
     *
     * Rules (AC7/AC8):
     * - Week Start: any day from the current week (Monday) onward; past dates are disabled.
     * - Week End: any day from the current week onward; past dates are disabled.
     * - Defaults on first load (when empty): Week Start = Monday of current week; Week End = Sunday of current week.
     * - Both dates are required to enable the Calculate button.
     * - Selecting any date snaps Week Start to that week’s Monday and Week End to that week’s Sunday (single-week only).
     *
     * Usage (outside model):
     *   $("#" + client.permOnlyWeekStart.id).datepicker(client.getPermOnlyWeekPickerOptions());
     */
    getPermOnlyWeekPickerOptions() {
        const self = this;

        const dateFormat = 'mm/dd/yy';
        // TODO: Remove legacy Monday-based picker boundaries after release of update-hours-logic-for-new-week.
        const minDate = this.useNewWeekLogic
            ? startOfPayrollWeekSaturday(new Date())
            : startOfIsoWeekMonday(new Date());

        return {
            dateFormat,
            firstDay: this.useNewWeekLogic ? 6 : 1,
            showOtherMonths: true,
            selectOtherMonths: false,
            minDate,

            onSelect(dateText, inst) {
                let pickedDate = null;

                // Prefer jQuery UI parser if available
                if (typeof window !== 'undefined' && window.$ && window.$.datepicker && window.$.datepicker.parseDate) {
                    try {
                        pickedDate = window.$.datepicker.parseDate(dateFormat, dateText);
                    } catch (e) {
                        pickedDate = null;
                    }
                }

                pickedDate = pickedDate || parseUs(dateText) || new Date();
                pickedDate.setHours(0, 0, 0, 0);

                let weekStart = self.useNewWeekLogic
                    ? startOfPayrollWeekSaturday(pickedDate)
                    : startOfIsoWeekMonday(pickedDate);

                if (weekStart < minDate) {
                    weekStart = minDate;
                }
                weekStart.setHours(0, 0, 0, 0);

                const weekEnd = addDays(weekStart, self.useNewWeekLogic ? 8 : 6);
                weekEnd.setHours(0, 0, 0, 0);

                const startStr = formatUs(weekStart);
                const endStr = formatUs(weekEnd);

                // Update model
                if (self.permOnlyWeekStart) self.permOnlyWeekStart.value = startStr;
                if (self.permOnlyWeekEnd) self.permOnlyWeekEnd.value = endStr;

                // Update DOM for start input (datepicker is attached to it)
                if (inst && inst.input) {
                    inst.input.val(startStr);
                    inst.input.trigger('change');
                    try { inst.input.datepicker('setDate', weekStart); } catch (e) {}
                }

                // Update DOM for end input (and its datepicker, if attached)
                if (typeof document !== 'undefined' && self.permOnlyWeekEnd?.id) {
                    const endEl = document.getElementById(self.permOnlyWeekEnd.id);
                    if (endEl) {
                        endEl.value = endStr;
                        if (typeof window !== 'undefined' && window.$) {
                            try { window.$(endEl).datepicker('setDate', weekEnd); } catch (e) {}
                        }
                        endEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                self.updateCalculateButtonState();
            }
        };
    }

    getPermOnlyWeekEndPickerOptions() {
        const self = this;

        const dateFormat = 'mm/dd/yy';
        // TODO: Remove legacy Monday-based picker boundaries after release of update-hours-logic-for-new-week.
        const minDate = this.useNewWeekLogic
            ? startOfPayrollWeekSaturday(new Date())
            : startOfIsoWeekMonday(new Date());

        return {
            dateFormat,
            firstDay: this.useNewWeekLogic ? 6 : 1,
            showOtherMonths: true,
            selectOtherMonths: false,
            minDate,

            onSelect(dateText, inst) {
                let pickedDate = null;

                if (typeof window !== 'undefined' && window.$ && window.$.datepicker && window.$.datepicker.parseDate) {
                    try {
                        pickedDate = window.$.datepicker.parseDate(dateFormat, dateText);
                    } catch (e) {
                        pickedDate = null;
                    }
                }

                pickedDate = pickedDate || parseUs(dateText) || new Date();
                pickedDate.setHours(0, 0, 0, 0);

                let weekStart = self.useNewWeekLogic
                    ? startOfPayrollWeekSaturday(pickedDate)
                    : startOfIsoWeekMonday(pickedDate);

                if (weekStart < minDate) {
                    weekStart = minDate;
                }
                weekStart.setHours(0, 0, 0, 0);

                const startStr = formatUs(weekStart);
                const endDate = self.useNewWeekLogic ? pickedDate : addDays(weekStart, 6);
                endDate.setHours(0, 0, 0, 0);
                const endStr = formatUs(endDate);

                // Update model
                if (self.permOnlyWeekStart) self.permOnlyWeekStart.value = startStr;
                if (self.permOnlyWeekEnd) self.permOnlyWeekEnd.value = endStr;

                // Update DOM for end input (datepicker is attached to it)
                if (inst && inst.input) {
                    inst.input.val(endStr);
                    inst.input.trigger('change');
                    try { inst.input.datepicker('setDate', endDate); } catch (e) {}
                }

                // Update DOM for start input (and its datepicker, if attached)
                if (typeof document !== 'undefined' && self.permOnlyWeekStart?.id) {
                    const startEl = document.getElementById(self.permOnlyWeekStart.id);
                    if (startEl) {
                        startEl.value = startStr;
                        if (typeof window !== 'undefined' && window.$) {
                            try { window.$(startEl).datepicker('setDate', weekStart); } catch (e) {}
                        }
                        startEl.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                }

                self.updateCalculateButtonState();
            }
        };
    }

    /**
     * AC8: both Week Start and Week End are required to enable Calculate.
     * Tries a few common selectors to find the Calculate button.
     */
    updateCalculateButtonState() {
        if (typeof document === 'undefined') return;

        const startOk = !!parseUs(this.permOnlyWeekStart?.value ?? '');
        const endOk = !!parseUs(this.permOnlyWeekEnd?.value ?? '');
        const enabled = startOk && endOk;

        const btn =
            document.getElementById('calculate') ||
            document.getElementById('calculateBtn') ||
            document.querySelector('button#calculate') ||
            document.querySelector('button[data-action="calculate"]') ||
            document.querySelector('button.calculate') ||
            document.querySelector('button[name="calculate"]');

        if (btn) {
            btn.disabled = !enabled;
        }
    }

    /**
     * Attach the jQuery UI datepicker to the Perm Only week start and end inputs (if present).
     * Safe to call multiple times. Applies AC7/AC8 defaults.
     */
    initPermOnlyWeekPicker() {
        if (typeof window === 'undefined' || !window.$) return;
        const $ = window.$;
        if (!$.fn || !$.fn.datepicker) return;

        // TODO: Remove legacy Monday-based picker defaults after release of update-hours-logic-for-new-week.
        const weekStart = this.useNewWeekLogic
            ? startOfPayrollWeekSaturday(new Date())
            : startOfIsoWeekMonday(new Date());
        const weekEnd = this.useNewWeekLogic
            ? new Date()
            : addDays(weekStart, 6);
        weekEnd.setHours(0, 0, 0, 0);

        // ---- Week Start ----
        if (this.permOnlyWeekStart?.id) {
            const startEl = document.getElementById(this.permOnlyWeekStart.id);
            if (startEl) {
                const $start = $(startEl);
                if (!$start.hasClass('hasDatepicker')) {
                    $start.datepicker(this.getPermOnlyWeekPickerOptions());
                }

                // AC7 default (only if empty)
                if (!this.permOnlyWeekStart.value) {
                    const startStr = formatUs(weekStart);
                    this.permOnlyWeekStart.value = startStr;
                    $start.val(startStr);
                    try { $start.datepicker('setDate', weekStart); } catch (e) {}
                }

                // Keep Calculate button state in sync
                $start.off('change.permOnlyWeek').on('change.permOnlyWeek', () => {
                    // sync model if user manually clears via code
                    this.permOnlyWeekStart.value = startEl.value;
                    this.updateCalculateButtonState();
                });
            }
        }

        // ---- Week End ----
        if (this.permOnlyWeekEnd?.id) {
            const endEl = document.getElementById(this.permOnlyWeekEnd.id);
            if (endEl) {
                const $end = $(endEl);
                if (!$end.hasClass('hasDatepicker')) {
                    $end.datepicker(this.getPermOnlyWeekEndPickerOptions());
                }

                // AC7 default (only if empty)
                if (!this.permOnlyWeekEnd.value) {
                    const endStr = formatUs(weekEnd);
                    this.permOnlyWeekEnd.value = endStr;
                    $end.val(endStr);
                    try { $end.datepicker('setDate', weekEnd); } catch (e) {}
                }

                $end.off('change.permOnlyWeek').on('change.permOnlyWeek', () => {
                    this.permOnlyWeekEnd.value = endEl.value;
                    this.updateCalculateButtonState();
                });
            }
        }

        // Initial state
        this.updateCalculateButtonState();
    }

    // Optional CSS (in your stylesheet) to highlight selected week in datepicker:
    // .ui-datepicker-current-week a { font-weight: 700; text-decoration: underline; }

    render(addCalculationBlock = false, hideProps = false, permOnlyActive = false) {
        const hideClass = hideProps ? 'hide' : '';
        const permOnlyAttr = addCalculationBlock ?  'id="permOnlyBlock"' : ''
        const permOnlyDates = addCalculationBlock ?  'id="permOnlyDates"' : ''
        // Single source of truth: the rendered checkbox follows the hidden value.
        this.showPermOnlySwitch.attributes.checked = (this.showPermOnlyHidden.value === 'true') ? 'checked' : undefined;

        const permOnlyDateBlock = addCalculationBlock ? `
            <div ${permOnlyDates} style="display: none;" class="form-group row ${hideClass}">
                        <div class="d-flex align-items-center">
                            <label>Week Start</label>
                            <div class="col-md-4">${this.permOnlyWeekStart.renderInput()}</div>
                            <label>Week End</label>
                            <div class="col-md-4">${this.permOnlyWeekEnd.renderInput()}</div>
                        </div>
                    </div>
            `.trim() : '';

        const html =  `
            <div class="col-md-4">
                <div class="form-group row ${hideClass}">
                    <label for="${escapeHtml(this.clientSelection.id)}">Select Client</label>
                    ${this.clientSelection.renderInput()}
                    ${this.aocPatientId.renderInput()}
                </div>`

        const branchBlock = this.showBranch ? `<div class="form-group row  ${hideClass}">
                    <label for="${escapeHtml(this.branch.id)}">Branch</label>
                    ${this.branch.renderSelect()}
                    <small class="form-text text-muted w-100">Auto-fills when you select an existing client.</small>
                    <small class="branch-error text-danger hide">Branch field is required.</small>
                </div>` : '';

        return !hideClass ?
                html + `${branchBlock}

                <div class="form-group row  ${hideClass}">
                    <label for="${escapeHtml(this.clientPayer.id)}">Insurance/Payer</label>
                    ${this.clientPayer.renderSelect()}
                </div>

                <div class="form-group row  ${hideClass}">
                    <div ${permOnlyAttr} class="col-md-6 d-flex p-0 invisible">
                        <label class="m-0 mr-3">Show Perm. Only</label>
                        ${this.showPermOnlyHidden.renderInput()}
                        <div class="custom-control custom-switch custom-switch-lg ml-auto"  data-placement="bottom" data-toggle="tooltip" title="Hours Already Scheduled reflect the toggle state — when ON, coverage hours are excluded and shown blank; when OFF, all coverage and permanent hours are included." >
                            ${this.showPermOnlySwitch.renderInput()}
                            <label class="custom-control-label" for="${escapeHtml(this.showPermOnlySwitch.id)}"></label>
                        </div>
                    </div>

                    <div class="col-md-6 d-flex p-0">
                        <label class="w-100 align-self-center m-0 mr-3">Respite?</label>
                        ${this.respiteHidden.renderInput()}
                        <div class="custom-control custom-switch custom-switch-lg"  data-placement="bottom" data-toggle="tooltip" title="Shows only respite services for manually added nurses. Whole home nurses are managed separately." >
                            ${this.respiteSwitch.renderInput()}
                            <label class="custom-control-label" for="${escapeHtml(this.respiteSwitch.id)}"></label>
                        </div>
                    </div>
                    ${this.respiteMsg.renderOutput('span')}
                </div>
                ${permOnlyDateBlock}
                ${this.initializeCalculationBlock(addCalculationBlock)}
            </div>`.trim() : html + '</div>';
    }
}
