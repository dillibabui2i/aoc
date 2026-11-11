import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EdEducationTab from '@/modules/vbc/components/EdVisitTracker/EdEducationTab.vue'
import EdEducationDetailsModal from '@/modules/vbc/components/EdVisitTracker/EdEducationDetailsModal.vue'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

vi.mock('@/modules/vbc/services/edVisitTracker.service.js', () => ({
    EdVisitTrackerService: {
        getTrainingTypes: vi.fn().mockResolvedValue([
            { value: 1, label: 'Head Injury' },
            { value: 2, label: 'Seasonal Illness Guide' },
        ]),
        getTrainingSources: vi
            .fn()
            .mockResolvedValue([{ value: 3, label: 'AOC Clinical Excellence' }]),
    },
}))

const RECORD = {
    id: 11,
    training_type_id: 1,
    training_type_name: 'Head Injury',
    training_source_id: 3,
    training_source_name: 'AOC Clinical Excellence',
    notes: 'Some notes',
    is_completed: true,
}
const tick = () => new Promise((r) => window.setTimeout(r))

function mountTab(status, education, stubActions = true) {
    const { required, records } = education
    const pinia = createTestingPinia({
        stubActions,
        createSpy: vi.fn,
        initialState: {
            edVisitTracker: { tracker: {
                    tracker_id: 9001,
                    status,
                    staff_family_training_required: required,
                    education_records: records,
                },
            },
        },
    })
    const wrapper = mount(EdEducationTab, {
        attachTo: document.body,
        global: { plugins: [pinia] },
    })
    return { wrapper, store: useEdVisitTracker() }
}

describe('EdEducationTab', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    it('In Progress: toggle enabled; ON shows table and Add Education', () => {
        const { wrapper } = mountTab('In Progress', {
            required: true,
            records: [RECORD],
        })
        expect(wrapper.text()).toContain(
            'Staff / Family Training/Education required?',
        )
        expect(wrapper.find('input[type="checkbox"]').element.disabled).toBe(
            false,
        )
        expect(wrapper.find('table').exists()).toBe(true)
        expect(wrapper.text()).toContain('Add Education')
        expect(wrapper.find('.fa-calendar').exists()).toBe(true)
    })

    it('toggle OFF hides table and Add Education', () => {
        const { wrapper } = mountTab('In Progress', {
            required: false,
            records: [],
        })
        expect(wrapper.find('table').exists()).toBe(false)
        expect(wrapper.text()).not.toContain('Add Education')
    })

    it('toggling calls store.setStaffFamilyTrainingRequired', async () => {
        const { wrapper, store } = mountTab('In Progress', {
            required: false,
            records: [],
        })
        await wrapper.find('input[type="checkbox"]').setValue(true)
        expect(store.setStaffFamilyTrainingRequired).toHaveBeenCalledWith(true)
    })

    it('Add / calendar icon open the modal actions', async () => {
        const { wrapper, store } = mountTab('In Progress', {
            required: true,
            records: [RECORD],
        })
        await wrapper.find('.education-tab__add').trigger('click')
        expect(store.openAddEducationRecord).toHaveBeenCalledOnce()
        await wrapper.find('.fa-calendar').trigger('click')
        expect(store.openEditEducationRecord).toHaveBeenCalledWith(RECORD)
    })

    it('not In Progress: records visible, toggle disabled, no Add, calendar opens read-only view', async () => {
        const { wrapper, store } = mountTab('Discharged', {
            required: true,
            records: [RECORD],
        })
        expect(wrapper.text()).toContain('Head Injury')
        expect(wrapper.find('input[type="checkbox"]').element.disabled).toBe(
            true,
        )
        expect(wrapper.text()).not.toContain('Add Education')
        // The calendar icon stays and opens the record read-only (the modal
        // itself decides read-only from the tracker status).
        await wrapper.find('.fa-calendar').trigger('click')
        expect(store.openEditEducationRecord).toHaveBeenCalledWith(RECORD)
    })
})

describe('EdEducationDetailsModal', () => {
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
        mount(EdEducationDetailsModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        return useEdVisitTracker()
    }

    it('opens blank on add, prefilled on edit', async () => {
        const store = mountModal('In Progress')
        store.openAddEducationRecord()
        await tick()
        await tick()
        let selects = document.body.querySelectorAll('select')
        expect(selects[0].value).toBe('')
        expect(selects[1].value).toBe('')
        expect(
            document.body.querySelector('input[type="checkbox"]').checked,
        ).toBe(false)
        store.closeEducationModal()
        await tick()
        store.openEditEducationRecord(RECORD)
        await tick()
        await tick()
        selects = document.body.querySelectorAll('select')
        expect(selects[0].value).toBe('1')
        expect(selects[1].value).toBe('3')
    })

    it('loads Training Type / Education Source options from the backend', async () => {
        const store = mountModal('In Progress')
        store.openAddEducationRecord()
        await tick()
        await tick()
        const labels = Array.from(document.body.querySelectorAll('option')).map(
            (o) => o.textContent.trim(),
        )
        expect(labels).toContain('Seasonal Illness Guide')
        expect(labels).toContain('AOC Clinical Excellence')
    })

    it('blocks submit and flags all missing required fields', async () => {
        const store = mountModal('In Progress')
        store.openAddEducationRecord()
        await tick()
        Array.from(document.body.querySelectorAll('button'))
            .find((b) => b.textContent.includes('Submit'))
            .click()
        await tick()
        expect(store.submitEducationRecord).not.toHaveBeenCalled()
        const count = document.body.textContent.split(
            'This field is required.',
        ).length - 1
        expect(count).toBe(3)
    })

    it('read-only when not In Progress: no form, no Submit', async () => {
        const store = mountModal('Completed')
        store.openEditEducationRecord(RECORD)
        await tick()
        expect(
            document.body.querySelector('.education-details__readonly'),
        ).not.toBeNull()
        expect(document.body.querySelector('.education-details__form')).toBeNull()
        expect(
            Array.from(document.body.querySelectorAll('button')).some((b) =>
                b.textContent.includes('Submit'),
            ),
        ).toBe(false)
    })
})
