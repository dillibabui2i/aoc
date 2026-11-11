// GpCalculator.js
import ClientCollection from './ClientCollection.js';
import NurseCollection from './NurseCollection.js';
import Client from './Client.js';
import Nurse from './Nurse.js';
import {IdeaTable} from "./IdeaTable.js";
import {EscalationGuideTable} from "./EscalationGuideTable.js";
import {GpCalcInformationPopup} from "./GpCalcInformationPopup.js";
import GpPersistentService from './../service/GpPersistenceService.js';


export default class GpCalculator {
    static CALC_TYPES = {
        singleClientSingleNurse: 'singleClientSingleNurse',
        singleClientMultipleNurses: 'singleClientMultipleNurses',
        multipleClientsSingleNurse: 'multipleClientsSingleNurse'
    };

    /**
     * @param {Object} params
     * @param {string} params.csrfToken
     * @param {string} [params.calculationType]
     * @param {Array}  [params.payers]
     * @param {Array}  [params.disciplines]
     * @param {boolean}[params.useServiceLabel]
     * @param {GpPersistentService} [params.persistenceService]
     */
    constructor({
                    $,
                    csrfToken,
                    calculationType = GpCalculator.CALC_TYPES.singleClientSingleNurse,
                    payers = [],
                    branches = [],
                    disciplines = [],
                    useServiceLabel = true,
                    renderEscalationGuideTable = false,
                    persistenceService = null,
                    rowsBlock = '',
                    popupBlock = '',
                    ideaTable = '',
                    escalationGuideTable = '',
                    modalId = '',
                    renderPermOnly = false,
                    useNewWeekLogic = false
                }) {
        this.$ = $;
        this.csrfToken = csrfToken;
        this.calculationType = calculationType;
        this.payers = payers;
        this.branches = branches;
        this.disciplines = disciplines;
        this.useServiceLabel = useServiceLabel;
        this.renderEscalationGuideTable = !!renderEscalationGuideTable;
        this.persistenceService = persistenceService;
        this.popupTemplateId = popupBlock;
        this.ideaTableId = ideaTable;
        this.escalationGuideTableId = escalationGuideTable;
        this.popupModalId = modalId;
        this.wholeHome = false;
        this.renderPermOnly = renderPermOnly;
        this.useNewWeekLogic = (useNewWeekLogic === true || useNewWeekLogic === 'true');
        this.clientCollection = new ClientCollection();
        this.nurseCollection = new NurseCollection();

        this.links = [];
        // Monotonic counters for per-(clientId,nurseId) index allocation.
        // We do NOT reuse indexes after deletions; this keeps DOM keys unique over time.
        this._pairIndexSeq = new Map();
        this.currentNurseIndex = 0;
        this._isHydrating = false;
        this.calculated = 0;
        this.id = null;


        // ---- DOM containers ----
        this.rowsBlock = typeof rowsBlock === 'string'
            ? document.getElementById(rowsBlock)
            : rowsBlock;

        if (!this.rowsBlock) {
            console.warn('GpCalculator: rowsBlock not found');
        }

        this.lastCalculationResponse = null;

        this.infoPopup = new GpCalcInformationPopup({
            templateId: this.popupTemplateId,
            mountId: this.popupMountId,
            modalId: this.popupModalId,
            $: this.$,
        });

        const mountEl = document.getElementById(this.popupMountId);
        if (mountEl) {
            this.ideaTable = new IdeaTable({
                mountEl
            });
        }

        const escalationGuideMountEl = document.getElementById(this.escalationGuideTableId);
        if (this.renderEscalationGuideTable && escalationGuideMountEl) {
            this.escalationGuideTable = new EscalationGuideTable({
                mountEl: escalationGuideMountEl
            });
        }

        document.addEventListener('click', (e) => {
            const el = e.target instanceof Element ? e.target.closest('#gpInformationPopupTag') : null;
            if (!el) return;
            e.preventDefault();
            try { e.stopPropagation(); } catch (err) {}
            try { e.stopImmediatePropagation(); } catch (err) {}
            if (!this.lastCalculationResponse) return;
            this.infoPopup.openFromResult(this.lastCalculationResponse);
        }, true);

        this.attachClientAutocomplete();
        this.attachNurseAutocomplete();
        this.attachRespiteToggleDelegation();
        this.attachPermOnlyToggleDelegation();
        this.attachPayerChangeDelegation();
        this.attachBranchChangeDelegation();
        this.attachNurseRemoveDelegation();
        this.attachRowFieldSyncDelegation();

    }

    setId(id) {
        this.id = id;
    }

    /**
     * Create one new Client + Nurse pair and register a link.
     */
    createNewRecord() {
        const client = this.clientCollection.createEmpty({
            payers: this.payers,
            branches: this.branches,
            useNewWeekLogic: this.useNewWeekLogic
        });

        const clientKey = client.clientKey;
        const slotIndex = client.slotIndex;

        const nurse = this.nurseCollection.createEmpty({
            clientId: clientKey,
            selectedPayer: '',
            isRespite: false,
            disciplines: this.disciplines,
            useServiceLabel: this.useServiceLabel
        });

        nurse.setClient(client);

        // Ensure unique per-(clientId,nurseId) index to avoid duplicated ids/names
        // when the same clinician is added multiple times for the same client.
        const pairIndex = this.getNextPairIndex(clientKey, nurse?.nurseId);
        nurse.setContext({ clientId: clientKey, nurseId: nurse?.nurseId, index: pairIndex });

        const linkId = (crypto?.randomUUID?.() ?? `l_${Date.now()}_${Math.random().toString(16).slice(2)}`);

        const link = { linkId, clientKey, slotIndex, client, nurse };
        this.links.push(link);

        // optional but nice: O(1) lookup
        this.linksById ??= new Map();
        this.linksById.set(linkId, link);

        this.renderAddedRow(link);
        return link;
    }

    findLinksByClient(client) {
        return this.links.filter(l => l.client === client);
    }

    findLinksByNurse(nurse) {
        return this.links.filter(l => l.nurse === nurse);
    }

    /**
     * Allocate a monotonically increasing index for a (clientId,nurseId) pair.
     * We intentionally do NOT reuse indexes after deletions.
     *
     * This keeps DOM ids/names unique over the session and is safe because calculation
     * uses the serialized model (clientId+nurseId+index).
     */
    countLinksByClientAndNurse(clientId, nurseId, { excludeLinkId = null } = {}) {
        const cid = String(clientId ?? '');
        const nid = String(nurseId ?? '');
        const key = `${cid}:${nid}`;

        // ignore excludeLinkId: this is an allocator now, not a counter
        const prev = this._pairIndexSeq?.get(key);
        const next = (prev === undefined || prev === null) ? 0 : (Number(prev) + 1);
        this._pairIndexSeq?.set(key, next);
        return next;
    }

    /**
     * Next available index for (clientId, nurseId) pair.
     * First occurrence -> 0, second -> 1, etc.
     */
    getNextPairIndex(clientId, nurseId, { excludeLinkId = null } = {}) {
        this.currentNurseIndex++;
        return this.currentNurseIndex;
        //return this.countLinksByClientAndNurse(clientId, nurseId, { excludeLinkId });
    }

    updateLinksAfterPatientAssigned(client) {
        const oldLinks = this.findLinksByClient(client);

        const newClientKey = client.clientKey;
        const newSlotIndex = client.slotIndex;

        oldLinks.forEach(link => {
            link.clientKey = newClientKey;
            link.slotIndex = newSlotIndex;

            const oldNurseIndex = link?.nurse?.index;
            const nurseId = link?.nurse?.nurseId;

            const pairIndex = this.getNextPairIndex(newClientKey, nurseId, { excludeLinkId: link.linkId });

            link.nurse.setContext({
                clientId: newClientKey,
                nurseId: nurseId,
                index: pairIndex
            });

            // If index changed, re-key only the nurse subtree to avoid touching client fields
            const rowEl = document.querySelector(`.clientSelectionContainerRow[data-link-id="${link.linkId}"]`);
            const nurseInner = rowEl?.querySelector('.nurse-form-inner');
            if (nurseInner && oldNurseIndex !== undefined && oldNurseIndex !== null && String(oldNurseIndex) !== String(pairIndex)) {
                this.applyDomKeysToRow(nurseInner, { oldIndex: oldNurseIndex, newIndex: pairIndex });
                nurseInner.setAttribute('data-nurse-index', String(pairIndex));
                this.reinitNurseDisciplineMultiselects(nurseInner);
            }
        });
    }

    async onClientPayerChanged(client) {
        await this.updateNurseDisciplineForClient(client, { resetDiscipline: true });
    }

    async onClientRespiteChanged(client) {
        await this.updateNurseDisciplineForClient(client);
    }

