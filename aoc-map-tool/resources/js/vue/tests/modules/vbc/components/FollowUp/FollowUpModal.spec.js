import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import FollowUpModal from '@/modules/vbc/components/FollowUp/FollowUpModal.vue'
import { useFollowUp } from '@/modules/vbc/store/followUp.store.js'

// The modal loads its Assigned To options on mount; the store's submit
// actions persist through the same service. Keep all of it off the network.
vi.mock(
    '@/modules/vbc/services/hospitalizationTracker.service.js',
    async (importOriginal) => {
        const actual = await importOriginal()
        return {
            ...actual,
            HospitalizationTrackerService: {
                ...actual.HospitalizationTrackerService,
                getAssignees: vi
                    .fn()
                    .mockResolvedValue([{ value: 5, label: 'Anna Lopatnova' }]),
                createFollowUp: vi
                    .fn()
                    .mockResolvedValue({ id: 'f-new', status: 'Open' }),
                updateFollowUpOutcome: vi
                    .fn()
                    .mockImplementation((id, payload) =>
                        Promise.resolve({ id, status: 'Open', payload }),
                    ),
            },
        }
    },
)

// UiModal renders via <teleport to="body">, so its content lives outside
// `wrapper.element` — query `document.body` for it, per the existing
// ContactDetailsModal.spec.js convention.

const UiDateTimepickerStub = {
    props: ['modelValue', 'label'],
    emits: ['update:modelValue'],
    template:
        '<label>{{ label }}<input type="text" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /></label>',
}

const CLIENT = {
    clientId: '12345-PDN',
    fullName: 'John Taylor',
    trackerId: '#12346',
    facilityName: 'First Provider of Dallas',
    admissionDateTime: '1/1/2026 12:00 AM',
    level: 'Level I/III',
}

