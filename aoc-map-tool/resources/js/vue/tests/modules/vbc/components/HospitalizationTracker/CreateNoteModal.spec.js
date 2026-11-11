import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import CreateNoteModal from '@/modules/vbc/components/HospitalizationTracker/CreateNoteModal.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// The real submitNote persists through createNote — keep it off the network.
vi.mock(
    '@/modules/vbc/services/hospitalizationTracker.service.js',
    async (importOriginal) => {
        const actual = await importOriginal()
        return {
            ...actual,
            HospitalizationTrackerService: {
                ...actual.HospitalizationTrackerService,
                createNote: vi.fn().mockResolvedValue([]),
                getTrackerNotes: vi.fn().mockResolvedValue([]),
            },
        }
    },
)

// UiModal renders via <teleport to="body">, so its content lives outside
// `wrapper.element` — query `document.body` for it, per the existing
// ContactDetailsModal.spec.js convention.

describe('CreateNoteModal', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
    })

    function mountComponent() {
        const pinia = createTestingPinia({
            stubActions: false,
            createSpy: vi.fn,
        })
        mount(CreateNoteModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        return useHospitalizationTracker()
    }

    it('prevents submission and shows an error when blank', async () => {
        const store = mountComponent()
        store.openCreateNoteForTracker('#11123')
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitNote).not.toHaveBeenCalled()
        expect(
            document.body.querySelector('.ui-textarea-wrap--error'),
        ).not.toBeNull()
    })

    it('submits the entered note text when valid', async () => {
        const store = mountComponent()
        store.openCreateNoteForTracker('#11123')
        await new Promise((r) => window.setTimeout(r))

        const textarea = document.body.querySelector('textarea')
        textarea.value = 'Family called to confirm discharge plan.'
        textarea.dispatchEvent(new window.Event('input'))
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))

        expect(store.submitNote).toHaveBeenCalledWith(
            'Family called to confirm discharge plan.',
        )
    })

    it('resets the textarea and error state each time the modal reopens', async () => {
        const store = mountComponent()
        store.openCreateNoteForTracker('#11123')
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons.find((b) => b.textContent.includes('Submit')).click()
        await new Promise((r) => window.setTimeout(r))
        expect(
            document.body.querySelector('.ui-textarea-wrap--error'),
        ).not.toBeNull()

        store.closeNoteModal()
        await new Promise((r) => window.setTimeout(r))
        store.openCreateNoteForTracker('#99999')
        await new Promise((r) => window.setTimeout(r))

        expect(document.body.querySelector('textarea').value).toBe('')
        expect(
            document.body.querySelector('.ui-textarea-wrap--error'),
        ).toBeNull()
    })
})
