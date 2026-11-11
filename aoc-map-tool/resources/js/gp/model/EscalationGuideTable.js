function escapeHtml(value) {
    if (value == null) return '';
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

export class EscalationGuideTable {
    constructor({ mountEl }) {
        if (!mountEl) throw new Error('EscalationGuideTable requires mountEl');

        this.mountEl = mountEl;
        this.refs = {};
        this.state = {
            guide: null,
        };
        this._renderSkeleton();
    }

    setVisible() {
        this.mountEl.setAttribute('style', 'display: block;');
    }

    remount(mountEl) {
        if (!mountEl) return;
        this.mountEl = mountEl;
        this.refs = {};
        this._renderSkeleton();
        if (this.state.guide) {
            this._renderGuide(this.state.guide);
        }
    }

    ensureRendered() {
        try {
            if (!this.mountEl || (typeof document !== 'undefined' && !document.contains(this.mountEl))) {
                return false;
            }
        } catch {
            return false;
        }

        if (!this.refs || !this.refs.grossMarginHead || !this.mountEl.querySelector('.gp-escalation-guide-table')) {
            this.refs = {};
            this._renderSkeleton();
        }

        return true;
    }

    update(calcResult) {
        if (!this.ensureRendered()) return;

        const guide =
            calcResult?.data?.data?.gross_margin_guide ??
            calcResult?.data?.gross_margin_guide ??
            calcResult?.gross_margin_guide ??
            null;

        this.state.guide = guide;
        this._renderGuide(guide);
    }

    destroy() {
        this.mountEl.innerHTML = '';
        this.refs = {};
        this.state = { guide: null };
    }

    _renderSkeleton() {
        this.mountEl.innerHTML = '';

        const root = document.createElement('div');
        root.className = 'gp-escalation-guide-table mb-0';

        const header = document.createElement('div');
        header.className = 'd-flex flex-wrap align-items-center mb-1 font-weight-bold';
        header.innerHTML = `
            <span data-ref="grossMarginTitle">Gross Margin Escalation Guide</span>
            <span class="mx-2">|</span>
            <span data-ref="grossMarginLocation">Location:</span>
            <span class="mx-2">|</span>
            <span data-ref="grossMarginPayer">Payer:</span>
        `;
        root.appendChild(header);

        const table = document.createElement('table');
        table.className = 'table border mb-0';
        table.innerHTML = `
            <thead data-ref="grossMarginHead"></thead>
            <tbody data-ref="grossMarginBody"></tbody>
        `;
        root.appendChild(table);
        this.mountEl.appendChild(root);

        this.refs.grossMarginTitle = header.querySelector('[data-ref="grossMarginTitle"]');
        this.refs.grossMarginLocation = header.querySelector('[data-ref="grossMarginLocation"]');
        this.refs.grossMarginPayer = header.querySelector('[data-ref="grossMarginPayer"]');
        this.refs.grossMarginHead = table.querySelector('[data-ref="grossMarginHead"]');
        this.refs.grossMarginBody = table.querySelector('[data-ref="grossMarginBody"]');
    }

    _renderGuide(guide) {
        const head = this.refs.grossMarginHead;
        const body = this.refs.grossMarginBody;
        if (!head || !body) return;

        const normalizedGuide = guide ?? {};
        const title = normalizedGuide.title || 'Gross Margin Escalation Guide';
        const location = normalizedGuide.location || '';
        const payer = normalizedGuide.payer || '';
        const visibleColumns = Array.isArray(normalizedGuide.visible_columns) && normalizedGuide.visible_columns.length
            ? normalizedGuide.visible_columns
            : [{ key: 'recruiter_coverage_target', label: 'Recruiter & Coverage Target' }];
        const rows = Array.isArray(normalizedGuide.rows) ? normalizedGuide.rows : [];
        const isEmpty = normalizedGuide.is_empty === undefined ? rows.length === 0 : Boolean(normalizedGuide.is_empty);
        const message = normalizedGuide.message || 'No gross margin targets have been configured for this payer. Please contact your administrator.';

        if (this.refs.grossMarginTitle) this.refs.grossMarginTitle.textContent = title;
        if (this.refs.grossMarginLocation) this.refs.grossMarginLocation.textContent = `Location: ${location}`;
        if (this.refs.grossMarginPayer) this.refs.grossMarginPayer.textContent = `Payer: ${payer}`;

        head.innerHTML = '';
        body.innerHTML = '';

        const headerRow = document.createElement('tr');
        headerRow.innerHTML = `
            <th>LOB</th>
            ${visibleColumns.map((column) => `<th>${escapeHtml(column?.label ?? '')}</th>`).join('')}
        `;
        head.appendChild(headerRow);

        if (isEmpty) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = `<td colspan="${visibleColumns.length + 1}" class="text-center">${escapeHtml(message)}</td>`;
            body.appendChild(emptyRow);
            return;
        }

        rows.forEach((row) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>${escapeHtml(row?.lob ?? '')}</td>
                ${visibleColumns.map((column) => {
                    const value = row?.[column.key] ?? '';
                    return `<td>${escapeHtml(value)}${value !== '' ? '%' : ''}</td>`;
                }).join('')}
            `;
            body.appendChild(tr);
        });
    }
}
