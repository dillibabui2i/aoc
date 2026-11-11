// Nurse.js
import NurseFormItem from './NurseFormItem.js';

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export default class Nurse {
    constructor({
                    clientId,
                    nurseId,        // provider/candidate ID or temp ID for new
                    index,          // incremental index per (clientId, nurseId)
                    data = {},
                    disciplines = [],       // [{ value, label }]
                    useServiceLabel = false, // Service vs Auth Type
                    client = null
                }) {
        this.clientId = clientId;
        this.nurseId = nurseId;
        this.index = index;
        this.client = client;
        this.clientKey = clientId;

        const rawHours = data.hours ?? 0;
        const hoursVal = typeof rawHours === 'number'
            ? rawHours.toFixed(1)
            : rawHours;

        const statusValue = data.status || (data.aoc_provider_id === 0 ? 'new' : 'current');

        const clinicianId =
            (data.aoc_provider_id === 0 && data.aoc_candidate_id === 0)
                ? -1
                : (data.aoc_provider_id || data.aoc_candidate_id || '');

        const disciplineValue = data.discipline ?? '';

        // normalize disciplines to array of { value, label }
        const disciplineOptions = Array.isArray(disciplines)
            ? disciplines // already [{value, label}]
            : Object.entries(disciplines || {}).map(([label, value]) => ({
                value,
                label
            }));

        // In Service mode the dropdown options are loaded asynchronously from getService().
        // Seeding the multiselect with fallback discipline config causes stale/duplicate UI state.
        const initialDisciplineOptions = useServiceLabel
            ? (disciplineValue
                ? [{ value: disciplineValue, label: disciplineValue, selected: true }]
                : [])
            : disciplineOptions.map(d => ({
                value: d.value,
                label: d.label,
                selected: String(d.value) === String(disciplineValue)
            }));

        // ---- hidden: isOldNurse ----
        this.isOldNurse = new NurseFormItem({
            type: 'hidden',
            baseId: 'isOldNurse',
            baseName: 'isOldNurse',
            clientId,
            nurseId,
            index,
            fieldKey: 'isOldNurse',
            value: data.isOldNurse ? 1 : 0
        });

        // ---- hidden: isRespite ----
        this.isRespite = new NurseFormItem({
            type: 'hidden',
            baseId: 'isRespite',
            baseName: 'isRespite',
            clientId,
            nurseId,
            index,
            fieldKey: 'isRespite',
            value: data.isRespite ? 1 : 0
        });

        // ---- remove link ----
        this.removeLink = new NurseFormItem({
            type: 'link',
            baseId: 'nurseRemove_',
            baseName: null,
            clientId,
            nurseId,
            index,
            fieldKey: 'removeLink',
            value: 'javascript:void(0);',
            attributes: {
                class: 'nurse-remove-icon gp ml-2'
            }
        });

        // ---- status radios (new/current) ----
        this.statusNew = new NurseFormItem({
            type: 'radio',
            baseId: 'new_',
            baseName: 'status',
            clientId: '0',
            nurseId: '0',
            index: '0',
            fieldKey: 'status',
            value: 'new',
            attributes: {
                class: 'form-control form-check-input ml-1',
                checked: statusValue === 'new' ? true : undefined
            }
        });

        this.statusCurrent = new NurseFormItem({
            type: 'radio',
            baseId: 'current_',
            baseName: 'status',
            clientId: '0',
            nurseId: '0',
            index: '0',
            fieldKey: 'status',
            value: 'current',
            attributes: {
                class: 'form-control form-check-input ml-1',
                checked: statusValue === 'current' ? true : undefined
            }
        });



        // ---- clinician name (text) ----
        this.nurseName = new NurseFormItem({
            type: 'text',
            baseId: 'nurseId',
            baseName: 'nurseId',
            clientId,
            nurseId,
            index,
            fieldKey: 'nurseName',
            value: data.nurseName || '',
            attributes: {
                class: 'form-control nurseName',
                required: 'required',
                readonly: data.readonly ? true : undefined
            }
        });

        // ---- aoc_clinician_id (hidden) ----
        this.aocClinicianId = new NurseFormItem({
            type: 'hidden',
            baseId: 'aoc_clinician_id',
            baseName: 'aoc_clinician_id',
            clientId,
            nurseId,
            index,
            fieldKey: 'aocClinicianId',
            value: clinicianId
        });

        // ---- selectedPayer (hidden) ----
        this.selectedPayer = new NurseFormItem({
            type: 'hidden',
            baseId: 'selectedPayer',
            baseName: 'selectedPayer',
            clientId,
            nurseId,
            index,
            fieldKey: 'selectedPayer',
            value: data.selectedPayer ?? '',
            attributes: data.selectedPayer
                ? { 'data-value': data.selectedPayer }
                : {}
        });

        // ---- gpNurseDiscipline (select) ----
        this.gpNurseDiscipline = new NurseFormItem({
            type: 'select',
            baseId: 'gpNurseDiscipline',
            baseName: 'gpNurseDiscipline',
            clientId,
            nurseId,
            index,
            fieldKey: 'gpNurseDiscipline',
            value: disciplineValue,
            attributes: {
                class: 'form-control',
                'data-value': disciplineValue,
                'data-label-type': useServiceLabel ? 'Service' : 'AuthType',
                required: 'required'
            },
            options: [
                { value: '', label: '--- Select ---', selected: !disciplineValue },
                ...initialDisciplineOptions
            ]
        });

        // ---- hours (number) ----
        this.hours = new NurseFormItem({
            type: 'number',
            baseId: 'hours',
            baseName: 'hours',
            clientId,
            nurseId,
            index,
            fieldKey: 'hours',
            value: hoursVal,
            attributes: {
                class: 'form-control bgform-control',
                pattern: '^\\d*(\\.\\d{0,2})?$',
                required: 'required',
                step: 0.01
            }
        });

        // ---- desiredRate (number) ----
        this.desiredRate = new NurseFormItem({
            type: 'number',
            baseId: 'desiredRate',
            baseName: 'desiredRate',
            clientId,
            nurseId,
            index,
            fieldKey: 'desiredRate',
            value: data.desiredRate ?? 0.0,
            attributes: {
                class: 'form-control bgform-control',
                required: 'required',
                pattern: '^\\d*(\\.\\d{0,2})?$',
                step: 0.01
            }
        });

        // ---- prevHours (number) ----
        const prevHoursVal = (data.preHours ?? data.prevHours ?? 0.0);
        this.prevHours = new NurseFormItem({
            type: 'number',
            baseId: 'prevHours',
            baseName: 'prevHours',
            clientId,
            nurseId,
            index,
            fieldKey: 'prevHours',
            value: prevHoursVal,
            attributes: {
                class: 'form-control bgform-control',
                required: 'required',
                pattern: '^\\d*(\\.\\d{0,2})?$',
                step: 0.01
            }
        });

        // ---- nurseGP (output span/div) ----
        this.nurseGP = new NurseFormItem({
            type: 'output',
            baseId: 'nurseGP',
            baseName: 'nurseGP',
            clientId,
            nurseId,
            index,
            fieldKey: 'nurseGP',
            value: (data.total_gp ?? data.gp ?? ''),
            attributes: {
                class: 'nurseGP'
            }
        });
    }


    /**
     * Status is a radio group. After user interaction the DOM *property* `.checked`
     * changes, but the original `checked` *attribute* does not.
     * Read from DOM when possible; fall back to initial attributes for non-DOM contexts.
     */
    getStatusValue() {
        if (typeof document !== 'undefined') {
            const newEl = this.statusNew?.id ? document.getElementById(this.statusNew.id) : null;
            const curEl = this.statusCurrent?.id ? document.getElementById(this.statusCurrent.id) : null;

            if (curEl && curEl.checked) return 'current';
            if (newEl && newEl.checked) return 'new';
        }

        // fallback (e.g., server-side render / before DOM exists)
        return this.statusCurrent?.attributes?.checked ? 'current'
            : this.statusNew?.attributes?.checked ? 'new'
                : 'current';
    }
    setClient(client) {
        this.client = client;
        if (client?.clientKey !== undefined) {
            this.clientId = client.clientKey;
            this.setContext({
                clientId: this.clientId,
                nurseId: this.nurseId,
                index: this.index
            });
        }
    }

    getClient() {
        return this.client;
    }

    setContext({ clientId, nurseId, index = -1 }) {
        this.clientId = clientId;
        this.nurseId = nurseId;

        if (index !== -1) {
            this.index = index;
        }

        Object.values(this).forEach(item => {
            if (item instanceof NurseFormItem) {
                item.setContext({ clientId, nurseId, index });
            }
        });
    }

    syncFieldFromElement(fieldKey, el) {
        const item = this[fieldKey];
        if (!item || !(item instanceof NurseFormItem)) return;
        item.syncFromElement(el);
    }

    /**
     * Apply calculated GP (total_gp) into the model and update rendered output.
     */
    applyCalculatedGp(totalGp) {
        const val = (totalGp === null || totalGp === undefined || totalGp === '') ? '' : String(totalGp);
        // Update model value
        if (this.nurseGP) {
            this.nurseGP.value = val;
        }
        // Update DOM output if already rendered
        if (typeof document !== 'undefined' && this.nurseGP?.id) {
            const el = document.getElementById(this.nurseGP.id);
            if (el) {
                el.textContent = val;
            }
        }
    }

    toJSON() {
        const status = this.getStatusValue();

        return {
            clientId: this.clientId,
            nurseId: this.nurseId,
            index: this.index,
            isOldNurse: +this.isOldNurse.value,
            isRespite: +this.isRespite.value,
            status,
            nurseName: this.nurseName.value.trim(),
            aoc_clinician_id: this.aocClinicianId.value,
            selectedPayer: this.selectedPayer.value,
            discipline: this.gpNurseDiscipline.value,
            hours: +this.hours.value || 0,
            desiredRate: +this.desiredRate.value || 0,
            prevHours: +this.prevHours.value || 0,
            total_gp: +this.nurseGP.value || 0,
            gp: this.nurseGP.value
        };
    }

    renderStatusBlock() {
        return `
        <div class="form-group row m-0">
            <div class="col-md-6 d-flex align-items-center">
                <label class="mb-0 mr-3">Status</label>

                <div class="form-check form-check-inline mb-0">
                    ${this.statusNew.renderInput()}
                    <label class="form-check-label ml-1" for="${escapeHtml(this.statusNew.id)}">New</label>
                </div>

                <div class="form-check form-check-inline mb-0 ml-4">
                    ${this.statusCurrent.renderInput()}
                    <label class="form-check-label ml-1" for="${escapeHtml(this.statusCurrent.id)}">Current</label>
                </div>
            </div>
        </div>`.trim();
    }

    renderClinicianBlock(labelServiceAs, disabled = false) {

        return `
        <div class="form-group row m-0">
            <div class="col-md-12 d-flex">
                <label for="${escapeHtml(this.nurseName.id)}">Clinician</label>
                ${this.nurseName.renderInput(disabled)}
                ${this.aocClinicianId.renderInput()}
                ${this.selectedPayer.renderInput()}
            </div>
            <div class="col-md-12 d-flex">
                <label for="${escapeHtml(this.gpNurseDiscipline.id)}">${escapeHtml(labelServiceAs)}</label>
                ${this.gpNurseDiscipline.renderSelect()}
            </div>
        </div>`.trim();
    }

    renderHoursBlock(gpAsDiv = false) {
        const gpTag = gpAsDiv ? 'div' : 'span';
        const gpLabel = gpAsDiv ? 'GP:' : 'GP';

        const gpHtml = this.nurseGP.renderOutput(gpTag);

        return `
        <div class="form-group row m-0 size-4">
            <div class="col-md-12 d-flex p-0">
                <div class="col-md-6 d-flex">
                    <label class="fix-label" for="${escapeHtml(this.hours.id)}">Hours</label>
                    ${this.hours.renderInput()}
                </div>
                <div class="col-md-6 d-flex">
                    <label class="fix-label" for="${escapeHtml(this.desiredRate.id)}">Desired Rate</label>
                    ${this.desiredRate.renderInput()}
                </div>
            </div>
            <div class="col-md-12 d-flex p-0">
                <div class="col-md-6 d-flex">
                    <label class="fix-label" for="${escapeHtml(this.prevHours.id)}">Hours Already Scheduled</label>
                    ${this.prevHours.renderInput()}
                </div>
                <div class="col-md-6 d-flex">
                    <label class="fix-label">${gpLabel}</label>
                    ${gpHtml}
                </div>
            </div>
        </div>`.trim();
    }

    renderRemoveIcon(hideButton) {
        if (!hideButton) {
            this.removeLink.attributes.style = 'display: block !important;';
        }
        return this.removeLink.renderLink('<i class="fa fa-trash-o"></i>');
    }

    renderMenuBlock(requiredToRender) {
        return requiredToRender ? `<div class="form-group row ml-3 action">
                            <a href="javascript:void(0);" class="btn btn-primary list" id="gpInformationPopupTag" data-placement="bottom" data-toggle="tooltip" title="Show detailed calculation"><i class="fa fa-info-circle fa-4x"></i></a>
                            <button class="btn btn-primary ml-3" id="addNurseBtn">Add Clinician</button>
                            <button class="btn btn-primary ml-3" id="calculate">Calculate</button>
                            <button class="btn btn-primary ml-3" id="wholeHome">Show Whole Home</button>
                            <a class="btn btn-primary list ml-3" title="Add GP" id="saveGp">Save to List</a>
                            <button class="btn btn-primary ml-3" id="clear">Clear</button>
                        </div>`.trim() : ''
    }

    renderEscalationGuideBlock(requiredToRender) {
        return requiredToRender
            ? `<div class="nurse-gp-form-cnt ml-3 mt-2 mb-0 px-0"><div id="gpEscalationGuideTable" style="display: none;"></div></div>`
            : '';
    }


    /**
     * Main render method for the whole nurse block
     */
    render({
               renderMenu = false,
               renderEscalationGuide = false,
               showStatusBlock = true,
               useServiceLabel = true,
               gpAsDiv = false,
               renderClientProperties = false,
               wholeHome = false,
           } = {}, addMenu) {
        const labelServiceAs = useServiceLabel ? 'Service' : 'Auth Type';
        const statusBlock = renderMenu ? this.renderStatusBlock() : '';

        const renderedPayerRespite = renderClientProperties ? this.getClient()?.getRenderedPayerRespite() : ''
        return `
            <div class="col-md-8">
                ${this.renderMenuBlock(renderMenu)}
                ${this.renderEscalationGuideBlock(renderMenu && renderEscalationGuide)}
                <div class="nurse-gp-form-cnt ml-3">
                    <div class="nurse-form-inner gp-inner mt-1 pt-1" data-client-id="${this.clientId}" data-nurse-id="${this.nurseId}" data-nurse-index="${this.index}">

                        ${this.isOldNurse.renderInput()}
                        ${this.isRespite.renderInput()}
                        ${this.renderRemoveIcon(renderMenu)}
                        ${statusBlock}
                        ${this.renderClinicianBlock(labelServiceAs, wholeHome)}
                        ${renderedPayerRespite}
                        ${this.renderHoursBlock(gpAsDiv)}
                    </div>
                </div>
            </div>`.trim();
    }
}
