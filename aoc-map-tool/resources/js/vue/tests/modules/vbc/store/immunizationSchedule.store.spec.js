import { describe, it, expect, vi, beforeEach } from 'vitest'
import { setActivePinia, createPinia } from 'pinia'
import { useImmunizationSchedule } from '@/modules/vbc/store/immunizationSchedule.store.js'
import { ImmunizationScheduleService } from '@/modules/vbc/services/immunizationSchedule.service.js'

vi.mock('@/modules/vbc/services/immunizationSchedule.service.js', () => ({
    ImmunizationScheduleService: {
        getAll: vi.fn(),
        getOptions: vi.fn(),
        getOne: vi.fn(),
        create: vi.fn(),
        update: vi.fn(),
    },
}))

const PAYLOAD = { vaccine_cvx_code: 114, state: 'TX' }
const LIST_RESPONSE = {
    data: [],
    meta: { total: 0, current_page: 1, per_page: 10 },
}

const validationError = (errors) => ({
    response: { status: 422, data: { errors } },
})

describe('useImmunizationSchedule — add / edit modal', () => {
    beforeEach(() => {
        setActivePinia(createPinia())
        vi.clearAllMocks()
        ImmunizationScheduleService.getAll.mockResolvedValue(LIST_RESPONSE)
    })

    it('openAdd opens the modal with nothing being edited', () => {
        const store = useImmunizationSchedule()
        store.editing = { id: 5 }
        store.serverErrors = { state: ['x'] }

        store.openAdd()

        expect(store.modalOpen).toBe(true)
        expect(store.editing).toBeNull()
        expect(store.serverErrors).toEqual({})
    })

    it('openEdit loads the schedule and opens the modal', async () => {
        const store = useImmunizationSchedule()
        ImmunizationScheduleService.getOne.mockResolvedValue({
            id: 7,
            state: 'TX',
        })

        await store.openEdit(7)

        expect(ImmunizationScheduleService.getOne).toHaveBeenCalledWith(7)
        expect(store.editing).toEqual({ id: 7, state: 'TX' })
        expect(store.modalOpen).toBe(true)
    })

    it('openEdit keeps the modal closed and shows the message when the schedule cannot be loaded', async () => {
        const store = useImmunizationSchedule()
        ImmunizationScheduleService.getOne.mockRejectedValue({
            response: { data: { message: 'Immunization schedule not found.' } },
        })

        await store.openEdit(7)

        expect(store.modalOpen).toBe(false)
        expect(store.error).toBe('Immunization schedule not found.')
    })

    it('loadOptions fetches the option lists once', async () => {
        const store = useImmunizationSchedule()
        ImmunizationScheduleService.getOptions.mockResolvedValue({ states: [] })

        await store.loadOptions()
        await store.loadOptions()

        expect(ImmunizationScheduleService.getOptions).toHaveBeenCalledTimes(1)
        expect(store.options).toEqual({ states: [] })
    })

    it('saveSchedule creates in Add mode, closes the modal and refreshes the table', async () => {
        const store = useImmunizationSchedule()
        ImmunizationScheduleService.create.mockResolvedValue({ id: 1 })
        store.openAdd()

        const saved = await store.saveSchedule(PAYLOAD)

        expect(saved).toBe(true)
        expect(ImmunizationScheduleService.create).toHaveBeenCalledWith(PAYLOAD)
        expect(ImmunizationScheduleService.update).not.toHaveBeenCalled()
        expect(store.modalOpen).toBe(false)
        expect(ImmunizationScheduleService.getAll).toHaveBeenCalledTimes(1)
    })

    it('saveSchedule updates the edited schedule in Edit mode', async () => {
        const store = useImmunizationSchedule()
        ImmunizationScheduleService.getOne.mockResolvedValue({ id: 7 })
        ImmunizationScheduleService.update.mockResolvedValue({ id: 7 })
        await store.openEdit(7)

        const saved = await store.saveSchedule(PAYLOAD)

        expect(saved).toBe(true)
        expect(ImmunizationScheduleService.update).toHaveBeenCalledWith(
            7,
            PAYLOAD,
        )
        expect(ImmunizationScheduleService.create).not.toHaveBeenCalled()
        expect(store.modalOpen).toBe(false)
    })

    it('saveSchedule keeps the modal open and exposes the 422 errors', async () => {
        const store = useImmunizationSchedule()
        const errors = { dose_age_targets: ['Dose targets #1 and #2 overlap.'] }
        ImmunizationScheduleService.create.mockRejectedValue(
            validationError(errors),
        )
        store.openAdd()

        const saved = await store.saveSchedule(PAYLOAD)

        expect(saved).toBe(false)
        expect(store.modalOpen).toBe(true)
        expect(store.serverErrors).toEqual(errors)
        expect(store.saving).toBe(false)
        expect(ImmunizationScheduleService.getAll).not.toHaveBeenCalled()
    })

    it('saveSchedule does not swallow errors other than a validation failure', async () => {
        const store = useImmunizationSchedule()
        ImmunizationScheduleService.create.mockRejectedValue({
            response: { status: 500 },
        })
        store.openAdd()

        await expect(store.saveSchedule(PAYLOAD)).rejects.toEqual({
            response: { status: 500 },
        })
        expect(store.saving).toBe(false)
        expect(store.modalOpen).toBe(true)
    })

    it('closeModal resets the editing state and the server errors', () => {
        const store = useImmunizationSchedule()
        store.modalOpen = true
        store.editing = { id: 7 }
        store.serverErrors = { state: ['x'] }

        store.closeModal()

        expect(store.modalOpen).toBe(false)
        expect(store.editing).toBeNull()
        expect(store.serverErrors).toEqual({})
    })
})
