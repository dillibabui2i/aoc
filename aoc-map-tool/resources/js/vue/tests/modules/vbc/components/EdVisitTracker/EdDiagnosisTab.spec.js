import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EdDiagnosisTab from '@/modules/vbc/components/EdVisitTracker/EdDiagnosisTab.vue'
import EdDiagnosisDetailsModal from '@/modules/vbc/components/EdVisitTracker/EdDiagnosisDetailsModal.vue'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

const DIAGNOSIS = {
    id: 7,
    icd_10_code: 'R94.5',
    description: 'Transaminitis',
    system: 'ICD-10',
    system_label: 'ICD-10',
    rank: 1,
    rank_label: '1 - Primary',
    clinical_status: 'active',
    clinical_status_label: 'Active',
    verification_status: 'suspected',
    verification_status_label: 'Suspected',
    created_at: '3/30/2026 12:01 AM',
    author: 'Melinda James',
    notes: 'Some notes',
}
const tick = () => new Promise((r) => window.setTimeout(r))

function mountTab(status, wasMade, diagnoses) {
    const pinia = createTestingPinia({
        stubActions: true,
        createSpy: vi.fn,
        initialState: {
            edVisitTracker: {
                tracker: {
                    tracker_id: 9001,
                    status,
                    was_diagnosis_made: wasMade,
                    diagnoses,
                },
            },
        },
    })
    const wrapper = mount(EdDiagnosisTab, {
        attachTo: document.body,
        global: { plugins: [pinia] },
    })
    return { wrapper, store: useEdVisitTracker() }
}

describe('EdDiagnosisTab', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    it('In Progress: toggle enabled; ON shows table and Add Diagnosis', () => {
        const { wrapper } = mountTab('In Progress', true, [DIAGNOSIS])
        expect(wrapper.text()).toContain('Was the Diagnosis Made?')
        expect(wrapper.find('input[type="checkbox"]').element.disabled).toBe(false)
        expect(wrapper.find('table').exists()).toBe(true)
        expect(wrapper.text()).toContain('R94.5')
        expect(wrapper.text()).toContain('Add Diagnosis')
    })

    it('toggle OFF with no records hides table and Add Diagnosis', () => {
        const { wrapper } = mountTab('In Progress', false, [])
        expect(wrapper.find('table').exists()).toBe(false)
        expect(wrapper.text()).not.toContain('Add Diagnosis')
    })

    it('toggle OFF with existing records keeps the table but hides Add Diagnosis', () => {
        const { wrapper } = mountTab('In Progress', false, [DIAGNOSIS])
        expect(wrapper.find('table').exists()).toBe(true)
        expect(wrapper.text()).not.toContain('Add Diagnosis')
    })

    it('toggling calls store.setWasDiagnosisMade', async () => {
        const { wrapper, store } = mountTab('In Progress', false, [])
        await wrapper.find('input[type="checkbox"]').setValue(true)
        expect(store.setWasDiagnosisMade).toHaveBeenCalledWith(true)
    })

    it('Add / calendar icon call the store modal actions', async () => {
        const { wrapper, store } = mountTab('In Progress', true, [DIAGNOSIS])
        await wrapper.find('.diagnosis-tab__add').trigger('click')
        expect(store.openAddDiagnosis).toHaveBeenCalledOnce()
        await wrapper.find('.fa-calendar').trigger('click')
        expect(store.openEditDiagnosis).toHaveBeenCalledWith(DIAGNOSIS)
    })

    it('not In Progress: records visible, toggle disabled, no Add', () => {
        const { wrapper } = mountTab('Completed', true, [DIAGNOSIS])
        expect(wrapper.text()).toContain('R94.5')
        expect(wrapper.find('input[type="checkbox"]').element.disabled).toBe(true)
        expect(wrapper.text()).not.toContain('Add Diagnosis')
    })
})

describe('EdDiagnosisDetailsModal', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    function mountModal(status) {
        const pinia = createTestingPinia({
            stubActions: false,
            createSpy: vi.fn,
            initialState: {
                edVisitTracker: { tracker: { tracker_id: 9002, status } },
            },
        })
        mount(EdDiagnosisDetailsModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        const store = useEdVisitTracker()
        store.submitDiagnosis = vi.fn()
        return store
    }

    it('opens blank on add, prefilled on edit (with Creation Date/Author)', async () => {
        const store = mountModal('In Progress')
        store.openAddDiagnosis()
        await tick()
        await tick()
        expect(document.body.querySelectorAll('select')[0].value).toBe('')
        expect(document.body.textContent).not.toContain('Creation Date')
        store.closeDiagnosisModal()
        await tick()
        store.openEditDiagnosis(DIAGNOSIS)
        await tick()
        await tick()
        expect(document.body.textContent).toContain('Creation Date')
        expect(document.body.querySelector('textarea').value).toBe('Some notes')
    })

    it('blocks submit when required fields are missing', async () => {
        const store = mountModal('In Progress')
        store.openAddDiagnosis()
        await tick()
        Array.from(document.body.querySelectorAll('button'))
            .find((b) => b.textContent.includes('Submit'))
            .click()
        await tick()
        expect(store.submitDiagnosis).not.toHaveBeenCalled()
        expect(document.body.textContent).toContain('Rank is required.')
    })

    it('read-only when not In Progress: no form, no Submit', async () => {
        const store = mountModal('Completed')
        store.openEditDiagnosis(DIAGNOSIS)
        await tick()
        expect(
            document.body.querySelector('.diagnosis-details__readonly'),
        ).not.toBeNull()
        expect(document.body.querySelector('.diagnosis-details__form')).toBeNull()
        expect(
            Array.from(document.body.querySelectorAll('button')).some((b) =>
                b.textContent.includes('Submit'),
            ),
        ).toBe(false)
    })
})
