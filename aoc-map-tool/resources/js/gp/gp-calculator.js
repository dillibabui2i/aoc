import GpCalculator from './model/GpCalculator.js';
import GpPersistenceService from './service/GpPersistenceService.js';


const GpCalculatorConfig = window.GpCalculatorConfig;

// Whitespace-only Clinician name must be treated the same as an empty required field
// (PD-4460: a lone space passed HTML5 `required` but desynced GP% between the
// nurse-level and totals-level insurance calculations on the backend).
function findBlankClinicianField(gpCalc) {
    for (const link of gpCalc?.links ?? []) {
        const nameItem = link.nurse?.nurseName;
        if (nameItem && nameItem.value.trim() === '') {
            return nameItem;
        }
    }
    return null;
}

function blockIfBlankClinician(gpCalc) {
    const blankField = findBlankClinicianField(gpCalc);
    if (!blankField) return false;

    const el = document.getElementById(blankField.id);
    if (el) {
        // Same visual treatment jQuery Validate applies to other required
        // fields on this form (see input.error rule in resources/css/myaccount.css).
        el.classList.add('error');
        el.focus();
    }
    return true;
}

function blockIfBlankBranch(gpCalc) {
    const blankBranch = (gpCalc?.clientCollection?.clients ?? []).find(c => c.showBranch && !c.branch?.value);
    if (!blankBranch) return false;

    const el = document.getElementById(blankBranch.branch.id);
    if (el) {
        el.classList.add('error');
        el.parentElement?.querySelector('.branch-error')?.classList.remove('hide');
        el.focus();
    }
    return true;
}

// Clear the manual error highlight once the user types a non-blank name.
document.addEventListener('input', (e) => {
    const el = e.target;
    if (el?.classList?.contains('nurseName') && el.classList.contains('error') && el.value.trim() !== '') {
        el.classList.remove('error');
    }
});

document.addEventListener('DOMContentLoaded', () => {
    const gpCalc = new GpCalculator({
        $: GpCalculatorConfig.$,
        csrfToken: document.querySelector('meta[name="csrf-token"]').content,
        calculationType: GpCalculator.CALC_TYPES.singleClientSingleNurse,
        payers: GpCalculatorConfig.payers,
        branches: GpCalculatorConfig.branches,
        disciplines: GpCalculatorConfig.disciplines,
        useServiceLabel: GpCalculatorConfig.useServiceLabel,
        renderEscalationGuideTable: GpCalculatorConfig.renderEscalationGuideTable,
        persistenceService: new GpPersistenceService(GpCalculatorConfig.routes, { csrfToken : document.querySelector('meta[name="csrf-token"]').content}),
        rowsBlock: 'clientSelectionContainer',
        popupBlock: GpCalculatorConfig.popupBlock,
        ideaTable: GpCalculatorConfig.ideaTable,
        escalationGuideTable: GpCalculatorConfig.escalationGuideTable,
        modalId: GpCalculatorConfig.modalId,
        renderPermOnly: GpCalculatorConfig.renderPermOnly,
        useNewWeekLogic: GpCalculatorConfig.useNewWeekLogic
    });
    // Restore or initialize first row (await restore because it is async)
    (async () => {
        if (GpCalculatorConfig.gpId) {
            await gpCalc.restoreGp(GpCalculatorConfig.gpId, { hydrate: true });
            gpCalc.setId(GpCalculatorConfig.gpId)
        } else {
            gpCalc.createNewRecord();
        }
    })();

    // Add Clinician (delegated because the button can be rendered after restore)
    document.addEventListener('click', (e) => {
        const selector = GpCalculatorConfig.actions?.addClinician;
        if (!selector) return;

        const btn = e.target.closest(selector);
        if (!btn) return;

        e.preventDefault();
        if ($('#nurseGPForm').valid()) {
            const clientBtn = document.getElementById(GpCalculatorConfig.actions.addClient.replace('#', ''));
            if (clientBtn) clientBtn.disabled = true;

            gpCalc.addClinician();
        }
    });

    // Save to List (button is rendered dynamically inside Nurse menu)
    document.addEventListener('click', (e) => {
        const btn = e.target.closest(GpCalculatorConfig.actions.save);
        if (!btn) return;

        e.preventDefault();
        if ($('#nurseGPForm').valid() && !blockIfBlankClinician(gpCalc)) {
            (async () => {
                const res = await gpCalc.saveGp({id: GpCalculatorConfig.gpId ?? null});
                if (res?.status) {
                    // optional: expose last saved id for debugging
                    window.GpLastSavedId = res.id;
                }
            })();
        }
    });

    // Calculate (server-side) and merge results back into the model + UI
    document.addEventListener('click', async (e) => {
        const btn = e.target?.closest?.(GpCalculatorConfig.actions.calculate);
        if (!btn) return;
        e.preventDefault();
        if (blockIfBlankBranch(gpCalc)) {
            $('#nurseGPForm').valid();
            return;
        }
        if ($('#nurseGPForm').valid() && !blockIfBlankClinician(gpCalc)) {
            await gpCalc.calculate()
        }
    });


    // Load whole home list and render all row fetched
    document.addEventListener('click', async (e) => {
        const btn = e.target?.closest?.(GpCalculatorConfig.actions.wholeHome);
        if (!btn) return;

        const clientBtn = document.getElementById(GpCalculatorConfig.actions.addClient.replace('#', ''));
        const wholeHomeBtn = document.getElementById(GpCalculatorConfig.actions.wholeHome.replace('#', ''));

        if ($('#nurseGPForm').valid()) {
            if (clientBtn) {
                clientBtn.disabled = 'disabled';
            }

            if (wholeHomeBtn) {
                wholeHomeBtn.disabled = 'disabled';
            }
            e.preventDefault();
            await gpCalc.requestWholeHome()
        }
    });

    // Load whole home list and render all row fetched
    document.addEventListener('click', async (e) => {
        const btn = e.target;
        const clinicianBtn = document.getElementById(GpCalculatorConfig.actions.addClinician.replace('#', ''));
        const wholeHomeBtn = document.getElementById(GpCalculatorConfig.actions.wholeHome.replace('#', ''));

        if (btn?.id !== 'addClientBtn') return;

        if ($('#nurseGPForm').valid()) {
            if (wholeHomeBtn) {
                wholeHomeBtn.disabled = 'disabled';
            }

            if (clinicianBtn) {
                clinicianBtn.disabled = 'disabled'
            }

            e.preventDefault();
            $('#nurseGPForm').valid()
            await gpCalc.addClient()
        }
    });


    document.addEventListener('click', async (e) => {
        const btn = e.target?.closest?.(GpCalculatorConfig.actions.clear);
        if (!btn) return;
        e.preventDefault();
        await gpCalc.clear()
    });

    $("#nurseGPForm").validate({
        onfocusout: false,
        invalidHandler: function(form, validator) {
            var errors = validator.numberOfInvalids();
            if (errors) {
                validator.errorList[0].element.focus();
            }
        },
        errorPlacement: function(error, element) {}
    });


});