    async updateNurseDisciplineForClient(client, { preserveSelected = false, resetDiscipline = false } = {}) {
        if (!this.persistenceService || typeof this.persistenceService.getService !== 'function') {
            return;
        }

        const payer = this.getFormItemValue(client?.clientPayer);
        const firstClient = this.getBaseClient();
        const isRespiteRaw = this.getFormItemValue(client?.respiteHidden ?? client?.respite);
        const isRespite = (String(isRespiteRaw ?? '') === '1' || isRespiteRaw === true);
        const showPermOnly = this.getBaseClient()?.showPermOnlyHidden?.value
        // Whole Home duplicated clients may not have `patientId` as a plain prop.
        // Prefer FormItem value from `aocPatientId`.
        const patientId =
            this.getFormItemValue(client?.aocPatientId)
            ?? this.getFormItemValue(client?.patientId)
            ?? client?.patientId
            ?? client?.aocPatientId
            ?? client?.clientKey
            ?? '';
        const links = this.findLinksByClient(client);
        if (!links.length) return;

        for (const link of links) {
            const nurse = link.nurse;
            // Keep nurse hidden fields in sync with current client context
            // (used on submit and sometimes by backend rules)
            if (nurse) {
                this.setFormItemValue(nurse.selectedPayer, payer ?? '');
                this.setFormItemValue(nurse.isRespite, isRespite ? 1 : 0);

                // Sync existing DOM inputs (limit lookup to this row/subtree)
                const rowEl = document.querySelector(`.clientSelectionContainerRow[data-link-id="${link.linkId}"]`);
                const nurseInner = rowEl?.querySelector('.nurse-form-inner') ?? rowEl;

                const selectedPayerEl = nurseInner?.querySelector(
                    'input[type="hidden"][id^="selectedPayer"], input[type="hidden"][name^="selectedPayer"]'
                );
                if (selectedPayerEl) selectedPayerEl.value = String(payer ?? '');

                const isRespiteEl = nurseInner?.querySelector(
                    'input[type="hidden"][id^="isRespite"], input[type="hidden"][name^="isRespite"]'
                );
                if (isRespiteEl) isRespiteEl.value = isRespite ? '1' : '0';

                // Reset discipline immediately (before async API call) so the UI reflects the
                // change without waiting for the server response.
                if (resetDiscipline && nurse?.gpNurseDiscipline) {
                    this.setFormItemValue(nurse.gpNurseDiscipline, '');
                    const disciplineSelectEarly = rowEl?.querySelector(
                        'select[id^="gpNurseDiscipline"], select[name^="gpNurseDiscipline"], select.gpNurseDiscipline'
                    );
                    if (disciplineSelectEarly) disciplineSelectEarly.value = '';
                }
            }

            const service = await this.persistenceService.getService({
                aoc_patient_id: patientId,
                payer: payer,
                isRespite: isRespite,
                permOnly: (showPermOnly === 'true' && nurse.isOldNurse.value === 1),
            });

            if (!service) continue;

            // New API shape: { status: true, options: [{ payerid, name, authorization_id }, ...] }
            const disciplineOptionsRaw = Array.isArray(service?.options)
                ? service.options
                : (Array.isArray(service?.data?.options) ? service.data.options : null);

            if (Array.isArray(disciplineOptionsRaw)) {
                // Normalize strings so values like "LVN\/LPN" match "LVN/LPN"
                const norm = (v) => String(v ?? '').replace(/\\\//g, '/').trim();

                // Prefer keeping user-selected value when requested, otherwise fall back to
                // discipline coming from Whole Home payload / nurse data.
                const currentValRaw = norm(this.getFormItemValue(nurse?.gpNurseDiscipline) ?? '');
                const apiValRaw = norm(
                    nurse?._wholeHomeRaw?.discipline
                    ?? nurse?.data?.discipline
                    ?? nurse?.discipline
                    ?? ''
                );

                const desiredValRaw = resetDiscipline
                    ? ''
                    : (preserveSelected && currentValRaw ? currentValRaw : (apiValRaw || currentValRaw));

                const mapped = (disciplineOptionsRaw || [])
                    .map(o => {
                        const name = norm(o?.name ?? '');
                        return {
                            value: name,
                            label: name,
                            selected: desiredValRaw ? (norm(name) === norm(desiredValRaw)) : false
                        };
                    })
                    .filter(o => String(o.value).length > 0);

                // Pick final selected value only if it exists in returned options
                const hasDesired = desiredValRaw
                    ? mapped.some(o => norm(o.value) === norm(desiredValRaw))
                    : false;
                const finalVal = hasDesired ? desiredValRaw : '';

                // Model
                nurse.gpNurseDiscipline.options = mapped;
                this.setFormItemValue(nurse.gpNurseDiscipline, finalVal);

                // DOM
                const rowEl = document.querySelector(`.clientSelectionContainerRow[data-link-id="${link.linkId}"]`);
                const disciplineSelect = rowEl?.querySelector(
                    'select[id^="gpNurseDiscipline"], select[name^="gpNurseDiscipline"], select.gpNurseDiscipline'
                );
                if (disciplineSelect) {
                    this.replaceSelectOptions(disciplineSelect, mapped, { includeEmpty: true });
                    disciplineSelect.value = finalVal;

                    this.reinitNurseDisciplineMultiselects(rowEl ?? document);
                    disciplineSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }

                continue;
            }

            // Backward-compatible: old API returns a single discipline
            if (service.discipline) {
                nurse.gpNurseDiscipline.value = service.discipline;
                nurse.gpNurseDiscipline.options.forEach(o => {
                    o.selected = (String(o.value) === String(service.discipline));
                });
            }
        }
    }

    attachClientAutocomplete() {
        document.addEventListener('focusin', (e) => {
            const inputEl = e.target;
            if (!(inputEl instanceof HTMLInputElement)) return;
            if (!inputEl.matches('input.clientSelectionForGP')) return;

            this.initClientAutocomplete(inputEl);
        });
    }

    attachNurseAutocomplete() {
        document.addEventListener('focusin', (e) => {
            const inputEl = e.target;
            if (!(inputEl instanceof HTMLInputElement)) return;
            if (!inputEl.matches('input.nurseName')) return;

            this.initNurseAutocomplete(inputEl);
        });
    }

    async initNurseAutocomplete(inputEl) {
        if (inputEl.dataset.acInit === "1") return;
        inputEl.dataset.acInit = "1";

        if (!this.$ || typeof this.$ !== 'function' || !this.persistenceService) {
            console.warn('GpCalculator.initNurseAutocomplete: jQuery/persistenceService not ready');
            return;
        }

        const $input = this.$(inputEl);

        $input.autocomplete({
            minLength: 2,
            source: (request, response) => {
                const svc = this.persistenceService;
                const fn = svc && typeof svc.getCandidateName === 'function'
                    ? svc.getCandidateName.bind(svc)
                    : null;

                const candidateType = document.querySelector('input[type="radio"][data-nurse-field="status"]:checked')?.value ?? 'current';

                if (!fn) {
                    console.error('Autocomplete getCandidateName failed: getCandidateName is not defined in persistenceService');
                    response([]);
                    return;
                }

                fn({ term: request.term, type: candidateType }, { forceRefresh: true })
                    .then((data) => {
                        const items = Array.isArray(data) ? data : (data?.data ?? []);
                        response((items || []).map(x => ({
                            label: x.label ?? x.value ?? x.name ?? String(x),
                            value: x.value ?? x.label ?? x.name ?? String(x),
                            ...x
                        })));
                    })
                    .catch((err) => {
                        console.error('Autocomplete getCandidateName failed:', err);
                        response([]);
                    });
            },
            classes: {
                "ui-autocomplete": "client-selection-for-gp"
            },
            select: async (event, ui) => {
                const {row, link, client, nurse} = this.resolveLinkFromInput(inputEl);

                // keep text box readable
                if (ui?.item?.value != null) {
                    inputEl.value = ui.item.value;
                }

                // DOM fields in the same row
                const hiddenClinicianId = row?.querySelector('input[id^="aoc_clinician_id_"], input[name^="aoc_clinician_id"], input[name="aoc_clinician_id[]"]');

                const clinicianIdVal = ui?.item?.id ?? '';

                // MODEL updates
                if (nurse) {
                    const oldIndex = nurse.index;

                    const newIndex = this.getNextPairIndex(
                        nurse.clientId,
                        clinicianIdVal,
                        {excludeLinkId: link?.linkId}
                    );

                    this.setFormItemValue(nurse.id, clinicianIdVal);
                    this.setFormItemValue(nurse.nurseName, inputEl.value);
                    this.setFormItemValue(nurse.aocClinicianId, clinicianIdVal);
                    nurse._selectedCandidateData = ui?.item ?? null;

                    // This regenerates ids/names in the model
                    nurse.setContext({clientId: nurse.clientId, nurseId: clinicianIdVal, index: newIndex});

                    //request hours already scheduled for nurse
                    const resHours = await this.persistenceService.loadScheduledHours({
                        providerId: clinicianIdVal
                    });
                    if (nurse.getStatusValue() === 'current') {
                        this.setFormItemValue(nurse.prevHours, (resHours.hour !== null ? resHours?.hour : 0), true);
                    }
                    // And this applies the new index to the existing DOM (only within nurse subtree)
                    const nurseInner = row?.querySelector('.nurse-form-inner');
                    if (nurseInner && oldIndex !== undefined && oldIndex !== null && String(oldIndex) !== String(newIndex)) {
                        this.applyDomKeysToRow(nurseInner, {oldIndex: oldIndex, newIndex: newIndex});
                        nurseInner.setAttribute('data-nurse-index', String(newIndex));
                        this.reinitNurseDisciplineMultiselects(nurseInner);
                    }
                }
                // DOM updates
                if (hiddenClinicianId) hiddenClinicianId.value = clinicianIdVal ?? '';
            },
            change: (event, ui) => {
                // jQuery UI sends change after select as well
                if (ui.item != null) return;

                const { row, link, nurse } = this.resolveLinkFromInput(inputEl);

                const hiddenClinicianId = row?.querySelector('input[id^="aoc_clinician_id_"], input[name^="aoc_clinician_id"], input[name="aoc_clinician_id[]"]');

                // When user typed something not from list – treat as "new" / unknown clinician
                const resetVal = '-1';

                if (hiddenClinicianId) hiddenClinicianId.value = resetVal;

                if (nurse) {
                    const oldIndex = nurse.index;
                    const newIndex = this.getNextPairIndex(
                        nurse.clientId,
                        resetVal,
                        { excludeLinkId: link?.linkId }
                    );

                    this.setFormItemValue(nurse.id, hiddenClinicianId.value);
                    this.setFormItemValue(nurse.nurseName, inputEl.value);
                    this.setFormItemValue(nurse.aocClinicianId, resetVal);
                    nurse._selectedCandidateData = null;

                    nurse.setContext({ clientId: nurse.clientId, nurseId: resetVal, index: newIndex });

                    const nurseInner = row?.querySelector('.nurse-form-inner');
                    if (nurseInner && oldIndex !== undefined && oldIndex !== null && String(oldIndex) !== String(newIndex)) {
                        this.applyDomKeysToRow(nurseInner, { oldIndex: oldIndex, newIndex: newIndex });
                        nurseInner.setAttribute('data-nurse-index', String(newIndex));
                        this.reinitNurseDisciplineMultiselects(nurseInner);
                    }
                }
            }
        });
    }

    // ----------------------
    // Live field -> model sync (for inputs the user types into)
    // ----------------------
    attachRowFieldSyncDelegation() {
        const handler = (e) => {
            const el = e.target;
            if (!(el instanceof HTMLElement)) return;

            const row = el.closest('.clientSelectionContainerRow');
            if (!row) return;

            const { client, nurse } = this.resolveLinkFromElement(el);
            if (!client && !nurse) return;

            // ---- Nurse numeric inputs ----
            if (nurse && el instanceof HTMLInputElement) {
                if (el.matches('input[id^="hours"], input[name^="hours"]')) {
                    this.setFormItemValue(nurse.hours, el.value);
                    return;
                }
                if (el.matches('input[id^="desiredRate"], input[name^="desiredRate"]')) {
                    this.setFormItemValue(nurse.desiredRate, el.value);
                    return;
                }
                if (el.matches('input[id^="prevHours"], input[name^="prevHours"], input[id^="preHours"], input[name^="preHours"]')) {
                    this.setFormItemValue(nurse.prevHours, el.value);
                    return;
                }
                if (el.matches('input.nurseName')) {
                    this.setFormItemValue(nurse.nurseName, el.value);
                    return;
                }
            }

            // ---- Nurse discipline ----
            if (nurse && el instanceof HTMLSelectElement) {
                if (el.matches('select[id^="gpNurseDiscipline"], select[name^="gpNurseDiscipline"], select.gpNurseDiscipline')) {
                    const val = el.value ?? '';
                    this.setFormItemValue(nurse.gpNurseDiscipline, val);
                    if (Array.isArray(nurse.gpNurseDiscipline?.options)) {
                        nurse.gpNurseDiscipline.options.forEach(o => o.selected = String(o.value) === String(val));
                    }
                    return;
                }
            }

            // ---- Nurse status radios ----
            if (nurse && el instanceof HTMLInputElement && el.type === 'radio') {
                if (el.matches('input[type="radio"][name^="status"]')) {
                    const v = String(el.value ?? '');
                    if (nurse.statusNew?.attributes) nurse.statusNew.attributes.checked = (v === 'new');
                    if (nurse.statusCurrent?.attributes) nurse.statusCurrent.attributes.checked = (v === 'current');
                    return;
                }
            }

            // ---- Client typed text ----
            if (client && el instanceof HTMLInputElement) {
                if (el.matches('input.clientSelectionForGP')) {
                    this.setFormItemValue(client.clientSelection, el.value);
                    return;
                }
            }
        };

        document.addEventListener('input', handler, true);
        document.addEventListener('change', handler, true);
    }

    resolveLinkFromInput(inputEl) {
        const row = inputEl.closest('.clientSelectionContainerRow');
        const linkId = row?.dataset?.linkId;
        if (!linkId) return { row: row ?? null, link: null, client: null };

        const link = this.linksById?.get(linkId) ?? this.links.find(l => l.linkId === linkId) ?? null;
        return { row, link, client: link?.client ?? null, nurse: link?.nurse };
    }

    initClientAutocomplete(inputEl) {
        if (inputEl.dataset.acInit === "1") return;
        inputEl.dataset.acInit = "1";

        const $input = this.$(inputEl);

        $input.autocomplete({
            minLength: 2,

            source: (request, response) => {
                this.persistenceService
                    .getScheduleClient({ term: request.term }, { forceRefresh: true })
                    .then((data) => {
                        const items = Array.isArray(data) ? data : (data?.data ?? []);
                        document.getElementById('addNewClientContainer')?.classList.remove('hide');

                        response(items.map(x => ({
                            label: x.label ?? x.value ?? x.name ?? String(x),
                            value: x.value ?? x.label ?? x.name ?? String(x),
                            ...x
                        })));
                    })
                    .catch((err) => {
                        console.error("Autocomplete getScheduleClient failed:", err);
                        response([]);
                    });
            },
            classes: {
                "ui-autocomplete": "client-selection-for-gp"
            },
            select: (event, ui) => {
                const { row, client } = this.resolveLinkFromInput(inputEl);

                // Prefer finding fields inside the same row (no reliance on regenerated ids)
                const hiddenId        = row?.querySelector('input[id^="aoc_patient_id_"], input[name="aoc_patient_id[]"]');
                const respiteMsg      = row?.querySelector('[id^="respiteMsg_"], .respiteMsg');
                // Use only the checkbox respite toggle
                const respiteToggle   = row?.querySelector('input[type="checkbox"].respite, input.respite[type="checkbox"], input[id^="respite_"][type="checkbox"]');
                const payerSelect     = row?.querySelector('select[id^="clientPayer_"], select[name^="clientPayer"]');
                const specializedTgl  = row?.querySelector('input[id^="specialized_"], input[name^="specialized"]');
                const branchSelect    = row?.querySelector('select[id^="clientBranch_"], select[name^="clientBranch"]');

                const selectedPatientId = ui?.item?.id;
                // Auto-fill Branch from the client's assigned branch (only if it is an active option)
                const clientBranchId = ui?.item?.aoc_facility_id ? String(ui.item.aoc_facility_id) : '';

                const standardPayer = ui?.item?.payer ?? '';
                const respitePayer  = ui?.item?.respiteTerminalPlayer ?? '';
                // Client has only a respite payer (no standard terminal payer) -> auto-enable Respite
                const isRespite = !standardPayer && !!respitePayer;

                if (respiteToggle) respiteToggle.checked = isRespite;

                // jQuery UI sets input value AFTER select() callback.
                // Force it now so we store the selected display value, not the typed term.
                const displayText = (ui?.item?.value ?? ui?.item?.label ?? '').toString();
                if (displayText) inputEl.value = displayText;

                // ----- MODEL updates -----
                if (client) {
                    this.setFormItemValue(client.aocPatientId, selectedPatientId);
                    this.setFormItemValue(client.respiteHidden, isRespite ? 1 : 0);
                    this.setFormItemValue(client.respite, isRespite ? 1 : 0);

                    // optional: keep user-visible text
                    if (client.clientSelection && typeof client.clientSelection === 'object' && 'value' in client.clientSelection) {
                        client.clientSelection.value = inputEl.value;
                    }

                    this.setFormItemValue(client.specializedHidden, ui?.item?.is_specialized);
                    client._payerNormal  = standardPayer;
                    client._payerRespite = respitePayer;
                    client._selectedClientData = {
                        payer: client._payerNormal,
                        respiteTerminalPlayer: client._payerRespite
                    };
                }

                this.setClientBranch(client, branchSelect, clientBranchId);

                // ----- DOM updates -----
                if (hiddenId) hiddenId.value = selectedPatientId ?? '';
                if (respiteMsg) respiteMsg.classList.add('hide');

                if (payerSelect) {
                    // Reload the payer options for the client's respite state before selecting a
                    // value — the select may currently hold a different client's option list
                    // (e.g. respite-only options left over from a previously selected client).
                    void this.syncPayerByRespiteSwitch({ row, client, isRespite });
                }

                if (specializedTgl) specializedTgl.checked = (ui.item.is_specialized !== 0);

                document.getElementById('addNewClientContainer')?.classList.remove('hide');

                if (client) this.clientCollection.reindexClient(this.getFormItemValue(client.aocPatientId))
                if (client) void this.updateLinksAfterPatientAssigned(client)
            },

            change: (event, ui) => {
                if (ui.item != null) return; // jQuery UI sends change after select as well

                const { row, client } = this.resolveLinkFromInput(inputEl);

                const hiddenId        = row?.querySelector('input[id^="aoc_patient_id_"], input[name="aoc_patient_id[]"]');
                const payerSelect     = row?.querySelector('select[id^="clientPayer_"], select[name^="clientPayer"]');
                const specializedTgl  = row?.querySelector('input[id^="specialized_"], input[name^="specialized"]');
                const branchSelect    = row?.querySelector('select[id^="clientBranch_"], select[name^="clientBranch"]');
                // Use only the checkbox respite toggle
                const respiteToggle   = row?.querySelector('input[type="checkbox"].respite, input.respite[type="checkbox"], input[id^="respite_"][type="checkbox"]');

                const newTempId = String(-Date.now());

                // ----- DOM resets -----
                if (hiddenId) hiddenId.value = newTempId;
                if (payerSelect) this.$(payerSelect).val('').multiselect("rebuild");
                if (specializedTgl) specializedTgl.checked = false;
                if (respiteToggle) {
                    respiteToggle.checked = false;
                    respiteToggle.dispatchEvent(new Event('change', { bubbles: true }));
                }

                // Manual (intake) client: branch must be chosen by the user
                this.setClientBranch(client, branchSelect, '');

                // ----- MODEL resets -----
                if (client) {
                    this.setFormItemValue(client.aocPatientId ?? client.patientId, newTempId);
                    this.setFormItemValue(client.respiteHidden, 0);
                    this.setFormItemValue(client.respite, 0);
                    this.setFormItemValue(client.specializedHidden, 0);
                    this.setFormItemValue(client.specialized, false);

                    this.setFormItemValue(client.clientSelection, inputEl.value);

                    void this.onClientPayerChanged(client);
                    void this.onClientRespiteChanged(client);
                }
            }
        });
    }

    // ----------------------
    // IdeaTable mounting
    // ----------------------

    /**
     * IdeaTable mount point may not exist at constructor time because it can be rendered
     * only after the first row is inserted / after restore hydration.
     * This method is safe to call multiple times.
     */
    ensureIdeaTableMounted() {
        // If we have an IdeaTable instance but its mount element is gone (e.g., after clear()),
        // drop it so we can mount again.
        try {
            if (this.ideaTable && (!this.ideaTable.mountEl || !document.contains(this.ideaTable.mountEl))) {
                this.ideaTable = null;
            }
        } catch (e) {
            this.ideaTable = null;
        }
        if (this.ideaTable) return true;

        const mountEl = document.getElementById(this.ideaTableId);
        if (!mountEl) return false;

        try {
            // IdeaTable should not open popup by itself; popup is controlled by #gpInformationPopupTag
            this.ideaTable = new IdeaTable({
                mountEl,
                popup: this.infoPopup ?? undefined,
                enableInfoButton: false,
            });

            if (this.lastCalculationResponse) {
                this.ideaTable.update(this.lastCalculationResponse);

            }

            return true;
        } catch (e) {
            console.warn('GpCalculator.ensureIdeaTableMounted failed:', e);
            return false;
        }
    }

    ensureEscalationGuideTableMounted() {
        if (!this.renderEscalationGuideTable) {
            return false;
        }

        try {
            if (this.escalationGuideTable && (!this.escalationGuideTable.mountEl || !document.contains(this.escalationGuideTable.mountEl))) {
                this.escalationGuideTable = null;
            }
        } catch (e) {
            this.escalationGuideTable = null;
        }
        if (this.escalationGuideTable) return true;

        const mountEl = document.getElementById(this.escalationGuideTableId);
        if (!mountEl) return false;

        try {
            this.escalationGuideTable = new EscalationGuideTable({ mountEl });
            if (this.lastCalculationResponse) {
                this.escalationGuideTable.update(this.lastCalculationResponse);
            }
            return true;
        } catch (e) {
            console.warn('GpCalculator.ensureEscalationGuideTableMounted failed:', e);
            return false;
        }
    }

    /**
     * Render newly added Client+Nurse row using containers from constructor.
     *
     * Clients:
     *   inserted BEFORE #gpIdeaTable inside this.clientsBlock (addedClientsBlock)
     *
     * Nurses:
     *   appended to this.nursesBlock (addedNursesBlock)
     *
     * For now nurse block is blank placeholder.
     */
    renderAddedRow(link, addMenus = false) {
        if (!this.rowsBlock) {
            console.warn('GpCalculator.renderAddedRow: containers not ready');
            return;
        }

        const { client, nurse } = link;

        const addCalculationBlock = this.links.length === 1 || addMenus;
        const clientHtml = client.render(addCalculationBlock);
        const nurseHtml = nurse.render({
            renderMenu: addCalculationBlock,
            renderEscalationGuide: this.renderEscalationGuideTable,
        });

        const wrapped = this.applyRowWrapper(clientHtml + nurseHtml, link);

        // If Whole Home header exists, insert new rows BEFORE it
        const wh = document.getElementById('nurseGPFormCnt');
        if (wh && this.rowsBlock.contains(wh)) {
            wh.insertAdjacentHTML('beforebegin', wrapped);
        } else {
            this.rowsBlock.insertAdjacentHTML('beforeend', wrapped);
        }

        // IdeaTable mount point may appear only after the first row is rendered
        this.ensureIdeaTableMounted();
        this.ensureEscalationGuideTableMounted();

        //Initializes a searchable multiselect on all Insurance/Payer select elements
        this.initPayerMultiselects();

        // In service mode, initialize the service multiselect only after real service options
        // have been loaded to avoid stale plugin state from fallback discipline config.
        if (!this.useServiceLabel) {
            this.initNurseDisciplineMultiselects();
        }

        return link;
    }

    applyRowWrapper(body, link) {
        return `<div class="clientSelectionContainerRow addBorder" data-link-id="${link.linkId}">
                    <a
                        data-index="${link.slotIndex}"
                        href="javascript:void(0);"
                        class='hide'
                        style="padding-top: 140px;">
                            <i class="fa fa-trash-o"></i>
                    </a>
                    <div class="col-md-12 d-flex">${body}</div>
                </div>`.trim();
    }

    /**
     * Replaces template placeholder indexes (commonly 0) inside a single row.
     * Fixes cross-row toggle issues caused by duplicated ids/label[for] after cloning/rendering.
     */
    applyDomKeysToRow(rowEl, { oldIndex = 0, newIndex }) {
        if (!rowEl || newIndex === undefined || newIndex === null) return;

        const oi = String(oldIndex);
        const ni = String(newIndex);

        // Match suffix "_0" but not "_01".
        const idSuffixRe = new RegExp(`_${oi}(?!\\d)`, 'g');
        const nameBracketRe = new RegExp(`\\[${oi}\\]`, 'g');

        const rekeyValue = (val) => {
            if (typeof val !== 'string' || !val) return val;
            let out = val;
            out = out.replace(idSuffixRe, `_${ni}`);
            out = out.replace(nameBracketRe, `[${ni}]`);
            return out;
        };

        // IDs
        rowEl.querySelectorAll('[id]').forEach((el) => {
            const before = el.id;
            const after = rekeyValue(before);
            if (after !== before) el.id = after;
        });

        // Names
        rowEl.querySelectorAll('[name]').forEach((el) => {
            const before = el.getAttribute('name');
            const after = rekeyValue(before);
            if (after !== before) el.setAttribute('name', after);
        });

        // Labels `for=` (THIS is what usually makes toggles flip the wrong checkbox)
        rowEl.querySelectorAll('label[for]').forEach((el) => {
            const before = el.getAttribute('for');
            const after = rekeyValue(before);
            if (after !== before) el.setAttribute('for', after);
        });

        // data-index placeholders
        rowEl.querySelectorAll('[data-index]').forEach((el) => {
            const before = el.getAttribute('data-index');
            if (before === oi) el.setAttribute('data-index', ni);
        });
    }


    replaceSelectOptions(selectEl, options, { includeEmpty = true } = {}) {
        if (!selectEl) return;

        // Clear existing options
        selectEl.innerHTML = '';

        if (includeEmpty) {
            const empty = document.createElement('option');
            empty.value = '';
            empty.textContent = '--Select--';
            selectEl.appendChild(empty);
        }

        (options || []).forEach(opt => {
            const o = document.createElement('option');
            o.value = String(opt.value ?? '');
            o.textContent = String(opt.label ?? opt.value ?? '');
            o.selected = Boolean(opt.selected);
            selectEl.appendChild(o);
        });
    }

    // ----------------------
    // Payer change handling
    // ----------------------

    attachPayerChangeDelegation() {
        if (!this.$) return;

        // Handles manual payer changes (and also those triggered programmatically)
        this.$(document).on('change', 'select[id^="clientPayer_"], select[name^="clientPayer"]', (e) => {
            const selectEl = e.target;
            if (!(selectEl instanceof HTMLSelectElement)) return;
            if (this._isHydrating) return;

            const { client } = this.resolveLinkFromElement(selectEl);
            if (!client) return;

            const val = selectEl.value ?? '';

            // Sync model/FormItem payer
            if (client?.clientPayer && typeof client.clientPayer === 'object' && 'value' in client.clientPayer) {
                client.clientPayer.value = val;
                if (Array.isArray(client.clientPayer.options)) {
                    client.clientPayer.options.forEach(o => {
                        o.selected = String(o.value) === String(val);
                    });
                }
            }

            void this.onClientPayerChanged(client);
        });
    }

    // ----------------------
    // Branch handling
    // ----------------------

    /**
     * Set Branch in DOM + model for a client and all its clones (same clientKey).
     * Falls back to empty when the id is not among the active branch options.
     */
    setClientBranch(client, branchSelect, branchId) {
        const known = (this.branches || []).some(b => String(b.value) === String(branchId));
        const value = known ? String(branchId) : '';

        if (branchSelect) {
            branchSelect.value = value;
            branchSelect.classList.remove('error');
            branchSelect.parentElement?.querySelector('.branch-error')?.classList.add('hide');
        }
        this.syncBranchToModel(client, value);
    }

    syncBranchToModel(client, value) {
        if (!client) return;
        const key = String(client.clientKey ?? '');
        const group = (this.clientCollection?.clients || []).filter(c => String(c.clientKey ?? '') === key);
        (group.length ? group : [client]).forEach(c => {
            c.branch.value = value;
            c.branch.options.forEach(o => { o.selected = String(o.value) === String(value); });
        });
    }

    attachBranchChangeDelegation() {
        if (!this.$) return;

        // Only syncs the model; the guide is refreshed by the next Calculate click
        this.$(document).on('change', 'select[id^="clientBranch_"], select[name^="clientBranch"]', (e) => {
            const selectEl = e.target;
            if (!(selectEl instanceof HTMLSelectElement)) return;
            if (this._isHydrating) return;

            const { client } = this.resolveLinkFromElement(selectEl);
            this.syncBranchToModel(client, selectEl.value ?? '');

            if (selectEl.value) {
                selectEl.classList.remove('error');
                selectEl.parentElement?.querySelector('.branch-error')?.classList.add('hide');
            }
        });
    }

    // ----------------------
    // Nurse remove handling
    // ----------------------

    attachNurseRemoveDelegation() {
        if (!this.$) return;

        // Nurse remove icon is rendered as <a class="nurse-remove-icon ...">...</a>
        this.$(document).on('click', 'a.nurse-remove-icon', (e) => {
            e.preventDefault();

            const el = e.currentTarget;
            const { row, link } = this.resolveLinkFromElement(el);
            if (!row || !link) return;

            this.removeLinkAndRow(link.linkId);
        });
    }

    removeLinkAndRow(linkId, resetMenuWhenOne = true) {
        const link = this.linksById?.get(linkId) ?? this.links.find(l => l.linkId === linkId) ?? null;
        if (!link) return;

        const { client, nurse } = link;

        // 1) Remove DOM row
        const rowEl = document.querySelector(`.clientSelectionContainerRow[data-link-id="${linkId}"]`);
        if (rowEl) rowEl.remove();

        // 2) Remove link from in-memory lists
        this.links = (this.links || []).filter(l => l.linkId !== linkId);
        if (this.linksById) this.linksById.delete(linkId);

        // 3) Remove nurse from NurseCollection
        if (nurse && this.nurseCollection?.map) {
            const cid = String(nurse.clientId ?? client?.clientKey ?? '');
            const list = this.nurseCollection.map.get(cid);
            if (Array.isArray(list)) {
                const next = list.filter(n => n !== nurse);
                if (next.length) this.nurseCollection.map.set(cid, next);
                else this.nurseCollection.map.delete(cid);
            }
        }

        // 4) Remove client if it has no remaining links
        if (client) {
            const remaining = (this.links || []).some(l => l.client === client);
            if (!remaining) {
                // remove from ClientCollection
                if (this.clientCollection?.clients && Array.isArray(this.clientCollection.clients)) {
                    this.clientCollection.clients = this.clientCollection.clients.filter(c => c !== client);
                }
            }

            // 5) Reindex clients for the same patientId (keeps slotIndex compact)
            const patientId = this.getFormItemValue(client?.aocPatientId) ?? client?.clientKey;
            if (patientId) {
                try {
                    this.clientCollection.reindexClient(patientId);
                } catch (e) {
                    // ignore
                }
            }
        }
        if (this.links.length === 1 && resetMenuWhenOne) {
            this.resetMenu()
            this.calculationType = GpCalculator.CALC_TYPES.singleClientSingleNurse;
        }

    }

    // ----------------------
    // Respite toggle handling
    // ----------------------

    attachRespiteToggleDelegation() {
        if (!this.$) return;

        // Some templates use Bootstrap custom-switch where the <input> is visually hidden
        // and the user actually clicks the <label>. If label[for] becomes stale after re-key/restore,
        // the checkbox won't toggle. This click handler makes toggling robust.
        this.$(document).on('click', '.clientSelectionContainerRow label.custom-control-label, .clientSelectionContainerRow label[for^="respite"]', (e) => {
            const label = e.currentTarget;
            const row = label.closest('.clientSelectionContainerRow');
            if (!row) return;

            // Find the respite checkbox in the same switch container first
            const container = label.closest('.custom-control, .custom-switch, .form-group') ?? row;
            const checkbox = container.querySelector('input.respite[type="checkbox"], input[type="checkbox"].respite');
            if (!checkbox) return;

            // Prevent default label behaviour (which may point to a stale id)
            e.preventDefault();

            checkbox.checked = !checkbox.checked;
            checkbox.dispatchEvent(new Event('change', { bubbles: true }));
        });

        // event delegation for dynamically rendered rows
        this.$(document).on('change', 'input.respite', async (e) => {
            const checkbox = e.target;
            if (!(checkbox instanceof HTMLInputElement)) return;
            if (this._isHydrating) return;
            this.applyRespiteHiddenInput(checkbox);

            const { row, client } = this.resolveLinkFromElement(checkbox);

             if (!row) return;

             const isRespite = checkbox.checked;
             const v = isRespite ? 1 : 0;

             // Model sync only when client exists
             if (client) {
               this.setFormItemValue(client.respiteHidden, v);
               this.setFormItemValue(client.respite, v);
             }

             // Dropdown should be updated even without selected client
             await this.syncPayerByRespiteSwitch({
               row,
               client: client ?? null,
               isRespite,
             });

        });
    }

    applyRespiteHiddenInput(checkboxEl) {
        let dataindex = checkboxEl.getAttribute('data-index') ?? checkboxEl.dataset?.index;

        if ((dataindex === undefined || dataindex === null || dataindex === '') && typeof this.$ === 'function') {
            try { dataindex = this.$(checkboxEl).data('index'); } catch (e) {}
        }

        if (dataindex === undefined || dataindex === null || dataindex === '') return;

        const name = `respite[${dataindex}]`;

        const row = checkboxEl.closest('.clientSelectionContainerRow') ?? document;
        let hidden = row.querySelector(`input[type="hidden"][name="${name}"]`);

        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            hidden.className = 'respite1';
            checkboxEl.parentNode?.insertBefore(hidden, checkboxEl);
        }

        hidden.value = checkboxEl.checked ? '1' : '0';
    }

