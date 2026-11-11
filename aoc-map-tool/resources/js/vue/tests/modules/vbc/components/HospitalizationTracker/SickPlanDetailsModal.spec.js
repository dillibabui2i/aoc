import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import SickPlanDetailsModal from '@/modules/vbc/components/HospitalizationTracker/SickPlanDetailsModal.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// UiModal renders via <teleport to="body">, so its content lives outside
// `wrapper.element` — query `document.body` for it, per the existing
// ContactDetailsModal.spec.js convention.

const SICK_PLAN_ITEM = {
    id: 'sickplan-1',
    sickPlanType: 'Hydration',
    completed: true,
    completedBy: 'Emma Watson',
    notes: 'Some notes',
    creationDate: '3/29/2026 09:00 AM',
    author: 'Harrison Bush',
}

describe('SickPlanDetailsModal', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    // The form is (re)filled by a watcher on `sickPlanModalOpen`, so the
    // tracker must exist via `initialState` before the modal is opened via
    // the (real, synchronous) store action. Only the HTTP-backed submit is
    // stubbed.
    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            stubActions: ['submitSickPlanItem'],
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        mount(SickPlanDetailsModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        return useHospitalizationTracker()
    }

    it('opens prefilled with the existing values when editing', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openEditSickPlanItem(SICK_PLAN_ITEM)
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        expect(select.value).toBe('Hydration')
    })

    it('prevents submission and marks Sick Plan Type when missing', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openEditSickPlanItem(SICK_PLAN_ITEM)
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = ''
        select.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitSickPlanItem).not.toHaveBeenCalled()
        expect(
            document.body.textContent.includes('This field is required.'),
        ).toBe(true)
    })

    it('submits with completedBy set to the current user when Completed? is toggled on', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openEditSickPlanItem(SICK_PLAN_ITEM)
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = 'Pain'
        select.dispatchEvent(new window.Event('change'))
        const checkbox = document.body.querySelector('input[type="checkbox"]')
        checkbox.checked = true
        checkbox.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitSickPlanItem).toHaveBeenCalledWith(
            expect.objectContaining({
                sickPlanType: 'Pain',
                completed: true,
                completedBy: 'Anna Lopatnova',
            }),
        )
    })

    it('submits with completedBy null when Completed? stays off', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openEditSickPlanItem({
            ...SICK_PLAN_ITEM,
            completed: false,
            completedBy: null,
        })
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = 'Pain'
        select.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitSickPlanItem).toHaveBeenCalledWith(
            expect.objectContaining({ completed: false, completedBy: null }),
        )
    })

    it.each([
        'Discharged',
        'Ready for Review',
        'Ready for Next Steps',
        'Completed',
        'Closed',
    ])(
        'renders read-only with no Submit action when the tracker is %s',
        async (status) => {
            const store = mountComponent({ tracker_id: 1, status })
            store.openEditSickPlanItem(SICK_PLAN_ITEM)
            await new Promise((r) => window.setTimeout(r))

            expect(
                document.body.querySelector('.sick-plan-details__readonly'),
            ).not.toBeNull()
            expect(
                document.body.querySelector('.sick-plan-details__form'),
            ).toBeNull()
            expect(document.body.textContent).toContain('Emma Watson')
            const buttons = Array.from(document.body.querySelectorAll('button'))
            expect(buttons.some((b) => b.textContent.includes('Submit'))).toBe(
                false,
            )
        },
    )
})
