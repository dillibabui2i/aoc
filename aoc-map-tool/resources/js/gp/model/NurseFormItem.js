

// NurseFormItem.js

import Nurse from "./Nurse";

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export default class NurseFormItem {
    constructor({
                    type,
                    baseId,
                    baseName,
                    clientId,
                    nurseId,
                    index,
                    fieldKey,
                    value = '',
                    attributes = {},
                    options = []
                }) {
        this.type = type;
        this.baseId = baseId;
        this.baseName = baseName;

        this.clientId = clientId;
        this.nurseId = nurseId;
        this.index = index;

        this.fieldKey = fieldKey;
        this.value = value;
        this.attributes = { ...attributes };
        this.options = options;
        this.el = null; // optional bound DOM element
        const tempId = NurseFormItem.buildId(this.baseId, this.clientId, this.nurseId, this.index);
        if (tempId.includes('tmp_')) {
            this.tmpId = tempId;
        }
        this.rebuildDomKeys();
    }

    static buildId(baseId, clientId, nurseId, index) {
        // IDs: baseId + {client_id}_{nurse_id}_{increment}
        const suffix = `${clientId}_${nurseId}_${index}`;
        return `${baseId}${suffix}`;
    }

    static buildName(baseName, clientId, nurseId, index) {
        // Matches Blade pattern: name="field[clientId][nurseId][index]"
        return `${baseName}[${clientId}][${nurseId}][${index}]`;
    }

    rebuildDomKeys() {
        const oldId = this.id;
        const oldName = this.name;

        this.id = NurseFormItem.buildId(this.baseId, this.clientId, this.nurseId, this.index);
        this.name = this.baseName
            ? NurseFormItem.buildName(this.baseName, this.clientId, this.nurseId, this.index)
            : undefined;

        // data-* used for event delegation
        this.attributes['data-index'] = this.index;
        this.attributes['data-client-id'] = this.clientId;
        this.attributes['data-nurse-id'] = this.nurseId;
        this.attributes['data-nurse-field'] = this.fieldKey;

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
                if (!found) {
                    const foundTemp = doc.getElementById(this.tmpId);
                    if (foundTemp) {
                        this.el = found;
                        this.applyDomKeys(foundTemp, this.tmpId);
                    }
                }
            }
        }
    }

    /**
     * Optionally bind a real DOM element to this FormItem so subsequent context/key rebuilds
     * can update the actual element (id/name/data-attrs) in-place.
     */
    bindElement(el) {
        this.el = el || null;
        this.applyDomKeys();
        return this;
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

        // keep checkbox/radio checked state consistent if applicable
        if (this.type === 'checkbox' || this.type === 'radio') {
            if (this.attributes && this.attributes.checked) el.checked = true;
        }
    }

    setContext({ clientId, nurseId, index }) {
        this.clientId = clientId;
        this.nurseId = nurseId;
        this.index = index;
        this.rebuildDomKeys();
        this.applyDomKeys();
    }

    renderAttributes({ includeId = true, includeName = true } = {}) {
        const attrs = { ...this.attributes };

        if (includeId && this.id) attrs.id = this.id;
        if (includeName && this.name) attrs.name = this.name;

        const parts = [];
        for (const [key, val] of Object.entries(attrs)) {
            if (val === false || val === null || val === undefined) continue;
            if (val === true) {
                parts.push(key);
            } else {
                parts.push(`${key}="${escapeHtml(String(val))}"`);
            }
        }

        return parts.length ? ' ' + parts.join(' ') : '';
    }

    renderInput(disabled = false) {
        if (disabled) {
            this.attributes.disabled = 'disabled';
        }
        const attrs = this.renderAttributes();
        const valueAttr =
            this.type === 'radio' ||
            this.type === 'checkbox' ||
            this.type === 'hidden' ||
            this.type === 'number' ||
            this.type === 'text'
                ? ` value="${escapeHtml(this.value ?? '')}"`
                : '';
        return `<input type="${escapeHtml(this.type)}"${attrs}${valueAttr}/>`;
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

    renderLink(innerHtml) {
        const attrs = this.renderAttributes({includeName: false});
        const href = this.attributes.href || this.value || 'javascript:void(0);';
        return `<a href="${escapeHtml(href)}"${attrs}>${innerHtml}</a>`;
    }

    renderOutput(tag = 'span') {
        const attrs = this.renderAttributes({includeName: false});
        return `<${tag}${attrs}>${escapeHtml(this.value ?? '')}</${tag}>`;
    }

    /**
     * Sync model field from DOM element (for event-driven updates)
     */
    syncFromElement(el) {
        const tag = el.tagName.toLowerCase();

        if (this.type === 'radio' || this.type === 'checkbox') {
            this.attributes.checked = el.checked || undefined;
            this.value = el.value;
        } else if (this.type === 'select' || tag === 'select') {
            this.value = el.value;
            this.options.forEach(o => {
                o.selected = (String(o.value) === String(el.value));
            });
        } else {
            this.value = el.value;
        }
    }

    setDomValue(value) {
        document.getElementById(this.id).value = value;
    }

    disable() {
        this.attributes.disabled = 'disabled'
        document.getElementById(this.id).disabled = 'disabled'
    }
}
