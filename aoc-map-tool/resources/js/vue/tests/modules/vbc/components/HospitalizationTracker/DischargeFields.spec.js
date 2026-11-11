import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createTestingPinia } from '@pinia/testing'
import DischargeFields from '@/modules/vbc/components/HospitalizationTracker/DischargeFields.vue'
import { useHospitalizationTracker } from '@/modules/vbc/store/hospitalizationTracker.store.js'

// flatpickr (behind UiDateTimepicker) doesn't behave meaningfully in
// jsdom — stub it with a plain input wired to the same v-model/label/error
// contract so date fields can be inspected/filled directly in tests, per
// the project's existing pattern of stubbing third-party widgets that
// don't work in jsdom.
const UiDateTimepickerStub = {
    props: ['modelValue', 'label', 'error'],
    emits: ['update:modelValue'],
    template:
        '<label>{{ label }}<input type="text" :value="modelValue" @input="$emit(\'update:modelValue\', $event.target.value)" /><span v-if="error" class="error">{{ error }}</span></label>',
}

describe('DischargeFields', () => {
    let pinia

    beforeEach(() => {
        pinia = createTestingPinia({ stubActions: false, createSpy: vi.fn })
    })

    function mountComponent() {
        return mount(DischargeFields, {
            global: {
                plugins: [pinia],
                stubs: { UiDateTimepicker: UiDateTimepickerStub },
            },
        })
    }

    it('does not show First billable shift date until the toggle is on', () => {
        const wrapper = mountComponent()
        expect(wrapper.text()).not.toContain('First billable shift date')
    })

    it('shows First billable shift date once the toggle is turned on', async () => {
        const wrapper = mountComponent()
        await wrapper.find('input[type=checkbox]').setValue(true)
        expect(wrapper.text()).toContain('First billable shift date')
    })

    it('prevents submission and marks Discharge Date as required when empty', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()

        await wrapper.find('button').trigger('click')

        expect(store.submitDischarge).not.toHaveBeenCalled()
        const [expectedDischarge, discharge] =
            wrapper.findAllComponents(UiDateTimepickerStub)
        // Expected Discharge Date is optional.
        expect(expectedDischarge.props('error')).toBeFalsy()
        expect(discharge.props('error')).toBe('Discharge Date is required.')
    })

    it('requires First billable shift date only when its toggle is on', async () => {
        const wrapper = mountComponent()
        await wrapper.find('input[type=checkbox]').setValue(true)

        await wrapper.find('button').trigger('click')

        const shiftDatePicker = wrapper
            .findAllComponents(UiDateTimepickerStub)
            .at(-1)
        expect(shiftDatePicker.props('error')).toBe(
            'First billable shift date is required.',
        )
    })

    it('submits the discharge form when all required fields are filled', async () => {
        const wrapper = mountComponent()
        const store = useHospitalizationTracker()

        const datePickers = wrapper.findAllComponents(UiDateTimepickerStub)
        await datePickers[0].find('input').setValue('3/6/2026') // Expected Discharge Date
        await datePickers[1].find('input').setValue('3/6/2026') // Discharge Date
        await wrapper.find('textarea').setValue('Here are some discharge notes')

        await wrapper.find('button').trigger('click')

        expect(store.submitDischarge).toHaveBeenCalledWith(
            expect.objectContaining({
                expectedDischargeDate: '3/6/2026',
                dischargeDate: '3/6/2026',
                dischargeNotes: 'Here are some discharge notes',
                firstBillableShiftCompleted: false,
            }),
        )
    })
})
