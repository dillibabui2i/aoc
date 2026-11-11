import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { EdVisitTrackerService } from '@/modules/vbc/services/edVisitTracker.service.js'

// PD-3970: Diagnosis tab store actions against the (mocked) shared diagnosis endpoints.
vi.mock('@/modules/vbc/services/edVisitTracker.service.js', () => ({
    EdVisitTrackerService: {
        updateWasDiagnosisMade: vi.fn(),
        createDiagnosis: vi.fn(),
        updateDiagnosis: vi.fn(),
        getAssignees: vi.fn().mockResolvedValue([]),
    },
}))

const FIELDS = {
    icd_10_code: 'R94.5',
    description: 'Transaminitis',
    system: 'ICD-10',
    rank: 1,
    clinical_status: 'active',
    verification_status: 'suspected',
    notes: 'n',
}
const SAVED = { id: 5, ...FIELDS }

describe('useEdVisitTracker diagnosis', () => {
    let store
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        store = useEdVisitTracker()
        store.tracker = {
            tracker_id: 42,
            status: 'In Progress',
            was_diagnosis_made: false,
            diagnoses: [],
        }
    })

    it('toggle: optimistic, then replaced by the tracker the server returns', async () => {
        const server = { ...store.tracker, was_diagnosis_made: true }
        EdVisitTrackerService.updateWasDiagnosisMade.mockResolvedValue(server)
        await store.setWasDiagnosisMade(true)
        expect(EdVisitTrackerService.updateWasDiagnosisMade).toHaveBeenCalledWith(
            42,
            true,
        )
        expect(store.tracker).toEqual(server)
    })

    it('toggle: rolls back when the request fails', async () => {
        EdVisitTrackerService.updateWasDiagnosisMade.mockRejectedValue(
            new Error('409'),
        )
        await expect(store.setWasDiagnosisMade(true)).rejects.toThrow()
        expect(store.tracker.was_diagnosis_made).toBe(false)
    })

    it('creates a diagnosis and appends it to the tracker diagnoses', async () => {
        EdVisitTrackerService.createDiagnosis.mockResolvedValue(SAVED)
        store.tracker.diagnoses = [{ id: 1 }]
        store.openAddDiagnosis()
        await store.submitDiagnosis({ ...FIELDS })
        expect(EdVisitTrackerService.createDiagnosis).toHaveBeenCalledWith(
            42,
            FIELDS,
        )
        expect(store.diagnosisModalOpen).toBe(false)
        expect(store.tracker.diagnoses).toEqual([{ id: 1 }, SAVED])
    })

    it('edits a diagnosis in place', async () => {
        const updated = { ...SAVED, notes: 'changed' }
        EdVisitTrackerService.updateDiagnosis.mockResolvedValue(updated)
        store.tracker.diagnoses = [{ id: 1 }, SAVED]
        store.openEditDiagnosis(SAVED)
        await store.submitDiagnosis({ ...FIELDS, notes: 'changed' })
        expect(EdVisitTrackerService.updateDiagnosis).toHaveBeenCalledWith(
            42,
            5,
            { ...FIELDS, notes: 'changed' },
        )
        expect(store.tracker.diagnoses).toEqual([{ id: 1 }, updated])
    })

    it('does nothing when the tracker is not In Progress', async () => {
        store.tracker = { ...store.tracker, status: 'Completed' }
        await store.setWasDiagnosisMade(true)
        await store.submitDiagnosis({ ...FIELDS })
        expect(EdVisitTrackerService.updateWasDiagnosisMade).not.toHaveBeenCalled()
        expect(EdVisitTrackerService.createDiagnosis).not.toHaveBeenCalled()
    })

    it('reset() clears the diagnosis modal state', () => {
        store.openEditDiagnosis(SAVED)
        expect(store.diagnosisModalOpen).toBe(true)
        store.openCreateModal() // calls reset()
        expect(store.diagnosisModalOpen).toBe(false)
        expect(store.editingDiagnosis).toBeNull()
    })
})