    async syncPayerByRespiteSwitch({ row, client, isRespite }) {
        // no selected client -> nothing to sync
        const data = client?._selectedClientData ?? null;

        // if (!data) return;

        const payerSelect = row?.querySelector('select[id^="clientPayer_"], select[name^="clientPayer"]');
        const respiteMsg  = row?.querySelector('[id^="respiteMsg_"], .respiteMsg');
        if (!payerSelect) return;

        // If client is selected, use client's default payer.
        // If no client is selected, just load the correct payer list and keep selection empty.
        const payerVal = data
          ? (isRespite ? data.respiteTerminalPlayer : data.payer)
          : '';

        const prevHydrating = this._isHydrating;
        this._isHydrating = true;

        let apiResult = null;
        try {
            if (this.persistenceService && typeof this.persistenceService.loadPayerDropdown === 'function') {
                try {
                    apiResult = await this.persistenceService.loadPayerDropdown(
                        { isRespite: Boolean(isRespite) },
                        { ttlMs: 60 * 1000 }
                    );
                } catch (e) {
                    apiResult = await this.persistenceService.loadPayerDropdown(
                        Boolean(isRespite),
                        { ttlMs: 60 * 1000 }
                    );
                }
            }
        } catch (e2) {
            console.warn('GpCalculator.syncPayerByRespiteSwitch: loadPayerDropdown failed', e2);
            apiResult = null;
        } finally {
            this._isHydrating = prevHydrating;
        }

        const rawOptions = Array.isArray(apiResult)
            ? apiResult
            : (apiResult?.data ?? apiResult?.items ?? apiResult?.options ?? []);

        const normalized = this.normalizePayerOptions(rawOptions);

        // 2) Replace DOM select options BEFORE selecting value
        if (normalized.length) {
            payerSelect.innerHTML = '';

            const emptyOpt = document.createElement('option');
            emptyOpt.value = '';
            emptyOpt.textContent = '';
            payerSelect.appendChild(emptyOpt);

            for (const opt of normalized) {
                const el = document.createElement('option');
                el.value = String(opt.value);
                el.textContent = String(opt.label);
                el.selected = (String(opt.value) === String(payerVal || ''));
                payerSelect.appendChild(el);
            }
        }

        // 3) Update DOM select + multiselect UI
        if (typeof this.$ === 'function') {
            this.$(payerSelect).val(payerVal || '');
            if (typeof this.$(payerSelect).multiselect === 'function') {
                this.$(payerSelect).multiselect('rebuild');
            }
        }

        // 4) Sync model select value + options
        if (client?.clientPayer && typeof client.clientPayer === 'object') {
            if ('value' in client.clientPayer) {
                client.clientPayer.value = payerVal || '';
            }

            if (normalized.length) {
                client.clientPayer.options = normalized.map(o => ({
                    value: o.value,
                    label: o.label,
                    selected: String(o.value) === String(payerVal || '')
                }));
            }
        }

        // 5) Show/hide message if respite payer is missing
        if (respiteMsg) {
            if (isRespite && !payerVal) respiteMsg.classList.remove('hide');
            else respiteMsg.classList.add('hide');
        }

        // 6) Downstream logic
        payerSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    resolveLinkFromElement(el) {
        const row = el.closest('.clientSelectionContainerRow');
        const linkId = row?.dataset?.linkId;
        if (!linkId) return { row: row ?? null, link: null, client: null, nurse: null };

        const link = this.linksById?.get(linkId) ?? this.links.find(l => l.linkId === linkId) ?? null;
        return { row, link, client: link?.client ?? null, nurse: link?.nurse ?? null };
    }

    getFormItemValue(maybeFormItem) {
        if (maybeFormItem == null) return undefined;
        if (typeof maybeFormItem === 'object' && 'value' in maybeFormItem) return maybeFormItem.value;
        return maybeFormItem;
    }

    setFormItemValue(maybeFormItem, value, updateDomValue = false) {
        if (maybeFormItem == null) return false;
        try {
            if (typeof maybeFormItem === 'object' && 'value' in maybeFormItem) {
                maybeFormItem.value = value;
                if (updateDomValue) {
                    maybeFormItem.setDomValue(value)
                }
                return true;
            }
        } catch (e) {}
        return false;
    }


    // ----------------------
    // Restore / Hydration
    // ----------------------

    buildClientFromJSON(c = {}) {
        const clientKey = c.clientKey ?? c.patientId ?? c.aoc_patient_id ?? c.aocPatientId ?? '';
        const slotIndex = Number.isFinite(Number(c.slotIndex)) ? Number(c.slotIndex) : 0;

        const client = new Client({
            clientKey,
            slotIndex,
            data: {
                patientId: c.patientId ?? c.aoc_patient_id ?? c.aocPatientId ?? '',
                clientLabel: c.clientLabel ?? c.label ?? c.clientSelection ?? c.value ?? '',
                clientPayer: c.clientPayer ?? c.payer ?? '',
                branchId: c.branchId ?? '',
                specialized: c.specialized ?? c.is_specialized ?? 0,
                respite: c.respite ?? 0,
                showPermOnly: c.showPermOnly ?? 1,
                permOnlyWeekStart: c.permOnlyWeekStart ?? '',
                permOnlyWeekEnd: c.permOnlyWeekEnd ?? ''
            },
            payers: this.payers,
            branches: this.branches,
            useNewWeekLogic: this.useNewWeekLogic
        });

        // Restore the same shape we normally set on autocomplete select()
        const payerNormal =
            c.payerNormal ??
            c.payer_normal ??
            c._payerNormal ??
            (c.respite ? '' : (c.clientPayer ?? c.payer ?? '')) ??
            '';

        const payerRespite =
            c.payerRespite ??
            c.payer_respite ??
            c.respiteTerminalPlayer ??
            c._payerRespite ??
            (c.respite ? (c.clientPayer ?? c.payer ?? '') : '') ??
            '';

        client._payerNormal = String(payerNormal ?? '');
        client._payerRespite = String(payerRespite ?? '');

        client._selectedClientData = {
            payer: client._payerNormal,
            respiteTerminalPlayer: client._payerRespite
        };

        return client;
    }

    buildNurseFromJSON(n = {}) {
        // Nurse expects `data` keys similar to NurseCollection.createEmpty
        // plus aoc_provider_id/aoc_candidate_id for clinician id resolution.
        const clientId = n.clientId ?? n.clientKey ?? '';
        const nurseId = n.nurseId ?? -1;
        const index = Number.isFinite(Number(n.index)) ? Number(n.index) : 0;

        const clinician = n.aoc_clinician_id ?? nurseId;

        const data = {
            status: n.status ?? 'current',
            isOldNurse: n.isOldNurse ?? 0,
            isRespite: n.isRespite ?? 0,
            selectedPayer: n.selectedPayer ?? '',
            nurseName: n.nurseName ?? '',
            hours: n.hours ?? 0,
            desiredRate: n.desiredRate ?? '0.0',
            preHours: n.prevHours ?? 0,
            discipline: n.discipline ?? '',
            gp: n.gp ?? '',
            // make constructor derive clinician id from provider/candidate fields
            aoc_provider_id: nurseId ?? -1,
            aoc_candidate_id: n.aoc_clinician_id ?? nurseId,
        };

        return new Nurse({
            clientId,
            nurseId,
            index,
            data,
            disciplines: this.disciplines,
            useServiceLabel: this.useServiceLabel,
            client: null
        });
    }

    clearUIAndState() {
        // Clear UI
        if (this.rowsBlock) this.rowsBlock.innerHTML = '';
        // Reset IdeaTable instance because its mount element is removed when we clear rowsBlock.
        // Otherwise ensureIdeaTableMounted() will think it's already mounted and skip re-mounting.
        this.ideaTable = null;
        this.escalationGuideTable = null;
        this.lastCalculationResponse = null;
        this.lastCalculation = null;
        try {
            document.getElementById('nurseGPFormCnt')?.remove();
        } catch (e) {}
        // Clear in-memory state
        this.clientCollection = new ClientCollection();
        this.nurseCollection = new NurseCollection();
        this.links = [];
        this.linksById = new Map();
        this.calculationType = GpCalculator.CALC_TYPES.singleClientSingleNurse;

    }

    clear() {
        history.replaceState(null, "", window.location.pathname);
        this.clearUIAndState()
        this.createNewRecord()
        const mountEl = document.getElementById(this.ideaTableId);
        const escalationGuideMountEl = this.renderEscalationGuideTable
            ? document.getElementById(this.escalationGuideTableId)
            : null;

        if (this.ideaTable) {
            this.ideaTable.remount(mountEl);
        } else {
            this.ideaTable = new IdeaTable({ mountEl, popup: this.infoPopup, enableInfoButton: false });
        }

        if (this.renderEscalationGuideTable && this.escalationGuideTable) {
            this.escalationGuideTable.remount(escalationGuideMountEl);
        } else if (this.renderEscalationGuideTable && escalationGuideMountEl) {
            this.escalationGuideTable = new EscalationGuideTable({ mountEl: escalationGuideMountEl });
        }
        this.resetMenu();
    }

    /**
     * Restore a full calculator snapshot created by `toJSON()`.
     * After restore we run `hydrateAfterRestore()` to reload dependent dropdowns.
     */
    async restore(snapshot, { hydrate = true } = {}) {
        if (!snapshot || typeof snapshot !== 'object') return;

        this.clearUIAndState();
        this.calculated = snapshot.calculated;
        this.calculationType = snapshot?.calculationType ?? GpCalculator.CALC_TYPES.singleClientSingleNurse;
        this.wholeHome = snapshot?.wholeHome === 1;

        // Build clients lookup by (clientKey, slotIndex)
        const clientsByKey = new Map();
        for (const c of (snapshot.clients || [])) {
            c.showPermOnly = snapshot?.showPermOnly;
            const client = this.buildClientFromJSON(c);

            // push into collection
            this.clientCollection.clients.push(client);

            const key = `${String(client.clientKey)}:${String(client.slotIndex)}`;
            clientsByKey.set(key, client);
        }

        // Build nurses lookup by nurseKey = clientId:nurseId:index
        const nursesByKey = new Map();
        for (const n of (snapshot.nurses || [])) {
            const nurse = this.buildNurseFromJSON(n);
            const key = `${String(nurse.clientId)}:${String(nurse.nurseId)}:${String(nurse.index)}`;
            nursesByKey.set(key, nurse);
        }

        // Rebuild links, attach nurse->client pointers, and register nurses
        for (const l of (snapshot.links || [])) {
            const linkId = l.linkId ?? (crypto?.randomUUID?.() ?? `l_${Date.now()}_${Math.random().toString(16).slice(2)}`);
            const clientKey = l.clientKey ?? '';
            const slotIndex = Number.isFinite(Number(l.slotIndex)) ? Number(l.slotIndex) : 0;

            const client = clientsByKey.get(`${String(clientKey)}:${String(slotIndex)}`) ?? null;
            const nurseKey = l.nurseKey
                ?? (l.nurse ? `${l.nurse.clientId}:${l.nurse.nurseId}:${l.nurse.index}` : null);
            const nurse = nurseKey ? (nursesByKey.get(String(nurseKey)) ?? null) : null;

            if (!client || !nurse) continue;

            nurse.setClient(client);
            this.nurseCollection.register(nurse);
            const link = { linkId, clientKey: client.clientKey, slotIndex: client.slotIndex, client, nurse };
            this.links.push(link);
            this.linksById.set(linkId, link);
        }

        // Seed monotonic pair counters from restored links so new allocations continue after max index.
        try {
            this._pairIndexSeq = this._pairIndexSeq || new Map();
            for (const link of (this.links || [])) {
                const n = link?.nurse;
                if (!n) continue;
                const cid = String(n.clientId ?? link.clientKey ?? '');
                const nid = String(n.nurseId ?? '');
                const key = `${cid}:${nid}`;
                const idx = Number(n.index ?? 0);

                const prev = this._pairIndexSeq.get(key);
                if (prev === undefined || prev === null) {
                    this._pairIndexSeq.set(key, idx);
                } else {
                    this._pairIndexSeq.set(key, Math.max(Number(prev), idx));
                }
            }
        } catch (e) {}

        // Render all rows exactly from restored models
        // Keep Whole Home (isOldNurse=1) at the bottom, and insert the Whole Home container
        // right before the first old nurse row.
        const isOld = (l) => this.isOldNurseLink(l);
        const normalLinks = (this.links || []).filter(l => !isOld(l));
        const oldLinks = (this.links || []).filter(l => isOld(l));
        // Keep internal ordering consistent
        this.links = [...normalLinks, ...oldLinks];
        //render manually added rows at the top
        let first = 1;
        for (const link of normalLinks) {
            this.renderAddedRow(link, first === 1);
            if (oldLinks.length && first === 1) {

                    link?.client?.setPermOnly(snapshot.showPermOnly)
                    link?.client?.showHidePermOnly(true)


            }
            first++;
        }
        //render whole home afterward
        //applying buttons behaviour on whole home
        if (oldLinks.length) {
            this.ensureWholeHomeContainer();
            for (const link of oldLinks) {
                this.renderWholeHomeRow(link);
            }
            this.applyWholeHomeBehaviour()
        }
        //restoring UI state from objects
        if (hydrate) {
            await this.hydrateAfterRestore();
        }
        //if it was previously calculated - restore calculation widgets
        if (this.calculated) {
            await this.calculate();
        }
    }

    applyWholeHomeBehaviour() {
        const wholeHomeBtn = document.getElementById(GpCalculatorConfig.actions.wholeHome.replace('#', ''));
        const clientBtn = document.getElementById(GpCalculatorConfig.actions.addClient.replace('#', ''));
        const showPermOnly = document.getElementById('permOnlyBlock');
        if (clientBtn) {
            clientBtn.disabled = 'disabled';
        }

        if (wholeHomeBtn) {
            wholeHomeBtn.disabled = 'disabled';
        }
    }

    resetMenu() {
        const wholeHomeBtn = document.getElementById(GpCalculatorConfig.actions.wholeHome.replace('#', ''));
        const clientBtn = document.getElementById(GpCalculatorConfig.actions.addClient);
        const calculateBtn = document.getElementById(GpCalculatorConfig.actions.calculate.replace('#', ''));

        if (clientBtn)  clientBtn.removeAttribute('disabled');
        if (wholeHomeBtn) wholeHomeBtn.removeAttribute('disabled');
        if (calculateBtn) calculateBtn.removeAttribute('disabled');
    }

    /**
     * After restore: reload dependent dropdowns and ensure messages/values match previous state.
     * - reload payer dropdown options for each row based on respite state
     * - keep saved payer value selected (do NOT derive from _selectedClientData)
     * - show respiteMsg only when respite is ON and payer is missing/invalid
     * - refresh nurse discipline options via getService (no selection)
     */
    async hydrateAfterRestore({ payerTtlMs = 60 * 1000 } = {}) {
        this._isHydrating = true;
        try {
            if (!this.persistenceService) return;

            // Unique clients (by object identity)
            const uniqueClients = Array.from(new Set((this.links || []).map(l => l.client).filter(Boolean)));

            // 1) Per-row payer dropdown + respiteMsg + checkbox state
            for (const link of (this.links || [])) {
                const row = document.querySelector(`.clientSelectionContainerRow[data-link-id="${link.linkId}"]`);
                if (!row) continue;

                const client = link.client;
                if (!client) continue;
                const isRespite = client?.respite ?? 0;
                const payerVal = String(this.getFormItemValue(client?.clientPayer) ?? '');

                const payerSelect = row.querySelector('select[id^="clientPayer_"], select[name^="clientPayer"]');
                const respiteMsg = row.querySelector('[id^="respiteMsg_"], .respiteMsg');
                const respiteToggle = row.querySelector('input[type="checkbox"].respite, input.respite[type="checkbox"], input[id^="respite_"][type="checkbox"]');

                if (respiteToggle) {
                    // Rebind label -> checkbox to avoid stale label[for] after restore
                    const sw = respiteToggle.closest('.custom-control, .custom-switch, .form-group') ?? row;
                    const lbl = sw?.querySelector('label.custom-control-label')
                        ?? (respiteToggle.id ? sw?.querySelector(`label[for="${respiteToggle.id}"]`) : null)
                        ?? sw?.querySelector('label');
                    if (lbl && respiteToggle.id) {
                        lbl.setAttribute('for', respiteToggle.id);
                    }

                    respiteToggle.checked = isRespite;
                }

                // Load payer dropdown options for current respite state
                let apiResult = null;
                if (typeof this.persistenceService.loadPayerDropdown === 'function') {
                    try {
                        apiResult = await this.persistenceService.loadPayerDropdown(
                            {isRespite: Boolean(isRespite)},
                            {ttlMs: payerTtlMs}
                        );
                    } catch (e) {
                        try {
                            apiResult = await this.persistenceService.loadPayerDropdown(Boolean(isRespite), {ttlMs: payerTtlMs});
                        } catch (e2) {
                            apiResult = null;
                        }
                    }
                }

                const rawOptions = Array.isArray(apiResult)
                    ? apiResult
                    : (apiResult?.data ?? apiResult?.items ?? apiResult?.options ?? []);
                const normalized = this.normalizePayerOptions(rawOptions);
                const hasMatch = payerVal
                    ? normalized.some(o => String(o.value) === String(payerVal))
                    : false;

                if (payerSelect) {
                    this.replaceSelectOptions(
                        payerSelect,
                        normalized.map(o => ({
                            value: o.value,
                            label: o.label,
                            selected: String(o.value) === String(payerVal)
                        })),
                        {includeEmpty: true}
                    );

                    // set selection (even if empty)
                    payerSelect.value = payerVal;
                    // rebuild/refresh multiselect UI (some versions need val() after rebuild)
                    try {
                        if (this.$) {
                            const $ps = this.$(payerSelect);
                            if (typeof $ps.multiselect === 'function') {
                                $ps.multiselect('rebuild');
                                $ps.val(payerVal);
                                try {
                                    $ps.multiselect('refresh');
                                } catch (e) {
                                }
                            }
                        }
                    } catch (e) {
                    }
                }

                // Show message only when respite ON and payer missing/invalid
                if (respiteMsg) {
                    if (isRespite && (!payerVal || !hasMatch)) respiteMsg.classList.remove('hide');
                    else respiteMsg.classList.add('hide');
                }
            }

            // 2) Refresh nurse discipline options for each client (uses current payer/respite/patient)
            for (const client of uniqueClients) {
                await this.updateNurseDisciplineForClient(client, {preserveSelected: true});
            }
        } finally {
            this._isHydrating = false;
        }
    }

    normalizePayerOptions(rawOptions) {
        const list = Array.isArray(rawOptions) ? rawOptions : [];
        return list
            .map(o => {
                const labelRaw = o?.label ?? o?.name ?? o?.text ?? o?.title ?? o?.value ?? o?.id ?? o?.key ?? '';
                let valueRaw   = o?.value ?? o?.name ?? o?.text ?? o?.title ?? o?.label ?? '';

                const label = String(labelRaw ?? '').trim();
                let value = valueRaw;

                const isNumberish =
                    (typeof value === 'number') ||
                    (typeof value === 'string' && /^\d+$/.test(value.trim()));

                if (isNumberish && label) value = label;

                value = String(value ?? '').trim();
                return { value, label: (label || value) };
            })
            .filter(o => o.value);
    }

    syncModelsFromDom() {
        for (const link of (this.links || [])) {
            const row = document.querySelector(`.clientSelectionContainerRow[data-link-id="${link.linkId}"]`);
            if (!row) continue;

            const client = link.client;
            const nurse = link.nurse;

            // client respite from checkbox
            if (client) {
                const respiteToggle = row.querySelector('input[type="checkbox"].respite, input.respite[type="checkbox"], input[id^="respite_"][type="checkbox"]');
                if (respiteToggle) {
                    const v = respiteToggle.checked ? 1 : 0;
                    this.setFormItemValue(client.respiteHidden, v);
                    this.setFormItemValue(client.respite, v);
                }
            }

            // nurse numbers
            if (nurse) {
                const hours = row.querySelector('input[id^="hours"], input[name^="hours"]');
                if (hours) this.setFormItemValue(nurse.hours, hours.value);

                const desiredRate = row.querySelector('input[id^="desiredRate"], input[name^="desiredRate"]');
                if (desiredRate) this.setFormItemValue(nurse.desiredRate, desiredRate.value);

                const prevHours = row.querySelector('input[id^="prevHours"], input[name^="prevHours"], input[id^="preHours"], input[name^="preHours"]');
                if (prevHours) this.setFormItemValue(nurse.prevHours, prevHours.value);
            }
        }
    }
    toJSON() {
        const clients = this.clientCollection?.toJSON
            ? this.clientCollection.toJSON()
            : [];

        const nurses = this.nurseCollection?.toJSON
            ? this.nurseCollection.toJSON()
            : [];

        // Use a stable key for a nurse row (unique even for duplicates)
        const nurseKeyOf = (n) => `${n.clientId}:${n.nurseId}:${n.index}`;

        const links = (this.links ?? []).map(l => {
            const client = l.client;
            const nurse  = l.nurse;

            const clientKey =
                client?.clientKey ??
                l.clientKey ??
                null;

            const slotIndex =
                client?.slotIndex ??
                l.slotIndex ??
                null;

            return {
                linkId: l.linkId ?? null,
                clientKey,
                slotIndex,
                nurseKey: nurse ? nurseKeyOf(nurse) : null,
                // optional but handy for debugging/restore:
                nurseId: nurse?.nurseId ?? null,
                nurseIndex: nurse?.index ?? null,
                nurseClientId: nurse?.clientId ?? null,
            };
        });

        const permOnly = document.querySelector('input[class^="perm-only1"]');

        return {
            schemaVersion: 1,
            calculationType: this.calculationType,
            calculated: this.calculated,
            showPermOnly: permOnly?.value === 'true' ? 1 : 0,
            wholeHome: this.wholeHome ? 1 : 0,
            clients,
            nurses,
            links,
        };
    }

    /**
     * Save current calculator snapshot via persistence service.
     * For now (testing) this stores to localStorage/memory in GpPersistenceService.
     *
     * @param {Object} [options]
     * @param {string|number} [options.id]       Optional id to overwrite (e.g. 'last')
     * @param {boolean} [options.persist=true]   Persist to localStorage (survive reload)
     */
    async saveGp(options = {}) {
        if (!this.persistenceService || typeof this.persistenceService.saveGp !== 'function') {
            console.warn('GpCalculator.saveGp: persistenceService.saveGp is not available');
            return { status: false, message: 'Persistence service not available' };
        }

        try { this.syncModelsFromDom(); } catch (e) {}
        const snapshot = this.toJSON();

        try {
            // Signature supported by our service: saveGp(snapshot, extraBody={}, options={})
            return await this.persistenceService.saveGp(snapshot, {}, {
                id: this.id !== null ? this.id : options?.id,
                persist: options.persist,
                type: 'db'
            });
        } catch (e) {
            console.error('GpCalculator.saveGp failed:', e);
            return { status: false, message: String(e?.message ?? e) };
        }
    }

    async calculate() {
        try {
            const calculatorPayload = this.toJSON(); // same shape you save

            const result = await this.persistenceService.requestCalculation(calculatorPayload);

            if (!result || result.status !== true || !result.data || !Array.isArray(result.data.nurses)) {
                console.error('Calculate: invalid response', result);
                return;
            }

            // Map response nurses by composite key
            const byKey = new Map();
            for (const n of result.data.nurses) {
                byKey.set(`${n.clientId}:${n.nurseId}:${n.index}`, n);
            }

            // Apply total_gp into Nurse models + rendered nurseGP output
            if (Array.isArray(this.links)) {
                for (const link of this.links) {
                    const nurse = link?.nurse;
                    if (!nurse) continue;

                    const fresh = byKey.get(`${nurse.clientId}:${nurse.nurseId}:${nurse.index}`);
                    if (!fresh) continue;

                    nurse.applyCalculatedGp?.(fresh.total_gp + ' %');
                }
            }

            // Optional: keep totals for UI
            this.lastCalculation = {
                totals: result.data.totals ?? null,
                ideal_table: result.data.ideal_table ?? null,
                client_data: result.data.client_data ?? null,
            };

            this.lastCalculationResponse = result;

            // IdeaTable mount may not exist until after first row render
            this.ensureIdeaTableMounted();
            if (this.ideaTable && typeof this.ideaTable.update === 'function') {
                this.ideaTable.update(result);
                this.ideaTable.setVisible();
            }

            this.ensureEscalationGuideTableMounted();
            if (this.escalationGuideTable && typeof this.escalationGuideTable.update === 'function') {
                this.escalationGuideTable.update(result);
                this.escalationGuideTable.setVisible();
            }

            // Keep popup content fresh, but never auto-open it
            if (this.infoPopup && typeof this.infoPopup.setResult === 'function') {
                this.infoPopup.setResult(result);
            }
            this.calculated = true;

        } catch (err) {
            console.error('Calculate failed', err);
        }
    }

    addClient(cloneNurse = true) {
        this.calculationType = GpCalculator.CALC_TYPES.multipleClientsSingleNurse;
        // Clone nurse from the very first link (row 0), but create a brand new (empty) client.
        // If there is no existing row yet, fall back to creating a completely new record.
        const sourceLink = Array.isArray(this.links) && this.links.length ? this.links[0] : null;
        if (!sourceLink) {
            return this.createNewRecord();
        }

        const sourceNurse = sourceLink?.nurse ?? null;

        // 1) Create empty client
        const client = this.clientCollection.createEmpty({
            payers: this.payers,
            branches: this.branches
        });

        const clientKey = client.clientKey;
        const slotIndex = client.slotIndex;

        // 2) Create nurse (cloned selection from the first row, if possible)
        let nurse;

        if (cloneNurse && sourceNurse) {
            const clonedNurseId = sourceNurse.nurseId
            const clonedName = String(this.getFormItemValue(sourceNurse?.nurseName) ?? '');
            const clonedDiscipline = String(this.getFormItemValue(sourceNurse?.gpNurseDiscipline) ?? '');
            const clonedHours = this.getFormItemValue(sourceNurse?.hours) ?? 0;
            const clonedPrevHours = this.getFormItemValue(sourceNurse?.prevHours) ?? 0;
            const clonedRate = this.getFormItemValue(sourceNurse?.desiredRate) ?? 0;

            nurse = this.nurseCollection.createEmpty({
                clientId: clientKey,
                selectedPayer: '',
                isRespite: false,
                disciplines: this.disciplines,
                useServiceLabel: this.useServiceLabel
            });
            nurse.nurseId = clonedNurseId
            nurse.nurseName.attributes.disabled = 'disabled';

            nurse.setClient(client);

            // Minimal fields needed so the nurse selection is displayed as cloned
            this.setFormItemValue(nurse.id, clonedNurseId);
            this.setFormItemValue(nurse.aocClinicianId, clonedNurseId);
            this.setFormItemValue(nurse.nurseName, clonedName);
            this.setFormItemValue(nurse.hours, clonedHours);
            this.setFormItemValue(nurse.prevHours, clonedPrevHours);
            this.setFormItemValue(nurse.desiredRate, clonedRate);

            // Keep any cached autocomplete payload for downstream logic (optional)
            nurse._selectedCandidateData = sourceNurse._selectedCandidateData ?? null;

            // Ensure per-(clientId,nurseId) index is stable and unique
            const pairIndex = this.getNextPairIndex(clientKey, clonedNurseId);
            nurse.setContext({ clientId: clientKey, nurseId: clonedNurseId, index: pairIndex });
        } else {
            nurse = this.nurseCollection.createEmpty({
                clientId: clientKey,
                selectedPayer: '',
                isRespite: false,
                disciplines: this.disciplines,
                useServiceLabel: this.useServiceLabel
            });

            nurse.setClient(client);

            const pairIndex = this.getNextPairIndex(clientKey, nurse?.nurseId);
            nurse.setContext({ clientId: clientKey, nurseId: nurse?.nurseId, index: pairIndex });
        }

        // 3) Register + render
        const linkId = (crypto?.randomUUID?.() ?? `l_${Date.now()}_${Math.random().toString(16).slice(2)}`);
        const link = { linkId, clientKey, slotIndex, client, nurse };

        this.links.push(link);
        this.linksById ??= new Map();
        this.linksById.set(linkId, link);

        this.renderAddedRow(link);
        return link;
    }

    addClinician(cloneClient = true) {
        this.calculationType = GpCalculator.CALC_TYPES.singleClientMultipleNurses;

        // Clone client from the very first link (row 0), but create a brand new (empty) nurse.
        // If there is no existing row yet, fall back to creating a completely new record.
        const sourceLink = Array.isArray(this.links) && this.links.length ? this.links[0] : null;
        if (!sourceLink) {
            return this.createNewRecord();
        }

        const sourceClient = sourceLink?.client ?? null;
        if (!sourceClient) {
            return this.createNewRecord();
        }

        // 1) Duplicate client (same patient) but with a new slotIndex so DOM keys stay unique
        const baseClientKey = String(sourceClient?.clientKey ?? sourceLink?.clientKey ?? this.getFormItemValue(sourceClient?.aocPatientId) ?? '');
        const basePatientId = String(this.getFormItemValue(sourceClient?.aocPatientId) ?? baseClientKey ?? '');

        const nextSlotIndexForClientKey = (clientKey) => {
            const ck = String(clientKey ?? '');
            const list = (this.clientCollection?.clients || []).filter(c => String(c?.clientKey ?? this.getFormItemValue(c?.aocPatientId) ?? '') === ck);
            const max = list.reduce((m, c) => Math.max(m, Number(c?.slotIndex ?? 0)), -1);
            return max + 1;
        };

        const slotIndex = nextSlotIndexForClientKey(baseClientKey);

        const isRespite = Number(this.getFormItemValue(sourceClient?.respiteHidden ?? sourceClient?.respite) ?? 0) === 1;
        const specialized = this.getFormItemValue(sourceClient?.specializedHidden ?? sourceClient?.specialized) ?? 0;
        const payerVal = String(this.getFormItemValue(sourceClient?.clientPayer) ?? '');

        const dupClient = cloneClient
            ? this.buildClientFromJSON({
                clientKey: baseClientKey,
                slotIndex,
                patientId: basePatientId,
                clientSelection: String(this.getFormItemValue(sourceClient?.clientSelection) ?? ''),
                clientPayer: payerVal,
                branchId: String(this.getFormItemValue(sourceClient?.branch) ?? ''),
                specialized,
                respite: isRespite ? 1 : 0,

                // keep both payers so respite toggle logic stays consistent
                payerNormal: sourceClient?._payerNormal ?? sourceClient?._selectedClientData?.payer ?? '',
                payerRespite: sourceClient?._payerRespite ?? sourceClient?._selectedClientData?.respiteTerminalPlayer ?? '',

                // perm-only state (Whole Home uses base client, but keep clones consistent)
                showPermOnly: String(sourceClient?.showPermOnlyHidden?.value ?? '') === 'true' ? 1 : 0,
                permOnlyWeekStart: String(sourceClient?.permOnlyWeekStart?.value ?? ''),
                permOnlyWeekEnd: String(sourceClient?.permOnlyWeekEnd?.value ?? ''),
            })
            : this.clientCollection.createEmpty({ payers: this.payers, branches: this.branches });

        // Copy selected client data for downstream payer syncing
        if (cloneClient) {
            dupClient._payerNormal = sourceClient?._payerNormal ?? sourceClient?._selectedClientData?.payer ?? '';
            dupClient._payerRespite = sourceClient?._payerRespite ?? sourceClient?._selectedClientData?.respiteTerminalPlayer ?? '';
            dupClient._selectedClientData = {
                payer: dupClient._payerNormal,
                respiteTerminalPlayer: dupClient._payerRespite
            };

            // Lock the client selection for additional clinicians (same patient)
            try {
                if (dupClient?.clientSelection?.attributes) {
                    dupClient.clientSelection.attributes.disabled = 'disabled';
                }
            } catch (e) {}
        }

        // Register duplicated client so it persists in snapshot/restore
        this.clientCollection.clients = this.clientCollection.clients || [];
        this.clientCollection.clients.push(dupClient);

        const clientKey = dupClient.clientKey;

        // 2) Create empty nurse for this client
        const nurse = this.nurseCollection.createEmpty({
            clientId: clientKey,
            selectedPayer: payerVal || '',
            isRespite: Boolean(isRespite),
            disciplines: this.disciplines,
            useServiceLabel: this.useServiceLabel
        });

        nurse.setClient(dupClient);

        // Ensure per-(clientId,nurseId) index is stable and unique
        const pairIndex = this.getNextPairIndex(clientKey, nurse?.nurseId);
        nurse.setContext({ clientId: clientKey, nurseId: nurse?.nurseId, index: pairIndex });

        // 3) Register + render
        const linkId = (crypto?.randomUUID?.() ?? `l_${Date.now()}_${Math.random().toString(16).slice(2)}`);
        const link = { linkId, clientKey, slotIndex: dupClient.slotIndex ?? slotIndex, client: dupClient, nurse };

        this.links.push(link);
        this.linksById ??= new Map();
        this.linksById.set(linkId, link);

        this.renderAddedRow(link);

        // 4) Hydrate payer options & disciplines for cloned client (best-effort, non-blocking)
        try {
            const rowEl = document.querySelector(`.clientSelectionContainerRow[data-link-id="${linkId}"]`);
            if (rowEl) {
                void this.syncPayerByRespiteSwitch({ row: rowEl, client: dupClient, isRespite: Boolean(isRespite) });
            }
        } catch (e) {}

        try {
            void this.updateNurseDisciplineForClient(dupClient, { preserveSelected: true });
        } catch (e) {}

        return link;
    }

    async requestWholeHome() {
        if (!this.persistenceService || typeof this.persistenceService.requestWholeHome !== 'function') {
            console.warn('GpCalculator.requestWholeHome: persistenceService.requestWholeHome is not available');
            return { status: false, message: 'Persistence service not available' };
        }

        // pick the first client (Whole Home is derived from the first selected patient)
        const firstClient = this.getBaseClient();
        this.wholeHome = true;

        const patientId = firstClient
            ? (this.getFormItemValue(firstClient?.aocPatientId) ?? firstClient?.clientKey ?? null)
            : null;


        firstClient?.showHidePermOnly(true);


        if (!patientId) {
            return { status: false, message: 'No client selected' };
        }

        const res = await this.persistenceService.requestWholeHome({
            aocPatientId: patientId,
            showPermOnly: firstClient.showPermOnlyHidden.value,
            permOnlyWeekStart: firstClient?.permOnlyWeekStart.value,
            permOnlyWeekEnd: firstClient?.permOnlyWeekEnd.value
        });

        const list = Array.isArray(res)
            ? res
            : (Array.isArray(res?.data) ? res.data
                : (Array.isArray(res?.items) ? res.items
                    : (Array.isArray(res?.nurses) ? res.nurses
                        : (Array.isArray(res?.data?.nurses) ? res.data.nurses : []))));
        this.calculationType = GpCalculator.CALC_TYPES.singleClientMultipleNurses;
        this.addWholeHomeRow(Array.isArray(list) ? list : []);
        return res;
    }

    // ----------------------
    // Whole Home rendering
    // ----------------------

    ensureWholeHomeContainer() {
        const existing = document.getElementById('nurseGPFormCnt');
        if (existing) return existing;

        const host = this.rowsBlock
            ?? document.getElementById('clientSelectionContainer')
            ?? document.getElementById('clientSelectionContainerRow')
            ?? null;

        if (!host) return null;

        host.insertAdjacentHTML(
            'beforeend',
            `<div class="col-md-12 d-flex nurseGPFormCnt" id="nurseGPFormCnt"><div class="col-md-4"></div><div class="col-md-8"><div class="col-md-10 m-3"><h3 class='extra-heading'> Whole Home </h3></div></div>`
        );

        return document.getElementById('nurseGPFormCnt');
    }

    isOldNurseLink(link) {
        const nurse = link?.nurse ?? null;
        if (!nurse) return false;

        // Support both FormItem and plain numeric shapes.
        const v =
            this.getFormItemValue(nurse?.isOldNurse)
            ?? nurse?.data?.isOldNurse
            ?? nurse?.data?.is_old_nurse
            ?? nurse?._isOldNurse
            ?? 0;

        return Boolean(Number(v) === 1);
    }

    renderWholeHomeRow(link) {
        if (!this.rowsBlock) {
            console.warn('GpCalculator.renderWholeHomeRow: containers not ready');
            return;
        }

        const client = link?.client;
        const nurse  = link?.nurse;
        if (!client || !nurse) return;

        // We render full row (client+nurse) but hide client DOM
        const clientHtml = client.render(false, true, this.renderPermOnly);
        const nurseHtml  = nurse.render({ renderMenu: false, renderClientProperties: true, wholeHome: true });

        const html = `
            <div class="clientSelectionContainerRow addBorder whole-home-row" data-link-id="${link.linkId}">
                <div class="col-md-12 d-flex">
                    ${clientHtml}
                    ${nurseHtml}
                </div>
            </div>
        `.trim();

        this.rowsBlock.insertAdjacentHTML('beforeend', html);
        const rowEl = document.querySelector(`.clientSelectionContainerRow[data-link-id="${link.linkId}"]`);
        const newIndex = Number.isFinite(Number(link?.slotIndex)) ? Number(link.slotIndex) : null;
        if (rowEl && newIndex !== null) {
            this.applyDomKeysToRow(rowEl, { oldIndex: 0, newIndex });
        }
        this.ensureIdeaTableMounted();
        return link;
    }

    /**
     * Adds Whole Home nurse rows based on the provided array.
     */
    async addWholeHomeRow(items = []) {

        if (!Array.isArray(items) || !items.length) return;

        const baseClient = this.clientCollection?.clients?.[0] ?? this.links?.[0]?.client ?? null;
        if (!baseClient) return;

        // Whole Home heading appears once
        this.ensureWholeHomeContainer();

        const basePatientId = String(this.getFormItemValue(baseClient?.aocPatientId) ?? baseClient?.clientKey ?? '');
        if (!basePatientId) return;

        // helper: next slotIndex for same patient (so duplicates stay unique)
        const nextSlotIndexForPatient = (patientId) => {
            const pid = String(patientId);
            const list = (this.clientCollection?.clients || []).filter(c => String(this.getFormItemValue(c?.aocPatientId) ?? c?.clientKey ?? '') === pid);
            const max = list.reduce((m, c) => Math.max(m, Number(c?.slotIndex ?? 0)), -1);
            return max + 1;
        };

        for (const raw of items) {

            const linkId = (crypto?.randomUUID?.() ?? `wh_${Date.now()}_${Math.random().toString(16).slice(2)}`);

            // priority: 1 => non-respite, 3 => respite
            const priority = Number(raw?.priority ?? 1);
            const isRespite = (priority === 3);

            // ----- Duplicate client (same patient) -----
            const slotIndex = nextSlotIndexForPatient(basePatientId);

            // Build new client from JSON using your existing builder so FormItems are created correctly
            const dupClient = this.buildClientFromJSON({
                clientKey: baseClient.clientKey ?? basePatientId,
                slotIndex,
                patientId: basePatientId,
                clientSelection: this.getFormItemValue(baseClient.clientSelection) ?? '',
                clientPayer: '', // we'll set below
                branchId: String(this.getFormItemValue(baseClient.branch) ?? ''),
                specialized: this.getFormItemValue(baseClient.specializedHidden ?? baseClient.specialized) ?? 0,
                respite: isRespite ? 1 : 0,

                // keep both payers so toggle logic stays consistent
                payerNormal: baseClient?._payerNormal ?? baseClient?._selectedClientData?.payer ?? '',
                payerRespite: baseClient?._payerRespite ?? baseClient?._selectedClientData?.respiteTerminalPlayer ?? ''
            });

            // copy selected client data (so syncPayerByRespiteSwitch can work if needed)
            dupClient._payerNormal = baseClient?._payerNormal ?? baseClient?._selectedClientData?.payer ?? '';
            dupClient._payerRespite = baseClient?._payerRespite ?? baseClient?._selectedClientData?.respiteTerminalPlayer ?? '';
            dupClient._selectedClientData = {
                payer: dupClient._payerNormal,
                respiteTerminalPlayer: dupClient._payerRespite
            };

            // Set respite flags in model (both hidden + main)
            this.setFormItemValue(dupClient.respiteHidden, isRespite ? 1 : 0);
            this.setFormItemValue(dupClient.respite, isRespite ? 1 : 0);

            // Set payer based on respite state
            const payerVal = isRespite ? dupClient._payerRespite : dupClient._payerNormal;
            if (dupClient?.clientPayer && typeof dupClient.clientPayer === 'object' && 'value' in dupClient.clientPayer) {
                dupClient.clientPayer.value = payerVal || '';
                if (Array.isArray(dupClient.clientPayer.options)) {
                    dupClient.clientPayer.options.forEach(o => o.selected = String(o.value) === String(payerVal || ''));
                }
            }

            // Register duplicated client so it persists in snapshot/restore
            this.clientCollection.clients = this.clientCollection.clients || [];
            this.clientCollection.clients.push(dupClient);

            // ----- Create nurse from raw -----

            const clinicianId = raw.aoc_provider_id;
            const index = (typeof this.getNextPairIndex === 'function')
                ? this.getNextPairIndex(basePatientId, clinicianId, {excludeLinkId: null})
                : 0;

            const nurseName = String(raw?.nurseName ?? '');
            const discipline = String(raw?.discipline ?? '');
            const hours = Number(raw?.hours ?? 0) || 0;
            const desiredRate = raw?.desiredRate ?? '';
            const prevHours = Number(raw?.perHours ?? raw?.preHours ?? raw?.prevHours ?? 0) || 0;
            const isOldNurse = Number(raw?.isOldNurse ?? 1) === 1 ? 1 : 0;

            const nurse = new Nurse({
                clientId: basePatientId,     // keep same patient grouping
                nurseId: clinicianId,
                index,
                data: {
                    status: 'current',
                    isOldNurse,
                    isRespite: isRespite ? 1 : 0, // optional: you wanted priority to control respite; keep it here too
                    selectedPayer: payerVal || '',
                    nurseName,
                    hours,
                    desiredRate,
                    preHours: prevHours,
                    discipline,
                    gp: '',
                    aoc_provider_id: clinicianId,
                    priority: raw?.priority ?? null,
                },
                disciplines: this.disciplines,
                useServiceLabel: this.useServiceLabel,
                client: dupClient
            });

            nurse._wholeHomeRaw = raw;

            try {
                nurse.setClient?.(dupClient);
            } catch (e) {
            }
            try {
                this.nurseCollection?.register?.(nurse);
            } catch (e) {
            }
            //reload context to bring up right indexing
            nurse.setContext({ clientId: dupClient.clientKey, nurseId: nurse?.nurseId, index: index });

            // Try to set discipline FormItem if present
            try {
                if (nurse?.gpNurseDiscipline) {
                    this.setFormItemValue(nurse.gpNurseDiscipline, discipline);
                    if (Array.isArray(nurse.gpNurseDiscipline.options)) {
                        nurse.nurseName.attributes.readonly = 'readonly'
                        nurse.gpNurseDiscipline.options.forEach(o => o.selected = String(o.value) === String(discipline));
                    }
                }
            } catch (e) {
            }

            const link = {
                linkId,
                clientKey: dupClient.clientKey ?? basePatientId,
                slotIndex: dupClient.slotIndex ?? slotIndex,
                client: dupClient,
                nurse,
            };

            this.links = this.links || [];
            this.links.push(link);

            this.linksById = this.linksById || new Map();
            this.linksById.set(linkId, link);

            // Render under Whole Home (client hidden)
            this.renderWholeHomeRow(link);

            const rowEl = document.querySelector(`.clientSelectionContainerRow[data-link-id="${linkId}"]`);
            if (rowEl) {
                await this.syncPayerByRespiteSwitch({row: rowEl, client: dupClient, isRespite});
            }
            await this.updateNurseDisciplineForClient(dupClient, {preserveSelected: true});
        }
    }

    /**
     * Restore calculator snapshot by id via persistence service, then hydrate UI.
     *
     * @param {string|number} id
     * @param {Object} [options]
     * @param {boolean} [options.hydrate=true]
     */
    async restoreGp(id, { hydrate = true } = {}) {
        if (!this.persistenceService || typeof this.persistenceService.restoreGp !== 'function') {
            console.warn('GpCalculator.restoreGp: persistenceService.restoreGp is not available');
            return { status: false, message: 'Persistence service not available' };
        }

        try {
            let res = await this.persistenceService.restoreGp(id, 'db');
            if (res?.calculator) {
                res = { status: true, id: String(id), data: res.calculator}
            }
            if (!res || res.status !== true || !res.data) {
                return res || { status: false, id: String(id), data: null, message: 'Snapshot not found' };
            }

            await this.restore(res.data, { hydrate });
            return res;
        } catch (e) {
            console.error('GpCalculator.restoreGp failed:', e);
            return { status: false, id: String(id), data: null, message: String(e?.message ?? e) };
        }
    }

    findClientByKeySlot(clientKey, slotIndex) {
        const ck = String(clientKey ?? '');
        const si = String(slotIndex ?? '');

        const list = (this.clientCollection?.clients || []);
        for (const c of list) {
            const cKey = String(c?.clientKey ?? this.getFormItemValue(c?.aocPatientId) ?? '');
            const cSlot = String(c?.slotIndex ?? 0);
            if (cKey === ck && cSlot === si) return c;
        }
        return null;
    }

    getBaseClient() {
        const baseLink = (this.links || []).find(l => !this.isOldNurseLink(l)) ?? null;
        return baseLink?.client
            ?? this.clientCollection?.clients?.[0]
            ?? this.links?.[0]?.client
            ?? null;
    }

    attachPermOnlyToggleDelegation() {
        if (!this.$) return;

        // Bootstrap custom-switch: clicking label should toggle even if for/id goes stale
        this.$(document).on(
            'click',
            'label.custom-control-label[for^="show_perm_only_"], #permOnlyBlock label.custom-control-label',
            (e) => {
                const label = e.currentTarget;
                const root =
                    label.closest('#permOnlyBlock') ??
                    label.closest('.clientSelectionContainerRow') ??
                    document;

                const container = label.closest('.custom-control, .custom-switch, .form-group') ?? root;
                const checkbox = container.querySelector('input.perm-only[type="checkbox"], input[type="checkbox"].perm-only');
                if (!checkbox) return;

                e.preventDefault();
                checkbox.checked = !checkbox.checked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            }
        );


        this.$(document).on('change', 'input.perm-only[type="checkbox"], input[type="checkbox"].perm-only', (e) => {
            const checkbox = e.target;
            if (!(checkbox instanceof HTMLInputElement)) return;
            if (this._isHydrating) return;

            this.applyPermOnlyHiddenInput(checkbox);

            const client = this.getBaseClient();
            if (!client) return;
            const v = checkbox.checked ? 'true' : 'false';

            // MODEL sync (no other dependencies)
            this.setFormItemValue(client.showPermOnlyHidden, v, true);
            client.showPermOnlySwitch.attributes.checked = (checkbox.checked ? 'checked' : undefined);

            // DOM hidden sync (if present)
            const root =
                checkbox.closest('#permOnlyBlock') ??
                checkbox.closest('.clientSelectionContainerRow') ??
                document;

            const hidden = root.querySelector(
                'input[type="hidden"][data-client-field="showPermOnlyHidden"], input[type="hidden"][name^="show_perm_only_hidden"]'
            );

            if (hidden) hidden.value = v;
            const permOnlyDates = document.getElementById('permOnlyDates')
            const datesStyle = permOnlyDates?.getAttribute('style')

            if (v === 'true') {
                permOnlyDates?.setAttribute('style', datesStyle.replace('display: block;', 'display: none;'));
            } else {
                permOnlyDates?.setAttribute('style', datesStyle.replace('display: none;', 'display: block;'));
            }
            // If Whole Home is active, rerun query with updated params (debounced)
            this.scheduleWholeHomeRefresh('permOnly');

        });

        // Week Start / Week End changes (datepickers write into these inputs)
        this.$(document).on(
            'change',
            'input.perm-only-week-start, input.perm-only-week-end, input[id^="perm_only_week_start_"], input[id^="perm_only_week_end_"]',
            (e) => {
                const el = e.target;
                if (!(el instanceof HTMLInputElement)) return;
                if (this._isHydrating) return;

                // These week fields are shown for the base client (Whole Home uses the first client)
                const baseClient = this.getBaseClient();
                if (!baseClient) return;

                if (el.classList.contains('perm-only-week-start') || String(el.id || '').startsWith('perm_only_week_start_')) {
                    this.setFormItemValue(baseClient.permOnlyWeekStart, el.value);
                }

                if (el.classList.contains('perm-only-week-end') || String(el.id || '').startsWith('perm_only_week_end_')) {
                    this.setFormItemValue(baseClient.permOnlyWeekEnd, el.value);
                }

                this.scheduleWholeHomeRefresh('weekRange');
            }
        );
    }

    // ----------------------
    // Whole Home auto-refresh (Perm Only + Week Start/End)
    // ----------------------

    _isValidUsDateStr(v) {
        const s = String(v ?? '').trim();
        const m = s.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (!m) return false;
        const mm = Number(m[1]);
        const dd = Number(m[2]);
        const yy = Number(m[3]);
        if (!mm || !dd || !yy) return false;
        const d = new Date(yy, mm - 1, dd);
        d.setHours(0, 0, 0, 0);
        return d.getFullYear() === yy && d.getMonth() === (mm - 1) && d.getDate() === dd;
    }

    scheduleWholeHomeRefresh(reason = 'change', { delayMs = 400 } = {}) {
        // Only applies when Whole Home mode is active
        if (!this.wholeHome) return;

        // debounce
        if (this._wholeHomeRefreshTimer) {
            try { clearTimeout(this._wholeHomeRefreshTimer); } catch (e) {}
        }

        this._wholeHomeRefreshTimer = setTimeout(() => {
            this.refreshWholeHomeIfActive(reason).catch((e) => {
                console.warn('GpCalculator.refreshWholeHomeIfActive failed:', e);
            });
        }, delayMs);
    }

    clearWholeHomeRows() {
        // Whole Home rows are marked by isOldNurseLink() (isOldNurse=1)
        const ids = (this.links || [])
            .filter(l => this.isOldNurseLink(l))
            .map(l => l.linkId)
            .filter(Boolean);

        // Remove via existing helper to keep collections in sync
        for (const id of ids) {
            try { this.removeLinkAndRow(id, false); } catch (e) {}
        }

        // Also clear the Whole Home header container content if needed
        // (we keep the container itself; rows are separate)
    }

    async refreshWholeHomeIfActive(reason = 'change') {
        if (!this.wholeHome) return;
        if (this._wholeHomeRefreshInFlight) return;

        const baseClient = this.clientCollection?.clients?.[0] ?? this.links?.[0]?.client ?? null;
        if (!baseClient) return;

        const patientId = this.getFormItemValue(baseClient?.aocPatientId) ?? baseClient?.clientKey ?? null;
        if (!patientId || String(patientId).trim() === '' || String(patientId) === '0') return;

        // Require both dates (AC8)
        const ws = String(baseClient?.permOnlyWeekStart?.value ?? '').trim();
        const we = String(baseClient?.permOnlyWeekEnd?.value ?? '').trim();
        if (!this._isValidUsDateStr(ws) || !this._isValidUsDateStr(we)) {
            return;
        }

        this._wholeHomeRefreshInFlight = true;
        try {
            // Prevent duplicates
            this.clearWholeHomeRows();

            // Re-run Whole Home request with current params from the base client
            await this.requestWholeHome();
        } finally {
            this._wholeHomeRefreshInFlight = false;
        }
    }

    applyPermOnlyHiddenInput(checkboxEl) {
        const clientKey = checkboxEl.dataset?.clientKey ?? checkboxEl.getAttribute('data-client-key');
        const slotIndex = checkboxEl.dataset?.slotIndex ?? checkboxEl.getAttribute('data-slot-index');
        if (clientKey == null || slotIndex == null) return;

        const name = `show_perm_only_hidden[${clientKey}][${slotIndex}]`;

        const root =
            checkboxEl.closest('#permOnlyBlock') ??
            checkboxEl.closest('.clientSelectionContainerRow') ??
            document;

        let hidden = root.querySelector(`input[type="hidden"][name="${name}"]`);

        if (!hidden) {
            hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = name;
            hidden.className = 'perm-only1';
            hidden.setAttribute('data-client-key', String(clientKey));
            hidden.setAttribute('data-slot-index', String(slotIndex));
            hidden.setAttribute('data-client-field', 'showPermOnlyHidden');
            checkboxEl.parentNode?.insertBefore(hidden, checkboxEl);
        }

        hidden.value = checkboxEl.checked ? 'true' : 'false';
    }

    /**
     * Initializes a searchable multiselect on all Insurance/Payer select elements.
     */
    initPayerMultiselects() {
        if (!this.$ || typeof this.$ !== 'function') return;

        const selects = document.querySelectorAll('select[id^="clientPayer_"], select[name^="clientPayer"]');

        selects.forEach(select => {
            if (select.dataset.multiselectInitialized === '1') return;

            this.$(select).multiselect({
                enableCaseInsensitiveFiltering: true,
                enableFiltering: true,
                filterPlaceholder: 'Search',
                nonSelectedText: '--- Select ---'
            });

            select.dataset.multiselectInitialized = '1';
        });
    }

  /**
   * Initializes a searchable multiselect on all Service/Discipline select elements.
   */
  reinitNurseDisciplineMultiselects(root = document) {
    if (!this.$ || typeof this.$ !== 'function') return;

    const selects = root.querySelectorAll(
      'select[data-nurse-field="gpNurseDiscipline"], ' +
      'select[id^="gpNurseDiscipline"], ' +
      'select[name^="gpNurseDiscipline"], ' +
      'select.gpNurseDiscipline'
    );

    selects.forEach(select => {
      const $select = this.$(select);

      if (select.dataset.disciplineMultiselectInitialized === '1' && typeof $select.multiselect === 'function') {
        try {
          $select.multiselect('destroy');
        } catch (e) {
          console.warn('Failed to destroy nurse discipline multiselect before re-init', e);
        }
      }

      delete select.dataset.disciplineMultiselectInitialized;
    });

    this.initNurseDisciplineMultiselects(root);
  }

  /**
   * Initializes a searchable multiselect on all Service/Discipline select elements.
   */
  initNurseDisciplineMultiselects(root = document) {
    if (!this.$ || typeof this.$ !== 'function') return;

    const selects = root.querySelectorAll(
      'select[data-nurse-field="gpNurseDiscipline"], ' +
      'select[id^="gpNurseDiscipline"], ' +
      'select[name^="gpNurseDiscipline"], ' +
      'select.gpNurseDiscipline'
    );

    selects.forEach(select => {
      const $select = this.$(select);

      if (select.dataset.disciplineMultiselectInitialized === '1') {
        if (typeof $select.multiselect === 'function') {
          $select.multiselect('rebuild');

          try {
            $select.multiselect('refresh');
          } catch (e) {}
        }

        return;
      }

      $select.multiselect({
        enableCaseInsensitiveFiltering: true,
        enableFiltering: true,
        filterPlaceholder: 'Search',
        nonSelectedText: '--Select--',
        onChange: (option) => {
          select.value = String(option?.val?.() ?? $select.val() ?? '');
          select.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });

      select.dataset.disciplineMultiselectInitialized = '1';
    });
  }
}
