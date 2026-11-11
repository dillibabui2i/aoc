import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import DiagnosisDetailsModal from '@/modules/vbc/components/HospitalizationTracker/DiagnosisDetailsModal.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'
import { HospitalizationTrackerService } from '@/modules/vbc/services/hospitalizationTracker.service.js'

// IcdCodeSearch (inside the modal) queries the ICD-10 search endpoint
// through the service directly — mocked so no real HTTP happens.
vi.mock('@/modules/vbc/services/hospitalizationTracker.service.js', () => ({
    HospitalizationTrackerService: {
        searchDiagnosisCodes: vi.fn(),
    },
}))

// UiModal renders via <teleport to="body">, so its content lives outside
// `wrapper.element` — query `document.body` for it, per the existing
// ContactDetailsModal.spec.js convention.

const DIAGNOSIS = {
    id: 1,
    icd_10_code: 'R94.5',
    description: 'Transaminitis',
    rank: 'PRIMARY',
    rank_label: 'Primary',
    system: 'CARDIOVASCULAR',
    system_label: 'Cardiovascular',
    clinical_status: 'ACTIVE',
    clinical_status_label: 'Active',
    verification_status: 'UNCONFIRMED',
    verification_status_label: 'Unconfirmed / Suspected',
    created_at: '3/30/2026 12:01 AM',
    author: 'Melinda James',
    notes: 'Some notes',
}

const tick = (ms = 0) => new Promise((r) => window.setTimeout(r, ms))

function setSelectValue(el, value) {
    el.value = value
    el.dispatchEvent(new window.Event('change'))
}

function clickButton(label) {
    Array.from(document.body.querySelectorAll('button'))
        .find((b) => b.textContent.includes(label))
        .click()
}

describe('DiagnosisDetailsModal', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
        vi.clearAllMocks()
    })

    // The form is (re)filled by a watcher on `diagnosisModalOpen`, so the
    // tracker must exist via `initialState` before the modal is opened via
    // the (real, synchronous) store action. Only the HTTP-backed submit is
    // stubbed.
    function mountComponent(tracker) {
        const pinia = createTestingPinia({
            stubActions: ['submitDiagnosis'],
            createSpy: vi.fn,
            initialState: { hospitalizationTracker: { tracker } },
        })
        mount(DiagnosisDetailsModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        return useHospitalizationTracker()
    }

    it('opens blank when adding a new diagnosis, without the system-set Creation Date/Author', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddDiagnosis()
        await tick()

        document.body
            .querySelectorAll('select')
            .forEach((select) => expect(select.value).toBe(''))
        const inputs = document.body.querySelectorAll('input')
        // Only the Diagnosis Code search input — Creation Date/Author are
        // stamped by the backend and only exist once editing.
        expect(inputs).toHaveLength(1)
        expect(inputs[0].value).toBe('')
        expect(document.body.textContent).not.toContain('Creation Date')
    })

    it('opens prefilled with the existing values when editing', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openEditDiagnosis(DIAGNOSIS)
        await tick()

        const inputs = document.body.querySelectorAll('input')
        expect(inputs[0].value).toBe('R94.5 — Transaminitis')
        const selects = document.body.querySelectorAll('select')
        expect(Array.from(selects).map((s) => s.value)).toEqual([
            'PRIMARY',
            'CARDIOVASCULAR',
            'ACTIVE',
            'UNCONFIRMED',
        ])
        // Creation Date / Author, both disabled and pre-filled.
        expect(inputs[1].disabled).toBe(true)
        expect(inputs[1].value).toBe('3/30/2026 12:01 AM')
        expect(inputs[2].disabled).toBe(true)
        expect(inputs[2].value).toBe('Melinda James')
    })

    it('prevents submission and marks required fields when incomplete', async () => {
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddDiagnosis()
        await tick()

        clickButton('Submit')
        await tick()

        expect(store.submitDiagnosis).not.toHaveBeenCalled()
        const text = document.body.textContent
        expect(text).toContain('Diagnosis Code is required.')
        expect(text).toContain('Rank is required.')
        expect(text).toContain('System is required.')
        expect(text).toContain('Clinical Status is required.')
        expect(text).toContain('Verification Status is required.')
    })

    it('submits the entered diagnosis when valid', async () => {
        HospitalizationTrackerService.searchDiagnosisCodes.mockResolvedValue([
            { code: 'R94.5', description: 'Transaminitis' },
        ])
        const store = mountComponent({ tracker_id: 1, status: 'In Progress' })
        store.openAddDiagnosis()
        await tick()

        // Diagnosis Code is an ICD-10 typeahead: type, wait out its 300ms
        // debounce, then pick the result.
        const codeInput = document.body.querySelector('input')
        codeInput.focus()
        codeInput.dispatchEvent(new window.Event('focus'))
        codeInput.value = 'R94'
        codeInput.dispatchEvent(new window.Event('input'))
        await tick(350)
        document.body
            .querySelector('.icd-code-search__result')
            .dispatchEvent(new window.MouseEvent('mousedown'))
        await tick()

        const selects = document.body.querySelectorAll('select')
        setSelectValue(selects[0], 'PRIMARY')
        setSelectValue(selects[1], 'CARDIOVASCULAR')
        setSelectValue(selects[2], 'ACTIVE')
        setSelectValue(selects[3], 'UNCONFIRMED')
        await tick()

        clickButton('Submit')
        await tick()

        expect(
            HospitalizationTrackerService.searchDiagnosisCodes,
        ).toHaveBeenCalledWith('R94')
        expect(store.submitDiagnosis).toHaveBeenCalledWith(
            expect.objectContaining({
                icd_10_code: 'R94.5',
                description: 'Transaminitis',
                rank: 'PRIMARY',
                system: 'CARDIOVASCULAR',
                clinical_status: 'ACTIVE',
                verification_status: 'UNCONFIRMED',
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
        'renders read-only with no Submit action when the tracker is %s',
        async (status) => {
            const store = mountComponent({ tracker_id: 1, status })
            store.openEditDiagnosis(DIAGNOSIS)
            await tick()

            expect(
                document.body.querySelector('.diagnosis-details__readonly'),
            ).not.toBeNull()
            expect(
                document.body.querySelector('.diagnosis-details__form'),
            ).toBeNull()
            expect(document.body.textContent).toContain('R94.5 — Transaminitis')
            const buttons = Array.from(document.body.querySelectorAll('button'))
            expect(buttons.some((b) => b.textContent.includes('Submit'))).toBe(
                false,
            )
        },
    )
})
