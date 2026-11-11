import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import ImmunizationScheduleModal from '@/modules/vbc/components/ImmunizationScheduleModal.vue'
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

// UiModal renders via <teleport to="body">, so its content lives outside `wrapper.element` — query
// `document.body`, per the existing modal specs.

const OPTIONS = {
    states: [
        { value: 'TX', text: 'Texas' },
        { value: 'AZ', text: 'Arizona' },
    ],
    vaccineCategories: [
        { value: 101, text: 'Combination vaccine' },
        { value: 105, text: 'Bacterial infections' },
    ],
    vaccines: [
        { value: 20, text: 'DTaP', category: 101 },
        { value: 114, text: 'Meningococcal ACWY (MenACWY)', category: 105 },
    ],
    recommendedFor: [
        { value: 'ALL_CHILDREN', label: 'All Children' },
        {
            value: 'HIGH_RISK_GROUPS',
            label: 'High risk groups or some populations',
        },
    ],
    frequencies: [
        { value: 'ONE_PER_SERIES', label: 'Once per series' },
        { value: 'TWO_PER_SERIES', label: 'Up to 2 doses per series' },
    ],
    doseAgeUnits: [
        { value: 'MONTH', label: 'Month(s)' },
        { value: 'YEAR', label: 'Years' },
    ],
}

const SAVED = {
    id: 7,
    cvx_group_code: 105,
    vaccine_cvx_code: 114,
    state: 'TX',
    recommended_for: 'HIGH_RISK_GROUPS',
    frequency: 'TWO_PER_SERIES',
    is_active: false,
    dose_age_targets: [
        {
            from_age: 1,
            from_age_unit: 'MONTH',
            to_range: true,
            to_age: 6,
            to_age_unit: 'MONTH',
        },
        {
            from_age: 2,
            from_age_unit: 'YEAR',
            to_range: false,
            to_age: null,
            to_age_unit: null,
        },
    ],
}

// Order of the selects in the modal.
const CATEGORY = 0
const STATE = 1
const VACCINE = 2
const RECOMMENDED_FOR = 3
const FREQUENCY = 4
const FIRST_ROW_START_UNIT = 5

const tick = () => new Promise((r) => window.setTimeout(r))
const selects = () => Array.from(document.body.querySelectorAll('select'))
const textInputs = () =>
    Array.from(document.body.querySelectorAll('input[type="text"]'))
const button = (label) =>
    Array.from(document.body.querySelectorAll('button')).find((b) =>
        b.textContent.includes(label),
    )

async function choose(index, value) {
    const select = selects()[index]
    select.value = String(value)
    select.dispatchEvent(new window.Event('change'))
    await tick()
}

async function type(index, value) {
    const input = textInputs()[index]
    input.value = value
    input.dispatchEvent(new window.Event('input'))
    await tick()
}

// The "to" checkbox of the n-th dose target row (the Active toggle's checkbox comes after all of them).
const toggleRange = (rowIndex) =>
    document.body.querySelectorAll('input[type="checkbox"]')[rowIndex].click()

async function click(label) {
    button(label).click()
    await tick()
}

async function fillRequiredFields() {
    await choose(CATEGORY, 105)
    await choose(STATE, 'TX')
    await choose(VACCINE, 114)
    await choose(RECOMMENDED_FOR, 'ALL_CHILDREN')
    await choose(FREQUENCY, 'TWO_PER_SERIES')
}