describe('FollowUpModal', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    function mountComponent() {
        const pinia = createTestingPinia({
            stubActions: false,
            createSpy: vi.fn,
        })
        mount(FollowUpModal, {
            attachTo: document.body,
            global: {
                plugins: [pinia],
                stubs: { UiDateTimepicker: UiDateTimepickerStub },
            },
        })
        return useFollowUp()
    }

    it('shows only the patient search in the New stage', async () => {
        const store = mountComponent()
        store.openCreate()
        await new Promise((r) => window.setTimeout(r))

        expect(document.body.textContent).toContain('New')
        expect(
            document.body.querySelector('.hospitalized-client-search'),
        ).not.toBeNull()
    })

    it('reveals the header and Save once a patient is selected', async () => {
        const store = mountComponent()
        store.openCreate()
        store.selectClientForCreate(CLIENT)
        await new Promise((r) => window.setTimeout(r))

        expect(document.body.textContent).toContain('John Taylor')
        expect(document.body.textContent).toContain(
            'More than 30 days have passed',
        )
        const buttons = Array.from(document.body.querySelectorAll('button'))
        expect(buttons.some((b) => b.textContent.includes('Submit'))).toBe(true)
    })

    it('saving in the New stage calls store.submitCreate', async () => {
        const store = mountComponent()
        store.openCreate()
        store.selectClientForCreate(CLIENT)
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitCreate).toHaveBeenCalledOnce()
    })

    it('Open status shows all four outcomes', async () => {
        const store = mountComponent()
        store.followUp = { id: 'f1', trackerId: '#12346', status: 'Open' }
        store.modalOpen = true
        await new Promise((r) => window.setTimeout(r))

        const options = Array.from(
            document.body.querySelectorAll('select option'),
        ).map((o) => o.value)
        expect(options).toEqual(
            expect.arrayContaining([
                'Scheduled',
                'Completed',
                'Attempted',
                'Canceled',
            ]),
        )
    })

    it('Scheduled status offers only Completed/Attempted/Canceled', async () => {
        const store = mountComponent()
        store.followUp = {
            id: 'f1',
            trackerId: '#12346',
            status: 'Scheduled',
            followUpDate: '3/01/2026 12:00 AM',
            assignedTo: 'Axel Hawker',
        }
        store.modalOpen = true
        await new Promise((r) => window.setTimeout(r))

        const options = Array.from(
            document.body.querySelectorAll('select option'),
        ).map((o) => o.value)
        expect(options).not.toContain('Scheduled')
        expect(options).toEqual(
            expect.arrayContaining(['Completed', 'Attempted', 'Canceled']),
        )
        expect(document.body.textContent).toContain('Axel Hawker')
    })

    it('selecting Scheduled shows date/assignee and validates them', async () => {
        const store = mountComponent()
        store.followUp = { id: 'f1', trackerId: '#12346', status: 'Open' }
        store.modalOpen = true
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = 'Scheduled'
        select.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitOutcome).not.toHaveBeenCalled()
        expect(
            document.body.textContent.includes('This field is required.'),
        ).toBe(true)
    })

    it('submits Scheduled with valid date/assignee', async () => {
        const store = mountComponent()
        store.followUp = { id: 'f1', trackerId: '#12346', status: 'Open' }
        store.modalOpen = true
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = 'Scheduled'
        select.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        const dateInput = document.body.querySelector('input[type="text"]')
        dateInput.value = '3/01/2026 12:00 AM'
        dateInput.dispatchEvent(new window.Event('input'))
        const assigneeSelects = document.body.querySelectorAll('select')
        // Options come from getAssignees(); the select carries the user id.
        assigneeSelects[1].value = '5'
        assigneeSelects[1].dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitOutcome).toHaveBeenCalledWith(
            'Scheduled',
            expect.objectContaining({
                followUpDate: '3/01/2026 12:00 AM',
                assignedTo: '5',
            }),
        )
    })

    it('Completed defaults Next Follow-up Due ON for a qualifying level', async () => {
        const store = mountComponent()
        store.followUp = {
            id: 'f1',
            trackerId: '#12346',
            status: 'Open',
            level: 'Level I/III',
            followUpDate: '3/01/2026 12:00 AM',
        }
        store.modalOpen = true
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = 'Completed'
        select.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        expect(
            document.body.querySelector('input[type="checkbox"]').checked,
        ).toBe(true)
        const textInputs = document.body.querySelectorAll('input[type="text"]')
        // [0] Completion date, [1] Completed by (both disabled UiInputs),
        // [2] Next follow-up due date (the stubbed UiDateTimepicker).
        // Seeded as follow-up date + 7 days, formatted MM/DD/YYYY HH:mm.
        expect(textInputs[2].value).toBe('03/08/2026 00:00')
    })

    it('Completed defaults Next Follow-up Due OFF for a non-qualifying level', async () => {
        const store = mountComponent()
        store.followUp = {
            id: 'f1',
            trackerId: '#12346',
            status: 'Open',
            level: 'Level II',
        }
        store.modalOpen = true
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = 'Completed'
        select.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        expect(
            document.body.querySelector('input[type="checkbox"]').checked,
        ).toBe(false)
    })

    it('validates required Completed fields', async () => {
        const store = mountComponent()
        store.followUp = {
            id: 'f1',
            trackerId: '#12346',
            status: 'Open',
            level: 'Level II',
        }
        store.modalOpen = true
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = 'Completed'
        select.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitOutcome).not.toHaveBeenCalled()
        expect(
            document.body.textContent.includes('This field is required.'),
        ).toBe(true)
    })

    it('submits Attempted with required Attempt Notes', async () => {
        const store = mountComponent()
        store.followUp = {
            id: 'f1',
            trackerId: '#12346',
            status: 'Open',
            level: 'Level II',
        }
        store.modalOpen = true
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = 'Attempted'
        select.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        const textarea = document.body.querySelector('textarea')
        textarea.value = 'Tried calling, no answer.'
        textarea.dispatchEvent(new window.Event('input'))
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitOutcome).toHaveBeenCalledWith(
            'Attempted',
            expect.objectContaining({
                attemptNotes: 'Tried calling, no answer.',
            }),
        )
    })

    it('Canceled shows no extra fields and submits directly', async () => {
        const store = mountComponent()
        store.followUp = { id: 'f1', trackerId: '#12346', status: 'Open' }
        store.modalOpen = true
        await new Promise((r) => window.setTimeout(r))

        const select = document.body.querySelector('select')
        select.value = 'Canceled'
        select.dispatchEvent(new window.Event('change'))
        await new Promise((r) => window.setTimeout(r))

        expect(document.body.querySelector('textarea')).toBeNull()

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitOutcome).toHaveBeenCalledWith(
            'Canceled',
            expect.anything(),
        )
    })

    it.each(['Completed', 'Attempted', 'Canceled'])(
        'renders read-only with no Submit action for %s',
        async (status) => {
            const store = mountComponent()
            store.followUp = {
                id: 'f1',
                trackerId: '#12346',
                status,
                followUpDate: '3/01/2026 12:00 AM',
                assignedTo: 'Axel Hawker',
                attemptNotes: 'Notes here',
            }
            store.modalOpen = true
            await new Promise((r) => window.setTimeout(r))

            expect(document.body.querySelector('select')).toBeNull()
            expect(document.body.querySelector('textarea')).toBeNull()
            const buttons = Array.from(document.body.querySelectorAll('button'))
            expect(buttons.some((b) => b.textContent.includes('Submit'))).toBe(
                false,
            )
        },
    )
})
