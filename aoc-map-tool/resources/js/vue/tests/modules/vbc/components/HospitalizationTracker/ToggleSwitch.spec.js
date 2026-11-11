import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import ToggleSwitch from '@/modules/vbc/components/HospitalizationTracker/ToggleSwitch.vue'

describe('ToggleSwitch', () => {
    it('reflects modelValue and emits update:modelValue on toggle', async () => {
        const wrapper = mount(ToggleSwitch, { props: { modelValue: false } })
        const input = wrapper.find('input')

        expect(input.element.checked).toBe(false)

        await input.setValue(true)

        expect(wrapper.emitted('update:modelValue')).toEqual([[true]])
    })

    it('disables the input when disabled is true', () => {
        const wrapper = mount(ToggleSwitch, {
            props: { modelValue: false, disabled: true },
        })
        expect(wrapper.find('input').element.disabled).toBe(true)
    })
})
