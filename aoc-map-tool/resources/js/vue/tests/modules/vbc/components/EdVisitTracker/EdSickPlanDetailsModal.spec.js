import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import EdSickPlanDetailsModal from '@/modules/vbc/components/EdVisitTracker/EdSickPlanDetailsModal.vue'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'

// UiModal teleports to body, so query document.body.
const ITEM = {
    id: 1,
    sickPlanType: 'Hydration',
    completed: true,
    completedBy: 'Emma Watson',
    notes: 'Some notes',
    creationDate: '3/29/2026 09:00 AM',
    author: 'Harrison Bush',
}
const tick = () => new Promise((r) => window.setTimeout(r))

async function openModal(status) {
    const pinia = createTestingPinia({
        stubActions: false,
        createSpy: vi.fn,
        initialState: {
            edVisitTracker: { tracker: { tracker_id: 9001, status } },
        },
    })
    mount(EdSickPlanDetailsModal, {
        attachTo: document.body,
        global: { plugins: [pinia] },
    })
    const store = useEdVisitTracker()
    store.openEditSickPlanItem(ITEM)
    await tick()
    return store
}

const submitButton = () =>
    Array.from(document.body.querySelectorAll('button')).find((b) =>
        b.textContent.includes('Submit'),
    )

describe('EdSickPlanDetailsModal', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    it('In Progress: editable form prefilled, Submit saves the item', async () => {
        const store = await openModal('In Progress')
        expect(
            document.body.querySelector('.sick-plan-details__form'),
        ).not.toBeNull()
        expect(document.body.querySelector('select').value).toBe('Hydration')

        submitButton().click()
        await tick()

        expect(store.submitSickPlanItem).toHaveBeenCalledWith(
            expect.objectContaining({ completed: true, notes: 'Some notes' }),
        )
    })

    it.each([
        'Ready for Review',
        'Ready for Next Steps',
        'Completed',
        'Closed',
    ])('%s: read-only with no Submit action', async (status) => {
        await openModal(status)
        expect(
            document.body.querySelector('.sick-plan-details__readonly'),
        ).not.toBeNull()
        expect(
            document.body.querySelector('.sick-plan-details__form'),
        ).toBeNull()
        expect(document.body.textContent).toContain('Emma Watson')
        expect(submitButton()).toBeUndefined()
    })
})