describe('ImmunizationScheduleModal', () => {
    beforeEach(() => {
        document.body.innerHTML = ''
        vi.clearAllMocks()
        ImmunizationScheduleService.getOptions.mockResolvedValue(OPTIONS)
        ImmunizationScheduleService.getAll.mockResolvedValue({
            data: [],
            meta: { total: 0, current_page: 1, per_page: 10 },
        })
    })

    async function openAdd() {
        const store = mountComponent()
        store.openAdd()
        await tick()
        await tick()
        return store
    }

    function mountComponent() {
        const pinia = createTestingPinia({
            stubActions: false,
            createSpy: vi.fn,
        })
        mount(ImmunizationScheduleModal, {
            attachTo: document.body,
            global: { plugins: [pinia] },
        })
        return useImmunizationSchedule()
    }

    it('is titled Add and starts with Active on', async () => {
        await openAdd()

        expect(document.body.textContent).toContain('Add Immunization Schedule')
        expect(
            document.body.querySelector('.toggle-switch__input').checked,
        ).toBe(true)
    })

    it('blocks saving an empty form and names every missing field', async () => {
        await openAdd()

        await click('Submit')

        const text = document.body.textContent
        expect(text).toContain('Vaccine Category is required.')
        expect(text).toContain('State is required.')
        expect(text).toContain('Vaccine Name is required.')
        expect(text).toContain('Recommended For is required.')
        expect(text).toContain('Frequency is required.')
        expect(text).toContain('At least one Dose Age Target is required.')
        expect(ImmunizationScheduleService.create).not.toHaveBeenCalled()
    })

    it('keeps Vaccine Name disabled until a category is chosen and then lists only its vaccines', async () => {
        await openAdd()
        expect(selects()[VACCINE].disabled).toBe(true)

        await choose(CATEGORY, 105)

        const names = Array.from(
            selects()[VACCINE].querySelectorAll('option'),
        ).map((o) => o.textContent.trim())
        expect(selects()[VACCINE].disabled).toBe(false)
        expect(names).toEqual(['- Select -', 'Meningococcal ACWY (MenACWY)'])
    })

    it('forgets the chosen vaccine when the category changes', async () => {
        await openAdd()
        await choose(CATEGORY, 105)
        await choose(VACCINE, 114)
        expect(selects()[VACCINE].value).toBe('114')

        await choose(CATEGORY, 101)

        expect(selects()[VACCINE].value).toBe('')
    })

    it('saves a single value and a range with numeric values and a null end for the single one', async () => {
        ImmunizationScheduleService.create.mockResolvedValue({ id: 1 })
        await openAdd()
        await fillRequiredFields()

        await click('Add dose target')
        await type(0, '2')
        await click('Add dose target')
        await type(2, '4')
        toggleRange(1)
        await tick()
        await type(3, '6')
        await click('Submit')

        expect(ImmunizationScheduleService.create).toHaveBeenCalledWith({
            vaccine_cvx_code: 114,
            state: 'TX',
            recommended_for: 'ALL_CHILDREN',
            frequency: 'TWO_PER_SERIES',
            is_active: true,
            dose_age_targets: [
                {
                    from_age: 2,
                    from_age_unit: 'MONTH',
                    to_range: false,
                    to_age: null,
                    to_age_unit: null,
                },
                {
                    from_age: 4,
                    from_age_unit: 'MONTH',
                    to_range: true,
                    to_age: 6,
                    to_age_unit: 'MONTH',
                },
            ],
        })
    })

    it('accepts 23 months', async () => {
        ImmunizationScheduleService.create.mockResolvedValue({ id: 1 })
        await openAdd()
        await fillRequiredFields()
        await click('Add dose target')

        await type(0, '23')
        await click('Submit')

        expect(ImmunizationScheduleService.create).toHaveBeenCalledTimes(1)
    })

    it('rejects a start of 24 months', async () => {
        await openAdd()
        await fillRequiredFields()
        await click('Add dose target')

        await type(0, '24')
        await click('Submit')

        expect(document.body.textContent).toContain(
            'Start value in months must be 23 or lower.',
        )
        expect(ImmunizationScheduleService.create).not.toHaveBeenCalled()
    })

    it('rejects a start below 2 years', async () => {
        await openAdd()
        await fillRequiredFields()
        await click('Add dose target')
        await choose(FIRST_ROW_START_UNIT, 'YEAR')

        await type(0, '1')
        await click('Submit')

        expect(document.body.textContent).toContain(
            'Start value in years must be 2 or higher.',
        )
        expect(ImmunizationScheduleService.create).not.toHaveBeenCalled()
    })

    it('rejects an end of 24 months', async () => {
        await openAdd()
        await fillRequiredFields()
        await click('Add dose target')
        await type(0, '1')
        toggleRange(0)
        await tick()
        await type(1, '24')

        await click('Submit')

        expect(document.body.textContent).toContain(
            'End value in months must be 23 or lower.',
        )
        expect(ImmunizationScheduleService.create).not.toHaveBeenCalled()
    })

    it('rejects a range whose end is not above its start', async () => {
        await openAdd()
        await fillRequiredFields()
        await click('Add dose target')
        await type(0, '15')
        toggleRange(0)
        await tick()
        await type(1, '10')

        await click('Submit')

        expect(document.body.textContent).toContain(
            'End value must be greater than the start value.',
        )
        expect(ImmunizationScheduleService.create).not.toHaveBeenCalled()
    })

    it('shows the backend message under the field and drops it once the form is edited', async () => {
        ImmunizationScheduleService.create.mockRejectedValue({
            response: {
                status: 422,
                data: {
                    errors: {
                        vaccine_cvx_code: [
                            'An immunization schedule for this vaccine and state already exists.',
                        ],
                        dose_age_targets: ['Dose targets #1 and #2 overlap.'],
                    },
                },
            },
        })
        const store = await openAdd()
        await fillRequiredFields()
        await click('Add dose target')
        await type(0, '2')

        await click('Submit')

        expect(store.modalOpen).toBe(true)
        expect(document.body.textContent).toContain(
            'An immunization schedule for this vaccine and state already exists.',
        )
        expect(document.body.textContent).toContain(
            'Dose targets #1 and #2 overlap.',
        )

        await choose(STATE, 'AZ')

        expect(document.body.textContent).not.toContain('already exists.')
        expect(document.body.textContent).not.toContain(
            'Dose targets #1 and #2 overlap.',
        )
    })

    it('opens Edit prefilled and saves through update with the schedule id', async () => {
        ImmunizationScheduleService.getOne.mockResolvedValue(SAVED)
        ImmunizationScheduleService.update.mockResolvedValue({ id: 7 })
        const store = mountComponent()
        await store.openEdit(7)
        await tick()
        await tick()

        expect(document.body.textContent).toContain(
            'Edit Immunization Schedule',
        )
        expect(selects()[CATEGORY].value).toBe('105')
        expect(selects()[STATE].value).toBe('TX')
        expect(selects()[VACCINE].value).toBe('114')
        expect(selects()[RECOMMENDED_FOR].value).toBe('HIGH_RISK_GROUPS')
        expect(selects()[FREQUENCY].value).toBe('TWO_PER_SERIES')
        expect(
            document.body.querySelector('.toggle-switch__input').checked,
        ).toBe(false)
        expect(textInputs().map((i) => i.value)).toEqual(['1', '6', '2', ''])

        await click('Submit')

        expect(ImmunizationScheduleService.update).toHaveBeenCalledWith(7, {
            vaccine_cvx_code: 114,
            state: 'TX',
            recommended_for: 'HIGH_RISK_GROUPS',
            frequency: 'TWO_PER_SERIES',
            is_active: false,
            dose_age_targets: [
                {
                    from_age: 1,
                    from_age_unit: 'MONTH',
                    to_range: true,
                    to_age: 6,
                    to_age_unit: 'MONTH',
                },
                {
                    from_age: 2,
                    from_age_unit: 'YEAR',
                    to_range: false,
                    to_age: null,
                    to_age_unit: null,
                },
            ],
        })
        expect(ImmunizationScheduleService.create).not.toHaveBeenCalled()
    })

    it('Close shuts the modal without saving', async () => {
        const store = await openAdd()

        await click('Close')

        expect(store.modalOpen).toBe(false)
        expect(ImmunizationScheduleService.create).not.toHaveBeenCalled()
    })
})
