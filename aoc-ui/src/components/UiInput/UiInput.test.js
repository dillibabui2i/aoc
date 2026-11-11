import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import UiInput from './UiInput.vue'

describe('UiInput', () => {
  it('renders the label when provided', () => {
    const wrapper = mount(UiInput, { props: { label: 'Name' } })
    expect(wrapper.find('label').text()).toBe('Name')
  })

  it('emits update:modelValue on input', async () => {
    const wrapper = mount(UiInput)
    await wrapper.find('input').setValue('hello')
    expect(wrapper.emitted('update:modelValue')[0]).toEqual(['hello'])
  })

  it('masks phone input as digits are typed', async () => {
    const wrapper = mount(UiInput, { props: { type: 'phone' } })
    const input = wrapper.find('input')
    await input.setValue('5551234567')
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['+1 (555) 123-4567'])
  })

  it('strips a leading 1 country code when pasted', async () => {
    const wrapper = mount(UiInput, { props: { type: 'phone' } })
    const input = wrapper.find('input')
    await input.setValue('15551234567')
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['+1 (555) 123-4567'])
  })

  it('does not corrupt digits when typed one at a time (regression: "+1" prefix miscounted as a typed digit)', async () => {
    const wrapper = mount(UiInput, { props: { type: 'phone' } })
    const input = wrapper.find('input')
    for (const digit of '2065550123') {
      await input.setValue(input.element.value + digit)
    }
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['+1 (206) 555-0123'])
  })

  it('removes exactly one digit on backspace without corrupting the rest', async () => {
    const wrapper = mount(UiInput, { props: { type: 'phone' } })
    const input = wrapper.find('input')
    for (const digit of '2065550123') {
      await input.setValue(input.element.value + digit)
    }
    await input.setValue(input.element.value.slice(0, -1))
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['+1 (206) 555-012'])
  })

  it('keeps the caret after the edited digit instead of snapping to the end', async () => {
    const wrapper = mount(UiInput, { props: { type: 'phone' } })
    const input = wrapper.find('input')
    for (const digit of '2065550123') {
      await input.setValue(input.element.value + digit)
    }
    const el = input.element
    // Backspace the first "5" of "(206) 555-0123" (native caret already moved to 9 by the browser
    // before this input event fires) — the remaining digits shift left, so the caret should land
    // right after "206" (position 7), not snap to the end of the string.
    el.value = el.value.slice(0, 9) + el.value.slice(10)
    el.setSelectionRange(9, 9)
    await input.trigger('input')
    expect(el.selectionStart).toBe(7)
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual(['+1 (206) 550-123'])
  })

  it('clears back to an empty string, not the "+1 " stub, when all digits are deleted', async () => {
    const wrapper = mount(UiInput, { props: { type: 'phone' } })
    const input = wrapper.find('input')
    for (const digit of '206') {
      await input.setValue(input.element.value + digit)
    }
    for (let i = 0; i < 3; i++) {
      await input.setValue(input.element.value.slice(0, -1))
    }
    expect(wrapper.emitted('update:modelValue').at(-1)).toEqual([''])
  })

  it('shows an error for an invalid email on blur', async () => {
    const wrapper = mount(UiInput, { props: { type: 'email', modelValue: 'not-an-email' } })
    await wrapper.find('input').trigger('blur')
    expect(wrapper.find('.ui-input__error').text()).toBe('Invalid email address')
  })

  it('shows an external error message', () => {
    const wrapper = mount(UiInput, { props: { error: 'Required' } })
    expect(wrapper.find('.ui-input__error').text()).toBe('Required')
    expect(wrapper.classes()).toContain('ui-input-wrap--error')
  })

  it('shows a required error when required and empty', () => {
    const wrapper = mount(UiInput, { props: { label: 'Facility Name', required: true } })
    expect(wrapper.find('.ui-input__error').text()).toBe('Facility Name is required.')
    expect(wrapper.classes()).toContain('ui-input-wrap--error')
  })

  it('clears the required error once filled', () => {
    const wrapper = mount(UiInput, { props: { label: 'Facility Name', required: true, modelValue: 'Test' } })
    expect(wrapper.find('.ui-input__error').exists()).toBe(false)
    expect(wrapper.classes()).not.toContain('ui-input-wrap--error')
  })

  it('disables the input when disabled prop is true', () => {
    const wrapper = mount(UiInput, { props: { disabled: true } })
    expect(wrapper.find('input').attributes('disabled')).toBeDefined()
  })
})
