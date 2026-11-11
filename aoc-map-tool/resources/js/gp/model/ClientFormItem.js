// ClientFormItem.js

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

/**
 * One input/select/checkbox/span for the Client form.
 *
 * Naming scheme: fieldName[clientKey][slotIndex]
 * - clientKey = real client_id (aoc_patient_id) OR temporary key before selection
 * - slotIndex = 0,1,2,... for multiple (respite/non-respite) rows per same client
 */
export default class ClientFormItem {
    constructor({
                    type,               // 'text', 'hidden', 'number', 'select', 'checkbox', 'output'
                    baseId,             // e.g. 'clientSelectionForGP_'
                    baseName,           // e.g. 'clientSelectionForGP'
                    clientKey,          // first dimension: client_id (or temp key)
                    slotIndex,          // second dimension: index per client
                    fieldKey,           // logical field name: 'clientSelection', 'patientId', 'payer', ...
                    value = '',
                    attributes = {},
                    options = []        // used for <select>
                }) {
        this.type = type;
        this.baseId = baseId;
        this.baseName = baseName;

        this.clientKey = clientKey;
        this.slotIndex = slotIndex;

        this.fieldKey = fieldKey;
        this.value = value;
        this.attributes = { ...attributes };
        this.options = options;
        this.el = null;
        this.rebuildDomKeys();
    }

    static buildId(baseId, clientKey, slotIndex) {
        // Example: clientSelectionForGP_123_0
        return `${baseId}${clientKey}_${slotIndex}`;
    }

    static buildName(baseName, clientKey, slotIndex) {
        // Example: clientPayer[123][0]
        return `${baseName}[${clientKey}][${slotIndex}]`;
    }


    /**
     * Apply the current computed id/name/attributes onto the bound element (or provided element).
     * This is the missing piece: `rebuildDomKeys()` only updates the model, not existing DOM.
     */
    applyDomKeys(el = this.el, oldId = null) {
        if (!el) return;

        // capture previous id before we overwrite it
        const prevId = oldId ?? el.getAttribute('id');

        // id / name
        if (this.id) el.id = this.id;
        else el.removeAttribute('id');

        // Fix stale <label for="..."> bindings (e.g. toggle widgets)
        // If we changed the element id, update any label that still points to the old id.
        if (typeof document !== 'undefined' && prevId && this.id && prevId !== this.id) {
            const labels = document.getElementsByTagName('label');
            for (let i = 0; i < labels.length; i++) {
                const lbl = labels[i];
                if (lbl.getAttribute('for') === prevId) {
                    lbl.setAttribute('for', this.id);
                }
            }
        }

        if (this.name) el.setAttribute('name', this.name);
        else el.removeAttribute('name');

        // attributes map -> real DOM attributes
        for (const [key, val] of Object.entries(this.attributes || {})) {
            if (val === false || val === null || val === undefined) {
                el.removeAttribute(key);
                continue;
            }
            if (val === true) {
                el.setAttribute(key, key);
                continue;
            }
            el.setAttribute(key, String(val));
        }

        // keep checkbox checked state consistent if applicable
        if (this.type === 'checkbox') {
            if (this.attributes && this.attributes.checked) el.checked = true;
        }
    }


    rebuildDomKeys() {
        const oldId = this.id;
        const oldName = this.name;

        this.id = ClientFormItem.buildId(this.baseId, this.clientKey, this.slotIndex);
        this.name = this.baseName
            ? ClientFormItem.buildName(this.baseName, this.clientKey, this.slotIndex)
            : undefined;

        // Attributes used by event delegation
        this.attributes['data-client-key'] = this.clientKey;
        this.attributes['data-slot-index'] = this.slotIndex;
        this.attributes['data-client-field'] = this.fieldKey;

        // If we already have a bound element, update it immediately.
        // If not, try to locate the element by the previous id (works when context changes after render).
        if (this.el) {
            this.applyDomKeys(this.el, oldId);
        } else {
            const doc = (typeof document !== 'undefined') ? document : null;
            if (doc && oldId && oldId !== this.id) {
                const found = doc.getElementById(oldId);
                if (found) {
                    this.el = found;
                    this.applyDomKeys(found, oldId);
                }
            }
        }
    }

    disable() {
        this.attributes.disabled = 'disabled'
        document.getElementById(this.id).disabled = 'disabled'
    }

    setContext({ clientKey, slotIndex }) {
        this.clientKey = clientKey;
        this.slotIndex = slotIndex;
        this.rebuildDomKeys();
    }

    renderAttributes({ includeId = true, includeName = true } = {}) {
        const attrs = { ...this.attributes };

        if (includeId && this.id) attrs.id = this.id;
        if (includeName && this.name) attrs.name = this.name;

        const parts = [];
        for (const [key, val] of Object.entries(attrs)) {
            if (val === false || val === null || val === undefined) continue;
            if (val === true) parts.push(key);
            else parts.push(`${key}="${escapeHtml(String(val))}"`);
        }

        return parts.length ? ' ' + parts.join(' ') : '';
    }

    renderInput() {
        const attrs = this.renderAttributes();
        const valueAttr =
            this.type === 'checkbox' ||
            this.type === 'hidden' ||
            this.type === 'number' ||
            this.type === 'text'
                ? ` value="${escapeHtml(this.value ?? '')}"`
                : '';

        const typeAttr = this.type === 'checkbox' ? 'checkbox' : this.type;

        return `<input type="${escapeHtml(typeAttr)}"${attrs}${valueAttr}/>`;
    }

    renderSelect() {
        const attrs = this.renderAttributes();
        const opts = this.options
            .map(o => {
                const selected = o.selected ? ' selected="selected"' : '';
                return `<option value="${escapeHtml(o.value ?? '')}"${selected}>${escapeHtml(o.label ?? '')}</option>`;
            })
            .join('');
        return `<select${attrs}>${opts}</select>`;
    }

    renderOutput(tag = 'span') {
        const attrs = this.renderAttributes({ includeName: false });
        return `<${tag}${attrs}>${escapeHtml(this.value ?? '')}</${tag}>`;
    }

    /**
     * Sync this item from a DOM element (for input/change events).
     */
    syncFromElement(el) {
        const tag = el.tagName.toLowerCase();

        if (this.type === 'checkbox') {
            this.attributes.checked = el.checked || undefined;
            this.value = el.checked ? el.value : this.value; // usually '1'
        } else if (this.type === 'select' || tag === 'select') {
            this.value = el.value;
            this.options.forEach(o => {
                o.selected = (String(o.value) === String(el.value));
            });
        } else {
            this.value = el.value;
        }
    }
}
