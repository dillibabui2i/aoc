import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ContactDetailsModal from '@/modules/vbc/components/HospitalizationTracker/ContactDetailsModal.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// The modal lazily loads its Relationship options through the service on
// first open — mocked so no real HTTP happens. `formatUsPhoneDisplay` stays
// real.
vi.mock(
    '@/modules/vbc/services/hospitalizationTracker.service.js',
    async (importOriginal) => {
        const actual = await importOriginal()
        return {
            ...actual,
            HospitalizationTrackerService: {
                getContactTypes: vi.fn().mockResolvedValue([
                    { value: 1, label: 'Step Mother' },
                    { value: 2, label: 'Nurse' },
                ]),
            },
        }
    },
)

// UiModal renders via <teleport to="body">, so its content lives outside
// `wrapper.element` — query `document.body` for it instead, per the
// existing ActiveTrackerConflictModal.spec.js convention.

const CONTACT = {
    id: 1,
    contactName: 'Pamela Hopkins',
    relationship: 'Step Mother',
    contactTypeId: 1,
    phoneNumber: '5551134212',
    email: 'not@for.you',
    contactDate: '3/30/2026 12:01 AM',
    contactReceivedBy: 'Melinda James',
    contactNotes: 'Some notes',
}

const tick = () => new Promise((r) => window.setTimeout(r))

function setInputValue(el, value) {
    el.value = value
    el.dispatchEvent(new window.Event('input'))
}

function clickButton(label) {
    Array.from(document.body.querySelectorAll('button'))
        .find((b) => b.textContent.includes(label))
        .click()
}

describe('ContactDetailsModal', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    // The form is (re)filled by a watcher on `contactModalOpen`, so the
    // tracker must exist via `initialState` before the modal is opened via
    // the (real, synchronous) store action. Only the HTTP-backed submit is
    // stubbed.
    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            stubActions: ['submitContact'],
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        mount(ContactDetailsModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        return useHospitalizationTracker()
    }

    it('opens blank when adding a new contact', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddContact()
        await tick()

        expect(document.body.querySelector('input').value).toBe('')
        expect(document.body.querySelector('select').value).toBe('')
    })

    it('opens prefilled with the existing values when editing', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openEditContact(CONTACT)
        await tick()

        const inputs = document.body.querySelectorAll('input')
        expect(inputs[0].value).toBe('Pamela Hopkins')
        // Relationship is pre-selected by the type's id, not its label.
        expect(document.body.querySelector('select').value).toBe('1')
        expect(inputs[1].value).toBe('+1 (555) 113-4212')
        expect(inputs[2].value).toBe('not@for.you')
    })

    it('prevents submission and names every missing required field', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddContact()
        await tick()

        clickButton('Submit')
        await tick()

        expect(store.submitContact).not.toHaveBeenCalled()
        const text = document.body.textContent
        expect(text).toContain('Contact Name is required.')
        expect(text).toContain('Relationship is required.')
        expect(text).toContain('Phone Number or E-mail is required.')
    })

    it('accepts an E-mail without a Phone Number', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddContact()
        await tick()

        const inputs = document.body.querySelectorAll('input')
        setInputValue(inputs[0], 'Julie Winborne')
        const select = document.body.querySelector('select')
        select.value = '2'
        select.dispatchEvent(new window.Event('change'))
        setInputValue(inputs[2], 'julie@example.com')
        await tick()

        clickButton('Submit')
        await tick()

        expect(document.body.textContent).not.toContain(
            'Phone Number or E-mail is required.',
        )
        expect(store.submitContact).toHaveBeenCalledOnce()
    })

    it('submits the entered contact when valid', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddContact()
        await tick()

        const inputs = document.body.querySelectorAll('input')
        setInputValue(inputs[0], 'Julie Winborne') // Contact Name
        const select = document.body.querySelector('select')
        select.value = '2' // Relationship: Nurse
        select.dispatchEvent(new window.Event('change'))
        setInputValue(inputs[1], '5550001111') // Phone Number
        setInputValue(inputs[2], 'julie@example.com') // E-mail
        setInputValue(document.body.querySelector('textarea'), 'Some notes')
        await tick()

        clickButton('Submit')
        await tick()

        expect(store.submitContact).toHaveBeenCalledWith(
            expect.objectContaining({
                contactName: 'Julie Winborne',
                relationship: '2',
                email: 'julie@example.com',
                contactNotes: 'Some notes',
            }),
        )
        expect(store.submitContact.mock.calls[0][0].phoneNumber).toBeTruthy()
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
            store.openEditContact(CONTACT)
            await tick()

            expect(
                document.body.querySelector('.contact-details__readonly'),
            ).not.toBeNull()
            expect(
                document.body.querySelector('.contact-details__form'),
            ).toBeNull()
            const text = document.body.textContent
            expect(text).toContain('Pamela Hopkins')
            expect(text).toContain('+1 (555) 113-4212')
            expect(text).toContain('Melinda James')
            const buttons = Array.from(document.body.querySelectorAll('button'))
            expect(buttons.some((b) => b.textContent.includes('Submit'))).toBe(
                false,
            )
        },
    )
})
