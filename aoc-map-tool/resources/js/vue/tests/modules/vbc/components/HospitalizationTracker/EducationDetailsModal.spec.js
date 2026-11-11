import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EducationDetailsModal from '@/modules/vbc/components/HospitalizationTracker/EducationDetailsModal.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// The modal lazily loads its Training Type / Education Source options
// through the service on first open — mocked so no real HTTP happens.
vi.mock('@/modules/vbc/services/hospitalizationTracker.service.js', () => ({
    HospitalizationTrackerService: {
        getTrainingTypes: vi.fn().mockResolvedValue([
            { value: 1, label: 'Fall Prevention' },
            { value: 2, label: 'Head Injury' },
        ]),
        getTrainingSources: vi
            .fn()
            .mockResolvedValue([
                { value: 1, label: 'AOC Clinical Excellence' },
            ]),
    },
}))

// UiModal renders via <teleport to="body">, so its content lives outside
// `wrapper.element` — query `document.body` for it, per the existing
// ContactDetailsModal.spec.js convention.

const EDUCATION_RECORD = {
    id: 1,
    training_type_name: 'Head Injury',
    training_type_id: 2,
    training_source_name: 'AOC Clinical Excellence',
    training_source_id: 1,
    notes: 'Some notes',
    is_completed: true,
}

describe('EducationDetailsModal', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    // The form is (re)filled by a watcher on `educationModalOpen`, so the
    // tracker must exist via `initialState` before the modal is opened via
    // the (real, synchronous) store action. Only the HTTP-backed submit is
    // stubbed.
    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            stubActions: ['submitEducationRecord'],
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        mount(EducationDetailsModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        return useHospitalizationTracker()
    }

    it('opens blank when adding a new record', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddEducationRecord()
        await new Promise((r) => window.setTimeout(r))

        const selects = document.body.querySelectorAll('select')
        expect(selects[0].value).toBe('')
        expect(selects[1].value).toBe('')
        expect(
            document.body.querySelector('input[type="checkbox"]').checked,
        ).toBe(false)
    })

    it('opens prefilled with the existing values when editing', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openEditEducationRecord(EDUCATION_RECORD)
        await new Promise((r) => window.setTimeout(r))

        const selects = document.body.querySelectorAll('select')
        expect(selects[0].value).toBe(String(EDUCATION_RECORD.training_type_id))
        expect(selects[1].value).toBe(
            String(EDUCATION_RECORD.training_source_id),
        )
    })

    it('prevents submission and marks required fields when incomplete', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddEducationRecord()
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitEducationRecord).not.toHaveBeenCalled()
        expect(
            document.body.textContent.includes('Training Type is required.'),
        ).toBe(true)
        expect(
            document.body.textContent.includes('Education Source is required.'),
        ).toBe(true)
    })

    it('submits the entered record when valid', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddEducationRecord()
        await new Promise((r) => window.setTimeout(r))

        const selects = document.body.querySelectorAll('select')
        const setSelectValue = (el, value) => {
            el.value = value
            el.dispatchEvent(new window.Event('change'))
        }
        setSelectValue(selects[0], '2')
        setSelectValue(selects[1], '1')
        const checkbox = document.body.querySelector('input[type="checkbox"]')
        checkbox.checked = true
        checkbox.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitEducationRecord).toHaveBeenCalledWith(
            expect.objectContaining({
                training_type_id: '2',
                training_source_id: '1',
                is_completed: true,
            }),
        )
    })

    it.each([
        'Discharged',
        'Ready for Review',
        'Ready for Next Steps',
        'Completed',
        'Closed',
    ])(
        'renders read-only with a disabled Submit action when the tracker is %s',
        async (status) => {
            const store = mountComponent({ tracker_id: 1, status })
            store.openEditEducationRecord(EDUCATION_RECORD)
            await new Promise((r) => window.setTimeout(r))

            expect(
                document.body.querySelector('.education-details__readonly'),
            ).not.toBeNull()
            expect(
                document.body.querySelector('.education-details__form'),
            ).toBeNull()
            expect(document.body.textContent).toContain('Head Injury')
            const buttons = Array.from(document.body.querySelectorAll('button'))
            const submit = buttons.find((b) => b.textContent.includes('Submit'))
            expect(submit.disabled).toBe(true)
        },
    )
})
