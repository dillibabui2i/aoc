import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import DoseTargetRow from '@/modules/vbc/components/DoseTargetRow.vue'

const UNITS = [
    { value: 'MONTH', label: 'Month(s)' },
    { value: 'YEAR', label: 'Years' },
]

const ROW = {
    key: 1,
    from_age: '',
    from_age_unit: 'MONTH',
    to_range: false,
    to_age: '',
    to_age_unit: '',
}

function mountRow(row = {}, errors = {}) {
    return mount(DoseTargetRow, {
        props: { modelValue: { ...ROW, ...row }, units: UNITS, errors },
    })
}

const lastUpdate = (wrapper) => wrapper.emitted('update:modelValue').at(-1)[0]

describe('DoseTargetRow', () => {
    it('keeps only digits typed into the start value', async () => {
        const wrapper = mountRow()

        await wrapper.findAll('input[type="text"]')[0].setValue('a1b2')

        expect(lastUpdate(wrapper).from_age).toBe('12')
    })

    it('keeps only digits typed into the end value', async () => {
        const wrapper = mountRow({ to_range: true, to_age_unit: 'MONTH' })

        await wrapper.findAll('input[type="text"]')[1].setValue('3x')

        expect(lastUpdate(wrapper).to_age).toBe('3')
    })

    it('disables the end value until "to" is ticked', () => {
        expect(
            mountRow().findAll('input[type="text"]')[1].element.disabled,
        ).toBe(true)
        expect(
            mountRow({ to_range: true, to_age_unit: 'MONTH' }).findAll(
                'input[type="text"]',
            )[1].element.disabled,
        ).toBe(false)
    })

    it('ticking "to" takes the start unit as the end unit', async () => {
        const wrapper = mountRow({ from_age_unit: 'YEAR' })

        await wrapper.find('input[type="checkbox"]').setValue(true)

        expect(lastUpdate(wrapper)).toMatchObject({
            to_range: true,
            to_age_unit: 'YEAR',
        })
    })

    it('never lets the end unit be picked: its select is always disabled', () => {
        const selects = mountRow({
            to_range: true,
            to_age_unit: 'MONTH',
        }).findAll('select')

        expect(selects[1].element.disabled).toBe(true)
    })

    it('changing the start unit moves the end unit with it while "to" is ticked', async () => {
        const wrapper = mountRow({
            to_range: true,
            to_age_unit: 'MONTH',
            from_age: '2',
            to_age: '3',
        })

        await wrapper.findAll('select')[0].setValue('YEAR')

        expect(lastUpdate(wrapper)).toMatchObject({
            from_age_unit: 'YEAR',
            to_age_unit: 'YEAR',
        })
    })

    it('changing the start unit leaves the end unit empty while "to" is off', async () => {
        const wrapper = mountRow({ from_age: '2' })

        await wrapper.findAll('select')[0].setValue('YEAR')

        expect(lastUpdate(wrapper)).toMatchObject({
            from_age_unit: 'YEAR',
            to_age_unit: '',
        })
    })

    it('unticking "to" clears the end value and unit', async () => {
        const wrapper = mountRow({
            to_range: true,
            to_age: '6',
            to_age_unit: 'MONTH',
        })

        await wrapper.find('input[type="checkbox"]').setValue(false)

        expect(lastUpdate(wrapper)).toMatchObject({
            to_range: false,
            to_age: '',
            to_age_unit: '',
        })
    })

    it('emits remove from the delete icon', async () => {
        const wrapper = mountRow()

        await wrapper.find('button.dose-target__delete').trigger('click')

        expect(wrapper.emitted('remove')).toHaveLength(1)
    })

    it('shows only the first error message under the row', () => {
        const wrapper = mountRow(
            {},
            {
                from_age: 'Start value in years must be 2 or higher.',
                to_age: 'End value is required.',
            },
        )

        expect(wrapper.find('.dose-target__message').text()).toBe(
            'Start value in years must be 2 or higher.',
        )
    })
})
