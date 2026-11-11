import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import DiagnosisTab from '@/modules/vbc/components/HospitalizationTracker/DiagnosisTab.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

const DIAGNOSES = [
    { id: 1, icd_10_code: 'R94.5', description: 'Transaminitis' },
    { id: 2, icd_10_code: 'R00.0', description: 'Tachycardia' },
]

describe('DiagnosisTab', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        const wrapper = mount(DiagnosisTab, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        const store = useHospitalizationTracker()
        return { wrapper, store }
    }

    it('shows the "Was the Diagnosis Made?" toggle', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            was_diagnosis_made: false,
            diagnoses: [],
        })
        expect(wrapper.text()).toContain('Was the Diagnosis Made?')
    })

    it('toggling calls store.setWasDiagnosisMade with the new value', async () => {
        const { wrapper, store } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            was_diagnosis_made: false,
            diagnoses: [],
        })
        await wrapper.find('input[type="checkbox"]').setValue(true)

        expect(store.setWasDiagnosisMade).toHaveBeenCalledWith(true)
    })

    it('toggle ON with In Progress tracker shows the table and Add Diagnosis', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            was_diagnosis_made: true,
            diagnoses: [],
        })
        expect(wrapper.find('table').exists()).toBe(true)
        expect(wrapper.text()).toContain('Add Diagnosis')
    })

    it('toggle OFF with no records hides both the table and Add Diagnosis', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            was_diagnosis_made: false,
            diagnoses: [],
        })
        expect(wrapper.find('table').exists()).toBe(false)
        expect(wrapper.text()).not.toContain('Add Diagnosis')
    })

    it('toggle OFF with existing records keeps the table visible but hides Add Diagnosis', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            was_diagnosis_made: false,
            diagnoses: DIAGNOSES,
        })
        expect(wrapper.find('table').exists()).toBe(true)
        expect(wrapper.text()).toContain('R94.5 — Transaminitis')
        expect(wrapper.text()).not.toContain('Add Diagnosis')
    })

    it('Add Diagnosis is hidden once the tracker leaves In Progress, even with the toggle ON', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Discharged',
            was_diagnosis_made: true,
            diagnoses: DIAGNOSES,
        })
        expect(wrapper.find('table').exists()).toBe(true)
        expect(wrapper.text()).not.toContain('Add Diagnosis')
    })

    it('clicking Add Diagnosis calls store.openAddDiagnosis', async () => {
        const { wrapper, store } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            was_diagnosis_made: true,
            diagnoses: [],
        })
        await wrapper.find('.diagnosis-tab__add').trigger('click')
        expect(store.openAddDiagnosis).toHaveBeenCalledOnce()
    })

    it('clicking the calendar icon calls store.openEditDiagnosis with the row', async () => {
        const { wrapper, store } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            was_diagnosis_made: true,
            diagnoses: DIAGNOSES,
        })
        await wrapper.find('.fa-calendar').trigger('click')
        expect(store.openEditDiagnosis).toHaveBeenCalledWith(DIAGNOSES[0])
    })
})
