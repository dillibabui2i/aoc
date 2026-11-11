import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useEdVisitTracker } from '@/modules/vbc/store/edVisitTracker.store.js'
import { EdVisitTrackerService } from '@/modules/vbc/services/edVisitTracker.service.js'

// PD-3973: Education tab store actions against the (mocked) real Training/Education endpoints.
vi.mock('@/modules/vbc/services/edVisitTracker.service.js', () => ({
    EdVisitTrackerService: {
        updateStaffFamilyTrainingRequired: vi.fn(),
        createEducationRecord: vi.fn(),
        updateEducationRecord: vi.fn(),
    },
}))

const FIELDS = {
    training_type_id: 1,
    training_source_id: 2,
    notes: 'n',
    is_completed: false,
}
const SAVED = {
    id: 5,
    training_type_id: 1,
    training_type_name: 'Head Injury',
    training_source_id: 2,
    training_source_name: 'Family Request',
    notes: 'n',
    is_completed: false,
}

describe('useEdVisitTracker education', () => {
    let store
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        store = useEdVisitTracker()
        store.tracker = {
            tracker_id: 42,
            status: 'In Progress',
            staff_family_training_required: false,
            education_records: [],
        }
    })

    it('toggle: optimistic, then replaced by the tracker the server returns', async () => {
        const server = { ...store.tracker, staff_family_training_required: true }
        EdVisitTrackerService.updateStaffFamilyTrainingRequired.mockResolvedValue(
            server,
        )
        await store.setStaffFamilyTrainingRequired(true)
        expect(
            EdVisitTrackerService.updateStaffFamilyTrainingRequired,
        ).toHaveBeenCalledWith(42, true)
        expect(store.tracker).toEqual(server)
    })

    it('toggle: rolls back when the request fails', async () => {
        EdVisitTrackerService.updateStaffFamilyTrainingRequired.mockRejectedValue(
            new Error('409'),
        )
        await expect(store.setStaffFamilyTrainingRequired(true)).rejects.toThrow()
        expect(store.tracker.staff_family_training_required).toBe(false)
    })

    it('creates a record and prepends it to the tracker records', async () => {
        EdVisitTrackerService.createEducationRecord.mockResolvedValue(SAVED)
        store.tracker.education_records = [{ id: 1 }]
        store.openAddEducationRecord()
        await store.submitEducationRecord({ ...FIELDS })
        expect(EdVisitTrackerService.createEducationRecord).toHaveBeenCalledWith(
            42,
            FIELDS,
        )
        expect(store.educationModalOpen).toBe(false)
        expect(store.tracker.education_records).toEqual([SAVED, { id: 1 }])
    })

    it('edits a record in place', async () => {
        const updated = { ...SAVED, is_completed: true }
        EdVisitTrackerService.updateEducationRecord.mockResolvedValue(updated)
        store.tracker.education_records = [SAVED, { id: 1 }]
        store.openEditEducationRecord(SAVED)
        await store.submitEducationRecord({ ...FIELDS, is_completed: true })
        expect(EdVisitTrackerService.updateEducationRecord).toHaveBeenCalledWith(
            42,
            5,
            { ...FIELDS, is_completed: true },
        )
        expect(store.tracker.education_records).toEqual([updated, { id: 1 }])
    })

    it('does nothing when the tracker is not In Progress', async () => {
        store.tracker = { ...store.tracker, status: 'Completed' }
        await store.setStaffFamilyTrainingRequired(true)
        await store.submitEducationRecord({ ...FIELDS })
        expect(
            EdVisitTrackerService.updateStaffFamilyTrainingRequired,
        ).not.toHaveBeenCalled()
        expect(EdVisitTrackerService.createEducationRecord).not.toHaveBeenCalled()
    })
})
