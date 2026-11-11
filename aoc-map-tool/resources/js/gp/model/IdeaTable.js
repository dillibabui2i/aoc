// IdeaTable.js
import { GpCalcInformationPopup } from './GpCalcInformationPopup.js';

function toNumber(v) {
    if (v == null) return 0;
    if (typeof v === 'number') return v;
    if (typeof v === 'string') {
        const n = parseFloat(v.replace(/,/g, '').trim());
        return Number.isFinite(n) ? n : 0;
    }
    return 0;
}

function money(n) {
    try {
        return toNumber(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    } catch {
        return String(n);
    }
}

export class IdeaTable {
    constructor({
                    mountEl,
                    popup = new GpCalcInformationPopup(),
                    enableInfoButton = true,
                }) {
        if (!mountEl) throw new Error('IdeaTable requires mountEl');

        this.mountEl = mountEl;
        this.popup = popup;
        this.enableInfoButton = !!enableInfoButton;

        this.state = {
            normalized: null,
            customRate: '',
        };

        this.refs = {};
        this._bound = false;
        this._renderSkeleton();
        this._bind();
    }

    setVisible() {
        this.mountEl.setAttribute('style',  'display: block;');
    }

    /**
     * If the original mount element was removed (e.g. rows container cleared) and later recreated,
     * call this with the new element to remount IdeaTable.
     */
    remount(mountEl) {
        if (!mountEl) return;
        this.mountEl = mountEl;
        this.refs = {};
        this._bound = false;
        this._renderSkeleton();
        this._bind();

        // Restore last state UI
        if (this.state.customRate && this.refs.customRate) {
            this.refs.customRate.value = this.state.customRate;
        }
        if (this.state.normalized) {
            this._renderFromTotals(this.state.normalized.totals);
            this._renderIdealTable(this.state.normalized.idealTable);
            if (this.state.customRate) this._recalcCustomRate();
        }
    }

    /**
     * Ensures internal refs are present and the table is rendered.
     * Useful when mount element survived but innerHTML was wiped.
     */
    ensureRendered() {
        try {
            if (!this.mountEl || (typeof document !== 'undefined' && !document.contains(this.mountEl))) {
                return false;
            }
        } catch {
            return false;
        }

        // If skeleton was wiped or refs lost, rebuild.
        if (!this.refs || !this.refs.customRate || !this.mountEl.querySelector('.gp-idea-table')) {
            this.refs = {};
            this._bound = false;
            this._renderSkeleton();
            this._bind();
            if (this.state.customRate && this.refs.customRate) {
                this.refs.customRate.value = this.state.customRate;
            }
        }

        return true;
    }

    update(calcResult) {
        // If our mount survived but got cleared, rebuild skeleton/refs.
        if (!this.ensureRendered()) return;

        const normalized = this.normalizeGpCalcResult(calcResult);
        this.state.normalized = normalized;

        this._renderFromTotals(normalized.totals);
        this._renderIdealTable(normalized.idealTable);

        // keep computed row in sync if user already typed
        if (this.state.customRate) {
            this._recalcCustomRate();
        } else {
            this._resetComputedCells();
        }
    }

    normalizeGpCalcResult(result) {
        // expects API: { status: true, data: { data: { totals, ideal_table, client_data }, nurses, ... } }
        // returns: { totals, idealTable, clientData, nurses, raw }
        const raw = result ?? null;

        const totals =
            result?.data?.data?.totals ??
            result?.data?.totals ??
            result?.totals ??
            null;

        const idealTable =
            result?.data?.data?.ideal_table ??
            result?.data?.ideal_table ??
            result?.ideal_table ??
            [];

        const clientData =
            result?.data?.data?.client_data ??
            result?.data?.client_data ??
            result?.client_data ??
            {};

        const nurses =
            result?.data?.nurses ??
            result?.nurses ??
            [];

        return { totals, idealTable, clientData, nurses, raw };
    }

    destroy() {
        this.mountEl.innerHTML = '';
        this.refs = {};
        this.state = { normalized: null, customRate: '' };
    }

    openInfo() {
        this.popup.open(this.state.normalized?.raw ?? null);
    }

    // ---------------- private ----------------

    _renderSkeleton() {
        this.mountEl.innerHTML = '';
        const root = document.createElement('div');
        root.className = 'gp-idea-table';

        root.appendChild(this._renderSummary());
        root.appendChild(this._renderIdeal());
        this.mountEl.appendChild(root);

        // If user already typed a custom rate earlier, restore it into the input
        if (this.state.customRate && this.refs.customRate) {
            this.refs.customRate.value = this.state.customRate;
        }
    }

    _renderSummary() {
        const wrap = document.createElement('div');
        wrap.className = 'd-flex gap-2 align-items-start';

        const table = document.createElement('table');
        table.className = 'table border';
        table.innerHTML = `
      <thead>
        <tr>
          <th>Weekly Pay (Pre-Tax)</th>
          <th>Home GP</th>
          ${this.enableInfoButton ? '<th style="width:1%"></th>' : ''}
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>$<span data-ref="weeklyPayPreTax">0.00</span></td>
          <td><span data-ref="homeGp">0</span>%</td>
          ${this.enableInfoButton ? '<td><button type="button" class="btn btn-sm btn-outline-secondary" data-action="info">Info</button></td>' : ''}
        </tr>
      </tbody>
    `;
        wrap.appendChild(table);

        this.refs.weeklyPayPreTax = table.querySelector('[data-ref="weeklyPayPreTax"]');
        this.refs.homeGp = table.querySelector('[data-ref="homeGp"]');
        this.refs.infoBtn = this.enableInfoButton ? table.querySelector('[data-action="info"]') : null;

        return wrap;
    }

    _renderIdeal() {
        const wrap = document.createElement('div');

        const table = document.createElement('table');
        table.className = 'table border';
        table.innerHTML = `
      <thead>
        <tr>
          <th width="20%">GP</th>
          <th width="30%">Total Profitability</th>
          <th width="25%">Total Paid</th>
          <th width="25%">Base Rate</th>
        </tr>
      </thead>
      <tbody data-ref="idealBody"></tbody>
      <tfoot>
        <tr>
          <td><input type="text" class="form-control" data-ref="customRate" placeholder="Custom GP %" /></td>
          <td data-ref="totalProfitabilityCell">0</td>
          <td data-ref="totalPaidCell">0</td>
          <td data-ref="baseRateCell">0</td>
        </tr>
      </tfoot>
    `;
        wrap.appendChild(table);

        this.refs.idealBody = table.querySelector('[data-ref="idealBody"]');
        this.refs.customRate = table.querySelector('[data-ref="customRate"]');
        this.refs.totalProfitabilityCell = table.querySelector('[data-ref="totalProfitabilityCell"]');
        this.refs.totalPaidCell = table.querySelector('[data-ref="totalPaidCell"]');
        this.refs.baseRateCell = table.querySelector('[data-ref="baseRateCell"]');

        return wrap;
    }

    _bind() {
        if (this._bound) return;
        this._bound = true;

        this.refs.infoBtn?.addEventListener('click', () => this.openInfo());

        this.refs.customRate?.addEventListener('input', (e) => {
            this.state.customRate = e.target.value;
            this._recalcCustomRate();
        });
    }

    _renderFromTotals(totals) {
        if (!totals) return;

        // prefer the explicit pre-tax if you have it
        const weekly = totals.final_weekly_pay_pre_tax ?? totals.all_final_pay ?? '0.00';
        const gp = totals.final_gp ?? '0';

        if (this.refs.weeklyPayPreTax) this.refs.weeklyPayPreTax.textContent = weekly;
        if (this.refs.homeGp) this.refs.homeGp.textContent = gp;
    }

    _renderIdealTable(rows) {
        const body = this.refs.idealBody;
        if (!body) return;

        body.innerHTML = '';

        (rows || []).forEach((r) => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
        <td>${r.ideal_gp ?? ''}%</td>
        <td>$${r.ideal_profitability ?? ''}</td>
        <td>$${r.ideal_paid ?? ''}</td>
        <td>$${r.ideal_base_rate ?? ''}</td>
      `;
            body.appendChild(tr);
        });
    }

    _resetComputedCells() {
        if (this.refs.totalProfitabilityCell) this.refs.totalProfitabilityCell.textContent = 0;
        if (this.refs.totalPaidCell) this.refs.totalPaidCell.textContent = 0;
        if (this.refs.baseRateCell) this.refs.baseRateCell.textContent = 0;
    }

    _recalcCustomRate() {
        const normalized = this.state.normalized;
        const totals = normalized?.totals;
        if (!totals) return;

        const customRate = toNumber(this.state.customRate);
        if (!customRate) {
            this._resetComputedCells();
            return;
        }

        const totalReimbursement = toNumber(totals.final_reimbursement);
        const insurance = toNumber(totals.final_insurance);

        const totalOTHours = toNumber(totals.overtime_final_hours);
        const regularHours = toNumber(totals.regular_final_hours);
        const totalHours = toNumber(totals.all_final_hours);

        const totalProfitability = (totalReimbursement * customRate) / 100;
        const totalPaid = totalReimbursement - totalProfitability;
        const weeklyPay = (totalPaid - insurance) / 1.1;

        let baseRate = 0;
        if (totalOTHours > 0) {
            const calculatedHours = regularHours + (totalOTHours * 1.5);
            baseRate = calculatedHours ? weeklyPay / calculatedHours : 0;
        } else {
            baseRate = totalHours ? weeklyPay / totalHours : 0;
        }

        this.refs.totalProfitabilityCell.textContent = '$' + money(totalProfitability);
        this.refs.totalPaidCell.textContent = '$' + money(totalPaid);
        this.refs.baseRateCell.textContent = '$' + money(baseRate);
    }
}
