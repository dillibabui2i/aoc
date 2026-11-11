import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EducationTab from '@/modules/vbc/components/HospitalizationTracker/EducationTab.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// Opening the real EducationDetailsModal lazily loads its Training Type /
// Education Source options through the service — mocked so no real HTTP happens.
vi.mock(
    '@/modules/vbc/services/hospitalizationTracker.service.js',
    async (importOriginal) => {
        const actual = await importOriginal()
        return {
            ...actual,
            HospitalizationTrackerService: {
                ...actual.HospitalizationTrackerService,
                getTrainingTypes: vi.fn().mockResolvedValue([]),
                getTrainingSources: vi.fn().mockResolvedValue([]),
            },
        }
    },
)

const RECORDS = [
    { id: 1, training_type_name: 'Head Injury', is_completed: true },
    {
        id: 2,
        training_type_name: 'Seasonal Illness Guide',
        is_completed: false,
    },
]

describe('EducationTab', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            stubActions: false,
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        const wrapper = mount(EducationTab, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        const store = useHospitalizationTracker()
        return { wrapper, store }
    }

    it('shows the "Staff / Family Training/Education required?" toggle', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            staff_family_training_required: false,
            education_records: [],
        })
        expect(wrapper.text()).toContain(
            'Staff / Family Training/Education required?',
        )
    })

    it('toggling calls store.setStaffFamilyTrainingRequired with the new value', async () => {
        const { wrapper, store } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            staff_family_training_required: false,
            education_records: [],
        })
        await wrapper.find('input[type="checkbox"]').setValue(true)

        expect(store.setStaffFamilyTrainingRequired).toHaveBeenCalledWith(
            true,
        )
    })

    it('toggle ON with In Progress tracker shows the table and Add Education', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            staff_family_training_required: true,
            education_records: RECORDS,
        })
        expect(wrapper.find('table').exists()).toBe(true)
        expect(wrapper.text()).toContain('Add Education')
        expect(wrapper.text()).toContain('Head Injury')
    })

    it('toggle OFF hides both the table and Add Education', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            staff_family_training_required: false,
            education_records: [],
        })
        expect(wrapper.find('table').exists()).toBe(false)
        expect(wrapper.text()).not.toContain('Add Education')
    })

    it('Add Education is hidden once the tracker leaves In Progress, even with the toggle ON', () => {
        const { wrapper } = mountComponent({
            tracker_id: 1,
            status: 'Discharged',
            staff_family_training_required: true,
            education_records: RECORDS,
        })
        expect(wrapper.find('table').exists()).toBe(true)
        expect(wrapper.text()).not.toContain('Add Education')
    })

    it('clicking Add Education calls store.openAddEducationRecord', async () => {
        const { wrapper, store } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            staff_family_training_required: true,
            education_records: [],
        })
        await wrapper.find('.education-tab__add').trigger('click')
        expect(store.openAddEducationRecord).toHaveBeenCalledOnce()
    })

    it('clicking the calendar icon calls store.openEditEducationRecord with the row', async () => {
        const { wrapper, store } = mountComponent({
            tracker_id: 1,
            status: 'In Progress',
            staff_family_training_required: true,
            education_records: RECORDS,
        })
        await wrapper.find('.fa-calendar').trigger('click')
        expect(store.openEditEducationRecord).toHaveBeenCalledWith(RECORDS[0])
    })
})
