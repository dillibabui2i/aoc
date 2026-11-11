import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ActiveTrackerConflictModal from '@/modules/vbc/components/HospitalizationTracker/ActiveTrackerConflictModal.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// UiModal renders via <teleport to="body">, so its content lives outside
// `wrapper.element` — query `document.body` for it instead of `wrapper`.

describe('ActiveTrackerConflictModal', () => {
    let pinia

    beforeEach(() => {
        // Actions stay stubbed — the buttons only have to call them, and the real
        // openExistingTracker would fetch the tracker over the network.
        pinia = createTestingPinia({ createSpy: vi.fn })
        document.body.innerHTML = ''
    })

    function mountComponent() {
        return mount(ActiveTrackerConflictModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
    }

    it('is only visible during the "conflict" wizard step', async () => {
        mountComponent()
        const store = useHospitalizationTracker()

        expect(document.body.querySelector('.modal')).toBeNull()

        store.wizardStep = 'conflict'
        store.activeTrackerConflict = { tracker_id: '#10087' }
        store.selectedClient = {
            patient_name: 'John Doe',
            client_id: '99999-PDN',
        }
        await new Promise((r) => window.setTimeout(r))

        expect(document.body.querySelector('.modal')).not.toBeNull()
        // Identifies the client the conflict is about, so the user can tell
        // whether they picked the wrong person.
        expect(document.body.textContent).toContain('John Doe')
        expect(document.body.textContent).toContain('99999-PDN')
    })

    it('"Open the active tracker" calls store.openExistingTracker', async () => {
        mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'conflict'
        store.activeTrackerConflict = { tracker_id: '#10087' }
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons
            .find((b) => b.textContent.includes('Open the active tracker'))
            .click()

        expect(store.openExistingTracker).toHaveBeenCalledOnce()
    })

    it('"Search another client" calls store.searchAnotherClient', async () => {
        mountComponent()
        const store = useHospitalizationTracker()
        store.wizardStep = 'conflict'
        store.activeTrackerConflict = { tracker_id: '#10087' }
        await new Promise((r) => window.setTimeout(r))

        const buttons = Array.from(document.body.querySelectorAll('button'))
        buttons
            .find((b) => b.textContent.includes('Search another client'))
            .click()

        expect(store.searchAnotherClient).toHaveBeenCalledOnce()
    })
})
