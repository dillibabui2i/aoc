// GpCalcInformationPopup.js
export class GpCalcInformationPopup {
    constructor({
                    templateId = 'gpCalcInformationPopupTpl',
                    mountId = 'gpCalcInformationPopupMount',
                    modalId = 'getGPCalculatorInformationPopup',
                } = {}) {
        this.templateId = templateId;
        this.mountId = mountId;
        this.modalId = modalId;

        this.modalEl = null;
        this.contentEl = null;
        this.result = null;
    }

    // -------------------- public --------------------

    open(calcResult = null) {
        // Allow opening either with an explicit payload or the last stored one.
        const payload = calcResult ?? this.result;
        if (!payload) return;

        this._ensureMounted();

        const { clientDataRows, totals } = this._normalize(payload);
        this._render(clientDataRows, totals);

        // show (Bootstrap/jQuery modal)
        if (window.$) {
            window.$(this.modalEl).modal('show');
        } else {
            // fallback if bootstrap js isn't present
            this.modalEl.style.display = 'block';
            this.modalEl.classList.add('show');
        }
    }

    // store latest calc response (no UI show)
    setResult(result) {
        this.result = result ?? null;

        // If modal is already mounted & visible, re-render it with the latest payload
        if (this.modalEl && this.contentEl && this.modalEl.classList.contains('show') && this.result) {
            const { clientDataRows, totals } = this._normalize(this.result);
            this._render(clientDataRows, totals);
        }
    }

    // convenience helper
    openFromResult(result) {
        this.setResult(result);
        this.open(this.result);
    }

    // -------------------- internal --------------------

    _ensureMounted() {
        if (this.modalEl) return;

        const tpl = document.getElementById(this.templateId);
        if (!tpl) throw new Error(`Template #${this.templateId} not found`);

        const mount = document.getElementById(this.mountId) || document.body;
        mount.appendChild(tpl.content.cloneNode(true));

        this.modalEl = document.getElementById(this.modalId);
        if (!this.modalEl) throw new Error(`Modal #${this.modalId} not found after mounting template`);

        this.contentEl = this.modalEl.querySelector('[data-role="content"]');
        if (!this.contentEl) throw new Error(`Modal content container [data-role="content"] not found`);
    }

    _normalize(result) {
        // Accept either full API response or already-extracted object
        const dataRoot = result?.data?.data ?? result?.data ?? result ?? {};

        const totals = dataRoot?.totals ?? dataRoot ?? {};

        // client_data can be:
        // 1) { "224494:0": { client_name, payer }, ... }
        // 2) { "224494": { client_name, payer }, ... }
        // 3) [ { client_name, payer }, ... ] (rare)
        const rawClientData = dataRoot?.client_data ?? {};

        let clientDataRows = [];
        if (Array.isArray(rawClientData)) {
            clientDataRows = rawClientData;
        } else if (rawClientData && typeof rawClientData === 'object') {
            clientDataRows = Object.keys(rawClientData).map((k) => rawClientData[k]);
        }

        // normalize keys
        clientDataRows = clientDataRows
            .filter(Boolean)
            .map((row) => ({
                client_name: row.client_name ?? row.clientName ?? '',
                payer: row.payer ?? row.insurance ?? '',
            }));

        return { clientDataRows, totals };
    }

    _render(clientRows, totals) {
        this.contentEl.innerHTML = '';

        this.contentEl.appendChild(this._buildClientSummaryTable(clientRows, totals));
        this.contentEl.appendChild(this._buildBreakdownTable(totals));
        this._initTooltips();
    }

    _buildClientSummaryTable(clientRows, totals) {
        const table = document.createElement('table');
        table.className = 'table border';
        table.setAttribute('aria-describedby', '');

        // header: client rows
        table.appendChild(this._tr(
            this._th('Client Name', { scope: 'col' }),
            this._th('Insurance', { scope: 'col' }),
            this._th('', { scope: 'col' }),
        ));

        clientRows.forEach((r) => {
            table.appendChild(this._tr(
                this._td(r.client_name),
                this._td(r.payer),
                this._td(''),
            ));
        });

        // reimbursement block-------------------------------------
        table.appendChild(this._tr(
            this._th('Reimbursement Rate', { scope: 'col',
                attrs: {
                    'data-toggle': 'tooltip',
                    'data-placement': 'bottom',
                    title: 'F(x) = Reimbursement Rate for Payer from Kantime OR the App (Manage Payer Rates). If more than one payer then its the sum of reimbursement rates for all payers / total number of payers.'
                }
            }),
            this._th('Total Paid', { scope: 'col',
                attrs: {
                    'data-toggle': 'tooltip',
                    'data-placement': 'bottom',
                    title: 'F(x) = Total All Pay + (Total All Pay * 10%) + Insurance'
                }
            }),
            this._th('Total Reimbursement', { scope: 'col',
                attrs: {
                    'data-toggle': 'tooltip',
                    'data-placement': 'bottom',
                    title: 'F(x) = Reimbursement Rate * Total All Hours'
                }
            }),
        ));

        table.appendChild(this._tr(
            this._td(this._money(totals.final_reimbursement_rate)),
            this._td(this._money(totals.final_paid)),
            this._td(this._money(totals.final_reimbursement)),
        ));
        //------------------------------------- reimbursement block

        // profitability block -------------------------------------
        table.appendChild(this._tr(
            this._th('Total Profitability', {
                scope: 'col',
                attrs: {
                    'data-toggle': 'tooltip',
                    'data-placement': 'bottom',
                    title: 'F(x) = Total Reimbursed - Total Paid'
                }
            }),
            this._th('Insurance', {
                scope: 'col',
                attrs: {
                    'data-toggle': 'tooltip',
                    'data-placement': 'bottom',
                    title: 'F(x) = $40 for every 30 hours per nurse'
                }
            }),
            this._th('GP', {
                scope: 'col',
                attrs: {
                    'data-toggle': 'tooltip',
                    'data-placement': 'bottom',
                    title: 'F(x) = Total Profitability / Total Reimbursed * 100'
                }
            }),
        ));

        table.appendChild(this._tr(
            this._td(this._money(totals.final_profitability)),
            this._td(this._money(totals.final_insurance)),
            this._td(this._percent(totals.final_gp)),
        ));
        // ------------------------------------ profitability block

        // weekly pay row (use final_weekly_pay_pre_tax if exists, else all_final_pay)
        const weekly = totals.final_weekly_pay_pre_tax ?? totals.all_final_pay ?? '';
        const tr = document.createElement('tr');

        const th = this._th('Weekly Pay (Pre-Tax)', { scope: 'row' });
        th.classList.add('newbgcolor');
        tr.appendChild(th);

        const td = document.createElement('td');
        td.colSpan = 2;
        td.className = 'newbgcolor';
        td.style.textAlign = 'center';
        td.textContent = this._money(weekly);
        tr.appendChild(td);

        table.appendChild(tr);

        return table;
    }

    _buildBreakdownTable(totals) {
        const table = document.createElement('table');
        table.className = 'table border';
        table.setAttribute('aria-describedby', '');

        table.appendChild(this._tr(
            this._th('', { scope: 'col' }),
            this._th('Regular', { scope: 'col' }),
            this._th('OT', {
                scope: 'col',
                attrs: {
                    'data-toggle': 'tooltip',
                    'data-placement': 'bottom',
                    title: 'Overtime totals and rates may be 0 depending on hours and rules. Basic rule: hours treated as overtime when hours > 40'
                }
            }),
            this._th('All', { scope: 'col' }),
        ));

        // Blended rate (OT can be 0)
        table.appendChild(this._tr(
            this._th('Blended Rate', { scope: 'row', className: 'newbgcolor' }),
            this._td(this._money(totals.regular_blended_rate)),
            this._td(this._moneyOrZero(totals.overtime_blended_rate)),
            this._td(this._money(totals.all_blended_rate)),
        ));

        // Hours
        table.appendChild(this._tr(
            this._th('Hours', { scope: 'row', className: 'newbgcolor' }),
            this._td(this._num(totals.regular_final_hours)),
            this._td(this._num(totals.overtime_final_hours)),
            this._td(this._num(totals.all_final_hours)),
        ));

        // Pay (OT can be 0)
        table.appendChild(this._tr(
            this._th('Pay', { scope: 'row', className: 'newbgcolor' }),
            this._td(this._money(totals.regular_final_pay)),
            this._td(this._moneyOrZero(totals.overtime_final_pay)),
            this._td(this._money(totals.all_final_pay)),
        ));

        return table;
    }

    _initTooltips() {
        // Bootstrap 4 tooltips require jQuery + Popper.
        if (!window.$) return;

        const $root = window.$(this.modalEl || document);
        const $tips = $root.find('[data-toggle="tooltip"]');

        // Re-init safely after re-render
        try {
            $tips.tooltip('dispose');
        } catch (e) {
            // ignore
        }

        try {
            $tips.tooltip({ container: 'body', trigger: 'hover focus' });
        } catch (e) {
            // ignore
        }
    }

    // -------------------- tiny DOM helpers --------------------

    _tr(...cells) {
        const tr = document.createElement('tr');
        cells.forEach((c) => tr.appendChild(c));
        return tr;
    }

    _th(text, opts = {}) {
        const th = document.createElement('th');
        th.textContent = text ?? '';
        if (opts.scope) th.setAttribute('scope', opts.scope);
        if (opts.className) th.className = opts.className;

        // Allow passing through arbitrary DOM attributes (e.g., Bootstrap tooltip attrs)
        if (opts.attrs && typeof opts.attrs === 'object') {
            Object.keys(opts.attrs).forEach((k) => {
                const v = opts.attrs[k];
                if (v === undefined || v === null) return;
                th.setAttribute(k, String(v));
            });
        }

        return th;
    }

    _td(text) {
        const td = document.createElement('td');
        td.textContent = text ?? '';
        return td;
    }

    // -------------------- formatting helpers --------------------

    _num(v) {
        if (v == null) return 0;
        if (typeof v === 'number') return v;
        if (typeof v === 'string') {
            const n = parseFloat(v.replace(/,/g, '').trim());
            return Number.isFinite(n) ? n : 0;
        }
        return 0;
    }

    _money(v) {
        const n = this._num(v);
        // if backend already sends formatted string like "1,023.00", keep it
        if (typeof v === 'string' && /[0-9]/.test(v) && v.includes(',')) return `$${v}`;
        if (typeof v === 'string' && v.trim() !== '' && isNaN(parseFloat(v.replace(/,/g, '')))) return String(v);

        try {
            return `$${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
        } catch {
            return `$${n}`;
        }
    }

    _moneyOrZero(v) {
        return this._num(v) > 0 ? this._money(v) : '0';
    }

    _percent(v) {
        const s = (v == null) ? '' : String(v);
        return s.endsWith('%') ? s : `${s}%`;
    }

    _plain(v) {
        return (v == null) ? '' : String(v);
    }
}
